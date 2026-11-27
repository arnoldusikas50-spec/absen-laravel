@extends('layouts.main')

@section('content')

<div x-data="monitoringSiswa('{{ url('presensisiswa/getMonitoringHarian') }}', '{{ $tgl }}', '{{ date('Y-m-d') }}', '{{ csrf_token() }}')"
    x-init="init()" class="fade-in-up space-y-6 p-4 md:p-6">

    {{-- Header --}}
    <div class="flex flex-col justify-between gap-4 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs lg:flex-row lg:items-center print:hidden">
        <div>
            <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700">
                <i class="bi bi-calendar-day"></i> Monitoring Harian
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $judul }}</h1>
            <p class="mt-1 text-xs text-slate-500">{{ $subjudul }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ url('admin/presensi/siswa/rekap') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-sky-50 px-4 py-2.5 text-xs font-bold text-sky-700 no-underline transition-colors hover:bg-sky-100">
                <i class="bi bi-file-earmark-spreadsheet"></i> Rekap Bulanan
            </a>
            <button type="button" @click="unduhPdf()"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                <i class="bi bi-file-earmark-pdf"></i> Unduh PDF
            </button>
            <a href="{{ url('presensisiswa') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white no-underline shadow-xs shadow-indigo-500/20 transition-colors hover:bg-indigo-700">
                <i class="bi bi-pencil-square"></i> Input Absensi
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs print:hidden">
        <div class="flex items-center gap-2 border-b border-slate-100 p-5">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                <i class="bi bi-funnel text-sm"></i>
            </span>
            <h2 class="text-sm font-extrabold text-slate-900">Filter Monitoring</h2>
        </div>

        <div class="flex flex-wrap items-end gap-3 p-5">
            <div>
                <label for="mon-tgl" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Tanggal</label>
                <div class="flex items-center gap-1">
                    <button type="button" @click="geserTanggal(-1)" title="Kemarin"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-xs text-slate-600 transition-colors hover:bg-slate-100">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <input type="date" id="mon-tgl" x-model="tgl" :max="hariIni" @change="tanggalBerubah()"
                        class="h-9 w-[150px] rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 outline-hidden focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20">
                    <button type="button" @click="geserTanggal(1)" :disabled="tgl >= hariIni" title="Besok"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-xs text-slate-600 transition-colors hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <button type="button" @click="pilihHariIni()"
                        class="ml-1 inline-flex h-9 items-center rounded-xl border border-sky-200 bg-sky-50 px-3 text-xs font-bold text-sky-700 transition-colors hover:bg-sky-100">
                        Hari Ini
                    </button>
                </div>
            </div>

            <div>
                <label for="mon-kelas" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Kelas</label>
                <select id="mon-kelas" x-model="kelas" @change="muat()"
                    class="h-9 w-[180px] rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 outline-hidden focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20">
                    <option value="">— Pilih Kelas —</option>
                    @foreach ($kelass as $id => $nama)
                        <option value="{{ $id }}">{{ $nama }}</option>
                    @endforeach
                </select>
            </div>

            <button type="button" @click="muat()" :disabled="! kelas"
                class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-sky-600 px-3.5 text-xs font-bold text-white shadow-xs shadow-sky-500/20 transition-colors hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50">
                <i class="bi" :class="memuat ? 'bi-arrow-repeat animate-spin' : 'bi-arrow-clockwise'"></i> Refresh
            </button>

            <div class="relative ml-0 min-w-[220px] md:ml-auto">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-slate-400">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" x-model="query" placeholder="Cari nama / NIS..." autocomplete="off" aria-label="Cari siswa"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pr-8 pl-8 text-xs font-semibold text-slate-800 outline-hidden focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20">
                <button type="button" x-show="query !== ''" x-cloak @click="query = ''" title="Bersihkan pencarian"
                    class="absolute inset-y-0 right-1 my-auto flex h-6 w-6 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-200/70 hover:text-slate-700">
                    <i class="bi bi-x text-xs"></i>
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 border-t border-slate-100 bg-slate-50/60 px-5 py-3">
            <span class="mr-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">Status</span>
            <template x-for="chip in daftarStatus" :key="chip.kode">
                <button type="button" @click="statusFilter = chip.kode"
                    :class="statusFilter === chip.kode
                        ? 'border-sky-600 bg-sky-600 text-white'
                        : 'border-slate-200 bg-white text-slate-500 hover:border-sky-300 hover:text-sky-700'"
                    class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold transition-colors">
                    <span class="h-2 w-2 rounded-full" :class="statusFilter === chip.kode ? 'bg-white' : chip.dot"></span>
                    <span x-text="chip.label"></span>
                </button>
            </template>
        </div>
    </div>

    {{-- Statistik --}}
    <div x-show="sudahMuatt" x-cloak class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-7">
        <template x-for="kartu in kartuStatistik" :key="kartu.key">
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 border-l-4 bg-white p-4 shadow-xs" :class="kartu.border">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg" :class="kartu.iconBg">
                    <i class="bi" :class="kartu.icon"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400" x-text="kartu.label"></p>
                    <p class="text-xl font-extrabold leading-tight" :class="kartu.teks" x-text="stat[kartu.key] || 0"></p>
                </div>
            </div>
        </template>
    </div>

    {{-- Loading --}}
    <div x-show="memuat" x-cloak class="py-12 text-center">
        <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-sky-500 border-t-transparent"></div>
        <p class="mt-3 text-sm text-slate-400">Memuat data monitoring…</p>
    </div>

    {{-- Empty --}}
    <div x-show="! kelas && ! memuat" class="rounded-3xl border border-slate-200/80 bg-white py-14 text-center shadow-xs">
        <i class="bi bi-calendar-day text-5xl text-slate-300"></i>
        <p class="mt-3 font-medium text-slate-400">Pilih kelas untuk melihat monitoring harian</p>
    </div>

    {{-- Tabel --}}
    <div x-show="kelas && ! memuat" x-cloak class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs">
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 p-5">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                <i class="bi bi-table text-sm"></i>
            </span>
            <h2 class="text-sm font-extrabold text-slate-900" x-text="'Kehadiran — ' + namaKelas"></h2>
            <span class="ml-auto rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500" x-text="labelTanggal"></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] border-collapse text-left text-sm">
                <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr class="border-b-2 border-slate-200/80">
                        <th class="w-10 px-3 py-3 text-center">#</th>
                        <th class="px-4 py-3">Nama Siswa</th>
                        <th class="hidden w-32 px-3 py-3 text-center sm:table-cell">NIS</th>
                        <th class="w-28 px-3 py-3 text-center">Status</th>
                        <th class="hidden w-28 px-3 py-3 text-center md:table-cell">Masuk</th>
                        <th class="hidden w-28 px-3 py-3 text-center md:table-cell">Keluar</th>
                        <th class="hidden px-4 py-3 lg:table-cell">Dicatat Oleh</th>
                        <th class="hidden px-4 py-3 lg:table-cell">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(s, idx) in daftarTampil" :key="s.id_siswa + '-' + idx">
                        <tr class="transition-colors hover:bg-slate-50/60">
                            <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400" x-text="idx + 1"></td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <template x-if="fotoSiswa(s)">
                                        <img :src="fotoSiswa(s)" :alt="inisial(s.nama)" loading="lazy"
                                            x-on:error="s.fotoGagal = true"
                                            class="h-8 w-8 shrink-0 rounded-full object-cover">
                                    </template>
                                    <template x-if="! fotoSiswa(s)">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white"
                                            :class="warnaAvatar(s.nama)" x-text="inisial(s.nama)"></span>
                                    </template>
                                    <span class="truncate text-sm font-semibold text-slate-800" :title="s.nama" x-text="s.nama || '-'"></span>
                                </div>
                            </td>
                            <td class="hidden px-3 py-2.5 text-center font-mono text-xs text-slate-400 sm:table-cell" x-text="s.nis || '—'"></td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold" :class="infoStatus(s.status).badge">
                                    <span class="h-2 w-2 rounded-full" :class="infoStatus(s.status).dot"></span>
                                    <span x-text="infoStatus(s.status).label"></span>
                                </span>
                            </td>
                            <td class="hidden px-3 py-2.5 text-center md:table-cell">
                                <span x-show="s.jam_masuk" class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 font-mono text-[11px] font-bold text-emerald-700">
                                    <i class="bi bi-box-arrow-in-right"></i><span x-text="jamPendek(s.jam_masuk)"></span>
                                </span>
                                <span x-show="! s.jam_masuk" class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-400">—</span>
                            </td>
                            <td class="hidden px-3 py-2.5 text-center md:table-cell">
                                <span x-show="s.jam_keluar" class="inline-flex items-center gap-1 rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 font-mono text-[11px] font-bold text-sky-700">
                                    <i class="bi bi-box-arrow-right"></i><span x-text="jamPendek(s.jam_keluar)"></span>
                                </span>
                                <span x-show="! s.jam_keluar" class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-400"
                                    :title="Number(s.pulang_tidak_tercatat) === 1 ? 'Jam pulang operasional sudah lewat tanpa absen pulang' : ''"
                                    x-text="Number(s.pulang_tidak_tercatat) === 1 ? 'tidak tercatat' : '—'"></span>
                            </td>
                            <td class="hidden max-w-[180px] truncate px-4 py-2.5 text-xs text-slate-400 lg:table-cell" :title="s.nama_guru || ''" x-text="s.nama_guru || '—'"></td>
                            <td class="hidden max-w-[220px] truncate px-4 py-2.5 text-xs text-slate-400 lg:table-cell" :title="s.keterangan || ''" x-text="s.keterangan || '—'"></td>
                        </tr>
                    </template>

                    <tr x-show="daftarTampil.length === 0">
                        <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                            <i class="bi bi-inbox mb-2 block text-3xl"></i>
                            <span x-text="rows.length === 0 ? 'Belum ada data siswa di kelas ini' : 'Tidak ada siswa yang cocok dengan filter'"></span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-5 py-2.5 text-xs text-slate-400 print:hidden">
            <span x-text="'Menampilkan ' + daftarTampil.length + ' dari ' + rows.length + ' siswa'"></span>
            <span x-text="labelTanggal"></span>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('monitoringSiswa', (url, tglAwal, hariIni, token) => ({
        kelas: '',
        tgl: tglAwal,
        hariIni: hariIni,
        memuat: false,
        sudahMuatt: false,
        query: '',
        statusFilter: 'all',
        stat: {},
        rows: [],

        daftarStatus: [
            { kode: 'all', label: 'Semua', dot: 'bg-slate-300' },
            { kode: 'HADIR', label: 'Hadir', dot: 'bg-emerald-500' },
            { kode: 'SAKIT', label: 'Sakit', dot: 'bg-yellow-500' },
            { kode: 'IZIN', label: 'Izin', dot: 'bg-sky-500' },
            { kode: 'ALPA', label: 'Alpa', dot: 'bg-red-500' },
            { kode: 'BDR', label: 'BDR', dot: 'bg-teal-500' },
            { kode: 'TANPA_KETERANGAN', label: 'Belum', dot: 'bg-slate-400' },
        ],

        kartuStatistik: [
            { key: 'total', label: 'Total', icon: 'bi-people-fill', border: 'border-l-slate-400', iconBg: 'bg-slate-100 text-slate-600', teks: 'text-slate-900' },
            { key: 'hadir', label: 'Hadir', icon: 'bi-check-circle-fill', border: 'border-l-emerald-500', iconBg: 'bg-emerald-50 text-emerald-600', teks: 'text-emerald-600' },
            { key: 'sakit', label: 'Sakit', icon: 'bi-thermometer-half', border: 'border-l-yellow-500', iconBg: 'bg-yellow-50 text-yellow-600', teks: 'text-yellow-600' },
            { key: 'izin', label: 'Izin', icon: 'bi-envelope-open', border: 'border-l-sky-500', iconBg: 'bg-sky-50 text-sky-600', teks: 'text-sky-600' },
            { key: 'alpa', label: 'Alpa', icon: 'bi-x-circle-fill', border: 'border-l-red-500', iconBg: 'bg-red-50 text-red-600', teks: 'text-red-600' },
            { key: 'bdr', label: 'BDR', icon: 'bi-house-door-fill', border: 'border-l-teal-500', iconBg: 'bg-teal-50 text-teal-600', teks: 'text-teal-600' },
            { key: 'tanpa', label: 'Belum', icon: 'bi-dash-circle', border: 'border-l-slate-300', iconBg: 'bg-slate-100 text-slate-500', teks: 'text-slate-500' },
        ],

        get namaKelas() {
            const opsi = document.querySelector('#mon-kelas option[value="' + this.kelas + '"]');

            return opsi ? opsi.textContent.trim() : '';
        },

        get labelTanggal() {
            return this.formatTanggal(this.tgl);
        },

        get daftarTampil() {
            const kata = this.query.trim().toLowerCase();
            const status = this.statusFilter;

            return this.rows.filter((s) => {
                const cocokStatus = status === 'all' || (s.status || 'TANPA_KETERANGAN') === status;
                const teks = ((s.nama || '') + ' ' + (s.nis || '')).toLowerCase();

                return cocokStatus && (kata === '' || teks.includes(kata));
            });
        },

        init() {
            try {
                const terakhir = localStorage.getItem('presensi_siswa_mon_kelas');

                if (terakhir && document.querySelector('#mon-kelas option[value="' + CSS.escape(terakhir) + '"]')) {
                    this.kelas = terakhir;
                    this.muat();
                }
            } catch (e) {}
        },

        muat() {
            if (! this.kelas) {
                return;
            }

            try {
                localStorage.setItem('presensi_siswa_mon_kelas', this.kelas);
                localStorage.setItem('presensi_siswa_kelas', this.kelas);
            } catch (e) {}

            this.memuat = true;

            const data = new FormData();
            data.append('id_kelas', this.kelas);
            data.append('tgl', this.tgl);

            fetch(url, {
                method: 'POST',
                body: data,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
            })
                .then((response) => response.json())
                .then((res) => {
                    if (! res || ! res.status) {
                        Swal.fire('Error', (res && res.message) || 'Gagal memuat data', 'error');

                        return;
                    }

                    this.stat = res.stat || {};
                    this.rows = (res.data || []).map((s) => ({ ...s, fotoGagal: false }));
                    this.sudahMuatt = true;
                })
                .catch(() => Swal.fire('Error', 'Gagal menghubungi server', 'error'))
                .finally(() => {
                    this.memuat = false;
                });
        },

        geserTanggal(delta) {
            const d = this.parseTanggal(this.tgl);

            if (! d) {
                return;
            }

            d.setDate(d.getDate() + delta);

            const batas = new Date();
            batas.setHours(0, 0, 0, 0);

            if (d > batas) {
                return;
            }

            this.tgl = this.formatYmd(d);
            this.muat();
        },

        tanggalBerubah() {
            if (this.tgl > this.hariIni) {
                this.tgl = this.hariIni;
            }

            this.muat();
        },

        pilihHariIni() {
            this.tgl = this.hariIni;
            this.muat();
        },

        unduhPdf() {
            if (! this.kelas) {
                Swal.fire('Peringatan', 'Pilih kelas terlebih dahulu untuk mengunduh PDF.', 'warning');

                return;
            }

            window.location.href = '{{ url('admin/presensi/siswa/harian/pdf') }}?id_kelas='
                + encodeURIComponent(this.kelas) + '&tgl=' + encodeURIComponent(this.tgl);
        },

        infoStatus(status) {
            return {
                HADIR: { label: 'Hadir', dot: 'bg-emerald-500', badge: 'bg-emerald-100 text-emerald-700' },
                SAKIT: { label: 'Sakit', dot: 'bg-yellow-500', badge: 'bg-yellow-100 text-yellow-700' },
                IZIN: { label: 'Izin', dot: 'bg-sky-500', badge: 'bg-sky-100 text-sky-700' },
                ALPA: { label: 'Alpa', dot: 'bg-red-500', badge: 'bg-red-100 text-red-700' },
                BDR: { label: 'BDR', dot: 'bg-teal-500', badge: 'bg-teal-100 text-teal-700' },
                TANPA_KETERANGAN: { label: 'Belum', dot: 'bg-slate-400', badge: 'bg-slate-100 text-slate-500' },
            }[status] || { label: 'Belum', dot: 'bg-slate-400', badge: 'bg-slate-100 text-slate-500' };
        },

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

            // Kolom foto kadang berisi NIS mentah tanpa ekstensi — anggap kosong agar tidak 404.
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

        jamPendek(jam) {
            return String(jam || '').substring(0, 5);
        },

        parseTanggal(str) {
            const bagian = String(str || '').split('-').map(Number);

            if (bagian.length !== 3 || bagian.some(isNaN)) {
                return null;
            }

            return new Date(bagian[0], bagian[1] - 1, bagian[2]);
        },

        formatYmd(d) {
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        },

        formatTanggal(str) {
            const d = this.parseTanggal(str);

            if (! d) {
                return str;
            }

            return d.toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
        },
    }));
});
</script>
@endsection
