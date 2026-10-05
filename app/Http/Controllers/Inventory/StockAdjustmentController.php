<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Inventory\StockAdjustment;
use App\Models\Inventory\StockAdjustmentItem;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $search = trim((string) $request->query('search'));

        $query = StockAdjustment::with(['warehouse', 'creator', 'items.inventoryItem'])
            ->forShop($shopId);

        if ($search !== '') {
            $query->where('adjustment_number', 'like', "%{$search}%")
                ->orWhere('reason', 'like', "%{$search}%");
        }

        $adjustments = $query->latest('id')->paginate(15)->withQueryString();

        return view('inventory.adjustments.index', compact('adjustments', 'search'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id', $user->shop_id) : $user->shop_id;

        $warehouses = Warehouse::forShop($shopId)->active()->orderBy('name')->get();
        $products = InventoryItem::where('status', 'active')
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'sku', 'purchase_price', 'quantity', 'sold_quantity', 'unit']);

        return view('inventory.adjustments.create', compact('warehouses', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'adjustment_date' => ['required', 'date'],
            'type' => ['required', 'in:addition,subtraction'],
            'reason' => ['required', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $adjustment = DB::transaction(function () use ($user, $shopId, $validated) {
            $prefix = 'ADJ-' . now()->format('Ym') . '-';
            $count = StockAdjustment::where('adjustment_number', 'like', "{$prefix}%")->count() + 1;
            $adjNo = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $isAddition = $validated['type'] === 'addition';
            $totalAmount = 0;

            $adjustment = StockAdjustment::create([
                'shop_id' => $shopId,
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'adjustment_number' => $adjNo,
                'adjustment_date' => $validated['adjustment_date'],
                'type' => $validated['type'],
                'reason' => $validated['reason'],
                'total_amount' => 0,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($validated['items'] as $line) {
                $item = InventoryItem::lockForUpdate()->find($line['inventory_item_id']);
                $qty = (float) $line['quantity'];
                $cost = (float) ($item->purchase_price ?? 0);
                $subtotal = $qty * $cost;
                $totalAmount += $subtotal;

                $balBefore = (float) $item->remaining_quantity;

                if ($isAddition) {
                    $item->increment('quantity', $qty);
                    $balAfter = $balBefore + $qty;
                    $transType = InventoryTransaction::TYPE_STOCK_IN;
                } else {
                    if ($qty > $balBefore) {
                        throw ValidationException::withMessages([
                            'items' => "Cannot deduct {$qty} from '{$item->item_name}'. Current stock is {$balBefore}.",
                        ]);
                    }
                    $item->decrement('quantity', $qty);
                    $item->increment('sold_quantity', $qty);
                    $balAfter = $balBefore - $qty;
                    $transType = InventoryTransaction::TYPE_DAMAGE_LOSS;
                }

                StockAdjustmentItem::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'inventory_item_id' => $item->id,
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'subtotal' => $subtotal,
                ]);

                InventoryTransaction::create([
                    'shop_id' => $shopId,
                    'inventory_item_id' => $item->id,
                    'transaction_type' => $transType,
                    'quantity' => $qty,
                    'balance_before' => $balBefore,
                    'balance_after' => $balAfter,
                    'unit_cost' => $cost,
                    'notes' => "Adjustment #{$adjNo}: {$validated['reason']}",
                    'created_by' => $user->id,
                ]);
            }

            $adjustment->update(['total_amount' => $totalAmount]);

            return $adjustment;
        });

        return redirect()->route('adjustments.show', $adjustment)->with('success', "Stock adjustment #{$adjustment->adjustment_number} applied to stock.");
    }

    public function show(StockAdjustment $adjustment): View
    {
        $adjustment->load(['warehouse', 'creator', 'items.inventoryItem']);

        return view('inventory.adjustments.show', compact('adjustment'));
    }
}
