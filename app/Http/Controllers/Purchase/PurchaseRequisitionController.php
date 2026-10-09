<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Warehouse;
use App\Models\InventoryItem;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\Purchase\PurchaseRequisition;
use App\Models\Purchase\PurchaseRequisitionItem;
use App\Models\Purchase\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseRequisitionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $status = $request->query('status');
        $search = trim((string) $request->query('search'));

        $query = PurchaseRequisition::query()
            ->with(['requester', 'approver', 'warehouse', 'items.inventoryItem'])
            ->forShop($shopId);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('pr_number', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $requisitions = $query->latest('id')->paginate(15)->withQueryString();

        return view('purchase.requisitions.index', compact('requisitions', 'status', 'search', 'shopId'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id', $user->shop_id) : $user->shop_id;

        $products = InventoryItem::where('status', 'active')
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'sku', 'unit', 'purchase_price', 'quantity']);

        $warehouses = Warehouse::forShop($shopId)->where('status', 'active')->get(['id', 'name']);

        return view('purchase.requisitions.create', compact('products', 'warehouses', 'shopId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'requisition_date' => ['required', 'date'],
            'required_by_date' => ['nullable', 'date'],
            'department' => ['nullable', 'string', 'max:100'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.estimated_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        $pr = DB::transaction(function () use ($user, $shopId, $validated) {
            $prefix = 'PR-' . now()->format('Ym') . '-';
            $count = PurchaseRequisition::where('pr_number', 'like', "{$prefix}%")->count() + 1;
            $prNumber = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $requisition = PurchaseRequisition::create([
                'shop_id' => $shopId,
                'pr_number' => $prNumber,
                'requisition_date' => $validated['requisition_date'],
                'required_by_date' => $validated['required_by_date'] ?? null,
                'department' => $validated['department'] ?? null,
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'priority' => $validated['priority'],
                'status' => PurchaseRequisition::STATUS_PENDING,
                'notes' => $validated['notes'] ?? null,
                'requested_by' => $user->id,
            ]);

            foreach ($validated['items'] as $item) {
                PurchaseRequisitionItem::create([
                    'purchase_requisition_id' => $requisition->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => (float) $item['quantity'],
                    'estimated_unit_cost' => !empty($item['estimated_unit_cost']) ? (float) $item['estimated_unit_cost'] : null,
                    'description' => $item['description'] ?? null,
                ]);
            }

            return $requisition;
        });

        return redirect()->route('purchases.requisitions.show', $pr)->with('success', "Requisition {$pr->pr_number} created successfully.");
    }

    public function show(PurchaseRequisition $requisition): View
    {
        $requisition->load(['requester', 'approver', 'warehouse', 'items.inventoryItem', 'purchaseOrders']);

        return view('purchase.requisitions.show', compact('requisition'));
    }

    public function approve(Request $request, PurchaseRequisition $requisition): RedirectResponse
    {
        $user = $request->user();

        $requisition->update([
            'status' => PurchaseRequisition::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', "Requisition {$requisition->pr_number} approved successfully.");
    }

    public function reject(Request $request, PurchaseRequisition $requisition): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $requisition->update([
            'status' => PurchaseRequisition::STATUS_REJECTED,
            'rejection_reason' => $validated['rejection_reason'],
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', "Requisition {$requisition->pr_number} marked as rejected.");
    }

    public function convertToPo(Request $request, PurchaseRequisition $requisition): RedirectResponse
    {
        $suppliers = Supplier::forShop($requisition->shop_id)->active()->get();
        if ($suppliers->isEmpty()) {
            return back()->with('error', 'Please create at least one supplier before generating a Purchase Order.');
        }

        $supplierId = $request->input('supplier_id', $suppliers->first()->id);
        $user = $request->user();

        $po = DB::transaction(function () use ($requisition, $supplierId, $user) {
            $prefix = 'PO-' . now()->format('Ym') . '-';
            $count = PurchaseOrder::where('po_number', 'like', "{$prefix}%")->count() + 1;
            $poNumber = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $subtotal = 0;
            $po = PurchaseOrder::create([
                'shop_id' => $requisition->shop_id,
                'supplier_id' => $supplierId,
                'purchase_requisition_id' => $requisition->id,
                'po_number' => $poNumber,
                'order_date' => now()->format('Y-m-d'),
                'expected_delivery_date' => $requisition->required_by_date ?? now()->addDays(7)->format('Y-m-d'),
                'delivery_station' => $requisition->department ?? 'Main Store',
                'status' => PurchaseOrder::STATUS_DRAFT,
                'subtotal' => 0,
                'grand_total' => 0,
                'notes' => "Generated from Purchase Requisition {$requisition->pr_number}. " . ($requisition->notes ?? ''),
                'created_by' => $user->id,
            ]);

            foreach ($requisition->items as $line) {
                $cost = (float) ($line->estimated_unit_cost ?? $line->inventoryItem->purchase_price ?? 0);
                $lineTotal = (float) $line->quantity * $cost;
                $subtotal += $lineTotal;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'inventory_item_id' => $line->inventory_item_id,
                    'quantity' => $line->quantity,
                    'received_quantity' => 0,
                    'unit_cost' => $cost,
                    'subtotal' => $lineTotal,
                ]);
            }

            $po->update([
                'subtotal' => $subtotal,
                'grand_total' => $subtotal,
            ]);

            $requisition->update(['status' => PurchaseRequisition::STATUS_CONVERTED]);

            return $po;
        });

        return redirect()->route('purchases.orders.show', $po)->with('success', "Purchase Order {$po->po_number} created from Requisition {$requisition->pr_number}.");
    }
}
