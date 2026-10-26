<?php

namespace App\Http\Controllers\Lube;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Lube\DrumDecanting;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DrumDecantingController extends Controller
{
    protected function getActiveShopId(): ?int
    {
        $user = auth()->user();
        if ($user->shop_id) {
            return $user->shop_id;
        }

        $sessionShopId = session('dashboard_shop_id');
        if ($sessionShopId) {
            return (int) $sessionShopId;
        }

        return Shop::where('status', 'active')->first()?->id;
    }

    public function index(Request $request): View
    {
        $shopId = $this->getActiveShopId();

        $decantings = DrumDecanting::forShop($shopId)
            ->with(['drumItem', 'looseItem', 'creator'])
            ->latest('id')
            ->paginate(15);

        // Fetch candidate bulk drums (STRICTLY items with product_type = 'drum' and quantity > 0)
        $drumProducts = InventoryItem::forShop($shopId)
            ->where('product_type', 'drum')
            ->where('quantity', '>', 0)
            ->orderBy('item_name')
            ->get();

        // Fetch ONLY candidate products explicitly marked as loose product
        $looseProducts = InventoryItem::forShop($shopId)
            ->loose()
            ->orderBy('item_name')
            ->get();

        return view('lube.decanting.index', compact('decantings', 'drumProducts', 'looseProducts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $shopId = $this->getActiveShopId();
        $user = auth()->user();

        $validated = $request->validate([
            'drum_item_id' => 'required|exists:inventory_items,id',
            'loose_item_id' => 'required|exists:inventory_items,id|different:drum_item_id',
            'drums_used' => 'required|numeric|min:0.01',
            'liters_produced' => 'required|numeric|min:0.1',
            'shrinkage_liters' => 'nullable|numeric|min:0',
            'selling_price_per_liter' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $summaryMessage = '';

        DB::transaction(function () use ($validated, $shopId, $user, &$summaryMessage) {
            $drumItem = InventoryItem::forShop($shopId)->lockForUpdate()->findOrFail($validated['drum_item_id']);
            $looseItem = InventoryItem::forShop($shopId)->lockForUpdate()->findOrFail($validated['loose_item_id']);

            if (! $looseItem->isLoose()) {
                throw ValidationException::withMessages([
                    'loose_item_id' => "The selected destination product must be registered or marked as a 'Loose Product'.",
                ]);
            }

            $drumsUsed = (float) $validated['drums_used'];
            $litersProduced = (float) $validated['liters_produced'];
            $shrinkage = (float) ($validated['shrinkage_liters'] ?? 0);

            if ($drumItem->quantity < $drumsUsed) {
                throw ValidationException::withMessages([
                    'drums_used' => "Insufficient drum stock! Available: {$drumItem->quantity} {$drumItem->unit}.",
                ]);
            }

            // Calculate cost per loose liter based on drum purchase cost
            $totalDrumCost = $drumsUsed * (float) ($drumItem->purchase_price ?? 0);
            $costPerLiter = $litersProduced > 0 ? round($totalDrumCost / $litersProduced, 2) : 0;

            // Calculate selling price per loose liter based on drum selling price (or user-supplied override)
            if (isset($validated['selling_price_per_liter']) && (float) $validated['selling_price_per_liter'] > 0) {
                $sellingPricePerLiter = (float) $validated['selling_price_per_liter'];
            } elseif ($drumItem->selling_price && (float) $drumItem->selling_price > 0) {
                $totalDrumSelling = $drumsUsed * (float) $drumItem->selling_price;
                $sellingPricePerLiter = $litersProduced > 0 ? round($totalDrumSelling / $litersProduced, 2) : (float) ($looseItem->selling_price ?? 0);
            } else {
                $sellingPricePerLiter = (float) ($looseItem->selling_price ?? 0);
            }

            // 1. Decrement Drum Stock
            $oldDrumStock = (float) $drumItem->quantity;
            $newDrumStock = max(0, $oldDrumStock - $drumsUsed);
            $drumItem->update(['quantity' => $newDrumStock]);

            InventoryTransaction::create([
                'shop_id' => $shopId,
                'inventory_item_id' => $drumItem->id,
                'transaction_type' => InventoryTransaction::TYPE_STOCK_OUT,
                'quantity' => -$drumsUsed,
                'balance_before' => $oldDrumStock,
                'balance_after' => $newDrumStock,
                'unit_cost' => $drumItem->purchase_price ?? 0,
                'notes' => "Decanted {$drumsUsed} drums into {$litersProduced} L loose liters",
                'created_by' => $user->id,
            ]);

            // Determine loose item unit volume (e.g. 1L, 2L, default 1)
            $looseUnitLiters = (float) ($looseItem->liters ?? $looseItem->pack_size ?? 1.0);
            if ($looseUnitLiters <= 0) {
                $looseUnitLiters = 1.0;
            }

            // Calculate loose units produced: Liters Produced / Liters per Unit (e.g. 250L / 1L = 250 units)
            $looseUnitsProduced = $litersProduced / $looseUnitLiters;

            // 2. Increment Loose Liters Stock & Update Cost / Selling Price
            $oldLooseStock = (float) $looseItem->quantity;
            $newLooseStock = $oldLooseStock + $looseUnitsProduced;

            $costPerUnit = round($costPerLiter * $looseUnitLiters, 2);
            $sellingPerUnit = round($sellingPricePerLiter * $looseUnitLiters, 2);

            $looseUpdate = [
                'quantity' => $newLooseStock,
            ];
            if ($costPerUnit > 0) {
                $looseUpdate['purchase_price'] = $costPerUnit;
            }
            if ($sellingPerUnit > 0) {
                $looseUpdate['selling_price'] = $sellingPerUnit;
            }
            $looseItem->update($looseUpdate);

            InventoryTransaction::create([
                'shop_id' => $shopId,
                'inventory_item_id' => $looseItem->id,
                'transaction_type' => InventoryTransaction::TYPE_STOCK_IN,
                'quantity' => $looseUnitsProduced,
                'balance_before' => $oldLooseStock,
                'balance_after' => $newLooseStock,
                'unit_cost' => $costPerUnit,
                'notes' => "Received {$litersProduced} L ({$looseUnitsProduced} units) from {$drumsUsed} {$drumItem->item_name}",
                'created_by' => $user->id,
            ]);

            // 3. Log Decanting Record
            DrumDecanting::create([
                'shop_id' => $shopId,
                'drum_item_id' => $drumItem->id,
                'loose_item_id' => $looseItem->id,
                'drums_used' => $drumsUsed,
                'liters_produced' => $litersProduced,
                'shrinkage_liters' => $shrinkage,
                'cost_per_liter' => $costPerLiter,
                'selling_price_per_liter' => $sellingPricePerLiter,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $summaryMessage = "Drum decanted successfully! Decanted {$drumsUsed} drum(s) into {$litersProduced} L. Available loose stock is now {$newLooseStock} " . ($looseItem->unit ?? 'L') . " (Cost: Rs. " . number_format($costPerLiter, 2) . "/L | Selling: Rs. " . number_format($sellingPricePerLiter, 2) . "/L).";
        });

        return back()->with('success', $summaryMessage ?: 'Drum decanted successfully! Loose oil stock updated.');
    }
}
