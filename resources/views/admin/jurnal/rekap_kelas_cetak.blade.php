<?php
/**
 * View: Cetak Rekap Jurnal per Kelas — format jurnal mengajar Kurikulum
 * Merdeka (kop sekolah, identitas kelas/fase, tabel jurnal, kolom paraf,
 * tanda tangan). Standalone tanpa layout admin; dicetak/disimpan sebagai PDF
 * lewat dialog cetak browser (A4 landscape).
 */
$namaSekolah = $setting->nama_sekolah ?? $setting->sekolah ?? 'SEKOLAH';
$namaKepsek  = $setting->kepsek ?? '';
$nipKepsek   = $setting->nip ?? '';
$namaBulan   = buat_tanggal(date('F', mktime(0, 0, 0, (int) $bulan, 10)));
$smtLabel    = trim((string) ($smt_active->smt ?? '')) ?: '-';
$tpLabel     = trim((string) ($tp_active->tahun ?? '')) ?: '-';
$namaKelas   = trim((string) ($kelas->nama_kelas ?? '')) ?: '-';
$namaWali    = trim((string) ($kelas->nama_wali ?? ''));
$tanggalCetak = buat_tanggal(date('d F Y'));

$guruPilih = '';
$guruPilihNip = '';
foreach ($gurus as $g) {
    if ((int) $g->id_guru === (int) $id_guru_pilih) {
        $guruPilih = (string) $g->nama_guru;
        $guruPilihNip = (string) ($g->nip ?? '');
        break;
    }
}
$mapelPilih = '';
foreach ($mapels as $m) {
    if ((int) $m->id_mapel === (int) $id_mapel_pilih) {
        $mapelPilih = (string) $m->nama_mapel;
        break;
    }
}

$img64 = function ($rel) {
    if (empty($rel)) return '';
    $path = public_path() . '/' . ltrim($rel, '/');
    if (!is_file($path)) return '';
    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = ($ext === 'jpg') ? 'jpeg' : $ext;
    return 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($path));
};

$kopBase64  = $img64($setting->kopsekolah ?? '');
$logoBase64 = $img64($setting->logo_kiri ?? '');
$ttdBase64  = $img64($setting->tanda_tangan ?? '');

