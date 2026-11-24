@extends('layouts.main')

@section('content')
@php
    $blnIdx = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    // Kelas Tailwind ditulis utuh (bukan dirakit) agar ikut ter-scan saat build.
    $tingkat = static function (int $level): array {
        if ($level >= 7) {
            return ['label' => 'III (7)', 'kelas' => 'bg-rose-50 text-rose-700'];
        }
        if ($level >= 5) {
            return ['label' => 'II (5)', 'kelas' => 'bg-amber-50 text-amber-700'];
        }

        return ['label' => 'I (3)', 'kelas' => 'bg-blue-50 text-blue-700'];
    };

    $barisSurat = [];
    foreach ($letters as $row) {
        $batal = ($row->status ?? 'aktif') === 'dibatalkan';
        $level = (int) ($row->level ?? 3);
        $idSiswa = (int) ($row->id_siswa ?? 0);

        $nomor = \App\Libraries\SuratNomor::build(
            (int) ($row->nomor_urut ?? 0),
            (int) ($row->bulan ?? 0),
            (int) ($row->tahun ?? 0),
            $setting,
            $level,
            !empty($row->created_at) ? date('d-m-Y', strtotime($row->created_at)) : null
        );

        $tanggalTerbit = !empty($row->created_at) ? date('d-m-Y H:i', strtotime($row->created_at)) : '-';
        $sumber = ($row->sumber ?? 'cron') === 'manual' ? 'Input manual' : 'Otomatis';
        $alpha = (int) ($row->jumlah_alpha ?? 0);
        $alphaLive = isset($alpha_live[$idSiswa]) ? (int) $alpha_live[$idSiswa]['alpha'] : null;
        $adaTeguran = isset($teguranSiswa[$idSiswa]);
        $penerima = trim((string) ($row->penerima ?? ''));
        $nisn = trim((string) ($row->nisn ?? ''));
        $nohp = trim((string) ($row->nohp_penerima ?? ''));

        $barisSurat[] = [
            'id' => (int) $row->id,
            'idSiswa' => $idSiswa,
            'nomor' => $nomor,
            'nama' => trim((string) ($row->nama ?? '')) ?: '-',
            'kelas' => trim((string) ($row->kelas_awal ?? '')),
            'nis' => trim((string) ($row->nis ?? '')),
            'nisn' => $nisn,
            'penerima' => $penerima,
            'nohp' => $nohp,
            'tingkat' => $level,
            'tingkatLabel' => $tingkat($level)['label'],
            'tingkatKelas' => $tingkat($level)['kelas'],
            'alpha' => $alpha,
            'drift' => ! $batal && $alphaLive !== null && $alphaLive !== $alpha,
            'alphaLive' => $alphaLive,
            'alphaBulanIni' => $alphaLive ?? 0,
            'teguran' => $adaTeguran,
            'terlambat' => (int) ($row->jumlah_terlambat ?? 0),
            'terbit' => $tanggalTerbit,
            'terbitSort' => !empty($row->created_at) ? strtotime($row->created_at) : 0,
            'sumber' => $sumber,
            'status' => $batal
                ? ['kode' => 'dibatalkan', 'label' => 'Dibatalkan', 'ikon' => 'bi-x-circle', 'kelas' => 'bg-slate-100 text-slate-500', 'baris' => 'bg-slate-50/40 opacity-70']
                : ['kode' => 'aktif', 'label' => 'Aktif', 'ikon' => 'bi-check2-circle', 'kelas' => 'bg-emerald-50 text-emerald-700', 'baris' => ''],
            'statusLabel' => $batal ? 'Dibatalkan' : 'Aktif',
            'keteranganBatal' => trim((string) ($row->keterangan_batal ?? '')),
            'cari' => mb_strtolower(implode(' ', array_filter([
                $nomor, $row->nama ?? '', $row->nis ?? '', $nisn, $penerima, $nohp,
                $row->kelas_awal ?? '', $tanggalTerbit, $sumber,
            ]))),
        ];
    }

    $barisKandidat = [];
    foreach ($kandidat as $k) {
        $level = (int) ($k['level'] ?? 3);

        $barisKandidat[] = [
            'idSiswa' => (int) ($k['id_siswa'] ?? 0),
            'nama' => trim((string) ($k['nama'] ?? '')) ?: '-',
            'nis' => trim((string) ($k['nis'] ?? '')),
            'alpha' => (int) ($k['alpha'] ?? 0),
            'terlambat' => (int) ($k['terlambat'] ?? 0),
            'level' => $level,
            'tingkatLabel' => $tingkat($level)['label'],
            'tingkatKelas' => $tingkat($level)['kelas'],
        ];
    }

    $jumlahAktif = count(array_filter($letters, static fn ($l) => ($l->status ?? 'aktif') === 'aktif'));
    $jumlahBatal = count($letters) - $jumlahAktif;

    $kpi = [
        [
            'status' => 'aktif', 'label' => 'Surat Aktif', 'ekspresi' => 'jumlahAktif', 'satuan' => 'surat',
            'ikon' => 'bi-envelope-check', 'ket' => 'Berlaku pada periode ini',
            'aksen' => 'border-l-blue-500', 'ikonKelas' => 'bg-blue-50 text-blue-600',
        ],
        [
            'status' => 'dibatalkan', 'label' => 'Dibatalkan', 'ekspresi' => 'jumlahDibatalkan', 'satuan' => 'surat',
            'ikon' => 'bi-envelope-slash', 'ket' => 'Arsip, tidak berlaku',
            'aksen' => 'border-l-slate-400', 'ikonKelas' => 'bg-slate-100 text-slate-500',
        ],
        [
            'status' => '', 'label' => 'Belum Ada Surat', 'ekspresi' => null, 'satuan' => 'siswa',
            'ikon' => 'bi-person-exclamation', 'ket' => 'Melewati ambang, belum terbit',
            'aksen' => 'border-l-emerald-500', 'ikonKelas' => 'bg-emerald-50 text-emerald-600',
        ],
        [
            'status' => '', 'label' => 'Ambang', 'ekspresi' => null, 'satuan' => 'hari alpha',
            'ikon' => 'bi-sliders', 'ket' => 'Tingkat surat yang tersedia',
            'aksen' => 'border-l-amber-500', 'ikonKelas' => 'bg-amber-50 text-amber-600',
        ],
    ];

    $config = [
        'baris' => $barisSurat,
        'kandidat' => $barisKandidat,
        'bulan' => (int) $bulan,
        'tahun' => (int) $tahun,
        'periode' => ($blnIdx[(int) $bulan] ?? '-').' '.(int) $tahun,
        'ambang' => \App\Services\SuratPanggilanService::AMBANG,
        'labelLevel' => \App\Services\SuratPanggilanService::LABEL_LEVEL,
        'urls' => [
            'buat' => url('admin/presensi/surat-panggilan/buat'),
            'hitungUlang' => url('admin/presensi/surat-panggilan/hitung-ulang'),
            'notifikasi' => url('admin/presensi/surat-panggilan/notifikasi'),
            'batal' => url('admin/presensi/surat-panggilan/batal'),
        ],
        'csrf' => csrf_token(),
    ];
