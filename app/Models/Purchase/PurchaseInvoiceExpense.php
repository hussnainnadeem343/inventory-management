<?php

namespace App\Models\Purchase;

use App\Models\Finance\Account;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id',
        'account_id',
        'category',
        'expense_type',
        'comments',
        'quantity',
        'rate',
        'debit',
        'credit',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'rate' => 'decimal:2',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
