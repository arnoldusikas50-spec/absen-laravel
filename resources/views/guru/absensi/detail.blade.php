@extends('layouts.main')

@section('content')

<?php
$fotoMasuk  = $absensi->foto_masuk ?? null;
$fotoPulang = $absensi->foto_pulang ?? null;

$pathMasuk = $fotoMasuk ? 'uploads/absensi/' . $fotoMasuk : null;
$pathPulang = $fotoPulang ? 'uploads/absensi/' . $fotoPulang : null;

$verMasuk = $pathMasuk && file_exists(public_path() . '/' . $pathMasuk) ? filemtime(public_path() . '/' . $pathMasuk) : time();
$verPulang = $pathPulang && file_exists(public_path() . '/' . $pathPulang) ? filemtime(public_path() . '/' . $pathPulang) : time();

$hasMasuk = isset($absensi->lat_masuk, $absensi->lng_masuk) && $absensi->lat_masuk !== null && $absensi->lng_masuk !== null;
$hasPulang = isset($absensi->lat_pulang, $absensi->lng_pulang) && $absensi->lat_pulang !== null && $absensi->lng_pulang !== null;

$markers = [];
if ($hasMasuk) {
    $markers[] = [
        'lat' => (float) $absensi->lat_masuk,
        'lng' => (float) $absensi->lng_masuk,
        'judul' => 'Lokasi Absen Masuk',
        'jam' => (string) ($absensi->jam_masuk ?? ''),
        'buka' => true,
    ];
}
if ($hasPulang) {
    $markers[] = [
        'lat' => (float) $absensi->lat_pulang,
        'lng' => (float) $absensi->lng_pulang,
        'judul' => 'Lokasi Absen Pulang',
        'jam' => (string) ($absensi->jam_pulang ?? ''),
        'buka' => false,
    ];
}

$infoAbsensi = [
    ['label' => 'Nama Guru', 'nilai' => $guru->nama_guru ?? '-'],
    ['label' => 'Tanggal', 'nilai' => date('l, d F Y', strtotime($absensi->tgl ?? ''))],
];
?>

<div class="px-4 pt-4 md:px-6 md:pt-6">
    <x-ui.page-header
        :title="$judul"
        :subtitle="$subjudul"
        icon="bi-calendar-check-fill"
        tone="blue"
        :crumbs="['Absensi', 'Detail']">
        <x-slot:actions>
            <a href="{{ url('guru/absensi') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </x-slot:actions>
    </x-ui.page-header>
</div>