@endphp

<div class="space-y-4" x-data="suratPanggilan(@js($config))">

    <x-ui.page-header title="{{ $judul ?? 'Surat Panggilan Orang Tua' }}" icon="bi-envelope-open" tone="amber"
        subtitle="Surat otomatis ke orang tua/wali siswa yang kehadirannya sudah melewati ambang. Terbit setiap tanggal 7, 14, 21, dan akhir bulan."
        :crumbs="['Presensi', 'Surat Panggilan']">
        <x-slot:actions>
            <a href="{{ url('admin/presensi/siswa/rekap') }}" title="Sumber angka alpha: rekap presensi siswa"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-file-earmark-spreadsheet"></i> Rekap Siswa
            </a>
            <a href="{{ url('admin/presensi/surat-teguran') }}" title="Surat teguran untuk guru"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-envelope-exclamation"></i> Surat Teguran Guru
            </a>
            <button type="button" x-on:click="hitungUlang()" :disabled="memproses" title="Hitung ulang angka surat aktif dari data absen saat ini"
                class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-3.5 py-2.5 text-xs font-bold text-white transition-colors hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50">
                <i class="bi" :class="memproses && proses === 'hitung' ? 'bi-arrow-repeat animate-spin' : 'bi-arrow-repeat'"></i>
                Hitung Ulang
            </button>
            <button type="button" x-on:click="kirimNotifikasi()" :disabled="memproses" title="Kirim notifikasi WA/Telegram ke wali kelas untuk surat panggilan periode ini"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2.5 text-xs font-bold text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                <i class="bi" :class="memproses && proses === 'notifikasi' ? 'bi-arrow-repeat animate-spin' : 'bi-send'"></i>
                Kirim Notifikasi
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    @if (! $adaTabel)
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <i class="bi bi-exclamation-triangle-fill mt-0.5 text-base text-amber-500"></i>
            <div class="text-xs leading-relaxed text-amber-800">
                <p class="font-bold">Tabel surat panggilan belum dibuat</p>
                <p class="mt-1">
                    Jalankan <code class="rounded bg-amber-100 px-1">php artisan migrate</code> untuk membuat tabel
                    <code class="rounded bg-amber-100 px-1">absensi_surat_panggilan</code>. Sampai itu selesai, surat tidak bisa terbit.
                </p>
            </div>
        </div>
    @endif

    {{-- Filter periode: tetap form GET agar URL bisa dibagikan & controller memakai nilai server. --}}
    <x-ui.card title="Periode Surat" icon="bi-calendar3" tone="slate" body-class="p-4">
        <form action="" method="get" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="fBulan" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Bulan</label>
                <select id="fBulan" name="bulan"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    @foreach ($blnIdx as $k => $v)
                        <option value="{{ $k }}" @selected($k === (int) $bulan)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="fTahun" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Tahun</label>
                <select id="fTahun" name="tahun"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    @for ($y = (int) date('Y'); $y >= attendance_tahun_tertua(); $y--)
                        <option value="{{ $y }}" @selected($y === (int) $tahun)>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <button type="submit"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                <i class="bi bi-search"></i> Tampilkan
            </button>
            <p class="ml-auto text-[11px] text-slate-400">
                <i class="bi bi-info-circle"></i>
                Filter baru dipakai setelah tombol Tampilkan ditekan.
            </p>
        </form>
    </x-ui.card>

    {{-- KPI: kartu aktif/dibatalkan bertindak sebagai filter tabel. --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($kpi as $item)
            <div role="button" tabindex="0" x-on:click="setStatus('{{ $item['status'] }}')"
                x-on:keydown.enter.prevent="setStatus('{{ $item['status'] }}')"
                x-bind:class="status === '{{ $item['status'] }}' && '{{ $item['status'] }}' !== '' ? 'ring-2 ring-blue-500/60 ring-offset-1' : ''"
                class="cursor-pointer text-left transition-shadow hover:shadow-md"
                title="{{ $item['status'] !== '' ? 'Tampilkan surat '.strtolower($item['label']) : 'Tampilkan semua surat' }}">
                <x-ui.card body-class="p-4" class="h-full border-l-4 {{ $item['aksen'] }}">
                    <div class="flex items-center gap-3">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-xl {{ $item['ikonKelas'] }}">
                            <i class="bi {{ $item['ikon'] }}"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $item['label'] }}</p>
                            <div class="mt-0.5 flex items-baseline gap-2">
                                <p class="text-2xl font-extrabold tabular-nums text-slate-900">
                                    @if ($item['ekspresi'])
                                        <span x-text="{{ $item['ekspresi'] }}"></span>
                                    @else
                                        {{ $item['label'] === 'Belum Ada Surat' ? count($kandidat) : implode('/', \App\Services\SuratPanggilanService::AMBANG) }}
                                    @endif
                                </p>
                                <span class="text-[10px] font-semibold text-slate-400">{{ $item['satuan'] }}</span>
                            </div>
                            <p class="mt-0.5 text-[11px] text-slate-400">{{ $item['ket'] }}</p>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        @endforeach
    </div>

    <x-ui.card body-class="p-0" title="Surat Panggilan {{ ($blnIdx[(int) $bulan] ?? '-').' '.(int) $tahun }}"
        icon="bi-envelope-paper" subtitle="Klik judul kolom untuk mengurutkan data.">
        <x-slot:meta>
            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold tabular-nums text-slate-600">
                <i class="bi bi-collection"></i> <span x-text="semuaBaris.length"></span> surat
            </span>
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold tabular-nums text-amber-700">
                <i class="bi bi-exclamation-triangle"></i> Ambang {{ implode('/', \App\Services\SuratPanggilanService::AMBANG) }} hari alpha
            </span>
        </x-slot:meta>

        <x-slot:toolbar>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="Filter status surat">
                    <button type="button" x-on:click="setStatus('')"
                        x-bind:class="status === '' ? 'border-slate-800 bg-slate-800 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1.5 text-[11px] font-bold transition-colors">
                        <span class="h-2 w-2 rounded-full bg-slate-400"></span> Semua
                    </button>
                    <button type="button" x-on:click="setStatus('aktif')"
                        x-bind:class="status === 'aktif' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1.5 text-[11px] font-bold transition-colors">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Aktif
                        <span class="tabular-nums" x-text="jumlahAktif"></span>
                    </button>
                    <button type="button" x-on:click="setStatus('dibatalkan')"
                        x-bind:class="status === 'dibatalkan' ? 'border-slate-600 bg-slate-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1.5 text-[11px] font-bold transition-colors">
                        <span class="h-2 w-2 rounded-full bg-slate-400"></span> Dibatalkan
                        <span class="tabular-nums" x-text="jumlahDibatalkan"></span>
                    </button>
                </div>

                <div class="relative w-full sm:ml-auto sm:w-64">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                    <input type="text" x-model="cari" x-ref="cari" autocomplete="off" x-on:input="halaman = 1"
                        placeholder="Cari nama / NIS / nomor surat..."
                        aria-label="Cari surat panggilan"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-8 text-xs font-semibold text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    <button type="button" x-show="cari !== ''" x-cloak x-on:click="bersihkan()"
                        class="absolute right-2 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-200/70 hover:text-slate-700"
                        title="Bersihkan pencarian">
                        <i class="bi bi-x text-xs"></i>
                    </button>
                </div>
            </div>
        </x-slot:toolbar>

        <div class="max-h-[68vh] overflow-auto" tabindex="0">
            <table class="w-full min-w-[1180px] text-[13px]">
                <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="w-10 px-3 py-3 text-center font-bold">#</th>
                        <th class="min-w-[190px] px-3 py-3 text-left font-bold">
                            <button type="button" x-on:click="urutkan('nomor')"
                                class="inline-flex items-center gap-1 font-bold uppercase tracking-wider transition-colors hover:text-slate-800">
                                No. Surat <i class="bi text-[10px]" x-bind:class="ikonUrut('nomor')"></i>
                            </button>
                        </th>
                        <th class="min-w-[200px] px-3 py-3 text-left font-bold">
                            <button type="button" x-on:click="urutkan('nama')"
                                class="inline-flex items-center gap-1 font-bold uppercase tracking-wider transition-colors hover:text-slate-800">
                                Nama Siswa <i class="bi text-[10px]" x-bind:class="ikonUrut('nama')"></i>
                            </button>
                        </th>
                        <th class="min-w-[120px] px-3 py-3 text-left font-bold">NIS / NISN</th>
                        <th class="min-w-[160px] px-3 py-3 text-left font-bold">Penerima</th>
                        <th class="w-[100px] px-3 py-3 text-center font-bold">
                            <button type="button" x-on:click="urutkan('tingkat')"
                                class="inline-flex items-center gap-1 font-bold uppercase tracking-wider transition-colors hover:text-slate-800">
                                Tingkat <i class="bi text-[10px]" x-bind:class="ikonUrut('tingkat')"></i>
                            </button>
                        </th>
                        <th class="w-[90px] px-3 py-3 text-center font-bold">
                            <button type="button" x-on:click="urutkan('alpha')"
                                class="inline-flex items-center gap-1 font-bold uppercase tracking-wider text-rose-600 transition-colors hover:text-rose-700">
                                Alpha <i class="bi text-[10px]" x-bind:class="ikonUrut('alpha')"></i>
                            </button>
                        </th>
                        <th class="w-[115px] px-3 py-3 text-center font-bold" title="Alpha live dari data absen bulan ini">
                            <button type="button" x-on:click="urutkan('alphaBulanIni')"
                                class="inline-flex items-center gap-1 font-bold uppercase tracking-wider transition-colors hover:text-slate-800">
                                Alpha Bulan Ini <i class="bi text-[10px]" x-bind:class="ikonUrut('alphaBulanIni')"></i>
                            </button>
                        </th>
                        <th class="w-[95px] px-3 py-3 text-center font-bold">Teguran</th>
                        <th class="w-[90px] px-3 py-3 text-center font-bold">
                            <button type="button" x-on:click="urutkan('terlambat')"
                                class="inline-flex items-center gap-1 font-bold uppercase tracking-wider text-amber-600 transition-colors hover:text-amber-700">
                                Terlambat <i class="bi text-[10px]" x-bind:class="ikonUrut('terlambat')"></i>
                            </button>
                        </th>
                        <th class="min-w-[130px] px-3 py-3 text-left font-bold">
                            <button type="button" x-on:click="urutkan('terbitSort')"
                                class="inline-flex items-center gap-1 font-bold uppercase tracking-wider transition-colors hover:text-slate-800">
                                Terbit <i class="bi text-[10px]" x-bind:class="ikonUrut('terbitSort')"></i>
                            </button>
                        </th>
                        <th class="w-[110px] px-3 py-3 text-center font-bold">
                            <button type="button" x-on:click="urutkan('statusLabel')"
                                class="inline-flex items-center gap-1 font-bold uppercase tracking-wider transition-colors hover:text-slate-800">
                                Status <i class="bi text-[10px]" x-bind:class="ikonUrut('statusLabel')"></i>
                            </button>
                        </th>
                        <th class="w-[110px] px-3 py-3 text-center font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr x-show="barisTampil.length === 0">
                        <td colspan="13" class="px-3 py-12 text-center">
                            <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl text-slate-400">
                                <i class="bi" :class="semuaBaris.length === 0 ? 'bi-envelope-open' : 'bi-search'"></i>
                            </span>
                            <p class="text-sm font-bold text-slate-700"
                                x-text="semuaBaris.length === 0 ? 'Belum ada surat panggilan untuk periode ini' : 'Tidak ada surat yang cocok'"></p>
                            <p class="mt-1 text-xs text-slate-400"
                                x-text="semuaBaris.length === 0
                                    ? 'Surat terbit otomatis pada tanggal 7, 14, 21, dan akhir bulan, atau buat manual dari daftar kandidat di bawah.'
                                    : 'Ubah kata kunci pencarian atau pilih status lain.'"></p>
                            <button type="button" x-show="semuaBaris.length > 0" x-on:click="bersihkan()"
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
                                <span class="inline-flex rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 font-mono text-[10px] font-semibold text-slate-600"
                                    x-text="row.nomor"></span>
                            </td>

                            <td class="px-3 py-2.5">
                                <p class="truncate text-sm font-semibold text-slate-800" :title="row.nama" x-text="row.nama"></p>
                                <p x-show="row.kelas" class="mt-0.5 text-[10px] text-slate-400" x-text="row.kelas"></p>
                            </td>

                            <td class="px-3 py-2.5 text-xs">
                                <span class="font-mono tabular-nums text-slate-700" x-text="row.nis || '—'"></span>
                                <p x-show="row.nisn" class="mt-0.5 font-mono text-[10px] text-slate-400" x-text="row.nisn"></p>
                            </td>

                            <td class="px-3 py-2.5 text-xs">
                                <span class="text-slate-700" x-text="row.penerima || '—'"></span>
                                <p x-show="row.nohp" class="mt-0.5 font-mono text-[10px] text-slate-400" x-text="row.nohp"></p>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                                    x-bind:class="row.tingkatKelas" x-text="row.tingkatLabel"></span>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <span class="text-sm font-bold tabular-nums text-rose-600" x-text="row.alpha"></span>
                                <p x-show="row.drift" class="text-[10px] font-normal text-amber-600"
                                    title="Angka dari data absen saat ini — klik Hitung Ulang untuk menyelaraskan"
                                    x-text="'real: ' + row.alphaLive"></p>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <span x-show="row.alphaBulanIni > 0"
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-bold"
                                    x-bind:class="row.teguran ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600'"
                                    :title="row.teguran ? 'Wali kelas sudah terima surat teguran bulan ini' : ''"
                                    x-text="'α ' + row.alphaBulanIni"></span>
                                <span x-show="row.alphaBulanIni <= 0" class="text-xs text-slate-300">α 0</span>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <span x-show="row.teguran"
                                    class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700"
                                    title="Wali kelas sudah terima surat teguran bulan ini">
                                    <i class="bi bi-shield-exclamation"></i> Teguran
                                </span>
                                <span x-show="!row.teguran" class="text-xs text-slate-300">—</span>
                            </td>

                            <td class="px-3 py-2.5 text-center text-xs font-bold tabular-nums text-amber-600" x-text="row.terlambat"></td>

                            <td class="whitespace-nowrap px-3 py-2.5 text-xs">
                                <span class="tabular-nums text-slate-700" x-text="row.terbit"></span>
                                <p class="mt-0.5 text-[10px] text-slate-400" x-text="row.sumber"></p>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                                    x-bind:class="row.status.kelas"
                                    :title="row.status.kode === 'dibatalkan' ? (row.keteranganBatal || 'Dibatalkan') : ''">
                                    <i class="bi text-[10px]" x-bind:class="row.status.ikon"></i>
                                    <span x-text="row.status.label"></span>
                                </span>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <div class="inline-flex items-center gap-1.5" x-show="row.status.kode === 'aktif'">
                                    <a :href="'{{ url('admin/presensi/surat-panggilan') }}/' + row.id + '/pdf'" target="_blank" rel="noopener"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-white no-underline transition-colors hover:bg-emerald-700"
                                        title="Cetak PDF surat panggilan">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                    <button type="button" x-on:click="bukaBatal(row)" :disabled="memproses"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-rose-600 transition-colors hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        title="Batalkan surat ini">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </div>
                                <span x-show="row.status.kode !== 'aktif'" class="text-xs text-slate-300">—</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <div class="flex flex-wrap items-center justify-between gap-3 text-[11px] text-slate-400">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="tabular-nums"
                        x-text="barisTampil.length === 0
                            ? 'Tidak ada baris ditampilkan'
                            : 'Menampilkan ' + nomorAwal + '–' + nomorAkhir + ' dari ' + barisTampil.length + ' surat'"></span>
                    <span><i class="bi bi-camera"></i> Angka di dalam surat adalah foto saat surat terbit.</span>
                </div>

                <div class="flex items-center gap-1.5">
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
            </div>
        </x-slot:footer>
    </x-ui.card>

    {{-- Kandidat: siswa yang perlu surat tapi belum punya --}}
    <x-ui.card body-class="p-0" icon="bi-person-exclamation" tone="amber"
        title="Siswa yang Belum Dapat Surat"
        subtitle="Alpha bulan {{ $blnIdx[(int) $bulan] ?? '-' }} {{ (int) $tahun }} sudah melewati ambang {{ \App\Services\SuratPanggilanService::AMBANG[0] }} hari, tapi belum ada surat terbit.">
        <x-slot:meta>
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold tabular-nums text-amber-700">
                <i class="bi bi-people"></i> {{ count($kandidat) }} siswa
            </span>
        </x-slot:meta>

        @if (empty($kandidat))
            <div class="p-5">
                <x-ui.empty icon="bi-check2-circle" title="Semua kandidat sudah punya surat"
                    description="Tidak ada siswa yang melewati ambang alpha tanpa surat pada periode ini." />
            </div>
        @else
            <div class="max-h-[52vh] overflow-auto" tabindex="0">
                <table class="w-full min-w-[820px] text-[13px]">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="w-10 px-3 py-3 text-center font-bold">#</th>
                            <th class="min-w-[220px] px-3 py-3 text-left font-bold">Nama Siswa</th>
                            <th class="min-w-[130px] px-3 py-3 text-left font-bold">NIS</th>
                            <th class="w-[100px] px-3 py-3 text-center font-bold">Alpha</th>
                            <th class="w-[110px] px-3 py-3 text-center font-bold">Terlambat</th>
                            <th class="w-[120px] px-3 py-3 text-center font-bold">Tercapai</th>
                            <th class="min-w-[260px] px-3 py-3 text-center font-bold">Buat Surat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(row, index) in semuaKandidat" :key="row.idSiswa">
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400" x-text="index + 1"></td>
                                <td class="px-3 py-2.5 text-sm font-semibold text-slate-800" x-text="row.nama"></td>
                                <td class="px-3 py-2.5 font-mono text-xs text-slate-600" x-text="row.nis || '—'"></td>
                                <td class="px-3 py-2.5 text-center text-sm font-bold tabular-nums text-rose-600" x-text="row.alpha"></td>
                                <td class="px-3 py-2.5 text-center text-xs font-bold tabular-nums text-amber-600" x-text="row.terlambat"></td>
                                <td class="px-3 py-2.5 text-center">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                                        x-bind:class="row.tingkatKelas" x-text="row.tingkatLabel"></span>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <select x-model.number="row.level" aria-label="Tingkat surat"
                                            class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                            <template x-for="lv in ambang" :key="lv">
                                                <option :value="lv" x-text="labelLevel[lv]"></option>
                                            </template>
                                        </select>
                                        <button type="button" x-on:click="bukaBuat(row)" :disabled="memproses"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                            title="Buat surat panggilan untuk siswa ini">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            <p class="text-[11px] text-slate-400">
                <i class="bi bi-info-circle"></i>
                Tingkat yang dipilih di tabel dipakai saat membuat surat. Surat yang sudah terbit tidak bisa dibuat ulang untuk periode yang sama.
            </p>
        </x-slot:footer>
    </x-ui.card>

    {{-- Penjelasan aturan --}}
    <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <i class="bi bi-info-circle-fill mt-0.5 text-base text-slate-400"></i>
        <div class="space-y-1 text-xs leading-relaxed text-slate-600">
            <p class="font-bold text-slate-700">Cara kerja surat panggilan</p>
            <p>
                <b>Sumber angka</b> — dihitung dari tabel presensi siswa, hanya hari efektif (bukan hari libur minggu,
                nasional, atau jadwal kerja libur). Hari yang belum datang tidak dihitung, jadi nomor ini tidak akan
                melompat di awal bulan.
            </p>
            <p>
                <b>Alpha</b> — hari efektif tanpa baris absensi sama sekali, atau berstatus ALPA / TANPA KETERANGAN.
                Izin dan sakit tidak dihitung.
            </p>
            <p>
                <b>Terbit otomatis</b> — tanggal 7, 14, 21, dan akhir bulan. Satu siswa mendapat satu surat per evaluasi,
                yaitu tingkat terberat yang sudah dilampaui.
            </p>
            <p>
                <b>Angka di dalam surat adalah foto saat surat terbit</b>, bukan dihitung ulang. Dicetak ulang tahun depan
                pun angkanya sama seperti waktu orang tua membacanya — kecuali admin menekan <b>Hitung Ulang</b>, yang
                menyelaraskan angka surat aktif dengan data absen terkini (surat yang syaratnya sudah gugur ikut dibatalkan otomatis).
            </p>
            <p>
                <b>Pembatalan</b> — surat dibatalkan, bukan dihapus, supaya arsip keberadaannya tetap bisa dibuktikan.
            </p>
        </div>
    </div>
