@extends('layouts.main')

@section('content')

<?php
$isAdmin   = (bool) ($is_admin ?? false);
$hasPeriode = (bool) ($has_periode ?? false);
$stats = array_merge([
    'total'         => 0,
    'aktif'         => 0,
    'lulus'         => 0,
    'pindah'        => 0,
    'keluar'        => 0,
    'tanpa_kelas'   => 0,
    'akun_nonaktif' => 0,
], is_array($stats ?? null) ? $stats : []);

$tpLabel = trim((string) ($tp_active->tahun ?? '')) ?: '-';
$smtLabel = trim((string) ($smt_active->nama_smt ?? '')) ?: (trim((string) ($smt_active->smt ?? '')) ?: '-');
$totalKelas = is_array($kelass ?? null) ? count($kelass) : 0;

$inputClass = 'w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden';
$labelClass = 'block text-xs font-semibold text-slate-700 mb-1.5';

$opsiKelasAwal = [];
if (($setting->jenjang ?? 0) == 1) {
    $opsiKelasAwal = [1, 2, 3, 4, 5, 6];
} elseif (($setting->jenjang ?? 0) == 2) {
    $opsiKelasAwal = [7, 8, 9];
} else {
    $opsiKelasAwal = [10, 11, 12];
}

$pageConfig = [
    'isAdmin'    => $isAdmin,
    'hasPeriode' => $hasPeriode,
    'csrf'       => csrf_token(),
    'defaultFoto' => asset('assets/img/siswa_sm.png'),
    'urls' => [
        'list'        => url('datasiswa/list'),
        'delete'      => url('datasiswa/delete'),
        'create'      => url('datasiswa/create'),
        'edit'        => url('datasiswa/edit'),
        'add'         => url('datasiswa/add'),
        'assignRfid'  => url('datasiswa/assign_rfid'),
        'siswaByKelas' => url('datasiswa/siswa_by_kelas'),
    ],
];
?>

