<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (
            $this->user()->isRequester() ||
            $this->user()->isSupervisor() ||
            $this->user()->isAdmin()
        );
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:5|max:255',
            'category' => 'required|string|max:100',
            'priority' => 'nullable|string|in:Low,Medium,High,Urgent',
            'description' => 'required|string|min:15',
            'is_draft' => 'nullable|boolean',
            'attachments.*' => 'nullable|file|max:10240|mimes:pdf,docx,xlsx,png,jpg,jpeg',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul permohonan wajib diisi.',
            'title.min' => 'Judul permohonan minimal 5 karakter.',
            'category.required' => 'Kategori permohonan wajib dipilih.',
            'description.required' => 'Deskripsi kebutuhan wajib diisi lengkap.',
            'description.min' => 'Deskripsi minimal 15 karakter.',
            'attachments.*.max' => 'Ukuran setiap lampiran maksimal 10 MB.',
            'attachments.*.mimes' => 'Format file lampiran harus PDF, DOCX, XLSX, PNG, JPG, atau JPEG.',
        ];
    }
}
