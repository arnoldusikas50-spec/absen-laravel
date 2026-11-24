@extends('layouts.main')

@section('content')
<div class="fade-in-up space-y-4 p-4 md:p-6" x-data="{
        mode: $el.dataset.mode || 'persiswa',
        kelasSelected: $el.dataset.kelas,
        token: $el.dataset.token,
        urlNaik: $el.dataset.urlNaik,
        urlSemua: $el.dataset.urlSemua,
        urlKenaikan: $el.dataset.urlKenaikan,
        setMode(mode) {
            this.mode = mode;
        },
        gantiKelas(id) {
            if (id) {
                window.location.href = this.urlKenaikan + '?kelas=' + encodeURIComponent(id) + '&type=' + this.mode;
            }
        },
        setSemuaTarget(value) {
            this.$root.querySelectorAll('select.sel-target-kelas').forEach((select) => {
                select.value = value;
            });
        },
        toggleSemuaKelas(checked) {
            this.$root.querySelectorAll('.check-per-kelas').forEach((checkbox) => {
                checkbox.checked = checked;
            });
        },
        async kirim(url, body) {
            Swal.fire({
                title: 'Sedang memproses...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    },
                    body,
                });
                const data = await response.json();
                const jumlah = parseInt(data && data.res) || 0;
                const pesan = (data && data.message) ? data.message : '';

                if (jumlah > 0) {
                    await Swal.fire('Berhasil', pesan || (jumlah + ' siswa dipindahkan'), 'success');
                    window.location.reload();
                } else {
                    Swal.fire('Gagal', pesan || 'Tidak ada siswa yang dipindahkan', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Gagal menghubungi server', 'error');
            }
        },
        async simpanPersiswa() {
            const items = [];

            this.$root.querySelectorAll('#tbl-siswa tbody tr').forEach((row) => {
                const select = row.querySelector('select.sel-target-kelas');

                if (! select || ! select.value || select.value === '0') {
                    return;
                }

                items.push({
                    id_siswa: row.dataset.id,
                    nama_kls_baru: select.options[select.selectedIndex].text.trim(),
                    id_kls_lama: this.kelasSelected,
                });
            });

            if (items.length === 0) {
                Swal.fire('Peringatan', 'Pilih minimal satu kelas baru untuk siswa!', 'warning');

                return;
            }

            const body = new URLSearchParams();
            body.append('_token', this.token);
            body.append('mode', 'persiswa');
            body.append('kelas', JSON.stringify(items));

            await this.kirim(this.urlNaik, body);
        },
        async simpanPerkelas() {
            const target = this.$root.querySelector('#bulk-target-kelas');

            if (! target || target.value === '0') {
                Swal.fire('Peringatan', 'Pilih kelas target!', 'warning');

                return;
            }

            const targetName = target.options[target.selectedIndex].text.trim();
            const items = [];

            this.$root.querySelectorAll('#tbl-siswa tbody tr').forEach((row) => {
                items.push({
                    id_siswa: row.dataset.id,
                    nama_kls_baru: targetName,
                    id_kls_lama: this.kelasSelected,
                });
            });

            const body = new URLSearchParams();
            body.append('_token', this.token);
            body.append('mode', 'perkelas');
            body.append('kelas', JSON.stringify(items));

            await this.kirim(this.urlNaik, body);
        },
        async simpanSemuaKelas() {
            const checked = Array.from(this.$root.querySelectorAll('.check-per-kelas')).filter((checkbox) => checkbox.checked);

            if (checked.length === 0) {
                Swal.fire('Peringatan', 'Pilih minimal satu kelas!', 'warning');

                return;
            }

            const confirmed = await Swal.fire({
                title: 'Naikkan Semua Kelas?',
                text: 'Siswa di kelas terpilih akan dinaikkan ke kelas baru secara otomatis.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Naikkan!',
            });

            if (! confirmed.isConfirmed) {
                return;
            }

            const body = new URLSearchParams();
            body.append('_token', this.token);
            checked.forEach((checkbox) => body.append(checkbox.name, checkbox.value));

            await this.kirim(this.urlSemua, body);
        },
    }"
    data-mode="{{ $type_selected ?? 'persiswa' }}"
    data-kelas="{{ $kelas_selected ?? '' }}"
    data-token="{{ csrf_token() }}"
    data-url-naik="{{ url('datakelas/naikkelas') }}"
    data-url-semua="{{ url('datakelas/naikkan_semua') }}"
    data-url-kenaikan="{{ url('datakelas/kenaikan') }}">
    <x-ui.page-header :title="$judul" icon="bi-box-arrow-up-right" tone="emerald"
        :subtitle="$subjudul" :crumbs="['Data Kelas', $subjudul]">
        <x-slot:actions>
            <a href="{{ url('datakelas') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-3.5 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card :title="$subjudul" icon="bi-info-circle" tone="blue" body-class="p-5">
        @if ($tpBefore)
            <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3.5 text-xs text-blue-900">
                <p class="mb-1.5 flex items-center gap-1.5 font-bold"><i class="bi bi-info-circle"></i> Fitur Kenaikan Kelas</p>
                <p class="mb-2 leading-relaxed">
                    Gunakan fitur ini untuk menaikkan siswa dari Tahun Pelajaran:
                    <b>{{ $tpBefore->tahun }}</b> ke <b>{{ $tp_active->tahun }}</b>.
                </p>
                <ul class="mb-0 list-disc space-y-1 pl-4 leading-relaxed">
                    <li>Pilih kelas asal dari TP sebelumnya.</li>
                    <li>Pilih mode kenaikan (Persiswa, Perkelas, atau Semua Kelas).</li>
                    <li>Siswa yang di rapor ditandai "Tidak Naik" akan tetap di kelas lamanya secara otomatis.</li>
                </ul>
            </div>

            <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="w-full lg:max-w-sm">
                    <label for="opsi-kelas-lama" class="mb-1.5 block text-xs font-bold text-slate-600">Kelas TP {{ $tpBefore->tahun }}</label>
                    <select id="opsi-kelas-lama" @change="gantiKelas($event.target.value)"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="" disabled {{ empty($kelas_selected) ? 'selected' : '' }}>Pilih Kelas Asal...</option>
                        @foreach ($kelas_lama as $id => $name)
                            <option value="{{ $id }}" {{ $id == $kelas_selected ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                @if (! empty($siswas) && ! empty($kelases))
                    <div>
                        <span class="mb-1.5 block text-xs font-bold text-slate-600">Mode Kenaikan</span>
                        <div class="inline-flex overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-xs">
                            @foreach (['persiswa' => 'Persiswa', 'perkelas' => 'Perkelas', 'allkelas' => 'Semua Kelas'] as $modeKey => $modeLabel)
                                <button type="button" @click="setMode('{{ $modeKey }}')"
                                    :class="mode === '{{ $modeKey }}' ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20' : 'text-slate-600 hover:bg-slate-100'"
                                    class="rounded-lg px-3.5 py-1.5 text-xs font-bold transition-colors">{{ $modeLabel }}</button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @isset($kelas_selected)
                <div class="pt-4">
                    <!-- Tab Persiswa -->
                    <div x-show="mode === 'persiswa'" x-cloak>
                        <x-ui.table id="tbl-siswa">
                            <x-slot:head>
                                <th class="w-14 px-4 py-3 text-center">No</th>
                                <th class="w-36 px-4 py-3 text-center">NIS / NISN</th>
                                <th class="px-4 py-3">Nama Siswa</th>
                                <th class="px-4 py-3 text-center">Status Rapor</th>
                                <th class="w-64 px-4 py-3 text-center">
                                    <span class="block">Target Kelas Baru</span>
                                    <select id="opsi-all-kelas" @change="setSemuaTarget($event.target.value)"
                                        class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[11px] font-semibold normal-case tracking-normal text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                        <option value="0">Set Semua...</option>
                                        @foreach ($kelases as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </th>
                            </x-slot:head>

                            @foreach ($siswas as $siswa)
                                @php
                                    $promoted = $siswa_kelas_baru[$siswa->id_siswa] ?? null;
                                    $isNaik = $siswa->naik ?? '1';
                                @endphp
                                <tr data-id="{{ $siswa->id_siswa }}" class="transition-colors hover:bg-slate-50/70">
                                    <td class="px-4 py-3 text-center text-xs font-semibold text-slate-400">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 text-center text-xs text-slate-500">{{ $siswa->nis }} / {{ $siswa->nisn }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-800">{{ $siswa->nama }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($isNaik == '1')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700">Naik</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-700">TIDAK NAIK</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($isNaik == '0')
                                            <input type="text" value="Tetap di {{ $siswa->nama_kelas }}" disabled
                                                class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-xs text-slate-500">
                                        @else
                                            <select class="sel-target-kelas w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                                <option value="0">Pilih Kelas...</option>
                                                @foreach ($kelases as $id => $name)
                                                    <option value="{{ $id }}" {{ ($promoted && $promoted->nama_kelas == $name) ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </x-ui.table>

                        <div class="mt-6 text-right">
                            <button type="button" @click="simpanPersiswa()"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm shadow-blue-500/20 transition-colors hover:bg-blue-700">
                                <i class="bi bi-save"></i> Simpan Kenaikan Persiswa
                            </button>
                        </div>
                    </div>

                    <!-- Tab Perkelas -->
                    <div x-show="mode === 'perkelas'" x-cloak>
                        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-900">
                            <i class="bi bi-exclamation-triangle mr-2"></i>
                            Fitur ini akan menaikkan <b>SELURUH SISWA</b> di kelas ini yang status rapor-nya "Naik"
                            ke kelas baru yang sama.
                        </div>

                        <div class="mt-4 flex flex-wrap items-end gap-4">
                            <div class="w-full sm:w-72">
                                <label for="bulk-target-kelas" class="mb-1.5 block text-xs font-bold text-slate-600">Naikkan Seluruh Siswa ke Kelas:</label>
                                <select id="bulk-target-kelas"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    <option value="0">Pilih Kelas Baru...</option>
                                    @foreach ($kelases as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" @click="simpanPerkelas()"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm shadow-blue-500/20 transition-colors hover:bg-blue-700">
                                <i class="bi bi-save"></i> Simpan Kenaikan Perkelas
                            </button>
                        </div>
                    </div>

                    <!-- Tab Semua Kelas -->
                    <div x-show="mode === 'allkelas'" x-cloak>
                        <form id="form-allkelas" @submit.prevent="simpanSemuaKelas()" class="overflow-hidden rounded-2xl border border-slate-200">
                            <x-ui.table>
                                <x-slot:head>
                                    <th class="w-14 px-4 py-3 text-center">No</th>
                                    <th class="px-4 py-3">Kelas TP {{ $tpBefore->tahun }}</th>
                                    <th class="px-4 py-3">Target Kelas TP {{ $tp_active->tahun }}</th>
                                    <th class="w-20 px-4 py-3 text-center">
                                        <span class="block">Pilih</span>
                                        <input type="checkbox" id="check-all-kelas" @change="toggleSemuaKelas($event.target.checked)"
                                            aria-label="Pilih semua kelas"
                                            class="mt-1 h-4 w-4 cursor-pointer rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    </th>
                                </x-slot:head>

                                @foreach ($kelas_lama as $id_lama => $name_lama)
                                    @php
                                        $id_baru = $all_kelas_baru[$id_lama] ?? '0';
                                        $name_baru = $kelases[$id_baru] ?? '-';
                                    @endphp
                                    <tr class="transition-colors hover:bg-slate-50/70">
                                        <td class="px-4 py-3 text-center text-xs font-semibold text-slate-400">{{ $loop->iteration }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $name_lama }}</td>
                                        <td class="px-4 py-3 font-bold {{ $name_baru === '-' ? 'text-rose-600' : 'text-slate-800' }}">
                                            {{ $name_baru !== '-' ? $name_baru : '?? (Level Tidak Sesuai)' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if ($id_baru != '0')
                                                <input type="checkbox" name="kelas_baru[{{ $id_lama }}]" value="{{ $id_baru }}"
                                                    class="check-per-kelas h-4 w-4 cursor-pointer rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                                    aria-label="Naikkan kelas {{ $name_lama }}">
                                            @else
                                                <i class="bi bi-x-circle text-red-500"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </x-ui.table>
                        </form>

                        <div class="mt-6 text-right">
                            <button type="button" @click="simpanSemuaKelas()"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm shadow-blue-500/20 transition-colors hover:bg-blue-700">
                                <i class="bi bi-save"></i> Simpan Semua Kelas Terpilih
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <div class="mt-4">
                    <x-ui.empty icon="bi-arrow-up-circle" title="Pilih kelas asal terlebih dahulu"
                        description="Silakan pilih kelas asal terlebih dahulu untuk memulai proses kenaikan." />
                </div>
            @endisset
        @else
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                <i class="bi bi-exclamation-triangle mr-2"></i>
                Tidak ada data Tahun Pelajaran sebelumnya. Proses kenaikan tidak dapat dilakukan.
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
