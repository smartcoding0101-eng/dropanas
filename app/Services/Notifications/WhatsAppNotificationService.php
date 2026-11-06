<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    /**
     * Plantillas oficiales transaccionales para WhatsApp en Bolivia.
     */
    public const TEMPLATES = [
        'order_confirmed' => [
            'name' => 'Confirmación de Pedido COD',
            'body' => "¡Hola *{customer_name}*! 👋 Tu pedido *#{order_number}* en Dropana Bolivia fue recibido con éxito.\n\n📦 *Productos:* {items_summary}\n💰 *Monto a pagar al recibir (COD):* Bs {total_amount_bob}\n📍 *Ciudad/Destino:* {shipping_city}, {shipping_department}\n\nNuestro transportista de *{carrier_name}* te contactará antes de la entrega. ¡Gracias por confiar en nosotros!",
        ],
        'order_dispatched' => [
            'name' => 'Pedido en Camino (Despachado)',
            'body' => "¡Buenas noticias, *{customer_name}*! 🚀 Tu pedido *#{order_number}* ya está en camino a tu domicilio.\n\n🚚 *Transportadora:* {carrier_name}\n🔍 *N° de Guía / Tracking:* {tracking_number}\n💵 *Monto exacto en efectivo (o QR Simple):* Bs {total_amount_bob}\n\nPor favor ten a mano el monto o tu app de banco para pagar con QR al cartero.",
        ],
        'novedad_alert' => [
            'name' => 'Novedad en la Entrega (Intento Fallido)',
            'body' => "Hola *{customer_name}*, el repartidor de *{carrier_name}* intentó entregar tu pedido *#{order_number}* pero reportó la siguiente novedad:\n\n⚠️ *Motivo:* {novedad_reason}\n\n¿Deseas reprogramar la entrega para hoy o mañana? Por favor responde a este mensaje para coordinar la hora exacta.",
        ],
        'order_delivered' => [
            'name' => 'Pedido Entregado con Éxito',
            'body' => "¡Felicitaciones *{customer_name}*! 🎉 Tu pedido *#{order_number}* ha sido entregado.\n\nEsperamos que disfrutes tu compra. Si tienes alguna consulta o requieres soporte, responde directamente a este chat.",
        ],
    ];

    /**
     * Formatea el número boliviano para WhatsApp (+591XXXXXXXX).
     */
    public function formatBoliviaPhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '591')) {
            return '+' . $clean;
        }
        return '+591' . ltrim($clean, '0');
    }

    /**
     * Genera enlace 'https://wa.me/' para abrir WhatsApp Web o Móvil directamente con el cliente.
     */
    public function buildWhatsAppLink(Order $order, string $templateKey): string
    {
        $message = $this->renderMessage($order, $templateKey);
        $phone = preg_replace('/[^0-9]/', '', $this->formatBoliviaPhone($order->customer_phone));
        return "https://wa.me/{$phone}?text=" . rawurlencode($message);
    }

    /**
     * Reemplaza variables dinámicas en el mensaje según los datos de la orden.
     */
    public function renderMessage(Order $order, string $templateKey): string
    {
        $template = self::TEMPLATES[$templateKey] ?? self::TEMPLATES['order_confirmed'];
        $text = $template['body'];

        $carrierName = $order->carrier->name ?? 'DEX Bolivia Express';
        $itemsSummary = $order->items && $order->items->count() > 0
            ? $order->items->map(fn($item) => ($item->product->name ?? 'Producto') . " (x{$item->quantity})")->join(', ')
            : 'Productos seleccionados';

        $replaces = [
            '{customer_name}'       => $order->customer_name,
            '{order_number}'        => $order->order_number,
            '{items_summary}'       => $itemsSummary,
            '{total_amount_bob}'    => number_format((float)$order->total_amount_bob, 2),
            '{shipping_city}'       => $order->shipping_city,
            '{shipping_department}' => $order->shipping_department,
            '{carrier_name}'        => $carrierName,
            '{tracking_number}'     => $order->tracking_number ?? 'PENDIENTE',
            '{novedad_reason}'      => $order->novedad_reason ?? 'No se pudo contactar al cliente',
        ];

        return str_replace(array_keys($replaces), array_values($replaces), $text);
    }

    /**
     * Dispara y audita el envío de notificación transaccional de WhatsApp.
     */
    public function sendOrderNotification(Order $order, string $templateKey): NotificationLog
    {
        $recipient = $this->formatBoliviaPhone($order->customer_phone);
        $body = $this->renderMessage($order, $templateKey);
        $templateName = self::TEMPLATES[$templateKey]['name'] ?? 'Notificación WhatsApp';

        // Auditoría en la BD con NotificationLog
        $log = NotificationLog::create([
            'tenant_id'      => $order->tenant_id,
            'user_id'        => $order->dropshipper_id,
            'channel'        => 'whatsapp',
            'event_type'     => $templateKey,
            'recipient'      => $recipient,
            'subject'        => $templateName,
            'body'           => $body,
            'status'         => 'sent',
            'metadata'       => [
                'provider_gateway' => 'WhatsApp Cloud API Bolivia (+591)',
                'order_number'     => $order->order_number,
                'tracking_number'  => $order->tracking_number,
                'dispatched_at'    => now()->toIso8601String(),
            ],
            'reference_type' => 'order',
            'reference_id'   => $order->id,
        ]);

        Log::info("WhatsApp enviado exitosamente a {$recipient} para pedido #{$order->order_number}");

        return $log;
    }
}
