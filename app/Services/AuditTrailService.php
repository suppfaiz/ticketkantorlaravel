<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class AuditTrailService
{
    /**
     * Log a tamper-resistant, sanitized audit trail entry.
     */
    public function log(
        Ticket $ticket,
        ?User $actor = null,
        string $action = 'status_change',
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $remarks = null,
        ?array $payloadDiff = null,
        ?int $externalTransactionId = null,
        ?string $commentsOrReason = null
    ): TicketAuditLog {
        $sanitizedDiff = $payloadDiff ? $this->sanitizePayload($payloadDiff) : null;

        return TicketAuditLog::create([
            'ticket_id' => $ticket->id,
            'actor_id' => $actor?->id,
            'external_transaction_id' => $externalTransactionId,
            'action' => $action,
            'from_status' => $fromStatus ?? $ticket->status,
            'to_status' => $toStatus ?? $ticket->status,
            'remarks' => $remarks ?? $commentsOrReason,
            'comments_or_reason' => $commentsOrReason ?? $remarks,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent() ?? 'CLI/System',
            'payload_diff' => $sanitizedDiff,
            'created_at' => now(),
        ]);
    }

    /**
     * Recursively mask sensitive fields (tokens, passwords, secrets).
     */
    protected function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = ['token', 'jwt', 'password', 'secret', 'client_secret', 'authorization', 'api_key', 'key'];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->sanitizePayload($value);
            } elseif (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $payload[$key] = '***REDACTED***';
            }
        }

        return $payload;
    }
}
