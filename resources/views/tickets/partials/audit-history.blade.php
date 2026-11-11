@php
    $actionLabels = [
        'CREATE_TICKET'                 => 'Tiket Dibuat',
        'SUBMIT_TICKET'                 => 'Tiket Diajukan',
        'SUPERVISOR_APPROVE'            => 'Disetujui Atasan',
        'SUPERVISOR_REJECT'             => 'Ditolak Atasan',
        'SUPERVISOR_REQUEST_REVISION'   => 'Revisi dari Atasan',
        'RESUBMIT_TICKET'               => 'Diajukan Ulang',
        'IT_HEAD_APPROVE_MINOR'         => 'Disetujui Kabag IT (Minor)',
        'IT_HEAD_APPROVE_MAJOR'         => 'Disetujui Kabag IT (Major)',
        'IT_HEAD_REJECT'                => 'Ditolak Kabag IT',
        'IT_HEAD_REQUEST_REVISION'      => 'Revisi dari Kabag IT',
        'KADIV_IT_APPROVE'              => 'Disetujui Kadiv IT',
        'KADIV_IT_REJECT'               => 'Ditolak Kadiv IT',
        'DIREKSI_APPROVE'               => 'Disetujui Direksi',
        'DIREKSI_REJECT'                => 'Ditolak Direksi',
        'SCHEDULE_MAJOR_MEETING'        => 'Rapat Dijadwalkan',
        'SUBMIT_MEETING_MINUTES'        => 'Notulensi Diserahkan',
        'APPROVE_MEETING_MINUTES'       => 'Notulensi Disetujui',
        'REVISE_MEETING_MINUTES'        => 'Revisi Notulensi',
        'IT_DEV_SELF_ASSIGN'            => 'Diambil Teknisi IT',
        'ADD_ACTIVITY_NOTE'             => 'Update Progres Teknisi',
        'IT_DEV_COMPLETE_WORK'          => 'Pekerjaan Selesai',
        'USER_REVIEW_ACCEPT'            => 'Diterima & Ditutup Pemohon',
        'USER_REVIEW_REQUEST_REVISION'  => 'Revisi dari Pemohon',
    ];
@endphp

<div class="p-4 sm:p-6">
    <div class="hidden sm:flex flex-wrap items-center justify-between gap-2 pb-4 mb-5 border-b border-slate-100">
        <h2 class="text-sm font-bold text-slate-900">Jejak Audit</h2>
        <span class="text-xs text-slate-400 tabular-nums">{{ $ticket->auditLogs->count() }} aktivitas</span>
    </div>

    @if($ticket->auditLogs->isEmpty())
        <p class="text-xs text-slate-400 py-4 text-center">Belum ada catatan jejak audit.</p>
    @else
        <div class="relative pl-5 space-y-4 before:content-[''] before:absolute before:top-2 before:bottom-2 before:left-1.5 before:w-px before:bg-slate-200">
            @foreach($ticket->auditLogs as $log)
                @php
                    $label = $actionLabels[$log->action] ?? ucwords(strtolower(str_replace('_', ' ', $log->action)));
                    $dotColor = str_contains($log->action, 'REJECT')
                        ? 'bg-rose-500'
                        : (str_contains($log->action, 'REVISION') || str_contains($log->action, 'REVISE')
                            ? 'bg-amber-500'
                            : (str_contains($log->action, 'APPROVE') || str_contains($log->action, 'ACCEPT') || str_contains($log->action, 'COMPLETE')
                                ? 'bg-emerald-500'
                                : 'bg-slate-400'));
                @endphp
                <div class="relative">
                    <span class="absolute -left-5 top-1.5 w-3 h-3 rounded-full ring-4 ring-white {{ $dotColor }}"></span>

                    <div class="text-xs">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <span class="font-semibold text-slate-900">{{ $label }}</span>
                                <span class="text-slate-300">&bull;</span>
                                <span class="text-slate-500">{{ $log->actor?->name ?? 'Sistem' }}</span>
                            </div>
                            <time class="text-slate-400 tabular-nums">{{ $log->created_at->format('d M Y, H:i') }}</time>
                        </div>

                        @if($log->remarks)
                            <p class="mt-1 text-slate-600 leading-relaxed">{{ $log->remarks }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
