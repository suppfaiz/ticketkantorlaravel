@php
    $user = Auth::user();
    $role = $user->role ?? 'requester';
    $delegationService = app(\App\Services\ApprovalDelegationService::class);
    $canApproveSupervisor = $user && ($user->isSupervisor() || $user->isAdmin() || $delegationService->delegatedUserIds($user, \App\Models\ApprovalDelegation::SCOPE_SUPERVISOR)->isNotEmpty());
    $canApproveItHead = $user && ($user->isAdmin() || $delegationService->actsForRole($user, \App\Models\User::ROLE_IT_HEAD, \App\Models\ApprovalDelegation::SCOPE_IT_HEAD));
    $canApproveKadiv = $user && ($user->isAdmin() || $delegationService->actsForRole($user, \App\Models\User::ROLE_KADIV_IT, \App\Models\ApprovalDelegation::SCOPE_KADIV_IT));
    $canApproveDireksi = $user && ($user->isAdmin() || $delegationService->actsForRole($user, \App\Models\User::ROLE_DIREKSI, \App\Models\ApprovalDelegation::SCOPE_DIREKSI));
    $isTicketIndex = request()->routeIs('tickets.index');
    $developerQueueItems = [
        'active' => 'Sedang Saya Kerjakan',
        'unassigned' => 'Belum Di-assign',
        'completed' => 'Selesai Dikerjakan',
    ];
    $requestedDeveloperQueue = request('developer_queue');
    $activeDeveloperQueue = $isTicketIndex && $user?->isItDeveloper() && is_string($requestedDeveloperQueue) && array_key_exists($requestedDeveloperQueue, $developerQueueItems)
        ? $requestedDeveloperQueue
        : null;
    $isPoolMenuActive = $isTicketIndex && $user?->isAdmin() && request('status') === \App\Models\Ticket::STATUS_READY_FOR_WORK;
    $isSupervisorMenuActive = $isTicketIndex && $canApproveSupervisor && request('status') === \App\Models\Ticket::STATUS_WAITING_SUPERVISOR;
    $isItHeadMenuActive = $isTicketIndex && $canApproveItHead && request('status') === \App\Models\Ticket::STATUS_WAITING_IT_HEAD;
    $isExecutiveMenuActive = $isTicketIndex && ($canApproveKadiv || $canApproveDireksi) && in_array(request('status'), [\App\Models\Ticket::STATUS_WAITING_LOA_KADIV_IT, \App\Models\Ticket::STATUS_WAITING_LOA_DIREKSI], true);
    $isGeneralTicketMenuActive = $isTicketIndex && ! ($activeDeveloperQueue || $isPoolMenuActive || $isSupervisorMenuActive || $isItHeadMenuActive || $isExecutiveMenuActive);
@endphp

<!-- Mobile Off-Canvas Backdrop -->
<div
    x-data
    x-show="$store.sidebar.mobileOpen"
    x-transition:enter="transition-opacity ease-linear duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="$store.sidebar.closeMobile()"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 lg:hidden"
    style="display: none;"
></div>

<!-- Sidebar Container (Corporate Blue Gradient Theme) -->
<aside
    x-data
    :class="{
        'translate-x-0': $store.sidebar.mobileOpen,
        '-translate-x-full': !$store.sidebar.mobileOpen,
        'lg:w-20': $store.sidebar.collapsed,
        'lg:w-64': !$store.sidebar.collapsed
    }"
    class="fixed inset-y-0 left-0 z-50 -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:self-start lg:h-screen lg:shrink-0 w-72 max-w-[82vw] lg:max-w-none lg:w-64 flex flex-col h-screen h-dvh bg-gradient-to-b from-[#1E40AF] via-[#1E3A8A] to-[#172554] border-r border-blue-900/40 transition-all duration-300 ease-in-out shadow-xl text-white select-none"
