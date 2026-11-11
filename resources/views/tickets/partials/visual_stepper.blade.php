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

<div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-xs mb-5 sm:mb-6">
    <!-- Mobile Header (Single Clean Line, No Redundant Status Pill, Hidden on sm: Desktop) -->
    <div class="sm:hidden flex items-center justify-between gap-2 mb-3 pb-2.5 border-b border-slate-100">
        <span class="font-mono text-xs font-bold text-[#1E40AF]">
            {{ $ticket->ticket_number }}
        </span>
        <span class="text-xs text-slate-500 truncate">
            {{ $ticket->category }}
            &bull; {{ $ticket->classification ?? ($isMajor ? 'Major' : 'Minor') }}
            @if($ticket->priority)
                &bull; {{ $ticket->priority }}
            @endif
        </span>
    </div>

    <!-- Desktop Header (Identical on sm: and up) -->
    <div class="hidden sm:flex flex-wrap items-center justify-between gap-3 mb-4 pb-3.5 border-b border-slate-100">
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="font-mono font-bold text-[#1E40AF] bg-blue-50 px-2.5 py-1 rounded-md border border-blue-200/70">
                {{ $ticket->ticket_number }}
            </span>
            <span class="text-slate-600 bg-slate-100 px-2.5 py-1 rounded-md">
                Kategori: <strong class="text-slate-800 font-semibold">{{ $ticket->category }}</strong>
            </span>
            <span class="text-slate-600 bg-slate-100 px-2.5 py-1 rounded-md">
                Klasifikasi: <strong class="text-slate-800 font-semibold">{{ $ticket->classification ?? ($isMajor ? 'Major' : 'Minor') }}</strong>
            </span>
            @if($ticket->priority)
                <span class="text-slate-600 bg-slate-100 px-2.5 py-1 rounded-md">
                    Prioritas: <strong class="text-slate-800 font-semibold">{{ $ticket->priority }}</strong>
                </span>
            @endif
        </div>
        <x-ticket-status-badge :status="$status" />
    </div>

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

    <!-- Mobile Compact Progress Bar & Expandable Stepper (Handphone Only, Hidden on sm: Desktop) -->
    <div x-data="{ showAllSteps: false }" class="sm:hidden">
        <div class="flex items-center justify-between gap-2 mb-2">
            <div class="flex items-center gap-2 min-w-0">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 {{ $isRejected ? 'bg-rose-600 text-white' : ($status === \App\Models\Ticket::STATUS_COMPLETED ? 'bg-emerald-600 text-white' : 'bg-[#1E40AF] text-white') }}">
                    {{ $currentDisplayIndex }}
                </span>
                <div class="min-w-0">
                    <div class="text-xs font-bold text-slate-900 truncate">
                        Tahap {{ $currentDisplayIndex }} dari {{ $totalSteps }}: {{ $currentStepLabel }}
                    </div>
                    @if($nextStepLabel)
                        <div class="text-[11px] text-slate-500 truncate">
                            Selanjutnya: {{ $nextStepLabel }}
                        </div>
                    @endif
                </div>
            </div>
            <button
                type="button"
                @click="showAllSteps = !showAllSteps"
                class="text-[11px] font-semibold text-[#1E40AF] bg-blue-50 px-2.5 py-1 rounded-lg shrink-0 cursor-pointer"
            >
                <span x-text="showAllSteps ? 'Tutup' : 'Rincian'">Rincian</span>
            </button>
        </div>

        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
            <div
                class="h-full rounded-full transition-all duration-300 {{ $isRejected ? 'bg-rose-500' : ($status === \App\Models\Ticket::STATUS_COMPLETED ? 'bg-emerald-500' : 'bg-[#1E40AF]') }}"
                style="width: {{ $progressPercent }}%;"
            ></div>
        </div>

        <div x-show="showAllSteps" x-collapse style="display: none;" class="mt-3 pt-3 border-t border-slate-100 grid grid-cols-2 gap-2">
            @foreach($steps as $index => $step)
                @php
                    $stepNum = $index + 1;
                    $isPassed = $stepNum < $activeStepIndex;
                    $isCurrent = $stepNum === $activeStepIndex && ! $isRejected;
                @endphp
                <div class="flex items-center gap-2 p-1.5 rounded-lg {{ $isCurrent ? 'bg-blue-50/80 border border-blue-200/70' : '' }}">
                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 {{ $isPassed ? 'bg-[#1E40AF] text-white' : ($isCurrent ? 'bg-white text-[#1E40AF] border-2 border-[#1E40AF]' : 'bg-slate-100 text-slate-400') }}">
                        @if($isPassed)
                            ✓
                        @else
                            {{ $stepNum }}
                        @endif
                    </span>
                    <span class="text-[11px] truncate {{ $isCurrent ? 'font-bold text-[#1E40AF]' : ($isPassed ? 'font-medium text-slate-700' : 'text-slate-400') }}">
                        {{ $step['label'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Desktop Full Horizontal Stepper (Hidden on Mobile, Identical on sm: and up) -->
    <div class="hidden sm:block overflow-x-auto pb-1">
        <ol class="flex items-center w-full min-w-[680px]">
            @foreach($steps as $index => $step)
                @php
                    $stepNum = $index + 1;
                    $isPassed = $stepNum < $activeStepIndex;
                    $isCurrent = $stepNum === $activeStepIndex && ! $isRejected;
                @endphp
                <li class="relative flex-1 {{ ! $loop->last ? "after:content-[''] after:w-full after:h-0.5 after:border-b " . ($isPassed ? 'after:border-[#1E40AF]' : 'after:border-slate-200') . " after:inline-block after:absolute after:top-3.5 after:left-[50%]" : '' }}">
                    <div class="flex flex-col items-center group relative z-10 px-1">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold transition-all
                            {{ $isPassed ? 'bg-[#1E40AF] text-white shadow-xs' : ($isCurrent ? 'bg-white text-[#1E40AF] border-2 border-[#1E40AF] ring-4 ring-[#1E40AF]/15 shadow-xs font-bold' : 'bg-slate-100 text-slate-400 border border-slate-200') }}">
                            @if($isPassed)
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            @else
                                {{ $stepNum }}
                            @endif
                        </span>
                        <span class="text-[11px] mt-1.5 font-medium tracking-tight text-center whitespace-nowrap {{ $isCurrent ? 'text-[#1E40AF] font-bold' : ($isPassed ? 'text-slate-800' : 'text-slate-400') }}">
                            {{ $step['label'] }}
                        </span>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</div>
