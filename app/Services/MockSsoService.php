<?php

namespace App\Services;

use App\Services\Contracts\SsoServiceInterface;

class MockSsoService implements SsoServiceInterface
{
    /**
     * Authenticate against mock user accounts for development & testing.
     */
    public function authenticate(string $username, string $password): array
    {
        if ($password === 'invalid' || $password === 'wrong-password') {
            return [
                'success' => false,
                'error' => 'Kredensial SSO tidak sah atau akun tidak ditemukan.',
            ];
        }

        $normalized = strtolower(trim($username));
        $role = match (true) {
            str_contains($normalized, 'supervisor') || str_contains($normalized, 'atasan') => 'supervisor',
            str_contains($normalized, 'ithead') || str_contains($normalized, 'kabag') => 'it_head',
            str_contains($normalized, 'kadiv') => 'kadiv_it',
            str_contains($normalized, 'direksi') => 'direksi',
            str_contains($normalized, 'dev') || str_contains($normalized, 'developer') => 'it_developer',
            str_contains($normalized, 'admin') => 'admin',
            default => 'requester',
        };

        $department = match ($role) {
            'it_head', 'it_developer', 'kadiv_it' => 'Divisi Teknologi Informasi',
            'direksi' => 'Dewan Direksi',
            default => 'Operasional Bisnis',
        };

        $ssoId = 'SSO-' . strtoupper(substr(md5($normalized), 0, 8));
        $knownNames = [
            'requester@kantor.internal' => 'Ahmad Fauzi (Requester)',
            'supervisor@kantor.internal' => 'Budi Santoso (Supervisor)',
            'kabagit@kantor.internal' => 'Hendra Wijaya (Kabag IT)',
            'kadivit@kantor.internal' => 'Irwan Setiawan (Kadiv IT & Ops)',
            'direksi@kantor.internal' => 'Dr. Bambang Hartono (Direksi)',
            'dev1@kantor.internal' => 'Rian Pratama (Dev 1)',
            'dev2@kantor.internal' => 'Doni Kusuma (Dev 2)',
        ];
        $name = $knownNames[$normalized] ?? ucwords(str_replace(['@kantor.com', '@kantor.internal', '.', '_'], [' ', ' ', ' ', ' '], $normalized));
        if (empty(trim($name))) {
            $name = 'Mock User ' . ucfirst($role);
        }

        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode([
            'sub' => $ssoId,
            'name' => $name,
            'email' => str_contains($normalized, '@') ? $normalized : "{$normalized}@kantor.com",
            'role' => $role,
            'department' => $department,
            'iss' => 'sso.kantor.com',
            'iat' => time(),
            'exp' => time() + 3600 * 8,
        ]));
        $signature = hash_hmac('sha256', "{$header}.{$payload}", 'mock-secret');
        $token = "{$header}.{$payload}.{$signature}";

        return [
            'success' => true,
            'token' => $token,
            'user' => [
                'sso_id' => $ssoId,
                'name' => $name,
                'email' => str_contains($normalized, '@') ? $normalized : "{$normalized}@kantor.com",
                'role' => $role,
                'department' => $department,
            ],
        ];
    }

    /**
     * Validate mock JWT token.
     */
    public function validateToken(string $jwtToken): array
    {
        $parts = explode('.', $jwtToken);
        if (count($parts) !== 3) {
            return ['valid' => false, 'error' => 'Format token JWT tidak sah.'];
        }

        $payload = json_decode(base64_decode($parts[1]), true);
        if (! is_array($payload)) {
            return ['valid' => false, 'error' => 'Klaim payload JWT tidak dapat dibaca.'];
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return ['valid' => false, 'error' => 'Token JWT SSO telah kedaluwarsa.'];
        }

        return [
            'valid' => true,
            'claims' => $payload,
        ];
    }
}
