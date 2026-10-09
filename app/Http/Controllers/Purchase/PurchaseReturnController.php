<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProductBatch;
use App\Models\Purchase\GoodsReceivedNote;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Purchase\PurchaseReturnItem;
use App\Models\Purchase\Supplier;
use App\Services\Finance\JournalEntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseReturnController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $search = trim((string) $request->query('search'));

        $query = PurchaseReturn::query()
            ->with(['supplier', 'goodsReceivedNote', 'purchaseInvoice', 'creator'])
            ->forShop($shopId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $returns = $query->latest('id')->paginate(15)->withQueryString();

        return view('purchase.returns.index', compact('returns', 'search', 'shopId'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id', $user->shop_id) : $user->shop_id;
        $grnId = $request->query('goods_received_note_id');

        $selectedGrn = null;
        if ($grnId) {
            $selectedGrn = GoodsReceivedNote::with(['items.inventoryItem', 'items.batch', 'supplier'])->forShop($shopId)->find($grnId);
        }

        $invoiceId = $request->query('purchase_invoice_id');
        $selectedInvoice = null;
        if ($invoiceId) {
            $selectedInvoice = PurchaseInvoice::with(['items.inventoryItem', 'items.batch', 'supplier'])->forShop($shopId)->find($invoiceId);
        }

        $suppliers = Supplier::forShop($shopId)->active()->orderBy('name')->get();
        $products = InventoryItem::where('status', 'active')
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->where('quantity', '>', 0)
            ->with('batches')
            ->orderBy('item_name')
            ->get();

        $productsData = $products->map(fn ($p) => [
            'id' => $p->id,
            'item_name' => $p->item_name,
            'sku' => $p->sku,
            'unit' => $p->unit ?? 'pcs',
            'remaining_quantity' => $p->remaining_quantity,
            'purchase_price' => $p->purchase_price,
            'batches' => $p->batches->map(fn ($b) => [
                'id' => $b->id,
                'batch_number' => $b->batch_number,
                'quantity' => $b->quantity,
                'unit_cost' => $b->unit_cost,
            ]),
        ]);

        $pendingGrns = GoodsReceivedNote::forShop($shopId)
            ->latest('id')
            ->limit(50)
            ->get(['id', 'grn_number', 'supplier_id', 'total_amount', 'received_date']);

        $pendingInvoices = PurchaseInvoice::forShop($shopId)
            ->latest('id')
            ->limit(50)
            ->get(['id', 'invoice_number', 'supplier_id', 'total_amount', 'invoice_date']);

        return view('purchase.returns.create', compact('suppliers', 'products', 'productsData', 'selectedGrn', 'selectedInvoice', 'pendingGrns', 'pendingInvoices', 'shopId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'goods_received_note_id' => ['nullable', 'exists:goods_received_notes,id'],
            'purchase_invoice_id' => ['nullable', 'exists:purchase_invoices,id'],
            'return_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.product_batch_id' => ['nullable', 'exists:product_batches,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.reason' => ['nullable', 'string', 'max:255'],
        ]);

        $return = DB::transaction(function () use ($user, $shopId, $validated) {
            $prefix = 'PRTN-' . now()->format('Ym') . '-';
            $count = PurchaseReturn::where('return_number', 'like', "{$prefix}%")->count() + 1;
            $returnNumber = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += ((float) $item['quantity']) * ((float) $item['unit_cost']);
            }

            $purchaseReturn = PurchaseReturn::create([
                'shop_id' => $shopId,
                'supplier_id' => $validated['supplier_id'],
                'goods_received_note_id' => $validated['goods_received_note_id'] ?? null,
                'purchase_invoice_id' => $validated['purchase_invoice_id'] ?? null,
                'return_number' => $returnNumber,
                'return_date' => $validated['return_date'],
                'subtotal' => $totalAmount,
                'tax_amount' => 0.00,
                'total_amount' => $totalAmount,
                'status' => PurchaseReturn::STATUS_APPROVED,
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $supplier = Supplier::find($validated['supplier_id']);

            foreach ($validated['items'] as $line) {
                $item = InventoryItem::lockForUpdate()->find($line['inventory_item_id']);
                $qty = (float) $line['quantity'];
                $cost = (float) $line['unit_cost'];
                $subtotal = $qty * $cost;

                // 1. Revert batch if specified
                if (!empty($line['product_batch_id'])) {
                    $batch = ProductBatch::lockForUpdate()->find($line['product_batch_id']);
                    if ($batch) {
                        $batch->decrement('quantity', min((float) $batch->quantity, $qty));
                    }
                }

                // 2. Decrement inventory stock
                $balBefore = (float) $item->remaining_quantity;
                $item->decrement('quantity', min((float) $item->quantity, $qty));
                $balAfter = max(0, $balBefore - $qty);

                // 3. Record Inventory Transaction
                InventoryTransaction::create([
                    'shop_id' => $shopId,
                    'inventory_item_id' => $item->id,
                    'product_batch_id' => $line['product_batch_id'] ?? null,
                    'transaction_type' => InventoryTransaction::TYPE_STOCK_OUT,
                    'quantity' => $qty,
                    'balance_before' => $balBefore,
                    'balance_after' => $balAfter,
                    'unit_cost' => $cost,
                    'notes' => "Purchase Return: {$returnNumber} to {$supplier->name}. Reason: " . ($line['reason'] ?? $validated['reason']),
                    'created_by' => $user->id,
                ]);

                // 4. Save item
                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'inventory_item_id' => $item->id,
                    'product_batch_id' => $line['product_batch_id'] ?? null,
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'subtotal' => $subtotal,
                    'reason' => $line['reason'] ?? $validated['reason'],
                ]);
            }

            // 5. Reduce Supplier Current Balance (Debit note reduces debt)
            if ($supplier) {
                $supplier->decrement('current_balance', $totalAmount);
            }

            // 6. Double entry journal voucher
            try {
                app(JournalEntryService::class)->recordPurchaseReturnEntry($purchaseReturn);
            } catch (\Throwable) {
                // Gracefully continue
            }

            return $purchaseReturn;
        });

        return redirect()->route('purchases.returns.show', $return)->with('success', "Debit Note {$return->return_number} issued. Inventory and supplier ledger updated!");
    }

    public function show(PurchaseReturn $return): View
    {
        $return->load(['supplier', 'goodsReceivedNote', 'purchaseInvoice', 'creator', 'items.inventoryItem', 'items.batch']);

        return view('purchase.returns.show', compact('return'));
    }
}
