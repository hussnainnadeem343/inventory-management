<?php

namespace App\Http\Controllers\Lube;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Lube\LubeServicePackage;
use App\Models\Lube\LubeServicePackageItem;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LubePackageController extends Controller
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

        $query = LubeServicePackage::forShop($shopId)->with('items.inventoryItem');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('vehicle_type')) {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        $packages = $query->latest('id')->paginate(15)->withQueryString();

        $products = InventoryItem::forShop($shopId)
            ->where('quantity', '>', 0)
            ->orWhere('unit', 'LITER')
            ->orderBy('item_name')
            ->get();

        return view('lube.packages.index', compact('packages', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $shopId = $this->getActiveShopId();

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'vehicle_type' => 'nullable|string|max:50',
            'package_price' => 'required|numeric|min:0',
            'labor_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'items' => 'nullable|array',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.1',
        ]);

        DB::transaction(function () use ($validated, $shopId) {
            $package = LubeServicePackage::create([
                'shop_id' => $shopId,
                'name' => trim($validated['name']),
                'vehicle_type' => $validated['vehicle_type'] ?? null,
                'package_price' => (float) $validated['package_price'],
                'labor_amount' => (float) ($validated['labor_amount'] ?? 0),
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $it) {
                    LubeServicePackageItem::create([
                        'package_id' => $package->id,
                        'inventory_item_id' => $it['inventory_item_id'],
                        'quantity' => (float) $it['quantity'],
                    ]);
                }
            }
        });

        return back()->with('success', 'Service package created successfully!');
    }

    public function update(Request $request, LubeServicePackage $package): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'vehicle_type' => 'nullable|string|max:50',
            'package_price' => 'required|numeric|min:0',
            'labor_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $package->update([
            'name' => trim($validated['name']),
            'vehicle_type' => $validated['vehicle_type'] ?? null,
            'package_price' => (float) $validated['package_price'],
            'labor_amount' => (float) ($validated['labor_amount'] ?? 0),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Service package updated successfully!');
    }

    public function destroy(LubeServicePackage $package): RedirectResponse
    {
        $package->delete();

        return back()->with('success', 'Service package removed successfully!');
    }
}
