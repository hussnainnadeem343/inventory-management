<?php

namespace App\Models\Purchase;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InwardGatePassItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inward_gate_pass_id',
        'inventory_item_id',
        'packages_count',
        'declared_quantity',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'packages_count' => 'decimal:2',
            'declared_quantity' => 'decimal:2',
        ];
    }

    public function gatePass(): BelongsTo
    {
        return $this->belongsTo(InwardGatePass::class, 'inward_gate_pass_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
