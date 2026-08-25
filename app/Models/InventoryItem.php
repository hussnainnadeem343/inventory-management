<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use SoftDeletes;

    public const UNITS = ['PCS', 'BOX', 'KG', 'GRAM', 'LITER', 'ML', 'PACK', 'DOZEN'];

    protected $fillable = ['item_name', 'sku', 'brand_id', 'category_id', 'quantity', 'pack_size', 'unit', 'purchase_price', 'selling_price', 'supplier', 'status', 'created_by'];

    protected $appends = ['remaining_quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'sold_quantity' => 'decimal:2', 'pack_size' => 'decimal:3', 'purchase_price' => 'decimal:2', 'selling_price' => 'decimal:2'];
    }

    public function getPackLabelAttribute(): ?string
    {
        if ($this->pack_size === null && blank($this->unit)) {
            return null;
        }

        $size = $this->pack_size === null ? '' : rtrim(rtrim(number_format((float) $this->pack_size, 3, '.', ''), '0'), '.');

        return $size.$this->unit;
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, (float) $this->quantity - (float) $this->sold_quantity);
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn (Builder $q, string $search) => $q->where(fn (Builder $q) => $q->where('item_name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
            ->when($filters['brand_id'] ?? null, fn (Builder $q, $brandId) => $q->where('brand_id', $brandId))
            ->when($filters['category_id'] ?? null, fn (Builder $q, $categoryId) => $q->where('category_id', $categoryId))
            ->when($filters['date'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', $date));
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
}
