<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 py-1">
            <div class="min-w-0">
                <h1 class="truncate text-base font-bold tracking-tight text-slate-900 sm:text-2xl">Notifikasi</h1>
                <p class="mt-0.5 hidden text-sm text-slate-500 sm:block">Pembaruan tiket dan tindakan yang perlu Anda lakukan</p>
            </div>
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn-secondary whitespace-nowrap px-3 py-2 text-xs">Tandai semua dibaca</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div data-ajax-poll="notifications-list">
    <div class="mb-4 flex items-center gap-2" aria-label="Filter notifikasi">
        <a href="{{ route('notifications.index') }}" @if($filter === 'all') aria-current="page" @endif class="rounded-lg px-3 py-2 text-sm font-semibold {{ $filter === 'all' ? 'bg-[#1E40AF] text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">Semua</a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" @if($filter === 'unread') aria-current="page" @endif class="rounded-lg px-3 py-2 text-sm font-semibold {{ $filter === 'unread' ? 'bg-[#1E40AF] text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">Belum dibaca @if($unreadCount > 0)<span class="ml-1 opacity-80">{{ $unreadCount }}</span>@endif</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
        @forelse($notifications as $notification)
            @php
                $eventLabel = match($notification->data['event'] ?? null) {
                    'status_changed' => 'Perubahan status',
                    'progress_added' => 'Progres pekerjaan',
                    'discussion_added' => 'Diskusi tiket',
                    'approval_delegated' => 'Delegasi persetujuan',
                    default => 'Pembaruan tiket',
                };
            @endphp
            <form method="POST" action="{{ route('notifications.read', $notification) }}" class="border-b border-slate-100 last:border-0">
                @csrf
                <button type="submit" class="group flex w-full gap-3 px-4 py-4 text-left transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#1E40AF]/30 sm:px-5 {{ $notification->read_at ? 'bg-white' : 'bg-blue-100/70' }}">
                    <span class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full {{ $notification->read_at ? 'bg-slate-200' : 'bg-[#1E40AF]' }}" aria-hidden="true"></span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                            <span class="text-xs font-semibold text-[#1E40AF]">{{ $eventLabel }}</span>
                            <time class="text-xs text-slate-500" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                        </span>
                        <span class="mt-1 block text-sm sm:text-base {{ $notification->read_at ? 'font-medium text-slate-700' : 'font-bold text-slate-950' }}">{{ $notification->data['title'] ?? 'Pembaruan tiket' }}</span>
                        <span class="mt-1 block text-sm leading-relaxed text-slate-600">{{ $notification->data['message'] ?? '' }}</span>
                        <span class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs font-medium text-slate-500">
                            <span>{{ $notification->data['ticket_number'] ?? '-' }} &bull; {{ $notification->data['ticket_title'] ?? 'Tiket' }}</span>
                            <span class="font-semibold text-[#1E40AF] group-hover:underline">Lihat tiket &rarr;</span>
                        </span>
                    </span>
                </button>
            </form>
        @empty
            <div class="px-4 py-14 text-center">
                <p class="text-sm font-semibold text-slate-700">{{ $filter === 'unread' ? 'Semua notifikasi sudah dibaca' : 'Belum ada notifikasi' }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $filter === 'unread' ? 'Pembaruan baru akan muncul di sini.' : 'Pembaruan tiket akan muncul di sini.' }}</p>
            </div>
        @endforelse

        @if($notifications->hasPages())
            <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-4 sm:px-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
    </div>
</x-app-layout>
