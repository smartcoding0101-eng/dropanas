<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Recarga #{{ $topup->topup_number }}</title>
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
                                <span style="color: #10B981;">DROPANA</span> FINTECH
                            </div>
                            <div style="font-size: 12px; color: #94A3B8; margin-top: 4px;">
                                Plataforma de Comercio Electrónico & Logística Bolivia
                            </div>
                        </td>
                    </tr>

                    <!-- Notification Badge -->
                    <tr>
                        <td style="padding: 32px 32px 10px 32px;">
                            <div style="display: inline-block; background-color: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">
                                🔔 Nueva Solicitud de Recarga Pendiente
                            </div>
                            <h1 style="font-size: 22px; font-weight: 800; color: #0F172A; margin: 16px 0 8px 0; letter-spacing: -0.5px;">
                                Se ha registrado una solicitud de recarga por Bs. {{ number_format((float) $topup->amount_bob, 2) }}
                            </h1>
                            <p style="font-size: 14px; color: #64748B; line-height: 1.5; margin: 0 0 24px 0;">
                                El vendedor <strong>{{ $topup->user?->name }}</strong> ha enviado una solicitud de acreditación a su billetera para prepago de pedidos. Por favor coteja el comprobante con tu extracto bancario y valida la transferencia.
                            </p>
                        </td>
                    </tr>

                    <!-- Transaction Details Card -->
                    <tr>
                        <td style="padding: 0 32px 24px 32px;">
                            <div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 20px;">
                                <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 13px;">
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0; width: 45%;">Código de Solicitud:</td>
                                        <td style="color: #0F172A; font-weight: 700; font-family: monospace; text-align: right; padding: 6px 0;">#{{ $topup->topup_number }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0;">Monto a Acreditar:</td>
                                        <td style="color: #059669; font-weight: 800; font-size: 16px; font-family: monospace; text-align: right; padding: 6px 0;">Bs. {{ number_format((float) $topup->amount_bob, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0;">Vendedor Dropshipper:</td>
                                        <td style="color: #0F172A; font-weight: 600; text-align: right; padding: 6px 0;">{{ $topup->user?->name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0;">Email del Vendedor:</td>
                                        <td style="color: #0F172A; text-align: right; padding: 6px 0;">{{ $topup->user?->email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0;">Método Declarado:</td>
                                        <td style="color: #0F172A; font-weight: 600; text-align: right; padding: 6px 0;">
                                            {{ in_array($topup->payment_method, ['qr_simple', 'qr_bolivia']) ? 'Pago QR Simple (ACH Interoperable)' : 'Transferencia Bancaria Nacional' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0;">Nro. de Referencia / Voucher:</td>
                                        <td style="color: #0F172A; font-weight: 700; font-family: monospace; text-align: right; padding: 6px 0;">
                                            {{ $topup->bank_reference ?? 'No especificada' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0;">Fecha y Hora de Envío:</td>
                                        <td style="color: #64748B; text-align: right; padding: 6px 0;">{{ $topup->created_at->format('d/m/Y H:i:s') }}</td>
                                    </tr>
                                    @if($topup->receipt_path)
                                    <tr>
                                        <td style="color: #64748B; padding: 6px 0;">Comprobante Adjunto:</td>
                                        <td style="color: #2563EB; font-weight: 600; text-align: right; padding: 6px 0;">
                                            📎 Adjunto en este correo y disponible en panel
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- CTA Action Button -->
                    <tr>
                        <td align="center" style="padding: 10px 32px 32px 32px;">
                            <a href="{{ url('/admin/recargas?search=' . $topup->topup_number) }}" style="display: inline-block; background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #FFFFFF; font-size: 14px; font-weight: 700; padding: 14px 32px; border-radius: 12px; text-decoration: none; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.3);">
                                🔍 Abrir Menú y Validar Transferencia
                            </a>
                            <p style="font-size: 11px; color: #94A3B8; margin-top: 12px;">
                                Al aprobar, los fondos se acreditarán en tiempo real a la billetera virtual del vendedor.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #F1F5F9; border-top: 1px solid #E2E8F0; padding: 20px 32px; text-align: center; font-size: 11px; color: #64748B;">
                            <div>Dropana FinTech Logística Bolivia S.R.L. • Sistema de Liquidación Inmediata</div>
                            <div style="margin-top: 4px;">Este correo fue generado automáticamente para el Administrador: <strong style="color: #334155;">{{ $adminEmail }}</strong></div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
