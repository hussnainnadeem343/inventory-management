<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    public const TYPE_SALE = 'SALE';

    protected $fillable = ['inventory_item_id', 'transaction_type', 'quantity', 'created_by'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2'];
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
