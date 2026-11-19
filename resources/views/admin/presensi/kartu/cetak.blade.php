@php
    // Variabel tata letak cetak (dipakai di seluruh halaman ini).
    $desainAktif = $desain ?? 'duplex';
    $isPortrait = $desainAktif === 'duplex_portrait';
    $modeAktif = in_array(($mode ?? 'lembar'), ['lembar', 'bolakbalik', 'ktp'], true) ? ($mode ?? 'lembar') : 'lembar';
    $flipAktif = ($flip ?? 'long') === 'short' ? 'short' : 'long';
    $gridAktif = $grid ?? ['cols' => 2, 'rows' => 4, 'per_sheet' => 8, 'portrait' => false];
    $lembarList = is_array($lembar ?? null) ? $lembar : [];
    $partialDepan = $isPortrait ? 'admin.presensi.kartu.partials.kartu_dp_depan' : 'admin.presensi.kartu.partials.kartu_dl_depan';
    $partialBelakang = $isPortrait ? 'admin.presensi.kartu.partials.kartu_dp_belakang' : 'admin.presensi.kartu.partials.kartu_dl_belakang';
    // Template latar kartu duplex portrait: 'vektor' atau 'png' (unggahan /settings).
    $templateAktif = ($template ?? 'vektor') === 'png' ? 'png' : 'vektor';
    $templatePngSiap = $isPortrait && ! empty($kartu_bg_depan) && ! empty($kartu_bg_belakang);
    // Prefix role-aware: admin dan guru memakai halaman preview yang berbeda.
    $basePath = $base_path ?? (str_starts_with(request()->path(), 'guru/') ? 'guru/presensi-siswa/kartu' : 'admin/presensi/siswa/kartu');
    // Admin bisa mengelola unggahan latar di menu Pengaturan; guru tidak punya akses.
    $bisaSetelan = ! str_starts_with($basePath, 'guru/');
    $qKelas = (int) request()->query('id_kelas', 0);
    $qSiswa = (int) request()->query('id_siswa', 0);
    // URL kembali yang deterministik (tidak bergantung riwayat browser).
    $previewQuery = array_filter([
        'id_kelas' => $qKelas > 0 ? $qKelas : null,
        'id_siswa' => $qSiswa > 0 ? $qSiswa : null,
        'desain' => $desainAktif,
        'mode' => $modeAktif,
        'flip' => $modeAktif === 'bolakbalik' ? $flipAktif : null,
        'template' => $isPortrait ? $templateAktif : null,
    ], static fn ($nilai): bool => $nilai !== null);
    $previewUrl = url($basePath).'?'.http_build_query($previewQuery);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Siswa & Presensi QR - {{ $nama_kelas }} - {{ $setting->sekolah ?? 'Sekolah' }}</title>
    <link rel="stylesheet" href="{{ asset('assets/app/css/lucide-icons.css') }}?v={{ @filemtime(public_path() . '/' . 'assets/app/css/lucide-icons.css') }}">
    {!! \App\Libraries\Theme::styleTag() !!}
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            margin: 0;
            padding: 20px;
        }

        /* Top Action Bar (Screen Only) */
        .top-bar {
            max-width: 1100px;
            margin: 0 auto 20px auto;
            background: #ffffff;
            padding: 16px 20px;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
            border: 1px solid #e2e8f0;
        }
        .top-bar-info {
            font-size: 13px;
            color: #475569;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .top-bar-info strong {
            color: #0f172a;
        }
        .btn-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            font-size: 11.5px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background-color: #2563eb;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
        }
        .btn-secondary {
            background-color: #f8fafc;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background-color: #e2e8f0;
        }
        .btn-active {
            background-color: #1d4ed8 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(29,78,216,0.3);
        }
        @media (max-width: 640px) {
            .top-bar-head { align-items: flex-start; }
            .btn-group { width: 100%; }
            .btn-group .btn { flex: 1; justify-content: center; }
        }

        /* ── Top bar terstruktur (layar) ──────────────────────────────── */
        .top-bar-head {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }
        .top-bar-title {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
        }
        .top-bar-title > i {
            color: #2563eb;
            font-size: 16px;
        }
        .top-bar-count {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 800;
            color: #1d4ed8;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 3px 10px;
            border-radius: 999px;
        }
        .top-bar-count.muted {
            color: #334155;
            background: #f1f5f9;
            border-color: #e2e8f0;
        }
        .top-bar-controls {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            padding-top: 13px;
            border-top: 1px dashed #e2e8f0;
        }
        .ctrl-group {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .ctrl-label {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #94a3b8;
        }
        .seg {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px;
            background: #f1f5f9;
            border-radius: 11px;
        }
        .top-bar-tip {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 11px;
            color: #64748b;
            padding-top: 11px;
            border-top: 1px dashed #e2e8f0;
        }
        .top-bar-tip i {
            color: #f59e0b;
            flex: 0 0 auto;
        }

        /* Sheet Container */
        .sheet-container {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }

        /* Duplex Row */
        .duplex-row {
            display: flex;
            gap: 24px;
            margin-bottom: 24px;
            justify-content: center;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .duplex-card-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }
        .side-badge {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
        }

        /* ═══════════════════════════════════════════════════════════════════
           1. DUPLEX LANDSCAPE CARD (85.6 mm × 54 mm ISO ID-1 CR80)
           Screen: 324px × 204px (96 DPI) | Print: 85.6mm × 54mm
        ═══════════════════════════════════════════════════════════════════ */
        .card-dl {
            width: 324px;
            height: 204px;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 16px rgba(15,23,42,0.08);
            display: flex;
            flex-direction: column;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* FRONT: Landscape */
        .card-dl-front {
            background: #ffffff;
        }

        /* Header Landscape: Bar penuh dengan aksen ombak tipis di bawah */
        .dl-header-bar {
            height: 42px;
            background: linear-gradient(90deg, #09265e 0%, #103a8a 40%, #1e56c8 100%);
            padding: 5px 10px 0 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            z-index: 2;
        }
        .dl-header-accent {
            position: absolute;
            bottom: -5px;
            left: 0;
            right: 0;
            height: 6px;
            pointer-events: none;
            z-index: 3;
        }
        .dl-logo-wrap {
            width: 30px;
            height: 30px;
            background: #ffffff;
            border-radius: 50%;
            padding: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.25);
            flex-shrink: 0;
        }
        .dl-logo-wrap img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .dl-header-titles {
            flex: 1;
            min-width: 0;
        }
        .dl-header-title {
            font-size: 7.6pt;
            font-weight: 900;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.25px;
            line-height: 1.15;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-shadow: 0 1px 2px rgba(0,0,0,0.35);
        }
        .dl-header-sub {
            font-size: 5.2pt;
            font-weight: 700;
            color: #fbbf24;
            margin: 1.5px 0 0 0;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .dl-class-pill {
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.35);
            color: #ffffff;
            font-size: 6.2pt;
            font-weight: 800;
            padding: 1.5px 7px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            flex-shrink: 0;
        }

        /* Body Landscape: 3 Kolom Rapi (Foto, Identitas, QR Code) */
        .dl-front-body {
            position: relative;
            z-index: 2;
            flex: 1;
            display: flex;
            padding: 10px 10px 8px 10px;
            gap: 10px;
            align-items: center;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        /* Kolom 1: Foto Siswa */
        .dl-photo-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 72px;
            flex-shrink: 0;
        }
        .dl-photo-box {
            width: 66px;
            height: 82px;
            background: #dc2626; /* Pas foto standar merah resmi */
            border-radius: 6px;
            overflow: hidden;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.14);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dl-photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .dl-photo-placeholder {
            font-size: 30px;
            color: rgba(255,255,255,0.7);
        }
        .dl-jk-tag {
            margin-top: 4px;
            font-size: 5.5pt;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 1px 6px;
            border-radius: 6px;
            text-align: center;
            width: 66px;
        }
        .dl-jk-tag.L {
            background: #dbeafe;
            color: #1e40af;
            border: 0.5px solid #bfdbfe;
        }
        .dl-jk-tag.P {
            background: #fce7f3;
            color: #9d174d;
            border: 0.5px solid #fbcfe8;
        }

        /* Kolom 2: Identitas Tengah */
        .dl-info-col {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0;
        }
        .dl-student-name {
            font-size: 9.5pt;
            font-weight: 900;
            color: #09265e;
            text-transform: uppercase;
            line-height: 1.15;
            margin: 0 0 2px 0;
            letter-spacing: 0.2px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .dl-name-divider {
            height: 2px;
            width: 32px;
            background: linear-gradient(90deg, #f59e0b 0%, #2563eb 100%);
            border-radius: 2px;
            margin-bottom: 5px;
        }
        .dl-info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5pt;
            line-height: 1.45;
        }
        .dl-info-table td {
            padding: 1.5px 0;
            vertical-align: top;
        }
        .dl-info-table .dl-lbl {
            width: 44px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            font-size: 5.5pt;
            letter-spacing: 0.2px;
        }
        .dl-info-table .dl-sep {
            width: 6px;
            font-weight: 700;
            color: #94a3b8;
        }
        .dl-info-table .dl-val {
            font-weight: 800;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 95px;
        }

        /* Kolom 3: QR Code Besar di Kanan */
        .dl-qr-col {
            width: 82px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        .dl-qr-card {
            background: #ffffff;
            border: 1.5px solid #bfdbfe;
            border-radius: 8px;
            padding: 4px;
            box-shadow: 0 2px 8px rgba(37,99,235,0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .dl-qr-box {
            width: 68px;
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dl-qr-box canvas, .dl-qr-box img {
            width: 100% !important;
            height: 100% !important;
            max-width: 100% !important;
            max-height: 100% !important;
            display: block;
        }
        .dl-qr-badge {
            background: #09265e;
            color: #ffffff;
            font-size: 5.2pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 2px 7px;
            border-radius: 10px;
            text-align: center;
            white-space: nowrap;
            margin-top: 1px;
        }

        /* BACK: Landscape */
        .card-dl-back {
            background: #ffffff;
        }
        .dl-back-header-bar {
            height: 32px;
            background: linear-gradient(90deg, #09265e 0%, #103a8a 50%, #1e56c8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
            border-bottom: 2px solid #f59e0b;
        }
        .dl-back-title {
            font-size: 7.8pt;
            font-weight: 900;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .dl-back-body {
            position: relative;
            z-index: 2;
            flex: 1;
            display: flex;
            padding: 8px 10px 4px 10px;
            gap: 12px;
            background: #ffffff;
        }
        .dl-back-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 95px;
            height: 95px;
            opacity: 0.04;
            pointer-events: none;
            z-index: 1;
        }
        .dl-back-watermark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .dl-back-col-left {
            position: relative;
            z-index: 2;
            width: 140px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .dl-back-col-right {
            position: relative;
            z-index: 2;
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .dl-back-sublabel {
            font-size: 5.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #09265e;
            margin-bottom: 3px;
        }
        .dl-back-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.2pt;
            line-height: 1.4;
        }
        .dl-back-table td {
            padding: 1.2px 0;
            vertical-align: top;
        }
        .dl-back-table .b-lbl {
            width: 44px;
            font-weight: 700;
            color: #64748b;
            font-size: 5.5pt;
            text-transform: uppercase;
        }
        .dl-back-table .b-sep {
            width: 6px;
            color: #94a3b8;
            font-weight: 700;
        }
        .dl-back-table .b-val {
            font-weight: 800;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 85px;
        }
        .ttl-wrap {
            white-space: normal !important;
            overflow: visible !important;
            overflow-wrap: anywhere;
            text-overflow: clip !important;
            max-width: none !important;
            min-width: 0 !important;
        }

        .dl-back-rules-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 5px 6px;
        }
        .dl-back-pill {
            background: #09265e;
            color: #ffffff;
            font-size: 5.2pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 1.5px 6px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 3px;
            display: inline-block;
        }
        .dl-rules-list {
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 1.5px;
            text-align: left;
        }
        .dl-rule-item {
            display: flex;
            align-items: flex-start;
            font-size: 4.8pt;
            color: #334155;
            line-height: 1.35;
            font-weight: 600;
        }
        .dl-rule-num {
            width: 10px;
            flex-shrink: 0;
            font-weight: 800;
            color: #09265e;
        }
        .dl-rule-text {
            flex: 1;
        }

        .dl-back-bottom-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 2px;
        }
        .dl-barcode-box {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .dl-barcode-box svg {
            width: 90px;
            height: 18px;
        }
        .dl-barcode-text {
            font-size: 4.8pt;
            font-weight: 800;
            font-family: monospace;
            color: #0f172a;
            margin-top: 1px;
            letter-spacing: 0.5px;
        }
        .dl-back-sign {
            position: relative;
            text-align: right;
            font-size: 4.8pt;
            color: #64748b;
            line-height: 1.25;
        }
        .sign-stamp-area {
            position: relative;
            width: max-content;
            margin-left: auto;
        }
        .sign-stamp-area > div,
        .dl-back-sign > div:first-child {
            position: relative;
            z-index: 2;
        }
        .sign-stamp {
            position: absolute;
            left: 50%;
            top: 35%;
            width: 20mm;
            height: 20mm;
            object-fit: contain;
            opacity: 0.7;
            pointer-events: none;
            transform: translate(-50%, -50%);
            z-index: 0;
        }
        .dl-sign-name {
            font-weight: 800;
            color: #0f172a;
            text-decoration: underline;
        }

        .dl-back-footer {
            height: 18px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 0 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 5pt;
        }
        .dl-motto {
            font-style: italic;
            font-weight: 800;
            color: #09265e;
            width: 100%;
            text-align: center;
            letter-spacing: 0.2px;
        }

        /* ═══════════════════════════════════════════════════════════════════
           2. DUPLEX PORTRAIT CARD (54 mm × 85.6 mm - Kalung / ID Card)
        ═══════════════════════════════════════════════════════════════════ */
        .card-dp {
            width: 204px;
            height: 324px;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 16px rgba(15,23,42,0.08);
            display: flex;
            flex-direction: column;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .card-dp-front {
            background: linear-gradient(170deg, #eaf3fc 0%, #dbeafb 45%, #cfe4f9 100%);
        }
        /* ═══ Template Latar PNG (kartu duplex portrait) ═══
           Latar unggahan /settings menempel penuh di belakang konten;
           hiasan vektor, kop, watermark, garis & ombak bawah disembunyikan. */
        .dp-bg-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            pointer-events: none;
        }
        .card-dp-tpl-png .dp-kop,
        .card-dp-tpl-png .dp-streaks,
        .card-dp-tpl-png .dp-wave-top,
        .card-dp-tpl-png .dp-wave-bottom,
        .card-dp-tpl-png .dp-back-header,
        .card-dp-tpl-png .dp-back-watermark,
        .card-dp-tpl-png .dp-info-divider,
        .card-dp-tpl-png .dp-back-footer-wave-wrap {
            display: none !important;
        }
        /* Latar: garis lengkung halus */
        .dp-streaks {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }
        /* Dekorasi: ombak biru muda + garis emas di bawah header */
        .dp-wave-top {
            position: absolute;
            top: 64px;
            left: 0;
            width: 100%;
            height: 36px;
            z-index: 1;
            pointer-events: none;
        }
        /* Sisi belakang: ombak pendek menempel di bawah header (46px),
           menjorok di belakang header agar tidak menabrak tabel data */
        .dp-wave-top-back {
            top: 40px;
            height: 13px;
        }
        /* Kop rata tengah: logo, dinas, nama sekolah, alamat */
        .dp-kop {
            position: relative;
            z-index: 2;
            margin: 2px 12px 0 12px;
            text-align: center;
            flex-shrink: 0;
        }
        .dp-kop-logo {
            width: 32px;
            height: 32px;
            margin: 0 auto 3px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dp-kop-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .dp-kop-dinas {
            font-size: 8.5px;
            font-weight: 800;
            letter-spacing: 0.3px;
            color: #16325c;
            margin: 0;
            line-height: 1.1;
        }
        .dp-kop-school {
            font-size: 11px;
            font-weight: 900;
            color: #16325c;
            text-transform: uppercase;
            margin: 2px auto 0 auto;
            line-height: 1.15;
            max-width: 190px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }
        .dp-kop-address {
            font-size: 7.5px;
            font-weight: 700;
            color: #1f3c66;
            margin: 2px auto 0 auto;
            line-height: 1.25;
            max-width: 190px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: -0.1px;
        }

        .dp-photo-wrap {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: center;
            margin-top: 3px;
        }
        .dp-photo-box {
            width: 82px;
            height: 84px;
            background: #e23434;
            border-radius: 9px;
            overflow: hidden;
            border: 3px solid #ffffff;
            box-shadow: 0 3px 10px rgba(9,38,94,0.28);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dp-photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dp-name-badge {
            position: relative;
            z-index: 2;
            margin: 4px 12px 0 12px;
            background: linear-gradient(135deg, #0f3593 0%, #1e56c8 100%);
            color: #ffffff;
            font-size: 10.5px;
            font-weight: 900;
            text-transform: uppercase;
            padding: 5px 8px;
            border-radius: 7px;
            text-align: center;
            word-break: break-word;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            box-shadow: 0 2px 7px rgba(15,53,147,0.35);
            letter-spacing: 0.1px;
            line-height: 1.2;
        }

        .dp-meta-table {
            position: relative;
            z-index: 2;
            margin: 3px auto 0 auto;
            width: 180px;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 9.5px;
            line-height: 1.25;
        }
        .dp-meta-table td {
            padding: 1px 0;
            vertical-align: middle;
        }
        .dp-meta-table .dp-meta-lbl {
            width: 46px;
            font-weight: 800;
            color: #123a75;
        }
        .dp-meta-table .dp-meta-sep {
            width: 9px;
            font-weight: 800;
            color: #123a75;
            text-align: center;
        }
        .dp-meta-table .dp-meta-val {
            padding-left: 2px;
            font-weight: 800;
            color: #123a75;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dp-qr-section {
            position: relative;
            z-index: 2;
            margin: auto 12px 7px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dp-qr-card {
            background: #ffffff;
            border: 2px solid #2f80ed;
            border-radius: 9px;
            padding: 4px;
            box-shadow: 0 2px 8px rgba(37,99,235,0.16);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .dp-qr-wrap {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dp-qr-logo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 27px;
            height: 27px;
            padding: 2px;
            background: #ffffff;
            border-radius: 4px;
            object-fit: contain;
        }
        .dp-qr-box {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dp-qr-box canvas, .dp-qr-box img {
            width: 100% !important;
            height: 100% !important;
            display: block;
        }
        .dp-qr-divider {
            width: 2.5px;
            height: 72px;
            background: #1e56c8;
            border-radius: 2px;
            flex-shrink: 0;
        }
        .dp-qr-desc {
            font-size: 9px;
            font-weight: 900;
            color: #1e56c8;
            text-transform: uppercase;
            line-height: 1.3;
            letter-spacing: 0.2px;
        }
        .dp-wave-bottom {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 120px;
            height: 42px;
            pointer-events: none;
            z-index: 1;
        }

        /* BACK: Portrait */
        .card-dp-back {
            background: linear-gradient(170deg, #eaf3fc 0%, #dbeafb 45%, #cfe4f9 100%);
            display: flex;
            flex-direction: column;
        }
        .dp-back-header {
            height: 46px;
            background: linear-gradient(135deg, #0b3d91 0%, #1450b4 55%, #1e56c8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
        }
        .dp-back-title {
            font-size: 13.5px;
            font-weight: 900;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin: 0;
            text-shadow: 0 1px 3px rgba(4, 24, 62, 0.45);
        }
        .dp-back-body {
            position: relative;
            z-index: 2;
            flex: 1;
            min-height: 0;
            padding: 6px 12px 0 12px;
            display: flex;
            flex-direction: column;
        }
        .dp-back-watermark {
            position: absolute;
            top: 52%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 130px;
            height: 130px;
            opacity: 0.07;
            pointer-events: none;
            z-index: 1;
        }
        .dp-back-watermark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .dp-info-table {
            position: relative;
            z-index: 2;
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8.5px;
            line-height: 1.25;
        }
        .dp-info-table td {
            padding: 1px 0;
            vertical-align: top;
        }
        .dp-info-table .dp-lbl {
            width: 44px;
            font-size: 8.5px;
            font-weight: 800;
            color: #123a75;
            white-space: nowrap;
        }
        .dp-info-table .dp-sep {
            width: 9px;
            font-weight: 800;
            color: #123a75;
            text-align: center;
        }
        .dp-info-table .dp-val {
            padding-left: 2px;
            font-weight: 800;
            color: #123a75;
            line-height: 1.25;
            word-break: break-word;
        }

        /* Garis biru di bawah tabel data */
        .dp-info-divider {
            position: relative;
            z-index: 2;
            height: 2px;
            background: #1e56c8;
            border-radius: 1px;
            margin: 4px 0 0 0;
        }

        .dp-rules-section {
            position: relative;
            z-index: 2;
            margin-top: 5px;
            text-align: center;
        }
        .dp-rules-pill {
            background: linear-gradient(135deg, #0f3593 0%, #1e56c8 100%);
            color: #ffffff;
            font-size: 8px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 2px 12px;
            border-radius: 9999px;
            display: inline-block;
            margin-bottom: 3px;
            box-shadow: 0 2px 6px rgba(15,53,147,0.3);
        }
        .dp-rules-list {
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 1px;
            text-align: left;
        }
        .dp-rule-item {
            display: flex;
            align-items: flex-start;
            font-size: 8px;
            color: #16325c;
            line-height: 1.25;
            font-weight: 600;
        }
        .dp-rule-num {
            width: 11px;
            flex-shrink: 0;
            font-weight: 800;
            color: #123a75;
        }
        .dp-rule-text {
            flex: 1;
        }

        .dp-barcode-wrap {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1px;
            margin-top: auto;
            padding-top: 4px;
        }
        .dp-barcode-svg {
            width: 150px;
            height: 16px;
        }
        .dp-barcode-svg rect {
            fill: #123a75;
        }
        .dp-barcode-text {
            font-family: monospace;
            font-size: 6.5px;
            font-weight: 800;
            color: #123a75;
            margin-top: -1px;
        }

        .dp-back-footer-wave-wrap {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 28px;
            margin-top: 5px;
            flex-shrink: 0;
        }
        .dp-back-footer-wave {
            width: 100%;
            height: 100%;
            display: block;
        }
        .dp-footer-motto {
            position: absolute;
            bottom: 5px;
            left: 0;
            width: 100%;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 9.5px;
            font-style: italic;
            font-weight: 700;
            color: #ffffff;
            text-align: center;
            letter-spacing: 0.2px;
        }

        /* ═══════════════════════════════════════════════════════════════════
           PRINT MEDIA QUERIES (300 DPI Presisi Fisik Milimeter)
        ═══════════════════════════════════════════════════════════════════ */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .sheet-container {
                padding: 0 !important;
                margin: 0 !important;
                max-width: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            /* Duplex Landscape Printing (85.6mm × 54mm) */
            .card-dl {
                width: 85.6mm !important;
                height: 54mm !important;
                border-radius: 3.18mm !important;
                box-shadow: none !important;
                border: 0.2mm solid #cbd5e1 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .dl-header-bar { height: 11mm !important; padding: 1.2mm 2.5mm 0 2.5mm !important; }
            .dl-logo-wrap { width: 7.5mm !important; height: 7.5mm !important; }
            .dl-header-title { font-size: 6.8pt !important; }
            .dl-header-sub { font-size: 4.5pt !important; }
            .dl-class-pill { font-size: 5.2pt !important; padding: 0.5mm 1.8mm !important; }
            .dl-front-body { padding: 2mm 2.5mm 2mm 2.5mm !important; gap: 2.5mm !important; }
            .dl-photo-col { width: 18mm !important; }
            .dl-photo-box { width: 16.5mm !important; height: 21mm !important; }
            .dl-jk-tag { width: 16.5mm !important; font-size: 4.5pt !important; }
            .dl-student-name { font-size: 7.8pt !important; }
            .dl-name-divider { height: 0.5mm !important; width: 8mm !important; margin-bottom: 1.2mm !important; }
            .dl-info-table { font-size: 5.2pt !important; }
            .dl-info-table .dl-lbl { width: 11mm !important; font-size: 4.8pt !important; }
            .dl-qr-col { width: 22mm !important; gap: 1mm !important; }
            .dl-qr-card { padding: 0.8mm !important; }
            .dl-qr-box { width: 18mm !important; height: 18mm !important; }
            .dl-qr-badge { font-size: 4.2pt !important; padding: 0.5mm 1.5mm !important; }

            .dl-back-header-bar { height: 8.5mm !important; }
            .dl-back-title { font-size: 6.8pt !important; }
            .dl-back-body { padding: 1.5mm 2.5mm 1mm 2.5mm !important; gap: 3mm !important; }
            .dl-back-col-left { width: 37mm !important; }
            .dl-back-table { font-size: 4.8pt !important; }
            .dl-back-table .b-lbl { width: 11mm !important; font-size: 4.5pt !important; }
            .dl-back-pill { font-size: 4.2pt !important; }
            .dl-rules-list { font-size: 3.8pt !important; line-height: 1.3 !important; }
            .dl-barcode-box svg { width: 24mm !important; height: 4.5mm !important; }
            .dl-barcode-text { font-size: 4.2pt !important; }
            .dl-back-sign { font-size: 4pt !important; }
            .dl-back-footer { height: 4.5mm !important; font-size: 4.2pt !important; }

            .duplex-row {
                gap: 4mm !important;
                margin-bottom: 4mm !important;
            }
            .side-badge {
                display: none !important;
            }

            /* Duplex Portrait Printing (54mm × 85.6mm) */
            .card-dp {
                width: 54mm !important;
                height: 85.6mm !important;
                border-radius: 3.18mm !important;
                box-shadow: none !important;
                border: 0.2mm solid #cbd5e1 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .dp-kop { margin: 0.8mm 3.2mm 0 3.2mm !important; }
            .dp-kop-logo { width: 7.5mm !important; height: 7.5mm !important; margin-bottom: 0.6mm !important; }
            .dp-kop-dinas { font-size: 5.6pt !important; }
            .dp-kop-school { font-size: 6.6pt !important; line-height: 1.15 !important; margin-top: 0.3mm !important; max-width: 47mm !important; }
            .dp-kop-address { font-size: 4.6pt !important; margin-top: 0.3mm !important; max-width: 47mm !important; }
            .dp-wave-top { top: 17mm !important; height: 9.5mm !important; }
            .dp-wave-top-back { top: 10.2mm !important; height: 3.8mm !important; }
            .dp-photo-wrap { margin-top: 2mm !important; }
            .dp-photo-box { width: 21mm !important; height: 23mm !important; border-radius: 2.4mm !important; border-width: 0.8mm !important; }
            .dp-name-badge { font-size: 7.8pt !important; margin: 1.5mm 3.2mm 0 3.2mm !important; padding: 1.2mm 2.4mm !important; border-radius: 1.8mm !important; line-height: 1.2 !important; -webkit-line-clamp: 2 !important; }
            .dp-meta-table { font-size: 7.5pt !important; margin: 1.2mm auto 0 auto !important; width: 44.5mm !important; }
            .dp-meta-table .dp-meta-lbl { width: 14.3mm !important; }
            .dp-meta-table .dp-meta-sep { width: 2.9mm !important; }
            .dp-qr-section { margin: auto 3.2mm 2.5mm 3.2mm !important; gap: 2.6mm !important; }
            .dp-qr-card { padding: 1mm !important; border-radius: 2.5mm !important; border-width: 0.5mm !important; }
            .dp-qr-logo { width: 7mm !important; height: 7mm !important; padding: 0.5mm !important; border-radius: 0.8mm !important; }
            .dp-qr-box { width: 21mm !important; height: 21mm !important; }
            .dp-qr-divider { height: 17.5mm !important; width: 0.66mm !important; }
            .dp-qr-desc { font-size: 7pt !important; }
            .dp-wave-bottom { width: 31.7mm !important; height: 11.1mm !important; }

            .dp-back-header { height: 12.2mm !important; }
            .dp-back-title { font-size: 10pt !important; }
            .dp-back-body { padding: 1.6mm 3.2mm 0 3.2mm !important; }
            .dp-info-table { font-size: 7.1pt !important; line-height: 1.3 !important; }
            .dp-info-table td { padding: 0.5mm 0 !important; }
            .dp-info-table .dp-lbl { width: 13mm !important; font-size: 6.7pt !important; }
            .dp-info-table .dp-sep { width: 2.9mm !important; }
            .dp-info-divider { height: 0.55mm !important; margin-top: 1.3mm !important; }
            .dp-rules-section { margin-top: 1.6mm !important; }
            .dp-rules-pill { font-size: 6.7pt !important; padding: 0.8mm 3.7mm !important; margin-bottom: 1mm !important; }
            .dp-rules-list { gap: 0.5mm !important; }
            .dp-rule-item { font-size: 6.2pt !important; line-height: 1.3 !important; }
            .dp-rule-num { width: 3.2mm !important; font-size: 6.2pt !important; }
            .dp-barcode-wrap { padding-top: 1.3mm !important; }
            .dp-barcode-svg { width: 39.7mm !important; height: 4.8mm !important; }
            .dp-barcode-text { font-size: 4.9pt !important; }
            .dp-back-footer-wave-wrap { height: 7.4mm !important; margin-top: 1.3mm !important; }
            .dp-footer-motto { font-size: 7pt !important; bottom: 1.3mm !important; }

            /* Bolak-balik grid: satu lembar A4 per sisi, dipisah page break */
            .bolakbalik-stack {
                display: block !important;
                gap: 0 !important;
            }
            .bolakbalik-group {
                display: block !important;
                background: transparent !important;
                border: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
            }
            .bolakbalik-sheet {
                gap: 4mm 4mm !important;
                padding: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                background: transparent !important;
                break-after: page !important;
                page-break-after: always !important;
            }
            .bolakbalik-stack > .bolakbalik-group:last-child .bolakbalik-sheet:last-child {
                break-after: auto !important;
                page-break-after: auto !important;
            }

            /* Mode KTP: satu kartu penuh per halaman */
            .ktp-stack {
                display: block !important;
            }
            .ktp-page {
                display: block !important;
                padding: 0 !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                overflow: hidden !important;
                break-after: page !important;
                page-break-after: always !important;
            }
            .ktp-page .card-dl {
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }
            .ktp-stack > .ktp-page:last-child {
                break-after: auto !important;
                page-break-after: auto !important;
            }

            @page {
                size: A4 portrait;
                margin: 8mm 10mm;
            }
        }

        /* ═══════════════════════════════════════════════════════════════════
           PRATINJAU BOLAK-BALIK GRID (Layar) & MODE KTP
        ═══════════════════════════════════════════════════════════════════ */
        .bolakbalik-hint,
        .ktp-hint {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            max-width: 820px;
            margin: 0 auto 18px auto;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-left: 4px solid #2563eb;
            border-radius: 10px;
            padding: 13px 16px;
            font-size: 12px;
            line-height: 1.65;
            color: #1e40af;
        }
        .bolakbalik-hint > i,
        .ktp-hint > i {
            font-size: 16px;
            line-height: 1.35;
            flex: 0 0 auto;
        }
        .bolakbalik-hint strong,
        .ktp-hint strong {
            color: #1d4ed8;
        }
        .bolakbalik-stack {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .bolakbalik-group {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 2px 12px rgba(15,23,42,.05);
        }
        .sheet-label {
            display: flex;
            align-items: center;
            gap: 9px;
            flex-wrap: wrap;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #475569;
            margin: 0 0 10px 0;
        }
        .sheet-label:not(:first-child) {
            margin-top: 18px;
        }
        .sheet-page-no {
            color: #94a3b8;
            font-weight: 700;
        }
        .side-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .02em;
        }
        .side-chip.front {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .side-chip.back {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
        }
        .bolakbalik-sheet {
            display: grid;
            gap: 5mm 5mm;
            justify-content: center;
            align-content: start;
            background: transparent;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 7mm;
            margin: 0 auto;
        }
        .bolakbalik-group > .bolakbalik-sheet:last-child {
            margin-bottom: 0;
        }
        /* Mode KTP (layar): tumpukan halaman satu kartu per halaman */
        .ktp-stack {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .ktp-cap {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 12px;
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: #64748b;
        }
        .ktp-cap i {
            color: #2563eb;
        }
        .ktp-page {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 6px;
            box-shadow: 0 2px 12px rgba(15,23,42,.05);
        }
    </style>
    @if ($modeAktif === 'ktp')
    <style>
        /* Mode KTP: kertas disetel persis seukuran kartu ID-1 CR80 (85.6 × 54 mm). */
        @media print {
            @page {
                size: {{ $ktp_lebar_mm ?? 85.6 }}mm {{ $ktp_tinggi_mm ?? 54 }}mm;
                margin: 0;
            }
        }
    </style>
    @endif
</head>
<body>

    <!-- Top Action Bar (Screen Only) -->
    <?php
    $baseQs = 'id_kelas=' . $qKelas . ($qSiswa ? '&id_siswa=' . $qSiswa : '');
    $qsDesain = static fn (string $d): string => '?' . $baseQs . '&desain=' . $d . '&mode=' . $modeAktif . ($modeAktif === 'bolakbalik' ? '&flip=' . $flipAktif : '') . ($d === 'duplex_portrait' ? '&template=' . $templateAktif : '');
    $qsMode = static fn (string $m): string => '?' . $baseQs . '&desain=' . $desainAktif . '&mode=' . $m . ($m === 'bolakbalik' ? '&flip=' . $flipAktif : '') . ($isPortrait ? '&template=' . $templateAktif : '');
    $qsFlip = static fn (string $f): string => '?' . $baseQs . '&desain=' . $desainAktif . '&mode=bolakbalik&flip=' . $f . ($isPortrait ? '&template=' . $templateAktif : '');
    $qsTemplate = static fn (string $t): string => '?' . $baseQs . '&desain=duplex_portrait&mode=' . $modeAktif . ($modeAktif === 'bolakbalik' ? '&flip=' . $flipAktif : '') . '&template=' . $t;
    ?>
    <div class="top-bar no-print">
        <div class="top-bar-head">
            <div class="top-bar-title">
                <i class="bi bi-printer-fill"></i> Siap Cetak
                <span class="top-bar-count">{{ count($siswa_list) }} Kartu Siswa</span>
                <span class="top-bar-count muted">{{ $nama_kelas }}</span>
            </div>
            <div class="btn-group">
                <a href="{{ $previewUrl }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Cetak Sekarang
                </button>
            </div>
        </div>

        <div class="top-bar-controls">
            <div class="ctrl-group">
                <span class="ctrl-label">Desain</span>
                <div class="seg">
                    <a href="{{ $qsDesain('duplex') }}" class="btn {{ $desainAktif === 'duplex' ? 'btn-active' : 'btn-secondary' }}" title="Ukuran 8.56 × 5.4 cm">
                        <i class="bi bi-layers-fill"></i> Landscape
                    </a>
                    <a href="{{ $qsDesain('duplex_portrait') }}" class="btn {{ $desainAktif === 'duplex_portrait' ? 'btn-active' : 'btn-secondary' }}" title="Portrait 5.4 × 8.56 cm">
                        <i class="bi bi-card-heading"></i> Portrait
                    </a>
                </div>
            </div>
            <div class="ctrl-group">
                <span class="ctrl-label">Tata Letak</span>
                <div class="seg">
                    <a href="{{ $qsMode('lembar') }}" class="btn {{ $modeAktif === 'lembar' ? 'btn-active' : 'btn-secondary' }}" title="Sisi depan & belakang berdampingan dalam satu lembar">
                        <i class="bi bi-file-earmark"></i> Lembar
                    </a>
                    <a href="{{ $qsMode('bolakbalik') }}" class="btn {{ $modeAktif === 'bolakbalik' ? 'btn-active' : 'btn-secondary' }}" title="Grid A4 dua tahap: semua depan, lalu semua belakang">
                        <i class="bi bi-layout-text-window-reverse"></i> Bolak-balik
                    </a>
                    <a href="{{ $qsMode('ktp') }}" class="btn {{ $modeAktif === 'ktp' ? 'btn-active' : 'btn-secondary' }}" title="Cetak langsung ukuran kartu KTP (85.6 × 54 mm)">
                        <i class="bi bi-credit-card-2-front"></i> Ukuran KTP
                    </a>
                </div>
            </div>
            @if ($isPortrait)
                <div class="ctrl-group">
                    <span class="ctrl-label">Template</span>
                    <div class="seg">
                        <a href="{{ $qsTemplate('vektor') }}" class="btn {{ $templateAktif === 'vektor' ? 'btn-active' : 'btn-secondary' }}" title="Desain vektor bawaan">
                            <i class="bi bi-vector-pen"></i> Vektor
                        </a>
                        <a href="{{ $templatePngSiap ? $qsTemplate('png') : ($bisaSetelan ? url('setting') : '#') }}"
                           {{ $templatePngSiap ? '' : ($bisaSetelan ? 'title="Unggah latar kartu depan &amp; belakang di menu Pengaturan dulu" style="opacity:.45;"' : 'aria-disabled="true" title="Latar kartu diunggah admin melalui menu Pengaturan" style="pointer-events:none;opacity:.45;"') }}
                           class="btn {{ $templateAktif === 'png' ? 'btn-active' : 'btn-secondary' }}" title="Latar PNG hasil unggahan /settings">
                            <i class="bi bi-image"></i> Latar PNG
                        </a>
                    </div>
                </div>
            @endif
            @if ($modeAktif === 'bolakbalik')
                <div class="ctrl-group">
                    <span class="ctrl-label">Balik Kertas</span>
                    <div class="seg">
                        <a href="{{ $qsFlip('long') }}" class="btn {{ $flipAktif === 'long' ? 'btn-active' : 'btn-secondary' }}" title="Balik kertas pada sisi panjang (sisi belakang tegak lurus)">
                            <i class="bi bi-arrow-left-right"></i> Sisi Panjang
                        </a>
                        <a href="{{ $qsFlip('short') }}" class="btn {{ $flipAktif === 'short' ? 'btn-active' : 'btn-secondary' }}" title="Balik kertas pada sisi pendek (sisi belakang terbalik 180°)">
                            <i class="bi bi-arrow-down-up"></i> Sisi Pendek
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <div class="top-bar-tip">
            <i class="bi bi-lightbulb-fill"></i>
            <span>
                Aktifkan <strong>“Background graphics”</strong> pada dialog cetak agar warna kartu tercetak penuh, dan pilih ukuran kertas
                <strong>{{ $modeAktif === 'ktp' ? 'Card Size / CR80' : 'A4' }}</strong>.
            </span>
        </div>
    </div>

    <!-- Sheet Container -->
    <div class="sheet-container">
        <?php if (! empty($catatan)): ?>
            <div class="no-print" style="max-width:640px;margin:0 auto 16px;background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:12px 16px;font-size:12px;color:#92400e;">
                <strong>Perhatian:</strong> {{ $catatan }}
            </div>
        <?php endif; ?>

        <?php if (! empty($terpotong)): ?>
            <div class="no-print" style="max-width:640px;margin:0 auto 16px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 16px;font-size:12px;color:#1e40af;">
                Daftar dipotong pada {{ (int) ($batas_cetak ?? 500) }} kartu pertama supaya halaman tidak macet.
                Pilih satu kelas atau satu siswa untuk mencetak sisanya.
            </div>
        <?php endif; ?>

        <?php if (empty($siswa_list)): ?>
            <div class="no-print" style="max-width:640px;margin:24px auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:32px;text-align:center;">
                <p style="font-size:15px;font-weight:800;color:#0f172a;margin:0 0 6px 0;">Tidak ada kartu untuk dicetak</p>
                <p style="font-size:12px;color:#64748b;margin:0 0 16px 0;">
                    Pilih kelas / siswa lain pada halaman preview kartu, lalu buka kembali halaman cetak ini.
                </p>
                <a href="{{ $previewUrl }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Preview</a>
            </div>
        <?php endif; ?>

        {{-- ══════════════════════════════════════════════════════════════════
             MODE KTP: cetak langsung ukuran kartu KTP (CR80 85.6 × 54 mm)
             Satu kartu per halaman — semua sisi depan, lalu semua sisi belakang.
        ══════════════════════════════════════════════════════════════════ --}}
        @if (! empty($siswa_list) && $modeAktif === 'ktp')
            @php
                // Mode KTP selalu memakai kartu landscape CR80 agar ukurannya sama dengan KTP.
                $ktpDepan = 'admin.presensi.kartu.partials.kartu_dl_depan';
                $ktpBelakang = 'admin.presensi.kartu.partials.kartu_dl_belakang';
            @endphp
            <div class="ktp-hint no-print">
                <i class="bi bi-info-circle-fill"></i>
                <span>
                    Cetak langsung ukuran kartu KTP ({{ $ktp_lebar_mm ?? 85.6 }} × {{ $ktp_tinggi_mm ?? 54 }} mm) — satu kartu per halaman.
                    Cetak semua <strong>sisi depan</strong> dulu, lalu masukkan kembali kartu dan cetak semua <strong>sisi belakang</strong>.
                </span>
            </div>
            <div class="ktp-stack">
                @foreach ($siswa_list as $index => $s)
                    <div class="ktp-cap no-print"><i class="bi bi-front"></i> Sisi Depan • Kartu {{ $index + 1 }}</div>
                    <div class="ktp-page">
                        @include($ktpDepan)
                    </div>
                @endforeach
                @foreach ($siswa_list as $index => $s)
                    <div class="ktp-cap no-print"><i class="bi bi-back"></i> Sisi Belakang • Kartu {{ $index + 1 }}</div>
                    <div class="ktp-page">
                        @include($ktpBelakang)
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════════════
             MODE BOLAK-BALIK: grid A4 dua tahap (semua depan, lalu semua belakang)
        ══════════════════════════════════════════════════════════════════ --}}
        @if (! empty($siswa_list) && $modeAktif === 'bolakbalik')
            @php
                $gridCols = max(1, (int) ($gridAktif['cols'] ?? 2));
                $gridRows = max(1, (int) ($gridAktif['rows'] ?? 4));
            @endphp
            <div class="bolakbalik-hint no-print">
                <i class="bi bi-info-circle-fill"></i>
                <span>
                    Cetak bolak-balik {{ $isPortrait ? 'portrait (5.4 × 8.56 cm)' : 'landscape (8.56 × 5.4 cm)' }} —
                    {{ $gridCols }} × {{ $gridRows }} kartu per lembar A4 ({{ (int) ($gridAktif['per_sheet'] ?? 8) }} kartu/lembar).
                    Arah balik kertas: <strong>{{ $flipAktif === 'short' ? 'Sisi pendek (short edge)' : 'Sisi panjang (long edge)' }}</strong>.
                    Cetak halaman <strong>sisi depan</strong> semua dulu, balik tumpukan kertas, lalu cetak halaman <strong>sisi belakang</strong>.
                </span>
            </div>
            <div class="bolakbalik-stack">
                @foreach ($lembarList as $i => $lembarCetak)
                    <div class="bolakbalik-group">
                        <div class="sheet-label no-print">
                            <span>Lembar {{ $i + 1 }}</span>
                            <span class="side-chip front"><i class="bi bi-front"></i> Sisi Depan</span>
                            <span class="sheet-page-no">Halaman {{ $i * 2 + 1 }}</span>
                        </div>
                        <div class="bolakbalik-sheet" style="grid-template-columns: repeat({{ $gridCols }}, max-content);">
                            @foreach ($lembarCetak['depan'] as $s)
                                @include($partialDepan)
                            @endforeach
                        </div>
                        <div class="sheet-label no-print">
                            <span>Lembar {{ $i + 1 }}</span>
                            <span class="side-chip back"><i class="bi bi-back"></i> Sisi Belakang</span>
                            <span class="sheet-page-no">Halaman {{ $i * 2 + 2 }}</span>
                        </div>
                        <div class="bolakbalik-sheet" style="grid-template-columns: repeat({{ $gridCols }}, max-content);">
                            @foreach ($lembarCetak['belakang'] as $s)
                                @include($partialBelakang)
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($modeAktif === 'lembar')
        {{-- ══════════════════════════════════════════════════════════════════
             MODE 1: DUPLEX LANDSCAPE (8.56 × 5.4 cm) — Dua Sisi Depan Belakang
        ══════════════════════════════════════════════════════════════════ --}}
        <?php if ($desainAktif === 'duplex'): ?>
            <?php foreach ($siswa_list as $s): ?>
                <?php
                $isLaki = ($s->jenis_kelamin ?? 'L') === 'L';
                $ttlClean = \App\Services\KartuSiswaFormatter::ttl($s);
                $nisnClean = \App\Services\KartuSiswaFormatter::nomorSiswa($s);
                $barcodeVal = $s->qr_value;
                ?>
                <div class="duplex-row">
                    <!-- SISI DEPAN (FRONT) LANDSCAPE -->
                    <div class="duplex-card-wrap">
                        <span class="side-badge no-print">Sisi Depan</span>
                        @include('admin.presensi.kartu.partials.kartu_dl_depan')
                    </div>

                    <!-- SISI BELAKANG (BACK) LANDSCAPE -->
                    <div class="duplex-card-wrap">
                        <span class="side-badge no-print">Sisi Belakang</span>
                        @include('admin.presensi.kartu.partials.kartu_dl_belakang')
                    </div>
                </div>
            <?php endforeach; ?>

        {{-- ══════════════════════════════════════════════════════════════════
             MODE 2: DUPLEX PORTRAIT (5.4 × 8.56 cm) — tata letak lembar
        ══════════════════════════════════════════════════════════════════ --}}
        <?php else: ?>
            <?php foreach ($siswa_list as $s): ?>
                <?php
                $isLaki = ($s->jenis_kelamin ?? 'L') === 'L';
                $ttlClean = \App\Services\KartuSiswaFormatter::ttl($s);
                $nisnClean = \App\Services\KartuSiswaFormatter::nomorSiswa($s);
                $barcodeVal = $s->qr_value;
                ?>
                <div class="duplex-row">
                    <!-- SISI DEPAN (FRONT) PORTRAIT -->
                    <div class="duplex-card-wrap">
                        <span class="side-badge no-print">Sisi Depan</span>
                        @include('admin.presensi.kartu.partials.kartu_dp_depan')
                    </div>

                    <!-- SISI BELAKANG (BACK) PORTRAIT -->
                    <div class="duplex-card-wrap">
                        <span class="side-badge no-print">Sisi Belakang</span>
                        @include('admin.presensi.kartu.partials.kartu_dp_belakang')
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        @endif
    </div>

    <!-- QR Code Script -->
    <script src="{{ asset('assets/app/js/qrcode.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Render QR Codes
            const qrBoxes = document.querySelectorAll('.qr-print-box');
            qrBoxes.forEach(function(box) {
                const code = box.getAttribute('data-code');
                const size = parseInt(box.getAttribute('data-size') || '64', 10) || 64;
                if (code) {
                    try {
                        new QRCode(box, {
                            text: String(code),
                            width: size * 2,
                            height: size * 2,
                            colorDark: "#09265e",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.H
                        });
                    } catch (e) {
                        box.textContent = code;
                        box.style.fontSize = '7pt';
                        box.style.fontWeight = '700';
                    }
                } else {
                    box.textContent = '-';
                    box.style.fontSize = '7pt';
                    box.style.color = '#94a3b8';
                }
            });

            // Render Vector Barcodes (Code 39 standard - high sharp resolution for print)
            const barcodeSvgs = document.querySelectorAll('.barcode-svg');
            const code39Map = {
                '0': '000110100', '1': '100100001', '2': '001100001', '3': '101100000',
                '4': '000110001', '5': '100110000', '6': '001110000', '7': '000100101',
                '8': '100100100', '9': '001100100', 'A': '100001001', 'B': '001001001',
                'C': '101001000', 'D': '000011001', 'E': '100011000', 'F': '001011000',
                'G': '000001101', 'H': '100001100', 'I': '001001100', 'J': '000011100',
                'K': '100000011', 'L': '001000011', 'M': '101000010', 'N': '000010011',
                'O': '100010010', 'P': '001010010', 'Q': '000000111', 'R': '100000110',
                'S': '001000110', 'T': '000010110', 'U': '110000001', 'V': '011000001',
                'W': '111000000', 'X': '010010001', 'Y': '110010000', 'Z': '011010000',
                '-': '010000101', '.': '110000100', ' ': '011000100', '*': '010010100'
            };

            barcodeSvgs.forEach(function(svg) {
                let rawText = (svg.getAttribute('data-barcode') || '12345678').toString().toUpperCase().replace(/[^0-9A-Z\-]/g, '');
                if (!rawText) rawText = '000000';
                const fullText = '*' + rawText + '*';
                let x = 0;
                const narrow = 1.0;
                const wide = 2.4;
                const height = 24;
                let rects = '';
                for (let i = 0; i < fullText.length; i++) {
                    const c = code39Map[fullText[i]] || code39Map['0'];
                    for (let j = 0; j < 9; j++) {
                        const isBar = (j % 2 === 0);
                        const w = (c[j] === '1') ? wide : narrow;
                        if (isBar) {
                            rects += '<rect x="' + x.toFixed(1) + '" y="0" width="' + w.toFixed(1) + '" height="' + height + '" fill="#09265e" />';
                        }
                        x += w;
                    }
                    x += narrow; // space between chars
                }
                svg.setAttribute('viewBox', '0 0 ' + x.toFixed(1) + ' ' + height);
                svg.innerHTML = rects;
            });
        });
    </script>
</body>
</html>
