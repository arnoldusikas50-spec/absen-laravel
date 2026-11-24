@extends('layouts.main')

@section('content')

<?php
$leaderboardRows = $leaderboard ?? [];
$kategoriRows = $kategori ?? [];
$kelasRows = $kelas_list ?? [];

$pageConfig = [
    'csrf' => csrf_token(),
    'bulan' => (int) date('n'),
    'tahun' => (int) date('Y'),
    'urls' => [
        'record' => url('admin/poin-siswa/record'),
        'kategoriSave' => url('admin/poin-siswa/kategori/save'),
        'kategoriDelete' => url('admin/poin-siswa/kategori/delete'),
        'history' => route('admin.poin-siswa.history.get_history', ['id' => '__ID__']),
        'sync' => url('admin/poin-siswa/sync'),
    ],
];
?>

<div x-data="poinSiswaPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <!-- Content Header -->
    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'Poin Disiplin Siswa'"
            icon="bi-exclamation-diamond-fill"
            tone="rose"
            :subtitle="'Skor awal 100 · pelanggaran mengurangi · penghargaan menambah · TP ' . ($tp_active->tahun ?? '-') . ' ' . ($smt_active->smt ?? '')"
            :crumbs="['Disiplin', 'Poin Siswa']">
            <x-slot:actions>
                <button type="button" @click="$store.modals.open('#modalSync')"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 shadow-xs transition-colors hover:bg-slate-50">
                    <i class="bi bi-arrow-repeat"></i> Sinkron Presensi
                </button>
                <button type="button" @click="openRecord()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                    <i class="bi bi-plus-lg"></i> Catat Poin
                </button>
            </x-slot:actions>
        </x-ui.page-header>
    </div>

    <div class="mt-4 space-y-4 px-4 pb-6 md:px-6">

        <!-- Statistik -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <div class="text-[11px] font-bold text-slate-400 uppercase">Total Siswa</div>
                <div class="text-2xl font-extrabold text-slate-900">{{ number_format($stats['total_siswa'] ?? 0) }}</div>
                <div class="text-[11px] text-slate-400">Terdata di leaderboard</div>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <div class="text-[11px] font-bold text-slate-400 uppercase">Rata-rata Skor</div>
                <div class="text-2xl font-extrabold text-blue-700">{{ number_format($stats['rata_rata'] ?? 100, 1) }}</div>
                <div class="text-[11px] text-slate-400">Dari 100 poin</div>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <div class="text-[11px] font-bold text-slate-400 uppercase">Skor &lt; 75</div>
                <div class="text-2xl font-extrabold text-rose-600">{{ number_format($stats['di_bawah_75'] ?? 0) }}</div>
                <div class="text-[11px] text-slate-400">Perlu pembinaan</div>
            </div>
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <div class="text-[11px] font-bold text-slate-400 uppercase">Kasus Bulan Ini</div>
                <div class="text-2xl font-extrabold text-amber-600">{{ number_format($stats['kasus_bulan_ini'] ?? 0) }}</div>
                <div class="text-[11px] text-slate-400">{{ date('F Y') }}</div>
            </div>
        </div>

        <!-- Kartu utama: tab leaderboard / kategori -->
        <x-ui.card bodyClass="p-0">
            <x-slot:toolbar>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1 rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Tab poin siswa">
                        <button type="button" role="tab" :aria-selected="tab === 'leaderboard' ? 'true' : 'false'"
                            @click="tab = 'leaderboard'"
                            :class="tab === 'leaderboard' ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-500 hover:text-slate-700'"
                            class="rounded-lg px-4 py-2 text-xs font-bold whitespace-nowrap transition-colors">
                            <i class="bi bi-trophy"></i> Leaderboard
                        </button>
                        <button type="button" role="tab" :aria-selected="tab === 'kategori' ? 'true' : 'false'"
                            @click="tab = 'kategori'"
                            :class="tab === 'kategori' ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-500 hover:text-slate-700'"
                            class="rounded-lg px-4 py-2 text-xs font-bold whitespace-nowrap transition-colors">
                            <i class="bi bi-list-check"></i> Kategori
                            <span class="ml-1 rounded-full bg-slate-200/80 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">{{ count($kategoriRows) }}</span>
                        </button>
                    </div>

                    <form method="get" action="{{ url('admin/poin-siswa') }}" class="ml-auto">
                        <label for="filter-kelas" class="sr-only">Filter kelas</label>
                        <select id="filter-kelas" name="kelas" @change="$el.form.submit()"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 outline-hidden transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                            <option value="">Semua Kelas</option>
                            @foreach ($kelasRows as $kelas)
                                <option value="{{ (int) $kelas->id_kelas }}" @selected((int) ($filter_kelas ?? 0) === (int) $kelas->id_kelas)>
                                    {{ $kelas->nama_kelas ?? ('Kelas #' . $kelas->id_kelas) }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </x-slot:toolbar>

            <div class="p-4 md:p-5">
                <!-- Panel Leaderboard -->
                <div x-show="tab === 'leaderboard'" role="tabpanel">
                    @if (count($leaderboardRows) === 0)
                        <x-ui.empty icon="bi-inbox" title="Belum ada data siswa"
                            description="Leaderboard akan terisi setelah siswa terdata pada tahun pelajaran dan semester aktif." />
                    @else
                        <div x-data="dataTable({ perPage: 25 })">
                            <div class="mb-3 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                                <div class="flex items-center gap-1.5 text-xs font-medium text-slate-500">
                                    <label for="poin-per-page">Tampilkan</label>
                                    <select id="poin-per-page" x-model.number="perPage" @change="page = 1; refresh()"
                                        class="rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                    <span>siswa</span>
                                </div>

                                <div class="relative w-full sm:w-64">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-slate-400">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="search" x-model="query" placeholder="Cari nama, NIS, atau kelas..." aria-label="Cari siswa"
                                        class="w-full rounded-xl border border-slate-200 bg-white py-2 pr-8 pl-8 text-xs outline-hidden transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                    <button type="button" x-show="query.length > 0" x-cloak @click="query = ''" aria-label="Hapus pencarian"
                                        class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600">
                                        <i class="bi bi-x-circle-fill text-xs"></i>
                                    </button>
                                </div>
                            </div>

                            <x-ui.table>
                                <x-slot:head>
                                    <th class="w-16 px-3 py-3 text-center">Rank</th>
                                    <th class="px-4 py-3">Siswa</th>
                                    <th class="px-4 py-3">Kelas</th>
                                    <th class="w-20 px-3 py-3 text-center">Kasus</th>
                                    <th class="w-20 px-3 py-3 text-center">Skor</th>
                                    <th class="w-32 px-3 py-3 text-right">Aksi</th>
                                </x-slot:head>

                                @foreach ($leaderboardRows as $index => $siswaRow)
                                    @php
                                        $rank = $index + 1;
                                        $skor = (int) ($siswaRow->skor ?? 100);
                                        $namaSiswa = $siswaRow->nama ?? '-';
                                    @endphp
                                    <tr class="transition-colors hover:bg-slate-50/60"
                                        data-search="{{ mb_strtolower(trim($namaSiswa . ' ' . ($siswaRow->nis ?? '') . ' ' . ($siswaRow->nama_kelas ?? ''))) }}">
                                        <td class="px-3 py-2.5 text-center">
                                            @if ($rank <= 3)
                                                <span @class([
                                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-extrabold',
                                                    'border border-amber-200 bg-amber-50 text-amber-700' => $rank === 1,
                                                    'border border-slate-200 bg-slate-100 text-slate-600' => $rank === 2,
                                                    'border border-blue-100 bg-blue-50 text-blue-700' => $rank === 3,
                                                ])>#{{ $rank }}</span>
                                            @else
                                                <span class="font-bold text-slate-400">#{{ $rank }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <div class="font-semibold text-slate-800">{{ $namaSiswa }}</div>
                                            <div class="font-mono text-[11px] text-slate-400">NIS {{ $siswaRow->nis ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <span class="inline-flex items-center rounded-full border border-blue-100 bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                                {{ $siswaRow->nama_kelas ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2.5 text-center font-bold text-slate-600">{{ (int) ($siswaRow->total_kasus ?? 0) }}×</td>
                                        <td class="px-3 py-2.5 text-center">
                                            <span @class([
                                                'mx-auto flex h-11 w-11 items-center justify-center rounded-full text-[13px] font-extrabold',
                                                'bg-emerald-100 text-emerald-700' => $skor >= 90,
                                                'bg-amber-100 text-amber-700' => $skor < 90 && $skor >= 75,
                                                'bg-rose-100 text-rose-700' => $skor < 75,
                                            ])>{{ $skor }}</span>
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <div class="flex justify-end gap-1">
                                                <button type="button" title="Riwayat"
                                                    @click="openHistory({{ (int) $siswaRow->id_siswa }}, {{ Js::from($namaSiswa) }})"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-500 transition-colors hover:bg-slate-50">
                                                    <i class="bi bi-clock-history"></i>
                                                </button>
                                                <button type="button" title="Catat poin"
                                                    @click="openRecord({{ (int) $siswaRow->id_siswa }}, {{ Js::from($namaSiswa) }})"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-xs text-amber-600 transition-colors hover:bg-amber-100">
                                                    <i class="bi bi-plus-lg"></i>
                                                </button>
                                                <a href="{{ url('admin/poin-siswa/detail/' . (int) $siswaRow->id_siswa) }}" title="Detail"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-xs text-blue-600 no-underline transition-colors hover:bg-blue-100">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </x-ui.table>

                            <div class="mt-3 flex flex-col justify-between gap-3 border-t border-slate-100 pt-3 sm:flex-row sm:items-center">
                                <div class="text-xs font-medium text-slate-500" aria-live="polite"
                                    x-text="total === 0 ? 'Tidak ada siswa yang cocok' : 'Menampilkan ' + from + '\\u2013' + to + ' dari ' + total + ' siswa'">
                                </div>
                                <div class="flex items-center gap-2" x-show="pages > 1" x-cloak>
                                    <button type="button" @click="prev()" :disabled="page <= 1" aria-label="Halaman sebelumnya"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <span class="text-xs font-semibold text-slate-500" x-text="'Halaman ' + page + ' / ' + pages"></span>
                                    <button type="button" @click="next()" :disabled="page >= pages" aria-label="Halaman berikutnya"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Panel Kategori -->
                <div x-show="tab === 'kategori'" x-cloak role="tabpanel">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-slate-500">
                            Pencatatan manual <b>hanya</b> dari kategori aktif di bawah. Nonaktif = tidak bisa dipilih.
                        </p>
                        <button type="button" @click="openKategori()"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                            <i class="bi bi-plus-lg"></i> Tambah Kategori
                        </button>
                    </div>

                    @if (count($kategoriRows) === 0)
                        <x-ui.empty icon="bi-list-check" title="Belum ada kategori"
                            description="Tambahkan kategori pelanggaran atau penghargaan sebelum mencatat poin siswa.">
                            <button type="button" @click="openKategori()"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                                <i class="bi bi-plus-lg"></i> Tambah Kategori
                            </button>
                        </x-ui.empty>
                    @else
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($kategoriRows as $kategoriRow)
                                @php $aktif = (int) $kategoriRow->is_active === 1; @endphp
                                <div class="flex items-center gap-3 rounded-2xl border p-3.5 {{ $aktif ? 'border-slate-200' : 'border-dashed border-slate-300 opacity-60' }}">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $kategoriRow->jenis === 'PENGHARGAAN' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                        <i class="bi {{ $kategoriRow->jenis === 'PENGHARGAAN' ? 'bi-award' : 'bi-exclamation-triangle' }}"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-bold text-slate-800">{{ $kategoriRow->nama }}</div>
                                        <div class="flex flex-wrap items-center gap-1 text-[11px] text-slate-400">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 font-semibold {{ $kategoriRow->jenis === 'PENGHARGAAN' ? 'border border-emerald-200 bg-emerald-50 text-emerald-700' : 'border border-rose-200 bg-rose-50 text-rose-700' }}">
                                                {{ $kategoriRow->jenis === 'PENGHARGAAN' ? '+' . (int) $kategoriRow->bobot : '-' . (int) $kategoriRow->bobot }}
                                            </span>
                                            @if (! $aktif)
                                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2 py-0.5 font-semibold text-slate-500">Nonaktif</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 flex-col gap-1">
                                        <button type="button" title="Edit"
                                            @click="openKategori({{ Js::from(['id' => (int) $kategoriRow->id, 'nama' => $kategoriRow->nama, 'jenis' => $kategoriRow->jenis, 'bobot' => (int) $kategoriRow->bobot, 'is_active' => (int) $kategoriRow->is_active]) }})"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-500 transition-colors hover:bg-slate-50">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" title="Hapus"
                                            @click="deleteKategori({{ (int) $kategoriRow->id }}, {{ Js::from($kategoriRow->nama ?? '') }})"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200 bg-white text-xs text-rose-600 transition-colors hover:bg-rose-50">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </x-ui.card>
    </div>

    <!-- Modal Catat Poin -->
    <x-ui.modal id="modalRecord" icon="bi-exclamation-diamond-fill" tone="amber" size="max-w-md">
        <x-slot:header>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white">
                        <i class="bi bi-exclamation-diamond-fill text-sm"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-slate-800">Catat Poin Disiplin</h2>
                        <p class="truncate text-[11px] text-slate-400" x-text="recordSubtitle"></p>
                    </div>
                </div>
                <button type="button" @click="$store.modals.close('#modalRecord')" aria-label="Tutup"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </x-slot:header>

        <form @submit.prevent="saveRecord()" novalidate class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <div>
                    <label for="rec-siswa" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                        Siswa <span class="text-rose-500">*</span>
                    </label>
                    <select id="rec-siswa" x-model="record.id_siswa" required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-amber-500 focus:bg-white focus:ring-2 focus:ring-amber-500/20">
                        <option value="">— Pilih siswa —</option>
                        @foreach ($leaderboardRows as $siswaRow)
                            <option value="{{ (int) $siswaRow->id_siswa }}">
                                {{ $siswaRow->nama ?? '-' }} ({{ $siswaRow->nama_kelas ?? '-' }} · skor {{ (int) ($siswaRow->skor ?? 100) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="rec-kategori" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                        Kategori <span class="text-rose-500">*</span>
                    </label>
                    <select id="rec-kategori" x-model="record.kategori_id" @change="setPreview($event)" required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-amber-500 focus:bg-white focus:ring-2 focus:ring-amber-500/20">
                        <option value="">— Pilih kategori —</option>
                        <optgroup label="Pelanggaran (mengurangi)">
                            @foreach ($kategoriRows as $kategoriRow)
                                @continue($kategoriRow->jenis !== 'PELANGGARAN' || ! (int) $kategoriRow->is_active)
                                <option value="{{ (int) $kategoriRow->id }}" data-bobot="-{{ (int) $kategoriRow->bobot }}">
                                    {{ $kategoriRow->nama }} (−{{ (int) $kategoriRow->bobot }})
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Penghargaan (menambah)">
                            @foreach ($kategoriRows as $kategoriRow)
                                @continue($kategoriRow->jenis !== 'PENGHARGAAN' || ! (int) $kategoriRow->is_active)
                                <option value="{{ (int) $kategoriRow->id }}" data-bobot="+{{ (int) $kategoriRow->bobot }}">
                                    {{ $kategoriRow->nama }} (+{{ (int) $kategoriRow->bobot }})
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                    <p class="mt-1 text-[11px]" x-show="previewBobot !== ''" x-cloak>
                        Efek ke skor:
                        <b :class="previewBobot.startsWith('+') ? 'text-emerald-600' : 'text-rose-600'" x-text="previewBobot + ' poin'"></b>
                    </p>
                </div>

                <div>
                    <label for="rec-ket" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                        Keterangan <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="rec-ket" x-model="record.keterangan" rows="2" required minlength="5" maxlength="255"
                        placeholder="Contoh: Terlambat apel pagi Senin, 23 Sep (min. 5 karakter)"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-amber-500 focus:bg-white focus:ring-2 focus:ring-amber-500/20"></textarea>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#modalRecord')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="recordSaving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-amber-500/20 transition-colors hover:bg-amber-600 disabled:opacity-60">
                    <span x-show="recordSaving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!recordSaving" class="bi bi-save"></i> Simpan
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal Kategori -->
    <x-ui.modal id="modalKategori" icon="bi-list-check" tone="blue" size="max-w-md">
        <x-slot:header>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white">
                        <i class="bi bi-list-check text-sm"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-slate-800" x-text="kategoriForm.id ? 'Edit Kategori' : 'Tambah Kategori'"></h2>
                        <p class="text-[11px] text-slate-400">Bobot 1–100 poin</p>
                    </div>
                </div>
                <button type="button" @click="$store.modals.close('#modalKategori')" aria-label="Tutup"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </x-slot:header>

        <form @submit.prevent="saveKategori()" novalidate class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <div>
                    <label for="kat-nama" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                        Nama Kategori <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="kat-nama" x-model="kategoriForm.nama" required minlength="3" maxlength="100"
                        placeholder="Contoh: Merokok di lingkungan sekolah"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="kat-jenis" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                            Jenis <span class="text-rose-500">*</span>
                        </label>
                        <select id="kat-jenis" x-model="kategoriForm.jenis" required
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                            <option value="PELANGGARAN">Pelanggaran (−)</option>
                            <option value="PENGHARGAAN">Penghargaan (+)</option>
                        </select>
                    </div>
                    <div>
                        <label for="kat-bobot" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                            Bobot Poin <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" id="kat-bobot" x-model.number="kategoriForm.bobot" required min="1" max="100"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>

                <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-bold text-slate-600">
                    <input type="checkbox" x-model="kategoriForm.is_active" class="h-4 w-4 rounded accent-blue-600">
                    Aktif (bisa dipilih saat mencatat)
                </label>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#modalKategori')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="kategoriSaving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                    <span x-show="kategoriSaving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!kategoriSaving" class="bi bi-save"></i> Simpan
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal Riwayat -->
    <x-ui.modal id="modalHistory" icon="bi-clock-history" tone="slate" size="max-w-lg">
        <x-slot:header>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-white">
                        <i class="bi bi-clock-history text-sm"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="truncate text-sm font-bold text-slate-800" x-text="history.nama || 'Riwayat'"></h2>
                        <p class="text-[11px] text-slate-400">
                            Skor saat ini: <b x-text="history.skor"></b> · transaksi terakhir
                        </p>
                    </div>
                </div>
                <button type="button" @click="$store.modals.close('#modalHistory')" aria-label="Tutup"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </x-slot:header>

        <div class="max-h-[50vh] space-y-2 overflow-y-auto p-5">
            <p x-show="history.loading" class="py-6 text-center text-xs text-slate-400">Memuat...</p>
            <p x-show="!history.loading && history.error" x-cloak class="py-6 text-center text-xs text-rose-500" x-text="history.error"></p>
            <p x-show="!history.loading && !history.error && history.list.length === 0" x-cloak
                class="py-6 text-center text-xs text-slate-400">Belum ada transaksi.</p>

            <template x-for="(item, index) in history.list" :key="index">
                <div class="flex items-center gap-2.5 rounded-xl border border-slate-100 px-3 py-2">
                    <span class="inline-flex min-w-12 items-center justify-center rounded-full border px-2 py-0.5 font-mono text-xs font-semibold"
                        :class="Number(item.poin) >= 0 ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700'"
                        x-text="poinLabel(item.poin)"></span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-xs font-bold text-slate-700" x-text="item.kategori_nama || item.tipe || '-'"></div>
                        <div class="truncate text-[11px] text-slate-400"
                            x-text="(item.keterangan || '') + ' · ' + (item.created_at || '')"></div>
                    </div>
                </div>
            </template>
        </div>

        <div class="flex shrink-0 items-center justify-end border-t border-slate-100 bg-slate-50 px-5 py-3.5">
            <button type="button" @click="$store.modals.close('#modalHistory')"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Tutup</button>
        </div>
    </x-ui.modal>

    <!-- Modal Sinkron Presensi -->
    <x-ui.modal id="modalSync" icon="bi-arrow-repeat" tone="blue" size="max-w-md">
        <x-slot:header>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white">
                        <i class="bi bi-arrow-repeat text-sm"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-slate-800">Sinkron Presensi → Poin</h2>
                        <p class="text-[11px] leading-snug text-slate-400">
                            Terlambat memakai bobot kategori "Terlambat", alpha memakai "Bolos". Aman dijalankan berulang; pengurangan yang tak lagi berlaku ikut dicabut.
                        </p>
                    </div>
                </div>
                <button type="button" @click="$store.modals.close('#modalSync')" aria-label="Tutup"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </x-slot:header>

        <form @submit.prevent="saveSync()" class="flex min-h-0 flex-col">
            <div class="grid grid-cols-2 gap-3 overflow-y-auto p-5">
                <div>
                    <label for="sync-bulan" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">Bulan</label>
                    <input type="number" id="sync-bulan" x-model.number="sync.bulan" min="1" max="12"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label for="sync-tahun" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">Tahun</label>
                    <input type="number" id="sync-tahun" x-model.number="sync.tahun" min="2020" max="{{ date('Y') + 1 }}"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div class="col-span-2">
                    <label for="sync-kelas" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">Kelas (opsional)</label>
                    <select id="sync-kelas" x-model="sync.id_kelas"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <option value="0">Semua kelas</option>
                        @foreach ($kelasRows as $kelas)
                            <option value="{{ (int) $kelas->id_kelas }}">{{ $kelas->nama_kelas ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#modalSync')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="syncSaving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                    <span x-show="syncSaving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!syncSaving" class="bi bi-arrow-repeat"></i> Jalankan
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('poinSiswaPage', () => ({
        config: { csrf: '', urls: {} },

        tab: 'leaderboard',

        record: { id_siswa: '', kategori_id: '', keterangan: '' },
        recordSubtitle: 'Pilih siswa & kategori pelanggaran/penghargaan',
        previewBobot: '',
        recordSaving: false,

        kategoriForm: { id: '', nama: '', jenis: 'PELANGGARAN', bobot: 5, is_active: true },
        kategoriSaving: false,

        history: { nama: '', skor: 100, list: [], loading: false, error: '' },

        sync: { bulan: 1, tahun: 2020, id_kelas: '0' },
        syncSaving: false,

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');

            this.config = {
                csrf: cfg.csrf || '',
                urls: cfg.urls || {},
                bulan: cfg.bulan || 1,
                tahun: cfg.tahun || 2020,
            };

            this.sync.bulan = this.config.bulan;
            this.sync.tahun = this.config.tahun;
        },

        poinLabel(poin) {
            const nilai = parseInt(poin || 0, 10);

            return (nilai > 0 ? '+' : '') + nilai;
        },

        async request(url, entries) {
            const body = new URLSearchParams();
            body.append('_token', this.config.csrf);

            entries.forEach(([key, value]) => body.append(key, value ?? ''));

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': this.config.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });

            let result = null;

            try {
                result = await response.json();
            } catch (error) {
                result = null;
            }

            if (!response.ok) {
                const err = new Error((result && (result.message || result.msg)) || 'Gagal menghubungi server.');
                err.result = result;
                throw err;
            }

            return result;
        },

        setPreview(event) {
            const option = event.target.selectedOptions && event.target.selectedOptions[0];

            this.previewBobot = (option && option.dataset.bobot) || '';
        },

        openRecord(idSiswa = null, nama = '') {
            this.record = {
                id_siswa: idSiswa ? String(idSiswa) : '',
                kategori_id: '',
                keterangan: '',
            };
            this.previewBobot = '';
            this.recordSubtitle = idSiswa
                ? 'Catat untuk: ' + (nama || '')
                : 'Pilih siswa & kategori pelanggaran/penghargaan';

            this.$store.modals.open('#modalRecord');
        },

        async saveRecord() {
            if (this.recordSaving) return;

            const keterangan = (this.record.keterangan || '').trim();

            if (!this.record.id_siswa) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih siswa terlebih dahulu.' });
                return;
            }

            if (!this.record.kategori_id) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih kategori terlebih dahulu.' });
                return;
            }

            if (keterangan.length < 5) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Keterangan minimal 5 karakter.' });
                return;
            }

            this.recordSaving = true;

            try {
                const result = await this.request(this.config.urls.record, [
                    ['id_siswa', this.record.id_siswa],
                    ['kategori_id', this.record.kategori_id],
                    ['keterangan', keterangan],
                ]);

                if (result && result.status) {
                    this.$store.modals.close('#modalRecord');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: (result.message || 'Tersimpan.')
                            + (result.data !== null && result.data !== undefined ? ' Skor kini: ' + result.data : ''),
                    }).then(() => window.location.reload());
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.message) || 'Gagal menyimpan.' });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Gagal menghubungi server.' });
            } finally {
                this.recordSaving = false;
            }
        },

        openKategori(kategori = null) {
            this.kategoriForm = kategori && kategori.id
                ? {
                    id: String(kategori.id),
                    nama: kategori.nama || '',
                    jenis: kategori.jenis || 'PELANGGARAN',
                    bobot: parseInt(kategori.bobot || 0, 10),
                    is_active: String(kategori.is_active) === '1',
                }
                : { id: '', nama: '', jenis: 'PELANGGARAN', bobot: 5, is_active: true };

            this.$store.modals.open('#modalKategori');
        },

        async saveKategori() {
            if (this.kategoriSaving) return;

            const nama = (this.kategoriForm.nama || '').trim();
            const bobot = parseInt(this.kategoriForm.bobot || 0, 10);

            if (nama.length < 3) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Nama kategori minimal 3 karakter.' });
                return;
            }

            if (!(bobot >= 1 && bobot <= 100)) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Bobot harus 1–100.' });
                return;
            }

            this.kategoriSaving = true;

            try {
                const result = await this.request(this.config.urls.kategoriSave, [
                    ['id', this.kategoriForm.id],
                    ['nama', nama],
                    ['jenis', this.kategoriForm.jenis],
                    ['bobot', bobot],
                    ['is_active', this.kategoriForm.is_active ? 1 : 0],
                ]);

                if (result && result.status) {
                    this.$store.modals.close('#modalKategori');
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message || 'Tersimpan.' })
                        .then(() => window.location.reload());
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.message) || 'Gagal menyimpan.' });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Gagal menghubungi server.' });
            } finally {
                this.kategoriSaving = false;
            }
        },

        async deleteKategori(id, nama) {
            const konfirmasi = await Swal.fire({
                title: 'Hapus "' + (nama || '') + '"?',
                text: 'Kategori yang sudah dipakai riwayat tidak bisa dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            try {
                const result = await this.request(this.config.urls.kategoriDelete, [['id', id]]);

                if (result && result.status) {
                    Swal.fire({ icon: 'success', title: 'Terhapus', text: result.message || '' })
                        .then(() => window.location.reload());
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.message) || 'Gagal menghapus.' });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Gagal menghubungi server.' });
            }
        },

        async openHistory(idSiswa, nama) {
            this.history = {
                nama: nama || 'Riwayat',
                skor: 100,
                list: [],
                loading: true,
                error: '',
            };

            this.$store.modals.open('#modalHistory');

            try {
                const response = await fetch(this.config.urls.history.replace('__ID__', idSiswa), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const result = await response.json();

                if (!(result && result.status)) {
                    this.history.loading = false;
                    this.history.error = 'Gagal memuat.';
                    return;
                }

                this.history.skor = result.data.skor ?? 100;
                this.history.list = result.data.history || [];
                this.history.loading = false;
            } catch (error) {
                this.history.loading = false;
                this.history.error = 'Gagal memuat.';
            }
        },

        async saveSync() {
            if (this.syncSaving) return;

            this.syncSaving = true;

            try {
                const result = await this.request(this.config.urls.sync, [
                    ['bulan', this.sync.bulan],
                    ['tahun', this.sync.tahun],
                    ['id_kelas', this.sync.id_kelas],
                ]);

                if (result && result.status) {
                    this.$store.modals.close('#modalSync');
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message })
                        .then(() => window.location.reload());
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.message) || 'Gagal sinkron.' });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Gagal menghubungi server.' });
            } finally {
                this.syncSaving = false;
            }
        },
    }));
});
</script>
@endsection
