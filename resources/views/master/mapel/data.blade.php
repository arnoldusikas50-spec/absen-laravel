@extends('layouts.main')

@section('content')

<?php
$pageConfig = [
    'csrf' => csrf_token(),
    'urls' => [
        'read'   => url('datamapel/read'),
        'create' => url('datamapel/create'),
        'update' => url('datamapel/update'),
        'delete' => url('datamapel/delete'),
    ],
];
?>

<div x-data="mapelPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <!-- Content Header -->
    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'Mata Pelajaran'"
            icon="bi-book"
            :subtitle="($subjudul ?? 'Daftar Mata Pelajaran') . ' · ' . (int) ($total_mapel ?? 0) . ' mapel terdaftar'"
            :crumbs="['Data Umum', $judul ?? 'Mata Pelajaran']">
            <x-slot:actions>
                <button type="button" @click="reload()" :disabled="loading"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-xs disabled:opacity-50">
                    <i class="bi bi-arrow-repeat"></i> Reload
                </button>
                <button type="button" @click="deleteSelected()" x-show="selected.length > 0" x-cloak
                    :disabled="saving"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-rose-600 rounded-xl hover:bg-rose-700 transition-colors shadow-xs shadow-rose-500/20 disabled:opacity-50">
                    <i class="bi bi-trash3"></i> Hapus Terpilih
                    <span class="inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-md bg-white/20 text-[10px] font-bold" x-text="selected.length"></span>
                </button>
                <button type="button" @click="openCreate()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-xs shadow-blue-500/20">
                    <i class="bi bi-plus-lg"></i> Tambah Mapel
                </button>
            </x-slot:actions>
        </x-ui.page-header>
    </div>

    <!-- Main Content Area -->
    <div class="mt-4 space-y-5 px-4 pb-6 md:px-6">

        <x-ui.card title="Daftar Mata Pelajaran" icon="bi-journal-text" :body-class="'p-0'"
            subtitle="Kelola mata pelajaran, status keaktifan, dan nomor urut tampil di raport">
            <x-slot:actions>
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="search" x-model="query" placeholder="Cari nama atau kode mapel..." aria-label="Cari mata pelajaran"
                        class="w-full pl-8 pr-8 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden">
                    <button type="button" x-show="query.length > 0" x-cloak @click="query = ''"
                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600"
                        aria-label="Hapus pencarian">
                        <i class="bi bi-x-circle-fill text-xs"></i>
                    </button>
                </div>
            </x-slot:actions>

            <x-slot:toolbar>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                        <label for="mapel-per-page">Tampilkan</label>
                        <select id="mapel-per-page" x-model.number="perPage" @change="page = 1" aria-label="Jumlah data per halaman"
                            class="px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-hidden font-medium text-slate-700">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span>data</span>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-400" x-text="summaryText()"></span>
                </div>
            </x-slot:toolbar>

            <div class="relative">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr class="border-b-2 border-slate-200/80">
                                <th class="w-10 px-3 py-3 text-center">
                                    <input type="checkbox" aria-label="Pilih semua mapel di halaman ini"
                                        :checked="allPageSelected"
                                        @change="toggleAll($event.target.checked)"
                                        class="cursor-pointer rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                </th>
                                <th class="w-16 px-3 py-3 text-center">Urut</th>
                                <th class="px-4 py-3">Mata Pelajaran</th>
                                <th class="w-24 px-3 py-3 text-center">Kode</th>
                                <th class="w-28 px-3 py-3 text-center">Status</th>
                                <th class="w-20 px-3 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <template x-for="row in pageRows" :key="row.id_mapel">
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="px-3 py-2.5 text-center">
                                        <template x-if="row.deletable === '0'">
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 text-slate-400"
                                                title="Mapel bawaan sistem — tidak dapat dihapus">
                                                <i class="bi bi-lock-fill text-xs"></i>
                                            </span>
                                        </template>
                                        <template x-if="row.deletable !== '0'">
                                            <input type="checkbox" x-model="selected" :value="String(row.id_mapel)"
                                                class="cursor-pointer rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                                :aria-label="'Pilih ' + row.nama_mapel">
                                        </template>
                                    </td>
                                    <td class="px-3 py-2.5 text-center font-bold text-slate-400" x-text="row.urutan_tampil"></td>
                                    <td class="px-4 py-2.5">
                                        <div class="font-bold text-slate-800" x-text="row.nama_mapel"></div>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span x-show="row.kode"
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100"
                                            x-text="row.kode"></span>
                                        <span x-show="!row.kode" class="text-xs text-slate-300">-</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span x-show="String(row.status) === '0'"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-100">Nonaktif</span>
                                        <span x-show="String(row.status) !== '0'"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">Aktif</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <button type="button" @click="openEdit(row)" title="Edit Mapel"
                                            :aria-label="'Edit ' + row.nama_mapel"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors shadow-xs">
                                            <i class="bi bi-pencil-square text-sm"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <tr x-show="!loading && pageRows.length === 0">
                                <td colspan="6" class="px-4 py-14 text-center">
                                    <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-slate-50 border border-slate-100 text-slate-400 flex items-center justify-center text-2xl">
                                        <i class="bi" :class="query !== '' ? 'bi-search' : 'bi-inbox'"></i>
                                    </div>
                                    <p class="text-sm font-bold text-slate-700 mb-1"
                                        x-text="query !== '' ? 'Mapel tidak ditemukan' : 'Belum ada mata pelajaran'"></p>
                                    <p class="text-xs text-slate-400 max-w-sm mx-auto mb-0"
                                        x-text="query !== '' ? 'Coba kata kunci lain atau bersihkan pencarian.' : 'Tambahkan mata pelajaran untuk mulai menyusun raport.'"></p>
                                </td>
                            </tr>

                            <tr x-show="loading && rows.length === 0">
                                <td colspan="6" class="px-4 py-14 text-center">
                                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 shadow-sm text-slate-600 text-xs font-semibold">
                                        <span class="inline-block w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></span>
                                        <span>Memuat data mapel...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-3.5 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="text-xs font-medium text-slate-500" aria-live="polite" x-text="summaryText()"></div>
                    <nav aria-label="Navigasi halaman mata pelajaran" class="min-w-0 overflow-x-auto">
                        <ul class="flex items-center gap-1 mb-0" x-show="pages > 1" x-cloak>
                            <li>
                                <button type="button" @click="page = page - 1" :disabled="page <= 1" aria-label="Halaman sebelumnya"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors text-xs">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                            </li>
                            <template x-for="p in pageList()" :key="p">
                                <li>
                                    <button type="button" @click="page = p"
                                        :aria-current="p === page ? 'page' : null"
                                        class="inline-flex items-center justify-center min-w-8 h-8 px-2 rounded-lg border text-xs font-semibold transition-colors"
                                        :class="p === page ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                                        x-text="p"></button>
                                </li>
                            </template>
                            <li>
                                <button type="button" @click="page = page + 1" :disabled="page >= pages" aria-label="Halaman berikutnya"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors text-xs">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </x-ui.card>
    </div>

    <!-- Modal Tambah Mapel -->
    <x-ui.modal id="createMapelModal" title="Tambah Mata Pelajaran" icon="bi-plus-lg" size="max-w-lg">
        <form @submit.prevent="submitCreate()" class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <div>
                    <label for="createnama" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Nama Mata Pelajaran <span class="text-rose-500">*</span></label>
                    <input type="text" id="createnama" x-model="form.nama_mapel" maxlength="50" required
                        placeholder="Contoh: Matematika"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="createkode" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Kode Mapel</label>
                        <input type="text" id="createkode" x-model="form.kode_mapel" maxlength="20"
                            placeholder="Contoh: MTK"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <div>
                        <label for="createurutan" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">No. Urut Rapor</label>
                        <input type="number" id="createurutan" x-model.number="form.urutan_tampil" min="0" max="9999"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>

                <div>
                    <label for="status-create" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Status Keaktifan <span class="text-rose-500">*</span></label>
                    <select id="status-create" x-model="form.status" required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        @foreach ($status as $nilaiStatus => $labelStatus)
                            <option value="{{ $nilaiStatus }}">{{ $labelStatus }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#createMapelModal')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="saving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                    <span x-show="saving" x-cloak class="inline-block w-3.5 h-3.5 border-2 border-white/70 border-t-transparent rounded-full animate-spin"></span>
                    <i x-show="!saving" class="bi bi-save"></i> Simpan
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal Edit Mapel -->
    <x-ui.modal id="editMapelModal" title="Edit Mata Pelajaran" icon="bi-pencil-square" tone="amber" size="max-w-lg">
        <form @submit.prevent="submitEdit()" class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <input type="hidden" x-model="editing.id_mapel">
                <input type="hidden" x-model="editing.kelompok">

                <div>
                    <label for="namaEdit" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Nama Mata Pelajaran <span class="text-rose-500">*</span></label>
                    <input type="text" id="namaEdit" x-model="editing.nama_mapel" maxlength="50" required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="kodeEdit" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Kode Mapel</label>
                        <input type="text" id="kodeEdit" x-model="editing.kode_mapel" maxlength="20"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <div>
                        <label for="kodeUrut" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">No. Urut Rapor</label>
                        <input type="number" id="kodeUrut" x-model.number="editing.urutan_tampil" min="0" max="9999"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>

                <div>
                    <label for="statusEdit" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Status Keaktifan <span class="text-rose-500">*</span></label>
                    <select id="statusEdit" x-model="editing.status" required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        @foreach ($status as $nilaiStatus => $labelStatus)
                            <option value="{{ $nilaiStatus }}">{{ $labelStatus }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#editMapelModal')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="saving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-amber-500/20 transition-colors hover:bg-amber-600 disabled:opacity-60">
                    <span x-show="saving" x-cloak class="inline-block w-3.5 h-3.5 border-2 border-white/70 border-t-transparent rounded-full animate-spin"></span>
                    <i x-show="!saving" class="bi bi-save"></i> Perbarui
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('mapelPage', () => ({
        urls: {},
        csrf: '',

        rows: [],
        loading: false,
        saving: false,

        query: '',
        perPage: 10,
        page: 1,
        selected: [],

        form: { nama_mapel: '', kode_mapel: '', urutan_tampil: 0, kelompok: 'A', status: '1' },
        editing: { id_mapel: null, nama_mapel: '', kode_mapel: '', urutan_tampil: 0, kelompok: 'A', status: '1' },

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');
            this.urls = cfg.urls || {};
            this.csrf = cfg.csrf || '';

            this.$watch('query', () => { this.page = 1; });

            this.load();
        },

        get filtered() {
            const needle = this.query.trim().toLowerCase();
            if (needle === '') return this.rows;

            return this.rows.filter((row) => {
                const nama = String(row.nama_mapel ?? '').toLowerCase();
                const kode = String(row.kode ?? '').toLowerCase();
                return nama.includes(needle) || kode.includes(needle);
            });
        },

        get pages() {
            return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
        },

        get pageRows() {
            const current = Math.min(this.page, this.pages);
            const start = (current - 1) * this.perPage;
            return this.filtered.slice(start, start + this.perPage);
        },

        get selectablePage() {
            return this.pageRows.filter((row) => row.deletable !== '0');
        },

        get allPageSelected() {
            return this.selectablePage.length > 0
                && this.selectablePage.every((row) => this.selected.includes(String(row.id_mapel)));
        },

        summaryText() {
            const total = this.filtered.length;
            if (total === 0) return 'Tidak ada data';
            const current = Math.min(this.page, this.pages);
            const start = (current - 1) * this.perPage;
            const end = Math.min(start + this.perPage, total);
            return 'Menampilkan ' + (start + 1) + '\u2013' + end + ' dari ' + total + ' mapel';
        },

        pageList() {
            const total = this.pages;
            const current = Math.min(this.page, total);
            const span = 2;
            let from = Math.max(1, current - span);
            let to = Math.min(total, current + span);
            if (to - from < span * 2) {
                from = Math.max(1, Math.min(from, to - span * 2));
                to = Math.min(total, from + span * 2);
            }

            const list = [];
            for (let p = from; p <= to; p++) list.push(p);
            return list;
        },

        toggleAll(checked) {
            const ids = this.selectablePage.map((row) => String(row.id_mapel));
            if (checked) {
                this.selected = Array.from(new Set([...this.selected, ...ids]));
            } else {
                this.selected = this.selected.filter((id) => !ids.includes(id));
            }
        },

        async requestJson(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': this.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: payload,
            });

            return response.json();
        },

        async load() {
            this.loading = true;

            const body = new URLSearchParams();
            body.append('_token', this.csrf);
            body.append('draw', '1');
            body.append('start', '0');
            body.append('length', '-1');
            body.append('search[value]', '');

            try {
                const result = await this.requestJson(this.urls.read, body);

                if (!result || !Array.isArray(result.data)) {
                    throw new Error('Format respons tidak dikenal');
                }

                this.rows = result.data.map((row) => ({
                    ...row,
                    deletable: String(row.deletable ?? '1'),
                    status: String(row.status ?? '0'),
                }));

                const valid = new Set(this.rows
                    .filter((row) => row.deletable !== '0')
                    .map((row) => String(row.id_mapel)));
                this.selected = this.selected.filter((id) => valid.has(id));

                if (this.page > this.pages) this.page = this.pages;
            } catch (error) {
                console.error(error);
                Swal.fire('Gagal', 'Data mata pelajaran gagal dimuat.', 'error');
            } finally {
                this.loading = false;
            }
        },

        async reload() {
            await this.load();
            if (this.rows.length === 0) return;

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Data berhasil dimuat ulang',
                showConfirmButton: false,
                timer: 1500,
            });
        },

        openCreate() {
            this.form = { nama_mapel: '', kode_mapel: '', urutan_tampil: 0, kelompok: 'A', status: '1' };
            this.$store.modals.open('#createMapelModal');
        },

        openEdit(row) {
            this.editing = {
                id_mapel: row.id_mapel,
                nama_mapel: row.nama_mapel ?? '',
                kode_mapel: row.kode ?? '',
                urutan_tampil: Number(row.urutan_tampil ?? 0),
                kelompok: row.kelompok ?? 'A',
                status: String(row.status ?? '1'),
            };
            this.$store.modals.open('#editMapelModal');
        },

        buildPayload(data) {
            const body = new URLSearchParams();
            body.append('_token', this.csrf);
            Object.keys(data).forEach((key) => body.append(key, data[key] ?? ''));
            return body;
        },

        async submitCreate() {
            if (this.saving) return;

            if (String(this.form.nama_mapel).trim() === '') {
                Swal.fire('Gagal', 'Nama mapel wajib diisi.', 'warning');
                return;
            }

            this.saving = true;
            try {
                const result = await this.requestJson(this.urls.create, this.buildPayload(this.form));

                if (result && result.status) {
                    this.$store.modals.close('#createMapelModal');
                    this.form = { nama_mapel: '', kode_mapel: '', urutan_tampil: 0, kelompok: 'A', status: '1' };
                    await this.load();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: result.message || 'Mata pelajaran berhasil ditambahkan',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: (result && result.message) || 'Mapel tidak tersimpan — periksa isian lalu coba lagi.',
                });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            } finally {
                this.saving = false;
            }
        },

        async submitEdit() {
            if (this.saving) return;

            this.saving = true;
            try {
                const result = await this.requestJson(this.urls.update, this.buildPayload(this.editing));

                if (result && result.status) {
                    this.$store.modals.close('#editMapelModal');
                    await this.load();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: result.message || 'Mata pelajaran berhasil diperbarui',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: (result && result.message) || 'Mapel tidak tersimpan — periksa isian lalu coba lagi.',
                });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            } finally {
                this.saving = false;
            }
        },

        async deleteSelected() {
            if (this.selected.length === 0 || this.saving) return;

            const konfirmasi = await Swal.fire({
                title: 'Hapus Terpilih?',
                text: 'Data yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.saving = true;
            try {
                const body = new URLSearchParams();
                body.append('_token', this.csrf);
                this.selected.forEach((id) => body.append('checked[]', id));

                const result = await this.requestJson(this.urls.delete, body);

                if (result && result.status) {
                    this.selected = [];
                    await this.load();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: result.message || ((result.total || 0) + ' data berhasil dihapus'),
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    return;
                }

                // Tidak ada yang terhapus — pilihan dibiarkan utuh.
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak ada yang terhapus',
                    text: (result && result.message) || 'Pilih mapel yang boleh dihapus, atau bersihkan data terkait dulu.',
                });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            } finally {
                this.saving = false;
            }
        },
    }));
});
</script>
@endsection
