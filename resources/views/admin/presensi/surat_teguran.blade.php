@extends('layouts.main')

@section('content')
@php
    $blnIdx = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    $ambang = \App\Libraries\SuratTeguranLevel::ambang();
    $ambangAwal = $ambang[0];

    // Kelas Tailwind ditulis utuh (bukan dirakit) agar ikut ter-scan saat build.
    $kelasTingkat = static fn (int $level): string => match ($level) {
        10 => 'bg-rose-50 text-rose-700',
        7 => 'bg-amber-50 text-amber-700',
        default => 'bg-blue-50 text-blue-700',
    };

    $barisSurat = [];
    foreach ($letters as $row) {
        $batal = ($row->status ?? 'aktif') === 'dibatalkan';
        $level = (int) ($row->level ?? 0);
        $namaGuru = trim((string) ($row->nama_guru ?? '')) ?: '-';

        $nomor = \App\Libraries\SuratNomor::build(
            (int) ($row->nomor_urut ?? 0),
            (int) ($row->bulan ?? 0),
            (int) ($row->tahun ?? 0),
            $setting,
            $level,
            !empty($row->created_at) ? date('d-m-Y', strtotime($row->created_at)) : null
        );

        $barisSurat[] = [
            'id' => (int) $row->id,
            'nomor' => $nomor,
            'nama' => $namaGuru,
            'nip' => trim((string) ($row->nip ?? '')),
            'tingkat' => $batal ? 'Dibatalkan' : \App\Libraries\SuratTeguranLevel::label($level),
            'tingkatKelas' => $batal ? 'bg-slate-100 text-slate-500' : $kelasTingkat($level),
            'alpha' => (int) ($row->jumlah_alpha ?? 0),
            'terbit' => !empty($row->created_at) ? date('d-m-Y H:i', strtotime($row->created_at)) : '-',
            'batal' => $batal,
            'keteranganBatal' => trim((string) ($row->keterangan_batal ?? '')),
            'dibaca' => !empty($row->ack_at),
            'dibacaText' => trim((string) ($row->ack_text ?? '')),
        ];
    }

    $barisKandidat = [];
    foreach ($kandidat as $k) {
        $level = (int) ($k['level'] ?? 0);

        $barisKandidat[] = [
            'idGuru' => (int) ($k['id_guru'] ?? 0),
            'nama' => trim((string) ($k['nama_guru'] ?? '')) ?: '-',
            'nip' => trim((string) ($k['nip'] ?? '')),
            'alpha' => (int) ($k['alpha'] ?? 0),
            'tingkat' => \App\Libraries\SuratTeguranLevel::label($level),
            'tingkatKelas' => $kelasTingkat($level),
        ];
    }

    $jumlahAktif = count(array_filter($barisSurat, static fn ($row) => !$row['batal']));
    $jumlahBatal = count($barisSurat) - $jumlahAktif;
    $jumlahDibaca = count(array_filter($barisSurat, static fn ($row) => !$row['batal'] && $row['dibaca']));

    $kpi = [
        [
            'label' => 'Surat Aktif', 'nilai' => $jumlahAktif, 'satuan' => 'surat',
            'ikon' => 'bi-envelope-check', 'ket' => 'Berlaku pada periode ini',
            'aksen' => 'border-l-blue-500', 'ikonKelas' => 'bg-blue-50 text-blue-600',
        ],
        [
            'label' => 'Dibatalkan', 'nilai' => $jumlahBatal, 'satuan' => 'surat',
            'ikon' => 'bi-envelope-slash', 'ket' => 'Arsip, tidak berlaku',
            'aksen' => 'border-l-slate-400', 'ikonKelas' => 'bg-slate-100 text-slate-500',
        ],
        [
            'label' => 'Sudah Dibaca Guru', 'nilai' => $jumlahDibaca, 'satuan' => 'surat',
            'ikon' => 'bi-check2-circle', 'ket' => 'Dikonfirmasi oleh guru',
            'aksen' => 'border-l-emerald-500', 'ikonKelas' => 'bg-emerald-50 text-emerald-600',
        ],
        [
            'label' => 'Ambang', 'nilai' => implode('/', $ambang), 'satuan' => 'hari alpha',
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
        'csrf' => csrf_token(),
        'urls' => [
            'buat' => url('admin/presensi/surat-teguran/buat'),
            'batal' => url('admin/presensi/surat-teguran/batal'),
            'kirim' => url('admin/presensi/surat-teguran/kirim'),
        ],
    ];
@endphp

<div class="space-y-4" x-data="suratTeguran(@js($config))">

    <x-ui.page-header title="{{ $judul ?? 'Surat Teguran Guru' }}" icon="bi-envelope-exclamation" tone="rose"
        subtitle="Surat resmi untuk guru yang kehadirannya sudah melewati ambang. Terbit otomatis setiap tanggal 7, 14, 21, dan akhir bulan."
        :crumbs="['Presensi', 'Surat Teguran']">
        <x-slot:actions>
            <a href="{{ url('admin/presensi') }}" title="Kembali ke daftar presensi"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-arrow-left"></i> Presensi
            </a>
            <a href="{{ url('admin/presensi/surat-panggilan') }}" title="Surat panggilan untuk orang tua/wali siswa"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-envelope-open"></i> Surat Panggilan Orang Tua
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    @if (! $adaTabel)
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <i class="bi bi-exclamation-triangle-fill mt-0.5 text-base text-amber-500"></i>
            <div class="text-xs leading-relaxed text-amber-800">
                <p class="font-bold">Tabel surat teguran belum dibuat</p>
                <p class="mt-1">
                    Jalankan <code class="rounded bg-amber-100 px-1">php artisan migrate</code> untuk membuat tabel
                    <code class="rounded bg-amber-100 px-1">absensi_surat_teguran</code>. Sampai itu selesai, surat tidak bisa terbit.
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

    {{-- Ringkasan periode --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($kpi as $item)
            <x-ui.card body-class="p-4" class="h-full border-l-4 {{ $item['aksen'] }}">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-xl {{ $item['ikonKelas'] }}">
                        <i class="bi {{ $item['ikon'] }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $item['label'] }}</p>
                        <div class="mt-0.5 flex items-baseline gap-2">
                            <p class="truncate text-2xl font-extrabold tabular-nums text-slate-900">{{ $item['nilai'] }}</p>
                            <span class="text-[10px] font-semibold text-slate-400">{{ $item['satuan'] }}</span>
                        </div>
                        <p class="mt-0.5 text-[11px] text-slate-400">{{ $item['ket'] }}</p>
                    </div>
                </div>
            </x-ui.card>
        @endforeach
    </div>

    <x-ui.card body-class="p-0" title="Surat Teguran {{ ($blnIdx[(int) $bulan] ?? '-').' '.(int) $tahun }}"
        icon="bi-envelope-paper" subtitle="Surat dibatalkan tidak dihapus — arsip keberadaannya tetap tersimpan.">
        <x-slot:meta>
            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold tabular-nums text-slate-600">
                <i class="bi bi-collection"></i> {{ count($barisSurat) }} surat
            </span>
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold tabular-nums text-amber-700">
                <i class="bi bi-exclamation-triangle"></i> Ambang {{ implode('/', $ambang) }} hari alpha
            </span>
        </x-slot:meta>

        @if (empty($barisSurat))
            <div class="p-5">
                <x-ui.empty icon="bi-envelope-open" title="Belum ada surat teguran untuk periode ini"
                    description="Surat terbit otomatis pada tanggal 7, 14, 21, dan akhir bulan, atau buat manual dari daftar kandidat di bawah." />
            </div>
        @else
            <div class="max-h-[68vh] overflow-auto" tabindex="0">
                <table class="w-full min-w-[1080px] text-[13px]">
                    <caption class="sr-only">Daftar surat teguran guru periode {{ ($blnIdx[(int) $bulan] ?? '-').' '.(int) $tahun }}</caption>
                    <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="w-10 px-3 py-3 text-center font-bold">#</th>
                            <th class="min-w-[210px] px-3 py-3 text-left font-bold">No. Surat</th>
                            <th class="min-w-[200px] px-3 py-3 text-left font-bold">Nama Guru</th>
                            <th class="min-w-[130px] px-3 py-3 text-left font-bold">NIP</th>
                            <th class="min-w-[190px] px-3 py-3 text-center font-bold">Tingkat</th>
                            <th class="w-[90px] px-3 py-3 text-center font-bold text-rose-600">Alpha</th>
                            <th class="min-w-[130px] px-3 py-3 text-left font-bold">Terbit</th>
                            <th class="w-[110px] px-3 py-3 text-center font-bold">Status</th>
                            <th class="w-[100px] px-3 py-3 text-center font-bold">Dibaca</th>
                            <th class="w-[130px] px-3 py-3 text-center font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($barisSurat as $row)
                            <tr @class(['transition-colors hover:bg-slate-50/60', 'bg-slate-50/40 opacity-70' => $row['batal']])>
                                <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400">{{ $loop->iteration }}</td>

                                <td class="px-3 py-2.5">
                                    <span class="inline-flex rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 font-mono text-[10px] font-semibold text-slate-600">
                                        {{ $row['nomor'] }}
                                    </span>
                                </td>

                                <td class="px-3 py-2.5">
                                    <p class="truncate text-sm font-semibold text-slate-800" title="{{ $row['nama'] }}">{{ $row['nama'] }}</p>
                                </td>

                                <td class="px-3 py-2.5 font-mono text-xs tabular-nums text-slate-600">{{ $row['nip'] !== '' ? $row['nip'] : '—' }}</td>

                                <td class="px-3 py-2.5 text-center">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $row['tingkatKelas'] }}"
                                        @if ($row['batal']) title="{{ $row['keteranganBatal'] !== '' ? $row['keteranganBatal'] : 'Dibatalkan' }}" @endif>
                                        @if ($row['batal'])
                                            <i class="bi bi-x-circle mr-1"></i>
                                        @endif
                                        {{ $row['tingkat'] }}
                                    </span>
                                </td>

                                <td class="px-3 py-2.5 text-center text-sm font-bold tabular-nums text-rose-600">{{ $row['alpha'] }}</td>

                                <td class="whitespace-nowrap px-3 py-2.5 text-xs tabular-nums text-slate-700">{{ $row['terbit'] }}</td>

                                <td class="px-3 py-2.5 text-center">
                                    @if ($row['batal'])
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-500">
                                            <i class="bi bi-x-circle"></i> Dibatalkan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">
                                            <i class="bi bi-check2-circle"></i> Aktif
                                        </span>
                                    @endif
                                </td>

                                <td class="px-3 py-2.5 text-center">
                                    @if ($row['dibaca'])
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700"
                                            title="Dikonfirmasi: {{ $row['dibacaText'] }}">
                                            <i class="bi bi-check2-circle"></i> Sudah
                                        </span>
                                    @elseif ($row['batal'])
                                        <span class="text-xs text-slate-300">—</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-700">
                                            <i class="bi bi-hourglass-split"></i> Belum
                                        </span>
                                    @endif
                                </td>

                                <td class="px-3 py-2.5 text-center">
                                    @if ($row['batal'])
                                        <span class="text-xs text-slate-300">—</span>
                                    @else
                                        <div class="inline-flex items-center gap-1.5">
                                            <a href="{{ url('admin/presensi/download-teguran/'.$row['id']) }}" target="_blank" rel="noopener"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-white no-underline transition-colors hover:bg-emerald-700"
                                                title="Cetak PDF surat teguran">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                            </a>
                                            <button type="button" x-on:click="bukaKirim({{ $row['id'] }})" :disabled="memproses"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-600 text-white transition-colors hover:bg-cyan-700 disabled:cursor-not-allowed disabled:opacity-50"
                                                title="Kirim file PDF ke WA/Telegram personal guru">
                                                <i class="bi bi-send"></i>
                                            </button>
                                            <button type="button" x-on:click="bukaBatal({{ $row['id'] }})" :disabled="memproses"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-rose-600 transition-colors hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                                                title="Batalkan surat ini">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            <p class="text-[11px] text-slate-400">
                <i class="bi bi-camera"></i>
                Angka alpha di dalam surat adalah foto saat surat terbit, bukan dihitung ulang.
            </p>
        </x-slot:footer>
    </x-ui.card>

    {{-- Kandidat: guru yang perlu surat tapi belum punya --}}
    <x-ui.card body-class="p-0" icon="bi-person-exclamation" tone="amber"
        title="Guru yang Belum Dapat Surat"
        subtitle="Alpha bulan {{ $blnIdx[(int) $bulan] ?? '-' }} {{ (int) $tahun }} sudah melewati ambang {{ $ambangAwal }} hari, tapi belum ada surat terbit.">
        <x-slot:meta>
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold tabular-nums text-amber-700">
                <i class="bi bi-people"></i> {{ count($barisKandidat) }} guru
            </span>
        </x-slot:meta>

        @if (empty($barisKandidat))
            <div class="p-5">
                <x-ui.empty icon="bi-check2-circle" title="Semua guru sudah punya surat"
                    description="Tidak ada guru yang melewati ambang alpha tanpa surat pada periode ini." />
            </div>
        @else
            <div class="max-h-[52vh] overflow-auto" tabindex="0">
                <table class="w-full min-w-[760px] text-[13px]">
                    <caption class="sr-only">Guru yang alpha-nya melewati ambang tetapi belum menerima surat teguran</caption>
                    <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="w-10 px-3 py-3 text-center font-bold">#</th>
                            <th class="min-w-[220px] px-3 py-3 text-left font-bold">Nama Guru</th>
                            <th class="min-w-[130px] px-3 py-3 text-left font-bold">NIP</th>
                            <th class="w-[100px] px-3 py-3 text-center font-bold text-rose-600">Alpha</th>
                            <th class="min-w-[190px] px-3 py-3 text-center font-bold">Tercapai</th>
                            <th class="w-[130px] px-3 py-3 text-center font-bold">Buat Surat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($barisKandidat as $k)
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2.5 text-sm font-semibold text-slate-800">{{ $k['nama'] }}</td>
                                <td class="px-3 py-2.5 font-mono text-xs tabular-nums text-slate-600">{{ $k['nip'] !== '' ? $k['nip'] : '—' }}</td>
                                <td class="px-3 py-2.5 text-center text-sm font-bold tabular-nums text-rose-600">{{ $k['alpha'] }}</td>
                                <td class="px-3 py-2.5 text-center">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $k['tingkatKelas'] }}">
                                        {{ $k['tingkat'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <button type="button" x-on:click="bukaBuat({{ $k['idGuru'] }})" :disabled="memproses"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                        title="Buat surat teguran untuk guru ini">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            <p class="text-[11px] text-slate-400">
                <i class="bi bi-info-circle"></i>
                Tingkat surat ditentukan otomatis oleh server dari jumlah alpha terkini — tingkat terberat yang sudah dilampaui.
            </p>
        </x-slot:footer>
    </x-ui.card>

    {{-- Penjelasan aturan --}}
    <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <i class="bi bi-info-circle-fill mt-0.5 text-base text-slate-400"></i>
        <div class="space-y-1 text-xs leading-relaxed text-slate-600">
            <p class="font-bold text-slate-700">Cara kerja surat teguran</p>
            <p>
                <b>Sumber angka</b> — dihitung dari tabel presensi guru, hanya hari kerja efektif (bukan hari libur minggu,
                nasional, atau jadwal kerja libur). Hari yang belum datang tidak dihitung, jadi nomor ini tidak akan melompat
                di awal bulan.
            </p>
            <p>
                <b>Alpha</b> — hari kerja efektif tanpa baris absensi, atau berstatus ALPHA / ALPA. Izin, sakit, dan dinas
                yang disetujui tidak dihitung.
            </p>
            <p>
                <b>Terbit otomatis</b> — tanggal 7, 14, 21, dan akhir bulan. Satu guru mendapat satu surat per evaluasi, yaitu
                tingkat terberat yang sudah dilampaui. Tingkat surat tidak pernah turun dalam satu bulan.
            </p>
            <p>
                <b>Angka di dalam surat adalah foto saat surat terbit</b>, bukan dihitung ulang. Dicetak ulang tahun depan pun
                angkanya sama seperti waktu guru membacanya.
            </p>
            <p>
                <b>Pembatalan</b> — surat dibatalkan, bukan dihapus, supaya arsip keberadaannya tetap bisa dibuktikan.
                Pembatalan otomatis karena hari alpha dilengkapi akan hidup kembali kalau alpha-nya naik lagi; pembatalan
                manual operator tidak ditimpa perhitungan.
            </p>
        </div>
    </div>

    {{-- ══════════ MODAL: BUAT SURAT TEGURAN MANUAL ══════════ --}}
    <x-ui.modal id="buatTeguranModal" title="Buat Surat Teguran" icon="bi-envelope-plus" tone="amber" size="max-w-lg">
        <form x-on:submit.prevent="kirimBuat()">
            <div class="space-y-4 p-5">
                <div class="flex gap-2 rounded-xl border border-amber-100 bg-amber-50 px-3.5 py-2.5 text-xs text-amber-800">
                    <i class="bi bi-exclamation-triangle-fill mt-0.5 shrink-0"></i>
                    <p class="leading-relaxed">
                        Surat akan diterbitkan untuk <strong x-text="buat.nama || '—'"></strong>
                        pada periode <strong>{{ ($blnIdx[(int) $bulan] ?? '-').' '.(int) $tahun }}</strong>.
                    </p>
                </div>

                <p class="text-xs leading-relaxed text-slate-500">
                    Tingkat surat ditentukan otomatis dari jumlah alpha bulan ini — tingkat terberat yang sudah dilampaui.
                    Surat tetap tidak terbit kalau guru sudah punya surat pada periode ini atau alpha-nya belum melewati ambang.
                </p>
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
    <x-ui.modal id="batalTeguranModal" title="Batalkan Surat Teguran" icon="bi-x-circle" tone="rose" size="max-w-lg">
        <form x-on:submit.prevent="batalkanTeguran()">
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
                    <p class="mt-1 text-[11px] leading-relaxed text-amber-700">
                        Pembatalan manual tidak akan dibatalkan otomatis, bahkan kalau alpha guru naik lagi di bulan berikutnya.
                    </p>
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

    {{-- ══════════ MODAL: KIRIM PDF PERSONAL ══════════ --}}
    <x-ui.modal id="kirimTeguranModal" title="Kirim PDF ke Personal?" icon="bi-send" tone="blue" size="max-w-lg">
        <form x-on:submit.prevent="kirimTeguran()">
            <div class="space-y-4 p-5">
                <div class="flex gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3.5 py-2.5 text-xs text-blue-800">
                    <i class="bi bi-info-circle-fill mt-0.5 shrink-0"></i>
                    <p class="leading-relaxed">
                        File PDF surat akan dikirim ke WA/Telegram pribadi
                        <strong x-text="kirim.nama || '—'"></strong> saja — tidak diumumkan ke grup.
                        Surat tidak diterbitkan ulang.
                    </p>
                </div>

                <p class="text-xs leading-relaxed text-slate-500">
                    Isi PDF identik dengan hasil unduhan. Kiriman yang gagal tidak mengubah status surat.
                </p>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
                <button type="button" data-ui-dismiss="modal"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100">
                    Batal
                </button>
                <button type="submit" :disabled="memproses"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-cyan-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-cyan-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <i class="bi" :class="memproses && proses === 'kirim' ? 'bi-arrow-repeat animate-spin' : 'bi-send'"></i>
                    Ya, Kirim
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('suratTeguran', (config) => ({
        semuaBaris: config.baris || [],
        semuaKandidat: config.kandidat || [],
        bulan: config.bulan,
        tahun: config.tahun,
        periode: config.periode || '',
        csrf: config.csrf,
        urls: config.urls,
        memproses: false,
        proses: '',
        buat: { idGuru: null, nama: '' },
        batal: { id: null, nomor: '', nama: '', alasan: '' },
        kirim: { id: null, nama: '' },

        cariBaris(id) {
            return this.semuaBaris.find((row) => row.id === Number(id)) || {};
        },

        bukaBuat(idGuru) {
            const row = this.semuaKandidat.find((k) => k.idGuru === Number(idGuru)) || {};
            this.buat = { idGuru: Number(idGuru), nama: row.nama || '' };
            this.$store.modals.open('#buatTeguranModal');
        },

        bukaBatal(id) {
            const row = this.cariBaris(id);
            this.batal = {
                id: Number(id),
                nomor: row.nomor || '',
                nama: row.nama || '',
                alasan: '',
            };
            this.$store.modals.open('#batalTeguranModal');
            this.$nextTick(() => this.$refs.alasan?.focus());
        },

        bukaKirim(id) {
            const row = this.cariBaris(id);
            this.kirim = { id: Number(id), nama: row.nama || '' };
            this.$store.modals.open('#kirimTeguranModal');
        },

        async kirimData(url, isi) {
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
            const pengirim = this.buat;

            await this.jalankanAksi('buat', async () => {
                const hasil = await this.kirimData(this.urls.buat, {
                    id_guru: pengirim.idGuru,
                    bulan: this.bulan,
                    tahun: this.tahun,
                });

                if (!hasil.ok) {
                    Swal.fire('Gagal Membuat Surat', hasil.pesan, 'error');

                    return;
                }

                this.$store.modals.close('#buatTeguranModal');
                await this.selesai('Surat Dibuat', hasil.pesan, true);
            });
        },

        async batalkanTeguran() {
            const pengirim = this.batal;

            await this.jalankanAksi('batal', async () => {
                const hasil = await this.kirimData(this.urls.batal + '/' + pengirim.id, {
                    alasan: pengirim.alasan.trim(),
                });

                if (!hasil.ok) {
                    Swal.fire('Gagal Membatalkan', hasil.pesan, 'error');

                    return;
                }

                this.$store.modals.close('#batalTeguranModal');
                await this.selesai('Surat Dibatalkan', hasil.pesan, true);
            });
        },

        async kirimTeguran() {
            const pengirim = this.kirim;

            await this.jalankanAksi('kirim', async () => {
                const hasil = await this.kirimData(this.urls.kirim + '/' + pengirim.id, {});

                if (!hasil.ok) {
                    Swal.fire('Gagal Mengirim', hasil.pesan, 'error');

                    return;
                }

                this.$store.modals.close('#kirimTeguranModal');
                await this.selesai('PDF Terkirim', hasil.pesan);
            });
        },
    }));
});
</script>
@endsection
