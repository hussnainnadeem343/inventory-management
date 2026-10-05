<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Models\Finance\AccountHead;
use App\Models\Finance\JournalEntryItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    public function profitLoss(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;

        $startDate = $request->query('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('end_date', now()->format('Y-m-d'));

        // Revenue (Head ID 4)
        $revenueAccounts = Account::forShop($shopId)
            ->where('account_head_id', 4)
            ->with(['journalItems' => function ($q) use ($startDate, $endDate) {
                $q->whereHas('journalEntry', fn ($jq) => $jq->whereBetween('entry_date', [$startDate, $endDate]));
            }])
            ->get()
            ->map(function ($acc) {
                $credit = $acc->journalItems->sum('credit');
                $debit = $acc->journalItems->sum('debit');
                $acc->period_total = max(0, $credit - $debit);
                return $acc;
            });

        $totalRevenue = $revenueAccounts->sum('period_total');

        // Expenses (Head ID 5)
        $expenseAccounts = Account::forShop($shopId)
            ->where('account_head_id', 5)
            ->with(['journalItems' => function ($q) use ($startDate, $endDate) {
                $q->whereHas('journalEntry', fn ($jq) => $jq->whereBetween('entry_date', [$startDate, $endDate]));
            }])
            ->get()
            ->map(function ($acc) {
                $debit = $acc->journalItems->sum('debit');
                $credit = $acc->journalItems->sum('credit');
                $acc->period_total = max(0, $debit - $credit);
                return $acc;
            });

        $cogsAccount = $expenseAccounts->firstWhere('code', '5001');
        $cogsTotal = $cogsAccount ? $cogsAccount->period_total : 0;
        $operatingExpenses = $expenseAccounts->where('code', '!=', '5001');
        $totalOperatingExpense = $operatingExpenses->sum('period_total');

        $grossProfit = $totalRevenue - $cogsTotal;
        $netProfit = $grossProfit - $totalOperatingExpense;

        return view('finance.reports.profit_loss', compact(
            'revenueAccounts',
            'totalRevenue',
            'cogsAccount',
            'cogsTotal',
            'operatingExpenses',
            'totalOperatingExpense',
            'grossProfit',
            'netProfit',
            'startDate',
            'endDate'
        ));
    }

    public function balanceSheet(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $asOfDate = $request->query('as_of_date', now()->format('Y-m-d'));

        // Assets (Head 1)
        $assetAccounts = Account::forShop($shopId)->where('account_head_id', 1)->active()->get();
        $totalAssets = $assetAccounts->sum('current_balance');

        // Liabilities (Head 2)
        $liabilityAccounts = Account::forShop($shopId)->where('account_head_id', 2)->active()->get();
        $totalLiabilities = $liabilityAccounts->sum('current_balance');

        // Equity (Head 3)
        $equityAccounts = Account::forShop($shopId)->where('account_head_id', 3)->active()->get();
        $totalEquity = $equityAccounts->sum('current_balance');

        return view('finance.reports.balance_sheet', compact(
            'assetAccounts',
            'totalAssets',
            'liabilityAccounts',
            'totalLiabilities',
            'equityAccounts',
            'totalEquity',
            'asOfDate'
        ));
    }

    public function trialBalance(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;

        $accounts = Account::with('head')->forShop($shopId)->active()->orderBy('code')->get();

        return view('finance.reports.trial_balance', compact('accounts'));
    }
}
