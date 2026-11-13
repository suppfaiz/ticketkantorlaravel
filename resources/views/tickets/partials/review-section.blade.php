@if($ticket->status === \App\Models\Ticket::STATUS_WAITING_USER_REVIEW || $ticket->reviews->isNotEmpty())
    <div class="p-4 sm:p-6">
        <div class="mx-auto max-w-2xl">
            <div class="flex flex-wrap items-start justify-between gap-2 border-b border-slate-100 pb-4 mb-5">
                <div>
                    <h2 class="text-base font-bold text-slate-900">{{ auth()->user()->can('reviewTicket', $ticket) ? 'Konfirmasi hasil pekerjaan' : 'Riwayat ulasan' }}</h2>
                    @can('reviewTicket', $ticket)
                        <p class="mt-1 text-sm text-slate-500">Periksa hasil kerja, lalu pilih terima atau minta revisi.</p>
                    @endcan
                </div>
                @if($ticket->reviews->whereNotNull('rating')->isNotEmpty())
                    <span class="text-xs font-medium text-slate-600">
                        Rating: <strong class="text-slate-900">{{ number_format($ticket->reviews->whereNotNull('rating')->avg('rating'), 1) }} / 5</strong>
                    </span>
                @endif
            </div>

            <!-- Review decision and form -->
            @can('reviewTicket', $ticket)
                <div x-data="{ mode: '{{ old('review_mode') === 'revise' ? 'revise' : 'accept' }}' }" class="{{ $ticket->reviews->isNotEmpty() ? 'mb-6 pb-6 border-b border-slate-100' : '' }}">
                    <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1" role="group" aria-label="Keputusan hasil pekerjaan">
                        <button
                            type="button"
                            @click="mode = 'accept'"
                            :aria-pressed="mode === 'accept'"
                            :class="mode === 'accept' ? 'bg-white text-[#1E40AF] shadow-xs font-semibold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="min-h-10 rounded-lg px-2 py-2 text-xs sm:text-sm transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#1E40AF]"
                        >
                            Terima hasil
                        </button>
                        <button
                            type="button"
                            @click="mode = 'revise'"
                            :aria-pressed="mode === 'revise'"
                            :class="mode === 'revise' ? 'bg-white text-[#1E40AF] shadow-xs font-semibold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="min-h-10 rounded-lg px-2 py-2 text-xs sm:text-sm transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#1E40AF]"
                        >
                            Minta revisi
                        </button>
                    </div>

                    <form x-show="mode === 'accept'" method="POST" action="{{ route('tickets.reviews.accept', $ticket) }}" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="review_mode" value="accept">
                        <div>
                            <label for="review-rating" class="mb-1.5 block text-sm font-medium text-slate-700">Penilaian hasil kerja <span class="text-rose-600">*</span></label>
                            <select id="review-rating" name="rating" required class="w-full sm:max-w-xs rounded-lg border-slate-300 bg-white text-sm focus:border-[#1E40AF] focus:ring-[#1E40AF]">
                            <option value="" disabled @selected(! old('rating'))>Pilih penilaian</option>
                                @foreach([5 => 'Sangat sesuai', 4 => 'Sesuai', 3 => 'Cukup', 2 => 'Kurang', 1 => 'Tidak sesuai'] as $score => $label)
                                <option value="{{ $score }}" @selected((int) old('rating') === $score)>{{ $score }} — {{ $label }}</option>
                                @endforeach
                            </select>
                            @error('rating') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="review-feedback" class="mb-1.5 block text-sm font-medium text-slate-700">Komentar <span class="font-normal text-slate-500">(opsional)</span></label>
                            <textarea id="review-feedback" name="feedback" rows="3" placeholder="Ceritakan apakah hasilnya sudah sesuai..." class="w-full rounded-lg border-slate-300 bg-white text-sm focus:border-[#1E40AF] focus:ring-[#1E40AF]">{{ old('review_mode') === 'accept' ? old('feedback') : '' }}</textarea>
                            @if(old('review_mode') !== 'revise') @error('feedback') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror @endif
                        </div>
                        <div class="flex justify-end pt-1">
                            <button type="submit" class="btn-primary w-full sm:w-auto px-4 py-2.5 text-sm font-semibold cursor-pointer">
                                Terima &amp; tutup tiket
                            </button>
                        </div>
                    </form>

                    <form x-show="mode === 'revise'" style="display: none;" method="POST" action="{{ route('tickets.reviews.revise', $ticket) }}" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="review_mode" value="revise">
                        <div>
                            <label for="revision-feedback" class="mb-1.5 block text-sm font-medium text-slate-700">Apa yang perlu diperbaiki? <span class="text-rose-600">*</span></label>
                            <textarea id="revision-feedback" name="feedback" rows="3" required placeholder="Jelaskan kendala yang masih terjadi agar teknisi dapat memperbaikinya..." class="w-full rounded-lg border-slate-300 bg-white text-sm focus:border-[#1E40AF] focus:ring-[#1E40AF]">{{ old('review_mode') === 'revise' ? old('feedback') : '' }}</textarea>
                            @if(old('review_mode') === 'revise') @error('feedback') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror @endif
                        </div>
                        <div class="flex justify-end pt-1">
                            <button type="submit" class="btn-secondary w-full sm:w-auto px-4 py-2.5 text-sm font-semibold cursor-pointer">
                                Kirim permintaan revisi
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
    </div>
@endif
