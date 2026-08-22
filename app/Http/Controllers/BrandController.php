<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('brands.index', ['brands' => Brand::with('creator')->latest()->paginate(10)]);
    }

    public function create(): View
    {
        return view('brands.form', ['brand' => new Brand]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $brand = new Brand($request->validated());
        $brand->created_by = $request->user()->id;
        $brand->save();

        return redirect()->route('brands.index')->with('success', 'Brand added successfully.');
    }

    public function edit(Brand $brand): View
    {
        return view('brands.form', compact('brand'));
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->validated());

        return redirect()->route('brands.index')->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->inventoryItems()->exists()) {
            return back()->with('error', 'This brand cannot be deleted because it is already associated with inventory items.');
        }$brand->delete();

        return back()->with('success', 'Brand deleted successfully.');
    }
}
