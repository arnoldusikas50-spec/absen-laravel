<?php
// Partial sisi BELAKANG kartu guru / GTK landscape (92 × 58 mm).
// Pasangan sisi depan kartu_guru_landscape untuk cetak 2 sisi (depan + belakang).
$ttlGuru = trim(($g->tempat_lahir ?? '') . ($g->tgl_lahir ? ', ' . date('d-m-Y', strtotime($g->tgl_lahir)) : ''));
$nipGuru = $g->nip ?: ($g->kode_guru ?: '-');
$pendidikanGuru = trim((string) ($g->pendidikan ?? ''));
$jabatanGuru = ! empty($g->tgs_tambahan) ? $g->tgs_tambahan : (! empty($g->jenis_ptk) ? $g->jenis_ptk : 'Guru');
$statusGuru = $g->status_pegawai ?: 'Pendidik';
?>
<div class="id-card">
    <!-- Header Sisi Belakang -->
    <div class="card-header-bar-back">
        <div class="card-back-title">DATA GURU / GTK</div>
        <div class="card-back-side">Sisi Belakang</div>
    </div>

    <!-- Isi Kartu -->
    <div class="card-content-back">
        <!-- Kolom kiri: identitas guru -->
        <div class="card-back-data">
            <div class="card-back-sub">Identitas Guru</div>
            <table class="card-back-table">
                <tr>
                    <td class="lbl">Nama</td>
                    <td class="sep">:</td>
                    <td class="val" title="{{ $g->nama_guru }}">{{ $g->nama_guru }}</td>
                </tr>
                <tr>
                    <td class="lbl">NIPK</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $nipGuru }}</td>
                </tr>
                <tr>
                    <td class="lbl">TTL</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $ttlGuru ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Pendidikan</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $pendidikanGuru ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Jabatan</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $jabatanGuru }}</td>
                </tr>
                <tr>
                    <td class="lbl">Status</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $statusGuru }}</td>
                </tr>
            </table>
        </div>

        <!-- Kolom kanan: QR Code absensi -->
        <div class="card-back-qr">
            <div class="card-back-qr-box qr-box" data-code="{{ $g->qr_value }}" data-size="56"></div>
            <div class="card-back-qr-label">SCAN ABSEN</div>
        </div>
    </div>

    <!-- Footer Kartu -->
    <div class="card-footer-bar-back">
        <span class="badge-instansi">{{ $setting->sekolah ?? 'Sekolah' }}</span>
        <span>GTK PRESENSI MANDIRI</span>
    </div>
</div>
