<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use SoftDeletes;

    public const UNITS = ['PCS', 'BOX', 'KG', 'GRAM', 'LITER', 'ML', 'PACK', 'DOZEN'];

    protected $fillable = [
        'shop_id',
        'item_name',
        'sku',
        'brand_id',
        'category_id',
        'expiry_date',
        'initial_quantity',
        'quantity',
        'sold_quantity',
        'alert_quantity',
        'pack_size',
        'liters',
        'unit',
        'product_type',
        'is_loose',
        'purchase_price',
        'selling_price',
        'supplier',
        'status',
        'created_by',
    ];

    protected $appends = [
        'remaining_quantity',
        'total_stock',
        'is_low_stock',
        'expiry_status',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'initial_quantity' => 'decimal:2',
            'quantity' => 'decimal:2',
            'sold_quantity' => 'decimal:2',
            'alert_quantity' => 'decimal:2',
            'pack_size' => 'decimal:3',
            'liters' => 'decimal:2',
            'is_loose' => 'boolean',
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
        ];
    }

    public function getPackLabelAttribute(): ?string
    {
        if ($this->pack_size === null && blank($this->unit)) {
            return null;
        }

        $size = $this->pack_size === null ? '' : rtrim(rtrim(number_format((float) $this->pack_size, 3, '.', ''), '0'), '.');

        return $size.$this->unit;
    }

    public function getLitersAttribute($value): ?float
    {
        if ($value !== null && $value !== '') {
            return (float) $value;
        }
        return $this->pack_size ? (float) $this->pack_size : null;
    }

    public function getTotalStockAttribute(): float
    {
        return (float) $this->quantity;
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, (float) $this->quantity);
    }

    public function getIsLowStockAttribute(): bool
    {
        $threshold = (float) ($this->alert_quantity ?? 5);

        return (float) $this->quantity > 0 && (float) $this->quantity <= $threshold;
    }

    public function getExpiryStatusAttribute(): string
    {
        if (! $this->expiry_date) {
            return 'none';
        }

        if ($this->expiry_date->isPast()) {
            return 'expired';
        }

        if ($this->expiry_date->diffInDays(now()) <= 30) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    public function isLoose(): bool
    {
        return (bool) $this->is_loose || $this->product_type === 'loose';
    }

    public function isDrum(): bool
    {
        return $this->product_type === 'drum' || str_contains(strtoupper($this->unit ?? ''), 'DRUM');
    }

    public function scopeLoose(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('is_loose', true)->orWhere('product_type', 'loose');
        });
    }

    public function scopeDrum(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('product_type', 'drum')
              ->orWhere('unit', 'DRUM')
              ->orWhere('item_name', 'like', '%drum%');
        });
    }

    public function scopeStandard(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where(function ($lq) {
                $lq->where('is_loose', false)->orWhereNull('is_loose');
            })->where(function ($sq) {
                $sq->whereNull('product_type')
                   ->orWhere('product_type', '!=', 'loose');
            });
        });
    }

    public function scopeForShop(Builder $query, ?int $shopId = null): Builder
    {
        $shopId = $shopId ?? auth()->user()?->shop_id;

        return $shopId ? $query->where('shop_id', $shopId) : $query;
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn (Builder $q, string $search) => $q->where(fn (Builder $q) => $q->where('item_name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
            ->when($filters['brand_id'] ?? null, fn (Builder $q, $brandId) => $q->where('brand_id', $brandId))
            ->when($filters['category_id'] ?? null, fn (Builder $q, $categoryId) => $q->where('category_id', $categoryId))
            ->when($filters['date'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', $date));
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }
}
