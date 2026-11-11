<?php

namespace App\Services;

use App\Models\ExternalLoaTransaction;
use App\Models\Ticket;
use App\Services\Contracts\LoaServiceInterface;

class LocalLoaService implements LoaServiceInterface
{
    public function dispatch(Ticket $ticket, string $approvalLevel, array $approverData): ExternalLoaTransaction
    {
        $idempotencyKey = hash('sha256', "LOA_{$ticket->id}_{$approvalLevel}_{$ticket->ticket_number}");
        $referenceId = 'LOCAL-LOA-'.strtoupper(substr($idempotencyKey, 0, 10));

        return ExternalLoaTransaction::updateOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'ticket_id' => $ticket->id,
                'approval_level' => $approvalLevel,
                'external_reference_id' => $referenceId,
                'sync_status' => ExternalLoaTransaction::SYNC_SYNCED,
                'decision' => ExternalLoaTransaction::DECISION_WAITING,
                'dispatched_at' => now(),
                'last_error_message' => null,
                'response_payload' => [
                    'provider' => 'local',
                    'status' => 'waiting_local_approval',
                    'approver' => $approverData,
                ],
            ]
        );
    }

    public function pollStatus(ExternalLoaTransaction $transaction): array
    {
        return [
            'status' => $transaction->sync_status,
            'decision' => $transaction->decision,
            'notes' => 'Keputusan LOA diproses langsung di aplikasi Helpdesk.',
        ];
    }

    public function processWebhook(array $payload, string $signature): array
    {
        return [
            'valid' => false,
            'error' => 'Webhook LOA tidak tersedia saat driver lokal digunakan.',
        ];
    }

    public function retry(ExternalLoaTransaction $transaction): ExternalLoaTransaction
    {
        return $transaction;
    }
}
