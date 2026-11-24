@extends('layouts.main')

@section('content')
@php
    // Normalisasi URL foto absensi: kolom menyimpan path relatif "uploads/absen/xxx.jpg".
    $fotoUrl = function ($v) {
        $s = trim((string) $v);

        if ($s === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $s) || strpos($s, '/') === 0) {
            return $s;
        }

        if (strpos($s, 'uploads/') === false) {
            $s = 'uploads/absen/' . ltrim($s, '/');
        }

        $ver = file_exists(public_path() . '/' . $s) ? filemtime(public_path() . '/' . $s) : time();

        return url($s) . '?v=' . $ver;
    };

    $namaBulanId = ['','Januari','Februari','Maret','April','Mei','Juni',
                    'Juli','Agustus','September','Oktober','November','Desember'];
    $labelBulanIni = $namaBulanId[(int) date('n')] . ' ' . date('Y');
    $avatarFallback = \App\Services\AvatarService::cadangan();

    $barisPending = [];
    foreach ($pending as $p) {
        $nama = $p->nama_guru ?? $p->username ?? 'Guru';
        $fotoAbsen = $fotoUrl($p->foto_masuk ?? null) ?: $fotoUrl($p->foto_pulang ?? null);
        $lat = !empty($p->lat_masuk) ? (float) $p->lat_masuk : null;
        $lng = !empty($p->lng_masuk) ? (float) $p->lng_masuk : null;

        $barisPending[] = [
            'id' => (int) $p->id,
            'cari' => mb_strtolower($nama . ' ' . ($p->nip ?? '') . ' ' . (!empty($p->tgl) ? date('d-m-Y', strtotime($p->tgl)) : '') . ' ' . ($p->device_info ?? '')),
            'nama' => $nama,
            'nip' => (string) ($p->nip ?? ''),
            'avatar' => \App\Services\AvatarService::tampilan((string) ($p->foto ?? ''), (string) $nama, '#0284c7'),
            'avatarFallback' => $avatarFallback,
            'tanggal' => !empty($p->tgl) ? date('d M Y', strtotime($p->tgl)) : '—',
            'jamMasuk' => !empty($p->jam_masuk) ? date('H:i', strtotime($p->jam_masuk)) : null,
            'jamPulang' => !empty($p->jam_pulang) ? date('H:i', strtotime($p->jam_pulang)) : null,
            'lat' => $lat,
            'lng' => $lng,
            'koordinat' => ($lat !== null && $lng !== null) ? round($lat, 5) . ', ' . round($lng, 5) : null,
            'maps' => ($lat !== null && $lng !== null) ? 'https://maps.google.com/?q=' . $lat . ',' . $lng : null,
            'akurasi' => !empty($p->gps_accuracy) ? (int) round($p->gps_accuracy) : null,
            'mock' => !empty($p->is_mock_location) && (int) $p->is_mock_location === 1,
            'keterangan' => trim((string) ($p->keterangan ?? '')),
            'device' => trim((string) ($p->device_info ?? '')),
            'ip' => trim((string) ($p->ip_address ?? '')),
            'foto' => $fotoAbsen,
        ];
    }

    $barisRiwayat = [];
    foreach ($history ?? [] as $h) {
        $nama = $h->nama_guru ?? $h->username ?? 'Guru';
        $fotoAbsen = $fotoUrl($h->foto_masuk ?? null) ?: $fotoUrl($h->foto_pulang ?? null);
        $disetujui = in_array($h->status_masuk, ['DINAS_APPROVED', 'DINAS'], true);
        $lat = !empty($h->lat_masuk) ? (float) $h->lat_masuk : null;
        $lng = !empty($h->lng_masuk) ? (float) $h->lng_masuk : null;

        $barisRiwayat[] = [
            'id' => (int) $h->id,
            'cari' => mb_strtolower($nama . ' ' . ($h->nip ?? '') . ' ' . (!empty($h->tgl) ? date('d-m-Y', strtotime($h->tgl)) : '')),
            'nama' => $nama,
            'nip' => (string) ($h->nip ?? ''),
            'avatar' => \App\Services\AvatarService::tampilan((string) ($h->foto ?? ''), (string) $nama, '#64748b'),
            'avatarFallback' => $avatarFallback,
            'tanggal' => !empty($h->tgl) ? date('d M Y', strtotime($h->tgl)) : '—',
            'jamMasuk' => !empty($h->jam_masuk) ? date('H:i', strtotime($h->jam_masuk)) : null,
            'jamPulang' => !empty($h->jam_pulang) ? date('H:i', strtotime($h->jam_pulang)) : null,
            'koordinat' => ($lat !== null && $lng !== null) ? round($lat, 5) . ', ' . round($lng, 5) : null,
            'maps' => ($lat !== null && $lng !== null) ? 'https://maps.google.com/?q=' . $lat . ',' . $lng : null,
            'status' => $disetujui
                ? ['kode' => 'approved', 'label' => 'Disetujui', 'ikon' => 'bi-check-circle-fill', 'kelas' => 'bg-emerald-50 text-emerald-700']
                : ['kode' => 'rejected', 'label' => 'Ditolak', 'ikon' => 'bi-x-circle-fill', 'kelas' => 'bg-rose-50 text-rose-700'],
            'diproses' => !empty($h->updated_at) ? date('d/m/Y H:i', strtotime($h->updated_at)) : '—',
            'foto' => $fotoAbsen,
        ];
    }

    // Kelas Tailwind ditulis utuh (bukan dirakit) agar ikut ter-scan saat build.
    $kpi = [
        [
            'label' => 'Menunggu (Pending)', 'kunci' => 'pending', 'nilai' => $stats['pending'] ?? 0,
            'ikon' => 'bi-hourglass-split', 'ket' => 'Permintaan dinas luar baru', 'catatan' => 'Perlu Respon',
            'aksen' => 'border-l-amber-500', 'ikonKelas' => 'bg-amber-50 text-amber-600',
            'badgeKelas' => 'bg-amber-50 text-amber-700',
            'sorot' => ($stats['pending'] ?? 0) > 0 ? 'ring-1 ring-amber-200' : '',
        ],
        [
            'label' => 'Disetujui (Bulan Ini)', 'kunci' => 'approved', 'nilai' => $stats['approved'] ?? 0,
            'ikon' => 'bi-check2-circle', 'ket' => 'Poin kehadiran diberikan', 'catatan' => 'Tervalidasi',
            'aksen' => 'border-l-emerald-500', 'ikonKelas' => 'bg-emerald-50 text-emerald-600',
            'badgeKelas' => 'bg-emerald-50 text-emerald-700',
            'sorot' => '',
        ],
        [
            'label' => 'Ditolak (Bulan Ini)', 'kunci' => 'rejected', 'nilai' => $stats['rejected'] ?? 0,
            'ikon' => 'bi-x-circle', 'ket' => 'Dianggap tidak sah / ditolak', 'catatan' => 'Ditolak',
            'aksen' => 'border-l-rose-500', 'ikonKelas' => 'bg-rose-50 text-rose-600',
            'badgeKelas' => 'bg-rose-50 text-rose-700',
            'sorot' => '',
        ],
        [
            'label' => 'Total Pengajuan', 'kunci' => 'total', 'nilai' => $stats['total'] ?? 0,
            'ikon' => 'bi-collection', 'ket' => 'Antrean + diproses bulan ini', 'catatan' => $labelBulanIni,
            'aksen' => 'border-l-sky-500', 'ikonKelas' => 'bg-sky-50 text-sky-600',
            'badgeKelas' => 'bg-sky-50 text-sky-700',
            'sorot' => '',
        ],
    ];

    $config = [
        'pending' => $barisPending,
        'riwayat' => $barisRiwayat,
        'urls' => [
            'approve' => url('admin/presensi/approve-dinas'),
            'reject' => url('admin/presensi/reject-dinas'),
        ],
        'csrf' => csrf_token(),
    ];
