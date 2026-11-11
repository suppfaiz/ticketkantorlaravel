<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeetingMinutesPolicy
{
    use HandlesAuthorization;

    public function scheduleMeeting(User $user, Ticket $ticket): bool
    {
        return ($user->isItHead() || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_MAJOR_MEETING;
    }

    public function submitMinutes(User $user, Ticket $ticket): bool
    {
        return ($ticket->requester_id === $user->id || $user->isAdmin()) &&
               in_array($ticket->status, [
                   Ticket::STATUS_WAITING_USER_MINUTES,
                   Ticket::STATUS_REVISION_MINUTES,
                   Ticket::STATUS_WAITING_MAJOR_MEETING,
                   Ticket::STATUS_WAITING_MINUTES,
               ], true);
    }

    public function approveMinutes(User $user, Ticket $ticket): bool
    {
        return ($user->isItHead() || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_MINUTES_APPROVAL;
    }

    public function reviseMinutes(User $user, Ticket $ticket): bool
    {
        return ($user->isItHead() || $user->isAdmin()) &&
               $ticket->status === Ticket::STATUS_WAITING_MINUTES_APPROVAL;
    }
}
