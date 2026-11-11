<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\Contracts\SsoServiceInterface::class, function () {
            return config('services.sso.driver') === 'enterprise' || config('services.sso.driver') === 'api'
                ? new \App\Services\EnterpriseSsoService()
                : new \App\Services\MockSsoService();
        });

        $this->app->singleton(\App\Services\Contracts\LoaServiceInterface::class, function () {
            return config('services.loa.driver') === 'enterprise' || config('services.loa.driver') === 'api'
                ? new \App\Services\EnterpriseLoaService()
                : new \App\Services\MockLoaService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Compatibility for cPanel shared hosting MySQL / MariaDB utf8mb4 index length limits
        Schema::defaultStringLength(191);

        // Support HTTPS behind cPanel AutoSSL / Cloudflare / LiteSpeed proxies
        if (
            config('app.env') === 'production' &&
            (env('FORCE_HTTPS', false) || request()->isSecure() || request()->header('X-Forwarded-Proto') === 'https')
        ) {
            URL::forceScheme('https');
        }
    }
}