@endphp

<div class="space-y-4" x-data="pendingDinas(@js($config))">

    <x-ui.page-header title="{{ $judul ?? 'Verifikasi Dinas Luar' }}" icon="bi-briefcase-fill" tone="blue"
        subtitle="Tinjau koordinat GPS, foto kehadiran, lalu setujui permintaan dinas luar guru."
        :crumbs="['Presensi', 'Dinas Luar']">
        <x-slot:actions>
            <a href="{{ url('admin/presensi/izin') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-file-earmark-medical text-blue-600"></i> Verifikasi Izin/Sakit
            </a>
            <a href="{{ url('admin/presensi') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-speedometer2"></i> Monitoring Presensi
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- KPI ringkas --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($kpi as $item)
            <x-ui.card body-class="p-4" class="border-l-4 {{ $item['aksen'] }} {{ $item['sorot'] }}">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-xl {{ $item['ikonKelas'] }}">
                        <i class="bi {{ $item['ikon'] }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $item['label'] }}</p>
                        <div class="mt-0.5 flex items-baseline gap-2">
                            <p class="text-2xl font-extrabold tabular-nums text-slate-900">{{ number_format($item['nilai']) }}</p>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $item['badgeKelas'] }}">{{ $item['catatan'] }}</span>
                        </div>
                        <p class="mt-0.5 text-[11px] text-slate-400">{{ $item['ket'] }}</p>
                    </div>
                </div>
            </x-ui.card>
        @endforeach
    </div>

    <x-ui.card body-class="p-0">
        <x-slot:toolbar>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="inline-flex items-center gap-1 rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Tab verifikasi dinas">
                    <button type="button" role="tab" :aria-selected="tab === 'pending' ? 'true' : 'false'"
                        @click="tab = 'pending'"
                        :class="tab === 'pending' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-500 hover:text-slate-700'"
                        class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-xs font-bold transition-colors">
                        <i class="bi bi-hourglass-split"></i> Menunggu Verifikasi
                        <span class="rounded-full px-1.5 py-0.5 text-[10px] font-extrabold tabular-nums"
                            :class="tab === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-500'"
                            x-text="semuaPending.length"></span>
                    </button>
                    <button type="button" role="tab" :aria-selected="tab === 'history' ? 'true' : 'false'"
                        @click="tab = 'history'"
                        :class="tab === 'history' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-500 hover:text-slate-700'"
                        class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-xs font-bold transition-colors">
                        <i class="bi bi-clock-history"></i> Riwayat
                        <span class="rounded-full px-1.5 py-0.5 text-[10px] font-extrabold tabular-nums"
                            :class="tab === 'history' ? 'bg-slate-100 text-slate-600' : 'bg-slate-200 text-slate-500'"
                            x-text="semuaRiwayat.length"></span>
                    </button>
                </div>

                <div class="relative w-full sm:ml-auto sm:w-64">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                    <input type="text" x-model="cari" x-ref="cari" autocomplete="off"
                        placeholder="Cari guru, NIP, tanggal..."
                        aria-label="Cari pengajuan dinas"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-8 text-xs font-semibold text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    <button type="button" x-show="cari !== ''" x-cloak @click="cari = ''; $refs.cari.focus()"
                        class="absolute right-2 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-200/70 hover:text-slate-700"
                        title="Bersihkan pencarian">
                        <i class="bi bi-x text-xs"></i>
                    </button>
                </div>
            </div>
        </x-slot:toolbar>

        {{-- ══════════ TAB PENDING ══════════ --}}
        <div x-show="tab === 'pending'" class="max-h-[62vh] overflow-auto" tabindex="0">
            <table class="w-full text-[13px]">
                <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="w-10 px-3 py-3 text-center font-bold">#</th>
                        <th class="min-w-[220px] px-3 py-3 text-left font-bold">Guru / Pegawai</th>
                        <th class="min-w-[150px] px-3 py-3 text-left font-bold">Tanggal &amp; Waktu</th>
                        <th class="min-w-[220px] px-3 py-3 text-left font-bold">Koordinat &amp; Lokasi</th>
                        <th class="min-w-[170px] px-3 py-3 text-left font-bold">Device &amp; Jaringan</th>
                        <th class="w-20 px-3 py-3 text-center font-bold">Foto</th>
                        <th class="min-w-[190px] px-3 py-3 text-center font-bold">Aksi Verifikasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr x-show="barisPending.length === 0">
                        <td colspan="7" class="px-3 py-12 text-center">
                            <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-2xl text-emerald-500">
                                <i class="bi bi-check-circle"></i>
                            </span>
                            <p class="text-sm font-bold text-slate-700"
                                x-text="semuaPending.length === 0 ? 'Semua Dinas Telah Diverifikasi' : 'Tidak ada pengajuan yang cocok'"></p>
                            <p class="mt-1 text-xs text-slate-400"
                                x-text="semuaPending.length === 0 ? 'Saat ini tidak ada permintaan presensi dinas luar yang menunggu persetujuan.' : 'Coba ubah kata kunci pencarian.'"></p>
                            <button type="button" x-show="semuaPending.length > 0" @click="cari = ''"
                                class="mt-3 inline-flex items-center gap-1.5 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition-colors hover:bg-blue-100">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset pencarian
                            </button>
                        </td>
                    </tr>

                    <template x-for="(row, index) in barisPending" :key="row.id">
                        <tr class="transition-colors hover:bg-slate-50/60">
                            <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400" x-text="index + 1"></td>

                            <td class="px-3 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <img :src="row.avatar" :alt="row.nama" loading="lazy"
                                        x-on:error="$el.src = row.avatarFallback"
                                        class="h-9 w-9 shrink-0 rounded-xl bg-slate-100 object-cover ring-1 ring-slate-200">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold leading-tight text-slate-800" :title="row.nama" x-text="row.nama"></p>
                                        <p class="mt-0.5 flex items-center gap-1.5 text-[11px] text-slate-400">
                                            <span class="truncate">NIP: <span x-text="row.nip || '—'"></span></span>
                                            <span class="text-slate-300">•</span>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                                                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"></span> Pending
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-2.5">
                                <p class="flex items-center gap-1.5 text-xs font-semibold text-slate-700">
                                    <i class="bi bi-calendar-check text-blue-500"></i>
                                    <span x-text="row.tanggal"></span>
                                </p>
                                <p class="mt-1 flex items-center gap-1.5 font-mono text-[11px] tabular-nums text-slate-500">
                                    <i class="bi bi-clock text-slate-400"></i>
                                    <span>Masuk: <strong class="text-slate-700" x-text="row.jamMasuk || '—'"></strong></span>
                                    <template x-if="row.jamPulang">
                                        <span class="text-slate-300">|</span>
                                    </template>
                                    <template x-if="row.jamPulang">
                                        <span>Plg: <strong class="text-slate-700" x-text="row.jamPulang"></strong></span>
                                    </template>
                                </p>
                            </td>

                            <td class="px-3 py-2.5">
                                <template x-if="row.koordinat">
                                    <div class="flex items-center gap-1.5">
                                        <i class="bi bi-geo-alt-fill text-rose-500"></i>
                                        <a :href="row.maps" target="_blank" rel="noopener"
                                            class="font-mono text-[11px] font-semibold text-blue-600 no-underline underline decoration-blue-200 hover:text-blue-800"
                                            :title="'Buka ' + row.koordinat + ' di Google Maps'"
                                            x-text="row.koordinat"></a>
                                    </div>
                                </template>
                                <span x-show="!row.koordinat" class="text-xs text-slate-400">Koordinat tidak tersedia</span>

                                <div class="mt-1 flex flex-wrap items-center gap-1">
                                    <span x-show="row.akurasi !== null"
                                        class="rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-bold text-sky-700">
                                        Akurasi ±<span x-text="row.akurasi"></span>m
                                    </span>
                                    <span x-show="row.mock" title="Terdeteksi fake GPS / mock location"
                                        class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700">
                                        <i class="bi bi-exclamation-triangle-fill"></i> Fake GPS
                                    </span>
                                </div>
                                <p x-show="row.keterangan" class="mt-1 max-w-[220px] truncate text-[11px] text-slate-500"
                                    :title="row.keterangan">
                                    Ket: <span x-text="row.keterangan"></span>
                                </p>
                            </td>

                            <td class="px-3 py-2.5">
                                <p x-show="row.device" class="flex max-w-[180px] items-center gap-1.5 text-xs text-slate-600" :title="row.device">
                                    <i class="bi bi-phone shrink-0 text-slate-400"></i>
                                    <span class="truncate" x-text="row.device"></span>
                                </p>
                                <span x-show="!row.device" class="text-xs text-slate-400">—</span>
                                <p x-show="row.ip" class="mt-0.5 font-mono text-[11px] text-slate-400">IP: <span x-text="row.ip"></span></p>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <template x-if="row.foto">
                                    <button type="button" @click="bukaFoto(row)"
                                        class="group relative inline-flex items-center justify-center rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                                        title="Lihat foto kehadiran">
                                        <img :src="row.foto" alt="Foto absen" loading="lazy"
                                            x-on:error="$el.classList.add('hidden'); $el.nextElementSibling.classList.remove('hidden'); $el.nextElementSibling.classList.add('flex')"
                                            class="h-10 w-10 rounded-lg border border-slate-200 object-cover shadow-xs transition-transform group-hover:scale-105">
                                        <span class="hidden h-10 w-10 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-slate-400">
                                            <i class="bi bi-image text-xs"></i>
                                        </span>
                                    </button>
                                </template>
                                <span x-show="!row.foto" class="text-xs text-slate-300" title="Guru tidak melampirkan foto">—</span>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" @click="setujui(row)" :disabled="memproses"
                                        class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg bg-emerald-600 px-2.5 py-1.5 text-[11px] font-bold text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                                        title="Setujui dinas luar">
                                        <i class="bi" :class="idProses === row.id && aksi === 'setuju' ? 'bi-arrow-repeat animate-spin' : 'bi-check-lg'"></i>
                                        Setujui
                                    </button>
                                    <button type="button" @click="bukaTolak(row)" :disabled="memproses"
                                        class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg border border-rose-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-rose-600 transition-colors hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        title="Tolak dinas luar">
                                        <i class="bi bi-x-lg"></i> Tolak
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- ══════════ TAB RIWAYAT ══════════ --}}
        <div x-show="tab === 'history'" x-cloak class="max-h-[62vh] overflow-auto" tabindex="0">
            <table class="w-full text-[13px]">
                <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="w-10 px-3 py-3 text-center font-bold">#</th>
                        <th class="min-w-[220px] px-3 py-3 text-left font-bold">Guru / Pegawai</th>
                        <th class="min-w-[150px] px-3 py-3 text-left font-bold">Tanggal &amp; Waktu</th>
                        <th class="min-w-[200px] px-3 py-3 text-left font-bold">Koordinat &amp; Lokasi</th>
                        <th class="w-[120px] px-3 py-3 text-center font-bold">Status</th>
                        <th class="w-20 px-3 py-3 text-center font-bold">Foto</th>
                        <th class="w-[140px] px-3 py-3 text-right font-bold">Diproses Pada</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr x-show="barisRiwayat.length === 0">
                        <td colspan="7" class="px-3 py-12 text-center">
                            <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl text-slate-400">
                                <i class="bi bi-inbox"></i>
                            </span>
                            <p class="text-sm font-bold text-slate-700"
                                x-text="semuaRiwayat.length === 0 ? 'Belum Ada Riwayat' : 'Tidak ada riwayat yang cocok'"></p>
                            <p class="mt-1 text-xs text-slate-400"
                                x-text="semuaRiwayat.length === 0 ? 'Riwayat verifikasi dinas luar yang telah diproses akan tampil di sini.' : 'Coba ubah kata kunci pencarian.'"></p>
                        </td>
                    </tr>

                    <template x-for="(row, index) in barisRiwayat" :key="row.id">
                        <tr class="transition-colors hover:bg-slate-50/60">
                            <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400" x-text="index + 1"></td>

                            <td class="px-3 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <img :src="row.avatar" :alt="row.nama" loading="lazy"
                                        x-on:error="$el.src = row.avatarFallback"
                                        class="h-9 w-9 shrink-0 rounded-xl bg-slate-100 object-cover ring-1 ring-slate-200">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold leading-tight text-slate-800" :title="row.nama" x-text="row.nama"></p>
                                        <p class="mt-0.5 truncate text-[11px] text-slate-400">NIP: <span x-text="row.nip || '—'"></span></p>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-2.5">
                                <p class="flex items-center gap-1.5 text-xs font-semibold text-slate-700">
                                    <i class="bi bi-calendar-check text-slate-400"></i>
                                    <span x-text="row.tanggal"></span>
                                </p>
                                <p class="mt-1 flex items-center gap-1.5 font-mono text-[11px] tabular-nums text-slate-500">
                                    <span>Masuk: <span x-text="row.jamMasuk || '—'"></span></span>
                                    <template x-if="row.jamPulang">
                                        <span class="text-slate-300">|</span>
                                    </template>
                                    <template x-if="row.jamPulang">
                                        <span>Plg: <span x-text="row.jamPulang"></span></span>
                                    </template>
                                </p>
                            </td>

                            <td class="px-3 py-2.5">
                                <template x-if="row.koordinat">
                                    <a :href="row.maps" target="_blank" rel="noopener"
                                        class="inline-flex items-center gap-1 font-mono text-[11px] font-semibold text-blue-600 no-underline hover:text-blue-800">
                                        <i class="bi bi-geo-alt-fill text-rose-500"></i>
                                        <span x-text="row.koordinat"></span>
                                    </a>
                                </template>
                                <span x-show="!row.koordinat" class="text-xs text-slate-400">—</span>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold" :class="row.status.kelas">
                                    <i class="bi text-[10px]" :class="row.status.ikon"></i>
                                    <span x-text="row.status.label"></span>
                                </span>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <template x-if="row.foto">
                                    <button type="button" @click="bukaFoto(row)"
                                        class="group relative inline-flex items-center justify-center rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                                        title="Lihat foto kehadiran">
                                        <img :src="row.foto" alt="Foto absen" loading="lazy"
                                            x-on:error="$el.classList.add('hidden'); $el.nextElementSibling.classList.remove('hidden'); $el.nextElementSibling.classList.add('flex')"
                                            class="h-10 w-10 rounded-lg border border-slate-200 object-cover shadow-xs transition-transform group-hover:scale-105">
                                        <span class="hidden h-10 w-10 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-slate-400">
                                            <i class="bi bi-image text-xs"></i>
                                        </span>
                                    </button>
                                </template>
                                <span x-show="!row.foto" class="text-xs text-slate-300">—</span>
                            </td>

                            <td class="whitespace-nowrap px-3 py-2.5 text-right font-mono text-[11px] tabular-nums text-slate-400" x-text="row.diproses"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-400">
                <span x-text="tab === 'pending'
                    ? 'Menampilkan ' + barisPending.length + ' dari ' + semuaPending.length + ' pengajuan'
                    : 'Menampilkan ' + barisRiwayat.length + ' dari ' + semuaRiwayat.length + ' riwayat'"></span>
                <span>Periode: {{ $labelBulanIni }}</span>
            </div>
        </x-slot:footer>
    </x-ui.card>
