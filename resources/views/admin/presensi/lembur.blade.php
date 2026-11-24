@extends('layouts.main')
@section('title', 'Lembur / Overtime')

@section('content')
<?php
$namaBulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
$curYear = (int) date('Y');
$labelPeriode = ($namaBulan[(int) $bulan] ?? $bulan) . ' ' . $tahun;
$fmtJam = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');

$pageConfig = [
    'csrf' => csrf_token(),
    'urls' => [
        'approve' => url('admin/presensi/lembur/approve'),
        'reject' => url('admin/presensi/lembur/reject'),
    ],
];
?>

<div x-data="lemburPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            title="Kelola & Verifikasi Lembur"
            icon="bi-clock-fill"
            tone="amber"
            :subtitle="'Persetujuan pengajuan lembur guru · Tarif ' . (int) $pointPerJam . ' poin/jam (diatur di Pengaturan Presensi)'"
            :crumbs="['Presensi', 'Lembur']">
            <x-slot:actions>
                <button type="button" @click="reload()" title="Muat ulang"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 shadow-xs transition-colors hover:bg-slate-50">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <a href="{{ url('admin/presensi') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </x-slot:actions>
        </x-ui.page-header>
    </div>

    <div class="mt-4 space-y-4 px-4 pb-6 md:px-6">

        @if (! empty($pending))
            <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <i class="bi bi-exclamation-triangle-fill mt-0.5 text-amber-500"></i>
                <div class="text-sm text-amber-800">
                    Ada <strong>{{ count($pending) }} pengajuan ({{ $fmtJam($summary['jam_pending'] ?? 0) }} jam)</strong>
                    menunggu persetujuan. Menyetujui akan menambah poin ke saldo guru (+{{ (int) $pointPerJam }} poin/jam).
                </div>
            </div>
        @endif

        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-500">
                    <i class="bi bi-hourglass-split text-lg"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-[11px] font-bold text-slate-500">Menunggu</div>
                    <div class="text-lg font-extrabold text-slate-900 tabular-nums">
                        {{ count($pending) }} <span class="text-xs font-normal text-slate-400">pengajuan</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <i class="bi bi-clock text-lg"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-[11px] font-bold text-slate-500">Jam Menunggu</div>
                    <div class="text-lg font-extrabold text-slate-900 tabular-nums">
                        {{ $fmtJam($summary['jam_pending'] ?? 0) }} <span class="text-xs font-normal text-slate-400">jam</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <i class="bi bi-check-circle text-lg"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-[11px] font-bold text-slate-500">Jam Disetujui</div>
                    <div class="text-lg font-extrabold text-slate-900 tabular-nums">
                        {{ $fmtJam($summary['jam_disetujui'] ?? 0) }} <span class="text-xs font-normal text-slate-400">jam</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i class="bi bi-star text-lg"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-[11px] font-bold text-slate-500">Poin Bulan Ini</div>
                    <div class="text-lg font-extrabold text-slate-900 tabular-nums">
                        +{{ number_format((int) ($summary['poin_bulan'] ?? 0)) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Pending --}}
        <div x-data="dataTable({ perPage: 10 })">
            <x-ui.card title="Perlu Persetujuan ({{ count($pending) }})" icon="bi-hourglass-split" tone="amber" bodyClass="p-0">
                @if (empty($pending))
                    <div class="p-5">
                        <x-ui.empty icon="bi-check-circle" title="Tidak ada pengajuan menunggu"
                            description="Semua pengajuan lembur sudah diproses." />
                    </div>
                @else
                    <x-ui.table>
                        <x-slot:head>
                            <th class="w-12 px-3 py-3">#</th>
                            <th class="px-4 py-3">Guru</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Jam</th>
                            <th class="px-3 py-3 text-center">Total</th>
                            <th class="px-3 py-3 text-center">Estimasi Poin</th>
                            <th class="px-4 py-3">Keterangan</th>
                            <th class="px-4 py-3">Diajukan</th>
                            <th class="w-44 px-3 py-3 text-center">Aksi</th>
                        </x-slot:head>

                        @foreach ($pending as $p)
                            @php $poinEst = (int) round((float) $p->total_jam * (int) $pointPerJam); @endphp
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-3 py-2.5">
                                    <span data-rownum class="text-xs font-bold text-slate-400">{{ $loop->iteration }}</span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="font-bold text-slate-800">{{ $p->nama_guru ?? 'Guru #' . $p->user_id }}</div>
                                    @if (! empty($p->nip))
                                        <div class="font-mono text-[11px] text-slate-400">{{ $p->nip }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    {{ date('d/m/Y', strtotime($p->tgl)) }}
                                    <div class="text-xs text-slate-400">{{ date('l', strtotime($p->tgl)) }}</div>
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap tabular-nums">
                                    {{ substr((string) $p->jam_mulai, 0, 5) }}–{{ substr((string) $p->jam_selesai, 0, 5) }}
                                </td>
                                <td class="px-3 py-2.5 text-center font-bold tabular-nums">{{ $fmtJam($p->total_jam) }} jam</td>
                                <td class="px-3 py-2.5 text-center font-bold text-blue-600 tabular-nums">+{{ number_format($poinEst) }}</td>
                                <td class="max-w-[220px] px-4 py-2.5">
                                    <span class="text-xs text-slate-500" title="{{ $p->keterangan ?? '-' }}">
                                        {{ $p->keterangan ? mb_strimwidth($p->keterangan, 0, 80, '…') : '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-xs whitespace-nowrap text-slate-400" title="{{ $p->created_at ?? '' }}">
                                    {{ ! empty($p->created_at) ? date('d/m/Y H:i', strtotime($p->created_at)) : '-' }}
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" title="Setujui (+{{ $poinEst }} poin)"
                                            :disabled="busyId === {{ (int) $p->id }}"
                                            @click="approve({{ (int) $p->id }}, {{ (int) $poinEst }}, {{ Js::from($p->nama_guru ?? 'guru') }})"
                                            class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1.5 text-[11px] font-bold text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                                            <i class="bi" :class="busyId === {{ (int) $p->id }} ? 'bi-arrow-repeat animate-spin' : 'bi-check-lg'"></i>
                                            Setujui
                                        </button>
                                        <button type="button" title="Tolak dengan alasan"
                                            @click="openReject({{ (int) $p->id }}, {{ Js::from($p->nama_guru ?? 'guru') }})"
                                            class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-[11px] font-bold text-rose-600 transition-colors hover:bg-rose-100">
                                            <i class="bi bi-x-lg"></i> Tolak
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>

                    <div class="flex flex-col justify-between gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center">
                        <div class="text-xs font-medium text-slate-500" aria-live="polite"
                            x-text="total === 0 ? 'Tidak ada pengajuan' : 'Menampilkan ' + from + '–' + to + ' dari ' + total + ' pengajuan'">
                        </div>
                        <div class="flex items-center gap-2" x-show="pages > 1" x-cloak>
                            <button type="button" @click="prev()" :disabled="page <= 1" aria-label="Halaman sebelumnya"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <span class="text-xs font-semibold text-slate-500" x-text="'Halaman ' + page + ' / ' + pages"></span>
                            <button type="button" @click="next()" :disabled="page >= pages" aria-label="Halaman berikutnya"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                @endif
            </x-ui.card>
        </div>

        {{-- Rekap bulanan --}}
        <div x-data="dataTable({ perPage: 10, sort: 3, dir: 'desc' })">
            <x-ui.card title="Rekap Lembur — {{ $labelPeriode }}" icon="bi-bar-chart" tone="emerald" bodyClass="p-0">
                <x-slot:actions>
                    <form method="get" class="flex flex-wrap items-center gap-2">
                        <label for="lembur-bulan" class="sr-only">Bulan</label>
                        <select id="lembur-bulan" name="bulan" @change="$el.form.submit()"
                            class="rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-semibold text-slate-700 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                            @foreach ($namaBulan as $num => $nm)
                                <option value="{{ str_pad($num, 2, '0', STR_PAD_LEFT) }}" @selected((int) $bulan === $num)>{{ $nm }}</option>
                            @endforeach
                        </select>
                        <label for="lembur-tahun" class="sr-only">Tahun</label>
                        <select id="lembur-tahun" name="tahun" @change="$el.form.submit()"
                            class="rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-semibold text-slate-700 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                            @for ($t = $curYear + 1; $t >= attendance_tahun_tertua(); $t--)
                                <option value="{{ $t }}" @selected((int) $tahun === $t)>{{ $t }}</option>
                            @endfor
                        </select>
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                            <i class="bi bi-search"></i> Tampilkan
                        </button>
                    </form>
                </x-slot:actions>

                @if ($rekap)
                    <x-ui.table>
                        <x-slot:head>
                            <th class="w-12 px-3 py-3">#</th>
                            <th class="px-4 py-3">Guru</th>
                            <th class="px-3 py-3 text-center">Pengajuan</th>
                            <th class="px-3 py-3 text-center">Jam Diajukan</th>
                            <th class="px-3 py-3 text-center text-emerald-600">Disetujui</th>
                            <th class="px-3 py-3 text-center text-amber-500">Menunggu</th>
                            <th class="px-3 py-3 text-center text-rose-400">Ditolak</th>
                            <th class="px-3 py-3 text-center text-blue-600">Poin</th>
                        </x-slot:head>

                        @foreach ($rekap as $r)
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-3 py-2.5">
                                    <span data-rownum class="text-xs font-bold text-slate-400">{{ $loop->iteration }}</span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="font-bold text-slate-800">{{ $r->nama_guru ?? 'User #' . $r->user_id }}</div>
                                    @if (! empty($r->nip))
                                        <div class="font-mono text-[11px] text-slate-400">{{ $r->nip }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-center tabular-nums">{{ (int) $r->total_lembur }}×</td>
                                <td class="px-3 py-2.5 text-center font-bold tabular-nums">{{ $fmtJam($r->total_jam) }} jam</td>
                                <td class="px-3 py-2.5 text-center font-bold text-emerald-600 tabular-nums">
                                    {{ (int) ($r->lembur_disetujui ?? 0) }}× &middot; {{ $fmtJam($r->jam_disetujui ?? 0) }} jam
                                </td>
                                <td class="px-3 py-2.5 text-center text-amber-500 tabular-nums">
                                    {{ $fmtJam($r->jam_pending ?? 0) }} jam ({{ (int) ($r->lembur_pending ?? 0) }}×)
                                </td>
                                <td class="px-3 py-2.5 text-center text-rose-400 tabular-nums">{{ (int) ($r->lembur_ditolak ?? 0) }}×</td>
                                <td class="px-3 py-2.5 text-center font-bold text-blue-600 tabular-nums">
                                    +{{ number_format((int) round((float) ($r->jam_disetujui ?? 0) * (int) $pointPerJam)) }}
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>

                    <div class="flex flex-col justify-between gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center">
                        <div class="text-xs font-medium text-slate-500" aria-live="polite"
                            x-text="'Menampilkan ' + from + '–' + to + ' dari ' + total + ' guru'"></div>
                        <div class="flex items-center gap-2" x-show="pages > 1" x-cloak>
                            <button type="button" @click="prev()" :disabled="page <= 1" aria-label="Halaman sebelumnya"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <span class="text-xs font-semibold text-slate-500" x-text="'Halaman ' + page + ' / ' + pages"></span>
                            <button type="button" @click="next()" :disabled="page >= pages" aria-label="Halaman berikutnya"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                @else
                    <div class="p-5">
                        <x-ui.empty icon="bi-inbox" title="Belum ada data lembur"
                            :description="'Tidak ada pengajuan pada ' . $labelPeriode . '.'" />
                    </div>
                @endif
            </x-ui.card>
        </div>

        {{-- Riwayat --}}
        <div x-data="dataTable({ perPage: 15, filters: { status: '' } })">
            <x-ui.card title="Riwayat Pengajuan — {{ $labelPeriode }}" icon="bi-clock-history" tone="slate" bodyClass="p-0">
                @if (! empty($riwayat))
                    <x-slot:actions>
                        <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="Filter status riwayat">
                            <button type="button" @click="filters.status = ''"
                                :class="filters.status === '' ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50'"
                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[11px] font-bold transition-colors">
                                Semua
                            </button>
                            <button type="button" @click="filters.status = 'PENDING'"
                                :class="filters.status === 'PENDING' ? 'border-amber-500 bg-amber-500 text-white' : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50'"
                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[11px] font-bold transition-colors">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span> Pending
                            </button>
                            <button type="button" @click="filters.status = 'APPROVED'"
                                :class="filters.status === 'APPROVED' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50'"
                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[11px] font-bold transition-colors">
                                <span class="h-2 w-2 rounded-full bg-emerald-600"></span> Disetujui
                            </button>
                            <button type="button" @click="filters.status = 'REJECTED'"
                                :class="filters.status === 'REJECTED' ? 'border-rose-600 bg-rose-600 text-white' : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-50'"
                                class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[11px] font-bold transition-colors">
                                <span class="h-2 w-2 rounded-full bg-rose-600"></span> Ditolak
                            </button>
                        </div>
                    </x-slot:actions>
                @endif

                @if (empty($riwayat))
                    <div class="p-5">
                        <x-ui.empty icon="bi-inbox" title="Belum ada riwayat"
                            :description="'Belum ada pengajuan lembur pada ' . $labelPeriode . '.'" />
                    </div>
                @else
                    <x-ui.table class="text-[13px]">
                        <x-slot:head>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Guru</th>
                            <th class="px-4 py-3">Jam</th>
                            <th class="px-3 py-3 text-center">Total</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-4 py-3">Catatan Admin</th>
                        </x-slot:head>

                        @foreach ($riwayat as $h)
                            <tr class="transition-colors hover:bg-slate-50/60" data-status="{{ $h->status }}">
                                <td class="px-4 py-2.5 whitespace-nowrap">{{ date('d/m/Y', strtotime($h->tgl)) }}</td>
                                <td class="px-4 py-2.5 font-bold text-slate-800">{{ $h->nama_guru ?? 'Guru #' . $h->user_id }}</td>
                                <td class="px-4 py-2.5 whitespace-nowrap tabular-nums">
                                    {{ substr((string) $h->jam_mulai, 0, 5) }}–{{ substr((string) $h->jam_selesai, 0, 5) }}
                                </td>
                                <td class="px-3 py-2.5 text-center font-bold tabular-nums">{{ $fmtJam($h->total_jam) }} jam</td>
                                <td class="px-3 py-2.5 text-center">
                                    <span @class([
                                        'inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold',
                                        'border-emerald-200 bg-emerald-50 text-emerald-700' => $h->status === 'APPROVED',
                                        'border-rose-200 bg-rose-50 text-rose-700' => $h->status === 'REJECTED',
                                        'border-amber-200 bg-amber-50 text-amber-700' => $h->status !== 'APPROVED' && $h->status !== 'REJECTED',
                                    ])>{{ $h->status }}</span>
                                </td>
                                <td class="max-w-[260px] px-4 py-2.5 text-xs text-slate-500">
                                    @if ($h->status === 'REJECTED')
                                        {{ ! empty($h->catatan_admin) ? $h->catatan_admin : 'Ditolak (tanpa catatan)' }}
                                    @else
                                        <span class="text-slate-300">–</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>

                    <div class="flex flex-col justify-between gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center">
                        <div class="text-xs font-medium text-slate-500" aria-live="polite"
                            x-text="total === 0 ? 'Tidak ada riwayat dengan status ini' : 'Menampilkan ' + from + '–' + to + ' dari ' + total + ' riwayat'">
                        </div>
                        <div class="flex items-center gap-2" x-show="pages > 1" x-cloak>
                            <button type="button" @click="prev()" :disabled="page <= 1" aria-label="Halaman sebelumnya"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <span class="text-xs font-semibold text-slate-500" x-text="'Halaman ' + page + ' / ' + pages"></span>
                            <button type="button" @click="next()" :disabled="page >= pages" aria-label="Halaman berikutnya"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>

    {{-- Modal tolak --}}
    <x-ui.modal id="rejectModal" title="Tolak Pengajuan Lembur" icon="bi-x-circle" tone="rose" size="max-w-lg">
        <form @submit.prevent="saveReject()" novalidate>
            <div class="space-y-3 p-5">
                <p class="text-sm text-slate-500">
                    Pengajuan milik <strong class="text-slate-800" x-text="rejectNama || '–'"></strong>
                    akan ditolak. Alasan tercatat dan dapat dilihat guru.
                </p>

                <div class="flex items-center justify-between">
                    <label for="reject_alasan" class="text-xs font-bold text-slate-600">
                        Alasan penolakan <span class="text-rose-500">*</span>
                    </label>
                    <span class="text-[11px] text-slate-400" x-text="alasan.length + '/500'">0/500</span>
                </div>

                <input type="text" id="reject_alasan" x-ref="alasan" x-model="alasan" minlength="5" maxlength="500" required
                    placeholder="Contoh: Kebutuhan dinas tidak mendesak / bukti kurang"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-hidden transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">

                <p x-show="alasan.length > 0 && alasan.trim().length < 5" x-cloak class="text-[11px] font-semibold text-rose-600">
                    Alasan minimal 5 karakter.
                </p>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
                <button type="button" @click="$store.modals.close('#rejectModal')"
                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-100">
                    Batal
                </button>
                <button type="submit" :disabled="rejectBusy"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <i class="bi" :class="rejectBusy ? 'bi-arrow-repeat animate-spin' : 'bi-x-circle'"></i>
                    <span x-text="rejectBusy ? 'Memproses...' : 'Konfirmasi Tolak'">Konfirmasi Tolak</span>
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('lemburPage', () => ({
            csrf: '',
            urls: {},
            busyId: null,
            rejectBusy: false,
            rejectId: null,
            rejectNama: '',
            alasan: '',

            init() {
                const config = JSON.parse(this.$el.dataset.config || '{}');
                this.csrf = config.csrf || '';
                this.urls = config.urls || {};
            },

            reload() {
                window.location.reload();
            },

            async request(url, payload) {
                const body = new FormData();
                body.append('_token', this.csrf);
                Object.entries(payload).forEach(([key, value]) => body.append(key, value ?? ''));

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body,
                });

                let data = null;
                try {
                    data = await response.json();
                } catch (error) {
                    data = null;
                }

                if (!data || data.status !== true) {
                    throw new Error((data && data.message) || 'Terjadi kesalahan sistem');
                }

                return data;
            },

            async approve(id, poin, nama) {
                if (this.busyId !== null) {
                    return;
                }

                const confirm = await Swal.fire({
                    title: 'Setujui Lembur?',
                    html: `Pengajuan milik <strong>${this.escape(nama)}</strong> akan disetujui.<br>Poin <strong>+${poin}</strong> masuk ke saldo guru.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#16a34a',
                    confirmButtonText: 'Ya, Setujui',
                    cancelButtonText: 'Batal',
                });

                if (!confirm.isConfirmed) {
                    return;
                }

                this.busyId = id;

                try {
                    const res = await this.request(`${this.urls.approve}/${id}`, {});

                    await Swal.fire({
                        icon: 'success',
                        title: 'Disetujui',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false,
                    });

                    window.location.reload();
                } catch (error) {
                    Swal.fire('Gagal', error.message, 'error');
                } finally {
                    this.busyId = null;
                }
            },

            openReject(id, nama) {
                this.rejectId = id;
                this.rejectNama = nama || '';
                this.alasan = '';
                this.$store.modals.open('#rejectModal');
                this.$nextTick(() => this.$refs.alasan?.focus());
            },

            async saveReject() {
                const alasan = this.alasan.trim();

                if (alasan.length < 5 || alasan.length > 500) {
                    Swal.fire('Perhatian', 'Alasan penolakan wajib diisi (5–500 karakter).', 'warning');
                    return;
                }

                if (this.rejectBusy) {
                    return;
                }

                this.rejectBusy = true;

                try {
                    const res = await this.request(`${this.urls.reject}/${this.rejectId}`, { alasan });

                    this.$store.modals.close('#rejectModal');

                    await Swal.fire({
                        icon: 'success',
                        title: 'Ditolak',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false,
                    });

                    window.location.reload();
                } catch (error) {
                    Swal.fire('Gagal', error.message, 'error');
                } finally {
                    this.rejectBusy = false;
                }
            },

            escape(value) {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            },
        }));
    });
</script>
@endsection
