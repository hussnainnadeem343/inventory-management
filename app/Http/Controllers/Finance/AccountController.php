<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Models\Finance\AccountHead;
use App\Models\Finance\JournalEntryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;

        $heads = AccountHead::with(['accounts' => function ($q) use ($shopId) {
            $q->forShop($shopId)->orderBy('code');
        }])->get();

        return view('finance.accounts.index', compact('heads', 'shopId'));
    }

    public function create(Request $request): View
    {
        $heads = AccountHead::all();

        return view('finance.accounts.create', compact('heads'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'account_head_id' => ['required', 'exists:account_heads,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'opening_balance' => ['nullable', 'numeric'],
        ]);

        $head = AccountHead::findOrFail($validated['account_head_id']);
        $openBal = (float) ($validated['opening_balance'] ?? 0);

        Account::create([
            'shop_id' => $shopId,
            'account_head_id' => $head->id,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'nature' => $head->nature,
            'opening_balance' => $openBal,
            'current_balance' => $openBal,
            'is_system' => false,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        return redirect()->route('finance.accounts.index')->with('success', 'Account created successfully.');
    }

    public function ledger(Request $request, Account $account): View
    {
        $startDate = $request->query('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('end_date', now()->format('Y-m-d'));

        $items = JournalEntryItem::with('journalEntry')
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('entry_date', [$startDate, $endDate]);
            })
            ->join('journal_entries', 'journal_entry_items.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->select('journal_entry_items.*')
            ->get();

        return view('finance.accounts.ledger', compact('account', 'items', 'startDate', 'endDate'));
    }
}
