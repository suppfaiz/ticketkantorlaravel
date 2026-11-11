<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public Ticket $ticket;

    public string $recipientName;

    public string $actorName;

    public ?string $remarks;

    public function __construct(Ticket $ticket, string $recipientName, ?User $actor = null, ?string $remarks = null)
    {
        $this->ticket = $ticket;
        $this->recipientName = $recipientName;
        $this->actorName = $actor?->name ?? 'Sistem Helpdesk IT';
        $this->remarks = $remarks;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[NALA Helpdesk] {$this->ticket->ticket_number} — {$this->ticket->status}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-status',
        );
    }
}
