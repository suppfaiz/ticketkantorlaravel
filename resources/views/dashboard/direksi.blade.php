<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 py-1">
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Dashboard Dewan Direksi</h1>
        </div>
    </x-slot>

    <x-action-queue :tickets="$actionTickets" :count="$actionCount" />

    <!-- KPI Metric Cards (Argon Modern Gradients) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-5 mb-6">
        <x-kpi-card
            title="MENUNGGU LOA DIREKSI"
            :value="$counts['waiting_direksi']"
            subtitle="See details"
            color="coral"
            :href="route('tickets.index', ['status' => \App\Models\Ticket::STATUS_WAITING_LOA_DIREKSI])"
        />

        <x-kpi-card
            title="PROYEK MAJOR BERJALAN"
            :value="$counts['in_progress_major']"
            subtitle="See details"
            color="cyan"
        />

        <x-kpi-card
            title="PROYEK MAJOR SELESAI"
            :value="$counts['completed_major']"
            subtitle="See details"
            color="dark"
            :href="route('tickets.index', ['status' => \App\Models\Ticket::STATUS_COMPLETED])"
        />
    </div>

    <!-- Direksi LOA Queue Table -->
    <div data-ajax-poll="dashboard-list" class="lg:bg-white lg:rounded-2xl lg:border lg:border-slate-100 lg:shadow-sm lg:overflow-hidden mb-6">
        <div class="px-1 sm:px-6 pb-3 sm:py-4 sm:border-b sm:border-slate-100 flex items-center justify-between sm:bg-white">
            <h2 class="text-sm sm:text-base font-bold text-slate-800 tracking-tight">Antrean Tiket Major Butuh Keputusan LOA Direksi</h2>
            <a href="{{ route('tickets.index') }}" class="text-xs font-semibold text-[#1E40AF] sm:text-white sm:bg-[#1E40AF] sm:hover:bg-[#1D4ED8] sm:px-3.5 sm:py-1.5 rounded-lg sm:shadow-xs flex items-center gap-1 transition-all shrink-0">
                <span>Lihat Semua</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        @if($direksiTickets->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200/80 sm:border-0 py-10 sm:py-12 px-4 text-center">
                <div class="w-11 h-11 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-2.5">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">Tidak Ada Antrean LOA Direksi</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Seluruh permohonan proyek strategis Major telah diputuskan.</p>
            </div>
        @else
            <!-- Mobile Native Separated Cards View (Handphone Only, Hidden on sm: Desktop) -->
            <div class="lg:hidden space-y-2.5">
                @foreach($direksiTickets as $ticket)
                    <x-mobile-ticket-card :ticket="$ticket" />
                @endforeach
            </div>

            <!-- Desktop Table View (Identical on sm: and up) -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-[#1E40AF]">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PROJECT / TIKET</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PEMOHON & UNIT</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">KLASIFIKASI</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">TANGGAL MASUK</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-white uppercase tracking-wider">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach($direksiTickets as $ticket)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 inline-flex items-center justify-center tabular-nums text-xs font-semibold shrink-0">
                                            {{ $direksiTickets->total() - (($direksiTickets->firstItem() ?? 1) - 1) - $loop->index }}
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('tickets.show', $ticket) }}" class="text-sm font-semibold text-slate-900 hover:text-[#1E40AF] transition-colors block max-w-xs truncate leading-snug">
                                                {{ $ticket->title }}
                                            </a>
                                            <span class="text-xs tabular-nums text-slate-500 block mt-0.5">
                                                {{ $ticket->ticket_number }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                    <div class="font-semibold text-slate-800">{{ $ticket->requester->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $ticket->requester->departmentRecord?->name ?? $ticket->requester->department ?? 'Tanpa Departemen' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-medium border {{ $ticket->classification === 'Major' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $ticket->classification === 'Major' ? 'bg-rose-500' : 'bg-emerald-500' }}"></span>
                                        <span>{{ $ticket->classification }}</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 tabular-nums">
                                    {{ $ticket->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center gap-1 font-semibold text-white bg-[#1E40AF] hover:bg-[#1D4ED8] px-3 py-1.5 rounded-lg shadow-xs transition-colors">
                                        <span>Putuskan LOA</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($direksiTickets->hasPages())
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/50">
                    {{ $direksiTickets->links() }}
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
