@extends('layouts.main')

@section('content')
<?php
$satuan = ["1" => ["SD", "MI"], "2" => ["SMP", "MTS"], "3" => ["SMA", "MA", "SMK"]];

$settingData = isset($setting) && is_object($setting) ? $setting : (object) [];

$jenjangOptions = [
    1 => 'SD/MI',
    2 => 'SMP/MTS',
    3 => 'SMA/MA/SMK'
];

$bankSoalOptions = [
    1 => 'Aktif',
    2 => 'Non-Aktif'
];

$tokenOptions = [
    1 => 'Ya',
    2 => 'Tidak'
];

$modeOptions = [
    1 => 'CBT',
    2 => 'Elearning'
];

$bankSoalDateTime = '';
if (!empty($settingData->ba_waktu) && $settingData->ba_waktu !== '0000-00-00 00:00:00') {
    $bankSoalDateObj = DateTime::createFromFormat('Y-m-d H:i:s', (string) $settingData->ba_waktu);
    if ($bankSoalDateObj instanceof DateTime) {
        $bankSoalDateTime = $bankSoalDateObj->format('Y-m-d\TH:i');
    }
}

$satuanOptionList = $satuan[$settingData->jenjang ?? '1'] ?? [];

// Kolom media yang ikut dikirim saat menyimpan pengaturan. Nilainya berasal
// dari unggahan di halaman ini atau dari database, semuanya path relatif.
$mediaFields = [
    'logo_kanan', 'logo_kiri', 'kodescan', 'kopsekolah', 'kartu_bg_depan',
    'kartu_bg_belakang', 'stampel', 'ttdproktor', 'tanda_tangan',
    'banner1', 'banner2', 'banner3',
];

$mediaValues = [];
foreach ($mediaFields as $field) {
    $mediaValues[$field] = (string) ($settingData->{$field} ?? '');
}

$uploadInputs = [
    ['field' => 'stampel', 'label' => 'Cap / Stampel Sekolah'],
    ['field' => 'tanda_tangan', 'label' => 'TTD Kepala Sekolah'],
    ['field' => 'logo_kiri', 'label' => 'Logo Kiri / Aplikasi'],
    ['field' => 'logo_kanan', 'label' => 'Logo Kanan'],
    ['field' => 'kopsekolah', 'label' => 'Kop Sekolah'],
    ['field' => 'kartu_bg_depan', 'label' => 'Latar Kartu Siswa Depan (PNG)'],
    ['field' => 'kartu_bg_belakang', 'label' => 'Latar Kartu Siswa Belakang (PNG)'],
];

$uploadCards = array_map(function ($item) use ($mediaValues) {
    return [
        'field' => $item['field'],
        'label' => $item['label'],
        'relative' => $mediaValues[$item['field']],
        'src' => $mediaValues[$item['field']] !== '' ? url($mediaValues[$item['field']]) : '',
    ];
}, $uploadInputs);

$pageConfig = [
    'csrf' => csrf_token(),
    'urls' => [
        'save' => url('setting/saveSetting'),
        'upload' => url('setting/uploadFile'),
        'delete' => url('setting/deleteFile'),
    ],
    'satuan' => $satuan,
    'media' => $mediaValues,
    'uploads' => $uploadCards,
];

$labelClass = 'mb-1.5 block text-xs font-semibold text-slate-700';
$inputClass = 'w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20';
$bintang = '<span class="text-red-500">*</span>';
?>

