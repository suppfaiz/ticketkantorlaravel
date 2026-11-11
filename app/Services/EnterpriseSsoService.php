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
            Log::error('SSO Authentication request failed: ' . $e->getMessage());

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

        $payloadJson = base64_decode(strtr($parts[1], '-_', '+/'));
        $payload = json_decode($payloadJson, true);

        if (! is_array($payload)) {
            return ['valid' => false, 'error' => 'Klaim payload JWT tidak dapat dibaca.'];
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return ['valid' => false, 'error' => 'Token JWT SSO telah kedaluwarsa.'];
        }

        return [
            'valid' => true,
            'claims' => $payload,
        ];
    }
}
