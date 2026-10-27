<?php

namespace App\Http\Controllers;

use App\Exports\CashRegisterReportExport;
use App\Exports\ExpenseReportExport;
use App\Exports\LubeServicesReportExport;
use App\Exports\ProductExpiryReportExport;
use App\Exports\ProductSalesReportExport;
use App\Exports\SalesInvoicesReportExport;
use App\Exports\SalesReportExport;
use App\Exports\StockMovementReportExport;
use App\Models\CashRegisterClosing;
use App\Models\Finance\Account;
use App\Models\Finance\Expense;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Sale;
use App\Models\Shop;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    /**
     * Report 1: Sales Invoices Report (Standalone Screen).
     */
    public function sales(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasPermission('reports.sales'), 403, 'Unauthorized. You do not have permission to view sales reports.');

        $dateRange = $this->reportService->resolveDateRange($request, defaultPreset: 'today');
        $scope = $this->reportService->resolveShopScope($user, $request);

        $startDate = $dateRange['startDate'];
        $endDate = $dateRange['endDate'];
        $preset = $dateRange['preset'];

        $targetShopIds = $scope['targetShopIds'];
        $selectedShopId = $scope['selectedShopId'];
        $shops = $scope['shops'];
        $isConsolidated = $scope['isConsolidated'];
        $search = trim((string) $request->query('search'));

        $invoicesQuery = $this->reportService->getSalesInvoicesQuery($startDate, $endDate, $targetShopIds, $search);
        $totalInvoicesCount = (clone $invoicesQuery)->count();
        $invoices = (clone $invoicesQuery)->limit(10)->get();

        $financialSummary = $this->reportService->getFinancialSummary($startDate, $endDate, $targetShopIds);

        $branchComparison = collect();
        if ($user->isBusinessOwner() && $isConsolidated) {
            $branchComparison = $this->reportService->getBranchComparison($startDate, $endDate, $targetShopIds);
        }

        return view('reports.sales', [
            'invoices' => $invoices,
            'totalInvoicesCount' => $totalInvoicesCount,
            'startDate' => $dateRange['formattedStart'],
            'endDate' => $dateRange['formattedEnd'],
            'preset' => $preset,
            'shopId' => $selectedShopId,
            'shops' => $shops,
            'isConsolidated' => $isConsolidated,
            'search' => $search,
            'financialSummary' => $financialSummary,
            'branchComparison' => $branchComparison,
            // Backward-compatible variable aliases
            'totalUnitsSold' => $financialSummary['totalUnitsSold'],
            'grossRevenue' => $financialSummary['grossSubtotal'],
            'itemDiscounts' => $financialSummary['itemsDiscount'],
            'billDiscounts' => $financialSummary['billDiscount'],
            'totalDiscounts' => $financialSummary['totalDiscounts'],
            'totalRefunds' => $financialSummary['totalRefunds'],
            'netRevenue' => $financialSummary['netSales'],
            'cogs' => $financialSummary['cogs'],
            'grossProfit' => $financialSummary['grossProfit'],
            'marginPercent' => $financialSummary['marginPercent'],
        ]);
    }

    /**
     * Download Sales Invoices Report as an Excel spreadsheet (.xlsx).
     */
    public function exportSales(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermission('reports.sales'), 403, 'Unauthorized.');

        $dateRange = $this->reportService->resolveDateRange($request, defaultPreset: 'today');
        $scope = $this->reportService->resolveShopScope($user, $request);

        $startDate = $dateRange['startDate'];
        $endDate = $dateRange['endDate'];
        $targetShopIds = $scope['targetShopIds'];
        $search = trim((string) $request->query('search'));

        $invoices = $this->reportService->getSalesInvoicesQuery($startDate, $endDate, $targetShopIds, $search)->get();
        $filename = 'Sales-Financial-Report-' . $dateRange['formattedStart'] . '-to-' . $dateRange['formattedEnd'] . '.xlsx';

        return Excel::download(new SalesInvoicesReportExport($invoices), $filename);
    }

    /**
     * Report 2: Product Sales & Profit Margin Report (Standalone Screen).
     */
    public function productSales(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasPermission('reports.sales'), 403, 'Unauthorized.');

        $dateRange = $this->reportService->resolveDateRange($request, defaultPreset: 'this_month');
        $scope = $this->reportService->resolveShopScope($user, $request);

        $startDate = $dateRange['startDate'];
        $endDate = $dateRange['endDate'];
        $preset = $dateRange['preset'];
        $targetShopIds = $scope['targetShopIds'];
        $selectedShopId = $scope['selectedShopId'];
        $shops = $scope['shops'];
        $isConsolidated = $scope['isConsolidated'];

        $allProducts = $this->reportService->getProductBreakdown($startDate, $endDate, $targetShopIds, perPage: null);
        $totalProductsCount = $allProducts->count();
        $productBreakdown = $allProducts->take(10);
        $financialSummary = $this->reportService->getFinancialSummary($startDate, $endDate, $targetShopIds);

        return view('reports.product-sales', [
            'productBreakdown' => $productBreakdown,
            'totalProductsCount' => $totalProductsCount,
            'financialSummary' => $financialSummary,
            'startDate' => $dateRange['formattedStart'],
            'endDate' => $dateRange['formattedEnd'],
            'preset' => $preset,
            'shopId' => $selectedShopId,
            'shops' => $shops,
            'isConsolidated' => $isConsolidated,
        ]);
    }

    /**
     * Download Product Sales & Margin Report as an Excel spreadsheet (.xlsx).
     */
    public function exportProductSales(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermission('reports.sales'), 403, 'Unauthorized.');

        $dateRange = $this->reportService->resolveDateRange($request, defaultPreset: 'this_month');
        $scope = $this->reportService->resolveShopScope($user, $request);

        $startDate = $dateRange['startDate'];
        $endDate = $dateRange['endDate'];
        $targetShopIds = $scope['targetShopIds'];

        $products = $this->reportService->getProductBreakdown($startDate, $endDate, $targetShopIds, perPage: null);
        $filename = 'Product-Sales-Margin-Report-' . $dateRange['formattedStart'] . '-to-' . $dateRange['formattedEnd'] . '.xlsx';

        return Excel::download(new ProductSalesReportExport($products), $filename);
    }

    /**
     * Report 3: Daily Cash Register & Payments Report (Standalone Screen).
     */
    public function cashRegister(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasPermission('reports.sales'), 403, 'Unauthorized.');

        $dateRange = $this->reportService->resolveDateRange($request, defaultPreset: 'this_month');
        $scope = $this->reportService->resolveShopScope($user, $request);

        $startDate = $dateRange['startDate'];
        $endDate = $dateRange['endDate'];
        $preset = $dateRange['preset'];
        $targetShopIds = $scope['targetShopIds'];
        $selectedShopId = $scope['selectedShopId'];
        $shops = $scope['shops'];
        $isConsolidated = $scope['isConsolidated'];

        $allRegisterRows = $this->reportService->getCashRegisterDaily($startDate, $endDate, $targetShopIds);
        $totalRecordsCount = $allRegisterRows->count();
        $registerRows = $allRegisterRows->take(10);
        $financialSummary = $this->reportService->getFinancialSummary($startDate, $endDate, $targetShopIds);

        $closings = CashRegisterClosing::with('user')
            ->whereIn('shop_id', $targetShopIds)
            ->whereBetween('closing_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->keyBy(fn ($c) => ($c->shop_id . '_' . $c->closing_date->format('Y-m-d')));

        return view('reports.cash-register', [
            'registerRows' => $registerRows,
            'totalRecordsCount' => $totalRecordsCount,
            'financialSummary' => $financialSummary,
            'closings' => $closings,
            'startDate' => $dateRange['formattedStart'],
            'endDate' => $dateRange['formattedEnd'],
            'preset' => $preset,
            'shopId' => $selectedShopId,
            'shops' => $shops,
            'isConsolidated' => $isConsolidated,
        ]);
    }

    /**
     * Download Daily Cash Register Report as an Excel spreadsheet (.xlsx).
     */
    public function exportCashRegister(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermission('reports.sales'), 403, 'Unauthorized.');

        $dateRange = $this->reportService->resolveDateRange($request, defaultPreset: 'this_month');
        $scope = $this->reportService->resolveShopScope($user, $request);

        $startDate = $dateRange['startDate'];
        $endDate = $dateRange['endDate'];
        $targetShopIds = $scope['targetShopIds'];

        $records = $this->reportService->getCashRegisterDaily($startDate, $endDate, $targetShopIds);
        $filename = 'Daily-Cash-Register-Report-' . $dateRange['formattedStart'] . '-to-' . $dateRange['formattedEnd'] . '.xlsx';

        return Excel::download(new CashRegisterReportExport($records), $filename);
    }

    /**
     * Ajax API: Fetch live cash register summary & existing closing info for a specific date & store.
     */
    public function cashRegisterSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermission('reports.sales'), 403, 'Unauthorized.');

        $dateStr = $request->query('date', now()->format('Y-m-d'));
        $closingDate = Carbon::parse($dateStr)->format('Y-m-d');

        $shopId = (int) $request->query('shop_id', $user->shop_id);
        if ($user->isShopAdmin() && $user->shop_id !== $shopId) {
            $shopId = (int) $user->shop_id;
        }

        $shop = Shop::find($shopId) ?? $user->shop;
        if (! $shop) {
            return response()->json(['error' => 'Store not found'], 404);
        }

        $start = Carbon::parse($closingDate)->startOfDay();
        $end = Carbon::parse($closingDate)->endOfDay();

        $salesData = DB::table('sales')
            ->where('shop_id', $shop->id)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('deleted_at')
            ->selectRaw('
                COUNT(id) as total_invoices,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) = "cash" THEN total_amount ELSE 0 END), 0) as cash_sales,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) = "card" THEN total_amount ELSE 0 END), 0) as card_total,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) = "bank" THEN total_amount ELSE 0 END), 0) as bank_total,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) = "credit" THEN total_amount ELSE 0 END), 0) as credit_total,
                COALESCE(SUM(total_amount), 0) as total_net_sales
            ')
            ->first();

        $cashExpenses = (float) DB::table('expenses')
            ->where('shop_id', $shop->id)
            ->whereDate('date', $closingDate)
            ->sum('amount');

        $existingClosing = CashRegisterClosing::with('user')
            ->where('shop_id', $shop->id)
            ->where('closing_date', $closingDate)
            ->first();

        $openingFloat = $existingClosing ? (float) $existingClosing->opening_float : 0.0;
        $cashSales = (float) ($salesData->cash_sales ?? 0);
        $expectedCash = round($openingFloat + $cashSales - $cashExpenses, 2);

        return response()->json([
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
            'shop_code' => $shop->code,
            'closing_date' => $closingDate,
            'formatted_date' => Carbon::parse($closingDate)->format('d M Y'),
            'total_invoices' => (int) ($salesData->total_invoices ?? 0),
            'cash_sales' => $cashSales,
            'card_total' => (float) ($salesData->card_total ?? 0),
            'bank_total' => (float) ($salesData->bank_total ?? 0),
            'credit_total' => (float) ($salesData->credit_total ?? 0),
            'total_net_sales' => (float) ($salesData->total_net_sales ?? 0),
            'cash_expenses' => $cashExpenses,
            'opening_float' => $openingFloat,
            'expected_cash' => $expectedCash,
            'actual_cash' => $existingClosing ? (float) $existingClosing->actual_cash : null,
            'difference' => $existingClosing ? (float) $existingClosing->difference : null,
            'is_closed' => (bool) $existingClosing,
            'closed_by' => $existingClosing?->user?->name,
            'closed_at' => $existingClosing?->closed_at?->format('d M Y h:i A'),
            'notes' => $existingClosing?->notes,
        ]);
    }

    /**
     * Submit and record physical Cash Register closing reconciliation.
     */
    public function closeCashRegister(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermission('reports.sales'), 403, 'Unauthorized.');

        $validated = $request->validate([
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'closing_date' => ['required', 'date'],
            'opening_float' => ['nullable', 'numeric', 'min:0'],
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $shopId = (int) $validated['shop_id'];
        if ($user->isShopAdmin() && $user->shop_id !== $shopId) {
            $shopId = (int) $user->shop_id;
        }

        $closingDate = Carbon::parse($validated['closing_date'])->format('Y-m-d');
        $start = Carbon::parse($closingDate)->startOfDay();
        $end = Carbon::parse($closingDate)->endOfDay();

        $salesData = DB::table('sales')
            ->where('shop_id', $shopId)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('deleted_at')
            ->selectRaw('
                COUNT(id) as total_invoices,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) = "cash" THEN total_amount ELSE 0 END), 0) as cash_sales,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) = "card" THEN total_amount ELSE 0 END), 0) as card_total,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) = "bank" THEN total_amount ELSE 0 END), 0) as bank_total,
                COALESCE(SUM(CASE WHEN LOWER(payment_method) = "credit" THEN total_amount ELSE 0 END), 0) as credit_total,
                COALESCE(SUM(total_amount), 0) as total_net_sales
            ')
            ->first();

        $cashExpenses = (float) DB::table('expenses')
            ->where('shop_id', $shopId)
            ->whereDate('date', $closingDate)
            ->sum('amount');

        $openingFloat = (float) ($validated['opening_float'] ?? 0);
        $cashSales = (float) ($salesData->cash_sales ?? 0);
        $expectedCash = round($openingFloat + $cashSales - $cashExpenses, 2);
        $actualCash = (float) $validated['actual_cash'];
        $difference = round($actualCash - $expectedCash, 2);

        $closing = CashRegisterClosing::updateOrCreate(
            [
                'shop_id' => $shopId,
                'closing_date' => $closingDate,
            ],
            [
                'user_id' => $user->id,
                'closed_at' => now(),
                'opening_float' => $openingFloat,
                'cash_sales' => $cashSales,
                'cash_expenses' => $cashExpenses,
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'difference' => $difference,
                'card_total' => (float) ($salesData->card_total ?? 0),
                'bank_total' => (float) ($salesData->bank_total ?? 0),
                'credit_total' => (float) ($salesData->credit_total ?? 0),
                'total_net_sales' => (float) ($salesData->total_net_sales ?? 0),
                'total_invoices' => (int) ($salesData->total_invoices ?? 0),
                'status' => 'closed',
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return redirect()->back()
            ->with('status', "Cash drawer for " . Carbon::parse($closingDate)->format('d M Y') . " has been successfully closed and reconciled.")
            ->with('just_closed_id', $closing->id);
    }

    /**
     * Printable Cash Drawer Closing Slip / Handover Receipt.
     */
    public function cashClosingSlip(CashRegisterClosing $closing): View
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('reports.sales'), 403, 'Unauthorized.');

        if ($user->isShopAdmin() && $user->shop_id !== $closing->shop_id) {
            abort(403, 'Unauthorized to view another store\'s cash closing slip.');
        }

        $closing->load(['shop', 'user']);

        return view('reports.cash-closing-slip', [
            'closing' => $closing,
        ]);
    }

    /**
     * Report 4: Stock Movement Audit Report (Standalone Screen).
     */
    public function stockMovement(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasPermission('reports.stock_movement'), 403, 'Unauthorized.');

        $dateRange = $this->reportService->resolveDateRange($request, defaultPreset: 'this_month');
        $scope = $this->reportService->resolveShopScope($user, $request);

        $startDate = $dateRange['startDate'];
        $endDate = $dateRange['endDate'];
        $preset = $dateRange['preset'];
        $targetShopIds = $scope['targetShopIds'];
        $selectedShopId = $scope['selectedShopId'];
        $shops = $scope['shops'];
        $type = $request->query('type');
        $search = trim((string) $request->query('search'));

        $query = $this->reportService->getStockMovementQuery($startDate, $endDate, $targetShopIds, $type, $search);
        $totalLogsCount = (clone $query)->count();

        $summary = (clone $query)
            ->select('transaction_type')
            ->selectRaw('COUNT(id) as count, SUM(quantity) as total_qty')
            ->groupBy('transaction_type')
            ->pluck('total_qty', 'transaction_type')
            ->toArray();

        $transactions = (clone $query)->limit(10)->get();

        return view('reports.stock-movement', [
            'startDate' => $dateRange['formattedStart'],
            'endDate' => $dateRange['formattedEnd'],
            'preset' => $preset,
            'shopId' => $selectedShopId,
            'shops' => $shops,
            'type' => $type,
            'search' => $search,
            'summary' => $summary,
            'transactions' => $transactions,
            'totalLogsCount' => $totalLogsCount,
        ]);
    }

    /**
     * Download Stock Movement Audit Report as an Excel spreadsheet (.xlsx).
     */
    public function exportStockMovement(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermission('reports.stock_movement'), 403, 'Unauthorized.');

        $dateRange = $this->reportService->resolveDateRange($request, defaultPreset: 'this_month');
        $scope = $this->reportService->resolveShopScope($user, $request);

        $startDate = $dateRange['startDate'];
        $endDate = $dateRange['endDate'];
        $targetShopIds = $scope['targetShopIds'];
        $type = $request->query('type');
        $search = trim((string) $request->query('search'));

        $transactions = $this->reportService->getStockMovementQuery($startDate, $endDate, $targetShopIds, $type, $search)->get();
        $filename = 'Stock-Movement-Audit-Report-' . $dateRange['formattedStart'] . '-to-' . $dateRange['formattedEnd'] . '.xlsx';

        return Excel::download(new StockMovementReportExport($transactions), $filename);
    }

    /**
     * Report 5: Product Expiry Alert Report (Standalone Screen).
     */
    public function expiry(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasPermission('reports.expiry'), 403, 'Unauthorized.');

        $scope = $this->reportService->resolveShopScope($user, $request);
        $targetShopIds = $scope['targetShopIds'];
        $selectedShopId = $scope['selectedShopId'];
        $shops = $scope['shops'];

        $range = $request->query('range', '30'); // 15, 30, 60, or expired
        $search = trim((string) $request->query('search'));

        $query = $this->reportService->getExpiryQuery($targetShopIds, $range, $search);
        $totalItemsCount = (clone $query)->count();
        $items = (clone $query)->limit(10)->get();

        return view('reports.expiry', [
            'items' => $items,
            'totalItemsCount' => $totalItemsCount,
            'range' => $range,
            'shopId' => $selectedShopId,
            'shops' => $shops,
            'search' => $search,
        ]);
    }

    /**
     * Download Product Expiry Report as an Excel spreadsheet (.xlsx).
     */
    public function exportExpiry(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermission('reports.expiry'), 403, 'Unauthorized.');

        $scope = $this->reportService->resolveShopScope($user, $request);
        $targetShopIds = $scope['targetShopIds'];
        $range = $request->query('range', '30');
        $search = trim((string) $request->query('search'));

        $items = $this->reportService->getExpiryQuery($targetShopIds, $range, $search)->get();
        $filename = 'Product-Expiry-Report-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new ProductExpiryReportExport($items), $filename);
    }

    /**
     * Report 6: Auto Lube Station Pit-Stop Services Report (Standalone Screen).
     */
    public function lubeServices(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasPermission('reports.sales'), 403, 'Unauthorized.');

        $dateRange = $this->reportService->resolveDateRange($request, defaultPreset: 'this_month');
        $scope = $this->reportService->resolveShopScope($user, $request);

        $startDate = $dateRange['startDate'];
        $endDate = $dateRange['endDate'];
        $preset = $dateRange['preset'];
        $targetShopIds = $scope['targetShopIds'];
        $selectedShopId = $scope['selectedShopId'];
        $shops = $scope['shops'];
        $search = trim((string) $request->query('search'));

        $query = $this->reportService->getLubeServicesQuery($startDate, $endDate, $targetShopIds, $search);
        $totalRecordsCount = $query ? (clone $query)->count() : 0;
        $records = $query ? (clone $query)->limit(10)->get() : collect();

        return view('reports.lube-services', [
            'records' => $records,
            'totalRecordsCount' => $totalRecordsCount,
            'startDate' => $dateRange['formattedStart'],
            'endDate' => $dateRange['formattedEnd'],
            'preset' => $preset,
            'shopId' => $selectedShopId,
            'shops' => $shops,
            'search' => $search,
        ]);
    }

    /**
     * Download Auto Lube Services Report as an Excel spreadsheet (.xlsx).
     */
    public function exportLubeServices(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user->hasPermission('reports.sales'), 403, 'Unauthorized.');

        $dateRange = $this->reportService->resolveDateRange($request, defaultPreset: 'this_month');
        $scope = $this->reportService->resolveShopScope($user, $request);

        $startDate = $dateRange['startDate'];
        $endDate = $dateRange['endDate'];
        $targetShopIds = $scope['targetShopIds'];
        $search = trim((string) $request->query('search'));

        $query = $this->reportService->getLubeServicesQuery($startDate, $endDate, $targetShopIds, $search);
        $records = $query ? $query->get() : collect();

        $filename = 'Auto-Lube-Services-Report-' . $dateRange['formattedStart'] . '-to-' . $dateRange['formattedEnd'] . '.xlsx';

        return Excel::download(new LubeServicesReportExport($records), $filename);
    }

    /**
     * Report 7: Daily Expense Report & Sheet (Standalone Screen).
     */
    public function expenses(Request $request): View
    {
        $user = $request->user();
        abort_unless(
            $user->isSuperAdmin() || $user->isBusinessOwner() || $user->isShopAdmin() || $user->hasPermission('reports.sales') || $user->hasPermission('accounts.view'),
            403,
            'Unauthorized.'
        );

        $dateRange = $this->reportService->resolveDateRange($request, defaultPreset: 'this_month');
        $scope = $this->reportService->resolveShopScope($user, $request);

        $startDate = $dateRange['startDate'];
        $endDate = $dateRange['endDate'];
        $preset = $dateRange['preset'];
        $targetShopIds = $scope['targetShopIds'];
        $selectedShopId = $scope['selectedShopId'];
        $shops = $scope['shops'];

        $category = $request->query('category');
        $accountId = $request->filled('expense_account_id') ? (int) $request->query('expense_account_id') : null;
        $search = trim((string) $request->query('search'));

        $query = $this->reportService->getExpenseQuery($startDate, $endDate, $targetShopIds, $category, $accountId, $search);
        $totalRecordsCount = (clone $query)->count();
        $expenses = (clone $query)->limit(10)->get();

        $summary = $this->reportService->getExpenseSummary($startDate, $endDate, $targetShopIds);

        $categories = Expense::whereIn('shop_id', $targetShopIds)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        $expenseAccounts = Account::whereIn('shop_id', $targetShopIds)
            ->where('account_head_id', 5)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('reports.expenses', [
            'expenses' => $expenses,
            'totalRecordsCount' => $totalRecordsCount,
            'summary' => $summary,
            'startDate' => $dateRange['formattedStart'],
            'endDate' => $dateRange['formattedEnd'],
            'preset' => $preset,
            'shopId' => $selectedShopId,
            'shops' => $shops,
            'category' => $category,
            'selectedAccountId' => $accountId,
            'categories' => $categories,
            'expenseAccounts' => $expenseAccounts,
            'search' => $search,
        ]);
    }

    /**
     * Download Expense Report & Sheet as an Excel spreadsheet (.xlsx).
     */
    public function exportExpenses(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless(
            $user->isSuperAdmin() || $user->isBusinessOwner() || $user->isShopAdmin() || $user->hasPermission('reports.sales') || $user->hasPermission('accounts.view'),
            403,
            'Unauthorized.'
        );

        $dateRange = $this->reportService->resolveDateRange($request, defaultPreset: 'this_month');
        $scope = $this->reportService->resolveShopScope($user, $request);

        $startDate = $dateRange['startDate'];
        $endDate = $dateRange['endDate'];
        $targetShopIds = $scope['targetShopIds'];

        $category = $request->query('category');
        $accountId = $request->filled('expense_account_id') ? (int) $request->query('expense_account_id') : null;
        $search = trim((string) $request->query('search'));

        $records = $this->reportService->getExpenseQuery($startDate, $endDate, $targetShopIds, $category, $accountId, $search)->get();

        $filename = 'Daily-Expense-Sheet-' . $dateRange['formattedStart'] . '-to-' . $dateRange['formattedEnd'] . '.xlsx';

        return Excel::download(new ExpenseReportExport($records), $filename);
    }
}
