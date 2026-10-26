<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
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

        $query = Category::with(['creator', 'shop'])->filtered($filters);
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $categories = $query->latest()->paginate($perPage)->withQueryString();

        return view('categories.index', [
            'categories' => $categories,
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

        return view('categories.form', [
            'category' => new Category(['shop_id' => $selectedShopId]),
            'shops' => $shops,
            'selectedShopId' => $selectedShopId,
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $shopId = $user->shop_id ?? ($validated['shop_id'] ?? session('dashboard_shop_id') ?? Shop::where('status', 'active')->first()?->id ?? 1);

        $category = new Category($validated);
        $category->shop_id = $shopId;
        $category->created_by = $user->id;
        $category->save();

        return redirect()->route('categories.index')->with('success', 'Category added successfully.');
    }

    public function edit(Request $request, Category $category): View
    {
        $this->authorizeCategoryManagement($request, $category);
        $user = $request->user();
        $shops = $user->isSuperAdmin() ? Shop::where('status', 'active')->orderBy('name')->get() : collect();

        return view('categories.form', [
            'category' => $category,
            'shops' => $shops,
            'selectedShopId' => $category->shop_id,
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorizeCategoryManagement($request, $category);

        $validated = $request->validated();
        if ($request->user()->isSuperAdmin() && isset($validated['shop_id'])) {
            $category->shop_id = $validated['shop_id'];
        }

        $category->update($validated);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $this->authorizeCategoryManagement($request, $category);

        if ($category->inventoryItems()->exists()) {
            return back()->with('error', 'This category cannot be deleted because it is already associated with inventory items.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }

    private function authorizeCategoryManagement(Request $request, Category $category): void
    {
        $user = $request->user();
        $isAllowed = $user->isSuperAdmin() || ($user->isShopAdmin() && $user->shop_id === $category->shop_id);

        abort_unless($isAllowed, 403, 'Unauthorized action. Only Shop Admins or Super Admins can edit or delete categories.');
    }
}
