<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'business_type',
        'phone',
        'address',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(\App\Models\Lube\Vehicle::class);
    }

    public function pitTechnicians(): HasMany
    {
        return $this->hasMany(\App\Models\Lube\PitTechnician::class);
    }

    public function lubeServiceRecords(): HasMany
    {
        return $this->hasMany(\App\Models\Lube\LubeServiceRecord::class);
    }

    public function lubeServicePackages(): HasMany
    {
        return $this->hasMany(\App\Models\Lube\LubeServicePackage::class);
    }

    public function drumDecantings(): HasMany
    {
        return $this->hasMany(\App\Models\Lube\DrumDecanting::class);
    }

    public function laborServices(): HasMany
    {
        return $this->hasMany(\App\Models\Lube\LubeLaborService::class);
    }

    public function isLube(): bool
    {
        return str_contains(strtolower($this->business_type ?? ''), 'lube');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
