<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WalletTopup extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'wallet_id',
        'topup_number',
        'amount_bob',
        'payment_method',
        'bank_name',
        'bank_reference',
        'receipt_path',
        'status',
        'admin_notes',
        'approved_by_id',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'amount_bob'   => 'decimal:2',
        'approved_at'  => 'datetime',
        'rejected_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public static function generateTopupNumber(): string
    {
        $prefix = 'REC-' . date('ymd') . '-';
        return $prefix . strtoupper(Str::random(5));
    }
}
