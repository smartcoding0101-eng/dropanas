<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankSettlementBatch extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'processed_by_id',
        'batch_number',
        'bank_name',
        'total_withdrawals_count',
        'total_amount_bob',
        'status',
        'bank_reference_number',
        'receipt_file_path',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'total_amount_bob'       => 'decimal:2',
        'total_withdrawals_count'=> 'integer',
        'completed_at'           => 'datetime',
    ];

    // ─────────────────────────────────────────
    // Constantes de Estado
    // ─────────────────────────────────────────
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_REJECTED   = 'rejected';

    const STATUSES = [
        self::STATUS_PENDING    => 'Pendiente',
        self::STATUS_PROCESSING => 'En proceso',
        self::STATUS_COMPLETED  => 'Completado',
        self::STATUS_REJECTED   => 'Rechazado',
    ];

    // Bancos soportados en Bolivia
    const BANKS = [
        'BNB'              => 'Banco Nacional de Bolivia (BNB)',
        'Banco Unión'      => 'Banco Unión',
        'BCP'              => 'Banco de Crédito BCP',
        'Mercantil'        => 'Banco Mercantil Santa Cruz',
        'QR Simple'        => 'QR Simple Interoperable',
    ];

    // ─────────────────────────────────────────
    // Generador de número de lote automático
    // ─────────────────────────────────────────
    public static function generateBatchNumber(string $bankKey): string
    {
        $prefix = match ($bankKey) {
            'BNB'         => 'BNB',
            'Banco Unión' => 'BUN',
            'BCP'         => 'BCP',
            'Mercantil'   => 'MRC',
            'QR Simple'   => 'QRS',
            default       => 'GEN',
        };

        $date  = now()->format('Ymd');
        $count = static::whereDate('created_at', today())
                       ->where('bank_name', $bankKey)
                       ->count() + 1;

        return sprintf('LOT-%s-%s-%03d', $prefix, $date, $count);
    }

    // ─────────────────────────────────────────
    // Relaciones
    // ─────────────────────────────────────────
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'bank_settlement_batch_id');
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING    => 'badge-warning',
            self::STATUS_PROCESSING => 'badge-info',
            self::STATUS_COMPLETED  => 'badge-success',
            self::STATUS_REJECTED   => 'badge-danger',
            default                 => 'badge-secondary',
        };
    }

    public function getReceiptUrlAttribute(): ?string
    {
        return $this->receipt_file_path
            ? asset('storage/' . $this->receipt_file_path)
            : null;
    }

    public function isPending(): bool    { return $this->status === self::STATUS_PENDING; }
    public function isCompleted(): bool  { return $this->status === self::STATUS_COMPLETED; }
    public function isRejected(): bool   { return $this->status === self::STATUS_REJECTED; }
}
