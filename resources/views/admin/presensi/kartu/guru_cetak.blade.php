@php
    // Tata letak cetak: grid (lembar A4) | ktp (satu kartu per halaman CR80).
    $desainAktif = $desain ?? 'landscape';
    $modeAktif = ($mode ?? 'grid') === 'ktp' ? 'ktp' : 'grid';
    $ktpLebarMm = 85.6;
    $ktpTinggiMm = 54.0;
    $qsBase = 'desain=' . $desainAktif;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Absen Guru - {{ $judul_cetak }} - {{ $setting->sekolah ?? 'Sekolah' }}</title>
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
            background-color: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 20px;
        }

        /* Top Action Bar (Screen Only) */
        .top-bar {
            max-width: 1000px;
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
            background-color: var(--accent-600);
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: var(--accent-700);
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
            background-color: var(--accent-700) !important;
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(88,28,135,0.3);
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
            flex-wrap: wrap;
        }
        .top-bar-title > i {
            color: var(--accent-600);
            font-size: 16px;
        }
        .top-bar-count {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 800;
            color: #6b21a8;
            background: #faf5ff;
            border: 1px solid #e9d5ff;
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
        .top-bar-alert {
            width: 100%;
            display: flex;
            align-items: flex-start;
            gap: 7px;
            font-size: 11px;
            font-weight: 600;
            color: #b45309;
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 8px 12px;
        }

        /* Sheet Container (A4 Printable Area) */
        .sheet-container {
            max-width: 210mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 6mm;
        }

        /* Grid Cards: 2 Columns on A4 for Landscape */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 5mm;
        }
        /* Grid Cards: 1 column for Portrait (depan + belakang berpasangan) */
        .card-grid-portrait {
            display: grid;
            grid-template-columns: 1fr;
            gap: 5mm;
            justify-items: center;
        }

        /* Single Mode */
        .single-mode {
            display: flex;
            justify-content: center;
            padding: 20px 0;
        }

        /* Duplex pair: kartu depan + belakang berdampingan */
        .guru-duplex-pair {
            display: flex;
            gap: 4mm;
            align-items: flex-start;
            justify-content: center;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Individual Card Box Landscape */
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
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Individual Card Box Portrait */
        .id-card-portrait {
            width: 58mm;
            height: 92mm;
            border: 1px dashed #94a3b8;
            border-radius: 8px;
            background: #ffffff;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* ═══════════════════════════════════════════════════════════════════
           KARTU GURU PORTRAIT — CSS CETAK (58 × 92 mm)
           Class-class ini dipakai oleh partial kartu_guru_portrait &
           kartu_guru_belakang. Ukuran dalam mm/pt untuk hasil cetak presisi.
        ═══════════════════════════════════════════════════════════════════ */

        /* Dekorasi: lingkaran biru kiri atas & ombak biru bawah */
        .gdeco-circle {
            position: absolute;
            top: -15mm;
            left: -17mm;
            width: 46mm;
            height: 46mm;
            z-index: 0;
            pointer-events: none;
        }
        .gdeco-wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 10mm;
            z-index: 0;
            pointer-events: none;
        }

        /* Kop: logo + dinas + sekolah + alamat */
        .gkop-header {
            position: relative;
            z-index: 1;
            text-align: center;
            flex-shrink: 0;
        }
        .gkop-logo {
            width: 10.5mm;
            height: 10.5mm;
            margin: 0 auto 0.8mm auto;
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
            font-size: 6.4pt;
            font-weight: 800;
            letter-spacing: 0.4px;
            color: #16325c;
            margin: 0;
            line-height: 1.1;
        }
        .gkop-school {
            font-size: 7.2pt;
            font-weight: 900;
            color: #16325c;
            margin: 0.4mm 0 0 0;
            line-height: 1.15;
            max-width: 52mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: clip;
            letter-spacing: -0.3px;
        }
        .gkop-address {
            font-size: 5pt;
            font-weight: 700;
            color: #1f3c66;
            margin: 0.6mm 0 0 0;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: clip;
            max-width: 52mm;
            letter-spacing: -0.2px;
        }

        /* Pil judul kartu dengan garis di kedua sisi */
        .gpill-row {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 1.5mm;
            width: 100%;
            margin: 1.4mm 0 0 0;
        }
        .gpill-line {
            flex: 1;
            height: 0.7mm;
            background: #1e56c8;
            border-radius: 1mm;
        }
        .gpill {
            background: #1e56c8;
            color: #ffffff;
            font-size: 7.8pt;
            font-weight: 900;
            letter-spacing: 0.5px;
            padding: 1mm 3.2mm;
            border-radius: 1.8mm;
            white-space: nowrap;
            line-height: 1;
            flex-shrink: 0;
        }

        /* Sisi depan: latar & layout */
        .id-card-guru-front {
            background: linear-gradient(165deg, #f5fbff 0%, #e8f2fc 55%, #d8eafa 100%);
            padding: 2.5mm 3mm 8mm 3mm;
            align-items: center;
        }

        /* Sisi depan: foto pas foto berbingkai biru */
        .gfront-photo {
            position: relative;
            z-index: 1;
            width: 25mm;
            height: 30mm;
            margin: 2mm auto 0 auto;
            background: #d92b2b;
            border: 0.8mm solid #2f7dd1;
            border-radius: 2.2mm;
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
            margin-top: 2mm;
            width: 100%;
        }
        .gfront-name {
            font-size: 9pt;
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
            height: 0.6mm;
            background: #16325c;
            border-radius: 1mm;
            width: 42mm;
            margin: 1.1mm auto 0 auto;
        }
        .gfront-nip {
            font-size: 7pt;
            font-weight: 800;
            color: #16325c;
            margin: 1mm 0 0 0;
            letter-spacing: 0.2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Sisi belakang: latar & layout */
        .id-card-guru-back {
            background: linear-gradient(165deg, #f5fbff 0%, #e8f2fc 55%, #d8eafa 100%);
            padding: 1.8mm 3.5mm 10mm 3.5mm;
        }

        /* Sisi belakang: garis ganda biru di bawah kop */
        .gback-divider {
            position: relative;
            z-index: 1;
            width: 100%;
            margin: 1.2mm 0 0 0;
            display: flex;
            flex-direction: column;
            gap: 0.5mm;
        }
        .gback-divider span {
            display: block;
            height: 0.55mm;
            background: #1e56c8;
            border-radius: 1mm;
        }
        .gback-table {
            position: relative;
            z-index: 1;
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 6.8pt;
            line-height: 1.3;
            margin-top: 1.2mm;
        }
        .gback-table td {
            padding: 0.4mm 0;
            vertical-align: top;
        }
        .gback-table .lbl {
            width: 16mm;
            font-weight: 800;
            color: #16325c;
            white-space: nowrap;
        }
        .gback-table .sep {
            width: 2.5mm;
            text-align: center;
            font-weight: 800;
            color: #16325c;
        }
        .gback-table .val {
            padding-left: 0.6mm;
            font-weight: 800;
            color: #16325c;
            word-break: break-word;
        }
        .gback-qr {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: center;
            margin-top: 1.2mm;
        }
        .gback-qr-box {
            width: 22mm;
            height: 22mm;
            background: #ffffff;
            border: 0.7mm solid #2f7dd1;
            border-radius: 2mm;
            padding: 0.8mm;
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
            font-size: 6.2pt;
            color: #1e56c8;
            text-align: center;
            line-height: 1.25;
            margin: 1mm 0 0 0;
        }

        /* Landscape Card Layout */
        .card-header-bar {
            background: linear-gradient(135deg, #581c87 0%, #6b21a8 50%, var(--accent-600) 100%);
            color: #ffffff;
            padding: 2mm 3mm;
            display: flex;
            align-items: center;
            gap: 2.5mm;
            border-bottom: 2px solid #a855f7;
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
            object-contain: contain;
        }
        .card-header-text {
            flex: 1;
            min-width: 0;
        }
        .card-header-text .app-title {
            font-size: 6pt;
            font-weight: 700;
            letter-spacing: 0.8px;
            color: #e9d5ff;
            text-transform: uppercase;
            margin: 0;
            line-height: 1;
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
            font-size: 6.5pt;
            font-weight: 800;
            padding: 0.6mm 2mm;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            flex-shrink: 0;
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
        .card-qr-render {
            width: 18mm;
            height: 18mm;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 1px;
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
            color: var(--accent-600);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 0.4mm;
            line-height: 1;
        }
        .card-footer-bar {
            background: #faf5ff;
            border-top: 1px solid #f3e8ff;
            padding: 1mm 3mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 5.5pt;
            color: #6b21a8;
            flex-shrink: 0;
        }
        .card-footer-bar .badge-instansi {
            font-weight: 700;
            color: #581c87;
        }

        /* Mode KTP (Layar): pratinjau satu kartu per halaman */
        .ktp-hint {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            max-width: 820px;
            margin: 0 auto 16px auto;
            background: #faf5ff;
            border: 1px solid #e9d5ff;
            border-left: 4px solid var(--accent-600);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 12px;
            line-height: 1.6;
            color: #6b21a8;
        }
        .ktp-hint > i {
            font-size: 16px;
            color: var(--accent-600);
            flex: 0 0 auto;
            margin-top: 1px;
        }
        .ktp-hint strong {
            color: #581c87;
        }
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
            color: var(--accent-600);
        }
        .ktp-page {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 4px;
            box-shadow: 0 2px 12px rgba(15,23,42,.05);
        }

        /* Print Media Style */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .top-bar {
                display: none !important;
            }
            .no-print {
                display: none !important;
            }
            .sheet-container {
                padding: 4mm !important;
                max-width: 100% !important;
                margin: 0 !important;
            }
            .card-grid {
                gap: 4mm !important;
            }
            .card-grid-portrait {
                gap: 4mm !important;
            }
            .guru-duplex-pair {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .id-card {
                border: 0.5px dashed #64748b !important;
            }
            .id-card-portrait {
                border: 0.5px dashed #64748b !important;
            }
            /* Portrait partials: pastikan dekorasi & teks tercetak */
            .id-card-guru-front,
            .id-card-guru-back {
                border: 0.5px dashed #64748b !important;
            }
            .gdeco-circle {
                top: -15mm !important;
                left: -17mm !important;
                width: 46mm !important;
                height: 46mm !important;
            }
            .gdeco-wave {
                height: 10mm !important;
            }

            /* Mode KTP: satu kartu penuh per halaman CR80 */
            .ktp-mode .sheet-container {
                padding: 0 !important;
                max-width: none !important;
            }
            .ktp-stack {
                display: block !important;
            }
            .ktp-page {
                display: block !important;
                width: {{ $ktpLebarMm }}mm;
                height: {{ $ktpTinggiMm }}mm;
                padding: 0 !important;
                margin: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                overflow: hidden !important;
                break-after: page !important;
                page-break-after: always !important;
            }
            .ktp-page .id-card {
                width: 92mm !important;
                height: 58mm !important;
                border: none !important;
                border-radius: 0 !important;
                transform: scale(0.9304);
                transform-origin: top left;
            }
            .ktp-stack > .ktp-page:last-child {
                break-after: auto !important;
                page-break-after: auto !important;
            }
        }
    </style>
    @if ($modeAktif === 'ktp')
    <style>
        /* Kertas disetel persis seukuran kartu ID-1 CR80 (85.6 × 54 mm). */
        @media print {
            @page {
                size: {{ $ktpLebarMm }}mm {{ $ktpTinggiMm }}mm;
                margin: 0;
            }
        }
    </style>
    @endif
</head>
<body class="{{ $modeAktif === 'ktp' ? 'ktp-mode' : '' }}">

    <!-- Action Bar (Screen Only) -->
    <div class="top-bar no-print">
        <div class="top-bar-head">
            <div class="top-bar-title">
                <i class="bi bi-printer-fill"></i> Siap Cetak
                <span class="top-bar-count">{{ count($guru_list) }} Kartu Guru</span>
                <span class="top-bar-count muted">{{ $judul_cetak }}</span>
            </div>
            <div class="btn-group">
                <a href="{{ url('admin/presensi/guru/kartu?' . $qsBase . '&mode=' . $modeAktif) }}" class="btn btn-secondary">
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
                    <a href="{{ url('admin/presensi/guru/kartu/cetak?desain=landscape&mode=' . $modeAktif) }}" class="btn {{ $desainAktif === 'landscape' ? 'btn-active' : 'btn-secondary' }}" title="Ukuran 9.2 × 5.8 cm">
                        <i class="bi bi-layers-fill"></i> Landscape
                    </a>
                    <a href="{{ url('admin/presensi/guru/kartu/cetak?desain=portrait&mode=' . $modeAktif) }}" class="btn {{ $desainAktif === 'portrait' ? 'btn-active' : 'btn-secondary' }}" title="Portrait 5.8 × 9.2 cm">
                        <i class="bi bi-card-heading"></i> Portrait
                    </a>
                </div>
            </div>
            <div class="ctrl-group">
                <span class="ctrl-label">Tata Letak</span>
                <div class="seg">
                    <a href="{{ url('admin/presensi/guru/kartu/cetak?' . $qsBase . '&mode=grid') }}" class="btn {{ $modeAktif === 'grid' ? 'btn-active' : 'btn-secondary' }}" title="Grid A4 — beberapa kartu per lembar">
                        <i class="bi bi-grid-3x3-gap"></i> Grid A4
                    </a>
                    <a href="{{ url('admin/presensi/guru/kartu/cetak?' . $qsBase . '&mode=ktp') }}" class="btn {{ $modeAktif === 'ktp' ? 'btn-active' : 'btn-secondary' }}" title="Cetak langsung ukuran kartu KTP (85.6 × 54 mm)">
                        <i class="bi bi-credit-card-2-front"></i> Ukuran KTP
                    </a>
                </div>
            </div>
        </div>

        <?php if (!empty($terpotong)): ?>
            <div class="top-bar-alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Daftar dipotong pada {{ (int) ($batas_cetak ?? 500) }} kartu pertama — cetak sisanya dengan memilih satu guru.</span>
            </div>
        <?php endif; ?>

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
        <?php if (empty($guru_list)): ?>
            <div class="no-print" style="max-width:640px;margin:24px auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:32px;text-align:center;">
                <p style="font-size:15px;font-weight:800;color:#0f172a;margin:0 0 6px 0;">Tidak ada kartu guru untuk dicetak</p>
                <p style="font-size:12px;color:#64748b;margin:0 0 16px 0;">
                    <?php if (!empty($catatan)): ?>
                        {{ $catatan }}
                    <?php else: ?>
                        Guru yang dipilih tidak ditemukan pada master data guru. Periksa kembali filternya di halaman preview.
                    <?php endif; ?>
                </p>
                <a href="{{ url('admin/presensi/guru/kartu') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Preview</a>
            </div>
        <?php endif; ?>

        @if ($modeAktif === 'ktp')
            <!-- Mode Cetak Ukuran KTP (CR80 85.6 × 54 mm, satu kartu per halaman) -->
            <div class="ktp-hint no-print">
                <i class="bi bi-info-circle-fill"></i>
                <span>
                    Kertas disetel otomatis ke ukuran kartu <strong>ID-1 CR80</strong> (85,6 × 54 mm). Pilih media
                    <strong>Card Size</strong> pada driver printer (Epson/Canon) agar hasil cetak presisi tanpa memotong.
                </span>
            </div>
            <div class="ktp-stack">
                <?php foreach ($guru_list as $index => $g): ?>
                    <div class="ktp-cap no-print"><i class="bi bi-credit-card-2-front"></i> Kartu {{ $index + 1 }} dari {{ count($guru_list) }}</div>
                    <div class="ktp-page">
                        @include('admin.presensi.kartu.partials.kartu_guru_landscape')
                    </div>
                <?php endforeach; ?>
            </div>
        @else
        <?php if ($desain === 'portrait'): ?>
            <!-- Mode Cetak Portrait — depan + belakang berdampingan per pasang -->
            <div class="{{ $is_single ? 'single-mode' : 'card-grid-portrait' }}">
                <?php foreach ($guru_list as $g): ?>
                    <div class="guru-duplex-pair">
                        @include('admin.presensi.kartu.partials.kartu_guru_portrait')
                        @include('admin.presensi.kartu.partials.kartu_guru_belakang')
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Mode Cetak Landscape (2 Kolom per Lembar A4) -->
            <div class="{{ $is_single ? 'single-mode' : 'card-grid' }}">
                <?php foreach ($guru_list as $g): ?>
                    @include('admin.presensi.kartu.partials.kartu_guru_landscape')
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        @endif
    </div>

    <!-- Script Library QRCode -->
    <script src="{{ asset('assets/app/js/qrcode.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var boxes = document.querySelectorAll('.qr-box');
            boxes.forEach(function(box) {
                var code = box.getAttribute('data-code');
                var size = parseInt(box.getAttribute('data-size') || '64', 10);
                if (code) {
                    try {
                        new QRCode(box, {
                            text: code,
                            width: size,
                            height: size,
                            colorDark: "#0f172a",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.M
                        });
                    } catch (e) {
                        // Jangan sisipkan nilai mentah ke innerHTML — kode kartu
                        // berasal dari data guru dan bisa memuat karakter HTML.
                        box.textContent = code;
                        box.style.fontSize = '7px';
                        box.style.fontWeight = 'bold';
                    }
                }
            });
        });
    </script>
</body>
</html>
