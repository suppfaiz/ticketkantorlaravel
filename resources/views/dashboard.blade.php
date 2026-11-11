<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Dashboard Utama</h1>
            <span class="text-xs text-slate-500">IT Helpdesk Portal</span>
        </div>
    </x-slot>

    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-8 text-center max-w-xl mx-auto my-8">
        <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-600 mx-auto flex items-center justify-center mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
        </div>
        <h2 class="text-lg font-bold text-slate-900">Selamat Datang di IT Helpdesk</h2>
        <p class="text-sm text-slate-500 mt-2">
            Anda berhasil masuk sebagai <strong>{{ auth()->user()->name }}</strong> ({{ ucfirst(auth()->user()->role) }}).
        </p>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('tickets.index') }}" class="btn-primary text-xs">
                Lihat Daftar Tiket
            </a>
            @if(auth()->user()->isRequester() || auth()->user()->isAdmin())
                <a href="{{ route('tickets.create') }}" class="btn-secondary text-xs">
                    Buat Tiket Baru
                </a>
            @endif
        </div>
    </div>
</x-app-layout>
