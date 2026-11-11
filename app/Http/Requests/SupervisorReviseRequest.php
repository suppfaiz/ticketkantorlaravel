<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupervisorReviseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');
        return $ticket && $this->user() && $this->user()->can('supervisorRevise', $ticket);
    }

    public function rules(): array
    {
        return [
            'revision_notes' => 'required|string|min:2|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'revision_notes.required' => 'Catatan revisi wajib diisi untuk memberi panduan perbaikan ke pemohon.',
            'revision_notes.min' => 'Catatan revisi minimal 2 karakter.',
        ];
    }
}
