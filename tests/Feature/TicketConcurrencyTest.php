<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $developerA;
    protected User $developerB;
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

        $this->developerA = User::factory()->create([
            'name' => 'Developer Alfa',
            'role' => User::ROLE_IT_DEVELOPER,
            'department_id' => $dept->id,
        ]);

        $this->developerB = User::factory()->create([
            'name' => 'Developer Bravo',
            'role' => User::ROLE_IT_DEVELOPER,
            'department_id' => $dept->id,
        ]);
    }

    public function test_first_developer_succeeds_and_second_developer_is_rejected(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0030',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'classification' => Ticket::CLASSIFICATION_MINOR,
            'title' => 'Bug Sinkronisasi Stok',
            'description' => 'Stok produk tidak berkurang saat checkout sukses.',
            'status' => Ticket::STATUS_READY_FOR_WORK,
            'assigned_to' => null,
        ]);

        // Developer A clicks self-assign
        $responseA = $this->actingAs($this->developerA)->post(route('tickets.self-assign', $ticket));
        $responseA->assertRedirect(route('tickets.show', $ticket));
        $responseA->assertSessionHas('success');

        $fresh = $ticket->fresh();
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $fresh->status);
        $this->assertSame($this->developerA->id, $fresh->assigned_to);
        $this->assertNotNull($fresh->work_started_at);

        // Developer B clicks self-assign on the exact same ticket (race condition simulation)
        // Since policy now checks status === Ready for Work, Developer B gets 403 Forbidden or redirected with error
        $responseB = $this->actingAs($this->developerB)->post(route('tickets.self-assign', $ticket));
        $this->assertTrue(
            $responseB->isForbidden() || $responseB->isRedirect()
        );

        // Ticket remains firmly assigned to Developer A
        $this->assertSame($this->developerA->id, $ticket->fresh()->assigned_to);
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
    }

    public function test_cannot_self_assign_ticket_not_ready_for_work(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0031',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Hardware',
            'title' => 'Ganti Harddisk Rusak',
            'description' => 'Harddisk laptop bad sector.',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR, // Still in Tahap B
        ]);

        $response = $this->actingAs($this->developerA)->post(route('tickets.self-assign', $ticket));
        $response->assertForbidden();
        $this->assertNull($ticket->fresh()->assigned_to);
    }
}
