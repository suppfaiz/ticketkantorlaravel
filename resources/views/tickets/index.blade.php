<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 py-1">
            <h1 class="text-base sm:text-xl font-bold text-slate-900 tracking-tight truncate">Daftar Tiket IT</h1>
            <div class="hidden sm:flex items-center gap-2 shrink-0">
                <a
                    href="{{ route('tickets.export', request()->query()) }}"
                    class="btn-secondary text-xs py-2 px-3 inline-flex items-center gap-1.5"
                    title="Unduh Laporan Excel / CSV sesuai filter aktif"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Export CSV / Excel</span>
                </a>
                @can('create', App\Models\Ticket::class)
                    <a href="{{ route('tickets.create') }}" class="px-4 py-2 text-xs font-semibold rounded-lg bg-[#1E40AF] hover:bg-[#1D4ED8] text-white shadow-md shadow-blue-900/20 flex items-center gap-2 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Buat Tiket Baru</span>
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <!-- Compact Search & Filter Bar (Desktop) + Native Pill Search & Filter Chips (Mobile) -->
    @php
        $statusOptions = [
            'pending_approval' => 'Menunggu Persetujuan',
            'need_revision'    => 'Perlu Revisi',
            'in_progress'      => 'Sedang Dikerjakan',
            'completed'        => 'Selesai',
            'rejected'         => 'Ditolak',
        ];
    @endphp
    <div class="mb-4 sm:mb-5">
        <div class="bg-white rounded-2xl sm:rounded-xl border border-slate-200/90 sm:border-slate-200 p-2.5 sm:p-3.5 shadow-[0_2px_12px_rgba(15,23,42,0.04)] sm:shadow-2xs">
            <form method="GET" action="{{ route('tickets.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-2.5">
                <div class="flex items-center gap-2 sm:flex-1">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            value="{{ request('search') }}"
                            placeholder="Cari nomor atau judul tiket..."
                            class="w-full pl-9 py-2 text-xs rounded-xl sm:rounded-lg border-slate-200 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-slate-50/70 focus:bg-white transition-colors"
                        >
                        @if(request('status'))
                            <input type="hidden" name="status" value="{{ request('status') }}" class="sm:hidden" disabled>
                        @endif
                    </div>
                    <button type="submit" class="sm:hidden btn-primary text-xs py-2 px-3.5 rounded-xl shrink-0 cursor-pointer">
                        Cari
                    </button>
                </div>

                <div class="hidden sm:contents">
                    <div class="sm:w-44">
                        <select
                            name="status"
                            id="status"
                            onchange="this.form.submit()"
                            class="w-full py-2 text-xs rounded-lg border-slate-200 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-slate-50/60 focus:bg-white"
                        >
                            <option value="">Semua Status</option>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:w-40">
                        <select
                            name="category"
                            onchange="this.form.submit()"
                            class="w-full py-2 text-xs rounded-lg border-slate-200 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-slate-50/60 focus:bg-white"
                        >
                            <option value="">Semua Kategori</option>
                            @foreach(($categories ?? \App\Models\TicketCategory::activeNames()) as $cat)
                                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:w-32">
                        <select
                            name="priority"
                            onchange="this.form.submit()"
                            class="w-full py-2 text-xs rounded-lg border-slate-200 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-slate-50/60 focus:bg-white"
                        >
                            <option value="">Prioritas</option>
                            @foreach(['Low', 'Medium', 'High', 'Urgent'] as $prio)
                                <option value="{{ $prio }}" {{ request('priority') === $prio ? 'selected' : '' }}>
                                    {{ $prio }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:w-36">
                        <input
                            type="date"
                            name="date_from"
                            value="{{ request('date_from') }}"
                            onchange="this.form.submit()"
                            title="Dari Tanggal"
                            class="w-full py-2 text-xs rounded-lg border-slate-200 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-slate-50/60 focus:bg-white text-slate-600"
                        >
                    </div>

                    <div class="sm:w-36">
                        <input
                            type="date"
                            name="date_to"
                            value="{{ request('date_to') }}"
                            onchange="this.form.submit()"
                            title="Sampai Tanggal"
                            class="w-full py-2 text-xs rounded-lg border-slate-200 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-slate-50/60 focus:bg-white text-slate-600"
                        >
                    </div>

                    <div class="sm:w-28">
                        <select
                            name="per_page"
                            id="per_page"
                            onchange="this.form.submit()"
                            class="w-full py-2 text-xs rounded-lg border-slate-200 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-slate-50/60 focus:bg-white"
                        >
                            @foreach([10, 50, 100] as $size)
                                <option value="{{ $size }}" {{ (int) request('per_page', $perPage ?? 10) === $size ? 'selected' : '' }}>
                                    {{ $size }} / hal
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="hidden sm:flex items-center gap-2">
                    <button type="submit" class="btn-primary text-xs py-2 px-4 cursor-pointer">
                        Cari
                    </button>
                    @if(request()->filled('search') || request()->filled('status') || request()->filled('category') || request()->filled('priority') || request()->filled('overdue') || request()->filled('date_from') || request()->filled('date_to') || (request()->filled('per_page') && (int) request('per_page') !== 10))
                        <a href="{{ route('tickets.index') }}" class="btn-secondary text-xs py-2 px-3">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Mobile Native Horizontal Filter Chips (Handphone Only, Hidden on sm: Desktop) -->
        <div class="sm:hidden flex items-center gap-2 overflow-x-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none] pt-2.5 pb-0.5">
            <a
                href="{{ route('tickets.index', array_filter(['search' => request('search')])) }}"
                class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap shrink-0 transition-all {{ !request()->filled('status') ? 'bg-[#1E40AF] text-white shadow-sm shadow-blue-900/25' : 'bg-white text-slate-600 border border-slate-200/90' }}"
            >
                Semua Tiket
            </a>
            @foreach($statusOptions as $value => $label)
                <a
                    href="{{ route('tickets.index', array_filter(['status' => $value, 'search' => request('search')])) }}"
                    class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap shrink-0 transition-all {{ request('status') === $value ? 'bg-[#1E40AF] text-white shadow-sm shadow-blue-900/25' : 'bg-white text-slate-600 border border-slate-200/90' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Ticket List (Mobile Native Cards) & Table (Desktop Card Model) -->
    <div data-ajax-poll="tickets-list" class="sm:bg-white sm:rounded-2xl sm:border sm:border-slate-100 sm:shadow-sm sm:overflow-hidden">
        @if($tickets->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200/80 sm:border-0 py-16 text-center px-4">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">Tidak ada tiket ditemukan</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Belum ada tiket yang cocok dengan kriteria pencarian atau status filter Anda.</p>
                @can('create', App\Models\Ticket::class)
                    <div class="mt-4">
                        <a href="{{ route('tickets.create') }}" class="btn-primary text-xs">
                            Buat Tiket Baru
                        </a>
                    </div>
                @endcan
            </div>
        @else
            <!-- Mobile Native Separated Cards View (Handphone Only, Hidden on sm: Desktop) -->
            <div class="sm:hidden space-y-2.5">
                @foreach($tickets as $ticket)
                    <x-mobile-ticket-card :ticket="$ticket" />
                @endforeach
            </div>

            <!-- Desktop Table View (Hidden on Mobile, Identical on sm: and up) -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-[#1E40AF]">
                        <tr>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">No. Tiket</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">Judul &amp; Kategori</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">Pemohon</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">Prioritas</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">Klasifikasi</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-white uppercase tracking-wider">Tanggal</th>
                            <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-white uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach($tickets as $ticket)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-xs tabular-nums font-bold text-slate-800">
                                    <div class="flex items-center gap-3">
                                        <span class="w-7 h-7 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 inline-flex items-center justify-center tabular-nums text-xs font-semibold shrink-0">
                                            {{ $tickets->total() - (($tickets->firstItem() ?? 1) - 1) - $loop->index }}
                                        </span>
                                        <a href="{{ route('tickets.show', $ticket) }}" class="text-[#1E40AF] hover:text-blue-800 hover:underline font-semibold">
                                            {{ $ticket->ticket_number }}
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-slate-900 line-clamp-1">{{ $ticket->title }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ $ticket->category }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-800">
                                    <div class="font-medium text-slate-900">{{ $ticket->requester->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $ticket->requester->department?->name ?? 'Tanpa Departemen' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <x-ticket-priority-badge :priority="$ticket->priority" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($ticket->classification)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-medium border {{ $ticket->classification === 'Major' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $ticket->classification === 'Major' ? 'bg-rose-500' : 'bg-emerald-500' }}"></span>
                                            <span>{{ $ticket->classification }}</span>
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Belum diklasifikasi</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <x-ticket-status-badge :status="$ticket->status" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 tabular-nums">
                                    <div>{{ $ticket->created_at->format('d/m/Y H:i') }}</div>
                                    @if($ticket->target_completion_at)
                                        <div class="mt-0.5 flex items-center gap-1 {{ $ticket->isOverdue() ? 'text-rose-600 font-semibold' : 'text-slate-400' }}">
                                            <span>Target: {{ $ticket->target_completion_at->format('d/m/Y') }}</span>
                                            @if($ticket->isOverdue())
                                                <span class="px-1.5 py-0.2 rounded bg-rose-100 text-rose-700 text-[10px] font-bold">Overdue</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium space-x-2">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="text-blue-600 hover:text-blue-800 font-semibold hover:underline">
                                        Lihat
                                    </a>
                                    @can('update', $ticket)
                                        <a href="{{ route('tickets.edit', $ticket) }}" class="text-slate-600 hover:text-slate-900 font-semibold hover:underline">
                                            Revisi
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="{{ $tickets->hasPages() ? 'flex' : 'hidden sm:flex' }} mt-3 sm:mt-0 px-4 sm:px-6 py-3.5 rounded-xl sm:rounded-none border border-slate-200/80 sm:border-0 sm:border-t sm:border-slate-100 bg-white sm:bg-slate-50/50 flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="text-xs text-slate-500 text-center sm:text-left">
                    Menampilkan <span class="font-semibold text-slate-700">{{ $tickets->firstItem() ?? 0 }}</span> &ndash; <span class="font-semibold text-slate-700">{{ $tickets->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-slate-700">{{ $tickets->total() }}</span> tiket
                </div>
                @if($tickets->hasPages())
                    <div>
                        {{ $tickets->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
