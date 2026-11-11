<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 py-1">
            <div>
                <h1 class="text-base font-bold text-slate-900 sm:text-xl">Delegasi Approval</h1>
                <p class="mt-0.5 hidden text-xs text-slate-500 sm:block">Alihkan kewenangan approval sementara tanpa mengubah atasan asli.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="btn-secondary px-3 py-2 text-xs">Kelola pengguna</a>
        </div>
    </x-slot>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)]">
        <form method="POST" action="{{ route('admin.delegations.store') }}" class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
            @csrf
            <h2 class="text-sm font-bold text-slate-900">Buat delegasi</h2>
            <p class="mt-1 text-xs text-slate-500">Gunakan untuk cuti atau ketidakhadiran sementara.</p>

            <div class="mt-5 space-y-4 text-xs">
                <div>
                    <label class="mb-1.5 block font-semibold text-slate-700">Pejabat asal</label>
                    <select name="delegator_id" required class="w-full rounded-lg border-slate-300 text-xs focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                        <option value="">Pilih pejabat</option>
                        @foreach($approvers as $user)
                            <option value="{{ $user->id }}" @selected(old('delegator_id') == $user->id)>{{ $user->name }} — {{ $user->role }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block font-semibold text-slate-700">Penerima delegasi</label>
                    <select name="delegate_id" required class="w-full rounded-lg border-slate-300 text-xs focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                        <option value="">Pilih pengganti</option>
                        @foreach($delegates as $user)
                            <option value="{{ $user->id }}" @selected(old('delegate_id') == $user->id)>{{ $user->name }} — {{ $user->role }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block font-semibold text-slate-700">Cakupan</label>
                    <select name="scope" required class="w-full rounded-lg border-slate-300 text-xs focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                        <option value="all">Semua kewenangan</option>
                        <option value="supervisor">Atasan langsung</option>
                        <option value="it_head">Kepala Bagian IT</option>
                        <option value="kadiv_it">Kadiv IT</option>
                        <option value="direksi">Direksi</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block font-semibold text-slate-700">Mulai</label>
                        <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required class="w-full rounded-lg border-slate-300 text-xs focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                    </div>
                    <div>
                        <label class="mb-1.5 block font-semibold text-slate-700">Berakhir</label>
                        <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" required class="w-full rounded-lg border-slate-300 text-xs focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block font-semibold text-slate-700">Alasan</label>
                    <textarea name="reason" rows="3" required maxlength="500" class="w-full rounded-lg border-slate-300 text-xs focus:border-[#1E40AF] focus:ring-[#1E40AF]" placeholder="Contoh: Cuti tahunan">{{ old('reason') }}</textarea>
                </div>
            </div>

            <button type="submit" class="btn-primary mt-5 w-full cursor-pointer text-xs">Aktifkan delegasi</button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-bold text-slate-900">Riwayat delegasi</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($delegations as $delegation)
                    @php
                        $delegationStatus = !$delegation->is_active
                            ? 'Nonaktif'
                            : ($delegation->starts_at->isFuture()
                                ? 'Terjadwal'
                                : ($delegation->ends_at->isPast() ? 'Berakhir' : 'Aktif'));
                    @endphp
                    <div class="p-4 sm:px-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">{{ $delegation->delegator->name }} → {{ $delegation->delegate->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $delegation->starts_at->format('d M Y H:i') }} – {{ $delegation->ends_at->format('d M Y H:i') }}</p>
                                <p class="mt-1 text-xs text-slate-600">{{ $delegation->reason }} &bull; {{ $delegation->scope }}</p>
                            </div>
                            @if($delegation->is_active && !$delegation->ends_at->isPast())
                                <form method="POST" action="{{ route('admin.delegations.destroy', $delegation) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">Nonaktifkan</button>
                                </form>
                            @else
                                <span class="text-xs font-medium text-slate-400">{{ $delegationStatus }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-12 text-center text-sm text-slate-400">Belum ada delegasi.</div>
                @endforelse
            </div>
            @if($delegations->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $delegations->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
