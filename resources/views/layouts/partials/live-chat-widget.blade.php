@if(auth()->check() && isset($ticket))
<div
    x-data="{
        ticketId: {{ (int) $ticket->id }},
        ticketNumber: @js($ticket->ticket_number),
        ticketTitle: @js($ticket->title),
        isOpen: localStorage.getItem('nala_ticket_chat_open_{{ $ticket->id }}') === 'true',
        wsConnected: false,
        ws: null,
        currentUserId: {{ (int) auth()->id() }},
        currentUserName: @js(auth()->user()->name),
        currentUserRole: @js(match(auth()->user()->role) {
            'requester' => 'Pemohon',
            'supervisor' => 'Atasan',
            'it_head' => 'Kabag IT',
            'kadiv_it' => 'Kadiv IT',
            'direksi' => 'Direksi',
            'it_developer' => 'IT Developer',
            'admin' => 'Administrator',
            default => 'Pengguna',
        }),
        messages: [],
        unreadCount: 0,
        draft: '',
        sending: false,
        loading: false,
        activeTypingName: '',
        activeTypingRole: '',
        typingTimeout: null,
        lastTypingSentAt: 0,

        init() {
            this.fetchMessages();
            this.connectWebSocket();

            this.$watch('isOpen', (val) => {
                localStorage.setItem('nala_ticket_chat_open_' + this.ticketId, val ? 'true' : 'false');
                if (val) {
                    this.unreadCount = 0;
                    this.fetchMessages();
                    this.scrollToBottom();
                    this.$nextTick(() => this.$refs.chatInput?.focus());
                }
            });
        },

        useQuickPrompt(text) {
            this.draft = text;
            this.$nextTick(() => this.$refs.chatInput?.focus());
        },

        connectWebSocket() {
            const wsHost = window.location.hostname || '127.0.0.1';
            const wsPort = {{ (int) env('WEBSOCKET_PORT', 6001) }};
            try {
                this.ws = new WebSocket(`ws://${wsHost}:${wsPort}`);

                this.ws.onopen = () => {
                    this.wsConnected = true;
                };

                this.ws.onmessage = (event) => {
                    try {
                        const payload = JSON.parse(event.data);
                        if (payload.event === 'chat.message' && payload.data) {
                            this.handleIncomingMessage(payload.data);
                        } else if (payload.event === 'chat.typing' && payload.data) {
                            this.handleIncomingTyping(payload.data);
                        }
                    } catch (e) {}
                };

                this.ws.onclose = () => {
                    this.wsConnected = false;
                    setTimeout(() => this.connectWebSocket(), 2500);
                };

                this.ws.onerror = () => {
                    this.wsConnected = false;
                };
            } catch (e) {
                this.wsConnected = false;
                setTimeout(() => this.connectWebSocket(), 3000);
            }
        },

        notifyTyping() {
            if (!this.ws || this.ws.readyState !== WebSocket.OPEN) return;
            const now = Date.now();
            if (now - this.lastTypingSentAt < 800) return;
            this.lastTypingSentAt = now;

            try {
                this.ws.send(JSON.stringify({
                    event: 'chat.typing',
                    data: {
                        ticket_id: this.ticketId,
                        sender_id: this.currentUserId,
                        sender_name: this.currentUserName,
                        sender_role: this.currentUserRole
                    }
                }));
            } catch (e) {}
        },

        handleIncomingTyping(data) {
            const senderId = Number(data.sender_id);
            const msgTicketId = Number(data.ticket_id || 0);
            if (!senderId || senderId === this.currentUserId) return;
            if (msgTicketId !== this.ticketId) return;

            this.activeTypingName = data.sender_name || 'Partisipan';
            this.activeTypingRole = data.sender_role || '';

            if (this.typingTimeout) {
                clearTimeout(this.typingTimeout);
            }
            this.typingTimeout = setTimeout(() => {
                this.activeTypingName = '';
                this.activeTypingRole = '';
            }, 2800);

            this.scrollToBottom();
        },

        handleIncomingMessage(msg) {
            const msgTicketId = Number(msg.ticket_id || 0);
            if (msgTicketId !== this.ticketId) return;

            const msgSender = Number(msg.sender_id);
            if (msgSender !== this.currentUserId) {
                this.activeTypingName = '';
                this.activeTypingRole = '';
            }

            if (this.messages.some(m => Number(m.id) === Number(msg.id))) {
                return;
            }

            this.messages.push(msg);
            this.scrollToBottom();

            if (!this.isOpen && msgSender !== this.currentUserId) {
                this.unreadCount++;
            }
        },

        async fetchMessages() {
            this.loading = true;
            try {
                const res = await fetch(`{{ route('chat.index') }}?ticket_id=${encodeURIComponent(this.ticketId)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.messages = data.messages || [];
                    this.scrollToBottom();
                }
            } catch (e) {
            } finally {
                this.loading = false;
            }
        },

        async sendMessage() {
            const text = this.draft.trim();
            if (!text || this.sending) return;

            this.sending = true;
            const prevDraft = this.draft;
            this.draft = '';
            this.lastTypingSentAt = 0;

            try {
                const res = await fetch(`{{ route('chat.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        ticket_id: this.ticketId,
                        message: text
                    })
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.message) {
                        this.handleIncomingMessage(data.message);
                    }
                } else {
                    this.draft = prevDraft;
                }
            } catch (e) {
                this.draft = prevDraft;
            } finally {
                this.sending = false;
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.messagesContainer;
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            });
        }
    }"
    @open-ticket-chat.window="isOpen = true"
    class="fixed bottom-5 right-5 z-50 flex flex-col items-end"
