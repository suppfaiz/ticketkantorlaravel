<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 py-1">
            <div class="min-w-0">
                <h1 class="truncate text-base font-bold tracking-tight text-slate-900 sm:text-2xl">Notifikasi</h1>
                <p class="mt-0.5 hidden text-xs text-slate-500 sm:block">Riwayat pembaruan tiket yang ditujukan kepada Anda</p>
            </div>
            @if(auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn-secondary whitespace-nowrap px-3 py-2 text-xs">Tandai semua dibaca</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div data-ajax-poll="notifications-list" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
        @forelse($notifications as $notification)
            <form method="POST" action="{{ route('notifications.read', $notification) }}" class="border-b border-slate-100 last:border-0">
                @csrf
                <button type="submit" class="flex w-full gap-3 px-4 py-4 text-left transition hover:bg-slate-50 sm:px-6 {{ $notification->read_at ? 'bg-white' : 'bg-blue-50/50' }}">
                    <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $notification->read_at ? 'bg-slate-200' : 'bg-[#1E40AF]' }}"></span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-slate-900">{{ $notification->data['title'] ?? 'Pembaruan tiket' }}</span>
                            <time class="shrink-0 text-[11px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</time>
                        </span>
                        <span class="mt-1 block text-xs leading-relaxed text-slate-600">{{ $notification->data['message'] ?? '' }}</span>
                        <span class="mt-1.5 block text-[11px] font-medium text-[#1E40AF]">
                            {{ $notification->data['ticket_number'] ?? '-' }} &bull; {{ $notification->data['ticket_title'] ?? 'Tiket' }}
                        </span>
                    </span>
                </button>
            </form>
        @empty
            <div class="px-4 py-14 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada notifikasi</p>
                <p class="mt-1 text-xs text-slate-400">Pembaruan tiket akan muncul di sini.</p>
            </div>
        @endforelse

        @if($notifications->hasPages())
            <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-4 sm:px-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
