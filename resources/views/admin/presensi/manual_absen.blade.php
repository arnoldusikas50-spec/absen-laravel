@extends('layouts.main')

@section('content')
@php
    // Default jam datang/pulang diambil dari jadwal kerja hari ini, bukan angka
    // tetap 07:30/15:00: guru shift sore yang diabsen manual dengan default ini
    // langsung tercatat "telat" ratusan menit.
    $jadwalHariIni = ($jadwalKerja ?? [])[(int) date('N')] ?? null;
    $jamHarian = static function ($nilai, $fallback) {
        $n = substr(trim((string) ($nilai ?? '')), 0, 5);
        return ($n === '' || $n === '00:00') ? $fallback : $n;
    };
    $jamMasukDefault  = $jamHarian($jadwalHariIni->jam_masuk ?? null, '07:30');
    $jamPulangDefault = $jamHarian($jadwalHariIni->jam_pulang ?? null, '15:00');

    $namaLengkap = static function ($g) {
        $nama = trim((string) ($g->nama_guru ?? ''));
        if ($nama === '') {
            $nama = trim(trim((string) ($g->first_name ?? '')) . ' ' . trim((string) ($g->last_name ?? '')));
        }
        return $nama !== '' ? $nama : 'Guru #' . $g->id;
    };

    $guruOpsi = [];
    $batchAwal = [];

    foreach ($guru as $g) {
        $nama = $namaLengkap($g);
        $nip  = trim((string) ($g->nip ?? ''));

        $guruOpsi[] = [
            'id'    => (int) $g->id,
            'nama'  => $nama,
            'nip'   => $nip,
            'label' => $nama . (($nip !== '' && $nip !== '-') ? ' — NIP: ' . $nip : ''),
        ];

        $att      = $todayAtt[$g->id] ?? null;
        $statusM  = strtoupper((string) ($att->status_masuk ?? ''));
        $jamM     = ($att && $att->jam_masuk) ? date('H:i', strtotime($att->jam_masuk)) : '';
        $jamP     = ($att && $att->jam_pulang) ? date('H:i', strtotime($att->jam_pulang)) : '';
        $isDinas  = strpos($statusM, 'DINAS') === 0;
        $isIzin   = $isDinas || in_array($statusM, ['IJIN', 'IZIN', 'SAKIT', 'CUTI'], true);

        $label    = 'Belum Absen';
        $badge    = 'bg-slate-100 text-slate-500';
        $kategori = 'belum';
        $statusPilih = 'HADIR';

        if ($att && $jamM !== '' && $jamP !== '') {
            $label    = 'Lengkap';
            $badge    = 'bg-emerald-100 text-emerald-700';
            $kategori = 'lengkap';
        } elseif ($att && $isIzin) {
            $label       = $isDinas ? 'DINAS' : $statusM;
            $badge       = 'bg-sky-100 text-sky-700';
            $kategori    = 'izin';
            $statusPilih = $isDinas ? 'DINAS' : $statusM;
        } elseif ($att && $jamM !== '') {
            $label    = 'Masuk Saja';
            $badge    = 'bg-amber-100 text-amber-700';
            $kategori = 'masuk';
        }

        $batchAwal[] = [
            'id'         => (int) $g->id,
            'nama'       => $nama,
            'nip'        => $nip,
            'initial'    => mb_strtoupper(mb_substr($nama, 0, 1)),
            'status'     => $statusPilih,
            'jamMasuk'   => $jamM,
            'jamPulang'  => $jamP,
            'keterangan' => (string) ($att->keterangan ?? ''),
            'kategori'   => $kategori,
            'label'      => $label,
            'badge'      => $badge,
            'edited'     => false,
        ];
    }

    $statusOpsi = [
        ['key' => 'HADIR', 'label' => 'Hadir', 'icon' => 'bi-check-circle', 'aktif' => 'peer-checked:border-emerald-400 peer-checked:bg-emerald-50 peer-checked:text-emerald-700'],
        ['key' => 'IJIN',  'label' => 'Izin',  'icon' => 'bi-file-text',    'aktif' => 'peer-checked:border-sky-400 peer-checked:bg-sky-50 peer-checked:text-sky-700'],
        ['key' => 'SAKIT', 'label' => 'Sakit', 'icon' => 'bi-heart-pulse',  'aktif' => 'peer-checked:border-amber-400 peer-checked:bg-amber-50 peer-checked:text-amber-700'],
        ['key' => 'CUTI',  'label' => 'Cuti',  'icon' => 'bi-calendar-check', 'aktif' => 'peer-checked:border-violet-400 peer-checked:bg-violet-50 peer-checked:text-violet-700'],
        ['key' => 'DINAS', 'label' => 'Dinas', 'icon' => 'bi-briefcase',    'aktif' => 'peer-checked:border-blue-400 peer-checked:bg-blue-50 peer-checked:text-blue-700'],
    ];
@endphp

