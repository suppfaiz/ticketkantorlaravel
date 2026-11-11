<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExternalLoaTransaction;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinorWorkflowTest extends TestCase
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
            'name' => 'Pak Kabag IT',
            'role' => User::ROLE_IT_HEAD,
        ]);

        $this->developer = User::factory()->create([
            'role' => User::ROLE_IT_DEVELOPER,
        ]);
    }

    public function test_it_head_can_approve_minor_ticket_directly_to_ready_for_work(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0020',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'hardware',
            'title' => 'Penggantian Mouse Rusak',
            'description' => 'Scroll wheel mouse macet untuk staff administrasi.',
            'status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);

        $response = $this->actingAs($this->itHead)->post("/tickets/{$ticket->id}/it-head-decision", [
            'decision' => 'minor_approved',
            'notes' => 'Minor request, disetujui langsung siap dikerjakan.',
        ]);

        $response->assertRedirect("/tickets/{$ticket->id}");
        $fresh = $ticket->fresh();

        $this->assertEquals(Ticket::CLASSIFICATION_MINOR, $fresh->classification);
        $this->assertEquals(Ticket::STATUS_READY_FOR_WORK, $fresh->status);
        $this->assertNotNull($fresh->approved_by_it_head_at);
    }

    public function test_it_head_approving_major_ticket_dispatches_loa_to_kadiv_it(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0021',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'software',
            'title' => 'Migrasi Server Database ERP',
            'description' => 'Migrasi seluruh basis data keuangan ke infrastruktur cloud baru.',
            'status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);

        $response = $this->actingAs($this->itHead)->post("/tickets/{$ticket->id}/it-head-decision", [
            'decision' => 'major_approved',
            'notes' => 'Major request, membutuhkan persetujuan Kadiv IT & Direksi.',
        ]);

        $response->assertRedirect("/tickets/{$ticket->id}");
        $fresh = $ticket->fresh();

        $this->assertEquals(Ticket::CLASSIFICATION_MAJOR, $fresh->classification);
        $this->assertEquals(Ticket::STATUS_WAITING_LOA_KADIV_IT, $fresh->status);

        $transaction = ExternalLoaTransaction::where('ticket_id', $ticket->id)
            ->where('approval_level', ExternalLoaTransaction::LEVEL_KADIV_IT_OPS)
            ->first();

        $this->assertNotNull($transaction);
    }

    public function test_it_head_can_reject_ticket(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0022',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'hardware',
            'title' => 'Permohonan Monitor Gaming 240Hz',
            'description' => 'Untuk staff input data harian kantor.',
            'status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);

        $response = $this->actingAs($this->itHead)->post("/tickets/{$ticket->id}/it-head-decision", [
            'decision' => 'rejected',
            'reason' => 'Spesifikasi tidak relevan dengan kebutuhan tugas operasional.',
        ]);

        $response->assertRedirect("/tickets/{$ticket->id}");
        $this->assertEquals(Ticket::STATUS_REJECTED_IT_HEAD, $ticket->fresh()->status);
    }

    public function test_it_head_can_request_revision(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0023',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'access',
            'title' => 'Permohonan Hak Akses Root',
            'description' => 'Perlu akses root untuk cek server.',
            'status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);

        $response = $this->actingAs($this->itHead)->post("/tickets/{$ticket->id}/it-head-decision", [
            'decision' => 'revision',
            'revision_notes' => 'Harap sertakan persetujuan tertulis dan modul server yang spesifik.',
        ]);

        $response->assertRedirect("/tickets/{$ticket->id}");
        $this->assertEquals(Ticket::STATUS_REVISION_BY_IT_HEAD, $ticket->fresh()->status);
    }

    public function test_non_it_head_cannot_execute_it_head_decision(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0024',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'hardware',
            'title' => 'Permohonan Laptop',
            'description' => 'Laptop kantor untuk staf baru.',
            'status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);

        $response = $this->actingAs($this->developer)->post("/tickets/{$ticket->id}/it-head-decision", [
            'decision' => 'minor_approved',
        ]);

        $response->assertStatus(403);
    }
}
