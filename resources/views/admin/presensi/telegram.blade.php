@extends('layouts.main')

@section('content')

<?php
$linkedRows = $linked ?? [];
$pageConfig = [
    'csrf' => csrf_token(),
    'urls' => [
        'test' => url('admin/presensi/telegram/test'),
        'unlink' => url('admin/presensi/telegram/unlink'),
    ],
];
?>

<div x-data="telegramLinkPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <!-- Content Header -->
    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'Tautan Telegram Guru'"
            icon="bi-telegram"
            tone="blue"
            :subtitle="count($linkedRows) . ' dari ' . (int) ($totalGuru ?? 0) . ' guru menautkan Telegram untuk notifikasi personal.'"
            :crumbs="['Presensi', 'Telegram']">
            <x-slot:actions>
                <a href="{{ url('admin/presensi/notif-config') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-3.5 py-2 text-xs font-semibold text-white no-underline shadow-xs shadow-violet-500/20 transition-colors hover:bg-violet-700">
                    <i class="bi bi-sliders2"></i> Channel Notifikasi
                </a>
                <a href="{{ url('admin/presensi/whatsapp') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                    <i class="bi bi-whatsapp"></i> WhatsApp
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
    </div>

    <div class="mt-4 space-y-5 px-4 pb-6 md:px-6">

        <!-- Status webhook -->
        <x-ui.card title="Status Webhook Bot" icon="bi-activity" tone="blue" bodyClass="p-4">
            <x-slot:actions>
                @if (! empty($wh['ok']))
                    @if (! empty($wh['url']))
                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">Terdaftar</span>
                    @else
                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-700">Belum terdaftar</span>
                    @endif
                @else
                    <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-0.5 text-[11px] font-bold text-rose-700">Bermasalah</span>
                @endif
            </x-slot:actions>

            @if (empty($wh['ok']))
                <p class="text-xs text-rose-600">{{ $wh['error'] ?? 'Tidak diketahui' }}</p>
            @else
                <p class="text-[11px] text-slate-500">
                    URL terdaftar:
                    <code class="font-mono break-all">{{ ($wh['url'] ?? '') !== '' ? $wh['url'] : '(kosong — webhook belum dipasang)' }}</code>
                </p>
                <p class="mt-1 text-[11px] text-slate-500">
                    Antrean tertunda: <b>{{ (int) ($wh['pending'] ?? 0) }}</b>
                    @if (! empty($wh['last_error']))
                        · Error terakhir Telegram: <b class="text-rose-600">{{ $wh['last_error'] }}</b>
                    @endif
                </p>
            @endif

            @if (! empty($whSetUrl))
                <p class="mt-3 mb-1 text-[11px] text-slate-500">
                    Belum terdaftar / URL tidak cocok? Buka tautan ini sekali (login sebagai admin tidak diperlukan — token bot di URL):
                </p>
                <a href="{{ $whSetUrl }}" target="_blank"
                    class="inline-flex items-center gap-1.5 rounded-full bg-sky-600 px-3.5 py-1.5 text-[11px] font-bold text-white no-underline transition-colors hover:bg-sky-700">
                    <i class="bi bi-plug-fill"></i> Pasang Webhook Sekarang
                </a>
                <p class="mt-1.5 text-[10px] text-slate-400">Wajib HTTPS dan dapat diakses publik. Setelah dipasang, kirim /start ulang ke bot.</p>
            @endif
        </x-ui.card>

        <!-- Daftar guru tertaut -->
        <x-ui.card title="Guru Tertaut Telegram" icon="bi-person-check" tone="blue"
            subtitle="Preferensi notifikasi personal per guru" bodyClass="p-0">

            @if (count($linkedRows) === 0)
                <div class="p-5">
                    <x-ui.empty icon="bi-telegram" title="Belum ada guru yang menautkan Telegram"
                        description="Guru dapat menautkan akun Telegram lewat bot agar menerima notifikasi personal." />
                </div>
            @else
                <div class="max-h-[68vh] overflow-y-auto">
                    <x-ui.table>
                        <x-slot:head>
                            <th class="w-12 px-3 py-3 text-center">No</th>
                            <th class="px-3 py-3">Guru</th>
                            <th class="px-3 py-3 text-center">Chat ID</th>
                            <th class="px-3 py-3 text-center">Denda</th>
                            <th class="px-3 py-3 text-center">Teguran</th>
                            <th class="px-3 py-3 text-center">Alpha</th>
                            <th class="px-3 py-3 text-center">Kehadiran</th>
                            <th class="w-28 px-3 py-3 text-center">Aksi</th>
                        </x-slot:head>

                        @foreach ($linkedRows as $index => $row)
                            @php
                                $prefs = json_decode((string) ($row->prefs ?? ''), true);
                                if (! is_array($prefs)) {
                                    $prefs = [];
                                }

                                foreach (['denda', 'teguran', 'alpha', 'kehadiran'] as $prefKey) {
                                    if (! array_key_exists($prefKey, $prefs)) {
                                        $prefs[$prefKey] = true;
                                    }
                                }

                                $nama = $row->nama_guru ?: trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));

                                if ($nama === '') {
                                    $nama = $row->username ?? ('User ' . $row->user_id);
                                }

                                $userId = (int) $row->user_id;
                            @endphp

                            <tr x-show="!removed.includes({{ $userId }})" class="transition-colors hover:bg-slate-50/60">
                                <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2.5 font-semibold text-slate-800">{{ $nama }}</td>
                                <td class="px-3 py-2.5 text-center font-mono text-xs">{{ $row->telegram_chat_id }}</td>

                                @foreach (['denda', 'teguran', 'alpha', 'kehadiran'] as $prefKey)
                                    <td class="px-3 py-2.5 text-center">
                                        @if (! empty($prefs[$prefKey]))
                                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">Aktif</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-500">Mati</span>
                                        @endif
                                    </td>
                                @endforeach

                                <td class="px-3 py-2.5">
                                    <div class="flex justify-center gap-1">
                                        <button type="button" @click="test({{ $userId }})" title="Kirim pesan tes"
                                            :disabled="busy === {{ $userId }}"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-sky-600 transition-colors hover:bg-sky-50 disabled:opacity-50">
                                            <i class="bi bi-send"></i>
                                        </button>
                                        <button type="button" @click="unlink({{ $userId }})" title="Lepas tautan"
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
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('telegramLinkPage', () => ({
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

        async test(userId) {
            if (this.busy === userId) return;

            this.busy = userId;

            try {
                const result = await this.post(this.config.urls.test, [['user_id', userId]]);
                const berhasil = !!(result && result.status);

                Swal.fire({
                    icon: berhasil ? 'success' : 'error',
                    title: berhasil ? 'Terkirim' : 'Gagal',
                    text: (result && result.message) || '',
                    timer: berhasil ? 2500 : undefined,
                    showConfirmButton: !berhasil,
                });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: error.message || 'Gagal terhubung ke server.' });
            } finally {
                this.busy = null;
            }
        },

        async unlink(userId) {
            const konfirmasi = await Swal.fire({
                title: 'Lepas tautan?',
                text: 'Guru tidak lagi menerima notifikasi personal.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, lepas',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.busy = userId;

            try {
                const result = await this.post(this.config.urls.unlink, [['user_id', userId]]);

                if (result && result.status) {
                    this.removed.push(userId);
                    Swal.fire({ icon: 'success', title: 'Dilepas', timer: 1500, showConfirmButton: false });
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
