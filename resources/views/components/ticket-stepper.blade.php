@props(['ticket'])

@php
    $status = $ticket->status;

    // Tahap A: Pengajuan
    $stageA = [
        'name' => 'Tahap A',
        'title' => 'Pengajuan Tiket',
        'desc' => $ticket->requester->name,
        'state' => 'completed',
    ];

    // Tahap B: Approval Atasan
    $stageBState = 'pending';
    $stageBDesc = 'Atasan Langsung';
    if ($status === \App\Models\Ticket::STATUS_WAITING_SUPERVISOR) {
        $stageBState = 'active';
        $stageBDesc = 'Menunggu Persetujuan';
    } elseif ($status === \App\Models\Ticket::STATUS_REJECTED_SUPERVISOR) {
        $stageBState = 'rejected';
        $stageBDesc = 'Ditolak Atasan';
    } elseif ($status === \App\Models\Ticket::STATUS_REVISION_REQUESTER) {
        $stageBState = 'revision';
        $stageBDesc = 'Perlu Revisi';
    } elseif (!in_array($status, [\App\Models\Ticket::STATUS_DRAFT])) {
        $stageBState = 'completed';
        $stageBDesc = 'Disetujui Atasan';
    }
    $stageB = [
        'name' => 'Tahap B',
        'title' => 'Approval Atasan',
        'desc' => $stageBDesc,
        'state' => $stageBState,
    ];

    // Tahap C: Evaluasi Kabag IT
    $stageCState = 'pending';
    $stageCDesc = 'Kepala Bagian IT';
    if ($status === \App\Models\Ticket::STATUS_WAITING_IT_HEAD) {
        $stageCState = 'active';
        $stageCDesc = 'Menunggu Evaluasi';
    } elseif (in_array($status, [
        \App\Models\Ticket::STATUS_WAITING_MAJOR_MEETING,
        \App\Models\Ticket::STATUS_WAITING_MINUTES,
        \App\Models\Ticket::STATUS_WAITING_MINUTES_APPROVAL,
    ])) {
        $stageCState = 'active';
        $stageCDesc = 'Rapat Major & Notulen';
    } elseif ($status === \App\Models\Ticket::STATUS_REJECTED_IT_HEAD) {
        $stageCState = 'rejected';
        $stageCDesc = 'Ditolak Kabag IT';
    } elseif (in_array($status, [
        \App\Models\Ticket::STATUS_READY_FOR_WORK,
        \App\Models\Ticket::STATUS_IN_PROGRESS,
        \App\Models\Ticket::STATUS_REVISION_WORK,
        \App\Models\Ticket::STATUS_WAITING_USER_REVIEW,
        \App\Models\Ticket::STATUS_COMPLETED,
    ])) {
        $stageCState = 'completed';
        $stageCDesc = 'Lolos (' . ($ticket->classification ?? 'Minor') . ')';
    }
    $stageC = [
        'name' => 'Tahap C',
        'title' => 'Evaluasi Kabag IT',
        'desc' => $stageCDesc,
        'state' => $stageCState,
    ];

    // Tahap D: Pengerjaan Tim IT
    $stageDState = 'pending';
    $stageDDesc = 'Tim Developer';
    if ($status === \App\Models\Ticket::STATUS_READY_FOR_WORK) {
        $stageDState = 'active';
        $stageDDesc = 'Antrean Siap Dikerjakan';
    } elseif ($status === \App\Models\Ticket::STATUS_IN_PROGRESS) {
        $stageDState = 'active';
        $stageDDesc = 'Sedang Dikerjakan: ' . ($ticket->assignedTo?->name ?? 'Tim IT');
    } elseif ($status === \App\Models\Ticket::STATUS_REVISION_WORK) {
        $stageDState = 'revision';
        $stageDDesc = 'Revisi Pengerjaan';
    } elseif (in_array($status, [
        \App\Models\Ticket::STATUS_WAITING_USER_REVIEW,
        \App\Models\Ticket::STATUS_COMPLETED,
    ])) {
        $stageDState = 'completed';
        $stageDDesc = 'Selesai Dikerjakan';
    }
    $stageD = [
        'name' => 'Tahap D',
        'title' => 'Pengerjaan IT',
        'desc' => $stageDDesc,
        'state' => $stageDState,
    ];

    // Tahap E: Review Pemohon & Penutupan
    $stageEState = 'pending';
    $stageEDesc = 'Ulasan Pemohon';
    if ($status === \App\Models\Ticket::STATUS_WAITING_USER_REVIEW) {
        $stageEState = 'active';
        $stageEDesc = 'Menunggu Review Anda';
    } elseif ($status === \App\Models\Ticket::STATUS_COMPLETED) {
        $stageEState = 'completed';
        $stageEDesc = 'Tiket Selesai & Ditutup';
    }
    $stageE = [
        'name' => 'Tahap E',
        'title' => 'Review & Selesai',
        'desc' => $stageEDesc,
        'state' => $stageEState,
    ];

    $stages = [$stageA, $stageB, $stageC, $stageD, $stageE];
