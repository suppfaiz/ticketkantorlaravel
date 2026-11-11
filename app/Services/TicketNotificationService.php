<?php

namespace App\Services;

use App\Mail\TicketStatusMail;
use App\Models\ApprovalDelegation;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketEventNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TicketNotificationService
{
    public function __construct(private readonly ApprovalDelegationService $delegations) {}

    public function statusChanged(Ticket $ticket, string $status, ?User $actor = null, ?string $remarks = null): void
    {
        $ticket->loadMissing(['requester', 'supervisor', 'assignedTo']);
        $recipients = $this->recipientsForStatus($ticket, $status);

        $this->notifyUsers(
            ticket: $ticket,
            recipients: $recipients,
            event: 'status_changed',
            title: 'Status tiket diperbarui',
            message: $remarks ?: "Status tiket berubah menjadi {$status}.",
            actor: $actor,
            sendEmail: true,
        );
    }

    public function notifyUsers(
        Ticket $ticket,
        Collection|array $recipients,
        string $event,
        string $title,
        string $message,
        ?User $actor = null,
        bool $sendEmail = false,
    ): void {
        collect($recipients)
            ->filter(fn ($recipient) => $recipient instanceof User && $recipient->is_active)
            ->unique('id')
            ->reject(fn (User $recipient) => $actor && $recipient->id === $actor->id)
            ->each(function (User $recipient) use ($ticket, $event, $title, $message, $actor, $sendEmail) {
                try {
                    $recipient->notify(new TicketEventNotification($ticket, $event, $title, $message, $actor));

                    if ($sendEmail && filled($recipient->email)) {
                        Mail::to($recipient->email)->send(new TicketStatusMail($ticket, $recipient->name, $actor, $message));
                    }
                } catch (\Throwable $exception) {
                    Log::warning('Gagal membuat notifikasi tiket.', [
                        'ticket_id' => $ticket->id,
                        'recipient_id' => $recipient->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });
    }

    public function recipientsForStatus(Ticket $ticket, string $status): Collection
    {
        if ($status === Ticket::STATUS_WAITING_SUPERVISOR && $ticket->supervisor) {
            return collect([$this->delegations->effectiveApprover($ticket->supervisor, ApprovalDelegation::SCOPE_SUPERVISOR)]);
        }

        if (in_array($status, [Ticket::STATUS_WAITING_IT_HEAD, Ticket::STATUS_WAITING_MAJOR_MEETING, Ticket::STATUS_WAITING_MINUTES_APPROVAL], true)) {
            return $this->roleApprovers(User::ROLE_IT_HEAD, ApprovalDelegation::SCOPE_IT_HEAD);
        }

        if ($status === Ticket::STATUS_WAITING_LOA_KADIV_IT) {
            return $this->roleApprovers(User::ROLE_KADIV_IT, ApprovalDelegation::SCOPE_KADIV_IT);
        }

        if ($status === Ticket::STATUS_WAITING_LOA_DIREKSI) {
            return $this->roleApprovers(User::ROLE_DIREKSI, ApprovalDelegation::SCOPE_DIREKSI);
        }

        if ($status === Ticket::STATUS_READY_FOR_WORK) {
            return User::where('role', User::ROLE_IT_DEVELOPER)->where('is_active', true)->get();
        }

        if ($status === Ticket::STATUS_IN_PROGRESS) {
            return collect([$ticket->assignedTo, $ticket->requester])->filter();
        }

        if ($status === Ticket::STATUS_REVISION_WORK && $ticket->assignedTo) {
            return collect([$ticket->assignedTo]);
        }

        if ($status === Ticket::STATUS_COMPLETED) {
            return collect([$ticket->assignedTo])->filter()->merge($this->roleApprovers(User::ROLE_IT_HEAD, ApprovalDelegation::SCOPE_IT_HEAD));
        }

        return collect([$ticket->requester])->filter();
    }

    private function roleApprovers(string $role, string $scope): Collection
    {
        $users = User::where('role', $role)->where('is_active', true)->get();

        return $this->delegations->effectiveApprovers($users, $scope);
    }
}
