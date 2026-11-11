<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketActivityLog;
use App\Services\AuditTrailService;
use App\Services\TicketNotificationService;
use App\Services\TicketStateMachine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketAssignmentController extends Controller
{
    protected AuditTrailService $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function selfAssign(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('selfAssign', $ticket);
        $user = $request->user();

        // Concurrency-safe atomic conditional SQL update
        $affected = Ticket::where('id', $ticket->id)
            ->where('status', Ticket::STATUS_READY_FOR_WORK)
            ->whereNull('assigned_to')
            ->update([
                'assigned_to' => $user->id,
                'status' => Ticket::STATUS_IN_PROGRESS,
                'work_started_at' => now(),
                'updated_at' => now(),
            ]);

        if ($affected === 0) {
            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Tiket ini baru saja diambil oleh anggota Tim IT yang lain.');
        }

        $freshTicket = $ticket->fresh();

        $this->auditTrailService->log(
            ticket: $freshTicket,
            actor: $user,
            action: 'IT_DEV_SELF_ASSIGN',
            fromStatus: Ticket::STATUS_READY_FOR_WORK,
            toStatus: Ticket::STATUS_IN_PROGRESS,
            remarks: "Tiket diambil mandiri (self-assign) oleh {$user->name}."
        );

        app(TicketNotificationService::class)->statusChanged(
            $freshTicket,
            Ticket::STATUS_IN_PROGRESS,
            $user,
            "Tiket mulai dikerjakan oleh {$user->name}.",
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Anda berhasil mengambil tiket {$ticket->ticket_number}. Tiket kini berstatus Dalam Pengerjaan.");
    }

    /**
     * Kembalikan tiket yang sedang dikerjakan kembali ke Pool Antrean (Siap Dikerjakan).
     */
    public function releaseToPool(
        Request $request,
        Ticket $ticket,
        TicketStateMachine $stateMachine
    ): RedirectResponse {
        $this->authorize('releaseToPool', $ticket);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reason.required' => 'Alasan pelepasan tiket ke antrean wajib diisi.',
            'reason.min' => 'Alasan pelepasan minimal 5 karakter.',
        ]);

        $actor = $request->user();
        $reason = trim($validated['reason']);
        $previousDevName = $ticket->assignedTo?->name ?? $actor->name;

        TicketActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'activity_type' => 'release_to_pool',
            'content' => "Tiket dikembalikan ke Pool Antrean oleh {$actor->name}. Alasan: {$reason}",
            'activity_note' => "Tiket dikembalikan ke Pool Antrean oleh {$actor->name}. Alasan: {$reason}",
            'created_at' => now(),
        ]);

        $ticket->update(['assigned_to' => null]);

        $stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_READY_FOR_WORK,
            actor: $actor,
            action: 'IT_DEV_RELEASE_TO_POOL',
            remarks: "Penugasan {$previousDevName} dilepas kembali ke Pool Siap Dikerjakan. Alasan: {$reason}"
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} telah dikembalikan ke Pool Antrean (Siap Dikerjakan).");
    }
}
