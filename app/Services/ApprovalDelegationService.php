<?php

namespace App\Services;

use App\Models\ApprovalDelegation;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketEventNotification;
use Illuminate\Support\Collection;

class ApprovalDelegationService
{
    public function activeDelegationFor(User $delegator, string $scope): ?ApprovalDelegation
    {
        return ApprovalDelegation::query()
            ->with('delegate')
            ->where('delegator_id', $delegator->id)
            ->where('is_active', true)
            ->whereIn('scope', [ApprovalDelegation::SCOPE_ALL, $scope])
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereHas('delegate', fn ($query) => $query->where('is_active', true))
            ->latest('starts_at')
            ->first();
    }

    public function effectiveApprover(User $delegator, string $scope): User
    {
        return $this->activeDelegationFor($delegator, $scope)?->delegate ?? $delegator;
    }

    public function effectiveApprovers(Collection $delegators, string $scope): Collection
    {
        return $delegators
            ->map(fn (User $delegator) => $this->effectiveApprover($delegator, $scope))
            ->filter(fn (User $user) => $user->is_active)
            ->unique('id')
            ->values();
    }

    public function canActFor(User $actor, User $delegator, string $scope): bool
    {
        return $actor->id === $delegator->id
            || $this->activeDelegationFor($delegator, $scope)?->delegate_id === $actor->id;
    }

    public function actsForRole(User $actor, string $role, string $scope): bool
    {
        if ($actor->role === $role) {
            return true;
        }

        return User::query()
            ->where('role', $role)
            ->where('is_active', true)
            ->get()
            ->contains(fn (User $delegator) => $this->canActFor($actor, $delegator, $scope));
    }

    public function delegatedUserIds(User $actor, string $scope): Collection
    {
        return ApprovalDelegation::query()
            ->where('delegate_id', $actor->id)
            ->where('is_active', true)
            ->whereIn('scope', [ApprovalDelegation::SCOPE_ALL, $scope])
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->pluck('delegator_id');
    }

    public function notifyDelegateOfPendingApprovals(ApprovalDelegation $delegation): void
    {
        $delegation->loadMissing(['delegator', 'delegate']);
        $delegator = $delegation->delegator;
        $delegate = $delegation->delegate;

        if (! $delegation->is_active || ! $delegate->is_active || $delegation->notified_at) {
            return;
        }

        $query = Ticket::query();

        if ($delegator->isSupervisor()) {
            $query->where('supervisor_id', $delegator->id)->where('status', Ticket::STATUS_WAITING_SUPERVISOR);
        } elseif ($delegator->isItHead()) {
            $query->whereIn('status', [Ticket::STATUS_WAITING_IT_HEAD, Ticket::STATUS_WAITING_MAJOR_MEETING, Ticket::STATUS_WAITING_MINUTES_APPROVAL]);
        } elseif ($delegator->isKadivIt()) {
            $query->where('status', Ticket::STATUS_WAITING_LOA_KADIV_IT);
        } elseif ($delegator->isDireksi()) {
            $query->where('status', Ticket::STATUS_WAITING_LOA_DIREKSI);
        } else {
            return;
        }

        $query->each(function (Ticket $ticket) use ($delegate, $delegator) {
            $delegate->notify(new TicketEventNotification(
                $ticket,
                'approval_delegated',
                'Approval didelegasikan',
                "Anda menerima delegasi approval dari {$delegator->name}.",
            ));
        });

        $delegation->update(['notified_at' => now()]);
    }
}
