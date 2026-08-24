<?php

namespace App\Http\Controllers;

use App\Exports\InventoryExport;
use App\Http\Requests\InventoryRequest;
use App\Http\Requests\SellInventoryRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $perPage = in_array((int) $request->query('per_page', 10), [10, 20, 30, 50, 100], true) ? (int) $request->query('per_page', 10) : 10;
        $items = InventoryItem::with(['brand', 'category', 'creator'])->filtered($filters)->latest()->paginate($perPage)->withQueryString();

        return view('inventory.index', ['items' => $items, 'filters' => $filters, 'perPage' => $perPage, 'brands' => Brand::orderBy('name')->get(), 'categories' => Category::orderBy('name')->get()]);
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

    public function sell(SellInventoryRequest $request, InventoryItem $inventory): RedirectResponse
    {
        $quantity = (int) $request->validated('sell_quantity');
        $remaining = DB::transaction(function () use ($inventory, $quantity, $request): float {
            $item = InventoryItem::query()->lockForUpdate()->findOrFail($inventory->id);
            $available = $item->remaining_quantity;
            if ($quantity > $available) {
                throw ValidationException::withMessages(['sell_quantity' => 'Insufficient stock. Only '.number_format($available, 2).' items are available.']);
            }
            $item->increment('sold_quantity', $quantity);
            InventoryTransaction::create(['inventory_item_id' => $item->id, 'transaction_type' => InventoryTransaction::TYPE_SALE, 'quantity' => $quantity, 'created_by' => $request->user()->id]);

            return $available - $quantity;
        });

        return back()->with('success', "{$quantity} {$inventory->item_name} items sold successfully. Remaining stock: ".number_format($remaining, 2));
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(new InventoryExport($this->filters($request), $request->user()->isSuperAdmin()), 'inventory-'.now()->format('Y-m-d-His').'.xlsx');
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

        return redirect()->route('inventory.index')->with('success', 'Inventory deleted successfully.');
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        return ['search' => trim((string) $request->query('search')), 'brand_id' => $request->query('brand_id'), 'category_id' => $request->query('category_id'), 'date' => $validated['date'] ?? null];
    }
}
