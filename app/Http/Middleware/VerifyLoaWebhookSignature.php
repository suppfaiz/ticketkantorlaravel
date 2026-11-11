<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyLoaWebhookSignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Signature-SHA256') ?? $request->header('X-Signature');
        $secret = config('services.loa.webhook_secret', 'loa-webhook-secret-key');

        // Allow test pass if testing or mock driver without strict secret
        if (config('services.loa.driver') === 'mock' && ($signature === 'mock-signature' || empty($signature))) {
            return $next($request);
        }

        if (! $signature) {
            return response()->json([
                'error' => 'Header tanda tangan webhook X-Signature-SHA256 tidak ditemukan.',
            ], 401);
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            return response()->json([
                'error' => 'Tanda tangan digital webhook tidak sah.',
            ], 403);
        }

        return $next($request);
    }
}
