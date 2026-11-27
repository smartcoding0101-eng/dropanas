<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Retiro #{{ $withdrawal->withdrawal_number }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1E293B;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F8FAFC; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 600px; background-color: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #E2E8F0;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%); padding: 28px 32px; text-align: left;">
                            <div style="font-size: 20px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.5px;">
                                <span style="color: #6366F1;">DROPANA</span> FINTECH
                            </div>
                            <div style="font-size: 12px; color: #94A3B8; margin-top: 4px;">
                                Mesa de Pagos & Liquidaciones a Vendedores Bolivia
                            </div>
                        </td>
                    </tr>

                    <!-- Notification Badge -->
                    <tr>
                        <td style="padding: 32px 32px 10px 32px;">
                            <div style="display: inline-block; background-color: #EEF2FF; border: 1px solid #C7D2FE; color: #3730A3; font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">
                                💸 Nueva Solicitud de Retiro de Fondos
                            </div>
                            <h1 style="font-size: 22px; font-weight: 800; color: #0F172A; margin: 16px 0 8px 0; letter-spacing: -0.5px;">
                                Solicitud de transferencia por Bs. {{ number_format((float) $withdrawal->amount_bob, 2) }}
                            </h1>
                            <p style="font-size: 14px; color: #64748B; line-height: 1.5; margin: 0 0 24px 0;">
                                El vendedor <strong>{{ $withdrawal->user?->name }}</strong> ha solicitado el retiro de sus utilidades disponibles. El saldo ya fue <strong>congelado en su billetera virtual</strong>. Escanea su código QR o transfiere a su cuenta bancaria.
                            </p>
                        </td>
                    </tr>

                    <!-- QR Code Card (si adjuntó QR) -->
                    @if($withdrawal->qr_code_path)
                    <tr>
                        <td style="padding: 0 32px 20px 32px;" align="center">
                            <div style="background-color: #F8FAFC; border: 2px dashed #CBD5E1; border-radius: 14px; padding: 18px; text-align: center; max-width: 320px;">
                                <div style="font-size: 12px; font-weight: 800; color: #1E293B; margin-bottom: 8px;">
                                    📱 Código QR de Cobro del Vendedor
                                </div>
                                <img src="{{ asset('storage/' . $withdrawal->qr_code_path) }}" alt="QR de Cobro Vendedor" style="width: 220px; height: 220px; border-radius: 12px; border: 1px solid #E2E8F0; object-fit: contain; background-color: #FFFFFF; padding: 6px;">
                                <div style="font-size: 11px; color: #64748B; margin-top: 8px;">
                                    Abre tu app bancaria (Banco Unión, BCP, BNB, Bisa, GanaMóvil) y escanea este QR para transferirle <strong>Bs. {{ number_format((float) $withdrawal->amount_bob, 2) }}</strong>.
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif

                    <!-- Details Card -->
                    <tr>
                        <td style="padding: 0 32px 24px 32px;">
                            <div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 20px;">
                                <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 13px;">
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0; width: 45%;">Código de Retiro:</td>
                                        <td style="color: #0F172A; font-weight: 700; font-family: monospace; text-align: right; padding: 6px 0;">#{{ $withdrawal->withdrawal_number }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0;">Monto a Transferir:</td>
                                        <td style="color: #4F46E5; font-weight: 800; font-size: 16px; font-family: monospace; text-align: right; padding: 6px 0;">Bs. {{ number_format((float) $withdrawal->amount_bob, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0;">Beneficiario:</td>
                                        <td style="color: #0F172A; font-weight: 700; text-align: right; padding: 6px 0;">{{ $withdrawal->beneficiary_name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0;">Carnet de Identidad (CI):</td>
                                        <td style="color: #0F172A; font-weight: 600; text-align: right; padding: 6px 0;">{{ $withdrawal->document_id }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0;">Vendedor (Email):</td>
                                        <td style="color: #0F172A; text-align: right; padding: 6px 0;">{{ $withdrawal->user?->email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0;">Método Solicitado:</td>
                                        <td style="color: #0F172A; font-weight: 600; text-align: right; padding: 6px 0;">
                                            {{ $withdrawal->payout_method === 'qr_simple' ? 'Transferencia QR Simple (ACH Bolivia)' : ($withdrawal->payout_method === 'bank_transfer' ? 'Cuenta Bancaria Tradicional' : 'Binance Pay USDT') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0;">Banco Destino:</td>
                                        <td style="color: #0F172A; font-weight: 600; text-align: right; padding: 6px 0;">{{ $withdrawal->bank_name ?? 'No especificado' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0;">Nro. Cuenta / Teléfono QR:</td>
                                        <td style="color: #0F172A; font-weight: 700; font-family: monospace; text-align: right; padding: 6px 0;">
                                            {{ $withdrawal->account_number ?? $withdrawal->binance_pay_id ?? 'No especificado' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0;">Fecha de Solicitud:</td>
                                        <td style="color: #64748B; text-align: right; padding: 6px 0;">{{ $withdrawal->created_at->format('d/m/Y H:i:s') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- CTA Action Button -->
                    <tr>
                        <td align="center" style="padding: 10px 32px 32px 32px;">
                            <a href="{{ url('/admin/recargas?tab=withdrawals&search=' . $withdrawal->withdrawal_number) }}" style="display: inline-block; background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%); color: #FFFFFF; font-size: 14px; font-weight: 700; padding: 14px 32px; border-radius: 12px; text-decoration: none; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);">
                                💳 Abrir Panel y Confirmar Transferencia
                            </a>
                            <p style="font-size: 11px; color: #94A3B8; margin-top: 12px;">
                                Una vez transferido el importe en tu app bancaria, marca la solicitud como pagada para registrar la salida en el libro mayor.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #F1F5F9; border-top: 1px solid #E2E8F0; padding: 20px 32px; text-align: center; font-size: 11px; color: #64748B;">
                            <div>Dropana FinTech Logística Bolivia S.R.L. • Mesa Central de Conciliación</div>
                            <div style="margin-top: 4px;">Notificación enviada al Administrador: <strong style="color: #334155;">{{ $adminEmail }}</strong></div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