</div>

{{-- ══════════ MODAL: BUAT SURAT PANGGILAN MANUAL ══════════ --}}
<x-ui.modal id="buatSuratModal" title="Buat Surat Panggilan" icon="bi-envelope-plus" tone="amber" size="max-w-lg">
    <form x-on:submit.prevent="kirimBuat()">
        <div class="space-y-4 p-5">
            <div class="flex gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3.5 py-2.5 text-xs text-blue-800">
                <i class="bi bi-info-circle-fill mt-0.5 shrink-0"></i>
                <p class="leading-relaxed">
                    Surat akan diterbitkan untuk <strong x-text="buat.nama || '—'"></strong>
                    pada periode <strong>{{ ($blnIdx[(int) $bulan] ?? '-').' '.(int) $tahun }}</strong>.
                </p>
            </div>

            <div>
                <label for="buatLevel" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Tingkat Surat</label>
                <select id="buatLevel" x-model.number="buat.level"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20">
                    <template x-for="lv in ambang" :key="lv">
                        <option :value="lv" x-text="labelLevel[lv]"></option>
                    </template>
                </select>
                <p class="mt-1 text-[11px] text-slate-400">Tingkat terberat yang sudah dilampaui dipilih otomatis dari tabel.</p>
            </div>

            <div>
                <label for="buatKeterangan" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                    Alasan <span class="font-normal normal-case text-slate-400">(opsional, tersimpan di data)</span>
                </label>
                <textarea id="buatKeterangan" x-model="buat.keterangan" rows="3" maxlength="255"
                    placeholder="mis. Sudah ditindaklanjuti via telepon"
                    class="w-full resize-none rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"></textarea>
                <p class="mt-1 text-right text-[11px] text-slate-400"><span x-text="buat.keterangan.length"></span>/255 karakter</p>
            </div>
        </div>

        <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
            <button type="button" data-ui-dismiss="modal"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100">
                Batal
            </button>
            <button type="submit" :disabled="memproses"
                class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50">
                <i class="bi" :class="memproses && proses === 'buat' ? 'bi-arrow-repeat animate-spin' : 'bi-plus-lg'"></i>
                Ya, Buat Surat
            </button>
        </div>
    </form>
