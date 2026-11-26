<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Presensi Siswa - {{ $kelasNama }} - {{ $tgl }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 0; padding: 12px; color: #111; }
        h2, p { margin: 0; padding: 0; }
        @include('partials.cetak.css-dokumen')
        table.grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.grid th, table.grid td { border: 1px solid #333; padding: 4px 5px; }
        table.grid th { background: #f0f0f0; font-size: 9px; text-transform: uppercase; }
        .right { text-align: right; }
        .center { text-align: center; }
        .summary td { border: 1px solid #333; padding: 4px 8px; font-weight: bold; }
    </style>
</head>
<body>
    <?php
    $tglFmt = date('d M Y', strtotime($tgl));
    $statusLabel = ['HADIR' => 'Hadir', 'SAKIT' => 'Sakit', 'IZIN' => 'Izin', 'ALPA' => 'Alpa', 'BDR' => 'BDR', 'TANPA_KETERANGAN' => 'Belum'];
    ?>

    @include('partials.cetak.kop', [
        'kopBase64' => null,
        'logoLebar' => 60,
        'logoKolomLebar' => 12,
    ])

    <div class="doc-title">Monitoring Presensi Siswa Harian</div>
    <div class="doc-sub">{{ $kelasNama }} • {{ $tglFmt }}</div>

    <table class="summary" width="100%">
        <tr>
            <td width="17%">Total: {{ (int) ($stat['total'] ?? 0) }} siswa</td>
            <td width="14%">Hadir: {{ (int) ($stat['hadir'] ?? 0) }}</td>
            <td width="14%">Sakit: {{ (int) ($stat['sakit'] ?? 0) }}</td>
            <td width="14%">Izin: {{ (int) ($stat['izin'] ?? 0) }}</td>
            <td width="14%">Alpa: {{ (int) ($stat['alpa'] ?? 0) }}</td>
            <td width="14%">BDR: {{ (int) ($stat['bdr'] ?? 0) }}</td>
            <td width="14%">Belum: {{ (int) ($stat['belum'] ?? 0) }}</td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th>Nama Siswa</th>
                <th width="12%">NIS</th>
                <th width="10%">Status</th>
                <th width="9%">Masuk</th>
                <th width="9%">Keluar</th>
                <th width="22%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
                <tr><td colspan="7" class="center">Belum ada data siswa di kelas ini.</td></tr>
            <?php else: ?>
                <?php $no = 1; foreach ($data as $r):
                    $st = $r->status ?? 'TANPA_KETERANGAN';
                    $label = $statusLabel[$st] ?? 'Belum';
                ?>
                <tr>
                    <td class="center">{{ $no++ }}</td>
                    <td>{{ $r->nama ?? '-' }}</td>
                    <td class="center">{{ $r->nis ?? '-' }}</td>
                    <td class="center">{{ $label }}</td>
                    <td class="center">{!! !empty($r->jam_masuk) ? e(substr($r->jam_masuk, 0, 5)) : '-' !!}</td>
                    <td class="center">{!! !empty($r->jam_keluar)
                        ? e(substr($r->jam_keluar, 0, 5))
                        : ((int) ($r->pulang_tidak_tercatat ?? 0) === 1 ? 'tidak tercatat' : '-') !!}</td>
                    <td>{{ $r->keterangan ?? '-' }}</td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="sign">
        <tr>
            <td width="60%"></td>
            <td align="center">
                {{ $setting->kota ?? '' }}, {{ date('d M Y') }}<br>
                Wali Kelas,<br><br><br><br>
                <b>( ........................................ )</b>
            </td>
        </tr>
    </table>
</body>
</html>
