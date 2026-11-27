<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WalletWithdrawal extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED  = 'rejected';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'wallet_id',
        'withdrawal_number',
        'amount_bob',
        'payout_method',
        'beneficiary_name',
        'document_id',
        'bank_name',
        'account_number',
        'binance_pay_id',
        'qr_code_path',
        'status',
        'transfer_receipt_path',
        'transfer_reference',
        'admin_notes',
        'processed_by_id',
        'processed_at',
        'rejected_at',
    ];

    protected $casts = [
        'amount_bob'   => 'decimal:2',
        'processed_at' => 'datetime',
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

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_id');
    }

    public static function generateWithdrawalNumber(): string
    {
        $prefix = 'RET-' . date('ymd') . '-';
        return $prefix . strtoupper(Str::random(5));
    }
}
