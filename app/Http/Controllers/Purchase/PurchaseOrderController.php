<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\Purchase\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $search = trim((string) $request->query('search'));
        $status = $request->query('status');

        $query = PurchaseOrder::query()
            ->with(['supplier', 'creator', 'items.inventoryItem'])
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

        $orders = $query->latest('id')->paginate(15)->withQueryString();

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
            ->get(['id', 'item_name', 'sku', 'purchase_price', 'selling_price', 'quantity', 'sold_quantity']);

        return view('purchase.orders.create', compact('suppliers', 'products', 'shopId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += ((float) $item['quantity']) * ((float) $item['unit_cost']);
        }

        $discount = (float) ($validated['discount_amount'] ?? 0);
        $tax = (float) ($validated['tax_amount'] ?? 0);
        $grandTotal = max(0, $subtotal - $discount + $tax);

        $po = DB::transaction(function () use ($user, $shopId, $validated, $subtotal, $discount, $tax, $grandTotal) {
            $prefix = 'PO-' . now()->format('Ym') . '-';
            $count = PurchaseOrder::where('po_number', 'like', "{$prefix}%")->count() + 1;
            $poNumber = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $order = PurchaseOrder::create([
                'shop_id' => $shopId,
                'supplier_id' => $validated['supplier_id'],
                'po_number' => $poNumber,
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'status' => PurchaseOrder::STATUS_APPROVED, // default to approved for easy workflow
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'grand_total' => $grandTotal,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['quantity'];
                $cost = (float) $item['unit_cost'];
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $qty,
                    'received_quantity' => 0,
                    'unit_cost' => $cost,
                    'subtotal' => $qty * $cost,
                ]);
            }

            return $order;
        });

        return redirect()->route('purchases.orders.show', $po)->with('success', 'Purchase Order ' . $po->po_number . ' created successfully.');
    }

    public function show(PurchaseOrder $order): View
    {
        $order->load(['supplier', 'creator', 'items.inventoryItem', 'goodsReceivedNotes.items']);

        return view('purchase.orders.show', compact('order'));
    }

    public function approve(PurchaseOrder $order): RedirectResponse
    {
        if ($order->status !== PurchaseOrder::STATUS_DRAFT) {
            return back()->with('error', 'Only draft purchase orders can be approved.');
        }

        $order->update(['status' => PurchaseOrder::STATUS_APPROVED]);

        return back()->with('success', 'Purchase Order approved successfully.');
    }

    public function cancel(PurchaseOrder $order): RedirectResponse
    {
        if ($order->status === PurchaseOrder::STATUS_RECEIVED || $order->status === PurchaseOrder::STATUS_PARTIALLY_RECEIVED) {
            return back()->with('error', 'Cannot cancel order that has already received goods.');
        }

        $order->update(['status' => PurchaseOrder::STATUS_CANCELLED]);

        return back()->with('success', 'Purchase Order cancelled.');
    }
}
