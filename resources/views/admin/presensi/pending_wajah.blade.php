@extends('layouts.main')

@section('content')
@php
    $avatarFallback = \App\Services\AvatarService::cadangan();

    $barisPending = [];
    foreach ($pending as $p) {
        $namaWajah = $p->nama_guru ?: trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
        $namaWajah = trim((string) $namaWajah) ?: 'Guru';

        $barisPending[] = [
            'id' => (int) $p->id,
            'cari' => mb_strtolower(implode(' ', array_filter([
                $namaWajah,
                $p->username ?? '',
                $p->nip ?? '',
                !empty($p->created_at) ? date('d/m/Y H:i', strtotime($p->created_at)) : '',
            ]))),
            'nama' => $namaWajah,
            'username' => (string) ($p->username ?? ''),
            'nip' => (string) ($p->nip ?? ''),
            'avatar' => \App\Services\AvatarService::tampilan((string) ($p->foto ?? ''), $namaWajah, '#2563eb'),
            'avatarFallback' => $avatarFallback,
            'tanggal' => !empty($p->created_at) ? date('d/m/Y H:i', strtotime($p->created_at)) : '—',
        ];
    }

    $barisRiwayat = [];
    foreach ($history ?? [] as $h) {
        $namaWajah = $h->nama_guru ?: trim(($h->first_name ?? '') . ' ' . ($h->last_name ?? ''));
        $namaWajah = trim((string) $namaWajah) ?: 'Guru';
        $disetujui = $h->status === 'approved';
        $admin = trim((string) ($h->admin_first ?? '') . ' ' . (string) ($h->admin_last ?? ''));

        $barisRiwayat[] = [
            'id' => (int) $h->id,
            'cari' => mb_strtolower(implode(' ', array_filter([
                $namaWajah,
                $h->username ?? '',
                $h->nip ?? '',
                $admin,
                $h->keterangan ?? '',
            ]))),
            'nama' => $namaWajah,
            'username' => (string) ($h->username ?? ''),
            'avatar' => \App\Services\AvatarService::tampilan((string) ($h->foto ?? ''), $namaWajah, '#64748b'),
            'avatarFallback' => $avatarFallback,
            'tanggal' => !empty($h->updated_at) ? date('d/m/Y H:i', strtotime($h->updated_at)) : '—',
            'status' => $disetujui
                ? ['kode' => 'approved', 'label' => 'Disetujui', 'ikon' => 'bi-check-lg', 'kelas' => 'bg-emerald-50 text-emerald-700']
                : ['kode' => 'rejected', 'label' => 'Ditolak', 'ikon' => 'bi-x-lg', 'kelas' => 'bg-rose-50 text-rose-700'],
            'admin' => $admin,
            'keterangan' => trim((string) ($h->keterangan ?? '')),
        ];
    }

    // Kelas Tailwind ditulis utuh (bukan dirakit) agar ikut ter-scan saat build.
    $kpi = [
        [
            'label' => 'Menunggu', 'nilai' => $stats['pending'] ?? 0, 'satuan' => 'pengajuan',
            'ikon' => 'bi-hourglass-split', 'ket' => 'Perlu persetujuan admin',
            'aksen' => 'border-l-amber-500', 'ikonKelas' => 'bg-amber-50 text-amber-600',
        ],
        [
            'label' => 'Disetujui Hari Ini', 'nilai' => $stats['approved_today'] ?? 0, 'satuan' => 'wajah',
            'ikon' => 'bi-check-circle', 'ket' => 'Descriptor wajah diperbarui',
            'aksen' => 'border-l-emerald-500', 'ikonKelas' => 'bg-emerald-50 text-emerald-600',
        ],
        [
            'label' => 'Belum Punya Wajah', 'nilai' => $stats['belum_wajah'] ?? 0, 'satuan' => 'guru',
            'ikon' => 'bi-person-badge', 'ket' => 'Belum registrasi biometrik',
            'aksen' => 'border-l-blue-500', 'ikonKelas' => 'bg-blue-50 text-blue-600',
        ],
        [
            'label' => 'Total Guru Aktif', 'nilai' => $stats['total_guru'] ?? 0, 'satuan' => 'orang',
            'ikon' => 'bi-people', 'ket' => 'Guru aktif terdaftar',
            'aksen' => 'border-l-indigo-500', 'ikonKelas' => 'bg-indigo-50 text-indigo-600',
        ],
    ];

    $config = [
        'pending' => $barisPending,
        'riwayat' => $barisRiwayat,
        'urls' => [
            'approve' => url('admin/presensi/wajah/approve'),
            'reject' => url('admin/presensi/wajah/reject'),
            'hapus' => url('admin/presensi/wajah/delete'),
            'bersihkan' => url('admin/presensi/wajah/clear-history'),
        ],
        'csrf' => csrf_token(),
    ];
