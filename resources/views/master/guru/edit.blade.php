@extends('layouts.main')

@section('content')

@php
    $fotoRaw = trim((string) ($guru->foto ?? ''));
    $foto = ($fotoRaw !== '' && strpos($fotoRaw, '..') === false && file_exists(public_path() . '/' . ltrim($fotoRaw, '/')))
        ? url(ltrim($fotoRaw, '/'))
        : asset('assets/img/guru.png');

    // Peta field by name agar bisa dirender per seksi tanpa ubah controller
    $mapProf = []; foreach (($input_profile ?? []) as $it) { $mapProf[$it->name] = $it; }
    $mapAlm  = []; foreach (($input_alamat ?? []) as $it) { $mapAlm[$it->name] = $it; }
    $v = fn ($m, $k) => e($m[$k]->value ?? '');

    // Kelengkapan data (progress). no_hp sengaja TIDAK dihitung lagi: kolom
    // itu milik Dapodik dan tidak lagi bisa diisi dari form ini.
    $checkVals = [
        $mapProf['nama_guru']->value ?? '', $mapProf['nip']->value ?? '', $mapProf['email']->value ?? '',
        $mapAlm['tempat_lahir']->value ?? '', $mapAlm['tgl_lahir']->value ?? '',
        $mapAlm['alamat_jalan']->value ?? '', $mapAlm['no_ktp']->value ?? '',
    ];
    $filled = 0;
    foreach ($checkVals as $cv) { if (trim((string) $cv) !== '') { $filled++; } }
    $pct = (int) round($filled / count($checkVals) * 100);

    // Tgl lahir → normalisasi Y-m-d untuk input date
    $tglLahir = trim((string) ($mapAlm['tgl_lahir']->value ?? ''));
    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglLahir) || $tglLahir === '0000-00-00') { $tglLahir = ''; }

    // TMT pengangkatan → normalisasi Y-m-d, caranya sama dengan tgl lahir
    $tmtPegawai = trim((string) ($mapProf['tmt']->value ?? ''));
    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tmtPegawai) || $tmtPegawai === '0000-00-00') { $tmtPegawai = ''; }

    $jabatan = trim(($guru->level ?? '') . ' ' . ($guru->nama_kelas ?? ''));
    if ($jabatan === '') { $jabatan = 'Guru / Tenaga Kependidikan'; }

    $currentStatusPegawai = (string) ($mapProf['status_pegawai']->value ?? '');
    $opsiStatusPegawai = ['PNS', 'CPNS', 'PPPK', 'Guru Honor Sekolah', 'Tenaga Honor Sekolah'];
    // Nilai legacy di luar daftar tetap ditampilkan supaya menyimpan tidak
    // diam-diam mengosongkannya.
    if ($currentStatusPegawai !== '' && ! in_array($currentStatusPegawai, $opsiStatusPegawai, true)) {
        array_unshift($opsiStatusPegawai, $currentStatusPegawai);
    }
@endphp

