@props(['priority'])

@php
    $p = strtolower($priority ?? 'low');
    if (in_array($p, ['high', 'tinggi', 'critical', 'urgent', 'darurat'])) {
        $badgeClasses = 'bg-rose-50 text-rose-700 border-rose-200';
        $dotClasses = 'bg-rose-500';
    } elseif (in_array($p, ['medium', 'sedang', 'normal'])) {
        $badgeClasses = 'bg-amber-50 text-amber-700 border-amber-200';
        $dotClasses = 'bg-amber-500';
    } else {
        $badgeClasses = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        $dotClasses = 'bg-emerald-500';
    }
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-medium border {$badgeClasses}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dotClasses }} shrink-0"></span>
    <span>{{ ucfirst($priority ?? 'Low') }}</span>
</span>
