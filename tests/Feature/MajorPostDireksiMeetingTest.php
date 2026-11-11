<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\MeetingMinutes;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MajorPostDireksiMeetingTest extends TestCase
{
    use RefreshDatabase;

    protected User $requester;
    protected User $supervisor;
    protected User $itHead;
    protected User $otherUser;

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

        $this->otherUser = User::factory()->create([
            'role' => User::ROLE_REQUESTER,
            'department_id' => $dept->id,
        ]);
    }

    public function test_full_three_post_direksi_stages_meeting_minutes_and_approval_to_pool_siap_progress(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0040',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'software',
            'title' => 'Pengembangan Portal Vendor Pengadaan',
            'description' => 'Sistem portal terintegrasi untuk pengadaan barang kantor skala besar.',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'status' => Ticket::STATUS_WAITING_MAJOR_MEETING, // Hasil disetujui LOA Direksi
        ]);

        // Tahap C.3: Penjadwalan & Pelaksanaan Rapat oleh Kabag IT
        $response1 = $this->actingAs($this->itHead)->post("/tickets/{$ticket->id}/schedule-meeting", [
            'meeting_date' => now()->format('Y-m-d H:i:s'),
            'attendees_summary' => 'Kabag IT, Pemohon (Budi), Kabag Pengadaan (Ibu Siti)',
        ]);

        $response1->assertRedirect("/tickets/{$ticket->id}");
        $this->assertEquals(Ticket::STATUS_WAITING_USER_MINUTES, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->meeting_scheduled_at);

        // Tahap C.4: User Pemohon Input Notulensi
        $response2 = $this->actingAs($this->requester)->post("/tickets/{$ticket->id}/submit-minutes", [
            'notes' => 'Disepakati pembuatan fitur registrasi vendor, verifikasi dokumen legal, dan kuotasi harga.',
        ]);

        $response2->assertRedirect("/tickets/{$ticket->id}");
        $this->assertEquals(Ticket::STATUS_WAITING_MINUTES_APPROVAL, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->minutes_submitted_at);

        // Tahap C.5: Approval Notulensi oleh Kabag IT -> Masuk ke Pool Siap Progress!
        $response3 = $this->actingAs($this->itHead)->post("/tickets/{$ticket->id}/approve-minutes");

        $response3->assertRedirect("/tickets/{$ticket->id}");
        $ticketFinal = $ticket->fresh();

        $this->assertEquals(Ticket::STATUS_READY_FOR_WORK, $ticketFinal->status);
        $this->assertNotNull($ticketFinal->minutes_approved_at);
        $this->assertTrue($ticketFinal->meetingMinutes->is_approved_by_it_head);
    }

    public function test_post_direksi_meeting_minutes_revision_loop(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0041',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'software',
            'title' => 'Migrasi Sistem HRD Cloud',
            'description' => 'Migrasi aplikasi penggajian ke SaaS cloud provider.',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'status' => Ticket::STATUS_WAITING_USER_MINUTES,
        ]);

        // User submits first draft of minutes
        $this->actingAs($this->requester)->post("/tickets/{$ticket->id}/submit-minutes", [
            'notes' => 'Migrasi payroll dimulai bulan depan.',
        ]);

        $this->assertEquals(Ticket::STATUS_WAITING_MINUTES_APPROVAL, $ticket->fresh()->status);

        // Kabag IT requests revision
        $response = $this->actingAs($this->itHead)->post("/tickets/{$ticket->id}/revise-minutes", [
            'revision_comments' => 'Rincian jadwal migrasi data karyawan dan masa transisi paralel belum dicantumkan.',
        ]);

        $response->assertRedirect("/tickets/{$ticket->id}");
        $this->assertEquals(Ticket::STATUS_REVISION_MINUTES, $ticket->fresh()->status);
        $this->assertEquals('Rincian jadwal migrasi data karyawan dan masa transisi paralel belum dicantumkan.', $ticket->fresh()->meetingMinutes->revision_comments);

        // User fixes and resubmits minutes
        $this->actingAs($this->requester)->post("/tickets/{$ticket->id}/submit-minutes", [
            'notes' => 'Diperbaiki: Jadwal cut-off data 15 Nov, paralel run 2 minggu, integrasi bank selesai akhir bulan.',
        ]);

        $this->assertEquals(Ticket::STATUS_WAITING_MINUTES_APPROVAL, $ticket->fresh()->status);

        // Kabag IT approves revised minutes -> enters Pool Siap Progress
        $this->actingAs($this->itHead)->post("/tickets/{$ticket->id}/approve-minutes");
        $this->assertEquals(Ticket::STATUS_READY_FOR_WORK, $ticket->fresh()->status);
    }

    public function test_unrelated_user_cannot_submit_meeting_minutes(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0042',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'software',
            'title' => 'Tiket Rahasia Bisnis',
            'description' => 'Kebutuhan sistem internal departemen.',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'status' => Ticket::STATUS_WAITING_USER_MINUTES,
        ]);

        $response = $this->actingAs($this->otherUser)->post("/tickets/{$ticket->id}/submit-minutes", [
            'notes' => 'Saya mencoba menginput notulensi orang lain.',
        ]);

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_download_meeting_minutes_attachment(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $fakeFile = \Illuminate\Http\UploadedFile::fake()->create('notulensi.pdf', 100);
        $path = $fakeFile->store('meeting_minutes/999', 'local');

        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0099',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'software',
            'title' => 'Tiket Download Notulensi',
            'description' => 'Test file download.',
            'classification' => Ticket::CLASSIFICATION_MAJOR,
            'status' => Ticket::STATUS_WAITING_MINUTES_APPROVAL,
        ]);

        MeetingMinutes::create([
            'ticket_id' => $ticket->id,
            'author_id' => $this->requester->id,
            'meeting_date' => now(),
            'notes' => 'Catatan rapat',
            'attachment_path' => $path,
            'status' => MeetingMinutes::STATUS_WAITING_APPROVAL,
        ]);

        $response = $this->actingAs($this->requester)->get(route('tickets.meeting-minutes.download', $ticket));
        $response->assertOk();
    }
}
