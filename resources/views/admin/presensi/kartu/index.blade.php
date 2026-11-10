@extends('layouts.main')

@section('styles')
<style>
    .card-preview {
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .card-preview:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 28px -6px rgb(var(--primary-700-rgb) / 0.15), 0 4px 10px -2px rgba(15, 23, 42, 0.06);
    }
    .qr-box canvas, .qr-box img {
        margin: 0 auto;
        display: block;
    }
    .card-header-siswa-landscape {
        background: linear-gradient(135deg, var(--primary-700) 0%, var(--indigo-700) 50%, var(--primary-800) 100%) !important;
        color: #ffffff !important;
        padding: 12px 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 2px solid var(--primary-500);
    }
    .badge-siswa-glass {
        background: rgba(255, 255, 255, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.4) !important;
        color: #ffffff !important;
    }
</style>
@endsection

@section('content')
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
            <?php $basePath = $base_path ?? 'admin/presensi/siswa/kartu'; ?>
            <?php $desainAktif = $desain ?? 'landscape'; ?>
            <a id="btnCetakLembar"
               href="{{ url($basePath . '/cetak?id_kelas=' . $id_kelas . ($id_siswa ? '&id_siswa=' . $id_siswa : '') . '&desain=' . $desainAktif . '&v=3') }}"
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
        <?php $desainAktif = $desain ?? 'landscape'; ?>
        <!-- Pilihan Desain Kartu -->
        <div class="mb-4">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Desain Kartu</label>
            <div class="inline-flex p-1 bg-slate-100 rounded-xl gap-1" role="tablist" aria-label="Pilihan desain kartu">
                <a href="{{ url(($base_path ?? 'admin/presensi/siswa/kartu') . '?id_kelas=' . $id_kelas . ($id_siswa ? '&id_siswa=' . $id_siswa : '') . '&desain=landscape&v=3') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $desainAktif === 'landscape' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700' }}">
                    <i class="bi bi-credit-card-2-front"></i> Landscape
                    <span class="hidden sm:inline font-semibold {{ $desainAktif === 'landscape' ? 'text-blue-500' : 'text-slate-400' }}">• Kartu Pelajar</span>
                </a>
                <a href="{{ url(($base_path ?? 'admin/presensi/siswa/kartu') . '?id_kelas=' . $id_kelas . ($id_siswa ? '&id_siswa=' . $id_siswa : '') . '&desain=portrait&v=3') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all no-underline {{ $desainAktif === 'portrait' ? 'bg-white text-blue-700 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-700' }}">
                    <i class="bi bi-credit-card"></i> Portrait
                    <span class="hidden sm:inline font-semibold {{ $desainAktif === 'portrait' ? 'text-blue-500' : 'text-slate-400' }}">• Tanpa Foto</span>
                </a>
            </div>
            <p class="text-[11px] text-slate-400 mt-1.5">
                {{ $desainAktif === 'portrait'
                    ? 'Portrait: kartu vertikal tanpa foto siswa, QR besar di tengah — hemat tinta & cocok untuk kalung ID.'
                    : 'Landscape: kartu pelajar horizontal dengan foto, identitas lengkap, QR absen, dan garis tanda tangan.' }}
            </p>
        </div>
        <form method="get" action="{{ url($base_path ?? 'admin/presensi/siswa/kartu') }}" id="filterForm" data-base="{{ url($base_path ?? 'admin/presensi/siswa/kartu') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
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
                            {{ $so->nama }} (NIS: {{ $so->nis ?? '-' }})
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
        <?php $desainAktif = $desain ?? 'landscape'; ?>
        <?php if ($desainAktif === 'portrait'): ?>
        <!-- Preview Portrait: vertikal, tanpa foto, QR besar — TIDAK DIUBAH -->
        <div id="cardGrid" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-6 justify-items-center">
            <?php foreach ($siswa_list as $s): ?>
                <div class="student-card-item card-preview bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-xs relative flex flex-col w-full max-w-[220px]"
                     data-name="{{ strtolower(esc($s->nama)) }}"
                     data-nis="{{ strtolower(esc($s->nis ?? '')) }}"
                     data-nisn="{{ strtolower(esc($s->nisn ?? '')) }}">
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
                        <p class="text-[10px] text-slate-500 mt-0.5 font-mono">NISN {{ $s->nisn ?: '-' }}</p>
                        <span class="inline-block mt-1 px-2 py-0.5 text-[9px] font-bold rounded-full {{ ($s->jenis_kelamin == 'L') ? 'bg-blue-50 text-blue-700' : 'bg-pink-50 text-pink-700' }}">
                            {{ ($s->jenis_kelamin == 'L') ? 'LAKI-LAKI' : 'PEREMPUAN' }}
                        </span>
                    </div>
                    <div class="px-3 py-2 bg-slate-50/80 border-t border-slate-100 flex items-center justify-center">
                        <a href="{{ url(($base_path ?? 'admin/presensi/siswa/kartu') . '/cetak?id_siswa=' . $s->id_siswa . '&id_kelas=' . $s->id_kelas . '&desain=portrait&v=3') }}"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 font-bold text-blue-600 hover:text-blue-700 bg-white hover:bg-blue-50 px-2.5 py-1 rounded-lg border border-slate-200 transition-colors no-underline text-[10px]">
                            <i class="bi bi-printer"></i> Cetak Kartu Ini
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
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
                 data-name="{{ strtolower(esc($s->nama)) }}"
                 data-nis="{{ strtolower(esc($s->nis ?? '')) }}"
                 data-nisn="{{ strtolower(esc($s->nisn ?? '')) }}">

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
                                <span style="font-weight:600;color:#334155;font-size:8.5px;line-height:1.3;">{{ trim(($s->tempat_lahir ?? '') . ($s->tempat_lahir && $s->tanggal_lahir ? ', ' : '') . ($s->tanggal_lahir ? \Carbon\Carbon::parse($s->tanggal_lahir)->locale('id')->isoFormat('D MMM Y') : '')) ?: '-' }}</span>
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
                        <a href="{{ url(($base_path ?? 'admin/presensi/siswa/kartu') . '/cetak?id_siswa=' . $s->id_siswa . '&id_kelas=' . $s->id_kelas . '&desain=landscape&v=3') }}"
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
                    colorDark: "#0f172a",
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
        const idKelas = $('#selectKelas').val() || '0';
        const idSiswa = $('#selectSiswa').val() || '0';
        const desain  = $('#inputDesain').val() || 'landscape';
        let url = basePath + '/cetak?id_kelas=' + encodeURIComponent(idKelas);
        if (idSiswa !== '0') url += '&id_siswa=' + encodeURIComponent(idSiswa);
        url += '&desain=' + encodeURIComponent(desain);
        url += '&v=3';
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
                        $siswa.append('<option value="' + row.id_siswa + '">' + $('<div>').text(row.nama).html() + ' (NIS: ' + $('<div>').text(row.nis || '-').html() + ')</option>');
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
            const name = $(this).data('name') || '';
            const nis  = $(this).data('nis') || '';
            const nisn = $(this).data('nisn') || '';

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
