<?php

namespace App\Models\Purchase;

use App\Models\Finance\Account;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceivedExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_received_note_id',
        'account_id',
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

    public function goodsReceivedNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