>
    <!-- Brand Header with NALA White Logo & Desktop Toggle -->
    <div 
        class="h-16 flex items-center px-5 justify-between border-b border-white/10 bg-black/10 backdrop-blur-xs transition-all duration-300"
        :class="{
            'lg:px-2 lg:justify-center': $store.sidebar.collapsed,
            'px-5 justify-between': !$store.sidebar.collapsed
        }"
    >
        <!-- Expanded Brand Logo -->
        <a href="{{ route('dashboard') }}" class="flex items-center overflow-hidden py-1 group" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen">
            <img
                src="{{ asset('images/logo-nala-white.png') }}"
                alt="NALA - Navigasi Aduan dan Layanan Andal"
                class="h-9 w-auto max-w-[160px] object-contain transition-transform duration-200 group-hover:scale-[1.02]"
            >
        </a>

        <!-- Desktop Toggle (Burger ≡) in Expanded Mode -->
        <button
            type="button"
            x-show="!$store.sidebar.collapsed"
            @click="$store.sidebar.collapse()"
            class="hidden lg:flex p-1.5 rounded-lg text-blue-200 hover:text-white hover:bg-white/10 transition-colors focus:outline-none cursor-pointer"
            title="Kecilkan Sidebar"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <!-- Collapsed Emblem (Click to expand) -->
        <button
            type="button"
            x-show="$store.sidebar.collapsed"
            @click="$store.sidebar.expand()"
            class="hidden lg:flex p-1.5 rounded-xl text-blue-200 hover:text-white hover:bg-white/15 transition-all focus:outline-none cursor-pointer items-center justify-center group"
            title="Perbesar Sidebar"
            style="display: none;"
        >
            <img
                src="{{ asset('images/logo-nala-icon-white.png') }}"
                alt="NALA"
                class="w-8 h-8 object-contain transition-transform duration-200 group-hover:scale-110"
            >
        </button>

        <!-- Mobile close button -->
        <button
            type="button"
            @click="$store.sidebar.closeMobile()"
            class="lg:hidden p-1.5 rounded-md text-blue-200 hover:text-white hover:bg-white/10 ml-auto"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Navigation Menu Items with High-Contrast Colorful Badges -->
    <nav 
        class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar text-xs font-semibold transition-all"
        :class="{
            'lg:px-2': $store.sidebar.collapsed,
            'px-3': !$store.sidebar.collapsed
        }"
    >
        <!-- Section: Menu Utama -->
        <div class="px-3 mb-1" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen" x-transition>
            <span class="text-xs font-bold uppercase tracking-wider text-blue-200/80">MENU UTAMA</span>
        </div>

        <a
            href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all group relative {{ request()->routeIs('dashboard') ? 'bg-white/20 text-white font-bold shadow-xs backdrop-blur-xs ring-1 ring-white/20' : 'text-blue-100 hover:text-white hover:bg-white/10 font-medium' }}"
            :class="{
                'lg:justify-center lg:px-0': $store.sidebar.collapsed,
                'gap-3 px-3': !$store.sidebar.collapsed
            }"
            :title="$store.sidebar.collapsed ? 'Dashboard' : ''"
        >
            <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-all {{ request()->routeIs('dashboard') ? 'bg-white text-[#1E3A8A] shadow-xs' : 'bg-white/15 text-white group-hover:bg-white group-hover:text-[#1E3A8A]' }}">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                </svg>
            </div>
            <span class="whitespace-nowrap transition-opacity duration-200" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen" x-transition>
                Dashboard
            </span>
        </a>

        @if($user && $user->isRequester())
            @include('layouts.partials.sidebar-create-ticket-link')
        @endif

        <!-- Section: Sistem Tiket -->
        <div class="pt-3 mt-2 border-t border-white/10">
            <div class="px-3 mb-1" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen" x-transition>
                <span class="text-xs font-bold uppercase tracking-wider text-blue-200/80">TIKET & LAYANAN</span>
            </div>

            @if($user?->isItDeveloper())
                @foreach($developerQueueItems as $queue => $label)
                    @php $isActiveDeveloperQueue = $activeDeveloperQueue === $queue; @endphp
                    <a
                        href="{{ route('tickets.index', ['developer_queue' => $queue]) }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all group {{ $isActiveDeveloperQueue ? 'bg-white/20 text-white font-bold shadow-xs ring-1 ring-white/20' : 'text-blue-100 hover:text-white hover:bg-white/10 font-medium' }}"
                        :class="{
                            'lg:justify-center lg:px-0': $store.sidebar.collapsed,
                            'gap-3 px-3': !$store.sidebar.collapsed
                        }"
                        :title="$store.sidebar.collapsed ? '{{ $label }}' : ''"
                    >
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-all {{ $isActiveDeveloperQueue ? 'bg-[#2DCE89] text-white' : 'bg-white/15 text-emerald-300 group-hover:bg-[#2DCE89] group-hover:text-white' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($queue === 'active')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2M12 3a9 9 0 110 18 9 9 0 010-18z" />
                                @elseif($queue === 'unassigned')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                @endif
                            </svg>
                        </div>
                        <span class="whitespace-nowrap transition-opacity duration-200" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen" x-transition>
                            {{ $label }}
                        </span>
                    </a>
                @endforeach
            @elseif($user?->isAdmin())
                <a
                    href="{{ route('tickets.index', ['status' => \App\Models\Ticket::STATUS_READY_FOR_WORK]) }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all group {{ $isPoolMenuActive ? 'bg-white/20 text-white font-bold shadow-xs ring-1 ring-white/20' : 'text-blue-100 hover:text-white hover:bg-white/10 font-medium' }}"
                    :class="{
                        'lg:justify-center lg:px-0': $store.sidebar.collapsed,
                        'gap-3 px-3': !$store.sidebar.collapsed
                    }"
                    :title="$store.sidebar.collapsed ? 'Pool Tiket Siap Dikerjakan' : ''"
                >
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-all {{ $isPoolMenuActive ? 'bg-[#2DCE89] text-white' : 'bg-white/15 text-emerald-300 group-hover:bg-[#2DCE89] group-hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                    </div>
                    <span class="whitespace-nowrap transition-opacity duration-200" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen" x-transition>
                        Pool Tiket Siap Dikerjakan
                    </span>
                </a>
            @endif

            <!-- Daftar Tiket -->
            <a
                href="{{ route('tickets.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all group relative {{ $isGeneralTicketMenuActive ? 'bg-white/20 text-white font-bold shadow-xs backdrop-blur-xs ring-1 ring-white/20' : 'text-blue-100 hover:text-white hover:bg-white/10 font-medium' }}"
                :class="{
                    'lg:justify-center lg:px-0': $store.sidebar.collapsed,
                    'gap-3 px-3': !$store.sidebar.collapsed
                }"
                :title="$store.sidebar.collapsed ? 'Daftar Tiket' : ''"
            >
                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-all {{ $isGeneralTicketMenuActive ? 'bg-[#F5365C] text-white shadow-xs' : 'bg-white/15 text-rose-300 group-hover:bg-[#F5365C] group-hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />
                    </svg>
                </div>
                <span class="whitespace-nowrap transition-opacity duration-200" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen" x-transition>
                    Daftar Tiket
                </span>
            </a>

            <!-- Kalender & Timeline -->
            <a
                href="{{ route('tickets.calendar') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all group relative {{ request()->routeIs('tickets.calendar') ? 'bg-white/20 text-white font-bold shadow-xs backdrop-blur-xs ring-1 ring-white/20' : 'text-blue-100 hover:text-white hover:bg-white/10 font-medium' }}"
                :class="{
                    'lg:justify-center lg:px-0': $store.sidebar.collapsed,
                    'gap-3 px-3': !$store.sidebar.collapsed
                }"
                :title="$store.sidebar.collapsed ? 'Kalender Timeline' : ''"
            >
                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-all {{ request()->routeIs('tickets.calendar') ? 'bg-amber-500 text-white shadow-xs' : 'bg-white/15 text-amber-200 group-hover:bg-amber-500 group-hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="whitespace-nowrap transition-opacity duration-200" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen" x-transition>
                    Kalender Timeline
                </span>
            </a>

            <!-- Supervisor dan admin tetap melihat aksi ini setelah daftar tiket. -->
            @if($user && ($user->isSupervisor() || $user->isAdmin()))
                @include('layouts.partials.sidebar-create-ticket-link')
            @endif
        </div>

        <!-- Section: Workflow Peran -->
        @if($user && ($canApproveSupervisor || $canApproveItHead || $canApproveKadiv || $canApproveDireksi))
            <div class="pt-3 mt-2 border-t border-white/10">
                <div class="px-3 mb-1" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen" x-transition>
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-200/80">OTORISASI & WORKFLOW</span>
                </div>

                @if($canApproveSupervisor)
                    <a
                        href="{{ route('tickets.index', ['status' => \App\Models\Ticket::STATUS_WAITING_SUPERVISOR]) }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all group {{ $isSupervisorMenuActive ? 'bg-white/20 text-white font-bold shadow-xs ring-1 ring-white/20' : 'text-blue-100 hover:text-white hover:bg-white/10 font-medium' }}"
                        :class="{
                            'lg:justify-center lg:px-0': $store.sidebar.collapsed,
                            'gap-3 px-3': !$store.sidebar.collapsed
                        }"
                        :title="$store.sidebar.collapsed ? 'Otorisasi Atasan' : ''"
                    >
                        <div class="w-7 h-7 rounded-lg bg-white/15 text-amber-300 flex items-center justify-center shrink-0 group-hover:bg-[#FB6340] group-hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <span class="whitespace-nowrap transition-opacity duration-200" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen" x-transition>
                            Otorisasi Atasan
                        </span>
                    </a>
                @endif
                @if($canApproveItHead)
                    <a
                        href="{{ route('tickets.index', ['status' => \App\Models\Ticket::STATUS_WAITING_IT_HEAD]) }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all group {{ $isItHeadMenuActive ? 'bg-white/20 text-white font-bold shadow-xs ring-1 ring-white/20' : 'text-blue-100 hover:text-white hover:bg-white/10 font-medium' }}"
                        :class="{
                            'lg:justify-center lg:px-0': $store.sidebar.collapsed,
                            'gap-3 px-3': !$store.sidebar.collapsed
                        }"
                        :title="$store.sidebar.collapsed ? 'Klasifikasi Kabag IT' : ''"
                    >
                        <div class="w-7 h-7 rounded-lg bg-white/15 text-sky-300 flex items-center justify-center shrink-0 group-hover:bg-[#1171EF] group-hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <span class="whitespace-nowrap transition-opacity duration-200" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen" x-transition>
                            Klasifikasi Kabag IT
                        </span>
                    </a>
                @endif

                @if($canApproveKadiv || $canApproveDireksi)
                    <a
                        href="{{ route('tickets.index', ['status' => $canApproveDireksi && !$canApproveKadiv ? \App\Models\Ticket::STATUS_WAITING_LOA_DIREKSI : \App\Models\Ticket::STATUS_WAITING_LOA_KADIV_IT]) }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all group {{ $isExecutiveMenuActive ? 'bg-white/20 text-white font-bold shadow-xs ring-1 ring-white/20' : 'text-blue-100 hover:text-white hover:bg-white/10 font-medium' }}"
                        :class="{
                            'lg:justify-center lg:px-0': $store.sidebar.collapsed,
                            'gap-3 px-3': !$store.sidebar.collapsed
                        }"
                        :title="$store.sidebar.collapsed ? 'Otorisasi Eksekutif LOA' : ''"
                    >
                        <div class="w-7 h-7 rounded-lg bg-white/15 text-purple-300 flex items-center justify-center shrink-0 group-hover:bg-[#825EE4] group-hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <span class="whitespace-nowrap transition-opacity duration-200" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen" x-transition>
                            LOA Eksekutif
                        </span>
                    </a>
                @endif
            </div>
        @endif

        <!-- Section: Administrasi Sistem (Strictly Admin Only) -->
        @if($user && $user->isAdmin())
            <div class="pt-3 mt-2 border-t border-white/10">
                <div class="px-3 mb-1" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen" x-transition>
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-200/80">ADMINISTRASI</span>
                </div>

                <a
                    href="{{ route('admin.users.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all group {{ request()->routeIs('admin.users.*') ? 'bg-white/20 text-white font-bold shadow-xs ring-1 ring-white/20' : 'text-blue-100 hover:text-white hover:bg-white/10 font-medium' }}"
                    :class="{
                        'lg:justify-center lg:px-0': $store.sidebar.collapsed,
                        'gap-3 px-3': !$store.sidebar.collapsed
                    }"
                    :title="$store.sidebar.collapsed ? 'Kelola Pengguna & Divisi' : ''"
                >
                    <div class="w-7 h-7 rounded-lg bg-white/15 text-indigo-200 flex items-center justify-center shrink-0 group-hover:bg-white group-hover:text-[#1E3A8A] transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <span class="whitespace-nowrap transition-opacity duration-200" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen" x-transition>
                        Kelola Pengguna &amp; Divisi
                    </span>
                </a>

                <a
                    href="{{ route('admin.delegations.index') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all group {{ request()->routeIs('admin.delegations.*') ? 'bg-white/20 text-white font-bold shadow-xs ring-1 ring-white/20' : 'text-blue-100 hover:text-white hover:bg-white/10 font-medium' }}"
                    :class="{
                        'lg:justify-center lg:px-0': $store.sidebar.collapsed,
                        'gap-3 px-3': !$store.sidebar.collapsed
                    }"
                    :title="$store.sidebar.collapsed ? 'Delegasi Approval' : ''"
                >
                    <div class="w-7 h-7 rounded-lg bg-white/15 text-amber-200 flex items-center justify-center shrink-0 group-hover:bg-white group-hover:text-[#1E3A8A] transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </div>
                    <span class="whitespace-nowrap transition-opacity duration-200" x-show="!$store.sidebar.collapsed || $store.sidebar.mobileOpen" x-transition>
                        Delegasi Approval
                    </span>
                </a>
            </div>
        @endif
    </nav>

    <!-- Mobile Quick Account Footer (Hidden on Desktop lg:) -->
    @if($user)
        <div class="p-3 border-t border-white/10 bg-black/15 lg:hidden">
            <div class="flex items-center justify-between gap-2 px-2 py-1.5">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 min-w-0 flex-1">
                    <div class="w-8 h-8 rounded-full bg-white text-[#1E3A8A] font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-white truncate">{{ $user->name }}</div>
                        <div class="text-[11px] text-blue-200 truncate">Profil Akun &rarr;</div>
                    </div>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="p-2 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-200 hover:text-white transition-colors cursor-pointer" title="Keluar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    @endif
</aside>
