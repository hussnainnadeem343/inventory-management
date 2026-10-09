<?php

namespace App\Models\Purchase;

use App\Models\Finance\JournalEntry;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_CREDIT = 'credit';
    public const TYPE_CASH = 'cash';

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'shop_id',
        'supplier_id',
        'goods_received_note_id',
        'purchase_order_id',
        'invoice_number',
        'manual_invoice_number',
        'invoice_date',
        'due_date',
        'invoice_type',
        'currency',
        'station',
        'supplier_invoice_no',
        'supplier_invoice_date',
        'payment_terms',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'inventory_expenses_total',
        'other_expenses_total',
        'commission_total',
        'grand_total',
        'paid_amount',
        'payment_status',
        'terms',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'supplier_invoice_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'inventory_expenses_total' => 'decimal:2',
            'other_expenses_total' => 'decimal:2',
            'commission_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function scopeForShop(Builder $query, ?int $shopId = null): Builder
    {
        $shopId = $shopId ?? auth()->user()?->shop_id;

        return $shopId ? $query->where('shop_id', $shopId) : $query;
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->grand_total;
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function goodsReceivedNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceExpense::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceCommission::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function getRemainingDueAttribute(): float
    {
        return max(0, (float) $this->grand_total - (float) $this->paid_amount);
    }
}
