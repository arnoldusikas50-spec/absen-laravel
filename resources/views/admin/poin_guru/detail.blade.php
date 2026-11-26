@extends('layouts.main')

@section('content')
<div class="space-y-4 px-4 pb-8 pt-4 md:px-6 md:pt-6">

    <x-ui.page-header
        title="Detail Poin Guru"
        icon="bi-person-badge"
        tone="violet"
        :subtitle="'Rekap poin tahun ' . $currentYear . ' — ' . $teacher->nama_guru"
        :crumbs="['Poin Guru', 'Daftar Peringkat', 'Detail']">
        <x-slot:actions>
            <a href="{{ url('admin/poinguru') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-100">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar Peringkat
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- Header / Profile -->
    @php
        $photo = $teacher->foto ?: $teacher->user_foto;
        $initials = substr($teacher->nama_guru, 0, 1) . (strpos($teacher->nama_guru, ' ') ? substr($teacher->nama_guru, strpos($teacher->nama_guru, ' ') + 1, 1) : '');
        $fotoUrl = '';
        if ($photo) {
            if (strpos($photo, 'uploads/') === false) {
                $photo = 'uploads/profile_guru/' . $photo;
            }
            if (file_exists(public_path() . '/' . $photo)) {
                $fotoUrl = url($photo);
            }
        }

        $totalPoin = 0;
        foreach ($poinHistory as $row) {
            $totalPoin += (float) $row->poin;
        }

        $tipeBadge = [
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'danger' => 'border-rose-200 bg-rose-50 text-rose-700',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
            'secondary' => 'border-slate-200 bg-slate-100 text-slate-600',
        ];
        $tipeKelas = static function (string $tipe) use ($tipeBadge): string {
            $tipeUpper = strtoupper($tipe);
            if (in_array($tipeUpper, ['HADIR', 'REWARD', 'BONUS'], true)) {
                return $tipeBadge['success'];
            }
            if (in_array($tipeUpper, ['TELAT', 'PENALTI', 'ALPA'], true)) {
                return $tipeBadge['danger'];
            }
            if (in_array($tipeUpper, ['REDEEM', 'CLAIMED'], true)) {
                return $tipeBadge['warning'];
            }

            return $tipeBadge['secondary'];
        };
    @endphp

    <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-[#667eea] to-[#764ba2] p-5 text-white shadow-xs">
        <div class="flex flex-wrap gap-4">
            <div class="text-center">
                @if ($fotoUrl)
                    <img src="{{ $fotoUrl }}" alt="Profile"
                        class="h-[120px] w-[120px] rounded-3xl border-4 border-white/20 object-cover shadow">
                @else
                    <div class="flex h-[120px] w-[120px] items-center justify-center rounded-3xl bg-white text-4xl font-bold text-blue-600 shadow">
                        {{ $initials }}
                    </div>
                @endif
            </div>
            <div class="min-w-0 flex-1 pl-0 md:pl-4">
                <span class="inline-flex items-center rounded-full border border-white/30 bg-white/20 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider">
                    Profil Guru
                </span>
                <h1 class="mb-1 mt-1 text-xl font-bold uppercase">{{ $teacher->nama_guru }}</h1>
                <p class="mb-0 text-xl opacity-75">NIP: {{ $teacher->nip ?: '-' }}</p>
                <div class="mt-6">
                    <div class="grid grid-cols-1 gap-2">
                        <div class="rounded-lg bg-white/20 px-4 py-3">
                            <span class="block text-sm opacity-75">Total Poin {{ $currentYear }}</span>
                            <span class="text-2xl font-bold">{{ number_format($totalPoin, 1) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div x-data="poinGuruChart()"
        data-monthly="{{ json_encode($monthly_stats, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">
        <x-ui.card title="Trend Poin Bulanan" icon="bi-graph-up" tone="violet" bodyClass="p-5">
            <div class="h-[300px]">
                <canvas x-ref="monthlyChart"></canvas>
            </div>
        </x-ui.card>
    </div>

    <!-- Type Summary -->
    <x-ui.card title="Ringkasan per Tipe" icon="bi-pie-chart" tone="blue" bodyClass="p-5">
        @if (!empty($typeSummary))
            <div class="space-y-2">
                @foreach ($typeSummary as $tipe => $info)
                    @php $isPos = $info['poin'] >= 0; @endphp
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2 last:border-0 last:pb-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider {{ $tipeKelas($tipe) }}">{{ $tipe }}</span>
                            <span class="text-sm text-slate-400">{{ $info['count'] }}x</span>
                        </div>
                        <span class="font-bold {{ $isPos ? 'text-emerald-600' : 'text-rose-500' }}">
                            {{ $isPos ? '+' : '' }}{{ number_format($info['poin'], 1) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="mb-0 text-sm text-slate-400">Belum ada data poin.</p>
        @endif
    </x-ui.card>

    <!-- Detailed History -->
    <x-ui.card title="Riwayat Poin" icon="bi-clock-history" tone="slate" bodyClass="p-0">
        <x-slot:meta>
            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-600">
                <i class="bi bi-calendar-event"></i> {{ $currentYear }}
            </span>
        </x-slot:meta>

        <x-ui.table>
            <x-slot:head>
                <th class="px-6 py-4 text-left">Tanggal</th>
                <th class="px-6 py-4 text-left">Tipe</th>
                <th class="px-6 py-4 text-left">Keterangan</th>
                <th class="px-6 py-4 text-right">Poin</th>
            </x-slot:head>

            @forelse ($poinHistory as $row)
                @php $isPos = (float) $row->poin >= 0; @endphp
                <tr class="transition-colors hover:bg-violet-50/40">
                    <td class="px-6 py-3">
                        <div class="font-bold text-slate-800">{{ date('d M Y', strtotime($row->created_at)) }}</div>
                        <div class="text-sm text-slate-400">{{ date('H:i', strtotime($row->created_at)) }}</div>
                    </td>
                    <td class="px-6 py-3">
                        <span class="inline-flex items-center rounded-full border px-2 py-1 text-sm {{ $tipeKelas((string) $row->tipe) }}">
                            {{ $row->tipe }}
                        </span>
                    </td>
                    <td class="px-6 py-3">
                        <div class="text-sm text-slate-600">{{ $row->keterangan }}</div>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <span class="rounded-lg px-3 py-1 text-sm font-extrabold {{ $isPos ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-500' }}">
                            {{ $isPos ? '+' : '' }}{{ number_format((float) $row->poin, 1) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-slate-400">Belum ada riwayat poin untuk tahun {{ $currentYear }}.</td>
                </tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>

    <!-- Formula Reference -->
    <x-ui.card title="Sistem Poin Terintegrasi" icon="bi-info-circle" tone="slate" bodyClass="p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="h-full rounded-lg bg-slate-50 p-3">
                <div class="mb-1 font-bold text-slate-800">HADIR / TELAT</div>
                <ul class="mb-0 list-disc space-y-0.5 pl-4 text-xs text-slate-600">
                    <li>ONTIME: poin progresif (+10, +12, +15, +20)</li>
                    <li>LATE: -5 tetap</li>
                    <li>ALPA: -10 tetap</li>
                </ul>
            </div>
            <div class="h-full rounded-lg bg-slate-50 p-3">
                <div class="mb-1 font-bold text-slate-800">CHECK-OUT</div>
                <ul class="mb-0 list-disc space-y-0.5 pl-4 text-xs text-slate-600">
                    <li>EARLY: PENALTI progresif (-2, -3, -5)</li>
                    <li>After hours: REWARD progresif (+1, +2, +3)</li>
                </ul>
            </div>
            <div class="h-full rounded-lg bg-slate-50 p-3">
                <div class="mb-1 font-bold text-slate-800">BONUS + REDEEM</div>
                <ul class="mb-0 list-disc space-y-0.5 pl-4 text-xs text-slate-600">
                    <li>BONUS: skor disiplin bulanan (1 skor = 1 poin)</li>
                    <li>REDEEM: pengurangan saat tukar voucher</li>
                    <li>CLAIMED: poin voucher dikembalikan</li>
                </ul>
            </div>
            <div class="h-full rounded-lg bg-slate-50 p-3">
                <div class="mb-1 font-bold text-slate-800">Sinkronisasi</div>
                <ul class="mb-0 list-disc space-y-0.5 pl-4 text-xs text-slate-600">
                    <li>Real-time saat check-in/out</li>
                    <li>Cron markAlpha untuk ALPA</li>
                    <li>Seed ulang via tombol Update</li>
                </ul>
            </div>
        </div>
    </x-ui.card>

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('poinGuruChart', () => ({
        chart: null,

        init() {
            const canvas = this.$refs.monthlyChart;

            if (! canvas || typeof window.Chart === 'undefined') {
                return;
            }

            let stats = {};

            try {
                stats = JSON.parse(this.$el.dataset.monthly || '{}') || {};
            } catch (error) {
                stats = {};
            }

            const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            const data = months.map((m) => (stats[m] ? stats[m].points : 0));

            this.chart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Total Poin',
                        data: data,
                        borderColor: '#764ba2',
                        backgroundColor: 'rgba(118, 75, 162, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#764ba2',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { display: false }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        },

        destroy() {
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
        }
    }));
});
</script>
@endsection
