<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', ['categories' => Category::with('creator')->latest()->paginate(10)]);
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
