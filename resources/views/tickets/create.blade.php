<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2 sm:gap-4">
            <h1 class="text-base sm:text-xl font-bold text-slate-900 tracking-tight truncate">Buat Tiket Permohonan IT</h1>
            <div class="shrink-0">
                <a href="{{ route('tickets.index') }}" class="btn-secondary text-xs py-1.5 px-3">
                    &larr; Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Main Form Area (8 Cols) -->
        <div class="lg:col-span-8 space-y-6">
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

            <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-7 shadow-xs">
                @if($supervisor)
                    <!-- Compact Mobile Supervisor Info Bar (Hidden on Desktop lg:) -->
                    <div class="lg:hidden mb-4 pb-3 border-b border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Atasan Peninjau</span>
                        <span class="font-semibold text-slate-800 truncate max-w-[200px]">{{ $supervisor->name }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" id="ticketForm">
                    @csrf
                    <input type="hidden" name="is_draft" id="isDraftInput" value="0">

                    <div class="space-y-6">
                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Judul Permohonan <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" required
                                placeholder="Contoh: Penggantian Monitor atau Penambahan Fitur Ekspor"
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
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach(($categories ?? \App\Models\TicketCategory::activeNames()) as $cat)
                                        <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="priority" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Tingkat Urgensi
                                </label>
                                <select name="priority" id="priority"
                                    class="w-full text-xs sm:text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 bg-white shadow-xs">
                                    <option value="Low" {{ old('priority', 'Low') === 'Low' ? 'selected' : '' }}>Low (Rendah)</option>
                                    <option value="Medium" {{ old('priority') === 'Medium' ? 'selected' : '' }}>Medium (Sedang)</option>
                                    <option value="High" {{ old('priority') === 'High' ? 'selected' : '' }}>High (Tinggi)</option>
                                    <option value="Urgent" {{ old('priority') === 'Urgent' ? 'selected' : '' }}>Urgent (Kritis)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Deskripsi Kebutuhan <span class="text-rose-500">*</span>
                            </label>
                            <textarea name="description" id="description" rows="6" required
                                placeholder="Tuliskan rincian kebutuhan atau kendala yang dialami (minimal 15 karakter)..."
                                class="w-full text-xs sm:text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 bg-white shadow-xs leading-relaxed">{{ old('description') }}</textarea>
                        </div>

                        <!-- Modern File Dropzone (FR-005, US3) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                Lampiran Berkas Pendukung (Opsional)
                            </label>
                            <x-file-dropzone name="attachments[]" id="attachments" />
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                        <button type="button" onclick="submitAsDraft()" class="w-full sm:w-auto btn-secondary text-xs">
                            Simpan sebagai Draf
                        </button>
                        <button type="submit" class="w-full sm:w-auto btn-primary text-xs">
                            Ajukan ke Atasan Langsung &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side Context Panel (4 Cols on Desktop Only) -->
        <div class="hidden lg:block lg:col-span-4 space-y-5">
            <!-- Card: Atasan Peninjau -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 pb-2 border-b border-slate-100">Atasan Peninjau</h3>
                @if($supervisor)
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-blue-50 text-[#1E40AF] border border-blue-200/70 flex items-center justify-center text-xs font-bold shrink-0">
                            {{ strtoupper(substr($supervisor->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-slate-900 text-sm truncate">{{ $supervisor->name }}</div>
                            <div class="text-slate-500 text-xs truncate">{{ $supervisor->departmentRecord?->name ?? ($supervisor->department ?? 'Divisi Operasional & Umum') }}</div>
                        </div>
                    </div>
                @else
                    <div class="text-xs text-slate-500">
                        Atasan langsung ditetapkan otomatis oleh sistem.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function submitAsDraft() {
            document.getElementById('isDraftInput').value = '1';
            document.getElementById('ticketForm').submit();
        }
    </script>
</x-app-layout>
