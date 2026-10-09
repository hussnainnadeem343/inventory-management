<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Purchase\InwardGatePass;
use App\Models\Purchase\InwardGatePassItem;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InwardGatePassController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $status = $request->query('status');
        $search = trim((string) $request->query('search'));

        $query = InwardGatePass::query()
            ->with(['supplier', 'purchaseOrder', 'receiver', 'items.inventoryItem'])
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

        $passes = $query->latest('id')->paginate(15)->withQueryString();

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

        return view('purchase.igp.create', compact('suppliers', 'pendingOrders', 'selectedPo', 'products', 'shopId'));
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
            'items.*.declared_quantity' => ['required', 'numeric', 'min:0.01'],
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
                    'declared_quantity' => (float) $item['declared_quantity'],
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
}
