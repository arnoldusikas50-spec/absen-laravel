@extends('layouts.main')

@section('content')
@php
    $jjs = [];

    // Jika edit mode dan ada siswakelas, gunakan itu untuk menandai siswa terpilih
    // (CATATAN: jangan pakai $siswa sebagai variabel loop — menimpa array $siswa dari controller!)
    if (isset($siswakelas) && ! empty($siswakelas)) {
        foreach ($siswakelas as $sw) {
            $jjs[] = $sw->id_siswa;
        }
    }

    // Fallback ke jumlah_siswa dari kelas (jika ada)
    if (empty($jjs) && isset($kelas->jumlah_siswa) && ! empty($kelas->jumlah_siswa)) {
        $jumlahSiswa = is_string($kelas->jumlah_siswa) ? json_decode($kelas->jumlah_siswa) : $kelas->jumlah_siswa;
        if (is_array($jumlahSiswa)) {
            foreach ($jumlahSiswa as $js) {
                if (isset($js->id)) {
                    $jjs[] = $js->id;
                }
            }
        }
    }

    $idKelasIni = isset($id_kelas) ? (int) $id_kelas : 0;

    // Semua siswa aktif tampil sebagai opsi (bisa pindah antar kelas).
    // save() memakai REPLACE satu baris per siswa per TP/SMT sehingga
    // memilih siswa dari kelas lain otomatis memindahkannya ke kelas ini.
    $siswaOpsi = [];

    if (isset($siswa) && is_array($siswa)) {
        foreach ($siswa as $s) {
            if (! is_object($s) || ! isset($s->id_siswa)) {
                continue;
            }

            $idSiswa = (int) $s->id_siswa;
            $kelasSekarang = (int) ($s->id_kelas ?? 0);

            $siswaOpsi[$idSiswa] = [
                'id' => $idSiswa,
                'label' => $s->nama.' ('.($s->nis ?? '-').')',
                'kelas' => ($kelasSekarang > 0 && $kelasSekarang !== $idKelasIni && ! empty($s->nama_kelas)) ? $s->nama_kelas : null,
            ];
        }
    }

    // Pastikan anggota kelas ini selalu ada (walau status non-aktif / tak ada di daftar)
    if (isset($siswakelas) && is_array($siswakelas)) {
        foreach ($siswakelas as $sk) {
            if (! is_object($sk) || ! isset($sk->id_siswa)) {
                continue;
            }

            $idSiswa = (int) $sk->id_siswa;
            $siswaOpsi[$idSiswa] = [
                'id' => $idSiswa,
                'label' => $sk->nama.' ('.($sk->nis ?? '-').')',
                'kelas' => null,
            ];
        }
    }

    $siswaOpsi = array_values($siswaOpsi);
    $jjs = array_values(array_unique(array_map('intval', $jjs)));
@endphp

