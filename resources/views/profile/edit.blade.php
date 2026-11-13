<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 py-1">
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Profil Pengguna</h1>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="p-6 bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="w-full">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="p-6 bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="w-full">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="p-6 bg-white rounded-2xl border border-slate-100 shadow-sm lg:col-span-2">
            @php
                $currentUser = Auth::user();
                $roleLabels = [
                    'requester'    => 'Pemohon (Karyawan / Staf)',
                    'supervisor'   => 'Atasan Langsung (Supervisor)',
                    'it_head'      => 'Kepala Bagian IT (Kabag IT)',
                    'kadiv_it'     => 'Kepala Divisi IT & Operasional',
                    'direksi'      => 'Dewan Direksi',
                    'it_developer' => 'Tim Teknis / IT Support & Developer',
                    'admin'        => 'Administrator Sistem',
                ];
            @endphp
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Informasi Kepegawaian &amp; Otorisasi Alur Tiket</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Data struktur organisasi dan hak akses Anda dikelola secara terpusat oleh Administrator Sistem untuk menjaga integritas jejak audit (Audit Trail).</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 self-start sm:self-auto">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Akun Aktif
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70">
                    <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Peran Sistem (Role)</div>
                    <div class="mt-1 text-xs sm:text-sm font-bold text-slate-900">{{ $roleLabels[$currentUser->role] ?? strtoupper($currentUser->role) }}</div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70">
                    <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Divisi / Departemen</div>
                    <div class="mt-1 text-xs sm:text-sm font-bold text-slate-900">{{ $currentUser->departmentRecord?->name ?? $currentUser->department ?? 'Belum Ditentukan' }}</div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70">
                    <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Atasan Peninjau (LOA)</div>
                    <div class="mt-1 text-xs sm:text-sm font-bold text-slate-900">{{ $currentUser->supervisor?->name ?? 'Langsung ke Kepala Bagian IT' }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
