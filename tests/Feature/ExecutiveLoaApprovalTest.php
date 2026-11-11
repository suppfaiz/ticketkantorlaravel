<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExternalLoaTransaction;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutiveLoaApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $requester;
    protected User $supervisor;
    protected User $itHead;
    protected User $kadivIt;
    protected User $direksi;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['name' => 'Operasional', 'code' => 'OPS']);

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

        $this->kadivIt = User::factory()->create([
            'role' => User::ROLE_KADIV_IT,
        ]);

        $this->direksi = User::factory()->create([
            'role' => User::ROLE_DIREKSI,
        ]);
    }

    public function test_kadiv_it_can_approve_major_ticket(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261030-0081',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Sistem Rekonsiliasi Otomatis',
            'description' => 'Implementasi modul rekonsiliasi data transaksi.',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'status' => Ticket::STATUS_WAITING_LOA_KADIV_IT,
            'submitted_at' => now(),
            'approved_by_supervisor_at' => now(),
            'approved_by_it_head_at' => now(),
        ]);

        $response = $this->actingAs($this->kadivIt)
            ->post(route('tickets.kadiv-approve', $ticket), [
                'notes' => 'Disetujui Kadiv IT, teruskan ke Direksi',
            ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $fresh = $ticket->fresh();

        $this->assertSame(Ticket::STATUS_WAITING_LOA_DIREKSI, $fresh->status);
        $this->assertNotNull($fresh->approved_by_kadiv_at);

        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'actor_id' => $this->kadivIt->id,
            'action' => 'KADIV_IT_APPROVE',
            'to_status' => Ticket::STATUS_WAITING_LOA_DIREKSI,
        ]);

        // Auto-dispatched Direksi LOA transaction exists
        $this->assertDatabaseHas('external_loa_transactions', [
            'ticket_id' => $ticket->id,
            'approval_level' => ExternalLoaTransaction::LEVEL_DIREKSI,
        ]);
    }

    public function test_kadiv_it_can_reject_major_ticket(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261030-0082',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Sistem Baru',
            'description' => 'Deskripsi pengujian penolakan Kadiv.',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'status' => Ticket::STATUS_WAITING_LOA_KADIV_IT,
        ]);

        $response = $this->actingAs($this->kadivIt)
            ->post(route('tickets.kadiv-reject', $ticket), [
                'reason' => 'Tidak masuk prioritas anggaran Q4.',
            ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $fresh = $ticket->fresh();

        $this->assertSame(Ticket::STATUS_REJECTED_KADIV_IT, $fresh->status);

        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'actor_id' => $this->kadivIt->id,
            'action' => 'KADIV_IT_REJECT',
            'to_status' => Ticket::STATUS_REJECTED_KADIV_IT,
        ]);
    }

    public function test_direksi_can_approve_major_ticket(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261030-0083',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Pengembangan Sistem ERP Terintegrasi',
            'description' => 'Upgrade modul procurement dan finance.',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'status' => Ticket::STATUS_WAITING_LOA_DIREKSI,
            'approved_by_kadiv_at' => now(),
        ]);

        $response = $this->actingAs($this->direksi)
            ->post(route('tickets.direksi-approve', $ticket), [
                'notes' => 'Disetujui resmi oleh Direksi, segera lakukan rapat koordinasi.',
            ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $fresh = $ticket->fresh();

        $this->assertSame(Ticket::STATUS_WAITING_MAJOR_MEETING, $fresh->status);
        $this->assertNotNull($fresh->approved_by_direksi_at);

        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'actor_id' => $this->direksi->id,
            'action' => 'DIREKSI_APPROVE',
            'to_status' => Ticket::STATUS_WAITING_MAJOR_MEETING,
        ]);
    }

    public function test_direksi_can_reject_major_ticket(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261030-0084',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Pengembangan Sistem Tidak Sesuai Rencana',
            'description' => 'Deskripsi tiket pengujian penolakan Direksi.',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'status' => Ticket::STATUS_WAITING_LOA_DIREKSI,
        ]);

        $response = $this->actingAs($this->direksi)
            ->post(route('tickets.direksi-reject', $ticket), [
                'reason' => 'Proyek ditunda hingga tahun depan sesuai rapat dewan.',
            ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $fresh = $ticket->fresh();

        $this->assertSame(Ticket::STATUS_REJECTED_DIREKSI, $fresh->status);

        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'actor_id' => $this->direksi->id,
            'action' => 'DIREKSI_REJECT',
            'to_status' => Ticket::STATUS_REJECTED_DIREKSI,
        ]);
    }

    public function test_unauthorized_user_cannot_approve_kadiv_or_direksi(): void
    {
        $ticketKadiv = Ticket::create([
            'ticket_number' => 'TICK-20261030-0085',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Uji Otorisasi Kadiv',
            'description' => 'Uji coba otorisasi.',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'status' => Ticket::STATUS_WAITING_LOA_KADIV_IT,
        ]);

        $response = $this->actingAs($this->itHead)
            ->post(route('tickets.kadiv-approve', $ticketKadiv));
        $response->assertForbidden();

        $ticketDireksi = Ticket::create([
            'ticket_number' => 'TICK-20261030-0086',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Uji Otorisasi Direksi',
            'description' => 'Uji coba otorisasi.',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'status' => Ticket::STATUS_WAITING_LOA_DIREKSI,
        ]);

        $response = $this->actingAs($this->kadivIt)
            ->post(route('tickets.direksi-approve', $ticketDireksi));
        $response->assertForbidden();
    }
}
