<?php

namespace App\Providers;

use App\Services\Contracts\LoaServiceInterface;
use App\Services\Contracts\SsoServiceInterface;
use App\Services\EnterpriseLoaService;
use App\Services\EnterpriseSsoService;
use App\Services\LocalLoaService;
use App\Services\LocalSsoService;
use App\Services\MockLoaService;
use App\Services\MockSsoService;
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
        $this->app->bind(SsoServiceInterface::class, function () {
            return match (config('services.sso.driver')) {
                'local' => new LocalSsoService,
                'api', 'enterprise' => new EnterpriseSsoService,
                'mock' => new MockSsoService,
                default => throw new \InvalidArgumentException('SSO_DRIVER tidak valid.'),
            };
        });

        $this->app->singleton(LoaServiceInterface::class, function () {
            return match (config('services.loa.driver')) {
                'local' => new LocalLoaService,
                'api', 'enterprise' => new EnterpriseLoaService,
                'mock' => new MockLoaService,
                default => throw new \InvalidArgumentException('LOA_DRIVER tidak valid.'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $ssoDriver = (string) config('services.sso.driver');
        $loaDriver = (string) config('services.loa.driver');

        if (! in_array($ssoDriver, ['local', 'api', 'enterprise', 'mock'], true)) {
            throw new \RuntimeException("SSO_DRIVER '{$ssoDriver}' tidak didukung.");
        }

        if (! in_array($loaDriver, ['local', 'api', 'enterprise', 'mock'], true)) {
            throw new \RuntimeException("LOA_DRIVER '{$loaDriver}' tidak didukung.");
        }

        if ($this->app->environment('production')) {
            if ($ssoDriver === 'mock' || $loaDriver === 'mock') {
                throw new \RuntimeException('Driver mock tidak boleh digunakan pada environment production.');
            }

            if (in_array($ssoDriver, ['api', 'enterprise'], true)) {
                foreach (['api_url', 'client_id', 'client_secret', 'jwt_public_key'] as $key) {
                    if (blank(config("services.sso.{$key}"))) {
                        throw new \RuntimeException("Konfigurasi SSO {$key} wajib diisi untuk driver API.");
                    }
                }
            }

            if (in_array($loaDriver, ['api', 'enterprise'], true)) {
                foreach (['api_url', 'api_key', 'webhook_secret'] as $key) {
                    if (blank(config("services.loa.{$key}"))) {
                        throw new \RuntimeException("Konfigurasi LOA {$key} wajib diisi untuk driver API.");
                    }
                }
            }
        }

        // Compatibility for cPanel shared hosting MySQL / MariaDB utf8mb4 index length limits
        Schema::defaultStringLength(191);

        // Support HTTPS behind cPanel AutoSSL / Cloudflare / LiteSpeed proxies
        if (
            config('app.env') === 'production' &&
            (config('app.force_https') || request()->isSecure())
        ) {
            URL::forceScheme('https');
        }
    }
}
