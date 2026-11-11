<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveMeetingMinutesRequest;
use App\Http\Requests\StoreMeetingMinutesRequest;
use App\Models\MeetingMinutes;
use App\Models\Ticket;
use App\Services\TicketStateMachine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class MeetingMinutesController extends Controller
{
    protected TicketStateMachine $stateMachine;

    public function __construct(TicketStateMachine $stateMachine)
    {
        $this->stateMachine = $stateMachine;
    }

    public function create(Ticket $ticket): View
    {
        $this->authorize('manageMeetingMinutes', $ticket);
        $ticket->load(['requester.department', 'supervisor', 'meetingMinutes']);

        return view('meeting-minutes.create', compact('ticket'));
    }

    public function store(StoreMeetingMinutesRequest $request, Ticket $ticket): RedirectResponse
    {
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            /** @var UploadedFile $file */
            $file = $request->file('attachment');
            $attachmentPath = $file->store('meeting_minutes/' . $ticket->id, 'local');
        }

        $minutes = MeetingMinutes::updateOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'author_id' => $request->user()->id,
                'meeting_date' => $request->input('meeting_date'),
                'attendees' => $request->input('attendees'),
                'scope_summary' => $request->input('scope_summary'),
                'deliverables' => $request->input('deliverables'),
                'target_completion_date' => $request->input('target_completion_date'),
                'attachment_path' => $attachmentPath ?? $ticket->meetingMinutes?->attachment_path,
                'status' => MeetingMinutes::STATUS_WAITING_APPROVAL,
            ]
        );

        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_WAITING_MINUTES_APPROVAL,
            actor: $request->user(),
            action: 'SUBMIT_MEETING_MINUTES',
            remarks: 'Notulensi rapat koordinasi Major diajukan untuk persetujuan Kepala Bagian IT.'
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Notulensi rapat koordinasi untuk tiket {$ticket->ticket_number} berhasil disimpan dan diajukan ke Kepala Bagian IT.");
    }

    public function approve(ApproveMeetingMinutesRequest $request, Ticket $ticket): RedirectResponse
    {
        $minutes = $ticket->meetingMinutes;

        if (! $minutes) {
            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Notulensi rapat belum dibuat untuk tiket ini.');
        }

        $minutes->update([
            'status' => MeetingMinutes::STATUS_APPROVED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'approval_notes' => $request->input('approval_notes'),
        ]);

        $ticket->update([
            'approved_by_it_head_at' => now(),
        ]);

        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_READY_FOR_WORK,
            actor: $request->user(),
            action: 'APPROVE_MEETING_MINUTES',
            remarks: $request->input('approval_notes') ?? 'Notulensi disetujui oleh Kepala Bagian IT, tiket masuk antrean Siap Dikerjakan.'
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Notulensi rapat telah disetujui. Tiket {$ticket->ticket_number} resmi masuk antrean Siap Dikerjakan.");
    }
}