>
    <!-- Pop-up Ticket Chat Window -->
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-3 scale-95"
        style="display: none;"
        class="w-[340px] sm:w-[375px] h-[480px] bg-white rounded-2xl shadow-xl border border-slate-200 flex flex-col overflow-hidden"
    >
        <!-- Header: Ticket Context -->
        <div class="px-4 py-3 bg-[#1E40AF] text-white flex items-center justify-between gap-2.5 shrink-0">
            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                <div class="w-8 h-8 rounded-xl bg-white/15 border border-white/20 text-white flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold text-white tracking-tight truncate">Diskusi Tiket</span>
                        <span class="px-1.5 py-0.5 rounded bg-white/15 text-[10px] font-semibold text-blue-50 tabular-nums shrink-0">
                            {{ $ticket->ticket_number }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <template x-if="activeTypingName">
                            <span class="text-[11px] text-emerald-300 font-medium truncate animate-pulse" x-text="`${activeTypingName} sedang mengetik...`"></span>
                        </template>
                        <template x-if="!activeTypingName">
                            <div class="flex items-center gap-1.5 truncate">
                                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="wsConnected ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                                <span class="text-[11px] text-blue-100 truncate">{{ $ticket->title }}</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Minimize Button -->
            <button
                type="button"
                @click="isOpen = false"
                class="p-1.5 rounded-lg text-blue-100 hover:text-white hover:bg-white/10 transition-colors cursor-pointer shrink-0"
                aria-label="Kecilkan diskusi tiket"
                title="Kecilkan chat"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>

        <!-- Ticket Participants Context Strip -->
        <div class="px-3.5 py-2 bg-slate-50 border-b border-slate-200/80 flex items-center justify-between gap-2 text-[11px] text-slate-600 shrink-0">
            <div class="truncate">
                <span class="text-slate-400">Pemohon:</span>
                <strong class="text-slate-800 font-semibold">{{ $ticket->requester->name }}</strong>
            </div>
            <div class="truncate text-right">
                <span class="text-slate-400">Teknisi:</span>
                <strong class="text-slate-800 font-semibold">{{ $ticket->assignedTo?->name ?? 'Antrean IT' }}</strong>
            </div>
        </div>

        <!-- Messages Stream (Scoped to this Ticket) -->
        <div
            x-ref="messagesContainer"
            class="flex-1 px-3.5 py-3 overflow-y-auto space-y-2.5 bg-slate-50/50 custom-scrollbar"
        >
            <!-- Empty State with Quick Starters -->
            <template x-if="messages.length === 0 && !loading">
                <div class="py-10 flex flex-col items-center justify-center text-center px-3">
                    <div class="w-10 h-10 rounded-full bg-blue-50 text-[#1E40AF] border border-blue-100 flex items-center justify-center mb-2.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <p class="text-xs font-semibold text-slate-800">Belum ada diskusi pada tiket ini</p>
                    <p class="text-[11px] text-slate-500 mt-1 max-w-[250px] leading-relaxed">
                        Ruang chat ini khusus untuk koordinasi tiket <strong class="text-slate-700">{{ $ticket->ticket_number }}</strong> antara Pemohon, Atasan, dan Tim IT.
                    </p>

                    <!-- Quick Prompt Chips -->
                    <div class="mt-4 flex flex-wrap items-center justify-center gap-1.5">
                        <button
                            type="button"
                            @click="useQuickPrompt('Mohon info perkembangan pengerjaan tiket ini.')"
                            class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-white hover:bg-blue-50 text-slate-700 hover:text-[#1E40AF] border border-slate-200 hover:border-blue-200 transition-colors cursor-pointer"
                        >
                            Tanya update progres
                        </button>
                        <button
                            type="button"
                            @click="useQuickPrompt('Apakah ada detail atau akses tambahan yang dibutuhkan?')"
                            class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-white hover:bg-blue-50 text-slate-700 hover:text-[#1E40AF] border border-slate-200 hover:border-blue-200 transition-colors cursor-pointer"
                        >
                            Konfirmasi kebutuhan
                        </button>
                    </div>
                </div>
            </template>

            <!-- Message Bubbles -->
            <template x-for="msg in messages" :key="msg.id">
                <div
                    class="flex flex-col"
                    :class="Number(msg.sender_id) === currentUserId ? 'items-end' : 'items-start'"
                >
                    <div class="flex items-center gap-1.5 mb-1 px-1">
                        <span
                            class="text-[11px] font-semibold"
                            :class="Number(msg.sender_id) === currentUserId ? 'text-slate-500' : 'text-slate-800'"
                            x-text="Number(msg.sender_id) === currentUserId ? 'Anda' : msg.sender_name"
                        ></span>
                        <span
                            class="px-1.5 py-0.2 rounded text-[10px] font-medium bg-slate-200/80 text-slate-600"
                            x-text="msg.sender_role"
                        ></span>
                    </div>

                    <div
                        class="max-w-[84%] px-3 py-2 rounded-xl text-xs leading-normal break-words shadow-2xs"
                        :class="Number(msg.sender_id) === currentUserId
                            ? 'bg-[#1E40AF] text-white rounded-br-none'
                            : 'bg-white text-slate-800 border border-slate-200/90 rounded-bl-none'"
                    >
                        <div class="whitespace-pre-wrap" x-text="msg.message"></div>
                        <div
                            class="text-[10px] mt-1 text-right tabular-nums"
                            :class="Number(msg.sender_id) === currentUserId ? 'text-blue-200' : 'text-slate-400'"
                            x-text="msg.created_at_formatted"
                        ></div>
                    </div>
                </div>
            </template>

            <!-- Live Typing Indicator Bubble inside Ticket Chat Stream -->
            <div x-show="activeTypingName" class="flex items-center gap-2 pt-1" style="display: none;">
                <div class="px-3 py-1.5 rounded-xl rounded-bl-none bg-white border border-slate-200 shadow-2xs inline-flex items-center gap-2">
                    <span class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#1E40AF] animate-bounce"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-[#1E40AF] animate-bounce" style="animation-delay: 150ms;"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-[#1E40AF] animate-bounce" style="animation-delay: 300ms;"></span>
                    </span>
                    <span class="text-[11px] text-slate-500 italic" x-text="`${activeTypingName} sedang mengetik...`"></span>
                </div>
            </div>
        </div>

        <!-- Input Form -->
        <form
            @submit.prevent="sendMessage"
            class="p-2.5 bg-white border-t border-slate-200 flex items-center gap-2 shrink-0"
        >
            <input
                type="text"
                x-ref="chatInput"
                x-model="draft"
                @input="notifyTyping()"
                @keydown="notifyTyping()"
                placeholder="Tulis pesan untuk tiket ini..."
                maxlength="2000"
                class="flex-1 text-xs rounded-xl border-slate-200 focus:border-[#1E40AF] focus:ring-1 focus:ring-[#1E40AF] py-2 px-3 bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
            >
            <button
                type="submit"
                :disabled="!draft.trim() || sending"
                class="w-9 h-9 rounded-xl bg-[#1E40AF] hover:bg-[#1D4ED8] disabled:opacity-40 text-white inline-flex items-center justify-center shrink-0 transition-colors cursor-pointer"
                aria-label="Kirim pesan"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
            </button>
        </form>
    </div>

    <!-- Floating Trigger Button (Scoped to this Ticket) -->
    <button
        type="button"
        x-show="!isOpen"
        @click="isOpen = true"
        class="relative inline-flex items-center gap-2.5 px-4 py-2.5 rounded-full bg-[#1E40AF] hover:bg-[#1D4ED8] text-white font-semibold text-xs shadow-lg hover:shadow-xl transition-all cursor-pointer"
    >
        <span class="relative flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            <span
                class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full ring-2 ring-[#1E40AF]"
                :class="wsConnected ? 'bg-emerald-400' : 'bg-amber-400'"
            ></span>
        </span>
        <span>Chat Tiket</span>
        <span
            x-show="messages.length > 0 && unreadCount === 0"
            x-text="messages.length"
            style="display: none;"
            class="px-1.5 py-0.5 text-[10px] font-bold bg-white/20 text-white rounded-full tabular-nums"
        ></span>
        <span
            x-show="unreadCount > 0"
            x-text="unreadCount"
            style="display: none;"
            class="px-1.5 py-0.5 text-[10px] font-bold bg-rose-500 text-white rounded-full tabular-nums"
        ></span>
    </button>
</div>
@endif
