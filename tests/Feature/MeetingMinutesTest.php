<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\MeetingMinutes;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingMinutesTest extends TestCase
{
    use RefreshDatabase;

    protected User $requester;
    protected User $supervisor;
    protected User $itHead;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['name' => 'Operations', 'code' => 'OPS']);

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
            'name' => 'Pak Hendra IT Head',
            'role' => User::ROLE_IT_HEAD,
        ]);

        $this->otherUser = User::factory()->create([
            'role' => User::ROLE_REQUESTER,
            'department_id' => $dept->id,
        ]);
    }

    public function test_meeting_minutes_submission_and_approval_workflow(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0020',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Perubahan Sistem',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'title' => 'Implementasi Modul Barcode Warehouse',
            'description' => 'Sistem integrasi scanner barcode dengan database ERP.',
            'status' => Ticket::STATUS_WAITING_MAJOR_MEETING,
        ]);

        // Requester submits minutes after meeting
        $responseSubmit = $this->actingAs($this->requester)->post(route('tickets.meeting-minutes.store', $ticket), [
            'meeting_date' => now()->format('Y-m-d'),
            'attendees' => 'Requester Andi, IT Head Hendra, Lead Dev Joko',
            'scope_summary' => 'Menyepakati integrasi API RESTful antara scanner Android dan server ERP gudang.',
            'deliverables' => 'API Endpoint barcode, aplikasi scanner APK, dan panduan teknis.',
            'target_completion_date' => now()->addWeeks(3)->format('Y-m-d'),
        ]);

        $responseSubmit->assertRedirect(route('tickets.show', $ticket));
        $fresh = $ticket->fresh();

        $this->assertSame(Ticket::STATUS_WAITING_MINUTES_APPROVAL, $fresh->status);
        $this->assertNotNull($fresh->meetingMinutes);
        $this->assertSame(MeetingMinutes::STATUS_WAITING_APPROVAL, $fresh->meetingMinutes->status);

        // IT Head approves the meeting minutes
        $responseApprove = $this->actingAs($this->itHead)->post(route('tickets.meeting-minutes.approve', $ticket), [
            'approval_notes' => 'Ruang lingkup disepakati, arsitektur memenuhi standar keamanan data.',
        ]);

        $responseApprove->assertRedirect(route('tickets.show', $ticket));
        $freshAfterApproval = $ticket->fresh();

        $this->assertSame(Ticket::STATUS_READY_FOR_WORK, $freshAfterApproval->status);
        $this->assertSame(MeetingMinutes::STATUS_APPROVED, $freshAfterApproval->meetingMinutes->status);
        $this->assertSame($this->itHead->id, $freshAfterApproval->meetingMinutes->approved_by);
        $this->assertNotNull($freshAfterApproval->approved_by_it_head_at);

        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'actor_id' => $this->itHead->id,
            'action' => 'APPROVE_MEETING_MINUTES',
            'to_status' => Ticket::STATUS_READY_FOR_WORK,
        ]);
    }

    public function test_requester_cannot_approve_meeting_minutes(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0021',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Perubahan Sistem',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'title' => 'Sistem Pemantauan Armada',
            'description' => 'Integrasi GPS tracker armada pengiriman.',
            'status' => Ticket::STATUS_WAITING_MINUTES_APPROVAL,
        ]);

        MeetingMinutes::create([
            'ticket_id' => $ticket->id,
            'author_id' => $this->requester->id,
            'meeting_date' => now()->format('Y-m-d'),
            'attendees' => 'Requester & Tim',
            'scope_summary' => 'Cakupan integrasi tracking armada.',
            'deliverables' => 'Dashboard pelacakan armada.',
            'status' => MeetingMinutes::STATUS_WAITING_APPROVAL,
        ]);

        // Requester tries to approve own minutes
        $response = $this->actingAs($this->requester)->post(route('tickets.meeting-minutes.approve', $ticket), [
            'approval_notes' => 'Saya setujui sendiri',
        ]);

        $response->assertForbidden();
        $this->assertSame(Ticket::STATUS_WAITING_MINUTES_APPROVAL, $ticket->fresh()->status);
    }

    public function test_unrelated_user_cannot_submit_meeting_minutes(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0022',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Perubahan Sistem',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'title' => 'Otomasi Faktur Pajak',
            'description' => 'Integrasi API DJP untuk e-Faktur otomatis.',
            'status' => Ticket::STATUS_WAITING_MAJOR_MEETING,
        ]);

        $response = $this->actingAs($this->otherUser)->post(route('tickets.meeting-minutes.store', $ticket), [
            'meeting_date' => now()->format('Y-m-d'),
            'attendees' => 'Peserta asing',
            'scope_summary' => 'Ruang lingkup percobaan ilegal.',
            'deliverables' => 'Deliverable sembarang.',
        ]);

        $response->assertForbidden();
    }
}
