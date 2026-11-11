<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalLoaTransaction extends Model
{
    use HasFactory;

    public const LEVEL_SUPERVISOR = 'supervisor';
    public const LEVEL_KADIV_IT_OPS = 'kadiv_it_ops';
    public const LEVEL_DIREKSI = 'direksi';

    public const SYNC_PENDING = 'pending';
    public const SYNC_SYNCED = 'synced';
    public const SYNC_FAILED = 'sync_failed';

    public const DECISION_WAITING = 'waiting';
    public const DECISION_APPROVED = 'approved';
    public const DECISION_REJECTED = 'rejected';
    public const DECISION_REVISION = 'revision';

    protected $fillable = [
        'ticket_id',
        'approval_level',
        'external_reference_id',
        'idempotency_key',
        'sync_status',
        'decision',
        'dispatched_at',
        'decided_at',
        'dispatch_payload_hash',
        'response_payload',
        'last_error_message',
        'retry_count',
    ];

    protected function casts(): array
    {
        return [
            'dispatched_at' => 'datetime',
            'decided_at' => 'datetime',
            'response_payload' => 'array',
            'retry_count' => 'integer',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(TicketAuditLog::class, 'external_transaction_id');
    }
}
