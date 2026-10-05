<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountHead extends Model
{
    protected $fillable = [
        'name',
        'code',
        'nature',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
