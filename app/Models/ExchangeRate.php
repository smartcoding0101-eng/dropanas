<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'currency_pair',
        'buy_rate',
        'sell_rate',
        'official_source',
        'is_official',
        'fetched_at',
    ];

    protected $casts = [
        'buy_rate' => 'decimal:4',
        'sell_rate' => 'decimal:4',
        'is_official' => 'boolean',
        'fetched_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
