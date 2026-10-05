<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Models\Finance\Expense;
use App\Services\Finance\JournalEntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $search = trim((string) $request->query('search'));
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Expense::with(['expenseAccount', 'paymentAccount', 'creator'])
            ->forShop($shopId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('voucher_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $totalAmount = (clone $query)->sum('amount');
        $expenses = $query->latest('date')->latest('id')->paginate(15)->withQueryString();

        return view('finance.expenses.index', compact('expenses', 'totalAmount', 'search', 'startDate', 'endDate'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id', $user->shop_id) : $user->shop_id;

        // Expense heads: head_id = 5 (Expenses)
        $expenseAccounts = Account::forShop($shopId)
            ->where('account_head_id', 5)
            ->orderBy('name')
            ->get();

        // Payment accounts: Cash & Bank accounts (head_id = 1)
        $paymentAccounts = Account::forShop($shopId)
            ->where('account_head_id', 1)
            ->whereIn('code', ['1001', '1002'])
            ->orderBy('name')
            ->get();

        if ($paymentAccounts->isEmpty()) {
            $paymentAccounts = Account::forShop($shopId)->where('account_head_id', 1)->get();
        }

        return view('finance.expenses.create', compact('expenseAccounts', 'paymentAccounts'));
    }

    public function store(Request $request, JournalEntryService $journalService): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'expense_account_id' => ['required', 'exists:accounts,id'],
            'payment_account_id' => ['required', 'exists:accounts,id'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'receipt_ref' => ['nullable', 'string', 'max:100'],
        ]);

        $expense = DB::transaction(function () use ($user, $shopId, $validated, $journalService) {
            $prefix = 'EXP-' . now()->format('Ym') . '-';
            $count = Expense::where('voucher_no', 'like', "{$prefix}%")->count() + 1;
            $voucherNo = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $expense = Expense::create([
                'shop_id' => $shopId,
                'expense_account_id' => $validated['expense_account_id'],
                'payment_account_id' => $validated['payment_account_id'],
                'voucher_no' => $voucherNo,
                'date' => $validated['date'],
                'amount' => $validated['amount'],
                'category' => $validated['category'] ?? null,
                'description' => $validated['description'],
                'receipt_ref' => $validated['receipt_ref'] ?? null,
                'created_by' => $user->id,
            ]);

            // Auto-post double-entry journal voucher
            $journalService->recordExpenseEntry($expense);

            return $expense;
        });

        return redirect()->route('finance.expenses.index')->with('success', "Expense voucher #{$expense->voucher_no} recorded and posted to ledger.");
    }
}
