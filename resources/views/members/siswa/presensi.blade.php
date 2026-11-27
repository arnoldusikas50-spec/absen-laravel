@extends('layouts.mobile_siswa')

@section('styles')
<style>
    /* Animasi dekorasi hero & transisi modal QR — tidak bisa dinyatakan
       sebagai utility Tailwind tanpa keyframes kustom. */
    @keyframes sSparkle {
        0% { transform: translateY(0) scale(.9); opacity: .4; }
        100% { transform: translateY(-5px) scale(1.15); opacity: 1; }
    }

    .s-sparkle { animation: sSparkle 4s ease-in-out infinite alternate; }

    @keyframes sBubbleFloat {
        0% { transform: translate(0, 0); }
        100% { transform: translate(4px, -6px); }
    }

    .s-bubble { animation: sBubbleFloat 6s ease-in-out infinite alternate; }

    @keyframes sLivePulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .45; }
    }

    .s-live { animation: sLivePulse 1.8s ease-in-out infinite; }
</style>
@endsection

@section('content')

<script src="{{ url('assets/js/wita-clock.js?v=' . (@filemtime(public_path() . '/' . 'assets/js/wita-clock.js') ?: time())) }}"></script>
<script>witaInit('{{ date('Y-m-d H:i:s') }}');</script>

<?php
$hour = (int) date('G');
$salam = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
$firstName = explode(' ', trim($siswa->nama ?? 'Siswa'))[0];
$namaSiswa = trim($siswa->nama ?? 'Siswa');
$initial = mb_strtoupper(mb_substr($namaSiswa, 0, 1));
$qrSiswaVal = ! empty($siswa->qr_value) ? $siswa->qr_value : (! empty($siswa->nis) ? $siswa->nis : (! empty($siswa->nisn) ? $siswa->nisn : (string) $siswa->id_siswa));

$todayJamMasuk = ($today && ! empty($today->jam_masuk)) ? substr($today->jam_masuk, 0, 5) : null;
$todayJamKeluar = ($today && ! empty($today->jam_keluar)) ? substr($today->jam_keluar, 0, 5) : null;

$bulanList = [];
for ($m = 1; $m <= 12; $m++) {
    $key = sprintf('%04d-%02d', date('Y'), $m);
    $bulanList[$key] = date('F Y', strtotime($key . '-01'));
}

$shown = [];
$cnt = 0;
$cur = date('Y-m');
foreach (array_reverse(array_keys($bulanList)) as $k) {
    if ($k <= $cur) {
        $shown[$k] = $bulanList[$k];
        $cnt++;
    }
    if ($cnt >= 6) {
        break;
    }
}

$d = is_array($denda ?? null) ? $denda : null;
$nettoAwal = $d ? (float) ($d['netto'] ?? 0) : 0;
$alphaAwal = $d ? (int) ($d['alpha'] ?? 0) : 0;
$bolosAwal = $d ? (int) ($d['bolos'] ?? 0) : 0;
$tanpaBarisAwal = $d ? (int) ($d['tanpa_baris'] ?? 0) : 0;
$cap = $d && isset($d['cap']) ? (float) $d['cap'] : null;
$adaDendaAwal = $nettoAwal > 0 || $alphaAwal > 0 || $bolosAwal > 0;

$skorD = $skor_disiplin ?? null;
$skorTerbaca = is_int($skorD);

$idK = (int) ($siswa->id_kelas ?? 0);
$cetakUrl = url('admin/presensi/siswa/kartu/cetak?id_siswa=' . $siswa->id_siswa . ($idK ? '&id_kelas=' . $idK : '') . '&desain=duplex&v=4');

$konfigSiswa = [
    'nama' => (string) ($siswa->nama ?? 'Siswa'),
    'nis' => (string) ($siswa->nis ?? '-'),
    'nisn' => (string) ($siswa->nisn ?? '-'),
    'kelas' => (string) ($siswa->nama_kelas ?? '-'),
    'qr' => (string) $qrSiswaVal,
    'sekolah' => (string) ($setting->sekolah ?? 'Sekolah'),
    'kota' => (string) ($setting->kota ?? 'Kab. Kupang'),
    'logo' => (string) ($logo_url ?? ''),
];
?>

