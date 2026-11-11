@if((auth()->user()->isItHead() || auth()->user()->isAdmin()) && $ticket->status === \App\Models\Ticket::STATUS_WAITING_MAJOR_MEETING)
<div x-data="{ open: false }" class="mt-4">
    <button @click="open = true" type="button" class="btn-primary text-xs py-2 px-3 inline-flex items-center gap-1.5 shadow-xs">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span>Setting &amp; Catat Pelaksanaan Rapat Koordinasi Major</span>
    </button>

    <div x-show="open" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" @click="open = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="open" x-transition class="relative inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                <form action="{{ route('tickets.schedule-meeting', $ticket) }}" method="POST" class="p-6">
                    @csrf
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900" id="modal-title">Tahap C.3: Pelaksanaan Rapat Koordinasi</h3>
                        <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-500">
                            <span class="sr-only">Tutup</span>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="mt-4 space-y-4 text-xs">
                        <div>
                            <label for="meeting_date" class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal & Waktu Rapat</label>
                            <input type="datetime-local" name="meeting_date" id="meeting_date" required value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="attendees_summary" class="block font-bold text-slate-700 uppercase tracking-wider mb-1">Daftar Peserta Rapat (Wajib Kabag IT, Pemohon, & Kabag Unit Pemohon)</label>
                            <textarea name="attendees_summary" id="attendees_summary" rows="3" required placeholder="Contoh: Kabag IT, Pemohon (Ahmad), Kabag Operasional (Bpk. Joko)" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <button type="button" @click="open = false" class="btn-secondary text-xs">Batal</button>
                        <button type="submit" class="btn-primary text-xs">Simpan & Teruskan ke Pemohon</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
