@extends('layouts.main')

@section('content')

<?php
$totalPoin = 0;
$totalDendaHarian = 0;
foreach ($absensi as $a) {
    if (isset($a->points)) {
        $totalPoin += (int) $a->points;
    }
    if (isset($a->denda) && (float) $a->denda > 0) {
        $totalDendaHarian += (float) $a->denda;
    }
}
// $totalDendaHarian sudah mencakup denda ALPHA yang tercatat, jadi hanya
// tambahkan estimasi tanggal bolong (anti double-count).
$alphaMissingCount = (int) ($alpha_missing_count ?? 0);
$alphaMissingDenda = (float) ($alpha_missing_denda ?? 0);
$totalPoin += ($alphaMissingCount * -abs((int) ($attendance_setting->point_alpa ?? 5)));
$totalDenda = $totalDendaHarian + $alphaMissingDenda;

$kartuStatistik = [
    ['label' => 'Hadir', 'nilai' => $statistik['hadir'] ?? 0, 'icon' => 'bi-check-circle-fill', 'bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'teks' => 'text-emerald-600'],
    ['label' => 'Izin / Cuti', 'nilai' => $statistik['izin'] ?? 0, 'icon' => 'bi-file-earmark-text-fill', 'bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'teks' => 'text-amber-600'],
    ['label' => 'Sakit', 'nilai' => $statistik['sakit'] ?? 0, 'icon' => 'bi-hospital-fill', 'bg' => 'bg-sky-50', 'border' => 'border-sky-200', 'teks' => 'text-sky-600'],
    ['label' => 'Alpha', 'nilai' => $statistik['alpha'] ?? 0, 'icon' => 'bi-x-circle-fill', 'bg' => 'bg-rose-50', 'border' => 'border-rose-200', 'teks' => 'text-rose-600'],
];

$jamMasuk  = substr($attendance_setting->jam_masuk ?? '07:30:00', 0, 5);
$jamPulang = substr($attendance_setting->jam_pulang ?? '15:00:00', 0, 5);
$toleransi = $attendance_setting->grace_period ?? '15';

$d1 = (int) ($attendance_setting->denda_tier_1 ?? 100);
$d2 = (int) ($attendance_setting->denda_tier_2 ?? 200);
$d3 = (int) ($attendance_setting->denda_tier_3 ?? 500);
$p1 = (int) ($attendance_setting->point_tier_1 ?? 1);
$p2 = (int) ($attendance_setting->point_tier_2 ?? 2);
$p3 = (int) ($attendance_setting->point_tier_3 ?? 5);
$adaTier = ($d1 > 0 || $d2 > 0 || $d3 > 0);
?>

<div class="px-4 pt-4 md:px-6 md:pt-6">
    <x-ui.page-header
        :title="$judul"
        icon="bi-calendar-check-fill"
        tone="blue"
        :subtitle="$subjudul"
        :crumbs="['Absensi']">
        <x-slot:actions>
            <a href="{{ url('guru/absensi/export?bulan=' . $bulan . '&tahun=' . $tahun . '&format=excel') }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white no-underline shadow-xs shadow-emerald-500/20 transition-colors hover:bg-emerald-700">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i> Export Excel
            </a>
        </x-slot:actions>
    </x-ui.page-header>
</div>

<div class="mt-4 space-y-5 px-4 pb-6 md:px-6">

    @if (! $guru)
        <div class="flex items-start gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <i class="bi bi-exclamation-triangle-fill mt-0.5 text-amber-500"></i>
            <span>Data guru tidak ditemukan. Silakan hubungi administrator.</span>
        </div>
    @else

        {{-- Filter periode --}}
        <x-ui.card bodyClass="p-0">
            <x-slot:toolbar>
                <form method="get" action="{{ url('guru/absensi') }}" class="flex flex-wrap items-end gap-3">
                    <div class="mr-1 flex items-center gap-2">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50">
                            <i class="bi bi-funnel text-sm text-blue-600"></i>
                        </span>
                        <h2 class="text-sm font-bold text-slate-700">Filter Periode</h2>
                    </div>

                    <div>
                        <label for="bulan" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Bulan</label>
                        <select name="bulan" id="bulan"
                            class="h-9 min-w-[150px] rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                            @foreach ($bulan_ini as $key => $nama)
                                <option value="{{ $key }}" @selected((int) $bulan === (int) $key)>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tahun" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Tahun</label>
                        <select name="tahun" id="tahun"
                            class="h-9 w-[95px] rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                            @for ($y = date('Y'); $y >= attendance_tahun_tertua(); $y--)
                                <option value="{{ $y }}" @selected((int) $tahun === $y)>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <button type="submit"
                        class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 text-xs font-bold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                        <i class="bi bi-search"></i> Tampilkan
                    </button>
                </form>
            </x-slot:toolbar>
        </x-ui.card>

        {{-- Kebijakan absensi --}}
        <div x-data="{ buka: false }" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <button type="button" @click="buka = !buka" :aria-expanded="buka" aria-controls="kebijakanAbsensi"
                class="flex w-full items-center justify-between gap-2 px-5 py-4 text-left text-sm font-bold text-slate-800 transition-colors hover:bg-slate-50">
                <span class="flex items-center gap-2">
                    <i class="bi bi-info-circle-fill text-blue-600"></i>
                    Kebijakan Absensi &amp; Sanksi
                </span>
                <i class="bi text-xs text-slate-400" :class="buka ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </button>

            <div id="kebijakanAbsensi" x-show="buka" x-cloak class="border-t border-slate-100 bg-slate-50/40 p-4 md:p-5">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <div class="flex items-center gap-2 border-b border-slate-100 bg-blue-50 px-4 py-2.5 text-xs font-bold text-blue-700">
                            <i class="bi bi-clock"></i> Aturan Jam Kerja
                        </div>
                        <dl class="divide-y divide-slate-100 text-xs">
                            <div class="flex items-center justify-between px-4 py-2.5">
                                <dt class="text-slate-600">Jam Masuk</dt>
                                <dd class="rounded-full bg-blue-50 px-2.5 py-0.5 font-bold text-blue-700">{{ $jamMasuk }}</dd>
                            </div>
                            <div class="flex items-center justify-between px-4 py-2.5">
                                <dt class="text-slate-600">Jam Pulang</dt>
                                <dd class="rounded-full bg-emerald-50 px-2.5 py-0.5 font-bold text-emerald-700">{{ $jamPulang }}</dd>
                            </div>
                            <div class="flex items-center justify-between px-4 py-2.5">
                                <dt class="text-slate-600">Toleransi Keterlambatan</dt>
                                <dd class="rounded-full bg-slate-100 px-2.5 py-0.5 font-bold text-slate-600">{{ $toleransi }} menit</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <div class="flex items-center gap-2 border-b border-slate-100 bg-rose-50 px-4 py-2.5 text-xs font-bold text-rose-700">
                            <i class="bi bi-exclamation-triangle"></i> Poin &amp; Denda Pelanggaran
                        </div>
                        <table class="w-full text-xs">
                            <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                <tr class="border-b border-slate-200">
                                    <th class="px-4 py-2 text-left">Jenis</th>
                                    <th class="px-2 py-2 text-center">Poin</th>
                                    <th class="px-4 py-2 text-right">Denda (Rp)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @if ($adaTier)
                                    <tr>
                                        <td class="px-4 py-2 text-slate-600">Telat 1&ndash;30 menit</td>
                                        <td class="px-2 py-2 text-center font-bold text-rose-600">-{{ $p1 }}/mnt</td>
                                        <td class="px-4 py-2 text-right text-slate-700">{{ number_format($d1, 0, ',', '.') }}/mnt</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-slate-600">Telat 31&ndash;60 menit</td>
                                        <td class="px-2 py-2 text-center font-bold text-rose-600">-{{ $p2 }}/mnt</td>
                                        <td class="px-4 py-2 text-right text-slate-700">{{ number_format($d2, 0, ',', '.') }}/mnt</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 text-slate-600">Telat &gt; 60 menit</td>
                                        <td class="px-2 py-2 text-center font-bold text-rose-600">-{{ $p3 }}/mnt</td>
                                        <td class="px-4 py-2 text-right text-slate-700">{{ number_format($d3, 0, ',', '.') }}/mnt</td>
                                    </tr>
                                @else
                                    <tr>
                                        <td class="px-4 py-2 text-slate-600">Telat (per menit)</td>
                                        <td class="px-2 py-2 text-center font-bold text-rose-600">-{{ $attendance_setting->point_telat ?? 1 }}</td>
                                        <td class="px-4 py-2 text-right text-slate-700">0</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="px-4 py-2 text-slate-600">Alpha (per hari)</td>
                                    <td class="px-2 py-2 text-center font-bold text-rose-600">-{{ $attendance_setting->point_alpa ?? 5 }}</td>
                                    <td class="px-4 py-2 text-right text-slate-400">-</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 text-slate-600">Sakit</td>
                                    <td class="px-2 py-2 text-center font-bold text-amber-600">{{ $attendance_setting->point_sakit ?? 0 }}</td>
                                    <td class="px-4 py-2 text-right text-slate-700">0</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 text-slate-600">Izin</td>
                                    <td class="px-2 py-2 text-center font-bold text-amber-600">{{ $attendance_setting->point_izin ?? 0 }}</td>
                                    <td class="px-4 py-2 text-right text-slate-700">0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white md:col-span-2">
                        <div class="flex items-center gap-2 border-b border-slate-100 bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-700">
                            <i class="bi bi-check-circle"></i> Prosedur Izin &amp; Sakit
                        </div>
                        <div class="space-y-2 px-4 py-3 text-xs leading-relaxed text-slate-600">
                            <p class="flex items-start gap-2">
                                <i class="bi bi-info-circle-fill mt-0.5 text-blue-500"></i>
                                <span><strong class="text-slate-800">Otomatisasi Status:</strong> pengajuan izin atau sakit yang telah <u>disetujui oleh admin</u> akan otomatis tercatat dalam absensi harian. Anda tidak perlu absen manual untuk hari yang telah disetujui.</span>
                            </p>
                            <p class="flex items-start gap-2">
                                <i class="bi bi-exclamation-circle-fill mt-0.5 text-amber-500"></i>
                                <span><strong class="text-slate-800">Bukti Pendukung:</strong> pengajuan sakit wajib melampirkan surat dokter. Izin pribadi diajukan minimal H-1, kecuali kondisi darurat.</span>
                            </p>
                        </div>
                    </div>
                </div>

                <p class="mt-3 flex items-start gap-1.5 border-t border-slate-200 pt-2 text-[11px] italic text-slate-500">
                    <i class="bi bi-info-circle mt-0.5"></i>
                    <span>Catatan: denda keterlambatan &amp; pulang awal dihitung berjenjang (tier) per menit. Alpha dikenakan denda flat per hari.</span>
                </p>
            </div>
        </div>

        {{-- Statistik --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($kartuStatistik as $kartu)
                <div class="flex items-center gap-3 rounded-2xl border bg-white px-4 py-3.5 shadow-xs {{ $kartu['border'] }}">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $kartu['bg'] }} {{ $kartu['teks'] }}">
                        <i class="bi {{ $kartu['icon'] }}"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="text-xl font-extrabold leading-none text-slate-800">{{ $kartu['nilai'] }}</div>
                        <div class="mt-1 truncate text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $kartu['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Ringkasan denda --}}
        <div class="flex flex-col gap-3 rounded-2xl border border-blue-200 border-l-4 border-l-blue-500 bg-blue-50/60 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="flex items-center gap-2 text-xs font-bold text-blue-700">
                    <i class="bi bi-exclamation-octagon-fill"></i> Ringkasan Denda Bulan Ini
                </h2>
                <p class="mt-0.5 text-[11px] text-slate-500">Denda harian (telat/pulang awal) + denda alpha.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-center">
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Poin</span>
                    <span class="text-base font-extrabold text-slate-800">{{ $totalPoin }}</span>
                </div>
                <span class="h-9 w-px bg-blue-200" aria-hidden="true"></span>
                <div class="text-right">
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Denda</span>
                    <span class="text-lg font-extrabold text-blue-700">Rp {{ number_format($totalDenda, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Riwayat absensi --}}
        <x-ui.card title="Riwayat Absensi — {{ $bulan_ini[$bulan] ?? '' }} {{ $tahun }}" icon="bi-table" tone="blue" bodyClass="p-0">
            <x-slot:actions>
                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-bold text-slate-500">{{ count($absensi) }} Data</span>
            </x-slot:actions>

            @if (count($absensi) === 0)
                <div class="p-5">
                    <x-ui.empty icon="bi-calendar-x" title="Belum ada data absensi"
                        description="Belum ada absensi tercatat untuk periode ini." />
                </div>
            @else
                <div x-data="dataTable({ perPage: 10, sort: 1, dir: 'desc' })">
                    <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-3 sm:flex-row sm:items-center">
                        <div class="relative w-full sm:max-w-xs">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-slate-400">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="search" x-model="query" placeholder="Cari status atau tanggal..." aria-label="Cari riwayat absensi"
                                class="w-full rounded-xl border border-slate-200 bg-white py-2 pr-8 pl-8 text-xs text-slate-700 outline-hidden transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                            <button type="button" x-show="query !== ''" x-cloak @click="query = ''" aria-label="Bersihkan pencarian"
                                class="absolute inset-y-0 right-1 my-auto flex h-6 w-6 items-center justify-center rounded-lg text-xs text-slate-400 hover:bg-slate-100 hover:text-slate-600">&times;</button>
                        </div>
                        <span class="text-[11px] font-semibold text-slate-400 sm:ml-auto" x-text="total + ' baris'"></span>
                    </div>

                    <x-ui.table id="tabelAbsensi" class="min-w-[860px]">
                        <x-slot:head>
                            <th class="w-14 px-3 py-3 text-center">No</th>
                            <th class="w-32 cursor-pointer select-none px-4 py-3" @click="sortBy(1)" scope="col">
                                Tanggal <span class="text-blue-600" x-text="indicator(1)"></span>
                            </th>
                            <th class="w-24 px-3 py-3 text-center">Masuk</th>
                            <th class="w-24 px-3 py-3 text-center">Pulang</th>
                            <th class="px-4 py-3">Status Masuk</th>
                            <th class="w-28 px-3 py-3 text-center">Telat</th>
                            <th class="w-20 px-3 py-3 text-center">Poin</th>
                            <th class="w-24 px-3 py-3 text-center">Aksi</th>
                        </x-slot:head>

                        @foreach ($absensi as $a)
                            @php
                                $tgl = $a->tgl ?? '';
                                $status = strtolower($a->status_masuk ?? 'unknown');
                                $badgeStatus = 'border-slate-200 bg-slate-50 text-slate-600';

                                if (str_contains($status, 'hadir') || str_contains($status, 'tepat')) {
                                    $badgeStatus = 'border-emerald-200 bg-emerald-50 text-emerald-700';
                                } elseif (str_contains($status, 'telat')) {
                                    $badgeStatus = 'border-amber-200 bg-amber-50 text-amber-700';
                                } elseif (str_contains($status, 'izin')) {
                                    $badgeStatus = 'border-sky-200 bg-sky-50 text-sky-700';
                                }
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-3 py-2.5 text-center text-xs font-bold text-slate-400" data-rownum>{{ $loop->iteration }}</td>
                                <td class="px-4 py-2.5 font-semibold text-slate-800" data-order="{{ $tgl }}">
                                    {{ $tgl !== '' ? date('d/m/Y', strtotime($tgl)) : '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    @if (isset($a->jam_masuk))
                                        <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 font-mono text-[11px] font-bold text-sky-700">{{ $a->jam_masuk }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    @if (isset($a->jam_pulang))
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 font-mono text-[11px] font-bold text-slate-600">{{ $a->jam_pulang }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $badgeStatus }}">
                                        {{ ucfirst($a->status_masuk ?? '-') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    @if (isset($a->telat_menit) && $a->telat_menit > 0)
                                        <span class="text-xs font-bold text-rose-600">{{ $a->telat_menit }} menit</span>
                                    @else
                                        <span class="text-[11px] text-slate-400">Tepat Waktu</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    @if (isset($a->points) && $a->points > 0)
                                        <span class="inline-flex min-w-[28px] items-center justify-center rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-bold text-rose-700">{{ $a->points }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <a href="{{ url('guru/absensi/detail/' . $tgl) }}" title="Lihat detail absensi"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-sky-100 bg-sky-50 text-sky-600 transition-colors hover:bg-sky-600 hover:text-white">
                                        <i class="bi bi-eye-fill text-xs"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>

                    <div class="flex flex-col justify-between gap-3 border-t border-slate-100 px-5 py-3.5 sm:flex-row sm:items-center">
                        <div class="text-xs font-medium text-slate-500" aria-live="polite"
                            x-text="total === 0 ? 'Tidak ada data' : 'Menampilkan ' + from + '–' + to + ' dari ' + total + ' absensi'"></div>
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
                </div>
            @endif
        </x-ui.card>

        {{-- Riwayat pengajuan izin/cuti --}}
        <x-ui.card title="Riwayat Pengajuan Izin/Cuti" icon="bi-file-earmark-medical" tone="violet" bodyClass="p-0">
            @if (empty($izin_list))
                <div class="px-5 py-6 text-xs italic text-slate-500">Tidak ada riwayat pengajuan izin.</div>
            @else
                <x-ui.table class="min-w-[820px]">
                    <x-slot:head>
                        <th class="w-12 px-3 py-3 text-center">No</th>
                        <th class="w-44 px-4 py-3">Tanggal Pengajuan</th>
                        <th class="w-52 px-4 py-3">Tanggal Izin</th>
                        <th class="w-28 px-3 py-3">Jenis</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="w-32 px-3 py-3">Status</th>
                    </x-slot:head>

                    @foreach ($izin_list as $izin)
                        @php
                            $statusIzin = $izin->status ?? 0;
                            $statusBadge = 'border-slate-200 bg-slate-50 text-slate-600';
                            $statusLabel = 'Pending';

                            if ($statusIzin == 1) {
                                $statusBadge = 'border-emerald-200 bg-emerald-50 text-emerald-700';
                                $statusLabel = 'Disetujui';
                            } elseif ($statusIzin == 2) {
                                $statusBadge = 'border-rose-200 bg-rose-50 text-rose-700';
                                $statusLabel = 'Ditolak';
                            }

                            $jenisIzin = $izin->jenis ?? '';
                            $badgeJenis = $jenisIzin === 'sakit'
                                ? 'border-sky-200 bg-sky-50 text-sky-700'
                                : 'border-amber-200 bg-amber-50 text-amber-700';
                            $tglMulai = $izin->tgl_mulai ?? '';
                            $tglSelesai = $izin->tgl_selesai ?? '';
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/60">
                            <td class="px-3 py-2.5 text-center text-xs font-bold text-slate-400">{{ $loop->iteration }}</td>
                            <td class="px-4 py-2.5 text-xs text-slate-600">
                                {{ ! empty($izin->created_at) ? date('d/m/Y H:i', strtotime($izin->created_at)) : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-xs text-slate-600">
                                {{ $tglMulai !== '' ? date('d/m/Y', strtotime($tglMulai)) : '—' }}
                                @if ($tglMulai !== $tglSelesai && ! empty($tglSelesai))
                                    <span class="block text-[11px] text-slate-400">s/d {{ date('d/m/Y', strtotime($tglSelesai)) }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $badgeJenis }}">
                                    {{ ucfirst($jenisIzin) }}
                                </span>
                                @if ($jenisIzin === 'cuti' && ! empty($izin->kategori_cuti) && \App\Services\CutiKategoriService::valid($izin->kategori_cuti))
                                    <div class="mt-1">
                                        <span class="inline-flex rounded-lg border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold text-slate-600">
                                            {{ \App\Services\CutiKategoriService::label($izin->kategori_cuti) }}
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-xs text-slate-500">{{ $izin->keterangan ?? '-' }}</td>
                            <td class="px-3 py-2.5">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $statusBadge }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.card>

    @endif
</div>
@endsection
