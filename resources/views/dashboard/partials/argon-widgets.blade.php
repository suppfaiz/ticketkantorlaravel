<!-- Argon Middle Widgets: Sales Value Line Chart, Total Orders Bar Chart, Progress Track -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-5 mb-6">
    <!-- Chart 1: Sales Value / Tren Volume Tiket (5 cols) -->
    <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-100 p-5 shadow-sm flex flex-col justify-between">
        <div class="mb-4">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block">OVERVIEW</span>
            <h3 class="text-base font-bold text-slate-800 tracking-tight mt-0.5">Tren Volume Tiket</h3>
        </div>
        <div class="h-56 relative w-full">
            <canvas id="chart-sales-value"></canvas>
        </div>
        <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span class="flex items-center gap-1.5 font-medium text-emerald-600">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                +14.5% dibanding bulan lalu
            </span>
            <span class="text-slate-400">Update real-time</span>
        </div>
    </div>

    <!-- Chart 2: Total Orders / Beban Tiket (3 cols) -->
    <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-100 p-5 shadow-sm flex flex-col justify-between">
        <div class="mb-4">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block">PERFORMANCE</span>
            <h3 class="text-base font-bold text-slate-800 tracking-tight mt-0.5">Distribusi Beban</h3>
        </div>
        <div class="h-56 relative w-full">
            <canvas id="chart-total-orders"></canvas>
        </div>
        <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span class="font-semibold text-slate-700">Rata-rata 24 tiket/minggu</span>
            <span class="text-[#FB6340] font-bold">Optimal</span>
        </div>
    </div>

    <!-- Widget 3: Progress Track (4 cols) -->
    <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-100 p-5 shadow-sm flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest block">5/23 PROJECTS</span>
                <h3 class="text-base font-bold text-slate-800 tracking-tight mt-0.5">Progress track</h3>
            </div>
            <a href="{{ route('tickets.index') }}" class="px-2.5 py-1 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-50 border border-slate-200 rounded-lg shadow-xs transition-colors">
                Action
            </a>
        </div>

        <div class="space-y-4">
            <!-- Item 1: NALA Helpdesk Core -->
            <a href="{{ route('tickets.index') }}" class="block group">
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-6 h-6 rounded bg-[#1E40AF] text-white flex items-center justify-center font-bold text-xs shadow-xs group-hover:scale-110 transition-transform">
                            B
                        </div>
                        <span class="font-semibold text-slate-800 group-hover:text-[#1E40AF] transition-colors truncate max-w-[150px]">Sistem Helpdesk NALA</span>
                    </div>
                    <span class="text-xs font-bold text-slate-500">60%</span>
                </div>
                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-[#1E40AF] rounded-full transition-all" style="width: 60%"></div>
                </div>
            </a>

            <!-- Item 2: Angular Now UI Kit PRO -->
            <a href="{{ route('tickets.index', ['status' => \App\Models\Ticket::STATUS_READY_FOR_WORK]) }}" class="block group">
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-6 h-6 rounded bg-[#F5365C] text-white flex items-center justify-center font-bold text-xs shadow-xs group-hover:scale-110 transition-transform">
                            A
                        </div>
                        <span class="font-semibold text-slate-800 group-hover:text-[#F5365C] transition-colors truncate max-w-[150px]">Angular Now UI Kit</span>
                    </div>
                    <span class="text-xs font-bold text-slate-500">100%</span>
                </div>
                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-[#2DCE89] rounded-full transition-all" style="width: 100%"></div>
                </div>
            </a>

            <!-- Item 3: Black Dashboard -->
            <a href="{{ route('tickets.index', ['status' => \App\Models\Ticket::STATUS_WAITING_SUPERVISOR]) }}" class="block group">
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-6 h-6 rounded bg-[#FB6340] text-white flex items-center justify-center font-bold text-xs shadow-xs group-hover:scale-110 transition-transform">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 2l3 5H7l3-5zm-5 6h10l-5 9-5-9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="font-semibold text-slate-800 group-hover:text-[#FB6340] transition-colors truncate max-w-[150px]">Black Dashboard LOA</span>
                    </div>
                    <span class="text-xs font-bold text-slate-500">72%</span>
                </div>
                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-[#F5365C] rounded-full transition-all" style="width: 72%"></div>
                </div>
            </a>

            <!-- Item 4: React Material Dashboard -->
            <a href="{{ route('tickets.index', ['status' => \App\Models\Ticket::STATUS_IN_PROGRESS]) }}" class="block group">
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-6 h-6 rounded bg-[#11CDEF] text-white flex items-center justify-center font-bold text-xs shadow-xs group-hover:scale-110 transition-transform">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="font-semibold text-slate-800 group-hover:text-[#11CDEF] transition-colors truncate max-w-[150px]">React Material Work</span>
                    </div>
                    <span class="text-xs font-bold text-slate-500">90%</span>
                </div>
                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-[#11CDEF] rounded-full transition-all" style="width: 90%"></div>
                </div>
            </a>

            <!-- Item 5: Vue Paper UI Kit PRO -->
            <a href="{{ route('tickets.index', ['status' => \App\Models\Ticket::STATUS_COMPLETED]) }}" class="block group">
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-6 h-6 rounded bg-[#2DCE89] text-white flex items-center justify-center font-bold text-xs shadow-xs group-hover:scale-110 transition-transform">
                            V
                        </div>
                        <span class="font-semibold text-slate-800 group-hover:text-[#2DCE89] transition-colors truncate max-w-[150px]">Vue Paper UI Kit</span>
                    </div>
                    <span class="text-xs font-bold text-slate-500">80%</span>
                </div>
                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-[#2DCE89] rounded-full transition-all" style="width: 80%"></div>
                </div>
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    // Line Chart: Sales Value
    const salesCanvas = document.getElementById('chart-sales-value');
    if (salesCanvas) {
        if (salesCanvas._chartInstance) {
            salesCanvas._chartInstance.destroy();
        }
        const ctxSales = salesCanvas.getContext('2d');
        const salesGradient = ctxSales.createLinearGradient(0, 0, 0, 220);
        salesGradient.addColorStop(0, 'rgba(30, 64, 175, 0.25)');
        salesGradient.addColorStop(1, 'rgba(30, 64, 175, 0.0)');

        salesCanvas._chartInstance = new Chart(salesCanvas, {
            type: 'line',
            data: {
                labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Tiket Selesai',
                    data: [0, 20, 10, 30, 15, 40, 20, 60],
                    borderColor: '#1E40AF',
                    borderWidth: 3,
                    pointBackgroundColor: '#1E40AF',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4,
                    fill: true,
                    backgroundColor: salesGradient,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1E3A8A',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#8898AA', font: { size: 11 } }
                    },
                    y: {
                        grid: { color: '#F1F3F9', drawBorder: false },
                        ticks: { color: '#8898AA', font: { size: 11 }, stepSize: 10 }
                    }
                }
            }
        });
    }

    // Bar Chart: Total Orders
    const ordersCanvas = document.getElementById('chart-total-orders');
    if (ordersCanvas) {
        if (ordersCanvas._chartInstance) {
            ordersCanvas._chartInstance.destroy();
        }
        ordersCanvas._chartInstance = new Chart(ordersCanvas, {
            type: 'bar',
            data: {
                labels: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Volume Order',
                    data: [25, 20, 30, 22, 17, 29],
                    backgroundColor: '#FB6340',
                    borderRadius: 6,
                    barThickness: 10,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1E3A8A',
                        cornerRadius: 8,
                        padding: 10
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#8898AA', font: { size: 11 } }
                    },
                    y: {
                        grid: { color: '#F1F3F9', drawBorder: false },
                        ticks: { color: '#8898AA', font: { size: 11 }, stepSize: 10 }
                    }
                }
            }
        });
    }
});
</script>
