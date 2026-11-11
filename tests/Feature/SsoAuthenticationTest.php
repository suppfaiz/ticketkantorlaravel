<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Contracts\SsoServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SsoAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_authenticate_via_sso(): void
    {
        $response = $this->post('/login', [
            'email' => 'karyawan@kantor.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::where('email', 'karyawan@kantor.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('requester', $user->role);
        $this->assertNotNull($user->sso_id);
    }

    public function test_user_cannot_authenticate_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'karyawan@kantor.com',
            'password' => 'invalid',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_sso_service_token_validation(): void
    {
        $ssoService = app(SsoServiceInterface::class);

        // Invalid token format
        $invalidResult = $ssoService->validateToken('not-a-valid-jwt');
        $this->assertFalse($invalidResult['valid']);

        // Authenticate to get a valid token
        $auth = $ssoService->authenticate('karyawan@kantor.com', 'password');
        $this->assertTrue($auth['success']);
        $this->assertNotEmpty($auth['token']);

        // Validate the generated token
        $validation = $ssoService->validateToken($auth['token']);
        $this->assertTrue($validation['valid']);
        $this->assertEquals('sso.kantor.com', $validation['claims']['iss']);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $this->post('/login', [
            'email' => 'karyawan@kantor.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        $response = $this->post('/logout');
        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
