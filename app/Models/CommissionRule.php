<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionRule extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'priority',
        'rule_scope',
        'scope_reference_id',
        'commission_type',
        'percentage_rate',
        'fixed_fee_bob',
        'min_fee_bob',
        'max_fee_bob',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'percentage_rate' => 'decimal:4',
        'fixed_fee_bob' => 'decimal:2',
        'min_fee_bob' => 'decimal:2',
        'max_fee_bob' => 'decimal:2',
        'priority' => 'integer',
    ];
}
