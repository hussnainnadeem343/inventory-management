<?php

namespace App\Http\Controllers;

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

    public function create(): View
    {
        return view('categories.form', ['category' => new Category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = new Category($request->validated());
        $category->shop_id = $request->user()->shop_id ?? $request->input('shop_id', 1);
        $category->created_by = $request->user()->id;
        $category->save();

        return redirect()->route('categories.index')->with('success', 'Category added successfully.');
    }

    public function edit(Request $request, Category $category): View
    {
        $this->authorizeCategoryManagement($request, $category);

        return view('categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorizeCategoryManagement($request, $category);

        $category->update($request->validated());

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
