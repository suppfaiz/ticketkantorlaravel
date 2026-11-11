<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItHeadDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ($this->user()->isItHead() || $this->user()->isAdmin());
    }

    public function rules(): array
    {
        return [
            'decision' => 'required|string|in:minor_approved,major_approved,revision,rejected,Minor,Major',
            'classification' => 'nullable|string|in:Minor,Major,minor,major',
            'priority' => 'nullable|string|in:Low,Medium,High,Urgent',
            'notes' => 'nullable|string',
            'reason' => 'nullable|string',
            'revision_notes' => 'nullable|string',
        ];
    }
}
