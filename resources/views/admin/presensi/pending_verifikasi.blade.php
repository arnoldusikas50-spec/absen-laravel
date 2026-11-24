@extends('layouts.main')

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
$pageConfig = [
    'csrf' => csrf_token(),
    'urls' => [
        'terima' => url('admin/presensi/verifikasi-terima'),
        'tolak' => url('admin/presensi/verifikasi-tolak'),
    ],
];
?>

<div x-data="pendingVerifikasiPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
    class="px-4 pb-6 pt-4 md:px-6 md:pt-6">

    <x-ui.page-header
        title="Verifikasi Kehadiran"
        icon="bi-person-check-fill"
        tone="violet"
        subtitle="Absensi guru dengan alpha ≥ 10 hari ditahan di sini. Terima untuk menghitung ulang status (ONTIME/LATE), tolak untuk menjadikannya ALPHA."
        :crumbs="['Presensi', 'Verifikasi Kehadiran']">
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

        @if (count($pendingList) > 0)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex gap-2.5">
                    <i class="bi bi-info-circle-fill mt-0.5 text-amber-500"></i>
                    <div class="text-[11px] leading-relaxed text-amber-800">
                        Baris <strong>PENDING_VERIFICATION</strong> dihitung <strong>belum tentu</strong>: tidak masuk alpha maupun hadir di rekap &amp; surat teguran selama <strong>{{ (int) $hariTinjau }} hari</strong> sejak tanggal kehadirannya. Setelah itu cron harian menutupnya menjadi <strong>ALPHA</strong> (denda + poin + surat disamakan dengan jalur tolak). Menerima akan <strong>menghitung ulang status, poin, dan denda</strong> dari jam tersimpan (termasuk cap bulanan); menolak menjadikannya ALPHA sekarang juga.
                    </div>
                </div>
            </div>
        @endif

        <!-- Ringkasan -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <i class="bi bi-hourglass-split text-lg"></i>
                </span>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Menunggu</div>
                    <div class="text-lg font-extrabold tabular-nums text-slate-900">{{ count($pendingList) }} <span class="text-xs font-normal text-slate-400">kehadiran</span></div>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i class="bi bi-calendar-event text-lg"></i>
                </span>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Kehadiran Tertua</div>
                    <div class="text-sm font-extrabold leading-tight tabular-nums text-slate-900">{{ $pendingTertua && ! empty($pendingTertua->tgl) ? date('d/m/Y', strtotime($pendingTertua->tgl)) : '—' }}</div>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-500">
                    <i class="bi bi-alarm text-lg"></i>
                </span>
                <div>
                    <div class="text-xs font-semibold text-slate-500">Batas Tinjau</div>
                    <div class="text-lg font-extrabold tabular-nums text-slate-900">{{ (int) $hariTinjau }} <span class="text-xs font-normal text-slate-400">hari</span></div>
                </div>
            </div>
        </div>

        @if (count($pendingList) === 0)
            <x-ui.card bodyClass="p-5">
                <x-ui.empty icon="bi-check2-circle" title="Tidak ada antrean verifikasi"
                    description="Semua kehadiran yang ditahan telah diproses." />
            </x-ui.card>
        @else
            <div x-data="dataTable({ perPage: 100 })">
                <x-ui.card title="Antrean Verifikasi ({{ count($pendingList) }})" icon="bi-list-check" tone="violet"
                    subtitle="Urut tanggal terdahulu" bodyClass="p-0">
                    <x-slot:toolbar>
                        <div class="relative w-full sm:w-72">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                            <input type="text" x-ref="search" x-model="query" placeholder="Cari nama / NIP / tanggal..." autocomplete="off"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-8 pr-8 text-xs font-semibold text-slate-800 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" x-show="query !== ''" x-cloak @click="query = ''; $refs.search.focus()" title="Bersihkan pencarian"
                                class="absolute right-2 top-1/2 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-200/70 hover:text-slate-700">
                                <i class="bi bi-x text-xs"></i>
                            </button>
                        </div>
                    </x-slot:toolbar>

                    <x-ui.table class="min-w-[900px]">
                        <x-slot:head>
                            <th class="w-10 px-4 py-3">#</th>
                            <th class="px-3 py-3">Tanggal</th>
                            <th class="px-3 py-3">Guru</th>
                            <th class="px-3 py-3">Jam Masuk</th>
                            <th class="px-3 py-3">Jam Pulang</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </x-slot:head>

                        @foreach ($pendingList as $p)
                            <?php
                            $searchVerifikasi = implode(' ', array_filter([
                                $p->nama_guru ?? '',
                                $p->nip ?? '',
                                ! empty($p->tgl) ? date('d/m/Y', strtotime($p->tgl)) : '',
                                $fmtJam($p->jam_masuk ?? null) ?? '',
                                $fmtJam($p->jam_pulang ?? null) ?? '',
                            ]));
                            ?>
                            <tr data-search="{{ $searchVerifikasi }}" class="transition-colors hover:bg-slate-50/60">
                                <td class="px-4 py-3"><span data-rownum class="tabular-nums text-xs text-slate-400">{{ $loop->iteration }}</span></td>
                                <td class="px-3 py-3">
                                    <div class="whitespace-nowrap text-xs font-semibold text-slate-800">{{ date('d/m/Y', strtotime($p->tgl)) }}</div>
                                    <div class="mt-0.5 text-[11px] text-slate-400">{{ date('l', strtotime($p->tgl)) }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="text-xs font-semibold text-slate-800">{{ $p->nama_guru ?? 'Guru #' . ($p->user_id ?? '-') }}</div>
                                    <div class="text-[11px] text-slate-400">NIP: {{ $p->nip ?? '-' }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    <code class="rounded border border-slate-200 bg-slate-50 px-2 py-1 font-mono text-xs font-medium tabular-nums text-slate-700">{{ $fmtJam($p->jam_masuk ?? null) ?? '-' }}</code>
                                </td>
                                <td class="px-3 py-3">
                                    <code class="rounded border border-slate-200 bg-slate-50 px-2 py-1 font-mono text-xs font-medium tabular-nums text-slate-700">{{ $fmtJam($p->jam_pulang ?? null) ?? '-' }}</code>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" @click="terima({{ (int) $p->id }})"
                                            :disabled="busy === {{ (int) $p->id }}"
                                            title="Verifikasi & hitung ulang"
                                            class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1.5 text-[11px] font-bold text-white transition-colors hover:bg-emerald-700 disabled:opacity-50">
                                            <i class="bi bi-check-lg"></i>Terima
                                        </button>
                                        <button type="button" @click="tolak({{ (int) $p->id }})"
                                            :disabled="busy === {{ (int) $p->id }}"
                                            title="Tolak menjadi ALPHA"
                                            class="inline-flex items-center gap-1 rounded-lg bg-rose-600 px-2.5 py-1.5 text-[11px] font-bold text-white transition-colors hover:bg-rose-700 disabled:opacity-50">
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
                            <div class="flex items-center gap-1.5" x-show="pages > 1" x-cloak>
                                <button type="button" @click="prev()" :disabled="page === 1"
                                    class="inline-flex h-7 items-center rounded-lg border border-slate-200 bg-white px-2 text-[11px] font-semibold text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-40">Sebelumnya</button>
                                <span class="font-mono text-slate-500" x-text="page + ' / ' + pages"></span>
                                <button type="button" @click="next()" :disabled="page === pages"
                                    class="inline-flex h-7 items-center rounded-lg border border-slate-200 bg-white px-2 text-[11px] font-semibold text-slate-600 transition-colors hover:bg-slate-50 disabled:opacity-40">Berikutnya</button>
                            </div>
                        </div>
                    </x-slot:footer>
                </x-ui.card>

                <div x-show="total === 0" x-cloak class="mt-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-6 py-10 text-center">
                    <i class="bi bi-search mb-2 block text-3xl text-slate-300"></i>
                    <p class="text-sm font-semibold text-slate-500">Tidak ada data yang cocok</p>
                    <p class="mt-1 text-xs text-slate-400">Coba ubah kata kunci pencarian</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pendingVerifikasiPage', () => ({
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
            body.append('note', note || '');

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

        async terima(id) {
            if (this.busy === id) return;

            const konfirmasi = await Swal.fire({
                title: 'Terima Verifikasi?',
                text: 'Status akan dihitung ulang dari jam tersimpan (ONTIME/LATE + denda + cap).',
                icon: 'question',
                input: 'textarea',
                inputLabel: 'Catatan (Opsional)',
                inputPlaceholder: 'Catatan verifikasi...',
                inputAttributes: { 'aria-label': 'Catatan', maxlength: '500' },
                showCancelButton: true,
                confirmButtonText: 'Ya, Terima',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.busy = id;

            try {
                const result = await this.post(this.config.urls.terima + '/' + id, (konfirmasi.value || '').trim());

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

        async tolak(id) {
            if (this.busy === id) return;

            const konfirmasi = await Swal.fire({
                title: 'Tolak Menjadi ALPHA?',
                input: 'textarea',
                inputLabel: 'Alasan Penolakan',
                inputPlaceholder: 'Tulis alasan penolakan di sini...',
                inputAttributes: { 'aria-label': 'Alasan Penolakan', maxlength: '500' },
                showCancelButton: true,
                confirmButtonText: 'Tolak Menjadi ALPHA',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.busy = id;

            try {
                const result = await this.post(this.config.urls.tolak + '/' + id, (konfirmasi.value || '').trim());

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
