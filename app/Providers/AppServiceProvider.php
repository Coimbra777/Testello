<?php

namespace App\Providers;

use App\Services\FreightImportService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar FreightImportService como singleton
        $this->app->singleton(FreightImportService::class, function ($app) {
            return new FreightImportService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
