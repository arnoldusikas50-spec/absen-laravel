@extends('layouts.main')

@section('content')

<?php
$logList = collect($logs ?? []);
$totalScan = $logList->count();
$pesertaCount = (int) ($peserta_count ?? $totalScan);
$scanPulang = (int) ($scan_pulang_count ?? 0);
$badgeTanggal = 'inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600';
$badgeJam = 'inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-bold text-blue-700';
$badgePeserta = 'inline-flex items-center gap-1 rounded-full bg-blue-600 px-2.5 py-0.5 text-[11px] font-bold text-white';
$badgePulang = 'inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-700';
$badgeMasuk = 'inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700';
$badgeScanPulang = 'inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700';
?>

<div class="px-4 pb-6 pt-4 md:px-6 md:pt-6">

    <x-ui.page-header
        :title="$judul ?? 'Rekap Peserta QR'"
        icon="bi-people-fill"
        tone="blue"
        subtitle="Riwayat scan masuk dan pulang pada QR acara ini."
        :crumbs="['Presensi', 'QR Code', 'Rekap Peserta']">
        <x-slot:actions>
            <a href="{{ url('admin/presensi/qr') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-arrow-left"></i> Kembali ke QR
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mt-4">
        <x-ui.card title="{{ $event->nama_acara }}" icon="bi-info-circle" tone="blue"
            subtitle="Peserta yang berhasil memindai QR acara ini."
            bodyClass="p-0">
            <x-slot:meta>
                <span class="{{ $badgeTanggal }}">
                    <i class="bi bi-calendar3"></i>
                    {{ date('d/m/Y', strtotime($event->tanggal)) }}
                </span>
                <span class="{{ $badgeJam }}">
                    <i class="bi bi-clock"></i>
                    {{ substr((string) $event->jam_mulai, 0, 5) }} - {{ substr((string) $event->jam_selesai, 0, 5) }}
                </span>
                <span class="{{ $badgePeserta }}">
                    <i class="bi bi-people-fill"></i>{{ $pesertaCount }} peserta
                </span>
                @if ($scanPulang > 0)
                    <span class="{{ $badgePulang }}">
                        <i class="bi bi-box-arrow-right"></i>{{ $scanPulang }} scan pulang
                    </span>
                @endif
            </x-slot:meta>

            @if ($logList->isEmpty())
                <div class="p-5">
                    <x-ui.empty icon="bi-people" title="Belum ada peserta"
                        description="Belum ada guru yang memindai QR pada acara ini." />
                </div>
            @else
                <x-ui.table class="min-w-[720px]">
                    <caption class="sr-only">Riwayat scan peserta pada acara {{ $event->nama_acara }}</caption>
                    <x-slot:head>
                        <th class="w-10 px-4 py-3">#</th>
                        <th class="px-3 py-3">Nama Guru</th>
                        <th class="px-3 py-3">NIP</th>
                        <th class="px-3 py-3">Jenis</th>
                        <th class="px-4 py-3">Scan Pada</th>
                    </x-slot:head>

                    @foreach ($logList as $l)
                        <tr class="transition-colors hover:bg-slate-50/60">
                            <td class="px-4 py-3">
                                <span class="tabular-nums text-xs text-slate-400">{{ $loop->iteration }}</span>
                            </td>
                            <td class="px-3 py-3 text-xs font-bold text-slate-800">
                                {{ $l->nama_guru ?? 'Guru #' . $l->user_id }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 font-mono text-xs tabular-nums text-slate-600">
                                {{ $l->nip ?? '-' }}
                            </td>
                            <td class="px-3 py-3">
                                @if (($l->jenis ?? 'masuk') === 'pulang')
                                    <span class="{{ $badgeScanPulang }}">Pulang</span>
                                @else
                                    <span class="{{ $badgeMasuk }}">Masuk</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs tabular-nums text-slate-700">
                                {{ date('d/m/Y H:i:s', strtotime($l->scanned_at)) }}
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif

            <x-slot:footer>
                <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400">
                    <span>Total scan: <span class="font-semibold text-slate-600">{{ $totalScan }}</span></span>
                    <span>{{ $pesertaCount }} peserta terdaftar</span>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </div>
</div>
@endsection
