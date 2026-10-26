<?php

namespace App\Models\Lube;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PitTechnician extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id',
        'name',
        'phone',
        'bay_number',
        'commission_type',
        'commission_rate',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(LubeServiceRecord::class);
    }

    public function scopeForShop(Builder $query, ?int $shopId = null): Builder
    {
        $shopId = $shopId ?? auth()->user()?->shop_id;

        return $shopId ? $query->where('pit_technicians.shop_id', $shopId) : $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function calculateCommission(float $laborAmount): float
    {
        if ($this->commission_type === 'fixed') {
            return (float) $this->commission_rate;
        }

        if ($this->commission_type === 'percentage') {
            return round(($laborAmount * (float) $this->commission_rate) / 100, 2);
        }

        return 0.0;
    }
}
