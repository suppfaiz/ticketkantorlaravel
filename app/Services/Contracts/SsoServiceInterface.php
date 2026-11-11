<?php

namespace App\Services\Contracts;

interface SsoServiceInterface
{
    /**
     * Authenticate user credentials against the corporate SSO API.
     *
     * @return array{success: bool, user?: array, token?: string, error?: string}
     */
    public function authenticate(string $username, string $password): array;

    /**
     * Validate a corporate SSO JWT token server-side.
     *
     * @return array{valid: bool, claims?: array, error?: string}
     */
    public function validateToken(string $jwtToken): array;
}
