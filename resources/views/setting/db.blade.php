@extends('layouts.main')

@section('content')
@php
    $list = $list ?? [];
    $stats = $stats ?? [];

    $badges = [
        'database' => ['bg-indigo-50 text-indigo-600 border-indigo-100', 'bi-database', 'Database'],
        'snapshot' => ['bg-amber-50 text-amber-600 border-amber-100', 'bi-clock-history', 'Snapshot'],
        'file'     => ['bg-emerald-50 text-emerald-600 border-emerald-100', 'bi-file-earmark-zip', 'File'],
        'arsip'    => ['bg-slate-100 text-slate-600 border-slate-200', 'bi-hdd-stack', 'Arsip Cron'],
    ];

    $rows = collect($list)->map(function ($value) use ($badges) {
        $fname = $value['src'] ?? (($value['nama'] ?? '') . '.' . ($value['type'] ?? ''));
        $badge = $badges[$value['kind'] ?? ''] ?? $badges['arsip'];

        $status = null;

        if (($value['lengkap'] ?? null) === true) {
            $status = ['text' => 'Lengkap', 'class' => 'text-emerald-600', 'icon' => 'bi-check-circle-fill'];
        } elseif (($value['lengkap'] ?? null) === false) {
            $status = ['text' => 'Tidak Lengkap', 'class' => 'text-rose-600', 'icon' => 'bi-x-circle-fill'];
        } elseif (($value['kind'] ?? '') === 'database' || ($value['kind'] ?? '') === 'snapshot') {
            $status = ['text' => 'Dump Lama', 'class' => 'text-amber-600', 'icon' => 'bi-exclamation-triangle-fill'];
        }

        return [
            'name' => $fname,
            'search' => strtolower($fname),
            'badge' => ['label' => $badge[2], 'class' => $badge[0], 'icon' => $badge[1]],
            'size' => $value['size'] ?? '-',
            'date' => date('d M Y H:i', (int) $value['tgl']),
            'dateShort' => date('d M Y', (int) $value['tgl']),
            'status' => $status,
            'canRestore' => ! empty($value['is_sql']) && in_array($value['kind'] ?? '', ['database', 'snapshot'], true),
            'download' => url('settings/dbManager/download') . '?src=' . rawurlencode((string) $value['src']),
        ];
    })->values()->all();

    $pageConfig = [
        'csrf' => csrf_token(),
        'urls' => [
            'db' => url('settings/dbManager/backupdb'),
            'files' => url('settings/dbManager/backupdata'),
            'delAll' => url('settings/dbManager/del_backup'),
            'delOne' => url('settings/dbManager/hapusbackup'),
            'restore' => url('settings/dbManager/restore'),
        ],
        'rows' => $rows,
    ];
@endphp

