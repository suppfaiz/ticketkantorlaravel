@props([
    'ticket',
    'number' => null,
    'showSelfAssign' => false,
])

@php
    $catLower = strtolower($ticket->category ?? '');
    $rawStatus = trim((string) ($ticket->status ?? ''));
    $statusMap = [
        'closed' => 'Selesai',
        'completed' => 'Selesai',
        'selesai' => 'Selesai',
        'open' => 'Menunggu Diproses',
        'in_progress' => 'Dalam Pengerjaan',
        'in progress' => 'Dalam Pengerjaan',
        'rejected' => 'Ditolak',
        'waiting_supervisor' => 'Menunggu Atasan',
        'waiting_it_head' => 'Menunggu Kabag IT',
        'menunggu approval kepala bagian it' => 'Menunggu Kabag IT',
        'menunggu approval atasan' => 'Menunggu Atasan',
        'revisi requester oleh kepala bagian it' => 'Perlu Revisi',
        'revisi requester' => 'Perlu Revisi',
    ];
    $cleanStatus = $statusMap[strtolower($rawStatus)] ?? $rawStatus;
@endphp

<div class="bg-white rounded-2xl border border-slate-200/80 px-4 py-3.5 shadow-2xs active:bg-slate-50 transition-colors">
    <a href="{{ route('tickets.show', $ticket) }}" class="flex items-center gap-3.5">
        <!-- Simple Neutral Icon Box -->
        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
            @if(str_contains($catLower, 'hardware'))
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            @elseif(str_contains($catLower, 'jaringan'))
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                </svg>
            @elseif(str_contains($catLower, 'akses'))
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
            @elseif(str_contains($catLower, 'perubahan'))
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
            @endif
        </div>

        <!-- Center: Title & Clean Subtitle (No Multi-Color Pills) -->
        <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-slate-900 truncate">
                    {{ $ticket->title }}
                </h3>
                <span class="text-[11px] text-slate-400 tabular-nums shrink-0">
                    {{ $ticket->created_at->format('d M') }}
                </span>
            </div>

            <div class="flex items-center justify-between gap-2 mt-1">
                <p class="text-xs text-slate-500 truncate">
                    {{ $ticket->category ? ucfirst($ticket->category) : 'Umum' }} &bull; {{ $cleanStatus }}
                    @if($ticket->isOverdue())
                        <span class="text-rose-600 font-semibold">&bull; Overdue ({{ $ticket->target_completion_at->format('d M') }})</span>
                    @elseif($ticket->target_completion_at)
                        <span class="text-slate-400">&bull; Target {{ $ticket->target_completion_at->format('d M') }}</span>
                    @endif
                </p>
                <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        </div>
    </a>

    @if($showSelfAssign)
        <div class="mt-2.5 pt-2.5 border-t border-slate-100">
            <form action="{{ route('tickets.self-assign', $ticket) }}" method="POST">
                @csrf
                <button type="submit" class="w-full py-1.5 px-3 rounded-xl bg-[#1E40AF] text-white text-xs font-semibold cursor-pointer">
                    Ambil Tiket
                </button>
            </form>
        </div>
    @endif
</div>
