@extends('layouts.main')

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    /* Custom Leaflet SVG Pins */
    .custom-leaflet-pin { display: flex; align-items: center; justify-content: center; position: relative; }

    .pin-pulse-ring {
        position: absolute; width: 44px; height: 44px; border-radius: 50%;
        animation: pin-pulse 2s infinite ease-out; opacity: 0.7; pointer-events: none;
    }

    .pin-blue .pin-pulse-ring { background: rgb(var(--primary-500-rgb) / 0.35); }
    .pin-red .pin-pulse-ring { background: rgba(239, 68, 68, 0.45); }

    .pin-body {
        width: 34px; height: 34px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg);
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.28); border: 2px solid #ffffff;
        transition: transform 0.2s ease;
    }

    .custom-leaflet-pin:hover .pin-body { transform: rotate(-45deg) scale(1.15); }
    .pin-body i { transform: rotate(45deg); color: #ffffff; font-size: 15px; }
    .pin-blue .pin-body { background: linear-gradient(135deg, var(--primary-600), var(--primary-700)); }
    .pin-red .pin-body { background: linear-gradient(135deg, #ef4444, #b91c1c); }

    @keyframes pin-pulse {
        0% { transform: scale(0.6); opacity: 0.9; }
        100% { transform: scale(1.8); opacity: 0; }
    }

    .resize-handle-pin {
        width: 16px; height: 16px; background: #fff; border: 3px solid var(--primary-600);
        border-radius: 50%; box-shadow: 0 2px 6px rgba(0,0,0,0.3); cursor: ew-resize;
    }

    .leaflet-marker-dragging .pin-body { transform: rotate(-45deg) scale(1.2); }

    /* Range slider */
    .radius-range-slider {
        -webkit-appearance: none; appearance: none; width: 100%; height: 6px;
        border-radius: 5px; background: #e2e8f0; outline: none;
    }

    .radius-range-slider::-webkit-slider-thumb {
        -webkit-appearance: none; appearance: none; width: 18px; height: 18px;
        border-radius: 50%; background: var(--primary-600); cursor: pointer;
        box-shadow: 0 2px 6px rgb(var(--primary-600-rgb) / 0.4); transition: transform 0.15s ease;
    }

    .radius-range-slider::-webkit-slider-thumb:hover { transform: scale(1.2); }
</style>
@endsection

@section('content')

@php
    $locationsData = collect($locations ?? [])->map(fn ($l) => [
        'id' => (int) $l->id,
        'nama_lokasi' => $l->nama_lokasi,
        'lat' => (float) $l->lat,
        'lng' => (float) $l->lng,
        'radius_meter' => (int) ($l->radius_meter ?? 50),
        'keterangan' => $l->keterangan ?? '',
        'is_utama' => (int) ($l->is_utama ?? 0),
    ])->values()->all();

    $pageConfig = [
        'csrf' => csrf_token(),
        'urls' => [
            'save' => url('admin/presensi/lokasi/save'),
            'utama' => url('admin/presensi/lokasi/utama'),
            'delete' => url('admin/presensi/lokasi/delete'),
        ],
        'locations' => $locationsData,
        'defaults' => [
            'lat' => -6.175392,
            'lng' => 106.827153,
            'radius' => 50,
        ],
    ];
@endphp

<div x-data="lokasiPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
    class="px-4 pb-6 pt-4 md:px-6 md:pt-6">

    <x-ui.page-header
        :title="$judul ?? 'Lokasi Kantor & Geofence'"
        icon="bi-geo-alt-fill"
        tone="blue"
        subtitle="Kelola koordinat GPS dan radius batas presensi kehadiran GTK."
        :crumbs="['Presensi', 'Lokasi']">
        <x-slot:actions>
            <button type="button" @click="locateCurrentGPS()"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-50"
                title="Pusatkan ke posisi GPS browser Anda">
                <i class="bi bi-crosshair text-blue-600"></i> Lokasi Saya
            </button>
            <button type="button" @click="fitAllMarkers()"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-50"
                title="Fokuskan ke seluruh titik lokasi">
                <i class="bi bi-arrows-angle-contract"></i>
            </button>
            <button type="button" @click="addLocation()"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                <i class="bi bi-plus-lg"></i> Tambah Lokasi
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mt-4 grid grid-cols-1 items-start gap-5 lg:grid-cols-12">

        <!-- Peta -->
        <div class="lg:col-span-7 xl:col-span-8">
            <x-ui.card title="Peta Geofence Interaktif" icon="bi-map-fill" bodyClass="p-0"
                subtitle="Visualisasi lingkaran batas absensi GPS">

                <div class="relative">
                    <!-- Pencarian lokasi -->
                    <div class="absolute left-3.5 top-3.5 z-[999] w-[calc(100%-130px)] max-w-[380px]">
                        <div class="relative rounded-xl shadow-md">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="bi bi-search"></i>
                            </div>
                            <input type="text" x-model="searchQuery" @keydown.enter.prevent="searchLocation()"
                                placeholder="Cari gedung, jalan, kota..." autocomplete="off"
                                class="w-full rounded-xl border border-slate-200 bg-white/95 py-2.5 pl-10 pr-20 text-xs text-slate-800 backdrop-blur transition placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 sm:text-sm">
                            <div class="absolute inset-y-0 right-1 flex items-center gap-1">
                                <button type="button" x-show="searchQuery !== ''" x-cloak @click="clearMapSearch()"
                                    class="p-1 text-xs text-slate-400 transition-colors hover:text-slate-600" title="Bersihkan pencarian">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                                <button type="button" @click="searchLocation()" :disabled="searching"
                                    class="mr-1 inline-flex items-center gap-1 rounded-lg bg-blue-600 px-2.5 py-1.5 text-xs font-bold text-white transition-colors hover:bg-blue-700 disabled:opacity-60">
                                    <span x-show="searching" x-cloak class="inline-block h-3 w-3 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                                    <span x-show="!searching">Cari</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Kontrol peta -->
                    <div class="absolute right-3.5 top-3.5 z-[999] flex flex-col gap-2">
                        <div class="relative" x-on:click.outside="layerOpen = false">
                            <button type="button" @click="layerOpen = !layerOpen" title="Ganti Tipe Peta"
                                :class="layerKey.includes('hybrid') ? 'border-emerald-300 bg-emerald-50 text-emerald-600' : 'border-slate-200 bg-white text-slate-700'"
                                class="flex h-[38px] w-[38px] items-center justify-center rounded-lg border shadow-md transition hover:-translate-y-px">
                                <i class="bi bi-layers text-base"></i>
                            </button>

                            <div x-show="layerOpen" x-cloak x-transition
                                class="absolute right-0 top-11 z-[1000] w-64 rounded-2xl border border-slate-200/90 bg-white py-2 text-xs shadow-2xl">
                                <div class="flex items-center justify-between border-b border-slate-100 px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                                    <span>Pilihan Tipe Peta</span>
                                    <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-600">Bebas Blokir</span>
                                </div>
                                <template x-for="layer in layers" :key="layer.key">
                                    <button type="button" @click="selectMapLayer(layer.key)"
                                        class="flex w-full items-center justify-between px-3.5 py-2.5 text-left font-semibold text-slate-700 transition hover:bg-slate-50">
                                        <span class="flex items-center gap-2.5">
                                            <i class="bi text-sm" :class="layer.icon + ' ' + layer.tone"></i>
                                            <span x-text="layer.label"></span>
                                        </span>
                                        <i class="bi bi-check-circle-fill text-sm text-blue-600" x-show="layerKey === layer.key"></i>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <button type="button" @click="$store.modals.open('#modalGeofenceGuide')" title="Panduan & Rekomendasi Radius Geofence"
                            class="flex h-[38px] w-[38px] items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-md transition hover:-translate-y-px hover:bg-slate-50">
                            <i class="bi bi-question-circle text-base"></i>
                        </button>
                    </div>

                    <!-- Leaflet -->
                    <div id="map" class="h-[420px] w-full bg-slate-100 md:h-[600px]"></div>

                    <!-- Toast autosave -->
                    <div x-show="toast.show" x-cloak x-transition
                        :class="toast.mode === 'success' ? 'bg-emerald-600/95' : (toast.mode === 'error' ? 'bg-red-600/95' : 'bg-slate-900/90')"
                        class="absolute left-1/2 top-3.5 z-[1000] flex -translate-x-1/2 items-center gap-2 whitespace-nowrap rounded-full px-4 py-2 text-xs font-semibold text-white shadow-xl">
                        <i class="bi" :class="toast.mode === 'error' ? 'bi-exclamation-triangle' : (toast.mode === 'success' ? 'bi-check2-circle' : 'bi-arrow-repeat')"></i>
                        <span x-text="toast.text"></span>
                    </div>

                    <!-- Petunjuk -->
                    <div class="absolute bottom-4 left-4 z-[999] flex max-w-[calc(100%-2rem)] items-center gap-1.5 rounded-full bg-slate-900/90 px-4 py-1.5 text-[11px] font-medium text-white shadow-lg backdrop-blur">
                        <i class="bi bi-info-circle shrink-0 text-sm text-cyan-300"></i>
                        <span><b>Seret pin biru</b> untuk pindah &amp; tersimpan otomatis | Seret <b>titik putih</b> di tepi lingkaran untuk ubah radius | Klik peta untuk titik baru</span>
                    </div>
                </div>

                <x-slot:footer>
                    <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600">
                        <div class="flex flex-wrap items-center gap-4">
                            <span class="inline-flex items-center gap-1.5 font-medium"><span class="inline-block h-3 w-3 rounded-full bg-blue-600 shadow-xs"></span> Titik Terdaftar</span>
                            <span class="inline-flex items-center gap-1.5 font-medium"><span class="inline-block h-3 w-3 rounded-full bg-red-500 shadow-xs"></span> Titik Baru / Mode Edit</span>
                            <span class="inline-flex items-center gap-1.5 font-medium"><span class="inline-block h-3 w-3 rounded-full border border-blue-400 bg-blue-200"></span> Radius Geofence</span>
                        </div>
                        <div class="flex items-center gap-1 text-slate-500">
                            <i class="bi bi-shield-lock text-emerald-600"></i>
                            <span>Guru hanya valid absen di dalam lingkaran radius.</span>
                        </div>
                    </div>
                </x-slot:footer>
            </x-ui.card>
        </div>

        <!-- Daftar lokasi -->
        <div class="lg:col-span-5 xl:col-span-4">
            <x-ui.card title="Daftar Titik Lokasi" icon="bi-geo-alt" subtitle="Klik kartu untuk fokus ke peta"
                bodyClass="p-3">
                <x-slot:meta>
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-bold text-blue-700"
                        x-text="filteredLocations.length + ' Titik'"></span>
                </x-slot:meta>

                <x-slot:toolbar>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="bi bi-funnel text-xs"></i>
                        </div>
                        <input type="text" x-model="filterQuery" placeholder="Filter nama lokasi atau keterangan..." autocomplete="off"
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-8 pr-8 text-xs text-slate-800 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="button" x-show="filterQuery !== ''" x-cloak @click="filterQuery = ''"
                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-xs text-slate-400 hover:text-slate-600">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </x-slot:toolbar>

                <div class="max-h-[575px] space-y-2.5 overflow-y-auto">
                    <template x-if="locations.length === 0">
                        <div class="p-8 text-center">
                            <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-blue-50 text-2xl text-blue-500 shadow-inner">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <h6 class="mb-1 text-sm font-bold text-slate-700">Belum Ada Titik Lokasi</h6>
                            <p class="mx-auto mb-4 max-w-xs text-xs leading-relaxed text-slate-400">
                                Tambahkan titik lokasi gedung sekolah agar sistem geofence presensi dapat bekerja.
                            </p>
                            <button type="button" @click="addLocation()"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3 py-1.5 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                                <i class="bi bi-plus-lg"></i> Tambah Titik Pertama
                            </button>
                        </div>
                    </template>

                    <template x-for="loc in filteredLocations" :key="loc.id">
                        <div @click="focusLocation(loc)"
                            class="cursor-pointer rounded-xl border border-slate-200/80 border-l-4 bg-white p-3.5 transition-all hover:shadow-md"
                            :class="activeId === loc.id ? 'border-l-blue-600 bg-blue-50 shadow-sm' : 'border-l-transparent'">
                            <div class="flex items-start justify-between gap-2.5">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5 text-sm font-bold leading-snug text-slate-800">
                                        <i class="bi bi-building text-xs text-blue-600"></i>
                                        <span class="truncate" x-text="loc.nama_lokasi"></span>
                                    </div>

                                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                        <span x-show="loc.is_utama === 1" class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800">
                                            <i class="bi bi-star-fill text-[9px]"></i> Lokasi Utama
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700">
                                            <i class="bi bi-bullseye text-[9px]"></i> <span x-text="loc.radius_meter + 'm'"></span>
                                        </span>
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 font-mono text-[10px] text-slate-600"
                                            x-text="coordLabel(loc)"></span>
                                    </div>

                                    <p x-show="loc.keterangan" x-cloak class="mt-1.5 line-clamp-2 text-[11px] leading-relaxed text-slate-500" x-text="loc.keterangan"></p>
                                </div>

                                <div class="flex shrink-0 items-center gap-1">
                                    <button type="button" @click.stop="toggleUtama(loc)"
                                        :class="loc.is_utama === 1 ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-400 hover:bg-amber-50 hover:text-amber-600'"
                                        :title="loc.is_utama === 1 ? 'Lepas penandaan lokasi utama' : 'Jadikan lokasi utama'"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-xs transition">
                                        <i class="bi" :class="loc.is_utama === 1 ? 'bi-star-fill' : 'bi-star'"></i>
                                    </button>
                                    <a :href="mapsUrl(loc)" target="_blank" rel="noopener noreferrer"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-500 transition hover:bg-blue-50 hover:text-blue-600"
                                        title="Buka di Google Maps">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                    <button type="button" @click.stop="editLocation(loc)" title="Edit Titik Lokasi"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-500 transition hover:bg-blue-50 hover:text-blue-600">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" @click.stop="deleteLocation(loc)" title="Hapus Titik Lokasi"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-500 transition hover:bg-red-50 hover:text-red-600">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div x-show="locations.length > 0 && filteredLocations.length === 0" x-cloak class="rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-6 py-10 text-center">
                        <i class="bi bi-search mb-2 block text-3xl text-slate-300"></i>
                        <p class="text-sm font-semibold text-slate-500">Tidak ada lokasi yang cocok</p>
                        <button type="button" @click="filterQuery = ''" class="mt-2 text-xs text-blue-500 hover:underline">Reset Filter</button>
                    </div>
                </div>

                <x-slot:footer>
                    <div class="text-center text-xs text-slate-400">
                        Presensi otomatis mencocokkan ke titik terdekat dalam radius.
                    </div>
                </x-slot:footer>
            </x-ui.card>
        </div>
    </div>

    <!-- Modal form lokasi -->
    <x-ui.modal id="modalLocation" size="max-w-lg">
        <x-slot:header>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-lg text-blue-600">
                        <i class="bi bi-geo-alt-fill"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="truncate text-sm font-bold text-slate-800" x-text="form.id ? 'Edit Lokasi Kantor' : 'Tambah Titik Lokasi Baru'"></h2>
                        <p class="truncate text-xs text-slate-400" x-text="form.id ? 'Perbarui nama gedung, koordinat, atau radius geofence' : 'Tentukan koordinat GPS dan batas radius presensi'"></p>
                    </div>
                </div>
                <button type="button" @click="closeModal()" aria-label="Tutup"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </x-slot:header>

        <form x-ref="locationForm" @submit.prevent="saveLocation()" class="flex min-h-0 flex-col">
            @csrf
            <div class="space-y-4 overflow-y-auto p-5">
                <div>
                    <label for="nama_lokasi" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Nama Gedung / Lokasi <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="bi bi-building"></i>
                        </div>
                        <input type="text" id="nama_lokasi" x-model="form.nama_lokasi" name="nama_lokasi" required
                            placeholder="Contoh: Gedung Utama SMAN 1 / Kampus A..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-3.5 text-sm outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>

                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-700">
                            Koordinat GPS <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[11px] text-slate-400">Geser pin merah di peta untuk mengubah</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="lat" class="mb-1 block text-[11px] text-slate-500">Latitude (Garis Lintang)</label>
                            <input type="text" id="lat" x-model="form.lat" name="lat" required readonly
                                class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2 font-mono text-xs text-slate-700">
                        </div>
                        <div>
                            <label for="lng" class="mb-1 block text-[11px] text-slate-500">Longitude (Garis Bujur)</label>
                            <input type="text" id="lng" x-model="form.lng" name="lng" required readonly
                                class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2 font-mono text-xs text-slate-700">
                        </div>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-2">
                        <button type="button" @click="useCurrentCenterMap()"
                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600 transition-colors hover:bg-slate-50">
                            <i class="bi bi-bullseye text-blue-600"></i> Ambil Titik Tengah Peta
                        </button>
                        <button type="button" @click="locateCurrentGPS()"
                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600 transition-colors hover:bg-slate-50">
                            <i class="bi bi-crosshair text-emerald-600"></i> Deteksi GPS Saya
                        </button>
                    </div>
                </div>

                <div class="space-y-3 rounded-xl border border-slate-200/80 bg-slate-50 p-3.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-xs font-bold uppercase leading-tight tracking-wider text-slate-700">Radius Toleransi Presensi</label>
                            <span class="text-[11px] text-slate-400">Jarak lingkaran di mana guru diperbolehkan absen</span>
                        </div>
                        <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-700" x-text="radiusLabel"></span>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="range" min="10" max="1000" step="5" x-model.number="form.radius_meter"
                            @input="onRadiusRange()"
                            class="radius-range-slider flex-1">
                        <div class="relative w-24 shrink-0">
                            <input type="number" id="radius_meter" name="radius_meter" x-model.number="form.radius_meter"
                                min="10" max="5000" required @input="onRadiusInput()"
                                class="w-full rounded-xl border border-slate-200 bg-white py-1.5 pr-7 text-center text-xs font-bold text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-[11px] text-slate-400">m</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                        <span class="mr-1 text-[11px] font-medium text-slate-400">Preset:</span>
                        <button type="button" @click="setRadiusPreset(25)"
                            class="rounded-lg border border-slate-200 bg-white px-2 py-0.5 text-[11px] text-slate-600 transition-colors hover:bg-slate-100">25m</button>
                        <button type="button" @click="setRadiusPreset(50)"
                            class="rounded-lg border border-blue-200 bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700 transition-colors hover:bg-blue-100">50m (Standar)</button>
                        <button type="button" @click="setRadiusPreset(100)"
                            class="rounded-lg border border-slate-200 bg-white px-2 py-0.5 text-[11px] text-slate-600 transition-colors hover:bg-slate-100">100m (Kampus)</button>
                        <button type="button" @click="setRadiusPreset(200)"
                            class="rounded-lg border border-slate-200 bg-white px-2 py-0.5 text-[11px] text-slate-600 transition-colors hover:bg-slate-100">200m (Luas)</button>
                        <button type="button" @click="setRadiusPreset(500)"
                            class="rounded-lg border border-slate-200 bg-white px-2 py-0.5 text-[11px] text-slate-600 transition-colors hover:bg-slate-100">500m (Kawasan)</button>
                    </div>
                </div>

                <div>
                    <label for="keterangan" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Keterangan / Catatan Tambahan (Opsional)
                    </label>
                    <textarea id="keterangan" name="keterangan" x-model="form.keterangan" rows="2"
                        placeholder="Contoh: Gedung utama, lapangan upacara, dan area parkir guru"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs outline-hidden transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"></textarea>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3.5">
                    <label class="flex cursor-pointer items-start gap-2.5">
                        <input type="checkbox" id="is_utama" name="is_utama" value="1" x-model="form.is_utama"
                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-400">
                        <span>
                            <span class="block text-xs font-bold text-amber-900">Jadikan sebagai Lokasi Utama</span>
                            <span class="mt-0.5 block text-[11px] leading-relaxed text-amber-800/90">
                                Hanya satu lokasi yang bisa menjadi utama. Lokasi ini dipakai otomatis sebagai
                                koordinat saat admin mengisi absen manual, karena input manual tidak membawa GPS.
                            </span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="closeModal()"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="saving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                    <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!saving" class="bi bi-save"></i> Simpan Titik Lokasi
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal panduan -->
    <x-ui.modal id="modalGeofenceGuide" title="Panduan Geofence Presensi" icon="bi-question-circle-fill" tone="blue" size="max-w-lg">
        <div class="space-y-4 overflow-y-auto p-5 text-xs leading-relaxed text-slate-600">
            <div class="rounded-xl border border-blue-200/70 bg-blue-50/70 p-3.5">
                <h6 class="mb-1 flex items-center gap-1.5 text-xs font-bold text-blue-800">
                    <i class="bi bi-shield-check"></i> Apa itu Geofence Presensi?
                </h6>
                <p class="text-blue-900/80">
                    Geofence adalah pembatas geografis virtual berbentuk lingkaran radius di sekitar titik koordinat kantor/sekolah. Saat GTK melakukan presensi, browser mengirimkan koordinat GPS perangkat mereka.
                </p>
            </div>

            <div class="space-y-2">
                <h6 class="text-xs font-bold text-slate-800">Rekomendasi Penentuan Radius:</h6>
                <ul class="list-disc space-y-1.5 pl-4 text-slate-600">
                    <li><strong>50 – 100 Meter (Sangat Dianjurkan)</strong>: Mengakomodasi ketidakakuratan GPS smartphone saat berada di dalam ruangan/gedung beton bertingkat (GPS drift 10–25m).</li>
                    <li><strong>25 Meter</strong>: Hanya cocok untuk titik kecil atau presensi di ruang terbuka (lapangan/pos satpam).</li>
                    <li><strong>150 – 300 Meter</strong>: Cocok untuk kompleks sekolah atau kampus luas dengan banyak fakultas atau area parkir terpisah.</li>
                </ul>
            </div>

            <div class="rounded-xl border border-amber-200/70 bg-amber-50/70 p-3.5">
                <h6 class="mb-1 flex items-center gap-1.5 text-xs font-bold text-amber-800">
                    <i class="bi bi-exclamation-triangle"></i> Pengecualian Validasi:
                </h6>
                <p class="text-amber-900/80">
                    GTK yang ditugaskan <strong>Dinas Luar</strong> atau bekerja dalam mode <strong>WFH (Work From Home)</strong> otomatis dikecualikan dari pengecekan geofence radius ini.
                </p>
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-100 bg-slate-50 px-5 py-3">
            <button type="button" @click="$store.modals.close('#modalGeofenceGuide')"
                class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700">Mengerti</button>
        </div>
    </x-ui.modal>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
document.addEventListener('alpine:init', () => {
    const state = {
        map: null,
        locationLayers: {},
        tempMarker: null,
        tempCircle: null,
        autoSaveTimers: {},
        toastTimer: null,
        bluePin: null,
        redPin: null,
        baseLayers: {},
        currentLayer: null,
    };

    Alpine.data('lokasiPage', () => ({
        config: { csrf: '', urls: {}, locations: [], defaults: { lat: -6.175392, lng: 106.827153, radius: 50 } },

        locations: [],
        filterQuery: '',
        activeId: null,
        layerOpen: false,
        layerKey: 'google_street',
        searchQuery: '',
        searching: false,
        modalOpen: false,
        saving: false,

        toast: { show: false, text: '', mode: '' },

        layers: [
            { key: 'google_street', label: 'Google Maps (Jalan)', icon: 'bi-map-fill', tone: 'text-blue-600' },
            { key: 'google_hybrid', label: 'Google Satelit (Hybrid)', icon: 'bi-globe-americas', tone: 'text-emerald-600' },
            { key: 'esri_street', label: 'Esri World Street', icon: 'bi-compass', tone: 'text-amber-600' },
            { key: 'osm_hot', label: 'OpenStreetMap (HOT)', icon: 'bi-pin-map', tone: 'text-rose-600' },
        ],

        form: { id: '', nama_lokasi: '', lat: '', lng: '', radius_meter: 50, keterangan: '', is_utama: false },

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');

            this.config = Object.assign({}, this.config, cfg);
            this.config.urls = cfg.urls || {};
            this.config.defaults = Object.assign({}, this.config.defaults, cfg.defaults || {});
            this.locations = cfg.locations || [];

            try {
                const saved = localStorage.getItem('presensi_admin_map_layer');

                if (saved) this.layerKey = saved;
            } catch (error) {
                /* penyimpanan lokal opsional */
            }

            if (!this.layers.some((layer) => layer.key === this.layerKey)) {
                this.layerKey = 'google_street';
            }

            this.$nextTick(() => this.initMap());

            window.addEventListener('resize', () => {
                if (state.map) state.map.invalidateSize();
            });
        },

        get filteredLocations() {
            const query = this.filterQuery.trim().toLowerCase();

            if (query === '') return this.locations;

            return this.locations.filter((loc) => {
                return (loc.nama_lokasi || '').toLowerCase().includes(query)
                    || (loc.keterangan || '').toLowerCase().includes(query);
            });
        },

        get radiusLabel() {
            return this.form.radius_meter + ' Meter';
        },

        /* ── Leaflet ──────────────────────────────── */

        createPinIcon(colorClass) {
            return L.divIcon({
                className: 'custom-leaflet-pin ' + colorClass,
                html: '<div class="pin-pulse-ring"></div><div class="pin-body"><i class="bi bi-geo-alt-fill"></i></div>',
                iconSize: [34, 34],
                iconAnchor: [17, 34],
                popupAnchor: [0, -32],
            });
        },

        initMap() {
            const el = document.getElementById('map');

            if (!el || typeof L === 'undefined') return;

            state.bluePin = this.createPinIcon('pin-blue');
            state.redPin = this.createPinIcon('pin-red');

            state.baseLayers = {
                google_street: L.tileLayer('https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                    subdomains: ['0', '1', '2', '3'],
                    maxZoom: 20,
                    attribution: '&copy; Google Maps',
                }),
                google_hybrid: L.tileLayer('https://mt{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                    subdomains: ['0', '1', '2', '3'],
                    maxZoom: 20,
                    attribution: '&copy; Google Maps Satelit',
                }),
                esri_street: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19,
                    attribution: '&copy; Esri, Garmin, USGS',
                }),
                osm_hot: L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
                    subdomains: 'abc',
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors, Humanitarian OpenStreetMap Team',
                }),
            };

            Object.keys(state.baseLayers).forEach((key) => {
                state.baseLayers[key].on('tileerror', () => {
                    if (this.layerKey !== 'google_hybrid' && this.layerKey !== 'google_street') {
                        console.warn('Tile error on ' + this.layerKey + ', auto-fallback to Google Street');
                        this.selectMapLayer('google_street');
                    }
                });
            });

            state.currentLayer = state.baseLayers[this.layerKey];

            let center = [this.config.defaults.lat, this.config.defaults.lng];

            if (this.locations.length > 0) {
                center = [this.locations[0].lat, this.locations[0].lng];
            }

            state.map = L.map(el, {
                center,
                zoom: 15,
                layers: [state.currentLayer],
                zoomControl: false,
            });

            L.control.zoom({ position: 'bottomright' }).addTo(state.map);
            L.control.scale({ imperial: false, position: 'bottomright' }).addTo(state.map);

            this.renderAllLocations();

            state.map.on('click', (e) => {
                this.setTempMarker(e.latlng.lat, e.latlng.lng);
                this.openModalAdd(e.latlng.lat, e.latlng.lng);
            });

            if (this.locations.length > 1) {
                this.fitAllMarkers();
            }
        },

        selectMapLayer(key) {
            this.layerOpen = false;

            if (!state.baseLayers[key] || key === this.layerKey) return;

            if (state.currentLayer) {
                state.map.removeLayer(state.currentLayer);
            }

            state.currentLayer = state.baseLayers[key];
            state.map.addLayer(state.currentLayer);
            this.layerKey = key;

            try {
                localStorage.setItem('presensi_admin_map_layer', key);
            } catch (error) {
                /* penyimpanan lokal opsional */
            }
        },

        fitAllMarkers() {
            if (!state.map) return;

            if (this.locations.length > 0) {
                const bounds = L.latLngBounds(this.locations.map((loc) => [Number(loc.lat), Number(loc.lng)]));
                state.map.fitBounds(bounds, { padding: [60, 60] });
            } else {
                state.map.setView([this.config.defaults.lat, this.config.defaults.lng], 15);
            }
        },

        renderAllLocations() {
            Object.values(state.locationLayers).forEach((item) => {
                if (item.marker) state.map.removeLayer(item.marker);
                if (item.circle) state.map.removeLayer(item.circle);
                if (item.resizeHandle) state.map.removeLayer(item.resizeHandle);
            });

            state.locationLayers = {};

            this.locations.forEach((loc) => {
                const lat = Number(loc.lat);
                const lng = Number(loc.lng);
                const radius = Number(loc.radius_meter) || 50;

                const marker = L.marker([lat, lng], { icon: state.bluePin, draggable: true, autoPan: true }).addTo(state.map);
                const circle = L.circle([lat, lng], {
                    color: '#2563eb',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.16,
                    weight: 2,
                    radius,
                }).addTo(state.map);

                const resizeHandle = L.marker([lat, lng], {
                    icon: L.divIcon({ className: '', html: '<div class="resize-handle-pin"></div>', iconSize: [16, 16], iconAnchor: [8, 8] }),
                    draggable: true,
                    autoPan: true,
                }).addTo(state.map);

                const popup = this.buildPopup(loc);

                marker.bindPopup(popup.el, { maxWidth: 300 });
                marker.bindTooltip('Seret untuk pindahkan • tersimpan otomatis', { direction: 'top', opacity: 0.9 });

                state.locationLayers[loc.id] = { marker, circle, resizeHandle, popup };

                this.positionResizeHandle(loc.id);

                marker.on('drag', (e) => {
                    const pos = e.target.getLatLng();
                    circle.setLatLng(pos);
                    this.positionResizeHandle(loc.id);
                });

                marker.on('dragend', (e) => {
                    const pos = e.target.getLatLng();
                    circle.setLatLng(pos);
                    this.positionResizeHandle(loc.id);
                    this.refreshPopup(loc.id);
                    this.scheduleAutoSave(loc.id);
                });

                resizeHandle.on('drag', (e) => {
                    const c = circle.getLatLng();
                    const h = e.target.getLatLng();
                    let r = Math.round(state.map.distance(c, h));
                    r = Math.min(Math.max(r, 10), 5000);
                    circle.setRadius(r);
                    this.refreshPopup(loc.id);
                });

                resizeHandle.on('dragend', () => {
                    this.positionResizeHandle(loc.id);
                    this.refreshPopup(loc.id);
                    this.scheduleAutoSave(loc.id);
                });
            });
        },

        buildPopup(loc) {
            const el = document.createElement('div');
            el.className = 'p-1 min-w-[230px] font-sans';

            const coord = Number(loc.lat).toFixed(5) + ', ' + Number(loc.lng).toFixed(5);

            el.innerHTML = `
                <div class="flex items-center gap-1.5 mb-1 text-blue-600">
                    <i class="bi bi-building font-bold"></i>
                    <h6 class="font-bold text-slate-800 text-sm m-0 leading-tight">${this.escapeHtml(loc.nama_lokasi)}</h6>
                </div>
                <div class="flex items-center gap-1.5 my-1.5">
                    <span data-role="radius" class="inline-block bg-blue-100 text-blue-800 text-[10px] font-semibold px-2 py-0.5 rounded-full">
                        <i class="bi bi-bullseye"></i> Radius: ${Number(loc.radius_meter) || 50}m
                    </span>
                </div>
                <div data-role="coord" class="text-[11px] text-slate-500 font-mono mb-2 bg-slate-50 p-1 rounded border border-slate-100">
                    ${coord}
                </div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">Geser untuk ubah radius (auto-save)</label>
                <input data-role="range" type="range" min="10" max="1000" step="5" value="${Math.min(Number(loc.radius_meter) || 50, 1000)}"
                    class="radius-range-slider w-full my-1">
                <div class="text-[10px] text-emerald-600 font-medium mb-1 flex items-center gap-1">
                    <i class="bi bi-lightning-charge-fill"></i> Seret pin / titik putih → tersimpan otomatis
                </div>
                ${loc.keterangan ? `<p class="text-[11px] text-slate-600 mb-2 italic">${this.escapeHtml(loc.keterangan)}</p>` : ''}
                <div class="flex items-center gap-1 pt-1 border-t border-slate-100">
                    <button type="button" data-act="edit" class="flex-1 inline-flex items-center justify-center rounded-lg bg-blue-600 px-2.5 py-1 text-xs font-bold text-white hover:bg-blue-700">
                        <i class="bi bi-pencil-square mr-1"></i> Edit
                    </button>
                    <a href="${this.mapsUrl(loc)}" target="_blank" rel="noopener"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-600 hover:bg-slate-50" title="Buka di Google Maps">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                    <button type="button" data-act="delete" class="inline-flex items-center justify-center rounded-lg bg-red-600 px-2 py-1 text-xs text-white hover:bg-red-700" title="Hapus Lokasi">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            `;

            const els = {
                radius: el.querySelector('[data-role="radius"]'),
                coord: el.querySelector('[data-role="coord"]'),
                range: el.querySelector('[data-role="range"]'),
            };

            el.querySelector('[data-act="edit"]').addEventListener('click', () => this.editLocation(loc));
            el.querySelector('[data-act="delete"]').addEventListener('click', () => this.deleteLocation(loc));
            els.range.addEventListener('input', (e) => this.onPopupRadius(loc.id, e.target.value));

            return { el, els };
        },

        refreshPopup(id) {
            const layer = state.locationLayers[id];

            if (!layer || !layer.popup) return;

            const pos = layer.marker.getLatLng();
            const radius = Math.round(layer.circle.getRadius());
            const els = layer.popup.els;

            if (els.coord) els.coord.textContent = pos.lat.toFixed(5) + ', ' + pos.lng.toFixed(5);
            if (els.radius) els.radius.innerHTML = '<i class="bi bi-bullseye"></i> Radius: ' + radius + 'm';
            if (els.range && document.activeElement !== els.range) els.range.value = Math.min(radius, 1000);
        },

        positionResizeHandle(id) {
            const layer = state.locationLayers[id];

            if (!layer || !layer.resizeHandle) return;

            const c = layer.circle.getLatLng();
            const r = layer.circle.getRadius();
            const dest = c.lng + (r / 111320) / Math.max(Math.cos(c.lat * Math.PI / 180), 0.2);

            layer.resizeHandle.setLatLng([c.lat, dest]);
        },

        scheduleAutoSave(id) {
            clearTimeout(state.autoSaveTimers[id]);
            state.autoSaveTimers[id] = setTimeout(() => this.autoSaveLocation(id), 800);
        },

        async autoSaveLocation(id) {
            const layer = state.locationLayers[id];
            const loc = this.locations.find((item) => String(item.id) === String(id));

            if (!layer || !loc) return;

            const pos = layer.marker.getLatLng();
            const radius = Math.round(layer.circle.getRadius());
            const lat = Number(pos.lat.toFixed(8));
            const lng = Number(pos.lng.toFixed(8));

            this.showToast('Menyimpan ' + (loc.nama_lokasi || '') + '...', '');

            const body = new URLSearchParams();
            body.append('_token', this.config.csrf);
            body.append('id', id);
            body.append('nama_lokasi', loc.nama_lokasi || '');
            body.append('lat', lat);
            body.append('lng', lng);
            body.append('radius_meter', radius);
            body.append('keterangan', loc.keterangan || '');

            try {
                const res = await this.postForm(this.config.urls.save, body);

                if (res && (res.status === true || res.status === 1)) {
                    loc.lat = lat;
                    loc.lng = lng;
                    loc.radius_meter = radius;
                    this.refreshPopup(id);
                    this.showToast('Tersimpan ✓ ' + lat.toFixed(5) + ', ' + lng.toFixed(5) + ' • ' + radius + 'm', 'success');
                } else {
                    this.showToast((res && res.message) || 'Gagal menyimpan', 'error');
                }
            } catch (error) {
                this.showToast('Gagal terhubung ke server', 'error');
            }
        },

        onPopupRadius(id, value) {
            const layer = state.locationLayers[id];

            if (!layer) return;

            let radius = parseInt(value, 10) || 50;
            radius = Math.min(Math.max(radius, 10), 5000);
            layer.circle.setRadius(radius);
            this.positionResizeHandle(id);
            this.refreshPopup(id);
            this.scheduleAutoSave(id);
        },

        setTempMarker(lat, lng, radius) {
            if (state.tempMarker) state.map.removeLayer(state.tempMarker);
            if (state.tempCircle) state.map.removeLayer(state.tempCircle);

            radius = radius || parseInt(this.form.radius_meter, 10) || 50;

            state.tempMarker = L.marker([lat, lng], { icon: state.redPin, draggable: true }).addTo(state.map);
            state.tempCircle = L.circle([lat, lng], {
                color: '#ef4444',
                fillColor: '#ef4444',
                fillOpacity: 0.22,
                weight: 2,
                radius,
            }).addTo(state.map);

            state.tempMarker.bindTooltip('Geser pin ini ke titik gedung', { permanent: false, direction: 'top' });

            const sync = (e) => {
                const pos = e.target.getLatLng();
                this.form.lat = pos.lat.toFixed(8);
                this.form.lng = pos.lng.toFixed(8);
                state.tempCircle.setLatLng(pos);
            };

            state.tempMarker.on('drag', sync);
            state.tempMarker.on('dragend', sync);
        },

        clearTempMarker() {
            if (state.tempMarker) {
                state.map.removeLayer(state.tempMarker);
                state.tempMarker = null;
            }

            if (state.tempCircle) {
                state.map.removeLayer(state.tempCircle);
                state.tempCircle = null;
            }
        },

        /* ── Radius ───────────────────────────────── */

        onRadiusRange() {
            this.clampRadius();
            if (state.tempCircle) state.tempCircle.setRadius(this.form.radius_meter);
        },

        onRadiusInput() {
            this.clampRadius();
            if (state.tempCircle) state.tempCircle.setRadius(this.form.radius_meter);
        },

        clampRadius() {
            let radius = parseInt(this.form.radius_meter, 10);

            if (Number.isNaN(radius)) radius = 50;

            radius = Math.min(Math.max(radius, 10), 5000);
            this.form.radius_meter = radius;
        },

        setRadiusPreset(value) {
            this.form.radius_meter = value;
            if (state.tempCircle) state.tempCircle.setRadius(value);
        },

        useCurrentCenterMap() {
            if (!state.map) return;

            const center = state.map.getCenter();
            this.form.lat = center.lat.toFixed(8);
            this.form.lng = center.lng.toFixed(8);
            this.setTempMarker(center.lat, center.lng);
        },

        /* ── CRUD ─────────────────────────────────── */

        focusLocation(loc) {
            this.activeId = loc.id;

            if (!state.map) return;

            state.map.setView([Number(loc.lat), Number(loc.lng)], 18, { animate: true });

            const layer = state.locationLayers[loc.id];

            if (layer && layer.marker) layer.marker.openPopup();
        },

        openModalAdd(lat, lng) {
            this.modalOpen = true;
            this.form = {
                id: '',
                nama_lokasi: '',
                lat: Number(lat).toFixed(8),
                lng: Number(lng).toFixed(8),
                radius_meter: this.config.defaults.radius || 50,
                keterangan: '',
                is_utama: false,
            };

            this.$store.modals.open('#modalLocation');
            this.$nextTick(() => {
                const input = document.getElementById('nama_lokasi');

                if (input) input.focus();
            });
        },

        addLocation() {
            if (!state.map) return;

            const center = state.map.getCenter();
            this.setTempMarker(center.lat, center.lng);
            this.openModalAdd(center.lat, center.lng);
        },

        editLocation(loc) {
            this.modalOpen = true;
            this.form = {
                id: String(loc.id),
                nama_lokasi: loc.nama_lokasi || '',
                lat: String(loc.lat),
                lng: String(loc.lng),
                radius_meter: parseInt(loc.radius_meter, 10) || 50,
                keterangan: loc.keterangan || '',
                is_utama: parseInt(loc.is_utama || 0, 10) === 1,
            };

            this.setTempMarker(Number(loc.lat), Number(loc.lng), this.form.radius_meter);
            state.map.setView([Number(loc.lat), Number(loc.lng)], 18);
            this.$store.modals.open('#modalLocation');
        },

        closeModal() {
            this.modalOpen = false;
            this.clearTempMarker();
            this.$store.modals.close('#modalLocation');
        },

        async saveLocation() {
            if (this.saving) return;

            const nama = (this.form.nama_lokasi || '').trim();

            if (!nama) {
                Swal.fire('Belum lengkap', 'Nama gedung / lokasi wajib diisi.', 'warning');
                return;
            }

            this.saving = true;

            const body = new FormData(this.$refs.locationForm);
            body.set('_token', this.config.csrf);

            try {
                const res = await this.postForm(this.config.urls.save, body);

                if (res && (res.status === true || res.status === 1 || res.status)) {
                    const editingId = this.form.id;

                    this.closeModal();

                    if (editingId) {
                        this.applyLocalUpdate(editingId);
                        this.showToast('Tersimpan ✓ ' + nama, 'success');
                        Swal.fire({ icon: 'success', title: 'Berhasil Disimpan!', text: res.message || 'Data lokasi berhasil diperbarui.', timer: 1200, showConfirmButton: false });
                    } else {
                        await Swal.fire({ icon: 'success', title: 'Berhasil Disimpan!', text: res.message || 'Data lokasi berhasil diperbarui.', timer: 1300, showConfirmButton: false });
                        window.location.reload();
                    }
                } else {
                    Swal.fire('Gagal Menyimpan', (res && res.message) || 'Gagal menyimpan data lokasi', 'error');
                }
            } catch (error) {
                Swal.fire('Error Sistem', error.message || 'Terjadi kesalahan saat memproses data ke server.', 'error');
            } finally {
                this.saving = false;
            }
        },

        applyLocalUpdate(id) {
            const loc = this.locations.find((item) => String(item.id) === String(id));

            if (!loc) return;

            loc.nama_lokasi = this.form.nama_lokasi;
            loc.lat = Number(this.form.lat);
            loc.lng = Number(this.form.lng);
            loc.radius_meter = parseInt(this.form.radius_meter, 10) || 50;
            loc.keterangan = this.form.keterangan;
            loc.is_utama = this.form.is_utama ? 1 : 0;

            const layer = state.locationLayers[id];

            if (layer) {
                layer.marker.setLatLng([loc.lat, loc.lng]);
                layer.circle.setLatLng([loc.lat, loc.lng]);
                layer.circle.setRadius(loc.radius_meter);
                this.positionResizeHandle(id);
                this.refreshPopup(id);
            } else {
                this.renderAllLocations();
            }
        },

        async toggleUtama(loc) {
            const aktif = parseInt(loc.is_utama || 0, 10) === 1 ? 0 : 1;

            const body = new URLSearchParams();
            body.append('_token', this.config.csrf);
            body.append('id', loc.id);
            body.append('aktif', aktif);

            try {
                const res = await this.postForm(this.config.urls.utama, body);

                if (res && res.status) {
                    await Swal.fire({
                        icon: 'success',
                        title: aktif ? 'Lokasi Utama' : 'Penandaan Dilepas',
                        text: res.message || (aktif ? 'Absen manual admin akan memakai lokasi ini.' : 'Lokasi utama sekarang kosong.'),
                        timer: 1200,
                        showConfirmButton: false,
                    });
                    window.location.reload();
                } else {
                    Swal.fire('Gagal', (res && res.message) || 'Tidak bisa mengubah lokasi utama.', 'error');
                }
            } catch (error) {
                Swal.fire('Error', error.message || 'Terjadi kesalahan saat menghubungi server.', 'error');
            }
        },

        deleteLocation(loc) {
            Swal.fire({
                title: 'Hapus Lokasi ' + (loc.nama_lokasi ? '"' + loc.nama_lokasi + '"?' : '?'),
                text: 'Guru tidak akan bisa absen menggunakan geofence lokasi ini lagi.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Lokasi!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then((result) => {
                if (!result.isConfirmed) return;

                const body = new URLSearchParams();
                body.append('_token', this.config.csrf);
                body.append('id', loc.id);

                this.postForm(this.config.urls.delete, body).then((res) => {
                    if (res && (res.status || res.status === true)) {
                        Swal.fire({ icon: 'success', title: 'Terhapus!', text: res.message || 'Lokasi berhasil dihapus.', timer: 1200, showConfirmButton: false })
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire('Gagal Menghapus', (res && res.message) || 'Gagal menghapus lokasi', 'error');
                    }
                }).catch((error) => Swal.fire('Error', error.message || 'Terjadi kesalahan saat menghubungi server.', 'error'));
            });
        },

        /* ── GPS & pencarian ──────────────────────── */

        locateCurrentGPS() {
            if (!navigator.geolocation) {
                Swal.fire('Tidak Didukung', 'Browser Anda tidak mendukung deteksi lokasi GPS.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Mendeteksi Posisi GPS...',
                text: 'Mohon izinkan browser mengakses lokasi perangkat Anda.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    Swal.close();

                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;

                    if (state.map) state.map.setView([lat, lng], 18);
                    this.setTempMarker(lat, lng);

                    if (this.modalOpen) {
                        this.form.lat = lat.toFixed(8);
                        this.form.lng = lng.toFixed(8);
                    } else {
                        this.openModalAdd(lat, lng);
                    }
                },
                (err) => {
                    Swal.close();
                    Swal.fire('Gagal Deteksi GPS', 'Pastikan izin lokasi telah diaktifkan di pengaturan perangkat Anda (' + err.message + ').', 'error');
                },
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 },
            );
        },

        async searchLocation() {
            const query = this.searchQuery.trim();

            if (!query || this.searching) return;

            this.searching = true;

            const fallbackNominatim = () => fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query) + '&countrycodes=id&limit=1')
                .then((r) => r.json())
                .then((rows) => {
                    if (rows && rows.length > 0) {
                        this.applySearchResult(parseFloat(rows[0].lat), parseFloat(rows[0].lon), rows[0].display_name.split(',')[0]);
                        return;
                    }

                    Swal.fire('Tidak Ditemukan', 'Lokasi tidak ditemukan. Coba ketikkan nama tempat, jalan, atau kota yang lebih spesifik.', 'info');
                });

            try {
                const response = await fetch('https://photon.komoot.io/api/?q=' + encodeURIComponent(query) + '&limit=1&lang=id');
                const data = await response.json();

                if (data && data.features && data.features.length > 0) {
                    const feat = data.features[0];
                    const lng = parseFloat(feat.geometry.coordinates[0]);
                    const lat = parseFloat(feat.geometry.coordinates[1]);
                    const label = feat.properties.name || feat.properties.city || query;

                    this.applySearchResult(lat, lng, label);
                } else {
                    await fallbackNominatim();
                }
            } catch (error) {
                console.warn('Primary geocoder failed, trying fallback:', error);

                try {
                    await fallbackNominatim();
                } catch (err2) {
                    console.error('All search geocoders failed:', err2);
                    Swal.fire('Pencarian Gagal', 'Gagal mencari lokasi. Pastikan koneksi internet aktif.', 'error');
                }
            } finally {
                this.searching = false;
            }
        },

        applySearchResult(lat, lng, label) {
            if (state.map) state.map.setView([lat, lng], 17);

            this.setTempMarker(lat, lng);

            if (this.modalOpen) {
                this.form.lat = lat.toFixed(8);
                this.form.lng = lng.toFixed(8);

                if (!this.form.nama_lokasi && label) {
                    this.form.nama_lokasi = label;
                }
            }
        },

        clearMapSearch() {
            this.searchQuery = '';
        },

        /* ── Utilitas ─────────────────────────────── */

        async postForm(url, body) {
            const response = await fetch(url, {
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

            if (!response.ok) {
                throw new Error((result && result.message) || 'Terjadi kesalahan komunikasi dengan server.');
            }

            return result;
        },

        showToast(text, mode) {
            this.toast = { show: true, text, mode: mode || '' };

            clearTimeout(state.toastTimer);
            state.toastTimer = setTimeout(() => {
                this.toast.show = false;
            }, mode ? 2200 : 6000);
        },

        coordLabel(loc) {
            return Number(loc.lat).toFixed(5) + ', ' + Number(loc.lng).toFixed(5);
        },

        mapsUrl(loc) {
            return 'https://www.google.com/maps?q=' + Number(loc.lat) + ',' + Number(loc.lng);
        },

        escapeHtml(text) {
            if (!text) return '';

            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },
    }));
});
</script>
@endsection
