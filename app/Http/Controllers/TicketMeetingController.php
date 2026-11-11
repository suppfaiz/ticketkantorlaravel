<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleMeetingRequest;
use App\Http\Requests\SubmitMeetingMinutesRequest;
use App\Models\MeetingMinutes;
use App\Models\Ticket;
use App\Services\TicketStateMachine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketMeetingController extends Controller
{
    /**
     * Tahap C.3: Penjadwalan & Pelaksanaan Rapat Koordinasi oleh Kabag IT.
     */
    public function scheduleMeeting(
        ScheduleMeetingRequest $request,
        Ticket $ticket,
        TicketStateMachine $stateMachine
    ): RedirectResponse {
        $meeting = MeetingMinutes::updateOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'author_id' => $ticket->requester_id,
                'meeting_date' => $request->input('meeting_date'),
                'attendees' => $request->input('attendees_summary'),
                'attendees_summary' => $request->input('attendees_summary'),
                'scope_summary' => $request->input('notes'),
                'status' => 'Menunggu Notulensi',
            ]
        );

        $stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_WAITING_USER_MINUTES,
            actor: $request->user(),
            action: 'IT_HEAD_RECORD_MEETING',
            remarks: 'Rapat koordinasi teknis dicatat oleh Kepala Bagian IT.'
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Rapat koordinasi berhasil dicatat. Tiket beralih ke tahap menunggu input notulensi oleh pemohon.');
    }

    /**
     * Tahap C.4: Input Notulensi Rapat oleh User Pemohon.
     */
    public function submitMinutes(
        SubmitMeetingMinutesRequest $request,
        Ticket $ticket,
        TicketStateMachine $stateMachine
    ): RedirectResponse {
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('meeting_minutes/' . $ticket->id, 'local');
        }

        $minutes = MeetingMinutes::updateOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'author_id' => $ticket->requester_id,
                'submitted_by_user_id' => $request->user()->id,
                'meeting_date' => $ticket->meetingMinutes?->meeting_date ?? $ticket->meeting_scheduled_at ?? now(),
                'notes' => $request->input('notes'),
                'deliverables' => $request->input('notes'),
                'attachment_path' => $attachmentPath ?? $ticket->meetingMinutes?->attachment_path,
                'status' => MeetingMinutes::STATUS_WAITING_APPROVAL,
            ]
        );

        $stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_WAITING_MINUTES_APPROVAL,
            actor: $request->user(),
            action: 'USER_SUBMIT_MINUTES',
            remarks: 'Notulensi rapat koordinasi telah diserahkan oleh user pemohon.'
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Notulensi rapat berhasil diserahkan dan diteruskan kepada Kepala Bagian IT untuk persetujuan.');
    }

    /**
     * Tahap C.5: Approval Notulensi oleh Kabag IT -> Tiket Major masuk Pool Siap Progress.
     */
    public function approveMinutes(
        Request $request,
        Ticket $ticket,
        TicketStateMachine $stateMachine
    ): RedirectResponse {
        if (! $request->user()->isItHead() && ! $request->user()->isAdmin()) {
            abort(403, 'Hanya Kepala Bagian IT yang berwenang menyetujui notulensi rapat.');
        }

        $minutes = $ticket->meetingMinutes;
        if ($minutes) {
            $minutes->update([
                'is_approved_by_it_head' => true,
                'approved_by' => $request->user()->id,
                'approved_by_it_head_id' => $request->user()->id,
                'approved_at' => now(),
                'status' => MeetingMinutes::STATUS_APPROVED,
                'approval_notes' => $request->input('notes') ?? 'Notulensi disetujui resmi oleh Kabag IT.',
            ]);
        }

        $updates = [];
        if ($request->filled('target_completion_at')) {
            $updates['target_completion_at'] = \Carbon\Carbon::parse($request->input('target_completion_at'))->endOfDay();
        } elseif (! $ticket->target_completion_at && $minutes?->target_completion_date) {
            $updates['target_completion_at'] = \Carbon\Carbon::parse($minutes->target_completion_date)->endOfDay();
        }

        $assignedDev = null;
        if ($request->filled('assigned_to')) {
            $assignedDev = \App\Models\User::where('id', (int) $request->input('assigned_to'))
                ->where('role', \App\Models\User::ROLE_IT_DEVELOPER)
                ->where('is_active', true)
                ->first();
            if ($assignedDev) {
                $updates['assigned_to'] = $assignedDev->id;
            }
        }

        if (! empty($updates)) {
            $ticket->update($updates);
        }

        if ($assignedDev) {
            $stateMachine->transition(
                ticket: $ticket,
                toStatus: Ticket::STATUS_IN_PROGRESS,
                actor: $request->user(),
                action: 'IT_HEAD_APPROVE_MINUTES_AND_ASSIGN',
                remarks: 'Notulensi rapat disetujui oleh Kepala Bagian IT dan tiket langsung ditugaskan ke ' . $assignedDev->name . '.'
            );

            return redirect()->route('tickets.show', $ticket)
                ->with('success', "Notulensi rapat disetujui dan tiket langsung ditugaskan kepada {$assignedDev->name} (Dalam Pengerjaan).");
        }

        $stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_READY_FOR_WORK,
            actor: $request->user(),
            action: 'IT_HEAD_APPROVE_MINUTES',
            remarks: 'Notulensi rapat koordinasi disetujui oleh Kepala Bagian IT. Tiket resmi masuk ke Pool Siap Progress.'
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Notulensi rapat disetujui. Tiket Major resmi masuk ke Pool Siap Progress.');
    }

    /**
     * Tahap C.5: Revisi Notulensi oleh Kabag IT -> Dikembalikan ke user pemohon.
     */
    public function reviseMinutes(
        Request $request,
        Ticket $ticket,
        TicketStateMachine $stateMachine
    ): RedirectResponse {
        if (! $request->user()->isItHead() && ! $request->user()->isAdmin()) {
            abort(403, 'Hanya Kepala Bagian IT yang berwenang meminta revisi notulensi.');
        }

        $request->validate([
            'revision_comments' => 'required|string|min:5',
        ], [
            'revision_comments.required' => 'Catatan perbaikan notulensi wajib diisi agar pemohon mengetahui koreksi yang diperlukan.',
        ]);

        $minutes = $ticket->meetingMinutes;
        if ($minutes) {
            $minutes->update([
                'status' => MeetingMinutes::STATUS_REVISION,
                'revision_comments' => $request->input('revision_comments'),
            ]);
        }

        $stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_REVISION_MINUTES,
            actor: $request->user(),
            action: 'IT_HEAD_REVISE_MINUTES',
            remarks: $request->input('revision_comments')
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Notulensi rapat dikembalikan kepada user pemohon untuk diperbaiki.');
    }
}
