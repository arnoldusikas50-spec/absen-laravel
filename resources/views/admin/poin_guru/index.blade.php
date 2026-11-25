@extends('layouts.main')

@section('content')
@php
    // array_column() tidak menerimanya, gunakan API Collection.
    $avg_point = collect($teachers)->avg('poin_tahunan') ?? 0;
    $top_teacher = collect($teachers)->first();

    $rankClasses = [
        'bg-[#FFD700] text-[#856404] shadow-[0_0_10px_rgba(255,215,0,0.5)]',
        'bg-[#C0C0C0] text-[#383d41]',
        'bg-[#CD7F32] text-white',
        'bg-slate-100 text-slate-500',
    ];
@endphp

<div class="space-y-4 px-4 pb-8 pt-4 md:px-6 md:pt-6">

    <!-- Hero Section -->
    <div x-data class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#667eea] to-[#764ba2] p-6 text-white shadow-lg shadow-purple-900/20 md:p-8">
        <div class="pointer-events-none absolute -right-10 -top-20 h-[300px] w-[300px] rounded-full bg-white/10"></div>
        <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl font-extrabold tracking-tight text-white md:text-3xl">Disiplin Guru {{ $currentYear }}</h1>
                <p class="mt-1.5 max-w-2xl text-sm text-white/75">Sistem Pemeringkatan Disiplin dan Dedikasi Otomatis Berdasarkan Data Kehadiran.</p>
            </div>
            <a href="{{ url('admin/poinguru/recalculate') }}"
                @click="if (!confirm('Proses ini akan menghitung ulang semua poin dari data absensi tahun ini berdasarkan konfigurasi terbaru. Lanjutkan?')) $event.preventDefault()"
                class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-white/30 bg-white/20 px-5 py-2.5 text-xs font-bold text-white no-underline backdrop-blur-sm transition-all hover:-translate-y-0.5 hover:bg-white hover:text-[#764ba2]">
                <i class="bi bi-arrow-repeat"></i> Update Data Poin
            </a>
        </div>
    </div>

    @if (session('message'))
        <div class="flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs text-sky-900" role="status">
            <i class="bi bi-check-circle-fill mt-0.5 shrink-0 text-2xl text-sky-600"></i>
            <div>{{ session('message') }}</div>
        </div>
    @endif

    <!-- Stats Row -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.card bodyClass="p-4">
            <div class="flex items-center gap-3">
                <span class="flex shrink-0 items-center justify-center rounded-xl bg-blue-50 p-3 text-3xl text-blue-600">
                    <i class="bi bi-people"></i>
                </span>
                <div class="min-w-0">
                    <h6 class="mb-1 text-sm text-slate-400">Total Guru Terdata</h6>
                    <h3 class="m-0 text-xl font-bold text-slate-800">{{ count($teachers) }}</h3>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card bodyClass="p-4" style="border-left-width: 4px; border-left-color: #10b981">
            <div class="flex items-center gap-3">
                <span class="flex shrink-0 items-center justify-center rounded-xl bg-emerald-50 p-3 text-3xl text-emerald-600">
                    <i class="bi bi-trophy"></i>
                </span>
                <div class="min-w-0 overflow-hidden">
                    <h6 class="mb-1 text-sm text-slate-400">Peringkat 1</h6>
                    <h5 class="m-0 truncate text-base font-bold text-slate-800">{!! $top_teacher ? e($top_teacher->nama_guru) : '-' !!}</h5>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card bodyClass="p-4">
            <div class="flex items-center gap-3">
                <span class="flex shrink-0 items-center justify-center rounded-xl bg-cyan-50 p-3 text-3xl text-cyan-600">
                    <i class="bi bi-graph-up"></i>
                </span>
                <div class="min-w-0">
                    <h6 class="mb-1 text-sm text-slate-400">Rata-rata Poin</h6>
                    <h3 class="m-0 text-xl font-bold text-slate-800">{{ number_format($avg_point, 1) }}</h3>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card bodyClass="p-4" style="border-left-width: 4px; border-left-color: #fbbf24">
            <div class="flex items-center gap-3">
                <span class="flex shrink-0 items-center justify-center rounded-xl bg-amber-50 p-3 text-3xl text-amber-500">
                    <i class="bi bi-coin"></i>
                </span>
                <div class="min-w-0">
                    <h6 class="mb-1 text-sm text-slate-400">Total Poin {{ $currentYear }}</h6>
                    <h3 class="m-0 text-xl font-bold text-slate-800">{{ number_format($annualTotalPoin) }}</h3>
                </div>
            </div>
        </x-ui.card>
    </div>

    <!-- Ranking Table Card -->
    <x-ui.card title="Daftar Peringkat Guru" icon="bi-list-stars" tone="blue" bodyClass="p-0"
        x-data="{
            query: '',
            page: 1,
            perPage: 25,
            total: {{ count($teachers) }},
            pages: 1,
            start: 0,
            end: 0,
            apply() {
                const q = this.query.toLowerCase().trim();
                const all = this.$refs.rows ? Array.from(this.$refs.rows.querySelectorAll('[data-search]')) : [];
                const matched = all.filter((row) => q === '' || row.dataset.search.includes(q));
                this.total = matched.length;
                this.pages = Math.max(1, Math.ceil(matched.length / this.perPage));
                if (this.page > this.pages) this.page = this.pages;
                const from = (this.page - 1) * this.perPage;
                const to = from + this.perPage;
                all.forEach((row) => row.classList.add('hidden'));
                matched.slice(from, to).forEach((row) => row.classList.remove('hidden'));
                this.start = matched.length === 0 ? 0 : from + 1;
                this.end = Math.min(to, matched.length);
            },
            search() {
                this.page = 1;
                this.apply();
            },
            go(target) {
                if (target < 1 || target > this.pages) return;
                this.page = target;
                this.apply();
            }
        }"
        x-init="apply()">
        <x-slot:meta>
            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600">
                <i class="bi bi-calendar-event"></i> Data Tahun {{ $currentYear }}
            </span>
        </x-slot:meta>

        <x-slot:toolbar>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:w-64">
                    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-slate-400"></i>
                    <input type="search" x-model="query" @input.debounce.200ms="search()" placeholder="Cari Guru:" aria-label="Cari guru"
                        autocomplete="off"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-3 text-xs font-semibold text-slate-800 transition-colors focus:border-transparent focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex flex-wrap items-center gap-2 text-[11px] font-semibold text-slate-500">
                    <span>
                        Menampilkan <span x-text="start"></span> sampai <span x-text="end"></span> dari <span x-text="total"></span> guru
                    </span>
                    <div class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1">
                        <button type="button" @click="go(page - 1)" :disabled="page <= 1" title="Halaman sebelumnya"
                            class="inline-flex h-6 w-6 items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-white hover:text-blue-600 disabled:opacity-40">
                            <i class="bi bi-chevron-left text-[10px]"></i>
                        </button>
                        <span class="px-1 tabular-nums"><span x-text="page"></span> / <span x-text="pages"></span></span>
                        <button type="button" @click="go(page + 1)" :disabled="page >= pages" title="Halaman berikutnya"
                            class="inline-flex h-6 w-6 items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-white hover:text-blue-600 disabled:opacity-40">
                            <i class="bi bi-chevron-right text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>
        </x-slot:toolbar>

        @if (empty($teachers))
            <div class="p-5">
                <x-ui.empty icon="bi-people" title="Belum ada guru terdata"
                    description="Belum ada data guru pada tahun ini." />
            </div>
        @else
        <div x-ref="rows">
            <x-ui.table id="table-points">
                <x-slot:head>
                    <th class="px-4 py-3 text-left" width="80">Rank</th>
                    <th class="px-4 py-3 text-left">Guru</th>
                    <th class="px-4 py-3 text-left">Identitas</th>
                    <th class="px-4 py-3 text-right">Poin Disiplin</th>
                    <th class="px-4 py-3 text-center" width="120">Action</th>
                </x-slot:head>

                @foreach ($teachers as $i => $guru)
                    @php
                        $rankClass = $rankClasses[$i] ?? $rankClasses[3];

                        $photo = $guru->foto ?: $guru->user_foto;
                        $initials = substr($guru->nama_guru, 0, 1) . (strpos($guru->nama_guru, ' ') ? substr($guru->nama_guru, strpos($guru->nama_guru, ' ') + 1, 1) : '');
                        $fotoUrl = '';
                        if ($photo) {
                            if (strpos($photo, 'uploads/') === false) $photo = 'uploads/profile_guru/' . $photo;
                            if (file_exists(public_path() . '/' . $photo)) $fotoUrl = url($photo);
                        }
                    @endphp
                    <tr data-search="{{ strtolower(trim(($guru->nama_guru ?? '') . ' ' . ($guru->nip ?? ''))) }}"
                        class="transition-colors hover:bg-slate-50/70 {{ $i >= 25 ? 'hidden' : '' }}">
                        <td class="px-4 py-3" width="80">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-extrabold {{ $rankClass }}">
                                {{ $i + 1 }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($fotoUrl)
                                    <img src="{{ $fotoUrl }}" class="h-11 w-11 shrink-0 rounded-xl object-cover shadow-sm" alt="Avatar {{ $guru->nama_guru }}">
                                @else
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 font-bold uppercase text-slate-600">{{ $initials }}</div>
                                @endif
                                <div class="min-w-0">
                                    <h6 class="m-0 truncate font-bold uppercase text-slate-800">{{ $guru->nama_guru }}</h6>
                                    <span class="text-sm uppercase text-slate-400">Guru Tetap</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-bold text-slate-400">{{ $guru->nip ?: '-' }}</div>
                            <div class="text-[11px] text-slate-400 opacity-75">NIP / ID User</div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="rounded-full px-4 py-2 text-lg font-extrabold {{ $guru->poin_tahunan < 0 ? 'bg-red-50 text-red-500' : 'bg-emerald-50 text-emerald-600' }}">
                                {{ $guru->poin_tahunan > 0 ? '+' : '' }}{{ number_format($guru->poin_tahunan, 1) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ url('admin/poinguru/detail/' . $guru->id_guru) }}"
                                class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1.5 text-[11px] font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                                <i class="bi bi-eye text-blue-600"></i> Detail
                            </a>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        </div>

        <div class="px-5 py-8 text-center text-xs text-slate-400" x-show="total === 0" x-cloak>
            <i class="bi bi-search me-1"></i> Tidak ada guru yang cocok dengan pencarian.
        </div>
        @endif
    </x-ui.card>

    <!-- Formula Reference -->
    <x-ui.card title="Sistem Poin Terintegrasi — Poin History" icon="bi-info-circle" tone="slate">
        <div class="grid grid-cols-1 gap-4">
            <div class="flex items-start gap-2">
                <i class="bi bi-check-circle mt-1 text-emerald-600"></i>
                <div>
                    <div class="font-bold text-slate-800">Sumber Data</div>
                    <div class="text-slate-600">Semua poin bersumber dari <code class="rounded bg-slate-100 px-1 py-0.5 text-[11px]">poin_history</code>, diisi real-time saat check-in/out dan cron.</div>
                </div>
            </div>
            <div class="flex items-start gap-2">
                <i class="bi bi-arrow-repeat mt-1 text-blue-600"></i>
                <div>
                    <div class="font-bold text-slate-800">Jenis Poin</div>
                    <div class="text-slate-600">HADIR (+progresif), TELAT (-5), PENALTI (-2), REWARD (+3), ALPA (-10), BONUS (skor disiplin), REDEEM, CLAIMED, REFUND</div>
                </div>
            </div>
            <div class="flex items-start gap-2">
                <i class="bi bi-database mt-1 text-slate-500"></i>
                <div>
                    <div class="font-bold text-slate-800">Sinkronisasi</div>
                    <div class="text-slate-600">Tombol "Update Data Poin" menjalankan seed ulang + menyetel <code class="rounded bg-slate-100 px-1 py-0.5 text-[11px]">poin_tahunan</code> dari total <code class="rounded bg-slate-100 px-1 py-0.5 text-[11px]">poin_history</code> tahun berjalan.</div>
                </div>
            </div>
        </div>
    </x-ui.card>
</div>
@endsection
