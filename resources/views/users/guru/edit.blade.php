@extends('layouts.main')

@section('content')

<?php
$users = $users ?? null;
$user = $user ?? null;
$guru = $guru ?? null;
$is_admin = $is_admin ?? false;

$akunAktif = ! is_null($users) && (int) ($users->active ?? 0) === 1;
$punyaAkun = ! is_null($users);

$pageConfig = [
    'csrf' => csrf_token(),
    'urls' => [
        'activate' => url('userguru/activate'),
        'editlogin' => url('userguru/editlogin'),
    ],
    'guru' => [
        'id' => (int) ($guru->id ?? 0),
        'id_guru' => (int) ($guru->id_guru ?? 0),
        'nama' => (string) ($guru->nama_guru ?? ''),
        'username' => (string) ($guru->username ?? ''),
    ],
    'account' => [
        'has' => $punyaAkun,
        'id' => (int) ($users->id ?? 0),
        'active' => $akunAktif,
    ],
    'canManageStatus' => (bool) $is_admin,
    'initial' => [
        'first_name' => (string) ($users->first_name ?? ''),
        'last_name' => (string) ($users->last_name ?? ''),
        'email' => (string) ($users->email ?? ''),
        'username' => (string) ($users->username ?? ''),
    ],
];
?>

<div x-data="guruAccountPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <!-- Content Header -->
    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="($judul ?? 'User Management') . ' — ' . ($guru->nama_guru ?? '-')"
            icon="bi-person-video3"
            tone="emerald"
            :subtitle="($subjudul ?? 'Edit Data User') . ' · username ' . ($guru->username ?? '-')"
            :crumbs="['User Management', $subjudul ?? 'Edit Data User']">
            <x-slot:actions>
                <a href="{{ url('admin/users') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:border-slate-300 hover:bg-slate-50">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </x-slot:actions>
        </x-ui.page-header>
    </div>

    <div class="mt-4 px-4 pb-6 md:px-6">
        @if (($user->username ?? null) === ($guru->username ?? null) || $is_admin)
            <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-2">

                <!-- Kartu: data login dasar -->
                <x-ui.card :title="'Edit Login ' . ($guru->nama_guru ?? '')" icon="bi-person-badge" tone="blue"
                    subtitle="Nama dan email yang dipakai akun login guru">

                    <x-slot:actions>
                        @if (! $punyaAkun)
                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                                Belum aktif
                            </span>
                            <button type="button" @click="toggleStatus(true)" :disabled="savingStatus"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-emerald-700 disabled:opacity-60">
                                <i class="bi bi-person-check-fill"></i> Aktifkan
                            </button>
                        @else
                            <span :class="active
                                    ? 'border-emerald-100 bg-emerald-50 text-emerald-700'
                                    : 'border-rose-100 bg-rose-50 text-rose-700'"
                                class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                x-text="active ? 'Aktif' : 'Nonaktif'"></span>
                            @if ($is_admin)
                                <button type="button" @click="toggleStatus(!active)" :disabled="savingStatus"
                                    :class="active
                                        ? 'border-rose-200 text-rose-600 hover:bg-rose-50'
                                        : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50'"
                                    class="inline-flex items-center gap-1.5 rounded-xl border bg-white px-3 py-1.5 text-xs font-semibold transition-colors disabled:opacity-60">
                                    <i class="bi bi-power"></i>
                                    <span x-text="active ? 'Nonaktifkan' : 'Aktifkan'"></span>
                                </button>
                            @endif
                        @endif
                    </x-slot:actions>

                    @if (! $punyaAkun)
                        <div class="mb-4">
                            <x-ui.empty icon="bi-person-dash" title="Akun login belum dibuat"
                                description="Aktifkan akun untuk membuat login guru dengan password default, lalu nama dan email bisa diubah di sini." />
                        </div>
                    @endif

                    <form @submit.prevent="submitInfo()" class="space-y-4">
                        <div>
                            <label for="first_name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                Nama Depan <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="first_name" x-model="form.first_name" maxlength="50" required
                                :disabled="!hasAccount" placeholder="Nama Depan"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:text-slate-400">
                            <p class="mt-1 text-xs text-rose-600" x-show="errors.first_name" x-cloak x-text="errors.first_name"></p>
                        </div>

                        <div>
                            <label for="last_name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Nama Belakang</label>
                            <input type="text" id="last_name" x-model="form.last_name" maxlength="50"
                                :disabled="!hasAccount" placeholder="Nama Belakang"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:text-slate-400">
                            <p class="mt-1 text-xs text-rose-600" x-show="errors.last_name" x-cloak x-text="errors.last_name"></p>
                        </div>

                        <div>
                            <label for="email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                Email <span class="text-rose-500">*</span>
                            </label>
                            <input type="email" id="email" x-model="form.email" maxlength="100" required
                                :disabled="!hasAccount" placeholder="Email"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:text-slate-400">
                            <p class="mt-1 text-xs text-rose-600" x-show="errors.email" x-cloak x-text="errors.email"></p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="username_readonly" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Username</label>
                                <input type="text" id="username_readonly" value="{{ $guru->username ?? '' }}" readonly disabled
                                    class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2 font-mono text-sm text-slate-500">
                            </div>
                            <div>
                                <label for="password_readonly" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Password tersimpan</label>
                                <input type="password" id="password_readonly" value="********" readonly disabled
                                    class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2 text-sm text-slate-500">
                            </div>
                        </div>

                        <p class="text-[11px] text-slate-400">
                            Password tidak ditampilkan demi keamanan. Gunakan kartu Ubah Password untuk mengganti.
                        </p>

                        <button type="submit" :disabled="!hasAccount || savingInfo"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                            <span x-show="savingInfo" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                            <i x-show="!savingInfo" class="bi bi-save"></i> Simpan
                        </button>
                    </form>
                </x-ui.card>

                <!-- Kartu: ubah password & username -->
                <x-ui.card title="Ubah Password & Username" icon="bi-key" tone="amber"
                    subtitle="Ganti username login atau password akun guru">
                    <form @submit.prevent="submitPassword()" class="space-y-4">
                        <div>
                            <label for="username" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                Username <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="username" x-model="form.username" minlength="4" maxlength="30" required
                                :disabled="!hasAccount" placeholder="Username" autocomplete="off"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 font-mono text-sm transition-all outline-hidden focus:border-amber-500 focus:bg-white focus:ring-2 focus:ring-amber-500/20 disabled:cursor-not-allowed disabled:text-slate-400">
                            <p class="mt-1 text-xs text-rose-600" x-show="errors.username" x-cloak x-text="errors.username"></p>
                        </div>

                        <div>
                            <label for="old" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                Password Lama <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input :type="showOld ? 'text' : 'password'" id="old" x-model="form.old" required
                                    :disabled="!hasAccount" placeholder="Password Lama" autocomplete="current-password"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 pr-10 text-sm transition-all outline-hidden focus:border-amber-500 focus:bg-white focus:ring-2 focus:ring-amber-500/20 disabled:cursor-not-allowed disabled:text-slate-400">
                                <button type="button" @click="showOld = !showOld"
                                    :aria-label="showOld ? 'Sembunyikan password lama' : 'Tampilkan password lama'"
                                    class="absolute inset-y-0 right-0 px-3 text-slate-400 transition-colors hover:text-slate-600">
                                    <i class="bi" :class="showOld ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-rose-600" x-show="errors.old" x-cloak x-text="errors.old"></p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="new" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                    Password Baru <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showNew ? 'text' : 'password'" id="new" x-model="form.new"
                                        minlength="6" maxlength="72" required :disabled="!hasAccount"
                                        placeholder="Min. 6 karakter" autocomplete="new-password"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 pr-10 text-sm transition-all outline-hidden focus:border-amber-500 focus:bg-white focus:ring-2 focus:ring-amber-500/20 disabled:cursor-not-allowed disabled:text-slate-400">
                                    <button type="button" @click="showNew = !showNew"
                                        :aria-label="showNew ? 'Sembunyikan password baru' : 'Tampilkan password baru'"
                                        class="absolute inset-y-0 right-0 px-3 text-slate-400 transition-colors hover:text-slate-600">
                                        <i class="bi" :class="showNew ? 'bi-eye-slash' : 'bi-eye'"></i>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-rose-600" x-show="errors.new" x-cloak x-text="errors.new"></p>
                            </div>
                            <div>
                                <label for="new_confirm" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                    Konfirmasi Password <span class="text-rose-500">*</span>
                                </label>
                                <input type="password" id="new_confirm" x-model="form.new_confirm" required
                                    :disabled="!hasAccount" placeholder="Ulangi password baru" autocomplete="new-password"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-amber-500 focus:bg-white focus:ring-2 focus:ring-amber-500/20 disabled:cursor-not-allowed disabled:text-slate-400">
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="submit" :disabled="!hasAccount || savingPassword"
                                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-60">
                                <span x-show="savingPassword" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                                <i x-show="!savingPassword" class="bi bi-key"></i> Ganti Password
                            </button>
                            <button type="button" @click="resetPasswordForm()" :disabled="!hasAccount || savingPassword"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-60">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </button>
                        </div>
                    </form>
                </x-ui.card>
            </div>
        @endif
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('guruAccountPage', () => ({
        config: { urls: {}, csrf: '', guru: {}, account: {}, initial: {} },

        savingInfo: false,
        savingPassword: false,
        savingStatus: false,
        errors: {},

        showOld: false,
        showNew: false,

        form: {
            first_name: '',
            last_name: '',
            email: '',
            username: '',
            old: '',
            new: '',
            new_confirm: '',
        },

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');
            this.config = Object.assign({ urls: {}, csrf: '', guru: {}, account: {}, initial: {} }, cfg);
            this.config.urls = cfg.urls || {};
            this.config.guru = cfg.guru || {};
            this.config.account = cfg.account || {};
            this.config.initial = cfg.initial || {};

            Object.keys(this.config.initial).forEach((key) => {
                if (Object.prototype.hasOwnProperty.call(this.form, key)) {
                    this.form[key] = this.config.initial[key] ?? '';
                }
            });
        },

        get csrf() {
            return this.config.csrf;
        },

        get hasAccount() {
            return Boolean(this.config.account && this.config.account.has);
        },

        get active() {
            return Boolean(this.config.account && this.config.account.active);
        },

        async requestJson(url, params) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': this.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: params,
            });

            return response.json();
        },

        post(url, data = {}) {
            const body = new URLSearchParams();
            body.append('_token', this.csrf);

            Object.keys(data).forEach((key) => body.append(key, data[key] ?? ''));

            return this.requestJson(url, body);
        },

        errorText(result, fallback) {
            if (result && result.msg) return result.msg;

            if (result && result.errors && typeof result.errors === 'object') {
                const pesan = Object.values(result.errors).filter((value) => value);

                if (pesan.length > 0) return pesan.join('\n');
            }

            return fallback;
        },

        resetPasswordForm() {
            this.errors = {};
            this.form.old = '';
            this.form.new = '';
            this.form.new_confirm = '';
        },

        async submitInfo() {
            if (!this.hasAccount || this.savingInfo) return;

            this.savingInfo = true;
            this.errors = {};

            try {
                const result = await this.post(this.config.urls.editlogin, {
                    id: this.config.account.id,
                    id_guru: this.config.guru.id_guru,
                    old_username: this.config.guru.username,
                    username: this.form.username || this.config.guru.username,
                    first_name: this.form.first_name,
                    last_name: this.form.last_name,
                    email: this.form.email,
                });

                if (result && result.status) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Data login berhasil diupdate.' });
                    return;
                }

                this.errors = (result && result.errors) || {};
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: this.errorText(result, 'Gagal menyimpan data login.'),
                });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menghubungi server.' });
            } finally {
                this.savingInfo = false;
            }
        },

        async submitPassword() {
            if (!this.hasAccount || this.savingPassword) return;

            if ((this.form.new || '').length < 6) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Password baru minimal 6 karakter.' });
                return;
            }

            if (this.form.new !== this.form.new_confirm) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Konfirmasi password tidak cocok.' });
                return;
            }

            this.savingPassword = true;
            this.errors = {};

            try {
                const result = await this.post(this.config.urls.editlogin, {
                    id: this.config.account.id,
                    id_guru: this.config.guru.id_guru,
                    old_username: this.config.guru.username,
                    username: this.form.username,
                    old: this.form.old,
                    new: this.form.new,
                    new_confirm: this.form.new_confirm,
                });

                if (result && result.status) {
                    this.config.guru.username = this.form.username;
                    this.config.guru.nama = (this.form.first_name || '') + ' ' + (this.form.last_name || '');
                    this.resetPasswordForm();
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: result.msg || 'Login diperbarui.' });
                    return;
                }

                this.errors = (result && result.errors) || {};
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: this.errorText(result, 'Gagal memperbarui login.'),
                });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menghubungi server.' });
            } finally {
                this.savingPassword = false;
            }
        },

        async toggleStatus(aktifkan) {
            if (this.savingStatus) return;

            const konfirmasi = await Swal.fire({
                title: aktifkan ? 'Aktifkan akun guru ini?' : 'Nonaktifkan akun guru ini?',
                text: aktifkan ? 'Guru dapat login kembali.' : 'Guru tidak dapat login sampai diaktifkan lagi.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: aktifkan ? '#16a34a' : '#d97706',
                confirmButtonText: aktifkan ? 'Ya, Aktifkan' : 'Ya, Nonaktifkan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.savingStatus = true;

            try {
                const result = await this.post(this.config.urls.activate, {
                    id: this.config.account.id || '',
                    id_guru: this.config.guru.id_guru,
                    nama: this.config.guru.nama,
                    is_active: aktifkan ? 'aktifkan' : 'nonaktifkan',
                });

                if (result && result.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: result.msg || 'Status diubah.',
                    }).then(() => window.location.reload());
                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: this.errorText(result, 'Gagal mengubah status akun.'),
                });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menghubungi server.' });
            } finally {
                this.savingStatus = false;
            }
        },
    }));
});
</script>
@endsection
