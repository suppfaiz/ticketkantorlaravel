<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    use HasFactory;

    // Status Constants
    public const STATUS_DRAFT = 'Draft';
    public const STATUS_WAITING_SUPERVISOR = 'Menunggu Approval Atasan';
    public const STATUS_REVISION_REQUESTER = 'Revisi Requester';
    public const STATUS_REJECTED_SUPERVISOR = 'Ditolak Atasan';
    public const STATUS_WAITING_IT_HEAD = 'Menunggu Approval Kepala Bagian IT';
    public const STATUS_REVISION_BY_IT_HEAD = 'Revisi Requester oleh Kepala Bagian IT';
    public const STATUS_REJECTED_IT_HEAD = 'Ditolak Kepala Bagian IT';
    public const STATUS_WAITING_LOA_KADIV_IT = 'Menunggu LOA Kadiv IT';
    public const STATUS_REJECTED_KADIV_IT = 'Ditolak Kadiv IT';
    public const STATUS_WAITING_LOA_DIREKSI = 'Menunggu LOA Direksi';
    public const STATUS_REJECTED_DIREKSI = 'Ditolak Direksi';
    public const STATUS_WAITING_MAJOR_MEETING = 'Menunggu Rapat Major';
    public const STATUS_WAITING_MINUTES = 'Menunggu Notulensi';
    public const STATUS_WAITING_USER_MINUTES = 'Menunggu Input Notulensi';
    public const STATUS_WAITING_MINUTES_APPROVAL = 'Menunggu Persetujuan Notulensi';
    public const STATUS_REVISION_MINUTES = 'Revisi Notulensi Major';
    public const STATUS_READY_FOR_WORK = 'Siap Dikerjakan';
    public const STATUS_IN_PROGRESS = 'Dalam Pengerjaan';
    public const STATUS_WAITING_USER_REVIEW = 'Menunggu Review User';
    public const STATUS_REVISION_WORK = 'Revisi Hasil Pekerjaan';
    public const STATUS_COMPLETED = 'Selesai';
    public const STATUS_CANCELLED = 'Dibatalkan Pemohon';

    public const CLASSIFICATION_MINOR = 'Minor';
    public const CLASSIFICATION_MAJOR = 'Major';

    protected $fillable = [
        'ticket_number',
        'requester_id',
        'supervisor_id',
        'category',
        'title',
        'description',
        'classification',
        'priority',
        'status',
        'assigned_to',
        'submitted_at',
        'approved_by_supervisor_at',
        'approved_by_it_head_at',
        'approved_by_kadiv_at',
        'approved_by_direksi_at',
        'meeting_scheduled_at',
        'minutes_submitted_at',
        'minutes_approved_at',
        'target_completion_at',
        'work_started_at',
        'work_completed_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'approved_by_supervisor_at' => 'datetime',
            'approved_by_it_head_at' => 'datetime',
            'approved_by_kadiv_at' => 'datetime',
            'approved_by_direksi_at' => 'datetime',
            'meeting_scheduled_at' => 'datetime',
            'minutes_submitted_at' => 'datetime',
            'minutes_approved_at' => 'datetime',
            'target_completion_at' => 'datetime',
            'work_started_at' => 'datetime',
            'work_completed_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_REJECTED_SUPERVISOR,
            self::STATUS_REJECTED_IT_HEAD,
            self::STATUS_REJECTED_KADIV_IT,
            self::STATUS_REJECTED_DIREKSI,
        ], true);
    }

    /**
     * True if IT Head has set a target timeline and the active ticket has passed it.
     */
    public function isOverdue(): bool
    {
        if (! $this->target_completion_at || $this->isTerminal() || $this->status === self::STATUS_WAITING_USER_REVIEW) {
            return false;
        }

        return now()->greaterThan($this->target_completion_at);
    }

    /**
     * True if the ticket was completed after the IT Head's target timeline.
     */
    public function isCompletedLate(): bool
    {
        if (! $this->target_completion_at) {
            return false;
        }

        $finishedAt = $this->work_completed_at ?? $this->closed_at;
        if (! $finishedAt) {
            return false;
        }

        return $finishedAt->greaterThan($this->target_completion_at);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function meetingMinutes(): HasOne
    {
        return $this->hasOne(MeetingMinutes::class);
    }

    public function externalLoaTransactions(): HasMany
    {
        return $this->hasMany(ExternalLoaTransaction::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(TicketActivityLog::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(TicketAuditLog::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TicketReview::class);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'ticket_id')->orderBy('created_at', 'asc')->orderBy('id', 'asc');
    }
}
