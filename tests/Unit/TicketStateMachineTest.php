<?php

namespace Tests\Unit;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\TicketService;
use App\Services\TicketStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TicketStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected TicketStateMachine $stateMachine;
    protected TicketService $ticketService;
    protected User $requester;
    protected User $supervisor;
    protected User $itHead;
    protected User $otherSupervisor;

    protected function setUp(): void
    {
        parent::setUp();

        $auditService = new AuditTrailService();
        $this->stateMachine = new TicketStateMachine($auditService);
        $this->ticketService = new TicketService($auditService, $this->stateMachine);

        $dept = Department::create(['name' => 'Finance', 'code' => 'FIN']);

        $this->supervisor = User::factory()->create([
            'role' => User::ROLE_SUPERVISOR,
            'department_id' => $dept->id,
        ]);

        $this->otherSupervisor = User::factory()->create([
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
    }

    public function test_ticket_number_generation_format(): void
    {
        $number1 = $this->ticketService->generateTicketNumber();
        $today = now()->format('Ymd');
        $this->assertStringStartsWith("TICK-{$today}-", $number1);
        $this->assertSame("TICK-{$today}-0001", $number1);

        // Create ticket with this number
        Ticket::create([
            'ticket_number' => $number1,
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Hardware',
            'title' => 'Test Hardware',
            'description' => 'Test description long enough',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        $number2 = $this->ticketService->generateTicketNumber();
        $this->assertSame("TICK-{$today}-0002", $number2);
    }

    public function test_valid_supervisor_approval_transition(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => $this->ticketService->generateTicketNumber(),
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Hardware',
            'title' => 'Perbaikan Mouse',
            'description' => 'Mouse tidak bisa klik kiri',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_WAITING_IT_HEAD,
            actor: $this->supervisor,
            action: 'SUPERVISOR_APPROVE',
            remarks: 'Disetujui untuk perbaikan'
        );

        $this->assertSame(Ticket::STATUS_WAITING_IT_HEAD, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->approved_by_supervisor_at);
        $this->assertDatabaseHas('ticket_audit_logs', [
            'ticket_id' => $ticket->id,
            'action' => 'SUPERVISOR_APPROVE',
            'from_status' => Ticket::STATUS_WAITING_SUPERVISOR,
            'to_status' => Ticket::STATUS_WAITING_IT_HEAD,
        ]);
    }

    public function test_unauthorized_supervisor_cannot_transition_subordinate_ticket(): void
    {
        $this->expectException(ValidationException::class);

        $ticket = Ticket::create([
            'ticket_number' => $this->ticketService->generateTicketNumber(),
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Lisensi Excel',
            'description' => 'Lisensi Excel expired',
            'status' => Ticket::STATUS_WAITING_SUPERVISOR,
        ]);

        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_WAITING_IT_HEAD,
            actor: $this->otherSupervisor, // Not the direct supervisor!
            action: 'SUPERVISOR_APPROVE'
        );
    }

    public function test_terminal_status_cannot_be_transitioned(): void
    {
        $this->expectException(ValidationException::class);

        $ticket = Ticket::create([
            'ticket_number' => $this->ticketService->generateTicketNumber(),
            'requester_id' => $this->requester->id,
            'supervisor_id' => $this->supervisor->id,
            'category' => 'Software',
            'title' => 'Permintaan Akses',
            'description' => 'Akses folder finance',
            'status' => Ticket::STATUS_REJECTED_SUPERVISOR,
        ]);

        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_WAITING_SUPERVISOR,
            actor: $this->requester,
            action: 'RESUBMIT'
        );
    }
}
