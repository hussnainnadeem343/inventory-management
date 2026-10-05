<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Models\Finance\JournalEntry;
use App\Services\Finance\JournalEntryService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalEntryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $search = trim((string) $request->query('search'));
        $refType = $request->query('reference_type');

        $query = JournalEntry::with(['creator', 'items.account'])
            ->forShop($shopId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($refType) {
            $query->where('reference_type', $refType);
        }

        $entries = $query->latest('entry_date')->latest('id')->paginate(15)->withQueryString();

        return view('finance.journal.index', compact('entries', 'search', 'refType'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id', $user->shop_id) : $user->shop_id;

        $accounts = Account::forShop($shopId)->active()->orderBy('code')->get();

        return view('finance.journal.create', compact('accounts'));
    }

    public function store(Request $request, JournalEntryService $journalService): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string'],
            'items' => ['required', 'array', 'min:2'],
            'items.*.account_id' => ['required', 'exists:accounts,id'],
            'items.*.debit' => ['nullable', 'numeric', 'min:0'],
            'items.*.credit' => ['nullable', 'numeric', 'min:0'],
            'items.*.narration' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $entry = $journalService->recordEntry(
                $shopId,
                $validated['entry_date'],
                'manual',
                null,
                $validated['description'],
                $validated['items'],
                $user->id
            );
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('finance.journal.show', $entry)->with('success', "Journal voucher #{$entry->entry_number} recorded successfully.");
    }

    public function show(JournalEntry $journal): View
    {
        $journal->load(['items.account.head', 'creator', 'shop']);

        return view('finance.journal.show', compact('journal'));
    }
}
