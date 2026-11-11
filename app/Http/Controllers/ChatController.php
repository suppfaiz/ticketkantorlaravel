<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $ticketId = $request->filled('ticket_id') ? (int) $request->input('ticket_id') : null;
        $contactId = $request->filled('contact_id') ? (int) $request->input('contact_id') : null;

        if ($ticketId) {
            $ticket = Ticket::findOrFail($ticketId);
            $this->authorize('view', $ticket);

            ChatMessage::where('ticket_id', $ticketId)
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $messages = ChatMessage::with(['sender:id,name,role'])
                ->where('ticket_id', $ticketId)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->limit(200)
                ->get();

            return response()->json([
                'ticket_id' => $ticketId,
                'count' => $messages->count(),
                'messages' => $messages->map(fn (ChatMessage $m) => $this->formatMessage($m)),
                'unread_total' => 0,
            ]);
        }

        if ($contactId) {
            ChatMessage::where('sender_id', $contactId)
                ->where('receiver_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $messages = ChatMessage::with(['sender:id,name,role', 'receiver:id,name,role'])
                ->where(function ($q) use ($user, $contactId) {
                    $q->where(function ($sq) use ($user, $contactId) {
                        $sq->where('sender_id', $user->id)
                            ->where('receiver_id', $contactId);
                    })->orWhere(function ($sq) use ($user, $contactId) {
                        $sq->where('sender_id', $contactId)
                            ->where('receiver_id', $user->id);
                    });
                })
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->limit(150)
                ->get();
        } else {
            $messages = ChatMessage::with(['sender:id,name,role'])
                ->whereNull('receiver_id')
                ->whereNull('ticket_id')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->limit(150)
                ->get();
        }

        $unreadBySender = ChatMessage::where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->selectRaw('sender_id, count(*) as aggregate')
            ->groupBy('sender_id')
            ->pluck('aggregate', 'sender_id');

        $contacts = User::with('department:id,name')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'department_id'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'role' => $this->translateRole($u->role),
                'department' => $u->department?->name ?? '-',
                'unread_count' => (int) ($unreadBySender[$u->id] ?? 0),
            ]);

        return response()->json([
            'messages' => $messages->map(fn (ChatMessage $m) => $this->formatMessage($m)),
            'contacts' => $contacts,
            'unread_total' => (int) $unreadBySender->sum(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'receiver_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                Rule::prohibitedIf(fn () => $request->filled('ticket_id')),
            ],
            'ticket_id' => [
                'nullable',
                'integer',
                'exists:tickets,id',
                Rule::prohibitedIf(fn () => $request->filled('receiver_id')),
            ],
        ]);

        $user = $request->user();

        if (! empty($validated['ticket_id'])) {
            $ticket = Ticket::findOrFail($validated['ticket_id']);
            $this->authorize('view', $ticket);
        }

        $chatMessage = ChatMessage::create([
            'sender_id' => $user->id,
            'receiver_id' => $validated['receiver_id'] ?? null,
            'ticket_id' => $validated['ticket_id'] ?? null,
            'message' => trim($validated['message']),
        ]);

        $chatMessage->load(['sender:id,name,role', 'receiver:id,name,role']);
        $payload = $this->formatMessage($chatMessage);

        return response()->json([
            'message' => $payload,
        ], 201);
    }

    public function storeDiscussion(Request $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $ticket);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx'],
        ]);

        $messageText = trim($validated['message']);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $storedPath = $file->store('tickets/'.$ticket->id, 'local');
            TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'uploader_id' => $request->user()->id,
                'file_name' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'file_size_bytes' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'created_at' => now(),
            ]);
            $messageText .= "\n[Lampiran Diskusi: ".$file->getClientOriginalName().']';
        }

        $chatMessage = ChatMessage::create([
            'sender_id' => $request->user()->id,
            'ticket_id' => $ticket->id,
            'message' => $messageText,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            $chatMessage->load(['sender:id,name,role']);
            $count = ChatMessage::where('ticket_id', $ticket->id)->count();

            return response()->json([
                'message' => $this->formatMessage($chatMessage),
                'count' => $count,
            ], 201);
        }

        return redirect()
            ->to(route('tickets.show', $ticket).'#discussion')
            ->with('active_tab', 'discussion')
            ->with('success', 'Komentar diskusi berhasil dikirim.');
    }

    private function formatMessage(ChatMessage $m): array
    {
        return [
            'id' => $m->id,
            'sender_id' => $m->sender_id,
            'sender_name' => $m->sender?->name ?? 'Pengguna',
            'sender_role' => $this->translateRole($m->sender?->role),
            'receiver_id' => $m->receiver_id,
            'receiver_name' => $m->receiver?->name,
            'ticket_id' => $m->ticket_id,
            'message' => $m->message,
            'created_at_formatted' => $m->created_at?->format('H:i') ?? now()->format('H:i'),
            'created_at_date' => $m->created_at?->format('d M Y, H:i') ?? now()->format('d M Y, H:i'),
            'created_at_iso' => $m->created_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }

    private function translateRole(?string $role): string
    {
        return match ($role) {
            'requester' => 'Pemohon',
            'supervisor' => 'Atasan',
            'it_head' => 'Kabag IT',
            'kadiv_it' => 'Kadiv IT',
            'direksi' => 'Direksi',
            'it_developer' => 'IT Developer',
            'admin' => 'Administrator',
            default => ucwords(str_replace('_', ' ', $role ?? 'Pengguna')),
        };
    }
}
