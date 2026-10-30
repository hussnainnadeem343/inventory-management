<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;

use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\ProductBatch;
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

        if ($user->isSuperAdmin()) {
            if ($shopId) {
                $targetShop = Shop::find($shopId);
                $query->whereIn('shop_id', $targetShop ? $targetShop->getAllBranchIds() : [$shopId]);
            }
        } else {
            $rootShopId = $user->shop ? $user->shop->getRootBusiness()->id : $user->shop_id;
            $branchIds = $user->shop ? $user->shop->getAllBranchIds() : [$user->shop_id];

            $query->where(function ($q) use ($rootShopId, $branchIds) {
                $q->where('shop_id', $rootShopId)
                    ->orWhere(function ($sub) use ($rootShopId, $branchIds) {
                        $sub->whereIn('shop_id', $branchIds)
                            ->whereNotIn('sku', function ($skuQuery) use ($rootShopId) {
                                $skuQuery->select('sku')
                                    ->from('inventory_items')
                                    ->where('shop_id', $rootShopId)
                                    ->whereNotNull('sku');
                            });
                    });
            });
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
        if ($user->isSuperAdmin()) {
            if ($shopId) {
                $targetShop = Shop::find($shopId);
                $targetIds = $targetShop ? $targetShop->getAllBranchIds() : [$shopId];
                $brandQuery->whereIn('shop_id', $targetIds);
                $categoryQuery->whereIn('shop_id', $targetIds);
            }
        } else {
            $branchIds = $user->shop ? $user->shop->getAllBranchIds() : [$user->shop_id];
            $brandQuery->whereIn('shop_id', $branchIds);
            $categoryQuery->whereIn('shop_id', $branchIds);
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
        abort_unless($user->hasPermission('products.create'), 403, 'Unauthorized. You do not have permission to add new products.');

        return $this->form(new InventoryItem, $user);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermission('products.create'), 403, 'Unauthorized. You do not have permission to add new products.');

        $validated = $request->validated();
        $rootShopId = $user->shop ? $user->shop->getRootBusiness()->id : ($user->shop_id ?? ($validated['shop_id'] ?? 1));
        $branchShopId = $user->shop_id ?? $rootShopId;

        DB::transaction(function () use ($validated, $rootShopId, $branchShopId, $user): void {
            $initialQty = (float) ($validated['initial_quantity'] ?? 0);

            $product = new InventoryItem($validated);
            $product->shop_id = $rootShopId; // Shared catalog belongs to the root business
            $product->quantity = ($branchShopId === $rootShopId) ? $initialQty : 0;
            $product->initial_quantity = ($branchShopId === $rootShopId) ? $initialQty : 0;
            $product->sold_quantity = 0;
            $product->created_by = $user->id;
            $product->save();

            if ($branchShopId !== $rootShopId && $initialQty > 0 && ! empty($product->sku)) {
                $branchItem = InventoryItem::create([
                    'shop_id' => $branchShopId,
                    'sku' => $product->sku,
                    'item_name' => $product->item_name,
                    'brand_id' => $product->brand_id,
                    'category_id' => $product->category_id,
                    'unit' => $product->unit,
                    'pack_size' => $product->pack_size,
                    'liters' => $product->liters,
                    'product_type' => $product->product_type,
                    'is_loose' => $product->is_loose,
                    'purchase_price' => $product->purchase_price,
                    'selling_price' => $product->selling_price,
                    'status' => 'active',
                    'quantity' => $initialQty,
                    'initial_quantity' => $initialQty,
                    'sold_quantity' => 0,
                    'created_by' => $user->id,
                ]);

                $batch = ProductBatch::create([
                    'shop_id' => $branchShopId,
                    'inventory_item_id' => $branchItem->id,
                    'batch_no' => 'BATCH-001',
                    'purchase_price' => $product->purchase_price ?? 0,
                    'selling_price' => $product->selling_price ?? 0,
                    'initial_quantity' => $initialQty,
                    'quantity' => $initialQty,
                    'expiry_date' => $product->expiry_date,
                    'status' => 'active',
                    'created_by' => $user->id,
                ]);

                InventoryTransaction::create([
                    'shop_id' => $branchShopId,
                    'inventory_item_id' => $branchItem->id,
                    'product_batch_id' => $batch->id,
                    'transaction_type' => InventoryTransaction::TYPE_STOCK_IN,
                    'quantity' => $initialQty,
                    'balance_before' => 0,
                    'balance_after' => $initialQty,
                    'unit_cost' => $product->purchase_price,
                    'unit_sale_price' => $product->selling_price,
                    'expiry_date' => $product->expiry_date,
                    'notes' => 'Opening / Initial Stock (Branch)',
                    'created_by' => $user->id,
                ]);
            } elseif ($branchShopId === $rootShopId && $initialQty > 0) {
                $batch = ProductBatch::create([
                    'shop_id' => $branchShopId,
                    'inventory_item_id' => $product->id,
                    'batch_no' => 'BATCH-001',
                    'purchase_price' => $product->purchase_price ?? 0,
                    'selling_price' => $product->selling_price ?? 0,
                    'initial_quantity' => $initialQty,
                    'quantity' => $initialQty,
                    'expiry_date' => $product->expiry_date,
                    'status' => 'active',
                    'created_by' => $user->id,
                ]);

                InventoryTransaction::create([
                    'shop_id' => $branchShopId,
                    'inventory_item_id' => $product->id,
                    'product_batch_id' => $batch->id,
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

        return redirect()->route('products.index')->with('success', 'Product added to business catalog successfully.');
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

        // Universal Selling Price (Option A): synchronize price across all branches for this SKU
        if (! empty($product->sku)) {
            $branchIds = $user->shop ? $user->shop->getAllBranchIds() : [$product->shop_id];
            InventoryItem::whereIn('shop_id', $branchIds)
                ->where('sku', $product->sku)
                ->where('id', '!=', $product->id)
                ->update(['selling_price' => $product->selling_price]);
        }

        return redirect()->route('products.index')->with('success', 'Product updated in catalog successfully.');
    }

    public function destroy(Request $request, InventoryItem $product): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeProductAccess($user, $product, 'products.delete');

        if ($product->transactions()->exists()) {
            return back()->with('error', 'This product has transaction history and cannot be deleted. Set it inactive instead.');
        }

        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }

    private function form(InventoryItem $product, $user): View
    {
        $shopId = $user->isSuperAdmin() ? ($product->shop_id ?? request()->query('shop_id')) : $user->shop_id;
        $shops = $user->isSuperAdmin() ? Shop::where('status', 'active')->orderBy('name')->get() : collect();
        if ($user->isSuperAdmin() && ! $shopId) {
            $shopId = $shops->first()?->id;
        }

        $currentShop = $user->shop ?? ($shopId ? Shop::find($shopId) : null);
        $isLubeBusiness = $currentShop ? $currentShop->isLube() : false;

        $targetIds = $user->isSuperAdmin()
            ? ($shopId ? (Shop::find($shopId)?->getAllBranchIds() ?? [$shopId]) : null)
            : ($user->shop ? $user->shop->getAllBranchIds() : [$user->shop_id]);

        $brandQuery = Brand::where('status', 'active');
        $categoryQuery = Category::where('status', 'active');

        if ($targetIds) {
            $brandQuery->whereIn('shop_id', $targetIds);
            $categoryQuery->whereIn('shop_id', $targetIds);
        }

        if ($product->exists) {
            $brandQuery->orWhere('id', $product->brand_id);
            $categoryQuery->orWhere('id', $product->category_id);
        }

        $units = \App\Models\Inventory\Unit::forShop($shopId)->active()->orderBy('name')->get();
        if ($units->isEmpty()) {
            $units = collect([
                (object)['code' => 'PCS', 'name' => 'Piece'],
                (object)['code' => 'BOX', 'name' => 'Box'],
                (object)['code' => 'PACK', 'name' => 'Pack'],
                (object)['code' => 'LTR', 'name' => 'Litre'],
                (object)['code' => 'DRUM', 'name' => 'Drum'],
                (object)['code' => 'KG', 'name' => 'Kilogram'],
            ]);
        }

        return view('products.form', [
            'product' => $product,
            'brands' => $brandQuery->orderBy('name')->get(),
            'categories' => $categoryQuery->orderBy('name')->get(),
            'shops' => $shops,
            'units' => $units,
            'isLubeBusiness' => $isLubeBusiness,
            'currentShop' => $currentShop,
        ]);
    }

    private function authorizeProductAccess($user, InventoryItem $product, string $permission = 'products.edit'): void
    {
        $branchIds = $user->shop ? $user->shop->getAllBranchIds() : [$user->shop_id];
        $isAllowed = ($user->isSuperAdmin() || in_array((int) $product->shop_id, $branchIds, true)) && $user->hasPermission($permission);

        abort_unless($isAllowed, 403, 'Unauthorized. You do not have permission to manage this product.');
    }
}
