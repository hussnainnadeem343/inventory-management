<?php

namespace App\Http\Controllers;

use App\Exports\InventoryExport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;

        $filters = [
            'search' => trim((string) $request->query('search')),
            'brand_id' => $request->query('brand_id'),
            'category_id' => $request->query('category_id'),
            'stock_status' => $request->query('stock_status'),
            'expiry_status' => $request->query('expiry_status'),
            'shop_id' => $shopId,
        ];

        $perPage = in_array((int) $request->query('per_page', 10), [10, 20, 30, 50, 100], true) ? (int) $request->query('per_page', 10) : 10;

        $query = InventoryItem::with(['brand:id,name', 'category:id,name', 'shop:id,name', 'creator:id,name'])
            ->filtered($filters);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        if ($filters['stock_status'] === 'out_of_stock') {
            $query->where('quantity', '<=', 0);
        } elseif ($filters['stock_status'] === 'low_stock') {
            $query->where('quantity', '>', 0)
                ->whereRaw('quantity <= COALESCE(alert_quantity, 5)');
        }

        if ($filters['expiry_status'] === 'expired') {
            $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<', now());
        } elseif ($filters['expiry_status'] === 'expiring_soon') {
            $query->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', now())
                ->whereDate('expiry_date', '<=', now()->addDays(30));
        }

        $products = $query->latest()->paginate($perPage)->withQueryString();

        $brandQuery = Brand::query();
        $categoryQuery = Category::query();
        $shopProductsQuery = InventoryItem::where('status', 'active');

        if ($shopId) {
            $brandQuery->where('shop_id', $shopId);
            $categoryQuery->where('shop_id', $shopId);
            $shopProductsQuery->where('shop_id', $shopId);
        }

        return view('stock.index', [
            'products' => $products,
            'filters' => $filters,
            'perPage' => $perPage,
            'brands' => $brandQuery->orderBy('name')->get(),
            'categories' => $categoryQuery->orderBy('name')->get(),
            'shops' => $user->isSuperAdmin() ? Shop::orderBy('name')->get() : collect(),
            'activeShopProducts' => $shopProductsQuery->select('id', 'item_name', 'sku', 'selling_price', 'quantity')->orderBy('item_name')->get(),
        ]);
    }

    public function add(Request $request, InventoryItem $product): RedirectResponse
    {
        $this->authorizeProductShop($request->user(), $product);

        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $qty = (float) $validated['quantity'];
        $newCost = isset($validated['purchase_price']) && $validated['purchase_price'] !== '' ? (float) $validated['purchase_price'] : null;
        $expiryDate = $validated['expiry_date'] ?? null;
        $notes = $validated['notes'] ?? null;

        DB::transaction(function () use ($product, $qty, $newCost, $expiryDate, $notes, $request): void {
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($product->id);
            $oldStock = (float) $locked->quantity;
            $oldCost = (float) ($locked->purchase_price ?? 0);

            // Calculate Weighted Average Cost (WAC) if a new purchase price was entered
            if ($newCost !== null && $newCost > 0) {
                if ($oldStock > 0 && $oldCost > 0) {
                    $wac = (($oldStock * $oldCost) + ($qty * $newCost)) / ($oldStock + $qty);
                    $locked->purchase_price = round($wac, 2);
                } else {
                    $locked->purchase_price = $newCost;
                }
            }

            if ($expiryDate) {
                $locked->expiry_date = $expiryDate;
            }

            $locked->quantity += $qty;
            $locked->save();

            InventoryTransaction::create([
                'shop_id' => $locked->shop_id,
                'inventory_item_id' => $locked->id,
                'transaction_type' => InventoryTransaction::TYPE_STOCK_IN,
                'quantity' => $qty,
                'balance_before' => $oldStock,
                'balance_after' => $locked->quantity,
                'unit_cost' => $newCost ?? $locked->purchase_price,
                'unit_sale_price' => $locked->selling_price,
                'expiry_date' => $expiryDate ?? $locked->expiry_date,
                'notes' => $notes ?: 'Restock',
                'created_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', "Added {$qty} units to {$product->item_name}. New stock: " . number_format($product->fresh()->quantity, 2));
    }

    public function sell(Request $request, InventoryItem $product): RedirectResponse
    {
        $this->authorizeProductShop($request->user(), $product);

        $validated = $request->validate([
            'sell_quantity' => ['required', 'numeric', 'min:0.01'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $qty = (float) $validated['sell_quantity'];
        $salePriceOverride = isset($validated['selling_price']) && $validated['selling_price'] !== '' ? (float) $validated['selling_price'] : null;
        $notes = $validated['notes'] ?? null;

        DB::transaction(function () use ($product, $qty, $salePriceOverride, $notes, $request): void {
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($product->id);
            $available = (float) $locked->quantity;

            if ($qty > $available) {
                throw ValidationException::withMessages([
                    'sell_quantity' => "Insufficient stock. Only " . number_format($available, 2) . " units are available.",
                ]);
            }

            $oldStock = $available;
            $locked->quantity -= $qty;
            $locked->sold_quantity += $qty;
            $locked->save();

            $unitSalePrice = $salePriceOverride ?? (float) ($locked->selling_price ?? 0);

            InventoryTransaction::create([
                'shop_id' => $locked->shop_id,
                'inventory_item_id' => $locked->id,
                'transaction_type' => InventoryTransaction::TYPE_SALE,
                'quantity' => $qty,
                'balance_before' => $oldStock,
                'balance_after' => $locked->quantity,
                'unit_cost' => $locked->purchase_price,
                'unit_sale_price' => $unitSalePrice,
                'notes' => $notes ?: 'Counter Sale',
                'created_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', "Sold {$qty} units of {$product->item_name}. Remaining stock: " . number_format($product->fresh()->quantity, 2));
    }

    public function customerReturn(Request $request, InventoryItem $product): RedirectResponse
    {
        $this->authorizeProductShop($request->user(), $product);

        $validated = $request->validate([
            'return_quantity' => ['required', 'numeric', 'min:0.01'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $qty = (float) $validated['return_quantity'];
        $refundAmount = isset($validated['refund_amount']) && $validated['refund_amount'] !== '' ? (float) $validated['refund_amount'] : null;
        $reason = $validated['reason'];

        DB::transaction(function () use ($product, $qty, $refundAmount, $reason, $request): void {
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($product->id);
            $oldStock = (float) $locked->quantity;

            $locked->quantity += $qty;
            $locked->sold_quantity = max(0, (float) $locked->sold_quantity - $qty);
            $locked->save();

            $unitRefund = $refundAmount !== null ? ($refundAmount / $qty) : (float) ($locked->selling_price ?? 0);

            InventoryTransaction::create([
                'shop_id' => $locked->shop_id,
                'inventory_item_id' => $locked->id,
                'transaction_type' => InventoryTransaction::TYPE_CUSTOMER_RETURN,
                'quantity' => $qty,
                'balance_before' => $oldStock,
                'balance_after' => $locked->quantity,
                'unit_cost' => $locked->purchase_price,
                'unit_sale_price' => $unitRefund,
                'notes' => 'Customer Return: ' . $reason,
                'created_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', "Customer return processed for {$qty} units of {$product->item_name}. Restocked successfully.");
    }

    public function damageLoss(Request $request, InventoryItem $product): RedirectResponse
    {
        $this->authorizeProductShop($request->user(), $product);

        $validated = $request->validate([
            'damage_quantity' => ['required', 'numeric', 'min:0.01'],
            'damage_type' => ['required', 'in:expired,damaged_broken,lost_audit'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $qty = (float) $validated['damage_quantity'];
        $damageType = $validated['damage_type'];
        $notes = $validated['notes'];

        DB::transaction(function () use ($product, $qty, $damageType, $notes, $request): void {
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($product->id);
            $available = (float) $locked->quantity;

            if ($qty > $available) {
                throw ValidationException::withMessages([
                    'damage_quantity' => "Cannot write off more than available stock (" . number_format($available, 2) . ").",
                ]);
            }

            $oldStock = $available;
            $locked->quantity -= $qty;
            $locked->save();

            $label = match ($damageType) {
                'expired' => 'Expired Stock',
                'damaged_broken' => 'Broken / Leaked',
                default => 'Shelf Audit Loss',
            };

            InventoryTransaction::create([
                'shop_id' => $locked->shop_id,
                'inventory_item_id' => $locked->id,
                'transaction_type' => InventoryTransaction::TYPE_DAMAGE_LOSS,
                'quantity' => $qty,
                'balance_before' => $oldStock,
                'balance_after' => $locked->quantity,
                'unit_cost' => $locked->purchase_price,
                'unit_sale_price' => 0,
                'notes' => "Write-off [{$label}]: " . ($notes ?: 'N/A'),
                'created_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', "Wastage write-off recorded for {$qty} units of {$product->item_name}.");
    }

    public function exchange(Request $request, InventoryItem $product): RedirectResponse
    {
        $this->authorizeProductShop($request->user(), $product);

        $validated = $request->validate([
            'return_quantity' => ['required', 'numeric', 'min:0.01'],
            'new_product_id' => ['required', 'exists:inventory_items,id'],
            'exchange_quantity' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $returnQty = (float) $validated['return_quantity'];
        $newProductId = $validated['new_product_id'];
        $exchangeQty = (float) $validated['exchange_quantity'];
        $notes = $validated['notes'] ?? 'Exchange';

        DB::transaction(function () use ($product, $newProductId, $returnQty, $exchangeQty, $notes, $request): void {
            // 1. Restock returned item
            $returnedItem = InventoryItem::query()->lockForUpdate()->findOrFail($product->id);
            $retOldStock = (float) $returnedItem->quantity;
            $returnedItem->quantity += $returnQty;
            $returnedItem->sold_quantity = max(0, (float) $returnedItem->sold_quantity - $returnQty);
            $returnedItem->save();

            InventoryTransaction::create([
                'shop_id' => $returnedItem->shop_id,
                'inventory_item_id' => $returnedItem->id,
                'transaction_type' => InventoryTransaction::TYPE_EXCHANGE_IN,
                'quantity' => $returnQty,
                'balance_before' => $retOldStock,
                'balance_after' => $returnedItem->quantity,
                'unit_cost' => $returnedItem->purchase_price,
                'unit_sale_price' => $returnedItem->selling_price,
                'notes' => "Exchanged In: {$notes}",
                'created_by' => $request->user()->id,
            ]);

            // 2. Deduct new item
            $newItem = InventoryItem::query()->lockForUpdate()->findOrFail($newProductId);
            $newAvailable = (float) $newItem->quantity;

            if ($exchangeQty > $newAvailable) {
                throw ValidationException::withMessages([
                    'exchange_quantity' => "Insufficient stock for {$newItem->item_name}. Only " . number_format($newAvailable, 2) . " available.",
                ]);
            }

            $newOldStock = $newAvailable;
            $newItem->quantity -= $exchangeQty;
            $newItem->sold_quantity += $exchangeQty;
            $newItem->save();

            InventoryTransaction::create([
                'shop_id' => $newItem->shop_id,
                'inventory_item_id' => $newItem->id,
                'transaction_type' => InventoryTransaction::TYPE_EXCHANGE_OUT,
                'quantity' => $exchangeQty,
                'balance_before' => $newOldStock,
                'balance_after' => $newItem->quantity,
                'unit_cost' => $newItem->purchase_price,
                'unit_sale_price' => $newItem->selling_price,
                'notes' => "Exchanged Out (for {$returnedItem->item_name}): {$notes}",
                'created_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', "Item exchange completed successfully.");
    }

    public function history(Request $request, InventoryItem $product): View
    {
        $this->authorizeProductShop($request->user(), $product);

        $perPage = in_array((int) $request->query('per_page', 15), [10, 15, 25, 50, 100], true) ? (int) $request->query('per_page', 15) : 15;
        $transactions = $product->transactions()
            ->with('creator:id,name')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('stock.history', compact('product', 'transactions', 'perPage'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(new InventoryExport($request->all(), $request->user()->isSuperAdmin()), 'stock-' . now()->format('Y-m-d-His') . '.xlsx');
    }

    private function authorizeProductShop($user, InventoryItem $product): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        abort_unless($user->shop_id === $product->shop_id, 403, 'Unauthorized access to another shop stock.');
    }
}
