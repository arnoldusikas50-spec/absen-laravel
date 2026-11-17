<?php
// Partial sisi DEPAN kartu siswa portrait (CR80 54×85.6mm).
// Desain mengikuti kartu pelajar resmi: header biru dengan logo + nama
// sekolah, ombak biru muda & garis emas di bawah header, latar biru muda
// bergaris lengkung, foto merah berbingkai putih, banner nama biru, data
// NIS/Kelas, QR + "SCAN UNTUK INFORMASI", dan ombak biru-emas kanan bawah.
?>
<div class="card-dp card-dp-front">
    <!-- Latar: garis lengkung halus (hanya di zona bawah, tidak menabrak foto & nama) -->
    <svg class="dp-streaks" viewBox="0 0 204 344" preserveAspectRatio="none" aria-hidden="true">
        <path d="M-24,258 C42,238 128,272 236,232" stroke="#cfe3f8" stroke-width="13" fill="none" opacity="0.55"/>
        <path d="M-24,296 C52,276 138,304 236,268" stroke="#ddebfa" stroke-width="19" fill="none" opacity="0.5"/>
    </svg>
    <!-- Dekorasi: ombak biru muda + garis emas di bawah header -->
    <svg class="dp-wave-top" viewBox="0 0 204 36" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0,0 L204,0 L204,6 C160,24 108,4 64,15 C38,22 16,24 0,30 Z" fill="#8ecbf5"/>
        <path d="M0,0 L204,0 L204,2 C156,15 100,0 56,9 C30,14 12,16 0,20" fill="none" stroke="#f0b429" stroke-width="3"/>
    </svg>

    <!-- Kop: Logo + Nama Sekolah -->
    <div class="dp-header">
        <div class="dp-logo-wrap">
            <img src="{{ $logo_url }}" alt="Logo" onerror="this.src='{{ asset('assets/img/logo.png') }}'">
        </div>
        <h4 class="dp-header-title">{{ $setting->sekolah ?? 'SEKOLAH' }}</h4>
    </div>

    <!-- Foto Siswa (latar merah pas foto, bingkai putih) -->
    <div class="dp-photo-wrap">
        <div class="dp-photo-box">
            <img src="{{ $s->foto_url }}" alt="{{ $s->nama }}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div class="dl-photo-placeholder" style="display:none;"><i class="bi bi-person-fill"></i></div>
        </div>
    </div>

    <!-- Banner Nama -->
    <div class="dp-name-badge" title="{{ $s->nama }}">{{ $s->nama }}</div>

    <!-- Data Ringkas -->
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

    <!-- QR Section -->
    <div class="dp-qr-section">
        <div class="dp-qr-card">
            <div class="dp-qr-box qr-print-box" data-code="{{ $s->qr_value }}" data-size="64"></div>
        </div>
        <div class="dp-qr-divider"></div>
        <div class="dp-qr-desc">
            SCAN UNTUK<br>INFORMASI
        </div>
    </div>

    <!-- Dekorasi: ombak biru + emas kanan bawah -->
    <svg class="dp-wave-bottom" viewBox="0 0 120 42" preserveAspectRatio="none" aria-hidden="true">
        <path d="M120,0 C88,14 52,28 0,42 L120,42 Z" fill="#1e56c8"/>
        <path d="M120,8 C92,20 56,32 20,42" fill="none" stroke="#f0b429" stroke-width="3"/>
        <path d="M120,16 C96,26 64,36 38,42" fill="none" stroke="#8ecbf5" stroke-width="2"/>
    </svg>
</div>
