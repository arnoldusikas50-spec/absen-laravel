@extends('layouts.main')

@section('content')

<?php
$groupLabels = [
    'personal' => ['label' => 'Notifikasi Real-time (ke Grup + Personal Guru)', 'rows' => []],
    'grup_only' => ['label' => 'Notifikasi Broadcast (Grup Saja)', 'rows' => []],
];
$cells = [];
$personalMap = [];

foreach ($jenisList as $jenis => $meta) {
    $cfg = $config[$jenis] ?? null;
    $isPersonal = (bool) ($meta['personal'] ?? false);

    if ($isPersonal) {
        $groupLabels['personal']['rows'][$jenis] = $meta;
    } else {
        $groupLabels['grup_only']['rows'][$jenis] = $meta;
    }

    $personalMap[$jenis] = $isPersonal;
    $cells[$jenis] = [
        'tg_grup' => (int) ($cfg->tg_grup ?? 1) === 1,
        'wa_grup' => (int) ($cfg->wa_grup ?? 1) === 1,
        'tg_personal' => (int) ($cfg->tg_personal ?? ($isPersonal ? 1 : 0)) === 1,
        'wa_personal' => (int) ($cfg->wa_personal ?? ($isPersonal ? 1 : 0)) === 1,
    ];
}

$kolom = [
    ['key' => 'tg_grup', 'label' => 'TG Grup', 'icon' => 'bi-telegram', 'warna' => 'sky', 'personal' => false, 'ikon' => 'text-sky-500'],
    ['key' => 'wa_grup', 'label' => 'WA Grup', 'icon' => 'bi-whatsapp', 'warna' => 'emerald', 'personal' => false, 'ikon' => 'text-emerald-500'],
    ['key' => 'tg_personal', 'label' => 'TG Personal', 'icon' => 'bi-telegram', 'warna' => 'sky', 'personal' => true, 'ikon' => 'text-sky-400'],
    ['key' => 'wa_personal', 'label' => 'WA Personal', 'icon' => 'bi-whatsapp', 'warna' => 'emerald', 'personal' => true, 'ikon' => 'text-emerald-400'],
];

$pageConfig = [
    'csrf' => csrf_token(),
    'saveUrl' => url('admin/presensi/notif-config/save'),
    'cells' => $cells,
    'personal' => $personalMap,
];
?>