<div x-data="settingPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <!-- Content Header -->
    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'Pengaturan'"
            icon="bi-database-gear"
            tone="emerald"
            :subtitle="$subjudul ?? 'Profil Sekolah & Kebijakan Presensi'"
            :crumbs="['Pengaturan', $subjudul ?? 'Data']" />
    </div>

    <!-- Main Content Area -->
    <div class="mt-4 space-y-5 px-4 pb-6 md:px-6">

        <form id="savesetting" @submit.prevent="save($event)">
            @csrf

            <x-ui.card title="Pengaturan Profil Sekolah" icon="bi-sliders2" tone="blue"
                subtitle="Kelola identitas sekolah, kontak, serta pengaturan ujian dalam satu tempat.">

                <div class="mb-4 flex gap-2.5 rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-3 text-xs text-blue-900">
                    <i class="bi bi-info-circle mt-0.5 text-blue-600"></i>
                    <div class="space-y-1">
                        <p class="font-bold">Panduan Pengisian Profil</p>
                        <ul class="ml-4 list-disc space-y-0.5">
                            <li>Isi seluruh kolom bertanda bintang (<span class="text-red-500">*</span>) untuk memastikan data profil lengkap.</li>
                            <li>Pastikan jenjang dan satuan pendidikan sesuai agar tampilan rapor dan fitur absensi by ikasmedia menyesuaikan otomatis.</li>
                            <li>Unggah logo dan dokumen pendukung dalam format JPG/PNG maksimal 2MB agar tampil tajam pada laporan.</li>
                            <li>Aktifkan token siswa hanya saat dibutuhkan untuk sesi ujian.</li>
                        </ul>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">

                    <x-ui.card title="Identitas Sekolah" icon="bi-building" tone="blue" body-class="space-y-4 p-5">
                        <div>
                            <label for="set-nama-aplikasi" class="{{ $labelClass }}">Nama Aplikasi {!! $bintang !!}</label>
                            <input type="text" id="set-nama-aplikasi" name="nama_aplikasi" class="{{ $inputClass }}"
                                value="{{ $settingData->nama_aplikasi ?? '' }}" required>
                        </div>
                        <div>
                            <label for="set-nama-sekolah" class="{{ $labelClass }}">Nama Sekolah {!! $bintang !!}</label>
                            <input type="text" id="set-nama-sekolah" name="nama_sekolah" class="{{ $inputClass }}"
                                value="{{ $settingData->sekolah ?? '' }}" required>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="set-nss" class="{{ $labelClass }}">NSS/NSM</label>
                                <input type="text" id="set-nss" name="nss" class="{{ $inputClass }}"
                                    value="{{ $settingData->nss ?? '' }}" pattern="[0-9]{10,12}"
                                    title="NSS/NSM harus berupa angka 10-12 digit">
                            </div>
                            <div>
                                <label for="set-npsn" class="{{ $labelClass }}">NPSN</label>
                                <input type="text" id="set-npsn" name="npsn" class="{{ $inputClass }}"
                                    value="{{ $settingData->npsn ?? '' }}" pattern="[0-9]{8}"
                                    title="NPSN harus berupa 8 digit angka">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="jenjang" class="{{ $labelClass }}">Jenjang {!! $bintang !!}</label>
                                <select id="jenjang" name="jenjang" class="{{ $inputClass }}" @change="onJenjangChange($event)" required>
                                    <option value="" disabled {{ empty($settingData->jenjang) ? 'selected' : '' }}>Pilih Jenjang</option>
                                    @foreach ($jenjangOptions as $key => $label)
                                        <option value="{{ $key }}" @selected((string) ($settingData->jenjang ?? '') === (string) $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="satuan-pend" class="{{ $labelClass }}">Satuan Pendidikan {!! $bintang !!}</label>
                                <select id="satuan-pend" name="satuan_pendidikan" class="{{ $inputClass }}" x-ref="satuanSelect" required>
                                    <option value="" disabled {{ empty($settingData->satuan_pendidikan) ? 'selected' : '' }}>Pilih Satuan Pendidikan</option>
                                    @foreach ($satuanOptionList as $idx => $label)
                                        <option value="{{ $idx + 1 }}" @selected((string) ($settingData->satuan_pendidikan ?? '') === (string) ($idx + 1))>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card title="Alamat & Wilayah" icon="bi-geo-alt" tone="violet" body-class="space-y-4 p-5">
                        <div>
                            <label for="set-alamat" class="{{ $labelClass }}">Alamat {!! $bintang !!}</label>
                            <textarea id="set-alamat" name="alamat" rows="4" class="{{ $inputClass }}"
                                placeholder="Masukkan alamat lengkap" required>{{ $settingData->alamat ?? '' }}</textarea>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="set-desa" class="{{ $labelClass }}">Desa/Kelurahan {!! $bintang !!}</label>
                                <input type="text" id="set-desa" name="desa" class="{{ $inputClass }}"
                                    value="{{ $settingData->desa ?? '' }}" required>
                            </div>
                            <div>
                                <label for="set-kec" class="{{ $labelClass }}">Kecamatan {!! $bintang !!}</label>
                                <input type="text" id="set-kec" name="kec" class="{{ $inputClass }}"
                                    value="{{ $settingData->kecamatan ?? '' }}" required>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="set-kota" class="{{ $labelClass }}">Kabupaten/Kota {!! $bintang !!}</label>
                                <input type="text" id="set-kota" name="kota" class="{{ $inputClass }}"
                                    value="{{ $settingData->kota ?? '' }}" required>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="set-kode-pos" class="{{ $labelClass }}">Kode Pos</label>
                                    <input type="text" id="set-kode-pos" name="kode_pos" class="{{ $inputClass }}"
                                        value="{{ $settingData->kode_pos ?? '' }}" pattern="[0-9]{5}"
                                        title="Kode pos terdiri dari 5 digit angka">
                                </div>
                                <div>
                                    <label for="set-provinsi" class="{{ $labelClass }}">Provinsi {!! $bintang !!}</label>
                                    <input type="text" id="set-provinsi" name="provinsi" class="{{ $inputClass }}"
                                        value="{{ $settingData->provinsi ?? '' }}" required>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card title="Kontak Sekolah" icon="bi-telephone" tone="emerald" body-class="space-y-4 p-5">
                        <div>
                            <label for="set-web" class="{{ $labelClass }}">Website</label>
                            <input type="url" id="set-web" name="web" class="{{ $inputClass }}"
                                value="{{ $settingData->web ?? ($settingData->website ?? '') }}"
                                placeholder="https://contoh-sekolah.sch.id">
                        </div>
                        <div>
                            <label for="set-email" class="{{ $labelClass }}">Email</label>
                            <input type="email" id="set-email" name="email" class="{{ $inputClass }}"
                                value="{{ $settingData->email ?? '' }}"
                                placeholder="kontak@sekolah.sch.id">
                        </div>
                        <div>
                            <label for="set-tlp" class="{{ $labelClass }}">Nomor Telepon</label>
                            <div class="flex items-stretch">
                                <span class="inline-flex items-center rounded-l-xl border border-r-0 border-slate-200 bg-slate-100 px-3 text-xs font-semibold text-slate-500">+62</span>
                                <input type="tel" id="set-tlp" name="tlp" class="{{ $inputClass }} rounded-l-none"
                                    value="{{ $settingData->telp ?? '' }}" pattern="[0-9]{6,15}"
                                    title="Masukkan nomor telepon tanpa spasi atau tanda hubung">
                            </div>
                        </div>
                        <div>
                            <label for="set-fax" class="{{ $labelClass }}">Faksimili</label>
                            <input type="text" id="set-fax" name="fax" class="{{ $inputClass }}"
                                value="{{ $settingData->fax ?? '' }}">
                        </div>
                    </x-ui.card>

                    <x-ui.card title="Konfigurasi Notifikasi" icon="bi-chat-dots" tone="blue" body-class="space-y-4 p-5">
                        <div class="flex gap-2.5 rounded-xl border border-sky-100 bg-sky-50/70 px-4 py-3 text-xs text-sky-900">
                            <i class="bi bi-info-circle mt-0.5 text-sky-600"></i>
                            <p>Notifikasi ini digunakan untuk mengirim rekap absensi harian otomatis.</p>
                        </div>

                        <h3 class="text-xs font-bold uppercase tracking-wider text-blue-600">Telegram</h3>
                        <div>
                            <label for="set-telegram-token" class="{{ $labelClass }}">Bot Token</label>
                            <input type="text" id="set-telegram-token" name="telegram_bot_token" class="{{ $inputClass }}"
                                value="{{ $settingData->telegram_bot_token ?? '' }}"
                                placeholder="123456789:ABCdefGhIjkLmNoPqRsTuVwXyZ">
                        </div>
                        <div>
                            <label for="set-telegram-chat" class="{{ $labelClass }}">Chat ID Tujuan</label>
                            <input type="text" id="set-telegram-chat" name="telegram_chat_id" class="{{ $inputClass }}"
                                value="{{ $settingData->telegram_chat_id ?? '' }}"
                                placeholder="-100xxxxxxx atau ID User">
                        </div>

                        <hr class="border-slate-100">

                        <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-600">WhatsApp (Fonnte/Wablas)</h3>
                        <div>
                            <label for="set-wa-token" class="{{ $labelClass }}">WA API Token</label>
                            <input type="text" id="set-wa-token" name="wa_token" class="{{ $inputClass }}"
                                value="{{ $settingData->wa_token ?? '' }}"
                                placeholder="Token API WhatsApp Gateway">
                        </div>
                        <div>
                            <label for="set-wa-target" class="{{ $labelClass }}">Nomor WA Tujuan</label>
                            <input type="text" id="set-wa-target" name="wa_target_number" class="{{ $inputClass }}"
                                value="{{ $settingData->wa_target_number ?? '' }}"
                                placeholder="0812345xxxxx">
                        </div>
                        <div>
                            <label for="set-wa-gateway" class="{{ $labelClass }}">Gateway URL (Opsional)</label>
                            <input type="url" id="set-wa-gateway" name="wa_gateway_url" class="{{ $inputClass }}"
                                value="{{ $settingData->wa_gateway_url ?? 'https://api.fonnte.com/send' }}"
                                placeholder="https://api.fonnte.com/send">
                        </div>

                        <hr class="border-slate-100">

                        <h3 class="text-xs font-bold uppercase tracking-wider text-rose-500">Integrasi MikroTik (Hotspot)</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="sm:col-span-2">
                                <label for="set-mikrotik-host" class="{{ $labelClass }}">IP Address MikroTik</label>
                                <input type="text" id="set-mikrotik-host" name="mikrotik_host" class="{{ $inputClass }}"
                                    value="{{ $settingData->mikrotik_host ?? '' }}"
                                    placeholder="192.168.88.1">
                            </div>
                            <div>
                                <label for="set-mikrotik-port" class="{{ $labelClass }}">Port API</label>
                                <input type="number" id="set-mikrotik-port" name="mikrotik_port" class="{{ $inputClass }}"
                                    value="{{ $settingData->mikrotik_port ?? '8728' }}"
                                    placeholder="8728">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="set-mikrotik-user" class="{{ $labelClass }}">User API</label>
                                <input type="text" id="set-mikrotik-user" name="mikrotik_user" class="{{ $inputClass }}"
                                    value="{{ $settingData->mikrotik_user ?? '' }}">
                            </div>
                            <div>
                                <label for="set-mikrotik-pass" class="{{ $labelClass }}">Password API</label>
                                <input type="password" id="set-mikrotik-pass" name="mikrotik_pass" class="{{ $inputClass }}"
                                    value="{{ $settingData->mikrotik_pass ?? '' }}">
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card title="Penanggung Jawab & Pengaturan" icon="bi-person-badge" tone="amber"
                        body-class="space-y-4 p-5">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="set-kepsek" class="{{ $labelClass }}">Kepala Sekolah {!! $bintang !!}</label>
                                <input type="text" id="set-kepsek" name="kepsek" class="{{ $inputClass }}"
                                    value="{{ $settingData->kepsek ?? '' }}" required>
                            </div>
                            <div>
                                <label for="set-nip" class="{{ $labelClass }}">NIP Kepala Sekolah</label>
                                <input type="text" id="set-nip" name="nip" class="{{ $inputClass }}"
                                    value="{{ $settingData->nip ?? '' }}" pattern="[0-9]{8,20}"
                                    title="Masukkan 8-20 digit angka">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="set-proktor" class="{{ $labelClass }}">Proktor {!! $bintang !!}</label>
                                <input type="text" id="set-proktor" name="proktor" class="{{ $inputClass }}"
                                    value="{{ $settingData->proktor ?? '' }}" required>
                            </div>
                            <div>
                                <label for="set-nip-proktor" class="{{ $labelClass }}">NIP Proktor</label>
                                <input type="text" id="set-nip-proktor" name="nip_proktor" class="{{ $inputClass }}"
                                    value="{{ $settingData->nip_proktor ?? '' }}" pattern="[0-9]{8,20}"
                                    title="Masukkan 8-20 digit angka">
                            </div>
                        </div>
                        <div>
                            <label for="ba_aktif" class="{{ $labelClass }}">Status Bank Soal</label>
                            <select id="ba_aktif" name="ba_aktif" class="{{ $inputClass }}" required>
                                <option value="" disabled {{ empty($settingData->ba_aktif) ? 'selected' : '' }}>Pilih Status</option>
                                @foreach ($bankSoalOptions as $key => $label)
                                    <option value="{{ $key }}" @selected((string) ($settingData->ba_aktif ?? '') === (string) $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="set-ba-waktu" class="{{ $labelClass }}">Waktu Bank Soal</label>
                            <input type="datetime-local" id="set-ba-waktu" name="ba_waktu" class="{{ $inputClass }}"
                                value="{{ $bankSoalDateTime }}">
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="tkn_siswa" class="{{ $labelClass }}">Tampilkan Token di Siswa</label>
                                <select id="tkn_siswa" name="tkn_siswa" class="{{ $inputClass }}" required>
                                    <option value="" disabled {{ empty($settingData->tkn_siswa) ? 'selected' : '' }}>Pilih Status</option>
                                    @foreach ($tokenOptions as $key => $label)
                                        <option value="{{ $key }}" @selected((string) ($settingData->tkn_siswa ?? '') === (string) $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="mode_app" class="{{ $labelClass }}">Mode Aplikasi</label>
                                <select id="mode_app" name="mode_app" class="{{ $inputClass }}" required>
                                    <option value="" disabled {{ empty($settingData->mode_app) ? 'selected' : '' }}>Pilih Mode</option>
                                    @foreach ($modeOptions as $key => $label)
                                        <option value="{{ $key }}" @selected((string) ($settingData->mode_app ?? '') === (string) $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="set-api-key" class="{{ $labelClass }}">API Key (OpenAI / Gemini)</label>
                            <input type="text" id="set-api-key" name="api_key" class="{{ $inputClass }}"
                                value="{{ $settingData->api_key ?? '' }}"
                                placeholder="Paste API Key disini...">
                            <p class="mt-1 text-[11px] text-slate-400">Kunci API diperlukan untuk fitur generate soal otomatis menggunakan AI.</p>
                        </div>
                    </x-ui.card>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="submit" :disabled="saving"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                        <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                        <i x-show="!saving" class="bi bi-save"></i> Simpan Pengaturan
                    </button>
                </div>
            </x-ui.card>
        </form>

        <x-ui.card title="Identitas Visual & Dokumen Pendukung" icon="bi-images" tone="violet"
            subtitle="Format file JPG/PNG maksimal 2MB">

            <div class="mb-4 flex gap-2.5 rounded-xl border border-amber-100 bg-amber-50/70 px-4 py-3 text-xs text-amber-900">
                <i class="bi bi-images mt-0.5 text-amber-600"></i>
                <div class="space-y-1">
                    <p class="font-bold">Panduan Upload</p>
                    <ul class="ml-4 list-disc space-y-0.5">
                        <li>Gunakan resolusi tinggi agar logo dan banner tampil tajam pada laporan.</li>
                        <li>Banner disarankan berukuran 1020x250 piksel dengan rasio 4:1.</li>
                        <li>Format transparan (PNG) ideal untuk tanda tangan digital dan cap sekolah.</li>
                        <li>Latar kartu siswa disarankan rasio 54:85,6 (mis. 640 × 1016 px) — dipakai desain "Kartu Pelajar (Portrait)".</li>
                    </ul>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <template x-for="item in uploads" :key="item.field">
                    <div class="flex flex-col rounded-xl border border-slate-200 bg-slate-50/50 p-3">
                        <p class="mb-2 text-xs font-semibold text-slate-700" x-text="item.label"></p>
                        <div class="mb-3 flex h-28 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-white">
                            <img x-show="item.preview" :src="item.preview" :alt="item.label"
                                class="h-full w-full object-contain p-1">
                            <span x-show="!item.preview" class="text-[11px] text-slate-400">Belum ada file</span>
                        </div>
                        <input type="file" accept="image/jpeg,image/png" :disabled="item.busy"
                            @change="upload(item, $event)"
                            class="block w-full cursor-pointer rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[11px] text-slate-600 file:mr-2 file:rounded-md file:border-0 file:bg-violet-50 file:px-2.5 file:py-1 file:text-[11px] file:font-semibold file:text-violet-700 hover:file:bg-violet-100 disabled:opacity-60">
                        <div class="mt-2 flex items-center justify-between gap-2">
                            <span class="text-[11px] text-slate-400">JPG/PNG maks. 2MB</span>
                            <button type="button" @click="remove(item)" x-show="item.src" :disabled="item.busy"
                                class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-rose-600 transition-colors hover:bg-rose-50 disabled:opacity-60">
                                <i class="bi bi-trash3"></i> Hapus
                            </button>
                        </div>
                        <p x-show="item.busy" x-cloak class="mt-2 flex items-center gap-1.5 text-[11px] font-semibold text-violet-600">
                            <span class="inline-block h-3 w-3 animate-spin rounded-full border-2 border-violet-300 border-t-transparent"></span>
                            Mengunggah...
                        </p>
                    </div>
                </template>
            </div>

        </x-ui.card>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('settingPage', () => ({
            csrf: '',
            urls: {},
            satuanMap: {},
            media: {},
            uploads: [],
            saving: false,

            init() {
                const cfg = JSON.parse(this.$el.dataset.config || '{}');
                this.csrf = cfg.csrf || '';
                this.urls = cfg.urls || {};
                this.satuanMap = cfg.satuan || {};
                this.media = cfg.media || {};
                this.uploads = (cfg.uploads || []).map((item) => ({
                    field: item.field,
                    label: item.label,
                    relative: item.relative || '',
                    src: item.src || '',
                    preview: item.src || '',
                    objectUrl: '',
                    busy: false,
                }));
            },

            onJenjangChange(event) {
                const select = this.$refs.satuanSelect;
                if (!select) return;

                const list = this.satuanMap[event.target.value] || [];

                select.innerHTML = '<option value="" disabled selected>Pilih Satuan Pendidikan</option>';
                list.forEach((label, index) => {
                    const option = document.createElement('option');
                    option.value = String(index + 1);
                    option.textContent = label;
                    select.appendChild(option);
                });
            },

            async postForm(url, formData) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                return response.json();
            },

            async save(event) {
                if (this.saving) return;

                this.saving = true;
                Swal.fire({
                    text: 'Menyimpan...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                try {
                    const formData = new FormData(event.target);
                    // Path media (logo, cap, TTD, latar kartu) ikut disimpan bersama
                    // form pengaturan; nilainya dari unggahan di halaman ini atau DB.
                    Object.entries(this.media).forEach(([field, value]) => {
                        formData.append(field, value || '');
                    });

                    const result = await this.postForm(this.urls.save, formData);

                    if (result && result.status) {
                        await Swal.fire({
                            title: 'Sukses',
                            text: result.message || 'Pengaturan berhasil disimpan',
                            icon: 'success',
                            confirmButtonColor: '#3085d6',
                        });
                        window.location.reload();

                        return;
                    }

                    // Pesan server menyebut bagian mana yang tidak tersimpan
                    // (mis. pengaturan notifikasi), bukan sekadar "kesalahan".
                    Swal.fire('Gagal', result?.message || 'Terjadi kesalahan saat menyimpan', 'error');
                } catch (error) {
                    console.error(error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menyimpan pengaturan' });
                } finally {
                    this.saving = false;
                }
            },

            setPreview(item, file) {
                if (item.objectUrl) {
                    URL.revokeObjectURL(item.objectUrl);
                    item.objectUrl = '';
                }

                if (!file) {
                    item.preview = item.src;
                    return '';
                }

                item.objectUrl = URL.createObjectURL(file);
                item.preview = item.objectUrl;

                return item.objectUrl;
            },

            async upload(item, event) {
                const input = event.target;
                const file = input.files?.[0] || null;

                if (!file || item.busy) return;

                if (file.size > 2 * 1024 * 1024) {
                    input.value = '';
                    Swal.fire('Gagal', 'Ukuran file maksimum adalah 2MB', 'error');
                    return;
                }

                const objectUrl = this.setPreview(item, file);
                item.busy = true;

                const formData = new FormData();
                formData.append('logo', file, file.name);

                try {
                    const result = await this.postForm(this.urls.upload + '/' + item.field, formData);

                    if (result && result.status) {
                        item.src = result.src || '';
                        item.relative = result.relative_path || '';
                        item.preview = item.src;
                        this.media[item.field] = item.relative;

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'File berhasil diunggah',
                            timer: 1500,
                            showConfirmButton: false,
                        });
                    } else {
                        item.preview = item.src;
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: result?.message || 'Gagal mengunggah file',
                        });
                    }
                } catch (error) {
                    item.preview = item.src;
                    console.error(error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
                } finally {
                    if (objectUrl) {
                        URL.revokeObjectURL(objectUrl);
                    }
                    item.objectUrl = '';
                    input.value = '';
                    item.busy = false;
                }
            },

            async remove(item) {
                if (item.busy || !item.src) return;

                const konfirmasi = await Swal.fire({
                    title: 'Hapus File?',
                    text: 'File ini akan dihapus dari pengaturan sekolah',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                });

                if (!konfirmasi.isConfirmed) return;

                item.busy = true;

                const body = new FormData();
                body.append('_token', this.csrf);
                body.append('src', item.relative);
                body.append('field', item.field);

                try {
                    const result = await this.postForm(this.urls.delete, body);

                    if (result && result.status) {
                        this.setPreview(item, null);
                        item.src = '';
                        item.relative = '';
                        item.preview = '';
                        this.media[item.field] = '';

                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus!',
                            text: result.message || 'File berhasil dihapus',
                            timer: 1500,
                            showConfirmButton: false,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: result?.message || 'File gagal dihapus',
                        });
                    }
                } catch (error) {
                    console.error(error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan pada server.' });
                } finally {
                    item.busy = false;
                }
            },
        }));
    });
</script>
@endsection
