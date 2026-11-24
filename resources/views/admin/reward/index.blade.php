@extends('layouts.main')

@section('content')
@php
    $pendingCount = (int) ($stats['pending_count'] ?? 0);
    $aktifCount = 0;
    $voucherCount = 0;
    foreach ($rewards as $rw) {
        if ((int) $rw->is_active) {
            $aktifCount++;
        }
        if (isset($rw->nominal) && (float) $rw->nominal > 0) {
            $voucherCount++;
        }
    }
    $statusBadge = function (string $status): string {
        return match ($status) {
            'APPROVED' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'REJECTED' => 'border-rose-200 bg-rose-50 text-rose-700',
            'CLAIMED' => 'border-blue-200 bg-blue-50 text-blue-700',
            default => 'border-amber-200 bg-amber-50 text-amber-700',
        };
    };
@endphp

<div class="space-y-4"
     x-data="rewardPage({
        csrf: @js(csrf_token()),
        tahun: @js($currentYear),
        rewards: @js($rewards->values()),
        urls: @js([
            'saveReward' => url('admin/reward/saveReward'),
            'deleteReward' => url('admin/reward/deleteReward'),
            'saveTier' => url('admin/reward/saveTier'),
            'deleteTier' => url('admin/reward/deleteTier'),
            'approve' => url('admin/reward/approveRedemption'),
            'reject' => url('admin/reward/rejectRedemption'),
            'adjust' => url('admin/reward/adjustPoin'),
            'history' => url('admin/reward/getTeacherHistory'),
            'annual' => url('admin/reward/getTeacherAnnual'),
            'seed' => url('admin/reward/seedPoinAll'),
        ]),
     })">

    <x-ui.page-header
        title="Kelola Reward &amp; Poin Kedisiplinan Guru"
        icon="bi-gift-fill"
        tone="amber"
        subtitle="Katalog penukaran, persetujuan klaim voucher, dan manajemen saldo poin guru."
        :crumbs="['Reward', 'Poin Guru']">
        <x-slot:actions>
            <button type="button" @click="reload()"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-100">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <a href="{{ url('admin/presensi/setting') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-100">
                <i class="bi bi-gear"></i> Pengaturan Poin
            </a>
            <a href="{{ url('admin/presensi/denda') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-100">
                <i class="bi bi-cash-coin text-emerald-600"></i> Rekap Denda
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($pendingCount > 0)
        <div class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
            <i class="bi bi-exclamation-triangle-fill mt-0.5 text-amber-500"></i>
            <div class="font-semibold leading-relaxed">
                Ada <strong>{{ $pendingCount }} klaim menunggu persetujuan</strong>. Buka tab
                <button type="button" @click="setTab('klaim')"
                        class="cursor-pointer border-none bg-transparent p-0 font-bold text-amber-900 underline">Klaim &amp; Penukaran</button>
                untuk memverifikasi sebelum voucher dipakai guru.
            </div>
        </div>
    @endif

    <!-- Statistik -->
    <div class="grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-400">Total Katalog</div>
            <div class="text-2xl font-extrabold tabular-nums text-slate-900">{{ count($rewards) }}</div>
            <div class="text-[11px] text-slate-400">{{ $aktifCount }} aktif &middot; {{ $voucherCount }} voucher</div>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-400">Klaim Menunggu</div>
            <div class="text-2xl font-extrabold tabular-nums text-amber-600">{{ $pendingCount }}</div>
            <div class="text-[11px] text-slate-400">Perlu persetujuan admin</div>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-400">Saldo Beredar</div>
            <div class="text-2xl font-extrabold tabular-nums text-emerald-600">{{ number_format((float) ($stats['total_poin_beredar'] ?? 0)) }}</div>
            <div class="text-[11px] text-slate-400">Akumulasi poin positif guru</div>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-400">Tier Disiplin</div>
            <div class="text-2xl font-extrabold tabular-nums text-blue-700">{{ count($tiers) }}</div>
            <div class="text-[11px] text-slate-400">Jenjang poin kinerja {{ $currentYear }}</div>
        </div>
    </div>

    <!-- Kartu utama: tab -->
    <x-ui.card bodyClass="p-4 md:p-5">
        <x-slot:toolbar>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex flex-wrap items-center gap-1 rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Navigasi reward">
                    <button type="button" role="tab" @click="setTab('katalog')" :aria-selected="tab === 'katalog'" :class="tabClass('katalog')">
                        <i class="bi bi-grid-fill"></i> Katalog
                        <span class="rounded-full bg-white/80 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">{{ count($rewards) }}</span>
                    </button>
                    <button type="button" role="tab" @click="setTab('klaim')" :aria-selected="tab === 'klaim'" :class="tabClass('klaim')">
                        <i class="bi bi-inbox-fill"></i> Klaim
                        @if ($pendingCount > 0)
                            <span class="rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $pendingCount }}</span>
                        @endif
                    </button>
                    <button type="button" role="tab" @click="setTab('guru')" :aria-selected="tab === 'guru'" :class="tabClass('guru')">
                        <i class="bi bi-people-fill"></i> Saldo Guru
                        <span class="rounded-full bg-white/80 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">{{ count($teachers) }}</span>
                    </button>
                    <button type="button" role="tab" @click="setTab('tier')" :aria-selected="tab === 'tier'" :class="tabClass('tier')">
                        <i class="bi bi-award-fill"></i> Tier
                        <span class="rounded-full bg-white/80 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">{{ count($tiers) }}</span>
                    </button>
                </div>
                <span class="ml-auto hidden text-[11px] font-semibold text-slate-400 sm:inline">Data tahun {{ $currentYear }}</span>
            </div>
        </x-slot:toolbar>

        <!-- TAB 1: KATALOG -->
        <div x-show="tab === 'katalog'" class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div class="min-w-0">
                    <h3 class="m-0 text-sm font-bold text-slate-800">Daftar Item &amp; Voucher</h3>
                    <p class="m-0 mt-0.5 text-[11px] text-slate-400">Guru menukar poin dengan voucher denda atau item. Stok 0 otomatis nonaktif.</p>
                </div>
                <button type="button" @click="openReward()"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-[11px] font-bold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Reward
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-[180px] flex-1 sm:max-w-xs">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                    <input type="search" x-model="catalog.query" placeholder="Cari nama / deskripsi..." aria-label="Cari reward"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-3 text-xs font-semibold text-slate-800 transition-all hover:border-slate-300 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <select x-model="catalog.type" aria-label="Filter jenis"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition-all focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua jenis</option>
                    <option value="voucher">Voucher potong denda</option>
                    <option value="item">Item / hadiah</option>
                </select>
                <select x-model="catalog.status" aria-label="Filter status"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition-all focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
                <select x-model="catalog.sort" aria-label="Urutkan"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition-all focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="poin-asc">Termurah (poin)</option>
                    <option value="poin-desc">Termahal (poin)</option>
                    <option value="nama-asc">Nama A–Z</option>
                    <option value="stok-asc">Stok menipis</option>
                </select>
                <span class="ml-auto text-xs tabular-nums text-slate-400"
                      x-text="filteredCatalog.length + ' dari ' + rewards.length + ' item'"></span>
            </div>

            @if (count($rewards) === 0)
                <x-ui.empty icon="bi-gift" title="Belum ada reward"
                    description="Buat voucher pemotongan denda atau item penukaran pertama.">
                    <button type="button" @click="openReward()"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-[11px] font-bold text-white transition-colors hover:bg-blue-700">
                        <i class="bi bi-plus-lg"></i> Tambah Reward Pertama
                    </button>
                </x-ui.empty>
            @else
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <template x-for="r in filteredCatalog" :key="r.id">
                        <article class="flex h-full flex-col rounded-2xl border border-slate-200/80 p-4 shadow-xs transition duration-200 hover:-translate-y-0.5 hover:shadow-md"
                                 :class="r.aktif ? 'bg-white' : 'bg-slate-50'">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                          :class="r.voucher ? 'bg-purple-100 text-purple-700' : 'bg-blue-50 text-blue-600'">
                                        <i class="bi" :class="r.voucher ? 'bi-ticket-perforated-fill' : 'bi-gift-fill'"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <h4 class="m-0 truncate text-sm font-bold text-slate-900" :title="r.nama" x-text="r.nama"></h4>
                                        <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold"
                                              :class="r.voucher ? 'bg-purple-100 text-purple-700' : 'bg-blue-50 text-blue-700'"
                                              x-text="r.voucher ? 'Voucher Potong Denda' : 'Item / Hadiah'"></span>
                                    </div>
                                </div>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                      :class="r.aktif ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                      x-text="r.aktif ? 'Aktif' : 'Nonaktif'"></span>
                            </div>

                            <p class="mt-3 mb-3 line-clamp-2 min-h-[2.4rem] text-xs leading-relaxed text-slate-500"
                               x-text="r.deskripsi || 'Tidak ada deskripsi.'"></p>

                            <div class="mb-3 rounded-xl border border-slate-100 bg-slate-50 p-3 text-xs">
                                <div class="flex items-center justify-between gap-2 py-0.5">
                                    <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Harga</span>
                                    <span class="text-sm font-bold tabular-nums text-amber-600">
                                        <i class="bi bi-star-fill mr-1"></i><span x-text="fmtNum(r.poin)"></span> pts
                                    </span>
                                </div>
                                <template x-if="r.voucher">
                                    <div class="flex items-center justify-between gap-2 py-0.5">
                                        <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Nilai</span>
                                        <span class="text-sm font-bold tabular-nums text-emerald-600" x-text="fmtRp(r.nominal)"></span>
                                    </div>
                                </template>
                                <template x-if="r.voucher && ratio(r) > 0">
                                    <div class="flex items-center justify-between gap-2 py-0.5">
                                        <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Rasio</span>
                                        <span class="text-xs tabular-nums text-slate-500" x-text="fmtRp(ratio(r)) + '/poin'"></span>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-auto">
                                <div class="mb-1.5 flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Stok</span>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                          :class="r.stok <= 0 ? 'bg-rose-50 text-rose-700' : (r.stok <= 5 ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700')"
                                          x-text="r.stok + ' pcs'"></span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-slate-100" :title="'Sisa stok ' + r.stok">
                                    <span class="block h-full rounded-full transition-all" :class="stokBar(r)" :style="'width:' + stokPct(r) + '%'"></span>
                                </div>
                            </div>

                            <div class="mt-3 flex gap-2">
                                <button type="button" @click="openReward(r)"
                                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-blue-200/70 bg-blue-50 px-3 py-1.5 text-[11px] font-bold text-blue-700 transition-colors hover:bg-blue-100">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button type="button" @click="deleteReward(r)" aria-label="Hapus reward" title="Hapus reward"
                                        class="inline-flex items-center justify-center rounded-lg border border-rose-200/70 bg-rose-50 px-2.5 py-1.5 text-[11px] font-bold text-rose-600 transition-colors hover:bg-rose-100">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </article>
                    </template>
                </div>

                <div x-show="filteredCatalog.length === 0" x-cloak>
                    <x-ui.empty icon="bi-search" title="Tidak ada hasil" description="Coba kata kunci atau filter lain."></x-ui.empty>
                </div>
            @endif
        </div>

        <!-- TAB 2: KLAIM -->
        <div x-show="tab === 'klaim'" x-cloak class="space-y-5">
            <div>
                <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h3 class="m-0 flex items-center gap-1.5 text-sm font-bold text-slate-800">
                            <i class="bi bi-hourglass-split text-amber-500"></i> Menunggu Persetujuan
                        </h3>
                        <p class="m-0 mt-0.5 text-[11px] text-slate-400">Setujui / tolak sebelum voucher dipakai. Penolakan otomatis refund poin + pulihkan stok.</p>
                    </div>
                    <span class="rounded-full bg-amber-50 px-3 py-1.5 text-[11px] font-bold tabular-nums text-amber-700">{{ count($pending) }} menunggu</span>
                </div>

                @if (count($pending) === 0)
                    <div class="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800">
                        <i class="bi bi-check-circle-fill mt-0.5 text-emerald-500"></i>
                        <div>Tidak ada klaim menunggu. Semua pengajuan sudah diproses.</div>
                    </div>
                @else
                    <div x-data="dataTable({ perPage: 10 })">
                        <div class="mb-2 flex justify-end">
                            <input type="search" x-model="query" placeholder="Cari guru / reward..." aria-label="Cari klaim"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-800 transition-all hover:border-slate-300 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 sm:w-56">
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                            <table class="w-full border-collapse text-left text-sm">
                                <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <tr class="border-b border-slate-200/80">
                                        <th class="px-3 py-2.5">Tgl Klaim</th>
                                        <th class="px-3 py-2.5">Guru</th>
                                        <th class="px-3 py-2.5">Reward</th>
                                        <th class="px-3 py-2.5 text-center">Poin</th>
                                        <th class="px-3 py-2.5 text-right">Nilai</th>
                                        <th class="px-3 py-2.5 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    @foreach ($pending as $p)
                                        <tr>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-xs text-slate-500">{{ date('d/m/Y H:i', strtotime($p->created_at)) }}</td>
                                            <td class="px-3 py-2.5 font-bold text-slate-800">{{ trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')) ?: 'Guru #' . $p->user_id }}</td>
                                            <td class="px-3 py-2.5">
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-purple-100 px-2.5 py-1 text-[11px] font-bold text-purple-700">
                                                    <i class="bi bi-gift-fill"></i>{{ $p->reward_nama ?? 'Reward dihapus' }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2.5 text-center font-bold tabular-nums text-amber-600">{{ number_format((int) $p->poin_digunakan) }} pts</td>
                                            <td class="px-3 py-2.5 text-right font-bold tabular-nums text-emerald-600">{{ isset($p->nominal) && (float) $p->nominal > 0 ? 'Rp ' . number_format((float) $p->nominal, 0, ',', '.') : '–' }}</td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-center">
                                                <button type="button" @click="approve({{ (int) $p->id }})"
                                                        class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 transition-colors hover:bg-emerald-100">
                                                    <i class="bi bi-check-circle-fill"></i> Setujui
                                                </button>
                                                <button type="button" @click="openReject({{ (int) $p->id }})"
                                                        class="ml-1 inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-600 transition-colors hover:bg-rose-100">
                                                    <i class="bi bi-x-circle"></i> Tolak
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="flex items-center justify-between gap-2 px-1 pt-3 text-[11px] font-semibold text-slate-500">
                            <span x-text="total ? from + '–' + to + ' dari ' + total : 'Tidak ada data'"></span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="prev()" :disabled="page <= 1"
                                        class="rounded-lg border border-slate-200 px-2.5 py-1 transition-colors hover:bg-slate-100 disabled:opacity-40">Sebelumnya</button>
                                <span class="tabular-nums" x-text="page + ' / ' + pages"></span>
                                <button type="button" @click="next()" :disabled="page >= pages"
                                        class="rounded-lg border border-slate-200 px-2.5 py-1 transition-colors hover:bg-slate-100 disabled:opacity-40">Berikutnya</button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div x-data="dataTable({ perPage: 10, filters: { status: '' } })">
                <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
                    <h3 class="m-0 text-sm font-bold text-slate-800">Riwayat Penukaran</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        <select aria-label="Filter status riwayat"
                                class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                                x-model="filters.status">
                            <option value="">Semua status</option>
                            <option value="PENDING">PENDING</option>
                            <option value="APPROVED">APPROVED</option>
                            <option value="CLAIMED">CLAIMED</option>
                            <option value="REJECTED">REJECTED</option>
                        </select>
                        <div class="relative">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                            <input type="search" x-model="query" placeholder="Cari guru / reward..." aria-label="Cari riwayat penukaran"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-3 text-xs font-semibold text-slate-800 transition-all hover:border-slate-300 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 sm:w-56">
                        </div>
                    </div>
                </div>

                @if (count($allRedemptions) === 0)
                    <p class="m-0 text-sm text-slate-400">Belum ada riwayat penukaran.</p>
                @else
                    <div>
                        <div class="max-h-[420px] overflow-auto rounded-xl border border-slate-200/80">
                            <table class="w-full border-collapse text-left text-xs">
                                <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <tr class="border-b border-slate-200/80">
                                        <th class="px-3 py-2.5">Tgl</th>
                                        <th class="px-3 py-2.5">Guru</th>
                                        <th class="px-3 py-2.5">Reward</th>
                                        <th class="px-3 py-2.5 text-center">Poin</th>
                                        <th class="px-3 py-2.5 text-right">Nilai</th>
                                        <th class="px-3 py-2.5 text-center">Status</th>
                                        <th class="px-3 py-2.5">Diproses / Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    @foreach ($allRedemptions as $ar)
                                        @php
                                            $guruNama = $ar->nama_guru ?? trim(($ar->first_name ?? '') . ' ' . ($ar->last_name ?? ''));
                                        @endphp
                                        <tr data-status="{{ $ar->status }}" data-search="{{ strtolower($guruNama . ' ' . ($ar->reward_nama ?? '') . ' ' . ($ar->nip ?? '')) }}">
                                            <td class="whitespace-nowrap px-3 py-2.5 text-slate-500">{{ date('d/m/Y H:i', strtotime($ar->created_at)) }}</td>
                                            <td class="px-3 py-2.5">
                                                <div class="font-bold text-slate-800">{{ $guruNama ?: '-' }}</div>
                                                <div class="text-[11px] text-slate-400">NIP: {{ $ar->nip ?? '-' }}</div>
                                            </td>
                                            <td class="px-3 py-2.5">{{ $ar->reward_nama ?? 'Reward dihapus' }}</td>
                                            <td class="px-3 py-2.5 text-center font-bold tabular-nums text-amber-600">{{ number_format((int) $ar->poin_digunakan) }}</td>
                                            <td class="px-3 py-2.5 text-right font-bold tabular-nums text-emerald-600">{{ isset($ar->nominal) && (float) $ar->nominal > 0 ? 'Rp ' . number_format((float) $ar->nominal, 0, ',', '.') : '–' }}</td>
                                            <td class="px-3 py-2.5 text-center">
                                                <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $statusBadge((string) ($ar->status ?? '')) }}">{{ $ar->status }}</span>
                                            </td>
                                            <td class="px-3 py-2.5 text-[11px] text-slate-500">
                                                @if (!empty($ar->admin_first))
                                                    <div><i class="bi bi-person-check mr-1"></i>{{ trim($ar->admin_first . ' ' . $ar->admin_last) }}</div>
                                                @endif
                                                @if (!empty($ar->keterangan))
                                                    <div class="text-[11px]">{{ $ar->keterangan }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="flex items-center justify-between gap-2 px-1 pt-3 text-[11px] font-semibold text-slate-500">
                            <span x-text="total ? from + '–' + to + ' dari ' + total : 'Tidak ada hasil'"></span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="prev()" :disabled="page <= 1"
                                        class="rounded-lg border border-slate-200 px-2.5 py-1 transition-colors hover:bg-slate-100 disabled:opacity-40">Sebelumnya</button>
                                <span class="tabular-nums" x-text="page + ' / ' + pages"></span>
                                <button type="button" @click="next()" :disabled="page >= pages"
                                        class="rounded-lg border border-slate-200 px-2.5 py-1 transition-colors hover:bg-slate-100 disabled:opacity-40">Berikutnya</button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- TAB 3: GURU -->
        <div x-show="tab === 'guru'" x-cloak class="space-y-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white p-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700"><i class="bi bi-trophy-fill"></i></span>
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold uppercase text-slate-400">Poin Kinerja {{ $currentYear }}</div>
                        <div class="font-bold tabular-nums text-slate-800">{{ number_format((float) ($stats['poin_tahunan_total'] ?? 0)) }} pts</div>
                        <div class="text-[11px] text-slate-400">Neto seluruh guru, termasuk penalti</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white p-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><i class="bi bi-bar-chart-fill"></i></span>
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold uppercase text-slate-400">Rata-rata Guru</div>
                        <div class="font-bold tabular-nums text-slate-800">{{ number_format((float) ($stats['poin_tahunan_avg'] ?? 0), 1) }} pts</div>
                        <div class="text-[11px] text-slate-400">Per guru aktif</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white p-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><i class="bi bi-cash-coin"></i></span>
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold uppercase text-slate-400">Saldo Beredar</div>
                        <div class="font-bold tabular-nums text-slate-800">{{ number_format((float) ($stats['total_poin_beredar'] ?? 0)) }} pts</div>
                        <div class="text-[11px] text-slate-400">Akumulasi saldo positif</div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h3 class="m-0 text-sm font-bold text-slate-800">Saldo &amp; Kedisiplinan Guru</h3>
                    <p class="m-0 mt-0.5 text-[11px] text-slate-400">Bonus / penalti manual tercatat sebagai jejak audit. Maks 1.000 pts per transaksi.</p>
                </div>
                <button type="button" @click="seedPoin()"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-[11px] font-bold text-emerald-700 transition-colors hover:bg-emerald-100">
                    <i class="bi bi-arrow-repeat"></i> Sinkron Tahunan
                </button>
            </div>

            <div x-data="dataTable({ perPage: 10, sort: 5, dir: 'desc' })">
                <div class="max-h-[56vh] overflow-auto rounded-xl border border-slate-200/80">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr class="border-b border-slate-200/80">
                                <th class="px-3 py-2.5 text-center">#</th>
                                <th class="px-3 py-2.5">Guru</th>
                                <th class="px-3 py-2.5">NIP</th>
                                <th class="px-3 py-2.5 text-center">Tier</th>
                                <th class="px-3 py-2.5 text-center">Poin {{ $currentYear }}</th>
                                <th class="px-3 py-2.5 text-center">Saldo</th>
                                <th class="px-3 py-2.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($teachers as $t)
                                @php
                                    $rankStyle = match ((int) $t->rank_tahunan) {
                                        1 => 'background:#fbbf24;color:#78350f',
                                        2 => 'background:#cbd5e1;color:#334155',
                                        3 => 'background:#fdba74;color:#7c2d12',
                                        default => 'background:#f1f5f9;color:#64748b',
                                    };
                                    $saldoClass = (int) $t->saldo < 0 ? 'text-rose-600' : ((int) $t->saldo > 0 ? 'text-emerald-600' : 'text-slate-500');
                                @endphp
                                <tr data-search="{{ strtolower($t->nama . ' ' . $t->nip) }}">
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold tabular-nums" style="{{ $rankStyle }}">{{ (int) $t->rank_tahunan }}</span>
                                    </td>
                                    <td class="px-3 py-2.5 font-bold text-slate-800">{{ $t->nama }}</td>
                                    <td class="px-3 py-2.5 text-xs text-slate-500">{{ $t->nip ?? '-' }}</td>
                                    <td class="px-3 py-2.5 text-center">
                                        @if (!empty($t->tier))
                                            @php $tc = ! empty($t->tier->warna) ? $t->tier->warna : '#64748b'; @endphp
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                                                  style="background:{{ $tc }}20;color:{{ $tc }};border:1px solid {{ $tc }}60">
                                                <i class="bi {{ $t->tier->badge ?? 'bi-star-fill' }}"></i>{{ $t->tier->nama_tier }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">–</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold tabular-nums {{ (float) $t->poin_tahunan > 0 ? 'bg-blue-50 text-blue-700' : ((float) $t->poin_tahunan < 0 ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-500') }}">
                                            {{ (float) $t->poin_tahunan > 0 ? '+' . number_format((float) $t->poin_tahunan, 1) : number_format((float) $t->poin_tahunan, 1) }} pts
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center" data-order="{{ (int) $t->saldo }}">
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold tabular-nums {{ $saldoClass }}">
                                            <i class="bi bi-star-fill text-amber-500"></i>{{ (int) $t->saldo > 0 ? '+' . number_format((int) $t->saldo) : number_format((int) $t->saldo) }} pts
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-center">
                                        <button type="button" title="Sesuaikan poin"
                                                @click="openAdjust({{ (int) $t->user_id }}, @js($t->nama), {{ (int) $t->saldo }})"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-blue-200/70 bg-blue-50 text-blue-700 transition-colors hover:bg-blue-100">
                                            <i class="bi bi-plus-slash-minus"></i>
                                        </button>
                                        <button type="button" title="Riwayat mutasi"
                                                @click="openHistory({{ (int) $t->user_id }}, @js($t->nama))"
                                                class="ml-1 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-100">
                                            <i class="bi bi-clock-history"></i>
                                        </button>
                                        <button type="button" title="Rincian {{ $currentYear }}"
                                                @click="openAnnual({{ (int) $t->user_id }}, @js($t->nama))"
                                                class="ml-1 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-100">
                                            <i class="bi bi-pie-chart"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between gap-2 px-1 pt-3 text-[11px] font-semibold text-slate-500">
                    <span x-text="total ? from + '–' + to + ' dari ' + total + ' guru' : 'Tidak ada data'"></span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="prev()" :disabled="page <= 1"
                                class="rounded-lg border border-slate-200 px-2.5 py-1 transition-colors hover:bg-slate-100 disabled:opacity-40">Sebelumnya</button>
                        <span class="tabular-nums" x-text="page + ' / ' + pages"></span>
                        <button type="button" @click="next()" :disabled="page >= pages"
                                class="rounded-lg border border-slate-200 px-2.5 py-1 transition-colors hover:bg-slate-100 disabled:opacity-40">Berikutnya</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: TIER -->
        <div x-show="tab === 'tier'" x-cloak class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h3 class="m-0 text-sm font-bold text-slate-800">Jenjang Tier Kedisiplinan</h3>
                    <p class="m-0 mt-0.5 text-[11px] text-slate-400">Rentang tidak boleh tumpang tindih. <code>0</code> pada maks = tak terhingga.</p>
                </div>
                <button type="button" @click="openTier()"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-[11px] font-bold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Tier
                </button>
            </div>

            @if (count($tiers) > 0)
                <div class="flex items-center gap-2 overflow-x-auto pb-1" aria-label="Tangga tier">
                    @foreach ($tiers as $tier)
                        @php $tc = $tier->warna ?: '#64748b'; @endphp
                        <div class="flex shrink-0 items-center gap-2">
                            <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2"
                                 title="{{ $tier->nama_tier }}: {{ number_format((int) $tier->poin_min) }}–{{ ((int) $tier->poin_max > 0 ? number_format((int) $tier->poin_max) : '∞') }}">
                                <span class="flex h-9 w-9 items-center justify-center rounded-[10px] text-lg"
                                      style="background:{{ $tc }}20;color:{{ $tc }};border:1px solid {{ $tc }}50">
                                    <i class="bi {{ $tier->badge ?: 'bi-star-fill' }}"></i>
                                </span>
                                <div>
                                    <div class="text-[13px] font-bold text-slate-800">{{ $tier->nama_tier }}</div>
                                    <div class="text-[11px] tabular-nums text-slate-400">{{ number_format((int) $tier->poin_min) }}–{{ ((int) $tier->poin_max > 0 ? number_format((int) $tier->poin_max) : '∞') }}</div>
                                </div>
                            </div>
                            @if (! $loop->last)
                                <i class="bi bi-chevron-right text-slate-300"></i>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <div x-data="dataTable({ perPage: 100 })">
                <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                    <table class="w-full border-collapse text-left text-sm">
                        <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr class="border-b border-slate-200/80">
                                <th class="w-14 px-3 py-2.5 text-center">Badge</th>
                                <th class="px-3 py-2.5">Nama Tier</th>
                                <th class="px-3 py-2.5 text-center">Rentang Poin</th>
                                <th class="px-3 py-2.5 text-center">Warna</th>
                                <th class="w-24 px-3 py-2.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($tiers as $tier)
                                @php $tc = $tier->warna ?: '#64748b'; @endphp
                                <tr>
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-[10px] text-lg"
                                              style="background:{{ $tc }}20;color:{{ $tc }};border:1px solid {{ $tc }}50">
                                            <i class="bi {{ $tier->badge ?: 'bi-star-fill' }}"></i>
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 font-bold text-slate-800">{{ $tier->nama_tier }}</td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-bold tabular-nums text-blue-700">
                                            {{ number_format((int) $tier->poin_min) }} s/d {{ ((int) $tier->poin_max > 0 ? number_format((int) $tier->poin_max) . ' pts' : '∞') }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold text-white" style="background:{{ $tc }}">{{ $tc }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-center">
                                        <button type="button" title="Edit tier" @click="openTier(@js($tier))"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-blue-200/70 bg-blue-50 text-blue-700 transition-colors hover:bg-blue-100">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" title="Hapus tier" @click="deleteTier({{ (int) $tier->id }}, @js($tier->nama_tier))"
                                                class="ml-1 inline-flex h-7 w-7 items-center justify-center rounded-lg border border-rose-200/70 bg-rose-50 text-rose-600 transition-colors hover:bg-rose-100">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-ui.card>

    <!-- MODAL REWARD -->
    <x-ui.modal id="modalReward" size="max-w-lg">
        <x-slot:header>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <h2 class="flex min-w-0 items-center gap-2.5 text-sm font-bold text-slate-800">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white"><i class="bi bi-gift text-sm"></i></span>
                    <span class="truncate" x-text="rewardForm.id ? 'Edit Reward' : 'Tambah Reward Baru'"></span>
                </h2>
                <button type="button" @click="$store.modals.close('#modalReward')" aria-label="Tutup"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </x-slot:header>
        <form @submit.prevent="saveReward()" novalidate class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <div>
                    <label for="rewardNama" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Nama Reward / Voucher <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="rewardNama" x-model="rewardForm.nama" minlength="3" maxlength="255" required
                           placeholder="Contoh: Voucher Potongan Denda Rp 50.000"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label for="rewardDeskripsi" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Deskripsi</label>
                    <textarea id="rewardDeskripsi" x-model="rewardForm.deskripsi" rows="2" maxlength="2000" placeholder="Ketentuan penukaran..."
                              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"></textarea>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label for="rewardPoin" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Poin Dibutuhkan <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" id="rewardPoin" x-model.number="rewardForm.poin" min="1" max="100000" required placeholder="50"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <div class="mt-1 text-[11px] text-slate-400">Min 1 — tidak boleh gratis.</div>
                    </div>
                    <div>
                        <label for="rewardStok" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Stok <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" id="rewardStok" x-model.number="rewardForm.stok" min="0" max="100000" required placeholder="10"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <div class="mt-1 text-[11px] text-slate-400">Stok 0 → otomatis nonaktif.</div>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <label for="rewardNominal" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Nominal Potongan Denda (Rp)</label>
                    <input type="number" id="rewardNominal" x-model.number="rewardForm.nominal" min="0" max="100000000" step="1000"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <div class="mt-1 text-[11px] text-slate-400">Isi &gt; 0 jika <b>voucher denda</b>. Isi <b>0</b> untuk barang fisik.</div>
                    <div class="mt-2 flex flex-wrap gap-1.5 text-[10px] font-bold">
                        <span x-show="rewardForm.nominal > 0" class="rounded-full bg-purple-100 px-2 py-0.5 text-purple-700"
                              x-text="'Voucher: ' + fmtRp(rewardForm.nominal) + (rewardForm.poin > 0 ? ' / ' + fmtNum(rewardForm.poin) + ' pts' : '')"></span>
                        <span x-show="rewardForm.nominal <= 0" class="rounded-full bg-blue-50 px-2 py-0.5 text-blue-700">Item fisik / merchandise</span>
                        <span x-show="rewardForm.stok <= 0" class="rounded-full bg-rose-50 px-2 py-0.5 text-rose-700">Stok 0 → akan nonaktif otomatis</span>
                        <span x-show="rewardForm.stok > 0 && rewardForm.stok <= 5" class="rounded-full bg-amber-50 px-2 py-0.5 text-amber-700"
                              x-text="'Stok menipis (' + rewardForm.stok + ')'"></span>
                    </div>
                </div>
                <label class="inline-flex cursor-pointer items-center gap-2">
                    <input type="checkbox" x-model="rewardForm.aktif" class="peer sr-only">
                    <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-slate-300 transition-colors after:absolute after:left-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition-transform after:content-[''] peer-checked:bg-emerald-500 peer-checked:after:translate-x-4 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500/40"></span>
                    <span class="text-sm font-bold text-slate-800">Aktifkan di katalog guru</span>
                </label>
            </div>
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#modalReward')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="rewardSaving"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                    <span x-show="rewardSaving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!rewardSaving" class="bi bi-check2-circle"></i> Simpan Reward
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- MODAL TIER -->
    <x-ui.modal id="modalTier" size="max-w-lg">
        <x-slot:header>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <h2 class="flex min-w-0 items-center gap-2.5 text-sm font-bold text-slate-800">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white"><i class="bi bi-award text-sm"></i></span>
                    <span class="truncate" x-text="tierForm.id ? 'Edit Tier' : 'Tambah Tier Baru'"></span>
                </h2>
                <button type="button" @click="$store.modals.close('#modalTier')" aria-label="Tutup"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </x-slot:header>
        <form @submit.prevent="saveTier()" novalidate class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <div>
                    <label for="tierNama" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Nama Tier <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="tierNama" x-model="tierForm.nama_tier" minlength="2" maxlength="50" required placeholder="Contoh: Gold"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label for="tierMin" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Poin Minimal <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" id="tierMin" x-model.number="tierForm.poin_min" min="0" max="1000000" required placeholder="100"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <div>
                        <label for="tierMax" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Poin Maksimal</label>
                        <input type="number" id="tierMax" x-model.number="tierForm.poin_max" min="0" max="10000000" placeholder="500 (0 = ∞)"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label for="tierBadge" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Icon Badge</label>
                        <input type="text" id="tierBadge" x-model="tierForm.badge" pattern="bi-[a-z0-9-]+" placeholder="bi-trophy-fill"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <div class="mt-1 text-[11px] text-slate-400">Contoh: bi-trophy-fill, bi-star-fill, bi-gem</div>
                    </div>
                    <div>
                        <label for="tierWarna" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">Warna Tier</label>
                        <div class="flex items-center gap-2">
                            <input type="color" id="tierWarna" x-model="tierForm.warna"
                                   class="h-10 w-14 cursor-pointer rounded border border-slate-200">
                            <span class="font-mono text-xs font-bold uppercase text-slate-500" x-text="tierForm.warna"></span>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="mb-2 text-[11px] font-bold uppercase text-slate-400">Pratinjau</div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold"
                          :style="'background:' + tierForm.warna + '20;color:' + tierForm.warna + ';border:1px solid ' + tierForm.warna + '60'">
                        <i class="bi" :class="tierForm.badge || 'bi-star-fill'"></i>
                        <span x-text="(tierForm.nama_tier || 'Nama Tier') + ' · ' + tierForm.poin_min + '–' + (tierForm.poin_max > 0 ? tierForm.poin_max : '∞')"></span>
                    </span>
                </div>
            </div>
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#modalTier')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="tierSaving"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                    <span x-show="tierSaving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!tierSaving" class="bi bi-check2-circle"></i> Simpan Tier
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- MODAL ADJUST -->
    <x-ui.modal id="modalAdjust" size="max-w-lg">
        <x-slot:header>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <h2 class="flex min-w-0 items-center gap-2.5 text-sm font-bold text-slate-800">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white"><i class="bi bi-plus-slash-minus text-sm"></i></span>
                    <span class="truncate">Penyesuaian Poin Guru</span>
                </h2>
                <button type="button" @click="$store.modals.close('#modalAdjust')" aria-label="Tutup"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </x-slot:header>
        <form @submit.prevent="saveAdjust()" novalidate class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <div class="text-center">
                    <h3 class="m-0 text-base font-bold text-slate-800" x-text="adjForm.nama || '–'"></h3>
                    <div class="text-xs text-slate-400">
                        Saldo saat ini: <span class="font-bold tabular-nums text-blue-600" x-text="fmtNum(adjForm.saldo) + ' pts'"></span>
                    </div>
                </div>
                <div>
                    <label for="adjTipe" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Tipe <span class="text-rose-500">*</span>
                    </label>
                    <select id="adjTipe" x-model="adjForm.tipe"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <option value="BONUS">Tambah poin bonus (prestasi)</option>
                        <option value="PENALTY">Kurangi poin (pelanggaran / koreksi)</option>
                    </select>
                </div>
                <div>
                    <label for="adjPoin" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Nominal Poin <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" id="adjPoin" x-model.number="adjForm.poin" min="1" max="1000" required placeholder="Contoh: 25"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    <div class="mt-1 text-[11px] text-slate-400">Maks 1.000 pts per transaksi. Saldo tidak boleh &lt; −1.000.</div>
                </div>
                <div>
                    <label for="adjKeterangan" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Keterangan / Alasan <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="adjKeterangan" x-model="adjForm.keterangan" minlength="5" maxlength="255" required
                           placeholder="Contoh: Bonus kepanitiaan ujian sekolah"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div x-show="adjForm.poin > 0" x-cloak class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs font-semibold text-slate-600">
                    Saldo baru: <strong class="tabular-nums" x-text="fmtNum(adjSaldoBaru) + ' pts'"></strong>
                    <span x-show="adjSaldoBaru < -1000" class="ml-1 rounded-full bg-rose-50 px-2 py-0.5 text-rose-700">Melebihi batas −1.000, akan ditolak</span>
                    <span x-show="adjSaldoBaru < 0 && adjSaldoBaru >= -1000" class="ml-1 rounded-full bg-amber-50 px-2 py-0.5 text-amber-700">Saldo menjadi negatif</span>
                </div>
            </div>
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#modalAdjust')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="adjSaving"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-emerald-700 disabled:opacity-60">
                    <span x-show="adjSaving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!adjSaving" class="bi bi-check2-circle"></i> Simpan
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- MODAL REJECT -->
    <x-ui.modal id="modalReject" size="max-w-lg">
        <x-slot:header>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <h2 class="flex min-w-0 items-center gap-2.5 text-sm font-bold text-slate-800">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-600 text-white"><i class="bi bi-x-circle text-sm"></i></span>
                    <span class="truncate">Tolak Klaim</span>
                </h2>
                <button type="button" @click="$store.modals.close('#modalReject')" aria-label="Tutup"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </x-slot:header>
        <form @submit.prevent="submitReject()" novalidate class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <div class="flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-800">
                    <i class="bi bi-info-circle-fill mt-0.5 text-blue-500"></i>
                    <div class="font-semibold">Poin otomatis <b>direfund</b> dan <b>stok dipulihkan</b>. Tindakan tercatat sebagai audit.</div>
                </div>
                <div>
                    <label for="rejectAlasan" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Alasan penolakan <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="rejectAlasan" x-model="rejectForm.alasan" minlength="5" maxlength="500" required
                           placeholder="Contoh: Stok fisik habis / salah pilih voucher"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>
            </div>
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#modalReject')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="rejectSaving"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-rose-700 disabled:opacity-60">
                    <span x-show="rejectSaving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!rejectSaving" class="bi bi-x-circle"></i> Konfirmasi Tolak
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- MODAL HISTORY -->
    <x-ui.modal id="modalHistory" size="max-w-2xl">
        <x-slot:header>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <h2 class="flex min-w-0 items-center gap-2.5 text-sm font-bold text-slate-800">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-white"><i class="bi bi-clock-history text-sm"></i></span>
                    <span class="truncate">Riwayat Mutasi Poin</span>
                </h2>
                <button type="button" @click="$store.modals.close('#modalHistory')" aria-label="Tutup"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </x-slot:header>
        <div class="space-y-4 overflow-y-auto p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="min-w-0">
                    <h3 class="m-0 text-base font-bold text-slate-800" x-text="hist.nama || '–'"></h3>
                    <span class="text-xs text-slate-400">Transaksi penambahan &amp; pemotongan poin</span>
                </div>
                <div class="text-right">
                    <span class="block text-[11px] font-bold uppercase text-slate-400">Total Saldo</span>
                    <span class="text-xl font-bold tabular-nums text-blue-600" x-text="fmtNum(hist.saldo) + ' pts'"></span>
                </div>
            </div>

            <div class="max-h-[350px] overflow-auto rounded-xl border border-slate-200/80">
                <table class="w-full border-collapse text-left text-xs">
                    <thead class="sticky top-0 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr class="border-b border-slate-200/80">
                            <th class="px-3 py-2">Waktu</th>
                            <th class="px-3 py-2">Tipe</th>
                            <th class="px-3 py-2">Keterangan</th>
                            <th class="px-3 py-2 text-right">Poin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <template x-if="hist.loading">
                            <tr><td colspan="4" class="px-3 py-8 text-center text-slate-400">Memuat data...</td></tr>
                        </template>
                        <template x-if="!hist.loading && hist.error">
                            <tr><td colspan="4" class="px-3 py-6 text-center text-rose-500" x-text="hist.error"></td></tr>
                        </template>
                        <template x-if="!hist.loading && !hist.error && hist.rows.length === 0">
                            <tr><td colspan="4" class="px-3 py-8 text-center text-slate-400">Belum ada transaksi.</td></tr>
                        </template>
                        <template x-for="h in hist.rows" :key="h.key">
                            <tr>
                                <td class="whitespace-nowrap px-3 py-2 text-slate-500" x-text="h.waktu"></td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold" :class="h.kelas" x-text="h.tipe"></span>
                                </td>
                                <td class="px-3 py-2" x-text="h.keterangan"></td>
                                <td class="px-3 py-2 text-right font-bold tabular-nums" :class="h.poin >= 0 ? 'text-emerald-600' : 'text-rose-500'"
                                    x-text="(h.poin >= 0 ? '+' : '') + fmtNum(h.poin) + ' pts'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="flex shrink-0 items-center justify-end border-t border-slate-100 bg-slate-50 px-5 py-3.5">
            <button type="button" @click="$store.modals.close('#modalHistory')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Tutup</button>
        </div>
    </x-ui.modal>

    <!-- MODAL ANNUAL -->
    <x-ui.modal id="modalAnnual" size="max-w-2xl">
        <x-slot:header>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <h2 class="flex min-w-0 items-center gap-2.5 text-sm font-bold text-slate-800">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white"><i class="bi bi-pie-chart text-sm"></i></span>
                    <span class="truncate">Rincian Kinerja Tahunan</span>
                </h2>
                <button type="button" @click="$store.modals.close('#modalAnnual')" aria-label="Tutup"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </x-slot:header>
        <div class="space-y-4 overflow-y-auto p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="min-w-0">
                    <h3 class="m-0 text-base font-bold text-slate-800" x-text="annual.nama || '–'"></h3>
                    <span class="text-xs text-slate-400">Kinerja disiplin tahun <span class="font-bold text-slate-600">{{ $currentYear }}</span></span>
                </div>
                <div class="text-right">
                    <span class="block text-[11px] font-bold uppercase text-slate-400">Total Tahunan</span>
                    <span class="text-xl font-bold tabular-nums text-blue-600" x-text="fmtNum(annual.saldo) + ' pts'"></span>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="mb-2 text-[11px] font-bold uppercase text-slate-400">Tren bulanan (hijau = positif, merah = negatif)</div>
                <div class="flex h-[132px] items-stretch gap-1.5">
                    <template x-for="m in annual.months" :key="m.name">
                        <div class="flex h-full min-w-0 flex-1 flex-col items-center justify-end">
                            <span class="mb-1 text-[9px] font-bold leading-none" :class="m.value >= 0 ? 'text-emerald-600' : 'text-rose-500'" x-text="m.label"></span>
                            <div class="flex w-full flex-1 flex-col">
                                <div class="flex flex-1 flex-col justify-end">
                                    <div x-show="m.dir === 'up'" class="w-full rounded-t bg-emerald-500" :style="'height:' + m.h + 'px'"></div>
                                </div>
                                <div class="border-t border-dashed border-slate-300"></div>
                                <div class="flex flex-1 flex-col justify-start">
                                    <div x-show="m.dir === 'down'" class="w-full rounded-b bg-rose-500" :style="'height:' + m.h + 'px'"></div>
                                </div>
                            </div>
                            <span class="mt-1 text-[9px] text-slate-400" x-text="m.name"></span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <div class="mb-2 text-[11px] font-bold uppercase text-slate-400">Ringkasan per tipe</div>
                    <div class="space-y-1 text-sm">
                        <template x-if="annual.loading">
                            <span class="text-slate-400">Memuat...</span>
                        </template>
                        <template x-if="!annual.loading && annual.types.length === 0">
                            <span class="text-slate-400">Belum ada transaksi.</span>
                        </template>
                        <template x-for="t in annual.types" :key="t.tipe">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-1">
                                <span class="flex items-center gap-1.5">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold" :class="t.kelas" x-text="t.tipe"></span>
                                    <span class="text-[11px] text-slate-400" x-text="t.count + 'x'"></span>
                                </span>
                                <span class="font-bold tabular-nums" :class="t.poin >= 0 ? 'text-emerald-600' : 'text-rose-500'" x-text="t.label"></span>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="max-h-56 overflow-auto rounded-xl border border-slate-200/80">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead class="sticky top-0 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr class="border-b border-slate-200/80">
                                <th class="px-3 py-2">Waktu</th>
                                <th class="px-3 py-2">Tipe</th>
                                <th class="px-3 py-2 text-right">Poin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <template x-if="annual.loading">
                                <tr><td colspan="3" class="px-3 py-8 text-center text-slate-400">Memuat...</td></tr>
                            </template>
                            <template x-if="!annual.loading && annual.error">
                                <tr><td colspan="3" class="px-3 py-6 text-center text-rose-500" x-text="annual.error"></td></tr>
                            </template>
                            <template x-for="h in annual.rows" :key="h.key">
                                <tr>
                                    <td class="whitespace-nowrap px-3 py-2 text-slate-500" x-text="h.waktu"></td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold" :class="h.kelas" x-text="h.tipe"></span>
                                    </td>
                                    <td class="px-3 py-2 text-right font-bold tabular-nums" :class="h.poin >= 0 ? 'text-emerald-600' : 'text-rose-500'"
                                        x-text="(h.poin >= 0 ? '+' : '') + fmtNum(h.poin)"></td>
                                </tr>
                            </template>
                            <template x-if="!annual.loading && annual.dibatasi">
                                <tr><td colspan="3" class="px-3 py-2 text-center text-[11px] text-amber-600"
                                        x-text="'Menampilkan 200 dari ' + annual.totalMutasi + ' mutasi — ringkasan di atas dihitung dari seluruhnya.'"></td></tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="flex shrink-0 items-center justify-end border-t border-slate-100 bg-slate-50 px-5 py-3.5">
            <button type="button" @click="$store.modals.close('#modalAnnual')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Tutup</button>
        </div>
    </x-ui.modal>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('rewardPage', (config = {}) => ({
        csrf: config.csrf || '',
        urls: config.urls || {},
        tahun: config.tahun || '',
        tab: 'katalog',
        catalog: { query: '', type: '', status: '', sort: 'poin-asc' },
        rewards: (config.rewards || []).map((r) => ({
            id: Number(r.id) || 0,
            nama: r.nama || '',
            deskripsi: r.deskripsi || '',
            poin: Number(r.poin_dibutuhkan) || 0,
            stok: Number(r.stok) || 0,
            nominal: Number(r.nominal) || 0,
            aktif: Number(r.is_active) === 1,
            voucher: Number(r.nominal) > 0,
        })),
        rewardForm: { id: null, nama: '', deskripsi: '', poin: 1, stok: 0, nominal: 0, aktif: true },
        tierForm: { id: null, nama_tier: '', poin_min: 0, poin_max: 0, badge: 'bi-star-fill', warna: '#eab308' },
        adjForm: { user_id: null, nama: '', saldo: 0, tipe: 'BONUS', poin: '', keterangan: '' },
        rejectForm: { id: null, alasan: '' },
        hist: { nama: '', saldo: 0, loading: false, error: '', rows: [] },
        annual: { nama: '', saldo: 0, loading: false, error: '', months: [], types: [], rows: [], dibatasi: false, totalMutasi: 0 },
        rewardSaving: false,
        tierSaving: false,
        adjSaving: false,
        rejectSaving: false,

        init() {
            const hash = String(window.location.hash || '').replace('#', '');

            if (['katalog', 'klaim', 'guru', 'tier'].includes(hash)) {
                this.tab = hash;
            }
        },

        setTab(tab) {
            this.tab = tab;
            history.replaceState(null, '', '#' + tab);
        },

        tabClass(tab) {
            return 'inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-xs font-bold whitespace-nowrap transition-all '
                + (this.tab === tab ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-500 hover:bg-white/70 hover:text-slate-700');
        },

        reload() {
            window.location.reload();
        },

        /* ── Katalog ─────────────────────────────── */

        get filteredCatalog() {
            const q = this.catalog.query.trim().toLowerCase();
            const rows = this.rewards.filter((r) => {
                if (q && !(r.nama + ' ' + r.deskripsi).toLowerCase().includes(q)) return false;
                if (this.catalog.type === 'voucher' && !r.voucher) return false;
                if (this.catalog.type === 'item' && r.voucher) return false;
                if (this.catalog.status !== '' && r.aktif !== (this.catalog.status === '1')) return false;

                return true;
            });

            return rows.slice().sort((a, b) => {
                if (this.catalog.sort === 'poin-desc') return b.poin - a.poin;
                if (this.catalog.sort === 'nama-asc') return a.nama.localeCompare(b.nama, 'id');
                if (this.catalog.sort === 'stok-asc') return a.stok - b.stok;

                return a.poin - b.poin;
            });
        },

        ratio(r) {
            return r.voucher && r.poin > 0 ? r.nominal / r.poin : 0;
        },

        stokPct(r) {
            return Math.min(100, Math.max(0, r.stok >= 20 ? 100 : r.stok * 5));
        },

        stokBar(r) {
            if (r.stok <= 0) return 'bg-rose-500';
            if (r.stok <= 5) return 'bg-amber-500';

            return 'bg-emerald-500';
        },

        /* ── Util ────────────────────────────────── */

        fmtNum(n) {
            return new Intl.NumberFormat('id-ID').format(Number(n) || 0);
        },

        fmtRp(n) {
            return 'Rp ' + this.fmtNum(n);
        },

        fmtDate(value) {
            if (!value) return '–';
            const d = new Date(String(value).replace(' ', 'T'));
            if (Number.isNaN(d.getTime())) return String(value);

            return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' })
                + ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        },

        tipeClass(tipe) {
            const up = String(tipe || 'INFO').toUpperCase();
            if (['HADIR', 'REWARD', 'BONUS', 'REFUND'].includes(up)) return 'bg-emerald-50 text-emerald-700';
            if (['TELAT', 'PENALTI', 'PENALTY', 'ALPA', 'ALPHA'].includes(up)) return 'bg-rose-50 text-rose-700';
            if (['REDEEM', 'CLAIMED', 'PENDING'].includes(up)) return 'bg-amber-50 text-amber-700';
            if (['IZIN', 'SAKIT', 'CUTI', 'DINAS'].includes(up)) return 'bg-blue-50 text-blue-700';

            return 'bg-slate-100 text-slate-600';
        },

        async request(url, options = {}) {
            try {
                const res = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    ...options,
                });

                return await res.json();
            } catch (e) {
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');

                return null;
            }
        },

        async post(url, data = {}) {
            const body = new FormData();
            Object.entries(data).forEach(([key, value]) => body.append(key, value ?? ''));
            body.append('_token', this.csrf);

            return this.request(url, { method: 'POST', body });
        },

        async get(url) {
            return this.request(url);
        },

        /** Tutup modal + notifikasi sukses; muat ulang halaman agar data server ikut segar. */
        async done(res, modalId, title = 'Berhasil!') {
            if (!res) return false;

            if (!res.status) {
                Swal.fire('Gagal', res.message || 'Terjadi kesalahan', 'error');

                return false;
            }

            if (modalId) this.$store.modals.close(modalId);

            await Swal.fire({ icon: 'success', title, text: res.message, timer: 1300, showConfirmButton: false });
            this.reload();

            return true;
        },

        /* ── Reward ──────────────────────────────── */

        openReward(r = null) {
            this.rewardForm = r
                ? { id: r.id, nama: r.nama, deskripsi: r.deskripsi, poin: r.poin, stok: r.stok, nominal: r.nominal, aktif: r.aktif }
                : { id: null, nama: '', deskripsi: '', poin: 1, stok: 0, nominal: 0, aktif: true };
            this.$store.modals.open('#modalReward');
        },

        async saveReward() {
            const f = this.rewardForm;
            this.rewardSaving = true;

            try {
                const res = await this.post(this.urls.saveReward, {
                    id: f.id || '',
                    nama: f.nama,
                    deskripsi: f.deskripsi,
                    poin_dibutuhkan: f.poin,
                    stok: f.stok,
                    nominal: f.nominal,
                    is_active: f.aktif ? 1 : 0,
                });
                await this.done(res, '#modalReward', 'Reward Tersimpan!');
            } finally {
                this.rewardSaving = false;
            }
        },

        async deleteReward(r) {
            const confirm = await Swal.fire({
                title: 'Hapus Reward?',
                text: 'Reward "' + r.nama + '" akan dihapus permanen. Jika sudah ada riwayat penukaran, gunakan nonaktifkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
            });

            if (!confirm.isConfirmed) return;

            await this.done(await this.post(this.urls.deleteReward + '/' + r.id), null, 'Terhapus');
        },

        /* ── Tier ────────────────────────────────── */

        openTier(tier = null) {
            this.tierForm = tier
                ? {
                    id: tier.id,
                    nama_tier: tier.nama_tier || '',
                    poin_min: Number(tier.poin_min) || 0,
                    poin_max: Number(tier.poin_max) || 0,
                    badge: tier.badge || 'bi-star-fill',
                    warna: tier.warna || '#eab308',
                }
                : { id: null, nama_tier: '', poin_min: 0, poin_max: 0, badge: 'bi-star-fill', warna: '#3b82f6' };
            this.$store.modals.open('#modalTier');
        },

        async saveTier() {
            const f = this.tierForm;
            this.tierSaving = true;

            try {
                const res = await this.post(this.urls.saveTier, {
                    id: f.id || '',
                    nama_tier: f.nama_tier,
                    poin_min: f.poin_min,
                    poin_max: f.poin_max,
                    badge: f.badge,
                    warna: f.warna,
                });
                await this.done(res, '#modalTier', 'Tier Tersimpan!');
            } finally {
                this.tierSaving = false;
            }
        },

        async deleteTier(id, nama) {
            const confirm = await Swal.fire({
                title: 'Hapus Tier?',
                text: 'Tier "' + nama + '" akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
            });

            if (!confirm.isConfirmed) return;

            await this.done(await this.post(this.urls.deleteTier + '/' + id), null, 'Terhapus');
        },

        /* ── Klaim ───────────────────────────────── */

        async approve(id) {
            const confirm = await Swal.fire({
                title: 'Setujui Klaim?',
                text: 'Voucher langsung dapat dipakai guru untuk potong denda.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                confirmButtonText: 'Ya, Setujui',
                cancelButtonText: 'Batal',
            });

            if (!confirm.isConfirmed) return;

            await this.done(await this.post(this.urls.approve + '/' + id), null, 'Disetujui!');
        },

        openReject(id) {
            this.rejectForm = { id, alasan: '' };
            this.$store.modals.open('#modalReject');
        },

        async submitReject() {
            const f = this.rejectForm;
            this.rejectSaving = true;

            try {
                const res = await this.post(this.urls.reject + '/' + f.id, { alasan: f.alasan });
                await this.done(res, '#modalReject', 'Ditolak & Refund!');
            } finally {
                this.rejectSaving = false;
            }
        },

        /* ── Penyesuaian poin ────────────────────── */

        openAdjust(userId, nama, saldo) {
            this.adjForm = { user_id: userId, nama, saldo: Number(saldo) || 0, tipe: 'BONUS', poin: '', keterangan: '' };
            this.$store.modals.open('#modalAdjust');
        },

        get adjSaldoBaru() {
            const poin = Math.abs(Number(this.adjForm.poin) || 0);

            return this.adjForm.saldo + (this.adjForm.tipe === 'PENALTY' ? -poin : poin);
        },

        async saveAdjust() {
            const f = this.adjForm;
            this.adjSaving = true;

            try {
                const res = await this.post(this.urls.adjust, {
                    user_id: f.user_id,
                    tipe: f.tipe,
                    poin: f.poin,
                    keterangan: f.keterangan,
                });
                await this.done(res, '#modalAdjust', 'Poin Disesuaikan!');
            } finally {
                this.adjSaving = false;
            }
        },

        /* ── Riwayat & rincian tahunan ───────────── */

        async openHistory(userId, nama) {
            this.hist = { nama, saldo: 0, loading: true, error: '', rows: [] };
            this.$store.modals.open('#modalHistory');

            const res = await this.get(this.urls.history + '/' + userId);
            this.hist.loading = false;

            if (!res || !res.status) {
                this.hist.error = (res && res.message) || 'Gagal memuat data';

                return;
            }

            this.hist.saldo = Number(res.data.saldo) || 0;
            this.hist.rows = (res.data.history || []).map((h, i) => ({
                key: i + '-' + (h.created_at || ''),
                waktu: this.fmtDate(h.created_at),
                tipe: h.tipe || 'INFO',
                kelas: this.tipeClass(h.tipe),
                keterangan: h.keterangan || '–',
                poin: Number(h.poin) || 0,
            }));
        },

        async openAnnual(userId, nama) {
            this.annual = {
                nama,
                saldo: 0,
                loading: true,
                error: '',
                months: [],
                types: [],
                rows: [],
                dibatasi: false,
                totalMutasi: 0,
            };
            this.$store.modals.open('#modalAnnual');

            const res = await this.get(this.urls.annual + '/' + userId);
            this.annual.loading = false;

            if (!res || !res.status) {
                this.annual.error = (res && res.message) || 'Gagal memuat data';

                return;
            }

            const data = res.data || {};
            const monthly = data.monthly || [];
            const max = Math.max(1, ...monthly.map((v) => Math.abs(Number(v) || 0)));
            const namaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

            this.annual.saldo = Number(data.saldo_tahunan) || 0;
            this.annual.months = namaBulan.map((name, index) => {
                const value = Number(monthly[index]) || 0;

                return {
                    name,
                    value,
                    label: this.fmtNum(value),
                    h: Math.max(2, Math.round((Math.abs(value) / max) * 50)),
                    dir: value === 0 ? 'zero' : (value > 0 ? 'up' : 'down'),
                };
            });
            this.annual.types = Object.entries(data.type_summary || {}).map(([tipe, info]) => {
                const poin = Number(info.poin) || 0;

                return {
                    tipe,
                    kelas: this.tipeClass(tipe),
                    count: info.count,
                    poin,
                    label: (poin >= 0 ? '+' : '') + this.fmtNum(poin),
                };
            });
            this.annual.rows = (data.history || []).map((h, i) => ({
                key: i + '-' + (h.created_at || ''),
                waktu: this.fmtDate(h.created_at),
                tipe: h.tipe || 'INFO',
                kelas: this.tipeClass(h.tipe),
                poin: Number(h.poin) || 0,
            }));
            this.annual.dibatasi = Boolean(data.dibatasi);
            this.annual.totalMutasi = Number(data.total_mutasi) || 0;
        },

        /* ── Sinkronisasi tahunan ────────────────── */

        async seedPoin() {
            const confirm = await Swal.fire({
                title: 'Sinkronisasi Poin ' + this.tahun + '?',
                text: 'Menghitung ulang mutasi presensi seluruh guru Jan–Des ' + this.tahun + '. Idempotent (tidak duplikat).',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                confirmButtonText: 'Ya, Sinkronkan',
                cancelButtonText: 'Batal',
            });

            if (!confirm.isConfirmed) return;

            Swal.fire({ title: 'Memproses...', text: 'Menyinkronkan presensi ke poin...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            const res = await this.post(this.urls.seed, { tahun: this.tahun });
            Swal.close();
            await this.done(res, null, 'Selesai!');
        },
    }));
});
</script>
@endsection
