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
    // URL reset filter: kembali ke semua kelas/siswa dengan desain & tata letak yang aktif.
    $resetUrl = url($basePath . '?desain=' . $desainAktif . '&mode=' . $modeAktif
        . ($modeAktif === 'bolakbalik' ? '&flip=' . $flipAktif : '')
        . ($desainAktif === 'duplex_portrait' ? '&template=' . $templateAktif : ''));
    // Kelas tab pilihan (desain / tata letak / template) supaya markup tidak berulang.
    $tabClass = fn (bool $aktif) => 'inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all no-underline '
        . ($aktif
            ? 'bg-white text-blue-700 shadow-xs border border-slate-200'
            : 'text-slate-500 hover:text-slate-700 hover:bg-white/70');
?>

<div class="space-y-6"
     x-data="kartuSiswaPage({ base: @js(url($basePath)), desain: @js($desainAktif), mode: @js($modeAktif), flip: @js($flipAktif), idKelas: @js((string) $id_kelas), idSiswa: @js((string) $id_siswa) })">

    <div class="space-y-3">
        <x-ui.page-header
            title="Cetak Kartu Absen Siswa QR"
            icon="bi-qr-code-scan"
            subtitle="Cetak kartu ID presensi siswa dengan QR Code standar untuk scan absensi harian & KBM."
            :crumbs="['Presensi', 'Kartu Siswa']">
            <x-slot:actions>
                <a :href="cetakUrl" target="_blank"
                   class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white no-underline shadow-sm shadow-blue-500/20 transition-colors hover:bg-blue-700">
                    <i class="bi bi-printer-fill"></i> Cetak Kartu
                </a>
                <a href="{{ $bisaSetelan ? url('scanner') : url('guru/presensi-siswa' . (auth()->id() ? '?h=' . \App\Http\Controllers\Guru\PresensiSiswaQrController::generateHash((int) auth()->id()) : '')) }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
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
                <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-400 text-[9px] text-white">2</span> Pilih kelas
            </span>
            <i class="bi bi-chevron-right text-slate-300"></i>
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-slate-600">
                <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-400 text-[9px] text-white">3</span> Cetak
            </span>
        </div>
    </div>

    <!-- Filter & Control Panel -->
    <x-ui.card title="Opsi Cetak Kartu" icon="bi-sliders" subtitle="Pilih desain kartu dan tata letak cetak sebelum mencetak." bodyClass="p-5 space-y-5">

        <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
            <!-- Pilihan Desain Kartu -->
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Desain & Format Kartu</label>
                <div class="inline-flex flex-wrap gap-1 rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Pilihan desain kartu">
                    <a href="{{ url($basePath . '?' . $qsCetak . '&desain=duplex' . $qsSertaan . '&v=5') }}"
                       aria-current="{{ $desainAktif === 'duplex' ? 'page' : 'false' }}"
                       title="Kartu pelajar horizontal 8,56 × 5,4 cm, dicetak 2 sisi"
                       class="{{ $tabClass($desainAktif === 'duplex') }}">
                        <i class="bi bi-layers-fill"></i> Kartu Pelajar (Landscape)
                    </a>
                    <a href="{{ url($basePath . '?' . $qsCetak . '&desain=duplex_portrait' . $qsSertaan . '&v=5') }}"
                       aria-current="{{ $desainAktif === 'duplex_portrait' ? 'page' : 'false' }}"
                       title="Kartu pelajar vertikal 5,4 × 8,56 cm, dicetak 2 sisi"
                       class="{{ $tabClass($desainAktif === 'duplex_portrait') }}">
                        <i class="bi bi-card-heading"></i> Kartu Pelajar (Portrait)
                    </a>
                </div>
                <p class="mt-1.5 text-[11px] text-slate-400">
                    @if($desainAktif === 'duplex')
                        <strong>Kartu Pelajar Landscape:</strong> desain horizontal 8,56 × 5,4 cm dengan foto, identitas, dan QR Code — dicetak 2 sisi (depan + belakang).
                    @else
                        <strong>Kartu Pelajar Portrait:</strong> desain vertikal 5,4 × 8,56 cm dengan foto dan QR Code — dicetak 2 sisi (depan + belakang).
                    @endif
                </p>
            </div>

            <!-- Pilihan Tata Letak / Mode Cetak -->
            <div>
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Tata Letak Cetak</label>
                <div class="inline-flex flex-wrap gap-1 rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Pilihan tata letak cetak">
                    <a href="{{ url($basePath . '?' . $qsCetak . '&desain=' . $desainAktif . '&mode=lembar&v=5') }}"
                       aria-current="{{ $modeAktif === 'lembar' ? 'page' : 'false' }}"
                       title="Sisi depan & belakang berdampingan dalam satu lembar"
                       class="{{ $tabClass($modeAktif === 'lembar') }}">
                        <i class="bi bi-file-earmark"></i> Lembar
                    </a>
                    <a href="{{ url($basePath . '?' . $qsCetak . '&desain=' . $desainAktif . '&mode=bolakbalik&flip=' . $flipAktif . '&v=5') }}"
                       aria-current="{{ $modeAktif === 'bolakbalik' ? 'page' : 'false' }}"
                       title="Grid A4 dua tahap: semua depan, lalu semua belakang"
                       class="{{ $tabClass($modeAktif === 'bolakbalik') }}">
                        <i class="bi bi-layout-text-window-reverse"></i> Bolak-balik (Grid A4)
                    </a>
                    <a href="{{ url($basePath . '?' . $qsCetak . '&desain=' . $desainAktif . '&mode=ktp&v=5') }}"
                       aria-current="{{ $modeAktif === 'ktp' ? 'page' : 'false' }}"
                       title="Cetak langsung ke kartu CR80 85,6 × 54 mm"
                       class="{{ $tabClass($modeAktif === 'ktp') }}">
                        <i class="bi bi-credit-card-2-front"></i> Ukuran KTP (CR80)
                    </a>
                </div>
                <p class="mt-1.5 text-[11px] text-slate-400">
                    @if($modeAktif === 'ktp')
                        <strong>Ukuran KTP :</strong> Cetak langsung ke kartu CR80 85.6 × 54 mm, satu kartu per halaman — cocok untuk printer kartu.
                    @elseif($modeAktif === 'bolakbalik')
                        <strong>Bolak-balik :</strong> Grid A4 dua tahap — cetak semua sisi depan, balik tumpukan kertas, lalu cetak semua sisi belakang.
                    @else
                        <strong>Lembar :</strong> Sisi depan & belakang dicetak berdampingan dalam satu lembar.
                    @endif
                </p>
                @if($modeAktif === 'bolakbalik')
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold text-slate-600">Arah balik kertas:</span>
                        <a href="{{ url($basePath . '?' . $qsCetak . '&desain=' . $desainAktif . '&mode=bolakbalik&flip=long&v=5') }}"
                           aria-current="{{ $flipAktif === 'long' ? 'page' : 'false' }}"
                           class="{{ $tabClass($flipAktif === 'long') }} text-[11px]">
                            <i class="bi bi-arrow-left-right"></i> Sisi Panjang (Long Edge)
                        </a>
                        <a href="{{ url($basePath . '?' . $qsCetak . '&desain=' . $desainAktif . '&mode=bolakbalik&flip=short&v=5') }}"
                           aria-current="{{ $flipAktif === 'short' ? 'page' : 'false' }}"
                           class="{{ $tabClass($flipAktif === 'short') }} text-[11px]">
                            <i class="bi bi-arrow-down-up"></i> Sisi Pendek (Short Edge)
                        </a>
                    </div>
                @endif
            </div>

            @if($desainAktif === 'duplex_portrait')
                <!-- Pilihan Template Kartu (hanya duplex portrait) -->
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Template Kartu</label>
                    <div class="inline-flex flex-wrap gap-1 rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Pilihan template kartu">
                        <a href="{{ url($basePath . '?' . $qsCetak . '&desain=duplex_portrait&mode=' . $modeAktif . ($modeAktif === 'bolakbalik' ? '&flip=' . $flipAktif : '') . '&template=vektor&v=5') }}"
                           aria-current="{{ $templateAktif === 'vektor' ? 'page' : 'false' }}"
                           class="{{ $tabClass($templateAktif === 'vektor') }}">
                            <i class="bi bi-vector-pen"></i> Vektor
                        </a>
                        <a href="{{ $pngHref }}"
                           aria-current="{{ $templateAktif === 'png' ? 'page' : 'false' }}"
                           {!! $templatePngSiap ? '' : ($bisaSetelan ? 'title="Unggah latar kartu depan &amp; belakang di menu Pengaturan dulu" style="opacity:.45;"' : 'aria-disabled="true" title="Latar kartu diunggah admin melalui menu Pengaturan" tabindex="-1" style="pointer-events:none;opacity:.45;"') !!}
                           class="{{ $tabClass($templateAktif === 'png') }}">
                            <i class="bi bi-image"></i> Latar PNG
                        </a>
                    </div>
                    <p class="mt-1.5 text-[11px] text-slate-400">
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

            <div class="flex items-start gap-2.5 rounded-xl border border-blue-100 bg-blue-50/60 px-3.5 py-3 text-[11px] text-blue-800 lg:col-span-2">
                <i class="bi bi-lightbulb-fill mt-0.5 text-blue-500"></i>
                <p class="m-0 font-semibold leading-relaxed">
                    Hasil cetak terbaik: gunakan skala <strong>100%</strong>, pilih kertas <strong>{{ $modeAktif === 'ktp' ? 'Card Size / CR80' : 'A4' }}</strong>, dan aktifkan <strong>Background graphics</strong>.
                </p>
            </div>
        </div>

        <hr class="border-slate-100">

        <!-- Filter Data -->
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Filter Data Siswa</label>
            <form method="get" action="{{ url($basePath) }}" x-ref="filterForm" class="grid grid-cols-1 items-end gap-4 md:grid-cols-12">
                <input type="hidden" name="desain" value="{{ $desainAktif }}">
                <input type="hidden" name="mode" value="{{ $modeAktif }}">
                <input type="hidden" name="flip" value="{{ $flipAktif }}">

                <!-- Pilih Kelas -->
                <div class="md:col-span-4">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Pilih Kelas</label>
                    <select name="id_kelas" x-model="idKelas" @change="idSiswa = '0'; $refs.filterForm.submit()"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition-all hover:border-slate-300 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="0">-- Semua Kelas --</option>
                        @foreach ($kelases as $k)
                            <option value="{{ $k->id_kelas }}" @selected($id_kelas == $k->id_kelas)>
                                Kelas {{ $k->nama_kelas }} ({{ $k->jumlah_siswa }} Siswa)
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Pilih Siswa (Per Orang) -->
                <div class="md:col-span-4">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Pilih Siswa (Opsional / Per Orang)</label>
                    <select name="id_siswa" x-model="idSiswa" @change="$refs.filterForm.submit()"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-semibold text-slate-800 transition-all hover:border-slate-300 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="0">-- Semua Siswa di Kelas Ini --</option>
                        @foreach ($siswa_options as $so)
                            <option value="{{ $so->id_siswa }}" @selected($id_siswa == $so->id_siswa)>
                                {{ $so->nama }} ({{ $so->id_label ?? 'ID' }}: {{ $so->id_value ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Pencarian Cepat Nama / NIS -->
                <div class="md:col-span-4">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Filter Pencarian</label>
                    <div class="relative">
                        <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                        <input type="text" x-ref="search" x-model="query"
                               placeholder="Cari nama, NIS, atau NISN siswa..."
                               aria-label="Cari nama, NIS, atau NISN siswa"
                               @keydown.enter.prevent
                               @keydown.escape.prevent="clearSearch()"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-9 text-xs font-semibold text-slate-800 transition-all hover:border-slate-300 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="button" x-show="query.trim() !== ''" x-cloak @click="clearSearch()"
                                aria-label="Bersihkan pencarian"
                                class="absolute right-2.5 top-1/2 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-200 hover:text-slate-600">
                            <i class="bi bi-x-circle-fill text-[13px]"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-1 md:col-span-12">
                    <a href="{{ $resetUrl }}"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-[11px] font-bold text-slate-600 no-underline transition-colors hover:bg-slate-200">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset filter
                    </a>
                </div>
            </form>
        </div>

        @if (!empty($catatan))
            <div class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-xs text-amber-800">
                <i class="bi bi-exclamation-triangle-fill mt-0.5 text-amber-500"></i>
                <p class="font-semibold leading-relaxed">{{ $catatan }}</p>
            </div>
        @endif

        @if (!empty($terpotong))
            <div class="flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-3 text-xs text-blue-800">
                <i class="bi bi-info-circle-fill mt-0.5 text-blue-500"></i>
                <p class="font-semibold leading-relaxed">
                    Daftar dipotong pada {{ (int) ($batas_cetak ?? 500) }} kartu pertama supaya halaman tidak macet.
                    Pilih satu kelas atau satu siswa untuk mencetak sisanya.
                </p>
            </div>
        @endif
    </x-ui.card>

    <!-- Live Card Grid Preview -->
    @if (empty($siswa_list))
        <x-ui.card>
            <x-ui.empty icon="bi-person-x" title="Tidak Ada Siswa Ditemukan"
                :description="!empty($catatan) ? $catatan : 'Silakan pilih kelas lain atau periksa data penempatan kelas siswa pada Tahun Pelajaran aktif.'">
                @if ($bisaSetelan)
                    <a href="{{ url('datasiswa') }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white no-underline transition-colors hover:bg-blue-700">
                        <i class="bi bi-people-fill"></i> Buka Data Siswa
                    </a>
                @endif
            </x-ui.empty>
        </x-ui.card>
    @else
        @if ($desainAktif === 'duplex')
            {{-- 1. DUPLEX LANDSCAPE PREVIEW (8.56 × 5.4 cm) --}}
            <div id="cardGrid" x-ref="grid" class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                @foreach ($siswa_list as $s)
                    <div class="student-card-item card-preview rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs"
                         x-show="matches($el)"
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
                        <div class="mb-3 flex items-start justify-between gap-3 border-b border-slate-100 pb-2">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-700 text-[11px] font-extrabold tracking-wide text-white">{{ $inisialSiswa }}</span>
                                <div class="min-w-0">
                                    <h5 class="m-0 truncate text-[12px] font-extrabold leading-tight text-slate-800" title="{{ $s->nama }}">{{ $s->nama }}</h5>
                                    <p class="m-0 mt-0.5 truncate text-[10px] font-semibold text-slate-500">
                                        <span class="font-mono text-slate-600">{{ $s->nis ?: ($s->nisn ?: 'Siswa') }}</span>
                                        <span class="mx-1 text-slate-300">•</span>{{ $s->nama_kelas }}
                                    </p>
                                </div>
                            </div>
                            <a href="{{ url($basePath . '/cetak?id_siswa=' . $s->id_siswa . '&id_kelas=' . $s->id_kelas . '&desain=duplex' . $qsSertaan . '&v=5') }}"
                               target="_blank"
                               class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200/60 bg-blue-50 px-3 py-1 text-[11px] font-bold text-blue-700 no-underline transition-colors hover:bg-blue-100">
                                <i class="bi bi-printer"></i> Cetak Kartu
                            </a>
                        </div>
                        <!-- Front & Back side-by-side: 100% Identik dengan Tampilan Cetak -->
                        <div class="flex flex-wrap items-center justify-center gap-4">
                            <div class="card-stage">
                                <span class="card-stage-label"><i class="bi bi-credit-card-2-front"></i> Sisi Depan</span>
                                @include('admin.presensi.kartu.partials.kartu_dl_depan')
                            </div>
                            <div class="card-stage">
                                <span class="card-stage-label"><i class="bi bi-arrow-repeat"></i> Sisi Belakang</span>
                                @include('admin.presensi.kartu.partials.kartu_dl_belakang')
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif ($desainAktif === 'duplex_portrait')
            {{-- 2. DUPLEX PORTRAIT PREVIEW (5.4 × 8.56 cm) --}}
            <div id="cardGrid" x-ref="grid" class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($siswa_list as $s)
                    <div class="student-card-item card-preview rounded-2xl border border-slate-200/90 bg-white p-4 shadow-xs"
                         x-show="matches($el)"
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
                        <div class="mb-3 flex items-start justify-between gap-3 border-b border-slate-100 pb-2">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-700 text-[11px] font-extrabold tracking-wide text-white">{{ $inisialSiswa }}</span>
                                <div class="min-w-0">
                                    <h5 class="m-0 truncate text-[12px] font-extrabold leading-tight text-slate-800" title="{{ $s->nama }}">{{ $s->nama }}</h5>
                                    <p class="m-0 mt-0.5 truncate text-[10px] font-semibold text-slate-500">
                                        <span class="font-mono text-slate-600">{{ $s->nis ?: ($s->nisn ?: 'Siswa') }}</span>
                                        <span class="mx-1 text-slate-300">•</span>{{ $s->nama_kelas }}
                                    </p>
                                </div>
                            </div>
                            <a href="{{ url($basePath . '/cetak?id_siswa=' . $s->id_siswa . '&id_kelas=' . $s->id_kelas . '&desain=duplex_portrait' . $qsSertaan . '&v=5') }}"
                               target="_blank"
                               class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200/60 bg-blue-50 px-3 py-1 text-[11px] font-bold text-blue-700 no-underline transition-colors hover:bg-blue-100">
                                <i class="bi bi-printer"></i> Cetak
                            </a>
                        </div>
                        <div class="flex flex-wrap items-center justify-center gap-4">
                            <div class="card-stage">
                                <span class="card-stage-label"><i class="bi bi-credit-card"></i> Sisi Depan</span>
                                @include('admin.presensi.kartu.partials.kartu_dp_depan')
                            </div>
                            <div class="card-stage">
                                <span class="card-stage-label"><i class="bi bi-arrow-repeat"></i> Sisi Belakang</span>
                                @include('admin.presensi.kartu.partials.kartu_dp_belakang')
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div x-show="matchCount === 0" x-cloak>
            <x-ui.card>
                <x-ui.empty icon="bi-search" title="Tidak ada kartu yang cocok"
                    description="Ubah kata kunci pencarian nama, NIS, atau NISN siswa.">
                    <button type="button" @click="clearSearch()"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-blue-200/60 bg-blue-50 px-3.5 py-2 text-[11px] font-bold text-blue-700 transition-colors hover:bg-blue-100">
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
    Alpine.data('kartuSiswaPage', (config = {}) => ({
        base: config.base || '',
        desain: config.desain || 'duplex',
        mode: config.mode || 'lembar',
        flip: config.flip || 'long',
        idKelas: String(config.idKelas ?? '0'),
        idSiswa: String(config.idSiswa ?? '0'),
        query: '',

        init() {
            this.$nextTick(() => {
                this.renderQrCodes();
                this.renderBarcodes();
            });
        },

        /** URL cetak mengikuti pilihan kelas/siswa + format yang sedang aktif. */
        get cetakUrl() {
            const params = new URLSearchParams();
            params.set('id_kelas', this.idKelas || '0');
            if (this.idSiswa && this.idSiswa !== '0') {
                params.set('id_siswa', this.idSiswa);
            }
            params.set('desain', this.desain);
            params.set('mode', this.mode);
            if (this.mode === 'bolakbalik') {
                params.set('flip', this.flip);
            }
            params.set('v', '5');

            return this.base + '/cetak?' + params.toString();
        },

        /** Cocokkan satu kartu dengan kata kunci nama / NIS / NISN. */
        matches(el) {
            const q = this.query.trim().toLowerCase();
            if (!q) return true;
            const data = el.dataset || {};

            return String(data.name || '').includes(q)
                || String(data.nis || '').includes(q)
                || String(data.nisn || '').includes(q);
        },

        get matchCount() {
            const grid = this.$refs.grid;
            if (!grid) return 0;

            return Array.from(grid.querySelectorAll('.student-card-item'))
                .filter((el) => this.matches(el)).length;
        },

        clearSearch() {
            this.query = '';
            this.$nextTick(() => this.$refs.search?.focus());
        },

        /** Render QR untuk setiap kartu yang tampil (dan yang tersembunyi). */
        renderQrCodes() {
            if (typeof QRCode === 'undefined') return;

            document.querySelectorAll('.qr-box, .qr-print-box').forEach((el) => {
                const code = el.dataset.code;
                const size = parseInt(el.dataset.size || '64', 10) || 64;

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
                        width: size * 2,
                        height: size * 2,
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

        /** Render barcode Code 39 sebagai SVG tajam (bukan gambar raster). */
        renderBarcodes() {
            const peta = {
                '0': '000110100', '1': '100100001', '2': '001100001', '3': '101100000',
                '4': '000110001', '5': '100110000', '6': '001110000', '7': '000100101',
                '8': '100100100', '9': '001100100', 'A': '100001001', 'B': '001001001',
                'C': '101001000', 'D': '000011001', 'E': '100011000', 'F': '001011000',
                'G': '000001101', 'H': '100001100', 'I': '001001100', 'J': '000011100',
                'K': '100000011', 'L': '001000011', 'M': '101000010', 'N': '000010011',
                'O': '100010010', 'P': '001010010', 'Q': '000000111', 'R': '100000110',
                'S': '001000110', 'T': '000010110', 'U': '110000001', 'V': '011000001',
                'W': '111000000', 'X': '010010001', 'Y': '110010000', 'Z': '011010000',
                '-': '010000101', '.': '110000100', ' ': '011000100', '*': '010010100',
            };

            document.querySelectorAll('.barcode-svg').forEach((svg) => {
                let teks = (svg.dataset.barcode || '12345678').toString().toUpperCase().replace(/[^0-9A-Z\-]/g, '');
                if (!teks) teks = '000000';

                const penuh = '*' + teks + '*';
                const sempit = 1.0;
                const lebar = 2.4;
                const tinggi = 24;
                let x = 0;
                let rects = '';

                for (let i = 0; i < penuh.length; i++) {
                    const pola = peta[penuh[i]] || peta['0'];
                    for (let j = 0; j < 9; j++) {
                        const bilah = j % 2 === 0;
                        const w = pola[j] === '1' ? lebar : sempit;
                        if (bilah) {
                            rects += '<rect x="' + x.toFixed(1) + '" y="0" width="' + w.toFixed(1) + '" height="' + tinggi + '" fill="#09265e" />';
                        }
                        x += w;
                    }
                    x += sempit;
                }

                svg.setAttribute('viewBox', '0 0 ' + x.toFixed(1) + ' ' + tinggi);
                svg.innerHTML = rects;
            });
        },
    }));
});
</script>
@endsection
