<?php
// Partial kartu guru / GTK landscape (92 × 58 mm).
// Dipakai bersama oleh tata letak grid A4 dan mode cetak langsung ukuran KTP.
//
// Badge kop memakai status pendek (PNS / PPPK / HONOR). Status lengkap seperti
// "TENAGA HONOR SEKOLAH" tidak muat di kop 92 mm dan dulu menghimpit nama
// sekolah sampai terpotong; status penuh tetap tercetak di sisi belakang.
$statusRaw = strtoupper(trim((string) ($g->status_pegawai ?? '')));
$statusPendek = 'GTK';
if (str_contains($statusRaw, 'PPPK')) {
    $statusPendek = 'PPPK';
} elseif (str_contains($statusRaw, 'PNS')) {
    $statusPendek = 'PNS';
} elseif (str_contains($statusRaw, 'HONOR')) {
    $statusPendek = 'HONOR';
} elseif ($statusRaw !== '') {
    $statusPendek = mb_substr($statusRaw, 0, 10);
}
?>
<div class="id-card">
    <!-- Kop Kartu -->
    <div class="card-header-bar">
        <div class="card-logo">
            <img src="{{ $logo_url }}" alt="Logo" onerror="this.src='{{ asset('assets/img/logo.png') }}'">
        </div>
        <div class="card-header-text">
            <p class="app-title">KARTU PRESENSI DIGITAL GURU / GTK</p>
            <h4 class="school-title">{{ $setting->sekolah ?? 'SEKOLAH' }}</h4>
        </div>
        <div class="card-header-badge" title="{{ $g->status_pegawai ?: 'GTK' }}">
            {{ $statusPendek }}
        </div>
    </div>

    <!-- Isi Kartu -->
    <div class="card-content">
        <!-- Foto -->
        <div class="card-photo-box">
            <img src="{{ $g->foto_url }}" alt="{{ $g->nama_guru }}"
                 onerror="this.onerror=null;this.src='{{ \App\Services\AvatarService::inisial($g->nama_guru, 128, '#1e56c8') }}'">
        </div>

        <!-- Identitas -->
        <div class="card-details">
            <h5 class="card-name" title="{{ $g->nama_guru }}">{{ $g->nama_guru }}</h5>
            <table class="card-table">
                <tr>
                    <td class="label">NIP</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $g->nip ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="label">NUPTK</td>
                    <td class="sep">:</td>
                    <td class="val">{{ $g->nuptk ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="label">TUGAS</td>
                    <td class="sep">:</td>
                    <td class="val" style="font-family:inherit;font-weight:700;color:#1e56c8;">{{ $g->jabatan }}</td>
                </tr>
            </table>
        </div>

        <!-- QR Code -->
        <div class="card-qr-box">
            <div class="card-qr-render qr-box" data-code="{{ $g->qr_value }}" data-size="64"></div>
            <div class="card-qr-code">{{ $g->qr_value }}</div>
            <div class="card-qr-label">Scan Absen</div>
        </div>
    </div>

    <!-- Footer Kartu -->
    <div class="card-footer-bar">
        <span class="badge-instansi">{{ $setting->sekolah ?? 'Sekolah' }}</span>
        <span>GTK PRESENSI MANDIRI</span>
    </div>
</div>
