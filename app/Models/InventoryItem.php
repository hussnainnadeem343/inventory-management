<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use SoftDeletes;

    public const UNITS = ['PCS', 'BOX', 'KG', 'GRAM', 'LITER', 'ML', 'PACK', 'DOZEN'];

    protected $fillable = ['item_name', 'sku', 'brand_id', 'category_id', 'quantity', 'unit', 'purchase_price', 'selling_price', 'supplier', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'purchase_price' => 'decimal:2', 'selling_price' => 'decimal:2'];
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
}
