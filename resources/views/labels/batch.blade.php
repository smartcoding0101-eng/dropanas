<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Impresión Masiva de Rótulos Térmicos COD ({{ count($labels) }} Guías)</title>
    <style>
        @page {
            size: 100mm 150mm;
            margin: 4mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            margin: 0;
            padding: 0;
            font-size: 11pt;
            background: #e5e5e5;
        }
        .label-wrapper {
            page-break-after: always;
            background: #fff;
            margin: 10px auto;
            width: 100mm;
            height: 142mm;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .label-container {
            border: 2px solid #000;
            padding: 8px;
            box-sizing: border-box;
            height: 100%;
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
            padding: 10px 20px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 999;
        }
        @media print {
            .no-print { display: none; }
            body { background: #fff; margin: 0; }
            .label-wrapper { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="no-print">
        <i class="fa-solid fa-print"></i> Imprimir Lote Completo ({{ count($labels) }} Etiquetas)
    </button>

    @foreach($labels as $item)
        @php $order = $item['order']; @endphp
        <div class="label-wrapper">
            <div class="label-container">
                
                <div class="header">
                    <div>
                        <div class="carrier-logo">DEX BOLIVIA EXPRESS</div>
                        <div style="font-size: 8pt; font-weight: bold; color: #555;">RED LOGÍSTICA COD NACIONAL</div>
                    </div>
                    <div class="badge-cod">
                        PAGO CONTRA ENTREGA
                    </div>
                </div>

                <div class="barcode-section">
                    <div class="barcode-svg">
                        {!! $item['barcodeSvg'] !!}
                    </div>
                    <div class="barcode-text">{{ $item['barcodeText'] }}</div>
                </div>

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

                <div class="financial-box">
                    <div>
                        <div style="font-size: 8pt; font-weight: bold; text-transform: uppercase;">Total a Cobrar:</div>
                        <div style="font-size: 7.5pt; color: #555;">Efectivo o QR Simple</div>
                    </div>
                    <div class="amount-collect">
                        Bs. {{ number_format($order->total_amount_bob, 2) }}
                    </div>
                </div>

                <div>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Cant.</th>
                                <th>Producto</th>
                                <th style="text-align: right;">SKU</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $it)
                                <tr>
                                    <td style="font-weight: bold;">{{ $it->quantity }}x</td>
                                    <td>{{ $it->product->title ?? 'Item Empacado' }}</td>
                                    <td style="text-align: right; font-family: monospace;">{{ $it->product->sku ?? 'GEN' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="footer-note">
                    <div><strong>Dropana Bolivia</strong> • Bodega: {{ $order->warehouse->name ?? 'Central Santa Cruz' }} • Pedido: {{ $order->order_number }}</div>
                    <div>Exige tu comprobante QR o recibo al cartero repartidor. Soporte DEX: (+591) 700-00000</div>
                </div>

            </div>
        </div>
    @endforeach

</body>
</html>
