@extends('layouts.main')

@section('styles')
<style>
    .num-tabular { font-variant-numeric: tabular-nums; }
    /* ── Icon box centering (pola lembur/izin) ── */
    .ic-box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .ic-box i,
    .ic-box .bi {
        vertical-align: 0;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    /* ── Filter chips (pola izin) ── */
    .filter-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .3rem .75rem; border-radius: 9999px; font-size: .75rem; font-weight: 700;
        border: 1.5px solid #e2e8f0; background: #fff; color: #475569; cursor: pointer;
        transition: all .15s ease; white-space: nowrap;
    }
    .filter-chip:hover { border-color: var(--primary-600); color: var(--primary-600); }
    .filter-chip.active { background: var(--primary-600); border-color: var(--primary-600); color: #fff; }
    .filter-chip.active .chip-count { background: rgba(255,255,255,0.25); color: #fff; }
    .chip-count {
        background: #f1f5f9; color: #475569;
        font-size: 0.68rem; font-weight: 800;
        padding: 0.05rem 0.4rem; border-radius: 9999px;
        min-width: 18px; text-align: center;
    }
    .filter-chip .dot { width: 8px; height: 8px; border-radius: 9999px; display: inline-block; }
    /* ── Row hover (pola izin) ── */
    #koreksiTable tbody tr:hover td, #historyTable tbody tr:hover td { background: #f8fafc; }
    .reason-text {
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden; max-width: 240px;
    }
    @media (max-width: 640px) {
        .content-area { padding: 0.5rem 0.75rem 1.25rem; }
        .card-tw-body { padding: 0.875rem; }
    }
</style>
@endsection

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
    if (!preg_match('/^(\d{1,2}):(\d{2})/', (string) $hm, $m)) return null;
    return ((int) $m[1]) * 60 + ((int) $m[2]);
};
$fieldInfo = [
    'jam_masuk'  => ['label' => 'Jam Masuk',  'icon' => 'bi-box-arrow-in-right', 'cls' => 'badge-info'],
    'jam_pulang' => ['label' => 'Jam Pulang', 'icon' => 'bi-box-arrow-right',    'cls' => 'badge-warning'],
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
$oldestText = !empty($summaryKoreksi['oldest']) ? date('d/m/Y H:i', strtotime($summaryKoreksi['oldest'])) : '—';
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
?>

<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- Header (pola kartu/dapodik/izin) -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-amber-50 text-amber-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-pencil-square"></i> Verifikasi Koreksi
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Koreksi Absensi</h1>
            <p class="text-xs text-slate-500 mt-1">Tinjau dan setujui permintaan koreksi kehadiran dari guru.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ url('admin/presensi/dinas') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-briefcase text-cyan-600"></i> Verifikasi Dinas
            </a>
            <a href="{{ url('admin/presensi/izin') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-file-earmark-medical text-cyan-600"></i> Verifikasi Izin/Sakit
            </a>
            <a href="{{ url('admin/presensi') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <?php if (!empty($pending)): ?>
    <div class="alert-tw alert-warning">
        <i class="bi bi-info-circle-fill mt-0.5"></i>
        <div>Menyetujui koreksi akan <strong>menghitung ulang status, poin, dan denda</strong> hari tersebut (termasuk cap denda bulanan). Pengajuan basi lain untuk absensi + field yang sama otomatis ditolak.</div>
    </div>
    <?php endif; ?>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex items-center gap-3">
            <span class="ic-box w-10 h-10 rounded-xl bg-amber-50 text-amber-500 shrink-0"><i class="bi bi-hourglass-split text-lg"></i></span>
            <div>
                <div class="text-xs text-slate-500 font-semibold">Menunggu</div>
                <div class="text-lg font-extrabold text-slate-900 num-tabular">{{ (int) $summaryKoreksi['pending'] }} <span class="text-xs font-normal text-slate-400">pengajuan</span></div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex items-center gap-3">
            <span class="ic-box w-10 h-10 rounded-xl bg-blue-50 text-blue-600 shrink-0"><i class="bi bi-calendar-event text-lg"></i></span>
            <div>
                <div class="text-xs text-slate-500 font-semibold">Pengajuan Tertua</div>
                <div class="text-sm font-extrabold text-slate-900 num-tabular leading-tight">{{ $oldestText }}</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex items-center gap-3">
            <span class="ic-box w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 shrink-0"><i class="bi bi-check-circle text-lg"></i></span>
            <div>
                <div class="text-xs text-slate-500 font-semibold">Sudah Diproses</div>
                <div class="text-lg font-extrabold text-slate-900 num-tabular">{{ (int) $summaryKoreksi['history'] }} <span class="text-xs font-normal text-slate-400">keputusan</span></div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 p-5 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="ic-box w-8 h-8 rounded-xl bg-blue-50 text-blue-600 shrink-0">
                    <i class="bi bi-list-check text-sm"></i>
                </span>
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900">Daftar Permintaan Koreksi</h2>
                    <p class="text-[11px] text-slate-400">Tinjau selisih waktu sebelum menyetujui</p>
                </div>
            </div>
            <?php if (!empty($pending)): ?>
            <div class="relative ml-0 md:ml-auto" style="min-width:220px;">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="searchKoreksiInput"
                       class="w-full pl-8 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Cari nama / NIP / tanggal..." autocomplete="off">
                <button type="button" id="clearKoreksiSearch"
                        class="hidden absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 items-center justify-center rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition-colors"
                        title="Bersihkan pencarian">
                    <i class="bi bi-x text-xs"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($pending)): ?>
        <div class="px-5 py-3 bg-slate-50/60 border-b border-slate-100 flex flex-wrap gap-1.5 items-center" id="koreksiChips">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mr-1">FIELD</span>
            <button type="button" class="filter-chip active" data-field="">
                Semua <span class="chip-count">{{ count($pending) }}</span>
            </button>
            <button type="button" class="filter-chip" data-field="jam_masuk">
                <i class="bi bi-box-arrow-in-right"></i>Masuk <span class="chip-count">{{ $jumlahField['jam_masuk'] ?? 0 }}</span>
            </button>
            <button type="button" class="filter-chip" data-field="jam_pulang">
                <i class="bi bi-box-arrow-right"></i>Pulang <span class="chip-count">{{ $jumlahField['jam_pulang'] ?? 0 }}</span>
            </button>
        </div>
        <?php endif; ?>

        <div class="p-0">
            <div id="emptyState" class="{{ empty($pending) ? '' : 'hidden' }} text-center py-16 text-slate-400">
                <i class="bi bi-check-circle text-5xl block mx-auto mb-3 opacity-20"></i>
                <p class="font-medium text-slate-600">Tidak ada permintaan koreksi</p>
                <p class="text-xs mt-1">Semua permintaan koreksi telah diproses.</p>
            </div>

            <?php if (!empty($pending)): ?>
            <div class="table-responsive-tw">
                <table class="table-tw" id="koreksiTable" style="min-width:1060px;">
                    <thead>
                        <tr>
                            <th class="pl-4 w-10">#</th>
                            <th>Tanggal Absen</th>
                            <th>Guru</th>
                            <th>Field</th>
                            <th>Nilai Lama</th>
                            <th>Nilai Baru</th>
                            <th class="text-center">Selisih</th>
                            <th class="min-w-[220px]">Alasan</th>
                            <th>Diajukan</th>
                            <th class="text-right pr-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="koreksiBody">
                        <?php
                        $no = 1;
                        foreach ($pending as $p):
                            $fInfo  = $fieldInfo[$p->field_name] ?? ['label' => ucwords(str_replace('_', ' ', (string) $p->field_name)), 'icon' => 'bi-pencil-square', 'cls' => 'badge-info'];
                            $oldHm  = $fmtWaktu($p->old_value ?? null, $p->tgl ?? null);
                            $newHm  = $fmtWaktu($p->new_value ?? null, $p->tgl ?? null);
                            $jamIn  = $fmtWaktu($p->jam_masuk ?? null, $p->tgl ?? null);
                            $jamOut = $fmtWaktu($p->jam_pulang ?? null, $p->tgl ?? null);
                            $stIn   = strtoupper((string) ($p->status_masuk ?? ''));
                            [$stInLbl, $stInCls] = $statusMap[$stIn] ?? [($stIn !== '' ? ucwords(strtolower($stIn)) : '—'), 'badge-secondary'];
                            $attMissing = empty($p->tgl);
                            $dMin = null;
                            $om = $toMin($oldHm ?? '');
                            $nm = $toMin($newHm ?? '');
                            if ($om !== null && $nm !== null) $dMin = $nm - $om;
                            $searchTxt = implode(' ', array_filter([
                                $p->nama_guru ?? '',
                                $p->nip ?? '',
                                !empty($p->tgl) ? date('d/m/Y', strtotime($p->tgl)) : '',
                                $fInfo['label'],
                                $oldHm ?? 'Kosong',
                                $newHm ?? '',
                                $p->reason ?? '',
                            ]));
                        ?>
                        <tr id="row-{{ (int) $p->id }}" data-id="{{ (int) $p->id }}" data-field="{{ $p->field_name ?? '' }}" data-search="{{ $searchTxt }}">
                            <td class="pl-4 text-slate-400 num-tabular">{{ $no++ }}</td>
                            <td>
                                <?php if ($attMissing): ?>
                                    <span class="badge-tw badge-danger"><i class="bi bi-exclamation-triangle"></i>Data absen dihapus</span>
                                <?php else: ?>
                                    <div class="font-semibold text-slate-800 whitespace-nowrap">{{ date('d/m/Y', strtotime($p->tgl)) }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5">
                                        Masuk {{ $jamIn ?: '-' }}<span class="badge-tw {{ $stInCls }} ml-1">{{ $stInLbl }}</span>
                                        <span class="mx-1">·</span>Pulang {{ $jamOut ?: '-' }}
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-800">{{ $p->nama_guru ?? 'Guru #' . ($p->user_id ?? '-') }}</div>
                                <div class="text-sm text-slate-400">NIP: {{ $p->nip ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="badge-tw {{ $fInfo['cls'] }}">
                                    <i class="bi {{ $fInfo['icon'] }} mr-1"></i>{{ $fInfo['label'] }}
                                </span>
                            </td>
                            <td>
                                <code class="px-2 py-1 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded" title="{{ $p->old_value ?? 'Kosong' }}">
                                    {{ $oldHm ?? 'Kosong' }}
                                </code>
                            </td>
                            <td>
                                <code class="px-2 py-1 text-sm font-medium text-emerald-600 bg-emerald-50 border border-emerald-200 rounded" title="{{ $p->new_value ?? '-' }}">
                                    {{ $newHm ?? '-' }}
                                </code>
                            </td>
                            <td class="text-center">
                                <?php if ($dMin === null): ?>
                                    <span class="text-slate-300">–</span>
                                <?php else: ?>
                                    <span class="badge-tw {{ $dMin === 0 ? 'badge-secondary' : ($dMin > 0 ? 'badge-warning' : 'badge-info') }} rounded-full num-tabular" title="Baru − lama">
                                        {{ $dMin === 0 ? 'sama' : (($dMin > 0 ? '+' : '−') . abs($dMin) . ' mnt') }}
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-slate-500 text-sm italic reason-text" title="{{ $p->reason ?? '-' }}">"{{ $p->reason ?? '-' }}"</span>
                            </td>
                            <td class="text-slate-400 text-sm whitespace-nowrap">{{ !empty($p->created_at) ? date('d/m/Y H:i', strtotime($p->created_at)) : '-' }}</td>
                            <td class="pr-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <?php if ($attMissing): ?>
                                        <span class="text-xs text-slate-400" title="Data absensi sudah dihapus — hanya bisa ditolak">Hanya tolak</span>
                                    <?php endif; ?>
                                    <button type="button" class="btn-tw btn-tw-sm btn-success js-approve" data-id="{{ (int) $p->id }}"
                                        data-url="{{ url('admin/presensi/approve-koreksi/' . $p->id) }}" title="Setujui" {{ $attMissing ? 'disabled' : '' }}>
                                        <i class="bi bi-check-lg mr-1"></i>Setujui
                                    </button>
                                    <button type="button" class="btn-tw btn-tw-sm btn-danger js-reject" data-id="{{ (int) $p->id }}"
                                        data-url="{{ url('admin/presensi/reject-koreksi/' . $p->id) }}" title="Tolak">
                                        <i class="bi bi-x-lg mr-1"></i>Tolak
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-2.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                <span id="koreksiCount">Menampilkan {{ count($pending) }} data</span>
                <span>Setujui untuk menghitung ulang status &amp; poin</span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Riwayat -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 p-5 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="ic-box w-8 h-8 rounded-xl bg-slate-100 text-slate-500 shrink-0">
                    <i class="bi bi-clock-history text-sm"></i>
                </span>
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900">Riwayat Keputusan</h2>
                    <p class="text-[11px] text-slate-400">100 koreksi terakhir yang disetujui / ditolak</p>
                </div>
            </div>
            <?php if (!empty($history)): ?>
            <div class="relative ml-0 md:ml-auto" style="min-width:220px;">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="searchRiwayatInput"
                       class="w-full pl-8 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Cari nama / NIP / tanggal..." autocomplete="off">
                <button type="button" id="clearRiwayatSearch"
                        class="hidden absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 items-center justify-center rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition-colors"
                        title="Bersihkan pencarian">
                    <i class="bi bi-x text-xs"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($history)): ?>
        <div class="px-5 py-3 bg-slate-50/60 border-b border-slate-100 flex flex-wrap gap-1.5 items-center" id="riwayatChips">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mr-1">STATUS</span>
            <button type="button" class="filter-chip active" data-hstatus="">
                Semua <span class="chip-count">{{ count($history) }}</span>
            </button>
            <button type="button" class="filter-chip" data-hstatus="approved">
                <span class="dot" style="background:#16a34a"></span>Disetujui <span class="chip-count">{{ $jumlahStatus['approved'] }}</span>
            </button>
            <button type="button" class="filter-chip" data-hstatus="rejected">
                <span class="dot" style="background:#dc2626"></span>Ditolak <span class="chip-count">{{ $jumlahStatus['rejected'] }}</span>
            </button>
        </div>
        <?php endif; ?>

        <div class="p-0">
            <?php if (empty($history)): ?>
                <div class="text-center py-16 text-slate-400">
                    <i class="bi bi-inbox text-5xl block mx-auto mb-3 opacity-20"></i>
                    <p class="font-medium text-slate-600">Belum ada riwayat</p>
                </div>
            <?php else: ?>
                <div class="table-responsive-tw">
                    <table class="table-tw" id="historyTable" style="font-size:.8rem;min-width:900px;">
                        <thead>
                            <tr>
                                <th class="pl-4">Tanggal Absen</th>
                                <th>Guru</th>
                                <th>Field</th>
                                <th>Perubahan</th>
                                <th class="text-center">Status</th>
                                <th>Catatan Admin</th>
                                <th>Diproses</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h):
                                $hb = ($h->status === 'approved') ? 'badge-success' : 'badge-danger';
                                $hl = ($h->status === 'approved') ? 'Disetujui' : 'Ditolak';
                                $fLbl = $fieldInfo[$h->field_name]['label'] ?? $h->field_name;
                                $searchH = implode(' ', array_filter([
                                    $h->nama_guru ?? '',
                                    !empty($h->tgl) ? date('d/m/Y', strtotime($h->tgl)) : '',
                                    $fLbl,
                                    $fmtWaktu($h->old_value ?? null, $h->tgl ?? null) ?? '',
                                    $fmtWaktu($h->new_value ?? null, $h->tgl ?? null) ?? '',
                                    $h->admin_note ?? '',
                                    $hl,
                                ]));
                            ?>
                            <tr data-hstatus="{{ $h->status }}" data-search="{{ $searchH }}">
                                <td class="pl-4 whitespace-nowrap">{{ !empty($h->tgl) ? date('d/m/Y', strtotime($h->tgl)) : '<span class="text-slate-300">absen dihapus</span>' }}</td>
                                <td class="font-bold text-slate-800">{{ $h->nama_guru ?? '-' }}</td>
                                <td>{{ $fLbl }}</td>
                                <td class="num-tabular"><span class="text-red-500">{{ $fmtWaktu($h->old_value ?? null, $h->tgl ?? null) ?? '∅' }}</span> → <span class="text-emerald-600 font-bold">{{ $fmtWaktu($h->new_value ?? null, $h->tgl ?? null) ?? '–' }}</span></td>
                                <td class="text-center"><span class="badge-tw {{ $hb }} rounded-full">{{ $hl }}</span></td>
                                <td class="text-sm text-slate-500" style="max-width:240px" title="{{ $h->admin_note ?? '' }}">{{ $h->admin_note ? mb_strimwidth($h->admin_note, 0, 90, '…') : '–' }}</td>
                                <td class="text-xs text-slate-400 whitespace-nowrap">
                                    {{ !empty($h->processed_at) ? date('d/m/Y H:i', strtotime($h->processed_at)) : '-' }}
                                    <?php if (!empty($h->diproses_oleh) && trim((string) $h->diproses_oleh) !== ''): ?><div>oleh {{ trim((string) $h->diproses_oleh) }}</div><?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-2.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                    <span id="riwayatCount">Menampilkan {{ count($history) }} data</span>
                    <span>100 keputusan terakhir</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<?php $csrfName = '_token'; $csrfHash = csrf_token(); ?>
<script>
$(document).ready(function () {
    var CSRF = {};
    CSRF['{{ $csrfName }}'] = '{{ $csrfHash }}';

    var activeField = '';
    var activeStatus = '';

    function applyKoreksiFilter() {
        var kw = ($('#searchKoreksiInput').val() || '').toLowerCase().trim();
        $('#clearKoreksiSearch').toggleClass('hidden', kw === '').toggleClass('inline-flex', kw !== '');
        var shown = 0;
        $('#koreksiTable tbody tr[data-search]').each(function () {
            var search = String($(this).data('search') || '').toLowerCase();
            var field = $(this).data('field') || '';
            var visible = (!activeField || field === activeField) && (!kw || search.indexOf(kw) !== -1);
            $(this).toggle(visible);
            if (visible) shown++;
        });
        var $empty = $('#koreksiEmptyFilter');
        if (shown === 0 && $('#koreksiTable tbody tr[data-search]').length > 0) {
            if ($empty.length === 0) {
                $('#koreksiTable tbody').append(
                    '<tr id="koreksiEmptyFilter"><td colspan="10" class="text-center py-12 text-slate-400">' +
                    '<i class="bi bi-search text-3xl block mx-auto mb-2 opacity-30"></i>' +
                    '<p class="text-sm font-semibold text-slate-500">Tidak ada data yang cocok dengan filter</p>' +
                    '<p class="text-xs mt-1">Coba ubah kata kunci atau filter field</p></td></tr>'
                );
            } else {
                $empty.show();
            }
        } else {
            $empty.hide();
        }
        $('#koreksiCount').text('Menampilkan ' + shown + ' data');
    }

    function applyRiwayatFilter() {
        var kw = ($('#searchRiwayatInput').val() || '').toLowerCase().trim();
        $('#clearRiwayatSearch').toggleClass('hidden', kw === '').toggleClass('inline-flex', kw !== '');
        var shown = 0;
        $('#historyTable tbody tr[data-search]').each(function () {
            var search = String($(this).data('search') || '').toLowerCase();
            var status = $(this).data('hstatus') || '';
            var visible = (!activeStatus || status === activeStatus) && (!kw || search.indexOf(kw) !== -1);
            $(this).toggle(visible);
            if (visible) shown++;
        });
        var $empty = $('#riwayatEmptyFilter');
        if (shown === 0 && $('#historyTable tbody tr[data-search]').length > 0) {
            if ($empty.length === 0) {
                $('#historyTable tbody').append(
                    '<tr id="riwayatEmptyFilter"><td colspan="7" class="text-center py-12 text-slate-400">' +
                    '<i class="bi bi-search text-3xl block mx-auto mb-2 opacity-30"></i>' +
                    '<p class="text-sm font-semibold text-slate-500">Tidak ada data yang cocok dengan filter</p>' +
                    '<p class="text-xs mt-1">Coba ubah kata kunci atau filter status</p></td></tr>'
                );
            } else {
                $empty.show();
            }
        } else {
            $empty.hide();
        }
        $('#riwayatCount').text('Menampilkan ' + shown + ' data');
    }

    $('#koreksiChips').on('click', '.filter-chip', function () {
        activeField = $(this).data('field') || '';
        $('#koreksiChips .filter-chip').removeClass('active');
        $(this).addClass('active');
        applyKoreksiFilter();
    });

    $('#riwayatChips').on('click', '.filter-chip', function () {
        activeStatus = $(this).data('hstatus') || '';
        $('#riwayatChips .filter-chip').removeClass('active');
        $(this).addClass('active');
        applyRiwayatFilter();
    });

    $('#searchKoreksiInput').on('input', applyKoreksiFilter);
    $('#clearKoreksiSearch').on('click', function () {
        $('#searchKoreksiInput').val('').trigger('input').focus();
    });
    $('#searchRiwayatInput').on('input', applyRiwayatFilter);
    $('#clearRiwayatSearch').on('click', function () {
        $('#searchRiwayatInput').val('').trigger('input').focus();
    });
    applyKoreksiFilter();
    applyRiwayatFilter();

    var setLoading = function ($btn, busy) {
        $btn.prop('disabled', busy);
        var $icon = $btn.find('i');
        if (busy) {
            $btn.data('orig', $icon.attr('class'));
            $icon.attr('class', 'spinner-tw mr-1');
        } else if ($btn.data('orig')) {
            $icon.attr('class', $btn.data('orig'));
        }
    };

    // Approve
    $(document).on('click', '.js-approve', function () {
        var $btn = $(this);
        if ($btn.is(':disabled')) return;
        var url = $btn.data('url');
        Swal.fire({
            title: 'Setujui Koreksi?',
            text: 'Data absensi akan diperbarui + status, poin, dan denda dihitung ulang.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            setLoading($btn, true);
            $.post(url, CSRF, function (res) {
                setLoading($btn, false);
                if (res.status) {
                    Swal.fire({ title: 'Berhasil!', text: res.message, icon: 'success', timer: 1400, showConfirmButton: false })
                        .then(function () { location.reload(); });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            }, 'json').fail(function () {
                setLoading($btn, false);
                Swal.fire('Gagal', 'Terjadi kesalahan komunikasi dengan server.', 'error');
            });
        });
    });

    // Reject (dengan catatan)
    $(document).on('click', '.js-reject', function () {
        var $btn = $(this);
        if ($btn.is(':disabled')) return;
        var url = $btn.data('url');
        Swal.fire({
            title: 'Tolak Koreksi?',
            input: 'textarea',
            inputLabel: 'Catatan Penolakan (Opsional)',
            inputPlaceholder: 'Tulis alasan penolakan di sini...',
            inputAttributes: { 'aria-label': 'Catatan Penolakan', 'maxlength': '500' },
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Tolak Permintaan',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            setLoading($btn, true);
            var data = Object.assign({ note: (result.value || '').trim() }, CSRF);
            $.post(url, data, function (res) {
                setLoading($btn, false);
                if (res.status) {
                    Swal.fire({ title: 'Ditolak', text: res.message, icon: 'success', timer: 1400, showConfirmButton: false })
                        .then(function () { location.reload(); });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            }, 'json').fail(function () {
                setLoading($btn, false);
                Swal.fire('Gagal', 'Terjadi kesalahan komunikasi dengan server.', 'error');
            });
        });
    });
});
</script>
@endsection
