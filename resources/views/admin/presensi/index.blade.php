@extends('layouts.main')

@section('content')

@php
    $jamSekarang = (int) date('H');
    $salam = $jamSekarang < 11 ? 'Selamat Pagi' : ($jamSekarang < 15 ? 'Selamat Siang' : ($jamSekarang < 18 ? 'Selamat Sore' : 'Selamat Malam'));

    $prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
    $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));

    $tanggalLabel = str_replace(
        ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
        buat_tanggal(date('l, d F Y', strtotime($date)))
    );

    $hariIcon = $hariStatus === 'aktif' ? 'bi-briefcase-fill' : ($hariStatus === 'libur' ? 'bi-calendar-x' : 'bi-calendar-week');
    $hariLabel = $hariStatus === 'aktif' ? 'Hari Kerja' : ($hariStatus === 'libur' ? 'Libur Sekolah' : 'Akhir Pekan');

    $bulanAngka = (int) date('n', strtotime($bulanAwal));
    $bulanTahun = (int) date('Y', strtotime($bulanAwal));
    $bulanLabel = buat_tanggal(date('F Y', strtotime($bulanAwal)));
    $rekapUrl = url('admin/presensi/rekap?bulan=' . $bulanAngka . '&tahun=' . $bulanTahun);

    $pendingItems = [
        [
            'key' => 'dinas',
            'label' => 'Dinas Luar',
            'count' => (int) ($pendingReview['dinas'] ?? 0),
            'url' => url('admin/presensi/dinas'),
            'icon' => 'bi-briefcase-fill',
            'class' => 'text-indigo-700 bg-indigo-50 border-indigo-200 hover:bg-indigo-100',
            'num' => 'text-indigo-700',
        ],
        [
            'key' => 'koreksi',
            'label' => 'Koreksi Absen',
            'count' => (int) ($pendingReview['koreksi'] ?? 0),
            'url' => url('admin/presensi/koreksi'),
            'icon' => 'bi-pencil-square',
            'class' => 'text-amber-700 bg-amber-50 border-amber-200 hover:bg-amber-100',
            'num' => 'text-amber-700',
        ],
        [
            'key' => 'izin',
            'label' => 'Izin / Cuti',
            'count' => (int) ($pendingReview['izin'] ?? 0),
            'url' => url('admin/presensi/izin'),
            'icon' => 'bi-envelope-paper-fill',
            'class' => 'text-sky-700 bg-sky-50 border-sky-200 hover:bg-sky-100',
            'num' => 'text-sky-700',
        ],
        [
            'key' => 'verifikasi',
            'label' => 'Verifikasi Presensi',
            'count' => (int) ($pendingReview['verifikasi'] ?? 0),
            'url' => url('admin/presensi/verifikasi'),
            'icon' => 'bi-patch-check-fill',
            'class' => 'text-emerald-700 bg-emerald-50 border-emerald-200 hover:bg-emerald-100',
            'num' => 'text-emerald-700',
        ],
        [
            'key' => 'wajah',
            'label' => 'Registrasi Wajah',
            'count' => (int) ($pendingReview['wajah'] ?? 0),
            'url' => url('admin/presensi/wajah'),
            'icon' => 'bi-person-bounding-box',
            'class' => 'text-purple-700 bg-purple-50 border-purple-200 hover:bg-purple-100',
            'num' => 'text-purple-700',
        ],
    ];

    $statusRankMap = [
        'HADIR' => 1,
        'TERLAMBAT' => 2,
        'VERIFIKASI' => 3,
        'IZIN_SAKIT' => 4,
        'DINAS' => 5,
        'WFH' => 6,
        'ALPHA' => 7,
        'DITOLAK' => 8,
        'LIBUR' => 9,
        'FUTURE' => 10,
        'LAINNYA' => 11,
    ];

    $fotoUrl = function ($path) {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        if (strpos($path, 'uploads/') === false) {
            $path = 'uploads/absen/' . $path;
        }
        $absolute = public_path() . '/' . $path;
        $version = file_exists($absolute) ? filemtime($absolute) : time();
        return url($path) . '?v=' . $version;
    };

    $pageConfig = [
        'csrf' => csrf_token(),
        'date' => $date,
        'dateLabel' => date('d/m/Y', strtotime($date)),
        'isToday' => (bool) $isToday,
        'primary' => \App\Libraries\Theme::metaColor('primary'),
        'urls' => [
            'recalculate' => url('admin/presensi/recalculate-day'),
        ],
    ];
@endphp

