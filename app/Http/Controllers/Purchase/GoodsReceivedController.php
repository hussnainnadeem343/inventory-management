<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProductBatch;
use App\Models\Purchase\GoodsReceivedItem;
use App\Models\Purchase\GoodsReceivedNote;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GoodsReceivedController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $search = trim((string) $request->query('search'));

        $query = GoodsReceivedNote::query()
            ->with(['supplier', 'purchaseOrder', 'receiver', 'items.inventoryItem'])
            ->forShop($shopId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('grn_number', 'like', "%{$search}%")
                    ->orWhere('supplier_invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $grns = $query->latest('id')->paginate(15)->withQueryString();

        return view('purchase.grn.index', compact('grns', 'search', 'shopId'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id', $user->shop_id) : $user->shop_id;

        $poId = $request->query('purchase_order_id');
        $selectedPo = null;

        if ($poId) {
            $selectedPo = PurchaseOrder::with(['items.inventoryItem', 'supplier'])
                ->forShop($shopId)
                ->whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED])
                ->find($poId);
        }

        $suppliers = Supplier::forShop($shopId)->active()->orderBy('name')->get(['id', 'name', 'company_name']);
        $products = InventoryItem::where('status', 'active')
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'sku', 'purchase_price', 'selling_price']);

        $pendingOrders = PurchaseOrder::forShop($shopId)
            ->whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED])
            ->latest('id')
            ->get(['id', 'po_number', 'supplier_id', 'grand_total', 'order_date']);

        return view('purchase.grn.create', compact('suppliers', 'products', 'pendingOrders', 'selectedPo', 'shopId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'received_date' => ['required', 'date'],
            'supplier_invoice_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date'],
        ]);

        $grn = DB::transaction(function () use ($user, $shopId, $validated) {
            $prefix = 'GRN-' . now()->format('Ym') . '-';
            $count = GoodsReceivedNote::where('grn_number', 'like', "{$prefix}%")->count() + 1;
            $grnNumber = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += ((float) $item['quantity']) * ((float) $item['unit_cost']);
            }

            $grn = GoodsReceivedNote::create([
                'shop_id' => $shopId,
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'supplier_id' => $validated['supplier_id'],
                'grn_number' => $grnNumber,
                'received_date' => $validated['received_date'],
                'supplier_invoice_no' => $validated['supplier_invoice_no'] ?? null,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
                'status' => GoodsReceivedNote::STATUS_RECEIVED,
                'received_by' => $user->id,
            ]);

            $supplier = Supplier::find($validated['supplier_id']);

            foreach ($validated['items'] as $line) {
                $item = InventoryItem::lockForUpdate()->find($line['inventory_item_id']);
                $qty = (float) $line['quantity'];
                $cost = (float) $line['unit_cost'];
                $lineSubtotal = $qty * $cost;

                $batchNo = !empty($line['batch_number'])
                    ? trim($line['batch_number'])
                    : 'B-' . now()->format('Ymd') . '-' . substr(uniqid(), -4);

                $expiry = !empty($line['expiry_date']) ? $line['expiry_date'] : null;

                // 1. Create Product Batch
                $batch = ProductBatch::create([
                    'shop_id' => $shopId,
                    'inventory_item_id' => $item->id,
                    'batch_no' => $batchNo,
                    'purchase_price' => $cost,
                    'selling_price' => $item->selling_price ?? ($cost * 1.2),
                    'initial_quantity' => $qty,
                    'quantity' => $qty,
                    'expiry_date' => $expiry,
                    'status' => 'active',
                    'created_by' => $user->id,
                ]);

                // 2. Increment stock in InventoryItem
                $balBefore = (float) $item->remaining_quantity;
                $item->increment('quantity', $qty);
                $balAfter = $balBefore + $qty;

                // 3. Record Inventory Transaction
                InventoryTransaction::create([
                    'shop_id' => $shopId,
                    'inventory_item_id' => $item->id,
                    'product_batch_id' => $batch->id,
                    'transaction_type' => InventoryTransaction::TYPE_STOCK_IN,
                    'quantity' => $qty,
                    'balance_before' => $balBefore,
                    'balance_after' => $balAfter,
                    'unit_cost' => $cost,
                    'expiry_date' => $expiry,
                    'notes' => "GRN: {$grnNumber}" . ($supplier ? " from {$supplier->name}" : ''),
                    'created_by' => $user->id,
                ]);

                // 4. Record GRN Item
                GoodsReceivedItem::create([
                    'goods_received_note_id' => $grn->id,
                    'inventory_item_id' => $item->id,
                    'product_batch_id' => $batch->id,
                    'batch_number' => $batchNo,
                    'expiry_date' => $expiry,
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'subtotal' => $lineSubtotal,
                ]);
            }

            // 5. If linked to PO, update PO line items received quantities
            if (!empty($validated['purchase_order_id'])) {
                $po = PurchaseOrder::with('items')->find($validated['purchase_order_id']);
                if ($po) {
                    $allReceived = true;
                    foreach ($validated['items'] as $line) {
                        $poItem = $po->items->where('inventory_item_id', $line['inventory_item_id'])->first();
                        if ($poItem) {
                            $poItem->increment('received_quantity', (float) $line['quantity']);
                        }
                    }

                    $po->refresh();
                    foreach ($po->items as $poItem) {
                        if ($poItem->received_quantity < $poItem->quantity) {
                            $allReceived = false;
                            break;
                        }
                    }

                    $po->update([
                        'status' => $allReceived ? PurchaseOrder::STATUS_RECEIVED : PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                    ]);
                }
            }

            // 6. Update Supplier Balance (Accounts Payable)
            if ($supplier) {
                $supplier->increment('current_balance', $totalAmount);
            }

            // 7. Auto double-entry journal voucher for Finance
            try {
                app(\App\Services\Finance\JournalEntryService::class)->recordPurchaseGrnEntry($grn);
            } catch (\Throwable) {
                // Gracefully continue
            }

            return $grn;
        });

        return redirect()->route('purchases.grn.show', $grn)->with('success', 'GRN ' . $grn->grn_number . ' received successfully. Stock has been updated!');
    }

    public function show(GoodsReceivedNote $grn): View
    {
        $grn->load(['supplier', 'purchaseOrder', 'receiver', 'items.inventoryItem', 'items.batch']);

        return view('purchase.grn.show', compact('grn'));
    }
}
