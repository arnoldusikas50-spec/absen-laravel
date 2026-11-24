@extends('layouts.main')

@section('content')

<?php
$fmtWaktu = function ($v, $tgl = null) {
    $s = trim((string) $v);
    if ($s === '' || $s === '0' || $s === 'null' || strtolower($s) === 'kosong') {
        return null;
    }
    if (preg_match('/^(\d{4}-\d{2}-\d{2}) (.+)$/', $s, $m)) {
        return ($tgl && $m[1] === substr($tgl, 0, 10)) ? substr($m[2], 0, 5) : $m[2];
    }
    return strlen($s) > 5 ? substr($s, 0, 5) : $s;
};
$toMin = function ($hm) {
    if (! preg_match('/^(\d{1,2}):(\d{2})/', (string) $hm, $m)) return null;
    return ((int) $m[1]) * 60 + ((int) $m[2]);
};
$tone = [
    'badge-success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
    'badge-danger' => 'border-rose-200 bg-rose-50 text-rose-700',
    'badge-warning' => 'border-amber-200 bg-amber-50 text-amber-700',
    'badge-info' => 'border-sky-200 bg-sky-50 text-sky-700',
    'badge-primary' => 'border-blue-200 bg-blue-50 text-blue-700',
    'badge-secondary' => 'border-slate-200 bg-slate-100 text-slate-600',
];
$fieldInfo = [
    'jam_masuk' => ['label' => 'Jam Masuk', 'icon' => 'bi-box-arrow-in-right', 'cls' => 'badge-info'],
    'jam_pulang' => ['label' => 'Jam Pulang', 'icon' => 'bi-box-arrow-right', 'cls' => 'badge-warning'],
];
$statusMap = [
    'ONTIME' => ['Tepat', 'badge-success'], 'LATE' => ['Telat', 'badge-danger'],
    'IJIN' => ['Izin', 'badge-info'], 'IZIN' => ['Izin', 'badge-info'],
    'SAKIT' => ['Sakit', 'badge-info'], 'CUTI' => ['Cuti', 'badge-secondary'],
    'ALPHA' => ['Alpha', 'badge-danger'], 'ALPA' => ['Alpha', 'badge-danger'],
    'DINAS_PENDING' => ['Dinas (pengajuan)', 'badge-warning'],
    'DINAS_APPROVED' => ['Dinas', 'badge-primary'], 'DINAS' => ['Dinas', 'badge-primary'],
    'DINAS_REJECTED' => ['Dinas ditolak', 'badge-danger'], 'WFH' => ['WFH', 'badge-primary'],
];
$history = $history ?? [];
$summaryKoreksi = $summaryKoreksi ?? ['pending' => count($pending), 'oldest' => null, 'history' => count($history)];
$oldestText = ! empty($summaryKoreksi['oldest']) ? date('d/m/Y H:i', strtotime($summaryKoreksi['oldest'])) : '—';
$jumlahField = [];
foreach ($pending as $pf) {
    $kunciField = (string) ($pf->field_name ?? '');
    $jumlahField[$kunciField] = ($jumlahField[$kunciField] ?? 0) + 1;
}
$jumlahStatus = ['approved' => 0, 'rejected' => 0];
foreach ($history as $hh) {
    if (($hh->status ?? '') === 'approved') $jumlahStatus['approved']++;
    elseif (($hh->status ?? '') === 'rejected') $jumlahStatus['rejected']++;
}
$pageConfig = [
    'csrf' => csrf_token(),
    'urls' => [
        'approve' => url('admin/presensi/approve-koreksi'),
        'reject' => url('admin/presensi/reject-koreksi'),
    ],
];
?>

