@extends('layouts.main')

@section('styles')
<style>
    .card-preview {
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .card-preview:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 28px -6px rgb(var(--primary-700-rgb, 30 64 175) / 0.15), 0 4px 10px -2px rgba(15, 23, 42, 0.06);
    }
    /* Panggung pratinjau: kertas netral supaya tepi putih kartu tetap terlihat. */
    .card-stage {
        background: #f8fafc;
        border: 1px solid #e8edf5;
        border-radius: 12px;
        padding: 10px 10px 12px 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    .card-stage-label {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #1d4ed8;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 999px;
        padding: 2px 9px;
        line-height: 1.4;
    }
    .qr-box canvas, .qr-box img {
        margin: 0 auto;
        display: block;
        width: 100% !important;
        height: 100% !important;
        max-width: 100% !important;
        max-height: 100% !important;
    }

    /* ═══════════════════════════════════════════════════════════════════
       KARTU GURU PORTRAIT — Base class (dipakai depan & belakang)
    ═══════════════════════════════════════════════════════════════════ */
    .id-card-portrait {
        width: 240px;
        height: 380px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: linear-gradient(165deg, #f5fbff 0%, #e8f2fc 55%, #d8eafa 100%);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        position: relative;
        box-shadow: 0 10px 24px -14px rgba(15, 23, 42, 0.35);
    }

    /* ═══════════════════════════════════════════════════════════════════
       KARTU GURU BELAKANG (preview)
    ═══════════════════════════════════════════════════════════════════ */
    .id-card-guru-back {
        padding: 4px 14px 36px 14px;
    }
    /* Dekorasi bersama dua sisi: lingkaran biru kiri atas & ombak bawah */
    .gdeco-circle {
        position: absolute;
        top: -62px;
        left: -70px;
        width: 190px;
        height: 190px;
        z-index: 0;
        pointer-events: none;
    }
    .gdeco-wave {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 34px;
        z-index: 0;
        pointer-events: none;
    }
    /* Kop bersama dua sisi: logo + dinas + sekolah + alamat */
    .gkop-header {
        position: relative;
        z-index: 1;
        text-align: center;
        flex-shrink: 0;
    }
    .gkop-logo {
        width: 46px;
        height: 46px;
        margin: 0 auto 3px auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .gkop-logo img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    .gkop-dinas {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.4px;
        color: #16325c;
        margin: 0;
        line-height: 1.1;
    }
    .gkop-school {
        font-size: 10.5px;
        font-weight: 900;
        color: #16325c;
        margin: 2px 0 0 0;
        line-height: 1.15;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: clip;
        width: 100%;
        display: block;
        letter-spacing: -0.3px;
    }
    .gkop-address {
        font-size: 8px;
        font-weight: 700;
        color: #1f3c66;
        margin: 3px 0 0 0;
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: clip;
        width: 100%;
        display: block;
        letter-spacing: -0.2px;
    }
    /* Pil judul kartu dengan garis di kedua sisi */
    .gpill-row {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 6px;
        width: 100%;
        margin-top: 5px;
    }
    .gpill-line {
        flex: 1;
        height: 3px;
        background: #1e56c8;
        border-radius: 3px;
    }
    .gpill {
        background: #1e56c8;
        color: #ffffff;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.5px;
        padding: 4px 13px;
        border-radius: 7px;
        white-space: nowrap;
        line-height: 1;
        flex-shrink: 0;
    }
    /* Garis ganda biru di bawah kop sisi belakang */
    .gback-divider {
        position: relative;
        z-index: 1;
        width: 100%;
        margin-top: 2px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .gback-divider span {
        display: block;
        height: 2.5px;
        background: #1e56c8;
        border-radius: 2px;
    }
    .gback-table {
        position: relative;
        z-index: 1;
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 10px;
        line-height: 1.3;
        margin-top: 2px;
    }
    .gback-table td {
        padding: 1px 0;
        vertical-align: top;
    }
    .gback-table .lbl {
        width: 64px;
        font-weight: 800;
        color: #16325c;
        white-space: nowrap;
    }
    .gback-table .sep {
        width: 12px;
        text-align: center;
        font-weight: 800;
        color: #16325c;
    }
    .gback-table .val {
        padding-left: 2px;
        font-weight: 800;
        color: #16325c;
        word-break: break-word;
    }
    .gback-qr {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: center;
        margin-top: 2px;
    }
    .gback-qr-wrap {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .gback-qr-logo {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 34px;
        height: 34px;
        padding: 2px;
        background: #ffffff;
        border-radius: 5px;
        object-fit: contain;
    }
    .gback-qr-box {
        width: 112px;
        height: 112px;
        background: #ffffff;
        border: 3px solid #2f80ed;
        border-radius: 10px;
        padding: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .gback-qr-box canvas,
    .gback-qr-box img {
        max-width: 100% !important;
        max-height: 100% !important;
        display: block;
        margin: 0 auto;
    }
    .gback-motto {
        position: relative;
        z-index: 1;
        font-family: "Segoe Script", "Brush Script MT", "Comic Sans MS", cursive;
        font-style: italic;
        font-weight: 700;
        font-size: 9px;
        color: #1e56c8;
        text-align: center;
        line-height: 1.25;
        margin: 1px 0 0 0;
    }

    /* ═══════════════════════════════════════════════════════════════════
       KARTU GURU DEPAN (preview, disesuaikan skala layar)
    ═══════════════════════════════════════════════════════════════════ */
    .id-card-guru-front {
        align-items: center;
        padding: 10px 12px 34px 12px;
    }
    /* Sisi depan: foto merah berbingkai biru + nama bergaris bawah + NIP */
    .gfront-photo {
        position: relative;
        z-index: 1;
        width: 104px;
        height: 124px;
        margin: 8px auto 0 auto;
        background: #d92b2b;
        border: 3px solid #2f7dd1;
        border-radius: 9px;
        overflow: hidden;
        flex-shrink: 0;
    }
    .gfront-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .gfront-identity {
        position: relative;
        z-index: 1;
        text-align: center;
        margin-top: 8px;
        width: 100%;
    }
    .gfront-name {
        font-size: 12.5px;
        font-weight: 900;
        color: #16325c;
        line-height: 1.15;
        margin: 0;
        text-transform: uppercase;
        word-break: break-word;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .gfront-name-line {
        height: 2.5px;
        background: #16325c;
        border-radius: 2px;
        width: 168px;
        margin: 5px auto 0 auto;
    }
    .gfront-nip {
        font-size: 10px;
        font-weight: 800;
        color: #16325c;
        margin: 4px 0 0 0;
        letter-spacing: 0.2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ═══════════════════════════════════════════════════════════════════
       KARTU GURU LANDSCAPE — CSS identik dengan halaman cetak (92 × 58 mm)
       Dipakai oleh partial kartu_guru_landscape agar pratinjau 100% sama
       dengan hasil cetak.
    ═══════════════════════════════════════════════════════════════════ */
    .id-card {
        width: 92mm;
        height: 58mm;
        border: 1px dashed #94a3b8;
        border-radius: 8px;
        background: #ffffff;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        position: relative;
    }
    .card-header-bar {
        background: linear-gradient(135deg, #0b3d91 0%, #1450b4 55%, #1e56c8 100%);
        color: #ffffff;
        padding: 2mm 3mm;
        display: flex;
        align-items: center;
        gap: 2.5mm;
        border-bottom: 2px solid #f0b429;
        flex-shrink: 0;
    }
    .card-logo {
        width: 8mm;
        height: 8mm;
        background: #ffffff;
        border-radius: 5px;
        padding: 1.5px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    .card-logo img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    .card-header-text {
        flex: 1;
        min-width: 0;
    }
    .card-header-text .app-title {
        font-size: 5.2pt;
        font-weight: 700;
        letter-spacing: 0.45px;
        color: #8ecbf5;
        text-transform: uppercase;
        margin: 0;
        line-height: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .card-header-text .school-title {
        font-size: 8pt;
        font-weight: 800;
        color: #ffffff;
        margin: 0.5mm 0 0 0;
        line-height: 1.15;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .card-header-badge {
        background: rgba(255,255,255,0.22);
        border: 1px solid rgba(255,255,255,0.4);
        color: #ffffff;
        font-size: 5.6pt;
        font-weight: 800;
        padding: 0.5mm 1.8mm;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        flex-shrink: 0;
        max-width: 18mm;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .card-content {
        flex: 1;
        padding: 2.5mm 3mm;
        display: flex;
        align-items: center;
        gap: 3mm;
        background: #ffffff;
    }
    .card-photo-box {
        width: 18mm;
        height: 23mm;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        overflow: hidden;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .card-photo-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .card-details {
        flex: 1;
        min-width: 0;
    }
    .card-name {
        font-size: 8.5pt;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.15;
        margin: 0 0 1.5mm 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .card-table {
        font-size: 6.5pt;
        border-collapse: collapse;
        width: 100%;
    }
    .card-table td {
        padding: 0.4mm 0;
        vertical-align: top;
    }
    .card-table .label {
        color: #64748b;
        font-weight: 700;
        width: 14mm;
    }
    .card-table .sep {
        color: #94a3b8;
        width: 2mm;
        text-align: center;
    }
    .card-table .val {
        color: #0f172a;
        font-weight: 600;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
    .card-qr-box {
        width: 21mm;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-left: 1px solid #e2e8f0;
        padding-left: 2.5mm;
        flex-shrink: 0;
    }
    .card-qr-wrap {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .card-qr-logo {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 6mm;
        height: 6mm;
        padding: 0.4mm;
        background: #ffffff;
        border-radius: 0.8mm;
        object-fit: contain;
    }
    .card-qr-render {
        width: 18mm;
        height: 18mm;
        border: 0.5mm solid #2f80ed;
        border-radius: 1.6mm;
        padding: 0.6mm;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .card-qr-render canvas, .card-qr-render img {
        max-width: 100% !important;
        max-height: 100% !important;
        display: block;
        margin: 0 auto;
    }
    .card-qr-code {
        font-size: 5pt;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 700;
        color: #0f172a;
        margin-top: 1mm;
        line-height: 1.1;
        max-width: 22mm;
        word-break: break-all;
        letter-spacing: -0.2px;
        text-align: center;
    }
    .card-qr-label {
        font-size: 4.8pt;
        font-weight: 700;
        color: #1e56c8;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-top: 0.4mm;
        line-height: 1;
    }
    .card-footer-bar {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 1mm 3mm;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 5.5pt;
        color: #1e56c8;
        flex-shrink: 0;
    }
    .card-footer-bar .badge-instansi {
        font-weight: 700;
        color: #0f3593;
    }

    /* ── Kartu Guru Landscape — SISI BELAKANG ──────────────────────
       Identik dengan CSS halaman cetak guru agar pratinjau = hasil cetak. */
    .card-header-bar-back {
        background: linear-gradient(135deg, #0b3d91 0%, #1450b4 55%, #1e56c8 100%);
        color: #ffffff;
        padding: 2mm 3mm;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2.5mm;
        border-bottom: 2px solid #f0b429;
        flex-shrink: 0;
    }
    .card-back-title {
        font-size: 8pt;
        font-weight: 800;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .card-back-side {
        background: rgba(255,255,255,0.22);
        border: 1px solid rgba(255,255,255,0.4);
        font-size: 6pt;
        font-weight: 700;
        padding: 0.6mm 2mm;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .card-content-back {
        flex: 1;
        padding: 2.5mm 3mm;
        display: flex;
        align-items: stretch;
        gap: 3mm;
        background: #ffffff;
    }
    .card-back-data {
        flex: 1;
        min-width: 0;
    }
    .card-back-sub {
        font-size: 6.5pt;
        font-weight: 800;
        color: #0f3593;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0 0 1mm 0;
    }
    .card-back-table {
        font-size: 6.5pt;
        border-collapse: collapse;
        width: 100%;
    }
    .card-back-table td {
        padding: 0.4mm 0;
        vertical-align: top;
    }
    .card-back-table .lbl {
        width: 14mm;
        color: #64748b;
        font-weight: 700;
        white-space: nowrap;
    }
    .card-back-table .sep {
        width: 2mm;
        color: #94a3b8;
        text-align: center;
    }
    .card-back-table .val {
        color: #0f172a;
        font-weight: 600;
        word-break: break-word;
    }
    .card-back-qr {
        width: 21mm;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-left: 1px solid #e2e8f0;
        padding-left: 2.5mm;
        flex-shrink: 0;
    }
    .card-back-qr-wrap {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .card-back-qr-logo {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 6mm;
        height: 6mm;
        padding: 0.4mm;
        background: #ffffff;
        border-radius: 0.8mm;
        object-fit: contain;
    }
    .card-back-qr-box {
        width: 18mm;
        height: 18mm;
        border: 0.5mm solid #2f80ed;
        border-radius: 1.6mm;
        padding: 0.6mm;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .card-back-qr-box canvas, .card-back-qr-box img {
        width: 100% !important;
        height: 100% !important;
        display: block;
        margin: 0 auto;
    }
    .card-back-qr-label {
        font-size: 4.8pt;
        font-weight: 700;
        color: #1e56c8;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-top: 1mm;
        line-height: 1;
    }
    .card-footer-bar-back {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 1mm 3mm;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 5.5pt;
        color: #1e56c8;
        flex-shrink: 0;
    }
    .card-footer-bar-back .badge-instansi {
        font-weight: 700;
        color: #0f3593;
    }
</style>
@endsection

@section('content')
<?php
    // Guru memakai halaman ini untuk kartunya sendiri; admin mengelola semua kartu.
    $basePath = $base_path ?? 'admin/presensi/guru/kartu';
    $bisaSetelan = str_starts_with((string) $basePath, 'admin');
    $desainAktif = $desain ?? 'landscape';
    $modeAktif = in_array(($mode ?? 'grid'), ['grid', 'ktp'], true) ? ($mode ?? 'grid') : 'grid';
    // Bawa serta mode cetak saat berganti desain agar pilihannya tidak ter-reset.
    $qsSertaan = '&mode=' . $modeAktif;
    // URL reset filter: kembali ke semua guru dengan desain & tata letak yang aktif.
    $resetUrl = url($basePath . '?desain=' . $desainAktif . '&mode=' . $modeAktif);
    // Kelas tab pilihan (desain / tata letak) supaya markup tidak berulang.
    $tabClass = fn (bool $aktif) => 'inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-colors no-underline '
        . ($aktif
            ? 'bg-white text-blue-700 shadow-xs border border-slate-200'
            : 'text-slate-500 hover:text-slate-700 hover:bg-white/70');
?>
<div class="fade-in-up space-y-5 px-4 pb-6 pt-4 md:px-6 md:pt-6"
     x-data="kartuGuruPage({ base: @js(url($basePath)), desain: @js($desainAktif), mode: @js($modeAktif), idGuru: @js((string) $id_guru) })">

    <div class="space-y-3">
        <x-ui.page-header
            :title="$bisaSetelan ? 'Cetak Kartu Absen Guru QR' : 'Kartu Absen Saya'"
            icon="bi-person-badge-fill"
            :subtitle="$bisaSetelan
                ? 'Cetak kartu ID presensi Guru & Tenaga Kependidikan dengan QR Code standar untuk scan absensi mandiri di scanner kiosk.'
                : 'Kartu ID presensi Anda dengan QR Code standar — tunjukkan saat absensi mandiri di scanner kiosk.'"
            :crumbs="['Presensi', 'Kartu Guru']">
            <x-slot:actions>
                <a :href="cetakUrl" target="_blank"
                   class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white no-underline shadow-sm shadow-blue-500/20 transition-colors hover:bg-blue-700">
                    <i class="bi bi-printer-fill"></i> Cetak Kartu
                </a>
                <a href="{{ $bisaSetelan ? url('scanner') : url('guru/presensi-siswa' . (auth()->id() ? '?h=' . \App\Http\Controllers\Guru\PresensiSiswaQrController::generateHash((int) auth()->id()) : '')) }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:border-slate-300 hover:bg-slate-50">
                    <i class="bi bi-camera-fill"></i> Buka Scanner QR
                </a>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-bold">
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-blue-100 bg-blue-50/70 px-2.5 py-1 text-blue-700">
                <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-blue-600 text-[9px] text-white">1</span> Pilih desain
            </span>
            <i class="bi bi-chevron-right text-slate-300"></i>
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-600">
                <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-400 text-[9px] text-white">2</span> {{ $bisaSetelan ? 'Pilih guru' : 'Kartu saya' }}
            </span>
            <i class="bi bi-chevron-right text-slate-300"></i>
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-600">
                <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-400 text-[9px] text-white">3</span> Cetak
            </span>
        </div>
    </div>

    <!-- Filter & Control Panel -->
    <x-ui.card title="Opsi Cetak Kartu" icon="bi-sliders"
              subtitle="Pilih desain kartu dan tata letak cetak sebelum mencetak."
              body-class="p-4 sm:p-5 space-y-5">

        <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-2">
            <!-- Pilihan Desain Kartu -->
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Desain Kartu</label>
                <div class="inline-flex flex-wrap gap-1 rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Pilihan desain kartu">
                    <a href="{{ url($basePath . '?' . ($id_guru ? 'id_guru=' . $id_guru . '&' : '') . 'desain=landscape' . $qsSertaan) }}"
                       aria-current="{{ $desainAktif === 'landscape' ? 'page' : 'false' }}"
                       title="Kartu horizontal 9,2 × 5,8 cm dengan foto, NIP, dan QR Code"
                       class="{{ $tabClass($desainAktif === 'landscape') }}">
                        <i class="bi bi-credit-card-2-front"></i> Landscape
                    </a>
                    <a href="{{ url($basePath . '?' . ($id_guru ? 'id_guru=' . $id_guru . '&' : '') . 'desain=portrait' . $qsSertaan) }}"
                       aria-current="{{ $desainAktif === 'portrait' ? 'page' : 'false' }}"
                       title="Kartu vertikal 5,8 × 9,2 cm dengan QR Code besar"
                       class="{{ $tabClass($desainAktif === 'portrait') }}">
                        <i class="bi bi-credit-card"></i> Portrait
                    </a>
                </div>
                <p class="mt-1.5 text-[11px] text-slate-400">
                    @if ($desainAktif === 'portrait')
                        <strong>Portrait:</strong> kartu ID resmi 5,8 × 9,2 cm — sisi depan foto & nama guru, sisi belakang data lengkap dan QR Code absensi. Dicetak 2 sisi (depan + belakang).
                    @else
                        <strong>Landscape:</strong> kartu horizontal resmi 9,2 × 5,8 cm — foto guru, NIP, jabatan, dan QR Code di samping kanan, sisi belakang data lengkap. Dicetak 2 sisi (depan + belakang).
                    @endif
                </p>
            </div>

            <!-- Tata Letak Cetak -->
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Tata Letak Cetak</label>
                <div class="inline-flex flex-wrap gap-1 rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Pilihan tata letak cetak">
                    <a href="{{ url($basePath . '?' . ($id_guru ? 'id_guru=' . $id_guru . '&' : '') . 'desain=' . $desainAktif . '&mode=grid') }}"
                       aria-current="{{ $modeAktif === 'grid' ? 'page' : 'false' }}"
                       title="Beberapa kartu per lembar A4 — hemat kertas"
                       class="{{ $tabClass($modeAktif === 'grid') }}">
                        <i class="bi bi-file-earmark-text"></i> Grid A4
                    </a>
                    <a href="{{ url($basePath . '?' . ($id_guru ? 'id_guru=' . $id_guru . '&' : '') . 'desain=' . $desainAktif . '&mode=ktp') }}"
                       aria-current="{{ $modeAktif === 'ktp' ? 'page' : 'false' }}"
                       title="Kertas disetel otomatis ke kartu ID-1 CR80 (85,6 × 54 mm)"
                       class="{{ $tabClass($modeAktif === 'ktp') }}">
                        <i class="bi bi-credit-card-2-front-fill"></i> Ukuran KTP
                    </a>
                </div>
                <p class="mt-1.5 text-[11px] text-slate-400">
                    @if ($modeAktif === 'ktp')
                        <strong>Ukuran KTP:</strong> kertas disetel otomatis ke kartu ID-1 CR80 (85,6 × 54 mm) sehingga bisa dicetak langsung ke blanko kartu / media Card Size pada printer Epson.
                    @else
                        <strong>Grid A4:</strong> beberapa kartu per lembar A4, hemat kertas dan mudah dipotong.
                    @endif
                </p>
            </div>

            <div class="flex items-start gap-2.5 rounded-lg border border-blue-100 bg-blue-50/60 px-3 py-2.5 text-[11px] text-blue-800 lg:col-span-2">
                <i class="bi bi-lightbulb-fill mt-0.5 text-blue-500"></i>
                <p class="m-0 font-semibold leading-relaxed">
                    Hasil cetak terbaik: gunakan skala <strong>100%</strong>, pilih kertas <strong>{{ $modeAktif === 'ktp' ? 'Card Size / CR80' : 'A4' }}</strong>, dan aktifkan <strong>Background graphics</strong>.
                </p>
            </div>
        </div>

        <hr class="border-slate-100">

        <!-- Filter Data -->
        @if ($bisaSetelan)
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Filter Data Guru</label>
                <form method="get" action="{{ url($basePath) }}" x-ref="filterForm" class="grid grid-cols-1 items-end gap-4 md:grid-cols-12">
                    <input type="hidden" name="desain" value="{{ $desainAktif }}">
                    <input type="hidden" name="mode" value="{{ $modeAktif }}">

                    <!-- Pilih Guru (Per Orang / Semua) -->
                    <div class="md:col-span-6">
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Pilih Guru / GTK</label>
                        <select name="id_guru" x-model="idGuru" @change="$refs.filterForm.submit()"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-800 outline-none transition-colors hover:border-slate-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-200">
                            <option value="0">-- Semua Guru / GTK ({{ count($guru_options) }} Guru) --</option>
                            @foreach ($guru_options as $go)
                                <option value="{{ $go->id_guru }}" @selected($id_guru == $go->id_guru)>
                                    {{ $go->nama_guru }} ({!! !empty($go->nip) ? 'NIP: ' . e($go->nip) : (!empty($go->kode_guru) ? 'Kode: ' . e($go->kode_guru) : 'ID: ' . $go->id_guru) !!})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Pencarian Cepat Nama / NIP -->
                    <div class="md:col-span-6">
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Filter Pencarian</label>
                        <div class="relative">
                            <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                            <input type="text" x-ref="search" x-model="query"
                                   placeholder="Cari nama, NIP, atau kode guru..."
                                   aria-label="Cari nama, NIP, atau kode guru"
                                   @keydown.enter.prevent
                                   @keydown.escape.prevent="clearSearch()"
                                   class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-9 pr-9 text-xs font-semibold text-slate-800 outline-none transition-colors hover:border-slate-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-200">
                            <button type="button" x-show="query.trim() !== ''" x-cloak @click="clearSearch()"
                                    aria-label="Bersihkan pencarian"
                                    class="absolute right-2.5 top-1/2 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-200 hover:text-slate-600">
                                <i class="bi bi-x-circle-fill text-[13px]"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-1 md:col-span-12">
                        <a href="{{ $resetUrl }}"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-600 no-underline transition-colors hover:border-slate-300 hover:bg-slate-50">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset filter
                        </a>
                    </div>
                </form>
            </div>
        @else
            @php
                $guruSendiri = $guru_list[0] ?? null;
                $inisialSendiri = $guruSendiri
                    ? collect(preg_split('/\s+/', trim((string) $guruSendiri->nama_guru)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($kata) => mb_strtoupper(mb_substr($kata, 0, 1)))
                        ->implode('')
                    : '?';
            @endphp
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Kartu Anda</label>
                <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-700 text-xs font-extrabold tracking-wide text-white">{{ $inisialSendiri }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="m-0 truncate text-sm font-extrabold leading-tight text-slate-800">{{ $guruSendiri->nama_guru ?? '-' }}</p>
                        <p class="m-0 mt-0.5 truncate text-[11px] font-semibold text-slate-500">
                            NIP: {{ $guruSendiri->nip ?? '-' }}
                            <span class="mx-1 text-slate-300">•</span>{{ $guruSendiri->jabatan ?? 'Guru / Tenaga Pendidik' }}
                        </p>
                    </div>
                    <span class="shrink-0 rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-blue-700">Kartu saya</span>
                </div>
                <p class="mb-0 mt-1.5 text-[11px] text-slate-400">
                    <i class="bi bi-shield-lock me-1"></i> Kartu ini terhubung dengan akun Anda. Hubungi admin bila data belum sesuai.
                </p>
            </div>
        @endif

        @if (!empty($catatan))
            <div class="flex items-start gap-2.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-800">
                <i class="bi bi-exclamation-triangle-fill mt-0.5 text-amber-500"></i>
                <p class="font-semibold leading-relaxed">{{ $catatan }}</p>
            </div>
        @endif

        @if (!empty($terpotong))
            <div class="flex items-start gap-2.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2.5 text-xs text-blue-800">
                <i class="bi bi-info-circle-fill mt-0.5 text-blue-500"></i>
                <p class="font-semibold leading-relaxed">
                    Daftar dipotong pada {{ (int) ($batas_cetak ?? 500) }} kartu pertama supaya halaman tidak macet.
                    Pilih satu guru untuk mencetak sisanya.
                </p>
            </div>
        @endif
    </x-ui.card>

    <!-- Live Card Grid Preview -->
    @if (empty($guru_list))
        <x-ui.card>
            <x-ui.empty icon="bi-person-x" title="Tidak Ada Data Guru"
                :description="!empty($catatan) ? $catatan : 'Silakan periksa master data guru di menu Data Guru.'">
                @if ($bisaSetelan)
                    <a href="{{ url('dataguru') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white no-underline transition-colors hover:bg-blue-700">
                        <i class="bi bi-person-video3"></i> Buka Data Guru
                    </a>
                @endif
            </x-ui.empty>
        </x-ui.card>
    @else
        @php
            // Partial pratinjau dipilih dari desain aktif supaya markup grid tidak diduplikasi.
            $previewDepan = 'admin.presensi.kartu.partials.kartu_guru_' . ($desainAktif === 'portrait' ? 'portrait' : 'landscape');
            $previewBelakang = 'admin.presensi.kartu.partials.kartu_guru_'
                . ($desainAktif === 'portrait' ? 'belakang' : 'landscape_belakang');
        @endphp
        <div id="cardGrid" x-ref="grid" class="mx-auto grid grid-cols-1 gap-4 {{ $desainAktif === 'portrait' ? 'max-w-3xl' : 'max-w-4xl' }}">
            @foreach ($guru_list as $g)
                <div class="teacher-card-item card-preview overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs"
                     x-show="matches($el)"
                     data-name="{{ strtolower($g->nama_guru) }}"
                     data-nip="{{ strtolower($g->nip ?? '') }}"
                     data-kode="{{ strtolower($g->kode_guru ?? '') }}">
                    @php
                        $inisialGuru = collect(preg_split('/\s+/', trim((string) $g->nama_guru)))
                            ->filter()
                            ->take(2)
                            ->map(fn ($kata) => mb_strtoupper(mb_substr($kata, 0, 1)))
                            ->implode('');
                    @endphp
                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-700 text-[11px] font-extrabold tracking-wide text-white">{{ $inisialGuru }}</span>
                            <div class="min-w-0">
                                <h5 class="m-0 truncate text-[12px] font-extrabold leading-tight text-slate-800" title="{{ $g->nama_guru }}">{{ $g->nama_guru }}</h5>
                                <p class="m-0 mt-0.5 truncate text-[10px] font-semibold text-slate-500">
                                    <span class="font-mono text-slate-600">{{ $g->nip ?: ($g->kode_guru ?: 'GTK') }}</span>
                                    <span class="mx-1 text-slate-300">•</span>{{ $g->jabatan }}
                                </p>
                            </div>
                        </div>
                        <a href="{{ url($basePath . '/cetak?id_guru=' . $g->id_guru . '&desain=' . $desainAktif . '&mode=' . $modeAktif . '&v=1') }}"
                           target="_blank"
                           class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-[11px] font-bold text-blue-700 no-underline transition-colors hover:border-blue-300 hover:bg-blue-100">
                            <i class="bi bi-printer"></i> Cetak
                        </a>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-4 p-4">
                        <div class="card-stage">
                            <span class="card-stage-label"><i class="bi bi-credit-card"></i> Sisi Depan</span>
                            @include($previewDepan)
                        </div>
                        <div class="card-stage">
                            <span class="card-stage-label"><i class="bi bi-arrow-repeat"></i> Sisi Belakang</span>
                            @include($previewBelakang)
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div x-show="matchCount === 0" x-cloak>
            <x-ui.card>
                <x-ui.empty icon="bi-search" title="Tidak ada kartu yang cocok"
                    description="Ubah kata kunci pencarian nama, NIP, atau kode guru.">
                    <button type="button" @click="clearSearch()"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-[11px] font-bold text-blue-700 transition-colors hover:border-blue-300 hover:bg-blue-100">
                        <i class="bi bi-arrow-counterclockwise"></i> Bersihkan pencarian
                    </button>
                </x-ui.empty>
            </x-ui.card>
        </div>
    @endif

</div>
@endsection

@section('scripts')
<!-- Library QRCode JS -->
<script src="{{ asset('assets/app/js/qrcode.min.js') }}"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('kartuGuruPage', (config = {}) => ({
        base: config.base || '',
        desain: config.desain || 'landscape',
        mode: config.mode || 'grid',
        idGuru: String(config.idGuru ?? '0'),
        query: '',

        init() {
            this.$nextTick(() => this.renderQrCodes());
        },

        /** URL cetak mengikuti pilihan guru + desain & tata letak yang aktif. */
        get cetakUrl() {
            const params = new URLSearchParams();
            if (this.idGuru !== '0') {
                params.set('id_guru', this.idGuru);
            }
            params.set('desain', this.desain);
            params.set('mode', this.mode);
            params.set('v', '1');

            return this.base + '/cetak?' + params.toString();
        },

        /** Cocokkan satu kartu dengan kata kunci nama / NIP / kode guru. */
        matches(el) {
            const q = this.query.trim().toLowerCase();
            if (!q) return true;
            const data = el.dataset || {};

            return String(data.name || '').includes(q)
                || String(data.nip || '').includes(q)
                || String(data.kode || '').includes(q);
        },

        get matchCount() {
            const grid = this.$refs.grid;
            if (!grid) return 0;

            return Array.from(grid.querySelectorAll('.teacher-card-item'))
                .filter((el) => this.matches(el)).length;
        },

        clearSearch() {
            this.query = '';
            this.$nextTick(() => this.$refs.search?.focus());
        },

        /** Render QR untuk setiap kartu yang tampil (dan yang tersembunyi). */
        renderQrCodes() {
            if (typeof QRCode === 'undefined') return;

            document.querySelectorAll('.qr-box').forEach((el) => {
                const code = el.dataset.code;
                const size = parseInt(el.dataset.size || '78', 10) || 78;

                if (!code) {
                    const kosong = document.createElement('span');
                    kosong.className = 'text-[10px] text-slate-400';
                    kosong.textContent = 'QR kosong';
                    el.replaceChildren(kosong);
                    return;
                }

                try {
                    new QRCode(el, {
                        text: String(code),
                        width: size,
                        height: size,
                        colorDark: '#09265e',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H,
                    });
                } catch (e) {
                    const fallback = document.createElement('span');
                    fallback.className = 'font-mono text-xs font-bold';
                    fallback.textContent = String(code);
                    el.replaceChildren(fallback);
                }
            });
        },
    }));
});
</script>
@endsection
