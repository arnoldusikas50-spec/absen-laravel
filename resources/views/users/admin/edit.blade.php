@extends('layouts.main')

@section('content')

@php
    $rawFoto = trim((string) ($users->foto ?? ''));
    $fotoPath = '';
    if ($rawFoto !== '' && ! str_contains($rawFoto, '..')) {
        $cleanPath = ltrim($rawFoto, '/');
        if (is_file(public_path($cleanPath))) {
            $fotoPath = $cleanPath;
        } elseif (is_file(public_path('uploads/profiles/'.$cleanPath))) {
            $fotoPath = 'uploads/profiles/'.$cleanPath;
        }
    }

    $displayName = trim(($users->first_name ?? '').' '.($users->last_name ?? '')) ?: ($users->username ?? '');
    $initial = mb_strtoupper(mb_substr((string) ($users->first_name ?? $users->username ?? 'A'), 0, 1));

    $adminConfig = [
        'csrf' => csrf_token(),
        'ids' => [
            'user' => (int) $users->id,
            'current' => (int) $user->id,
        ],
        'foto' => $fotoPath !== '' ? url($fotoPath) : '',
        'initial' => $initial,
        'urls' => [
            'info' => url('useradmin/edit_info'),
            'password' => url('useradmin/change_password'),
            'upload' => url('useradmin/uploadfile/'.(int) $users->id),
            'delete' => url('useradmin/deletefile'),
            'logout' => url('auth/logout'),
        ],
    ];
@endphp

