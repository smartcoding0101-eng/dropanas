<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Services\Currency\BcbExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    /**
     * Consulta la tasa oficial vigente del Banco Central de Bolivia (USD/BOB).
     */
    public function current(BcbExchangeRateService $service): JsonResponse
    {
        $sellRate = $service->getCurrentSellRate();
        $latestRecord = ExchangeRate::where('currency_pair', 'USD/BOB')
            ->latest('fetched_at')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'currency_pair' => 'USD/BOB',
                'buy_rate' => $latestRecord ? (float) $latestRecord->buy_rate : round($sellRate - 0.10, 4),
                'sell_rate' => $sellRate,
                'official_source' => $latestRecord->official_source ?? 'Banco Central de Bolivia (Oficial)',
                'last_updated_bolivia' => $latestRecord ? $latestRecord->fetched_at->toDateTimeString() : now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Forzar sincronización bajo demanda o configurar tasa manual (requiere permisos de administrador).
     */
    public function sync(Request $request, BcbExchangeRateService $service): JsonResponse
    {
        if ($request->filled('sell_rate')) {
            $validated = $request->validate([
                'sell_rate' => 'required|numeric|min:1|max:100',
                'buy_rate'  => 'nullable|numeric|min:1|max:100',
            ]);
            $user = $request->user();
            $rate = $service->setManualRate(
                (float) $validated['sell_rate'],
                isset($validated['buy_rate']) ? (float) $validated['buy_rate'] : null,
                $user?->tenant_id,
                $user?->name
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Tipo de cambio configurado manualmente.',
                'data' => $rate,
            ]);
        }

        $rate = $service->syncRate();

        return response()->json([
            'status' => 'success',
            'message' => 'Tipo de cambio del BCB sincronizado y actualizado en caché.',
            'data' => $rate,
        ]);
    }
}

