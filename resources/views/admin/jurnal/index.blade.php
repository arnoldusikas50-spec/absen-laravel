@extends('layouts.main')

@section('content')
@php
    $exportQuery = [];
    if (!empty($mode_bulanan)) {
        $exportQuery['bulan'] = $bulan;
        $exportQuery['tahun'] = $tahun;
    } else {
        $exportQuery['tanggal'] = $tanggal;
    }
    if (!empty($id_guru_pilih)) $exportQuery['id_guru'] = $id_guru_pilih;
    if (!empty($id_kelas_pilih)) $exportQuery['id_kelas'] = $id_kelas_pilih;
    if (!empty($id_mapel_pilih)) $exportQuery['id_mapel'] = $id_mapel_pilih;
    if (!empty($validasi_pilih)) $exportQuery['validasi'] = $validasi_pilih;
    $exportUrl = url('admin/jurnal/export') . '?' . http_build_query($exportQuery);

    $resetFilterUrl = url('admin/jurnal') . '?' . http_build_query(
        !empty($mode_bulanan) ? ['bulan' => $bulan, 'tahun' => $tahun] : ['tanggal' => $tanggal]
    );

    $periodeLabel = $periode_label ?? buat_tanggal(date('l, d F Y', strtotime($tanggal)));

    $totalSesiView          = (int) ($total_sesi ?? count($jurnals));
    $totalTerjadwalView     = (int) ($total_terjadwal ?? 0);
    $totalTerjadwalSudah    = (int) ($total_terjadwal_sudah ?? 0);
    $totalTerjadwalBelum    = (int) ($total_terjadwal_belum ?? 0);
    $totalTerjadwalAkan     = (int) ($total_terjadwal_akan ?? 0);
    $persenCakupan          = $totalTerjadwalView > 0
        ? min(100, (int) round($totalTerjadwalSudah / $totalTerjadwalView * 100))
        : null;
    $barCakupan             = $persenCakupan === null
        ? 'bg-slate-200'
        : ($persenCakupan >= 80 ? 'bg-emerald-500' : ($persenCakupan >= 50 ? 'bg-amber-500' : 'bg-rose-500'));

    $hadirCount  = (int) ($total_guru_hadir ?? 0);
    $hadirWajibCount = (int) ($total_guru_hadir_berjadwal ?? 0);
    $wajibCount  = (int) ($total_guru_wajib_jurnal ?? 0);
    $belumCount  = (int) ($total_belum_jurnal ?? 0);
    $guruSudahJurnal = (int) ($total_guru_sudah_jurnal ?? 0);
    $guruBelum   = $guru_belum_jurnal ?? [];
    $guruBelumSesi = $guru_belum_sesi ?? [];

    // Status validasi jurnal. Angka "sudah dijurnal" hanya ikut terbelah saat
    // mode ketat aktif; di luar itu jurnal tetap dihitung terisi, sama seperti
    // gerbang absen pulang guru.
    $adaValidasi        = !empty($kolom_validasi);
    $modeValidasiKetat  = !empty($mode_validasi_ketat);
    $validasiStats      = is_array($validasi_stats ?? null) ? $validasi_stats : [];
    $validasiMenunggu   = (int) ($validasiStats['menunggu'] ?? 0);
    $validasiDitolak    = (int) ($validasiStats['ditolak'] ?? 0);
    $validasiDisetujui  = (int) ($validasiStats['disetujui'] ?? 0);
    $totalTerjadwalMenunggu = (int) ($total_terjadwal_menunggu ?? 0);
    $petugasValidasi    = $petugas_validasi ?? [];
    $validasiPilih      = (string) ($validasi_pilih ?? '');
    $labelValidasi      = [
        'APPROVED' => ['Disetujui', 'bg-emerald-100 text-emerald-800', 'bi-check-circle-fill'],
        'PENDING'  => ['Menunggu validasi', 'bg-indigo-100 text-indigo-700', 'bi-hourglass-split'],
        'REJECTED' => ['Ditolak', 'bg-rose-100 text-rose-700', 'bi-x-circle-fill'],
    ];
    $statusValidasiBaris = static function ($status) use ($labelValidasi): array {
        $st = strtoupper(trim((string) ($status ?? '')));
        return $labelValidasi[isset($labelValidasi[$st]) ? $st : 'PENDING'];
    };

    // Tautan drill-down kartu guru: tampilkan sesi milik guru tersebut saja.
    $guruFilterUrl = static function (int $idGuru) use ($tanggal, $id_guru_pilih): string {
        $query = ['tanggal' => $tanggal];
        if ($idGuru > 0 && (int) $id_guru_pilih !== $idGuru) {
            $query['id_guru'] = $idGuru;
        }
        return url('admin/jurnal') . '?' . http_build_query($query);
    };

    $inisial = static function (string $nama): string {
        $nama = trim(preg_replace('/\s+/', ' ', $nama));
        if ($nama === '') return '?';
        $bagian = explode(' ', $nama);
        $hasil = mb_substr($bagian[0], 0, 1);
        if (isset($bagian[1]) && $bagian[1] !== '') $hasil .= mb_substr($bagian[1], 0, 1);
        return mb_strtoupper($hasil);
    };

    $namaGuruFilter = '';
    foreach (($gurus ?? []) as $g) {
        if ((int) $g->id_guru === (int) $id_guru_pilih) { $namaGuruFilter = $g->nama_guru; break; }
    }
    $namaKelasFilter = '';
    foreach (($kelases ?? []) as $k) {
        $kId = is_object($k) ? (int) $k->id_kelas : (int) $k;
        if ($kId === (int) $id_kelas_pilih) { $namaKelasFilter = is_object($k) ? $k->nama_kelas : $k; break; }
    }
    $namaMapelFilter = '';
    foreach (($mapels ?? []) as $m) {
        if ((int) $m->id_mapel === (int) $id_mapel_pilih) { $namaMapelFilter = $m->nama_mapel; break; }
    }
    $adaFilter = !empty($id_guru_pilih) || !empty($id_kelas_pilih) || !empty($id_mapel_pilih);

    // Status sesi terjadwal dipakai bersama oleh filter Alpine dan badge baris.
    $statusSesiRow = static function (array $s) use ($modeValidasiKetat): string {
        $sudah = !empty($s['terisi']);
        $berjalan = !empty($s['berjalan']);
        $menungguValidasi = $sudah && strtoupper((string) ($s['validasi_status'] ?? '')) !== 'APPROVED';
        return $sudah
            ? ($modeValidasiKetat && $menungguValidasi ? 'menunggu' : 'terisi')
            : ($berjalan ? 'belum' : 'akan');
    };
    $sesiList = $sesi_terjadwal ?? [];
    $sesiStatusCount = ['semua' => count($sesiList), 'belum' => 0, 'terisi' => 0, 'menunggu' => 0, 'akan' => 0];
    foreach ($sesiList as $s) {
        $sesiStatusCount[$statusSesiRow($s)]++;
    }
