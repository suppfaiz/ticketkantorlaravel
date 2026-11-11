<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalDelegation extends Model
{
    public const SCOPE_ALL = 'all';

    public const SCOPE_SUPERVISOR = 'supervisor';

    public const SCOPE_IT_HEAD = 'it_head';

    public const SCOPE_KADIV_IT = 'kadiv_it';

    public const SCOPE_DIREKSI = 'direksi';

    protected $fillable = [
        'delegator_id',
        'delegate_id',
        'created_by',
        'scope',
        'starts_at',
        'ends_at',
        'reason',
        'is_active',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'notified_at' => 'datetime',
        ];
    }

    public function delegator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegator_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
