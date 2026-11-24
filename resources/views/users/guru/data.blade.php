@extends('layouts.main')

@section('content')

<?php
$pageConfig = [
    'csrf' => csrf_token(),
    'avatarFallback' => url('assets/img/guru.png'),
    'urls' => [
        'data' => url('userguru/data'),
        'activate' => url('userguru/activate'),
        'resetLogin' => url('userguru/reset_login'),
        'bulk' => url('userguru/aktifkan_all'),
        'updateIduser' => url('userguru/update_iduser'),
    ],
];
?>

<div x-data="guruUserPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <!-- Content Header -->
    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'User Management'"
            icon="bi-person-badge"
            tone="amber"
            :subtitle="$subjudul ?? 'Data User Guru'"
            :crumbs="['User Management', $subjudul ?? 'Data User Guru']" />
    </div>

    <!-- Main Content Area -->
    <div class="mt-4 space-y-5 px-4 pb-6 md:px-6">
        <x-ui.card :title="$subjudul ?? 'Data User Guru'" icon="bi-person-video2" tone="amber" :body-class="'p-0'">

            <x-slot:meta>
                <span class="text-xs text-slate-500"
                    x-text="loading ? 'Memuat data...' : filtered.length + ' guru' + (query ? ' (filter: \u201c' + query + '\u201d)' : ' terdaftar')"></span>
            </x-slot:meta>

            <x-slot:actions>
                <button type="button" @click="bulk('aktifkan')" :disabled="saving"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100 disabled:opacity-60">
                    <i class="bi bi-people-fill"></i> Aktifkan Semua
                </button>
                <button type="button" @click="bulk('nonaktifkan')" :disabled="saving"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-100 disabled:opacity-60">
                    <i class="bi bi-slash-circle"></i> Nonaktifkan Semua
                </button>
                <button type="button" @click="load()" title="Reload" :disabled="loading"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500 transition-colors hover:bg-slate-100 disabled:opacity-60"
                    aria-label="Muat ulang data guru">
                    <i class="bi bi-arrow-clockwise text-sm" :class="loading ? 'inline-block animate-spin' : ''"></i>
                </button>
            </x-slot:actions>

            <x-slot:toolbar>
                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-1.5 text-xs font-medium text-slate-500">
                        <label for="guru-per-page">Tampilkan</label>
                        <select id="guru-per-page" x-model.number="perPage" @change="page = 1"
                            aria-label="Jumlah data per halaman"
                            class="rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 outline-hidden focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span>data</span>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-slate-400">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="search" x-model="query" placeholder="Cari nama, username, jabatan..." aria-label="Cari guru"
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-8 pr-8 text-xs outline-hidden transition-all focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20">
                        <button type="button" x-show="query.length > 0" x-cloak @click="query = ''"
                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600"
                            aria-label="Hapus pencarian">
                            <i class="bi bi-x-circle-fill text-xs"></i>
                        </button>
                    </div>
                </div>
            </x-slot:toolbar>

            <x-ui.table>
                <x-slot:head>
                    <th class="w-12 px-3 py-3 text-center">No.</th>
                    <th class="px-4 py-3">Nama Guru</th>
                    <th class="hidden px-4 py-3 sm:table-cell">Username</th>
                    <th class="hidden w-32 px-3 py-3 text-center md:table-cell">Jabatan</th>
                    <th class="w-24 px-3 py-3 text-center">Reset Login</th>
                    <th class="w-24 px-3 py-3 text-center">Status</th>
                    <th class="w-24 px-3 py-3 text-center">Aksi</th>
                </x-slot:head>

                <tr x-show="loading" x-cloak>
                    <td colspan="7" class="px-5 py-10 text-center text-xs font-medium text-slate-400">
                        <i class="bi bi-arrow-repeat mr-1 inline-block animate-spin"></i> Memuat data guru...
                    </td>
                </tr>

                <tr x-show="!loading && filtered.length === 0" x-cloak>
                    <td colspan="7" class="px-5 py-10 text-center text-xs font-medium text-slate-400">
                        <i class="bi bi-person-x mr-1"></i> Tidak ada data guru
                    </td>
                </tr>

                <template x-for="(guru, index) in pageRows" :key="guru.id_guru ?? guru.username ?? index">
                    <tr class="transition-colors hover:bg-slate-50/60">
                        <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400"
                            x-text="(page - 1) * perPage + index + 1"></td>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <img :src="guru.foto || config.avatarFallback" x-on:error="$el.src = config.avatarFallback"
                                    alt="" class="h-9 w-9 shrink-0 rounded-lg border border-slate-200 object-cover">
                                <span class="max-w-[180px] truncate font-semibold text-slate-800" x-text="guru.nama_guru || '-'"></span>
                            </div>
                        </td>
                        <td class="hidden px-4 py-2.5 sm:table-cell">
                            <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-600" x-text="guru.username || '-'"></span>
                        </td>
                        <td class="hidden px-3 py-2.5 text-center md:table-cell">
                            <span class="inline-flex items-center rounded-full border border-amber-100 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700" x-text="guru.level || '-'"></span>
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            <button type="button" :disabled="!hasReset(guru.reset) || saving"
                                @click="resetLogin(guru)"
                                :title="hasReset(guru.reset) ? 'Reset ' + guru.reset + ' login gagal' : 'Tidak ada login gagal'"
                                :class="hasReset(guru.reset)
                                    ? 'bg-rose-50 text-rose-600 hover:bg-rose-100'
                                    : 'bg-slate-100 text-slate-300 cursor-not-allowed'"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg shadow-xs transition-colors"
                                aria-label="Reset login">
                                <i class="bi bi-arrow-repeat text-sm"></i>
                            </button>
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            <span x-show="isActive(guru.active)"
                                class="inline-flex items-center gap-1 rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                <i class="bi bi-check-circle-fill text-[9px]"></i> Aktif
                            </span>
                            <span x-show="!isActive(guru.active)" x-cloak
                                class="inline-flex items-center gap-1 rounded-full border border-rose-100 bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700">
                                <i class="bi bi-x-circle-fill text-[9px]"></i> Nonaktif
                            </span>
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="flex justify-center gap-1">
                                <button type="button" @click="toggle(guru)" :disabled="saving"
                                    :title="isActive(guru.active) ? 'Nonaktifkan' : 'Aktifkan'"
                                    :class="isActive(guru.active)
                                        ? 'bg-amber-50 text-amber-600 hover:bg-amber-100'
                                        : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg shadow-xs transition-colors disabled:opacity-60"
                                    aria-label="Ubah status akun guru">
                                    <i class="bi text-sm" :class="isActive(guru.active) ? 'bi-slash-circle' : 'bi-person-check-fill'"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </x-ui.table>

            <div class="flex flex-col justify-between gap-3 border-t border-slate-100 px-5 py-3.5 sm:flex-row sm:items-center">
                <div class="text-xs font-medium text-slate-500" aria-live="polite"
                    x-text="total === 0 ? 'Tidak ada data' : 'Menampilkan ' + from + '\u2013' + to + ' dari ' + total + ' guru'"></div>
                <div class="flex items-center gap-2" x-show="pages > 1" x-cloak>
                    <button type="button" @click="prev()" :disabled="page <= 1" aria-label="Halaman sebelumnya"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <span class="text-xs font-semibold text-slate-500" x-text="'Halaman ' + page + ' / ' + pages"></span>
                    <button type="button" @click="next()" :disabled="page >= pages" aria-label="Halaman berikutnya"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('guruUserPage', () => ({
        config: { urls: {}, csrf: '', avatarFallback: '' },

        rows: [],
        loading: false,
        saving: false,

        query: '',
        perPage: 10,
        page: 1,

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');
            this.config = Object.assign({ urls: {}, csrf: '', avatarFallback: '' }, cfg);
            this.config.urls = cfg.urls || {};

            this.load();
        },

        get csrf() {
            return this.config.csrf;
        },

        async requestJson(url, { method = 'POST', params = null } = {}) {
            const options = {
                method,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            };

            if (params) {
                options.headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
                options.body = params;
            }

            const response = await fetch(url, options);

            return response.json();
        },

        post(url, data = {}) {
            const body = new URLSearchParams();
            body.append('_token', this.csrf);

            Object.keys(data).forEach((key) => body.append(key, data[key] ?? ''));

            return this.requestJson(url, { params: body });
        },

        isActive(value) {
            return value === 1 || value === '1' || value === true;
        },

        hasReset(value) {
            return Boolean(value) && String(value) !== '0';
        },

        get filtered() {
            const query = this.query.trim().toLowerCase();

            if (query === '') return this.rows;

            return this.rows.filter((guru) => [
                guru.nama_guru,
                guru.username,
                guru.level,
            ].some((field) => String(field || '').toLowerCase().includes(query)));
        },

        get total() {
            return this.filtered.length;
        },

        get pages() {
            return Math.max(1, Math.ceil(this.total / this.perPage));
        },

        get from() {
            return this.total === 0 ? 0 : (this.page - 1) * this.perPage + 1;
        },

        get to() {
            return Math.min(this.page * this.perPage, this.total);
        },

        get pageRows() {
            const start = (this.page - 1) * this.perPage;

            return this.filtered.slice(start, start + this.perPage);
        },

        prev() {
            this.page = Math.max(1, this.page - 1);
        },

        next() {
            this.page = Math.min(this.pages, this.page + 1);
        },

        async load() {
            this.loading = true;

            try {
                const data = await this.requestJson(this.config.urls.data, { method: 'GET' });
                this.rows = Array.isArray(data) ? data : [];
            } catch (error) {
                console.error(error);
                this.rows = [];
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal memuat data guru.' });
            } finally {
                this.loading = false;

                if (this.page > this.pages) {
                    this.page = this.pages;
                }
            }
        },

        async toggle(guru) {
            if (this.saving) return;

            const aktif = this.isActive(guru.active);
            const action = aktif ? 'nonaktifkan' : 'aktifkan';

            const konfirmasi = await Swal.fire({
                title: (aktif ? 'Nonaktifkan' : 'Aktifkan') + ' guru ini?',
                text: guru.nama_guru || '',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: aktif ? '#d97706' : '#16a34a',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.saving = true;

            try {
                const result = await this.post(this.config.urls.activate, {
                    id: guru.id ?? '',
                    id_guru: guru.id_guru ?? '',
                    nama: guru.nama_guru ?? '',
                    is_active: action,
                });

                if (result && result.status) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: result.msg || '', timer: 1300, showConfirmButton: false });
                    await this.load();
                    return;
                }

                let pesan = (result && result.msg) || 'Gagal mengubah status akun guru';

                if (result && Array.isArray(result.users)) {
                    pesan += '<br><b>' + result.users
                        .map((user) => '- ' + (user.nama_guru || user.username || ''))
                        .join('<br>') + '</b>';
                }

                Swal.fire({ icon: 'error', title: 'Gagal', html: pesan });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Koneksi server bermasalah' });
            } finally {
                this.saving = false;
            }
        },

        async resetLogin(guru) {
            if (this.saving || !this.hasReset(guru.reset)) return;

            const konfirmasi = await Swal.fire({
                title: 'Reset Login?',
                text: 'Hapus catatan login gagal untuk "' + (guru.nama_guru || '') + '"?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Reset',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.saving = true;

            try {
                const result = await this.post(this.config.urls.resetLogin, {
                    username: guru.username ?? '',
                    nama: guru.nama_guru ?? '',
                });

                if (result && result.status) {
                    Swal.fire({ icon: 'success', title: 'Sukses', html: '<b>' + (guru.nama_guru || '') + '</b> ' + (result.msg || 'berhasil direset'), timer: 1500, showConfirmButton: false });
                    await this.load();
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.msg) || 'Reset login gagal' });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Koneksi server bermasalah' });
            } finally {
                this.saving = false;
            }
        },

        async bulk(action) {
            if (this.saving) return;

            const konfirmasi = await Swal.fire({
                title: action === 'aktifkan' ? 'Aktifkan Semua Guru?' : 'Nonaktifkan Semua Guru?',
                text: 'Tindakan ini akan mempengaruhi semua akun guru.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: action === 'aktifkan' ? '#16a34a' : '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Lanjutkan',
                cancelButtonText: 'Batal',
            });

            if (!konfirmasi.isConfirmed) return;

            this.saving = true;
            Swal.showLoading();

            try {
                const result = await this.post(this.config.urls.bulk, { is_active: action });

                if (!(result && result.status)) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.msg) || 'Aksi massal gagal dijalankan' });
                    return;
                }

                const tautan = await this.post(this.config.urls.updateIduser, {});

                Swal.fire({
                    icon: tautan && tautan.status ? 'success' : 'warning',
                    title: tautan && tautan.status ? 'Sukses' : 'Perlu Diperiksa',
                    html: (tautan && tautan.msg) || (result && result.msg) || '',
                });

                await this.load();
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Koneksi server bermasalah' });
            } finally {
                this.saving = false;
            }
        },
    }));
});
</script>
@endsection
