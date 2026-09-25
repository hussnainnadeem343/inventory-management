<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function sales(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || $user->isShopAdmin(), 403, 'Staff cannot view financial profit reports.');

        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;

        $startDate = $request->query('start_date') ? Carbon::parse($request->query('start_date'))->startOfDay() : now()->startOfMonth();
        $endDate = $request->query('end_date') ? Carbon::parse($request->query('end_date'))->endOfDay() : now()->endOfDay();

        // 1. Gross Sales Aggregation directly in MySQL (high performance)
        $salesQuery = InventoryTransaction::query()
            ->where('transaction_type', InventoryTransaction::TYPE_SALE)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($shopId) {
            $salesQuery->where('shop_id', $shopId);
        }

        $salesStats = (clone $salesQuery)
            ->selectRaw('
                COUNT(id) as total_transactions,
                COALESCE(SUM(quantity), 0) as total_units_sold,
                COALESCE(SUM(quantity * unit_sale_price), 0) as gross_revenue,
                COALESCE(SUM(quantity * unit_cost), 0) as cogs
            ')
            ->first();

        // 2. Returns & Refunds Aggregation
        $returnsQuery = InventoryTransaction::query()
            ->where('transaction_type', InventoryTransaction::TYPE_CUSTOMER_RETURN)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($shopId) {
            $returnsQuery->where('shop_id', $shopId);
        }

        $returnsStats = (clone $returnsQuery)
            ->selectRaw('
                COALESCE(SUM(quantity), 0) as total_units_returned,
                COALESCE(SUM(quantity * unit_sale_price), 0) as total_refunds
            ')
            ->first();

        $grossRevenue = (float) $salesStats->gross_revenue;
        $totalRefunds = (float) $returnsStats->total_refunds;
        $netRevenue = max(0, $grossRevenue - $totalRefunds);
        $cogs = (float) $salesStats->cogs;
        $grossProfit = $netRevenue - $cogs;
        $marginPercent = $netRevenue > 0 ? ($grossProfit / $netRevenue) * 100 : 0;

        // 3. Product breakdown table
        $productBreakdown = (clone $salesQuery)
            ->select('inventory_item_id')
            ->selectRaw('
                SUM(quantity) as units_sold,
                SUM(quantity * unit_sale_price) as revenue,
                SUM(quantity * unit_cost) as cost,
                SUM(quantity * unit_sale_price) - SUM(quantity * unit_cost) as profit
            ')
            ->groupBy('inventory_item_id')
            ->with(['inventoryItem:id,item_name,sku,shop_id', 'inventoryItem.shop:id,name'])
            ->orderByDesc('revenue')
            ->paginate(15)
            ->withQueryString();

        return view('reports.sales', [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'shopId' => $shopId,
            'shops' => $user->isSuperAdmin() ? Shop::orderBy('name')->get() : collect(),
            'totalUnitsSold' => (float) $salesStats->total_units_sold,
            'grossRevenue' => $grossRevenue,
            'totalRefunds' => $totalRefunds,
            'netRevenue' => $netRevenue,
            'cogs' => $cogs,
            'grossProfit' => $grossProfit,
            'marginPercent' => $marginPercent,
            'productBreakdown' => $productBreakdown,
        ]);
    }

    public function stockMovement(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || $user->isShopAdmin(), 403);

        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $startDate = $request->query('start_date') ? Carbon::parse($request->query('start_date'))->startOfDay() : now()->startOfMonth();
        $endDate = $request->query('end_date') ? Carbon::parse($request->query('end_date'))->endOfDay() : now()->endOfDay();

        $query = InventoryTransaction::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $summary = (clone $query)
            ->select('transaction_type')
            ->selectRaw('COUNT(id) as count, SUM(quantity) as total_qty')
            ->groupBy('transaction_type')
            ->pluck('total_qty', 'transaction_type')
            ->toArray();

        $transactions = $query->with(['inventoryItem:id,item_name,sku', 'creator:id,name', 'shop:id,name'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('reports.stock-movement', [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'shopId' => $shopId,
            'shops' => $user->isSuperAdmin() ? Shop::orderBy('name')->get() : collect(),
            'summary' => $summary,
            'transactions' => $transactions,
        ]);
    }

    public function expiry(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $range = $request->query('range', '30'); // 15, 30, 60, or expired

        $query = InventoryItem::with(['brand:id,name', 'category:id,name', 'shop:id,name'])
            ->whereNotNull('expiry_date')
            ->where('quantity', '>', 0);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        if ($range === 'expired') {
            $query->whereDate('expiry_date', '<', now());
        } elseif ($range === '15') {
            $query->whereDate('expiry_date', '>=', now())
                ->whereDate('expiry_date', '<=', now()->addDays(15));
        } elseif ($range === '60') {
            $query->whereDate('expiry_date', '>=', now())
                ->whereDate('expiry_date', '<=', now()->addDays(60));
        } else {
            // default 30 days
            $query->whereDate('expiry_date', '>=', now())
                ->whereDate('expiry_date', '<=', now()->addDays(30));
        }

        $items = $query->orderBy('expiry_date', 'asc')->paginate(20)->withQueryString();

        return view('reports.expiry', [
            'items' => $items,
            'range' => $range,
            'shopId' => $shopId,
            'shops' => $user->isSuperAdmin() ? Shop::orderBy('name')->get() : collect(),
        ]);
    }
}
