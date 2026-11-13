@props(['tickets', 'count'])

<div data-ajax-poll="dashboard-actions">
    @if($count > 0 && $tickets->isNotEmpty())
        @php $firstTicket = $tickets->first(); @endphp
        <section class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-blue-200 bg-blue-50/70 px-4 py-3.5 sm:px-5" aria-labelledby="dashboard-actions-title">
            <div class="min-w-0">
                <h2 id="dashboard-actions-title" class="text-sm font-bold text-slate-900 sm:text-base">Perlu tindakan Anda</h2>
                <p class="mt-0.5 text-sm text-slate-600">{{ $count }} tiket menunggu langkah dari Anda.</p>
            </div>
            <a href="{{ route('tickets.show', $firstTicket) }}" class="inline-flex shrink-0 items-center rounded-lg bg-[#1E40AF] px-3 py-2 text-xs font-semibold text-white hover:bg-[#1D4ED8] focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2">
                Buka tiket berikutnya &rarr;
            </a>
        </section>
    @endif
</div>
