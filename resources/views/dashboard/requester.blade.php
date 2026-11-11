<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 py-1">
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Dashboard Karyawan</h1>
        </div>
    </x-slot>

    <!-- Row 1: Argon PRO 4 Metric KPI Cards (Purple, Cyan, Coral, Dark Navy) -->
    <div data-ajax-poll="dashboard-kpis" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 mb-6">
        <!-- 1: Tasks Completed / Total Tiket (Purple) -->
        <x-kpi-card
            title="TOTAL TIKET SAYA"
            :value="$counts['total']"
            subtitle="See details"
            color="purple"
            :href="route('tickets.index')"
        />

        <!-- 2: Contacts / Dalam Pengerjaan (Cyan) -->
        <x-kpi-card
            title="DALAM PENGERJAAN"
            :value="$counts['in_progress']"
            subtitle="See details"
            color="cyan"
            :href="route('tickets.index', ['status' => \App\Models\Ticket::STATUS_IN_PROGRESS])"
        />

        <!-- 3: Items Sold / Menunggu Approval (Coral) -->
        <x-kpi-card
            title="MENUNGGU APPROVAL"
            :value="$counts['waiting_supervisor']"
            subtitle="See details"
            color="coral"
            :href="route('tickets.index', ['status' => \App\Models\Ticket::STATUS_WAITING_SUPERVISOR])"
        />

        <!-- 4: Notifications / Selesai Ditutup (Dark Navy with Neon Green bar) -->
        <x-kpi-card
            title="SELESAI DITUTUP"
            :value="$counts['completed']"
            subtitle="See details"
            color="dark"
            :href="route('tickets.index', ['status' => \App\Models\Ticket::STATUS_COMPLETED])"
        />
    </div>

    <!-- Row 2: Table Section (Tiket Aktif & Terakhir Diajukan) -->
    <div data-ajax-poll="dashboard-list" class="sm:bg-white sm:rounded-2xl sm:border sm:border-slate-100 sm:shadow-sm sm:overflow-hidden mb-6">
        <div class="px-1 sm:px-6 pb-3 sm:py-4 sm:border-b sm:border-slate-100 flex items-center justify-between sm:bg-white">
            <h2 class="text-sm sm:text-base font-bold text-slate-800 tracking-tight">Tiket Aktif &amp; Terakhir Diajukan</h2>
            <a href="{{ route('tickets.index') }}" class="text-xs font-semibold text-[#1E40AF] sm:text-white sm:bg-[#1E40AF] sm:hover:bg-[#1D4ED8] sm:px-3.5 sm:py-1.5 rounded-lg sm:shadow-xs flex items-center gap-1 transition-all">
                <span>Lihat Semua</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        @if($recentTickets->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200/80 sm:border-0 py-10 sm:py-12 px-4 text-center">
                <div class="w-11 h-11 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-2.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">Belum Ada Tiket</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Anda belum pernah mengajukan tiket permohonan ke Tim IT. Buat tiket pertama Anda sekarang.</p>
                <div class="mt-3.5">
                    <a href="{{ route('tickets.create') }}" class="btn-primary text-xs py-2 px-3.5">
                        Ajukan Tiket Sekarang
                    </a>
                </div>
            </div>
        @else
            <!-- Mobile Native Separated Cards View (Handphone Only, Hidden on sm: Desktop) -->
            <div class="sm:hidden space-y-2.5">
                @foreach($recentTickets as $ticket)
                    <x-mobile-ticket-card :ticket="$ticket" />
                @endforeach
            </div>

            <!-- Desktop Table View (Identical on sm: and up) -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-[#1E40AF]">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PROJECT / TIKET</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">KATEGORI</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PRIORITAS</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">STATUS</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">USERS / PENANGGUNG JAWAB</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-white uppercase tracking-wider">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach($recentTickets as $ticket)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 inline-flex items-center justify-center tabular-nums text-xs font-semibold shrink-0">
                                            {{ $recentTickets->total() - (($recentTickets->firstItem() ?? 1) - 1) - $loop->index }}
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
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ $ticket->category ? ucfirst($ticket->category) : 'Umum' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-ticket-priority-badge :priority="$ticket->priority" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-ticket-status-badge :status="$ticket->status" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                    @if($ticket->assignedTo)
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center text-xs">
                                                {{ substr($ticket->assignedTo->name, 0, 1) }}
                                            </div>
                                            <span class="truncate max-w-[120px]">{{ $ticket->assignedTo->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">Belum ditugaskan</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center gap-1 font-semibold text-[#1E40AF] hover:text-[#1D4ED8] bg-blue-50/70 hover:bg-blue-50 px-2.5 py-1.5 rounded-lg transition-colors">
                                        <span>Rincian</span>
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

            @if($recentTickets->hasPages())
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/50">
                    {{ $recentTickets->links() }}
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
