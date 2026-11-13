<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="app-base-url" content="{{ rtrim(url('/'), '/') }}">

        <title>{{ config('app.name', 'NALA') }} - Navigasi Aduan dan Layanan Andal</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body x-data class="font-sans antialiased bg-[#F8FAFC] text-slate-900 min-h-screen selection:bg-blue-100 selection:text-blue-900">
        <div class="flex min-h-screen bg-[#F8FAFC]">
            <!-- Collapsible Sidebar -->
            @include('layouts.sidebar')

            <!-- Main Layout Column (In flex flow with sidebar on desktop) -->
            <div class="min-h-screen flex flex-col flex-1 min-w-0 transition-all duration-300 ease-in-out">
                <!-- Unified Topbar (Includes Page Header Title on Left & Notifications/User on Right) -->
                @include('layouts.topbar', ['header' => $header ?? null])

                @if (session('login_greeting'))
                    @php
                        $loginHour = now('Asia/Jakarta')->hour;
                        $loginSalutation = match (true) {
                            $loginHour >= 5 && $loginHour < 11 => 'pagi',
                            $loginHour >= 11 && $loginHour < 15 => 'siang',
                            $loginHour >= 15 && $loginHour < 18 => 'sore',
                            default => 'malam',
                        };
                    @endphp
                    <div
                        x-data="{ show: true, timer: null, close() { this.show = false; clearTimeout(this.timer); document.activeElement?.blur() } }"
                        x-init="$nextTick(() => $refs.closeButton.focus()); timer = setTimeout(() => close(), 5000)"
                        x-show="show"
                        x-transition.opacity.duration.200ms
                        @keydown.escape.window="if (show) close()"
                        @click.self="close()"
                        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-[2px]"
                        style="display: none;"
                    >
                        <div role="dialog" aria-modal="true" aria-labelledby="login-greeting-title" aria-describedby="login-greeting-message" class="relative w-full max-w-lg rounded-2xl bg-white px-6 py-8 text-center shadow-2xl sm:px-10 sm:py-10">
                            <button type="button" x-ref="closeButton" @click="close()" class="absolute right-4 top-4 rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]" aria-label="Tutup sapaan">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-[#1E40AF]">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 3v2m0 14v2M3 12h2m14 0h2M5.64 5.64l1.42 1.42m9.88 9.88 1.42 1.42M18.36 5.64l-1.42 1.42M7.06 16.94l-1.42 1.42M12 8a4 4 0 100 8 4 4 0 000-8z" />
                                </svg>
                            </div>
                            <h2 id="login-greeting-title" class="mt-5 text-xl font-bold text-slate-900 sm:text-2xl">Selamat {{ $loginSalutation }}, Rekan!</h2>
                            <p id="login-greeting-message" class="mt-3 text-sm leading-relaxed text-slate-600 sm:text-base">Semoga aktivitas hari ini berjalan lancar dan menyenangkan.</p>
                            <button type="button" @click="close()" class="mt-7 inline-flex items-center justify-center rounded-xl bg-[#1E40AF] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#1D4ED8] focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2">Lanjutkan</button>
                        </div>
                    </div>
                @endif

                <!-- Flash Alerts (FR-007) -->
                @if (session('success') || session('error') || $errors->any())
                    <div class="w-full px-4 sm:px-6 lg:px-8 pt-4">
                        @if (session('success'))
                            <div x-data="{ show: true }" x-show="show" x-transition class="mb-3 bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-2.5 rounded-xl text-sm flex items-center justify-between shadow-xs">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="font-medium text-xs sm:text-sm">{{ session('success') }}</span>
                                </div>
                                <button type="button" @click="show = false" class="text-emerald-500 hover:text-emerald-700 p-1 rounded-md" aria-label="Close">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endif

                        @if (session('error') || $errors->any())
                            <div x-data="{ show: true }" x-show="show" x-transition class="mb-3 bg-rose-50 border border-rose-200 text-rose-900 px-4 py-2.5 rounded-xl text-sm flex items-center justify-between shadow-xs">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="font-medium text-xs sm:text-sm">
                                        {{ session('error') ?? $errors->first() }}
                                    </span>
                                </div>
                                <button type="button" @click="show = false" class="text-rose-500 hover:text-rose-700 p-1 rounded-md" aria-label="Close">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Main Page Content -->
                <main class="flex-1 px-3.5 sm:px-6 lg:px-8 pt-4 sm:py-6 pb-24 lg:pb-6 w-full">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Mobile Bottom Navigation Bar (Hidden on Desktop lg:) -->
        @include('layouts.mobile-bottom-nav')
    </body>
</html>
