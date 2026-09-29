<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $search = trim((string) $request->query('search'));
        $paymentMethod = $request->query('payment_method');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Sale::query()
            ->with(['shop', 'creator', 'items.inventoryItem'])
            ->when($shopId, fn ($q) => $q->where('sales.shop_id', $shopId))
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_no', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            })
            ->when($paymentMethod, fn ($q) => $q->where('payment_method', $paymentMethod))
            ->when($startDate, fn ($q) => $q->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('created_at', '<=', $endDate));

        $totalSalesAmount = (clone $query)->sum('total_amount');
        $sales = $query->latest()->paginate(15)->withQueryString();

        return view('sales.index', [
            'sales' => $sales,
            'totalSalesAmount' => (float) $totalSalesAmount,
            'search' => $search,
            'paymentMethod' => $paymentMethod,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'shopId' => $shopId,
            'shops' => $user->isSuperAdmin() ? Shop::orderBy('name')->get() : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shops = collect();

        if ($user->isSuperAdmin()) {
            $shops = Shop::where('status', 'active')->orderBy('name')->get();
            $shopId = $request->query('shop_id') ? (int) $request->query('shop_id') : session('dashboard_shop_id', $shops->first()?->id);
            $currentShop = $shopId ? $shops->firstWhere('id', $shopId) : $shops->first();
            $shopId = $currentShop?->id;
        } else {
            $shopId = $user->shop_id;
            $currentShop = $user->shop;
        }

        abort_if(! $currentShop, 404, 'No active shop available for POS billing.');

        // Load active items with remaining stock for fast instant search
        $products = InventoryItem::query()
            ->where('shop_id', $shopId)
            ->where('status', 'active')
            ->with([
                'brand:id,name',
                'category:id,name',
                'batches' => fn ($q) => $q->active()->fifo(),
            ])
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'sku', 'brand_id', 'category_id', 'quantity', 'selling_price', 'pack_size', 'unit']);

        return view('sales.create', [
            'currentShop' => $currentShop,
            'shops' => $shops,
            'products' => $products,
        ]);
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $shopId = $user->isSuperAdmin()
            ? (int) ($request->input('shop_id') ?: session('dashboard_shop_id') ?: Shop::first()->id)
            : $user->shop_id;

        $shop = Shop::findOrFail($shopId);

        $sale = DB::transaction(function () use ($validated, $shop, $user): Sale {
            // Generate clean, short unique invoice number: INV-1001, INV-1002...
            $lastSale = Sale::where('shop_id', $shop->id)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $nextNumber = 1001;
            if ($lastSale && preg_match('/(\d+)$/', $lastSale->invoice_no, $matches)) {
                $nextNumber = max(1001, ((int) $matches[1]) + 1);
            }

            $invoiceNo = 'INV-' . $nextNumber;

            while (Sale::where('shop_id', $shop->id)->where('invoice_no', $invoiceNo)->exists()) {
                $nextNumber++;
                $invoiceNo = 'INV-' . $nextNumber;
            }

            $rawItems = $validated['items'];
            $lineItemsData = [];
            $grossSubtotal = 0;
            $itemsDiscountTotal = 0;
            $totalUnits = 0;

            foreach ($rawItems as $itemInput) {
                $qty = (float) $itemInput['quantity'];
                $unitPrice = (float) $itemInput['unit_price'];
                $itemDiscount = (isset($itemInput['discount']) && $itemInput['discount'] !== '' && $user->hasPermission('pos.discount_item'))
                    ? max(0, (float) $itemInput['discount'])
                    : 0;

                if ($qty <= 0) {
                    continue;
                }

                $product = InventoryItem::query()
                    ->lockForUpdate()
                    ->where('shop_id', $shop->id)
                    ->findOrFail($itemInput['inventory_item_id']);

                $available = (float) $product->quantity;
                if ($qty > $available) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for '{$product->item_name}'. Available: " . number_format($available, 2) . ", requested: {$qty}.",
                    ]);
                }

                $oldStock = $available;
                $product->quantity -= $qty;
                $product->sold_quantity += $qty;
                $product->save();

                // Auto-FIFO: Deduct from active product batches
                $batches = ProductBatch::where('shop_id', $shop->id)
                    ->where('inventory_item_id', $product->id)
                    ->active()
                    ->fifo()
                    ->lockForUpdate()
                    ->get();

                $remainingToDeduct = $qty;
                $primaryBatchId = null;
                $consumedBatches = [];
                $totalBatchCost = 0;

                foreach ($batches as $batch) {
                    if ($remainingToDeduct <= 0) break;
                    if ($primaryBatchId === null) {
                        $primaryBatchId = $batch->id;
                    }

                    $deduct = min((float) $batch->quantity, $remainingToDeduct);
                    $batch->quantity -= $deduct;
                    if ($batch->quantity <= 0) {
                        $batch->status = 'depleted';
                    }
                    $batch->save();

                    $batchCost = (float) $batch->purchase_price;
                    $totalBatchCost += ($deduct * $batchCost);
                    $consumedBatches[] = [
                        'batch_id' => $batch->id,
                        'quantity' => $deduct,
                        'unit_cost' => $batchCost,
                    ];
                    $remainingToDeduct -= $deduct;
                }

                if ($remainingToDeduct > 0) {
                    $fallbackCost = (float) ($product->purchase_price ?? 0);
                    $totalBatchCost += ($remainingToDeduct * $fallbackCost);
                    $consumedBatches[] = [
                        'batch_id' => null,
                        'quantity' => $remainingToDeduct,
                        'unit_cost' => $fallbackCost,
                    ];
                }

                $effectiveUnitCost = $qty > 0 ? round($totalBatchCost / $qty, 2) : (float) ($product->purchase_price ?? 0);
                $grossLineTotal = round($qty * $unitPrice, 2);
                $lineTotal = max(0, $grossLineTotal - $itemDiscount);

                $grossSubtotal += $grossLineTotal;
                $itemsDiscountTotal += $itemDiscount;
                $totalUnits += $qty;

                $lineItemsData[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_cost' => $effectiveUnitCost,
                    'unit_price' => $unitPrice,
                    'discount' => $itemDiscount,
                    'line_total' => $lineTotal,
                    'primary_batch_id' => $primaryBatchId,
                    'consumed_batches' => $consumedBatches,
                    'old_stock' => $oldStock,
                    'new_stock' => $product->quantity,
                ];
            }

            if (empty($lineItemsData)) {
                throw ValidationException::withMessages([
                    'items' => 'Sale must include at least one valid item with quantity greater than zero.',
                ]);
            }

            $billDiscount = $user->hasPermission('pos.discount_bill')
                ? max(0, (float) ($validated['discount'] ?? 0))
                : 0;
            $totalAmount = max(0, $grossSubtotal - $itemsDiscountTotal - $billDiscount);
            $paidAmount = isset($validated['paid_amount']) && $validated['paid_amount'] !== ''
                ? (float) $validated['paid_amount']
                : $totalAmount;
            $changeAmount = max(0, $paidAmount - $totalAmount);

            $sale = Sale::create([
                'shop_id' => $shop->id,
                'invoice_no' => $invoiceNo,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'total_items' => count($lineItemsData),
                'subtotal' => $grossSubtotal,
                'items_discount_total' => $itemsDiscountTotal,
                'discount' => $billDiscount,
                'total_amount' => $totalAmount,
                'payment_method' => $validated['payment_method'],
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($lineItemsData as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'shop_id' => $shop->id,
                    'inventory_item_id' => $item['product']->id,
                    'product_batch_id' => $item['primary_batch_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'],
                    'line_total' => $item['line_total'],
                ]);

                foreach ($item['consumed_batches'] as $cb) {
                    InventoryTransaction::create([
                        'shop_id' => $shop->id,
                        'sale_id' => $sale->id,
                        'inventory_item_id' => $item['product']->id,
                        'product_batch_id' => $cb['batch_id'],
                        'transaction_type' => InventoryTransaction::TYPE_SALE,
                        'quantity' => $cb['quantity'],
                        'balance_before' => $item['old_stock'],
                        'balance_after' => $item['new_stock'],
                        'unit_cost' => $cb['unit_cost'],
                        'unit_sale_price' => $item['unit_price'],
                        'notes' => "Invoice #{$invoiceNo}",
                        'created_by' => $user->id,
                    ]);
                }
            }

            return $sale;
        });

        return redirect()->route('sales.show', $sale)->with('success', "Sale completed successfully! Invoice #{$sale->invoice_no} generated.");
    }

    public function show(Request $request, Sale $sale): View
    {
        $this->authorizeSaleShop($request->user(), $sale);

        $sale->load(['shop', 'creator', 'items.inventoryItem.brand', 'items.inventoryItem.category']);

        return view('sales.show', [
            'sale' => $sale,
        ]);
    }

    public function receipt(Request $request, Sale $sale): View
    {
        $this->authorizeSaleShop($request->user(), $sale);

        $sale->load(['shop', 'creator', 'items.inventoryItem']);

        return view('sales.receipt', [
            'sale' => $sale,
        ]);
    }

    private function authorizeSaleShop($user, Sale $sale): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if ($sale->shop_id !== $user->shop_id) {
            abort(403, 'Unauthorized. This invoice belongs to a different shop.');
        }
    }
}