<div class="fade-in-up space-y-4 px-4 pt-6 md:px-6"
    x-data="guruEdit"
    data-token="{{ csrf_token() }}"
    data-id-guru="{{ (int) $id_active }}"
    data-foto="{{ $foto }}"
    data-foto-default="{{ asset('assets/img/guru.png') }}"
    data-ttd="{{ ! empty($guru->ttd) ? url(ltrim($guru->ttd, '/')) : '' }}"
    data-url-save="{{ url('dataguru/save') }}"
    data-url-upload-foto="{{ url('dataguru/uploadFile/' . (int) $id_active) }}"
    data-url-delete-foto="{{ url('dataguru/delete_foto/' . (int) $id_active) }}"
    data-url-login="{{ url('dataguru/editLogin') }}"
    data-url-save-ttd="{{ url('dataguru/save_signature') }}"
    data-url-delete-ttd="{{ url('dataguru/delete_ttd') }}"
    data-url-list="{{ url('dataguru') }}"
    data-url-detail="{{ url('dataguru/detail/' . (int) $id_active) }}">

    <x-ui.page-header
        title="{{ $judul ?? 'Edit Guru' }} — {{ $guru->nama_guru ?? '' }}"
        icon="bi-pencil-square"
        :subtitle="($subjudul ?? 'Edit Data') . ' · ' . ($guru->username ?? '-') . ' · TP ' . ($tp_active->tahun ?? '-') . ' ' . ($smt_active->smt ?? '')"
        :crumbs="['Data Guru', $guru->nama_guru ?? 'Edit']">
        <x-slot:actions>
            <a href="{{ url('dataguru') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <a href="{{ url('dataguru/detail/' . $id_active) }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-eye"></i> Lihat Detail
            </a>
            <button type="button" @click="submit()" :disabled="saving"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                <i class="bi bi-save"></i> Simpan
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <div x-show="alert" x-cloak class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700" role="alert" x-text="alert"></div>

    <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-5">
        <!-- ═══ Profil ═══ -->
        <aside class="min-w-0 space-y-4 lg:col-span-2 lg:sticky lg:top-4">
            <x-ui.card title="Profil Guru" icon="bi-person-badge">
                <div class="mb-5 text-center">
                    <div class="relative inline-block">
                        <img :src="fotoSrc" alt="Foto {{ $guru->nama_guru ?? 'guru' }}"
                            class="h-[132px] w-[132px] rounded-full object-cover shadow-sm ring-4 ring-slate-100">
                        <span class="absolute bottom-2 right-2 h-4 w-4 rounded-full bg-emerald-500 ring-2 ring-white" title="Aktif"></span>
                        <button type="button" @click="openFotoModal()"
                            class="absolute bottom-0 right-0 inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-white shadow-md transition-colors hover:bg-blue-700"
                            title="Ganti Foto" aria-label="Ganti foto">
                            <i class="bi bi-camera text-xs"></i>
                        </button>
                    </div>
                    <h4 x-text="namaPreview" class="mt-3 font-bold text-slate-900">{{ $guru->nama_guru ?? '-' }}</h4>
                    <p class="mt-0.5 text-xs text-slate-400">{{ $jabatan }}</p>
                    <p class="mt-1.5">
                        <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-500">
                            <i class="bi bi-person-circle"></i>{{ $guru->username ?? '-' }}
                        </span>
                    </p>
                </div>

                <div class="mb-4 rounded-2xl border border-slate-100 bg-slate-50 p-3">
                    <div class="mb-1.5 flex items-center justify-between text-[11px] font-bold">
                        <span class="text-slate-600">Kelengkapan data</span>
                        <span class="text-blue-700">{{ $pct }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-emerald-500" style="width: {{ $pct }}%"></div>
                    </div>
                </div>

                <dl class="mb-4 space-y-2.5 text-xs">
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-slate-400">NIP / NUPTK</dt>
                        <dd class="text-right font-mono font-bold text-slate-700">{{ $guru->nip ?? '-' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-slate-400">No. HP <span class="text-[10px] font-normal">(Dapodik)</span></dt>
                        <dd class="text-right font-mono font-bold text-slate-700">{{ $guru->no_hp ?? '-' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-slate-400">Tahun aktif</dt>
                        <dd class="font-bold text-slate-700">TP {{ $tp_active->tahun ?? '-' }} · {{ $smt_active->smt ?? '' }}</dd>
                    </div>
                </dl>

                <div class="grid gap-2">
                    <button type="button" @click="$store.modals.open('#editLoginModal')"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-white shadow-xs transition-colors hover:bg-amber-600">
                        <i class="bi bi-key"></i> Akun Login
                    </button>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="openFotoModal()"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-50">
                            <i class="bi bi-camera"></i> Foto
                        </button>
                        <button type="button" @click="deleteFoto()" :disabled="deletingFoto"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50 disabled:opacity-50">
                            <i class="bi bi-trash"></i> Hapus
                        </button>
                    </div>
                </div>
                <p class="mt-3 text-[11px] leading-relaxed text-slate-400">
                    <i class="bi bi-info-circle mr-1"></i>Perubahan tersimpan via tombol <b>Simpan</b>.
                    <kbd class="rounded bg-slate-100 px-1 py-0.5 font-mono text-[10px]">Ctrl+S</kbd> untuk simpan cepat.
                </p>
            </x-ui.card>

            <x-ui.card title="Tanda Tangan" icon="bi-pencil-square" tone="emerald">
                <div x-show="!ttdSrc" x-cloak class="flex h-[100px] items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-slate-400">
                    <span class="text-xs"><i class="bi bi-pen mr-1"></i>Belum ada TTD</span>
                </div>
                <img x-show="ttdSrc" x-cloak :src="ttdSrc" alt="TTD" class="mx-auto block h-auto max-h-[100px] max-w-full">
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <button type="button" @click="openSignature()"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white transition-colors hover:bg-emerald-700"
                        title="Buat TTD Baru">
                        <i class="bi bi-pencil-square"></i> <span x-text="ttdSrc ? 'Buat Ulang' : 'Buat TTD'"></span>
                    </button>
                    <button type="button" x-show="ttdSrc" x-cloak @click="deleteTtd()" :disabled="deletingTtd"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-600 transition-colors hover:bg-rose-50 disabled:opacity-50"
                        title="Hapus TTD">
                        <i class="bi bi-trash"></i> Hapus
                    </button>
                </div>
            </x-ui.card>
        </aside>

        <!-- ═══ Form ═══ -->
        <div class="min-w-0 lg:col-span-3">
            <form x-ref="form" @submit.prevent="submit()" @input="markDirty()" @change="markDirty()"
                enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" name="id_guru" value="{{ (int) $id_active }}">

                <div class="w-full rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                    <!-- Tab bar -->
                    <div class="z-20 rounded-t-2xl border-b border-slate-100 bg-white/95 p-3 backdrop-blur lg:sticky lg:top-0">
                        <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Bagian data guru">
                            <button type="button" @click="tab = 'profile'" :aria-selected="tab === 'profile' ? 'true' : 'false'"
                                :class="tab === 'profile' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                class="relative flex min-w-0 items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-[11px] font-bold transition-all"
                                role="tab" aria-controls="profile-pane">
                                <i class="bi bi-person"></i> Profil
                                <span x-show="tabErrors.profile > 0" x-cloak
                                    class="absolute right-1.5 top-1 inline-flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-extrabold text-white"
                                    x-text="tabErrors.profile"></span>
                            </button>
                            <button type="button" @click="tab = 'address'" :aria-selected="tab === 'address' ? 'true' : 'false'"
                                :class="tab === 'address' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                class="relative flex min-w-0 items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-[11px] font-bold transition-all"
                                role="tab" aria-controls="address-pane">
                                <i class="bi bi-geo-alt"></i> Alamat &amp; Data
                                <span x-show="tabErrors.address > 0" x-cloak
                                    class="absolute right-1.5 top-1 inline-flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-extrabold text-white"
                                    x-text="tabErrors.address"></span>
                            </button>
                        </div>
                    </div>

                    <div class="p-5">
                        <!-- TAB PROFIL -->
                        <div x-show="tab === 'profile'" id="profile-pane" role="tabpanel">
                            <div class="mb-3 flex items-center gap-2.5">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[9px] bg-blue-50 text-[13px] text-blue-600"><i class="bi bi-person"></i></span>
                                <div>
                                    <h3 class="text-[13px] font-extrabold text-slate-900">Data Pokok</h3>
                                    <p class="text-[11px] text-slate-400">Identitas utama — nama wajib diisi</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label for="f-nama_guru" class="mb-1.5 block text-xs font-bold text-slate-700">Nama Lengkap <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <i class="bi bi-person pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="text" id="f-nama_guru" name="nama_guru" x-ref="nama_guru" @input="namaPreview = $event.target.value"
                                            value="{{ $v($mapProf, 'nama_guru') }}" required maxlength="100" autocomplete="off"
                                            placeholder="Nama lengkap beserta gelar"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                            :class="errors.nama_guru ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                    </div>
                                    <p class="mt-1 text-[11px] text-rose-600" x-show="errors.nama_guru" x-cloak x-text="errors.nama_guru"></p>
                                </div>

                                <div>
                                    <label for="f-nip" class="mb-1.5 block text-xs font-bold text-slate-700">NIP / NUPTK</label>
                                    <div class="relative">
                                        <i class="bi bi-person-badge pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="text" id="f-nip" name="nip" inputmode="numeric" value="{{ $v($mapProf, 'nip') }}"
                                            maxlength="30" autocomplete="off" placeholder="18 digit NIP / NUPTK"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                            :class="errors.nip ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                    </div>
                                    <p class="mt-1 text-[11px] text-rose-600" x-show="errors.nip" x-cloak x-text="errors.nip"></p>
                                </div>

                                <div>
                                    <label for="f-jk" class="mb-1.5 block text-xs font-bold text-slate-700">Jenis Kelamin</label>
                                    <div class="relative">
                                        <i class="bi bi-gender-ambiguous pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <select id="f-jk" name="jenis_kelamin"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                            <option value="" {{ empty($mapProf['jenis_kelamin']->value ?? '') ? 'selected' : '' }}>— Pilih —</option>
                                            <option value="L" {{ ($mapProf['jenis_kelamin']->value ?? '') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                            <option value="P" {{ ($mapProf['jenis_kelamin']->value ?? '') == 'P' ? 'selected' : '' }}>Perempuan</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label for="f-status_pegawai" class="mb-1.5 block text-xs font-bold text-slate-700">Status Pegawai</label>
                                    <div class="relative">
                                        <i class="bi bi-person-vcard pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <select id="f-status_pegawai" name="status_pegawai"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                            <option value="" {{ $currentStatusPegawai === '' ? 'selected' : '' }}>— Tidak Terdata —</option>
                                            @foreach ($opsiStatusPegawai as $optSp)
                                                <option value="{{ $optSp }}" {{ $currentStatusPegawai === $optSp ? 'selected' : '' }}>{{ $optSp }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <p class="mt-1 text-[11px] text-slate-400">Menentukan hak cuti (PNS vs PPPK) di aplikasi absensi.</p>
                                </div>

                                <div>
                                    <label for="f-tmt" class="mb-1.5 block text-xs font-bold text-slate-700">TMT Pengangkatan</label>
                                    <div class="relative">
                                        <i class="bi bi-calendar-check pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="date" id="f-tmt" name="tmt" value="{{ $tmtPegawai }}"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                    </div>
                                    <p class="mt-1 text-[11px] text-slate-400">Basis hitungan masa kerja (syarat cuti 1/5 tahun).</p>
                                </div>
                            </div>

                            <div class="mb-3 mt-5 flex items-center gap-2.5">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[9px] bg-emerald-50 text-[13px] text-emerald-600"><i class="bi bi-telephone"></i></span>
                                <div>
                                    <h3 class="text-[13px] font-extrabold text-slate-900">Kontak</h3>
                                    <p class="text-[11px] text-slate-400">Email, HP &amp; agama</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="f-email" class="mb-1.5 block text-xs font-bold text-slate-700">Email</label>
                                    <div class="relative">
                                        <i class="bi bi-envelope pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="email" id="f-email" name="email" value="{{ $v($mapProf, 'email') }}"
                                            maxlength="100" autocomplete="off" placeholder="nama@sekolah.id"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                            :class="errors.email ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                    </div>
                                    <p class="mt-1 text-[11px] text-rose-600" x-show="errors.email" x-cloak x-text="errors.email"></p>
                                </div>

                                <div>
                                    <label for="f-agama" class="mb-1.5 block text-xs font-bold text-slate-700">Agama</label>
                                    <div class="relative">
                                        <i class="bi bi-star pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <select id="f-agama" name="agama"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                            <option value="">— Pilih Agama —</option>
                                            @foreach (['Islam', 'Katolik', 'Protestan', 'Hindu', 'Budha', 'Konghucu'] as $agama)
                                                <option value="{{ $agama }}" {{ ($mapProf['agama']->value ?? '') == $agama ? 'selected' : '' }}>{{ $agama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 mt-5 flex items-center gap-2.5">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[9px] bg-violet-50 text-[13px] text-violet-600"><i class="bi bi-key"></i></span>
                                <div>
                                    <h3 class="text-[13px] font-extrabold text-slate-900">Integrasi</h3>
                                    <p class="text-[11px] text-slate-400">Kunci API &amp; kanal notifikasi (opsional)</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label for="f-api_key" class="mb-1.5 block text-xs font-bold text-slate-700">Google API Key</label>
                                    <div class="relative">
                                        <i class="bi bi-key pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input :type="showApiKey ? 'text' : 'password'" id="f-api_key" name="api_key"
                                            value="{{ $v($mapProf, 'api_key') }}" maxlength="255" autocomplete="off" placeholder="AIza..."
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-11 font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                        <button type="button" @click="showApiKey = ! showApiKey"
                                            class="absolute inset-y-0 right-0 px-3.5 text-slate-400 hover:text-slate-600"
                                            title="Tampilkan/sembunyikan" aria-label="Tampilkan API key">
                                            <i class="bi" :class="showApiKey ? 'bi-eye-slash' : 'bi-eye'"></i>
                                        </button>
                                    </div>
                                    <p class="mt-1 text-[11px] text-slate-400">Dipakai untuk fitur peta presensi. Biarkan apa adanya jika tidak tahu.</p>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label for="f-nomor_rfid" class="mb-1.5 block text-xs font-bold text-slate-700">Nomor Kartu RFID</label>
                                        <div class="relative">
                                            <i class="bi bi-card-id pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                            <input type="text" id="f-nomor_rfid" name="nomor_rfid" x-ref="nomor_rfid"
                                                value="{{ $v($mapProf, 'nomor_rfid') }}" maxlength="64" autocomplete="off" placeholder="04A1B2C3D"
                                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-24 font-mono text-sm uppercase text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                            <button type="button" @click="scanNfc()"
                                                class="absolute right-1.5 top-1/2 inline-flex -translate-y-1/2 items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-[11px] font-bold text-indigo-700 transition-all hover:bg-indigo-100"
                                                title="Tempelkan kartu ke HP untuk isi otomatis">
                                                <i class="bi bi-broadcast"></i> <span>Tap NFC</span>
                                            </button>
                                        </div>
                                        <p class="mt-1 text-[11px] leading-relaxed text-slate-400">Nomor kartu RFID guru/GTK — dipakai untuk QR, tap kartu, dan USB scanner. Kosongkan bila tidak pakai RFID.</p>
                                    </div>

                                    <div>
                                        <label for="f-fingerprint_uid" class="mb-1.5 block text-xs font-bold text-slate-700">UserID Mesin Sidik Jari</label>
                                        <div class="relative">
                                            <i class="bi bi-fingerprint pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                            <input type="text" id="f-fingerprint_uid" name="fingerprint_uid" value="{{ $v($mapProf, 'fingerprint_uid') }}"
                                                maxlength="64" autocomplete="off" placeholder="1001"
                                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                        </div>
                                        <p class="mt-1 text-[11px] leading-relaxed text-slate-400">UserID di mesin sidik jari GEISA GX330. Kosongkan bila tidak dipakai.</p>
                                    </div>

                                    <div>
                                        <label for="f-telegram_id" class="mb-1.5 block text-xs font-bold text-slate-700">ID Telegram</label>
                                        <div class="relative">
                                            <i class="bi bi-telegram pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                            <input type="text" id="f-telegram_id" name="telegram_id" inputmode="numeric" value="{{ $v($mapProf, 'telegram_id') }}"
                                                maxlength="50" autocomplete="off" placeholder="123456789"
                                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                                :class="errors.telegram_id ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                        </div>
                                        <p class="mt-1 text-[11px] text-rose-600" x-show="errors.telegram_id" x-cloak x-text="errors.telegram_id"></p>
                                        <p class="mt-1 text-[11px] leading-relaxed text-slate-400">Buka bot <b>@userinfobot</b> di Telegram, tekan Start, salin angkanya.</p>
                                    </div>

                                    <div>
                                        <label for="f-wa_number" class="mb-1.5 block text-xs font-bold text-slate-700">No. WhatsApp</label>
                                        <div class="relative">
                                            <i class="bi bi-whatsapp pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                            <input type="tel" id="f-wa_number" name="wa_number" inputmode="tel" value="{{ $v($mapProf, 'wa_number') }}"
                                                maxlength="20" autocomplete="off" placeholder="6281234567890"
                                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                                :class="errors.wa_number ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                        </div>
                                        <p class="mt-1 text-[11px] text-rose-600" x-show="errors.wa_number" x-cloak x-text="errors.wa_number"></p>
                                        @if (! empty($waVerified))
                                            <p class="mt-1 text-[11px] text-emerald-600"><i class="bi bi-check-circle"></i> Nomor ini aktif — notifikasi personal dikirim ke sini.</p>
                                        @elseif (! empty($waNumber))
                                            <p class="mt-1 text-[11px] text-amber-600"><i class="bi bi-hourglass-split"></i> Tersimpan, menunggu konfirmasi di <b>Kelola Tautan Guru</b>.</p>
                                        @else
                                            <p class="mt-1 text-[11px] leading-relaxed text-slate-400">Tujuan notifikasi denda, teguran, dan peringatan alpha. Boleh 08 / +62 / 62.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB ALAMAT -->
                        <div x-show="tab === 'address'" x-cloak id="address-pane" role="tabpanel">
                            <div class="mb-3 flex items-center gap-2.5">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[9px] bg-amber-50 text-[13px] text-amber-600"><i class="bi bi-person-badge"></i></span>
                                <div>
                                    <h3 class="text-[13px] font-extrabold text-slate-900">Identitas Kependudukan</h3>
                                    <p class="text-[11px] text-slate-400">NIK &amp; kelahiran</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="f-no_ktp" class="mb-1.5 block text-xs font-bold text-slate-700">NIK (No. KTP)</label>
                                    <div class="relative">
                                        <i class="bi bi-person-badge pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="text" id="f-no_ktp" name="no_ktp" inputmode="numeric" value="{{ $v($mapAlm, 'no_ktp') }}"
                                            maxlength="20" autocomplete="off" placeholder="16 digit NIK"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                            :class="errors.no_ktp ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                    </div>
                                    <p class="mt-1 text-[11px] text-rose-600" x-show="errors.no_ktp" x-cloak x-text="errors.no_ktp"></p>
                                </div>

                                <div>
                                    <label for="f-tempat_lahir" class="mb-1.5 block text-xs font-bold text-slate-700">Tempat Lahir</label>
                                    <div class="relative">
                                        <i class="bi bi-geo-alt pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="text" id="f-tempat_lahir" name="tempat_lahir" value="{{ $v($mapAlm, 'tempat_lahir') }}"
                                            maxlength="50" autocomplete="off" placeholder="Kota kelahiran"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                    </div>
                                </div>

                                <div>
                                    <label for="f-tgl_lahir" class="mb-1.5 block text-xs font-bold text-slate-700">Tanggal Lahir</label>
                                    <div class="relative">
                                        <i class="bi bi-calendar-date pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="date" id="f-tgl_lahir" name="tgl_lahir" value="{{ $tglLahir }}" max="{{ date('Y-m-d') }}"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                            :class="errors.tgl_lahir ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                    </div>
                                    <p class="mt-1 text-[11px] text-rose-600" x-show="errors.tgl_lahir" x-cloak x-text="errors.tgl_lahir"></p>
                                </div>
                            </div>

                            <div class="mb-3 mt-5 flex items-center gap-2.5">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[9px] bg-blue-50 text-[13px] text-blue-600"><i class="bi bi-house-door"></i></span>
                                <div>
                                    <h3 class="text-[13px] font-extrabold text-slate-900">Alamat Domisili</h3>
                                    <p class="text-[11px] text-slate-400">Alamat lengkap tempat tinggal</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label for="f-alamat_jalan" class="mb-1.5 block text-xs font-bold text-slate-700">Alamat (Jalan / Dusun)</label>
                                    <textarea id="f-alamat_jalan" name="alamat_jalan" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, dusun..."
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">{{ $v($mapAlm, 'alamat_jalan') }}</textarea>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="f-kecamatan" class="mb-1.5 block text-xs font-bold text-slate-700">Kecamatan</label>
                                    <input type="text" id="f-kecamatan" name="kecamatan" value="{{ $v($mapAlm, 'kecamatan') }}" maxlength="50" placeholder="Kecamatan"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div>
                                    <label for="f-kabupaten" class="mb-1.5 block text-xs font-bold text-slate-700">Kota / Kabupaten</label>
                                    <input type="text" id="f-kabupaten" name="kabupaten" value="{{ $v($mapAlm, 'kabupaten') }}" maxlength="50" placeholder="Kota / kabupaten"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div>
                                    <label for="f-provinsi" class="mb-1.5 block text-xs font-bold text-slate-700">Provinsi</label>
                                    <input type="text" id="f-provinsi" name="provinsi" value="{{ $v($mapAlm, 'provinsi') }}" maxlength="50" placeholder="Provinsi"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div>
                                    <label for="f-kode_pos" class="mb-1.5 block text-xs font-bold text-slate-700">Kode Pos</label>
                                    <input type="text" id="f-kode_pos" name="kode_pos" inputmode="numeric" value="{{ $v($mapAlm, 'kode_pos') }}" maxlength="10" placeholder="85xxx"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                        :class="errors.kode_pos ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                    <p class="mt-1 text-[11px] text-rose-600" x-show="errors.kode_pos" x-cloak x-text="errors.kode_pos"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky save bar -->
                    <div class="px-4 pb-4">
                        <div class="sticky bottom-3 z-30 space-y-2.5 rounded-2xl border border-slate-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur">
                            <div class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                                    <span class="h-2 w-2 rounded-full transition-colors" :class="dirty ? 'animate-pulse bg-amber-500' : 'bg-slate-300'"></span>
                                    <span x-text="dirty ? 'Ada perubahan belum disimpan' : 'Belum ada perubahan'"></span>
                                </span>
                                <span class="text-[11px] text-slate-400"><span class="font-bold text-red-500">*</span> Nama wajib diisi</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                <button type="button" @click="resetForm()"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-50">
                                    <i class="bi bi-arrow-repeat"></i> Reset
                                </button>
                                <a href="{{ url('dataguru') }}"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-600 no-underline transition-colors hover:bg-rose-50">
                                    <i class="bi bi-x-lg"></i> Batal
                                </a>
                                <button type="submit" :disabled="saving"
                                    class="col-span-2 inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-xs transition-colors hover:bg-blue-700 disabled:opacity-60 sm:col-span-1">
                                    <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                    <i class="bi bi-save" x-show="!saving"></i>
                                    <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══ Modal Foto ═══ -->
    <x-ui.modal id="editFotoModal" title="Update Foto Guru" icon="bi-camera">
        <div class="min-h-0 flex-1 overflow-y-auto p-5">
            <div x-show="!cropVisible">
                <div role="button" tabindex="0" @click="pickFoto()" @keydown.enter.prevent="pickFoto()" @keydown.space.prevent="pickFoto()"
                    @dragover.prevent="dzOver = true" @dragenter.prevent="dzOver = true"
                    @dragleave.prevent="dzOver = false" @drop.prevent="onFotoDrop($event)"
                    class="cursor-pointer rounded-xl border-2 border-dashed p-5 text-center transition-colors"
                    :class="dzOver ? 'border-blue-500 bg-blue-50' : 'border-slate-300 hover:border-blue-500 hover:bg-blue-50'">
                    <img :src="fotoSrc" alt="Preview foto"
                        class="mx-auto mb-3 block h-[180px] w-[180px] rounded-2xl border object-cover shadow-sm">
                    <p class="mb-0.5 text-xs font-bold text-slate-600">Seret &amp; letakkan foto di sini, atau <span class="text-blue-600 underline">klik untuk memilih</span></p>
                    <p x-show="fotoName" x-cloak class="mt-1.5 break-all font-mono text-[11px] font-bold text-blue-700" x-text="fotoName"></p>
                </div>
                <input type="file" x-ref="foto" @change="onFotoPicked($event.target.files[0])" class="hidden" accept="image/jpeg,image/png">
            </div>

            <div x-show="cropVisible" x-cloak class="mt-1">
                <div class="mb-2 flex items-center justify-center gap-2">
                    <span class="text-[11px] text-slate-500">Ukuran</span>
                    <div class="inline-flex gap-0.5 rounded-full bg-slate-100 p-1" role="group" aria-label="Ukuran foto">
                        <button type="button" @click="setRatio('4x6')" :aria-pressed="ratio === '4x6' ? 'true' : 'false'"
                            :class="ratio === '4x6' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'"
                            class="rounded-full px-3 py-1 text-xs font-bold transition-all">4 × 6</button>
                        <button type="button" @click="setRatio('3x4')" :aria-pressed="ratio === '3x4' ? 'true' : 'false'"
                            :class="ratio === '3x4' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500'"
                            class="rounded-full px-3 py-1 text-xs font-bold transition-all">3 × 4</button>
                    </div>
                </div>

                <div x-ref="viewport" @pointerdown="startDrag($event)" @pointermove="drag($event)"
                    @pointerup="endDrag()" @pointercancel="endDrag()" @wheel.prevent="wheelZoom($event)"
                    class="relative mx-auto max-w-[210px] cursor-grab touch-none overflow-hidden rounded-2xl bg-slate-900"
                    :style="'width:100%; aspect-ratio: ' + (ratio === '4x6' ? '2 / 3' : '3 / 4') + ';'">
                    <img x-ref="cropImg" alt="Pratinjau crop" draggable="false" @load="onCropLoad()" :src="cropSrc"
                        class="pointer-events-none absolute left-1/2 top-1/2 max-w-none select-none"
                        :style="cropImgStyle">
                    <div class="pointer-events-none absolute inset-1 rounded border-2 border-dashed border-white/80"></div>
                </div>

                <div class="mx-auto mt-2 flex max-w-[250px] items-center gap-2">
                    <button type="button" @click="resetCrop()" title="Atur ulang crop" aria-label="Atur ulang crop"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-800">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                    <i class="bi bi-zoom-out text-xs text-slate-400"></i>
                    <input type="range" min="1" max="3" step="0.01" x-model.number="zoom" @input="renderCrop()"
                        class="w-full" aria-label="Zoom foto">
                    <i class="bi bi-zoom-in text-xs text-slate-400"></i>
                </div>
                <p class="mt-1 text-center text-[11px] text-slate-400">Geser foto atau atur zoom.</p>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <button type="button" @click="pickFoto()"
                        class="rounded-xl bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 hover:bg-slate-200">Ganti Foto</button>
                    <button type="button" @click="applyCrop()"
                        class="inline-flex items-center gap-1 rounded-xl bg-blue-600 px-3 py-1 text-xs font-bold text-white hover:bg-blue-700">
                        <i class="bi bi-check2"></i> Terapkan
                    </button>
                </div>
            </div>
        </div>

        <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
            <button type="button" @click="$store.modals.close('#editFotoModal')"
                class="rounded-xl bg-slate-200 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-300">Batal</button>
            <button type="button" @click="uploadFoto()" :disabled="!fotoFile || uploadingFoto"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                <span x-show="uploadingFoto" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                <i class="bi bi-cloud-arrow-up" x-show="!uploadingFoto"></i>
                <span x-text="uploadingFoto ? 'Mengunggah...' : 'Upload Foto'"></span>
            </button>
        </div>
    </x-ui.modal>

    <!-- ═══ Modal Login ═══ -->
    <x-ui.modal id="editLoginModal" title="Edit Akun Login" icon="bi-key" tone="amber">
        <form @submit.prevent="saveLogin()" novalidate>
            <input type="hidden" name="id_guru" value="{{ (int) $id_active }}">
            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-700" for="login-username">Username <span class="text-rose-500">*</span></label>
                    <input type="text" id="login-username" x-model="login.username" required minlength="4" maxlength="30" autocomplete="username"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    <p class="mt-1 text-[11px] text-rose-600" x-show="loginErrors.username" x-cloak x-text="loginErrors.username"></p>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-700" for="login-new">Password Baru
                        <span class="font-normal text-slate-400">(opsional, min. 6)</span></label>
                    <div class="relative">
                        <input :type="showLoginPassword ? 'text' : 'password'" id="login-new" x-model="login.password"
                            minlength="6" maxlength="72" autocomplete="new-password" placeholder="Kosongkan jika tidak ingin ganti"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 pr-11 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <button type="button" @click="showLoginPassword = ! showLoginPassword"
                            class="absolute inset-y-0 right-0 px-3.5 text-slate-400 hover:text-slate-600"
                            title="Tampilkan/sembunyikan" aria-label="Tampilkan password">
                            <i class="bi" :class="showLoginPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                        </button>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full transition-all" :class="strengthClass" :style="'width: ' + strength + '%'"></div>
                    </div>
                    <p class="mt-1 text-[11px] text-rose-600" x-show="loginErrors.password" x-cloak x-text="loginErrors.password"></p>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-700" for="login-new-confirm">Konfirmasi Password Baru</label>
                    <input type="password" id="login-new-confirm" x-model="login.confirm" maxlength="72" autocomplete="new-password"
                        placeholder="Ulangi password baru"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    <p class="mt-1 text-[11px] text-rose-600" x-show="loginErrors.confirm" x-cloak x-text="loginErrors.confirm"></p>
                </div>
            </div>
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
                <button type="button" @click="$store.modals.close('#editLoginModal')"
                    class="rounded-xl bg-slate-200 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-300">Batal</button>
                <button type="submit" :disabled="savingLogin"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-white hover:bg-amber-600 disabled:opacity-60">
                    <span x-show="savingLogin" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <i class="bi bi-key" x-show="!savingLogin"></i>
                    <span x-text="savingLogin ? 'Menyimpan...' : 'Update Login'"></span>
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- ═══ Modal TTD ═══ -->
    <x-ui.modal id="modal-signature" title="Buat Tanda Tangan" icon="bi-pen" tone="emerald" size="max-w-lg">
        <div class="min-h-0 flex-1 overflow-y-auto p-5">
            <p class="mb-2 text-[11px] text-slate-400">Gambar dengan mouse / jari di kanvas</p>
            <canvas x-ref="sigCanvas" @pointerdown="sigStart($event)" @pointermove="sigMove($event)"
                @pointerup="sigEnd()" @pointercancel="sigEnd()" @pointerleave="sigEnd()"
                class="mx-auto block h-[200px] w-full max-w-[400px] cursor-crosshair touch-none rounded-[15px] border-2 border-dotted border-slate-300 bg-white"></canvas>
        </div>
        <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
            <button type="button" @click="clearSignature()"
                class="inline-flex items-center gap-1.5 rounded-xl bg-slate-200 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-300">
                <i class="bi bi-eraser"></i> Bersihkan
            </button>
            <button type="button" @click="saveSignature()" :disabled="savingTtd"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-700 disabled:opacity-60">
                <span x-show="savingTtd" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                <i class="bi bi-check-lg" x-show="!savingTtd"></i>
                <span x-text="savingTtd ? 'Menyimpan...' : 'Simpan TTD'"></span>
            </button>
        </div>
    </x-ui.modal>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('guruEdit', () => ({
        tab: 'profile',
        dirty: false,
        alert: '',
        saving: false,
        errors: {},
        initial: '',
        namaPreview: @js($guru->nama_guru ?? ''),
        fotoSrc: '',
        fotoDefault: '',
        showApiKey: false,
        deletingFoto: false,
        config: {},

        // foto + crop
        fotoModal: false,
        dzOver: false,
        fotoFile: null,
        fotoName: '',
        uploadingFoto: false,
        cropVisible: false,
        cropSrc: '',
        cropUrl: '',
        ratio: '4x6',
        zoom: 1,
        panX: 0,
        panY: 0,
        baseScale: 1,
        natW: 0,
        natH: 0,
        viewW: 0,
        viewH: 0,
        dragging: false,
        dragX: 0,
        dragY: 0,

        // login
        login: { username: @js($guru->username ?? ''), password: '', confirm: '' },
        loginErrors: {},
        showLoginPassword: false,
        savingLogin: false,

        // ttd
        ttdSrc: '',
        savingTtd: false,
        deletingTtd: false,
        sigDrawing: false,
        sigX: 0,
        sigY: 0,

        init() {
            const data = this.$el.dataset;
            this.config = {
                token: data.token,
                idGuru: Number(data.idGuru) || 0,
                urls: {
                    save: data.urlSave,
                    uploadFoto: data.urlUploadFoto,
                    deleteFoto: data.urlDeleteFoto,
                    login: data.urlLogin,
                    saveTtd: data.urlSaveTtd,
                    deleteTtd: data.urlDeleteTtd,
                    list: data.urlList,
                },
            };

            this.fotoSrc = data.foto || '';
            this.fotoDefault = data.fotoDefault || '';
            this.ttdSrc = data.ttd || '';
            this.initial = this.serialize();

            window.addEventListener('beforeunload', (event) => {
                if (this.dirty) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            });

            window.addEventListener('keydown', (event) => {
                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                    event.preventDefault();
                    this.submit();
                }
            });
        },

        get tabErrors() {
            const membership = {
                profile: ['nama_guru', 'nip', 'email', 'wa_number', 'telegram_id'],
                address: ['no_ktp', 'tgl_lahir', 'kode_pos'],
            };
            const counts = { profile: 0, address: 0 };

            Object.keys(this.errors).forEach((field) => {
                Object.keys(membership).forEach((key) => {
                    if (membership[key].includes(field)) {
                        counts[key]++;
                    }
                });
            });

            return counts;
        },

        get strength() {
            const value = this.login.password || '';
            let score = 0;

            if (value.length >= 6) { score += 34; }
            if (value.length >= 10) { score += 22; }
            if (/[0-9]/.test(value) && /[a-zA-Z]/.test(value)) { score += 22; }
            if (/[^a-zA-Z0-9]/.test(value)) { score += 22; }

            return Math.min(score, 100);
        },

        get strengthClass() {
            return this.strength < 34 ? 'bg-red-500' : (this.strength < 70 ? 'bg-amber-500' : 'bg-emerald-500');
        },

        serialize() {
            if (! this.$refs.form) {
                return '';
            }

            return new URLSearchParams(new FormData(this.$refs.form)).toString();
        },

        markDirty() {
            this.dirty = this.serialize() !== this.initial;
        },

        resetForm() {
            if (this.dirty && ! confirm('Batalkan semua perubahan yang belum disimpan?')) {
                return;
            }

            window.location.reload();
        },

        post(url, payload) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({ _token: this.config.token, ...payload }),
            }).then(async (response) => {
                const json = await response.json().catch(() => ({}));

                return { ok: response.ok, json: json };
            });
        },

        validate() {
            const form = this.$refs.form;
            const value = (name) => (form.elements[name]?.value || '').trim();
            const errors = {};

            if (value('nama_guru') === '') {
                errors.nama_guru = 'Nama guru wajib diisi.';
            }

            const email = value('email');
            if (email !== '' && ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errors.email = 'Format email tidak valid.';
            }

            const nip = value('nip');
            if (nip !== '' && ! /^[0-9]+$/.test(nip)) {
                errors.nip = 'NIP/NUPTK harus angka.';
            }

            // Aturan longgar di client: normalisasi sebenarnya dilakukan server
            // lewat WhatsAppLink — 08, +62, dan 62 semuanya sah.
            const wa = value('wa_number');
            if (wa !== '' && ! /^[0-9+\-\s()]{7,20}$/.test(wa)) {
                errors.wa_number = 'Nomor WhatsApp tidak valid.';
            }

            const nik = value('no_ktp');
            if (nik !== '' && ! /^[0-9]{8,20}$/.test(nik)) {
                errors.no_ktp = 'NIK 8–20 digit angka.';
            }

            const lahir = value('tgl_lahir');
            if (lahir !== '' && isNaN(Date.parse(lahir))) {
                errors.tgl_lahir = 'Tanggal lahir tidak valid.';
            }

            const kodePos = value('kode_pos');
            if (kodePos !== '' && ! /^[0-9]{3,10}$/.test(kodePos)) {
                errors.kode_pos = 'Kode pos harus angka.';
            }

            const telegram = value('telegram_id').replace(/[\s@]/g, '');
            if (telegram !== '' && ! /^-?\d{1,20}$/.test(telegram)) {
                errors.telegram_id = 'ID Telegram harus angka saja.';
            }

            this.errors = errors;

            return Object.keys(errors).length === 0;
        },

        async submit() {
            if (this.saving) {
                return;
            }

            this.alert = '';

            if (! this.validate()) {
                this.tab = this.errors.no_ktp || this.errors.tgl_lahir || this.errors.kode_pos ? 'address' : 'profile';
                this.alert = 'Periksa kembali: ' + Object.values(this.errors).join(' • ');
                return;
            }

            this.saving = true;

            const body = new FormData(this.$refs.form);
            body.set('_token', this.config.token);

            try {
                const response = await fetch(this.config.urls.save, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: body,
                });
                const res = await response.json().catch(() => ({}));

                if (! res || ! res.status) {
                    const message = (res && res.message) || 'Periksa kembali inputan Anda.';
                    this.alert = 'Gagal menyimpan: ' + message;
                    Swal.fire('Gagal', message, 'error');
                    return;
                }

                this.dirty = false;

                // Pesan utama selalu tentang profilnya; kanal notifikasi hanya
                // disebut kalau ada yang benar-benar berubah.
                const notif = Array.isArray(res.notif) ? res.notif.filter(Boolean) : [];
                const adaGagal = notif.some((text) => /gagal|tidak bisa|ditolak|tidak tersedia/i.test(text));

                if (notif.length === 0) {
                    await Swal.fire('Berhasil', 'Data guru berhasil disimpan.', 'success');
                } else if (adaGagal) {
                    await Swal.fire({ icon: 'warning', title: 'Data guru disimpan, sebagian perlu perhatian', html: notif.join('<br>') });
                } else {
                    await Swal.fire({ icon: 'success', title: 'Data guru berhasil disimpan', html: '<b>Perubahan kanal notifikasi:</b><br>' + notif.join('<br>') });
                }

                window.location.reload();
            } catch (e) {
                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            } finally {
                this.saving = false;
            }
        },

        /* ── Foto ────────────────────────────────────────────── */
        openFotoModal() {
            this.resetFoto();
            this.$store.modals.open('#editFotoModal');
        },

        pickFoto() {
            this.$refs.foto.click();
        },

        resetFoto() {
            this.fotoFile = null;
            this.fotoName = '';
            this.cropVisible = false;
            this.dzOver = false;
            this.$refs.foto.value = '';
            this.revokeCropUrl();
        },

        revokeCropUrl() {
            if (this.cropUrl) {
                URL.revokeObjectURL(this.cropUrl);
                this.cropUrl = '';
            }
        },

        onFotoDrop(event) {
            this.dzOver = false;
            const files = event.dataTransfer && event.dataTransfer.files;

            if (files && files.length) {
                this.onFotoPicked(files[0]);
            }
        },

        onFotoPicked(file) {
            if (! file) {
                return;
            }

            if (! /jpe?g|png/i.test(file.type + ' ' + file.name)) {
                Swal.fire('Format Salah', 'Foto harus JPG/PNG.', 'warning');
                return;
            }

            if (file.size > 15 * 1024 * 1024) {
                Swal.fire('Terlalu Besar', 'Ukuran file asli maksimal 15MB.', 'warning');
                return;
            }

            this.revokeCropUrl();
            this.cropUrl = URL.createObjectURL(file);
            this.cropSrc = this.cropUrl;
            this.cropVisible = true;
            this.fotoFile = null;
            this.fotoName = '';
            this.ratio = '4x6';
            this.zoom = 1;
            this.panX = 0;
            this.panY = 0;
        },

        onCropLoad() {
            const image = this.$refs.cropImg;
            const viewport = this.$refs.viewport;

            if (! image || ! viewport) {
                return;
            }

            this.natW = image.naturalWidth || 1;
            this.natH = image.naturalHeight || 1;
            this.measureViewport();
        },

        measureViewport() {
            const viewport = this.$refs.viewport;

            if (! viewport) {
                return;
            }

            this.viewW = viewport.clientWidth || 210;
            this.viewH = viewport.clientHeight || this.viewW * (this.ratio === '4x6' ? 1.5 : 1.333);
            this.baseScale = Math.max(this.viewW / this.natW, this.viewH / this.natH);
        },

        get cropImgStyle() {
            if (! this.natW) {
                return 'display:none';
            }

            const scale = this.baseScale * this.zoom;

            return 'width:' + (this.natW * scale) + 'px;height:' + (this.natH * scale)
                + 'px;transform:translate(-50%,-50%) translate(' + this.panX + 'px,' + this.panY + 'px);';
        },

        renderCrop() {
            this.clampPan();
        },

        clampPan() {
            const scale = this.baseScale * this.zoom;
            const maxX = Math.max(0, (this.natW * scale - this.viewW) / 2);
            const maxY = Math.max(0, (this.natH * scale - this.viewH) / 2);

            this.panX = Math.min(maxX, Math.max(-maxX, this.panX));
            this.panY = Math.min(maxY, Math.max(-maxY, this.panY));
        },

        setRatio(key) {
            this.ratio = key;
            this.$nextTick(() => {
                this.zoom = 1;
                this.panX = 0;
                this.panY = 0;
                this.measureViewport();
                this.clampPan();
            });
        },

        resetCrop() {
            this.zoom = 1;
            this.panX = 0;
            this.panY = 0;
        },

        startDrag(event) {
            if (! this.natW) {
                return;
            }

            this.dragging = true;
            this.dragX = event.clientX;
            this.dragY = event.clientY;
            event.currentTarget.setPointerCapture?.(event.pointerId);
        },

        drag(event) {
            if (! this.dragging) {
                return;
            }

            this.panX += event.clientX - this.dragX;
            this.panY += event.clientY - this.dragY;
            this.dragX = event.clientX;
            this.dragY = event.clientY;
            this.clampPan();
        },

        endDrag() {
            this.dragging = false;
        },

        wheelZoom(event) {
            if (! this.natW) {
                return;
            }

            this.zoom = Math.min(3, Math.max(1, this.zoom - event.deltaY * 0.0015));
            this.clampPan();
        },

        applyCrop() {
            if (! this.natW) {
                return;
            }

            const scale = this.baseScale * this.zoom;
            const cropWidth = this.viewW / scale;
            const cropHeight = this.viewH / scale;
            const sourceX = (this.natW - cropWidth) / 2 - this.panX / scale;
            const sourceY = (this.natH - cropHeight) / 2 - this.panY / scale;
            const maxDim = 800;
            const ratio = this.ratio === '4x6' ? { width: 2, height: 3 } : { width: 3, height: 4 };
            const outputHeight = Math.floor(maxDim / ratio.height) * ratio.height;
            const outputWidth = outputHeight * ratio.width / ratio.height;

            const canvas = document.createElement('canvas');
            canvas.width = outputWidth;
            canvas.height = outputHeight;

            const context = canvas.getContext('2d');
            context.imageSmoothingQuality = 'high';
            // Latar putih: PNG transparan tidak jadi hitam saat diubah ke JPEG.
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, outputWidth, outputHeight);
            context.drawImage(this.$refs.cropImg, sourceX, sourceY, cropWidth, cropHeight, 0, 0, outputWidth, outputHeight);

            canvas.toBlob((blob) => {
                if (! blob) {
                    Swal.fire('Gagal', 'Crop tidak bisa diproses. Coba foto lain.', 'error');
                    return;
                }

                this.fotoFile = new File([blob], 'foto.jpg', { type: 'image/jpeg' });
                this.fotoName = 'foto.jpg · ' + Math.max(1, Math.round(blob.size / 1024)) + ' KB';
                this.cropVisible = false;
                this.fotoSrc = URL.createObjectURL(blob);
                this.revokeCropUrl();
            }, 'image/jpeg', 0.8);
        },

        async uploadFoto() {
            if (! this.fotoFile) {
                Swal.fire('Peringatan', 'Pilih file foto terlebih dahulu.', 'warning');
                return;
            }

            this.uploadingFoto = true;

            const body = new FormData();
            body.append('foto', this.fotoFile, 'foto.jpg');
            body.append('_token', this.config.token);

            try {
                const response = await fetch(this.config.urls.uploadFoto, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: body,
                });
                const res = await response.json().catch(() => ({}));

                if (! res || ! res.status) {
                    Swal.fire('Gagal', (res && res.message) || 'Gagal mengunggah foto.', 'error');
                    return;
                }

                this.fotoSrc = res.src || this.fotoSrc;
                this.$store.modals.close('#editFotoModal');
                Swal.fire('Berhasil', 'Foto berhasil diperbarui', 'success');
            } catch (e) {
                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            } finally {
                this.uploadingFoto = false;
            }
        },

        async deleteFoto() {
            const confirmation = await Swal.fire({
                title: 'Hapus Foto?',
                text: 'Foto profil akan dihapus dan kembali ke default.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
            });

            if (! confirmation.isConfirmed) {
                return;
            }

            this.deletingFoto = true;

            try {
                const { ok, json } = await this.post(this.config.urls.deleteFoto, {});

                if (ok && json.status) {
                    this.fotoSrc = this.fotoDefault;
                    Swal.fire('Terhapus!', 'Foto profil berhasil dihapus.', 'success');
                    return;
                }

                Swal.fire('Gagal', (json && json.message) || 'Gagal menghapus foto.', 'error');
            } catch (e) {
                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            } finally {
                this.deletingFoto = false;
            }
        },

        /* ── Login ───────────────────────────────────────────── */
        async saveLogin() {
            const errors = {};

            if (this.login.username.trim().length < 4) {
                errors.username = 'Username minimal 4 karakter.';
            }

            if (this.login.password !== '' && this.login.password.length < 6) {
                errors.password = 'Password baru minimal 6 karakter.';
            }

            if (this.login.password !== '' && this.login.password !== this.login.confirm) {
                errors.confirm = 'Konfirmasi tidak sama dengan password baru.';
            }

            this.loginErrors = errors;

            if (Object.keys(errors).length > 0) {
                return;
            }

            this.savingLogin = true;

            try {
                const { ok, json } = await this.post(this.config.urls.login, {
                    id_guru: this.config.idGuru,
                    username: this.login.username.trim(),
                    new: this.login.password,
                    new_confirm: this.login.confirm,
                });

                if (ok && json.status) {
                    this.$store.modals.close('#editLoginModal');
                    this.login.password = '';
                    this.login.confirm = '';
                    Swal.fire('Berhasil', json.text || 'Login diperbarui.', 'success');
                    return;
                }

                const message = json.errors
                    ? Object.values(json.errors).join('<br>')
                    : (json.text || json.message || 'Gagal memperbarui login.');

                Swal.fire('Gagal', message, 'error');
            } catch (e) {
                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            } finally {
                this.savingLogin = false;
            }
        },

        /* ── Tanda tangan ────────────────────────────────────── */
        openSignature() {
            this.$store.modals.open('#modal-signature');
            this.$nextTick(() => setTimeout(() => this.fitCanvas(), 60));
        },

        fitCanvas() {
            const canvas = this.$refs.sigCanvas;

            if (! canvas) {
                return;
            }

            const rect = canvas.getBoundingClientRect();
            const width = Math.max(200, Math.floor(rect.width));
            const height = Math.max(120, Math.floor(rect.height || 200));

            if (canvas.width !== width || canvas.height !== height) {
                canvas.width = width;
                canvas.height = height;
            }
        },

        sigPoint(event) {
            const rect = this.$refs.sigCanvas.getBoundingClientRect();

            return [event.clientX - rect.left, event.clientY - rect.top];
        },

        sigStart(event) {
            this.sigDrawing = true;
            this.$refs.sigCanvas.setPointerCapture?.(event.pointerId);
            [this.sigX, this.sigY] = this.sigPoint(event);
        },

        sigMove(event) {
            if (! this.sigDrawing) {
                return;
            }

            event.preventDefault();

            const context = this.$refs.sigCanvas.getContext('2d');
            const [x, y] = this.sigPoint(event);

            context.beginPath();
            context.moveTo(this.sigX, this.sigY);
            context.lineTo(x, y);
            context.lineWidth = 2;
            context.lineCap = 'round';
            context.stroke();
            this.sigX = x;
            this.sigY = y;
        },

        sigEnd() {
            this.sigDrawing = false;
        },

        clearSignature() {
            const canvas = this.$refs.sigCanvas;
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        },

        async saveSignature() {
            this.savingTtd = true;

            try {
                const { ok, json } = await this.post(this.config.urls.saveTtd, {
                    id_guru: this.config.idGuru,
                    image: this.$refs.sigCanvas.toDataURL('image/png'),
                });

                if (ok && json.status) {
                    this.ttdSrc = json.src || this.ttdSrc;
                    this.$store.modals.close('#modal-signature');
                    Swal.fire('Berhasil', json.message || 'TTD tersimpan.', 'success');
                    return;
                }

                Swal.fire('Gagal', (json && json.message) || 'Gagal menyimpan TTD.', 'error');
            } catch (e) {
                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            } finally {
                this.savingTtd = false;
            }
        },

        async deleteTtd() {
            const confirmation = await Swal.fire({
                title: 'Hapus Tanda Tangan?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
            });

            if (! confirmation.isConfirmed) {
                return;
            }

            this.deletingTtd = true;

            try {
                const { ok, json } = await this.post(this.config.urls.deleteTtd, { id_guru: this.config.idGuru });

                if (ok && json.status) {
                    this.ttdSrc = '';
                    Swal.fire('Terhapus', json.message || 'TTD dihapus.', 'success');
                    return;
                }

                Swal.fire('Gagal', (json && json.message) || 'Gagal menghapus TTD.', 'error');
            } catch (e) {
                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
            } finally {
                this.deletingTtd = false;
            }
        },

        /* ── NFC ─────────────────────────────────────────────── */
        async scanNfc() {
            if (! ('NDEFReader' in window)) {
                Swal.fire({
                    icon: 'info',
                    title: 'Web NFC',
                    html: '<div class="space-y-1.5 text-left text-xs text-slate-600">'
                        + '<p>Fitur Tap NFC langsung di browser membutuhkan:</p>'
                        + '<ul class="list-disc space-y-1 pl-4"><li>Smartphone <strong>Android</strong> ber-NFC.</li>'
                        + '<li>Browser <strong>Google Chrome</strong> Android.</li>'
                        + '<li>Akses melalui protokol <strong>HTTPS</strong>.</li></ul>'
                        + '<p class="mt-2 text-slate-500">Anda juga dapat mengetikkan UID kartu secara manual.</p></div>',
                    confirmButtonText: 'Tutup',
                });
                return;
            }

            try {
                const ndef = new NDEFReader();
                await ndef.scan();

                Swal.fire({
                    title: 'Tempelkan Kartu NFC',
                    text: 'Dekatkan kartu RFID ke bagian belakang HP Anda sekarang...',
                    showCancelButton: true,
                    cancelButtonText: 'Batal',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                ndef.onreading = (event) => {
                    const serial = event.serialNumber || '';

                    if (! serial) {
                        return;
                    }

                    navigator.vibrate?.([100, 50, 100]);

                    const clean = serial.replace(/[: -]/g, '').toUpperCase();
                    const field = this.$refs.nomor_rfid;

                    field.value = clean;
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    this.markDirty();

                    Swal.fire({ icon: 'success', title: 'Kartu Terbaca!', text: 'UID Kartu: ' + clean, timer: 1800, showConfirmButton: false });
                };
            } catch (e) {
                Swal.fire('Gagal', e.message || 'Tidak dapat membaca NFC.', 'error');
            }
        },
    }));
});
</script>
@endsection
