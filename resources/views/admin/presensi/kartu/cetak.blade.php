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
            padding: 14px 20px;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border: 1px solid #e2e8f0;
            flex-wrap: wrap;
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
            background-color: #1e40af !important;
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(30,64,175,0.3);
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
        .dl-back-sign > div:first-child,
        .dp-back-sign > div:first-child {
            position: relative;
            z-index: 2;
        }
        .sign-stamp {
            position: absolute;
            left: 50%;
            top: 50%;
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
            background: #ffffff;
        }
        .dp-header {
            height: 48px;
            background: linear-gradient(135deg, #09265e 0%, #103a8a 55%, #1e56c8 100%);
            padding: 6px 8px 4px 8px;
            display: flex;
            align-items: center;
            gap: 7px;
            position: relative;
            z-index: 2;
        }
        .dp-header-accent {
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 6px;
            pointer-events: none;
            z-index: 3;
        }
        .dp-logo-wrap {
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
        .dp-logo-wrap img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .dp-header-titles {
            flex: 1;
            min-width: 0;
        }
        .dp-header-title {
            font-size: 6.8pt;
            font-weight: 900;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            line-height: 1.15;
            margin: 0;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        .dp-header-sub {
            font-size: 4.6pt;
            font-weight: 700;
            color: #fbbf24;
            margin: 1.5px 0 0 0;
            letter-spacing: 0.2px;
            text-transform: uppercase;
        }

        .dp-photo-wrap {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: center;
            margin-top: 8px;
        }
        .dp-photo-box {
            width: 86px;
            height: 108px;
            background: #dc2626;
            border-radius: 6px;
            overflow: hidden;
            border: 2.5px solid #ffffff;
            box-shadow: 0 3px 8px rgba(0,0,0,0.18);
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
            margin: 6px 12px 0 12px;
            background: linear-gradient(90deg, #09265e 0%, #1e56c8 100%);
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: 900;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 9999px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            box-shadow: 0 2px 6px rgba(9,38,94,0.25);
            letter-spacing: 0.3px;
        }

        .dp-meta-table {
            position: relative;
            z-index: 2;
            margin: 5px auto 0 auto;
            width: 152px;
            border-collapse: collapse;
            font-size: 6.5pt;
            line-height: 1.35;
        }
        .dp-meta-table td {
            padding: 0.5px 0;
            vertical-align: middle;
        }
        .dp-meta-table .dp-meta-lbl {
            width: 48px;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            font-size: 5.8pt;
        }
        .dp-meta-table .dp-meta-sep {
            width: 8px;
            font-weight: 800;
            color: #94a3b8;
            text-align: center;
        }
        .dp-meta-table .dp-meta-val {
            font-weight: 900;
            color: #09265e;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dp-qr-section {
            position: relative;
            z-index: 2;
            margin: auto 8px 8px 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dp-qr-card {
            background: #ffffff;
            border: 1.5px solid #bfdbfe;
            border-radius: 8px;
            padding: 3px;
            box-shadow: 0 2px 8px rgba(37,99,235,0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .dp-qr-box {
            width: 60px;
            height: 60px;
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
            width: 2px;
            height: 46px;
            background: #09265e;
            border-radius: 1px;
            flex-shrink: 0;
        }
        .dp-qr-desc {
            font-size: 6.2pt;
            font-weight: 900;
            color: #09265e;
            text-transform: uppercase;
            line-height: 1.25;
            letter-spacing: 0.3px;
        }
        .dp-front-wave-bottom {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 86px;
            height: 38px;
            pointer-events: none;
            z-index: 1;
        }

        /* BACK: Portrait */
        .card-dp-back {
            background: #ffffff;
            display: flex;
            flex-direction: column;
        }
        .dp-back-header {
            height: 32px;
            background: linear-gradient(135deg, #09265e 0%, #103a8a 55%, #1e56c8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
        }
        .dp-back-title {
            font-size: 7.5pt;
            font-weight: 900;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .dp-back-body {
            position: relative;
            z-index: 2;
            flex: 1;
            padding: 6px 10px 0 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .dp-back-watermark {
            position: absolute;
            top: 42%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 110px;
            height: 110px;
            opacity: 0.04;
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
            font-size: 6.2pt;
            line-height: 1.35;
        }
        .dp-info-table td {
            padding: 1.2px 0;
            vertical-align: top;
        }
        .dp-info-table .dp-lbl {
            width: 56px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            font-size: 5.4pt;
            white-space: nowrap;
        }
        .dp-info-table .dp-sep {
            width: 6px;
            font-weight: 700;
            color: #94a3b8;
        }
        .dp-info-table .dp-val {
            font-weight: 800;
            color: #0f172a;
            line-height: 1.3;
        }

        .dp-rules-section {
            position: relative;
            z-index: 2;
            margin-top: 3px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px 6px;
            text-align: center;
        }
        .dp-rules-pill {
            background: #09265e;
            color: #ffffff;
            font-size: 5.4pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 1.5px 8px;
            border-radius: 9999px;
            display: inline-block;
            margin-bottom: 2px;
        }
        .dp-rules-list {
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 1.5px;
            text-align: left;
        }
        .dp-rule-item {
            display: flex;
            align-items: flex-start;
            font-size: 4.6pt;
            color: #334155;
            line-height: 1.35;
            font-weight: 600;
        }
        .dp-rule-num {
            width: 10px;
            flex-shrink: 0;
            font-weight: 800;
            color: #09265e;
        }
        .dp-rule-text {
            flex: 1;
        }

        .dp-back-sign {
            position: relative;
            z-index: 2;
            text-align: right;
            font-size: 4.6pt;
            color: #475569;
            line-height: 1.25;
            margin-top: 3px;
            padding-right: 2px;
        }
        .dp-back-sign .sign-stamp {
            width: 14mm;
            height: 14mm;
        }
        .dp-sign-name {
            font-weight: 800;
            color: #0f172a;
            text-decoration: underline;
            font-size: 5pt;
            margin-top: 7px;
        }

        .dp-barcode-wrap {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1px;
            margin-top: 3px;
        }
        .dp-barcode-svg {
            width: 110px;
            height: 16px;
        }
        .dp-barcode-text {
            font-family: monospace;
            font-size: 5pt;
            font-weight: 800;
            color: #09265e;
            margin-top: -1px;
        }

        .dp-back-footer-wave-wrap {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 22px;
            margin-top: auto;
        }
        .dp-back-footer-wave {
            width: 100%;
            height: 100%;
            display: block;
        }
        .dp-footer-motto {
            position: absolute;
            bottom: 3px;
            left: 0;
            width: 100%;
            font-size: 5.2pt;
            font-style: italic;
            font-weight: 800;
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
            .dp-header { height: 12.5mm !important; padding: 1.5mm 2mm 0 2mm !important; }
            .dp-logo-wrap { width: 7.5mm !important; height: 7.5mm !important; }
            .dp-header-title { font-size: 6.2pt !important; }
            .dp-header-sub { font-size: 4.2pt !important; }
            .dp-photo-wrap { margin-top: 1.5mm !important; }
            .dp-photo-box { width: 22.5mm !important; height: 28.5mm !important; }
            .dp-name-badge { font-size: 6.5pt !important; margin: 1.5mm 3mm 0 3mm !important; padding: 0.6mm 2mm !important; }
            .dp-meta-table { font-size: 5.2pt !important; margin: 1mm auto 0 auto !important; width: 38mm !important; }
            .dp-meta-table .dp-meta-lbl { width: 12mm !important; font-size: 4.8pt !important; }
            .dp-qr-section { margin: auto 2.5mm 2mm 2.5mm !important; gap: 2.5mm !important; }
            .dp-qr-card { padding: 0.8mm !important; }
            .dp-qr-box { width: 16.5mm !important; height: 16.5mm !important; }
            .dp-qr-divider { height: 13.5mm !important; width: 0.5mm !important; }
            .dp-qr-desc { font-size: 4.8pt !important; }

            .dp-back-header { height: 8.5mm !important; }
            .dp-back-title { font-size: 6.8pt !important; }
            .dp-back-body { padding: 1.5mm 2.5mm 0 2.5mm !important; }
            .dp-info-table { font-size: 5pt !important; line-height: 1.3 !important; }
            .dp-info-table td { padding: 0.6px 0 !important; }
            .dp-info-table .dp-lbl { width: 15mm !important; font-size: 4.4pt !important; }
            .dp-rules-section { padding: 0.8mm 1.5mm !important; margin-top: 0.8mm !important; border-radius: 1.5mm !important; }
            .dp-rules-pill { font-size: 4.2pt !important; padding: 0.3mm 2mm !important; border-radius: 2mm !important; margin-bottom: 0.6mm !important; }
            .dp-rules-list { gap: 0.5mm !important; }
            .dp-rule-item { font-size: 3.8pt !important; line-height: 1.25 !important; }
            .dp-rule-num { width: 2.6mm !important; font-size: 3.8pt !important; }
            .dp-back-sign { font-size: 4pt !important; line-height: 1.2 !important; margin-top: 0.8mm !important; padding-right: 1mm !important; }
            .dp-sign-name { font-size: 4.4pt !important; margin-top: 1.6mm !important; }
            .dp-barcode-wrap { margin-top: 0.8mm !important; }
            .dp-barcode-svg { width: 28mm !important; height: 3.8mm !important; }
            .dp-barcode-text { font-size: 4pt !important; }
            .dp-back-footer-wave-wrap { height: 5.4mm !important; }
            .dp-footer-motto { font-size: 4.2pt !important; bottom: 0.8mm !important; }

            @page {
                size: A4 portrait;
                margin: 8mm 10mm;
            }
        }
    </style>
</head>
<body>

    <!-- Top Action Bar (Screen Only) -->
    <?php
    $desainAktif = $desain ?? 'duplex';
    $qKelas = (int) ($_GET['id_kelas'] ?? 0);
    $qSiswa = (int) ($_GET['id_siswa'] ?? 0);
    $baseQs = 'id_kelas=' . $qKelas . ($qSiswa ? '&id_siswa=' . $qSiswa : '');
    ?>
    <div class="top-bar no-print">
        <div class="top-bar-info">
            <span style="display:inline-flex;align-items:center;gap:6px;font-weight:800;color:#1e40af;">
                <i class="bi bi-printer-fill"></i> Siap Cetak: {{ count($siswa_list) }} Kartu ({{ $nama_kelas }})
            </span>
            <span style="color: #cbd5e1;">|</span>
            <span style="display:inline-flex;gap:6px;vertical-align:middle;flex-wrap:wrap;">
                <a href="?{{ $baseQs }}&desain=duplex" class="btn {{ $desainAktif === 'duplex' ? 'btn-active' : 'btn-secondary' }}" title="Ukuran 8.56 × 5.4 cm">
                    <i class="bi bi-layers-fill"></i> Landscape ✦
                </a>
                <a href="?{{ $baseQs }}&desain=duplex_portrait" class="btn {{ $desainAktif === 'duplex_portrait' ? 'btn-active' : 'btn-secondary' }}" title=" Portrait 5.4 × 8.56 cm">
                    <i class="bi bi-card-heading"></i> Portrait ✦
                </a>
            </span>
            <span style="font-size: 11px; color: #64748b; margin-left: 4px;">
                💡 Tips: Centang <strong>"Background graphics"</strong> pada print dialog agar warna tercetak penuh.
            </span>
        </div>
        <div class="btn-group">
            <button onclick="window.history.back()" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </button>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Cetak Sekarang
            </button>
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
                <button onclick="window.history.back()" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Preview</button>
            </div>
        <?php endif; ?>

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
                    </div>

                    <!-- SISI BELAKANG (BACK) LANDSCAPE -->
                    <div class="duplex-card-wrap">
                        <span class="side-badge no-print">Sisi Belakang</span>
                        <div class="card-dl card-dl-back">
                            <!-- Header Back -->
                            <div class="dl-back-header-bar">
                                <h4 class="dl-back-title">DATA PESERTA DIDIK</h4>
                            </div>

                            <!-- Watermark logo transparan -->
                            <div class="dl-back-watermark">
                                <img src="{{ $logo_url }}" alt="Watermark" onerror="this.src='{{ asset('assets/img/logo.png') }}'">
                            </div>

                            <div class="dl-back-body">
                                <!-- Left Column: Data Siswa + Barcode -->
                                <div class="dl-back-col-left">
                                    <div class="dl-back-sublabel">Identitas Siswa</div>
                                    <table class="dl-back-table">
                                        <tr>
                                            <td class="b-lbl">Nama</td>
                                            <td class="b-sep">:</td>
                                            <td class="b-val" title="{{ $s->nama }}">{{ $s->nama }}</td>
                                        </tr>
                                        <tr>
                                            <td class="b-lbl">JK</td>
                                            <td class="b-sep">:</td>
                                            <td class="b-val">{{ $isLaki ? 'Laki-laki' : 'Perempuan' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="b-lbl">TTL</td>
                                            <td class="b-sep">:</td>
                                            <td class="b-val ttl-wrap" title="{{ $ttlClean }}">{{ $ttlClean }}</td>
                                        </tr>
                                        <tr>
                                            <td class="b-lbl">NISN</td>
                                            <td class="b-sep">:</td>
                                            <td class="b-val">{{ $nisnClean }}</td>
                                        </tr>
                                        <tr>
                                            <td class="b-lbl">Kelas</td>
                                            <td class="b-sep">:</td>
                                            <td class="b-val">{{ $s->nama_kelas }}</td>
                                        </tr>
                                    </table>

                                    <!-- Barcode di bawah tabel -->
                                    <div class="dl-barcode-box">
                                        <svg class="barcode-svg" data-barcode="{{ $barcodeVal }}"></svg>
                                        <span class="dl-barcode-text">{{ $barcodeVal }}</span>
                                    </div>
                                </div>

                                <!-- Right Column: Tata Tertib & Pengesahan Kepala Sekolah -->
                                <div class="dl-back-col-right">
                                    <div class="dl-back-rules-box">
                                        <div class="dl-back-pill">TATA TERTIB SISWA</div>
                                        <div class="dl-rules-list">
                                            <div class="dl-rule-item">
                                                <span class="dl-rule-num">1.</span>
                                                <span class="dl-rule-text">Wajib membawa kartu saat KBM & presensi.</span>
                                            </div>
                                            <div class="dl-rule-item">
                                                <span class="dl-rule-num">2.</span>
                                                <span class="dl-rule-text">Merupakan identitas resmi siswa di sekolah.</span>
                                            </div>
                                            <div class="dl-rule-item">
                                                <span class="dl-rule-num">3.</span>
                                                <span class="dl-rule-text">Jika kartu hilang, segera lapor ke sekolah.</span>
                                            </div>
                                            <div class="dl-rule-item">
                                                <span class="dl-rule-num">4.</span>
                                                <span class="dl-rule-text">Kartu tidak boleh dipindahtangankan.</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pengesahan Kepala Sekolah -->
                                    <div class="dl-back-sign">
                                        <div>{{ $setting->kota ?? 'Kupang' }}, {{ date('Y') }}</div>
                                        <div class="sign-stamp-area">
                                        @if (!empty($stampel_url))
                                            <img class="sign-stamp" src="{{ $stampel_url }}" alt="Cap Sekolah">
                                        @endif
                                            <div style="font-weight:700;">Kepala Sekolah,</div>
                                            <div class="dl-sign-name" style="margin-top:10px;">{{ $setting->kepsek ?? 'Kepala Sekolah' }}</div>
                                            <div>NIP. {{ $setting->nip ?? '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Card: Motto Sekolah -->
                            <div class="dl-back-footer">
                                <span class="dl-motto">Belajar, Berprestasi, Berakhlak Mulia</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        {{-- ══════════════════════════════════════════════════════════════════
             MODE 2: DUPLEX PORTRAIT (5.4 × 8.56 cm)
        ══════════════════════════════════════════════════════════════════ --}}
        <?php elseif ($desainAktif === 'duplex_portrait'): ?>
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

                            <!-- Foto Siswa (Merah) -->
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
                    </div>

                    <!-- SISI BELAKANG (BACK) PORTRAIT -->
                    <div class="duplex-card-wrap">
                        <span class="side-badge no-print">Sisi Belakang</span>
                        <div class="card-dp card-dp-back">
                            <div class="dp-back-header">
                                <h4 class="dp-back-title">DATA PESERTA DIDIK</h4>
                                <!-- Accent ombak di bawah header back -->
                                <svg class="dp-header-accent" viewBox="0 0 204 6" preserveAspectRatio="none">
                                    <path d="M0,0 L204,0 L204,2 Q150,6 102,3 Q50,0 0,3 Z" fill="#f59e0b" />
                                    <path d="M0,3 Q50,0 102,3 Q150,6 204,2 L204,4 Q150,7 102,4 Q50,1 0,4 Z" fill="#38bdf8" />
                                </svg>
                            </div>

                            <!-- Watermark -->
                            <div class="dp-back-watermark">
                                <img src="{{ $logo_url }}" alt="Watermark" onerror="this.src='{{ asset('assets/img/logo.png') }}'">
                            </div>

                            <div class="dp-back-body">
                                <table class="dp-info-table">
                                    <tr>
                                        <td class="dp-lbl">Nama</td>
                                        <td class="dp-sep">:</td>
                                        <td class="dp-val" title="{{ $s->nama }}">{{ $s->nama }}</td>
                                    </tr>
                                    <tr>
                                        <td class="dp-lbl">Jenis Kelamin</td>
                                        <td class="dp-sep">:</td>
                                        <td class="dp-val">{{ $isLaki ? 'Laki-laki' : 'Perempuan' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="dp-lbl">Tempat Tgl Lahir</td>
                                        <td class="dp-sep">:</td>
                                        <td class="dp-val ttl-wrap" title="{{ $ttlClean }}">{{ $ttlClean }}</td>
                                    </tr>
                                    <tr>
                                        <td class="dp-lbl">NIS/NISN</td>
                                        <td class="dp-sep">:</td>
                                        <td class="dp-val">{{ ($s->nis ? $s->nis . ' / ' : '') . ($s->nisn ?: '-') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="dp-lbl">Kelas</td>
                                        <td class="dp-sep">:</td>
                                        <td class="dp-val">{{ $s->nama_kelas }}</td>
                                    </tr>
                                </table>
                                <!-- Divider -->
                                <div style="height:1px;background:linear-gradient(90deg,transparent,#e2e8f0,transparent);margin:4px 0 3px;"></div>

                                <div class="dp-rules-section">
                                    <div class="dp-rules-pill">TATA TERTIB SISWA</div>
                                    <div class="dp-rules-list">
                                        <div class="dp-rule-item">
                                            <span class="dp-rule-num">1.</span>
                                            <span class="dp-rule-text">Siswa wajib membawa kartu pelajar setiap saat.</span>
                                        </div>
                                        <div class="dp-rule-item">
                                            <span class="dp-rule-num">2.</span>
                                            <span class="dp-rule-text">Kartu ini digunakan sebagai identitas resmi sekolah.</span>
                                        </div>
                                        <div class="dp-rule-item">
                                            <span class="dp-rule-num">3.</span>
                                            <span class="dp-rule-text">Jika kartu hilang, segera lapor ke pihak sekolah.</span>
                                        </div>
                                        <div class="dp-rule-item">
                                            <span class="dp-rule-num">4.</span>
                                            <span class="dp-rule-text">Kartu tidak boleh dipindahtangankan.</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pengesahan Kepala Sekolah -->
                                <div class="dp-back-sign">
                                    <div>{{ $setting->kota ?? 'Kupang' }}, {{ date('Y') }}</div>
                                    <div class="sign-stamp-area">
                                    @if (!empty($stampel_url))
                                        <img class="sign-stamp" src="{{ $stampel_url }}" alt="Cap Sekolah">
                                    @endif
                                        <div style="font-weight:700;">Kepala Sekolah,</div>
                                        <div class="dp-sign-name">{{ $setting->kepsek ?? 'Kepala Sekolah' }}</div>
                                        <div>NIP. {{ $setting->nip ?? '-' }}</div>
                                    </div>
                                </div>

                                <div class="dp-barcode-wrap">
                                    <svg class="barcode-svg dp-barcode-svg" data-barcode="{{ $barcodeVal }}"></svg>
                                    <span class="dp-barcode-text">{{ $barcodeVal }}</span>
                                </div>
                            </div>

                            <!-- Footer wave with motto -->
                            <div class="dp-back-footer-wave-wrap">
                                <svg class="dp-back-footer-wave" viewBox="0 0 204 22" preserveAspectRatio="none">
                                    <path d="M0,8 C60,0 140,0 204,8 L204,22 L0,22 Z" fill="#09265e" />
                                    <path d="M0,7 C60,-1 140,-1 204,7" fill="none" stroke="#f59e0b" stroke-width="1.8" />
                                    <path d="M0,9 C60,1 140,1 204,9" fill="none" stroke="#38bdf8" stroke-width="1" />
                                </svg>
                                <div class="dp-footer-motto">Belajar, Berprestasi, Berakhlak Mulia</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
                            correctLevel: QRCode.CorrectLevel.M
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
