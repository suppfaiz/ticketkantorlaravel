<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItHeadDecisionRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\ApprovalDelegation;
use App\Models\ExternalLoaTransaction;
use App\Models\Ticket;
use App\Models\TicketActivityLog;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\ApprovalDelegationService;
use App\Services\AuditTrailService;
use App\Services\Contracts\LoaServiceInterface;
use App\Services\TicketService;
use App\Services\TicketStateMachine;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    protected TicketService $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    protected function buildFilteredTicketQuery(Request $request)
    {
        $user = $request->user();

        $query = Ticket::with(['requester.departmentRecord', 'supervisor', 'assignedTo'])
            ->latest('created_at')
            ->orderByDesc('id');

        $delegations = app(ApprovalDelegationService::class);
        $hasExecutiveDelegation = $delegations->actsForRole($user, User::ROLE_IT_HEAD, ApprovalDelegation::SCOPE_IT_HEAD)
            || $delegations->actsForRole($user, User::ROLE_KADIV_IT, ApprovalDelegation::SCOPE_KADIV_IT)
            || $delegations->actsForRole($user, User::ROLE_DIREKSI, ApprovalDelegation::SCOPE_DIREKSI);

        // Scope queries by role (least privilege)
        if ($hasExecutiveDelegation || $user->isAdmin()) {
            // Existing executive flow grants visibility to the full ticket list.
        } elseif ($user->isRequester()) {
            $query->where('requester_id', $user->id);
        } elseif ($user->isSupervisor()) {
            $supervisorIds = $delegations
                ->delegatedUserIds($user, ApprovalDelegation::SCOPE_SUPERVISOR)
                ->push($user->id)
                ->unique();

            $query->where(function ($q) use ($user, $supervisorIds) {
                $q->whereIn('supervisor_id', $supervisorIds)
                    ->orWhere('requester_id', $user->id);
            });
        } elseif ($user->isItDeveloper()) {
            $query->where(function ($q) use ($user) {
                $q->whereIn('status', [
                    Ticket::STATUS_READY_FOR_WORK,
                    Ticket::STATUS_IN_PROGRESS,
                    Ticket::STATUS_WAITING_USER_REVIEW,
                    Ticket::STATUS_REVISION_WORK,
                    Ticket::STATUS_COMPLETED,
                ])->orWhere('assigned_to', $user->id);
            });
        }
        // IT Head, Kadiv IT, Direksi, and Admin see all tickets

        if ($request->filled('status')) {
            $statusFilter = $request->query('status');
            $statusGroups = [
                'pending_approval' => [
                    Ticket::STATUS_WAITING_SUPERVISOR,
                    Ticket::STATUS_WAITING_IT_HEAD,
                    Ticket::STATUS_WAITING_LOA_KADIV_IT,
                    Ticket::STATUS_WAITING_LOA_DIREKSI,
                    Ticket::STATUS_WAITING_MAJOR_MEETING,
                    Ticket::STATUS_WAITING_MINUTES,
                    Ticket::STATUS_WAITING_USER_MINUTES,
                    Ticket::STATUS_WAITING_MINUTES_APPROVAL,
                ],
                'need_revision' => [
                    Ticket::STATUS_REVISION_REQUESTER,
                    Ticket::STATUS_REVISION_BY_IT_HEAD,
                    Ticket::STATUS_REVISION_MINUTES,
                    Ticket::STATUS_REVISION_WORK,
                ],
                'in_progress' => [
                    Ticket::STATUS_READY_FOR_WORK,
                    Ticket::STATUS_IN_PROGRESS,
                    Ticket::STATUS_WAITING_USER_REVIEW,
                ],
                'completed' => [
                    Ticket::STATUS_COMPLETED,
                ],
                'rejected' => [
                    Ticket::STATUS_REJECTED_SUPERVISOR,
                    Ticket::STATUS_REJECTED_IT_HEAD,
                    Ticket::STATUS_REJECTED_KADIV_IT,
                    Ticket::STATUS_REJECTED_DIREKSI,
                    Ticket::STATUS_CANCELLED,
                ],
            ];

            if (isset($statusGroups[$statusFilter])) {
                $query->whereIn('status', $statusGroups[$statusFilter]);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        if ($user->isItDeveloper()) {
            match ($this->developerQueue($request)) {
                'unassigned' => $query->where('status', Ticket::STATUS_READY_FOR_WORK)->whereNull('assigned_to'),
                'active' => $query->where('assigned_to', $user->id)->whereIn('status', [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_REVISION_WORK]),
                'completed' => $query->where('assigned_to', $user->id)->whereIn('status', [Ticket::STATUS_WAITING_USER_REVIEW, Ticket::STATUS_COMPLETED]),
                default => null,
            };
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->query('priority'));
        }

        if ($request->boolean('overdue')) {
            $query->whereNotNull('target_completion_at')
                ->where('target_completion_at', '<', now())
                ->whereNotIn('status', [
                    Ticket::STATUS_COMPLETED,
                    Ticket::STATUS_CANCELLED,
                    Ticket::STATUS_WAITING_USER_REVIEW,
                    Ticket::STATUS_REJECTED_SUPERVISOR,
                    Ticket::STATUS_REJECTED_IT_HEAD,
                    Ticket::STATUS_REJECTED_KADIV_IT,
                    Ticket::STATUS_REJECTED_DIREKSI,
                ]);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%");
            });
        }

        return $query;
    }

    private function developerQueue(Request $request): ?string
    {
        $queue = $request->query('developer_queue');

        return $request->user()->isItDeveloper() && in_array($queue, ['unassigned', 'active', 'completed'], true)
            ? $queue
            : null;
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        $query = $this->buildFilteredTicketQuery($request);

        $perPage = in_array((int) $request->query('per_page'), [10, 50, 100], true)
            ? (int) $request->query('per_page')
            : 10;

        $tickets = $query->paginate($perPage)->withQueryString();
        $categories = TicketCategory::activeNames();
        $developerQueue = $this->developerQueue($request);

        return view('tickets.index', compact('tickets', 'perPage', 'categories', 'developerQueue'));
    }

    public function calendar(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        $monthParam = (string) $request->query('month', now()->format('Y-m'));
        try {
            $currentMonth = Carbon::createFromFormat('Y-m-d', $monthParam.'-01')->startOfMonth();
        } catch (\Throwable $e) {
            $currentMonth = now()->startOfMonth();
        }

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $user = $request->user();
        $baseQuery = Ticket::with(['requester.departmentRecord', 'supervisor', 'assignedTo', 'meetingMinutes'])
            ->where('status', '!=', Ticket::STATUS_CANCELLED);

        if ($user->isRequester()) {
            $baseQuery->where('requester_id', $user->id);
        } elseif ($user->isSupervisor()) {
            $baseQuery->where(function ($q) use ($user) {
                $q->where('supervisor_id', $user->id)
                    ->orWhere('requester_id', $user->id);
            });
        } elseif ($user->isItDeveloper()) {
            // Teknisi hanya melihat timeline tiket yang dia kerjakan sendiri
            $baseQuery->where('assigned_to', $user->id);
        } elseif ($user->isKadivIt() || $user->isDireksi()) {
            $baseQuery->where('classification', Ticket::CLASSIFICATION_MAJOR);
        }
        // Kabag IT (it_head) & Admin dapat melihat seluruh timeline tiket

        if (($user->isItHead() || $user->isAdmin()) && $request->filled('assigned_to')) {
            $baseQuery->where('assigned_to', (int) $request->query('assigned_to'));
        }

        if ($request->filled('category')) {
            $baseQuery->where('category', $request->query('category'));
        }

        // Fetch tickets relevant to this month (by target, meeting, creation, completion, or still active)
        $tickets = $baseQuery->where(function ($q) use ($startOfMonth, $endOfMonth) {
            $q->whereBetween('target_completion_at', [$startOfMonth, $endOfMonth])
                ->orWhereBetween('meeting_scheduled_at', [$startOfMonth, $endOfMonth])
                ->orWhereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->orWhereBetween('work_started_at', [$startOfMonth, $endOfMonth])
                ->orWhereBetween('work_completed_at', [$startOfMonth, $endOfMonth])
                ->orWhereHas('meetingMinutes', function ($mq) use ($startOfMonth, $endOfMonth) {
                    $mq->whereBetween('meeting_date', [$startOfMonth, $endOfMonth]);
                })
                ->orWhere(function ($aq) use ($endOfMonth) {
                    $aq->where('created_at', '<=', $endOfMonth)
                        ->whereNotIn('status', [
                            Ticket::STATUS_COMPLETED,
                            Ticket::STATUS_CANCELLED,
                            Ticket::STATUS_REJECTED_SUPERVISOR,
                            Ticket::STATUS_REJECTED_IT_HEAD,
                            Ticket::STATUS_REJECTED_KADIV_IT,
                            Ticket::STATUS_REJECTED_DIREKSI,
                        ]);
                });
        })->orderBy('created_at', 'asc')->get();

        $eventTypeFilter = (string) $request->query('event_type', '');
        $eventsByDate = [];
        $ganttTickets = collect();

        foreach ($tickets as $t) {
            $overallState = 'approval';
            if ($t->isOverdue()) {
                $overallState = 'overdue';
            } elseif (in_array($t->status, [Ticket::STATUS_COMPLETED, Ticket::STATUS_WAITING_USER_REVIEW], true)) {
                $overallState = 'completed';
            } elseif (in_array($t->status, [Ticket::STATUS_READY_FOR_WORK, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_REVISION_WORK], true)) {
                $overallState = 'in_progress';
            } elseif ($t->isTerminal()) {
                $overallState = 'rejected';
            }

            if ($eventTypeFilter === 'target' && ! $t->target_completion_at) {
                continue;
            }
            $rawMeetingDate = $t->meetingMinutes?->meeting_date ?? $t->meeting_scheduled_at;
            if ($eventTypeFilter === 'meeting' && ! $rawMeetingDate) {
                continue;
            }
            if ($eventTypeFilter === 'overdue' && ! $t->isOverdue()) {
                continue;
            }

            $spanStart = ($t->submitted_at ?? $t->created_at)->copy()->startOfDay();
            $spanEnd = ($t->target_completion_at ?? $t->work_completed_at ?? $t->closed_at ?? now())->copy()->endOfDay();
            if ($spanEnd->lt($spanStart)) {
                $spanEnd = $spanStart->copy()->endOfDay();
            }

            $ganttTickets->push([
                'ticket' => $t,
                'state' => $overallState,
                'span_start' => $spanStart,
                'span_end' => $spanEnd,
                'meeting_date' => $rawMeetingDate ? Carbon::parse($rawMeetingDate) : null,
            ]);

            // Populate Monthly Calendar Grid Events
            // 1. Target Timeline Kabag IT
            if ($t->target_completion_at && $t->target_completion_at->between($startOfMonth, $endOfMonth)) {
                $dKey = $t->target_completion_at->format('Y-m-d');
                $eventsByDate[$dKey][] = [
                    'type' => 'target',
                    'date' => $t->target_completion_at,
                    'state' => $t->isOverdue() ? 'overdue' : ($overallState === 'completed' ? 'completed' : 'target'),
                    'badge' => 'Target Selesai',
                    'ticket' => $t,
                ];
            }

            // 2. Jadwal Rapat Major
            if ($rawMeetingDate) {
                $mCarbon = Carbon::parse($rawMeetingDate);
                if ($mCarbon->between($startOfMonth, $endOfMonth)) {
                    $dKey = $mCarbon->format('Y-m-d');
                    $eventsByDate[$dKey][] = [
                        'type' => 'meeting',
                        'date' => $mCarbon,
                        'state' => 'meeting',
                        'badge' => 'Rapat '.$mCarbon->format('H:i'),
                        'ticket' => $t,
                    ];
                }
            }

            // 3. Jika belum ada target_completion_at, tampilkan di tanggal mulai/selesai agar kalender tetap informatif
            if (! $t->target_completion_at && $eventTypeFilter === '') {
                $milestoneDate = $t->work_completed_at ?? $t->work_started_at ?? $t->created_at;
                if ($milestoneDate && $milestoneDate->between($startOfMonth, $endOfMonth)) {
                    $dKey = $milestoneDate->format('Y-m-d');
                    $badgeText = $t->work_completed_at ? 'Selesai Kerja' : ($t->work_started_at ? 'Mulai Kerja' : 'Tiket Masuk');
                    $eventsByDate[$dKey][] = [
                        'type' => 'milestone',
                        'date' => $milestoneDate,
                        'state' => $overallState,
                        'badge' => $badgeText,
                        'ticket' => $t,
                    ];
                }
            }
        }

        $itDevelopers = User::where('role', User::ROLE_IT_DEVELOPER)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $categories = TicketCategory::activeNames();

        return view('tickets.calendar', compact(
            'currentMonth',
            'eventsByDate',
            'ganttTickets',
            'itDevelopers',
            'categories'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $tickets = $this->buildFilteredTicketQuery($request)->limit(5000)->get();
        $filename = 'Laporan_Tiket_Helpdesk_IT_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($tickets) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Microsoft Excel reads UTF-8 characters properly
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Nomor Tiket',
                'Tanggal Dibuat',
                'Judul Permohonan',
                'Kategori',
                'Klasifikasi',
                'Prioritas',
                'Status Saat Ini',
                'Nama Pemohon',
                'Divisi Pemohon',
                'Atasan Peninjau',
                'Teknisi IT',
                'Target Timeline Kabag IT',
                'Status Ketepatan Timeline',
                'Mulai Dikerjakan',
                'Selesai Dikerjakan',
                'Ditutup Resmi',
                'Durasi Pengerjaan IT (Jam)',
                'Total SLA Siklus (Jam)',
            ]);

            foreach ($tickets as $t) {
                $workHours = ($t->work_started_at && $t->work_completed_at)
                    ? round($t->work_started_at->diffInMinutes($t->work_completed_at) / 60, 2)
                    : '';
                $endPoint = $t->closed_at ?? $t->work_completed_at;
                $totalSlaHours = ($t->created_at && $endPoint)
                    ? round($t->created_at->diffInMinutes($endPoint) / 60, 2)
                    : '';

                $timelineStatus = 'Belum Ditetapkan';
                if ($t->target_completion_at) {
                    if ($t->isOverdue() || $t->isCompletedLate()) {
                        $timelineStatus = 'Melewati Timeline (Overdue)';
                    } else {
                        $timelineStatus = 'Sesuai Timeline';
                    }
                }

                fputcsv($out, array_map(self::safeCsvCell(...), [
                    $t->ticket_number,
                    $t->created_at?->format('Y-m-d H:i:s'),
                    $t->title,
                    $t->category ?? 'Umum',
                    $t->classification ?? '-',
                    $t->priority ?? '-',
                    $t->status,
                    $t->requester?->name ?? '-',
                    $t->requester?->departmentRecord?->name ?? ($t->requester?->department ?? '-'),
                    $t->supervisor?->name ?? '-',
                    $t->assignedTo?->name ?? 'Belum ditugaskan',
                    $t->target_completion_at?->format('Y-m-d') ?? '-',
                    $timelineStatus,
                    $t->work_started_at?->format('Y-m-d H:i:s') ?? '-',
                    $t->work_completed_at?->format('Y-m-d H:i:s') ?? '-',
                    $t->closed_at?->format('Y-m-d H:i:s') ?? '-',
                    $workHours,
                    $totalSlaHours,
                ]));
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private static function safeCsvCell(mixed $value): string
    {
        $text = (string) $value;

        return preg_match('/\A[\x00-\x20]*[=+\-@]/', $text) ? "'".$text : $text;
    }

    public function create(): View
    {
        $this->authorize('create', Ticket::class);
        $user = auth()->user();
        $supervisor = $user->supervisor;

        $availableSupervisors = User::where('role', 'supervisor')->get();
        $categories = TicketCategory::activeNames();

        return view('tickets.create', compact('supervisor', 'availableSupervisors', 'categories'));
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $this->ticketService->createTicket(
            requester: $request->user(),
            data: $request->validated(),
            attachments: $request->file('attachments', [])
        );

        // Dispatch LOA Atasan via LOA Service
        if ($ticket->status === Ticket::STATUS_WAITING_SUPERVISOR && $ticket->supervisor_id) {
            app(LoaServiceInterface::class)->dispatch(
                $ticket,
                ExternalLoaTransaction::LEVEL_SUPERVISOR,
                ['supervisor_id' => $ticket->supervisor_id]
            );
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} berhasil diajukan.");
    }

    public function show(Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'requester.departmentRecord',
            'supervisor',
            'assignedTo',
            'attachments.uploader',
            'meetingMinutes.author',
            'meetingMinutes.approver',
            'activityLogs.user',
            'reviews.reviewer',
            'auditLogs.actor',
            'discussions.sender',
        ]);

        $itDevelopers = User::where('role', User::ROLE_IT_DEVELOPER)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $supervisors = User::whereIn('role', [
            User::ROLE_SUPERVISOR,
            User::ROLE_IT_HEAD,
            User::ROLE_KADIV_IT,
            User::ROLE_DIREKSI,
        ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('tickets.show', compact('ticket', 'itDevelopers', 'supervisors'));
    }

    public function submitDraft(
        Request $request,
        Ticket $ticket,
        TicketStateMachine $stateMachine
    ): RedirectResponse {
        $this->authorize('update', $ticket);

        if ($ticket->status !== Ticket::STATUS_DRAFT) {
            return redirect()->route('tickets.show', $ticket)
                ->with('info', 'Tiket ini sudah tidak berstatus Draf.');
        }

        $requester = $ticket->requester ?? $request->user();
        $bypassesSupervisor = ($requester->isSupervisor() || $requester->isAdmin()) &&
            (! $requester->supervisor_id || (int) $requester->supervisor_id === (int) $requester->id);

        $targetStatus = $bypassesSupervisor
            ? Ticket::STATUS_WAITING_IT_HEAD
            : Ticket::STATUS_WAITING_SUPERVISOR;

        $stateMachine->transition(
            ticket: $ticket,
            toStatus: $targetStatus,
            actor: $request->user(),
            action: 'SUBMIT_DRAFT_TICKET',
            remarks: 'Draf tiket diajukan resmi oleh pemohon.'
        );

        if ($ticket->status === Ticket::STATUS_WAITING_SUPERVISOR && $ticket->supervisor_id) {
            app(LoaServiceInterface::class)->dispatch(
                $ticket,
                ExternalLoaTransaction::LEVEL_SUPERVISOR,
                ['supervisor_id' => $ticket->supervisor_id]
            );
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Draf tiket {$ticket->ticket_number} berhasil diajukan ke alur persetujuan.");
    }

    public function print(Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'requester.departmentRecord',
            'supervisor.departmentRecord',
            'assignedTo.departmentRecord',
            'attachments.uploader',
            'meetingMinutes.author',
            'meetingMinutes.approver',
            'activityLogs.user',
            'reviews.reviewer',
            'auditLogs.actor',
        ]);

        return view('tickets.print', compact('ticket'));
    }

    public function cancel(
        Request $request,
        Ticket $ticket,
        TicketStateMachine $stateMachine
    ): RedirectResponse {
        $this->authorize('cancel', $ticket);

        $reason = trim((string) $request->input('reason', 'Dibatalkan oleh pemohon.'));
        if ($reason === '') {
            $reason = 'Dibatalkan oleh pemohon.';
        }

        if ($ticket->status === Ticket::STATUS_DRAFT && $request->boolean('delete_draft')) {
            $ticketNumber = $ticket->ticket_number;
            $ticket->delete();

            return redirect()->route('tickets.index')
                ->with('success', "Draf tiket {$ticketNumber} berhasil dihapus.");
        }

        $stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_CANCELLED,
            actor: $request->user(),
            action: 'REQUESTER_CANCEL_TICKET',
            remarks: $reason
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Permohonan tiket {$ticket->ticket_number} telah dibatalkan.");
    }

    public function edit(Ticket $ticket): View
    {
        $this->authorize('update', $ticket);
        $categories = TicketCategory::activeNames($ticket->category);

        return view('tickets.edit', compact('ticket', 'categories'));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->ticketService->updateTicket(
            ticket: $ticket,
            requester: $request->user(),
            data: $request->validated(),
            attachments: $request->file('attachments', [])
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} berhasil diperbarui dan diajukan ulang.");
    }

    public function syncLoaStatus(Ticket $ticket, LoaServiceInterface $loaService): RedirectResponse
    {
        $this->authorize('view', $ticket);

        $latestTransaction = $ticket->externalLoaTransactions()->latest()->first();
        if ($latestTransaction) {
            if ($latestTransaction->sync_status === ExternalLoaTransaction::SYNC_FAILED) {
                $loaService->retry($latestTransaction);
            } else {
                $loaService->pollStatus($latestTransaction);
            }
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Status LOA eksternal berhasil disinkronkan.');
    }

    public function itHeadDecision(
        ItHeadDecisionRequest $request,
        Ticket $ticket,
        TicketStateMachine $stateMachine
    ): RedirectResponse {
        $this->authorize('reviewItHead', $ticket);

        $decision = $request->input('decision');
        $notes = $request->input('notes') ?? $request->input('reason') ?? $request->input('revision_notes');

        if ($decision === 'minor_approved' || $decision === 'Minor') {
            $ticket->update(['classification' => Ticket::CLASSIFICATION_MINOR]);
            $stateMachine->transition(
                ticket: $ticket,
                toStatus: Ticket::STATUS_READY_FOR_WORK,
                actor: $request->user(),
                action: 'IT_HEAD_APPROVE_MINOR',
                remarks: $notes ?? 'Klasifikasi Minor disetujui, tiket langsung masuk ke Pool Siap Progress.'
            );

            return redirect()->route('tickets.show', $ticket)
                ->with('success', "Tiket {$ticket->ticket_number} disetujui sebagai Minor dan masuk antrean Pool Siap Progress.");
        }

        if ($decision === 'major_approved' || $decision === 'Major') {
            $ticket->update(['classification' => Ticket::CLASSIFICATION_MAJOR]);
            $stateMachine->transition(
                ticket: $ticket,
                toStatus: Ticket::STATUS_WAITING_LOA_KADIV_IT,
                actor: $request->user(),
                action: 'IT_HEAD_APPROVE_MAJOR',
                remarks: $notes ?? 'Klasifikasi Major disetujui, mengajukan LOA ke Kadiv IT & Operasional.'
            );

            app(LoaServiceInterface::class)->dispatch(
                $ticket,
                ExternalLoaTransaction::LEVEL_KADIV_IT_OPS,
                ['role' => 'kadiv_it', 'title' => 'LOA Kadiv IT & Operasional']
            );

            return redirect()->route('tickets.show', $ticket)
                ->with('success', "Tiket {$ticket->ticket_number} disetujui sebagai Major. Pengajuan LOA diteruskan ke Kadiv IT & Operasional.");
        }

        if ($decision === 'rejected') {
            $stateMachine->transition(
                ticket: $ticket,
                toStatus: Ticket::STATUS_REJECTED_IT_HEAD,
                actor: $request->user(),
                action: 'IT_HEAD_REJECT',
                remarks: $notes
            );

            return redirect()->route('tickets.show', $ticket)
                ->with('success', "Tiket {$ticket->ticket_number} telah ditolak oleh Kepala Bagian IT.");
        }

        if ($decision === 'revision') {
            $stateMachine->transition(
                ticket: $ticket,
                toStatus: Ticket::STATUS_REVISION_BY_IT_HEAD,
                actor: $request->user(),
                action: 'IT_HEAD_REVISE',
                remarks: $notes
            );

            return redirect()->route('tickets.show', $ticket)
                ->with('success', "Tiket {$ticket->ticket_number} dikembalikan ke pemohon untuk revisi.");
        }

        return redirect()->route('tickets.show', $ticket);
    }

    public function selfAssign(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('selfAssign', $ticket);
        $user = $request->user();

        $claimed = DB::transaction(function () use ($ticket, $user) {
            $lockedTicket = Ticket::where('id', $ticket->id)
                ->where('status', Ticket::STATUS_READY_FOR_WORK)
                ->whereNull('assigned_to')
                ->lockForUpdate()
                ->first();

            if (! $lockedTicket) {
                return false;
            }

            $lockedTicket->update([
                'assigned_to' => $user->id,
                'status' => Ticket::STATUS_IN_PROGRESS,
                'work_started_at' => now(),
            ]);

            app(AuditTrailService::class)->log(
                ticket: $lockedTicket,
                actor: $user,
                action: 'IT_DEV_SELF_ASSIGN',
                fromStatus: Ticket::STATUS_READY_FOR_WORK,
                toStatus: Ticket::STATUS_IN_PROGRESS,
                remarks: "Tiket diambil mandiri (self-assign) oleh {$user->name}."
            );

            return true;
        });

        if (! $claimed) {
            return redirect()->route('tickets.show', $ticket)
                ->with('error', 'Tiket ini baru saja diambil oleh anggota Tim IT yang lain.');
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Anda berhasil mengambil tiket {$ticket->ticket_number}. Tiket kini berstatus Dalam Pengerjaan.");
    }

    public function completeWork(Request $request, Ticket $ticket, TicketStateMachine $stateMachine): RedirectResponse
    {
        $this->authorize('completeWork', $ticket);

        $completionNotes = $request->input('completion_notes', 'Pekerjaan teknis telah selesai.');

        TicketActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'activity_type' => 'work_completion',
            'content' => $completionNotes,
            'created_at' => now(),
        ]);

        $stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_WAITING_USER_REVIEW,
            actor: $request->user(),
            action: 'IT_DEV_COMPLETE_WORK',
            remarks: $completionNotes
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Pengerjaan tiket {$ticket->ticket_number} telah selesai dan diteruskan ke Pemohon untuk tahap review akhir.");
    }
}
