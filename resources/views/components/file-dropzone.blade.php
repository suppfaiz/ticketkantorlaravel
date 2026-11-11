@props([
    'name' => 'attachments[]',
    'id' => 'attachments',
    'multiple' => true,
    'accept' => '.pdf,.docx,.xlsx,.png,.jpg,.jpeg',
    'maxSizeMb' => 10,
])

<div
    x-data="{
        isDragging: false,
        fileList: [],
        formatSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },
        updateFiles(e) {
            const files = e.target.files;
            this.fileList = [];
            for (let i = 0; i < files.length; i++) {
                this.fileList.push({
                    name: files[i].name,
                    size: this.formatSize(files[i].size),
                });
            }
        },
        clearFiles() {
            this.fileList = [];
            document.getElementById('{{ $id }}').value = '';
        }
    }"
    class="space-y-3"
>
    <!-- Dropzone Box -->
    <div
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="isDragging = false; document.getElementById('{{ $id }}').files = $event.dataTransfer.files; updateFiles({ target: { files: $event.dataTransfer.files } })"
        :class="{ 'border-blue-500 bg-blue-50/40': isDragging, 'border-slate-300 bg-slate-50/50 hover:bg-slate-50 hover:border-slate-400': !isDragging }"
        class="border-2 border-dashed rounded-xl p-6 text-center transition-all duration-150 cursor-pointer relative"
        onclick="document.getElementById('{{ $id }}').click()"
    >
        <input
            type="file"
            name="{{ $name }}"
            id="{{ $id }}"
            {{ $multiple ? 'multiple' : '' }}
            accept="{{ $accept }}"
            class="hidden"
            @change="updateFiles($event)"
        >

        <div class="flex flex-col items-center pointer-events-none">
            <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>
            <p class="text-xs sm:text-sm font-semibold text-slate-800">
                <span class="text-blue-600 hover:underline">Klik untuk memilih berkas</span> atau seret dan lepas di sini
            </p>
            <p class="text-xs text-slate-500 mt-1">
                Format yang didukung: PDF, DOCX, XLSX, PNG, JPG (Maks. {{ $maxSizeMb }}MB per berkas)
            </p>
        </div>
    </div>

    <!-- Selected Files Preview List -->
    <template x-if="fileList.length > 0">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between mb-2 pb-2 border-b border-slate-100">
                <span class="text-xs font-semibold text-slate-700" x-text="fileList.length + ' berkas dipilih:'"></span>
                <button type="button" @click="clearFiles()" class="text-xs text-red-600 hover:text-red-700 hover:underline font-medium">
                    Hapus Semua
                </button>
            </div>
            <ul class="divide-y divide-slate-100 text-xs">
                <template x-for="(file, index) in fileList" :key="index">
                    <li class="py-2 flex items-center justify-between">
                        <div class="flex items-center gap-2 truncate">
                            <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="font-medium text-slate-800 truncate" x-text="file.name"></span>
                        </div>
                        <span class="text-slate-400 tabular-nums text-xs shrink-0 ml-3" x-text="file.size"></span>
                    </li>
                </template>
            </ul>
        </div>
    </template>
</div>