<div x-data="pendingKoreksiPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
    class="px-4 pb-6 pt-4 md:px-6 md:pt-6">

    <x-ui.page-header
        title="Koreksi Absensi"
        icon="bi-pencil-square"
        tone="amber"
        subtitle="Tinjau dan setujui permintaan koreksi kehadiran dari guru."
        :crumbs="['Presensi', 'Koreksi Absensi']">
        <x-slot:actions>
            <a href="{{ url('admin/presensi/dinas') }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 px-3.5 py-2 text-xs font-semibold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-briefcase text-cyan-600"></i> Verifikasi Dinas
            </a>
            <a href="{{ url('admin/presensi/izin') }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 px-3.5 py-2 text-xs font-semibold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-file-earmark-medical text-cyan-600"></i> Verifikasi Izin/Sakit
            </a>
            <a href="{{ url('admin/presensi') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mt-4 space-y-5">

        @if (! empty($pending))
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex gap-2.5">
                    <i class="bi bi-info-circle-fill mt-0.5 text-amber-500"></i>
                    <div class="text-[11px] leading-relaxed text-amber-800">
                        Menyetujui koreksi akan <strong>menghitung ulang status, poin, dan denda</strong> hari tersebut (termasuk cap denda bulanan). Pengajuan basi lain untuk absensi + field yang sama otomatis ditolak.
                    </div>
                </div>
            </div>
        @endif

        <!-- Ringkasan -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-500">
                    <i class="bi bi-hourglass-split text-lg"></i>
                </span>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Menunggu</div>
                    <div class="text-lg font-extrabold tabular-nums text-slate-900">{{ (int) $summaryKoreksi['pending'] }} <span class="text-xs font-normal text-slate-400">pengajuan</span></div>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i class="bi bi-calendar-event text-lg"></i>
                </span>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Pengajuan Tertua</div>
                    <div class="text-sm font-extrabold leading-tight tabular-nums text-slate-900">{{ $oldestText }}</div>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <i class="bi bi-check-circle text-lg"></i>
                </span>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Sudah Diproses</div>
                    <div class="text-lg font-extrabold tabular-nums text-slate-900">{{ (int) $summaryKoreksi['history'] }} <span class="text-xs font-normal text-slate-400">keputusan</span></div>
                </div>
            </div>
        </div>

        <!-- Daftar permintaan -->
        @if (empty($pending))
            <x-ui.card bodyClass="p-5">
                <x-ui.empty icon="bi-check2-circle" title="Tidak ada permintaan koreksi"
                    description="Semua permintaan koreksi telah diproses." />
            </x-ui.card>
        @else
            <div x-data="dataTable({ perPage: 100, filters: { field: '' } })">
                <x-ui.card title="Daftar Permintaan Koreksi" icon="bi-list-check" tone="amber"
                    subtitle="Tinjau selisih waktu sebelum menyetujui" bodyClass="p-0">
                    <x-slot:actions>
                        <div class="relative w-full sm:w-64">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                            <input type="text" x-ref="search" x-model="query" placeholder="Cari nama / NIP / tanggal..." autocomplete="off"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-8 text-xs font-semibold text-slate-800 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" x-show="query !== ''" x-cloak @click="query = ''; $refs.search.focus()" title="Bersihkan pencarian"
                                class="absolute right-2 top-1/2 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-200/70 hover:text-slate-700">
                                <i class="bi bi-x text-xs"></i>
                            </button>
                        </div>
                    </x-slot:actions>

                    <x-slot:toolbar>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="mr-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">Field</span>
                            <button type="button" @click="filters.field = ''"
                                :class="filters.field === '' ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-600 hover:text-blue-600'"
                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1 text-xs font-bold transition-colors">
                                Semua <span class="rounded-full bg-slate-100 px-1.5 text-[10px] font-extrabold text-slate-600">{{ count($pending) }}</span>
                            </button>
                            <button type="button" @click="filters.field = 'jam_masuk'"
                                :class="filters.field === 'jam_masuk' ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-600 hover:text-blue-600'"
                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1 text-xs font-bold transition-colors">
                                <i class="bi bi-box-arrow-in-right"></i>Masuk <span class="rounded-full bg-slate-100 px-1.5 text-[10px] font-extrabold text-slate-600">{{ $jumlahField['jam_masuk'] ?? 0 }}</span>
                            </button>
                            <button type="button" @click="filters.field = 'jam_pulang'"
                                :class="filters.field === 'jam_pulang' ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-600 hover:text-blue-600'"
                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1 text-xs font-bold transition-colors">
                                <i class="bi bi-box-arrow-right"></i>Pulang <span class="rounded-full bg-slate-100 px-1.5 text-[10px] font-extrabold text-slate-600">{{ $jumlahField['jam_pulang'] ?? 0 }}</span>
                            </button>
                        </div>
                    </x-slot:toolbar>

                    <x-ui.table class="min-w-[1060px]">
                        <x-slot:head>
                            <th class="w-10 px-4 py-3">#</th>
                            <th class="px-3 py-3">Tanggal Absen</th>
                            <th class="px-3 py-3">Guru</th>
                            <th class="px-3 py-3">Field</th>
                            <th class="px-3 py-3">Nilai Lama</th>
                            <th class="px-3 py-3">Nilai Baru</th>
                            <th class="px-3 py-3 text-center">Selisih</th>
                            <th class="min-w-[220px] px-3 py-3">Alasan</th>
                            <th class="px-3 py-3">Diajukan</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </x-slot:head>

                        @foreach ($pending as $p)
                            @php
                                $fInfo = $fieldInfo[$p->field_name] ?? ['label' => ucwords(str_replace('_', ' ', (string) $p->field_name)), 'icon' => 'bi-pencil-square', 'cls' => 'badge-info'];
                                $oldHm = $fmtWaktu($p->old_value ?? null, $p->tgl ?? null);
                                $newHm = $fmtWaktu($p->new_value ?? null, $p->tgl ?? null);
                                $jamIn = $fmtWaktu($p->jam_masuk ?? null, $p->tgl ?? null);
                                $jamOut = $fmtWaktu($p->jam_pulang ?? null, $p->tgl ?? null);
                                $stIn = strtoupper((string) ($p->status_masuk ?? ''));
                                [$stInLbl, $stInCls] = $statusMap[$stIn] ?? [($stIn !== '' ? ucwords(strtolower($stIn)) : '—'), 'badge-secondary'];
                                $attMissing = empty($p->tgl);
                                $dMin = null;
                                $om = $toMin($oldHm ?? '');
                                $nm = $toMin($newHm ?? '');
                                if ($om !== null && $nm !== null) $dMin = $nm - $om;
                                $searchTxt = implode(' ', array_filter([
                                    $p->nama_guru ?? '',
                                    $p->nip ?? '',
                                    ! empty($p->tgl) ? date('d/m/Y', strtotime($p->tgl)) : '',
                                    $fInfo['label'],
                                    $oldHm ?? 'Kosong',
                                    $newHm ?? '',
                                    $p->reason ?? '',
                                ]));
                            @endphp

                            <tr data-field="{{ $p->field_name ?? '' }}" data-search="{{ $searchTxt }}" class="transition-colors hover:bg-slate-50/60">
                                <td class="px-4 py-3"><span data-rownum class="tabular-nums text-xs text-slate-400">{{ $loop->iteration }}</span></td>
                                <td class="px-3 py-3">
                                    @if ($attMissing)
                                        <span class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-0.5 text-[11px] font-bold text-rose-700">
                                            <i class="bi bi-exclamation-triangle"></i>Data absen dihapus
                                        </span>
                                    @else
                                        <div class="whitespace-nowrap text-xs font-semibold text-slate-800">{{ date('d/m/Y', strtotime($p->tgl)) }}</div>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-1 text-[11px] text-slate-400">
                                            <span>Masuk {{ $jamIn ?: '-' }}</span>
                                            <span class="inline-flex items-center rounded-full border px-1.5 py-px text-[10px] font-bold {{ $tone[$stInCls] ?? $tone['badge-secondary'] }}">{{ $stInLbl }}</span>
                                            <span>·</span>
                                            <span>Pulang {{ $jamOut ?: '-' }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <div class="text-xs font-semibold text-slate-800">{{ $p->nama_guru ?? 'Guru #' . ($p->user_id ?? '-') }}</div>
                                    <div class="text-[11px] text-slate-400">NIP: {{ $p->nip ?? '-' }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $tone[$fInfo['cls']] ?? $tone['badge-info'] }}">
                                        <i class="bi {{ $fInfo['icon'] }}"></i>{{ $fInfo['label'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    <code class="rounded border border-rose-200 bg-rose-50 px-2 py-1 font-mono text-xs font-medium text-rose-600" title="{{ $p->old_value ?? 'Kosong' }}">
                                        {{ $oldHm ?? 'Kosong' }}
                                    </code>
                                </td>
                                <td class="px-3 py-3">
                                    <code class="rounded border border-emerald-200 bg-emerald-50 px-2 py-1 font-mono text-xs font-medium text-emerald-600" title="{{ $p->new_value ?? '-' }}">
                                        {{ $newHm ?? '-' }}
                                    </code>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($dMin === null)
                                        <span class="text-slate-300">–</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold tabular-nums {{ $dMin === 0 ? 'border-slate-200 bg-slate-100 text-slate-600' : ($dMin > 0 ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-sky-200 bg-sky-50 text-sky-700') }}" title="Baru − lama">
                                            {{ $dMin === 0 ? 'sama' : (($dMin > 0 ? '+' : '−') . abs($dMin) . ' mnt') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <span class="line-clamp-2 max-w-[240px] text-xs italic text-slate-500" title="{{ $p->reason ?? '-' }}">"{{ $p->reason ?? '-' }}"</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-[11px] text-slate-400">{{ ! empty($p->created_at) ? date('d/m/Y H:i', strtotime($p->created_at)) : '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($attMissing)
                                            <span class="text-[11px] text-slate-400" title="Data absensi sudah dihapus — hanya bisa ditolak">Hanya tolak</span>
                                        @endif
                                        <button type="button" @click="approve({{ (int) $p->id }})"
                                            :disabled="busy === {{ (int) $p->id }} || {{ $attMissing ? 'true' : 'false' }}"
                                            title="Setujui"
                                            class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1.5 text-[11px] font-bold text-white transition-colors hover:bg-emerald-700 disabled:opacity-40">
                                            <i class="bi bi-check-lg"></i>Setujui
                                        </button>
                                        <button type="button" @click="reject({{ (int) $p->id }})"
                                            :disabled="busy === {{ (int) $p->id }}"
                                            title="Tolak"
                                            class="inline-flex items-center gap-1 rounded-lg bg-rose-600 px-2.5 py-1.5 text-[11px] font-bold text-white transition-colors hover:bg-rose-700 disabled:opacity-40">
                                            <i class="bi bi-x-lg"></i>Tolak
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>

                    <x-slot:footer>
                        <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400">
                            <span>Menampilkan <span class="font-semibold text-slate-600" x-text="from"></span>–<span class="font-semibold text-slate-600" x-text="to"></span> dari <span class="font-semibold text-slate-600" x-text="total"></span> data</span>
                            <span>Setujui untuk menghitung ulang status &amp; poin</span>
                        </div>
                    </x-slot:footer>
                </x-ui.card>

                <div x-show="total === 0" x-cloak class="mt-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-6 py-10 text-center">
                    <i class="bi bi-search mb-2 block text-3xl text-slate-300"></i>
                    <p class="text-sm font-semibold text-slate-500">Tidak ada data yang cocok dengan filter</p>
                    <p class="mt-1 text-xs text-slate-400">Coba ubah kata kunci atau filter field</p>
                </div>
            </div>
        @endif

        <!-- Riwayat -->
        @if (empty($history))
            <x-ui.card title="Riwayat Keputusan" icon="bi-clock-history" tone="slate" bodyClass="p-5">
                <x-ui.empty icon="bi-inbox" title="Belum ada riwayat"
                    description="100 koreksi terakhir yang disetujui / ditolak akan tampil di sini." />
            </x-ui.card>
        @else
            <div x-data="dataTable({ perPage: 100, filters: { status: '' } })">
                <x-ui.card title="Riwayat Keputusan" icon="bi-clock-history" tone="slate"
                    subtitle="100 koreksi terakhir yang disetujui / ditolak" bodyClass="p-0">
                    <x-slot:actions>
                        <div class="relative w-full sm:w-64">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                            <input type="text" x-ref="search" x-model="query" placeholder="Cari nama / NIP / tanggal..." autocomplete="off"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-8 text-xs font-semibold text-slate-800 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" x-show="query !== ''" x-cloak @click="query = ''; $refs.search.focus()" title="Bersihkan pencarian"
                                class="absolute right-2 top-1/2 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-200/70 hover:text-slate-700">
                                <i class="bi bi-x text-xs"></i>
                            </button>
                        </div>
                    </x-slot:actions>

                    <x-slot:toolbar>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="mr-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">Status</span>
                            <button type="button" @click="filters.status = ''"
                                :class="filters.status === '' ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-600 hover:text-blue-600'"
                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1 text-xs font-bold transition-colors">
                                Semua <span class="rounded-full bg-slate-100 px-1.5 text-[10px] font-extrabold text-slate-600">{{ count($history) }}</span>
                            </button>
                            <button type="button" @click="filters.status = 'approved'"
                                :class="filters.status === 'approved' ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-600 hover:text-blue-600'"
                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1 text-xs font-bold transition-colors">
                                <span class="inline-block h-2 w-2 rounded-full bg-emerald-600"></span>Disetujui <span class="rounded-full bg-slate-100 px-1.5 text-[10px] font-extrabold text-slate-600">{{ $jumlahStatus['approved'] }}</span>
                            </button>
                            <button type="button" @click="filters.status = 'rejected'"
                                :class="filters.status === 'rejected' ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-600 hover:text-blue-600'"
                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1 text-xs font-bold transition-colors">
                                <span class="inline-block h-2 w-2 rounded-full bg-rose-600"></span>Ditolak <span class="rounded-full bg-slate-100 px-1.5 text-[10px] font-extrabold text-slate-600">{{ $jumlahStatus['rejected'] }}</span>
                            </button>
                        </div>
                    </x-slot:toolbar>

                    <x-ui.table class="min-w-[900px] text-xs">
                        <x-slot:head>
                            <th class="px-4 py-3">Tanggal Absen</th>
                            <th class="px-3 py-3">Guru</th>
                            <th class="px-3 py-3">Field</th>
                            <th class="px-3 py-3">Perubahan</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="px-3 py-3">Catatan Admin</th>
                            <th class="px-3 py-3">Diproses</th>
                        </x-slot:head>

                        @foreach ($history as $h)
                            @php
                                $approved = ($h->status ?? '') === 'approved';
                                $fLbl = $fieldInfo[$h->field_name]['label'] ?? $h->field_name;
                                $searchH = implode(' ', array_filter([
                                    $h->nama_guru ?? '',
                                    ! empty($h->tgl) ? date('d/m/Y', strtotime($h->tgl)) : '',
                                    $fLbl,
                                    $fmtWaktu($h->old_value ?? null, $h->tgl ?? null) ?? '',
                                    $fmtWaktu($h->new_value ?? null, $h->tgl ?? null) ?? '',
                                    $h->admin_note ?? '',
                                    $approved ? 'Disetujui' : 'Ditolak',
                                ]));
                            @endphp
                            <tr data-status="{{ $h->status }}" data-search="{{ $searchH }}" class="transition-colors hover:bg-slate-50/60">
                                <td class="whitespace-nowrap px-4 py-3 text-xs">
                                    @if (! empty($h->tgl))
                                        {{ date('d/m/Y', strtotime($h->tgl)) }}
                                    @else
                                        <span class="text-slate-300">absen dihapus</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-xs font-bold text-slate-800">{{ $h->nama_guru ?? '-' }}</td>
                                <td class="px-3 py-3 text-xs">{{ $fLbl }}</td>
                                <td class="px-3 py-3 font-mono text-xs tabular-nums">
                                    <span class="text-rose-500">{{ $fmtWaktu($h->old_value ?? null, $h->tgl ?? null) ?? '∅' }}</span>
                                    →
                                    <span class="font-bold text-emerald-600">{{ $fmtWaktu($h->new_value ?? null, $h->tgl ?? null) ?? '–' }}</span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $approved ? $tone['badge-success'] : $tone['badge-danger'] }}">
                                        {{ $approved ? 'Disetujui' : 'Ditolak' }}
                                    </span>
                                </td>
                                <td class="max-w-[240px] px-3 py-3 text-xs text-slate-500" title="{{ $h->admin_note ?? '' }}">
                                    {{ $h->admin_note ? mb_strimwidth($h->admin_note, 0, 90, '…') : '–' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-[11px] text-slate-400">
                                    {{ ! empty($h->processed_at) ? date('d/m/Y H:i', strtotime($h->processed_at)) : '-' }}
                                    @if (! empty($h->diproses_oleh) && trim((string) $h->diproses_oleh) !== '')
                                        <div>oleh {{ trim((string) $h->diproses_oleh) }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>

                    <x-slot:footer>
                        <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400">
                            <span>Menampilkan <span class="font-semibold text-slate-600" x-text="from"></span>–<span class="font-semibold text-slate-600" x-text="to"></span> dari <span class="font-semibold text-slate-600" x-text="total"></span> data</span>
                            <span>100 keputusan terakhir</span>
                        </div>
                    </x-slot:footer>
                </x-ui.card>

                <div x-show="total === 0" x-cloak class="mt-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-6 py-10 text-center">
                    <i class="bi bi-search mb-2 block text-3xl text-slate-300"></i>
                    <p class="text-sm font-semibold text-slate-500">Tidak ada data yang cocok dengan filter</p>
                    <p class="mt-1 text-xs text-slate-400">Coba ubah kata kunci atau filter status</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pendingKoreksiPage', () => ({
        config: { csrf: '', urls: {} },

        busy: null,

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');

            this.config = {
                csrf: cfg.csrf || '',
                urls: cfg.urls || {},
            };
        },

        async post(url, note) {
            const body = new URLSearchParams();
            body.append('_token', this.config.csrf);

            if (note !== undefined) {
                body.append('note', note || '');
            }

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
                throw new Error((result && (result.message || result.msg)) || 'Terjadi kesalahan komunikasi dengan server.');
            }

            return result;
        },

        async approve(id) {
            if (this.busy === id) return;

            const konfirmasi = await Swal.fire({
                title: 'Setujui Koreksi?',
                text: 'Data absensi akan diperbarui + status, poin, dan denda dihitung ulang.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Setujui',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.busy = id;

            try {
                const result = await this.post(this.config.urls.approve + '/' + id);

                if (result && result.status) {
                    await Swal.fire({ title: 'Berhasil!', text: result.message || '', icon: 'success', timer: 1400, showConfirmButton: false });
                    window.location.reload();
                    return;
                }

                Swal.fire('Gagal', (result && result.message) || '', 'error');
            } catch (error) {
                Swal.fire('Gagal', error.message || 'Terjadi kesalahan komunikasi dengan server.', 'error');
            } finally {
                this.busy = null;
            }
        },

        async reject(id) {
            if (this.busy === id) return;

            const konfirmasi = await Swal.fire({
                title: 'Tolak Koreksi?',
                input: 'textarea',
                inputLabel: 'Catatan Penolakan (Opsional)',
                inputPlaceholder: 'Tulis alasan penolakan di sini...',
                inputAttributes: { 'aria-label': 'Catatan Penolakan', maxlength: '500' },
                showCancelButton: true,
                confirmButtonText: 'Tolak Permintaan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.busy = id;

            try {
                const result = await this.post(this.config.urls.reject + '/' + id, (konfirmasi.value || '').trim());

                if (result && result.status) {
                    await Swal.fire({ title: 'Ditolak', text: result.message || '', icon: 'success', timer: 1400, showConfirmButton: false });
                    window.location.reload();
                    return;
                }

                Swal.fire('Gagal', (result && result.message) || '', 'error');
            } catch (error) {
                Swal.fire('Gagal', error.message || 'Terjadi kesalahan komunikasi dengan server.', 'error');
            } finally {
                this.busy = null;
            }
        },
    }));
});
</script>
@endsection
