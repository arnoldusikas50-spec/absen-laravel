@extends('layouts.main')

@section('content')

<?php
$pageConfig = [
    'csrf' => csrf_token(),
    'urls' => [
        'approve' => url('admin/presensi/approve-permit'),
        'reject' => url('admin/presensi/reject-permit'),
    ],
];
$pendingRows = $pending ?? [];
?>

<div x-data="pendingPermitsPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
    class="px-4 pb-6 pt-4 md:px-6 md:pt-6">

    <x-ui.page-header
        title="Verifikasi Izin/Sakit"
        icon="bi-file-earmark-medical"
        tone="blue"
        :subtitle="count($pendingRows) . ' permintaan izin/sakit menunggu verifikasi.'"
        :crumbs="['Presensi', 'Izin & Sakit']">
        <x-slot:actions>
            <a href="{{ url('admin/presensi') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mt-4">
        <x-ui.card title="Daftar Permintaan Izin/Sakit" icon="bi-list-check" tone="blue"
            subtitle="Setujui untuk mengisi absensi pada rentang tanggal terkait" bodyClass="p-0">

            @if (count($pendingRows) === 0)
                <div class="p-5">
                    <x-ui.empty icon="bi-check2-circle" title="Tidak ada permintaan pending"
                        description="Semua permintaan izin/sakit telah diverifikasi." />
                </div>
            @else
                <x-ui.table>
                    <x-slot:head>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-3 py-3">Guru</th>
                        <th class="px-3 py-3">Jenis & Keterangan</th>
                        <th class="px-3 py-3">Bukti</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </x-slot:head>

                    @foreach ($pendingRows as $p)
                        @php
                            $nama = $p->nama_guru;
                            $avatar = \App\Services\AvatarService::tampilan((string) ($p->foto ?? ''), (string) $nama);
                            $sakit = strtoupper((string) $p->jenis) === 'SAKIT';
                            $pUrl = null;

                            if (! empty($p->bukti)) {
                                $pRaw = trim((string) $p->bukti);

                                if (preg_match('#^https?://#i', $pRaw)) {
                                    $pUrl = $pRaw;
                                } elseif (file_exists(public_path() . '/' . ltrim($pRaw, '/'))) {
                                    $pUrl = url(ltrim($pRaw, '/'));
                                } else {
                                    $pUrl = url('uploads/izin/' . basename($pRaw));
                                }
                            }
                        @endphp

                        <tr x-show="!removed.includes({{ (int) $p->id }})" class="transition-colors hover:bg-slate-50/60">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                        <i class="bi bi-calendar-range text-xs"></i>
                                    </span>
                                    <div>
                                        <div class="text-xs font-bold text-slate-700">{{ date('d-m-Y', strtotime($p->tgl_mulai)) }}</div>
                                        <div class="text-[11px] text-slate-400">s/d {{ date('d-m-Y', strtotime($p->tgl_selesai)) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2">
                                    <img src="{{ $avatar }}" alt="Avatar {{ $nama }}"
                                        class="h-10 w-10 shrink-0 rounded-full object-cover shadow-sm">
                                    <div class="min-w-0">
                                        <div class="truncate text-xs font-bold text-slate-800">{{ $nama }}</div>
                                        <div class="text-[11px] text-slate-400">NIP: {{ $p->nip ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <span class="mb-1 inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $sakit ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                                    {{ strtoupper((string) $p->jenis) }}
                                </span>
                                <p class="max-w-[250px] text-xs text-slate-500">{{ $p->keterangan }}</p>
                            </td>
                            <td class="px-3 py-3">
                                @if ($pUrl)
                                    <a href="{{ $pUrl }}" target="_blank" rel="noopener"
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 no-underline transition-colors hover:bg-slate-50">
                                        <i class="bi bi-image"></i> Lihat Bukti
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex justify-end gap-1">
                                    <button type="button" @click="act({{ (int) $p->id }}, 'approve')" title="Setujui"
                                        :disabled="busy === {{ (int) $p->id }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-xs text-white transition-colors hover:bg-emerald-700 disabled:opacity-50">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <button type="button" @click="act({{ (int) $p->id }}, 'reject')" title="Tolak"
                                        :disabled="busy === {{ (int) $p->id }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-600 text-xs text-white transition-colors hover:bg-rose-700 disabled:opacity-50">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.card>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pendingPermitsPage', () => ({
        config: { csrf: '', urls: {} },

        busy: null,
        removed: [],

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');

            this.config = {
                csrf: cfg.csrf || '',
                urls: cfg.urls || {},
            };
        },

        async act(id, action) {
            if (this.busy === id) return;

            const menyetujui = action === 'approve';

            const konfirmasi = await Swal.fire({
                title: menyetujui ? 'Setujui Permintaan?' : 'Tolak Permintaan?',
                text: menyetujui
                    ? 'Absensi akan otomatis diupdate untuk rentang tanggal tersebut.'
                    : 'Permintaan izin/sakit akan ditolak.',
                icon: menyetujui ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonText: menyetujui ? 'Ya, Setujui' : 'Ya, Tolak',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.busy = id;

            try {
                const body = new URLSearchParams();
                body.append('_token', this.config.csrf);

                const response = await fetch(this.config.urls[action] + '/' + id, {
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
                    throw new Error((result && (result.message || result.msg)) || 'Gagal terhubung ke server.');
                }

                if (result && result.status) {
                    this.removed.push(id);
                    Swal.fire({
                        icon: 'success',
                        title: menyetujui ? 'Berhasil!' : 'Ditolak',
                        text: result.message || '',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.message) || '' });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: error.message || 'Gagal terhubung ke server.' });
            } finally {
                this.busy = null;
            }
        },
    }));
});
</script>
@endsection
