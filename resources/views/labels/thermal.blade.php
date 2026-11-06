<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Rótulo Térmico COD - {{ $order->order_number }}</title>
    <style>
        @page {
            size: 100mm 150mm; /* Formato estándar Zebra 4x6 pulgadas */
            margin: 4mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            margin: 0;
            padding: 0;
            font-size: 11pt;
            background: #fff;
        }
        .label-container {
            border: 2px solid #000;
            padding: 8px;
            box-sizing: border-box;
            height: 142mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
        }
        .carrier-logo {
            font-size: 16pt;
            font-weight: 900;
            letter-spacing: -0.5px;
        }
        .badge-cod {
            background: #000;
            color: #fff;
            padding: 4px 8px;
            font-size: 12pt;
            font-weight: bold;
            border-radius: 4px;
        }
        .barcode-section {
            text-align: center;
            padding: 8px 0;
            border-bottom: 1.5px solid #000;
        }
        .barcode-svg {
            display: inline-block;
            max-width: 90%;
            height: 50px;
        }
        .barcode-text {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            font-size: 11pt;
            letter-spacing: 2px;
            margin-top: 3px;
        }
        .address-box {
            padding: 8px 0;
            border-bottom: 1.5px solid #000;
            font-size: 10.5pt;
        }
        .dept-banner {
            font-size: 18pt;
            font-weight: 900;
            text-transform: uppercase;
            text-align: right;
            border-left: 2px solid #000;
            padding-left: 6px;
        }
        .financial-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f2f2f2;
            border: 2px dashed #000;
            padding: 8px 12px;
            margin: 6px 0;
        }
        .amount-collect {
            font-size: 20pt;
            font-weight: 900;
            font-family: Arial, sans-serif;
        }
        .items-table {
            width: 100%;
            font-size: 9pt;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .items-table th, .items-table td {
            text-align: left;
            padding: 2px 4px;
            border-bottom: 1px solid #ccc;
        }
        .footer-note {
            font-size: 7.5pt;
            text-align: center;
            color: #333;
            border-top: 1px solid #000;
            padding-top: 4px;
        }
        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #960016;
            color: #fff;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            z-index: 999;
        }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="no-print">
        <i class="fa-solid fa-print"></i> Imprimir Rótulo (Ctrl+P)
    </button>

    <div class="label-container">
        
        <!-- HEADER -->
        <div class="header">
            <div>
                <div class="carrier-logo">DEX BOLIVIA EXPRESS</div>
                <div style="font-size: 8pt; font-weight: bold; color: #555;">RED LOGÍSTICA COD NACIONAL</div>
            </div>
            <div class="badge-cod">
                PAGO CONTRA ENTREGA
            </div>
        </div>

        <!-- CÓDIGO DE BARRAS CENTRAL -->
        <div class="barcode-section">
            <div class="barcode-svg">
                {!! $barcodeSvg !!}
            </div>
            <div class="barcode-text">{{ $barcodeText }}</div>
        </div>

        <!-- DESTINATARIO Y DEPARTAMENTO -->
        <div class="address-box">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: top; width: 65%;">
                        <strong style="font-size: 12pt;">DESTINATARIO:</strong><br>
                        <span style="font-size: 11pt; font-weight: bold;">{{ $order->customer_name }}</span><br>
                        <span>Teléfono: <strong>{{ $order->customer_phone }}</strong></span><br>
                        <span>Dirección: {{ $order->shipping_address }}</span><br>
                        @if($order->shipping_reference)
                            <span style="font-size: 9pt; font-style: italic;">Ref: {{ $order->shipping_reference }}</span><br>
                        @endif
                    </td>
                    <td class="dept-banner" style="vertical-align: middle; width: 35%;">
                        <div>{{ $order->shipping_department }}</div>
                        <div style="font-size: 9.5pt; font-weight: normal; margin-top: 4px;">{{ $order->shipping_city }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- MONTO A RECAUDAR EN BS (BOLIVIA) -->
        <div class="financial-box">
            <div>
                <div style="font-size: 8pt; font-weight: bold; text-transform: uppercase;">Monto Total a Cobrar al Cliente:</div>
                <div style="font-size: 7.5pt; color: #555;">Efectivo en Bs. o QR Simple Interoperable</div>
            </div>
            <div class="amount-collect">
                Bs. {{ number_format($order->total_amount_bob, 2) }}
            </div>
        </div>

        <!-- DETALLE DE PRODUCTOS PARA VERIFICACIÓN -->
        <div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Cant.</th>
                        <th>Producto / Contenido</th>
                        <th style="text-align: right;">SKU</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td style="font-weight: bold;">{{ $item->quantity }}x</td>
                            <td>{{ $item->product->title ?? 'Item Empacado' }}</td>
                            <td style="text-align: right; font-family: monospace;">{{ $item->product->sku ?? 'GEN' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- PIE DE GUÍA -->
        <div class="footer-note">
            <div><strong>Dropana Bolivia</strong> • Bodega: {{ $order->warehouse->name ?? 'Central Santa Cruz' }} • Pedido: {{ $order->order_number }}</div>
            <div>Exige tu comprobante QR o recibo al cartero repartidor. Soporte DEX: (+591) 700-00000</div>
        </div>

    </div>

</body>
</html>
