<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'business_name',
        'business_type',
        'phone',
        'email',
        'city',
        'branches_count',
        'message',
        'preferred_plan',
        'status',
        'internal_notes',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
