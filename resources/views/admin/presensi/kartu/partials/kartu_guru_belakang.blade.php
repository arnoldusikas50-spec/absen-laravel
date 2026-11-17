<?php
// Partial sisi BELAKANG kartu guru / GTK portrait (58 × 92 mm).
// Desain mengikuti contoh resmi: kop yang sama dengan sisi depan, garis ganda
// biru, pil "KARTU IDENTITAS GURU", tabel data guru (Nama, NIP, TTL,
// Pendidikan), QR code berbingkai biru, dan motto sekolah.
$alamatSekolah = trim(implode(', ', array_filter([
    $setting->alamat ?? '',
    $setting->kecamatan ?? '',
    $setting->kode_pos ?? ($setting->kota ?? ''),
])));
$nipGuru = trim((string) ($g->nip ?: ($g->kode_guru ?: '-')));
$ttlGuru = trim(($g->tempat_lahir ?? '') . ($g->tgl_lahir ? ', ' . date('d-m-Y', strtotime($g->tgl_lahir)) : ''));
$pendidikanGuru = trim((string) ($g->pendidikan ?? ''));
?>
<div class="id-card-portrait id-card-guru-back">
    <!-- Dekorasi: lingkaran biru kiri atas -->
    <svg class="gdeco-circle" viewBox="0 0 200 200" aria-hidden="true">
        <circle cx="70" cy="70" r="80" fill="#cfe3f7" opacity="0.9"/>
        <circle cx="70" cy="70" r="96" fill="none" stroke="#9cc4ec" stroke-width="7" opacity="0.8"/>
        <circle cx="70" cy="70" r="114" fill="none" stroke="#c3ddf5" stroke-width="4" opacity="0.6"/>
    </svg>
    <!-- Dekorasi: ombak biru bawah -->
    <svg class="gdeco-wave" viewBox="0 0 232 40" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 40 L0 26 C30 12, 58 26, 92 16 C126 6, 158 20, 196 10 C210 7, 222 10, 232 14 L232 40 Z" fill="#1e56c8"/>
        <path d="M0 40 L0 33 C34 22, 64 34, 100 25 C134 17, 166 28, 200 20 C214 17, 224 19, 232 22 L232 40 Z" fill="#3f8ef7" opacity="0.9"/>
    </svg>

    <!-- Kop: Logo + Identitas Sekolah -->
    <div class="gkop-header">
        <div class="gkop-logo">
            <img src="{{ $logo_url }}" alt="Logo" onerror="this.src='{{ asset('assets/img/logo.png') }}'">
        </div>
        <p class="gkop-dinas">DINAS PENDIDIKAN</p>
        <h4 class="gkop-school">{{ $setting->sekolah ?? 'SEKOLAH' }}</h4>
        <p class="gkop-address">{{ $alamatSekolah ?: ($setting->kota ?? '') }}</p>
    </div>

    <!-- Garis ganda biru -->
    <div class="gback-divider"><span></span><span></span></div>

    <!-- Pil judul sisi belakang -->
    <div class="gpill-row">
        <span class="gpill-line"></span>
        <span class="gpill">KARTU IDENTITAS GURU</span>
        <span class="gpill-line"></span>
    </div>

    <!-- Data Guru -->
    <table class="gback-table">
        <tr>
            <td class="lbl">Nama</td>
            <td class="sep">:</td>
            <td class="val" title="{{ $g->nama_guru }}">{{ $g->nama_guru }}</td>
        </tr>
        <tr>
            <td class="lbl">NIP</td>
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
    </table>

    <!-- QR Code absensi -->
    <div class="gback-qr">
        <div class="gback-qr-box qr-box" data-code="{{ $g->qr_value }}" data-size="100"></div>
    </div>

    <!-- Motto -->
    <p class="gback-motto">Berkarakter, Berprestasi,<br>Untuk Negeri</p>
</div>
