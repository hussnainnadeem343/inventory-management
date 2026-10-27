<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Purchase\GoodsReceivedNote;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseRequisition;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $shops = collect();
        $selectedShopId = null;
        $allowedShopIds = null;

        if ($user->isSuperAdmin()) {
            $shops = Shop::orderBy('name')->get();

            if ($request->has('shop_id')) {
                $requestedShopId = $request->query('shop_id');
                if ($requestedShopId === 'all' || empty($requestedShopId)) {
                    $selectedShopId = null;
                    $request->session()->forget('dashboard_shop_id');
                } else {
                    $selectedShopId = (int) $requestedShopId;
                    $request->session()->put('dashboard_shop_id', $selectedShopId);
                }
            } else {
                $selectedShopId = $request->session()->get('dashboard_shop_id');
            }

            // If no specific shop is selected, render the dedicated Platform Owner Dashboard (DenFin Super Admin)
            if (! $selectedShopId) {
                $totalBusinessesCount = Shop::whereNull('parent_id')->count();
                $activeBusinessesCount = Shop::whereNull('parent_id')->where('status', 'active')->count();
                $totalBranchesCount = Shop::whereNotNull('parent_id')->count();
                $totalPlatformUsers = User::where('role', '!=', 'super_admin')->count();

                $recentBusinesses = Shop::whereNull('parent_id')
                    ->withCount(['branches', 'users'])
                    ->latest()
                    ->limit(6)
                    ->get();

                return view('dashboard.index', [
                    'isPlatformAdmin' => true,
                    'totalBusinessesCount' => $totalBusinessesCount,
                    'activeBusinessesCount' => $activeBusinessesCount,
                    'totalBranchesCount' => $totalBranchesCount,
                    'totalPlatformUsers' => $totalPlatformUsers,
                    'recentBusinesses' => $recentBusinesses,
                    'shops' => $shops,
                    'currentShop' => null,
                    'selectedShopId' => null,
                    'canSeeSales' => false,
                    'canSeePurchases' => false,
                    'canSeeStock' => false,
                    'canSeeLube' => false,
                    'salesTodayRevenue' => 0.0,
                    'salesTodayCount' => 0,
                    'netSalesTotal' => 0.0,
                    'totalSalesCount' => 0,
                    'pendingPrCount' => 0,
                    'pendingPoCount' => 0,
                    'pendingGrnCount' => 0,
                    'unpaidInvoicesCount' => 0,
                    'unpaidInvoicesAmount' => 0.0,
                    'recentActivity' => collect(),
                ]);
            }
        } elseif ($user->isBusinessOwner()) {
            $business = $user->shop?->getRootBusiness();
            if ($business) {
                $allowedShopIds = $business->getAllBranchIds();
                $shops = Shop::whereIn('id', $allowedShopIds)->orderBy('name')->get();

                if ($request->has('shop_id')) {
                    $requestedShopId = $request->query('shop_id');
                    if ($requestedShopId === 'all' || empty($requestedShopId)) {
                        $selectedShopId = null;
                        $request->session()->forget('dashboard_shop_id');
                    } elseif (in_array((int) $requestedShopId, $allowedShopIds, true)) {
                        $selectedShopId = (int) $requestedShopId;
                        $request->session()->put('dashboard_shop_id', $selectedShopId);
                    }
                } else {
                    $sessionShopId = $request->session()->get('dashboard_shop_id');
                    if ($sessionShopId && in_array((int) $sessionShopId, $allowedShopIds, true)) {
                        $selectedShopId = (int) $sessionShopId;
                    } else {
                        $selectedShopId = null; // Consolidated by default
                    }
                }
            } else {
                $selectedShopId = $user->shop_id;
            }
        } else {
            $selectedShopId = $user->shop_id;
        }

        $currentShop = $selectedShopId ? Shop::find($selectedShopId) : null;

        // Scoping closure to apply appropriate shop filtering
        $applyShopFilter = function ($query, string $column = 'shop_id') use ($selectedShopId, $allowedShopIds, $user) {
            if ($selectedShopId) {
                $query->where($column, $selectedShopId);
            } elseif ($user->isBusinessOwner() && $allowedShopIds) {
                $query->whereIn($column, $allowedShopIds);
            } elseif (! $user->isSuperAdmin() && $user->shop_id) {
                $query->where($column, $user->shop_id);
            }
        };

        // Determine granular module & screen view permissions
        $canSeeSales = $user->hasAnyPermission('pos.access', 'sales.view_invoices', 'reports.sales');
        $canSeePurchases = ($currentShop ? $currentShop->isModuleAvailable('purchases') : true) && $user->hasPermission('purchases.view');
        $canSeeStock = ($currentShop ? $currentShop->isModuleAvailable('stock') : true) && $user->hasPermission('stock.view');
        $canSeeLube = ($currentShop ? $currentShop->isModuleAvailable('lube') : $user->isSuperAdmin()) && $user->hasAnyPermission('lube.pos', 'lube.analytics', 'lube.packages', 'lube.vehicles');

        // Smart redirect for restricted POS-only users
        if (! $user->isSuperAdmin() && ! $user->isShopAdmin() && ! $user->isBusinessOwner()) {
            if ($user->hasPermission('pos.access') && ! $canSeePurchases && ! $canSeeStock && ! $user->hasPermission('reports.sales')) {
                return redirect()->route('pos');
            }
        }

        // 1. Sales Operational KPIs (Fast & Indexed)
        $salesTodayRevenue = 0.0;
        $salesTodayCount = 0;
        $salesTodayCash = 0.0;
        $salesTodayDigital = 0.0;
        $salesTodayCredit = 0.0;
        $netSalesTotal = 0.0;
        $totalSalesCount = 0;

        if ($canSeeSales) {
            $salesQuery = Sale::query();
            $applyShopFilter($salesQuery, 'sales.shop_id');

            $todayStart = now()->startOfDay();
            $todayEnd = now()->endOfDay();

            $salesToday = (clone $salesQuery)
                ->whereBetween('sales.created_at', [$todayStart, $todayEnd])
                ->selectRaw('
                    COUNT(*) as total_count,
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN LOWER(payment_method) = "cash" THEN total_amount ELSE 0 END), 0) as cash_revenue,
                    COALESCE(SUM(CASE WHEN LOWER(payment_method) = "card" THEN total_amount ELSE 0 END), 0) as card_revenue,
                    COALESCE(SUM(CASE WHEN LOWER(payment_method) = "bank" THEN total_amount ELSE 0 END), 0) as bank_revenue,
                    COALESCE(SUM(CASE WHEN LOWER(payment_method) = "credit" THEN total_amount ELSE 0 END), 0) as credit_revenue
                ')
                ->first();
            $salesTodayRevenue = (float) ($salesToday->total_revenue ?? 0);
            $salesTodayCount = (int) ($salesToday->total_count ?? 0);
            $salesTodayCash = (float) ($salesToday->cash_revenue ?? 0);
            $salesTodayDigital = (float) (($salesToday->card_revenue ?? 0) + ($salesToday->bank_revenue ?? 0));
            $salesTodayCredit = (float) ($salesToday->credit_revenue ?? 0);

            $netSalesTotal = (float) (clone $salesQuery)->sum('total_amount');
            $totalSalesCount = (int) (clone $salesQuery)->count();
        }

        // 2. Purchase & Procurement Operational KPIs
        $pendingPrCount = 0;
        $pendingPoCount = 0;
        $pendingGrnCount = 0;
        $unpaidInvoicesCount = 0;
        $unpaidInvoicesAmount = 0.0;

        if ($canSeePurchases) {
            $prQuery = PurchaseRequisition::query();
            $applyShopFilter($prQuery, 'shop_id');
            $pendingPrCount = (clone $prQuery)
                ->whereIn('status', [PurchaseRequisition::STATUS_PENDING, 'pending'])
                ->count();

            $poQuery = PurchaseOrder::query();
            $applyShopFilter($poQuery, 'shop_id');
            $pendingPoCount = (clone $poQuery)
                ->whereIn('status', [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED])
                ->count();

            $grnQuery = GoodsReceivedNote::query();
            $applyShopFilter($grnQuery, 'shop_id');
            $pendingGrnCount = (clone $grnQuery)
                ->where('billing_status', '!=', GoodsReceivedNote::BILLING_BILLED)
                ->count();

            $piQuery = PurchaseInvoice::query();
            $applyShopFilter($piQuery, 'shop_id');
            $unpaidInvoicesCount = (clone $piQuery)
                ->whereIn('payment_status', [PurchaseInvoice::STATUS_UNPAID, PurchaseInvoice::STATUS_PARTIALLY_PAID])
                ->count();
            $unpaidInvoicesAmount = (float) (clone $piQuery)
                ->whereIn('payment_status', [PurchaseInvoice::STATUS_UNPAID, PurchaseInvoice::STATUS_PARTIALLY_PAID])
                ->sum('grand_total');
        }

        // 3. Lean Recent Stock Activity (Strict limit 5)
        $recentActivity = collect();
        if ($canSeeStock) {
            $txnQuery = InventoryTransaction::query();
            $applyShopFilter($txnQuery, 'inventory_transactions.shop_id');
            $recentActivity = (clone $txnQuery)
                ->with(['inventoryItem:id,item_name,sku', 'creator:id,name'])
                ->latest('inventory_transactions.created_at')
                ->limit(5)
                ->get(['id', 'inventory_item_id', 'transaction_type', 'quantity', 'created_by', 'created_at']);
        }

        return view('dashboard.index', [
            'shops' => $shops,
            'currentShop' => $currentShop,
            'selectedShopId' => $selectedShopId,
            'canSeeSales' => $canSeeSales,
            'canSeePurchases' => $canSeePurchases,
            'canSeeStock' => $canSeeStock,
            'canSeeLube' => $canSeeLube,
            'salesTodayRevenue' => $salesTodayRevenue,
            'salesTodayCount' => $salesTodayCount,
            'salesTodayCash' => $salesTodayCash,
            'salesTodayDigital' => $salesTodayDigital,
            'salesTodayCredit' => $salesTodayCredit,
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
