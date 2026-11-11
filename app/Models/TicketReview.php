<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReview extends Model
{
    use HasFactory;

    public const DECISION_ACCEPT = 'Accept';
    public const DECISION_REVISE = 'Revisi';

    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'reviewer_id',
        'decision',
        'feedback',
        'rating',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
