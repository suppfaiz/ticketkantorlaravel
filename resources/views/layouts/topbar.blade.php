@php
    $authUser = Auth::user();
    $notifications = collect();
    if ($authUser) {
        if ($authUser->isSupervisor()) {
            $notifications = \App\Models\Ticket::where('supervisor_id', $authUser->id)
                ->where('status', \App\Models\Ticket::STATUS_WAITING_SUPERVISOR)
                ->latest('updated_at')->get();
        } elseif ($authUser->isItHead() || $authUser->isAdmin()) {
            $notifications = \App\Models\Ticket::whereIn('status', [
                \App\Models\Ticket::STATUS_WAITING_IT_HEAD,
                \App\Models\Ticket::STATUS_WAITING_MAJOR_MEETING,
                \App\Models\Ticket::STATUS_WAITING_MINUTES_APPROVAL,
            ])->latest('updated_at')->get();
        } elseif ($authUser->isKadivIt()) {
            $notifications = \App\Models\Ticket::where('status', \App\Models\Ticket::STATUS_WAITING_LOA_KADIV_IT)
                ->latest('updated_at')->get();
        } elseif ($authUser->isDireksi()) {
            $notifications = \App\Models\Ticket::where('status', \App\Models\Ticket::STATUS_WAITING_LOA_DIREKSI)
                ->latest('updated_at')->get();
        } elseif ($authUser->isItDeveloper()) {
            $notifications = \App\Models\Ticket::where(function($q) use ($authUser) {
                $q->where('status', \App\Models\Ticket::STATUS_READY_FOR_WORK)
                  ->orWhere(function($sq) use ($authUser) {
                      $sq->where('assigned_to', $authUser->id)
                         ->whereIn('status', [\App\Models\Ticket::STATUS_IN_PROGRESS, \App\Models\Ticket::STATUS_REVISION_WORK]);
                  });
            })->latest('updated_at')->get();
        } else {
            // Requester
            $notifications = \App\Models\Ticket::where('requester_id', $authUser->id)
                ->whereIn('status', [
                    \App\Models\Ticket::STATUS_REVISION_REQUESTER,
                    \App\Models\Ticket::STATUS_REVISION_BY_IT_HEAD,
                    \App\Models\Ticket::STATUS_WAITING_USER_MINUTES,
                    \App\Models\Ticket::STATUS_REVISION_MINUTES,
                    \App\Models\Ticket::STATUS_WAITING_USER_REVIEW,
                    \App\Models\Ticket::STATUS_REJECTED_SUPERVISOR,
                    \App\Models\Ticket::STATUS_REJECTED_IT_HEAD,
                    \App\Models\Ticket::STATUS_REJECTED_KADIV_IT,
                    \App\Models\Ticket::STATUS_REJECTED_DIREKSI,
                    \App\Models\Ticket::STATUS_COMPLETED,
                ])->latest('updated_at')->get();
        }
    }
    $notifCount = $notifications->count();
@endphp

<header
    x-data="{
        showNotifications: false,
        showApps: false,
        showSopModal: false
    }"
    class="min-h-16 py-2.5 bg-white max-sm:bg-gradient-to-r max-sm:from-[#1E40AF] max-sm:via-[#1E3A8A] max-sm:to-[#172554] border-b border-slate-200/80 max-sm:border-0 px-4 sm:px-6 lg:px-8 flex items-center justify-between sticky top-0 z-30 shadow-2xs max-sm:shadow-md w-full"
