<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegisterClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'user_id',
        'closing_date',
        'closed_at',
        'opening_float',
        'cash_sales',
        'cash_expenses',
        'expected_cash',
        'actual_cash',
        'difference',
        'card_total',
        'bank_total',
        'credit_total',
        'total_net_sales',
        'total_invoices',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'closing_date' => 'date',
            'closed_at' => 'datetime',
            'opening_float' => 'decimal:2',
            'cash_sales' => 'decimal:2',
            'cash_expenses' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'difference' => 'decimal:2',
            'card_total' => 'decimal:2',
            'bank_total' => 'decimal:2',
            'credit_total' => 'decimal:2',
            'total_net_sales' => 'decimal:2',
            'total_invoices' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isBalanced(): bool
    {
        return abs((float) $this->difference) < 0.01;
    }

    public function isShortage(): bool
    {
        return (float) $this->difference < -0.01;
    }

    public function isExcess(): bool
    {
        return (float) $this->difference > 0.01;
    }

    public function varianceLabel(): string
    {
        if ($this->isBalanced()) {
            return 'Balanced (Exact)';
        }

        if ($this->isShortage()) {
            return 'Shortage (-Rs. ' . number_format(abs((float) $this->difference), 2) . ')';
        }

        return 'Excess (+Rs. ' . number_format((float) $this->difference, 2) . ')';
    }

    public function varianceBadgeClass(): string
    {
        if ($this->isBalanced()) {
            return 'text-bg-success';
        }

        if ($this->isShortage()) {
            return 'text-bg-danger';
        }

        return 'text-bg-info text-white';
    }
}
