<?php

namespace App\Services;

use App\Models\ApprovalDelegation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TicketStateMachine
{
    protected AuditTrailService $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    /**
     * Validate and transition a ticket to a new status.
     *
     * @throws ValidationException
     */
    public function transition(
        Ticket $ticket,
        string $toStatus,
        ?User $actor = null,
        string $action = 'status_change',
        ?string $remarks = null,
        ?array $payloadDiff = null,
        ?int $externalTransactionId = null
    ): Ticket {
        $fromStatus = $ticket->status;

        // Terminal states check
        if (in_array($fromStatus, [
            Ticket::STATUS_COMPLETED,
            Ticket::STATUS_CANCELLED,
            Ticket::STATUS_REJECTED_SUPERVISOR,
            Ticket::STATUS_REJECTED_IT_HEAD,
            Ticket::STATUS_REJECTED_KADIV_IT,
            Ticket::STATUS_REJECTED_DIREKSI,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => "Tiket telah berstatus final ({$fromStatus}) dan tidak dapat diubah statusnya lagi.",
            ]);
        }

        // Validate allowed transitions
        if (! $this->isTransitionAllowed($fromStatus, $toStatus)) {
            throw ValidationException::withMessages([
                'status' => "Transisi status tidak sah dari '{$fromStatus}' ke '{$toStatus}'.",
            ]);
        }

        // Validate role authority for this transition if actor provided
        if ($actor) {
            $this->validateActorAuthority($ticket, $actor, $fromStatus, $toStatus);
        }

        // Update timestamps based on state
        $updates = ['status' => $toStatus];

        if ($toStatus === Ticket::STATUS_WAITING_SUPERVISOR && $fromStatus === Ticket::STATUS_DRAFT) {
            $updates['submitted_at'] = now();
        } elseif ($toStatus === Ticket::STATUS_WAITING_IT_HEAD) {
            $updates['approved_by_supervisor_at'] = now();
        } elseif ($toStatus === Ticket::STATUS_READY_FOR_WORK && $ticket->classification === Ticket::CLASSIFICATION_MINOR) {
            $updates['approved_by_it_head_at'] = now();
        } elseif ($toStatus === Ticket::STATUS_WAITING_LOA_KADIV_IT) {
            $updates['approved_by_it_head_at'] = now();
        } elseif ($toStatus === Ticket::STATUS_WAITING_LOA_DIREKSI) {
            $updates['approved_by_kadiv_at'] = now();
        } elseif ($toStatus === Ticket::STATUS_WAITING_MAJOR_MEETING) {
            if ($fromStatus === Ticket::STATUS_WAITING_LOA_DIREKSI) {
                $updates['approved_by_direksi_at'] = now();
            } elseif ($fromStatus === Ticket::STATUS_WAITING_IT_HEAD) {
                $updates['approved_by_it_head_at'] = now();
            }
        } elseif ($toStatus === Ticket::STATUS_WAITING_USER_MINUTES) {
            $updates['meeting_scheduled_at'] = now();
        } elseif ($toStatus === Ticket::STATUS_WAITING_MINUTES_APPROVAL) {
            $updates['minutes_submitted_at'] = now();
        } elseif (in_array($toStatus, [Ticket::STATUS_READY_FOR_WORK, Ticket::STATUS_IN_PROGRESS], true) && $fromStatus === Ticket::STATUS_WAITING_MINUTES_APPROVAL) {
            $updates['minutes_approved_at'] = now();
            if ($toStatus === Ticket::STATUS_IN_PROGRESS) {
                $updates['work_started_at'] = $ticket->work_started_at ?? now();
            }
        } elseif ($toStatus === Ticket::STATUS_IN_PROGRESS && $fromStatus === Ticket::STATUS_READY_FOR_WORK) {
            $updates['work_started_at'] = $ticket->work_started_at ?? now();
        } elseif ($toStatus === Ticket::STATUS_WAITING_USER_REVIEW) {
            $updates['work_completed_at'] = now();
        } elseif ($toStatus === Ticket::STATUS_COMPLETED || $toStatus === Ticket::STATUS_CANCELLED) {
            $updates['closed_at'] = now();
        }

        $ticket->update($updates);

        // Log audit trail
        $this->auditTrailService->log(
            ticket: $ticket,
            actor: $actor,
            action: $action,
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            remarks: $remarks,
            payloadDiff: $payloadDiff,
            externalTransactionId: $externalTransactionId,
            commentsOrReason: $remarks
        );

        $this->dispatchStatusNotification($ticket, $toStatus, $actor, $remarks);

        return $ticket;
    }

    /**
     * Send email notifications safely to the appropriate role/recipient for the new ticket status.
     */
    public function dispatchStatusNotification(Ticket $ticket, string $toStatus, ?User $actor = null, ?string $remarks = null): void
    {
        app(TicketNotificationService::class)->statusChanged($ticket, $toStatus, $actor, $remarks);
    }

    public function isTransitionAllowed(string $from, string $to): bool
    {
        $allowedTransitions = [
            Ticket::STATUS_DRAFT => [
                Ticket::STATUS_WAITING_SUPERVISOR,
                Ticket::STATUS_WAITING_IT_HEAD,
                Ticket::STATUS_CANCELLED,
            ],
            Ticket::STATUS_WAITING_SUPERVISOR => [
                Ticket::STATUS_WAITING_IT_HEAD,
                Ticket::STATUS_REJECTED_SUPERVISOR,
                Ticket::STATUS_REVISION_REQUESTER,
                Ticket::STATUS_CANCELLED,
            ],
            Ticket::STATUS_REVISION_REQUESTER => [
                Ticket::STATUS_WAITING_SUPERVISOR,
                Ticket::STATUS_WAITING_IT_HEAD,
                Ticket::STATUS_CANCELLED,
            ],
            Ticket::STATUS_WAITING_IT_HEAD => [
                Ticket::STATUS_READY_FOR_WORK,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_WAITING_LOA_KADIV_IT,
                Ticket::STATUS_WAITING_MAJOR_MEETING,
                Ticket::STATUS_REJECTED_IT_HEAD,
                Ticket::STATUS_REVISION_BY_IT_HEAD,
                Ticket::STATUS_CANCELLED,
            ],
            Ticket::STATUS_REVISION_BY_IT_HEAD => [
                Ticket::STATUS_WAITING_SUPERVISOR,
                Ticket::STATUS_WAITING_IT_HEAD,
                Ticket::STATUS_CANCELLED,
            ],
            Ticket::STATUS_WAITING_LOA_KADIV_IT => [
                Ticket::STATUS_WAITING_LOA_DIREKSI,
                Ticket::STATUS_REJECTED_KADIV_IT,
            ],
            Ticket::STATUS_WAITING_LOA_DIREKSI => [
                Ticket::STATUS_WAITING_MAJOR_MEETING,
                Ticket::STATUS_REJECTED_DIREKSI,
            ],
            Ticket::STATUS_WAITING_MAJOR_MEETING => [
                Ticket::STATUS_WAITING_USER_MINUTES,
                Ticket::STATUS_WAITING_MINUTES_APPROVAL,
            ],
            Ticket::STATUS_WAITING_MINUTES => [
                Ticket::STATUS_WAITING_MINUTES_APPROVAL,
                Ticket::STATUS_WAITING_USER_MINUTES,
            ],
            Ticket::STATUS_WAITING_USER_MINUTES => [
                Ticket::STATUS_WAITING_MINUTES_APPROVAL,
            ],
            Ticket::STATUS_WAITING_MINUTES_APPROVAL => [
                Ticket::STATUS_READY_FOR_WORK,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_REVISION_MINUTES,
                Ticket::STATUS_WAITING_MAJOR_MEETING,
            ],
            Ticket::STATUS_REVISION_MINUTES => [
                Ticket::STATUS_WAITING_MINUTES_APPROVAL,
            ],
            Ticket::STATUS_READY_FOR_WORK => [Ticket::STATUS_IN_PROGRESS],
            Ticket::STATUS_IN_PROGRESS => [
                Ticket::STATUS_WAITING_USER_REVIEW,
                Ticket::STATUS_READY_FOR_WORK,
            ],
            Ticket::STATUS_WAITING_USER_REVIEW => [
                Ticket::STATUS_COMPLETED,
                Ticket::STATUS_REVISION_WORK,
            ],
            Ticket::STATUS_REVISION_WORK => [
                Ticket::STATUS_WAITING_USER_REVIEW,
                Ticket::STATUS_READY_FOR_WORK,
            ],
        ];

        return in_array($to, $allowedTransitions[$from] ?? [], true);
    }

    protected function validateActorAuthority(Ticket $ticket, User $actor, string $from, string $to): void
    {
        if ($to === Ticket::STATUS_CANCELLED) {
            if ($ticket->requester_id !== $actor->id && ! $actor->isAdmin()) {
                throw ValidationException::withMessages([
                    'authorization' => 'Hanya Pemohon tiket atau Administrator yang berhak membatalkan permohonan ini.',
                ]);
            }

            return;
        }

        // Transition: Supervisor decision
        if ($from === Ticket::STATUS_WAITING_SUPERVISOR) {
            $supervisor = $ticket->supervisor;
            $canAct = $supervisor && app(ApprovalDelegationService::class)
                ->canActFor($actor, $supervisor, ApprovalDelegation::SCOPE_SUPERVISOR);

            if (! $canAct && ! $actor->isAdmin()) {
                throw ValidationException::withMessages([
                    'authorization' => 'Hanya Atasan Langsung dari pemohon yang berhak memproses persetujuan ini.',
                ]);
            }
        }

        // Transition: IT Head decision
        if ($from === Ticket::STATUS_WAITING_IT_HEAD || $from === Ticket::STATUS_WAITING_MINUTES_APPROVAL) {
            if (! app(ApprovalDelegationService::class)->actsForRole($actor, User::ROLE_IT_HEAD, ApprovalDelegation::SCOPE_IT_HEAD) && ! $actor->isAdmin()) {
                throw ValidationException::withMessages([
                    'authorization' => 'Hanya Kepala Bagian IT yang berwenang mengambil keputusan ini.',
                ]);
            }
        }

        // Transition: Kadiv IT decision (Tahap C.1)
        if ($from === Ticket::STATUS_WAITING_LOA_KADIV_IT) {
            if (! app(ApprovalDelegationService::class)->actsForRole($actor, User::ROLE_KADIV_IT, ApprovalDelegation::SCOPE_KADIV_IT) && ! $actor->isAdmin()) {
                throw ValidationException::withMessages([
                    'authorization' => 'Hanya Kepala Divisi IT dan Operasional yang berwenang memproses persetujuan LOA ini.',
                ]);
            }
        }

        // Transition: Direksi decision (Tahap C.2)
        if ($from === Ticket::STATUS_WAITING_LOA_DIREKSI) {
            if (! app(ApprovalDelegationService::class)->actsForRole($actor, User::ROLE_DIREKSI, ApprovalDelegation::SCOPE_DIREKSI) && ! $actor->isAdmin()) {
                throw ValidationException::withMessages([
                    'authorization' => 'Hanya Dewan Direksi yang berwenang memproses persetujuan LOA ini.',
                ]);
            }
        }

        // Transition: User Review sign-off
        if ($from === Ticket::STATUS_WAITING_USER_REVIEW) {
            if ($ticket->requester_id !== $actor->id && ! $actor->isItHead() && ! $actor->isAdmin()) {
                throw ValidationException::withMessages([
                    'authorization' => 'Hanya Karyawan pemohon (Requester) atau Kepala Bagian IT yang berhak melakukan verifikasi dan menutup tiket.',
                ]);
            }
        }
    }
}
