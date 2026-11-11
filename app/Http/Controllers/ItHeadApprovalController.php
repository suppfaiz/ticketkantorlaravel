<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItHeadClassifyRequest;
use App\Http\Requests\ItHeadRejectRequest;
use App\Http\Requests\ItHeadReviseRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\TicketStateMachine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ItHeadApprovalController extends Controller
{
    protected TicketStateMachine $stateMachine;
    protected AuditTrailService $auditTrailService;

    public function __construct(TicketStateMachine $stateMachine, AuditTrailService $auditTrailService)
    {
        $this->stateMachine = $stateMachine;
        $this->auditTrailService = $auditTrailService;
    }

    public function classifyAndApprove(ItHeadClassifyRequest $request, Ticket $ticket): RedirectResponse
    {
        $classification = $request->input('classification');
        $priority = $request->input('priority');
        $targetCompletionAt = $request->input('target_completion_at');
        $assignedToId = $request->input('assigned_to');

        $updates = ['classification' => $classification];
        if ($priority) {
            $updates['priority'] = $priority;
        }
        if ($targetCompletionAt) {
            $updates['target_completion_at'] = \Carbon\Carbon::parse($targetCompletionAt)->endOfDay();
        }

        $assignedDev = null;
        if ($assignedToId) {
            $assignedDev = User::where('id', (int) $assignedToId)
                ->where('role', User::ROLE_IT_DEVELOPER)
                ->where('is_active', true)
                ->first();
            if ($assignedDev) {
                $updates['assigned_to'] = $assignedDev->id;
            }
        }

        $ticket->update($updates);

        if ($classification === Ticket::CLASSIFICATION_MINOR) {
            if ($assignedDev) {
                $ticket->update([
                    'approved_by_it_head_at' => now(),
                    'work_started_at' => now(),
                ]);

                $this->stateMachine->transition(
                    ticket: $ticket,
                    toStatus: Ticket::STATUS_IN_PROGRESS,
                    actor: $request->user(),
                    action: 'IT_HEAD_CLASSIFY_MINOR_AND_ASSIGN',
                    remarks: ($request->input('notes') ? $request->input('notes') . ' — ' : '') . "Ditugaskan langsung ke {$assignedDev->name}."
                );

                return redirect()->route('tickets.show', $ticket)
                    ->with('success', "Tiket {$ticket->ticket_number} disetujui sebagai Minor dan langsung ditugaskan ke {$assignedDev->name}.");
            }

            $this->stateMachine->transition(
                ticket: $ticket,
                toStatus: Ticket::STATUS_READY_FOR_WORK,
                actor: $request->user(),
                action: 'IT_HEAD_CLASSIFY_MINOR',
                remarks: $request->input('notes') ?? 'Klasifikasi Minor disetujui, tiket langsung masuk antrean Siap Dikerjakan.'
            );

            return redirect()->route('tickets.show', $ticket)
                ->with('success', "Tiket {$ticket->ticket_number} berhasil diklasifikasikan sebagai Minor dan masuk antrean Siap Dikerjakan.");
        }

        // Major Classification -> Requires LOA Kadiv IT & Operasional (Tahap C.1)
        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_WAITING_LOA_KADIV_IT,
            actor: $request->user(),
            action: 'IT_HEAD_CLASSIFY_MAJOR',
            remarks: $request->input('notes') ?? 'Klasifikasi Major disetujui, mengajukan LOA ke Kadiv IT & Operasional.'
        );

        app(\App\Services\Contracts\LoaServiceInterface::class)->dispatch(
            $ticket,
            \App\Models\ExternalLoaTransaction::LEVEL_KADIV_IT_OPS,
            ['role' => 'kadiv_it', 'title' => 'LOA Kadiv IT & Operasional']
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} diklasifikasikan sebagai Major. Pengajuan LOA diteruskan ke Kepala Divisi IT & Operasional.");
    }

    /**
     * Penetapan / Perubahan Target Timeline Pengerjaan & Penugasan/Re-assign Teknisi oleh Kabag IT / Admin.
     */
    public function updateAssignmentAndTimeline(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('manageAssignmentAndTimeline', $ticket);

        $validated = $request->validate([
            'target_completion_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $changes = [];
        $updates = [];

        if (! empty($validated['supervisor_id']) && (int) $validated['supervisor_id'] !== (int) $ticket->supervisor_id) {
            $newSup = User::where('id', (int) $validated['supervisor_id'])
                ->where('is_active', true)
                ->first();
            if ($newSup) {
                $updates['supervisor_id'] = $newSup->id;
                $changes[] = "Atasan peninjau dialihkan ke: {$newSup->name}";
            }
        }

        $newTimeline = ! empty($validated['target_completion_at'])
            ? \Carbon\Carbon::parse($validated['target_completion_at'])->endOfDay()
            : null;

        $oldTimelineStr = $ticket->target_completion_at?->format('d/m/Y');
        $newTimelineStr = $newTimeline?->format('d/m/Y');

        if ($oldTimelineStr !== $newTimelineStr) {
            $updates['target_completion_at'] = $newTimeline;
            $changes[] = $newTimelineStr
                ? "Target timeline ditetapkan: {$newTimelineStr}"
                : 'Target timeline dikosongkan';
        }

        $oldAssignedId = $ticket->assigned_to ? (int) $ticket->assigned_to : null;
        $newAssignedId = ! empty($validated['assigned_to']) ? (int) $validated['assigned_to'] : null;
        $newDev = null;

        if ($newAssignedId !== $oldAssignedId) {
            if ($newAssignedId) {
                $newDev = User::where('id', $newAssignedId)
                    ->where('role', User::ROLE_IT_DEVELOPER)
                    ->where('is_active', true)
                    ->first();

                if ($newDev) {
                    $updates['assigned_to'] = $newDev->id;
                    $changes[] = "Teknisi ditugaskan ke: {$newDev->name}";
                }
            } else {
                $updates['assigned_to'] = null;
                $changes[] = 'Penugasan teknisi dilepas kembali ke antrean';
            }
        }

        if (empty($updates) && empty($validated['notes'])) {
            return redirect()->route('tickets.show', $ticket)
                ->with('info', 'Tidak ada perubahan timeline atau teknisi yang disimpan.');
        }

        // If ticket was waiting in Ready Pool and Kabag IT assigns a developer, transition to In Progress
        if ($newDev && $ticket->status === Ticket::STATUS_READY_FOR_WORK) {
            $updates['work_started_at'] = $ticket->work_started_at ?? now();
            $ticket->update($updates);

            $remarkText = implode('; ', $changes);
            if (! empty($validated['notes'])) {
                $remarkText .= ' — Catatan: ' . trim($validated['notes']);
            }

            $this->stateMachine->transition(
                ticket: $ticket,
                toStatus: Ticket::STATUS_IN_PROGRESS,
                actor: $request->user(),
                action: 'IT_HEAD_ASSIGN_DEVELOPER',
                remarks: $remarkText ?: "Tiket ditugaskan oleh Kabag IT kepada {$newDev->name}."
            );

            return redirect()->route('tickets.show', $ticket)
                ->with('success', "Teknisi {$newDev->name} berhasil ditugaskan dan status tiket beralih ke Dalam Pengerjaan.");
        }

        $ticket->update($updates);

        $remarkText = implode('; ', $changes);
        if (! empty($validated['notes'])) {
            $remarkText .= ($remarkText ? ' — ' : '') . 'Catatan: ' . trim($validated['notes']);
        }

        $this->auditTrailService->log(
            ticket: $ticket,
            actor: $request->user(),
            action: 'IT_HEAD_UPDATE_TIMELINE_ASSIGNMENT',
            fromStatus: $ticket->status,
            toStatus: $ticket->status,
            remarks: $remarkText ?: 'Pengaturan timeline/teknisi diperbarui oleh Kabag IT.'
        );

        if ($newDev) {
            try {
                \Illuminate\Support\Facades\Mail::to($newDev->email)->send(
                    new \App\Mail\TicketStatusMail($ticket, $newDev->name, $request->user(), $remarkText)
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Gagal mengirim email penugasan teknisi: ' . $e->getMessage());
            }
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Pengaturan Timeline & Penugasan Teknisi berhasil diperbarui.');
    }

    public function reject(ItHeadRejectRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_REJECTED_IT_HEAD,
            actor: $request->user(),
            action: 'IT_HEAD_REJECT',
            remarks: $request->input('reason')
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} telah ditolak oleh Kepala Bagian IT.");
    }

    public function revise(ItHeadReviseRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->stateMachine->transition(
            ticket: $ticket,
            toStatus: Ticket::STATUS_REVISION_BY_IT_HEAD,
            actor: $request->user(),
            action: 'IT_HEAD_REQUEST_REVISION',
            remarks: $request->input('revision_notes')
        );

        return redirect()->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} dikembalikan ke pemohon untuk revisi. Catatan: Pengajuan ulang akan kembali melalui persetujuan Atasan Langsung.");
    }
}