<div class="space-y-4"
    x-data="manualAbsenPage({
        csrf: @js(csrf_token()),
        primary: @js(\App\Libraries\Theme::metaColor('primary')),
        today: @js(date('Y-m-d')),
        jamMasukDefault: @js($jamMasukDefault),
        jamPulangDefault: @js($jamPulangDefault),
        holidays: @js($holidays ?? []),
        jadwal: @js($jadwalKerja ?? []),
        liburMingguan: @js($hariLiburMingguan ?? ['Sun']),
        guru: @js($guruOpsi),
        batchAwal: @js($batchAwal),
        urls: @js([
            'status'      => url('admin/presensi/attendance-status'),
            'batchStatus' => url('admin/presensi/batch-attendance-status'),
            'checkin'     => url('admin/presensi/manual-checkin'),
            'checkout'    => url('admin/presensi/manual-checkout'),
            'hapus'       => url('admin/presensi/delete-attendance'),
            'batch'       => url('admin/presensi/batch-manual'),
        ]),
    })">

    <x-ui.page-header
        title="Input Kehadiran Guru"
        icon="bi-pencil-square"
        tone="blue"
        subtitle="Catat kehadiran guru secara manual oleh admin / panitia, per guru maupun massal."
        :crumbs="['Presensi', 'Absen Manual']">
        <x-slot:actions>
            <a href="{{ url('admin/presensi') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-100">
                <i class="bi bi-speedometer2"></i> Monitoring
            </a>
            <a href="{{ url('admin/presensi/rekap') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-xs font-bold text-violet-700 no-underline transition-colors hover:bg-violet-100">
                <i class="bi bi-table"></i> Rekap
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Tanggal + mode --}}
    <x-ui.card body-class="p-5 space-y-3">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:flex-wrap">
            <div class="grid grid-cols-2 gap-2 md:flex md:items-center">
                <input type="date" x-model="tanggal" :max="today" aria-label="Tanggal absensi"
                    class="col-span-2 min-h-[40px] w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-300 md:col-span-1 md:w-auto">
                <button type="button" @click="quickSetDate(-1)"
                    class="min-h-[40px] w-full rounded-lg border-[1.5px] border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 transition-colors hover:border-slate-400 hover:bg-slate-50 md:w-auto">
                    Kemarin
                </button>
                <button type="button" @click="quickSetDate(0)"
                    class="min-h-[40px] w-full rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700 md:w-auto">
                    Hari Ini
                </button>
            </div>

            <div x-show="tanggal" x-cloak class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold"
                :class="liburInfo.libur ? 'bg-amber-100 text-amber-800' : (isHariAktif ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700')">
                <i class="bi bi-calendar3"></i>
                <span x-text="tanggalLabel"></span>
            </div>

            <div class="inline-flex gap-1 rounded-xl border border-slate-200 bg-slate-100 p-1 md:ml-auto" role="tablist" aria-label="Mode pengisian">
                <button type="button" role="tab" @click="mode = 'single'"
                    :aria-selected="mode === 'single' ? 'true' : 'false'"
                    :class="mode === 'single' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-white'"
                    class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-xs font-bold transition-colors">
                    <i class="bi bi-person"></i> Per Guru
                </button>
                <button type="button" role="tab" @click="mode = 'batch'"
                    :aria-selected="mode === 'batch' ? 'true' : 'false'"
                    :class="mode === 'batch' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-white'"
                    class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-xs font-bold transition-colors">
                    <i class="bi bi-people"></i> Massal
                </button>
            </div>
        </div>

        <div x-show="isHariAktif" x-cloak class="flex items-start gap-2 rounded-xl border border-amber-300 bg-amber-50 px-3.5 py-2.5 text-xs font-medium text-amber-800" role="alert">
            <i class="bi bi-clock-history mt-0.5 shrink-0"></i>
            <span><strong>Hari ini (hari aktif):</strong> hanya boleh absen <em>Datang saja</em> atau <em>Pulang saja</em>. Datang + Pulang sekaligus hanya untuk tanggal kemarin atau sebelumnya.</span>
        </div>
        <div x-show="liburInfo.libur" x-cloak class="flex items-start gap-2 rounded-xl border border-orange-300 bg-orange-50 px-3.5 py-2.5 text-xs font-medium text-orange-800" role="alert">
            <i class="bi bi-exclamation-triangle mt-0.5 shrink-0"></i>
            <span><strong>Hari libur</strong> — <span x-text="liburInfo.alasan"></span>. Simpan per guru &amp; baris HADIR massal akan ditolak server.</span>
        </div>
    </x-ui.card>

    {{-- MODE 1: per guru --}}
    <div x-show="mode === 'single'" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-ui.card title="Form Presensi" icon="bi-pencil-square" tone="blue">
                <x-slot:actions>
                    <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold"
                        :class="chipClass(single.label)"
                        x-text="single.label || 'Pilih guru'"></span>
                </x-slot:actions>

                <div class="space-y-5">
                    {{-- Guru --}}
                    <div>
                        <label for="selectGuru" class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Guru <span class="text-red-500">*</span>
                        </label>
                        <select id="selectGuru" x-model="guruId"
                            class="min-h-[42px] w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-300">
                            <option value="">-- Ketik nama atau NIP --</option>
                            @foreach ($guruOpsi as $g)
                                <option value="{{ $g['id'] }}">{{ $g['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status --}}
                    <fieldset>
                        <legend class="mb-2 text-sm font-semibold text-slate-700">Status Kehadiran</legend>
                        <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Status kehadiran">
                            @foreach ($statusOpsi as $s)
                                <div>
                                    <input type="radio" class="peer sr-only" name="singleStatus"
                                        id="status{{ $s['key'] }}" value="{{ $s['key'] }}" x-model="status">
                                    <label for="status{{ $s['key'] }}"
                                        class="flex cursor-pointer items-center gap-1.5 rounded-xl border-[1.5px] border-slate-200 bg-white px-3.5 py-2 text-[13px] font-bold text-slate-600 transition-colors hover:border-slate-400 hover:bg-slate-50 {{ $s['aktif'] }}">
                                        <i class="bi {{ $s['icon'] }} text-sm"></i> {{ $s['label'] }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>

                    {{-- Jenis absen --}}
                    <div x-show="status === 'HADIR'" x-cloak>
                        <span class="mb-1.5 block text-sm font-semibold text-slate-700">Jenis Absen</span>
                        <div class="inline-flex gap-1 rounded-xl border border-slate-200 bg-slate-100 p-1" role="group" aria-label="Jenis absen">
                            <button type="button" @click="jenis = 'masuk'" :aria-pressed="jenis === 'masuk' ? 'true' : 'false'"
                                :class="jenis === 'masuk' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white'"
                                class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-xs font-bold transition-colors">
                                <i class="bi bi-box-arrow-in-right"></i> Datang
                            </button>
                            <button type="button" @click="jenis = 'pulang'" :aria-pressed="jenis === 'pulang' ? 'true' : 'false'"
                                :class="jenis === 'pulang' ? 'bg-orange-500 text-white shadow-sm' : 'text-slate-600 hover:bg-white'"
                                class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-xs font-bold transition-colors">
                                <i class="bi bi-box-arrow-left"></i> Pulang
                            </button>
                            <button type="button" @click="jenis = 'keduanya'" :disabled="isHariAktif"
                                :aria-pressed="jenis === 'keduanya' ? 'true' : 'false'"
                                :title="isHariAktif ? 'Tidak tersedia untuk hari aktif — pilih Datang atau Pulang' : ''"
                                :class="jenis === 'keduanya' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white'"
                                class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-xs font-bold transition-colors disabled:cursor-not-allowed disabled:opacity-40">
                                <i class="bi bi-arrow-left-right"></i> Keduanya
                            </button>
                        </div>
                        <div class="mt-2 flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600"
                            :class="isHariAktif ? 'border-amber-300 bg-amber-50 text-amber-800' : ''">
                            <i class="bi bi-info-circle mt-0.5 shrink-0"></i>
                            <span x-text="hintJenis"></span>
                        </div>
                    </div>

                    {{-- Jam --}}
                    <div x-show="status === 'HADIR'" x-cloak class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div x-show="jenis !== 'pulang'">
                                <label for="inputJamMasuk" class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    <i class="bi bi-box-arrow-in-right me-1 text-emerald-600"></i>Jam Datang
                                </label>
                                <input type="time" id="inputJamMasuk" x-model="jamMasuk"
                                    class="min-h-[42px] w-full rounded-lg border border-slate-200 px-3 py-2.5 font-mono text-base outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-300">
                            </div>
                            <div x-show="jenis !== 'masuk'">
                                <label for="inputJamPulang" class="mb-1.5 block text-sm font-semibold text-slate-700">
                                    <i class="bi bi-box-arrow-left me-1 text-orange-500"></i>Jam Pulang
                                </label>
                                <input type="time" id="inputJamPulang" x-model="jamPulang"
                                    class="min-h-[42px] w-full rounded-lg border border-slate-200 px-3 py-2.5 font-mono text-base outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-300">
                            </div>
                        </div>
                        <p x-show="jadwalAktif" x-cloak class="rounded-lg bg-slate-50 px-3 py-2 text-[11px] text-slate-500">
                            <i class="bi bi-clock me-1"></i>
                            <span x-text="jadwalAktif ? ('Jadwal: ' + jadwalAktif.jam_masuk + ' – ' + jadwalAktif.jam_pulang + ' (' + jadwalAktif.sumber + ')') : ''"></span>
                        </p>
                    </div>

                    {{-- Catatan --}}
                    <div>
                        <label for="inputKeterangan" class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Catatan <span class="text-xs font-normal text-slate-400">(opsional)</span>
                        </label>
                        <div class="relative">
                            <input type="text" id="inputKeterangan" x-model="keterangan" maxlength="500"
                                placeholder="Mis: Keperluan keluarga, sakit mendadak…"
                                class="min-h-[42px] w-full rounded-lg border border-slate-200 px-3 py-2.5 pr-16 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-300">
                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">
                                <span x-text="keterangan.length"></span>/500
                            </span>
                        </div>
                    </div>

                    {{-- Aksi --}}
                    <div class="flex items-center gap-2 border-t border-slate-100 pt-3">
                        <button type="button" x-show="single.adaData" x-cloak @click="hapusSingle()"
                            class="min-h-[42px] rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-xs font-bold text-red-600 transition-colors hover:bg-red-100"
                            title="Hapus data presensi guru ini pada tanggal yang dipilih">
                            <i class="bi bi-trash me-1"></i>Hapus Presensi
                        </button>
                        <button type="button" @click="simpanSingle()" :disabled="simpanNonaktif"
                            :title="liburInfo.libur ? 'Nonaktif: server menolak presensi di hari libur' : ''"
                            class="min-h-[44px] flex-1 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                            <i class="bi bi-floppy me-1"></i><span x-text="saving ? 'Menyimpan…' : 'Simpan Presensi'"></span>
                        </button>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <div class="lg:sticky lg:top-4 lg:self-start">
            <x-ui.card title="Status Terkini" icon="bi-person-check" tone="violet">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-violet-600 text-xl font-extrabold text-white"
                        x-text="guruAktif ? guruAktif.nama.charAt(0).toUpperCase() : '?'"></div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold leading-snug text-slate-900"
                            x-text="guruAktif ? guruAktif.nama : 'Pilih guru terlebih dulu'"></p>
                        <p class="mt-0.5 text-xs text-slate-400"
                            x-text="guruAktif && guruAktif.nip && guruAktif.nip !== '-' ? ('NIP: ' + guruAktif.nip) : '—'"></p>
                    </div>
                </div>

                <div class="space-y-1 pt-3">
                    <div x-show="single.loading" class="space-y-2 py-1">
                        <div class="h-4 w-3/4 animate-pulse rounded bg-slate-100"></div>
                        <div class="h-4 w-1/2 animate-pulse rounded bg-slate-100"></div>
                        <div class="h-4 w-2/3 animate-pulse rounded bg-slate-100"></div>
                    </div>

                    <div x-show="!single.loading && !guruAktif" x-cloak class="py-4 text-center">
                        <i class="bi bi-person-dash block text-3xl text-slate-200"></i>
                        <p class="mt-1 text-xs text-slate-400">Pilih guru untuk melihat status kehadiran hari ini</p>
                    </div>

                    <div x-show="!single.loading && guruAktif" x-cloak class="space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500">Status</span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                                :class="chipClass(single.label)"
                                x-text="single.label || '—'"></span>
                        </div>
                        <div class="flex items-center gap-2 border-b border-slate-100 py-2">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" :class="single.jamMasuk ? 'bg-emerald-400' : 'bg-slate-200'"></span>
                            <span class="w-14 shrink-0 text-xs text-slate-500">Datang</span>
                            <span class="ml-auto font-mono text-sm font-bold text-emerald-700" x-text="single.jamMasuk || '—'"></span>
                        </div>
                        <div class="flex items-center gap-2 py-2">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" :class="single.jamPulang ? 'bg-orange-400' : 'bg-slate-200'"></span>
                            <span class="w-14 shrink-0 text-xs text-slate-500">Pulang</span>
                            <span class="ml-auto font-mono text-sm font-bold text-orange-600" x-text="single.jamPulang || '—'"></span>
                        </div>
                        <div x-show="single.keterangan" x-cloak class="pt-2">
                            <p class="mb-1 text-xs text-slate-400">Catatan</p>
                            <p class="text-xs italic text-slate-600" x-text="single.keterangan"></p>
                        </div>
                    </div>
                </div>

                <p x-show="jadwalAktif" x-cloak class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-[11px] text-slate-500">
                    <i class="bi bi-alarm me-1"></i>
                    <span x-text="jadwalAktif ? ('Jadwal kerja: ' + jadwalAktif.jam_masuk + ' – ' + jadwalAktif.jam_pulang + ' (' + jadwalAktif.sumber + ')') : ''"></span>
                </p>
            </x-ui.card>
        </div>
    </div>

    {{-- MODE 2: massal --}}
    <div x-show="mode === 'batch'" x-cloak>
        <x-ui.card title="Absensi Massal" icon="bi-people" tone="emerald" body-class="p-0">
            <x-slot:actions>
                <button type="button" @click="isiYangBelum()"
                    class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100"
                    title="Isi Hadir untuk guru yang belum absen">
                    <i class="bi bi-plus-circle me-1"></i>Isi yang Belum
                </button>
                <button type="button" @click="pulangSaja()"
                    class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700 transition-colors hover:bg-orange-100"
                    title="Isi jam pulang untuk guru yang sudah datang">
                    <i class="bi bi-box-arrow-left me-1"></i>Pulang Saja
                </button>
                <button type="button" @click="resetBatch()"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-500 transition-colors hover:bg-slate-50">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
            </x-slot:actions>

            <x-slot:toolbar>
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2" aria-live="polite" aria-label="Statistik presensi massal">
                        <div class="flex min-w-[68px] flex-1 flex-col items-center rounded-xl bg-slate-100 px-3.5 py-1.5 text-center text-slate-700">
                            <span class="text-xl font-extrabold leading-tight" x-text="statistik.total"></span>
                            <span class="text-[11px] font-semibold opacity-70">Total</span>
                        </div>
                        <div class="flex min-w-[68px] flex-1 flex-col items-center rounded-xl bg-emerald-100 px-3.5 py-1.5 text-center text-emerald-700">
                            <span class="text-xl font-extrabold leading-tight" x-text="statistik.lengkap"></span>
                            <span class="text-[11px] font-semibold opacity-70">Lengkap</span>
                        </div>
                        <div class="flex min-w-[68px] flex-1 flex-col items-center rounded-xl bg-amber-100 px-3.5 py-1.5 text-center text-amber-700">
                            <span class="text-xl font-extrabold leading-tight" x-text="statistik.masuk"></span>
                            <span class="text-[11px] font-semibold opacity-70">Masuk</span>
                        </div>
                        <div class="flex min-w-[68px] flex-1 flex-col items-center rounded-xl bg-sky-100 px-3.5 py-1.5 text-center text-sky-700">
                            <span class="text-xl font-extrabold leading-tight" x-text="statistik.izin"></span>
                            <span class="text-[11px] font-semibold opacity-70">Izin</span>
                        </div>
                        <div class="flex min-w-[68px] flex-1 flex-col items-center rounded-xl bg-red-100 px-3.5 py-1.5 text-center text-red-700">
                            <span class="text-xl font-extrabold leading-tight" x-text="statistik.belum"></span>
                            <span class="text-[11px] font-semibold opacity-70">Belum</span>
                        </div>
                        <div class="ml-auto">
                            <span x-show="editedCount > 0" x-cloak
                                class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-bold text-blue-700">
                                <i class="bi bi-pencil"></i><span x-text="editedCount"></span> diubah
                            </span>
                        </div>
                    </div>

                    <div class="h-1 overflow-hidden rounded bg-slate-200">
                        <div class="h-full rounded bg-emerald-500 transition-all" :style="'width:' + progres + '%'"></div>
                    </div>

                    <div class="flex flex-col gap-2 md:flex-row md:items-center">
                        <div class="relative flex-1">
                            <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                            <input type="search" x-model="query" placeholder="Cari nama guru atau NIP…" autocomplete="off"
                                class="min-h-[38px] w-full rounded-lg border border-slate-200 pl-9 pr-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="Filter kategori">
                            <template x-for="f in filterOpsi" :key="f.key">
                                <button type="button" @click="filter = f.key"
                                    :class="filter === f.key ? f.aktif : 'border-slate-200 bg-white text-slate-600 hover:border-slate-400'"
                                    class="rounded-full border-[1.5px] px-3.5 py-1.5 text-xs font-bold transition-colors">
                                    <span x-text="f.label"></span>
                                    <span class="font-extrabold opacity-80" x-text="hitungFilter(f.key)"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="query !== '' || filter !== 'all'" x-cloak class="mt-1 text-xs text-slate-400 md:mt-0"
                            x-text="'Menampilkan ' + visibleRows.length + ' dari ' + rows.length + ' guru'"></p>
                    </div>
                </div>
            </x-slot:toolbar>

            <div class="max-h-[58vh] overflow-auto" :class="loadingBatch ? 'opacity-60' : ''" tabindex="0" aria-label="Tabel presensi massal">
                <x-ui.table>
                    <x-slot:head>
                        <th class="px-4 py-3 text-left" scope="col" style="width: 46%; min-width: 200px;">Guru</th>
                        <th class="px-3 py-3" style="width: 18%;" scope="col">Status</th>
                        <th class="px-3 py-3 text-center" style="width: 18%;" scope="col">Datang</th>
                        <th class="px-3 py-3 text-center" style="width: 18%;" scope="col">Pulang</th>
                    </x-slot:head>

                    <template x-for="row in visibleRows" :key="row.id">
                        <tr class="border-l-[3px] transition-colors" :class="[borderKategori(row.kategori), row.edited ? 'bg-blue-50/70' : '']">
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-xs font-extrabold text-indigo-700" x-text="row.initial"></div>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold leading-tight text-slate-800" x-text="row.nama"></div>
                                        <div x-show="row.nip && row.nip !== '-'" class="font-mono text-xs text-slate-400" x-text="row.nip"></div>
                                        <div class="mt-0.5">
                                            <span class="rounded-md px-1.5 py-0.5 text-[11px] font-bold" :class="row.badge" x-text="row.label"></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-2 py-2.5">
                                <select x-model="row.status" @change="onBatchStatusChange(row)" :aria-label="'Status ' + row.nama"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-300">
                                    <option value="HADIR">Hadir</option>
                                    <option value="IJIN">Izin</option>
                                    <option value="SAKIT">Sakit</option>
                                    <option value="CUTI">Cuti</option>
                                    <option value="DINAS">Dinas</option>
                                </select>
                            </td>
                            <td class="px-2 py-2.5">
                                <input type="time" x-model="row.jamMasuk" @input="row.edited = true"
                                    :disabled="row.status !== 'HADIR'" :aria-label="'Datang ' + row.nama"
                                    class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-center font-mono text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-300 disabled:bg-slate-50 disabled:text-slate-400">
                            </td>
                            <td class="px-2 py-2.5">
                                <input type="time" x-model="row.jamPulang" @input="row.edited = true"
                                    :disabled="row.status !== 'HADIR'" :aria-label="'Pulang ' + row.nama"
                                    class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-center font-mono text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-300 disabled:bg-slate-50 disabled:text-slate-400">
                            </td>
                        </tr>
                    </template>

                    <tr x-show="visibleRows.length === 0" x-cloak>
                        <td colspan="4" class="px-4 py-6">
                            <x-ui.empty icon="bi-search" title="Tidak ada hasil yang cocok"
                                description="Ubah kata kunci pencarian atau pilih filter lain." />
                        </td>
                    </tr>
                </x-ui.table>
            </div>

            <x-slot:footer>
                <div class="flex items-center gap-3">
                    <p class="flex-1 text-xs text-slate-500"
                        x-text="editedCount > 0 ? (editedCount + ' baris telah diubah dan siap disimpan.') : 'Ubah minimal 1 baris untuk mengaktifkan tombol simpan.'"></p>
                    <button type="button" @click="simpanBatch()" :disabled="editedCount === 0 || savingBatch"
                        class="min-h-[42px] rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40">
                        <i class="bi bi-cloud-upload me-1.5"></i>
                        <span x-text="savingBatch ? 'Menyimpan…' : ('Simpan ' + editedCount + ' Perubahan')"></span>
                    </button>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    const HINT_JENIS = {
        masuk: 'Hanya mencatat jam datang. Jam pulang dapat dilengkapi belakangan.',
        pulang: 'Hanya mencatat jam pulang. Pastikan guru sudah memiliki catatan jam datang.',
        keduanya: 'Mencatat jam datang dan jam pulang sekaligus.',
    };

    const BADGE = {
        lengkap: 'bg-emerald-100 text-emerald-700',
        masuk: 'bg-amber-100 text-amber-700',
        izin: 'bg-sky-100 text-sky-700',
        belum: 'bg-slate-100 text-slate-500',
    };

    const BORDER = {
        lengkap: 'border-l-emerald-500',
        masuk: 'border-l-amber-500',
        izin: 'border-l-sky-500',
        belum: 'border-l-transparent',
    };

    const STATUS_IZIN = ['IJIN', 'IZIN', 'SAKIT', 'CUTI'];

    const potongJam = (nilai) => {
        if (!nilai) return '';

        const teks = String(nilai);
        const bagian = teks.indexOf(' ') >= 0 ? teks.split(' ')[1] : teks;

        return bagian ? bagian.substring(0, 5) : '';
    };

    const esc = (nilai) => String(nilai ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));

    Alpine.data('manualAbsenPage', (config = {}) => ({
        csrf: config.csrf || '',
        primary: config.primary || '#2563eb',
        today: config.today || new Date().toLocaleDateString('en-CA'),
        urls: config.urls || {},
        guru: config.guru || [],
        jadwal: config.jadwal || {},
        holidays: config.holidays || {},
        liburMingguan: config.liburMingguan || ['Sun'],
        jamMasukDefault: config.jamMasukDefault || '07:30',
        jamPulangDefault: config.jamPulangDefault || '15:00',

        mode: 'single',
        tanggal: config.today || '',
        filter: 'all',
        query: '',

        filterOpsi: [
            { key: 'all', label: 'Semua', aktif: 'border-slate-900 bg-slate-900 text-white' },
            { key: 'belum', label: 'Belum', aktif: 'border-red-500 bg-red-500 text-white' },
            { key: 'lengkap', label: 'Lengkap', aktif: 'border-emerald-500 bg-emerald-500 text-white' },
            { key: 'izin', label: 'Izin', aktif: 'border-sky-500 bg-sky-500 text-white' },
        ],

        /* ── Mode per guru ─────────────────────── */
        guruId: '',
        status: 'HADIR',
        jenis: 'keduanya',
        jamMasuk: config.jamMasukDefault || '',
        jamPulang: config.jamPulangDefault || '',
        keterangan: '',
        saving: false,
        jadwalAktif: null,
        single: { loading: false, label: '', jamMasuk: '', jamPulang: '', keterangan: '', adaData: false },

        /* ── Mode massal ───────────────────────── */
        rows: config.batchAwal || [],
        loadingBatch: false,
        savingBatch: false,

        init() {
            this.$watch('tanggal', (nilai) => {
                if (nilai && nilai > this.today) {
                    this.tanggal = this.today;

                    return;
                }

                this.onTanggalChange();
            });

            this.$watch('guruId', () => this.loadStatus());
            this.$watch('status', (nilai) => {
                if (nilai === 'HADIR' && this.isHariAktif && this.jenis === 'keduanya') {
                    this.jenis = 'masuk';
                }
            });

            /* Baris massal hari ini sudah dirender server — jangan muat ulang. */
            this.onTanggalChange(true);
        },

        /* ── Turunan ───────────────────────────── */

        get isHariAktif() {
            return this.tanggal !== '' && this.tanggal === this.today;
        },

        get guruAktif() {
            if (!this.guruId) return null;

            return this.guru.find((g) => String(g.id) === String(this.guruId)) || null;
        },

        get tanggalLabel() {
            if (!this.tanggal) return '—';

            let teks = this.tanggal;

            try {
                teks = new Date(this.tanggal + 'T00:00:00').toLocaleDateString('id-ID', {
                    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
                });
            } catch (e) {
                /* pakai tanggal mentah */
            }

            return teks + (this.isHariAktif ? ' — Hari ini' : '');
        },

        get liburInfo() {
            const tanggal = this.tanggal;

            if (!tanggal) return { libur: false, alasan: '' };

            const d = new Date(tanggal + 'T00:00:00');

            if (isNaN(d.getTime())) return { libur: false, alasan: '' };

            const iso = d.getDay() === 0 ? 7 : d.getDay();
            const singkat = d.toLocaleDateString('en-US', { weekday: 'short' });
            const panjang = d.toLocaleDateString('id-ID', { weekday: 'long' });
            const jadwalHari = this.jadwal[iso] || this.jadwal[String(iso)] || null;

            if (jadwalHari && parseInt(jadwalHari.is_libur) === 1) {
                return { libur: true, alasan: 'jadwal kerja menetapkan ' + panjang + ' sebagai libur' };
            }

            if (this.liburMingguan.indexOf(singkat) !== -1) {
                return { libur: true, alasan: panjang + ' adalah hari libur mingguan' };
            }

            if (Object.prototype.hasOwnProperty.call(this.holidays, tanggal)) {
                return { libur: true, alasan: 'libur nasional: ' + (this.holidays[tanggal] || 'Hari libur nasional') };
            }

            return { libur: false, alasan: '' };
        },

        get hintJenis() {
            return HINT_JENIS[this.jenis] || HINT_JENIS.keduanya;
        },

        get simpanNonaktif() {
            return this.saving || this.liburInfo.libur;
        },

        get visibleRows() {
            const kata = this.query.trim().toLowerCase();

            return this.rows.filter((row) => {
                const cocokCari = kata === '' || (row.nama + ' ' + row.nip).toLowerCase().includes(kata);
                const cocokFilter = this.filter === 'all' || row.kategori === this.filter;

                return cocokCari && cocokFilter;
            });
        },

        get statistik() {
            const hasil = { total: this.rows.length, lengkap: 0, masuk: 0, izin: 0, belum: 0 };

            this.rows.forEach((row) => {
                if (hasil[row.kategori] !== undefined) hasil[row.kategori]++;
            });

            return hasil;
        },

        get editedCount() {
            return this.rows.filter((row) => row.edited).length;
        },

        get progres() {
            const total = this.rows.length;

            if (total === 0) return 0;

            return Math.round(((this.statistik.lengkap + this.statistik.izin) / total) * 100);
        },

        hitungFilter(kunci) {
            return this.statistik[kunci] ?? this.rows.length;
        },

        borderKategori(kategori) {
            return BORDER[kategori] || BORDER.belum;
        },

        chipClass(label) {
            const nilai = String(label || '').toUpperCase();

            if (['HADIR', 'LENGKAP', 'HADIR LENGKAP'].includes(nilai)) return 'bg-emerald-100 text-emerald-700';
            if (nilai.includes('MASUK')) return 'bg-amber-100 text-amber-700';
            if (['IJIN', 'IZIN', 'SAKIT', 'CUTI', 'DINAS'].includes(nilai)) return 'bg-sky-100 text-sky-700';

            return 'bg-slate-100 text-slate-500';
        },

        /* ── Jadwal kerja ──────────────────────── */

        jamKerja(kolom, fallback, tanggal) {
            let iso = new Date().getDay();
            const acuan = tanggal || this.tanggal;

            if (acuan && /^\d{4}-\d{2}-\d{2}$/.test(acuan)) {
                const d = new Date(acuan + 'T00:00:00');

                if (!isNaN(d.getTime())) iso = d.getDay();
            }

            if (iso === 0) iso = 7;

            const hari = this.jadwal[iso] || this.jadwal[String(iso)] || null;
            const nilai = hari ? String(hari[kolom] || '').slice(0, 5) : '';

            return (nilai === '' || nilai === '00:00') ? fallback : nilai;
        },

        /* ── Perubahan tanggal & mode ──────────── */

        quickSetDate(offset) {
            const d = new Date();

            if (offset !== 0) d.setDate(d.getDate() + offset);

            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            let hasil = yyyy + '-' + mm + '-' + dd;

            if (hasil > this.today) hasil = this.today;
            this.tanggal = hasil;
        },

        onTanggalChange(lewatiBatch = false) {
            if (!this.tanggal) return;

            if (this.isHariAktif && this.jenis === 'keduanya') this.jenis = 'masuk';

            if (this.guruId) {
                this.loadStatus();
            } else {
                this.resetSingle();
            }

            if (!lewatiBatch) this.refreshBatch();
        },

        resetSingle() {
            this.jadwalAktif = null;
            this.single = {
                loading: false,
                label: '',
                jamMasuk: '',
                jamPulang: '',
                keterangan: '',
                adaData: false,
            };
            this.jenis = this.isHariAktif ? 'masuk' : 'keduanya';
        },

        /* ── Transport ─────────────────────────── */

        async ambilJson(url, params = {}) {
            const alamat = new URL(url, window.location.origin);

            Object.entries(params).forEach(([kunci, nilai]) => alamat.searchParams.set(kunci, nilai ?? ''));

            const res = await fetch(alamat, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            return { code: res.status, json: await res.json().catch(() => null) };
        },

        async kirimForm(url, isian = {}) {
            const body = new FormData();

            Object.entries(isian).forEach(([kunci, nilai]) => body.append(kunci, nilai ?? ''));
            body.append('_token', this.csrf);

            return this.kirimBody(url, body);
        },

        async kirimBody(url, body) {
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    body,
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                return { code: res.status, json: await res.json().catch(() => null) };
            } catch (e) {
                return { code: 0, json: null };
            }
        },

        csrfKedaluwarsa(recap) {
            Swal.fire({
                icon: 'warning',
                title: 'Sesi Kedaluwarsa',
                html: (recap || '') + '<p style="margin-top:8px;font-size:12px;color:#b45309">Token keamanan kedaluwarsa (tab terbuka lama). Muat ulang halaman lalu ulangi penyimpanan.</p>',
                confirmButtonColor: this.primary,
                confirmButtonText: 'Muat Ulang Halaman',
                cancelButtonText: 'Nanti',
                showCancelButton: true,
            }).then((r) => {
                if (r.isConfirmed) location.reload();
            });
        },

        /* ── Status guru terpilih ──────────────── */

        async loadStatus() {
            const id = this.guruId;
            const tanggal = this.tanggal;

            if (!id || !tanggal) {
                this.resetSingle();

                return;
            }

            this.single = { loading: true, label: '', jamMasuk: '', jamPulang: '', keterangan: '', adaData: false };
            this.jadwalAktif = null;

            let hasil;

            try {
                hasil = await this.ambilJson(this.urls.status, { user_id: id, tanggal });
            } catch (e) {
                hasil = { json: null };
            }

            /* Abaikan jawaban basi bila guru/tanggal sudah berganti. */
            if (String(this.guruId) !== String(id) || this.tanggal !== tanggal) return;

            if (!hasil.json || !hasil.json.status) {
                this.single = { loading: false, label: 'Error', jamMasuk: '', jamPulang: '', keterangan: '', adaData: false };

                return;
            }

            this.terapkanStatus(hasil.json, tanggal);
        },

        terapkanStatus(json, tanggal) {
            const att = json.data || null;
            this.jadwalAktif = json.jadwal || null;

            const jamMasuk = potongJam(att?.jam_masuk);
            const jamPulang = potongJam(att?.jam_pulang);
            const statusMasuk = String(att?.status_masuk || '').toUpperCase();
            const dinas = statusMasuk.indexOf('DINAS') === 0;
            const izin = dinas || STATUS_IZIN.includes(statusMasuk);

            let label = 'Belum Absen';

            if (att && izin) {
                label = dinas ? 'DINAS' : statusMasuk;
                this.status = dinas ? 'DINAS' : statusMasuk;
            } else if (att && jamMasuk !== '' && jamPulang !== '') {
                label = 'Lengkap';
                this.status = 'HADIR';
                this.jamMasuk = jamMasuk;
                this.jamPulang = jamPulang;
                this.jenis = 'keduanya';
            } else if (att && jamMasuk !== '') {
                label = 'Masuk Saja';
                this.status = 'HADIR';
                this.jamMasuk = jamMasuk;
                this.jenis = 'pulang';
            } else {
                this.jenis = tanggal === this.today ? 'masuk' : 'keduanya';
            }

            if (this.isHariAktif && this.jenis === 'keduanya') this.jenis = 'masuk';

            this.keterangan = att?.keterangan ? String(att.keterangan) : '';
            this.single = {
                loading: false,
                label,
                jamMasuk,
                jamPulang,
                keterangan: this.keterangan,
                adaData: !!att,
            };
        },

        /* ── Simpan / hapus per guru ───────────── */

        ringkasanHtml() {
            const guru = this.guruAktif;
            const status = this.status;
            const jenisTeks = status !== 'HADIR'
                ? status
                : (this.jenis === 'masuk' ? 'Datang saja' : this.jenis === 'pulang' ? 'Pulang saja' : 'Datang + Pulang');

            const baris = [
                ['Guru', guru ? guru.nama : '—'],
                ['Tanggal', this.tanggalLabel],
                ['Status', status],
                ['Jenis', jenisTeks],
            ];

            if (status === 'HADIR' && this.jenis !== 'pulang') baris.push(['Jam datang', this.jamMasuk || '—']);
            if (status === 'HADIR' && this.jenis !== 'masuk') baris.push(['Jam pulang', this.jamPulang || '—']);
            if (this.keterangan) baris.push(['Catatan', this.keterangan]);

            return '<div style="text-align:left;font-size:13px">'
                + baris.map((r) => '<div style="display:flex;gap:8px;padding:5px 0;border-bottom:1px solid #f1f5f9">'
                    + '<div style="width:96px;color:#64748b;flex-shrink:0">' + esc(r[0]) + '</div>'
                    + '<div style="font-weight:700">' + esc(r[1]) + '</div></div>').join('')
                + '</div>';
        },

        async simpanSingle() {
            if (this.saving) return;

            const info = this.liburInfo;

            if (!this.guruId) {
                Swal.fire('Perhatian', 'Silakan pilih guru terlebih dahulu.', 'warning');

                return;
            }
            if (!this.tanggal) {
                Swal.fire('Perhatian', 'Silakan tentukan tanggal absensi.', 'warning');

                return;
            }
            if (info.libur) {
                Swal.fire('Hari Libur', 'Server menolak presensi di hari libur (' + info.alasan + ').', 'warning');

                return;
            }
            if (this.status === 'HADIR' && this.jenis !== 'pulang' && !this.jamMasuk) {
                Swal.fire('Perhatian', 'Jam datang harus diisi.', 'warning');

                return;
            }
            if (this.status === 'HADIR' && this.jenis !== 'masuk' && !this.jamPulang) {
                Swal.fire('Perhatian', 'Jam pulang harus diisi.', 'warning');

                return;
            }
            if (this.status === 'HADIR' && this.jenis === 'keduanya' && this.isHariAktif) {
                Swal.fire('Tidak Diizinkan', 'Pada hari aktif hanya boleh Datang saja atau Pulang saja. Datang + Pulang sekaligus hanya untuk tanggal kemarin atau sebelumnya.', 'warning');

                return;
            }

            const konfirmasi = await Swal.fire({
                title: 'Simpan Presensi?',
                html: this.ringkasanHtml(),
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: this.primary,
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Simpan',
                cancelButtonText: 'Cek Lagi',
            });

            if (!konfirmasi.isConfirmed) return;

            await this.kirimPresensi();
        },

        async kirimPresensi() {
            const recap = this.ringkasanHtml();
            const guruId = this.guruId;
            const tanggal = this.tanggal;

            this.saving = true;

            try {
                /* Pulang saja → checkout saja. */
                if (this.status === 'HADIR' && this.jenis === 'pulang') {
                    const keluar = await this.kirimForm(this.urls.checkout, {
                        user_id: guruId, tanggal, jam_pulang: this.jamPulang,
                    });

                    if (this.tanganiGagal(keluar, recap)) return;

                    await this.selesaiSimpan(keluar, recap, 'Absen Pulang Tersimpan');

                    return;
                }

                const masuk = await this.kirimForm(this.urls.checkin, {
                    user_id: guruId,
                    tanggal,
                    status: this.status,
                    jam_masuk: this.status === 'HADIR' ? this.jamMasuk : '',
                    keterangan: this.keterangan,
                });

                if (this.tanganiGagal(masuk, recap)) return;

                if (!masuk.json.status) {
                    this.gagalSimpan(recap, masuk.json.message);

                    return;
                }

                if (this.status !== 'HADIR' || this.jenis === 'masuk' || !this.jamPulang) {
                    await this.selesaiSimpan(masuk, recap, 'Presensi Tersimpan');

                    return;
                }

                const keluar = await this.kirimForm(this.urls.checkout, {
                    user_id: guruId, tanggal, jam_pulang: this.jamPulang,
                });

                if (this.tanganiGagal(keluar, recap)) return;

                await this.selesaiSimpan(keluar, recap, keluar.json.status ? 'Datang + Pulang Tersimpan' : 'Sebagian Gagal');
            } finally {
                this.saving = false;
            }
        },

        tanganiGagal(hasil, recap) {
            if (hasil.code === 403) {
                this.csrfKedaluwarsa(recap);

                return true;
            }

            if (!hasil.json) {
                this.gagalSimpan(recap, 'Terjadi kesalahan sistem saat menyimpan presensi.');

                return true;
            }

            return false;
        },

        gagalSimpan(recap, pesan) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                html: (recap || '') + '<p style="margin-top:8px;font-size:12px;color:#dc2626">'
                    + esc(pesan || 'Gagal menyimpan presensi.') + '</p>',
            });
        },

        async selesaiSimpan(hasil, recap, judul) {
            if (hasil.json && hasil.json.status) {
                await Swal.fire({
                    icon: 'success',
                    title: judul,
                    html: recap + '<p style="margin-top:8px;font-size:12px;color:#059669">'
                        + esc(hasil.json.message || 'Presensi berhasil disimpan.') + '</p>',
                    timer: 2600,
                    showConfirmButton: false,
                });
                this.loadStatus();
                this.refreshBatch();

                return;
            }

            this.gagalSimpan(recap, hasil.json?.message);
        },

        async hapusSingle() {
            const guru = this.guruAktif;

            if (!guru || !this.tanggal) return;

            const konfirmasi = await Swal.fire({
                title: 'Hapus Presensi?',
                html: '<div style="text-align:left;font-size:13px">'
                    + '<div style="display:flex;gap:8px;padding:5px 0;border-bottom:1px solid #f1f5f9"><div style="width:96px;color:#64748b">Guru</div><div style="font-weight:700">' + esc(guru.nama) + '</div></div>'
                    + '<div style="display:flex;gap:8px;padding:5px 0"><div style="width:96px;color:#64748b">Tanggal</div><div style="font-weight:700">' + esc(this.tanggalLabel) + '</div></div>'
                    + '<p style="margin-top:10px;font-size:12px;color:#b45309;background:#fffbeb;padding:8px 12px;border-radius:8px;border:1px solid #fcd34d">Data akan direset ke Belum Absen. Poin &amp; denda ikut dihitung ulang.</p></div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
            });

            if (!konfirmasi.isConfirmed) return;

            const hasil = await this.kirimForm(this.urls.hapus, { user_id: guru.id, tanggal: this.tanggal });

            if (hasil.code === 403) {
                this.csrfKedaluwarsa();

                return;
            }
            if (!hasil.json) {
                Swal.fire('Gagal', 'Terjadi kesalahan sistem.', 'error');

                return;
            }
            if (!hasil.json.status) {
                Swal.fire('Gagal', hasil.json.message, 'error');

                return;
            }

            await Swal.fire({
                icon: 'success',
                title: 'Terhapus',
                html: '<p style="font-size:13px">' + esc(guru.nama) + '</p>'
                    + '<p style="font-size:12px;color:#64748b">' + esc(this.tanggalLabel) + '</p>'
                    + '<p style="font-size:12px;color:#64748b">' + esc(hasil.json.message || '') + '</p>',
                timer: 2200,
                showConfirmButton: false,
            });

            this.loadStatus();
            this.refreshBatch();
        },

        /* ── Mode massal ───────────────────────── */

        async refreshBatch() {
            const tanggal = this.tanggal;

            if (!tanggal) return;

            this.loadingBatch = true;

            let hasil;

            try {
                hasil = await this.ambilJson(this.urls.batchStatus, { tanggal });
            } catch (e) {
                hasil = { json: null };
            } finally {
                this.loadingBatch = false;
            }

            if (this.tanggal !== tanggal || !hasil?.json || !hasil.json.status) return;

            this.terapkanBatch(hasil.json.data || {});
        },

        terapkanBatch(data) {
            this.rows = this.rows.map((row) => {
                const att = data[row.id] ?? data[String(row.id)] ?? null;
                const segar = { ...row, edited: false, jamMasuk: '', jamPulang: '', keterangan: '', status: 'HADIR', kategori: 'belum', label: 'Belum Absen', badge: BADGE.belum };

                if (!att) return segar;

                const jamMasuk = att.jam_masuk || potongJam(att.jam_masuk_raw);
                const jamPulang = att.jam_pulang || potongJam(att.jam_pulang_raw);
                const statusMasuk = String(att.status_masuk || '').toUpperCase();
                const dinas = statusMasuk.indexOf('DINAS') === 0;

                segar.keterangan = att.keterangan || '';

                if (dinas || STATUS_IZIN.includes(statusMasuk)) {
                    segar.status = dinas ? 'DINAS' : statusMasuk;
                    segar.label = segar.status;
                    segar.badge = BADGE.izin;
                    segar.kategori = 'izin';
                } else if (att.jam_masuk_raw && att.jam_pulang_raw) {
                    segar.jamMasuk = jamMasuk;
                    segar.jamPulang = jamPulang;
                    segar.label = 'Lengkap';
                    segar.badge = BADGE.lengkap;
                    segar.kategori = 'lengkap';
                } else if (att.jam_masuk_raw) {
                    segar.jamMasuk = jamMasuk;
                    segar.label = 'Masuk Saja';
                    segar.badge = BADGE.masuk;
                    segar.kategori = 'masuk';
                }

                return segar;
            });
        },

        onBatchStatusChange(row) {
            row.edited = true;

            if (row.status === 'HADIR') {
                if (!row.jamMasuk) row.jamMasuk = this.jamKerja('jam_masuk', this.jamMasukDefault);
                if (!this.isHariAktif && !row.jamPulang) row.jamPulang = this.jamKerja('jam_pulang', this.jamPulangDefault);
            } else {
                row.jamMasuk = '';
                row.jamPulang = '';
            }
        },

        isiYangBelum() {
            const aktif = this.isHariAktif;
            const masuk = this.jamKerja('jam_masuk', this.jamMasukDefault);
            const pulang = this.jamKerja('jam_pulang', this.jamPulangDefault);

            this.visibleRows.forEach((row) => {
                if (row.kategori !== 'belum') return;

                row.status = 'HADIR';
                row.jamMasuk = masuk;
                row.jamPulang = aktif ? '' : pulang;
                row.edited = true;
            });

            if (aktif) {
                Swal.fire('Info', 'Hari aktif: hanya jam datang yang diisi otomatis. Jam pulang diisi terpisah.', 'info');
            }
        },

        pulangSaja() {
            const pulang = this.jamKerja('jam_pulang', this.jamPulangDefault);
            let jumlah = 0;

            this.visibleRows.forEach((row) => {
                if (row.kategori !== 'masuk') return;

                row.status = 'HADIR';
                row.jamMasuk = '';
                if (!row.jamPulang) row.jamPulang = pulang;
                row.edited = true;
                jumlah++;
            });

            if (jumlah === 0) {
                Swal.fire('Info', 'Tidak ada guru berstatus "Masuk Saja" yang tampil saat ini.', 'info');
            }
        },

        resetBatch() {
            this.refreshBatch();
        },

        ringkasanBatchHtml(entries) {
            const perStatus = {};
            let datang = 0, pulang = 0, keduanya = 0;

            entries.forEach((e) => {
                const status = String(e.status || 'HADIR').toUpperCase();
                perStatus[status] = (perStatus[status] || 0) + 1;

                if (status === 'HADIR') {
                    if (e.jamMasuk && e.jamPulang) keduanya++;
                    else if (e.jamPulang) pulang++;
                    else datang++;
                }
            });

            const warna = { HADIR: '#059669', IJIN: '#0284c7', SAKIT: '#d97706', CUTI: '#7c3aed', DINAS: '#2563eb' };
            const chip = Object.keys(perStatus).sort().map((k) => '<span style="display:inline-block;padding:2px 8px;margin:2px;border-radius:999px;font-size:11px;font-weight:800;background:#f1f5f9;color:'
                + (warna[k] || '#334155') + '">' + esc(k) + ' × ' + perStatus[k] + '</span>').join('');

            let daftar = entries.slice(0, 5).map((e) => '<li>' + esc(e.nama || 'ID ' + e.id) + ' — ' + esc(e.status)
                + (e.status === 'HADIR' ? ' (' + esc(e.jamMasuk || '–') + ' / ' + esc(e.jamPulang || '–') + ')' : '') + '</li>').join('');

            if (entries.length > 5) {
                daftar += '<li style="color:#64748b">… dan ' + (entries.length - 5) + ' lainnya</li>';
            }

            const info = this.liburInfo;

            return '<div style="text-align:left;font-size:13px">'
                + '<div style="display:flex;gap:8px;padding:5px 0;border-bottom:1px solid #f1f5f9"><div style="width:88px;color:#64748b">Tanggal</div><div style="font-weight:700">' + esc(this.tanggalLabel) + '</div></div>'
                + '<div style="display:flex;gap:8px;padding:5px 0;border-bottom:1px solid #f1f5f9"><div style="width:88px;color:#64748b">Diubah</div><div style="font-weight:700">' + entries.length + ' guru</div></div>'
                + '<div style="padding:6px 0">' + chip + '</div>'
                + '<div style="color:#64748b;font-size:11px;margin:2px 0">Datang: ' + datang + ' • Pulang: ' + pulang + ' • Keduanya: ' + keduanya + '</div>'
                + '<ul style="margin:6px 0 0 16px;padding:0;color:#0f172a">' + daftar + '</ul>'
                + (info.libur
                    ? '<p style="margin:8px 0 0;font-size:12px;color:#b45309;background:#fffbeb;padding:8px 12px;border-radius:8px;border:1px solid #fcd34d">Hari libur (' + esc(info.alasan) + '): baris HADIR akan dilewati server, status lain tetap tersimpan.</p>'
                    : '')
                + '</div>';
        },

        hasilBatchHtml(json) {
            let html = '<p style="font-size:13px;color:#0f172a">' + esc(json?.message || 'Presensi massal berhasil diperbarui.') + '</p>'
                + '<p style="font-size:11px;color:#64748b">' + esc(this.tanggalLabel) + '</p>';
            const galat = json?.errors || [];

            if (galat.length) {
                html += '<div style="text-align:left;margin-top:8px;max-height:140px;overflow:auto;font-size:12px;color:#b45309">'
                    + '<strong>' + galat.length + ' baris dilewati:</strong>'
                    + '<ul style="margin:4px 0 0 16px;padding:0">'
                    + galat.slice(0, 8).map((e) => '<li>' + esc(e) + '</li>').join('')
                    + (galat.length > 8 ? '<li>… dan ' + (galat.length - 8) + ' lainnya</li>' : '')
                    + '</ul></div>';
            }

            return html;
        },

        async simpanBatch() {
            if (this.savingBatch) return;

            const tanggal = this.tanggal;

            if (!tanggal) {
                Swal.fire('Perhatian', 'Silakan pilih tanggal absensi.', 'warning');

                return;
            }

            const entries = this.rows
                .filter((row) => row.edited)
                .map((row) => ({
                    id: row.id,
                    nama: row.nama,
                    status: row.status,
                    jamMasuk: row.jamMasuk,
                    jamPulang: row.jamPulang,
                    keterangan: row.keterangan,
                }));

            if (entries.length === 0) {
                Swal.fire('Perhatian', 'Belum ada perubahan. Baris yang diubah akan ditandai biru.', 'warning');

                return;
            }

            if (this.isHariAktif) {
                const dilanggar = entries.filter((e) => e.status === 'HADIR' && e.jamMasuk && e.jamPulang).length;

                if (dilanggar > 0) {
                    Swal.fire('Tidak Diizinkan', dilanggar + ' baris memiliki datang + pulang sekaligus. Pada hari aktif, kosongkan salah satunya.', 'warning');

                    return;
                }
            }

            const konfirmasi = await Swal.fire({
                title: 'Simpan ' + entries.length + ' Perubahan?',
                html: this.ringkasanBatchHtml(entries),
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Simpan',
                cancelButtonText: 'Cek Lagi',
            });

            if (!konfirmasi.isConfirmed) return;

            const body = new FormData();
            body.append('_token', this.csrf);
            body.append('tanggal', tanggal);
            entries.forEach((e, i) => {
                body.append(`entries[${i}][user_id]`, e.id);
                body.append(`entries[${i}][status]`, e.status);
                body.append(`entries[${i}][jam_masuk]`, e.jamMasuk || '');
                body.append(`entries[${i}][jam_pulang]`, e.jamPulang || '');
                body.append(`entries[${i}][keterangan]`, e.keterangan || '');
            });

            this.savingBatch = true;

            try {
                const hasil = await this.kirimBody(this.urls.batch, body);

                if (hasil.code === 403) {
                    this.csrfKedaluwarsa();

                    return;
                }
                if (!hasil.json) {
                    Swal.fire('Error', 'Terjadi kesalahan sistem saat menyimpan presensi massal.', 'error');

                    return;
                }

                Swal.fire({
                    icon: hasil.json.status ? ((hasil.json.errors || []).length ? 'warning' : 'success') : 'error',
                    title: hasil.json.status
                        ? ((hasil.json.errors || []).length ? 'Tersimpan dengan Catatan' : 'Berhasil!')
                        : 'Gagal',
                    html: this.hasilBatchHtml(hasil.json),
                });

                if (hasil.json.status) this.refreshBatch();
            } finally {
                this.savingBatch = false;
            }
        },
    }));
});
</script>
@endsection
