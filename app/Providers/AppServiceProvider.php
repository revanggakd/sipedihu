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
        // Paksa semua URL yang di-generate Laravel menjadi https://
        // Wajib jika app berada di belakang reverse proxy (Coolify / Traefik / Nginx)
        // tanpa ini, redirect internal bisa turun ke http:// → mixed content error
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}