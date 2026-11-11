<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickstartEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected User $requester;
    protected User $supervisor;
    protected User $itHead;
    protected User $dev1;
    protected User $dev2;
    protected User $kadivIt;
    protected User $direksi;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['name' => 'General Affairs', 'code' => 'GA']);

        $this->supervisor = User::factory()->create([
            'email' => 'supervisor@kantor.internal',
            'name' => 'Supervisor Pak Budi',
            'role' => User::ROLE_SUPERVISOR,
            'department_id' => $dept->id,
        ]);

        $this->requester = User::factory()->create([
            'email' => 'requester@kantor.internal',
            'name' => 'Karyawan Pemohon',
            'role' => User::ROLE_REQUESTER,
            'department_id' => $dept->id,
            'supervisor_id' => $this->supervisor->id,
        ]);

        $this->itHead = User::factory()->create([
            'email' => 'kabagit@kantor.internal',
            'name' => 'Kepala Bagian IT Hendra',
            'role' => User::ROLE_IT_HEAD,
        ]);

        $this->kadivIt = User::factory()->create([
            'email' => 'kadivit@kantor.internal',
            'name' => 'Kadiv IT',
            'role' => User::ROLE_KADIV_IT,
        ]);

        $this->direksi = User::factory()->create([
            'email' => 'direksi@kantor.internal',
            'name' => 'Dewan Direksi',
            'role' => User::ROLE_DIREKSI,
        ]);

        $this->dev1 = User::factory()->create([
            'email' => 'dev1@kantor.internal',
            'name' => 'IT Developer 1',
            'role' => User::ROLE_IT_DEVELOPER,
        ]);

        $this->dev2 = User::factory()->create([
            'email' => 'dev2@kantor.internal',
            'name' => 'IT Developer 2',
            'role' => User::ROLE_IT_DEVELOPER,
        ]);
    }

    public function test_quickstart_scenario_1_minor_ticket_full_lifecycle(): void
    {
        // 1. Requester submits ticket
        $resSubmit = $this->actingAs($this->requester)->post(route('tickets.store'), [
            'title' => 'Koneksi Printer Lambat di Ruang GA',
            'category' => 'Hardware',
            'priority' => 'Medium',
            'description' => 'Printer sering offline saat jam sibuk pengiriman berkas.',
        ]);
        $resSubmit->assertRedirect();

        $ticket = Ticket::where('requester_id', $this->requester->id)->first();
        $this->assertNotNull($ticket);
        $this->assertSame(Ticket::STATUS_WAITING_SUPERVISOR, $ticket->status);
        $this->assertStringStartsWith('TICK-', $ticket->ticket_number);

        // 2. Supervisor approves
        $resSup = $this->actingAs($this->supervisor)->post(route('tickets.supervisor-approve', $ticket), [
            'notes' => 'Disetujui, printer memang sangat dibutuhkan tim.',
        ]);
        $resSup->assertRedirect();
        $this->assertSame(Ticket::STATUS_WAITING_IT_HEAD, $ticket->fresh()->status);

        // 3. IT Head classifies as Minor
        $resHead = $this->actingAs($this->itHead)->post(route('tickets.it-head-classify-approve', $ticket), [
            'classification' => Ticket::CLASSIFICATION_MINOR,
            'priority' => 'Medium',
            'notes' => 'Klasifikasi Minor, ganti switch kabel LAN.',
        ]);
        $resHead->assertRedirect();
        $this->assertSame(Ticket::STATUS_READY_FOR_WORK, $ticket->fresh()->status);

        // 4. IT Dev 1 self-assigns and completes work
        $resAssign = $this->actingAs($this->dev1)->post(route('tickets.self-assign', $ticket));
        $resAssign->assertRedirect();
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertSame($this->dev1->id, $ticket->fresh()->assigned_to);

        $resProgress = $this->actingAs($this->dev1)->post(route('tickets.activities.store', $ticket), [
            'activity_type' => 'progress_note',
            'content' => 'Penggantian kabel LAN dan konfigurasi IP statik printer selesai.',
        ]);
        $resProgress->assertRedirect();

        $resComplete = $this->actingAs($this->dev1)->post(route('tickets.complete-work', $ticket), [
            'completion_notes' => 'Printer sudah online kembali dan dites cetak 10 lembar lancar.',
        ]);
        $resComplete->assertRedirect();
        $this->assertSame(Ticket::STATUS_WAITING_USER_REVIEW, $ticket->fresh()->status);

        // 5. Requester reviews and accepts
        $resAccept = $this->actingAs($this->requester)->post(route('tickets.reviews.accept', $ticket), [
            'rating' => 5,
            'feedback' => 'Printer sudah lancar kembali, terima kasih!',
        ]);
        $resAccept->assertRedirect();
        $this->assertSame(Ticket::STATUS_COMPLETED, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->closed_at);
    }

    public function test_quickstart_scenario_2_major_ticket_with_meeting_minutes(): void
    {
        // 1. Requester submits ticket
        $this->actingAs($this->requester)->post(route('tickets.store'), [
            'title' => 'Permintaan Sistem CRM Penjualan Baru',
            'category' => 'Perubahan Sistem',
            'priority' => 'High',
            'description' => 'Migrasi spreadsheet ke sistem CRM terintegrasi database pelanggan.',
        ]);
        $ticket = Ticket::where('requester_id', $this->requester->id)->latest()->first();

        // 2. Supervisor approves
        $this->actingAs($this->supervisor)->post(route('tickets.supervisor-approve', $ticket), [
            'notes' => 'Disetujui untuk meningkatkan omset dan koordinasi sales.',
        ]);

        // 3. IT Head classifies as Major -> Wait for Kadiv IT LOA
        $this->actingAs($this->itHead)->post(route('tickets.it-head-classify-approve', $ticket), [
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'priority' => 'High',
            'notes' => 'Major project, butuh analisis skema database dan keamanan.',
        ]);
        $this->assertSame(Ticket::STATUS_WAITING_LOA_KADIV_IT, $ticket->fresh()->status);

        // 3a. Kadiv IT approves LOA
        $this->actingAs($this->kadivIt)->post(route('tickets.kadiv-approve', $ticket), [
            'notes' => 'Disetujui Kadiv IT.',
        ]);
        $this->assertSame(Ticket::STATUS_WAITING_LOA_DIREKSI, $ticket->fresh()->status);

        // 3b. Direksi approves LOA -> Wait for Meeting
        $this->actingAs($this->direksi)->post(route('tickets.direksi-approve', $ticket), [
            'notes' => 'Disetujui Direksi.',
        ]);
        $this->assertSame(Ticket::STATUS_WAITING_MAJOR_MEETING, $ticket->fresh()->status);

        // Dev cannot take ticket while in Major meeting stage
        $resForbiddenAssign = $this->actingAs($this->dev1)->post(route('tickets.self-assign', $ticket));
        $resForbiddenAssign->assertForbidden();

        // 4. Input Meeting Minutes
        $this->actingAs($this->requester)->post(route('tickets.meeting-minutes.store', $ticket), [
            'meeting_date' => now()->format('Y-m-d'),
            'attendees' => 'Requester, Kabag IT Hendra, Dev1, Dev2',
            'scope_summary' => 'Menyepakati modul leads, opportunities, dan integrasi WhatsApp API.',
            'deliverables' => 'Source code CRM, API docs, dan User Acceptance Test.',
            'target_completion_date' => now()->addMonths(2)->format('Y-m-d'),
        ]);
        $this->assertSame(Ticket::STATUS_WAITING_MINUTES_APPROVAL, $ticket->fresh()->status);

        // 5. IT Head approves minutes
        $this->actingAs($this->itHead)->post(route('tickets.meeting-minutes.approve', $ticket), [
            'approval_notes' => 'Notulensi disetujui, siap masuk backlog sprint tim IT.',
        ]);
        $this->assertSame(Ticket::STATUS_READY_FOR_WORK, $ticket->fresh()->status);

        // 6. Now Developer can self-assign
        $resAssign = $this->actingAs($this->dev2)->post(route('tickets.self-assign', $ticket));
        $resAssign->assertRedirect();
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertSame($this->dev2->id, $ticket->fresh()->assigned_to);
    }

    public function test_quickstart_scenario_3_sso_authentication_lifecycle(): void
    {
        $response = $this->post('/login', [
            'email' => 'karyawan.baru@kantor.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::where('email', 'karyawan.baru@kantor.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->sso_id);
    }

    public function test_quickstart_scenario_4_major_executive_chain_kadiv_and_direksi(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0099',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'software',
            'title' => 'Implementasi Modul AI Fraud Detection',
            'description' => 'Sistem pendeteksi penipuan transaksi berbasis machine learning.',
            'status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);

        // Kabag IT sets Major
        $this->actingAs($this->itHead)->post("/tickets/{$ticket->id}/it-head-decision", [
            'decision' => 'major_approved',
        ]);

        $this->assertSame(Ticket::STATUS_WAITING_LOA_KADIV_IT, $ticket->fresh()->status);

        // Webhook Kadiv IT approves
        $kadivTrx = $ticket->externalLoaTransactions()->where('approval_level', 'kadiv_it_ops')->first();
        $this->postJson('/api/v1/webhooks/loa-decision', [
            'idempotency_key' => $kadivTrx->idempotency_key,
            'approval_level' => 'kadiv_it_ops',
            'decision' => 'approved',
        ], ['X-Signature-SHA256' => 'mock-signature']);

        $this->assertSame(Ticket::STATUS_WAITING_LOA_DIREKSI, $ticket->fresh()->status);

        // Webhook Direksi approves
        $direksiTrx = $ticket->externalLoaTransactions()->where('approval_level', 'direksi')->first();
        $this->postJson('/api/v1/webhooks/loa-decision', [
            'idempotency_key' => $direksiTrx->idempotency_key,
            'approval_level' => 'direksi',
            'decision' => 'approved',
        ], ['X-Signature-SHA256' => 'mock-signature']);

        $this->assertSame(Ticket::STATUS_WAITING_MAJOR_MEETING, $ticket->fresh()->status);
    }

    public function test_quickstart_scenario_5_concurrency_safe_self_assignment_and_signoff(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0100',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'hardware',
            'title' => 'Penggantian Switch Lantai 2',
            'description' => 'Switch sering restart sendiri saat beban tinggi.',
            'status' => Ticket::STATUS_READY_FOR_WORK,
        ]);

        // Dev 1 claims successfully
        $this->actingAs($this->dev1)->post("/tickets/{$ticket->id}/self-assign");
        $this->assertSame($this->dev1->id, $ticket->fresh()->assigned_to);

        // Dev 2 tries to claim same ticket
        $resDev2 = $this->actingAs($this->dev2)->post("/tickets/{$ticket->id}/self-assign");
        $this->assertTrue($resDev2->isForbidden() || $resDev2->isRedirect());
        $this->assertSame($this->dev1->id, $ticket->fresh()->assigned_to);

        // Dev 1 completes work
        $this->actingAs($this->dev1)->post("/tickets/{$ticket->id}/complete-work", [
            'completion_notes' => 'Pekerjaan penggantian switch telah selesai dan telah diuji.',
        ]);
        $this->assertSame(Ticket::STATUS_WAITING_USER_REVIEW, $ticket->fresh()->status);

        // Requester reviews and signs off
        $this->actingAs($this->requester)->post("/tickets/{$ticket->id}/user-review", [
            'action' => 'accept',
            'rating' => 5,
            'feedback' => 'Pekerjaan sangat cepat dan rapi.',
        ]);

        $this->assertSame(Ticket::STATUS_COMPLETED, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->closed_at);
    }
}
