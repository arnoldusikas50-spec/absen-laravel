@extends('layouts.main')

@section('content')
@php
    $bulanPilih = (string) $bulan;
    $queryBase = [
        'bulan' => $bulanPilih,
        'tahun' => $tahun,
    ];
    if (!empty($q)) $queryBase['q'] = $q;
    if (!empty($hanya_belum)) $queryBase['hanya_belum'] = '1';

    $exportUrl = url('admin/jurnal/rekap/export') . '?' . http_build_query($queryBase);
    $printUrl  = url('admin/jurnal/rekap/cetak') . '?' . http_build_query($queryBase);

    $belum = max(0, (int) $total_guru - (int) $guru_mengisi);
    $months = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];

    $persenTotal = (int) $total_scheduled > 0
        ? min(100, (int) round((int) $total_jurnal / (int) $total_scheduled * 100))
        : null;
@endphp

<div class="space-y-4 px-4 pb-8 pt-4 md:px-6 md:pt-6">

    <x-ui.page-header
        title="Rekapitulasi Keterisian Jurnal Guru"
        icon="bi-file-earmark-check"
        tone="blue"
        subtitle="Akumulasi jurnal, hari aktif, dan pengisian terakhir setiap guru per periode"
        :crumbs="['Jurnal', 'Rekap Bulanan']">
        <x-slot:actions>
            <a href="{{ $exportUrl }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-700 no-underline transition-colors hover:bg-emerald-100">
                <i class="bi bi-filetype-csv"></i> Ekspor CSV
            </a>
            <a href="{{ $printUrl }}" target="_blank"
                class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-xs font-bold text-indigo-700 no-underline transition-colors hover:bg-indigo-100">
                <i class="bi bi-printer"></i> Cetak
            </a>
            <a href="{{ url('admin/jurnal/rekap-kelas?bulan=' . (int) $bulan . '&tahun=' . (int) $tahun) }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-100">
                <i class="bi bi-journal-text"></i> Rekap per Kelas
            </a>
            <a href="{{ url('admin/jurnal') }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white no-underline shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                <i class="bi bi-arrow-left"></i> Monitoring Harian
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- Ringkasan -->
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.card bodyClass="p-4">
            <p class="m-0 text-[10px] font-bold uppercase tracking-wide text-slate-400">Total Guru</p>
            <p class="m-0 text-2xl font-extrabold leading-tight text-slate-900">{{ (int) $total_guru }}</p>
        </x-ui.card>

        <x-ui.card bodyClass="p-4" style="border-color: rgb(167 243 208 / 0.7)">
            <p class="m-0 text-[10px] font-bold uppercase tracking-wide text-emerald-500">Sudah Mengisi</p>
            <p class="m-0 text-2xl font-extrabold leading-tight text-emerald-700">{{ (int) $guru_mengisi }}</p>
        </x-ui.card>

        <x-ui.card bodyClass="p-4" style="{{ $belum > 0 ? 'border-color: rgb(253 230 138 / 0.8)' : '' }}">
            <p class="m-0 text-[10px] font-bold uppercase tracking-wide text-slate-400">Belum Mengisi</p>
            <p class="m-0 text-2xl font-extrabold leading-tight {{ $belum > 0 ? 'text-amber-700' : 'text-slate-400' }}">{{ $belum }}</p>
        </x-ui.card>

        <x-ui.card bodyClass="p-4">
            <p class="m-0 text-[10px] font-bold uppercase tracking-wide text-slate-400">Total Jurnal</p>
            <p class="m-0 text-2xl font-extrabold leading-tight text-indigo-700">{{ (int) $total_jurnal }}</p>
        </x-ui.card>
    </div>

    <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-indigo-100 bg-indigo-50/60 px-4 py-3 text-[11px] font-semibold text-indigo-900">
        <span class="inline-flex items-center gap-1.5"><i class="bi bi-calendar2-week"></i> Sesi terjadwal: <b>{{ (int) $total_scheduled }}</b></span>
        <span class="inline-flex items-center gap-1.5"><i class="bi bi-journal-check"></i> Sudah dijurnal: <b>{{ (int) $total_jurnal }}</b></span>
        <span class="inline-flex items-center gap-1.5"><i class="bi bi-graph-up-arrow"></i> Kepatuhan: <b>{{ $persenTotal === null ? '—' : $persenTotal . '%' }}</b></span>
        <span class="font-medium text-indigo-700/70">Sesi terjadwal dihitung hingga hari ini.</span>
    </div>

    @if (!empty($ada_validasi))
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-[11px] font-semibold text-slate-700">
            <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-wide text-slate-400">Status validasi</span>
            <span class="inline-flex items-center gap-1.5"><i class="bi bi-shield-check text-emerald-600"></i> Disetujui: <b>{{ (int) ($total_disetujui ?? 0) }}</b></span>
            <span class="inline-flex items-center gap-1.5"><i class="bi bi-hourglass-split text-indigo-600"></i> Menunggu: <b>{{ (int) ($total_menunggu ?? 0) }}</b></span>
            <span class="inline-flex items-center gap-1.5"><i class="bi bi-x-circle text-rose-600"></i> Ditolak: <b>{{ (int) ($total_ditolak ?? 0) }}</b></span>
            @if (!empty($mode_validasi_ketat))
                <span class="font-bold text-indigo-700">Mode ketat aktif: hanya jurnal Disetujui yang melepaskan guru dari absen pulang.</span>
            @else
                <span class="font-medium text-slate-400">Mode ketat mati — jurnal terisi sudah cukup untuk absen pulang.</span>
            @endif
        </div>
    @endif

    <!-- Filter Bulan & Tahun -->
    <x-ui.card title="Filter Periode" icon="bi-funnel" subtitle="Pilih bulan, tahun, dan kata kunci guru untuk menyaring rekap.">
        <form method="GET" action="{{ url('admin/jurnal/rekap') }}" class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase text-slate-600">Bulan</label>
                <select name="bulan" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    @foreach ($months as $k => $m)
                        <option value="{{ $k }}" {{ $bulanPilih === (string) $k ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase text-slate-600">Tahun</label>
                <select name="tahun" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    @for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                        <option value="{{ $y }}" {{ (int) $tahun === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase text-slate-600">Cari Guru</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="Nama atau NIP..."
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <label class="inline-flex cursor-pointer select-none items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
                <input type="checkbox" name="hanya_belum" value="1" {{ !empty($hanya_belum) ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                Hanya belum mengisi
            </label>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                    <i class="bi bi-funnel-fill me-1"></i> Terapkan
                </button>
                <a href="{{ url('admin/jurnal/rekap') }}" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600 no-underline transition-colors hover:bg-slate-200" title="Reset filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </x-ui.card>

    <!-- Table -->
    <x-ui.card title="Daftar Guru" icon="bi-people" tone="violet" bodyClass="p-0">
        <x-slot:meta>
            <span class="text-xs font-semibold text-slate-500">
                {{ $months[$bulanPilih] ?? $bulanPilih }} {{ (int) $tahun }}
                &middot; {{ count($gurus) }} baris ditampilkan
            </span>
        </x-slot:meta>

        <x-ui.table>
            <x-slot:head>
                <th class="px-5 py-3.5 text-center" width="50">No</th>
                <th class="px-5 py-3.5 text-left">Nama Guru</th>
                <th class="px-5 py-3.5 text-left" width="150">NIP</th>
                <th class="px-5 py-3.5 text-center" width="120">Sesi Terjadwal</th>
                <th class="px-5 py-3.5 text-center" width="110">Total Jurnal</th>
                @if (!empty($ada_validasi))
                    <th class="px-5 py-3.5 text-center" width="150">Status Validasi</th>
                @endif
                <th class="px-5 py-3.5 text-center" width="110">Kepatuhan</th>
                <th class="px-5 py-3.5 text-center" width="100">Hari Aktif</th>
                <th class="px-5 py-3.5 text-center" width="140">Terakhir Mengisi</th>
                <th class="px-5 py-3.5 text-center" width="90">Aksi</th>
            </x-slot:head>

            @if (empty($gurus))
                <tr>
                    <td colspan="{{ !empty($ada_validasi) ? 10 : 9 }}" class="px-5 py-10 text-center text-slate-400">
                        {{ !empty($hanya_belum) ? 'Semua guru sudah mengisi jurnal pada periode ini.' : 'Tidak ada data guru yang cocok dengan filter.' }}
                    </td>
                </tr>
            @else
                @php $no = 1; @endphp
                @foreach ($gurus as $g)
                    @php
                        $stat = $stat_map[(int) $g->id_guru] ?? null;
                        $tot = $stat ? (int) $stat->total_jurnal : 0;
                        $hariAktif = $stat ? (int) $stat->hari_aktif : 0;
                        $terjadwal = (int) ($scheduled_map[(int) $g->id_guru] ?? 0);
                        $kepatuhan = $terjadwal > 0 ? min(100, (int) round($tot / $terjadwal * 100)) : null;
                        $kepatuhanClass = $kepatuhan === null
                            ? 'bg-slate-100 text-slate-400'
                            : ($kepatuhan >= 80 ? 'bg-emerald-100 text-emerald-800' : ($kepatuhan >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-700'));
                        $last = $stat->last_tanggal ?? '';
                        $lastLabel = $last !== ''
                            ? ($last === date('Y-m-d') ? 'Hari ini' : date('d/m/Y', strtotime($last)))
                            : '-';
                    @endphp
                    <tr class="transition-colors hover:bg-slate-50/70">
                        <td class="px-5 py-3 text-center text-slate-400">{{ $no++ }}</td>
                        <td class="px-5 py-3">
                            <p class="m-0 font-bold uppercase text-slate-800">{{ $g->nama_guru }}</p>
                        </td>
                        <td class="px-5 py-3 text-slate-500">{{ $g->nip ?: '-' }}</td>
                        <td class="px-5 py-3 text-center font-bold {{ $terjadwal > 0 ? 'text-slate-700' : 'text-slate-300' }}">
                            {{ $terjadwal > 0 ? $terjadwal . ' sesi' : '—' }}
                        </td>
                        <td class="px-5 py-3 text-center">
                            @if ($tot > 0)
                                <span class="inline-block rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-800">
                                    {{ $tot }} Sesi
                                </span>
                            @else
                                <span class="inline-block rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-400">
                                    0
                                </span>
                            @endif
                        </td>
                        @if (!empty($ada_validasi))
                            @php
                                $disetujui = $stat ? (int) ($stat->jumlah_disetujui ?? 0) : 0;
                                $ditolak   = $stat ? (int) ($stat->jumlah_ditolak ?? 0) : 0;
                                $menungguStat = max(0, $tot - $disetujui - $ditolak);
                            @endphp
                            <td class="whitespace-nowrap px-5 py-3 text-center">
                                @if ($tot > 0)
                                    <span class="inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-black text-emerald-800" title="Disetujui">{{ $disetujui }}</span>
                                    <span class="inline-block rounded-full px-2 py-0.5 text-[11px] font-black {{ $menungguStat > 0 ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-400' }}" title="Menunggu validasi">{{ $menungguStat }}</span>
                                    <span class="inline-block rounded-full px-2 py-0.5 text-[11px] font-black {{ $ditolak > 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-400' }}" title="Ditolak, perlu diperbaiki">{{ $ditolak }}</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                        @endif
                        <td class="px-5 py-3 text-center">
                            <span class="inline-block rounded-full px-2.5 py-1 text-xs font-black {{ $kepatuhanClass }}">
                                {{ $kepatuhan === null ? '—' : $kepatuhan . '%' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center font-bold {{ $hariAktif > 0 ? 'text-slate-700' : 'text-slate-300' }}">
                            {{ $hariAktif }} hari
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="text-[11px] font-bold {{ $tot > 0 ? 'text-slate-600' : 'text-slate-300' }}">
                                {{ $lastLabel }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <a href="{{ url('admin/jurnal?id_guru=' . (int) $g->id_guru . '&bulan=' . (int) $bulan . '&tahun=' . (int) $tahun) }}"
                                class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 no-underline hover:text-blue-800">
                                Rincian <i class="bi bi-arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            @endif
        </x-ui.table>
    </x-ui.card>

    <p class="m-0 flex items-center justify-center gap-1 text-center text-[10px] text-slate-400">
        <i class="bi bi-info-circle"></i>
        Rekap dihitung dari jurnal pada tahun pelajaran dan semester aktif. Hari aktif = jumlah tanggal berbeda yang memiliki jurnal. Sesi terjadwal = slot jadwal pada hari Senin–Sabtu dikali jumlah hari tersebut hingga hari ini; kepatuhan = jurnal &divide; sesi terjadwal.
    </p>

</div>
@endsection
