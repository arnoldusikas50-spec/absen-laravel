@extends('layouts.main')

@section('styles')
<style>
    .card-preview {
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .card-preview:hover {
        transform: translateY(-3px);
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
       1. DUPLEX LANDSCAPE CARD (85.6 mm × 54 mm ISO ID-1 CR80)
       Screen: 324px × 204px (96 DPI)
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
        text-align: left;
    }
    .card-dl-front {
        background: #ffffff;
    }
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
        background: #dc2626;
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
    .dl-barcode-box svg, .barcode-svg {
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
    .dl-sign-name {
        font-weight: 800;
        color: #0f172a;
        text-decoration: underline;
    }
    /* Cap / Stempel Sekolah (kolom setting.stampel, diatur di /settings) */
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
        transform: translate(-50%, -50%);
        width: 20mm;
        height: 20mm;
        object-fit: contain;
        opacity: .85;
        pointer-events: none;
        z-index: 0;
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
       2. DUPLEX PORTRAIT CARD (54 mm × 85.6 mm)
       Screen: 204px × 324px (96 DPI)
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
        text-align: left;
    }
    .card-dp-front {
        background: linear-gradient(170deg, #eaf3fc 0%, #dbeafb 45%, #cfe4f9 100%);
    }
    /* ═══ Template Latar PNG (kartu duplex portrait) ═══
       .dp-bg-img menempel penuh di belakang semua konten; hiasan vektor,
       kop, dan watermark disembunyikan agar latar unggahan /settings
       tampil bersih — hanya data dinamis yang tercetak di atasnya. */
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
        margin: 2px 0 0 0;
        line-height: 1.15;
        max-width: 190px;
        margin-left: auto;
        margin-right: auto;
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
        margin-top: 4px;
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
</style>
@endsection

@section('content')
<?php
    // Di-set sekali di awal section lalu dipakai seluruh link & switcher desain.
    $basePath = $base_path ?? 'admin/presensi/siswa/kartu';
    $desainAktif = $desain ?? 'duplex';
    $modeAktif = in_array(($mode ?? 'lembar'), ['lembar', 'bolakbalik', 'ktp'], true) ? ($mode ?? 'lembar') : 'lembar';
    $flipAktif = ($flip ?? 'long') === 'short' ? 'short' : 'long';
    // Template latar kartu duplex portrait: 'vektor' atau 'png' (unggahan /settings).
    $templateAktif = ($template ?? 'vektor') === 'png' ? 'png' : 'vektor';
    // PNG hanya menempel pada duplex portrait dan bila kedua latarnya terunggah.
    $templatePngSiap = $desainAktif === 'duplex_portrait'
        && ! empty($kartu_bg_depan) && ! empty($kartu_bg_belakang);
    $qsCetak = 'id_kelas=' . $id_kelas . ($id_siswa ? '&id_siswa=' . $id_siswa : '');
    // Admin bisa mengelola unggahan latar di menu Pengaturan; guru tidak punya akses.
    $bisaSetelan = str_starts_with((string) $basePath, 'admin');
    $pngHref = $templatePngSiap
        ? url($basePath . '?' . $qsCetak . '&desain=duplex_portrait&mode=' . $modeAktif . ($modeAktif === 'bolakbalik' ? '&flip=' . $flipAktif : '') . '&template=png&v=5')
        : ($bisaSetelan ? url('setting') : '#');
    // Bawa serta mode, arah flip & template saat berganti desain agar pilihannya tidak ter-reset.
    $qsSertaan = '&mode=' . $modeAktif
        . ($modeAktif === 'bolakbalik' ? '&flip=' . $flipAktif : '')
        . ($desainAktif === 'duplex_portrait' ? '&template=' . $templateAktif : '');
?>
<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-qr-code-scan"></i> Kartu Presensi Siswa
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Cetak Kartu Absen Siswa QR</h1>
            <p class="text-xs text-slate-500 mt-1">Cetak kartu ID presensi siswa dengan QR Code standar untuk scan absensi harian & KBM.</p>
            <div class="mt-4 flex flex-wrap items-center gap-1.5 text-[11px] font-bold">
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-blue-100 bg-blue-50/70 px-2.5 py-1 text-blue-700">
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-blue-600 text-[9px] text-white">1</span> Pilih desain
                </span>
                <i class="bi bi-chevron-right text-slate-300"></i>
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-600">
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-400 text-[9px] text-white">2</span> Pilih kelas
                </span>
                <i class="bi bi-chevron-right text-slate-300"></i>
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-600">
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-400 text-[9px] text-white">3</span> Cetak
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <a id="btnCetakLembar"
               href="{{ url($basePath . '/cetak?' . $qsCetak . '&desain=' . $desainAktif . '&mode=' . $modeAktif . ($modeAktif === 'bolakbalik' ? '&flip=' . $flipAktif : '') . ($desainAktif === 'duplex_portrait' ? '&template=' . $templateAktif : '') . '&v=5') }}"
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-blue-500/20 no-underline">
                <i class="bi bi-printer-fill"></i> Cetak Kartu
            </a>
            <a href="{{ $bisaSetelan ? url('scanner') : url('guru/presensi-siswa' . (auth()->id() ? '?h=' . \App\Http\Controllers\Guru\PresensiSiswaQrController::generateHash((int) auth()->id()) : '')) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-camera-fill"></i> Buka Scanner QR
            </a>
        </div>
    </div>

    <!-- Filter & Control Panel -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <!-- Judul Panel Opsi -->
        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-100">
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-blue-50 text-blue-600 shrink-0">
                <i class="bi bi-sliders"></i>
            </span>
            <div class="min-w-0">
                <p class="text-sm font-extrabold text-slate-800 leading-tight">Opsi Cetak Kartu</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Pilih desain kartu dan tata letak cetak sebelum mencetak.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
        <!-- Pilihan Desain Kartu -->
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Desain & Format Kartu</label>
            <div class="inline-flex p-1 bg-slate-100 rounded-xl gap-1 flex-wrap" role="tablist" aria-label="Pilihan desain kartu">
                <a href="{{ url($basePath . '?' . $qsCetak . '&desain=duplex' . $qsSertaan . '&v=5') }}"
                   aria-current="{{ $desainAktif === 'duplex' ? 'page' : 'false' }}"
                   title="Kartu pelajar horizontal 8,56 × 5,4 cm, dicetak 2 sisi"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $desainAktif === 'duplex' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/70' }}">
                    <i class="bi bi-layers-fill"></i> Kartu Pelajar (Landscape)
                </a>
                <a href="{{ url($basePath . '?' . $qsCetak . '&desain=duplex_portrait' . $qsSertaan . '&v=5') }}"
                   aria-current="{{ $desainAktif === 'duplex_portrait' ? 'page' : 'false' }}"
                   title="Kartu pelajar vertikal 5,4 × 8,56 cm, dicetak 2 sisi"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $desainAktif === 'duplex_portrait' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/70' }}">
                    <i class="bi bi-card-heading"></i> Kartu Pelajar (Portrait)
                </a>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">
                @if($desainAktif === 'duplex')
                    <strong>Kartu Pelajar Landscape:</strong> desain horizontal 8,56 × 5,4 cm dengan foto, identitas, dan QR Code — dicetak 2 sisi (depan + belakang).
                @else
                    <strong>Kartu Pelajar Portrait:</strong> desain vertikal 5,4 × 8,56 cm dengan foto dan QR Code — dicetak 2 sisi (depan + belakang).
                @endif
            </p>
        </div>

        <!-- Pilihan Tata Letak / Mode Cetak -->
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tata Letak Cetak</label>
            <div class="inline-flex p-1 bg-slate-100 rounded-xl gap-1 flex-wrap" role="tablist" aria-label="Pilihan tata letak cetak">
                <a href="{{ url($basePath . '?' . $qsCetak . '&desain=' . $desainAktif . '&mode=lembar&v=5') }}"
                   aria-current="{{ $modeAktif === 'lembar' ? 'page' : 'false' }}"
                   title="Sisi depan & belakang berdampingan dalam satu lembar"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $modeAktif === 'lembar' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/70' }}">
                    <i class="bi bi-file-earmark"></i> Lembar
                </a>
                <a href="{{ url($basePath . '?' . $qsCetak . '&desain=' . $desainAktif . '&mode=bolakbalik&flip=' . $flipAktif . '&v=5') }}"
                   aria-current="{{ $modeAktif === 'bolakbalik' ? 'page' : 'false' }}"
                   title="Grid A4 dua tahap: semua depan, lalu semua belakang"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $modeAktif === 'bolakbalik' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/70' }}">
                    <i class="bi bi-layout-text-window-reverse"></i> Bolak-balik (Grid A4)
                </a>
                <a href="{{ url($basePath . '?' . $qsCetak . '&desain=' . $desainAktif . '&mode=ktp&v=5') }}"
                   aria-current="{{ $modeAktif === 'ktp' ? 'page' : 'false' }}"
                   title="Cetak langsung ke kartu CR80 85,6 × 54 mm"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $modeAktif === 'ktp' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/70' }}">
                    <i class="bi bi-credit-card-2-front"></i> Ukuran KTP (CR80)
                </a>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">
                @if($modeAktif === 'ktp')
                    <strong>Ukuran KTP :</strong> Cetak langsung ke kartu CR80 85.6 × 54 mm, satu kartu per halaman — cocok untuk printer kartu.
                @elseif($modeAktif === 'bolakbalik')
                    <strong>Bolak-balik :</strong> Grid A4 dua tahap — cetak semua sisi depan, balik tumpukan kertas, lalu cetak semua sisi belakang.
                @else
                    <strong>Lembar :</strong> Sisi depan & belakang dicetak berdampingan dalam satu lembar.
                @endif
            </p>
            @if($modeAktif === 'bolakbalik')
                <div class="mt-3 flex items-center gap-2 flex-wrap">
                    <span class="text-[11px] font-bold text-slate-600">Arah balik kertas:</span>
                    <a href="{{ url($basePath . '?' . $qsCetak . '&desain=' . $desainAktif . '&mode=bolakbalik&flip=long&v=5') }}"
                       aria-current="{{ $flipAktif === 'long' ? 'page' : 'false' }}"
                       class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all no-underline {{ $flipAktif === 'long' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/70' }}">
                        <i class="bi bi-arrow-left-right"></i> Sisi Panjang (Long Edge)
                    </a>
                    <a href="{{ url($basePath . '?' . $qsCetak . '&desain=' . $desainAktif . '&mode=bolakbalik&flip=short&v=5') }}"
                       aria-current="{{ $flipAktif === 'short' ? 'page' : 'false' }}"
                       class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all no-underline {{ $flipAktif === 'short' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/70' }}">
                        <i class="bi bi-arrow-down-up"></i> Sisi Pendek (Short Edge)
                    </a>
                </div>
            @endif
        </div>

        @if($desainAktif === 'duplex_portrait')
        <!-- Pilihan Template Kartu (hanya duplex portrait) -->
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Template Kartu</label>
            <div class="inline-flex p-1 bg-slate-100 rounded-xl gap-1 flex-wrap" role="tablist" aria-label="Pilihan template kartu">
                <a href="{{ url($basePath . '?' . $qsCetak . '&desain=duplex_portrait&mode=' . $modeAktif . ($modeAktif === 'bolakbalik' ? '&flip=' . $flipAktif : '') . '&template=vektor&v=5') }}"
                   aria-current="{{ $templateAktif === 'vektor' ? 'page' : 'false' }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $templateAktif === 'vektor' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/70' }}">
                    <i class="bi bi-vector-pen"></i> Vektor
                </a>
                <a href="{{ $pngHref }}"
                   aria-current="{{ $templateAktif === 'png' ? 'page' : 'false' }}"
                   {{ $templatePngSiap ? '' : ($bisaSetelan ? 'title="Unggah latar kartu depan &amp; belakang di menu Pengaturan dulu" style="opacity:.45;"' : 'aria-disabled="true" title="Latar kartu diunggah admin melalui menu Pengaturan" tabindex="-1" style="pointer-events:none;opacity:.45;"') }}
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $templateAktif === 'png' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/70' }}">
                    <i class="bi bi-image"></i> Latar PNG
                </a>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">
                @if($templateAktif === 'png')
                    <strong>Latar PNG :</strong> Desain memakai latar PNG yang diunggah di menu <strong>Pengaturan</strong> — kop, ombak, dan hiasan vektor disembunyikan, hanya data (foto, nama, QR, barcode) yang tercetak di atasnya.
                @elseif($templatePngSiap)
                    <strong>Vektor :</strong> Desain kartu bawaan. Latar PNG sudah tersedia — pilih "Latar PNG" untuk memakainya.
                @elseif($bisaSetelan)
                    <strong>Vektor :</strong> Desain kartu bawaan. Belum ada latar PNG — <strong>klik "Latar PNG"</strong> untuk mengunggah Latar Depan &amp; Belakang di menu Pengaturan.
                @else
                    <strong>Vektor :</strong> Desain kartu bawaan. Latar PNG hanya aktif bila admin mengunggah Latar Kartu Depan &amp; Belakang di menu Pengaturan.
                @endif
            </p>
        </div>
        @endif

        <div class="flex items-start gap-2.5 rounded-xl border border-blue-100 bg-blue-50/60 px-3.5 py-3 text-[11px] text-blue-800" style="grid-column: 1 / -1;">
            <i class="bi bi-lightbulb-fill mt-0.5 text-blue-500"></i>
            <p class="font-semibold leading-relaxed m-0">
                Hasil cetak terbaik: gunakan skala <strong>100%</strong>, pilih kertas <strong>{{ $modeAktif === 'ktp' ? 'Card Size / CR80' : 'A4' }}</strong>, dan aktifkan <strong>Background graphics</strong>.
            </p>
        </div>
        </div>

        <hr class="my-5 border-slate-100">

        <!-- Filter Data -->
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Filter Data Siswa</label>
        <form method="get" action="{{ url($basePath) }}" id="filterForm" data-base="{{ url($basePath) }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <input type="hidden" name="desain" id="inputDesain" value="{{ $desainAktif }}">
            <input type="hidden" name="mode" id="inputMode" value="{{ $modeAktif }}">
            <input type="hidden" name="flip" id="inputFlip" value="{{ $flipAktif }}">
            <!-- Pilih Kelas -->
            <div class="md:col-span-4">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Kelas</label>
                <select name="id_kelas" id="selectKelas" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all hover:border-slate-300">
                    <option value="0">-- Semua Kelas --</option>
                    <?php foreach ($kelases as $k): ?>
                        <option value="{{ $k->id_kelas }}" {{ ($id_kelas == $k->id_kelas) ? 'selected' : '' }}>
                            Kelas {{ $k->nama_kelas }} ({{ $k->jumlah_siswa }} Siswa)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Pilih Siswa (Per Orang) -->
            <div class="md:col-span-4">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Siswa (Opsional / Per Orang)</label>
                <select name="id_siswa" id="selectSiswa" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all hover:border-slate-300">
                    <option value="0">-- Semua Siswa di Kelas Ini --</option>
                    <?php foreach ($siswa_options as $so): ?>
                        <option value="{{ $so->id_siswa }}" {{ ($id_siswa == $so->id_siswa) ? 'selected' : '' }}>
                            {{ $so->nama }} ({{ $so->id_label ?? 'ID' }}: {{ $so->id_value ?? '-' }})
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Pencarian Cepat Nama / NIS -->
            <div class="md:col-span-4">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Filter Pencarian</label>
                <div class="relative">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="searchInput" placeholder="Cari nama, NIS, atau NISN siswa..."
                           aria-label="Cari nama, NIS, atau NISN siswa"
                           class="w-full pl-9 pr-9 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all hover:border-slate-300">
                    <button type="button" id="clearSearch" aria-label="Bersihkan pencarian"
                            class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 inline-flex h-5 w-5 items-center justify-center rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition-colors">
                        <i class="bi bi-x-circle-fill text-[13px]"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-end pt-1" style="grid-column: 1 / -1;">
                <button type="button" id="resetFilter"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset filter
                </button>
            </div>
        </form>
        </div>

        <?php if (!empty($catatan)): ?>
            <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-xs text-amber-800">
                <i class="bi bi-exclamation-triangle-fill mt-0.5 text-amber-500"></i>
                <p class="font-semibold leading-relaxed">{{ $catatan }}</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($terpotong)): ?>
            <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-3 text-xs text-blue-800">
                <i class="bi bi-info-circle-fill mt-0.5 text-blue-500"></i>
                <p class="font-semibold leading-relaxed">
                    Daftar dipotong pada {{ (int) ($batas_cetak ?? 500) }} kartu pertama supaya halaman tidak macet.
                    Pilih satu kelas atau satu siswa untuk mencetak sisanya.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Live Card Grid Preview -->
    <?php if (empty($siswa_list)): ?>
        <div class="bg-white rounded-3xl p-12 text-center border border-slate-200/80">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 text-2xl mx-auto mb-3">
                <i class="bi bi-person-x"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800">Tidak Ada Siswa Ditemukan</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                <?php if (!empty($catatan)): ?>
                    {{ $catatan }}
                <?php else: ?>
                    Silakan pilih kelas lain atau periksa data penempatan kelas siswa pada Tahun Pelajaran aktif.
                <?php endif; ?>
            </p>
            @if ($bisaSetelan)
                <a href="{{ url('datasiswa') }}"
                   class="inline-flex items-center gap-2 mt-4 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors no-underline">
                    <i class="bi bi-people-fill"></i> Buka Data Siswa
                </a>
            @endif
        </div>
    <?php else: ?>

        {{-- ══════════════════════════════════════════════════════════════════
             1. DUPLEX LANDSCAPE PREVIEW (8.56 × 5.4 cm)
        ══════════════════════════════════════════════════════════════════ --}}
        <?php if ($desainAktif === 'duplex'): ?>
        <div id="cardGrid" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <?php foreach ($siswa_list as $s): ?>
                <div class="student-card-item card-preview bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs"
                     data-name="{{ strtolower($s->nama) }}"
                     data-nis="{{ strtolower($s->nis ?? '') }}"
                     data-nisn="{{ strtolower($s->nisn ?? '') }}">
                    @php
                        $inisialSiswa = collect(preg_split('/\s+/', trim((string) $s->nama)))
                            ->filter()
                            ->take(2)
                            ->map(fn ($kata) => mb_strtoupper(mb_substr($kata, 0, 1)))
                            ->implode('');
                    @endphp
                    <div class="flex items-start justify-between gap-3 mb-3 pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-blue-700 text-white text-[11px] font-extrabold tracking-wide shrink-0">{{ $inisialSiswa }}</span>
                            <div class="min-w-0">
                                <h5 class="text-[12px] font-extrabold text-slate-800 leading-tight truncate m-0" title="{{ $s->nama }}">{{ $s->nama }}</h5>
                                <p class="text-[10px] font-semibold text-slate-500 m-0 mt-0.5 truncate">
                                    <span class="font-mono text-slate-600">{{ $s->nis ?: ($s->nisn ?: 'Siswa') }}</span>
                                    <span class="text-slate-300 mx-1">•</span>{{ $s->nama_kelas }}
                                </p>
                            </div>
                        </div>
                        <a href="{{ url($basePath . '/cetak?id_siswa=' . $s->id_siswa . '&id_kelas=' . $s->id_kelas . '&desain=duplex' . $qsSertaan . '&v=5') }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-[11px] font-bold transition-colors no-underline border border-blue-200/60">
                            <i class="bi bi-printer"></i> Cetak Kartu
                        </a>
                    </div>
                    <!-- Front & Back side-by-side: 100% Identik dengan Tampilan Cetak -->
                    <div class="flex flex-wrap items-center justify-center gap-4">
                        <!-- SISI DEPAN -->
                        <div class="card-stage">
                            <span class="card-stage-label"><i class="bi bi-credit-card-2-front"></i> Sisi Depan</span>
                            @include('admin.presensi.kartu.partials.kartu_dl_depan')
                        </div>

                        <!-- SISI BELAKANG -->
                        <div class="card-stage">
                            <span class="card-stage-label"><i class="bi bi-arrow-repeat"></i> Sisi Belakang</span>
                            @include('admin.presensi.kartu.partials.kartu_dl_belakang')
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════
             2. DUPLEX PORTRAIT PREVIEW (5.4 × 8.56 cm)
        ══════════════════════════════════════════════════════════════════ --}}
        <?php elseif ($desainAktif === 'duplex_portrait'): ?>
        <div id="cardGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($siswa_list as $s): ?>
                <div class="student-card-item card-preview bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs"
                     data-name="{{ strtolower($s->nama) }}"
                     data-nis="{{ strtolower($s->nis ?? '') }}"
                     data-nisn="{{ strtolower($s->nisn ?? '') }}">
                    @php
                        $inisialSiswa = collect(preg_split('/\s+/', trim((string) $s->nama)))
                            ->filter()
                            ->take(2)
                            ->map(fn ($kata) => mb_strtoupper(mb_substr($kata, 0, 1)))
                            ->implode('');
                    @endphp
                    <div class="flex items-start justify-between gap-3 mb-3 pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-blue-700 text-white text-[11px] font-extrabold tracking-wide shrink-0">{{ $inisialSiswa }}</span>
                            <div class="min-w-0">
                                <h5 class="text-[12px] font-extrabold text-slate-800 leading-tight truncate m-0" title="{{ $s->nama }}">{{ $s->nama }}</h5>
                                <p class="text-[10px] font-semibold text-slate-500 m-0 mt-0.5 truncate">
                                    <span class="font-mono text-slate-600">{{ $s->nis ?: ($s->nisn ?: 'Siswa') }}</span>
                                    <span class="text-slate-300 mx-1">•</span>{{ $s->nama_kelas }}
                                </p>
                            </div>
                        </div>
                        <a href="{{ url($basePath . '/cetak?id_siswa=' . $s->id_siswa . '&id_kelas=' . $s->id_kelas . '&desain=duplex_portrait' . $qsSertaan . '&v=5') }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-[11px] font-bold transition-colors no-underline border border-blue-200/60">
                            <i class="bi bi-printer"></i> Cetak
                        </a>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-4">
                        <!-- SISI DEPAN (FRONT) PORTRAIT -->
                        <div class="card-stage">
                            <span class="card-stage-label"><i class="bi bi-credit-card"></i> Sisi Depan</span>
                            @include('admin.presensi.kartu.partials.kartu_dp_depan')
                        </div>

                        <!-- SISI BELAKANG (BACK) PORTRAIT -->
                        <div class="card-stage">
                            <span class="card-stage-label"><i class="bi bi-arrow-repeat"></i> Sisi Belakang</span>
                            @include('admin.presensi.kartu.partials.kartu_dp_belakang')
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>

        <div id="emptyFilter" class="hidden bg-white rounded-3xl p-10 text-center border border-slate-200/80">
            <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 text-xl mx-auto mb-3">
                <i class="bi bi-search"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800">Tidak ada kartu yang cocok</h3>
            <p class="text-xs text-slate-500 mt-1">Ubah kata kunci pencarian nama, NIS, atau NISN siswa.</p>
            <button type="button" id="btnResetCari"
                    class="inline-flex items-center gap-1.5 mt-4 px-3.5 py-2 rounded-xl text-[11px] font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200/60 transition-colors">
                <i class="bi bi-arrow-counterclockwise"></i> Bersihkan pencarian
            </button>
        </div>
    <?php endif; ?>

</div>
@endsection

@section('scripts')
<!-- Library QRCode JS -->
<script src="{{ asset('assets/app/js/qrcode.min.js') }}"></script>
<script>
$(document).ready(function() {
    const basePath = $('#filterForm').data('base');

    // Generate QR Code untuk setiap kartu yang tampil
    $('.qr-box, .qr-print-box').each(function() {
        const el = this;
        const code = $(el).data('code');
        const size = parseInt($(el).data('size') || '64', 10) || 64;
        if (code) {
            try {
                new QRCode(el, {
                    text: String(code),
                    width: size * 2,
                    height: size * 2,
                    colorDark: "#09265e",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            } catch (e) {
                $(el).html('<span class="font-mono text-xs font-bold">' + $('<div>').text(code).html() + '</span>');
            }
        } else {
            $(el).html('<span class="text-[10px] text-slate-400">QR kosong</span>');
        }
    });

    // Render Vector Barcodes (Code 39 standard - high sharp resolution)
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
            x += narrow;
        }
        svg.setAttribute('viewBox', '0 0 ' + x.toFixed(1) + ' ' + height);
        svg.innerHTML = rects;
    });

    function refreshCetakLink() {
        const idKelas = $('#selectKelas').val() || '0';
        const idSiswa = $('#selectSiswa').val() || '0';
        const desain  = $('#inputDesain').val() || 'duplex';
        const mode    = $('#inputMode').val() || 'lembar';
        const flip    = $('#inputFlip').val() || 'long';
        let url = basePath + '/cetak?id_kelas=' + encodeURIComponent(idKelas);
        if (idSiswa !== '0') url += '&id_siswa=' + encodeURIComponent(idSiswa);
        url += '&desain=' + encodeURIComponent(desain);
        url += '&mode=' + encodeURIComponent(mode);
        if (mode === 'bolakbalik') url += '&flip=' + encodeURIComponent(flip);
        url += '&v=5';
        $('#btnCetakLembar').attr('href', url);
    }

    // Muat opsi siswa via AJAX saat kelas diganti (tanpa reload penuh bila memungkinkan)
    $('#selectKelas').on('change', function() {
        const idKelas = $(this).val();
        $('#selectSiswa').val('0');
        refreshCetakLink();
        if (idKelas === '0') {
            $('#filterForm').submit();
            return;
        }
        const $siswa = $('#selectSiswa');
        $siswa.prop('disabled', true);
        $.get(basePath + '/siswa/' + encodeURIComponent(idKelas))
            .done(function(res) {
                if (res && res.status && Array.isArray(res.data)) {
                    $siswa.empty().append('<option value="0">-- Semua Siswa di Kelas Ini --</option>');
                    res.data.forEach(function(row) {
                        $siswa.append('<option value="' + row.id_siswa + '">' + $('<div>').text(row.nama).html() + ' (' + $('<div>').text((row.id_label || 'ID') + ': ' + (row.id_value || '-')).html() + ')</option>');
                    });
                    // Tetap submit agar grid kartu ikut terfilter server-side
                    $('#filterForm').submit();
                } else {
                    $('#filterForm').submit();
                }
            })
            .fail(function() { $('#filterForm').submit(); })
            .always(function() { $siswa.prop('disabled', false); });
    });

    // Auto submit filter saat ganti siswa
    $('#selectSiswa').on('change', function() {
        refreshCetakLink();
        $('#filterForm').submit();
    });

    // Realtime search filter nama / NIS / NISN di preview grid.
    // Hitungan dan pesan "tidak ada hasil" ikut diperbarui agar konsisten
    // dengan panel ringkasan di halaman kartu guru.
    function filterKartu() {
        const query = $('#searchInput').val().toLowerCase().trim();
        let tampil = 0;

        $('.student-card-item').each(function() {
            // jQuery .data() mengubah string angka jadi Number, jadi .includes()
            // melempar TypeError dan membatalkan seluruh loop filter.
            // String() memastikan nilainya tetap teks.
            const name = String($(this).data('name') || '');
            const nis  = String($(this).data('nis') || '');
            const nisn = String($(this).data('nisn') || '');

            const cocok = name.includes(query) || nis.includes(query) || nisn.includes(query);
            $(this).toggle(cocok);
            if (cocok) {
                tampil++;
            }
        });

        $('#emptyFilter').toggleClass('hidden', tampil > 0);
    }

    function syncSearchUi() {
        $('#clearSearch').toggleClass('hidden', $('#searchInput').val().trim() === '');
    }

    $('#searchInput').on('input', function() {
        syncSearchUi();
        filterKartu();
    }).on('keydown', function(e) {
        if (e.key === 'Enter') {
            // Pencarian berjalan realtime; Enter tidak perlu mengirim form
            // (mengirim form justru menghapus kata kunci yang sedang diketik).
            e.preventDefault();
            return;
        }
        if (e.key === 'Escape') {
            $(this).val('');
            syncSearchUi();
            filterKartu();
        }
    });

    function resetCari() {
        $('#searchInput').val('');
        syncSearchUi();
        filterKartu();
        $('#searchInput').trigger('focus');
    }

    $('#clearSearch, #btnResetCari').on('click', resetCari);

    $('#resetFilter').on('click', function() {
        const params = new URLSearchParams();
        const current = new URLSearchParams(window.location.search);
        const desain = $('#inputDesain').val() || 'duplex';
        const mode = $('#inputMode').val() || 'lembar';
        const flip = $('#inputFlip').val() || 'long';

        params.set('desain', desain);
        params.set('mode', mode);
        if (mode === 'bolakbalik') {
            params.set('flip', flip);
        }
        if (desain === 'duplex_portrait' && current.get('template')) {
            params.set('template', current.get('template'));
        }
        window.location.href = basePath + '?' + params.toString();
    });

    syncSearchUi();
});
</script>
@endsection
