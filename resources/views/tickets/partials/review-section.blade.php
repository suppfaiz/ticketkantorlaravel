@if($ticket->status === \App\Models\Ticket::STATUS_WAITING_USER_REVIEW || $ticket->reviews->isNotEmpty())
    <div class="p-5 sm:p-6">
        <!-- Simple Header -->
        <div class="flex flex-wrap items-center justify-between gap-2 pb-4 mb-5 border-b border-slate-100">
            <h2 class="text-sm font-bold text-slate-900">Ulasan &amp; Konfirmasi Pemohon</h2>
            @if($ticket->reviews->whereNotNull('rating')->isNotEmpty())
                <span class="text-xs font-medium text-slate-600">
                    Rating: <strong class="text-slate-900">{{ number_format($ticket->reviews->whereNotNull('rating')->avg('rating'), 1) }} / 5</strong>
                </span>
            @endif
        </div>

        <!-- Requester Action Form (Single Clean Monochrome Form with Mode Switch) -->
        @can('reviewTicket', $ticket)
            <div x-data="{ mode: 'accept' }" class="{{ $ticket->reviews->isNotEmpty() ? 'mb-6 pb-6 border-b border-slate-100' : '' }}">
                <!-- Mode Switcher -->
                <div class="inline-flex p-1 rounded-lg bg-slate-100 mb-4">
                    <button
                        type="button"
                        @click="mode = 'accept'"
                        :class="mode === 'accept' ? 'bg-white text-slate-900 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                        class="px-3 py-1.5 rounded-md text-xs transition-all cursor-pointer"
                    >
                        Terima Hasil Pekerjaan
                    </button>
                    <button
                        type="button"
                        @click="mode = 'revise'"
                        :class="mode === 'revise' ? 'bg-white text-slate-900 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                        class="px-3 py-1.5 rounded-md text-xs transition-all cursor-pointer"
                    >
                        Minta Perbaikan (Revisi)
                    </button>
                </div>

                <!-- Form 1: Terima & Selesaikan -->
                <form x-show="mode === 'accept'" method="POST" action="{{ route('tickets.reviews.accept', $ticket) }}" class="space-y-3">
                    @csrf
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <label class="text-xs font-medium text-slate-700">Penilaian Hasil Kerja</label>
                        <select name="rating" required class="text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white py-1.5 pl-3 pr-8">
                            <option value="5" selected>5 - Sangat Sesuai</option>
                            <option value="4">4 - Sesuai</option>
                            <option value="3">3 - Cukup</option>
                            <option value="2">2 - Kurang</option>
                            <option value="1">1 - Tidak Sesuai</option>
                        </select>
                    </div>
                    <div>
                        <textarea name="feedback" rows="3" placeholder="Tulis komentar ulasan (opsional)..."
                            class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary text-xs py-2 px-4 font-semibold cursor-pointer">
                            Terima &amp; Selesaikan Tiket
                        </button>
                    </div>
                </form>

                <!-- Form 2: Minta Revisi -->
                <form x-show="mode === 'revise'" style="display: none;" method="POST" action="{{ route('tickets.reviews.revise', $ticket) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Catatan Perbaikan untuk Teknisi</label>
                        <textarea name="feedback" rows="3" required placeholder="Jelaskan bagian yang masih perlu diperbaiki..."
                            class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-secondary text-xs py-2 px-4 font-semibold cursor-pointer">
                            Kembalikan untuk Revisi
                        </button>
                    </div>
                </form>
            </div>
        @endcan

        <!-- Minimalist Review Timeline -->
        @if($ticket->reviews->isEmpty())
            @cannot('reviewTicket', $ticket)
                <p class="text-xs text-slate-400 py-4 text-center">Belum ada ulasan dari pemohon.</p>
            @endcannot
        @else
            <div class="relative pl-5 space-y-4 before:content-[''] before:absolute before:top-2 before:bottom-2 before:left-1.5 before:w-px before:bg-slate-200">
                @foreach($ticket->reviews as $review)
                    @php
                        $isAccept = $review->decision === 'Accept';
                    @endphp
                    <div class="relative">
                        <span class="absolute -left-5 top-1.5 w-3 h-3 rounded-full ring-4 ring-white bg-slate-400"></span>

                        <div class="text-xs">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-semibold text-slate-900">{{ $isAccept ? 'Disetujui Pemohon' : 'Permintaan Revisi' }}</span>
                                    <span class="text-slate-300">&bull;</span>
                                    <span class="text-slate-500">{{ $review->reviewer->name }}</span>
                                    @if($review->rating)
                                        <span class="text-slate-500">&bull; Rating {{ $review->rating }}/5</span>
                                    @endif
                                </div>
                                <time class="text-slate-400 tabular-nums">{{ $review->created_at->format('d M Y, H:i') }}</time>
                            </div>

                            @if($review->feedback && $review->feedback !== 'Pekerjaan disetujui oleh Pemohon.')
                                <p class="mt-1 text-slate-600 leading-relaxed">{{ $review->feedback }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
