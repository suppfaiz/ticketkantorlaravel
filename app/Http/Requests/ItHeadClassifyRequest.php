<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItHeadClassifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');
        return $ticket && $this->user() && $this->user()->can('itHeadDecide', $ticket);
    }

    public function rules(): array
    {
        return [
            'classification' => 'required|string|in:Minor,Major',
            'priority' => 'nullable|string|in:Low,Medium,High,Urgent',
            'target_completion_at' => 'nullable|date',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'classification.required' => 'Klasifikasi Minor atau Major wajib dipilih.',
            'classification.in' => 'Klasifikasi hanya boleh Minor atau Major.',
        ];
    }
}