@endphp

<div class="space-y-4" x-data="pendingWajah(@js($config))">

    <x-ui.page-header title="{{ $judul ?? 'Pengajuan Registrasi Ulang Wajah' }}" icon="bi-camera-fill" tone="blue"
        subtitle="Persetujuan perubahan data wajah guru sebelum diterapkan ke verifikasi absensi."
        :crumbs="['Presensi', 'Verifikasi Wajah']">
        <x-slot:actions>
            <a href="{{ url('admin/presensi') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- KPI ringkas --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($kpi as $item)
            <x-ui.card body-class="p-4" class="border-l-4 {{ $item['aksen'] }}">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-xl {{ $item['ikonKelas'] }}">
                        <i class="bi {{ $item['ikon'] }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $item['label'] }}</p>
                        <div class="mt-0.5 flex items-baseline gap-2">
                            <p class="text-2xl font-extrabold tabular-nums text-slate-900">{{ number_format($item['nilai']) }}</p>
                            <span class="text-[10px] font-semibold text-slate-400">{{ $item['satuan'] }}</span>
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
                <div class="inline-flex items-center gap-1 rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Tab verifikasi wajah">
                    <button type="button" role="tab" :aria-selected="tab === 'pending' ? 'true' : 'false'"
                        @click="tab = 'pending'"
                        :class="tab === 'pending' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-500 hover:text-slate-700'"
                        class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-xs font-bold transition-colors">
                        <i class="bi bi-hourglass-split"></i> Menunggu
                        <span class="rounded-full px-1.5 py-0.5 text-[10px] font-extrabold tabular-nums"
                            :class="tab === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-200 text-slate-500'"
                            x-text="semuaPending.length"></span>
                    </button>
                    <button type="button" role="tab" :aria-selected="tab === 'riwayat' ? 'true' : 'false'"
                        @click="tab = 'riwayat'"
                        :class="tab === 'riwayat' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-500 hover:text-slate-700'"
                        class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-xs font-bold transition-colors">
                        <i class="bi bi-clock-history"></i> Riwayat
                        <span class="rounded-full px-1.5 py-0.5 text-[10px] font-extrabold tabular-nums"
                            :class="tab === 'riwayat' ? 'bg-slate-100 text-slate-600' : 'bg-slate-200 text-slate-500'"
                            x-text="semuaRiwayat.length"></span>
                    </button>
                </div>

                <div class="relative w-full sm:ml-auto sm:w-64">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                    <input type="text" x-model="cari" x-ref="cari" autocomplete="off"
                        placeholder="Cari nama / NIP / username..."
                        aria-label="Cari pengajuan wajah"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-8 text-xs font-semibold text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    <button type="button" x-show="cari !== ''" x-cloak @click="cari = ''; $refs.cari.focus()"
                        class="absolute right-2 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-200/70 hover:text-slate-700"
                        title="Bersihkan pencarian">
                        <i class="bi bi-x text-xs"></i>
                    </button>
                </div>
            </div>
        </x-slot:toolbar>

        {{-- ══════════ TAB MENUNGGU ══════════ --}}
        <div x-show="tab === 'pending'" role="tabpanel" aria-label="Pengajuan menunggu" class="max-h-[62vh] overflow-auto" tabindex="0">
            <table class="w-full text-[13px]">
                <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="w-10 px-3 py-3 text-center font-bold">#</th>
                        <th class="min-w-[240px] px-3 py-3 text-left font-bold">Nama Guru</th>
                        <th class="min-w-[150px] px-3 py-3 text-left font-bold">NIP</th>
                        <th class="min-w-[160px] px-3 py-3 text-left font-bold">Tanggal Pengajuan</th>
                        <th class="min-w-[170px] px-3 py-3 text-center font-bold">Status Wajah Lama</th>
                        <th class="min-w-[170px] px-3 py-3 text-center font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr x-show="barisPending.length === 0">
                        <td colspan="6" class="px-3 py-12 text-center">
                            <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-2xl text-emerald-500">
                                <i class="bi bi-check-circle"></i>
                            </span>
                            <p class="text-sm font-bold text-slate-700"
                                x-text="semuaPending.length === 0 ? 'Tidak Ada Pengajuan Menunggu' : 'Tidak ada data yang cocok'"></p>
                            <p class="mt-1 text-xs text-slate-400"
                                x-text="semuaPending.length === 0 ? 'Semua pengajuan registrasi ulang wajah telah diproses.' : 'Coba ubah kata kunci pencarian.'"></p>
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
                                        <p class="mt-0.5 truncate text-[11px] text-slate-400" x-text="'@' + (row.username || '—')"></p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-3 py-2.5 font-mono text-xs text-slate-600" x-text="row.nip || '—'"></td>

                            <td class="whitespace-nowrap px-3 py-2.5">
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700">
                                    <i class="bi bi-clock text-slate-400"></i>
                                    <span class="tabular-nums" x-text="row.tanggal"></span>
                                </span>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-700">
                                    <i class="bi bi-exclamation-triangle"></i> Sudah pernah daftar
                                </span>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" @click="setujui(row)" :disabled="memproses"
                                        class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg bg-emerald-600 px-2.5 py-1.5 text-[11px] font-bold text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                                        title="Setujui & aktifkan wajah baru">
                                        <i class="bi" :class="idProses === row.id ? 'bi-arrow-repeat animate-spin' : 'bi-check-lg'"></i>
                                        Setujui
                                    </button>
                                    <button type="button" @click="bukaTolak(row)" :disabled="memproses"
                                        class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg border border-rose-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-rose-600 transition-colors hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        title="Tolak, wajah lama tetap berlaku">
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
        <div x-show="tab === 'riwayat'" x-cloak role="tabpanel" aria-label="Riwayat pengajuan" class="max-h-[62vh] overflow-auto" tabindex="0">
            <table class="w-full text-[13px]">
                <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="min-w-[220px] px-3 py-3 text-left font-bold">Guru</th>
                        <th class="min-w-[160px] px-3 py-3 text-left font-bold">Tanggal Diproses</th>
                        <th class="w-[120px] px-3 py-3 text-center font-bold">Status</th>
                        <th class="min-w-[220px] px-3 py-3 text-left font-bold">Diproses Oleh / Catatan</th>
                        <th class="w-[80px] px-3 py-3 text-center font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr x-show="barisRiwayat.length === 0">
                        <td colspan="5" class="px-3 py-12 text-center">
                            <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl text-slate-400">
                                <i class="bi bi-inbox"></i>
                            </span>
                            <p class="text-sm font-bold text-slate-700"
                                x-text="semuaRiwayat.length === 0 ? 'Belum Ada Riwayat' : 'Tidak ada riwayat yang cocok'"></p>
                            <p class="mt-1 text-xs text-slate-400"
                                x-text="semuaRiwayat.length === 0 ? 'Pengajuan wajah yang diproses akan muncul di sini.' : 'Coba ubah kata kunci pencarian.'"></p>
                        </td>
                    </tr>

                    <template x-for="row in barisRiwayat" :key="row.id">
                        <tr class="transition-colors hover:bg-slate-50/60">
                            <td class="px-3 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <img :src="row.avatar" :alt="row.nama" loading="lazy"
                                        x-on:error="$el.src = row.avatarFallback"
                                        class="h-9 w-9 shrink-0 rounded-xl bg-slate-100 object-cover ring-1 ring-slate-200">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold leading-tight text-slate-800" :title="row.nama" x-text="row.nama"></p>
                                        <p class="mt-0.5 truncate text-[11px] text-slate-400" x-text="'@' + (row.username || '—')"></p>
                                    </div>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-2.5">
                                <span class="inline-flex items-center gap-1.5 text-xs text-slate-500">
                                    <i class="bi bi-clock-history text-slate-400"></i>
                                    <span class="tabular-nums" x-text="row.tanggal"></span>
                                </span>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold" :class="row.status.kelas">
                                    <i class="bi text-[10px]" :class="row.status.ikon"></i>
                                    <span x-text="row.status.label"></span>
                                </span>
                            </td>

                            <td class="px-3 py-2.5">
                                <p x-show="row.admin" class="flex items-center gap-1.5 text-xs font-semibold text-slate-700">
                                    <i class="bi bi-person-check text-slate-400"></i>
                                    <span class="truncate" x-text="row.admin"></span>
                                </p>
                                <p x-show="row.keterangan" class="mt-0.5 max-w-[240px] truncate text-[11px] text-slate-500" :title="row.keterangan" x-text="row.keterangan"></p>
                                <span x-show="!row.admin && !row.keterangan" class="text-xs text-slate-300">—</span>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <button type="button" @click="hapusRiwayat(row)" :disabled="memproses"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-rose-500 transition-colors hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    title="Hapus riwayat ini">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-400">
                <span x-text="tab === 'pending'
                    ? 'Menampilkan ' + barisPending.length + ' dari ' + semuaPending.length + ' pengajuan'
                    : 'Menampilkan ' + barisRiwayat.length + ' dari ' + semuaRiwayat.length + ' riwayat (maks. 15)'"></span>
                <span x-show="tab === 'pending'" x-cloak class="text-[11px] text-slate-400">Setujui untuk mengaktifkan wajah baru</span>
                <button type="button" x-show="tab === 'riwayat' && semuaRiwayat.length > 0" x-cloak
                    @click="hapusSemua()" :disabled="memproses"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-[11px] font-bold text-rose-600 transition-colors hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-50">
                    <i class="bi bi-trash3"></i> Hapus Riwayat
                </button>
            </div>
        </x-slot:footer>
    </x-ui.card>
