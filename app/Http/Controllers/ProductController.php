<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = ['search' => trim((string) $request->query('search')), 'brand_id' => $request->query('brand_id'), 'category_id' => $request->query('category_id'), 'status' => $request->query('status')];
        $perPage = in_array((int) $request->query('per_page', 10), [10, 20, 30, 50, 100], true) ? (int) $request->query('per_page', 10) : 10;
        $products = InventoryItem::with(['brand', 'category', 'creator'])->filtered($filters)->when($filters['status'], fn ($query, $status) => $query->where('status', $status))->latest()->paginate($perPage)->withQueryString();

        return view('products.index', ['products' => $products, 'filters' => $filters, 'perPage' => $perPage, 'brands' => Brand::orderBy('name')->get(), 'categories' => Category::orderBy('name')->get()]);
    }

    public function create(): View
    {
        return $this->form(new InventoryItem);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = new InventoryItem($request->validated());
        $product->quantity = 0;
        $product->sold_quantity = 0;
        $product->created_by = $request->user()->id;
        $product->save();

        return redirect()->route('products.index')->with('success', 'Product added successfully. Add stock from Stock Management.');
    }

    public function edit(InventoryItem $product): View
    {
        return $this->form($product);
    }

    public function update(ProductRequest $request, InventoryItem $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(InventoryItem $product): RedirectResponse
    {
        if ($product->transactions()->exists()) {
            return back()->with('error', 'This product has stock history and cannot be deleted. Set it inactive instead.');
        }
        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }

    private function form(InventoryItem $product): View
    {
        return view('products.form', ['product' => $product, 'brands' => Brand::where('status', 'active')->orWhere('id', $product->brand_id)->orderBy('name')->get(), 'categories' => Category::where('status', 'active')->orWhere('id', $product->category_id)->orderBy('name')->get()]);
    }
}
