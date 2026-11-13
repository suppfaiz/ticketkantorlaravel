<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewAcceptRequest;
use App\Http\Requests\ReviewReviseRequest;
use App\Http\Requests\TicketReviewRequest;
use App\Models\Ticket;
use App\Models\TicketReview;
use App\Models\User;
use App\Services\TicketStateMachine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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

        $this->recordReview($ticket, $request->user(), TicketReview::DECISION_ACCEPT, $feedback, $rating);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Terima kasih atas konfirmasi Anda. Tiket {$ticket->ticket_number} resmi ditutup (Selesai).");
    }

    public function revise(ReviewReviseRequest $request, Ticket $ticket): RedirectResponse
    {
        $feedback = $request->input('feedback');

        $this->recordReview($ticket, $request->user(), TicketReview::DECISION_REVISE, $feedback);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Permintaan perbaikan telah dikembalikan otomatis kepada pengembang terkait ({$ticket->assignedTo?->name}).");
    }

    public function store(TicketReviewRequest $request, Ticket $ticket): RedirectResponse
    {
        $action = strtolower($request->input('action'));

        if ($action === 'accept') {
            $feedback = $request->input('feedback') ?? 'Pekerjaan disetujui oleh Pemohon.';
            $rating = $request->input('rating', 5);

            $this->recordReview($ticket, $request->user(), TicketReview::DECISION_ACCEPT, $feedback, $rating);

            return redirect()->route('tickets.show', $ticket)
                ->with('success', "Terima kasih atas konfirmasi Anda. Tiket {$ticket->ticket_number} resmi ditutup (Selesai).");
        }

        $feedback = $request->input('feedback');

        $this->recordReview($ticket, $request->user(), TicketReview::DECISION_REVISE, $feedback);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Permintaan perbaikan telah dikembalikan otomatis kepada pengembang terkait ({$ticket->assignedTo?->name}).");
    }

    private function recordReview(Ticket $ticket, User $reviewer, string $decision, string $feedback, ?int $rating = null): void
    {
        DB::transaction(function () use ($ticket, $reviewer, $decision, $feedback, $rating) {
            $lockedTicket = Ticket::query()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($reviewer)->authorize('reviewTicket', $lockedTicket);

            TicketReview::create([
                'ticket_id' => $lockedTicket->id,
                'reviewer_id' => $reviewer->id,
                'decision' => $decision,
                'feedback' => $feedback,
                'rating' => $rating,
            ]);

            $accepted = $decision === TicketReview::DECISION_ACCEPT;

            $this->stateMachine->transition(
                ticket: $lockedTicket,
                toStatus: $accepted ? Ticket::STATUS_COMPLETED : Ticket::STATUS_REVISION_WORK,
                actor: $reviewer,
                action: $accepted ? 'USER_REVIEW_ACCEPT' : 'USER_REVIEW_REQUEST_REVISION',
                remarks: $accepted ? "Rating: {$rating}/5. Ulasan: {$feedback}" : $feedback,
            );
        });
    }
}
