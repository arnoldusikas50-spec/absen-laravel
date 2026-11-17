@extends('layouts.main')

@section('styles')
<style>
    .card-preview {
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .card-preview:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 28px -6px rgba(107, 33, 168, 0.15), 0 4px 10px -2px rgba(15, 23, 42, 0.06);
    }
    .qr-box canvas, .qr-box img {
        margin: 0 auto;
        display: block;
    }
    .card-header-gtk-landscape {
        background: linear-gradient(135deg, var(--accent-900) 0%, var(--purple-800) 50%, var(--indigo-700) 100%) !important;
        color: #ffffff !important;
        padding: 12px 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 2px solid var(--accent-500);
    }
    .card-header-gtk-portrait {
        background: linear-gradient(180deg, var(--accent-900) 0%, var(--accent-600) 55%, #581c87 100%) !important;
        color: #ffffff !important;
        border-bottom: 2px solid var(--accent-500);
    }
    .badge-gtk-glass {
        background: rgba(255, 255, 255, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.4) !important;
        color: #ffffff !important;
    }

    /* Duplex pair: kartu depan + belakang berdampingan di preview */
    .guru-duplex-pair {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        justify-content: center;
    }

    /* ═══════════════════════════════════════════════════════════════════
       KARTU GURU PORTRAIT — Base class (dipakai depan & belakang)
    ═══════════════════════════════════════════════════════════════════ */
    .id-card-portrait {
        width: 240px;
        height: 380px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: linear-gradient(165deg, #f5fbff 0%, #e8f2fc 55%, #d8eafa 100%);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        position: relative;
        box-shadow: 0 10px 24px -14px rgba(15, 23, 42, 0.35);
    }

    /* ═══════════════════════════════════════════════════════════════════
       KARTU GURU BELAKANG (preview)
    ═══════════════════════════════════════════════════════════════════ */
    .id-card-guru-back {
        padding: 8px 14px 40px 14px;
    }
    /* Dekorasi bersama dua sisi: lingkaran biru kiri atas & ombak bawah */
    .gdeco-circle {
        position: absolute;
        top: -62px;
        left: -70px;
        width: 190px;
        height: 190px;
        z-index: 0;
        pointer-events: none;
    }
    .gdeco-wave {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 34px;
        z-index: 0;
        pointer-events: none;
    }
    /* Kop bersama dua sisi: logo + dinas + sekolah + alamat */
    .gkop-header {
        position: relative;
        z-index: 1;
        text-align: center;
        flex-shrink: 0;
    }
    .gkop-logo {
        width: 46px;
        height: 46px;
        margin: 0 auto 3px auto;
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
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.4px;
        color: #16325c;
        margin: 0;
        line-height: 1.1;
    }
    .gkop-school {
        font-size: 10.5px;
        font-weight: 900;
        color: #16325c;
        margin: 2px 0 0 0;
        line-height: 1.15;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: clip;
        width: 100%;
        display: block;
        letter-spacing: -0.3px;
    }
    .gkop-address {
        font-size: 8px;
        font-weight: 700;
        color: #1f3c66;
        margin: 3px 0 0 0;
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: clip;
        width: 100%;
        display: block;
        letter-spacing: -0.2px;
    }
    /* Pil judul kartu dengan garis di kedua sisi */
    .gpill-row {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 6px;
        width: 100%;
        margin-top: 6px;
    }
    .gpill-line {
        flex: 1;
        height: 3px;
        background: #1e56c8;
        border-radius: 3px;
    }
    .gpill {
        background: #1e56c8;
        color: #ffffff;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.5px;
        padding: 4px 13px;
        border-radius: 7px;
        white-space: nowrap;
        line-height: 1;
        flex-shrink: 0;
    }
    /* Garis ganda biru di bawah kop sisi belakang */
    .gback-divider {
        position: relative;
        z-index: 1;
        width: 100%;
        margin-top: 5px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .gback-divider span {
        display: block;
        height: 2.5px;
        background: #1e56c8;
        border-radius: 2px;
    }
    .gback-table {
        position: relative;
        z-index: 1;
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 10px;
        line-height: 1.3;
        margin-top: 5px;
    }
    .gback-table td {
        padding: 1.5px 0;
        vertical-align: top;
    }
    .gback-table .lbl {
        width: 64px;
        font-weight: 800;
        color: #16325c;
        white-space: nowrap;
    }
    .gback-table .sep {
        width: 12px;
        text-align: center;
        font-weight: 800;
        color: #16325c;
    }
    .gback-table .val {
        padding-left: 2px;
        font-weight: 800;
        color: #16325c;
        word-break: break-word;
    }
    .gback-qr {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: center;
        margin-top: 5px;
    }
    .gback-qr-box {
        width: 90px;
        height: 90px;
        background: #ffffff;
        border: 3px solid #2f7dd1;
        border-radius: 8px;
        padding: 4px;
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
        font-size: 9px;
        color: #1e56c8;
        text-align: center;
        line-height: 1.25;
        margin: 4px 0 0 0;
    }

    /* ═══════════════════════════════════════════════════════════════════
       KARTU GURU DEPAN (preview, disesuaikan skala layar)
    ═══════════════════════════════════════════════════════════════════ */
    .id-card-guru-front {
        align-items: center;
        padding: 10px 12px 34px 12px;
    }
    /* Sisi depan: foto merah berbingkai biru + nama bergaris bawah + NIP */
    .gfront-photo {
        position: relative;
        z-index: 1;
        width: 104px;
        height: 124px;
        margin: 8px auto 0 auto;
        background: #d92b2b;
        border: 3px solid #2f7dd1;
        border-radius: 9px;
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
        margin-top: 8px;
        width: 100%;
    }
    .gfront-name {
        font-size: 12.5px;
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
        height: 2.5px;
        background: #16325c;
        border-radius: 2px;
        width: 168px;
        margin: 5px auto 0 auto;
    }
    .gfront-nip {
        font-size: 10px;
        font-weight: 800;
        color: #16325c;
        margin: 4px 0 0 0;
        letter-spacing: 0.2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
@endsection

@section('content')
<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-purple-50 text-purple-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-person-badge-fill"></i> Kartu Presensi Guru / GTK
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Cetak Kartu Absen Guru QR</h1>
            <p class="text-xs text-slate-500 mt-1">Cetak kartu ID presensi Guru & Tenaga Kependidikan dengan QR Code standar untuk scan absensi mandiri di scanner kiosk.</p>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <?php $basePath = $base_path ?? 'admin/presensi/guru/kartu'; ?>
            <?php $desainAktif = $desain ?? 'landscape'; ?>
            <?php $modeAktif = in_array(($mode ?? 'grid'), ['grid', 'ktp'], true) ? ($mode ?? 'grid') : 'grid'; ?>
            <a id="btnCetakLembar"
               href="{{ url($basePath . '/cetak?' . ($id_guru ? 'id_guru=' . $id_guru . '&' : '') . 'desain=' . $desainAktif . '&mode=' . $modeAktif . '&v=1') }}"
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm shadow-purple-500/20 no-underline">
                <i class="bi bi-printer-fill"></i> Cetak Kartu
            </a>
            <a href="{{ url('scanner') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-camera-fill"></i> Buka Scanner QR
            </a>
        </div>
    </div>

    <!-- Filter & Control Panel -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <?php
        // Bawa serta mode cetak saat berganti desain agar pilihannya tidak ter-reset.
        $qsSertaan = '&mode=' . $modeAktif;
        ?>
        <!-- Judul Panel Opsi -->
        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-100">
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-purple-50 text-purple-600 shrink-0">
                <i class="bi bi-sliders"></i>
            </span>
            <div class="min-w-0">
                <p class="text-sm font-extrabold text-slate-800 leading-tight">Opsi Cetak Kartu</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Pilih desain kartu dan tata letak cetak sebelum mencetak.</p>
            </div>
        </div>

        <div class="space-y-5">
        <!-- Pilihan Desain Kartu -->
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Desain Kartu</label>
            <div class="inline-flex p-1 bg-slate-100 rounded-xl gap-1 flex-wrap" role="tablist" aria-label="Pilihan desain kartu">
                <a href="{{ url($basePath . '?' . ($id_guru ? 'id_guru=' . $id_guru . '&' : '') . 'desain=landscape' . $qsSertaan) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $desainAktif === 'landscape' ? 'bg-white text-purple-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/70' }}">
                    <i class="bi bi-credit-card-2-front"></i> Landscape
                </a>
                <a href="{{ url($basePath . '?' . ($id_guru ? 'id_guru=' . $id_guru . '&' : '') . 'desain=portrait' . $qsSertaan) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $desainAktif === 'portrait' ? 'bg-white text-purple-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/70' }}">
                    <i class="bi bi-credit-card"></i> Portrait
                </a>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">
                {{ $desainAktif === 'portrait'
                    ? 'Portrait: kartu ID resmi 5,8 × 9,2 cm — sisi depan foto & nama guru, sisi belakang data lengkap dan QR Code absensi. Dicetak 2 sisi (depan + belakang).'
                    : 'Landscape: kartu horizontal resmi 9,2 × 5,8 cm — foto guru, NIP, jabatan, dan QR Code di samping kanan, sisi belakang data lengkap. Dicetak 2 sisi (depan + belakang).' }}
            </p>
        </div>

        <!-- Tata Letak Cetak -->
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tata Letak Cetak</label>
            <div class="inline-flex p-1 bg-slate-100 rounded-xl gap-1 flex-wrap" role="tablist" aria-label="Pilihan tata letak cetak">
                <a href="{{ url($basePath . '?' . ($id_guru ? 'id_guru=' . $id_guru . '&' : '') . 'desain=' . $desainAktif . '&mode=grid') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $modeAktif === 'grid' ? 'bg-white text-purple-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/70' }}">
                    <i class="bi bi-file-earmark-text"></i> Grid A4
                </a>
                <a href="{{ url($basePath . '?' . ($id_guru ? 'id_guru=' . $id_guru . '&' : '') . 'desain=' . $desainAktif . '&mode=ktp') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $modeAktif === 'ktp' ? 'bg-white text-purple-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-white/70' }}">
                    <i class="bi bi-credit-card-2-front-fill"></i> Ukuran KTP
                </a>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">
                {{ $modeAktif === 'ktp'
                    ? 'Ukuran KTP: kertas disetel otomatis ke kartu ID-1 CR80 (85,6 × 54 mm) sehingga bisa dicetak langsung ke blanko kartu / media Card Size pada printer Epson.'
                    : 'Grid A4: beberapa kartu per lembar A4, hemat kertas dan mudah dipotong.' }}
            </p>
        </div>
        </div>

        <hr class="my-5 border-slate-100">

        <!-- Filter Data -->
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Filter Data Guru</label>
        <form method="get" action="{{ url($basePath) }}" id="filterForm" data-base="{{ url($basePath) }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <input type="hidden" name="desain" id="inputDesain" value="{{ $desainAktif }}">
            <input type="hidden" name="mode" id="inputMode" value="{{ $modeAktif }}">
            
            <!-- Pilih Guru (Per Orang / Semua) -->
            <div class="md:col-span-6">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Guru / GTK</label>
                <select name="id_guru" id="selectGuru" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all hover:border-slate-300">
                    <option value="0">-- Semua Guru / GTK ({{ count($guru_options) }} Guru) --</option>
                    <?php foreach ($guru_options as $go): ?>
                        <option value="{{ $go->id_guru }}" {{ ($id_guru == $go->id_guru) ? 'selected' : '' }}>
                            {{ $go->nama_guru }} ({{ !empty($go->nip) ? 'NIP: ' . esc($go->nip) : (!empty($go->kode_guru) ? 'Kode: ' . esc($go->kode_guru) : 'ID: ' . $go->id_guru) }})
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Pencarian Cepat Nama / NIP -->
            <div class="md:col-span-6">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Filter Pencarian</label>
                <div class="relative">
                    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="searchInput" placeholder="Cari nama, NIP, atau kode guru..."
                           class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all hover:border-slate-300">
                </div>
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
            <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-purple-200 bg-purple-50 px-3.5 py-3 text-xs text-purple-800">
                <i class="bi bi-info-circle-fill mt-0.5 text-purple-500"></i>
                <p class="font-semibold leading-relaxed">
                    Daftar dipotong pada {{ (int) ($batas_cetak ?? 500) }} kartu pertama supaya halaman tidak macet.
                    Pilih satu guru untuk mencetak sisanya.
                </p>
            </div>
        <?php endif; ?>

        <div class="flex items-center justify-between mt-5 pt-4 border-t border-slate-100 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center min-w-[22px] h-[22px] px-1.5 rounded-full bg-purple-50 text-purple-700 font-bold text-[11px] border border-purple-100">
                    {{ count($guru_list) }}
                </span>
                <span class="font-semibold text-slate-600">kartu guru ditampilkan</span>
                @if (!empty($terpotong))
                    <span class="text-slate-400">(dari daftar yang dipotong)</span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <i class="bi bi-building text-slate-400"></i>
                <span class="text-slate-400">Instansi</span>
                <strong class="text-slate-700">{{ $setting->sekolah ?? 'Sekolah' }}</strong>
            </div>
        </div>
    </div>

    <!-- Live Card Grid Preview -->
    <?php if (empty($guru_list)): ?>
        <div class="bg-white rounded-3xl p-12 text-center border border-slate-200/80">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 text-2xl mx-auto mb-3">
                <i class="bi bi-person-x"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800">Tidak Ada Data Guru</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                <?php if (!empty($catatan)): ?>
                    {{ $catatan }}
                <?php else: ?>
                    Silakan periksa master data guru di menu Data Guru.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <?php if ($desainAktif === 'portrait'): ?>
        <!-- Preview Portrait: kartu ID depan + belakang berdampingan -->
        <div id="cardGrid" class="grid grid-cols-1 xl:grid-cols-2 gap-6 justify-items-center">
            <?php foreach ($guru_list as $g): ?>
                <div class="teacher-card-item card-preview flex flex-col items-center gap-2"
                     data-name="{{ strtolower($g->nama_guru) }}"
                     data-nip="{{ strtolower($g->nip ?? '') }}"
                     data-kode="{{ strtolower($g->kode_guru ?? '') }}">
                    <div class="guru-duplex-pair">
                        <div class="flex flex-col items-center gap-1.5">
                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400">Sisi Depan</span>
                            @include('admin.presensi.kartu.partials.kartu_guru_portrait')
                        </div>
                        <div class="flex flex-col items-center gap-1.5">
                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400">Sisi Belakang</span>
                            @include('admin.presensi.kartu.partials.kartu_guru_belakang')
                        </div>
                    </div>
                    <a href="{{ url($basePath . '/cetak?id_guru=' . $g->id_guru . '&desain=portrait&mode=' . $modeAktif . '&v=1') }}"
                       target="_blank"
                       class="inline-flex items-center gap-1.5 font-bold text-purple-600 hover:text-purple-700 bg-white hover:bg-purple-50 px-2.5 py-1 rounded-lg border border-slate-200 transition-colors no-underline text-[10px]">
                        <i class="bi bi-printer"></i> Cetak Kartu Ini
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- Preview Landscape: horizontal dengan foto -->
        <div id="cardGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($guru_list as $g): ?>
                <div class="teacher-card-item card-preview bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-xs relative flex flex-col"
                     data-name="{{ strtolower($g->nama_guru) }}"
                     data-nip="{{ strtolower($g->nip ?? '') }}"
                     data-kode="{{ strtolower($g->kode_guru ?? '') }}">

                    <!-- Card Header (Kop Kartu) -->
                    <div class="card-header-gtk-landscape" style="background:linear-gradient(135deg,var(--accent-900) 0%,var(--purple-800) 50%,var(--indigo-700) 100%);color:#fff;">
                        <div class="w-10 h-10 bg-white rounded-xl p-1 shrink-0 flex items-center justify-center shadow-xs">
                            <img src="{{ $logo_url }}" alt="Logo" class="max-h-full max-w-full object-contain"
                                 onerror="this.src='{{ asset('assets/img/logo.png') }}'">
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[8.5px] font-bold tracking-wider uppercase leading-none" style="color: #e9d5ff;">KARTU PRESENSI DIGITAL</p>
                            <h4 class="text-xs font-black text-white truncate mt-0.5 leading-snug" style="color: #ffffff;" title="{{ $setting->sekolah ?? 'SEKOLAH' }}">{{ $setting->sekolah ?? 'SEKOLAH' }}</h4>
                            <p class="text-[8.5px] font-medium leading-tight truncate mt-0.5" style="color: var(--accent-200);">{{ $setting->kota ?? '' }}{{ !empty($setting->kota) ? ' • ' : '' }}GTK DIGITAL</p>
                        </div>
                        <div class="shrink-0 badge-gtk-glass px-2.5 py-1 rounded-md text-[9px] font-extrabold uppercase tracking-wider text-white">
                            GURU / GTK
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 flex gap-3.5 items-center flex-1">
                        <!-- Foto Guru & Status Pegawai -->
                        <div class="flex flex-col items-center shrink-0 w-20">
                            <div class="w-20 h-24 rounded-xl overflow-hidden border-2 border-purple-100 shadow-xs bg-slate-50 flex items-center justify-center">
                                <img src="{{ $g->foto_url }}" alt="{{ $g->nama_guru }}"
                                     class="w-full h-full object-cover"
                                     onerror="this.onerror=null;this.src='{{ \App\Services\AvatarService::inisial($g->nama_guru, 128, '#7c3aed') }}'">
                            </div>
                            <?php
                            $sp = strtoupper(trim((string)($g->status_pegawai ?? '')));
                            $spClass = 'bg-purple-50 text-purple-700 border-purple-200';
                            if (strpos($sp, 'PNS') !== false) {
                                $spClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            } elseif (strpos($sp, 'PPPK') !== false) {
                                $spClass = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                            } elseif (strpos($sp, 'HONOR') !== false || strpos($sp, 'GTT') !== false || strpos($sp, 'PTT') !== false) {
                                $spClass = 'bg-amber-50 text-amber-700 border-amber-200';
                            }
                            ?>
                            <span class="mt-1.5 px-1.5 py-0.5 text-[8.5px] font-bold rounded-md border text-center w-full leading-tight {{ $spClass }}" title="{{ $g->status_pegawai ?: 'PENDIDIK' }}">
                                {{ $g->status_pegawai ?: 'PENDIDIK' }}
                            </span>
                        </div>

                        <!-- Info Guru -->
                        <div class="flex-1 min-w-0">
                            <h5 class="text-sm font-extrabold text-slate-900 leading-snug line-clamp-2" title="{{ $g->nama_guru }}">
                                {{ $g->nama_guru }}
                            </h5>
                            <div class="mt-2.5 space-y-1.5 text-xs">
                                <div class="flex items-center text-slate-600">
                                    <span class="w-14 shrink-0 text-[10px] font-bold text-slate-400 uppercase tracking-wider">NIP</span>
                                    <span class="font-mono font-bold text-slate-900 truncate">: {{ $g->nip ?: '-' }}</span>
                                </div>
                                <div class="flex items-center text-slate-600">
                                    <span class="w-14 shrink-0 text-[10px] font-bold text-slate-400 uppercase tracking-wider">NUPTK</span>
                                    <span class="font-mono text-slate-700 truncate">: {{ $g->nuptk ?: '-' }}</span>
                                </div>
                                <div class="flex items-center text-slate-600">
                                    <span class="w-14 shrink-0 text-[10px] font-bold text-slate-400 uppercase tracking-wider">TUGAS</span>
                                    <span class="font-semibold text-purple-700 truncate">: {{ $g->jabatan }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- QR Code Box -->
                        <div class="shrink-0 flex flex-col items-center justify-center pl-3 border-l border-slate-100">
                            <div class="qr-box p-1.5 bg-white border border-slate-200 rounded-xl shadow-2xs"
                                 data-code="{{ $g->qr_value }}"
                                 data-size="80"
                                 id="qr-preview-{{ $g->id_guru }}"
                                 style="width:92px;height:92px;display:flex;align-items:center;justify-content:center;"></div>
                            <span class="font-mono font-bold text-slate-800 text-[8.5px] mt-1 text-center tracking-tight break-all max-w-[95px]" title="{{ $g->qr_value }}">{{ $g->qr_value }}</span>
                            <span class="text-[7.5px] font-extrabold text-purple-600 uppercase tracking-wider mt-0.5">Scan Absen</span>
                        </div>
                    </div>

                    <!-- Card Footer & Single Print Button -->
                    <div class="px-4 py-2 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between text-[10px]">
                        <span class="text-slate-400 truncate max-w-[170px]">Gunakan scan di Kiosk Presensi</span>
                        <a href="{{ url($basePath . '/cetak?id_guru=' . $g->id_guru . '&desain=landscape&mode=' . $modeAktif . '&v=1') }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 font-bold text-purple-600 hover:text-purple-700 bg-white hover:bg-purple-50 px-2.5 py-1 rounded-lg border border-slate-200 transition-colors no-underline">
                            <i class="bi bi-printer"></i> Cetak Kartu Ini
                        </a>
                    </div>
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
    $('.qr-box').each(function() {
        const el = this;
        const code = $(el).data('code');
        const size = parseInt($(el).data('size') || '78', 10) || 78;
        if (code) {
            try {
                new QRCode(el, {
                    text: String(code),
                    width: size,
                    height: size,
                    colorDark: "#1e1b4b",
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

    function refreshCetakLink() {
        const idGuru = $('#selectGuru').val() || '0';
        const desain = $('#inputDesain').val() || 'landscape';
        const mode = $('#inputMode').val() || 'grid';
        let url = basePath + '/cetak?';
        if (idGuru !== '0') url += 'id_guru=' + encodeURIComponent(idGuru) + '&';
        url += 'desain=' + encodeURIComponent(desain) + '&mode=' + encodeURIComponent(mode) + '&v=1';
        $('#btnCetakLembar').attr('href', url);
    }

    // Auto submit filter saat ganti guru
    $('#selectGuru').on('change', function() {
        refreshCetakLink();
        $('#filterForm').submit();
    });

    // Realtime search filter nama / NIP / kode di preview grid
    $('#searchInput').on('keyup', function() {
        const query = $(this).val().toLowerCase().trim();
        $('.teacher-card-item').each(function() {
            // jQuery .data() mengubah string angka jadi Number, jadi .includes()
            // melempar TypeError dan membatalkan seluruh loop filter.
            // String() memastikan nilainya tetap teks.
            const name = String($(this).data('name') || '');
            const nip  = String($(this).data('nip') || '');
            const kode = String($(this).data('kode') || '');

            if (name.includes(query) || nip.includes(query) || kode.includes(query)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>
@endsection
