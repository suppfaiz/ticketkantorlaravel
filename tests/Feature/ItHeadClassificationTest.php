<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItHeadClassificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $requester;
    protected User $supervisor;
    protected User $itHead;
    protected User $developer;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['name' => 'Marketing', 'code' => 'MKT']);

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
            'name' => 'Pak Hendra Kabag IT',
            'role' => User::ROLE_IT_HEAD,
        ]);

        $this->developer = User::factory()->create([
            'role' => User::ROLE_IT_DEVELOPER,
        ]);
    }

    public function test_it_head_can_classify_minor_ticket_directly_to_ready_for_work(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0010',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Perbaikan Format Export Excel',
            'description' => 'Format currency terpotong dua digit desimal.',
            'status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);

        $response = $this->actingAs($this->itHead)->post(route('tickets.it-head-classify-approve', $ticket), [
            'classification' => Ticket::CLASSIFICATION_MINOR,
            'priority' => 'Medium',
            'notes' => 'Minor issue, langsung kerjakan tim frontend.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $fresh = $ticket->fresh();

        $this->assertSame(Ticket::CLASSIFICATION_MINOR, $fresh->classification);
        $this->assertSame(Ticket::STATUS_READY_FOR_WORK, $fresh->status);
        $this->assertNotNull($fresh->approved_by_it_head_at);

        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'actor_id' => $this->itHead->id,
            'action' => 'IT_HEAD_CLASSIFY_MINOR',
            'to_status' => Ticket::STATUS_READY_FOR_WORK,
        ]);
    }

    public function test_it_head_can_classify_major_ticket_to_waiting_major_meeting(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0011',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Perubahan Sistem',
            'title' => 'Integrasi Payment Gateway Baru',
            'description' => 'Membutuhkan penambahan gateway QRIS dan VA pada modul checkout.',
            'status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);

        $response = $this->actingAs($this->itHead)->post(route('tickets.it-head-classify-approve', $ticket), [
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'priority' => 'High',
            'notes' => 'Major scope, wajib otorisasi eksekutif dan rapat koordinasi.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $fresh = $ticket->fresh();

        $this->assertSame(Ticket::CLASSIFICATION_MAJOR, $fresh->classification);
        $this->assertSame(Ticket::STATUS_WAITING_LOA_KADIV_IT, $fresh->status);

        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'actor_id' => $this->itHead->id,
            'action' => 'IT_HEAD_CLASSIFY_MAJOR',
            'to_status' => Ticket::STATUS_WAITING_LOA_KADIV_IT,
        ]);
    }

    public function test_non_it_head_cannot_classify_ticket(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0012',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Update Modul Kasir',
            'description' => 'Deskripsi cukup panjang untuk pengujian.',
            'status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);

        // Developer tries to classify
        $response = $this->actingAs($this->developer)->post(route('tickets.it-head-classify-approve', $ticket), [
            'classification' => Ticket::CLASSIFICATION_MINOR,
        ]);

        $response->assertForbidden();
        $this->assertSame(Ticket::STATUS_WAITING_IT_HEAD, $ticket->fresh()->status);
    }

    public function test_it_head_can_reject_ticket_with_reason(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0013',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Perubahan Sistem',
            'title' => 'Migrasi Total Server ke Cloud Pribadi',
            'description' => 'Permintaan yang tidak sesuai arsitektur perusahaan.',
            'status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);

        $response = $this->actingAs($this->itHead)->post(route('tickets.it-head-reject', $ticket), [
            'reason' => 'Tidak sesuai dengan roadmap teknologi dan kebijakan keamanan data korporasi.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertSame(Ticket::STATUS_REJECTED_IT_HEAD, $ticket->fresh()->status);

        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'action' => 'IT_HEAD_REJECT',
            'to_status' => Ticket::STATUS_REJECTED_IT_HEAD,
        ]);
    }

    public function test_resubmission_post_it_head_revision_returns_to_supervisor(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0014',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Penambahan Fitur Chat Internal',
            'description' => 'Perlu fitur chatting dalam intranet.',
            'status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);

        // IT Head asks for revision
        $this->actingAs($this->itHead)->post(route('tickets.it-head-revise', $ticket), [
            'revision_notes' => 'Harap sertakan dokumen skema user flow dan izin departemen.',
        ]);

        $this->assertSame(Ticket::STATUS_REVISION_BY_IT_HEAD, $ticket->fresh()->status);

        // Requester updates and resubmits
        $response = $this->actingAs($this->requester)->put(route('tickets.update', $ticket), [
            'title' => 'Penambahan Fitur Chat Internal (Revisi User Flow)',
            'category' => 'Software',
            'description' => 'Berikut user flow terlampir dan sudah dikoordinasikan.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));

        // INVARIANT FR-012: Must return to Direct Supervisor approval!
        $this->assertSame(Ticket::STATUS_WAITING_SUPERVISOR, $ticket->fresh()->status);

        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'action' => 'RESUBMIT_TO_SUPERVISOR_AFTER_IT_REVISION',
            'to_status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);
    }
}
