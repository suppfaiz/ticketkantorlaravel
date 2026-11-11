<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConcurrencySelfAssignTest extends TestCase
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

    public function test_atomic_self_assign_prevents_duplicate_assignment(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0050',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'hardware',
            'title' => 'Penggantian RAM Server',
            'description' => 'RAM server database mengalami ecc error berkala.',
            'status' => Ticket::STATUS_READY_FOR_WORK,
        ]);

        // Developer A claims ticket
        $responseA = $this->actingAs($this->developerA)->post("/tickets/{$ticket->id}/self-assign");
        $responseA->assertRedirect("/tickets/{$ticket->id}");

        $ticketFresh = $ticket->fresh();
        $this->assertEquals($this->developerA->id, $ticketFresh->assigned_to);
        $this->assertEquals(Ticket::STATUS_IN_PROGRESS, $ticketFresh->status);
        $this->assertNotNull($ticketFresh->work_started_at);

        // Developer B tries to claim the same ticket
        $responseB = $this->actingAs($this->developerB)->post("/tickets/{$ticket->id}/self-assign");
        $this->assertTrue($responseB->isForbidden() || $responseB->isRedirect());

        // Firmly assigned to Developer A with zero conflict
        $this->assertEquals($this->developerA->id, $ticket->fresh()->assigned_to);
    }

    public function test_non_developer_cannot_self_assign_ticket(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => 'TICK-20261028-0051',
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'software',
            'title' => 'Bug Reporting Form',
            'description' => 'Error 500 saat download CSV.',
            'status' => Ticket::STATUS_READY_FOR_WORK,
        ]);

        $response = $this->actingAs($this->requester)->post("/tickets/{$ticket->id}/self-assign");
        $response->assertStatus(403);
    }
}
