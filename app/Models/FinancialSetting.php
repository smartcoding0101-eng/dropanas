<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialSetting extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'seller_commission_bob',
        'provider_commission_bob',
        'bank_account_name',
        'bank_name',
        'bank_account_number',
        'qr_deposit_image_url',
        'recharge_instructions',
        'admin_notification_email',
    ];

    protected $casts = [
        'seller_commission_bob'   => 'decimal:2',
        'provider_commission_bob' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Obtiene la configuración financiera activa del tenant o crea la predeterminada (5 Bs / 5 Bs).
     */
    public static function forTenant(string $tenantId): self
    {
        return static::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'seller_commission_bob'   => 5.00,
                'provider_commission_bob' => 5.00,
                'bank_account_name'       => 'Dropana Bolivia S.R.L.',
                'bank_name'               => 'Banco Nacional de Bolivia / BCP (QR Simple)',
                'bank_account_number'     => '1000-482910-BOB',
                'admin_notification_email'=> 'admin@bolidrop.bo',
                'recharge_instructions'   => 'Escanea el código QR oficial de Dropana desde tu app bancaria (BCP, BNB, Banco Unión, GanaMóvil). Una vez realizada la transferencia, sube la captura de pantalla o comprobante junto con el código de referencia para que el Administrador active tu saldo.',
            ]
        );
    }

    /**
     * Obtiene el correo oficial para notificaciones de recarga.
     */
    public function getEffectiveAdminEmail(): string
    {
        return $this->admin_notification_email
            ?: (User::where('role', 'tenant_admin')->value('email') ?? 'admin@bolidrop.bo');
    }

    /**
     * Obtiene la URL del código QR para recargas (imagen subida o QR Simple oficial estándar).
     */
    public function getEffectiveQrImageUrl(): string
    {
        if ($this->qr_deposit_image_url) {
            return asset('storage/' . $this->qr_deposit_image_url);
        }

        // QR oficial predeterminado Dropana FinTech Bolivia (EMVCo interoperable Simple ACH)
        $qrPayload = json_encode([
            'country'        => 'BO',
            'currency'       => 'BOB',
            'beneficiary'    => $this->bank_account_name ?? 'Dropana Bolivia S.R.L.',
            'bank'           => $this->bank_name ?? 'Banco Nacional de Bolivia / BCP (QR Simple)',
            'account'        => $this->bank_account_number ?? '1000-482910-BOB',
            'concept'        => 'Recarga Billetera Dropana Prepago',
            'admin_contact'  => $this->getEffectiveAdminEmail(),
        ]);

        return 'https://api.qrserver.com/v1/create-qr-code/?size=350x350&margin=10&data=' . urlencode($qrPayload);
    }
}
