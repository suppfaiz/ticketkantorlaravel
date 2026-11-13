@if(in_array($ticket->status, [
    \App\Models\Ticket::STATUS_READY_FOR_WORK,
    \App\Models\Ticket::STATUS_IN_PROGRESS,
    \App\Models\Ticket::STATUS_WAITING_USER_REVIEW,
    \App\Models\Ticket::STATUS_REVISION_WORK,
    \App\Models\Ticket::STATUS_COMPLETED,
]) || $ticket->activityLogs->isNotEmpty())
    @php
        $activityLabels = [
            'work_completion' => ['label' => 'Pekerjaan Selesai', 'dot' => 'bg-emerald-500'],
            'progress_note'   => ['label' => 'Update Progres', 'dot' => 'bg-slate-400'],
            'code_commit'     => ['label' => 'Perbaikan Sistem', 'dot' => 'bg-slate-400'],
            'test_result'     => ['label' => 'Hasil Pengujian', 'dot' => 'bg-slate-400'],
            'deployment'      => ['label' => 'Rilis ke Server', 'dot' => 'bg-emerald-500'],
            'release_to_pool' => ['label' => 'Dikembalikan ke Pool', 'dot' => 'bg-amber-500'],
        ];

        $timelineEvents = collect();

        if ($ticket->work_started_at) {
            $timelineEvents->push([
                'timestamp' => $ticket->work_started_at,
                'label'     => 'Mulai Dikerjakan',
                'actor'     => $ticket->assignedTo?->name ?? 'Teknisi IT',
                'content'   => null,
                'dot'       => 'bg-slate-400',
            ]);
        }

        foreach ($ticket->activityLogs as $act) {
            $meta = $activityLabels[$act->activity_type] ?? ['label' => 'Catatan Progres', 'dot' => 'bg-slate-400'];
            $timelineEvents->push([
                'timestamp' => $act->created_at,
                'label'     => $meta['label'],
                'actor'     => $act->user->name ?? 'Teknisi IT',
                'content'   => trim((string) $act->content),
                'dot'       => $meta['dot'],
            ]);
        }

        $timelineEvents = $timelineEvents->sortBy(fn($e) => $e['timestamp']?->timestamp ?? 0)->values();
    @endphp

    <div class="p-4 sm:p-6">
        <!-- Simple Header -->
        <div class="hidden sm:flex flex-wrap items-center justify-between gap-2 pb-4 mb-5 border-b border-slate-100">
            <h2 class="text-sm font-bold text-slate-900">Progres &amp; Penanganan IT</h2>
            @if($ticket->assignedTo)
                <span class="text-xs text-slate-500">
                    Teknisi: <strong class="text-slate-800 font-semibold">{{ $ticket->assignedTo->name }}</strong>
                </span>
            @endif
        </div>

        <!-- Self Assign Action (Pool) -->
        @can('selfAssign', $ticket)
            <div class="mb-5 p-4 bg-slate-50 border border-slate-200 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="text-xs text-slate-600">
                    Tiket ini siap dikerjakan. Ambil tiket untuk mulai menangani.
                </div>
                <form method="POST" action="{{ route('tickets.self-assign', $ticket) }}">
                    @csrf
                    <button type="submit" class="btn-primary text-xs py-2 px-3.5 whitespace-nowrap cursor-pointer">
                        Ambil Tiket Ini
                    </button>
                </form>
            </div>
        @endcan

        <!-- Pencatatan harian menjadi aksi utama; penyelesaian dibuka secara sengaja. -->
        @if(((int) auth()->id() === (int) $ticket->assigned_to || auth()->user()->isAdmin()) && in_array($ticket->status, [\App\Models\Ticket::STATUS_IN_PROGRESS, \App\Models\Ticket::STATUS_REVISION_WORK]))
            <div class="mb-5 space-y-3">
                <!-- Catat Progres -->
                <form method="POST" action="{{ route('tickets.activities.store', $ticket) }}" enctype="multipart/form-data" class="p-4 bg-slate-50/70 border border-slate-200 rounded-xl flex flex-col gap-4">
                    @csrf
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <label for="activity_type" class="text-sm font-bold text-slate-900">Catat progres</label>
                            <select id="activity_type" name="activity_type" class="text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white py-1 pl-2.5 pr-7">
                                <option value="progress_note">Update Progres</option>
                                <option value="code_commit">Perbaikan Sistem</option>
                                <option value="test_result">Hasil Pengujian</option>
                                <option value="deployment">Rilis ke Server</option>
                            </select>
                        </div>
                        <label for="activity_content" class="sr-only">Catatan progres pengerjaan</label>
                        <textarea id="activity_content" name="content" rows="3" required placeholder="Tulis progres pengerjaan..."
                            class="w-full text-sm rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">{{ old('content') }}</textarea>
                        <div x-data="{ fileName: '' }">
                            <span class="block text-xs font-medium text-slate-600 mb-1.5">Bukti / screenshot <span class="font-normal text-slate-400">(opsional, maks. 5 MB)</span></span>
                            <label class="flex min-h-10 items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-white px-3 py-2 text-xs text-slate-600 cursor-pointer hover:border-[#1E40AF] hover:bg-blue-50/30">
                                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" class="sr-only" @change="fileName = $event.target.files[0]?.name || ''">
                                <svg class="h-4 w-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                <span class="truncate" x-text="fileName || 'Pilih berkas pendukung'">Pilih berkas pendukung</span>
                            </label>
                        </div>
                        @error('content')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        @error('attachment')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn-primary text-xs py-2 px-3.5 self-end cursor-pointer">
                        Simpan Progres
                    </button>
                </form>

                <!-- Selesaikan Pekerjaan -->
                @can('completeWork', $ticket)
                    <details class="group overflow-hidden rounded-xl border border-blue-200 bg-blue-50/40" @if($errors->has('completion_notes')) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 marker:hidden focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#1E40AF]/30">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-slate-900">Pekerjaan sudah selesai?</span>
                                <span class="mt-0.5 block text-xs text-slate-600">Kirim hasilnya kepada pemohon untuk diperiksa.</span>
                            </span>
                            <span class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-[#1E40AF]">
                                <span class="group-open:hidden">Buka formulir</span>
                                <span class="hidden group-open:inline">Tutup formulir</span>
                                <svg class="h-4 w-4 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </span>
                        </summary>
                    <form method="POST" action="{{ route('tickets.complete-work', $ticket) }}" enctype="multipart/form-data" class="flex flex-col gap-4 border-t border-blue-100 bg-white p-4">
                        @csrf
                        <div class="space-y-2.5">
                            <p class="text-xs text-slate-600">Pastikan pekerjaan siap ditinjau sebelum mengirim ke pemohon.</p>
                            <label for="completion_notes" class="sr-only">Catatan penyelesaian untuk pemohon</label>
                            <textarea id="completion_notes" name="completion_notes" rows="3" placeholder="Catatan penyelesaian untuk pemohon (opsional)..."
                                class="w-full text-sm rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">{{ old('completion_notes') }}</textarea>
                            <div x-data="{ fileName: '' }">
                                <span class="block text-xs font-medium text-slate-600 mb-1.5">Bukti hasil kerja <span class="font-normal text-slate-400">(opsional, maks. 5 MB)</span></span>
                                <label class="flex min-h-10 items-center gap-2 rounded-lg border border-dashed border-slate-300 bg-white px-3 py-2 text-xs text-slate-600 cursor-pointer hover:border-[#1E40AF] hover:bg-blue-50/30">
                                    <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" class="sr-only" @change="fileName = $event.target.files[0]?.name || ''">
                                    <svg class="h-4 w-4 shrink-0 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                    <span class="truncate" x-text="fileName || 'Pilih berkas hasil kerja'">Pilih berkas hasil kerja</span>
                                </label>
                            </div>
                            @error('completion_notes')
                                <p class="text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="btn-primary text-xs py-2 px-3.5 font-semibold self-end cursor-pointer">
                            Kirim Hasil ke Pemohon
                        </button>
                    </form>
                    </details>
                @endcan
            </div>

            @can('releaseToPool', $ticket)
                <div class="mb-6 flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-4 text-xs text-slate-500">
                    <span>Berhalangan menangani tiket ini?</span>
                    <button type="button" onclick="document.getElementById('modal-release-pool').classList.remove('hidden')" class="font-semibold text-slate-600 underline decoration-slate-300 underline-offset-4 hover:text-[#1E40AF] cursor-pointer">
                        Kembalikan ke pool
                    </button>
                </div>

                <!-- Modal Release to Pool -->
                <div id="modal-release-pool" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                    <div class="flex items-center justify-center min-h-screen px-4 py-6">
                        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" onclick="document.getElementById('modal-release-pool').classList.add('hidden')"></div>
                        <div class="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 z-10">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <h3 class="text-base font-bold text-slate-900">Kembalikan Tiket ke Pool Antrean</h3>
                                <button type="button" onclick="document.getElementById('modal-release-pool').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 cursor-pointer">&times;</button>
                            </div>
                            <form method="POST" action="{{ route('tickets.release-to-pool', $ticket) }}" class="mt-4 space-y-4">
                                @csrf
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Status tiket akan dikembalikan ke <strong>Siap Dikerjakan</strong> dan penugasan Anda dilepas agar teknisi IT lain atau Kabag IT dapat mengambil alih.
                                </p>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan Pengembalian ke Pool <span class="text-rose-500">*</span></label>
                                    <textarea name="reason" rows="3" required minlength="5" placeholder="Contoh: Sedang menangani insiden darurat jaringan cabang / membutuhkan keahlian tim database..."
                                        class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500"></textarea>
                                </div>
                                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                                    <button type="button" onclick="document.getElementById('modal-release-pool').classList.add('hidden')" class="btn-secondary text-xs py-2 px-3.5 cursor-pointer">Batal</button>
                                    <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs cursor-pointer">Lepas &amp; Kembalikan ke Pool</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        @endif

        <!-- Minimalist Timeline -->
        @if($timelineEvents->isEmpty())
            <p class="text-xs text-slate-400 py-4 text-center">Belum ada riwayat pengerjaan.</p>
        @else
            <div class="relative pl-5 space-y-4 before:content-[''] before:absolute before:top-2 before:bottom-2 before:left-1.5 before:w-px before:bg-slate-200">
                @foreach($timelineEvents as $event)
                    <div class="relative">
                        <span class="absolute -left-5 top-1.5 w-3 h-3 rounded-full ring-4 ring-white {{ $event['dot'] }}"></span>

                        <div class="text-xs">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-semibold text-slate-900">{{ $event['label'] }}</span>
                                    <span class="text-slate-300">&bull;</span>
                                    <span class="text-slate-500">{{ $event['actor'] }}</span>
                                </div>
                                <time class="text-slate-400 tabular-nums">{{ $event['timestamp']?->format('d M Y, H:i') }}</time>
                            </div>

                            @if(!empty($event['content']))
                                <p class="mt-1 text-slate-600 whitespace-pre-wrap leading-relaxed">{{ $event['content'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
