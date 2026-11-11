<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupervisorRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');
        return $ticket && $this->user() && $this->user()->can('supervisorReject', $ticket);
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|min:2|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan penolakan wajib diisi.',
            'reason.min' => 'Alasan penolakan minimal 2 karakter.',
        ];
    }
}
