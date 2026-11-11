<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteWorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');
        return $ticket && $this->user() && $this->user()->can('completeWork', $ticket);
    }

    public function rules(): array
    {
        return [
            'completion_notes' => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx',
        ];
    }

    public function messages(): array
    {
        return [
            'completion_notes.max' => 'Catatan penyelesaian maksimal 2000 karakter.',
        ];
    }
}