</div>

{{-- ══════════ MODAL: FOTO KEHADIRAN DINAS ══════════ --}}
<x-ui.modal id="fotoDinasModal" title="Foto Kehadiran Dinas" icon="bi-camera" tone="blue" size="max-w-lg">
    <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50/50 p-5 text-center">
        <p class="mb-3 text-xs text-slate-500">
            Dinas luar: <span class="font-bold text-slate-700" x-text="foto.nama || '—'"></span>
        </p>

        <template x-if="foto.url && !foto.gagal">
            <img :src="foto.url" alt="Foto kehadiran dinas" x-on:error="foto.gagal = true"
                class="mx-auto max-h-96 w-auto rounded-xl border border-slate-200 bg-white object-contain shadow-xs">
        </template>

        <div x-show="!foto.url || foto.gagal" x-cloak
            class="mx-auto flex h-52 max-w-xs flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-300 bg-white px-4 text-center">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-400">
                <i class="bi bi-image"></i>
            </span>
            <p class="text-xs font-bold text-slate-600">Foto tidak tersedia</p>
            <p class="text-[10px] leading-relaxed text-slate-400">Berkas foto mungkin sudah dihapus atau gagal dimuat dari server.</p>
        </div>
    </div>

    <div class="flex shrink-0 items-center justify-between gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
        <a :href="foto.url || '#'" :class="foto.url ? '' : 'pointer-events-none opacity-50'"
            target="_blank" rel="noopener"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-100">
            <i class="bi bi-box-arrow-up-right"></i> Buka Gambar Penuh
        </a>
        <button type="button" data-ui-dismiss="modal"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100">
            Tutup
        </button>
    </div>
