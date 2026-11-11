@php
    $canSubmitMinutes = ($ticket->requester_id === auth()->id() || auth()->user()->isItHead() || auth()->user()->isAdmin()) &&
        in_array($ticket->status, [
            \App\Models\Ticket::STATUS_WAITING_USER_MINUTES,
            \App\Models\Ticket::STATUS_REVISION_MINUTES
        ], true);
@endphp

@if($canSubmitMinutes)
<div class="pt-2">
    <a href="{{ route('tickets.meeting-minutes.create', $ticket) }}" class="btn-primary text-xs py-2.5 px-4 inline-flex items-center gap-2 shadow-xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        <span>{{ $ticket->status === \App\Models\Ticket::STATUS_REVISION_MINUTES ? 'Buka Halaman Perbaikan Notulensi' : 'Buka Halaman Input Notulensi Rapat' }} &rarr;</span>
    </a>

    @if($ticket->status === \App\Models\Ticket::STATUS_REVISION_MINUTES && $ticket->meetingMinutes?->revision_comments)
        <div class="mt-3 p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 shadow-xs">
            <div class="font-bold flex items-center gap-1.5 mb-1 text-slate-900">
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Catatan Revisi dari Kabag IT:</span>
            </div>
            <p class="leading-relaxed text-slate-700">{{ $ticket->meetingMinutes->revision_comments }}</p>
        </div>
    @endif
</div>
@endif
