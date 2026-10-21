<?php

namespace App\Models\Purchase;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'shop_id',
        'supplier_id',
        'purchase_requisition_id',
        'po_number',
        'order_date',
        'expected_delivery_date',
        'payment_terms',
        'payment_mode',
        'payment_term_custom',
        'delivery_station',
        'status',
        'approved_by',
        'approved_at',
        'subtotal',
        'discount_amount',
        'tax_percent',
        'tax_amount',
        'grand_total',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'approved_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function scopeForShop(Builder $query, ?int $shopId = null): Builder
    {
        $shopId = $shopId ?? auth()->user()?->shop_id;

        return $shopId ? $query->where('shop_id', $shopId) : $query;
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function inwardGatePasses(): HasMany
    {
        return $this->hasMany(InwardGatePass::class);
    }

    public function goodsReceivedNotes(): HasMany
    {
        return $this->hasMany(GoodsReceivedNote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_PARTIALLY_RECEIVED,
            self::STATUS_RECEIVED,
        ], true);
    }

    public function canBeEditedOrDeleted(): bool
    {
        if ($this->relationLoaded('inwardGatePasses') && $this->relationLoaded('goodsReceivedNotes') && $this->relationLoaded('invoices')) {
            return $this->inwardGatePasses->isEmpty() && $this->goodsReceivedNotes->isEmpty() && $this->invoices->isEmpty();
        }

        return !$this->inwardGatePasses()->exists() && !$this->goodsReceivedNotes()->exists() && !$this->invoices()->exists();
    }
}
