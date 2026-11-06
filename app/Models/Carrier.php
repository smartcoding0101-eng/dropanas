<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrier extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'contact_name',
        'contact_phone',
        'contact_email',
        'has_cod_service',
        'integration_mode',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'has_cod_service' => 'boolean',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function coverages(): HasMany
    {
        return $this->hasMany(CarrierCoverage::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function shippingManifests(): HasMany
    {
        return $this->hasMany(ShippingManifest::class);
    }
}
