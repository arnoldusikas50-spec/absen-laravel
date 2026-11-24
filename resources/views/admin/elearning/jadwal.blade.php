@extends('layouts.main')

@section('content')

@php
    $arrHari = ['0' => 'Tidak ada', '7' => 'Minggu', '1' => 'Senin', '2' => 'Selasa', '3' => 'Rabu', '4' => 'Kamis', '5' => 'Jumat', '6' => 'Sabtu'];

    $setting_kbm = $setting_kbm ?? (object) [];
    $istirahatAwal = is_array($setting_kbm->istirahat ?? null) ? $setting_kbm->istirahat : [];

    // Kolom libur lama bisa tersimpan sebagai skalar maupun array hari.
    $liburRaw = $setting_kbm->libur ?? '7';
    $liburAwal = is_array($liburRaw) ? (string) (reset($liburRaw) ?: '7') : (string) $liburRaw;

    $upacaraAwal = is_object($setting_kbm->upacara ?? null) ? $setting_kbm->upacara : (object) [];
    $jumlahIstirahat = count($istirahatAwal);
    $jumIstOptions = range(0, max(3, $jumlahIstirahat));

    $pageConfig = [
        'csrf' => csrf_token(),
        'isAdmin' => (bool) ($is_admin ?? false),
        'urls' => [
            'get' => url('admin/jadwal/get_jadwal'),
            'saveSetting' => url('admin/jadwal/save_setting'),
            'saveSlot' => url('admin/jadwal/save_mapel_slot'),
            'deleteMapel' => url('admin/jadwal/delete_mapel'),
            'reformat' => url('admin/jadwal/reformat_jadwal'),
            'copy' => url('admin/jadwal/copy_jadwal'),
            'import' => route('admin.jadwal.import.import_jadwal'),
        ],
        'settingKbm' => $setting_kbm,
        'kelas' => $kelas ?? [],
        'mapels' => $mapels ?? [],
        'gurus' => $gurus ?? [],
        'tp' => ['tahun' => $tp_active->tahun ?? ''],
        'smt' => ['id_smt' => $smt_active->id_smt ?? ''],
        'liburAwal' => $liburAwal,
        'istirahatAwal' => $istirahatAwal,
        'confirmColor' => \App\Libraries\Theme::metaColor('primary'),
    ];
@endphp

