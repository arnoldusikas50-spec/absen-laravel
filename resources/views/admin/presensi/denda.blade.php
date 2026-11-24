@extends('layouts.main')

@section('content')
@php
    $daysInMonth = date('t', mktime(0, 0, 0, $bulan, 1, $tahun));

    // Compute summary metrics
    $totalDendaKotorAll = 0;
    $totalBayarTunaiAll = 0;
    $totalPotonganPoinAll = 0;
    $totalPotonganSaldoAll = 0;
    $totalSisaBelumAll = 0;
    $countLunas = 0;
    $countSebagian = 0;
    $countBelum = 0;
    $countNihil = 0;
    $countHasPoin = 0;
    $countHasTunai = 0;
    $countHasSaldo = 0;
    $potonganSaldoMap = $potonganSaldoMap ?? [];
    $totalGuru = count($users);

    $statsByUser = [];
    foreach ($users as $user) {
        $cntHadir = 0; $cntTelat = 0; $cntDinas = 0; $cntIzinSakit = 0;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
            $rec = $mapByUser[$user->id][$date] ?? null;

            if ($rec) {
                $status = $rec->status_masuk;
                if (in_array($status, ['ONTIME', 'LATE'])) {
                    $cntHadir++;
                    if ($status == 'LATE') $cntTelat++;
                }
                elseif (strpos($status, 'DINAS') !== false) { $cntDinas++; }
                elseif (in_array($status, ['IJIN', 'SAKIT', 'CUTI', 'IZIN'])) { $cntIzinSakit++; }
            }
        }

        // Accurate database-driven alpha count
        $cntAlpha = $alphaMap[$user->id] ?? 0;

        $totalPoinBulanIni = 0;
        $totalDendaHarian = 0;
        $dendaItems = [];

        foreach ($mapByUser[$user->id] ?? [] as $tgl => $rec) {
            if (isset($rec->points)) $totalPoinBulanIni += (int)$rec->points;
            if (isset($rec->denda) && (float)$rec->denda > 0) {
                $totalDendaHarian += (float)$rec->denda;
                $dendaItems[] = [
                    'tgl' => $tgl,
                    'jam_masuk' => $rec->jam_masuk ?? '-',
                    'jam_pulang' => $rec->jam_pulang ?? '-',
                    'status_masuk' => $rec->status_masuk ?? '-',
                    'telat_menit' => $rec->telat_menit ?? 0,
                    'pulang_cepat_menit' => $rec->pulang_cepat_menit ?? 0,
                    'denda' => (float)$rec->denda,
                    'keterangan_denda' => $rec->keterangan_denda ?? ''
                ];
            }
        }

        $totalPoinBulanIni += (($alphaMissingMap[$user->id] ?? $cntAlpha) * -abs((int)($absenSetting->point_alpa ?? 5)));

        // Denda Alpa flat per hari (hanya tanggal bolong; tercatat tidak dihitung lagi).
        // $alphaDetailMap berisi missing-only; 'total_denda' selalu ada bila entry ada
        // (bahkan 0 bila semua alpha sudah tercatat) — samakan dengan denda_pdf.
        $dendaAlpha = 0;
        if (isset($alphaDetailMap[$user->id]['total_denda'])) {
            $dendaAlpha = (float) $alphaDetailMap[$user->id]['total_denda'];
            foreach ($alphaDetailMap[$user->id]['items'] ?? [] as $aItem) {
                $dendaItems[] = [
                    'tgl' => $aItem['tgl'],
                    'jam_masuk' => '-',
                    'jam_pulang' => '-',
                    'status_masuk' => 'ALPHA',
                    'telat_menit' => 0,
                    'pulang_cepat_menit' => 0,
                    'denda' => (float)$aItem['denda'],
                    'keterangan_denda' => $aItem['keterangan']
                ];
            }
        }
        $totalDendaKotor = $totalDendaHarian + $dendaAlpha;

        // Payments
        $bayarTunai = $bayarTunaiMap[$user->id] ?? 0;
        $potonganPoin = $potonganPoinMap[$user->id] ?? 0;
        $potonganSaldo = $potonganSaldoMap[$user->id] ?? 0;
        $poinDigunakan = $poinDigunakanMap[$user->id] ?? 0;
        // Saldo denda ikut menghitung "sudah dibayar" (dibayar dari deposit),
        // tapi tidak masuk kas sekolah — jadi dipisah dari $bayarTunai.
        $totalSudahBayar = $bayarTunai + $potonganPoin + $potonganSaldo;

        $sisaDendaBulanIni = max(0, $totalDendaKotor - $totalSudahBayar);

        $isLunas = ($totalDendaKotor > 0 && $sisaDendaBulanIni == 0);
        $isSebagian = ($totalDendaKotor > 0 && $totalSudahBayar > 0 && $sisaDendaBulanIni > 0);
        $isBelum = ($totalDendaKotor > 0 && $totalSudahBayar == 0);
        $isNihil = ($totalDendaKotor == 0);

        if ($isLunas) $countLunas++;
        elseif ($isSebagian) $countSebagian++;
        elseif ($isBelum) $countBelum++;
        elseif ($isNihil) $countNihil++;

        if ($potonganPoin > 0) $countHasPoin++;
        if ($bayarTunai > 0) $countHasTunai++;
        if ($potonganSaldo > 0) $countHasSaldo++;

        $totalDendaKotorAll += $totalDendaKotor;
        $totalBayarTunaiAll += $bayarTunai;
        $totalPotonganPoinAll += $potonganPoin;
        $totalPotonganSaldoAll += $potonganSaldo;
        $totalSisaBelumAll += $sisaDendaBulanIni;

        $saldoPoinAktif = $saldoPoinMap[$user->id] ?? 0;

        $statsByUser[$user->id] = compact(
            'cntHadir', 'cntTelat', 'cntDinas', 'cntIzinSakit', 'cntAlpha',
            'totalPoinBulanIni', 'totalDendaHarian', 'dendaAlpha', 'totalDendaKotor',
            'bayarTunai', 'potonganPoin', 'potonganSaldo', 'poinDigunakan', 'totalSudahBayar',
            'sisaDendaBulanIni', 'isLunas', 'isSebagian', 'isBelum', 'isNihil', 'saldoPoinAktif', 'dendaItems'
        );
    }

    $totalTerkumpul = $totalBayarTunaiAll + $totalPotonganPoinAll + $totalPotonganSaldoAll;
    $persenLunas = $totalDendaKotorAll > 0 ? round(($totalTerkumpul / $totalDendaKotorAll) * 100) : 0;
    $baseAlphaAdmin = (float)($absenSetting->denda_alpha_per_hari ?? 5000);
    $periodeLabel = buat_tanggal(date('F Y', mktime(0, 0, 0, $bulan, 1, $tahun)));

    $chips = [
        ['key' => 'all', 'label' => 'Semua', 'count' => $totalGuru],
        ['key' => 'belum', 'label' => 'Belum Lunas', 'count' => $countBelum + $countSebagian],
        ['key' => 'lunas', 'label' => 'Lunas', 'count' => $countLunas],
        ['key' => 'poin', 'label' => 'Ada Potongan Poin', 'count' => $countHasPoin],
        ['key' => 'tunai', 'label' => 'Ada Bayar Tunai', 'count' => $countHasTunai],
        ['key' => 'nihil', 'label' => 'Nihil (Rp 0)', 'count' => $countNihil],
    ];
@endphp

