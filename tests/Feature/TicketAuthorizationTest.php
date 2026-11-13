<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $requesterA;
    protected User $requesterB;
    protected User $supervisorA;
    protected User $supervisorB;

    protected function setUp(): void
    {
        parent::setUp();

        $deptA = Department::create(['name' => 'Marketing', 'code' => 'MKT']);
        $deptB = Department::create(['name' => 'Finance', 'code' => 'FIN']);

        $this->supervisorA = User::factory()->create([
            'name' => 'Supervisor A',
            'role' => User::ROLE_SUPERVISOR,
            'department_id' => $deptA->id,
        ]);

        $this->supervisorB = User::factory()->create([
            'name' => 'Supervisor B',
            'role' => User::ROLE_SUPERVISOR,
            'department_id' => $deptB->id,
        ]);

        $this->requesterA = User::factory()->create([
            'name' => 'Requester A',
            'role' => User::ROLE_REQUESTER,
            'department_id' => $deptA->id,
            'supervisor_id' => $this->supervisorA->id,
        ]);

        $this->requesterB = User::factory()->create([
            'name' => 'Requester B',
            'role' => User::ROLE_REQUESTER,
            'department_id' => $deptB->id,
            'supervisor_id' => $this->supervisorB->id,
        ]);
    }

    public function test_user_cannot_view_another_users_private_ticket_idor(): void
    {
        $ticketA = Ticket::create([
            'ticket_number' => 'TICK-20261028-0070',
            'requester_id' => $this->requesterA->id,
            'supervisor_id' => $this->supervisorA->id,
            'category' => 'Software',
            'title' => 'Tiket Rahasia Dept Marketing',
            'description' => 'Data sensitif marketing.',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        // Requester B tries to view Ticket A
        $response = $this->actingAs($this->requesterB)->get(route('tickets.show', $ticketA));
        $response->assertForbidden();
    }

    public function test_user_cannot_download_attachment_from_unauthorized_ticket(): void
    {
        Storage::fake('local');

        $ticketA = Ticket::create([
            'ticket_number' => 'TICK-20261028-0071',
            'requester_id' => $this->requesterA->id,
            'supervisor_id' => $this->supervisorA->id,
            'category' => 'Software',
            'title' => 'Tiket Dokumen Rahasia',
            'description' => 'Lampiran dokumen rahasia.',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        $fakeFile = UploadedFile::fake()->create('rahasia.pdf', 100);
        $path = $fakeFile->store('tickets/' . $ticketA->id, 'local');

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticketA->id,
            'uploader_id' => $this->requesterA->id,
            'file_name' => 'rahasia.pdf',
            'stored_path' => $path,
            'file_size_bytes' => 1000,
            'mime_type' => 'application/pdf',
            'created_at' => now(),
        ]);

        $this->get(route('tickets.attachments.download', [$ticketA, $attachment]))
            ->assertRedirect(route('login'));

        // Requester B tries to download the attachment
        $response = $this->actingAs($this->requesterB)->get(route('tickets.attachments.download', [$ticketA, $attachment]));
        $response->assertForbidden();

        $this->actingAs($this->requesterA)
            ->get(route('tickets.attachments.download', [$ticketA, $attachment]))
            ->assertOk();
    }

    public function test_user_cannot_edit_another_users_ticket(): void
    {
        $ticketA = Ticket::create([
            'ticket_number' => 'TICK-20261028-0072',
            'requester_id' => $this->requesterA->id,
            'supervisor_id' => $this->supervisorA->id,
            'category' => 'Software',
            'title' => 'Tiket A Perlu Revisi',
            'description' => 'Deskripsi lama.',
            'status' => Ticket::STATUS_REVISION_REQUESTER,
        ]);

        // Requester B tries to update Ticket A
        $response = $this->actingAs($this->requesterB)->put(route('tickets.update', $ticketA), [
            'title' => 'Judul Dibajak',
            'category' => 'Software',
            'description' => 'Deskripsi dibajak oleh orang lain.',
        ]);

        $response->assertForbidden();
        $this->assertSame('Tiket A Perlu Revisi', $ticketA->fresh()->title);
    }
}
