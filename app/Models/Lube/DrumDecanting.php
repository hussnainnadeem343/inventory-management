<?php

namespace App\Models\Lube;

use App\Models\InventoryItem;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrumDecanting extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'drum_item_id',
        'loose_item_id',
        'drums_used',
        'liters_produced',
        'shrinkage_liters',
        'cost_per_liter',
        'selling_price_per_liter',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'drums_used' => 'decimal:2',
            'liters_produced' => 'decimal:2',
            'shrinkage_liters' => 'decimal:2',
            'cost_per_liter' => 'decimal:2',
            'selling_price_per_liter' => 'decimal:2',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function drumItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'drum_item_id');
    }

    public function looseItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'loose_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForShop(Builder $query, ?int $shopId = null): Builder
    {
        $shopId = $shopId ?? auth()->user()?->shop_id;

        return $shopId ? $query->where('drum_decantings.shop_id', $shopId) : $query;
    }
}
