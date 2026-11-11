<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewAcceptRequest;
use App\Http\Requests\ReviewReviseRequest;
use App\Models\Ticket;
use App\Models\TicketReview;
use App\Services\TicketStateMachine;
use Illuminate\Http\RedirectResponse;

class TicketReviewController extends Controller
{
    protected TicketStateMachine $stateMachine;

    public function __construct(TicketStateMachine $stateMachine)
    {
        $this->stateMachine = $stateMachine;
    }

    public function accept(ReviewAcceptRequest $request, Ticket $ticket): RedirectResponse
    {
        $feedback = $request->input('feedback') ?? 'Pekerjaan disetujui oleh Pemohon.';
        $rating = $request->input('rating');

        TicketReview::create([
            'ticket_id' => $ticket->id,
            'reviewer_id' => $request->user()->id,
            'decision' => TicketReview::DECISION_ACCEPT,
            'feedback' => $feedback,
            'rating' => $rating,
            'created_at' => now(),
        ]);

        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_COMPLETED,
            actor: $request->user(),
            action: 'USER_REVIEW_ACCEPT',
            remarks: "Rating: {$rating}/5. Ulasan: {$feedback}"
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Terima kasih atas konfirmasi Anda. Tiket {$ticket->ticket_number} resmi ditutup (Selesai).");
    }

    public function revise(ReviewReviseRequest $request, Ticket $ticket): RedirectResponse
    {
        $feedback = $request->input('feedback');

        TicketReview::create([
            'ticket_id' => $ticket->id,
            'reviewer_id' => $request->user()->id,
            'decision' => TicketReview::DECISION_REVISE,
            'feedback' => $feedback,
            'created_at' => now(),
        ]);

        // Invariant FR-020: Developer remains assigned (ticket->assigned_to is NOT reset)
        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_REVISION_WORK,
            actor: $request->user(),
            action: 'USER_REVIEW_REQUEST_REVISION',
            remarks: $feedback
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Permintaan perbaikan telah dikembalikan otomatis kepada pengembang terkait ({$ticket->assignedTo?->name}).");
    }

    public function store(\App\Http\Requests\TicketReviewRequest $request, Ticket $ticket): RedirectResponse
    {
        $action = strtolower($request->input('action'));

        if ($action === 'accept') {
            $feedback = $request->input('feedback') ?? 'Pekerjaan disetujui oleh Pemohon.';
            $rating = $request->input('rating', 5);

            TicketReview::create([
                'ticket_id' => $ticket->id,
                'reviewer_id' => $request->user()->id,
                'decision' => TicketReview::DECISION_ACCEPT,
                'feedback' => $feedback,
                'rating' => $rating,
                'created_at' => now(),
            ]);

            $this->stateMachine->transition(
                ticket: $ticket,
                toStatus: Ticket::STATUS_COMPLETED,
                actor: $request->user(),
                action: 'USER_REVIEW_ACCEPT',
                remarks: "Rating: {$rating}/5. Ulasan: {$feedback}"
            );

            return redirect()->route('tickets.show', $ticket)
                ->with('success', "Terima kasih atas konfirmasi Anda. Tiket {$ticket->ticket_number} resmi ditutup (Selesai).");
        }

        $feedback = $request->input('feedback');

        TicketReview::create([
            'ticket_id' => $ticket->id,
            'reviewer_id' => $request->user()->id,
            'decision' => TicketReview::DECISION_REVISE,
            'feedback' => $feedback,
            'created_at' => now(),
        ]);

        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_REVISION_WORK,
            actor: $request->user(),
            action: 'USER_REVIEW_REQUEST_REVISION',
            remarks: $feedback
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Permintaan perbaikan telah dikembalikan otomatis kepada pengembang terkait ({$ticket->assignedTo?->name}).");
    }
}
