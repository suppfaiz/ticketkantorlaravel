@props([
    'title',
    'value',
    'subtitle' => 'See details',
    'color' => 'purple',
    'href' => null,
    'progress' => null,
])

@php
$tag = $href ? 'a' : 'div';

$gradientMap = [
    // Cohesive Royal Blue & Brand Gradients matching Navbar
    'purple' => [
        'bg' => 'from-[#1E40AF] to-[#2563EB]',
        'shadow' => 'shadow-blue-900/20',
        'progress' => 'bg-white',
    ],
    'cyan' => [
        'bg' => 'from-[#0284C7] to-[#0369A1]',
        'shadow' => 'shadow-sky-500/20',
        'progress' => 'bg-white',
    ],
    'blue' => [
        'bg' => 'from-[#1E40AF] to-[#3B82F6]',
        'shadow' => 'shadow-blue-500/20',
        'progress' => 'bg-white',
    ],
    'coral' => [
        'bg' => 'from-[#F5365C] to-[#FB6340]',
        'shadow' => 'shadow-rose-500/20',
        'progress' => 'bg-white',
    ],
    'amber' => [
        'bg' => 'from-[#FB6340] to-[#F5365C]',
        'shadow' => 'shadow-amber-500/20',
        'progress' => 'bg-white',
    ],
    'dark' => [
        'bg' => 'from-[#1E3A8A] to-[#172554]',
        'shadow' => 'shadow-blue-950/30',
        'progress' => 'bg-[#2DCE89]',
    ],
    'navy' => [
        'bg' => 'from-[#1E3A8A] to-[#172554]',
        'shadow' => 'shadow-blue-950/30',
        'progress' => 'bg-[#2DCE89]',
    ],
    'emerald' => [
        'bg' => 'from-[#2DCE89] to-[#10B981]',
        'shadow' => 'shadow-emerald-500/20',
        'progress' => 'bg-white',
    ],
];

$scheme = $gradientMap[$color] ?? $gradientMap['purple'];
$progressVal = $progress ?? (is_numeric($value) && $value > 0 ? min(100, max(28, $value * 12)) : 55);
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    class="group relative min-h-24 sm:min-h-28 max-sm:bg-none max-sm:bg-white max-sm:border max-sm:border-slate-200/80 max-sm:shadow-2xs bg-gradient-to-r {{ $scheme['bg'] }} rounded-2xl p-3.5 sm:p-5 text-slate-900 sm:text-white sm:shadow-md sm:hover:shadow-xl transition-all duration-200 overflow-hidden flex flex-col justify-between {{ $href ? 'cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2' : '' }}"
>
    <!-- Background subtle shine accent (Desktop Only) -->
    <div class="hidden sm:block absolute -right-5 -bottom-5 w-24 h-24 rounded-full bg-white/15 pointer-events-none blur-xs group-hover:scale-125 transition-transform duration-300"></div>

    <div class="relative z-10">
        <div class="text-xs sm:text-sm font-semibold sm:font-bold text-slate-600 sm:text-white/90 uppercase tracking-wide leading-snug mb-1.5">
            {{ $title }}
        </div>
        <div class="flex items-end justify-between gap-2">
            <div class="text-xl sm:text-3xl font-bold text-slate-900 sm:text-white tracking-tight font-sans tabular-nums leading-none">
                {{ $value }}
            </div>
            @if($href)
                <span class="sm:hidden w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            @endif
        </div>
    </div>
</{{ $tag }}>