</x-ui.modal>

{{-- ══════════ MODAL: BATALKAN SURAT ══════════ --}}
<x-ui.modal id="batalSuratModal" title="Batalkan Surat Panggilan" icon="bi-x-circle" tone="rose" size="max-w-lg">
    <form x-on:submit.prevent="batalkanSurat()">
        <div class="space-y-4 p-5">
            <div class="flex gap-2 rounded-xl border border-amber-100 bg-amber-50 px-3.5 py-2.5 text-xs text-amber-800">
                <i class="bi bi-exclamation-triangle-fill mt-0.5 shrink-0"></i>
                <p class="leading-relaxed">
                    Surat <strong x-text="batal.nomor || '—'"></strong> milik <strong x-text="batal.nama || '—'"></strong>
                    akan berstatus dibatalkan. Surat tidak dihapus, arsipnya tetap tersimpan.
                </p>
            </div>

            <div>
                <label for="batalAlasan" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                    Alasan Pembatalan <span class="font-normal normal-case text-slate-400">(opsional, maks 255 karakter)</span>
                </label>
                <textarea id="batalAlasan" x-model="batal.alasan" x-ref="alasan" rows="3" maxlength="255"
                    placeholder="mis. Sudah ada izin resmi"
                    class="w-full resize-none rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"></textarea>
                <p class="mt-1 text-right text-[11px] text-slate-400"><span x-text="batal.alasan.length"></span>/255 karakter</p>
            </div>
        </div>

        <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
            <button type="button" data-ui-dismiss="modal"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100">
                Kembali
            </button>
            <button type="submit" :disabled="memproses"
                class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50">
                <i class="bi" :class="memproses && proses === 'batal' ? 'bi-arrow-repeat animate-spin' : 'bi-x-circle'"></i>
                Ya, Batalkan
            </button>
        </div>
    </form>