<div x-data="notifConfigPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'Pengaturan Channel Notifikasi'"
            icon="bi-sliders2"
            tone="violet"
            subtitle="Pilih kombinasi channel pengiriman untuk setiap jenis notifikasi. Perubahan aktif seketika tanpa restart."
            :crumbs="['Presensi', 'Channel Notifikasi']">
            <x-slot:actions>
                <a href="{{ url('admin/presensi/notifikasi-status') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                    <i class="bi bi-activity"></i> Status
                </a>
                <a href="{{ url('admin/presensi/telegram') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                    <i class="bi bi-telegram"></i> Telegram
                </a>
                <a href="{{ url('admin/presensi/whatsapp') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                    <i class="bi bi-whatsapp"></i> WhatsApp
                </a>
            </x-slot:actions>
        </x-ui.page-header>
    </div>

    <div class="mt-4 space-y-5 px-4 pb-6 md:px-6">

        <!-- Legenda channel -->
        <x-ui.card title="Legenda Channel" icon="bi-info-circle" tone="slate" bodyClass="p-4">
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-600">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block h-3 w-3 rounded-full bg-sky-400"></span>
                    <b>TG Grup</b> — Telegram ke grup/channel sekolah
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block h-3 w-3 rounded-full bg-emerald-400"></span>
                    <b>WA Grup</b> — WhatsApp ke nomor/grup broadcast
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block h-3 w-3 rounded-full border border-dashed border-sky-400 bg-sky-300"></span>
                    <b>TG Personal</b> — Telegram DM ke guru
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block h-3 w-3 rounded-full border border-dashed border-emerald-400 bg-emerald-300"></span>
                    <b>WA Personal</b> — WhatsApp ke nomor pribadi guru
                </span>
                <span class="ml-auto flex items-center gap-1.5">
                    <span class="inline-flex h-5 w-8 items-center justify-center rounded-md border border-slate-200 bg-slate-100 text-[10px] font-bold text-slate-400">—</span>
                    Sel bergaris = tidak berlaku (jenis ini tidak punya target personal)
                </span>
            </div>
        </x-ui.card>

        <!-- Matriks channel per jenis notifikasi -->
        <form x-ref="form" x-on:submit.prevent="simpan">
            <x-ui.card title="Matriks Channel Notifikasi" icon="bi-grid-3x3-gap" tone="violet"
                subtitle="Centang channel yang dipakai untuk mengirim tiap jenis notifikasi." bodyClass="p-0">

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-sm">
                        <caption class="sr-only">Matriks channel notifikasi per jenis</caption>
                        <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr class="border-b-2 border-slate-200/80">
                                <th scope="col" class="min-w-[210px] px-4 py-3 text-left">Jenis Notifikasi</th>
                                @foreach ($kolom as $k)
                                    <th scope="col"
                                        class="px-2 py-3 text-center {{ $k['personal'] ? 'border-l-2 border-dashed border-slate-200' : '' }}">
                                        <span class="flex flex-col items-center gap-0.5">
                                            <i class="bi {{ $k['icon'] }} {{ $k['ikon'] }} text-sm"></i>
                                            <span>{{ $k['label'] }}</span>
                                        </span>
                                    </th>
                                @endforeach
                                <th scope="col" class="px-3 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($groupLabels as $groupKey => $group)
                                <tr>
                                    <td colspan="6" class="border-y border-slate-200 bg-slate-50 px-4 py-2">
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                            {{ $group['label'] }}
                                        </span>
                                    </td>
                                </tr>

                                @foreach ($group['rows'] as $jenis => $meta)
                                    @php $rowPersonal = (bool) ($meta['personal'] ?? false); @endphp
                                    <tr class="transition-colors hover:bg-slate-50/60">
                                        <!-- Label jenis -->
                                        <td class="px-4 py-3">
                                            <div class="flex flex-col gap-0.5">
                                                <span class="font-semibold text-slate-800">{{ $meta['label'] }}</span>
                                                @if (($meta['pref_key'] ?? '') !== '')
                                                    <span class="text-[10px] font-normal text-slate-400">
                                                        Preferensi guru:
                                                        <code class="rounded bg-slate-100 px-1">{{ $meta['pref_key'] }}</code>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Kolom channel -->
                                        @foreach ($kolom as $k)
                                            @php $tidakBerlaku = $k['personal'] && ! $rowPersonal; @endphp
                                            <td class="px-2 py-3 text-center
                                                {{ $k['personal'] ? 'border-l-2 border-dashed border-slate-200' : '' }}
                                                {{ $tidakBerlaku ? 'bg-[repeating-linear-gradient(45deg,#f8fafc,#f8fafc_3px,#f1f5f9_3px,#f1f5f9_6px)]' : '' }}">
                                                <label class="flex items-center justify-center {{ $tidakBerlaku ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                                    <input type="checkbox"
                                                        x-model="cells['{{ $jenis }}'].{{ $k['key'] }}"
                                                        name="notif[{{ $jenis }}][{{ $k['key'] }}]"
                                                        value="1"
                                                        aria-label="{{ $meta['label'] }} — {{ $k['label'] }}"
                                                        @disabled($tidakBerlaku)
                                                        class="relative h-[22px] w-10 shrink-0 appearance-none rounded-full bg-slate-300 transition-colors before:absolute before:left-[3px] before:top-[3px] before:h-4 before:w-4 before:rounded-full before:bg-white before:shadow-sm before:transition-transform before:content-[''] checked:before:translate-x-[18px] disabled:cursor-not-allowed disabled:opacity-35 {{ $k['warna'] === 'sky' ? 'checked:bg-sky-500' : 'checked:bg-emerald-500' }}">
                                                </label>
                                            </td>
                                        @endforeach

                                        <!-- Aktifkan / matikan satu baris -->
                                        <td class="px-3 py-3">
                                            <div class="flex justify-center gap-1">
                                                <button type="button"
                                                    x-on:click="setRow('{{ $jenis }}', true)"
                                                    title="Aktifkan semua channel"
                                                    aria-label="Aktifkan semua channel {{ $meta['label'] }}"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-violet-200 bg-violet-50 text-[10px] text-violet-700 transition-colors hover:bg-violet-100">
                                                    <i class="bi bi-check2-all"></i>
                                                </button>
                                                <button type="button"
                                                    x-on:click="setRow('{{ $jenis }}', false)"
                                                    title="Matikan semua channel"
                                                    aria-label="Matikan semua channel {{ $meta['label'] }}"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-[10px] text-slate-500 transition-colors hover:bg-slate-100">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-slot:footer>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex gap-2">
                            <button type="button" x-on:click="setAll(true)"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-100">
                                <i class="bi bi-check2-all"></i> Aktifkan Semua
                            </button>
                            <button type="button" x-on:click="setAll(false)"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-500 transition-colors hover:bg-slate-100">
                                <i class="bi bi-x-circle"></i> Matikan Semua
                            </button>
                        </div>
                        <button type="submit" x-bind:disabled="saving"
                            class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-5 py-2 text-sm font-bold text-white shadow-xs shadow-violet-500/20 transition-colors hover:bg-violet-700 disabled:opacity-60">
                            <i class="bi bi-floppy2-fill"></i>
                            <span x-text="saving ? 'Menyimpan…' : 'Simpan Pengaturan'">Simpan Pengaturan</span>
                        </button>
                    </div>
                </x-slot:footer>
            </x-ui.card>
        </form>

        <!-- Hierarki kontrol -->
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4" role="note">
            <i class="bi bi-info-circle-fill mt-0.5 text-base text-amber-500"></i>
            <div class="space-y-1 text-xs text-amber-800">
                <p class="font-bold">Hierarki kontrol notifikasi (3 level)</p>
                <p>
                    <b>Level 1 (halaman ini)</b> — Admin mengatur channel mana yang aktif per jenis.
                    Bila semua channel mati, notifikasi tidak terkirim sama sekali.
                </p>
                <p>
                    <b>Level 2</b> — Guru bisa mematikan jenis notifikasi personal tertentu dari
                    halaman profil mereka. Ini hanya berpengaruh bila kolom TG/WA Personal di atas aktif.
                </p>
                <p>
                    <b>Level 3</b> — Kanal personal guru: WA hanya aktif bila nomor sudah dikonfirmasi
                    admin. Bila guru tidak punya kanal personal dan fallback ke grup diizinkan, pesan
                    tetap terkirim ke grup.
                </p>
                <p>
                    <b>Catatan</b> — <i>Kehadiran Rendah (Cron)</i> punya saklar utama tambahan di
                    <b>Pengaturan → Presensi</b> (<i>alert kehadiran</i>): cron hanya mengirim bila
                    saklar itu DAN channel di halaman ini sama-sama aktif. Mematikan semua channel
                    grup suatu jenis membuat cron mencatat <i>dilewati</i> (bukan gagal).
                </p>
            </div>
        </div>

    </div>

    <!-- Toast hasil simpan -->
    <div x-show="toast.show" x-cloak x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-4 opacity-0"
        class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-bold text-white shadow-lg"
        x-bind:class="toast.ok ? 'bg-emerald-600' : 'bg-rose-500'"
        role="status" aria-live="polite">
        <i class="bi" x-bind:class="toast.ok ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'"></i>
        <span x-text="toast.msg"></span>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('notifConfigPage', () => ({
        config: { csrf: '', saveUrl: '', cells: {}, personal: {} },
        cells: {},
        personal: {},
        saving: false,
        toast: { show: false, ok: true, msg: '' },
        toastTimer: null,

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');

            this.config = {
                csrf: cfg.csrf || '',
                saveUrl: cfg.saveUrl || '',
            };

            this.cells = cfg.cells || {};
            this.personal = cfg.personal || {};
        },

        // Kolom personal hanya berlaku untuk jenis notifikasi personal.
        bisaDiubah(jenis, channel) {
            const personalOnly = channel === 'tg_personal' || channel === 'wa_personal';

            return ! personalOnly || this.personal[jenis] === true;
        },

        setRow(jenis, nyala) {
            if (! this.cells[jenis]) return;

            ['tg_grup', 'wa_grup', 'tg_personal', 'wa_personal'].forEach((channel) => {
                if (! this.bisaDiubah(jenis, channel)) return;

                this.cells[jenis][channel] = nyala;
            });
        },

        setAll(nyala) {
            Object.keys(this.cells).forEach((jenis) => this.setRow(jenis, nyala));
        },

        tampilkanToast(pesan, ok) {
            this.toast = { show: true, ok: ok, msg: pesan };

            clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => { this.toast.show = false; }, 3000);
        },

        async simpan() {
            if (this.saving) return;

            this.saving = true;

            const body = new FormData(this.$refs.form);
            body.append('_token', this.config.csrf);

            try {
                const response = await fetch(this.config.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
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

                const ok = !!(result && result.status);

                this.tampilkanToast(
                    (result && result.message) || (response.ok ? 'Tersimpan.' : 'Gagal menyimpan.'),
                    ok
                );
            } catch (error) {
                this.tampilkanToast('Gagal menghubungi server.', false);
            } finally {
                this.saving = false;
            }
        },
    }));
});
</script>
@endsection
