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
        'parent_id',
        'name',
        'code',
        'business_type',
        'phone',
        'address',
        'max_branches',
        'max_users',
        'status',
    ];

    protected $casts = [
        'max_branches' => 'integer',
        'max_users' => 'integer',
    ];

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Shop::class, 'parent_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Shop::class, 'parent_id');
    }

    public function isBusiness(): bool
    {
        return is_null($this->parent_id);
    }

    public function isBranch(): bool
    {
        return !is_null($this->parent_id);
    }

    public function getRootBusiness(): Shop
    {
        return $this->parent_id && $this->parent ? $this->parent : $this;
    }

    public function getAllBranchIds(): array
    {
        $root = $this->getRootBusiness();
        return array_values(array_unique(array_merge([$root->id], $root->branches()->pluck('id')->all())));
    }

    public function totalBranchesCount(): int
    {
        $root = $this->getRootBusiness();
        return $root->branches()->count() + 1;
    }

    public function canAddBranch(): bool
    {
        $root = $this->getRootBusiness();
        return $root->totalBranchesCount() < $root->max_branches;
    }

    public function totalUsersCount(): int
    {
        $root = $this->getRootBusiness();
        $branchIds = $root->getAllBranchIds();
        return User::whereIn('shop_id', $branchIds)->where('status', 'active')->count();
    }

    public function canAddUser(): bool
    {
        $root = $this->getRootBusiness();
        return $root->totalUsersCount() < $root->max_users;
    }

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
        return \App\Services\ModuleRegistry::normalizeBusinessType($this->business_type) === \App\Services\ModuleRegistry::TYPE_LUBE;
    }

    public function isModuleAvailable(string $module): bool
    {
        return \App\Services\ModuleRegistry::isModuleAvailable($module, $this->business_type);
    }

    public function getAvailableModules(): array
    {
        return \App\Services\ModuleRegistry::getModulesForType($this->business_type ?? '');
    }

    public function getCanonicalBusinessType(): string
    {
        return \App\Services\ModuleRegistry::normalizeBusinessType($this->business_type);
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
