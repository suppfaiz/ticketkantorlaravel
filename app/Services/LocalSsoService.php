<?php

namespace App\Services;

use App\Models\User;
use App\Services\Contracts\SsoServiceInterface;
use Illuminate\Support\Facades\Hash;

class LocalSsoService implements SsoServiceInterface
{
    public function authenticate(string $username, string $password): array
    {
        $user = User::where('email', strtolower(trim($username)))->first();

        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'error' => 'Email atau password tidak sesuai.',
            ];
        }

        return [
            'success' => true,
            'user' => [
                'sso_id' => $user->sso_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'department' => $user->department,
            ],
        ];
    }

    public function validateToken(string $jwtToken): array
    {
        return [
            'valid' => false,
            'error' => 'Mode autentikasi lokal tidak menggunakan token SSO.',
        ];
    }
}