</x-ui.modal>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('suratPanggilan', (config) => ({
        semuaBaris: config.baris || [],
        semuaKandidat: config.kandidat || [],
        ambang: config.ambang || [3, 5, 7],
        labelLevel: config.labelLevel || {},
        csrf: config.csrf,
        urls: config.urls,
        bulan: config.bulan,
        tahun: config.tahun,
        periode: config.periode || '',
        cari: '',
        status: '',
        urut: '',
        arah: 'asc',
        halaman: 1,
        perHalaman: 25,
        memproses: false,
        proses: '',
        buat: { idSiswa: null, nama: '', level: 3, keterangan: '' },
        batal: { id: null, nomor: '', nama: '', alasan: '' },

        cocok(row) {
            const kunci = this.cari.trim().toLowerCase();

            return kunci === '' || (row.cari || '').includes(kunci);
        },

        get jumlahAktif() {
            return this.semuaBaris.filter((row) => row.status.kode === 'aktif').length;
        },

        get jumlahDibatalkan() {
            return this.semuaBaris.filter((row) => row.status.kode === 'dibatalkan').length;
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

        setStatus(kode) {
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

        bukaBuat(row) {
            this.buat = {
                idSiswa: row.idSiswa,
                nama: row.nama,
                level: Number(row.level) || this.ambang[0],
                keterangan: '',
            };
            this.$store.modals.open('#buatSuratModal');
        },

        bukaBatal(row) {
            this.batal = { id: row.id, nomor: row.nomor, nama: row.nama, alasan: '' };
            this.$store.modals.open('#batalSuratModal');
            this.$nextTick(() => this.$refs.alasan?.focus());
        },

        async kirim(url, isi) {
            try {
                const respon = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(isi || {}),
                });
                const data = await respon.json().catch(() => ({}));

                return {
                    ok: respon.ok && data.status !== false,
                    pesan: data.message || 'Terjadi kesalahan pada server',
                };
            } catch {
                return { ok: false, pesan: 'Gagal menghubungi server' };
            }
        },

        konfirmasi(judul, html, label, warna, ikon = 'question') {
            return Swal.fire({
                title: judul,
                html: html,
                icon: ikon,
                showCancelButton: true,
                confirmButtonColor: warna,
                cancelButtonColor: '#94a3b8',
                confirmButtonText: label,
                cancelButtonText: 'Batal',
            }).then((hasil) => hasil.isConfirmed);
        },

        /** Satu penjaga "sedang memproses" untuk semua aksi. */
        async jalankanAksi(nama, aksi) {
            if (this.memproses) {
                return;
            }

            this.memproses = true;
            this.proses = nama;

            try {
                await aksi();
            } finally {
                this.memproses = false;
                this.proses = '';
            }
        },

        async selesai(judul, pesan, muatUlang = false) {
            await Swal.fire({ icon: 'success', title: judul, text: pesan, timer: 1800, showConfirmButton: false });

            if (muatUlang) {
                location.reload();
            }
        },

        async kirimBuat() {
            const isi = {
                id_siswa: this.buat.idSiswa,
                bulan: this.bulan,
                tahun: this.tahun,
                level: this.buat.level,
                keterangan: this.buat.keterangan.trim(),
            };

            await this.jalankanAksi('buat', async () => {
                const hasil = await this.kirim(this.urls.buat, isi);

                if (!hasil.ok) {
                    Swal.fire('Gagal Membuat Surat', hasil.pesan, 'error');

                    return;
                }

                this.$store.modals.close('#buatSuratModal');
                await this.selesai('Surat Dibuat', hasil.pesan, true);
            });
        },

        async batalkanSurat() {
            const id = this.batal.id;

            await this.jalankanAksi('batal', async () => {
                const hasil = await this.kirim(this.urls.batal + '/' + id, { alasan: this.batal.alasan.trim() });

                if (!hasil.ok) {
                    Swal.fire('Gagal Membatalkan', hasil.pesan, 'error');

                    return;
                }

                this.$store.modals.close('#batalSuratModal');
                await this.selesai('Surat Dibatalkan', hasil.pesan, true);
            });
        },

        async hitungUlang() {
            const lanjut = await this.konfirmasi(
                'Hitung Ulang Surat?',
                'Angka <strong>alpha & terlambat</strong> surat aktif periode ini akan diselaraskan dengan data absen saat ini. '
                    + 'Surat yang syaratnya sudah gugur akan <strong>dibatalkan otomatis</strong>. Level dan penerima tidak diubah.',
                'Ya, Hitung Ulang',
                '#d97706',
            );

            if (!lanjut) {
                return;
            }

            await this.jalankanAksi('hitung', async () => {
                const hasil = await this.kirim(this.urls.hitungUlang, { bulan: this.bulan, tahun: this.tahun });

                if (!hasil.ok) {
                    Swal.fire('Gagal Menghitung Ulang', hasil.pesan, 'error');

                    return;
                }

                await this.selesai('Hitung Ulang Selesai', hasil.pesan, true);
            });
        },

        async kirimNotifikasi() {
            const lanjut = await this.konfirmasi(
                'Kirim Notifikasi Wali Kelas?',
                'Notifikasi akan dikirim ke wali kelas untuk semua surat panggilan yang sudah terbit pada bulan '
                    + '<strong>' + this.periode + '</strong>.',
                'Ya, Kirim',
                '#2563eb',
            );

            if (!lanjut) {
                return;
            }

            await this.jalankanAksi('notifikasi', async () => {
                const hasil = await this.kirim(this.urls.notifikasi, { bulan: this.bulan, tahun: this.tahun });

                if (!hasil.ok) {
                    Swal.fire('Gagal Mengirim', hasil.pesan, 'error');

                    return;
                }

                await this.selesai('Notifikasi Terkirim', hasil.pesan, true);
            });
        },
    }));
});
</script>
@endsection
