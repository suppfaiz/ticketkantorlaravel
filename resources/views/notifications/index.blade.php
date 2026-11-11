<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 py-1">
            <div class="min-w-0">
                <h1 class="text-base sm:text-2xl font-bold text-slate-900 tracking-tight truncate">Semua Notifikasi &amp; Antrean</h1>
                <p class="hidden sm:block text-xs text-slate-500 mt-0.5">Daftar lengkap tiket dan pembaruan status yang memerlukan perhatian Anda</p>
            </div>
            <div class="hidden sm:block shrink-0">
                <a href="{{ route('dashboard') }}" class="btn-secondary text-xs py-2 px-3.5 inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke Dashboard</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div data-ajax-poll="notifications-list" class="sm:bg-white sm:rounded-xl sm:border sm:border-slate-200 sm:shadow-xs sm:overflow-hidden">
        <div class="px-1 sm:px-6 pb-3 sm:py-3.5 sm:border-b sm:border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Daftar Notifikasi ({{ $notifications->total() }})</h2>
        </div>

        @if($notifications->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200/80 sm:border-0 py-12 px-4 text-center">
                <p class="text-sm font-medium text-slate-700">Tidak ada notifikasi</p>
                <p class="text-xs text-slate-400 mt-1">Seluruh antrean dan notifikasi tiket Anda saat ini sudah bersih.</p>
            </div>
        @else
            <!-- Mobile Native Separated Cards View (Handphone Only) -->
            <div class="sm:hidden space-y-2.5">
                @foreach($notifications as $ticket)
                    <x-mobile-ticket-card :ticket="$ticket" />
                @endforeach
            </div>

            <!-- Desktop List View (Identical on sm: and up) -->
            <div class="hidden sm:block divide-y divide-slate-100">
                @foreach($notifications as $ticket)
                    <a href="{{ route('tickets.show', $ticket) }}" class="px-6 py-3.5 flex items-center justify-between gap-3 hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-3.5 min-w-0 flex-1">
                            <span class="w-7 h-7 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 inline-flex items-center justify-center tabular-nums text-xs font-semibold shrink-0">
                                {{ ($notifications->firstItem() ?? 1) + $loop->index }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-slate-900 truncate">
                                    {{ $ticket->title }}
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5 truncate tabular-nums">
                                    <span>{{ $ticket->ticket_number }}</span> &bull; {{ $ticket->status }} &bull; Pemohon: {{ $ticket->requester->name }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 shrink-0 text-xs text-slate-400 tabular-nums">
                            <span>{{ $ticket->updated_at->diffForHumans() }}</span>
                            <span class="font-medium text-slate-700">&rarr;</span>
                        </div>
                    </a>
                @endforeach
            </div>

            @if($notifications->hasPages())
                <div class="mt-4 sm:mt-0 sm:px-6 sm:py-4 sm:border-t sm:border-slate-100 sm:bg-slate-50/50">
                    {{ $notifications->links() }}
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
