<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;

        $filters = [
            'search' => trim((string) $request->query('search')),
            'brand_id' => $request->query('brand_id'),
            'category_id' => $request->query('category_id'),
            'status' => $request->query('status'),
            'expiry_status' => $request->query('expiry_status'),
            'shop_id' => $shopId,
        ];

        $perPage = in_array((int) $request->query('per_page', 10), [10, 20, 30, 50, 100], true) ? (int) $request->query('per_page', 10) : 10;

        $query = InventoryItem::with(['brand', 'category', 'creator', 'shop'])
            ->filtered($filters);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['expiry_status'] === 'expired') {
            $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<', now());
        } elseif ($filters['expiry_status'] === 'expiring_soon') {
            $query->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', now())
                ->whereDate('expiry_date', '<=', now()->addDays(30));
        }

        $products = $query->latest()->paginate($perPage)->withQueryString();

        $brandQuery = Brand::query();
        $categoryQuery = Category::query();
        if ($shopId) {
            $brandQuery->where('shop_id', $shopId);
            $categoryQuery->where('shop_id', $shopId);
        }

        return view('products.index', [
            'products' => $products,
            'filters' => $filters,
            'perPage' => $perPage,
            'brands' => $brandQuery->orderBy('name')->get(),
            'categories' => $categoryQuery->orderBy('name')->get(),
            'shops' => $user->isSuperAdmin() ? Shop::orderBy('name')->get() : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || $user->isShopAdmin(), 403, 'Only Shop Admins can add new products.');

        return $this->form(new InventoryItem, $user);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || $user->isShopAdmin(), 403);

        $validated = $request->validated();
        $shopId = $user->shop_id ?? ($validated['shop_id'] ?? 1);

        DB::transaction(function () use ($validated, $shopId, $user): void {
            $initialQty = (float) ($validated['initial_quantity'] ?? 0);

            $product = new InventoryItem($validated);
            $product->shop_id = $shopId;
            $product->quantity = $initialQty;
            $product->initial_quantity = $initialQty;
            $product->sold_quantity = 0;
            $product->created_by = $user->id;
            $product->save();

            if ($initialQty > 0) {
                InventoryTransaction::create([
                    'shop_id' => $shopId,
                    'inventory_item_id' => $product->id,
                    'transaction_type' => InventoryTransaction::TYPE_STOCK_IN,
                    'quantity' => $initialQty,
                    'balance_before' => 0,
                    'balance_after' => $initialQty,
                    'unit_cost' => $product->purchase_price,
                    'unit_sale_price' => $product->selling_price,
                    'expiry_date' => $product->expiry_date,
                    'notes' => 'Opening / Initial Stock',
                    'created_by' => $user->id,
                ]);
            }
        });

        return redirect()->route('products.index')->with('success', 'Product added to catalog successfully.');
    }

    public function edit(Request $request, InventoryItem $product): View
    {
        $user = $request->user();
        $this->authorizeProductAccess($user, $product);

        return $this->form($product, $user);
    }

    public function update(ProductRequest $request, InventoryItem $product): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeProductAccess($user, $product);

        $validated = $request->validated();
        unset($validated['initial_quantity']); // opening quantity cannot be modified via master edit

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Request $request, InventoryItem $product): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeProductAccess($user, $product);

        if ($product->transactions()->exists()) {
            return back()->with('error', 'This product has transaction history and cannot be deleted. Set it inactive instead.');
        }

        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }

    private function form(InventoryItem $product, $user): View
    {
        $shopId = $user->isSuperAdmin() ? ($product->shop_id ?? request()->query('shop_id')) : $user->shop_id;

        $brandQuery = Brand::where('status', 'active');
        $categoryQuery = Category::where('status', 'active');

        if ($shopId) {
            $brandQuery->where('shop_id', $shopId);
            $categoryQuery->where('shop_id', $shopId);
        }

        if ($product->exists) {
            $brandQuery->orWhere('id', $product->brand_id);
            $categoryQuery->orWhere('id', $product->category_id);
        }

        return view('products.form', [
            'product' => $product,
            'brands' => $brandQuery->orderBy('name')->get(),
            'categories' => $categoryQuery->orderBy('name')->get(),
            'shops' => $user->isSuperAdmin() ? Shop::where('status', 'active')->orderBy('name')->get() : collect(),
            'units' => InventoryItem::UNITS,
        ]);
    }

    private function authorizeProductAccess($user, InventoryItem $product): void
    {
        $isAllowed = $user->isSuperAdmin() || ($user->isShopAdmin() && $user->shop_id === $product->shop_id);

        abort_unless($isAllowed, 403, 'Unauthorized. You cannot edit products from another shop.');
    }
}
