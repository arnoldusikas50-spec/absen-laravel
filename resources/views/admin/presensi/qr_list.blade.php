@extends('layouts.main')

@section('content')

<?php
$eventList = collect($events ?? []);
$qrMulai = substr(trim((string) ($setting->qr_scan_mulai ?? '')), 0, 5);
$qrSelesai = substr(trim((string) ($setting->qr_scan_selesai ?? '')), 0, 5);
$pageConfig = [
    'csrf' => csrf_token(),
    'urls' => [
        'generate' => url('admin/presensi/qr/generate'),
        'toggle' => url('admin/presensi/qr/toggle'),
        'delete' => url('admin/presensi/qr/delete'),
        'token' => url('admin/presensi/qr/token'),
    ],
    'defaults' => [
        'tanggal' => date('Y-m-d'),
        'jam_mulai' => $qrMulai !== '' && $qrMulai !== '00:00' ? $qrMulai : '07:00',
        'jam_selesai' => $qrSelesai !== '' && $qrSelesai !== '00:00' ? $qrSelesai : '16:00',
    ],
    'states' => $eventList->mapWithKeys(fn ($e) => [(int) $e->id => (bool) (int) $e->active])->all(),
];
?>

<div x-data="qrEventPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
    class="px-4 pb-6 pt-4 md:px-6 md:pt-6">

    <x-ui.page-header
        :title="$judul ?? 'QR Code Presensi'"
        icon="bi-qr-code-scan"
        tone="blue"
        subtitle="Buat QR acara, tampilkan token rotasi di layar, dan pantau peserta."
        :crumbs="['Presensi', 'QR Code']">
        <x-slot:actions>
            <a href="{{ url('admin/presensi') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button type="button" @click="openGenerate()"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                <i class="bi bi-plus-lg"></i> Buat QR Baru
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mt-4">
        @if ($eventList->isEmpty())
            <x-ui.card title="Daftar Acara / QR Presensi" icon="bi-calendar-event" subtitle="Belum ada event QR yang dibuat." bodyClass="p-5">
                <x-ui.empty icon="bi-qr-code" title="Belum ada acara QR"
                    description="Buat event QR baru untuk mulai mencatat kehadiran lewat pemindaian." />
            </x-ui.card>
        @else
            <div x-data="dataTable({ perPage: 100 })">
                <x-ui.card title="Daftar Acara / QR Presensi" icon="bi-calendar-event"
                    subtitle="Token rotasi wajib ditampilkan di layar; mode statis aman untuk cetak/poster."
                    bodyClass="p-0">
                    <x-slot:meta>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600">
                            {{ $eventList->count() }} acara
                        </span>
                    </x-slot:meta>

                    <x-slot:toolbar>
                        <div class="relative w-full sm:w-72">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                            <input type="text" x-ref="search" x-model="query" placeholder="Cari acara / lokasi..." autocomplete="off"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-8 text-xs font-semibold text-slate-800 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" x-show="query !== ''" x-cloak @click="query = ''; $refs.search.focus()" title="Bersihkan pencarian"
                                class="absolute right-2 top-1/2 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-200/70 hover:text-slate-700">
                                <i class="bi bi-x text-xs"></i>
                            </button>
                        </div>
                    </x-slot:toolbar>

                    <x-ui.table class="min-w-[980px]">
                        <x-slot:head>
                            <th class="w-10 px-4 py-3">#</th>
                            <th class="px-3 py-3">Acara</th>
                            <th class="px-3 py-3">Tanggal</th>
                            <th class="px-3 py-3">Jam</th>
                            <th class="px-3 py-3">Lokasi</th>
                            <th class="px-3 py-3">Mode</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3">Peserta</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </x-slot:head>

                        @foreach ($eventList as $e)
                            <?php
                            $isPast = ($e->tanggal ?? '') !== '' && $e->tanggal < ($today ?? date('Y-m-d'));
                            $jmlPeserta = (int) ($pesertaCounts[(int) $e->id] ?? 0);
                            $qrMode = $e->mode ?? (! empty($e->secret) ? 'rotating' : 'static');
                            $interval = (int) ($e->interval_detik ?? 60);
                            $searchQr = implode(' ', array_filter([
                                $e->nama_acara ?? '',
                                $e->lokasi ?? '',
                                ! empty($e->tanggal) ? date('d/m/Y', strtotime($e->tanggal)) : '',
                                $qrMode,
                                (int) $e->active === 1 ? 'aktif' : 'nonaktif',
                            ]));
                            ?>
                            <tr data-search="{{ $searchQr }}" class="transition-colors hover:bg-slate-50/60">
                                <td class="px-4 py-3"><span data-rownum class="tabular-nums text-xs text-slate-400">{{ $loop->iteration }}</span></td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-xs font-bold text-slate-800">{{ $e->nama_acara }}</span>
                                        @if ($isPast)
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500" title="Tanggal acara sudah lewat">Berlalu</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-xs text-slate-700">
                                    {{ ! empty($e->tanggal) ? date('d/m/Y', strtotime($e->tanggal)) : '-' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 font-mono text-xs tabular-nums text-slate-700">
                                    {{ substr((string) $e->jam_mulai, 0, 5) }} - {{ substr((string) $e->jam_selesai, 0, 5) }}
                                </td>
                                <td class="px-3 py-3 text-xs text-slate-600">{{ $e->lokasi ?? '-' }}</td>
                                <td class="px-3 py-3">
                                    @if ($qrMode === 'rotating')
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700"
                                            title="Token 6 digit berganti tiap {{ $interval }} detik">Rotasi {{ $interval }}s</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600"
                                            title="Token tetap — aman untuk cetak/poster">Statis</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <button type="button" role="switch" @click="toggleEvent({{ (int) $e->id }})"
                                        :aria-checked="aktif[{{ (int) $e->id }}] ? 'true' : 'false'"
                                        :disabled="busyToggle === {{ (int) $e->id }}"
                                        :class="aktif[{{ (int) $e->id }}] ? 'bg-blue-600' : 'bg-slate-300'"
                                        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors disabled:opacity-60"
                                        title="Aktif / nonaktif event">
                                        <span :class="aktif[{{ (int) $e->id }}] ? 'translate-x-[18px]' : 'translate-x-[2px]'"
                                            class="inline-block h-4 w-4 rounded-full bg-white shadow-xs transition-transform"></span>
                                    </button>
                                </td>
                                <td class="px-3 py-3">
                                    <a href="{{ url('admin/presensi/qr/rekap/' . $e->id) }}"
                                        class="inline-flex items-center gap-1 rounded-lg border border-cyan-200 bg-cyan-50 px-2.5 py-1.5 text-[11px] font-bold text-cyan-700 no-underline transition-colors hover:bg-cyan-100"
                                        title="Lihat peserta">
                                        <i class="bi bi-people"></i>{{ $jmlPeserta }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button"
                                            @click="openQr({{ (int) $e->id }}, '{{ $qrMode }}', '{{ url('qr/' . $e->token) }}', @js($e->nama_acara))"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600"
                                            title="Tampilkan QR">
                                            <i class="bi bi-qr-code"></i>
                                        </button>
                                        <button type="button"
                                            @click="hapusEvent({{ (int) $e->id }}, @js($e->nama_acara), {{ $jmlPeserta }})"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200 bg-white text-rose-600 transition-colors hover:bg-rose-50"
                                            title="Hapus event">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>

                    <x-slot:footer>
                        <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400">
                            <span>Menampilkan <span class="font-semibold text-slate-600" x-text="from"></span>–<span class="font-semibold text-slate-600" x-text="to"></span> dari <span class="font-semibold text-slate-600" x-text="total"></span> data</span>
                            <div class="flex items-center gap-1.5" x-show="pages > 1" x-cloak>
                                <button type="button" @click="prev()" :disabled="page === 1"
                                    class="inline-flex h-7 items-center rounded-lg border border-slate-200 bg-white px-2 text-[11px] font-semibold text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-40">Sebelumnya</button>
                                <span class="font-mono text-slate-500" x-text="page + ' / ' + pages"></span>
                                <button type="button" @click="next()" :disabled="page === pages"
                                    class="inline-flex h-7 items-center rounded-lg border border-slate-200 bg-white px-2 text-[11px] font-semibold text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-40">Berikutnya</button>
                            </div>
                        </div>
                    </x-slot:footer>
                </x-ui.card>

                <div x-show="total === 0" x-cloak class="mt-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-6 py-10 text-center">
                    <i class="bi bi-search mb-2 block text-3xl text-slate-300"></i>
                    <p class="text-sm font-semibold text-slate-500">Tidak ada data yang cocok</p>
                    <p class="mt-1 text-xs text-slate-400">Coba ubah kata kunci pencarian</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal Buat QR -->
    <x-ui.modal id="modalGenerate" title="Buat QR Code Baru" icon="bi-qr-code" tone="blue" size="max-w-lg">
        <form x-ref="form" @submit.prevent="submitGenerate()" autocomplete="off" class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <div>
                    <label for="qrNamaAcara" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Nama Acara <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="qrNamaAcara" x-model="form.nama_acara" required minlength="3" maxlength="255"
                        placeholder="Contoh: Rapat Bulanan" autocomplete="off"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div>
                    <label for="qrTanggal" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Tanggal Acara <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" id="qrTanggal" x-model="form.tanggal" required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="qrJamMulai" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Jam Mulai</label>
                        <input type="time" id="qrJamMulai" x-model="form.jam_mulai"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <div>
                        <label for="qrJamSelesai" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Jam Selesai</label>
                        <input type="time" id="qrJamSelesai" x-model="form.jam_selesai"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>

                <div>
                    <label for="qrLokasi" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Lokasi <span class="font-normal normal-case text-slate-400">(opsional)</span>
                    </label>
                    <input type="text" id="qrLokasi" x-model="form.lokasi" maxlength="255" autocomplete="off"
                        placeholder="Aula / Ruang Guru / Online"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="qrMode" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Mode QR</label>
                        <select id="qrMode" x-model="form.mode"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                            <option value="rotating">Rotasi (layar — token ganti otomatis)</option>
                            <option value="static">Statis (cetak/poster)</option>
                        </select>
                    </div>
                    <div x-show="form.mode !== 'static'" x-cloak>
                        <label for="qrInterval" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Interval Rotasi</label>
                        <select id="qrInterval" x-model="form.interval_detik"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                            <option value="30">30 detik</option>
                            <option value="60">60 detik</option>
                            <option value="120">120 detik</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3">
                    <i class="bi bi-info-circle-fill mt-0.5 text-blue-500"></i>
                    <p class="text-[11px] leading-relaxed text-blue-800">Mode rotasi wajib ditampilkan di layar (proyektor/TV) — QR cetak akan kedaluwarsa dalam hitungan detik.</p>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#modalGenerate')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="saving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                    <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!saving" class="bi bi-qr-code"></i> Generate QR
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal Tampil QR -->
    <x-ui.modal id="modalQr" size="max-w-md">
        <x-slot:header>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <h2 class="flex min-w-0 items-center gap-2.5 text-sm font-bold text-slate-800">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white">
                        <i class="bi bi-qr-code text-sm"></i>
                    </span>
                    <span class="truncate" x-text="qrTitle || 'QR Presensi'"></span>
                </h2>
                <button type="button" @click="$store.modals.close('#modalQr')" aria-label="Tutup"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </x-slot:header>

        <div class="flex flex-col items-center gap-4 p-6">
            <div x-ref="qrBox" class="flex min-h-[200px] min-w-[200px] items-center justify-center rounded-xl border border-slate-200 bg-white p-2"></div>
            <p class="text-center text-xs text-slate-400" x-text="qrCountdown"></p>
            <div class="flex items-center gap-2">
                <button type="button" x-show="qrMode !== 'rotating'" x-cloak @click="printQr()"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 transition-colors hover:bg-blue-100">
                    <i class="bi bi-printer"></i> Cetak
                </button>
                <button type="button" @click="$store.modals.close('#modalQr')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Tutup</button>
            </div>
        </div>
    </x-ui.modal>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
// Didaftarkan sebagai fungsi global agar tetap tersedia walau Alpine sudah berjalan
// lebih dulu (mis. saat skrip ini dieksekusi setelah alpine:init).
window.qrEventPage = function () {
    return {
        config: { csrf: '', urls: {}, defaults: {}, states: {} },

        form: { nama_acara: '', tanggal: '', jam_mulai: '', jam_selesai: '', lokasi: '', mode: 'rotating', interval_detik: '60' },
        aktif: {},
        busyToggle: null,
        saving: false,

        qrTitle: '',
        qrMode: 'static',
        qrCountdown: '',
        qrTimer: null,

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');

            this.config = {
                csrf: cfg.csrf || '',
                urls: cfg.urls || {},
                defaults: cfg.defaults || {},
                states: cfg.states || {},
            };
            this.aktif = Object.assign({}, this.config.states);

            const modal = document.getElementById('modalQr');

            if (modal) {
                modal.addEventListener('app:modal-hidden', () => {
                    this.stopQrTimer();
                    this.qrCountdown = '';
                });
            }
        },

        async post(url, entries) {
            const body = new URLSearchParams();
            body.append('_token', this.config.csrf);

            entries.forEach(([key, value]) => body.append(key, value ?? ''));

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
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
                throw new Error((result && (result.message || result.msg)) || 'Terjadi kesalahan komunikasi dengan server.');
            }

            return result;
        },

        async getJson(url) {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            let result = null;

            try {
                result = await response.json();
            } catch (error) {
                result = null;
            }

            if (!response.ok) {
                throw new Error((result && (result.message || result.msg)) || 'Gagal menghubungi server.');
            }

            return result;
        },

        openGenerate() {
            this.form = {
                nama_acara: '',
                tanggal: this.config.defaults.tanggal || '',
                jam_mulai: this.config.defaults.jam_mulai || '07:00',
                jam_selesai: this.config.defaults.jam_selesai || '16:00',
                lokasi: '',
                mode: 'rotating',
                interval_detik: '60',
            };

            this.$store.modals.open('#modalGenerate');
            this.$nextTick(() => {
                const input = document.getElementById('qrNamaAcara');

                if (input) input.focus();
            });
        },

        async submitGenerate() {
            if (this.saving) return;

            const el = this.$refs.form;

            if (el && !el.checkValidity()) {
                el.reportValidity();
                return;
            }

            this.saving = true;

            try {
                const result = await this.post(this.config.urls.generate, [
                    ['nama_acara', (this.form.nama_acara || '').trim()],
                    ['tanggal', this.form.tanggal],
                    ['jam_mulai', this.form.jam_mulai],
                    ['jam_selesai', this.form.jam_selesai],
                    ['lokasi', (this.form.lokasi || '').trim()],
                    ['mode', this.form.mode],
                    ['interval_detik', this.form.mode === 'static' ? 60 : this.form.interval_detik],
                ]);

                if (result && result.status) {
                    this.$store.modals.close('#modalGenerate');
                    await Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message || 'QR Code dibuat', timer: 1300, showConfirmButton: false });
                    window.location.reload();
                    return;
                }

                Swal.fire('Gagal', (result && result.message) || 'Gagal membuat QR', 'error');
            } catch (error) {
                Swal.fire('Gagal', error.message || 'Terjadi kesalahan koneksi server.', 'error');
            } finally {
                this.saving = false;
            }
        },

        async toggleEvent(id) {
            if (this.busyToggle === id) return;

            const sebelumnya = !!this.aktif[id];
            const berikutnya = !sebelumnya;

            this.aktif[id] = berikutnya;
            this.busyToggle = id;

            try {
                const result = await this.post(this.config.urls.toggle, [
                    ['id', id],
                    ['active', berikutnya ? 1 : 0],
                ]);

                if (result && result.status) {
                    Swal.fire({ icon: 'success', title: result.message || 'Status diubah', timer: 1200, showConfirmButton: false });
                    return;
                }

                this.aktif[id] = sebelumnya;
                Swal.fire('Gagal', (result && result.message) || 'Gagal mengubah status', 'error');
            } catch (error) {
                this.aktif[id] = sebelumnya;
                Swal.fire('Gagal', error.message || 'Terjadi kesalahan koneksi server.', 'error');
            } finally {
                this.busyToggle = null;
            }
        },

        async hapusEvent(id, nama, peserta) {
            const konfirmasi = await Swal.fire({
                title: 'Hapus event "' + (nama || '') + '"?',
                text: peserta > 0
                    ? 'Event memiliki ' + peserta + ' riwayat scan dan kemungkinan besar akan ditolak server demi audit.'
                    : 'Event yang belum pernah dipindai akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            try {
                const result = await this.post(this.config.urls.delete, [['id', id]]);

                if (result && result.status) {
                    await Swal.fire({ icon: 'success', title: 'Terhapus!', text: result.message || '', timer: 1300, showConfirmButton: false });
                    window.location.reload();
                    return;
                }

                Swal.fire('Gagal', (result && result.message) || 'Gagal menghapus', 'error');
            } catch (error) {
                Swal.fire('Gagal', error.message || 'Terjadi kesalahan koneksi server.', 'error');
            }
        },

        openQr(id, mode, url, title) {
            this.stopQrTimer();
            this.qrTitle = title || '';
            this.qrMode = mode || 'static';
            this.qrCountdown = '';
            this.$store.modals.open('#modalQr');

            if (this.qrMode !== 'rotating') {
                this.$nextTick(() => this.renderQr(url));
                return;
            }

            this.$nextTick(() => this.muatToken(id));
        },

        async muatToken(id) {
            try {
                const result = await this.getJson(this.config.urls.token + '/' + encodeURIComponent(id));

                if (!result || !result.status) {
                    this.qrCountdown = (result && result.message) || 'Gagal memuat token';
                    return;
                }

                const data = result.data || {};

                this.renderQr(data.url);
                this.qrCountdown = 'Token berganti dalam ~' + (data.expires_in != null ? data.expires_in : '-') + ' dtk';

                const refresh = Number(data.refresh_ms) || 0;

                if (refresh > 0) {
                    this.stopQrTimer();
                    this.qrTimer = setInterval(() => this.muatToken(id), refresh);
                }
            } catch (error) {
                this.qrCountdown = error.message || 'Gagal menghubungi server';
            }
        },

        renderQr(url) {
            const box = this.$refs.qrBox;

            if (!box) return;

            box.innerHTML = '';

            try {
                new QRCode(box, { text: url || '', width: 200, height: 200 });
            } catch (error) {
                const pesan = document.createElement('p');
                pesan.className = 'text-center text-xs text-rose-500';
                pesan.append('Gagal merender QR. Buka tautan manual: ');

                const tautan = document.createElement('a');
                tautan.className = 'break-all text-blue-600 underline';
                tautan.href = url || '#';
                tautan.target = '_blank';
                tautan.rel = 'noopener';
                tautan.textContent = url || '';

                pesan.append(tautan);
                box.append(pesan);
            }
        },

        stopQrTimer() {
            if (this.qrTimer) {
                clearInterval(this.qrTimer);
                this.qrTimer = null;
            }
        },

        printQr() {
            const box = this.$refs.qrBox;

            if (!box) return;

            const w = window.open('', '', 'width=420,height=460');

            if (!w) {
                Swal.fire('Gagal', 'Popup diblokir browser. Izinkan popup untuk mencetak.', 'warning');
                return;
            }

            const doc = w.document;
            const judul = doc.createElement('h3');
            const isi = doc.createElement('div');

            doc.title = this.qrTitle || 'QR Presensi';

            judul.textContent = this.qrTitle || 'QR Presensi';
            judul.style.margin = '0 0 12px';
            judul.style.fontFamily = 'sans-serif';

            isi.id = 'qr-print';
            isi.innerHTML = box.innerHTML;

            doc.body.style.textAlign = 'center';
            doc.body.style.padding = '20px';
            doc.body.append(judul, isi);

            w.focus();
            w.print();
        },
    };
};
</script>
@endsection
