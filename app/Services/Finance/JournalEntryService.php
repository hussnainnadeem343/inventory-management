<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Expense;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryItem;
use App\Models\Finance\Payment;
use App\Models\Purchase\GoodsReceivedNote;
use App\Models\Sale;
use Exception;
use Illuminate\Support\Facades\DB;

class JournalEntryService
{
    /**
     * Record a balanced double-entry journal entry and update account balances.
     *
     * @param int|null $shopId
     * @param string $entryDate
     * @param string $referenceType
     * @param int|null $referenceId
     * @param string $description
     * @param array $lines Array of ['account_id' => int, 'debit' => float, 'credit' => float, 'narration' => ?string]
     * @param int|null $userId
     * @return JournalEntry
     * @throws Exception
     */
    public function recordEntry(
        ?int $shopId,
        string $entryDate,
        string $referenceType,
        ?int $referenceId,
        string $description,
        array $lines,
        ?int $userId = null
    ): JournalEntry {
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        // Floating point delta verification (ensure debits == credits)
        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new Exception("Journal entry out of balance. Total Debit: {$totalDebit}, Total Credit: {$totalCredit}");
        }

        return DB::transaction(function () use ($shopId, $entryDate, $referenceType, $referenceId, $description, $lines, $totalDebit, $totalCredit, $userId) {
            $prefix = 'JV-' . now()->format('Ym') . '-';
            $count = JournalEntry::where('entry_number', 'like', "{$prefix}%")->count() + 1;
            $entryNo = $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $entry = JournalEntry::create([
                'shop_id' => $shopId,
                'entry_number' => $entryNo,
                'entry_date' => $entryDate,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'description' => $description,
                'created_by' => $userId ?? auth()->id(),
            ]);

            foreach ($lines as $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);
                $account = Account::lockForUpdate()->find($line['account_id']);

                if (!$account) {
                    continue;
                }

                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $account->id,
                    'debit' => $debit,
                    'credit' => $credit,
                    'narration' => $line['narration'] ?? null,
                ]);

