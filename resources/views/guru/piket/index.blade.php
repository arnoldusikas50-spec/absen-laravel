@extends('layouts.main')

@section('content')

<?php
$kartuStatistik = [
    ['label' => 'Tamu Masuk', 'nilai' => $stats['tamu'] ?? 0, 'satuan' => 'Orang', 'icon' => 'bi-person-badge', 'gradien' => 'from-violet-500 to-violet-600'],
    ['label' => 'Kejadian', 'nilai' => $stats['kejadian'] ?? 0, 'satuan' => 'Kasus', 'icon' => 'bi-info-circle', 'gradien' => 'from-amber-500 to-amber-600'],
    ['label' => 'Jurnal Terisi', 'nilai' => $stats['jurnal'] ?? 0, 'satuan' => 'Kelas', 'icon' => 'bi-journal-check', 'gradien' => 'from-emerald-500 to-emerald-600'],
    ['label' => 'Siswa Absent', 'nilai' => $stats['siswa_absen'] ?? 0, 'satuan' => 'Siswa', 'icon' => 'bi-person-x', 'gradien' => 'from-rose-500 to-rose-600'],
];

$layanan = [
    ['url' => 'guru/piket/tamu', 'icon' => 'bi-person-rolodex', 'judul' => 'Buku Tamu', 'teks' => 'Catat tamu yang berkunjung ke sekolah', 'warna' => 'bg-violet-50 text-violet-600', 'hover' => 'hover:border-violet-300 hover:shadow-violet-500/10', 'hoverIcon' => 'group-hover:bg-violet-500'],
    ['url' => 'guru/piket/kejadian', 'icon' => 'bi-exclamation-octagon', 'judul' => 'Kejadian', 'teks' => 'Catat masalah/kejadian khusus siswa', 'warna' => 'bg-amber-50 text-amber-600', 'hover' => 'hover:border-amber-300 hover:shadow-amber-500/10', 'hoverIcon' => 'group-hover:bg-amber-500'],
    ['url' => 'guru/piket/absensi', 'icon' => 'bi-calendar2-check', 'judul' => 'Absen Kelas', 'teks' => 'Lihat dan bantu rekap absensi per kelas', 'warna' => 'bg-rose-50 text-rose-600', 'hover' => 'hover:border-rose-300 hover:shadow-rose-500/10', 'hoverIcon' => 'group-hover:bg-rose-500'],
    ['url' => 'guru/piket/laporan', 'icon' => 'bi-display', 'judul' => 'Monitoring', 'teks' => 'Aktivitas Guru & Siswa terpusat', 'warna' => 'bg-sky-50 text-sky-600', 'hover' => 'hover:border-sky-300 hover:shadow-sky-500/10', 'hoverIcon' => 'group-hover:bg-sky-500'],
    ['url' => 'guru/piket/laporan', 'icon' => 'bi-file-earmark-bar-graph', 'judul' => 'Laporan Guru', 'teks' => 'Rekap kehadiran guru di kelas', 'warna' => 'bg-blue-50 text-blue-600', 'hover' => 'hover:border-blue-300 hover:shadow-blue-500/10', 'hoverIcon' => 'group-hover:bg-blue-500'],
];
?>

<div class="px-4 pt-4 md:px-6 md:pt-6">
    <div x-data="{
            waktu: '',
            mulai() {
                const format = () => new Date().toLocaleTimeString('id-ID', { hour12: false });
                this.waktu = format();
                setInterval(() => { this.waktu = format(); }, 1000);
            },
        }" x-init="mulai()"
        class="relative overflow-hidden rounded-3xl bg-linear-to-br from-slate-800 via-slate-800 to-slate-700 px-6 py-7 shadow-lg shadow-slate-900/20">
        <span class="pointer-events-none absolute -right-12 -top-12 h-48 w-48 rounded-full bg-white/5" aria-hidden="true"></span>
        <span class="pointer-events-none absolute -bottom-16 right-24 h-40 w-40 rounded-full bg-white/5" aria-hidden="true"></span>
        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-white md:text-2xl">Selamat Datang, Bapak/Ibu Guru!</h1>
                <p class="mt-1 text-sm text-slate-300">Halaman Dashboard Petugas Piket · {{ date('l, d F Y') }}</p>
            </div>
            <div class="shrink-0 text-left sm:text-right">
                <div class="font-mono text-3xl font-extrabold tracking-tight text-white/60" x-text="waktu">00:00:00</div>
                <div class="mt-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Waktu server lokal</div>
            </div>
        </div>
    </div>