</x-ui.modal>

{{-- ══════════ MODAL: TOLAK DINAS LUAR ══════════ --}}
<x-ui.modal id="tolakDinasModal" title="Tolak Dinas Luar" icon="bi-x-circle" tone="rose" size="max-w-lg">
    <form x-ref="tolakForm" @submit.prevent="kirimTolak()">
        <div class="space-y-4 p-5">
            <div class="flex gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3.5 py-2.5 text-xs text-blue-800">
                <i class="bi bi-info-circle-fill mt-0.5 shrink-0"></i>
                <p class="leading-relaxed">
                    Pengajuan milik <strong x-text="tolak.nama || '—'"></strong> akan ditolak dan poinnya direset ke 0.
                </p>
            </div>

            <div>
                <label for="tolakAlasan" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                    Alasan Penolakan <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="tolakAlasan" x-model="tolak.alasan" x-ref="alasan"
                    minlength="5" maxlength="500" required autocomplete="off"
                    placeholder="Contoh: Surat tugas tidak dilampirkan / lokasi tidak sesuai"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20">
                <p class="mt-1 text-[11px] text-slate-400">Wajib diisi 5–500 karakter agar guru memahami dasar penolakan.</p>
            </div>
        </div>

        <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
            <button type="button" data-ui-dismiss="modal"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100">
                Batal
            </button>
            <button type="submit" :disabled="memproses"
                class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50">
                <i class="bi" :class="memproses ? 'bi-arrow-repeat animate-spin' : 'bi-x-lg'"></i>
                Konfirmasi Tolak
            </button>
        </div>
    </form>