>
    <div class="w-full flex items-center justify-between gap-3 sm:gap-4">
    <!-- Left Section: Mobile NALA Emblem + Page Header Title -->
    <div class="flex items-center gap-2.5 sm:gap-3 flex-1 min-w-0">
        <a href="{{ route('dashboard') }}" class="sm:hidden w-8 h-8 rounded-xl bg-white/15 backdrop-blur-xs ring-1 ring-white/25 flex items-center justify-center shrink-0">
            <img src="{{ asset('images/logo-nala-icon-white.png') }}" alt="NALA" class="w-5 h-5 object-contain">
        </a>
        @if(!empty($header))
            <div class="flex-1 min-w-0 [&_h1]:max-sm:!text-white [&_p]:max-sm:!text-blue-100 [&_a.btn-secondary]:max-sm:!bg-white/15 [&_a.btn-secondary]:max-sm:!text-white [&_a.btn-secondary]:max-sm:!border-white/25 [&_a.btn-secondary_svg]:max-sm:!text-white">
                {{ $header }}
            </div>
        @endif
    </div>

    <!-- Right Section: Notification Bell & User Profile -->
    <div class="flex items-center gap-2 sm:gap-3 shrink-0">

        <!-- Notification Bell Dropdown (Desktop & Tablet sm:, on Mobile it's in the Bottom Nav) -->
        <div class="relative hidden sm:block">
            <button
                type="button"
                @click="showNotifications = !showNotifications; showApps = false"
                class="relative p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition-colors focus:outline-none cursor-pointer"
                aria-label="Notifikasi Aktivitas"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span data-ajax-poll="topbar-notif-dot">
                    @if($notifCount > 0)
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-[#F5365C] rounded-full ring-2 ring-white"></span>
                    @endif
                </span>
            </button>

            <!-- Dropdown Panel Notifications -->
            <div
                x-show="showNotifications"
                @click.away="showNotifications = false"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="fixed inset-x-3 top-16 sm:absolute sm:inset-auto sm:right-0 sm:mt-2 sm:w-[340px] bg-white rounded-xl shadow-lg border border-slate-200 z-50 overflow-hidden"
                style="display: none;"
                data-ajax-poll="topbar-notif-panel"
            >
                <div class="px-4 py-2.5 border-b border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-900">Notifikasi ({{ $notifCount }})</span>
                    <a href="{{ route('notifications.index') }}" class="text-2xs font-medium text-slate-500 hover:text-slate-900 transition-colors">
                        Lihat Semua
                    </a>
                </div>

                <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto custom-scrollbar">
                    @forelse($notifications->take(5) as $item)
                        <a href="{{ route('tickets.show', $item) }}" class="block px-4 py-2.5 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs font-medium text-slate-900 truncate">
                                    {{ $item->title }}
                                </span>
                                <span class="text-[11px] text-slate-400 tabular-nums shrink-0">{{ $item->updated_at->diffForHumans() }}</span>
                            </div>
                            <div class="mt-0.5 text-[11px] text-slate-500 truncate tabular-nums">
                                {{ $item->ticket_number }} &bull; {{ $item->status }}
                            </div>
                        </a>
                    @empty
                        <div class="py-6 px-4 text-center">
                            <p class="text-xs font-medium text-slate-600">Tidak ada notifikasi baru</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- User Profile Dropdown -->
        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="inline-flex items-center gap-2 py-1 px-1.5 rounded-xl max-sm:bg-white/15 max-sm:ring-1 max-sm:ring-white/25 hover:bg-slate-50 max-sm:hover:bg-white/20 transition-colors focus:outline-none cursor-pointer">
                    <!-- Circular User Avatar with Initial -->
                    <div class="w-8 h-8 max-sm:w-7 max-sm:h-7 rounded-full bg-gradient-to-tr from-[#1E40AF] to-[#2563EB] max-sm:from-white max-sm:to-blue-50 text-white max-sm:text-[#1E40AF] flex items-center justify-center font-bold text-xs shadow-xs ring-2 ring-white max-sm:ring-0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <span class="font-bold text-slate-800 text-xs hidden sm:inline max-w-[120px] truncate">
                        {{ Auth::user()->name }}
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-400 max-sm:text-blue-100" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="px-4 py-2 border-b border-slate-100">
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Masuk sebagai</p>
                    <p class="text-xs font-bold text-slate-800 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ Auth::user()->email }}</p>
                </div>

                @if(Auth::user()?->isAdmin())
                    <x-dropdown-link :href="route('admin.users.index')" class="flex items-center gap-2 text-xs font-semibold text-[#1E40AF]">
                        <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Kelola Pengguna &amp; Divisi</span>
                    </x-dropdown-link>
                @endif

                <x-dropdown-link :href="route('tickets.calendar')" class="flex items-center gap-2 text-xs">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Kalender Timeline</span>
                </x-dropdown-link>

                <x-dropdown-link :href="route('profile.edit')" class="flex items-center gap-2 text-xs">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ __('Profil Akun') }}
                </x-dropdown-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link
                        :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                        class="flex items-center gap-2 text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                    >
                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        {{ __('Keluar') }}
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
    </div>

    <!-- SOP & Documentation Modal -->
    <div
        x-show="showSopModal"
        style="display: none;"
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex items-center justify-center min-h-screen px-4 py-6 text-center">
            <div
                x-show="showSopModal"
                x-transition.opacity
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"
                @click="showSopModal = false"
            ></div>

            <div
                x-show="showSopModal"
                x-transition
                class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all w-full max-w-2xl border border-slate-100 p-6 z-10"
            >
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Panduan Alur Kerja NALA (SOP)</h3>
                            <p class="text-xs text-slate-500">Standar Operasional Prosedur Layanan IT Terpadu</p>
                        </div>
                    </div>
                    <button type="button" @click="showSopModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4 space-y-4 text-xs max-h-[65vh] overflow-y-auto pr-1">
                    <!-- Step A -->
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-[#1E40AF] text-white flex items-center justify-center font-bold text-xs shrink-0">A</span>
                        <div>
                            <h4 class="font-bold text-slate-900">Tahap A: Pengajuan Tiket oleh Karyawan</h4>
                            <p class="text-slate-600 mt-0.5 leading-relaxed">Karyawan mengisi permohonan tiket. Sistem membangkitkan nomor unik collision-safe berformat <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 tabular-nums text-xs">TICK-YYYYMMDD-XXXX</code> dan mengunggah lampiran aman.</p>
                        </div>
                    </div>

                    <!-- Step B -->
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-[#FB6340] text-white flex items-center justify-center font-bold text-xs shrink-0">B</span>
                        <div>
                            <h4 class="font-bold text-slate-900">Tahap B: Otorisasi LOA Atasan Langsung</h4>
                            <p class="text-slate-600 mt-0.5 leading-relaxed">Atasan langsung memeriksa permohonan bawahan: <em>Setujui</em> (diteruskan ke Kabag IT), <em>Minta Revisi</em> (dikembalikan ke karyawan), atau <em>Tolak</em> (dihentikan permanen).</p>
                        </div>
                    </div>

                    <!-- Step C -->
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-[#11CDEF] text-white flex items-center justify-center font-bold text-xs shrink-0">C</span>
                        <div>
                            <h4 class="font-bold text-slate-900">Tahap C: Klasifikasi Kepala Bagian IT</h4>
                            <ul class="list-disc list-inside text-slate-600 mt-0.5 space-y-1">
                                <li><strong>Minor:</strong> Fast-track langsung masuk ke Pool Siap Dikerjakan.</li>
                                <li><strong>Major:</strong> Wajib otorisasi LOA Kadiv IT &amp; Direksi, pelaksanaan rapat koordinasi, serta verifikasi notulensi.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Step D -->
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-[#2DCE89] text-white flex items-center justify-center font-bold text-xs shrink-0">D</span>
                        <div>
                            <h4 class="font-bold text-slate-900">Tahap D: Pengerjaan Tim IT Developer</h4>
                            <p class="text-slate-600 mt-0.5 leading-relaxed">Teknisi mengambil tiket dari pool terbuka secara atomic (perlindungan konkurensi database), mencatat log aktivitas berkala, dan menyerahkan pekerjaan selesai.</p>
                        </div>
                    </div>

                    <!-- Step E -->
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-100 flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-[#825EE4] text-white flex items-center justify-center font-bold text-xs shrink-0">E</span>
                        <div>
                            <h4 class="font-bold text-slate-900">Tahap E: Review &amp; Sign-Off Pemohon</h4>
                            <p class="text-slate-600 mt-0.5 leading-relaxed">Pemohon memeriksa hasil kerja dan memberikan rating kepuasan (1-5 bintang) untuk menutup tiket secara resmi, atau meminta perbaikan jika ada kendala.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end">
                    <button type="button" @click="showSopModal = false" class="px-4 py-2 text-xs font-semibold text-white bg-[#1E40AF] hover:bg-[#1D4ED8] rounded-xl shadow-xs transition-all">
                        Mengerti &amp; Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</header>
