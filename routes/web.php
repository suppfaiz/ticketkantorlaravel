<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItHeadApprovalController;
use App\Http\Controllers\MeetingMinutesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SupervisorApprovalController;
use App\Http\Controllers\TicketActivityController;
use App\Http\Controllers\TicketAssignmentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifications', function (\Illuminate\Http\Request $request) {
        $authUser = $request->user();
        $query = \App\Models\Ticket::with(['requester.department', 'supervisor', 'assignedTo']);

        if ($authUser->isSupervisor()) {
            $query->where('supervisor_id', $authUser->id)
                ->where('status', \App\Models\Ticket::STATUS_WAITING_SUPERVISOR);
        } elseif ($authUser->isItHead() || $authUser->isAdmin()) {
            $query->whereIn('status', [
                \App\Models\Ticket::STATUS_WAITING_IT_HEAD,
                \App\Models\Ticket::STATUS_WAITING_MAJOR_MEETING,
                \App\Models\Ticket::STATUS_WAITING_MINUTES_APPROVAL,
            ]);
        } elseif ($authUser->isKadivIt()) {
            $query->where('status', \App\Models\Ticket::STATUS_WAITING_LOA_KADIV_IT);
        } elseif ($authUser->isDireksi()) {
            $query->where('status', \App\Models\Ticket::STATUS_WAITING_LOA_DIREKSI);
        } elseif ($authUser->isItDeveloper()) {
            $query->where(function ($q) use ($authUser) {
                $q->where('status', \App\Models\Ticket::STATUS_READY_FOR_WORK)
                    ->orWhere(function ($sq) use ($authUser) {
                        $sq->where('assigned_to', $authUser->id)
                            ->whereIn('status', [\App\Models\Ticket::STATUS_IN_PROGRESS, \App\Models\Ticket::STATUS_REVISION_WORK]);
                    });
            });
        } else {
            $query->where('requester_id', $authUser->id)
                ->whereIn('status', [
                    \App\Models\Ticket::STATUS_REVISION_REQUESTER,
                    \App\Models\Ticket::STATUS_REVISION_BY_IT_HEAD,
                    \App\Models\Ticket::STATUS_WAITING_USER_MINUTES,
                    \App\Models\Ticket::STATUS_REVISION_MINUTES,
                    \App\Models\Ticket::STATUS_WAITING_USER_REVIEW,
                    \App\Models\Ticket::STATUS_REJECTED_SUPERVISOR,
                    \App\Models\Ticket::STATUS_REJECTED_IT_HEAD,
                    \App\Models\Ticket::STATUS_REJECTED_KADIV_IT,
                    \App\Models\Ticket::STATUS_REJECTED_DIREKSI,
                    \App\Models\Ticket::STATUS_COMPLETED,
                ]);
        }

        $notifications = $query->latest('updated_at')->paginate(15);

        return view('notifications.index', compact('notifications'));
    })->name('notifications.index');

    Route::get('/tickets-calendar', [TicketController::class, 'calendar'])->name('tickets.calendar');
    Route::get('/tickets-export', [TicketController::class, 'exportCsv'])->name('tickets.export');
    Route::get('/tickets/{ticket}/print', [TicketController::class, 'print'])->name('tickets.print');
    Route::post('/tickets/{ticket}/submit-draft', [TicketController::class, 'submitDraft'])->name('tickets.submit-draft');
    Route::post('/tickets/{ticket}/cancel', [TicketController::class, 'cancel'])->name('tickets.cancel');
    Route::resource('tickets', TicketController::class);

    // Strictly Admin-Only User, Department & Request Category Management
    Route::middleware(['admin.only'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [\App\Http\Controllers\AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [\App\Http\Controllers\AdminUserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [\App\Http\Controllers\AdminUserController::class, 'update'])->name('users.update');
        Route::post('/departments', [\App\Http\Controllers\AdminUserController::class, 'storeDepartment'])->name('departments.store');
        Route::delete('/departments/{department}', [\App\Http\Controllers\AdminUserController::class, 'destroyDepartment'])->name('departments.destroy');
        Route::post('/categories', [\App\Http\Controllers\AdminUserController::class, 'storeCategory'])->name('categories.store');
        Route::patch('/categories/{category}', [\App\Http\Controllers\AdminUserController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [\App\Http\Controllers\AdminUserController::class, 'destroyCategory'])->name('categories.destroy');
    });

    // LOA Status Manual Sync
    Route::post('/tickets/{ticket}/sync-loa', [TicketController::class, 'syncLoaStatus'])
        ->name('tickets.sync-loa');
    Route::post('/tickets/{ticket}/it-head-decision', [TicketController::class, 'itHeadDecision'])
        ->name('tickets.it-head-decision');

    // Supervisor approval routes (Tahap B)
    Route::post('/tickets/{ticket}/supervisor-approve', [SupervisorApprovalController::class, 'approve'])
        ->name('tickets.supervisor-approve');
    Route::post('/tickets/{ticket}/supervisor-reject', [SupervisorApprovalController::class, 'reject'])
        ->name('tickets.supervisor-reject');
    Route::post('/tickets/{ticket}/supervisor-revise', [SupervisorApprovalController::class, 'revise'])
        ->name('tickets.supervisor-revise');

    // IT Head classification & decision routes (Tahap C)
    Route::post('/tickets/{ticket}/it-head-classify-approve', [ItHeadApprovalController::class, 'classifyAndApprove'])
        ->name('tickets.it-head-classify-approve');
    Route::post('/tickets/{ticket}/assign-and-timeline', [ItHeadApprovalController::class, 'updateAssignmentAndTimeline'])
        ->name('tickets.assign-and-timeline');
    Route::post('/tickets/{ticket}/it-head-reject', [ItHeadApprovalController::class, 'reject'])
        ->name('tickets.it-head-reject');
    Route::post('/tickets/{ticket}/it-head-revise', [ItHeadApprovalController::class, 'revise'])
        ->name('tickets.it-head-revise');

    // Executive LOA approval routes (Tahap C.1 Kadiv IT & Tahap C.2 Direksi)
    Route::post('/tickets/{ticket}/kadiv-approve', [\App\Http\Controllers\ExecutiveLoaApprovalController::class, 'kadivApprove'])
        ->name('tickets.kadiv-approve');
    Route::post('/tickets/{ticket}/kadiv-reject', [\App\Http\Controllers\ExecutiveLoaApprovalController::class, 'kadivReject'])
        ->name('tickets.kadiv-reject');
    Route::post('/tickets/{ticket}/direksi-approve', [\App\Http\Controllers\ExecutiveLoaApprovalController::class, 'direksiApprove'])
        ->name('tickets.direksi-approve');
    Route::post('/tickets/{ticket}/direksi-reject', [\App\Http\Controllers\ExecutiveLoaApprovalController::class, 'direksiReject'])
        ->name('tickets.direksi-reject');

    // Post-Direksi Major Meeting & Minutes routes (Tahap C.3, C.4, C.5)
    Route::post('/tickets/{ticket}/schedule-meeting', [\App\Http\Controllers\TicketMeetingController::class, 'scheduleMeeting'])
        ->name('tickets.schedule-meeting');
    Route::post('/tickets/{ticket}/submit-minutes', [\App\Http\Controllers\TicketMeetingController::class, 'submitMinutes'])
        ->name('tickets.submit-minutes');
    Route::post('/tickets/{ticket}/approve-minutes', [\App\Http\Controllers\TicketMeetingController::class, 'approveMinutes'])
        ->name('tickets.approve-minutes');
    Route::post('/tickets/{ticket}/revise-minutes', [\App\Http\Controllers\TicketMeetingController::class, 'reviseMinutes'])
        ->name('tickets.revise-minutes');

    // Meeting Minutes routes (Tahap C Major)
    Route::get('/tickets/{ticket}/meeting-minutes/create', [MeetingMinutesController::class, 'create'])
        ->name('tickets.meeting-minutes.create');
    Route::post('/tickets/{ticket}/meeting-minutes', [MeetingMinutesController::class, 'store'])
        ->name('tickets.meeting-minutes.store');
    Route::post('/tickets/{ticket}/meeting-minutes/approve', [MeetingMinutesController::class, 'approve'])
        ->name('tickets.meeting-minutes.approve');

    // IT Developer Queue & Execution routes (Tahap D)
    Route::post('/tickets/{ticket}/self-assign', [TicketAssignmentController::class, 'selfAssign'])
        ->name('tickets.self-assign');
    Route::post('/tickets/{ticket}/release-to-pool', [TicketAssignmentController::class, 'releaseToPool'])
        ->name('tickets.release-to-pool');
    Route::post('/tickets/{ticket}/activities', [TicketActivityController::class, 'store'])
        ->name('tickets.activities.store');
    Route::post('/tickets/{ticket}/complete-work', [TicketActivityController::class, 'complete'])
        ->name('tickets.complete-work');

    // User Review & Closure routes (Tahap E)
    Route::post('/tickets/{ticket}/user-review', [TicketReviewController::class, 'store'])
        ->name('tickets.user-review');
    Route::post('/tickets/{ticket}/reviews/accept', [TicketReviewController::class, 'accept'])
        ->name('tickets.reviews.accept');
    Route::post('/tickets/{ticket}/reviews/revise', [TicketReviewController::class, 'revise'])
        ->name('tickets.reviews.revise');

    // Secure attachments download & delete
    Route::get('/tickets/{ticket}/attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('tickets.attachments.download');
    Route::delete('/tickets/{ticket}/attachments/{attachment}', [AttachmentController::class, 'destroy'])
        ->name('tickets.attachments.destroy');
    Route::get('/tickets/{ticket}/meeting-minutes/download', [AttachmentController::class, 'downloadMeetingMinutes'])
        ->name('tickets.meeting-minutes.download');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Ticket Forum Discussion & Chat (Rate-limited for AJAX safety)
    Route::middleware(['throttle:60,1'])->group(function () {
        Route::post('/tickets/{ticket}/discussions', [\App\Http\Controllers\ChatController::class, 'storeDiscussion'])
            ->name('tickets.discussions.store');
        Route::get('/chat/messages', [\App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
        Route::post('/chat/messages', [\App\Http\Controllers\ChatController::class, 'store'])->name('chat.store');
    });
});

require __DIR__.'/auth.php';

