<?php

use App\Http\Middleware\ConfiguredTrustProxies;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VerifyLoaWebhookSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(SecurityHeaders::class);
        $middleware->replace(TrustProxies::class, ConfiguredTrustProxies::class);
        $middleware->trustHosts(
            at: fn () => ['^'.preg_quote((string) parse_url(config('app.url'), PHP_URL_HOST)).'$'],
            subdomains: false,
        );
        $middleware->web(append: [EnsureActiveUser::class]);
        $middleware->alias([
            'verify.loa.signature' => VerifyLoaWebhookSignature::class,
            'admin.only' => EnsureAdminRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(fn (Response $response) => SecurityHeaders::apply($response));
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Sesi keamanan Anda telah berakhir. Silakan muat ulang halaman.',
                ], 419);
            }

            return redirect()
                ->back()
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->with('error', 'Sesi formulir Anda sempat kedaluwarsa karena halaman lama tidak aktif. Silakan kirim ulang.');
        });
    })->create();
