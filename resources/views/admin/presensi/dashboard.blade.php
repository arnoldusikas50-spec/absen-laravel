@extends('layouts.main')

@section('content')

<?php
$pageConfig = [
    'lateTrend' => $lateTrend ?? [],
    'comparison' => $comparison ?? [],
];

$quickLinks = [
    ['url' => 'scanner', 'icon' => 'bi-display', 'color' => 'emerald', 'label' => 'Scanner QR Siswa', 'badge' => null],
    ['url' => 'admin/presensi/manual', 'icon' => 'bi-person-check-fill', 'color' => 'blue', 'label' => 'Absen Manual', 'badge' => null],
    ['url' => 'admin/presensi/dinas', 'icon' => 'bi-briefcase-fill', 'color' => 'cyan', 'label' => 'Verifikasi Dinas', 'badge' => $summary['pending_dinas'] > 0 ? $summary['pending_dinas'] : null],
    ['url' => 'admin/presensi/koreksi', 'icon' => 'bi-pencil-square', 'color' => 'amber', 'label' => 'Koreksi Manual', 'badge' => $summary['pending_koreksi'] > 0 ? $summary['pending_koreksi'] : null],
    ['url' => 'admin/presensi/rekap', 'icon' => 'bi-file-earmark-spreadsheet-fill', 'color' => 'emerald', 'label' => 'Rekap Bulanan', 'badge' => null],
    ['url' => 'admin/presensi/setting', 'icon' => 'bi-gear-fill', 'color' => 'slate', 'label' => 'Pengaturan Absen', 'badge' => null],
];

$colorMap = [
    'blue' => 'bg-blue-50 text-blue-600 group-hover:bg-blue-100',
    'cyan' => 'bg-cyan-50 text-cyan-600 group-hover:bg-cyan-100',
    'amber' => 'bg-amber-50 text-amber-600 group-hover:bg-amber-100',
    'emerald' => 'bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100',
    'slate' => 'bg-slate-100 text-slate-600 group-hover:bg-slate-200',
];

$kpi = [
    ['label' => 'Total Guru', 'nilai' => $summary['total_guru'], 'icon' => 'bi-people-fill', 'bg' => 'bg-blue-100', 'teks' => 'text-blue-600'],
    ['label' => 'Hadir Hari Ini', 'nilai' => $summary['hadir_hari_ini'], 'icon' => 'bi-check-circle-fill', 'bg' => 'bg-emerald-100', 'teks' => 'text-emerald-600'],
    ['label' => 'Telat Bulan Ini', 'nilai' => $summary['telat_bulan_ini'], 'icon' => 'bi-clock-fill', 'bg' => 'bg-amber-100', 'teks' => 'text-amber-600'],
    ['label' => 'Pending Review', 'nilai' => $summary['pending_dinas'] + $summary['pending_koreksi'], 'icon' => 'bi-exclamation-triangle-fill', 'bg' => 'bg-red-100', 'teks' => 'text-red-600'],
];
?>

