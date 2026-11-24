@extends('layouts.main')

@section('content')
<?php
$skor = (int) ($skor ?? 100);
$skorCls = $skor >= 90 ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($skor >= 75 ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-rose-200 bg-rose-50 text-rose-700');
$namaBulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$typeBadge = [
    'PELANGGARAN' => 'border-rose-200 bg-rose-50 text-rose-700',
    'PENGHARGAAN' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
    'KOREKSI' => 'border-amber-200 bg-amber-50 text-amber-700',
    'PRESENSI' => 'border-sky-200 bg-sky-50 text-sky-700',
];
$pageConfig = [
    'labels' => array_values(array_slice($namaBulan, 1)),
    'values' => array_map('intval', array_values($monthly ?? [])),
];
?>

<div x-data="poinSiswaDetail()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
    class="px-4 pb-6 pt-4 md:px-6 md:pt-6">

    <x-ui.page-header
        :title="$siswa->nama ?? '-'"
        icon="bi-person-badge"
        tone="rose"
        :subtitle="'NIS ' . ($siswa->nis ?? '-') . ' · NISN ' . ($siswa->nisn ?? '-') . ' · Tahun ' . (int) $tahun . ' · TP ' . ($tp_active->tahun ?? '-')"
        :crumbs="['Poin Disiplin Siswa', 'Detail']">
        <x-slot:actions>
            <a href="{{ url('admin/poin-siswa') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <form method="get" class="flex items-center gap-1.5">
                <input type="number" name="tahun" value="{{ (int) $tahun }}" min="2020" max="{{ date('Y') + 1 }}"
                    aria-label="Tahun"
                    class="w-24 rounded-xl border border-slate-200 bg-white px-3 py-2 text-center text-xs font-semibold text-slate-700 shadow-xs focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" title="Tampilkan tahun"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-xs transition-colors hover:bg-slate-50">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mt-4 grid grid-cols-1 items-start gap-4 lg:grid-cols-5">
        <div class="space-y-4 lg:col-span-3">
            <x-ui.card bodyClass="p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-2xl font-extrabold text-rose-600">
                        {{ mb_substr($siswa->nama ?? '?', 0, 1) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Skor Disiplin</p>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full border px-4 py-1 text-2xl font-extrabold {{ $skorCls }}">{{ $skor }}</span>
                            <span class="text-[11px] text-slate-400">dari 100</span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">Total poin tercatat sepanjang tahun {{ (int) $tahun }}.</p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Pergerakan Poin {{ (int) $tahun }}" icon="bi-graph-up" tone="rose"
                subtitle="Total poin tercatat per bulan (negatif = pelanggaran dominan)">
                <div style="height:260px"><canvas x-ref="canvas"></canvas></div>
            </x-ui.card>

            <x-ui.card title="Ringkasan per Jenis" icon="bi-pie-chart" tone="rose">
                @if (empty($type_summary))
                    <p class="text-xs text-slate-400">Belum ada catatan tahun ini.</p>
                @else
                    <div class="flex flex-wrap gap-2">
                        @foreach ($type_summary as $tipe => $s)
                            <span class="inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-bold {{ $typeBadge[$tipe] ?? 'border-slate-200 bg-slate-100 text-slate-600' }}">
                                {{ $tipe }}: {{ $s['poin'] > 0 ? '+' : '' }}{{ (int) $s['poin'] }} ({{ (int) $s['count'] }}×)
                            </span>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>

        <div class="lg:col-span-2">
            <x-ui.card title="Riwayat Transaksi" icon="bi-clock-history" tone="slate" bodyClass="p-5">
                @if (empty($riwayat))
                    <x-ui.empty icon="bi-journal-x" title="Belum ada riwayat"
                        description="Poin pelanggaran, penghargaan, dan koreksi akan tampil di sini." />
                @else
                    <div class="max-h-[560px] space-y-2 overflow-y-auto pr-1">
                        @foreach ($riwayat as $h)
                            <?php $p = (int) ($h->poin ?? 0); ?>
                            <div class="flex items-center gap-2.5 rounded-xl border border-slate-100 px-3 py-2">
                                <span class="inline-flex min-w-11 items-center justify-center rounded-full border px-2 py-0.5 font-mono text-xs font-bold {{ $p >= 0 ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                                    {{ $p > 0 ? '+' : '' }}{{ $p }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-xs font-bold text-slate-700">{{ $h->kategori_nama ?? $h->tipe }}</div>
                                    <div class="truncate text-[11px] text-slate-400">{{ $h->keterangan ?? '' }}</div>
                                    <div class="font-mono text-[10px] text-slate-300">{{ $h->created_at ?? '' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('poinSiswaDetail', () => ({
        config: { labels: [], values: [] },
        chart: null,

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');

            this.config = {
                labels: cfg.labels || [],
                values: cfg.values || [],
            };

            this.render();
        },

        render() {
            const canvas = this.$refs.canvas;

            if (!canvas || !window.Chart) return;

            this.chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: this.config.labels,
                    datasets: [{
                        label: 'Poin',
                        data: this.config.values,
                        backgroundColor: this.config.values.map((v) => (v >= 0 ? 'rgba(16,185,129,.6)' : 'rgba(239,68,68,.6)')),
                        borderRadius: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: false } },
                },
            });
        },
    }));
});
</script>
@endsection
