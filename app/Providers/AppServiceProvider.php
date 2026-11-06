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
            $view->with('bcbOfficialRate', $rate);
        });
    }
}
