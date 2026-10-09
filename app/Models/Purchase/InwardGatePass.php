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

class InwardGatePass extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending_inspection';
    public const STATUS_COMPLETED = 'grn_completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'shop_id',
        'supplier_id',
        'purchase_order_id',
        'igp_number',
        'igp_date',
        'gate_entry_time',
        'received_via',
        'vehicle_number',
        'driver_name',
        'driver_phone',
        'bilty_number',
        'challan_number',
        'status',
        'remarks',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'igp_date' => 'date',
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

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InwardGatePassItem::class);
    }

    public function goodsReceivedNotes(): HasMany
    {
        return $this->hasMany(GoodsReceivedNote::class);
    }
}
