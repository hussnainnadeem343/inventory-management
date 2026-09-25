<?php

namespace App\Http\Controllers;

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

    public function create(): View
    {
        return view('brands.form', ['brand' => new Brand]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $brand = new Brand($request->validated());
        $brand->shop_id = $request->user()->shop_id ?? $request->input('shop_id', 1);
        $brand->created_by = $request->user()->id;
        $brand->save();

        return redirect()->route('brands.index')->with('success', 'Brand added successfully.');
    }

    public function edit(Request $request, Brand $brand): View
    {
        $this->authorizeBrandManagement($request, $brand);

        return view('brands.form', compact('brand'));
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->authorizeBrandManagement($request, $brand);

        $brand->update($request->validated());

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
