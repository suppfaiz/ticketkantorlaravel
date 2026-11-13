<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket && $this->user()
            && $ticket->requester_id === $this->user()->id
            && $this->user()->can('reviewTicket', $ticket);
    }

    public function rules(): array
    {
        return [
            'action' => 'required|string|in:accept,revision,Accept,Revisi',
            'rating' => 'required_if:action,accept,Accept|nullable|integer|min:1|max:5',
            'feedback' => 'required_if:action,revision,Revisi|nullable|string',
        ];
    }
}
