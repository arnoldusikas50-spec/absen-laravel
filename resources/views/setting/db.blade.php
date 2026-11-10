@extends('layouts.main')

@section('content')
@php
    $list = $list ?? [];
    $stats = $stats ?? [];

    $badges = [
        'database' => ['bg-indigo-50 text-indigo-600', 'bi-database', 'Database'],
        'snapshot' => ['bg-amber-50 text-amber-600', 'bi-clock-history', 'Snapshot'],
        'file'     => ['bg-emerald-50 text-emerald-600', 'bi-file-earmark-zip', 'File'],
        'arsip'    => ['bg-slate-100 text-slate-500', 'bi-hdd-stack', 'Arsip Cron'],
    ];

    // Ikon yang dipakai halaman ini (cek lucide-icons.css sebelum menambah).
    $icons = [
        'database' => 'bi bi-database',
        'snapshot' => 'bi bi-clock-history',
        'file'     => 'bi bi-file-earmark-zip',
        'arsip'    => 'bi bi-hdd-stack',
        'total'    => 'bi bi-collection',
        'size'     => 'bi bi-clipboard-data',
    ];
@endphp
<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shadow-sm shrink-0">
                    <i class="bi bi-database text-xl"></i>
                </span>
                <span>{{ $judul ?? 'Backup dan Restore' }}</span>
            </h3>
            <p class="text-sm text-slate-500 mt-1 mb-0">Kelola cadangan database &amp; file uploads aplikasi.</p>
        </div>
        <nav class="breadcrumb-tw">
            <a href="{{ url('dashboard') }}" class="no-underline">
                <i class="bi bi-house-door mr-1"></i>Dashboard
            </a>
            <span class="sep">/</span>
            <a href="{{ url('settings') }}" class="no-underline">Pengaturan</a>
            <span class="sep">/</span>
            <span class="active">{{ $subjudul ?? 'Backup' }}</span>
        </nav>
    </div>
</div>

