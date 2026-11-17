<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Kiosk Absensi RFID Siswa &amp; Guru | {{ $setting->nama_sekolah ?? 'Presensi' }}</title>

    <?php
    $setting = $setting ?? new \stdClass();
    $setting->logo_kiri = $setting->logo_kiri ?? null;
    $logo_app = ($setting->logo_kiri == null) ? asset('assets/img/favicon.png') : url('' . $setting->logo_kiri);
    $tpText = ($tp_active->tahun ?? '2026/2027') . ' ' . ($smt_active->nama_smt ?? 'Ganjil');
    ?>
    <link rel="shortcut icon" href="{{ $logo_app }}" type="image/x-icon">

    <!-- Fonts: Inter, Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons & SweetAlert2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/app/css/tailwind.css') }}?v={{ @filemtime(public_path() . '/' . 'assets/app/css/tailwind.css') }}">
    {{-- Utility yang dipakai halaman kiosk (arbitrary value, varian sm/md/lg,
         dan warna transparan) tidak ada di build tailwind.css lama. --}}
    <link rel="stylesheet" href="{{ asset('assets/app/css/kiosk-ui.css') }}?v={{ @filemtime(public_path() . '/' . 'assets/app/css/kiosk-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.css') }}">

    <script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ url('assets/js/wita-clock.js?v=' . (@filemtime(public_path() . '/' . 'assets/js/wita-clock.js') ?: time())) }}"></script>

    <script>
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
            user-select: none;
            -webkit-user-select: none;
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

        /* Radar Waves Animation */
        @keyframes tapPulse {
            0% { transform: scale(0.65); opacity: 0.9; }
            100% { transform: scale(1.3); opacity: 0; }
        }

        .tap-pulse-ring {
            position: absolute;
            border-radius: 9999px;
            animation: tapPulse 3.2s cubic-bezier(0.2, 0.8, 0.2, 1) infinite;
            pointer-events: none;
        }
        .tap-pulse-ring:nth-child(1) { width: 170px; height: 170px; animation-delay: 0s; border: 1.5px solid rgba(20, 184, 166, 0.5); }
        .tap-pulse-ring:nth-child(2) { width: 260px; height: 260px; animation-delay: 1.1s; border: 1.5px solid rgba(16, 185, 129, 0.35); }
        .tap-pulse-ring:nth-child(3) { width: 350px; height: 350px; animation-delay: 2.2s; border: 1px solid rgba(6, 182, 212, 0.2); }

        /* Warna bingkai hasil scan. Ditaruh di sini (bukan utility border-*)
           karena .glass-panel mendeklarasikan border/box-shadow sesudah
           stylesheet Tailwind, sehingga utility kalah cascade. */
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
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.4);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(51, 65, 85, 0.8);
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(71, 85, 105, 1);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between selection:bg-emerald-500 selection:text-slate-950 relative">

    <!-- Ambient Glow & Cyber Grid -->
    <div class="ambient-glow"></div>
    <div class="cyber-grid"></div>

    <!-- Hidden Input untuk menangkap ketikan / scan dari USB RFID Reader Fisik -->
    <input type="text" id="rfidHiddenInput" autocomplete="off" autofocus
           style="position: fixed; opacity: 0; pointer-events: none; left: -9999px; top: -9999px;"
           aria-hidden="true" tabindex="-1">

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- HEADER / TOP BAR (CYBER MODERN KIOSK)                                    -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <header class="bg-slate-950/80 backdrop-blur-xl border-b border-slate-800/80 px-4 sm:px-6 lg:px-8 py-3 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center gap-x-2.5 gap-y-2.5 sm:gap-x-4">

            <!-- Identitas Sekolah (Kiri) -->
            <div class="order-1 flex items-center gap-2.5 sm:gap-3 min-w-0 flex-1">
                <img src="{{ $logo_app }}" alt="Logo {{ $setting->nama_sekolah ?? 'sekolah' }}" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl object-contain bg-slate-900/90 p-1 border border-slate-700/80 shadow-md shrink-0">
                <div class="min-w-0">
                    <h1 class="text-sm sm:text-base font-extrabold tracking-tight text-white truncate">
                        {{ $setting->nama_sekolah ?? 'SMA NEGERI 1 AMFOANG TIMUR' }}
                    </h1>
                    <p id="header-status-badge" class="text-[11px] sm:text-xs text-slate-400 m-0 truncate">
                        Terminal Presensi Siswa &amp; Guru
                    </p>
                </div>
            </div>

            <!-- Tombol Aksi (kanan atas di mobile, paling kanan di desktop) -->
            <div class="order-2 lg:order-3 flex items-center gap-1.5 sm:gap-2 shrink-0">
                <button type="button" id="btn-toggle-sound" onclick="toggleSound()" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 flex items-center justify-center transition-all hover:scale-105 active:scale-95" title="Suara Bel" aria-label="Nyalakan atau matikan suara bel">
                    <i id="icon-sound" class="fa-solid fa-volume-high text-emerald-400 text-xs sm:text-sm"></i>
                </button>

                <button type="button" onclick="toggleFullscreen()" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 flex items-center justify-center transition-all hover:scale-105 active:scale-95" title="Layar Penuh" aria-label="Alihkan layar penuh">
                    <i id="fullscreenIcon" class="fa-solid fa-expand text-xs sm:text-sm"></i>
                </button>

                <a href="{{ url('scanner') }}" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-cyan-400 border border-slate-800 flex items-center justify-center transition-all hover:scale-105 active:scale-95" title="Buka Scanner Kamera QR" aria-label="Buka scanner kamera QR">
                    <i class="fa-solid fa-qrcode text-xs sm:text-sm"></i>
                </a>

                @if(!empty($isLoggedIn))
                <a href="{{ url('dashboard') }}" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-rose-500/10 text-rose-400 hover:bg-rose-500 hover:text-white border border-rose-500/20 flex items-center justify-center transition-all hover:scale-105 active:scale-95" title="Keluar" aria-label="Tutup kiosk dan kembali ke dashboard">
                    <i class="fa-solid fa-xmark text-xs sm:text-sm"></i>
                </a>
                @endif
            </div>

            <!-- Jam + Mode: baris kedua di mobile, klaster kanan di desktop -->
            <div class="order-3 lg:order-2 flex w-full lg:w-auto items-center justify-center lg:justify-end gap-2 sm:gap-4">

                <!-- Jam Real-Time -->
                <div class="flex items-center justify-center gap-2 bg-slate-900/90 px-2.5 sm:px-5 py-1 sm:py-1.5 rounded-xl sm:rounded-2xl border border-slate-700/70 shadow-[0_0_20px_rgba(16,185,129,0.12)] shrink-0">
                    <div id="liveTime" class="text-sm sm:text-lg font-mono-num font-extrabold text-emerald-400 tracking-widest drop-shadow-[0_0_8px_rgba(52,211,153,0.5)]">
                        --:--:--
                    </div>
                    <div id="liveDate" class="hidden md:block text-[10px] font-medium text-slate-400 tracking-wide truncate max-w-[140px]">
                        --
                    </div>
                </div>

                <!-- Mode Selector (Auto / Masuk / Pulang) -->
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
    <!-- MAIN KIOSK CONTENT (7 COLS KIRI / 5 COLS KANAN DI DESKTOP)              -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-5 sm:py-6 grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6 items-stretch relative z-10">
        
        <!-- ── KOLOM KIRI: AREA SCANNER RFID & HASIL SCAN (7 KOLOM) ── -->
        <section class="lg:col-span-7 flex flex-col gap-4 sm:gap-5">
            
            <!-- Kotak Utama Status & Scanner -->
            <div id="scannerContainer" class="flex-1 glass-panel rounded-3xl p-6 sm:p-8 md:p-9 flex flex-col items-center justify-center relative overflow-hidden transition-all duration-300 min-h-[400px] sm:min-h-[480px]">
                
                <!-- Efek Cahaya Latar Glow -->
                <div id="glowEffect" class="absolute inset-0 bg-gradient-to-b from-teal-500/10 via-transparent to-transparent pointer-events-none transition-colors duration-500"></div>

                <!-- Status Atas Bar (Jadwal & Status Reader) -->
                <div class="absolute top-5 left-6 right-6 flex items-center justify-between text-xs text-slate-400 z-10 pointer-events-none">
                    <div>
                        @if(!empty($qr_window))
                        <span class="px-3 py-1 rounded-full bg-slate-900/70 border border-slate-800 text-slate-400 font-mono-num text-[11px] inline-flex items-center gap-1.5 shadow-sm">
                            <i class="fa-regular fa-clock text-[10px] text-teal-400"></i> {{ $qr_window }}
                        </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 font-mono-num text-[11px] text-slate-400">
                        <span id="nfc-chip" class="hidden px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-400 text-[10px] font-bold border border-emerald-500/30">
                            <i class="fa-solid fa-wifi mr-1"></i> NFC
                        </span>
                        <span id="reader-status-pill" class="px-3 py-1 rounded-full bg-slate-900/80 text-slate-300 border border-slate-700/80 flex items-center gap-1.5 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span id="reader-status">Standby</span>
                        </span>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────── -->
                <!-- TAMPILAN 1: STANDBY / MENUNGGU TAP KARTU       -->
                <!-- ────────────────────────────────────────────── -->
                <div id="standbyView" class="flex flex-col items-center text-center z-10 max-w-md mx-auto my-auto py-6">
                    
                    <!-- Lingkaran Radar & Glowing Tap Disc -->
                    <div class="relative w-44 h-44 sm:w-52 sm:h-52 mb-6 flex items-center justify-center cursor-pointer select-none" onclick="focusWedgeInput()">
                        <div class="tap-pulse-ring"></div>
                        <div class="tap-pulse-ring"></div>
                        <div class="tap-pulse-ring"></div>

                        <!-- Tap Card Button / Emblem -->
                        <div class="w-36 sm:w-40 py-4 sm:py-5 rounded-3xl bg-gradient-to-tr from-emerald-500 via-teal-400 to-cyan-400 text-slate-950 shadow-[0_0_45px_rgba(20,184,166,0.6)] flex flex-col items-center justify-center relative overflow-hidden transition-all duration-300 transform active:scale-95 hover:scale-105 hover:shadow-[0_0_65px_rgba(20,184,166,0.85)] z-10 border border-teal-200/50">
                            <div class="text-2xl font-black mb-0.5 flex items-center justify-center text-slate-950">
                                <span>((&nbsp;</span><i class="fa-solid fa-wifi rotate-90 text-xl inline-block -mx-1"></i><span>&nbsp;))</span>
                            </div>
                            <span class="text-[11px] font-black tracking-widest uppercase text-slate-950">TAP CARD</span>
                        </div>
                    </div>

                    <div id="standbyModePill" class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-900/80 border border-slate-700/80 text-[11px] font-semibold text-slate-300 mb-3 shadow-sm">
                        <span id="standbyModeDot" class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span id="standbyModeText">Mode otomatis &middot; Masuk</span>
                    </div>

                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mb-5">
                        Tempelkan Kartu RFID / NFC
                    </h2>

                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <button type="button" id="btn-enable-nfc" onclick="startNfcScan()" class="hidden items-center gap-2 px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 border border-emerald-300/60 text-xs font-bold transition-all shadow-md hover:scale-105 active:scale-95">
                            <i class="fa-solid fa-wifi"></i> Aktifkan Sensor NFC
                        </button>
                        <button type="button" onclick="promptManualUid()" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700/80 text-xs font-semibold transition-all shadow-md hover:scale-105 active:scale-95">
                            <i class="fa-solid fa-keyboard text-slate-400"></i> Input Manual
                        </button>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────── -->
                <!-- TAMPILAN 2: HASIL SCAN SISWA / GURU            -->
                <!-- ────────────────────────────────────────────── -->
                <div id="resultView" class="hidden w-full z-10 flex-col justify-between my-auto py-2">
                    
                    <!-- Banner Status Kehadiran -->
                    <div id="statusBanner" class="w-full py-3.5 px-5 rounded-2xl mb-5 flex items-center justify-between font-bold text-sm sm:text-base transition-colors shadow-lg">
                        <div class="flex items-center gap-2.5 sm:gap-3">
                            <i id="statusIcon" class="fa-solid fa-circle-check text-xl"></i>
                            <span id="statusText">ABSEN MASUK BERHASIL</span>
                        </div>
                        <span id="scanTimestamp" class="font-mono-num text-xs sm:text-sm px-2.5 py-1 rounded-lg bg-black/20">07:05:12 WITA</span>
                    </div>

                    <!-- Detail Kartu Identitas -->
                    <div class="flex flex-col sm:flex-row items-center gap-5 sm:gap-6 bg-slate-950/70 p-5 sm:p-6 rounded-2xl border border-slate-800">
                        <!-- Foto Profil -->
                        <div class="relative shrink-0">
                            <img id="personPhoto" src="{{ asset('assets/img/siswa.png') }}" alt="Foto"
                                 class="w-24 h-24 sm:w-32 sm:h-32 rounded-2xl object-cover border-2 border-slate-700 shadow-xl bg-slate-800">
                            <div id="photoBadge" class="absolute -bottom-2 -right-2 w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center shadow-lg border-2 border-slate-900">
                                <i id="photoBadgeIcon" class="fa-solid fa-check text-sm"></i>
                            </div>
                        </div>

                        <!-- Informasi Biodata -->
                        <div class="flex-1 text-center sm:text-left space-y-2.5 w-full">
                            <div>
                                <div class="flex items-center justify-center sm:justify-start gap-2 mb-1">
                                    <span id="roleBadge" class="px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        SISWA
                                    </span>
                                    <span class="text-xs font-mono text-slate-400 uppercase tracking-wider">Terverifikasi</span>
                                </div>
                                <h3 id="personName" class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white tracking-tight leading-tight">
                                    Nama Lengkap
                                </h3>
                            </div>

                            <div class="grid grid-cols-2 gap-2 sm:gap-3 pt-2.5 border-t border-slate-800/80">
                                <div>
                                    <p id="idNumberLabel" class="text-[11px] text-slate-400 m-0">NIS / NISN</p>
                                    <p id="personIdNumber" class="font-mono-num font-semibold text-slate-200 text-xs sm:text-sm m-0">-</p>
                                </div>
                                <div>
                                    <p id="departmentLabel" class="text-[11px] text-slate-400 m-0">Kelas</p>
                                    <p id="personDepartment" class="font-semibold text-emerald-400 text-xs sm:text-sm m-0">-</p>
                                </div>
                                <div>
                                    <p class="text-[11px] text-slate-400 m-0">UID Kartu</p>
                                    <p id="personRfid" class="font-mono-num text-xs text-slate-400 m-0">-</p>
                                </div>
                                <div>
                                    <p class="text-[11px] text-slate-400 m-0">Keterangan</p>
                                    <p id="personNote" class="font-semibold text-emerald-400 text-xs sm:text-sm m-0">Tepat Waktu</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar Countdown Kembali ke Standby -->
                    <div class="mt-5">
                        <div class="flex justify-between text-xs text-slate-400 mb-1.5">
                            <span class="text-[11px]">Kembali ke layar utama otomatis...</span>
                            <span id="countdownText" class="font-mono-num font-bold text-xs text-slate-300">4 dtk</span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                            <div id="resetProgressBar" class="h-full bg-emerald-500 transition-all duration-100 ease-linear" style="width: 100%"></div>
                        </div>
                    </div>
                </div>

                <!-- ────────────────────────────────────────────── -->
                <!-- TAMPILAN 3: KARTU TIDAK TERDAFTAR / ERROR      -->
                <!-- ────────────────────────────────────────────── -->
                <div id="errorView" class="hidden flex-col items-center text-center z-10 max-w-md mx-auto my-auto py-4">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl bg-rose-500/10 border border-rose-500/30 text-rose-500 flex items-center justify-center text-4xl sm:text-5xl mb-4 shadow-lg shadow-rose-500/15">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-white mb-2">Kartu Tidak Terdaftar!</h2>
                    <p class="text-slate-400 text-xs sm:text-sm mb-3">
                        UID Kartu <span id="unknownUid" class="font-mono-num text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded border border-rose-500/20">XX:XX:XX:XX</span> belum terdaftar pada database Siswa maupun Guru.
                    </p>
                    <p class="text-[11px] text-slate-500 m-0">Silakan hubungi bagian Tata Usaha atau Admin Sekolah untuk mendaftarkan kartu.</p>
                </div>

            </div>

        </section>

        <!-- ── KOLOM KANAN: STATISTIK & LOG AKTIVITAS (5 KOLOM DI DESKTOP) ── -->
        <aside class="lg:col-span-5 flex flex-col gap-4 sm:gap-5">
            
            <!-- Ringkasan Statistik 2x2 Grid -->
            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                
                <!-- 1. TOTAL HADIR -->
                <div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:border-slate-700 transition-all shadow-lg">
                    <div class="flex items-center justify-between text-[11px] font-bold tracking-wider text-slate-400 mb-2">
                        <span>TOTAL HADIR</span>
                        <i class="fa-solid fa-users text-emerald-400 text-sm"></i>
                    </div>
                    <div id="statTotalHadir" class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white font-mono-num">
                        {{ $stats['hadir'] ?? 0 }}
                    </div>
                    <div class="text-[11px] text-emerald-400/80 mt-1 font-medium">Siswa &amp; Guru</div>
                </div>

                <!-- 2. TEPAT WAKTU -->
                <div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:border-slate-700 transition-all shadow-lg">
                    <div class="flex items-center justify-between text-[11px] font-bold tracking-wider text-slate-400 mb-2">
                        <span>TEPAT WAKTU</span>
                        <i class="fa-solid fa-square-check text-teal-400 text-sm"></i>
                    </div>
                    <div id="statTepatWaktu" class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-teal-400 font-mono-num">
                        {{ max(0, ($stats['hadir'] ?? 0) - ($stats['terlambat'] ?? 0)) }}
                    </div>
                    <div class="text-[11px] text-teal-400/80 mt-1 font-medium">Tepat Waktu</div>
                </div>

                <!-- 3. TERLAMBAT -->
                <div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:border-slate-700 transition-all shadow-lg">
                    <div class="flex items-center justify-between text-[11px] font-bold tracking-wider text-slate-400 mb-2">
                        <span>TERLAMBAT</span>
                        <i class="fa-solid fa-clock-rotate-left text-amber-400 text-sm"></i>
                    </div>
                    <div id="statTerlambat" class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-amber-400 font-mono-num">
                        {{ $stats['terlambat'] ?? 0 }}
                    </div>
                    <div class="text-[11px] text-amber-400/80 mt-1 font-medium">Terlambat</div>
                </div>

                <!-- 4. SUDAH PULANG -->
                <div class="glass-card rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:border-slate-700 transition-all shadow-lg">
                    <div class="flex items-center justify-between text-[11px] font-bold tracking-wider text-slate-400 mb-2">
                        <span>SUDAH PULANG</span>
                        <i class="fa-solid fa-arrow-right-from-bracket text-blue-400 text-sm"></i>
                    </div>
                    <div id="statSudahPulang" class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-blue-400 font-mono-num">
                        {{ $stats['keluar'] ?? 0 }}
                    </div>
                    <div class="text-[11px] text-blue-400/80 mt-1 font-medium">Sudah Pulang</div>
                </div>

            </div>

            <!-- Daftar Log Presensi Live (Aktivitas Tap Terkini) -->
            <div class="flex-1 glass-card rounded-3xl p-4 sm:p-5 flex flex-col min-h-[360px] sm:min-h-[400px]">
                <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-tower-broadcast text-emerald-400 text-sm" aria-hidden="true"></i>
                        <h3 class="font-bold text-white text-sm sm:text-base m-0">Aktivitas Tap Terkini</h3>
                    </div>
                    <span class="text-[11px] font-semibold bg-slate-800/80 text-slate-300 px-2.5 py-1 rounded-full border border-slate-700/80 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                        Live Feed
                    </span>
                </div>

                <!-- Feed Container -->
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
    <!-- LOGIKA JAVASCRIPT KIOSK                                                 -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <script>
    (function () {
        'use strict';

        const SCAN_URL = '{{ $scan_url }}';
        const HISTORY_URL = '{{ $history_url }}';
        const DEFAULT_PHOTO = '{{ asset("assets/img/siswa.png") }}';
        const CSRF_TOKEN = '{{ csrf_token() }}';
        const serverMode = @json($mode_tab ?? []);

        let activeMode = 'auto';
        let soundEnabled = (localStorage.getItem('kiosk_sound') !== 'false');
        let isProcessing = false;
        let lastUid = null, lastTime = 0;
        let resetTimer = null, progressInterval = null;

        // Inisialisasi Riwayat Lokal
        let attendanceLogs = [];

        // ── 1. Jam & Tanggal WITA Real-Time ──
        witaInit('{{ date('Y-m-d H:i:s') }}');

        function updateClock() {
            const now = witaNow();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

            const dayName = days[now.getDay()];
            const dateNum = now.getDate();
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();

            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');

            $('#liveDate').text(`${dayName}, ${dateNum} ${monthName} ${year}`);
            $('#liveTime').text(`${h}:${m}:${s}`);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // ── 2. Efek Audio Chime (Web Audio API Synthesizer) ──
        function playBeepSound(type) {
            if (!soundEnabled) return;
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const audioCtx = new AudioCtx();
                const oscillator = audioCtx.createOscillator();
                const gainNode = audioCtx.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioCtx.destination);

                if (type === 'success') {
                    // Nada ganda ceria (880Hz -> 1174Hz)
                    oscillator.type = 'sine';
                    oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
                    oscillator.frequency.setValueAtTime(1174.66, audioCtx.currentTime + 0.1);
                    gainNode.gain.setValueAtTime(0.15, audioCtx.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.3);
                    oscillator.start();
                    oscillator.stop(audioCtx.currentTime + 0.3);
                } else if (type === 'warning') {
                    // Nada tunggal untuk terlambat (523Hz)
                    oscillator.type = 'triangle';
                    oscillator.frequency.setValueAtTime(523.25, audioCtx.currentTime);
                    gainNode.gain.setValueAtTime(0.15, audioCtx.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.35);
                    oscillator.start();
                    oscillator.stop(audioCtx.currentTime + 0.35);
                } else {
                    // Nada rendah untuk error (220Hz -> 180Hz)
                    oscillator.type = 'sawtooth';
                    oscillator.frequency.setValueAtTime(220, audioCtx.currentTime);
                    oscillator.frequency.setValueAtTime(180, audioCtx.currentTime + 0.12);
                    gainNode.gain.setValueAtTime(0.15, audioCtx.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.38);
                    oscillator.start();
                    oscillator.stop(audioCtx.currentTime + 0.38);
                }
            } catch (e) {}
        }

        window.toggleSound = function () {
            soundEnabled = !soundEnabled;
            localStorage.setItem('kiosk_sound', soundEnabled ? 'true' : 'false');
            syncSoundIcon();
            if (soundEnabled) playBeepSound('success');
        };

        // Samakan ikon dengan preferensi tersimpan (dulu ikon selalu tampak aktif
        // walau suara sudah dimatikan pada sesi sebelumnya).
        function syncSoundIcon() {
            $('#icon-sound').attr('class', soundEnabled
                ? 'fa-solid fa-volume-high text-emerald-400 text-xs sm:text-sm'
                : 'fa-solid fa-volume-xmark text-slate-500 text-xs sm:text-sm');
            $('#btn-toggle-sound')
                .attr('title', soundEnabled ? 'Suara bel aktif' : 'Suara bel mati')
                .attr('aria-pressed', soundEnabled ? 'true' : 'false');
        }

        // ── 3. Mode Switcher (Auto / Masuk / Pulang) ──
        function getEffectiveMode() {
            if (activeMode !== 'auto') return activeMode;

            // Server memakai "keluar" untuk tab pulang; halaman ini memakai
            // "pulang". Tanpa normalisasi, tap di luar jam masuk tercatat
            // sebagai pulang tetapi kartu hasilnya tampil hijau "masuk".
            const serverDefault = String((serverMode && serverMode.default_mode) || '');
            if (serverDefault === 'keluar' || serverDefault === 'pulang') return 'pulang';
            if (serverDefault === 'masuk') return 'masuk';

            // Jam WITA, bukan jam perangkat: kiosk WIB/WIT tidak boleh memilih
            // mode yang berbeda dari jadwal server.
            return (witaNow().getHours() >= 11) ? 'pulang' : 'masuk';
        }

        // Jelaskan mode yang akan dicatat pada tap berikutnya, supaya petugas
        // tidak perlu menebak apakah kiosk sedang mode masuk atau pulang.
        function updateModeIndicator() {
            const effective = getEffectiveMode();
            const isPulang = effective === 'pulang';
            const prefix = activeMode === 'auto' ? 'Mode otomatis' : 'Mode manual';

            $('#standbyModeText').text(`${prefix} \u00b7 ${isPulang ? 'Pulang' : 'Masuk'}`);
            $('#standbyModeDot').attr('class', 'w-1.5 h-1.5 rounded-full animate-pulse ' + (isPulang ? 'bg-blue-400' : 'bg-emerald-400'));
            $('#standbyModePill').attr('class', 'inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-900/80 border text-[11px] font-semibold mb-3 shadow-sm ' + (isPulang ? 'border-blue-500/40 text-blue-300' : 'border-slate-700/80 text-slate-300'));
        }

        function setMode(mode) {
            activeMode = mode;
            $('#mode-group button').removeClass('active text-white').addClass('text-slate-400').attr('aria-pressed', 'false');
            $(`#mode-group button[data-mode="${mode}"]`).addClass('active text-white').removeClass('text-slate-400').attr('aria-pressed', 'true');
            updateModeIndicator();
            focusWedgeInput();
        }

        $('#mode-group button').on('click', function () {
            setMode($(this).data('mode'));
        });

        // ── 4. Input Focus & Tangkap USB Reader ──
        const $hiddenInput = $('#rfidHiddenInput');
        // PENTING: gunakan fokus native ($hiddenInput[0].focus()), BUKAN
        // $hiddenInput.focus(). jQuery .focus() = .trigger('focus') yang ikut
        // membubble sampai window, sehingga handler $(window).on('focus', ...)
        // di bawah terpanggil lagi -> rekursi tanpa henti
        // (RangeError: Maximum call stack size exceeded) setiap kali ada klik.
        window.focusWedgeInput = function () {
            const el = $hiddenInput[0];
            if (el && document.activeElement !== el) {
                el.focus();
            }
        };

        $(document).on('click', function (e) {
            if (!$(e.target).closest('button, a, input, select, .swal2-container').length) {
                focusWedgeInput();
            }
        });
        $(window).on('focus', focusWedgeInput);

        let buffer = '', bufTimer = null;
        $(window).on('keydown', function (e) {
            if ($('.swal2-container').length) return;
            
            // Hotkeys: F (Fullscreen), M (Mode cycle), S (Sound)
            if (e.target.tagName !== 'INPUT') {
                if (e.key === 'f' || e.key === 'F') {
                    e.preventDefault();
                    toggleFullscreen();
                    return;
                }
                if (e.key === 's' || e.key === 'S') {
                    e.preventDefault();
                    toggleSound();
                    return;
                }
                if (e.key === 'm' || e.key === 'M') {
                    e.preventDefault();
                    const modes = ['auto', 'masuk', 'pulang'];
                    const nextMode = modes[(modes.indexOf(activeMode) + 1) % modes.length];
                    setMode(nextMode);
                    return;
                }
            }

            if (e.key === 'Enter') {
                e.preventDefault();
                const raw = ($hiddenInput.val() || buffer).trim();
                $hiddenInput.val('');
                buffer = '';
                if (raw.length >= 2) processRfidScan(raw);
                return;
            }

            if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
                buffer += e.key;
                clearTimeout(bufTimer);
                bufTimer = setTimeout(() => {
                    const raw = ($hiddenInput.val() || buffer).trim();
                    $hiddenInput.val('');
                    buffer = '';
                    if (raw.length >= 4) processRfidScan(raw);
                }, 180);
            }
        });

        // ── 5. Eksekusi Scan RFID ke Backend ──
        window.processRfidScan = function (rfidCode) {
            const cleanCode = (rfidCode || '').trim();
            if (!cleanCode || isProcessing) return;

            const now = Date.now();
            if (cleanCode === lastUid && (now - lastTime) < 3000) return;
            isProcessing = true;
            lastUid = cleanCode;
            lastTime = now;

            clearTimeout(resetTimer);
            clearInterval(progressInterval);

            const mode = getEffectiveMode();
            $('#reader-status').text('Membaca...');

            $.ajax({
                url: SCAN_URL,
                type: 'POST',
                data: {
                    _token: CSRF_TOKEN,
                    rfid_value: cleanCode,
                    mode: mode
                },
                dataType: 'json',
                success: function (res) {
                    isProcessing = false;
                    $('#reader-status').text('Standby');

                    if (res && res.status) {
                        renderScanSuccess(res, cleanCode, mode);
                    } else {
                        renderScanError(res, cleanCode);
                    }
                    focusWedgeInput();
                },
                error: function (xhr) {
                    isProcessing = false;
                    $('#reader-status').text('Standby');
                    let msg = 'Nomor kartu belum terdaftar';
                    try {
                        const parsed = JSON.parse(xhr.responseText);
                        if (parsed.message) msg = parsed.message;
                    } catch(e) {}
                    renderScanError({ message: msg }, cleanCode);
                    focusWedgeInput();
                }
            });
        };

        // ── 6. Render Hasil Scan Berhasil (Siswa & Guru) ──
        function renderScanSuccess(res, rfidCode, currentMode) {
            const isGuru = !!(res.guru || res.role === 'guru');
            const person = res.guru || res.siswa || {};
            const isLate = !!(res.telat || res.terlambat);

            const $standby = $('#standbyView');
            const $result = $('#resultView');
            const $error = $('#errorView');
            const $container = $('#scannerContainer');
            const $glow = $('#glowEffect');

            $standby.addClass('hidden');
            $error.addClass('hidden').removeClass('flex');
            $result.removeClass('hidden').addClass('flex');

            // Format Role
            const $roleBadge = $('#roleBadge');
            const $idNumberLabel = $('#idNumberLabel');
            const $departmentLabel = $('#departmentLabel');

            if (isGuru) {
                $roleBadge.text('GURU & GTK')
                    .attr('class', 'px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-purple-500/20 text-purple-300 border border-purple-500/30');
                $idNumberLabel.text('NIP / NUPTK');
                $departmentLabel.text('Jabatan / Mapel');
            } else {
                $roleBadge.text('SISWA')
                    .attr('class', 'px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30');
                $idNumberLabel.text('NIS / NISN');
                $departmentLabel.text('Kelas');
            }

            // Biodata
            const nama = person.nama || person.nama_guru || '-';
            const idNumber = isGuru ? (person.nip || person.nuptk || person.kode_guru || '-') : (person.nis || person.nisn || '-');
            const department = isGuru ? (person.jabatan || 'Guru / GTK') : (person.nama_kelas || person.kelas || '-');
            const now = witaNow();
            const timeStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;

            $('#personName').text(nama);
            $('#personIdNumber').text(idNumber);
            $('#personDepartment').text(department);
            $('#personRfid').text(rfidCode);
            $('#personPhoto').attr('src', person.foto || DEFAULT_PHOTO);
            $('#scanTimestamp').text(`${timeStr} WITA`);

            // Styling Banner Status
            const $statusBanner = $('#statusBanner');
            const $statusIcon = $('#statusIcon');
            const $statusText = $('#statusText');
            const $photoBadge = $('#photoBadge');
            const $photoBadgeIcon = $('#photoBadgeIcon');
            const $personNote = $('#personNote');

            if (currentMode === 'pulang') {
                $container.attr('class', 'flex-1 glass-panel state-info border-2 border-blue-500/70 rounded-3xl p-6 sm:p-8 md:p-9 flex flex-col items-center justify-between relative overflow-hidden transition-all duration-300 shadow-2xl shadow-blue-500/15 min-h-[400px] sm:min-h-[480px]');
                $glow.attr('class', 'absolute inset-0 bg-gradient-to-b from-blue-500/15 to-transparent pointer-events-none');
                $statusBanner.attr('class', 'w-full py-3.5 px-5 rounded-2xl mb-5 flex items-center justify-between font-bold text-sm sm:text-base bg-blue-500/20 text-blue-300 border border-blue-500/30');
                $statusIcon.attr('class', 'fa-solid fa-house-circle-check text-xl text-blue-400');
                $statusText.text(`ABSEN PULANG ${isGuru ? 'GURU' : 'SISWA'} TERCATAT`);
                $photoBadge.attr('class', 'absolute -bottom-2 -right-2 w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-blue-500 text-white flex items-center justify-center shadow-lg border-2 border-slate-900');
                $photoBadgeIcon.attr('class', 'fa-solid fa-house text-sm');
                $personNote.attr('class', 'font-semibold text-blue-400 text-xs sm:text-sm m-0').text('Absen Pulang Selesai');
                playBeepSound('success');
            } else if (isLate) {
                const telatMenit = res.telat_menit || res.terlambat || 0;
                $container.attr('class', 'flex-1 glass-panel state-warning border-2 border-amber-500/70 rounded-3xl p-6 sm:p-8 md:p-9 flex flex-col items-center justify-between relative overflow-hidden transition-all duration-300 shadow-2xl shadow-amber-500/15 min-h-[400px] sm:min-h-[480px]');
                $glow.attr('class', 'absolute inset-0 bg-gradient-to-b from-amber-500/15 to-transparent pointer-events-none');
                $statusBanner.attr('class', 'w-full py-3.5 px-5 rounded-2xl mb-5 flex items-center justify-between font-bold text-sm sm:text-base bg-amber-500/20 text-amber-300 border border-amber-500/30');
                $statusIcon.attr('class', 'fa-solid fa-clock text-xl text-amber-400');
                $statusText.text(`ABSEN MASUK ${isGuru ? 'GURU' : 'SISWA'} (TERLAMBAT)`);
                $photoBadge.attr('class', 'absolute -bottom-2 -right-2 w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-lg border-2 border-slate-900');
                $photoBadgeIcon.attr('class', 'fa-solid fa-clock text-sm');
                $personNote.attr('class', 'font-semibold text-amber-400 text-xs sm:text-sm m-0').text(`Terlambat (${telatMenit} Menit)`);
                playBeepSound('warning');
            } else {
                $container.attr('class', 'flex-1 glass-panel state-success border-2 border-emerald-500/70 rounded-3xl p-6 sm:p-8 md:p-9 flex flex-col items-center justify-between relative overflow-hidden transition-all duration-300 shadow-2xl shadow-emerald-500/15 min-h-[400px] sm:min-h-[480px]');
                $glow.attr('class', 'absolute inset-0 bg-gradient-to-b from-emerald-500/15 to-transparent pointer-events-none');
                $statusBanner.attr('class', 'w-full py-3.5 px-5 rounded-2xl mb-5 flex items-center justify-between font-bold text-sm sm:text-base bg-emerald-500/20 text-emerald-300 border border-emerald-500/30');
                $statusIcon.attr('class', 'fa-solid fa-circle-check text-xl text-emerald-400');
                $statusText.text(`ABSEN MASUK ${isGuru ? 'GURU' : 'SISWA'} BERHASIL`);
                $photoBadge.attr('class', 'absolute -bottom-2 -right-2 w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center shadow-lg border-2 border-slate-900');
                $photoBadgeIcon.attr('class', 'fa-solid fa-check text-sm');
                $personNote.attr('class', 'font-semibold text-emerald-400 text-xs sm:text-sm m-0').text('Hadir Tepat Waktu');
                playBeepSound('success');
            }

            // Perbarui statistik
            if (res.stats) {
                updateStats(res.stats);
            } else {
                let statH = parseInt($('#statTotalHadir').text()) || 0;
                let statT = parseInt($('#statTerlambat').text()) || 0;
                let statP = parseInt($('#statSudahPulang').text()) || 0;
                if (currentMode === 'pulang') {
                    $('#statSudahPulang').text(statP + 1);
                } else {
                    $('#statTotalHadir').text(statH + 1);
                    if (isLate) $('#statTerlambat').text(statT + 1);
                    const newH = statH + 1;
                    const newT = isLate ? (statT + 1) : statT;
                    $('#statTepatWaktu').text(Math.max(0, newH - newT));
                }
            }

            // Tambahkan ke riwayat lokal
            prependAttendanceLog({
                role: isGuru ? 'GURU' : 'SISWA',
                name: nama,
                department: department,
                time: timeStr.substring(0, 5),
                status: (currentMode === 'pulang') ? 'PULANG' : (isLate ? 'TERLAMBAT' : 'MASUK'),
                isLate: isLate,
                photo: person.foto || DEFAULT_PHOTO
            });

            startResetCountdown(4000);
        }

        // ── 7. Render Hasil Scan Error (Kartu Tidak Terdaftar) ──
        function renderScanError(res, rfidCode) {
            const $standby = $('#standbyView');
            const $result = $('#resultView');
            const $error = $('#errorView');
            const $container = $('#scannerContainer');
            const $glow = $('#glowEffect');

            $standby.addClass('hidden');
            $result.addClass('hidden').removeClass('flex');
            $error.removeClass('hidden').addClass('flex');

            $('#unknownUid').text(rfidCode || 'TIDAK TERBACA');
            $container.attr('class', 'flex-1 glass-panel state-error border-2 border-rose-500/80 rounded-3xl p-6 sm:p-8 md:p-9 flex flex-col items-center justify-between relative overflow-hidden transition-all duration-300 shadow-2xl shadow-rose-500/15 min-h-[400px] sm:min-h-[480px]');
            $glow.attr('class', 'absolute inset-0 bg-gradient-to-b from-rose-500/15 to-transparent pointer-events-none');

            playBeepSound('error');

            resetTimer = setTimeout(() => {
                resetToStandby();
            }, 3500);
        }

        function startResetCountdown(durationMs) {
            const $progressBar = $('#resetProgressBar');
            const $countdownText = $('#countdownText');
            const startTime = Date.now();

            $progressBar.css('width', '100%');

            progressInterval = setInterval(() => {
                const elapsed = Date.now() - startTime;
                const remaining = Math.max(0, durationMs - elapsed);
                const percentage = (remaining / durationMs) * 100;

                $progressBar.css('width', `${percentage}%`);
                $countdownText.text(`${Math.ceil(remaining / 1000)} dtk`);

                if (remaining <= 0) {
                    clearInterval(progressInterval);
                    resetToStandby();
                }
            }, 50);
        }

        function resetToStandby() {
            clearInterval(progressInterval);
            clearTimeout(resetTimer);

            $('#resultView').addClass('hidden').removeClass('flex');
            $('#errorView').addClass('hidden').removeClass('flex');
            $('#standbyView').removeClass('hidden');

            $('#scannerContainer').attr('class', 'flex-1 glass-panel rounded-3xl p-6 sm:p-8 md:p-9 flex flex-col items-center justify-between relative overflow-hidden transition-all duration-300 min-h-[400px] sm:min-h-[480px]');
            $('#glowEffect').attr('class', 'absolute inset-0 bg-gradient-to-b from-teal-500/10 via-transparent to-transparent pointer-events-none transition-colors duration-500');
            updateModeIndicator();
            focusWedgeInput();
        }

        // Helper Initials Avatar
        function getInitials(name) {
            if (!name) return '??';
            const parts = name.trim().split(/\s+/);
            if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }

        // ── 8. Render Riwayat Kehadiran (Live Feed) ──
        function prependAttendanceLog(log) {
            attendanceLogs.unshift(log);
            if (attendanceLogs.length > 20) attendanceLogs.pop();
            renderAttendanceLogs();
        }

        function renderAttendanceLogs() {
            const $container = $('#attendanceLogList');

            if (!attendanceLogs || attendanceLogs.length === 0) {
                $container.html(`
                    <div id="empty-feed-placeholder" class="py-12 text-center text-xs text-slate-500">
                        <i class="fa-solid fa-inbox text-2xl mb-1.5 block text-slate-600"></i>
                        Belum ada aktivitas presensi hari ini
                    </div>
                `);
                return;
            }

            const html = attendanceLogs.map((log, index) => {
                let statusBadge = '';
                if (log.status === 'PULANG') {
                    statusBadge = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-blue-500/15 text-blue-400 border border-blue-500/30">Pulang</span>`;
                } else if (log.isLate || log.status === 'TERLAMBAT') {
                    statusBadge = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/15 text-amber-400 border border-amber-500/30">Terlambat</span>`;
                } else {
                    statusBadge = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">Masuk</span>`;
                }

                const initials = getInitials(log.name);
                const hasCustomPhoto = (log.photo && !log.photo.includes('siswa.png') && !log.photo.includes('guru.png'));
                const avatarEl = hasCustomPhoto
                    ? `<img src="${log.photo}" alt="Foto" class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl object-cover border border-slate-700/80 shrink-0 bg-slate-800" onerror="this.outerHTML='<div class=\\'w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-gradient-to-tr from-rose-500 to-pink-600 flex items-center justify-center font-bold text-white text-xs sm:text-sm shadow-md shrink-0\\'>${initials}</div>'">`
                    : `<div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-gradient-to-tr from-rose-500 to-pink-600 flex items-center justify-center font-bold text-white text-xs sm:text-sm shadow-md shrink-0">${initials}</div>`;

                const highlightClass = index === 0 ? 'bg-slate-900/90 border-emerald-500/40 shadow-md' : 'bg-slate-950/60 border-slate-800/80';

                return `
                    <div class="flex items-center justify-between p-2.5 sm:p-3 rounded-2xl border ${highlightClass} transition-all hover:border-slate-700">
                        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                            ${avatarEl}
                            <div class="min-w-0">
                                <h4 class="text-xs sm:text-sm font-bold text-white truncate m-0">${log.name}</h4>
                                <p class="text-[11px] text-slate-400 truncate m-0">${log.department}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0 ml-2 flex flex-col items-end gap-1">
                            ${statusBadge}
                            <span class="font-mono-num text-[11px] text-slate-400 font-medium">${log.time}</span>
                        </div>
                    </div>
                `;
            }).join('');

            $container.html(html);
        }

        function updateStats(s) {
            if (!s) return;
            const hadir = parseInt(s.hadir !== undefined ? s.hadir : ((s.siswa_hadir || 0) + (s.guru_hadir || 0))) || 0;
            const terlambat = parseInt(s.terlambat || 0);
            const keluar = parseInt(s.keluar || 0);
            const tepatWaktu = Math.max(0, hadir - terlambat);

            $('#statTotalHadir').text(hadir);
            $('#statTepatWaktu').text(tepatWaktu);
            $('#statTerlambat').text(terlambat);
            $('#statSudahPulang').text(keluar);
        }

        // ── 9. Sinkronisasi Polling History Backend ──
        function pollHistory() {
            $.ajax({
                url: HISTORY_URL,
                type: 'GET',
                data: { limit: 12 },
                dataType: 'json',
                success: function (res) {
                    if (res && res.status && Array.isArray(res.data) && res.data.length > 0) {
                        attendanceLogs = res.data.map(r => {
                            const isGuru = (r.role === 'guru') || (r.nama_kelas === 'GURU / GTK') || !!r.nip;
                            const isLate = !!(r.terlambat && r.terlambat > 0) || r.status === 'LATE';
                            const st = r.jam_keluar ? 'PULANG' : (isLate ? 'TERLAMBAT' : 'MASUK');
                            const timeRaw = r.jam_keluar || r.jam_masuk || '';
                            const timeShort = timeRaw ? String(timeRaw).substr(0, 5) : '--:--';

                            return {
                                role: isGuru ? 'GURU' : 'SISWA',
                                name: r.nama_siswa || r.nama_guru || r.nama || '-',
                                department: isGuru ? (r.jabatan || 'Guru / GTK') : (r.nama_kelas || 'Kelas'),
                                time: timeShort,
                                status: st,
                                isLate: isLate,
                                photo: r.foto || DEFAULT_PHOTO
                            };
                        });
                        renderAttendanceLogs();
                    }
                    if (res && res.stats) updateStats(res.stats);
                }
            });
        }

        // ── 10. Web NFC API (Sensor NFC Android) ──
        // Web NFC (NDEFReader) hanya tersedia di Chrome Android pada konteks
        // aman (HTTPS/localhost), dan ndef.scan() WAJIB dipicu gesture
        // pengguna. Pemanggilan otomatis saat halaman dimuat selalu ditolak
        // NotAllowedError — sebelumnya kegagalan itu ditelan `.catch(() => {})`
        // sehingga kartu yang ditempel tidak menimbulkan reaksi apa pun.
        function nfcDidukung() {
            return window.isSecureContext && ('NDEFReader' in window);
        }

        window.startNfcScan = function () {
            if (!nfcDidukung()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sensor NFC Tidak Tersedia',
                    html: window.isSecureContext
                        ? 'Browser ini tidak mendukung Web NFC.<br>Gunakan <b>Chrome di Android</b> dan aktifkan NFC perangkat.'
                        : 'Web NFC hanya berjalan lewat <b>HTTPS</b>.<br>Buka kiosk ini dengan alamat <b>https://…</b>, bukan http://…',
                    background: '#0a0f1d',
                    color: '#f8fafc',
                    confirmButtonColor: '#10b981'
                });
                return;
            }

            const ndef = new NDEFReader();
            $('#reader-status').text('Menyiapkan NFC...');

            ndef.scan().then(function () {
                $('#nfc-chip').removeClass('hidden');
                $('#header-status-badge').html('Terminal Absen Mandiri &bull; <span class="text-emerald-400 font-bold">NFC Aktif</span>');
                $('#btn-enable-nfc').addClass('hidden').removeClass('inline-flex');
                $('#reader-status').text('NFC aktif \u00b7 tempelkan kartu');

                ndef.onreading = function (event) {
                    const serial = event.serialNumber;
                    if (!serial) return;
                    if (navigator.vibrate) navigator.vibrate([60, 40, 60]);
                    const cleanUid = serial.replace(/[: -]/g, '').toUpperCase();
                    $('#reader-status').text('Membaca: ' + cleanUid);
                    processRfidScan(cleanUid);
                };

                ndef.onreadingerror = function () {
                    if (navigator.vibrate) navigator.vibrate(200);
                    $('#reader-status').text('Gagal membaca kartu');
                };
            }).catch(function (err) {
                $('#btn-enable-nfc').removeClass('hidden').addClass('inline-flex');
                $('#reader-status').text('NFC tidak aktif');

                const pesan = (err && err.name === 'NotAllowedError')
                    ? 'Izin NFC belum diberikan. Tekan <b>Aktifkan Sensor NFC</b> lalu pilih <b>Allow</b> saat Chrome meminta izin.'
                    : 'Gagal mengaktifkan sensor NFC: ' + ((err && err.message) || 'penyebab tidak diketahui');

                Swal.fire({
                    icon: 'error',
                    title: 'NFC Gagal Aktif',
                    html: pesan,
                    background: '#0a0f1d',
                    color: '#f8fafc',
                    confirmButtonColor: '#10b981'
                });
            });
        };

        // Tombol aktivasi hanya muncul kalau perangkat memang mendukung NFC;
        // kalau tidak, status diberi keterangan supaya petugas tidak menduga
        // kiosk rusak saat kartu ditempel tanpa hasil.
        if (nfcDidukung()) {
            $('#btn-enable-nfc').removeClass('hidden').addClass('inline-flex');
            $('#reader-status').text('Tekan Aktifkan NFC');
        } else {
            $('#reader-status').text(window.isSecureContext ? 'Browser tanpa NFC' : 'NFC butuh HTTPS');
        }

        // ── 11. Modal Input Manual UID / NIS ──
        window.promptManualUid = function () {
            Swal.fire({
                title: 'Input Manual UID / NIS',
                html: '<p class="text-xs text-slate-400 mb-2">Masukkan UID kartu RFID / nomor NIS Siswa / NIP Guru:</p>',
                input: 'text',
                inputAttributes: { autocapitalize: 'characters', autocomplete: 'off', placeholder: 'Ketik UID atau nomor...' },
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-check mr-1"></i> Proses',
                cancelButtonText: 'Batal',
                background: '#0a0f1d',
                color: '#f8fafc',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#334155'
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    processRfidScan(result.value.trim());
                }
                focusWedgeInput();
            });
        };

        // ── 12. Fullscreen Toggle ──
        window.toggleFullscreen = function () {
            const icon = document.getElementById('fullscreenIcon');
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
                if (icon) icon.className = 'fa-solid fa-compress text-xs sm:text-sm';
            } else {
                if (document.exitFullscreen) document.exitFullscreen().catch(() => {});
                if (icon) icon.className = 'fa-solid fa-expand text-xs sm:text-sm';
            }
        };

        // Inisialisasi awal
        $(document).ready(function () {
            syncSoundIcon();
            updateModeIndicator();
            focusWedgeInput();
            pollHistory();
            setInterval(() => {
                pollHistory();
            }, 25000);
            // Mode auto bisa berganti saat kiosk dibiarkan terbuka.
            setInterval(updateModeIndicator, 60000);
        });

    })();
    </script>
</body>
</html>
