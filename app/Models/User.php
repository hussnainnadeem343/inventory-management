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

    public function isShopAdmin(): bool
    {
        return $this->role === 'shop_admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff' || $this->role === 'user';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->isSuperAdmin() || $this->isShopAdmin()) {
            return true;
        }

        // If user has a specific custom role assigned, strictly respect its configured rights
        if ($this->role_id) {
            return $this->customRole ? $this->customRole->hasPermission($permissionSlug) : false;
        }

        // Default baseline permissions for standard staff/user without custom role
        $defaultStaffPerms = [
            'pos.access', 'pos.discount_item', 'pos.discount_bill',
            'sales.view_invoices', 'products.view', 'stock.view', 'stock.sell',
        ];

        return in_array($permissionSlug, $defaultStaffPerms, true);
    }

    public function hasAnyPermission(array|string ...$permissions): bool
    {
        if ($this->isSuperAdmin() || $this->isShopAdmin()) {
            return true;
        }

        $flattened = collect($permissions)->flatten()->all();
        foreach ($flattened as $perm) {
            if ($this->hasPermission((string) $perm)) {
                return true;
            }
        }

        return false;
    }
}
