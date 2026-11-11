<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');
        return $ticket && $this->user() && $this->user()->can('update', $ticket);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:5|max:255',
            'category' => 'required|string|max:100',
            'priority' => 'nullable|string|in:Low,Medium,High,Urgent',
            'description' => 'required|string|min:15',
            'attachments.*' => 'nullable|file|max:10240|mimes:pdf,docx,xlsx,png,jpg,jpeg',
        ];
    }
}
