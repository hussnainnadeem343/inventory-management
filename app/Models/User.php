<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['shop_id', 'role_id', 'name', 'username', 'email', 'password', 'role', 'status'])] #[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory,Notifiable;

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function shop(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function customRole(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'created_by');
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class, 'created_by');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'created_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isBusinessOwner(): bool
    {
        return $this->role === 'business_owner';
    }

    public function isShopAdmin(): bool
    {
        return $this->role === 'shop_admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff' || $this->role === 'user';
    }

    public function isLubeShop(): bool
    {
        if ($this->isSuperAdmin() || $this->isBusinessOwner()) {
            $sessionShopId = session('dashboard_shop_id');
            if ($sessionShopId) {
                return Shop::find($sessionShopId)?->isLube() ?? false;
            }
            if ($this->isSuperAdmin()) {
                return true;
            }
            return $this->shop?->isLube() ?? false;
        }

        return $this->shop?->isLube() ?? false;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canAccessModule(string $module): bool
    {
        if ($this->isSuperAdmin()) {
            return in_array($module, ['management', 'users', 'roles', 'branches', 'shops'], true);
        }

        if (! $this->shop) {
            return false;
        }

        if (! $this->shop->isModuleAvailable($module)) {
            return false;
        }

        if ($this->isBusinessOwner() || $this->isShopAdmin()) {
            return true;
        }

        $resolvedModules = \App\Services\ModuleRegistry::resolveModuleGroup($module);

        if ($this->role_id && $this->customRole) {
            return $this->customRole->permissions()->whereIn('module', $resolvedModules)->exists();
        }

        if ($this->role === 'staff' || $this->role === 'user') {
            $legacyHubs = ['pos', 'sales', 'inventory', 'stock', 'products'];
            foreach ($resolvedModules as $m) {
                if (in_array($m, $legacyHubs, true) || in_array($module, $legacyHubs, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hasPermission(string $permissionSlug): bool
    {
        static $permissionModuleMap = null;
        if ($permissionModuleMap === null) {
            $permissionModuleMap = Permission::pluck('module', 'slug')->all();
        }

        $module = $permissionModuleMap[$permissionSlug] ?? str($permissionSlug)->before('.')->value();

        // If user belongs to a shop, the module must be enabled for that shop's business type
        if ($this->shop && ! $this->shop->isModuleAvailable($module)) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            $platformPerms = [
                'users.view', 'users.manage', 'roles.manage',
                'settings.view', 'branches.view', 'branches.manage',
            ];

            return in_array($permissionSlug, $platformPerms, true);
        }

        if ($this->isBusinessOwner() || $this->isShopAdmin()) {
            return true;
        }

        if ($this->role_id) {
            return $this->customRole ? $this->customRole->hasPermission($permissionSlug) : false;
        }

        // Backward compatibility for legacy accounts without an assigned custom role
        if ($this->role === 'staff' || $this->role === 'user') {
            $legacyStaffPerms = [
                'pos.access', 'pos.discount_item', 'pos.discount_bill',
                'sales.view_invoices', 'products.view', 'stock.view', 'stock.sell',
            ];

            return in_array($permissionSlug, $legacyStaffPerms, true);
        }

        return false;
    }

    public function hasAnyPermission(array|string ...$permissions): bool
    {
        $flattened = collect($permissions)->flatten()->all();
        foreach ($flattened as $perm) {
            if ($this->hasPermission((string) $perm)) {
                return true;
            }
        }

        return false;
    }
}
