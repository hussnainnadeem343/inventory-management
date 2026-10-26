<?php

namespace App\Models\Lube;

use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LubeServiceRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id',
        'sale_id',
        'vehicle_id',
        'pit_technician_id',
        'current_odometer',
        'interval_km',
        'next_due_odometer',
        'next_due_date',
        'oil_grade',
        'inspection_checklist',
        'labor_amount',
        'technician_commission',
        'commission_status',
        'commission_paid_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'current_odometer' => 'integer',
            'interval_km' => 'integer',
            'next_due_odometer' => 'integer',
            'next_due_date' => 'date',
            'inspection_checklist' => 'array',
            'labor_amount' => 'decimal:2',
            'technician_commission' => 'decimal:2',
            'commission_paid_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(PitTechnician::class, 'pit_technician_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForShop(Builder $query, ?int $shopId = null): Builder
    {
        $shopId = $shopId ?? auth()->user()?->shop_id;

        return $shopId ? $query->where('lube_service_records.shop_id', $shopId) : $query;
    }
}
