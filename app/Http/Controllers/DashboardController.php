<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Purchase\GoodsReceivedNote;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseRequisition;
use App\Models\Sale;
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

        // 1. Sales Operational KPIs (Fast & Indexed)
        $salesQuery = Sale::query();
        if ($shopId) {
            $salesQuery->where('sales.shop_id', $shopId);
        }

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $salesToday = (clone $salesQuery)
            ->whereBetween('sales.created_at', [$todayStart, $todayEnd])
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->first();
        $salesTodayRevenue = (float) ($salesToday->total_revenue ?? 0);
        $salesTodayCount = (int) ($salesToday->total_count ?? 0);

        $netSalesTotal = (float) (clone $salesQuery)->sum('total_amount');
        $totalSalesCount = (int) (clone $salesQuery)->count();

        // 2. Purchase & Procurement Operational KPIs
        $prQuery = PurchaseRequisition::query();
        if ($shopId) {
            $prQuery->where('shop_id', $shopId);
        }
        $pendingPrCount = (clone $prQuery)
            ->whereIn('status', [PurchaseRequisition::STATUS_PENDING, 'pending'])
            ->count();

        $poQuery = PurchaseOrder::query();
        if ($shopId) {
            $poQuery->where('shop_id', $shopId);
        }
        $pendingPoCount = (clone $poQuery)
            ->whereIn('status', [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED])
            ->count();

        $grnQuery = GoodsReceivedNote::query();
        if ($shopId) {
            $grnQuery->where('shop_id', $shopId);
        }
        $pendingGrnCount = (clone $grnQuery)
            ->where('billing_status', '!=', GoodsReceivedNote::BILLING_BILLED)
            ->count();

        $piQuery = PurchaseInvoice::query();
        if ($shopId) {
            $piQuery->where('shop_id', $shopId);
        }
        $unpaidInvoicesCount = (clone $piQuery)
            ->whereIn('payment_status', [PurchaseInvoice::STATUS_UNPAID, PurchaseInvoice::STATUS_PARTIALLY_PAID])
            ->count();
        $unpaidInvoicesAmount = (float) (clone $piQuery)
            ->whereIn('payment_status', [PurchaseInvoice::STATUS_UNPAID, PurchaseInvoice::STATUS_PARTIALLY_PAID])
            ->sum('grand_total');

        // 3. Lean Recent Stock Activity (Strict limit 5)
        $txnQuery = InventoryTransaction::query();
        if ($shopId) {
            $txnQuery->where('inventory_transactions.shop_id', $shopId);
        }
        $recentActivity = (clone $txnQuery)
            ->with(['inventoryItem:id,item_name,sku', 'creator:id,name'])
            ->latest('inventory_transactions.created_at')
            ->limit(5)
            ->get(['id', 'inventory_item_id', 'transaction_type', 'quantity', 'created_by', 'created_at']);

        return view('dashboard.index', [
            'shops' => $shops,
            'currentShop' => $currentShop,
            'selectedShopId' => $shopId,
            'salesTodayRevenue' => $salesTodayRevenue,
            'salesTodayCount' => $salesTodayCount,
            'netSalesTotal' => $netSalesTotal,
            'totalSalesCount' => $totalSalesCount,
            'pendingPrCount' => $pendingPrCount,
            'pendingPoCount' => $pendingPoCount,
            'pendingGrnCount' => $pendingGrnCount,
            'unpaidInvoicesCount' => $unpaidInvoicesCount,
            'unpaidInvoicesAmount' => $unpaidInvoicesAmount,
            'recentActivity' => $recentActivity,
        ]);
    }
}
