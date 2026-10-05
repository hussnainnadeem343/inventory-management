<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Inventory\StockTransfer;
use App\Models\Inventory\StockTransferItem;
use App\Models\Inventory\Warehouse;
use App\Models\ProductBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $search = trim((string) $request->query('search'));

        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'creator', 'items.inventoryItem'])
            ->forShop($shopId);

        if ($search !== '') {
            $query->where('transfer_number', 'like', "%{$search}%");
        }

        $transfers = $query->latest('id')->paginate(15)->withQueryString();

        return view('inventory.transfers.index', compact('transfers', 'search'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id', $user->shop_id) : $user->shop_id;

        $warehouses = Warehouse::forShop($shopId)->active()->orderBy('name')->get();
        $products = InventoryItem::where('status', 'active')
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->whereRaw('(quantity - sold_quantity) > 0')
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'sku', 'quantity', 'sold_quantity', 'unit']);

        return view('inventory.transfers.create', compact('warehouses', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'from_warehouse_id' => ['required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $transfer = DB::transaction(function () use ($user, $shopId, $validated) {
            $prefix = 'TRF-' . now()->format('Ym') . '-';
            $count = StockTransfer::where('transfer_number', 'like', "{$prefix}%")->count() + 1;
            $trfNo = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $fromWh = Warehouse::find($validated['from_warehouse_id']);
            $toWh = Warehouse::find($validated['to_warehouse_id']);

            $transfer = StockTransfer::create([
                'shop_id' => $shopId,
                'transfer_number' => $trfNo,
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'transfer_date' => $validated['transfer_date'],
                'status' => StockTransfer::STATUS_COMPLETED,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($validated['items'] as $line) {
                $item = InventoryItem::lockForUpdate()->find($line['inventory_item_id']);
                $qty = (float) $line['quantity'];

                if ($qty > (float) $item->remaining_quantity) {
                    throw ValidationException::withMessages([
                        'items' => "Cannot transfer {$qty} of {$item->item_name}. Only " . number_format($item->remaining_quantity, 2) . " available.",
                    ]);
                }

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'inventory_item_id' => $item->id,
                    'quantity' => $qty,
                ]);

                // Record transfer movement in audit ledger
                InventoryTransaction::create([
                    'shop_id' => $shopId,
                    'inventory_item_id' => $item->id,
                    'transaction_type' => InventoryTransaction::TYPE_STOCK_IN,
                    'quantity' => 0, // net total stock doesn't change, godown location changes
                    'balance_before' => $item->remaining_quantity,
                    'balance_after' => $item->remaining_quantity,
                    'notes' => "Transfer #{$trfNo} from {$fromWh->name} to {$toWh->name} (Qty: {$qty})",
                    'created_by' => $user->id,
                ]);
            }

            return $transfer;
        });

        return redirect()->route('transfers.show', $transfer)->with('success', "Stock transfer #{$transfer->transfer_number} completed.");
    }

    public function show(StockTransfer $transfer): View
    {
        $transfer->load(['fromWarehouse', 'toWarehouse', 'creator', 'items.inventoryItem']);

        return view('inventory.transfers.show', compact('transfer'));
    }
}
