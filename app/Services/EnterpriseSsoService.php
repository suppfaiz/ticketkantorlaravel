<?php

namespace App\Services;

use App\Services\Contracts\SsoServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnterpriseSsoService implements SsoServiceInterface
{
    protected string $apiUrl;

    protected string $clientId;

    protected string $clientSecret;

    protected ?string $jwtPublicKey;

    public function __construct()
    {
        $this->apiUrl = rtrim((string) config('services.sso.api_url', 'https://sso.kantor.com/api/v1'), '/');
        $this->clientId = (string) config('services.sso.client_id', 'it-helpdesk-client');
        $this->clientSecret = (string) config('services.sso.client_secret', '');
        $this->jwtPublicKey = config('services.sso.jwt_public_key');
    }

    /**
     * Authenticate user credentials against corporate SSO endpoint.
     */
    public function authenticate(string $username, string $password): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-Client-Id' => $this->clientId,
                    'Accept' => 'application/json',
                ])
                ->post("{$this->apiUrl}/auth/login", [
                    'username' => $username,
                    'password' => $password,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['token'] ?? null;

                if (! $token) {
                    return ['success' => false, 'error' => 'Respon SSO tidak menyertakan token autentikasi.'];
                }

                $validation = $this->validateToken($token);
                if (! $validation['valid']) {
                    return ['success' => false, 'error' => $validation['error'] ?? 'Token JWT tidak valid.'];
                }

                return [
                    'success' => true,
                    'token' => $token,
                    'user' => $data['user'] ?? $validation['claims'],
                ];
            }

            return [
                'success' => false,
                'error' => 'Kredensial SSO tidak sah atau akun tidak ditemukan.',
            ];
        } catch (\Throwable $e) {
            Log::error('SSO Authentication request failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Gagal terhubung ke layanan server SSO perusahaan.',
            ];
        }
    }

    /**
     * Validate JWT token claims and signature.
     */
    public function validateToken(string $jwtToken): array
    {
        $parts = explode('.', $jwtToken);
        if (count($parts) !== 3) {
            return ['valid' => false, 'error' => 'Format token JWT tidak sah.'];
        }

        $headerJson = $this->base64UrlDecode($parts[0]);
        $payloadJson = $this->base64UrlDecode($parts[1]);
        $signature = $this->base64UrlDecode($parts[2]);

        if ($headerJson === false || $payloadJson === false || $signature === false) {
            return ['valid' => false, 'error' => 'Encoding token JWT tidak sah.'];
        }

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (! is_array($header) || ! is_array($payload)) {
            return ['valid' => false, 'error' => 'Header atau klaim JWT tidak dapat dibaca.'];
        }

        $expectedAlgorithm = (string) config('services.sso.jwt_algorithm', 'RS256');
        if ($expectedAlgorithm !== 'RS256' || ($header['alg'] ?? null) !== $expectedAlgorithm) {
            return ['valid' => false, 'error' => 'Algorithm token JWT tidak diizinkan.'];
        }

        $publicKey = str_replace('\\n', "\n", trim((string) $this->jwtPublicKey));
        if ($publicKey === '') {
            return ['valid' => false, 'error' => 'Public key JWT SSO belum dikonfigurasi.'];
        }

        $verification = openssl_verify(
            "{$parts[0]}.{$parts[1]}",
            $signature,
            $publicKey,
            OPENSSL_ALGO_SHA256
        );

        if ($verification !== 1) {
            return ['valid' => false, 'error' => 'Signature token JWT tidak sah.'];
        }

        $now = time();
        if (! isset($payload['exp']) || ! is_numeric($payload['exp']) || (int) $payload['exp'] <= $now) {
            return ['valid' => false, 'error' => 'Token JWT SSO telah kedaluwarsa.'];
        }

        if (isset($payload['nbf']) && is_numeric($payload['nbf']) && (int) $payload['nbf'] > $now) {
            return ['valid' => false, 'error' => 'Token JWT SSO belum berlaku.'];
        }

        $expectedIssuer = config('services.sso.jwt_issuer');
        if ($expectedIssuer && ($payload['iss'] ?? null) !== $expectedIssuer) {
            return ['valid' => false, 'error' => 'Issuer token JWT tidak sesuai.'];
        }

        $expectedAudience = config('services.sso.jwt_audience');
        $tokenAudience = $payload['aud'] ?? null;
        if ($expectedAudience && ! (
            $tokenAudience === $expectedAudience ||
            (is_array($tokenAudience) && in_array($expectedAudience, $tokenAudience, true))
        )) {
            return ['valid' => false, 'error' => 'Audience token JWT tidak sesuai.'];
        }

        return [
            'valid' => true,
            'claims' => $payload,
        ];
    }

    private function base64UrlDecode(string $value): string|false
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}