</div>

<div class="mt-5 space-y-6 px-4 pb-6 md:px-6">

    {{-- Statistik --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($kartuStatistik as $kartu)
            <div class="relative overflow-hidden rounded-2xl bg-linear-to-br {{ $kartu['gradien'] }} p-4 text-white shadow-md">
                <i class="bi {{ $kartu['icon'] }} pointer-events-none absolute -bottom-3 -right-3 text-6xl rotate-[-15deg] text-white/15" aria-hidden="true"></i>
                <div class="relative">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-white/75">{{ $kartu['label'] }}</div>
                    <div class="mt-2 flex items-baseline gap-1.5">
                        <span class="text-2xl font-extrabold leading-none">{{ $kartu['nilai'] }}</span>
                        <span class="text-xs font-medium text-white/70">{{ $kartu['satuan'] }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Layanan utama --}}
    <div>
        <h2 class="mb-3 flex items-center gap-2 px-1 text-sm font-bold text-slate-800">
            <i class="bi bi-lightning-charge-fill text-amber-500"></i> Layanan Utama Piket
        </h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @foreach ($layanan as $item)
                <a href="{{ url($item['url']) }}"
                    class="group flex flex-col items-center rounded-3xl border-2 border-dashed border-slate-200 bg-white p-5 text-center no-underline transition-all hover:-translate-y-0.5 hover:shadow-xl {{ $item['hover'] }}">
                    <span class="mb-3 flex h-16 w-16 items-center justify-center rounded-2xl text-3xl transition-colors {{ $item['warna'] }} {{ $item['hoverIcon'] }} group-hover:text-white">
                        <i class="bi {{ $item['icon'] }}"></i>
                    </span>
                    <span class="text-sm font-bold text-slate-800">{{ $item['judul'] }}</span>
                    <span class="mt-1 text-xs leading-relaxed text-slate-500">{{ $item['teks'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Aktivitas mengajar terbaru --}}
    <x-ui.card title="Aktivitas Mengajar Terbaru" icon="bi-clock-history" tone="blue" bodyClass="p-0">
        <x-slot:actions>
            <a href="{{ url('guru/piket/laporan') }}#seksi-detail"
                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-bold text-blue-600 no-underline transition-colors hover:bg-blue-50">
                Lihat Semua <i class="bi bi-arrow-right"></i>
            </a>
        </x-slot:actions>

        <x-ui.table>
            <x-slot:head>
                <th class="w-20 px-4 py-3">Jam</th>
                <th class="px-4 py-3">Nama Guru</th>
                <th class="px-4 py-3">Mata Pelajaran</th>
                <th class="w-32 px-3 py-3">Kelas</th>
                <th class="w-40 px-4 py-3 text-center">Siswa Absent</th>
            </x-slot:head>

            @forelse ($jurnal_terbaru as $j)
                <tr class="transition-colors hover:bg-slate-50/60">
                    <td class="px-4 py-2.5 font-bold text-slate-700">{{ $j->jam_ke }}</td>
                    <td class="px-4 py-2.5 font-bold text-blue-600">{{ $j->nama_guru }}</td>
                    <td class="px-4 py-2.5 text-slate-500">{{ $j->nama_mapel }}</td>
                    <td class="px-3 py-2.5">
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600">{{ $j->nama_kelas }}</span>
                    </td>
                    <td class="px-4 py-2.5 text-center">
                        @if (! \App\Models\PiketModel::parseSiswaAbsent($j->siswa_absent ?? null)['nihil'])
                            <span class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-0.5 text-[11px] font-bold text-rose-700">
                                <i class="bi bi-person-x"></i> Ada Siswa
                            </span>
                        @else
                            <span class="text-[11px] text-slate-400">- Nihil -</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center">
                        <i class="bi bi-journal-x mb-2 block text-4xl text-slate-200"></i>
                        <span class="text-xs text-slate-500">Belum ada aktivitas mengajar hari ini.</span>
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>
</div>
@endsection
