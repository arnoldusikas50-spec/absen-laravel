@extends('layouts.main')

@section('content')

@php
    $fotoSiswa = trim((string) ($siswa->foto ?? ''));
    $defaultFoto = ($siswa->jenis_kelamin ?? '') == 'P' ? 'assets/img/siswa-p.png' : 'assets/img/siswa-m.png';
    $displayFoto = ($fotoSiswa !== '' && strpos($fotoSiswa, '..') === false && file_exists(public_path() . '/' . ltrim($fotoSiswa, '/')))
        ? url(ltrim($fotoSiswa, '/'))
        : url($defaultFoto);

    // Status akademik dinamis (1=Aktif, 2=Lulus, 3=Pindah, 4=Keluar)
    $statusVal = (string) ($siswa->status ?? '1');
    $statusMap = [
        '1' => ['Aktif', 'border-emerald-100 bg-emerald-50 text-emerald-700'],
        '2' => ['Lulus', 'border-blue-100 bg-blue-50 text-blue-700'],
        '3' => ['Pindah', 'border-amber-100 bg-amber-50 text-amber-700'],
        '4' => ['Keluar', 'border-rose-100 bg-rose-50 text-rose-700'],
    ];
    $statusLbl = $statusMap[$statusVal][0] ?? 'Aktif';
    $statusCls = $statusMap[$statusVal][1] ?? $statusMap['1'][1];

    // Peta field by name agar bisa dirender per seksi tanpa ubah controller
    $mapData = []; foreach (($input_data ?? []) as $it) { $mapData[$it->name] = $it; }
    $mapBio  = []; foreach (($input_bio ?? []) as $it) { $mapBio[$it->name] = $it; }
    $mapOrtu = []; foreach (($input_ortu ?? []) as $it) { $mapOrtu[$it->name] = $it; }
    $mapWali = []; foreach (($input_wali ?? []) as $it) { $mapWali[$it->name] = $it; }
    $v = fn ($m, $k) => e($m[$k]->value ?? '');

    // Kelengkapan data (progress)
    $checkKeys = ['nama', 'nis', 'nisn', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'alamat', 'hp', 'nama_ayah', 'nama_ibu'];
    $filled = 0;
    foreach ($checkKeys as $ck) {
        $vv = $mapData[$ck]->value ?? $mapBio[$ck]->value ?? $mapOrtu[$ck]->value ?? null;
        if (trim((string) $vv) !== '') { $filled++; }
    }
    $pct = (int) round($filled / count($checkKeys) * 100);

    // Tanggal lahir → normalisasi Y-m-d untuk input date
    $tglLahir = trim((string) ($mapBio['tanggal_lahir']->value ?? ''));
    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglLahir) || $tglLahir === '0000-00-00') { $tglLahir = ''; }

    $opsiKelasAwal = ($setting->jenjang == 1) ? [1, 2, 3, 4, 5, 6] : (($setting->jenjang == 2) ? [7, 8, 9] : [10, 11, 12]);
@endphp

