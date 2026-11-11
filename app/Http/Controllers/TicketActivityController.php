<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteWorkRequest;
use App\Http\Requests\StoreActivityLogRequest;
use App\Models\Ticket;
use App\Models\TicketActivityLog;
use App\Services\AuditTrailService;
use App\Services\TicketStateMachine;
use Illuminate\Http\RedirectResponse;

class TicketActivityController extends Controller
{
    protected TicketStateMachine $stateMachine;
    protected AuditTrailService $auditTrailService;

    public function __construct(TicketStateMachine $stateMachine, AuditTrailService $auditTrailService)
    {
        $this->stateMachine = $stateMachine;
        $this->auditTrailService = $auditTrailService;
    }

    public function store(StoreActivityLogRequest $request, Ticket $ticket): RedirectResponse
    {
        $content = trim((string) $request->input('content'));

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $storedPath = $file->store('tickets/' . $ticket->id, 'local');
            \App\Models\TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'uploader_id' => $request->user()->id,
                'file_name' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'file_size_bytes' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'created_at' => now(),
            ]);
            $content .= "\n[Lampiran Progres: " . $file->getClientOriginalName() . ']';
        }

        TicketActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'activity_type' => $request->input('activity_type', 'progress_note'),
            'content' => $content,
            'created_at' => now(),
        ]);

        $this->auditTrailService->log(
            ticket: $ticket,
            actor: $request->user(),
            action: 'ADD_ACTIVITY_NOTE',
            fromStatus: $ticket->status,
            toStatus: $ticket->status,
            remarks: 'Menambahkan catatan perkembangan teknis.'
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Catatan aktivitas teknis berhasil ditambahkan.');
    }

    public function complete(CompleteWorkRequest $request, Ticket $ticket): RedirectResponse
    {
        $completionNotes = trim((string) $request->input('completion_notes'));
        if ($completionNotes === '') {
            $completionNotes = 'Pekerjaan telah diselesaikan oleh tim IT Developer.';
        }

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $storedPath = $file->store('tickets/' . $ticket->id, 'local');
            \App\Models\TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'uploader_id' => $request->user()->id,
                'file_name' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'file_size_bytes' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'created_at' => now(),
            ]);
            $completionNotes .= "\n[Bukti Penyelesaian: " . $file->getClientOriginalName() . ']';
        }

        // Record work completion activity log
        TicketActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'activity_type' => 'work_completion',
            'content' => $completionNotes,
            'created_at' => now(),
        ]);

        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_WAITING_USER_REVIEW,
            actor: $request->user(),
            action: 'IT_DEV_COMPLETE_WORK',
            remarks: $completionNotes
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Pengerjaan tiket {$ticket->ticket_number} telah selesai dan diteruskan ke Pemohon untuk tahap review akhir.");
    }
}
