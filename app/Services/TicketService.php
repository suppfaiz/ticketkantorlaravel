<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TicketService
{
    protected AuditTrailService $auditTrailService;
    protected TicketStateMachine $stateMachine;

    public function __construct(AuditTrailService $auditTrailService, TicketStateMachine $stateMachine)
    {
        $this->auditTrailService = $auditTrailService;
        $this->stateMachine = $stateMachine;
    }

    /**
     * Generate collision-safe ticket number: TICK-YYYYMMDD-XXXX
     */
    public function generateTicketNumber(): string
    {
        $todayPrefix = 'TICK-' . date('Ymd') . '-';

        return DB::transaction(function () use ($todayPrefix) {
            $latest = Ticket::where('ticket_number', 'LIKE', $todayPrefix . '%')
                ->lockForUpdate()
                ->orderByDesc('ticket_number')
                ->value('ticket_number');

            if ($latest) {
                $lastSequence = (int) substr($latest, -4);
                $newSequence = str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);
            } else {
                $newSequence = '0001';
            }

            return $todayPrefix . $newSequence;
        });
    }

    /**
     * Create and submit a new ticket.
     *
     * @param array<string, mixed> $data
     * @param array<UploadedFile> $attachments
     * @throws ValidationException
     */
    public function createTicket(User $requester, array $data, array $attachments = []): Ticket
    {
        $bypassesSupervisor = ($requester->isSupervisor() || $requester->isAdmin()) && ! $requester->supervisor_id;

        if (! $requester->supervisor_id && ! $bypassesSupervisor) {
            throw ValidationException::withMessages([
                'supervisor' => 'Profil Anda belum memiliki Atasan Langsung yang terdaftar. Hubungi Administrator untuk konfigurasi data atasan.',
            ]);
        }

        return DB::transaction(function () use ($requester, $data, $attachments, $bypassesSupervisor) {
            $isDraft = ($data['is_draft'] ?? false) === true;
            $status = $isDraft
                ? Ticket::STATUS_DRAFT
                : ($bypassesSupervisor ? Ticket::STATUS_WAITING_IT_HEAD : Ticket::STATUS_WAITING_SUPERVISOR);

            $ticket = Ticket::create([
                'ticket_number' => $this->generateTicketNumber(),
                'requester_id' => $requester->id,
                'supervisor_id' => $requester->supervisor_id ?? $requester->id,
                'category' => $data['category'],
                'priority' => $data['priority'] ?? 'Low',
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => $status,
                'submitted_at' => $isDraft ? null : now(),
                'approved_by_supervisor_at' => (! $isDraft && $bypassesSupervisor) ? now() : null,
            ]);

            // Save attachments to private storage
            foreach ($attachments as $file) {
                if ($file instanceof UploadedFile) {
                    $storedPath = $file->store('tickets/' . $ticket->id, 'local');
                    TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'uploader_id' => $requester->id,
                        'file_name' => $file->getClientOriginalName(),
                        'stored_path' => $storedPath,
                        'file_size_bytes' => $file->getSize(),
                        'mime_type' => $file->getClientMimeType(),
                        'created_at' => now(),
                    ]);
                }
            }

            // Record audit log
            $this->auditTrailService->log(
                ticket: $ticket,
                actor: $requester,
                action: $isDraft ? 'SAVE_DRAFT' : 'SUBMIT_TICKET',
                fromStatus: null,
                toStatus: $status,
                remarks: $isDraft ? 'Tiket disimpan sebagai draf' : 'Tiket baru berhasil diajukan'
            );

            if (! $isDraft) {
                $this->stateMachine->dispatchStatusNotification(
                    $ticket,
                    $status,
                    $requester,
                    'Permohonan tiket baru menunggu persetujuan Atasan Langsung.'
                );
            }

            return $ticket;
        });
    }

    /**
     * Resubmit/update ticket post-revision.
     *
     * @param array<string, mixed> $data
     * @param array<UploadedFile> $attachments
     */
    public function updateTicket(Ticket $ticket, User $requester, array $data, array $attachments = []): Ticket
    {
        return DB::transaction(function () use ($ticket, $requester, $data, $attachments) {
            $ticket->update([
                'category' => $data['category'] ?? $ticket->category,
                'priority' => $data['priority'] ?? $ticket->priority,
                'title' => $data['title'] ?? $ticket->title,
                'description' => $data['description'] ?? $ticket->description,
            ]);

            // Save new attachments
            foreach ($attachments as $file) {
                if ($file instanceof UploadedFile) {
                    $storedPath = $file->store('tickets/' . $ticket->id, 'local');
                    TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'uploader_id' => $requester->id,
                        'file_name' => $file->getClientOriginalName(),
                        'stored_path' => $storedPath,
                        'file_size_bytes' => $file->getSize(),
                        'mime_type' => $file->getClientMimeType(),
                        'created_at' => now(),
                    ]);
                }
            }

            // Resubmit if in revision status
            if ($ticket->status === Ticket::STATUS_REVISION_REQUESTER ||
                $ticket->status === Ticket::STATUS_REVISION_BY_IT_HEAD ||
                $ticket->status === Ticket::STATUS_DRAFT) {
                
                $bypassesSupervisor = ($requester->isSupervisor() || $requester->isAdmin()) &&
                    (! $requester->supervisor_id || (int) $requester->supervisor_id === (int) $requester->id);

                $targetStatus = $bypassesSupervisor
                    ? Ticket::STATUS_WAITING_IT_HEAD
                    : Ticket::STATUS_WAITING_SUPERVISOR;

                $action = ($ticket->status === Ticket::STATUS_REVISION_BY_IT_HEAD)
                    ? 'RESUBMIT_TO_SUPERVISOR_AFTER_IT_REVISION'
                    : 'RESUBMIT_TICKET';

                $this->stateMachine->transition(
                    ticket: $ticket,
                    toStatus: $targetStatus,
                    actor: $requester,
                    action: $action,
                    remarks: 'Tiket diperbarui dan diajukan kembali oleh pemohon'
                );
            }

            return $ticket;
        });
    }
}