<div class="fade-in-up space-y-4 px-4 pt-6 md:px-6"
    x-data="siswaEdit"
    data-token="{{ csrf_token() }}"
    data-id-siswa="{{ (int) $siswa->id_siswa }}"
    data-foto="{{ $displayFoto }}"
    data-url-save="{{ url('datasiswa/updateData') }}"
    data-url-upload-foto="{{ url('datasiswa/uploadFile/' . (int) $siswa->id_siswa) }}"
    data-url-delete-foto="{{ url('datasiswa/delete_foto/' . (int) $siswa->id_siswa) }}"
    data-url-login="{{ url('datasiswa/editLogin') }}"
    data-url-list="{{ url('datasiswa') }}">

    <x-ui.page-header
        title="Edit Siswa — {{ $siswa->nama ?? '-' }}"
        icon="bi-pencil-square"
        :subtitle="'NIS ' . ($siswa->nis ?? '-') . ' · NISN ' . ($siswa->nisn ?? '-') . ' · TP ' . ($tp_active->tahun ?? '-') . ' ' . ($smt_active->smt ?? '')"
        :crumbs="['Data Siswa', $siswa->nama ?? 'Edit']">
        <x-slot:actions>
            <a href="{{ url('datasiswa') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button type="button" @click="submit()" :disabled="saving"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                <i class="bi bi-save"></i> Simpan
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <div x-show="alert" x-cloak class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-700" role="alert" x-text="alert"></div>

    <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-[300px_minmax(0,1fr)]">
        <!-- ═══ Kartu profil ═══ -->
        <aside class="min-w-0 lg:sticky lg:top-4">
            <x-ui.card title="Detail Profil" icon="bi-person-badge">
                <div class="mb-5 text-center">
                    <div class="relative inline-block">
                        <img :src="fotoSrc" alt="Foto {{ $siswa->nama ?? 'siswa' }}"
                            class="h-[132px] w-[132px] rounded-full object-cover shadow-sm ring-4 ring-slate-100">
                        <span class="absolute bottom-2 right-2 h-4 w-4 rounded-full bg-emerald-500 ring-2 ring-white" title="Aktif"></span>
                        <button type="button" @click="openFotoModal()"
                            class="absolute bottom-0 right-0 inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-white shadow-md transition-colors hover:bg-blue-700"
                            title="Ganti Foto" aria-label="Ganti foto">
                            <i class="bi bi-camera text-xs"></i>
                        </button>
                    </div>
                    <h4 x-text="namaPreview" class="mt-3 font-bold text-slate-900">{{ $siswa->nama ?? '-' }}</h4>
                    <p class="mt-0.5 font-mono text-xs text-slate-400">NIS {{ $siswa->nis ?? '-' }} · NISN {{ $siswa->nisn ?? '-' }}</p>
                    @if (! empty($siswa->username))
                        <p class="mt-1.5">
                            <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-500">
                                <i class="bi bi-person-circle"></i>{{ $siswa->username }}
                            </span>
                        </p>
                    @endif
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
                        <dt class="text-slate-400">Status</dt>
                        <dd><span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $statusCls }}">{{ $statusLbl }}</span></dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-slate-400">Kelas saat ini</dt>
                        <dd class="text-right font-bold text-slate-700">
                            @if (! empty($kelas_siswa_nama))
                                {{ $kelas_siswa_nama }}
                            @else
                                <span class="text-amber-600">Belum ada kelas</span>
                            @endif
                        </dd>
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
        </aside>

        <!-- ═══ Form ═══ -->
        <div class="min-w-0">
            <form x-ref="form" action="{{ url('datasiswa/updateData') }}" method="POST"
                @submit.prevent="submit()" @input="markDirty()" @change="markDirty()" novalidate>
                @csrf
                <input type="hidden" name="id_siswa" value="{{ (int) $siswa->id_siswa }}">
                <input type="hidden" name="method" value="edit">

                <div class="w-full rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                    <!-- Tab bar -->
                    <div class="z-20 rounded-t-2xl border-b border-slate-100 bg-white/95 p-3 backdrop-blur lg:sticky lg:top-0">
                        <div class="flex items-center gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Bagian data siswa">
                            @foreach ([
                                ['siswa', 'Siswa', 'bi-person'],
                                ['detail', 'Detail', 'bi-card-list'],
                                ['keluarga', 'Keluarga', 'bi-people'],
                                ['wali', 'Wali', 'bi-person-check'],
                            ] as [$key, $label, $icon])
                                <button type="button" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}' ? 'true' : 'false'"
                                    :class="tab === '{{ $key }}' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                    class="relative flex items-center gap-2 whitespace-nowrap rounded-lg px-4 py-2 text-xs font-bold transition-all"
                                    role="tab">
                                    <i class="bi {{ $icon }}"></i> {{ $label }}
                                    <span x-show="tabErrors.{{ $key }} > 0" x-cloak
                                        class="absolute -right-0.5 -top-0.5 inline-flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-extrabold text-white"
                                        x-text="tabErrors.{{ $key }}"></span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-5">
                        <!-- TAB SISWA -->
                        <div x-show="tab === 'siswa'" id="datasiswa" role="tabpanel">
                            <div class="mb-4 grid grid-cols-1 items-center gap-2 rounded-2xl border border-blue-100 bg-blue-50/50 p-3 md:grid-cols-12">
                                <label for="f-id_kelas" class="text-xs font-bold text-blue-800 md:col-span-3 md:mb-0">
                                    Kelas <span class="font-normal">(TP {{ $tp_active->tahun ?? '-' }})</span>
                                </label>
                                <div class="md:col-span-9">
                                    <div class="relative">
                                        <i class="bi bi-mortarboard pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-blue-400"></i>
                                        <select id="f-id_kelas" name="id_kelas"
                                            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                            <option value="">— Belum ada kelas —</option>
                                            @foreach (($kelas_list ?? []) as $kls)
                                                <option value="{{ (int) $kls->id_kelas }}" {{ isset($kelas_siswa_id) && (int) $kelas_siswa_id === (int) $kls->id_kelas ? 'selected' : '' }}>
                                                    {{ $kls->nama_kelas ?? ('Kelas #' . $kls->id_kelas) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <p class="mt-1 text-[11px] text-blue-500/80">Pindah kelas hanya berlaku untuk TP/Semester aktif. Kosongkan untuk keluarkan dari kelas.</p>
                                </div>
                            </div>

                            <div class="mb-3 flex items-center gap-2.5">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[9px] bg-blue-50 text-[13px] text-blue-600"><i class="bi bi-person"></i></span>
                                <div>
                                    <h3 class="text-[13px] font-extrabold text-slate-900">Data Pokok</h3>
                                    <p class="text-[11px] text-slate-400">Identitas utama — wajib diisi</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label for="f-nama" class="mb-1.5 block text-xs font-bold text-slate-700">Nama Lengkap <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <i class="bi bi-person pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="text" id="f-nama" name="nama" value="{{ $v($mapData, 'nama') }}"
                                            @input="namaPreview = $event.target.value" required minlength="3" maxlength="100" autocomplete="off"
                                            placeholder="Nama lengkap sesuai ijazah"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                            :class="errors.nama ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                    </div>
                                    <p class="mt-1 text-[11px] text-rose-600" x-show="errors.nama" x-cloak x-text="errors.nama"></p>
                                </div>

                                <div>
                                    <label for="f-nis" class="mb-1.5 block text-xs font-bold text-slate-700">NIS <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <i class="bi bi-person-badge pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="text" id="f-nis" name="nis" inputmode="numeric" value="{{ $v($mapData, 'nis') }}"
                                            required maxlength="20" autocomplete="off" placeholder="Min. 4 digit angka"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                            :class="errors.nis ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                    </div>
                                    <p class="mt-1 text-[11px] text-rose-600" x-show="errors.nis" x-cloak x-text="errors.nis"></p>
                                </div>

                                <div>
                                    <label for="f-nisn" class="mb-1.5 block text-xs font-bold text-slate-700">NISN <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <i class="bi bi-person-badge pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="text" id="f-nisn" name="nisn" inputmode="numeric" value="{{ $v($mapData, 'nisn') }}"
                                            required maxlength="20" autocomplete="off" placeholder="10 digit NISN"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                            :class="errors.nisn ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                    </div>
                                    <p class="mt-1 text-[11px] text-rose-600" x-show="errors.nisn" x-cloak x-text="errors.nisn"></p>
                                </div>

                                <div>
                                    <label for="f-jk" class="mb-1.5 block text-xs font-bold text-slate-700">Jenis Kelamin</label>
                                    <div class="relative">
                                        <i class="bi bi-gender-ambiguous pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <select id="f-jk" name="jenis_kelamin"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                            <option value="L" {{ ($mapData['jenis_kelamin']->value ?? '') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                            <option value="P" {{ ($mapData['jenis_kelamin']->value ?? '') == 'P' ? 'selected' : '' }}>Perempuan</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label for="f-status" class="mb-1.5 block text-xs font-bold text-slate-700">Status</label>
                                    <div class="relative">
                                        <i class="bi bi-person-check pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <select id="f-status" name="status"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                            <option value="1" {{ ($mapData['status']->value ?? '') == '1' ? 'selected' : '' }}>AKTIF</option>
                                            <option value="2" {{ ($mapData['status']->value ?? '') == '2' ? 'selected' : '' }}>LULUS</option>
                                            <option value="3" {{ ($mapData['status']->value ?? '') == '3' ? 'selected' : '' }}>PINDAH</option>
                                            <option value="4" {{ ($mapData['status']->value ?? '') == '4' ? 'selected' : '' }}>KELUAR</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label for="f-nomor_rfid" class="mb-1.5 block text-xs font-bold text-slate-700">Nomor Kartu RFID</label>
                                    <div class="relative">
                                        <i class="bi bi-card-id pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="text" id="f-nomor_rfid" name="nomor_rfid" x-ref="nomor_rfid"
                                            value="{{ $v($mapData, 'nomor_rfid') }}" maxlength="64" autocomplete="off" placeholder="04A1B2C3D"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-24 font-mono text-sm uppercase text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                        <button type="button" @click="scanNfc()"
                                            class="absolute right-1.5 top-1/2 inline-flex -translate-y-1/2 items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-[11px] font-bold text-indigo-700 transition-all hover:bg-indigo-100"
                                            title="Tempelkan kartu ke HP untuk isi otomatis">
                                            <i class="bi bi-broadcast"></i> <span>Tap NFC</span>
                                        </button>
                                    </div>
                                    <p class="mt-1 text-[11px] leading-relaxed text-slate-400">Nomor kartu RFID siswa — dipakai untuk QR, tap kartu, dan USB scanner.</p>
                                </div>

                                <div>
                                    <label for="f-fingerprint_uid" class="mb-1.5 block text-xs font-bold text-slate-700">UserID Mesin Sidik Jari</label>
                                    <div class="relative">
                                        <i class="bi bi-fingerprint pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="text" id="f-fingerprint_uid" name="fingerprint_uid" value="{{ $v($mapData, 'fingerprint_uid') }}"
                                            maxlength="64" autocomplete="off" placeholder="1001"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                    </div>
                                    <p class="mt-1 text-[11px] leading-relaxed text-slate-400">UserID di mesin sidik jari GEISA GX330.</p>
                                </div>
                            </div>

                            <div class="mb-3 mt-5 flex items-center gap-2.5">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[9px] bg-emerald-50 text-[13px] text-emerald-600"><i class="bi bi-mortarboard"></i></span>
                                <div>
                                    <h3 class="text-[13px] font-extrabold text-slate-900">Riwayat Akademik</h3>
                                    <p class="text-[11px] text-slate-400">Asal sekolah &amp; penerimaan</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="f-kelas_awal" class="mb-1.5 block text-xs font-bold text-slate-700">Diterima di Kelas</label>
                                    <div class="relative">
                                        <i class="bi bi-mortarboard pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <select id="f-kelas_awal" name="kelas_awal"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                            @foreach ($opsiKelasAwal as $kls)
                                                <option value="{{ $kls }}" {{ ($mapData['kelas_awal']->value ?? '') == $kls ? 'selected' : '' }}>Kelas {{ $kls }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label for="f-tahun_masuk" class="mb-1.5 block text-xs font-bold text-slate-700">Tanggal Diterima</label>
                                    <div class="relative">
                                        <i class="bi bi-calendar-date pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="date" id="f-tahun_masuk" name="tahun_masuk" value="{{ $v($mapData, 'tahun_masuk') }}"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                            :class="errors.tahun_masuk ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                    </div>
                                    <p class="mt-1 text-[11px] text-rose-600" x-show="errors.tahun_masuk" x-cloak x-text="errors.tahun_masuk"></p>
                                </div>

                                <div class="md:col-span-2">
                                    <label for="f-sekolah_asal" class="mb-1.5 block text-xs font-bold text-slate-700">Sekolah Asal</label>
                                    <div class="relative">
                                        <i class="bi bi-bank pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="text" id="f-sekolah_asal" name="sekolah_asal" value="{{ $v($mapData, 'sekolah_asal') }}"
                                            maxlength="100" autocomplete="off" placeholder="Contoh: SDN 1 Kupang"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB DETAIL -->
                        <div x-show="tab === 'detail'" x-cloak id="biosiswa" role="tabpanel">
                            <div class="mb-3 flex items-center gap-2.5">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[9px] bg-violet-50 text-[13px] text-violet-600"><i class="bi bi-cake2"></i></span>
                                <div>
                                    <h3 class="text-[13px] font-extrabold text-slate-900">Kelahiran &amp; Identitas</h3>
                                    <p class="text-[11px] text-slate-400">Tempat, tanggal lahir &amp; agama</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="f-tempat_lahir" class="mb-1.5 block text-xs font-bold text-slate-700">Tempat Lahir</label>
                                    <div class="relative">
                                        <i class="bi bi-geo-alt pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="text" id="f-tempat_lahir" name="tempat_lahir" value="{{ $v($mapBio, 'tempat_lahir') }}"
                                            maxlength="50" autocomplete="off" placeholder="Kota kelahiran"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                    </div>
                                </div>
                                <div>
                                    <label for="f-tanggal_lahir" class="mb-1.5 block text-xs font-bold text-slate-700">Tanggal Lahir</label>
                                    <div class="relative">
                                        <i class="bi bi-calendar-date pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="date" id="f-tanggal_lahir" name="tanggal_lahir" value="{{ $tglLahir }}" max="{{ date('Y-m-d') }}"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                            :class="errors.tanggal_lahir ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                    </div>
                                    <p class="mt-1 text-[11px] text-rose-600" x-show="errors.tanggal_lahir" x-cloak x-text="errors.tanggal_lahir"></p>
                                </div>
                                <div>
                                    <label for="f-agama" class="mb-1.5 block text-xs font-bold text-slate-700">Agama</label>
                                    <div class="relative">
                                        <i class="bi bi-star pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <select id="f-agama" name="agama"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                            <option value="">— Pilih Agama —</option>
                                            @foreach (['Islam', 'Katolik', 'Protestan', 'Hindu', 'Budha', 'Konghucu'] as $agm)
                                                <option value="{{ $agm }}" {{ ($mapBio['agama']->value ?? '') == $agm ? 'selected' : '' }}>{{ $agm }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label for="f-hp" class="mb-1.5 block text-xs font-bold text-slate-700">No. HP Siswa</label>
                                    <div class="relative">
                                        <i class="bi bi-telephone pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="tel" id="f-hp" name="hp" inputmode="tel" value="{{ $v($mapBio, 'hp') }}"
                                            maxlength="20" autocomplete="off" placeholder="08xxxxxxxxxx"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                            :class="errors.hp ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                    </div>
                                    <p class="mt-1 text-[11px] text-rose-600" x-show="errors.hp" x-cloak x-text="errors.hp"></p>
                                </div>
                            </div>

                            <div class="mb-3 mt-5 flex items-center gap-2.5">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[9px] bg-amber-50 text-[13px] text-amber-600"><i class="bi bi-house-door"></i></span>
                                <div>
                                    <h3 class="text-[13px] font-extrabold text-slate-900">Alamat Domisili</h3>
                                    <p class="text-[11px] text-slate-400">Alamat lengkap tempat tinggal</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label for="f-alamat" class="mb-1.5 block text-xs font-bold text-slate-700">Alamat (Jalan / Dusun)</label>
                                    <textarea id="f-alamat" name="alamat" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW, dusun..."
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">{{ $v($mapBio, 'alamat') }}</textarea>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                                <div>
                                    <label for="f-rt" class="mb-1.5 block text-xs font-bold text-slate-700">RT</label>
                                    <input type="text" id="f-rt" name="rt" inputmode="numeric" value="{{ $v($mapBio, 'rt') }}" maxlength="5" placeholder="001"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-center font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div>
                                    <label for="f-rw" class="mb-1.5 block text-xs font-bold text-slate-700">RW</label>
                                    <input type="text" id="f-rw" name="rw" inputmode="numeric" value="{{ $v($mapBio, 'rw') }}" maxlength="5" placeholder="002"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-center font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div class="col-span-2">
                                    <label for="f-kode_pos" class="mb-1.5 block text-xs font-bold text-slate-700">Kode Pos</label>
                                    <input type="text" id="f-kode_pos" name="kode_pos" inputmode="numeric" value="{{ $v($mapBio, 'kode_pos') }}" maxlength="10" placeholder="85xxx"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div class="col-span-2">
                                    <label for="f-kelurahan" class="mb-1.5 block text-xs font-bold text-slate-700">Kelurahan / Desa</label>
                                    <input type="text" id="f-kelurahan" name="kelurahan" value="{{ $v($mapBio, 'kelurahan') }}" maxlength="50" placeholder="Kelurahan / desa"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div class="col-span-2">
                                    <label for="f-kecamatan" class="mb-1.5 block text-xs font-bold text-slate-700">Kecamatan</label>
                                    <input type="text" id="f-kecamatan" name="kecamatan" value="{{ $v($mapBio, 'kecamatan') }}" maxlength="50" placeholder="Kecamatan"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div class="col-span-2 md:col-span-4">
                                    <label for="f-kabupaten" class="mb-1.5 block text-xs font-bold text-slate-700">Kabupaten / Kota</label>
                                    <input type="text" id="f-kabupaten" name="kabupaten" value="{{ $v($mapBio, 'kabupaten') }}" maxlength="50" placeholder="Kabupaten / kota"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                </div>
                            </div>
                        </div>

                        <!-- TAB KELUARGA -->
                        <div x-show="tab === 'keluarga'" x-cloak id="ortusiswa" role="tabpanel">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="f-status_keluarga" class="mb-1.5 block text-xs font-bold text-slate-700">Status Keluarga</label>
                                    <div class="relative">
                                        <i class="bi bi-people pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <select id="f-status_keluarga" name="status_keluarga"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                            <option value="1" {{ ($mapOrtu['status_keluarga']->value ?? '') == '1' ? 'selected' : '' }}>Anak Kandung</option>
                                            <option value="2" {{ ($mapOrtu['status_keluarga']->value ?? '') == '2' ? 'selected' : '' }}>Anak Tiri</option>
                                            <option value="3" {{ ($mapOrtu['status_keluarga']->value ?? '') == '3' ? 'selected' : '' }}>Anak Angkat</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label for="f-anak_ke" class="mb-1.5 block text-xs font-bold text-slate-700">Anak Ke-</label>
                                    <div class="relative">
                                        <i class="bi bi-sort-numeric-down pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="number" id="f-anak_ke" name="anak_ke" value="{{ $v($mapOrtu, 'anak_ke') }}" min="1" max="30" placeholder="1"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 mt-5 flex items-center gap-2.5">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[9px] bg-blue-50 text-[13px] text-blue-600"><i class="bi bi-person"></i></span>
                                <div>
                                    <h3 class="text-[13px] font-extrabold text-slate-900">Data Ayah</h3>
                                    <p class="text-[11px] text-slate-400">Identitas &amp; kontak ayah kandung</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-200/80 bg-slate-50/50 p-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label for="f-nama_ayah" class="mb-1.5 block text-xs font-bold text-slate-700">Nama Ayah</label>
                                    <input type="text" id="f-nama_ayah" name="nama_ayah" value="{{ $v($mapOrtu, 'nama_ayah') }}" maxlength="100" autocomplete="off" placeholder="Nama lengkap ayah"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div>
                                    <label for="f-pekerjaan_ayah" class="mb-1.5 block text-xs font-bold text-slate-700">Pekerjaan Ayah</label>
                                    <input type="text" id="f-pekerjaan_ayah" name="pekerjaan_ayah" value="{{ $v($mapOrtu, 'pekerjaan_ayah') }}" maxlength="50" autocomplete="off" placeholder="Contoh: Wiraswasta"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div>
                                    <label for="f-nohp_ayah" class="mb-1.5 block text-xs font-bold text-slate-700">No. HP Ayah</label>
                                    <input type="tel" id="f-nohp_ayah" name="nohp_ayah" inputmode="tel" value="{{ $v($mapOrtu, 'nohp_ayah') }}" maxlength="20" autocomplete="off" placeholder="08xxxxxxxxxx"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                                        :class="errors.nohp_ayah ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                    <p class="mt-1 text-[11px] text-rose-600" x-show="errors.nohp_ayah" x-cloak x-text="errors.nohp_ayah"></p>
                                </div>
                                <div class="md:col-span-2">
                                    <label for="f-alamat_ayah" class="mb-1.5 block text-xs font-bold text-slate-700">Alamat Ayah
                                        <span class="font-normal text-slate-400">(kosongkan jika sama dengan siswa)</span></label>
                                    <textarea id="f-alamat_ayah" name="alamat_ayah" rows="2" placeholder="Alamat ayah..."
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">{{ $v($mapOrtu, 'alamat_ayah') }}</textarea>
                                </div>
                            </div>

                            <div class="mb-3 mt-5 flex items-center gap-2.5">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[9px] bg-pink-50 text-[13px] text-pink-600"><i class="bi bi-person"></i></span>
                                <div>
                                    <h3 class="text-[13px] font-extrabold text-slate-900">Data Ibu</h3>
                                    <p class="text-[11px] text-slate-400">Identitas &amp; kontak ibu kandung</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-200/80 bg-slate-50/50 p-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label for="f-nama_ibu" class="mb-1.5 block text-xs font-bold text-slate-700">Nama Ibu</label>
                                    <input type="text" id="f-nama_ibu" name="nama_ibu" value="{{ $v($mapOrtu, 'nama_ibu') }}" maxlength="100" autocomplete="off" placeholder="Nama lengkap ibu"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div>
                                    <label for="f-pekerjaan_ibu" class="mb-1.5 block text-xs font-bold text-slate-700">Pekerjaan Ibu</label>
                                    <input type="text" id="f-pekerjaan_ibu" name="pekerjaan_ibu" value="{{ $v($mapOrtu, 'pekerjaan_ibu') }}" maxlength="50" autocomplete="off" placeholder="Contoh: Ibu Rumah Tangga"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div>
                                    <label for="f-nohp_ibu" class="mb-1.5 block text-xs font-bold text-slate-700">No. HP Ibu</label>
                                    <input type="tel" id="f-nohp_ibu" name="nohp_ibu" inputmode="tel" value="{{ $v($mapOrtu, 'nohp_ibu') }}" maxlength="20" autocomplete="off" placeholder="08xxxxxxxxxx"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                                        :class="errors.nohp_ibu ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                    <p class="mt-1 text-[11px] text-rose-600" x-show="errors.nohp_ibu" x-cloak x-text="errors.nohp_ibu"></p>
                                </div>
                                <div class="md:col-span-2">
                                    <label for="f-alamat_ibu" class="mb-1.5 block text-xs font-bold text-slate-700">Alamat Ibu
                                        <span class="font-normal text-slate-400">(kosongkan jika sama dengan siswa)</span></label>
                                    <textarea id="f-alamat_ibu" name="alamat_ibu" rows="2" placeholder="Alamat ibu..."
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">{{ $v($mapOrtu, 'alamat_ibu') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- TAB WALI -->
                        <div x-show="tab === 'wali'" x-cloak id="walisiswa" role="tabpanel">
                            <div class="mb-4 flex items-start gap-2 rounded-2xl border border-blue-100 bg-blue-50/50 px-4 py-3 text-xs text-blue-700">
                                <i class="bi bi-info-circle mt-0.5 shrink-0"></i>
                                <span><b>Opsional.</b> Isi hanya jika siswa memiliki wali selain orang tua kandung (mis. kakek, paman, atau pengasuh pondok).</span>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label for="f-nama_wali" class="mb-1.5 block text-xs font-bold text-slate-700">Nama Wali</label>
                                    <div class="relative">
                                        <i class="bi bi-person-check pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="text" id="f-nama_wali" name="nama_wali" value="{{ $v($mapWali, 'nama_wali') }}"
                                            maxlength="100" autocomplete="off" placeholder="Nama lengkap wali"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                    </div>
                                </div>
                                <div>
                                    <label for="f-pekerjaan_wali" class="mb-1.5 block text-xs font-bold text-slate-700">Pekerjaan Wali</label>
                                    <div class="relative">
                                        <i class="bi bi-briefcase pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="text" id="f-pekerjaan_wali" name="pekerjaan_wali" value="{{ $v($mapWali, 'pekerjaan_wali') }}"
                                            maxlength="50" autocomplete="off" placeholder="Pekerjaan wali"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                                    </div>
                                </div>
                                <div>
                                    <label for="f-nohp_wali" class="mb-1.5 block text-xs font-bold text-slate-700">No. HP Wali</label>
                                    <div class="relative">
                                        <i class="bi bi-telephone pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                        <input type="tel" id="f-nohp_wali" name="nohp_wali" inputmode="tel" value="{{ $v($mapWali, 'nohp_wali') }}"
                                            maxlength="20" autocomplete="off" placeholder="08xxxxxxxxxx"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 font-mono text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                                            :class="errors.nohp_wali ? 'border-rose-400 ring-2 ring-rose-500/10' : ''">
                                    </div>
                                    <p class="mt-1 text-[11px] text-rose-600" x-show="errors.nohp_wali" x-cloak x-text="errors.nohp_wali"></p>
                                </div>
                                <div class="md:col-span-2">
                                    <label for="f-alamat_wali" class="mb-1.5 block text-xs font-bold text-slate-700">Alamat Wali</label>
                                    <textarea id="f-alamat_wali" name="alamat_wali" rows="2" placeholder="Alamat lengkap wali..."
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">{{ $v($mapWali, 'alamat_wali') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky save bar -->
                    <div class="px-4 pb-4">
                        <div class="sticky bottom-3 z-30 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur">
                            <span class="mr-auto flex items-center gap-2 text-xs font-semibold text-slate-500">
                                <span class="h-2 w-2 rounded-full transition-colors" :class="dirty ? 'animate-pulse bg-amber-500' : 'bg-slate-300'"></span>
                                <span x-text="dirty ? 'Ada perubahan belum disimpan' : 'Belum ada perubahan'"></span>
                            </span>
                            <button type="button" @click="resetForm()"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-50">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </button>
                            <a href="{{ url('datasiswa') }}"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-bold text-rose-600 no-underline transition-colors hover:bg-rose-50">
                                <i class="bi bi-x-lg"></i> Batal
                            </a>
                            <button type="submit" :disabled="saving"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-xs transition-colors hover:bg-blue-700 disabled:opacity-60">
                                <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                <i class="bi bi-save" x-show="!saving"></i>
                                <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══ Modal Foto ═══ -->
    <x-ui.modal id="editFotoModal" title="Update Foto Siswa" icon="bi-camera">
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

    <!-- ═══ Modal Akun Login ═══ -->
    <x-ui.modal id="editLoginModal" title="Update Akun Siswa" icon="bi-key" tone="amber">
        <form @submit.prevent="saveLogin()" novalidate>
            <input type="hidden" name="id_siswa" value="{{ (int) $siswa->id_siswa }}">
            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Username</label>
                    <input type="text" value="{{ $siswa->username ?? '' }}" readonly disabled
                        class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 font-mono text-sm text-slate-500">
                    <p class="mt-1 text-[11px] text-slate-400">Username terkunci · hanya password yang diubah.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-700" for="login-password">Password Baru <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input :type="showLoginPassword ? 'text' : 'password'" id="login-password" x-model="password"
                            minlength="6" maxlength="72" required autocomplete="new-password" placeholder="Minimal 6 karakter"
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
                    <ul class="mt-2 space-y-1 text-[11px] text-slate-500">
                        <li>
                            <i class="bi mr-1" :class="password.length >= 6 ? 'bi-check-circle-fill text-emerald-500' : 'bi-circle'"></i>Minimal 6 karakter
                        </li>
                        <li>
                            <i class="bi mr-1" :class="hasMixedPassword ? 'bi-check-circle-fill text-emerald-500' : 'bi-circle'"></i>Lebih kuat jika campur huruf + angka
                        </li>
                    </ul>
                </div>
            </div>
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
                <button type="button" @click="$store.modals.close('#editLoginModal')"
                    class="rounded-xl bg-slate-200 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-300">Batal</button>
                <button type="submit" :disabled="savingLogin"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-white hover:bg-amber-600 disabled:opacity-60">
                    <span x-show="savingLogin" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <i class="bi bi-key" x-show="!savingLogin"></i>
                    <span x-text="savingLogin ? 'Menyimpan...' : 'Update Password'"></span>
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('siswaEdit', () => ({
        tab: 'siswa',
        dirty: false,
        alert: '',
        saving: false,
        errors: {},
        initial: '',
        namaPreview: @js($siswa->nama ?? ''),
        fotoSrc: '',
        fotoDefault: '',
        deletingFoto: false,
        config: {},

        // foto + crop
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
        password: '',
        showLoginPassword: false,
        savingLogin: false,

        init() {
            const data = this.$el.dataset;
            this.config = {
                token: data.token,
                idSiswa: Number(data.idSiswa) || 0,
                urls: {
                    save: data.urlSave,
                    uploadFoto: data.urlUploadFoto,
                    deleteFoto: data.urlDeleteFoto,
                    login: data.urlLogin,
                    list: data.urlList,
                },
            };

            this.fotoSrc = data.foto || '';
            this.fotoDefault = data.foto || '';
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
                siswa: ['nama', 'nis', 'nisn', 'tahun_masuk'],
                detail: ['tanggal_lahir', 'hp'],
                keluarga: ['nohp_ayah', 'nohp_ibu'],
                wali: ['nohp_wali'],
            };
            const counts = { siswa: 0, detail: 0, keluarga: 0, wali: 0 };

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
            const value = this.password || '';
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

        get hasMixedPassword() {
            return /[0-9]/.test(this.password) && /[a-zA-Z]/.test(this.password);
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
            const phoneOk = (input) => input === '' || /^[0-9+\-\s]{8,20}$/.test(input);
            const errors = {};

            if (value('nama').length < 3) {
                errors.nama = 'Nama minimal 3 karakter.';
            }

            if (! /^[0-9]{4,20}$/.test(value('nis'))) {
                errors.nis = 'NIS wajib angka, min. 4 digit.';
            }

            if (! /^[0-9]{9,20}$/.test(value('nisn'))) {
                errors.nisn = 'NISN wajib angka, min. 9 digit.';
            }

            // <input type="date"> mengirim YYYY-MM-DD. Tahun saja (YYYY) juga
            // diterima supaya data lama yang tersimpan sebagai tahun tidak gagal.
            const masuk = value('tahun_masuk');
            if (masuk !== '' && ! /^\d{4}(-\d{2}-\d{2})?$/.test(masuk)) {
                errors.tahun_masuk = 'Tanggal tidak valid.';
            }

            const lahir = value('tanggal_lahir');
            if (lahir !== '' && isNaN(Date.parse(lahir))) {
                errors.tanggal_lahir = 'Tanggal lahir tidak valid.';
            }

            ['hp', 'nohp_ayah', 'nohp_ibu', 'nohp_wali'].forEach((field) => {
                if (! phoneOk(value(field))) {
                    errors[field] = 'Nomor HP 8–20 digit.';
                }
            });

            this.errors = errors;

            return Object.keys(errors).length === 0;
        },

        async submit() {
            if (this.saving) {
                return;
            }

            this.alert = '';

            if (! this.validate()) {
                const first = Object.keys(this.errors)[0];
                const membership = {
                    nama: 'siswa', nis: 'siswa', nisn: 'siswa', tahun_masuk: 'siswa',
                    tanggal_lahir: 'detail', hp: 'detail',
                    nohp_ayah: 'keluarga', nohp_ibu: 'keluarga',
                    nohp_wali: 'wali',
                };
                this.tab = membership[first] || 'siswa';
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
                const data = await response.json().catch(() => ({}));

                // updateData mengembalikan {insert, text} — bukan {status, message}
                if (! data || ! data.insert) {
                    this.alert = 'Gagal menyimpan: ' + ((data && data.text) || 'Terjadi kesalahan');
                    Swal.fire('Gagal', 'Periksa pesan di atas form.', 'error');
                    return;
                }

                this.dirty = false;
                await Swal.fire('Berhasil', data.text || 'Tersimpan.', 'success');
                window.location.reload();
            } catch (e) {
                Swal.fire('Error', 'Gagal menghubungi server', 'error');
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
                const data = await response.json().catch(() => ({}));

                if (! data || ! data.status) {
                    Swal.fire('Gagal', (data && data.message) || 'Gagal upload foto.', 'error');
                    return;
                }

                this.dirty = false;
                await Swal.fire('Berhasil', 'Foto berhasil diperbarui', 'success');
                window.location.reload();
            } catch (e) {
                Swal.fire('Error', 'Gagal menghubungi server', 'error');
            } finally {
                this.uploadingFoto = false;
            }
        },

        async deleteFoto() {
            const confirmation = await Swal.fire({
                title: 'Hapus Foto?',
                text: 'Foto akan dihapus dan kembali ke default.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
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
                    this.dirty = false;
                    await Swal.fire('Terhapus', json.message || 'Foto dihapus.', 'success');
                    window.location.reload();
                    return;
                }

                Swal.fire('Gagal', (json && json.message) || 'Gagal menghapus foto.', 'error');
            } catch (e) {
                Swal.fire('Error', 'Gagal menghubungi server.', 'error');
            } finally {
                this.deletingFoto = false;
            }
        },

        /* ── Login ───────────────────────────────────────────── */
        async saveLogin() {
            if (this.password.length < 6) {
                Swal.fire('Perhatian', 'Password minimal 6 karakter.', 'warning');
                return;
            }

            this.savingLogin = true;

            try {
                const { ok, json } = await this.post(this.config.urls.login, {
                    id_siswa: this.config.idSiswa,
                    password: this.password,
                });

                if (ok && json.status) {
                    this.$store.modals.close('#editLoginModal');
                    this.password = '';
                    Swal.fire('Berhasil', json.message || 'Password diperbarui.', 'success');
                    return;
                }

                Swal.fire('Gagal', (json && json.message) || 'Gagal memperbarui password.', 'error');
            } catch (e) {
                Swal.fire('Error', 'Gagal menghubungi server.', 'error');
            } finally {
                this.savingLogin = false;
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
                    text: 'Dekatkan kartu RFID siswa ke bagian belakang HP Anda sekarang...',
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