<div class="fade-in-up space-y-4 p-4 md:p-6" x-data="{
        siswa: JSON.parse($el.dataset.siswa),
        selected: JSON.parse($el.dataset.selected),
        q1: '',
        q2: '',
        saving: false,
        get tersedia() {
            const query = this.q1.trim().toLowerCase();

            return this.siswa.filter((s) => ! this.selected.includes(s.id) && (query === '' || s.label.toLowerCase().includes(query)));
        },
        get terpilih() {
            const query = this.q2.trim().toLowerCase();

            return this.siswa.filter((s) => this.selected.includes(s.id) && (query === '' || s.label.toLowerCase().includes(query)));
        },
        pilih(id) {
            if (! this.selected.includes(id)) {
                this.selected.push(id);
            }
        },
        lepas(id) {
            this.selected = this.selected.filter((item) => item !== id);
        },
        async save() {
            if (this.saving) {
                return;
            }

            const form = this.$refs.form;
            const namaInput = form.querySelector('[name=nama_kelas]');
            const nama = (namaInput.value || '').trim();

            if (! (nama.length >= 2)) {
                Swal.fire('Perhatian', 'Nama kelas minimal 2 karakter.', 'warning');
                namaInput.focus();

                return;
            }

            this.saving = true;

            Swal.fire({
                title: 'Sedang menyimpan...',
                text: 'Mohon tunggu sejenak',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const body = new FormData(form);
                this.selected.forEach((id) => body.append('siswa[]', id));

                const response = await fetch('{{ url('datakelas/save') }}', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body,
                });
                const data = await response.json();

                if (data && data.status) {
                    await Swal.fire({
                        title: 'Berhasil',
                        text: data.message || 'Data kelas berhasil disimpan',
                        icon: 'success',
                        confirmButtonColor: '#3085d6',
                    });

                    window.location.href = '{{ url('datakelas') }}';
                } else {
                    Swal.fire({
                        title: 'Gagal',
                        text: (data && data.message) || 'Gagal menyimpan data',
                        icon: 'warning',
                        confirmButtonColor: '#d33',
                    });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({
                    title: 'Error',
                    text: 'Gagal menghubungi server',
                    icon: 'error',
                    confirmButtonColor: '#d33',
                });
            } finally {
                this.saving = false;
            }
        },
    }"
    data-siswa="{{ json_encode($siswaOpsi) }}"
    data-selected="{{ json_encode($jjs) }}">
    <x-ui.page-header :title="($judul ?? 'Kelas').(isset($kelas->nama_kelas) && $kelas->nama_kelas !== '' ? ' — '.$kelas->nama_kelas : '')"
        icon="bi-door-closed" tone="blue"
        :subtitle="($subjudul ?? 'Form Kelas').' · TP '.($tp_active->tahun ?? '-').' · Semester '.($smt_active->smt ?? '-')"
        :crumbs="['Data Kelas', isset($id_kelas) ? 'Edit Kelas' : 'Kelas Baru']">
        <x-slot:actions>
            <a href="{{ url('datakelas') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-3.5 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button type="button" @click="save()" :disabled="saving"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none">
                <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-save'"></i>
                <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <form id="create" x-ref="form" @submit.prevent="save()">
        @csrf
        @isset($id_kelas)
            <input type="hidden" name="id_kelas" value="{{ (int) $id_kelas }}">
        @endisset

        <x-ui.card :title="$subjudul ?? 'Form Kelas'" icon="bi-door-closed" tone="blue"
            subtitle="Lengkapi identitas kelas di bawah ini" body-class="p-5">
            <x-slot:meta>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                    <i class="bi bi-calendar3 text-[10px]"></i> TP {{ $tp_active->tahun ?? '-' }} &middot; Smt {{ $smt_active->smt ?? '-' }}
                </span>
            </x-slot:meta>

            <div class="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2">
                <div>
                    <label for="nama_kelas" class="mb-1.5 block text-xs font-bold text-slate-600">Nama Kelas <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_kelas" id="nama_kelas" value="{{ $kelas->nama_kelas ?? '' }}"
                        placeholder="Contoh: X 1" required minlength="2" maxlength="30"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label for="level_id" class="mb-1.5 block text-xs font-bold text-slate-600">Level <span class="text-red-500">*</span></label>
                    <select name="level_id" id="level_id" required
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        @foreach ($level as $__k => $__v)
                            <option value="{{ $__k }}"{{ (is_array($kelas->level_id ?? '') ? in_array($__k, ($kelas->level_id ?? '')) : $__k == ($kelas->level_id ?? '')) ? ' selected' : '' }}>{{ $__v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="kode_kelas" class="mb-1.5 block text-xs font-bold text-slate-600">Kode Kelas</label>
                    <input type="text" name="kode_kelas" id="kode_kelas" value="{{ $kelas->kode_kelas ?? '' }}"
                        placeholder="Contoh: X-1" maxlength="20"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                @if (isset($guru) && is_array($guru) && count($guru) > 0)
                    <div>
                        <label for="guru_id" class="mb-1.5 block text-xs font-bold text-slate-600">Wali Kelas</label>
                        <select name="guru_id" id="guru_id"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            @foreach ($guru as $__k => $__v)
                                <option value="{{ $__k }}"{{ (is_array($kelas->guru_id ?? '') ? in_array($__k, ($kelas->guru_id ?? '')) : $__k == ($kelas->guru_id ?? '')) ? ' selected' : '' }}>{{ $__v }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">Wali lama kelas ini otomatis dilepas saat diganti.</p>
                    </div>
                @endif
                @if (($setting->jenjang ?? '') == '3')
                    <div class="md:col-span-2">
                        <label for="jurusan_id" class="mb-1.5 block text-xs font-bold text-slate-600">Jurusan</label>
                        <select name="jurusan_id" id="jurusan_id"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            @foreach ($jurusan as $__k => $__v)
                                <option value="{{ $__k }}"{{ (is_array($kelas->jurusan_id ?? '') ? in_array($__k, ($kelas->jurusan_id ?? '')) : $__k == ($kelas->jurusan_id ?? '')) ? ' selected' : '' }}>{{ $__v }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <hr class="my-5 border-slate-100">

            <div>
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                            <i class="bi bi-people text-xs"></i>
                        </span>
                        Daftar Siswa
                    </h3>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700">
                        <i class="bi bi-check-circle-fill text-[10px]"></i><span x-text="selected.length">0</span>&nbsp;terpilih
                    </span>
                </div>

                <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-blue-100 bg-blue-50/70 px-3.5 py-2.5 text-xs text-blue-900">
                    <i class="bi bi-info-circle-fill mt-px shrink-0 text-sm text-blue-600"></i>
                    <span><strong>Catatan:</strong> Klik nama siswa untuk memindahkan antar kolom. Siswa yang sudah masuk kelas lain menampilkan nama kelasnya — memilihnya akan <strong>memindahkan</strong> siswa ke kelas ini. Gunakan pencarian di tiap kolom untuk mempercepat.</span>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/60 px-4 py-2.5">
                            <p class="text-xs font-bold text-slate-600">Siswa Tersedia</p>
                            <span class="text-[11px] font-semibold text-slate-400"><span x-text="siswa.length - selected.length">0</span> siswa</span>
                        </div>
                        <div class="p-3">
                            <input type="search" x-model="q1" placeholder="Cari siswa tersedia..." autocomplete="off"
                                class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <ul class="mt-2 max-h-[320px] space-y-0.5 overflow-y-auto pr-1">
                                <template x-for="s in tersedia" :key="s.id">
                                    <li>
                                        <button type="button" @click="pilih(s.id)"
                                            class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-2 text-left text-xs text-slate-700 transition-colors hover:bg-blue-50 hover:text-blue-700">
                                            <span class="truncate">
                                                <span x-text="s.label"></span>
                                                <span x-show="s.kelas" class="ml-1 rounded-full bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700" x-text="s.kelas"></span>
                                            </span>
                                            <i class="bi bi-chevron-right shrink-0 text-slate-300"></i>
                                        </button>
                                    </li>
                                </template>
                                <li x-show="tersedia.length === 0" class="px-3 py-6 text-center text-xs italic text-slate-400">Tidak ada siswa.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 bg-emerald-50/70 px-4 py-2.5">
                            <p class="text-xs font-bold text-emerald-700">Siswa Terpilih</p>
                            <span class="text-[11px] font-semibold text-emerald-600"><span x-text="selected.length">0</span> siswa</span>
                        </div>
                        <div class="p-3">
                            <input type="search" x-model="q2" placeholder="Cari siswa terpilih..." autocomplete="off"
                                class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <ul class="mt-2 max-h-[320px] space-y-0.5 overflow-y-auto pr-1">
                                <template x-for="s in terpilih" :key="s.id">
                                    <li>
                                        <button type="button" @click="lepas(s.id)"
                                            class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-2 text-left text-xs text-slate-700 transition-colors hover:bg-rose-50 hover:text-rose-700">
                                            <span class="truncate" x-text="s.label"></span>
                                            <i class="bi bi-x-lg shrink-0 text-slate-300"></i>
                                        </button>
                                    </li>
                                </template>
                                <li x-show="terpilih.length === 0" class="px-3 py-6 text-center text-xs italic text-slate-400">Belum ada siswa dipilih.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <p class="mt-2 text-[11px] text-slate-400">
                    Klik nama untuk memindahkan &middot;
                    <span class="font-bold text-slate-600"><span x-text="selected.length">0</span> siswa</span> terpilih di kelas ini.
                </p>
            </div>
        </x-ui.card>
    </form>
</div>
@endsection
