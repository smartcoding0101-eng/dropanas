<?php

namespace App\Services\Currency;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BcbExchangeRateService
{
    public const CACHE_KEY = 'exchange_rate:bcb:latest';
    public const CACHE_TTL_SECONDS = 21600; // 6 horas

    /**
     * Obtiene la tasa de venta actual (USD -> BOB).
     * Si no hay caché o si el registro en BD es obsoleto, ejecuta sincronización en vivo.
     */
    public function getCurrentSellRate(): float
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            $latest = null;
            try {
                $latest = ExchangeRate::where('currency_pair', 'USD/BOB')
                    ->where('is_official', true)
                    ->latest('fetched_at')
                    ->first();

                // Si no hay registro o si el último registro es de hace más de 12 horas, sincronizar en vivo
                if (!$latest || $latest->fetched_at->diffInHours(now()) >= 12) {
                    $synced = $this->syncRate();
                    return (float) $synced->sell_rate;
                }

                return (float) $latest->sell_rate;
            } catch (\Throwable $e) {
                Log::error('Error al obtener tasa BCB en getCurrentSellRate: ' . $e->getMessage());
                return ($latest && isset($latest->sell_rate)) ? (float) $latest->sell_rate : 12.0600;
            }
        });
    }

    /**
     * Sincroniza la tasa con fuentes oficiales del BCB (directa y espejos), guardando en BD y caché.
     */
    public function syncRate(?string $tenantId = null): ExchangeRate
    {
        $rateData = $this->fetchFromBcbOfficialDirect()
            ?? $this->fetchFromFacturaBo()
            ?? $this->fetchFromCucuBo()
            ?? $this->getFallbackFromDatabaseOrStatic();

        $record = ExchangeRate::create([
            'tenant_id'       => $tenantId,
            'currency_pair'   => 'USD/BOB',
            'buy_rate'        => $rateData['buy'],
            'sell_rate'       => $rateData['sell'],
            'official_source' => $rateData['source'],
            'is_official'     => true,
            'fetched_at'      => Carbon::now('America/La_Paz'),
        ]);

        Cache::put(self::CACHE_KEY, (float) $record->sell_rate, self::CACHE_TTL_SECONDS);

        return $record;
    }

    /**
     * Fuente 1 (PRIMARIA): Portal Oficial del Banco Central de Bolivia (www.bcb.gob.bo)
     * Consulta directa de la última cotización oficial publicada por el BCB.
     */
    protected function fetchFromBcbOfficialDirect(): ?array
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(6)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get('https://www.bcb.gob.bo/bcb_tco_publico_ultima_cotizacion.php');

            if ($response->successful()) {
                $html = $response->body();

                // Buscar filas de la tabla de cotización del BCB: <tr><td>...fecha...</td><td>12,06</td></tr>
                if (preg_match_all('/<tr>\s*<td>([^<]+)<\/td>\s*<td>([0-9]+[.,][0-9]+)<\/td>\s*<\/tr>/i', $html, $matches)) {
                    $count = count($matches[0]);
                    if ($count > 0) {
                        $lastIndex = $count - 1;
                        $fechaBcb = trim($matches[1][$lastIndex]);
                        $rateString = str_replace(',', '.', $matches[2][$lastIndex]);
                        $rate = (float) $rateString;

                        if ($rate > 0) {
                            return [
                                'buy'    => $rate,
                                'sell'   => $rate,
                                'source' => "Banco Central de Bolivia (Oficial www.bcb.gob.bo - {$fechaBcb})",
                            ];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Fallo consulta directa a www.bcb.gob.bo: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Fuente 2: api.factura.bo (Espejo Certificado con TCO en tiempo real del BCB)
     */
    protected function fetchFromFacturaBo(): ?array
    {
        try {
            $res = Http::timeout(5)->get('https://api.factura.bo/ExchangeRate');
            if ($res->successful()) {
                $data = $res->json();
                $datos = $data['datos'] ?? $data;
                $usdBob = (float) ($datos['usd_bob'] ?? $datos['SellRate'] ?? 0);
                if ($usdBob > 0) {
                    $vigencia = $datos['vigencia_usd'] ?? $datos['fecha_corte_usd'] ?? '';
                    return [
                        'buy'    => (float) ($datos['BuyRate'] ?? $usdBob),
                        'sell'   => $usdBob,
                        'source' => 'Banco Central de Bolivia (via api.factura.bo' . ($vigencia ? " - {$vigencia}" : '') . ')',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Fallo consulta a api.factura.bo: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Fuente 3: apibcb.cucu.bo (Espejo Secundario BCB)
     */
    protected function fetchFromCucuBo(): ?array
    {
        try {
            $res = Http::timeout(4)->get('https://apibcb.cucu.bo/api/v1/tc/oficial');
            if ($res->successful()) {
                $data = $res->json();
                $tc = $data['tc_oficial'] ?? $data;
                $val = (float) ($tc['venta'] ?? $tc['valor'] ?? $tc['compra'] ?? 0);
                if ($val > 0) {
                    return [
                        'buy'    => (float) ($tc['compra'] ?? $val),
                        'sell'   => $val,
                        'source' => 'Banco Central de Bolivia (via apibcb.cucu.bo)',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Fallo consulta a apibcb.cucu.bo: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Fallback: Si no hay conexión externa, usar el último valor guardado en BD antes de usar valor estático.
     */
    protected function getFallbackFromDatabaseOrStatic(): array
    {
        try {
            $latest = ExchangeRate::where('currency_pair', 'USD/BOB')
                ->where('is_official', true)
                ->latest('fetched_at')
                ->first();

            if ($latest) {
                return [
                    'buy'    => (float) $latest->buy_rate,
                    'sell'   => (float) $latest->sell_rate,
                    'source' => $latest->official_source . ' (Última cotización local guardada)',
                ];
            }
        } catch (\Throwable $e) {
            // Ignorar y pasar al estático
        }

        return [
            'buy'    => 12.0600,
            'sell'   => 12.0600,
            'source' => 'BCB Official Statutory Rate (Fallback Contingencia 12.06 BOB)',
        ];
    }
}
