<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class TicketAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'actor_id',
        'external_transaction_id',
        'action',
        'from_status',
        'to_status',
        'remarks',
        'comments_or_reason',
        'ip_address',
        'user_agent',
        'payload_diff',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_diff' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function externalTransaction(): BelongsTo
    {
        return $this->belongsTo(ExternalLoaTransaction::class, 'external_transaction_id');
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('Audit log entries are immutable and cannot be updated.');
    }

    public function delete(): ?bool
    {
        throw new RuntimeException('Audit log entries are immutable and cannot be deleted.');
    }
}