<div x-data="jadwalPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'Jadwal Pelajaran'"
            icon="bi-calendar-week"
            tone="blue"
            subtitle="Matriks jam belajar mengajar per rombel untuk tahun pelajaran dan semester aktif."
            :crumbs="['Data Umum', $judul ?? 'Jadwal Pelajaran']">
            <x-slot:actions>
                <a href="{{ url('admin/jurnal') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 no-underline shadow-xs transition-colors hover:bg-indigo-100">
                    <i class="bi bi-journal-check"></i> Monitoring Jurnal
                </a>
                <button type="button" @click="reload()"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-xs transition-colors hover:border-slate-300 hover:bg-slate-50">
                    <i class="bi bi-arrow-repeat"></i> Reload
                </button>
            </x-slot:actions>
        </x-ui.page-header>
    </div>

    <div class="mt-4 space-y-4 px-4 pb-6 md:px-6">
        @if (! empty($penugasan_kosong))
            @php
                $previewLabels = array_map(static function ($row) {
                    return trim((string) ($row->nama_kelas ?? ('Kelas #' . (int) $row->id_kelas)))
                        . ' - ' . trim((string) ($row->nama_mapel ?? ('Mapel #' . (int) $row->id_mapel)));
                }, array_slice($penugasan_kosong, 0, 5));
            @endphp
            <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3.5 shadow-xs">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </span>
                <div class="min-w-0 text-xs text-amber-900">
                    <p class="mb-0.5 font-bold">{{ count($penugasan_kosong) }} pasangan kelas-mapel belum punya guru pengampu</p>
                    <p class="m-0 leading-relaxed text-amber-800/80">
                        Sesi ini tidak muncul di quick-pick jurnal dan tidak ikut dihitung pada gerbang wajib jurnal sebelum pulang.
                        {{ implode(', ', $previewLabels) }}{{ count($penugasan_kosong) > count($previewLabels) ? ', dan lainnya' : '' }}.
                    </p>
                    <a href="{{ url('dataguru') }}" class="mt-1.5 inline-flex items-center gap-1 font-bold text-amber-800 no-underline hover:text-amber-900">
                        <i class="bi bi-person-check"></i> Atur penugasan guru
                    </a>
                </div>
            </div>
        @endif

        <x-ui.card title="Matriks Jadwal KBM" icon="bi-calendar-range" tone="blue"
            subtitle="Atur alokasi jam belajar mengajar per hari untuk masing-masing rombel"
            class="relative" bodyClass="p-5 md:p-6">
            <x-slot:actions>
                @if ($is_admin ?? false)
                    <button type="button" @click="copyJadwal()"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-semibold text-emerald-700 shadow-xs transition-colors hover:bg-emerald-100">
                        <i class="bi bi-copy"></i> Copy Jadwal
                    </button>
                    <button type="button" @click="openSetting()"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                        <i class="bi bi-gear-fill"></i> Set Jadwal
                    </button>
                    <a href="{{ route('admin.jadwal.export-template.export_template_jadwal') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-xs font-semibold text-indigo-700 no-underline shadow-xs transition-colors hover:bg-indigo-100">
                        <i class="bi bi-file-earmark-excel"></i> Template Excel
                    </a>
                    <button type="button" @click="$refs.importFile.click()"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-xs font-semibold text-indigo-700 shadow-xs transition-colors hover:bg-indigo-100">
                        <i class="bi bi-file-earmark-arrow-up"></i> Import Excel
                    </button>
                    <input type="file" x-ref="importFile" accept=".xlsx,.xls,.xml,.csv" class="hidden"
                        @change="importJadwal($event)">
                @endif
            </x-slot:actions>

            @if (empty($kelas))
                <x-ui.empty icon="bi-calendar-x" title="Belum Ada Data Rombel"
                    description="Belum ada data kelas untuk tahun pelajaran {{ $tp_active->tahun ?? '-' }} semester {{ $smt_active->smt ?? '-' }}. Silakan tambahkan kelas terlebih dahulu.">
                    <a href="{{ url('datakelas') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white no-underline shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                        <i class="bi bi-plus-lg"></i> Kelola Kelas
                    </a>
                </x-ui.empty>
            @else
                <div class="mb-5 flex items-start gap-3 rounded-2xl border border-blue-100 bg-blue-50/70 p-4 text-xs text-blue-900">
                    <i class="bi bi-info-circle-fill mt-0.5 shrink-0 text-base text-blue-600"></i>
                    <div class="space-y-1">
                        <p class="mb-0.5 font-semibold text-blue-950">Petunjuk Pengaturan Jadwal:</p>
                        <p class="m-0 text-blue-800">1. Klik tombol hari di bawah untuk menampilkan atau mengatur jadwal pada hari tersebut.</p>
                        <p class="m-0 text-blue-800">2. Klik salah satu slot waktu di tabel, pilih mata pelajaran, lalu tentukan <b>jumlah jam pelajaran</b> — cukup diisi sekali meski 2 jam pelajaran.</p>
                        @if ($is_admin ?? false)
                            <p class="m-0 text-blue-800">3. Gunakan <b>Set Jadwal</b> untuk mengatur slot jam pelajaran sekaligus untuk seluruh kelas.</p>
                            <p class="m-0 text-blue-800">4. Jadwal dengan guru pengajar otomatis muncul sebagai pilihan cepat saat guru menulis jurnal.</p>
                        @endif
                    </div>
                </div>

                <div class="mb-6">
                    <p class="mb-2.5 text-xs font-bold tracking-wider text-slate-400 uppercase">Pilih Hari Pembelajaran:</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="day in days" :key="day.key">
                            <button type="button" @click="chooseDay(day.key)"
                                class="inline-flex items-center gap-1.5 rounded-xl border px-4 py-2 text-xs font-bold shadow-2xs transition-all"
                                :class="activeHari === day.key
                                    ? 'border-blue-600 bg-blue-600 text-white shadow-blue-500/25'
                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'">
                                <i class="bi bi-calendar-event"></i>
                                <span x-text="day.label"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div x-show="!activeHari" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center">
                    <i class="bi bi-hand-index-thumb mb-2 block text-3xl text-blue-500"></i>
                    <h5 class="mb-1 font-bold text-slate-700">Silakan Pilih Hari</h5>
                    <p class="mb-0 text-xs text-slate-400">Pilih salah satu tombol hari di atas untuk memuat matriks jadwal harian.</p>
                </div>

                <div x-ref="tabel" x-show="activeHari" x-cloak class="mt-4" @click="onCellClick($event)"></div>

                <p x-show="tableEmpty" x-cloak
                    class="mt-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-xs text-slate-500">
                    Tidak ada slot jadwal yang bisa ditampilkan untuk hari ini.
                </p>

                @if ($is_admin ?? false)
                    <div x-show="activeHari" x-cloak
                        class="mt-6 flex flex-col justify-between gap-3 rounded-2xl border border-slate-200/80 bg-slate-50 p-4 sm:flex-row sm:items-center">
                        <div class="text-xs text-slate-600">
                            <span class="mb-0.5 block font-bold text-slate-800">Reformat Garis Waktu:</span>
                            Jika susunan slot jam pada tabel tidak sejajar, sinkronkan kembali dengan tombol Reformat.
                        </div>
                        <button type="button" @click="reformat()"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition-colors hover:bg-slate-100">
                            <i class="bi bi-arrow-repeat"></i> Reformat Jadwal
                        </button>
                    </div>
                @endif
            @endif

            <div x-show="loading" x-cloak
                class="absolute inset-0 z-30 flex items-center justify-center bg-white/75">
                <div class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-blue-600 shadow-xl">
                    <span class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-blue-500/70 border-t-transparent"></span>
                    <span>Memuat data jadwal...</span>
                </div>
            </div>
        </x-ui.card>
    </div>

    <!-- Modal Set Jadwal -->
    <x-ui.modal id="modalSetting" title="Pengaturan Jadwal KBM" icon="bi-gear-fill" tone="blue" size="max-w-xl">
        <form x-ref="settingForm" @submit.prevent="saveSetting()" autocomplete="off" class="flex min-h-0 flex-col">
            @csrf

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                <div>
                    <label for="hariLibur" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                        Hari Libur Mingguan <span class="text-rose-500">*</span>
                    </label>
                    <select id="hariLibur" name="hari_libur" x-model="setForm.hari_libur" required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        @foreach ($arrHari as $kode => $nama)
                            <option value="{{ $kode }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="jamMulai" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                            Jam Masuk KBM <span class="text-rose-500">*</span>
                        </label>
                        <input type="time" id="jamMulai" name="jam_mulai" x-model="setForm.jam_mulai" required
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <div>
                        <label for="jamSelesai" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                            Jam Pulang KBM <span class="text-rose-500">*</span>
                        </label>
                        <input type="time" id="jamSelesai" name="jam_selesai" x-model="setForm.jam_selesai" required
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200/80 bg-slate-50 p-3">
                    <label class="mb-0 flex cursor-pointer items-center gap-2.5">
                        <input type="checkbox" name="format" x-model="setForm.format"
                            class="h-4 w-4 cursor-pointer rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-medium text-slate-700">Terapkan format pembagian slot otomatis ke seluruh kelas</span>
                    </label>
                </div>

                <div x-show="setForm.format" x-cloak class="space-y-4 border-t border-slate-100 pt-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label for="hariUpacara" class="mb-1 block text-xs font-semibold tracking-wider text-slate-500 uppercase">Hari Upacara</label>
                            <select id="hariUpacara" name="hari_upacara" x-model="setForm.hari_upacara"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs">
                                @foreach ($arrHari as $kode => $nama)
                                    <option value="{{ $kode }}">{{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="jamUpacara" class="mb-1 block text-xs font-semibold tracking-wider text-slate-500 uppercase">Jam Ke</label>
                            <select id="jamUpacara" name="jam_upacara" x-model="setForm.jam_upacara"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs">
                                <option value="1">1</option>
                                <option value="2">2</option>
                            </select>
                        </div>
                        <div>
                            <label for="durasiUpacara" class="mb-1 block text-xs font-semibold tracking-wider text-slate-500 uppercase">Durasi (Mnt)</label>
                            <input type="number" id="durasiUpacara" name="durasi_upacara" x-model="setForm.durasi_upacara"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label for="jamMapel" class="mb-1 block text-xs font-semibold tracking-wider text-slate-500 uppercase">Durasi Mapel (Mnt)</label>
                            <input type="number" id="jamMapel" name="jam_mapel" x-model="setForm.jam_mapel"
                                :required="setForm.format"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs">
                        </div>
                        <div>
                            <label for="jmlMapel" class="mb-1 block text-xs font-semibold tracking-wider text-slate-500 uppercase">Jml Mapel/Hari</label>
                            <input type="number" id="jmlMapel" name="jml_mapel" x-model="setForm.jml_mapel"
                                :required="setForm.format"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs">
                        </div>
                        <div>
                            <label for="jumIst" class="mb-1 block text-xs font-semibold tracking-wider text-slate-500 uppercase">Jml Istirahat</label>
                            <select id="jumIst" name="jum_ist" x-model="setForm.jum_ist" @change="resetIstRows()"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs">
                                @foreach ($jumIstOptions as $jumlah)
                                    <option value="{{ $jumlah }}">{{ $jumlah === 0 ? 'Tidak ada' : $jumlah . ' Kali' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(row, index) in istRows" :key="index">
                            <div class="grid grid-cols-2 gap-3 rounded-xl border border-slate-200/80 bg-slate-100/70 p-3">
                                <div>
                                    <label class="mb-1 block text-[11px] font-semibold text-slate-600 uppercase"
                                        x-text="'Istirahat Ke-' + (index + 1)"></label>
                                    <select :name="'ist' + index" x-model="row.ist"
                                        class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs">
                                        <option value="">Pilih Jam Ke...</option>
                                        <template x-for="n in 15" :key="n">
                                            <option :value="String(n)" x-text="'Jam Ke ' + n"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-[11px] font-semibold text-slate-600 uppercase">Durasi (Menit)</label>
                                    <input type="number" :name="'dur_ist' + index" x-model="row.dur"
                                        class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs">
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#modalSetting')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="saving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                    <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!saving" class="bi bi-save"></i> Simpan
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal Set Mata Pelajaran per Slot -->
    <x-ui.modal id="modalSlot" title="Set Mata Pelajaran Slot" icon="bi-clock-history" tone="blue" size="max-w-lg">
        <form x-ref="slotForm" @submit.prevent="saveSlot()" autocomplete="off" class="flex min-h-0 flex-col">
            @csrf

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                <input type="hidden" name="kelas" :value="slot.kelas">
                <input type="hidden" name="hari" :value="slot.hari">
                <input type="hidden" name="jadwal" :value="slot.jadwal">

                <div>
                    <label for="slotMapel" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                        Mata Pelajaran <span class="text-rose-500">*</span>
                    </label>
                    <select id="slotMapel" name="id_mapel" x-model="slot.id_mapel" @change="onMapelChange()" required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <option value="0">-- Tanpa Mapel --</option>
                        <option value="-1">Istirahat</option>
                        <option value="-2">Upacara Bendera</option>
                        <template x-for="[id, nama] in mapelEntries" :key="id">
                            <option :value="String(id)" x-text="nama"></option>
                        </template>
                    </select>
                </div>

                <div :class="Number(slot.id_mapel) > 0 ? '' : 'opacity-60'">
                    <label for="slotGuru" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                        Guru Pengajar <span class="text-rose-500">*</span>
                    </label>
                    <select id="slotGuru" name="id_guru" x-model="slot.id_guru"
                        :disabled="Number(slot.id_mapel) <= 0"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed">
                        <option value="0">-- Pilih Guru Pengajar --</option>
                        <template x-for="guru in guruList" :key="guru.id_guru">
                            <option :value="String(guru.id_guru)"
                                x-text="guru.nama_guru + (guru.nip ? ' (' + guru.nip + ')' : '')"></option>
                        </template>
                    </select>
                    <p class="mt-1 mb-0 text-[10px] text-slate-400">Jadwal ini otomatis menjadi pilihan cepat pada form jurnal guru.</p>
                </div>

                <div x-show="Number(slot.id_mapel) > 0 && slot.kbm_jam_pel > 0" x-cloak>
                    <label for="slotJumlahJam" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                        Jumlah Jam Pelajaran <span class="text-rose-500">*</span>
                    </label>
                    <select id="slotJumlahJam" name="jumlah_jam" x-model="slot.jumlah_jam" @change="applyJumlahJam()"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <template x-for="n in jamOptions" :key="n">
                            <option :value="String(n)" x-text="n + ' jam pelajaran (' + (n * slot.kbm_jam_pel) + ' menit)'"></option>
                        </template>
                        <option value="0">Kustom (atur jam selesai manual)</option>
                    </select>
                    <p class="mt-1 mb-0 text-[10px] text-slate-400"
                        x-text="'Pilih jumlah jam, jam selesai dihitung otomatis. Satu jam pelajaran = ' + slot.kbm_jam_pel + ' menit.'"></p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="slotJamMulai" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">Dari Jam</label>
                        <input type="text" id="slotJamMulai" name="jam_mulai" :value="slot.jam_mulai" readonly
                            class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2 text-sm font-bold text-slate-600">
                    </div>
                    <div>
                        <label for="slotJamSelesai" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                            Sampai Jam <span class="text-rose-500">*</span>
                        </label>
                        <input type="time" id="slotJamSelesai" name="jam_selesai" x-model="slot.jam_selesai"
                            @change="syncJumlahDariJamSelesai()" required
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm font-bold transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <p class="mt-1 mb-0 text-[10px] text-slate-400" x-text="durasiHint"></p>
                    </div>
                </div>

                <div class="flex items-center gap-2 rounded-xl border border-blue-100 bg-blue-50/80 p-3 text-xs text-blue-800">
                    <i class="bi bi-info-circle shrink-0 text-blue-600"></i>
                    <span>Jumlah jam menentukan durasi mapel. Rentang yang bentrok dengan Istirahat, Upacara, atau jadwal lain akan ditolak.</span>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-between gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="deleteSlot()" x-show="slot.jadwal" x-cloak
                    class="inline-flex items-center gap-1 rounded-xl px-3 py-2 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-50">
                    <i class="bi bi-trash3"></i> Hapus
                </button>
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" @click="$store.modals.close('#modalSlot')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                    <button type="submit" :disabled="saving"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                        <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                        <i x-show="!saving" class="bi bi-save"></i> Simpan
                    </button>
                </div>
            </div>
        </form>
    </x-ui.modal>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    const HARI = { '7': 'Minggu', '1': 'Senin', '2': 'Selasa', '3': 'Rabu', '4': 'Kamis', '5': 'Jumat', '6': 'Sabtu' };
    const URUTAN_HARI = ['1', '2', '3', '4', '5', '6', '7'];

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[ch]);

    const timeToMinutes = (time) => {
        const match = String(time ?? '').match(/^(\d{1,2}):(\d{2})/);
        if (!match) return null;
        return (parseInt(match[1], 10) * 60) + parseInt(match[2], 10);
    };

    const minutesToTime = (minutes) => {
        const value = Math.max(0, Math.min((24 * 60) - 1, Math.round(minutes)));
        return `${String(Math.floor(value / 60)).padStart(2, '0')}:${String(value % 60).padStart(2, '0')}`;
    };

    const parseKbmList = (value) => {
        if (Array.isArray(value)) return value;
        if (typeof value === 'string' && value.trim() !== '') {
            try {
                const parsed = JSON.parse(value);
                return Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                return [];
            }
        }
        return [];
    };

    const parseKbmObject = (value) => {
        if (value && typeof value === 'object') return value;
        if (typeof value === 'string' && value.trim() !== '') {
            try {
                const parsed = JSON.parse(value);
                return (parsed && typeof parsed === 'object') ? parsed : {};
            } catch (error) {
                return {};
            }
        }
        return {};
    };

    const periodLabel = (row) => {
        if (row.kind === 'istirahat') return 'Istirahat';
        if (row.kind === 'upacara') return 'Upacara';
        return `Jam ke-${row.jamKe}`;
    };

    Alpine.data('jadwalPage', () => ({
        csrf: '',
        urls: {},
        confirmColor: '#2563eb',
        isAdmin: false,

        settingKbm: {},
        kelasList: {},
        mapelList: {},
        guruList: [],
        tp: {},
        smt: {},

        activeHari: null,
        minTime: '07:00',
        maxTime: '15:00',
        mapelData: {},
        kbmData: {},

        loading: false,
        saving: false,
        tableEmpty: false,

        setForm: {
            hari_libur: '7',
            jam_mulai: '07:00',
            jam_selesai: '15:00',
            format: false,
            hari_upacara: '0',
            jam_upacara: '1',
            durasi_upacara: 45,
            jam_mapel: 45,
            jml_mapel: 10,
            jum_ist: 0,
        },
        istRows: [],

        slot: {
            kelas: '',
            hari: '',
            jadwal: '',
            id_mapel: '0',
            id_guru: '0',
            jumlah_jam: '1',
            jam_mulai: '',
            jam_selesai: '',
            kbm_jam_pel: 0,
            maxJam: 12,
            mapelAwal: false,
        },

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');

            this.csrf = cfg.csrf || '';
            this.urls = cfg.urls || {};
            this.confirmColor = cfg.confirmColor || '#2563eb';
            this.isAdmin = !!cfg.isAdmin;
            this.settingKbm = cfg.settingKbm || {};
            this.kelasList = cfg.kelas || {};
            this.mapelList = cfg.mapels || {};
            this.guruList = cfg.gurus || [];
            this.tp = cfg.tp || {};
            this.smt = cfg.smt || {};

            this.hydrateSettingForm(cfg);
        },

        get days() {
            const libur = this.liburHari();

            return URUTAN_HARI
                .filter((key) => !(libur !== '0' && key === libur))
                .map((key) => ({ key, label: HARI[key] }));
        },

        get mapelEntries() {
            const collator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });

            // Key objek angka selalu dienumerasi naik oleh JavaScript, sehingga
            // urutan nama mapel dari server harus dipulihkan secara eksplisit.
            return Object.entries(this.mapelList || {})
                .sort((a, b) => {
                    const byName = collator.compare(String(a[1] ?? ''), String(b[1] ?? ''));
                    return byName !== 0 ? byName : (Number(a[0]) - Number(b[0]));
                });
        },

        // Daftar opsi tetap 1-20 agar nilai x-model tidak pernah menunjuk opsi
        // yang belum terbentuk saat modal dibuka untuk jumlah jam berbeda.
        get jamOptions() {
            return Array.from({ length: 20 }, (_, index) => index + 1);
        },

        get durasiHint() {
            const startMin = timeToMinutes(this.slot.jam_mulai);
            const endMin = timeToMinutes(this.slot.jam_selesai);

            if (endMin === null || startMin === null || endMin <= startMin) return '';

            const durasi = endMin - startMin;
            const jamPel = Number(this.slot.kbm_jam_pel) || 0;
            const jumlah = (jamPel > 0 && durasi % jamPel === 0) ? Math.round(durasi / jamPel) : 0;

            return (jumlah > 0 ? jumlah + ' jam pelajaran · ' : '') + durasi + ' menit';
        },

        liburHari() {
            const value = this.settingKbm.libur;

            return (value === undefined || value === null || value === '') ? '7' : String(value);
        },

        hydrateSettingForm(cfg = {}) {
            const upacara = parseKbmObject(this.settingKbm.upacara);
            const istirahat = parseKbmList(this.settingKbm.istirahat);

            this.setForm = {
                hari_libur: String(cfg.liburAwal || this.liburHari()),
                jam_mulai: String(this.settingKbm.kbm_jam_mulai || '07:00').slice(0, 5),
                jam_selesai: String(this.settingKbm.kbm_jam_selesai || '15:00').slice(0, 5),
                format: false,
                hari_upacara: String(upacara.id_hari ?? '0'),
                jam_upacara: String(upacara.jam_ke ?? '1'),
                durasi_upacara: Number(upacara.durasi) || 45,
                jam_mapel: Number(this.settingKbm.kbm_jam_pel) || 45,
                jml_mapel: Number(this.settingKbm.kbm_jml_mapel_hari) || 10,
                jum_ist: istirahat.length,
            };

            this.istRows = istirahat.map((item) => ({
                ist: item && item.ist !== undefined && item.ist !== null ? String(item.ist) : '',
                dur: Number(item && item.dur) || 15,
            }));
        },

        resetIstRows() {
            const jumlah = Math.max(0, Number(this.setForm.jum_ist) || 0);
            const rows = [];

            for (let i = 0; i < jumlah; i++) {
                rows.push(this.istRows[i] || { ist: '', dur: 15 });
            }

            this.istRows = rows;
        },

        openSetting() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');

            this.hydrateSettingForm(cfg);
            this.$store.modals.open('#modalSetting');
        },

        async request(url, options = {}) {
            const headers = Object.assign({
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.csrf,
            }, options.headers || {});

            const response = await fetch(url, Object.assign({}, options, { headers }));
            let result = null;

            try {
                result = await response.json();
            } catch (error) {
                result = null;
            }

            if (!response.ok) {
                const err = new Error((result && result.message) || 'Terjadi kesalahan koneksi server.');
                err.result = result;
                throw err;
            }

            return result;
        },

        postParams(url, entries = []) {
            const body = new URLSearchParams();
            body.append('_token', this.csrf);
            entries.forEach(([key, value]) => body.append(key, value ?? ''));

            return this.request(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body,
            });
        },

        postForm(url, formEl) {
            return this.request(url, { method: 'POST', body: new FormData(formEl) });
        },

        async saveSetting() {
            if (this.saving) return;

            this.saving = true;

            try {
                const result = await this.postForm(this.urls.saveSetting, this.$refs.settingForm);

                this.$store.modals.close('#modalSetting');

                if (result && result.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: result.message,
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(() => window.location.reload());
                    return;
                }

                Swal.fire('Gagal', (result && result.message) || 'Gagal menyimpan pengaturan jadwal.', 'error');
            } catch (error) {
                this.$store.modals.close('#modalSetting');
                Swal.fire('Error', error.message || 'Terjadi kesalahan pada server', 'error');
            } finally {
                this.saving = false;
            }
        },

        chooseDay(hari) {
            this.activeHari = hari;
            this.loadJadwalHarian(hari);
        },

        async loadJadwalHarian(hari) {
            if (!hari) return;

            this.loading = true;
            this.tableEmpty = false;

            if (this.$refs.tabel) {
                this.$refs.tabel.innerHTML = '';
            }

            try {
                const result = await this.request(`${this.urls.get}/${hari}`, { method: 'GET' });

                if (!result || !result.status) {
                    Swal.fire('Info', (result && result.message) || 'Jadwal hari ini tidak tersedia.', 'info');
                    return;
                }

                this.minTime = result.data.min_time || '07:00';
                this.maxTime = result.data.max_time || '15:00';
                this.mapelData = result.data.mapel || {};
                this.kbmData = result.data.kbm || {};

                this.$nextTick(() => {
                    try {
                        this.renderTimetable();
                    } catch (error) {
                        console.error('[jadwal] gagal merender tabel', error);
                        this.tableEmpty = true;
                        Swal.fire('Error', 'Gagal menampilkan jadwal. Silakan muat ulang halaman.', 'error');
                    }
                });
            } catch (error) {
                this.tableEmpty = true;
                Swal.fire('Error', error.message || 'Gagal memuat jadwal', 'error');
            } finally {
                this.loading = false;
            }
        },

        buildPeriodRows() {
            const kbm = this.settingKbm || {};
            const mulai = timeToMinutes(kbm.kbm_jam_mulai) ?? timeToMinutes(this.minTime);
            const selesai = timeToMinutes(kbm.kbm_jam_selesai) ?? timeToMinutes(this.maxTime);
            const jamPel = parseInt(kbm.kbm_jam_pel, 10) || 0;

            if (mulai === null || selesai === null || jamPel <= 0 || selesai <= mulai) return null;

            const breaks = {};
            parseKbmList(kbm.istirahat).forEach((item) => {
                const ke = parseInt(item && item.ist, 10);
                const dur = parseInt(item && item.dur, 10);

                if (!(ke >= 1) || !(dur >= 1)) return;
                breaks[ke] = (breaks[ke] || 0) + dur;
            });

            const upacara = parseKbmObject(kbm.upacara);
            const upacaraDur = parseInt(upacara.durasi, 10) || 0;
            const upacaraJamKe = Math.max(1, parseInt(upacara.jam_ke, 10) || 1);
            const hasUpacara = parseInt(upacara.id_hari, 10) === parseInt(this.activeHari, 10) && upacaraDur > 0;

            const rows = [];
            let cursor = mulai;

            const push = (from, to, kind, jamKe) => {
                if (from >= selesai) return;

                to = Math.min(to, selesai);

                if (to <= from) return;

                rows.push({
                    startMin: from,
                    endMin: to,
                    start: minutesToTime(from),
                    end: minutesToTime(to),
                    kind,
                    jamKe,
                });
            };

            if (hasUpacara && upacaraJamKe === 1) {
                push(cursor, cursor + upacaraDur, 'upacara', 0);
                cursor += upacaraDur;
            }

            // Batas 30 jam mencegah loop panjang bila data KBM tidak wajar.
            for (let i = 1; i <= 30 && cursor < selesai; i++) {
                push(cursor, cursor + jamPel, 'jam', i);
                cursor += jamPel;

                if (hasUpacara && upacaraJamKe === i + 1) {
                    push(cursor, cursor + upacaraDur, 'upacara', 0);
                    cursor += upacaraDur;
                }

                if (breaks[i]) {
                    push(cursor, cursor + breaks[i], 'istirahat', 0);
                    cursor += breaks[i];
                }
            }

            return rows.length ? rows : null;
        },

        buildFallbackSegments() {
            const marks = new Set();
            const addMark = (time) => {
                const value = timeToMinutes(time);

                if (value !== null) marks.add(value);
            };

            addMark(this.minTime);
            addMark(this.maxTime);

            Object.values(this.mapelData || {}).forEach((list) => {
                (list || []).forEach((slot) => {
                    addMark(slot.dari);
                    addMark(slot.sampai);
                });
            });

            const boundaries = Array.from(marks).sort((a, b) => a - b);

            if (boundaries.length < 2) return [];

            const axis = [];
            boundaries.forEach((start, index) => {
                axis.push(start);

                const next = boundaries[index + 1];

                if (next === undefined) return;

                let tick = Math.floor(start / 30) * 30 + 30;

                while (tick < next) {
                    axis.push(tick);
                    tick += 30;
                }
            });

            const unique = Array.from(new Set(axis)).sort((a, b) => a - b);
            const segments = [];

            for (let i = 0; i < unique.length - 1; i++) {
                segments.push({
                    start: minutesToTime(unique[i]),
                    end: minutesToTime(unique[i + 1]),
                    startMin: unique[i],
                    endMin: unique[i + 1],
                    kind: '',
                    jamKe: 0,
                    label: '',
                });
            }

            return segments;
        },

        buildTimeSegments() {
            const rows = this.buildPeriodRows();

            if (!rows) return this.buildFallbackSegments();

            const marks = new Set();

            rows.forEach((row) => {
                marks.add(row.startMin);
                marks.add(row.endMin);
            });

            // Batas slot lama yang tidak sejajar tetap dimasukkan agar jadwal
            // tersebut tidak hilang dari tabel (label lanjutan dikosongkan).
            Object.values(this.mapelData || {}).forEach((list) => {
                (list || []).forEach((slot) => {
                    const from = timeToMinutes(slot.dari);
                    const to = timeToMinutes(slot.sampai);

                    if (from !== null) marks.add(from);
                    if (to !== null) marks.add(to);
                });
            });

            const boundaries = Array.from(marks).sort((a, b) => a - b);
            const segments = [];

            for (let i = 0; i < boundaries.length - 1; i++) {
                const start = boundaries[i];
                const end = boundaries[i + 1];

                if (end <= start) continue;

                const owner = rows.find((row) => start >= row.startMin && start < row.endMin);
                const isRowStart = owner && start === owner.startMin;

                segments.push({
                    start: minutesToTime(start),
                    end: minutesToTime(end),
                    startMin: start,
                    endMin: end,
                    kind: owner ? owner.kind : '',
                    jamKe: isRowStart ? owner.jamKe : 0,
                    label: isRowStart ? periodLabel(owner) : '',
                });
            }

            return segments;
        },

        renderTimetable() {
            const collator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });
            const kelasKeys = Object.keys(this.kelasList).sort((a, b) => {
                const byName = collator.compare(String(this.kelasList[a] ?? ''), String(this.kelasList[b] ?? ''));
                return byName !== 0 ? byName : (Number(a) - Number(b));
            });
            const segments = this.buildTimeSegments();
            const indexByStart = new Map(segments.map((segment, index) => [segment.startMin, index]));
            const target = this.$refs.tabel;

            if (!target) return;

            target.innerHTML = '';
            this.tableEmpty = !(kelasKeys.length && segments.length);

            if (this.tableEmpty) return;

            const tableContainer = document.createElement('div');
            tableContainer.className = 'max-h-[68vh] overflow-auto rounded-2xl border border-slate-200 shadow-xs';

            const table = document.createElement('table');
            table.id = 'tbl';
            table.className = 'w-full border-collapse text-left text-[0.8rem]';

            const thead = document.createElement('thead');
            const headerRow = document.createElement('tr');
            headerRow.className = 'bg-slate-800 text-white';

            const thJam = document.createElement('th');
            thJam.colSpan = 2;
            thJam.style.cssText = 'width: 82px; min-width: 82px; max-width: 82px;';
            thJam.className = 'sticky top-0 left-0 z-20 border border-slate-700 bg-slate-800 px-2 py-2 text-center text-[11px] font-bold tracking-wider text-white';
            thJam.textContent = segments.some((segment) => segment.label) ? 'JAM KE' : 'JAM';
            headerRow.appendChild(thJam);

            kelasKeys.forEach((key) => {
                const th = document.createElement('th');
                th.className = 'sticky top-0 z-10 min-w-[110px] border border-slate-700 bg-slate-800 px-3 py-2 text-center align-middle text-[11px] font-bold tracking-wider text-white';
                th.textContent = this.kelasList[key];
                th.setAttribute('data-kelas', key);
                headerRow.appendChild(th);
            });

            thead.appendChild(headerRow);
            table.appendChild(thead);

            const tbody = document.createElement('tbody');
            const jadwalRowSpans = {};

            segments.forEach((segment, rowIndex) => {
                const tr = document.createElement('tr');
                tr.className = 'h-[34px] bg-white hover:bg-slate-50/60';
                tr.setAttribute('data-time', segment.start);
                tr.setAttribute('data-time-end', segment.end);

                const tdTime = document.createElement('td');
                tdTime.className = 'sticky left-0 z-10 border border-slate-200 border-r-2 border-r-slate-300 bg-slate-50 px-2 py-1.5 text-center align-middle';

                const labelClass = segment.kind === 'istirahat'
                    ? 'text-amber-700'
                    : (segment.kind === 'upacara' ? 'text-indigo-700' : 'text-slate-700');
                const labelLine = segment.label
                    ? `<span class="block text-[11px] font-bold leading-tight ${labelClass}">${escapeHtml(segment.label)}</span>`
                    : '';

                tdTime.innerHTML = `${labelLine}
                    <span class="block text-[9px] font-medium leading-tight text-slate-400">${segment.start}-${segment.end}</span>`;
                tr.appendChild(tdTime);

                const tdDot = document.createElement('td');
                tdDot.style.cssText = 'width:4px; background:#e2e8f0; padding:0;';
                tdDot.className = 'border border-slate-200';
                tr.appendChild(tdDot);

                kelasKeys.forEach((key) => {
                    if (jadwalRowSpans[key] && jadwalRowSpans[key] > 0) {
                        jadwalRowSpans[key]--;
                        return;
                    }

                    const scheduleList = this.mapelData[key] || [];
                    const currentSlot = scheduleList.find((slot) => timeToMinutes(slot.dari) === segment.startMin);

                    const td = document.createElement('td');
                    td.className = 'border border-slate-200 p-1 align-middle text-xs';
                    td.setAttribute('data-dari', segment.start);
                    td.setAttribute('data-kelas', key);

                    const div = document.createElement('div');
                    div.className = 'add-mapel group h-full min-h-[28px] cursor-pointer rounded-xl p-1.5 transition-all';
                    div.setAttribute('data-kelas', key);
                    div.setAttribute('data-dari', segment.start);
                    div.setAttribute('data-segmen-selesai', segment.end);
                    div.setAttribute('data-hari', this.activeHari);

                    const kbm = this.kbmData[key] || {};
                    div.setAttribute('data-kbmmulai', kbm.kbm_jam_mulai || '07:00');
                    div.setAttribute('data-kbmselesai', kbm.kbm_jam_selesai || '15:00');

                    const emptyCell = '<div class="flex h-full w-full items-center justify-center rounded-lg text-xs text-slate-300 transition-colors hover:bg-slate-100"><i class="bi bi-plus-lg opacity-40 md:opacity-0 md:group-hover:opacity-100"></i></div>';

                    if (currentSlot) {
                        const mapelId = parseInt(currentSlot.id_mapel, 10);
                        const endIndex = indexByStart.get(timeToMinutes(currentSlot.sampai));
                        const slotsCount = (endIndex !== undefined && endIndex > rowIndex)
                            ? (endIndex - rowIndex)
                            : 1;

                        if (slotsCount > 1) {
                            td.rowSpan = slotsCount;
                            jadwalRowSpans[key] = slotsCount - 1;
                        }

                        div.setAttribute('data-id', currentSlot.id_jadwal);
                        div.setAttribute('data-mapel', currentSlot.id_mapel);
                        div.setAttribute('data-sampai', currentSlot.sampai);
                        div.setAttribute('data-guru', (currentSlot.guru_ids || []).join(','));

                        const guruLabel = currentSlot.guru_nama
                            ? `<div class="truncate text-[10px] text-slate-600" title="${escapeHtml(currentSlot.guru_nama)}">${escapeHtml(currentSlot.guru_nama)}</div>`
                            : '';

                        if (mapelId === -1) {
                            div.innerHTML = `<div class="rounded-lg border border-amber-200 bg-amber-50 p-1.5 text-center text-xs font-medium text-amber-800">
                                <i class="bi bi-cup-hot mr-1 text-[11px]"></i> Istirahat
                                <div class="text-[10px] font-normal text-amber-600">${currentSlot.dari}-${currentSlot.sampai}</div>
                            </div>`;
                        } else if (mapelId === -2) {
                            div.innerHTML = `<div class="rounded-lg border border-indigo-200 bg-indigo-50 p-1.5 text-center text-xs font-medium text-indigo-800">
                                <i class="bi bi-flag mr-1 text-[11px]"></i> Upacara
                                <div class="text-[10px] font-normal text-indigo-600">${currentSlot.dari}-${currentSlot.sampai}</div>
                            </div>`;
                        } else if (mapelId > 0) {
                            const durasi = (timeToMinutes(currentSlot.sampai) ?? 0) - (timeToMinutes(currentSlot.dari) ?? 0);
                            const jamPelKelas = parseInt((this.kbmData[key] || {}).kbm_jam_pel, 10);
                            const jp = (jamPelKelas > 0 && durasi > 0 && durasi % jamPelKelas === 0) ? (durasi / jamPelKelas) : 0;
                            const jpLabel = jp > 0 ? ` &middot; ${jp} JP` : '';

                            div.innerHTML = `<div class="rounded-lg border border-blue-200 bg-blue-50 p-1.5 text-center text-xs font-semibold text-blue-900 shadow-2xs transition-colors hover:bg-blue-100/80">
                                <div class="truncate">${escapeHtml(currentSlot.kode || 'Mapel')}</div>
                                <div class="text-[10px] font-normal text-blue-600">${currentSlot.dari}-${currentSlot.sampai}${jpLabel}</div>
                                ${guruLabel || '<div class="text-[10px] font-medium text-rose-500">Guru belum diset</div>'}
                            </div>`;
                        } else {
                            div.innerHTML = emptyCell;
                        }
                    } else {
                        div.innerHTML = emptyCell;
                    }

                    td.appendChild(div);
                    tr.appendChild(td);
                });

                tbody.appendChild(tr);
            });

            table.appendChild(tbody);
            tableContainer.appendChild(table);
            target.appendChild(tableContainer);
        },

        onCellClick(event) {
            if (!this.isAdmin) return;

            const div = event.target.closest('.add-mapel');

            if (!div) return;

            const kelasId = div.getAttribute('data-kelas');
            const dari = div.getAttribute('data-dari');
            const mapel = div.getAttribute('data-mapel') || '0';
            const sampai = div.getAttribute('data-sampai') || div.getAttribute('data-segmen-selesai') || '';
            const idJadwal = div.getAttribute('data-id') || '';
            const guruIds = (div.getAttribute('data-guru') || '').split(',').filter(Boolean);

            const kbm = this.kbmData[kelasId] || {};
            const jamPel = parseInt(kbm.kbm_jam_pel, 10) > 0
                ? parseInt(kbm.kbm_jam_pel, 10)
                : (parseInt(this.settingKbm.kbm_jam_pel, 10) > 0 ? parseInt(this.settingKbm.kbm_jam_pel, 10) : 0);
            const startMin = timeToMinutes(dari);
            const dayEndMin = timeToMinutes(kbm.kbm_jam_selesai || this.settingKbm.kbm_jam_selesai || this.maxTime);
            const maxJam = (jamPel > 0 && startMin !== null && dayEndMin !== null)
                ? Math.max(1, Math.min(20, Math.floor((dayEndMin - startMin) / jamPel)))
                : 12;

            // Slot baru langsung diisi satu jam pelajaran penuh. Jumlah jam
            // menentukan jam selesai, jadi sesi 2 jam cukup diisi satu kali.
            let jumlah = 1;
            let akhir = sampai || dari;

            if (idJadwal) {
                const durasi = (timeToMinutes(sampai) ?? 0) - (startMin ?? 0);
                jumlah = (jamPel > 0 && durasi > 0 && durasi % jamPel === 0) ? Math.round(durasi / jamPel) : 0;

                if (jumlah > maxJam) jumlah = 0;
            } else if (jamPel > 0 && startMin !== null) {
                akhir = minutesToTime(startMin + jamPel);
            }

            this.slot = {
                kelas: kelasId,
                hari: this.activeHari,
                jadwal: idJadwal,
                id_mapel: String(mapel),
                id_guru: guruIds.length ? String(guruIds[0]) : '0',
                jumlah_jam: String(jumlah),
                jam_mulai: dari,
                jam_selesai: akhir,
                kbm_jam_pel: jamPel,
                maxJam,
                mapelAwal: parseInt(mapel, 10) > 0,
            };

            this.$store.modals.open('#modalSlot');
        },

        onMapelChange() {
            const isMapel = Number(this.slot.id_mapel) > 0;

            if (!isMapel) {
                this.slot.id_guru = '0';
                this.slot.mapelAwal = false;
                return;
            }

            // Mapel pertama kali dipilih pada slot kosong: mulai dari 1 jam.
            if (!this.slot.mapelAwal && this.slot.kbm_jam_pel > 0) {
                this.slot.jumlah_jam = '1';
                this.applyJumlahJam();
            }

            this.slot.mapelAwal = true;
        },

        applyJumlahJam() {
            const jumlah = parseInt(this.slot.jumlah_jam, 10);

            if (jumlah > 0 && this.slot.kbm_jam_pel > 0) {
                const startMin = timeToMinutes(this.slot.jam_mulai);

                if (startMin !== null) {
                    this.slot.jam_selesai = minutesToTime(startMin + (jumlah * this.slot.kbm_jam_pel));
                }
            }
        },

        syncJumlahDariJamSelesai() {
            const startMin = timeToMinutes(this.slot.jam_mulai);
            const endMin = timeToMinutes(this.slot.jam_selesai);
            const jamPel = Number(this.slot.kbm_jam_pel) || 0;

            if (jamPel > 0 && startMin !== null && endMin !== null) {
                const durasi = endMin - startMin;
                const exact = (durasi > 0 && durasi % jamPel === 0) ? Math.round(durasi / jamPel) : 0;

                this.slot.jumlah_jam = (exact > 0 && exact <= this.slot.maxJam) ? String(exact) : '0';
            }
        },

        async saveSlot() {
            if (this.saving) return;

            this.saving = true;

            try {
                const result = await this.postForm(this.urls.saveSlot, this.$refs.slotForm);

                this.$store.modals.close('#modalSlot');

                if (result && result.status) {
                    if (this.activeHari) {
                        this.loadJadwalHarian(this.activeHari);
                    }

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: result.message,
                        showConfirmButton: false,
                        timer: 2000,
                    });
                    return;
                }

                Swal.fire('Gagal', (result && result.message) || 'Gagal menyimpan jadwal slot.', 'error');
            } catch (error) {
                this.$store.modals.close('#modalSlot');
                Swal.fire('Error', error.message || 'Terjadi kesalahan pada server', 'error');
            } finally {
                this.saving = false;
            }
        },

        async deleteSlot() {
            const idJadwal = this.slot.jadwal;

            if (!idJadwal) return;

            const konfirmasi = await Swal.fire({
                title: 'Hapus Jadwal?',
                text: 'Mapel ini akan dihapus dari slot waktu terpilih.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            try {
                const result = await this.postParams(this.urls.deleteMapel, [['id_jadwal', idJadwal]]);

                this.$store.modals.close('#modalSlot');

                if (result && result.status) {
                    if (this.activeHari) {
                        this.loadJadwalHarian(this.activeHari);
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: result.message,
                        timer: 1500,
                        showConfirmButton: false,
                    });
                    return;
                }

                Swal.fire('Gagal', (result && result.message) || 'Gagal menghapus jadwal.', 'error');
            } catch (error) {
                this.$store.modals.close('#modalSlot');
                Swal.fire('Error', error.message || 'Gagal menghubungi server', 'error');
            }
        },

        reload() {
            if (this.activeHari) {
                this.loadJadwalHarian(this.activeHari);
                return;
            }

            window.location.reload();
        },

        async reformat() {
            const konfirmasi = await Swal.fire({
                title: 'Reformat Jadwal?',
                text: 'Susunan slot garis waktu harian akan diformat ulang dan disesuaikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: this.confirmColor,
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Format Ulang',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const result = await this.postParams(this.urls.reformat);

                if (result && result.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sukses',
                        text: result.message,
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(() => this.reload());
                    return;
                }

                Swal.fire('Gagal', (result && result.message) || 'Gagal reformat', 'error');
            } catch (error) {
                Swal.fire('Error', error.message || 'Gagal menghubungi server', 'error');
            }
        },

        async copyJadwal() {
            const semester = String(this.smt.id_smt || '');
            const smtLalu = semester === '1' ? '2' : '1';
            const tahun = String(this.tp.tahun || '').split('/');
            let thnLalu = String(this.tp.tahun || '');

            if (semester === '1' && tahun.length > 1) {
                thnLalu = `${parseInt(tahun[0], 10) - 1}/${parseInt(tahun[1], 10) - 1}`;
            }

            const konfirmasi = await Swal.fire({
                title: 'Copy Jadwal Semester Lalu?',
                html: `Sumber Data: <b>Tahun ${thnLalu} Semester ${smtLalu === '1' ? 'Ganjil' : 'Genap'}</b>.<br>
                       <span class='text-rose-600 font-bold'>PERHATIAN:</span> Jadwal saat ini akan ditimpa.<br>
                       Jadwal hanya tersalin jika <b>kode kelas</b> sesuai.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: this.confirmColor,
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Copy Jadwal',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            Swal.fire({ title: 'Menyalin Jadwal...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const result = await this.postParams(this.urls.copy, [
                    ['thnLalu', thnLalu],
                    ['smtLalu', smtLalu],
                ]);

                if (result && result.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sukses',
                        text: result.message,
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(() => this.reload());
                    return;
                }

                Swal.fire('Gagal', (result && result.message) || 'Gagal menyalin jadwal', 'error');
            } catch (error) {
                Swal.fire('Error', error.message || 'Gagal menghubungi server', 'error');
            }
        },

        async importJadwal(event) {
            const input = event.target;
            const file = input.files[0];

            if (!file) return;

            const formData = new FormData();
            formData.append('jadwal_file', file);
            formData.append('_token', this.csrf);

            Swal.fire({
                title: 'Mengimpor Jadwal...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            const detail = (errors) => {
                if (!Array.isArray(errors) || errors.length === 0) return '';

                const lines = errors.slice(0, 5).map((item) => '• Baris ' + item.baris + ': ' + item.pesan);
                const sisa = errors.length > 5 ? '\n... dan ' + (errors.length - 5) + ' baris lainnya' : '';

                return '\n\n' + lines.join('\n') + sisa;
            };

            try {
                const result = await this.request(this.urls.import, { method: 'POST', body: formData });

                input.value = '';

                if (result && result.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sukses Impor',
                        text: (result.message || 'Jadwal berhasil diimpor.') + detail(result.errors),
                        timer: 3000,
                        showConfirmButton: false,
                    }).then(() => this.reload());
                    return;
                }

                Swal.fire('Gagal', ((result && result.message) || 'Gagal mengimpor jadwal') + detail(result && result.errors), 'error');
            } catch (error) {
                input.value = '';
                Swal.fire('Error', error.message || 'Gagal menghubungi server', 'error');
            }
        },
    }));
});
</script>
@endsection