</x-ui.modal>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pendingDinas', (config) => ({
        tab: 'pending',
        cari: '',
        memproses: false,
        idProses: null,
        aksi: '',
        semuaPending: config.pending || [],
        semuaRiwayat: config.riwayat || [],
        csrf: config.csrf,
        urls: config.urls,
        foto: { url: '', nama: '', gagal: false },
        tolak: { id: null, nama: '', alasan: '' },

        cocok(row) {
            const kunci = this.cari.trim().toLowerCase();

            return kunci === '' || (row.cari || '').includes(kunci);
        },

        get barisPending() {
            return this.semuaPending.filter((row) => this.cocok(row));
        },

        get barisRiwayat() {
            return this.semuaRiwayat.filter((row) => this.cocok(row));
        },

        buka(id) {
            this.$store.modals.open('#' + id);
        },

        tutup(id) {
            this.$store.modals.close('#' + id);
        },

        bukaFoto(row) {
            this.foto = { url: row.foto || '', nama: row.nama, gagal: false };
            this.buka('fotoDinasModal');
        },

        bukaTolak(row) {
            this.tolak = { id: row.id, nama: row.nama, alasan: '' };
            this.buka('tolakDinasModal');
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
                    status: respon.status,
                    pesan: data.message || 'Terjadi kesalahan pada server',
                };
            } catch (galat) {
                return { ok: false, status: 0, pesan: 'Gagal menghubungi server' };
            }
        },

        konfirmasi(judul, teks, label, warna) {
            return Swal.fire({
                title: judul,
                text: teks,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: warna || '#2563eb',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: label || 'Ya',
                cancelButtonText: 'Batal',
            }).then((hasil) => hasil.isConfirmed);
        },

        berhasil(judul, pesan) {
            Swal.fire({ icon: 'success', title: judul, text: pesan, timer: 1500, showConfirmButton: false })
                .then(() => location.reload());
        },

        async setujui(row) {
            if (this.memproses) {
                return;
            }

            const lanjut = await this.konfirmasi(
                'Setujui Dinas Luar?',
                'Absensi ' + row.nama + ' akan diverifikasi sah sebagai Dinas Luar dan poin kehadiran diberikan.',
                'Ya, Setujui',
                '#059669',
            );

            if (!lanjut) {
                return;
            }

            this.memproses = true;
            this.idProses = row.id;
            this.aksi = 'setuju';

            try {
                const hasil = await this.kirim(this.urls.approve + '/' + encodeURIComponent(row.id), {});

                if (!hasil.ok) {
                    Swal.fire('Gagal Menyetujui', hasil.pesan, 'error');

                    return;
                }

                this.berhasil('Berhasil!', hasil.pesan);
            } finally {
                this.memproses = false;
                this.idProses = null;
                this.aksi = '';
            }
        },

        async kirimTolak() {
            if (this.memproses) {
                return;
            }

            const alasan = this.tolak.alasan.trim();

            if (alasan.length < 5) {
                Swal.fire('Perhatian', 'Alasan penolakan wajib diisi minimal 5 karakter', 'warning');

                return;
            }

            this.memproses = true;

            try {
                const hasil = await this.kirim(this.urls.reject + '/' + encodeURIComponent(this.tolak.id), { alasan });

                if (!hasil.ok) {
                    Swal.fire('Gagal Memproses', hasil.pesan, 'error');

                    return;
                }

                this.tutup('tolakDinasModal');
                this.berhasil('Dinas Ditolak', hasil.pesan);
            } finally {
                this.memproses = false;
            }
        },
    }));
});
</script>
@endsection
