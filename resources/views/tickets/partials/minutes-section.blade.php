@if($ticket->classification === \App\Models\Ticket::CLASSIFICATION_MAJOR || $ticket->meetingMinutes)
    <div class="p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-2 pb-4 mb-5 border-b border-slate-100">
            <h2 class="text-sm font-bold text-slate-900">Notulensi Rapat Koordinasi</h2>
            @if($ticket->meetingMinutes)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                    {{ $ticket->meetingMinutes->status }}
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                    Belum Ada Notulensi
                </span>
            @endif
        </div>

        @if(! $ticket->meetingMinutes)
            <div class="py-6 text-center space-y-3">
                <p class="text-xs text-slate-400">
                    Belum ada catatan rapat koordinasi atau notulensi pada tiket ini.
                </p>
                @include('tickets.partials.minutes_modal')
            </div>
        @else
            <!-- Display Existing Meeting Details & Minutes -->
            <div class="space-y-4 text-xs">
                <div class="pb-3 border-b border-slate-100">
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tanggal &amp; Waktu Rapat</div>
                    <div class="font-bold text-slate-800 tabular-nums mt-0.5">
                        {{ $ticket->meetingMinutes->meeting_date ? $ticket->meetingMinutes->meeting_date->format('d M Y, H:i') . ' WIB' : '-' }}
                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Daftar Peserta Rapat</div>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-slate-800 leading-relaxed">{{ trim($ticket->meetingMinutes->attendees ?? $ticket->meetingMinutes->attendees_summary ?? '-') }}</div>
                </div>

                @if($ticket->meetingMinutes->scope_summary)
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Agenda / Pengantar Rapat</div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-slate-800 leading-relaxed">{{ trim($ticket->meetingMinutes->scope_summary) }}</div>
                    </div>
                @endif

                <div>
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Poin Kesepakatan &amp; Ruang Lingkup (Notulensi)</div>
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 text-slate-800 rich-text-content max-w-none leading-relaxed">
                        {!! \App\Support\SafeMinutesHtml::render($ticket->meetingMinutes->notes ?? '<p>Menunggu input notulensi hasil rapat dari pemohon.</p>') !!}
                    </div>
                </div>

                @include('tickets.partials.minutes_modal')

                @if($ticket->meetingMinutes->deliverables && $ticket->meetingMinutes->deliverables !== $ticket->meetingMinutes->notes)
                    <div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Deliverables &amp; Luaran Pekerjaan</div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-slate-800 whitespace-pre-wrap leading-relaxed">{{ trim($ticket->meetingMinutes->deliverables) }}</div>
                    </div>
                @endif

                <!-- Attachment Download if Available -->
                @if($ticket->meetingMinutes->attachment_path)
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <span class="font-semibold text-slate-800">Dokumen Berita Acara / Notulensi Bertanda Tangan</span>
                        </div>
                        <a href="{{ route('tickets.meeting-minutes.download', $ticket) }}" class="btn-secondary text-xs py-1.5 px-3 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Unduh Berkas</span>
                        </a>
                    </div>
                @endif

                @if($ticket->meetingMinutes->isApproved())
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-slate-800 flex items-center justify-between">
                        <div>
                            <strong>Disetujui oleh:</strong> {{ $ticket->meetingMinutes->approver?->name ?? 'Kepala Bagian IT' }} pada {{ $ticket->meetingMinutes->approved_at?->format('d M Y, H:i') }}
                            @if($ticket->meetingMinutes->approval_notes)
                                <div class="mt-1 italic text-slate-600">&ldquo;{{ $ticket->meetingMinutes->approval_notes }}&rdquo;</div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endif
