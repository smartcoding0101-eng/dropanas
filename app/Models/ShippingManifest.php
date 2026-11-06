<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingManifest extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'carrier_id',
        'manifest_number',
        'manifest_date',
        'total_packages',
        'total_cod_amount_bob',
        'status',
        'export_file_path',
    ];

    protected $casts = [
        'manifest_date' => 'date',
        'total_packages' => 'integer',
        'total_cod_amount_bob' => 'decimal:2',
    ];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