$hariNama = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jurnal Mengajar Kelas {{ $namaKelas }} — {{ $namaBulan }} {{ (int) $tahun }}</title>
    <style>
        @page { size: A4 landscape; margin: 1cm 1.1cm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5px;
            color: #1e293b;
            margin: 16px;
            line-height: 1.4;
        }
        h1, h2, h3, p { margin: 0; padding: 0; }
        .kop { border-bottom: 3px double #0f172a; padding-bottom: 8px; margin-bottom: 10px; }
        .kop-title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .kop-sub { font-size: 10px; color: #475569; }
        .doc-title { text-align: center; margin: 10px 0 2px; font-size: 14px; font-weight: bold; text-transform: uppercase; }
        .doc-sub { text-align: center; font-size: 10px; color: #475569; margin-bottom: 12px; }
        .muted { color: #64748b; }
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .meta-table td { border: 1px solid #94a3b8; padding: 4px 7px; vertical-align: top; }
        .meta-table .lbl { width: 120px; background: #f1f5f9; font-weight: bold; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid thead { display: table-header-group; }
        table.grid th, table.grid td { border: 1px solid #94a3b8; padding: 4px 6px; vertical-align: top; }
        table.grid th { background: #eef2f7; font-size: 9px; text-transform: uppercase; text-align: center; }
        table.grid tr { page-break-inside: avoid; }
        table.grid tbody tr:nth-child(even) td { background: #f8fafc; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; background: #e2e8f0; }
        .badge-hadir { background: #d1fae5; color: #047857; }
        .badge-absent { background: #fee2e2; color: #b91c1c; }
        .paraf { height: 26px; }
        .foot { font-size: 8.5px; color: #64748b; margin-top: 8px; }
        .sign { width: 100%; margin-top: 22px; page-break-inside: avoid; }
        .sign td { vertical-align: top; font-size: 10.5px; }
        .sign .space { height: 58px; }
        .no-print { text-align: right; margin-bottom: 12px; }
        .no-print button {
            padding: 8px 18px; background: #4f46e5; color: #fff; border: none;
            border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 12px;
        }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">Cetak / Simpan PDF</button>
</div>

<!-- Kop -->
<?php if ($kopBase64): ?>
<div style="text-align:center; margin-bottom:10px;">
    <img src="{{ $kopBase64 }}" style="max-height:90px; width:auto; max-width:100%;">
</div>
<?php else: ?>
<table width="100%" class="kop">
    <tr>
        <td width="10%" align="center" valign="middle" style="border:none;">
            <?php if ($logoBase64): ?><img src="{{ $logoBase64 }}" style="width:52px;"><?php endif; ?>
        </td>
        <td align="center" valign="middle" style="border:none;">
            <div class="kop-title">{{ $namaSekolah }}</div>
            <?php if (!empty($setting->alamat)): ?>
            <div class="kop-sub">{{ $setting->alamat }}</div>
            <?php endif; ?>
        </td>
    </tr>
</table>
<?php endif; ?>

<div class="doc-title">Jurnal Mengajar</div>
<div class="doc-sub">
    Rekapitulasi Jurnal Kelas {{ $namaKelas }} &middot; Fase {{ $fase }}
    &middot; {{ $namaBulan }} {{ (int) $tahun }}
</div>

<!-- Identitas -->
<table class="meta-table">
    <tr>
        <td class="lbl">Satuan Pendidikan</td>
        <td>{{ $namaSekolah }}</td>
        <td class="lbl">Kelas / Fase</td>
        <td>{{ $namaKelas }} / {{ $fase }}</td>
    </tr>
    <tr>
        <td class="lbl">Semester</td>
        <td>{{ $smtLabel }}</td>
        <td class="lbl">Tahun Pelajaran</td>
        <td>{{ $tpLabel }}</td>
    </tr>
    <tr>
        <td class="lbl">Guru</td>
        <td>{{ $guruPilih !== '' ? $guruPilih : 'Semua guru (' . (int) $total_guru . ' guru)' }}</td>
        <td class="lbl">Mata Pelajaran</td>
        <td>{{ $mapelPilih !== '' ? $mapelPilih : 'Semua mata pelajaran (' . (int) $total_mapel . ' mapel)' }}</td>
    </tr>
    <tr>
        <td class="lbl">Periode</td>
        <td>{{ $namaBulan }} {{ (int) $tahun }}</td>
        <td class="lbl">Jumlah Pertemuan</td>
        <td>{{ (int) $total_pertemuan }} pertemuan</td>
    </tr>
</table>

<!-- Tabel jurnal -->
<table class="grid">
    <thead>
        <tr>
            <th width="26">No</th>
            <th width="82">Hari, Tanggal</th>
            <th width="38">Jam Ke</th>
            <th width="92">Mata Pelajaran</th>
            <th width="100">Guru</th>
            <th>Materi Pokok / Tujuan Pembelajaran</th>
            <th width="150">Kegiatan &amp; Model Pembelajaran</th>
            <th width="66">Asesmen</th>
            <th width="96">Siswa Tidak Hadir</th>
            <th width="86">Catatan / Refleksi</th>
            <th width="46">Paraf</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($jurnals)): ?>
        <tr>
            <td colspan="11" class="center muted" style="padding:14px;">Belum ada catatan jurnal mengajar untuk kelas ini pada periode terpilih.</td>
        </tr>
        <?php else: $no = 1; foreach ($jurnals as $j):
            $absenId = json_decode((string) ($j->siswa_absent ?? ''), true);
            $absenId = is_array($absenId)
                ? array_values(array_filter(array_map('intval', $absenId), static fn ($v) => $v > 0))
                : [];
            $namaAbsen = [];
            foreach (array_slice($absenId, 0, 3) as $sid) {
                $namaAbsen[] = $absen_map[$sid] ?? ('#' . $sid);
            }
            $sisaAbsen = count($absenId) - count($namaAbsen);
            $catatan = trim((string) ($j->catatan_khusus ?? ''));
            if ($catatan === '') {
                $catatan = trim((string) ($j->refleksi ?? ''));
            }
            if ($catatan === '') {
                $catatan = trim((string) ($j->tindak_lanjut ?? ''));
            }
        ?>
        <tr>
            <td class="center">{{ $no++ }}</td>
            <td class="center">
                {{ $hariNama[(int) date('N', strtotime($j->tanggal))] ?? '' }},<br>
                {{ date('d/m/Y', strtotime($j->tanggal)) }}
            </td>
            <td class="center">{{ $j->jam_ke ?: '-' }}</td>
            <td>{{ $j->nama_mapel ?? '-' }}</td>
            <td>{{ $j->nama_guru ?? '-' }}</td>
            <td>
                <strong>{!! nl2br(e((string) ($j->materi_pokok ?: '-'))) !!}</strong>
                <?php if (!empty($j->tujuan_pembelajaran)): ?>
                <div class="muted" style="margin-top:2px;">TP: {!! nl2br(e((string) $j->tujuan_pembelajaran)) !!}</div>
                <?php endif; ?>
            </td>
            <td>
                {!! nl2br(e((string) ($j->kegiatan_belajar ?: '-'))) !!}
                <?php if (!empty($j->model_belajar)): ?>
                <div class="muted" style="margin-top:2px;">Model: {{ $j->model_belajar }}</div>
                <?php endif; ?>
            </td>
            <td>{{ $j->jenis_asesmen ?: '-' }}</td>
            <td>
                <?php if (empty($absenId)): ?>
                <span class="badge badge-hadir">Lengkap</span>
                <?php else: ?>
                <span class="badge badge-absent">{{ count($absenId) }} siswa</span>
                <?php if (!empty($namaAbsen)): ?>
                <div class="muted" style="margin-top:2px;">
                    {{ implode(', ', $namaAbsen) }}{{ $sisaAbsen > 0 ? ' +' . $sisaAbsen . ' lainnya' : '' }}
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </td>
            <td>{!! nl2br(e($catatan !== '' ? $catatan : '-')) !!}</td>
            <td class="paraf"></td>
        </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

<p class="foot">
    Fase mengikuti kelas: X = Fase E, XI/XII = Fase F (Kurikulum Merdeka). Kolom paraf diisi guru mata pelajaran
    setelah jurnal diverifikasi. Dicetak dari sistem {{ $setting->nama_aplikasi ?? $namaSekolah }} pada {{ $tanggalCetak }}.
</p>

<!-- Tanda Tangan -->
<table class="sign">
    <tr>
        <td width="50%" align="center">
            Mengetahui,<br>Kepala Sekolah
            <div class="space">
                <?php if ($ttdBase64): ?>
                <img src="{{ $ttdBase64 }}" style="max-height:54px; max-width:170px;">
                <?php endif; ?>
            </div>
            <span class="bold">{{ $namaKepsek ?: '( _______________________ )' }}</span><br>
            NIP. {{ $nipKepsek ?: '-' }}
        </td>
        <td width="50%" align="center">
            <?php if (!empty($setting->tempat)): ?>{{ $setting->tempat }}, <?php endif; ?>{{ $tanggalCetak }}<br>
            {{ $guruPilih !== '' ? 'Guru Mata Pelajaran' : 'Wali Kelas' }}
            <div class="space"></div>
            <span class="bold">
                {{ $guruPilih !== '' ? $guruPilih : ($namaWali !== '' ? $namaWali : '( _______________________ )') }}
            </span><br>
            NIP. {{ $guruPilih !== '' ? ($guruPilihNip !== '' ? $guruPilihNip : '-') : '..........................' }}
        </td>
    </tr>
</table>

</body>
</html>
