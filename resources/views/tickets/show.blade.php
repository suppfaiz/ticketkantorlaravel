<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2 sm:gap-4">
            <div class="min-w-0 flex-1">
                <h1 class="text-base sm:text-xl font-bold text-slate-900 tracking-tight truncate">{{ $ticket->title }}</h1>
            </div>
            <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                <a
                    href="{{ route('tickets.print', $ticket) }}"
                    target="_blank"
                    class="btn-secondary text-xs py-1.5 px-2.5 sm:px-3 inline-flex items-center gap-1.5 shadow-xs"
                    title="Cetak Berita Acara / Lembar Pengesahan Tiket"
                >
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span class="hidden sm:inline">Cetak / PDF</span>
                </a>
                @can('update', $ticket)
                    <a href="{{ route('tickets.edit', $ticket) }}" class="btn-primary text-xs py-1.5 px-2.5 sm:px-3 inline-flex items-center gap-1.5 shadow-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span class="hidden sm:inline">Edit / Revisi Tiket</span>
                        <span class="sm:hidden">Edit</span>
                    </a>
                @endcan
                @can('cancel', $ticket)
                    <button
                        type="button"
                        x-data=""
                        @click="$dispatch('open-modal', 'modal-cancel-ticket')"
                        class="px-2.5 sm:px-3 py-1.5 text-xs font-semibold rounded-lg border border-rose-200 bg-rose-50 max-sm:bg-white/15 max-sm:border-white/30 max-sm:text-white text-rose-700 hover:bg-rose-100 inline-flex items-center gap-1.5 transition-colors cursor-pointer"
                    >
                        <span>Batalkan</span>
                    </button>
                @endcan
                <a href="{{ route('tickets.index') }}" class="btn-secondary text-xs py-1.5 px-2.5 sm:px-3 inline-flex items-center gap-1.5 shadow-xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span class="hidden sm:inline">Kembali</span>
                </a>
            </div>
        </div>
    </x-slot>

    <!-- Visual Dynamic Stepper (Minor & Major) -->
    @include('tickets.partials.visual_stepper')

    @php
        $defaultTab = 'detail';
        if (session('active_tab') === 'discussion') {
            $defaultTab = 'discussion';
        } elseif ($ticket->status === \App\Models\Ticket::STATUS_WAITING_USER_REVIEW && (auth()->id() === $ticket->requester_id || auth()->user()->isAdmin())) {
            $defaultTab = 'review';
        } elseif (in_array($ticket->status, [
            \App\Models\Ticket::STATUS_WAITING_MAJOR_MEETING,
            \App\Models\Ticket::STATUS_WAITING_USER_MINUTES,
            \App\Models\Ticket::STATUS_REVISION_MINUTES,
            \App\Models\Ticket::STATUS_WAITING_MINUTES_APPROVAL
        ])) {
            $defaultTab = 'minutes';
        } elseif (in_array($ticket->status, [
            \App\Models\Ticket::STATUS_READY_FOR_WORK,
            \App\Models\Ticket::STATUS_IN_PROGRESS,
            \App\Models\Ticket::STATUS_REVISION_WORK
        ]) && auth()->user()->isItDeveloper()) {
            $defaultTab = 'activities';
        }
    @endphp

    <div 
        x-data="{ 
            activeTab: (['detail', 'discussion', 'activities', 'minutes', 'review', 'audit'].includes(window.location.hash.replace('#', '')) ? window.location.hash.replace('#', '') : '{{ $defaultTab }}'),
            discussionCount: {{ $ticket->discussions->count() }},
            setTab(tab) {
                this.activeTab = tab;
                if (history.replaceState) {
                    history.replaceState(null, null, '#' + tab);
                } else {
                    window.location.hash = tab;
                }
                window.dispatchEvent(new CustomEvent('ticket-tab-changed', { detail: { tab } }));
            }
        }"
        @discussion-count-updated.window="discussionCount = $event.detail.count"
        class="grid grid-cols-1 lg:grid-cols-12 gap-6"
    >
        <!-- Main Content Area (8 Cols) -->
        <div class="lg:col-span-8 space-y-6">

            <!-- ACTION PANEL: Tahap A (Draft Ticket Direct Submission) -->
            @if($ticket->status === \App\Models\Ticket::STATUS_DRAFT && auth()->user()->can('update', $ticket))
                <div class="bg-blue-50/80 rounded-xl border border-blue-200 p-5 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <div class="inline-flex items-center gap-1.5 text-xs font-bold text-[#1E40AF] uppercase tracking-wider mb-1">
                                <span class="w-2 h-2 rounded-full bg-[#1E40AF]"></span>
                                Status Draf Belum Diajukan
                            </div>
                            <h2 class="text-sm font-bold text-slate-900">Tiket Masih Tersimpan Sebagai Draf</h2>
                            <p class="text-xs text-slate-600 mt-0.5">Periksa kembali rincian permohonan Anda, lalu klik <strong>Ajukan Tiket Sekarang</strong> untuk meneruskan ke alur persetujuan.</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <form method="POST" action="{{ route('tickets.submit-draft', $ticket) }}">
                                @csrf
                                <button type="submit" class="btn-primary text-xs py-2 px-4 font-semibold cursor-pointer">
                                    Ajukan Tiket Sekarang &rarr;
                                </button>
                            </form>
                            <a href="{{ route('tickets.edit', $ticket) }}" class="btn-secondary text-xs py-2 px-3.5 font-medium">
                                Edit Draf
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <!-- ACTION PANEL: Tahap E (Waiting User Review / Verification Banner) -->
            @can('reviewTicket', $ticket)
                <div class="bg-emerald-50/80 rounded-xl border border-emerald-200 p-5 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <div class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 uppercase tracking-wider mb-1">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                Pekerjaan Teknis Selesai — Menunggu Konfirmasi Akhir
                            </div>
                            <h2 class="text-sm font-bold text-slate-900">
                                {{ auth()->id() === $ticket->requester_id ? 'Silakan Periksa Hasil Pengerjaan & Berikan Ulasan' : 'Verifikasi Penyelesaian Tiket (Otoritas Kabag IT / Admin)' }}
                            </h2>
                            <p class="text-xs text-slate-600 mt-0.5">
                                Teknisi IT (<strong class="text-slate-800">{{ $ticket->assignedTo?->name ?? 'Tim IT' }}</strong>) telah menyelesaikan tiket ini. Konfirmasi terima pekerjaan untuk menutup tiket resmi atau minta revisi jika masih ada kendala.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="setTab('review')"
                            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs shrink-0 self-start sm:self-center cursor-pointer transition-colors"
                        >
                            Konfirmasi &amp; Beri Rating &rarr;
                        </button>
                    </div>
                </div>
            @endcan

            <!-- ACTION PANEL: Tahap B (Supervisor Approval) -->
            @can('supervisorApprove', $ticket)
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Persetujuan Atasan Langsung</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Tinjau permohonan dari <strong class="text-slate-800">{{ $ticket->requester->name }}</strong> sebelum diteruskan ke Kepala Bagian IT.</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'modal-supervisor-approve')"
                                class="btn-primary text-xs py-2 px-3.5 font-semibold cursor-pointer"
                            >
                                Setujui
                            </button>

                            <button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'modal-supervisor-revise')"
                                class="btn-secondary text-xs py-2 px-3.5 font-medium cursor-pointer"
                            >
                                Minta Revisi
                            </button>

                            <button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'modal-supervisor-reject')"
                                class="btn-secondary text-xs py-2 px-3.5 font-medium cursor-pointer"
                            >
                                Tolak
                            </button>
                        </div>
                    </div>

                    <!-- MODAL 1: Supervisor Approve -->
                    <x-modal name="modal-supervisor-approve" focusable>
                        <form method="POST" action="{{ route('tickets.supervisor-approve', $ticket) }}" class="p-6">
                            @csrf
                            <h3 class="text-base font-bold text-slate-900 mb-1">Setujui Permohonan Tiket</h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Tiket akan disetujui dan diteruskan ke Kepala Bagian IT.
                            </p>
                            <div class="mb-5">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Persetujuan (Opsional)</label>
                                <input type="text" name="notes" placeholder="Contoh: Disetujui, sesuai kebutuhan operasional tim."
                                    class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">
                            </div>
                            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">
                                    Batal
                                </button>
                                <button type="submit" class="btn-primary text-xs">
                                    Setujui &amp; Teruskan
                                </button>
                            </div>
                        </form>
                    </x-modal>

                    <!-- MODAL 2: Supervisor Revise -->
                    <x-modal name="modal-supervisor-revise" focusable>
                        <form method="POST" action="{{ route('tickets.supervisor-revise', $ticket) }}" class="p-6">
                            @csrf
                            <h3 class="text-base font-bold text-slate-900 mb-1">Minta Revisi ke Pemohon</h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Tiket akan dikembalikan ke pemohon untuk melengkapi informasi.
                            </p>
                            <div class="mb-5">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Instruksi Revisi <span class="text-red-500">*</span></label>
                                <textarea name="revision_notes" rows="3" required placeholder="Jelaskan informasi yang perlu diperbaiki oleh pemohon..."
                                    class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">
                                    Batal
                                </button>
                                <button type="submit" class="btn-primary text-xs">
                                    Kembalikan untuk Revisi
                                </button>
                            </div>
                        </form>
                    </x-modal>

                    <!-- MODAL 3: Supervisor Reject -->
                    <x-modal name="modal-supervisor-reject" focusable>
                        <form method="POST" action="{{ route('tickets.supervisor-reject', $ticket) }}" class="p-6">
                            @csrf
                            <h3 class="text-base font-bold text-slate-900 mb-1">Tolak Permohonan Tiket</h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Tiket yang ditolak akan dihentikan secara permanen.
                            </p>
                            <div class="mb-5">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
                                <textarea name="reason" rows="3" required placeholder="Tuliskan alasan penolakan..."
                                    class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">
                                    Batal
                                </button>
                                <button type="submit" class="btn-primary text-xs">
                                    Tolak Permohonan
                                </button>
                            </div>
                        </form>
                    </x-modal>
                </div>
            @endcan

            <!-- ACTION PANEL: Tahap C (IT Head Approval & Classification) -->
            @can('itHeadDecide', $ticket)
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Keputusan &amp; Klasifikasi Kepala Bagian IT</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Tetapkan klasifikasi Minor (langsung antrean teknisi) atau Major (LOA eksekutif &amp; rapat).</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'modal-ithead-classify')"
                                class="btn-primary text-xs py-2 px-3.5 font-semibold cursor-pointer"
                            >
                                Klasifikasi &amp; Setujui
                            </button>

                            <button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'modal-ithead-revise')"
                                class="btn-secondary text-xs py-2 px-3.5 font-medium cursor-pointer"
                            >
                                Minta Revisi
                            </button>

                            <button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'modal-ithead-reject')"
                                class="btn-secondary text-xs py-2 px-3.5 font-medium cursor-pointer"
                            >
                                Tolak
                            </button>
                        </div>
                    </div>

                    <!-- MODAL 1: IT Head Classify & Approve -->
                    <x-modal name="modal-ithead-classify" focusable>
                        <form method="POST" action="{{ route('tickets.it-head-classify-approve', $ticket) }}" class="p-6">
                            @csrf
                            <h3 class="text-base font-bold text-slate-900 mb-1">Klasifikasi &amp; Persetujuan Kepala Bagian IT</h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Tiket Minor langsung masuk ke antrean developer, sedangkan tiket Major memerlukan otorisasi eksekutif &amp; rapat koordinasi.
                            </p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Klasifikasi Pekerjaan <span class="text-red-500">*</span></label>
                                    <select name="classification" required class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">
                                        <option value="">-- Pilih Klasifikasi --</option>
                                        <option value="Minor" {{ old('classification', $ticket->classification) === 'Minor' ? 'selected' : '' }}>Minor (Langsung Antrean)</option>
                                        <option value="Major" {{ old('classification', $ticket->classification) === 'Major' ? 'selected' : '' }}>Major (Eksekutif &amp; Rapat)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tingkat Prioritas</label>
                                    <select name="priority" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">
                                        @foreach(['Low', 'Medium', 'High', 'Urgent'] as $p)
                                             <option value="{{ $p }}" {{ old('priority', $ticket->priority) === $p ? 'selected' : '' }}>{{ $p }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Timeline Selesai (Kabag IT)</label>
                                    <input
                                        type="date"
                                        name="target_completion_at"
                                        value="{{ old('target_completion_at', $ticket->target_completion_at?->format('Y-m-d')) }}"
                                        class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"
                                    >
                                    <span class="block text-[11px] text-slate-400 mt-1">Batas waktu pengerjaan yang ditetapkan Kabag IT.</span>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tugaskan Teknisi Langsung (Opsional)</label>
                                    <select name="assigned_to" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">
                                        <option value="">-- Masuk Antrean Pool Teknisi --</option>
                                        @foreach(($itDevelopers ?? []) as $dev)
                                            <option value="{{ $dev->id }}" {{ (int) old('assigned_to', $ticket->assigned_to) === (int) $dev->id ? 'selected' : '' }}>
                                                {{ $dev->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="block text-[11px] text-slate-400 mt-1">Untuk Minor, jika dipilih maka langsung ditugaskan ke teknisi.</span>
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Pengarahan (Opsional)</label>
                                <input type="text" name="notes" placeholder="Catatan pengarahan teknis atau ruang lingkup..."
                                    class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">
                            </div>

                            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">
                                    Batal
                                </button>
                                <button type="submit" class="btn-primary text-xs">
                                    Simpan &amp; Setujui
                                </button>
                            </div>
                        </form>
                    </x-modal>

                    <!-- MODAL 2: IT Head Revise -->
                    <x-modal name="modal-ithead-revise" focusable>
                        <form method="POST" action="{{ route('tickets.it-head-revise', $ticket) }}" class="p-6">
                            @csrf
                            <h3 class="text-base font-bold text-slate-900 mb-1">Minta Revisi Detail ke Pemohon</h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Tiket akan dikembalikan ke pemohon untuk dilengkapi.
                            </p>
                            <div class="mb-5">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Revisi <span class="text-red-500">*</span></label>
                                <textarea name="revision_notes" rows="3" required placeholder="Jelaskan kebutuhan teknis yang perlu dilengkapi..."
                                    class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">
                                    Batal
                                </button>
                                <button type="submit" class="btn-primary text-xs">
                                    Kembalikan untuk Revisi
                                </button>
                            </div>
                        </form>
                    </x-modal>

                    <!-- MODAL 3: IT Head Reject -->
                    <x-modal name="modal-ithead-reject" focusable>
                        <form method="POST" action="{{ route('tickets.it-head-reject', $ticket) }}" class="p-6">
                            @csrf
                            <h3 class="text-base font-bold text-slate-900 mb-1">Tolak Permohonan Tiket</h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Tiket akan dihentikan secara permanen.
                            </p>
                            <div class="mb-5">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
                                <textarea name="reason" rows="3" required placeholder="Alasan penolakan..."
                                    class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">
                                    Batal
                                </button>
                                <button type="submit" class="btn-primary text-xs">
                                    Tolak Permohonan
                                </button>
                            </div>
                        </form>
                    </x-modal>
                </div>
            @endcan

            <!-- ACTION PANEL: Tahap C.1 (Kadiv IT & Ops LOA Approval) -->
            @can('kadivApprove', $ticket)
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Persetujuan LOA Kadiv IT &amp; Operasional</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Tinjau kelayakan proyek Major sebelum diteruskan ke Dewan Direksi.</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'modal-kadiv-approve')"
                                class="btn-primary text-xs py-2 px-3.5 font-semibold cursor-pointer"
                            >
                                Setujui LOA
                            </button>

                            <button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'modal-kadiv-reject')"
                                class="btn-secondary text-xs py-2 px-3.5 font-medium cursor-pointer"
                            >
                                Tolak
                            </button>
                        </div>
                    </div>

                    <!-- MODAL: Kadiv Approve -->
                    <x-modal name="modal-kadiv-approve" focusable>
                        <form method="POST" action="{{ route('tickets.kadiv-approve', $ticket) }}" class="p-6">
                            @csrf
                            <h3 class="text-base font-bold text-slate-900 mb-1">Setujui LOA Kadiv IT &amp; Operasional</h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Pengajuan LOA akan disetujui dan diteruskan ke Dewan Direksi.
                            </p>
                            <div class="mb-5">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Persetujuan (Opsional)</label>
                                <input type="text" name="notes" placeholder="Contoh: Disetujui sesuai prioritas divisi."
                                    class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">
                            </div>
                            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">
                                    Batal
                                </button>
                                <button type="submit" class="btn-primary text-xs">
                                    Setujui &amp; Teruskan
                                </button>
                            </div>
                        </form>
                    </x-modal>

                    <!-- MODAL: Kadiv Reject -->
                    <x-modal name="modal-kadiv-reject" focusable>
                        <form method="POST" action="{{ route('tickets.kadiv-reject', $ticket) }}" class="p-6">
                            @csrf
                            <h3 class="text-base font-bold text-slate-900 mb-1">Tolak Permohonan Tiket Major</h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Tiket yang ditolak akan dihentikan secara permanen.
                            </p>
                            <div class="mb-5">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
                                <textarea name="reason" rows="3" required placeholder="Alasan penolakan..."
                                    class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">
                                    Batal
                                </button>
                                <button type="submit" class="btn-primary text-xs">
                                    Tolak Permohonan
                                </button>
                            </div>
                        </form>
                    </x-modal>
                </div>
            @endcan

            <!-- ACTION PANEL: Tahap C.2 (Direksi LOA Approval) -->
            @can('direksiApprove', $ticket)
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Persetujuan LOA Dewan Direksi</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Tinjau otorisasi proyek Major sebelum memasuki tahap rapat koordinasi teknis.</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'modal-direksi-approve')"
                                class="btn-primary text-xs py-2 px-3.5 font-semibold cursor-pointer"
                            >
                                Setujui LOA Direksi
                            </button>

                            <button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'modal-direksi-reject')"
                                class="btn-secondary text-xs py-2 px-3.5 font-medium cursor-pointer"
                            >
                                Tolak
                            </button>
                        </div>
                    </div>

                    <!-- MODAL: Direksi Approve -->
                    <x-modal name="modal-direksi-approve" focusable>
                        <form method="POST" action="{{ route('tickets.direksi-approve', $ticket) }}" class="p-6">
                            @csrf
                            <h3 class="text-base font-bold text-slate-900 mb-1">Setujui LOA Dewan Direksi</h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Tiket akan dilanjutkan ke tahap penjadwalan Rapat Koordinasi Major.
                            </p>
                            <div class="mb-5">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Keputusan (Opsional)</label>
                                <input type="text" name="notes" placeholder="Contoh: Disetujui oleh Direksi."
                                    class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">
                            </div>
                            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">
                                    Batal
                                </button>
                                <button type="submit" class="btn-primary text-xs">
                                    Setujui LOA Direksi
                                </button>
                            </div>
                        </form>
                    </x-modal>

                    <!-- MODAL: Direksi Reject -->
                    <x-modal name="modal-direksi-reject" focusable>
                        <form method="POST" action="{{ route('tickets.direksi-reject', $ticket) }}" class="p-6">
                            @csrf
                            <h3 class="text-base font-bold text-slate-900 mb-1">Tolak Permohonan Tiket Major</h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Tiket yang ditolak akan dihentikan secara permanen.
                            </p>
                            <div class="mb-5">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
                                <textarea name="reason" rows="3" required placeholder="Alasan penolakan..."
                                    class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">
                                    Batal
                                </button>
                                <button type="submit" class="btn-primary text-xs">
                                    Tolak Permohonan
                                </button>
                            </div>
                        </form>
                    </x-modal>
                </div>
            @endcan

            <!-- ACTION PANEL: Tahap C.3 (Setting & Pelaksanaan Rapat Koordinasi Major oleh Kabag IT) -->
            @if((auth()->user()->isItHead() || auth()->user()->isAdmin()) && $ticket->status === \App\Models\Ticket::STATUS_WAITING_MAJOR_MEETING)
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Penjadwalan Rapat Koordinasi Major</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Tentukan jadwal dan peserta rapat bersama pemohon (<strong class="text-slate-800">{{ $ticket->requester->name }}</strong>).</p>
                        </div>

                        <button
                            type="button"
                            x-data=""
                            x-on:click.prevent="$dispatch('open-modal', 'modal-schedule-meeting-action')"
                            class="btn-primary text-xs py-2 px-3.5 font-semibold cursor-pointer shrink-0 self-start sm:self-center"
                        >
                            Jadwalkan Rapat
                        </button>
                    </div>

                    <!-- MODAL: Schedule Meeting -->
                    <x-modal name="modal-schedule-meeting-action" focusable>
                        <form action="{{ route('tickets.schedule-meeting', $ticket) }}" method="POST" class="p-6" onsubmit="if (history.replaceState) { history.replaceState(null, null, '#minutes'); }">
                            @csrf
                            <h3 class="text-base font-bold text-slate-900 mb-1">Jadwal Rapat Koordinasi Major</h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Catat waktu pelaksanaan rapat dan daftar peserta yang hadir.
                            </p>

                            <div class="space-y-4 mb-5 text-xs">
                                <div>
                                    <label for="meeting_date_action" class="block font-semibold text-slate-700 mb-1">Tanggal &amp; Waktu Rapat <span class="text-red-500">*</span></label>
                                    <input type="datetime-local" name="meeting_date" id="meeting_date_action" required value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">
                                </div>

                                <div>
                                    <label for="attendees_summary_action" class="block font-semibold text-slate-700 mb-1">Daftar Peserta Rapat <span class="text-red-500">*</span></label>
                                    <textarea name="attendees_summary" id="attendees_summary_action" rows="3" required placeholder="Contoh: Kabag IT, Pemohon ({{ $ticket->requester->name }}), Kepala Unit..." class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"></textarea>
                                </div>

                                <div>
                                    <label for="notes_action" class="block font-semibold text-slate-700 mb-1">Agenda Rapat (Opsional)</label>
                                    <input type="text" name="notes" id="notes_action" placeholder="Contoh: Pembahasan ruang lingkup dan timeline." class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">
                                </div>
                            </div>

                            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">
                                    Batal
                                </button>
                                <button type="submit" class="btn-primary text-xs">
                                    Simpan Jadwal Rapat
                                </button>
                            </div>
                        </form>
                    </x-modal>
                </div>
            @endif

            <!-- ACTION PANEL: Tahap C.4 (Input / Revisi Notulensi Rapat Koordinasi Major) -->
            @if(($ticket->requester_id === auth()->id() || auth()->user()->isItHead() || auth()->user()->isAdmin()) && in_array($ticket->status, [\App\Models\Ticket::STATUS_WAITING_USER_MINUTES, \App\Models\Ticket::STATUS_REVISION_MINUTES], true))
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">
                                {{ $ticket->status === \App\Models\Ticket::STATUS_REVISION_MINUTES ? 'Perbaikan Notulensi Rapat Koordinasi Major' : 'Input Notulensi Hasil Rapat Koordinasi Major' }}
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ $ticket->status === \App\Models\Ticket::STATUS_REVISION_MINUTES ? 'Silakan perbaiki poin notulensi sesuai catatan dari Kepala Bagian IT.' : 'Tuliskan poin kesepakatan rapat koordinasi untuk diajukan ke Kepala Bagian IT.' }}
                            </p>
                        </div>

                        <a href="{{ route('tickets.meeting-minutes.create', $ticket) }}" class="btn-primary text-xs py-2 px-3.5 font-semibold shrink-0 self-start sm:self-center inline-flex items-center gap-1.5">
                            <span>{{ $ticket->status === \App\Models\Ticket::STATUS_REVISION_MINUTES ? 'Perbaiki Notulensi' : 'Input Notulensi Rapat' }}</span>
                            <span>&rarr;</span>
                        </a>
                    </div>

                    @if($ticket->status === \App\Models\Ticket::STATUS_REVISION_MINUTES && $ticket->meetingMinutes?->revision_comments)
                        <div class="mt-3.5 pt-3 border-t border-slate-100 text-xs text-slate-700">
                            <span class="font-semibold text-slate-900">Catatan Revisi Kabag IT:</span> {{ $ticket->meetingMinutes->revision_comments }}
                        </div>
                    @endif
                </div>
            @endif

            <!-- ACTION PANEL: Tahap C.5 (Persetujuan Notulensi Major oleh Kabag IT) -->
            @if((auth()->user()->isItHead() || auth()->user()->isAdmin()) && $ticket->status === \App\Models\Ticket::STATUS_WAITING_MINUTES_APPROVAL)
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Persetujuan Notulensi Rapat</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Periksa notulensi di tab <strong class="text-slate-800">Notulensi Rapat</strong>, lalu setujui agar tiket masuk ke antrean teknisi IT.</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'modal-approve-minutes-action')"
                                class="btn-primary text-xs py-2 px-3.5 font-semibold cursor-pointer"
                            >
                                Setujui Notulensi &amp; Atur Timeline
                            </button>

                            <button
                                type="button"
                                x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'modal-revise-minutes-action')"
                                class="btn-secondary text-xs py-2 px-3.5 font-medium cursor-pointer"
                            >
                                Minta Revisi
                            </button>
                        </div>
                    </div>

                    <!-- MODAL: Approve Minutes + Set Timeline & Developer -->
                    <x-modal name="modal-approve-minutes-action" focusable>
                        <form action="{{ route('tickets.approve-minutes', $ticket) }}" method="POST" class="p-6">
                            @csrf
                            <h3 class="text-base font-bold text-slate-900 mb-1">Setujui Notulensi Rapat &amp; Tetapkan Timeline</h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Setujui hasil kesepakatan rapat koordinasi Major, tetapkan target penyelesaian, dan pilih teknisi pelaksana (opsional).
                            </p>

                            @php
                                $defaultMajorTarget = $ticket->target_completion_at?->format('Y-m-d')
                                    ?? ($ticket->meetingMinutes?->target_completion_date ? \Carbon\Carbon::parse($ticket->meetingMinutes->target_completion_date)->format('Y-m-d') : '');
                            @endphp

                            <div class="space-y-4 mb-5 text-xs">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Target Timeline Penyelesaian (Kabag IT)</label>
                                    <input
                                        type="date"
                                        name="target_completion_at"
                                        value="{{ $defaultMajorTarget }}"
                                        class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"
                                    >
                                    <span class="block text-[11px] text-slate-400 mt-1">Otomatis terisi dari usulan tanggal di notulensi rapat bila tersedia.</span>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Tugaskan Teknisi IT Langsung (Opsional)</label>
                                    <select name="assigned_to" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">
                                        <option value="">-- Masuk ke Pool Antrean Siap Dikerjakan --</option>
                                        @foreach(($itDevelopers ?? []) as $dev)
                                            <option value="{{ $dev->id }}" {{ (int) $ticket->assigned_to === (int) $dev->id ? 'selected' : '' }}>
                                                {{ $dev->name }} ({{ $dev->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Catatan Persetujuan Notulensi (Opsional)</label>
                                    <input
                                        type="text"
                                        name="notes"
                                        placeholder="Contoh: Notulensi disetujui, silakan eksekusi sesuai ruang lingkup rapat."
                                        class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"
                                    >
                                </div>
                            </div>

                            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">
                                    Batal
                                </button>
                                <button type="submit" class="btn-primary text-xs cursor-pointer">
                                    Setujui Notulensi &amp; Lanjutkan
                                </button>
                            </div>
                        </form>
                    </x-modal>

                    <!-- MODAL: Revise Minutes -->
                    <x-modal name="modal-revise-minutes-action" focusable>
                        <form action="{{ route('tickets.revise-minutes', $ticket) }}" method="POST" class="p-6">
                            @csrf
                            <h3 class="text-base font-bold text-slate-900 mb-1">Minta Revisi Notulensi Rapat</h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Tuliskan poin notulensi yang perlu diperbaiki oleh pemohon.
                            </p>

                            <div class="mb-5">
                                <label for="revision_comments_action" class="block text-xs font-semibold text-slate-700 mb-1">Catatan Perbaikan <span class="text-red-500">*</span></label>
                                <textarea name="revision_comments" id="revision_comments_action" rows="3" required placeholder="Jelaskan poin notulensi yang perlu diperbaiki..." class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"></textarea>
                            </div>

                            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">
                                    Batal
                                </button>
                                <button type="submit" class="btn-primary text-xs">
                                    Kirim Instruksi Revisi
                                </button>
                            </div>
                        </form>
                    </x-modal>
                </div>
            @endif

            <!-- Status Alerts if Rejected / Needs Revision by IT Head -->
            @can('manageAssignmentAndTimeline', $ticket)
                @if($ticket->status !== \App\Models\Ticket::STATUS_WAITING_IT_HEAD)
                    <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="text-xs">
                            <div class="font-bold text-slate-900 text-sm">Pengaturan Timeline &amp; Penugasan Teknisi (Kabag IT)</div>
                            <div class="text-slate-500 mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span>
                                    Target Timeline:
                                    <strong class="text-slate-800">{{ $ticket->target_completion_at ? $ticket->target_completion_at->format('d M Y') : 'Belum ditetapkan' }}</strong>
                                </span>
                                <span>&bull;</span>
                                <span>
                                    Teknisi:
                                    <strong class="text-slate-800">{{ $ticket->assignedTo?->name ?? 'Belum ditugaskan (Pool)' }}</strong>
                                </span>
                            </div>
                        </div>
                        <button
                            type="button"
                            x-data=""
                            @click="$dispatch('open-modal', 'modal-ithead-timeline-assign')"
                            class="btn-secondary text-xs py-2 px-3.5 font-semibold shrink-0 self-start sm:self-center cursor-pointer"
                        >
                            Atur Timeline &amp; Teknisi
                        </button>
                    </div>
                @endif

                <x-modal name="modal-ithead-timeline-assign" focusable>
                    <form method="POST" action="{{ route('tickets.assign-and-timeline', $ticket) }}" class="p-6">
                        @csrf
                        <h3 class="text-base font-bold text-slate-900 mb-1">Atur Target Timeline &amp; Teknisi IT</h3>
                        <p class="text-xs text-slate-500 mb-4">
                            Tetapkan batas waktu penyelesaian tiket atau tunjuk/pindah-tangankan (*re-assign*) teknisi pelaksana.
                        </p>

                        <div class="space-y-4 mb-5 text-xs">
                            @if($ticket->status === \App\Models\Ticket::STATUS_WAITING_SUPERVISOR && !empty($supervisors))
                                <div class="p-3 rounded-xl bg-amber-50/70 border border-amber-200/80">
                                    <label class="block font-semibold text-amber-900 mb-1">Delegasi / Alihkan Atasan Peninjau (Jika Atasan Cuti/Berhalangan)</label>
                                    <select name="supervisor_id" class="w-full text-xs rounded-lg border-amber-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">
                                        @foreach($supervisors as $sup)
                                            <option value="{{ $sup->id }}" {{ (int) $ticket->supervisor_id === (int) $sup->id ? 'selected' : '' }}>
                                                {{ $sup->name }} ({{ strtoupper($sup->role) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="block text-[11px] text-amber-700 mt-1">Gunakan opsi ini bila atasan langsung pemohon sedang cuti agar tiket tidak menggantung.</span>
                                </div>
                            @endif

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Target Timeline Penyelesaian (Kabag IT)</label>
                                <input
                                    type="date"
                                    name="target_completion_at"
                                    value="{{ $ticket->target_completion_at?->format('Y-m-d') }}"
                                    class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"
                                >
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Teknisi IT Pelaksana (Assign / Re-assign)</label>
                                <select name="assigned_to" class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">
                                    <option value="">-- Belum Ditugaskan (Antrean Pool) --</option>
                                    @foreach(($itDevelopers ?? []) as $dev)
                                        <option value="{{ $dev->id }}" {{ (int) $ticket->assigned_to === (int) $dev->id ? 'selected' : '' }}>
                                            {{ $dev->name }} ({{ $dev->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Catatan Perubahan / Instruksi (Opsional)</label>
                                <input
                                    type="text"
                                    name="notes"
                                    placeholder="Contoh: Penyesuaian timeline atau pengalihan tugas teknisi..."
                                    class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"
                                >
                            </div>
                        </div>

                        <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                            <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">Batal</button>
                            <button type="submit" class="btn-primary text-xs cursor-pointer">Simpan Pengaturan</button>
                        </div>
                    </form>
                </x-modal>
            @endcan

            @can('cancel', $ticket)
                <x-modal name="modal-cancel-ticket" focusable>
                    <form method="POST" action="{{ route('tickets.cancel', $ticket) }}" class="p-6">
                        @csrf
                        <h3 class="text-base font-bold text-slate-900 mb-1">Batalkan Permohonan Tiket</h3>
                        <p class="text-xs text-slate-500 mb-4">
                            Apakah Anda yakin ingin membatalkan permohonan tiket <strong>{{ $ticket->ticket_number }}</strong>?
                        </p>

                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Pembatalan</label>
                            <textarea
                                name="reason"
                                rows="3"
                                required
                                placeholder="Contoh: Kendala sudah teratasi sendiri / pengajuan digabungkan ke tiket lain..."
                                class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"
                            >{{ $ticket->status === \App\Models\Ticket::STATUS_DRAFT ? 'Draf tiket dibatalkan oleh pemohon.' : '' }}</textarea>
                        </div>

                        @if($ticket->status === \App\Models\Ticket::STATUS_DRAFT)
                            <label class="inline-flex items-center gap-2 mb-4 cursor-pointer">
                                <input type="checkbox" name="delete_draft" value="1" checked class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                <span class="text-xs font-medium text-slate-700">Hapus permanen draf tiket ini dari daftar</span>
                            </label>
                        @endif

                        <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                            <button type="button" x-on:click="$dispatch('close')" class="btn-secondary text-xs">Kembali</button>
                            <button type="submit" class="px-4 py-2 text-xs font-semibold rounded-lg bg-rose-600 hover:bg-rose-700 text-white shadow-xs cursor-pointer">
                                Ya, Batalkan Tiket
                            </button>
                        </div>
                    </form>
                </x-modal>
            @endcan

            @if($ticket->status === \App\Models\Ticket::STATUS_CANCELLED)
                <div class="bg-slate-100 border border-slate-200 rounded-xl p-4 text-xs text-slate-700 shadow-xs flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-slate-200 text-slate-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <div>
                        <div class="font-bold text-slate-900 text-sm">Tiket Dibatalkan oleh Pemohon</div>
                        @php
                            $cancelLog = $ticket->auditLogs()->where('action', 'REQUESTER_CANCEL_TICKET')->latest()->first();
                        @endphp
                        <div class="mt-1 text-slate-600 leading-relaxed">Keterangan: {{ $cancelLog?->remarks ?? '-' }}</div>
                    </div>
                </div>
            @endif
            @if($ticket->status === \App\Models\Ticket::STATUS_REJECTED_IT_HEAD)
                <div class="bg-rose-50 border border-rose-200/90 rounded-xl p-4 text-xs text-rose-900 shadow-xs flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <div>
                        <div class="font-bold text-rose-900 text-sm">Tiket Telah Ditolak oleh Kepala Bagian IT</div>
                        @php
                            $rejectItLog = $ticket->auditLogs()->where('action', 'IT_HEAD_REJECT')->latest()->first();
                        @endphp
                        <div class="mt-1 text-slate-700 leading-relaxed">Alasan: {{ $rejectItLog?->remarks ?? '-' }}</div>
                    </div>
                </div>
            @endif

            @if($ticket->status === \App\Models\Ticket::STATUS_REVISION_BY_IT_HEAD)
                <div class="bg-amber-50 border border-amber-200/90 rounded-xl p-4 text-xs text-amber-900 shadow-xs flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-amber-900 text-sm">Menunggu Revisi dari Pemohon (Catatan dari Kepala Bagian IT)</div>
                        @php
                            $reviseItLog = $ticket->auditLogs()->where('action', 'IT_HEAD_REQUEST_REVISION')->latest()->first();
                        @endphp
                        <div class="mt-1 text-slate-700 leading-relaxed">Catatan revisi: {{ $reviseItLog?->remarks ?? '-' }}</div>
                    </div>
                </div>
            @endif

            <!-- Status Alerts if Rejected / Needs Revision by Supervisor -->
            @if($ticket->status === \App\Models\Ticket::STATUS_REJECTED_SUPERVISOR)
                <div class="bg-rose-50 border border-rose-200/90 rounded-xl p-4 text-xs text-rose-900 shadow-xs flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <div>
                        <div class="font-bold text-rose-900 text-sm">Tiket Telah Ditolak oleh Atasan Langsung</div>
                        @php
                            $rejectLog = $ticket->auditLogs()->where('action', 'SUPERVISOR_REJECT')->latest()->first();
                        @endphp
                        <div class="mt-1 text-slate-700 leading-relaxed">Alasan: {{ $rejectLog?->remarks ?? '-' }}</div>
                    </div>
                </div>
            @endif

            <!-- Status Alerts if Rejected by Kadiv IT or Direksi -->
            @if($ticket->status === \App\Models\Ticket::STATUS_REJECTED_KADIV_IT)
                <div class="bg-rose-50 border border-rose-200/90 rounded-xl p-4 text-xs text-rose-900 shadow-xs flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <div>
                        <div class="font-bold text-rose-900 text-sm">Tiket Telah Ditolak oleh Kepala Divisi IT & Operasional</div>
                        @php
                            $rejectKadivLog = $ticket->auditLogs()->whereIn('action', ['KADIV_IT_REJECT', 'LOA_KADIV_REJECTED'])->latest()->first();
                        @endphp
                        <div class="mt-1 text-slate-700 leading-relaxed">Alasan: {{ $rejectKadivLog?->remarks ?? '-' }}</div>
                    </div>
                </div>
            @endif

            @if($ticket->status === \App\Models\Ticket::STATUS_REJECTED_DIREKSI)
                <div class="bg-rose-50 border border-rose-200/90 rounded-xl p-4 text-xs text-rose-900 shadow-xs flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <div>
                        <div class="font-bold text-rose-900 text-sm">Tiket Telah Ditolak oleh Dewan Direksi</div>
                        @php
                            $rejectDireksiLog = $ticket->auditLogs()->whereIn('action', ['DIREKSI_REJECT', 'LOA_DIREKSI_REJECTED'])->latest()->first();
                        @endphp
                        <div class="mt-1 text-slate-700 leading-relaxed">Alasan: {{ $rejectDireksiLog?->remarks ?? '-' }}</div>
                    </div>
                </div>
            @endif

            @if($ticket->status === \App\Models\Ticket::STATUS_REVISION_REQUESTER)
                <div class="bg-amber-50 border border-amber-200/90 rounded-xl p-4 text-xs text-amber-900 shadow-xs flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-amber-900 text-sm">Menunggu Revisi dari Pemohon (Anda)</div>
                        @php
                            $reviseLog = $ticket->auditLogs()->where('action', 'SUPERVISOR_REQUEST_REVISION')->latest()->first();
                        @endphp
                        <div class="mt-1 text-slate-700 leading-relaxed">Catatan revisi atasan: {{ $reviseLog?->remarks ?? '-' }}</div>
                    </div>
                </div>
            @endif

            @if($ticket->status === \App\Models\Ticket::STATUS_REVISION_WORK)
                @php
                    $latestWorkRevise = $ticket->reviews->where('decision', 'Revise')->sortByDesc('created_at')->first();
                    $latestWorkReviseAudit = $ticket->auditLogs->whereIn('action', ['USER_REVIEW_REVISE', 'REQUESTER_REVIEWS_REVISE'])->sortByDesc('created_at')->first();
                    $workReviseNote = $latestWorkRevise?->feedback ?? $latestWorkReviseAudit?->remarks ?? 'Pemohon meminta perbaikan pada hasil pengerjaan tiket ini.';
                @endphp
                <div class="bg-amber-50 border border-amber-200/90 rounded-xl p-4 text-xs text-amber-900 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <div class="font-bold text-amber-900 text-sm">Permintaan Revisi Hasil Pengerjaan dari Pemohon</div>
                            <div class="mt-1 text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $workReviseNote }}</div>
                        </div>
                    </div>
                    <button type="button" @click="setTab('activities')" class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shrink-0 self-start sm:self-center cursor-pointer">
                        Buka Panel Pengerjaan &rarr;
                    </button>
                </div>
            @endif

            <!-- Unified Main Content Card (Tabs + Active Tab Body) -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <!-- Sub-Menu Navigation Tabs Header -->
                <div class="bg-slate-50/80 border-b border-slate-200 px-2.5 sm:px-3 py-2">
                    <nav class="flex flex-nowrap sm:flex-wrap items-center gap-1 sm:gap-1.5 overflow-x-auto sm:overflow-visible [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none] pb-0.5 sm:pb-0" aria-label="Sub-Menu Tiket">
                        <!-- Tab 1: Detail Permohonan -->
                        <button 
                            type="button" 
                            @click="setTab('detail')"
                            :class="activeTab === 'detail' 
                                ? 'bg-[#1E40AF] text-white shadow-xs font-semibold' 
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 font-medium'"
                            class="px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-lg text-xs flex items-center gap-1.5 sm:gap-2 transition-all cursor-pointer shrink-0 whitespace-nowrap">
                            <svg class="hidden sm:block w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Detail &amp; Berkas</span>
                            @if($ticket->attachments->count() > 0)
                                <span :class="activeTab === 'detail' ? 'bg-white/20 text-white' : 'bg-slate-200/80 text-slate-700'" class="px-1.5 py-0.5 rounded-full text-[11px] sm:text-xs font-bold leading-none">
                                    {{ $ticket->attachments->count() }}
                                </span>
                            @endif
                        </button>

                        <!-- Tab: Forum Diskusi -->
                        <button 
                            type="button" 
                            @click="setTab('discussion')"
                            :class="activeTab === 'discussion' 
                                ? 'bg-[#1E40AF] text-white shadow-xs font-semibold' 
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 font-medium'"
                            class="px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-lg text-xs flex items-center gap-1.5 sm:gap-2 transition-all cursor-pointer shrink-0 whitespace-nowrap">
                            <svg class="hidden sm:block w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                            <span>Forum Diskusi</span>
                            <span x-text="discussionCount" :class="activeTab === 'discussion' ? 'bg-white/20 text-white' : 'bg-slate-200/80 text-slate-700'" class="px-1.5 py-0.5 rounded-full text-[11px] sm:text-xs font-bold leading-none">
                                {{ $ticket->discussions->count() }}
                            </span>
                        </button>

                        <!-- Tab 2: Pengerjaan IT -->
                        @if(in_array($ticket->status, [
                            \App\Models\Ticket::STATUS_READY_FOR_WORK,
                            \App\Models\Ticket::STATUS_IN_PROGRESS,
                            \App\Models\Ticket::STATUS_WAITING_USER_REVIEW,
                            \App\Models\Ticket::STATUS_REVISION_WORK,
                            \App\Models\Ticket::STATUS_COMPLETED,
                        ]) || $ticket->activityLogs->isNotEmpty())
                            <button 
                                type="button" 
                                @click="setTab('activities')"
                                :class="activeTab === 'activities' 
                                    ? 'bg-[#1E40AF] text-white shadow-xs font-semibold' 
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 font-medium'"
                                class="px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-lg text-xs flex items-center gap-1.5 sm:gap-2 transition-all cursor-pointer shrink-0 whitespace-nowrap">
                                <svg class="hidden sm:block w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                </svg>
                                <span>Pengerjaan IT</span>
                                <span :class="activeTab === 'activities' ? 'bg-white/20 text-white' : 'bg-slate-200/80 text-slate-700'" class="px-1.5 py-0.5 rounded-full text-[11px] sm:text-xs font-bold leading-none">
                                    {{ $ticket->activityLogs->count() }}
                                </span>
                            </button>
                        @endif

                        <!-- Tab 3: Notulensi Rapat Major -->
                        @if($ticket->classification === \App\Models\Ticket::CLASSIFICATION_MAJOR || $ticket->meetingMinutes)
                            <button 
                                type="button" 
                                @click="setTab('minutes')"
                                :class="activeTab === 'minutes' 
                                    ? 'bg-[#1E40AF] text-white shadow-xs font-semibold' 
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 font-medium'"
                                class="px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-lg text-xs flex items-center gap-1.5 sm:gap-2 transition-all cursor-pointer shrink-0 whitespace-nowrap">
                                <svg class="hidden sm:block w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Notulensi Rapat</span>
                                @if($ticket->meetingMinutes?->isApproved())
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                @else
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                @endif
                            </button>
                        @endif

                        <!-- Tab 4: Review Kepuasan -->
                        @if($ticket->status === \App\Models\Ticket::STATUS_WAITING_USER_REVIEW || $ticket->reviews->isNotEmpty())
                            <button 
                                type="button" 
                                @click="setTab('review')"
                                :class="activeTab === 'review' 
                                    ? 'bg-[#1E40AF] text-white shadow-xs font-semibold' 
                                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 font-medium'"
                                class="px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-lg text-xs flex items-center gap-1.5 sm:gap-2 transition-all cursor-pointer shrink-0 whitespace-nowrap">
                                <svg class="hidden sm:block w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                                <span>Ulasan Kepuasan</span>
                                @if($ticket->reviews->isNotEmpty())
                                    <span :class="activeTab === 'review' ? 'bg-white/20 text-white' : 'bg-slate-200/80 text-slate-700'" class="px-1.5 py-0.5 rounded-full text-[11px] sm:text-xs font-bold leading-none">
                                        {{ number_format($ticket->reviews->avg('rating'), 1) }}
                                    </span>
                                @endif
                            </button>
                        @endif

                        <!-- Tab 5: Jejak Audit (Sub-Menu) -->
                        <button 
                            type="button" 
                            @click="setTab('audit')"
                            :class="activeTab === 'audit' 
                                ? 'bg-[#1E40AF] text-white shadow-xs font-semibold' 
                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 font-medium'"
                            class="px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-lg text-xs flex items-center gap-1.5 sm:gap-2 transition-all cursor-pointer shrink-0 whitespace-nowrap">
                            <svg class="hidden sm:block w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Jejak Audit</span>
                            <span :class="activeTab === 'audit' ? 'bg-white/20 text-white' : 'bg-slate-200/80 text-slate-700'" class="px-1.5 py-0.5 rounded-full text-[11px] sm:text-xs font-bold leading-none">
                                {{ $ticket->auditLogs->count() }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- TAB CONTENT 1: Detail & Berkas Permohonan -->
                <div x-show="activeTab === 'detail'" class="p-4 sm:p-6 space-y-5 sm:space-y-6">
                    <!-- Section 1: Deskripsi Kebutuhan -->
                    <div>
                        <div class="mb-2 sm:mb-3">
                            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Deskripsi Kebutuhan</h2>
                        </div>
                        <div class="sm:p-4 sm:bg-slate-50/70 sm:rounded-xl sm:border sm:border-slate-200/80 prose max-w-none text-sm text-slate-800 whitespace-pre-wrap leading-relaxed">{{ trim($ticket->description) }}</div>
                    </div>

                    <!-- Section 2: Berkas Lampiran Pendukung -->
                    <div class="pt-4 sm:pt-5 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-2.5 sm:mb-3">
                            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Berkas Lampiran Pendukung</h2>
                            <span class="text-xs text-slate-400">{{ $ticket->attachments->count() }} file</span>
                        </div>

                        @if($ticket->attachments->isEmpty())
                            <div class="p-3.5 bg-slate-50/60 rounded-xl border border-dashed border-slate-200 text-xs text-slate-400 text-center">
                                Tidak ada lampiran berkas pada tiket ini.
                            </div>
                        @else
                            <div class="divide-y divide-slate-100 sm:rounded-xl sm:border sm:border-slate-200/80 sm:bg-slate-50/40 sm:px-4">
                                @foreach($ticket->attachments as $attachment)
                                    <div class="py-2.5 sm:py-3 flex items-center justify-between gap-3 sm:gap-4">
                                        <div class="flex items-center gap-3 min-w-0 flex-1">
                                            <div class="w-9 h-9 sm:w-8 sm:h-8 rounded-xl sm:rounded-lg bg-slate-100 sm:bg-white sm:border sm:border-slate-200 text-slate-600 flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-sm font-semibold text-slate-900 truncate">{{ $attachment->file_name }}</div>
                                                <div class="text-xs text-slate-400 truncate mt-0.5">
                                                    <span>{{ number_format($attachment->file_size_bytes / 1024, 1) }} KB</span>
                                                    <span class="sm:hidden">&bull; {{ $attachment->created_at->format('d M Y') }}</span>
                                                    <span class="hidden sm:inline">&bull; Diunggah oleh {{ $attachment->uploader->name }} ({{ $attachment->created_at->format('d/m/Y H:i') }})</span>
                                                </div>
                                            </div>
                                        </div>
                                        <a
                                            href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}"
                                            class="w-9 h-9 sm:w-auto sm:h-auto rounded-xl sm:rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 sm:px-3 sm:py-1.5 text-xs font-medium shrink-0 inline-flex items-center justify-center gap-1.5 transition-colors"
                                            title="Unduh {{ $attachment->file_name }}"
                                        >
                                            <svg class="w-4 h-4 sm:w-3.5 sm:h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            <span class="hidden sm:inline">Unduh</span>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- TAB CONTENT: Forum Diskusi Tiket -->
                <div x-show="activeTab === 'discussion'" style="display: none;">
                    @include('tickets.partials.discussion-section')
                </div>

                <!-- TAB CONTENT 2: Pengerjaan IT (Tahap D) -->
                @if(in_array($ticket->status, [
                    \App\Models\Ticket::STATUS_READY_FOR_WORK,
                    \App\Models\Ticket::STATUS_IN_PROGRESS,
                    \App\Models\Ticket::STATUS_WAITING_USER_REVIEW,
                    \App\Models\Ticket::STATUS_REVISION_WORK,
                    \App\Models\Ticket::STATUS_COMPLETED,
                ]) || $ticket->activityLogs->isNotEmpty())
                    <div x-show="activeTab === 'activities'" style="display: none;">
                        @includeWhen(view()->exists('tickets.partials.activity-timeline'), 'tickets.partials.activity-timeline')
                    </div>
                @endif

                <!-- TAB CONTENT 3: Notulensi Rapat Major (Tahap C) -->
                @if($ticket->classification === \App\Models\Ticket::CLASSIFICATION_MAJOR || $ticket->meetingMinutes)
                    <div x-show="activeTab === 'minutes'" style="display: none;">
                        @includeWhen(view()->exists('tickets.partials.minutes-section'), 'tickets.partials.minutes-section')
                    </div>
                @endif

                <!-- TAB CONTENT 4: Ulasan Kepuasan (Tahap E) -->
                @if($ticket->status === \App\Models\Ticket::STATUS_WAITING_USER_REVIEW || $ticket->reviews->isNotEmpty())
                    <div x-show="activeTab === 'review'" style="display: none;">
                        @includeWhen(view()->exists('tickets.partials.review-section'), 'tickets.partials.review-section')
                    </div>
                @endif

                <!-- TAB CONTENT 5: Jejak Audit & Riwayat Keputusan (Sub-Menu) -->
                <div x-show="activeTab === 'audit'" style="display: none;" data-ajax-poll="ticket-audit-section">
                    @include('tickets.partials.audit-history')
                </div>
            </div>

        </div>

        <!-- Sidebar Meta Info (4 Cols on Desktop, Compact Collapsible Cards on Mobile) -->
        <div class="lg:col-span-4 space-y-3 lg:space-y-5" x-data="{ openPemohon: false, openPetugas: false, openLinimasa: false }">

            <!-- Card: Data Pemohon -->
            <div class="bg-white rounded-xl border border-slate-200 p-4 lg:p-5 shadow-xs">
                <button
                    type="button"
                    @click="openPemohon = !openPemohon"
                    class="w-full flex items-center justify-between lg:cursor-default lg:pointer-events-none"
                >
                    <h3 class="text-xs font-bold text-slate-600 lg:text-slate-500 uppercase tracking-wider">Informasi Pemohon</h3>
                    <div class="flex items-center gap-2 lg:hidden">
                        <span class="text-xs font-medium text-slate-700 truncate max-w-[140px]">{{ $ticket->requester->name }}</span>
                        <svg class="w-4 h-4 text-slate-400 transition-transform" :class="openPemohon ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </button>
                <div :class="openPemohon ? 'block' : 'hidden lg:block'" class="mt-3 pt-3 border-t border-slate-100">
                    <div class="flex items-center gap-3 pb-3 mb-3 border-b border-slate-100">
                        <div class="w-9 h-9 rounded-full bg-blue-50 text-[#1E40AF] border border-blue-200/70 flex items-center justify-center text-xs font-bold shrink-0">
                            {{ strtoupper(substr($ticket->requester->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-900 truncate">{{ $ticket->requester->name }}</div>
                            <div class="text-xs text-slate-500 truncate">{{ $ticket->requester->department?->name ?? 'Belum ditentukan' }}</div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">Email</span>
                        <span class="tabular-nums text-slate-700 font-medium truncate max-w-[200px]">{{ $ticket->requester->email }}</span>
                    </div>
                </div>
            </div>

            <!-- Card: Petugas Terkait -->
            @php
                $itHeadActor = $ticket->auditLogs->first(fn($log) => str_starts_with($log->action, 'IT_HEAD_'))?->actor;
                $kadivActor = $ticket->auditLogs->first(fn($log) => str_starts_with($log->action, 'KADIV_IT_'))?->actor;
                $direksiActor = $ticket->auditLogs->first(fn($log) => str_starts_with($log->action, 'DIREKSI_'))?->actor;

                $itHeadUser = $itHeadActor ?? \App\Models\User::where('role', 'it_head')->first();
                $kadivUser = $kadivActor ?? \App\Models\User::where('role', 'kadiv_it')->first();
                $direksiUser = $direksiActor ?? \App\Models\User::where('role', 'direksi')->first();

                $itHeadName = $itHeadUser?->name ?? 'Kepala Bagian IT';
                $kadivName = $kadivUser?->name ?? 'Kadiv IT & Operasional';
                $direksiName = $direksiUser?->name ?? 'Dewan Direksi';

                $status = $ticket->status;
                $isMajor = $ticket->classification === \App\Models\Ticket::CLASSIFICATION_MAJOR
                    || (!$ticket->classification && $ticket->category === 'Perubahan Sistem');

                // Tahap B: Atasan Langsung
                if ($status === \App\Models\Ticket::STATUS_REJECTED_SUPERVISOR) {
                    $stepBState = 'rejected';
                    $stepBLabel = 'Ditolak';
                } elseif ($status === \App\Models\Ticket::STATUS_REVISION_REQUESTER) {
                    $stepBState = 'revision';
                    $stepBLabel = 'Revisi';
                } elseif ($status === \App\Models\Ticket::STATUS_WAITING_SUPERVISOR) {
                    $stepBState = 'active';
                    $stepBLabel = 'Menunggu';
                } elseif ($ticket->approved_by_supervisor_at || !in_array($status, [\App\Models\Ticket::STATUS_DRAFT, \App\Models\Ticket::STATUS_WAITING_SUPERVISOR, \App\Models\Ticket::STATUS_REVISION_REQUESTER, \App\Models\Ticket::STATUS_REJECTED_SUPERVISOR])) {
                    $stepBState = 'done';
                    $stepBLabel = 'Disetujui';
                } else {
                    $stepBState = 'upcoming';
                    $stepBLabel = 'Menunggu';
                }

                // Tahap C: Kepala Bagian IT
                $preItHeadStatuses = [
                    \App\Models\Ticket::STATUS_DRAFT,
                    \App\Models\Ticket::STATUS_WAITING_SUPERVISOR,
                    \App\Models\Ticket::STATUS_REVISION_REQUESTER,
                    \App\Models\Ticket::STATUS_REJECTED_SUPERVISOR,
                ];
                if ($status === \App\Models\Ticket::STATUS_REJECTED_IT_HEAD) {
                    $stepCState = 'rejected';
                    $stepCLabel = 'Ditolak';
                } elseif ($status === \App\Models\Ticket::STATUS_REVISION_BY_IT_HEAD) {
                    $stepCState = 'revision';
                    $stepCLabel = 'Revisi';
                } elseif ($status === \App\Models\Ticket::STATUS_WAITING_IT_HEAD) {
                    $stepCState = 'active';
                    $stepCLabel = 'Menunggu';
                } elseif ($ticket->approved_by_it_head_at || !in_array($status, array_merge($preItHeadStatuses, [\App\Models\Ticket::STATUS_WAITING_IT_HEAD, \App\Models\Ticket::STATUS_REVISION_BY_IT_HEAD, \App\Models\Ticket::STATUS_REJECTED_IT_HEAD]))) {
                    $stepCState = 'done';
                    $stepCLabel = 'Disetujui';
                } else {
                    $stepCState = 'upcoming';
                    $stepCLabel = 'Belum Giliran';
                }

                // Tahap C.1: LOA Kadiv IT & Operasional (Major)
                $postKadivStatuses = [
                    \App\Models\Ticket::STATUS_WAITING_LOA_DIREKSI,
                    \App\Models\Ticket::STATUS_REJECTED_DIREKSI,
                    \App\Models\Ticket::STATUS_WAITING_MAJOR_MEETING,
                    \App\Models\Ticket::STATUS_WAITING_MINUTES,
                    \App\Models\Ticket::STATUS_WAITING_USER_MINUTES,
                    \App\Models\Ticket::STATUS_WAITING_MINUTES_APPROVAL,
                    \App\Models\Ticket::STATUS_REVISION_MINUTES,
                    \App\Models\Ticket::STATUS_READY_FOR_WORK,
                    \App\Models\Ticket::STATUS_IN_PROGRESS,
                    \App\Models\Ticket::STATUS_WAITING_USER_REVIEW,
                    \App\Models\Ticket::STATUS_REVISION_WORK,
                    \App\Models\Ticket::STATUS_COMPLETED,
                ];
                if ($status === \App\Models\Ticket::STATUS_REJECTED_KADIV_IT) {
                    $stepC1State = 'rejected';
                    $stepC1Label = 'Ditolak';
                } elseif ($status === \App\Models\Ticket::STATUS_WAITING_LOA_KADIV_IT) {
                    $stepC1State = 'active';
                    $stepC1Label = 'Menunggu';
                } elseif ($ticket->approved_by_kadiv_at || ($isMajor && in_array($status, $postKadivStatuses))) {
                    $stepC1State = 'done';
                    $stepC1Label = 'Disetujui';
                } else {
                    $stepC1State = 'upcoming';
                    $stepC1Label = 'Belum Giliran';
                }

                // Tahap C.2: LOA Dewan Direksi (Major)
                $postDireksiStatuses = [
                    \App\Models\Ticket::STATUS_WAITING_MAJOR_MEETING,
                    \App\Models\Ticket::STATUS_WAITING_MINUTES,
                    \App\Models\Ticket::STATUS_WAITING_USER_MINUTES,
                    \App\Models\Ticket::STATUS_WAITING_MINUTES_APPROVAL,
                    \App\Models\Ticket::STATUS_REVISION_MINUTES,
                    \App\Models\Ticket::STATUS_READY_FOR_WORK,
                    \App\Models\Ticket::STATUS_IN_PROGRESS,
                    \App\Models\Ticket::STATUS_WAITING_USER_REVIEW,
                    \App\Models\Ticket::STATUS_REVISION_WORK,
                    \App\Models\Ticket::STATUS_COMPLETED,
                ];
                if ($status === \App\Models\Ticket::STATUS_REJECTED_DIREKSI) {
                    $stepC2State = 'rejected';
                    $stepC2Label = 'Ditolak';
                } elseif ($status === \App\Models\Ticket::STATUS_WAITING_LOA_DIREKSI) {
                    $stepC2State = 'active';
                    $stepC2Label = 'Menunggu';
                } elseif ($ticket->approved_by_direksi_at || ($isMajor && in_array($status, $postDireksiStatuses))) {
                    $stepC2State = 'done';
                    $stepC2Label = 'Disetujui';
                } else {
                    $stepC2State = 'upcoming';
                    $stepC2Label = 'Belum Giliran';
                }

                // Tahap D: Pengerjaan Teknis IT
                if ($status === \App\Models\Ticket::STATUS_REVISION_WORK) {
                    $stepDState = 'revision';
                    $stepDLabel = 'Revisi';
                } elseif ($status === \App\Models\Ticket::STATUS_READY_FOR_WORK) {
                    $stepDState = 'active';
                    $stepDLabel = 'Antrean Pool';
                } elseif ($status === \App\Models\Ticket::STATUS_IN_PROGRESS) {
                    $stepDState = 'active';
                    $stepDLabel = 'Dikerjakan';
                } elseif (in_array($status, [\App\Models\Ticket::STATUS_WAITING_USER_REVIEW, \App\Models\Ticket::STATUS_COMPLETED])) {
                    $stepDState = 'done';
                    $stepDLabel = 'Selesai';
                } else {
                    $stepDState = 'upcoming';
                    $stepDLabel = 'Belum Giliran';
                }

                $officers = [
                    [
                        'role' => 'Atasan Langsung',
                        'officer' => $ticket->supervisor->name ?? 'Tidak ada',
                        'is_unassigned' => false,
                        'state' => $stepBState,
                        'label' => $stepBLabel,
                    ],
                    [
                        'role' => 'Kepala Bagian IT',
                        'officer' => $itHeadName,
                        'is_unassigned' => false,
                        'state' => $stepCState,
                        'label' => $stepCLabel,
                    ],
                ];

                if ($isMajor) {
                    $officers[] = [
                        'role' => 'Kadiv IT & Operasional',
                        'officer' => $kadivName,
                        'is_unassigned' => false,
                        'state' => $stepC1State,
                        'label' => $stepC1Label,
                    ];
                    $officers[] = [
                        'role' => 'Dewan Direksi',
                        'officer' => $direksiName,
                        'is_unassigned' => false,
                        'state' => $stepC2State,
                        'label' => $stepC2Label,
                    ];
                }

                $officers[] = [
                    'role' => 'Teknisi IT',
                    'officer' => $ticket->assignedTo?->name ?? 'Belum diambil',
                    'is_unassigned' => !$ticket->assignedTo,
                    'state' => $stepDState,
                    'label' => $stepDLabel,
                ];
            @endphp

            <div class="bg-white rounded-xl border border-slate-200 p-4 lg:p-5 shadow-xs">
                <button
                    type="button"
                    @click="openPetugas = !openPetugas"
                    class="w-full flex items-center justify-between lg:cursor-default lg:pointer-events-none"
                >
                    <h3 class="text-xs font-bold text-slate-600 lg:text-slate-500 uppercase tracking-wider">Petugas Terkait</h3>
                    <div class="flex items-center gap-2 lg:hidden">
                        <span class="text-xs text-slate-500">{{ count($officers) }} pihak</span>
                        <svg class="w-4 h-4 text-slate-400 transition-transform" :class="openPetugas ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </button>

                <div :class="openPetugas ? 'block' : 'hidden lg:block'" class="mt-3 pt-3 border-t border-slate-100 divide-y divide-slate-100">
                    @foreach($officers as $item)
                        @php
                            $st = $item['state'];
                            $statusTextClass = match($st) {
                                'done' => 'text-emerald-600 font-medium',
                                'active', 'revision' => 'text-amber-600 font-medium',
                                'rejected' => 'text-rose-600 font-medium',
                                default => 'text-slate-400',
                            };
                            $dotClass = match($st) {
                                'done' => 'bg-emerald-500',
                                'active', 'revision' => 'bg-amber-500',
                                'rejected' => 'bg-rose-500',
                                default => null,
                            };
                        @endphp
                        <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-[11px] text-slate-400">{{ $item['role'] }}</div>
                                <div class="text-xs font-semibold text-slate-900 truncate mt-0.5">
                                    @if($item['is_unassigned'])
                                        <span class="text-slate-400 italic font-normal">{{ $item['officer'] }}</span>
                                    @else
                                        {{ $item['officer'] }}
                                    @endif
                                </div>
                            </div>

                            <span class="inline-flex items-center gap-1.5 text-xs shrink-0 {{ $statusTextClass }}">
                                @if($dotClass)
                                    <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }}"></span>
                                    <span>{{ $item['label'] }}</span>
                                @else
                                    <span>-</span>
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Card: Linimasa Waktu (Ringkas - Hanya Tahapan yang Sudah Tercatat) -->
            @php
                $milestones = collect([
                    ['label' => 'Dibuat', 'time' => $ticket->created_at],
                    ['label' => 'Diajukan', 'time' => $ticket->submitted_at],
                    ['label' => 'Disetujui Atasan', 'time' => $ticket->approved_by_supervisor_at],
                    ['label' => 'Disetujui Kabag IT', 'time' => $ticket->approved_by_it_head_at],
                    ['label' => 'Disetujui Kadiv IT', 'time' => $ticket->approved_by_kadiv_at],
                    ['label' => 'Disetujui Direksi', 'time' => $ticket->approved_by_direksi_at],
                    ['label' => 'Rapat Dijadwalkan', 'time' => $ticket->meeting_scheduled_at],
                    ['label' => 'Notulensi Disetujui', 'time' => $ticket->minutes_approved_at],
                    ['label' => 'Mulai Dikerjakan', 'time' => $ticket->work_started_at],
                    ['label' => 'Pekerjaan Selesai', 'time' => $ticket->work_completed_at],
                    ['label' => 'Ditutup Resmi', 'time' => $ticket->closed_at],
                ])->filter(fn ($ms) => ! is_null($ms['time']))->values();
            @endphp

            <div class="bg-white rounded-xl border border-slate-200 p-4 lg:p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <button
                        type="button"
                        @click="openLinimasa = !openLinimasa"
                        class="flex-1 flex items-center justify-between lg:cursor-default lg:pointer-events-none text-left"
                    >
                        <h3 class="text-xs font-bold text-slate-600 lg:text-slate-500 uppercase tracking-wider">Linimasa Waktu</h3>
                        <svg class="w-4 h-4 text-slate-400 transition-transform lg:hidden mr-2" :class="openLinimasa ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="setTab('audit')"
                        class="text-[11px] font-medium text-[#1E40AF] hover:underline cursor-pointer shrink-0"
                    >
                        Jejak Audit ({{ $ticket->auditLogs->count() }})
                    </button>
                </div>

                <div :class="openLinimasa ? 'block' : 'hidden lg:block'" class="mt-3 pt-3 border-t border-slate-100 divide-y divide-slate-100 text-xs">
                    <div class="py-2 first:pt-0 flex items-center justify-between gap-2">
                        <span class="text-slate-500 font-medium">Target Timeline (Kabag IT)</span>
                        @if($ticket->target_completion_at)
                            <div class="flex items-center gap-1.5">
                                <span class="font-semibold {{ $ticket->isOverdue() ? 'text-rose-600' : 'text-slate-900' }} tabular-nums">
                                    {{ $ticket->target_completion_at->format('d/m/Y') }}
                                </span>
                                @if($ticket->isOverdue())
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">Overdue</span>
                                @elseif($ticket->isCompletedLate())
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Lewat Target</span>
                                @else
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700">On Track</span>
                                @endif
                            </div>
                        @else
                            <span class="text-slate-400 italic">Belum ditetapkan</span>
                        @endif
                    </div>
                    @foreach($milestones as $ms)
                        <div class="py-2 first:pt-0 last:pb-0 flex items-center justify-between gap-2">
                            <span class="text-slate-500">{{ $ms['label'] }}</span>
                            <span class="font-medium text-slate-800 tabular-nums">{{ $ms['time']->format('d/m/Y H:i') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
