<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 py-1">
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Dashboard Kepala Bagian IT</h1>
        </div>
    </x-slot>

    <!-- KPI Metric Cards (Argon Modern Gradients) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 mb-6">
        <x-kpi-card
            title="BUTUH KLASIFIKASI"
            :value="$counts['waiting_it_head']"
            subtitle="See details"
            color="coral"
            :href="route('tickets.index', ['status' => \App\Models\Ticket::STATUS_WAITING_IT_HEAD])"
        />

        <x-kpi-card
            title="ALUR RAPAT MAJOR"
            :value="$counts['waiting_meeting']"
            subtitle="See details"
            color="purple"
        />

        <x-kpi-card
            title="DALAM PENGERJAAN"
            :value="$counts['active_development']"
            subtitle="See details"
            color="cyan"
        />

        <x-kpi-card
            title="SELESAI DITUTUP"
            :value="$counts['completed']"
            subtitle="See details"
            color="dark"
            :href="route('tickets.index', ['status' => \App\Models\Ticket::STATUS_COMPLETED])"
        />
    </div>

    @if(($counts['overdue'] ?? 0) > 0)
        <div class="mb-6 bg-rose-50 border border-rose-200 rounded-xl p-4 flex items-center justify-between gap-4 shadow-2xs">
            <div class="flex items-center gap-3 text-xs sm:text-sm text-rose-900">
                <span class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 font-bold flex items-center justify-center shrink-0">!</span>
                <div>
                    <div class="font-bold">Perhatian Timeline Kabag IT: {{ $counts['overdue'] }} Tiket Melewati Target Waktu (Overdue)</div>
                    <div class="text-xs text-rose-700 mt-0.5">Terdapat tiket aktif yang telah melewati tanggal target penyelesaian yang ditetapkan.</div>
                </div>
            </div>
            <a href="{{ route('tickets.index', ['overdue' => 1]) }}" class="px-3.5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shrink-0 transition-colors">
                Lihat Tiket Overdue &rarr;
            </a>
        </div>
    @endif

    <!-- Actionable Tickets Queue -->
    <div data-ajax-poll="dashboard-list" class="sm:bg-white sm:rounded-2xl sm:border sm:border-slate-100 sm:shadow-sm sm:overflow-hidden mb-6">
        <div class="px-1 sm:px-6 pb-3 sm:py-4 sm:border-b sm:border-slate-100 flex items-center justify-between sm:bg-white">
            <h2 class="text-sm sm:text-base font-bold text-slate-800 tracking-tight">Tiket Butuh Klasifikasi &amp; Verifikasi Notulen</h2>
            <a href="{{ route('tickets.index') }}" class="text-xs font-semibold text-[#1E40AF] sm:text-white sm:bg-[#1E40AF] sm:hover:bg-[#1D4ED8] sm:px-3.5 sm:py-1.5 rounded-lg sm:shadow-xs flex items-center gap-1 transition-all shrink-0">
                <span>Lihat Semua</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        @if($actionableTickets->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200/80 sm:border-0 py-10 sm:py-12 px-4 text-center">
                <div class="w-11 h-11 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-2.5">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">Antrean Bersih</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Tidak ada tiket yang membutuhkan klasifikasi atau persetujuan notulen saat ini.</p>
            </div>
        @else
            <!-- Mobile Native Separated Cards View (Handphone Only, Hidden on sm: Desktop) -->
            <div class="sm:hidden space-y-2.5">
                @foreach($actionableTickets as $ticket)
                    <x-mobile-ticket-card :ticket="$ticket" />
                @endforeach
            </div>

            <!-- Desktop Table View (Identical on sm: and up) -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-[#1E40AF]">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PROJECT / TIKET</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PEMOHON & ATASAN</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">PRIORITAS</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">STATUS SAAT INI</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold text-white uppercase tracking-wider">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach($actionableTickets as $ticket)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 inline-flex items-center justify-center tabular-nums text-xs font-semibold shrink-0">
                                            {{ $actionableTickets->total() - (($actionableTickets->firstItem() ?? 1) - 1) - $loop->index }}
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
                                    <div class="text-xs text-slate-400">Atasan: {{ $ticket->supervisor?->name ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-ticket-priority-badge :priority="$ticket->priority" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <x-ticket-status-badge :status="$ticket->status" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center gap-1 font-semibold text-white bg-[#1E40AF] hover:bg-[#1D4ED8] px-3 py-1.5 rounded-lg shadow-xs transition-colors">
                                        @if($ticket->status === \App\Models\Ticket::STATUS_WAITING_MAJOR_MEETING)
                                            <span>Setting Rapat</span>
                                        @elseif($ticket->status === \App\Models\Ticket::STATUS_WAITING_MINUTES_APPROVAL)
                                            <span>Verifikasi Notulensi</span>
                                        @else
                                            <span>Proses</span>
                                        @endif
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

            @if($actionableTickets->hasPages())
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/50">
                    {{ $actionableTickets->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- Analytics & Workload Breakdown (Kategori, Divisi, & Teknisi IT) -->
    @php
        $totalCatTickets = max(1, ($categoryStats ?? collect())->sum('total'));
        $totalDeptTickets = max(1, ($departmentStats ?? collect())->sum('tickets_count'));
    @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Card 1: Distribusi per Kategori Request -->
        <div class="bg-white rounded-2xl border border-slate-200/80 sm:border-slate-100 p-5 shadow-2xs">
            <div class="flex items-center justify-between mb-4 pb-2.5 border-b border-slate-100">
                <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tiket per Kategori Request</h3>
                <span class="text-xs text-slate-400">{{ ($categoryStats ?? collect())->sum('total') }} total</span>
            </div>
            <div class="space-y-3 text-xs">
                @forelse(($categoryStats ?? []) as $stat)
                    @php
                        $pct = round(($stat->total / $totalCatTickets) * 100);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <a href="{{ route('tickets.index', ['category' => $stat->category]) }}" class="font-semibold text-slate-800 hover:text-[#1E40AF]">
                                {{ $stat->category }}
                            </a>
                            <span class="tabular-nums font-bold text-slate-700">{{ $stat->total }} <span class="text-slate-400 font-normal">({{ $pct }}%)</span></span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-[#1E40AF] rounded-full" style="width: {{ max(4, $pct) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-slate-400 py-4 text-center">Belum ada data kategori tiket.</div>
                @endforelse
            </div>
        </div>

        <!-- Card 2: Distribusi per Divisi Pemohon -->
        <div class="bg-white rounded-2xl border border-slate-200/80 sm:border-slate-100 p-5 shadow-2xs">
            <div class="flex items-center justify-between mb-4 pb-2.5 border-b border-slate-100">
                <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tiket per Divisi Pemohon</h3>
                <span class="text-xs text-slate-400">{{ ($departmentStats ?? collect())->count() }} divisi</span>
            </div>
            <div class="space-y-3 text-xs">
                @forelse(($departmentStats ?? []) as $dept)
                    @php
                        $pct = round(($dept->tickets_count / $totalDeptTickets) * 100);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-semibold text-slate-800 truncate pr-2">{{ $dept->name }}</span>
                            <span class="tabular-nums font-bold text-slate-700 shrink-0">{{ $dept->tickets_count }} tiket</span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-cyan-600 rounded-full" style="width: {{ $dept->tickets_count > 0 ? max(4, $pct) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-slate-400 py-4 text-center">Belum ada data divisi.</div>
                @endforelse
            </div>
        </div>

        <!-- Card 3: Beban Kerja Teknisi IT -->
        <div class="bg-white rounded-2xl border border-slate-200/80 sm:border-slate-100 p-5 shadow-2xs">
            <div class="flex items-center justify-between mb-4 pb-2.5 border-b border-slate-100">
                <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Beban Kerja Teknisi IT</h3>
                <span class="text-xs text-slate-400">{{ ($developerWorkloads ?? collect())->count() }} personel</span>
            </div>
            <div class="divide-y divide-slate-100 text-xs">
                @forelse(($developerWorkloads ?? []) as $dev)
                    <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-semibold text-slate-900 truncate">{{ $dev->name }}</div>
                            <div class="text-[11px] text-slate-400 truncate">{{ $dev->email }}</div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 tabular-nums">
                            <span class="px-2 py-0.5 rounded-md font-semibold {{ $dev->active_count > 0 ? 'bg-amber-50 text-amber-800 border border-amber-200/70' : 'bg-slate-100 text-slate-500' }}">
                                {{ $dev->active_count }} aktif
                            </span>
                            <span class="px-2 py-0.5 rounded-md font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/70">
                                {{ $dev->completed_count }} selesai
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-slate-400 py-4 text-center">Belum ada teknisi IT terdaftar.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
