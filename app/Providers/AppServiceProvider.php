<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $service = app(\App\Services\Currency\BcbExchangeRateService::class);
            $rate = $service->getCurrentSellRate();
            $record = $service->getLatestRecord();
            $view->with('bcbOfficialRate', $rate);
            $view->with('bcbBuyRate', $record ? (float) $record->buy_rate : round($rate - 0.10, 2));
            $view->with('bcbRateSource', $record ? $record->official_source : 'Banco Central de Bolivia (www.bcb.gob.bo)');
        });
    }
}

