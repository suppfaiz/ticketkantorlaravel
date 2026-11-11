<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveMeetingMinutesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');
        return $ticket && $this->user() && $this->user()->can('approveMeetingMinutes', $ticket);
    }

    public function rules(): array
    {
        return [
            'approval_notes' => 'nullable|string|max:500',
        ];
    }
}
