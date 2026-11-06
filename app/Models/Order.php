<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'dropshipper_id',
        'warehouse_id',
        'carrier_id',
        'shipping_manifest_id',
        'order_number',
        'tracking_number',
        'customer_name',
        'customer_phone',
        'customer_document_id',
        'shipping_department',
        'shipping_city',
        'shipping_address',
        'shipping_reference',
        'customer_lat',
        'customer_lng',
        'total_amount_bob',
        'cost_amount_bob',
        'shipping_fee_bob',
        'platform_fee_bob',
        'dropshipper_profit_bob',
        'status',
        'delivery_attempts',
        'novedad_reason',
        'novedad_notes',
        'rescheduled_delivery_at',
        'payment_method',
        'dispatched_at',
        'delivered_at',
        'settled_at',
    ];

    protected $casts = [
        'total_amount_bob' => 'decimal:2',
        'cost_amount_bob' => 'decimal:2',
        'shipping_fee_bob' => 'decimal:2',
        'platform_fee_bob' => 'decimal:2',
        'dropshipper_profit_bob' => 'decimal:2',
        'delivery_attempts' => 'integer',
        'rescheduled_delivery_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function dropshipper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dropshipper_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }
}
