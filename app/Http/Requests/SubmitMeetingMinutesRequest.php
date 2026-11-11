<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitMeetingMinutesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket && $this->user() && $this->user()->can('manageMeetingMinutes', $ticket);
    }

    public function rules(): array
    {
        return [
            'notes' => 'required|string|min:2',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,docx,xlsx,png,jpg,jpeg',
        ];
    }
}
