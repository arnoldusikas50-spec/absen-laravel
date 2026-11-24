@extends('layouts.main')

@section('content')
@php
    $fmtTrim = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    $minFmt = $fmtTrim($minPersen);
    $lowFmt = $fmtTrim($minPersen - 10);
    $meta = $meta ?? [];
    $periode = $meta['periode'] ?? (($semester == 1) ? "Juli {$tahun} – Desember {$tahun}" : "Januari {$tahun} – Juni {$tahun}");
    $tpLabel = $meta['tahunAjaran'] ?? (($semester == 1) ? "{$tahun}/".($tahun + 1) : ($tahun - 1)."/{$tahun}");
    $isPartial = ! empty($meta['isPartial']);
    $isStale = ! empty($meta['isStale']);
    $totalGuru = (int) ($meta['totalGuru'] ?? ($summary['guru'] ?? 0));
    $curYear = (int) date('Y');
    $totalDirekap = (int) ($summary['guru'] ?? 0);
    $pembagi = max(1, $totalDirekap);
    $pct = fn ($n) => number_format(((int) $n / $pembagi) * 100, 0);

    $gayaStatus = [
        'tidak_layak' => [
            'kode' => 'tidak_layak', 'label' => 'Tidak Layak', 'ikon' => 'bi-x-octagon-fill',
            'kelas' => 'bg-rose-50 text-rose-700', 'baris' => 'bg-rose-50/60', 'bar' => '#ef4444',
        ],
        'perhatian' => [
            'kode' => 'perhatian', 'label' => 'Perhatian', 'ikon' => 'bi-exclamation-triangle-fill',
            'kelas' => 'bg-amber-50 text-amber-700', 'baris' => 'bg-amber-50/60', 'bar' => '#f59e0b',
        ],
        'layak' => [
            'kode' => 'layak', 'label' => 'Layak', 'ikon' => 'bi-check-circle-fill',
            'kelas' => 'bg-emerald-50 text-emerald-700', 'baris' => '', 'bar' => '#22c55e',
        ],
    ];

    $barisRekap = [];
    foreach ($rekap as $r) {
        $persen = (float) $r->persentase_kehadiran;
        $status = $gayaStatus[$r->status_kelayakan] ?? $gayaStatus['layak'];
        $poin = (int) $r->total_poin;
        $denda = (float) ($r->total_denda ?? 0);
        $nama = trim((string) ($r->nama_guru ?? '')) ?: 'Guru';

        $barisRekap[] = [
            'id' => (int) $r->id,
            'cari' => mb_strtolower(implode(' ', array_filter([
                $nama, $r->nip ?? '', $status['label'],
            ]))),
            'nama' => $nama,
            'nip' => (string) ($r->nip ?? ''),
            'hari' => (int) $r->total_hari_efektif,
            'hadir' => (int) $r->total_hadir,
            'telat' => (int) $r->total_telat,
            'izin' => (int) $r->total_izin,
            'sakit' => (int) $r->total_sakit,
            'dinas' => (int) $r->total_dinas,
            'alpha' => (int) $r->total_alpha,
            'persen' => round($persen, 1),
            'persenTeks' => number_format($persen, 1),
            'barWidth' => min(max($persen, 0), 100),
            'status' => $status,
            'statusLabel' => $status['label'],
            'poin' => $poin,
            'poinTeks' => ($poin >= 0 ? '+' : '').number_format($poin),
            'denda' => $denda,
            'dendaTeks' => $denda > 0 ? 'Rp '.number_format($denda, 0, ',', '.') : '—',
        ];
    }

    // Kelas Tailwind ditulis utuh (bukan dirakit) agar ikut ter-scan saat build.
    $kpi = [
        [
            'kode' => '', 'label' => 'Total Guru Direkap', 'nilai' => $totalDirekap, 'tambahan' => null,
            'ket' => 'dari '.$totalGuru.' guru aktif', 'ikon' => 'bi-people-fill',
            'ikonKelas' => 'bg-blue-50 text-blue-600', 'aksen' => 'border-l-blue-500',
        ],
        [
            'kode' => 'layak', 'label' => 'Layak', 'nilai' => (int) ($summary['layak'] ?? 0), 'tambahan' => $pct($summary['layak'] ?? 0).'%',
            'ket' => 'Kehadiran ≥ '.$minFmt.'%', 'ikon' => 'bi-check-circle-fill',
            'ikonKelas' => 'bg-emerald-50 text-emerald-600', 'aksen' => 'border-l-emerald-500',
        ],
        [
            'kode' => 'perhatian', 'label' => 'Perhatian', 'nilai' => (int) ($summary['perhatian'] ?? 0), 'tambahan' => $pct($summary['perhatian'] ?? 0).'%',
            'ket' => 'Kehadiran '.$lowFmt.'–'.$minFmt.'%', 'ikon' => 'bi-exclamation-triangle-fill',
            'ikonKelas' => 'bg-amber-50 text-amber-600', 'aksen' => 'border-l-amber-500',
        ],
        [
            'kode' => 'tidak_layak', 'label' => 'Tidak Layak', 'nilai' => (int) ($summary['tidak_layak'] ?? 0), 'tambahan' => $pct($summary['tidak_layak'] ?? 0).'%',
            'ket' => 'Kehadiran < '.$lowFmt.'%', 'ikon' => 'bi-x-octagon-fill',
            'ikonKelas' => 'bg-rose-50 text-rose-600', 'aksen' => 'border-l-rose-500',
        ],
    ];

    $config = [
        'baris' => $barisRekap,
        'periode' => $periode,
    ];

    $generateUrl = url('admin/presensi/rekap-semester/generate');
    $generatedAt = ! empty($meta['generatedAt'])
        ? date('d/m/Y H:i', strtotime($meta['generatedAt']))
        : (! empty($rekap[0]->generated_at) ? date('d/m/Y H:i', strtotime($rekap[0]->generated_at)) : '—');
    $maxAbsensi = ! empty($meta['maxAbsensi']) ? date('d/m/Y', strtotime($meta['maxAbsensi'])) : null;
    $semLabel = $semester == 1 ? 'Ganjil' : 'Genap';
    $judulRekap = 'Rekap Semester '.$semLabel.' '.$tahun;
