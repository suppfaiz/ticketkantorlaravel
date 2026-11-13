<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_REQUESTER = 'requester';
    public const ROLE_SUPERVISOR = 'supervisor';
    public const ROLE_IT_HEAD = 'it_head';
    public const ROLE_KADIV_IT = 'kadiv_it';
    public const ROLE_DIREKSI = 'direksi';
    public const ROLE_IT_DEVELOPER = 'it_developer';
    public const ROLE_ADMIN = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sso_id',
        'name',
        'email',
        'password',
        'role',
        'department',
        'department_id',
        'supervisor_id',
        'sso_metadata',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'sso_metadata' => 'array',
        ];
    }

    public function departmentRecord(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    public function submittedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function supervisedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'supervisor_id');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function isRequester(): bool
    {
        return $this->role === 'requester';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isItHead(): bool
    {
        return $this->role === 'it_head';
    }

    public function isKadivIt(): bool
    {
        return $this->role === 'kadiv_it';
    }

    public function isDireksi(): bool
    {
        return $this->role === 'direksi';
    }

    public function isItDeveloper(): bool
    {
        return $this->role === 'it_developer';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
