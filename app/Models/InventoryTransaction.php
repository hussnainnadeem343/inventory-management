<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    public const TYPE_STOCK_IN = 'STOCK_IN';

    public const TYPE_SALE = 'SALE';

    public const TYPE_CUSTOMER_RETURN = 'CUSTOMER_RETURN';

    public const TYPE_EXCHANGE_IN = 'EXCHANGE_IN';

    public const TYPE_EXCHANGE_OUT = 'EXCHANGE_OUT';

    public const TYPE_DAMAGE_LOSS = 'DAMAGE_LOSS';

    protected $fillable = [
        'shop_id',
        'inventory_item_id',
        'transaction_type',
        'quantity',
        'balance_before',
        'balance_after',
        'unit_cost',
        'unit_sale_price',
        'expiry_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'unit_sale_price' => 'decimal:2',
            'expiry_date' => 'date',
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

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
