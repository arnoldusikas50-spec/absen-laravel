@extends('layouts.main')

@section('styles')
<style>
@media print {
    @page { size: landscape; margin: 8mm; }

    body { background: #fff; }
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
@endsection

@section('content')

<?php
$namaBulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
              7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$bulanInt  = (int) $bulan;
$dayLabels = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

$subjudulRekap = ($namaBulan[$bulanInt] ?? '') . ' ' . $tahun
    . ' — ' . count($students) . ' siswa'
    . ' · ' . (! empty($kelasInfo) ? 'Kelas ' . $kelasInfo->nama_kelas : 'Semua Kelas')
    . ' · ' . ($hariEfektifLabel ?? ($hariEfektif . ' hari efektif'));

// Kelas sel tabel ditulis utuh (bukan dirakit) supaya ikut ter-scan saat build Tailwind.
$kepalaDasar   = 'sticky top-0 z-[3] border-b-2 border-slate-300 bg-slate-50 px-1 py-1 text-[0.68rem] font-bold uppercase tracking-[0.04em] text-slate-500 print:static';
$kepalaWarna   = 'sticky top-0 z-[3] border-b-2 border-slate-300 px-1 py-1 text-[0.68rem] font-bold uppercase tracking-[0.04em] print:static';
$kepalaNo      = 'sticky left-0 top-0 z-[5] w-[42px] min-w-[42px] border-b-2 border-slate-300 bg-slate-50 px-1 py-1 text-[0.68rem] font-bold uppercase tracking-[0.04em] text-slate-500 print:static';
$kepalaNama    = 'sticky left-[42px] top-0 z-[5] w-[190px] min-w-[190px] border-b-2 border-slate-300 bg-slate-50 px-2 py-1 text-left text-[0.68rem] font-bold uppercase tracking-[0.04em] text-slate-500 print:static';
$kepalaHari    = 'sticky top-0 z-[3] border-b-2 border-slate-300 bg-slate-50 px-0.5 py-[3px] text-[0.68rem] font-bold uppercase tracking-[0.04em] text-slate-500 print:static';
$kepalaHariOff = 'sticky top-0 z-[3] border-b-2 border-slate-300 bg-red-50 px-0.5 py-[3px] text-[0.68rem] font-bold uppercase tracking-[0.04em] text-red-400 print:static';

$selNo      = 'sticky left-0 z-[2] w-[42px] min-w-[42px] bg-white px-1 py-0 text-center font-mono text-xs text-slate-400 print:static';
$selNama    = 'sticky left-[42px] z-[2] w-[190px] min-w-[190px] bg-white px-2 py-1 text-left print:static';
$selHari    = 'h-[38px] w-[46px] min-w-[46px] px-px py-0.5 leading-[1.2]';
$selRingkas = 'w-[38px] min-w-[38px] px-1 py-[3px] font-bold';
$selPersen  = 'w-[55px] min-w-[55px] px-1 py-[3px] font-bold';
$selDenda   = 'w-[96px] min-w-[96px] px-1.5 py-[3px] whitespace-nowrap text-right text-[0.7rem] font-bold';
?>

<div class="px-4 pt-4 md:px-6 md:pt-6">
    <x-ui.page-header
        title="Rekap Presensi Bulanan Siswa"
        icon="bi-people-fill"
        tone="blue"
        :subtitle="$subjudulRekap"
        :crumbs="['Presensi Siswa', 'Rekap']">
        <x-slot:actions>
            <a href="{{ url('admin/presensi/rekap?bulan=' . $bulan . '&tahun=' . $tahun) }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50 print:hidden">
                <i class="bi bi-person-badge"></i> Rekap Presensi Guru
            </a>
            <span class="inline-flex items-center gap-1.5 rounded-xl bg-sky-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs shadow-sky-500/20 print:hidden">
                <i class="bi bi-people-fill"></i> Rekap Presensi Siswa
            </span>
        </x-slot:actions>
    </x-ui.page-header>
</div>

<div class="mt-4 space-y-5 px-4 pb-6 md:px-6">

    <x-ui.card bodyClass="p-0">
        <x-slot:toolbar>
            <form method="get" class="flex flex-wrap items-center gap-3 print:hidden">
                <div class="mr-1 flex items-center gap-2">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-50">
                        <i class="bi bi-funnel text-sm text-sky-600"></i>
                    </span>
                    <h2 class="text-sm font-bold text-slate-700">Filter Periode &amp; Rombel</h2>
                </div>

                <select name="id_kelas" aria-label="Kelas"
                    class="h-9 min-w-[160px] rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 focus:border-blue-500 focus:outline-none">
                    <option value="0" @selected($idKelas === 0)>— Semua Kelas —</option>
                    @foreach ($kelases as $k)
                        <option value="{{ $k->id_kelas }}" @selected((int) $idKelas === (int) $k->id_kelas)>
                            {{ $k->nama_kelas }} ({{ (int) ($k->jumlah_siswa ?? 0) }} siswa)
                        </option>
                    @endforeach
                </select>

                <select name="bulan" aria-label="Bulan"
                    class="h-9 w-[140px] rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 focus:border-blue-500 focus:outline-none">
                    @foreach ($namaBulan as $m => $nm)
                        <option value="{{ $m }}" @selected($m == (int) $bulan)>{{ $nm }}</option>
                    @endforeach
                </select>

                <select name="tahun" aria-label="Tahun"
                    class="h-9 w-[95px] rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 focus:border-blue-500 focus:outline-none">
                    @for ($y = date('Y'); $y >= attendance_tahun_tertua(); $y--)
                        <option value="{{ $y }}" @selected($y == (int) $tahun)>{{ $y }}</option>
                    @endfor
                </select>

                <button type="submit"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 text-xs font-bold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                    <i class="bi bi-search"></i> Tampilkan
                </button>

                <div class="ml-auto flex items-center gap-1.5">
                    <a href="{{ url('admin/presensi/siswa/rekap/export?bulan=' . $bulan . '&tahun=' . $tahun . '&id_kelas=' . $idKelas) }}"
                        target="_blank"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 text-xs font-bold text-white no-underline shadow-xs transition-colors hover:bg-emerald-700">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </a>
                    <a href="{{ url('admin/presensi/siswa/rekap/pdf?bulan=' . $bulan . '&tahun=' . $tahun . '&id_kelas=' . $idKelas) }}"
                        target="_blank"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-rose-600 px-3.5 text-xs font-bold text-white no-underline shadow-xs transition-colors hover:bg-rose-700">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                    <a href="{{ url('admin/presensi/siswa/harian') }}"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-600 no-underline transition-colors hover:bg-slate-50">
                        <i class="bi bi-arrow-left"></i> Monitoring Harian
                    </a>
                </div>
            </form>
        </x-slot:toolbar>

        @if (empty($students))
            <div class="p-5">
                <x-ui.empty icon="bi-people" title="Belum ada data siswa"
                    description="Belum ada data siswa untuk rombel atau periode ini." />
            </div>
        @else

            <!-- Legenda -->
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-slate-50 px-5 py-2.5 text-xs text-slate-500 print:hidden">
                <span class="font-semibold text-slate-600">Keterangan:</span>
                <?php
                $legends = [
                    ['bg-emerald-50 border border-emerald-200', '●', 'text-emerald-600', 'Hadir tepat waktu'],
                    ['bg-amber-50 border border-amber-200', '●', 'text-amber-500', 'Terlambat'],
                    ['bg-yellow-50 border border-yellow-200', 'S', 'text-yellow-600', 'Sakit'],
                    ['bg-blue-50 border border-blue-200', 'I', 'text-blue-600', 'Izin'],
                    ['bg-red-50 border border-red-200', '✕', 'text-red-500', 'Alpa / Tanpa Keterangan'],
                    ['bg-teal-50 border border-teal-200', 'R', 'text-teal-600', 'BDR / Belajar dari Rumah'],
                    ['bg-slate-100 border border-slate-200', '—', 'text-slate-400', 'Libur / Akhir Pekan'],
                ];
                ?>
                @foreach ($legends as [$bg, $sym, $tc, $label])
                    <span class="inline-flex items-center gap-1.5">
                        <span class="inline-flex h-5 w-5 items-center justify-center rounded text-[10px] font-bold {{ $bg }} {{ $tc }}">{{ $sym }}</span>
                        <span>{{ $label }}</span>
                    </span>
                @endforeach
            </div>

            <!-- Tabel rekap -->
            <div class="max-h-[76vh] overflow-auto print:max-h-none print:overflow-visible">
                <table
                    class="w-full border-collapse text-center text-[0.72rem] [table-layout:fixed]
                        [&_th]:border [&_td]:border [&_th]:border-slate-200 [&_td]:border-slate-200
                        [&_th]:align-middle [&_td]:align-middle
                        print:[&_th]:border-slate-400 print:[&_td]:border-slate-400">
                    <caption class="sr-only">Rekap presensi harian siswa {{ $namaBulan[$bulanInt] ?? '' }} {{ $tahun }}</caption>
                    <thead>
                        <!-- Baris 1: judul grup kolom -->
                        <tr>
                            <th scope="col" rowspan="2" class="{{ $kepalaNo }}">No</th>
                            <th scope="col" rowspan="2" class="{{ $kepalaNama }}">Nama Siswa / NIS</th>
                            <th scope="colgroup" colspan="{{ $daysInMonth }}" class="{{ $kepalaDasar }} py-2">
                                Kehadiran &mdash; {{ $namaBulan[$bulanInt] }} {{ $tahun }}
                                @if (! empty($kelasInfo))
                                    ({{ $kelasInfo->nama_kelas }})
                                @endif
                            </th>
                            <th scope="col" class="{{ $kepalaWarna }} bg-emerald-50 py-2 text-emerald-700" title="Total Hadir">H</th>
                            <th scope="col" class="{{ $kepalaWarna }} bg-amber-50 py-2 text-amber-600" title="Terlambat">TL</th>
                            <th scope="col" class="{{ $kepalaWarna }} bg-yellow-50 py-2 text-yellow-700" title="Sakit">S</th>
                            <th scope="col" class="{{ $kepalaWarna }} bg-blue-50 py-2 text-blue-700" title="Izin">I</th>
                            <th scope="col" class="{{ $kepalaWarna }} bg-red-50 py-2 text-red-600" title="Alpa">A</th>
                            <th scope="col" class="{{ $kepalaWarna }} bg-teal-50 py-2 text-teal-700" title="Belajar dari Rumah">R</th>
                            <th scope="col" class="{{ $kepalaWarna }} bg-indigo-50 py-2 text-indigo-700" title="Persentase Kehadiran">%</th>
                            <th scope="col" class="{{ $kepalaWarna }} bg-rose-50 py-2 text-rose-700" title="Total denda alpha &amp; bolos (sudah dipotong batas denda siswa per bulan)">Denda</th>
                        </tr>
                        <!-- Baris 2: tanggal -->
                        <tr>
                            <?php for ($d = 1; $d <= $daysInMonth; $d++):
                                $dateStr   = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                                $ts        = strtotime($dateStr);
                                $dw        = date('w', $ts); // 0=Sun
                                $dayEn     = date('D', $ts);
                                // Gabungan dua sumber, sama seperti sel di bawahnya — kalau
                                // tidak, header dan isi tabel bisa beda pendapat soal hari libur.
                                $isLibur   = attendance_is_weekly_holiday($dayEn, (int) date('N', $ts), $jadwalKerja ?? [], $hariLiburMingguan ?? []);
                                $isHoliday = isset($holidays[$dateStr]);
                            ?>
                            <th scope="col" class="{{ $isLibur || $isHoliday ? $kepalaHariOff : $kepalaHari }}"
                                title="{{ $isHoliday ? $holidays[$dateStr] : '' }}">
                                <div class="font-bold">{{ $dayLabels[$dw] }}</div>
                                <div class="text-[0.64rem] font-normal">{{ sprintf('%02d', $d) }}</div>
                            </th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody class="[&>tr:hover>td]:bg-slate-50">
                    <?php
                    $no = 1;
                    $grandH = $grandTL = $grandS = $grandI = $grandA = $grandB = $grandBLuarJadwal = 0;
                    $grandEfektif = 0;
                    $grandDenda = 0.0;

                    foreach ($students as $siswa):
                        $cntH = $cntTL = $cntS = $cntI = $cntA = $cntB = 0;
                        $cntBLuarJadwal = 0;
                        $cntBdrHadir = 0;
                        // Setiap siswa memakai jadwal BDR dan hari efektif kelasnya sendiri,
                        // agar mode "Semua Kelas" tetap benar saat tiap kelas punya BDR
                        // berbeda.
                        $kelasSiswaId     = (int) ($siswa->id_kelas ?? 0);
                        $bdrTanggalSiswa  = $bdrByKelas[$kelasSiswaId] ?? [];
                        $hariEfektifSiswa = $hariEfektifByKelas[$kelasSiswaId] ?? 0;
                    ?>
                    <tr>
                        <!-- No -->
                        <td class="{{ $selNo }}">{{ $no++ }}</td>

                        <!-- Nama & identitas -->
                        <td class="{{ $selNama }}">
                            <div class="max-w-[180px] truncate font-semibold text-slate-800" title="{{ $siswa->nama }}">
                                {{ $siswa->nama }}
                            </div>
                            <div class="flex items-center gap-1.5 text-[0.65rem] text-slate-400">
                                <span>{{ $siswa->nis ?? $siswa->nisn ?? '—' }}</span>
                                @if ($idKelas === 0 && ! empty($siswa->nama_kelas))
                                    <span class="font-medium text-sky-600">&middot; {{ $siswa->nama_kelas }}</span>
                                @endif
                            </div>
                        </td>

                        <!-- Sel harian -->
                        <?php for ($d = 1; $d <= $daysInMonth; $d++):
                            $dateStr   = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                            $ts        = strtotime($dateStr);
                            $dayEn     = date('D', $ts);
                            $dayNum    = (int) date('N', $ts); // 1=Senin..7=Minggu
                            $isLiburMingguan = attendance_is_weekly_holiday($dayEn, $dayNum, $jadwalKerja ?? [], $hariLiburMingguan ?? []);
                            $isHoliday = isset($holidays[$dateStr]);
                            $isBdrJadwal = isset($bdrTanggalSiswa[$dateStr]);
                            $rec       = $mapBySiswa[$siswa->id_siswa][$dateStr] ?? null;

                            $cell  = attendance_rekap_day_cell(
                                $rec,
                                $dateStr,
                                $isLiburMingguan,
                                $isHoliday,
                                $isBdrJadwal,
                                $jamBatasMasukPerHari[$d] ?? $jamBatasMasuk,
                                null,
                                attendance_hari_tercatat($tercatatByKelas ?? [], $dateStr, $kelasSiswaId)
                            );
                            $state = $cell['state'];

                            $bgClass   = '';
                            $cellInner = '';

                            switch ($state) {
                                case 'holiday':
                                    $bgClass   = 'bg-red-50';
                                    $cellInner = '<span class="text-slate-300">—</span>';
                                    break;

                                case 'off':
                                    $bgClass   = 'bg-rose-50/60';
                                    $cellInner = '<span class="text-slate-300">—</span>';
                                    break;

                                case 'present':
                                case 'late':
                                    $cntH++;
                                    if ($state === 'late') $cntTL++;

                                    // Hadir fisik di hari BDR tetap dihitung; hari itu masuk
                                    // kembali ke pembagi persen.
                                    if ($isBdrJadwal) $cntBdrHadir++;

                                    $bgClass = $state === 'late' ? 'bg-amber-50' : 'bg-emerald-50';

                                    $inT  = $cell['time_in'] !== ''  ? substr($cell['time_in'], 0, 5) : 'H';
                                    $outT = $cell['time_out'] !== '' ? substr($cell['time_out'], 0, 5) : '';

                                    $inColor  = $state === 'late' ? 'text-amber-700' : 'text-emerald-600';
                                    $outColor = 'text-slate-400';

                                    if ($cell['time_in'] !== '') {
                                        $cellInner = '<div class="px-[3px] py-0.5 text-[0.63rem] leading-[1.15]">
                                            <div class="mb-px border-b border-slate-200 pb-px font-bold ' . $inColor . '">' . $inT . '</div>';
                                        if ($outT !== '') {
                                            $cellInner .= '<div class="' . $outColor . '">' . $outT . '</div>';
                                        }
                                        $cellInner .= '</div>';
                                    } else {
                                        $cellInner = '<span class="font-bold ' . $inColor . '">H</span>';
                                    }
                                    break;

                                case 'sakit':
                                    $cntS++;
                                    $bgClass = 'bg-yellow-50';
                                    $cellInner = '<span class="text-[0.65rem] font-bold text-yellow-600" title="Sakit">S</span>';
                                    break;

                                case 'izin':
                                    $cntI++;
                                    $bgClass = 'bg-blue-50';
                                    $cellInner = '<span class="text-[0.65rem] font-bold text-blue-600" title="Izin">I</span>';
                                    break;

                                case 'bdr_scheduled':
                                    $cntB++;
                                    $bgClass = 'bg-teal-50';
                                    $cellInner = '<span class="font-extrabold text-teal-700" title="Belajar dari Rumah">R</span>';
                                    break;

                                case 'bdr_outside':
                                    $cntB++;
                                    $cntBLuarJadwal++;
                                    $bgClass = 'bg-teal-50';
                                    $cellInner = '<span class="font-extrabold text-teal-700" title="Belajar dari Rumah (di luar jadwal)">R</span>';
                                    break;

                                case 'alpha':
                                    $cntA++;
                                    $bgClass = 'bg-red-50';
                                    $cellInner = '<span class="font-extrabold text-red-700" title="Alpa / Tanpa Keterangan">✕</span>';
                                    break;

                                case 'no_data':
                                    // Hari kerja yang presensinya tidak tercatat sama
                                    // sekali untuk kelas ini: absensi belum jalan, bukan
                                    // satu kelas membolos bersama. Tidak masuk kolom Alpha
                                    // dan tidak menagih denda maupun surat panggilan.
                                    $bgClass   = 'bg-slate-100';
                                    $cellInner = '<span class="text-slate-300" title="Presensi hari ini belum tercatat">&mdash;</span>';
                                    break;

                                default: // future
                                    $bgClass   = 'bg-slate-50';
                                    $cellInner = '<span class="text-slate-200">—</span>';
                            }
                        ?>
                        <td class="{{ $selHari }} {{ $bgClass }}">
                            {!! $cellInner !!}
                        </td>
                        <?php endfor; ?>

                        <!-- Kolom ringkasan siswa -->
                        <?php
                        // Hari BDR di luar jadwal dikeluarkan dari pembagi; hari BDR yang
                        // dihadiri fisik dikembalikan ke pembagi.
                        $efektifSiswa  = max(0, $hariEfektifSiswa + $cntBdrHadir - $cntBLuarJadwal);
                        $persen = $efektifSiswa > 0 ? round(($cntH / $efektifSiswa) * 100, 1) : 0;
                        $grandH  += $cntH;
                        $grandTL += $cntTL;
                        $grandB  += $cntB;
                        $grandBLuarJadwal += $cntBLuarJadwal;
                        $grandS  += $cntS;
                        $grandI  += $cntI;
                        $grandA  += $cntA;
                        $grandEfektif += $efektifSiswa;
                        // Netto sudah dipotong plafon bulanan per siswa (SiswaDendaService),
                        // jadi jumlah baris bawah adalah total tagihan periode ini.
                        $ringkasDenda  = $dendaSiswa[(int) $siswa->id_siswa] ?? [];
                        $dendaNetto  = (float) ($ringkasDenda['netto'] ?? 0);
                        $dendaAlpha  = (int) ($ringkasDenda['alpha'] ?? 0);
                        $dendaBolos  = (int) ($ringkasDenda['bolos'] ?? 0);
                        $grandDenda += $dendaNetto;
                        ?>
                        <td class="{{ $selRingkas }} bg-emerald-50 text-emerald-700">{{ $cntH }}</td>
                        <td class="{{ $selRingkas }} bg-amber-50 text-amber-600">{{ $cntTL }}</td>
                        <td class="{{ $selRingkas }} bg-yellow-50 text-yellow-700">{{ $cntS }}</td>
                        <td class="{{ $selRingkas }} bg-blue-50 text-blue-700">{{ $cntI }}</td>
                        <td class="{{ $selRingkas }} bg-red-50 text-red-600">{{ $cntA }}</td>
                        <td class="{{ $selRingkas }} bg-teal-50 text-teal-700">{{ $cntB }}</td>
                        <td class="{{ $selPersen }} bg-indigo-50 text-indigo-700">{{ $persen }}%</td>
                        <td class="{{ $selDenda }} bg-rose-50 text-rose-700"
                            title="Alpha ber-denda: {{ $dendaAlpha }} hari · Bolos ber-denda: {{ $dendaBolos }} hari">
                            {!! $dendaNetto > 0 ? 'Rp&nbsp;' . number_format($dendaNetto, 0, ',', '.') : '<span class="text-slate-300">—</span>' !!}
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold text-slate-700">
                            <td colspan="2" class="px-3 py-2 text-right">Rata-rata / Total:</td>
                            <td colspan="{{ $daysInMonth }}" class="text-xs font-normal text-slate-400">
                                {{ count($students) }} Siswa &middot; {{ $hariEfektifLabel ?? ($hariEfektif . ' hari efektif') }}
                            </td>
                            <td class="{{ $selRingkas }} bg-emerald-100 text-emerald-800">{{ $grandH }}</td>
                            <td class="{{ $selRingkas }} bg-amber-100 text-amber-800">{{ $grandTL }}</td>
                            <td class="{{ $selRingkas }} bg-yellow-100 text-yellow-800">{{ $grandS }}</td>
                            <td class="{{ $selRingkas }} bg-blue-100 text-blue-800">{{ $grandI }}</td>
                            <td class="{{ $selRingkas }} bg-red-100 text-red-800">{{ $grandA }}</td>
                            <td class="{{ $selRingkas }} bg-teal-100 text-teal-800">{{ $grandB }}</td>
                            <?php
                            $grandPersen = $grandEfektif > 0 ? round(($grandH / $grandEfektif) * 100, 1) : 0;
                            ?>
                            <td class="{{ $selPersen }} bg-indigo-100 text-indigo-800">{{ $grandPersen }}%</td>
                            <td class="{{ $selDenda }} bg-rose-100 text-rose-800">
                                {!! $grandDenda > 0 ? 'Rp&nbsp;' . number_format($grandDenda, 0, ',', '.') : '—' !!}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

        <x-slot:footer>
            <div class="flex flex-col gap-2 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                <span class="print:hidden">
                    <i class="bi bi-info-circle mr-1"></i>
                    H=Hadir &nbsp;&middot;&nbsp; TL=Terlambat &nbsp;&middot;&nbsp; S=Sakit &nbsp;&middot;&nbsp; I=Izin &nbsp;&middot;&nbsp; A=Alpa / Tanpa Keterangan &nbsp;&middot;&nbsp; R=BDR (belajar dari rumah)
                </span>
                <?php
                // Tarif aktif ditampilkan supaya angka kolom Denda bisa dibaca tanpa
                // harus membuka halaman pengaturan.
                $tarifAlphaRekap = (float) ($absenSetting->denda_alpha_siswa_per_hari ?? 0);
                $tarifBolosRekap = (float) ($absenSetting->denda_bolos_siswa_per_hari ?? 0);
                $capRekap        = (float) ($absenSetting->max_denda_siswa_bulan ?? 0);
                ?>
                @if ($tarifAlphaRekap > 0 || $tarifBolosRekap > 0)
                    <span class="print:hidden">
                        Denda: alpha Rp&nbsp;{{ number_format($tarifAlphaRekap, 0, ',', '.') }}/hari
                        &nbsp;&middot;&nbsp; bolos Rp&nbsp;{{ number_format($tarifBolosRekap, 0, ',', '.') }}/hari
                        @if ($capRekap > 0)
                            &nbsp;&middot;&nbsp; maks Rp&nbsp;{{ number_format($capRekap, 0, ',', '.') }}/siswa/bulan
                        @endif
                    </span>
                @endif
                <span class="font-medium text-slate-500">
                    Total {{ count($students) }} siswa &nbsp;&middot;&nbsp; {{ $namaBulan[$bulanInt] }} {{ $tahun }}
                    @if (! empty($waliKelas))
                        &nbsp;&middot;&nbsp; Wali Kelas: <strong>{{ $waliKelas->nama_guru }}</strong>
                    @endif
                </span>
            </div>
        </x-slot:footer>
    </x-ui.card>
</div>

@endsection
