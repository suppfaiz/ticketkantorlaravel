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

        <!-- Developer Controls (Clean & Compact) -->
        @if(((int) auth()->id() === (int) $ticket->assigned_to || auth()->user()->isAdmin()) && in_array($ticket->status, [\App\Models\Ticket::STATUS_IN_PROGRESS, \App\Models\Ticket::STATUS_REVISION_WORK]))
            <div class="mb-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Catat Progres -->
                <form method="POST" action="{{ route('tickets.activities.store', $ticket) }}" enctype="multipart/form-data" class="p-4 bg-slate-50 border border-slate-200 rounded-xl flex flex-col justify-between gap-3">
                    @csrf
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-semibold text-slate-800">Tambah Catatan Progres</span>
                            <select name="activity_type" class="text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white py-1 pl-2.5 pr-7">
                                <option value="progress_note">Update Progres</option>
                                <option value="code_commit">Perbaikan Sistem</option>
                                <option value="test_result">Hasil Pengujian</option>
                                <option value="deployment">Rilis ke Server</option>
                            </select>
                        </div>
                        <textarea name="content" rows="2" required placeholder="Tulis progres pengerjaan..."
                            class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">{{ old('content') }}</textarea>
                        <div>
                            <label class="block text-[11px] text-slate-500 mb-1">Lampiran Bukti / Screenshot (Opsional, maks 5MB)</label>
                            <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" class="block w-full text-[11px] text-slate-500 file:mr-2.5 file:py-1 file:px-2.5 file:rounded-md file:border file:border-slate-200 file:text-[11px] file:font-medium file:bg-white file:text-slate-700 hover:file:bg-slate-50 cursor-pointer">
                        </div>
                        @error('content')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        @error('attachment')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn-secondary text-xs py-2 px-3 self-end cursor-pointer">
                        Simpan Progres
                    </button>
                </form>

                <!-- Selesaikan Pekerjaan -->
                @can('completeWork', $ticket)
                    <form method="POST" action="{{ route('tickets.complete-work', $ticket) }}" enctype="multipart/form-data" class="p-4 bg-slate-50 border border-slate-200 rounded-xl flex flex-col justify-between gap-3">
                        @csrf
                        <div class="space-y-2.5">
                            <span class="text-xs font-semibold text-slate-800 block">Selesaikan Pekerjaan</span>
                            <textarea name="completion_notes" rows="2" placeholder="Catatan penyelesaian untuk pemohon (opsional)..."
                                class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white">{{ old('completion_notes') }}</textarea>
                            <div>
                                <label class="block text-[11px] text-slate-500 mb-1">Bukti Hasil Kerja / Screenshot (Opsional, maks 5MB)</label>
                                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" class="block w-full text-[11px] text-slate-500 file:mr-2.5 file:py-1 file:px-2.5 file:rounded-md file:border file:border-slate-200 file:text-[11px] file:font-medium file:bg-white file:text-slate-700 hover:file:bg-slate-50 cursor-pointer">
                            </div>
                            @error('completion_notes')
                                <p class="text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="btn-primary text-xs py-2 px-3.5 font-semibold self-end cursor-pointer">
                            Pekerjaan Selesai &amp; Kirim ke Pemohon
                        </button>
                    </form>
                @endcan
            </div>

            @can('releaseToPool', $ticket)
                <div class="mb-6 px-4 py-3 bg-amber-50/60 border border-amber-200/80 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                    <div class="text-xs text-slate-600">
                        <span class="font-semibold text-slate-800">Berhalangan melanjutkan pengerjaan tiket ini?</span>
                        Anda dapat mengembalikan tiket ke Pool Antrean agar dapat diambil oleh rekan teknisi IT lainnya.
                    </div>
                    <button type="button" onclick="document.getElementById('modal-release-pool').classList.remove('hidden')" class="px-3 py-1.5 rounded-lg border border-amber-300 bg-white hover:bg-amber-50 text-amber-800 font-semibold text-xs whitespace-nowrap cursor-pointer transition-colors self-start sm:self-auto">
                        Kembalikan ke Pool Antrean
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