                // Update account current balance based on account nature
                if ($account->nature === 'debit') {
                    $account->increment('current_balance', $debit - $credit);
                } else {
                    $account->increment('current_balance', $credit - $debit);
                }
            }

            return $entry;
        });
    }

    /**
     * Auto journal entry on POS / Invoice Sale:
     * Dr. Cash in Hand / AR
     * Cr. Sales Revenue
     */
    public function recordSaleEntry(Sale $sale): ?JournalEntry
    {
        $shopId = $sale->shop_id;
        $cashAcc = Account::where('shop_id', $shopId)->where('code', '1001')->first()
            ?? Account::whereNull('shop_id')->where('code', '1001')->first()
            ?? Account::where('code', '1001')->first();
        $salesAcc = Account::where('shop_id', $shopId)->where('code', '4001')->first()
            ?? Account::whereNull('shop_id')->where('code', '4001')->first()
            ?? Account::where('code', '4001')->first();

        if (!$cashAcc || !$salesAcc) {
            return null; // System accounts not configured yet
        }

        $amount = (float) $sale->total_amount;
        if ($amount <= 0) {
            return null;
        }

        return $this->recordEntry(
            $shopId,
            $sale->created_at->format('Y-m-d'),
            'sale',
            $sale->id,
            "Auto entry for Sale Invoice #{$sale->invoice_number}",
            [
                ['account_id' => $cashAcc->id, 'debit' => $amount, 'credit' => 0, 'narration' => 'Cash received from customer'],
                ['account_id' => $salesAcc->id, 'debit' => 0, 'credit' => $amount, 'narration' => 'Sales revenue recorded'],
            ],
            $sale->user_id
        );
    }

    /**
     * Auto journal entry on Goods Received (GRN):
     * Dr. Inventory Asset
     * Cr. Accounts Payable (Vendors)
     */
    public function recordPurchaseGrnEntry(GoodsReceivedNote $grn): ?JournalEntry
    {
        $shopId = $grn->shop_id;
        $invAcc = Account::where('shop_id', $shopId)->where('code', '1050')->first()
            ?? Account::whereNull('shop_id')->where('code', '1050')->first()
            ?? Account::where('code', '1050')->first();
        $apAcc = Account::where('shop_id', $shopId)->where('code', '2001')->first()
            ?? Account::whereNull('shop_id')->where('code', '2001')->first()
            ?? Account::where('code', '2001')->first();

        if (!$invAcc || !$apAcc) {
            return null;
        }

        $amount = (float) $grn->total_amount;
        if ($amount <= 0) {
            return null;
        }

        return $this->recordEntry(
            $shopId,
            $grn->received_date->format('Y-m-d'),
            'purchase_grn',
            $grn->id,
            "Inward stock via GRN #{$grn->grn_number} from {$grn->supplier->name}",
            [
                ['account_id' => $invAcc->id, 'debit' => $amount, 'credit' => 0, 'narration' => 'Inventory asset increased'],
                ['account_id' => $apAcc->id, 'debit' => 0, 'credit' => $amount, 'narration' => 'Accounts payable liability increased'],
            ],
            $grn->received_by
        );
    }

    /**
     * Auto journal entry on Daily Expense:
     * Dr. Specific Expense Account
     * Cr. Cash/Bank Payment Account
     */
    public function recordExpenseEntry(Expense $expense): ?JournalEntry
    {
        return $this->recordEntry(
            $expense->shop_id,
            $expense->date->format('Y-m-d'),
            'expense',
            $expense->id,
            "Expense Voucher #{$expense->voucher_no}: {$expense->description}",
            [
                ['account_id' => $expense->expense_account_id, 'debit' => $expense->amount, 'credit' => 0, 'narration' => $expense->category ?? 'Expense recorded'],
                ['account_id' => $expense->payment_account_id, 'debit' => 0, 'credit' => $expense->amount, 'narration' => 'Paid via ' . $expense->paymentAccount->name],
            ],
            $expense->created_by
        );
    }

    /**
     * Auto journal entry on Supplier Payment:
     * Dr. Accounts Payable (2001)
     * Cr. Cash/Bank Account
     */
    public function recordSupplierPaymentEntry(Payment $payment): ?JournalEntry
    {
        $shopId = $payment->shop_id;
        $apAcc = Account::where('shop_id', $shopId)->where('code', '2001')->first()
            ?? Account::whereNull('shop_id')->where('code', '2001')->first()
            ?? Account::where('code', '2001')->first();

        if (!$apAcc) {
            return null;
        }

        return $this->recordEntry(
            $payment->shop_id,
            $payment->payment_date->format('Y-m-d'),
            'payment',
            $payment->id,
            "Payment Voucher #{$payment->voucher_no} to {$payment->supplier?->name}",
            [
                ['account_id' => $apAcc->id, 'debit' => $payment->amount, 'credit' => 0, 'narration' => 'Supplier liability settled'],
                ['account_id' => $payment->payment_account_id, 'debit' => 0, 'credit' => $payment->amount, 'narration' => 'Paid from ' . $payment->paymentAccount->name],
            ],
            $payment->created_by
        );
    }

    /**
     * Auto journal entry on Purchase Invoice (Vendor Bill):
     * Dr. Inventory Asset / Purchases
     * Dr. Landed Expenses (Freight, etc.)
     * Cr. Accounts Payable (Vendors)
     */
    public function recordPurchaseInvoiceEntry(\App\Models\Purchase\PurchaseInvoice $invoice): ?JournalEntry
    {
        $shopId = $invoice->shop_id;
        $invAcc = Account::where('shop_id', $shopId)->where('code', '1050')->first()
            ?? Account::whereNull('shop_id')->where('code', '1050')->first()
            ?? Account::where('code', '1050')->first();
        $apAcc = Account::where('shop_id', $shopId)->where('code', '2001')->first()
            ?? Account::whereNull('shop_id')->where('code', '2001')->first()
            ?? Account::where('code', '2001')->first();

        if (!$invAcc || !$apAcc) {
            return null;
        }

        $lines = [];
        $productTotal = (float) $invoice->subtotal - (float) $invoice->discount_amount + (float) $invoice->tax_amount;
        if ($productTotal > 0) {
            $lines[] = [
                'account_id' => $invAcc->id,
                'debit' => $productTotal,
                'credit' => 0,
                'narration' => 'Purchased goods inward valuation',
            ];
        }

        // Add additional expenses (freight, offloading)
        foreach ($invoice->expenses as $exp) {
            $expAccId = $exp->account_id ?? $invAcc->id;
            $expDebit = (float) $exp->debit > 0 ? (float) $exp->debit : (float) $exp->rate * (float) $exp->quantity;
            if ($expDebit > 0) {
                $lines[] = [
                    'account_id' => $expAccId,
                    'debit' => $expDebit,
                    'credit' => 0,
                    'narration' => ($exp->expense_type ?? 'Expense') . ': ' . ($exp->comments ?? ''),
                ];
            }
        }

        $totalPayable = (float) $invoice->grand_total;
        if ($totalPayable > 0) {
            $lines[] = [
                'account_id' => $apAcc->id,
                'debit' => 0,
                'credit' => $totalPayable,
                'narration' => "Accounts payable to {$invoice->supplier->name} for Bill #{$invoice->invoice_number}",
            ];
        }

        if (empty($lines)) {
            return null;
        }

        try {
            return $this->recordEntry(
                $shopId,
                $invoice->invoice_date->format('Y-m-d'),
                'purchase_invoice',
                $invoice->id,
                "Purchase Bill #{$invoice->invoice_number} from {$invoice->supplier->name}",
                $lines,
                $invoice->created_by
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Auto journal entry on Purchase Return (Debit Note):
     * Dr. Accounts Payable (2001) - Reducing vendor liability
     * Cr. Inventory Asset (1050) - Reducing inventory valuation
     */
    public function recordPurchaseReturnEntry(\App\Models\Purchase\PurchaseReturn $return): ?JournalEntry
    {
        $shopId = $return->shop_id;
        $invAcc = Account::where('shop_id', $shopId)->where('code', '1050')->first()
            ?? Account::whereNull('shop_id')->where('code', '1050')->first()
            ?? Account::where('code', '1050')->first();
        $apAcc = Account::where('shop_id', $shopId)->where('code', '2001')->first()
            ?? Account::whereNull('shop_id')->where('code', '2001')->first()
            ?? Account::where('code', '2001')->first();

        if (!$invAcc || !$apAcc) {
            return null;
        }

        $amount = (float) $return->total_amount;
        if ($amount <= 0) {
            return null;
        }

        try {
            return $this->recordEntry(
                $shopId,
                $return->return_date->format('Y-m-d'),
                'purchase_return',
                $return->id,
                "Debit Note #{$return->return_number} to {$return->supplier->name}: {$return->reason}",
                [
                    ['account_id' => $apAcc->id, 'debit' => $amount, 'credit' => 0, 'narration' => 'Vendor debt reduced via Debit Note'],
                    ['account_id' => $invAcc->id, 'debit' => 0, 'credit' => $amount, 'narration' => 'Inventory asset reduced for returned goods'],
                ],
                $return->created_by
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
