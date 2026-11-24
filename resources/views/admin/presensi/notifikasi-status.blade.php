@extends('layouts.main')

@section('content')

<?php
$badgeSiap = 'inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700';
$badgeMasalah = 'inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-0.5 text-[11px] font-bold text-rose-700';
$badgeNetral = 'inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600';
$badgeMati = 'inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-500';
$badgeAktif = 'inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700';
$badgeNonaktif = 'inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500';
?>

<div class="px-4 pt-4 md:px-6 md:pt-6">
    <x-ui.page-header
        :title="$judul ?? 'Status Notifikasi'"
        icon="bi-activity"
        tone="violet"
        subtitle="Kegagalan pengiriman yang sebelumnya hanya tersimpan di file log server."
        :crumbs="['Presensi', 'Status Notifikasi']">
        <x-slot:actions>
            <a href="{{ url('admin/presensi/notif-config') }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-3.5 py-2 text-xs font-semibold text-white no-underline shadow-xs shadow-violet-500/20 transition-colors hover:bg-violet-700">
                <i class="bi bi-sliders2"></i> Channel Notifikasi
            </a>
            <a href="{{ url('admin/presensi/whatsapp') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-whatsapp"></i> WhatsApp
            </a>
            <a href="{{ url('admin/presensi/telegram') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-telegram"></i> Telegram
            </a>
        </x-slot:actions>
    </x-ui.page-header>
</div>

