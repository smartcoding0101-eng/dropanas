<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'full_name',
        'phone',
        'document_id',
        'email',
        'department',
        'city',
        'default_address',
        'total_orders',
        'completed_orders',
        'cancelled_orders',
        'total_spent_bob',
        'customer_segment',
        'last_order_at',
    ];

    protected $casts = [
        'total_orders'     => 'integer',
        'completed_orders' => 'integer',
        'cancelled_orders' => 'integer',
        'total_spent_bob'  => 'decimal:2',
        'last_order_at'    => 'datetime',
    ];

    /** Recalcula el segmento del cliente según su comportamiento de compra */
    public function recalculateSegment(): void
    {
        if ($this->completed_orders >= 5 || $this->total_spent_bob >= 1000) {
            $this->customer_segment = 'vip';
        } elseif ($this->completed_orders >= 2) {
            $this->customer_segment = 'regular';
        } elseif ($this->cancelled_orders >= 2 && $this->completed_orders === 0) {
            $this->customer_segment = 'at_risk';
        } else {
            $this->customer_segment = 'new';
        }
        $this->save();
    }
}
