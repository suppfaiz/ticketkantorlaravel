<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewReviseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');
        return $ticket && $this->user() && $this->user()->can('reviewTicket', $ticket);
    }

    public function rules(): array
    {
        return [
            'feedback' => 'required|string|min:2|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'feedback.required' => 'Catatan bagian pekerjaan yang belum sesuai wajib diisi agar pengembang dapat memperbaikinya.',
            'feedback.min' => 'Catatan revisi minimal 2 karakter.',
        ];
    }
}
