<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Order;
use App\Models\ShippingManifest;
use App\Services\Logistics\Adapters\DexBoliviaAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShippingManifestController extends Controller
{
    /**
     * Lista manifiestos de despacho.
     */
    public function index(Request $request): JsonResponse
    {
        $manifests = ShippingManifest::with('carrier')
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $manifests,
        ]);
    }

    /**
     * Genera un manifiesto agrupando pedidos 'confirmed' listos para despacho.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['tenant_admin', 'provider'])) {
            return response()->json(['status' => 'error', 'message' => 'No autorizado.'], 403);
        }

        $validated = $request->validate([
            'carrier_id' => 'required|uuid|exists:carriers,id',
            'order_ids'  => 'required|array|min:1',
            'order_ids.*'=> 'uuid|exists:orders,id',
        ]);

        $carrier = Carrier::findOrFail($validated['carrier_id']);

        $orders = Order::whereIn('id', $validated['order_ids'])
            ->whereIn('status', ['confirmed', 'pending_confirmation'])
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No hay pedidos confirmados válidos para generar manifiesto.',
            ], 422);
        }

        return DB::transaction(function () use ($carrier, $orders, $user): JsonResponse {
            $manifestNumber = 'MAN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            $totalCod = $orders->sum('total_amount_bob');

            $manifest = ShippingManifest::create([
                'tenant_id' => $user->tenant_id,
                'carrier_id' => $carrier->id,
                'manifest_number' => $manifestNumber,
                'manifest_date' => now()->toDateString(),
                'total_packages' => $orders->count(),
                'total_cod_amount_bob' => $totalCod,
                'status' => 'generated',
            ]);

            // Asignar manifiesto y marcar pedidos como despachados
            foreach ($orders as $order) {
                $order->shipping_manifest_id = $manifest->id;
                $order->carrier_id = $carrier->id;
                $order->status = 'dispatched';
                $order->dispatched_at = now();
                if (empty($order->tracking_number)) {
                    $order->tracking_number = 'DEX-BO-' . $order->order_number;
                }
                $order->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => "Manifiesto {$manifestNumber} creado con {$orders->count()} paquetes.",
                'data' => [
                    'manifest' => $manifest->load('carrier'),
                    'orders_count' => $orders->count(),
                    'total_cod_amount_bob' => $totalCod,
                ],
            ], 201);
        });
    }

    /**
     * Descarga el CSV formateado del manifiesto para DEX Bolivia / Courier.
     */
    public function download(string $id)
    {
        $manifest = ShippingManifest::with('carrier')->findOrFail($id);
        $orders = Order::where('shipping_manifest_id', $manifest->id)->get();

        $adapter = new DexBoliviaAdapter();
        $csvContent = $adapter->generateManifest($manifest, $orders);

        $filename = "manifiesto_{$manifest->manifest_number}.csv";

        return response()->streamDownload(function () use ($csvContent) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM para compatibilidad con Excel
            echo $csvContent;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
