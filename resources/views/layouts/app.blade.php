<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="app-base-url" content="{{ rtrim(url('/'), '/') }}">

        <title>{{ config('app.name', 'NALA') }} - Navigasi Aduan dan Layanan Andal</title>

        <!-- Google Fonts: Plus Jakarta Sans & Inter (Proportional Typography) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

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
