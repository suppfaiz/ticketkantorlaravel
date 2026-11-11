<?php

namespace App\Http\Controllers;

use App\Models\ApprovalDelegation;
use App\Models\User;
use App\Services\ApprovalDelegationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApprovalDelegationController extends Controller
{
    public function index(): View
    {
        $delegations = ApprovalDelegation::with(['delegator', 'delegate', 'creator'])->latest()->paginate(15);
        $approvers = User::whereIn('role', [User::ROLE_SUPERVISOR, User::ROLE_IT_HEAD, User::ROLE_KADIV_IT, User::ROLE_DIREKSI])
            ->where('is_active', true)->orderBy('name')->get();
        $delegates = User::whereIn('role', [User::ROLE_SUPERVISOR, User::ROLE_IT_HEAD, User::ROLE_KADIV_IT, User::ROLE_DIREKSI, User::ROLE_ADMIN])
            ->where('is_active', true)->orderBy('name')->get();

        return view('admin.delegations.index', compact('delegations', 'approvers', 'delegates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'delegator_id' => ['required', 'integer', 'exists:users,id', 'different:delegate_id'],
            'delegate_id' => ['required', 'integer', 'exists:users,id'],
            'scope' => ['required', Rule::in([
                ApprovalDelegation::SCOPE_ALL,
                ApprovalDelegation::SCOPE_SUPERVISOR,
                ApprovalDelegation::SCOPE_IT_HEAD,
                ApprovalDelegation::SCOPE_KADIV_IT,
                ApprovalDelegation::SCOPE_DIREKSI,
            ])],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $delegator = User::whereKey($validated['delegator_id'])->where('is_active', true)->firstOrFail();
        $delegate = User::whereKey($validated['delegate_id'])
            ->whereIn('role', [User::ROLE_SUPERVISOR, User::ROLE_IT_HEAD, User::ROLE_KADIV_IT, User::ROLE_DIREKSI, User::ROLE_ADMIN])
            ->where('is_active', true)->firstOrFail();

        $expectedScope = match ($delegator->role) {
            User::ROLE_SUPERVISOR => ApprovalDelegation::SCOPE_SUPERVISOR,
            User::ROLE_IT_HEAD => ApprovalDelegation::SCOPE_IT_HEAD,
            User::ROLE_KADIV_IT => ApprovalDelegation::SCOPE_KADIV_IT,
            User::ROLE_DIREKSI => ApprovalDelegation::SCOPE_DIREKSI,
            default => null,
        };

        if ($expectedScope === null || ! in_array($validated['scope'], [ApprovalDelegation::SCOPE_ALL, $expectedScope], true)) {
            return back()->withInput()->with('error', 'Cakupan delegasi tidak sesuai dengan role pejabat asal.');
        }

        $overlapScopes = $validated['scope'] === ApprovalDelegation::SCOPE_ALL
            ? [ApprovalDelegation::SCOPE_ALL, $expectedScope]
            : [ApprovalDelegation::SCOPE_ALL, $validated['scope']];

        $overlaps = ApprovalDelegation::query()
            ->where('delegator_id', $delegator->id)
            ->where('is_active', true)
            ->whereIn('scope', $overlapScopes)
            ->where('starts_at', '<', $validated['ends_at'])
            ->where('ends_at', '>', $validated['starts_at'])
            ->exists();

        if ($overlaps) {
            return back()->withInput()->with('error', 'Periode delegasi bertabrakan dengan delegasi aktif lainnya.');
        }

        $delegation = ApprovalDelegation::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'is_active' => true,
        ]);

        if ($delegation->starts_at->lte(now()) && $delegation->ends_at->gte(now())) {
            app(ApprovalDelegationService::class)->notifyDelegateOfPendingApprovals($delegation);
        }

        return back()->with('success', "Delegasi {$delegator->name} kepada {$delegate->name} berhasil dibuat.");
    }

    public function destroy(ApprovalDelegation $delegation): RedirectResponse
    {
        $delegation->update(['is_active' => false]);

        return back()->with('success', 'Delegasi berhasil dinonaktifkan.');
    }
}
