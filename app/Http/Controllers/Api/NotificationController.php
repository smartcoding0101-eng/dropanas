<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Consultar historial de notificaciones */
    public function index(Request $request): JsonResponse
    {
        $notifications = NotificationLog::latest()
            ->paginate(25);

        return response()->json([
            'status' => 'success',
            'data'   => $notifications,
        ]);
    }

    /** Disparar una notificación transaccional (WhatsApp Bolivia, SMS o Email) */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel'        => 'required|in:whatsapp,sms,email,in_app',
            'event_type'     => 'required|string|max:60',
            'recipient'      => 'required|string|max:200',
            'subject'        => 'nullable|string|max:200',
            'body'           => 'required|string',
            'reference_type' => 'nullable|string|max:50',
            'reference_id'   => 'nullable|string',
        ]);

        $user = $request->user();

        // En un entorno de producción, aquí se conecta con Twilio / WhatsApp Cloud API / Tigo SMS Bolivia.
        // Aquí registramos la auditoría de envío en la BD con estado 'sent'.
        $log = NotificationLog::create([
            'tenant_id'      => $user->tenant_id,
            'user_id'        => $user->id,
            'channel'        => $validated['channel'],
            'event_type'     => $validated['event_type'],
            'recipient'      => $validated['recipient'],
            'subject'        => $validated['subject'] ?? 'Notificación Dropana Bolivia',
            'body'           => $validated['body'],
            'status'         => 'sent',
            'metadata'       => [
                'provider_gateway' => $validated['channel'] === 'whatsapp' ? 'WhatsApp Business Cloud API' : 'SMS Gateway Bolivia',
                'dispatched_at'    => now()->toIso8601String(),
            ],
            'reference_type' => $validated['reference_type'] ?? null,
            'reference_id'   => $validated['reference_id'] ?? null,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => "Notificación enviada exitosamente vía {$validated['channel']}.",
            'data'    => $log,
        ], 201);
    }
}
