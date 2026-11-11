<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExternalLoaTransaction;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MajorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $requester;
    protected User $supervisor;
    protected User $itHead;
    protected User $developer;

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

        $this->itHead = User::factory()->create([
            'role' => User::ROLE_IT_HEAD,
        ]);

        $this->developer = User::factory()->create([
            'role' => User::ROLE_IT_DEVELOPER,
        ]);
    }

    public function test_sequential_major_executive_chain_kadiv_it_then_direksi_to_waiting_meeting(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0030',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'software',
            'title' => 'Pengembangan Core Banking Baru',
            'description' => 'Sistem core banking generasi baru dengan arsitektur microservices.',
            'status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);

        // Step 1: Kabag IT approves Major
        $this->actingAs($this->itHead)->post("/tickets/{$ticket->id}/it-head-decision", [
            'decision' => 'major_approved',
        ]);

        $this->assertEquals(Ticket::STATUS_WAITING_LOA_KADIV_IT, $ticket->fresh()->status);

        $kadivTransaction = ExternalLoaTransaction::where('ticket_id', $ticket->id)
            ->where('approval_level', ExternalLoaTransaction::LEVEL_KADIV_IT_OPS)
            ->first();

        $this->assertNotNull($kadivTransaction);

        // Step 2: Inbound Webhook Kadiv IT Approves
        $this->postJson('/api/v1/webhooks/loa-decision', [
            'idempotency_key' => $kadivTransaction->idempotency_key,
            'external_reference_id' => $kadivTransaction->external_reference_id,
            'approval_level' => 'kadiv_it_ops',
            'decision' => 'approved',
            'notes' => 'Disetujui oleh Kepala Divisi IT & Ops',
        ], ['X-Signature-SHA256' => 'mock-signature']);

        $ticketFresh = $ticket->fresh();
        $this->assertEquals(Ticket::STATUS_WAITING_LOA_DIREKSI, $ticketFresh->status);
        $this->assertNotNull($ticketFresh->approved_by_kadiv_at);

        // Auto-dispatched Direksi transaction exists
        $direksiTransaction = ExternalLoaTransaction::where('ticket_id', $ticket->id)
            ->where('approval_level', ExternalLoaTransaction::LEVEL_DIREKSI)
            ->first();

        $this->assertNotNull($direksiTransaction);

        // Step 3: Inbound Webhook Direksi Approves
        $this->postJson('/api/v1/webhooks/loa-decision', [
            'idempotency_key' => $direksiTransaction->idempotency_key,
            'external_reference_id' => $direksiTransaction->external_reference_id,
            'approval_level' => 'direksi',
            'decision' => 'approved',
            'notes' => 'Disetujui resmi oleh Dewan Direksi',
        ], ['X-Signature-SHA256' => 'mock-signature']);

        $ticketPostDireksi = $ticket->fresh();

        // INVARIANT CHECK: Tiket DILARANG langsung masuk ke Pool Siap Progress!
        $this->assertEquals(Ticket::STATUS_WAITING_MAJOR_MEETING, $ticketPostDireksi->status);
        $this->assertNotEquals(Ticket::STATUS_READY_FOR_WORK, $ticketPostDireksi->status);
        $this->assertNotNull($ticketPostDireksi->approved_by_direksi_at);
    }

    public function test_kadiv_it_rejection_moves_ticket_to_rejected_kadiv_it(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0031',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'software',
            'title' => 'Pengadaan Lisensi Software AI Generatif',
            'description' => 'Lisensi AI untuk seluruh departemen operasional.',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'status' => Ticket::STATUS_WAITING_LOA_KADIV_IT,
        ]);

        $loaService = app(\App\Services\Contracts\LoaServiceInterface::class);
        $transaction = $loaService->dispatch($ticket, ExternalLoaTransaction::LEVEL_KADIV_IT_OPS, []);

        $this->postJson('/api/v1/webhooks/loa-decision', [
            'idempotency_key' => $transaction->idempotency_key,
            'external_reference_id' => $transaction->external_reference_id,
            'approval_level' => 'kadiv_it_ops',
            'decision' => 'rejected',
            'notes' => 'Ditolak: Prioritas infrastruktur dialihkan ke Q3.',
        ], ['X-Signature-SHA256' => 'mock-signature']);

        $this->assertEquals(Ticket::STATUS_REJECTED_KADIV_IT, $ticket->fresh()->status);
    }

    public function test_direksi_rejection_moves_ticket_to_rejected_direksi(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0032',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'software',
            'title' => 'Pembangunan Data Center Baru',
            'description' => 'Proyek ekspansi data center regional.',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'status' => Ticket::STATUS_WAITING_LOA_DIREKSI,
        ]);

        $loaService = app(\App\Services\Contracts\LoaServiceInterface::class);
        $transaction = $loaService->dispatch($ticket, ExternalLoaTransaction::LEVEL_DIREKSI, []);

        $this->postJson('/api/v1/webhooks/loa-decision', [
            'idempotency_key' => $transaction->idempotency_key,
            'external_reference_id' => $transaction->external_reference_id,
            'approval_level' => 'direksi',
            'decision' => 'rejected',
            'notes' => 'Ditolak Direksi: Rencana CAPEX ditunda.',
        ], ['X-Signature-SHA256' => 'mock-signature']);

        $this->assertEquals(Ticket::STATUS_REJECTED_DIREKSI, $ticket->fresh()->status);
    }
}
