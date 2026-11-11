<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $requester;

    protected User $supervisor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

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

    public function test_requester_can_submit_valid_ticket_with_attachment(): void
    {
        $file = UploadedFile::fake()->create('dokumen_spesifikasi.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->requester)->post('/tickets', [
            'title' => 'Permohonan Penggantian Keyboard Rusak',
            'category' => 'hardware',
            'priority' => 'Low',
            'description' => 'Keyboard kantor tombol spasi dan enter macet tidak berfungsi sama sekali.',
            'attachments' => [$file],
        ]);

        $ticket = Ticket::where('requester_id', $this->requester->id)->first();
        $this->assertNotNull($ticket);
        $this->assertMatchesRegularExpression('/^TICK-\d{8}-\d{4}$/', $ticket->ticket_number);
        $this->assertEquals(Ticket::STATUS_WAITING_SUPERVISOR, $ticket->status);
        $this->assertEquals($this->supervisor->id, $ticket->supervisor_id);

        $response->assertRedirect("/tickets/{$ticket->id}");
        $this->assertCount(1, $ticket->attachments);
    }

    public function test_ticket_submission_requires_mandatory_fields(): void
    {
        $response = $this->actingAs($this->requester)->post('/tickets', [
            'title' => '',
            'category' => '',
            'description' => 'Pendek',
        ]);

        $response->assertSessionHasErrors(['title', 'category', 'description']);
        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_requester_without_configured_supervisor_is_not_assigned_arbitrarily(): void
    {
        $requester = User::factory()->create([
            'role' => User::ROLE_REQUESTER,
            'department_id' => $this->requester->department_id,
            'supervisor_id' => null,
        ]);

        $response = $this->actingAs($requester)->post('/tickets', [
            'title' => 'Permohonan tanpa atasan terdaftar',
            'category' => 'Software',
            'description' => 'Permohonan ini harus ditolak sampai administrator menetapkan atasan.',
        ]);

        $response->assertSessionHasErrors('supervisor');
        $this->assertNull($requester->fresh()->supervisor_id);
        $this->assertDatabaseMissing('tickets', ['requester_id' => $requester->id]);
    }
}
