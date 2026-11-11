<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupervisorApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $requester;
    protected User $supervisor;
    protected User $otherSupervisor;
    protected User $otherRequester;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['name' => 'Human Resources', 'code' => 'HR']);

        $this->supervisor = User::factory()->create([
            'name' => 'Pak Budi Supervisor',
            'role' => User::ROLE_SUPERVISOR,
            'department_id' => $dept->id,
        ]);

        $this->otherSupervisor = User::factory()->create([
            'name' => 'Ibu Siti Supervisor',
            'role' => User::ROLE_SUPERVISOR,
            'department_id' => $dept->id,
        ]);

        $this->requester = User::factory()->create([
            'name' => 'Andi Requester',
            'role' => User::ROLE_REQUESTER,
            'department_id' => $dept->id,
            'supervisor_id' => $this->supervisor->id,
        ]);

        $this->otherRequester = User::factory()->create([
            'name' => 'Bambang Requester',
            'role' => User::ROLE_REQUESTER,
            'department_id' => $dept->id,
            'supervisor_id' => $this->otherSupervisor->id,
        ]);
    }

    public function test_requester_can_submit_ticket_with_attachments(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->requester)->post(route('tickets.store'), [
            'title' => 'Permintaan Laptop Baru',
            'category' => 'Hardware',
            'priority' => 'Medium',
            'description' => 'Laptop kantor saat ini sering mati mendadak dan baterai drop.',
            'attachments' => [
                UploadedFile::fake()->create('foto_laptop.jpg', 200, 'image/jpeg'),
                UploadedFile::fake()->create('spesifikasi.pdf', 500, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'title' => 'Permintaan Laptop Baru',
            'category' => 'Hardware',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        $ticket = Ticket::where('requester_id', $this->requester->id)->first();
        $this->assertNotNull($ticket);
        $this->assertCount(2, $ticket->attachments);

        // Verify audit log created
        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'actor_id' => $this->requester->id,
            'action' => 'SUBMIT_TICKET',
            'to_status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);
    }

    public function test_supervisor_can_approve_subordinate_ticket(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0001',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Permintaan Lisensi Figma',
            'description' => 'Untuk kebutuhan perancangan UI modul rekrutmen.',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        $response = $this->actingAs($this->supervisor)->post(route('tickets.supervisor-approve', $ticket), [
            'notes' => 'Disetujui untuk mendukung operasional tim HR.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertSame(Ticket::STATUS_WAITING_IT_HEAD, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->approved_by_supervisor_at);

        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'actor_id' => $this->supervisor->id,
            'action' => 'SUPERVISOR_APPROVE',
            'to_status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);
    }

    public function test_other_supervisor_cannot_approve_ticket(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0002',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id, // Budi is supervisor
            'category' => 'Software',
            'title' => 'Permintaan Akses Database',
            'description' => 'Akses read-only database laporan.',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        // Ibu Siti tries to approve
        $response = $this->actingAs($this->otherSupervisor)->post(route('tickets.supervisor-approve', $ticket), [
            'notes' => 'Mencoba menyetujui tiket bawahan orang lain',
        ]);

        $response->assertForbidden();
        $this->assertSame(Ticket::STATUS_WAITING_SUPERVISOR, $ticket->fresh()->status);
    }

    public function test_supervisor_can_reject_ticket_with_reason(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0003',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Hardware',
            'title' => 'Permintaan Keyboard Gaming',
            'description' => 'Keyboard mekanikal RGB untuk kenyamanan mengetik.',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        $response = $this->actingAs($this->supervisor)->post(route('tickets.supervisor-reject', $ticket), [
            'reason' => 'Perangkat tidak sesuai dengan standar perlengkapan kerja kantor.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertSame(Ticket::STATUS_REJECTED_SUPERVISOR, $ticket->fresh()->status);

        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'action' => 'SUPERVISOR_REJECT',
            'to_status' => Ticket::STATUS_REJECTED_SUPERVISOR,
            'remarks' => 'Perangkat tidak sesuai dengan standar perlengkapan kerja kantor.',
        ]);
    }

    public function test_supervisor_can_request_revision_and_requester_can_resubmit(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0004',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Permintaan Software Editing',
            'description' => 'Butuh software desain grafis.',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        // Supervisor asks for revision
        $responseRevise = $this->actingAs($this->supervisor)->post(route('tickets.supervisor-revise', $ticket), [
            'revision_notes' => 'Harap sebutkan nama software dan estimasi anggaran biayanya.',
        ]);

        $responseRevise->assertRedirect(route('tickets.show', $ticket));
        $this->assertSame(Ticket::STATUS_REVISION_REQUESTER, $ticket->fresh()->status);

        // Requester updates & resubmits
        $responseUpdate = $this->actingAs($this->requester)->put(route('tickets.update', $ticket), [
            'title' => 'Permintaan Software Editing (Adobe Illustrator)',
            'category' => 'Software',
            'priority' => 'Medium',
            'description' => 'Butuh Adobe Illustrator untuk desain materi poster internal HR. Biaya $25/bulan.',
        ]);

        $responseUpdate->assertRedirect(route('tickets.show', $ticket));
        $this->assertSame(Ticket::STATUS_WAITING_SUPERVISOR, $ticket->fresh()->status);
        $this->assertSame('Permintaan Software Editing (Adobe Illustrator)', $ticket->fresh()->title);
    }
}
