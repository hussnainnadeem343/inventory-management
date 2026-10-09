<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'inventory_item_id',
        'batch_no',
        'purchase_price',
        'selling_price',
        'initial_quantity',
        'quantity',
        'expiry_date',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'initial_quantity' => 'decimal:2',
            'quantity' => 'decimal:2',
            'expiry_date' => 'date',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('quantity', '>', 0);
    }

    public function getBatchNumberAttribute(): ?string
    {
        return $this->batch_no;
    }

    public function scopeFifo(Builder $query): Builder
    {
        return $query->orderByRaw('CASE WHEN expiry_date IS NOT NULL THEN expiry_date ELSE created_at END ASC')
            ->orderBy('id', 'asc');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
