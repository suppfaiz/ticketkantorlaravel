<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ($this->user()->isItHead() || $this->user()->isAdmin());
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
