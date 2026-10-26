<?php

namespace App\Models\Lube;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LubeServicePackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id',
        'name',
        'vehicle_type',
        'package_price',
        'labor_amount',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'package_price' => 'decimal:2',
            'labor_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LubeServicePackageItem::class, 'package_id');
    }

    public function scopeForShop(Builder $query, ?int $shopId = null): Builder
    {
        $shopId = $shopId ?? auth()->user()?->shop_id;

        return $shopId ? $query->where('lube_service_packages.shop_id', $shopId) : $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
