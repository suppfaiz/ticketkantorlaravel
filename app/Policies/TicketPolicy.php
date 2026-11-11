<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin() || $user->isItHead() || $user->isKadivIt() || $user->isDireksi()) {
            return true;
        }

        if ($ticket->requester_id === $user->id) {
            return true;
        }

        if ($ticket->supervisor_id === $user->id) {
            return true;
        }

        if ($user->isItDeveloper()) {
            // Developers can view tickets ready for development or already assigned
            return in_array($ticket->status, [
                Ticket::STATUS_READY_FOR_WORK,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_WAITING_USER_REVIEW,
                Ticket::STATUS_REVISION_WORK,
                Ticket::STATUS_COMPLETED,
            ], true) || $ticket->assigned_to === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isRequester() || $user->isSupervisor() || $user->isAdmin();
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return ($ticket->requester_id === $user->id || $user->isAdmin()) && in_array($ticket->status, [
            Ticket::STATUS_DRAFT,
            Ticket::STATUS_REVISION_REQUESTER,
            Ticket::STATUS_REVISION_BY_IT_HEAD,
        ], true);
    }

    public function cancel(User $user, Ticket $ticket): bool
    {
        return ($ticket->requester_id === $user->id || $user->isAdmin()) && in_array($ticket->status, [
            Ticket::STATUS_DRAFT,
            Ticket::STATUS_WAITING_SUPERVISOR,
            Ticket::STATUS_REVISION_REQUESTER,
            Ticket::STATUS_WAITING_IT_HEAD,
            Ticket::STATUS_REVISION_BY_IT_HEAD,
        ], true);
    }

    public function manageAssignmentAndTimeline(User $user, Ticket $ticket): bool
    {
        return ($user->isItHead() || $user->isAdmin()) && ! $ticket->isTerminal();
    }

    public function supervisorApprove(User $user, Ticket $ticket): bool
    {
        return ($ticket->supervisor_id === $user->id || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_SUPERVISOR;
    }

    public function supervisorReject(User $user, Ticket $ticket): bool
    {
        return ($ticket->supervisor_id === $user->id || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_SUPERVISOR;
    }

    public function supervisorRevise(User $user, Ticket $ticket): bool
    {
        return ($ticket->supervisor_id === $user->id || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_SUPERVISOR;
    }

    public function itHeadDecide(User $user, Ticket $ticket): bool
    {
        return ($user->isItHead() || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_IT_HEAD;
    }

    public function kadivApprove(User $user, Ticket $ticket): bool
    {
        return ($user->isKadivIt() || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_LOA_KADIV_IT;
    }

    public function direksiApprove(User $user, Ticket $ticket): bool
    {
        return ($user->isDireksi() || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_LOA_DIREKSI;
    }

    public function reviewItHead(User $user, Ticket $ticket): bool
    {
        return $this->itHeadDecide($user, $ticket);
    }

    public function claim(User $user, Ticket $ticket): bool
    {
        return $this->selfAssign($user, $ticket);
    }

    public function updateProgress(User $user, Ticket $ticket): bool
    {
        return $this->addActivityLog($user, $ticket);
    }

    public function reviewWork(User $user, Ticket $ticket): bool
    {
        return $this->reviewTicket($user, $ticket);
    }

    public function scheduleMeeting(User $user, Ticket $ticket): bool
    {
        return ($user->isItHead() || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_MAJOR_MEETING;
    }

    public function manageMeetingMinutes(User $user, Ticket $ticket): bool
    {
        return ($user->isItHead() || $user->isAdmin() || $ticket->requester_id === $user->id) &&
               in_array($ticket->status, [
                   Ticket::STATUS_WAITING_MAJOR_MEETING,
                   Ticket::STATUS_WAITING_MINUTES,
                   Ticket::STATUS_WAITING_USER_MINUTES,
                   Ticket::STATUS_REVISION_MINUTES,
               ], true);
    }

    public function approveMeetingMinutes(User $user, Ticket $ticket): bool
    {
        return ($user->isItHead() || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_MINUTES_APPROVAL;
    }

    public function selfAssign(User $user, Ticket $ticket): bool
    {
        return $user->isItDeveloper() &&
               $ticket->status === Ticket::STATUS_READY_FOR_WORK &&
               $ticket->assigned_to === null;
    }

    public function addActivityLog(User $user, Ticket $ticket): bool
    {
        return $ticket->assigned_to === $user->id &&
               in_array($ticket->status, [
                   Ticket::STATUS_IN_PROGRESS,
                   Ticket::STATUS_REVISION_WORK,
               ], true);
    }

    public function completeWork(User $user, Ticket $ticket): bool
    {
        return $ticket->assigned_to === $user->id &&
               in_array($ticket->status, [
                   Ticket::STATUS_IN_PROGRESS,
                   Ticket::STATUS_REVISION_WORK,
               ], true);
    }

    public function releaseToPool(User $user, Ticket $ticket): bool
    {
        return ($ticket->assigned_to === $user->id || $user->isItHead() || $user->isAdmin()) &&
               in_array($ticket->status, [
                   Ticket::STATUS_IN_PROGRESS,
                   Ticket::STATUS_REVISION_WORK,
               ], true);
    }

    public function reviewTicket(User $user, Ticket $ticket): bool
    {
        return ($ticket->requester_id === $user->id || $user->isItHead() || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_USER_REVIEW;
    }

    public function downloadAttachment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
