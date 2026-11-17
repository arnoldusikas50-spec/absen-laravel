<?php
// Partial sisi DEPAN kartu siswa portrait (CR80 54×85.6mm).
$isLaki = ($s->jenis_kelamin ?? 'L') === 'L';
?>
<div class="card-dp card-dp-front">
    <div class="dp-header">
        <div class="dp-logo-wrap">
            <img src="{{ $logo_url }}" alt="Logo" onerror="this.src='{{ asset('assets/img/logo.png') }}'">
        </div>
        <div class="dp-header-titles">
            <h4 class="dp-header-title">{{ $setting->sekolah ?? 'SMAN 2 AMFOANG TIMUR' }}</h4>
            <p class="dp-header-sub">KARTU PELAJAR • TP {{ $tp_active->tahun ?? '-' }}</p>
        </div>
        <!-- Accent ombak di bawah header -->
        <svg class="dp-header-accent" viewBox="0 0 204 6" preserveAspectRatio="none">
            <path d="M0,0 L204,0 L204,2 Q150,6 102,3 Q50,0 0,3 Z" fill="#f59e0b" />
            <path d="M0,3 Q50,0 102,3 Q150,6 204,2 L204,4 Q150,7 102,4 Q50,1 0,4 Z" fill="#38bdf8" />
        </svg>
    </div>

    <!-- Foto Siswa -->
    <div class="dp-photo-wrap">
        <div class="dp-photo-box">
            <img src="{{ $s->foto_url }}" alt="{{ $s->nama }}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div class="dl-photo-placeholder" style="display:none;"><i class="bi bi-person-fill"></i></div>
        </div>
    </div>

    <!-- Nama Siswa Pill -->
    <div class="dp-name-badge" title="{{ $s->nama }}">{{ $s->nama }}</div>

    <!-- Metadata Table -->
    <table class="dp-meta-table">
        <tr>
            <td class="dp-meta-lbl">NIS/NISN</td>
            <td class="dp-meta-sep">:</td>
            <td class="dp-meta-val">{{ ($s->nis ? $s->nis . ' / ' : '') . ($s->nisn ?: '-') }}</td>
        </tr>
        <tr>
            <td class="dp-meta-lbl">Kelas</td>
            <td class="dp-meta-sep">:</td>
            <td class="dp-meta-val">{{ $s->nama_kelas }}</td>
        </tr>
    </table>

    <!-- QR Section Bottom -->
    <div class="dp-qr-section">
        <div class="dp-qr-card">
            <div class="dp-qr-box qr-print-box" data-code="{{ $s->qr_value }}" data-size="64"></div>
        </div>
        <div class="dp-qr-divider"></div>
        <div class="dp-qr-desc">
            SCAN UNTUK<br>PRESENSI
        </div>
    </div>

    <!-- Bottom Right Accent Wave -->
    <svg class="dp-front-wave-bottom" viewBox="0 0 86 38" preserveAspectRatio="none">
        <path d="M86,0 C60,12 25,26 0,38 L86,38 Z" fill="#09265e" />
        <path d="M86,5 C64,15 32,29 12,38" fill="none" stroke="#f59e0b" stroke-width="2" />
        <path d="M86,9 C67,18 40,32 22,38" fill="none" stroke="#38bdf8" stroke-width="1.2" />
    </svg>
</div>
