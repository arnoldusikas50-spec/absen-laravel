@extends('layouts.main')

@section('content')

<?php
$pageConfig = [
    'csrf' => csrf_token(),
    'urls' => [
        'verify' => url('admin/presensi/whatsapp/verify'),
    ],
];
$linkedRows = $linked ?? [];
?>

<div x-data="whatsappPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
    class="px-4 pb-6 pt-4 md:px-6 md:pt-6">

    <x-ui.page-header
        :title="$judul ?? 'Tautan WhatsApp Guru'"
        icon="bi-whatsapp"
        tone="emerald"
        :subtitle="(int) ($verifiedCount ?? 0) . ' guru terverifikasi menerima notifikasi personal via WhatsApp.'"
        :crumbs="['Presensi', 'WhatsApp']">
        <x-slot:actions>
            <a href="{{ url('admin/presensi/notif-config') }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-3.5 py-2 text-xs font-semibold text-white no-underline shadow-xs shadow-violet-500/20 transition-colors hover:bg-violet-700">
                <i class="bi bi-sliders2"></i> Channel Notifikasi
            </a>
            <a href="{{ url('admin/presensi/telegram') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-telegram"></i> Telegram
            </a>
            <a href="{{ url('admin/presensi/notifikasi-status') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-activity"></i> Status
            </a>
            <a href="{{ url('admin/presensi') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-speedometer2"></i> Monitoring
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mt-4 space-y-5">

        <!-- Status gateway OpenWA -->
        <x-ui.card title="Status Gateway OpenWA" icon="bi-activity" tone="emerald" bodyClass="p-4">
            <x-slot:actions>
                @if (! empty($status['ok']))
                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">Siap</span>
                @else
                    <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-0.5 text-[11px] font-bold text-rose-700">Belum siap</span>
                @endif
            </x-slot:actions>

            @if (empty($status['ok']))
                <p class="text-xs text-rose-600">{{ $status['error'] ?? 'Tidak diketahui' }}</p>
            @endif

            <p class="mt-1 text-[11px] text-slate-500">
                Base URL: <code class="break-all font-mono">{{ $status['base_url'] ?? '-' }}</code>
                @if (! empty($status['session_name']))
                    · Sesi dikunci ke <code class="font-mono">{{ $status['session_name'] }}</code>
                @endif
            </p>

            @if (! empty($status['sessions']))
                <div class="mt-3 overflow-hidden rounded-xl border border-slate-200">
                    <x-ui.table>
                        <x-slot:head>
                            <th class="px-3 py-2">Sesi</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Nomor</th>
                            <th class="px-3 py-2">Keterangan</th>
                        </x-slot:head>

                        @foreach ($status['sessions'] as $s)
                            @php
                                $ready = $s['status'] === 'ready';
                                $badge = $ready
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                    : 'border-amber-200 bg-amber-50 text-amber-700';

                                // restriction = pembatasan yang imposed WhatsApp
                                // sendiri, bukan kegagalan gateway. Nomor
                                // seperti ini tidak boleh dipakai untuk broadcast.
                                $note = $s['last_error'];

                                if ($note === '' && ! empty($s['restriction'])) {
                                    $r = $s['restriction'];
                                    $note = 'Dibatasi WhatsApp: ' . ($r['kind'] ?? 'tidak diketahui')
                                        . (isset($r['expiresAt']) && $r['expiresAt'] ? ' (sampai ' . e(date('d/m/Y H:i', strtotime($r['expiresAt']))) . ')' : '');
                                }

                                if ($note === '') {
                                    $note = '—';
                                }
                            @endphp
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs text-slate-700">{{ $s['name'] ?: substr($s['id'], 0, 8) }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-bold {{ $badge }}">
                                        {{ $s['status'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 font-mono text-xs text-slate-600">{{ $s['phone'] ?: '—' }}</td>
                                <td class="px-3 py-2 text-xs text-slate-500">{{ $note }}</td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                </div>
                <p class="mt-2 text-[10px] text-slate-400">
                    Hanya sesi berstatus <code>ready</code> yang dipakai untuk kirim. QR di-pindai lewat dashboard OpenWA.
                </p>
            @endif
        </x-ui.card>

        <!-- Daftar nomor guru -->
        <x-ui.card title="Nomor WhatsApp Guru" icon="bi-person-check" tone="emerald"
            subtitle="Nomor terdaftar dan status verifikasinya" bodyClass="p-0">

            @if (count($linkedRows) === 0)
                <div class="p-5">
                    <x-ui.empty icon="bi-whatsapp" title="Belum ada guru yang mengisi nomor WhatsApp"
                        description="Guru menambahkannya sendiri dari aplikasi → menu Notifikasi." />
                </div>
            @else
                <div class="max-h-[68vh] overflow-y-auto">
                    <x-ui.table>
                        <x-slot:head>
                            <th class="w-12 px-3 py-3 text-center">No</th>
                            <th class="px-3 py-3">Guru</th>
                            <th class="px-3 py-3 text-center">Nomor WhatsApp</th>
                            <th class="px-3 py-3 text-center">Status</th>
                            <th class="w-28 px-3 py-3 text-center">Aksi</th>
                        </x-slot:head>

                        @foreach ($linkedRows as $row)
                            @php
                                $verified = (int) ($row->wa_verified ?? 0) === 1;
                                $userId = (int) $row->user_id;

                                $nama = trim((string) ($row->nama_guru ?? ''));

                                if ($nama === '') {
                                    $nama = trim((string) ($row->first_name ?? '') . ' ' . (string) ($row->last_name ?? ''));
                                }

                                if ($nama === '') {
                                    $nama = (string) ($row->username ?? 'Guru');
                                }
                            @endphp

                            <tr x-show="!removed.includes({{ $userId }})" class="transition-colors hover:bg-slate-50/60">
                                <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2.5 font-semibold text-slate-800">{{ $nama }}</td>
                                <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-600">+{{ $row->wa_number }}</td>
                                <td class="px-3 py-2.5 text-center">
                                    @if ($verified)
                                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">Aktif</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-700">Menunggu</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex justify-center gap-1">
                                        @if ($verified)
                                            <button type="button" @click="toggle({{ $userId }}, 0, false, false)" title="Nonaktifkan"
                                                :disabled="busy === {{ $userId }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-amber-600 transition-colors hover:bg-amber-50 disabled:opacity-50">
                                                <i class="bi bi-pause-circle"></i>
                                            </button>
                                        @else
                                            <button type="button" @click="toggle({{ $userId }}, 1, false, true)" title="Konfirmasi nomor ini milik guru"
                                                :disabled="busy === {{ $userId }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-200 bg-white text-xs text-emerald-600 transition-colors hover:bg-emerald-50 disabled:opacity-50">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        @endif
                                        <button type="button" @click="toggle({{ $userId }}, 0, true, false)" title="Hapus nomor"
                                            :disabled="busy === {{ $userId }}"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-rose-600 transition-colors hover:bg-rose-50 disabled:opacity-50">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                </div>
            @endif
        </x-ui.card>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3">
            <p class="text-[11px] text-amber-800">
                <i class="bi bi-info-circle mr-1"></i>
                Hanya nomor berstatus <b>Aktif</b> yang menerima kiriman. Konfirmasi dulu nomornya bersama guru:
                OpenWA membalas sukses untuk nomor format-benar yang ternyata bukan miliknya,
                sehingga salah ketik satu digit berarti data absensi terkirim ke orang yang tidak berhak.
            </p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('whatsappPage', () => ({
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

        async post(url, entries) {
            const body = new URLSearchParams();
            body.append('_token', this.config.csrf);

            entries.forEach(([key, value]) => body.append(key, value ?? ''));

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
                const err = new Error((result && (result.message || result.msg)) || 'Gagal terhubung ke server.');
                err.result = result;
                throw err;
            }

            return result;
        },

        async toggle(userId, verify, remove, confirm) {
            if (this.busy === userId) return;

            if (remove || confirm) {
                const konfirmasi = await Swal.fire(
                    remove
                        ? {
                            title: 'Hapus nomor?',
                            text: 'Guru tidak lagi menerima notifikasi WhatsApp personal.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, hapus',
                            cancelButtonText: 'Batal',
                            reverseButtons: true,
                        }
                        : {
                            title: 'Konfirmasi nomor?',
                            text: 'Pastikan nomor ini benar-benar milik guru tersebut.',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, konfirmasi',
                            cancelButtonText: 'Batal',
                            reverseButtons: true,
                        },
                );

                if (!konfirmasi.isConfirmed) return;
            }

            this.busy = userId;

            try {
                const result = await this.post(this.config.urls.verify, [
                    ['user_id', userId],
                    ['verify', remove ? 0 : verify],
                ]);

                if (result && result.status) {
                    // Baris hilang saat dihapus; badge berubah saat statusnya berubah.
                    if (remove) {
                        this.removed.push(userId);
                        Swal.fire({ icon: 'success', title: 'Terhapus', timer: 1500, showConfirmButton: false });
                        return;
                    }

                    window.location.reload();
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
