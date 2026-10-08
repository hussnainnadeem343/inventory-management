<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Models\Finance\Payment;
use App\Models\Purchase\Supplier;
use App\Services\Finance\JournalEntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id') : $user->shop_id;
        $search = trim((string) $request->query('search'));
        $type = $request->query('type');

        $query = Payment::with(['supplier', 'paymentAccount', 'creator'])
            ->forShop($shopId);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('voucher_no', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        $totalAmount = (clone $query)->sum('amount');
        $payments = $query->latest('payment_date')->latest('id')->paginate(15)->withQueryString();

        return view('finance.payments.index', compact('payments', 'totalAmount', 'search', 'type'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->query('shop_id', $user->shop_id) : $user->shop_id;

        if ($shopId) {
            Account::ensureStandardAccountsExistForShop($shopId);
        }

        $suppliers = Supplier::forShop($shopId)->active()->orderBy('name')->get();
        $paymentAccounts = Account::forShop($shopId)
            ->where('account_head_id', 1)
            ->whereIn('code', ['1001', '1002'])
            ->orderBy('name')
            ->get();

        if ($paymentAccounts->isEmpty()) {
            $paymentAccounts = Account::forShop($shopId)->where('account_head_id', 1)->get();
        }

        if ($paymentAccounts->isEmpty()) {
            $paymentAccounts = Account::where('account_head_id', 1)->whereIn('code', ['1001', '1002'])->get();
        }

        return view('finance.payments.create', compact('suppliers', 'paymentAccounts'));
    }

    public function store(Request $request, JournalEntryService $journalService): RedirectResponse
    {
        $user = $request->user();
        $shopId = $user->isSuperAdmin() ? $request->input('shop_id', $user->shop_id) : $user->shop_id;

        $validated = $request->validate([
            'type' => ['required', 'in:supplier_payment,customer_receipt'],
            'supplier_id' => ['required_if:type,supplier_payment', 'nullable', 'exists:suppliers,id'],
            'payment_account_id' => ['required', 'exists:accounts,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:50'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment = DB::transaction(function () use ($user, $shopId, $validated, $journalService) {
            $prefix = ($validated['type'] === 'supplier_payment' ? 'PV-' : 'CR-') . now()->format('Ym') . '-';
            $count = Payment::where('voucher_no', 'like', "{$prefix}%")->count() + 1;
            $voucherNo = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $payment = Payment::create([
                'shop_id' => $shopId,
                'type' => $validated['type'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'payment_account_id' => $validated['payment_account_id'],
                'voucher_no' => $voucherNo,
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference_no' => $validated['reference_no'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            // Deduct from supplier payable balance
            if ($payment->type === Payment::TYPE_SUPPLIER_PAYMENT && $payment->supplier_id) {
                $supplier = Supplier::find($payment->supplier_id);
                if ($supplier) {
                    $supplier->decrement('current_balance', $payment->amount);
                }
                // Post double-entry journal voucher
                $journalService->recordSupplierPaymentEntry($payment);
            }

            return $payment;
        });

        return redirect()->route('finance.payments.index')->with('success', "Payment voucher #{$payment->voucher_no} recorded successfully.");
    }
}
