<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketEventNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Ticket $ticket,
        private readonly string $event,
        private readonly string $title,
        private readonly string $message,
        private readonly ?User $actor = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'ticket_title' => $this->ticket->title,
            'status' => $this->ticket->status,
            'actor_id' => $this->actor?->id,
            'actor_name' => $this->actor?->name ?? 'Sistem NALA',
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}