<div x-data="adminEdit()"
    data-config="{{ json_encode($adminConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <!-- Content Header -->
    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="($judul ?? 'Administrator') . ' — ' . $displayName"
            icon="bi-person-gear"
            :subtitle="($subjudul ?? 'Edit Data Admin') . ' · username ' . ($users->username ?? '-')"
            :crumbs="['Manajemen Admin', $subjudul ?? 'Edit Data Admin']">
            <x-slot:actions>
                <a href="{{ url('useradmin') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:border-slate-300 hover:bg-slate-50">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </x-slot:actions>
        </x-ui.page-header>
    </div>

    <!-- Main Content Area -->
    <div class="mt-4 grid grid-cols-1 items-start gap-5 px-4 pb-6 md:px-6 xl:grid-cols-2">

        <!-- Kartu data admin -->
        <x-ui.card title="Data Admin" icon="bi-person-badge" :subtitle="$subjudul ?? 'Edit Data Admin'">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-12">
                <div class="md:col-span-5">
                    <p class="mb-2 block text-xs font-semibold uppercase tracking-wider text-slate-700">Foto Profil</p>

                    <div class="flex flex-col items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                        <div class="relative flex h-32 w-32 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                            <img x-show="photoUrl !== ''" :src="photoUrl" alt="Foto profil {{ $displayName }}"
                                class="h-full w-full object-cover">
                            <span x-show="photoUrl === ''" x-cloak
                                class="text-3xl font-extrabold text-slate-300">{{ $initial }}</span>

                            <div x-show="uploading" x-cloak
                                class="absolute inset-0 flex items-center justify-center bg-slate-900/50">
                                <span class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700"
                                :class="uploading ? 'pointer-events-none opacity-60' : ''">
                                <i class="bi bi-upload"></i> Ganti Foto
                                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif"
                                    class="sr-only" @change="uploadPhoto($event)" :disabled="uploading">
                            </label>
                            <button type="button" x-show="photoUrl !== ''" x-cloak @click="deletePhoto()"
                                :disabled="uploading"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-3.5 py-2 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-50 disabled:opacity-60">
                                <i class="bi bi-trash3"></i> Hapus
                            </button>
                        </div>

                        <p class="text-center text-[11px] leading-relaxed text-slate-400">
                            <i class="bi bi-info-circle"></i> JPG, PNG, WEBP, atau GIF. Maksimal 2MB.
                        </p>
                    </div>
                </div>

                <div class="md:col-span-7">
                    <form x-ref="infoForm" @submit.prevent="submitInfo()" novalidate class="space-y-4">
                        @csrf
                        <input type="hidden" name="id" value="{{ $users->id }}">

                        <div>
                            <label for="first_name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">First Name <span class="text-rose-500">*</span></label>
                            <input type="text" id="first_name" name="first_name" maxlength="50" required
                                value="{{ $users->first_name ?? '' }}" autocomplete="given-name"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        </div>

                        <div>
                            <label for="last_name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Last Name</label>
                            <input type="text" id="last_name" name="last_name" maxlength="50"
                                value="{{ $users->last_name ?? '' }}" autocomplete="family-name"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        </div>

                        <div>
                            <label for="email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Email</label>
                            <input type="email" id="email" name="email" maxlength="100"
                                value="{{ $users->email ?? '' }}" autocomplete="email"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        </div>

                        <div>
                            <label for="jabatan" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Jabatan</label>
                            <input type="text" id="jabatan" name="jabatan" maxlength="50" placeholder="Jabatan"
                                value="{{ $users->jabatan ?? '' }}"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-1">
                            <button type="reset"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </button>
                            <button type="submit" :disabled="savingInfo"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                                <span x-show="savingInfo" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                                <i x-show="!savingInfo" class="bi bi-save"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </x-ui.card>

        <!-- Kartu data login -->
        <x-ui.card title="Edit Data Login" icon="bi-key" tone="amber"
            subtitle="Ubah username atau ganti password akun administrator">
            <form x-ref="loginForm" @submit.prevent="submitPassword()" novalidate class="space-y-4">
                @csrf
                <input type="hidden" name="id" value="{{ $users->id }}">

                <div>
                    <label for="username" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Username</label>
                    <input type="text" id="username" name="username" maxlength="30" required
                        value="{{ $users->username ?? '' }}" autocomplete="username" spellcheck="false"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 font-mono text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    <p class="mt-1 text-[11px] text-slate-400">Hanya huruf, angka, titik, garis bawah, dan tanda hubung (min. 4).</p>
                </div>

                <div>
                    <label for="old" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Password Lama</label>
                    <input type="password" id="old" name="old" placeholder="Kosongkan bila hanya ingin mengganti username"
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="new" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Password Baru</label>
                        <input type="password" id="new" name="new" placeholder="Min. 6 karakter" minlength="6" maxlength="72"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <div>
                        <label for="new_confirm" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Konfirmasi Password</label>
                        <input type="password" id="new_confirm" name="new_confirm" placeholder="Ulangi password baru"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="reset"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                    <button type="submit" :disabled="savingPassword"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-amber-500/20 transition-colors hover:bg-amber-600 disabled:opacity-60">
                        <span x-show="savingPassword" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                        <i x-show="!savingPassword" class="bi bi-key"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('adminEdit', () => ({
        csrf: '',
        urls: {},
        idUser: 0,
        isSelf: false,

        photoUrl: '',
        uploading: false,
        savingInfo: false,
        savingPassword: false,

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');
            this.csrf = cfg.csrf || '';
            this.urls = cfg.urls || {};
            this.idUser = Number(cfg.ids?.user || 0);
            this.isSelf = Number(cfg.ids?.current || 0) === this.idUser;
            this.photoUrl = cfg.foto || '';
        },

        errorHtml(errors) {
            if (!errors || typeof errors !== 'object') return '';
            return Object.values(errors)
                .flat()
                .filter((message) => message != null && message !== '')
                .join('<br>');
        },

        async post(url, payload) {
            const headers = {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrf,
                'X-Requested-With': 'XMLHttpRequest',
            };
            const isFormData = payload instanceof FormData;
            if (!isFormData) {
                headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
            }

            const response = await fetch(url, {
                method: 'POST',
                headers,
                body: payload,
            });

            return response.json();
        },

        async uploadPhoto(event) {
            const input = event.target;
            const file = input.files && input.files[0];
            if (!file) return;

            const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!allowed.includes(file.type)) {
                Swal.fire('Format Tidak Diizinkan', 'Gunakan foto berformat JPG, PNG, WEBP, atau GIF.', 'warning');
                input.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire('Terlalu Besar', 'Ukuran foto maksimal 2MB.', 'warning');
                input.value = '';
                return;
            }

            const body = new FormData();
            body.append('_token', this.csrf);
            body.append('foto', file);

            this.uploading = true;
            try {
                const result = await this.post(this.urls.upload, body);

                if (result && result.status) {
                    if (result.src) {
                        this.photoUrl = result.src;
                    }
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: result.message || 'Foto profil diperbarui.',
                        showConfirmButton: false,
                        timer: 2000,
                    });
                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: (result && (result.message || result.msg)) || 'Foto gagal diunggah — coba lagi.',
                });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            } finally {
                this.uploading = false;
                input.value = '';
            }
        },

        async deletePhoto() {
            if (this.uploading || this.photoUrl === '') return;

            const konfirmasi = await Swal.fire({
                title: 'Hapus Foto Profil?',
                text: 'Foto profil akan dihapus dari akun ini.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });
            if (!konfirmasi.isConfirmed) return;

            const body = new FormData();
            body.append('_token', this.csrf);
            body.append('id_user', this.idUser);
            body.append('src', this.photoUrl.split('?')[0]);

            this.uploading = true;
            try {
                const result = await this.post(this.urls.delete, body);

                if (result && result.status) {
                    this.photoUrl = '';
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: result.message || 'Foto profil berhasil dihapus.',
                        showConfirmButton: false,
                        timer: 2000,
                    });
                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: (result && (result.message || result.msg)) || 'Foto gagal dihapus — coba lagi.',
                });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            } finally {
                this.uploading = false;
            }
        },

        async submitInfo() {
            if (this.savingInfo) return;

            const form = this.$refs.infoForm;
            if (!form.reportValidity()) return;

            this.savingInfo = true;
            try {
                const result = await this.post(this.urls.info, new FormData(form));

                if (result && result.status) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: 'Data admin berhasil diperbarui.',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                    window.location.reload();
                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    html: this.errorHtml(result && result.errors) || 'Data admin gagal disimpan — periksa isian lalu coba lagi.',
                });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            } finally {
                this.savingInfo = false;
            }
        },

        async submitPassword() {
            if (this.savingPassword) return;

            const form = this.$refs.loginForm;
            if (!form.reportValidity()) return;

            const data = new FormData(form);
            const username = String(data.get('username') || '').trim();
            if (username.length < 4) {
                Swal.fire('Gagal', 'Username minimal 4 karakter.', 'warning');
                return;
            }

            this.savingPassword = true;
            try {
                const result = await this.post(this.urls.password, data);

                if (result && result.status) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Sukses',
                        text: result.msg || 'Data login berhasil diperbarui.',
                    });
                    if (this.isSelf) {
                        window.location.href = this.urls.logout;
                        return;
                    }
                    window.location.reload();
                    return;
                }

                const detail = this.errorHtml(result && result.errors);
                if (detail !== '') {
                    Swal.fire({ icon: 'error', title: 'ERROR', html: detail });
                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: (result && (result.msg || result.message)) || 'Data login gagal diperbarui.',
                });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            } finally {
                this.savingPassword = false;
            }
        },
    }));
});
</script>
@endsection
