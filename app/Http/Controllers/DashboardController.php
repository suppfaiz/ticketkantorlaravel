<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isSupervisor()) {
            $counts = [
                'pending_approval' => Ticket::where('supervisor_id', $user->id)
                    ->where('status', Ticket::STATUS_WAITING_SUPERVISOR)->count(),
                'approved' => Ticket::where('supervisor_id', $user->id)
                    ->whereNotNull('approved_by_supervisor_at')->count(),
                'revision' => Ticket::where('supervisor_id', $user->id)
                    ->where('status', Ticket::STATUS_REVISION_REQUESTER)->count(),
                'rejected' => Ticket::where('supervisor_id', $user->id)
                    ->where('status', Ticket::STATUS_REJECTED_SUPERVISOR)->count(),
            ];

            $pendingTickets = Ticket::where('supervisor_id', $user->id)
                ->where('status', Ticket::STATUS_WAITING_SUPERVISOR)
                ->with(['requester.department'])
                ->latest()
                ->paginate(10);

            return view('dashboard.supervisor', compact('counts', 'pendingTickets'));
        }

        if ($user->isItHead() || $user->isAdmin()) {
            $counts = [
                'waiting_it_head' => Ticket::where('status', Ticket::STATUS_WAITING_IT_HEAD)->count(),
                'waiting_meeting' => Ticket::whereIn('status', [
                    Ticket::STATUS_WAITING_MAJOR_MEETING,
                    Ticket::STATUS_WAITING_MINUTES,
                    Ticket::STATUS_WAITING_MINUTES_APPROVAL,
                ])->count(),
                'active_development' => Ticket::whereIn('status', [
                    Ticket::STATUS_READY_FOR_WORK,
                    Ticket::STATUS_IN_PROGRESS,
                    Ticket::STATUS_REVISION_WORK,
                ])->count(),
                'completed' => Ticket::where('status', Ticket::STATUS_COMPLETED)->count(),
                'overdue' => Ticket::whereNotNull('target_completion_at')
                    ->where('target_completion_at', '<', now())
                    ->whereNotIn('status', [
                        Ticket::STATUS_COMPLETED,
                        Ticket::STATUS_CANCELLED,
                        Ticket::STATUS_WAITING_USER_REVIEW,
                        Ticket::STATUS_REJECTED_SUPERVISOR,
                        Ticket::STATUS_REJECTED_IT_HEAD,
                        Ticket::STATUS_REJECTED_KADIV_IT,
                        Ticket::STATUS_REJECTED_DIREKSI,
                    ])->count(),
            ];

            $actionableTickets = Ticket::whereIn('status', [
                Ticket::STATUS_WAITING_IT_HEAD,
                Ticket::STATUS_WAITING_MAJOR_MEETING,
                Ticket::STATUS_WAITING_MINUTES_APPROVAL,
            ])
            ->with(['requester.department', 'supervisor'])
            ->latest()
            ->paginate(10);

            $categoryStats = Ticket::selectRaw('category, COUNT(*) as total')
                ->whereNotNull('category')
                ->groupBy('category')
                ->orderByDesc('total')
                ->get();

            $departmentStats = \App\Models\Department::query()
                ->withCount(['users as tickets_count' => function ($q) {
                    $q->join('tickets', 'tickets.requester_id', '=', 'users.id');
                }])
                ->orderByDesc('tickets_count')
                ->get();

            $developerWorkloads = \App\Models\User::where('role', \App\Models\User::ROLE_IT_DEVELOPER)
                ->where('is_active', true)
                ->withCount([
                    'assignedTickets as active_count' => function ($q) {
                        $q->whereIn('status', [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_REVISION_WORK]);
                    },
                    'assignedTickets as completed_count' => function ($q) {
                        $q->whereIn('status', [Ticket::STATUS_WAITING_USER_REVIEW, Ticket::STATUS_COMPLETED]);
                    },
                ])
                ->orderBy('name')
                ->get();

            return view('dashboard.it_head', compact(
                'counts',
                'actionableTickets',
                'categoryStats',
                'departmentStats',
                'developerWorkloads'
            ));
        }

        if ($user->isItDeveloper()) {
            $counts = [
                'ready_pool' => Ticket::where('status', Ticket::STATUS_READY_FOR_WORK)
                    ->whereNull('assigned_to')->count(),
                'my_in_progress' => Ticket::where('assigned_to', $user->id)
                    ->where('status', Ticket::STATUS_IN_PROGRESS)->count(),
                'my_revisions' => Ticket::where('assigned_to', $user->id)
                    ->where('status', Ticket::STATUS_REVISION_WORK)->count(),
                'my_completed' => Ticket::where('assigned_to', $user->id)
                    ->whereIn('status', [Ticket::STATUS_WAITING_USER_REVIEW, Ticket::STATUS_COMPLETED])->count(),
            ];

            $myActiveTickets = Ticket::where('assigned_to', $user->id)
                ->whereIn('status', [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_REVISION_WORK])
                ->with(['requester.department'])
                ->latest()
                ->get();

            $availablePool = Ticket::where('status', Ticket::STATUS_READY_FOR_WORK)
                ->whereNull('assigned_to')
                ->with(['requester.department'])
                ->latest()
                ->paginate(10);

            return view('dashboard.it_developer', compact('counts', 'myActiveTickets', 'availablePool'));
        }

        if ($user->isKadivIt()) {
            $counts = [
                'waiting_kadiv' => Ticket::where('status', Ticket::STATUS_WAITING_LOA_KADIV_IT)->count(),
                'forwarded_direksi' => Ticket::where('status', Ticket::STATUS_WAITING_LOA_DIREKSI)->count(),
                'completed_major' => Ticket::where('classification', Ticket::CLASSIFICATION_MAJOR)
                    ->where('status', Ticket::STATUS_COMPLETED)->count(),
            ];

            $majorTickets = Ticket::where('status', Ticket::STATUS_WAITING_LOA_KADIV_IT)
                ->with(['requester.department'])
                ->latest()
                ->paginate(10);

            return view('dashboard.kadiv_it', compact('counts', 'majorTickets'));
        }

        if ($user->isDireksi()) {
            $counts = [
                'waiting_direksi' => Ticket::where('status', Ticket::STATUS_WAITING_LOA_DIREKSI)->count(),
                'in_progress_major' => Ticket::where('classification', Ticket::CLASSIFICATION_MAJOR)
                    ->whereIn('status', [
                        Ticket::STATUS_WAITING_MAJOR_MEETING,
                        Ticket::STATUS_WAITING_USER_MINUTES,
                        Ticket::STATUS_WAITING_MINUTES_APPROVAL,
                        Ticket::STATUS_READY_FOR_WORK,
                        Ticket::STATUS_IN_PROGRESS,
                    ])->count(),
                'completed_major' => Ticket::where('classification', Ticket::CLASSIFICATION_MAJOR)
                    ->where('status', Ticket::STATUS_COMPLETED)->count(),
            ];

            $direksiTickets = Ticket::where('status', Ticket::STATUS_WAITING_LOA_DIREKSI)
                ->with(['requester.department'])
                ->latest()
                ->paginate(10);

            return view('dashboard.direksi', compact('counts', 'direksiTickets'));
        }

        // Default: Requester Dashboard
        $counts = [
            'total' => Ticket::where('requester_id', $user->id)->count(),
            'waiting_supervisor' => Ticket::where('requester_id', $user->id)
                ->where('status', Ticket::STATUS_WAITING_SUPERVISOR)->count(),
            'in_progress' => Ticket::where('requester_id', $user->id)
                ->whereIn('status', [
                    Ticket::STATUS_READY_FOR_WORK,
                    Ticket::STATUS_IN_PROGRESS,
                    Ticket::STATUS_REVISION_WORK,
                ])->count(),
            'completed' => Ticket::where('requester_id', $user->id)
                ->where('status', Ticket::STATUS_COMPLETED)->count(),
        ];

        $recentTickets = Ticket::where('requester_id', $user->id)
            ->with(['supervisor', 'assignedTo'])
            ->latest()
            ->paginate(10);

        return view('dashboard.requester', compact('counts', 'recentTickets'));
    }
}
