@extends('layouts.main')

@section('content')

<?php
$pageConfig = [
    'csrf' => csrf_token(),
    'avatarFallback' => url('assets/img/siswa_sm.png'),
    'isAdmin' => (bool) ($is_admin ?? false),
    'urls' => [
        'list' => url('usersiswa/list'),
        'activate' => url('usersiswa/activate'),
        'resetLogin' => url('usersiswa/reset_login'),
        'bulk' => url('usersiswa/aktifkansemua'),
    ],
];
?>

<div x-data="siswaUserPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <!-- Content Header -->
    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'User Management'"
            icon="bi-person-badge"
            tone="rose"
            :subtitle="$subjudul ?? 'Data User Siswa'"
            :crumbs="['User Management', $subjudul ?? 'Data User Siswa']" />
    </div>

    <!-- Main Content Area -->
    <div class="mt-4 space-y-5 px-4 pb-6 md:px-6">
        <x-ui.card :title="$subjudul ?? 'Data User Siswa'" icon="bi-people" tone="rose" bodyClass="p-0">

            <x-slot:meta>
                <span class="text-xs text-slate-500"
                    x-text="loading ? 'Memuat data...' : total + ' siswa' + (query ? ' (cari: \u201c' + query + '\u201d)' : ' terdaftar')"></span>
                <span x-show="onlyReset" x-cloak
                    class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">
                    <i class="bi bi-funnel-fill"></i> Perlu reset login
                </span>
            </x-slot:meta>

            @if ($is_admin)
                <x-slot:actions>
                    <button type="button" @click="bulk('aktifkan')" :disabled="saving || loading"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100 disabled:opacity-60">
                        <i class="bi bi-people-fill"></i> Aktifkan Semua
                    </button>
                    <button type="button" @click="bulk('nonaktifkan')" :disabled="saving || loading"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-100 disabled:opacity-60">
                        <i class="bi bi-slash-circle"></i> Nonaktifkan Semua
                    </button>
                    <button type="button" @click="load()" title="Reload" :disabled="loading"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500 transition-colors hover:bg-slate-100 disabled:opacity-60"
                        aria-label="Muat ulang data siswa">
                        <i class="bi bi-arrow-clockwise text-sm" :class="loading ? 'inline-block animate-spin' : ''"></i>
                    </button>
                </x-slot:actions>
            @endif

            <x-slot:toolbar>
                <div class="flex flex-col justify-between gap-2 lg:flex-row lg:items-center">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-1.5 text-xs font-medium text-slate-500">
                            <label for="siswa-per-page">Tampilkan</label>
                            <select id="siswa-per-page" x-model.number="perPage" @change="applyPerPage()"
                                aria-label="Jumlah data per halaman"
                                class="rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 outline-hidden focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span>data</span>
                        </div>

                        <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-medium text-slate-500">
                            <input type="checkbox" x-model="onlyReset" @change="applyFilter()"
                                class="h-3.5 w-3.5 rounded border-slate-300 text-rose-600 focus:ring-rose-500/30">
                            Hanya yang perlu reset
                        </label>
                    </div>

                    <div class="relative w-full lg:w-64">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-slate-400">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="search" x-model="searchInput" @input.debounce.400ms="applyFilter()"
                            placeholder="Cari nama, NIS, username..." aria-label="Cari siswa"
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 pr-8 pl-8 text-xs outline-hidden transition-all focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20">
                        <button type="button" x-show="searchInput.length > 0" x-cloak @click="clearSearch()"
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
                    <th class="hidden w-28 px-4 py-3 sm:table-cell">NIS</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="hidden w-32 px-3 py-3 text-center md:table-cell">Kelas</th>
                    <th class="hidden px-4 py-3 sm:table-cell">Username</th>
                    <th class="w-24 px-3 py-3 text-center">Reset Login</th>
                    @if ($is_admin)
                        <th class="w-32 px-3 py-3 text-center">Status/Aksi</th>
                    @endif
                </x-slot:head>

                <tr x-show="loading" x-cloak>
                    <td colspan="{{ $is_admin ? 7 : 6 }}" class="px-5 py-10 text-center text-xs font-medium text-slate-400">
                        <i class="bi bi-arrow-repeat mr-1 inline-block animate-spin"></i> Memuat data siswa...
                    </td>
                </tr>

                <tr x-show="!loading && rows.length === 0" x-cloak>
                    <td colspan="{{ $is_admin ? 7 : 6 }}" class="px-5 py-10 text-center text-xs font-medium text-slate-400">
                        <i class="bi bi-person-x mr-1"></i> Tidak ada data siswa
                    </td>
                </tr>

                <template x-for="(siswa, index) in rows" :key="siswa.id_siswa ?? siswa.username ?? index">
                    <tr class="transition-colors hover:bg-slate-50/60">
                        <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400"
                            x-text="(page - 1) * perPage + index + 1"></td>
                        <td class="hidden px-4 py-2.5 font-mono text-xs text-slate-600 sm:table-cell" x-text="siswa.nis || '-'"></td>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <img :src="siswa.foto || config.avatarFallback" x-on:error="$el.src = config.avatarFallback"
                                    alt="" class="h-9 w-9 shrink-0 rounded-lg border border-slate-200 object-cover">
                                <span class="max-w-[200px] truncate font-semibold text-slate-800" x-text="siswa.nama || '-'"></span>
                            </div>
                        </td>
                        <td class="hidden px-3 py-2.5 text-center md:table-cell">
                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600"
                                x-text="siswa.nama_kelas || '-'"></span>
                        </td>
                        <td class="hidden px-4 py-2.5 font-mono text-xs text-slate-600 sm:table-cell" x-text="siswa.username || '-'"></td>
                        <td class="px-3 py-2.5 text-center">
                            <button type="button" @click="resetLogin(siswa)" :disabled="saving || String(siswa.reset) === '0'"
                                title="Reset login"
                                :class="String(siswa.reset) === '0'
                                    ? 'border-slate-200 bg-slate-100 text-slate-400'
                                    : 'border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100'"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg border transition-colors disabled:cursor-not-allowed disabled:opacity-60">
                                <i class="bi bi-arrow-repeat text-xs"></i>
                            </button>
                        </td>
                        @if ($is_admin)
                            <td class="px-3 py-2.5">
                                <div class="flex items-center justify-center gap-1.5">
                                    <span :class="isActive(siswa)
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                            : 'border-rose-200 bg-rose-50 text-rose-600'"
                                        class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold"
                                        x-text="isActive(siswa) ? 'Aktif' : 'Nonaktif'"></span>
                                    <button type="button" @click="toggleActive(siswa)" :disabled="saving"
                                        :title="isActive(siswa) ? 'Nonaktifkan' : 'Aktifkan'"
                                        :class="isActive(siswa)
                                            ? 'border-amber-200 bg-amber-50 text-amber-600 hover:bg-amber-100'
                                            : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg border transition-colors disabled:cursor-not-allowed disabled:opacity-60">
                                        <i class="bi text-xs" :class="isActive(siswa) ? 'bi-slash-circle' : 'bi-person-plus-fill'"></i>
                                    </button>
                                </div>
                            </td>
                        @endif
                    </tr>
                </template>
            </x-ui.table>

            <x-slot:footer>
                <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <span class="text-xs text-slate-500"
                        x-text="total === 0 ? '0 entri' : (from + ' ~ ' + to + ' dari ' + total + ' entri')"></span>

                    <nav aria-label="Navigasi halaman" x-show="pages > 1" x-cloak>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="goTo(page - 1)" :disabled="page <= 1 || loading"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-500 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Halaman sebelumnya">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <template x-for="item in pageItems" :key="item">
                                <button type="button" @click="goTo(item)" :disabled="loading"
                                    :class="item === page
                                        ? 'border-rose-500 bg-rose-500 text-white'
                                        : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                                    class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border px-2 text-xs font-semibold transition-colors disabled:cursor-not-allowed"
                                    :aria-current="item === page ? 'page' : null" x-text="item"></button>
                            </template>
                            <button type="button" @click="goTo(page + 1)" :disabled="page >= pages || loading"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-500 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                aria-label="Halaman berikutnya">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </nav>
                </div>
            </x-slot:footer>
        </x-ui.card>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('siswaUserPage', () => ({
        config: { csrf: '', urls: {}, avatarFallback: '', isAdmin: false },

        rows: [],
        total: 0,
        pages: 0,
        page: 1,
        perPage: 10,
        query: '',
        searchInput: '',
        onlyReset: false,
        loading: false,
        saving: false,

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');
            this.config = Object.assign({ csrf: '', urls: {}, avatarFallback: '', isAdmin: false }, cfg);
            this.config.urls = cfg.urls || {};

            this.load();
        },

        get from() {
            return this.total === 0 ? 0 : (this.page - 1) * this.perPage + 1;
        },

        get to() {
            return Math.min(this.page * this.perPage, this.total);
        },

        get pageItems() {
            const total = this.pages;
            if (total <= 1) return total === 1 ? [1] : [];

            const visible = 5;
            let start = Math.max(1, this.page - Math.floor(visible / 2));
            const end = Math.min(total, start + visible - 1);
            start = Math.max(1, end - visible + 1);

            const items = [];
            for (let i = start; i <= end; i++) items.push(i);

            return items;
        },

        isActive(siswa) {
            return String(siswa && siswa.active) === '1';
        },

        async post(url, data = {}) {
            const body = new URLSearchParams();
            body.append('_token', this.config.csrf);

            Object.keys(data).forEach((key) => {
                const value = data[key];
                if (value !== null && value !== undefined) body.append(key, value);
            });

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

            if (!response.ok) {
                throw new Error('Permintaan gagal (' + response.status + ').');
            }

            return response.json();
        },

        async load() {
            if (this.loading) return;

            this.loading = true;

            try {
                const result = await this.post(this.config.urls.list, {
                    page: this.page,
                    limit: this.perPage,
                    search: this.query,
                    reset: this.onlyReset ? '1' : '0',
                });

                this.rows = (result && result.lists) || [];
                this.total = Number((result && result.total) || 0);
                this.pages = Number((result && result.pages) || 0);

                if (this.page > this.pages && this.pages > 0) {
                    this.page = this.pages;
                    this.loading = false;
                    return this.load();
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal memuat data siswa.' });
            } finally {
                this.loading = false;
            }
        },

        applyPerPage() {
            this.page = 1;
            this.load();
        },

        applyFilter() {
            this.query = (this.searchInput || '').trim();
            this.page = 1;
            this.load();
        },

        clearSearch() {
            this.searchInput = '';
            this.query = '';
            this.page = 1;
            this.load();
        },

        goTo(target) {
            if (this.loading || target < 1 || target > this.pages || target === this.page) return;

            this.page = target;
            this.load();
        },

        async toggleActive(siswa) {
            if (this.saving) return;

            const aktifkan = !this.isActive(siswa);
            const konfirmasi = await Swal.fire({
                title: aktifkan ? 'Aktifkan akun siswa?' : 'Nonaktifkan akun siswa?',
                html: '<b>' + this.escapeHtml(siswa.nama || siswa.username || '') + '</b>'
                    + (aktifkan ? ' akan dapat login kembali.' : ' tidak dapat login sampai diaktifkan lagi.'),
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: aktifkan ? '#16a34a' : '#d97706',
                confirmButtonText: aktifkan ? 'Ya, Aktifkan' : 'Ya, Nonaktifkan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.saving = true;

            try {
                const result = await this.post(this.config.urls.activate, {
                    is_active: aktifkan ? 'aktifkan' : 'nonaktifkan',
                    id_siswa: siswa.id_siswa,
                    username: siswa.username,
                    nama: siswa.nama,
                });

                if (result && result.status) {
                    Swal.fire({ icon: 'success', title: 'Sukses', text: result.msg || 'Status diubah.' });
                    await this.load();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.msg) || 'Gagal mengubah status.' });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menghubungi server.' });
            } finally {
                this.saving = false;
            }
        },

        async resetLogin(siswa) {
            if (this.saving) return;

            this.saving = true;

            try {
                const result = await this.post(this.config.urls.resetLogin, {
                    id_siswa: siswa.id_siswa,
                    username: siswa.username,
                    nama: siswa.nama,
                });

                if (result && result.status) {
                    Swal.fire({ icon: 'success', title: 'Sukses', text: result.msg || 'Login berhasil direset.' });
                    await this.load();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: (result && result.msg) || 'Gagal mereset login.' });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menghubungi server.' });
            } finally {
                this.saving = false;
            }
        },

        async bulk(action) {
            if (this.saving) return;

            const aktifkan = action === 'aktifkan';
            const konfirmasi = await Swal.fire({
                title: aktifkan ? 'Aktifkan semua siswa?' : 'Nonaktifkan semua siswa?',
                text: aktifkan
                    ? 'Semua akun siswa akan diaktifkan dan akun yang belum ada akan dibuatkan.'
                    : 'Semua akun siswa akan dinonaktifkan.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Lanjutkan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.saving = true;
            Swal.fire({
                title: aktifkan ? 'Mengaktifkan semua siswa' : 'Menonaktifkan semua siswa',
                text: 'Silahkan tunggu....',
                allowEscapeKey: false,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const result = await this.post(this.config.urls.bulk, { is_active: action });

                if (result && result.status) {
                    Swal.fire({ icon: 'success', title: 'Sukses', text: result.msg || 'Status siswa diupdate.' });
                    await this.load();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.msg) || 'Gagal mengubah status siswa.' });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menghubungi server.' });
            } finally {
                this.saving = false;
            }
        },

        escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            })[char]);
        },
    }));
});
</script>
@endsection
