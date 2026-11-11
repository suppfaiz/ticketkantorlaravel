<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExternalLoaTransaction;
use App\Models\Ticket;
use App\Services\Contracts\LoaServiceInterface;
use App\Services\TicketStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoaWebhookController extends Controller
{
    protected LoaServiceInterface $loaService;
    protected TicketStateMachine $stateMachine;

    public function __construct(LoaServiceInterface $loaService, TicketStateMachine $stateMachine)
    {
        $this->loaService = $loaService;
        $this->stateMachine = $stateMachine;
    }

    /**
     * Handle inbound LOA decision webhook callback.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $signature = $request->header('X-Signature-SHA256') ?? $request->header('X-Signature') ?? 'mock-signature';

        $result = $this->loaService->processWebhook($payload, $signature);

        if (! $result['valid']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['error'] ?? 'Webhook verification failed',
            ], 400);
        }

        /** @var ExternalLoaTransaction $transaction */
        $transaction = $result['transaction'];
        $ticket = $transaction->ticket;
        $decision = $result['decision'];
        $level = $transaction->approval_level;
        $notes = $payload['notes'] ?? 'Keputusan LOA Eksternal';

        if ($decision === ExternalLoaTransaction::DECISION_APPROVED) {
            if ($level === ExternalLoaTransaction::LEVEL_SUPERVISOR) {
                if ($ticket->status === Ticket::STATUS_WAITING_SUPERVISOR) {
                    $this->stateMachine->transition(
                        ticket: $ticket,
                        toStatus: Ticket::STATUS_WAITING_IT_HEAD,
                        actor: null,
                        action: 'LOA_SUPERVISOR_APPROVED',
                        remarks: $notes,
                        externalTransactionId: $transaction->id
                    );
                }
            } elseif ($level === ExternalLoaTransaction::LEVEL_KADIV_IT_OPS) {
                if ($ticket->status === Ticket::STATUS_WAITING_LOA_KADIV_IT) {
                    $this->stateMachine->transition(
                        ticket: $ticket,
                        toStatus: Ticket::STATUS_WAITING_LOA_DIREKSI,
                        actor: null,
                        action: 'LOA_KADIV_APPROVED',
                        remarks: $notes,
                        externalTransactionId: $transaction->id
                    );

                    // Automatically dispatch to Direksi
                    $this->loaService->dispatch($ticket, ExternalLoaTransaction::LEVEL_DIREKSI, [
                        'role' => 'direksi',
                        'title' => 'Direksi Persetujuan Proyek Major',
                    ]);
                }
            } elseif ($level === ExternalLoaTransaction::LEVEL_DIREKSI) {
                if ($ticket->status === Ticket::STATUS_WAITING_LOA_DIREKSI) {
                    $this->stateMachine->transition(
                        ticket: $ticket,
                        toStatus: Ticket::STATUS_WAITING_MAJOR_MEETING,
                        actor: null,
                        action: 'LOA_DIREKSI_APPROVED',
                        remarks: $notes,
                        externalTransactionId: $transaction->id
                    );
                }
            }
        } elseif ($decision === ExternalLoaTransaction::DECISION_REJECTED) {
            $rejectedStatus = match ($level) {
                ExternalLoaTransaction::LEVEL_SUPERVISOR => Ticket::STATUS_REJECTED_SUPERVISOR,
                ExternalLoaTransaction::LEVEL_KADIV_IT_OPS => Ticket::STATUS_REJECTED_KADIV_IT,
                ExternalLoaTransaction::LEVEL_DIREKSI => Ticket::STATUS_REJECTED_DIREKSI,
                default => Ticket::STATUS_REJECTED_SUPERVISOR,
            };

            $this->stateMachine->transition(
                ticket: $ticket,
                toStatus: $rejectedStatus,
                actor: null,
                action: 'LOA_' . strtoupper($level) . '_REJECTED',
                remarks: $notes,
                externalTransactionId: $transaction->id
            );
        } elseif ($decision === ExternalLoaTransaction::DECISION_REVISION) {
            if ($level === ExternalLoaTransaction::LEVEL_SUPERVISOR) {
                $this->stateMachine->transition(
                    ticket: $ticket,
                    toStatus: Ticket::STATUS_REVISION_REQUESTER,
                    actor: null,
                    action: 'LOA_SUPERVISOR_REVISION',
                    remarks: $notes,
                    externalTransactionId: $transaction->id
                );
            }
        }

        return response()->json([
            'status' => 'success',
            'ticket_id' => $ticket->id,
            'new_status' => $ticket->fresh()->status,
        ]);
    }
}