<div x-data="dashboardPresensi()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            title="Dashboard Presensi"
            icon="bi-bar-chart-line-fill"
            tone="blue"
            subtitle="Monitoring dan analisis kehadiran guru"
            :crumbs="['Presensi', 'Dashboard']">
            <x-slot:actions>
                <a href="{{ url('admin/presensi') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                    <i class="bi bi-speedometer2"></i> Monitoring
                </a>
                <a href="{{ url('admin/presensi/rekap') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white no-underline shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                    <i class="bi bi-file-earmark-spreadsheet-fill"></i> Rekap Bulanan
                </a>
            </x-slot:actions>
        </x-ui.page-header>
    </div>

    <div class="mt-4 space-y-5 px-4 pb-6 md:px-6">

        <!-- Ringkasan -->
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach ($kpi as $kartu)
                <x-ui.card bodyClass="p-5" class="transition-transform duration-300 hover:-translate-y-1">
                    <div class="flex items-center gap-4">
                        <span class="flex shrink-0 items-center justify-center rounded-xl p-3 {{ $kartu['bg'] }} {{ $kartu['teks'] }}">
                            <i class="bi {{ $kartu['icon'] }} text-2xl"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $kartu['label'] }}</p>
                            <p class="text-2xl font-bold text-slate-800">{{ $kartu['nilai'] }}</p>
                        </div>
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <!-- Trend keterlambatan -->
            <x-ui.card class="lg:col-span-2" title="Trend Keterlambatan" subtitle="12 Bulan Terakhir"
                icon="bi-graph-up" tone="blue" bodyClass="p-6">
                <div class="relative h-[280px]">
                    <canvas x-ref="trendChart" aria-label="Grafik tren keterlambatan 12 bulan terakhir" role="img"></canvas>
                </div>
            </x-ui.card>

            <!-- Menu cepat -->
            <x-ui.card title="Menu Cepat" subtitle="Akses cepat fitur" icon="bi-lightning-charge-fill"
                tone="amber" bodyClass="p-4">
                <div class="space-y-2">
                    @foreach ($quickLinks as $ql)
                        <a href="{{ url($ql['url']) }}"
                            class="group flex items-center justify-between rounded-xl border border-slate-100 p-3 text-slate-700 no-underline transition-all hover:translate-x-1 hover:border-blue-200 hover:bg-blue-50/40">
                            <span class="flex items-center gap-3">
                                <span class="{{ $colorMap[$ql['color']] }} flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-colors">
                                    <i class="bi {{ $ql['icon'] }} text-lg"></i>
                                </span>
                                <span class="text-sm font-semibold">{{ $ql['label'] }}</span>
                            </span>
                            @if ($ql['badge'])
                                <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-bold text-red-600">{{ $ql['badge'] }}</span>
                            @else
                                <i class="bi bi-chevron-right text-sm text-slate-300 transition-colors group-hover:text-blue-400"></i>
                            @endif
                        </a>
                    @endforeach
                </div>
            </x-ui.card>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <!-- Komparasi kehadiran -->
            <x-ui.card title="Komparasi Kehadiran" subtitle="Perbandingan status kehadiran"
                icon="bi-bar-chart-fill" tone="blue" bodyClass="p-6">
                <div class="relative h-[280px]">
                    <canvas x-ref="comparisonChart" aria-label="Grafik komparasi kehadiran guru" role="img"></canvas>
                </div>
            </x-ui.card>

            <!-- Ranking kedisiplinan -->
            <x-ui.card title="Ranking Kedisiplinan" subtitle="Top 20 Guru Terbaik"
                icon="bi-trophy-fill" tone="amber" bodyClass="p-0">
                <div class="max-h-[320px] overflow-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <caption class="sr-only">Ranking kedisiplinan guru</caption>
                        <thead class="sticky top-0 z-[1] bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr class="border-b-2 border-slate-200/80">
                                <th scope="col" class="px-4 py-3">#</th>
                                <th scope="col" class="px-4 py-3">Nama Guru</th>
                                <th scope="col" class="px-4 py-3 text-center">Hadir</th>
                                <th scope="col" class="px-4 py-3 text-center">Tepat</th>
                                <th scope="col" class="px-4 py-3 text-center">Telat</th>
                                <th scope="col" class="px-4 py-3 text-right">Skor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($ranking as $r)
                                <tr class="transition-colors hover:bg-slate-50/60">
                                    <td class="px-4 py-3">
                                        @if ($loop->iteration === 1)
                                            <i class="bi bi-trophy-fill text-xl text-amber-400"></i>
                                        @elseif ($loop->iteration === 2)
                                            <i class="bi bi-trophy-fill text-xl text-slate-400"></i>
                                        @elseif ($loop->iteration === 3)
                                            <i class="bi bi-trophy-fill text-xl text-orange-400"></i>
                                        @else
                                            <span class="text-lg font-bold text-slate-400">{{ $loop->iteration }}</span>
                                        @endif
                                    </td>
                                    <td class="max-w-[150px] truncate px-4 py-3 font-medium text-slate-700">
                                        {{ $r->first_name . ' ' . $r->last_name }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-slate-600">{{ $r->total_hadir }}</td>
                                    <td class="px-4 py-3 text-center font-bold text-emerald-600">{{ $r->ontime }}</td>
                                    <td class="px-4 py-3 text-center font-bold text-red-500">{{ $r->late }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                                            {{ $r->score >= 80 ? 'bg-emerald-100 text-emerald-700' : ($r->score >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                            {{ number_format($r->score, 1) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-400">
                                        <i class="bi bi-inbox mb-3 block text-4xl opacity-50"></i>
                                        <p class="text-sm">Belum ada data</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardPresensi', () => ({
        config: { lateTrend: [], comparison: [] },
        charts: [],

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');

            this.config = {
                lateTrend: cfg.lateTrend || [],
                comparison: cfg.comparison || [],
            };

            // Chart.js dimuat oleh <script> klasik di atas (sebelum modul Alpine).
            // Bila CDN gagal, panel tetap tampil tanpa grafik, bukan error JS.
            if (typeof window.Chart === 'undefined') {
                return;
            }

            this.renderTrend();
            this.renderComparison();
        },

        destroy() {
            this.charts.forEach((chart) => chart.destroy());
            this.charts = [];
        },

        commonOptions() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
                        },
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 13 },
                        bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { family: "'Plus Jakarta Sans', sans-serif" } } },
                    y: {
                        border: { dash: [4, 4] },
                        grid: { color: '#f3f4f6' },
                        ticks: { font: { family: "'Plus Jakarta Sans', sans-serif" }, maxTicksLimit: 5 },
                    },
                },
            };
        },

        renderTrend() {
            const el = this.$refs.trendChart;

            if (! el) {
                return;
            }

            const data = this.config.lateTrend;
            const options = this.commonOptions();

            options.scales = {
                ...options.scales,
                y: { ...options.scales.y, beginAtZero: true },
            };

            this.charts.push(new Chart(el.getContext('2d'), {
                type: 'line',
                data: {
                    labels: data.map((d) => d.month),
                    datasets: [
                        {
                            label: 'Jumlah Terlambat',
                            data: data.map((d) => d.total_late),
                            borderColor: '#f59e0b',
                            backgroundColor: (context) => {
                                const ctx = context.chart.ctx;
                                const gradient = ctx.createLinearGradient(0, 0, 0, 280);
                                gradient.addColorStop(0, 'rgba(245, 158, 11, 0.2)');
                                gradient.addColorStop(1, 'rgba(245, 158, 11, 0)');

                                return gradient;
                            },
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        },
                    ],
                },
                options,
            }));
        },

        renderComparison() {
            const el = this.$refs.comparisonChart;

            if (! el) {
                return;
            }

            const data = this.config.comparison.slice(0, 10);
            const options = this.commonOptions();

            options.scales = {
                x: { ...options.scales.x, stacked: true },
                y: { ...options.scales.y, stacked: true, beginAtZero: true },
            };

            this.charts.push(new Chart(el.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: data.map((d) => d.first_name),
                    datasets: [
                        { label: 'Tepat Waktu', data: data.map((d) => parseInt(d.total_tepat, 10)), backgroundColor: '#10b981', borderRadius: 6 },
                        { label: 'Terlambat', data: data.map((d) => parseInt(d.total_telat, 10)), backgroundColor: '#f59e0b', borderRadius: 6 },
                        { label: 'Dinas', data: data.map((d) => parseInt(d.total_dinas, 10)), backgroundColor: '#3b82f6', borderRadius: 6 },
                    ],
                },
                options,
            }));
        },
    }));
});
</script>
@endsection
