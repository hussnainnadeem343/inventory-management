<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['shop_id', 'name', 'username', 'email', 'password', 'role', 'status'])] #[Hidden(['password', 'remember_token'])]
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
}
