<?php

namespace App\Console\Commands;

use App\Models\ApprovalDelegation;
use App\Services\ApprovalDelegationService;
use Illuminate\Console\Command;

class ActivateApprovalDelegations extends Command
{
    protected $signature = 'delegations:activate';

    protected $description = 'Notify delegates when their scheduled approval delegation becomes active';

    public function handle(ApprovalDelegationService $service): int
    {
        ApprovalDelegation::query()
            ->where('is_active', true)
            ->whereNull('notified_at')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with(['delegator', 'delegate'])
            ->each(fn (ApprovalDelegation $delegation) => $service->notifyDelegateOfPendingApprovals($delegation));

        return self::SUCCESS;
    }
}
