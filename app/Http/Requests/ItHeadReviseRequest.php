<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItHeadReviseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');
        return $ticket && $this->user() && $this->user()->can('itHeadDecide', $ticket);
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
            'revision_notes.required' => 'Catatan instruksi revisi dari Kepala Bagian IT wajib diisi.',
            'revision_notes.min' => 'Catatan revisi minimal 2 karakter.',
        ];
    }
}
