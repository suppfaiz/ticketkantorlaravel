<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewAcceptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');
        return $ticket && $this->user() && $this->user()->can('reviewTicket', $ticket);
    }

    public function rules(): array
    {
        return [
            'feedback' => 'nullable|string|max:1000',
            'rating' => 'required|integer|between:1,5',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Penilaian bintang kepuasan layanan (1-5) wajib dipilih.',
            'rating.between' => 'Penilaian bintang harus antara 1 sampai 5.',
        ];
    }
}
