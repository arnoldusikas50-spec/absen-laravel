@extends('layouts.main')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<style>
    #kalender.fc { --fc-border-color: #e2e8f0; font-size: .8125rem; }
    #kalender .fc-toolbar { gap: .5rem; flex-wrap: wrap; }
    #kalender .fc-toolbar-title { font-size: 1rem; font-weight: 800; color: #0f172a; text-transform: capitalize; }
    #kalender .fc-button {
        font-size: .7rem !important; font-weight: 700; border-radius: .65rem !important;
        padding: .42rem .7rem !important; text-transform: capitalize; box-shadow: none !important;
    }
    #kalender .fc-button-primary { background: #fff !important; color: #334155 !important; border-color: #e2e8f0 !important; }
    #kalender .fc-button-primary:hover { background: #f1f5f9 !important; color: #0f172a !important; border-color: #cbd5e1 !important; }
    #kalender .fc-button-primary:not(:disabled).fc-button-active,
    #kalender .fc-button-primary:not(:disabled):active { background: var(--primary-600) !important; border-color: var(--primary-600) !important; color: #fff !important; }
    #kalender .fc-today-button:disabled { opacity: .45; }
    #kalender .fc-col-header-cell { font-size: .66rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; padding: .4rem 0; }
    #kalender .fc-col-header-cell-cushion { color: inherit; text-decoration: none; }
    #kalender .fc-daygrid-day-number { font-size: .72rem; font-weight: 700; color: #475569; padding: 4px 6px; text-decoration: none; }
    #kalender .fc-daygrid-day.fc-day-today { background: #eff6ff; }
    #kalender .fc-daygrid-day.fc-day-today .fc-daygrid-day-number { color: var(--primary-700); }
    #kalender .fc-scrollgrid { border-radius: .9rem; overflow: hidden; border-color: #eef2f7; }
    #kalender .fc-theme-standard td, #kalender .fc-theme-standard th { border-color: #eef2f7; }
    #kalender .kalender-akhir-pekan { background: #f8fafc; }
    #kalender .kalender-akhir-pekan .fc-daygrid-day-number { color: #94a3b8; }
    #kalender .fc-event {
        border-radius: .45rem; font-size: .68rem; font-weight: 700;
        padding: 1px 5px; cursor: pointer; border-width: 1px; box-shadow: none;
    }
    #kalender .kalender-libur { border-style: dashed; }
    #kalender .fc-list-event-title { font-size: .8rem; font-weight: 600; }
    #kalender .fc-list-day-cushion { background: #f8fafc; }
    @media (max-width: 640px) {
        #kalender .fc-toolbar { justify-content: flex-start; }
        #kalender .fc-toolbar-chunk { display: flex; align-items: center; }
        #kalender .fc-toolbar-title { font-size: .9rem; width: 100%; }
        #kalender .fc-button { padding: .35rem .55rem !important; }
    }
</style>
@endsection

@section('content')

@php
    $events        = $events ?? [];
    $holidays      = $holidays ?? [];
    $holidaysBulan = $holidays_bulan ?? [];
    $weeklyOff     = $weekly_off ?? [];
    $liburIso      = $libur_iso ?? [];
    $todayStatus   = $today_status ?? [];
    $stats         = $stats ?? [];
    $bulan         = (int) ($bulan ?? date('n'));
    $tahun         = (int) ($tahun ?? date('Y'));

    $liburTahun  = (int) ($stats['libur_tahun'] ?? 0);
    $liburBulan  = (int) ($stats['libur_bulan'] ?? 0);
    $cutiTahun   = (int) ($stats['cuti_tahun'] ?? 0);
    $eventBulan  = (int) ($stats['event_bulan'] ?? count($events));
    $hariSekolah = (int) ($stats['hari_sekolah'] ?? 0);
    $totalHari   = (int) ($stats['total_hari'] ?? date('t'));

    $namaBulanId = ['','Januari','Februari','Maret','April','Mei','Juni',
                    'Juli','Agustus','September','Oktober','November','Desember'];
    $bulanSingkat = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

    $nama_bulan      = $nama_bulan ?? $namaBulanId[$bulan];
    $weeklyOffLabel  = $weeklyOff ? implode(' & ', array_column($weeklyOff, 'nama')) : 'Tidak ada';
    $isHariSekolah   = ($todayStatus['status'] ?? '') === 'aktif';
    $statusKeterangan = trim((string) ($todayStatus['keterangan'] ?? ''));
    if (($todayStatus['status'] ?? '') === 'akhir_pekan') {
        $statusKeterangan = 'Akhir pekan';
    } elseif ($statusKeterangan === '') {
        $statusKeterangan = 'Libur';
    }

    $tipeColors = [
        'EVENT'   => '#3b82f6',
        'UJIAN'   => '#8b5cf6',
        'LIBUR'   => '#ef4444',
        'RAPAT'   => '#f59e0b',
        'DINAS'   => '#06b6d4',
        'LAINNYA' => '#64748b',
    ];
    $tipeBadge = [
        'EVENT'   => 'bg-blue-50 text-blue-700 border-blue-100',
        'UJIAN'   => 'bg-purple-50 text-purple-700 border-purple-100',
        'LIBUR'   => 'bg-red-50 text-red-700 border-red-100',
        'RAPAT'   => 'bg-amber-50 text-amber-700 border-amber-100',
        'DINAS'   => 'bg-cyan-50 text-cyan-700 border-cyan-100',
        'LAINNYA' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];

    $formatRentang = function (?string $mulai, ?string $selesai) use ($bulanSingkat): string {
        if (! $mulai) {
            return '-';
        }
        $ts1 = strtotime($mulai);
        $ts2 = $selesai ? strtotime($selesai) : $ts1;
        if ($ts2 < $ts1) {
            $ts2 = $ts1;
        }
        $d1 = (int) date('j', $ts1);
        $d2 = (int) date('j', $ts2);
        $m1 = (int) date('n', $ts1);
        $m2 = (int) date('n', $ts2);
        $y1 = date('Y', $ts1);
        $y2 = date('Y', $ts2);
        if ($d1 === $d2 && $m1 === $m2 && $y1 === $y2) {
            return buat_tanggal(date('l', $ts1)) . ', ' . $d1 . ' ' . $bulanSingkat[$m1] . ' ' . $y1;
        }
        if ($m1 === $m2 && $y1 === $y2) {
            return $d1 . '–' . $d2 . ' ' . $bulanSingkat[$m1] . ' ' . $y1;
        }
        return $d1 . ' ' . $bulanSingkat[$m1] . ' – ' . $d2 . ' ' . $bulanSingkat[$m2] . ' ' . $y2;
    };

    $agenda = [];
    foreach ($events as $e) {
        $agenda[] = [
            'kind'  => 'event',
            'id'    => (int) $e->id,
            'date'  => $e->tgl_mulai,
            'end'   => $e->tgl_selesai,
            'title' => $e->judul,
            'tipe'  => $e->tipe,
            'color' => $e->warna ?: ($tipeColors[$e->tipe] ?? '#3b82f6'),
        ];
    }
    foreach ($holidaysBulan as $h) {
        $agenda[] = [
            'kind'  => 'libur',
            'id'    => (int) $h->id,
            'date'  => $h->tanggal,
            'end'   => $h->tanggal,
            'title' => $h->keterangan,
            'tipe'  => 'LIBUR',
            'color' => '#ef4444',
        ];
    }
    usort($agenda, static fn ($a, $b) => [$a['date'], $a['title']] <=> [$b['date'], $b['title']]);

    $tahunList = [];
    foreach ($holidays as $h) {
        $yr = substr((string) $h->tanggal, 0, 4);
        if ($yr !== '' && ! in_array($yr, $tahunList, true)) {
            $tahunList[] = $yr;
        }
    }
    rsort($tahunList);
    $tahunAda = in_array((string) $tahun, $tahunList, true);

    $holidayRows = [];
    foreach ($holidays as $h) {
        $ts = strtotime((string) $h->tanggal);
        $holidayRows[] = [
            'id' => (int) $h->id,
            'tanggal' => $h->tanggal,
            'keterangan' => $h->keterangan,
            'year' => date('Y', $ts),
            'month' => date('m', $ts),
            'desc' => mb_strtolower((string) ($h->keterangan ?? '')),
        ];
    }

    $syncYears = [];
    for ($y = (int) date('Y') - 1; $y <= (int) date('Y') + 2; $y++) {
        $syncYears[] = $y;
    }

    $pageConfig = [
        'csrf' => csrf_token(),
        'urls' => [
            'feed' => url('admin/kalender/get-events'),
            'create' => url('admin/kalender/create'),
            'update' => url('admin/kalender/update'),
            'delete' => url('admin/kalender/delete'),
            'getEvent' => url('admin/kalender/get-event'),
            'holidaySave' => url('admin/kalender/holiday/save'),
            'holidayDelete' => url('admin/kalender/holiday/delete'),
            'holidaySync' => url('admin/kalender/holiday/sync'),
        ],
        'initialDate' => sprintf('%04d-%02d-01', $tahun, $bulan),
        'view' => ['year' => $tahun, 'month' => $bulan],
        'liburIso' => array_values($liburIso),
        'bulanNama' => array_slice($namaBulanId, 1),
        'bulanSingkat' => array_slice($bulanSingkat, 1),
        'tahunList' => $tahunList,
        'tahunAda' => $tahunAda,
        'syncYears' => $syncYears,
        'tipe' => $tipeColors,
        'weeklyOffLabel' => $weeklyOffLabel,
        'defaultTanggal' => date('Y-m-d'),
        'hasPeringatan' => ! empty($peringatan),
        'agenda' => $agenda,
        'liburBulan' => collect($holidaysBulan)->map(fn ($h) => [
            'id' => (int) $h->id,
            'tanggal' => $h->tanggal,
            'keterangan' => $h->keterangan,
        ])->values()->all(),
        'holidays' => $holidayRows,
        'kpi' => [
            'eventBulan' => $eventBulan,
            'liburBulan' => $liburBulan,
            'hariSekolah' => $hariSekolah,
            'totalHari' => $totalHari,
            'liburTahun' => $liburTahun,
            'cutiTahun' => $cutiTahun,
        ],
    ];
