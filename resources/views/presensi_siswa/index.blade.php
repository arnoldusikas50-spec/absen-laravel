@extends('layouts.main')

@section('content')

<div x-data="presensiSiswaPage({
        urls: {
            siswa: '{{ url('presensisiswa/getSiswa') }}',
            presensi: '{{ url('presensisiswa/getPresensi') }}',
            bdr: '{{ url('presensisiswa/getBdr') }}',
            updateJam: '{{ url('presensisiswa/updateJam') }}',
            checkIn: '{{ url('presensisiswa/checkIn') }}',
            simpan: '{{ url('presensisiswa/save') }}',
            rekap: '{{ url('presensisiswa/rekapBulanan') }}',
        },
        token: '{{ csrf_token() }}',
        hariIni: '{{ date('Y-m-d') }}',
        jamMasuk: '{{ $jam_masuk_default ?? '07:15' }}',
        jamPulang: '{{ $jam_pulang_default ?? '14:00' }}',
        jadwal: {{ json_encode($jadwal_kerja_map ?? []) }},
    })" x-init="init()" class="fade-in-up space-y-6 p-4 md:p-6">

    {{-- Header --}}
    <div class="flex flex-col justify-between gap-4 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs lg:flex-row lg:items-center print:hidden">
        <div>
            <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">
                <i class="bi bi-people-fill"></i> Absensi Harian Siswa
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $judul }}</h1>
            <p class="mt-1 text-xs text-slate-500">{{ $subjudul }}</p>
        </div>
        <nav class="flex items-center gap-1.5 text-xs text-slate-500">
            <a href="{{ url('dashboard') }}" class="font-semibold no-underline hover:text-blue-600"><i class="bi bi-house-door mr-1"></i>Dashboard</a>
            <span class="text-slate-300">/</span>
            <span class="font-bold text-indigo-600">Presensi Siswa</span>
        </nav>
    </div>

    {{-- Tab --}}
    <div class="flex w-fit gap-1 rounded-xl border border-slate-200/80 bg-white p-1 shadow-xs print:hidden">
        <button type="button" @click="tab = 'absen'"
            :class="tab === 'absen' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
            class="rounded-lg px-4 py-1.5 text-sm font-semibold transition-all">
            <i class="bi bi-ui-checks mr-1.5"></i>Input Absensi
        </button>
        <button type="button" @click="tab = 'rekap'"
            :class="tab === 'rekap' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
            class="rounded-lg px-4 py-1.5 text-sm font-semibold transition-all">
            <i class="bi bi-file-earmark-bar-graph mr-1.5"></i>Rekap Bulanan
        </button>
    </div>

    {{-- ═════════════ TAB 1: INPUT ABSENSI ═════════════ --}}
    <div x-show="tab === 'absen'" x-cloak class="space-y-4">

        <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs print:hidden">
            <div class="flex items-center gap-2 border-b border-slate-100 p-5">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <i class="bi bi-funnel text-sm"></i>
                </span>
                <h2 class="text-sm font-extrabold text-slate-900">Filter Kelas &amp; Tanggal</h2>
            </div>

            <div class="p-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="id_kelas" class="mb-1 block text-xs font-bold text-slate-700">Kelas <span class="text-rose-500">*</span></label>
                        <select id="id_kelas" x-model="idKelas"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <option value="">— Pilih Kelas —</option>
                            @foreach ($kelass as $id => $nama)
                                <option value="{{ $id }}">{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="tgl" class="mb-1 block text-xs font-bold text-slate-700">Tanggal <span class="text-rose-500">*</span></label>
                        <input type="date" id="tgl" x-model="tgl" :max="hariIni" @change="tanggalBerubah()"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    </div>
                    <div>
                        <label for="jam_masuk_default" class="mb-1 block text-xs font-bold text-slate-700">Jam Masuk Default</label>
                        <input type="time" id="jam_masuk_default" x-model="jamMasukDefault"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        <p class="mt-1 text-xs text-slate-400">Otomatis dari /settings</p>
                    </div>
                    <div>
                        <label for="jam_pulang_default" class="mb-1 block text-xs font-bold text-slate-700">Jam Pulang Default</label>
                        <input type="time" id="jam_pulang_default" x-model="jamPulangDefault"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        <p class="mt-1 text-xs text-slate-400">Otomatis dari /settings</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <button type="button" @click="muatSiswa()" :disabled="memuat"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs shadow-indigo-500/20 transition-colors hover:bg-indigo-700 disabled:opacity-60">
                        <i class="bi" :class="memuat ? 'bi-arrow-repeat animate-spin' : 'bi-search'"></i> Tampilkan Siswa
                    </button>

                    <button type="button" x-show="rows.length > 0" x-cloak @click="simpanSemua()" :disabled="menyimpan"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-xs shadow-emerald-500/20 transition-colors hover:bg-emerald-700 disabled:opacity-60">
                        <i class="bi" :class="menyimpan ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                        <span x-text="menyimpan ? 'Menyimpan...' : 'Simpan Absensi'"></span>
                    </button>

                    <button type="button" x-show="rows.length > 0" x-cloak @click="tandaiSemua('HADIR')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-100">
                        <i class="bi bi-check-all"></i> Semua Hadir
                    </button>

                    <button type="button" x-show="rows.length > 0 && bdrJudul" x-cloak @click="tandaiSemua('BDR')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-100">
                        <i class="bi bi-house-door"></i> Semua BDR
                    </button>

                    <div x-show="rows.length > 0" x-cloak class="ml-auto flex flex-wrap gap-1.5 text-xs">
                        <span class="inline-flex h-6 min-w-8 items-center justify-center rounded-full bg-emerald-100 px-1.5 font-extrabold text-emerald-700" x-text="'H:' + hitung.HADIR"></span>
                        <span class="inline-flex h-6 min-w-8 items-center justify-center rounded-full bg-yellow-100 px-1.5 font-extrabold text-yellow-700" x-text="'S:' + hitung.SAKIT"></span>
                        <span class="inline-flex h-6 min-w-8 items-center justify-center rounded-full bg-sky-100 px-1.5 font-extrabold text-sky-700" x-text="'I:' + hitung.IZIN"></span>
                        <span class="inline-flex h-6 min-w-8 items-center justify-center rounded-full bg-red-100 px-1.5 font-extrabold text-red-700" x-text="'A:' + hitung.ALPA"></span>
                        <span class="inline-flex h-6 min-w-8 items-center justify-center rounded-full bg-teal-100 px-1.5 font-extrabold text-teal-700" x-text="'BDR:' + hitung.BDR"></span>
                        <span class="inline-flex h-6 min-w-8 items-center justify-center rounded-full bg-slate-100 px-1.5 font-extrabold text-slate-600" x-text="'T:' + hitung.TANPA_KETERANGAN"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Banner BDR --}}
        <div x-show="bdrJudul !== ''" x-cloak class="flex items-start gap-3 rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3 text-teal-800">
            <i class="bi bi-house-door-fill mt-0.5 text-lg leading-none"></i>
            <div class="text-sm">
                <p class="mb-0.5 font-bold">Kelas ini sedang Belajar dari Rumah (BDR)</p>
                <p class="text-xs text-teal-700" x-text="bdrJudul + ' — siswa belajar dari rumah, tidak perlu scan di sekolah.'"></p>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="memuat" x-cloak class="py-12 text-center">
            <div class="inline-flex flex-col items-center gap-3">
                <div class="h-10 w-10 animate-spin rounded-full border-4 border-indigo-600 border-t-transparent"></div>
                <p class="text-sm text-slate-400">Memuat data siswa…</p>
            </div>
        </div>

        {{-- Empty --}}
        <div x-show="! memuat && rows.length === 0" class="rounded-3xl border border-slate-200/80 bg-white py-16 text-center shadow-xs">
            <i class="bi bi-people text-5xl text-slate-300"></i>
            <p class="mt-3 font-medium text-slate-400">Pilih kelas lalu klik <strong>Tampilkan Siswa</strong></p>
        </div>

        {{-- Daftar siswa --}}
        <div x-show="rows.length > 0" x-cloak class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-5">
                <div class="flex min-w-0 items-center gap-2">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <i class="bi bi-people-fill text-sm"></i>
                    </span>
                    <h2 class="truncate text-sm font-extrabold text-slate-900" x-text="'Daftar Siswa — ' + namaKelas"></h2>
                </div>
                <span class="text-xs font-medium text-slate-400" x-text="labelTanggal"></span>
                <div class="relative ml-auto min-w-[200px] print:hidden">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-slate-400">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" x-model="query" placeholder="Cari nama / NIS..." autocomplete="off" aria-label="Cari siswa"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pr-8 pl-8 text-xs font-semibold text-slate-800 outline-hidden focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <button type="button" x-show="query !== ''" x-cloak @click="query = ''" title="Bersihkan pencarian"
                        class="absolute inset-y-0 right-1 my-auto flex h-6 w-6 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-200/70 hover:text-slate-700">
                        <i class="bi bi-x text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="hidden border-b border-slate-100 bg-slate-50 px-5 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 lg:grid lg:grid-cols-[2rem_1fr_9rem_18rem_14rem]">
                <span class="text-center">#</span>
                <span>Nama Siswa</span>
                <span class="text-center">Jam Masuk / Keluar</span>
                <span class="text-center">Status Kehadiran</span>
                <span class="text-center">Keterangan</span>
            </div>

            <div class="divide-y divide-slate-50">
                <template x-for="(s, idx) in daftarTampil" :key="s.id_siswa">
                    <div class="grid grid-cols-[2rem_1fr] items-start gap-3 px-4 py-3 transition-colors hover:bg-slate-50/60">
                        <span class="pt-2 text-center font-mono text-xs text-slate-400" x-text="idx + 1"></span>

                        <div class="grid grid-cols-1 items-center gap-2 lg:grid-cols-[1fr_9rem_18rem_14rem]">
                            {{-- Info siswa --}}
                            <div class="flex min-w-0 items-center gap-2.5">
                                <template x-if="fotoSiswa(s)">
                                    <img :src="fotoSiswa(s)" :alt="inisial(s.nama)" loading="lazy" x-on:error="s.fotoGagal = true"
                                        class="h-9 w-9 shrink-0 rounded-full object-cover">
                                </template>
                                <template x-if="! fotoSiswa(s)">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white"
                                        :class="warnaAvatar(s.nama)" x-text="inisial(s.nama)"></span>
                                </template>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-800" :title="s.nama" x-text="s.nama || '-'"></p>
                                    <p class="text-xs text-slate-400" x-text="'NIS: ' + (s.nis || '—')"></p>
                                </div>
                            </div>

                            {{-- Jam --}}
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3.5 text-[10px] font-semibold text-slate-400">M</span>
                                    <input type="time" :value="s.jamMasuk" @change="ubahJam(s, 'masuk', $event.target.value)"
                                        title="Jam Masuk" aria-label="Jam masuk"
                                        class="w-[100px] rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700 outline-hidden focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3.5 text-[10px] font-semibold text-slate-400">K</span>
                                    <input type="time" :value="s.jamKeluar" @change="ubahJam(s, 'keluar', $event.target.value)"
                                        title="Jam Keluar" aria-label="Jam keluar"
                                        class="w-[100px] rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700 outline-hidden focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                </div>
                            </div>

                            {{-- Status --}}
                            <div class="flex gap-1">
                                <template x-for="opt in pilihanStatus" :key="opt.kode">
                                    <button type="button" @click="setStatus(s, opt.kode)"
                                        :class="s.status === opt.kode ? opt.aktif : 'border-transparent bg-slate-100 text-slate-500 hover:brightness-95'"
                                        class="flex-1 rounded-lg border-2 px-0 py-1.5 text-[11px] font-bold tracking-wide whitespace-nowrap transition-all"
                                        x-text="opt.label"></button>
                                </template>
                            </div>

                            {{-- Keterangan --}}
                            <input type="text" x-model="s.keterangan" maxlength="500" placeholder="Keterangan (opsional)"
                                aria-label="Keterangan"
                                class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700 outline-hidden focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                    </div>
                </template>

                <div x-show="daftarTampil.length === 0" class="px-4 py-8 text-center text-sm text-slate-400">
                    Tidak ada siswa yang cocok dengan pencarian.
                </div>
            </div>
        </div>
    </div>

    {{-- ═════════════ TAB 2: REKAP BULANAN ═════════════ --}}
    <div x-show="tab === 'rekap'" x-cloak class="space-y-4">

        <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs print:hidden">
            <div class="flex items-center gap-2 border-b border-slate-100 p-5">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <i class="bi bi-file-earmark-bar-graph text-sm"></i>
                </span>
                <h2 class="text-sm font-extrabold text-slate-900">Rekap Bulanan</h2>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="rekap_kelas" class="mb-1 block text-xs font-bold text-slate-700">Kelas <span class="text-rose-500">*</span></label>
                        <select id="rekap_kelas" x-model="rekapKelas"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                            <option value="">— Pilih Kelas —</option>
                            @foreach ($kelass as $id => $nama)
                                <option value="{{ $id }}">{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="rekap_bulan" class="mb-1 block text-xs font-bold text-slate-700">Bulan</label>
                        <select id="rekap_bulan" x-model="rekapBulan"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                            @foreach (['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $m => $namaBulan)
                                @continue($m === 0)
                                <option value="{{ $m }}" @selected($m == date('n'))>{{ $namaBulan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="rekap_tahun" class="mb-1 block text-xs font-bold text-slate-700">Tahun</label>
                        <select id="rekap_tahun" x-model="rekapTahun"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                            @for ($y = date('Y'); $y >= attendance_tahun_tertua(); $y--)
                                <option value="{{ $y }}" @selected($y == date('Y'))>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <button type="button" @click="muatRekap()" :disabled="memuatRekap"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-xs shadow-emerald-500/20 transition-colors hover:bg-emerald-700 disabled:opacity-60">
                        <i class="bi" :class="memuatRekap ? 'bi-arrow-repeat animate-spin' : 'bi-search'"></i> Tampilkan Ringkasan
                    </button>
                    <a href="{{ url('admin/presensi/siswa/rekap') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-700 no-underline transition-colors hover:bg-emerald-100">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Matriks Rekap Bulanan Lengkap
                    </a>
                </div>
            </div>
        </div>

        <div x-show="memuatRekap" x-cloak class="py-10 text-center">
            <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-emerald-500 border-t-transparent"></div>
            <p class="mt-3 text-sm text-slate-400">Menghitung rekap…</p>
        </div>

        <div x-show="rekapRows.length > 0 && ! memuatRekap" x-cloak class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs">
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 p-5">
                <h2 class="text-sm font-extrabold text-slate-900" x-text="judulRekap"></h2>
                <button type="button" onclick="window.print()"
                    class="ml-auto inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-100 print:hidden">
                    <i class="bi bi-printer"></i> Cetak
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] border-collapse text-left text-sm">
                    <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr class="border-b-2 border-slate-200/80">
                            <th class="w-10 px-3 py-3 text-center">#</th>
                            <th class="px-4 py-3">Nama Siswa</th>
                            <th class="w-28 px-3 py-3 text-center">NIS</th>
                            <th class="w-14 bg-emerald-50 px-2 py-3 text-center text-emerald-700">H</th>
                            <th class="w-14 bg-yellow-50 px-2 py-3 text-center text-yellow-700">S</th>
                            <th class="w-14 bg-sky-50 px-2 py-3 text-center text-sky-700">I</th>
                            <th class="w-14 bg-red-50 px-2 py-3 text-center text-red-700">A</th>
                            <th class="w-14 bg-teal-50 px-2 py-3 text-center text-teal-700">BDR</th>
                            <th class="w-14 bg-slate-50 px-2 py-3 text-center">T</th>
                            <th class="w-16 px-2 py-3 text-center">Total</th>
                            <th class="w-32 px-4 py-3 text-center">% Hadir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(r, idx) in rekapRows" :key="r.nama + '-' + idx">
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-3 py-2 text-center font-mono text-xs text-slate-400" x-text="idx + 1"></td>
                                <td class="px-4 py-2 font-semibold text-slate-800" :title="r.nama" x-text="r.nama || '-'"></td>
                                <td class="px-3 py-2 text-center font-mono text-xs text-slate-400" x-text="r.nis || '—'"></td>
                                <td class="px-2 py-2 text-center font-bold tabular-nums text-emerald-600" x-text="r.hadir"></td>
                                <td class="px-2 py-2 text-center font-bold tabular-nums text-yellow-600" x-text="r.sakit"></td>
                                <td class="px-2 py-2 text-center font-bold tabular-nums text-sky-600" x-text="r.izin"></td>
                                <td class="px-2 py-2 text-center font-bold tabular-nums text-red-600" x-text="r.alpa"></td>
                                <td class="px-2 py-2 text-center font-bold tabular-nums text-teal-600" x-text="r.bdr"></td>
                                <td class="px-2 py-2 text-center font-bold tabular-nums text-slate-400" x-text="r.tanpa"></td>
                                <td class="px-2 py-2 text-center font-bold tabular-nums text-slate-700" x-text="r.total"></td>
                                <td class="min-w-[100px] px-4 py-2" :title="'Dihitung dari ' + r.hadir + '/' + r.efektif + ' hari (hari BDR dikecualikan)'">
                                    <div class="flex items-center gap-1.5">
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full" :class="r.bar" :style="'width:' + r.persen + '%'"></div>
                                        </div>
                                        <span class="text-xs font-bold" :class="r.teks" x-text="r.persen + '%'"></span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('presensiSiswaPage', (config) => {
        const timers = {};

        return {
            tab: 'absen',
            idKelas: '',
            tgl: config.hariIni,
            hariIni: config.hariIni,
            jamMasukDefault: config.jamMasuk,
            jamPulangDefault: config.jamPulang,
            memuat: false,
            menyimpan: false,
            query: '',
            rows: [],
            bdrJudul: '',

            rekapKelas: '',
            rekapBulan: '{{ date('n') }}',
            rekapTahun: '{{ date('Y') }}',
            memuatRekap: false,
            rekapRows: [],

            pilihanStatus: [
                { kode: 'HADIR', label: 'Hadir', aktif: 'border-emerald-500 bg-emerald-100 text-emerald-700 shadow-xs' },
                { kode: 'SAKIT', label: 'Sakit', aktif: 'border-yellow-500 bg-yellow-100 text-yellow-700' },
                { kode: 'IZIN', label: 'Izin', aktif: 'border-sky-500 bg-sky-100 text-sky-700' },
                { kode: 'ALPA', label: 'Alpa', aktif: 'border-red-500 bg-red-100 text-red-700 shadow-xs' },
                { kode: 'BDR', label: 'BDR', aktif: 'border-teal-500 bg-teal-100 text-teal-700' },
                { kode: 'TANPA_KETERANGAN', label: '-/-', aktif: 'border-slate-400 bg-slate-100 text-slate-500' },
            ],

            get namaKelas() {
                const opsi = document.querySelector('#id_kelas option[value="' + CSS.escape(this.idKelas) + '"]');

                return opsi ? opsi.textContent.trim() : '';
            },

            get labelTanggal() {
                return this.formatTanggal(this.tgl);
            },

            get daftarTampil() {
                const kata = this.query.trim().toLowerCase();

                if (! kata) {
                    return this.rows;
                }

                return this.rows.filter((s) => ((s.nama || '') + ' ' + (s.nis || '')).toLowerCase().includes(kata));
            },

            get hitung() {
                const hasil = { HADIR: 0, SAKIT: 0, IZIN: 0, ALPA: 0, BDR: 0, TANPA_KETERANGAN: 0 };

                this.rows.forEach((s) => {
                    if (hasil[s.status] !== undefined) {
                        hasil[s.status]++;
                    }
                });

                return hasil;
            },

            get judulRekap() {
                const bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                const opsi = document.querySelector('#rekap_kelas option[value="' + CSS.escape(this.rekapKelas) + '"]');
                const nama = opsi ? opsi.textContent.trim() : '';

                return 'Rekap Kehadiran — ' + nama + ' — ' + bulan[parseInt(this.rekapBulan)] + ' ' + this.rekapTahun;
            },

            init() {
                try {
                    const kelasTerakhir = localStorage.getItem('presensi_siswa_kelas');
                    const rekapTerakhir = localStorage.getItem('presensi_siswa_rekap_kelas');

                    if (kelasTerakhir && document.querySelector('#id_kelas option[value="' + CSS.escape(kelasTerakhir) + '"]')) {
                        this.idKelas = kelasTerakhir;
                    }

                    if (rekapTerakhir && document.querySelector('#rekap_kelas option[value="' + CSS.escape(rekapTerakhir) + '"]')) {
                        this.rekapKelas = rekapTerakhir;
                    }
                } catch (e) {}

                this.$watch('idKelas', (nilai) => {
                    try { localStorage.setItem('presensi_siswa_kelas', nilai); } catch (e) {}
                });

                this.$watch('rekapKelas', (nilai) => {
                    try { localStorage.setItem('presensi_siswa_rekap_kelas', nilai); } catch (e) {}
                });
            },

            // ── Jam default mengikuti jadwal hari terpilih ──
            jamValid(nilai) {
                return /^([01]\d|2[0-3]):[0-5]\d$/.test(String(nilai || ''));
            },

            defaultJam(tipe) {
                let cadangan = tipe === 'pulang' ? config.jamPulang : config.jamMasuk;

                if (! this.jamValid(cadangan)) {
                    cadangan = tipe === 'pulang' ? '14:00' : '07:15';
                }

                if (! /^\d{4}-\d{2}-\d{2}$/.test(this.tgl || '')) {
                    return cadangan;
                }

                const tanggal = this.parseTanggal(this.tgl);
                const iso = ((tanggal.getDay() + 6) % 7) + 1; // Senin=1 … Minggu=7
                const baris = config.jadwal[iso] || config.jadwal[String(iso)];
                const nilai = baris ? baris[tipe === 'pulang' ? 'pulang' : 'masuk'] : '';

                return this.jamValid(nilai) ? nilai : cadangan;
            },

            segarkanJamDefault() {
                this.jamMasukDefault = this.defaultJam('masuk');
                this.jamPulangDefault = this.defaultJam('pulang');
            },

            // ── Ambil data ──
            async kirim(url, data) {
                const body = new FormData();

                Object.entries(data).forEach(([kunci, nilai]) => body.append(kunci, nilai));

                const response = await fetch(url, {
                    method: 'POST',
                    body: body,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': config.token },
                });

                return response.json();
            },

            async muatSiswa() {
                if (! this.idKelas) {
                    Swal.fire('Perhatian', 'Pilih kelas terlebih dahulu', 'warning');

                    return;
                }

                if (! this.tgl) {
                    Swal.fire('Perhatian', 'Pilih tanggal terlebih dahulu', 'warning');

                    return;
                }

                this.segarkanJamDefault();
                this.muatBdr();

                this.memuat = true;
                this.rows = [];
                this.bdrJudul = '';

                try {
                    const res = await this.kirim(config.urls.siswa, { id_kelas: this.idKelas });

                    if (! res || ! res.status || ! res.data.length) {
                        Swal.fire('Info', (res && res.message) || 'Tidak ada siswa di kelas ini', 'info');

                        return;
                    }

                    this.rows = res.data.map((s) => ({
                        ...s,
                        status: 'TANPA_KETERANGAN',
                        jamMasuk: '',
                        jamKeluar: '',
                        keterangan: '',
                        fotoGagal: false,
                    }));

                    await this.muatPresensiTersimpan();
                } catch (e) {
                    Swal.fire('Error', 'Gagal memuat data siswa', 'error');
                } finally {
                    this.memuat = false;
                }
            },

            async muatBdr() {
                try {
                    const res = await this.kirim(config.urls.bdr, { tgl: this.tgl });
                    const judul = res && res.status && res.data ? res.data[String(this.idKelas)] : null;
                    this.bdrJudul = judul || '';
                } catch (e) {
                    this.bdrJudul = '';
                }
            },

            async muatPresensiTersimpan() {
                try {
                    const res = await this.kirim(config.urls.presensi, { id_kelas: this.idKelas, tgl: this.tgl });

                    if (! res.status || ! res.data.length) {
                        return;
                    }

                    res.data.forEach((p) => {
                        const baris = this.rows.find((s) => String(s.id_siswa) === String(p.id_siswa));

                        if (! baris) {
                            return;
                        }

                        if (p.status) {
                            baris.status = p.status;
                        }

                        if (p.jam_masuk) {
                            baris.jamMasuk = String(p.jam_masuk).substring(0, 5);
                        }

                        if (p.jam_keluar) {
                            baris.jamKeluar = String(p.jam_keluar).substring(0, 5);
                        }

                        if (p.keterangan) {
                            baris.keterangan = p.keterangan;
                        }
                    });
                } catch (e) {}
            },

            // ── Status ──
            setStatus(baris, status) {
                baris.status = status;

                // Otomatis isi jam masuk + pulang default (/settings) bila belum ada.
                if (status === 'HADIR') {
                    if (! baris.jamMasuk) {
                        baris.jamMasuk = this.defaultJam('masuk');
                    }

                    if (! baris.jamKeluar) {
                        let jamKeluar = this.defaultJam('pulang');

                        if (baris.jamMasuk && jamKeluar < baris.jamMasuk) {
                            jamKeluar = baris.jamMasuk;
                        }

                        baris.jamKeluar = jamKeluar;
                    }
                }
            },

            tandaiSemua(status) {
                const sudahBerstatus = this.rows.filter((s) => (s.status || 'TANPA_KETERANGAN') !== 'TANPA_KETERANGAN').length;

                const terapkan = () => this.rows.forEach((s) => this.setStatus(s, status));

                if (sudahBerstatus > 0) {
                    Swal.fire({
                        title: 'Tandai Semua?',
                        html: '<strong>' + sudahBerstatus + ' siswa</strong> sudah punya status (termasuk izin/sakit) dan akan ikut tertimpa.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '{{ \App\Libraries\Theme::metaColor('primary') }}',
                        confirmButtonText: 'Ya, Timpa Semua',
                        cancelButtonText: 'Batal',
                    }).then((hasil) => {
                        if (hasil.isConfirmed) {
                            terapkan();
                        }
                    });

                    return;
                }

                terapkan();
            },

            // ── Ubah jam satu siswa (debounce 400ms) ──
            ubahJam(baris, tipe, nilai) {
                if (! this.idKelas || ! this.tgl || ! nilai) {
                    return;
                }

                if (! /^\d{2}:\d{2}(:\d{2})?$/.test(nilai)) {
                    return;
                }

                if (tipe === 'masuk') {
                    baris.jamMasuk = nilai;
                } else {
                    baris.jamKeluar = nilai;
                }

                const kunci = baris.id_siswa + '-' + tipe;
                clearTimeout(timers[kunci]);

                timers[kunci] = setTimeout(async () => {
                    const data = { id_siswa: baris.id_siswa, id_kelas: this.idKelas, tgl: this.tgl };
                    data[tipe === 'masuk' ? 'jam_masuk' : 'jam_keluar'] = nilai;

                    try {
                        const res = await this.kirim(config.urls.updateJam, data);

                        if (res && res.promoted) {
                            // Baris TANPA_KETERANGAN yang diisi jam → otomatis HADIR
                            this.setStatus(baris, 'HADIR');
                        }

                        if (res && ! res.status && res.message) {
                            // Belum ada baris presensi → buat via check-in (khusus jam masuk)
                            if (tipe === 'masuk' && /tidak ditemukan/i.test(res.message)) {
                                const ci = await this.kirim(config.urls.checkIn, {
                                    id_siswa: baris.id_siswa,
                                    id_kelas: this.idKelas,
                                    tgl: this.tgl,
                                    jam_masuk: nilai,
                                });

                                if (ci && ci.status) {
                                    this.setStatus(baris, 'HADIR');
                                } else if (ci && ci.message) {
                                    Swal.fire('Gagal', ci.message, 'error');
                                }

                                return;
                            }

                            Swal.fire('Gagal', res.message, 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error', 'Gagal menyimpan jam', 'error');
                    }
                }, 400);
            },

            // ── Simpan batch ──
            simpanSemua() {
                if (! this.idKelas || ! this.tgl) {
                    Swal.fire('Perhatian', 'Kelas dan tanggal wajib diisi', 'warning');

                    return;
                }

                if (! this.rows.length) {
                    Swal.fire('Perhatian', 'Belum ada data siswa untuk disimpan', 'warning');

                    return;
                }

                Swal.fire({
                    title: 'Simpan Absensi?',
                    html: 'Menyimpan absensi <strong>' + this.rows.length + ' siswa</strong> pada <strong>' + this.tgl + '</strong>',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '{{ \App\Libraries\Theme::metaColor('primary') }}',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Simpan',
                    cancelButtonText: 'Batal',
                }).then(async (hasil) => {
                    if (! hasil.isConfirmed) {
                        return;
                    }

                    this.menyimpan = true;

                    const data = new FormData();
                    data.append('id_kelas', this.idKelas);
                    data.append('tgl', this.tgl);
                    data.append('jam_masuk', this.jamMasukDefault);

                    this.rows.forEach((s, index) => {
                        data.append('presensi[' + index + '][id_siswa]', s.id_siswa);
                        data.append('presensi[' + index + '][status]', s.status || 'TANPA_KETERANGAN');
                        data.append('presensi[' + index + '][keterangan]', (s.keterangan || '').trim());
                        data.append('presensi[' + index + '][jam_masuk]', s.jamMasuk || '');
                        data.append('presensi[' + index + '][jam_keluar]', s.jamKeluar || '');
                    });

                    try {
                        const response = await fetch(config.urls.simpan, {
                            method: 'POST',
                            body: data,
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': config.token },
                        });
                        const res = await response.json();

                        if (res.status) {
                            Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 2000, showConfirmButton: false });
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    } catch (e) {
                        Swal.fire('Error', 'Gagal menghubungi server', 'error');
                    } finally {
                        this.menyimpan = false;
                    }
                });
            },

            // ── Rekap bulanan ──
            async muatRekap() {
                if (! this.rekapKelas) {
                    Swal.fire('Perhatian', 'Pilih kelas terlebih dahulu', 'warning');

                    return;
                }

                this.memuatRekap = true;
                this.rekapRows = [];

                try {
                    const res = await this.kirim(config.urls.rekap, {
                        id_kelas: this.rekapKelas,
                        bulan: this.rekapBulan,
                        tahun: this.rekapTahun,
                    });

                    if (! res.status || ! res.data.length) {
                        Swal.fire('Info', 'Belum ada data absensi untuk periode ini', 'info');

                        return;
                    }

                    this.rekapRows = res.data.map((s) => {
                        const total = parseInt(s.total) || 0;
                        const hadir = parseInt(s.hadir) || 0;
                        const bdr = parseInt(s.bdr) || 0;
                        // BDR tidak menurunkan persentase: hari BDR dikeluarkan dari pembagi.
                        const efektif = total - bdr;
                        const persen = efektif > 0 ? Math.round((hadir / efektif) * 100) : 0;

                        return {
                            nama: s.nama,
                            nis: s.nis,
                            hadir: s.hadir,
                            sakit: s.sakit,
                            izin: s.izin,
                            alpa: s.alpa,
                            bdr: s.bdr ?? bdr,
                            tanpa: s.tanpa_keterangan,
                            total: total,
                            efektif: efektif,
                            persen: persen,
                            bar: persen >= 75 ? 'bg-emerald-500' : (persen >= 50 ? 'bg-amber-400' : 'bg-red-500'),
                            teks: persen >= 75 ? 'text-emerald-600' : (persen >= 50 ? 'text-amber-500' : 'text-red-500'),
                        };
                    });
                } catch (e) {
                    Swal.fire('Error', 'Gagal memuat rekap', 'error');
                } finally {
                    this.memuatRekap = false;
                }
            },

            // ── Tanggal ──
            tanggalBerubah() {
                if (this.tgl > this.hariIni) {
                    this.tgl = this.hariIni;
                }

                this.segarkanJamDefault();

                if (this.idKelas && this.rows.length) {
                    this.muatPresensiTersimpan();
                }
            },

            // ── Helper ──
            fotoSiswa(s) {
                if (s.fotoGagal) {
                    return null;
                }

                if (s.foto_url) {
                    return s.foto_url;
                }

                let f = String(s.foto || '').trim();

                if (! f || ['siswa.png', 'default.png', 'null'].includes(f.toLowerCase())) {
                    return null;
                }

                if (/^https?:\/\//i.test(f)) {
                    return f;
                }

                if (f.startsWith('/')) {
                    return appUrl + f.replace(/^\//, '');
                }

                if (f.startsWith('uploads/')) {
                    return appUrl + f;
                }

                return f.includes('.') ? appUrl + 'uploads/foto_siswa/' + f : null;
            },

            inisial(nama) {
                const bagian = String(nama || '').trim().split(/\s+/).filter(Boolean);

                if (! bagian.length) {
                    return '?';
                }

                return (bagian[0][0] + (bagian[1] ? bagian[1][0] : '')).toUpperCase();
            },

            warnaAvatar(nama) {
                const warna = ['bg-indigo-500', 'bg-violet-500', 'bg-sky-500', 'bg-teal-500', 'bg-emerald-500', 'bg-amber-500', 'bg-rose-500', 'bg-pink-500'];
                let total = 0;

                for (const huruf of String(nama || '')) {
                    total += huruf.codePointAt(0);
                }

                return warna[total % warna.length];
            },

            parseTanggal(str) {
                const bagian = String(str || '').split('-').map(Number);

                if (bagian.length !== 3 || bagian.some(isNaN)) {
                    return new Date();
                }

                return new Date(bagian[0], bagian[1] - 1, bagian[2]);
            },

            formatTanggal(str) {
                const d = this.parseTanggal(str);

                return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
            },
        };
    });
});
</script>
@endsection
