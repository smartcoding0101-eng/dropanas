<?php

namespace App\Console\Commands;

use App\Services\Currency\BcbExchangeRateService;
use Illuminate\Console\Command;

class SyncExchangeRateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange-rate:sync {--tenant= : ID opcional del tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza la cotización oficial del Banco Central de Bolivia (BCB) en tiempo real.';

    /**
     * Execute the console command.
     */
    public function handle(BcbExchangeRateService $service): int
    {
        $this->info('Consultando fuentes oficiales del Banco Central de Bolivia...');

        try {
            $tenantId = $this->option('tenant');
            $rate = $service->syncRate($tenantId);

            $this->table(
                ['Par', 'Compra', 'Venta', 'Fuente Oficial', 'Hora Bolivia'],
                [
                    [
                        $rate->currency_pair,
                        $rate->buy_rate,
                        $rate->sell_rate,
                        $rate->official_source,
                        $rate->fetched_at->toDateTimeString(),
                    ]
                ]
            );

            $this->info('✓ Tasa sincronizada y guardada en caché con éxito.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error al sincronizar tipo de cambio: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
