<?php

namespace App\Http\Controllers;

use App\Exports\InventoryExport;
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

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $perPage = in_array((int) $request->query('per_page', 10), [10, 20, 30, 50, 100], true) ? (int) $request->query('per_page', 10) : 10;
        $products = InventoryItem::with(['brand', 'category', 'creator'])->filtered($filters)->latest()->paginate($perPage)->withQueryString();

        return view('stock.index', ['products' => $products, 'filters' => $filters, 'perPage' => $perPage, 'brands' => Brand::orderBy('name')->get(), 'categories' => Category::orderBy('name')->get()]);
    }

    public function addForm(InventoryItem $product): View
    {
        return view('stock.modify', ['product' => $product, 'operation' => 'add']);
    }

    public function sellForm(InventoryItem $product): View
    {
        return view('stock.modify', ['product' => $product, 'operation' => 'sell']);
    }

    public function add(SellInventoryRequest $request, InventoryItem $product): RedirectResponse
    {
        $quantity = (int) $request->validated('sell_quantity');
        DB::transaction(function () use ($product, $quantity, $request): void {
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($product->id);
            $locked->increment('quantity', $quantity);
            InventoryTransaction::create(['inventory_item_id' => $locked->id, 'transaction_type' => InventoryTransaction::TYPE_STOCK_IN, 'quantity' => $quantity, 'created_by' => $request->user()->id]);
        });

        return redirect()->route('stock.index')->with('success', "{$quantity} {$product->item_name} items added successfully.");
    }

    public function sell(SellInventoryRequest $request, InventoryItem $product): RedirectResponse
    {
        $quantity = (int) $request->validated('sell_quantity');
        DB::transaction(function () use ($product, $quantity, $request): void {
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($product->id);
            if ($quantity > $locked->remaining_quantity) {
                throw ValidationException::withMessages(['sell_quantity' => 'Insufficient stock. Only '.number_format($locked->remaining_quantity, 2).' items are available.']);
            }
            $locked->increment('sold_quantity', $quantity);
            InventoryTransaction::create(['inventory_item_id' => $locked->id, 'transaction_type' => InventoryTransaction::TYPE_SALE, 'quantity' => $quantity, 'created_by' => $request->user()->id]);
        });

        return redirect()->route('stock.index')->with('success', "{$quantity} {$product->item_name} items sold successfully.");
    }

    public function history(Request $request, InventoryItem $product): View
    {
        $perPage = in_array((int) $request->query('per_page', 10), [10, 20, 30, 50, 100], true) ? (int) $request->query('per_page', 10) : 10;
        $transactions = $product->transactions()->with('creator')->latest()->paginate($perPage)->withQueryString();

        return view('stock.history', compact('product', 'transactions', 'perPage'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(new InventoryExport($this->filters($request), $request->user()->isSuperAdmin()), 'stock-'.now()->format('Y-m-d-His').'.xlsx');
    }

    private function filters(Request $request): array
    {
        return ['search' => trim((string) $request->query('search')), 'brand_id' => $request->query('brand_id'), 'category_id' => $request->query('category_id')];
    }
}
