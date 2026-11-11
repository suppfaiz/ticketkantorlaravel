<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2 sm:gap-4">
            <div class="min-w-0">
                <h1 class="text-base sm:text-2xl font-bold text-slate-900 tracking-tight truncate">Input Notulensi Hasil Rapat Major</h1>
                <p class="hidden sm:block text-xs sm:text-sm text-slate-500 mt-1">
                    Tiket <span class="tabular-nums font-semibold text-slate-700">{{ $ticket->ticket_number }}</span> &bull; {{ $ticket->title }}
                </p>
            </div>
            <div class="shrink-0">
                <a href="{{ route('tickets.show', $ticket) }}" class="btn-secondary text-xs py-1.5 sm:py-2 px-3 sm:px-3.5 inline-flex items-center gap-1.5 shadow-xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span class="hidden sm:inline">Kembali ke Detail Tiket</span>
                    <span class="sm:hidden">Kembali</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Main Editor Area (8 Cols) -->
        <div class="lg:col-span-8 space-y-6">
            @if($ticket->status === \App\Models\Ticket::STATUS_REVISION_MINUTES && $ticket->meetingMinutes?->revision_comments)
                <div class="p-4 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 shadow-xs">
                    <div class="font-bold flex items-center gap-1.5 mb-1 text-slate-900">
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Catatan Revisi dari Kepala Bagian IT:</span>
                    </div>
                    <p class="leading-relaxed text-slate-700">{{ $ticket->meetingMinutes->revision_comments }}</p>
                </div>
            @endif

            <div class="bg-white rounded-xl border border-slate-200 p-6 sm:p-7 shadow-xs"
                 x-data="{
                     notesError: false,
                     fileName: '',
                     init() {
                         this.$nextTick(() => {
                             if (window.initTrumbowyg) {
                                 window.initTrumbowyg('#notes-trumbowyg');
                             }
                         });
                     },
                     syncAndValidate(e) {
                         if (window.$ && $('#notes-trumbowyg').data('trumbowyg')) {
                             const html = $('#notes-trumbowyg').trumbowyg('html');
                             $('#notes-trumbowyg').val(html);
                         }
                         const rawVal = document.getElementById('notes-trumbowyg').value || '';
                         const textOnly = rawVal.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
                         if (textOnly.length < 2) {
                             e.preventDefault();
                             this.notesError = true;
                             if (window.$ && $('#notes-trumbowyg').data('trumbowyg')) {
                                 $('#notes-trumbowyg').data('trumbowyg').$ed.trigger('focus');
                             }
                             return false;
                         }
                         this.notesError = false;
                         return true;
                     }
                 }">
                <form action="{{ route('tickets.submit-minutes', $ticket) }}" method="POST" enctype="multipart/form-data"
                      x-on:submit="syncAndValidate($event)">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <label for="notes-trumbowyg" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Poin Kesepakatan &amp; Notulensi Rapat <span class="text-rose-500">*</span>
                            </label>
                            <textarea name="notes" id="notes-trumbowyg" rows="16"
                                placeholder="Tuliskan poin pembahasan rapat, kesepakatan ruang lingkup, serta luaran pekerjaan..."
                                class="w-full text-sm rounded-xl border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] min-h-[340px]">{{ old('notes', $ticket->meetingMinutes?->notes) }}</textarea>
                            <p x-show="notesError" x-cloak class="text-xs font-medium text-rose-600 mt-1.5">
                                Poin kesepakatan dan notulensi rapat wajib diisi (minimal 2 karakter).
                            </p>
                            @error('notes')
                                <p class="text-xs font-medium text-rose-600 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="pt-2 border-t border-slate-100">
                            <label for="attachment" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Lampiran Dokumen Notulensi (Opsional)
                            </label>
                            @if($ticket->meetingMinutes?->attachment_path)
                                <div class="mb-2.5 p-2.5 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between text-xs">
                                    <span class="text-slate-700 font-medium truncate">Berkas notulensi sebelumnya sudah tersimpan</span>
                                    <a href="{{ route('tickets.meeting-minutes.download', $ticket) }}" class="text-[#1E40AF] hover:underline font-semibold shrink-0 ml-3">
                                        Unduh Berkas Saat Ini
                                    </a>
                                </div>
                            @endif
                            <input type="file" name="attachment" id="attachment"
                                accept=".pdf,.docx,.xlsx,.png,.jpg,.jpeg"
                                x-on:change="fileName = $event.target.files[0]?.name || ''"
                                class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border file:border-slate-200 file:text-xs file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100 cursor-pointer">
                            <p class="text-xs text-slate-400 mt-1">PDF, DOCX, XLSX, PNG, JPG (Maks. 10MB)</p>
                            @error('attachment')
                                <p class="text-xs font-medium text-rose-600 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-8 pt-5 border-t border-slate-100 flex items-center justify-end gap-3">
                        <a href="{{ route('tickets.show', $ticket) }}" class="btn-secondary text-xs py-2.5 px-4">
                            Batal
                        </a>
                        <button type="submit" class="w-full sm:w-auto btn-primary text-xs py-2.5 px-5 font-semibold cursor-pointer">
                            Simpan &amp; Kirim Notulensi ke Kabag IT
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar Context (4 Cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Card: Detail Pelaksanaan Rapat -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">Informasi Rapat (Tahap C.3)</h3>
                <div class="space-y-3.5 text-xs">
                    <div>
                        <div class="text-xs text-slate-400 font-medium">Tanggal &amp; Waktu Rapat</div>
                        <div class="font-bold text-slate-900 tabular-nums mt-0.5">
                            {{ $ticket->meetingMinutes?->meeting_date ? $ticket->meetingMinutes->meeting_date->format('d M Y, H:i') . ' WIB' : ($ticket->meeting_scheduled_at ? $ticket->meeting_scheduled_at->format('d M Y, H:i') . ' WIB' : '-') }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 font-medium">Daftar Peserta Rapat</div>
                        <div class="font-medium text-slate-800 mt-0.5 leading-relaxed">
                            {{ trim($ticket->meetingMinutes?->attendees ?? $ticket->meetingMinutes?->attendees_summary ?? '-') }}
                        </div>
                    </div>
                    @if($ticket->meetingMinutes?->scope_summary)
                        <div>
                            <div class="text-xs text-slate-400 font-medium">Agenda / Pengantar Rapat</div>
                            <div class="font-medium text-slate-800 mt-0.5 leading-relaxed">
                                {{ trim($ticket->meetingMinutes->scope_summary) }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Card: Ringkasan Permohonan -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">Ringkasan Tiket</h3>
                <div class="space-y-3 text-xs">
                    <div>
                        <div class="text-xs text-slate-400 font-medium">Pemohon</div>
                        <div class="font-semibold text-slate-900 mt-0.5">{{ $ticket->requester->name }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 font-medium">Kategori</div>
                        <div class="font-medium text-slate-800 mt-0.5">{{ $ticket->category }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 font-medium">Deskripsi Awal</div>
                        <div class="text-slate-700 mt-1 leading-relaxed line-clamp-5 whitespace-pre-wrap">{{ trim($ticket->description) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