@endphp

<div class="space-y-4 px-4 pb-8 pt-4 md:px-6 md:pt-6">

    <x-ui.page-header
        title="Monitoring Jurnal Mengajar"
        icon="bi-journal-text"
        tone="blue"
        subtitle="Pantau pelaksanaan KBM guru, materi pokok, dan cross-check dengan kehadiran harian."
        :crumbs="['Monitoring KBM', 'Jurnal Mengajar']">
        <x-slot:actions>
            <span class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] font-bold text-slate-600">
                <i class="bi bi-calendar3 text-slate-400"></i>
                {{ $periodeLabel }}
            </span>
            <a href="{{ $exportUrl }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-700 no-underline transition-colors hover:bg-emerald-100">
                <i class="bi bi-filetype-csv"></i> Ekspor CSV
            </a>
            <a href="{{ url('admin/jurnal/rekap') }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white no-underline shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                <i class="bi bi-bar-chart-fill"></i> Rekap Bulanan
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- Ringkasan -->
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.card bodyClass="p-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-base text-indigo-600">
                    <i class="bi bi-journal-check"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="m-0 text-[10px] font-bold uppercase tracking-wide text-slate-400">Jurnal Terisi</p>
                    <p class="m-0 text-xl font-extrabold leading-tight text-slate-900">{{ $totalSesiView }}</p>
                </div>
            </div>
            <p class="mt-2.5 text-[10px] font-semibold text-slate-400">
                @if ($persenCakupan !== null)
                    {{ $totalTerjadwalSudah }} dari {{ $totalTerjadwalView }} sesi terjadwal
                    @if ($totalTerjadwalAkan > 0)
                        <span class="text-slate-300">&middot;</span> {{ $totalTerjadwalAkan }} belum dimulai
                    @endif
                @elseif (!empty($mode_bulanan))
                    selama periode ini
                @else
                    belum ada jadwal pada tanggal ini
                @endif
            </p>
            @if ($persenCakupan !== null)
                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100" role="progressbar"
                    aria-valuenow="{{ $persenCakupan }}" aria-valuemin="0" aria-valuemax="100"
                    aria-label="Cakupan jurnal {{ $persenCakupan }} persen">
                    <div class="h-full rounded-full {{ $barCakupan }}" style="width: {{ $persenCakupan }}%"></div>
                </div>
            @endif
        </x-ui.card>

        <x-ui.card bodyClass="p-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-base text-blue-600">
                    <i class="bi bi-person-workspace"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="m-0 text-[10px] font-bold uppercase tracking-wide text-slate-400">
                        {{ !empty($mode_bulanan) ? 'Guru Mengisi Jurnal' : 'Guru Sudah Jurnal' }}
                    </p>
                    <p class="m-0 text-xl font-extrabold leading-tight text-slate-900">
                        {{ !empty($mode_bulanan) ? (int) ($total_guru_mengajar ?? 0) : $guruSudahJurnal }}
                    </p>
                </div>
            </div>
            <p class="mt-2.5 text-[10px] font-semibold text-slate-400">
                @if (!empty($mode_bulanan))
                    mengisi jurnal periode ini
                @elseif ($wajibCount > 0)
                    dari {{ $wajibCount }} guru berjadwal &middot; {{ $hadirWajibCount }} hadir
                @else
                    {{ $hadirCount }} guru tercatat hadir
                @endif
            </p>
        </x-ui.card>

        <x-ui.card bodyClass="p-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-base text-violet-600">
                    <i class="bi bi-door-open"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="m-0 text-[10px] font-bold uppercase tracking-wide text-slate-400">Kelas Tercakup</p>
                    <p class="m-0 text-xl font-extrabold leading-tight text-slate-900">{{ (int) ($total_kelas_tercakup ?? 0) }}</p>
                </div>
            </div>
            <p class="mt-2.5 text-[10px] font-semibold text-slate-400">
                {{ count($kelases ?? []) > 0 ? 'dari ' . count($kelases) . ' kelas' : 'kelas memiliki jurnal' }}
            </p>
        </x-ui.card>

        <x-ui.card bodyClass="p-4" style="{{ $belumCount > 0 ? 'border-color: rgb(253 230 138 / 0.8)' : '' }}">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-base {{ $belumCount > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }}">
                    <i class="bi {{ $belumCount > 0 ? 'bi-person-exclamation' : 'bi-check2-circle' }}"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="m-0 text-[10px] font-bold uppercase tracking-wide text-slate-400">
                        {{ !empty($mode_bulanan) ? 'Guru Berjadwal Tanpa Jurnal' : 'Hadir Belum Jurnal' }}
                    </p>
                    <p class="m-0 text-xl font-extrabold leading-tight {{ $belumCount > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ $belumCount }}</p>
                </div>
            </div>
            <p class="mt-2.5 text-[10px] font-semibold text-slate-400">
                @if (!empty($mode_bulanan))
                    belum mengisi bulan ini &middot; {{ $wajibCount }} guru berjadwal
                @elseif ($hadirWajibCount > 0)
                    dari {{ $hadirWajibCount }} guru berjadwal yang hadir
                @elseif ($wajibCount > 0)
                    belum ada yang hadir dari {{ $wajibCount }} guru berjadwal
                @else
                    tidak ada guru berjadwal
                @endif
            </p>
        </x-ui.card>
    </div>

    <!-- Filter & Date Selector -->
    <x-ui.card title="Filter Jurnal" icon="bi-funnel" subtitle="Saring sesi berdasarkan tanggal, guru, kelas, mata pelajaran, dan status validasi.">
        @if (!empty($mode_bulanan))
            <div class="mb-3 flex items-start gap-2 rounded-xl border border-indigo-100 bg-indigo-50 px-3 py-2 text-[11px] font-semibold text-indigo-800">
                <i class="bi bi-calendar-month mt-0.5"></i>
                <span>
                    Menampilkan seluruh jurnal periode <strong>{{ $periodeLabel }}</strong>.
                    Pilih tanggal lalu klik <strong>Tampilkan</strong> untuk kembali ke mode harian,
                    atau <a href="{{ url('admin/jurnal') }}" class="font-bold underline">kembali ke hari ini</a>.
                </span>
            </div>
        @endif

        <form method="GET" action="{{ url('admin/jurnal') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
            @if (!empty($mode_bulanan))
                <input type="hidden" name="bulan" value="{{ (int) $bulan }}">
                <input type="hidden" name="tahun" value="{{ (int) $tahun }}">
            @endif

            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase text-slate-600">Tanggal</label>
                <input type="date" name="tanggal" value="{{ $tanggal }}"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase text-slate-600">Guru Pengajar</label>
                <select name="id_guru" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <option value="">Semua Guru</option>
                    @foreach ($gurus as $g)
                        <option value="{{ $g->id_guru }}" {{ $id_guru_pilih == $g->id_guru ? 'selected' : '' }}>
                            {{ $g->nama_guru }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase text-slate-600">Kelas</label>
                <select name="id_kelas" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <option value="">Semua Kelas</option>
                    @if (!empty($kelases))
                        @foreach ($kelases as $key => $k)
                            @php
                                $kId = is_object($k) ? $k->id_kelas : $key;
                                $kNama = is_object($k) ? $k->nama_kelas : $k;
                            @endphp
                            <option value="{{ $kId }}" {{ $id_kelas_pilih == $kId ? 'selected' : '' }}>
                                {{ $kNama }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase text-slate-600">Mata Pelajaran</label>
                <select name="id_mapel" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <option value="">Semua Mapel</option>
                    @foreach (($mapels ?? []) as $m)
                        <option value="{{ (int) $m->id_mapel }}" {{ (int) $id_mapel_pilih === (int) $m->id_mapel ? 'selected' : '' }}>
                            {{ $m->nama_mapel }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($adaValidasi)
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase text-slate-600">Status Validasi</label>
                    <select name="validasi" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                        <option value="">Semua Status</option>
                        <option value="PENDING" {{ $validasiPilih === 'PENDING' ? 'selected' : '' }}>Menunggu ({{ $validasiMenunggu }})</option>
                        <option value="APPROVED" {{ $validasiPilih === 'APPROVED' ? 'selected' : '' }}>Disetujui ({{ $validasiDisetujui }})</option>
                        <option value="REJECTED" {{ $validasiPilih === 'REJECTED' ? 'selected' : '' }}>Ditolak ({{ $validasiDitolak }})</option>
                    </select>
                </div>
            @endif

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                    <i class="bi bi-search me-1"></i> Tampilkan
                </button>
                <a href="{{ url('admin/jurnal') }}" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600 no-underline transition-colors hover:bg-slate-200" title="Reset filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>

        @if ($adaFilter)
            <div class="mt-3 flex flex-wrap items-center gap-1.5 border-t border-slate-100 pt-3">
                <span class="me-1 text-[10px] font-bold uppercase tracking-wide text-slate-400">Filter aktif</span>
                @if ($namaGuruFilter !== '')
                    <span class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2 py-1 text-[10px] font-bold text-blue-700">
                        <i class="bi bi-person-fill"></i> {{ $namaGuruFilter }}
                    </span>
                @endif
                @if ($namaKelasFilter !== '')
                    <span class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2 py-1 text-[10px] font-bold text-indigo-700">
                        <i class="bi bi-door-open-fill"></i> {{ $namaKelasFilter }}
                    </span>
                @endif
                @if ($namaMapelFilter !== '')
                    <span class="inline-flex items-center gap-1 rounded-lg bg-violet-50 px-2 py-1 text-[10px] font-bold text-violet-700">
                        <i class="bi bi-book-fill"></i> {{ $namaMapelFilter }}
                    </span>
                @endif
                <a href="{{ $resetFilterUrl }}" class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[10px] font-bold text-slate-500 no-underline hover:bg-slate-100">
                    <i class="bi bi-x-circle"></i> Hapus semua
                </a>
            </div>
        @endif
    </x-ui.card>

    @if ($adaValidasi && ((int) ($validasiStats['total'] ?? 0)) > 0)
        <!-- Antrean validasi: angka ini yang dilihat guru di gerbang absen pulang -->
        @php
            $validasiQueryBase = array_filter([
                'tanggal' => $tanggal,
                'bulan' => !empty($mode_bulanan) ? $bulan : null,
                'tahun' => !empty($mode_bulanan) ? $tahun : null,
                'id_guru' => $id_guru_pilih ?: null,
                'id_kelas' => $id_kelas_pilih ?: null,
                'id_mapel' => $id_mapel_pilih ?: null,
            ], static fn($v) => $v !== null && $v !== '');
            $namaPetugas = implode(' / ', array_map(static fn($p) => (string) ($p['nama'] ?? ''), $petugasValidasi));
        @endphp

        <x-ui.card title="Antrean Validasi Jurnal" icon="bi-shield-check" tone="violet">
            <x-slot:meta>
                <span class="flex h-2.5 w-2.5 rounded-full {{ $validasiMenunggu + $validasiDitolak > 0 ? 'bg-indigo-500' : 'bg-emerald-500' }}"></span>
                @if ($modeValidasiKetat)
                    <span class="inline-flex items-center gap-1 rounded-full border border-indigo-100 bg-indigo-50 px-2 py-0.5 text-[10px] font-extrabold text-indigo-700">
                        <i class="bi bi-shield-check text-[9px]"></i> Mode ketat aktif
                    </span>
                @endif
            </x-slot:meta>

            <x-slot:actions>
                @if ($validasiMenunggu > 0)
                    <a href="{{ url('admin/jurnal') . '?' . http_build_query($validasiQueryBase + ['validasi' => 'PENDING']) }}"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-[11px] font-bold text-indigo-700 no-underline transition-colors hover:bg-indigo-100">
                        <i class="bi bi-hourglass-split"></i> Menunggu {{ $validasiMenunggu }}
                    </a>
                @endif
                @if ($validasiDitolak > 0)
                    <a href="{{ url('admin/jurnal') . '?' . http_build_query($validasiQueryBase + ['validasi' => 'REJECTED']) }}"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-rose-50 px-3 py-1.5 text-[11px] font-bold text-rose-700 no-underline transition-colors hover:bg-rose-100">
                        <i class="bi bi-x-circle"></i> Ditolak {{ $validasiDitolak }}
                    </a>
                @endif
                <a href="{{ url('admin/jurnal') . '?' . http_build_query($validasiQueryBase + ['validasi' => 'APPROVED']) }}"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-[11px] font-bold text-emerald-700 no-underline transition-colors hover:bg-emerald-100">
                    <i class="bi bi-check-circle"></i> Disetujui {{ $validasiDisetujui }}
                </a>
                <a href="{{ url('guru/piket/laporan') }}"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-[11px] font-bold text-slate-600 no-underline transition-colors hover:bg-slate-200">
                    <i class="bi bi-check2-all"></i> Halaman Validasi Piket
                </a>
            </x-slot:actions>

            <p class="text-[11px] text-slate-500">
                @if ($modeValidasiKetat)
                    Selama mode ketat aktif, guru baru bisa absen pulang setelah jurnalnya berstatus <b>Disetujui</b>.
                @else
                    Mode ketat tidak aktif — jurnal terisi sudah cukup untuk absen pulang, tapi antrean validasi tetap dipantau di sini.
                @endif
            </p>

            @if ($modeValidasiKetat)
                <p class="mt-3 flex items-start gap-1 text-[10px] font-semibold text-slate-500">
                    <i class="bi bi-person-badge mt-0.5"></i>
                    <span>
                        @if ($namaPetugas !== '')
                            Penanggung jawab validasi hari ini: <b>{{ $namaPetugas }}</b>.
                        @else
                            <span class="font-bold text-rose-600">Belum ada guru piket terjadwal hari ini</span>
                            — jurnal yang menunggu validasi tidak bisa disetujui, dan guru terkunci di absen pulang selama mode ketat aktif.
                        @endif
                    </span>
                </p>
            @endif
        </x-ui.card>
    @endif

    @if (empty($mode_bulanan))
        <!-- Guru hadir tetapi belum menulis jurnal -->
        <x-ui.card title="Guru Hadir, Belum Menulis Jurnal" icon="bi-person-exclamation" tone="{{ $belumCount > 0 ? 'amber' : 'emerald' }}"
            subtitle="Hanya guru yang punya jadwal mengajar pada tanggal ini. Tanpa jadwal berarti tidak wajib menulis jurnal.">
            <x-slot:actions>
                @if ($totalTerjadwalAkan > 0)
                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500"
                        title="Sesi yang jamnya belum tiba belum menagih jurnal">
                        <i class="bi bi-clock-history me-1"></i>{{ $totalTerjadwalAkan }} sesi belum dimulai
                    </span>
                @endif
                <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500">
                    {{ $hadirWajibCount }} dari {{ $wajibCount }} guru berjadwal hadir
                </span>
            </x-slot:actions>

            @if ($wajibCount === 0)
                <x-ui.empty icon="bi-calendar-x" title="Tidak ada jadwal mengajar"
                    description="Tidak ada jadwal mengajar pada tanggal ini, jadi tidak ada guru yang diwajibkan menulis jurnal." />
            @elseif ($hadirWajibCount === 0)
                <x-ui.empty icon="bi-person-dash" title="Belum ada guru hadir"
                    description="Belum ada guru berjadwal yang tercatat hadir pada tanggal ini." />
            @elseif (empty($guruBelum))
                <x-ui.empty icon="bi-check-circle" title="Semua jurnal sudah ditulis"
                    description="Semua guru berjadwal yang hadir sudah menulis jurnal untuk sesi yang sudah berjalan." />
            @else
                <div class="grid max-h-[430px] grid-cols-1 gap-2.5 overflow-y-auto pe-1 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($guruBelum as $b)
                        @php
                            $gid = (int) ($b->id_guru ?? 0);
                            $jamMasuk = (string) ($b->jam_masuk ?? '');
                            $jamLabel = strlen($jamMasuk) >= 16 ? substr($jamMasuk, 11, 5) : (strlen($jamMasuk) >= 5 ? substr($jamMasuk, 0, 5) : '');
                            $sesiKurang = $guruBelumSesi[$gid] ?? [];
                            $nMenunggu = count(array_filter($sesiKurang, static fn($sk) => !empty($sk['menunggu_validasi'])));
                        @endphp
                        <a href="{{ $guruFilterUrl($gid) }}"
                            class="group block rounded-2xl border border-amber-200/70 bg-amber-50/40 p-3 no-underline transition-colors hover:border-amber-300 hover:bg-amber-50"
                            title="Tampilkan sesi guru ini">
                            <div class="flex items-start gap-2.5">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-[11px] font-extrabold text-amber-700">
                                    {{ $inisial((string) ($b->nama_guru ?? '')) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="m-0 truncate text-xs font-bold uppercase leading-snug text-slate-800" title="{{ $b->nama_guru ?? '' }}">
                                        {{ $b->nama_guru ?? ('Guru #' . $gid) }}
                                    </p>
                                    <p class="mt-0.5 text-[10px] font-semibold text-slate-500">
                                        Hadir{!! $jamLabel !== '' ? ' · ' . e($jamLabel) : '' !!}
                                        &middot; {{ count($sesiKurang) - $nMenunggu }} sesi belum dijurnal
                                        @if ($nMenunggu > 0)
                                            &middot; {{ $nMenunggu }} menunggu validasi
                                        @endif
                                    </p>
                                </div>
                                <i class="bi bi-chevron-right mt-1 shrink-0 text-[10px] text-amber-400 transition-colors group-hover:text-amber-600"></i>
                            </div>

                            @if (!empty($sesiKurang))
                                <div class="mt-2.5 flex flex-wrap gap-1">
                                    @foreach (array_slice($sesiKurang, 0, 3) as $sk)
                                        @php $skMenunggu = !empty($sk['menunggu_validasi']); @endphp
                                        <span class="inline-flex max-w-full items-center gap-1 rounded-lg border bg-white px-2 py-1 text-[10px] font-bold text-slate-700 {{ $skMenunggu ? 'border-indigo-200' : 'border-amber-200/70' }}"
                                            title="{{ $skMenunggu ? 'Jurnal sudah ditulis, menunggu validasi piket · ' : '' }}Jam {{ (int) ($sk['jam_ke'] ?? 0) }} · {{ $sk['nama_kelas'] ?? '' }} · {{ $sk['nama_mapel'] ?? '' }} ({{ ($sk['dari'] ?? '') . '–' . ($sk['sampai'] ?? '') }})">
                                            <i class="bi {{ $skMenunggu ? 'bi-hourglass-split text-indigo-500' : 'bi-clock text-amber-500' }}"></i>
                                            <span class="truncate">
                                                Jam {{ (int) ($sk['jam_ke'] ?? 0) }} · {{ $sk['nama_kelas'] ?? '' }} · {{ $sk['nama_mapel'] ?? '' }}
                                            </span>
                                        </span>
                                    @endforeach
                                    @if (count($sesiKurang) > 3)
                                        @php
                                            $sisaLabel = implode(' | ', array_map(static function ($sk): string {
                                                return 'Jam ' . (int) ($sk['jam_ke'] ?? 0) . ' '
                                                    . ($sk['nama_kelas'] ?? '') . ' '
                                                    . ($sk['nama_mapel'] ?? '');
                                            }, array_slice($sesiKurang, 3)));
                                        @endphp
                                        <span class="inline-flex items-center rounded-lg bg-amber-100 px-2 py-1 text-[10px] font-bold text-amber-800" title="{{ $sisaLabel }}">
                                            +{{ count($sesiKurang) - 3 }} sesi
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>

                <p class="mt-3 flex items-start gap-1 text-[10px] text-slate-400">
                    <i class="bi bi-info-circle mt-0.5"></i>
                    <span>Klik kartu guru untuk memfilter sesi miliknya. Guru tanpa jadwal pada tanggal ini tidak diwajibkan menulis jurnal,
                    dan sesi yang belum dimulai belum ditagih. Rincian tiap sesi ada di panel <b>Sesi Terjadwal vs Jurnal</b> di bawah.</span>
                </p>
            @endif
        </x-ui.card>

        <!-- Sesi terjadwal vs jurnal -->
        <x-ui.card title="Sesi Terjadwal vs Jurnal" icon="bi-calendar2-week" tone="blue" bodyClass="p-0"
            x-data="{ tab: 'semua', counts: {{ Js::from($sesiStatusCount) }} }">
            <x-slot:meta>
                <p class="text-[11px] text-slate-500">
                    {{ $totalTerjadwalSudah }} sudah dijurnal &middot; {{ $totalTerjadwalBelum }} belum
                    @if ($modeValidasiKetat && $totalTerjadwalMenunggu > 0)
                        &middot; {{ $totalTerjadwalMenunggu }} menunggu validasi
                    @endif
                    @if ($totalTerjadwalAkan > 0)
                        &middot; {{ $totalTerjadwalAkan }} belum dimulai
                    @endif
                </p>
            </x-slot:meta>

            <x-slot:actions>
                <div class="flex items-center gap-1 rounded-full bg-slate-100 p-1" role="tablist" aria-label="Filter status sesi">
                    <button type="button" role="tab" aria-controls="sesiBody" @click="tab = 'semua'" :aria-selected="tab === 'semua'"
                        :class="tab === 'semua' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                        class="whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-extrabold transition-colors">
                        Semua {{ $totalTerjadwalView }}
                    </button>
                    <button type="button" role="tab" aria-controls="sesiBody" @click="tab = 'belum'" :aria-selected="tab === 'belum'"
                        :class="tab === 'belum' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                        class="whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-extrabold transition-colors">
                        Belum {{ $totalTerjadwalBelum }}
                    </button>
                    @if ($modeValidasiKetat && $totalTerjadwalMenunggu > 0)
                        <button type="button" role="tab" aria-controls="sesiBody" @click="tab = 'menunggu'" :aria-selected="tab === 'menunggu'"
                            :class="tab === 'menunggu' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                            class="whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-extrabold transition-colors">
                            Menunggu {{ $totalTerjadwalMenunggu }}
                        </button>
                    @endif
                    <button type="button" role="tab" aria-controls="sesiBody" @click="tab = 'terisi'" :aria-selected="tab === 'terisi'"
                        :class="tab === 'terisi' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                        class="whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-extrabold transition-colors">
                        Sudah {{ $totalTerjadwalSudah }}
                    </button>
                    @if ($totalTerjadwalAkan > 0)
                        <button type="button" role="tab" aria-controls="sesiBody" @click="tab = 'akan'" :aria-selected="tab === 'akan'"
                            :class="tab === 'akan' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                            class="whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-extrabold transition-colors">
                            Akan {{ $totalTerjadwalAkan }}
                        </button>
                    @endif
                </div>
                <a href="{{ url('admin/jadwal') }}"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-[11px] font-bold text-blue-700 no-underline transition-colors hover:bg-blue-100">
                    <i class="bi bi-calendar-week"></i> Atur Jadwal
                </a>
            </x-slot:actions>

            @if ($totalTerjadwalView === 0)
                <div class="p-5">
                    <x-ui.empty icon="bi-calendar-x" title="Belum ada jadwal pelajaran"
                        description="Belum ada jadwal pelajaran pada tanggal ini. Atur jadwal agar sesi KBM dapat dipantau otomatis.">
                        <a href="{{ url('admin/jadwal') }}"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white no-underline hover:bg-blue-700">
                            <i class="bi bi-calendar-plus"></i> Atur Jadwal
                        </a>
                    </x-ui.empty>
                </div>
            @else
                <x-ui.table id="sesiBody">
                    <x-slot:head>
                        <th class="px-4 py-3 text-center whitespace-nowrap" width="52">Jam</th>
                        <th class="px-4 py-3 whitespace-nowrap" width="104">Waktu</th>
                        <th class="px-4 py-3" width="180">Guru Pengajar</th>
                        <th class="px-4 py-3" width="110">Kelas</th>
                        <th class="px-4 py-3" width="200">Mata Pelajaran</th>
                        <th class="px-4 py-3" width="130">Status</th>
                    </x-slot:head>

                        @foreach ($sesiList as $s)
                            @php
                                $filterUrl = url('admin/jurnal') . '?' . http_build_query(array_filter([
                                    'tanggal' => $tanggal,
                                    'id_guru' => (int) ($s['id_guru'] ?? 0) ?: null,
                                    'id_kelas' => (int) ($s['id_kelas'] ?? 0) ?: null,
                                    'id_mapel' => (int) ($s['id_mapel'] ?? 0) ?: null,
                                ], static fn($v) => $v !== null && $v !== ''));
                                $sudah = !empty($s['terisi']);
                                $berjalan = !empty($s['berjalan']);
                                $menungguValidasi = $sudah && strtoupper((string) ($s['validasi_status'] ?? '')) !== 'APPROVED';
                                $stValidasi = $menungguValidasi ? $statusValidasiBaris($s['validasi_status'] ?? '') : null;
                                $statusSesi = $statusSesiRow($s);
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50/70" data-sesi-status="{{ $statusSesi }}"
                                x-show="tab === 'semua' || tab === '{{ $statusSesi }}'">
                                <td class="bg-blue-50/40 px-4 py-3 text-center font-extrabold text-blue-700">{{ (int) ($s['jam_ke'] ?? 0) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold tabular-nums text-slate-600">{{ ($s['dari'] ?? '-') . ' - ' . ($s['sampai'] ?? '-') }}</td>
                                <td class="px-4 py-3">
                                    @if ((int) ($s['id_guru'] ?? 0) > 0)
                                        <a href="{{ $filterUrl }}" class="font-bold uppercase text-slate-800 no-underline hover:text-blue-700">{{ $s['nama_guru'] ?? '-' }}</a>
                                    @else
                                        <span class="italic uppercase text-slate-400">Belum ditetapkan</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-bold uppercase text-indigo-700">{{ $s['nama_kelas'] ?? '-' }}</td>
                                <td class="px-4 py-3 font-semibold uppercase text-slate-700">{{ $s['nama_mapel'] ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @if ((int) ($s['id_guru'] ?? 0) <= 0)
                                        <a href="{{ url('admin/jadwal') }}" class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 no-underline">
                                            <i class="bi bi-person-x-fill text-[8px]"></i> Guru belum diset
                                        </a>
                                    @elseif ($sudah)
                                        <div class="flex flex-wrap items-center gap-1">
                                            @if ((int) ($s['id_jurnal'] ?? 0) > 0)
                                                <a href="{{ url('admin/jurnal/detail/' . (int) $s['id_jurnal']) }}" target="_blank"
                                                    class="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 no-underline hover:bg-emerald-200"
                                                    title="Baca jurnal sesi ini">
                                                    <i class="bi bi-check-circle-fill text-[8px]"></i> Dijurnal
                                                </a>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">
                                                    <i class="bi bi-check-circle-fill text-[8px]"></i> Dijurnal
                                                </span>
                                            @endif
                                            @if ($menungguValidasi && $adaValidasi)
                                                <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold {{ $stValidasi[1] }}"
                                                    title="Jurnal sudah ditulis, {{ strtolower($stValidasi[0]) }} oleh guru piket{{ $modeValidasiKetat ? ' — selama mode ketat, guru ini belum bisa absen pulang' : '' }}">
                                                    <i class="bi {{ $stValidasi[2] }} text-[8px]"></i> {{ $stValidasi[0] }}
                                                </span>
                                            @elseif ($adaValidasi)
                                                <span class="inline-flex items-center gap-1 rounded-md border border-emerald-100 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-600"
                                                    title="Sudah disetujui guru piket">
                                                    <i class="bi bi-shield-check text-[8px]"></i> Tervalidasi
                                                </span>
                                            @endif
                                        </div>
                                    @elseif ($berjalan)
                                        <span class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">
                                            <i class="bi bi-hourglass-split text-[8px]"></i> Belum dijurnal
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500"
                                            title="Sesi belum dimulai, jurnal belum ditagih">
                                            <i class="bi bi-clock text-[8px]"></i> Belum dimulai
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                </x-ui.table>

                <div class="px-5 py-8 text-center text-xs text-slate-400" x-show="counts[tab] === 0" x-cloak>
                    <i class="bi bi-funnel me-1"></i> Tidak ada sesi pada filter status ini.
                </div>
            @endif
        </x-ui.card>
    @endif

    <!-- Data Table -->
    <x-ui.card title="Jurnal Mengajar Terisi" icon="bi-journal-check" tone="emerald" bodyClass="p-0"
        x-data="{
            query: '',
            empty: false,
            check() {
                const rows = this.$refs.jurnalList ? this.$refs.jurnalList.querySelectorAll('[data-search]') : [];
                let shown = 0;
                rows.forEach((row) => { if (row.style.display !== 'none') shown++; });
                this.empty = shown === 0;
            }
        }">
        <x-slot:meta>
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-extrabold text-slate-600">
                {{ $totalSesiView }} sesi
            </span>
        </x-slot:meta>

        <x-slot:toolbar>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:w-64">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-slate-400"></i>
                    <input type="search" id="jurnalSearch" x-ref="search" x-model="query" @input.debounce.150ms="check()"
                        placeholder="Cari guru / kelas / mapel..." aria-label="Cari jurnal" autocomplete="off"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-3 text-[11px] font-semibold text-slate-800 transition-colors focus:border-transparent focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <span class="whitespace-nowrap text-[11px] font-semibold text-slate-400">
                    {{ $periodeLabel }}
                </span>
            </div>
        </x-slot:toolbar>

        @if (empty($jurnals))
            <div class="p-5">
                <x-ui.empty icon="bi-journal-x" title="Belum ada jurnal mengajar pada periode ini"
                    description="Jurnal yang diisi oleh guru akan muncul di sini secara real-time." />
            </div>
        @else
            <div x-ref="jurnalList" class="max-h-[68vh] overflow-auto [&_thead_th]:sticky [&_thead_th]:top-0 [&_thead_th]:z-10 [&_thead_th]:bg-slate-50">
                <x-ui.table id="jurnalBody">
                    <x-slot:head>
                        <th class="px-4 py-3 text-center whitespace-nowrap" width="50">Jam</th>
                        @if (!empty($mode_bulanan))
                            <th class="px-4 py-3 text-center whitespace-nowrap" width="80">Tanggal</th>
                        @endif
                        <th class="px-4 py-3" width="180">Guru Pengajar</th>
                        <th class="px-4 py-3" width="90">Kelas</th>
                        <th class="px-4 py-3" width="160">Mata Pelajaran</th>
                        <th class="px-4 py-3">Materi Pokok &amp; Model</th>
                        <th class="px-4 py-3" width="110">Presensi Guru</th>
                        <th class="px-4 py-3 text-center" width="70">Aksi</th>
                    </x-slot:head>

                        @foreach ($jurnals as $j)
                            @php
                                $hadirInfo = $guru_hadir_map[$j->tanggal . '|' . (int) $j->id_guru] ?? null;
                                $statusMasuk = strtoupper(trim((string) ($hadirInfo->status_masuk ?? '')));
                                $jamMasuk = trim((string) ($hadirInfo->jam_masuk ?? ''));
                                $adaJamMasuk = $jamMasuk !== ''
                                    && $jamMasuk !== '00:00:00'
                                    && !str_starts_with($jamMasuk, '0000-00-00');
                                $jamLabel = $adaJamMasuk
                                    ? (strlen($jamMasuk) >= 16 ? substr($jamMasuk, 11, 5) : substr($jamMasuk, 0, 5))
                                    : '';
                                $cari = strtolower(implode(' ', array_filter([
                                    (string) ($j->nama_guru ?? ''),
                                    (string) ($j->nip ?? ''),
                                    (string) ($j->nama_kelas ?? ''),
                                    (string) ($j->nama_mapel ?? ''),
                                    (string) ($j->materi_pokok ?? ''),
                                    (string) ($j->model_belajar ?? ''),
                                    (string) ($j->jenis_asesmen ?? ''),
                                    $adaValidasi ? strtolower($statusValidasiBaris($j->validasi_status ?? '')[0]) : '',
                                ])));
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50/70" data-search="{{ $cari }}"
                                x-show="query === '' || $el.dataset.search.includes(query.toLowerCase())">
                                <td class="bg-blue-50/40 px-4 py-3 text-center font-extrabold text-blue-700">
                                    {{ $j->jam_ke }}
                                </td>
                                @if (!empty($mode_bulanan))
                                    <td class="whitespace-nowrap px-4 py-3 text-center font-bold text-slate-600">
                                        {{ date('d/m/Y', strtotime($j->tanggal)) }}
                                        <span class="block text-[9px] font-semibold text-slate-400">{{ buat_tanggal(date('D', strtotime($j->tanggal))) }}</span>
                                    </td>
                                @endif
                                <td class="px-4 py-3">
                                    <p class="font-bold leading-tight text-slate-800">{{ $j->nama_guru }}</p>
                                    <p class="mt-0.5 text-[10px] text-slate-400">NIP: {{ $j->nip ?: '-' }}</p>
                                </td>
                                <td class="px-4 py-3 font-bold text-indigo-700">
                                    {{ $j->nama_kelas }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-700">
                                    {{ $j->nama_mapel }}
                                </td>
                                <td class="px-4 py-3">
                                    <p class="line-clamp-1 font-bold text-slate-800" title="{{ $j->materi_pokok }}">
                                        {{ $j->materi_pokok }}
                                    </p>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                        @if ($adaValidasi)
                                            @php $vLabel = $statusValidasiBaris($j->validasi_status ?? ''); @endphp
                                            <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[9px] font-bold {{ $vLabel[1] }}"
                                                title="{{ $vLabel[0] }}{!! !empty($j->validasi_catatan) ? ' · ' . e($j->validasi_catatan) : '' !!}{!! !empty($j->validasi_pada) ? ' · ' . e((string) $j->validasi_pada) : '' !!}">
                                                <i class="bi {{ $vLabel[2] }}"></i> {{ $vLabel[0] }}
                                            </span>
                                        @endif
                                        @if (!empty($j->model_belajar))
                                            <span class="rounded bg-purple-50 px-1.5 py-0.5 text-[9px] font-bold text-purple-700">{{ $j->model_belajar }}</span>
                                        @endif
                                        @if (!empty($j->jenis_asesmen))
                                            <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700">{{ $j->jenis_asesmen }}</span>
                                        @endif
                                        @if (!empty($j->catatan_khusus))
                                            <span class="rounded bg-amber-50 px-1.5 py-0.5 text-[9px] font-bold text-amber-700" title="Ada catatan khusus">
                                                <i class="bi bi-sticky"></i> Catatan
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($adaJamMasuk)
                                        <span class="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">
                                            <i class="bi bi-check-circle-fill text-[8px]"></i> Hadir ({{ $jamLabel }})
                                        </span>
                                    @elseif (in_array($statusMasuk, ['IJIN', 'SAKIT'], true))
                                        <span class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">
                                            <i class="bi bi-clock-fill text-[8px]"></i> {{ $statusMasuk === 'IJIN' ? 'Izin' : 'Sakit' }}
                                        </span>
                                    @elseif ($statusMasuk === 'DINAS_APPROVED')
                                        <span class="inline-flex items-center gap-1 rounded-md bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-sky-800">
                                            <i class="bi bi-briefcase-fill text-[8px]"></i> Dinas
                                        </span>
                                    @elseif ($statusMasuk === 'ALPHA')
                                        <span class="inline-flex items-center gap-1 rounded-md bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-800">
                                            <i class="bi bi-x-circle-fill text-[8px]"></i> Alpa
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-md bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-800">
                                            <i class="bi bi-x-circle-fill text-[8px]"></i> Belum Absen
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ url('admin/jurnal/detail/' . $j->id_jurnal) }}" target="_blank"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-xs text-blue-600 no-underline transition-colors hover:bg-blue-100"
                                        title="Baca Detail Jurnal">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                </x-ui.table>

                <div class="px-5 py-10 text-center text-xs text-slate-400" x-show="empty" x-cloak>
                    <i class="bi bi-search me-1"></i> Tidak ada jurnal yang cocok dengan pencarian.
                </div>
            </div>
        @endif
    </x-ui.card>

</div>
@endsection
