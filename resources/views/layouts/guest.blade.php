<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'NALA IT Helpdesk') }} - Masuk</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased selection:bg-blue-600 selection:text-white">
        <main class="relative min-h-screen overflow-hidden bg-slate-100 px-4 py-8 sm:px-6">
            <div class="absolute inset-x-0 top-0 h-1 bg-[#1E40AF]"></div>
            <div class="absolute -left-24 -top-24 h-72 w-72 rounded-full bg-blue-200/40"></div>
            <div class="absolute -bottom-32 -right-24 h-80 w-80 rounded-full bg-slate-200/70"></div>

            <div class="relative mx-auto flex min-h-[calc(100vh-4rem)] max-w-md items-center justify-center">
                <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-300/30 sm:p-8">
                    <a href="/" class="mx-auto mb-8 block w-fit" aria-label="NALA IT Helpdesk">
                        <img
                            src="{{ asset('images/logo-nala.png') }}"
                            alt="NALA"
                            class="h-14 w-auto max-w-full object-contain"
                        >
                    </a>

                    {{ $slot }}
                </div>
            </div>
        </main>
    </body>
</html>
