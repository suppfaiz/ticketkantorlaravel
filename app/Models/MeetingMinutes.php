<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingMinutes extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'Draft';
    public const STATUS_WAITING_APPROVAL = 'Menunggu Persetujuan Notulensi';
    public const STATUS_APPROVED = 'Disetujui';
    public const STATUS_REVISION = 'Revisi Notulensi Major';

    protected $fillable = [
        'ticket_id',
        'author_id',
        'submitted_by_user_id',
        'meeting_date',
        'attendees',
        'attendees_summary',
        'notes',
        'scope_summary',
        'deliverables',
        'target_completion_date',
        'attachment_path',
        'status',
        'is_approved_by_it_head',
        'approved_by',
        'approved_by_it_head_id',
        'approved_at',
        'approval_notes',
        'revision_comments',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'datetime',
            'target_completion_date' => 'date',
            'is_approved_by_it_head' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
