<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 py-1">
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Dashboard Tim IT</h1>
        </div>
    </x-slot>

    <!-- KPI Metric Cards (Argon Modern Gradients) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 mb-6">
        <x-kpi-card
            title="POOL TIKET SIAP"
            :value="$counts['ready_pool']"
            subtitle="See details"
            color="cyan"
            :href="route('tickets.index', ['status' => \App\Models\Ticket::STATUS_READY_FOR_WORK])"
        />

        <x-kpi-card
            title="SEDANG SAYA KERJAKAN"
            :value="$counts['my_in_progress']"
            subtitle="See details"
            color="purple"
            :href="route('tickets.index', ['status' => \App\Models\Ticket::STATUS_IN_PROGRESS])"
        />

        <x-kpi-card
            title="PERLU REVISI KERJA"
            :value="$counts['my_revisions']"
            subtitle="See details"
            color="coral"
            :href="route('tickets.index', ['status' => \App\Models\Ticket::STATUS_REVISION_WORK])"
        />

        <x-kpi-card
            title="TIKET SAYA SELESAI"
            :value="$counts['my_completed']"
            subtitle="See details"
            color="dark"
            :href="route('tickets.index', ['status' => \App\Models\Ticket::STATUS_COMPLETED])"
        />
    </div>

    <!-- My Active Work List -->
    <div data-ajax-poll="dashboard-list-active" class="sm:bg-white sm:rounded-2xl sm:border sm:border-slate-100 sm:shadow-sm sm:overflow-hidden mb-6">
        <div class="px-1 sm:px-6 pb-3 sm:py-4 sm:border-b sm:border-slate-100 flex items-center justify-between sm:bg-white">
            <h2 class="text-sm sm:text-base font-bold text-slate-800 tracking-tight">Tiket Sedang Saya Tangani</h2>
            <a href="{{ route('tickets.index') }}" class="text-xs font-semibold text-[#1E40AF] sm:text-white sm:bg-[#1E40AF] sm:hover:bg-[#1D4ED8] sm:px-3.5 sm:py-1.5 rounded-lg sm:shadow-xs flex items-center gap-1 transition-all shrink-0">
                <span>Lihat Semua</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        @if($myActiveTickets->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200/80 sm:border-0 py-8 px-4 text-center">
                <p class="text-xs text-slate-500">Anda tidak memiliki tiket aktif saat ini. Silakan ambil tiket dari pool di bawah.</p>
            </div>
        @else
            <!-- Mobile Native Separated Cards View (Handphone Only, Hidden on sm: Desktop) -->
            <div class="sm:hidden space-y-2.5">
                @foreach($myActiveTickets as $ticket)
                    <x-mobile-ticket-card :ticket="$ticket" />
                @endforeach
            </div>

            <!-- Desktop Table View (Identical on sm: and up) -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-[#1E40AF]">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PROJECT / TIKET</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PEMOHON</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PRIORITAS</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">STATUS</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-white uppercase tracking-wider">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach($myActiveTickets as $ticket)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 inline-flex items-center justify-center tabular-nums text-xs font-semibold shrink-0">
                                            {{ $myActiveTickets->count() - $loop->index }}
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
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-ticket-status-badge :status="$ticket->status" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center gap-1 font-semibold text-white bg-[#1E40AF] hover:bg-[#1D4ED8] px-3 py-1.5 rounded-lg shadow-xs transition-colors">
                                        <span>Lanjutkan Kerja</span>
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
        @endif
    </div>

    <!-- Available Ready Pool (Self-Assignment) -->
    <div data-ajax-poll="dashboard-list-pool" class="sm:bg-white sm:rounded-2xl sm:border sm:border-slate-100 sm:shadow-sm sm:overflow-hidden mb-6">
        <div class="px-1 sm:px-6 pb-3 sm:py-4 sm:border-b sm:border-slate-100 flex items-center justify-between sm:bg-white">
            <h2 class="text-sm sm:text-base font-bold text-slate-800 tracking-tight">Pool Tiket Siap Dikerjakan</h2>
            <a href="{{ route('tickets.index', ['status' => \App\Models\Ticket::STATUS_READY_FOR_WORK]) }}" class="text-xs font-semibold text-[#1E40AF] sm:bg-blue-50 sm:hover:bg-blue-100 sm:px-3.5 sm:py-1.5 rounded-lg transition-colors shrink-0">
                Lihat Pool Lengkap
            </a>
        </div>

        @if($availablePool->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200/80 sm:border-0 py-8 px-4 text-center">
                <p class="text-xs text-slate-500">Pool tiket kosong. Tidak ada tiket yang menunggu penugasan developer saat ini.</p>
            </div>
        @else
            <!-- Mobile Native Separated Cards View (Handphone Only, Hidden on sm: Desktop) -->
            <div class="sm:hidden space-y-2.5">
                @foreach($availablePool as $ticket)
                    <x-mobile-ticket-card :ticket="$ticket" :showSelfAssign="true" />
                @endforeach
            </div>

            <!-- Desktop Table View (Identical on sm: and up) -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-[#1E40AF]">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PROJECT / TIKET</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PEMOHON</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PRIORITAS</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-white uppercase tracking-wider">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach($availablePool as $ticket)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 inline-flex items-center justify-center tabular-nums text-xs font-semibold shrink-0">
                                            {{ $availablePool->total() - (($availablePool->firstItem() ?? 1) - 1) - $loop->index }}
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
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                    <form action="{{ route('tickets.self-assign', $ticket) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 font-semibold text-white bg-[#2DCE89] hover:bg-[#25b577] px-3 py-1.5 rounded-lg shadow-xs transition-colors">
                                            <span>Ambil Tiket</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($availablePool->hasPages())
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/50">
                    {{ $availablePool->links() }}
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
