@extends('layouts.main')

@section('content')

<?php
$currentUserId = (int) ($user->id ?? 0);
$isSuperAdmin = $currentUserId === 1;

$pageConfig = [
    'csrf' => csrf_token(),
    'urls' => [
        'create' => url('useradmin/create'),
        'delete' => url('useradmin/delete'),
    ],
];
?>

<div x-data="adminPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <!-- Content Header -->
    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'Manajemen Admin'"
            icon="bi-person-badge"
            :subtitle="'Kelola akun administrator sistem · ' . count($admins) . ' akun terdaftar'"
            :crumbs="['Administrator', $subjudul ?? 'Data']">
            <x-slot:actions>
                <button type="button" @click="reload()"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-xs transition-colors hover:border-slate-300 hover:bg-slate-50">
                    <i class="bi bi-arrow-repeat"></i> Reload
                </button>
                <button type="button" @click="openCreate()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                    <i class="bi bi-plus-lg"></i> Tambah Admin
                </button>
            </x-slot:actions>
        </x-ui.page-header>
    </div>

    <!-- Main Content Area -->
    <div class="mt-4 space-y-5 px-4 pb-6 md:px-6">

        <x-ui.card :title="$subjudul ?? 'Daftar Admin'" icon="bi-shield-lock"
            subtitle="Akun dengan akses penuh ke backend aplikasi" :body-class="'p-0'"
            x-data="dataTable({ perPage: 10 })">

            <x-slot:toolbar>
                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-1.5 text-xs font-medium text-slate-500">
                        <label for="admin-per-page">Tampilkan</label>
                        <select id="admin-per-page" x-model.number="perPage" @change="page = 1; refresh()"
                            aria-label="Jumlah data per halaman"
                            class="rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
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
                        <input type="search" x-model="query" placeholder="Cari nama atau username..." aria-label="Cari admin"
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-8 pr-8 text-xs outline-hidden transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                        <button type="button" x-show="query.length > 0" x-cloak @click="query = ''"
                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600"
                            aria-label="Hapus pencarian">
                            <i class="bi bi-x-circle-fill text-xs"></i>
                        </button>
                    </div>
                </div>
            </x-slot:toolbar>

            @if (count($admins) === 0)
                <div class="p-5">
                    <x-ui.empty icon="bi-shield-x" title="Belum ada administrator"
                        description="Tambahkan akun administrator untuk mulai mengelola sistem." />
                </div>
            @else
                <div class="relative">
                    <x-ui.table>
                        <x-slot:head>
                            <th class="w-12 px-3 py-3 text-center">No.</th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Username</th>
                            <th class="hidden px-4 py-3 md:table-cell">Dibuat</th>
                            <th class="hidden w-28 px-3 py-3 text-center sm:table-cell">Level</th>
                            <th class="w-28 px-3 py-3 text-center">Status</th>
                            <th class="w-28 px-3 py-3 text-center">Aksi</th>
                        </x-slot:head>

                        @foreach ($admins as $num => $admin)
                            <tr class="transition-colors hover:bg-slate-50/60"
                                data-search="{{ strtolower(trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? '') . ' ' . $admin->username . ' ' . ($admin->level ?? ''))) }}">
                                <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400" data-rownum>{{ $num + 1 }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-xs font-bold text-white">
                                            {{ strtoupper(mb_substr((string) $admin->first_name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold leading-tight text-slate-800">
                                                {{ trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? '')) }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-600">
                                        {{ $admin->username }}
                                    </span>
                                </td>
                                <td class="hidden px-4 py-2.5 text-xs text-slate-400 md:table-cell">
                                    {{ $admin->created_on }}
                                </td>
                                <td class="hidden px-3 py-2.5 text-center sm:table-cell">
                                    <span class="inline-flex items-center rounded-full border border-blue-100 bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                        {{ $admin->level }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    @if ($admin->active == 1)
                                        <span class="inline-flex items-center gap-1 rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                            <i class="bi bi-check-circle-fill text-[9px]"></i> Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full border border-rose-100 bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700">
                                            <i class="bi bi-x-circle-fill text-[9px]"></i> Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex justify-center gap-1">
                                        @if ((int) $admin->id === $currentUserId)
                                            <a href="{{ url('useradmin/edit/' . $admin->id) }}" title="Edit Profil"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 shadow-xs transition-colors hover:bg-blue-100">
                                                <i class="bi bi-gear-fill text-sm"></i>
                                            </a>
                                        @elseif ($isSuperAdmin)
                                            <a href="{{ url('useradmin/edit/' . $admin->id) }}" title="Edit"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 shadow-xs transition-colors hover:bg-indigo-100">
                                                <i class="bi bi-pencil text-sm"></i>
                                            </a>
                                            <button type="button" title="Hapus"
                                                @click="deleteAdmin({{ (int) $admin->id }}, @js(trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''))))"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 shadow-xs transition-colors hover:bg-rose-100">
                                                <i class="bi bi-trash3 text-sm"></i>
                                            </button>
                                        @else
                                            <span class="text-slate-300" title="Tidak ada akses">
                                                <i class="bi bi-ban"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>

                    <div class="flex flex-col justify-between gap-3 border-t border-slate-100 px-5 py-3.5 sm:flex-row sm:items-center">
                        <div class="text-xs font-medium text-slate-500" aria-live="polite"
                            x-text="total === 0 ? 'Tidak ada data' : 'Menampilkan ' + from + '\u2013' + to + ' dari ' + total + ' admin'">
                        </div>
                        <div class="flex items-center gap-2" x-show="pages > 1" x-cloak>
                            <button type="button" @click="prev()" :disabled="page <= 1" aria-label="Halaman sebelumnya"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <span class="text-xs font-semibold text-slate-500"
                                x-text="'Halaman ' + page + ' / ' + pages"></span>
                            <button type="button" @click="next()" :disabled="page >= pages" aria-label="Halaman berikutnya"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </x-ui.card>
    </div>

    <!-- Modal Tambah Admin -->
    <x-ui.modal id="createAdminModal" title="Tambah Admin Baru" icon="bi-person-plus-fill" size="max-w-lg">
        <form @submit.prevent="submitCreate()" class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="createFirstName" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">First Name <span class="text-rose-500">*</span></label>
                        <input type="text" id="createFirstName" x-model="form.first_name" maxlength="50" required
                            placeholder="Budi" autocomplete="off"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <p class="mt-1 text-xs text-rose-600" x-show="errors.first_name" x-cloak x-text="errors.first_name"></p>
                    </div>
                    <div>
                        <label for="createLastName" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Last Name</label>
                        <input type="text" id="createLastName" x-model="form.last_name" maxlength="50"
                            placeholder="Santoso" autocomplete="off"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>

                <div>
                    <label for="createUsername" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Username <span class="text-rose-500">*</span></label>
                    <input type="text" id="createUsername" x-model="form.username" maxlength="30" required
                        placeholder="budi.admin" autocomplete="off"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    <p class="mt-1 text-xs text-rose-600" x-show="errors.username" x-cloak x-text="errors.username"></p>
                </div>

                <div x-data="{ show: false }">
                    <label for="createPassword" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Password <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" id="createPassword" x-model="form.password"
                            maxlength="72" required placeholder="Min. 6 karakter" autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 pr-10 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <button type="button" @click="show = !show"
                            :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
                            class="absolute inset-y-0 right-0 px-3 text-slate-400 transition-colors hover:text-slate-600">
                            <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-rose-600" x-show="errors.password" x-cloak x-text="errors.password"></p>
                </div>

                <div x-data="{ show: false }">
                    <label for="createPasswordConfirm" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Konfirmasi Password <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" id="createPasswordConfirm" x-model="form.confirm_password"
                            maxlength="72" required placeholder="Ulangi password" autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 pr-10 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <button type="button" @click="show = !show"
                            :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
                            class="absolute inset-y-0 right-0 px-3 text-slate-400 transition-colors hover:text-slate-600">
                            <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-rose-600" x-show="errors.confirm_password" x-cloak x-text="errors.confirm_password"></p>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#createAdminModal')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="button" @click="resetForm()"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">
                    <i class="bi bi-arrow-repeat"></i> Reset
                </button>
                <button type="submit" :disabled="saving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                    <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!saving" class="bi bi-check-lg"></i> Simpan
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('adminPage', () => ({
        urls: {},
        csrf: '',

        saving: false,
        errors: {},

        form: { first_name: '', last_name: '', username: '', password: '', confirm_password: '' },

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');
            this.urls = cfg.urls || {};
            this.csrf = cfg.csrf || '';
        },

        reload() {
            window.location.reload();
        },

        emptyForm() {
            return { first_name: '', last_name: '', username: '', password: '', confirm_password: '' };
        },

        resetForm() {
            this.errors = {};
            this.form = this.emptyForm();
        },

        openCreate() {
            this.resetForm();
            this.$store.modals.open('#createAdminModal');
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

        async submitCreate() {
            if (this.saving) return;

            this.saving = true;
            this.errors = {};

            try {
                const body = new URLSearchParams();
                body.append('_token', this.csrf);
                Object.keys(this.form).forEach((key) => body.append(key, this.form[key] ?? ''));

                const result = await this.requestJson(this.urls.create, body);

                if (result && result.status) {
                    this.$store.modals.close('#createAdminModal');
                    this.resetForm();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: result.msg || 'Admin berhasil dibuat',
                        timer: 1600,
                        showConfirmButton: false,
                    }).then(() => window.location.reload());
                    return;
                }

                this.errors = (result && result.errors) || {};
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: (result && result.msg) || 'Admin gagal dibuat — periksa isian lalu coba lagi.',
                });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            } finally {
                this.saving = false;
            }
        },

        async deleteAdmin(id, nama) {
            if (this.saving) return;

            const konfirmasi = await Swal.fire({
                title: 'Hapus admin?',
                text: (nama || 'Akun ini') + ' akan dihapus permanen.',
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

                const result = await this.requestJson(this.urls.delete + '/' + id, body);

                if (result && result.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: result.msg || 'Admin berhasil dihapus',
                        timer: 1600,
                        showConfirmButton: false,
                    }).then(() => window.location.reload());
                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: (result && result.msg) || 'Admin gagal dihapus.',
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