@endphp

<div class="space-y-4" x-data="rekapSemester(@js($config))">

    <x-ui.page-header title="{{ $judul ?? 'Rekap Kehadiran Per Semester' }}" icon="bi-journal-check" tone="emerald"
        subtitle="Semester {{ $semLabel }} &middot; TP {{ $tpLabel }} &middot; {{ $periode }}{{ $isPartial ? ' · Parsial s/d hari ini' : '' }}"
        :crumbs="['Presensi', 'Rekap Semester']">
        <x-slot:actions>
            <button type="button" x-on:click="location.reload()" title="Muat ulang"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-200 print:hidden">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            <button type="button" x-on:click="window.print()" title="Cetak halaman"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100 print:hidden">
                <i class="bi bi-printer"></i> Cetak
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Filter periode + aksi generate/export --}}
    <x-ui.card body-class="p-5 print:hidden">
        <form method="get" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="fTahun" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Tahun Periode</label>
                <select name="tahun" id="fTahun"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    @for ($y = $curYear + 1; $y >= attendance_tahun_tertua(); $y--)
                        <option value="{{ $y }}" @selected((int) $tahun === $y)>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label for="fSemester" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Semester</label>
                <select name="semester" id="fSemester"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="1" @selected((int) $semester === 1)>Ganjil (Jul–Des)</option>
                    <option value="2" @selected((int) $semester === 2)>Genap (Jan–Jun)</option>
                </select>
            </div>
            <button type="submit"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                <i class="bi bi-search"></i> Tampilkan
            </button>
            <a href="{{ url('admin/presensi/rekap-semester') }}" title="Kembali ke periode berjalan"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-x-circle"></i> Reset
            </a>
        </form>

        <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
            <form id="formGenerateRekap" method="post" action="{{ $generateUrl }}"
                x-on:submit.prevent="konfirmasiGenerate($event)">
                @csrf
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <input type="hidden" name="semester" value="{{ $semester }}">
                <button type="submit" title="Hitung ulang dari data absensi_harian"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-emerald-700">
                    <i class="bi bi-arrow-repeat"></i> Generate Rekap
                </button>
            </form>

            @if (! empty($rekap))
                <a href="{{ url('admin/presensi/rekap-semester/export?tahun='.$tahun.'&semester='.$semester) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-700 no-underline transition-colors hover:bg-emerald-100">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                </a>
                <a href="{{ url('admin/presensi/rekap-semester/pdf?tahun='.$tahun.'&semester='.$semester) }}"
                    title="Download rekap semester sebagai PDF"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-bold text-rose-700 no-underline transition-colors hover:bg-rose-100">
                    <i class="bi bi-file-earmark-pdf"></i> Download PDF
                </a>
            @else
                <span title="Belum ada data untuk diekspor"
                    class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-400">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                </span>
                <span title="Belum ada data untuk diekspor"
                    class="inline-flex cursor-not-allowed items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-400">
                    <i class="bi bi-file-earmark-pdf"></i> Download PDF
                </span>
            @endif
        </div>

        <p class="mb-0 mt-3 text-[11px] leading-relaxed text-slate-400">
            <i class="bi bi-info-circle"></i> Rumus: <strong class="text-slate-600">Hadir</strong> = ONTIME + LATE + WFH + dinas disetujui &middot;
            <strong class="text-slate-600">Alpha</strong> = hari efektif − hadir − izin/sakit/cuti/dinas-menunggu &middot;
            <strong class="text-slate-600">%</strong> = hadir ÷ hari efektif × 100. Dinas resmi dihitung hadir.
        </p>
    </x-ui.card>

    @if ($isStale)
        <div class="flex gap-2.5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-800">
            <i class="bi bi-exclamation-triangle-fill mt-0.5 shrink-0 text-base"></i>
            <p class="mb-0">
                <strong>Data mungkin kedaluwarsa.</strong>
                Ada absensi (s/d {{ $maxAbsensi ?? '-' }}) atau guru aktif ({{ $totalGuru }})
                yang lebih baru dari waktu generate ({{ $generatedAt }}).
                Klik <strong>Generate Rekap</strong> untuk menghitung ulang.
            </p>
        </div>
    @endif

    {{-- Ringkasan per status (klik untuk memfilter tabel) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($kpi as $item)
            <x-ui.card body-class="p-4" role="button" tabindex="0"
                class="cursor-pointer border-l-4 {{ $item['aksen'] }} transition hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                x-bind:class="status === '{{ $item['kode'] }}' ? 'ring-2 ring-blue-500/30' : ''"
                x-bind:aria-pressed="status === '{{ $item['kode'] }}' ? 'true' : 'false'"
                x-on:click="setFilter('{{ $item['kode'] }}')"
                x-on:keydown.enter.prevent="setFilter('{{ $item['kode'] }}')">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-xl {{ $item['ikonKelas'] }}">
                        <i class="bi {{ $item['ikon'] }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $item['label'] }}</p>
                        <div class="mt-0.5 flex items-baseline gap-2">
                            <p class="text-2xl font-extrabold tabular-nums text-slate-900">{{ number_format($item['nilai']) }}</p>
                            @if ($item['tambahan'])
                                <span class="text-[11px] font-bold tabular-nums text-slate-400">{{ $item['tambahan'] }}</span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-[11px] text-slate-400">{{ $item['ket'] }}</p>
                    </div>
                </div>
            </x-ui.card>
        @endforeach
    </div>

    @if ((int) ($summary['tidak_layak'] ?? 0) > 0)
        <div class="flex items-center gap-2.5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs leading-relaxed text-rose-800 print:hidden">
            <i class="bi bi-exclamation-octagon-fill shrink-0 text-base"></i>
            <p class="mb-0">
                <strong>{{ (int) $summary['tidak_layak'] }} guru</strong> memiliki kehadiran di bawah {{ $lowFmt }}% dan perlu tindak lanjut segera.
                <button type="button" x-on:click="setFilter('tidak_layak')" class="font-bold underline underline-offset-2 hover:text-rose-900">Lihat daftar</button>
            </p>
        </div>
    @endif

    <x-ui.card body-class="p-0" title="{{ $judulRekap }}" icon="bi-table" subtitle="Klik judul kolom untuk mengurutkan data.">
        <x-slot:meta>
            <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-bold tabular-nums text-blue-700">
                <i class="bi bi-graph-up-arrow"></i> {{ number_format((float) ($summary['rata_kehadiran'] ?? 0), 1) }}% rata-rata
            </span>
        </x-slot:meta>

        <x-slot:toolbar>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @if (! empty($rekap))
                    <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="Filter status">
                        <button type="button" x-on:click="setFilter('')"
                            x-bind:class="status === '' ? 'border-slate-800 bg-slate-800 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1.5 text-[11px] font-bold transition-colors">
                            <span class="h-2 w-2 rounded-full bg-slate-400"></span> Semua
                        </button>
                        <button type="button" x-on:click="setFilter('layak')"
                            x-bind:class="status === 'layak' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1.5 text-[11px] font-bold transition-colors">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Layak
                        </button>
                        <button type="button" x-on:click="setFilter('perhatian')"
                            x-bind:class="status === 'perhatian' ? 'border-amber-500 bg-amber-500 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1.5 text-[11px] font-bold transition-colors">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span> Perhatian
                        </button>
                        <button type="button" x-on:click="setFilter('tidak_layak')"
                            x-bind:class="status === 'tidak_layak' ? 'border-rose-600 bg-rose-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1.5 text-[11px] font-bold transition-colors">
                            <span class="h-2 w-2 rounded-full bg-rose-500"></span> Tidak Layak
                        </button>
                    </div>

                    <div class="relative w-full sm:ml-auto sm:w-64 print:hidden">
                        <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                        <input type="text" x-model="cari" x-ref="cari" autocomplete="off" x-on:input="halaman = 1"
                            placeholder="Cari nama guru / NIP..."
                            aria-label="Cari guru pada rekap semester"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-8 text-xs font-semibold text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <button type="button" x-show="cari !== ''" x-cloak x-on:click="bersihkan()"
                            class="absolute right-2 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-200/70 hover:text-slate-700"
                            title="Bersihkan pencarian">
                            <i class="bi bi-x text-xs"></i>
                        </button>
                    </div>
                @endif
            </div>
        </x-slot:toolbar>

        @if (empty($rekap))
            <div class="p-5">
                <x-ui.empty icon="bi-inbox" title="Belum ada data rekap untuk periode ini."
                    description="Klik Generate Rekap untuk menghitung dari data absensi harian ({{ $periode }}).">
                    <button type="submit" form="formGenerateRekap"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-emerald-700">
                        <i class="bi bi-arrow-repeat"></i> Generate Rekap Sekarang
                    </button>
                </x-ui.empty>
            </div>
        @else
            <div class="max-h-[68vh] overflow-auto" tabindex="0">
                <table class="w-full min-w-[1080px] text-[13px]">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="w-10 px-3 py-3 text-center font-bold">#</th>
                            <th class="min-w-[200px] px-3 py-3 text-left font-bold">
                                <button type="button" x-on:click="urutkan('nama')"
                                    class="inline-flex items-center gap-1 font-bold uppercase tracking-wider transition-colors hover:text-slate-800">
                                    Nama Guru <i class="bi text-[10px]" x-bind:class="ikonUrut('nama')"></i>
                                </button>
                            </th>
                            <th class="min-w-[130px] px-3 py-3 text-left font-bold">
                                <button type="button" x-on:click="urutkan('nip')"
                                    class="inline-flex items-center gap-1 font-bold uppercase tracking-wider transition-colors hover:text-slate-800">
                                    NIP <i class="bi text-[10px]" x-bind:class="ikonUrut('nip')"></i>
                                </button>
                            </th>
                            @foreach ([
                                ['hari', 'Eff.', 'text-slate-500', 'Hari efektif'],
                                ['hadir', 'Hadir', 'text-emerald-600', 'Termasuk dinas disetujui'],
                                ['telat', 'Telat', 'text-amber-600', 'Total keterlambatan'],
                                ['izin', 'Izin', 'text-blue-600', 'Izin disetujui'],
                                ['sakit', 'Sakit', 'text-cyan-600', 'Sakit dengan surat'],
                                ['dinas', 'Dinas', 'text-slate-500', 'Dinas luar disetujui'],
                                ['alpha', 'Alpha', 'text-rose-600', 'Tanpa keterangan'],
                            ] as [$kunci, $kolom, $warna, $tip])
                                <th class="min-w-[74px] px-3 py-3 text-center font-bold" title="{{ $tip }}">
                                    <button type="button" x-on:click="urutkan('{{ $kunci }}')"
                                        class="inline-flex items-center gap-1 font-bold uppercase tracking-wider transition-colors hover:text-slate-800 {{ $warna }}">
                                        {{ $kolom }} <i class="bi text-[10px]" x-bind:class="ikonUrut('{{ $kunci }}')"></i>
                                    </button>
                                </th>
                            @endforeach
                            <th class="min-w-[150px] px-3 py-3 text-left font-bold">
                                <button type="button" x-on:click="urutkan('persen')"
                                    class="inline-flex items-center gap-1 font-bold uppercase tracking-wider transition-colors hover:text-slate-800">
                                    Persentase <i class="bi text-[10px]" x-bind:class="ikonUrut('persen')"></i>
                                </button>
                            </th>
                            <th class="w-[110px] px-3 py-3 text-center font-bold">
                                <button type="button" x-on:click="urutkan('statusLabel')"
                                    class="inline-flex items-center gap-1 font-bold uppercase tracking-wider transition-colors hover:text-slate-800">
                                    Status <i class="bi text-[10px]" x-bind:class="ikonUrut('statusLabel')"></i>
                                </button>
                            </th>
                            <th class="w-[80px] px-3 py-3 text-center font-bold">
                                <button type="button" x-on:click="urutkan('poin')"
                                    class="inline-flex items-center gap-1 font-bold uppercase tracking-wider transition-colors hover:text-slate-800">
                                    Poin <i class="bi text-[10px]" x-bind:class="ikonUrut('poin')"></i>
                                </button>
                            </th>
                            <th class="w-[110px] px-3 py-3 text-center font-bold">
                                <button type="button" x-on:click="urutkan('denda')"
                                    class="inline-flex items-center gap-1 font-bold uppercase tracking-wider transition-colors hover:text-slate-800">
                                    Denda <i class="bi text-[10px]" x-bind:class="ikonUrut('denda')"></i>
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr x-show="barisTampil.length === 0">
                            <td colspan="14" class="px-3 py-12 text-center">
                                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl text-slate-400">
                                    <i class="bi bi-search"></i>
                                </span>
                                <p class="text-sm font-bold text-slate-700">Tidak ada guru yang cocok</p>
                                <p class="mt-1 text-xs text-slate-400">Ubah kata kunci pencarian atau pilih status lain.</p>
                                <button type="button" x-on:click="bersihkan()"
                                    class="mt-3 inline-flex items-center gap-1.5 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition-colors hover:bg-blue-100">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset filter
                                </button>
                            </td>
                        </tr>

                        <template x-for="(row, index) in barisHalaman" :key="row.id">
                            <tr class="transition-colors hover:bg-slate-50/60" x-bind:class="row.status.baris">
                                <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400"
                                    x-text="(halamanAktif - 1) * perHalaman + index + 1"></td>

                                <td class="px-3 py-2.5">
                                    <p class="truncate text-sm font-semibold text-slate-800" x-bind:title="row.nama" x-text="row.nama"></p>
                                </td>

                                <td class="px-3 py-2.5 font-mono text-xs tabular-nums text-slate-500" x-text="row.nip || '—'"></td>

                                <td class="px-3 py-2.5 text-center text-xs font-semibold tabular-nums text-slate-600" x-text="row.hari"></td>

                                <td class="px-3 py-2.5 text-center text-xs font-bold tabular-nums text-emerald-600" title="Termasuk dinas disetujui" x-text="row.hadir"></td>

                                <td class="px-3 py-2.5 text-center text-xs font-bold tabular-nums text-amber-600" x-text="row.telat"></td>

                                <td class="px-3 py-2.5 text-center text-xs tabular-nums text-blue-600" x-text="row.izin"></td>

                                <td class="px-3 py-2.5 text-center text-xs tabular-nums text-cyan-600" x-text="row.sakit"></td>

                                <td class="px-3 py-2.5 text-center text-xs tabular-nums text-slate-500" x-text="row.dinas"></td>

                                <td class="px-3 py-2.5 text-center text-xs font-bold tabular-nums text-rose-600" x-text="row.alpha"></td>

                                <td class="px-3 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100" role="progressbar"
                                            x-bind:aria-valuenow="row.barWidth" aria-valuemin="0" aria-valuemax="100"
                                            x-bind:title="row.persenTeks + '%'">
                                            <div class="h-full rounded-full transition-all"
                                                x-bind:style="'width:' + row.barWidth + '%; background:' + row.status.bar"></div>
                                        </div>
                                        <span class="min-w-[48px] text-right text-xs font-bold tabular-nums text-slate-700" x-text="row.persenTeks + '%'"></span>
                                    </div>
                                </td>

                                <td class="px-3 py-2.5 text-center">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold" x-bind:class="row.status.kelas">
                                        <i class="bi text-[10px]" x-bind:class="row.status.ikon"></i>
                                        <span x-text="row.status.label"></span>
                                    </span>
                                </td>

                                <td class="px-3 py-2.5 text-center text-xs font-bold tabular-nums"
                                    x-bind:class="row.poin >= 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="row.poinTeks"></td>

                                <td class="px-3 py-2.5 text-center text-xs font-bold tabular-nums text-rose-600" x-text="row.dendaTeks"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            <div class="flex flex-wrap items-center justify-between gap-3 text-[11px] text-slate-400">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="tabular-nums"
                        x-text="barisTampil.length === 0
                            ? 'Tidak ada baris ditampilkan'
                            : 'Menampilkan ' + nomorAwal + '–' + nomorAkhir + ' dari ' + barisTampil.length + ' guru'"></span>
                    <span title="Waktu generate terakhir">
                        <i class="bi bi-clock"></i> Generate: <strong class="text-slate-600">{{ $generatedAt }}</strong>
                    </span>
                    @if ($maxAbsensi)
                        <span title="Absensi harian terbaru pada periode ini">
                            <i class="bi bi-calendar-check"></i> Absensi terbaru: <strong class="text-slate-600">{{ $maxAbsensi }}</strong>
                        </span>
                    @endif
                    <span>
                        <i class="bi bi-sliders"></i> Layak ≥ {{ $minFmt }}% &middot; Perhatian {{ $lowFmt }}–{{ $minFmt }}% &middot; Tidak layak &lt; {{ $lowFmt }}% (standar min. {{ $minFmt }}%)
                    </span>
                </div>

                @if (! empty($rekap))
                    <div class="flex items-center gap-1.5 print:hidden">
                        <label for="fPerHalaman" class="sr-only">Baris per halaman</label>
                        <select id="fPerHalaman" x-model.number="perHalaman" x-on:change="halaman = 1"
                            class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <button type="button" x-on:click="halaman = halamanAktif - 1" x-bind:disabled="halamanAktif <= 1"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition-colors hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40"
                            title="Halaman sebelumnya">
                            <i class="bi bi-chevron-left text-[10px]"></i>
                        </button>
                        <span class="tabular-nums" x-text="halamanAktif + ' / ' + totalHalaman"></span>
                        <button type="button" x-on:click="halaman = halamanAktif + 1" x-bind:disabled="halamanAktif >= totalHalaman"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition-colors hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40"
                            title="Halaman berikutnya">
                            <i class="bi bi-chevron-right text-[10px]"></i>
                        </button>
                    </div>
                @endif
            </div>
        </x-slot:footer>
    </x-ui.card>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('rekapSemester', (config) => ({
        semuaBaris: config.baris || [],
        periode: config.periode || '',
        cari: '',
        status: '',
        urut: '',
        arah: 'asc',
        halaman: 1,
        perHalaman: 25,

        cocok(row) {
            const kunci = this.cari.trim().toLowerCase();

            return kunci === '' || (row.cari || '').includes(kunci);
        },

        get barisTampil() {
            const rows = this.semuaBaris.filter((row) => this.cocok(row)
                && (this.status === '' || row.status.kode === this.status));

            if (this.urut === '') {
                return rows;
            }

            const arah = this.arah === 'asc' ? 1 : -1;
            const kunci = this.urut;

            return rows.slice().sort((a, b) => {
                if (typeof a[kunci] === 'number' && typeof b[kunci] === 'number') {
                    return (a[kunci] - b[kunci]) * arah;
                }

                return String(a[kunci]).localeCompare(String(b[kunci]), 'id', { numeric: true }) * arah;
            });
        },

        get totalHalaman() {
            return Math.max(1, Math.ceil(this.barisTampil.length / this.perHalaman));
        },

        get halamanAktif() {
            return Math.min(this.halaman, this.totalHalaman);
        },

        get barisHalaman() {
            const mulai = (this.halamanAktif - 1) * this.perHalaman;

            return this.barisTampil.slice(mulai, mulai + this.perHalaman);
        },

        get nomorAwal() {
            return this.barisTampil.length === 0 ? 0 : (this.halamanAktif - 1) * this.perHalaman + 1;
        },

        get nomorAkhir() {
            return Math.min(this.halamanAktif * this.perHalaman, this.barisTampil.length);
        },

        setFilter(kode) {
            this.status = kode;
            this.halaman = 1;
        },

        urutkan(kunci) {
            if (this.urut === kunci) {
                this.arah = this.arah === 'asc' ? 'desc' : 'asc';
            } else {
                this.urut = kunci;
                this.arah = 'asc';
            }

            this.halaman = 1;
        },

        ikonUrut(kunci) {
            if (this.urut !== kunci) {
                return 'bi-arrow-down-up text-slate-300';
            }

            return this.arah === 'asc' ? 'bi-arrow-up text-blue-600' : 'bi-arrow-down text-blue-600';
        },

        bersihkan() {
            this.cari = '';
            this.status = '';
            this.urut = '';
            this.halaman = 1;
            this.$nextTick(() => this.$refs.cari?.focus());
        },

        konfirmasiGenerate(event) {
            const form = event.target;

            Swal.fire({
                title: 'Generate Rekap?',
                html: 'Menghitung ulang kehadiran <strong>' + this.periode + '</strong> dari data absensi harian.'
                    + '<br><span class="text-sm text-slate-400">Data lama periode ini akan diperbarui.</span>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Generate',
                cancelButtonText: 'Batal',
            }).then((hasil) => {
                if (!hasil.isConfirmed) {
                    return;
                }

                Swal.fire({
                    title: 'Menghitung...',
                    text: 'Mohon tunggu, sedang merekap kehadiran semester.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading(),
                });

                form.submit();
            });
        },
    }));
});
</script>
@endsection
