<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;

use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;

        $filters = [
            'search' => trim((string) $request->query('search')),
            'status' => $request->query('status'),
            'shop_id' => $shopId,
        ];

        $perPage = in_array((int) $request->query('per_page', 10), [10, 20, 30, 50, 100], true) ? (int) $request->query('per_page', 10) : 10;

        $query = Brand::with(['creator', 'shop'])->filtered($filters);
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $brands = $query->latest()->paginate($perPage)->withQueryString();

        return view('brands.index', [
            'brands' => $brands,
            'filters' => $filters,
            'perPage' => $perPage,
            'shops' => $user->isSuperAdmin() ? Shop::orderBy('name')->get() : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shops = $user->isSuperAdmin() ? Shop::where('status', 'active')->orderBy('name')->get() : collect();
        $selectedShopId = $user->shop_id ?? session('dashboard_shop_id') ?? $shops->first()?->id;

        return view('brands.form', [
            'brand' => new Brand(['shop_id' => $selectedShopId]),
            'shops' => $shops,
            'selectedShopId' => $selectedShopId,
        ]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $shopId = $user->shop_id ?? ($validated['shop_id'] ?? session('dashboard_shop_id') ?? Shop::where('status', 'active')->first()?->id ?? 1);

        $brand = new Brand($validated);
        $brand->shop_id = $shopId;
        $brand->created_by = $user->id;
        $brand->save();

        return redirect()->route('brands.index')->with('success', 'Brand added successfully.');
    }

    public function edit(Request $request, Brand $brand): View
    {
        $this->authorizeBrandManagement($request, $brand);
        $user = $request->user();
        $shops = $user->isSuperAdmin() ? Shop::where('status', 'active')->orderBy('name')->get() : collect();

        return view('brands.form', [
            'brand' => $brand,
            'shops' => $shops,
            'selectedShopId' => $brand->shop_id,
        ]);
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->authorizeBrandManagement($request, $brand);

        $validated = $request->validated();
        if ($request->user()->isSuperAdmin() && isset($validated['shop_id'])) {
            $brand->shop_id = $validated['shop_id'];
        }

        $brand->update($validated);

        return redirect()->route('brands.index')->with('success', 'Brand updated successfully.');
    }

    public function destroy(Request $request, Brand $brand): RedirectResponse
    {
        $this->authorizeBrandManagement($request, $brand);

        if ($brand->inventoryItems()->exists()) {
            return back()->with('error', 'This brand cannot be deleted because it is already associated with inventory items.');
        }

        $brand->delete();

        return back()->with('success', 'Brand deleted successfully.');
    }

    private function authorizeBrandManagement(Request $request, Brand $brand): void
    {
        $user = $request->user();
        $isAllowed = $user->isSuperAdmin() || ($user->isShopAdmin() && $user->shop_id === $brand->shop_id);

        abort_unless($isAllowed, 403, 'Unauthorized action. Only Shop Admins or Super Admins can edit or delete brands.');
    }
}
