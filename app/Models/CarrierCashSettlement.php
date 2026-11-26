<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CarrierCashSettlement extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'tenant_id',
        'carrier_id',
        'settlement_number',
        'total_collected_bob',
        'courier_fees_earned_bob',
        'net_deposit_bob',
        'payment_method',
        'deposit_reference',
        'receipt_path',
        'status',
        'approved_by_id',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'total_collected_bob'     => 'decimal:2',
        'courier_fees_earned_bob' => 'decimal:2',
        'net_deposit_bob'         => 'decimal:2',
        'approved_at'             => 'datetime',
    ];

    public function getNetDepositAmountBobAttribute(): float
    {
        return (float) ($this->attributes['net_deposit_bob'] ?? 0.0);
    }

    public function getDepositReceiptPathAttribute(): ?string
    {
        return $this->attributes['receipt_path'] ?? null;
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'carrier_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'carrier_cash_settlement_id');
    }

    public static function generateSettlementNumber(): string
    {
        return 'REND-' . date('ymd') . '-' . strtoupper(Str::random(5));
    }
}