<div x-data="dbBackupPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <!-- Content Header -->
    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'Backup dan Restore'"
            icon="bi-database"
            tone="blue"
            subtitle="Kelola cadangan database & file uploads aplikasi."
            :crumbs="['Pengaturan', $subjudul ?? 'Backup']" />
    </div>

    <div class="mt-4 space-y-4 px-4 pb-6 md:px-6">

        <!-- Ringkasan -->
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-xs">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <i class="bi bi-collection"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-lg leading-none font-bold text-slate-800">{{ $stats['total'] ?? count($rows) }}</div>
                    <div class="mt-1 text-xs text-slate-400">Total Cadangan</div>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-xs">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <i class="bi bi-database"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-lg leading-none font-bold text-slate-800">{{ $stats['db'] ?? 0 }}</div>
                    <div class="mt-1 text-xs text-slate-400">Backup Database <span class="text-slate-300">· {{ $stats['arsip'] ?? 0 }} arsip cron</span></div>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-xs">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <i class="bi bi-file-earmark-zip"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-lg leading-none font-bold text-slate-800">{{ $stats['file'] ?? 0 }}</div>
                    <div class="mt-1 text-xs text-slate-400">Backup File Uploads</div>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 shadow-xs">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                    <i class="bi bi-clipboard-data"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-lg leading-none font-bold text-slate-800">{{ $stats['total_size'] ?? '0 KB' }}</div>
                    <div class="mt-1 truncate text-xs text-slate-400">
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
        <x-ui.card title="Aksi Backup" icon="bi-cloud-arrow-up" tone="blue"
            subtitle="Jalankan backup database, file uploads, atau keduanya">
            <x-slot:actions>
                <span class="hidden items-center gap-1.5 text-xs text-slate-400 md:inline-flex">
                    <i class="bi bi-calendar3"></i> {{ date('d M Y H:i') }}
                </span>
            </x-slot:actions>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="backupAll()" :disabled="busy"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-show="busy && activeAction === 'all'" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!(busy && activeAction === 'all')" class="bi bi-cloud-arrow-up"></i>
                    <span>Backup Semua</span>
                </button>
                <button type="button" @click="backupDb()" :disabled="busy"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-show="busy && activeAction === 'db'" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-slate-400/50 border-t-slate-600"></span>
                    <i x-show="!(busy && activeAction === 'db')" class="bi bi-database"></i>
                    <span>Backup Database</span>
                </button>
                <button type="button" @click="backupFiles()" :disabled="busy"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-xs transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-show="busy && activeAction === 'files'" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-slate-400/50 border-t-slate-600"></span>
                    <i x-show="!(busy && activeAction === 'files')" class="bi bi-file-earmark-zip"></i>
                    <span>Backup File</span>
                </button>
            </div>

            <div class="mt-4">
                <div class="mb-1.5 flex items-center justify-between gap-3">
                    <span class="text-xs text-slate-500" x-text="message"></span>
                    <span class="text-xs font-semibold text-slate-400" x-text="pct + '%'"></span>
                </div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100" role="progressbar"
                    aria-valuemin="0" aria-valuemax="100" :aria-valuenow="pct">
                    <div class="h-full rounded-full bg-blue-500 transition-all duration-300"
                        :style="'width:' + pct + '%'"></div>
                </div>
            </div>
        </x-ui.card>

        <!-- Banner keamanan -->
        <div class="flex gap-3 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3">
            <i class="bi bi-shield-check mt-0.5 text-base text-blue-500"></i>
            <div class="space-y-1 text-xs leading-relaxed text-blue-700">
                <p><b>Cadangan disimpan di luar webroot</b> (storage/app/backups) dan hanya bisa diunduh lewat rute terautentikasi admin.</p>
                <p>Restore selalu membuat <b>snapshot otomatis</b> keadaan sebelum data ditimpa, membutuhkan konfirmasi ketik <b>RESTORE</b>, dan arsip otomatis cron tidak ikut terhapus.</p>
            </div>
        </div>

        <!-- Riwayat -->
        <x-ui.card title="Riwayat" icon="bi-clock-history" tone="slate" bodyClass="p-0">
            <x-slot:meta>
                <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-bold text-slate-500">
                    {{ count($rows) }} file
                </span>
            </x-slot:meta>

            <x-slot:actions>
                <div class="relative">
                    <i class="bi bi-search pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-xs text-slate-400"></i>
                    <input type="search" x-model.debounce.200ms="query" placeholder="Cari file…" autocomplete="off"
                        class="w-56 rounded-xl border border-slate-200 bg-white py-2 pr-3 pl-8 text-xs transition-all outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                </div>
            </x-slot:actions>

            @if (count($rows) > 0)
                <x-ui.table>
                    <x-slot:head>
                        <th class="px-5 py-3">File</th>
                        <th class="px-5 py-3 text-right">Ukuran</th>
                        <th class="px-5 py-3 text-right">Waktu</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </x-slot:head>

                    <template x-for="row in filtered" :key="row.name">
                        <tr class="transition-colors hover:bg-slate-50/70">
                            <td class="px-5 py-3">
                                <div class="flex min-w-0 items-start gap-2">
                                    <span class="mt-0.5 inline-flex shrink-0 items-center gap-1 rounded-md border px-2 py-0.5 text-[11px] font-semibold"
                                        :class="row.badge.class">
                                        <i class="bi" :class="row.badge.icon"></i>
                                        <span x-text="row.badge.label"></span>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="truncate text-[13px] font-medium text-slate-700" :title="row.name" x-text="row.name"></div>
                                        <div class="mt-0.5 flex items-center gap-1.5 text-[11px] text-slate-400">
                                            <span x-text="row.date"></span>
                                            <span x-show="row.status" class="inline-flex items-center gap-0.5" :class="row.status ? row.status.class : ''">
                                                <i class="bi" :class="row.status ? row.status.icon : ''"></i>
                                                <span x-text="row.status ? row.status.text : ''"></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap text-slate-500" x-text="row.size"></td>
                            <td class="px-5 py-3 text-right text-xs whitespace-nowrap text-slate-400" x-text="row.dateShort"></td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <a :href="row.download" title="Download"
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 no-underline transition-colors hover:bg-slate-50">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                    <button type="button" x-show="row.canRestore" @click="restore(row.name)" title="Restore"
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 transition-colors hover:bg-slate-50">
                                        <i class="bi bi-arrow-counterclockwise"></i> Restore
                                    </button>
                                    <button type="button" @click="removeOne(row.name)" title="Hapus" :disabled="busy"
                                        class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-rose-600 transition-colors hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="filtered.length === 0" x-cloak>
                        <td colspan="4" class="px-5 py-8 text-center text-xs text-slate-400">Tidak ada file yang cocok.</td>
                    </tr>
                </x-ui.table>

                <x-slot:footer>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs text-slate-400">{{ count($rows) }} file tersimpan di storage/app/backups — arsip cron (db_*.sql.gz) hanya bisa dihapus satu per satu</span>
                        <button type="button" @click="removeAll()" :disabled="busy"
                            class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-rose-600 transition-colors hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60">
                            <i class="bi bi-trash3"></i> Hapus Semua
                        </button>
                    </div>
                </x-slot:footer>
            @else
                <div class="p-5">
                    <x-ui.empty icon="bi-archive" title="Belum ada backup"
                        description="Klik Backup Semua untuk membuat cadangan database dan file uploads pertama.">
                        <button type="button" @click="backupAll()" :disabled="busy"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                            <i class="bi bi-cloud-arrow-up"></i> Backup Sekarang
                        </button>
                    </x-ui.empty>
                </div>
            @endif
        </x-ui.card>

    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dbBackupPage', () => ({
        config: { csrf: '', urls: {}, rows: [] },

        rows: [],
        query: '',
        busy: false,
        activeAction: null,
        pct: 0,
        message: 'Siap. Pilih jenis backup.',

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');
            this.config = Object.assign({ csrf: '', urls: {}, rows: [] }, cfg);
            this.config.urls = cfg.urls || {};
            this.rows = cfg.rows || [];
        },

        get filtered() {
            const q = (this.query || '').toLowerCase().trim();
            if (!q) return this.rows;

            return this.rows.filter((row) => (row.search || '').includes(q));
        },

        setProgress(pct, message) {
            this.pct = Math.max(0, Math.min(100, Number(pct) || 0));

            if (message !== undefined) this.message = message;
        },

        errorMessage(result, fallback) {
            if (result && result.message) return result.message;
            if (result && result.msg) return result.msg;

            return fallback;
        },

        escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            })[char]);
        },

        async post(url, data = {}) {
            const body = new URLSearchParams();
            body.append('_token', this.config.csrf);

            Object.keys(data).forEach((key) => body.append(key, data[key] ?? ''));

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
                const err = new Error((result && (result.message || result.msg)) || 'Permintaan gagal (' + response.status + ').');
                err.result = result;
                throw err;
            }

            return result;
        },

        hasilBackup(result, fallback) {
            // HTTP 200 belum berarti backup lengkap: server menandai dump
            // parsial / zip gagal ditutup dengan status false.
            const ok = Boolean(result && result.status);
            this.setProgress(ok ? 100 : 0, (result && result.message) || fallback);

            Swal.fire({
                icon: ok ? 'success' : 'warning',
                title: ok ? 'Berhasil' : 'Backup Tidak Lengkap',
                text: (result && result.message) || fallback,
            }).then(() => window.location.reload());
        },

        async backupDb() {
            if (this.busy) return;

            this.busy = true;
            this.activeAction = 'db';
            this.setProgress(10, 'Memulai backup database…');
            Swal.fire({
                title: 'Backup Database',
                text: 'Silakan tunggu, jangan tutup halaman ini…',
                allowEscapeKey: false,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const result = await this.post(this.config.urls.db);
                this.hasilBackup(result, 'Backup database selesai.');
            } catch (error) {
                this.setProgress(0, 'Backup database gagal.');
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan: ' + this.errorMessage(error.result, 'Backup gagal') });
            } finally {
                this.busy = false;
                this.activeAction = null;
            }
        },

        async backupFiles() {
            if (this.busy) return;

            this.busy = true;
            this.activeAction = 'files';
            this.setProgress(10, 'Memulai backup file uploads…');
            Swal.fire({
                title: 'Backup File Uploads',
                text: 'Silakan tunggu, jangan tutup halaman ini…',
                allowEscapeKey: false,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const result = await this.post(this.config.urls.files);
                this.hasilBackup(result, 'Backup file selesai.');
            } catch (error) {
                this.setProgress(0, 'Backup file gagal.');
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan: ' + this.errorMessage(error.result, 'Backup gagal') });
            } finally {
                this.busy = false;
                this.activeAction = null;
            }
        },

        async backupAll() {
            if (this.busy) return;

            this.busy = true;
            this.activeAction = 'all';
            this.setProgress(5, 'Langkah 1/2: backup database…');
            Swal.fire({
                title: 'Backup Semua',
                text: 'Langkah 1/2: database… jangan tutup halaman ini.',
                allowEscapeKey: false,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const db = await this.post(this.config.urls.db);

                if (!db || !db.status) {
                    this.setProgress(0, (db && db.message) || 'Backup database tidak lengkap.');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Backup Tidak Lengkap',
                        text: (db && db.message) || 'Backup database gagal.',
                    }).then(() => window.location.reload());
                    return;
                }

                this.setProgress(50, ((db.message || 'Database OK.') + ' Lanjut backup file…'));
                Swal.update({ text: 'Langkah 2/2: file uploads…' });

                let files = null;

                try {
                    files = await this.post(this.config.urls.files);
                } catch (error) {
                    this.setProgress(50, 'Database OK, backup file gagal.');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sebagian Gagal',
                        text: 'Database berhasil, tetapi file gagal: ' + this.errorMessage(error.result, 'Backup file gagal'),
                    }).then(() => window.location.reload());
                    return;
                }

                if (!files || !files.status) {
                    this.setProgress(50, (files && files.message) || 'Backup file tidak lengkap.');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Backup Tidak Lengkap',
                        text: (files && files.message) || 'Database berhasil, tetapi backup file gagal.',
                    }).then(() => window.location.reload());
                    return;
                }

                this.setProgress(100, files.message || 'Semua backup selesai.');
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Database & file berhasil dibackup.',
                }).then(() => window.location.reload());
            } catch (error) {
                this.setProgress(0, 'Backup gagal.');
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan: ' + this.errorMessage(error.result, 'Backup gagal') });
            } finally {
                this.busy = false;
                this.activeAction = null;
            }
        },

        async removeAll() {
            if (this.busy) return;

            const konfirmasi = await Swal.fire({
                title: 'Hapus Semua Backup?',
                html: "<span class='text-red-600'><b>Semua backup manual & snapshot (db_backup_ / files_backup_ / pre_restore_) dihapus permanen!</b></span><br><br>"
                    + 'Arsip otomatis dari cron (<code>db_*.sql.gz</code>) <b>tidak disentuh</b> — hapus satu per satu lewat tombol di tabel bila memang perlu.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Semua',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.busy = true;
            Swal.fire({ title: 'Menghapus…', text: 'Silakan tunggu.', allowEscapeKey: false, allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const result = await this.post(this.config.urls.delAll);

                if (result && result.status) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message || 'Semua backup dihapus.' })
                        .then(() => window.location.reload());
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.message) || 'Gagal menghapus.' });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan: ' + this.errorMessage(error.result, 'Gagal menghapus') });
            } finally {
                this.busy = false;
            }
        },

        async removeOne(src) {
            if (this.busy) return;

            const konfirmasi = await Swal.fire({
                title: 'Hapus file ini?',
                html: 'File <b>' + this.escapeHtml(src) + '</b> akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.busy = true;
            Swal.fire({ text: 'Menghapus…', allowEscapeKey: false, allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const result = await this.post(this.config.urls.delOne, { src });

                if (result && result.status) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message || 'File dihapus.' })
                        .then(() => window.location.reload());
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.message) || 'Gagal menghapus.' });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan: ' + this.errorMessage(error.result, 'Gagal menghapus') });
            } finally {
                this.busy = false;
            }
        },

        async restore(src) {
            const konfirmasi = await Swal.fire({
                title: 'Restore Database',
                html: 'File: <b>' + this.escapeHtml(src) + '</b><br><br>'
                    + "<span class='text-red-600'><b>PERINGATAN:</b><br>"
                    + '1. Seluruh data saat ini akan DITIMPA.<br>'
                    + '2. Aplikasi membuat snapshot otomatis sebelum restore.<br>'
                    + '3. Proses tidak bisa dibatalkan di tengah jalan.</span><br><br>'
                    + 'Ketik <b>RESTORE</b> untuk melanjutkan:',
                icon: 'warning',
                input: 'text',
                inputPlaceholder: 'RESTORE',
                inputValidator: (value) => (!value || value.trim().toUpperCase() !== 'RESTORE') ? 'Ketik RESTORE untuk melanjutkan' : null,
                showCancelButton: true,
                confirmButtonColor: '#d97706',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Restore!',
                cancelButtonText: 'Batal',
                allowEscapeKey: false,
                allowOutsideClick: false,
            });

            if (!konfirmasi.isConfirmed) return;

            this.busy = true;
            Swal.fire({
                title: 'Membuat snapshot & merestore…',
                text: 'Jangan tutup halaman ini.',
                allowEscapeKey: false,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const result = await this.post(this.config.urls.restore, { src, konfirmasi: 'RESTORE' });

                if (result && result.status) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: result.message || 'Restore selesai.' })
                        .then(() => window.location.reload());
                } else {
                    Swal.fire({ icon: 'warning', title: 'Restore Tidak Lengkap', text: (result && result.message) || 'Restore gagal.' })
                        .then(() => window.location.reload());
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan server: ' + this.errorMessage(error.result, 'Restore gagal') });
            } finally {
                this.busy = false;
            }
        },
    }));
});
</script>
@endsection
