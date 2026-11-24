@extends('layouts.main')

@section('content')

<?php
$currentUserId = (int) ($user->id ?? 0);
$isSuperAdmin = $currentUserId === 1;

$pageConfig = [
    'csrf' => csrf_token(),
    'avatarFallback' => asset('assets/img/siswa_sm.png'),
    'urls' => [
        'adminCreate' => url('useradmin/create'),
        'adminDelete' => url('useradmin/delete'),
        'adminEdit' => url('useradmin/edit'),
        'guruData' => url('userguru/data'),
        'guruEdit' => url('userguru/edit'),
        'guruActivate' => url('userguru/activate'),
        'guruResetLogin' => url('userguru/reset_login'),
        'guruResetPassword' => url('userguru/reset_password'),
        'guruBulk' => url('userguru/aktifkansemua'),
        'siswaList' => url('usersiswa/list'),
        'siswaActivate' => url('usersiswa/activate'),
        'siswaResetLogin' => url('usersiswa/reset_login'),
        'siswaResetPassword' => url('usersiswa/reset_password'),
        'siswaBulk' => url('usersiswa/aktifkansemua'),
    ],
];
?>

<div x-data="userManagementPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <!-- Content Header -->
    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'Manajemen Pengguna'"
            icon="bi-people-fill"
            :subtitle="'Kelola akun Admin, Guru, dan Siswa dalam satu halaman · ' . ($totalAdmin + $totalGuru + $totalSiswa) . ' akun'"
            :crumbs="['Manajemen Pengguna']" />
    </div>

    <div class="mt-4 space-y-5 px-4 pb-6 md:px-6">

        <!-- Tab Navigation -->
        <div class="flex w-fit max-w-full items-center gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1" role="tablist">
            <button type="button" role="tab" :aria-selected="tab === 'admin'" @click="setTab('admin')"
                :class="tab === 'admin' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                class="flex items-center gap-2 whitespace-nowrap rounded-lg px-5 py-2.5 text-sm font-semibold transition-colors">
                <i class="bi bi-shield-lock-fill text-blue-500"></i> Admin
                <span class="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-blue-100 px-1 text-[10px] font-extrabold text-blue-700">{{ $totalAdmin }}</span>
            </button>
            <button type="button" role="tab" :aria-selected="tab === 'guru'" @click="setTab('guru')"
                :class="tab === 'guru' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                class="flex items-center gap-2 whitespace-nowrap rounded-lg px-5 py-2.5 text-sm font-semibold transition-colors">
                <i class="bi bi-person-video3 text-emerald-500"></i> Guru
                <span class="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-emerald-100 px-1 text-[10px] font-extrabold text-emerald-700">{{ $totalGuru }}</span>
            </button>
            <button type="button" role="tab" :aria-selected="tab === 'siswa'" @click="setTab('siswa')"
                :class="tab === 'siswa' ? 'bg-white text-violet-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                class="flex items-center gap-2 whitespace-nowrap rounded-lg px-5 py-2.5 text-sm font-semibold transition-colors">
                <i class="bi bi-mortarboard-fill text-violet-500"></i> Siswa
                <span class="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-violet-100 px-1 text-[10px] font-extrabold text-violet-700">{{ $totalSiswa }}</span>
            </button>
        </div>

        <!-- ══════════════════ TAB: ADMIN ══════════════════ -->
        <div x-show="tab === 'admin'" x-cloak>
            <x-ui.card title="Daftar Administrator" icon="bi-shield-lock" tone="blue"
                subtitle="Akun dengan akses penuh sistem" :body-class="'p-0'"
                x-data="dataTable({ perPage: 10 })">

                <x-slot:actions>
                    <button type="button" @click="reload()"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-xs transition-colors hover:border-slate-300 hover:bg-slate-50">
                        <i class="bi bi-arrow-clockwise"></i> Reload
                    </button>
                    <button type="button" @click="openCreate()"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                        <i class="bi bi-plus-lg"></i> Tambah Admin
                    </button>
                </x-slot:actions>

                <x-slot:toolbar>
                    <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                        <div class="flex items-center gap-1.5 text-xs font-medium text-slate-500">
                            <label for="admin-per-page">Tampilkan</label>
                            <select id="admin-per-page" x-model.number="perPage" @change="page = 1; refresh()"
                                aria-label="Jumlah data per halaman"
                                class="rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span>data</span>
                        </div>

                        <div class="relative w-full sm:w-64">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-slate-400">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="search" x-model="query" placeholder="Cari nama atau username..." aria-label="Cari admin"
                                class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-8 pr-8 text-xs outline-hidden transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                            <button type="button" x-show="query.length > 0" x-cloak @click="query = ''"
                                class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600"
                                aria-label="Hapus pencarian">
                                <i class="bi bi-x-circle-fill text-xs"></i>
                            </button>
                        </div>
                    </div>
                </x-slot:toolbar>

                @if (count($admins) === 0)
                    <div class="p-5">
                        <x-ui.empty icon="bi-shield-x" title="Belum ada administrator"
                            description="Tambahkan akun administrator untuk mulai mengelola sistem." />
                    </div>
                @else
                    <x-ui.table>
                        <x-slot:head>
                            <th class="w-12 px-3 py-3 text-center">No.</th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Username</th>
                            <th class="hidden px-4 py-3 md:table-cell">Email</th>
                            <th class="hidden w-24 px-3 py-3 text-center sm:table-cell">Level</th>
                            <th class="w-24 px-3 py-3 text-center">Status</th>
                            <th class="w-28 px-3 py-3 text-center">Aksi</th>
                        </x-slot:head>

                        @foreach ($admins as $num => $admin)
                            <tr class="transition-colors hover:bg-slate-50/60"
                                data-search="{{ strtolower(trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? '') . ' ' . $admin->username . ' ' . ($admin->level ?? ''))) }}">
                                <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400" data-rownum>{{ $num + 1 }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-xs font-bold text-white">
                                            {{ strtoupper(mb_substr((string) $admin->first_name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold leading-tight text-slate-800">
                                                {{ trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? '')) }}
                                            </p>
                                            @if (! empty($admin->email))
                                                <p class="mt-0.5 truncate text-[10px] text-slate-400 md:hidden">{{ $admin->email }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-600">
                                        {{ $admin->username }}
                                    </span>
                                </td>
                                <td class="hidden px-4 py-2.5 text-xs text-slate-500 md:table-cell">{{ $admin->email ?: '-' }}</td>
                                <td class="hidden px-3 py-2.5 text-center sm:table-cell">
                                    <span class="inline-flex items-center rounded-full border border-blue-100 bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                        {{ $admin->level ?? 'Admin' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    @if ($admin->active == 1)
                                        <span class="inline-flex items-center gap-1 rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                            <i class="bi bi-check-circle-fill text-[9px]"></i> Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full border border-rose-100 bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700">
                                            <i class="bi bi-x-circle-fill text-[9px]"></i> Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex justify-center gap-1">
                                        @if ((int) $admin->id === $currentUserId)
                                            <a href="{{ url('useradmin/edit/' . $admin->id) }}" title="Edit Profil Saya"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 shadow-xs transition-colors hover:bg-blue-100">
                                                <i class="bi bi-person-gear text-sm"></i>
                                            </a>
                                        @elseif ($isSuperAdmin)
                                            <a href="{{ url('useradmin/edit/' . $admin->id) }}" title="Edit"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 shadow-xs transition-colors hover:bg-indigo-100">
                                                <i class="bi bi-pencil text-sm"></i>
                                            </a>
                                            <button type="button" title="Hapus"
                                                @click="deleteAdmin({{ (int) $admin->id }}, @js(trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''))))"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 shadow-xs transition-colors hover:bg-rose-100">
                                                <i class="bi bi-trash3 text-sm"></i>
                                            </button>
                                        @else
                                            <span class="text-slate-300" title="Tidak ada akses">
                                                <i class="bi bi-ban"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>

                    <div class="flex flex-col justify-between gap-3 border-t border-slate-100 px-5 py-3.5 sm:flex-row sm:items-center">
                        <div class="text-xs font-medium text-slate-500" aria-live="polite"
                            x-text="total === 0 ? 'Tidak ada data' : 'Menampilkan ' + from + '\u2013' + to + ' dari ' + total + ' admin'"></div>
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
                @endif
            </x-ui.card>
        </div>

        <!-- ══════════════════ TAB: GURU ══════════════════ -->
        <div x-show="tab === 'guru'" x-cloak>
            <x-ui.card title="Daftar Guru" icon="bi-person-video3" tone="emerald" :body-class="'p-0'">
                <x-slot:meta>
                    <span class="text-xs text-slate-500"
                        x-text="guruLoading ? 'Memuat data...' : guruFiltered.length + ' guru' + (guruQuery ? ' (filter: \u201c' + guruQuery + '\u201d)' : ' terdaftar')"></span>
                </x-slot:meta>

                <x-slot:actions>
                    <button type="button" @click="bulkGuru('aktifkan')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100">
                        <i class="bi bi-people-fill"></i> Aktifkan Semua
                    </button>
                    <button type="button" @click="bulkGuru('nonaktifkan')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-100">
                        <i class="bi bi-slash-circle"></i> Nonaktifkan Semua
                    </button>
                    <button type="button" @click="loadGuru()" title="Reload"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500 transition-colors hover:bg-slate-100">
                        <i class="bi bi-arrow-clockwise text-sm"></i>
                    </button>
                </x-slot:actions>

                <x-slot:toolbar>
                    <div class="relative w-full sm:w-64">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-slate-400">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="search" x-model="guruQuery" placeholder="Cari nama guru..." aria-label="Cari guru"
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-8 pr-8 text-xs outline-hidden transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                        <button type="button" x-show="guruQuery.length > 0" x-cloak @click="guruQuery = ''"
                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600"
                            aria-label="Hapus pencarian">
                            <i class="bi bi-x-circle-fill text-xs"></i>
                        </button>
                    </div>
                </x-slot:toolbar>

                <x-ui.table>
                    <x-slot:head>
                        <th class="w-12 px-3 py-3 text-center">No.</th>
                        <th class="px-4 py-3">Nama Guru</th>
                        <th class="hidden px-4 py-3 sm:table-cell">Username</th>
                        <th class="hidden w-28 px-3 py-3 text-center md:table-cell">Jabatan</th>
                        <th class="w-28 px-3 py-3 text-center">Reset</th>
                        <th class="w-24 px-3 py-3 text-center">Status</th>
                        <th class="w-24 px-3 py-3 text-center">Aksi</th>
                    </x-slot:head>

                    <tr x-show="guruLoading" x-cloak>
                        <td colspan="7" class="px-5 py-10 text-center text-xs font-medium text-slate-400">
                            <i class="bi bi-arrow-repeat mr-1 inline-block animate-spin"></i> Memuat data guru...
                        </td>
                    </tr>

                    <tr x-show="!guruLoading && guruFiltered.length === 0" x-cloak>
                        <td colspan="7" class="px-5 py-10 text-center text-xs font-medium text-slate-400">
                            <i class="bi bi-person-x mr-1"></i> Tidak ada data guru
                        </td>
                    </tr>

                    <template x-for="(guru, index) in guruFiltered" :key="guru.id ?? index">
                        <tr class="transition-colors hover:bg-slate-50/60">
                            <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400" x-text="index + 1"></td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <img :src="guru.foto || config.avatarFallback" x-on:error="$el.src = config.avatarFallback"
                                        alt="" class="h-9 w-9 shrink-0 rounded-lg border border-slate-200 object-cover">
                                    <span class="max-w-[180px] truncate font-semibold text-slate-800" x-text="guru.nama_guru || '-'"></span>
                                </div>
                            </td>
                            <td class="hidden px-4 py-2.5 sm:table-cell">
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-600" x-text="guru.username || '-'"></span>
                            </td>
                            <td class="hidden px-3 py-2.5 text-center md:table-cell">
                                <span class="inline-flex items-center rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700" x-text="guru.level || '-'"></span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <button type="button" :disabled="!hasReset(guru.reset)"
                                    @click="resetLoginGuru(guru)"
                                    :title="hasReset(guru.reset) ? 'Reset ' + guru.reset + ' login gagal' : 'Tidak ada login gagal'"
                                    :class="hasReset(guru.reset)
                                        ? 'bg-rose-50 text-rose-600 hover:bg-rose-100'
                                        : 'bg-slate-100 text-slate-300 cursor-not-allowed'"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg shadow-xs transition-colors">
                                    <i class="bi bi-arrow-repeat text-sm"></i>
                                </button>
                                <button type="button" @click="resetPasswordGuru(guru)"
                                    title="Reset password ke default (123456)"
                                    class="ml-1 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 shadow-xs transition-colors hover:bg-amber-100">
                                    <i class="bi bi-key-fill text-sm"></i>
                                </button>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span x-show="isActive(guru.active)"
                                    class="inline-flex items-center gap-1 rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                    <i class="bi bi-check-circle-fill text-[9px]"></i> Aktif
                                </span>
                                <span x-show="!isActive(guru.active)" x-cloak
                                    class="inline-flex items-center gap-1 rounded-full border border-rose-100 bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700">
                                    <i class="bi bi-x-circle-fill text-[9px]"></i> Nonaktif
                                </span>
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="flex justify-center gap-1">
                                    <button type="button" @click="toggleGuru(guru)"
                                        :title="isActive(guru.active) ? 'Nonaktifkan' : 'Aktifkan'"
                                        :class="isActive(guru.active)
                                            ? 'bg-amber-50 text-amber-600 hover:bg-amber-100'
                                            : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg shadow-xs transition-colors">
                                        <i class="bi text-sm" :class="isActive(guru.active) ? 'bi-slash-circle' : 'bi-person-check-fill'"></i>
                                    </button>
                                    <a :href="config.urls.guruEdit + '/' + encodeURIComponent(guru.id_guru)" title="Edit"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 shadow-xs transition-colors hover:bg-blue-100">
                                        <i class="bi bi-pencil text-sm"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>
                </x-ui.table>
            </x-ui.card>
        </div>

        <!-- ══════════════════ TAB: SISWA ══════════════════ -->
        <div x-show="tab === 'siswa'" x-cloak>
            <x-ui.card title="Daftar Siswa" icon="bi-mortarboard-fill" tone="violet" :body-class="'p-0'">
                <x-slot:meta>
                    <span class="text-xs text-slate-500"
                        x-text="siswaLoading ? 'Memuat data...' : siswaMeta.total + ' siswa' + (siswaAppliedQuery ? ' (filter: \u201c' + siswaAppliedQuery + '\u201d)' : '')"></span>
                </x-slot:meta>

                <x-slot:actions>
                    <button type="button" @click="bulkSiswa('aktifkan')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-violet-200 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 transition-colors hover:bg-violet-100">
                        <i class="bi bi-people-fill"></i> Aktifkan Semua
                    </button>
                    <button type="button" @click="bulkSiswa('nonaktifkan')"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-100">
                        <i class="bi bi-slash-circle"></i> Nonaktifkan Semua
                    </button>
                </x-slot:actions>

                <x-slot:toolbar>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative min-w-[180px] flex-1 sm:max-w-xs">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-slate-400">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="search" x-model="siswaQuery" @keydown.enter="loadSiswa(1)"
                                placeholder="Cari nama / NIS / NISN..." aria-label="Cari siswa"
                                class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-8 pr-8 text-xs outline-hidden transition-all focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20">
                            <button type="button" x-show="siswaQuery.length > 0" x-cloak @click="siswaQuery = ''; loadSiswa(1)"
                                class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600"
                                aria-label="Hapus pencarian">
                                <i class="bi bi-x-circle-fill text-xs"></i>
                            </button>
                        </div>

                        <label class="flex cursor-pointer select-none items-center gap-1.5 text-xs font-medium text-slate-600">
                            <input type="checkbox" x-model="siswaResetOnly" @change="loadSiswa(1)"
                                class="h-3.5 w-3.5 rounded border-slate-300 accent-violet-600"> Perlu reset login
                        </label>

                        <select x-model.number="siswaPerPage" @change="loadSiswa(1)" aria-label="Jumlah data per halaman"
                            class="rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 outline-hidden focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>

                        <button type="button" @click="loadSiswa(1)"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs shadow-violet-500/20 transition-colors hover:bg-violet-700">
                            <i class="bi bi-search"></i> Cari
                        </button>
                    </div>
                </x-slot:toolbar>

                <x-ui.table>
                    <x-slot:head>
                        <th class="w-12 px-3 py-3 text-center">No.</th>
                        <th class="hidden w-24 px-3 py-3 sm:table-cell">NIS</th>
                        <th class="px-4 py-3">Nama Siswa</th>
                        <th class="hidden w-28 px-3 py-3 md:table-cell">Kelas</th>
                        <th class="hidden px-4 py-3 lg:table-cell">Username</th>
                        <th class="w-28 px-3 py-3 text-center">Reset</th>
                        <th class="w-24 px-3 py-3 text-center">Status</th>
                        <th class="w-28 px-3 py-3 text-center">Aksi</th>
                    </x-slot:head>

                    <tr x-show="siswaLoading" x-cloak>
                        <td colspan="8" class="px-5 py-10 text-center text-xs font-medium text-slate-400">
                            <i class="bi bi-arrow-repeat mr-1 inline-block animate-spin"></i> Memuat data siswa...
                        </td>
                    </tr>

                    <tr x-show="!siswaLoading && siswaRows.length === 0" x-cloak>
                        <td colspan="8" class="px-5 py-10 text-center text-xs font-medium text-slate-400">
                            <i class="bi bi-person-x mr-1"></i> Tidak ada data siswa
                        </td>
                    </tr>

                    <template x-for="(siswa, index) in siswaRows" :key="siswa.id_siswa ?? index">
                        <tr class="transition-colors hover:bg-slate-50/60">
                            <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400"
                                x-text="(siswaPage - 1) * siswaPerPage + index + 1"></td>
                            <td class="hidden px-3 py-2.5 font-mono text-xs text-slate-500 sm:table-cell" x-text="siswa.nis || '-'"></td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <img :src="siswa.foto || config.avatarFallback" x-on:error="$el.src = config.avatarFallback"
                                        alt="" class="h-9 w-9 shrink-0 rounded-lg border border-slate-200 object-cover">
                                    <span class="max-w-[160px] truncate font-semibold text-slate-800" x-text="siswa.nama || '-'"></span>
                                </div>
                            </td>
                            <td class="hidden px-3 py-2.5 md:table-cell">
                                <span class="inline-flex items-center rounded-full border border-violet-100 bg-violet-50 px-2.5 py-0.5 text-xs font-semibold text-violet-700" x-text="siswa.nama_kelas || '-'"></span>
                            </td>
                            <td class="hidden px-4 py-2.5 lg:table-cell">
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-600" x-text="siswa.username || '-'"></span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <button type="button" :disabled="!hasReset(siswa.reset)"
                                    @click="resetLoginSiswa(siswa)"
                                    :title="hasReset(siswa.reset) ? 'Reset ' + siswa.reset + ' login gagal' : 'Tidak ada login gagal'"
                                    :class="hasReset(siswa.reset)
                                        ? 'bg-rose-50 text-rose-600 hover:bg-rose-100'
                                        : 'bg-slate-100 text-slate-300 cursor-not-allowed'"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg shadow-xs transition-colors">
                                    <i class="bi bi-arrow-repeat text-sm"></i>
                                </button>
                                <button type="button" @click="resetPasswordSiswa(siswa)"
                                    title="Reset password ke default (NISN)"
                                    class="ml-1 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 shadow-xs transition-colors hover:bg-amber-100">
                                    <i class="bi bi-key-fill text-sm"></i>
                                </button>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span x-show="isActive(siswa.active)"
                                    class="inline-flex items-center gap-1 rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                    <i class="bi bi-check-circle-fill text-[9px]"></i> Aktif
                                </span>
                                <span x-show="!isActive(siswa.active)" x-cloak
                                    class="inline-flex items-center gap-1 rounded-full border border-rose-100 bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700">
                                    <i class="bi bi-x-circle-fill text-[9px]"></i> Nonaktif
                                </span>
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="flex justify-center gap-1">
                                    <button type="button" @click="toggleSiswa(siswa)"
                                        :class="isActive(siswa.active)
                                            ? 'bg-amber-50 text-amber-700 hover:bg-amber-100'
                                            : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
                                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[10px] font-bold shadow-xs transition-colors">
                                        <i class="bi text-[10px]" :class="isActive(siswa.active) ? 'bi-slash-circle' : 'bi-person-check-fill'"></i>
                                        <span x-text="isActive(siswa.active) ? 'Nonaktif' : 'Aktifkan'"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </x-ui.table>

                <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 px-5 py-3.5 sm:flex-row">
                    <p class="text-xs font-medium text-slate-500" aria-live="polite"
                        x-text="siswaMeta.total === 0
                            ? 'Tidak ada data'
                            : 'Menampilkan ' + ((siswaPage - 1) * siswaPerPage + 1) + '\u2013' + Math.min(siswaPage * siswaPerPage, siswaMeta.total) + ' dari ' + siswaMeta.total + ' siswa'"></p>

                    <div class="flex items-center gap-1" x-show="siswaMeta.pages > 1" x-cloak>
                        <button type="button" @click="loadSiswa(siswaPage - 1)" :disabled="siswaPage <= 1"
                            aria-label="Halaman sebelumnya"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <i class="bi bi-chevron-left"></i>
                        </button>

                        <template x-for="(pageNumber, index) in siswaPageNumbers" :key="index">
                            <span class="inline-flex items-center">
                                <button type="button" x-show="pageNumber !== '\u2026'"
                                    @click="loadSiswa(pageNumber)"
                                    :class="pageNumber === siswaPage
                                        ? 'border-violet-600 bg-violet-600 text-white'
                                        : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                                    class="inline-flex h-8 min-w-[32px] items-center justify-center rounded-lg border text-xs font-semibold transition-colors"
                                    x-text="pageNumber"></button>
                                <span x-show="pageNumber === '\u2026'" class="px-1 text-xs text-slate-400">&hellip;</span>
                            </span>
                        </template>

                        <button type="button" @click="loadSiswa(siswaPage + 1)" :disabled="siswaPage >= siswaMeta.pages"
                            aria-label="Halaman berikutnya"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </x-ui.card>
        </div>

    </div><!-- /page wrapper -->

    <!-- Modal Tambah Admin -->
    <x-ui.modal id="createAdminModal" title="Tambah Administrator Baru" icon="bi-person-plus-fill" size="max-w-lg">
        <form @submit.prevent="submitCreate()" class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="admFirstName" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Nama Depan <span class="text-rose-500">*</span></label>
                        <input type="text" id="admFirstName" x-model="form.first_name" maxlength="50" required
                            placeholder="Budi" autocomplete="off"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <p class="mt-1 text-xs text-rose-600" x-show="errors.first_name" x-cloak x-text="errors.first_name"></p>
                    </div>
                    <div>
                        <label for="admLastName" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Nama Belakang</label>
                        <input type="text" id="admLastName" x-model="form.last_name" maxlength="50"
                            placeholder="Santoso" autocomplete="off"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>

                <div>
                    <label for="admUsername" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Username <span class="text-rose-500">*</span></label>
                    <input type="text" id="admUsername" x-model="form.username" maxlength="30" required
                        placeholder="budi.admin" autocomplete="off"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    <p class="mt-1 text-xs text-rose-600" x-show="errors.username" x-cloak x-text="errors.username"></p>
                </div>

                <div>
                    <label for="admEmail" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Email</label>
                    <input type="email" id="admEmail" x-model="form.email" maxlength="100"
                        placeholder="budi@sekolah.sch.id" autocomplete="off"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    <p class="mt-1 text-xs text-rose-600" x-show="errors.email" x-cloak x-text="errors.email"></p>
                </div>

                <div x-data="{ show: false }">
                    <label for="admPassword" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Password <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" id="admPassword" x-model="form.password"
                            maxlength="72" required placeholder="Min. 6 karakter" autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 pr-10 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <button type="button" @click="show = !show"
                            :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
                            class="absolute inset-y-0 right-0 px-3 text-slate-400 transition-colors hover:text-slate-600">
                            <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-rose-600" x-show="errors.password" x-cloak x-text="errors.password"></p>
                </div>

                <div x-data="{ show: false }">
                    <label for="admPasswordConfirm" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Konfirmasi Password <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" id="admPasswordConfirm" x-model="form.confirm_password"
                            maxlength="72" required placeholder="Ulangi password" autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 pr-10 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <button type="button" @click="show = !show"
                            :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
                            class="absolute inset-y-0 right-0 px-3 text-slate-400 transition-colors hover:text-slate-600">
                            <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-rose-600" x-show="errors.confirm_password" x-cloak x-text="errors.confirm_password"></p>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#createAdminModal')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="button" @click="resetForm()"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">
                    <i class="bi bi-arrow-repeat"></i> Reset
                </button>
                <button type="submit" :disabled="saving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                    <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!saving" class="bi bi-check-lg"></i> Simpan
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('userManagementPage', () => ({
        config: { urls: {}, csrf: '', avatarFallback: '' },

        tab: 'admin',

        saving: false,
        errors: {},
        form: { first_name: '', last_name: '', username: '', email: '', password: '', confirm_password: '' },

        guruList: [],
        guruLoading: false,
        guruLoaded: false,
        guruQuery: '',

        siswaRows: [],
        siswaMeta: { total: 0, pages: 1 },
        siswaLoading: false,
        siswaLoaded: false,
        siswaPage: 1,
        siswaPerPage: 10,
        siswaQuery: '',
        siswaAppliedQuery: '',
        siswaResetOnly: false,

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');
            this.config = Object.assign({ urls: {}, csrf: '', avatarFallback: '' }, cfg);
            this.config.urls = cfg.urls || {};

            const savedTab = ['admin', 'guru', 'siswa'].includes(localStorage.getItem('umTab'))
                ? localStorage.getItem('umTab')
                : 'admin';

            this.setTab(savedTab);
        },

        /* ── util ─────────────────────────────────────────── */
        get csrf() {
            return this.config.csrf;
        },

        async requestJson(url, { method = 'POST', params = null } = {}) {
            const options = {
                method,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            };

            if (params) {
                options.headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
                options.body = params;
            }

            const response = await fetch(url, options);

            return response.json();
        },

        post(url, data = {}) {
            const body = new URLSearchParams();
            body.append('_token', this.csrf);

            Object.keys(data).forEach((key) => body.append(key, data[key] ?? ''));

            return this.requestJson(url, { params: body });
        },

        isActive(value) {
            return value === 1 || value === '1' || value === true;
        },

        hasReset(value) {
            return Boolean(value) && String(value) !== '0';
        },

        reload() {
            window.location.reload();
        },

        /* ── tabs ─────────────────────────────────────────── */
        setTab(tab) {
            this.tab = tab;
            localStorage.setItem('umTab', tab);

            if (tab === 'guru' && !this.guruLoaded) {
                this.loadGuru();
            }

            if (tab === 'siswa' && !this.siswaLoaded) {
                this.loadSiswa(1);
            }
        },

        /* ── admin ────────────────────────────────────────── */
        resetForm() {
            this.errors = {};
            this.form = { first_name: '', last_name: '', username: '', email: '', password: '', confirm_password: '' };
        },

        openCreate() {
            this.resetForm();
            this.$store.modals.open('#createAdminModal');
        },

        async submitCreate() {
            if (this.saving) return;

            this.saving = true;
            this.errors = {};

            try {
                const result = await this.post(this.config.urls.adminCreate, this.form);

                if (result && result.status) {
                    this.$store.modals.close('#createAdminModal');
                    this.resetForm();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: result.msg || 'Admin berhasil dibuat',
                        timer: 1600,
                        showConfirmButton: false,
                    }).then(() => window.location.reload());
                    return;
                }

                this.errors = (result && result.errors) || {};
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: (result && result.msg) || 'Admin gagal dibuat — periksa isian lalu coba lagi.',
                });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            } finally {
                this.saving = false;
            }
        },

        async deleteAdmin(id, nama) {
            const konfirmasi = await Swal.fire({
                title: 'Hapus Administrator?',
                text: 'Akun "' + (nama || 'ini') + '" akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            try {
                const result = await this.post(this.config.urls.adminDelete + '/' + encodeURIComponent(id));

                if (result && result.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus',
                        text: result.msg || 'Admin berhasil dihapus',
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(() => window.location.reload());
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.msg) || 'Admin gagal dihapus.' });
            } catch (error) {
                console.error(error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
            }
        },

        /* ── guru ─────────────────────────────────────────── */
        get guruFiltered() {
            const query = this.guruQuery.trim().toLowerCase();

            if (query === '') return this.guruList;

            return this.guruList.filter((guru) => (guru.nama_guru || '').toLowerCase().includes(query));
        },

        async loadGuru() {
            this.guruLoading = true;

            try {
                const data = await this.requestJson(this.config.urls.guruData, { method: 'GET' });
                this.guruList = Array.isArray(data) ? data : [];
                this.guruLoaded = true;
            } catch (error) {
                console.error(error);
                this.guruList = [];
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal memuat data guru.' });
            } finally {
                this.guruLoading = false;
            }
        },

        async toggleGuru(guru) {
            const aktif = this.isActive(guru.active);
            const action = aktif ? 'nonaktifkan' : 'aktifkan';

            const konfirmasi = await Swal.fire({
                title: (aktif ? 'Nonaktifkan' : 'Aktifkan') + ' guru ini?',
                text: guru.nama_guru || '',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: aktif ? '#d97706' : '#16a34a',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            try {
                const result = await this.post(this.config.urls.guruActivate, {
                    id: guru.id ?? '',
                    id_guru: guru.id_guru ?? '',
                    nama: guru.nama_guru ?? '',
                    is_active: action,
                });

                if (result && result.status) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: result.msg || '', timer: 1300, showConfirmButton: false });
                    await this.loadGuru();
                    return;
                }

                Swal.fire('Gagal', (result && result.msg) || 'Gagal mengubah status', 'error');
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Koneksi server bermasalah', 'error');
            }
        },

        async resetLoginGuru(guru) {
            const konfirmasi = await Swal.fire({
                title: 'Reset Login?',
                text: 'Hapus catatan login gagal untuk "' + (guru.nama_guru || '') + '"?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Reset',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            try {
                const result = await this.post(this.config.urls.guruResetLogin, {
                    username: guru.username ?? '',
                    nama: guru.nama_guru ?? '',
                });

                Swal.fire({
                    icon: result && result.status ? 'success' : 'error',
                    title: result && result.status ? 'Berhasil' : 'Gagal',
                    text: (result && result.msg) || '',
                    timer: result && result.status ? 1400 : undefined,
                    showConfirmButton: !(result && result.status),
                });

                await this.loadGuru();
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Koneksi server bermasalah', 'error');
            }
        },

        async resetPasswordGuru(guru) {
            if (!guru.username) {
                Swal.fire('Perhatian', 'Username kosong.', 'warning');
                return;
            }

            const konfirmasi = await Swal.fire({
                title: 'Reset Password?',
                text: 'Password "' + (guru.nama_guru || '') + '" akan direset ke default: 123456',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d97706',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Reset',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            try {
                const result = await this.post(this.config.urls.guruResetPassword, { username: guru.username });

                Swal.fire({
                    icon: result && result.status ? 'success' : 'error',
                    title: result && result.status ? 'Berhasil' : 'Gagal',
                    text: (result && result.msg) || '',
                    timer: result && result.status ? 2500 : undefined,
                    showConfirmButton: !(result && result.status),
                });
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Koneksi server bermasalah', 'error');
            }
        },

        async bulkGuru(action) {
            const konfirmasi = await Swal.fire({
                title: (action === 'aktifkan' ? 'Aktifkan' : 'Nonaktifkan') + ' Semua Guru?',
                text: 'Tindakan ini akan mempengaruhi semua akun guru.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: action === 'aktifkan' ? '#16a34a' : '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Lanjutkan',
                cancelButtonText: 'Batal',
            });

            if (!konfirmasi.isConfirmed) return;

            Swal.showLoading();

            try {
                const result = await this.post(this.config.urls.guruBulk, { is_active: action });

                Swal.fire({
                    icon: result && result.status ? 'success' : 'error',
                    title: result && result.status ? 'Berhasil' : 'Gagal',
                    text: (result && result.msg) || '',
                });

                await this.loadGuru();
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Koneksi server bermasalah', 'error');
            }
        },

        /* ── siswa ────────────────────────────────────────── */
        get siswaPageNumbers() {
            const total = Math.max(1, this.siswaMeta.pages || 1);
            const current = this.siswaPage;
            const numbers = [];

            for (let page = 1; page <= total; page += 1) {
                if (page === 1 || page === total || Math.abs(page - current) <= 2) {
                    numbers.push(page);
                } else if (Math.abs(page - current) === 3) {
                    numbers.push('\u2026');
                }
            }

            return numbers;
        },

        async loadSiswa(page = 1) {
            this.siswaPage = Math.max(1, Number(page) || 1);
            this.siswaLoading = true;
            this.siswaAppliedQuery = this.siswaQuery.trim();

            try {
                const result = await this.post(this.config.urls.siswaList, {
                    page: this.siswaPage,
                    limit: this.siswaPerPage,
                    reset: this.siswaResetOnly ? '1' : '0',
                    ...(this.siswaAppliedQuery ? { search: this.siswaAppliedQuery } : {}),
                });

                this.siswaRows = (result && result.lists) || [];
                this.siswaMeta = {
                    total: Number((result && result.total) || 0),
                    pages: Math.max(1, Number((result && result.pages) || 1)),
                };
                this.siswaLoaded = true;
            } catch (error) {
                console.error(error);
                this.siswaRows = [];
                this.siswaMeta = { total: 0, pages: 1 };
                Swal.fire('Error', 'Koneksi server bermasalah', 'error');
            } finally {
                this.siswaLoading = false;
            }
        },

        async toggleSiswa(siswa) {
            const aktif = this.isActive(siswa.active);
            const action = aktif ? 'nonaktifkan' : 'aktifkan';

            const konfirmasi = await Swal.fire({
                title: (aktif ? 'Nonaktifkan' : 'Aktifkan') + ' siswa ini?',
                text: siswa.nama || '',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: aktif ? '#d97706' : '#16a34a',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            try {
                const result = await this.post(this.config.urls.siswaActivate, {
                    id_siswa: siswa.id_siswa ?? '',
                    nama: siswa.nama ?? '',
                    is_active: action,
                });

                if (result && result.status) {
                    await this.loadSiswa(this.siswaPage);
                    return;
                }

                Swal.fire('Gagal', (result && result.msg) || 'Gagal mengubah status', 'error');
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Koneksi server bermasalah', 'error');
            }
        },

        async resetLoginSiswa(siswa) {
            const konfirmasi = await Swal.fire({
                title: 'Reset Login?',
                text: 'Hapus catatan login gagal untuk "' + (siswa.nama || '') + '"?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Reset',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            try {
                const result = await this.post(this.config.urls.siswaResetLogin, { username: siswa.username ?? '' });

                Swal.fire({
                    icon: result && result.status ? 'success' : 'error',
                    title: result && result.status ? 'Berhasil' : 'Gagal',
                    text: (result && result.msg) || '',
                    timer: 1200,
                    showConfirmButton: false,
                });

                await this.loadSiswa(this.siswaPage);
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Koneksi server bermasalah', 'error');
            }
        },

        async resetPasswordSiswa(siswa) {
            if (!siswa.username) {
                Swal.fire('Perhatian', 'Username kosong.', 'warning');
                return;
            }

            const konfirmasi = await Swal.fire({
                title: 'Reset Password?',
                text: 'Password "' + (siswa.nama || '') + '" akan direset ke default: NISN siswa',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d97706',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Reset',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            try {
                const result = await this.post(this.config.urls.siswaResetPassword, { username: siswa.username });

                Swal.fire({
                    icon: result && result.status ? 'success' : 'error',
                    title: result && result.status ? 'Berhasil' : 'Gagal',
                    text: (result && result.msg) || '',
                    timer: result && result.status ? 2500 : undefined,
                    showConfirmButton: !(result && result.status),
                });
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Koneksi server bermasalah', 'error');
            }
        },

        async bulkSiswa(action) {
            const konfirmasi = await Swal.fire({
                title: (action === 'aktifkan' ? 'Aktifkan' : 'Nonaktifkan') + ' Semua Siswa?',
                text: 'Tindakan ini mempengaruhi seluruh akun siswa yang sudah terdaftar.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: action === 'aktifkan' ? '#7c3aed' : '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Lanjutkan',
                cancelButtonText: 'Batal',
            });

            if (!konfirmasi.isConfirmed) return;

            Swal.showLoading();

            try {
                const result = await this.post(this.config.urls.siswaBulk, { is_active: action });

                Swal.fire({
                    icon: result && result.status ? 'success' : 'error',
                    title: result && result.status ? 'Berhasil' : 'Gagal',
                    text: (result && result.msg) || '',
                });

                await this.loadSiswa(1);
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Koneksi server bermasalah', 'error');
            }
        },
    }));
});
</script>
@endsection
