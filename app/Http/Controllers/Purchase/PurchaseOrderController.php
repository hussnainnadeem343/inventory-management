<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Inventory\Warehouse;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\Purchase\PurchaseRequisition;
use App\Models\Purchase\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

use App\Http\Controllers\Concerns\HasPagination;

class PurchaseOrderController extends Controller
{
    use HasPagination;

    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $search = trim((string) $request->query('search'));
        $status = $request->query('status');

        $query = PurchaseOrder::query()
            ->with(['supplier', 'creator', 'approver', 'items.inventoryItem', 'inwardGatePasses', 'goodsReceivedNotes', 'invoices'])
            ->forShop($shopId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $this->paginateWithPerPage($query->latest('id'), $request, default: 10);

        return view('purchase.orders.index', compact('orders', 'search', 'status', 'shopId'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id', $user->shop_id) : $user->shop_id;

        $suppliers = Supplier::forShop($shopId)->active()->orderBy('name')->get(['id', 'name', 'company_name']);
        $products = InventoryItem::where('status', 'active')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'sku', 'unit', 'purchase_price', 'selling_price', 'quantity', 'sold_quantity']);

        $warehouses = Warehouse::forShop($shopId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']);

        $requisitionId = $request->query('requisition_id');
        $selectedRequisition = null;
        if ($requisitionId) {
            $selectedRequisition = PurchaseRequisition::with(['items.inventoryItem'])->forShop($shopId)->find($requisitionId);
        }

        $approvedRequisitions = PurchaseRequisition::forShop($shopId)
            ->whereIn('status', [PurchaseRequisition::STATUS_APPROVED, PurchaseRequisition::STATUS_PENDING])
            ->with(['items'])
            ->latest('id')
            ->limit(50)
            ->get(['id', 'pr_number', 'requisition_date', 'status', 'department']);

        return view('purchase.orders.create', compact('suppliers', 'products', 'warehouses', 'approvedRequisitions', 'selectedRequisition', 'shopId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_requisition_id' => ['nullable', 'exists:purchase_requisitions,id'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'payment_mode' => ['nullable', 'string', 'in:cash,cheque,bank'],
            'payment_term_custom' => ['nullable', 'string', 'max:150'],
            'delivery_station' => ['nullable', 'string', 'max:150'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $subtotal = 0;
        $totalLineDiscount = 0;
        $totalLineTax = 0;

        foreach ($validated['items'] as $item) {
            $qty = (float) $item['quantity'];
            $cost = (float) $item['unit_cost'];
            $lineSub = $qty * $cost;
            $lineDisc = (float) ($item['discount_amount'] ?? 0);
            $lineTaxPct = (float) ($item['tax_percent'] ?? 0);
            $lineTax = ($lineSub - $lineDisc) * ($lineTaxPct / 100);

            $subtotal += $lineSub;
            $totalLineDiscount += $lineDisc;
            $totalLineTax += $lineTax;
        }

        $overallDiscount = (float) ($validated['discount_amount'] ?? 0);
        $discount = max($overallDiscount, $totalLineDiscount);

        $taxRatio = (float) ($validated['tax_percent'] ?? 0);
        $taxFromRatio = $taxRatio > 0 ? ($subtotal - $discount) * ($taxRatio / 100) : 0;
        $tax = $totalLineTax + $taxFromRatio;
        if ($tax <= 0 && !empty($validated['tax_amount'])) {
            $tax = (float) $validated['tax_amount'];
        }

        $grandTotal = max(0, $subtotal - $discount + $tax);

        $po = DB::transaction(function () use ($user, $shopId, $validated, $subtotal, $discount, $taxRatio, $tax, $grandTotal) {
            $prefix = 'PO-' . now()->format('Ym') . '-';
            $count = PurchaseOrder::where('po_number', 'like', "{$prefix}%")->count() + 1;
            $poNumber = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $order = PurchaseOrder::create([
                'shop_id' => $shopId,
                'supplier_id' => $validated['supplier_id'],
                'purchase_requisition_id' => $validated['purchase_requisition_id'] ?? null,
                'po_number' => $poNumber,
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? 'net_30',
                'payment_mode' => $validated['payment_mode'] ?? 'bank',
                'payment_term_custom' => $validated['payment_term_custom'] ?? null,
                'delivery_station' => $validated['delivery_station'] ?? null,
                'status' => PurchaseOrder::STATUS_PENDING, // Default unapproved / pending approval
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_percent' => $taxRatio,
                'tax_amount' => $tax,
                'grand_total' => $grandTotal,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['quantity'];
                $cost = (float) $item['unit_cost'];
                $lineSub = $qty * $cost;
                $lineDisc = (float) ($item['discount_amount'] ?? 0);
                $lineTaxPct = (float) ($item['tax_percent'] ?? 0);
                $lineTax = ($lineSub - $lineDisc) * ($lineTaxPct / 100);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $qty,
                    'received_quantity' => 0,
                    'unit_cost' => $cost,
                    'discount_amount' => $lineDisc,
                    'tax_percent' => $lineTaxPct,
                    'tax_amount' => $lineTax,
                    'subtotal' => ($lineSub - $lineDisc) + $lineTax,
                ]);
            }

            if (!empty($validated['purchase_requisition_id'])) {
                $pr = PurchaseRequisition::find($validated['purchase_requisition_id']);
                if ($pr && $pr->status !== PurchaseRequisition::STATUS_CONVERTED) {
                    $pr->update(['status' => PurchaseRequisition::STATUS_CONVERTED]);
                }
            }

            return $order;
        });

        return redirect()->route('purchases.orders.show', $po)->with('success', 'Purchase Order ' . $po->po_number . ' created successfully and is pending approval.');
    }

    public function show(PurchaseOrder $order): View
    {
        $order->load(['supplier', 'creator', 'approver', 'purchaseRequisition', 'items.inventoryItem', 'inwardGatePasses', 'goodsReceivedNotes.items']);

        return view('purchase.orders.show', compact('order'));
    }

    public function edit(PurchaseOrder $order): View|RedirectResponse
    {
        if (!$order->canBeEditedOrDeleted()) {
            return back()->with('error', "Cannot edit Purchase Order {$order->po_number} because Inward Gate Pass (IGP) or Goods Received Note (GRN) has already been created.");
        }

        $user = auth()->user();
        $shopId = $order->shop_id ?? ($user->isSuperAdmin() ? null : $user->shop_id);

        $suppliers = Supplier::forShop($shopId)->active()->orderBy('name')->get(['id', 'name', 'company_name']);
        $products = InventoryItem::where('status', 'active')
            ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'sku', 'unit', 'purchase_price', 'selling_price', 'quantity', 'sold_quantity']);

        $warehouses = Warehouse::forShop($shopId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']);
        $order->load(['items.inventoryItem', 'supplier', 'purchaseRequisition']);

        return view('purchase.orders.edit', compact('order', 'suppliers', 'products', 'warehouses', 'shopId'));
    }

    public function update(Request $request, PurchaseOrder $order): RedirectResponse
    {
        if (!$order->canBeEditedOrDeleted()) {
            return back()->with('error', "Cannot edit Purchase Order {$order->po_number} because Inward Gate Pass (IGP) or Goods Received Note (GRN) has already been created.");
        }

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'payment_mode' => ['nullable', 'string', 'in:cash,cheque,bank'],
            'payment_term_custom' => ['nullable', 'string', 'max:150'],
            'delivery_station' => ['nullable', 'string', 'max:150'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_percent' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $subtotal = 0;
        $totalLineDiscount = 0;
        $totalLineTax = 0;

        foreach ($validated['items'] as $item) {
            $qty = (float) $item['quantity'];
            $cost = (float) $item['unit_cost'];
            $lineSub = $qty * $cost;
            $lineDisc = (float) ($item['discount_amount'] ?? 0);
            $lineTaxPct = (float) ($item['tax_percent'] ?? 0);
            $lineTax = ($lineSub - $lineDisc) * ($lineTaxPct / 100);

            $subtotal += $lineSub;
            $totalLineDiscount += $lineDisc;
            $totalLineTax += $lineTax;
        }

        $overallDiscount = (float) ($validated['discount_amount'] ?? 0);
        $discount = max($overallDiscount, $totalLineDiscount);

        $taxRatio = (float) ($validated['tax_percent'] ?? 0);
        $taxFromRatio = $taxRatio > 0 ? ($subtotal - $discount) * ($taxRatio / 100) : 0;
        $tax = $totalLineTax + $taxFromRatio;
        if ($tax <= 0 && !empty($validated['tax_amount'])) {
            $tax = (float) $validated['tax_amount'];
        }

        $grandTotal = max(0, $subtotal - $discount + $tax);

        DB::transaction(function () use ($order, $validated, $subtotal, $discount, $taxRatio, $tax, $grandTotal) {
            $order->update([
                'supplier_id' => $validated['supplier_id'],
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? 'net_30',
                'payment_mode' => $validated['payment_mode'] ?? 'bank',
                'payment_term_custom' => $validated['payment_term_custom'] ?? null,
                'delivery_station' => $validated['delivery_station'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_percent' => $taxRatio,
                'tax_amount' => $tax,
                'grand_total' => $grandTotal,
                'notes' => $validated['notes'] ?? null,
            ]);

            $order->items()->delete();

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['quantity'];
                $cost = (float) $item['unit_cost'];
                $lineSub = $qty * $cost;
                $lineDisc = (float) ($item['discount_amount'] ?? 0);
                $lineTaxPct = (float) ($item['tax_percent'] ?? 0);
                $lineTax = ($lineSub - $lineDisc) * ($lineTaxPct / 100);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $qty,
                    'received_quantity' => 0,
                    'unit_cost' => $cost,
                    'discount_amount' => $lineDisc,
                    'tax_percent' => $lineTaxPct,
                    'tax_amount' => $lineTax,
                    'subtotal' => ($lineSub - $lineDisc) + $lineTax,
                ]);
            }
        });

        return redirect()->route('purchases.orders.show', $order)->with('success', "Purchase Order {$order->po_number} updated successfully.");
    }

    public function destroy(PurchaseOrder $order): RedirectResponse
    {
        if (!$order->canBeEditedOrDeleted()) {
            return back()->with('error', "Cannot delete Purchase Order {$order->po_number} because Inward Gate Pass (IGP) or Goods Received Note (GRN) has already been created.");
        }

        DB::transaction(function () use ($order) {
            $order->items()->delete();
            $order->delete();
        });

        return redirect()->route('purchases.orders.index')->with('success', "Purchase Order {$order->po_number} deleted successfully.");
    }

    public function approve(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !$user->isShopAdmin() && !$user->hasPermission('purchases.orders.approve')) {
            abort(403, 'You do not have permission to approve Purchase Orders.');
        }

        if (!$order->isPending() && $order->status !== PurchaseOrder::STATUS_DRAFT) {
            return back()->with('error', 'Only pending approval or draft purchase orders can be approved.');
        }

        $order->update([
            'status' => PurchaseOrder::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', "Purchase Order {$order->po_number} has been approved successfully.");
    }

    public function cancel(PurchaseOrder $order): RedirectResponse
    {
        if ($order->status === PurchaseOrder::STATUS_RECEIVED || $order->status === PurchaseOrder::STATUS_PARTIALLY_RECEIVED) {
            return back()->with('error', 'Cannot cancel order that has already received goods.');
        }

        $order->update(['status' => PurchaseOrder::STATUS_CANCELLED]);

        return back()->with('success', 'Purchase Order cancelled.');
    }

    public function itemsJson(PurchaseOrder $order): JsonResponse
    {
        $order->load(['items.inventoryItem', 'supplier']);

        return response()->json([
            'id' => $order->id,
            'po_number' => $order->po_number,
            'supplier_id' => $order->supplier_id,
            'supplier_name' => $order->supplier?->name,
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'item_name' => $item->inventoryItem?->item_name,
                    'sku' => $item->inventoryItem?->sku,
                    'unit' => $item->inventoryItem?->unit ?? 'PCS',
                    'ordered_quantity' => (float) $item->quantity,
                    'received_quantity' => (float) $item->received_quantity,
                    'pending_quantity' => max(0, (float) $item->quantity - (float) $item->received_quantity),
                    'unit_cost' => (float) $item->unit_cost,
                    'tax_percent' => (float) $item->tax_percent,
                ];
            }),
        ]);
    }
}
