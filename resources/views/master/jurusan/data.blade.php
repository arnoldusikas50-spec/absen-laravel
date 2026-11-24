@extends('layouts.main')

@section('content')

@php
    $tpTahun = e($tp_active->tahun ?? '-');
    $smtNama = e($smt_active->smt ?? '-');
@endphp

<div class="fade-in-up space-y-4 px-4 pt-6 md:px-6"
    x-data="jurusanPage"
    data-token="{{ csrf_token() }}"
    data-url-data="{{ url('datajurusan/data') }}"
    data-url-save="{{ url('datajurusan/save') }}"
    data-url-update="{{ url('datajurusan/update') }}"
    data-url-delete="{{ url('datajurusan/delete') }}"
    data-url-get="{{ url('datajurusan/get_data') }}">

    <x-ui.page-header
        title="{{ $judul ?? 'Data Jurusan' }}"
        icon="bi-diagram-3"
        :subtitle="($subjudul ?? 'Kelola Data Jurusan/Program') . ' · TP ' . $tpTahun . ' · Semester ' . $smtNama"
        :crumbs="['Data Umum', 'Jurusan']" />

    <x-ui.card title="Daftar Jurusan / Program" icon="bi-card-list"
        subtitle="Kelola master data jurusan dan kelompok peminatan kurikulum"
        body-class="p-0">
        <x-slot:actions>
            <button type="button" @click="load()" :disabled="loading"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-xs transition-colors hover:bg-slate-50 disabled:opacity-50">
                <i class="bi bi-arrow-clockwise" :class="loading ? 'inline-block animate-spin' : ''"></i> Reload
            </button>
            <button type="button" @click="openCreate()"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                <i class="bi bi-plus-lg"></i> Tambah Jurusan
            </button>
        </x-slot:actions>

        <x-slot:toolbar>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:max-w-xs">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                    <input type="text" x-model="query" placeholder="Cari kode, nama, atau mapel..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-8 text-xs font-semibold text-slate-700 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    <button type="button" x-show="query.length > 0" x-cloak @click="query = ''"
                        class="absolute right-2.5 top-1/2 inline-flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-lg bg-slate-200 text-xs text-slate-600 hover:bg-slate-300"
                        aria-label="Bersihkan pencarian">&times;</button>
                </div>
                <span class="inline-flex items-center gap-1.5 self-start rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 sm:self-auto">
                    <i class="bi bi-collection text-slate-400"></i>
                    <span x-text="filtered.length + ' jurusan'"></span>
                </span>
            </div>
        </x-slot:toolbar>

        <div x-show="loading" x-cloak class="flex items-center justify-center gap-2 py-12 text-xs font-semibold text-blue-600">
            <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-blue-500 border-t-transparent"></span>
            Memuat data jurusan...
        </div>

        <div x-show="!loading && filtered.length === 0" x-cloak class="p-6">
            <x-ui.empty icon="bi-diagram-3" title="Belum ada data jurusan"
                description="Tambahkan jurusan/program pertama, atau ubah kata kunci pencarian.">
                <button type="button" @click="openCreate()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-700">
                    <i class="bi bi-plus-lg"></i> Tambah Jurusan
                </button>
            </x-ui.empty>
        </div>

        <div x-show="!loading && filtered.length > 0" x-cloak>
            <x-ui.table class="min-w-[720px]">
                <x-slot:head>
                    <th class="w-14 px-4 py-3 text-center">No</th>
                    <th class="w-32 px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama Jurusan</th>
                    <th class="px-4 py-3">Mapel Peminatan</th>
                    <th class="w-24 px-4 py-3 text-center">Aksi</th>
                </x-slot:head>

                <template x-for="(row, index) in paged" :key="row.id_jurusan">
                    <tr class="transition-colors hover:bg-slate-50">
                        <td class="px-4 py-3.5 text-center text-xs font-bold text-slate-400" x-text="(page - 1) * perPage + index + 1"></td>
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center rounded-full border border-blue-100 bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700"
                                x-text="row.kode_jurusan || '-'"></span>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="font-semibold text-slate-800" x-text="row.nama_jurusan || '-'"></span>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex flex-wrap items-center gap-1">
                                <template x-if="!row.mapel_peminatan || row.mapel_peminatan.length === 0">
                                    <span class="text-xs text-slate-300">–</span>
                                </template>
                                <template x-for="mapel in (row.mapel_peminatan || []).slice(0, 3)" :key="mapel">
                                    <span class="inline-flex items-center rounded-full border border-emerald-100 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700"
                                        x-text="mapel"></span>
                                </template>
                                <span x-show="(row.mapel_peminatan || []).length > 3"
                                    class="text-[10px] font-bold text-slate-400"
                                    x-text="'+' + ((row.mapel_peminatan || []).length - 3)"></span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button" @click="openEdit(row.id_jurusan)"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-100 bg-amber-50 text-amber-600 shadow-xs transition-all hover:-translate-y-px hover:border-amber-600 hover:bg-amber-600 hover:text-white"
                                    title="Edit Jurusan">
                                    <i class="bi bi-pencil-square text-sm"></i>
                                </button>
                                <span x-show="row.locked" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-300"
                                    title="Data bawaan sistem — tidak dapat dihapus">
                                    <i class="bi bi-lock-fill text-sm"></i>
                                </span>
                                <button type="button" x-show="!row.locked" @click="remove(row)"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-100 bg-rose-50 text-rose-600 shadow-xs transition-all hover:-translate-y-px hover:border-rose-600 hover:bg-rose-600 hover:text-white"
                                    title="Hapus Jurusan">
                                    <i class="bi bi-trash3 text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </x-ui.table>

            <div class="flex flex-col gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3 text-xs sm:flex-row sm:items-center sm:justify-between">
                <div class="font-medium text-slate-500">
                    Menampilkan <span class="font-extrabold text-slate-700" x-text="from"></span>–<span class="font-extrabold text-slate-700" x-text="to"></span>
                    dari <span class="font-extrabold text-slate-700" x-text="filtered.length"></span> jurusan
                </div>
                <div class="flex items-center gap-1.5">
                    <button type="button" @click="goto(page - 1)" :disabled="page <= 1"
                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 font-semibold text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                        <i class="bi bi-chevron-left"></i> Sebelumnya
                    </button>
                    <span class="px-1 font-bold text-slate-600" x-text="page + ' / ' + pages"></span>
                    <button type="button" @click="goto(page + 1)" :disabled="page >= pages"
                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 font-semibold text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                        Berikutnya <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </x-ui.card>

    <!-- ═══ Modal Tambah / Edit Jurusan ═══ -->
    <div id="modalJurusan" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-3 sm:p-4"
        tabindex="-1" aria-labelledby="modalJurusanLabel" aria-hidden="true">
        <div class="ui-modal-box flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <h2 id="modalJurusanLabel" class="flex min-w-0 items-center gap-2.5 text-sm font-bold text-slate-800">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white"
                        :class="form.id ? 'bg-amber-500' : 'bg-blue-600'">
                        <i class="bi text-sm" :class="form.id ? 'bi-pencil' : 'bi-diagram-3'"></i>
                    </span>
                    <span class="truncate" x-text="form.id ? 'Edit Jurusan' : 'Tambah Jurusan'"></span>
                </h2>
                <button type="button" @click="closeModal()" aria-label="Tutup"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <form x-ref="form" @submit.prevent="save()" class="flex min-h-0 flex-col">
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                    <div>
                        <label for="kode_jurusan" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-600">
                            Kode / Singkatan Jurusan
                        </label>
                        <div class="relative">
                            <i class="bi bi-tag pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" id="kode_jurusan" x-model="form.kode_jurusan" maxlength="10" autocomplete="off"
                                placeholder="Contoh: IPA, TKJ, RPL"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">Kode singkat untuk identifikasi kelas dan raport (maks. 10 karakter).</p>
                    </div>

                    <div>
                        <label for="nama_jurusan" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-600">
                            Nama Lengkap Jurusan <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <i class="bi bi-card-text pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" id="nama_jurusan" x-model="form.nama_jurusan" required maxlength="30" autocomplete="off"
                                placeholder="Contoh: Teknik Komputer dan Jaringan"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                :class="error ? 'border-rose-300 ring-2 ring-rose-500/10' : ''">
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">Maksimal 30 karakter.</p>
                    </div>

                    <div>
                        <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-slate-600">
                            Mata Pelajaran Peminatan
                        </span>
                        <div class="max-h-48 space-y-1 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/60 p-2">
                            <?php foreach ($mapel_peminatan as $mapel): ?>
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-white">
                                    <input type="checkbox" value="{{ (int) $mapel['id_mapel'] }}" x-model="form.mapel"
                                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span class="truncate">{{ $mapel['nama_mapel'] }}</span>
                                </label>
                            <?php endforeach; ?>
                            <?php if (empty($mapel_peminatan)): ?>
                                <p class="px-2 py-3 text-xs text-slate-400">Belum ada mata pelajaran master.</p>
                            <?php endif; ?>
                        </div>
                        <p class="mt-1.5 text-[11px] text-slate-400">
                            <i class="bi bi-info-circle mr-1"></i> Opsional. Pilih mapel yang spesifik dipelajari oleh jurusan ini.
                        </p>
                    </div>

                    <div x-show="error" x-cloak class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700" x-text="error"></div>
                </div>

                <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                    <button type="button" @click="closeModal()"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">
                        Batal
                    </button>
                    <button type="submit" :disabled="saving"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        <i class="bi bi-check-lg" x-show="!saving"></i>
                        <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('jurusanPage', () => ({
        items: [],
        loading: true,
        saving: false,
        query: '',
        page: 1,
        perPage: 10,
        error: '',
        form: { id: null, kode_jurusan: '', nama_jurusan: '', mapel: [] },
        urls: {},

        init() {
            const data = this.$el.dataset;
            this.token = data.token;
            this.urls = {
                data: data.urlData,
                save: data.urlSave,
                update: data.urlUpdate,
                delete: data.urlDelete,
                get: data.urlGet,
            };
            this.load();
        },

        get filtered() {
            const q = this.query.trim().toLowerCase();
            const rows = this.items.slice().sort((a, b) =>
                String(a.nama_jurusan || '').localeCompare(String(b.nama_jurusan || ''), 'id', { sensitivity: 'base' })
            );

            if (q === '') {
                return rows;
            }

            return rows.filter((row) => [
                row.kode_jurusan,
                row.nama_jurusan,
                (row.mapel_peminatan || []).join(' '),
            ].join(' ').toLowerCase().includes(q));
        },

        get pages() {
            return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
        },

        get paged() {
            const start = (this.page - 1) * this.perPage;

            return this.filtered.slice(start, start + this.perPage);
        },

        get from() {
            return this.filtered.length ? (this.page - 1) * this.perPage + 1 : 0;
        },

        get to() {
            return Math.min(this.page * this.perPage, this.filtered.length);
        },

        goto(target) {
            this.page = Math.min(Math.max(1, target), this.pages);
        },

        async request(url, payload, method = 'POST') {
            const response = await fetch(url, {
                method,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(method === 'POST' ? { 'Content-Type': 'application/x-www-form-urlencoded' } : {}),
                },
                body: method === 'POST' ? new URLSearchParams(payload) : undefined,
            });

            const json = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(json.message || 'Permintaan gagal diproses.');
            }

            return json;
        },

        async load() {
            this.loading = true;

            try {
                const json = await this.request(this.urls.data, { _token: this.token });
                this.items = Array.isArray(json.data) ? json.data : [];
                this.page = 1;
            } catch (e) {
                Swal.fire('Gagal', e.message || 'Data jurusan tidak dapat dimuat.', 'error');
            } finally {
                this.loading = false;
            }
        },

        openCreate() {
            this.resetForm();
            this.$store.modals.open('#modalJurusan');
        },

        async openEdit(id) {
            try {
                const json = await this.request(this.urls.get + '/' + encodeURIComponent(id), {}, 'GET');
                const data = json.data || {};

                this.resetForm();
                this.form.id = data.id_jurusan || id;
                this.form.kode_jurusan = data.kode_jurusan || '';
                this.form.nama_jurusan = data.nama_jurusan || '';
                this.form.mapel = (data.mapel_peminatan || []).map((item) => String(item.id_mapel));
                this.$store.modals.open('#modalJurusan');
            } catch (e) {
                Swal.fire('Gagal', e.message || 'Data jurusan tidak dapat diambil.', 'error');
            }
        },

        closeModal() {
            this.$store.modals.close('#modalJurusan');
        },

        resetForm() {
            this.error = '';
            this.form = { id: null, kode_jurusan: '', nama_jurusan: '', mapel: [] };
        },

        async save() {
            const nama = this.form.nama_jurusan.trim();

            if (nama.length < 3) {
                this.error = 'Nama jurusan minimal 3 karakter.';
                return;
            }

            this.error = '';
            this.saving = true;

            const payload = {
                _token: this.token,
                id_jurusan: this.form.id || '',
                kode_jurusan: this.form.kode_jurusan.trim(),
                nama_jurusan: nama,
            };

            const body = new URLSearchParams();
            Object.entries(payload).forEach(([key, value]) => {
                if (Array.isArray(value)) {
                    value.forEach((item) => body.append(key, item));
                } else {
                    body.append(key, value);
                }
            });
            this.form.mapel.forEach((id) => body.append('mapel_peminatan[]', id));

            try {
                const json = await fetch(this.form.id ? this.urls.update : this.urls.save, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body,
                }).then((response) => response.json());

                if (!json.status) {
                    this.error = json.message || 'Data jurusan gagal disimpan.';
                    return;
                }

                this.closeModal();
                await this.load();
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: json.message, timer: 1500, showConfirmButton: false });
            } catch (e) {
                this.error = 'Terjadi kesalahan pada server.';
            } finally {
                this.saving = false;
            }
        },

        async remove(row) {
            const confirmation = await Swal.fire({
                title: 'Hapus Jurusan?',
                text: 'Jurusan ' + (row.nama_jurusan || '') + ' akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!confirmation.isConfirmed) {
                return;
            }

            try {
                const json = await this.request(this.urls.delete, {
                    _token: this.token,
                    id_jurusan: row.id_jurusan,
                });

                if (!json.status) {
                    Swal.fire('Gagal', json.message || 'Jurusan tidak dapat dihapus.', 'error');
                    return;
                }

                await this.load();
                Swal.fire({ icon: 'success', title: 'Terhapus!', text: json.message, timer: 1500, showConfirmButton: false });
            } catch (e) {
                Swal.fire('Error', e.message || 'Gagal menghubungi server.', 'error');
            }
        },
    }));
});
</script>
@endsection
