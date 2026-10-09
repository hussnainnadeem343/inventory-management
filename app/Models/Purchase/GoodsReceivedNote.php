<?php

namespace App\Models\Purchase;

use App\Models\Inventory\Warehouse;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceivedNote extends Model
{
    use HasFactory;

    public const STATUS_RECEIVED = 'received';
    public const STATUS_RETURNED = 'returned';

    public const BILLING_UNBILLED = 'unbilled';
    public const BILLING_PARTIAL = 'partially_billed';
    public const BILLING_BILLED = 'fully_billed';

    protected $fillable = [
        'shop_id',
        'purchase_order_id',
        'inward_gate_pass_id',
        'supplier_id',
        'grn_number',
        'manual_grn_number',
        'received_via',
        'warehouse_id',
        'received_date',
        'supplier_invoice_no',
        'total_amount',
        'landed_expenses_total',
        'status',
        'billing_status',
        'notes',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'total_amount' => 'decimal:2',
            'landed_expenses_total' => 'decimal:2',
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

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inwardGatePass(): BelongsTo
    {
        return $this->belongsTo(InwardGatePass::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceivedItem::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(GoodsReceivedExpense::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }
}
