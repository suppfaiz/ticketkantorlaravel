@props(['tickets', 'count'])

<div data-ajax-poll="dashboard-actions">
    @if($count > 0 && $tickets->isNotEmpty())
        @php $firstTicket = $tickets->first(); @endphp
        <section class="mb-5 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm" aria-labelledby="dashboard-actions-title">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-4">
                <div class="flex min-w-0 items-center gap-2.5 lg:w-56 lg:shrink-0">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-[#1E40AF]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h2 id="dashboard-actions-title" class="text-sm font-bold text-slate-900">Perlu tindakan Anda</h2>
                        <p class="text-xs text-slate-500">{{ $count }} tiket menunggu tindakan</p>
                    </div>
                </div>

                <div class="min-w-0 flex-1 lg:border-l lg:border-slate-200 lg:pl-4">
                    <div class="flex min-w-0 items-baseline gap-2">
                        <span class="shrink-0 text-[11px] font-semibold text-[#1E40AF]">{{ $firstTicket->ticket_number }}</span>
                        <span class="truncate text-sm font-semibold text-slate-900" title="{{ $firstTicket->title }}">{{ $firstTicket->title }}</span>
                    </div>
                    <p class="truncate text-xs text-slate-500">{{ $firstTicket->status }}</p>
                </div>

                <a href="{{ route('tickets.show', $firstTicket) }}" class="inline-flex shrink-0 self-start items-center justify-center gap-1.5 rounded-lg bg-[#1E40AF] px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#1D4ED8] focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2 lg:self-auto">
                    Buka tiket
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6l6 6-6 6" />
                    </svg>
                </a>
            </div>
        </section>
    @endif
</div>
