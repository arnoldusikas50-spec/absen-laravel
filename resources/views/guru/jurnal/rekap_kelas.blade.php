@extends('layouts.mobile_tw')

@section('styles')
<style>
    /* Top app bar custom adjustments */
    .top-app-bar {
        background: #ffffff;
        padding-top: calc(14px + var(--safe-area-inset-top, 0px));
        padding-bottom: 12px;
    }

    /* Month navigation chip */
    .month-nav-chip {
        user-select: none;
        transition: transform 0.12s ease, background-color 0.15s ease;
    }
    .month-nav-chip:active {
        transform: scale(0.985);
    }

    /* Card press animations */
    .mobile-rekap-card {
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .mobile-rekap-card:active {
        transform: scale(0.985);
    }
</style>
@endsection

@section('content')

<?php
    $months = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
    ];

    $curM = (int) $bulan;
    $curY = (int) $tahun;

    $prevTs = mktime(0, 0, 0, $curM - 1, 1, $curY);
    $nextTs = mktime(0, 0, 0, $curM + 1, 1, $curY);

    $linkBulan = function (int $m, int $y) use ($id_kelas_pilih, $id_mapel_pilih): string {
        $qs = ['bulan' => sprintf('%02d', $m), 'tahun' => $y];
        if ((int) $id_kelas_pilih > 0) {
            $qs['id_kelas'] = (int) $id_kelas_pilih;
        }
        if ((int) $id_mapel_pilih > 0) {
            $qs['id_mapel'] = (int) $id_mapel_pilih;
        }

        return url('guru/jurnal/rekap-kelas') . '?' . http_build_query($qs);
    };

    $namaKelas = $kelas->nama_kelas ?? '—';
    $namaGuru  = $gurus[0]->nama_guru ?? ($user->nama ?? 'Guru');
    $nipGuru   = trim((string) ($gurus[0]->nip ?? ''));
    $ringkasan = $per_guru[0] ?? null;
    $adaFilter = ((int) $id_kelas_pilih > 0) || ((int) $id_mapel_pilih > 0);
    $resetUrl  = url('guru/jurnal/rekap-kelas?bulan=' . sprintf('%02d', $curM) . '&tahun=' . $curY);
?>

<!-- ── Top App Bar ──────────────────────────────────────────── -->
<div class="top-app-bar px-4 flex items-center justify-between border-b border-slate-100 shadow-xs">
    <div class="flex items-center gap-2.5">
        <a href="{{ url('guru/jurnal') }}" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-700 transition-colors -ml-1 no-underline" title="Kembali ke Jurnal">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-base font-extrabold text-slate-800 m-0 leading-tight">Rekap per Kelas</h1>
            <p class="text-[11px] font-semibold text-slate-500 m-0 leading-none">{{ $namaKelas }} &middot; {{ $months[sprintf('%02d', $curM)] ?? $curM }} {{ $curY }}</p>
        </div>
    </div>
    <div class="flex items-center gap-1.5">
        <a href="{{ $print_url }}" target="_blank" rel="noopener"
           class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition-colors no-underline" title="Download PDF">
            <i class="bi bi-printer"></i>
        </a>
    </div>
</div>

