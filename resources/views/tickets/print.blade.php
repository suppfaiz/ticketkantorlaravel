<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Berita Acara Tiket {{ $ticket->ticket_number }} - {{ config('app.name', 'Helpdesk IT') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-sheet { box-shadow: none !important; border: none !important; margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans antialiased py-6 px-4">
    @php
        $itHeadActor = $ticket->auditLogs->first(fn($log) => str_starts_with($log->action, 'IT_HEAD_'))?->actor;
        $kadivActor = $ticket->auditLogs->first(fn($log) => str_starts_with($log->action, 'KADIV_IT_'))?->actor;
        $direksiActor = $ticket->auditLogs->first(fn($log) => str_starts_with($log->action, 'DIREKSI_'))?->actor;

        $itHeadName = ($itHeadActor ?? \App\Models\User::where('role', 'it_head')->first())?->name ?? 'Kepala Bagian IT';
        $kadivName = ($kadivActor ?? \App\Models\User::where('role', 'kadiv_it')->first())?->name ?? 'Kadiv IT & Operasional';
        $direksiName = ($direksiActor ?? \App\Models\User::where('role', 'direksi')->first())?->name ?? 'Dewan Direksi';
        $isMajor = $ticket->classification === \App\Models\Ticket::CLASSIFICATION_MAJOR;
    @endphp

    <!-- Action Bar (Hidden when printing) -->
    <div class="no-print max-w-4xl mx-auto mb-4 bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-xs flex items-center justify-between gap-3">
        <div class="text-xs text-slate-600">
            Dokumen Resmi: <strong class="text-slate-900">{{ $ticket->ticket_number }}</strong>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="btn-primary text-xs py-2 px-4 inline-flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak / Simpan PDF</span>
            </button>
            <a href="{{ route('tickets.show', $ticket) }}" class="btn-secondary text-xs py-2 px-3">
                Kembali ke Detail
            </a>
        </div>
    </div>

    <!-- Printable Document Sheet -->
    <div class="print-sheet max-w-4xl mx-auto bg-white rounded-xl border border-slate-200 p-8 shadow-xs space-y-6">
        <!-- Kop Dokumen -->
        <div class="border-b-2 border-slate-900 pb-4 flex items-start justify-between gap-4">
            <div>
                <div class="text-xs font-bold uppercase tracking-widest text-[#1E40AF]">Sistem Layanan &amp; Tata Kelola TI Internal</div>
                <h1 class="text-lg font-extrabold text-slate-900 uppercase tracking-tight mt-0.5">
                    Formulir &amp; Berita Acara Permohonan Layanan TI
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">Dicetak pada: {{ now()->format('d/m/Y H:i') }} WIB</p>
            </div>
            <div class="text-right shrink-0">
                <div class="text-xs font-bold px-3 py-1 rounded border border-slate-900 inline-block tabular-nums">
                    {{ $ticket->ticket_number }}
                </div>
                <div class="text-xs font-semibold text-slate-700 mt-1">
                    Status: {{ $ticket->status }}
                </div>
            </div>
        </div>

        <!-- Ringkasan Informasi Tiket -->
        <div>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">I. Identitas Permohonan &amp; Target Timeline</h2>
            <table class="w-full text-xs border border-slate-300 border-collapse">
                <tbody>
                    <tr class="border-b border-slate-200">
                        <td class="py-2 px-3 bg-slate-50 font-semibold text-slate-600 w-1/4 border-r border-slate-200">Judul Permohonan</td>
                        <td class="py-2 px-3 font-bold text-slate-900" colspan="3">{{ $ticket->title }}</td>
                    </tr>
                    <tr class="border-b border-slate-200">
                        <td class="py-2 px-3 bg-slate-50 font-semibold text-slate-600 border-r border-slate-200">Nama Pemohon</td>
                        <td class="py-2 px-3 text-slate-800 border-r border-slate-200 w-1/4">{{ $ticket->requester->name }}</td>
                        <td class="py-2 px-3 bg-slate-50 font-semibold text-slate-600 border-r border-slate-200 w-1/4">Divisi / Unit Kerja</td>
                        <td class="py-2 px-3 text-slate-800">{{ $ticket->requester->departmentRecord?->name ?? $ticket->requester->department ?? '-' }}</td>
                    </tr>
                    <tr class="border-b border-slate-200">
                        <td class="py-2 px-3 bg-slate-50 font-semibold text-slate-600 border-r border-slate-200">Kategori Request</td>
                        <td class="py-2 px-3 text-slate-800 border-r border-slate-200">{{ $ticket->category }}</td>
                        <td class="py-2 px-3 bg-slate-50 font-semibold text-slate-600 border-r border-slate-200">Klasifikasi &amp; Prioritas</td>
                        <td class="py-2 px-3 text-slate-800">{{ $ticket->classification ?? 'Belum Diklasifikasi' }} / {{ $ticket->priority ?? 'Low' }}</td>
                    </tr>
                    <tr class="border-b border-slate-200">
                        <td class="py-2 px-3 bg-slate-50 font-semibold text-slate-600 border-r border-slate-200">Tanggal Pengajuan</td>
                        <td class="py-2 px-3 text-slate-800 border-r border-slate-200 tabular-nums">{{ ($ticket->submitted_at ?? $ticket->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="py-2 px-3 bg-slate-50 font-semibold text-slate-600 border-r border-slate-200">Target Timeline (Kabag IT)</td>
                        <td class="py-2 px-3 font-semibold text-slate-900 tabular-nums">
                            {{ $ticket->target_completion_at ? $ticket->target_completion_at->format('d/m/Y') : 'Belum ditetapkan' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2 px-3 bg-slate-50 font-semibold text-slate-600 border-r border-slate-200">Teknisi Pelaksana</td>
                        <td class="py-2 px-3 text-slate-800 border-r border-slate-200">{{ $ticket->assignedTo?->name ?? 'Belum ditugaskan' }}</td>
                        <td class="py-2 px-3 bg-slate-50 font-semibold text-slate-600 border-r border-slate-200">Waktu Selesai Kerja</td>
                        <td class="py-2 px-3 text-slate-800 tabular-nums">{{ $ticket->work_completed_at ? $ticket->work_completed_at->format('d/m/Y H:i') : '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Deskripsi Kebutuhan -->
        <div>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">II. Rincian Kebutuhan / Permasalahan</h2>
            <div class="border border-slate-300 rounded p-3 text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">{{ trim($ticket->description) }}</div>
        </div>

        <!-- Notulensi Rapat Koordinasi (Jika Major) -->
        @if($ticket->meetingMinutes)
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">III. Ringkasan Notulensi Rapat Koordinasi Major</h2>
                <div class="border border-slate-300 rounded p-3 text-xs space-y-1.5">
                    <div><span class="font-semibold text-slate-600">Tanggal Rapat:</span> {{ $ticket->meetingMinutes->meeting_date ? \Carbon\Carbon::parse($ticket->meetingMinutes->meeting_date)->format('d/m/Y H:i') : '-' }}</div>
                    <div><span class="font-semibold text-slate-600">Peserta Rapat:</span> {{ $ticket->meetingMinutes->attendees ?? $ticket->meetingMinutes->attendees_summary ?? '-' }}</div>
                    <div><span class="font-semibold text-slate-600">Hasil &amp; Kesepakatan:</span></div>
                    <div class="prose prose-xs max-w-none text-slate-800 pt-1 border-t border-slate-100">{!! \App\Support\SafeMinutesHtml::render($ticket->meetingMinutes->notes ?? $ticket->meetingMinutes->deliverables ?? '-') !!}</div>
                </div>
            </div>
        @endif

        <!-- Riwayat Pengerjaan Teknis IT -->
        @if($ticket->activityLogs->isNotEmpty())
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                    {{ $ticket->meetingMinutes ? 'IV.' : 'III.' }} Catatan Pelaksanaan &amp; Penyelesaian Teknis IT
                </h2>
                <table class="w-full text-xs border border-slate-300 border-collapse">
                    <thead class="bg-slate-50">
                        <tr class="border-b border-slate-300">
                            <th class="py-1.5 px-3 text-left font-semibold text-slate-600 border-r border-slate-200 w-36">Waktu</th>
                            <th class="py-1.5 px-3 text-left font-semibold text-slate-600 border-r border-slate-200 w-44">Petugas</th>
                            <th class="py-1.5 px-3 text-left font-semibold text-slate-600">Uraian Pengerjaan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($ticket->activityLogs as $log)
                            <tr>
                                <td class="py-1.5 px-3 tabular-nums text-slate-600 border-r border-slate-200">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="py-1.5 px-3 text-slate-800 border-r border-slate-200">{{ $log->user?->name ?? '-' }}</td>
                                <td class="py-1.5 px-3 text-slate-800">{{ $log->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Lembar Pengesahan / Jejak Persetujuan Elektronik -->
        <div class="pt-2">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Lembar Pengesahan &amp; Otorisasi Elektronik (LOA)</h2>
            <div class="grid grid-cols-2 {{ $isMajor ? 'sm:grid-cols-3' : 'sm:grid-cols-4' }} gap-3 text-xs">
                <div class="border border-slate-300 rounded p-3 text-center">
                    <div class="text-[11px] text-slate-500 font-semibold uppercase">Pemohon</div>
                    <div class="my-3 py-1 rounded bg-emerald-50 text-emerald-700 font-bold text-[10px] uppercase">Diajukan Elektronik</div>
                    <div class="font-bold text-slate-900 truncate">{{ $ticket->requester->name }}</div>
                    <div class="text-[11px] text-slate-500 tabular-nums">{{ ($ticket->submitted_at ?? $ticket->created_at)->format('d/m/Y H:i') }}</div>
                </div>

                <div class="border border-slate-300 rounded p-3 text-center">
                    <div class="text-[11px] text-slate-500 font-semibold uppercase">Atasan Langsung</div>
                    @if($ticket->approved_by_supervisor_at)
                        <div class="my-3 py-1 rounded bg-emerald-50 text-emerald-700 font-bold text-[10px] uppercase">Disetujui Elektronik</div>
                    @else
                        <div class="my-3 py-1 rounded bg-slate-100 text-slate-500 font-semibold text-[10px] uppercase">Menunggu / Belum</div>
                    @endif
                    <div class="font-bold text-slate-900 truncate">{{ $ticket->supervisor?->name ?? '-' }}</div>
                    <div class="text-[11px] text-slate-500 tabular-nums">{{ $ticket->approved_by_supervisor_at ? $ticket->approved_by_supervisor_at->format('d/m/Y H:i') : '-' }}</div>
                </div>

                <div class="border border-slate-300 rounded p-3 text-center">
                    <div class="text-[11px] text-slate-500 font-semibold uppercase">Kepala Bagian IT</div>
                    @if($ticket->approved_by_it_head_at)
                        <div class="my-3 py-1 rounded bg-emerald-50 text-emerald-700 font-bold text-[10px] uppercase">Disetujui Elektronik</div>
                    @else
                        <div class="my-3 py-1 rounded bg-slate-100 text-slate-500 font-semibold text-[10px] uppercase">Menunggu / Belum</div>
                    @endif
                    <div class="font-bold text-slate-900 truncate">{{ $itHeadName }}</div>
                    <div class="text-[11px] text-slate-500 tabular-nums">{{ $ticket->approved_by_it_head_at ? $ticket->approved_by_it_head_at->format('d/m/Y H:i') : '-' }}</div>
                </div>

                @if($isMajor)
                    <div class="border border-slate-300 rounded p-3 text-center">
                        <div class="text-[11px] text-slate-500 font-semibold uppercase">Kadiv IT &amp; Ops</div>
                        @if($ticket->approved_by_kadiv_at)
                            <div class="my-3 py-1 rounded bg-emerald-50 text-emerald-700 font-bold text-[10px] uppercase">Disetujui LOA</div>
                        @else
                            <div class="my-3 py-1 rounded bg-slate-100 text-slate-500 font-semibold text-[10px] uppercase">Menunggu / Belum</div>
                        @endif
                        <div class="font-bold text-slate-900 truncate">{{ $kadivName }}</div>
                        <div class="text-[11px] text-slate-500 tabular-nums">{{ $ticket->approved_by_kadiv_at ? $ticket->approved_by_kadiv_at->format('d/m/Y H:i') : '-' }}</div>
                    </div>

                    <div class="border border-slate-300 rounded p-3 text-center">
                        <div class="text-[11px] text-slate-500 font-semibold uppercase">Dewan Direksi</div>
                        @if($ticket->approved_by_direksi_at)
                            <div class="my-3 py-1 rounded bg-emerald-50 text-emerald-700 font-bold text-[10px] uppercase">Disetujui LOA</div>
                        @else
                            <div class="my-3 py-1 rounded bg-slate-100 text-slate-500 font-semibold text-[10px] uppercase">Menunggu / Belum</div>
                        @endif
                        <div class="font-bold text-slate-900 truncate">{{ $direksiName }}</div>
                        <div class="text-[11px] text-slate-500 tabular-nums">{{ $ticket->approved_by_direksi_at ? $ticket->approved_by_direksi_at->format('d/m/Y H:i') : '-' }}</div>
                    </div>
                @endif

                <div class="border border-slate-300 rounded p-3 text-center">
                    <div class="text-[11px] text-slate-500 font-semibold uppercase">Pelaksana / Penutupan</div>
                    @if($ticket->closed_at || $ticket->work_completed_at)
                        <div class="my-3 py-1 rounded bg-emerald-50 text-emerald-700 font-bold text-[10px] uppercase">Selesai Dikerjakan</div>
                    @else
                        <div class="my-3 py-1 rounded bg-slate-100 text-slate-500 font-semibold text-[10px] uppercase">Dalam Proses</div>
                    @endif
                    <div class="font-bold text-slate-900 truncate">{{ $ticket->assignedTo?->name ?? 'Tim Teknis IT' }}</div>
                    <div class="text-[11px] text-slate-500 tabular-nums">{{ ($ticket->closed_at ?? $ticket->work_completed_at)?->format('d/m/Y H:i') ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
