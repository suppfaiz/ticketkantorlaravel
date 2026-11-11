<?php

namespace App\Services;

use App\Models\ExternalLoaTransaction;
use App\Models\Ticket;
use App\Services\Contracts\LoaServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnterpriseLoaService implements LoaServiceInterface
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $webhookSecret;

    public function __construct()
    {
        $this->apiUrl = rtrim((string) config('services.loa.api_url', 'https://loa.kantor.com/api/v1'), '/');
        $this->apiKey = (string) config('services.loa.api_key', '');
        $this->webhookSecret = (string) config('services.loa.webhook_secret', '');
    }

    /**
     * Dispatch approval request with Idempotency-Key.
     */
    public function dispatch(Ticket $ticket, string $approvalLevel, array $approverData): ExternalLoaTransaction
    {
        $idempotencyKey = hash('sha256', "LOA_{$ticket->id}_{$approvalLevel}_{$ticket->ticket_number}");
        $payload = [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'title' => $ticket->title,
            'classification' => $ticket->classification,
            'approval_level' => $approvalLevel,
            'approver' => $approverData,
            'callback_url' => url('/api/v1/webhooks/loa-decision'),
        ];

        $payloadHash = hash('sha256', json_encode($payload));

        $transaction = ExternalLoaTransaction::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'ticket_id' => $ticket->id,
                'approval_level' => $approvalLevel,
                'sync_status' => ExternalLoaTransaction::SYNC_PENDING,
                'decision' => ExternalLoaTransaction::DECISION_WAITING,
                'dispatched_at' => now(),
                'dispatch_payload_hash' => $payloadHash,
            ]
        );

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Idempotency-Key' => $idempotencyKey,
                    'X-Api-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->post("{$this->apiUrl}/approvals", $payload);

            if ($response->successful() || $response->status() === 202) {
                $data = $response->json();
                $transaction->update([
                    'external_reference_id' => $data['reference_id'] ?? $data['loa_id'] ?? null,
                    'sync_status' => ExternalLoaTransaction::SYNC_SYNCED,
                    'response_payload' => $data,
                ]);
            } else {
                $transaction->update([
                    'sync_status' => ExternalLoaTransaction::SYNC_FAILED,
                    'last_error_message' => "HTTP {$response->status()}: " . substr($response->body(), 0, 500),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("LOA API dispatch failure for Ticket #{$ticket->id}: " . $e->getMessage());
            $transaction->update([
                'sync_status' => ExternalLoaTransaction::SYNC_FAILED,
                'last_error_message' => $e->getMessage(),
            ]);
        }

        return $transaction;
    }

    /**
     * Poll status of pending transaction.
     */
    public function pollStatus(ExternalLoaTransaction $transaction): array
    {
        if (! $transaction->external_reference_id) {
            return ['status' => 'pending', 'decision' => 'waiting'];
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders(['X-Api-Key' => $this->apiKey])
                ->get("{$this->apiUrl}/approvals/{$transaction->external_reference_id}");

            if ($response->successful()) {
                $data = $response->json();
                $decision = strtolower((string) ($data['decision'] ?? 'waiting'));

                $transaction->update([
                    'decision' => $decision,
                    'response_payload' => $data,
                    'decided_at' => $decision !== 'waiting' ? now() : $transaction->decided_at,
                ]);

                return ['status' => 'synced', 'decision' => $decision, 'notes' => $data['notes'] ?? null];
            }
        } catch (\Throwable $e) {
            Log::error("LOA API polling error: " . $e->getMessage());
        }

        return ['status' => 'sync_failed', 'decision' => $transaction->decision];
    }

    /**
     * Process inbound webhook.
     */
    public function processWebhook(array $payload, string $signature): array
    {
        $computed = hash_hmac('sha256', json_encode($payload), $this->webhookSecret);
        if (! hash_equals($computed, $signature)) {
            return ['valid' => false, 'error' => 'Tanda tangan webhook tidak cocok.'];
        }

        $idempotencyKey = $payload['idempotency_key'] ?? null;
        $transaction = ExternalLoaTransaction::where('idempotency_key', $idempotencyKey)->first();

        if (! $transaction) {
            return ['valid' => false, 'error' => 'Transaksi LOA tidak ditemukan.'];
        }

        $decision = strtolower((string) ($payload['decision'] ?? 'waiting'));
        $transaction->update([
            'decision' => $decision,
            'decided_at' => now(),
            'response_payload' => $payload,
        ]);

        return ['valid' => true, 'decision' => $decision, 'transaction' => $transaction];
    }

    /**
     * Retry failed dispatch with identical Idempotency-Key.
     */
    public function retry(ExternalLoaTransaction $transaction): ExternalLoaTransaction
    {
        $transaction->increment('retry_count');
        $transaction->update(['sync_status' => ExternalLoaTransaction::SYNC_PENDING]);

        return $this->dispatch($transaction->ticket, $transaction->approval_level, []);
    }
}
