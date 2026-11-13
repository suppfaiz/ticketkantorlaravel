<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 py-1">
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">Dashboard Tim IT</h1>
        </div>
    </x-slot>

    <!-- Dismissible work notification; the full queue lives in the sidebar. -->
    @php
        $nextActiveTicket = $myActiveTickets->first();
        $activeListVersion = sha1($myActiveTickets->map(fn ($ticket) => $ticket->id.'-'.$ticket->status.'-'.$ticket->updated_at?->timestamp)->implode('|'));
        $activeToastKey = $nextActiveTicket ? 'developer-work-'.$activeListVersion : null;
    @endphp
    <div data-ajax-poll="dashboard-list-active" data-ajax-poll-version="{{ $activeListVersion }}">
        @if($nextActiveTicket)
            <aside
                x-data="{ open: true }"
                x-init="try { open = sessionStorage.getItem($el.dataset.toastKey) !== '1' } catch (_) { open = true }"
                x-show="open"
                x-transition.opacity.duration.200ms
                data-toast-key="{{ $activeToastKey }}"
                style="display: none;"
                role="status"
                aria-live="polite"
                class="fixed bottom-20 left-4 right-4 z-40 rounded-2xl border border-blue-200 bg-white p-4 shadow-xl shadow-slate-900/15 sm:bottom-6 sm:left-auto sm:right-6 sm:w-96"
            >
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-[#1E40AF]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2M12 3a9 9 0 110 18 9 9 0 010-18z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-bold text-slate-900">Pekerjaan Anda menunggu</h2>
                        <p class="mt-0.5 text-xs text-slate-600">{{ $myActiveTickets->count() }} tiket sedang Anda tangani.</p>
                        <p class="mt-2 truncate text-sm font-semibold text-slate-800">{{ $nextActiveTicket->title }}</p>
                        <p class="text-xs text-slate-500">{{ $nextActiveTicket->ticket_number }}</p>
                    </div>
                    <button
                        type="button"
                        @click="open = false; try { sessionStorage.setItem($el.closest('[data-toast-key]').dataset.toastKey, '1') } catch (_) {}"
                        class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]"
                        aria-label="Tutup notifikasi pekerjaan"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="mt-3 flex items-center gap-3 pl-12">
                    <a href="{{ route('tickets.show', $nextActiveTicket) }}" @click="try { sessionStorage.setItem($el.closest('[data-toast-key]').dataset.toastKey, '1') } catch (_) {}" class="inline-flex rounded-lg bg-[#1E40AF] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#1D4ED8]">Buka tiket</a>
                    <a href="{{ route('tickets.index', ['developer_queue' => 'active']) }}" @click="try { sessionStorage.setItem($el.closest('[data-toast-key]').dataset.toastKey, '1') } catch (_) {}" class="text-xs font-semibold text-[#1E40AF] hover:underline">Lihat semua</a>
                </div>
            </aside>
        @endif
    </div>

    <!-- KPI Metric Cards (Argon Modern Gradients) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 mb-6">
        <x-kpi-card
            title="POOL TIKET SIAP"
            :value="$counts['ready_pool']"
            subtitle="See details"
            color="cyan"
            :href="route('tickets.index', ['developer_queue' => 'unassigned'])"
        />

        <x-kpi-card
            title="SEDANG SAYA KERJAKAN"
            :value="$counts['my_in_progress']"
            subtitle="See details"
            color="purple"
            :href="route('tickets.index', ['developer_queue' => 'active', 'status' => \App\Models\Ticket::STATUS_IN_PROGRESS])"
        />

        <x-kpi-card
            title="PERLU REVISI KERJA"
            :value="$counts['my_revisions']"
            subtitle="See details"
            color="coral"
            :href="route('tickets.index', ['developer_queue' => 'active', 'status' => \App\Models\Ticket::STATUS_REVISION_WORK])"
        />

        <x-kpi-card
            title="TIKET SAYA SELESAI"
            :value="$counts['my_completed']"
            subtitle="See details"
            color="dark"
            :href="route('tickets.index', ['developer_queue' => 'completed'])"
        />
    </div>

    <!-- Available Ready Pool (Self-Assignment) -->
    <div data-ajax-poll="dashboard-list-pool" class="lg:bg-white lg:rounded-2xl lg:border lg:border-slate-100 lg:shadow-sm lg:overflow-hidden mb-6">
        <div class="px-1 sm:px-6 pb-3 sm:py-4 sm:border-b sm:border-slate-100 flex items-center justify-between sm:bg-white">
            <h2 class="text-sm sm:text-base font-bold text-slate-800 tracking-tight">Pool Tiket Siap Dikerjakan</h2>
            <a href="{{ route('tickets.index', ['developer_queue' => 'unassigned']) }}" class="text-xs font-semibold text-[#1E40AF] sm:bg-blue-50 sm:hover:bg-blue-100 sm:px-3.5 sm:py-1.5 rounded-lg transition-colors shrink-0">
                Lihat Pool Lengkap
            </a>
        </div>

        @if($availablePool->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200/80 sm:border-0 py-8 px-4 text-center">
                <p class="text-xs text-slate-500">Pool tiket kosong. Tidak ada tiket yang menunggu penugasan developer saat ini.</p>
            </div>
        @else
            <!-- Mobile Native Separated Cards View (Handphone Only, Hidden on sm: Desktop) -->
            <div class="lg:hidden space-y-2.5">
                @foreach($availablePool as $ticket)
                    <x-mobile-ticket-card :ticket="$ticket" :showSelfAssign="true" />
                @endforeach
            </div>

            <!-- Desktop Table View (Identical on sm: and up) -->
            <div class="hidden lg:block overflow-x-auto">
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
                                    <div class="text-xs text-slate-400">{{ $ticket->requester->departmentRecord?->name ?? $ticket->requester->department ?? 'Tanpa Departemen' }}</div>
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
