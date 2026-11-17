<?php
// Partial sisi DEPAN kartu guru / GTK portrait (58 × 92 mm).
// Desain mengikuti contoh resmi Dinas Pendidikan: latar biru muda dengan
// lingkaran dekoratif kiri atas & ombak biru bawah, kop logo + identitas
// sekolah, pil "ID CARD GURU", foto merah berbingkai biru, nama bergaris
// bawah, dan NIP di bawahnya.
$alamatSekolah = trim(implode(', ', array_filter([
    $setting->alamat ?? '',
    $setting->kecamatan ?? '',
    $setting->kode_pos ?? ($setting->kota ?? ''),
])));
$nipGuru = trim((string) ($g->nip ?: ($g->kode_guru ?: '-')));
?>
<div class="id-card-portrait id-card-guru-front">
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

    <!-- Pil judul kartu dengan garis di kedua sisi -->
    <div class="gpill-row">
        <span class="gpill-line"></span>
        <span class="gpill">ID CARD GURU</span>
        <span class="gpill-line"></span>
    </div>

    <!-- Foto Guru (latar merah pas foto, bingkai biru) -->
    <div class="gfront-photo">
        <img src="{{ $g->foto_url }}" alt="{{ $g->nama_guru }}"
             onerror="this.onerror=null;this.src='{{ \App\Services\AvatarService::inisial($g->nama_guru, 256, '#1e3a8a') }}'">
    </div>

    <!-- Nama & NIP -->
    <div class="gfront-identity">
        <h5 class="gfront-name" title="{{ $g->nama_guru }}">{{ $g->nama_guru }}</h5>
        <div class="gfront-name-line"></div>
        <p class="gfront-nip">NIP. {{ $nipGuru }}</p>
    </div>
</div>
