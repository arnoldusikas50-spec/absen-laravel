<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>{{ $title ?? 'Scanner QR Presensi Siswa' }}</title>

    <?php
    $setting = $setting ?? new \stdClass();
    $setting->logo_kiri = $setting->logo_kiri ?? null;
    $logo_app = ($setting->logo_kiri == null) ? asset('assets/img/favicon.png') : url('' . $setting->logo_kiri);
    ?>

    <link rel="shortcut icon" href="{{ $logo_app }}" type="image/x-icon">

    <!-- Fonts: Inter, Plus Jakarta Sans & JetBrains Mono (sama dengan kiosk) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons & SweetAlert2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/app/css/tailwind.css') }}?v={{ @filemtime(public_path() . '/' . 'assets/app/css/tailwind.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/app/css/kiosk-ui.css') }}?v={{ @filemtime(public_path() . '/' . 'assets/app/css/kiosk-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.css') }}">

    <script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ url('assets/js/wita-clock.js?v=' . (@filemtime(public_path() . '/' . 'assets/js/wita-clock.js') ?: time())) }}"></script>

    <script>
        // Perangkat baru mendaftar lewat /scanner?k=TOKEN. Server sudah memasang
        // cookie gerbangnya, jadi token tidak diperlukan lagi di URL — buang dari
        // address bar agar tidak tertinggal di riwayat browser kiosk.
        (function () {
            try {
                var u = new URL(window.location.href);
                if (!u.searchParams.has('k')) return;
                u.searchParams.delete('k');
                window.history.replaceState({}, document.title, u.pathname + u.search + u.hash);
            } catch (e) {}
        })();
    </script>

    <style>
        body {
            font-family: 'Inter', 'Plus Jakarta Sans', sans-serif;
            background-color: #060b13;
            color: #f8fafc;
            overflow-x: hidden;
            -webkit-user-select: none;
            user-select: none;
        }

        .font-mono-num {
            font-family: 'JetBrains Mono', monospace;
            font-variant-numeric: tabular-nums;
        }

        /* Ambient Cyber Lighting */
        .ambient-glow {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background:
                radial-gradient(circle at 18% 22%, rgba(20, 184, 166, 0.12) 0%, transparent 45%),
                radial-gradient(circle at 82% 18%, rgba(99, 102, 241, 0.09) 0%, transparent 45%),
                radial-gradient(circle at 50% 85%, rgba(16, 185, 129, 0.08) 0%, transparent 50%);
        }

        .cyber-grid {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background-image: radial-gradient(rgba(148, 163, 184, 0.07) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        /* Glassmorphism Panels */
        .glass-panel {
            background: rgba(13, 20, 36, 0.72);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
        }

        .glass-card {
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        /* Warna bingkai hasil scan (lihat juga halaman kiosk) */
        #scannerContainer.state-success {
            border-color: rgba(16, 185, 129, .75);
            box-shadow: 0 20px 50px rgba(0, 0, 0, .45), 0 0 0 1px rgba(16, 185, 129, .35), 0 0 45px rgba(16, 185, 129, .18);
        }
        #scannerContainer.state-warning {
            border-color: rgba(245, 158, 11, .75);
            box-shadow: 0 20px 50px rgba(0, 0, 0, .45), 0 0 0 1px rgba(245, 158, 11, .35), 0 0 45px rgba(245, 158, 11, .18);
        }
        #scannerContainer.state-info {
            border-color: rgba(59, 130, 246, .75);
            box-shadow: 0 20px 50px rgba(0, 0, 0, .45), 0 0 0 1px rgba(59, 130, 246, .35), 0 0 45px rgba(59, 130, 246, .18);
        }
        #scannerContainer.state-error {
            border-color: rgba(244, 63, 94, .8);
            box-shadow: 0 20px 50px rgba(0, 0, 0, .45), 0 0 0 1px rgba(244, 63, 94, .4), 0 0 45px rgba(244, 63, 94, .2);
        }

        /* Mode Button Active */
        .mode-btn.active {
            background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
            color: #02120d;
            font-weight: 800;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.45);
            border-color: rgba(255, 255, 255, 0.3);
        }

        /* Custom Scrollbars */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.4); }
        ::-webkit-scrollbar-thumb { background: rgba(51, 65, 85, 0.8); border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(71, 85, 105, 1); }

        /* ── Viewport kamera ─────────────────────────────── */
        .camera-frame {
            position: relative;
            width: 100%;
            background: #020617;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: inset 0 0 40px rgba(0, 0, 0, 0.85);
            aspect-ratio: 16 / 9;
        }
        @media (max-width: 640px) {
            .camera-frame { aspect-ratio: 4 / 3; }
        }
        #qr-reader-desktop { width: 100% !important; border: none !important; }
        #qr-reader-desktop video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }
        #qr-reader-desktop img[alt="Info icon"] { display: none !important; }
        #qr-reader-desktop div[style*="text-align: center"] { display: none !important; }
        #qr-reader-desktop__dashboard { display: none !important; }

        .scanner-corner {
            position: absolute;
            width: 30px;
            height: 30px;
            border-color: #10b981;
            border-style: solid;
            z-index: 15;
            pointer-events: none;
            transition: border-color 0.3s ease;
        }
        .scanner-corner-tl { top: 14px; left: 14px; border-width: 3px 0 0 3px; border-top-left-radius: 8px; }
        .scanner-corner-tr { top: 14px; right: 14px; border-width: 3px 3px 0 0; border-top-right-radius: 8px; }
        .scanner-corner-bl { bottom: 14px; left: 14px; border-width: 0 0 3px 3px; border-bottom-left-radius: 8px; }
        .scanner-corner-br { bottom: 14px; right: 14px; border-width: 0 3px 3px 0; border-bottom-right-radius: 8px; }

        .scanner-laser-line {
            position: absolute;
            left: 20px;
            right: 20px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #34d399, #10b981, #34d399, transparent);
            box-shadow: 0 0 16px #10b981, 0 0 24px rgba(16, 185, 129, 0.6);
            z-index: 14;
            animation: laserScanning 2.4s ease-in-out infinite;
            pointer-events: none;
        }
        @keyframes laserScanning {
            0%   { top: 12%; opacity: 0; }
            15%  { opacity: 1; }
            85%  { opacity: 1; }
            100% { top: 88%; opacity: 0; }
        }

        .camera-top-hud {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 6px;
            z-index: 25;
            pointer-events: auto;
        }
        .camera-top-hud > div { min-width: 0; flex-wrap: wrap; gap: 6px; }

        .hud-control {
            height: 34px;
            padding: 0 10px;
            border-radius: 10px;
            background: rgba(2, 6, 23, 0.78);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.22);
            color: #f8fafc;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
        }
        .hud-control:hover { background: rgba(15, 23, 42, 0.95); border-color: rgba(255, 255, 255, 0.45); }
        .hud-control.active { background: #f59e0b; border-color: #fbbf24; color: #fff; }
        #select-video-device { max-width: 150px; text-overflow: ellipsis; }
        #select-video-device option { background-color: #0f172a; color: #f8fafc; }
        @media (max-width: 640px) {
            #select-video-device { max-width: 112px; }
        }

        .camera-hint-pill {
            position: absolute;
            left: 50%;
            bottom: 12px;
            transform: translateX(-50%);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 9999px;
            background: rgba(2, 6, 23, 0.75);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #e2e8f0;
            font-size: 10.5px;
            font-weight: 600;
            z-index: 12;
            pointer-events: none;
            max-width: calc(100% - 28px);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── Input gelap (selaras kiosk) ─────────────────── */
        .dark-input {
            background: rgba(2, 6, 23, 0.7);
            border: 1px solid rgba(51, 65, 85, 0.9);
            color: #f8fafc;
            border-radius: 12px;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .dark-input::placeholder { color: #64748b; }
        .dark-input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.18);
        }

        /* ── Modal pengaturan suara ──────────────────────── */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.78);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            padding: 16px;
        }
        .modal-backdrop.active { display: flex; }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between selection:bg-emerald-500 selection:text-slate-950 relative">

    <!-- Ambient Glow & Cyber Grid -->
    <div class="ambient-glow"></div>
    <div class="cyber-grid"></div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- HEADER / TOP BAR (SAMA DENGAN KIOSK)                                     -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <header class="bg-slate-950/80 backdrop-blur-xl border-b border-slate-800/80 px-4 sm:px-6 lg:px-8 py-3 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center gap-x-2.5 gap-y-2.5 sm:gap-x-4">

            <!-- Identitas Sekolah -->
            <div class="order-1 flex items-center gap-2.5 sm:gap-3 min-w-0 flex-1">
                <img src="{{ $logo_app }}" alt="Logo {{ $setting->nama_sekolah ?? 'sekolah' }}" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl object-contain bg-slate-900/90 p-1 border border-slate-700/80 shadow-md shrink-0">
                <div class="min-w-0">
                    <h1 class="text-sm sm:text-base font-extrabold tracking-tight text-white truncate">
                        {{ $setting->nama_sekolah ?? 'Scanner QR' }}
                    </h1>
                    <p id="header-status-badge" class="text-[11px] sm:text-xs text-slate-400 m-0 truncate">
                        Absensi - Mode Scanner QRcode
                    </p>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="order-2 lg:order-3 flex items-center gap-1.5 sm:gap-2 shrink-0">
                <button type="button" id="btn-open-audio" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 flex items-center justify-center transition-all hover:scale-105 active:scale-95" title="Pengaturan Suara" aria-label="Buka pengaturan suara">
                    <i id="header-audio-icon" class="fa-solid fa-volume-high text-emerald-400 text-xs sm:text-sm"></i>
                </button>

                <button type="button" id="btn-fullscreen" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 flex items-center justify-center transition-all hover:scale-105 active:scale-95" title="Layar Penuh" aria-label="Alihkan layar penuh">
                    <i id="fullscreenIcon" class="fa-solid fa-expand text-xs sm:text-sm"></i>
                </button>

                <a href="{{ url('kiosk') }}" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-cyan-400 border border-slate-800 flex items-center justify-center transition-all hover:scale-105 active:scale-95" title="Buka Terminal Kiosk RFID / NFC" aria-label="Buka terminal kiosk RFID">
                    <i class="fa-solid fa-wifi rotate-90 text-xs sm:text-sm"></i>
                </a>

                @if(!empty($isLoggedIn))
                <a href="{{ url('admin/presensi') }}" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-rose-500/10 text-rose-400 hover:bg-rose-500 hover:text-white border border-rose-500/20 flex items-center justify-center transition-all hover:scale-105 active:scale-95" title="Keluar" aria-label="Tutup scanner dan kembali ke panel admin">
                    <i class="fa-solid fa-xmark text-xs sm:text-sm"></i>
                </a>
                @endif
            </div>

            <!-- Jam + Mode -->
            <div class="order-3 lg:order-2 flex w-full lg:w-auto items-center justify-center lg:justify-end gap-2 sm:gap-4">

                <div class="flex items-center justify-center gap-2 bg-slate-900/90 px-2.5 sm:px-5 py-1 sm:py-1.5 rounded-xl sm:rounded-2xl border border-slate-700/70 shadow-[0_0_20px_rgba(16,185,129,0.12)] shrink-0">
                    <div id="liveTime" class="text-sm sm:text-lg font-mono-num font-extrabold text-emerald-400 tracking-widest drop-shadow-[0_0_8px_rgba(52,211,153,0.5)]">--:--:--</div>
                    <div id="liveDate" class="hidden md:block text-[10px] font-medium text-slate-400 tracking-wide truncate max-w-[140px]">--</div>
                </div>

                <div class="inline-flex p-1 rounded-2xl bg-slate-900/90 border border-slate-800 text-xs font-semibold" id="mode-group" role="group" aria-label="Mode presensi">
                    <button type="button" class="mode-btn active px-2.5 sm:px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5" data-mode="auto" aria-pressed="true" title="Mode otomatis mengikuti jadwal">
                        <i class="fa-solid fa-bolt text-xs" aria-hidden="true"></i> <span>Auto</span>
                    </button>
                    <button type="button" class="mode-btn px-2.5 sm:px-3 py-1.5 rounded-xl text-slate-400 hover:text-white transition-all flex items-center gap-1.5" data-mode="masuk" aria-pressed="false" title="Paksa absen masuk">
                        <i class="fa-solid fa-arrow-right-to-bracket text-xs" aria-hidden="true"></i> <span>Masuk</span>
                    </button>
                    <button type="button" class="mode-btn px-2.5 sm:px-3 py-1.5 rounded-xl text-slate-400 hover:text-white transition-all flex items-center gap-1.5" data-mode="pulang" aria-pressed="false" title="Paksa absen pulang">
                        <i class="fa-solid fa-arrow-right-from-bracket text-xs" aria-hidden="true"></i> <span>Pulang</span>
                    </button>
                </div>
            </div>

        </div>
    </header>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- MAIN CONTENT (7 KOLOM KIRI / 5 KOLOM KANAN)                              -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6 items-stretch relative z-10">

        <!-- ── KOLOM KIRI: KAMERA, HASIL SCAN & INPUT ── -->
        <section class="lg:col-span-7 flex flex-col gap-4 sm:gap-5">

            <div id="scannerContainer" class="flex-1 glass-panel rounded-3xl p-5 sm:p-6 md:p-7 flex flex-col items-center justify-center relative overflow-hidden transition-all duration-300 min-h-[400px] sm:min-h-[480px]">

                <div id="glowEffect" class="absolute inset-0 bg-gradient-to-b from-teal-500/10 via-transparent to-transparent pointer-events-none transition-colors duration-500"></div>

                <!-- Status Atas Bar -->
                <div class="absolute top-4 left-5 right-5 flex items-center justify-between gap-2 text-xs text-slate-400 z-10 pointer-events-none">
                    <div>
                        @if(!empty($qr_window))
                        <span class="px-3 py-1 rounded-full bg-slate-900/70 border border-slate-800 text-slate-400 font-mono-num text-[11px] inline-flex items-center gap-1.5 shadow-sm">
                            <i class="fa-regular fa-clock text-[10px] text-teal-400"></i> {{ $qr_window }}
                        </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-1.5 font-mono-num text-[11px] text-slate-400">
                        <span id="gps-lock-pill" class="px-2.5 py-1 rounded-full bg-slate-900/80 text-slate-300 border border-slate-700/80 inline-flex items-center gap-1.5 shadow-sm" title="Status GPS untuk geofence">
                            <i id="gps-lock-icon" class="fa-solid fa-location-dot text-[10px] text-slate-400"></i>
                            <span id="gps-lock-text">GPS…</span>
                        </span>
                        <span id="reader-status-pill" class="px-3 py-1 rounded-full bg-slate-900/80 text-slate-300 border border-slate-700/80 flex items-center gap-1.5 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span id="reader-status">Memuat…</span>
                        </span>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────── -->
                <!-- TAMPILAN 1: KAMERA & INPUT SCANNER              -->
                <!-- ────────────────────────────────────────────── -->
                <div id="cameraView" class="w-full z-10 flex flex-col my-auto pt-7 sm:pt-8">

                    <div class="camera-frame" id="camera-frame-box">
                        <div id="qr-reader-desktop"></div>

                        <!-- Neon Corners & Laser -->
                        <div class="scanner-corner scanner-corner-tl"></div>
                        <div class="scanner-corner scanner-corner-tr"></div>
                        <div class="scanner-corner scanner-corner-bl"></div>
                        <div class="scanner-corner scanner-corner-br"></div>
                        <div class="scanner-laser-line" id="scanner-laser"></div>

                        <!-- HUD atas -->
                        <div class="camera-top-hud">
                            <div class="flex items-center">
                                <div class="relative">
                                    <select id="select-video-device" class="hud-control pr-6 appearance-none cursor-pointer" aria-label="Pilih kamera">
                                        <option value="">Deteksi kamera…</option>
                                    </select>
                                    <i class="fa-solid fa-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-white/60 text-[9px] pointer-events-none"></i>
                                </div>

                                <button type="button" id="btn-flip-cam" class="hud-control" title="Ganti kamera depan/belakang">
                                    <i class="fa-solid fa-arrows-rotate text-xs"></i>
                                    <span class="hidden sm:inline">Flip</span>
                                </button>

                                <button type="button" id="btn-desktop-torch" class="hud-control hidden" title="Lampu senter">
                                    <i class="fa-solid fa-bolt text-xs text-amber-400"></i>
                                    <span class="hidden sm:inline">Senter</span>
                                </button>
                            </div>

                            <div class="flex items-center">
                                <button type="button" id="btn-desktop-pause" class="hud-control" title="Jeda kamera">
                                    <i class="fa-solid fa-pause text-xs" id="icon-pause-cam"></i>
                                    <span class="hidden sm:inline" id="label-pause-cam">Jeda</span>
                                </button>
                            </div>
                        </div>

                        <!-- Hint bawah -->
                        <div class="camera-hint-pill">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span id="scan-hint-text">Arahkan QR code ke kamera</span>
                        </div>

                        <!-- Loading Overlay -->
                        <div id="camera-loading-overlay" class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-slate-950/90 text-white gap-2 p-4">
                            <div class="w-8 h-8 rounded-full border-2 border-emerald-500 border-t-transparent animate-spin"></div>
                            <p class="text-xs text-slate-300 m-0">Menghubungkan kamera…</p>
                        </div>

                        <!-- Error Overlay -->
                        <div id="camera-error-overlay" class="absolute inset-0 z-20 hidden flex-col items-center justify-center bg-slate-950/95 text-white gap-3 p-6 text-center">
                            <div class="w-12 h-12 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-400 text-xl">
                                <i class="fa-solid fa-video-slash"></i>
                            </div>
                            <div>
                                <h6 class="font-semibold text-sm m-0 text-white">Kamera Belum Terhubung</h6>
                                <p class="text-xs text-slate-400 mt-1 m-0">Gunakan scanner gun / input manual di bawah, atau muat ulang kamera.</p>
                            </div>
                            <button type="button" id="btn-retry-cam" class="px-3.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white font-medium text-xs border border-slate-600 transition-colors">
                                Muat Ulang Kamera
                            </button>
                        </div>
                    </div>

                    <!-- Catatan jadwal mode -->
                    <p id="mode-status-note" class="hidden text-[11px] font-semibold text-amber-400 text-center leading-snug mt-2 mb-0"></p>

                    <!-- Input Scanner Gun & Filter Kelas -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 mt-3.5">
                        <div class="sm:col-span-7">
                            <label for="input-barcode-scanner" class="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5 mb-1.5">
                                <i class="fa-solid fa-barcode text-slate-500"></i>
                                <span>Scanner Gun QR / Input Manual</span>
                            </label>
                            <div class="relative">
                                <i class="fa-solid fa-keyboard absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs pointer-events-none"></i>
                                <input type="text" id="input-barcode-scanner" autocomplete="off" autofocus
                                    placeholder="Scan QR atau ketik NIS…"
                                    class="dark-input w-full pl-9 pr-24 py-2.5 text-xs font-medium">
                                <button type="button" id="btn-barcode-proses"
                                    class="absolute right-1.5 top-1/2 -translate-y-1/2 px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-[11px] font-bold transition-colors">
                                    Proses
                                </button>
                            </div>
                        </div>

                        <div class="sm:col-span-5">
                            <label for="select-kelas-filter" class="block text-[11px] font-semibold text-slate-400 mb-1.5">Filter Kategori</label>
                            <select id="select-kelas-filter" class="dark-input w-full px-3 py-2.5 text-xs font-medium">
                                <option value="">Semua (Siswa &amp; Guru)</option>
                                <option value="guru">Khusus Guru / GTK</option>
                                @if(!empty($kelasList))
                                    <optgroup label="Filter Per Kelas Siswa">
                                        @foreach($kelasList as $k)
                                            <option value="{{ (int) $k->id_kelas }}" {{ ((int) $selectedKelas === (int) $k->id_kelas) ? 'selected' : '' }}>
                                                {{ $k->nama_kelas }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────── -->
                <!-- TAMPILAN 2: HASIL SCAN SISWA / GURU            -->
                <!-- ────────────────────────────────────────────── -->
                <div id="resultView" class="hidden w-full z-10 flex-col justify-between my-auto py-2">

                    <div id="statusBanner" class="w-full py-3.5 px-5 rounded-2xl mb-5 flex items-center justify-between gap-2 font-bold text-sm sm:text-base transition-colors shadow-lg bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                            <i id="statusIcon" class="fa-solid fa-circle-check text-xl shrink-0"></i>
                            <span id="statusText" class="truncate">ABSEN MASUK BERHASIL</span>
                        </div>
                        <span id="scanTimestamp" class="font-mono-num text-xs sm:text-sm px-2.5 py-1 rounded-lg bg-black/20 shrink-0">--:--:-- WITA</span>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-5 sm:gap-6 bg-slate-950/70 p-5 sm:p-6 rounded-2xl border border-slate-800">
                        <div class="relative shrink-0">
                            <img id="personPhoto" src="{{ asset('assets/img/siswa.png') }}" alt="Foto"
                                 class="w-24 h-24 sm:w-32 sm:h-32 rounded-2xl object-cover border-2 border-slate-700 shadow-xl bg-slate-800">
                            <div id="photoBadge" class="absolute -bottom-2 -right-2 w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center shadow-lg border-2 border-slate-900">
                                <i id="photoBadgeIcon" class="fa-solid fa-check text-sm"></i>
                            </div>
                        </div>

                        <div class="flex-1 text-center sm:text-left space-y-2.5 w-full min-w-0">
                            <div>
                                <div class="flex items-center justify-center sm:justify-start gap-2 mb-1">
                                    <span id="roleBadge" class="px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        SISWA
                                    </span>
                                    <span class="text-xs font-mono text-slate-400 uppercase tracking-wider">Terverifikasi</span>
                                </div>
                                <h3 id="personName" class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white tracking-tight leading-tight truncate">
                                    Nama Lengkap
                                </h3>
                            </div>

                            <div class="grid grid-cols-2 gap-2 sm:gap-3 pt-2.5 border-t border-slate-800/80">
                                <div>
                                    <p id="idNumberLabel" class="text-[11px] text-slate-400 m-0">NIS / NISN</p>
                                    <p id="personIdNumber" class="font-mono-num font-semibold text-slate-200 text-xs sm:text-sm m-0 truncate">-</p>
                                </div>
                                <div>
                                    <p id="departmentLabel" class="text-[11px] text-slate-400 m-0">Kelas</p>
                                    <p id="personDepartment" class="font-semibold text-emerald-400 text-xs sm:text-sm m-0 truncate">-</p>
                                </div>
                                <div>
                                    <p class="text-[11px] text-slate-400 m-0">Metode Scan</p>
                                    <p id="personMethod" class="font-mono-num text-xs text-slate-400 m-0 truncate">-</p>
                                </div>
                                <div>
                                    <p class="text-[11px] text-slate-400 m-0">Keterangan</p>
                                    <p id="personNote" class="font-semibold text-emerald-400 text-xs sm:text-sm m-0 truncate">Tepat Waktu</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="flex justify-between text-xs text-slate-400 mb-1.5">
                            <span class="text-[11px]">Kembali ke kamera otomatis…</span>
                            <span id="countdownText" class="font-mono-num font-bold text-xs text-slate-300">4 dtk</span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                            <div id="resetProgressBar" class="h-full bg-emerald-500 transition-all duration-100 ease-linear" style="width: 100%"></div>
                        </div>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────── -->
                <!-- TAMPILAN 3: GAGAL / DI LUAR JADWAL             -->
                <!-- ────────────────────────────────────────────── -->
                <div id="errorView" class="hidden flex-col items-center text-center z-10 max-w-md mx-auto my-auto py-4">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl bg-rose-500/10 border border-rose-500/30 text-rose-500 flex items-center justify-center text-4xl sm:text-5xl mb-4 shadow-lg shadow-rose-500/15">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-white mb-2">Presensi Tidak Dapat Diproses</h2>
                    <p id="errorMessage" class="text-slate-400 text-xs sm:text-sm mb-2">Terjadi kesalahan.</p>
                    <p class="text-[11px] text-slate-500 m-0">Periksa kartu/QR, jadwal presensi, atau hubungi admin sekolah.</p>
                </div>

            </div>

        </section>

        <!-- ── KOLOM KANAN: STATISTIK & LOG AKTIVITAS ── -->
        <aside class="lg:col-span-5 flex flex-col gap-4 sm:gap-5">

            <div class="grid grid-cols-2 gap-3 sm:gap-4">

                <div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:border-slate-700 transition-all shadow-lg">
                    <div class="flex items-center justify-between text-[11px] font-bold tracking-wider text-slate-400 mb-2">
                        <span>TOTAL HADIR</span>
                        <i class="fa-solid fa-users text-emerald-400 text-sm"></i>
                    </div>
                    <div id="statTotalHadir" class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white font-mono-num">{{ (int) ($stats['hadir'] ?? 0) }}</div>
                    <div class="text-[11px] text-emerald-400/80 mt-1 font-medium">Siswa &amp; Guru</div>
                </div>

                <div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:border-slate-700 transition-all shadow-lg">
                    <div class="flex items-center justify-between text-[11px] font-bold tracking-wider text-slate-400 mb-2">
                        <span>TEPAT WAKTU</span>
                        <i class="fa-solid fa-square-check text-teal-400 text-sm"></i>
                    </div>
                    <div id="statTepatWaktu" class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-teal-400 font-mono-num">{{ max(0, (int) ($stats['hadir'] ?? 0) - (int) ($stats['terlambat'] ?? 0)) }}</div>
                    <div class="text-[11px] text-teal-400/80 mt-1 font-medium">Tepat Waktu</div>
                </div>

                <div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:border-slate-700 transition-all shadow-lg">
                    <div class="flex items-center justify-between text-[11px] font-bold tracking-wider text-slate-400 mb-2">
                        <span>TERLAMBAT</span>
                        <i class="fa-solid fa-clock-rotate-left text-amber-400 text-sm"></i>
                    </div>
                    <div id="statTerlambat" class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-amber-400 font-mono-num">{{ (int) ($stats['terlambat'] ?? 0) }}</div>
                    <div class="text-[11px] text-amber-400/80 mt-1 font-medium">Terlambat</div>
                </div>

                <div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:border-slate-700 transition-all shadow-lg">
                    <div class="flex items-center justify-between text-[11px] font-bold tracking-wider text-slate-400 mb-2">
                        <span>SUDAH PULANG</span>
                        <i class="fa-solid fa-arrow-right-from-bracket text-blue-400 text-sm"></i>
                    </div>
                    <div id="statSudahPulang" class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-blue-400 font-mono-num">{{ (int) ($stats['keluar'] ?? 0) }}</div>
                    <div class="text-[11px] text-blue-400/80 mt-1 font-medium">Sudah Pulang</div>
                </div>

            </div>

            <div class="flex-1 glass-card rounded-3xl p-4 sm:p-5 flex flex-col min-h-[360px] sm:min-h-[400px]">
                <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-tower-broadcast text-emerald-400 text-sm" aria-hidden="true"></i>
                        <h3 class="font-bold text-white text-sm sm:text-base m-0">Aktivitas Scan Terkini</h3>
                    </div>
                    <span class="text-[11px] font-semibold bg-slate-800/80 text-slate-300 px-2.5 py-1 rounded-full border border-slate-700/80 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                        Live Feed
                    </span>
                </div>

                <div id="attendanceLogList" class="flex-1 overflow-y-auto space-y-2.5 pt-3.5 pr-1 max-h-[380px] sm:max-h-[420px]">
                    <div id="empty-feed-placeholder" class="py-12 text-center text-xs text-slate-500">
                        <i class="fa-solid fa-inbox text-2xl mb-1.5 block text-slate-600"></i>
                        Belum ada aktivitas presensi hari ini
                    </div>
                </div>
            </div>

        </aside>

    </main>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- FOOTER INFORMASI                                                        -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <footer class="bg-slate-950/60 border-t border-slate-800/80 py-3 px-4 sm:px-8 text-center sm:text-left flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2 relative z-10">
        <div>
            &copy; {{ date('Y') }} {{ $setting->nama_sekolah ?? 'Sistem Presensi' }}
        </div>
        <div class="flex items-center gap-3 text-[11px]">
            <span class="hidden lg:inline-flex items-center gap-1.5 text-slate-500">
                <i class="fa-solid fa-keyboard"></i> F layar penuh &middot; M mode &middot; S suara
            </span>
            <span class="flex items-center gap-1.5 text-emerald-400">
                <i class="fa-solid fa-wifi"></i> Online
            </span>
        </div>
    </footer>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL PENGATURAN SUARA                                                  -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div id="modal-audio" class="modal-backdrop">
        <div class="glass-panel w-full max-w-sm rounded-3xl p-5" role="dialog" aria-modal="true" aria-labelledby="modal-audio-title">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-volume-high text-emerald-400 text-sm"></i>
                    <h3 id="modal-audio-title" class="font-bold text-white text-sm m-0">Pengaturan Suara</h3>
                </div>
                <button type="button" id="btn-close-audio" class="w-8 h-8 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition-colors" aria-label="Tutup pengaturan suara">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <div class="space-y-2.5 mb-4">
                <label class="flex items-center justify-between gap-3 p-3 rounded-2xl bg-slate-950/70 border border-slate-800 cursor-pointer hover:bg-slate-900 transition-colors">
                    <span class="min-w-0">
                        <span class="block text-xs font-semibold text-white">Nada Notifikasi</span>
                        <span class="block text-[11px] text-slate-400">Bunyi saat presensi tercatat</span>
                    </span>
                    <input type="checkbox" id="setting-sound-chime" class="w-5 h-5 accent-emerald-500 rounded cursor-pointer shrink-0">
                </label>

                <label class="flex items-center justify-between gap-3 p-3 rounded-2xl bg-slate-950/70 border border-slate-800 cursor-pointer hover:bg-slate-900 transition-colors">
                    <span class="min-w-0">
                        <span class="block text-xs font-semibold text-white">Sebut Nama Siswa</span>
                        <span class="block text-[11px] text-slate-400">Pengucapan nama via Text-to-Speech</span>
                    </span>
                    <input type="checkbox" id="setting-voice-tts" class="w-5 h-5 accent-emerald-500 rounded cursor-pointer shrink-0">
                </label>
            </div>

            <div class="pt-3 border-t border-slate-800">
                <span class="text-[11px] text-slate-400 block mb-2">Uji Suara</span>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" data-soundtest="in" class="py-2 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 font-medium text-xs flex items-center justify-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-play text-[10px]"></i> Masuk
                    </button>
                    <button type="button" data-soundtest="out" class="py-2 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/20 font-medium text-xs flex items-center justify-center gap-1.5 transition-colors">
                        <i class="fa-solid fa-play text-[10px]"></i> Pulang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- html5-qrcode library -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <script>
    (function () {
        'use strict';

        const SCAN_URL = '{{ $scan_url }}';
        const HISTORY_URL = '{{ $history_url }}';
        const CSRF_TOKEN = '{{ csrf_token() }}';
        const DEFAULT_PHOTO = {!! json_script(\App\Services\AvatarService::inisial('Scan', 128, '#0f172a'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};
        const MODE_TAB = {
            masukOpen:  {{ !empty($mode_tab['masuk_open']) ? 'true' : 'false' }},
            pulangOpen: {{ !empty($mode_tab['pulang_open']) ? 'true' : 'false' }},
            defaultMode: {!! json_script(($mode_tab['default_mode'] ?? 'masuk') === 'keluar' ? 'keluar' : 'masuk') !!},
            masukMsg:   {!! json_script($mode_tab['masuk_msg'] ?? '', JSON_UNESCAPED_UNICODE) !!},
            pulangMsg:  {!! json_script($mode_tab['pulang_msg'] ?? '', JSON_UNESCAPED_UNICODE) !!},
            jamMasuk:   {!! json_script($mode_tab['jam_masuk'] ?? '', JSON_UNESCAPED_UNICODE) !!},
            bukaMasuk:  {!! json_script($mode_tab['buka_masuk'] ?? '', JSON_UNESCAPED_UNICODE) !!},
            tutupMasuk: {!! json_script($mode_tab['tutup_masuk'] ?? '', JSON_UNESCAPED_UNICODE) !!},
            jamPulang:  {!! json_script($mode_tab['jam_pulang'] ?? '', JSON_UNESCAPED_UNICODE) !!},
            bukaPulang: {!! json_script($mode_tab['buka_pulang'] ?? ($mode_tab['jam_pulang'] ?? ''), JSON_UNESCAPED_UNICODE) !!},
            qrAktif:    {{ !empty($mode_tab['qr_aktif']) ? 'true' : 'false' }},
            qrMulai:    {!! json_script($mode_tab['qr_mulai'] ?? '', JSON_UNESCAPED_UNICODE) !!},
            qrSelesai:  {!! json_script($mode_tab['qr_selesai'] ?? '', JSON_UNESCAPED_UNICODE) !!}
        };

        let activeMode       = 'auto';
        let soundEnabled     = localStorage.getItem('presensi_qr_sound') !== '0';
        let voiceEnabled     = localStorage.getItem('presensi_qr_voice') !== '0';
        let isProcessing     = false;
        let isScanning       = false;
        let isCameraPaused   = false;
        let isTorchOn        = false;
        let cameraDevices    = [];
        let currentCameraId  = null;
        let facingModeIndex  = 0;
        const facingModes    = ['environment', 'user'];

        let html5QrCode      = null;
        let cameraOverlayGuard = null;
        let countdownTimer   = null;
        let countdownInterval = null;
        let modeRefreshTimer = null;
        let feedTimer        = null;

        let audioCtx         = null;
        let indonesianVoice  = null;
        let lastScanMethod   = 'QR Code';

        let kioskLoc = { lat: null, lng: null, accuracy: null };
        const modeState = { masukOpen: MODE_TAB.masukOpen, pulangOpen: MODE_TAB.pulangOpen, masukMsg: MODE_TAB.masukMsg, pulangMsg: MODE_TAB.pulangMsg };

        // Jam acuan = jam server (WITA), bukan jam perangkat.
        witaInit('{{ date('Y-m-d H:i:s') }}');

        // ── 1. JAM & TANGGAL ─────────────────────────────
        function updateClock() {
            const now = witaNow();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

            $('#liveDate').text(`${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`);
            $('#liveTime').text(`${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // ── 2. AUDIO (CHIME & TTS) ───────────────────────
        function unlockAudio() {
            if (!audioCtx) {
                const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
                if (AudioCtxClass) audioCtx = new AudioCtxClass();
            }
            if (audioCtx && audioCtx.state === 'suspended') audioCtx.resume();
        }
        $(document).one('click keydown touchstart', unlockAudio);

        function playChime(type) {
            if (!soundEnabled) return;
            unlockAudio();
            if (!audioCtx) return;

            try {
                const ctx = audioCtx;
                const now = ctx.currentTime;

                if (type === 'success_in' || type === 'success_out') {
                    const notes = type === 'success_in' ? [659.25, 830.61, 987.77] : [987.77, 830.61, 659.25];
                    notes.forEach((freq, i) => {
                        const t = now + (i * 0.08);
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, t);
                        gain.gain.setValueAtTime(0.22, t);
                        gain.gain.exponentialRampToValueAtTime(0.001, t + (i === 2 ? 0.55 : 0.24));
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(t);
                        osc.stop(t + (i === 2 ? 0.55 : 0.24));
                    });
                } else if (type === 'warning') {
                    [440, 349.23].forEach((freq, i) => {
                        const t = now + (i * 0.12);
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'triangle';
                        osc.frequency.setValueAtTime(freq, t);
                        gain.gain.setValueAtTime(0.2, t);
                        gain.gain.exponentialRampToValueAtTime(0.01, t + 0.18);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(t);
                        osc.stop(t + 0.18);
                    });
                } else {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(220, now);
                    osc.frequency.exponentialRampToValueAtTime(130, now + 0.28);
                    gain.gain.setValueAtTime(0.22, now);
                    gain.gain.exponentialRampToValueAtTime(0.01, now + 0.28);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.28);
                }
            } catch (e) {}
        }

        function formatSpeechName(fullName) {
            if (!fullName) return '';
            const clean = String(fullName).replace(/['"`]/g, '').replace(/[^\w\s]/gi, ' ').trim();
            const words = clean.toLowerCase().split(/\s+/).filter(Boolean);
            if (!words.length) return '';
            const maxWords = (words[0].length <= 2 && words.length > 2) ? 3 : 2;
            return words.slice(0, Math.min(words.length, maxWords))
                .map(w => w.charAt(0).toUpperCase() + w.slice(1))
                .join(' ');
        }

        function getIndonesianVoice() {
            if (!('speechSynthesis' in window)) return null;
            if (indonesianVoice) return indonesianVoice;
            const voices = window.speechSynthesis.getVoices();
            if (!voices || !voices.length) return null;
            indonesianVoice = voices.find(v => v.lang === 'id-ID' || v.lang === 'id_ID')
                || voices.find(v => v.lang && v.lang.toLowerCase().startsWith('id'))
                || voices.find(v => v.name && /indonesia|indonesian/i.test(v.name))
                || null;
            return indonesianVoice;
        }

        if ('speechSynthesis' in window) {
            window.speechSynthesis.onvoiceschanged = function () {
                indonesianVoice = null;
                getIndonesianVoice();
            };
            try { window.speechSynthesis.getVoices(); } catch (e) {}
        }

        function speakText(rawText) {
            if (!voiceEnabled || !('speechSynthesis' in window) || !rawText) return;
            try {
                window.speechSynthesis.cancel();
                let text = String(rawText).replace(/['"`]/g, '').trim();
                text = text.replace(/\b[A-Z]{2,}\b/g, m => m.charAt(0) + m.slice(1).toLowerCase());

                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'id-ID';
                utterance.rate = 1.0;
                utterance.pitch = 1.0;
                const voice = getIndonesianVoice();
                if (voice) utterance.voice = voice;

                setTimeout(() => { try { window.speechSynthesis.speak(utterance); } catch (e) {} }, 60);
            } catch (e) {}
        }

        function syncAudioUi() {
            const icon = document.getElementById('header-audio-icon');
            if (icon) {
                icon.className = (soundEnabled || voiceEnabled)
                    ? 'fa-solid fa-volume-high text-emerald-400 text-xs sm:text-sm'
                    : 'fa-solid fa-volume-xmark text-slate-500 text-xs sm:text-sm';
            }
            $('#setting-sound-chime').prop('checked', soundEnabled);
            $('#setting-voice-tts').prop('checked', voiceEnabled);
        }

        function openAudioSettings() {
            syncAudioUi();
            $('#modal-audio').addClass('active');
        }

        function closeAudioSettings() {
            $('#modal-audio').removeClass('active');
            focusBarcodeInput();
        }

        // ── 3. MODE PRESENSI ─────────────────────────────
        function normHMS(s) { s = String(s || ''); return s.length === 5 ? s + ':00' : s; }

        function computeModeOpen(now) {
            let mOpen = true, mMsg = '';
            if (MODE_TAB.bukaMasuk && MODE_TAB.tutupMasuk) {
                const b = normHMS(MODE_TAB.bukaMasuk), t = normHMS(MODE_TAB.tutupMasuk);
                if (now < b) { mOpen = false; mMsg = 'Absen masuk belum dibuka. Jadwal: ' + MODE_TAB.jamMasuk + ' (dibuka ' + MODE_TAB.bukaMasuk + ').'; }
                else if (now > t) { mOpen = false; mMsg = 'Waktu absen masuk habis. Batas: ' + MODE_TAB.tutupMasuk + '.'; }
            }
            if (mOpen && MODE_TAB.qrAktif && MODE_TAB.qrMulai && MODE_TAB.qrSelesai) {
                const qs = normHMS(MODE_TAB.qrMulai), qe = normHMS(MODE_TAB.qrSelesai);
                if (now < qs || now > qe) { mOpen = false; mMsg = 'Di luar jam operasional scan (' + MODE_TAB.qrMulai + '–' + MODE_TAB.qrSelesai + ').'; }
            }

            let pOpen = true, pMsg = '';
            const bp = MODE_TAB.bukaPulang || MODE_TAB.jamPulang;
            if (bp && now < normHMS(bp)) {
                pOpen = false;
                pMsg = 'Belum waktunya pulang. Pulang dibuka pukul ' + bp + '.';
            }
            if (pOpen && MODE_TAB.qrAktif && MODE_TAB.qrMulai && MODE_TAB.qrSelesai) {
                const qs = normHMS(MODE_TAB.qrMulai), qe = normHMS(MODE_TAB.qrSelesai);
                if (now < qs || now > qe) {
                    pOpen = false;
                    pMsg = 'Di luar jam operasional scan (' + MODE_TAB.qrMulai + '–' + MODE_TAB.qrSelesai + '). Scan pulang ditutup pukul ' + MODE_TAB.qrSelesai + '.';
                }
            }

            return { mOpen, mMsg, pOpen, pMsg };
        }

        function effectiveMode() {
            if (activeMode !== 'auto') return activeMode;
            const preferred = MODE_TAB.defaultMode === 'keluar' ? 'pulang' : 'masuk';
            if (modeState[preferred + 'Open']) return preferred;
            const other = preferred === 'masuk' ? 'pulang' : 'masuk';
            if (modeState[other + 'Open']) return other;
            return preferred;
        }

        function updateModeIndicator() {
            const mode = effectiveMode();
            const isPulang = mode === 'pulang';
            const prefix = activeMode === 'auto' ? 'Mode otomatis' : 'Mode manual';

            $('#scan-hint-text').text(`${prefix} · ${isPulang ? 'Pulang' : 'Masuk'} — arahkan QR code ke kamera`);

            $('#scanner-laser').css({
                'background': isPulang
                    ? 'linear-gradient(90deg, transparent, #fbbf24, #f59e0b, #fbbf24, transparent)'
                    : 'linear-gradient(90deg, transparent, #34d399, #10b981, #34d399, transparent)',
                'box-shadow': isPulang
                    ? '0 0 16px #f59e0b, 0 0 24px rgba(245, 158, 11, 0.6)'
                    : '0 0 16px #10b981, 0 0 24px rgba(16, 185, 129, 0.6)'
            });
            $('.scanner-corner').css('border-color', isPulang ? '#f59e0b' : '#10b981');

            const notes = [];
            if (!modeState.masukOpen) notes.push(modeState.masukMsg || 'Absen masuk sedang ditutup.');
            if (!modeState.pulangOpen) notes.push(modeState.pulangMsg || 'Absen pulang sedang ditutup.');
            $('#mode-status-note').text(notes.join(' ')).toggleClass('hidden', notes.length === 0);
        }

        function setMode(mode) {
            activeMode = mode;
            $('#mode-group button').removeClass('active text-white').addClass('text-slate-400').attr('aria-pressed', 'false');
            $(`#mode-group button[data-mode="${mode}"]`).addClass('active text-white').removeClass('text-slate-400').attr('aria-pressed', 'true');
            updateModeIndicator();
            focusBarcodeInput();
        }

        function refreshModes() {
            const st = computeModeOpen(witaHMS());
            modeState.masukOpen = st.mOpen;
            modeState.pulangOpen = st.pOpen;
            modeState.masukMsg = st.mMsg;
            modeState.pulangMsg = st.pMsg;
            updateModeIndicator();
            scheduleNextModeRefresh();
        }

        function scheduleNextModeRefresh() {
            clearTimeout(modeRefreshTimer);
            const toSec = h => { const p = String(h || '').split(':'); return (+p[0] || 0) * 3600 + (+p[1] || 0) * 60 + (+p[2] || 0); };
            const nowSec = toSec(witaHMS());
            const marks = [MODE_TAB.bukaMasuk, MODE_TAB.tutupMasuk, MODE_TAB.bukaPulang || MODE_TAB.jamPulang];
            if (MODE_TAB.qrAktif) marks.push(MODE_TAB.qrMulai, MODE_TAB.qrSelesai);

            let best = null;
            marks.filter(Boolean).map(normHMS).forEach(h => {
                const delta = toSec(h) - nowSec;
                if (delta > 0) {
                    const ms = delta * 1000;
                    if (best === null || ms < best) best = ms;
                }
            });
            modeRefreshTimer = setTimeout(refreshModes, best !== null ? best + 400 : 60000);
        }

        // ── 4. GPS ──────────────────────────────────────
        function updateGpsPill() {
            if (kioskLoc.lat === null) {
                $('#gps-lock-icon').attr('class', 'fa-solid fa-location-dot text-[10px] text-slate-400');
                $('#gps-lock-text').text('GPS…');
            } else {
                $('#gps-lock-icon').attr('class', 'fa-solid fa-location-dot text-[10px] text-emerald-400');
                $('#gps-lock-text').text('GPS ±' + Math.round(kioskLoc.accuracy || 0) + 'm');
            }
        }

        function startGpsWatch() {
            if (!('geolocation' in navigator)) {
                $('#gps-lock-text').text('Tanpa GPS');
                return;
            }
            try {
                navigator.geolocation.watchPosition(
                    function (pos) {
                        kioskLoc = { lat: pos.coords.latitude, lng: pos.coords.longitude, accuracy: pos.coords.accuracy };
                        updateGpsPill();
                    },
                    function () { updateGpsPill(); },
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
                );
            } catch (e) { updateGpsPill(); }
            updateGpsPill();
        }

        // ── 5. KAMERA ───────────────────────────────────
        function setCameraOverlay(state) {
            clearTimeout(cameraOverlayGuard);

            $('#camera-loading-overlay')
                .toggleClass('hidden', state !== 'loading')
                .toggleClass('flex', state === 'loading');
            $('#camera-error-overlay')
                .toggleClass('hidden', state !== 'error')
                .toggleClass('flex', state === 'error');

            // Izin kamera bisa menggantung tanpa resolve/reject.
            if (state === 'loading') {
                cameraOverlayGuard = setTimeout(function () {
                    if (!isScanning) setCameraOverlay('error');
                }, 12000);
            }
        }

        function setReaderStatus(text) {
            $('#reader-status').text(text);
        }

        function initCamera() {
            setCameraOverlay('loading');
            setReaderStatus('Memuat…');

            if (html5QrCode) {
                html5QrCode.stop().catch(function () {}).then(startNewScanner);
            } else {
                startNewScanner();
            }
        }

        function startNewScanner() {
            html5QrCode = new Html5Qrcode('qr-reader-desktop');

            Html5Qrcode.getCameras().then(function (devices) {
                cameraDevices = devices || [];
                const $select = $('#select-video-device');
                $select.empty();

                if (cameraDevices.length > 0) {
                    cameraDevices.forEach(function (dev, idx) {
                        const label = dev.label || ('Kamera ' + (idx + 1));
                        $select.append(`<option value="${dev.id}">${label}</option>`);
                    });
                    currentCameraId = cameraDevices[0].id;
                    startCameraWithConfig({ deviceId: { exact: currentCameraId } });
                } else {
                    startCameraWithConfig({ facingMode: facingModes[facingModeIndex] });
                }
            }).catch(function () {
                startCameraWithConfig({ facingMode: facingModes[facingModeIndex] });
            });
        }

        function startCameraWithConfig(cameraConfig) {
            const config = {
                fps: 20,
                qrbox: function (w, h) {
                    const minEdge = Math.min(w, h);
                    const edge = Math.floor(minEdge * 0.75);
                    return { width: edge, height: edge };
                },
                aspectRatio: 1.777778
            };

            html5QrCode.start(cameraConfig, config, onScanSuccess, function () { })
                .then(function () {
                    setCameraOverlay('none');
                    setReaderStatus('Kamera aktif');
                    isScanning = true;
                    isCameraPaused = false;
                    $('#label-pause-cam').text('Jeda');
                    $('#icon-pause-cam').attr('class', 'fa-solid fa-pause text-xs');
                    checkTorchSupport();
                    focusBarcodeInput();
                })
                .catch(function (err) {
                    console.warn('Camera start error:', err);
                    setCameraOverlay('error');
                    setReaderStatus('Kamera offline');
                    isScanning = false;
                });
        }

        function switchCameraDevice(deviceId) {
            if (!deviceId || deviceId === currentCameraId) return;
            currentCameraId = deviceId;
            setCameraOverlay('loading');
            if (!html5QrCode) { initCamera(); return; }
            html5QrCode.stop().then(function () {
                startCameraWithConfig({ deviceId: { exact: deviceId } });
            }).catch(function () { initCamera(); });
        }

        function cycleFacingMode() {
            facingModeIndex = (facingModeIndex + 1) % facingModes.length;
            setCameraOverlay('loading');
            if (!html5QrCode) { initCamera(); return; }
            html5QrCode.stop().then(function () {
                startCameraWithConfig({ facingMode: facingModes[facingModeIndex] });
            }).catch(function () { initCamera(); });
        }

        function toggleCameraPause() {
            if (!html5QrCode || !isScanning) return;
            if (!isCameraPaused) {
                html5QrCode.pause();
                isCameraPaused = true;
                $('#label-pause-cam').text('Lanjut');
                $('#icon-pause-cam').attr('class', 'fa-solid fa-play text-xs');
                setReaderStatus('Dijeda');
            } else {
                html5QrCode.resume();
                isCameraPaused = false;
                $('#label-pause-cam').text('Jeda');
                $('#icon-pause-cam').attr('class', 'fa-solid fa-pause text-xs');
                setReaderStatus('Kamera aktif');
            }
        }

        function checkTorchSupport() {
            try {
                const video = document.querySelector('#qr-reader-desktop video');
                if (video && video.srcObject) {
                    const track = video.srcObject.getVideoTracks()[0];
                    const caps = track.getCapabilities ? track.getCapabilities() : {};
                    $('#btn-desktop-torch').toggleClass('hidden', !caps.torch);
                }
            } catch (e) {}
        }

        function toggleTorch() {
            try {
                const video = document.querySelector('#qr-reader-desktop video');
                if (!video || !video.srcObject) return;
                const track = video.srcObject.getVideoTracks()[0];
                isTorchOn = !isTorchOn;
                track.applyConstraints({ advanced: [{ torch: isTorchOn }] })
                    .then(function () { $('#btn-desktop-torch').toggleClass('active', isTorchOn); })
                    .catch(function () {});
            } catch (e) {}
        }

        function onScanSuccess(decodedText) {
            if (isProcessing || !decodedText) return;
            lastScanMethod = 'QR Code';
            processScan(decodedText);
        }

        // ── 6. PROSES PRESENSI ──────────────────────────
        function processScan(value) {
            const raw = String(value == null ? '' : value).trim();
            if (!raw || isProcessing) return;

            refreshModes();
            const mode = effectiveMode();

            if (!modeState[mode + 'Open']) {
                renderError(modeState[mode + 'Msg'] || 'Di luar jadwal presensi untuk mode ini.');
                return;
            }

            isProcessing = true;
            unlockAudio();
            setReaderStatus('Memproses…');
            clearTimeout(countdownTimer);
            clearInterval(countdownInterval);

            const filterVal = $('#select-kelas-filter').val();
            const idKelas = (filterVal === 'guru') ? 0 : (filterVal || 0);
            const kioskToken = (function () { try { return localStorage.getItem('qr_kiosk_token') || ''; } catch (e) { return ''; } })();

            $.ajax({
                url: SCAN_URL,
                type: 'POST',
                dataType: 'json',
                headers: kioskToken ? { 'X-Kiosk-Token': kioskToken } : {},
                data: {
                    _token: CSRF_TOKEN,
                    qr_value: raw,
                    mode: mode === 'pulang' ? 'keluar' : 'masuk',
                    id_kelas: idKelas,
                    lat: kioskLoc.lat,
                    lng: kioskLoc.lng,
                    accuracy: kioskLoc.accuracy,
                    k: kioskToken || undefined
                }
            }).done(function (res) {
                renderResult(res, mode);
                loadFeed();
            }).fail(function (xhr) {
                let msg = 'Gagal memproses presensi.';
                try {
                    const parsed = JSON.parse(xhr.responseText);
                    if (parsed && parsed.message) msg = parsed.message;
                } catch (e) {}
                renderError(msg);
            }).always(function () {
                setReaderStatus(isScanning ? 'Kamera aktif' : 'Kamera offline');
                setTimeout(function () {
                    isProcessing = false;
                    focusBarcodeInput();
                }, 1200);
            });
        }

        // ── 7. TAMPILAN HASIL ───────────────────────────
        function showView(name) {
            $('#cameraView').toggleClass('hidden', name !== 'camera').toggleClass('flex', name === 'camera');
            $('#resultView').toggleClass('hidden', name !== 'result').toggleClass('flex', name === 'result');
            $('#errorView').toggleClass('hidden', name !== 'error').toggleClass('flex', name === 'error');
        }

        function setContainerState(tone) {
            const $c = $('#scannerContainer');
            $c.removeClass('state-success state-warning state-info state-error');
            if (tone) $c.addClass('state-' + tone);
        }

        function renderResult(res, mode) {
            const ok = res && res.status === true;
            const person = (res && (res.siswa || res.guru)) || null;
            const isGuru = !!(res && (res.role === 'guru' || res.guru));
            const type = (res && res.type) || (ok ? (mode === 'pulang' ? 'check_out' : 'check_in') : 'error');

            if (!ok && !person) {
                renderError((res && res.message) || 'Presensi tidak berhasil diproses.');
                return;
            }

            const configs = {
                check_in:  { tone: 'success', icon: 'fa-circle-check',    label: 'ABSEN MASUK',          note: 'Hadir Tepat Waktu',      sound: 'success_in',  tts: 'Hadir.' },
                check_out: { tone: 'info',    icon: 'fa-house-circle-check', label: 'ABSEN PULANG',       note: 'Absen pulang selesai',   sound: 'success_out', tts: 'Pulang.' },
                already_in: { tone: 'warning', icon: 'fa-clock',           label: 'SUDAH ABSEN MASUK',    note: 'Sudah tercatat masuk',   sound: 'warning', tts: 'Sudah absen masuk.' },
                already_out: { tone: 'warning', icon: 'fa-clock',          label: 'SUDAH ABSEN PULANG',   note: 'Sudah tercatat pulang',  sound: 'warning', tts: 'Sudah absen pulang.' },
                not_in:    { tone: 'warning', icon: 'fa-triangle-exclamation', label: 'BELUM ABSEN MASUK', note: 'Silakan absen masuk dulu', sound: 'warning', tts: 'Belum absen masuk.' },
                izin:      { tone: 'warning', icon: 'fa-file-medical',     label: 'IZIN / SAKIT',         note: 'Berstatus izin',         sound: 'warning', tts: 'Berstatus izin.' },
                jurnal_kurang: { tone: 'error', icon: 'fa-triangle-exclamation', label: 'JURNAL BELUM LENGKAP', note: 'Lengkapi jurnal mengajar', sound: 'warning', tts: 'Jurnal mengajar belum lengkap.' },
                error:     { tone: 'error',   icon: 'fa-triangle-exclamation', label: 'GAGAL',            note: 'Presensi gagal',         sound: 'error',   tts: 'Presensi tidak berhasil.' }
            };
            const cfg = Object.assign({}, configs[type] || configs.error, { label: (configs[type] || configs.error).label + (isGuru ? ' (GURU)' : '') });

            if (type === 'check_in' && res.telat) {
                cfg.tone = 'warning';
                cfg.icon = 'fa-clock';
                cfg.label = 'ABSEN MASUK TERLAMBAT' + (isGuru ? ' (GURU)' : '');
                cfg.note = 'Terlambat (' + (res.telat_menit || 0) + ' menit)';
                cfg.sound = 'warning';
            }

            const timeStr = witaHMS();
            const $banner = $('#statusBanner');

            $('#statusBanner').attr('class', 'w-full py-3.5 px-5 rounded-2xl mb-5 flex items-center justify-between gap-2 font-bold text-sm sm:text-base transition-colors shadow-lg ' + ({
                success: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
                info: 'bg-blue-500/20 text-blue-300 border border-blue-500/30',
                warning: 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
                error: 'bg-rose-500/20 text-rose-300 border border-rose-500/30'
            }[cfg.tone] || 'bg-slate-800 text-slate-300 border border-slate-700'));

            const iconColor = { success: 'text-emerald-400', info: 'text-blue-400', warning: 'text-amber-400', error: 'text-rose-400' }[cfg.tone];
            $('#statusIcon').attr('class', 'fa-solid ' + cfg.icon + ' text-xl ' + iconColor);
            $('#statusText').text(res.message ? String(res.message).toUpperCase() : cfg.label);
            $('#scanTimestamp').text(timeStr + ' WITA');

            const photoBadgeBg = { success: 'bg-emerald-500', info: 'bg-blue-500', warning: 'bg-amber-500', error: 'bg-rose-500' }[cfg.tone];
            $('#photoBadge').attr('class', 'absolute -bottom-2 -right-2 w-8 h-8 sm:w-9 sm:h-9 rounded-xl ' + photoBadgeBg + ' text-white flex items-center justify-center shadow-lg border-2 border-slate-900');
            $('#photoBadgeIcon').attr('class', 'fa-solid ' + cfg.icon + ' text-sm');
            $('#personNote').attr('class', 'font-semibold ' + iconColor + ' text-xs sm:text-sm m-0 truncate').text(cfg.note);

            $('#personPhoto').attr('src', (person && person.foto) ? person.foto : DEFAULT_PHOTO)
                .off('error').on('error', function () { this.src = DEFAULT_PHOTO; });

            if (person) {
                $('#roleBadge')
                    .text(isGuru ? 'GURU & GTK' : 'SISWA')
                    .attr('class', 'px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider ' + (isGuru
                        ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30'
                        : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'));
                $('#idNumberLabel').text(isGuru ? 'NIP / NUPTK' : 'NIS / NISN');
                $('#departmentLabel').text(isGuru ? 'Jabatan / Mapel' : 'Kelas');

                $('#personName').text(person.nama || '-');
                $('#personIdNumber').text(isGuru ? (person.nip || person.nuptk || person.kode_guru || '-') : (person.nis || person.nisn || '-'));
                $('#personDepartment').text(isGuru ? (person.jabatan || 'Guru / GTK') : (person.nama_kelas || person.kelas || '-'));
                $('#personMethod').text(lastScanMethod);
            } else {
                $('#personName').text('Perhatian');
                $('#personIdNumber').text('-');
                $('#personDepartment').text('-');
                $('#personMethod').text(lastScanMethod);
            }

            setContainerState(cfg.tone);
            showView('result');
            playChime(cfg.sound);

            const spoken = person
                ? formatSpeechName(person.nama) + '. ' + cfg.tts
                : (res.message || cfg.tts);
            speakText(spoken);

            if (res.stats) updateStats(res.stats);

            startCountdown(4000);
        }

        function renderError(message) {
            clearTimeout(countdownTimer);
            clearInterval(countdownInterval);

            $('#errorMessage').text(message || 'Terjadi kesalahan.');
            setContainerState('error');
            showView('error');
            playChime('error');
            startCountdown(4000);
        }

        function startCountdown(durationMs) {
            const $bar = $('#resetProgressBar');
            const $text = $('#countdownText');
            const start = Date.now();

            $bar.css('width', '100%');
            $text.text(Math.ceil(durationMs / 1000) + ' dtk');

            clearInterval(countdownInterval);
            countdownInterval = setInterval(function () {
                const remaining = Math.max(0, durationMs - (Date.now() - start));
                $bar.css('width', ((remaining / durationMs) * 100) + '%');
                $text.text(Math.ceil(remaining / 1000) + ' dtk');
                if (remaining <= 0) {
                    clearInterval(countdownInterval);
                    backToCamera();
                }
            }, 50);
        }

        function backToCamera() {
            clearInterval(countdownInterval);
            clearTimeout(countdownTimer);

            $('#resetProgressBar').css('width', '100%');
            setContainerState(null);
            showView('camera');
            updateModeIndicator();
            focusBarcodeInput();
        }

        // ── 8. STATISTIK & FEED ─────────────────────────
        function updateStats(stats) {
            if (!stats) return;
            const hadir = parseInt(stats.hadir || 0, 10) || 0;
            const terlambat = parseInt(stats.terlambat || 0, 10) || 0;
            const pulang = parseInt(stats.keluar || 0, 10) || 0;

            $('#statTotalHadir').text(hadir);
            $('#statTerlambat').text(terlambat);
            $('#statTepatWaktu').text(Math.max(0, hadir - terlambat));
            $('#statSudahPulang').text(pulang);
        }

        function getInitials(name) {
            if (!name) return '??';
            const parts = String(name).trim().split(/\s+/);
            if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }

        function renderFeed(rows) {
            const $container = $('#attendanceLogList');

            if (!rows || rows.length === 0) {
                $container.html(`
                    <div id="empty-feed-placeholder" class="py-12 text-center text-xs text-slate-500">
                        <i class="fa-solid fa-inbox text-2xl mb-1.5 block text-slate-600"></i>
                        Belum ada aktivitas presensi hari ini
                    </div>
                `);
                return;
            }

            $container.html(rows.map(function (log, index) {
                let badge = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">Masuk</span>`;
                if (log.status === 'PULANG') {
                    badge = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-blue-500/15 text-blue-400 border border-blue-500/30">Pulang</span>`;
                } else if (log.isLate || log.status === 'TERLAMBAT') {
                    badge = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/15 text-amber-400 border border-amber-500/30">Terlambat</span>`;
                }

                const highlight = index === 0 ? 'bg-slate-900/90 border-emerald-500/40 shadow-md' : 'bg-slate-950/60 border-slate-800/80';
                const gradient = log.role === 'GURU' ? 'from-purple-500 to-indigo-600' : 'from-rose-500 to-pink-600';

                return `
                    <div class="flex items-center justify-between p-2.5 sm:p-3 rounded-2xl border ${highlight} transition-all hover:border-slate-700">
                        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-gradient-to-tr ${gradient} flex items-center justify-center font-bold text-white text-xs sm:text-sm shadow-md shrink-0">${getInitials(log.name)}</div>
                            <div class="min-w-0">
                                <h4 class="text-xs sm:text-sm font-bold text-white truncate m-0">${log.name}</h4>
                                <p class="text-[11px] text-slate-400 truncate m-0">${log.department}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0 ml-2 flex flex-col items-end gap-1">
                            ${badge}
                            <span class="font-mono-num text-[11px] text-slate-400 font-medium">${log.time}</span>
                        </div>
                    </div>
                `;
            }).join(''));
        }

        function loadFeed() {
            const filterVal = $('#select-kelas-filter').val();
            const idKelas = (filterVal === 'guru') ? 0 : (filterVal || 0);
            const filterRole = (filterVal === 'guru') ? 'guru' : '';
            const kioskToken = (function () { try { return localStorage.getItem('qr_kiosk_token') || ''; } catch (e) { return ''; } })();

            $.ajax({
                url: HISTORY_URL,
                type: 'GET',
                dataType: 'json',
                headers: kioskToken ? { 'X-Kiosk-Token': kioskToken } : {},
                data: {
                    id_kelas: idKelas,
                    filter_role: filterRole,
                    limit: 12,
                    k: kioskToken || undefined
                }
            }).done(function (res) {
                if (res && res.status && Array.isArray(res.data)) {
                    renderFeed(res.data.map(function (r) {
                        const isGuru = (r.role === 'guru') || (r.nama_kelas === 'GURU / GTK') || !!r.nip;
                        const isLate = !!(r.terlambat && r.terlambat > 0) || r.status === 'LATE';
                        const timeRaw = r.jam_keluar || r.jam_masuk || '';
                        return {
                            role: isGuru ? 'GURU' : 'SISWA',
                            name: r.nama_siswa || r.nama_guru || r.nama || '-',
                            department: isGuru ? (r.jabatan || 'Guru / GTK') : (r.nama_kelas || 'Kelas'),
                            time: timeRaw ? String(timeRaw).substr(0, 5) : '--:--',
                            status: r.jam_keluar ? 'PULANG' : (isLate ? 'TERLAMBAT' : 'MASUK'),
                            isLate: isLate
                        };
                    }));
                }
                if (res && res.stats) updateStats(res.stats);
            }).fail(function (xhr) {
                // Token kiosk kosong/invalid: hentikan polling agar log server
                // tidak dibanjiri peringatan tiap 25 detik, lalu beri tahu operator.
                if (xhr.status === 403 || xhr.status === 503) {
                    if (feedTimer) { clearInterval(feedTimer); feedTimer = null; }
                    $('#attendanceLogList').html(`
                        <div class="py-12 px-4 text-center text-xs text-slate-400">
                            <i class="fa-solid fa-lock text-2xl mb-2 block text-rose-400"></i>
                            <p class="font-bold text-white m-0 mb-1">Feed terkunci</p>
                            <p class="m-0">Token Kiosk belum diisi admin. Muat ulang halaman setelah token diatur di Pengaturan &rarr; Presensi.</p>
                        </div>
                    `);
                }
            });
        }

        // ── 9. UTILITAS INPUT & FULLSCREEN ──────────────
        function focusBarcodeInput() {
            setTimeout(function () {
                if ($('#modal-audio').hasClass('active')) return;
                $('#input-barcode-scanner').trigger('focus');
            }, 80);
        }

        function submitBarcodeInput() {
            const val = ($('#input-barcode-scanner').val() || '').trim();
            if (!val) return;
            $('#input-barcode-scanner').val('');
            lastScanMethod = 'Input Manual';
            processScan(val);
        }

        function toggleFullscreen() {
            const icon = document.getElementById('fullscreenIcon');
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(function () {});
                if (icon) icon.className = 'fa-solid fa-compress text-xs sm:text-sm';
            } else {
                if (document.exitFullscreen) document.exitFullscreen().catch(function () {});
                if (icon) icon.className = 'fa-solid fa-expand text-xs sm:text-sm';
            }
        }

        function testSound(kind) {
            unlockAudio();
            if (kind === 'in') {
                playChime('success_in');
                setTimeout(function () { speakText('Tes suara. Absen masuk.'); }, 280);
            } else {
                playChime('success_out');
                setTimeout(function () { speakText('Tes suara. Absen pulang.'); }, 280);
            }
        }

        // ── 10. EVENT BINDING ───────────────────────────
        $('#mode-group button').on('click', function () { setMode($(this).data('mode')); });

        $('#btn-open-audio').on('click', openAudioSettings);
        $('#btn-close-audio').on('click', closeAudioSettings);
        $('#modal-audio').on('click', function (e) { if (e.target === this) closeAudioSettings(); });

        $('#setting-sound-chime').on('change', function () {
            soundEnabled = !!this.checked;
            localStorage.setItem('presensi_qr_sound', soundEnabled ? '1' : '0');
            syncAudioUi();
            if (soundEnabled) playChime('success_in');
        });

        $('#setting-voice-tts').on('change', function () {
            voiceEnabled = !!this.checked;
            localStorage.setItem('presensi_qr_voice', voiceEnabled ? '1' : '0');
            syncAudioUi();
            if (voiceEnabled) speakText('Suara aktif');
        });

        $('[data-soundtest]').on('click', function () { testSound($(this).data('soundtest')); });

        $('#btn-fullscreen').on('click', toggleFullscreen);
        $('#btn-flip-cam').on('click', cycleFacingMode);
        $('#btn-desktop-torch').on('click', toggleTorch);
        $('#btn-desktop-pause').on('click', toggleCameraPause);
        $('#btn-retry-cam').on('click', initCamera);
        $('#btn-barcode-proses').on('click', submitBarcodeInput);
        $('#select-video-device').on('change', function () { switchCameraDevice(this.value); });
        $('#select-kelas-filter').on('change', function () { loadFeed(); focusBarcodeInput(); });

        $('#input-barcode-scanner').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitBarcodeInput();
            }
        });

        $(window).on('keydown', function (e) {
            if ($('#modal-audio').hasClass('active')) {
                if (e.key === 'Escape') closeAudioSettings();
                return;
            }

            const tag = (e.target && e.target.tagName) || '';
            if (tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA') return;

            if (e.key === 'f' || e.key === 'F') { e.preventDefault(); toggleFullscreen(); return; }
            if (e.key === 's' || e.key === 'S') { e.preventDefault(); openAudioSettings(); return; }
            if (e.key === 'm' || e.key === 'M') {
                e.preventDefault();
                const modes = ['auto', 'masuk', 'pulang'];
                setMode(modes[(modes.indexOf(activeMode) + 1) % modes.length]);
            }
        });

        $(document).on('click', function (e) {
            if ($(e.target).closest('button, a, input, select, label, .modal-backdrop').length) return;
            focusBarcodeInput();
        });

        // ── 11. INISIALISASI ────────────────────────────
        $(document).ready(function () {
            syncAudioUi();
            refreshModes();
            startGpsWatch();
            initCamera();
            loadFeed();
            focusBarcodeInput();

            feedTimer = setInterval(loadFeed, 25000);
            setInterval(refreshModes, 15000);
            setInterval(updateModeIndicator, 60000);
        });
    })();
    </script>
</body>
</html>
