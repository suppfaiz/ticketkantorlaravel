<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2 sm:gap-4">
            <div class="min-w-0">
                <div class="hidden sm:flex items-center gap-3 mb-1">
                    <span class="tabular-nums text-xs font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 border border-slate-200">
                        {{ $ticket->ticket_number }}
                    </span>
                    <x-ticket-status-badge :status="$ticket->status" />
                </div>
                <h1 class="text-base sm:text-2xl font-bold text-slate-900 tracking-tight truncate">Revisi / Edit Tiket</h1>
            </div>
            <div class="shrink-0">
                <a href="{{ route('tickets.show', $ticket) }}" class="btn-secondary text-xs py-1.5 px-3">
                    &larr; Batal
                </a>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Main Edit Form Area (8 Cols) -->
        <div class="lg:col-span-8 space-y-6">
            <!-- Revision Notice Callout if revising -->
            @if(in_array($ticket->status, [\App\Models\Ticket::STATUS_REVISION_REQUESTER, \App\Models\Ticket::STATUS_REVISION_BY_IT_HEAD]))
                @php
                    $latestRevisionLog = $ticket->auditLogs()
                        ->whereIn('action', ['SUPERVISOR_REQUEST_REVISION', 'IT_HEAD_REQUEST_REVISION'])
                        ->latest()
                        ->first();
                @endphp
                <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-4 text-xs sm:text-sm text-amber-900 shadow-xs">
                    <div class="font-bold flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Catatan Revisi dari {{ $ticket->status === \App\Models\Ticket::STATUS_REVISION_REQUESTER ? 'Atasan Langsung' : 'Kepala Bagian IT' }}:</span>
                    </div>
                    <div class="mt-2 text-slate-800 whitespace-pre-wrap bg-white/90 p-3 rounded-lg border border-amber-200 text-xs leading-relaxed font-sans">{{ trim($latestRevisionLog?->remarks ?? 'Harap lengkapi dan perjelas detail permohonan tiket Anda.') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 text-xs sm:text-sm text-rose-800 shadow-xs">
                    <div class="font-bold mb-1.5 flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Terdapat kesalahan pada isian formulir:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-1 ml-6 text-rose-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white rounded-xl border border-slate-200 p-6 sm:p-7 shadow-xs">
                <form method="POST" action="{{ route('tickets.update', $ticket) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Judul Permohonan <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="title" id="title" value="{{ old('title', $ticket->title) }}" required
                                class="w-full text-xs sm:text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 bg-white shadow-xs">
                        </div>

                        <!-- Category & Priority -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="category" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Kategori Permohonan <span class="text-rose-500">*</span>
                                </label>
                                <select name="category" id="category" required
                                    class="w-full text-xs sm:text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 bg-white shadow-xs">
                                    @foreach(($categories ?? \App\Models\TicketCategory::activeNames($ticket->category)) as $cat)
                                        <option value="{{ $cat }}" {{ old('category', $ticket->category) === $cat ? 'selected' : '' }}>
                                            {{ $cat }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="priority" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Tingkat Urgensi
                                </label>
                                <select name="priority" id="priority"
                                    class="w-full text-xs sm:text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 bg-white shadow-xs">
                                    @foreach(['Low', 'Medium', 'High', 'Urgent'] as $prio)
                                        <option value="{{ $prio }}" {{ old('priority', $ticket->priority) === $prio ? 'selected' : '' }}>
                                            {{ $prio }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Deskripsi Kebutuhan / Masalah <span class="text-rose-500">*</span>
                            </label>
                            <textarea name="description" id="description" rows="6" required
                                class="w-full text-xs sm:text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 bg-white shadow-xs leading-relaxed">{{ old('description', $ticket->description) }}</textarea>
                        </div>

                        <!-- Existing Attachments -->
                        @if($ticket->attachments->isNotEmpty())
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Lampiran yang Sudah Diunggah</label>
                                <ul class="divide-y divide-slate-100 border border-slate-200 rounded-xl bg-slate-50/50 p-2">
                                    @foreach($ticket->attachments as $att)
                                        <li class="py-2 px-3 flex items-center justify-between gap-3 text-xs">
                                            <div class="flex items-center space-x-2 truncate min-w-0">
                                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                </svg>
                                                <span class="font-medium text-slate-800 truncate">{{ $att->file_name }}</span>
                                                <span class="text-slate-400 tabular-nums text-xs shrink-0">({{ number_format($att->file_size_bytes / 1024, 1) }} KB)</span>
                                            </div>
                                            <div class="flex items-center gap-3 shrink-0">
                                                <a href="{{ route('tickets.attachments.download', [$ticket, $att]) }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    Unduh
                                                </a>
                                                <button type="button"
                                                    onclick="confirmDeleteAttachment('{{ route('tickets.attachments.destroy', [$ticket, $att]) }}', '{{ addslashes($att->file_name) }}')"
                                                    class="text-rose-600 hover:text-rose-800 font-semibold hover:underline cursor-pointer">
                                                    Hapus
                                                </button>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Add More Attachments with Dropzone -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Tambah Lampiran Baru (Opsional)
                            </label>
                            <x-file-dropzone name="attachments[]" id="attachments" />
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-end">
                        <button type="submit" class="btn-primary text-xs">
                            {{ $ticket->status === \App\Models\Ticket::STATUS_DRAFT ? 'Simpan Perubahan & Ajukan Tiket' : 'Simpan & Ajukan Ulang' }} &rarr;
                        </button>
                    </div>
                </form>

                <form id="deleteAttachmentForm" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
                <script>
                    function confirmDeleteAttachment(actionUrl, fileName) {
                        if (confirm('Hapus berkas lampiran "' + fileName + '" dari tiket ini?')) {
                            var form = document.getElementById('deleteAttachmentForm');
                            if (form) {
                                form.action = actionUrl;
                                form.submit();
                            }
                        }
                    }
                </script>
            </div>
        </div>

        <!-- Right Side Context Panel (4 Cols) -->
        <div class="lg:col-span-4 space-y-5">
            <!-- Card: Atasan Peninjau -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 pb-2 border-b border-slate-100">Atasan Peninjau</h3>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-blue-50 text-[#1E40AF] border border-blue-200/70 flex items-center justify-center text-xs font-bold shrink-0">
                        {{ strtoupper(substr($ticket->supervisor?->name ?? 'A', 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-slate-900 text-sm truncate">{{ $ticket->supervisor?->name ?? 'Belum ditentukan' }}</div>
                        @if($ticket->supervisor?->departmentRecord || $ticket->supervisor?->department)
                            <div class="text-slate-500 text-xs truncate">{{ $ticket->supervisor->departmentRecord?->name ?? $ticket->supervisor->department }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
