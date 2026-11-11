<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExternalLoaTransaction;
use App\Models\Ticket;
use App\Models\User;
use App\Services\MockLoaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalLoaIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $requester;
    protected User $supervisor;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['name' => 'Operasional Bisnis', 'code' => 'OPS']);

        $this->supervisor = User::factory()->create([
            'role' => User::ROLE_SUPERVISOR,
            'department_id' => $dept->id,
        ]);

        $this->requester = User::factory()->create([
            'role' => User::ROLE_REQUESTER,
            'department_id' => $dept->id,
            'supervisor_id' => $this->supervisor->id,
        ]);
    }

    public function test_ticket_submission_triggers_external_loa_dispatch(): void
    {
        $this->actingAs($this->requester)->post('/tickets', [
            'title' => 'Permohonan Penggantian Akses Jaringan',
            'category' => 'network',
            'description' => 'Membutuhkan akses port VPN baru untuk tim logistik kantor pusat.',
        ]);

        $ticket = Ticket::where('requester_id', $this->requester->id)->first();
        $this->assertNotNull($ticket);

        $transaction = ExternalLoaTransaction::where('ticket_id', $ticket->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(ExternalLoaTransaction::LEVEL_SUPERVISOR, $transaction->approval_level);
        $this->assertEquals(ExternalLoaTransaction::SYNC_SYNCED, $transaction->sync_status);
        $this->assertNotEmpty($transaction->idempotency_key);
    }

    public function test_inbound_webhook_approves_supervisor_loa(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0010',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'hardware',
            'title' => 'Permohonan Monitor Eksternal',
            'description' => 'Monitor tambahan untuk kebutuhan desain arsitektur grafis.',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        $loaService = app(\App\Services\Contracts\LoaServiceInterface::class);
        $transaction = $loaService->dispatch($ticket, ExternalLoaTransaction::LEVEL_SUPERVISOR, [
            'supervisor_id' => $this->supervisor->id,
        ]);

        $response = $this->postJson('/api/v1/webhooks/loa-decision', [
            'idempotency_key' => $transaction->idempotency_key,
            'external_reference_id' => $transaction->external_reference_id,
            'approval_level' => 'supervisor',
            'decision' => 'approved',
            'notes' => 'Disetujui oleh atasan langsung via SSO LOA',
        ], ['X-Signature-SHA256' => 'mock-signature']);

        $response->assertStatus(200);
        $this->assertEquals(Ticket::STATUS_WAITING_IT_HEAD, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->approved_by_supervisor_at);
    }

    public function test_inbound_webhook_rejects_supervisor_loa(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0011',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'hardware',
            'title' => 'Permohonan iPad Pro',
            'description' => 'iPad pro baru untuk presentasi ke klien eksternal.',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        $loaService = app(\App\Services\Contracts\LoaServiceInterface::class);
        $transaction = $loaService->dispatch($ticket, ExternalLoaTransaction::LEVEL_SUPERVISOR, [
            'supervisor_id' => $this->supervisor->id,
        ]);

        $response = $this->postJson('/api/v1/webhooks/loa-decision', [
            'idempotency_key' => $transaction->idempotency_key,
            'external_reference_id' => $transaction->external_reference_id,
            'approval_level' => 'supervisor',
            'decision' => 'rejected',
            'notes' => 'Permohonan tidak sesuai dengan anggaran divisi tahun ini.',
        ], ['X-Signature-SHA256' => 'mock-signature']);

        $response->assertStatus(200);
        $this->assertEquals(Ticket::STATUS_REJECTED_SUPERVISOR, $ticket->fresh()->status);
    }

    public function test_timeout_failure_handling_and_retry_with_same_idempotency_key(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0012',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'network',
            'title' => 'Pemasangan Router Baru',
            'description' => 'Router untuk ruang meeting baru lantai 4 gedung barat.',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        /** @var MockLoaService $mockLoa */
        $mockLoa = app(\App\Services\Contracts\LoaServiceInterface::class);
        $mockLoa->setSimulateTimeout(true);

        $transaction = $mockLoa->dispatch($ticket, ExternalLoaTransaction::LEVEL_SUPERVISOR, []);
        $this->assertEquals(ExternalLoaTransaction::SYNC_FAILED, $transaction->sync_status);
        $this->assertEquals(Ticket::STATUS_WAITING_SUPERVISOR, $ticket->fresh()->status); // Status lokal TIDAK berubah prematur

        // Retry via manual sync
        $mockLoa->setSimulateTimeout(false);
        $response = $this->actingAs($this->requester)->post("/tickets/{$ticket->id}/sync-loa");
        $response->assertRedirect("/tickets/{$ticket->id}");

        $refreshed = $transaction->fresh();
        $this->assertEquals(ExternalLoaTransaction::SYNC_SYNCED, $refreshed->sync_status);
        $this->assertEquals(1, $refreshed->retry_count);
    }
}
