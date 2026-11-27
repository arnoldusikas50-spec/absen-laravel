@extends('layouts.main')

@section('content')
@php
    $namaBulan = ['1' => 'Januari', '2' => 'Februari', '3' => 'Maret', '4' => 'April', '5' => 'Mei', '6' => 'Juni',
        '7' => 'Juli', '8' => 'Agustus', '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
    $bulanKey = ($bulan !== 'all' && is_numeric($bulan)) ? (string) (int) $bulan : 'all';
    $periodeLabel = ($bulanKey !== 'all' ? ($namaBulan[$bulanKey] ?? $bulan) : 'Semua Bulan')
        . ($tahun !== 'all' ? ' ' . $tahun : '');

    $avatarFallback = \App\Services\AvatarService::cadangan();

    $baris = [];
    foreach ($permits as $row) {
        // ── Nama guru
        $namaGuru = ! empty($row->nama_guru)
            ? $row->nama_guru
            : trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
        if (trim($namaGuru) === '') {
            $namaGuru = $row->username ?? '—';
        }

        // ── Status verifikasi
        $statusStr = strtolower(trim((string) ($row->status ?? 'pending')));
        if ($statusStr === 'approved' || $statusStr === '1') {
            $status = ['kode' => 'approved', 'label' => 'Disetujui', 'ikon' => 'bi-check-circle-fill', 'kelas' => 'bg-emerald-50 text-emerald-700'];
        } elseif ($statusStr === 'rejected' || $statusStr === '2') {
            $status = ['kode' => 'rejected', 'label' => 'Ditolak', 'ikon' => 'bi-x-circle-fill', 'kelas' => 'bg-rose-50 text-rose-700'];
        } else {
            $status = ['kode' => 'pending', 'label' => 'Menunggu', 'ikon' => 'bi-clock-history', 'kelas' => 'bg-amber-50 text-amber-700'];
        }

        // ── Jenis pengajuan (ijin → izin untuk tampilan)
        $jenisRaw = strtolower(trim((string) ($row->jenis ?? 'izin')));
        $jenisCat = ($jenisRaw === 'ijin') ? 'izin' : $jenisRaw;
        $jenis = match ($jenisCat) {
            'sakit' => ['kode' => 'sakit', 'label' => 'Sakit', 'ikon' => 'bi-heart-pulse-fill', 'kelas' => 'bg-rose-50 text-rose-700'],
            'cuti' => ['kode' => 'cuti', 'label' => 'Cuti', 'ikon' => 'bi-calendar2-range-fill', 'kelas' => 'bg-amber-50 text-amber-700'],
            'dinas' => ['kode' => 'dinas', 'label' => 'Dinas', 'ikon' => 'bi-briefcase-fill', 'kelas' => 'bg-violet-50 text-violet-700'],
            default => ['kode' => 'izin', 'label' => 'Izin', 'ikon' => 'bi-file-earmark-text', 'kelas' => 'bg-sky-50 text-sky-700'],
        };

        // ── Kategori cuti (Tahunan/Besar/Melahirkan/CAP) bila valid
        $kategoriCuti = '';
        if ($jenisCat === 'cuti'
            && ! empty($row->kategori_cuti)
            && \App\Services\CutiKategoriService::valid((string) $row->kategori_cuti)) {
            $kategoriCuti = \App\Services\CutiKategoriService::label((string) $row->kategori_cuti);
        }

        // ── Durasi (hari kalender)
        $durasi = 1;
        if (! empty($row->tgl_mulai) && ! empty($row->tgl_selesai)) {
            $durasi = max(1, (int) round((strtotime($row->tgl_selesai) - strtotime($row->tgl_mulai)) / 86400) + 1);
        }

        // ── Lampiran bukti
        $buktiUrl = null;
        $buktiRaw = trim((string) ($row->bukti ?? ''));
        if ($buktiRaw !== '') {
            if (preg_match('#^https?://#i', $buktiRaw)) {
                $buktiUrl = $buktiRaw;
            } elseif (file_exists(public_path() . '/' . ltrim($buktiRaw, '/'))) {
                $buktiUrl = url(ltrim($buktiRaw, '/'));
            } elseif (file_exists(public_path() . '/uploads/izin/' . basename($buktiRaw))) {
                $buktiUrl = url('uploads/izin/' . basename($buktiRaw));
            } elseif (file_exists(public_path() . '/uploads/surat_izin/' . basename($buktiRaw))) {
                $buktiUrl = url('uploads/surat_izin/' . basename($buktiRaw));
            } else {
                $buktiUrl = url('uploads/izin/' . basename($buktiRaw));
            }
        }

        $verifikatorNama = trim(($row->verifier_first ?? '') . ' ' . ($row->verifier_last ?? ''));

        $baris[] = [
            'id' => (int) $row->id,
            'cari' => mb_strtolower($namaGuru . ' ' . ($row->nip ?? '') . ' ' . ($row->keterangan ?? '')),
            'nama' => $namaGuru,
            'nip' => (string) ($row->nip ?? ''),
            'avatar' => \App\Services\AvatarService::tampilan((string) ($row->foto ?? ''), $namaGuru, '#4338ca'),
            'avatarFallback' => $avatarFallback,
            'jenis' => $jenis,
            'kategori' => $kategoriCuti,
            'tgl' => [
                'mulai' => date('d/m/Y', strtotime($row->tgl_mulai)),
                'selesai' => date('d/m/Y', strtotime($row->tgl_selesai ?? $row->tgl_mulai)),
                'sama' => $row->tgl_mulai === ($row->tgl_selesai ?? $row->tgl_mulai),
                'durasi' => $durasi,
            ],
            'keterangan' => (string) ($row->keterangan ?? ''),
            'catatan' => (string) ($row->verifikasi_catatan ?? ''),
            'bukti' => $buktiUrl,
            'status' => $status,
            'verifikator' => $verifikatorNama === '' ? null : [
                'nama' => $verifikatorNama,
                'waktu' => ! empty($row->verified_at) ? date('d/m/Y H:i', strtotime($row->verified_at)) : '',
            ],
        ];
    }

    $chips = [
        ['kode' => 'all', 'label' => 'Semua', 'jumlah' => $stats['total'], 'ikon' => null, 'titik' => null],
        ['kode' => 'pending', 'label' => 'Menunggu', 'jumlah' => $stats['pending'], 'ikon' => null, 'titik' => 'bg-amber-500'],
        ['kode' => 'approved', 'label' => 'Disetujui', 'jumlah' => $stats['approved'], 'ikon' => 'bi-check-circle-fill text-emerald-500', 'titik' => null],
        ['kode' => 'rejected', 'label' => 'Ditolak', 'jumlah' => $stats['rejected'], 'ikon' => 'bi-x-circle-fill text-rose-400', 'titik' => null],
        ['kode' => 'izin', 'label' => 'Izin', 'jumlah' => $stats['izin'], 'ikon' => null, 'titik' => 'bg-sky-500'],
        ['kode' => 'sakit', 'label' => 'Sakit', 'jumlah' => $stats['sakit'], 'ikon' => null, 'titik' => 'bg-rose-500'],
        ['kode' => 'cuti', 'label' => 'Cuti', 'jumlah' => $stats['cuti'], 'ikon' => null, 'titik' => 'bg-amber-500'],
        ['kode' => 'dinas', 'label' => 'Dinas', 'jumlah' => $stats['dinas'], 'ikon' => null, 'titik' => 'bg-violet-500'],
    ];

    $jenisOpts = [
        'izin' => ['ikon' => 'bi-file-earmark-text', 'warna' => 'text-sky-600', 'bg' => 'bg-sky-50', 'label' => 'Izin', 'sub' => 'Keperluan pribadi'],
        'sakit' => ['ikon' => 'bi-heart-pulse-fill', 'warna' => 'text-rose-500', 'bg' => 'bg-rose-50', 'label' => 'Sakit', 'sub' => 'Surat keterangan dokter'],
        'cuti' => ['ikon' => 'bi-calendar2-range-fill', 'warna' => 'text-amber-500', 'bg' => 'bg-amber-50', 'label' => 'Cuti', 'sub' => 'Cuti tahunan / bersalin'],
        'dinas' => ['ikon' => 'bi-briefcase-fill', 'warna' => 'text-violet-600', 'bg' => 'bg-violet-50', 'label' => 'Dinas Luar', 'sub' => 'Tugas / perjalanan dinas'],
    ];

    $config = [
        'rows' => $baris,
        'urls' => [
            'simpanManual' => url('admin/presensi/izin/save-manual'),
            'approve' => url('admin/presensi/izin/approve'),
            'reject' => url('admin/presensi/izin/reject'),
            'hapus' => url('admin/presensi/izin/delete'),
        ],
        'csrf' => csrf_token(),
    ];
@endphp

<div class="fade-in-up space-y-5 px-4 pb-6 pt-4 md:px-6 md:pt-6" x-data="permitsPage(@js($config))">

    <x-ui.page-header title="Kelola &amp; Verifikasi Izin / Cuti" icon="bi-file-earmark-medical-fill" tone="blue"
        subtitle="Verifikasi pengajuan izin, sakit, cuti, dan dinas luar guru."
        :crumbs="['Presensi', 'Izin & Cuti']">
        <x-slot:actions>
            <button type="button" @click="bukaManual()"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm shadow-blue-500/20 transition-colors hover:bg-blue-700">
                <i class="bi bi-plus-circle-fill"></i> Input Izin Manual
            </button>
            <a href="{{ url('admin/presensi') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:border-slate-300 hover:bg-slate-50">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card title="Daftar Pengajuan Izin &amp; Cuti" icon="bi-clipboard2-check" bodyClass="p-0">
        <x-slot:actions>
            <form method="get" class="flex flex-wrap items-center gap-2">
                <select name="bulan" @change="$el.form.submit()" aria-label="Filter bulan"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 outline-none transition-colors hover:border-slate-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-200">
                    <option value="all" {{ $bulanKey === 'all' ? 'selected' : '' }}>Semua Bulan</option>
                    @foreach ($namaBulan as $num => $nama)
                        <option value="{{ $num }}" {{ (string) $bulanKey === (string) $num ? 'selected' : '' }}>{{ $nama }}</option>
                    @endforeach
                </select>
                <select name="tahun" @change="$el.form.submit()" aria-label="Filter tahun"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 outline-none transition-colors hover:border-slate-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-200">
                    <option value="all" {{ $tahun === 'all' ? 'selected' : '' }}>Semua</option>
                    @for ($y = date('Y'); $y >= attendance_tahun_tertua(); $y--)
                        <option value="{{ $y }}" {{ (string) $tahun === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700"
                    title="Terapkan filter periode">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </x-slot:actions>

        <x-slot:toolbar>
            <div class="flex flex-wrap items-center gap-1.5">
                @foreach ($chips as $chip)
                    <button type="button" @click="filter = '{{ $chip['kode'] }}'"
                        :class="filter === '{{ $chip['kode'] }}'
                            ? 'border-blue-600 bg-blue-600 text-white'
                            : 'border-slate-200 bg-white text-slate-500 hover:border-blue-500 hover:bg-blue-50 hover:text-blue-600'"
                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border-[1.5px] px-3 py-1.5 text-xs font-semibold transition-colors">
                        @if ($chip['titik'])
                            <span class="h-2 w-2 shrink-0 rounded-full {{ $chip['titik'] }}"></span>
                        @endif
                        @if ($chip['ikon'])
                            <i class="bi {{ $chip['ikon'] }} text-[10px]"></i>
                        @endif
                        {{ $chip['label'] }}
                        <span class="rounded-full px-1.5 py-0.5 text-[10px] font-extrabold"
                            :class="filter === '{{ $chip['kode'] }}' ? 'bg-white/25 text-white' : 'bg-slate-100 text-slate-500'">{{ $chip['jumlah'] }}</span>
                    </button>
                @endforeach

                <div class="relative ml-0 min-w-[200px] md:ml-auto">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                    <input type="text" x-model="q" x-ref="cari" placeholder="Cari nama / NIP..." autocomplete="off"
                        class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-8 pr-8 text-xs font-semibold text-slate-800 outline-none transition-colors hover:border-slate-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-200">
                    <button type="button" x-show="q !== ''" x-cloak @click="q = ''; $refs.cari.focus()"
                        class="absolute right-2 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                        title="Bersihkan pencarian">
                        <i class="bi bi-x text-xs"></i>
                    </button>
                </div>
            </div>
        </x-slot:toolbar>

        <div class="max-h-[62vh] overflow-auto" tabindex="0">
            <table class="w-full text-[13px]">
                <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="w-10 px-3 py-3 text-center font-bold">#</th>
                        <th class="min-w-[200px] px-3 py-3 text-left font-bold">Guru / Pegawai</th>
                        <th class="w-[92px] px-3 py-3 text-center font-bold">Jenis</th>
                        <th class="min-w-[190px] px-3 py-3 text-left font-bold">Rentang Tanggal</th>
                        <th class="min-w-[200px] px-3 py-3 text-left font-bold">Keterangan</th>
                        <th class="w-[96px] px-3 py-3 text-center font-bold">Bukti</th>
                        <th class="w-[112px] px-3 py-3 text-center font-bold">Status</th>
                        <th class="min-w-[150px] px-3 py-3 text-left font-bold">Verifikator</th>
                        <th class="min-w-[180px] px-3 py-3 text-center font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr x-show="!baris.length">
                        <td colspan="9" class="px-3 py-12 text-center text-slate-400">
                            <i class="bi bi-inbox mb-2 block text-4xl"></i>
                            <p class="text-sm font-semibold text-slate-500"
                                x-text="rows.length ? 'Tidak ada data yang cocok dengan filter' : 'Belum ada data pengajuan pada periode ini'"></p>
                            <p class="text-xs" x-text="rows.length ? 'Coba ubah kata kunci atau filter status / jenis' : 'Periode: {{ $periodeLabel }}'"></p>
                            <button type="button" x-show="!rows.length" @click="bukaManual()"
                                class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 transition-colors hover:border-blue-300 hover:bg-blue-100">
                                <i class="bi bi-plus"></i> Input Izin Manual
                            </button>
                        </td>
                    </tr>

                    <template x-for="(row, index) in baris" :key="row.id">
                        <tr class="transition-colors hover:bg-slate-50/60">
                            <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400" x-text="index + 1"></td>

                            <td class="px-3 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <img :src="row.avatar" :alt="row.nama" loading="lazy"
                                        x-on:error="$el.src = row.avatarFallback"
                                        class="h-9 w-9 shrink-0 rounded-full bg-slate-100 object-cover ring-1 ring-slate-200">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold leading-tight text-slate-800" :title="row.nama" x-text="row.nama"></p>
                                        <p class="truncate text-[11px] tabular-nums text-slate-400">
                                            NIP: <span x-text="row.nip || '—'"></span>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold" :class="row.jenis.kelas">
                                    <i class="bi text-[10px]" :class="row.jenis.ikon"></i>
                                    <span x-text="row.jenis.label"></span>
                                </span>
                                <div x-show="row.kategori" class="mt-1">
                                    <span class="whitespace-nowrap rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600" :title="row.kategori" x-text="row.kategori"></span>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-3 py-2.5">
                                <div class="flex items-center gap-1.5 text-sm">
                                    <span class="font-semibold tabular-nums text-slate-800" x-text="row.tgl.mulai"></span>
                                    <template x-if="!row.tgl.sama">
                                        <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-slate-400">s.d.</span>
                                    </template>
                                    <template x-if="!row.tgl.sama">
                                        <span class="font-semibold tabular-nums text-slate-800" x-text="row.tgl.selesai"></span>
                                    </template>
                                </div>
                                <span class="mt-1 inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">
                                    <i class="bi bi-calendar3 text-[9px]"></i>
                                    <span x-text="row.tgl.durasi + ' hari'"></span>
                                </span>
                            </td>

                            <td class="max-w-[220px] px-3 py-2.5">
                                <p class="line-clamp-2 text-[13px] leading-snug text-slate-700" :title="row.keterangan" x-text="row.keterangan || '—'"></p>
                                <p x-show="row.catatan" class="mt-0.5 truncate text-xs text-rose-500" :title="row.catatan">
                                    <i class="bi bi-chat-left-quote mr-1"></i><span x-text="row.catatan"></span>
                                </p>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <template x-if="row.bukti">
                                    <button type="button" @click="bukaBukti(row)"
                                        class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-bold text-slate-600 transition-colors hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                                        title="Lihat lampiran bukti">
                                        <i class="bi bi-paperclip text-blue-600"></i> Bukti
                                    </button>
                                </template>
                                <template x-if="!row.bukti">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50 text-slate-300" title="Tanpa lampiran">
                                        <i class="bi bi-dash"></i>
                                    </span>
                                </template>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold" :class="row.status.kelas">
                                    <span x-show="row.status.kode === 'pending'" class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"></span>
                                    <i x-show="row.status.kode !== 'pending'" class="bi text-[10px]" :class="row.status.ikon"></i>
                                    <span x-text="row.status.label"></span>
                                </span>
                            </td>

                            <td class="px-3 py-2.5">
                                <template x-if="row.verifikator">
                                    <div>
                                        <p class="truncate text-[13px] font-semibold leading-tight text-slate-700" :title="row.verifikator.nama">
                                            <i class="bi bi-person-check mr-1 text-emerald-500"></i><span x-text="row.verifikator.nama"></span>
                                        </p>
                                        <p x-show="row.verifikator.waktu" class="mt-0.5 whitespace-nowrap text-[11px] text-slate-400">
                                            <i class="bi bi-clock mr-0.5 text-[9px]"></i><span x-text="row.verifikator.waktu"></span>
                                        </p>
                                    </div>
                                </template>
                                <span x-show="!row.verifikator" class="text-xs text-slate-300">—</span>
                            </td>

                            <td class="px-3 py-2.5 text-center">
                                <div class="inline-flex items-center justify-center gap-1.5">
                                    <template x-if="row.status.kode === 'pending'">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button type="button" @click="setujui(row)"
                                                class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg bg-emerald-600 px-2.5 py-1 text-[11px] font-bold text-white transition-colors hover:bg-emerald-700"
                                                title="Setujui pengajuan">
                                                <i class="bi bi-check-lg"></i> Setujui
                                            </button>
                                            <button type="button" @click="bukaTolak(row)"
                                                class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-[11px] font-bold text-rose-600 transition-colors hover:border-rose-300 hover:bg-rose-50"
                                                title="Tolak pengajuan">
                                                <i class="bi bi-x-lg"></i> Tolak
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="row.status.kode !== 'pending'">
                                        <button type="button" @click="hapus(row)"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-rose-500 transition-colors hover:border-rose-300 hover:bg-rose-50"
                                            title="Hapus data">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span x-text="'Menampilkan ' + baris.length + ' dari ' + rows.length + ' data'"></span>
                <span>Periode: {{ $periodeLabel }}</span>
            </div>
        </x-slot:footer>
    </x-ui.card>
</div>


{{-- ══════════ MODAL: INPUT IZIN MANUAL ══════════ --}}
<x-ui.modal id="manualPermitModal" title="Input Izin / Cuti Manual" icon="bi-file-earmark-plus-fill" tone="blue" size="max-w-xl">
    <form x-ref="manualForm" @submit.prevent="simpanManual()" class="flex min-h-0 flex-col">
        @csrf
        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
            <div>
                <label for="manualUserId" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                    Guru / Pegawai <span class="text-rose-500">*</span>
                </label>
                <select name="user_id" id="manualUserId" x-model="manual.user_id" required
                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none transition-colors hover:border-slate-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-200">
                    <option value="">— Pilih Guru —</option>
                    @foreach ($teachers as $tc)
                        @php
                            $namaPengajar = $tc->nama_guru ?: trim(($tc->first_name ?? '') . ' ' . ($tc->last_name ?? ''));
                        @endphp
                        <option value="{{ $tc->user_id }}">{{ $namaPengajar }}{{ ! empty($tc->nip) ? ' · ' . $tc->nip : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                    Jenis Pengajuan <span class="text-rose-500">*</span>
                </span>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($jenisOpts as $nilai => $opt)
                        <label class="block cursor-pointer">
                            <input type="radio" name="jenis" value="{{ $nilai }}" class="sr-only" x-model="manual.jenis">
                            <span class="flex items-center gap-2.5 rounded-lg border-2 p-2.5 transition-colors"
                                :class="manual.jenis === '{{ $nilai }}' ? 'border-blue-500 bg-blue-50' : 'border-slate-200 hover:border-blue-300'">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $opt['bg'] }}">
                                    <i class="bi {{ $opt['ikon'] }} {{ $opt['warna'] }} text-sm"></i>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold leading-tight text-slate-800">{{ $opt['label'] }}</span>
                                    <span class="block text-xs text-slate-400">{{ $opt['sub'] }}</span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="manualTglMulai" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        Tanggal Mulai <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="tgl_mulai" id="manualTglMulai" x-model="manual.tgl_mulai" required
                        class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none transition-colors hover:border-slate-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-200">
                </div>
                <div>
                    <label for="manualTglSelesai" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                        Tanggal Selesai <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="tgl_selesai" id="manualTglSelesai" x-model="manual.tgl_selesai" required
                        class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none transition-colors hover:border-slate-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-200">
                </div>
            </div>
            <p class="-mt-2 text-xs text-slate-400" x-text="durasiManual"></p>

            <div>
                <label for="manualKeterangan" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Keterangan / Alasan</label>
                <textarea name="keterangan" id="manualKeterangan" rows="2" x-model="manual.keterangan"
                    placeholder="Keperluan keluarga / diagnosa dokter / surat tugas..."
                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none transition-colors hover:border-slate-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-200"></textarea>
            </div>

            <div>
                <label for="manualBukti" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                    Lampiran Dokumen <span class="font-normal text-slate-400">(opsional)</span>
                </label>
                <input type="file" name="bukti" id="manualBukti" accept="image/jpeg,image/png,application/pdf"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-slate-600">
                <p class="mt-1 text-xs text-slate-400">PDF/JPG/PNG · maks. 2 MB</p>
            </div>

            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3.5">
                <input type="checkbox" name="langsung_approve" value="1" x-model="manual.langsung_approve"
                    class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span>
                    <span class="block text-sm font-bold leading-tight text-emerald-700">
                        <i class="bi bi-check2-all mr-1"></i>Langsung Setujui &amp; Update Rekap Absensi
                    </span>
                    <span class="mt-0.5 block text-xs text-emerald-600">
                        Absensi guru pada rentang tanggal tersebut otomatis tercatat dan poin kedisiplinan dihitung.
                        Hari libur mingguan &amp; nasional akan dilewati.
                    </span>
                </span>
            </label>
        </div>

        <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3">
            <button type="button" data-ui-dismiss="modal"
                class="rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 transition-colors hover:border-slate-300 hover:bg-slate-50">
                Batal
            </button>
            <button type="submit" :disabled="memproses"
                class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700 disabled:opacity-60">
                <i class="bi" :class="memproses ? 'bi-arrow-repeat animate-spin' : 'bi-check2-circle'"></i>
                <span x-text="memproses ? 'Menyimpan...' : 'Simpan Izin'"></span>
            </button>
        </div>
    </form>
</x-ui.modal>


{{-- ══════════ MODAL: TOLAK PENGAJUAN ══════════ --}}
<x-ui.modal id="rejectModal" title="Tolak Pengajuan Izin" icon="bi-x-circle-fill" tone="rose" size="max-w-md">
    <form @submit.prevent="kirimTolak()" class="flex min-h-0 flex-col">
        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
            <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <i class="bi bi-person-circle shrink-0 text-xl text-slate-400"></i>
                <div>
                    <p class="text-sm font-semibold text-slate-800" x-text="tolak.nama || '—'"></p>
                    <p class="text-xs text-slate-400">Konfirmasi penolakan pengajuan izin</p>
                </div>
            </div>
            <div>
                <label for="tolakAlasan" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                    Alasan Penolakan <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="tolakAlasan" x-ref="tolakAlasan" x-model="tolak.alasan" required
                    placeholder="cth: Bukti surat tidak valid / Sudah ada jadwal piket"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none transition-colors hover:border-slate-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-200">
                <p class="mt-1 text-xs text-slate-400">Alasan ini akan terlihat oleh guru</p>
            </div>
        </div>
        <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3">
            <button type="button" data-ui-dismiss="modal"
                class="rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 transition-colors hover:border-slate-300 hover:bg-slate-50">
                Batal
            </button>
            <button type="submit" :disabled="memproses"
                class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-rose-700 disabled:opacity-60">
                <i class="bi bi-x-circle-fill"></i> Tolak Pengajuan
            </button>
        </div>
    </form>
</x-ui.modal>


{{-- ══════════ MODAL: PREVIEW BUKTI ══════════ --}}
<x-ui.modal id="buktiModal" size="max-w-2xl">
    <x-slot:header>
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
            <h2 class="flex min-w-0 items-center gap-2 text-sm font-bold text-slate-800">
                <i class="bi bi-paperclip text-blue-600"></i>
                <span class="truncate">Bukti: <span x-text="bukti.nama"></span></span>
            </h2>
            <button type="button" data-ui-dismiss="modal" aria-label="Tutup"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>
    </x-slot:header>

    <div class="min-h-[200px] bg-slate-50 p-5 text-center">
        <img x-show="bukti.gambar && !bukti.gagal" :src="bukti.url" alt="Lampiran bukti"
            x-on:error="bukti.gagal = true"
            class="mx-auto h-auto max-h-[65vh] max-w-full rounded-xl shadow-sm">

        <div x-show="!bukti.url || bukti.gagal" class="py-8">
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white text-2xl text-slate-400">
                <i class="bi bi-image"></i>
            </div>
            <p class="mb-1 text-sm font-bold text-slate-600">Foto tidak tersedia</p>
            <p class="text-xs text-slate-400">Berkas lampiran mungkin sudah dihapus atau gagal dimuat dari server.</p>
        </div>

        <div x-show="bukti.url && !bukti.gambar" class="py-8">
            <i class="bi bi-file-earmark-pdf mb-3 block text-5xl text-rose-500"></i>
            <p class="mb-4 text-sm font-medium text-slate-600">Dokumen PDF / File Lampiran</p>
            <a :href="bukti.url" target="_blank" rel="noopener"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white no-underline transition-colors hover:bg-blue-700">
                <i class="bi bi-download"></i> Buka / Unduh Dokumen
            </a>
        </div>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-3">
        <a :href="bukti.url || '#'" target="_blank" rel="noopener"
            class="inline-flex items-center gap-1.5 rounded-xl border border-blue-200 bg-white px-3 py-2 text-xs font-bold text-blue-700 no-underline transition-colors hover:bg-blue-50">
            <i class="bi bi-box-arrow-up-right"></i> Buka di Tab Baru
        </a>
        <button type="button" data-ui-dismiss="modal"
            class="rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-200">
            Tutup
        </button>
    </div>
</x-ui.modal>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('permitsPage', (config = {}) => ({
        rows: config.rows || [],
        urls: config.urls || {},
        csrf: config.csrf || '',
        filter: 'all',
        q: '',
        memproses: false,
        manual: {
            user_id: '',
            jenis: 'izin',
            tgl_mulai: '',
            tgl_selesai: '',
            keterangan: '',
            langsung_approve: true,
        },
        tolak: { id: null, nama: '', alasan: '' },
        bukti: { url: '', nama: '', gambar: false, gagal: false },

        get baris() {
            const kata = this.q.trim().toLowerCase();

            return this.rows.filter((row) => {
                if (!this.cocokFilter(row)) {
                    return false;
                }

                return kata === '' || row.cari.includes(kata);
            });
        },

        get durasiManual() {
            const mulai = this.manual.tgl_mulai;
            const selesai = this.manual.tgl_selesai;

            if (!mulai || !selesai) {
                return 'Durasi: 1 hari kerja (libur otomatis dilewati)';
            }

            const hari = Math.round((new Date(selesai) - new Date(mulai)) / 86400000) + 1;

            return 'Durasi: ' + Math.max(1, hari) + ' hari kalender (hari libur otomatis dilewati)';
        },

        cocokFilter(row) {
            if (this.filter === 'all') {
                return true;
            }

            if (['pending', 'approved', 'rejected'].includes(this.filter)) {
                return row.status.kode === this.filter;
            }

            return row.jenis.kode === this.filter;
        },

        hariIni() {
            return new Date().toLocaleDateString('en-CA');
        },

        buka(id) {
            this.$store.modals.open('#' + id);
        },

        tutup(id) {
            this.$store.modals.close('#' + id);
        },

        bukaManual() {
            const hariIni = this.hariIni();

            this.manual = {
                user_id: '',
                jenis: 'izin',
                tgl_mulai: hariIni,
                tgl_selesai: hariIni,
                keterangan: '',
                langsung_approve: true,
            };

            if (this.$refs.manualForm) {
                this.$refs.manualForm.reset();
                const berkas = this.$refs.manualForm.querySelector('input[type="file"]');

                if (berkas) {
                    berkas.value = '';
                }
            }

            this.buka('manualPermitModal');
        },

        bukaTolak(row) {
            this.tolak = { id: row.id, nama: row.nama, alasan: '' };
            this.buka('rejectModal');
            this.$nextTick(() => this.$refs.tolakAlasan?.focus());
        },

        bukaBukti(row) {
            this.bukti = {
                url: row.bukti || '',
                nama: row.nama,
                gambar: /\.(jpe?g|png|gif|webp)(\?.*)?$/i.test(row.bukti || ''),
                gagal: false,
            };

            this.buka('buktiModal');
        },

        async kirim(url, isi) {
            const opsi = {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrf,
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            };

            if (isi instanceof FormData) {
                opsi.body = isi;
            } else {
                opsi.headers['Content-Type'] = 'application/json';
                opsi.body = JSON.stringify(isi || {});
            }

            try {
                const respon = await fetch(url, opsi);
                const data = await respon.json().catch(() => ({}));

                return {
                    ok: respon.ok && data.status !== false,
                    status: respon.status,
                    pesan: data.message || 'Terjadi kesalahan pada server',
                };
            } catch (galat) {
                return { ok: false, status: 0, pesan: 'Kesalahan koneksi server' };
            }
        },

        konfirmasi(judul, teks, label, warna) {
            return Swal.fire({
                title: judul,
                text: teks,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: warna || '#2563eb',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: label || 'Ya',
                cancelButtonText: 'Batal',
            }).then((hasil) => hasil.isConfirmed);
        },

        berhasil(judul, pesan) {
            Swal.fire({ icon: 'success', title: judul, text: pesan, timer: 1600, showConfirmButton: false })
                .then(() => location.reload());
        },

        async simpanManual() {
            if (this.memproses) {
                return;
            }

            this.memproses = true;

            try {
                let paksa = false;

                for (;;) {
                    const data = new FormData(this.$refs.manualForm);

                    if (paksa) {
                        data.set('force', '1');
                    }

                    const hasil = await this.kirim(this.urls.simpanManual, data);

                    if (hasil.ok) {
                        this.tutup('manualPermitModal');
                        this.berhasil('Berhasil!', hasil.pesan);

                        return;
                    }

                    if (hasil.status === 409 && !paksa) {
                        const lanjut = await this.konfirmasi(
                            'Tetap simpan?',
                            hasil.pesan.replace(' Ulangi dengan force=1 bila memang disengaja.', ''),
                            'Ya, tetap simpan',
                            '#059669',
                        );

                        if (!lanjut) {
                            return;
                        }

                        paksa = true;

                        continue;
                    }

                    Swal.fire('Gagal', hasil.pesan, 'error');

                    return;
                }
            } finally {
                this.memproses = false;
            }
        },

        async setujui(row) {
            if (this.memproses) {
                return;
            }

            const lanjut = await this.konfirmasi(
                'Setujui Pengajuan?',
                'Pengajuan ' + row.nama + ' akan disetujui dan absensi pada rentang tanggal tersebut otomatis diperbarui.',
                'Ya, Setujui',
                '#059669',
            );

            if (!lanjut) {
                return;
            }

            this.memproses = true;

            try {
                const hasil = await this.kirim(this.urls.approve + '/' + encodeURIComponent(row.id), {});

                if (!hasil.ok) {
                    Swal.fire('Gagal', hasil.pesan, 'error');

                    return;
                }

                this.berhasil('Disetujui!', hasil.pesan);
            } finally {
                this.memproses = false;
            }
        },

        async kirimTolak() {
            if (this.memproses) {
                return;
            }

            const alasan = this.tolak.alasan.trim();

            if (alasan === '') {
                Swal.fire('Perhatian', 'Alasan penolakan wajib diisi', 'warning');

                return;
            }

            this.memproses = true;

            try {
                const hasil = await this.kirim(this.urls.reject + '/' + encodeURIComponent(this.tolak.id), { alasan });

                if (!hasil.ok) {
                    Swal.fire('Gagal', hasil.pesan, 'error');

                    return;
                }

                this.tutup('rejectModal');
                this.berhasil('Ditolak!', hasil.pesan);
            } finally {
                this.memproses = false;
            }
        },

        async hapus(row) {
            if (this.memproses) {
                return;
            }

            const lanjut = await this.konfirmasi(
                'Hapus Data Izin?',
                'Pengajuan ' + row.nama + ' akan dihapus permanen.',
                'Ya, Hapus',
                '#dc2626',
            );

            if (!lanjut) {
                return;
            }

            this.memproses = true;

            try {
                let hasil = await this.kirim(this.urls.hapus + '/' + encodeURIComponent(row.id), {});

                // 409 = izin sudah approved; hapus ulang dengan force agar baris
                // absensi yang ditulis pengajuan ini ikut direvert server.
                if (!hasil.ok && hasil.status === 409) {
                    const paksa = await this.konfirmasi(
                        'Izin Sudah Disetujui',
                        hasil.pesan + ' Baris absensi yang terkait akan otomatis direvert.',
                        'Ya, Hapus + Revert',
                        '#dc2626',
                    );

                    if (!paksa) {
                        return;
                    }

                    hasil = await this.kirim(this.urls.hapus + '/' + encodeURIComponent(row.id), { force: '1' });
                }

                if (!hasil.ok) {
                    Swal.fire('Gagal', hasil.pesan, 'error');

                    return;
                }

                this.berhasil('Terhapus', hasil.pesan);
            } finally {
                this.memproses = false;
            }
        },
    }));
});
</script>
@endsection
