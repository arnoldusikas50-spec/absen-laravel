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
    /* ── Row hover (pola izin) ── */
    #verifikasiTable tbody tr:hover td { background: #f8fafc; }
</style>
@endsection

@section('content')

<?php
$pendingList = collect($pending ?? []);
$hariTinjau = \App\Services\AttendancePolicy::PENDING_REVIEW_HARI;
$pendingTertua = $pendingList->first();
$fmtJam = function ($v) {
    $s = trim((string) $v);
    if ($s === '' || $s === '0') return null;
    if (preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})(?::\d{2})?$/', $s, $m)) return $m[2];
    return strlen($s) > 5 ? substr($s, 0, 5) : $s;
};
?>

<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-violet-50 text-violet-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-person-check-fill"></i> Verifikasi Kehadiran
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Verifikasi Kehadiran</h1>
            <p class="text-xs text-slate-500 mt-1">Absensi guru dengan alpha ≥ 10 hari ditahan di sini. Terima untuk menghitung ulang status (ONTIME/LATE), tolak untuk menjadikannya ALPHA.</p>
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

    <?php if (count($pendingList) > 0): ?>
    <div class="alert-tw alert-warning">
        <i class="bi bi-info-circle-fill mt-0.5"></i>
        <div>Baris <strong>PENDING_VERIFICATION</strong> dihitung <strong>belum tentu</strong>: tidak masuk alpha maupun hadir di rekap &amp; surat teguran selama <strong>{{ (int) $hariTinjau }} hari</strong> sejak tanggal kehadirannya. Setelah itu cron harian menutupnya menjadi <strong>ALPHA</strong> (denda + poin + surat disamakan dengan jalur tolak). Menerima akan <strong>menghitung ulang status, poin, dan denda</strong> dari jam tersimpan (termasuk cap bulanan); menolak menjadikannya ALPHA sekarang juga.</div>
    </div>
    <?php endif; ?>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex items-center gap-3">
            <span class="ic-box w-10 h-10 rounded-xl bg-violet-50 text-violet-600 shrink-0"><i class="bi bi-hourglass-split text-lg"></i></span>
            <div>
                <div class="text-xs text-slate-500 font-semibold">Menunggu</div>
                <div class="text-lg font-extrabold text-slate-900 num-tabular">{{ count($pendingList) }} <span class="text-xs font-normal text-slate-400">kehadiran</span></div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex items-center gap-3">
            <span class="ic-box w-10 h-10 rounded-xl bg-blue-50 text-blue-600 shrink-0"><i class="bi bi-calendar-event text-lg"></i></span>
            <div>
                <div class="text-xs text-slate-500 font-semibold">Kehadiran Tertua</div>
                <div class="text-sm font-extrabold text-slate-900 num-tabular leading-tight">{{ $pendingTertua && !empty($pendingTertua->tgl) ? date('d/m/Y', strtotime($pendingTertua->tgl)) : '—' }}</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 flex items-center gap-3">
            <span class="ic-box w-10 h-10 rounded-xl bg-amber-50 text-amber-500 shrink-0"><i class="bi bi-alarm text-lg"></i></span>
            <div>
                <div class="text-xs text-slate-500 font-semibold">Batas Tinjau</div>
                <div class="text-lg font-extrabold text-slate-900 num-tabular">{{ (int) $hariTinjau }} <span class="text-xs font-normal text-slate-400">hari</span></div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 p-5 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="ic-box w-8 h-8 rounded-xl bg-violet-50 text-violet-600 shrink-0">
                    <i class="bi bi-list-check text-sm"></i>
                </span>
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900">Antrean Verifikasi ({{ count($pendingList) }})</h2>
                    <p class="text-[11px] text-slate-400">Urut tanggal terdahulu</p>
                </div>
            </div>
            <?php if (count($pendingList) > 0): ?>
            <div class="relative ml-0 md:ml-auto" style="min-width:220px;">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="searchVerifikasiInput"
                       class="w-full pl-8 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Cari nama / NIP / tanggal..." autocomplete="off">
                <button type="button" id="clearVerifikasiSearch"
                        class="hidden absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 items-center justify-center rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition-colors"
                        title="Bersihkan pencarian">
                    <i class="bi bi-x text-xs"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <div class="p-0">
            <div id="emptyState" class="{{ count($pendingList) === 0 ? '' : 'hidden' }} text-center py-16 text-slate-400">
                <i class="bi bi-check-circle text-5xl block mx-auto mb-3 opacity-20"></i>
                <p class="font-medium text-slate-600">Tidak ada antrean verifikasi</p>
                <p class="text-xs mt-1">Semua kehadiran yang ditahan telah diproses.</p>
            </div>

            <?php if (count($pendingList) > 0): ?>
            <div class="table-responsive-tw">
                <table class="table-tw" id="verifikasiTable" style="min-width:900px;">
                    <thead>
                        <tr>
                            <th class="pl-4 w-10">#</th>
                            <th>Tanggal</th>
                            <th>Guru</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th class="text-right pr-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($pendingList as $p):
                            $searchVerifikasi = implode(' ', array_filter([
                                $p->nama_guru ?? '',
                                $p->nip ?? '',
                                !empty($p->tgl) ? date('d/m/Y', strtotime($p->tgl)) : '',
                                $fmtJam($p->jam_masuk ?? null) ?? '',
                                $fmtJam($p->jam_pulang ?? null) ?? '',
                            ]));
                        ?>
                        <tr id="row-{{ (int) $p->id }}" data-search="{{ $searchVerifikasi }}">
                            <td class="pl-4 text-slate-400 num-tabular">{{ $no++ }}</td>
                            <td>
                                <div class="font-semibold text-slate-800 whitespace-nowrap">{{ date('d/m/Y', strtotime($p->tgl)) }}</div>
                                <div class="text-xs text-slate-400 mt-0.5">{{ date('l', strtotime($p->tgl)) }}</div>
                            </td>
                            <td>
                                <div class="font-semibold text-slate-800">{{ $p->nama_guru ?? 'Guru #' . ($p->user_id ?? '-') }}</div>
                                <div class="text-sm text-slate-400">NIP: {{ $p->nip ?? '-' }}</div>
                            </td>
                            <td class="num-tabular"><code class="px-2 py-1 text-sm font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded">{{ $fmtJam($p->jam_masuk ?? null) ?? '-' }}</code></td>
                            <td class="num-tabular"><code class="px-2 py-1 text-sm font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded">{{ $fmtJam($p->jam_pulang ?? null) ?? '-' }}</code></td>
                            <td class="pr-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" class="btn-tw btn-tw-sm btn-success js-terima" data-id="{{ (int) $p->id }}"
                                        data-url="{{ url('admin/presensi/verifikasi-terima/' . $p->id) }}" title="Verifikasi & hitung ulang">
                                        <i class="bi bi-check-lg mr-1"></i>Terima
                                    </button>
                                    <button type="button" class="btn-tw btn-tw-sm btn-danger js-tolak" data-id="{{ (int) $p->id }}"
                                        data-url="{{ url('admin/presensi/verifikasi-tolak/' . $p->id) }}" title="Tolak menjadi ALPHA">
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
                <span id="verifikasiCount">Menampilkan {{ count($pendingList) }} data</span>
                <span>Urut tanggal terdahulu</span>
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

    // ── Pencarian antrean (pola izin) ───────────────────────
    function applyVerifikasiFilter() {
        const kw = ($('#searchVerifikasiInput').val() || '').toLowerCase().trim();
        $('#clearVerifikasiSearch').toggleClass('hidden', kw === '').toggleClass('inline-flex', kw !== '');
        let shown = 0;
        $('#verifikasiTable tbody tr[data-search]').each(function () {
            const search = String($(this).data('search') || '').toLowerCase();
            const visible = !kw || search.indexOf(kw) !== -1;
            $(this).toggle(visible);
            if (visible) shown++;
        });
        const $empty = $('#verifikasiEmptyFilter');
        if (shown === 0 && $('#verifikasiTable tbody tr[data-search]').length > 0) {
            if ($empty.length === 0) {
                $('#verifikasiTable tbody').append(
                    '<tr id="verifikasiEmptyFilter"><td colspan="6" class="text-center py-12 text-slate-400">' +
                    '<i class="bi bi-search text-3xl block mx-auto mb-2 opacity-30"></i>' +
                    '<p class="text-sm font-semibold text-slate-500">Tidak ada data yang cocok</p>' +
                    '<p class="text-xs mt-1">Coba ubah kata kunci pencarian</p></td></tr>'
                );
            } else {
                $empty.show();
            }
        } else {
            $empty.hide();
        }
        $('#verifikasiCount').text('Menampilkan ' + shown + ' data');
    }

    $('#searchVerifikasiInput').on('input', applyVerifikasiFilter);
    $('#clearVerifikasiSearch').on('click', function () {
        $('#searchVerifikasiInput').val('').trigger('input').focus();
    });

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

    // Terima (dengan catatan opsional)
    $(document).on('click', '.js-terima', function () {
        var $btn = $(this);
        if ($btn.is(':disabled')) return;
        var url = $btn.data('url');
        Swal.fire({
            title: 'Terima Verifikasi?',
            text: 'Status akan dihitung ulang dari jam tersimpan (ONTIME/LATE + denda + cap).',
            icon: 'question',
            input: 'textarea',
            inputLabel: 'Catatan (Opsional)',
            inputPlaceholder: 'Catatan verifikasi...',
            inputAttributes: { 'aria-label': 'Catatan', 'maxlength': '500' },
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Terima',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            setLoading($btn, true);
            var data = Object.assign({ note: (result.value || '').trim() }, CSRF);
            $.post(url, data, function (res) {
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

    // Tolak (dengan alasan)
    $(document).on('click', '.js-tolak', function () {
        var $btn = $(this);
        if ($btn.is(':disabled')) return;
        var url = $btn.data('url');
        Swal.fire({
            title: 'Tolak Menjadi ALPHA?',
            input: 'textarea',
            inputLabel: 'Alasan Penolakan',
            inputPlaceholder: 'Tulis alasan penolakan di sini...',
            inputAttributes: { 'aria-label': 'Alasan Penolakan', 'maxlength': '500' },
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Tolak Menjadi ALPHA',
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