<div x-data="presensiMonitor()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
    class="px-4 pb-8 pt-4 md:px-6 md:pt-6">

    <x-ui.page-header
        title="Monitoring Presensi Guru"
        icon="bi-calendar2-check-fill"
        tone="blue"
        subtitle="{{ $salam }} — Pantau kehadiran, keterlambatan, antrean verifikasi, dan rekap bulanan"
        :crumbs="['Monitoring Presensi']">
        <x-slot:actions>
            <button type="button" @click="$store.modals.open('#policyModal')"
                class="inline-flex items-center gap-1.5 rounded-xl border border-sky-200 bg-sky-50 px-3.5 py-2 text-xs font-bold text-sky-700 transition-colors hover:bg-sky-100">
                <i class="bi bi-shield-check"></i> Kebijakan
            </button>
            <a href="{{ $rekapUrl }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-700 no-underline transition-colors hover:bg-emerald-100">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i> Rekap Bulan
            </a>
            <a href="{{ url('admin/presensi/manual') }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white no-underline shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                <i class="bi bi-plus-circle-fill"></i> Absen Manual
            </a>
            <button type="button" @click="hitungUlang()" :disabled="recalculating"
                class="inline-flex items-center gap-1.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-700 transition-colors hover:bg-amber-100 disabled:opacity-60"
                title="Hitung ulang status presensi (tepat waktu/terlambat/denda) memakai toleransi dan jadwal aktif">
                <span x-show="recalculating" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-amber-500/40 border-t-amber-600"></span>
                <i x-show="!recalculating" class="bi bi-arrow-repeat"></i> Hitung Ulang
            </button>
            <button type="button" @click="reloadPage()"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500 transition-colors hover:bg-slate-100"
                title="Segarkan">
                <i class="bi bi-arrow-clockwise text-sm"></i>
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- Tanggal & aksi -->
    <div class="mt-4 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-xs">
        <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-center">
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-100 p-1">
                    <a href="?date={{ $prevDate }}"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 no-underline transition-all hover:bg-white hover:shadow-xs"
                        title="Hari sebelumnya">
                        <i class="bi bi-chevron-left text-sm"></i>
                    </a>
                    <a href="?date={{ date('Y-m-d') }}"
                        class="rounded-lg px-3 py-1.5 text-xs font-bold no-underline transition-all {{ $isToday ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-700 hover:bg-white' }}">
                        <i class="bi bi-calendar-check mr-1"></i>Hari Ini
                    </a>
                    <a href="?date={{ $nextDate }}"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 no-underline transition-all hover:bg-white hover:shadow-xs"
                        title="Hari berikutnya">
                        <i class="bi bi-chevron-right text-sm"></i>
                    </a>
                </div>

                <form action="" method="get" class="flex items-center">
                    <div class="relative">
                        <input type="date" name="date" value="{{ $date }}" @change="$el.form.submit()"
                            class="cursor-pointer rounded-xl border border-slate-200 bg-slate-50 py-1.5 pl-8 pr-3 text-xs font-semibold text-slate-700 transition-all focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="bi bi-calendar3 pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                    </div>
                </form>

                <span class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold {{ $hariStatus === 'aktif' ? 'border border-emerald-200 bg-emerald-50 text-emerald-700' : 'border border-amber-200 bg-amber-50 text-amber-700' }}">
                    <i class="bi {{ $hariIcon }}"></i>
                    {{ $tanggalLabel }}
                    <span class="opacity-70">&middot; {{ $hariLabel }}</span>
                </span>
            </div>
        </div>
    </div>

    @if ($isFuture)
        <div class="mt-4 flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3" role="status">
            <i class="bi bi-calendar-plus-fill mt-0.5 shrink-0 text-lg text-sky-600"></i>
            <div class="text-xs leading-relaxed text-sky-900">
                <b>Tanggal ini belum berjalan.</b> Presensi belum dapat diisi, sehingga guru tanpa data tidak dihitung sebagai alpha atau belum absen.
            </div>
        </div>
    @elseif (! $isWorkingDay)
        <div class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3" role="status">
            <i class="bi bi-calendar-x-fill mt-0.5 shrink-0 text-lg text-amber-600"></i>
            <div class="text-xs leading-relaxed text-amber-900">
                <b>{{ $hariLabel }}{{ $hariKeterangan ? ' — ' . $hariKeterangan : '' }}.</b>
                Tidak ada kewajiban presensi pada tanggal ini; guru tanpa data tidak dihitung sebagai alpha atau belum absen.
            </div>
        </div>
    @endif

    @if (! empty($peringatanKalender))
        <div class="mt-4 flex flex-col justify-between gap-3 rounded-2xl border border-amber-200 bg-white px-4 py-3 sm:flex-row sm:items-center" role="alert">
            <div class="flex items-start gap-3">
                <i class="bi bi-exclamation-triangle-fill mt-0.5 shrink-0 text-lg text-amber-500"></i>
                <div class="text-xs leading-relaxed text-slate-700">
                    <b class="text-amber-800">Kalender libur perlu diperiksa.</b> {{ $peringatanKalender }}
                </div>
            </div>
            <a href="{{ url('admin/kalender') }}"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 no-underline transition-colors hover:bg-amber-100">
                <i class="bi bi-calendar3"></i> Buka Kalender
            </a>
        </div>
    @endif

    <!-- Antrean persetujuan -->
    <div class="mt-4">
        <x-ui.card title="Antrean Persetujuan" icon="bi-inbox-fill"
            subtitle="Seluruh pengajuan yang menunggu tindakan admin"
            tone="{{ $pendingReview['total'] > 0 ? 'amber' : 'slate' }}">
            <x-slot:meta>
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-extrabold {{ $pendingReview['total'] > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-50 text-emerald-700' }}">
                    <i class="bi {{ $pendingReview['total'] > 0 ? 'bi-hourglass-split' : 'bi-check2-circle' }}"></i>
                    {{ $pendingReview['total'] > 0 ? $pendingReview['total'] . ' menunggu' : 'Tidak ada antrean' }}
                </span>
            </x-slot:meta>

            <div class="grid grid-cols-2 gap-2.5 lg:grid-cols-5">
                @foreach ($pendingItems as $item)
                    @php $hasPending = $item['count'] > 0; @endphp
                    <a href="{{ $item['url'] }}"
                        class="flex items-center justify-between gap-2 rounded-xl border px-3 py-2.5 no-underline transition-colors {{ $hasPending ? $item['class'] : 'border-slate-200 bg-slate-50 text-slate-400 hover:bg-slate-100' }}">
                        <span class="flex min-w-0 items-center gap-1.5 text-[11px] font-bold">
                            <i class="bi {{ $item['icon'] }} shrink-0"></i>
                            <span class="truncate">{{ $item['label'] }}</span>
                        </span>
                        <span class="shrink-0 text-sm font-extrabold {{ $hasPending ? $item['num'] : 'text-slate-400' }}">{{ $item['count'] }}</span>
                    </a>
                @endforeach
            </div>
        </x-ui.card>
    </div>

    <!-- Tabel monitoring -->
    <div class="mt-4">
        <x-ui.card title="Daftar Presensi Guru" icon="bi-table" subtitle="{{ $tanggalLabel }}" bodyClass="p-0">
            <x-slot:meta>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600">
                    <span x-text="visible"></span> / {{ count($users) }} guru
                </span>
            </x-slot:meta>

            <x-slot:toolbar>
                <div class="flex flex-col gap-3">
                    <div class="relative w-full sm:w-72">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                        <input type="text" x-ref="search" x-model="query" placeholder="Cari nama atau NIP guru..." autocomplete="off"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-8 text-xs font-semibold text-slate-800 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="button" x-show="query !== ''" x-cloak @click="query = ''; $refs.search.focus()" title="Hapus pencarian"
                            class="absolute right-2 top-1/2 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-200/70 hover:text-slate-700">
                            <i class="bi bi-x text-xs"></i>
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 overflow-x-auto text-xs">
                        <span class="mr-1 shrink-0 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            <i class="bi bi-funnel mr-0.5"></i> Status:
                        </span>
                        @php
                            $pills = [
                                ['key' => 'ALL', 'label' => 'Semua', 'tone' => 'slate'],
                                ['key' => 'HADIR', 'label' => 'Hadir', 'icon' => 'bi-check-circle-fill', 'tone' => 'emerald'],
                                ['key' => 'TERLAMBAT', 'label' => 'Terlambat', 'icon' => 'bi-clock-history', 'tone' => 'amber'],
                                ['key' => 'IZIN_SAKIT', 'label' => 'Izin / Sakit', 'icon' => 'bi-envelope-paper-fill', 'tone' => 'sky'],
                                ['key' => 'DINAS', 'label' => 'Dinas Luar', 'icon' => 'bi-briefcase-fill', 'tone' => 'indigo'],
                                ['key' => 'WFH', 'label' => 'WFH', 'icon' => 'bi-laptop', 'tone' => 'teal'],
                                ['key' => 'VERIFIKASI', 'label' => 'Verifikasi', 'icon' => 'bi-patch-question-fill', 'tone' => 'amber'],
                                ['key' => 'ALPHA', 'label' => 'Belum Absen / Alpha', 'icon' => 'bi-x-circle-fill', 'tone' => 'rose'],
                            ];
                        @endphp
                        @foreach ($pills as $pill)
                            <button type="button" @click="setStatus('{{ $pill['key'] }}')"
                                :class="status === '{{ $pill['key'] }}' ? 'ring-2 ring-blue-500/40 ring-offset-1' : ''"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-1.5 text-[11px] font-bold transition-all
                                    {{ ($pill['tone'] ?? '') === 'slate' ? 'border-slate-200 bg-slate-100 text-slate-700 hover:bg-slate-200' : '' }}
                                    {{ ($pill['tone'] ?? '') === 'emerald' ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : '' }}
                                    {{ ($pill['tone'] ?? '') === 'amber' ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' : '' }}
                                    {{ ($pill['tone'] ?? '') === 'sky' ? 'border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100' : '' }}
                                    {{ ($pill['tone'] ?? '') === 'indigo' ? 'border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100' : '' }}
                                    {{ ($pill['tone'] ?? '') === 'teal' ? 'border-teal-200 bg-teal-50 text-teal-700 hover:bg-teal-100' : '' }}
                                    {{ ($pill['tone'] ?? '') === 'rose' ? 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' : '' }}">
                                @isset($pill['icon'])
                                    <i class="bi {{ $pill['icon'] }}"></i>
                                @endisset
                                {{ $pill['label'] }}
                                <span class="rounded-full bg-white/70 px-1.5 text-[10px] font-extrabold tabular-nums"
                                    x-text="counts['{{ $pill['key'] }}'] || 0"></span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </x-slot:toolbar>

            <div class="max-h-[70vh] overflow-auto">
                <table class="w-full border-collapse text-left text-[13px]">
                    <thead class="select-none bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="sticky left-0 top-0 z-30 w-12 bg-slate-50 px-2 py-3 text-center">No</th>
                            <th class="sticky left-12 top-0 z-30 min-w-[230px] bg-slate-50 px-4 py-3">
                                <button type="button" @click="sortBy('name')" class="inline-flex items-center gap-1 hover:text-blue-600" :class="sortKey === 'name' ? 'text-blue-600' : ''">
                                    Guru <i class="bi text-[10px]" :class="sortKey === 'name' ? (sortDir === 'asc' ? 'bi-arrow-down' : 'bi-arrow-up') : 'bi-arrow-down-up opacity-40'"></i>
                                </button>
                            </th>
                            <th class="sticky top-0 z-20 w-[92px] bg-slate-50 px-3 py-3 text-center">
                                <button type="button" @click="sortBy('in')" class="inline-flex items-center gap-1 hover:text-blue-600" :class="sortKey === 'in' ? 'text-blue-600' : ''">
                                    Masuk <i class="bi text-[10px]" :class="sortKey === 'in' ? (sortDir === 'asc' ? 'bi-arrow-down' : 'bi-arrow-up') : 'bi-arrow-down-up opacity-40'"></i>
                                </button>
                            </th>
                            <th class="sticky top-0 z-20 w-[92px] bg-slate-50 px-3 py-3 text-center">
                                <button type="button" @click="sortBy('out')" class="inline-flex items-center gap-1 hover:text-blue-600" :class="sortKey === 'out' ? 'text-blue-600' : ''">
                                    Pulang <i class="bi text-[10px]" :class="sortKey === 'out' ? (sortDir === 'asc' ? 'bi-arrow-down' : 'bi-arrow-up') : 'bi-arrow-down-up opacity-40'"></i>
                                </button>
                            </th>
                            <th class="sticky top-0 z-20 w-[118px] bg-slate-50 px-3 py-3 text-center">
                                <button type="button" @click="sortBy('status')" class="inline-flex items-center gap-1 hover:text-blue-600" :class="sortKey === 'status' ? 'text-blue-600' : ''">
                                    Status <i class="bi text-[10px]" :class="sortKey === 'status' ? (sortDir === 'asc' ? 'bi-arrow-down' : 'bi-arrow-up') : 'bi-arrow-down-up opacity-40'"></i>
                                </button>
                            </th>

                            <th class="sticky top-0 z-20 w-[34px] border-l border-r border-slate-200/80 bg-slate-100 px-2 py-3 text-center text-emerald-700" title="Hadir (tepat waktu) bulan {{ $bulanLabel }}">H</th>
                            <th class="sticky top-0 z-20 w-[34px] border-r border-slate-200/80 bg-slate-100 px-2 py-3 text-center text-amber-700" title="Terlambat bulan {{ $bulanLabel }}">T</th>
                            <th class="sticky top-0 z-20 w-[34px] border-r border-slate-200/80 bg-slate-100 px-2 py-3 text-center text-sky-700" title="Izin bulan {{ $bulanLabel }}">I</th>
                            <th class="sticky top-0 z-20 w-[34px] border-r border-slate-200/80 bg-slate-100 px-2 py-3 text-center text-sky-700" title="Sakit bulan {{ $bulanLabel }}">S</th>
                            <th class="sticky top-0 z-20 w-[34px] border-r border-slate-200/80 bg-slate-100 px-2 py-3 text-center text-purple-700" title="Cuti bulan {{ $bulanLabel }}">C</th>
                            <th class="sticky top-0 z-20 w-[34px] border-r border-slate-200/80 bg-slate-100 px-2 py-3 text-center text-indigo-700" title="Dinas luar bulan {{ $bulanLabel }}">D</th>
                            <th class="sticky top-0 z-20 w-[34px] border-r border-slate-200/80 bg-slate-100 px-2 py-3 text-center text-teal-700" title="WFH bulan {{ $bulanLabel }}">W</th>
                            <th class="sticky top-0 z-20 w-[34px] border-r border-slate-200/80 bg-slate-100 px-2 py-3 text-center text-rose-700" title="Alpha (tercatat + belum tercatat) bulan {{ $bulanLabel }}">A</th>

                            <th class="sticky top-0 z-20 w-[106px] border-r border-slate-200/80 bg-blue-50/70 px-3 py-3 text-center">
                                <button type="button" @click="sortBy('denda')" class="inline-flex items-center gap-1 text-blue-700">
                                    Denda <i class="bi text-[10px]" :class="sortKey === 'denda' ? (sortDir === 'asc' ? 'bi-arrow-down' : 'bi-arrow-up') : 'bi-arrow-down-up opacity-40'"></i>
                                </button>
                            </th>
                            <th class="sticky top-0 z-20 w-16 bg-slate-50 px-3 py-3 text-center" title="Poin harian dari presensi">Poin</th>
                            <th class="sticky top-0 z-20 w-[100px] bg-slate-50 px-3 py-3 text-center">Lokasi</th>
                            <th class="sticky top-0 z-20 w-16 bg-slate-50 px-3 py-3 text-center">Bukti</th>
                            <th class="sticky top-0 z-20 w-[150px] bg-slate-50 px-3 py-3 text-center">Keterangan</th>
                            <th class="sticky top-0 z-20 w-[88px] bg-slate-50 px-3 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody x-ref="tbody" class="divide-y divide-slate-100 bg-white">
                        @if (empty($users))
                            <tr>
                                <td colspan="19" class="px-6 py-12 text-center text-slate-400">
                                    <div class="mx-auto mb-2.5 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <i class="bi bi-people text-2xl"></i>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-700">Belum ada guru aktif</h4>
                                    <p class="mt-1 text-xs">Tambahkan pengguna dengan role guru terlebih dahulu.</p>
                                </td>
                            </tr>
                        @endif

                        @foreach ($users as $guruRow)
                            @php
                                $att = $map[$guruRow->id] ?? null;
                                $stat = $rekapBulanan[$guruRow->id] ?? [
                                    'hadir' => 0, 'telat' => 0, 'ijin' => 0, 'sakit' => 0,
                                    'cuti' => 0, 'dinas' => 0, 'wfh' => 0, 'alpha' => 0, 'pending' => 0,
                                ];

                                $statusRaw = $att ? strtoupper(trim((string) ($att->status_masuk ?? ''))) : null;

                                $filterCategory = 'LAINNYA';
                                $statusLabel = 'Tidak diketahui';
                                $badgeClass = 'bg-slate-100 text-slate-600 border border-slate-200';

                                if ($att === null) {
                                    if ($isFuture) {
                                        $filterCategory = 'FUTURE';
                                        $statusLabel = 'Masa Depan';
                                        $badgeClass = 'bg-slate-100 text-slate-500 border border-slate-200';
                                    } elseif (! $isWorkingDay) {
                                        $filterCategory = 'LIBUR';
                                        $statusLabel = 'Libur';
                                        $badgeClass = 'bg-slate-100 text-slate-500 border border-slate-200';
                                    } else {
                                        $filterCategory = 'ALPHA';
                                        $statusLabel = 'Belum Absen';
                                        $badgeClass = 'bg-rose-100 text-rose-700 border border-rose-200';
                                    }
                                } else {
                                    switch ($statusRaw) {
                                        case 'ONTIME':
                                            $filterCategory = 'HADIR';
                                            $statusLabel = 'Hadir';
                                            $badgeClass = 'bg-emerald-100 text-emerald-800 border border-emerald-200';
                                            break;
                                        case 'LATE':
                                            $filterCategory = 'TERLAMBAT';
                                            $statusLabel = 'Terlambat';
                                            $badgeClass = 'bg-amber-100 text-amber-800 border border-amber-200';
                                            break;
                                        case 'IJIN':
                                        case 'IZIN':
                                            $filterCategory = 'IZIN_SAKIT';
                                            $statusLabel = 'Izin';
                                            $badgeClass = 'bg-sky-100 text-sky-800 border border-sky-200';
                                            break;
                                        case 'SAKIT':
                                            $filterCategory = 'IZIN_SAKIT';
                                            $statusLabel = 'Sakit';
                                            $badgeClass = 'bg-sky-100 text-sky-800 border border-sky-200';
                                            break;
                                        case 'CUTI':
                                            $filterCategory = 'IZIN_SAKIT';
                                            $statusLabel = 'Cuti';
                                            $badgeClass = 'bg-purple-100 text-purple-800 border border-purple-200';
                                            break;
                                        case 'ALPHA':
                                        case 'ALPA':
                                            $filterCategory = 'ALPHA';
                                            $statusLabel = 'Alpha';
                                            $badgeClass = 'bg-rose-100 text-rose-700 border border-rose-200';
                                            break;
                                        case 'PENDING_VERIFICATION':
                                            $filterCategory = 'VERIFIKASI';
                                            $statusLabel = 'Menunggu Verifikasi';
                                            $badgeClass = 'bg-amber-100 text-amber-800 border border-amber-200';
                                            break;
                                        case 'DINAS_REJECTED':
                                            $filterCategory = 'DITOLAK';
                                            $statusLabel = 'Dinas Ditolak';
                                            $badgeClass = 'bg-rose-100 text-rose-700 border border-rose-200';
                                            break;
                                        case 'WFH':
                                            $filterCategory = 'WFH';
                                            $statusLabel = 'WFH';
                                            $badgeClass = 'bg-teal-100 text-teal-800 border border-teal-200';
                                            break;
                                        case 'DINAS_OUT':
                                            $filterCategory = 'DINAS';
                                            $statusLabel = 'Dinas (Pulang)';
                                            $badgeClass = 'bg-indigo-100 text-indigo-800 border border-indigo-200';
                                            break;
                                        default:
                                            if (strpos((string) $statusRaw, 'DINAS') !== false) {
                                                $filterCategory = 'DINAS';
                                                $statusLabel = $statusRaw === 'DINAS_PENDING' ? 'Dinas (Menunggu)' : 'Dinas';
                                                $badgeClass = 'bg-indigo-100 text-indigo-800 border border-indigo-200';
                                            } elseif ($statusRaw !== '') {
                                                $filterCategory = 'LAINNYA';
                                                $statusLabel = ucwords(strtolower(str_replace('_', ' ', $statusRaw)));
                                                $badgeClass = 'bg-slate-100 text-slate-600 border border-slate-200';
                                            }
                                    }
                                }

                                $statusRank = $statusRankMap[$filterCategory] ?? 11;

                                $jamMasuk = ($att && ! empty($att->jam_masuk)) ? date('H:i', strtotime($att->jam_masuk)) : '';
                                $jamPulang = ($att && ! empty($att->jam_pulang)) ? date('H:i', strtotime($att->jam_pulang)) : '';
                                $telatMenit = (int) ($att->telat_menit ?? 0);
                                $pulangCepat = (int) ($att->pulang_cepat_menit ?? 0);
                                $lemburMenit = (int) ($att->pulang_akhir_menit ?? 0);
                                $poinHarian = $att ? (float) ($att->points ?? 0) : 0.0;

                                $latIn = $att->lat_masuk ?? null;
                                $lngIn = $att->lng_masuk ?? null;
                                if ((! $latIn || ! $lngIn) && ! empty($att->lokasi_masuk)) {
                                    $coords = explode(',', (string) $att->lokasi_masuk);
                                    $latIn = $latIn ?: trim($coords[0] ?? '');
                                    $lngIn = $lngIn ?: trim($coords[1] ?? '');
                                }

                                $latOut = $att->lat_pulang ?? null;
                                $lngOut = $att->lng_pulang ?? null;
                                if ((! $latOut || ! $lngOut) && ! empty($att->lokasi_pulang)) {
                                    $coordsOut = explode(',', (string) $att->lokasi_pulang);
                                    $latOut = $latOut ?: trim($coordsOut[0] ?? '');
                                    $lngOut = $lngOut ?: trim($coordsOut[1] ?? '');
                                }

                                $locInName = trim((string) ($att->nama_lokasi_masuk ?? ''));
                                $locOutName = trim((string) ($att->nama_lokasi_pulang ?? ''));

                                $fotoInUrl = $att ? $fotoUrl($att->foto_masuk ?? '') : '';
                                $fotoOutUrl = $att ? $fotoUrl($att->foto_pulang ?? '') : '';

                                $catatan = $att ? trim((string) ($att->keterangan ?? '')) : '';
                                $catatanDenda = $att ? trim((string) ($att->keterangan_denda ?? '')) : '';

                                $dendaHarianVal = (float) ($dendaMap[$guruRow->id] ?? 0);
                                $dendaEstimasiVal = (float) ($alphaDendaMap[$guruRow->id] ?? 0);
                                $dendaBulanVal = (float) ($dendaTotalMap[$guruRow->id] ?? 0);

                                $alphaBulan = max((int) ($stat['alpha'] ?? 0), (int) ($alphaMap[$guruRow->id] ?? 0));
                                $alphaMissing = (int) ($alphaMissingMap[$guruRow->id] ?? 0);

                                $dendaTooltip = 'Denda harian: Rp ' . number_format($dendaHarianVal, 0, ',', '.')
                                    . ' · Estimasi alpha: Rp ' . number_format($dendaEstimasiVal, 0, ',', '.');
                                $alphaTooltip = 'Alpha tercatat: ' . (int) ($stat['alpha'] ?? 0) . ' hari'
                                    . ($alphaMissing > 0 ? ' · Belum tercatat: ' . $alphaMissing . ' hari' : '');

                                $inStatusText = ! $att ? 'Tidak ada data' : ($jamMasuk === '' ? 'Tidak ada data' : ($telatMenit > 0 ? 'Terlambat ' . $telatMenit . ' menit' : ($statusRaw === 'PENDING_VERIFICATION' ? 'Menunggu verifikasi' : ($statusRaw === 'DINAS_PENDING' ? 'Menunggu persetujuan dinas' : (strpos((string) $statusRaw, 'DINAS') !== false ? 'Dinas luar' : 'Tepat waktu')))));
                                $outStatusText = ! $att ? 'Tidak ada data' : ($jamPulang === '' ? 'Belum absen pulang' : ($pulangCepat > 0 ? 'Pulang cepat ' . $pulangCepat . ' menit' : ($lemburMenit > 0 ? 'Lembur ' . $lemburMenit . ' menit' : 'Pulang tercatat')));

                                $keteranganHtml = '<span class="text-slate-300">-</span>';
                                if ($att && $telatMenit > 0) {
                                    $keteranganHtml = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200"><i class="bi bi-arrow-up-right"></i> +' . $telatMenit . ' mnt</span>';
                                } elseif ($att && $pulangCepat > 0) {
                                    $keteranganHtml = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-orange-50 text-orange-700 border border-orange-200"><i class="bi bi-arrow-down-left"></i> -' . $pulangCepat . ' mnt</span>';
                                } elseif ($att && in_array($statusRaw, ['IJIN', 'IZIN', 'SAKIT', 'CUTI'], true)) {
                                    $keteranganHtml = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200" title="' . e($catatan) . '"><i class="bi bi-file-text"></i> ' . e($statusLabel) . '</span>';
                                } elseif ($att && $statusRaw === 'PENDING_VERIFICATION') {
                                    $keteranganHtml = '<a href="' . url('admin/presensi/verifikasi') . '" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 no-underline hover:bg-amber-100"><i class="bi bi-patch-question"></i> Verifikasi</a>';
                                } elseif ($att && $statusRaw === 'DINAS_REJECTED') {
                                    $keteranganHtml = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="' . e($catatan) . '"><i class="bi bi-x-octagon"></i> Ditolak</span>';
                                } elseif ($att && strpos((string) $statusRaw, 'DINAS') !== false) {
                                    $keteranganHtml = '<a href="' . url('admin/presensi/dinas') . '" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-800 border border-indigo-200 no-underline hover:bg-indigo-100" title="' . e($catatan ?: 'Tugas dinas luar') . '"><i class="bi bi-briefcase"></i> Dinas Luar</a>';
                                } elseif ($att && $statusRaw === 'WFH') {
                                    $keteranganHtml = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-50 text-teal-800 border border-teal-200" title="' . e($catatan) . '"><i class="bi bi-laptop"></i> Bekerja WFH</span>';
                                } elseif ($att && $statusRaw === 'ONTIME') {
                                    $keteranganHtml = '<span class="text-emerald-600 text-[11px] font-bold inline-flex items-center gap-1"><i class="bi bi-check2"></i> Tepat waktu</span>';
                                } elseif (! $att && ! $isFuture && $isWorkingDay) {
                                    $keteranganHtml = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200"><i class="bi bi-dash-circle"></i> Belum absen</span>';
                                }

                                $dotClass = 'bg-slate-300';
                                if ($filterCategory === 'HADIR') $dotClass = 'bg-emerald-500';
                                elseif ($filterCategory === 'TERLAMBAT' || $filterCategory === 'VERIFIKASI') $dotClass = 'bg-amber-500';
                                elseif ($filterCategory === 'DINAS') $dotClass = 'bg-indigo-500';
                                elseif ($filterCategory === 'WFH') $dotClass = 'bg-teal-500';
                                elseif ($filterCategory === 'IZIN_SAKIT') $dotClass = 'bg-sky-500';
                                elseif ($filterCategory === 'ALPHA' || $filterCategory === 'DITOLAK') $dotClass = 'bg-rose-500';

                                $userAvatar = \App\Services\AvatarService::tampilan($guruRow->foto ?? '', $guruRow->nama_guru ?? '', \App\Libraries\Theme::metaColor('primary'));
                                $avatarFallback = \App\Services\AvatarService::cadangan();

                                $detailData = [
                                    'uid' => (int) $guruRow->id,
                                    'name' => $guruRow->nama_guru,
                                    'nip' => $guruRow->nip ?: '-',
                                    'avatar' => $userAvatar,
                                    'date' => date('d/m/Y', strtotime($date)),
                                    'status' => $statusLabel,
                                    'status_class' => $badgeClass,
                                    'in' => $jamMasuk,
                                    'out' => $jamPulang,
                                    'in_status' => $inStatusText,
                                    'out_status' => $outStatusText,
                                    'late' => $telatMenit,
                                    'early' => $pulangCepat,
                                    'overtime' => $lemburMenit,
                                    'points' => $poinHarian,
                                    'denda_day' => (float) ($att->denda ?? 0),
                                    'denda_month' => $dendaBulanVal,
                                    'denda_note' => $catatanDenda,
                                    'alpha' => $alphaBulan,
                                    'alpha_missing' => $alphaMissing,
                                    'loc_in' => $locInName,
                                    'lat_in' => $latIn,
                                    'lng_in' => $lngIn,
                                    'loc_out' => $locOutName,
                                    'lat_out' => $latOut,
                                    'lng_out' => $lngOut,
                                    'note' => $catatan,
                                    'photo_in' => $fotoInUrl,
                                    'photo_out' => $fotoOutUrl,
                                    't_in' => $jamMasuk !== '' ? $jamMasuk . ' WITA' : '-',
                                    't_out' => $jamPulang !== '' ? $jamPulang . ' WITA' : '-',
                                    'has_photo' => ($fotoInUrl !== '' || $fotoOutUrl !== ''),
                                ];

                                $photoData = [
                                    'in' => $fotoInUrl,
                                    'out' => $fotoOutUrl,
                                    'name' => $guruRow->nama_guru,
                                    'tIn' => $detailData['t_in'],
                                    'tOut' => $detailData['t_out'],
                                    'loc' => $locInName ?: '-',
                                ];

                                $sortIn = $jamMasuk !== '' ? ((int) substr($jamMasuk, 0, 2) * 60 + (int) substr($jamMasuk, 3, 2)) : '';
                                $sortOut = $jamPulang !== '' ? ((int) substr($jamPulang, 0, 2) * 60 + (int) substr($jamPulang, 3, 2)) : '';
                                $rowSearch = strtolower(trim(($guruRow->nama_guru ?? '') . ' ' . ($guruRow->nip ?? '')));
                            @endphp
                            <tr class="group teacher-row transition-colors"
                                data-status="{{ $filterCategory }}"
                                data-search="{{ $rowSearch }}"
                                data-sort-name="{{ strtolower((string) $guruRow->nama_guru) }}"
                                data-sort-in="{{ $sortIn }}"
                                data-sort-out="{{ $sortOut }}"
                                data-sort-status="{{ (int) $statusRank }}"
                                data-sort-denda="{{ $dendaBulanVal }}">

                                <td class="sticky left-0 z-10 bg-white px-2 py-3 text-center text-xs font-bold text-slate-400 transition-colors group-hover:bg-blue-50">
                                    <span data-rownum>{{ $loop->iteration }}</span>
                                </td>

                                <td class="sticky left-12 z-10 bg-white px-4 py-3 transition-colors group-hover:bg-blue-50">
                                    <div class="flex items-center gap-3">
                                        <div class="relative shrink-0">
                                            <img src="{{ $userAvatar }}" class="h-[38px] w-[38px] shrink-0 rounded-xl border border-slate-200 object-cover" alt="Avatar {{ $guruRow->nama_guru }}" data-avatar-fallback="{{ $avatarFallback }}" onerror="this.onerror=null;this.src=this.dataset.avatarFallback">
                                            <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white {{ $dotClass }}" title="{{ $statusLabel }}"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="max-w-[220px] truncate font-bold text-slate-800" title="{{ $guruRow->nama_guru }}">
                                                {{ $guruRow->nama_guru }}
                                            </div>
                                            <div class="truncate text-[11px] text-slate-400">
                                                NIP: {{ $guruRow->nip ?: '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-3 py-3 text-center">
                                    @if ($jamMasuk !== '')
                                        <div class="text-xs font-extrabold tracking-tight text-slate-800">{{ $jamMasuk }}</div>
                                        @if ($telatMenit > 0)
                                            <span class="text-[10px] font-bold text-rose-500">+{{ $telatMenit }}m</span>
                                        @else
                                            <span class="text-[10px] font-semibold text-slate-400">WITA</span>
                                        @endif
                                    @else
                                        <span class="font-bold text-slate-300">--:--</span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-3 py-3 text-center">
                                    @if ($jamPulang !== '')
                                        <div class="text-xs font-extrabold tracking-tight text-slate-800">{{ $jamPulang }}</div>
                                        @if ($pulangCepat > 0)
                                            <span class="text-[10px] font-bold text-orange-500">-{{ $pulangCepat }}m</span>
                                        @else
                                            <span class="text-[10px] font-semibold text-slate-400">WITA</span>
                                        @endif
                                    @else
                                        <span class="font-bold text-slate-300">--:--</span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-3 py-3 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $badgeClass }}">{{ $statusLabel }}</span>
                                </td>

                                <td class="border-l border-r border-slate-100 bg-slate-50/60 px-2 py-3 text-center text-xs font-extrabold {{ $stat['hadir'] > 0 ? 'text-emerald-600' : 'text-slate-300' }}">
                                    {{ $stat['hadir'] > 0 ? (int) $stat['hadir'] : '-' }}
                                </td>
                                <td class="border-r border-slate-100 bg-slate-50/60 px-2 py-3 text-center text-xs font-extrabold {{ $stat['telat'] > 0 ? 'text-amber-600' : 'text-slate-300' }}">
                                    {{ $stat['telat'] > 0 ? (int) $stat['telat'] : '-' }}
                                </td>
                                <td class="border-r border-slate-100 bg-slate-50/60 px-2 py-3 text-center text-xs font-semibold {{ $stat['ijin'] > 0 ? 'text-sky-600' : 'text-slate-300' }}">
                                    {{ $stat['ijin'] > 0 ? (int) $stat['ijin'] : '-' }}
                                </td>
                                <td class="border-r border-slate-100 bg-slate-50/60 px-2 py-3 text-center text-xs font-semibold {{ $stat['sakit'] > 0 ? 'text-sky-600' : 'text-slate-300' }}">
                                    {{ $stat['sakit'] > 0 ? (int) $stat['sakit'] : '-' }}
                                </td>
                                <td class="border-r border-slate-100 bg-slate-50/60 px-2 py-3 text-center text-xs font-semibold {{ $stat['cuti'] > 0 ? 'text-purple-600' : 'text-slate-300' }}">
                                    {{ $stat['cuti'] > 0 ? (int) $stat['cuti'] : '-' }}
                                </td>
                                <td class="border-r border-slate-100 bg-slate-50/60 px-2 py-3 text-center text-xs font-semibold {{ $stat['dinas'] > 0 ? 'text-indigo-600' : 'text-slate-300' }}">
                                    {{ $stat['dinas'] > 0 ? (int) $stat['dinas'] : '-' }}
                                </td>
                                <td class="border-r border-slate-100 bg-slate-50/60 px-2 py-3 text-center text-xs font-semibold {{ $stat['wfh'] > 0 ? 'text-teal-600' : 'text-slate-300' }}">
                                    {{ $stat['wfh'] > 0 ? (int) $stat['wfh'] : '-' }}
                                </td>
                                <td class="border-r border-slate-100 bg-slate-50/60 px-2 py-3 text-center text-xs font-extrabold {{ $alphaBulan > 0 ? 'text-rose-600' : 'text-slate-300' }}" title="{{ $alphaTooltip }}">
                                    {{ $alphaBulan > 0 ? $alphaBulan : '-' }}
                                </td>

                                <td class="whitespace-nowrap bg-blue-50/40 px-3 py-3 text-center text-xs font-bold" title="{{ $dendaTooltip }}">
                                    @if ($dendaBulanVal > 0)
                                        <span class="text-rose-600">Rp {{ number_format($dendaBulanVal, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-3 py-3 text-center text-xs font-bold">
                                    @if ($poinHarian < 0)
                                        <span class="text-rose-600">{{ rtrim(rtrim(number_format($poinHarian, 1, ',', '.'), '0'), ',') }}</span>
                                    @elseif ($poinHarian > 0)
                                        <span class="text-emerald-600">+{{ rtrim(rtrim(number_format($poinHarian, 1, ',', '.'), '0'), ',') }}</span>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>

                                <td class="px-3 py-3 text-center">
                                    @if ($latIn && $lngIn)
                                        <a href="https://www.google.com/maps?q={{ $latIn }},{{ $lngIn }}" target="_blank" rel="noopener"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-rose-50 text-rose-600 transition-colors hover:bg-rose-100"
                                            title="Buka lokasi masuk di Google Maps">
                                            <i class="bi bi-geo-alt-fill text-xs"></i>
                                        </a>
                                        @if ($locInName !== '')
                                            <div class="mx-auto mt-1 max-w-[80px] truncate text-[10px] font-bold text-slate-500" title="{{ $locInName }}">
                                                {{ $locInName }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-3 py-3 text-center">
                                    @if ($fotoInUrl !== '' || $fotoOutUrl !== '')
                                        <button type="button"
                                            data-photo="{{ json_encode($photoData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                                            @click="openPhoto($el.dataset.photo)"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition-colors hover:bg-blue-100"
                                            title="Lihat foto presensi">
                                            <i class="bi bi-camera-fill text-xs"></i>
                                        </button>
                                    @else
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-300" title="Tidak ada foto">
                                            <i class="bi bi-camera-video-off text-xs"></i>
                                        </span>
                                    @endif
                                </td>

                                <td class="px-3 py-3 text-center">
                                    {!! $keteranganHtml !!}
                                </td>

                                <td class="px-3 py-3 text-center">
                                    <button type="button"
                                        data-detail="{{ json_encode($detailData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                                        @click="openDetail($el.dataset.detail)"
                                        class="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-[11px] font-bold text-blue-700 transition-colors hover:bg-blue-100">
                                        <i class="bi bi-eye"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (! empty($users))
                <div x-show="visible === 0" x-cloak class="border-t border-slate-100 p-10 text-center text-slate-400">
                    <div class="mx-auto mb-2.5 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i class="bi bi-search text-2xl"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-700">Tidak ada data yang cocok</h4>
                    <p class="mt-1 text-xs text-slate-400">Sesuaikan kata kunci pencarian atau ganti filter status.</p>
                    <button type="button" @click="resetFilters()"
                        class="mt-3 inline-flex items-center gap-1 rounded-xl border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-600 transition-colors hover:bg-blue-100">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                    </button>
                </div>
            @endif

            <x-slot:footer>
                <div class="flex flex-col items-center justify-between gap-3 text-xs font-medium text-slate-500 sm:flex-row">
                    <div>
                        Menampilkan <b x-text="visible"></b> dari <b>{{ count($users) }}</b> guru aktif &middot; <b>{{ date('d/m/Y', strtotime($date)) }}</b>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-[11px]">
                        <span class="inline-flex items-center gap-1"><b class="text-emerald-600">H</b> Hadir</span>
                        <span class="inline-flex items-center gap-1"><b class="text-amber-600">T</b> Telat</span>
                        <span class="inline-flex items-center gap-1"><b class="text-sky-600">I</b> Izin</span>
                        <span class="inline-flex items-center gap-1"><b class="text-sky-600">S</b> Sakit</span>
                        <span class="inline-flex items-center gap-1"><b class="text-purple-600">C</b> Cuti</span>
                        <span class="inline-flex items-center gap-1"><b class="text-indigo-600">D</b> Dinas</span>
                        <span class="inline-flex items-center gap-1"><b class="text-teal-600">W</b> WFH</span>
                        <span class="inline-flex items-center gap-1"><b class="text-rose-600">A</b> Alpha</span>
                    </div>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </div>

    <!-- Alert kehadiran rendah -->
    @if (! empty($alertKehadiran))
        <div class="mt-4 overflow-hidden rounded-2xl border border-red-200 bg-red-50 shadow-xs" role="alert">
            <div class="flex flex-col justify-between gap-4 p-4 md:flex-row md:items-center">
                <div class="flex items-center gap-3.5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-600 text-white shadow-xs">
                        <i class="bi bi-exclamation-triangle-fill text-lg"></i>
                    </div>
                    <div>
                        <div class="text-sm font-extrabold text-red-950">
                            {{ count($alertKehadiran) }} Guru di Bawah Standar Kehadiran ({{ $minKehadiranPersen }}%)
                        </div>
                        <div class="text-xs text-red-800">
                            Berdasarkan hari efektif bulan {{ $bulanLabel }}. Klik detail untuk melihat rincian.
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="alertOpen = !alertOpen" :aria-expanded="alertOpen ? 'true' : 'false'"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-50">
                        <i class="bi bi-chevron-down text-xs transition-transform" :class="alertOpen ? 'rotate-180' : ''"></i>
                        <span>Detail Rincian</span>
                    </button>
                    <a href="{{ $rekapUrl }}"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-3 py-1.5 text-xs font-bold text-white no-underline transition-colors hover:bg-red-700">
                        Rekap Bulanan
                    </a>
                </div>
            </div>

            <div x-show="alertOpen" x-cloak class="border-t border-red-200/60 bg-white/60">
                <div class="overflow-x-auto p-4">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-red-100 text-[11px] font-bold uppercase text-slate-500">
                                <th class="pb-2 text-left">No</th>
                                <th class="pb-2 text-left">Nama Guru</th>
                                <th class="pb-2 text-center">Hadir</th>
                                <th class="pb-2 text-center">Efektif</th>
                                <th class="pb-2 text-center">Alpha</th>
                                <th class="w-[180px] pb-2 text-center">Persentase</th>
                                <th class="pb-2 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-red-100/60">
                            @foreach ($alertKehadiran as $i => $ak)
                                @php
                                    $badgeClassAk = $ak['status'] === 'kritis' ? 'bg-red-600 text-white' : 'bg-amber-500 text-white';
                                    $badgeTextAk = $ak['status'] === 'kritis' ? 'KRITIS' : 'PERHATIAN';
                                    $barClassAk = $ak['persen'] < 75 ? 'bg-red-500' : 'bg-amber-500';
                                @endphp
                                <tr class="transition-colors hover:bg-red-50/50">
                                    <td class="py-2 font-semibold text-slate-400">{{ $i + 1 }}</td>
                                    <td class="py-2 font-bold text-slate-800">{{ $ak['nama_guru'] }}</td>
                                    <td class="py-2 text-center font-semibold text-slate-700">{{ (int) $ak['hadir'] }}</td>
                                    <td class="py-2 text-center text-slate-500">{{ (int) $ak['efektif'] }}</td>
                                    <td class="py-2 text-center font-bold text-red-600">{{ (int) $ak['alpha'] }}</td>
                                    <td class="py-2 text-center">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200">
                                                <div class="h-full rounded-full {{ $barClassAk }}" style="width:{{ min($ak['persen'], 100) }}%"></div>
                                            </div>
                                            <span class="text-[11px] font-extrabold {{ $ak['status'] === 'kritis' ? 'text-red-600' : 'text-amber-600' }}">
                                                {{ number_format($ak['persen'], 1) }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-2 text-center">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-extrabold {{ $badgeClassAk }}">{{ $badgeTextAk }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal detail presensi -->
    <x-ui.modal id="detailModal" size="max-w-2xl">
        <x-slot:header>
            <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <div class="flex min-w-0 items-center gap-3">
                    <img :src="detail.avatar || ''" alt="Avatar" class="h-[38px] w-[38px] shrink-0 rounded-xl border border-slate-200 object-cover">
                    <div class="min-w-0">
                        <h5 class="truncate text-sm font-bold text-slate-800" x-text="detail.name || '-'">-</h5>
                        <p class="text-[11px] text-slate-400" x-text="'NIP: ' + (detail.nip || '-') + ' · ' + (detail.date || '-')">-</p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                        :class="detail.status_class || 'bg-slate-100 text-slate-600 border border-slate-200'"
                        x-text="detail.status || '-'">-</span>
                    <button type="button" @click="$store.modals.close('#detailModal')" aria-label="Tutup"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
            </div>
        </x-slot:header>

        <div class="max-h-[75vh] space-y-4 overflow-y-auto p-5">
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Jam Masuk</div>
                    <div class="mt-1.5 text-lg font-extrabold leading-none text-slate-800" x-text="detail.in || '--:--'">--:--</div>
                    <div class="mt-1 text-[11px] text-slate-500" x-text="detail.in_status || '-'">-</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Jam Pulang</div>
                    <div class="mt-1.5 text-lg font-extrabold leading-none text-slate-800" x-text="detail.out || '--:--'">--:--</div>
                    <div class="mt-1 text-[11px] text-slate-500" x-text="detail.out_status || '-'">-</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Terlambat</div>
                    <div class="mt-1 text-sm font-extrabold text-rose-600" x-text="fmtMin(detail.late)">0 mnt</div>
                </div>
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pulang Cepat</div>
                    <div class="mt-1 text-sm font-extrabold text-orange-600" x-text="fmtMin(detail.early)">0 mnt</div>
                </div>
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Lembur</div>
                    <div class="mt-1 text-sm font-extrabold text-emerald-600" x-text="fmtMin(detail.overtime)">0 mnt</div>
                </div>
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Poin Harian</div>
                    <div class="mt-1 text-sm font-extrabold" :class="poinClass(detail.points)" x-text="poinLabel(detail.points)">0</div>
                </div>
            </div>

            <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-blue-500">Denda</div>
                        <div class="mt-1 text-xs text-blue-900">
                            Hari ini <b x-text="fmtRp(detail.denda_day)">Rp 0</b> &middot; Bulan ini <b x-text="fmtRp(detail.denda_month)">Rp 0</b>
                            &middot; Alpha bulan ini <b x-text="String(Number(detail.alpha) || 0)">0</b> hari
                        </div>
                    </div>
                    <i class="bi bi-cash-coin shrink-0 text-lg text-blue-400"></i>
                </div>
                <div x-show="detail.denda_note" x-cloak class="mt-2 rounded-lg border border-blue-100 bg-white/70 px-2.5 py-1.5 text-[11px] text-blue-800">
                    <i class="bi bi-info-circle mr-1"></i><span x-text="detail.denda_note"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Lokasi Masuk</div>
                    <div class="mt-1.5 flex items-center justify-between gap-2">
                        <span class="truncate text-xs font-semibold text-slate-700" x-text="detail.loc_in || '-'">-</span>
                        <a :href="maps(detail.lat_in, detail.lng_in)" x-show="detail.lat_in && detail.lng_in" x-cloak target="_blank" rel="noopener"
                            class="inline-flex shrink-0 items-center gap-1 text-[11px] font-bold text-blue-600 no-underline hover:underline">
                            <i class="bi bi-geo-alt-fill"></i> Peta
                        </a>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Lokasi Pulang</div>
                    <div class="mt-1.5 flex items-center justify-between gap-2">
                        <span class="truncate text-xs font-semibold text-slate-700" x-text="detail.loc_out || '-'">-</span>
                        <a :href="maps(detail.lat_out, detail.lng_out)" x-show="detail.lat_out && detail.lng_out" x-cloak target="_blank" rel="noopener"
                            class="inline-flex shrink-0 items-center gap-1 text-[11px] font-bold text-blue-600 no-underline hover:underline">
                            <i class="bi bi-geo-alt-fill"></i> Peta
                        </a>
                    </div>
                </div>
            </div>

            <div x-show="detail.note" x-cloak class="rounded-xl border border-amber-200 bg-amber-50 p-3.5">
                <div class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Keterangan</div>
                <p class="mt-1 whitespace-pre-line text-xs leading-relaxed text-amber-900" x-text="detail.note"></p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <button type="button" x-show="detail.has_photo" x-cloak @click="openPhotoFromDetail()"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                    <i class="bi bi-camera-fill"></i> Lihat Foto Presensi
                </button>
                <a href="{{ url('admin/presensi/manual') }}"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-50">
                    <i class="bi bi-pencil-square"></i> Absen Manual
                </a>
                <button type="button" @click="$store.modals.close('#detailModal')"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-200 sm:ml-auto">
                    Tutup
                </button>
            </div>
        </div>
    </x-ui.modal>

    <!-- Modal foto -->
    <x-ui.modal id="photoModal" size="max-w-lg">
        <x-slot:header>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <div class="min-w-0">
                    <h6 class="truncate text-sm font-bold text-slate-800" x-text="photoTitle">Foto Absen</h6>
                    <span class="text-xs text-slate-400" x-text="photoMeta"></span>
                </div>
                <button type="button" @click="$store.modals.close('#photoModal')" aria-label="Tutup"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </x-slot:header>

        <div class="flex flex-col items-center justify-center gap-3 bg-slate-50 p-5">
            <div class="inline-flex gap-1 rounded-xl border border-slate-200 bg-white p-1">
                <button type="button" x-show="photo.in" x-cloak @click="setPhotoTab('in')"
                    :class="photoTab === 'in' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                    class="rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all">
                    <i class="bi bi-box-arrow-in-right mr-1"></i>Datang <span class="font-semibold opacity-70" x-text="photo.in ? '(' + photo.tIn + ')' : ''"></span>
                </button>
                <button type="button" x-show="photo.out" x-cloak @click="setPhotoTab('out')"
                    :class="photoTab === 'out' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                    class="rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all">
                    <i class="bi bi-box-arrow-left mr-1"></i>Pulang <span class="font-semibold opacity-70" x-text="photo.out ? '(' + photo.tOut + ')' : ''"></span>
                </button>
            </div>

            <img x-show="!photoError" :src="photoSrc" x-on:error="photoError = true" alt="Foto Absen"
                class="h-auto max-h-[400px] max-w-full rounded-2xl border border-white object-contain shadow-md">

            <div x-show="photoError" x-cloak class="w-full max-w-xs">
                <div class="flex h-52 flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-300 bg-white px-4 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-400">
                        <i class="bi bi-image"></i>
                    </div>
                    <p class="m-0 text-xs font-bold text-slate-600">Foto tidak tersedia</p>
                    <p class="m-0 text-[10px] leading-relaxed text-slate-400">Berkas foto mungkin sudah dihapus atau gagal dimuat dari server.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-100 bg-slate-50/50 px-5 py-3.5">
            <button type="button" @click="$store.modals.close('#photoModal')"
                class="rounded-xl bg-slate-200 px-4 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-300">
                Tutup
            </button>
        </div>
    </x-ui.modal>

    <!-- Modal kebijakan -->
    <x-ui.modal id="policyModal" title="Kebijakan dan Aturan Absensi Guru" icon="bi-shield-check" tone="blue" size="max-w-2xl">
        <div class="max-h-[75vh] space-y-5 overflow-y-auto p-5 text-xs text-slate-600">
            <div class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50/80 p-4">
                <i class="bi bi-info-circle-fill mt-0.5 shrink-0 text-lg text-blue-600"></i>
                <div class="leading-relaxed">
                    Sistem mencatat presensi berdasarkan koordinat GPS valid (radius sekolah), verifikasi foto wajah, dan waktu server. Tindakan kecurangan tersimpan otomatis di audit log.
                </div>
            </div>

            <div>
                <h6 class="mb-2 flex items-center gap-1.5 text-sm font-bold text-blue-700">
                    <i class="bi bi-clock-history"></i> 1. Denda Keterlambatan (3 Tier)
                </h6>
                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5">Menit Terlambat</th>
                                <th class="px-4 py-2.5">Denda / Menit</th>
                                <th class="px-4 py-2.5">Poin / Menit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="px-4 py-2 font-bold text-slate-700">Menit 1 &ndash; 30</td>
                                <td class="px-4 py-2 font-semibold text-rose-600">Rp {{ number_format((float) ($absenSetting->denda_tier_1 ?? 0), 0, ',', '.') }}</td>
                                <td class="px-4 py-2 text-slate-600">{{ (int) ($absenSetting->point_tier_1 ?? 1) }} poin</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 font-bold text-slate-700">Menit 31 &ndash; 60</td>
                                <td class="px-4 py-2 font-semibold text-rose-600">Rp {{ number_format((float) ($absenSetting->denda_tier_2 ?? 0), 0, ',', '.') }}</td>
                                <td class="px-4 py-2 text-slate-600">{{ (int) ($absenSetting->point_tier_2 ?? 2) }} poin</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 font-bold text-slate-700">Menit 61+</td>
                                <td class="px-4 py-2 font-semibold text-rose-600">Rp {{ number_format((float) ($absenSetting->denda_tier_3 ?? 0), 0, ',', '.') }}</td>
                                <td class="px-4 py-2 text-slate-600">{{ (int) ($absenSetting->point_tier_3 ?? 5) }} poin</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-[11px] text-slate-400">
                    Toleransi keterlambatan <b>{{ (int) ($absenSetting->grace_period ?? 15) }} menit</b>. Batas denda per bulan <b>Rp {{ number_format((float) ($absenSetting->max_denda_bulan ?? 0), 0, ',', '.') }}</b>.
                </p>
            </div>

            <div>
                <h6 class="mb-2 flex items-center gap-1.5 text-sm font-bold text-rose-700">
                    <i class="bi bi-x-circle-fill"></i> 2. Alpha / Tanpa Keterangan
                </h6>
                <div class="flex items-center justify-between gap-3 rounded-xl border border-rose-200 bg-rose-50 p-3.5">
                    <div>
                        <span class="font-bold text-rose-900">Alpha (A)</span>
                        <p class="mt-0.5 text-[11px] text-rose-700">Tidak melakukan presensi tanpa pengajuan izin yang sah.</p>
                    </div>
                    <span class="shrink-0 rounded-xl bg-rose-600 px-2.5 py-1 text-xs font-extrabold text-white">
                        -{{ (int) ($absenSetting->point_alpa ?? 5) }} poin/hari
                    </span>
                </div>
                <p class="mt-2 text-[11px] text-slate-400">
                    Denda alpha <b>Rp {{ number_format((float) ($absenSetting->denda_alpha_per_hari ?? 0), 0, ',', '.') }}</b> per hari.
                </p>
            </div>

            <div>
                <h6 class="mb-2 flex items-center gap-1.5 text-sm font-bold text-emerald-700">
                    <i class="bi bi-file-earmark-check-fill"></i> 3. Izin, Sakit, Dinas &amp; WFH
                </h6>
                <ul class="list-disc space-y-1.5 pl-4 leading-relaxed text-slate-600">
                    <li><b>Izin / Sakit / Cuti:</b> diajukan melalui portal guru dengan bukti sah, aktif setelah disetujui admin.</li>
                    <li><b>Tugas Dinas Luar:</b> bebas radius sekolah dan verifikasi wajah, menunggu persetujuan admin.</li>
                    <li><b>WFH:</b> dibatasi {{ (int) ($absenSetting->wfh_max_per_bulan ?? 5) }} hari per bulan, tanpa radius GPS.</li>
                </ul>
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-100 bg-slate-50/50 px-5 py-3.5">
            <button type="button" @click="$store.modals.close('#policyModal')"
                class="rounded-xl bg-slate-200 px-4 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-300">
                Tutup
            </button>
        </div>
    </x-ui.modal>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    const state = { rows: [] };

    Alpine.data('presensiMonitor', () => ({
        config: { csrf: '', date: '', dateLabel: '', isToday: false, primary: '#2563eb', urls: {} },

        query: '',
        status: 'ALL',
        sortKey: '',
        sortDir: 'asc',
        visible: 0,
        counts: {},
        alertOpen: false,
        recalculating: false,

        detail: {},
        photo: { in: '', out: '', name: '', tIn: '-', tOut: '-', loc: '-' },
        photoTab: 'in',
        photoError: false,

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');

            this.config = {
                csrf: cfg.csrf || '',
                date: cfg.date || '',
                dateLabel: cfg.dateLabel || '',
                isToday: !!cfg.isToday,
                primary: cfg.primary || '#2563eb',
                urls: cfg.urls || {},
            };

            state.rows = Array.from(this.$refs.tbody.querySelectorAll('tr.teacher-row'));
            state.rows.forEach((row, index) => { row.dataset.idx = String(index); });

            const counts = { ALL: state.rows.length };

            state.rows.forEach((row) => {
                const key = row.dataset.status || 'LAINNYA';
                counts[key] = (counts[key] || 0) + 1;
            });

            this.counts = counts;

            this.restore();

            if (this.config.isToday) {
                window.setInterval(() => this.autoRefresh(), 60000);
            }

            window.addEventListener('beforeunload', () => this.persist({ scrollY: window.scrollY }));
        },

        storageKey() {
            return 'presensiMonitoringState';
        },

        loadSaved() {
            try {
                const saved = JSON.parse(sessionStorage.getItem(this.storageKey()) || '{}');

                return saved && saved.d === this.config.date ? saved : {};
            } catch (error) {
                return {};
            }
        },

        persist(patch = {}) {
            try {
                const saved = this.loadSaved();

                Object.assign(saved, patch, {
                    d: this.config.date,
                    q: this.query,
                    f: this.status,
                    sortKey: this.sortKey,
                    sortDir: this.sortDir,
                });

                sessionStorage.setItem(this.storageKey(), JSON.stringify(saved));
            } catch (error) {
                /* penyimpanan sesi opsional */
            }
        },

        restore() {
            const saved = this.loadSaved();

            this.query = saved.q || '';
            this.status = saved.f || 'ALL';
            this.sortKey = saved.sortKey || '';
            this.sortDir = saved.sortDir === 'desc' ? 'desc' : 'asc';

            if (this.sortKey) {
                this.applySort();
            }

            this.apply();

            if (saved.scrollY) {
                window.scrollTo(0, parseInt(saved.scrollY, 10) || 0);
            }
        },

        apply() {
            const query = this.query.trim().toLowerCase();
            const visible = [];

            state.rows.forEach((row) => {
                const matchQuery = query === '' || (row.dataset.search || '').includes(query);
                const matchStatus = this.status === 'ALL' || row.dataset.status === this.status;
                const show = matchQuery && matchStatus;

                row.classList.toggle('hidden', !show);

                if (show) {
                    visible.push(row);
                }
            });

            if (this.sortKey) {
                const key = 'sort' + this.sortKey.charAt(0).toUpperCase() + this.sortKey.slice(1);
                const direction = this.sortDir === 'desc' ? -1 : 1;
                const collator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });

                visible.sort((a, b) => {
                    const av = a.dataset[key] ?? '';
                    const bv = b.dataset[key] ?? '';

                    if (av === '' && bv === '') {
                        return Number(a.dataset.idx) - Number(b.dataset.idx);
                    }

                    if (av === '') {
                        return 1;
                    }

                    if (bv === '') {
                        return -1;
                    }

                    const result = this.sortKey === 'name'
                        ? collator.compare(av, bv)
                        : parseFloat(av) - parseFloat(bv);

                    return result * direction;
                });

                visible.forEach((row) => this.$refs.tbody.appendChild(row));
            }

            visible.forEach((row, index) => {
                const numbering = row.querySelector('[data-rownum]');

                if (numbering) {
                    numbering.textContent = index + 1;
                }
            });

            this.visible = visible.length;
            this.persist();
        },

        applySort() {
            this.apply();
        },

        sortBy(key) {
            if (this.sortKey === key) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = key;
                this.sortDir = 'asc';
            }

            this.apply();
        },

        setStatus(status) {
            this.status = status;
            this.apply();
        },

        resetFilters() {
            this.query = '';
            this.status = 'ALL';
            this.sortKey = '';
            this.sortDir = 'asc';

            state.rows.sort((a, b) => Number(a.dataset.idx) - Number(b.dataset.idx));
            state.rows.forEach((row) => this.$refs.tbody.appendChild(row));

            this.apply();

            this.$nextTick(() => {
                if (this.$refs.search) {
                    this.$refs.search.focus();
                }
            });
        },

        autoRefresh() {
            if (document.hidden) return;
            if (document.querySelector('.modal.is-open')) return;
            if (this.query.trim() !== '' || this.status !== 'ALL' || this.sortKey !== '') return;

            window.location.reload();
        },

        reloadPage() {
            window.location.reload();
        },

        async hitungUlang() {
            if (this.recalculating) return;

            const konfirmasi = await Swal.fire({
                title: 'Hitung Ulang Status Presensi?',
                html: 'Sistem akan mengevaluasi ulang status kehadiran serta denda seluruh guru pada <strong>' + this.config.dateLabel + '</strong> sesuai toleransi dan jadwal aktif.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: this.config.primary,
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="bi bi-arrow-repeat mr-1"></i> Ya, Hitung Ulang',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.recalculating = true;
            Swal.fire({
                title: 'Memproses...',
                text: 'Sedang mengevaluasi ulang data presensi...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            const body = new URLSearchParams();
            body.append('_token', this.config.csrf);
            body.append('date', this.config.date);

            try {
                const response = await fetch(this.config.urls.recalculate, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'X-CSRF-TOKEN': this.config.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });

                const result = await response.json();

                if (result && result.status) {
                    await Swal.fire({ icon: 'success', title: 'Berhasil!', text: result.message || '', timer: 1500, showConfirmButton: false });
                    window.location.reload();
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.message) || 'Terjadi kesalahan saat menghitung ulang.' });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Kesalahan Jaringan', text: 'Gagal menghubungi server: ' + (error.message || '') });
            } finally {
                this.recalculating = false;
            }
        },

        openDetail(raw) {
            let data = {};

            try {
                data = JSON.parse(raw || '{}');
            } catch (error) {
                return;
            }

            this.detail = data;
            this.$store.modals.open('#detailModal');
        },

        openPhoto(raw) {
            let data = null;

            try {
                data = typeof raw === 'string' ? JSON.parse(raw || '{}') : raw;
            } catch (error) {
                data = null;
            }

            if (!data || (!data.in && !data.out)) return;

            this.photo = {
                in: data.in || '',
                out: data.out || '',
                name: data.name || '',
                tIn: data.tIn || '-',
                tOut: data.tOut || '-',
                loc: data.loc || '-',
            };
            this.photoTab = this.photo.in ? 'in' : 'out';
            this.photoError = false;

            this.$store.modals.open('#photoModal');
        },

        openPhotoFromDetail() {
            this.$store.modals.close('#detailModal');

            this.openPhoto({
                in: this.detail.photo_in,
                out: this.detail.photo_out,
                name: this.detail.name,
                tIn: this.detail.t_in,
                tOut: this.detail.t_out,
                loc: this.detail.loc_in,
            });
        },

        setPhotoTab(which) {
            this.photoTab = which;
            this.photoError = false;
        },

        get photoSrc() {
            return this.photoTab === 'out' ? this.photo.out : this.photo.in;
        },

        get photoTitle() {
            return 'Foto ' + (this.photoTab === 'out' ? 'Pulang' : 'Datang') + ' - ' + (this.photo.name || '');
        },

        get photoMeta() {
            return 'Waktu: ' + (this.photoTab === 'out' ? this.photo.tOut : this.photo.tIn) + ' | Lokasi: ' + (this.photo.loc || '-');
        },

        maps(lat, lng) {
            if (!lat || !lng) return '#';

            return 'https://www.google.com/maps?q=' + encodeURIComponent(lat) + ',' + encodeURIComponent(lng);
        },

        fmtRp(value) {
            return 'Rp ' + (Number(value) || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
        },

        fmtMin(value) {
            return (Number(value) || 0) + ' mnt';
        },

        poinLabel(value) {
            const points = Number(value) || 0;

            return (points > 0 ? '+' : '') + points;
        },

        poinClass(value) {
            const points = Number(value) || 0;

            if (points < 0) return 'text-rose-600';
            if (points > 0) return 'text-emerald-600';

            return 'text-slate-700';
        },
    }));
});
</script>
@endsection
