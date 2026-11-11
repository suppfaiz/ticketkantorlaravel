@php
    $roleLabels = [
        'requester'    => 'Pemohon',
        'supervisor'   => 'Atasan',
        'it_head'      => 'Kabag IT',
        'kadiv_it'     => 'Kadiv IT',
        'direksi'      => 'Direksi',
        'it_developer' => 'IT Developer',
        'admin'        => 'Administrator',
    ];

    $initialMessages = $ticket->discussions->map(function ($post) use ($roleLabels) {
        $senderName = $post->sender?->name ?? 'Pengguna';
        return [
            'id' => $post->id,
            'sender_name' => $senderName,
            'sender_initial' => strtoupper(substr($senderName, 0, 1)),
            'sender_role' => $roleLabels[$post->sender?->role ?? ''] ?? 'Pengguna',
            'message' => $post->message,
            'created_at_date' => $post->created_at?->format('d M Y, H:i') ?? '',
        ];
    })->values();
@endphp

<div
    x-data="{
        messages: @js($initialMessages),
        newMessage: '',
        submitting: false,
        errorMsg: '',
        pollTimer: null,
        isPolling: false,
        pollUrl: '{{ route('chat.index', ['ticket_id' => $ticket->id]) }}',
        postUrl: '{{ route('tickets.discussions.store', $ticket) }}',

        isTabAndPageActive() {
            return document.visibilityState === 'visible' && this.activeTab === 'discussion';
        },

        async fetchMessages() {
            if (!this.isTabAndPageActive() || this.isPolling) return;
            this.isPolling = true;
            try {
                const res = await fetch(this.pollUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                if (!res.ok) return;
                const data = await res.json();
                if (Array.isArray(data.messages)) {
                    this.messages = data.messages.map(m => ({
                        id: m.id,
                        sender_name: m.sender_name || 'Pengguna',
                        sender_initial: (m.sender_name || 'P').charAt(0).toUpperCase(),
                        sender_role: m.sender_role || 'Pengguna',
                        message: m.message || '',
                        created_at_date: m.created_at_date || m.created_at_formatted || ''
                    }));
                    window.dispatchEvent(new CustomEvent('discussion-count-updated', {
                        detail: { count: this.messages.length }
                    }));
                }
            } catch (e) {
                // Silent network error during background poll
            } finally {
                this.isPolling = false;
            }
        },

        startPolling() {
            if (this.pollTimer) return;
            this.fetchMessages();
            this.pollTimer = setInterval(() => {
                if (this.isTabAndPageActive()) {
                    this.fetchMessages();
                } else {
                    this.stopPolling();
                }
            }, 5000);
        },

        stopPolling() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        syncPollingState() {
            if (this.isTabAndPageActive()) {
                this.startPolling();
            } else {
                this.stopPolling();
            }
        },

        async submitComment() {
            const text = (this.newMessage || '').trim();
            if (!text || this.submitting) return;
            this.submitting = true;
            this.errorMsg = '';
            try {
                const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
                const formData = new FormData();
                formData.append('message', text);
                if (this.$refs.discussionFile && this.$refs.discussionFile.files[0]) {
                    formData.append('attachment', this.$refs.discussionFile.files[0]);
                }

                const res = await fetch(this.postUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf
                    },
                    credentials: 'same-origin',
                    body: formData
                });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    this.errorMsg = err.message || 'Gagal mengirim komentar.';
                    return;
                }
                const data = await res.json();
                if (data.message) {
                    const m = data.message;
                    if (!this.messages.some(item => item.id === m.id)) {
                        this.messages.push({
                            id: m.id,
                            sender_name: m.sender_name || 'Pengguna',
                            sender_initial: (m.sender_name || 'P').charAt(0).toUpperCase(),
                            sender_role: m.sender_role || 'Pengguna',
                            message: m.message || '',
                            created_at_date: m.created_at_date || m.created_at_formatted || ''
                        });
                    }
                    this.newMessage = '';
                    if (this.$refs.discussionFile) {
                        this.$refs.discussionFile.value = '';
                    }
                    window.dispatchEvent(new CustomEvent('discussion-count-updated', {
                        detail: { count: data.count ?? this.messages.length }
                    }));
                }
            } catch (e) {
                this.errorMsg = 'Terjadi gangguan jaringan saat mengirim komentar.';
            } finally {
                this.submitting = false;
            }
        },

        init() {
            this.syncPollingState();

            // Event Listener 1: Browser Tab Visibility (Page Visibility API)
            document.addEventListener('visibilitychange', () => {
                this.syncPollingState();
            });

            // Event Listener 2: Window Focus / Blur
            window.addEventListener('focus', () => {
                this.syncPollingState();
            });

            // Event Listener 3: Sub-Tab Navigation Change
            window.addEventListener('ticket-tab-changed', () => {
                this.syncPollingState();
            });
        }
    }"
    class="p-4 sm:p-6"
