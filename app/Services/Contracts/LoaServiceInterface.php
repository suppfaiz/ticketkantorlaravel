<?php

namespace App\Services\Contracts;

use App\Models\ExternalLoaTransaction;
use App\Models\Ticket;

interface LoaServiceInterface
{
    /**
     * Dispatch an approval request to the corporate LOA API.
     */
    public function dispatch(Ticket $ticket, string $approvalLevel, array $approverData): ExternalLoaTransaction;

    /**
     * Poll the current approval status for an existing LOA transaction.
     *
     * @return array{status: string, decision: string, notes?: string}
     */
    public function pollStatus(ExternalLoaTransaction $transaction): array;

    /**
     * Verify and process an inbound webhook callback from the LOA API.
     *
     * @return array{valid: bool, decision?: string, transaction?: ExternalLoaTransaction, error?: string}
     */
    public function processWebhook(array $payload, string $signature): array;

    /**
     * Retry a failed or timed-out dispatch using the identical Idempotency-Key.
     */
    public function retry(ExternalLoaTransaction $transaction): ExternalLoaTransaction;
}