<div class="mt-4 grid gap-5 px-4 pb-6 md:px-6 lg:grid-cols-3">

    <div class="space-y-5 lg:col-span-2">
        <x-ui.card title="Informasi Absensi" icon="bi-info-circle-fill" tone="blue" bodyClass="p-0">
            <dl class="divide-y divide-slate-100 text-sm">
                @foreach ($infoAbsensi as $info)
                    <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:items-center sm:gap-4">
                        <dt class="w-44 shrink-0 text-xs font-bold uppercase tracking-wider text-slate-400">{{ $info['label'] }}</dt>
                        <dd class="font-semibold text-slate-800">{{ $info['nilai'] }}</dd>
                    </div>
                @endforeach

                <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:items-center sm:gap-4">
                    <dt class="w-44 shrink-0 text-xs font-bold uppercase tracking-wider text-slate-400">Jam Masuk</dt>
                    <dd>
                        @if (isset($absensi->jam_masuk))
                            <span class="inline-flex items-center gap-1 rounded-full border border-sky-200 bg-sky-50 px-2.5 py-0.5 font-mono text-sm font-bold text-sky-700">
                                <i class="bi bi-box-arrow-in-right text-xs"></i>{{ $absensi->jam_masuk }}
                            </span>
                        @else
                            <span class="text-xs text-slate-400">Belum absen masuk</span>
                        @endif
                    </dd>
                </div>

                <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:items-center sm:gap-4">
                    <dt class="w-44 shrink-0 text-xs font-bold uppercase tracking-wider text-slate-400">Jam Pulang</dt>
                    <dd>
                        @if (isset($absensi->jam_pulang))
                            <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 font-mono text-sm font-bold text-slate-600">
                                <i class="bi bi-box-arrow-right text-xs"></i>{{ $absensi->jam_pulang }}
                            </span>
                        @else
                            <span class="text-xs text-slate-400">Belum absen pulang</span>
                        @endif
                    </dd>
                </div>

                <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:items-center sm:gap-4">
                    <dt class="w-44 shrink-0 text-xs font-bold uppercase tracking-wider text-slate-400">Status Masuk</dt>
                    <dd class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700">
                            {{ ucfirst($absensi->status_masuk ?? '-') }}
                        </span>
                        @if (isset($absensi->telat_menit) && $absensi->telat_menit > 0)
                            <span class="text-xs font-semibold text-rose-600">(Telat {{ $absensi->telat_menit }} menit)</span>
                        @endif
                    </dd>
                </div>

                <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:items-start sm:gap-4">
                    <dt class="w-44 shrink-0 text-xs font-bold uppercase tracking-wider text-slate-400">Info Perangkat</dt>
                    <dd class="text-xs leading-relaxed text-slate-500">{{ $absensi->device_info ?? '-' }}</dd>
                </div>

                @if ($hasMasuk)
                    <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:items-center sm:gap-4">
                        <dt class="w-44 shrink-0 text-xs font-bold uppercase tracking-wider text-slate-400">Koordinat Masuk</dt>
                        <dd>
                            <a href="https://www.google.com/maps?q={{ $absensi->lat_masuk }},{{ $absensi->lng_masuk }}" target="_blank"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 no-underline hover:underline">
                                <i class="bi bi-geo-alt-fill text-rose-500"></i>
                                {{ $absensi->lat_masuk }}, {{ $absensi->lng_masuk }}
                            </a>
                        </dd>
                    </div>
                @endif

                @if ($hasPulang)
                    <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:items-center sm:gap-4">
                        <dt class="w-44 shrink-0 text-xs font-bold uppercase tracking-wider text-slate-400">Koordinat Pulang</dt>
                        <dd>
                            <a href="https://www.google.com/maps?q={{ $absensi->lat_pulang }},{{ $absensi->lng_pulang }}" target="_blank"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 no-underline hover:underline">
                                <i class="bi bi-geo-alt-fill text-rose-500"></i>
                                {{ $absensi->lat_pulang }}, {{ $absensi->lng_pulang }}
                            </a>
                        </dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>

        @if (! empty($markers))
            <x-ui.card title="Peta Lokasi" icon="bi-map-fill" tone="blue" bodyClass="p-0">
                <div x-data="petaAbsensi({{ json_encode($markers) }})" class="h-[400px] w-full">
                    <div x-ref="peta" class="h-full w-full"></div>
                </div>
            </x-ui.card>
        @endif
    </div>

    <div class="space-y-5">
        @if ($pathMasuk && file_exists(public_path() . '/' . $pathMasuk))
            <x-ui.card title="Foto Absen Masuk" icon="bi-camera-fill" tone="blue" bodyClass="p-0">
                <img src="{{ url($pathMasuk) }}?v={{ $verMasuk }}" alt="Foto absen masuk"
                    class="max-h-[400px] w-full object-cover" loading="lazy">
            </x-ui.card>
        @endif

        @if ($pathPulang && file_exists(public_path() . '/' . $pathPulang))
            <x-ui.card title="Foto Absen Pulang" icon="bi-camera-fill" tone="slate" bodyClass="p-0">
                <img src="{{ url($pathPulang) }}?v={{ $verPulang }}" alt="Foto absen pulang"
                    class="max-h-[400px] w-full object-cover" loading="lazy">
            </x-ui.card>
        @endif
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    /* Leaflet default z-index menutupi navbar shell admin. */
    .leaflet-pane { z-index: 1 !important; }
    .leaflet-top, .leaflet-bottom { z-index: 2 !important; }
</style>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('petaAbsensi', (markers) => ({
        peta: null,

        init() {
            if (!window.L || !markers.length) {
                return;
            }

            const pertama = markers[0];
            this.peta = L.map(this.$refs.peta).setView([pertama.lat, pertama.lng], 15);

            // Esri World Street Map — alih-alih tile.openstreetmap.org (dibanned
            // OSM) dan cartocdn (menyuntik watermark "API KEY REQUIRED" di tile
            // anonim, kasus Nov 2026). Jalur legacy Esri masih bebas-key.
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
                attribution: '&copy; <a href="https://www.esri.com/">Esri</a> — sumber data: Esri, Maxar, Garmin, <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(this.peta);

            const penanda = markers.map((titik) => {
                const m = L.marker([titik.lat, titik.lng]).addTo(this.peta);
                m.bindPopup('<b>' + titik.judul + '</b><br>' + titik.jam);

                if (titik.buka) {
                    m.openPopup();
                }

                return m;
            });

            if (penanda.length > 1) {
                this.peta.fitBounds(new L.featureGroup(penanda).getBounds().pad(0.1));
            }
        },
    }));
});
</script>
@endsection
