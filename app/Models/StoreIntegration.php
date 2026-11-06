<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreIntegration extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'dropshipper_id',
        'platform',
        'store_name',
        'store_url',
        'api_key',
        'api_secret',
        'webhook_secret',
        'is_active',
        'auto_dispatch',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'auto_dispatch'  => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function dropshipper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dropshipper_id');
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(StoreWebhookLog::class);
    }
}
