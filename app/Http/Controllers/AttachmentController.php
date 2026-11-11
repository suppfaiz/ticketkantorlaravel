<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function download(Request $request, Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        $this->authorize('downloadAttachment', $ticket);

        if ($attachment->ticket_id !== $ticket->id) {
            abort(404, 'Lampiran tidak ditemukan pada tiket ini.');
        }

        if (! Storage::disk('local')->exists($attachment->stored_path)) {
            abort(404, 'Berkas fisik tidak ditemukan di penyimpanan server.');
        }

        return Storage::disk('local')->download(
            $attachment->stored_path,
            $attachment->file_name
        );
    }

    public function downloadMeetingMinutes(Request $request, Ticket $ticket): StreamedResponse
    {
        $this->authorize('view', $ticket);

        $minutes = $ticket->meetingMinutes;
        if (! $minutes || ! $minutes->attachment_path) {
            abort(404, 'Berkas notulensi tidak ditemukan.');
        }

        if (! Storage::disk('local')->exists($minutes->attachment_path)) {
            abort(404, 'Berkas fisik notulensi tidak ditemukan di penyimpanan server.');
        }

        $extension = pathinfo($minutes->attachment_path, PATHINFO_EXTENSION) ?: 'pdf';

        return Storage::disk('local')->download(
            $minutes->attachment_path,
            'Notulensi-' . $ticket->ticket_number . '.' . $extension
        );
    }

    public function destroy(Request $request, Ticket $ticket, TicketAttachment $attachment): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $ticket);

        if ((int) $attachment->ticket_id !== (int) $ticket->id) {
            abort(404, 'Lampiran tidak ditemukan pada tiket ini.');
        }

        $fileName = $attachment->file_name;

        if ($attachment->stored_path && Storage::disk('local')->exists($attachment->stored_path)) {
            Storage::disk('local')->delete($attachment->stored_path);
        }

        $attachment->delete();

        return back()->with('success', "Lampiran '{$fileName}' berhasil dihapus.");
    }
}