<div class="mt-4 space-y-5 px-4 pb-6 md:px-6">

    <!-- Status kanal -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <x-ui.card title="Kanal WhatsApp" icon="bi-whatsapp" tone="emerald" bodyClass="p-4">
            <x-slot:actions>
                @if (($waMode ?? '') === 'openwa')
                    <span class="{{ $badgeNetral }}">mode: openwa</span>
                @elseif (($waMode ?? '') === 'fonnte')
                    <span class="{{ $badgeNetral }}">mode: fonnte</span>
                @else
                    <span class="{{ $badgeMati }}">mode: nonaktif</span>
                @endif

                @if (! empty($wa['ok']))
                    <span class="{{ $badgeSiap }}">Siap</span>
                @elseif (($waMode ?? '') === 'off')
                    <span class="{{ $badgeMati }}">Tidak aktif</span>
                @else
                    <span class="{{ $badgeMasalah }}">Bermasalah</span>
                @endif
            </x-slot:actions>

            @if (($waMode ?? '') === 'off')
                <p class="text-xs text-slate-500">Kanal WhatsApp dimatikan di Pengaturan → Notifikasi.</p>
            @elseif (empty($wa['ok']))
                <p class="text-xs text-rose-600">{{ $wa['error'] ?? 'Tidak diketahui' }}</p>
            @else
                <p class="text-[11px] text-slate-500">
                    {{ count(array_filter($wa['sessions'] ?? [], static fn ($s) => ($s['status'] ?? '') === 'ready')) }}
                    sesi siap dari {{ count($wa['sessions'] ?? []) }} total.
                </p>
                @foreach (($wa['sessions'] ?? []) as $s)
                    @if (($s['status'] ?? '') !== 'ready')
                        <p class="mt-1 text-[11px] text-amber-700">
                            Sesi <b>{{ $s['name'] ?: substr((string) ($s['id'] ?? ''), 0, 8) }}</b>
                            berstatus <code>{{ $s['status'] ?? '' }}</code>
                            @if (! empty($s['restriction']))
                                — dibatasi WhatsApp ({{ $s['restriction']['kind'] ?? '' }})
                            @endif
                        </p>
                    @endif
                @endforeach
            @endif
        </x-ui.card>

        <x-ui.card title="Kanal Telegram" icon="bi-telegram" tone="blue" bodyClass="p-4">
            <x-slot:actions>
                @if (! empty($tg['ok']))
                    <span class="{{ $badgeSiap }}">Siap</span>
                @else
                    <span class="{{ $badgeMasalah }}">Bermasalah</span>
                @endif
            </x-slot:actions>

            @if (empty($tg['ok']))
                <p class="text-xs text-rose-600">{{ $tg['error'] ?? 'Tidak diketahui' }}</p>
            @else
                <p class="text-[11px] text-slate-500">
                    Antrean tertunda: <b>{{ (int) ($tg['pending'] ?? 0) }}</b>
                </p>
                @if (! empty($tg['last_error']))
                    <p class="mt-1 text-[11px] text-rose-600">Error terakhir: {{ $tg['last_error'] }}</p>
                @endif
            @endif
        </x-ui.card>
    </div>

    <!-- Channel per jenis -->
    <x-ui.card title="Channel aktif per jenis" icon="bi-sliders2" tone="violet" bodyClass="p-0">
        <x-slot:actions>
            <a href="{{ url('admin/presensi/notif-config') }}"
                class="text-[11px] font-bold text-violet-600 no-underline transition-colors hover:underline">Ubah →</a>
        </x-slot:actions>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-[0.8rem]">
                <caption class="sr-only">Channel notifikasi yang aktif untuk setiap jenis notifikasi</caption>
                <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr class="border-b-2 border-slate-200/80">
                        <th scope="col" class="px-4 py-2.5">Jenis</th>
                        @foreach (['tg_grup', 'wa_grup', 'tg_personal', 'wa_personal'] as $ch)
                            <th scope="col" class="px-2 py-2.5 text-center">{{ $chLabels[$ch] ?? $ch }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach (($chJenis ?? []) as $jenis => $meta)
                        @php
                            $cfg = ($chConfig ?? [])[$jenis] ?? null;
                            $grupMati = ((int) ($cfg->tg_grup ?? 1) !== 1) && ((int) ($cfg->wa_grup ?? 1) !== 1);
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/60 {{ $grupMati ? 'bg-amber-50/60' : '' }}">
                            <td class="px-4 py-2 text-slate-700">
                                {{ $meta['label'] ?? $jenis }}
                                @if ($grupMati)
                                    <span class="ml-1 inline-flex items-center rounded-full border border-amber-200 bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">cron dilewati (Skip)</span>
                                @endif
                            </td>
                            @foreach (['tg_grup', 'wa_grup', 'tg_personal', 'wa_personal'] as $ch)
                                @php
                                    $nyala = ((int) ($cfg->$ch ?? 1) === 1);
                                    $takBerlaku = ! ($meta['personal'] ?? true) && in_array($ch, ['tg_personal', 'wa_personal'], true);
                                @endphp
                                <td class="px-2 py-2 text-center">
                                    @if ($takBerlaku)
                                        <span class="text-slate-300">—</span>
                                    @elseif ($nyala)
                                        <span class="{{ $badgeAktif }}">Aktif</span>
                                    @else
                                        <span class="{{ $badgeNonaktif }}">Mati</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <p class="text-[11px] text-slate-400">
                Baris berlabel <b>Skip</b> berarti kedua channel grupnya mati — cron mencatat
                <i>dilewati</i> (HTTP 200), bukan gagal. Ini bukan error gateway.
            </p>
        </x-slot:footer>
    </x-ui.card>

    <!-- Riwayat pengiriman cron -->
    <x-ui.card title="Riwayat pengiriman cron" icon="bi-clock-history" tone="slate" bodyClass="p-0">
        <x-slot:actions>
            @if (! empty($cronGagal))
                <span class="{{ $badgeMasalah }}">{{ (int) $cronGagal }} belum berhasil</span>
            @endif
        </x-slot:actions>

        @if (empty($cron))
            <div class="p-5">
                <x-ui.empty icon="bi-clock-history" title="Belum ada riwayat"
                    description="Baris muncul setelah endpoint cron pertama kali mengirim." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-[0.8rem]">
                    <caption class="sr-only">Riwayat pengiriman notifikasi oleh cron</caption>
                    <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr class="border-b-2 border-slate-200/80">
                            <th scope="col" class="px-4 py-2.5">Jenis</th>
                            <th scope="col" class="px-4 py-2.5">Periode</th>
                            <th scope="col" class="px-4 py-2.5 text-center">Status</th>
                            <th scope="col" class="px-4 py-2.5 text-center">Percobaan</th>
                            <th scope="col" class="px-4 py-2.5">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($cron as $r)
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-4 py-2 font-mono text-slate-700">{{ $r->jenis }}</td>
                                <td class="px-4 py-2 font-mono text-slate-500">{{ $r->periode }}</td>
                                <td class="px-4 py-2 text-center">
                                    @if ((int) $r->terkirim === 1)
                                        <span class="{{ $badgeSiap }}">Terkirim</span>
                                    @else
                                        <span class="{{ $badgeMasalah }}">Gagal</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center text-slate-500">{{ (int) $r->percobaan }}</td>
                                <td class="px-4 py-2 text-slate-500">
                                    {!! $r->catatan ? e(mb_strimwidth((string) $r->catatan, 0, 70, '…')) : '—' !!}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            <p class="text-[11px] text-slate-400">
                Hanya pengiriman yang <b>berhasil</b> yang mengunci periode. Yang gagal boleh dicoba lagi —
                perbaiki dulu, lalu panggil endpoint cron-nya lagi (tambahkan <code>&amp;force=1</code> bila memang
                ingin mengirim ulang yang sudah terkirim).
            </p>
        </x-slot:footer>
    </x-ui.card>

    <!-- DM guru tercepat gagal -->
    <x-ui.card title="DM guru tercepat yang gagal terkirim" icon="bi-person-check" tone="amber" bodyClass="p-0">
        @if (empty($dmGagal))
            <div class="p-5">
                <x-ui.empty icon="bi-person-check" title="Tidak ada DM guru tercepat yang gagal terkirim"
                    description="Penyebab paling umum: guru belum menautkan akun Telegram maupun WhatsApp." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-[0.8rem]">
                    <caption class="sr-only">DM guru tercepat yang gagal terkirim</caption>
                    <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr class="border-b-2 border-slate-200/80">
                            <th scope="col" class="px-4 py-2.5">Tanggal</th>
                            <th scope="col" class="px-4 py-2.5">Guru</th>
                            <th scope="col" class="px-4 py-2.5">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($dmGagal as $r)
                            <tr class="transition-colors hover:bg-slate-50/60">
                                <td class="px-4 py-2 font-mono text-slate-500">{{ $r->tgl }}</td>
                                <td class="px-4 py-2 text-slate-700">{{ $r->nama_guru }}</td>
                                <td class="px-4 py-2 text-slate-500">
                                    {!! $r->catatan ? e($r->catatan) : 'DM tidak diterima gateway' !!}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            <p class="text-[11px] text-slate-400">
                Penyebab paling umum: guru belum menautkan akun Telegram maupun WhatsApp.
                Hubungi guru tersebut agar mengisinya di halaman Profil.
            </p>
        </x-slot:footer>
    </x-ui.card>
</div>

@endsection