@endphp

<div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs mb-6 overflow-hidden">
    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Alur Siklus Hidup Tiket (Lifecycle Stepper)</h3>
        </div>
        <span class="text-xs text-slate-400 font-medium">5 Tahap Verifikasi</span>
    </div>

    <!-- Stepper Grid (Horizontal on Desktop, Stack/Scroll on Mobile) -->
    <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 relative">
        @foreach($stages as $index => $step)
            @php
                $isCompleted = $step['state'] === 'completed';
                $isActive = $step['state'] === 'active';
                $isRejected = $step['state'] === 'rejected';
                $isRevision = $step['state'] === 'revision';
                $isPending = $step['state'] === 'pending';

                // Node Box Style
                if ($isCompleted) {
                    $cardClass = 'bg-emerald-50/60 border-emerald-200 text-emerald-900';
                    $circleClass = 'bg-emerald-600 text-white shadow-xs';
                } elseif ($isActive) {
                    $cardClass = 'bg-blue-50/80 border-blue-300 text-blue-900 ring-2 ring-blue-100';
                    $circleClass = 'bg-blue-600 text-white shadow-xs animate-pulse';
                } elseif ($isRejected) {
                    $cardClass = 'bg-red-50 border-red-200 text-red-900';
                    $circleClass = 'bg-red-600 text-white';
                } elseif ($isRevision) {
                    $cardClass = 'bg-amber-50 border-amber-200 text-amber-900';
                    $circleClass = 'bg-amber-500 text-white';
                } else {
                    $cardClass = 'bg-slate-50/70 border-slate-200 text-slate-400';
                    $circleClass = 'bg-slate-200 text-slate-500';
                }
            @endphp

            <div class="rounded-lg border p-3 flex flex-col justify-between transition-all duration-150 {{ $cardClass }}">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider {{ $isActive ? 'text-blue-700' : ($isCompleted ? 'text-emerald-700' : 'text-slate-400') }}">
                        {{ $step['name'] }}
                    </span>
                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $circleClass }}">
                        @if($isCompleted)
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        @elseif($isRejected)
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        @elseif($isRevision)
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01" />
                            </svg>
                        @else
                            {{ $index + 1 }}
                        @endif
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-semibold leading-snug {{ $isActive ? 'text-blue-950 font-bold' : ($isCompleted ? 'text-slate-900' : 'text-slate-600') }}">
                        {{ $step['title'] }}
                    </h4>
                    <p class="text-xs mt-0.5 truncate {{ $isActive ? 'text-blue-700 font-medium' : ($isCompleted ? 'text-emerald-700' : 'text-slate-400') }}" title="{{ $step['desc'] }}">
                        {{ $step['desc'] }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>
</div>
