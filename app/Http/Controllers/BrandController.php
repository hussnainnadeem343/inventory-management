<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search')),
            'status' => $request->query('status'),
        ];
        $perPage = in_array((int) $request->query('per_page', 10), [10, 20, 30, 50, 100], true) ? (int) $request->query('per_page', 10) : 10;
        $brands = Brand::with('creator')
            ->filtered($filters)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('brands.index', [
            'brands' => $brands,
            'filters' => $filters,
            'perPage' => $perPage,
        ]);
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
