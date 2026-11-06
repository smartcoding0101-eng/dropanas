<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierCoverage extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'carrier_id',
        'department',
        'city',
        'zone_type',
        'base_shipping_bob',
        'cod_fee_percentage',
        'estimated_delivery_days',
        'is_active',
    ];

    protected $casts = [
        'base_shipping_bob' => 'decimal:2',
        'cod_fee_percentage' => 'decimal:2',
        'estimated_delivery_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }
}