>
    <!-- Simple Header (Desktop Only, Mobile already shows Tab Title & Count) -->
    <div class="hidden sm:flex items-center justify-between gap-2 pb-4 mb-5 border-b border-slate-100">
        <h2 class="text-sm font-bold text-slate-900">Forum Diskusi Tiket</h2>
        <span class="text-xs text-slate-400"><span x-text="messages.length">{{ $ticket->discussions->count() }}</span> komentar</span>
    </div>

    <!-- Forum Thread -->
    <template x-if="messages.length === 0">
        <p class="text-xs text-slate-400 py-6 text-center">Belum ada diskusi pada tiket ini. Tulis komentar di bawah untuk memulai diskusi.</p>
    </template>

    <div x-show="messages.length > 0" class="divide-y divide-slate-100 mb-6">
        <template x-for="post in messages" :key="post.id">
            <div class="py-3.5 first:pt-0 last:pb-0 flex items-start gap-3">
                <div
                    class="w-7 h-7 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center text-xs font-bold shrink-0 mt-0.5"
                    x-text="post.sender_initial"
                ></div>
                <div class="flex-1 min-w-0 text-xs">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <span class="font-semibold text-slate-900" x-text="post.sender_name"></span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="text-slate-500" x-text="post.sender_role"></span>
                        </div>
                        <time class="text-slate-400 tabular-nums" x-text="post.created_at_date"></time>
                    </div>
                    <p class="mt-1 text-slate-700 whitespace-pre-wrap leading-relaxed" x-text="post.message"></p>
                </div>
            </div>
        </template>
    </div>

    <!-- Reply Form -->
    <form method="POST" action="{{ route('tickets.discussions.store', $ticket) }}" enctype="multipart/form-data" @submit.prevent="submitComment" class="pt-4 border-t border-slate-100 space-y-3">
        @csrf
        <div>
            <textarea
                name="message"
                x-model="newMessage"
                rows="3"
                required
                maxlength="2000"
                placeholder="Tulis komentar atau tanggapan pada forum tiket ini..."
                class="w-full text-xs rounded-lg border-slate-300 focus:border-[#1E40AF] focus:ring-[#1E40AF] bg-white"
            >{{ old('message') }}</textarea>
            <template x-if="errorMsg">
                <p class="mt-1 text-xs text-rose-600" x-text="errorMsg"></p>
            </template>
            @error('message')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <input
                type="file"
                name="attachment"
                x-ref="discussionFile"
                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx"
                class="block text-[11px] text-slate-500 file:mr-2.5 file:py-1 file:px-2.5 file:rounded-md file:border file:border-slate-200 file:text-[11px] file:font-medium file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100 cursor-pointer"
            >
            <button type="submit" :disabled="submitting" class="btn-primary text-xs py-2 px-4 cursor-pointer disabled:opacity-60 self-end sm:self-auto shrink-0">
                <span x-text="submitting ? 'Mengirim...' : 'Kirim Komentar'">Kirim Komentar</span>
            </button>
        </div>
    </form>
</div>
