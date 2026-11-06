@php
    $itemsCount = $order->items ? $order->items->sum('quantity') : 1;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota de Entrega #{{ $order->order_number }} - Dropana Bolivia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            padding: 24px;
            font-size: 13px;
            line-height: 1.5;
        }
        .page {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 36px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #960016;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .logo-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand {
            font-size: 26px;
            font-weight: 800;
            color: #960016;
            letter-spacing: -0.5px;
        }
        .badge-country {
            display: inline-block;
            background: #fef2f2;
            color: #960016;
            border: 1px solid #fecaca;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 9999px;
            text-transform: uppercase;
        }
        .doc-meta {
            text-align: right;
        }
        .doc-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
        }
        .doc-number {
            font-family: 'JetBrains Mono', monospace;
            font-size: 15px;
            font-weight: 700;
            color: #ED2100;
            margin-top: 4px;
        }
        .doc-date {
            color: #64748b;
            font-size: 12px;
            margin-top: 2px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
        }
        .card-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .card-content strong {
            color: #0f172a;
            font-size: 14px;
            display: block;
            margin-bottom: 4px;
        }
        .card-content p {
            color: #475569;
            font-size: 12px;
            margin-bottom: 2px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .table th {
            background: #f1f5f9;
            color: #475569;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #cbd5e1;
        }
        .table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }
        .table td.num {
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
        }
        .table th.num {
            text-align: right;
        }
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 28px;
        }
        .totals-box {
            width: 320px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 12px;
            color: #475569;
        }
        .totals-row.final {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 2px solid #cbd5e1;
            font-size: 16px;
            font-weight: 800;
            color: #960016;
        }
        .totals-row.final .amount {
            font-family: 'JetBrains Mono', monospace;
            color: #ED2100;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            padding-top: 20px;
        }
        .sign-box {
            text-align: center;
            border-top: 1px dashed #94a3b8;
            padding-top: 10px;
            color: #64748b;
            font-size: 11px;
        }
        .sign-box strong {
            display: block;
            color: #1e293b;
            font-size: 12px;
            margin-bottom: 2px;
        }
        .footer-note {
            margin-top: 30px;
            text-align: center;
            color: #94a3b8;
            font-size: 11px;
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
        }
        .barcode-box {
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        .barcode-box svg {
            max-height: 40px;
        }
        .actions-bar {
            max-width: 800px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background: #ED2100;
            color: white;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 13px;
            box-shadow: 0 2px 4px rgba(237, 33, 0, 0.2);
        }
        .btn-print:hover {
            background: #d61d00;
        }
        .btn-back {
            background: #ffffff;
            color: #475569;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            text-decoration: none;
            font-size: 12px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .actions-bar {
                display: none;
            }
            .page {
                box-shadow: none;
                border: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="actions-bar">
        <a href="javascript:history.back()" class="btn-back">← Volver</a>
        <button onclick="window.print()" class="btn-print">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Imprimir Nota de Entrega
        </button>
    </div>

    <div class="page">
        <!-- Header -->
        <div class="header">
            <div>
                <div class="logo-title">
                    <span class="brand">DROPANA</span>
                    <span class="badge-country">Bolivia COD</span>
                </div>
                <p style="color: #64748b; font-size: 11px; margin-top: 4px;">
                    Plataforma Logística y E-commerce Contracomprobante Oficial
                </p>
                <p style="color: #64748b; font-size: 11px;">
                    Bodega Origen: <strong>{{ $order->warehouse->name ?? 'Megabodega Central' }}</strong> ({{ $order->warehouse->department ?? 'Santa Cruz' }})
                </p>
            </div>
            <div class="doc-meta">
                <div class="doc-title">Nota de Entrega</div>
                <div class="doc-number">#{{ $order->order_number }}</div>
                <div class="doc-date">Fecha: {{ $order->created_at->format('d/m/Y H:i') }}</div>
                @if(isset($barcodeSvg))
                    <div class="barcode-box">
                        {!! $barcodeSvg !!}
                        <span style="font-family: 'JetBrains Mono', monospace; font-size: 10px; color: #64748b; margin-top: 2px;">
                            {{ $order->tracking_number ?: $order->order_number }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Info Grid -->
        <div class="grid-2">
            <!-- Destinatario / Cliente -->
            <div class="card">
                <div class="card-title">
                    👤 Destinatario Final (Cliente)
                </div>
                <div class="card-content">
                    <strong>{{ $order->customer_name }}</strong>
                    <p><strong>Tel / WhatsApp:</strong> {{ $order->customer_phone }}</p>
                    @if($order->customer_document_id)
                        <p><strong>CI / NIT:</strong> {{ $order->customer_document_id }}</p>
                    @endif
                    <p><strong>Destino:</strong> {{ $order->shipping_city }}, {{ $order->shipping_department }}</p>
                    <p><strong>Dirección:</strong> {{ $order->shipping_address }}</p>
                    @if($order->shipping_reference)
                        <p><strong>Referencia:</strong> {{ $order->shipping_reference }}</p>
                    @endif
                </div>
            </div>

            <!-- Despacho y Transportadora -->
            <div class="card">
                <div class="card-title">
                    🚚 Datos del Envío & Transportadora
                </div>
                <div class="card-content">
                    <strong>{{ $order->carrier->name ?? 'DEX Bolivia Express' }}</strong>
                    <p><strong>Guía / Tracking:</strong> <span style="font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #960016;">{{ $order->tracking_number ?? 'S/T' }}</span></p>
                    <p><strong>Método de Cobro:</strong> Pago Contra Entrega (Efectivo / QR Simple)</p>
                    <p><strong>Tienda / Dropshipper:</strong> {{ $order->dropshipper->name ?? 'Comercio Asociado' }}</p>
                    <p><strong>Estado Operativo:</strong> <span style="text-transform: uppercase; font-weight: 700; color: #ED2100;">{{ $order->status }}</span></p>
                </div>
            </div>
        </div>

        <!-- Tabla de Artículos -->
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">Item</th>
                    <th>Descripción del Producto</th>
                    <th class="num" style="width: 80px;">Cant.</th>
                    <th class="num" style="width: 120px;">Precio Unit.</th>
                    <th class="num" style="width: 130px;">Subtotal (BOB)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $index => $item)
                    <tr>
                        <td style="color: #64748b; font-family: 'JetBrains Mono', monospace;">{{ $index + 1 }}</td>
                        <td>
                            <strong style="color: #0f172a;">{{ $item->product->name ?? 'Producto' }}</strong>
                            @if(isset($item->product->sku))
                                <div style="font-size: 11px; color: #64748b; font-family: 'JetBrains Mono', monospace;">SKU: {{ $item->product->sku }}</div>
                            @endif
                        </td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">Bs {{ number_format($item->unit_price_bob, 2) }}</td>
                        <td class="num" style="font-weight: 700;">Bs {{ number_format($item->subtotal_bob, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">
                            No hay items desglosados en este pedido.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Totales -->
        <div class="totals-section">
            <div class="totals-box">
                <div class="totals-row">
                    <span>Subtotal Productos:</span>
                    <span style="font-family: 'JetBrains Mono', monospace;">Bs {{ number_format($order->items->sum('subtotal_bob') ?: $order->total_amount_bob, 2) }}</span>
                </div>
                <div class="totals-row">
                    <span>Flete de Envío Courier:</span>
                    <span style="font-family: 'JetBrains Mono', monospace;">Bs {{ number_format($order->shipping_fee_bob, 2) }}</span>
                </div>
                <div class="totals-row final">
                    <span>TOTAL A COBRAR (COD):</span>
                    <span class="amount">Bs {{ number_format($order->total_amount_bob, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Firmas -->
        <div class="signatures">
            <div class="sign-box">
                <strong>Entregado por (Courier / Transportista)</strong>
                <span>Firma / Nombre / C.I.</span>
            </div>
            <div class="sign-box">
                <strong>Recibido Conforme (Cliente Final)</strong>
                <span>Firma / C.I. / Fecha y Hora</span>
            </div>
        </div>

        <!-- Nota al pie -->
        <div class="footer-note">
            Este documento certifica la recepción física y comprobación de mercancía bajo modalidad Contra Entrega (COD) en el Estado Plurinacional de Bolivia.<br>
            Dropana Bolivia • Soporte: soporte@dropana.bo • www.dropana.bo
        </div>
    </div>

</body>
</html>
