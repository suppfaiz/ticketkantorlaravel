<?php

namespace App\Http\Controllers;

use App\Models\ExternalLoaTransaction;
use App\Models\Ticket;
use App\Services\Contracts\LoaServiceInterface;
use App\Services\TicketStateMachine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExecutiveLoaApprovalController extends Controller
{
    protected TicketStateMachine $stateMachine;
    protected LoaServiceInterface $loaService;

    public function __construct(TicketStateMachine $stateMachine, LoaServiceInterface $loaService)
    {
        $this->stateMachine = $stateMachine;
        $this->loaService = $loaService;
    }

    /**
     * Kadiv IT & Operasional approves the Major ticket LOA (Tahap C.1).
     */
    public function kadivApprove(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('kadivApprove', $ticket);

        $notes = $request->input('notes') ?? 'Disetujui oleh Kepala Divisi IT dan Operasional';

        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_WAITING_LOA_DIREKSI,
            actor: $request->user(),
            action: 'KADIV_IT_APPROVE',
            remarks: $notes
        );

        // Automatically dispatch to Direksi LOA
        $this->loaService->dispatch(
            $ticket,
            ExternalLoaTransaction::LEVEL_DIREKSI,
            ['role' => 'direksi', 'title' => 'Direksi Persetujuan Proyek Major']
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} telah disetujui oleh Kadiv IT & Operasional dan diteruskan ke Dewan Direksi.");
    }

    /**
     * Kadiv IT & Operasional rejects the Major ticket LOA (Tahap C.1).
     */
    public function kadivReject(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('kadivApprove', $ticket);

        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_REJECTED_KADIV_IT,
            actor: $request->user(),
            action: 'KADIV_IT_REJECT',
            remarks: $request->input('reason')
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} telah ditolak oleh Kepala Divisi IT & Operasional.");
    }

    /**
     * Direksi approves the Major ticket LOA (Tahap C.2).
     */
    public function direksiApprove(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('direksiApprove', $ticket);

        $notes = $request->input('notes') ?? 'Disetujui resmi oleh Dewan Direksi';

        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_WAITING_MAJOR_MEETING,
            actor: $request->user(),
            action: 'DIREKSI_APPROVE',
            remarks: $notes
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} telah disetujui resmi oleh Dewan Direksi. Menunggu pelaksanaan Rapat Koordinasi Major.");
    }

    /**
     * Direksi rejects the Major ticket LOA (Tahap C.2).
     */
    public function direksiReject(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('direksiApprove', $ticket);

        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_REJECTED_DIREKSI,
            actor: $request->user(),
            action: 'DIREKSI_REJECT',
            remarks: $request->input('reason')
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} telah ditolak oleh Dewan Direksi.");
    }
}
