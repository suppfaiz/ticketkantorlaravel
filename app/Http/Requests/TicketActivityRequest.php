<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');
        return $this->user() !== null && $ticket->assigned_to === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'activity_note' => 'nullable|string',
            'content' => 'nullable|string',
            'activity_type' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,docx,xlsx,png,jpg,jpeg',
        ];
    }
}
