@php
    $isMajor = $ticket->classification === \App\Models\Ticket::CLASSIFICATION_MAJOR
        || (!$ticket->classification && $ticket->category === 'Perubahan Sistem');
    $status = $ticket->status;

    if ($isMajor) {
        $steps = [
            ['id' => 1, 'label' => 'Pengajuan', 'statuses' => [\App\Models\Ticket::STATUS_DRAFT]],
            ['id' => 2, 'label' => 'LOA Atasan', 'statuses' => [\App\Models\Ticket::STATUS_WAITING_SUPERVISOR, \App\Models\Ticket::STATUS_REVISION_REQUESTER]],
            ['id' => 3, 'label' => 'Kabag IT', 'statuses' => [\App\Models\Ticket::STATUS_WAITING_IT_HEAD, \App\Models\Ticket::STATUS_REVISION_BY_IT_HEAD]],
            ['id' => 4, 'label' => 'LOA Kadiv', 'statuses' => [\App\Models\Ticket::STATUS_WAITING_LOA_KADIV_IT]],
            ['id' => 5, 'label' => 'LOA Direksi', 'statuses' => [\App\Models\Ticket::STATUS_WAITING_LOA_DIREKSI]],
            ['id' => 6, 'label' => 'Rapat Major', 'statuses' => [\App\Models\Ticket::STATUS_WAITING_MAJOR_MEETING]],
            ['id' => 7, 'label' => 'Notulensi', 'statuses' => [\App\Models\Ticket::STATUS_WAITING_USER_MINUTES, \App\Models\Ticket::STATUS_WAITING_MINUTES]],
            ['id' => 8, 'label' => 'Verifikasi', 'statuses' => [\App\Models\Ticket::STATUS_WAITING_MINUTES_APPROVAL, \App\Models\Ticket::STATUS_REVISION_MINUTES]],
            ['id' => 9, 'label' => 'Antrean IT', 'statuses' => [\App\Models\Ticket::STATUS_READY_FOR_WORK]],
            ['id' => 10, 'label' => 'Pengerjaan', 'statuses' => [\App\Models\Ticket::STATUS_IN_PROGRESS, \App\Models\Ticket::STATUS_REVISION_WORK]],
            ['id' => 11, 'label' => 'Selesai', 'statuses' => [\App\Models\Ticket::STATUS_WAITING_USER_REVIEW, \App\Models\Ticket::STATUS_COMPLETED]],
        ];
    } else {
        $steps = [
            ['id' => 1, 'label' => 'Pengajuan', 'statuses' => [\App\Models\Ticket::STATUS_DRAFT]],
            ['id' => 2, 'label' => 'LOA Atasan', 'statuses' => [\App\Models\Ticket::STATUS_WAITING_SUPERVISOR, \App\Models\Ticket::STATUS_REVISION_REQUESTER]],
            ['id' => 3, 'label' => 'Kabag IT', 'statuses' => [\App\Models\Ticket::STATUS_WAITING_IT_HEAD, \App\Models\Ticket::STATUS_REVISION_BY_IT_HEAD]],
            ['id' => 4, 'label' => 'Antrean IT', 'statuses' => [\App\Models\Ticket::STATUS_READY_FOR_WORK]],
            ['id' => 5, 'label' => 'Pengerjaan', 'statuses' => [\App\Models\Ticket::STATUS_IN_PROGRESS, \App\Models\Ticket::STATUS_REVISION_WORK]],
            ['id' => 6, 'label' => 'Review User', 'statuses' => [\App\Models\Ticket::STATUS_WAITING_USER_REVIEW, \App\Models\Ticket::STATUS_REVISION_WORK]],
            ['id' => 7, 'label' => 'Selesai', 'statuses' => [\App\Models\Ticket::STATUS_COMPLETED]],
        ];
    }

    // Determine active step index
    $activeStepIndex = 1;
    foreach ($steps as $index => $step) {
        if (in_array($status, $step['statuses'], true)) {
            $activeStepIndex = $index + 1;
            break;
        }
    }
    if ($status === \App\Models\Ticket::STATUS_COMPLETED) {
        $activeStepIndex = count($steps) + 1;
    }
    $isRejected = str_contains(strtolower($status), 'ditolak');
