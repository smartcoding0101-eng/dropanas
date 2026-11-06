<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// =========================================================================
// DROPANA BOLIVIA — TAREAS PROGRAMADAS DE PRODUCCIÓN (BOLIVIA GMT-4)
// =========================================================================

// 1. Sincronización Diaria de la Tasa Oficial del Banco Central de Bolivia (BCB) a las 07:00 AM
\Illuminate\Support\Facades\Schedule::call(function () {
    /** @var \App\Services\Currency\BcbExchangeRateService $service */
    $service = app(\App\Services\Currency\BcbExchangeRateService::class);
    $service->syncRate();
    \Illuminate\Support\Facades\Log::info('Dropana Scheduler: Sincronización diaria de cotización BCB completada exitosamente.');
})->dailyAt('07:00')->timezone('America/La_Paz')->name('sync-bcb-rates');

// 2. Verificación de Alertas Preventivas de Stock Bajo en Megabodegas Eje Troncal (Cada 4 Horas)
\Illuminate\Support\Facades\Schedule::call(function () {
    $lowStockInventories = \App\Models\WarehouseInventory::where('stock_available', '<=', 10)
        ->with(['product', 'warehouse'])
        ->get();

    if ($lowStockInventories->isNotEmpty()) {
        \Illuminate\Support\Facades\Log::warning('Dropana Scheduler: Se detectaron ' . $lowStockInventories->count() . ' ítems con stock crítico en bodegas.');
    }
})->everyFourHours()->name('check-warehouse-low-stock');

// 3. Resumen y Cierre de Órdenes Entregadas para Liquidación (Lunes y Jueves a las 23:00)
\Illuminate\Support\Facades\Schedule::call(function () {
    $deliveredOrders = \App\Models\Order::where('status', 'delivered')
        ->whereNull('settled_at')
        ->update(['settled_at' => now()]);

    \Illuminate\Support\Facades\Log::info("Dropana Scheduler: Auto-liquidación completada para {$deliveredOrders} pedidos entregados.");
})->weeklyOn(1, '23:00')->timezone('America/La_Paz')->name('weekly-cod-settlement');

// 4. Auditoría Periódica de Conciliación Contable y Zero-Drift de Billeteras (Diario a las 02:00 AM)
\Illuminate\Support\Facades\Schedule::command('audit:reconcile-ledgers')
    ->dailyAt('02:00')
    ->timezone('America/La_Paz')
    ->name('audit-reconcile-ledgers');


