<?php

namespace App\Policies;

use App\Models\ApprovalDelegation;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ApprovalDelegationService;
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

        if ($this->canActForSupervisor($user, $ticket)) {
            return true;
        }

        if ($this->canActAsItHead($user) || $this->canActAsRole($user, User::ROLE_KADIV_IT, ApprovalDelegation::SCOPE_KADIV_IT) || $this->canActAsRole($user, User::ROLE_DIREKSI, ApprovalDelegation::SCOPE_DIREKSI)) {
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
        return ($this->canActForSupervisor($user, $ticket) || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_SUPERVISOR;
    }

    public function supervisorReject(User $user, Ticket $ticket): bool
    {
        return ($this->canActForSupervisor($user, $ticket) || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_SUPERVISOR;
    }

    public function supervisorRevise(User $user, Ticket $ticket): bool
    {
        return ($this->canActForSupervisor($user, $ticket) || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_SUPERVISOR;
    }

    public function itHeadDecide(User $user, Ticket $ticket): bool
    {
        return ($this->canActAsItHead($user) || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_IT_HEAD;
    }

    public function kadivApprove(User $user, Ticket $ticket): bool
    {
        return ($this->canActAsRole($user, User::ROLE_KADIV_IT, ApprovalDelegation::SCOPE_KADIV_IT) || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_LOA_KADIV_IT;
    }

    public function direksiApprove(User $user, Ticket $ticket): bool
    {
        return ($this->canActAsRole($user, User::ROLE_DIREKSI, ApprovalDelegation::SCOPE_DIREKSI) || $user->isAdmin()) &&
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
        return ($this->canActAsItHead($user) || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_MAJOR_MEETING;
    }

    public function manageMeetingMinutes(User $user, Ticket $ticket): bool
    {
        return ($this->canActAsItHead($user) || $user->isAdmin() || $ticket->requester_id === $user->id) &&
               in_array($ticket->status, [
                   Ticket::STATUS_WAITING_MAJOR_MEETING,
                   Ticket::STATUS_WAITING_MINUTES,
                   Ticket::STATUS_WAITING_USER_MINUTES,
                   Ticket::STATUS_REVISION_MINUTES,
               ], true);
    }

    public function approveMeetingMinutes(User $user, Ticket $ticket): bool
    {
        return ($this->canActAsItHead($user) || $user->isAdmin()) &&
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

    private function canActForSupervisor(User $user, Ticket $ticket): bool
    {
        return $ticket->supervisor
            && app(ApprovalDelegationService::class)->canActFor(
                $user,
                $ticket->supervisor,
                ApprovalDelegation::SCOPE_SUPERVISOR,
            );
    }

    private function canActAsItHead(User $user): bool
    {
        return $this->canActAsRole($user, User::ROLE_IT_HEAD, ApprovalDelegation::SCOPE_IT_HEAD);
    }

    private function canActAsRole(User $user, string $role, string $scope): bool
    {
        return app(ApprovalDelegationService::class)->actsForRole($user, $role, $scope);
    }
}