@endphp

@php
    $totalSteps = count($steps);
    $currentDisplayIndex = min($activeStepIndex, $totalSteps);
    $currentStepLabel = $status === \App\Models\Ticket::STATUS_COMPLETED
        ? 'Selesai & Ditutup'
        : ($steps[$currentDisplayIndex - 1]['label'] ?? $status);
    $nextStepLabel = ($currentDisplayIndex < $totalSteps && !$isRejected && $status !== \App\Models\Ticket::STATUS_COMPLETED)
        ? ($steps[$currentDisplayIndex]['label'] ?? null)
        : null;
    $progressPercent = $status === \App\Models\Ticket::STATUS_COMPLETED
        ? 100
        : (int) round(($currentDisplayIndex / $totalSteps) * 100);
@endphp

<section x-data="{ showAllSteps: false }" class="mx-auto mb-5 w-full max-w-[1600px] rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-xs sm:px-6 sm:py-5" aria-label="Ringkasan dan alur tiket">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="text-xs font-bold tracking-wide text-[#1E40AF]">{{ $ticket->ticket_number }}</div>
            <h2 class="mt-1 text-lg font-bold leading-snug text-slate-900 sm:text-xl">{{ $ticket->title }}</h2>
            <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-600 sm:text-sm">
                <span>{{ $ticket->category }}</span>
                <span class="text-slate-300">&bull;</span>
                <span>{{ $ticket->classification ?? ($isMajor ? 'Major' : 'Minor') }}</span>
                @if($ticket->priority)
                    <span class="text-slate-300">&bull;</span>
                    <span>Prioritas {{ $ticket->priority }}</span>
                @endif
            </div>
        </div>
        <div class="shrink-0 self-start"><x-ticket-status-badge :status="$status" /></div>
    </div>

    <div class="mt-4 border-t border-slate-100 pt-4">
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
            <div class="min-w-0">
                <div class="text-xs font-medium text-slate-500">Tahap {{ $currentDisplayIndex }} dari {{ $totalSteps }}</div>
                <div class="text-sm font-bold text-slate-900">{{ $currentStepLabel }}</div>
                @if($nextStepLabel)
                    <div class="text-xs text-slate-500">Berikutnya: {{ $nextStepLabel }}</div>
                @endif
            </div>
            <button type="button" @click="showAllSteps = !showAllSteps" :aria-expanded="showAllSteps.toString()" aria-controls="ticket-all-steps" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-[#1E40AF] hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/30">
                <span x-text="showAllSteps ? 'Tutup alur' : 'Lihat seluruh alur'">Lihat seluruh alur</span>
                <svg class="h-3.5 w-3.5 transition-transform" :class="showAllSteps ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>
        </div>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-label="Kemajuan alur tiket" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progressPercent }}">
            <div class="h-full rounded-full {{ $isRejected ? 'bg-rose-500' : ($status === \App\Models\Ticket::STATUS_COMPLETED ? 'bg-emerald-500' : 'bg-[#1E40AF]') }}" style="width: {{ $progressPercent }}%"></div>
        </div>
    </div>

    <ol id="ticket-all-steps" x-show="showAllSteps" x-collapse style="display: none;" class="mt-4 grid grid-cols-2 gap-2 border-t border-slate-100 pt-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
        @foreach($steps as $index => $step)
            @php
                $stepNum = $index + 1;
                $isPassed = $stepNum < $activeStepIndex;
                $isCurrent = $stepNum === $activeStepIndex && ! $isRejected;
            @endphp
            <li class="flex min-w-0 items-center gap-2 rounded-lg px-2 py-2 {{ $isCurrent ? 'bg-blue-50 text-[#1E40AF]' : 'bg-slate-50 text-slate-600' }}">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $isPassed ? 'bg-[#1E40AF] text-white' : ($isCurrent ? 'border-2 border-[#1E40AF] bg-white text-[#1E40AF]' : 'bg-slate-200 text-slate-500') }}">{{ $isPassed ? '✓' : $stepNum }}</span>
                <span class="min-w-0 text-xs font-medium leading-tight">{{ $step['label'] }}</span>
            </li>
        @endforeach
    </ol>
</section>
