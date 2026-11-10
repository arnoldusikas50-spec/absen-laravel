@extends('layouts.main')

@section('styles')
<style>
    /* ── Stat chip (breakdown panel) ─────────────────── */
    .stat-chip { transition: transform 0.15s; }
    .stat-chip:hover { transform: translateY(-2px); }

    /* ── Quick action tile ──────────────────────── */
    .qa-tile { transition: background 0.15s, border-color 0.15s, transform 0.15s; }
    .qa-tile:hover { transform: translateY(-2px); }

    /* ── Timeline dot connector ─────────────────── */
    .tl-connector::before {
        content: ''; position: absolute;
        top: 28px; left: 18px; bottom: -8px;
        width: 1px; background: #f1f5f9;
    }

    /* ── Donut ring (statistik guru/siswa) ───────────── */
    .donut-ring {
        stroke-dasharray: var(--pct, 0) 100;
        stroke-linecap: round;
        transition: stroke-dasharray 0.6s ease;
    }

    /* ── Check-in list polish ─────────────────── */
    .checkin-row { transition: background 0.15s; }
    .checkin-row:hover { background: #f8fafc; }
    .checkin-scroll::-webkit-scrollbar { width: 6px; }
    .checkin-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 9999px; }
    .checkin-scroll::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    .tabular { font-variant-numeric: tabular-nums; }

    /* ── Pulse dot ──────────────────────────────── */
    .pulse-live::after {
        content: ''; position: absolute;
        inset: 0; border-radius: 9999px;
        background: currentColor; opacity: 0.6;
        animation: pulseLive 1.8s ease-out infinite;
    }
    @keyframes pulseLive { 75%,100% { transform: scale(2.4); opacity: 0; } }

    /* ── Icon box centering ─────────────────────────
       Bootstrap Icons punya vertical-align: -0.125em bawaan yang
       mendorong ikon ke bawah di dalam container flex centered.
       Class .ic-box menetapkan display:inline-flex + reset vertical-align
       sehingga ikon selalu tepat tengah secara optis. ──────────────── */
    .ic-box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .ic-box i, .ic-box .bi {
        vertical-align: 0;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endsection

@section('content')
<?php
$jamSekarang = (int)date('H');
$salam = $jamSekarang < 11 ? 'Selamat Pagi' : ($jamSekarang < 15 ? 'Selamat Siang' : ($jamSekarang < 18 ? 'Selamat Sore' : 'Selamat Malam'));
$namaUser = session('nama') ?? $user->first_name ?? 'Administrator';

$ph = $presensiHariIni ?? [
    'hadir' => 0, 'ontime' => 0, 'telat' => 0, 'izin' => 0, 'sakit' => 0,
    'cuti' => 0, 'dinas' => 0, 'wfh' => 0, 'alpha' => 0, 'belum_absen' => 0,
    'pending' => 0, 'total_guru' => 0, 'persen' => 0
];
$pending = $pendingSummary ?? ['dinas' => 0, 'koreksi' => 0, 'izin' => 0, 'wajah' => 0, 'kehadiran' => 0, 'total' => 0];
$persen  = min(100, max(0, (float)($ph['persen'] ?? 0)));

// Statistik presensi siswa (dari DashboardController::getSiswaPresensiHariIni)
$sp          = $siswaPresensiHariIni ?? [
    'hadir' => 0, 'telat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'bdr' => 0,
    'tanpa_ket' => 0, 'belum_absen' => 0, 'recorded' => 0, 'total_siswa' => 0, 'persen' => 0
];
$persenSiswa = min(100, max(0, (float)($sp['persen'] ?? 0)));

$hariSekolah      = $hari_sekolah ?? ['status' => 'aktif', 'keterangan' => 'Hari kerja sekolah', 'nama_hari' => ''];
$isHariSekolah    = $is_hari_sekolah ?? true;
$nextSchoolDay    = $next_school_day ?? null;
$todayLabel       = $today_label ?? buat_tanggal(date('l, d F Y'));
$isAkhirPekan     = ($hariSekolah['status'] ?? '') === 'akhir_pekan';
$liburLabel       = $isAkhirPekan ? 'Libur Mingguan' : 'Hari Libur';
$liburKeterangan  = trim((string)($hariSekolah['keterangan'] ?? '')) ?: $liburLabel;
$deskripsiLibur   = $isAkhirPekan
    ? 'Hari ' . trim((string)($hariSekolah['nama_hari'] ?? '')) . ' ditetapkan sebagai libur mingguan.'
    : 'Hari ini libur: ' . rtrim($liburKeterangan, '.') . '.';
$checkinDateLabel = $checkin_date_label ?? null;
$checkinCount     = is_array($recentPresensi ?? null) ? count($recentPresensi) : 0;

if ($isHariSekolah) {
    $salamTampil = $salam;
    $sapaan = 'Selamat datang di panel admin. Pantau kehadiran guru dan siswa secara real-time.';
} else {
    $salamTampil = $isAkhirPekan ? 'Selamat Berakhir Pekan' : 'Selamat Hari Libur';
    $sapaan = $isAkhirPekan
        ? 'Sekolah libur hari ini. Presensi tidak dihitung — nikmati waktu istirahat Anda.'
        : $deskripsiLibur . ' Presensi tidak dihitung.';
}

// Donut SVG helpers
$circumference = 100;
$pctOntime = $ph['total_guru'] > 0 ? round(($ph['ontime'] / $ph['total_guru']) * 100) : 0;
$pctTelat  = $ph['total_guru'] > 0 ? round(($ph['telat']  / $ph['total_guru']) * 100) : 0;
?>

<div class="fade-in-up">

    <!-- ══════════════════════════════════════════════════
         HERO BANNER
    ══════════════════════════════════════════════════ -->
    <div class="px-6 pt-6 pb-0">
        {{--
            Gradien hero sengaja direduksi supaya tidak pekat/neon.
            Warna tema (primary -> accent -> indigo) dilapis di atas dasar
            slate gelap dengan alpha < 1, sehingga warnanya meredup dan tetap
            mengikuti preset tema yang dipilih (App\Libraries\Theme).
            Variabel --primary-*-rgb / --accent-*-rgb / --indigo-*-rgb dikirim
            oleh Theme::styleTag() pada layout.
        --}}
        <div class="relative overflow-hidden rounded-3xl text-white shadow-lg"
            style="background-color:#1e293b;background-image:linear-gradient(135deg,rgb(var(--primary-600-rgb)/0.82) 0%,rgb(var(--accent-600-rgb)/0.72) 52%,rgb(var(--indigo-700-rgb)/0.85) 100%);">
            <!-- Decorative blobs -->
            <div class="absolute -right-16 -bottom-16 w-64 h-64 rounded-full bg-white/5 pointer-events-none"></div>
            <div class="absolute right-40 -top-20 w-40 h-40 rounded-full bg-white/5 pointer-events-none"></div>
            <div class="absolute left-0 top-0 w-32 h-full bg-gradient-to-r from-blue-800/40 to-transparent pointer-events-none"></div>
            <div class="absolute inset-0 pointer-events-none"
                style="background:radial-gradient(110% 80% at 88% 4%,rgb(var(--accent-400-rgb)/0.22),transparent 62%);"></div>

            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-6 p-6 md:p-7">
                <!-- Left: greeting + meta -->
                <div class="lg:col-span-7 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-white/10 rounded-full text-[11px] font-semibold text-blue-100 border border-white/15 backdrop-blur-sm">
                            <i class="bi bi-calendar3"></i>
                            <span>{{ $todayLabel }}</span>
                            <?php if (isset($tp_active) && $tp_active): ?>
                                <span class="text-white/40">·</span>
                                <span>TP {{ $tp_active->tahun }}</span>
                            <?php endif; ?>
                            <?php if (isset($smt_active) && $smt_active): ?>
                                <span class="text-white/40">·</span>
                                <span>Smt {{ $smt_active->nama_smt }}</span>
                            <?php endif; ?>
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wide border backdrop-blur-sm {{ $isHariSekolah ? 'bg-emerald-400/15 text-emerald-100 border-emerald-300/30' : 'bg-amber-300/15 text-amber-100 border-amber-200/30' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $isHariSekolah ? 'bg-emerald-300' : 'bg-amber-300' }}"></span>
                            {{ $isHariSekolah ? 'Hari Sekolah' : $liburLabel }}
                        </span>
                    </div>

                    <h1 class="text-2xl md:text-[2rem] font-extrabold tracking-tight leading-[1.15]">
                        {{ $salamTampil }}, {{ $namaUser }}! <span aria-hidden="true">👋</span>
                    </h1>
                    <p class="text-sm text-blue-100/80 mt-2 max-w-xl leading-relaxed">
                        {{ $sapaan }}
                    </p>

                    <!-- Ringkasan cepat di dalam hero -->
                    <?php if ($isHariSekolah): ?>
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-3 mt-5 pt-5 border-t border-white/10">
                        <div>
                            <p class="text-[10px] text-white/50 font-semibold uppercase tracking-wider leading-none">Tepat Waktu</p>
                            <p class="text-lg font-extrabold text-emerald-300 leading-none mt-1 tabular">{{ (int)($ph['ontime'] ?? 0) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-white/50 font-semibold uppercase tracking-wider leading-none">Terlambat</p>
                            <p class="text-lg font-extrabold text-amber-300 leading-none mt-1 tabular">{{ (int)($ph['telat'] ?? 0) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-white/50 font-semibold uppercase tracking-wider leading-none">Belum Absen</p>
                            <p class="text-lg font-extrabold text-rose-300 leading-none mt-1 tabular">{{ (int)($ph['belum_absen'] ?? 0) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-white/50 font-semibold uppercase tracking-wider leading-none">Total Guru</p>
                            <p class="text-lg font-extrabold text-white leading-none mt-1 tabular">{{ (int)$ph['total_guru'] }}</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right: live attendance pill + action -->
                <div class="lg:col-span-5 flex flex-col sm:flex-row lg:flex-col items-stretch sm:items-center lg:items-stretch lg:justify-end gap-3 shrink-0">
                    <?php if ($isHariSekolah): ?>
                        <!-- Live attendance mini pill -->
                        <div class="flex-1 flex items-center gap-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl px-4 py-3.5">
                            <div class="relative w-2.5 h-2.5 rounded-full bg-emerald-400 pulse-live flex-shrink-0"></div>
                            <div class="min-w-0">
                                <p class="text-[10px] text-white/60 font-semibold uppercase tracking-wider leading-none">Hadir Hari Ini</p>
                                <p class="text-2xl font-extrabold text-white leading-none mt-1 tabular">
                                    {{ (int)$ph['hadir'] }}
                                    <span class="text-sm font-semibold text-white/50">/ {{ (int)$ph['total_guru'] }}</span>
                                </p>
                            </div>
                            <div class="ml-auto text-right shrink-0 hidden sm:block">
                                <p class="text-[10px] text-white/50 font-semibold uppercase tracking-wider leading-none">Tingkat</p>
                                <p class="text-lg font-extrabold text-emerald-300 leading-none mt-1 tabular">{{ $persen }}%</p>
                            </div>
                        </div>

                        <a href="{{ url('admin/presensi') }}"
                            class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-white text-blue-700 font-bold rounded-2xl hover:bg-blue-50 transition-colors shadow text-sm no-underline shrink-0">
                            <i class="bi bi-speedometer2"></i>
                            <span>Monitoring Presensi</span>
                        </a>
                    <?php else: ?>
                        <div class="flex-1 flex items-center gap-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl px-4 py-3.5">
                            <i class="bi bi-calendar-x text-2xl text-amber-200 shrink-0"></i>
                            <div class="min-w-0">
                                <p class="text-[10px] text-white/60 font-semibold uppercase tracking-wider leading-none">{{ $liburLabel }}</p>
                                <p class="text-sm font-extrabold text-white leading-none mt-1">Tidak ada presensi</p>
                            </div>
                        </div>

                        <a href="{{ url('admin/presensi/rekap') }}"
                            class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-white text-blue-700 font-bold rounded-2xl hover:bg-blue-50 transition-colors shadow text-sm no-underline shrink-0">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                            <span>Rekap Presensi</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bottom bar inside hero -->
            <div class="relative z-10 px-6 md:px-7 pb-5">
                <?php if ($isHariSekolah): ?>
                    <div class="flex items-center justify-between text-[11px] text-white/70 mb-2">
                        <span class="inline-flex items-center gap-1.5">
                            <i class="bi bi-graph-up-arrow text-emerald-300"></i>
                            Tingkat Kehadiran Guru Hari Ini
                        </span>
                        <span class="font-extrabold text-white tabular">{{ $persen }}%</span>
                    </div>
                    <div class="h-2 w-full bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-300 transition-all duration-700"
                            style="width: {{ $persen }}%"></div>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[11px] text-white/70 border-t border-white/10 pt-3">
                        <span class="inline-flex items-center gap-1.5">
                            <i class="bi bi-info-circle"></i>
                            Presensi tidak dihitung pada hari libur
                        </span>
                        <?php if ($nextSchoolDay): ?>
                            <span class="inline-flex items-center gap-1.5 font-semibold text-white/85">
                                <i class="bi bi-calendar-check"></i>
                                Hari sekolah berikutnya: {{ $nextSchoolDay['label'] }}
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pending badge row (below hero, if any) -->
    <?php if (!empty($pending['total']) && $pending['total'] > 0): ?>
    <div class="px-6 mt-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500 text-white ic-box shrink-0 shadow-sm mt-0.5">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-amber-950">
                        {{ $pending['total'] }} Pengajuan Menunggu Tindakan
                    </p>
                    <div class="flex flex-wrap gap-1.5 mt-1.5">
                        <?php
                        $pendingItems = [
                            ['key' => 'dinas',  'icon' => 'bi-briefcase',      'label' => 'Dinas',   'url' => 'admin/presensi/dinas'],
                            ['key' => 'koreksi','icon' => 'bi-pencil-square',  'label' => 'Koreksi', 'url' => 'admin/presensi/koreksi'],
                            ['key' => 'izin',   'icon' => 'bi-envelope-open',  'label' => 'Izin',    'url' => 'admin/presensi/izin'],
                            ['key' => 'wajah',  'icon' => 'bi-camera',         'label' => 'Wajah',   'url' => 'admin/presensi'],
                            ['key' => 'kehadiran','icon' => 'bi-person-check', 'label' => 'Kehadiran', 'url' => 'admin/presensi/verifikasi'],
                        ];
                        foreach ($pendingItems as $pi):
                            if (empty($pending[$pi['key']])) continue; ?>
                        <a href="{{ url($pi['url']) }}"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-100 hover:bg-amber-200 border border-amber-200 text-amber-800 text-[11px] font-semibold no-underline transition-colors">
                            <i class="bi {{ $pi['icon'] }}"></i>
                            {{ $pi['label'] }}
                            <span class="bg-amber-600 text-white rounded-full px-1.5 py-0.5 text-[9px] font-extrabold">
                                {{ (int)$pending[$pi['key']] }}
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════
         MAIN BODY
    ══════════════════════════════════════════════════ -->
    <div class="p-6 space-y-6">

        <!-- Row 1: Statistik Kehadiran Guru & Siswa -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- ── Panel Statistik Guru (1/2) ───────── -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6">
                <!-- Header -->
                <div class="flex items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-100">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 ic-box shrink-0">
                            <i class="bi bi-person-video3 text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-slate-800 leading-none">Statistik Guru</h2>
                            <p class="text-[11px] text-slate-400 mt-0.5 truncate">
                                {{ $isHariSekolah ? 'Kehadiran hari ini · ' . buat_tanggal(date('d F Y')) : $todayLabel . ' · ' . $liburLabel }}
                            </p>
                        </div>
                    </div>
                    <a href="{{ url($isHariSekolah ? 'admin/presensi' : 'admin/presensi/rekap') }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-50 text-emerald-700 rounded-xl hover:bg-emerald-100 transition-colors text-xs font-bold no-underline shrink-0">
                        Detail <i class="bi bi-arrow-right text-xs"></i>
                    </a>
                </div>

                <?php if ($isHariSekolah): ?>
                <!-- Ring: tingkat kehadiran -->
                <div class="flex items-center gap-5">
                    <div class="relative shrink-0" style="width:104px;height:104px">
                        <svg viewBox="0 0 36 36" class="w-full h-full" aria-hidden="true">
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#f1f5f9" stroke-width="3.4"></circle>
                            <circle class="donut-ring text-emerald-500" cx="18" cy="18" r="15.9155" fill="none" stroke="currentColor" stroke-width="3.4" transform="rotate(-90 18 18)" style="--pct: {{ $persen }}"></circle>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-xl font-extrabold text-slate-800 tabular leading-none">{{ $persen }}%</span>
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mt-1">Hadir</span>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 leading-none">Hadir / Total Guru</p>
                        <p class="text-3xl font-extrabold text-slate-800 tabular leading-none mt-2">
                            {{ (int)($ph['hadir'] ?? 0) }}
                            <span class="text-base font-bold text-slate-300">/ {{ (int)$ph['total_guru'] }}</span>
                        </p>
                        <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden mt-3">
                            <div class="h-full rounded-full bg-emerald-500 transition-all duration-700" style="width: {{ $persen }}%"></div>
                        </div>
                        <p class="flex items-center gap-1.5 text-[10px] font-medium text-slate-400 mt-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                            Tingkat kehadiran guru hari ini
                        </p>
                    </div>
                </div>

                <?php
                $gTotal = max(1, (int)$ph['total_guru']);
                $gIzin  = (int)($ph['izin'] ?? 0) + (int)($ph['sakit'] ?? 0) + (int)($ph['cuti'] ?? 0) + (int)($ph['wfh'] ?? 0) + (int)($ph['dinas'] ?? 0);
                $gChips = [
                    ['label' => 'Tepat Waktu', 'n' => (int)($ph['ontime'] ?? 0),            'box' => 'bg-emerald-50 border border-emerald-200/70', 'txt' => 'text-emerald-700', 'sub' => 'text-emerald-600', 'dot' => 'bg-emerald-500'],
                    ['label' => 'Terlambat',   'n' => (int)($ph['telat'] ?? 0),             'box' => 'bg-amber-50 border border-amber-200/70',     'txt' => 'text-amber-700',  'sub' => 'text-amber-600',   'dot' => 'bg-amber-500'],
                    ['label' => 'Izin · Dinas','n' => $gIzin,                               'box' => 'bg-sky-50 border border-sky-200/70',         'txt' => 'text-sky-700',    'sub' => 'text-sky-600',     'dot' => 'bg-sky-500'],
                    ['label' => 'Belum Absen', 'n' => (int)($ph['belum_absen'] ?? 0),       'box' => 'bg-rose-50 border border-rose-200/70',       'txt' => 'text-rose-700',   'sub' => 'text-rose-600',    'dot' => 'bg-rose-500'],
                ];
                ?>
                <!-- Breakdown -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 pt-5 border-t border-slate-100">
                    <?php foreach ($gChips as $gc): ?>
                    <div class="stat-chip rounded-2xl p-3 {{ $gc['box'] }}">
                        <div class="flex items-center gap-1.5 mb-1.5 min-w-0">
                            <span class="w-1.5 h-1.5 rounded-full {{ $gc['dot'] }} shrink-0"></span>
                            <span class="text-[10px] font-bold uppercase tracking-wide truncate {{ $gc['txt'] }}">{{ $gc['label'] }}</span>
                        </div>
                        <p class="text-2xl font-extrabold tabular leading-none {{ $gc['txt'] }}">{{ $gc['n'] }}</p>
                        <p class="text-[10px] font-medium mt-1 {{ $gc['sub'] }}">{{ round(($gc['n'] / $gTotal) * 100) }}% dari total</p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <!-- Hari libur -->
                <div class="rounded-2xl border border-amber-200/80 bg-amber-50/60 p-5">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 ic-box shrink-0">
                            <i class="bi bi-calendar-x text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-800">Presensi guru tidak dihitung</p>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ $deskripsiLibur }}</p>
                            <a href="{{ url('admin/presensi/rekap') }}"
                                class="inline-flex items-center gap-1 mt-2.5 text-xs font-bold text-emerald-700 no-underline">
                                Buka Rekap Presensi <i class="bi bi-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                    <?php if ($nextSchoolDay): ?>
                    <div class="mt-4 pt-3 border-t border-amber-100 inline-flex items-center gap-1.5 text-[11px] font-semibold text-slate-500">
                        <i class="bi bi-calendar-check text-emerald-600"></i>
                        Hari sekolah berikutnya: <span class="text-slate-700">{{ $nextSchoolDay['label'] }}</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ── Panel Statistik Siswa (2/2) ──────── -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 flex flex-col">
                <div class="flex items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-100">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 ic-box shrink-0">
                            <i class="bi bi-mortarboard-fill text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-slate-800 leading-none">Statistik Siswa</h2>
                            <p class="text-[11px] text-slate-400 mt-0.5 truncate">
                                {{ $isHariSekolah ? 'Presensi harian · ' . buat_tanggal(date('d F Y')) : $todayLabel . ' · ' . $liburLabel }}
                            </p>
                        </div>
                    </div>
                    <a href="{{ url('admin/presensi/siswa/harian') }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-50 text-indigo-700 rounded-xl hover:bg-indigo-100 transition-colors text-xs font-bold no-underline shrink-0">
                        Detail <i class="bi bi-arrow-right text-xs"></i>
                    </a>
                </div>

                <?php if ($isHariSekolah): ?>
                <!-- Ring: tingkat kehadiran -->
                <div class="flex items-center gap-5">
                    <div class="relative shrink-0" style="width:104px;height:104px">
                        <svg viewBox="0 0 36 36" class="w-full h-full" aria-hidden="true">
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#f1f5f9" stroke-width="3.4"></circle>
                            <circle class="donut-ring text-indigo-500" cx="18" cy="18" r="15.9155" fill="none" stroke="currentColor" stroke-width="3.4" transform="rotate(-90 18 18)" style="--pct: {{ $persenSiswa }}"></circle>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-xl font-extrabold text-slate-800 tabular leading-none">{{ $persenSiswa }}%</span>
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mt-1">Hadir</span>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 leading-none">Hadir / Total Siswa</p>
                        <p class="text-3xl font-extrabold text-slate-800 tabular leading-none mt-2">
                            {{ (int)($sp['hadir'] ?? 0) + (int)($sp['telat'] ?? 0) }}
                            <span class="text-base font-bold text-slate-300">/ {{ (int)$sp['total_siswa'] }}</span>
                        </p>
                        <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden mt-3">
                            <div class="h-full rounded-full bg-indigo-500 transition-all duration-700" style="width: {{ $persenSiswa }}%"></div>
                        </div>
                        <p class="flex items-center gap-1.5 text-[10px] font-medium text-slate-400 mt-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0"></span>
                            Tingkat kehadiran siswa hari ini
                        </p>
                    </div>
                </div>

                <?php
                $sHadir  = (int)($sp['hadir'] ?? 0) + (int)($sp['telat'] ?? 0);
                $sTelat  = (int)($sp['telat'] ?? 0);
                $sIzinGr = (int)($sp['izin'] ?? 0) + (int)($sp['sakit'] ?? 0) + (int)($sp['bdr'] ?? 0);
                $sBdr    = (int)($sp['bdr'] ?? 0);
                $sAlpa   = (int)($sp['alpa'] ?? 0) + (int)($sp['tanpa_ket'] ?? 0);
                $sTanpa  = (int)($sp['tanpa_ket'] ?? 0);
                $sChips = [
                    ['label' => 'Hadir', 'n' => $sHadir,   'box' => 'bg-emerald-50 border border-emerald-200/70', 'txt' => 'text-emerald-700', 'sub' => 'text-emerald-600', 'dot' => 'bg-emerald-500', 'info' => $sTelat > 0 ? '+' . $sTelat . ' telat' : 'Scan & face recog.'],
                    ['label' => 'Izin · Sakit', 'n' => $sIzinGr, 'box' => 'bg-sky-50 border border-sky-200/70',  'txt' => 'text-sky-700',    'sub' => 'text-sky-600',     'dot' => 'bg-sky-500',     'info' => $sBdr > 0 ? 'termasuk ' . $sBdr . ' BDR' : 'Pengajuan terverifikasi'],
                    ['label' => 'Alpa', 'n' => $sAlpa,       'box' => 'bg-rose-50 border border-rose-200/70',       'txt' => 'text-rose-700',   'sub' => 'text-rose-600',    'dot' => 'bg-rose-500',    'info' => $sTanpa > 0 ? $sTanpa . ' tanpa ket.' : 'Tanpa keterangan masuk'],
                    ['label' => 'Belum Absen', 'n' => (int)($sp['belum_absen'] ?? 0), 'box' => 'bg-amber-50 border border-amber-200/70', 'txt' => 'text-amber-700', 'sub' => 'text-amber-600', 'dot' => 'bg-amber-500', 'info' => 'dari ' . (int)$sp['total_siswa'] . ' siswa'],
                ];
                ?>
                <!-- Breakdown -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 pt-5 border-t border-slate-100">
                    <?php foreach ($sChips as $sc): ?>
                    <div class="stat-chip rounded-2xl p-3 {{ $sc['box'] }}">
                        <div class="flex items-center gap-1.5 mb-1.5 min-w-0">
                            <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }} shrink-0"></span>
                            <span class="text-[10px] font-bold uppercase tracking-wide truncate {{ $sc['txt'] }}">{{ $sc['label'] }}</span>
                        </div>
                        <p class="text-2xl font-extrabold tabular leading-none {{ $sc['txt'] }}">{{ $sc['n'] }}</p>
                        <p class="text-[10px] font-medium mt-1 truncate {{ $sc['sub'] }}">{{ $sc['info'] }}</p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <!-- Hari libur -->
                <div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-5">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-600 ic-box shrink-0">
                            <i class="bi bi-mortarboard text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-800">Presensi siswa tidak dihitung</p>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ $deskripsiLibur }}</p>
                            <a href="{{ url('admin/presensi/siswa/harian') }}"
                                class="inline-flex items-center gap-1 mt-2.5 text-xs font-bold text-indigo-700 no-underline">
                                Buka Monitoring Presensi Siswa <i class="bi bi-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Row 1.5: Akses Cepat (strip horizontal) -->
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                <i class="bi bi-grid-fill text-[9px]"></i> Akses Cepat
            </p>
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4">
                <?php
                $qaLinks = [
                    ['url' => 'admin/presensi',              'icon' => 'bi-speedometer2',       'ic' => 'text-blue-600',   'icbg' => 'bg-blue-50',    'label' => 'Presensi Guru'],
                    ['url' => 'admin/presensi/siswa/harian', 'icon' => 'bi-people-fill',        'ic' => 'text-emerald-600','icbg' => 'bg-emerald-50', 'label' => 'Presensi Siswa'],
                    ['url' => 'admin/jurnal',                'icon' => 'bi-journal-check',      'ic' => 'text-purple-600', 'icbg' => 'bg-purple-50',  'label' => 'Jurnal Mengajar'],
                    ['url' => 'admin/piket',                 'icon' => 'bi-shield-check',       'ic' => 'text-amber-600',  'icbg' => 'bg-amber-50',   'label' => 'Piket Guru'],
                    ['url' => 'scanner',                     'icon' => 'bi-display',            'ic' => 'text-teal-600',   'icbg' => 'bg-teal-50',    'label' => 'Scanner QR Siswa'],
                    ['url' => 'admin/presensi/rekap',        'icon' => 'bi-file-earmark-spreadsheet', 'ic' => 'text-rose-600', 'icbg' => 'bg-rose-50', 'label' => 'Rekap Presensi'],
                ];
                foreach ($qaLinks as $qa): ?>
                <a href="{{ url($qa['url']) }}"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-700 text-xs font-semibold whitespace-nowrap no-underline transition-colors">
                    <span class="w-5 h-5 rounded-lg {{ $qa['icbg'] }} {{ $qa['ic'] }} ic-box shrink-0">
                        <i class="bi {{ $qa['icon'] }} text-[11px]"></i>
                    </span>
                    <span>{{ $qa['label'] }}</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Row 2: Check-in Terbaru + Aktivitas Log -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- ── Check-in Guru Terbaru ─────────────── -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-5 sm:p-6 flex flex-col">
                <div class="flex items-center justify-between gap-3 pb-4 mb-1 border-b border-slate-100">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 ic-box shrink-0">
                            <i class="bi bi-fingerprint text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-slate-800 leading-none">{{ $isHariSekolah ? 'Check-in Guru Terbaru' : 'Check-in Guru Terakhir' }}</h2>
                            <p class="text-[11px] text-slate-400 mt-1">
                                <?php if ($isHariSekolah): ?>
                                    Absensi masuk hari ini &middot; {{ buat_tanggal(date('d F Y')) }}
                                <?php elseif ($checkinDateLabel): ?>
                                    Data terakhir &middot; {{ $checkinDateLabel }}
                                <?php else: ?>
                                    Belum ada data check-in tercatat
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <a href="{{ url('admin/presensi') }}"
                        class="inline-flex items-center gap-1 pl-3 pr-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-100 rounded-full text-emerald-700 text-xs font-bold no-underline transition-colors shrink-0">
                        Semua ({{ $isHariSekolah ? (int)$ph['hadir'] : $checkinCount }}) <i class="bi bi-chevron-right text-[10px]"></i>
                    </a>
                </div>

                <div class="flex-1">
                    <?php if (!empty($recentPresensi) && count($recentPresensi) > 0): ?>
                    <?php
                    // Helper: jam_masuk tersimpan sebagai DATETIME ("2026-09-15 07:05:00"),
                    // jadi harus di-parse, bukan di-substr langsung.
                    $formatJam = function ($val) {
                        if (empty($val) || $val === '0000-00-00 00:00:00' || $val === '0000-00-00') return '--:--';
                        $ts = strtotime($val);
                        if ($ts !== false) return date('H:i', $ts);
                        // fallback: ambil segmen HH:MM terakhir (mis. "07:05:00" / "2026-.. 07:05")
                        if (preg_match('/(\d{1,2}:\d{2})/', (string)$val, $m)) return substr($m[1], 0, 5);
                        return '--:--';
                    };
                    $resolveFoto = function ($foto) {
                        $fallback = asset('assets/img/user.png');
                        if (empty($foto)) return $fallback;
                        $foto = trim((string)$foto);
                        if (preg_match('#^https?://#i', $foto)) return $foto;
                        // Foto bisa menunjuk file yang sudah hilang (mis. sisa import lama):
                        // jangan hasilkan URL yang pasti 404, pakai avatar bawaan.
                        $relatif = str_contains($foto, '/') ? ltrim($foto, '/') : 'uploads/profiles/' . $foto;
                        if (! file_exists(public_path($relatif))) return $fallback;
                        return url($relatif);
                    };
                    ?>
                    <div class="divide-y divide-slate-100 checkin-scroll max-h-[460px] overflow-y-auto -mx-1 px-1">
                        <?php foreach ($recentPresensi as $idx => $att):
                            $namaGuru = trim(($att->nama_guru ?? '') ?: (($att->first_name ?? '') . ' ' . ($att->last_name ?? '')));
                            if (!$namaGuru) $namaGuru = 'Guru';
                            $fotoUrl = $resolveFoto($att->foto ?? null);
                            $st = strtoupper(trim($att->status_masuk ?? ''));
                            [$bcls, $blbl, $dotCls, $ringCls] = match(true) {
                                $st === 'ONTIME'             => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'Tepat Waktu', 'bg-emerald-500', 'ring-emerald-100'],
                                $st === 'LATE'               => ['bg-amber-50 text-amber-700 border-amber-200',         'Terlambat',   'bg-amber-500',   'ring-amber-100'],
                                $st === 'WFH'                => ['bg-sky-50 text-sky-700 border-sky-200',               'WFH',         'bg-sky-500',     'ring-sky-100'],
                                str_contains($st, 'DINAS')   => ['bg-indigo-50 text-indigo-700 border-indigo-200',     'Dinas',       'bg-indigo-500',  'ring-indigo-100'],
                                $st === 'PENDING_VERIFICATION' => ['bg-slate-100 text-slate-600 border-slate-200',     'Verifikasi',  'bg-slate-400',   'ring-slate-200'],
                                default                      => ['bg-slate-100 text-slate-600 border-slate-200',       $st ? ucfirst(strtolower($st)) : '-', 'bg-slate-300', 'ring-slate-200'],
                            };
                            $jamTampil = $formatJam($att->jam_masuk ?? null);
                            $telatMnt = (int)($att->telat_menit ?? 0);
                        ?>
                        <div class="checkin-row flex items-center gap-3 py-3 px-2 -mx-2 rounded-2xl" title="{{ $namaGuru }}">
                            <div class="relative shrink-0">
                                <img src="{{ $fotoUrl }}" alt="{{ $namaGuru }}" loading="lazy"
                                    class="w-10 h-10 rounded-full object-cover ring-2 {{ $ringCls }} bg-slate-100"
                                    onerror="this.onerror=null;this.src='{{ asset('assets/img/user.png') }}'">
                                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full {{ $dotCls }} ring-2 ring-white"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-bold text-slate-800 truncate leading-tight">{{ $namaGuru }}</p>
                                <p class="text-[11px] text-slate-400 mt-1 truncate flex items-center gap-1.5 min-w-0">
                                    <?php if (!empty($att->nip)): ?>
                                        <span class="tabular font-medium text-slate-400 shrink-0">{{ $att->nip }}</span>
                                        <?php if (!empty($att->nama_lokasi_masuk)): ?>
                                            <span class="text-slate-200 shrink-0">&bull;</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($att->nama_lokasi_masuk)): ?>
                                        <span class="inline-flex items-center gap-1 truncate">
                                            <i class="bi bi-geo-alt shrink-0"></i>
                                            <span class="truncate">{{ $att->nama_lokasi_masuk }}</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1">
                                            <i class="bi bi-clock"></i> {{ $jamTampil }} WIB
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="text-right shrink-0 flex flex-col items-end gap-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $bcls }}">
                                    {{ $blbl }}
                                </span>
                                <span class="inline-flex items-center gap-1 text-[13px] font-extrabold text-slate-700 tabular leading-none">
                                    <i class="bi bi-clock text-[11px] text-slate-300"></i>{{ $jamTampil }}
                                </span>
                                <?php if ($st === 'LATE' && $telatMnt > 0): ?>
                                    <span class="text-[10px] font-semibold text-amber-600 leading-none">+{{ $telatMnt }} mnt</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <div class="w-12 h-12 rounded-2xl {{ $isHariSekolah ? 'bg-slate-100 text-slate-300' : 'bg-amber-50 text-amber-400' }} ic-box mb-3">
                            <i class="bi {{ $isHariSekolah ? 'bi-inbox' : 'bi-calendar-x' }} text-2xl"></i>
                        </div>
                        <p class="text-sm font-semibold text-slate-600">
                            {{ $isHariSekolah ? 'Belum ada presensi hari ini' : 'Belum ada riwayat check-in' }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            {{ $isHariSekolah
                                ? 'Data absensi guru akan muncul otomatis di sini'
                                : 'Data check-in akan tampil setelah ada presensi pada hari sekolah' }}
                        </p>
                        <?php if ($isHariSekolah): ?>
                            <a href="{{ url('admin/presensi/manual') }}"
                                class="inline-flex items-center gap-1.5 mt-3 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-xl text-xs font-semibold hover:bg-blue-100 transition-colors no-underline">
                                <i class="bi bi-plus-circle"></i> Input Manual
                            </a>
                        <?php else: ?>
                            <a href="{{ url('admin/presensi/rekap') }}"
                                class="inline-flex items-center gap-1.5 mt-3 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-xl text-xs font-semibold hover:bg-blue-100 transition-colors no-underline">
                                <i class="bi bi-file-earmark-bar-graph"></i> Buka Rekap Presensi
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="pt-3 border-t border-slate-100 mt-2 flex items-center justify-between gap-2">
                    <?php if ($isHariSekolah): ?>
                        <span class="flex items-center gap-1.5 text-[11px] text-slate-400">
                            <span class="relative w-1.5 h-1.5 rounded-full bg-emerald-500 inline-flex shrink-0">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            </span>
                            Diperbarui otomatis
                        </span>
                    <?php else: ?>
                        <span class="flex items-center gap-1.5 text-[11px] text-slate-400">
                            <i class="bi bi-clock-history text-slate-300"></i>
                            Data historis &middot; tidak diperbarui hari ini
                        </span>
                    <?php endif; ?>
                    <a href="{{ url('admin/presensi') }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 text-[11px] font-bold no-underline transition-colors">
                        Tabel Lengkap <i class="bi bi-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <!-- ── Aktivitas Sistem (Log) ─────────────── -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 flex flex-col">
                <div class="flex items-center justify-between pb-4 mb-1 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-cyan-50 text-cyan-600 ic-box">
                            <i class="bi bi-activity text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-800 leading-none">Aktivitas Sistem</h2>
                            <p class="text-[11px] text-slate-400 mt-0.5">Log pengguna terbaru</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-[10px] font-semibold">
                            <i class="bi bi-shield-check text-emerald-500"></i> Audit Trail
                        </span>
                        <?php if (!empty($recentLogs) && count($recentLogs) > 0): ?>
                        <button type="button" id="btnClearDashboardLogs"
                            class="w-7 h-7 inline-flex items-center justify-center rounded-xl bg-red-50 border border-red-200 text-red-500 hover:bg-red-100 hover:text-red-600 transition-colors"
                            title="Hapus seluruh log aktivitas">
                            <i class="bi bi-trash3 text-xs"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex-1">
                    <?php if (!empty($recentLogs) && count($recentLogs) > 0): ?>
                    <?php
                    // ── Peta hari & bulan Indonesia (mandiri, tanpa helper eksternal) ──
                    $logHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    $logBulan = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agt', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
                    $logBulanFull = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                    $todayKey = date('Y-m-d');
                    $yesterdayKey = date('Y-m-d', strtotime('-1 day'));
                    $lastDateKey = null;
                    $nLogs = count($recentLogs);
                    ?>
                    <div id="dashboardLogsBody">
                        <?php foreach ($recentLogs as $idx => $log):
                            $uNama = trim(($log->first_name ?? '') . ' ' . ($log->last_name ?? '')) ?: 'Pengguna';
                            $fotoLog = \App\Services\AvatarService::url($log->foto) ?? asset('assets/img/user.png');
                            $ts = !empty($log->waktu) ? strtotime($log->waktu) : false;
                            $dateKey = $ts ? date('Y-m-d', $ts) : '';
                            $waktuLog = $ts ? date('H:i', $ts) : '';
                            $logId = (int)($log->id_log ?? 0);

                            // ── Label relatif + tanggal Bahasa Indonesia ──
                            $isToday = ($dateKey !== '' && $dateKey === $todayKey);
                            $isYesterday = ($dateKey !== '' && $dateKey === $yesterdayKey);
                            if ($ts) {
                                $wIdx = (int)date('w', $ts);
                                $mIdx = (int)date('n', $ts);
                                $tglFull = $logHari[$wIdx] . ', ' . date('j', $ts) . ' ' . $logBulanFull[$mIdx] . ' ' . date('Y', $ts);
                                $tglShort = date('j', $ts) . ' ' . $logBulan[$mIdx] . ' ' . date('Y', $ts);
                                $relLabel = $isToday ? 'Hari ini' : ($isYesterday ? 'Kemarin' : $logHari[$wIdx]);
                            } else {
                                $tglFull = '';
                                $tglShort = '';
                                $relLabel = '';
                            }

                            // ── Header grup: tampil tiap tanggal berganti ──
                            $showDateHeader = ($dateKey !== '' && $dateKey !== $lastDateKey);
                            $lastDateKey = $dateKey;

                            // ── Garis konektor timeline hanya ke log berikutnya di tanggal yang sama ──
                            $nextSameDate = false;
                            if ($idx < $nLogs - 1 && $ts) {
                                $nextWaktu = $recentLogs[$idx + 1]->waktu ?? null;
                                $nextTs = $nextWaktu ? strtotime($nextWaktu) : false;
                                $nextSameDate = ($nextTs && date('Y-m-d', $nextTs) === $dateKey);
                            }
                        ?>
                        <?php if ($showDateHeader): ?>
                        <div class="flex items-center gap-2 pt-3 first:pt-0">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $isToday ? 'bg-emerald-50 text-emerald-600' : ($isYesterday ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-500') }}">
                                {{ $relLabel }}
                            </span>
                            <span class="text-[10px] font-medium text-slate-400">{{ $tglFull }}</span>
                            <span class="flex-1 border-t border-dashed border-slate-200"></span>
                        </div>
                        <?php endif; ?>
                        <div id="dashboard-log-{{ $logId }}" class="group flex items-start gap-3 py-3 border-b border-slate-50 last:border-b-0 relative {{ $nextSameDate ? 'tl-connector' : '' }}">
                            <img src="{{ $fotoLog }}" alt=""
                                class="w-8 h-8 rounded-full object-cover ring-1 ring-slate-200 shrink-0 mt-0.5 relative z-10"
                                onerror="this.src='{{ asset('assets/img/user.png') }}'">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-800 leading-none">{{ $uNama }}</p>
                                <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed break-words">
                                    {{ $log->keterangan ?? 'Melakukan aksi di sistem' }}
                                </p>
                            </div>
                            <?php if ($waktuLog): ?>
                            <div class="text-right shrink-0 mt-0.5" title="{{ $tglFull . ' • ' . date('H:i:s', $ts) }}">
                                <p class="text-xs font-bold text-slate-700 leading-none tabular">{{ $waktuLog }}</p>
                                <?php if (!$isToday): ?>
                                <p class="text-[10px] text-slate-400 mt-1 leading-none">{{ $isYesterday ? 'Kemarin' : $tglShort }}</p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($logId > 0): ?>
                            <button type="button" class="btn-delete-dashboard-log shrink-0 w-6 h-6 inline-flex items-center justify-center rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 transition-colors sm:opacity-0 sm:group-hover:opacity-100 mt-0.5"
                                data-id="{{ $logId }}" title="Hapus log ini">
                                <i class="bi bi-x-lg text-[10px]"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-300 ic-box mb-3">
                            <i class="bi bi-clock-history text-2xl"></i>
                        </div>
                        <p class="text-sm font-semibold text-slate-600">Belum ada aktivitas tercatat</p>
                        <p class="text-xs text-slate-400 mt-1">Log sistem akan muncul saat ada aktivitas</p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="pt-3 border-t border-slate-100 mt-3 flex items-center justify-between gap-2">
                    <p class="text-[11px] text-slate-400">Audit trail tercatat secara aman untuk transparansi sistem</p>
                    <?php if (!empty($recentLogs) && count($recentLogs) > 0): ?>
                    <button type="button" id="btnClearDashboardLogsFooter"
                        class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-400 hover:text-red-500 transition-colors shrink-0">
                        <i class="bi bi-trash3 text-[10px]"></i> Hapus Semua
                    </button>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    function dashboardCsrf() { const d = {}; d[CSRF_NAME] = CSRF_HASH; return d; }

    // ── Hapus satu log ──────────────────────────────────
    $(document).on('click', '.btn-delete-dashboard-log', function () {
        const id = $(this).data('id');
        const $row = $('#dashboard-log-' + id);
        $.post('{{ url('dashboard/hapuslog') }}/' + encodeURIComponent(id), dashboardCsrf(), function (res) {
            if (res && res.status) {
                $row.fadeOut(250, function () {
                    $(this).remove();
                    if ($('#dashboardLogsBody > div[id^="dashboard-log-"]').length === 0) location.reload();
                });
            } else {
                Swal.fire('Gagal', (res && res.message) || 'Terjadi kesalahan', 'error');
            }
        }, 'json').fail(function () {
            Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
        });
    });

    // ── Hapus seluruh log ───────────────────────────────
    function clearDashboardLogs() {
        Swal.fire({
            title: 'Hapus Seluruh Log?',
            text: 'Semua riwayat aktivitas pengguna akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Ya, Hapus Semua',
            cancelButtonText: 'Batal'
        }).then((r) => {
            if (!r.isConfirmed) return;
            $.post('{{ url('dashboard/hapuslog') }}', dashboardCsrf(), function (res) {
                if (res && res.status) {
                    Swal.fire({ icon: 'success', title: 'Terhapus', text: res.message, timer: 1600, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Terjadi kesalahan', 'error');
                }
            }, 'json').fail(function () {
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            });
        });
    }
    $(document).on('click', '#btnClearDashboardLogs, #btnClearDashboardLogsFooter', clearDashboardLogs);
});
</script>
@endsection
