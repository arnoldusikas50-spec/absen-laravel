<?php
// Partial sisi DEPAN kartu siswa portrait (CR80 54×85.6mm).
// Desain mengikuti kartu pelajar resmi: kop tengah (logo + dinas + sekolah +
// alamat), latar biru muda bergaris lengkung, foto merah berbingkai putih,
// banner nama biru, data NIS/Kelas, QR + "SCAN UNTUK INFORMASI", dan ombak
// biru-emas kanan bawah.
//
// Template latar: $template === 'png' memakai latar PNG hasil unggahan
// /settings (kolom setting.kartu_bg_depan) — semua hiasan vektor/kop
// disembunyikan, hanya data dinamis (foto, nama, data, QR) yang tetap.
// Bila salah satu latar belum diunggah, controller sudah memaksa 'vektor'.
$kartuTemplate = ($template ?? 'vektor') === 'png' ? 'png' : 'vektor';
$alamatSekolah = trim(implode(', ', array_filter([
    $setting->alamat ?? '',
    $setting->kecamatan ?? '',
    $setting->kode_pos ?? ($setting->kota ?? ''),
])));
?>
<div class="card-dp card-dp-front<?php echo $kartuTemplate === 'png' ? ' card-dp-tpl-png' : ''; ?>">
    @if ($kartuTemplate === 'png')
        <img class="dp-bg-img" src="{{ $kartu_bg_depan }}" alt="Latar kartu depan">
    @else
    <!-- Latar: garis lengkung halus -->
    <svg class="dp-streaks" viewBox="0 0 204 324" preserveAspectRatio="none" aria-hidden="true">
        <path d="M-30,12 C40,-8 120,54 240,14" stroke="#cfe3f8" stroke-width="26" fill="none" opacity="0.55"/>
        <path d="M-30,34 C48,14 128,74 240,36" stroke="#ddebfa" stroke-width="18" fill="none" opacity="0.5"/>
        <path d="M-24,258 C42,238 128,272 236,232" stroke="#cfe3f8" stroke-width="13" fill="none" opacity="0.55"/>
        <path d="M-24,296 C52,276 138,304 236,268" stroke="#ddebfa" stroke-width="19" fill="none" opacity="0.5"/>
    </svg>

    <!-- Kop: logo + dinas + nama sekolah + alamat (rata tengah) -->
    <div class="dp-kop">
        <div class="dp-kop-logo">
            <img src="{{ $logo_url }}" alt="Logo" onerror="this.src='{{ asset('assets/img/logo.png') }}'">
        </div>
        <p class="dp-kop-dinas">DINAS PENDIDIKAN</p>
        <h4 class="dp-kop-school" title="{{ $setting->sekolah ?? 'SEKOLAH' }}">{{ $setting->sekolah ?? 'SEKOLAH' }}</h4>
        <p class="dp-kop-address" title="{{ $alamatSekolah }}">{{ $alamatSekolah }}</p>
    </div>
    @endif

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

    <!-- QR Section (bingkai biru membulat + logo sekolah di tengah) -->
    <div class="dp-qr-section">
        <div class="dp-qr-card">
            <div class="dp-qr-wrap">
                <div class="dp-qr-box qr-print-box" data-code="{{ $s->qr_value }}" data-size="128"></div>
                <img class="dp-qr-logo" src="{{ $logo_url }}" alt="Logo" onerror="this.style.display='none'">
            </div>
        </div>
        <div class="dp-qr-divider"></div>
        <div class="dp-qr-desc">
            SCAN UNTUK<br>INFORMASI
        </div>
    </div>

    @if ($kartuTemplate !== 'png')
    <!-- Dekorasi: ombak biru + emas kanan bawah -->
    <svg class="dp-wave-bottom" viewBox="0 0 120 42" preserveAspectRatio="none" aria-hidden="true">
        <path d="M120,0 C88,14 52,28 0,42 L120,42 Z" fill="#1e56c8"/>
        <path d="M120,8 C92,20 56,32 20,42" fill="none" stroke="#f0b429" stroke-width="3"/>
        <path d="M120,16 C96,26 64,36 38,42" fill="none" stroke="#8ecbf5" stroke-width="2"/>
    </svg>
    @endif
</div>
