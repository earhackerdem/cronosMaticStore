<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Forzar HTTPS en producción para evitar Mixed Content
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Si tienes la variable FORCE_HTTPS configurada
        if (config('app.force_https', false)) {
            URL::forceScheme('https');
        }
    }
}