<!-- ── Main Content Container ───────────────────────────────── -->
<div class="px-4 pb-24 space-y-3.5 mt-2.5">

    <!-- ── Month Navigation Chip ─────────────────────────────── -->
    <div class="month-nav-chip flex items-center justify-between bg-brand-50 border border-brand-100 text-brand-800 rounded-2xl px-3.5 py-2.5 shadow-2xs">
        <a href="{{ $linkBulan((int) date('n', $prevTs), (int) date('Y', $prevTs)) }}"
           class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-brand-100 text-brand-600 transition-colors no-underline" title="Bulan sebelumnya">
            <i class="bi bi-chevron-left text-sm font-bold"></i>
        </a>

        <div class="flex flex-col items-center select-none px-2 py-0.5">
            <span class="font-bold text-xs sm:text-sm text-slate-800 flex items-center gap-2">
                <i class="bi bi-calendar3 text-brand-600 text-sm"></i>
                {{ $months[sprintf('%02d', $curM)] ?? $curM }} {{ $curY }}
            </span>
            <span class="text-[10px] font-semibold text-brand-700 leading-none mt-0.5">Rekap jurnal kelas</span>
        </div>

        <a href="{{ $linkBulan((int) date('n', $nextTs), (int) date('Y', $nextTs)) }}"
           class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-brand-100 text-brand-600 transition-colors no-underline" title="Bulan berikutnya">
            <i class="bi bi-chevron-right text-sm font-bold"></i>
        </a>
    </div>

    <!-- ── Summary KPI Cards ─────────────────────────────────── -->
    <div class="grid grid-cols-2 gap-2">
        <div class="bg-white border border-slate-200/80 rounded-2xl p-3 flex flex-col items-center justify-center text-center shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Pertemuan</span>
            <span class="text-xl font-black text-brand-600 leading-tight mt-0.5">{{ (int) $total_pertemuan }}</span>
            <span class="text-[10px] font-semibold text-slate-500">Jurnal Tercatat</span>
        </div>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-3 flex flex-col items-center justify-center text-center shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Mapel</span>
            <span class="text-xl font-black text-emerald-600 leading-tight mt-0.5">{{ (int) $total_mapel }}</span>
            <span class="text-[10px] font-semibold text-slate-500">Diampu di Kelas</span>
        </div>
    </div>

    <!-- ── Identitas Kelas ───────────────────────────────────── -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs space-y-2">
        <div class="flex items-center justify-between gap-2">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-indigo-50 text-indigo-700 rounded-lg text-[11px] font-bold">
                <i class="bi bi-building text-[10px]"></i>
                Kelas {{ $namaKelas }}
            </span>
            <span class="inline-flex items-center px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md text-[10px] font-extrabold uppercase tracking-wider">
                Fase {{ $fase ?? '-' }}
            </span>
        </div>

        <div class="min-w-0">
            <h2 class="text-sm font-black text-slate-900 leading-snug m-0">{{ $namaGuru }}</h2>
            <p class="text-[11px] font-semibold text-slate-500 m-0 mt-0.5">
                NIP {{ $nipGuru !== '' ? $nipGuru : '-' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-1.5 pt-0.5">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-brand-50 text-brand-700 rounded-md text-[10px] font-bold">
                <i class="bi bi-mortarboard text-[9px]"></i>
                {{ $smt_active->nama_smt ?? 'Semester' }}
            </span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 text-amber-700 rounded-md text-[10px] font-bold">
                <i class="bi bi-calendar-range text-[9px]"></i>
                TP {{ $tp_active->tahun ?? '-' }}
            </span>
            @if($ringkasan)
            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-md text-[10px] font-bold">
                <i class="bi bi-check2-circle text-[9px]"></i>
                {{ (int) $ringkasan['pertemuan'] }} pertemuan
            </span>
            @endif
        </div>

        @if($ringkasan && !empty($ringkasan['mapel']))
        <div class="flex flex-wrap gap-1 pt-1">
            @foreach($ringkasan['mapel'] as $namaMapel)
            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md text-[10px] font-semibold">
                <i class="bi bi-journal-bookmark text-[8px] text-brand-500"></i>
                {{ $namaMapel }}
            </span>
            @endforeach
        </div>
        @endif
    </div>

    <!-- ── Filter Kelas / Mapel ──────────────────────────────── -->
    <div class="bg-white rounded-2xl p-3 border border-slate-200/80 shadow-2xs space-y-2">
        <form method="GET" action="{{ url('guru/jurnal/rekap-kelas') }}" class="space-y-2">
            <input type="hidden" name="bulan" value="{{ sprintf('%02d', $curM) }}">
            <input type="hidden" name="tahun" value="{{ $curY }}">

            <div class="grid grid-cols-2 gap-2">
                <div class="relative min-w-0">
                    <select name="id_kelas" onchange="this.form.submit()" class="w-full text-xs font-semibold pl-2.5 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:outline-hidden appearance-none cursor-pointer truncate">
                        <?php if (empty($kelases)): ?>
                        <option value="">Belum ada kelas</option>
                        <?php else: foreach ($kelases as $k): ?>
                        <option value="{{ (int) $k->id_kelas }}" {{ (int) $id_kelas_pilih === (int) $k->id_kelas ? 'selected' : '' }}>
                            {{ $k->nama_kelas }}
                        </option>
                        <?php endforeach; endif; ?>
                    </select>
                    <i class="bi bi-chevron-down absolute right-2.5 top-2 text-slate-400 pointer-events-none text-xs"></i>
                </div>

                <div class="relative min-w-0">
                    <select name="id_mapel" onchange="this.form.submit()" class="w-full text-xs font-semibold pl-2.5 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:outline-hidden appearance-none cursor-pointer truncate">
                        <option value="">Semua Mapel</option>
                        <?php foreach (($mapels ?? []) as $m): ?>
                        <option value="{{ (int) $m->id_mapel }}" {{ (int) $id_mapel_pilih === (int) $m->id_mapel ? 'selected' : '' }}>
                            {{ $m->nama_mapel }}
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down absolute right-2.5 top-2 text-slate-400 pointer-events-none text-xs"></i>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition-all shadow-2xs active:scale-95">
                    <i class="bi bi-funnel-fill text-xs"></i> Terapkan Filter
                </button>

                <?php if ($adaFilter): ?>
                <a href="{{ $resetUrl }}"
                   class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition-all no-underline shrink-0" title="Reset filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ── Rincian Jurnal ────────────────────────────────────── -->
    <?php if (empty($jurnals)): ?>
    <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-2xs text-center">
        <div class="w-14 h-14 rounded-2xl bg-brand-50 text-brand-500 flex items-center justify-center mx-auto mb-3 text-2xl">
            <i class="bi bi-journal-x"></i>
        </div>
        <h3 class="text-sm font-bold text-slate-800">Belum Ada Jurnal</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto leading-relaxed">
            Tidak ada catatan jurnal untuk kelas {{ $namaKelas }} pada {{ $months[sprintf('%02d', $curM)] ?? $curM }} {{ $curY }}.
        </p>
        <?php if ($adaFilter): ?>
        <a href="{{ $resetUrl }}"
           class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition-all shadow-2xs no-underline active:scale-95">
            <i class="bi bi-arrow-counterclockwise"></i> Hapus Filter
        </a>
        <?php else: ?>
        <a href="{{ url('guru/jurnal/create') }}"
           class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 bg-gradient-to-r from-brand-600 via-brand-500 to-brand-400 hover:from-brand-700 hover:to-brand-500 text-white rounded-xl text-xs font-bold transition-all shadow-xs no-underline active:scale-95">
            <i class="bi bi-plus-lg"></i> Tulis Jurnal Baru
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <?php
        $dateCounts = [];
        foreach ($jurnals as $row) {
            $dateCounts[$row->tanggal] = ($dateCounts[$row->tanggal] ?? 0) + 1;
        }
        $lastDate = null;
    ?>
    <div class="space-y-3">
        <?php foreach ($jurnals as $j): ?>
        <?php if ($lastDate !== $j->tanggal): $lastDate = $j->tanggal; ?>
        <div class="flex items-center gap-2 pt-1">
            <span class="text-[11px] font-extrabold text-slate-500">
                {{ buat_tanggal(date('l, d F Y', strtotime($j->tanggal))) }}
            </span>
            <?php if ($j->tanggal === date('Y-m-d')): ?>
            <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 rounded text-[9px] font-extrabold uppercase tracking-wide">Hari ini</span>
            <?php endif; ?>
            <span class="ml-auto text-[10px] font-semibold text-slate-400">{{ (int) ($dateCounts[$j->tanggal] ?? 0) }} jurnal</span>
        </div>
        <?php endif; ?>
        <div class="mobile-rekap-card bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs flex flex-col justify-between">
            <div class="space-y-2.5">
                <!-- Top info badge -->
                <div class="flex items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-slate-100 rounded-lg text-[11px] font-bold text-slate-700">
                        <i class="bi bi-calendar3 text-brand-600 text-[10px]"></i>
                        {{ date('d/m/Y', strtotime($j->tanggal)) }}
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 bg-brand-50 text-brand-700 rounded-md text-[10px] font-extrabold uppercase tracking-wider">
                        Jam {{ $j->jam_ke ?: '-' }}
                    </span>
                </div>

                <!-- Class & Mapel -->
                <div>
                    <div class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-md text-[10px] font-bold mb-1">
                        <i class="bi bi-building text-[9px]"></i> {{ $j->nama_kelas ?? $namaKelas }}
                    </div>
                    <h3 class="text-sm font-black text-slate-900 leading-snug">
                        {{ $j->nama_mapel ?? 'Mata Pelajaran' }}
                    </h3>
                </div>

                <!-- Materi Pokok -->
                <div class="bg-slate-50 rounded-xl p-2.5 border border-slate-100/80">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-0.5 flex items-center gap-1">
                        <i class="bi bi-bookmark-fill text-brand-500 text-[8px]"></i> Materi Pokok
                    </p>
                    <p class="text-xs font-semibold text-slate-700 line-clamp-2 leading-relaxed">
                        {{ $j->materi_pokok ?: '-' }}
                    </p>
                </div>

                <!-- Model & Asesmen badges -->
                <div class="flex flex-wrap gap-1">
                    <?php if (!empty($j->model_belajar)): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-50 text-purple-700 rounded-md text-[10px] font-bold">
                        <i class="bi bi-diagram-3 text-[8px]"></i>
                        {{ $j->model_belajar }}
                    </span>
                    <?php endif; ?>

                    <?php if (!empty($j->jenis_asesmen)): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-md text-[10px] font-bold">
                        <i class="bi bi-clipboard-check text-[8px]"></i>
                        {{ $j->jenis_asesmen }}
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Siswa Absen Status -->
                <?php
                    $absenIds = json_decode((string) ($j->siswa_absent ?? ''), true);
                    $absenIds = is_array($absenIds)
                        ? array_values(array_filter(array_map('intval', $absenIds), static fn ($v) => $v > 0))
                        : [];
                    $namaAbsen = [];
                    foreach (array_slice($absenIds, 0, 2) as $sid) {
                        $namaAbsen[] = $absen_map[$sid] ?? ('#' . $sid);
                    }
                    $sisaAbsen = count($absenIds) - count($namaAbsen);
                ?>
                <?php if (empty($absenIds)): ?>
                <div class="text-[11px] text-emerald-700 bg-emerald-50/50 border border-emerald-100/60 rounded-lg px-2.5 py-1 flex items-center gap-1.5 font-semibold">
                    <i class="bi bi-check2-circle text-xs shrink-0"></i>
                    <span>Siswa hadir lengkap</span>
                </div>
                <?php else: ?>
                <div class="text-[11px] text-rose-600 bg-rose-50/70 border border-rose-100 rounded-lg px-2.5 py-1 flex items-center gap-1.5 font-semibold">
                    <i class="bi bi-person-x text-xs shrink-0"></i>
                    <span class="truncate">
                        Absen ({{ count($absenIds) }}): {{ implode(', ', $namaAbsen) }}{{ $sisaAbsen > 0 ? ' +' . $sisaAbsen . ' lainnya' : '' }}
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Footer actions -->
            <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between mt-3">
                <a href="{{ url('guru/jurnal/detail/' . (int) $j->id_jurnal) }}"
                   class="inline-flex items-center gap-1 text-xs font-bold text-brand-600 hover:text-brand-800 transition-colors no-underline active:scale-95">
                    <span>Lihat Rincian</span>
                    <i class="bi bi-chevron-right text-[10px]"></i>
                </a>
                <a href="{{ url('guru/jurnal/cetak/' . (int) $j->id_jurnal) }}" target="_blank" rel="noopener"
                   title="Cetak jurnal ini"
                   class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition-colors no-underline active:scale-95">
                    <i class="bi bi-printer"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ── Download PDF ──────────────────────────────────────── -->
    <a href="{{ $print_url }}" target="_blank" rel="noopener"
       class="flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-gradient-to-r from-brand-600 via-brand-500 to-brand-400 hover:from-brand-700 hover:to-brand-500 text-white rounded-xl text-xs font-bold transition-all shadow-xs no-underline active:scale-95">
        <i class="bi bi-file-earmark-pdf"></i>
        Download PDF Rekap Kelas
    </a>
    <p class="text-[10px] text-slate-400 text-center m-0 leading-relaxed px-4">
        PDF berformat A4 landscape: kop sekolah, identitas kelas/fase, tabel jurnal, kolom paraf, dan tanda tangan.
    </p>

</div>
@endsection
