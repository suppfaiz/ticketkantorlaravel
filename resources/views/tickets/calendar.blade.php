<x-app-layout>
    @php
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');
        $thisMonth = now()->format('Y-m');

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $dayShortNames = [
            1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam',
            5 => 'Jum', 6 => 'Sab', 0 => 'Min',
        ];

        $monthTitle = ($monthNames[(int) $currentMonth->format('n')] ?? $currentMonth->format('F')) . ' ' . $currentMonth->format('Y');
        $daysInMonth = $currentMonth->daysInMonth;
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        // Build 7-column monthly calendar grid (Monday .. Sunday)
        $firstDayOfGrid = $startOfMonth->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $lastDayOfGrid = $endOfMonth->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

        $calendarDays = [];
        $cursor = $firstDayOfGrid->copy();
        while ($cursor->lte($lastDayOfGrid)) {
            $calendarDays[] = $cursor->copy();
            $cursor->addDay();
        }

        // Build array of days 1..N for the Gantt Chart header
        $monthDays = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $monthDays[$d] = $currentMonth->copy()->day($d);
        }

        $totalCount = $ganttTickets->count();
        $overdueCount = $ganttTickets->where('state', 'overdue')->count();
        $inProgressCount = $ganttTickets->whereIn('state', ['in_progress', 'approval'])->count();
        $completedCount = $ganttTickets->where('state', 'completed')->count();

        $canManageTimeline = auth()->user()->isItHead() || auth()->user()->isAdmin();
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between gap-2 sm:gap-4 py-1">
            <div>
                <h1 class="text-base sm:text-xl font-bold text-slate-900 tracking-tight truncate">Kalender &amp; Linimasa Pengerjaan</h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('tickets.index') }}" class="btn-secondary text-xs py-1.5 px-3">
                    &larr; Daftar Tiket
                </a>
            </div>
        </div>
    </x-slot>

    <div
        x-data="{
            viewMode: 'gantt',
            editingTicket: {
                number: '',
                title: '',
                target_completion_at: '',
                assigned_to: '',
                actionUrl: ''
            },
            openTimelineModal(data) {
                this.editingTicket = data;
                $dispatch('open-modal', 'modal-calendar-set-timeline');
            }
        }"
        class="space-y-4 sm:space-y-5"
    >
        <!-- Clean, Compact Single-Row Toolbar -->
        <div class="bg-white rounded-xl border border-slate-200 p-3 sm:px-4 shadow-2xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <!-- Left: Month Switcher + Minimal Filters -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Month Switcher -->
                <div class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 p-0.5">
                    <a
                        href="{{ route('tickets.calendar', array_merge(request()->query(), ['month' => $prevMonth])) }}"
                        class="p-1.5 rounded-md text-slate-500 hover:text-slate-900 hover:bg-white transition-colors"
                        title="Bulan Sebelumnya"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <span class="px-3 text-xs font-bold text-slate-900 min-w-[120px] text-center">
                        {{ $monthTitle }}
                    </span>
                    <a
                        href="{{ route('tickets.calendar', array_merge(request()->query(), ['month' => $nextMonth])) }}"
                        class="p-1.5 rounded-md text-slate-500 hover:text-slate-900 hover:bg-white transition-colors"
                        title="Bulan Berikutnya"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                @if($currentMonth->format('Y-m') !== $thisMonth)
                    <a
                        href="{{ route('tickets.calendar', array_merge(request()->query(), ['month' => $thisMonth])) }}"
                        class="btn-secondary text-xs py-1.5 px-2.5"
                    >
                        Bulan Ini
                    </a>
                @endif

                <form method="GET" action="{{ route('tickets.calendar') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="month" value="{{ $currentMonth->format('Y-m') }}">

                    <select name="event_type" onchange="this.form.submit()" class="py-1.5 text-xs rounded-lg border-slate-200 focus:border-slate-400 focus:ring-0 bg-white text-slate-700">
                        <option value="">Semua Tiket ({{ $totalCount }})</option>
                        <option value="target" {{ request('event_type') === 'target' ? 'selected' : '' }}>Sudah Ada Target</option>
                        <option value="overdue" {{ request('event_type') === 'overdue' ? 'selected' : '' }}>Melewati Target ({{ $overdueCount }})</option>
                    </select>

                    @if($canManageTimeline)
                        <select name="assigned_to" onchange="this.form.submit()" class="py-1.5 text-xs rounded-lg border-slate-200 focus:border-slate-400 focus:ring-0 bg-white text-slate-700">
                            <option value="">Semua Teknisi</option>
                            @foreach($itDevelopers as $dev)
                                <option value="{{ $dev->id }}" {{ (string) request('assigned_to') === (string) $dev->id ? 'selected' : '' }}>
                                    {{ $dev->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    @if(request()->filled('event_type') || request()->filled('assigned_to') || request()->filled('category'))
                        <a href="{{ route('tickets.calendar', ['month' => $currentMonth->format('Y-m')]) }}" class="text-xs text-slate-500 hover:text-slate-900 px-2 py-1">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <!-- Right: Inline Summary + View Switcher -->
            <div class="flex items-center justify-between lg:justify-end gap-4">
                <div class="hidden xl:flex items-center gap-3 text-xs text-slate-500">
                    <span><strong class="text-slate-900">{{ $inProgressCount }}</strong> aktif</span>
                    <span class="text-slate-300">&bull;</span>
                    <span><strong class="text-slate-900">{{ $completedCount }}</strong> selesai</span>
                    @if($overdueCount > 0)
                        <span class="text-slate-300">&bull;</span>
                        <span class="text-rose-600 font-semibold">{{ $overdueCount }} terlambat</span>
                    @endif
                </div>

                <div class="inline-flex p-0.5 rounded-lg bg-slate-100 border border-slate-200/80 shrink-0">
                    <button
                        type="button"
                        @click="viewMode = 'gantt'"
                        :class="viewMode === 'gantt' ? 'bg-white text-slate-900 font-semibold shadow-2xs' : 'text-slate-500 hover:text-slate-800 font-medium'"
                        class="px-3 py-1 rounded-md text-xs transition-all cursor-pointer"
                    >
                        Linimasa
                    </button>
                    <button
                        type="button"
                        @click="viewMode = 'calendar'"
                        :class="viewMode === 'calendar' ? 'bg-white text-slate-900 font-semibold shadow-2xs' : 'text-slate-500 hover:text-slate-800 font-medium'"
                        class="px-3 py-1 rounded-md text-xs transition-all cursor-pointer"
                    >
                        Kalender
                    </button>
                </div>
            </div>
        </div>

        <!-- VIEW 1: SIMPLE & CLEAN GANTT TIMELINE -->
        <div x-show="viewMode === 'gantt'" class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            @if($ganttTickets->isEmpty())
                <div class="py-14 px-4 text-center">
                    <p class="text-sm font-semibold text-slate-700">Belum ada tiket pada periode {{ $monthTitle }}</p>
                    <p class="text-xs text-slate-400 mt-1">Pilih bulan lain menggunakan navigasi di atas.</p>
                </div>
            @else
                <!-- Desktop Gantt Table -->
                <div class="hidden sm:block overflow-x-auto">
                    <div class="min-w-[1100px]">
                        <!-- Header Row -->
                        <div class="flex border-b border-slate-200 bg-slate-50/70">
                            <div class="w-72 shrink-0 px-4 py-2.5 border-r border-slate-200 flex items-center justify-between text-[11px] font-semibold text-slate-500 uppercase tracking-wider bg-slate-50 sticky left-0 z-10">
                                <span>Tiket</span>
                                <span>Target</span>
                            </div>

                            <div class="flex-1 grid" style="grid-template-columns: repeat({{ $daysInMonth }}, minmax(28px, 1fr));">
                                @foreach($monthDays as $dayNum => $dayCarbon)
                                    @php
                                        $isToday = $dayCarbon->isToday();
                                        $isWeekend = $dayCarbon->isWeekend();
                                    @endphp
                                    <div class="py-2 text-center border-r border-slate-100 last:border-r-0 {{ $isToday ? 'bg-slate-900 text-white' : ($isWeekend ? 'bg-slate-50 text-slate-400' : 'text-slate-500') }}">
                                        <div class="text-[9px] uppercase leading-none {{ $isToday ? 'text-slate-300' : 'text-slate-400' }}">
                                            {{ $dayShortNames[$dayCarbon->dayOfWeek] ?? '' }}
                                        </div>
                                        <div class="text-[11px] font-semibold tabular-nums mt-0.5 leading-none">
                                            {{ $dayNum }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Ticket Rows -->
                        <div class="divide-y divide-slate-100">
                            @foreach($ganttTickets as $row)
                                @php
                                    /** @var \App\Models\Ticket $t */
                                    $t = $row['ticket'];
                                    $state = $row['state'];
                                    $spanStart = $row['span_start'];
                                    $spanEnd = $row['span_end'];

                                    $clampedStart = $spanStart->lt($startOfMonth) ? $startOfMonth->copy() : $spanStart->copy();
                                    $clampedEnd = $spanEnd->gt($endOfMonth) ? $endOfMonth->copy() : $spanEnd->copy();

                                    $startDayIdx = max(1, (int) $clampedStart->day);
                                    $endDayIdx = max($startDayIdx, (int) $clampedEnd->day);
                                    $spanDays = ($endDayIdx - $startDayIdx) + 1;

                                    $leftPct = (($startDayIdx - 1) / $daysInMonth) * 100;
                                    $widthPct = ($spanDays / $daysInMonth) * 100;

                                    // Calm, minimalist bar styling (only 3 clean states: active blue, completed slate, overdue rose)
                                    $barStyle = match($state) {
                                        'overdue'   => 'bg-rose-600 text-white',
                                        'completed' => 'bg-slate-200 text-slate-700',
                                        default     => 'bg-[#1E40AF] text-white',
                                    };
                                @endphp
                                <div class="flex items-stretch hover:bg-slate-50/60 transition-colors group">
                                    <!-- Left Sticky Info Column -->
                                    <div class="w-72 shrink-0 px-4 py-2.5 border-r border-slate-200 bg-white group-hover:bg-slate-50 sticky left-0 z-10 flex items-center justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('tickets.show', $t) }}" class="text-xs font-semibold text-slate-900 hover:text-[#1E40AF] truncate block" title="{{ $t->title }}">
                                                {{ $t->title }}
                                            </a>
                                            <div class="text-[11px] text-slate-400 truncate mt-0.5 tabular-nums">
                                                {{ $t->ticket_number }} &bull; {{ $t->assignedTo?->name ?? 'Pool' }}
                                            </div>
                                        </div>

                                        <div class="shrink-0">
                                            @if($canManageTimeline && !$t->isTerminal())
                                                <button
                                                    type="button"
                                                    @click="openTimelineModal(@js([
                                                        'number' => $t->ticket_number,
                                                        'title' => $t->title,
                                                        'target_completion_at' => $t->target_completion_at?->format('Y-m-d') ?? '',
                                                        'assigned_to' => $t->assigned_to ?? '',
                                                        'actionUrl' => route('tickets.assign-and-timeline', $t),
                                                    ]))"
                                                    class="px-2 py-0.5 rounded border text-[11px] font-medium tabular-nums transition-colors cursor-pointer {{ $t->target_completion_at ? ($t->isOverdue() ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300') : 'border-dashed border-slate-300 text-slate-400 hover:text-slate-700' }}"
                                                    title="Atur Target Timeline"
                                                >
                                                    {{ $t->target_completion_at ? $t->target_completion_at->format('d M') : '+ Target' }}
                                                </button>
                                            @else
                                                <span class="text-[11px] tabular-nums {{ $t->isOverdue() ? 'text-rose-600 font-semibold' : 'text-slate-500' }}">
                                                    {{ $t->target_completion_at ? $t->target_completion_at->format('d M') : '-' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Right Horizontal Bar Area -->
                                    <div class="flex-1 relative grid items-center" style="grid-template-columns: repeat({{ $daysInMonth }}, minmax(28px, 1fr));">
                                        @foreach($monthDays as $dayNum => $dayCarbon)
                                            @php
                                                $isToday = $dayCarbon->isToday();
                                                $isWeekend = $dayCarbon->isWeekend();
                                            @endphp
                                            <div class="h-full border-r border-slate-100/80 last:border-r-0 {{ $isToday ? 'bg-slate-100/70' : ($isWeekend ? 'bg-slate-50/40' : '') }}"></div>
                                        @endforeach

                                        <div
                                            class="absolute inset-y-0 flex items-center px-1 pointer-events-none"
                                            style="left: {{ $leftPct }}%; width: {{ $widthPct }}%;"
                                        >
                                            <a
                                                href="{{ route('tickets.show', $t) }}"
                                                class="pointer-events-auto w-full h-6 rounded-md px-2 flex items-center justify-between gap-1 text-[11px] font-medium truncate transition-opacity hover:opacity-90 {{ $barStyle }}"
                                                title="{{ $t->ticket_number }} — {{ $t->title }} ({{ $spanStart->format('d/m/Y') }} - {{ $spanEnd->format('d/m/Y') }})"
                                            >
                                                <span class="truncate tabular-nums">{{ $spanStart->format('d/m') }} &ndash; {{ $spanEnd->format('d/m') }}</span>
                                                @if($state === 'completed')
                                                    <span class="shrink-0 text-[10px]">Selesai</span>
                                                @endif
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Minimal Footer Legend -->
                        <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 text-[11px] text-slate-500">
                            <div class="flex items-center gap-4">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-xs bg-[#1E40AF]"></span>
                                    <span>Aktif / Dalam Proses</span>
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-xs bg-slate-300"></span>
                                    <span>Selesai</span>
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-xs bg-rose-600"></span>
                                    <span>Melewati Target</span>
                                </span>
                            </div>
                            @if($canManageTimeline)
                                <span>Klik tanggal pada kolom <strong>Target</strong> untuk mengubah batas waktu.</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Mobile Simple List View -->
                <div class="sm:hidden divide-y divide-slate-100">
                    @foreach($ganttTickets as $row)
                        @php
                            $t = $row['ticket'];
                            $state = $row['state'];
                            $spanStart = $row['span_start'];
                            $spanEnd = $row['span_end'];
                        @endphp
                        <div class="p-3.5 flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('tickets.show', $t) }}" class="text-xs font-semibold text-slate-900 truncate block">
                                    {{ $t->title }}
                                </a>
                                <div class="text-[11px] text-slate-400 mt-0.5 tabular-nums">
                                    {{ $t->ticket_number }} &bull; {{ $spanStart->format('d/m') }}–{{ $spanEnd->format('d/m') }}
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[11px] font-medium shrink-0 {{ $state === 'completed' ? 'bg-slate-100 text-slate-600' : ($state === 'overdue' ? 'bg-rose-50 text-rose-700' : 'bg-blue-50 text-[#1E40AF]') }}">
                                {{ $state === 'completed' ? 'Selesai' : ($t->target_completion_at ? $t->target_completion_at->format('d M') : 'Aktif') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- VIEW 2: CLEAN MONTHLY CALENDAR GRID -->
        <div x-show="viewMode === 'calendar'" style="display: none;">
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <!-- Day Headers -->
                <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-200 text-slate-500 text-[11px] font-semibold uppercase tracking-wider text-center divide-x divide-slate-200">
                    <div class="py-2">Sen</div>
                    <div class="py-2">Sel</div>
                    <div class="py-2">Rab</div>
                    <div class="py-2">Kam</div>
                    <div class="py-2">Jum</div>
                    <div class="py-2 text-slate-400">Sab</div>
                    <div class="py-2 text-slate-400">Min</div>
                </div>

                <!-- Calendar Cells -->
                <div class="grid grid-cols-7 divide-x divide-y divide-slate-200">
                    @foreach($calendarDays as $day)
                        @php
                            $dateKey = $day->format('Y-m-d');
                            $isCurrentMonth = $day->month === $currentMonth->month;
                            $isToday = $day->isToday();
                            $dayEvents = $eventsByDate[$dateKey] ?? [];
                        @endphp
                        <div class="min-h-[105px] p-2 {{ $isCurrentMonth ? 'bg-white' : 'bg-slate-50/50 text-slate-400' }}">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-semibold tabular-nums {{ $isToday ? 'w-5 h-5 rounded-full bg-slate-900 text-white flex items-center justify-center text-[11px]' : ($isCurrentMonth ? 'text-slate-700' : 'text-slate-400') }}">
                                    {{ $day->format('j') }}
                                </span>
                            </div>

                            <div class="space-y-1">
                                @foreach($dayEvents as $ev)
                                    @php
                                        $t = $ev['ticket'];
                                        $leftBorder = match($ev['state']) {
                                            'overdue'   => 'border-l-rose-500',
                                            'completed' => 'border-l-slate-400',
                                            default     => 'border-l-[#1E40AF]',
                                        };
                                    @endphp
                                    <a
                                        href="{{ route('tickets.show', $t) }}"
                                        class="block px-2 py-1 rounded border border-slate-200 border-l-2 {{ $leftBorder }} bg-slate-50/70 hover:bg-slate-100 text-[11px] leading-tight transition-colors"
                                        title="{{ $t->ticket_number }} - {{ $t->title }} ({{ $ev['badge'] }})"
                                    >
                                        <div class="font-medium text-slate-800 truncate">{{ $t->title }}</div>
                                        <div class="text-[10px] text-slate-400 truncate mt-0.5 tabular-nums">{{ $ev['badge'] }}</div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Quick Modal for Kabag IT / Admin to Set Target Timeline & Technician directly from Calendar -->
        @if($canManageTimeline)
            <x-modal name="modal-calendar-set-timeline" focusable>
                <form method="POST" :action="editingTicket.actionUrl" class="p-6">
                    @csrf
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-bold px-2 py-0.5 rounded bg-blue-50 text-[#1E40AF] tabular-nums" x-text="editingTicket.number"></span>
                        <h3 class="text-base font-bold text-slate-900">Atur Target Timeline &amp; Teknisi</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-4 truncate" x-text="editingTicket.title"></p>

                    <div class="space-y-4 mb-5 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Target Timeline Penyelesaian (Kabag IT)</label>
                            <input
                                type="date"
                                name="target_completion_at"
                                x-model="editingTicket.target_completion_at"
                                class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"
                            >
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Teknisi IT Pelaksana</label>
                            <select name="assigned_to" x-model="editingTicket.assigned_to" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">
                                <option value="">-- Belum Ditugaskan (Antrean Pool) --</option>
                                @foreach($itDevelopers as $dev)
                                    <option value="{{ $dev->id }}">{{ $dev->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Catatan (Opsional)</label>
                            <input
                                type="text"
                                name="notes"
                                placeholder="Catatan penetapan timeline..."
                                class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"
                            >
                        </div>
                    </div>

                    <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">Batal</button>
                        <button type="submit" class="btn-primary text-xs cursor-pointer">Simpan Timeline</button>
                    </div>
                </form>
            </x-modal>
        @endif
    </div>
</x-app-layout>
