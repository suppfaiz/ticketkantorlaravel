<?php

namespace Tests\Feature;

use App\Models\MeetingMinutes;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_cover_html_redirects_and_missing_routes(): void
    {
        foreach (['/login' => 200, '/' => 302, '/route-that-does-not-exist' => 404] as $path => $status) {
            $response = $this->get($path)->assertStatus($status);

            $response->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'")
                ->assertHeaderMissing('X-Powered-By');
        }

        $this->get('/login')->assertOk()->assertDontSee('fonts.googleapis.com');
    }

    public function test_security_headers_cover_rejected_oversized_requests(): void
    {
        $this->withServerVariables(['CONTENT_LENGTH' => '999999999'])
            ->post('/login')
            ->assertStatus(413)
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");
    }

    public function test_security_headers_cover_maintenance_responses(): void
    {
        foreach ([['status' => 503], ['status' => 503, 'template' => 'Maintenance']] as $payload) {
            $this->app->instance(MaintenanceMode::class, new class($payload) implements MaintenanceMode
            {
                public function __construct(private array $payload) {}

                public function activate(array $payload): void {}

                public function deactivate(): void {}

                public function active(): bool
                {
                    return true;
                }

                public function data(): array
                {
                    return $this->payload;
                }
            });

            $this->get('/login')->assertStatus(503)
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");
        }
    }

    public function test_existing_session_is_revoked_after_account_deactivation(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_REQUESTER, 'is_active' => true]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $user->update(['is_active' => false]);

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_forwarded_host_is_not_used_for_redirects_or_login_form(): void
    {
        $headers = [
            'X-Forwarded-Host' => 'attacker.example',
            'X-Forwarded-Proto' => 'https',
        ];

        $this->withHeaders($headers)->get('/dashboard')
            ->assertRedirect('http://localhost/login');

        $this->withHeaders($headers)->get('/login')
            ->assertOk()
            ->assertSee('action="http://localhost/login"', false)
            ->assertDontSee('attacker.example');
    }

    public function test_minutes_html_is_safe_on_detail_and_print_while_formatting_remains(): void
    {
        $requester = User::factory()->create(['role' => User::ROLE_REQUESTER]);
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-SECURITY-0001',
            'requester_id' => $requester->id,
            'category' => 'Software',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'title' => 'Notulensi proyek internal',
            'description' => 'Permintaan pekerjaan internal yang perlu didokumentasikan.',
            'status' => Ticket::STATUS_WAITING_MINUTES_APPROVAL,
        ]);

        MeetingMinutes::create([
            'ticket_id' => $ticket->id,
            'author_id' => $requester->id,
            'meeting_date' => now(),
            'notes' => '<p onmouseover="alert(1)"><strong>Aman</strong> <a href="javascript:alert(2)">tautan</a> <a href="&#x6a;avascript:alert(5)">tautan kedua</a> <a href="https://example.com/info">panduan</a></p><img src=x onerror="alert(4)"><script>alert(3)</script>',
            'status' => MeetingMinutes::STATUS_WAITING_APPROVAL,
        ]);

        foreach (['tickets.show', 'tickets.print'] as $route) {
            $this->actingAs($requester)->get(route($route, $ticket))
                ->assertOk()
                ->assertSee('<strong>Aman</strong>', false)
                ->assertSee('href="https://example.com/info"', false)
                ->assertDontSee('onmouseover', false)
                ->assertDontSee('onerror', false)
                ->assertDontSee('javascript:alert', false)
                ->assertDontSee('<script>alert(3)</script>', false);
        }
    }

    public function test_csv_export_neutralizes_formula_cells_and_preserves_normal_titles(): void
    {
        $requester = User::factory()->create(['role' => User::ROLE_REQUESTER]);

        foreach (['=1+1 as formula', "\t@SUM(1,1) as formula", 'Judul tiket biasa'] as $index => $title) {
            Ticket::create([
                'ticket_number' => 'TICK-SECURITY-000'.($index + 2),
                'requester_id' => $requester->id,
                'category' => 'Software',
                'title' => $title,
                'description' => 'Permintaan pekerjaan internal yang perlu didokumentasikan.',
                'status' => Ticket::STATUS_DRAFT,
            ]);
        }

        $csv = $this->actingAs($requester)->get(route('tickets.export'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString("'=1+1 as formula", $csv);
        $this->assertStringContainsString("'\t@SUM(1,1) as formula", $csv);
        $this->assertStringContainsString('Judul tiket biasa', $csv);
        $this->assertStringNotContainsString('"=1+1 as formula"', $csv);
    }
}
