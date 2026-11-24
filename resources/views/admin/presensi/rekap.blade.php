@extends('layouts.main')

@section('styles')
<style>
    @media print {
        @page { size: landscape; margin: 8mm; }
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        /* Sticky hanya berguna saat layar digulir; saat dicetak header bisa
           menimpa baris data, jadi kembali ke posisi statis seperti versi lama. */
        thead th, tbody td { position: static !important; }
        thead th { background: #e2e8f0 !important; }
        thead th, tbody td { border-color: #94a3b8 !important; }
    }
</style>
@endsection

@section('content')
@php
    $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    $bulanInt = (int) $bulan;
    $tahunInt = (int) $tahun;
    $labelPeriode = trim(($namaBulan[$bulanInt] ?? '') . ' ' . $tahunInt);
    $dayLabels = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    $hariLiburMingguan = $hariLiburMingguan ?? [];
    $tanggalHariIni = date('Y-m-d');
    $daysInMonth = (int) date('t', mktime(0, 0, 0, $bulanInt, 1, $tahunInt));

    // ── Kalender bulan: dihitung sekali, dipakai header hari + sel harian ──
    $kalender = [];
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $ts = mktime(0, 0, 0, $bulanInt, $d, $tahunInt);
        $tanggal = sprintf('%04d-%02d-%02d', $tahunInt, $bulanInt, $d);
        $nasional = isset($holidays[$tanggal]);
        $mingguan = attendance_is_weekly_holiday(date('D', $ts), (int) date('N', $ts), $jadwalKerja, $hariLiburMingguan);

        $kalender[] = [
            'tanggal' => $tanggal,
            'label' => $dayLabels[(int) date('w', $ts)],
            'hari' => sprintf('%02d', $d),
            'libur' => $nasional || $mingguan,
            'nasional' => $nasional,
        ];
    }

    // ── Kolom ringkasan (kanan) ───────────────────────────────────────────
    $kolomRingkas = [
        ['label' => 'H', 'tip' => 'Hadir', 'kelas' => 'bg-emerald-50 text-emerald-700'],
        ['label' => 'TL', 'tip' => 'Terlambat', 'kelas' => 'bg-amber-50 text-amber-600'],
        ['label' => 'D', 'tip' => 'Dinas luar', 'kelas' => 'bg-cyan-50 text-cyan-700'],
        ['label' => 'W', 'tip' => 'Work From Home', 'kelas' => 'bg-emerald-50 text-emerald-700'],
        ['label' => 'I/S', 'tip' => 'Izin / Sakit / Cuti', 'kelas' => 'bg-blue-50 text-blue-700'],
        ['label' => 'A', 'tip' => 'Alpha', 'kelas' => 'bg-rose-50 text-rose-700'],
        ['label' => 'Poin', 'tip' => 'Total poin', 'kelas' => 'bg-slate-100 text-slate-700'],
        ['label' => 'Denda', 'tip' => 'Total denda', 'kelas' => 'bg-orange-50 text-orange-700'],
        ['label' => 'Rekomendasi', 'tip' => 'Rekomendasi tindak lanjut', 'kelas' => 'bg-violet-50 text-violet-700'],
        ['label' => 'Status', 'tip' => 'Status pembayaran denda', 'kelas' => 'bg-teal-50 text-teal-700'],
    ];

    // ── Legenda warna sel ─────────────────────────────────────────────────
    $legenda = [
        ['kelas' => 'border border-emerald-200 bg-emerald-50', 'simbol' => '●', 'warna' => 'text-emerald-600', 'label' => 'Hadir tepat waktu'],
        ['kelas' => 'border border-amber-200 bg-amber-50', 'simbol' => '●', 'warna' => 'text-amber-500', 'label' => 'Terlambat'],
        ['kelas' => 'border border-cyan-200 bg-cyan-50', 'simbol' => 'DNS', 'warna' => 'text-cyan-600', 'label' => 'Dinas Luar'],
        ['kelas' => 'border border-emerald-200 bg-emerald-50', 'simbol' => 'WFH', 'warna' => 'text-emerald-600', 'label' => 'WFH'],
        ['kelas' => 'border border-blue-200 bg-blue-50', 'simbol' => 'IZN', 'warna' => 'text-blue-600', 'label' => 'Izin/Sakit/Cuti'],
        ['kelas' => 'border border-rose-200 bg-rose-50', 'simbol' => 'X', 'warna' => 'text-rose-500', 'label' => 'Alpha / Tidak hadir'],
        ['kelas' => 'border border-slate-200 bg-slate-100', 'simbol' => '—', 'warna' => 'text-slate-400', 'label' => 'Libur/Weekend'],
    ];

    // ── Baris rekap per guru (sel harian + ringkasan) ─────────────────────
    $barisRekap = [];
    $nomor = 1;

    foreach ($users as $guru) {
        $cntH = 0;
        $cntTL = 0;
        $cntD = 0;
        $cntW = 0;
        $cntIS = 0;
        $cntA = 0;
        $cntARecorded = 0;
        $sel = [];

        foreach ($kalender as $hari) {
            $tanggal = $hari['tanggal'];

            // Libur nasional / mingguan: tidak ada presensi yang dihitung.
            if ($hari['libur']) {
                $sel[] = [
                    'kelas' => $hari['nasional'] ? 'bg-rose-50/60' : 'bg-slate-100',
                    'jenis' => 'kosong',
                    'teks' => '—',
                    'warna' => 'text-slate-300',
                ];

                continue;
            }

            $rec = $mapByUser[$guru->id][$tanggal] ?? null;

            if ($rec) {
                $status = (string) ($rec->status_masuk ?? '');

                if (in_array($status, ['ONTIME', 'LATE'], true)) {
                    $cntH++;
                    $terlambat = $status === 'LATE';
                    if ($terlambat) {
                        $cntTL++;
                    }

                    $sel[] = [
                        'kelas' => $terlambat ? 'bg-amber-50' : 'bg-emerald-50',
                        'jenis' => 'waktu',
                        'teks' => '',
                        'warna' => '',
                        'masuk' => ! empty($rec->jam_masuk) ? date('H:i', strtotime($rec->jam_masuk)) : '—',
                        'pulang' => ! empty($rec->jam_pulang) ? date('H:i', strtotime($rec->jam_pulang)) : '—',
                        'warnaMasuk' => $terlambat ? 'text-amber-600' : 'text-emerald-600',
                    ];
                } elseif (str_contains($status, 'DINAS')) {
                    $cntD++;
                    $sel[] = ['kelas' => 'bg-cyan-50', 'jenis' => 'teks', 'teks' => 'DNS', 'warna' => 'font-bold text-cyan-700'];
                } elseif ($status === 'WFH') {
                    $cntW++;
                    $sel[] = ['kelas' => 'bg-emerald-50', 'jenis' => 'teks', 'teks' => 'WFH', 'warna' => 'font-bold text-emerald-700'];
                } elseif (in_array($status, ['IJIN', 'IZIN', 'SAKIT', 'CUTI'], true)) {
                    $cntIS++;
                    $label = match ($status) {
                        'SAKIT' => 'SKT',
                        'CUTI' => 'CTI',
                        default => 'IZN',
                    };
                    $sel[] = ['kelas' => 'bg-blue-50', 'jenis' => 'teks', 'teks' => $label, 'warna' => 'font-bold text-blue-700'];
                } elseif ($status === 'PENDING_VERIFICATION') {
                    $sel[] = ['kelas' => '', 'jenis' => 'teks', 'teks' => 'PND', 'warna' => 'text-amber-600'];
                } elseif (in_array($status, ['ALPHA', 'ALPA'], true)) {
                    // Alpha tercatat: nominal & poin sudah masuk lewat SUM di
                    // bawah, jadi tidak ditambahkan ke $cntA (alpha bolong).
                    $cntARecorded++;
                    $sel[] = ['kelas' => 'bg-rose-50', 'jenis' => 'teks', 'teks' => '✕', 'warna' => 'font-extrabold text-rose-700'];
                } else {
                    $sel[] = [
                        'kelas' => '',
                        'jenis' => 'teks',
                        'teks' => mb_strtoupper(substr($status, 0, 3)),
                        'warna' => 'text-slate-400',
                    ];
                }
            } else {
                // Tanpa record: alpha hanya bila hari kerja lampau dan bukan
                // izin approved (libur sudah ditangani di atas).
                $isLeave = isset($leaveMap[$guru->id][$tanggal]);
                if (! $isLeave && $tanggal < $tanggalHariIni) {
                    $cntA++;
                    $sel[] = ['kelas' => 'bg-rose-50', 'jenis' => 'teks', 'teks' => '✕', 'warna' => 'font-extrabold text-rose-700'];
                } else {
                    $sel[] = ['kelas' => 'bg-slate-100', 'jenis' => 'kosong', 'teks' => '—', 'warna' => 'text-slate-300'];
                }
            }
        }

        // ── Ringkasan: poin & denda (libur nasional + mingguan dilewati) ──
        $totalPoin = 0;
        $totalDendaHarian = 0;
        foreach ($mapByUser[$guru->id] ?? [] as $tanggal => $baris) {
            if (attendance_is_holiday_date($tanggal, $jadwalKerja, $hariLiburMingguan, $holidays)) {
                continue;
            }
            $totalPoin += (int) ($baris->points ?? 0);
            $totalDendaHarian += (float) ($baris->denda ?? 0);
        }

        $totalPoin += $cntA * -abs((int) ($absenSetting->point_alpa ?? 5));
        $dendaAlpha = $cntA * (int) ($absenSetting->denda_alpha_per_hari ?? 0);
        $totalDenda = $totalDendaHarian + $dendaAlpha;
        $cntATotal = $cntA + $cntARecorded;

        $rekomendasi = 'Baik';
        $rekomendasiKelas = 'bg-emerald-50 text-emerald-700';
        if ($cntATotal >= 3 || $totalPoin <= -100) {
            $rekomendasi = 'SP 2';
            $rekomendasiKelas = 'bg-rose-50 text-rose-700';
        } elseif ($cntATotal >= 1 || $cntTL >= 10 || $totalPoin <= -50) {
            $rekomendasi = 'SP 1';
            $rekomendasiKelas = 'bg-amber-50 text-amber-700';
        } elseif ($cntTL >= 5 || $totalPoin <= -20) {
            $rekomendasi = 'Pembinaan';
            $rekomendasiKelas = 'bg-blue-50 text-blue-700';
        }

        $barisRekap[] = [
            'no' => $nomor++,
            'nama' => (string) ($guru->nama_guru ?? '—'),
            'nip' => (string) ($guru->nip ?? ''),
            'sel' => $sel,
            // Ringkasan kiri-ke-kanan; urutan harus sama dengan $kolomRingkas.
            'ringkas' => [
                ['nilai' => $cntH, 'kelas' => 'bg-emerald-50/60 text-emerald-700'],
                ['nilai' => $cntTL, 'kelas' => 'bg-amber-50/60 text-amber-600'],
                ['nilai' => $cntD, 'kelas' => 'bg-cyan-50/60 text-cyan-700'],
                ['nilai' => $cntW, 'kelas' => 'bg-emerald-50/60 text-emerald-700'],
                ['nilai' => $cntIS, 'kelas' => 'bg-blue-50/60 text-blue-700'],
                ['nilai' => $cntATotal, 'kelas' => 'bg-rose-50/60 text-rose-700'],
            ],
            'poin' => ($totalPoin >= 0 ? '+' : '') . number_format($totalPoin, 0, ',', '.'),
            'poinNegatif' => $totalPoin < 0,
            'denda' => $totalDenda > 0 ? 'Rp ' . number_format($totalDenda, 0, ',', '.') : '—',
            'rekomendasi' => $rekomendasi,
            'rekomendasiKelas' => $rekomendasiKelas,
            'lunas' => isset($lunasMap[$guru->id]),
        ];
    }

    $config = [
        'bulan' => $bulanInt,
        'tahun' => $tahunInt,
        'csrf' => csrf_token(),
        'urlKirim' => url('admin/presensi/rekap/kirim'),
    ];
@endphp

<div class="space-y-4" x-data="rekapBulanan(@js($config))">

    <x-ui.page-header title="Rekap Presensi Bulanan" icon="bi-file-earmark-spreadsheet" tone="emerald"
        subtitle="{{ $labelPeriode }} — {{ count($users) }} guru"
        :crumbs="['Presensi', 'Rekap']">
        <x-slot:actions>
            <button type="button" x-on:click="window.print()" title="Cetak rekap (landscape)"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100 print:hidden">
                <i class="bi bi-printer"></i> Cetak
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Tab guru / siswa --}}
    <div class="flex flex-wrap items-center gap-2 print:hidden">
        <a href="{{ url('admin/presensi/rekap?bulan=' . $bulanInt . '&tahun=' . $tahunInt) }}"
            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white no-underline shadow-sm shadow-emerald-500/20">
            <i class="bi bi-person-badge"></i> Rekap Presensi Guru
        </a>
        <a href="{{ url('admin/presensi/siswa/rekap?bulan=' . $bulanInt . '&tahun=' . $tahunInt) }}"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 no-underline transition-colors hover:bg-slate-50">
            <i class="bi bi-people-fill"></i> Rekap Presensi Siswa
        </a>
    </div>

    <x-ui.card body-class="p-0" title="Rekap Kehadiran {{ $labelPeriode }}" icon="bi-table" tone="emerald"
        subtitle="Tabel rekap harian seluruh guru aktif pada periode terpilih.">
        <x-slot:actions>
            <a href="{{ url('admin/absensi/rekap/export?bulan=' . $bulanInt . '&tahun=' . $tahunInt) }}" target="_blank"
                class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-700 no-underline transition-colors hover:bg-emerald-100 print:hidden">
                <i class="bi bi-file-earmark-excel"></i> Excel
            </a>
            <a href="{{ url('admin/absensi/rekap/pdf?bulan=' . $bulanInt . '&tahun=' . $tahunInt) }}" target="_blank"
                class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-bold text-rose-700 no-underline transition-colors hover:bg-rose-100 print:hidden">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </a>
            <button type="button" x-on:click="buka()"
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-emerald-700 print:hidden">
                <i class="bi bi-send"></i> Kirim ke WA &amp; TG
            </button>
            <a href="{{ url('admin/presensi') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200 print:hidden">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </x-slot:actions>

        <x-slot:toolbar>
            <form method="get" class="flex flex-wrap items-end gap-3 print:hidden">
                <div>
                    <label for="fBulan" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Bulan</label>
                    <select name="bulan" id="fBulan"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                        @foreach ($namaBulan as $kode => $nama)
                            <option value="{{ $kode }}" @selected($bulanInt === $kode)>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="fTahun" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Tahun</label>
                    <select name="tahun" id="fTahun"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                        @for ($y = (int) date('Y'); $y >= attendance_tahun_tertua(); $y--)
                            <option value="{{ $y }}" @selected($tahunInt === $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-emerald-700">
                    <i class="bi bi-search"></i> Tampilkan
                </button>
            </form>

            <div class="mt-3 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-2.5 text-[11px] text-slate-500 print:hidden">
                <span class="font-bold text-slate-600">Keterangan:</span>
                @foreach ($legenda as $item)
                    <span class="inline-flex items-center gap-1.5">
                        <span class="inline-flex h-5 w-5 items-center justify-center rounded text-[9px] font-bold {{ $item['kelas'] }} {{ $item['warna'] }}">{{ $item['simbol'] }}</span>
                        <span>{{ $item['label'] }}</span>
                    </span>
                @endforeach
            </div>
        </x-slot:toolbar>

        @if (count($users) === 0)
            <div class="p-5">
                <x-ui.empty icon="bi-people" title="Belum ada data guru"
                    description="Tidak ada guru aktif yang dapat direkap pada {{ $labelPeriode }}." />
            </div>
        @else
            <div class="max-h-[76vh] overflow-auto print:max-h-none print:overflow-visible" tabindex="0">
                <table class="w-full table-fixed border-collapse text-[11.5px]">
                    <caption class="sr-only">Rekap presensi harian guru periode {{ $labelPeriode }}</caption>
                    <colgroup>
                        <col class="w-[42px]">
                        <col class="w-[190px]">
                        @foreach ($kalender as $hari)
                            <col class="w-[46px]">
                        @endforeach
                        <col class="w-[38px]">
                        <col class="w-[38px]">
                        <col class="w-[38px]">
                        <col class="w-[38px]">
                        <col class="w-[38px]">
                        <col class="w-[38px]">
                        <col class="w-[46px]">
                        <col class="w-[112px]">
                        <col class="w-[104px]">
                        <col class="w-[86px]">
                    </colgroup>

                    <thead>
                        <tr>
                            <th rowspan="2" scope="col"
                                class="sticky left-0 top-0 z-40 h-8 border border-slate-200 bg-slate-50 px-1 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500">No</th>
                            <th rowspan="2" scope="col"
                                class="sticky left-[42px] top-0 z-40 h-8 border border-slate-200 bg-slate-50 px-2 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">Nama Guru / NIP</th>
                            <th colspan="{{ $daysInMonth }}"
                                class="sticky top-0 z-30 h-8 border border-slate-200 bg-slate-50 px-2 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Kehadiran — {{ $labelPeriode }}
                            </th>
                            @foreach ($kolomRingkas as $kolom)
                                <th rowspan="2" scope="col" title="{{ $kolom['tip'] }}"
                                    class="sticky top-0 z-30 border border-slate-200 px-1 text-center text-[10px] font-bold uppercase tracking-wider {{ $kolom['kelas'] }}">{{ $kolom['label'] }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach ($kalender as $hari)
                                <th scope="col" title="{{ $hari['tanggal'] }}"
                                    class="sticky top-8 z-30 h-8 border border-slate-200 px-0.5 py-1 text-center text-[10px] font-bold uppercase leading-tight {{ $hari['libur'] ? 'bg-rose-50 text-rose-400' : 'bg-slate-50 text-slate-500' }}">
                                    <span class="block">{{ $hari['label'] }}</span>
                                    <span class="block text-[10px] font-normal">{{ $hari['hari'] }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($barisRekap as $baris)
                            <tr>
                                <td class="sticky left-0 z-10 h-[38px] border border-slate-200 bg-white px-1 text-center font-mono text-[11px] text-slate-400">{{ $baris['no'] }}</td>
                                <td class="sticky left-[42px] z-10 border border-slate-200 bg-white px-2 py-1 text-left">
                                    <div class="truncate text-[12px] font-semibold text-slate-800" title="{{ $baris['nama'] }}">{{ $baris['nama'] }}</div>
                                    <div class="truncate text-[10px] text-slate-400">{{ $baris['nip'] !== '' ? $baris['nip'] : '—' }}</div>
                                </td>

                                @foreach ($baris['sel'] as $sel)
                                    <td class="h-[38px] border border-slate-200 px-0.5 py-0.5 text-center align-middle {{ $sel['kelas'] }}">
                                        @if ($sel['jenis'] === 'waktu')
                                            <div class="text-[10px] leading-tight">
                                                <div class="border-b border-slate-200/80 pb-px font-bold {{ $sel['warnaMasuk'] }}">{{ $sel['masuk'] }}</div>
                                                <div class="text-slate-400">{{ $sel['pulang'] }}</div>
                                            </div>
                                        @else
                                            <span class="text-[10px] {{ $sel['warna'] }}">{{ $sel['teks'] }}</span>
                                        @endif
                                    </td>
                                @endforeach

                                @foreach ($baris['ringkas'] as $ringkas)
                                    <td class="h-[38px] whitespace-nowrap border border-slate-200 px-1 text-center text-[11px] font-bold tabular-nums {{ $ringkas['kelas'] }}">{{ $ringkas['nilai'] }}</td>
                                @endforeach

                                <td class="h-[38px] whitespace-nowrap border border-slate-200 bg-slate-50 px-1 text-center text-[11px] font-bold tabular-nums {{ $baris['poinNegatif'] ? 'text-rose-600' : 'text-emerald-600' }}">{{ $baris['poin'] }}</td>
                                <td class="h-[38px] whitespace-nowrap border border-slate-200 px-1.5 text-right text-[10.5px] font-semibold tabular-nums {{ $baris['denda'] === '—' ? 'text-slate-300' : 'text-orange-700' }}">{{ $baris['denda'] }}</td>
                                <td class="h-[38px] border border-slate-200 px-1 text-center">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold {{ $baris['rekomendasiKelas'] }}">{{ $baris['rekomendasi'] }}</span>
                                </td>
                                <td class="h-[38px] border border-slate-200 px-1 text-center">
                                    @if ($baris['lunas'])
                                        <span class="inline-flex items-center gap-0.5 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                                            <i class="bi bi-check-circle"></i> Lunas
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-0.5 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">
                                            <i class="bi bi-clock"></i> Belum
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400 print:hidden">
                <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span>H=Hadir</span>
                    <span>TL=Terlambat</span>
                    <span>D=Dinas</span>
                    <span>W=WFH</span>
                    <span>I/S=Izin/Sakit</span>
                    <span>A=Alpha</span>
                </span>
                <span class="font-semibold text-slate-500">Total {{ count($users) }} guru &middot; {{ $labelPeriode }}</span>
            </div>
        </x-slot:footer>
    </x-ui.card>

    {{-- ─── Modal kirim rekap ke grup WhatsApp & Telegram ─────────────── --}}
    <x-ui.modal id="modalKirimRekap" title="Kirim Rekap ke Grup" icon="bi-send" tone="emerald" size="max-w-md">
        <div class="space-y-4 overflow-y-auto p-5">
            <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <i class="bi bi-file-earmark-pdf mt-0.5 shrink-0 text-xl text-rose-500"></i>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-700">PDF Rekap Presensi Guru</p>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Periode: <span class="font-medium text-slate-700">{{ $labelPeriode }}</span><br>
                        <span>{{ count($users) }} guru</span>
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-2.5 rounded-xl border border-blue-100 bg-blue-50 p-3 text-xs leading-relaxed text-blue-700">
                <i class="bi bi-info-circle mt-0.5 shrink-0"></i>
                <span>PDF dibuat ulang dari data terkini lalu dikirim ke <b>grup WhatsApp</b> dan <b>grup Telegram</b> yang dikonfigurasi di Pengaturan Notifikasi.</span>
            </div>

            <div x-show="hasil" x-cloak aria-live="polite"
                class="rounded-xl border p-4 text-sm"
                x-bind:class="hasil && hasil.berhasil
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                    : 'border-rose-200 bg-rose-50 text-rose-800'">
                <div class="flex items-start gap-2.5">
                    <i class="bi mt-0.5 shrink-0 text-lg"
                        x-bind:class="hasil && hasil.berhasil ? 'bi-check-circle-fill text-emerald-500' : 'bi-x-circle-fill text-rose-500'"></i>
                    <div class="min-w-0">
                        <p class="mb-1 font-semibold" x-text="hasil ? hasil.pesan : ''"></p>
                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs" x-show="hasil && hasil.adaDetail">
                            <span class="inline-flex items-center gap-1">
                                <i class="bi" x-bind:class="hasil && hasil.telegram ? 'bi-check-circle text-emerald-500' : 'bi-x-circle text-rose-400'"></i>
                                Telegram: <span x-text="hasil && hasil.telegram ? 'terkirim' : 'gagal'"></span>
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <i class="bi" x-bind:class="hasil && hasil.whatsapp ? 'bi-check-circle text-emerald-500' : 'bi-x-circle text-rose-400'"></i>
                                WhatsApp: <span x-text="hasil && hasil.whatsapp ? 'terkirim' : 'gagal'"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex shrink-0 items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
            <button type="button" x-on:click="tutup()" x-bind:disabled="mengirim"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-60">
                <span x-text="selesai ? 'Tutup' : 'Batal'"></span>
            </button>
            <button type="button" x-show="!selesai" x-on:click="kirim()" x-bind:disabled="mengirim"
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                <i class="bi bi-arrow-repeat animate-spin" x-show="mengirim" x-cloak></i>
                <i class="bi bi-send" x-show="!mengirim"></i>
                <span x-text="mengirim ? 'Mengirim…' : (hasil ? 'Coba Lagi' : 'Kirim Sekarang')"></span>
            </button>
        </div>
    </x-ui.modal>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('rekapBulanan', (config) => ({
        bulan: config.bulan,
        tahun: config.tahun,
        csrf: config.csrf || '',
        urlKirim: config.urlKirim || '',
        mengirim: false,
        hasil: null,

        get selesai() {
            return Boolean(this.hasil && this.hasil.berhasil);
        },

        buka() {
            this.hasil = null;
            this.mengirim = false;
            this.$store.modals.open('#modalKirimRekap');
        },

        tutup() {
            this.$store.modals.close('#modalKirimRekap');
        },

        async kirim() {
            if (this.mengirim || this.selesai) {
                return;
            }

            this.mengirim = true;
            this.hasil = null;

            try {
                const body = new FormData();
                body.append('bulan', this.bulan);
                body.append('tahun', this.tahun);

                const respon = await fetch(this.urlKirim, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    body,
                });

                const data = await respon.json().catch(() => ({}));
                const detail = data.data || {};
                const berhasil = respon.ok
                    && (data.status === true || data.status === 1 || data.status === 'true');

                this.hasil = {
                    berhasil,
                    pesan: data.message || (berhasil ? 'Rekap berhasil dikirim.' : 'Gagal mengirim rekap.'),
                    telegram: detail.telegram === true,
                    whatsapp: detail.whatsapp === true,
                    adaDetail: detail.telegram !== undefined || detail.whatsapp !== undefined,
                };
            } catch (err) {
                this.hasil = {
                    berhasil: false,
                    pesan: 'Koneksi gagal: ' + err.message,
                    telegram: false,
                    whatsapp: false,
                    adaDetail: false,
                };
            } finally {
                this.mengirim = false;
            }
        },
    }));
});
</script>
@endsection
