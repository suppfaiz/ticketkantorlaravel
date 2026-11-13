<?php

namespace Tests\Feature;

use App\Models\ExternalLoaTransaction;
use App\Models\Ticket;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\Contracts\LoaServiceInterface;
use App\Services\Contracts\SsoServiceInterface;
use App\Services\LocalLoaService;
use App\Services\LocalSsoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocalModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_driver_authenticates_existing_active_user_without_http_request(): void
    {
        config(['services.sso.driver' => 'local']);
        $this->app->forgetInstance(SsoServiceInterface::class);
        Http::fake();

        $user = User::factory()->create([
            'email' => 'local.user@kantor.internal',
            'password' => Hash::make('local-password'),
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'local-password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        Http::assertNothingSent();
        $this->assertInstanceOf(LocalSsoService::class, app(SsoServiceInterface::class));
    }

    public function test_local_driver_rejects_invalid_password_and_inactive_user(): void
    {
        config(['services.sso.driver' => 'local']);
        $this->app->forgetInstance(SsoServiceInterface::class);

        $active = User::factory()->create([
            'email' => 'active@kantor.internal',
            'password' => Hash::make('correct-password'),
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => $active->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('role="alertdialog"', false);

        $inactive = User::factory()->create([
            'email' => 'inactive@kantor.internal',
            'password' => Hash::make('correct-password'),
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => $inactive->email,
            'password' => 'correct-password',
        ])->assertSessionHasErrors('account_inactive');
        $this->assertGuest();

        $this->get('/login')
            ->assertOk()
            ->assertSee('role="alertdialog"', false)
            ->assertSee('Akun dinonaktifkan')
            ->assertSee('Hubungi administrator');
    }

    public function test_admin_can_deactivate_user_without_changing_password(): void
    {
        config(['services.sso.driver' => 'local']);
        $this->app->forgetInstance(SsoServiceInterface::class);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create([
            'role' => User::ROLE_REQUESTER,
            'password' => Hash::make('original-password'),
            'is_active' => true,
        ]);
        $originalHash = $user->password;

        $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'password' => 'browser-autofilled-password',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertFalse($user->fresh()->is_active);
        $this->assertSame($originalHash, $user->fresh()->password);

        $this->post('/logout')->assertRedirect('/');
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'original-password',
        ])->assertSessionHasErrors('account_inactive');
        $this->assertGuest();
    }

    public function test_admin_can_explicitly_reset_user_password(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create([
            'role' => User::ROLE_REQUESTER,
            'password' => Hash::make('original-password'),
        ]);

        $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => '1',
            'reset_password' => '1',
            'password' => 'new-password',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_local_loa_records_pending_local_approval_without_http_request(): void
    {
        config(['services.loa.driver' => 'local']);
        $this->app->forgetInstance(LoaServiceInterface::class);
        Http::fake();

        $requester = User::factory()->create(['role' => User::ROLE_REQUESTER]);
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261111-0001',
            'requester_id' => $requester->id,
            'category' => 'Software',
            'title' => 'Pengujian LOA Lokal',
            'description' => 'Memastikan driver lokal tidak memanggil API eksternal.',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        $service = app(LoaServiceInterface::class);
        $transaction = $service->dispatch($ticket, ExternalLoaTransaction::LEVEL_SUPERVISOR, []);

        $this->assertInstanceOf(LocalLoaService::class, $service);
        $this->assertSame(ExternalLoaTransaction::SYNC_SYNCED, $transaction->sync_status);
        $this->assertSame(ExternalLoaTransaction::DECISION_WAITING, $transaction->decision);
        $this->assertStringStartsWith('LOCAL-LOA-', $transaction->external_reference_id);
        Http::assertNothingSent();
    }

    public function test_mock_drivers_are_rejected_in_production(): void
    {
        config([
            'services.sso.driver' => 'mock',
            'services.loa.driver' => 'mock',
        ]);
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Driver mock tidak boleh digunakan');

        (new AppServiceProvider($this->app))->boot();
    }

    public function test_api_authentication_failure_does_not_fall_back_to_local_password(): void
    {
        config(['services.sso.driver' => 'api']);

        $user = User::factory()->create([
            'email' => 'hybrid-risk@kantor.internal',
            'password' => Hash::make('valid-local-password'),
            'is_active' => true,
        ]);

        $this->app->instance(SsoServiceInterface::class, new class implements SsoServiceInterface
        {
            public function authenticate(string $username, string $password): array
            {
                return ['success' => false, 'error' => 'SSO tidak tersedia.'];
            }

            public function validateToken(string $jwtToken): array
            {
                return ['valid' => false];
            }
        });

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'valid-local-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
