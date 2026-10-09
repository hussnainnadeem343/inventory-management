<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Models\Inventory\Warehouse;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProductBatch;
use App\Models\Purchase\GoodsReceivedExpense;
use App\Models\Purchase\GoodsReceivedItem;
use App\Models\Purchase\GoodsReceivedNote;
use App\Models\Purchase\InwardGatePass;
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
            ->with(['supplier', 'purchaseOrder', 'inwardGatePass', 'receiver', 'warehouse', 'items.inventoryItem', 'expenses'])
            ->forShop($shopId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('grn_number', 'like', "%{$search}%")
                    ->orWhere('manual_grn_number', 'like', "%{$search}%")
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
        $igpId = $request->query('inward_gate_pass_id');

        $selectedPo = null;
        if ($poId) {
            $selectedPo = PurchaseOrder::with(['items.inventoryItem', 'supplier'])
                ->forShop($shopId)
                ->whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED])
                ->find($poId);
        }

        $selectedIgp = null;
        if ($igpId) {
            $selectedIgp = InwardGatePass::with(['items.inventoryItem', 'supplier', 'purchaseOrder.items.inventoryItem'])
                ->forShop($shopId)
                ->find($igpId);

            if ($selectedIgp && $selectedIgp->purchaseOrder) {
                $selectedPo = $selectedIgp->purchaseOrder;
            }
        }

        $suppliers = Supplier::forShop($shopId)->active()->orderBy('name')->get(['id', 'name', 'company_name', 'current_balance']);
        $products = InventoryItem::where('status', 'active')
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'sku', 'unit', 'purchase_price', 'selling_price', 'quantity']);

        $pendingOrders = PurchaseOrder::forShop($shopId)
            ->whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED])
            ->latest('id')
            ->get(['id', 'po_number', 'supplier_id', 'grand_total', 'order_date']);

        $pendingIgps = InwardGatePass::forShop($shopId)
            ->where('status', InwardGatePass::STATUS_PENDING)
            ->latest('id')
            ->get(['id', 'igp_number', 'supplier_id', 'purchase_order_id', 'vehicle_number', 'igp_date']);

        $warehouses = Warehouse::forShop($shopId)->where('status', 'active')->get(['id', 'name']);
        $expenseAccounts = Account::forShop($shopId)->orderBy('code')->get(['id', 'code', 'name']);

        return view('purchase.grn.create', compact(
            'suppliers',
            'products',
            'pendingOrders',
            'pendingIgps',
            'selectedPo',
            'selectedIgp',
            'warehouses',
            'expenseAccounts',
            'shopId'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'inward_gate_pass_id' => ['nullable', 'exists:inward_gate_passes,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'received_date' => ['required', 'date'],
            'manual_grn_number' => ['nullable', 'string', 'max:100'],
            'received_via' => ['nullable', 'string', 'max:100'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'supplier_invoice_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.rejected_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'expenses' => ['nullable', 'array'],
            'expenses.*.account_id' => ['nullable', 'exists:accounts,id'],
            'expenses.*.expense_type' => ['nullable', 'string', 'max:100'],
            'expenses.*.comments' => ['nullable', 'string', 'max:255'],
            'expenses.*.quantity' => ['nullable', 'numeric'],
            'expenses.*.rate' => ['nullable', 'numeric'],
            'expenses.*.debit' => ['nullable', 'numeric'],
            'expenses.*.credit' => ['nullable', 'numeric'],
        ]);

        $grn = DB::transaction(function () use ($user, $shopId, $validated) {
            $prefix = 'GRN-' . now()->format('Ym') . '-';
            $count = GoodsReceivedNote::where('grn_number', 'like', "{$prefix}%")->count() + 1;
            $grnNumber = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $acceptedQty = (float) $item['quantity'];
                $totalAmount += $acceptedQty * ((float) $item['unit_cost']);
            }

            $landedExpensesTotal = 0;
            if (!empty($validated['expenses'])) {
                foreach ($validated['expenses'] as $exp) {
                    $debit = (float) ($exp['debit'] ?? 0);
                    $rate = (float) ($exp['rate'] ?? 0);
                    $qty = (float) ($exp['quantity'] ?? 1);
                    $landedExpensesTotal += $debit > 0 ? $debit : ($rate * $qty);
                }
            }

            $grn = GoodsReceivedNote::create([
                'shop_id' => $shopId,
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'inward_gate_pass_id' => $validated['inward_gate_pass_id'] ?? null,
                'supplier_id' => $validated['supplier_id'],
                'grn_number' => $grnNumber,
                'manual_grn_number' => $validated['manual_grn_number'] ?? null,
                'received_via' => $validated['received_via'] ?? null,
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'received_date' => $validated['received_date'],
                'supplier_invoice_no' => $validated['supplier_invoice_no'] ?? null,
                'total_amount' => $totalAmount,
                'landed_expenses_total' => $landedExpensesTotal,
                'notes' => $validated['notes'] ?? null,
                'status' => GoodsReceivedNote::STATUS_RECEIVED,
                'billing_status' => GoodsReceivedNote::BILLING_UNBILLED,
                'received_by' => $user->id,
            ]);

            // If linked to IGP, mark IGP as completed
            if (!empty($validated['inward_gate_pass_id'])) {
                InwardGatePass::where('id', $validated['inward_gate_pass_id'])->update([
                    'status' => InwardGatePass::STATUS_COMPLETED,
                ]);
            }

            $supplier = Supplier::find($validated['supplier_id']);

            foreach ($validated['items'] as $line) {
                $item = InventoryItem::lockForUpdate()->find($line['inventory_item_id']);
                $acceptedQty = (float) $line['quantity'];
                $rejectedQty = (float) ($line['rejected_quantity'] ?? 0);
                $cost = (float) $line['unit_cost'];
                $lineSubtotal = $acceptedQty * $cost;
                $lineWarehouseId = $line['warehouse_id'] ?? $validated['warehouse_id'] ?? null;

                $batchNo = !empty($line['batch_number'])
                    ? trim($line['batch_number'])
                    : 'B-' . now()->format('Ymd') . '-' . substr(uniqid(), -4);

                $expiry = !empty($line['expiry_date']) ? $line['expiry_date'] : null;

                $batch = null;
                if ($acceptedQty > 0) {
                    // 1. Create Product Batch
                    $batch = ProductBatch::create([
                        'shop_id' => $shopId,
                        'inventory_item_id' => $item->id,
                        'batch_no' => $batchNo,
                        'purchase_price' => $cost,
                        'selling_price' => $item->selling_price ?? ($cost * 1.2),
                        'initial_quantity' => $acceptedQty,
                        'quantity' => $acceptedQty,
                        'expiry_date' => $expiry,
                        'status' => 'active',
                        'created_by' => $user->id,
                    ]);

                    // 2. Increment stock in InventoryItem
                    $balBefore = (float) $item->remaining_quantity;
                    $item->increment('quantity', $acceptedQty);
                    $balAfter = $balBefore + $acceptedQty;

                    // 3. Record Inventory Transaction
                    InventoryTransaction::create([
                        'shop_id' => $shopId,
                        'inventory_item_id' => $item->id,
                        'product_batch_id' => $batch->id,
                        'transaction_type' => InventoryTransaction::TYPE_STOCK_IN,
                        'quantity' => $acceptedQty,
                        'balance_before' => $balBefore,
                        'balance_after' => $balAfter,
                        'unit_cost' => $cost,
                        'expiry_date' => $expiry,
                        'notes' => "GRN: {$grnNumber}" . ($supplier ? " from {$supplier->name}" : '') . ($rejectedQty > 0 ? " (Rejected: {$rejectedQty})" : ''),
                        'created_by' => $user->id,
                    ]);
                }

                // 4. Record GRN Item
                GoodsReceivedItem::create([
                    'goods_received_note_id' => $grn->id,
                    'inventory_item_id' => $item->id,
                    'product_batch_id' => $batch?->id,
                    'warehouse_id' => $lineWarehouseId,
                    'batch_number' => $batchNo,
                    'expiry_date' => $expiry,
                    'quantity' => $acceptedQty,
                    'rejected_quantity' => $rejectedQty,
                    'unit_cost' => $cost,
                    'subtotal' => $lineSubtotal,
                ]);
            }

            // Record Landed Expenses
            if (!empty($validated['expenses'])) {
                foreach ($validated['expenses'] as $exp) {
                    if (empty($exp['expense_type']) && empty($exp['debit']) && empty($exp['rate'])) {
                        continue;
                    }
                    GoodsReceivedExpense::create([
                        'goods_received_note_id' => $grn->id,
                        'account_id' => $exp['account_id'] ?? null,
                        'expense_type' => $exp['expense_type'] ?? 'Freight',
                        'comments' => $exp['comments'] ?? null,
                        'quantity' => (float) ($exp['quantity'] ?? 1),
                        'rate' => (float) ($exp['rate'] ?? 0),
                        'debit' => (float) ($exp['debit'] ?? 0),
                        'credit' => (float) ($exp['credit'] ?? 0),
                    ]);
                }
            }

            // 5. Update PO items received quantities if linked
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

            // 6. Update Supplier Balance (Accounts Payable) for quick/direct workflow
            if ($supplier) {
                $supplier->increment('current_balance', $totalAmount);
            }

            // 7. Auto journal entry
            try {
                app(\App\Services\Finance\JournalEntryService::class)->recordPurchaseGrnEntry($grn);
            } catch (\Throwable) {
                // Gracefully continue
            }

            return $grn;
        });

        return redirect()->route('purchases.grn.show', $grn)->with('success', 'GRN ' . $grn->grn_number . ' received successfully. Stock and batches have been updated!');
    }

    public function show(GoodsReceivedNote $grn): View
    {
        $grn->load(['supplier', 'purchaseOrder', 'inwardGatePass', 'receiver', 'warehouse', 'items.inventoryItem', 'items.batch', 'items.warehouse', 'expenses.account']);

        return view('purchase.grn.show', compact('grn'));
    }
}
