<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Purchase\InwardGatePass;
use App\Models\Purchase\InwardGatePassItem;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseTransportMode;
use App\Models\Purchase\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Concerns\HasPagination;
use Illuminate\View\View;

class InwardGatePassController extends Controller
{
    use HasPagination;

    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $status = $request->query('status');
        $search = trim((string) $request->query('search'));

        $query = InwardGatePass::query()
            ->with(['supplier', 'purchaseOrder', 'receiver', 'items.inventoryItem', 'goodsReceivedNotes'])
            ->forShop($shopId);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('igp_number', 'like', "%{$search}%")
                    ->orWhere('vehicle_number', 'like', "%{$search}%")
                    ->orWhere('bilty_number', 'like', "%{$search}%")
                    ->orWhere('challan_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $passes = $this->paginateWithPerPage($query->latest('id'), $request, default: 10);

        return view('purchase.igp.index', compact('passes', 'status', 'search', 'shopId'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id', $user->shop_id) : $user->shop_id;
        $poId = $request->query('purchase_order_id');

        $selectedPo = null;
        if ($poId) {
            $selectedPo = PurchaseOrder::with(['items.inventoryItem', 'supplier'])->forShop($shopId)->find($poId);
        }

        $suppliers = Supplier::forShop($shopId)->active()->orderBy('name')->get();
        $pendingOrders = PurchaseOrder::forShop($shopId)
            ->whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED])
            ->latest('id')
            ->get();
        $products = InventoryItem::where('status', 'active')
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'sku', 'unit']);

        $transportModes = PurchaseTransportMode::forShop($shopId)->where('is_active', true)->orderBy('name')->get();

        return view('purchase.igp.create', compact('suppliers', 'pendingOrders', 'selectedPo', 'products', 'transportModes', 'shopId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'igp_date' => ['required', 'date'],
            'gate_entry_time' => ['nullable'],
            'received_via' => ['required', 'string', 'max:100'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'driver_phone' => ['nullable', 'string', 'max:50'],
            'bilty_number' => ['nullable', 'string', 'max:100'],
            'challan_number' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.packages_count' => ['nullable', 'numeric', 'min:0'],
            'items.*.vendor_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.declared_quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rejected_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $igp = DB::transaction(function () use ($user, $shopId, $validated) {
            $prefix = 'IGP-' . now()->format('Ym') . '-';
            $count = InwardGatePass::where('igp_number', 'like', "{$prefix}%")->count() + 1;
            $igpNumber = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $gatePass = InwardGatePass::create([
                'shop_id' => $shopId,
                'supplier_id' => $validated['supplier_id'],
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'igp_number' => $igpNumber,
                'igp_date' => $validated['igp_date'],
                'gate_entry_time' => $validated['gate_entry_time'] ?? now()->format('H:i:s'),
                'received_via' => $validated['received_via'],
                'vehicle_number' => $validated['vehicle_number'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'driver_phone' => $validated['driver_phone'] ?? null,
                'bilty_number' => $validated['bilty_number'] ?? null,
                'challan_number' => $validated['challan_number'] ?? null,
                'status' => InwardGatePass::STATUS_PENDING,
                'remarks' => $validated['remarks'] ?? null,
                'received_by' => $user->id,
            ]);

            foreach ($validated['items'] as $item) {
                InwardGatePassItem::create([
                    'inward_gate_pass_id' => $gatePass->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'packages_count' => (float) ($item['packages_count'] ?? 0),
                    'vendor_quantity' => isset($item['vendor_quantity']) && $item['vendor_quantity'] !== '' ? (float) $item['vendor_quantity'] : (float) $item['declared_quantity'],
                    'declared_quantity' => (float) $item['declared_quantity'],
                    'rejected_quantity' => (float) ($item['rejected_quantity'] ?? 0),
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            return $gatePass;
        });

        return redirect()->route('purchases.igp.show', $igp)->with('success', "Gate Pass {$igp->igp_number} logged successfully.");
    }

    public function show(InwardGatePass $igp): View
    {
        $igp->load(['supplier', 'purchaseOrder', 'receiver', 'items.inventoryItem', 'goodsReceivedNotes']);

        return view('purchase.igp.show', compact('igp'));
    }

    public function edit(InwardGatePass $igp): View|RedirectResponse
    {
        if (!$igp->canBeEditedOrDeleted()) {
            return back()->with('error', "Cannot edit Gate Pass {$igp->igp_number} because Goods Received Note (GRN) has already been created.");
        }

        $user = auth()->user();
        $shopId = $igp->shop_id ?? ($user->isSuperAdmin() ? null : $user->shop_id);

        $suppliers = Supplier::forShop($shopId)->active()->orderBy('name')->get();
        $pendingOrders = PurchaseOrder::forShop($shopId)
            ->whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED])
            ->orWhere('id', $igp->purchase_order_id)
            ->latest('id')
            ->get();
        $products = InventoryItem::where('status', 'active')
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'sku', 'unit']);

        $transportModes = PurchaseTransportMode::forShop($shopId)->where('is_active', true)->orderBy('name')->get();

        $igp->load(['items.inventoryItem', 'supplier', 'purchaseOrder.items.inventoryItem']);

        return view('purchase.igp.edit', compact('igp', 'suppliers', 'pendingOrders', 'products', 'transportModes', 'shopId'));
    }

    public function update(Request $request, InwardGatePass $igp): RedirectResponse
    {
        if (!$igp->canBeEditedOrDeleted()) {
            return back()->with('error', "Cannot edit Gate Pass {$igp->igp_number} because Goods Received Note (GRN) has already been created.");
        }

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'igp_date' => ['required', 'date'],
            'gate_entry_time' => ['nullable'],
            'received_via' => ['required', 'string', 'max:100'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'driver_phone' => ['nullable', 'string', 'max:50'],
            'bilty_number' => ['nullable', 'string', 'max:100'],
            'challan_number' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.packages_count' => ['nullable', 'numeric', 'min:0'],
            'items.*.vendor_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.declared_quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rejected_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($igp, $validated) {
            $igp->update([
                'supplier_id' => $validated['supplier_id'],
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'igp_date' => $validated['igp_date'],
                'gate_entry_time' => $validated['gate_entry_time'] ?? $igp->gate_entry_time,
                'received_via' => $validated['received_via'],
                'vehicle_number' => $validated['vehicle_number'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'driver_phone' => $validated['driver_phone'] ?? null,
                'bilty_number' => $validated['bilty_number'] ?? null,
                'challan_number' => $validated['challan_number'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $igp->items()->delete();

            foreach ($validated['items'] as $item) {
                InwardGatePassItem::create([
                    'inward_gate_pass_id' => $igp->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'packages_count' => (float) ($item['packages_count'] ?? 0),
                    'vendor_quantity' => isset($item['vendor_quantity']) && $item['vendor_quantity'] !== '' ? (float) $item['vendor_quantity'] : (float) $item['declared_quantity'],
                    'declared_quantity' => (float) $item['declared_quantity'],
                    'rejected_quantity' => (float) ($item['rejected_quantity'] ?? 0),
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }
        });

        return redirect()->route('purchases.igp.show', $igp)->with('success', "Gate Pass {$igp->igp_number} updated successfully.");
    }

    public function destroy(InwardGatePass $igp): RedirectResponse
    {
        if (!$igp->canBeEditedOrDeleted()) {
            return back()->with('error', "Cannot delete Gate Pass {$igp->igp_number} because Goods Received Note (GRN) has already been created.");
        }

        DB::transaction(function () use ($igp) {
            $igp->items()->delete();
            $igp->delete();
        });

        return redirect()->route('purchases.igp.index')->with('success', "Gate Pass {$igp->igp_number} deleted successfully.");
    }
}
