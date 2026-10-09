<?php

namespace App\Models\Purchase;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id',
        'agent_name',
        'commission_type',
        'calculation_type',
        'deduction_type',
        'invoice_net_total',
        'rate',
        'amount',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'invoice_net_total' => 'decimal:2',
            'rate' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }
}
