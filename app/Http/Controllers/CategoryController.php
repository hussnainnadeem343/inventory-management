<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = in_array((int) $request->query('per_page', 10), [10, 20, 30, 50, 100], true) ? (int) $request->query('per_page', 10) : 10;

        return view('categories.index', ['categories' => Category::with('creator')->latest()->paginate($perPage)->withQueryString(), 'perPage' => $perPage]);
    }

    public function create(): View
    {
        return view('categories.form', ['category' => new Category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = new Category($request->validated());
        $category->created_by = $request->user()->id;
        $category->save();

        return redirect()->route('categories.index')->with('success', 'Category added successfully.');
    }

    public function edit(Category $category): View
    {
        return view('categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->inventoryItems()->exists()) {
            return back()->with('error', 'This category cannot be deleted because it is already associated with inventory items.');
        }$category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }
}
