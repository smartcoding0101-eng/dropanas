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
                'buy_rate' => $latestRecord ? (float) $latestRecord->buy_rate : 6.8600,
                'sell_rate' => $sellRate,
                'official_source' => $latestRecord->official_source ?? 'Banco Central de Bolivia (Oficial)',
                'last_updated_bolivia' => $latestRecord ? $latestRecord->fetched_at->toDateTimeString() : now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Forzar sincronización bajo demanda (requiere permisos de administrador).
     */
    public function sync(BcbExchangeRateService $service): JsonResponse
    {
        $rate = $service->syncRate();

        return response()->json([
            'status' => 'success',
            'message' => 'Tipo de cambio del BCB sincronizado y actualizado en caché.',
            'data' => $rate,
        ]);
    }
}
