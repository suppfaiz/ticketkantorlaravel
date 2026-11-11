<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingMinutesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');
        return $ticket && $this->user() && $this->user()->can('manageMeetingMinutes', $ticket);
    }

    public function rules(): array
    {
        return [
            'meeting_date' => 'required|date|before_or_equal:today',
            'attendees' => 'required|string|min:5|max:1000',
            'scope_summary' => 'required|string|min:20',
            'deliverables' => 'required|string|min:10',
            'target_completion_date' => 'nullable|date|after_or_equal:meeting_date',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,docx,xlsx,png,jpg,jpeg',
        ];
    }

    public function messages(): array
    {
        return [
            'meeting_date.required' => 'Tanggal pelaksanaan rapat wajib diisi.',
            'attendees.required' => 'Daftar peserta rapat wajib diisi.',
            'scope_summary.required' => 'Ringkasan kesepakatan ruang lingkup wajib diisi lengkap.',
            'deliverables.required' => 'Daftar deliverable / hasil kerja wajib dirinci.',
        ];
    }
}
