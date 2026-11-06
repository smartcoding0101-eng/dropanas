<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'full_name',
        'phone',
        'email',
        'interest_role',
        'estimated_monthly_volume',
        'department',
        'city',
        'notes',
        'status',
    ];
}
