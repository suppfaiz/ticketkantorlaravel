<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');
        return $ticket && $this->user() && $this->user()->can('addActivityLog', $ticket);
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|min:1|max:2000',
            'activity_type' => 'nullable|string|in:progress_note,code_commit,test_result,deployment',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Catatan progres penanganan wajib diisi.',
            'content.min' => 'Catatan progres wajib diisi.',
        ];
    }
}