<div x-data="siswaPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <!-- Content Header -->
    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'Data Siswa'"
            icon="bi-people-fill"
            :crumbs="['Data Umum', $judul ?? 'Siswa']" />
    </div>

    <!-- Main Content Area -->
    <div class="mt-4 space-y-5 px-4 pb-6 md:px-6">

        <?php if (!$hasPeriode): ?>
            <div class="flex items-start gap-3 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 shadow-xs">
                <i class="bi bi-exclamation-triangle-fill text-amber-500 text-lg mt-0.5 shrink-0"></i>
                <div class="min-w-0">
                    <p class="text-sm font-bold mb-0.5">Tahun Pelajaran / Semester aktif belum diset</p>
                    <p class="text-xs mb-0">Daftar siswa dan filter kelas membutuhkan periode aktif. Atur TP/SMT terlebih dahulu agar data dapat ditampilkan.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Ringkasan data -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 hover:border-blue-100 transition-colors">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold text-slate-500 mb-0">Total siswa</p>
                        <p class="text-2xl font-bold text-slate-800 leading-none mt-1.5" x-text="format(stats.total)">{{ number_format((int) $stats['total'], 0, ',', '.') }}</p>
                    </div>
                    <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <i class="bi bi-people-fill"></i>
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 mt-2 mb-0">Seluruh siswa terdaftar</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 hover:border-emerald-100 transition-colors">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold text-slate-500 mb-0">Siswa aktif</p>
                        <p class="text-2xl font-bold text-emerald-700 leading-none mt-1.5" x-text="format(stats.aktif)">{{ number_format((int) $stats['aktif'], 0, ',', '.') }}</p>
                    </div>
                    <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <i class="bi bi-person-check-fill"></i>
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 mt-2 mb-0">Status buku induk aktif</p>
            </div>

            <button type="button" @click="filterTanpaKelas()" :disabled="!hasPeriode"
                class="text-left bg-white rounded-2xl border border-slate-100 shadow-xs p-4 hover:border-amber-300 hover:shadow-sm transition-all disabled:opacity-60 disabled:cursor-not-allowed"
                title="Klik untuk memfilter siswa yang belum ada kelas">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold text-slate-500 mb-0">Tanpa kelas</p>
                        <p class="text-2xl font-bold text-amber-700 leading-none mt-1.5">
                            <span x-show="hasPeriode" x-text="format(stats.tanpa_kelas)">{{ number_format((int) $stats['tanpa_kelas'], 0, ',', '.') }}</span>
                            <span x-show="!hasPeriode" x-cloak>&mdash;</span>
                        </p>
                    </div>
                    <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <i class="bi bi-mortarboard-fill"></i>
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 mt-2 mb-0">Belum punya kelas di TP aktif</p>
            </button>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 hover:border-rose-100 transition-colors">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold text-slate-500 mb-0">Akun nonaktif</p>
                        <p class="text-2xl font-bold text-rose-700 leading-none mt-1.5" x-text="format(stats.akun_nonaktif)">{{ number_format((int) $stats['akun_nonaktif'], 0, ',', '.') }}</p>
                    </div>
                    <span class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <i class="bi bi-shield-slash-fill"></i>
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 mt-2 mb-0">Tidak dapat login ke aplikasi</p>
            </div>
        </div>

        <x-ui.card :body-class="'p-0'">
            <!-- Card Header Toolbar -->
            <div class="px-5 md:px-6 py-4 border-b border-slate-100 flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-slate-50/50">
                <div class="min-w-0">
                    <h2 class="font-bold text-slate-800 flex items-center gap-2 mb-0.5">
                        <i class="bi bi-person-lines-fill text-blue-600"></i>
                        Daftar Peserta Didik
                    </h2>
                    <p class="text-xs text-slate-500 mb-0">Kelola identitas siswa, kelas aktif, NIS/NISN, dan status akun.</p>
                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[11px] font-semibold">
                            <i class="bi bi-calendar3 text-slate-400"></i>
                            TP {{ $tpLabel }} &middot; {{ $smtLabel }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-100 text-[11px] font-semibold">
                            <i class="bi bi-door-open text-blue-400"></i>
                            {{ number_format($totalKelas, 0, ',', '.') }} kelas
                        </span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2 w-full xl:w-auto">
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="search" x-ref="search" x-model="query" @input.debounce.300ms="applyFilters()"
                            placeholder="Cari nama, NIS, atau NISN..." aria-label="Cari siswa"
                            class="w-full pl-8 pr-8 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden">
                        <button type="button" x-show="query.length > 0" x-cloak @click="clearSearch()"
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600"
                            aria-label="Hapus pencarian">
                            <i class="bi bi-x-circle-fill text-xs"></i>
                        </button>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" @click="reload()"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-xs">
                            <i class="bi bi-arrow-repeat"></i> Reload
                        </button>
                        <?php if ($isAdmin): ?>
                            <a href="{{ url('datasiswa/update') }}"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-xs">
                                <i class="bi bi-database-check text-emerald-600"></i> Update Data
                            </a>
                            <a href="{{ url('datasiswa/add') }}"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-xl hover:bg-indigo-100 transition-colors shadow-xs">
                                <i class="bi bi-upload"></i> Import
                            </a>
                            <button type="button" @click="openNfc()"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 transition-colors shadow-xs"
                                title="Scan & Tautkan Kartu RFID/NFC ke Siswa">
                                <i class="bi bi-broadcast"></i> Scan NFC Siswa
                            </button>
                            <button type="button" @click="openCreate()"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-xs shadow-blue-500/20">
                                <i class="bi bi-plus-lg"></i> Tambah Siswa
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Filter & Bulk Actions -->
            <div class="px-5 md:px-6 py-3.5 border-b border-slate-100 bg-white">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                            <label for="users_length">Tampilkan</label>
                            <select id="users_length" x-model.number="perPage" @change="applyFilters()" aria-label="Jumlah data per halaman"
                                class="px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-hidden font-medium text-slate-700">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span>data</span>
                        </div>

                        <span class="hidden sm:inline-block w-px h-5 bg-slate-200 mx-0.5" aria-hidden="true"></span>

                        <select x-model="statusFilter" @change="applyFilters()" aria-label="Filter status siswa"
                            :class="statusFilter !== '0' ? 'border-blue-300 bg-blue-50/40 text-blue-700' : ''"
                            class="px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-hidden font-medium text-slate-700 transition-colors">
                            <option value="0">Semua Status</option>
                            <option value="1">Aktif</option>
                            <option value="2">Lulus</option>
                            <option value="3">Pindah</option>
                            <option value="4">Keluar</option>
                            <option value="5">Akun Nonaktif</option>
                        </select>

                        <select x-model="kelasFilter" @change="applyFilters()" aria-label="Filter kelas"
                            :class="kelasFilter !== '' ? 'border-blue-300 bg-blue-50/40 text-blue-700' : ''"
                            class="px-3 py-1.5 text-xs bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-hidden font-medium text-slate-700 transition-colors">
                            <option value="">Semua Kelas</option>
                            <option value="tanpa">Tanpa Kelas (belum ada kelas)</option>
                            <?php foreach (($kelass ?? []) as $id => $name): ?>
                                <option value="{{ (int) $id }}">{{ $name }}</option>
                            <?php endforeach; ?>
                        </select>

                        <button type="button" x-show="hasFilter()" x-cloak @click="resetFilters()"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-100 rounded-xl hover:bg-blue-100 transition-colors">
                            <i class="bi bi-x-circle"></i> Reset filter
                        </button>
                    </div>

                    <?php if ($isAdmin): ?>
                        <div class="flex flex-wrap items-center gap-2">
                            <span x-show="selected.length > 0" x-cloak
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-blue-50 border border-blue-100 text-[11px] font-semibold text-blue-700">
                                <i class="bi bi-check2-circle"></i>
                                <span x-text="selected.length">0</span> dipilih
                            </span>
                            <div class="relative" @click.outside="bulkOpen = false">
                                <button type="button" @click="bulkOpen = !bulkOpen" :disabled="selected.length === 0"
                                    :aria-expanded="bulkOpen ? 'true' : 'false'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-100 transition-colors shadow-2xs">
                                    <i class="bi bi-check2-square"></i>
                                    <span>Aksi Terpilih</span>
                                    <i class="bi bi-chevron-down text-[10px] ml-1"></i>
                                </button>
                                <div x-show="bulkOpen" x-cloak
                                    class="absolute right-0 mt-1 w-56 rounded-2xl border border-slate-100 shadow-xl py-2 bg-white text-xs z-50">
                                    <button type="button" @click="bulkAction('pindah')"
                                        class="w-full px-4 py-2 text-left hover:bg-amber-50 text-amber-700 font-medium flex items-center gap-2">
                                        <i class="bi bi-box-arrow-right"></i> Set sebagai PINDAH
                                    </button>
                                    <button type="button" @click="bulkAction('keluar')"
                                        class="w-full px-4 py-2 text-left hover:bg-slate-100 text-slate-700 font-medium flex items-center gap-2">
                                        <i class="bi bi-door-closed"></i> Set sebagai KELUAR
                                    </button>
                                    <div class="my-1 border-t border-slate-100"></div>
                                    <button type="button" @click="bulkAction('hapus')"
                                        class="w-full px-4 py-2 text-left hover:bg-rose-50 text-rose-600 font-medium flex items-center gap-2">
                                        <i class="bi bi-trash3"></i> Hapus Terpilih
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="px-5 md:px-6 py-5">
                <div class="relative overflow-x-auto rounded-xl">
                    <x-ui.table aria-describedby="total-entri" class="min-w-[880px]">
                        <x-slot:head>
                            <?php if ($isAdmin): ?>
                                <th class="py-3 px-3 text-center w-10">
                                    <input type="checkbox" :checked="allSelected()"
                                        x-effect="$el.indeterminate = selected.length > 0 && !allSelected()"
                                        @change="toggleAll($event)" aria-label="Pilih semua siswa pada halaman ini"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                </th>
                            <?php endif; ?>
                            <th class="py-3 px-3 text-center w-12 font-bold">No</th>
                            <th class="py-3 px-4 font-bold">Siswa</th>
                            <th class="py-3 px-4 font-bold">NIS &amp; NISN</th>
                            <th class="py-3 px-4 font-bold">Kelas</th>
                            <th class="py-3 px-4 text-center font-bold">Status</th>
                            <?php if ($isAdmin): ?>
                                <th class="py-3 px-4 text-center font-bold w-28">Aksi</th>
                            <?php endif; ?>
                        </x-slot:head>

                        <template x-for="(row, idx) in rows" :key="row.id_siswa">
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td x-show="isAdmin" class="text-center align-middle py-3 px-3">
                                    <input type="checkbox" :checked="selected.includes(Number(row.id_siswa))"
                                        @change="toggleOne($event, row.id_siswa)" :aria-label="'Pilih ' + row.nama"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                </td>
                                <td class="text-center align-middle font-bold text-slate-400 py-3 px-3" x-text="rowNo(idx)"></td>
                                <td class="align-middle py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <img :src="row.foto || defaultFoto" :alt="'Foto ' + row.nama" loading="lazy"
                                            x-on:error="$event.target.onerror = null; $event.target.src = defaultFoto"
                                            class="w-10 h-10 rounded-xl object-cover border border-slate-200 bg-slate-50 shadow-xs shrink-0">
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-800 text-sm truncate max-w-[240px]" :title="row.nama" x-text="row.nama || 'Tanpa nama'"></div>
                                            <div x-show="rowMeta(row) !== ''" x-cloak class="mt-1 text-[11px] text-slate-500" x-text="rowMeta(row)"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle py-3 px-4">
                                    <div class="text-[11px] text-slate-400 font-medium">
                                        NIS <span class="font-bold text-slate-700 font-mono" x-text="row.nis || '-'"></span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 font-medium mt-0.5">
                                        NISN <span class="font-bold text-slate-700 font-mono" x-text="row.nisn || '-'"></span>
                                    </div>
                                    <div x-show="row.nomor_rfid" x-cloak
                                        class="text-[10px] text-emerald-700 font-semibold mt-1 flex items-center gap-1 font-mono bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded-md w-fit">
                                        <i class="bi bi-broadcast text-emerald-600"></i><span x-text="row.nomor_rfid"></span>
                                    </div>
                                    <div x-show="!row.nomor_rfid" class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>Tanpa RFID
                                    </div>
                                </td>
                                <td class="align-middle py-3 px-4">
                                    <span x-show="row.nama_kelas"
                                        class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-100"
                                        x-text="row.nama_kelas"></span>
                                    <span x-show="!row.nama_kelas"
                                        class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-100">
                                        <i class="bi bi-exclamation-circle mr-1"></i>Belum ada kelas
                                    </span>
                                </td>
                                <td class="text-center align-middle py-3 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full border text-[10px] font-bold uppercase tracking-wide whitespace-nowrap"
                                        :class="statusMeta(row.status).cls">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="statusMeta(row.status).dot"></span>
                                        <span x-text="statusMeta(row.status).label"></span>
                                    </span>
                                    <div x-show="String(row.active) === '0'" x-cloak class="mt-1">
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-rose-50 text-rose-600 border border-rose-100 text-[10px] font-semibold">
                                            <i class="bi bi-shield-slash"></i>Login nonaktif
                                        </span>
                                    </div>
                                    <div x-show="row.active === null || row.active === undefined" x-cloak class="mt-1">
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-slate-50 text-slate-500 border border-slate-200 text-[10px] font-semibold">
                                            <i class="bi bi-person-x"></i>Tanpa akun
                                        </span>
                                    </div>
                                </td>
                                <td x-show="isAdmin" class="text-center align-middle py-3 px-4">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" @click="openNfc(row.id_siswa, row.id_kelas)"
                                            :title="row.nomor_rfid ? 'RFID: ' + row.nomor_rfid + ' (Klik untuk ganti)' : 'Tap NFC untuk daftarkan kartu'"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg transition-colors shadow-xs"
                                            :class="row.nomor_rfid ? 'bg-emerald-50 text-emerald-600 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200'">
                                            <i class="bi bi-broadcast text-xs"></i>
                                        </button>
                                        <a :href="editUrl(row.id_siswa)" :aria-label="'Edit data ' + row.nama" title="Edit data siswa"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 hover:bg-blue-100 transition-colors shadow-xs">
                                            <i class="bi bi-pencil-square text-xs"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="!loading && rows.length === 0">
                            <td :colspan="isAdmin ? 7 : 5" class="px-4 py-14 text-center">
                                <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-slate-50 border border-slate-100 text-slate-400 flex items-center justify-center text-2xl">
                                    <i class="bi" :class="errorMessage ? 'bi-exclamation-triangle' : (hasFilter() ? 'bi-search' : 'bi-inbox')"></i>
                                </div>
                                <p class="text-sm font-bold text-slate-700 mb-1" x-text="emptyTitle()"></p>
                                <p class="text-xs text-slate-400 max-w-sm mx-auto mb-4" x-text="emptyDetail()"></p>
                                <button type="button" x-show="hasFilter()" x-cloak @click="resetFilters()"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-100 rounded-xl hover:bg-blue-100 transition-colors">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset filter
                                </button>
                                <?php if ($isAdmin): ?>
                                    <a x-show="!hasFilter()" x-cloak href="{{ url('datasiswa/add') }}"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-xl hover:bg-blue-700 shadow-xs shadow-blue-500/20">
                                        <i class="bi bi-plus-lg"></i> Tambah / Import Siswa
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <template x-if="loading && rows.length === 0">
                            <tr>
                                <td :colspan="isAdmin ? 7 : 5" class="px-4 py-14 text-center">
                                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 shadow-sm text-slate-600 text-xs font-semibold">
                                        <span class="inline-block w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></span>
                                        <span>Memuat data siswa...</span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </x-ui.table>

                    <div x-show="loading && rows.length > 0" x-cloak
                        class="absolute inset-0 bg-white/70 backdrop-blur-[1px] flex items-center justify-center rounded-xl z-20">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 shadow-lg text-slate-600 text-xs font-semibold">
                            <span class="inline-block w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></span>
                            <span>Memuat data siswa...</span>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="text-xs font-medium text-slate-500" id="total-entri" aria-live="polite" x-text="summaryText()"></div>
                    <nav aria-label="Navigasi halaman siswa" class="min-w-0 overflow-x-auto">
                        <ul class="flex items-center gap-1 mb-0" x-show="pages > 0" x-cloak>
                            <li>
                                <button type="button" @click="goPage(page - 1)" :disabled="page <= 1" aria-label="Halaman sebelumnya"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors text-xs">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                            </li>
                            <template x-for="p in pageList()" :key="p">
                                <li>
                                    <button type="button" @click="goPage(p)"
                                        :aria-current="p === page ? 'page' : null"
                                        class="inline-flex items-center justify-center min-w-8 h-8 px-2 rounded-lg border text-xs font-semibold transition-colors"
                                        :class="p === page ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                                        x-text="p"></button>
                                </li>
                            </template>
                            <li>
                                <button type="button" @click="goPage(page + 1)" :disabled="page >= pages" aria-label="Halaman berikutnya"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors text-xs">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </x-ui.card>
    </div>

    <?php if ($isAdmin): ?>
        <!-- Modal Tambah Siswa -->
        <div x-show="createOpen" x-cloak role="dialog" aria-modal="true" aria-labelledby="createModalLabel"
            @keydown.escape.window="createOpen = false"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-3 sm:p-4">
            <div class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="px-5 sm:px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-blue-50 to-indigo-50/30 flex items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center text-sm shrink-0">
                            <i class="bi bi-person-plus"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 class="font-bold text-slate-800 text-base mb-0.5" id="createModalLabel">Tambah Siswa Baru</h2>
                            <p class="text-[11px] text-slate-500 mb-0">Data akun login dibuat otomatis setelah siswa disimpan.</p>
                        </div>
                    </div>
                    <button type="button" @click="createOpen = false"
                        class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 inline-flex items-center justify-center transition-colors shrink-0"
                        aria-label="Tutup">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>

                <form x-ref="formSiswa" @submit.prevent="createSiswa()" class="flex flex-col min-h-0">
                    <div class="p-5 sm:p-6 space-y-5 overflow-y-auto">
                        <section>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Identitas Siswa</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label for="nama_siswa" class="{{ $labelClass }}">Nama Lengkap <span class="text-rose-500">*</span></label>
                                    <input id="nama_siswa" name="nama_siswa" type="text" class="{{ $inputClass }}" x-model="form.nama_siswa"
                                        placeholder="Contoh: Maria Goreti Lopes" autocomplete="off" maxlength="100" required>
                                    <p x-show="errors.nama_siswa" x-cloak class="mt-1 text-[11px] font-medium text-rose-600" x-text="errors.nama_siswa"></p>
                                </div>
                                <div>
                                    <label for="nis" class="{{ $labelClass }}">NIS <span class="text-rose-500">*</span></label>
                                    <input id="nis" name="nis" type="number" inputmode="numeric" class="{{ $inputClass }}" x-model="form.nis"
                                        placeholder="Nomor Induk Siswa" autocomplete="off" minlength="4" maxlength="20" required>
                                    <p x-show="errors.nis" x-cloak class="mt-1 text-[11px] font-medium text-rose-600" x-text="errors.nis"></p>
                                </div>
                                <div>
                                    <label for="nisn" class="{{ $labelClass }}">NISN <span class="text-rose-500">*</span></label>
                                    <input id="nisn" name="nisn" type="number" inputmode="numeric" class="{{ $inputClass }}" x-model="form.nisn"
                                        placeholder="NISN Nasional" autocomplete="off" minlength="9" maxlength="20" required>
                                    <p x-show="errors.nisn" x-cloak class="mt-1 text-[11px] font-medium text-rose-600" x-text="errors.nisn"></p>
                                </div>
                                <div>
                                    <label for="jenis_kelamin" class="{{ $labelClass }}">Jenis Kelamin</label>
                                    <select id="jenis_kelamin" name="jenis_kelamin" x-model="form.jenis_kelamin" class="{{ $inputClass }}">
                                        <option value="">Belum dipilih</option>
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="agama" class="{{ $labelClass }}">Agama</label>
                                    <select id="agama" name="agama" x-model="form.agama" class="{{ $inputClass }}">
                                        <option value="">Belum dipilih</option>
                                        <?php foreach (["Islam", "Katolik", "Protestan", "Hindu", "Budha", "Konghucu"] as $agama): ?>
                                            <option value="{{ $agama }}">{{ $agama }}</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <section>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Data Akademik</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="kelas_awal" class="{{ $labelClass }}">Kelas Awal</label>
                                    <select id="kelas_awal" name="kelas_awal" x-model="form.kelas_awal" class="{{ $inputClass }}">
                                        <option value="">Belum dipilih</option>
                                        <?php foreach ($opsiKelasAwal as $kls): ?>
                                            <option value="{{ $kls }}">{{ $kls }}</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="tahunmasuk" class="{{ $labelClass }}">Tanggal Diterima <span class="text-rose-500">*</span></label>
                                    <input type="date" id="tahunmasuk" name="tahun_masuk" class="{{ $inputClass }}" x-model="form.tahun_masuk" required>
                                    <p x-show="errors.tahun_masuk" x-cloak class="mt-1 text-[11px] font-medium text-rose-600" x-text="errors.tahun_masuk"></p>
                                </div>
                            </div>
                        </section>

                        <section>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Kartu RFID / NFC</p>
                            <div>
                                <label for="modal_nomor_rfid" class="{{ $labelClass }}">Nomor Kartu RFID</label>
                                <div class="relative">
                                    <input id="modal_nomor_rfid" name="nomor_rfid" type="text" class="{{ $inputClass }} pr-24 font-mono uppercase"
                                        x-model="form.nomor_rfid" placeholder="Contoh: 04A1B2C3" autocomplete="off" maxlength="64">
                                    <button type="button" @click="scanField()"
                                        class="absolute right-1.5 top-1/2 -translate-y-1/2 px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 flex items-center gap-1 transition-all"
                                        title="Tempelkan kartu RFID ke HP">
                                        <i class="bi bi-broadcast"></i> Tap NFC
                                    </button>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1 mb-0">Opsional. Boleh dikosongkan jika belum memiliki kartu RFID.</p>
                            </div>
                        </section>

                        <section>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2.5">Akun Login</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="username" class="{{ $labelClass }}">Username <span class="text-rose-500">*</span></label>
                                    <input id="username" name="username" type="text" class="{{ $inputClass }}" x-model="form.username"
                                        placeholder="Username untuk login" autocomplete="off" maxlength="30" required>
                                    <p class="text-[10px] text-slate-400 mt-1 mb-0">Hanya huruf, angka, titik, garis bawah, atau strip.</p>
                                    <p x-show="errors.username" x-cloak class="mt-1 text-[11px] font-medium text-rose-600" x-text="errors.username"></p>
                                </div>
                                <div>
                                    <label for="password" class="{{ $labelClass }}">Password <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <input id="password" name="password" :type="showPassword ? 'text' : 'password'" class="{{ $inputClass }} pr-10"
                                            x-model="form.password" placeholder="Minimal 6 karakter" autocomplete="new-password"
                                            minlength="6" maxlength="72" required>
                                        <button type="button" @click="showPassword = !showPassword"
                                            class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600"
                                            :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'">
                                            <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                        </button>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-1 mb-0">Simpan password ini untuk diberikan kepada siswa.</p>
                                    <p x-show="errors.password" x-cloak class="mt-1 text-[11px] font-medium text-rose-600" x-text="errors.password"></p>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="px-5 sm:px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex flex-wrap items-center justify-end gap-2 shrink-0">
                        <button type="button" @click="createOpen = false"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition-colors">Batal</button>
                        <button type="reset"
                            class="px-4 py-2 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-xl hover:bg-amber-100 transition-colors">Reset</button>
                        <button type="submit" :disabled="saving"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed transition-colors shadow-xs shadow-blue-500/20">
                            <span x-show="saving" x-cloak class="inline-block w-3.5 h-3.5 border-2 border-white/70 border-t-transparent rounded-full animate-spin"></span>
                            <i x-show="!saving" class="bi bi-save"></i>
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Registrasi Cepat Kartu RFID / NFC Siswa -->
        <div x-show="nfcOpen" x-cloak role="dialog" aria-modal="true" aria-labelledby="quickNfcLabel"
            @keydown.escape.window="closeNfc()"
            class="fixed inset-0 z-[105] flex items-center justify-center bg-slate-900/60 p-3 sm:p-4">
            <div class="flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <!-- Header -->
                <div class="px-5 sm:px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-emerald-500 to-teal-600 text-white flex items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-10 h-10 rounded-2xl bg-white/20 text-white flex items-center justify-center text-xl shrink-0">
                            <i class="bi bi-broadcast"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 class="font-bold text-white text-base mb-0.5" id="quickNfcLabel">Scan &amp; Tautkan Kartu NFC Siswa</h2>
                            <p class="text-[11px] text-emerald-100 mb-0">Tempelkan kartu RFID ke HP untuk menyimpan langsung ke database.</p>
                        </div>
                    </div>
                    <button type="button" @click="closeNfc()"
                        class="w-8 h-8 rounded-lg text-white/80 hover:text-white hover:bg-white/10 inline-flex items-center justify-center transition-colors shrink-0"
                        aria-label="Tutup">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-5 sm:p-6 space-y-3.5 overflow-y-auto max-h-[75vh]">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label for="nfc-filter-kelas" class="block text-xs font-semibold text-slate-700 mb-1">Filter Kelas</label>
                            <select id="nfc-filter-kelas" x-model="nfcKelasFilter" @change="loadNfcList(nfcKelasFilter)"
                                class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-hidden transition-all">
                                <option value="">Semua Kelas</option>
                                <?php foreach (($kelass ?? []) as $id => $name): ?>
                                    <option value="{{ (int) $id }}">{{ $name }}</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="nfc-select-siswa" class="block text-xs font-semibold text-slate-700 mb-1">Pilih Siswa Target <span class="text-rose-500">*</span></label>
                            <select id="nfc-select-siswa" x-model="nfcSelectedId"
                                class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-hidden font-medium transition-all">
                                <option value="" x-text="nfcLoading ? '-- Memuat Siswa --' : '-- Pilih Siswa --'">-- Pilih Siswa --</option>
                                <template x-for="s in nfcList" :key="s.id_siswa">
                                    <option :value="String(s.id_siswa)" x-text="nfcLabel(s)"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Kartu Preview Siswa Terpilih -->
                    <div class="p-3 sm:p-3.5 rounded-2xl border border-slate-200/80 bg-slate-50/70 flex items-center gap-3 transition-all">
                        <img :src="nfcSelected ? (nfcSelected.foto || defaultFoto) : defaultFoto" alt="Foto siswa terpilih"
                            x-on:error="$event.target.onerror = null; $event.target.src = defaultFoto"
                            class="w-11 h-11 rounded-xl object-cover border border-slate-200 bg-white shadow-xs shrink-0">
                        <div class="min-w-0 flex-1">
                            <h3 class="font-bold text-slate-800 text-sm leading-tight truncate" x-text="nfcSelected ? nfcSelected.nama : 'Pilih siswa terlebih dahulu'"></h3>
                            <div class="flex flex-wrap items-center gap-1.5 mt-0.5 text-[11px] text-slate-500">
                                <span x-text="nfcSelected ? 'NIS: ' + (nfcSelected.nis || '-') : 'NIS: -'"></span>
                                <span>&bull;</span>
                                <span class="font-semibold text-emerald-700" x-text="nfcSelected ? (nfcSelected.nama_kelas || 'Kelas -') : '-'"></span>
                            </div>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-[10px] text-slate-400">Status:</span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold"
                                    :class="nfcSelected && nfcSelected.nomor_rfid ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200/80 text-slate-600'">
                                    <i x-show="nfcSelected && nfcSelected.nomor_rfid" class="bi bi-check-circle-fill text-emerald-600"></i>
                                    <span x-text="nfcSelected && nfcSelected.nomor_rfid ? 'RFID: ' + nfcSelected.nomor_rfid : 'Belum Ada RFID'"></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Sensor NFC & Tap Area -->
                    <div class="rounded-2xl border-2 border-dashed border-emerald-300 bg-emerald-50/40 p-4 text-center relative overflow-hidden transition-all">
                        <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500 text-white flex items-center justify-center text-xl shadow-md shadow-emerald-500/20 animate-pulse">
                            <i class="bi bi-broadcast"></i>
                        </div>
                        <p class="text-xs text-slate-600 max-w-sm mx-auto my-2" x-text="nfcStatus"></p>

                        <!-- Input Manual / USB Reader fallback -->
                        <div class="max-w-sm mx-auto mt-2">
                            <div class="relative">
                                <input type="text" x-ref="nfcManual" x-model="nfcManual" @keydown.enter.prevent="manualSave()"
                                    placeholder="Atau scan via USB Reader / ketik UID"
                                    class="w-full pl-8 pr-16 py-1.5 text-xs bg-white border border-slate-300 rounded-xl font-mono uppercase focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-hidden shadow-2xs"
                                    autocomplete="off">
                                <i class="bi bi-upc-scan absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <button type="button" @click="manualSave()" :disabled="nfcSaving"
                                    class="absolute right-1 top-1/2 -translate-y-1/2 px-2.5 py-0.5 text-[11px] font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white transition-colors">
                                    Simpan
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Auto advance & counter -->
                    <div class="flex items-center justify-between text-xs px-1">
                        <label for="nfc-auto-next" class="flex items-center gap-2 cursor-pointer select-none font-semibold text-slate-700">
                            <input type="checkbox" id="nfc-auto-next" x-model="nfcAutoNext"
                                class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                            <span>Otomatis lanjut ke siswa berikutnya</span>
                        </label>
                        <span class="text-emerald-600 font-bold" x-text="nfcSessionCount + ' tersimpan'"></span>
                    </div>

                    <!-- Log Aktivitas Scan Terbaru -->
                    <div x-show="nfcHistory.length > 0" x-cloak>
                        <div class="space-y-1 max-h-24 overflow-y-auto pr-1 text-xs">
                            <template x-for="(log, i) in nfcHistory" :key="i">
                                <div class="p-2 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-between text-[11px]">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="w-6 h-6 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0 text-xs font-bold">
                                            <i class="bi bi-check-lg"></i>
                                        </span>
                                        <div class="truncate">
                                            <span class="font-bold text-slate-800" x-text="log.nama"></span>
                                            <span class="text-slate-400 text-[10px]">&bull; RFID: <strong class="font-mono text-emerald-700" x-text="log.rfid"></strong></span>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-slate-400 shrink-0" x-text="log.time"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-5 sm:px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between shrink-0">
                    <button type="button" @click="removeRfid()" x-show="nfcSelected && nfcSelected.nomor_rfid" x-cloak
                        class="px-3 py-1.5 text-xs font-semibold text-rose-600 hover:text-rose-700 hover:bg-rose-50 border border-rose-200 rounded-xl transition-colors">
                        <i class="bi bi-trash3 mr-1"></i> Lepas Kartu Siswa Ini
                    </button>
                    <div class="ml-auto">
                        <button type="button" @click="closeNfc()"
                            class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-100 transition-colors">Selesai</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('siswaPage', () => ({
        isAdmin: false,
        hasPeriode: false,
        urls: {},
        csrf: '',
        defaultFoto: '',

        rows: [],
        stats: { total: 0, aktif: 0, tanpa_kelas: 0, akun_nonaktif: 0 },
        loading: false,
        errorMessage: '',
        page: 1,
        perPage: 10,
        total: 0,
        pages: 0,
        query: '',
        statusFilter: '0',
        kelasFilter: '',
        requestSeq: 0,

        selected: [],
        bulkOpen: false,

        createOpen: false,
        saving: false,
        showPassword: false,
        errors: {},
        form: {
            nama_siswa: '',
            nis: '',
            nisn: '',
            jenis_kelamin: '',
            agama: '',
            kelas_awal: '',
            tahun_masuk: '',
            nomor_rfid: '',
            username: '',
            password: '',
        },

        nfcOpen: false,
        nfcList: [],
        nfcLoading: false,
        nfcSelectedId: '',
        nfcKelasFilter: '',
        nfcSessionCount: 0,
        nfcAutoNext: true,
        nfcManual: '',
        nfcSaving: false,
        nfcStatus: 'Tempelkan kartu RFID siswa ke bagian belakang HP Android Anda.',
        nfcHistory: [],
        nfcReader: null,
        nfcController: null,
        fieldReader: null,
        fieldController: null,

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');
            this.isAdmin = !!cfg.isAdmin;
            this.hasPeriode = !!cfg.hasPeriode;
            this.urls = cfg.urls || {};
            this.csrf = cfg.csrf || '';
            this.defaultFoto = cfg.defaultFoto || '';

            if (this.hasPeriode) {
                this.load();
            } else {
                this.errorMessage = 'Tahun Pelajaran / Semester aktif belum diset.';
            }
        },

        get nfcSelected() {
            return this.nfcList.find((s) => String(s.id_siswa) === String(this.nfcSelectedId)) || null;
        },

        format(value) {
            return Number(value || 0).toLocaleString('id-ID');
        },

        hasFilter() {
            return this.query.trim() !== '' || this.statusFilter !== '0' || this.kelasFilter !== '';
        },

        rowNo(idx) {
            return (this.page - 1) * this.perPage + idx + 1;
        },

        rowMeta(row) {
            const parts = [];
            const jk = String(row.jenis_kelamin || '').toUpperCase();
            if (jk === 'L') parts.push('Laki-laki');
            if (jk === 'P') parts.push('Perempuan');
            if (row.agama) parts.push(row.agama);
            if (row.username) parts.push('@' + row.username);
            return parts.join(' \u00b7 ');
        },

        statusMeta(status) {
            const map = {
                '1': { label: 'Aktif', cls: 'bg-emerald-50 text-emerald-700 border-emerald-100', dot: 'bg-emerald-500' },
                '2': { label: 'Lulus', cls: 'bg-blue-50 text-blue-700 border-blue-100', dot: 'bg-blue-500' },
                '3': { label: 'Pindah', cls: 'bg-amber-50 text-amber-700 border-amber-100', dot: 'bg-amber-500' },
                '4': { label: 'Keluar', cls: 'bg-rose-50 text-rose-700 border-rose-100', dot: 'bg-rose-500' },
            };
            return map[String(status || '')] || {
                label: 'Belum terdaftar',
                cls: 'bg-slate-50 text-slate-500 border-slate-200',
                dot: 'bg-slate-400',
            };
        },

        emptyTitle() {
            if (this.errorMessage) return this.errorMessage;
            return this.hasFilter() ? 'Tidak ada siswa yang cocok' : 'Belum ada data siswa';
        },

        emptyDetail() {
            if (this.errorMessage) return 'Periksa pengaturan periode atau coba muat ulang halaman ini.';
            if (this.hasFilter()) return 'Ubah kata kunci, status, atau kelas lalu coba lagi.';
            return this.isAdmin
                ? 'Tambahkan siswa baru atau import dari file Excel untuk mulai mengelola data.'
                : 'Data siswa belum tersedia.';
        },

        summaryText() {
            if (!this.rows.length) return 'Menampilkan 0 entri';
            const from = (this.page - 1) * this.perPage + 1;
            const to = from + this.rows.length - 1;
            return 'Menampilkan ' + from + '\u2013' + to + ' dari ' + this.format(this.total) + ' siswa';
        },

        pageList() {
            const total = this.pages;
            if (total <= 0) return [];
            const windowSize = 5;
            let start = Math.max(1, this.page - Math.floor(windowSize / 2));
            const end = Math.min(total, start + windowSize - 1);
            start = Math.max(1, end - windowSize + 1);
            const list = [];
            for (let p = start; p <= end; p++) list.push(p);
            return list;
        },

        editUrl(id) {
            return String(this.urls.edit || '').replace(/\/$/, '') + '/' + id;
        },

        async load() {
            if (!this.hasPeriode) {
                this.rows = [];
                return;
            }

            const seq = ++this.requestSeq;
            this.loading = true;
            this.errorMessage = '';
            this.selected = [];
            this.bulkOpen = false;

            const body = new URLSearchParams({
                page: String(this.page),
                limit: String(this.perPage),
                search: this.query.trim(),
                status: this.statusFilter,
                kelas: this.kelasFilter,
            });
            body.append('_token', this.csrf);

            try {
                const response = await fetch(this.urls.list, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });

                if (seq !== this.requestSeq) return;

                if (response.status === 401) throw new Error('Sesi Anda berakhir. Silakan login kembali.');
                if (response.status === 403) throw new Error('Anda tidak memiliki akses ke daftar siswa.');
                if (!response.ok) throw new Error('Gagal memuat data siswa. Periksa koneksi lalu coba lagi.');

                const data = await response.json();
                if (seq !== this.requestSeq) return;

                if (!data || data.status === false) {
                    this.rows = [];
                    this.pages = 0;
                    this.total = 0;
                    this.errorMessage = (data && data.message) || 'Gagal memuat data.';
                    return;
                }

                if (data.message) {
                    this.rows = [];
                    this.pages = 0;
                    this.total = 0;
                    this.errorMessage = data.message;
                    return;
                }

                if (data.stats) this.stats = data.stats;

                this.total = Number(data.total) || 0;
                this.pages = Number(data.pages) || 0;

                if (this.pages > 0 && this.page > this.pages) {
                    this.page = this.pages;
                    this.loading = false;
                    this.load();
                    return;
                }

                this.rows = Array.isArray(data.lists) ? data.lists : [];
            } catch (error) {
                if (seq !== this.requestSeq) return;
                this.rows = [];
                this.pages = 0;
                this.total = 0;
                this.errorMessage = error.message || 'Terjadi kesalahan saat memuat data siswa.';
            } finally {
                if (seq === this.requestSeq) this.loading = false;
            }
        },

        reload() {
            this.load();
        },

        applyFilters() {
            this.page = 1;
            this.load();
        },

        clearSearch() {
            this.query = '';
            this.applyFilters();
            this.$nextTick(() => this.$refs.search?.focus());
        },

        resetFilters() {
            this.query = '';
            this.statusFilter = '0';
            this.kelasFilter = '';
            this.applyFilters();
        },

        filterTanpaKelas() {
            if (!this.hasPeriode) return;
            this.kelasFilter = 'tanpa';
            this.applyFilters();
        },

        goPage(target) {
            if (target < 1 || target > this.pages || target === this.page) return;
            this.page = target;
            this.load();
        },

        allSelected() {
            return this.rows.length > 0 && this.rows.every((row) => this.selected.includes(Number(row.id_siswa)));
        },

        toggleAll(event) {
            this.selected = event.target.checked
                ? this.rows.map((row) => Number(row.id_siswa))
                : [];
        },

        toggleOne(event, id) {
            const value = Number(id);
            if (event.target.checked) {
                if (!this.selected.includes(value)) this.selected.push(value);
            } else {
                this.selected = this.selected.filter((item) => item !== value);
            }
        },

        async bulkAction(action) {
            if (this.selected.length === 0) return;
            this.bulkOpen = false;

            const count = this.selected.length;
            const config = {
                hapus: {
                    title: 'Hapus siswa terpilih?',
                    html: '<b>' + count + ' siswa</b> akan dihapus permanen dari data siswa, kelas, dan buku induk. Tindakan ini tidak dapat dibatalkan.',
                    icon: 'warning',
                    confirm: 'Ya, Hapus',
                    color: '#e11d48',
                },
                pindah: {
                    title: 'Tandai sebagai PINDAH?',
                    html: 'Status <b>' + count + ' siswa</b> terpilih akan diubah menjadi <b>Pindah</b>.',
                    icon: 'question',
                    confirm: 'Ya, Lanjutkan',
                    color: '#d97706',
                },
                keluar: {
                    title: 'Tandai sebagai KELUAR?',
                    html: 'Status <b>' + count + ' siswa</b> terpilih akan diubah menjadi <b>Keluar</b>.',
                    icon: 'question',
                    confirm: 'Ya, Lanjutkan',
                    color: '#475569',
                },
            }[action];

            if (!config) return;

            const result = await Swal.fire({
                title: config.title,
                html: config.html,
                icon: config.icon,
                showCancelButton: true,
                confirmButtonColor: config.color,
                cancelButtonColor: '#64748b',
                confirmButtonText: config.confirm,
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Memproses...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading(),
            });

            const body = new FormData();
            this.selected.forEach((id) => body.append('checked[]', id));
            body.append('aksi', action);
            body.append('_token', this.csrf);

            try {
                const response = await fetch(this.urls.delete, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });

                if (response.status === 403) throw new Error('Anda tidak memiliki akses untuk mengubah data siswa.');
                if (!response.ok) throw new Error('Terjadi kesalahan pada server. Coba lagi.');

                const data = await response.json();
                if (data && data.status) {
                    this.selected = [];
                    await Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: data.message || 'Data berhasil diperbarui.',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                    this.load();
                } else {
                    Swal.fire('Gagal', (data && data.message) || 'Data gagal diproses.', 'error');
                }
            } catch (error) {
                Swal.fire('Gagal', error.message || 'Terjadi kesalahan pada server. Coba lagi.', 'error');
            }
        },

        openCreate() {
            this.createOpen = true;
            this.errors = {};
            this.showPassword = false;
            this.$nextTick(() => document.getElementById('nama_siswa')?.focus());
        },

        resetCreateForm() {
            this.form = {
                nama_siswa: '',
                nis: '',
                nisn: '',
                jenis_kelamin: '',
                agama: '',
                kelas_awal: '',
                tahun_masuk: '',
                nomor_rfid: '',
                username: '',
                password: '',
            };
            this.errors = {};
            this.showPassword = false;
        },

        async createSiswa() {
            const formEl = this.$refs.formSiswa;
            if (formEl && !formEl.reportValidity()) return;

            this.saving = true;
            this.errors = {};

            const body = new URLSearchParams(new FormData(formEl));
            body.append('_token', this.csrf);

            try {
                const response = await fetch(this.urls.create, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });

                if (response.status === 403) throw new Error('Anda tidak memiliki akses untuk menambah siswa.');
                if (!response.ok) throw new Error('Terjadi kesalahan pada server. Coba lagi.');

                const data = await response.json();

                if (data && data.insert) {
                    this.createOpen = false;
                    const rfid = this.form.nomor_rfid;
                    this.resetCreateForm();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: data.text || 'Siswa berhasil ditambahkan.',
                        timer: 1600,
                        showConfirmButton: false,
                    });
                    this.page = 1;
                    this.load();
                    if (rfid) this.nfcKelasFilter = '';
                } else {
                    this.errors = (data && data.error) || {};
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal menyimpan',
                        text: (data && data.text) || 'Periksa kembali data siswa.',
                    });
                }
            } catch (error) {
                Swal.fire('Gagal', error.message || 'Terjadi kesalahan pada server. Coba lagi.', 'error');
            } finally {
                this.saving = false;
            }
        },

        async scanField() {
            if (!('NDEFReader' in window)) {
                this.nfcUnsupported();
                return;
            }

            try {
                if (this.fieldController) this.fieldController.abort();
                this.fieldController = new AbortController();
                this.fieldReader = new NDEFReader();
                await this.fieldReader.scan({ signal: this.fieldController.signal });

                Swal.fire({
                    title: 'Tempelkan Kartu NFC',
                    text: 'Dekatkan kartu RFID siswa ke bagian belakang HP Anda sekarang...',
                    showCancelButton: true,
                    cancelButtonText: 'Batal',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                this.fieldReader.onreading = (event) => {
                    const clean = String(event.serialNumber || '').replace(/[: -]/g, '').toUpperCase();
                    if (!clean) return;
                    if (navigator.vibrate) navigator.vibrate([100, 50, 100]);
                    this.form.nomor_rfid = clean;
                    this.fieldController?.abort();
                    Swal.fire({
                        icon: 'success',
                        title: 'Kartu Terbaca!',
                        text: 'UID Kartu: ' + clean,
                        timer: 1800,
                        showConfirmButton: false,
                    });
                };
            } catch (error) {
                Swal.fire('Gagal', error.message || 'Tidak dapat membaca NFC.', 'error');
            }
        },

        nfcUnsupported() {
            Swal.fire({
                icon: 'info',
                title: 'Web NFC',
                html: '<div class="text-left text-xs space-y-1.5 text-slate-600">'
                    + '<p>Fitur Tap NFC langsung di browser membutuhkan:</p>'
                    + '<ul class="list-disc pl-4 space-y-1">'
                    + '<li>Smartphone <strong>Android</strong> ber-NFC.</li>'
                    + '<li>Browser <strong>Google Chrome</strong> Android.</li>'
                    + '<li>Akses melalui protokol <strong>HTTPS</strong>.</li>'
                    + '</ul>'
                    + '<p class="mt-2 text-slate-500">Anda juga dapat mengetikkan UID kartu secara manual atau menggunakan USB RFID reader.</p>'
                    + '</div>',
                confirmButtonText: 'Tutup',
            });
        },

        openNfc(preselectId = null, preselectKelas = null) {
            this.nfcOpen = true;
            this.nfcStatus = 'Tempelkan kartu RFID siswa ke bagian belakang HP Android Anda.';
            this.nfcManual = '';

            let filterKelas = '';
            if (preselectKelas) {
                filterKelas = String(preselectKelas);
            } else {
                filterKelas = this.kelasFilter === 'tanpa' ? '' : this.kelasFilter;
            }
            this.nfcKelasFilter = filterKelas;
            this.nfcSelectedId = preselectId ? String(preselectId) : '';

            this.loadNfcList(filterKelas, preselectId);
            this.startNfcScan();

            this.$nextTick(() => {
                setTimeout(() => this.$refs.nfcManual?.focus(), 150);
            });
        },

        closeNfc() {
            this.stopNfcScan();
            this.nfcOpen = false;
            this.load();
        },

        nfcLabel(s) {
            const base = (s.nama || '-') + ' (' + (s.nis || '-') + ')';
            return base + (s.nomor_rfid ? ' [\u2713 ' + s.nomor_rfid + ']' : ' [Belum ada RFID]');
        },

        async loadNfcList(idKelas = '', selectId = null) {
            this.nfcLoading = true;
            try {
                const url = String(this.urls.siswaByKelas || '').replace(/\/$/, '') + (idKelas ? '/' + idKelas : '');
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await response.json();
                this.nfcList = (data && Array.isArray(data.lists)) ? data.lists : [];

                if (selectId && this.nfcList.some((s) => String(s.id_siswa) === String(selectId))) {
                    this.nfcSelectedId = String(selectId);
                    return;
                }

                if (selectId && idKelas) {
                    // Siswa yang dipilih tidak ada di kelas tersebut: muat semua kelas.
                    this.nfcKelasFilter = '';
                    this.nfcLoading = false;
                    return this.loadNfcList('', selectId);
                }

                const target = this.nfcList.find((s) => !s.nomor_rfid) || this.nfcList[0] || null;
                this.nfcSelectedId = target ? String(target.id_siswa) : '';
            } catch (error) {
                this.nfcList = [];
                this.nfcSelectedId = '';
                this.nfcStatus = 'Gagal memuat daftar siswa. Periksa koneksi lalu coba lagi.';
            } finally {
                this.nfcLoading = false;
            }
        },

        async startNfcScan() {
            if (!('NDEFReader' in window)) {
                this.nfcStatus = 'Mode Reader USB / Input Manual aktif. Tempelkan kartu pada USB RFID Reader atau ketik UID di bawah.';
                return;
            }

            try {
                this.stopNfcScan();
                this.nfcController = new AbortController();
                this.nfcReader = new NDEFReader();
                await this.nfcReader.scan({ signal: this.nfcController.signal });

                this.nfcStatus = 'Sensor NFC HP aktif. Tempelkan kartu RFID siswa ke belakang HP...';

                this.nfcReader.onreading = (event) => {
                    const cleanUid = String(event.serialNumber || '').replace(/[: -]/g, '').toUpperCase();
                    if (!cleanUid) return;

                    if (!this.nfcSelectedId) {
                        if (navigator.vibrate) navigator.vibrate(200);
                        Swal.fire({
                            icon: 'warning',
                            title: 'Pilih Siswa',
                            text: 'Pilih siswa target terlebih dahulu sebelum menempelkan kartu.',
                            timer: 2000,
                            showConfirmButton: false,
                        });
                        return;
                    }

                    if (navigator.vibrate) navigator.vibrate([80, 40, 80]);
                    this.saveRfid(this.nfcSelectedId, cleanUid);
                };

                this.nfcReader.onreadingerror = () => {
                    if (navigator.vibrate) navigator.vibrate(200);
                    this.nfcStatus = 'Gagal membaca kartu. Pastikan kartu tetap menempel sejenak.';
                    setTimeout(() => {
                        if (this.nfcOpen && this.nfcController) {
                            this.nfcStatus = 'Sensor NFC HP aktif. Tempelkan kartu RFID siswa ke belakang HP...';
                        }
                    }, 1800);
                };
            } catch (error) {
                this.nfcStatus = 'NFC belum aktif (' + (error.message || error.name) + '). Pastikan sensor NFC diaktifkan di Pengaturan HP Anda.';
            }
        },

        stopNfcScan() {
            if (this.nfcController) {
                this.nfcController.abort();
                this.nfcController = null;
            }
            this.nfcReader = null;
        },

        beep() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(1320, ctx.currentTime + 0.15);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.2);
            } catch (error) { /* audio opsional */ }
        },

        async saveRfid(idSiswa, rfidNumber) {
            const id = Number(idSiswa);
            const cleanUid = String(rfidNumber || '').replace(/[: -]/g, '').toUpperCase();
            if (!id || !cleanUid || this.nfcSaving) return;

            this.nfcSaving = true;
            this.nfcStatus = 'Menyimpan kartu ' + cleanUid + ' ke database...';

            const body = new FormData();
            body.append('id_siswa', id);
            body.append('nomor_rfid', cleanUid);
            body.append('_token', this.csrf);

            try {
                const response = await fetch(this.urls.assignRfid, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });
                const data = await response.json();

                if (data && data.status) {
                    this.nfcSessionCount++;
                    this.beep();

                    const item = this.nfcList.find((s) => Number(s.id_siswa) === id);
                    if (item) item.nomor_rfid = cleanUid;

                    this.nfcHistory.unshift({
                        nama: data.nama || (item ? item.nama : 'Siswa'),
                        rfid: cleanUid,
                        time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
                    });
                    this.nfcHistory = this.nfcHistory.slice(0, 8);

                    const next = this.nfcAutoNext ? this.findNextWithoutRfid(id) : null;
                    if (next) {
                        this.nfcSelectedId = String(next.id_siswa);
                        this.nfcStatus = 'Tersimpan! Siap scan kartu untuk: ' + next.nama;
                    } else if (this.nfcAutoNext) {
                        this.nfcStatus = 'Kartu ' + cleanUid + ' tersimpan! Semua siswa di kelas ini sudah memiliki kartu RFID.';
                    } else {
                        this.nfcStatus = 'Kartu ' + cleanUid + ' berhasil disimpan!';
                    }

                    this.load();
                } else {
                    const message = (data && data.message) || 'Terjadi kesalahan saat menyimpan nomor RFID.';
                    this.nfcStatus = message;
                    Swal.fire({ icon: 'error', title: 'Gagal Menyimpan Kartu', text: message });
                }
            } catch (error) {
                this.nfcStatus = error.message || 'Terjadi kesalahan server.';
                Swal.fire('Error', this.nfcStatus, 'error');
            } finally {
                this.nfcSaving = false;
            }
        },

        findNextWithoutRfid(currentId) {
            const index = this.nfcList.findIndex((s) => Number(s.id_siswa) === Number(currentId));
            if (index < 0) return null;

            for (let i = index + 1; i < this.nfcList.length; i++) {
                if (!this.nfcList[i].nomor_rfid) return this.nfcList[i];
            }
            for (let i = 0; i < index; i++) {
                if (!this.nfcList[i].nomor_rfid) return this.nfcList[i];
            }
            return null;
        },

        manualSave() {
            const uid = String(this.nfcManual || '').trim();
            if (!uid) {
                Swal.fire('Peringatan', 'Ketik nomor UID kartu terlebih dahulu.', 'warning');
                return;
            }
            if (!this.nfcSelectedId) {
                Swal.fire('Peringatan', 'Pilih siswa target terlebih dahulu.', 'warning');
                return;
            }
            this.saveRfid(this.nfcSelectedId, uid);
            this.nfcManual = '';
        },

        async removeRfid() {
            if (!this.nfcSelectedId) return;
            const siswa = this.nfcSelected;
            const nama = siswa ? siswa.nama : 'siswa ini';

            const result = await Swal.fire({
                title: 'Lepas Kartu RFID?',
                text: 'Nomor kartu RFID ' + nama + ' akan dihapus dari sistem.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Ya, Lepas',
                cancelButtonText: 'Batal',
            });
            if (!result.isConfirmed) return;

            const body = new FormData();
            body.append('id_siswa', this.nfcSelectedId);
            body.append('nomor_rfid', '');
            body.append('_token', this.csrf);

            try {
                const response = await fetch(this.urls.assignRfid, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });
                const data = await response.json();

                if (data && data.status) {
                    if (siswa) siswa.nomor_rfid = '';
                    this.nfcStatus = 'Kartu RFID telah dilepas.';
                    this.load();
                    Swal.fire('Berhasil', 'Kartu RFID telah dilepas.', 'success');
                } else {
                    Swal.fire('Gagal', (data && data.message) || 'Gagal melepas kartu.', 'error');
                }
            } catch (error) {
                Swal.fire('Gagal', 'Terjadi kesalahan pada server. Coba lagi.', 'error');
            }
        },
    }));
});
</script>
@endsection
