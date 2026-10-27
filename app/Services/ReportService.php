<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportService
{
    /**
     * Resolve start and end dates from preset or custom query inputs.
     */
    public function resolveDateRange(Request $request, string $defaultPreset = 'today'): array
    {
        $preset = $request->query('preset');
        $customStart = $request->query('start_date');
        $customEnd = $request->query('end_date');

        // If explicit start/end dates are provided without a preset, treat as custom
        if (! $preset && ($customStart || $customEnd)) {
            $preset = 'custom';
        } elseif (! $preset) {
            $preset = $defaultPreset;
        }

        switch ($preset) {
            case 'today':
                $start = now()->startOfDay();
                $end = now()->endOfDay();
                break;
            case 'yesterday':
                $start = now()->subDay()->startOfDay();
                $end = now()->subDay()->endOfDay();
                break;
            case 'this_week':
                $start = now()->startOfWeek();
                $end = now()->endOfDay();
                break;
            case 'this_month':
                $start = now()->startOfMonth();
                $end = now()->endOfDay();
                break;
            case 'last_month':
                $start = now()->subMonth()->startOfMonth();
                $end = now()->subMonth()->endOfMonth();
                break;
            case 'this_year':
                $start = now()->startOfYear();
                $end = now()->endOfDay();
                break;
            case 'custom':
            default:
                $preset = 'custom';
                $start = $customStart ? Carbon::parse($customStart)->startOfDay() : now()->startOfMonth();
                $end = $customEnd ? Carbon::parse($customEnd)->endOfDay() : now()->endOfDay();
                break;
        }

        return [
            'startDate' => $start,
            'endDate' => $end,
            'preset' => $preset,
            'formattedStart' => $start->format('Y-m-d'),
            'formattedEnd' => $end->format('Y-m-d'),
        ];
    }

    /**
     * Resolve shop scoping and permission boundaries.
     */
    public function resolveShopScope(User $user, Request $request): array
    {
        $selectedShopId = null;
        $targetShopIds = [];
        $shops = collect();
        $isConsolidated = false;

        if ($user->isSuperAdmin()) {
            $shops = Shop::orderBy('name')->get();
            $requested = $request->query('shop_id');
            if ($requested === 'all' || empty($requested)) {
                $selectedShopId = null;
                $targetShopIds = $shops->pluck('id')->toArray();
                $isConsolidated = true;
            } else {
                $selectedShopId = (int) $requested;
                $targetShopIds = [$selectedShopId];
                $isConsolidated = false;
            }
        } elseif ($user->isBusinessOwner()) {
            $business = $user->shop ? $user->shop->getRootBusiness() : null;
            $branchIds = $business ? $business->getAllBranchIds() : [$user->shop_id];
            $shops = Shop::whereIn('id', $branchIds)->orderBy('name')->get();

            $requested = $request->query('shop_id');
            if ($requested === 'all' || empty($requested)) {
                $selectedShopId = null;
                $targetShopIds = $branchIds;
                $isConsolidated = true;
            } elseif (in_array((int) $requested, $branchIds, true)) {
                $selectedShopId = (int) $requested;
                $targetShopIds = [$selectedShopId];
                $isConsolidated = false;
            } else {
                $selectedShopId = null;
                $targetShopIds = $branchIds;
                $isConsolidated = true;
            }
        } else {
            // Shop Admin / Store staff strictly locked to their own shop
            $selectedShopId = $user->shop_id;
            $targetShopIds = [$user->shop_id];
            $isConsolidated = false;
        }

        return [
            'selectedShopId' => $selectedShopId,
            'targetShopIds' => array_filter($targetShopIds),
            'shops' => $shops,
            'isConsolidated' => $isConsolidated,
        ];
    }

    /**
     * Compute comprehensive financial and cash register summary.
     */
    public function getFinancialSummary(Carbon $startDate, Carbon $endDate, array $shopIds): array
    {
        if (empty($shopIds)) {
            return $this->emptyFinancialSummary();
        }

        // 1. Core Sales Invoices Aggregates
        $salesAgg = DB::table('sales')
            ->whereIn('shop_id', $shopIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->selectRaw('
                COUNT(id) as total_invoices,
                COALESCE(SUM(total_items), 0) as total_items_count,
                COALESCE(SUM(subtotal), 0) as gross_subtotal,
                COALESCE(SUM(items_discount_total), 0) as items_discount,
                COALESCE(SUM(discount), 0) as bill_discount,
                COALESCE(SUM(total_amount), 0) as net_sales,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) = "cash" THEN total_amount ELSE 0 END), 0) as cash_total,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) = "card" THEN total_amount ELSE 0 END), 0) as card_total,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) = "bank" THEN total_amount ELSE 0 END), 0) as bank_total,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) = "credit" THEN total_amount ELSE 0 END), 0) as credit_total
            ')
            ->first();

        $totalInvoices = (int) ($salesAgg->total_invoices ?? 0);
        $grossSubtotal = (float) ($salesAgg->gross_subtotal ?? 0);
        $itemsDiscount = (float) ($salesAgg->items_discount ?? 0);
        $billDiscount = (float) ($salesAgg->bill_discount ?? 0);
        $totalDiscounts = $itemsDiscount + $billDiscount;
        $netSales = (float) ($salesAgg->net_sales ?? 0);

        $cashTotal = (float) ($salesAgg->cash_total ?? 0);
        $cardTotal = (float) ($salesAgg->card_total ?? 0);
        $bankTotal = (float) ($salesAgg->bank_total ?? 0);
        $creditTotal = (float) ($salesAgg->credit_total ?? 0);

        // 2. Cost of Goods Sold (COGS) & Units Sold from sale_items
        $cogsAgg = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereIn('sales.shop_id', $shopIds)
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->whereNull('sales.deleted_at')
            ->selectRaw('
                COALESCE(SUM(sale_items.quantity), 0) as total_units_sold,
                COALESCE(SUM(sale_items.quantity * sale_items.unit_cost), 0) as total_cogs
            ')
            ->first();

        $totalUnitsSold = (float) ($cogsAgg->total_units_sold ?? 0);
        $cogs = (float) ($cogsAgg->total_cogs ?? 0);

        // 3. Customer Returns & Refunds from inventory_transactions
        $returnsAgg = DB::table('inventory_transactions')
            ->whereIn('shop_id', $shopIds)
            ->where('transaction_type', InventoryTransaction::TYPE_CUSTOMER_RETURN)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                COALESCE(SUM(quantity), 0) as total_units_returned,
                COALESCE(SUM(quantity * unit_sale_price), 0) as total_refunds
            ')
            ->first();

        $unitsReturned = (float) ($returnsAgg->total_units_returned ?? 0);
        $totalRefunds = (float) ($returnsAgg->total_refunds ?? 0);

        // Gross Profit = Net Sales Revenue - COGS
        $grossProfit = $netSales - $cogs;
        $marginPercent = $netSales > 0 ? ($grossProfit / $netSales) * 100 : 0;
        $averageOrderValue = $totalInvoices > 0 ? $netSales / $totalInvoices : 0;

        // 4. Auto Lube Station Breakdown (if applicable)
        $lubeServicesCount = 0;
        $lubeLaborTotal = 0.0;
        $lubeCommissionsTotal = 0.0;

        if (Schema::hasTable('lube_service_records')) {
            $lubeAgg = DB::table('lube_service_records')
                ->whereIn('shop_id', $shopIds)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNull('deleted_at')
                ->selectRaw('
                    COUNT(id) as count,
                    COALESCE(SUM(labor_amount), 0) as total_labor,
                    COALESCE(SUM(technician_commission), 0) as total_commissions
                ')
                ->first();

            $lubeServicesCount = (int) ($lubeAgg->count ?? 0);
            $lubeLaborTotal = (float) ($lubeAgg->total_labor ?? 0);
            $lubeCommissionsTotal = (float) ($lubeAgg->total_commissions ?? 0);
        }

        $partsRevenue = max(0, $netSales - $lubeLaborTotal);

        return [
            'totalInvoices' => $totalInvoices,
            'totalUnitsSold' => $totalUnitsSold,
            'grossSubtotal' => $grossSubtotal,
            'itemsDiscount' => $itemsDiscount,
            'billDiscount' => $billDiscount,
            'totalDiscounts' => $totalDiscounts,
            'totalRefunds' => $totalRefunds,
            'unitsReturned' => $unitsReturned,
            'netSales' => $netSales,
            'cogs' => $cogs,
            'grossProfit' => $grossProfit,
            'marginPercent' => round($marginPercent, 1),
            'averageOrderValue' => round($averageOrderValue, 2),
            // Payment method breakdown
            'cashTotal' => $cashTotal,
            'cardTotal' => $cardTotal,
            'bankTotal' => $bankTotal,
            'creditTotal' => $creditTotal,
            'digitalTotal' => $cardTotal + $bankTotal,
            // Auto lube service separation
            'lubeServicesCount' => $lubeServicesCount,
            'lubeLaborTotal' => $lubeLaborTotal,
            'partsRevenue' => $partsRevenue,
            'lubeCommissionsTotal' => $lubeCommissionsTotal,
        ];
    }

    /**
     * Compute side-by-side branch comparison performance for multi-branch chains.
     */
    public function getBranchComparison(Carbon $startDate, Carbon $endDate, array $branchIds): Collection
    {
        if (empty($branchIds)) {
            return collect();
        }

        $branches = Shop::whereIn('id', $branchIds)->get()->keyBy('id');

        $rows = DB::table('sales')
            ->whereIn('shop_id', $branchIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->select('shop_id')
            ->selectRaw('
                COUNT(id) as total_invoices,
                COALESCE(SUM(subtotal), 0) as gross_subtotal,
                COALESCE(SUM(items_discount_total + discount), 0) as total_discount,
                COALESCE(SUM(total_amount), 0) as net_sales,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) = "cash" THEN total_amount ELSE 0 END), 0) as cash_amount,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) IN ("card", "bank") THEN total_amount ELSE 0 END), 0) as digital_amount,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) = "credit" THEN total_amount ELSE 0 END), 0) as credit_amount
            ')
            ->groupBy('shop_id')
            ->get();

        $cogsRows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereIn('sales.shop_id', $branchIds)
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->whereNull('sales.deleted_at')
            ->select('sales.shop_id')
            ->selectRaw('COALESCE(SUM(sale_items.quantity * sale_items.unit_cost), 0) as total_cogs')
            ->groupBy('sales.shop_id')
            ->pluck('total_cogs', 'shop_id')
            ->toArray();

        $totalChainRevenue = (float) $rows->sum('net_sales');

        return $rows->map(function ($row) use ($branches, $cogsRows, $totalChainRevenue) {
            $branch = $branches->get($row->shop_id);
            $net = (float) $row->net_sales;
            $cogs = (float) ($cogsRows[$row->shop_id] ?? 0);
            $profit = $net - $cogs;
            $margin = $net > 0 ? ($profit / $net) * 100 : 0;
            $share = $totalChainRevenue > 0 ? ($net / $totalChainRevenue) * 100 : 0;

            return (object) [
                'shop_id' => $row->shop_id,
                'name' => $branch->name ?? 'Branch #' . $row->shop_id,
                'code' => $branch->code ?? 'N/A',
                'is_hq' => $branch ? $branch->isBusiness() : false,
                'total_invoices' => (int) $row->total_invoices,
                'cash_amount' => (float) $row->cash_amount,
                'digital_amount' => (float) $row->digital_amount,
                'credit_amount' => (float) $row->credit_amount,
                'net_sales' => $net,
                'cogs' => $cogs,
                'gross_profit' => $profit,
                'margin_percent' => round($margin, 1),
                'share_percent' => round($share, 1),
            ];
        })->sortByDesc('net_sales')->values();
    }

    /**
     * Compute itemized product sales and margin breakdown.
     */
    public function getProductBreakdown(Carbon $startDate, Carbon $endDate, array $shopIds, ?int $perPage = 15): LengthAwarePaginator|Collection
    {
        if (empty($shopIds)) {
            return $perPage ? new LengthAwarePaginator([], 0, $perPage) : collect();
        }

        $query = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('inventory_items', 'inventory_items.id', '=', 'sale_items.inventory_item_id')
            ->leftJoin('brands', 'brands.id', '=', 'inventory_items.brand_id')
            ->leftJoin('categories', 'categories.id', '=', 'inventory_items.category_id')
            ->whereIn('sales.shop_id', $shopIds)
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->whereNull('sales.deleted_at')
            ->select(
                'inventory_items.id as product_id',
                'inventory_items.item_name',
                'inventory_items.sku',
                'inventory_items.quantity as stock_remaining',
                'brands.name as brand_name',
                'categories.name as category_name'
            )
            ->selectRaw('
                COALESCE(SUM(sale_items.quantity), 0) as units_sold,
                COALESCE(SUM(sale_items.quantity * sale_items.unit_price), 0) as gross_revenue,
                COALESCE(SUM(sale_items.discount), 0) as discount_given,
                COALESCE(SUM(sale_items.line_total), 0) as net_revenue,
                COALESCE(SUM(sale_items.quantity * sale_items.unit_cost), 0) as cogs
            ')
            ->groupBy(
                'inventory_items.id',
                'inventory_items.item_name',
                'inventory_items.sku',
                'inventory_items.quantity',
                'brands.name',
                'categories.name'
            )
            ->orderByDesc('net_revenue');

        if ($perPage) {
            $paginator = $query->paginate($perPage)->withQueryString();
            $paginator->getCollection()->transform(function ($item) {
                $net = (float) $item->net_revenue;
                $cost = (float) $item->cogs;
                $item->gross_profit = $net - $cost;
                $item->margin_percent = $net > 0 ? round(($item->gross_profit / $net) * 100, 1) : 0;
                return $item;
            });
            return $paginator;
        }

        return $query->get()->map(function ($item) {
            $net = (float) $item->net_revenue;
            $cost = (float) $item->cogs;
            $item->gross_profit = $net - $cost;
            $item->margin_percent = $net > 0 ? round(($item->gross_profit / $net) * 100, 1) : 0;
            return $item;
        });
    }

    /**
     * Query sales invoices for the dedicated Sales Invoices Report.
     */
    public function getSalesInvoicesQuery(Carbon $startDate, Carbon $endDate, array $shopIds, ?string $search = null)
    {
        $query = Sale::with(['shop:id,name,code', 'creator:id,name'])
            ->whereIn('shop_id', $shopIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Compute daily cash drawer & payment register report.
     */
    public function getCashRegisterDaily(Carbon $startDate, Carbon $endDate, array $shopIds): Collection
    {
        if (empty($shopIds)) {
            return collect();
        }

        return DB::table('sales')
            ->join('shops', 'shops.id', '=', 'sales.shop_id')
            ->whereIn('sales.shop_id', $shopIds)
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->whereNull('sales.deleted_at')
            ->select(
                DB::raw('date(sales.created_at) as sale_date'),
                'sales.shop_id',
                'shops.name as shop_name',
                'shops.code as shop_code',
                DB::raw('COUNT(sales.id) as total_invoices'),
                DB::raw('COALESCE(SUM(CASE WHEN LOWER(sales.payment_method) = "cash" THEN sales.total_amount ELSE 0 END), 0) as cash_total'),
                DB::raw('COALESCE(SUM(CASE WHEN LOWER(sales.payment_method) = "card" THEN sales.total_amount ELSE 0 END), 0) as card_total'),
                DB::raw('COALESCE(SUM(CASE WHEN LOWER(sales.payment_method) = "bank" THEN sales.total_amount ELSE 0 END), 0) as bank_total'),
                DB::raw('COALESCE(SUM(CASE WHEN LOWER(sales.payment_method) = "credit" THEN sales.total_amount ELSE 0 END), 0) as credit_total'),
                DB::raw('COALESCE(SUM(sales.total_amount), 0) as net_sales')
            )
            ->groupBy(DB::raw('date(sales.created_at)'), 'sales.shop_id', 'shops.name', 'shops.code')
            ->orderByDesc('sale_date')
            ->get();
    }

    /**
     * Query inventory transactions for the Stock Movement Audit Report.
     */
    public function getStockMovementQuery(Carbon $startDate, Carbon $endDate, array $shopIds, ?string $type = null, ?string $search = null)
    {
        $query = InventoryTransaction::with(['inventoryItem:id,item_name,sku,shop_id', 'shop:id,name,code', 'creator:id,name'])
            ->whereIn('shop_id', $shopIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest('created_at');

        if ($type && $type !== 'all') {
            $query->where('transaction_type', $type);
        }

        if ($search) {
            $query->whereHas('inventoryItem', function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Query inventory items for Expiry Alerts Report.
     */
    public function getExpiryQuery(array $shopIds, string $range = '30', ?string $search = null)
    {
        $query = \App\Models\InventoryItem::with(['brand:id,name', 'category:id,name', 'shop:id,name,code'])
            ->whereIn('shop_id', $shopIds)
            ->whereNotNull('expiry_date')
            ->where('quantity', '>', 0);

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

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('expiry_date', 'asc');
    }

    /**
     * Query pit-stop lube service records.
     */
    public function getLubeServicesQuery(Carbon $startDate, Carbon $endDate, array $shopIds, ?string $search = null)
    {
        if (!Schema::hasTable('lube_service_records')) {
            return null;
        }

        $query = DB::table('lube_service_records')
            ->join('shops', 'shops.id', '=', 'lube_service_records.shop_id')
            ->join('vehicles', 'vehicles.id', '=', 'lube_service_records.vehicle_id')
            ->leftJoin('pit_technicians', 'pit_technicians.id', '=', 'lube_service_records.pit_technician_id')
            ->leftJoin('sales', 'sales.id', '=', 'lube_service_records.sale_id')
            ->whereIn('lube_service_records.shop_id', $shopIds)
            ->whereBetween('lube_service_records.created_at', [$startDate, $endDate])
            ->whereNull('lube_service_records.deleted_at')
            ->select(
                'lube_service_records.id',
                'lube_service_records.created_at',
                'shops.name as shop_name',
                'shops.code as shop_code',
                'vehicles.plate_number',
                'vehicles.make',
                'vehicles.model',
                'vehicles.customer_name',
                'vehicles.customer_phone',
                'lube_service_records.current_odometer',
                'lube_service_records.oil_grade',
                'lube_service_records.labor_amount',
                'lube_service_records.technician_commission',
                'lube_service_records.commission_status',
                'pit_technicians.name as technician_name',
                'sales.invoice_no',
                'sales.total_amount as invoice_total',
                'sales.payment_method'
            )
            ->orderByDesc('lube_service_records.created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('vehicles.plate_number', 'like', "%{$search}%")
                    ->orWhere('vehicles.customer_name', 'like', "%{$search}%")
                    ->orWhere('sales.invoice_no', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Query expenses for the Expense Report & Sheet.
     */
    public function getExpenseQuery(Carbon $startDate, Carbon $endDate, array $shopIds, ?string $category = null, ?int $accountId = null, ?string $search = null)
    {
        $query = \App\Models\Finance\Expense::with(['shop:id,name,code', 'expenseAccount:id,name,code', 'paymentAccount:id,name,code', 'creator:id,name'])
            ->whereIn('shop_id', $shopIds)
            ->whereDate('date', '>=', $startDate->format('Y-m-d'))
            ->whereDate('date', '<=', $endDate->format('Y-m-d'))
            ->latest('date')
            ->latest('id');

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if ($accountId) {
            $query->where('expense_account_id', $accountId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('voucher_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('receipt_ref', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Compute aggregated statistics for the Expense Report.
     */
    public function getExpenseSummary(Carbon $startDate, Carbon $endDate, array $shopIds): array
    {
        if (empty($shopIds)) {
            return [
                'totalAmount' => 0.0,
                'totalCount' => 0,
                'cashPaid' => 0.0,
                'bankPaid' => 0.0,
                'topCategory' => 'N/A',
                'topCategoryAmount' => 0.0,
            ];
        }

        $base = DB::table('expenses')
            ->join('accounts', 'accounts.id', '=', 'expenses.payment_account_id')
            ->whereIn('expenses.shop_id', $shopIds)
            ->whereDate('expenses.date', '>=', $startDate->format('Y-m-d'))
            ->whereDate('expenses.date', '<=', $endDate->format('Y-m-d'));

        $totals = (clone $base)
            ->selectRaw('
                COUNT(expenses.id) as total_count,
                COALESCE(SUM(expenses.amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN accounts.code = "1001" OR LOWER(accounts.name) LIKE "%cash%" THEN expenses.amount ELSE 0 END), 0) as cash_paid,
                COALESCE(SUM(CASE WHEN accounts.code != "1001" AND LOWER(accounts.name) NOT LIKE "%cash%" THEN expenses.amount ELSE 0 END), 0) as bank_paid
            ')
            ->first();

        $topCat = DB::table('expenses')
            ->whereIn('shop_id', $shopIds)
            ->whereDate('date', '>=', $startDate->format('Y-m-d'))
            ->whereDate('date', '<=', $endDate->format('Y-m-d'))
            ->select('category', DB::raw('SUM(amount) as cat_total'))
            ->groupBy('category')
            ->orderByDesc('cat_total')
            ->first();

        return [
            'totalAmount' => (float) ($totals->total_amount ?? 0),
            'totalCount' => (int) ($totals->total_count ?? 0),
            'cashPaid' => (float) ($totals->cash_paid ?? 0),
            'bankPaid' => (float) ($totals->bank_paid ?? 0),
            'topCategory' => $topCat ? ($topCat->category ?: 'General') : 'None',
            'topCategoryAmount' => (float) ($topCat->cat_total ?? 0),
        ];
    }

    /**
     * Default empty financial summary data structure.
     */
    protected function emptyFinancialSummary(): array
    {
        return [
            'totalInvoices' => 0,
            'totalUnitsSold' => 0.0,
            'grossSubtotal' => 0.0,
            'itemsDiscount' => 0.0,
            'billDiscount' => 0.0,
            'totalDiscounts' => 0.0,
            'totalRefunds' => 0.0,
            'unitsReturned' => 0.0,
            'netSales' => 0.0,
            'cogs' => 0.0,
            'grossProfit' => 0.0,
            'marginPercent' => 0.0,
            'averageOrderValue' => 0.0,
            'cashTotal' => 0.0,
            'cardTotal' => 0.0,
            'bankTotal' => 0.0,
            'creditTotal' => 0.0,
            'digitalTotal' => 0.0,
            'lubeServicesCount' => 0,
            'lubeLaborTotal' => 0.0,
            'partsRevenue' => 0.0,
            'lubeCommissionsTotal' => 0.0,
        ];
    }
}
