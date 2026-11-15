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
</style>
@endsection

@section('content')
<?php
    // Di-set sekali di awal section lalu dipakai seluruh link & switcher desain.
    $basePath = $base_path ?? 'admin/presensi/siswa/kartu';
    $desainAktif = $desain ?? 'duplex';
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
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <a id="btnCetakLembar"
               href="{{ url($basePath . '/cetak?id_kelas=' . $id_kelas . ($id_siswa ? '&id_siswa=' . $id_siswa : '') . '&desain=' . $desainAktif . '&v=4') }}"
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-blue-500/20 no-underline">
                <i class="bi bi-printer-fill"></i> Cetak Lembar Kartu (A4)
            </a>
            <a href="{{ url('guru/presensi-siswa' . (session('user_id') ? '?h=' . \App\Http\Controllers\Guru\PresensiSiswaQrController::generateHash((int)session('user_id')) : '')) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-camera-fill"></i> Buka Scanner QR
            </a>
        </div>
    </div>

    <!-- Filter & Control Panel -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <!-- Pilihan Desain Kartu -->
        <div class="mb-4">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Desain & Format Kartu</label>
            <div class="inline-flex p-1 bg-slate-100 rounded-xl gap-1 flex-wrap" role="tablist" aria-label="Pilihan desain kartu">
                <a href="{{ url($basePath . '?id_kelas=' . $id_kelas . ($id_siswa ? '&id_siswa=' . $id_siswa : '') . '&desain=duplex&v=4') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $desainAktif === 'duplex' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700' }}">
                    <i class="bi bi-layers-fill"></i> Kartu Pelajar (Landscape)
                    <span class="hidden sm:inline font-semibold {{ $desainAktif === 'duplex' ? 'text-blue-500' : 'text-slate-400' }}"></span>
                </a>
                <a href="{{ url($basePath . '?id_kelas=' . $id_kelas . ($id_siswa ? '&id_siswa=' . $id_siswa : '') . '&desain=duplex_portrait&v=4') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $desainAktif === 'duplex_portrait' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700' }}">
                    <i class="bi bi-card-heading"></i> Kartu Pelajar (Portrait)
                    <span class="hidden sm:inline font-semibold {{ $desainAktif === 'duplex_portrait' ? 'text-blue-500' : 'text-slate-400' }}"></span>
                </a>
                <a href="{{ url($basePath . '?id_kelas=' . $id_kelas . ($id_siswa ? '&id_siswa=' . $id_siswa : '') . '&desain=landscape&v=4') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $desainAktif === 'landscape' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700' }}">
                    <i class="bi bi-credit-card-2-front"></i> Kartu Absen Landscape
                </a>
                <a href="{{ url($basePath . '?id_kelas=' . $id_kelas . ($id_siswa ? '&id_siswa=' . $id_siswa : '') . '&desain=portrait&v=4') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $desainAktif === 'portrait' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700' }}">
                    <i class="bi bi-credit-card"></i> Kartu Absen Portrait
                </a>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">
                @if($desainAktif === 'duplex')
                    <strong>Duplex Landscape :</strong> Standar PVC ID Card.
                @elseif($desainAktif === 'duplex_portrait')
                    <strong>Duplex Portrait :</strong> Format ID card vertikal.
                @elseif($desainAktif === 'portrait')
                    <strong>Portrait Klasik:</strong> Cetak 1 sisi vertikal tanpa foto dengan QR code besar di tengah.
                @else
                    <strong>Landscape Klasik:</strong> Format 1 sisi kartu pelajar horizontal terdahulu.
                @endif
            </p>
        </div>
        <form method="get" action="{{ url($basePath) }}" id="filterForm" data-base="{{ url($basePath) }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <input type="hidden" name="desain" id="inputDesain" value="{{ $desainAktif }}">
            <!-- Pilih Kelas -->
            <div class="md:col-span-4">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Kelas</label>
                <select name="id_kelas" id="selectKelas" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
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
                <select name="id_siswa" id="selectSiswa" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
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
                    <input type="text" id="searchInput" placeholder="Cari nama atau NIS siswa..."
                           class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                </div>
            </div>
        </form>

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

        <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-100 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 text-blue-700 font-bold text-[10px]">
                    {{ count($siswa_list) }}
                </span>
                <span>kartu ditampilkan{{ !empty($terpotong) ? ' (dari daftar yang dipotong)' : '' }}</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-slate-400">TP: <strong class="text-slate-700">{{ $tp_active->tahun ?? '-' }}</strong></span>
                <span class="text-slate-300">•</span>
                <span class="text-slate-400">Semester: <strong class="text-slate-700">{{ $smt_active->smt ?? '-' }}</strong></span>
            </div>
        </div>
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
        </div>
    <?php else: ?>

        {{-- ══════════════════════════════════════════════════════════════════
             1. DUPLEX LANDSCAPE PREVIEW (8.56 × 5.4 cm)
        ══════════════════════════════════════════════════════════════════ --}}
        <?php if ($desainAktif === 'duplex'): ?>
        <div id="cardGrid" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <?php foreach ($siswa_list as $s): ?>
                <?php
                $isLaki = ($s->jenis_kelamin ?? 'L') === 'L';
                $ttlClean = \App\Services\KartuSiswaFormatter::ttl($s);
                $nisnClean = \App\Services\KartuSiswaFormatter::nomorSiswa($s);
                $barcodeVal = $s->qr_value;
                ?>
                <div class="student-card-item card-preview bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs"
                     data-name="{{ strtolower($s->nama) }}"
                     data-nis="{{ strtolower($s->nis ?? '') }}"
                     data-nisn="{{ strtolower($s->nisn ?? '') }}">
                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                            <h5 class="text-xs font-extrabold text-slate-800 line-clamp-1 m-0">{{ $s->nama }}</h5>
                            <span class="text-[10px] font-semibold text-slate-400">({{ $s->nama_kelas }})</span>
                        </div>
                        <a href="{{ url($basePath . '/cetak?id_siswa=' . $s->id_siswa . '&id_kelas=' . $s->id_kelas . '&desain=duplex&v=4') }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-[11px] font-bold transition-colors no-underline border border-blue-200/60">
                            <i class="bi bi-printer"></i> Cetak Kartu
                        </a>
                    </div>
                    <!-- Front & Back side-by-side: 100% Identik dengan Tampilan Cetak -->
                    <div class="flex flex-wrap items-center justify-center gap-4">
                        <!-- SISI DEPAN -->
                        <div class="flex flex-col items-center gap-1.5">
                            <span class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">Sisi Depan</span>
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
                                                <td class="dl-val" title="{{ $ttlClean }}">{{ $ttlClean }}</td>
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

                        <!-- SISI BELAKANG -->
                        <div class="flex flex-col items-center gap-1.5">
                            <span class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">Sisi Belakang</span>
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
                                                <td class="b-val" title="{{ $ttlClean }}">{{ $ttlClean }}</td>
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
                                            <div style="font-weight:700;">Kepala Sekolah,</div>
                                            <div class="dl-sign-name" style="margin-top:10px;">{{ $setting->kepsek ?? 'Kepala Sekolah' }}</div>
                                            <div>NIP. {{ $setting->nip ?? '-' }}</div>
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
                </div>
            <?php endforeach; ?>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════
             2. DUPLEX PORTRAIT PREVIEW (5.4 × 8.56 cm)
        ══════════════════════════════════════════════════════════════════ --}}
        <?php elseif ($desainAktif === 'duplex_portrait'): ?>
        <div id="cardGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($siswa_list as $s): ?>
                <?php
                $isLaki = ($s->jenis_kelamin ?? 'L') === 'L';
                $ttlClean = \App\Services\KartuSiswaFormatter::ttl($s);
                $nisnClean = \App\Services\KartuSiswaFormatter::nomorSiswa($s);
                $barcodeVal = $s->qr_value;
                ?>
                <div class="student-card-item card-preview bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs"
                     data-name="{{ strtolower($s->nama) }}"
                     data-nis="{{ strtolower($s->nis ?? '') }}"
                     data-nisn="{{ strtolower($s->nisn ?? '') }}">
                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                            <h5 class="text-xs font-extrabold text-slate-800 line-clamp-1 m-0">{{ $s->nama }}</h5>
                            <span class="text-[10px] font-semibold text-slate-400">({{ $s->nama_kelas }})</span>
                        </div>
                        <a href="{{ url($basePath . '/cetak?id_siswa=' . $s->id_siswa . '&id_kelas=' . $s->id_kelas . '&desain=duplex_portrait&v=4') }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-[11px] font-bold transition-colors no-underline border border-blue-200/60">
                            <i class="bi bi-printer"></i> Cetak
                        </a>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-4">
                        <!-- SISI DEPAN (FRONT) PORTRAIT -->
                        <div class="flex flex-col items-center gap-1.5">
                            <span class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">Sisi Depan</span>
                            <div class="card-dp card-dp-front">
                                <div class="dp-header">
                                    <div class="dp-logo-wrap">
                                        <img src="{{ $logo_url }}" alt="Logo" onerror="this.src='{{ asset('assets/img/logo.png') }}'">
                                    </div>
                                    <div class="dp-header-titles">
                                        <h4 class="dp-header-title">{{ $setting->sekolah ?? 'SMA NEGERI 2 AMFOANG TIMUR' }}</h4>
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
                        <div class="flex flex-col items-center gap-1.5">
                            <span class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">Sisi Belakang</span>
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
                                            <td class="dp-val" title="{{ $ttlClean }}">{{ $ttlClean }}</td>
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
                                        <div style="font-weight:700;">Kepala Sekolah,</div>
                                        <div class="dp-sign-name">{{ $setting->kepsek ?? 'Kepala Sekolah' }}</div>
                                        <div>NIP. {{ $setting->nip ?? '-' }}</div>
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
                </div>
            <?php endforeach; ?>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════
             3. PORTRAIT 1-SISI (Legacy)
        ══════════════════════════════════════════════════════════════════ --}}
        <?php elseif ($desainAktif === 'portrait'): ?>
        <div id="cardGrid" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-6 justify-items-center">
            <?php foreach ($siswa_list as $s): ?>
                <div class="student-card-item card-preview bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-xs relative flex flex-col w-full max-w-[220px]"
                     data-name="{{ strtolower($s->nama) }}"
                     data-nis="{{ strtolower($s->nis ?? '') }}"
                     data-nisn="{{ strtolower($s->nisn ?? '') }}">
                    <!-- Kop vertikal -->
                    <div class="px-3 pt-3 pb-2.5 text-center" style="background:linear-gradient(180deg,var(--primary-700) 0%,var(--indigo-800) 55%,var(--primary-900) 100%);color:#fff;">
                        <div class="w-9 h-9 rounded-xl p-1 mx-auto flex items-center justify-center" style="background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.15);">
                            <img src="{{ $logo_url }}" alt="Logo" class="max-h-full max-w-full object-contain"
                                 onerror="this.src='{{ asset('assets/img/logo.png') }}'">
                        </div>
                        <p class="tracking-wider uppercase leading-none mt-1.5" style="font-size:8px;font-weight:600;color:var(--primary-200);">KARTU PRESENSI DIGITAL</p>
                        <h4 class="leading-tight mt-0.5" style="font-size:11px;font-weight:800;color:#fff;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $setting->sekolah ?? 'SMA NEGERI 2 AMFOANG TIMUR' }}</h4>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded-md uppercase" style="font-size:10px;font-weight:800;letter-spacing:.05em;color:#fff;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);">
                            {{ $s->nama_kelas }}
                        </span>
                    </div>
                    <!-- QR besar -->
                    <div class="px-3 pt-2 pb-1 flex flex-col items-center">
                        <div class="qr-box p-2 bg-white border border-slate-200 rounded-xl shadow-2xs"
                             data-code="{{ $s->qr_value }}" data-size="150"
                             id="qr-preview-{{ $s->id_siswa }}" style="width:166px;height:166px;display:flex;align-items:center;justify-content:center;"></div>
                        <span class="text-[8px] font-bold text-slate-400 uppercase tracking-wider">Scan Absen</span>
                    </div>
                    <!-- Identitas -->
                    <div class="px-3 pb-2 pt-1 text-center flex-1">
                        <h5 class="text-xs font-extrabold text-slate-900 leading-snug line-clamp-2" title="{{ $s->nama }}">
                            {{ $s->nama }}
                        </h5>
                        <p class="text-[10px] text-slate-500 mt-0.5 font-mono">NISN {{ \App\Services\KartuSiswaFormatter::nomorSiswa($s) }}</p>
                        <span class="inline-block mt-1 px-2 py-0.5 text-[9px] font-bold rounded-full {{ ($s->jenis_kelamin == 'L') ? 'bg-blue-50 text-blue-700' : 'bg-pink-50 text-pink-700' }}">
                            {{ ($s->jenis_kelamin == 'L') ? 'LAKI-LAKI' : 'PEREMPUAN' }}
                        </span>
                    </div>
                    <div class="px-3 py-2 bg-slate-50/80 border-t border-slate-100 flex items-center justify-center">
                        <a href="{{ url($basePath . '/cetak?id_siswa=' . $s->id_siswa . '&id_kelas=' . $s->id_kelas . '&desain=duplex_portrait&v=4') }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 font-bold text-blue-600 hover:text-blue-700 bg-white hover:bg-blue-50 px-2.5 py-1 rounded-lg border border-slate-200 transition-colors no-underline text-[10px]">
                            <i class="bi bi-printer"></i> Cetak Kartu Ini
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════
             4. LANDSCAPE 1-SISI (Legacy)
        ══════════════════════════════════════════════════════════════════ --}}
        <?php else: ?>
        <!-- ══════════════════════════════════════════════════════════════════
             LANDSCAPE: Desain Kartu Pelajar / ID Card
             Layout: sisi kiri = header+foto+ttd | sisi kanan = info+QR
        ══════════════════════════════════════════════════════════════════ -->
        <div id="cardGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <?php foreach ($siswa_list as $s): ?>
            <?php $isLaki = ($s->jenis_kelamin ?? 'L') === 'L'; ?>
            <div class="student-card-item card-preview overflow-hidden shadow-sm relative"
                 style="display:flex;flex-direction:row;border-radius:14px;border:1.5px solid #e2e8f0;background:#fff;min-height:155px;max-height:185px;min-width:0;"
                 data-name="{{ strtolower($s->nama) }}"
                 data-nis="{{ strtolower($s->nis ?? '') }}"
                 data-nisn="{{ strtolower($s->nisn ?? '') }}">

                <!-- ── Sisi Kiri: Strip header + Foto + Badge ── -->
                <div class="shrink-0 flex flex-col" style="width:96px;background:linear-gradient(175deg,var(--primary-700,#4338ca) 0%,var(--primary-900,#1e1b4b) 100%);">
                    <!-- Kop nama sekolah vertikal-compact -->
                    <div class="flex items-center gap-2 px-2.5 py-2" style="border-bottom:1.5px solid rgba(255,255,255,.15);">
                        <div class="shrink-0" style="width:24px;height:24px;background:#fff;border-radius:6px;padding:2px;display:flex;align-items:center;justify-content:center;">
                            <img src="{{ $logo_url }}" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;"
                                 onerror="this.src='{{ asset('assets/img/logo.png') }}'">
                        </div>
                        <div style="min-width:0;">
                            <p style="font-size:5.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.65);margin:0;line-height:1.1;">KARTU PELAJAR</p>
                            <p style="font-size:6.5px;font-weight:800;color:#fff;margin:0;line-height:1.2;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $setting->sekolah ?? 'SEKOLAH' }}</p>
                        </div>
                    </div>
                    <!-- Foto Siswa -->
                    <div class="flex-1 flex flex-col items-center justify-center px-2 py-2 gap-1">
                        <div style="width:58px;height:76px;border-radius:8px;overflow:hidden;border:2px solid rgba(255,255,255,.35);background:#e2e8f0;flex-shrink:0;">
                            <img src="{{ $s->foto_url }}" alt="{{ $s->nama }}"
                                 style="width:100%;height:100%;object-fit:cover;"
                                 onerror="this.src='{{ asset('assets/img/siswa.png') }}'">
                        </div>
                        <span style="font-size:7px;font-weight:800;letter-spacing:.05em;text-transform:uppercase;padding:1.5px 7px;border-radius:20px;{{ $isLaki ? 'background:rgba(96,165,250,.25);color:#bfdbfe;border:1px solid rgba(147,197,253,.3);' : 'background:rgba(244,114,182,.25);color:#fbcfe8;border:1px solid rgba(249,168,212,.3);' }}">
                            {{ $isLaki ? 'L' : 'P' }}
                        </span>
                    </div>
                    <!-- Tahun pelajaran -->
                    <div style="padding:3px 8px;text-align:center;border-top:1px solid rgba(255,255,255,.12);">
                        <span style="font-size:5.5px;font-weight:700;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.06em;">TP {{ $tp_active->tahun ?? '-' }}</span>
                    </div>
                </div>

                <!-- ── Sisi Kanan: Identitas + QR ── -->
                <?php $bgCardUrl = asset('assets/img/bg-card.png'); ?>
                <div class="flex-1 min-w-0 flex flex-col" style="position:relative;background-image:url('{{ $bgCardUrl }}');background-size:cover;background-position:center;">
                    <!-- overlay putih semi-transparan agar teks tetap terbaca -->
                    <div style="position:absolute;inset:0;background:rgba(255,255,255,0.82);pointer-events:none;z-index:0;"></div>
                    <!-- konten di atas overlay -->
                    <div style="position:relative;z-index:1;display:flex;flex-direction:column;flex:1;min-width:0;">
                    <!-- Badge kelas di pojok kanan atas -->
                    <div class="flex items-start justify-between px-3 pt-2.5 pb-0">
                        <span style="font-size:6.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--primary-500,#6366f1);">KARTU SISWA AKTIF</span>
                        <span style="font-size:8px;font-weight:900;padding:1.5px 7px;border-radius:5px;background:var(--primary-50,#eef2ff);color:var(--primary-700,#4338ca);border:1px solid var(--primary-100,#e0e7ff);text-transform:uppercase;">{{ $s->nama_kelas }}</span>
                    </div>

                    <!-- Nama Siswa -->
                    <div class="px-3 pt-1 pb-0">
                        <h5 style="font-size:11px;font-weight:900;color:#0f172a;margin:0;line-height:1.2;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;" title="{{ $s->nama }}">
                            {{ strtoupper($s->nama) }}
                        </h5>
                    </div>

                    <!-- Data & QR side by side -->
                    <div class="flex gap-2 px-3 pt-1.5 pb-0 flex-1 items-start">
                        <!-- Data identitas -->
                        <div class="flex-1 min-w-0" style="font-size:9px;line-height:1.55;color:#334155;">
                            <div class="flex" style="gap:3px;">
                                <span style="width:30px;font-weight:700;color:#94a3b8;font-size:8px;text-transform:uppercase;flex-shrink:0;">NIS</span>
                                <span style="font-weight:800;color:#0f172a;font-family:monospace;">{{ $s->nis ?: '-' }}</span>
                            </div>
                            <div class="flex" style="gap:3px;">
                                <span style="width:30px;font-weight:700;color:#94a3b8;font-size:8px;text-transform:uppercase;flex-shrink:0;">NISN</span>
                                <span style="font-weight:600;color:#334155;font-family:monospace;">{{ $s->nisn ?: '-' }}</span>
                            </div>
                            <div class="flex" style="gap:3px;">
                                <span style="width:30px;font-weight:700;color:#94a3b8;font-size:8px;text-transform:uppercase;flex-shrink:0;">KELAS</span>
                                <span style="font-weight:700;color:#0f172a;">{{ $s->nama_kelas }}</span>
                            </div>
                            <div class="flex" style="gap:3px;">
                                <span style="width:30px;font-weight:700;color:#94a3b8;font-size:8px;text-transform:uppercase;flex-shrink:0;">JK</span>
                                <span style="font-weight:600;color:#334155;">{{ $isLaki ? 'Laki-laki' : 'Perempuan' }}</span>
                            </div>
                            @if(!empty($s->tempat_lahir) || !empty($s->tanggal_lahir))
                            <div class="flex" style="gap:3px;">
                                <span style="width:30px;font-weight:700;color:#94a3b8;font-size:8px;text-transform:uppercase;flex-shrink:0;">TTL</span>
                                <span style="font-weight:600;color:#334155;font-size:8.5px;line-height:1.3;">{{ \App\Services\KartuSiswaFormatter::ttl($s, 'D MMM Y') }}</span>
                            </div>
                            @endif
                            @if(!empty($s->alamat))
                            <div class="flex" style="gap:3px;align-items:flex-start;">
                                <span style="width:30px;font-weight:700;color:#94a3b8;font-size:8px;text-transform:uppercase;flex-shrink:0;padding-top:1px;">ALMT</span>
                                <span style="font-weight:500;color:#475569;font-size:8px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $s->alamat }}</span>
                            </div>
                            @endif
                        </div>

                        <!-- QR Code -->
                        <div class="shrink-0 flex flex-col items-center" style="gap:2px;">
                            <div class="qr-box" style="padding:3px;background:#fff;border:1.5px solid #e2e8f0;border-radius:8px;width:62px;height:62px;display:flex;align-items:center;justify-content:center;"
                                 data-code="{{ $s->qr_value }}"
                                 id="qr-preview-{{ $s->id_siswa }}"></div>
                            <span style="font-size:7px;font-weight:800;color:#1e293b;font-family:monospace;letter-spacing:-.01em;">{{ $s->qr_value }}</span>
                            <span style="font-size:6.5px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;">Scan Absen</span>
                        </div>
                    </div>

                    <!-- Footer kartu: garis tanda tangan + cetak -->
                    <div class="flex items-end justify-between px-3 pb-2 pt-1 mt-auto" style="border-top:1px solid #f1f5f9;">
                        <div style="font-size:7px;color:#94a3b8;">
                            <div style="width:52px;border-bottom:1px solid #cbd5e1;margin-bottom:2px;"></div>
                            <span style="font-weight:600;">Kepala Sekolah</span>
                        </div>
                        <a href="{{ url($basePath . '/cetak?id_siswa=' . $s->id_siswa . '&id_kelas=' . $s->id_kelas . '&desain=duplex&v=4') }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 no-underline"
                           style="font-size:9px;font-weight:700;color:var(--primary-600,#4f46e5);background:var(--primary-50,#eef2ff);border:1px solid var(--primary-100,#e0e7ff);padding:3px 9px;border-radius:7px;transition:all .15s;">
                            <i class="bi bi-printer"></i> Cetak
                        </a>
                    </div>
                </div><!-- end inner content -->
                </div><!-- end right panel -->
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
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
                    correctLevel: QRCode.CorrectLevel.M
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
        let url = basePath + '/cetak?id_kelas=' + encodeURIComponent(idKelas);
        if (idSiswa !== '0') url += '&id_siswa=' + encodeURIComponent(idSiswa);
        url += '&desain=' + encodeURIComponent(desain);
        url += '&v=4';
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

    // Realtime search filter nama / NIS di preview grid
    $('#searchInput').on('keyup', function() {
        const query = $(this).val().toLowerCase().trim();
        $('.student-card-item').each(function() {
            // jQuery .data() mengubah string angka jadi Number, jadi .includes()
            // melempar TypeError dan membatalkan seluruh loop filter.
            // String() memastikan nilainya tetap teks.
            const name = String($(this).data('name') || '');
            const nis  = String($(this).data('nis') || '');
            const nisn = String($(this).data('nisn') || '');

            if (name.includes(query) || nis.includes(query) || nisn.includes(query)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>
@endsection
