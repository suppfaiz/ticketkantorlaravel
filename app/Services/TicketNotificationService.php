<?php

namespace App\Services;

use App\Mail\TicketStatusMail;
use App\Models\ApprovalDelegation;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketEventNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TicketNotificationService
{
    public function __construct(private readonly ApprovalDelegationService $delegations) {}

    public function statusChanged(Ticket $ticket, string $status, ?User $actor = null, ?string $remarks = null): void
    {
        $ticket->loadMissing(['requester', 'supervisor', 'assignedTo']);
        $recipients = $this->recipientsForStatus($ticket, $status);

        foreach ($recipients->unique('id') as $recipient) {
            $nextStep = $this->nextStepFor($ticket, $status, $recipient);
            $summary = ($actor?->name ?? 'Sistem').' memperbarui tiket menjadi '.$status.'.';
            $note = filled($remarks) ? ' Catatan: '.Str::limit(trim($remarks), 180).'.' : '';

            $this->notifyUsers(
                ticket: $ticket,
                recipients: [$recipient],
                event: 'status_changed',
                title: $this->titleForStatus($status),
                message: $summary.$note.' '.$nextStep,
                actor: $actor,
                sendEmail: true,
            );
        }
    }

    public function notifyUsers(
        Ticket $ticket,
        Collection|array $recipients,
        string $event,
        string $title,
        string $message,
        ?User $actor = null,
        bool $sendEmail = false,
    ): void {
        collect($recipients)
            ->filter(fn ($recipient) => $recipient instanceof User && $recipient->is_active)
            ->unique('id')
            ->reject(fn (User $recipient) => $actor && $recipient->id === $actor->id)
            ->each(function (User $recipient) use ($ticket, $event, $title, $message, $actor, $sendEmail) {
                try {
                    $recipient->notify(new TicketEventNotification($ticket, $event, $title, $message, $actor));

                    if ($sendEmail && filled($recipient->email)) {
                        Mail::to($recipient->email)->send(new TicketStatusMail($ticket, $recipient->name, $actor, $message));
                    }
                } catch (\Throwable $exception) {
                    Log::warning('Gagal membuat notifikasi tiket.', [
                        'ticket_id' => $ticket->id,
                        'recipient_id' => $recipient->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });
    }

    public function recipientsForStatus(Ticket $ticket, string $status): Collection
    {
        if ($status === Ticket::STATUS_WAITING_SUPERVISOR && $ticket->supervisor) {
            return collect([$this->delegations->effectiveApprover($ticket->supervisor, ApprovalDelegation::SCOPE_SUPERVISOR)]);
        }

        if (in_array($status, [Ticket::STATUS_WAITING_IT_HEAD, Ticket::STATUS_WAITING_MAJOR_MEETING, Ticket::STATUS_WAITING_MINUTES_APPROVAL], true)) {
            return $this->roleApprovers(User::ROLE_IT_HEAD, ApprovalDelegation::SCOPE_IT_HEAD);
        }

        if ($status === Ticket::STATUS_WAITING_LOA_KADIV_IT) {
            return $this->roleApprovers(User::ROLE_KADIV_IT, ApprovalDelegation::SCOPE_KADIV_IT);
        }

        if ($status === Ticket::STATUS_WAITING_LOA_DIREKSI) {
            return $this->roleApprovers(User::ROLE_DIREKSI, ApprovalDelegation::SCOPE_DIREKSI);
        }

        if ($status === Ticket::STATUS_READY_FOR_WORK) {
            return User::where('role', User::ROLE_IT_DEVELOPER)->where('is_active', true)->get();
        }

        if ($status === Ticket::STATUS_IN_PROGRESS) {
            return collect([$ticket->assignedTo, $ticket->requester])->filter();
        }

        if ($status === Ticket::STATUS_REVISION_WORK && $ticket->assignedTo) {
            return collect([$ticket->assignedTo]);
        }

        if ($status === Ticket::STATUS_COMPLETED) {
            return collect([$ticket->assignedTo])->filter()->merge($this->roleApprovers(User::ROLE_IT_HEAD, ApprovalDelegation::SCOPE_IT_HEAD));
        }

        return collect([$ticket->requester])->filter();
    }

    private function roleApprovers(string $role, string $scope): Collection
    {
        $users = User::where('role', $role)->where('is_active', true)->get();

        return $this->delegations->effectiveApprovers($users, $scope);
    }

    private function titleForStatus(string $status): string
    {
        return match ($status) {
            Ticket::STATUS_WAITING_SUPERVISOR => 'Persetujuan atasan diperlukan',
            Ticket::STATUS_WAITING_IT_HEAD => 'Tiket menunggu keputusan Kabag IT',
            Ticket::STATUS_WAITING_LOA_KADIV_IT => 'LOA Kadiv IT perlu ditinjau',
            Ticket::STATUS_WAITING_LOA_DIREKSI => 'LOA Direksi perlu ditinjau',
            Ticket::STATUS_WAITING_MAJOR_MEETING => 'Rapat major perlu dijadwalkan',
            Ticket::STATUS_WAITING_MINUTES, Ticket::STATUS_WAITING_USER_MINUTES, Ticket::STATUS_REVISION_MINUTES => 'Notulensi perlu dilengkapi',
            Ticket::STATUS_WAITING_MINUTES_APPROVAL => 'Notulensi menunggu persetujuan',
            Ticket::STATUS_READY_FOR_WORK => 'Tiket tersedia di pool IT',
            Ticket::STATUS_IN_PROGRESS => 'Pengerjaan tiket dimulai',
            Ticket::STATUS_REVISION_WORK => 'Hasil pekerjaan perlu direvisi',
            Ticket::STATUS_WAITING_USER_REVIEW => 'Hasil pekerjaan siap diperiksa',
            Ticket::STATUS_COMPLETED => 'Tiket selesai',
            Ticket::STATUS_REVISION_REQUESTER, Ticket::STATUS_REVISION_BY_IT_HEAD => 'Pengajuan perlu diperbaiki',
            Ticket::STATUS_REJECTED_SUPERVISOR, Ticket::STATUS_REJECTED_IT_HEAD,
            Ticket::STATUS_REJECTED_KADIV_IT, Ticket::STATUS_REJECTED_DIREKSI => 'Tiket ditolak',
            default => 'Status tiket diperbarui',
        };
    }

    private function nextStepFor(Ticket $ticket, string $status, User $recipient): string
    {
        return match ($status) {
            Ticket::STATUS_WAITING_SUPERVISOR => 'Tinjau pengajuan dan berikan keputusan.',
            Ticket::STATUS_WAITING_IT_HEAD => 'Tinjau klasifikasi dan berikan keputusan.',
            Ticket::STATUS_WAITING_LOA_KADIV_IT, Ticket::STATUS_WAITING_LOA_DIREKSI => 'Tinjau LOA dan berikan keputusan.',
            Ticket::STATUS_WAITING_MAJOR_MEETING => 'Atur jadwal rapat major.',
            Ticket::STATUS_WAITING_MINUTES, Ticket::STATUS_WAITING_USER_MINUTES, Ticket::STATUS_REVISION_MINUTES => 'Lengkapi notulensi pada halaman tiket.',
            Ticket::STATUS_WAITING_MINUTES_APPROVAL => 'Periksa notulensi dan berikan keputusan.',
            Ticket::STATUS_READY_FOR_WORK => 'Ambil tiket dari pool jika Anda siap menanganinya.',
            Ticket::STATUS_IN_PROGRESS => $ticket->assigned_to === $recipient->id
                ? 'Lanjutkan pengerjaan dan catat progresnya.'
                : 'Pantau progres pengerjaan pada halaman tiket.',
            Ticket::STATUS_REVISION_WORK => 'Periksa masukan pemohon dan kerjakan revisinya.',
            Ticket::STATUS_WAITING_USER_REVIEW => 'Periksa hasil kerja, lalu terima atau minta revisi.',
            Ticket::STATUS_REVISION_REQUESTER, Ticket::STATUS_REVISION_BY_IT_HEAD => 'Perbaiki pengajuan sesuai catatan peninjau.',
            Ticket::STATUS_COMPLETED => 'Tiket telah ditutup. Tidak ada tindakan lanjutan.',
            Ticket::STATUS_REJECTED_SUPERVISOR, Ticket::STATUS_REJECTED_IT_HEAD,
            Ticket::STATUS_REJECTED_KADIV_IT, Ticket::STATUS_REJECTED_DIREKSI => 'Buka tiket untuk melihat alasan penolakan.',
            default => 'Buka tiket untuk melihat rinciannya.',
        };
    }
}