<div class="content-area space-y-4">

    <!-- Ringkasan -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 flex items-center gap-3">
            <span class="w-9 h-9 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                <i class="{{ $icons['total'] }}"></i>
            </span>
            <div class="min-w-0">
                <div class="text-lg font-bold text-slate-800 leading-none">{{ $stats['total'] ?? count($list) }}</div>
                <div class="text-xs text-slate-400 mt-1">Total Cadangan</div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 flex items-center gap-3">
            <span class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <i class="{{ $icons['database'] }}"></i>
            </span>
            <div class="min-w-0">
                <div class="text-lg font-bold text-slate-800 leading-none">{{ $stats['db'] ?? 0 }}</div>
                <div class="text-xs text-slate-400 mt-1">Backup Database <span class="text-slate-300">· {{ $stats['arsip'] ?? 0 }} arsip cron</span></div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 flex items-center gap-3">
            <span class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i class="{{ $icons['file'] }}"></i>
            </span>
            <div class="min-w-0">
                <div class="text-lg font-bold text-slate-800 leading-none">{{ $stats['file'] ?? 0 }}</div>
                <div class="text-xs text-slate-400 mt-1">Backup File Uploads</div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 flex items-center gap-3">
            <span class="w-9 h-9 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center shrink-0">
                <i class="{{ $icons['size'] }}"></i>
            </span>
            <div class="min-w-0">
                <div class="text-lg font-bold text-slate-800 leading-none">{{ $stats['total_size'] ?? '0 KB' }}</div>
                <div class="text-xs text-slate-400 mt-1 truncate">
                    @if (! empty($stats['last']))
                        Terakhir: {{ date('d M Y H:i', (int) $stats['last']) }}
                    @else
                        Belum ada backup
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Aksi backup -->
    <div class="card-tw">
        <div class="card-tw-body">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="btn-tw btn-primary js-backup-btn" onclick="processBackup(this)">
                    <i class="bi bi-cloud-arrow-up"></i><span>Backup Semua</span>
                </button>
                <button type="button" class="btn-tw btn-light js-backup-btn" onclick="processBackupDb(this)">
                    <i class="bi bi-database"></i><span>Backup Database</span>
                </button>
                <button type="button" class="btn-tw btn-light js-backup-btn" onclick="processBackupFile(this)">
                    <i class="bi bi-file-earmark-zip"></i><span>Backup File</span>
                </button>
                <span class="text-xs text-slate-400 ml-auto hidden md:flex items-center gap-1.5">
                    <i class="bi bi-calendar3"></i>{{ date('d M Y H:i') }}
                </span>
            </div>
            <div class="mt-3">
                <div class="flex items-center justify-between mb-1.5">
                    <span id="progressMsg" class="text-xs text-slate-500">Siap. Pilih jenis backup.</span>
                    <span id="progressPct" class="text-xs font-semibold text-slate-400">0%</span>
                </div>
                <div class="progress-tw" style="height:6px">
                    <div id="backupProgressBar" class="progress-bar-tw" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Banner keamanan -->
    <div class="rounded-xl bg-blue-50 border border-blue-100 px-4 py-3 flex gap-3">
        <i class="bi bi-shield-check text-blue-500 text-base mt-0.5"></i>
        <div class="text-xs text-blue-700 leading-tight space-y-1">
            <p><b>Cadangan disimpan di luar webroot</b> (storage/app/backups) dan hanya bisa diunduh lewat rute terautentikasi admin.</p>
            <p>Restore selalu membuat <b>snapshot otomatis</b> keadaan sebelum data ditimpa, membutuhkan konfirmasi ketik <b>RESTORE</b>, dan arsip otomatis cron tidak ikut terhapus.</p>
        </div>
    </div>

    <!-- Riwayat -->
    <div class="card-tw">
        <div class="card-tw-header justify-between gap-3">
            <h6><i class="bi bi-clock-history text-slate-400"></i>Riwayat <span class="font-normal text-slate-400">· {{ count($list) }}</span></h6>
            <input type="search" id="searchBackup" class="form-control-tw !w-44 !py-1.5 !text-xs" placeholder="Cari file…" autocomplete="off">
        </div>
        <div class="card-tw-body !p-0">
            @if (! empty($list))
            <div class="table-responsive-tw">
                <table id="database" class="table-tw">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th class="text-right" style="width:90px">Ukuran</th>
                            <th class="text-right" style="width:130px">Waktu</th>
                            <th class="text-right" style="width:220px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="backupTableBody">
                        @foreach ($list as $value)
                            @php
                                $fname = $value['src'] ?? ($value['nama'].'.'.$value['type']);
                                $b = $badges[$value['kind']] ?? $badges['arsip'];
                                $bisaRestore = ! empty($value['is_sql']) && ($value['kind'] === 'database' || $value['kind'] === 'snapshot');
                            @endphp
                            <tr data-name="{{ strtolower($fname) }}">
                                <td>
                                    <div class="flex items-start gap-2 min-w-0">
                                        <span class="{{ $b[0] }} rounded-md px-2 py-0.5 text-[11px] font-semibold inline-flex items-center gap-1 shrink-0 mt-0.5">
                                            <i class="{{ $b[1] }}"></i>{{ $b[2] }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="font-medium text-slate-700 text-[13px] truncate" title="{{ $fname }}">{{ $fname }}</div>
                                            <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1.5">
                                                <span>{{ date('d M Y H:i', (int) $value['tgl']) }}</span>
                                                @if ($value['lengkap'] === true)
                                                    <span class="text-emerald-500 inline-flex items-center gap-0.5"><i class="bi bi-check-circle-fill"></i>Lengkap</span>
                                                @elseif ($value['lengkap'] === false)
                                                    <span class="text-red-500 inline-flex items-center gap-0.5"><i class="bi bi-x-circle-fill"></i>Tidak Lengkap</span>
                                                @elseif ($value['kind'] === 'database' || $value['kind'] === 'snapshot')
                                                    <span class="text-amber-500 inline-flex items-center gap-0.5"><i class="bi bi-exclamation-triangle-fill"></i>Dump Lama</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right text-slate-500 whitespace-nowrap">{{ $value['size'] ?? '-' }}</td>
                                <td class="text-right text-slate-400 text-xs whitespace-nowrap">{{ date('d M Y', (int) $value['tgl']) }}</td>
                                <td class="text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ url('settings/dbManager/download') }}?src={{ rawurlencode($value['src']) }}"
                                            class="btn-tw btn-tw-sm btn-light" title="Download">
                                            <i class="bi bi-download"></i>Download
                                        </a>
                                        @if ($bisaRestore)
                                        <button type="button" data-src="{{ $fname }}" onclick="restore(this.dataset.src)"
                                            class="btn-tw btn-tw-sm btn-light" title="Restore">
                                            <i class="bi bi-arrow-counterclockwise"></i>Restore
                                        </button>
                                        @endif
                                        <button type="button" data-src="{{ $fname }}" onclick="hapus(this.dataset.src)"
                                            class="btn-tw btn-tw-sm btn-light !text-red-600" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr id="backupEmptySearch" class="hidden">
                            <td colspan="4" class="text-center !py-6 text-xs text-slate-400">Tidak ada file yang cocok.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between px-4 py-2.5 border-t border-slate-100">
                <span class="text-xs text-slate-400">{{ count($list) }} file tersimpan di storage/app/backups — arsip cron (db_*.sql.gz) hanya bisa dihapus satu per satu</span>
                <button type="button" class="btn-tw btn-tw-sm btn-light !text-red-600" onclick="hapusBackup()">
                    <i class="bi bi-trash3"></i>Hapus Semua
                </button>
            </div>
            @else
            <div class="text-center px-6 py-10">
                <p class="text-sm text-slate-500 mb-3">Belum ada backup. Klik <b>Backup Semua</b> untuk mulai.</p>
                <button type="button" class="btn-tw btn-primary js-backup-btn" onclick="processBackup(this)">
                    <i class="bi bi-cloud-arrow-up"></i><span>Backup Sekarang</span>
                </button>
            </div>
            @endif
        </div>
    </div>

</div>

<script>
(function () {
    const BASE = (typeof base_url !== 'undefined' ? base_url : '{{ rtrim(url('/').'/', '/') }}/');
    const URL = {
        db: BASE + 'settings/dbManager/backupdb',
        files: BASE + 'settings/dbManager/backupdata',
        delAll: BASE + 'settings/dbManager/del_backup',
        delOne: BASE + 'settings/dbManager/hapusbackup',
        restore: BASE + 'settings/dbManager/restore'
    };
    let busy = false;

    function csrfData(extra) {
        const data = Object.assign({}, extra || {});
        if (typeof CSRF_NAME !== 'undefined' && typeof CSRF_HASH !== 'undefined') {
            data[CSRF_NAME] = CSRF_HASH;
        }
        return data;
    }

    function refreshCsrf(resp) {
        if (!resp) return;
        const newHash = resp.csrfHash || resp.csrf_hash;
        const newName = resp.csrfName || resp.csrf_name;
        if (typeof CSRF_HASH !== 'undefined' && newHash) CSRF_HASH = newHash;
        if (typeof CSRF_NAME !== 'undefined' && newName) CSRF_NAME = newName;
    }

    function errMsg(xhr, fallback) {
        try {
            const j = xhr.responseJSON;
            if (j && j.message) return j.message;
        } catch (e) {}
        return (xhr && xhr.statusText ? xhr.statusText : fallback) || fallback;
    }

    window.updateProgress = function (pct, message) {
        pct = Math.max(0, Math.min(100, Number(pct) || 0));
        const bar = document.getElementById('backupProgressBar');
        const label = document.getElementById('progressPct');
        const msg = document.getElementById('progressMsg');
        if (bar) {
            bar.style.width = pct + '%';
            bar.setAttribute('aria-valuenow', String(pct));
        }
        if (label) label.textContent = pct + '%';
        if (msg && message !== undefined) msg.textContent = message;
    };

    function setBusy(isBusy, activeBtn) {
        busy = isBusy;
        document.querySelectorAll('.js-backup-btn').forEach(function (btn) {
            if (isBusy) {
                if (!btn.dataset.orig) btn.dataset.orig = btn.innerHTML;
                btn.setAttribute('disabled', 'disabled');
                if (btn === activeBtn) {
                    btn.innerHTML = '<span class="spinner-tw"></span><span>Memproses…</span>';
                }
            } else {
                btn.removeAttribute('disabled');
                if (btn.dataset.orig) btn.innerHTML = btn.dataset.orig;
            }
        });
    }

    function ajaxPost(url, data) {
        return $.ajax({ type: 'POST', url: url, data: data, dataType: 'json' });
    }

    function tampilkanHasilBackup(respon, fallback) {
        // HTTP 200 belum berarti backup lengkap: server menandai dump
        // parsial/zip yang gagal ditutup dengan status false.
        const ok = !!(respon && respon.status);
        updateProgress(ok ? 100 : 0, (respon && respon.message) || fallback);
        Swal.fire({
            title: ok ? 'Berhasil' : 'Backup Tidak Lengkap',
            text: (respon && respon.message) || fallback,
            icon: ok ? 'success' : 'warning'
        }).then(() => window.location.reload());
    }

    window.processBackupDb = function (btn) {
        if (busy) return;
        setBusy(true, btn || null);
        updateProgress(10, 'Memulai backup database…');
        Swal.fire({
            title: 'Backup Database',
            text: 'Silakan tunggu, jangan tutup halaman ini…',
            allowEscapeKey: false, allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        ajaxPost(URL.db, csrfData({})).done(function (response) {
            refreshCsrf(response);
            tampilkanHasilBackup(response, 'Backup database selesai.');
        }).fail(function (xhr) {
            updateProgress(0, 'Backup database gagal.');
            Swal.fire('Gagal', 'Terjadi kesalahan: ' + errMsg(xhr, 'Backup gagal'), 'error');
        }).always(function () { setBusy(false); });
    };

    window.processBackupFile = function (btn) {
        if (busy) return;
        setBusy(true, btn || null);
        updateProgress(10, 'Memulai backup file uploads…');
        Swal.fire({
            title: 'Backup File Uploads',
            text: 'Silakan tunggu, jangan tutup halaman ini…',
            allowEscapeKey: false, allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        ajaxPost(URL.files, csrfData({})).done(function (response) {
            refreshCsrf(response);
            tampilkanHasilBackup(response, 'Backup file selesai.');
        }).fail(function (xhr) {
            updateProgress(0, 'Backup file gagal.');
            Swal.fire('Gagal', 'Terjadi kesalahan: ' + errMsg(xhr, 'Backup gagal'), 'error');
        }).always(function () { setBusy(false); });
    };

    window.processBackup = function (btn) {
        if (busy) return;
        setBusy(true, btn || null);
        updateProgress(5, 'Langkah 1/2: backup database…');
        Swal.fire({
            title: 'Backup Semua',
            text: 'Langkah 1/2: database… jangan tutup halaman ini.',
            allowEscapeKey: false, allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        ajaxPost(URL.db, csrfData({})).done(function (response) {
            refreshCsrf(response);
            if (!response || !response.status) {
                updateProgress(0, response && response.message ? response.message : 'Backup database tidak lengkap.');
                Swal.fire({ title: 'Backup Tidak Lengkap', text: (response && response.message) || 'Backup database gagal.', icon: 'warning' })
                    .then(() => window.location.reload());
                setBusy(false);
                return;
            }
            updateProgress(50, (response.message || 'Database OK.') + ' Lanjut backup file…');
            Swal.update({ text: 'Langkah 2/2: file uploads…' });
            ajaxPost(URL.files, csrfData({})).done(function (resp2) {
                refreshCsrf(resp2);
                if (!resp2 || !resp2.status) {
                    updateProgress(50, resp2 && resp2.message ? resp2.message : 'Backup file tidak lengkap.');
                    Swal.fire({ title: 'Backup Tidak Lengkap', text: (resp2 && resp2.message) || 'Database berhasil, tetapi backup file gagal.', icon: 'warning' })
                        .then(() => window.location.reload());
                } else {
                    updateProgress(100, resp2.message || 'Semua backup selesai.');
                    Swal.fire({ title: 'Berhasil', text: 'Database & file berhasil dibackup.', icon: 'success' })
                        .then(() => window.location.reload());
                }
            }).fail(function (xhr) {
                updateProgress(50, 'Database OK, backup file gagal.');
                Swal.fire('Sebagian Gagal', 'Database berhasil, tetapi file gagal: ' + errMsg(xhr, 'Backup file gagal'), 'warning')
                    .then(() => window.location.reload());
            }).always(function () { setBusy(false); });
        }).fail(function (xhr) {
            updateProgress(0, 'Backup gagal.');
            Swal.fire('Gagal', 'Terjadi kesalahan: ' + errMsg(xhr, 'Backup gagal'), 'error');
            setBusy(false);
        });
    };

    window.hapusBackup = function () {
        Swal.fire({
            title: 'Hapus Semua Backup?',
            html: "<span class='text-red-600'><b>Semua backup manual & snapshot (db_backup_ / files_backup_ / pre_restore_) dihapus permanen!</b></span><br><br>"
                + "Arsip otomatis dari cron (<code>db_*.sql.gz</code>) <b>tidak disentuh</b> — hapus satu per satu lewat tombol di tabel bila memang perlu.",
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Semua', cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) return;
            Swal.fire({ title: 'Menghapus…', text: 'Silakan tunggu.', allowEscapeKey: false, allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            ajaxPost(URL.delAll, csrfData({})).done(function (respon) {
                refreshCsrf(respon);
                if (respon.status) {
                    Swal.fire({ title: 'Berhasil', text: respon.message || 'Semua backup dihapus.', icon: 'success' })
                        .then(() => window.location.reload());
                } else {
                    Swal.fire({ title: 'Gagal', text: respon.message || 'Gagal menghapus.', icon: 'error' });
                }
            }).fail(function (xhr) {
                Swal.fire({ title: 'Error', text: 'Terjadi kesalahan: ' + errMsg(xhr, 'Gagal menghapus'), icon: 'error' });
            });
        });
    };

    window.hapus = function (src) {
        Swal.fire({
            title: 'Hapus file ini?',
            html: 'File <b>' + $('<div>').text(src).html() + '</b> akan dihapus permanen.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
            confirmButtonText: 'Hapus', cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.value) return;
            Swal.fire({ text: 'Menghapus…', allowEscapeKey: false, allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            ajaxPost(URL.delOne, csrfData({ src: src })).done(function (respon) {
                refreshCsrf(respon);
                if (respon.status) {
                    Swal.fire({ title: 'Berhasil', text: respon.message || 'File dihapus.', icon: 'success' })
                        .then(() => window.location.reload());
                } else {
                    Swal.fire({ title: 'Gagal', text: respon.message || 'Gagal menghapus.', icon: 'error' });
                }
            }).fail(function (xhr) {
                Swal.fire({ title: 'Error', text: 'Terjadi kesalahan: ' + errMsg(xhr, 'Gagal menghapus'), icon: 'error' });
            });
        });
    };

    window.restore = function (src) {
        Swal.fire({
            title: 'Restore Database',
            html: 'File: <b>' + $('<div>').text(src).html() + '</b><br><br>'
                + "<span class='text-red-600'><b>PERINGATAN:</b><br>"
                + '1. Seluruh data saat ini akan DITIMPA.<br>'
                + '2. Aplikasi membuat snapshot otomatis sebelum restore.<br>'
                + '3. Proses tidak bisa dibatalkan di tengah jalan.</span><br><br>'
                + 'Ketik <b>RESTORE</b> untuk melanjutkan:',
            icon: 'warning',
            input: 'text',
            inputPlaceholder: 'RESTORE',
            inputValidator: function (value) {
                if (!value || value.trim().toUpperCase() !== 'RESTORE') {
                    return 'Ketik RESTORE untuk melanjutkan';
                }
                return null;
            },
            showCancelButton: true,
            confirmButtonColor: '#d97706', cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Restore!', cancelButtonText: 'Batal',
            allowEscapeKey: false, allowOutsideClick: false
        }).then(function (result) {
            if (!result.value) return;
            Swal.fire({
                title: 'Membuat snapshot & merestore…',
                text: 'Jangan tutup halaman ini.',
                allowEscapeKey: false, allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            ajaxPost(URL.restore, csrfData({ src: src, konfirmasi: 'RESTORE' })).done(function (respon) {
                refreshCsrf(respon);
                if (respon.status) {
                    Swal.fire({ title: 'Berhasil', text: respon.message || 'Restore selesai.', icon: 'success' })
                        .then(() => window.location.reload());
                } else {
                    Swal.fire({ title: 'Restore Tidak Lengkap', text: respon.message || 'Restore gagal.', icon: 'warning' })
                        .then(() => window.location.reload());
                }
            }).fail(function (xhr) {
                Swal.fire({ title: 'Error', text: 'Terjadi kesalahan server: ' + errMsg(xhr, 'Restore gagal'), icon: 'error' });
            });
        });
    };

    // Pencarian client-side
    function applyFilter() {
        const q = (document.getElementById('searchBackup')?.value || '').toLowerCase().trim();
        let visible = 0;
        document.querySelectorAll('#backupTableBody tr[data-name]').forEach(function (row) {
            const show = !q || (row.dataset.name || '').includes(q);
            row.classList.toggle('hidden', !show);
            if (show) visible++;
        });
        const emptyRow = document.getElementById('backupEmptySearch');
        if (emptyRow) emptyRow.classList.toggle('hidden', visible !== 0);
    }
    document.getElementById('searchBackup')?.addEventListener('input', applyFilter);

    updateProgress(0, 'Siap. Pilih jenis backup.');
})();
</script>
@endsection
