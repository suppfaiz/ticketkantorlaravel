<?php

namespace App\Services;

use App\Models\ExternalLoaTransaction;
use App\Models\Ticket;
use App\Services\Contracts\LoaServiceInterface;

class MockLoaService implements LoaServiceInterface
{
    protected string $forcedDecision = 'waiting';
    protected bool $simulateTimeout = false;

    /**
     * Set a forced decision for testing/demo (approved, rejected, revision).
     */
    public function setForcedDecision(string $decision): self
    {
        $this->forcedDecision = $decision;
        return $this;
    }

    /**
     * Simulate connection timeout for testing failure handling.
     */
    public function setSimulateTimeout(bool $simulate): self
    {
        $this->simulateTimeout = $simulate;
        return $this;
    }

    /**
     * Dispatch mock approval request.
     */
    public function dispatch(Ticket $ticket, string $approvalLevel, array $approverData): ExternalLoaTransaction
    {
        $idempotencyKey = hash('sha256', "LOA_{$ticket->id}_{$approvalLevel}_{$ticket->ticket_number}");
        $refId = 'MOCK-LOA-' . strtoupper(substr($idempotencyKey, 0, 10));

        if ($this->simulateTimeout) {
            return ExternalLoaTransaction::updateOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'ticket_id' => $ticket->id,
                    'approval_level' => $approvalLevel,
                    'sync_status' => ExternalLoaTransaction::SYNC_FAILED,
                    'decision' => ExternalLoaTransaction::DECISION_WAITING,
                    'last_error_message' => 'cURL error 28: Connection timed out after 5000 milliseconds',
                    'dispatched_at' => now(),
                ]
            );
        }

        $decision = config('services.loa.mock_auto_approve', false) ? ExternalLoaTransaction::DECISION_APPROVED : $this->forcedDecision;

        return ExternalLoaTransaction::updateOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'ticket_id' => $ticket->id,
                'approval_level' => $approvalLevel,
                'external_reference_id' => $refId,
                'sync_status' => ExternalLoaTransaction::SYNC_SYNCED,
                'decision' => $decision,
                'dispatched_at' => now(),
                'decided_at' => $decision !== 'waiting' ? now() : null,
                'response_payload' => [
                    'reference_id' => $refId,
                    'status' => 'success',
                    'decision' => $decision,
                    'message' => 'Dispatched successfully via Mock LOA Provider',
                ],
            ]
        );
    }

    /**
     * Poll status.
     */
    public function pollStatus(ExternalLoaTransaction $transaction): array
    {
        return [
            'status' => $transaction->sync_status,
            'decision' => $transaction->decision,
            'notes' => 'Mock polled successfully',
        ];
    }

    /**
     * Process webhook in mock mode.
     */
    public function processWebhook(array $payload, string $signature): array
    {
        $idempotencyKey = $payload['idempotency_key'] ?? null;
        $transaction = ExternalLoaTransaction::where('idempotency_key', $idempotencyKey)->first();

        if (! $transaction) {
            return ['valid' => false, 'error' => 'Transaksi LOA tidak ditemukan.'];
        }

        $decision = strtolower((string) ($payload['decision'] ?? 'approved'));
        $transaction->update([
            'decision' => $decision,
            'decided_at' => now(),
            'response_payload' => $payload,
        ]);

        return ['valid' => true, 'decision' => $decision, 'transaction' => $transaction];
    }

    /**
     * Retry with same idempotency key.
     */
    public function retry(ExternalLoaTransaction $transaction): ExternalLoaTransaction
    {
        $transaction->increment('retry_count');
        $transaction->update([
            'sync_status' => ExternalLoaTransaction::SYNC_SYNCED,
            'last_error_message' => null,
        ]);

        return $transaction;
    }
}
