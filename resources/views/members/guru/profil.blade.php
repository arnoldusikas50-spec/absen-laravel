@extends('layouts.main')

@section('content')

@php
    $dokumenAwal = is_array($guru->dokumen ?? null) ? $guru->dokumen : [];

    $pageConfig = [
        'csrf' => csrf_token(),
        'base' => rtrim(url('/'), '/'),
        'defaultFoto' => asset('assets/img/guru.png'),
        'foto' => $foto,
        'ttd' => $ttd,
        'dokumen' => $dokumenAwal,
        'urls' => [
            'updateProfil' => url('guru/updateProfil'),
            'uploadFoto' => url('guru/uploadFoto'),
            'deleteFoto' => url('guru/deleteFoto'),
            'updateLogin' => url('guru/updateLogin'),
            'saveSignature' => url('guru/saveSignature'),
            'deleteTtd' => url('guru/deleteTtd'),
            'deleteDokumen' => url('guru/deleteDokumen'),
        ],
    ];

    $foto = $guru->foto && file_exists(public_path() . '/' . $guru->foto)
        ? url($guru->foto)
        : asset('assets/img/guru.png');

    $ttd = ! empty($guru->ttd) ? url($guru->ttd) : '';
@endphp

<style>
    .sig-canvas {
        width: 100%;
        height: 200px;
        border: 2px dashed #cbd5e1;
        border-radius: 1rem;
        background-color: #ffffff;
        cursor: crosshair;
        touch-action: none;
    }
</style>

