<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $shops = collect();
        $shopId = null;

        if ($user->isSuperAdmin()) {
            $shops = Shop::orderBy('name')->get();

            if ($request->has('shop_id')) {
                $requestedShopId = $request->query('shop_id');
                if ($requestedShopId === 'all' || empty($requestedShopId)) {
                    $shopId = null;
                    $request->session()->forget('dashboard_shop_id');
                } else {
                    $shopId = (int) $requestedShopId;
                    $request->session()->put('dashboard_shop_id', $shopId);
                }
            } else {
                $shopId = $request->session()->get('dashboard_shop_id');
                if ($shopId === null && $shops->isNotEmpty()) {
                    $shopId = $shops->first()->id;
                    $request->session()->put('dashboard_shop_id', $shopId);
                }
            }
        } else {
            $shopId = $user->shop_id;
        }

        $currentShop = $shopId ? Shop::find($shopId) : null;

        $itemsQuery = InventoryItem::query();
        $brandQuery = Brand::query();
        $categoryQuery = Category::query();
        $txnQuery = InventoryTransaction::query();

        if ($shopId) {
            $itemsQuery->where('inventory_items.shop_id', $shopId);
            $brandQuery->where('brands.shop_id', $shopId);
            $categoryQuery->where('categories.shop_id', $shopId);
            $txnQuery->where('inventory_transactions.shop_id', $shopId);
        }

        $stock = (clone $itemsQuery)->selectRaw('
            COUNT(*) as total_items,
            COALESCE(SUM(initial_quantity), 0) as total_initial,
            COALESCE(SUM(sold_quantity), 0) as total_sold,
            COALESCE(SUM(quantity), 0) as total_remaining
        ')->first();

        // Financial KPIs: Sales & Customer Returns for this shop
        $salesRevenue = (float) (clone $txnQuery)
            ->where('inventory_transactions.transaction_type', 'SALE')
            ->selectRaw('COALESCE(SUM(quantity * COALESCE(unit_sale_price, 0)), 0) as total')
            ->value('total');

        $returnsRefund = (float) (clone $txnQuery)
            ->where('inventory_transactions.transaction_type', 'CUSTOMER_RETURN')
            ->selectRaw('COALESCE(SUM(quantity * COALESCE(unit_sale_price, 0)), 0) as total')
            ->value('total');

        $netRevenue = max(0, $salesRevenue - $returnsRefund);

        // Low stock count (remaining <= alert_quantity or 5)
        $lowStockCount = (clone $itemsQuery)
            ->whereRaw('inventory_items.quantity <= COALESCE(inventory_items.alert_quantity, 5) AND inventory_items.quantity > 0')
            ->count();

        $outOfStockCount = (clone $itemsQuery)
            ->where('inventory_items.quantity', '<=', 0)
            ->count();

        // Expiring soon count (within 30 days or expired)
        $expiringCount = (clone $itemsQuery)
            ->whereNotNull('inventory_items.expiry_date')
            ->where('inventory_items.expiry_date', '<=', now()->addDays(30))
            ->count();

        $byCategory = (clone $itemsQuery)
            ->join('categories', 'categories.id', '=', 'inventory_items.category_id')
            ->selectRaw('categories.name as label, SUM(inventory_items.quantity) as total')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $byBrand = (clone $itemsQuery)
            ->join('brands', 'brands.id', '=', 'inventory_items.brand_id')
            ->selectRaw('brands.name as label, SUM(inventory_items.quantity) as total')
            ->groupBy('brands.id', 'brands.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $recentActivity = (clone $txnQuery)
            ->with(['inventoryItem:id,item_name,sku', 'creator:id,name'])
            ->latest('inventory_transactions.created_at')
            ->limit(7)
            ->get();

        $movement = (clone $txnQuery)
            ->where('inventory_transactions.created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('
                DATE(inventory_transactions.created_at) as movement_date,
                SUM(CASE WHEN transaction_type = ? THEN quantity ELSE 0 END) as stock_in,
                SUM(CASE WHEN transaction_type = ? THEN quantity ELSE 0 END) as sold
            ', ['STOCK_IN', 'SALE'])
            ->groupBy('movement_date')
            ->orderBy('movement_date')
            ->get();

        // Urgent Low Stock watchlist (top 5 in this shop)
        $lowStockItems = (clone $itemsQuery)
            ->whereRaw('inventory_items.quantity <= COALESCE(inventory_items.alert_quantity, 5)')
            ->with(['brand:id,name', 'category:id,name'])
            ->orderBy('inventory_items.quantity')
            ->limit(5)
            ->get();

        return view('dashboard.index', [
            'shops' => $shops,
            'currentShop' => $currentShop,
            'selectedShopId' => $shopId,
            'totalItems' => (int) $stock->total_items,
            'totalInitial' => (float) $stock->total_initial,
            'totalSold' => (float) $stock->total_sold,
            'totalRemaining' => (float) $stock->total_remaining,
            'netRevenue' => $netRevenue,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'expiringCount' => $expiringCount,
            'totalBrands' => $brandQuery->count(),
            'totalCategories' => $categoryQuery->count(),
            'byCategory' => $byCategory,
            'byBrand' => $byBrand,
            'recentActivity' => $recentActivity,
            'movement' => $movement,
            'lowStockItems' => $lowStockItems,
        ]);
    }
}