<div x-data="presensiSiswa({{ json_encode($konfigSiswa) }}, '{{ url('siswa/presensi/history') }}', '{{ $bulan }}')"
    x-init="init()" class="flex min-h-screen flex-col bg-slate-50">

    {{-- ═════════ HERO ═════════ --}}
    <div class="relative overflow-hidden rounded-b-[32px] px-5 pt-[calc(22px+var(--safe-area-inset-top,0px))] pb-[52px] text-white"
        style="background: radial-gradient(130% 110% at 85% 0%, var(--accent-300, #c4b5fd) 0%, var(--accent-400, #a78bfa) 22%, var(--accent-500, #8b5cf6) 52%, var(--accent-600, #7c3aed) 78%, var(--accent-700, #6d28d9) 100%); box-shadow: 0 12px 36px rgb(var(--accent-600-rgb, 124, 58, 237) / 0.25);">

        <div class="pointer-events-none absolute inset-0 z-[1] overflow-hidden" aria-hidden="true">
            <svg class="absolute inset-0 h-full w-full" viewBox="0 0 500 220" preserveAspectRatio="none">
                <path d="M0,70 C160,150 340,10 500,60 L500,0 L0,0 Z" fill="rgba(255,255,255,0.08)" />
                <path d="M0,130 C190,50 310,160 500,95 L500,0 L0,0 Z" fill="rgba(255,255,255,0.05)" />
                <circle cx="440" cy="30" r="55" fill="rgba(255,255,255,0.12)" />
                <circle cx="70" cy="180" r="75" fill="rgba(255,255,255,0.08)" />
            </svg>
            <div class="s-bubble absolute left-1/2 top-[8%] h-[46px] w-[46px] rounded-full border border-white/20 bg-white/10 backdrop-blur-[4px]"></div>
            <div class="s-bubble absolute bottom-[20%] right-[22%] h-[30px] w-[30px] rounded-full border border-white/20 bg-white/10 backdrop-blur-[4px]" style="animation-direction: alternate-reverse;"></div>
            <span class="s-sparkle absolute right-[26%] top-[16%] text-sm opacity-90">✦</span>
            <span class="s-sparkle absolute right-[12%] top-[60%] text-[11px] opacity-65" style="animation-delay:1.5s">✦</span>
            <span class="s-sparkle absolute left-[48%] top-[22%] text-[13px] opacity-80" style="animation-delay:2.3s">✨</span>
        </div>

        <div class="relative z-10 mb-4 flex items-center justify-between">
            <div class="min-w-0">
                <p class="m-0 text-[13.5px] font-medium tracking-wide opacity-90">{{ $salam }},</p>
                <h1 class="m-0 truncate text-[27px] font-extrabold uppercase tracking-tight">{{ $firstName }},</h1>
            </div>

            <div class="shrink-0">
                @if (! empty($siswa->foto_url))
                    <img src="{{ $siswa->foto_url }}" alt="{{ $namaSiswa }}"
                        x-data="{ gagal: false }" x-show="! gagal" x-on:error="gagal = true"
                        class="h-[52px] w-[52px] rounded-[18px] border-[2.5px] border-white/90 bg-white object-cover shadow-lg">
                    <div x-data="{ gagal: false }" x-show="gagal" class="flex h-[52px] w-[52px] items-center justify-center rounded-[18px] border-[2.5px] border-white/90 bg-white/20 text-[22px] font-black text-white shadow-lg">{{ $initial }}</div>
                @else
                    <div class="flex h-[52px] w-[52px] items-center justify-center rounded-[18px] border-[2.5px] border-white/90 bg-white/20 text-[22px] font-black text-white shadow-lg">{{ $initial }}</div>
                @endif
            </div>
        </div>

        <div class="relative z-10 inline-flex max-w-full items-center gap-1.5 rounded-[14px] border border-white/30 bg-white/20 px-3.5 py-1.5 text-[11.5px] font-semibold leading-snug backdrop-blur-md">
            <i class="bi bi-stars shrink-0" aria-hidden="true"></i>
            <span class="truncate uppercase">NIS {{ $siswa->nis ?: '-' }} &bull; {{ $siswa->nama_kelas ?: '-' }}</span>
        </div>
    </div>

    {{-- ═════════ KARTU STATUS (mengapung) ═════════ --}}
    <div class="relative z-20 mx-4 -mt-[38px] rounded-[22px] border border-slate-200/90 bg-white px-4 pt-[18px] pb-4 shadow-[0_14px_34px_rgba(15,23,42,0.10)]">
        <div class="pb-2 pt-0.5 text-center">
            <div class="mb-0.5 flex items-center justify-center gap-1.5 text-[10px] font-extrabold uppercase tracking-[1.6px] text-slate-400">
                <span class="s-live h-[7px] w-[7px] rounded-full bg-emerald-500 shadow-[0_0_0_3px_rgba(34,197,94,0.18)]"></span>Jam Server
            </div>
            <div class="mb-2 font-mono text-[clamp(38px,11.5vw,46px)] font-black leading-none tracking-[1px] tabular-nums text-accent-500"
                x-text="jam">{{ date('H:i:s') }}</div>
            <span class="mb-1.5 block text-[10.5px] font-bold tracking-[1.4px] text-slate-400">WITA</span>
            <div class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11.5px] font-semibold capitalize text-slate-500"
                x-text="tanggal">{{ date('d M Y') }}</div>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2 border-t border-dashed border-slate-200 pt-3">
            <div class="flex flex-col items-center justify-center rounded-[18px] border border-emerald-100 bg-emerald-50 px-2 pb-2.5 pt-3 text-center">
                <span class="mb-1 inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wide text-emerald-700">
                    <i class="bi bi-arrow-down-left"></i>Jam Masuk
                </span>
                <span class="text-[21px] font-black tracking-tight tabular-nums text-slate-900">{{ $todayJamMasuk ?? '--:--' }}</span>
                @if ($todayJamMasuk !== null)
                    <span class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-extrabold text-emerald-700">
                        <i class="bi bi-check-circle-fill"></i>Hadir
                    </span>
                @else
                    <span class="mt-1.5 inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-extrabold text-slate-500">Belum Absen</span>
                @endif
            </div>

            <div class="flex flex-col items-center justify-center rounded-[18px] border border-slate-200/80 bg-slate-50 px-2 pb-2.5 pt-3 text-center">
                <span class="mb-1 inline-flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wide text-slate-500">
                    <i class="bi bi-arrow-up-right"></i>Jam Pulang
                </span>
                <span class="text-[21px] font-black tracking-tight tabular-nums text-slate-900">{{ $todayJamKeluar ?? '--:--' }}</span>
                @if ($todayJamKeluar !== null)
                    <span class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-extrabold text-slate-500">
                        <i class="bi bi-check-circle"></i>Selesai
                    </span>
                @elseif ($todayJamMasuk !== null)
                    <span class="mt-1.5 inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-extrabold text-slate-500">Belum Pulang</span>
                @else
                    <span class="mt-1.5 inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-extrabold text-slate-500">--:--</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ═════════ KONTEN UTAMA ═════════ --}}
    <div class="px-4 pt-4">

        {{-- Progress bulan --}}
        <div class="mb-4 rounded-2xl border border-slate-200 bg-white px-4 py-3.5 shadow-xs">
            <div class="mb-2 flex items-center justify-between text-xs">
                <span class="font-bold text-slate-500">Kehadiran Bulan <span class="font-black text-slate-900">{{ date('M Y', strtotime($bulan . '-01')) }}</span></span>
                <span class="text-sm font-black text-accent-600"><span x-text="stats.persen">{{ $stats['persen'] ?? 0 }}</span>%</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full bg-emerald-500 transition-[width] duration-500" :style="'width:' + (stats.persen || 0) + '%'"></div>
            </div>
        </div>

        {{-- Filter bulan --}}
        <div class="mb-4 flex items-center gap-3 rounded-[14px] border border-slate-200 bg-white px-4 py-2.5 shadow-xs">
            <i class="bi bi-calendar-month shrink-0 text-lg text-accent-600"></i>
            <form id="filterForm" action="" method="get" class="grow">
                <select name="bulan" id="filterBulan" x-model="bulan" @change="muatRiwayat()"
                    class="w-full cursor-pointer appearance-none border-0 bg-transparent p-1.5 text-sm font-bold text-accent-600 outline-hidden">
                    @foreach ($shown as $k => $v)
                        <option value="{{ $k }}" @selected($bulan == $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="v" value="3">
            </form>
            <button type="button" @click="muatUlang()" title="Refresh"
                class="inline-flex h-[2.2rem] w-[2.2rem] shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-accent-600 transition-colors hover:bg-slate-200">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>

        {{-- Statistik --}}
        <div class="mb-4 grid grid-cols-2 gap-2.5">
            @php
                $kartuStat = [
                    ['kunci' => 'hadir', 'label' => 'Hadir', 'icon' => 'bi-check-circle-fill', 'warna' => 'bg-emerald-50 text-emerald-600'],
                    ['kunci' => 'izin', 'label' => 'Izin', 'icon' => 'bi-envelope-fill', 'warna' => 'bg-sky-50 text-sky-600'],
                    ['kunci' => 'sakit', 'label' => 'Sakit', 'icon' => 'bi-heart-pulse-fill', 'warna' => 'bg-amber-50 text-amber-600'],
                    ['kunci' => 'alpha', 'label' => 'Alpha', 'icon' => 'bi-x-circle-fill', 'warna' => 'bg-rose-50 text-rose-600'],
                    ['kunci' => 'bdr', 'label' => 'BDR', 'icon' => 'bi-house-door-fill', 'warna' => 'bg-teal-50 text-teal-600'],
                    ['kunci' => 'tanpa_keterangan', 'label' => 'Tanpa Ket.', 'icon' => 'bi-question-circle-fill', 'warna' => 'bg-slate-100 text-slate-500'],
                ];
            @endphp

            @foreach ($kartuStat as $kartu)
                @if (in_array($kartu['kunci'], ['bdr', 'tanpa_keterangan'], true))
                    <div x-show="(stats['{{ $kartu['kunci'] }}'] || 0) > 0" x-cloak
                        class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3.5 shadow-xs transition-transform active:scale-[0.98]">
                        <span class="inline-flex h-[2.35rem] w-[2.35rem] shrink-0 items-center justify-center rounded-xl text-[1.05rem] {{ $kartu['warna'] }}">
                            <i class="bi {{ $kartu['icon'] }}"></i>
                        </span>
                        <div>
                            <div class="text-xl font-black leading-tight text-slate-900" x-text="stats['{{ $kartu['kunci'] }}'] || 0">{{ (int) ($stats[$kartu['kunci']] ?? 0) }}</div>
                            <div class="mt-0.5 text-[0.65rem] font-bold uppercase tracking-wider text-slate-500">{{ $kartu['label'] }}</div>
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3.5 shadow-xs transition-transform active:scale-[0.98]">
                        <span class="inline-flex h-[2.35rem] w-[2.35rem] shrink-0 items-center justify-center rounded-xl text-[1.05rem] {{ $kartu['warna'] }}">
                            <i class="bi {{ $kartu['icon'] }}"></i>
                        </span>
                        <div>
                            <div class="text-xl font-black leading-tight text-slate-900" x-text="stats['{{ $kartu['kunci'] }}'] || 0">{{ (int) ($stats[$kartu['kunci']] ?? 0) }}</div>
                            <div class="mt-0.5 text-[0.65rem] font-bold uppercase tracking-wider text-slate-500">{{ $kartu['label'] }}</div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Tagihan denda --}}
        <div x-show="denda !== null" x-cloak
            :class="dendaAktif ? 'border-rose-200 bg-rose-50/60' : 'border-emerald-200 bg-emerald-50/60'"
            class="mb-4 overflow-hidden rounded-[20px] border bg-white shadow-xs">
            <div class="px-5 py-[1.15rem]">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-[14px] text-xl"
                            :class="dendaAktif ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'">
                            <i class="bi" :class="dendaAktif ? 'bi-wallet2' : 'bi-check-circle-fill'"></i>
                        </div>
                        <div>
                            <div class="text-[0.95rem] font-extrabold leading-tight text-slate-900">Tagihan Denda</div>
                            <div class="mt-1 inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[0.68rem] font-extrabold"
                                :class="dendaAktif ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
                                <i class="bi" :class="dendaAktif ? 'bi-clock' : 'bi-check-circle-fill'"></i>
                                <span x-text="dendaAktif ? 'Belum dibayar' : 'Bersih'">{{ $adaDendaAwal ? 'Belum dibayar' : 'Bersih' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <div class="text-[1.35rem] font-black leading-tight tracking-tight text-slate-900" x-text="formatRupiah(dendaNetto)">Rp {{ number_format($nettoAwal, 0, ',', '.') }}</div>
                        @if ($cap > 0)
                            <div class="mt-0.5 text-[0.7rem] font-semibold text-slate-500">Plafon Rp {{ number_format($cap, 0, ',', '.') }}/bln</div>
                        @endif
                    </div>
                </div>

                <div class="mb-3 min-h-4 text-xs font-semibold text-slate-500"
                    x-text="dendaAktif ? 'Jumlah denda yang harus dibayar bulan ini' : 'Tidak ada pelanggaran denda bulan ini'">
                    {{ $adaDendaAwal ? 'Jumlah denda yang harus dibayar bulan ini' : 'Tidak ada pelanggaran denda bulan ini' }}
                </div>

                <div x-show="dendaAktif" x-cloak class="grid grid-cols-2 gap-2.5">
                    <div class="flex flex-col gap-1 rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-2.5">
                        <div class="text-[0.65rem] font-extrabold uppercase tracking-wide text-slate-400">Alpha</div>
                        <div class="text-[0.95rem] font-black text-slate-900"><span x-text="dendaAlpha">{{ $alphaAwal }}</span> hari</div>
                    </div>
                    <div class="flex flex-col gap-1 rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-2.5">
                        <div class="text-[0.65rem] font-extrabold uppercase tracking-wide text-slate-400">Bolos</div>
                        <div class="text-[0.95rem] font-black text-slate-900"><span x-text="dendaBolos">{{ $bolosAwal }}</span> hari</div>
                    </div>
                </div>

                <div x-show="dendaTanpaBaris > 0" x-cloak class="mt-3 flex items-center gap-1.5 border-t border-slate-100 pt-2 text-xs font-semibold text-slate-500">
                    <i class="bi bi-dash-circle"></i>
                    <span><span x-text="dendaTanpaBaris">{{ $tanpaBarisAwal }}</span> alpha belum punya baris presensi</span>
                </div>
            </div>
        </div>

        {{-- Skor disiplin --}}
        <div class="mb-4 overflow-hidden rounded-[20px] border border-slate-200 bg-white shadow-xs">
            <div class="px-5 py-[1.15rem]">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-[14px] bg-indigo-50 text-xl text-indigo-600">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <div class="text-[0.95rem] font-extrabold leading-tight text-slate-900">Skor Disiplin</div>
                            <div class="mt-0.5 text-[0.68rem] font-bold uppercase tracking-wider text-slate-500">
                                {{ ! $skorTerbaca ? 'Belum Terhitung' : ($skorD >= 90 ? 'Sangat Baik' : ($skorD >= 75 ? 'Cukup' : 'Perhatian')) }}
                            </div>
                        </div>
                    </div>
                    <div class="inline-flex h-[2.6rem] w-[2.6rem] shrink-0 items-center justify-center rounded-full text-base font-black text-white shadow-md
                        {{ ! $skorTerbaca ? 'bg-slate-200 text-slate-500' : ($skorD >= 90 ? 'bg-emerald-500' : ($skorD >= 75 ? 'bg-amber-500' : 'bg-rose-500')) }}">
                        {{ $skorTerbaca ? (int) $skorD : '—' }}
                    </div>
                </div>
                <div class="mt-2 min-h-4 text-xs font-medium text-slate-500">
                    {{ $skorTerbaca ? 'Skor maksimal 100 · Pertahankan kedisiplinanmu!' : 'Skor sedang tidak dapat dihitung · hubungi guru piket.' }}
                </div>
            </div>
        </div>

        {{-- Presensi hari ini --}}
        <div class="mb-4 overflow-hidden rounded-[20px] border border-slate-200 bg-white shadow-xs">
            <div class="px-5 py-[1.15rem]">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-[14px] bg-indigo-50 text-xl text-indigo-600">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div>
                            <div class="text-[0.95rem] font-extrabold leading-tight text-slate-900">Presensi Hari Ini</div>
                            <div class="mt-0.5 text-[0.7rem] font-semibold text-slate-500">{{ date('d M Y') }}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        @if (! $today)
                            <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[0.65rem] font-extrabold text-slate-600">
                                <i class="bi bi-clock"></i> Belum Absen
                            </span>
                        @elseif ($today_cat === 'hadir')
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[0.65rem] font-extrabold text-emerald-700"><i class="bi bi-check-circle-fill"></i> Hadir</span>
                        @elseif ($today_cat === 'izin')
                            <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2.5 py-0.5 text-[0.65rem] font-extrabold text-sky-700"><i class="bi bi-envelope-fill"></i> Izin</span>
                        @elseif ($today_cat === 'sakit')
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-[0.65rem] font-extrabold text-amber-700"><i class="bi bi-heart-pulse-fill"></i> Sakit</span>
                        @elseif ($today_cat === 'bdr')
                            <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2.5 py-0.5 text-[0.65rem] font-extrabold text-teal-700"><i class="bi bi-house-door-fill"></i> BDR</span>
                        @elseif ($today_cat === 'alpha')
                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-[0.65rem] font-extrabold text-rose-700"><i class="bi bi-x-circle-fill"></i> Alpha</span>
                        @elseif ($today_cat === 'tanpa_keterangan')
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[0.65rem] font-extrabold text-slate-600"><i class="bi bi-question-circle-fill"></i> Tanpa Ket.</span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[0.65rem] font-extrabold text-slate-700">{{ $today->status ?? '-' }}</span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1 rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-3">
                        <div class="text-[0.65rem] font-extrabold uppercase text-slate-400">Masuk</div>
                        <div class="text-[0.95rem] font-extrabold text-slate-900">{{ $todayJamMasuk ?? '--:--' }}</div>
                    </div>
                    <div class="flex flex-col gap-1 rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-3">
                        <div class="text-[0.65rem] font-extrabold uppercase text-slate-400">Keluar</div>
                        <div class="text-[0.95rem] font-extrabold text-slate-900">{{ $todayJamKeluar ?? '--:--' }}</div>
                    </div>
                </div>

                @if ($today && ! empty($today->keterangan))
                    <div class="mt-3 flex items-center gap-1.5 border-t border-slate-100 pt-2 text-xs font-semibold text-amber-700">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>{{ $today->keterangan }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Riwayat --}}
        <div id="riwayat" class="scroll-mt-4 pb-4">
            <h2 class="mb-3 text-sm font-extrabold uppercase tracking-wide text-slate-600">Riwayat Presensi</h2>

            <div x-show="memuat" class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-8 text-center">
                <i class="bi bi-hourglass-split mb-1 block text-3xl text-slate-300"></i>
                <div class="text-sm font-bold text-slate-800">Memuat riwayat...</div>
            </div>

            <div x-show="! memuat && galat !== ''" x-cloak class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-8 text-center">
                <i class="bi bi-wifi-off mb-1 block text-3xl text-slate-300"></i>
                <div class="text-sm font-bold text-slate-800" x-text="galat"></div>
            </div>

            <div x-show="! memuat && galat === '' && riwayat.length === 0" x-cloak class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-8 text-center">
                <i class="bi bi-inbox mb-1 block text-3xl text-slate-300"></i>
                <div class="text-sm font-bold text-slate-800">Belum ada data presensi bulan ini.</div>
            </div>

            <div class="space-y-4">
                <template x-for="(r, idx) in riwayat" :key="(r.tgl || '') + '-' + idx">
                    <div class="relative flex gap-3">
                        <span x-show="idx < riwayat.length - 1" class="absolute left-[0.95rem] top-[1.1rem] bottom-[-0.5rem] z-0 w-0.5 rounded bg-slate-200"></span>

                        <div class="z-[1] flex w-[2.2rem] shrink-0 flex-col items-center">
                            <div class="flex h-[2.2rem] w-[2.2rem] flex-col items-center justify-center rounded-full border border-slate-200 bg-white shadow-xs">
                                <span class="text-xs font-black leading-none text-slate-800" x-text="tanggalAngka(r.tgl)"></span>
                                <span class="text-[8px] font-bold uppercase leading-tight text-slate-400" x-text="bulanSingkat(r.tgl)"></span>
                            </div>
                        </div>

                        <div class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-xs">
                            <div class="flex items-center justify-between gap-2">
                                <div class="font-mono text-sm font-black text-slate-800">
                                    <span x-text="jamPendek(r.jam_masuk) || '-'"></span>
                                    <template x-if="jamPendek(r.jam_keluar)">
                                        <span class="mx-1 text-[10px] text-slate-300">&rarr; <span x-text="jamPendek(r.jam_keluar)"></span></span>
                                    </template>
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-0.5 text-[0.65rem] font-extrabold"
                                    :class="badgeKategori(r.kategori).kelas">
                                    <i class="bi" :class="badgeKategori(r.kategori).icon"></i>
                                    <span x-text="badgeKategori(r.kategori).label"></span>
                                </span>
                            </div>

                            <template x-if="r.keterangan">
                                <p class="mt-1 text-xs text-slate-500" x-text="r.keterangan"></p>
                            </template>
                            <template x-if="! r.keterangan && ! jamPendek(r.jam_keluar) && r.kategori === 'hadir'">
                                <p class="mt-1 text-xs font-semibold text-amber-700">Belum absen pulang</p>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ═════════ MODAL QR ═════════ --}}
    <div x-show="qrBuka" x-cloak @click.self="tutupQr()" @keydown.escape.window="tutupQr()"
        x-transition.opacity
        class="fixed inset-0 z-[2000] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
        <div x-show="qrBuka" x-transition.scale.origin.center
            class="relative w-full max-w-sm overflow-hidden rounded-[28px] bg-white shadow-2xl">
            <button type="button" @click="tutupQr()" aria-label="Tutup"
                class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-white transition-colors hover:bg-white/25">
                <i class="bi bi-x-lg"></i>
            </button>

            <div class="bg-linear-to-br from-indigo-700 to-indigo-900 px-6 pb-6 pt-7 text-center text-white">
                <div class="mb-3 inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/15 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider">
                    <i class="bi bi-qr-code-scan"></i> Kartu Presensi Digital
                </div>
                <h2 class="text-lg font-extrabold uppercase leading-tight">{{ $siswa->nama ?? 'Siswa' }}</h2>
                <p class="mt-1 text-[11px] font-medium uppercase tracking-wide text-indigo-200">
                    NIS: {{ $siswa->nis ?: '-' }} &bull; Kelas {{ $siswa->nama_kelas ?: '-' }}
                </p>
                <span class="mt-3 inline-block rounded-full border border-white/25 bg-white/15 px-3 py-1 text-[11px] font-bold">{{ $setting->sekolah ?? 'Sekolah' }}</span>
            </div>

            <div class="flex justify-center px-6 pb-2 pt-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                    <div x-ref="qrBox" class="flex h-[176px] w-[176px] items-center justify-center"></div>
                </div>
            </div>

            <p class="px-6 text-center font-mono text-sm font-bold text-slate-800">{{ $qrSiswaVal }}</p>
            <p class="mt-2 flex items-center justify-center gap-1.5 px-6 text-center text-[11px] font-semibold text-slate-400">
                <i class="bi bi-camera-fill"></i> Arahkan ke kamera Kiosk Scanner di sekolah
            </p>

            <div class="mt-5 grid gap-2 px-6 pb-6">
                <a href="{{ $cetakUrl }}" target="_blank"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white no-underline transition-colors hover:bg-indigo-700">
                    <i class="bi bi-printer-fill"></i> Cetak Kartu
                </a>
                <button type="button" @click="unduhKartu()"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-emerald-700">
                    <i class="bi bi-download"></i> Unduh JPG
                </button>
                <button type="button" @click="tutupQr()"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-100">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/app/js/qrcode.min.js') }}"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('presensiSiswa', (config, urlRiwayat, bulanAwal) => ({
        bulan: bulanAwal,
        jam: '{{ date('H:i:s') }}',
        tanggal: '{{ date('d M Y') }}',
        memuat: true,
        galat: '',
        riwayat: [],
        stats: { hadir: {{ (int) ($stats['hadir'] ?? 0) }}, izin: {{ (int) ($stats['izin'] ?? 0) }}, sakit: {{ (int) ($stats['sakit'] ?? 0) }}, alpha: {{ (int) ($stats['alpha'] ?? 0) }}, bdr: {{ (int) ($stats['bdr'] ?? 0) }}, tanpa_keterangan: {{ (int) ($stats['tanpa_keterangan'] ?? 0) }}, persen: {{ (int) ($stats['persen'] ?? 0) }} },
        denda: {{ $d !== null ? '{}' : 'null' }},
        dendaNetto: {{ (float) $nettoAwal }},
        dendaAlpha: {{ (int) $alphaAwal }},
        dendaBolos: {{ (int) $bolosAwal }},
        dendaTanpaBaris: {{ (int) $tanpaBarisAwal }},
        qrBuka: false,
        qrDibuat: false,

        get dendaAktif() {
            return this.dendaNetto > 0 || this.dendaAlpha > 0 || this.dendaBolos > 0;
        },

        init() {
            // Jembatan untuk tombol QR di bottom nav layout mobile_siswa.
            window.openStudentQrModal = () => this.bukaQr();
            window.closeStudentQrModal = () => this.tutupQr();
            window.downloadKartuJpg = () => this.unduhKartu();

            window.setInterval(() => this.perbaruiJam(), 1000);
            this.perbaruiJam();
            this.muatRiwayat();

            if (window.location.hash === '#qr' || window.location.hash === '#kartu-qr') {
                this.bukaQr();
            }
        },

        perbaruiJam() {
            const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            let sekarang;

            try {
                sekarang = (window.witaAktif && witaAktif()) ? witaNow() : new Date();
            } catch (e) {
                sekarang = new Date();
            }

            const dua = (n) => String(n).padStart(2, '0');
            this.jam = dua(sekarang.getHours()) + ':' + dua(sekarang.getMinutes()) + ':' + dua(sekarang.getSeconds());
            this.tanggal = hari[sekarang.getDay()] + ', ' + sekarang.getDate() + ' ' + bulan[sekarang.getMonth()] + ' ' + sekarang.getFullYear();
        },

        muatUlang() {
            window.location.reload();
        },

        muatRiwayat() {
            this.memuat = true;
            this.galat = '';

            fetch(urlRiwayat + '?bulan=' + encodeURIComponent(this.bulan), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then((response) => response.json())
                .then((res) => {
                    if (! res || ! res.status) {
                        this.galat = 'Gagal memuat riwayat.';

                        return;
                    }

                    this.riwayat = res.data || [];

                    if (res.stats) {
                        this.stats = {
                            hadir: res.stats.hadir || 0,
                            izin: res.stats.izin || 0,
                            sakit: res.stats.sakit || 0,
                            alpha: res.stats.alpha || 0,
                            bdr: res.stats.bdr || 0,
                            tanpa_keterangan: res.stats.tanpa_keterangan || 0,
                            persen: res.stats.persen || 0,
                        };

                        if (res.stats.denda) {
                            const d = res.stats.denda;
                            this.denda = d;
                            this.dendaNetto = Number(d.netto || 0);
                            this.dendaAlpha = Number(d.alpha || 0);
                            this.dendaBolos = Number(d.bolos || 0);
                            this.dendaTanpaBaris = Number(d.tanpa_baris || 0);
                        }
                    }
                })
                .catch(() => {
                    this.galat = 'Gagal menghubungi server.';
                })
                .finally(() => {
                    this.memuat = false;
                });
        },

        badgeKategori(kategori) {
            return {
                hadir: { label: 'Hadir', icon: 'bi-check-circle-fill', kelas: 'bg-emerald-100 text-emerald-700' },
                izin: { label: 'Izin', icon: 'bi-envelope-fill', kelas: 'bg-sky-100 text-sky-700' },
                sakit: { label: 'Sakit', icon: 'bi-heart-pulse-fill', kelas: 'bg-amber-100 text-amber-700' },
                alpha: { label: 'Alpha', icon: 'bi-x-circle-fill', kelas: 'bg-rose-100 text-rose-700' },
                bdr: { label: 'BDR', icon: 'bi-house-door-fill', kelas: 'bg-teal-100 text-teal-700' },
                tanpa_keterangan: { label: 'Tanpa Ket.', icon: 'bi-question-circle-fill', kelas: 'bg-slate-100 text-slate-600' },
            }[kategori] || { label: '—', icon: 'bi-dash-circle', kelas: 'bg-slate-100 text-slate-600' };
        },

        tanggalAngka(tgl) {
            const d = new Date(String(tgl || '') + 'T00:00:00');

            return isNaN(d) ? '-' : d.getDate();
        },

        bulanSingkat(tgl) {
            const d = new Date(String(tgl || '') + 'T00:00:00');

            return isNaN(d) ? '' : d.toLocaleDateString('id-ID', { month: 'short' });
        },

        jamPendek(jam) {
            return String(jam || '').substring(0, 5);
        },

        formatRupiah(nilai) {
            return 'Rp ' + Number(nilai || 0).toLocaleString('id-ID');
        },

        bukaQr() {
            this.qrBuka = true;

            if (this.qrDibuat) {
                return;
            }

            this.$nextTick(() => {
                const kotak = this.$refs.qrBox;

                if (! kotak) {
                    return;
                }

                if (window.QRCode) {
                    try {
                        kotak.innerHTML = '';
                        new QRCode(kotak, {
                            text: config.qr,
                            width: 176,
                            height: 176,
                            colorDark: '#1e1b4b',
                            colorLight: '#ffffff',
                            correctLevel: QRCode.CorrectLevel.M,
                        });
                    } catch (e) {
                        kotak.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent(config.qr) + '" alt="QR Code" class="h-[176px] w-[176px] rounded-lg">';
                    }
                } else {
                    kotak.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' + encodeURIComponent(config.qr) + '" alt="QR Code" class="h-[176px] w-[176px] rounded-lg">';
                }

                this.qrDibuat = true;
            });
        },

        tutupQr() {
            this.qrBuka = false;
        },

        unduhKartu() {
            this.bukaQr();

            const { nama, nis, nisn, kelas, qr: qrVal, sekolah, kota, logo: logoUrl } = config;
            const W = 720;
            const H = 1140;
            const off = document.createElement('canvas');
            off.width = W;
            off.height = H;
            const ctx = off.getContext('2d');

            const fillRoundedRect = (x, y, w, h, r) => {
                ctx.beginPath();
                ctx.moveTo(x + r, y);
                ctx.lineTo(x + w - r, y);
                ctx.quadraticCurveTo(x + w, y, x + w, y + r);
                ctx.lineTo(x + w, y + h - r);
                ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
                ctx.lineTo(x + r, y + h);
                ctx.quadraticCurveTo(x, y + h, x, y + h - r);
                ctx.lineTo(x, y + r);
                ctx.quadraticCurveTo(x, y, x + r, y);
                ctx.closePath();
                ctx.fill();
            };
            const strokeRoundedRect = (x, y, w, h, r) => {
                ctx.beginPath();
                ctx.moveTo(x + r, y);
                ctx.lineTo(x + w - r, y);
                ctx.quadraticCurveTo(x + w, y, x + w, y + r);
                ctx.lineTo(x + w, y + h - r);
                ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
                ctx.lineTo(x + r, y + h);
                ctx.quadraticCurveTo(x, y + h, x, y + h - r);
                ctx.lineTo(x, y + r);
                ctx.quadraticCurveTo(x, y, x + r, y);
                ctx.closePath();
                ctx.stroke();
            };
            const clipRoundedRect = (x, y, w, h, r) => {
                ctx.beginPath();
                ctx.moveTo(x + r, y);
                ctx.lineTo(x + w - r, y);
                ctx.quadraticCurveTo(x + w, y, x + w, y + r);
                ctx.lineTo(x + w, y + h - r);
                ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
                ctx.lineTo(x + r, y + h);
                ctx.quadraticCurveTo(x, y + h, x, y + h - r);
                ctx.lineTo(x, y + r);
                ctx.quadraticCurveTo(x, y, x + r, y);
                ctx.closePath();
            };

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, W, H);

            const headerH = H * 0.27;

            const drawHeader = (logoImg) => {
                const grad = ctx.createLinearGradient(0, 0, 0, headerH);
                grad.addColorStop(0, '#4338ca');
                grad.addColorStop(1, '#312e81');
                ctx.fillStyle = grad;
                ctx.fillRect(0, 0, W, headerH);

                ctx.fillStyle = '#4f46e5';
                ctx.fillRect(0, headerH, W, 4);

                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = '#ffffff';

                if (logoImg && logoImg.complete && logoImg.naturalWidth) {
                    const logoSize = headerH * 0.42;
                    const lx = (W - logoSize) / 2;
                    const ly = headerH * 0.10;
                    ctx.save();
                    clipRoundedRect(lx, ly, logoSize, logoSize, 6);
                    ctx.clip();
                    ctx.drawImage(logoImg, lx, ly, logoSize, logoSize);
                    ctx.restore();
                    ctx.strokeStyle = 'rgba(255,255,255,0.35)';
                    ctx.lineWidth = 2;
                    ctx.strokeRect(lx, ly, logoSize, logoSize);
                }

                ctx.font = 'bold 21px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
                ctx.fillText(sekolah, W / 2, headerH * 0.56);

                ctx.fillStyle = '#c7c7ff';
                ctx.font = 'bold 13px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
                ctx.fillText('KARTU PRESENSI DIGITAL', W / 2, headerH * 0.79);

                const badgeText = kelas;
                ctx.fillStyle = 'rgba(255,255,255,0.22)';
                const m = ctx.measureText(badgeText);
                const badgeW = m.width + 20;
                const badgeH = 24;
                const bx = (W - badgeW) / 2;
                const by = headerH * 0.93 - badgeH / 2;
                fillRoundedRect(bx, by, badgeW, badgeH, badgeH / 2);
                ctx.strokeStyle = 'rgba(255,255,255,0.4)';
                ctx.lineWidth = 1.5;
                strokeRoundedRect(bx, by, badgeW, badgeH, badgeH / 2);
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 14px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
                ctx.fillText(badgeText, W / 2, by + badgeH / 2 + 2);
            };

            const drawQr = () => {
                const qrBox = document.createElement('div');
                qrBox.style.position = 'fixed';
                qrBox.style.top = '-9999px';
                qrBox.style.left = '-9999px';
                document.body.appendChild(qrBox);

                new QRCode(qrBox, {
                    text: qrVal,
                    width: 300,
                    height: 300,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M,
                });

                let qrSource = qrBox.querySelector('canvas') || qrBox.querySelector('img');
                if (! qrSource) {
                    const tbl = qrBox.querySelector('table');
                    if (tbl) {
                        const tmp = document.createElement('canvas');
                        tmp.width = 300;
                        tmp.height = 300;
                        tmp.getContext('2d').drawImage(tbl, 0, 0);
                        qrSource = tmp;
                    }
                }

                const qrSize = 360;
                const qrX = (W - qrSize) / 2;
                const qrY = headerH + (H - headerH) * 0.06;

                ctx.fillStyle = '#ffffff';
                fillRoundedRect(qrX, qrY, qrSize, qrSize, 14);
                ctx.drawImage(qrSource, qrX, qrY, qrSize, qrSize);
                ctx.strokeStyle = '#e2e8f0';
                ctx.lineWidth = 2;
                strokeRoundedRect(qrX, qrY, qrSize, qrSize, 14);

                document.body.removeChild(qrBox);

                ctx.fillStyle = '#0f172a';
                ctx.textAlign = 'center';
                ctx.font = 'bold 17px monospace';
                let codeText = qrVal;
                const maxW = W - 40;
                while (ctx.measureText(codeText).width > maxW && codeText.length > 1) {
                    codeText = codeText.slice(0, -1);
                }
                ctx.fillText(codeText, W / 2, qrY + qrSize + 40);

                ctx.fillStyle = '#64748b';
                ctx.font = 'bold 13px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
                ctx.fillText('SCAN ABSEN', W / 2, qrY + qrSize + 72);
            };

            const drawName = () => {
                const nameY = headerH + (H - headerH) * 0.66;
                ctx.fillStyle = '#0f172a';
                ctx.textAlign = 'center';
                ctx.font = 'bold 26px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
                ctx.fillText(String(nama).toUpperCase(), W / 2, nameY);
            };

            const drawMeta = () => {
                const metaY = headerH + (H - headerH) * 0.74;
                ctx.fillStyle = '#475569';
                ctx.textAlign = 'center';
                ctx.font = '14px monospace';
                ctx.fillText('NIS ' + nis + '  •  NISN ' + nisn, W / 2, metaY);
            };

            const drawFooter = () => {
                const footerH = H * 0.13;
                const footerY = H - footerH;
                ctx.fillStyle = '#f8fafc';
                ctx.fillRect(0, footerY, W, footerH);
                ctx.strokeStyle = '#f1f5f9';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(0, footerY);
                ctx.lineTo(W, footerY);
                ctx.stroke();

                ctx.fillStyle = '#475569';
                ctx.textAlign = 'start';
                ctx.font = 'bold 13px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
                ctx.fillText('Wajib dibawa KBM / Harian', 28, footerY + footerH * 0.42);

                ctx.textAlign = 'end';
                ctx.fillStyle = '#64748b';
                ctx.font = '13px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
                ctx.fillText(kota, W - 28, footerY + footerH * 0.42);
            };

            const selesaikan = () => {
                try {
                    const tautan = document.createElement('a');
                    tautan.download = 'kartu-presensi-' + qrVal + '.jpg';
                    tautan.href = off.toDataURL('image/jpeg', 0.92);
                    document.body.appendChild(tautan);
                    tautan.click();
                    document.body.removeChild(tautan);
                } catch (e) {
                    console.error('Gagal download JPG:', e);
                    alert('Gagal menghasilkan file JPG. Coba cetak kartu lalu simpan sebagai gambar/PDF.');
                }
            };

            drawQr();
            drawName();
            drawMeta();
            drawFooter();

            if (logoUrl) {
                const logoImg = new Image();
                logoImg.crossOrigin = 'anonymous';
                logoImg.src = logoUrl;
                logoImg.onload = () => { drawHeader(logoImg); selesaikan(); };
                logoImg.onerror = () => { drawHeader(null); selesaikan(); };
            } else {
                drawHeader(null);
                selesaikan();
            }
        },
    }));
});
</script>
@endsection
