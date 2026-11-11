@php
    $authUser = Auth::user();
    $notifCount = 0;
    if ($authUser) {
        if ($authUser->isSupervisor()) {
            $notifCount = \App\Models\Ticket::where('supervisor_id', $authUser->id)
                ->where('status', \App\Models\Ticket::STATUS_WAITING_SUPERVISOR)
                ->count();
        } elseif ($authUser->isItHead()) {
            $notifCount = \App\Models\Ticket::whereIn('status', [
                \App\Models\Ticket::STATUS_WAITING_IT_HEAD,
                \App\Models\Ticket::STATUS_WAITING_MAJOR_MEETING,
                \App\Models\Ticket::STATUS_WAITING_MINUTES_APPROVAL,
            ])->count();
        } elseif ($authUser->isKadivIt()) {
            $notifCount = \App\Models\Ticket::where('status', \App\Models\Ticket::STATUS_WAITING_LOA_KADIV_IT)->count();
        } elseif ($authUser->isDireksi()) {
            $notifCount = \App\Models\Ticket::where('status', \App\Models\Ticket::STATUS_WAITING_LOA_DIREKSI)->count();
        } elseif ($authUser->isItDeveloper()) {
            $notifCount = \App\Models\Ticket::where(function ($q) use ($authUser) {
                $q->where('status', \App\Models\Ticket::STATUS_READY_FOR_WORK)
                    ->orWhere(function ($sq) use ($authUser) {
                        $sq->where('assigned_to', $authUser->id)
                            ->whereIn('status', [\App\Models\Ticket::STATUS_IN_PROGRESS, \App\Models\Ticket::STATUS_REVISION_WORK]);
                    });
            })->count();
        } else {
            $notifCount = \App\Models\Ticket::where('requester_id', $authUser->id)
                ->whereIn('status', [
                    \App\Models\Ticket::STATUS_REVISION_REQUESTER,
                    \App\Models\Ticket::STATUS_REVISION_BY_IT_HEAD,
                    \App\Models\Ticket::STATUS_WAITING_USER_MINUTES,
                    \App\Models\Ticket::STATUS_REVISION_MINUTES,
                    \App\Models\Ticket::STATUS_WAITING_USER_REVIEW,
                ])->count();
        }
    }

    $queueStatus = null;
    $queueLabel = 'Antrean';
    if ($authUser) {
        if ($authUser->isSupervisor()) {
            $queueStatus = \App\Models\Ticket::STATUS_WAITING_SUPERVISOR;
            $queueLabel = 'Otorisasi';
        } elseif ($authUser->isItHead()) {
            $queueStatus = \App\Models\Ticket::STATUS_WAITING_IT_HEAD;
            $queueLabel = 'Klasifikasi';
        } elseif ($authUser->isKadivIt()) {
            $queueStatus = \App\Models\Ticket::STATUS_WAITING_LOA_KADIV_IT;
            $queueLabel = 'LOA Kadiv';
        } elseif ($authUser->isDireksi()) {
            $queueStatus = \App\Models\Ticket::STATUS_WAITING_LOA_DIREKSI;
            $queueLabel = 'LOA Direksi';
        } elseif ($authUser->isItDeveloper()) {
            $queueStatus = \App\Models\Ticket::STATUS_READY_FOR_WORK;
            $queueLabel = 'Pool IT';
        }
    }
@endphp

<!-- Mobile Native Floating Dock Navigation Bar (Handphone Only, Hidden on Desktop lg:) -->
<div class="fixed bottom-3 inset-x-3 z-30 lg:hidden pointer-events-none" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
    <nav
        x-data
        aria-label="Navigasi Bawah Handphone"
        class="pointer-events-auto max-w-md mx-auto bg-white/95 backdrop-blur-xl rounded-2xl border border-slate-200/90 shadow-[0_10px_30px_rgba(15,23,42,0.14)] p-1.5 select-none"
    >
        <div class="grid grid-cols-4 items-center gap-1">
            <!-- 1. Beranda -->
            @php $isDash = request()->routeIs('dashboard'); @endphp
            <a
                href="{{ route('dashboard') }}"
                class="flex flex-col items-center justify-center gap-1 py-2 rounded-xl transition-all {{ $isDash ? 'bg-[#1E40AF] text-white font-bold shadow-sm shadow-blue-900/25' : 'text-slate-500 hover:text-slate-900 font-medium' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isDash ? '2.25' : '1.8' }}" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-[10px] leading-none">Beranda</span>
            </a>

            <!-- 2. Daftar Tiket -->
            @php $isTickets = request()->routeIs('tickets.index') && !request()->filled('status'); @endphp
            <a
                href="{{ route('tickets.index') }}"
                class="flex flex-col items-center justify-center gap-1 py-2 rounded-xl transition-all {{ $isTickets ? 'bg-[#1E40AF] text-white font-bold shadow-sm shadow-blue-900/25' : 'text-slate-500 hover:text-slate-900 font-medium' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isTickets ? '2.25' : '1.8' }}" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="text-[10px] leading-none">Tiket</span>
            </a>

            <!-- 3. Buat Tiket (Requester/Admin) atau Antrean (Approver/Dev) -->
            @if($authUser && ($authUser->isRequester() || $authUser->isAdmin()))
                @php $isCreate = request()->routeIs('tickets.create'); @endphp
                <a
                    href="{{ route('tickets.create') }}"
                    class="flex flex-col items-center justify-center gap-1 py-2 rounded-xl transition-all {{ $isCreate ? 'bg-[#1E40AF] text-white font-bold shadow-sm shadow-blue-900/25' : 'text-slate-500 hover:text-slate-900 font-medium' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isCreate ? '2.25' : '1.8' }}" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-[10px] leading-none">Buat Baru</span>
                </a>
            @elseif($queueStatus)
                @php $isQueue = request('status') === $queueStatus; @endphp
                <a
                    href="{{ route('tickets.index', ['status' => $queueStatus]) }}"
                    class="flex flex-col items-center justify-center gap-1 py-2 rounded-xl transition-all {{ $isQueue ? 'bg-[#1E40AF] text-white font-bold shadow-sm shadow-blue-900/25' : 'text-slate-500 hover:text-slate-900 font-medium' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isQueue ? '2.25' : '1.8' }}" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span class="text-[10px] leading-none">{{ $queueLabel }}</span>
                </a>
            @endif

            <!-- 4. Notifikasi -->
            @php $isNotif = request()->routeIs('notifications.*'); @endphp
            <a
                href="{{ route('notifications.index') }}"
                class="flex flex-col items-center justify-center gap-1 py-2 rounded-xl transition-all relative {{ $isNotif ? 'bg-[#1E40AF] text-white font-bold shadow-sm shadow-blue-900/25' : 'text-slate-500 hover:text-slate-900 font-medium' }}"
            >
                <div class="relative" data-ajax-poll="mobile-nav-notif">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $isNotif ? '2.25' : '1.8' }}" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    @if($notifCount > 0)
                        <span class="absolute -top-1 -right-1.5 min-w-[15px] h-[15px] px-1 rounded-full {{ $isNotif ? 'bg-white text-[#1E40AF]' : 'bg-[#1E40AF] text-white ring-2 ring-white' }} text-[9px] font-bold flex items-center justify-center leading-none tabular-nums">
                            {{ $notifCount > 9 ? '9+' : $notifCount }}
                        </span>
                    @endif
                </div>
                <span class="text-[10px] leading-none">Notifikasi</span>
            </a>
        </div>
    </nav>
</div>