<div x-data="guruProfil()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <!-- Content Header -->
    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'Profil Guru'"
            icon="bi-person-circle"
            tone="violet"
            :subtitle="$subjudul ?? 'Data Profil Saya'"
            :crumbs="[$subjudul ?? 'Data Profil Saya']" />
    </div>

    <div class="mt-4 grid grid-cols-1 gap-5 px-4 pb-6 md:px-6 xl:grid-cols-12">

        <!-- Kartu Identitas -->
        <div class="xl:col-span-4">
            <x-ui.card :body-class="'p-5 text-center'">
                <img :src="fotoSrc" alt="Foto profil {{ $guru->nama_guru }}"
                    class="mx-auto mb-3 h-36 w-36 rounded-full border-4 border-white object-cover shadow-md ring-1 ring-slate-200">

                <h2 class="text-lg font-bold text-slate-800">{{ $guru->nama_guru }}</h2>
                <p class="mt-0.5 mb-4 text-xs font-semibold text-slate-500">
                    {{ trim(($guru->level ?? '') . ' ' . ($guru->nama_kelas ?? '')) ?: 'Guru' }}
                </p>

                <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="$store.modals.open('#editFotoModal')"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                        <i class="bi bi-image"></i> Ganti Foto
                    </button>
                    <button type="button" @click="deleteFoto()" :disabled="saving"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white shadow-xs shadow-rose-500/20 transition-colors hover:bg-rose-700 disabled:opacity-60">
                        <i class="bi bi-trash"></i> Hapus Foto
                    </button>
                </div>

                <button type="button" @click="$store.modals.open('#editLoginModal')"
                    class="mt-2 inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-amber-500 px-3 py-2 text-xs font-semibold text-white shadow-xs shadow-amber-500/20 transition-colors hover:bg-amber-600">
                    <i class="bi bi-pencil"></i> Edit Username / Password
                </button>

                <div class="mt-5 text-left">
                    <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tanda Tangan</p>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-3">
                        <div x-show="!ttdSrc" class="flex h-24 items-center justify-center rounded-xl border-2 border-dashed border-slate-300 text-xs font-medium text-slate-400">
                            Belum ada TTD
                        </div>
                        <img x-show="ttdSrc" x-cloak :src="ttdSrc" alt="Tanda tangan"
                            class="mx-auto block max-h-24 rounded-xl">
                        <div class="mt-2 flex items-center justify-center gap-2">
                            <button type="button" @click="openSignature()"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-emerald-700">
                                <i class="bi bi-pencil-square"></i> Buat
                            </button>
                            <button type="button" x-show="ttdSrc" x-cloak @click="deleteTtd()" :disabled="saving"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-rose-700 disabled:opacity-60"
                                aria-label="Hapus tanda tangan">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Kartu Data Profil -->
        <div class="xl:col-span-8">
            <x-ui.card title="Data Profil" icon="bi-person-vcard" :body-class="'p-0'"
                subtitle="Perbarui data pribadi, alamat, dan dokumen pendukung Anda">

                <x-slot:footer>
                    <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
                        <small class="text-xs font-semibold text-rose-500"><i>* Semua kolom profil wajib diisi</i></small>
                        <button type="submit" form="formguru" :disabled="saving"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                            <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                            <i x-show="!saving" class="bi bi-save"></i> Simpan
                        </button>
                    </div>
                </x-slot:footer>

                <div class="border-b border-slate-100 px-5 pt-3">
                    <nav class="-mb-px flex gap-1 overflow-x-auto" role="tablist" aria-label="Bagian profil">
                        <button type="button" role="tab" @click="tab = 'profile'"
                            :aria-selected="tab === 'profile' ? 'true' : 'false'"
                            class="whitespace-nowrap border-b-2 px-4 py-2.5 text-xs font-bold transition-colors"
                            :class="tab === 'profile' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                            Profile
                        </button>
                        <button type="button" role="tab" @click="tab = 'address'"
                            :aria-selected="tab === 'address' ? 'true' : 'false'"
                            class="whitespace-nowrap border-b-2 px-4 py-2.5 text-xs font-bold transition-colors"
                            :class="tab === 'address' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                            Data Lengkap
                        </button>
                        <button type="button" role="tab" @click="tab = 'docs'"
                            :aria-selected="tab === 'docs' ? 'true' : 'false'"
                            class="whitespace-nowrap border-b-2 px-4 py-2.5 text-xs font-bold transition-colors"
                            :class="tab === 'docs' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                            Dokumen
                        </button>
                    </nav>
                </div>

                <form id="formguru" @submit.prevent="submitProfil($event)" enctype="multipart/form-data" class="p-5">
                    @csrf
                    <input type="hidden" name="id_guru" value="{{ $id_active }}">

                    <!-- Tab Profile -->
                    <div x-show="tab === 'profile'" class="space-y-4">
                        @foreach ($input_profile as $input)
                            <div class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:gap-4">
                                <label for="profil-{{ $input->name }}" class="flex items-center gap-2 text-xs font-semibold text-slate-600 sm:w-1/4">
                                    <i class="{{ $input->icon }} text-slate-400"></i> {{ $input->label }}
                                </label>
                                <div class="sm:w-3/4">
                                    @if ($input->name === 'jenis_kelamin')
                                        <select id="profil-jenis_kelamin" name="jenis_kelamin" required
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                            <option value="" disabled {{ empty($input->value) ? 'selected' : '' }}>Pilih Jenis Kelamin</option>
                                            <option value="L" {{ $input->value == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                            <option value="P" {{ $input->value == 'P' ? 'selected' : '' }}>Perempuan</option>
                                        </select>
                                    @elseif ($input->name === 'agama')
                                        <select id="profil-agama" name="agama"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                            @foreach (['Islam', 'Katolik', 'Protestan', 'Hindu', 'Budha', 'Konghucu'] as $agama)
                                                <option value="{{ $agama }}" {{ $input->value == $agama ? 'selected' : '' }}>{{ $agama }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="{{ $input->type }}" id="profil-{{ $input->name }}"
                                            name="{{ $input->name }}" value="{{ $input->value }}"
                                            placeholder="{{ $input->label }}"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Tab Data Lengkap -->
                    <div x-show="tab === 'address'" x-cloak class="space-y-4">
                        @foreach ($input_alamat as $input)
                            <div class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:gap-4">
                                <label for="alamat-{{ $input->name }}" class="flex items-center gap-2 text-xs font-semibold text-slate-600 sm:w-1/4">
                                    <i class="{{ $input->icon }} text-slate-400"></i> {{ $input->label }}
                                </label>
                                <div class="sm:w-3/4">
                                    @if ($input->name === 'tgl_lahir')
                                        <input type="date" id="alamat-tgl_lahir" name="tgl_lahir"
                                            value="{{ $input->value }}"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                    @else
                                        <input type="{{ $input->type }}" id="alamat-{{ $input->name }}"
                                            name="{{ $input->name }}" value="{{ $input->value }}"
                                            placeholder="{{ $input->label }}"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Tab Dokumen -->
                    <div x-show="tab === 'docs'" x-cloak class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach ($input_dokumen as $doc)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                                <label for="{{ $doc->id }}" class="mb-2 block text-xs font-bold text-slate-700">{{ $doc->label }}</label>
                                <input type="file" id="{{ $doc->id }}" name="{{ $doc->name }}"
                                    accept=".pdf,.jpg,.jpeg,.png,.gif" {{ $doc->id === 'sertifikat' ? 'multiple' : '' }}
                                    class="block w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100">

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <template x-for="item in docsFor('{{ $doc->id }}')" :key="item.src">
                                        <div class="relative">
                                            <img :src="item.url" alt="Dokumen {{ $doc->label }}"
                                                class="h-24 w-24 rounded-xl border border-slate-200 bg-white object-cover">
                                            <button type="button" @click="deleteDoc(item.src)"
                                                class="absolute -right-1.5 -top-1.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-600 text-white shadow-sm transition-colors hover:bg-rose-700"
                                                aria-label="Hapus dokumen">
                                                <i class="bi bi-x text-xs"></i>
                                            </button>
                                        </div>
                                    </template>
                                    <p x-show="docsFor('{{ $doc->id }}').length === 0"
                                        class="text-[11px] font-medium text-slate-400">Belum ada file.</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>

    <!-- Modal Ganti Foto -->
    <x-ui.modal id="editFotoModal" title="Ganti Foto Profil" icon="bi-image" tone="blue" size="max-w-md">
        <form @submit.prevent="uploadFoto()" class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <img :src="fotoPreview || fotoSrc" alt="Pratinjau foto"
                    class="mx-auto h-36 w-36 rounded-full border-4 border-white object-cover shadow-md ring-1 ring-slate-200">
                <input type="file" x-ref="fotoInput" accept="image/jpeg,image/png,image/gif" @change="onFotoChange($event)"
                    class="block w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                <p class="text-[11px] text-slate-400">Format JPG, PNG, atau GIF. Maksimal 2 MB.</p>
            </div>
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#editFotoModal')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="saving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                    <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!saving" class="bi bi-save"></i> Simpan Foto
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal Edit Login -->
    <x-ui.modal id="editLoginModal" title="Edit Akun Login" icon="bi-key" tone="amber" size="max-w-md">
        <form @submit.prevent="updateLogin($event)" class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <div>
                    <label for="login-username" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Username</label>
                    <input type="text" id="login-username" name="username" value="{{ $user->username }}" required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label for="login-old" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Password Lama</label>
                    <input type="password" id="login-old" name="old" autocomplete="current-password"
                        placeholder="Wajib diisi bila mengganti password"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label for="login-new" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Password Baru</label>
                    <input type="password" id="login-new" name="new" autocomplete="new-password"
                        placeholder="Kosongkan jika tidak ingin ganti"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label for="login-new-confirm" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Konfirmasi Password Baru</label>
                    <input type="password" id="login-new-confirm" name="new_confirm" autocomplete="new-password"
                        placeholder="Ulangi password baru"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>
            </div>
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#editLoginModal')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="saving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-amber-500/20 transition-colors hover:bg-amber-600 disabled:opacity-60">
                    <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!saving" class="bi bi-save"></i> Update Login
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal Tanda Tangan -->
    <x-ui.modal id="modal-signature" title="Buat Tanda Tangan" icon="bi-pencil-square" tone="emerald" size="max-w-lg">
        <div class="p-5">
            <canvas x-ref="sigCanvas" id="sig-canvas" class="sig-canvas"
                @pointerdown.prevent="startDraw($event)"
                @pointermove.prevent="moveDraw($event)"
                @pointerup="endDraw()"
                @pointerleave="endDraw()"></canvas>
            <p class="mt-2 text-center text-[11px] text-slate-400">Gambar tanda tangan di area di atas.</p>
        </div>
        <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
            <button type="button" @click="clearSignature()"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Bersihkan</button>
            <button type="button" @click="saveSignature()" :disabled="saving"
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-emerald-500/20 transition-colors hover:bg-emerald-700 disabled:opacity-60">
                <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                <i x-show="!saving" class="bi bi-save"></i> Simpan TTD
            </button>
        </div>
    </x-ui.modal>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('guruProfil', () => ({
        csrf: '',
        base: '',
        urls: {},

        tab: 'profile',
        saving: false,

        fotoSrc: '',
        defaultFoto: '',
        fotoPreview: '',
        fotoFile: null,
        fotoObjectUrl: '',

        ttdSrc: '',
        docs: {},

        sigCtx: null,
        drawing: false,
        lastX: 0,
        lastY: 0,

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');
            this.csrf = cfg.csrf || '';
            this.base = cfg.base || '';
            this.urls = cfg.urls || {};
            this.defaultFoto = cfg.defaultFoto || '';
            this.docs = cfg.dokumen || {};

            this.fotoSrc = cfg.foto || this.defaultFoto;
            this.ttdSrc = cfg.ttd || '';

            this.initSignature();
        },

        initSignature() {
            const canvas = this.$refs.sigCanvas;
            if (!canvas) return;

            canvas.width = 600;
            canvas.height = 240;

            const ctx = canvas.getContext('2d');
            ctx.lineWidth = 3;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#0f172a';

            this.sigCtx = ctx;
        },

        point(event) {
            const canvas = this.$refs.sigCanvas;
            const rect = canvas.getBoundingClientRect();
            return {
                x: (event.clientX - rect.left) * (canvas.width / rect.width),
                y: (event.clientY - rect.top) * (canvas.height / rect.height),
            };
        },

        startDraw(event) {
            if (!this.sigCtx) return;
            const p = this.point(event);
            this.drawing = true;
            this.lastX = p.x;
            this.lastY = p.y;
            this.sigCtx.beginPath();
            this.sigCtx.arc(p.x, p.y, 1.2, 0, Math.PI * 2);
            this.sigCtx.fillStyle = '#0f172a';
            this.sigCtx.fill();
        },

        moveDraw(event) {
            if (!this.drawing || !this.sigCtx) return;
            const p = this.point(event);
            this.sigCtx.beginPath();
            this.sigCtx.moveTo(this.lastX, this.lastY);
            this.sigCtx.lineTo(p.x, p.y);
            this.sigCtx.stroke();
            this.lastX = p.x;
            this.lastY = p.y;
        },

        endDraw() {
            this.drawing = false;
        },

        clearSignature() {
            const canvas = this.$refs.sigCanvas;
            if (!canvas || !this.sigCtx) return;
            this.sigCtx.clearRect(0, 0, canvas.width, canvas.height);
        },

        openSignature() {
            this.$store.modals.open('#modal-signature');
        },

        docsFor(id) {
            const key = id === 'sertifikat' ? 'serti' : id;
            const value = this.docs ? this.docs[key] : null;
            if (!value) return [];

            const list = Array.isArray(value) ? value : [value];

            return list.map((src) => ({
                src: String(src),
                url: this.base + '/' + String(src).replace(/^\/+/, ''),
            }));
        },

        async postJson(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: payload,
            });

            return response.json();
        },

        async postForm(url, formData) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            return response.json();
        },

        onFotoChange(event) {
            const file = event.target.files?.[0] || null;
            this.fotoFile = file;

            if (this.fotoObjectUrl) {
                URL.revokeObjectURL(this.fotoObjectUrl);
                this.fotoObjectUrl = '';
            }

            if (!file) {
                this.fotoPreview = '';
                return;
            }

            this.fotoObjectUrl = URL.createObjectURL(file);
            this.fotoPreview = this.fotoObjectUrl;
        },

        async uploadFoto() {
            if (this.saving) return;

            if (!this.fotoFile) {
                Swal.fire('Gagal', 'Pilih foto terlebih dahulu.', 'warning');
                return;
            }

            this.saving = true;
            try {
                const formData = new FormData();
                formData.append('foto', this.fotoFile);

                const result = await this.postForm(this.urls.uploadFoto, formData);

                if (result && result.status) {
                    this.fotoSrc = result.src || this.fotoSrc;
                    this.fotoPreview = '';
                    this.fotoFile = null;
                    if (this.fotoObjectUrl) {
                        URL.revokeObjectURL(this.fotoObjectUrl);
                        this.fotoObjectUrl = '';
                    }
                    if (this.$refs.fotoInput) {
                        this.$refs.fotoInput.value = '';
                    }
                    this.$store.modals.close('#editFotoModal');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: 'Foto berhasil diperbarui',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: result?.message || 'Foto gagal diunggah — periksa format dan ukuran file.',
                });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            } finally {
                this.saving = false;
            }
        },

        async deleteFoto() {
            if (this.saving) return;

            const konfirmasi = await Swal.fire({
                title: 'Hapus Foto?',
                text: 'Foto profil akan dihapus dan kembali ke default',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.saving = true;
            try {
                const body = new URLSearchParams();
                body.append('_token', this.csrf);

                const result = await this.postJson(this.urls.deleteFoto, body);

                if (result && result.status) {
                    this.fotoSrc = this.defaultFoto;
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: 'Foto profil berhasil dihapus.',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                    return;
                }

                Swal.fire('Gagal', 'Gagal menghapus foto.', 'error');
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan sistem.' });
            } finally {
                this.saving = false;
            }
        },

        async submitProfil(event) {
            if (this.saving) return;

            this.saving = true;
            try {
                const formData = new FormData(event.target);

                // File kosong ikut terkirim oleh FormData; buang agar server tidak
                // menganggapnya unggahan yang gagal.
                [...formData.entries()].forEach(([key, value]) => {
                    if (value instanceof File && value.name === '') {
                        formData.delete(key);
                    }
                });

                const result = await this.postForm(this.urls.updateProfil, formData);

                if (result && result.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: result.message || 'Data profile berhasil disimpan',
                        timer: 1400,
                        showConfirmButton: false,
                    });

                    setTimeout(() => window.location.reload(), 1400);
                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: result?.message || 'Periksa kembali inputan Anda',
                });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            } finally {
                this.saving = false;
            }
        },

        async updateLogin(event) {
            if (this.saving) return;

            const form = event.target;
            const body = new URLSearchParams();
            body.append('_token', this.csrf);
            ['username', 'old', 'new', 'new_confirm'].forEach((name) => {
                body.append(name, form.elements[name]?.value ?? '');
            });

            this.saving = true;
            try {
                const result = await this.postJson(this.urls.updateLogin, body);

                if (result && result.status) {
                    form.reset();
                    this.$store.modals.close('#editLoginModal');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: result.text || 'Login info berhasil diupdate',
                        timer: 1600,
                        showConfirmButton: false,
                    });
                    return;
                }

                const pesanValidasi = result?.errors
                    ? Object.values(result.errors).flat().join('<br>')
                    : null;

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    html: pesanValidasi || result?.message || 'Login gagal diperbarui.',
                });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            } finally {
                this.saving = false;
            }
        },

        async saveSignature() {
            if (this.saving) return;

            const canvas = this.$refs.sigCanvas;
            if (!canvas) return;

            this.saving = true;
            try {
                const body = new URLSearchParams();
                body.append('_token', this.csrf);
                body.append('image', canvas.toDataURL('image/png'));

                const result = await this.postJson(this.urls.saveSignature, body);

                if (result && result.status) {
                    this.ttdSrc = result.src || this.ttdSrc;
                    this.clearSignature();
                    this.$store.modals.close('#modal-signature');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: result.message || 'Tanda tangan berhasil disimpan',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                    return;
                }

                Swal.fire('Gagal', result?.message || 'Tanda tangan gagal disimpan.', 'error');
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            } finally {
                this.saving = false;
            }
        },

        async deleteTtd() {
            if (this.saving) return;

            const konfirmasi = await Swal.fire({
                title: 'Hapus Tanda Tangan?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.saving = true;
            try {
                const body = new URLSearchParams();
                body.append('_token', this.csrf);

                const result = await this.postJson(this.urls.deleteTtd, body);

                if (result && result.status) {
                    this.ttdSrc = '';
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: 'Tanda tangan berhasil dihapus.',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                    return;
                }

                Swal.fire('Gagal', 'Tanda tangan gagal dihapus.', 'error');
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            } finally {
                this.saving = false;
            }
        },

        async deleteDoc(src) {
            const konfirmasi = await Swal.fire({
                title: 'Hapus Dokumen?',
                text: 'Dokumen yang dihapus tidak dapat dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            try {
                const body = new URLSearchParams();
                body.append('_token', this.csrf);
                body.append('src', src);

                const result = await this.postJson(this.urls.deleteDokumen, body);

                if (result && result.status) {
                    this.docs = result.dokumen || {};
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: 'Dokumen berhasil dihapus.',
                        timer: 1400,
                        showConfirmButton: false,
                    });
                    return;
                }

                Swal.fire('Gagal', 'Dokumen gagal dihapus.', 'error');
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            }
        },
    }));
});
</script>
@endsection
