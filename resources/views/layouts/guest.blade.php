<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'NALA IT Helpdesk') }} - Masuk</title>

        <!-- Google Fonts: Plus Jakarta Sans & Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased bg-[#0B1437] selection:bg-blue-600 selection:text-white">
        <div
            class="min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-10 bg-cover bg-center bg-no-repeat"
            style="background-image: linear-gradient(135deg, rgba(7, 14, 38, 0.78) 0%, rgba(30, 58, 138, 0.62) 50%, rgba(10, 17, 40, 0.85) 100%), url('{{ asset('images/login-bg.png') }}');"
        >
            <!-- Main Centered Container (Zero backdrop-filter / blur for 0% GPU load on macOS Retina) -->
            <div class="w-full max-w-5xl">
                <!-- Mobile Top Logo (Only visible below lg) -->
                <div class="lg:hidden mb-6 text-center">
                    <a href="/" class="inline-flex flex-col items-center gap-2">
                        <div class="px-5 py-2.5 rounded-2xl bg-[#0F172A]/85 border border-white/20 shadow-md">
                            <img src="{{ asset('images/logo-nala-white.png') }}" alt="NALA - Navigasi Aduan dan Layanan Andal" class="h-9 w-auto object-contain">
                        </div>
                    </a>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 rounded-3xl overflow-hidden shadow-2xl border border-white/15 bg-white">

                    <!-- Left Visual Hero Panel (Desktop lg: 7 Columns) -->
                    <div
                        class="hidden lg:flex lg:col-span-7 relative flex-col justify-between p-10 xl:p-12 text-white overflow-hidden bg-cover bg-center"
                        style="background-image: linear-gradient(180deg, rgba(30, 64, 175, 0.55) 0%, rgba(23, 37, 84, 0.82) 55%, rgba(7, 14, 38, 0.95) 100%), url('{{ asset('images/login-bg.png') }}');"
                    >
                        <!-- Top Brand Header -->
                        <div class="relative z-10 flex items-center justify-between">
                            <a href="/" class="inline-flex items-center gap-3">
                                <img src="{{ asset('images/logo-nala-white.png') }}" alt="NALA" class="h-11 w-auto object-contain">
                            </a>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-[#0F172A]/60 border border-white/20 text-blue-100">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                Enterprise IT Service Management
                            </span>
                        </div>

                        <!-- Center Headline & Feature Highlights -->
                        <div class="relative z-10 my-auto py-8">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-[#1E3A8A]/70 border border-blue-300/30 text-cyan-200 text-xs font-semibold mb-4">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <span>Navigasi Aduan dan Layanan Andal</span>
                            </div>

                            <h1 class="text-2xl xl:text-3xl font-extrabold tracking-tight leading-snug text-white">
                                Portal Terpadu Pengajuan &amp; Pengawalan Layanan IT Korporat
                            </h1>
                            <p class="mt-3 text-xs xl:text-sm text-blue-100/90 leading-relaxed max-w-lg">
                                Kelola permohonan dukungan teknis, persetujuan berjenjang (LOA), hingga pemantauan timeline pengerjaan secara transparan dan terukur dalam satu ekosistem.
                            </p>

                            <!-- 2 Feature Cards (Solid semi-transparent without GPU backdrop-blur) -->
                            <div class="mt-7 grid grid-cols-1 gap-3">
                                <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-[#0F172A]/65 border border-white/15">
                                    <div class="w-9 h-9 rounded-xl bg-cyan-500/20 border border-cyan-300/30 text-cyan-200 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-white">Otorisasi Berjenjang &amp; Audit Trail Lengkap</div>
                                        <div class="text-[11px] text-blue-100/80 mt-0.5 leading-relaxed">Alur persetujuan terstruktur mulai dari Atasan Langsung, Kabag IT, hingga Kadiv IT &amp; Direksi.</div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-[#0F172A]/65 border border-white/15">
                                    <div class="w-9 h-9 rounded-xl bg-blue-500/20 border border-blue-300/30 text-blue-200 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-white">Kalender Gantt &amp; Monitoring SLA Real-Time</div>
                                        <div class="text-[11px] text-blue-100/80 mt-0.5 leading-relaxed">Pantau target penyelesaian tiket, diskusi langsung dengan teknisi, dan cetak berita acara resmi.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Copyright / Security Badge -->
                        <div class="relative z-10 pt-4 border-t border-white/15 flex items-center justify-between text-[11px] text-blue-200/80">
                            <span>&copy; {{ date('Y') }} NALA IT Helpdesk System</span>
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Koneksi Terenkripsi Internal
                            </span>
                        </div>
                    </div>

                    <!-- Right Form Panel (Desktop lg: 5 Columns) -->
                    <div class="lg:col-span-5 bg-white p-6 sm:p-9 xl:p-10 flex flex-col justify-center">
                        {{ $slot }}
                    </div>

                </div>
            </div>
        </div>
    </body>
</html>
