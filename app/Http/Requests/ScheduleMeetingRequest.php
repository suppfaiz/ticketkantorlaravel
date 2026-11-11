<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket && $this->user() && $this->user()->can('scheduleMeeting', $ticket);
    }

    public function rules(): array
    {
        return [
            'meeting_date' => 'required|date',
            'attendees_summary' => 'required|string|min:2',
            'notes' => 'nullable|string',
        ];
    }
}