<div class="space-y-4"
     x-data="dendaPage({
        csrf: @js(csrf_token()),
        tarif: {{ (int) max(1, (float) ($dendaPerPoin ?? 1000)) }},
        period: { bulan: @js((string) $bulan), tahun: @js((string) $tahun), label: @js($periodeLabel) },
        now: { bulan: @js(date('m')), tahun: @js(date('Y')), label: @js(date('F Y')) },
        urls: @js([
            'markLunas' => url('admin/presensi/denda/mark-lunas'),
            'batalLunas' => url('admin/presensi/denda/batal-lunas'),
            'bayarNominal' => url('admin/presensi/denda/bayar-nominal'),
            'preview' => url('admin/presensi/denda/preview-distribusi'),
            'recalculate' => url('admin/presensi/denda/recalculate'),
            'kirimRekap' => url('admin/presensi/denda/kirim-rekap'),
        ]),
     })">

    <x-ui.page-header
        title="{{ $judul ?? 'Rekapitulasi Denda Presensi' }}"
        icon="bi-cash-coin"
        tone="emerald"
        subtitle="Pembukuan pelunasan denda secara Tunai (Kas), Potongan Poin Kedisiplinan, maupun Saldo Denda."
        :crumbs="['Presensi', 'Denda']">
        <x-slot:actions>
            <a href="{{ url('admin/presensi/denda/laporan') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-100">
                <i class="bi bi-journal-text"></i> Laporan Pembayaran
            </a>
            <a href="{{ url('settings?tab=poin') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-100">
                <i class="bi bi-gear"></i> Aturan Denda
            </a>
            <a href="{{ url('admin/presensi') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-100">
                <i class="bi bi-speedometer2"></i> Monitoring
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- 5 Summary Metric Cards -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-2xl border border-slate-200/80 border-l-4 border-l-blue-500 bg-white p-4 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-lg text-blue-600">
                    <i class="bi bi-people-fill"></i>
                </span>
                <div class="min-w-0">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Total GTK</span>
                    <div class="text-xl font-extrabold leading-tight text-slate-800">
                        {{ $totalGuru }} <span class="text-xs font-normal text-slate-500">Orang</span>
                    </div>
                    <p class="mt-0.5 truncate text-[11px] text-slate-400">{{ $countLunas }} Lunas &middot; {{ $countBelum + $countSebagian }} Belum</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 border-l-4 border-l-slate-600 bg-white p-4 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-lg text-slate-700">
                    <i class="bi bi-receipt-cutoff"></i>
                </span>
                <div class="min-w-0">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Denda Kotor</span>
                    <div class="text-lg font-extrabold leading-tight tabular-nums text-slate-800">Rp {{ number_format($totalDendaKotorAll, 0, ',', '.') }}</div>
                    <p class="mt-0.5 truncate text-[11px] text-slate-400">Harian + Alpa Bulan Ini</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 border-l-4 border-l-emerald-500 bg-white p-4 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-lg text-emerald-600">
                    <i class="bi bi-cash-stack"></i>
                </span>
                <div class="min-w-0">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Bayar Tunai</span>
                    <div class="text-lg font-extrabold leading-tight tabular-nums text-emerald-600">Rp {{ number_format($totalBayarTunaiAll, 0, ',', '.') }}</div>
                    <p class="mt-0.5 truncate text-[11px] text-slate-400">Kas Masuk Sekolah</p>
                    @if ($totalPotonganSaldoAll > 0)
                        <p class="mt-0.5 truncate text-[11px] tabular-nums text-sky-600">+ Saldo Denda Rp {{ number_format($totalPotonganSaldoAll, 0, ',', '.') }} ({{ $countHasSaldo }} GTK)</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 border-l-4 border-l-violet-500 bg-white p-4 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-lg text-violet-600">
                    <i class="bi bi-stars"></i>
                </span>
                <div class="min-w-0">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Potongan Poin</span>
                    <div class="text-lg font-extrabold leading-tight tabular-nums text-violet-700">Rp {{ number_format($totalPotonganPoinAll, 0, ',', '.') }}</div>
                    <p class="mt-0.5 truncate text-[11px] text-slate-400">Voucher Reward GTK</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200/80 border-l-4 border-l-rose-500 bg-white p-4 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-lg text-rose-500">
                    <i class="bi bi-hourglass-split"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Sisa Belum Lunas</span>
                    <div class="text-lg font-extrabold leading-tight tabular-nums text-rose-600">Rp {{ number_format($totalSisaBelumAll, 0, ',', '.') }}</div>
                    <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-1.5 rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $persenLunas }}%"></div>
                    </div>
                    <p class="mt-0.5 text-[11px] text-slate-400">{{ $persenLunas }}% Terselesaikan</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Denda Alpa Flat -->
    <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3.5">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-400 text-white"><i class="bi bi-dash-circle"></i></div>
        <div class="min-w-0 flex-1">
            <div class="text-[13px] font-extrabold text-slate-800">
                Denda Alpa Flat <span class="text-[11px] font-normal text-slate-500">Rp {{ number_format($baseAlphaAdmin, 0, ',', '.') }}/hari</span>
            </div>
            <p class="mt-1 text-[11.5px] font-medium leading-relaxed text-slate-500">
                Tarif flat <b>Rp {{ number_format($baseAlphaAdmin, 0, ',', '.') }}</b> per hari kerja tanpa kelipatan.
            </p>
        </div>
    </div>

    <!-- Filter, Search & Table -->
    <x-ui.card title="Rekap Denda {{ $periodeLabel }}" icon="bi-table" tone="emerald" bodyClass="p-0">
        <x-slot:toolbar>
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <form action="" method="get" class="flex flex-wrap items-center gap-2">
                    <span class="mr-1 flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <i class="bi bi-calendar-event text-sm text-blue-600"></i> Periode:
                    </span>
                    <select name="bulan" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 focus:border-blue-400 focus:outline-none">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $m == $bulan ? 'selected' : '' }}>
                                {{ buat_tanggal(date('F', mktime(0, 0, 0, $m, 1))) }}
                            </option>
                        @endfor
                    </select>
                    <select name="tahun" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 focus:border-blue-400 focus:outline-none">
                        @for ($y = date('Y'); $y >= attendance_tahun_tertua(); $y--)
                            <option value="{{ $y }}" {{ $y == $tahun ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-1.5 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                        <i class="bi bi-arrow-repeat"></i> Tampilkan
                    </button>
                    <a href="{{ url('admin/presensi/denda/pdf?bulan=' . $bulan . '&tahun=' . $tahun) }}" target="_blank"
                       title="Cetak Rekap Denda ke format PDF"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-bold text-rose-600 no-underline transition-colors hover:bg-rose-50">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Cetak PDF
                    </a>
                    <button type="button" @click="kirimRekap()" :disabled="sending"
                            :class="sending ? 'opacity-60' : ''"
                            title="Kirim file PDF rekap denda ke grup WhatsApp dan grup Telegram"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 bg-white px-3.5 py-1.5 text-xs font-bold text-emerald-700 transition-colors hover:bg-emerald-50 disabled:cursor-not-allowed">
                        <i class="bi bi-send-fill"></i> Kirim ke Grup
                    </button>
                    <button type="button" @click="recalculate()" :disabled="recalculating"
                            :class="recalculating ? 'opacity-60' : ''"
                            title="Hitung ulang seluruh denda bulan aktif (keterlambatan, pulang cepat, dan alpa flat)"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-amber-300 bg-white px-3.5 py-1.5 text-xs font-bold text-amber-700 transition-colors hover:bg-amber-50 disabled:cursor-not-allowed">
                        <i class="bi bi-arrow-clockwise"></i> Hitung Ulang Denda
                    </button>
                </form>

                <div class="relative w-full sm:w-72">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="bi bi-search text-xs"></i>
                    </span>
                    <input type="text" x-model.debounce.250ms="query" placeholder="Cari nama guru atau NIP..."
                           aria-label="Cari nama guru atau NIP"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 py-1.5 pl-8 pr-8 text-xs text-slate-700 focus:border-blue-400 focus:bg-white focus:outline-none">
                    <button type="button" x-show="query !== ''" @click="query = ''" aria-label="Bersihkan pencarian"
                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 transition-colors hover:text-slate-600">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
            </div>
        </x-slot:toolbar>

        <!-- Quick Filter Status Chips -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/60 px-5 py-2.5">
            <span class="mr-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Filter Status:</span>
            @foreach ($chips as $chip)
                <button type="button" @click="statusFilter = '{{ $chip['key'] }}'"
                        :class="statusFilter === '{{ $chip['key'] }}'
                            ? 'border-blue-600 bg-blue-600 text-white shadow-xs'
                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                        class="inline-flex select-none items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold transition-colors">
                    {{ $chip['label'] }}
                    <span class="rounded-full px-1.5 text-[10px] font-bold tabular-nums"
                          :class="statusFilter === '{{ $chip['key'] }}' ? 'bg-white/25 text-white' : 'bg-slate-100 text-slate-500'">{{ $chip['count'] }}</span>
                </button>
            @endforeach
        </div>

        @if ($users->isEmpty())
            <div class="p-5">
                <x-ui.empty icon="bi-people" title="Belum ada data guru terdaftar."
                            description="Data GTK aktif akan muncul di sini setelah master guru diisi." />
            </div>
        @else
            <div class="max-h-[68vh] overflow-auto">
                <table class="w-full border-collapse text-left text-[13px]">
                    <thead>
                        <tr class="sticky top-0 z-10">
                            <th class="w-10 whitespace-nowrap border-b-2 border-slate-200 bg-slate-50 px-2.5 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500">No</th>
                            <th class="min-w-[180px] whitespace-nowrap border-b-2 border-slate-200 bg-slate-50 px-2.5 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Guru / Pegawai</th>
                            <th class="w-10 whitespace-nowrap border-b-2 border-slate-200 bg-slate-50 px-2.5 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500" title="Jumlah Hadir Bulan Ini">H</th>
                            <th class="w-10 whitespace-nowrap border-b-2 border-slate-200 bg-slate-50 px-2.5 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500" title="Terlambat Masuk Bulan Ini">TL</th>
                            <th class="w-10 whitespace-nowrap border-b-2 border-slate-200 bg-slate-50 px-2.5 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500" title="Alpa / Tanpa Keterangan">A</th>
                            <th class="min-w-[90px] whitespace-nowrap border-b-2 border-slate-200 bg-slate-50 px-2.5 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500" title="Saldo Poin Kedisiplinan GTK">Saldo Poin</th>
                            <th class="min-w-[110px] whitespace-nowrap border-b-2 border-slate-200 bg-slate-50 px-2.5 py-2 text-right text-[11px] font-bold uppercase tracking-wider text-slate-500" title="Denda Kotor Bulan Ini (Harian + Alpa)">Denda Kotor</th>
                            <th class="min-w-[105px] whitespace-nowrap border-b-2 border-slate-200 bg-slate-50 px-2.5 py-2 text-right text-[11px] font-bold uppercase tracking-wider text-violet-700" title="Potongan denda via reward/poin">Potong Poin</th>
                            <th class="min-w-[105px] whitespace-nowrap border-b-2 border-slate-200 bg-slate-50 px-2.5 py-2 text-right text-[11px] font-bold uppercase tracking-wider text-emerald-600" title="Denda dibayar tunai/transfer">Bayar Tunai</th>
                            <th class="min-w-[105px] whitespace-nowrap border-b-2 border-slate-200 bg-slate-50 px-2.5 py-2 text-right text-[11px] font-bold uppercase tracking-wider text-rose-500" title="Sisa denda yang belum dibayar bulan ini">Sisa (Bln Ini)</th>
                            <th class="min-w-[115px] whitespace-nowrap border-b-2 border-slate-200 bg-emerald-50 px-2.5 py-2 text-right text-[11px] font-bold uppercase tracking-wider text-emerald-700" title="Total akumulasi pembayaran denda YTD">Dibayar (YTD)</th>
                            <th class="min-w-[115px] whitespace-nowrap border-b-2 border-slate-200 bg-rose-50 px-2.5 py-2 text-right text-[11px] font-bold uppercase tracking-wider text-rose-600" title="Sisa akumulasi denda belum bayar YTD">Sisa (YTD)</th>
                            <th class="min-w-[120px] whitespace-nowrap border-b-2 border-slate-200 bg-slate-50 px-2.5 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500">Status</th>
                            <th class="w-28 whitespace-nowrap border-b-2 border-slate-200 bg-slate-50 px-2.5 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-500">Aksi</th>
                            <th class="w-10 whitespace-nowrap border-b-2 border-slate-200 bg-slate-50 px-2.5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" data-denda-body>
                        @php $no = 1; @endphp
                        @foreach ($users as $user)
                            @php
                                $s = $statsByUser[$user->id];
                                $history = $historyByUser[$user->id] ?? [];
                                $ytd = $ytdStats[$user->id] ?? ['total_denda' => 0, 'dibayar_tunai' => 0, 'potongan_poin' => 0, 'total_dibayar' => 0, 'sisa' => 0];

                                // Category for quick filtering
                                $filterCat = 'nihil';
                                $statusTone = 'border-slate-200 bg-slate-100 text-slate-600';
                                $statusText = 'Nihil (Rp 0)';
                                $statusIcon = 'bi-check-all';

                                if ($s['totalDendaKotor'] > 0) {
                                    if ($s['sisaDendaBulanIni'] == 0) {
                                        $filterCat = 'lunas';
                                        $kanalBayar = ($s['bayarTunai'] > 0 ? 1 : 0) + ($s['potonganPoin'] > 0 ? 1 : 0) + ($s['potonganSaldo'] > 0 ? 1 : 0);
                                        if ($kanalBayar > 1) {
                                            $statusTone = 'border-blue-200 bg-blue-50 text-blue-700';
                                            $statusText = 'Lunas (Mix)';
                                            $statusIcon = 'bi-check-circle-fill';
                                        } elseif ($s['potonganPoin'] > 0) {
                                            $statusTone = 'border-violet-200 bg-violet-50 text-violet-700';
                                            $statusText = 'Lunas (Poin)';
                                            $statusIcon = 'bi-stars';
                                        } elseif ($s['potonganSaldo'] > 0) {
                                            $statusTone = 'border-sky-200 bg-sky-50 text-sky-700';
                                            $statusText = 'Lunas (Saldo)';
                                            $statusIcon = 'bi-wallet2';
                                        } else {
                                            $statusTone = 'border-emerald-200 bg-emerald-50 text-emerald-700';
                                            $statusText = 'Lunas (Tunai)';
                                            $statusIcon = 'bi-check-circle-fill';
                                        }
                                    } elseif ($s['totalSudahBayar'] > 0) {
                                        $filterCat = 'sebagian';
                                        $statusTone = 'border-amber-200 bg-amber-50 text-amber-700';
                                        $statusText = 'Sebagian';
                                        $statusIcon = 'bi-pie-chart-fill';
                                    } else {
                                        $filterCat = 'belum';
                                        $statusTone = 'border-rose-200 bg-rose-50 text-rose-700';
                                        $statusText = 'Belum Bayar';
                                        $statusIcon = 'bi-x-circle-fill';
                                    }
                                }

                                $hasPoin = $s['potonganPoin'] > 0 ? '1' : '0';
                                $hasTunai = $s['bayarTunai'] > 0 ? '1' : '0';
                                $searchKeyword = strtolower($user->nama_guru . ' ' . ($user->nip ?? ''));
                                $guruSaldoDenda = (float) ($saldoDendaMap[$user->id] ?? 0);
                                $saldoVal = (int) $s['saldoPoinAktif'];
                                $poinTone = $saldoVal < 0
                                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                                    : ($saldoVal > 0 ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-100 text-slate-600');
                                $hasSisaBulanIni = $s['sisaDendaBulanIni'] > 0;
                                $hasYtdSisa = ($ytd['sisa'] ?? 0) > 0;
                            @endphp

                            <tr class="align-middle transition-colors hover:bg-slate-50/80"
                                data-denda-row
                                data-user-id="{{ $user->id }}"
                                data-search="{{ $searchKeyword }}"
                                data-status-cat="{{ $filterCat }}"
                                data-has-poin="{{ $hasPoin }}"
                                data-has-tunai="{{ $hasTunai }}">

                                <td class="border-b border-slate-100 px-2.5 py-2 text-center text-xs text-slate-400">{{ $no++ }}</td>

                                <!-- Teacher Info -->
                                <td class="border-b border-slate-100 px-2.5 py-2">
                                    <div class="max-w-[190px] truncate text-sm font-bold text-slate-800" title="{{ $user->nama_guru }}">{{ $user->nama_guru }}</div>
                                    <div class="text-[11px] text-slate-400">NIP: {{ $user->nip ?? '-' }}</div>
                                    @if ($guruSaldoDenda > 0)
                                        <div class="mt-1">
                                            <span class="inline-flex items-center gap-1 rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-[10px] font-bold text-sky-700" title="Saldo Denda Tersedia">
                                                <i class="bi bi-wallet2"></i>Saldo: Rp {{ number_format($guruSaldoDenda, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Attendance Counters -->
                                <td class="border-b border-slate-100 px-2.5 py-2 text-center font-bold tabular-nums text-emerald-600">{{ $s['cntHadir'] }}</td>
                                <td class="border-b border-slate-100 px-2.5 py-2 text-center font-bold tabular-nums text-amber-500">{{ $s['cntTelat'] }}</td>
                                <td class="border-b border-slate-100 px-2.5 py-2 text-center font-bold tabular-nums text-rose-500">{{ $s['cntAlpha'] }}</td>

                                <!-- Saldo Poin -->
                                <td class="border-b border-slate-100 px-2.5 py-2 text-center">
                                    <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-bold tabular-nums {{ $poinTone }}">
                                        <i class="bi bi-star-fill text-[10px] text-amber-400"></i>{{ $saldoVal > 0 ? '+' . number_format($saldoVal) : number_format($saldoVal) }}
                                    </span>
                                </td>

                                <!-- Denda Kotor (Bulan Ini) -->
                                <td class="border-b border-slate-100 px-2.5 py-2 text-right">
                                    <div class="font-bold tabular-nums text-slate-800">Rp {{ number_format($s['totalDendaKotor'], 0, ',', '.') }}</div>
                                    @if ($s['totalDendaKotor'] > 0)
                                        <div class="text-[10px] text-slate-400">
                                            {{ number_format($s['totalDendaHarian'] / 1000, 1) }}k + {{ number_format($s['dendaAlpha'] / 1000, 1) }}k (A)
                                        </div>
                                    @endif
                                </td>

                                <!-- Potongan Poin -->
                                <td class="border-b border-slate-100 px-2.5 py-2 text-right">
                                    <span class="tabular-nums {{ $s['potonganPoin'] > 0 ? 'font-bold text-violet-700' : 'text-slate-400' }}">
                                        {{ $s['potonganPoin'] > 0 ? 'Rp ' . number_format($s['potonganPoin'], 0, ',', '.') : '-' }}
                                    </span>
                                    @if ($s['poinDigunakan'] > 0)
                                        <div class="text-[10px] text-slate-400">({{ number_format($s['poinDigunakan']) }} pts)</div>
                                    @endif
                                </td>

                                <!-- Bayar Tunai -->
                                <td class="border-b border-slate-100 px-2.5 py-2 text-right" title="Kolom ini hanya kas tunai. Pembayaran dari saldo denda (deposit) ditandai biru.">
                                    <span class="tabular-nums {{ $s['bayarTunai'] > 0 ? 'font-bold text-emerald-600' : 'text-slate-400' }}">
                                        {{ $s['bayarTunai'] > 0 ? 'Rp ' . number_format($s['bayarTunai'], 0, ',', '.') : '-' }}
                                    </span>
                                    @if ($s['potonganSaldo'] > 0)
                                        <div class="text-[10px] tabular-nums text-sky-600">(Saldo: {{ number_format($s['potonganSaldo'], 0, ',', '.') }})</div>
                                    @endif
                                </td>

                                <!-- Sisa Denda Bulan Ini -->
                                <td class="border-b border-slate-100 px-2.5 py-2 text-right">
                                    <span class="font-bold tabular-nums {{ $s['sisaDendaBulanIni'] > 0 ? 'text-rose-500' : 'text-slate-400' }}">
                                        {{ $s['sisaDendaBulanIni'] > 0 ? 'Rp ' . number_format($s['sisaDendaBulanIni'], 0, ',', '.') : '-' }}
                                    </span>
                                </td>

                                <!-- YTD Sudah Dibayar -->
                                <td class="border-b border-slate-100 bg-emerald-50/50 px-2.5 py-2 text-right tabular-nums text-emerald-600" title="Rincian akumulasi pembayaran YTD: Tunai / Poin / Saldo">
                                    <div class="font-bold">Rp {{ number_format($ytd['total_dibayar'], 0, ',', '.') }}</div>
                                    @if (($ytd['dibayar_tunai'] ?? 0) > 0)
                                        <div class="text-[10px] text-emerald-700">(Tunai: {{ number_format($ytd['dibayar_tunai'] / 1000, 1) }}k)</div>
                                    @endif
                                    @if ($ytd['potongan_poin'] > 0)
                                        <div class="text-[10px] text-violet-600">(Poin: {{ number_format($ytd['potongan_poin'] / 1000, 1) }}k)</div>
                                    @endif
                                    @if (($ytd['potongan_saldo'] ?? 0) > 0)
                                        <div class="text-[10px] text-sky-600">(Saldo: {{ number_format($ytd['potongan_saldo'] / 1000, 1) }}k)</div>
                                    @endif
                                </td>

                                <!-- YTD Sisa -->
                                <td class="border-b border-l border-slate-100 bg-rose-50/40 px-2.5 py-2 text-right">
                                    <span class="font-bold tabular-nums {{ ($ytd['sisa'] ?? 0) > 0 ? 'text-rose-500' : 'text-slate-400' }}" title="Akumulasi Belum Lunas Tahun {{ $tahun }}">
                                        {{ ($ytd['sisa'] ?? 0) > 0 ? 'Rp ' . number_format($ytd['sisa'], 0, ',', '.') : '-' }}
                                    </span>
                                </td>

                                <!-- Status Pill -->
                                <td class="border-b border-slate-100 px-2.5 py-2 text-center">
                                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $statusTone }}">
                                        <i class="bi {{ $statusIcon }}"></i> {{ $statusText }}
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="border-b border-slate-100 px-2.5 py-2 text-center">
                                    @if ($hasSisaBulanIni || $hasYtdSisa)
                                        <div class="inline-flex items-center gap-1">
                                            @if ($hasSisaBulanIni)
                                                <button type="button"
                                                        @click="openMark({ id: {{ $user->id }}, nama: @js($user->nama_guru), total: {{ $s['sisaDendaBulanIni'] }}, saldoPoin: {{ $saldoVal }}, saldoDenda: {{ $guruSaldoDenda }} })"
                                                        title="Lunasi Denda Bulan Ini"
                                                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2 py-1 text-xs font-bold text-white transition-colors hover:bg-emerald-700">
                                                    <i class="bi bi-check2"></i>Lunas
                                                </button>
                                            @elseif ($s['isLunas'])
                                                <button type="button"
                                                        @click="openBatal({ id: {{ $user->id }}, nama: @js($user->nama_guru) })"
                                                        title="Batalkan Pelunasan"
                                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50">
                                                    <i class="bi bi-x-circle"></i>Batal
                                                </button>
                                            @endif
                                            <button type="button"
                                                    @click="openNominal({ id: {{ $user->id }}, nama: @js($user->nama_guru), tahun: @js((string) $tahun), saldoPoin: {{ $saldoVal }}, saldoDenda: {{ $guruSaldoDenda }}, ytdSisa: {{ $ytd['sisa'] ?? 0 }} })"
                                                    title="Bayar dengan Nominal Tertentu (Multi-Bulan FIFO)"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-2 py-1 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                                                <i class="bi bi-wallet2"></i>Nominal
                                            </button>
                                        </div>
                                    @elseif ($s['isLunas'])
                                        <button type="button"
                                                @click="openBatal({ id: {{ $user->id }}, nama: @js($user->nama_guru) })"
                                                title="Batalkan Pelunasan"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50">
                                            <i class="bi bi-x-circle"></i>Batal
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-300">-</span>
                                    @endif
                                </td>

                                <!-- Toggle Details -->
                                <td class="border-b border-slate-100 px-2.5 py-2 text-center">
                                    <button type="button" @click="toggleDetail({{ $user->id }})" title="Lihat Rincian Harian &amp; Pembayaran"
                                            :aria-expanded="openDetail[{{ $user->id }}] ? 'true' : 'false'"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-500 transition-colors hover:bg-slate-200">
                                        <i class="bi text-xs" :class="openDetail[{{ $user->id }}] ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Detail Accordion Row -->
                            <tr x-show="openDetail[{{ $user->id }}]" x-cloak>
                                <td colspan="15" class="border-b border-slate-200 bg-slate-50/70 p-0">
                                    <div class="space-y-3 p-4">
                                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">

                                            <!-- Left: Per-day denda breakdown -->
                                            <div class="lg:col-span-7">
                                                <div class="h-full rounded-xl border border-slate-200/80 bg-white p-3 shadow-xs">
                                                    <h6 class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-800">
                                                        <i class="bi bi-list-check text-blue-600"></i> Rincian Keterlambatan &amp; Denda Harian
                                                    </h6>
                                                    @if (empty($s['dendaItems']))
                                                        <div class="py-6 text-center text-xs text-slate-400">
                                                            <i class="bi bi-shield-check mb-1 block text-2xl text-emerald-500"></i>
                                                            Tidak ada catatan keterlambatan atau denda harian pada bulan ini.
                                                        </div>
                                                    @else
                                                        <div class="max-h-48 overflow-auto rounded-lg border border-slate-100">
                                                            <table class="w-full border-collapse text-[11.5px]">
                                                                <thead>
                                                                    <tr class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                                                                        <th class="sticky top-0 z-[1] whitespace-nowrap border-b border-slate-200 bg-slate-50 px-2 py-1.5 text-left font-bold">Tanggal</th>
                                                                        <th class="sticky top-0 z-[1] whitespace-nowrap border-b border-slate-200 bg-slate-50 px-2 py-1.5 text-left font-bold">Status</th>
                                                                        <th class="sticky top-0 z-[1] whitespace-nowrap border-b border-slate-200 bg-slate-50 px-2 py-1.5 text-left font-bold">Masuk</th>
                                                                        <th class="sticky top-0 z-[1] whitespace-nowrap border-b border-slate-200 bg-slate-50 px-2 py-1.5 text-left font-bold">Pulang</th>
                                                                        <th class="sticky top-0 z-[1] whitespace-nowrap border-b border-slate-200 bg-slate-50 px-2 py-1.5 text-center font-bold">Telat</th>
                                                                        <th class="sticky top-0 z-[1] whitespace-nowrap border-b border-slate-200 bg-slate-50 px-2 py-1.5 text-center font-bold">P.Cepat</th>
                                                                        <th class="sticky top-0 z-[1] whitespace-nowrap border-b border-slate-200 bg-slate-50 px-2 py-1.5 text-right font-bold">Denda</th>
                                                                        <th class="sticky top-0 z-[1] whitespace-nowrap border-b border-slate-200 bg-slate-50 px-2 py-1.5 text-left font-bold">Keterangan</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-slate-100">
                                                                    @foreach ($s['dendaItems'] as $di)
                                                                        @php $isDiAlpha = (strtoupper($di['status_masuk']) === 'ALPHA'); @endphp
                                                                        <tr class="{{ $isDiAlpha ? 'bg-amber-50/40' : '' }} hover:bg-slate-50">
                                                                            <td class="whitespace-nowrap px-2 py-1.5 font-mono">{{ date('d/m/Y', strtotime($di['tgl'])) }}</td>
                                                                            <td class="whitespace-nowrap px-2 py-1.5">
                                                                                <span class="rounded-full border px-1.5 py-0.5 text-[10px] font-bold {{ $isDiAlpha ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                                                                                    {{ $di['status_masuk'] }}
                                                                                </span>
                                                                            </td>
                                                                            <td class="whitespace-nowrap px-2 py-1.5 font-mono">{{ !empty($di['jam_masuk']) && $di['jam_masuk'] != '-' ? date('H:i', strtotime($di['jam_masuk'])) : '-' }}</td>
                                                                            <td class="whitespace-nowrap px-2 py-1.5 font-mono">{{ !empty($di['jam_pulang']) && $di['jam_pulang'] != '-' ? date('H:i', strtotime($di['jam_pulang'])) : '-' }}</td>
                                                                            <td class="whitespace-nowrap px-2 py-1.5 text-center">{{ $di['telat_menit'] ? $di['telat_menit'] . 'm' : '-' }}</td>
                                                                            <td class="whitespace-nowrap px-2 py-1.5 text-center">{{ $di['pulang_cepat_menit'] ? $di['pulang_cepat_menit'] . 'm' : '-' }}</td>
                                                                            <td class="whitespace-nowrap px-2 py-1.5 text-right font-bold tabular-nums {{ $isDiAlpha ? 'text-amber-600' : 'text-rose-500' }}">Rp {{ number_format($di['denda'], 0, ',', '.') }}</td>
                                                                            <td class="max-w-[180px] whitespace-normal px-2 py-1.5 leading-tight text-slate-600" title="{{ $di['keterangan_denda'] ?: '-' }}">{{ $di['keterangan_denda'] ?: '-' }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Right: Payment history -->
                                            <div class="lg:col-span-5">
                                                <div class="h-full rounded-xl border border-slate-200/80 bg-white p-3 shadow-xs">
                                                    <h6 class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-800">
                                                        <i class="bi bi-clock-history text-blue-600"></i> Riwayat Pembayaran &amp; Potongan
                                                    </h6>
                                                    @if (empty($history))
                                                        <div class="py-6 text-center text-xs text-slate-400">
                                                            <i class="bi bi-inbox mb-1 block text-2xl opacity-30"></i>
                                                            Belum ada riwayat pembayaran tercatat untuk periode ini.
                                                        </div>
                                                    @else
                                                        <div class="max-h-48 overflow-auto rounded-lg border border-slate-100">
                                                            <table class="w-full border-collapse text-[11.5px]">
                                                                <thead>
                                                                    <tr class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                                                                        <th class="sticky top-0 z-[1] whitespace-nowrap border-b border-slate-200 bg-slate-50 px-2 py-1.5 text-left font-bold">Metode</th>
                                                                        <th class="sticky top-0 z-[1] whitespace-nowrap border-b border-slate-200 bg-slate-50 px-2 py-1.5 text-left font-bold">Tgl Bayar</th>
                                                                        <th class="sticky top-0 z-[1] whitespace-nowrap border-b border-slate-200 bg-slate-50 px-2 py-1.5 text-right font-bold">Jumlah</th>
                                                                        <th class="sticky top-0 z-[1] whitespace-nowrap border-b border-slate-200 bg-slate-50 px-2 py-1.5 text-left font-bold">Keterangan</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-slate-100">
                                                                    @foreach ($history as $h)
                                                                        @php
                                                                            $isPoin = ($h->metode_bayar === 'POIN' || !empty($h->reward_redemption_id));
                                                                            $isSaldo = ($h->metode_bayar === 'SALDO');
                                                                        @endphp
                                                                        <tr class="hover:bg-slate-50">
                                                                            <td class="whitespace-nowrap px-2 py-1.5">
                                                                                @if ($isPoin)
                                                                                    <span class="inline-flex items-center gap-1 rounded-full border border-violet-200 bg-violet-50 px-2 py-0.5 text-[10px] font-bold text-violet-700">
                                                                                        <i class="bi bi-stars"></i> Potongan Poin
                                                                                    </span>
                                                                                @elseif ($isSaldo)
                                                                                    <span class="inline-flex items-center gap-1 rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-[10px] font-bold text-sky-700">
                                                                                        <i class="bi bi-wallet2"></i> Saldo Denda
                                                                                    </span>
                                                                                @else
                                                                                    <span class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                                                                                        <i class="bi bi-cash"></i> Tunai / Kas
                                                                                    </span>
                                                                                @endif
                                                                            </td>
                                                                            <td class="whitespace-nowrap px-2 py-1.5 font-mono">
                                                                                @if (!empty($h->tanggal_bayar))
                                                                                    {{ date('d/m/Y', strtotime($h->tanggal_bayar)) }}
                                                                                @elseif (!empty($h->paid_at))
                                                                                    {{ date('d/m/Y', strtotime($h->paid_at)) }}
                                                                                @else
                                                                                    -
                                                                                @endif
                                                                            </td>
                                                                            <td class="whitespace-nowrap px-2 py-1.5 text-right font-bold tabular-nums {{ $isPoin ? 'text-violet-700' : ($isSaldo ? 'text-sky-700' : 'text-emerald-600') }}">
                                                                                Rp {{ number_format((float) ($h->nominal_bayar ?: $h->total_denda), 0, ',', '.') }}
                                                                            </td>
                                                                            <td class="max-w-[120px] truncate px-2 py-1.5 text-slate-400" title="{{ $h->keterangan ?? '-' }}">{{ $h->keterangan ?? '-' }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                <span>Menampilkan <strong class="text-slate-700" x-text="visible"></strong> dari {{ $totalGuru }} guru &amp; staf untuk periode {{ $periodeLabel }}</span>
                <span class="text-slate-400">Gunakan tombol <strong>Nominal</strong> untuk melunasi tunggakan lintas bulan secara FIFO.</span>
            </div>
        </x-slot:footer>
    </x-ui.card>

    <!-- Modal Mark Lunas dengan Pilihan Metode Pembayaran (Single Bulan) -->
    <x-ui.modal id="modalMarkLunas" title="Konfirmasi Pembayaran Denda" icon="bi-cash-coin" tone="emerald" size="max-w-lg">
        <form @submit.prevent="saveMark()">
            <div class="space-y-4 p-5">
                <div class="rounded-xl border border-slate-200/80 bg-slate-50 p-4 text-center">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">Pelunasan Denda GTK</span>
                    <div class="mt-0.5 text-base font-bold text-slate-800" x-text="mark.nama"></div>
                    <div class="mt-0.5 text-xs text-slate-400">Periode: {{ $periodeLabel }}</div>

                    <div class="mt-3 inline-block rounded-xl border border-emerald-200/70 bg-emerald-50 px-4 py-2">
                        <span class="block text-[11px] font-bold uppercase text-emerald-700">Total Nominal Yang Diselesaikan</span>
                        <div class="mt-0.5 text-2xl font-extrabold tabular-nums text-emerald-600" x-text="fmtRp(mark.total)"></div>
                    </div>
                </div>

                <div>
                    <span class="mb-2 block text-[11px] font-bold uppercase tracking-wider text-slate-700">Pilih Metode Pembayaran <span class="text-rose-500">*</span></span>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="cursor-pointer rounded-xl border-2 p-2.5 text-center transition-colors"
                               :class="mark.metode === 'TUNAI' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" class="sr-only" x-model="mark.metode" value="TUNAI">
                            <i class="bi bi-cash-stack block text-xl text-emerald-600"></i>
                            <span class="mt-1 block text-xs font-bold">Tunai</span>
                            <span class="block text-[10px] text-slate-400">Kas Sekolah</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border-2 p-2.5 text-center transition-colors"
                               :class="mark.metode === 'POIN' ? 'border-violet-500 bg-violet-50 text-violet-700' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" class="sr-only" x-model="mark.metode" value="POIN">
                            <i class="bi bi-stars block text-xl text-violet-600"></i>
                            <span class="mt-1 block text-xs font-bold text-violet-700">Poin</span>
                            <span class="block text-[10px] text-slate-400">Potong Poin</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border-2 p-2.5 text-center transition-colors"
                               :class="mark.metode === 'SALDO' ? 'border-sky-500 bg-sky-50 text-sky-700' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" class="sr-only" x-model="mark.metode" value="SALDO">
                            <i class="bi bi-wallet2 block text-xl text-sky-600"></i>
                            <span class="mt-1 block text-xs font-bold text-sky-700">Saldo</span>
                            <span class="block text-[10px] text-slate-400">Saldo Denda</span>
                        </label>
                    </div>
                </div>

                <!-- Saldo Denda Helper Box -->
                <div x-show="mark.metode === 'SALDO'" x-cloak class="space-y-1 rounded-xl border border-sky-200/80 bg-sky-50/70 p-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-medium text-sky-800">Saldo Denda Guru:</span>
                        <span class="text-sm font-bold tabular-nums text-sky-700" x-text="fmtRp(mark.saldoDenda)"></span>
                    </div>
                    <p class="text-[11px] text-sky-700/80">Saldo denda guru akan dipotong otomatis untuk melunasi periode ini.</p>
                </div>

                <!-- Point Deduction Helper Box -->
                <div x-show="mark.metode === 'POIN'" x-cloak class="space-y-2 rounded-xl border border-violet-200/80 bg-violet-50/70 p-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-medium text-violet-800">Saldo Poin Kedisiplinan:</span>
                        <span class="text-sm font-bold tabular-nums text-violet-700" x-text="fmtNum(mark.saldoPoin) + ' pts'"></span>
                    </div>
                    <div>
                        <span class="mb-1 block text-[11px] font-bold text-violet-900">Jumlah Poin Yang Dipotong:</span>
                        <input type="number" :value="markPoin" readonly
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold tabular-nums text-slate-600">
                        <span class="mt-1 block text-[10px] text-violet-600">Dihitung dari nominal &times; tarif Rp {{ number_format((float) ($dendaPerPoin ?? 1000), 0, ',', '.') }}/poin, lalu dipotong dari saldo kedisiplinan guru.</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="mark_tanggal_bayar" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-700">Tanggal Pembayaran</label>
                        <input id="mark_tanggal_bayar" type="date" x-model="mark.tanggal"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-700 focus:border-blue-400 focus:outline-none">
                    </div>
                    <div>
                        <label for="mark_keterangan" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-700">Keterangan / Bukti</label>
                        <input id="mark_keterangan" type="text" x-model="mark.keterangan" placeholder="Diterima bendahara / voucher"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-700 focus:border-blue-400 focus:outline-none">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#modalMarkLunas')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="mark.saving"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <i class="bi bi-check2-circle"></i> <span x-text="mark.saving ? 'Menyimpan...' : 'Simpan Pembayaran'"></span>
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal Batal Lunas -->
    <x-ui.modal id="modalBatalLunas" title="Batalkan Pelunasan Denda" icon="bi-exclamation-triangle" tone="rose" size="max-w-md">
        <form @submit.prevent="saveBatal()">
            <div class="space-y-4 p-5">
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-center">
                    <span class="block text-[11px] font-bold uppercase text-slate-500">Guru / Pegawai</span>
                    <div class="mt-0.5 text-base font-bold text-slate-800" x-text="batal.nama"></div>
                    <div class="text-xs text-slate-400">Periode: {{ $periodeLabel }}</div>
                </div>

                <div class="space-y-1 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs leading-relaxed text-amber-800">
                    <div class="flex items-center gap-1 font-bold">
                        <i class="bi bi-info-circle-fill text-amber-600"></i> Catatan Pembatalan:
                    </div>
                    <p>Status lunas untuk guru ini akan dibatalkan. Jika sebelumnya dibayar dengan potongan poin, poin akan dikembalikan ke saldo poin guru secara otomatis.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#modalBatalLunas')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Tutup</button>
                <button type="submit" :disabled="batal.saving"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition-colors hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <i class="bi bi-arrow-counterclockwise"></i> <span x-text="batal.saving ? 'Memproses...' : 'Batalkan Pelunasan'"></span>
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal Bayar Nominal (Multi-Bulan Auto-Distribusi FIFO) -->
    <x-ui.modal id="modalBayarNominal" title="Pembayaran Nominal (Multi-Bulan)" icon="bi-wallet2" tone="blue" size="max-w-3xl">
        <form @submit.prevent="saveNominal()">
            <div class="space-y-4 p-5">
                <p class="-mt-1 text-xs text-slate-400">Distribusi otomatis secara FIFO ke bulan yang belum lunas.</p>

                <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200/80 bg-slate-50 p-3.5">
                    <div>
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Pelunasan Akumulasi</span>
                        <div class="text-sm font-bold leading-tight text-slate-800" x-text="nominal.nama"></div>
                        <span class="text-xs text-slate-400">Tahun: <span x-text="nominal.tahun"></span></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700">
                            Sisa YTD: <span class="tabular-nums" x-text="fmtRp(nominal.ytdSisa)"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700">
                            Saldo: <span class="tabular-nums" x-text="fmtRp(nominal.saldoDenda)"></span>
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
                    <!-- Left Form Inputs -->
                    <div class="space-y-3.5 lg:col-span-5">
                        <div>
                            <label for="nominal_bayar_input" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-700">
                                Nominal Pembayaran <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-bold text-slate-400">Rp</span>
                                <input id="nominal_bayar_input" type="number" x-model.number="nominal.nominal" min="1" step="any" placeholder="Contoh: 150000" required
                                       class="w-full rounded-xl border border-slate-200 py-2 pl-9 pr-3 text-xs font-bold tabular-nums text-slate-700 focus:border-blue-400 focus:outline-none">
                            </div>
                            <span class="mt-1 block text-[10px] text-slate-400">Kelebihan nominal akan otomatis disimpan menjadi <strong>Saldo Denda</strong>.</span>
                        </div>

                        <div>
                            <label for="nominal_tanggal_bayar" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-700">Tanggal Pembayaran</label>
                            <input id="nominal_tanggal_bayar" type="date" x-model="nominal.tanggal"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-700 focus:border-blue-400 focus:outline-none">
                        </div>

                        <div>
                            <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-700">Metode Pembayaran <span class="text-rose-500">*</span></span>
                            <div class="grid grid-cols-3 gap-1.5">
                                <label class="cursor-pointer rounded-xl border-2 px-1 py-2 text-center transition-colors"
                                       :class="nominal.metode === 'TUNAI' ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 bg-white hover:border-slate-300'">
                                    <input type="radio" class="sr-only" x-model="nominal.metode" value="TUNAI">
                                    <i class="bi bi-cash-stack block text-lg text-emerald-600"></i>
                                    <span class="mt-0.5 block text-[11px] font-bold">Tunai</span>
                                </label>
                                <label class="cursor-pointer rounded-xl border-2 px-1 py-2 text-center transition-colors"
                                       :class="nominal.metode === 'POIN' ? 'border-violet-500 bg-violet-50' : 'border-slate-200 bg-white hover:border-slate-300'">
                                    <input type="radio" class="sr-only" x-model="nominal.metode" value="POIN">
                                    <i class="bi bi-stars block text-lg text-violet-600"></i>
                                    <span class="mt-0.5 block text-[11px] font-bold text-violet-700">Poin</span>
                                </label>
                                <label class="cursor-pointer rounded-xl border-2 px-1 py-2 text-center transition-colors"
                                       :class="nominal.metode === 'SALDO' ? 'border-sky-500 bg-sky-50' : 'border-slate-200 bg-white hover:border-slate-300'">
                                    <input type="radio" class="sr-only" x-model="nominal.metode" value="SALDO">
                                    <i class="bi bi-wallet2 block text-lg text-sky-600"></i>
                                    <span class="mt-0.5 block text-[11px] font-bold text-sky-700">Saldo</span>
                                </label>
                            </div>
                        </div>

                        <div x-show="nominal.metode === 'SALDO'" x-cloak class="rounded-xl border border-sky-200/80 bg-sky-50/70 p-2.5 text-xs">
                            <div class="mb-1 flex items-center justify-between">
                                <span class="text-sky-800">Saldo Denda Tersedia:</span>
                                <span class="font-bold tabular-nums text-sky-700" x-text="fmtRp(nominal.saldoDenda)"></span>
                            </div>
                            <span class="text-[10px] text-sky-600">Denda dipotong langsung dari saldo denda guru.</span>
                        </div>

                        <div x-show="nominal.metode === 'POIN'" x-cloak class="space-y-1 rounded-xl border border-violet-200/80 bg-violet-50/70 p-2.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-violet-800">Saldo Poin:</span>
                                <span class="font-bold tabular-nums text-violet-700" x-text="fmtNum(nominal.saldoPoin) + ' pts'"></span>
                            </div>
                            <input type="number" :value="nominalPoin" readonly
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold tabular-nums text-slate-600">
                            <span class="block text-[10px] text-violet-600">Ikut nominal: Rp &divide; tarif Rp {{ number_format((float) ($dendaPerPoin ?? 1000), 0, ',', '.') }}/poin. Server yang menghitungnya.</span>
                        </div>

                        <div>
                            <label for="nominal_keterangan" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-700">Keterangan (Opsional)</label>
                            <input id="nominal_keterangan" type="text" x-model="nominal.keterangan" placeholder="Catatan pembayaran"
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-700 focus:border-blue-400 focus:outline-none">
                        </div>

                        <button type="button" @click="loadPreview()"
                                class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-blue-200 bg-white px-4 py-2 text-xs font-semibold text-blue-700 transition-colors hover:bg-blue-50">
                            <i class="bi bi-eye"></i> Hitung Preview Distribusi
                        </button>
                    </div>

                    <!-- Right Live Preview -->
                    <div class="lg:col-span-7">
                        <div class="flex h-full flex-col rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                            <h6 class="mb-2.5 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-700">
                                <i class="bi bi-diagram-3 text-blue-600"></i> Preview Distribusi Pembayaran FIFO
                            </h6>
                            <div class="flex flex-1 flex-col justify-center">
                                <template x-if="preview.loading">
                                    <div class="py-8 text-center text-xs text-slate-500">
                                        <i class="bi bi-arrow-repeat mr-1.5 inline-block animate-spin"></i> Menghitung distribusi...
                                    </div>
                                </template>

                                <template x-if="!preview.loading && preview.error !== ''">
                                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700" x-text="preview.error"></div>
                                </template>

                                <template x-if="!preview.loading && preview.error === '' && preview.summary">
                                    <div class="space-y-3">
                                        <template x-if="preview.rows.length > 0">
                                            <div class="max-h-44 overflow-auto rounded-xl border border-slate-200">
                                                <table class="w-full border-collapse text-[11.5px]">
                                                    <thead>
                                                        <tr class="bg-slate-100 text-[10px] uppercase tracking-wider text-slate-600">
                                                            <th class="sticky top-0 bg-slate-100 px-2 py-1.5 text-left font-bold">Periode</th>
                                                            <th class="sticky top-0 bg-slate-100 px-2 py-1.5 text-right font-bold">Sisa Denda</th>
                                                            <th class="sticky top-0 bg-slate-100 px-2 py-1.5 text-right font-bold">Dibayar</th>
                                                            <th class="sticky top-0 bg-slate-100 px-2 py-1.5 text-right font-bold">Sisa Sesudah</th>
                                                            <th class="sticky top-0 bg-slate-100 px-2 py-1.5 text-center font-bold">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        <template x-for="(d, i) in preview.rows" :key="i">
                                                            <tr class="bg-white">
                                                                <td class="whitespace-nowrap px-2 py-1.5 font-bold" x-text="d.periode"></td>
                                                                <td class="whitespace-nowrap px-2 py-1.5 text-right tabular-nums" x-text="fmtRp(d.sisaSebelum)"></td>
                                                                <td class="whitespace-nowrap px-2 py-1.5 text-right font-bold tabular-nums text-emerald-600" x-text="fmtRp(d.bayar)"></td>
                                                                <td class="whitespace-nowrap px-2 py-1.5 text-right tabular-nums"
                                                                    :class="d.sisaSesudah > 0 ? 'text-rose-500' : 'text-slate-400'" x-text="fmtRp(d.sisaSesudah)"></td>
                                                                <td class="whitespace-nowrap px-2 py-1.5 text-center">
                                                                    <span class="rounded-full border px-2 py-0.5 text-[10px] font-bold"
                                                                          :class="d.lunas ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700'"
                                                                          x-text="d.lunas ? 'LUNAS' : 'SEBAGIAN'"></span>
                                                                </td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </template>

                                        <template x-if="preview.rows.length === 0">
                                            <div class="rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs text-blue-800">
                                                <i class="bi bi-info-circle mr-1"></i> Tidak ada tunggakan denda. Seluruh nominal akan disimpan sebagai Saldo Denda.
                                            </div>
                                        </template>

                                        <div class="space-y-1.5 rounded-xl border border-slate-200 bg-white p-3 text-xs shadow-xs">
                                            <div class="flex justify-between text-slate-600"><span>Total Tunggakan:</span><strong x-text="fmtRp(preview.summary.totalSisa)"></strong></div>
                                            <div class="flex justify-between text-blue-600"><span>Nominal Dibayarkan:</span><strong x-text="fmtRp(preview.summary.nominal)"></strong></div>
                                            <div class="flex justify-between text-emerald-600"><span>Alokasi Pelunasan:</span><strong x-text="fmtRp(preview.summary.alokasi)"></strong></div>
                                            <template x-if="preview.summary.lebih > 0">
                                                <div class="flex justify-between border-t border-slate-100 pt-1 font-bold text-sky-600">
                                                    <span>Kelebihan (Masuk Saldo):</span><strong x-text="'+' + fmtRp(preview.summary.lebih)"></strong>
                                                </div>
                                            </template>
                                            <div class="flex justify-between border-t border-slate-100 pt-1"
                                                 :class="preview.summary.sisaSetelah > 0 ? 'font-bold text-rose-500' : 'text-slate-500'">
                                                <span>Sisa Setelah Bayar:</span><strong x-text="fmtRp(preview.summary.sisaSetelah)"></strong>
                                            </div>
                                        </div>

                                        <template x-if="preview.summary.lebih > 0">
                                            <div class="rounded-lg border border-sky-200 bg-sky-50 p-2 text-[11px] text-sky-800">
                                                <i class="bi bi-wallet2 mr-1"></i> Kelebihan <strong x-text="fmtRp(preview.summary.lebih)"></strong> otomatis disimpan menjadi <strong>Saldo Denda</strong> guru.
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="!preview.loading && preview.error === '' && !preview.summary">
                                    <div class="py-8 text-center text-slate-400">
                                        <i class="bi bi-arrow-left-circle mb-2 block text-3xl text-slate-300"></i>
                                        <span class="mx-auto block max-w-xs text-xs leading-relaxed">Masukkan nominal lalu klik <strong>Hitung Preview Distribusi</strong> untuk melihat rincian alokasi ke bulan-bulan yang menunggak.</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#modalBayarNominal')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="nominal.saving || !(Number(nominal.nominal) > 0)"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <i class="bi bi-check2-circle"></i> <span x-text="nominal.saving ? 'Menyimpan...' : 'Simpan Pembayaran'"></span>
                </button>
            </div>
        </form>
    </x-ui.modal>

</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    /** Nonce unik per aksi bayar untuk kunci idempotensi server. */
    const nonceBayar = () => {
        try {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                return window.crypto.randomUUID().replace(/-/g, '').slice(0, 32);
            }
        } catch (e) {
            /* fallback di bawah */
        }

        const abjad = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let hasil = '';

        for (let i = 0; i < 32; i++) {
            hasil += abjad.charAt(Math.floor(Math.random() * abjad.length));
        }

        return hasil;
    };

    const hariIni = () => new Date().toLocaleDateString('en-CA');

    const NAMA_BULAN = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    Alpine.data('dendaPage', (config = {}) => {
        /* Baris tabel adalah node DOM — disimpan di closure agar Alpine tidak
           memproksikan elemen DOM ke state reaktif. */
        const rows = [];

        return {
            csrf: config.csrf || '',
            tarif: Math.max(1, Number(config.tarif) || 1000),
            urls: config.urls || {},
            period: config.period || {},
            now: config.now || {},
            query: '',
            statusFilter: 'all',
            openDetail: {},
            visible: 0,
            sending: false,
            recalculating: false,
            preview: { loading: false, error: '', rows: [], summary: null },
            mark: { saving: false, userId: null, nama: '', total: 0, saldoPoin: 0, saldoDenda: 0, metode: 'TUNAI', tanggal: '', keterangan: '' },
            batal: { saving: false, userId: null, nama: '' },
            nominal: { saving: false, userId: null, nama: '', tahun: '', ytdSisa: 0, saldoPoin: 0, saldoDenda: 0, nominal: '', tanggal: '', metode: 'TUNAI', keterangan: '' },

            init() {
                const body = this.$root.querySelector('[data-denda-body]');
                rows.push(...(body ? Array.from(body.querySelectorAll('[data-denda-row]')) : []));
                this.visible = rows.length;

                this.$watch('query', () => this.applyFilter());
                this.$watch('statusFilter', () => this.applyFilter());
            },

            /* ── Filter & rincian baris ─────────────── */

            applyFilter() {
                const keyword = this.query.trim().toLowerCase();
                let shown = 0;

                rows.forEach((row) => {
                    const category = row.dataset.statusCat || 'belum';
                    const matchSearch = keyword === '' || (row.dataset.search || '').includes(keyword);
                    let matchFilter = true;

                    if (this.statusFilter === 'belum') {
                        matchFilter = category === 'belum' || category === 'sebagian';
                    } else if (this.statusFilter === 'lunas') {
                        matchFilter = category === 'lunas';
                    } else if (this.statusFilter === 'poin') {
                        matchFilter = row.dataset.hasPoin === '1';
                    } else if (this.statusFilter === 'tunai') {
                        matchFilter = row.dataset.hasTunai === '1';
                    } else if (this.statusFilter === 'nihil') {
                        matchFilter = category === 'nihil';
                    }

                    const show = matchSearch && matchFilter;
                    row.classList.toggle('hidden', !show);

                    if (show) {
                        shown++;
                    } else if (this.openDetail[row.dataset.userId]) {
                        this.openDetail[row.dataset.userId] = false;
                    }
                });

                this.visible = shown;
            },

            toggleDetail(id) {
                this.openDetail[id] = !this.openDetail[id];
            },

            /* ── Format & transport ─────────────────── */

            fmtNum(value) {
                return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
            },

            fmtRp(value) {
                return 'Rp ' + this.fmtNum(value);
            },

            reload() {
                window.location.reload();
            },

            async post(url, data = {}) {
                const body = new FormData();
                Object.entries(data).forEach(([key, value]) => body.append(key, value ?? ''));
                body.append('_token', this.csrf);

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        body,
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });

                    return await res.json();
                } catch (e) {
                    if (window.Swal) Swal.close();
                    Swal.fire('Error Sistem', 'Terjadi kesalahan sistem saat menghubungi server', 'error');

                    return null;
                }
            },

            /** Tutup modal, notifikasi sukses, lalu muat ulang agar data server segar. */
            async done(res, modalId, title, failTitle) {
                if (!res) return false;

                if (!res.status) {
                    Swal.fire(failTitle || 'Gagal Menyimpan', res.message || 'Terjadi kesalahan', 'error');

                    return false;
                }

                if (modalId) this.$store.modals.close(modalId);

                await Swal.fire({ icon: 'success', title, text: res.message, timer: 1500, showConfirmButton: false });
                this.reload();

                return true;
            },

            /* ── Lunasi per bulan ───────────────────── */

            openMark(row) {
                this.mark = {
                    saving: false,
                    userId: row.id,
                    nama: row.nama,
                    total: Number(row.total) || 0,
                    saldoPoin: Number(row.saldoPoin) || 0,
                    saldoDenda: Number(row.saldoDenda) || 0,
                    metode: 'TUNAI',
                    tanggal: hariIni(),
                    keterangan: '',
                };
                this.$store.modals.open('#modalMarkLunas');
            },

            get markPoin() {
                return Math.max(0, Math.ceil((Number(this.mark.total) || 0) / this.tarif));
            },

            async saveMark() {
                if (this.mark.saving || !this.mark.userId) return;
                this.mark.saving = true;

                const res = await this.post(this.urls.markLunas, {
                    user_id: this.mark.userId,
                    bulan: this.period.bulan,
                    tahun: this.period.tahun,
                    total_denda: this.mark.total,
                    metode_bayar: this.mark.metode,
                    poin_digunakan: this.mark.metode === 'POIN' ? this.markPoin : 0,
                    tanggal_bayar: this.mark.tanggal,
                    keterangan: this.mark.keterangan,
                    idempotency_key: nonceBayar(),
                });

                this.mark.saving = false;
                await this.done(res, '#modalMarkLunas', 'Berhasil!', 'Gagal Menyimpan');
            },

            /* ── Batalkan pelunasan ─────────────────── */

            openBatal(row) {
                this.batal = { saving: false, userId: row.id, nama: row.nama };
                this.$store.modals.open('#modalBatalLunas');
            },

            async saveBatal() {
                if (this.batal.saving || !this.batal.userId) return;
                this.batal.saving = true;

                const res = await this.post(this.urls.batalLunas, {
                    user_id: this.batal.userId,
                    bulan: this.period.bulan,
                    tahun: this.period.tahun,
                });

                this.batal.saving = false;
                await this.done(res, '#modalBatalLunas', 'Dibatalkan!', 'Gagal Membatalkan');
            },

            /* ── Bayar nominal (FIFO lintas bulan) ──── */

            openNominal(row) {
                this.nominal = {
                    saving: false,
                    userId: row.id,
                    nama: row.nama,
                    tahun: row.tahun,
                    ytdSisa: Number(row.ytdSisa) || 0,
                    saldoPoin: Number(row.saldoPoin) || 0,
                    saldoDenda: Number(row.saldoDenda) || 0,
                    nominal: '',
                    tanggal: hariIni(),
                    metode: 'TUNAI',
                    keterangan: '',
                };
                this.preview = { loading: false, error: '', rows: [], summary: null };
                this.$store.modals.open('#modalBayarNominal');
            },

            get nominalPoin() {
                return Math.max(0, Math.ceil((Number(this.nominal.nominal) || 0) / this.tarif));
            },

            async loadPreview() {
                const nominal = Number(this.nominal.nominal) || 0;

                if (!this.nominal.userId) return;

                if (nominal <= 0) {
                    Swal.fire('Peringatan', 'Masukkan nominal pembayaran yang valid terlebih dahulu.', 'warning');

                    return;
                }

                this.preview = { loading: true, error: '', rows: [], summary: null };

                const url = new URL(this.urls.preview, window.location.origin);
                url.searchParams.set('user_id', this.nominal.userId);
                url.searchParams.set('nominal_bayar', nominal);
                url.searchParams.set('tahun', this.nominal.tahun);

                try {
                    const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    const json = await res.json();

                    if (!json.status) {
                        this.preview = { loading: false, error: json.message || 'Tidak dapat memproses distribusi.', rows: [], summary: null };

                        return;
                    }

                    const data = json.data || {};

                    this.preview = {
                        loading: false,
                        error: '',
                        rows: (data.distribusi || []).map((d) => ({
                            periode: (NAMA_BULAN[d.bulan] || d.bulan) + ' ' + d.tahun,
                            sisaSebelum: d.sisa_sebelum,
                            bayar: d.nominal_bayar,
                            sisaSesudah: d.sisa_sesudah,
                            lunas: d.status === 'LUNAS',
                        })),
                        summary: {
                            totalSisa: data.total_sisa,
                            nominal,
                            alokasi: data.alokasi_denda,
                            lebih: data.lebih_bayar,
                            sisaSetelah: data.sisa_setelah_bayar,
                        },
                    };
                    this.nominal.ytdSisa = data.total_sisa;
                } catch (e) {
                    this.preview = { loading: false, error: 'Gagal mengambil kalkulasi distribusi dari server.', rows: [], summary: null };
                }
            },

            async saveNominal() {
                if (this.nominal.saving) return;
                this.nominal.saving = true;

                const res = await this.post(this.urls.bayarNominal, {
                    user_id: this.nominal.userId,
                    tahun: this.nominal.tahun,
                    nominal_bayar: this.nominal.nominal,
                    metode_bayar: this.nominal.metode,
                    poin_digunakan: this.nominal.metode === 'POIN' ? this.nominalPoin : 0,
                    tanggal_bayar: this.nominal.tanggal,
                    keterangan: this.nominal.keterangan,
                    idempotency_key: nonceBayar(),
                });

                this.nominal.saving = false;
                await this.done(res, '#modalBayarNominal', 'Pembayaran Tercatat!', 'Gagal Menyimpan');
            },

            /* ── Aksi periode ───────────────────────── */

            async kirimRekap() {
                if (this.sending) return;
                this.sending = true;

                try {
                    const res = await this.post(this.urls.kirimRekap, {
                        bulan: this.period.bulan,
                        tahun: this.period.tahun,
                    });

                    if (!res) return;

                    if (res.status) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Rekap Denda Terkirim',
                            text: res.message || 'Berkas PDF telah dikirim ke grup.',
                            timer: 4500,
                            showConfirmButton: false,
                        });
                    } else {
                        // Pesan server menyebut kanal mana yang gagal — tampilkan apa adanya.
                        Swal.fire({
                            icon: 'error',
                            title: 'Rekap Tidak Lengkap Dikirim',
                            text: res.message || 'Gagal mengirim rekap denda.',
                        });
                    }
                } finally {
                    this.sending = false;
                }
            },

            async recalculate() {
                if (this.recalculating) return;

                const konfirmasi = await Swal.fire({
                    title: 'Hitung Ulang Denda Presensi?',
                    html: '<p class="text-xs text-slate-600">Sistem akan mengevaluasi dan menghitung ulang seluruh denda presensi periode <strong>'
                        + this.now.label + '</strong> (bulan aktif — bulan lampau dikunci sebagai arsip):</p>'
                        + '<div class="mt-2 space-y-1.5 rounded-lg border border-slate-200 bg-slate-50 p-3 text-left text-xs text-slate-600">'
                        + '<div><i class="bi bi-check2-circle mr-1.5 text-emerald-600"></i>Status keterlambatan masuk sesuai toleransi &amp; jadwal aktif</div>'
                        + '<div><i class="bi bi-check2-circle mr-1.5 text-emerald-600"></i>Status pulang cepat sesuai batas jam pulang aktif</div>'
                        + '<div><i class="bi bi-check2-circle mr-1.5 text-emerald-600"></i>Denda Alpa flat per hari otomatis</div>'
                        + '<div><i class="bi bi-check2-circle mr-1.5 text-emerald-600"></i>Batas maksimal denda bulanan (cap) jika dikonfigurasi</div>'
                        + '</div>',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#d97706',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hitung Ulang',
                    cancelButtonText: 'Batal',
                });

                if (!konfirmasi.isConfirmed) return;

                this.recalculating = true;
                Swal.fire({
                    title: 'Sedang Menghitung Ulang...',
                    text: 'Mohon tunggu, sistem sedang memproses data presensi & denda...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                const res = await this.post(this.urls.recalculate, {
                    bulan: this.now.bulan,
                    tahun: this.now.tahun,
                });

                this.recalculating = false;

                if (!res) return;

                if (res.status) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Dihitung Ulang!',
                        text: res.message,
                        timer: 2500,
                        showConfirmButton: false,
                    });
                    this.reload();
                } else {
                    Swal.fire('Gagal Menghitung Ulang', res.message || 'Terjadi kesalahan.', 'error');
                }
            },
        };
    });
});
</script>
@endsection
