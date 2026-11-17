<?php
// Partial sisi DEPAN kartu siswa landscape (CR80 85.6×54mm).
// Dipakai bersama oleh tata letak lembar berdampingan, grid bolak-balik,
// dan cetak langsung ukuran KTP agar desainnya selalu identik.
$isLaki = ($s->jenis_kelamin ?? 'L') === 'L';
$nisnClean = \App\Services\KartuSiswaFormatter::nomorSiswa($s);
$ttlClean = \App\Services\KartuSiswaFormatter::ttl($s);
?>
<div class="card-dl card-dl-front">
    <!-- Header Bar -->
    <div class="dl-header-bar">
        <div class="dl-logo-wrap">
            <img src="{{ $logo_url }}" alt="Logo" onerror="this.src='{{ asset('assets/img/logo.png') }}'">
        </div>
        <div class="dl-header-titles">
            <h4 class="dl-header-title">{{ $setting->sekolah ?? 'SMA NEGERI 2 AMFOANG TIMUR' }}</h4>
            <p class="dl-header-sub">KARTU PELAJAR • TP {{ $tp_active->tahun ?? '-' }}</p>
        </div>
        <div class="dl-class-pill">{{ $s->nama_kelas }}</div>
        <!-- Accent ombak tipis di bawah header -->
        <svg class="dl-header-accent" viewBox="0 0 324 6" preserveAspectRatio="none">
            <path d="M0,0 L324,0 L324,2 Q240,6 160,3 Q80,0 0,3 Z" fill="#f59e0b" />
            <path d="M0,3 Q80,0 160,3 Q240,6 324,2 L324,4 Q240,7 160,4 Q80,1 0,4 Z" fill="#38bdf8" />
        </svg>
    </div>

    <!-- Body: 3 Kolom Rapi (Foto Siswa, Identitas Tengah, QR Code Kanan) -->
    <div class="dl-front-body">
        <!-- Kolom 1: Foto Siswa -->
        <div class="dl-photo-col">
            <div class="dl-photo-box">
                <img src="{{ $s->foto_url }}" alt="{{ $s->nama }}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                <div class="dl-photo-placeholder" style="display:none;"><i class="bi bi-person-fill"></i></div>
            </div>
            <span class="dl-jk-tag {{ $isLaki ? 'L' : 'P' }}">{{ $isLaki ? 'LAKI-LAKI' : 'PEREMPUAN' }}</span>
        </div>

        <!-- Kolom 2: Identitas Tengah -->
        <div class="dl-info-col">
            <h4 class="dl-student-name" title="{{ $s->nama }}">{{ $s->nama }}</h4>
            <div class="dl-name-divider"></div>
            <table class="dl-info-table">
                <tr>
                    <td class="dl-lbl">NISN</td>
                    <td class="dl-sep">:</td>
                    <td class="dl-val">{{ $nisnClean }}</td>
                </tr>
                <tr>
                    <td class="dl-lbl">Kelas</td>
                    <td class="dl-sep">:</td>
                    <td class="dl-val">{{ $s->nama_kelas }}</td>
                </tr>
                <tr>
                    <td class="dl-lbl">JK</td>
                    <td class="dl-sep">:</td>
                    <td class="dl-val">{{ $isLaki ? 'Laki-laki' : 'Perempuan' }}</td>
                </tr>
                <tr>
                    <td class="dl-lbl">TTL</td>
                    <td class="dl-sep">:</td>
                    <td class="dl-val ttl-wrap" title="{{ $ttlClean }}">{{ $ttlClean }}</td>
                </tr>
            </table>
        </div>

        <!-- Kolom 3: QR Code Besar -->
        <div class="dl-qr-col">
            <div class="dl-qr-card">
                <div class="dl-qr-box qr-print-box" data-code="{{ $s->qr_value }}" data-size="70"></div>
            </div>
            <div class="dl-qr-badge">SCAN PRESENSI</div>
        </div>
    </div>
</div>
