<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupervisorApprovalRequest;
use App\Http\Requests\SupervisorRejectRequest;
use App\Http\Requests\SupervisorReviseRequest;
use App\Models\Ticket;
use App\Services\TicketStateMachine;
use Illuminate\Http\RedirectResponse;

class SupervisorApprovalController extends Controller
{
    protected TicketStateMachine $stateMachine;

    public function __construct(TicketStateMachine $stateMachine)
    {
        $this->stateMachine = $stateMachine;
    }

    public function approve(SupervisorApprovalRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_WAITING_IT_HEAD,
            actor: $request->user(),
            action: 'SUPERVISOR_APPROVE',
            remarks: $request->input('notes') ?? 'Disetujui oleh Atasan Langsung'
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} telah disetujui dan diteruskan ke Kepala Bagian IT.");
    }

    public function reject(SupervisorRejectRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_REJECTED_SUPERVISOR,
            actor: $request->user(),
            action: 'SUPERVISOR_REJECT',
            remarks: $request->input('reason')
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} telah ditolak.");
    }

    public function revise(SupervisorReviseRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_REVISION_REQUESTER,
            actor: $request->user(),
            action: 'SUPERVISOR_REQUEST_REVISION',
            remarks: $request->input('revision_notes')
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} dikembalikan ke pemohon untuk revisi.");
    }
}
