<?php

namespace App\Models\Lube;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id',
        'plate_number',
        'vehicle_type',
        'make',
        'model',
        'year',
        'customer_name',
        'customer_phone',
        'notes',
        'last_odometer',
        'last_service_date',
    ];

    protected function casts(): array
    {
        return [
            'last_odometer' => 'integer',
            'last_service_date' => 'date',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(LubeServiceRecord::class)->orderByDesc('id');
    }

    public function latestService(): HasOne
    {
        return $this->hasOne(LubeServiceRecord::class)->latestOfMany();
    }

    public function scopeForShop(Builder $query, ?int $shopId = null): Builder
    {
        $shopId = $shopId ?? auth()->user()?->shop_id;

        return $shopId ? $query->where('vehicles.shop_id', $shopId) : $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('plate_number', 'like', "%{$term}%")
              ->orWhere('customer_phone', 'like', "%{$term}%")
              ->orWhere('customer_name', 'like', "%{$term}%")
              ->orWhere('make', 'like', "%{$term}%")
              ->orWhere('model', 'like', "%{$term}%");
        });
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->plate_number} - {$this->make} {$this->model}";
    }
}
