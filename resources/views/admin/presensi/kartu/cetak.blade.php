<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Absen Siswa - {{ $nama_kelas }} - {{ $setting->sekolah ?? 'Sekolah' }}</title>
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
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
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
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background-color: var(--primary-600);
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: var(--primary-700);
        }
        .btn-secondary {
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background-color: #e2e8f0;
        }

        /* Sheet Container (A4 Printable Area) */
        .sheet-container {
            max-width: 210mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 6mm;
        }

        /* Grid Cards: 2 kolom, kartu ATM 85.6×54mm — 8 kartu per A4 */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(2, 85.6mm);
            gap: 4mm 6mm;
            justify-content: center;
        }
        .card-grid-portrait {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5mm;
            justify-items: center;
        }

        /* Single Student Mode */
        .single-mode {
            display: flex;
            justify-content: center;
            padding: 20px 0;
        }

        /* ═══════════════════════════════════════════════════════════
           Kartu ATM ISO/IEC 7810 ID-1: 85.60 × 53.98 mm
           Grid cetak A4: 2 kolom × 4 baris = 8 kartu per halaman
        ═══════════════════════════════════════════════════════════ */
        .id-card {
            width: 85.6mm;
            height: 53.98mm;
            border: 0.5px dashed #94a3b8;
            border-radius: 3mm;
            background: #ffffff;
            overflow: hidden;
            display: flex;
            flex-direction: row;
            position: relative;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Strip kiri: lebar 30mm dari total 85.6mm */
        .id-left-strip {
            width: 30mm;
            flex-shrink: 0;
            background: linear-gradient(175deg, var(--primary-700, #4338ca) 0%, var(--primary-900, #1e1b4b) 100%);
            display: flex;
            flex-direction: column;
        }
        .id-strip-header {
            display: flex;
            align-items: center;
            gap: 3px;
            padding: 1.8mm 2mm;
            border-bottom: 1px solid rgba(255,255,255,.15);
        }
        .id-strip-logo {
            width: 6mm; height: 6mm;
            background: #fff; border-radius: 3px; padding: 1px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .id-strip-logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .id-strip-kop { min-width: 0; }
        .id-strip-kop p { margin: 0; line-height: 1.1; }
        .id-strip-kop .kop-label { font-size: 4.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: rgba(255,255,255,.65); }
        .id-strip-kop .kop-sekolah { font-size: 5pt; font-weight: 800; color: #fff; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .id-strip-photo-area {
            flex: 1;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 1.5mm 2mm; gap: 1mm;
        }
        .id-strip-photo {
            width: 16mm; height: 20mm;
            border-radius: 1.5mm; overflow: hidden;
            border: 1px solid rgba(255,255,255,.35);
            background: #e2e8f0; flex-shrink: 0;
        }
        .id-strip-photo img { width: 100%; height: 100%; object-fit: cover; }
        .id-strip-jk {
            font-size: 4.5pt; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
            padding: 0.5px 4px; border-radius: 8px;
        }
        .id-strip-jk.L { background: rgba(96,165,250,.25); color: #bfdbfe; border: 0.5px solid rgba(147,197,253,.3); }
        .id-strip-jk.P { background: rgba(244,114,182,.25); color: #fbcfe8; border: 0.5px solid rgba(249,168,212,.3); }
        .id-strip-tp {
            padding: 1mm 2mm; text-align: center;
            border-top: 0.5px solid rgba(255,255,255,.12);
        }
        .id-strip-tp span { font-size: 4.5pt; font-weight: 700; color: rgba(255,255,255,.55); text-transform: uppercase; letter-spacing: .05em; }

        /* Panel kanan: identitas + QR + footer */
        .id-right-panel {
            flex: 1; min-width: 0;
            display: flex; flex-direction: column;
            position: relative;
            background-image: url('{{ asset("assets/img/bg-card.png") }}');
            background-size: cover;
            background-position: center;
        }
        .id-right-overlay {
            position: absolute; inset: 0;
            background: rgba(255, 255, 255, 0.82);
            pointer-events: none; z-index: 0;
        }
        .id-right-content {
            position: relative; z-index: 1;
            display: flex; flex-direction: column; flex: 1; min-width: 0;
        }
        .id-panel-top {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.8mm 2.5mm 0 2.5mm;
        }
        .id-panel-label { font-size: 4.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--primary-500, #6366f1); }
        .id-panel-badge {
            font-size: 5.5pt; font-weight: 900; padding: 0.5px 4px; border-radius: 2px;
            background: var(--primary-50, #eef2ff); color: var(--primary-700, #4338ca);
            border: 0.5px solid var(--primary-100, #e0e7ff); text-transform: uppercase;
        }
        .id-panel-name {
            padding: 0.8mm 2.5mm 0 2.5mm;
            font-size: 7.5pt; font-weight: 900; color: #0f172a; margin: 0;
            line-height: 1.15; text-transform: uppercase;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .id-panel-body {
            display: flex; gap: 1.5mm; padding: 1mm 2.5mm 0 2.5mm; flex: 1; align-items: flex-start;
        }
        .id-panel-data { flex: 1; min-width: 0; font-size: 5.5pt; line-height: 1.4; color: #334155; }
        .id-panel-data .row { display: flex; gap: 1.5px; }
        .id-panel-data .row .lbl { width: 8.5mm; font-weight: 700; color: #94a3b8; font-size: 4.8pt; text-transform: uppercase; flex-shrink: 0; }
        .id-panel-data .row .val { font-weight: 700; color: #0f172a; font-family: monospace; font-size: 5.5pt; }
        .id-panel-qr {
            flex-shrink: 0; display: flex; flex-direction: column; align-items: center; gap: 0.8mm;
        }
        .id-panel-qr .qr-print-box {
            width: 16mm; height: 16mm; padding: 0.8mm;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 1.5mm;
            display: flex; align-items: center; justify-content: center;
        }
        .id-panel-qr .qr-print-box canvas, .id-panel-qr .qr-print-box img, .id-panel-qr .qr-print-box table {
            max-width: 100% !important; max-height: 100% !important; display: block;
        }
        .id-panel-qr .qr-code-text { font-size: 4.5pt; font-weight: 800; color: #1e293b; font-family: monospace; }
        .id-panel-qr .qr-scan-text { font-size: 3.8pt; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
        .id-panel-footer {
            display: flex; align-items: flex-end; justify-content: space-between;
            padding: 0.8mm 2.5mm 1.5mm 2.5mm; margin-top: auto;
            border-top: 0.5px solid #f1f5f9;
        }
        .id-panel-sign { font-size: 4.8pt; color: #94a3b8; }
        .id-panel-sign .sign-line { width: 18mm; border-bottom: 0.5px solid #cbd5e1; margin-bottom: 1px; }
        .id-panel-sign .sign-label { font-weight: 600; }
        .id-panel-kota { font-size: 4.5pt; color: #64748b; }

        /* Portrait Card Box: vertikal 58 x 92mm, tanpa foto */
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
        .portrait-header {
            background: linear-gradient(180deg, var(--primary-800) 0%, var(--primary-900) 100%);
            color: #ffffff;
            padding: 2.5mm 3mm 2mm 3mm;
            text-align: center;
            border-bottom: 2px solid var(--primary-500);
            flex-shrink: 0;
        }
        .portrait-logo {
            width: 8mm;
            height: 8mm;
            background: #ffffff;
            border-radius: 5px;
            padding: 1.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        .portrait-logo img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .portrait-title { font-size: 6.5pt; font-weight: 800; text-transform: uppercase; margin: 1.2mm 0 0 0; line-height: 1.15; }
        .portrait-subtitle { font-size: 5pt; color: var(--primary-200); margin: 0.5mm 0 0 0; }
        .portrait-badge {
            display: inline-block;
            margin-top: 1mm;
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            padding: 0.5px 6px;
            border-radius: 4px;
            font-size: 6.5pt;
            font-weight: 800;
            text-transform: uppercase;
        }
        .portrait-qr {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 2mm 2mm 1mm 2mm;
            background: #fff;
            overflow: hidden;
        }
        .portrait-qr .portrait-qr-box { width: 40mm !important; height: 40mm !important; min-height: 40mm; padding: 2mm; background: #fff; border: 1px solid #e2e8f0; border-radius: 2mm; display: flex; align-items: center; justify-content: center; box-sizing: border-box; }
        .portrait-qr .portrait-qr-box canvas, .portrait-qr .portrait-qr-box img, .portrait-qr .portrait-qr-box table { width: 100% !important; height: 100% !important; max-width: 100% !important; max-height: 100% !important; display: block; image-rendering: pixelated; }
        .portrait-code { font-size: 7.5pt; font-weight: 800; color: #0f172a; font-family: monospace; margin-top: 1mm; }
        .portrait-scan { font-size: 4.8pt; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
        .portrait-name { font-size: 7.5pt; font-weight: 800; color: #0f172a; text-transform: uppercase; text-align: center; margin: 0; line-height: 1.2; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .portrait-meta { font-size: 5.5pt; color: #475569; text-align: center; margin: 0.5mm 0 0 0; font-family: monospace; }
        .portrait-footer {
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 1.5mm 3mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 5pt;
            color: #64748b;
        }

        /* Card Header */
        .card-header {
            background: linear-gradient(135deg, var(--primary-800) 0%, var(--primary-900) 100%);
            color: #ffffff;
            padding: 5px 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            height: 15mm;
            border-bottom: 2px solid var(--primary-500);
        }
        .card-logo {
            width: 11mm;
            height: 11mm;
            background: #ffffff;
            border-radius: 6px;
            padding: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
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
        .card-header-title {
            font-size: 7.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin: 0;
        }
        .card-header-subtitle {
            font-size: 5.8pt;
            color: var(--primary-200);
            margin: 1px 0 0 0;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-class-badge {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #ffffff;
            padding: 1px 5px;
            border-radius: 4px;
            font-size: 6.5pt;
            font-weight: 800;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        /* Card Body */
        .card-body {
            flex: 1;
            padding: 5px 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
        }
        .photo-container {
            width: 17mm;
            height: 22mm;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            overflow: hidden;
            background: #f1f5f9;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .student-details {
            flex: 1;
            min-width: 0;
        }
        .student-name {
            font-size: 8pt;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 4px 0;
            line-height: 1.15;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-transform: uppercase;
        }
        .info-table {
            font-size: 6pt;
            line-height: 1.35;
            color: #334155;
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 0.5px 0;
            vertical-align: middle;
        }
        .info-table .label {
            width: 12mm;
            font-weight: 700;
            color: #64748b;
        }
        .info-table .val {
            font-weight: 600;
            color: #0f172a;
        }

        /* QR Code Container */
        .qr-container {
            width: 22mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border-left: 1px dotted #e2e8f0;
            padding-left: 5px;
        }
        .qr-print-box {
            width: 19mm;
            height: 19mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-print-box canvas, .qr-print-box img {
            max-width: 100% !important;
            max-height: 100% !important;
            display: block;
        }
        .qr-caption {
            font-size: 4.8pt;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 2px;
            letter-spacing: 0.2px;
        }

        /* Card Footer */
        .card-footer {
            height: 5.5mm;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 0 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 5pt;
            color: #64748b;
        }
        .card-footer-left {
            font-weight: 600;
            color: #475569;
        }

        /* Print Media Queries */
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
            }
            .card-grid {
                gap: 3mm 5mm !important;
            }
            .card-grid-portrait {
                gap: 3.5mm !important;
            }
            .id-card, .id-card-portrait {
                border: 1px dashed #94a3b8 !important;
                box-shadow: none !important;
            }
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
    $desainAktif = $desain ?? 'landscape';
    $qKelas = (int) ($_GET['id_kelas'] ?? 0);
    $qSiswa = (int) ($_GET['id_siswa'] ?? 0);
    $baseQs = 'id_kelas=' . $qKelas . ($qSiswa ? '&id_siswa=' . $qSiswa : '');
    $curQs  = $_SERVER['QUERY_STRING'] ?? '';
    ?>
    <div class="top-bar no-print">
        <div class="top-bar-info">
            <i class="bi bi-printer-fill text-primary"></i>
            Siap Cetak: <strong>{{ count($siswa_list) }} Kartu</strong> ({{ $nama_kelas }})
            <?php if (!empty($terpotong)): ?>
                <span style="font-size: 11px; color: #b45309; margin-left: 8px;">
                    ⚠ Daftar dipotong pada {{ (int) ($batas_cetak ?? 500) }} kartu pertama — cetak sisanya dengan memilih kelas/siswa yang lebih kecil.
                </span>
            <?php endif; ?>
            <span style="margin-left: 8px; color: #94a3b8;">|</span>
            <span style="display:inline-flex;gap:6px;margin-left:8px;vertical-align:middle;">
                <a href="?{{ $baseQs }}&desain=landscape" class="btn {{ $desainAktif === 'landscape' ? 'btn-primary' : 'btn-secondary' }}" style="{{ $desainAktif === 'landscape' ? '' : 'background:#fff;' }}">
                    <i class="bi bi-credit-card-2-front"></i> Landscape + Foto
                </a>
                <a href="?{{ $baseQs }}&desain=portrait" class="btn {{ $desainAktif === 'portrait' ? 'btn-primary' : 'btn-secondary' }}" style="{{ $desainAktif === 'portrait' ? '' : 'background:#fff;' }}">
                    <i class="bi bi-credit-card"></i> Portrait − Foto
                </a>
            </span>
            <span style="font-size: 11px; color: #64748b; margin-left: 8px;">
                💡 Tips: Centang <strong>"Background graphics"</strong> pada dialog print browser agar warna kartu tercetak penuh.
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
        <?php if (empty($siswa_list)): ?>
            <div class="no-print" style="max-width:640px;margin:24px auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:32px;text-align:center;">
                <p style="font-size:15px;font-weight:800;color:#0f172a;margin:0 0 6px 0;">Tidak ada kartu untuk dicetak</p>
                <p style="font-size:12px;color:#64748b;margin:0 0 16px 0;">
                    <?php if (!empty($catatan)): ?>
                        {{ $catatan }}
                    <?php else: ?>
                        Pilih kelas / siswa lain pada halaman preview kartu, lalu buka kembali halaman cetak ini.
                    <?php endif; ?>
                </p>
                <button onclick="window.history.back()" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Preview</button>
            </div>
        <?php endif; ?>
        <?php if ($desainAktif === 'portrait'): ?>
        <div class="{{ $is_single ? 'single-mode' : 'card-grid-portrait' }}">
            <?php foreach ($siswa_list as $s): ?>
                <div class="id-card-portrait">
                    <div class="portrait-header">
                        <div class="portrait-logo">
                            <img src="{{ $logo_url }}" alt="Logo" onerror="this.src='{{ asset('assets/img/logo.png') }}'">
                        </div>
                        <h4 class="portrait-title">{{ $setting->sekolah ?? 'SMA NEGERI 2 AMFOANG TIMUR' }}</h4>
                        <p class="portrait-subtitle">KARTU PRESENSI DIGITAL • TP {{ $tp_active->tahun ?? '-' }}</p>
                        <span class="portrait-badge">{{ $s->nama_kelas }}</span>
                    </div>
                    <div class="portrait-qr">
                        <div class="portrait-qr-box qr-print-box" data-code="{{ $s->qr_value }}" data-size="200"></div>
                        <span class="portrait-code">{{ $s->qr_value }}</span>
                        <span class="portrait-scan">Scans Absen</span>
                        <p class="portrait-name" style="margin-top:2mm;" title="{{ $s->nama }}">{{ $s->nama }}</p>
                        <p class="portrait-meta">NIS {{ $s->nis ?: '-' }} • NISN {{ $s->nisn ?: '-' }}</p>
                    </div>
                    <div class="portrait-footer">
                        <span>Wajib dibawa KBM / Harian</span>
                        <span>{{ $setting->kota ?? 'Kab. Kupang' }}</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- ═══ LANDSCAPE: Kartu Pelajar / ID Card — split kiri-kanan ═══ -->
        <div class="{{ $is_single ? 'single-mode' : 'card-grid' }}">
            <?php foreach ($siswa_list as $s): ?>
                <?php $isLaki = ($s->jenis_kelamin ?? 'L') === 'L'; ?>
                <div class="id-card">
                    <!-- ── Strip Kiri: gradient + logo + foto + JK ── -->
                    <div class="id-left-strip">
                        <div class="id-strip-header">
                            <div class="id-strip-logo">
                                <img src="{{ $logo_url }}" alt="Logo" onerror="this.src='{{ asset('assets/img/logo.png') }}'">
                            </div>
                            <div class="id-strip-kop">
                                <p class="kop-label">KARTU PELAJAR</p>
                                <p class="kop-sekolah">{{ $setting->sekolah ?? 'SEKOLAH' }}</p>
                            </div>
                        </div>
                        <div class="id-strip-photo-area">
                            <div class="id-strip-photo">
                                <img src="{{ $s->foto_url }}" alt="{{ $s->nama }}"
                                     onerror="this.src='{{ asset('assets/img/siswa.png') }}'">
                            </div>
                            <span class="id-strip-jk {{ $isLaki ? 'L' : 'P' }}">{{ $isLaki ? 'Laki-laki' : 'Perempuan' }}</span>
                        </div>
                        <div class="id-strip-tp">
                            <span>TP {{ $tp_active->tahun ?? '-' }}</span>
                        </div>
                    </div>

                    <!-- ── Panel Kanan: identitas + QR ── -->
                    <div class="id-right-panel">
                        <div class="id-right-overlay"></div>
                        <div class="id-right-content">
                        <div class="id-panel-top">
                            <span class="id-panel-label">KARTU SISWA AKTIF</span>
                            <span class="id-panel-badge">{{ $s->nama_kelas }}</span>
                        </div>
                        <p class="id-panel-name" title="{{ $s->nama }}">{{ strtoupper($s->nama) }}</p>
                        <div class="id-panel-body">
                            <div class="id-panel-data">
                                <div class="row"><span class="lbl">NIS</span><span class="val">{{ $s->nis ?: '-' }}</span></div>
                                <div class="row"><span class="lbl">NISN</span><span class="val">{{ $s->nisn ?: '-' }}</span></div>
                                <div class="row"><span class="lbl">KELAS</span><span class="val" style="font-family:sans-serif;">{{ $s->nama_kelas }}</span></div>
                                <div class="row"><span class="lbl">JK</span><span class="val" style="font-family:sans-serif;">{{ $isLaki ? 'Laki-laki' : 'Perempuan' }}</span></div>
                                @if(!empty($s->tempat_lahir) || !empty($s->tanggal_lahir))
                                <div class="row" style="align-items:flex-start;">
                                    <span class="lbl">TTL</span>
                                    <span class="val" style="font-family:sans-serif;font-size:5.5pt;line-height:1.3;">{{ trim(($s->tempat_lahir ?? '') . ($s->tempat_lahir && $s->tanggal_lahir ? ', ' : '') . ($s->tanggal_lahir ? \Carbon\Carbon::parse($s->tanggal_lahir)->locale('id')->isoFormat('D MMM Y') : '')) ?: '-' }}</span>
                                </div>
                                @endif
                                @if(!empty($s->alamat))
                                <div class="row" style="align-items:flex-start;">
                                    <span class="lbl">ALMT</span>
                                    <span class="val" style="font-family:sans-serif;font-size:5pt;font-weight:500;color:#475569;line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $s->alamat }}</span>
                                </div>
                                @endif
                            </div>
                            <div class="id-panel-qr">
                                <div class="qr-print-box" data-code="{{ $s->qr_value }}"></div>
                                <span class="qr-code-text">{{ $s->qr_value }}</span>
                                <span class="qr-scan-text">Scan Absen</span>
                            </div>
                        </div>
                        <div class="id-panel-footer">
                            <div class="id-panel-sign">
                                <div class="sign-line"></div>
                                <span class="sign-label">Kepala Sekolah</span>
                            </div>
                            <span class="id-panel-kota">{{ $setting->kota ?? 'Kab. Kupang' }}</span>
                        </div>
                        </div><!-- end id-right-content -->
                    </div><!-- end id-right-panel -->
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- QR Code Script -->
    <script src="{{ asset('assets/app/js/qrcode.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const qrBoxes = document.querySelectorAll('.qr-print-box');
            qrBoxes.forEach(function(box) {
                const code = box.getAttribute('data-code');
                const size = parseInt(box.getAttribute('data-size') || '56', 10) || 56;
                if (code) {
                    try {
                        new QRCode(box, {
                            text: String(code),
                            width: size,
                            height: size,
                            colorDark: "#000000",
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
        });
    </script>
</body>
</html>
