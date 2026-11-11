@props(['status'])

@php
    $s = strtolower($status ?? '');
    if (str_contains($s, 'ditolak') || str_contains($s, 'rejected')) {
        $badgeClasses = 'bg-rose-50 text-rose-700 border-rose-200';
        $dotClasses = 'bg-rose-500';
    } elseif (
        $status === \App\Models\Ticket::STATUS_COMPLETED ||
        $status === \App\Models\Ticket::STATUS_READY_FOR_WORK ||
        str_contains($s, 'selesai') ||
        str_contains($s, 'siap dikerjakan')
    ) {
        $badgeClasses = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        $dotClasses = 'bg-emerald-500';
    } else {
        $badgeClasses = 'bg-amber-50 text-amber-700 border-amber-200';
        $dotClasses = 'bg-amber-500';
    }
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-medium border {$badgeClasses}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dotClasses }} shrink-0"></span>
    <span>{{ $status }}</span>
</span>