@endphp

<div x-data="kalenderPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
    class="px-4 pb-6 pt-4 md:px-6 md:pt-6">

    <x-ui.page-header
        :title="$judul ?? 'Kalender & Hari Libur'"
        icon="bi-calendar3"
        tone="blue"
        subtitle="Kalender akademik, agenda sekolah, dan hari libur absensi guru."
        :crumbs="['Kalender Akademik']">
        <x-slot:actions>
            <button type="button" @click="startTambahLibur()"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-600">
                <i class="bi bi-calendar-x"></i> Tambah Libur
            </button>
            <button type="button" @click="openEvent()"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                <i class="bi bi-plus-lg"></i> Tambah Event
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mt-2 flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $isHariSekolah ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
            <i class="bi {{ $isHariSekolah ? 'bi-check-circle-fill' : 'bi-calendar-x-fill' }}"></i>
            {{ $isHariSekolah ? 'Hari sekolah' : $statusKeterangan }}
        </span>
        <span class="text-[11px] text-slate-400">{{ buat_tanggal(date('l, j F Y')) }}</span>
        @if ($tp_active || $smt_active)
            <span class="text-[11px] text-slate-400">
                <span class="mx-1 text-slate-300">•</span>
                TP {{ $tp_active->tahun ?? '-' }} &middot; {{ $smt_active->smt ?? '-' }}
            </span>
        @endif
    </div>

    <!-- Tab -->
    <div class="mt-4 flex w-full max-w-full gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1 sm:w-fit">
        <button type="button" @click="switchTab('kalender')"
            :class="tab === 'kalender' ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-500 hover:text-slate-700'"
            class="flex flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors sm:flex-none sm:px-5">
            <i class="bi bi-calendar3"></i> Kalender
        </button>
        <button type="button" @click="switchTab('libur')"
            :class="tab === 'libur' ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-500 hover:text-slate-700'"
            class="flex flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors sm:flex-none sm:px-5">
            <i class="bi bi-calendar-x"></i> Hari Libur
            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">{{ $liburTahun }}</span>
        </button>
    </div>

    <!-- TAB: KALENDER -->
    <div x-show="tab === 'kalender'" class="mt-5">

        <div class="mb-5 grid grid-cols-2 gap-3 md:gap-4 xl:grid-cols-4">
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 border-l-4 border-l-blue-500 bg-white p-4 shadow-xs">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-lg text-blue-600">
                    <i class="bi bi-journal-text"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Event <span x-text="monthLabel"></span></p>
                    <p class="mt-0.5 text-2xl font-extrabold leading-none text-slate-900" x-text="kpi.eventBulan"></p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 border-l-4 border-l-red-500 bg-white p-4 shadow-xs">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-lg text-red-500">
                    <i class="bi bi-calendar-x"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Libur <span x-text="monthLabel"></span></p>
                    <p class="mt-0.5 text-2xl font-extrabold leading-none text-red-500" x-text="kpi.liburBulan"></p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 border-l-4 border-l-emerald-500 bg-white p-4 shadow-xs">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-lg text-emerald-600">
                    <i class="bi bi-calendar2-check"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Hari Sekolah</p>
                    <p class="mt-0.5 text-2xl font-extrabold leading-none text-emerald-600">
                        <span x-text="kpi.hariSekolah"></span>
                        <span class="text-xs font-semibold text-slate-400">/ <span x-text="kpi.totalHari"></span></span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 border-l-4 border-l-amber-500 bg-white p-4 shadow-xs">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-lg text-amber-500">
                    <i class="bi bi-calendar2-range"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Libur <span x-text="viewYear"></span></p>
                    <p class="mt-0.5 text-2xl font-extrabold leading-none text-slate-900">
                        <span x-text="kpi.liburTahun"></span>
                        <span class="text-xs font-normal text-slate-400">hari</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <!-- Kalender -->
            <div class="xl:col-span-2">
                <x-ui.card title="Kalender Akademik" icon="bi-calendar3" bodyClass="p-2 sm:p-4"
                    subtitle="Klik tanggal untuk menambah event. Hari libur absensi tampil merah.">
                    <x-slot:meta>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                            <i class="bi bi-calendar2-week text-slate-400"></i>
                            <span x-text="monthLabel"></span>
                        </span>
                    </x-slot:meta>

                    <div class="relative">
                        <div id="kalender" class="min-h-[460px]"></div>

                        <div x-show="calendarLoading" class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 rounded-2xl bg-white/80 backdrop-blur-[1px]">
                            <span class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></span>
                            <span class="text-xs font-semibold text-slate-500">Memuat kalender…</span>
                        </div>

                        <div x-show="calendarError" x-cloak class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 rounded-2xl bg-white/95 px-4 text-center">
                            <i class="bi bi-wifi-off text-2xl text-slate-300"></i>
                            <p class="m-0 text-xs font-semibold text-slate-600" x-text="calendarErrorText"></p>
                            <button type="button" @click="retryFeed()"
                                class="mt-1 rounded-lg bg-blue-600 px-3 py-1.5 text-[11px] font-bold text-white transition-colors hover:bg-blue-700">Coba Lagi</button>
                        </div>
                    </div>

                    <x-slot:footer>
                        <div class="flex flex-wrap gap-x-4 gap-y-1.5 text-[10px] font-semibold text-slate-500">
                            @foreach ($tipeColors as $tipe => $warna)
                                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background:{{ $warna }}"></span>{{ ucfirst(strtolower($tipe)) }}</span>
                            @endforeach
                            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full border border-dashed border-red-400 bg-red-200"></span>Libur absensi</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm border border-slate-200 bg-slate-100"></span>Akhir pekan</span>
                        </div>
                    </x-slot:footer>
                </x-ui.card>
            </div>

            <!-- Sidebar -->
            <div class="flex flex-col gap-4">
                <x-ui.card title="Agenda" icon="bi-list-ul" bodyClass="p-0">
                    <x-slot:meta>
                        <span class="text-[11px] font-semibold text-slate-400" x-text="monthLabel"></span>
                    </x-slot:meta>
                    <x-slot:actions>
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-100 px-1.5 text-[11px] font-bold text-slate-600" x-text="agenda.length"></span>
                    </x-slot:actions>

                    <div class="max-h-[420px] overflow-y-auto">
                        <template x-if="agenda.length === 0">
                            <div class="px-4 py-10 text-center text-xs text-slate-400">
                                <i class="bi bi-calendar-plus mb-2 block text-3xl opacity-20"></i>
                                Belum ada agenda bulan ini.<br>Klik tanggal di kalender untuk menambah event.
                            </div>
                        </template>

                        <div x-show="agenda.length > 0" class="divide-y divide-slate-50">
                            <template x-for="item in agenda" :key="item.kind + '-' + item.id">
                                <div class="group flex cursor-pointer items-start gap-2.5 px-4 py-2.5 transition-colors hover:bg-slate-50"
                                    role="button" tabindex="0"
                                    @click="openAgenda(item)" @keydown.enter.prevent="openAgenda(item)" @keydown.space.prevent="openAgenda(item)">
                                    <div class="flex h-11 w-10 shrink-0 flex-col items-center justify-center rounded-xl border"
                                        :style="'background:' + hexAlpha(item.color, 0.08) + ';border-color:' + hexAlpha(item.color, 0.2) + ';color:' + item.color">
                                        <span class="text-sm font-extrabold leading-none" x-text="item.day"></span>
                                        <span class="mt-0.5 text-[9px] font-bold uppercase" x-text="item.mon"></span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="m-0 truncate text-[13px] font-bold leading-snug text-slate-800" x-text="item.title"></p>
                                        <p class="m-0 truncate text-[11px] text-slate-400" x-text="item.range"></p>
                                        <span class="mt-1 inline-flex rounded border px-1.5 py-0.5 text-[9px] font-bold"
                                            :class="item.kind === 'libur' ? 'border-red-100 bg-red-50 text-red-600' : (badgeTipe[item.tipe] || 'border-slate-200 bg-slate-100 text-slate-600')"
                                            x-text="item.tipe"></span>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1 opacity-60 transition-opacity group-hover:opacity-100">
                                        <button type="button" @click.stop="openAgenda(item)" title="Kelola"
                                            class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition-colors hover:border-blue-200 hover:text-blue-600">
                                            <i class="bi bi-pencil text-[11px]"></i>
                                        </button>
                                        <button type="button" x-show="item.kind === 'event'" @click.stop="deleteEvent(item.id)" title="Hapus event"
                                            class="flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition-colors hover:border-red-200 hover:text-red-600">
                                            <i class="bi bi-trash3 text-[11px]"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card title="Libur" icon="bi-calendar-x" tone="rose" bodyClass="p-0">
                    <x-slot:meta>
                        <span class="text-[11px] font-semibold text-slate-400" x-text="bulanNama[viewMonth - 1]"></span>
                    </x-slot:meta>
                    <x-slot:actions>
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full border border-red-100 bg-red-50 px-1.5 text-[10px] font-bold text-red-600" x-text="liburRows.length"></span>
                        <button type="button" @click="switchTab('libur')" class="text-[11px] font-semibold text-blue-500 hover:underline">Kelola →</button>
                    </x-slot:actions>

                    <div class="max-h-48 overflow-y-auto">
                        <template x-if="liburRows.length === 0">
                            <div class="py-5 text-center text-xs text-slate-400">Tidak ada libur bulan ini</div>
                        </template>

                        <div x-show="liburRows.length > 0" class="divide-y divide-slate-50">
                            <template x-for="h in liburRows" :key="h.id">
                                <button type="button" @click="startEditHoliday(h.id, h.tanggal, h.keterangan)"
                                    class="flex w-full items-start gap-2.5 bg-transparent px-4 py-2.5 text-left transition-colors hover:bg-red-50/40">
                                    <i class="bi bi-circle-fill mt-1.5 shrink-0 text-[8px] text-red-400"></i>
                                    <div class="min-w-0">
                                        <span class="text-xs font-bold text-slate-500" x-text="h.dateLabel"></span>
                                        <span class="ml-1 text-xs text-slate-600" x-text="h.keterangan"></span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card title="Hari Libur Mingguan" icon="bi-calendar-week" tone="slate">
                    <div class="flex flex-wrap gap-1.5">
                        @forelse ($weeklyOff as $wo)
                            <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                                <i class="bi bi-calendar-week text-slate-400"></i> {{ $wo['nama'] }}
                            </span>
                        @empty
                            <span class="text-[11px] text-slate-400">Tidak ada hari libur mingguan.</span>
                        @endforelse
                    </div>
                    <p class="mt-2 text-[10px] leading-relaxed text-slate-400">
                        Diatur di <a href="{{ url('settings') }}" class="text-blue-500 hover:underline">Pengaturan Absensi</a>.
                        Tanggal merah di tab Hari Libur otomatis menonaktifkan alfa &amp; denda.
                    </p>
                </x-ui.card>
            </div>
        </div>
    </div>

    <!-- TAB: HARI LIBUR -->
    <div x-show="tab === 'libur'" x-cloak class="mt-5">

        <div x-show="kpi.liburTahun === 0" class="mb-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
            <i class="bi bi-exclamation-triangle-fill mt-0.5 text-amber-500"></i>
            <div class="min-w-0 flex-1">
                <p class="m-0 text-xs font-bold text-amber-800">Kalender libur <span x-text="viewYear"></span> masih kosong</p>
                <p class="m-0 mt-0.5 text-[11px] leading-relaxed text-amber-700">
                    Tanpa data ini, tanggal merah akan dianggap hari kerja dan absensi guru tetap dihitung.
                </p>
            </div>
            <button type="button" @click="syncHolidays()"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-amber-500 px-3 py-1.5 text-[11px] font-bold text-white transition-colors hover:bg-amber-600">
                <i class="bi bi-cloud-arrow-down"></i> Sinkron
            </button>
        </div>

        <div class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-3 md:gap-4">
            <div class="flex items-center gap-4 rounded-2xl border border-slate-200/80 border-l-4 border-l-blue-500 bg-white p-4 shadow-xs">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-xl text-blue-600">
                    <i class="bi bi-calendar2-check"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Libur <span x-text="viewYear"></span></p>
                    <p class="mt-0.5 text-2xl font-extrabold leading-none text-slate-900">
                        <span x-text="kpi.liburTahun"></span> <span class="text-sm font-normal text-slate-400">hari</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-2xl border border-slate-200/80 border-l-4 border-l-red-500 bg-white p-4 shadow-xs">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-xl text-red-500">
                    <i class="bi bi-calendar2-day"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Libur <span x-text="bulanNama[viewMonth - 1]"></span></p>
                    <p class="mt-0.5 text-2xl font-extrabold leading-none text-red-500">
                        <span x-text="kpi.liburBulan"></span> <span class="text-sm font-normal text-slate-400">hari</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-2xl border border-slate-200/80 border-l-4 border-l-amber-500 bg-white p-4 shadow-xs">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-xl text-amber-500">
                    <i class="bi bi-calendar2-range"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Cuti Bersama <span x-text="viewYear"></span></p>
                    <p class="mt-0.5 text-2xl font-extrabold leading-none text-slate-900">
                        <span x-text="kpi.cutiTahun"></span> <span class="text-sm font-normal text-slate-400">hari</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

            <!-- Form -->
            <div class="xl:col-span-1">
                <div x-ref="holidayCard" class="sticky top-4">
                    <x-ui.card title="Form Hari Libur" icon="bi-calendar-plus" bodyClass="p-5">
                        <x-slot:meta>
                            <span x-show="holidayEditing" x-cloak class="inline-flex items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-700">
                                <i class="bi bi-pencil-square"></i> Mode edit
                            </span>
                        </x-slot:meta>

                        <div x-show="holidayEditing" x-cloak class="mb-4 flex items-start gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2.5">
                            <i class="bi bi-pencil-square mt-0.5 text-blue-500"></i>
                            <div class="min-w-0 flex-1">
                                <p class="m-0 text-[11px] font-bold text-blue-800">Mode edit</p>
                                <p class="m-0 text-[10px] leading-relaxed text-blue-600">Ubah tanggal atau keterangan, lalu klik Perbarui.</p>
                            </div>
                            <button type="button" @click="resetHoliday()" class="shrink-0 text-[10px] font-bold text-blue-600 hover:underline">Batal</button>
                        </div>

                        <form x-ref="holidayForm" @submit.prevent="saveHoliday()" novalidate>
                            <div x-show="!holidayEditing" x-cloak class="mb-5 flex gap-2">
                                <button type="button" @click="holidayForm.mode = '0'"
                                    :class="holidayForm.mode !== '1' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-500'"
                                    class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border-2 px-3 py-2.5 text-xs font-semibold transition-colors">
                                    <i class="bi bi-calendar-event"></i> Satu Tanggal
                                </button>
                                <button type="button" @click="holidayForm.mode = '1'"
                                    :class="holidayForm.mode === '1' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-500'"
                                    class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border-2 px-3 py-2.5 text-xs font-semibold transition-colors">
                                    <i class="bi bi-calendar-range"></i> Rentang
                                </button>
                            </div>

                            <div x-show="holidayForm.mode !== '1'" class="mb-4">
                                <label for="holidayDate" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                    Tanggal Libur <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" id="holidayDate" x-model="holidayForm.tanggal"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                            </div>

                            <div x-show="holidayForm.mode === '1'" x-cloak class="mb-4 grid grid-cols-2 gap-2">
                                <div>
                                    <label for="holidayDateStart" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Mulai <span class="text-rose-500">*</span></label>
                                    <input type="date" id="holidayDateStart" x-model="holidayForm.mulai"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div>
                                    <label for="holidayDateEnd" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Selesai <span class="text-rose-500">*</span></label>
                                    <input type="date" id="holidayDateEnd" x-model="holidayForm.selesai" :min="holidayForm.mulai"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div class="col-span-2">
                                    <p class="text-[11px] text-slate-400">Semua hari dalam rentang ditambahkan (maks. 366 hari).</p>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="holidayDesc" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                    Keterangan / Nama Libur <span class="text-rose-500">*</span>
                                </label>
                                <textarea id="holidayDesc" x-model="holidayForm.keterangan" rows="3" maxlength="255" required
                                    placeholder="Contoh: Hari Raya Idul Fitri / Libur Semester Ganjil"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"></textarea>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach (['Libur Nasional', 'Cuti Bersama', 'Libur Semester', 'Libur Sekolah', 'Libur Awal Puasa'] as $preset)
                                        <button type="button" @click="holidayForm.keterangan = @js($preset)"
                                            class="rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-semibold text-slate-500 transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">{{ $preset }}</button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-5 flex gap-2">
                                <button type="submit" :disabled="holidaySaving"
                                    class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                                    <span x-show="holidaySaving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                                    <i x-show="!holidaySaving" class="bi bi-save"></i>
                                    <span x-text="holidayEditing ? 'Perbarui' : 'Simpan'"></span>
                                </button>
                                <button type="button" @click="resetHoliday()"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">
                                    <i class="bi bi-x"></i> Batal
                                </button>
                            </div>
                        </form>

                        <x-slot:footer>
                            <p class="m-0 text-[11px] leading-relaxed text-slate-500">
                                <i class="bi bi-info-circle mr-1 text-blue-500"></i>
                                Tanggal di sini otomatis menonaktifkan alfa &amp; denda absensi guru.
                                Akhir pekan ({{ $weeklyOffLabel }}) sudah libur secara otomatis.
                            </p>
                        </x-slot:footer>
                    </x-ui.card>
                </div>
            </div>

            <!-- Tabel -->
            <div class="xl:col-span-2">
                <x-ui.card title="Daftar Hari Libur" icon="bi-list-check" bodyClass="p-0">
                    <x-slot:meta>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600" x-text="filteredHolidays.length + ' Data'"></span>
                    </x-slot:meta>
                    <x-slot:actions>
                        <select x-model="syncYear" aria-label="Tahun sinkronisasi"
                            class="w-[92px] rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-700 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach ($syncYears as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                        <button type="button" @click="syncHolidays()"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 transition-colors hover:bg-blue-100">
                            <i class="bi bi-cloud-arrow-down-fill"></i>
                            <span class="hidden sm:inline">Sinkron Nasional</span>
                        </button>
                    </x-slot:actions>

                    <x-slot:toolbar>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="flex min-w-[170px] max-w-[260px] flex-1 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5">
                                <i class="bi bi-search text-xs text-slate-400"></i>
                                <input type="text" x-model="filterQuery" placeholder="Cari keterangan..." autocomplete="off"
                                    class="flex-1 border-0 bg-transparent text-xs outline-none">
                            </div>
                            <select x-model="filterYear" aria-label="Filter tahun"
                                class="rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-700 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Tahun</option>
                                @foreach ($tahunList as $yr)
                                    <option value="{{ $yr }}">{{ $yr }}</option>
                                @endforeach
                            </select>
                            <select x-model="filterMonth" aria-label="Filter bulan"
                                class="rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-700 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Bulan</option>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ sprintf('%02d', $m) }}">{{ $namaBulanId[$m] }}</option>
                                @endfor
                            </select>
                            <button type="button" @click="resetFilter()"
                                class="rounded-lg px-2 py-1 text-xs font-medium text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600">Reset</button>
                        </div>
                    </x-slot:toolbar>

                    <div class="max-h-[600px] overflow-auto">
                        <table class="w-full border-collapse text-left text-sm">
                            <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <tr class="border-b-2 border-slate-200/80">
                                    <th class="w-10 px-4 py-3 text-center">#</th>
                                    <th class="w-44 px-3 py-3">Tanggal</th>
                                    <th class="px-3 py-3">Keterangan Hari Libur</th>
                                    <th class="w-24 px-3 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr x-show="holidays.length === 0">
                                    <td colspan="4" class="px-4 py-14 text-center text-slate-400">
                                        <i class="bi bi-calendar-x mb-3 block text-4xl opacity-20"></i>
                                        <p class="mb-2 font-semibold text-slate-600">Belum ada data hari libur</p>
                                        <button type="button" @click="syncHolidays()"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 transition-colors hover:bg-blue-100">
                                            <i class="bi bi-cloud-arrow-down"></i> Sinkronkan Hari Libur Nasional
                                        </button>
                                    </td>
                                </tr>

                                <template x-for="(h, index) in filteredHolidays" :key="h.id">
                                    <tr class="transition-colors hover:bg-slate-50">
                                        <td class="px-4 py-3 text-center text-xs font-semibold text-slate-400" x-text="index + 1"></td>

                                        <td class="px-3 py-3">
                                            <div class="flex items-center gap-2.5">
                                                <div class="flex h-[46px] w-[46px] shrink-0 flex-col items-center justify-center rounded-xl border leading-tight"
                                                    :class="hariIconClass(h)">
                                                    <span class="text-base font-bold leading-none" x-text="dayOf(h.tanggal)"></span>
                                                    <span class="text-[9px] font-semibold uppercase" x-text="monthShort(h.tanggal)"></span>
                                                </div>
                                                <div>
                                                    <span class="block text-sm font-semibold text-slate-800" x-text="fmtDate(h.tanggal)"></span>
                                                    <span class="text-xs text-slate-400" x-text="dayName(h.tanggal)"></span>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-3 py-3">
                                            <p class="m-0 text-sm font-semibold text-slate-800" x-text="h.keterangan"></p>
                                            <template x-if="isCuti(h)">
                                                <span class="mt-0.5 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                                                    <i class="bi bi-tag-fill text-[9px]"></i> Cuti Bersama
                                                </span>
                                            </template>
                                            <template x-if="!isCuti(h)">
                                                <span class="mt-0.5 inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700">
                                                    <i class="bi bi-flag-fill text-[9px]"></i> Libur Resmi
                                                </span>
                                            </template>
                                        </td>

                                        <td class="px-3 py-3">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button type="button" @click="startEditHoliday(h.id, h.tanggal, h.keterangan)" title="Edit"
                                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-600 transition-colors hover:bg-blue-100">
                                                    <i class="bi bi-pencil text-xs"></i>
                                                </button>
                                                <button type="button" @click="deleteHoliday(h.id, fmtDate(h.tanggal) + ' - ' + h.keterangan)" title="Hapus"
                                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-500 transition-colors hover:bg-red-100">
                                                    <i class="bi bi-trash3 text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div x-show="holidays.length > 0 && filteredHolidays.length === 0" x-cloak class="py-10 text-center text-slate-400">
                        <i class="bi bi-search mb-2 block text-3xl opacity-20"></i>
                        <p class="text-sm font-semibold text-slate-600">Tidak ada data yang cocok</p>
                        <button type="button" @click="resetFilter()" class="mt-2 text-xs text-blue-500 hover:underline">Reset Filter</button>
                    </div>

                    <x-slot:footer>
                        <div class="text-xs text-slate-400">
                            <i class="bi bi-info-circle mr-1 text-blue-500"></i>
                            Tanggal terdaftar di sini akan meniadakan denda &amp; alfa absensi guru secara otomatis.
                        </div>
                    </x-slot:footer>
                </x-ui.card>
            </div>
        </div>
    </div>

    <!-- Modal Event -->
    <x-ui.modal id="modalEvent" size="max-w-lg">
        <x-slot:header>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white">
                        <i class="bi bi-calendar-plus text-sm"></i>
                    </span>
                    <h2 class="truncate text-sm font-bold text-slate-800" x-text="eventForm.id ? 'Edit Event' : 'Tambah Event'"></h2>
                </div>
                <button type="button" @click="$store.modals.close('#modalEvent')" aria-label="Tutup"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </x-slot:header>

        <form x-ref="eventForm" @submit.prevent="saveEvent()" novalidate class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <div>
                    <label for="eventJudul" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Judul <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="eventJudul" x-model="eventForm.judul" maxlength="255" required autocomplete="off"
                        placeholder="Nama event / kegiatan"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="eventTglMulai" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Tgl Mulai <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" id="eventTglMulai" x-model="eventForm.tgl_mulai" required
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <div>
                        <label for="eventTglSelesai" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Tgl Selesai <span class="font-normal normal-case text-slate-400">(opsional)</span>
                        </label>
                        <input type="date" id="eventTglSelesai" x-model="eventForm.tgl_selesai" :min="eventForm.tgl_mulai"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="eventTipe" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Tipe Event</label>
                        <select id="eventTipe" x-model="eventForm.tipe" @change="onTipeChange()"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                            <option value="EVENT">Event / Kegiatan</option>
                            <option value="UJIAN">Ujian / Penilaian</option>
                            <option value="LIBUR">Libur Sekolah</option>
                            <option value="RAPAT">Rapat / Pertemuan</option>
                            <option value="DINAS">Dinas / Kunjungan</option>
                            <option value="LAINNYA">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label for="eventWarna" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Warna Penanda</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="eventWarna" x-model="eventForm.warna"
                                class="h-10 w-14 cursor-pointer rounded border border-slate-200">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($tipeColors as $warna)
                                    <button type="button" @click="eventForm.warna = @js($warna)" aria-label="Warna {{ $warna }}"
                                        class="h-5 w-5 rounded-full border-2 border-white ring-1 ring-slate-200" style="background:{{ $warna }}"></button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="eventForm.tipe === 'LIBUR'" x-cloak class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5">
                    <i class="bi bi-info-circle-fill mt-0.5 text-amber-500"></i>
                    <div class="min-w-0">
                        <p class="m-0 text-[11px] font-bold text-amber-800">Event LIBUR tidak otomatis meliburkan absensi</p>
                        <p class="m-0 mt-0.5 text-[10px] leading-relaxed text-amber-700">
                            Agar alfa &amp; denda guru ikut nonaktif, daftarkan tanggalnya di tab Hari Libur.
                        </p>
                        <button type="button" @click="goToHolidayTab()" class="mt-1 border-none bg-transparent p-0 text-[10px] font-bold text-amber-700 underline">Kelola Hari Libur</button>
                    </div>
                </div>

                <div>
                    <label for="eventDeskripsi" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Deskripsi <span class="font-normal normal-case text-slate-400">(opsional)</span>
                    </label>
                    <textarea id="eventDeskripsi" x-model="eventForm.deskripsi" rows="3" maxlength="2000" placeholder="Catatan tambahan tentang event ini..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"></textarea>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-between gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" x-show="eventForm.id" x-cloak @click="deleteEvent(eventForm.id)"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-red-200 bg-white px-3 py-2 text-xs font-bold text-red-600 transition-colors hover:bg-red-50">
                    <i class="bi bi-trash3"></i> Hapus
                </button>
                <div class="ml-auto flex items-center justify-end gap-2">
                    <button type="button" @click="$store.modals.close('#modalEvent')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                    <button type="submit" :disabled="eventSaving"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                        <span x-show="eventSaving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                        <i x-show="!eventSaving" class="bi bi-save"></i> Simpan
                    </button>
                </div>
            </div>
        </form>
    </x-ui.modal>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales-all.global.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    const state = { calendar: null, feedEvents: [], feedLoaded: false };

    const pad2 = (n) => String(n).padStart(2, '0');
    const ymd = (d) => d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());

    Alpine.data('kalenderPage', () => ({
        config: {
            csrf: '', urls: {}, initialDate: '', view: { year: 0, month: 1 }, liburIso: [],
            bulanNama: [], bulanSingkat: [], tahunList: [], tahunAda: false, syncYears: [],
            tipe: {}, weeklyOffLabel: '', defaultTanggal: '', hasPeringatan: false,
            agenda: [], liburBulan: [], holidays: [], kpi: {},
        },

        tab: 'kalender',
        viewYear: 0,
        viewMonth: 1,
        kpi: { eventBulan: 0, liburBulan: 0, hariSekolah: 0, totalHari: 0, liburTahun: 0, cutiTahun: 0 },
        agenda: [],
        liburRows: [],
        holidays: [],
        filterQuery: '',
        filterYear: '',
        filterMonth: '',
        syncYear: '',
        calendarLoading: true,
        calendarError: false,
        calendarErrorText: 'Gagal memuat kalender.',
        eventSaving: false,
        holidaySaving: false,
        holidayEditing: false,

        eventForm: { id: '', judul: '', tgl_mulai: '', tgl_selesai: '', tipe: 'EVENT', warna: '#3b82f6', deskripsi: '' },
        holidayForm: { id: '', mode: '0', tanggal: '', mulai: '', selesai: '', keterangan: '' },

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');

            this.config = Object.assign({}, this.config, cfg);
            this.config.urls = cfg.urls || {};
            this.config.kpi = cfg.kpi || {};

            this.viewYear = Number(this.config.view.year) || new Date().getFullYear();
            this.viewMonth = Number(this.config.view.month) || (new Date().getMonth() + 1);
            this.kpi = Object.assign({}, this.kpi, this.config.kpi);
            this.holidays = this.config.holidays || [];
            this.filterYear = this.config.tahunAda ? String(this.viewYear) : '';
            this.syncYear = String(this.viewYear);

            this.agenda = this.mapAgenda(this.config.agenda || []);
            this.liburRows = (this.config.liburBulan || []).map((h) => ({
                id: h.id,
                tanggal: h.tanggal,
                keterangan: h.keterangan,
                dateLabel: this.fmtShort(h.tanggal),
            }));

            this.holidayForm.tanggal = this.config.defaultTanggal;
            this.holidayForm.mulai = this.config.defaultTanggal;
            this.holidayForm.selesai = this.config.defaultTanggal;

            try {
                const savedTab = localStorage.getItem('kalenderTab');

                if (savedTab === 'libur' || savedTab === 'kalender') {
                    this.tab = savedTab;
                }
            } catch (error) {
                /* penyimpanan lokal opsional */
            }

            this.$nextTick(() => this.initCalendar());
        },

        get monthLabel() {
            return (this.config.bulanNama[this.viewMonth - 1] || '') + ' ' + this.viewYear;
        },

        get badgeTipe() {
            return {
                EVENT: 'bg-blue-50 text-blue-700 border-blue-100',
                UJIAN: 'bg-purple-50 text-purple-700 border-purple-100',
                LIBUR: 'bg-red-50 text-red-700 border-red-100',
                RAPAT: 'bg-amber-50 text-amber-700 border-amber-100',
                DINAS: 'bg-cyan-50 text-cyan-700 border-cyan-100',
                LAINNYA: 'bg-slate-100 text-slate-600 border-slate-200',
            };
        },

        get filteredHolidays() {
            const query = this.filterQuery.trim().toLowerCase();

            return this.holidays.filter((h) => {
                const matchQuery = query === '' || (h.desc || '').includes(query);
                const matchYear = this.filterYear === '' || h.year === this.filterYear;
                const matchMonth = this.filterMonth === '' || h.month === this.filterMonth;

                return matchQuery && matchYear && matchMonth;
            });
        },

        switchTab(tab) {
            this.tab = tab === 'libur' ? 'libur' : 'kalender';

            try {
                localStorage.setItem('kalenderTab', this.tab);
            } catch (error) {
                /* penyimpanan lokal opsional */
            }

            if (this.tab === 'kalender' && state.calendar) {
                this.$nextTick(() => state.calendar.updateSize());
            }
        },

        startTambahLibur() {
            this.switchTab('libur');
            this.resetHoliday();

            this.$nextTick(() => {
                const input = document.getElementById('holidayDate');

                if (input) input.focus();
            });
        },

        goToHolidayTab() {
            this.$store.modals.close('#modalEvent');
            this.switchTab('libur');

            this.$nextTick(() => {
                const input = document.getElementById('holidayDesc');

                if (input) input.focus();
            });
        },

        /* ── Agenda ───────────────────────────────── */

        monthBounds(year, month) {
            return {
                first: ymd(new Date(year, month - 1, 1)),
                last: ymd(new Date(year, month, 0)),
                total: new Date(year, month, 0).getDate(),
            };
        },

        eventStart(ev) {
            if (ev.start instanceof Date) return ymd(ev.start);

            return String(ev.start || '').slice(0, 10);
        },

        eventEnd(ev) {
            if (!ev.end) return this.eventStart(ev);

            const d = ev.end instanceof Date
                ? new Date(ev.end.getFullYear(), ev.end.getMonth(), ev.end.getDate())
                : new Date(String(ev.end).slice(0, 10) + 'T00:00:00');

            d.setDate(d.getDate() - 1);

            return ymd(d);
        },

        formatRange(start, end) {
            const [y1, m1, d1] = start.split('-').map(Number);
            const [y2, m2, d2] = end.split('-').map(Number);
            const short = (m) => this.config.bulanSingkat[m - 1];

            if (start === end) {
                return this.config.bulanNama[m1 - 1] ? this.dayNameLong(start) + ', ' + d1 + ' ' + short(m1) + ' ' + y1 : '';
            }

            if (y1 === y2 && m1 === m2) {
                return d1 + '–' + d2 + ' ' + short(m1) + ' ' + y1;
            }

            return d1 + ' ' + short(m1) + ' – ' + d2 + ' ' + short(m2) + ' ' + y2;
        },

        mapAgenda(items) {
            return items
                .slice()
                .sort((a, b) => String(a.date).localeCompare(String(b.date)) || String(a.title).localeCompare(String(b.title), 'id'))
                .map((item) => {
                    const start = String(item.date || '').slice(0, 10);
                    const end = String(item.end || item.date || '').slice(0, 10);
                    const [, m, d] = start.split('-');

                    return {
                        kind: item.kind,
                        id: item.id,
                        date: start,
                        end,
                        title: item.title,
                        tipe: item.tipe || 'EVENT',
                        color: item.color || '#3b82f6',
                        day: d,
                        mon: this.config.bulanSingkat[Number(m) - 1],
                        range: this.formatRange(start, end < start ? start : end),
                    };
                });
        },

        feedAgenda(events) {
            return events.map((ev) => {
                const props = ev.extendedProps || {};
                const isLibur = props.sumber === 'absensi_libur';

                return {
                    kind: isLibur ? 'libur' : 'event',
                    id: isLibur ? props.holiday_id : props.event_id,
                    date: this.eventStart(ev),
                    end: this.eventEnd(ev),
                    title: ev.title,
                    tipe: isLibur ? 'LIBUR' : (props.tipe || 'EVENT'),
                    color: isLibur ? '#ef4444' : (ev.backgroundColor || '#3b82f6'),
                };
            });
        },

        recalc() {
            if (!state.feedLoaded) {
                return;
            }

            const bounds = this.monthBounds(this.viewYear, this.viewMonth);
            const monthly = state.feedEvents.filter((ev) => this.eventStart(ev) <= bounds.last && this.eventEnd(ev) >= bounds.first);
            const liburOnly = monthly.filter((ev) => (ev.extendedProps || {}).sumber === 'absensi_libur');
            const eventOnly = monthly.filter((ev) => (ev.extendedProps || {}).sumber !== 'absensi_libur');

            this.agenda = this.mapAgenda(this.feedAgenda(monthly));
            this.liburRows = liburOnly.map((ev) => {
                const props = ev.extendedProps || {};
                const start = this.eventStart(ev);

                return {
                    id: props.holiday_id,
                    tanggal: start,
                    keterangan: ev.title,
                    dateLabel: this.fmtShort(start),
                };
            });

            this.kpi.eventBulan = eventOnly.length;
            this.kpi.liburBulan = liburOnly.length;
            this.kpi.totalHari = bounds.total;

            const tanggalLibur = new Set(liburOnly.map((ev) => this.eventStart(ev)));
            let hariSekolah = 0;

            for (let d = 1; d <= bounds.total; d++) {
                const dt = new Date(this.viewYear, this.viewMonth - 1, d);
                const iso = dt.getDay() === 0 ? 7 : dt.getDay();

                if (this.config.liburIso.includes(iso)) continue;
                if (tanggalLibur.has(ymd(dt))) continue;

                hariSekolah++;
            }

            this.kpi.hariSekolah = hariSekolah;

            const yearHolidays = this.holidays.filter((h) => h.year === String(this.viewYear));

            this.kpi.liburTahun = yearHolidays.length;
            this.kpi.cutiTahun = yearHolidays.filter((h) => (h.desc || '').includes('cuti')).length;
        },

        syncMonthViews(viewStart) {
            if (!viewStart) return;

            this.viewYear = viewStart.getFullYear();
            this.viewMonth = viewStart.getMonth() + 1;
            this.recalc();

            if (this.config.tahunList.includes(String(this.viewYear))) {
                this.filterYear = String(this.viewYear);
            } else {
                this.filterYear = '';
            }

            if (this.config.syncYears.includes(this.viewYear)) {
                this.syncYear = String(this.viewYear);
            }

            const url = new URL(window.location.href);

            url.searchParams.set('bulan', this.viewMonth);
            url.searchParams.set('tahun', this.viewYear);
            history.replaceState({}, '', url.toString());
        },

        /* ── Calendar ─────────────────────────────── */

        initCalendar() {
            const el = document.getElementById('kalender');

            if (!el) return;

            if (typeof FullCalendar === 'undefined') {
                this.calendarLoading = false;
                this.showCalendarError('Modul kalender gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.');

                return;
            }

            const calendar = new FullCalendar.Calendar(el, {
                initialView: 'dayGridMonth',
                initialDate: this.config.initialDate,
                locale: 'id',
                firstDay: 1,
                height: 'auto',
                expandRows: true,
                dayMaxEventRows: 3,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listMonth',
                },
                buttonText: { today: 'Hari ini', month: 'Bulan', list: 'Daftar' },
                dayCellClassNames: (arg) => {
                    const iso = arg.date.getDay() === 0 ? 7 : arg.date.getDay();

                    return this.config.liburIso.includes(iso) ? ['kalender-akhir-pekan'] : [];
                },
                events: (fetchInfo, successCallback, failureCallback) => {
                    this.calendarLoading = true;
                    this.calendarError = false;

                    this.loadFeed(fetchInfo).then((events) => {
                        state.feedEvents = events;
                        state.feedLoaded = true;
                        successCallback(events);
                        this.syncMonthViews(calendar.view.currentStart);
                        this.calendarLoading = false;
                    }).catch((error) => {
                        console.error('[Kalender]', error);
                        this.calendarLoading = false;
                        this.showCalendarError('Gagal memuat agenda. Periksa koneksi server.');
                        failureCallback(error);
                    });
                },
                datesSet: (info) => {
                    if (!state.feedLoaded) {
                        return;
                    }

                    this.syncMonthViews(info.view.currentStart);
                },
                eventClick: (info) => {
                    const props = info.event.extendedProps || {};

                    if (props.sumber === 'absensi_libur') {
                        this.startEditHoliday(props.holiday_id, this.eventStart(info.event), info.event.title);
                        return;
                    }

                    this.fetchEvent(props.event_id);
                },
                dateClick: (info) => {
                    this.openEvent({ tgl_mulai: info.dateStr });
                },
                eventDidMount: (info) => {
                    const desc = (info.event.extendedProps || {}).description;
                    info.el.title = desc || info.event.title;
                },
            });

            calendar.render();
            state.calendar = calendar;
        },

        loadFeed(fetchInfo) {
            const params = new URLSearchParams({ start: ymd(fetchInfo.start), end: ymd(fetchInfo.end) });

            return fetch(this.config.urls.feed + '?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            }).then((response) => {
                if (!response.ok) throw new Error('HTTP ' + response.status);

                return response.json();
            }).then((data) => {
                if (!Array.isArray(data)) throw new Error('Format feed tidak valid');

                return data;
            });
        },

        showCalendarError(message) {
            this.calendarErrorText = message || 'Gagal memuat kalender.';
            this.calendarError = true;
        },

        retryFeed() {
            this.calendarLoading = true;
            this.calendarError = false;

            if (state.calendar) {
                state.calendar.refetchEvents();
            } else {
                window.location.reload();
            }
        },

        refetch() {
            if (state.calendar) {
                state.calendar.refetchEvents();
            } else {
                window.location.reload();
            }
        },

        /* ── Event CRUD ───────────────────────────── */

        openEvent(data = {}) {
            this.eventForm = {
                id: data.id ? String(data.id) : '',
                judul: data.judul || '',
                tgl_mulai: data.tgl_mulai || '',
                tgl_selesai: data.tgl_selesai || '',
                tipe: data.tipe || 'EVENT',
                warna: data.warna || '#3b82f6',
                deskripsi: data.deskripsi || '',
            };

            this.$store.modals.open('#modalEvent');
            this.$nextTick(() => {
                const input = document.getElementById('eventJudul');

                if (input) input.focus();
            });
        },

        fetchEvent(id) {
            if (!id) return;

            fetch(this.config.urls.getEvent + '/' + encodeURIComponent(id), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            }).then((response) => response.json()).then((res) => {
                if (!res.status) {
                    Swal.fire('Gagal', res.message || 'Event tidak ditemukan', 'error');
                    return;
                }

                const ev = res.data;
                this.openEvent({
                    id: ev.id,
                    judul: ev.judul,
                    tgl_mulai: ev.tgl_mulai,
                    tgl_selesai: ev.tgl_selesai || '',
                    tipe: ev.tipe,
                    warna: ev.warna,
                    deskripsi: ev.deskripsi || '',
                });
            }).catch(() => Swal.fire('Error', 'Koneksi server bermasalah', 'error'));
        },

        onTipeChange() {
            const map = this.config.tipe || {};

            if (map[this.eventForm.tipe]) {
                this.eventForm.warna = map[this.eventForm.tipe];
            }
        },

        openAgenda(item) {
            if (item.kind === 'libur') {
                this.startEditHoliday(item.id, item.date, item.title);
                return;
            }

            this.fetchEvent(item.id);
        },

        async saveEvent() {
            if (this.eventSaving) return;

            const judul = (this.eventForm.judul || '').trim();
            const mulai = this.eventForm.tgl_mulai || '';
            const selesai = this.eventForm.tgl_selesai || '';

            if (!judul) {
                Swal.fire('Belum lengkap', 'Judul event wajib diisi.', 'warning');
                return;
            }

            if (!mulai) {
                Swal.fire('Belum lengkap', 'Tanggal mulai wajib diisi.', 'warning');
                return;
            }

            if (selesai && selesai < mulai) {
                Swal.fire('Periksa tanggal', 'Tanggal selesai tidak boleh sebelum tanggal mulai.', 'warning');
                return;
            }

            this.eventSaving = true;

            const body = new FormData();
            body.append('_token', this.config.csrf);
            body.append('judul', judul);
            body.append('tgl_mulai', mulai);
            body.append('tgl_selesai', selesai);
            body.append('tipe', this.eventForm.tipe);
            body.append('warna', this.eventForm.warna);
            body.append('deskripsi', (this.eventForm.deskripsi || '').trim());

            const id = this.eventForm.id;
            const url = id ? this.config.urls.update + '/' + id : this.config.urls.create;

            try {
                const result = await this.postForm(url, body);

                if (!result.status) {
                    Swal.fire('Gagal', result.message || 'Event gagal disimpan', 'error');
                    return;
                }

                this.$store.modals.close('#modalEvent');
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: result.message || '', timer: 1400, showConfirmButton: false });
                this.refetch();
            } catch (error) {
                Swal.fire('Error', error.message || 'Koneksi server bermasalah', 'error');
            } finally {
                this.eventSaving = false;
            }
        },

        deleteEvent(id) {
            if (!id) return;

            Swal.fire({
                title: 'Hapus Event?',
                text: 'Event akan dihapus dari kalender akademik.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then((result) => {
                if (!result.isConfirmed) return;

                const body = new FormData();
                body.append('_token', this.config.csrf);

                this.postForm(this.config.urls.delete + '/' + id, body).then((res) => {
                    if (!res.status) {
                        Swal.fire('Gagal', res.message || 'Event gagal dihapus', 'error');
                        return;
                    }

                    if (this.eventForm.id && String(this.eventForm.id) === String(id)) {
                        this.$store.modals.close('#modalEvent');
                    }

                    Swal.fire({ icon: 'success', title: 'Terhapus!', text: res.message || '', timer: 1300, showConfirmButton: false });
                    this.refetch();
                }).catch((error) => Swal.fire('Error', error.message || 'Koneksi server bermasalah', 'error'));
            });
        },

        /* ── Hari libur ───────────────────────────── */

        startEditHoliday(id, date, desc) {
            this.switchTab('libur');
            this.holidayEditing = true;
            this.holidayForm = {
                id: String(id || ''),
                mode: '0',
                tanggal: date || '',
                mulai: date || '',
                selesai: date || '',
                keterangan: desc || '',
            };

            this.$nextTick(() => {
                this.$refs.holidayCard?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                const input = document.getElementById('holidayDesc');

                if (input) input.focus({ preventScroll: true });
            });
        },

        resetHoliday() {
            this.holidayEditing = false;
            this.holidayForm = {
                id: '',
                mode: '0',
                tanggal: this.config.defaultTanggal,
                mulai: this.config.defaultTanggal,
                selesai: this.config.defaultTanggal,
                keterangan: '',
            };
        },

        async saveHoliday() {
            if (this.holidaySaving) return;

            const keterangan = (this.holidayForm.keterangan || '').trim();

            if (!keterangan) {
                Swal.fire('Belum lengkap', 'Keterangan hari libur wajib diisi.', 'warning');
                return;
            }

            const isRange = this.holidayForm.mode === '1';

            if (isRange) {
                if (!this.holidayForm.mulai || !this.holidayForm.selesai) {
                    Swal.fire('Belum lengkap', 'Tanggal mulai dan selesai wajib diisi.', 'warning');
                    return;
                }

                if (this.holidayForm.mulai > this.holidayForm.selesai) {
                    Swal.fire('Periksa tanggal', 'Tanggal mulai tidak boleh setelah tanggal selesai.', 'warning');
                    return;
                }
            } else if (!this.holidayForm.tanggal) {
                Swal.fire('Belum lengkap', 'Tanggal libur wajib diisi.', 'warning');
                return;
            }

            this.holidaySaving = true;

            const body = new FormData();
            body.append('_token', this.config.csrf);
            body.append('id', this.holidayForm.id || '');
            body.append('is_range', isRange ? '1' : '0');
            body.append('keterangan', keterangan);
            body.append('tanggal', isRange ? '' : this.holidayForm.tanggal);
            body.append('tanggal_mulai', isRange ? this.holidayForm.mulai : '');
            body.append('tanggal_selesai', isRange ? this.holidayForm.selesai : '');

            try {
                const result = await this.postForm(this.config.urls.holidaySave, body);

                if (!result.status) {
                    Swal.fire('Gagal', result.message || 'Terjadi kesalahan', 'error');
                    return;
                }

                await Swal.fire({ icon: 'success', title: 'Berhasil!', text: result.message || '', timer: 1500, showConfirmButton: false });
                window.location.reload();
            } catch (error) {
                Swal.fire('Error', error.message || 'Kesalahan koneksi server', 'error');
            } finally {
                this.holidaySaving = false;
            }
        },

        deleteHoliday(id, desc) {
            Swal.fire({
                title: 'Hapus Hari Libur?',
                text: 'Yakin hapus "' + (desc || '') + '"? Tindakan ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then((result) => {
                if (!result.isConfirmed) return;

                const body = new FormData();
                body.append('_token', this.config.csrf);
                body.append('id', id);

                this.postForm(this.config.urls.holidayDelete, body).then((res) => {
                    if (!res.status) {
                        Swal.fire('Gagal', res.message || 'Gagal menghapus', 'error');
                        return;
                    }

                    Swal.fire({ icon: 'success', title: 'Terhapus!', text: res.message || '', timer: 1300, showConfirmButton: false })
                        .then(() => window.location.reload());
                }).catch((error) => Swal.fire('Error', error.message || 'Kesalahan koneksi server', 'error'));
            });
        },

        syncHolidays() {
            const year = this.syncYear || new Date().getFullYear();

            Swal.fire({
                title: 'Sinkronisasi Libur Nasional ' + year,
                html: 'Mengambil data hari libur resmi pemerintah Indonesia tahun <strong>' + year + '</strong> dari API publik.<br><small class="text-slate-400">Membutuhkan koneksi internet aktif di server.</small>',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Mulai Sinkron',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const body = new FormData();
                    body.append('_token', this.config.csrf);
                    body.append('year', year);

                    return this.postForm(this.config.urls.holidaySync, body)
                        .catch(() => ({ status: false, message: 'Tidak dapat terhubung ke server' }));
                },
                allowOutsideClick: () => !Swal.isLoading(),
            }).then((result) => {
                if (!result.isConfirmed || !result.value) return;

                if (result.value.status) {
                    Swal.fire({ icon: 'success', title: 'Sinkronisasi Berhasil!', text: result.value.message || '' })
                        .then(() => window.location.reload());
                } else {
                    Swal.fire('Gagal', result.value.message || 'Gagal sinkronisasi', 'error');
                }
            });
        },

        resetFilter() {
            this.filterQuery = '';
            this.filterYear = this.config.tahunAda ? String(this.viewYear) : '';
            this.filterMonth = '';
        },

        /* ── Helpers ──────────────────────────────── */

        async postForm(url, body) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });

            let result = null;

            try {
                result = await response.json();
            } catch (error) {
                result = null;
            }

            if (!response.ok) {
                throw new Error((result && result.message) || 'Terjadi kesalahan komunikasi dengan server.');
            }

            return result || { status: false, message: 'Respons server tidak valid' };
        },

        hexAlpha(hex, alpha) {
            const clean = String(hex || '').replace('#', '');

            if (!/^[0-9a-fA-F]{6}$/.test(clean)) {
                return 'rgba(59,130,246,' + alpha + ')';
            }

            const r = parseInt(clean.slice(0, 2), 16);
            const g = parseInt(clean.slice(2, 4), 16);
            const b = parseInt(clean.slice(4, 6), 16);

            return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
        },

        fmtDate(iso) {
            const [y, m, d] = String(iso || '').slice(0, 10).split('-');

            return d + '/' + m + '/' + y;
        },

        fmtShort(iso) {
            const [, m, d] = String(iso || '').slice(0, 10).split('-');

            return d + '/' + m;
        },

        dayOf(iso) {
            return String(iso || '').slice(8, 10);
        },

        monthShort(iso) {
            return this.config.bulanSingkat[Number(String(iso || '').slice(5, 7)) - 1] || '';
        },

        dayName(iso) {
            const names = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const d = new Date(String(iso || '').slice(0, 10) + 'T00:00:00');

            return names[d.getDay()] || '';
        },

        dayNameLong(iso) {
            return this.dayName(iso);
        },

        isCuti(h) {
            return (h.keterangan || '').toLowerCase().includes('cuti');
        },

        hariIconClass(h) {
            if (this.isCuti(h)) {
                return 'border-amber-200 bg-amber-50 text-amber-700';
            }

            const d = new Date(String(h.tanggal || '').slice(0, 10) + 'T00:00:00');

            return d.getDay() === 0
                ? 'border-red-200 bg-red-50 text-red-600'
                : 'border-blue-200 bg-blue-50 text-blue-600';
        },
    }));
});
</script>
@endsection
