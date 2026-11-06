<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Services\Notifications\WhatsAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppNotificationController extends Controller
{
    public function __construct(
        protected WhatsAppNotificationService $whatsAppService
    ) {}

    /**
     * Centro de Notificaciones & Auditoría de Mensajes WhatsApp
     */
    public function index(Request $request): View
    {
        $logs = NotificationLog::where('channel', 'whatsapp')
            ->latest()
            ->paginate(20);

        $templates = WhatsAppNotificationService::TEMPLATES;

        return view('notifications.whatsapp', compact('logs', 'templates'));
    }

    /**
     * Disparo manual o reintento de envío de WhatsApp para una orden específica
     */
    public function send(Request $request, string $orderId): RedirectResponse
    {
        $request->validate([
            'template' => 'required|string|in:order_confirmed,order_dispatched,novedad_alert,order_delivered',
        ]);

        $order = Order::with(['carrier', 'items.product'])->findOrFail($orderId);
        $this->whatsAppService->sendOrderNotification($order, $request->input('template'));

        return back()->with('success', "Mensaje WhatsApp ({$request->input('template')}) enviado a {$order->customer_phone} para la orden #{$order->order_number}.");
    }

    /**
     * Endpoint API para generar el wa.me deep link para que el courier/operador abra chat
     */
    public function getLink(Request $request, string $orderId): JsonResponse
    {
        $template = $request->get('template', 'order_dispatched');
        $order = Order::with(['carrier', 'items.product'])->findOrFail($orderId);

        $link = $this->whatsAppService->buildWhatsAppLink($order, $template);
        $message = $this->whatsAppService->renderMessage($order, $template);

        return response()->json([
            'status'  => 'success',
            'link'    => $link,
            'message' => $message,
        ]);
    }
}