</div>

{{-- ══════════ MODAL: TOLAK PENGAJUAN WAJAH ══════════ --}}
<x-ui.modal id="tolakWajahModal" title="Tolak Pengajuan Wajah" icon="bi-x-circle" tone="rose" size="max-w-lg">
    <form @submit.prevent="kirimTolak()">
        <div class="space-y-4 p-5">
            <div class="flex gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3.5 py-2.5 text-xs text-blue-800">
                <i class="bi bi-info-circle-fill mt-0.5 shrink-0"></i>
                <p class="leading-relaxed">
                    Pengajuan milik <strong x-text="tolak.nama || '—'"></strong> akan ditolak dan wajah lama guru tetap berlaku.
                </p>
            </div>

            <div>
                <label for="tolakWajahAlasan" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                    Alasan Penolakan <span class="font-normal normal-case text-slate-400">(opsional, maks 255 karakter)</span>
                </label>
                <textarea id="tolakWajahAlasan" x-model="tolak.alasan" x-ref="alasan" rows="3" maxlength="255"
                    placeholder="Tulis alasan penolakan di sini..."
                    class="w-full resize-none rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"></textarea>
                <p class="mt-1 text-right text-[11px] text-slate-400">
                    <span x-text="tolak.alasan.length"></span>/255 karakter
                </p>
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
    Alpine.data('pendingWajah', (config) => ({
        tab: 'pending',
        cari: '',
        memproses: false,
        idProses: null,
        semuaPending: config.pending || [],
        semuaRiwayat: config.riwayat || [],
        csrf: config.csrf,
        urls: config.urls,
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

        bukaTolak(row) {
            this.tolak = { id: row.id, nama: row.nama, alasan: '' };
            this.$store.modals.open('#tolakWajahModal');
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

        konfirmasi(judul, teks, label, warna) {
            return Swal.fire({
                title: judul,
                text: teks,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: warna,
                cancelButtonColor: '#94a3b8',
                confirmButtonText: label,
                cancelButtonText: 'Batal',
            }).then((hasil) => hasil.isConfirmed);
        },

        /** Satu penjaga "sedang memproses" untuk semua aksi. */
        async jalankanAksi(aksi, id = null) {
            if (this.memproses) {
                return;
            }

            this.memproses = true;
            this.idProses = id;

            try {
                await aksi();
            } finally {
                this.memproses = false;
                this.idProses = null;
            }
        },

        async berhasil(judul, pesan, muatUlang = false) {
            await Swal.fire({ icon: 'success', title: judul, text: pesan, timer: 1500, showConfirmButton: false });

            if (muatUlang) {
                location.reload();
            }
        },

        async setujui(row) {
            const lanjut = await this.konfirmasi(
                'Setujui Pengajuan Ulang Wajah?',
                'Wajah lama ' + row.nama + ' akan diganti. Guru dapat memakai wajah baru untuk verifikasi absensi.',
                'Ya, Setujui',
                '#059669',
            );

            if (!lanjut) {
                return;
            }

            await this.jalankanAksi(async () => {
                const hasil = await this.kirim(this.urls.approve + '/' + row.id, {});

                if (!hasil.ok) {
                    Swal.fire('Gagal Menyetujui', hasil.pesan, 'error');

                    return;
                }

                this.semuaPending = this.semuaPending.filter((item) => item.id !== row.id);
                await this.berhasil('Berhasil!', hasil.pesan, this.semuaPending.length === 0);
            }, row.id);
        },

        async kirimTolak() {
            await this.jalankanAksi(async () => {
                const hasil = await this.kirim(this.urls.reject + '/' + this.tolak.id, {
                    keterangan: this.tolak.alasan.trim(),
                });

                if (!hasil.ok) {
                    Swal.fire('Gagal Menolak', hasil.pesan, 'error');

                    return;
                }

                this.$store.modals.close('#tolakWajahModal');
                this.semuaPending = this.semuaPending.filter((item) => item.id !== this.tolak.id);
                await this.berhasil('Pengajuan Ditolak', hasil.pesan, this.semuaPending.length === 0);
            });
        },

        async hapusRiwayat(row) {
            const lanjut = await this.konfirmasi(
                'Hapus Riwayat Ini?',
                'Baris riwayat pengajuan wajah milik ' + row.nama + ' akan dihapus permanen.',
                'Ya, Hapus',
                '#dc2626',
            );

            if (!lanjut) {
                return;
            }

            await this.jalankanAksi(async () => {
                const hasil = await this.kirim(this.urls.hapus + '/' + row.id, {});

                if (!hasil.ok) {
                    Swal.fire('Gagal Menghapus', hasil.pesan, 'error');

                    return;
                }

                this.semuaRiwayat = this.semuaRiwayat.filter((item) => item.id !== row.id);
                await this.berhasil('Terhapus', hasil.pesan, this.semuaRiwayat.length === 0);
            });
        },

        async hapusSemua() {
            const lanjut = await this.konfirmasi(
                'Hapus Seluruh Riwayat?',
                'Semua riwayat pengajuan yang telah diproses akan dihapus permanen. Pengajuan pending tidak ikut terhapus.',
                'Ya, Hapus Semua',
                '#dc2626',
            );

            if (!lanjut) {
                return;
            }

            await this.jalankanAksi(async () => {
                const hasil = await this.kirim(this.urls.bersihkan, {});

                if (!hasil.ok) {
                    Swal.fire('Gagal Menghapus', hasil.pesan, 'error');

                    return;
                }

                this.semuaRiwayat = [];
                await this.berhasil('Terhapus', hasil.pesan, true);
            });
        },
    }));
});
</script>
@endsection
