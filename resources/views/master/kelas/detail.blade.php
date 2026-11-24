@extends('layouts.main')

@section('content')
<div class="fade-in-up space-y-4 p-4 md:p-6">
    <x-ui.page-header :title="$judul ?? 'Detail Data'" icon="bi-door-open" tone="emerald"
        subtitle="{{ $subjudul }} {{ $kelas->nama_kelas ?? '-' }}"
        :crumbs="['Data Kelas', 'Detail']">
        <x-slot:actions>
            <a href="{{ url('datakelas') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-3.5 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-ui.card :title="'Informasi ' . ($kelas->nama_kelas ?? '-')" icon="bi-info-circle" tone="emerald" class="lg:col-span-1">
            <dl class="divide-y divide-slate-100 text-sm">
                <div class="flex items-center justify-between gap-3 py-2.5 first:pt-0">
                    <dt class="text-xs font-bold text-slate-500">Nama Kelas</dt>
                    <dd class="mb-0 font-semibold text-slate-800">{{ $kelas->nama_kelas ?? '-' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <dt class="text-xs font-bold text-slate-500">Kode Kelas</dt>
                    <dd class="mb-0 font-mono text-slate-700">{{ $kelas->kode_kelas ?? '-' }}</dd>
                </div>
                @if (($setting->jenjang ?? '') == '3')
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-xs font-bold text-slate-500">Jurusan</dt>
                        <dd class="mb-0 text-slate-700">{{ $kelas->nama_jurusan ?? '-' }}</dd>
                    </div>
                @endif
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <dt class="text-xs font-bold text-slate-500">Level Kelas</dt>
                    <dd class="mb-0 text-slate-700">{{ $kelas->level ?? '-' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 py-2.5">
                    <dt class="text-xs font-bold text-slate-500">Wali Kelas</dt>
                    <dd class="mb-0 text-right text-slate-700">{{ $kelas->nama_guru ?? '-' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 pt-2.5 last:pb-0">
                    <dt class="text-xs font-bold text-emerald-600">Jumlah Siswa</dt>
                    <dd class="mb-0 text-lg font-extrabold text-emerald-600">{{ count($siswas) }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Daftar Siswa" icon="bi-people" tone="blue" class="lg:col-span-2" body-class="p-0">
            <div class="max-h-[500px] overflow-y-auto">
                <x-ui.table>
                    <x-slot:head>
                        <th class="w-14 px-4 py-3 text-center">No</th>
                        <th class="w-40 px-4 py-3 text-center">NIS / NISN</th>
                        <th class="px-4 py-3">Nama Siswa</th>
                        <th class="w-20 px-4 py-3 text-center">L/P</th>
                    </x-slot:head>

                    @forelse ($siswas as $siswa)
                        <tr class="transition-colors hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-center text-xs font-semibold text-slate-400">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-bold text-slate-600">{{ $siswa->nis }}</span>
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-bold text-slate-600">{{ $siswa->nisn }}</span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $siswa->nama }}</td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $siswa->jenis_kelamin }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10">
                                <x-ui.empty icon="bi-emoji-frown" title="Belum ada siswa"
                                    description="Belum ada siswa yang terdaftar di kelas ini." />
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
            </div>
        </x-ui.card>

        <x-ui.card title="Struktur Organisasi" icon="bi-diagram-3" tone="violet" class="lg:col-span-3" body-class="p-0">
            <x-slot:meta>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700">
                    <i class="bi bi-check-circle"></i> Wali Kelas
                </span>
            </x-slot:meta>

            @php
                $arrJabatan = [
                    'wali_kelas' => 'Wali Kelas',
                    'ketua' => 'Ketua Kelas',
                    'wakil_ketua' => 'Wakil Ketua',
                    'sekretaris_1' => 'Sekretaris I',
                    'sekretaris_2' => 'Sekretaris II',
                    'bendahara_1' => 'Bendahara I',
                    'bendahara_2' => 'Bendahara II',
                    'sie_upacara' => 'Sie Upacara',
                    'sie_ekstrakurikuler' => 'Sie Ekstrakurikuler',
                    'sie_olahraga' => 'Sie Olahraga',
                    'sie_keagamaan' => 'Sie Keagamaan',
                    'sie_keamanan' => 'Sie Keamanan',
                    'sie_ketertiban' => 'Sie Ketertiban',
                    'sie_kebersihan' => 'Sie Kebersihan',
                    'sie_keindahan' => 'Sie Keindahan',
                    'sie_kesehatan' => 'Sie Kesehatan',
                    'sie_kekeluargaan' => 'Sie Kekeluargaan',
                    'sie_humas' => 'Sie Humas',
                ];
            @endphp

            <x-ui.table>
                <x-slot:head>
                    <th class="w-14 px-4 py-3 text-center">No</th>
                    <th class="px-4 py-3">Jabatan</th>
                    <th class="px-4 py-3">Nama / Petugas</th>
                </x-slot:head>

                @foreach ($arrJabatan as $key => $jabatan)
                    <tr class="transition-colors hover:bg-slate-50/70">
                        <td class="px-4 py-3 text-center text-xs font-semibold text-slate-400">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-500">{{ $jabatan }}</td>
                        <td class="px-4 py-3">
                            @if ($key === 'wali_kelas')
                                <span class="font-bold text-emerald-600">{{ $kelas->nama_guru ?? '-' }}</span>
                            @else
                                @php
                                    $petugas = collect($siswas)->firstWhere('id_siswa', $struktur->$key ?? null);
                                @endphp
                                @if ($petugas)
                                    <span class="text-slate-700">{{ $petugas->nama }}</span>
                                @else
                                    <span class="text-sm italic text-slate-400">-</span>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>
    </div>
</div>
@endsection
