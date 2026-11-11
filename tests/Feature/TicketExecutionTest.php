<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected User $developer;
    protected User $otherDeveloper;
    protected User $requester;
    protected User $supervisor;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['name' => 'IT Department', 'code' => 'IT']);

        $this->supervisor = User::factory()->create([
            'role' => User::ROLE_SUPERVISOR,
            'department_id' => $dept->id,
        ]);

        $this->requester = User::factory()->create([
            'role' => User::ROLE_REQUESTER,
            'department_id' => $dept->id,
            'supervisor_id' => $this->supervisor->id,
        ]);

        $this->developer = User::factory()->create([
            'name' => 'Developer Ditugaskan',
            'role' => User::ROLE_IT_DEVELOPER,
            'department_id' => $dept->id,
        ]);

        $this->otherDeveloper = User::factory()->create([
            'name' => 'Developer Lain',
            'role' => User::ROLE_IT_DEVELOPER,
            'department_id' => $dept->id,
        ]);
    }

    public function test_assigned_developer_can_log_activity_notes(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0040',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'classification' => Ticket::CLASSIFICATION_MINOR,
            'title' => 'Perbaikan Validasi Form Registrasi',
            'description' => 'Validasi nomor HP tidak memeriksa kode area.',
            'status' => Ticket::STATUS_IN_PROGRESS,
            'assigned_to' => $this->developer->id,
        ]);

        $response = $this->actingAs($this->developer)->post(route('tickets.activities.store', $ticket), [
            'activity_type' => 'code_commit',
            'content' => 'Telah menambahkan regex validasi nomor HP Indonesia di file AuthController.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_activity_logs', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->developer->id,
            'activity_type' => 'code_commit',
            'content' => 'Telah menambahkan regex validasi nomor HP Indonesia di file AuthController.',
        ]);
    }

    public function test_assigned_developer_can_complete_work(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0041',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'classification' => Ticket::CLASSIFICATION_MINOR,
            'title' => 'Export Data Transaksi Harian',
            'description' => 'Penambahan kolom nama kasir pada export excel.',
            'status' => Ticket::STATUS_IN_PROGRESS,
            'assigned_to' => $this->developer->id,
        ]);

        $response = $this->actingAs($this->developer)->post(route('tickets.complete-work', $ticket), [
            'completion_notes' => 'Fitur telah selesai dideploy ke server staging. Kolom nama kasir telah muncul.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $fresh = $ticket->fresh();

        $this->assertSame(Ticket::STATUS_WAITING_USER_REVIEW, $fresh->status);
        $this->assertNotNull($fresh->work_completed_at);

        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'actor_id' => $this->developer->id,
            'action' => 'IT_DEV_COMPLETE_WORK',
            'to_status' => Ticket::STATUS_WAITING_USER_REVIEW,
        ]);
    }

    public function test_other_developer_cannot_log_activity_or_complete_work(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0042',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'classification' => Ticket::CLASSIFICATION_MINOR,
            'title' => 'Optimasi Index Database',
            'description' => 'Query laporan lambat di akhir bulan.',
            'status' => Ticket::STATUS_IN_PROGRESS,
            'assigned_to' => $this->developer->id,
        ]);

        // Other developer tries to log activity
        $responseActivity = $this->actingAs($this->otherDeveloper)->post(route('tickets.activities.store', $ticket), [
            'activity_type' => 'progress_note',
            'content' => 'Catatan penyusup.',
        ]);
        $responseActivity->assertForbidden();

        // Other developer tries to complete work
        $responseComplete = $this->actingAs($this->otherDeveloper)->post(route('tickets.complete-work', $ticket), [
            'completion_notes' => 'Mencoba menandai selesai tiket orang lain.',
        ]);
        $responseComplete->assertForbidden();
    }

    public function test_assigned_developer_can_complete_work_with_short_or_empty_notes(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0043',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Hardware',
            'classification' => Ticket::CLASSIFICATION_MINOR,
            'title' => 'Testing Flow Selesai Cepat',
            'description' => 'Pengujian klik selesai oleh developer.',
            'status' => Ticket::STATUS_IN_PROGRESS,
            'assigned_to' => $this->developer->id,
        ]);

        $response = $this->actingAs($this->developer)->post(route('tickets.complete-work', $ticket), [
            'completion_notes' => '',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertSame(Ticket::STATUS_WAITING_USER_REVIEW, $ticket->fresh()->status);
    }
}
