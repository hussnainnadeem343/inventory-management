<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $items = InventoryItem::with(['brand', 'category', 'creator'])->when($search, fn ($q) => $q->where(fn ($q) => $q->where('item_name', 'like', "%$search%")->orWhere('sku', 'like', "%$search%")->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%$search%"))->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%$search%"))))->latest()->paginate(10)->withQueryString();

        return view('inventory.index', compact('items', 'search'));
    }

    public function create(): View
    {
        return view('inventory.form', ['item' => new InventoryItem, 'brands' => Brand::where('status', 'active')->orderBy('name')->get(), 'categories' => Category::where('status', 'active')->orderBy('name')->get(), 'units' => InventoryItem::UNITS]);
    }

    public function store(InventoryRequest $request): RedirectResponse
    {
        $item = new InventoryItem($request->validated());
        $item->created_by = $request->user()->id;
        $item->save();

        return redirect()->route('inventory.index')->with('success', 'Inventory added successfully.');
    }

    public function edit(InventoryItem $inventory): View
{
    return view('inventory.form', [
        'item' => $inventory,

        'brands' => Brand::where('status', 'active')
            ->orWhere('id', $inventory->brand_id)
            ->orderBy('name')
            ->get(),

        'categories' => Category::where('status', 'active')
            ->orWhere('id', $inventory->category_id)
            ->orderBy('name')
            ->get(),

        'units' => InventoryItem::UNITS,
    ]);
}

    public function update(InventoryRequest $request, InventoryItem $inventory): RedirectResponse
    {
        $inventory->update($request->validated());

        return redirect()->route('inventory.index')->with('success', 'Inventory updated successfully.');
    }

    public function destroy(InventoryItem $inventory): RedirectResponse
    {
        $inventory->delete();

        return redirect()->route('inventory.index')->with('success','Inventory deleted successfully.');
    }
}
