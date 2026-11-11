<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 py-1">
            <h1 class="text-base sm:text-xl font-bold text-slate-900 tracking-tight truncate">Dashboard Atasan Langsung</h1>
            <a href="{{ route('tickets.create') }}" class="px-3 sm:px-4 py-1.5 sm:py-2 text-xs font-semibold rounded-lg bg-[#1E40AF] max-sm:bg-white/20 max-sm:ring-1 max-sm:ring-white/30 hover:bg-[#1D4ED8] text-white shadow-xs flex items-center gap-1.5 transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Buat Tiket IT</span>
            </a>
        </div>
    </x-slot>

    <!-- KPI Metric Cards (Argon Modern Gradients) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 mb-6">
        <x-kpi-card
            title="PERLU PERSETUJUAN"
            :value="$counts['pending_approval']"
            subtitle="See details"
            color="coral"
            :href="route('tickets.index', ['status' => \App\Models\Ticket::STATUS_WAITING_SUPERVISOR])"
        />

        <x-kpi-card
            title="REVISI PEMOHON"
            :value="$counts['revision']"
            subtitle="See details"
            color="cyan"
            :href="route('tickets.index', ['status' => \App\Models\Ticket::STATUS_REVISION_REQUESTER])"
        />

        <x-kpi-card
            title="TOTAL DISETUJUI"
            :value="$counts['approved']"
            subtitle="See details"
            color="purple"
        />

        <x-kpi-card
            title="TOTAL DITOLAK"
            :value="$counts['rejected']"
            subtitle="See details"
            color="dark"
            :href="route('tickets.index', ['status' => \App\Models\Ticket::STATUS_REJECTED_SUPERVISOR])"
        />
    </div>

    <!-- Pending Subordinate Approval Queue -->
    <div data-ajax-poll="dashboard-list" class="sm:bg-white sm:rounded-2xl sm:border sm:border-slate-100 sm:shadow-sm sm:overflow-hidden mb-6">
        <div class="px-1 sm:px-6 pb-3 sm:py-4 sm:border-b sm:border-slate-100 flex items-center justify-between sm:bg-white">
            <h2 class="text-sm sm:text-base font-bold text-slate-800 tracking-tight">Antrean Persetujuan Bawahan</h2>
            <a href="{{ route('tickets.index') }}" class="text-xs font-semibold text-[#1E40AF] sm:text-white sm:bg-[#1E40AF] sm:hover:bg-[#1D4ED8] sm:px-3.5 sm:py-1.5 rounded-lg sm:shadow-xs flex items-center gap-1 transition-all">
                <span>Lihat Semua</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        @if($pendingTickets->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200/80 sm:border-0 py-10 sm:py-12 px-4 text-center">
                <div class="w-11 h-11 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-2.5">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">Antrean Bersih</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Tidak ada tiket dari bawahan Anda yang membutuhkan persetujuan saat ini.</p>
            </div>
        @else
            <!-- Mobile Native Separated Cards View (Handphone Only, Hidden on sm: Desktop) -->
            <div class="sm:hidden space-y-2.5">
                @foreach($pendingTickets as $ticket)
                    <x-mobile-ticket-card :ticket="$ticket" />
                @endforeach
            </div>

            <!-- Desktop Table View (Identical on sm: and up) -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-[#1E40AF]">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PROJECT / TIKET</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PEMOHON & DEPARTEMEN</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PRIORITAS</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">TANGGAL MASUK</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-white uppercase tracking-wider">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach($pendingTickets as $ticket)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 inline-flex items-center justify-center tabular-nums text-xs font-semibold shrink-0">
                                            {{ $pendingTickets->total() - (($pendingTickets->firstItem() ?? 1) - 1) - $loop->index }}
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
                                    <div class="text-xs text-slate-400">{{ $ticket->requester->department?->name ?? 'Tanpa Departemen' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-ticket-priority-badge :priority="$ticket->priority" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 tabular-nums">
                                    {{ $ticket->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center gap-1 font-semibold text-white bg-[#1E40AF] hover:bg-[#1D4ED8] px-3 py-1.5 rounded-lg shadow-xs transition-colors">
                                        <span>Tinjau</span>
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

            @if($pendingTickets->hasPages())
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/50">
                    {{ $pendingTickets->links() }}
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
