@extends('layouts.main')

@section('content')
@php
    $satuanPerJenjang = ['1' => ['SD', 'MI'], '2' => ['SMP', 'MTs'], '3' => ['SMA', 'MA', 'SMK']];

    $jadwalAwal = [];
    foreach ([1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'] as $hariKe => $namaHari) {
        $baris = null;
        foreach (($jadwal ?? []) as $j) {
            if ((int) $j->hari === $hariKe) {
                $baris = $j;
                break;
            }
        }
        $jadwalAwal[$hariKe] = [
            'masuk' => substr((string) ($baris->jam_masuk ?? '07:30'), 0, 5),
            'pulang' => substr((string) ($baris->jam_pulang ?? '15:00'), 0, 5),
            'libur' => (bool) ($baris ? $baris->is_libur : ($hariKe === 7 ? 1 : 0)),
        ];
    }

    $mediaUploads = [
        ['field' => 'logo_kiri', 'label' => 'Logo Kiri / Aplikasi', 'desc' => 'Logo sidebar & navbar'],
        ['field' => 'logo_kanan', 'label' => 'Logo Kanan / Yayasan', 'desc' => 'Logo instansi pembina'],
        ['field' => 'stampel', 'label' => 'Cap / Stempel Sekolah', 'desc' => 'Stempel resmi dokumen (PNG)'],
        ['field' => 'tanda_tangan', 'label' => 'TTD Kepala Sekolah', 'desc' => 'Tanda tangan digital kepsek'],
        ['field' => 'kopsekolah', 'label' => 'Kop Surat Sekolah', 'desc' => 'Kop resmi cetak rapor & surat'],
        ['field' => 'kartu_bg_depan', 'label' => 'Latar Kartu Depan', 'desc' => 'Latar PNG kartu siswa sisi depan (rasio 54:85,6)'],
        ['field' => 'kartu_bg_belakang', 'label' => 'Latar Kartu Belakang', 'desc' => 'Latar PNG kartu siswa sisi belakang'],
    ];

    $mediaAwal = [];
    foreach ($mediaUploads as $item) {
        // Tampilkan preview hanya bila file benar-benar ada di disk.
        $rawPath = trim((string) ($setting->{$item['field']} ?? ''));
        $relPath = ltrim(str_replace(url('/').'/', '', $rawPath), '/');
        $mediaAwal[$item['field']] = ($rawPath !== '' && strpos($relPath, '..') === false
            && strpos($relPath, 'uploads/') === 0
            && is_file(public_path().'/'.$relPath))
            ? url($relPath)
            : '';
    }

    $waModeAwal = strtolower(trim((string) ($absenSetting->wa_gateway_mode ?? '')));
    if ($waModeAwal === '') {
        // Instalasi lama hanya punya Fonnte — jangan diam-diam jadi "off".
        $waModeAwal = (! empty($absenSetting->wa_token) && ! empty($absenSetting->wa_target_number)) ? 'fonnte' : 'off';
    }

    $teguranFormat = (string) ($setting->surat_teguran_format ?? \App\Libraries\SuratNomor::DEFAULT_FORMAT);
    $teguranPadding = (int) ($setting->surat_teguran_padding ?? \App\Libraries\SuratNomor::DEFAULT_PADDING);
    if ($teguranPadding < 1 || $teguranPadding > 6) {
        $teguranPadding = \App\Libraries\SuratNomor::DEFAULT_PADDING;
    }

    $pageConfig = [
        'csrf' => csrf_token(),
        'urls' => [
            'profil' => url('settings/saveSetting'),
            'setting' => url('admin/presensi/setting/save'),
            'upload' => url('settings/uploadFile'),
            'hapusFile' => url('settings/deleteFile'),
            'testNotif' => url('admin/presensi/test-notification'),
            'cleanPhotos' => url('admin/presensi/clean-photos'),
            'saveTheme' => url('settings/saveTheme'),
        ],
        'satuan' => $satuanPerJenjang,
        'jenjang' => (string) ($setting->jenjang ?? '3'),
        'satuanAwal' => (string) ($setting->satuan_pendidikan ?? '1'),
        'jadwal' => $jadwalAwal,
        'media' => $mediaAwal,
        'grace' => (int) ($absenSetting->grace_period ?? 15),
        'threshold' => (float) ($absenSetting->face_threshold ?? 0.72),
        'faceMode' => (string) ($absenSetting->face_match_mode ?? 'ai_match'),
        'simMenit' => 45,
        'tiers' => [
            ['denda' => (int) ($absenSetting->denda_tier_1 ?? 100), 'poin' => (int) ($absenSetting->point_tier_1 ?? 1)],
            ['denda' => (int) ($absenSetting->denda_tier_2 ?? 200), 'poin' => (int) ($absenSetting->point_tier_2 ?? 2)],
            ['denda' => (int) ($absenSetting->denda_tier_3 ?? 500), 'poin' => (int) ($absenSetting->point_tier_3 ?? 5)],
        ],
        'waMode' => $waModeAwal,
        'wfh' => (bool) ($absenSetting->wfh_enabled ?? 0),
        'qr' => (bool) ($absenSetting->qr_scan_aktif ?? 1),
        'teguran' => [
            'format' => $teguranFormat,
            'padding' => $teguranPadding,
            'kode' => (string) ($setting->kode_sekolah ?? \App\Libraries\SuratNomor::DEFAULT_KODE_SEKOLAH),
            'defaultFormat' => \App\Libraries\SuratNomor::DEFAULT_FORMAT,
            'defaultPadding' => \App\Libraries\SuratNomor::DEFAULT_PADDING,
            'defaultKode' => \App\Libraries\SuratNomor::DEFAULT_KODE_SEKOLAH,
            'sekolah' => (string) ($setting->sekolah ?? ''),
            'npsn' => (string) ($setting->npsn ?? ''),
            'nss' => (string) ($setting->nss ?? ''),
            'kepsek' => (string) ($setting->kepsek ?? ''),
            'nip' => (string) ($setting->nip ?? ''),
        ],
        'theme' => [
            'preset' => (string) ($theme['preset'] ?? 'default'),
            'custom' => (string) (! empty($theme['custom']) ? $theme['custom'] : '#2563eb'),
            'client' => \App\Libraries\Theme::clientConfig(),
        ],
        'accentShift' => \App\Libraries\Theme::CUSTOM_ACCENT_HUE_SHIFT,
        'tabAwal' => 'profil',
    ];
@endphp

<div class="space-y-4" x-data="settingPage(@js($pageConfig))">

    <x-ui.page-header
        title="Pengaturan Sistem"
        icon="bi-sliders"
        tone="blue"
        subtitle="Kelola identitas sekolah, jadwal kerja, kebijakan presensi, notifikasi, tema, dan pemeliharaan data."
        :crumbs="['Pengaturan', 'Sistem']">
        <x-slot:actions>
            <a href="{{ url('dashboard') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-100">
                <i class="bi bi-arrow-left"></i> Dashboard
            </a>
        </x-slot:actions>
    </x-ui.page-header>

        <div class="w-full">

            <!-- Navigasi tab -->
            <div class="sticky top-0 z-20 mb-6 overflow-x-auto rounded-2xl border border-slate-200/80 bg-slate-100/95 p-1.5 shadow-inner backdrop-blur">
                <div class="flex min-w-max items-center gap-1.5" role="tablist">
                    <template x-for="t in tabOpsi" :key="t.key">
                        <button type="button" role="tab" @click="pilihTab(t.key)"
                            :aria-selected="tab === t.key ? 'true' : 'false'"
                            :class="tab === t.key ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:bg-white/60 hover:text-slate-800'"
                            class="flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-semibold transition-all">
                            <i class="bi" :class="t.icon"></i>
                            <span x-text="t.label"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="pt-1">
                
                <!-- ==================================================== -->
                <!-- TAB 1: PROFIL & IDENTITAS SEKOLAH                    -->
                <!-- ==================================================== -->
                <div class="space-y-6" x-show="tab === 'profil'" x-cloak id="profil" role="tabpanel">
                    <?php
                    $bankSoalDateTime = '';
                    if (! empty($setting->ba_waktu) && $setting->ba_waktu !== '0000-00-00 00:00:00') {
                        $dtObj = DateTime::createFromFormat('Y-m-d H:i:s', (string) $setting->ba_waktu);
                        if ($dtObj instanceof DateTime) {
                            $bankSoalDateTime = $dtObj->format('Y-m-d\TH:i');
                        }
                    }
                    ?>
                    
                    <!-- Form Text Identitas -->
                    <form class="space-y-6" x-ref="profilForm" @submit.prevent="simpanProfil()">
                        @csrf
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            <!-- Card 1: Identitas Pokok Sekolah -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-blue-100/80 rounded-xl p-2.5 text-blue-600">
                                            <i class="bi bi-building text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Identitas Lembaga &amp; Aplikasi</h6>
                                            <p class="text-xs text-slate-500 mb-0">Nama resmi sekolah dan jenjang satuan pendidikan</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-700 text-[10px]">Wajib</span>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama Aplikasi <span class="text-red-500">*</span></label>
                                        <input type="text" name="nama_aplikasi" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->nama_aplikasi ?? 'absensi by ikasmedia' }}" required>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama Sekolah / Madrasah <span class="text-red-500">*</span></label>
                                        <input type="text" name="nama_sekolah" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->sekolah ?? '' }}" required>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">NPSN</label>
                                            <input type="text" name="npsn" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->npsn ?? '' }}" pattern="[0-9]{8}" title="8 digit angka" placeholder="Contoh: 12345678">
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">NSS / NSM</label>
                                            <input type="text" name="nss" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->nss ?? '' }}" placeholder="Nomor statistik sekolah">
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Kode Sekolah</label>
                                            <input type="text" name="kode_sekolah" x-model="teguran.kode" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->kode_sekolah ?? \App\Libraries\SuratNomor::DEFAULT_KODE_SEKOLAH }}" maxlength="50" placeholder="SMAN2.AT">
                                            <span class="text-xs text-slate-400 mt-1 block">Dipakai pada nomor surat teguran.</span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Jenjang <span class="text-red-500">*</span></label>
                                            <select class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="jenjang" x-model="jenjang" required>
                                                <option value="1">SD / MI</option>
                                                <option value="2">SMP / MTs</option>
                                                <option value="3">SMA / MA / SMK</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Satuan Pendidikan <span class="text-red-500">*</span></label>
                                            <select class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="satuan_pendidikan" x-model="satuan" required>
                                                <template x-for="s in satuanOpsi" :key="s.value">
                                                    <option :value="s.value" x-text="s.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 2: Alamat & Wilayah -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-indigo-100/80 rounded-xl p-2.5 text-indigo-600">
                                            <i class="bi bi-geo-alt text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Alamat &amp; Domisili</h6>
                                            <p class="text-xs text-slate-500 mb-0">Lokasi geografis fisik instansi</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Alamat Lengkap Jalan <span class="text-red-500">*</span></label>
                                        <textarea name="alamat" rows="2" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" placeholder="Nama jalan, nomor, RT/RW" required>{{ $setting->alamat ?? '' }}</textarea>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Desa / Kelurahan <span class="text-red-500">*</span></label>
                                            <input type="text" name="desa" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->desa ?? '' }}" required>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Kecamatan <span class="text-red-500">*</span></label>
                                            <input type="text" name="kec" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->kecamatan ?? '' }}" required>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Kabupaten / Kota <span class="text-red-500">*</span></label>
                                            <input type="text" name="kota" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->kota ?? '' }}" required>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Provinsi <span class="text-red-500">*</span></label>
                                            <input type="text" name="provinsi" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->provinsi ?? '' }}" required>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Kode Pos</label>
                                            <input type="text" name="kode_pos" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->kode_pos ?? '' }}" placeholder="5 digit">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 3: Kontak Resmi -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-emerald-100/80 rounded-xl p-2.5 text-emerald-600">
                                            <i class="bi bi-telephone text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Kontak Resmi Lembaga</h6>
                                            <p class="text-xs text-slate-500 mb-0">Saluran telepon, email, dan situs web sekolah</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nomor Telepon</label>
                                            <input type="tel" name="tlp" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->telp ?? '' }}" placeholder="021-xxxxxx">
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Faksimili</label>
                                            <input type="text" name="fax" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->fax ?? '' }}" placeholder="Nomor fax">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Email Resmi</label>
                                            <input type="email" name="email" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->email ?? '' }}" placeholder="admin@sekolah.sch.id">
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Website Resmi</label>
                                            <input type="url" name="web" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->web ?? '' }}" placeholder="https://sekolah.sch.id">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 4: Penanggung Jawab & Parameter absensi by ikasmedia/AI -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-purple-100/80 rounded-xl p-2.5 text-purple-600">
                                            <i class="bi bi-person-badge text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Pimpinan &amp; Opsi Sistem</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Kepala Sekolah <span class="text-red-500">*</span></label>
                                            <input type="text" name="kepsek" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->kepsek ?? '' }}" required>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">NIP Kepala Sekolah</label>
                                            <input type="text" name="nip" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->nip ?? '' }}" placeholder="NIP / NUPTK">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">API Key AI (OpenAI / Gemini)</label>
                                        <input type="password" name="api_key" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" value="{{ $setting->api_key ?? '' }}" placeholder="sk-...">
                                        <span class="text-xs text-slate-400 mt-1 block">Digunakan untuk fitur pembuatan soal otomatis berbasis kecerdasan buatan.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 5: Format Penomoran Surat Teguran -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden lg:col-span-2">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-amber-100/80 rounded-xl p-2.5 text-amber-600">
                                            <i class="bi bi-upc-scan text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Format Penomoran Surat Teguran</h6>
                                            <p class="text-xs text-slate-500 mb-0">Atur pola nomor surat yang tercetak pada PDF dan daftar surat teguran</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-700 text-[10px]">Surat Teguran</span>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Format Nomor</label>
                                            <input type="text" name="surat_teguran_format" x-ref="teguranFormat" x-model="teguran.format"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-mono text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" maxlength="120"
                                                value="{{ $teguranFormat }}"
                                                placeholder="{{ \App\Libraries\SuratNomor::DEFAULT_FORMAT }}">
                                            <span class="text-xs text-slate-400 mt-1 block">Gunakan placeholder di bawah. Kosongkan untuk memakai format bawaan.</span>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Jumlah Digit Nomor Urut</label>
                                            <select name="surat_teguran_padding" x-model.number="teguran.padding"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                                @for ($p = 1; $p <= 6; $p++)
                                                    <option value="{{ $p }}">{{ $p }} digit{{ $p === 3 ? ' (standar)' : '' }}</option>
                                                @endfor
                                            </select>
                                            <span class="text-xs text-slate-400 mt-1 block">Contoh: 3 digit menghasilkan 001, 002, 003.</span>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Pratinjau Nomor</label>
                                        <div class="break-all rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm text-slate-800" x-text="teguranPreview"></div>
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Placeholder yang Tersedia</label>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach (\App\Libraries\SuratNomor::placeholders() as $ph => $desc)
                                                <button type="button" @click="sisipPlaceholder(@js($ph))"
                                                    class="rounded-lg border border-slate-200 bg-slate-100 px-2.5 py-1 font-mono text-[11px] text-slate-600 transition-colors hover:bg-blue-50 hover:text-blue-700"
                                                    title="{{ $desc }}">{{ $ph }}</button>
                                            @endforeach
                                        </div>
                                        <span class="text-xs text-slate-400 mt-2 block">Klik placeholder untuk menyisipkannya ke field format. Perubahan berlaku untuk surat yang terbit setelah disimpan.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Bar Simpan Profil -->
                        <div class="flex justify-end pt-2">
                            <button type="submit" :disabled="menyimpanProfil"
                                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-md shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-show="menyimpanProfil" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                <i x-show="!menyimpanProfil" class="bi bi-save"></i>
                                <span x-text="menyimpanProfil ? 'Menyimpan…' : 'Simpan Profil Sekolah'"></span>
                            </button>
                        </div>
                    </form>

                    <!-- Card: Identitas Visual & Media Upload -->
                    <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden mt-8 mb-6">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                            <div class="flex items-center gap-3">
                                <div class="bg-amber-100/80 rounded-xl p-2.5 text-amber-600">
                                    <i class="bi bi-images text-lg"></i>
                                </div>
                                <div>
                                    <h6 class="font-bold text-slate-800 mb-0">Identitas Visual &amp; Dokumen Branding</h6>
                                    <p class="text-xs text-slate-500 mb-0">Unggah logo resmi, cap stempel, tanda tangan digital, dan kop surat sekolah (Format: JPG/PNG/WEBP, Maks. 5MB)</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-700 text-[10px]">Realtime Upload</span>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                @foreach ($mediaUploads as $item)
                                    @php $field = $item['field']; @endphp
                                    <div class="flex flex-col justify-between rounded-xl border border-slate-200/80 bg-slate-50 p-3.5 transition-colors hover:border-slate-300">
                                        <div class="mb-2.5">
                                            <label class="mb-0.5 block text-xs font-bold text-slate-800">{{ $item['label'] }}</label>
                                            <span class="block truncate text-[11px] text-slate-400">{{ $item['desc'] }}</span>
                                        </div>
                                        <div class="mb-2 flex h-24 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-white">
                                            <img x-show="media['{{ $field }}']" :src="media['{{ $field }}']" alt="{{ $item['label'] }}"
                                                class="h-full w-full object-contain p-1">
                                            <span x-show="!media['{{ $field }}']" class="text-[11px] text-slate-400">Belum ada file</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <label class="cursor-pointer rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-600 transition-colors hover:bg-slate-100">
                                                <i class="bi bi-upload me-1"></i>Pilih File
                                                <input type="file" class="hidden" accept="image/png,image/jpeg,image/webp"
                                                    @change="unggahMedia($event, '{{ $field }}')">
                                            </label>
                                            <button type="button" x-show="media['{{ $field }}']" x-cloak
                                                @click="hapusMedia('{{ $field }}')"
                                                class="rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-red-600 transition-colors hover:bg-red-50">
                                                <i class="bi bi-trash me-1"></i>Hapus
                                            </button>
                                            <span x-show="mediaLoading['{{ $field }}']" x-cloak class="text-[11px] text-slate-400">Mengunggah…</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Setting Presensi Wrapping Tab 2 to 6 -->
                <form autocomplete="off" x-ref="settingForm" @submit.prevent="simpanPengaturan()">
                    @csrf

                    <!-- ==================================================== -->
                    <!-- TAB 2: JADWAL KERJA & LOKASI GEOFENCE                -->
                    <!-- ==================================================== -->
                    <div class="space-y-6" x-show="tab === 'jadwal'" x-cloak id="jadwal" role="tabpanel">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            
                            <!-- Jadwal Kerja Mingguan Table -->
                            <div class="lg:col-span-2">
                                <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-2 flex-wrap bg-slate-50/50">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-blue-100/80 rounded-xl p-2.5 text-blue-600">
                                                <i class="bi bi-calendar-check text-lg"></i>
                                            </div>
                                            <div>
                                                <h6 class="font-bold text-slate-800 mb-0">Jadwal Kerja Mingguan GTK</h6>
                                                <p class="text-xs text-slate-500 mb-0">Atur jam masuk, jam pulang, dan status hari libur</p>
                                            </div>
                                        </div>
                                        <button type="button" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-blue-200 bg-white px-3 py-1.5 text-[11px] font-bold text-blue-700 transition-colors hover:bg-blue-50" @click="terapkanSenin()">
                                            <i class="bi bi-copy mr-1"></i> Terapkan Senin ke Semua
                                        </button>
                                    </div>
                                    <div class="p-0">
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                                    <tr>
                                                        <th class="w-32">Hari</th>
                                                        <th>Jam Masuk</th>
                                                        <th>Jam Pulang</th>
                                                        <th class="text-center w-28">Status Libur</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    <?php
                                                        $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                    foreach ($days as $k => $dayName) {
                        $row = null;
                        if (isset($jadwal)) {
                            foreach ($jadwal as $j) {
                                if ($j->hari == $k) {
                                    $row = $j;
                                    break;
                                }
                            }
                        }
                        $jamMasuk = $row ? $row->jam_masuk : '07:30';
                        $jamPulang = $row ? $row->jam_pulang : '15:00';
                        $isLibur = $row ? $row->is_libur : ($k == 7 ? 1 : 0);
                        ?>
                                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                                        <td class="font-semibold text-slate-800 flex items-center gap-2">
                                                            <span class="w-2 h-2 rounded-full {{ $k == 7 ? 'bg-red-500' : 'bg-blue-500' }}"></span>
                                                            {{ $dayName }}
                                                        </td>
                                                        <td>
                                                            <input type="time" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" 
                                                                name="jadwal[{{ $k }}][jam_masuk]" x-model="jadwal[{{ $k }}].masuk" value="{{ substr($jamMasuk, 0, 5) }}">
                                                        </td>
                                                        <td>
                                                            <input type="time" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" 
                                                                name="jadwal[{{ $k }}][jam_pulang]" x-model="jadwal[{{ $k }}].pulang" value="{{ substr($jamPulang, 0, 5) }}">
                                                        </td>
                                                        <td class="text-center">
                                                            <label class="relative my-auto inline-flex h-6 w-11 shrink-0 cursor-pointer items-center"><input type="checkbox" class="peer sr-only" name="jadwal[{{ $k }}][is_libur]" value="1" x-model="jadwal[{{ $k }}].libur"><span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-600"></span><span class="absolute left-[3px] h-[18px] w-[18px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span></label>
                                                        </td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="px-6 py-3 bg-blue-50/60 border-t border-blue-100 flex items-center gap-2 text-xs text-blue-700">
                                        <i class="bi bi-info-circle shrink-0"></i>
                                        Pada hari yang ditandai <b>Libur</b>, guru tidak diwajibkan presensi dan tidak akan terkena sanksi alpa/denda.
                                    </div>
                                </div>
                            </div>

                            <!-- Toleransi & Geofence GPS. Status hari libur TIDAK
                                 ada di sini: diatur lewat toggle "Status Libur" pada
                                 Jadwal Kerja Mingguan GTK di atas (satu sumber
                                 kebenaran), lalu absensi_setting.hari_libur_mingguan
                                 diturunkan otomatis saat disimpan. -->
                            <div class="space-y-6">
                                <!-- Toleransi Batas Waktu Keterlambatan (Grace Period) -->
                                <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden border-l-4 border-l-amber-500 shadow-sm">
                                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 bg-slate-50/50">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-amber-100/80 rounded-xl p-2.5 text-amber-600 shrink-0">
                                                <i class="bi bi-clock-history text-lg"></i>
                                            </div>
                                            <div>
                                                <h6 class="font-bold text-slate-800 mb-0">Toleransi Batas Waktu Absensi</h6>
                                                <p class="text-xs text-slate-500 mb-0">Kelonggaran menit sebelum absensi dinyatakan terlambat</p>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0">
                                            <i class="bi bi-stopwatch"></i> <span x-text="grace + ' Menit'">{{ (int) ($absenSetting->grace_period ?? 15) }} Menit</span>
                                        </span>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500" for="input_grace_period">Toleransi Keterlambatan (Grace Period)</label>
                                            <div class="flex items-center gap-2">
                                                <div class="relative flex-1">
                                                    <i class="bi bi-stopwatch absolute left-3 top-3 text-slate-400"></i>
                                                    <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 pl-9 font-bold text-slate-800 text-base" 
                                                        id="input_grace_period" name="grace_period" x-model.number="grace"
                                                        value="{{ $absenSetting->grace_period ?? 15 }}" min="0" max="120" step="1" required>
                                                </div>
                                                <span class="text-sm font-semibold text-slate-600 bg-slate-100 px-3.5 py-2 rounded-xl border border-slate-200 shrink-0">Menit</span>
                                            </div>
                                            <p class="text-xs text-slate-400 mt-1.5 mb-0">Atur 0 jika ingin langsung tercatat terlambat tepat pada jam masuk tanpa toleransi.</p>
                                        </div>

                                        <!-- Simulasi Interaktif Perhitungan -->
                                        <div class="p-4 rounded-2xl border border-amber-200/80 bg-amber-50/70 text-xs text-amber-900 space-y-2.5">
                                            <div class="font-bold flex items-center gap-1.5 text-amber-800 text-xs">
                                                <i class="bi bi-info-circle-fill text-amber-600"></i> Contoh Simulasi Batas Waktu Masuk:
                                            </div>
                                            <div class="bg-white/90 rounded-xl p-3 border border-amber-200/80 text-slate-700 leading-relaxed font-mono text-[11px] space-y-1">
                                                <div class="text-slate-600 font-sans font-semibold pb-1 border-b border-slate-100 flex items-center justify-between">
                                                    <span>Jam Masuk: <strong class="text-slate-800" x-text="jadwal[1].masuk || '07:15'">07:15</strong></span>
                                                    <span>Toleransi: <strong class="text-amber-700" x-text="grace + ' Menit'">{{ (int) ($absenSetting->grace_period ?? 15) }} Menit</strong></span>
                                                </div>
                                                <div class="pt-1 flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                    <span>Absensi s/d <strong class="font-bold font-mono text-emerald-700" x-text="batasOntime">07:30</strong>:</span>
                                                    <span class="inline-block px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-sans font-bold">Tepat Waktu (Belum Terlambat)</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                                    <span>Absensi mulai <strong class="font-bold font-mono text-red-700" x-text="batasTelat">07:31</strong>:</span>
                                                    <span class="inline-block px-1.5 py-0.5 rounded bg-red-100 text-red-800 text-[10px] font-sans font-bold">Terlambat</span>
                                                </div>
                                            </div>
                                            <p class="text-[11px] text-amber-700 mb-0 font-sans">
                                                Aturan batas toleransi ini otomatis tersinkronisasi ke seluruh sistem presensi (GTK dan Siswa).
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Radius & Geofence GPS -->
                                <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                    <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                        <div class="bg-rose-100/80 rounded-xl p-2 text-rose-600">
                                            <i class="bi bi-geo-alt-fill text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Radius &amp; Geofence GPS</h6>
                                            <p class="text-xs text-slate-500 mb-0">Toleransi jarak presensi dari titik sekolah</p>
                                        </div>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Toleransi Jarak Absen</label>
                                            <div class="flex items-center gap-2">
                                                <div class="relative flex-1">
                                                    <i class="bi bi-broadcast absolute left-3 top-3 text-slate-400"></i>
                                                    <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 pl-9" name="radius_meter" 
                                                        value="{{ $absenSetting->radius_meter ?? 100 }}" min="10" max="5000" required>
                                                </div>
                                                <span class="text-sm font-semibold text-slate-600 bg-slate-100 px-3 py-2 rounded-xl border border-slate-200">Meter</span>
                                            </div>
                                            <span class="text-xs text-slate-400 mt-1 block">Jarak maksimal guru dari koordinat sekolah (standar: 100m).</span>
                                        </div>

                                        <div class="p-4 rounded-xl border border-blue-100 bg-blue-50/50">
                                            <div class="font-semibold text-slate-800 text-xs mb-1 flex items-center gap-1.5">
                                                <i class="bi bi-pin-map text-blue-600"></i> Titik Lokasi Kampus / Kantor
                                            </div>
                                            <p class="text-xs text-slate-500 mb-3">Kelola koordinat GPS multi-lokasi kantor cabang atau kampus pembelajaran.</p>
                                            <a href="{{ url('admin/presensi/lokasi') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl px-3 py-1.5 text-[11px] font-bold transition-colors no-underline disabled:cursor-not-allowed disabled:opacity-50 bg-blue-600 text-white hover:bg-blue-700 w-full">
                                                <i class="bi bi-map mr-1"></i> Kelola Titik Lokasi ({{ count($locations ?? []) }} Lokasi)
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Integrasi Jadwal & Jurnal Mengajar -->
                        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden mt-6">
                            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                <div class="bg-indigo-100/80 rounded-xl p-2.5 text-indigo-600">
                                    <i class="bi bi-journal-check text-lg"></i>
                                </div>
                                <div>
                                    <h6 class="font-bold text-slate-800 mb-0">Integrasi Jadwal &amp; Jurnal Mengajar</h6>
                                    <p class="text-xs text-slate-500 mb-0">Syarat jurnal sebelum guru diizinkan absen pulang</p>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                    <div class="pr-4">
                                        <span class="font-bold block text-sm text-slate-800">Wajib Isi Jurnal Sebelum Absen Pulang</span>
                                        <span class="text-xs text-slate-500">
                                            Guru yang memiliki jadwal mengajar hari ini wajib mengisi jurnal untuk setiap sesi yang sudah selesai sebelum absen pulang.
                                            Guru tanpa jadwal, sesi yang belum selesai, hari libur, dan absen Dinas dikecualikan.
                                        </span>
                                    </div>
                                    <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center shrink-0 ml-3">
                                        <input type="checkbox" class="peer sr-only" name="wajib_jurnal_sebelum_pulang" value="1" {{ (int) ($absenSetting->wajib_jurnal_sebelum_pulang ?? 1) === 1 ? 'checked' : '' }}>
                                        <span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-600"></span><span class="absolute left-[3px] h-[18px] w-[18px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 mt-3 ml-4 sm:ml-6 rounded-xl border border-slate-200 bg-white">
                                    <div class="pr-4">
                                        <span class="font-bold block text-sm text-slate-800">Jurnal Harus Sudah Divalidasi</span>
                                        <span class="text-xs text-slate-500">
                                            Hanya jurnal berstatus <strong>Disetujui</strong> yang dihitung memenuhi syarat; jurnal yang masih
                                            menunggu validasi atau ditolak tetap menahan absen pulang.
                                            Yang memvalidasi adalah guru piket hari itu, jadi pastikan jadwal piket terisi &mdash; tanpa itu tidak ada
                                            guru yang bisa pulang meski jurnalnya sudah diisi.
                                            Aktif hanya bila sakelar di atas juga aktif.
                                        </span>
                                    </div>
                                    <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center shrink-0 ml-3">
                                        <input type="checkbox" class="peer sr-only" name="wajib_jurnal_tervalidasi_sebelum_pulang" value="1" {{ (int) ($absenSetting->wajib_jurnal_tervalidasi_sebelum_pulang ?? 0) === 1 ? 'checked' : '' }}>
                                        <span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-600"></span><span class="absolute left-[3px] h-[18px] w-[18px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================================================== -->
                    <!-- TAB 3: VERIFIKASI BIOMETRIK & KAMERA                 -->
                    <!-- ==================================================== -->
                    <div class="space-y-6" x-show="tab === 'wajah'" x-cloak id="wajah" role="tabpanel">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            <!-- AI Face Recognition -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-cyan-100/80 rounded-xl p-2.5 text-cyan-600">
                                            <i class="bi bi-person-bounding-box text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Biometrik Wajah AI (Face Recognition)</h6>
                                            <p class="text-xs text-slate-500 mb-0">Model neural network pencocokan wajah guru</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold bg-sky-100 text-sky-700 text-[10px]">ResNet-34 AI</span>
                                </div>
                                <div class="p-6 space-y-5">
                                    <div class="flex items-start gap-2 rounded-xl border px-3.5 py-2.5 text-xs leading-relaxed border-blue-200 bg-blue-50 text-blue-800 flex items-center gap-3">
                                        <i class="bi bi-cpu text-2xl text-blue-600 shrink-0"></i>
                                        <div class="text-xs">
                                            Sistem menggunakan modul <b>Face-API AI (128D Face Descriptor)</b> untuk membandingkan wajah selfie dengan foto profil terdaftar secara real-time di browser.
                                        </div>
                                    </div>

                                    <!-- Mode Verifikasi Radio Cards -->
                                    <div>
                                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Mode Kamera Face ID</label>
                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                            <label class="block cursor-pointer rounded-xl border-2 p-3.5 transition-all"
                                                :class="faceMode === 'ai_match' ? 'border-blue-500 bg-blue-50/60 text-blue-900' : 'border-slate-200 hover:border-slate-300'">
                                                <input class="sr-only" type="radio" name="face_match_mode" value="ai_match" x-model="faceMode">
                                                <div class="font-bold text-sm flex items-center gap-2">
                                                    <i class="bi bi-shield-check text-blue-600"></i> Selfie + AI Match
                                                </div>
                                                <span class="text-xs text-slate-500 block mt-1">Verifikasi kecocokan wajah selfie dengan foto profil guru secara biometrik.</span>
                                            </label>
                                            <label class="block cursor-pointer rounded-xl border-2 p-3.5 transition-all"
                                                :class="faceMode === 'selfie_only' ? 'border-blue-500 bg-blue-50/60 text-blue-900' : 'border-slate-200 hover:border-slate-300'">
                                                <input class="sr-only" type="radio" name="face_match_mode" value="selfie_only" x-model="faceMode">
                                                <div class="font-bold text-sm flex items-center gap-2">
                                                    <i class="bi bi-camera text-slate-600"></i> Hanya Foto Selfie
                                                </div>
                                                <span class="text-xs text-slate-500 block mt-1">Ambil bukti foto selfie saja tanpa komparasi embedding wajah.</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Sensitivitas / Threshold -->
                                    <div>
                                        <div class="mb-2 flex items-center justify-between">
                                            <label class="block text-xs font-bold uppercase tracking-wide text-slate-500">Toleransi Kemiripan (Match Threshold)</label>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 font-mono text-xs font-bold text-blue-700" x-text="threshold.toFixed(2)">0.72</span>
                                        </div>

                                        <input type="range" class="w-full cursor-pointer accent-blue-600" min="0.55" max="0.85" step="0.01"
                                            x-model.number="threshold">
                                        <input type="hidden" name="face_threshold" :value="threshold">

                                        <div class="flex justify-between text-[11px] text-slate-400 mt-1 mb-3">
                                            <span>0.55 (Ketat)</span>
                                            <span class="font-bold text-blue-600">0.72 (Rekomendasi)</span>
                                            <span>0.85 (Longgar)</span>
                                        </div>

                                        <!-- Quick Presets -->
                                        <div class="flex gap-2 flex-wrap">
                                            <template x-for="p in thresholdPreset" :key="p.nilai">
                                                <button type="button" @click="threshold = p.nilai"
                                                    :class="Math.abs(threshold - p.nilai) < 0.01 ? 'bg-blue-600 text-white' : 'border border-slate-200 bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                                    class="rounded-lg px-3 py-1 text-[11px] font-bold transition-colors">
                                                    <span x-text="p.label"></span>
                                                </button>
                                            </template>
                                        </div>

                                        <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                                            <div class="font-semibold text-slate-800" :class="thresholdWarna" x-text="thresholdJudul"></div>
                                            <p class="mt-0.5 text-slate-600" x-text="thresholdDetail"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aturan Kamera & Foto Profil -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                    <div class="bg-indigo-100/80 rounded-xl p-2.5 text-indigo-600">
                                        <i class="bi bi-camera text-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="font-bold text-slate-800 mb-0">Kebijakan Kamera &amp; Foto Profil</h6>
                                        <p class="text-xs text-slate-500 mb-0">Ketentuan selfie pada jalur Face ID (absen QR tidak memakai kamera)</p>
                                    </div>
                                </div>
                                <div class="p-6 space-y-5">
                                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                        <div>
                                            <span class="font-bold block text-sm text-slate-800">Wajib Foto Selfie (Face ID)</span>
                                            <span class="text-xs text-slate-500">Berlaku hanya untuk absen masuk &amp; pulang via Face ID. Absen QR tidak memerlukan foto atau verifikasi wajah.</span>
                                        </div>
                                        <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center shrink-0 ml-3">
                                            <input type="checkbox" class="peer sr-only" name="foto_wajib" value="1" {{ ($absenSetting->foto_wajib ?? true) ? 'checked' : '' }}>
                                            <span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-600"></span><span class="absolute left-[3px] h-[18px] w-[18px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                                        </label>
                                    </div>

                                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                        <div>
                                            <span class="font-bold block text-sm text-slate-800">Verifikasi Wajah Saat WFH</span>
                                            <span class="text-xs text-slate-500">Wajib memverifikasi kecocokan wajah jika melakukan presensi dari rumah (WFH).</span>
                                        </div>
                                        <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center shrink-0 ml-3">
                                            <input type="checkbox" class="peer sr-only" name="wfh_wajib_wajah" value="1" {{ ($absenSetting->wfh_wajib_wajah ?? 1) ? 'checked' : '' }}>
                                            <span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-600"></span><span class="absolute left-[3px] h-[18px] w-[18px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                                        </label>
                                    </div>

                                    <div class="p-4 rounded-xl border border-indigo-100 bg-indigo-50/40">
                                        <div class="font-semibold text-slate-800 text-xs mb-1 flex items-center gap-1.5">
                                            <i class="bi bi-person-lines-fill text-indigo-600"></i> Pembaruan Foto Profil Guru
                                        </div>
                                        <p class="text-xs text-slate-500 mb-3">Pastikan guru memiliki foto profil jelas berlatar polos agar AI dapat mendeteksi landmark wajah secara akurat.</p>
                                        <a href="{{ url('dataguru') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl px-3 py-1.5 text-[11px] font-bold transition-colors no-underline disabled:cursor-not-allowed disabled:opacity-50 border border-blue-200 bg-white text-blue-700 hover:bg-blue-50">
                                            <i class="bi bi-people mr-1"></i> Buka Manajemen Data Guru
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================================================== -->
                    <!-- TAB 4: POIN, REWARD & SANKSI DENDA                   -->
                    <!-- ==================================================== -->
                    <div class="space-y-6" x-show="tab === 'poin'" x-cloak id="poin" role="tabpanel">
                        <div class="space-y-6">
                            
                            <!-- 1. Poin Dasar Kehadiran (Flat) -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-emerald-100/80 rounded-xl p-2.5 text-emerald-600">
                                            <i class="bi bi-plus-circle-fill text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">1. Poin Kehadiran Harian (Reward Flat)</h6>
                                            <p class="text-xs text-slate-500 mb-0">Poin tambahan yang diperoleh guru berdasarkan status kehadiran</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Hadir Tepat Waktu</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold text-emerald-600" name="point_hadir" 
                                                    value="{{ $absenSetting->point_hadir ?? 10 }}" min="0">
                                                <span class="text-xs font-semibold text-slate-500">Poin</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Izin Resmi</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="point_izin" 
                                                    value="{{ $absenSetting->point_izin ?? 0 }}" min="0">
                                                <span class="text-xs font-semibold text-slate-500">Poin</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Sakit Sah</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="point_sakit" 
                                                    value="{{ $absenSetting->point_sakit ?? 0 }}" min="0">
                                                <span class="text-xs font-semibold text-slate-500">Poin</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Cuti Disetujui</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="point_cuti" 
                                                    value="{{ $absenSetting->point_cuti ?? 0 }}" min="0">
                                                <span class="text-xs font-semibold text-slate-500">Poin</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Reward Datang Lebih Awal (Early Bird) -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-blue-100/80 rounded-xl p-2.5 text-blue-600">
                                            <i class="bi bi-award-fill text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">2. Reward Datang Lebih Awal (Early Bird Points)</h6>
                                            <p class="text-xs text-slate-500 mb-0">Apresiasi kedisiplinan guru yang hadir sebelum jam masuk dimulai</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tier 1 (1 - 15 Mnt Awal)</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="reward_point_tier_1" 
                                                    value="{{ $absenSetting->reward_point_tier_1 ?? 1 }}" min="0">
                                                <span class="text-xs font-semibold text-slate-500">pt/mnt</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tier 2 (16 - 60 Mnt Awal)</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="reward_point_tier_2" 
                                                    value="{{ $absenSetting->reward_point_tier_2 ?? 1 }}" min="0">
                                                <span class="text-xs font-semibold text-slate-500">pt/mnt</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tier 3 (&gt; 60 Mnt Awal)</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="reward_point_tier_3" 
                                                    value="{{ $absenSetting->reward_point_tier_3 ?? 2 }}" min="0">
                                                <span class="text-xs font-semibold text-slate-500">pt/mnt</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Batas Maksimal Reward</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="reward_cap_menit" 
                                                    value="{{ $absenSetting->reward_cap_menit ?? 30 }}" min="0">
                                                <span class="text-xs font-semibold text-slate-500">Menit</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Sanksi & Poin Pelanggaran -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-red-100/80 rounded-xl p-2.5 text-red-600">
                                            <i class="bi bi-dash-circle-fill text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">3. Poin Pelanggaran Dasar (Penalty Points)</h6>
                                            <p class="text-xs text-slate-500 mb-0">Pengurangan poin kedisiplinan per kejadian pelanggaran</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Terlambat Masuk (LATE)</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold text-amber-600" name="point_telat" 
                                                    value="{{ $absenSetting->point_telat ?? 1 }}" min="0">
                                                <span class="text-xs font-semibold text-slate-500">pt / kejadian</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Alpha (Tanpa Keterangan)</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold text-red-600" name="point_alpa" 
                                                    value="{{ $absenSetting->point_alpa ?? 5 }}" min="0">
                                                <span class="text-xs font-semibold text-slate-500">pt / hari</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tidak Absen Pulang (Lupa Check-Out)</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold text-red-600" name="point_lupa_pulang" 
                                                    value="{{ $absenSetting->point_lupa_pulang ?? 5 }}" min="0" max="100">
                                                <span class="text-xs font-semibold text-slate-500">pt / hari</span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-3 mb-0">
                                        Poin pulang lebih awal mengikuti tier pada tabel "Tarif Progresif" di bawah, bukan tarif terpisah.
                                    </p>
                                    <p class="text-[11px] text-slate-400 mt-1.5 mb-0">
                                        "Tidak absen pulang" dipotong cron harian untuk hari yang <b>sudah lewat</b> dan jam
                                        pulangnya tetap kosong; guru yang absen pulang setelah jam kerja tidak dipotong.
                                        Hari WFH, dinas, izin, sakit, cuti, alpha, dan yang masih antre verifikasi dibebaskan.
                                        Nilai <b>0</b> mematikan aturan ini. Potongan dimulai sejak cron
                                        pertama kali menjalaninya — hari-hari lama yang jam pulangnya kosong tidak ikut dipotong ke belakang.
                                        Cron meninjau maksimal 60 hari ke belakang, jadi hari yang lebih tua dari itu tidak pernah terpotong
                                        mendadak setelah cron lama mati. Bila admin kemudian mengisi jam pulang untuk hari yang sudah
                                        terpotong, poinnya kembali penuh.
                                    </p>
                                </div>
                            </div>

                            <!-- 4. Tarif Denda Bertingkat (Tiered Penalties) -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-amber-100/80 rounded-xl p-2.5 text-amber-600">
                                            <i class="bi bi-sort-numeric-down text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">4. Tarif Denda &amp; Poin Keterlambatan Berjenjang</h6>
                                            <p class="text-xs text-slate-500 mb-0">Skema denda rupiah dan poin per menit berdasarkan durasi telat</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-700 text-[10px]">Tiered System</span>
                                </div>
                                <div class="p-6 space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <!-- Tier 1 -->
                                        <div class="rounded-2xl border border-slate-200 bg-white p-4 transition-shadow hover:border-slate-300 hover:shadow-sm p-4">
                                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                                                <span class="font-bold text-sm text-slate-800">Tier 1 (1 - 30 Menit)</span>
                                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold bg-slate-100 text-slate-600 text-[10px]">Ringan</span>
                                            </div>
                                            <div class="space-y-3">
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Denda Rupiah / Menit</label>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-xs font-bold text-slate-500">Rp</span>
                                                        <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-semibold" name="denda_tier_1" x-model.number="tier[0].denda" 
                                                            value="{{ $absenSetting->denda_tier_1 ?? 100 }}">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Poin Pelanggaran / Menit</label>
                                                    <div class="flex items-center gap-1.5">
                                                        <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-semibold" name="point_tier_1" x-model.number="tier[0].poin" 
                                                            value="{{ $absenSetting->point_tier_1 ?? 1 }}">
                                                        <span class="text-xs text-slate-500">pt/mnt</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tier 2 -->
                                        <div class="rounded-2xl border border-slate-200 bg-white p-4 transition-shadow hover:border-slate-300 hover:shadow-sm p-4">
                                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                                                <span class="font-bold text-sm text-slate-800">Tier 2 (31 - 60 Menit)</span>
                                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-700 text-[10px]">Sedang</span>
                                            </div>
                                            <div class="space-y-3">
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Denda Rupiah / Menit</label>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-xs font-bold text-slate-500">Rp</span>
                                                        <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-semibold" name="denda_tier_2" x-model.number="tier[1].denda" 
                                                            value="{{ $absenSetting->denda_tier_2 ?? 200 }}">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Poin Pelanggaran / Menit</label>
                                                    <div class="flex items-center gap-1.5">
                                                        <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-semibold" name="point_tier_2" x-model.number="tier[1].poin" 
                                                            value="{{ $absenSetting->point_tier_2 ?? 2 }}">
                                                        <span class="text-xs text-slate-500">pt/mnt</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tier 3 -->
                                        <div class="rounded-2xl border border-slate-200 bg-white p-4 transition-shadow hover:border-slate-300 hover:shadow-sm p-4">
                                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                                                <span class="font-bold text-sm text-slate-800">Tier 3 (&gt; 60 Menit)</span>
                                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold bg-red-100 text-red-700 text-[10px]">Berat</span>
                                            </div>
                                            <div class="space-y-3">
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Denda Rupiah / Menit</label>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-xs font-bold text-slate-500">Rp</span>
                                                        <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-semibold" name="denda_tier_3" x-model.number="tier[2].denda" 
                                                            value="{{ $absenSetting->denda_tier_3 ?? 500 }}">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Poin Pelanggaran / Menit</label>
                                                    <div class="flex items-center gap-1.5">
                                                        <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-semibold" name="point_tier_3" x-model.number="tier[2].poin" 
                                                            value="{{ $absenSetting->point_tier_3 ?? 5 }}">
                                                        <span class="text-xs text-slate-500">pt/mnt</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                     <!-- 5. Batasan Denda & Alpha -->
                                     <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200">
                                         <div class="font-bold text-xs text-slate-700 uppercase tracking-wide mb-3 flex items-center gap-1.5">
                                             <i class="bi bi-shield-lock text-slate-500"></i> Batasan Maksimal &amp; Denda Alpha
                                         </div>
                                         <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                              <div>
                                                  <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Denda Alpha / Hari (Flat)</label>
                                                  <div class="flex items-center gap-1.5">
                                                      <span class="text-xs font-bold text-slate-500">Rp</span>
                                                      <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold" name="denda_alpha_per_hari"
                                                          value="{{ $absenSetting->denda_alpha_per_hari ?? 5000 }}" min="0" step="500">
                                                  </div>
                                                  <span class="text-[11px] text-slate-400 mt-1 block">Denda alpa flat per hari kerja, tanpa kelipatan.</span>
                                              </div>
                                             <div>
                                                 <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Batas Denda Maksimal / Bulan</label>
                                                 <div class="flex items-center gap-1.5">
                                                     <span class="text-xs font-bold text-slate-500">Rp</span>
                                                     <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="max_denda_bulan" 
                                                         value="{{ $absenSetting->max_denda_bulan ?? 500000 }}" min="0" step="5000">
                                                 </div>
                                                 <span class="text-[11px] text-slate-400 mt-1 block">Batas maksimal total denda guru per bulan (0 = tanpa batas).</span>
                                             </div>
                                             <div>
                                                 <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Poin Lembur / Jam</label>
                                                 <div class="flex items-center gap-1.5">
                                                     <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="point_lembur_per_jam" 
                                                         value="{{ $absenSetting->point_lembur_per_jam ?? 5 }}" min="0" max="100">
                                                     <span class="text-xs font-bold text-slate-500">Poin/Jam</span>
                                                 </div>
                                                 <span class="text-[11px] text-slate-400 mt-1 block">Reward poin per jam lembur yang disetujui.</span>
                                             </div>
                                         </div>

                                      </div>

                                    <!-- 6. Interactive Simulator Calculator -->
                                    <div class="p-4 rounded-2xl border border-blue-200 bg-blue-50/60">
                                        <div class="flex justify-between items-center mb-3">
                                            <div class="flex items-center gap-2">
                                                <i class="bi bi-lightning-charge-fill text-blue-600"></i>
                                                <h6 class="font-bold text-slate-800 text-sm mb-0">Simulator Live Perhitungan Denda</h6>
                                            </div>
                                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-700 text-[10px]">Simulasi Interaktif</span>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                            <div>
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Simulasi Keterlambatan (Menit)</label>
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold" x-model.number="simMenit" min="1" max="300">
                                            </div>
                                            <div class="p-3 bg-white rounded-xl border border-blue-100 shadow-sm text-center">
                                                <div class="text-xs text-slate-500 font-medium">Estimasi Denda Rupiah</div>
                                                <div class="text-lg font-bold text-red-600 mt-0.5" x-text="'Rp ' + formatAngka(sim.denda)">Rp 0</div>
                                            </div>
                                            <div class="p-3 bg-white rounded-xl border border-blue-100 shadow-sm text-center">
                                                <div class="text-xs text-slate-500 font-medium">Estimasi Poin Pelanggaran</div>
                                                <div class="text-lg font-bold text-amber-600 mt-0.5" x-text="sim.poin + ' pt'">0 pt</div>
                                            </div>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-2.5" x-text="simBreakdown">
                                            Breakdown perhitungan tier akan muncul otomatis sesuai perubahan angka di atas.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 7. Denda Siswa (Alpha & Bolos) -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-rose-100/80 rounded-xl p-2.5 text-rose-600">
                                            <i class="bi bi-bank text-lg"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">7. Denda Siswa (Alpha &amp; Bolos)</h6>
                                            <p class="text-xs text-slate-500 mb-0">Tagihan rupiah per hari untuk siswa yang tidak masuk tanpa keterangan atau masuk tanpa absen pulang</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold bg-red-100 text-red-700 text-[10px]">Presensi Siswa</span>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Denda Alpha / Hari</label>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-slate-500">Rp</span>
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold" name="denda_alpha_siswa_per_hari"
                                                    value="{{ $absenSetting->denda_alpha_siswa_per_hari ?? 0 }}" min="0" step="500">
                                            </div>
                                            <span class="text-[11px] text-slate-400 mt-1 block">Ditagih saat status siswa ALPHA / TANPA KETERANGAN pada hari kerja. <b>0</b> = tidak ditagih.</span>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Denda Bolos / Hari</label>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-slate-500">Rp</span>
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold" name="denda_bolos_siswa_per_hari"
                                                    value="{{ $absenSetting->denda_bolos_siswa_per_hari ?? 0 }}" min="0" step="500">
                                            </div>
                                            <span class="text-[11px] text-slate-400 mt-1 block">Ditagih saat siswa punya jam masuk tetapi tetap tanpa jam pulang setelah jam pulang lewat — orang yang sama dengan daftar "BOLOS — MASUK TANPA PULANG" di rekap piket.</span>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Batas Denda Siswa / Bulan</label>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs font-bold text-slate-500">Rp</span>
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="max_denda_siswa_bulan"
                                                    value="{{ $absenSetting->max_denda_siswa_bulan ?? 0 }}" min="0" step="5000">
                                            </div>
                                            <span class="text-[11px] text-slate-400 mt-1 block">Plafon total per siswa per bulan kalender (0 = tanpa batas). Terpisah dari batas denda guru di atas.</span>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-4 mb-0 leading-relaxed">
                                        Nominal tercatat pada baris presensi siswa (<i>Presensi &raquo; Rekap Siswa</i> kolom <b>Denda</b>).
                                        Hari yang sudah tertagih mempertahankan nominalnya, jadi mengubah tarif di sini tidak menulis ulang
                                        tagihan bulan-bulan sebelumnya — hanya hari-hari baru yang memakai tarif baru.
                                        Matikan fitur cukup dengan mengembalikan kedua tarif ke <b>0</b>; riwayat tagihan yang sudah tercetak tidak ikut terhapus.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================================================== -->
                    <!-- TAB 5: BATASAN & KEBIJAKAN WFH                       -->
                    <!-- ==================================================== -->
                    <div class="space-y-6" x-show="tab === 'aturan'" x-cloak id="aturan" role="tabpanel">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            <!-- Batas Izin, Cuti & Standar Kehadiran -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                    <div class="bg-violet-100/80 rounded-xl p-2.5 text-violet-600">
                                        <i class="bi bi-shield-check text-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="font-bold text-slate-800 mb-0">Batas Kuota Izin, Cuti &amp; Kehadiran</h6>
                                        <p class="text-xs text-slate-500 mb-0">Pembatasan durasi permohonan dispensasi guru</p>
                                    </div>
                                </div>
                                <div class="p-6 space-y-5">
                                    <p class="text-xs text-slate-500 mb-0 leading-relaxed">
                                        Kuota dihitung dari <b>hari kerja efektif</b> — akhir pekan dan hari libur
                                        tidak memotong kuota, karena hari-hari itu memang tidak menghasilkan
                                        catatan kehadiran.
                                    </p>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Kuota Izin / Tahun</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold" name="max_izin_hari"
                                                    value="{{ $absenSetting->max_izin_hari ?? 3 }}" min="0" max="365">
                                                <span class="text-xs font-semibold text-slate-500">Hari</span>
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-1 mb-0">0 = tanpa batas. Akumulasi izin disetujui + masih menunggu.</p>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Kuota Cuti / Tahun</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold" name="max_cuti_hari"
                                                    value="{{ $absenSetting->max_cuti_hari ?? 12 }}" min="0" max="365">
                                                <span class="text-xs font-semibold text-slate-500">Hari</span>
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-1 mb-0">0 = tanpa batas.</p>
                                        </div>
                                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Kuota Sakit / Tahun</label>
                                            <div class="flex items-center gap-2">
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold" name="max_sakit_hari"
                                                    value="{{ $absenSetting->max_sakit_hari ?? 12 }}" min="0" max="365">
                                                <span class="text-xs font-semibold text-slate-500">Hari</span>
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-1 mb-0">0 = tanpa batas. 12 = 2 minggu.</p>
                                        </div>
                                    </div>

                                    <div class="border-t border-slate-100 pt-5">
                                        <h6 class="font-bold text-slate-700 text-sm mb-3">Maksimal durasi per pengajuan</h6>
                                        <p class="text-xs text-slate-500 mb-3 leading-relaxed">
                                            Batas ini terpisah dari kuota tahunan: mengatur durasi satu pengajuan,
                                            bukan total pemakaian setahun. Ini yang membuat “izin 3 hari” tidak lagi
                                            otomatis menghabiskan seluruh kuota 3 hari/tahun.
                                        </p>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Izin per Pengajuan</label>
                                                <div class="flex items-center gap-2">
                                                    <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold" name="max_izin_per_pengajuan"
                                                        value="{{ $absenSetting->max_izin_per_pengajuan ?? 3 }}" min="0" max="365">
                                                    <span class="text-xs font-semibold text-slate-500">Hari</span>
                                                </div>
                                            </div>
                                            <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Cuti per Pengajuan</label>
                                                <div class="flex items-center gap-2">
                                                    <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold" name="max_cuti_per_pengajuan"
                                                        value="{{ $absenSetting->max_cuti_per_pengajuan ?? 12 }}" min="0" max="365">
                                                    <span class="text-xs font-semibold text-slate-500">Hari</span>
                                                </div>
                                            </div>
                                            <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Sakit per Pengajuan</label>
                                                <div class="flex items-center gap-2">
                                                    <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold" name="max_sakit_per_pengajuan"
                                                        value="{{ $absenSetting->max_sakit_per_pengajuan ?? 3 }}" min="0" max="365">
                                                    <span class="text-xs font-semibold text-slate-500">Hari</span>
                                                </div>
                                                <p class="text-[11px] text-slate-400 mt-1 mb-0">Di atas 3 hari tetap wajib surat dokter.</p>
                                            </div>
                                            <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Dinas per Pengajuan</label>
                                                <div class="flex items-center gap-2">
                                                    <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold" name="max_dinas_per_pengajuan"
                                                        value="{{ $absenSetting->max_dinas_per_pengajuan ?? 30 }}" min="0" max="365">
                                                    <span class="text-xs font-semibold text-slate-500">Hari</span>
                                                </div>
                                                <p class="text-[11px] text-slate-400 mt-1 mb-0">Dinas tidak memakai kuota tahunan — tugas resmi dihitung sebagai hadir.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                        <i class="bi bi-calendar-check text-slate-500 text-lg mt-0.5"></i>
                                        <div>
                                            <span class="font-bold block text-sm text-slate-800">Dasar perhitungan kuota</span>
                                            <label class="flex items-center gap-2 mt-2 cursor-pointer">
                                                <input type="checkbox" name="kuota_hari_kerja" value="1"
                                                    {{ ((int) ($absenSetting->kuota_hari_kerja ?? 1) === 0) ? '' : 'checked' }}>
                                                <span class="text-xs text-slate-600">Hitung hanya hari kerja efektif (hapus centang untuk memakai hitungan hari kalender)</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/50">
                                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500 text-amber-900 flex items-center gap-1.5">
                                            <i class="bi bi-percent text-amber-600"></i> Batas Minimum Kehadiran (Standar SMA/Disdik)
                                        </label>
                                        <div class="flex items-center gap-2 max-w-xs">
                                            <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold" name="min_kehadiran_persen"
                                                value="{{ $absenSetting->min_kehadiran_persen ?? 90 }}"
                                                min="50" max="100" step="0.5">
                                            <span class="text-sm font-bold text-amber-800">%</span>
                                        </div>
                                        <p class="text-xs text-amber-700 mt-2 mb-0">Standar minimal kehadiran bulanan (default 90%). Guru dengan persentase di bawah angka ini akan muncul di panel atensi khusus.</p>
                                    </div>

                                    <div class="flex items-start gap-3 p-4 rounded-xl border border-emerald-200 bg-emerald-50/50">
                                        <i class="bi bi-check-circle-fill text-emerald-500 text-lg mt-0.5"></i>
                                        <div>
                                            <span class="font-bold block text-sm text-slate-800">Libur Kalender Nasional</span>
                                            <span class="text-xs text-slate-600">Selalu aktif — tidak ada sakelar. Tanggal merah nasional selalu dikeluarkan dari perhitungan kehadiran, rekap, dan denda.</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                        <div>
                                            <span class="font-bold block text-sm text-slate-800">Alert Kehadiran Rendah</span>
                                            <span class="text-xs text-slate-500">Tampilkan indikator peringatan di monitoring jika kehadiran guru di bawah standar.</span>
                                        </div>
                                        <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center shrink-0 ml-3">
                                            <input type="checkbox" class="peer sr-only" name="alert_kehadiran_enabled" value="1" {{ ($absenSetting->alert_kehadiran_enabled ?? 1) ? 'checked' : '' }}>
                                            <span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-600"></span><span class="absolute left-[3px] h-[18px] w-[18px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Kebijakan WFH (Work From Home) -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                    <div class="bg-teal-100/80 rounded-xl p-2.5 text-teal-600">
                                        <i class="bi bi-laptop text-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="font-bold text-slate-800 mb-0">Kebijakan WFH (Work From Home)</h6>
                                        <p class="text-xs text-slate-500 mb-0">Bypass radius GPS untuk penugasan luar/rumah</p>
                                    </div>
                                </div>
                                <div class="p-6 space-y-5">
                                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                        <div>
                                            <span class="font-bold block text-sm text-slate-800">Aktifkan Fitur WFH</span>
                                            <span class="text-xs text-slate-500">Izinkan guru memilih opsi presensi bekerja dari rumah.</span>
                                        </div>
                                        <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center shrink-0 ml-3">
                                            <input type="checkbox" class="peer sr-only" name="wfh_enabled" value="1" {{ ($absenSetting->wfh_enabled ?? 0) ? 'checked' : '' }} x-model="wfh">
                                            <span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-600"></span><span class="absolute left-[3px] h-[18px] w-[18px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                                        </label>
                                    </div>

                                        <div class="space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4" x-show="wfh" x-cloak>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Jam Mulai WFH</label>
                                                <input type="time" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="wfh_mulai"
                                                    value="{{ $absenSetting->wfh_mulai ?? '08:00' }}">
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Jam Selesai WFH</label>
                                                <input type="time" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="wfh_selesai"
                                                    value="{{ $absenSetting->wfh_selesai ?? '16:00' }}">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Batas Kuota WFH / Bulan</label>
                                            <div class="flex items-center gap-2 max-w-xs">
                                                <input type="number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-bold" name="wfh_max_per_bulan"
                                                    value="{{ $absenSetting->wfh_max_per_bulan ?? 5 }}" min="0" max="31">
                                                <span class="text-xs font-semibold text-slate-500">Hari / Guru</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                            <!-- Kebijakan Jam Operasional Scanner QR Siswa -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                    <div class="bg-rose-100/80 rounded-xl p-2.5 text-rose-600">
                                        <i class="bi bi-qr-code-scan text-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="font-bold text-slate-800 mb-0">Jam Operasional Scanner QR Siswa</h6>
                                        <p class="text-xs text-slate-500 mb-0">Scan <b>masuk</b> dibatasi jendela operasional + jadwal masuk (ala absen wajah); scan <b>pulang</b> dibuka begitu jendela masuk ditutup dan ditutup lagi oleh jendela operasional</p>
                                    </div>
                                </div>
                                <div class="p-6 space-y-5">
                                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                        <div>
                                            <span class="font-bold block text-sm text-slate-800">Batasi Jam Scan Masuk</span>
                                            <span class="text-xs text-slate-500">Matikan untuk scan 24 jam (tidak disarankan).</span>
                                        </div>
                                        <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center shrink-0 ml-3">
                                            <input type="checkbox" class="peer sr-only" name="qr_scan_aktif" value="1" {{ ($absenSetting->qr_scan_aktif ?? 1) ? 'checked' : '' }} x-model="qr">
                                            <span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-600"></span><span class="absolute left-[3px] h-[18px] w-[18px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                                        </label>
                                    </div>

                                    <div class="space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4" x-show="qr" x-cloak>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Scan Dibuka Pukul</label>
                                                <input type="time" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="qr_scan_mulai"
                                                    value="{{ substr((string)($absenSetting->qr_scan_mulai ?? '05:30:00'), 0, 5) }}">
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Scan Ditutup Pukul</label>
                                                <input type="time" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="qr_scan_selesai"
                                                    value="{{ substr((string)($absenSetting->qr_scan_selesai ?? '18:00:00'), 0, 5) }}">
                                            </div>
                                        </div>
                                        <p class="text-[11px] text-slate-400">Contoh: 05:30–18:00 WITA. Berlaku untuk halaman <code>/scanner</code> &amp; <code>/guru/presensi-siswa</code>. Selain itu scan masuk mengikuti jadwal masuk hari ini (dibuka 1 jam sebelum, ditutup 3 jam setelah) dan scan pulang langsung dibuka setelah jendela masuk ditutup — sama seperti absen wajah. Jam selesai berlaku dua arah: lewat jam itu scan masuk <b>dan</b> pulang ditolak, dan cron <code>/cron/daily</code> menandai siswa yang masuk tanpa pulang sebagai &quot;pulang tidak tercatat&quot; (jam keluar tetap kosong, tidak diisi otomatis).</p>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Token Kiosk /scanner (wajib untuk kiosk publik)</label>
                                            <?php $tokenKioskAktif = trim((string) ($absenSetting->qr_kiosk_token ?? '')) !== ''; ?>
                                            <div class="mb-2">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold {{ $tokenKioskAktif ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                                    <i class="bi {{ $tokenKioskAktif ? 'bi-shield-lock-fill' : 'bi-shield-exclamation' }}"></i>
                                                    {{ $tokenKioskAktif ? 'Token terpasang — /scanner terkunci' : 'Belum ada token — /scanner publik ditolak' }}
                                                </span>
                                            </div>
                                            <input type="password" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 font-mono" name="qr_kiosk_token" value=""
                                                placeholder="{{ $tokenKioskAktif ? 'Tidak diubah — isi hanya untuk mengganti token' : 'Isi token untuk mengunci kiosk' }}"
                                                maxlength="64" autocomplete="new-password">
                                            <?php if ($tokenKioskAktif) { ?>
                                            <label class="flex items-center gap-2 text-[12px] text-slate-500 mt-2">
                                                <input type="checkbox" name="qr_kiosk_token_hapus" value="1" class="form-check-input">
                                                Hapus token (buka kembali /scanner untuk umum)
                                            </label>
                                            <?php } ?>
                                            <p class="text-[11px] text-slate-400 mt-1">Bila terpasang, <b>seluruh</b> jalur publik <code>/scanner</code> — halaman kiosk, <code>/scanner/scan</code>, dan <code>/scanner/history</code> — menolak permintaan tanpa token. Daftarkan perangkat kiosk sekali lewat <code>/scanner?k=TOKEN</code> atau kolom buka-kunci di halaman itu; setelahnya perangkat dikenali sendiri. Kolom ini write-only: nilai token tidak pernah ditampilkan kembali, dan menyimpan form tanpa mengisi kolom tidak mengubahnya. Selama token kosong, halaman <code>/scanner</code> publik beserta endpoint scan/riwayatnya <b>menolak akses</b> (kecuali admin/guru yang sedang login).</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>

                    <!-- ==================================================== -->
                    <!-- TAB 6: NOTIFIKASI GATEWAY & INTEGRASI OAUTH          -->
                    <!-- ==================================================== -->
                    <div class="space-y-6" x-show="tab === 'notifikasi'" x-cloak id="notifikasi" role="tabpanel">
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                
                                <!-- Bot Telegram Card -->
                                <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-sky-100/80 rounded-xl p-2.5 text-sky-600">
                                                <i class="bi bi-telegram text-lg"></i>
                                            </div>
                                            <div>
                                                <h6 class="font-bold text-slate-800 mb-0">Notifikasi Bot Telegram</h6>
                                                <p class="text-xs text-slate-500 mb-0">Broadcast laporan absensi ke grup/channel</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div class="flex items-start gap-2 rounded-xl border px-3.5 py-2.5 text-xs leading-relaxed border-blue-200 bg-blue-50 text-blue-800 text-xs">
                                            Kirim ringkasan harian dan alert keterlambatan otomatis. Dapatkan bot token gratis melalui <a href="https://t.me/BotFather" target="_blank" class="font-bold underline">@BotFather</a>.
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Bot Token</label>
                                            <input type="text" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="telegram_bot_token" 
                                                value="{{ $absenSetting->telegram_bot_token ?? '' }}" 
                                                placeholder="Contoh: 1234567890:AAHdqTcv...">
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Chat ID Tujuan (Grup / Channel)</label>
                                            <input type="text" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="telegram_chat_id" 
                                                value="{{ $absenSetting->telegram_chat_id ?? '' }}" 
                                                placeholder="Contoh: -1001234567890">
                                        </div>
                                        <div class="pt-2">
                                            <button type="button" @click="ujiNotifikasi('telegram')" :disabled="!!menguji"
                                                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-blue-200 bg-white px-3 py-1.5 text-[11px] font-bold text-blue-700 transition-colors hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-50">
                                                <span x-show="menguji === 'telegram'" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-blue-200 border-t-blue-600"></span>
                                                <i x-show="menguji !== 'telegram'" class="bi bi-send"></i>
                                                <span x-text="menguji === 'telegram' ? 'Mengirim…' : 'Test Kirim Telegram'"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- WhatsApp Gateway Card -->
                                <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-emerald-100/80 rounded-xl p-2.5 text-emerald-600">
                                                <i class="bi bi-whatsapp text-lg"></i>
                                            </div>
                                            <div>
                                                <h6 class="font-bold text-slate-800 mb-0">WhatsApp Gateway</h6>
                                                <p class="text-xs text-slate-500 mb-0">Notifikasi ke nomor/grup WhatsApp</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-6 space-y-4">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Kanal aktif</label>
                                            <select class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="wa_gateway_mode" x-model="waMode">
                                                <?php
                                                $waMode = strtolower(trim((string) ($absenSetting->wa_gateway_mode ?? '')));
                    if ($waMode === '') {
                        // Instalasi lama hanya punya Fonnte — jangan diam-diam jadi "off".
                        $waMode = (! empty($absenSetting->wa_token) && ! empty($absenSetting->wa_target_number)) ? 'fonnte' : 'off';
                    }
                    ?>
                                                <option value="off" {{ $waMode === 'off' ? 'selected' : '' }}>Nonaktif</option>
                                                <option value="openwa" {{ $waMode === 'openwa' ? 'selected' : '' }}>OpenWA (self-hosted)</option>
                                                <option value="fonnte" {{ $waMode === 'fonnte' ? 'selected' : '' }}>Fonnte</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nomor / Grup WhatsApp tujuan laporan</label>
                                            <input type="text" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="wa_target_number" 
                                                value="{{ $absenSetting->wa_target_number ?? '' }}" 
                                                placeholder="081234567890 atau 120363...@g.us">
                                            <p class="text-xs text-slate-500 mt-1">
                                                Nomor Untuk grup tulis chat id OpenWA seperti <code>120363...@g.us</code>.
                                            </p>
                                        </div>

                                        <!-- Panel OpenWA -->
                                        <div class="space-y-4" x-show="waMode === 'openwa'" x-cloak>
                                            <div>
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Base URL OpenWA</label>
                                                <input type="url" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="wa_base_url" 
                                                    value="{{ $absenSetting->wa_base_url ?? '' }}" 
                                                    placeholder="http://localhost:2785">
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">API Key OpenWA</label>
                                                <input type="password" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="wa_api_key" 
                                                    value="{{ $absenSetting->wa_api_key ?? '' }}" 
                                                    placeholder="owa_k1_...">
                                                <p class="text-xs text-slate-500 mt-1">
                                                   Ambil dari dashboard OpenWA atau file <code>data/.api-key</code>.
                                                </p>
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama Sesi (opsional)</label>
                                                <input type="text" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="wa_session_name" 
                                                    value="{{ $absenSetting->wa_session_name ?? '' }}" 
                                                    placeholder="ika-notif">
                                                <p class="text-xs text-slate-500 mt-1">Kosongkan untuk memakai sesi berstatus <code>ready</code> pertama.</p>
                                            </div>
                                        </div>

                                        <!-- Panel Fonnte -->
                                        <div class="space-y-4" x-show="waMode === 'fonnte'" x-cloak>
                                            <div class="flex items-start gap-2 rounded-xl border px-3.5 py-2.5 text-xs leading-relaxed border-blue-200 bg-blue-50 text-blue-800 text-xs">
                                                Integrasi via layanan gateway <a href="https://fonnte.com" target="_blank" class="font-bold underline text-emerald-600">Fonnte</a>.
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Gateway API URL</label>
                                                <input type="url" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="wa_gateway_url" 
                                                    value="{{ $absenSetting->wa_gateway_url ?? 'https://api.fonnte.com/send' }}">
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">API Token</label>
                                                <input type="password" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="wa_token" 
                                                    value="{{ $absenSetting->wa_token ?? '' }}" 
                                                    placeholder="Masukkan token API Fonnte">
                                            </div>
                                        </div>

                                        <div class="pt-2 flex items-center gap-3">
                                            <button type="button" @click="ujiNotifikasi('whatsapp')" :disabled="!!menguji"
                                                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-emerald-200 bg-white px-3 py-1.5 text-[11px] font-bold text-emerald-700 transition-colors hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50">
                                                <span x-show="menguji === 'whatsapp'" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-emerald-200 border-t-emerald-600"></span>
                                                <i x-show="menguji !== 'whatsapp'" class="bi bi-send"></i>
                                                <span x-text="menguji === 'whatsapp' ? 'Mengirim…' : 'Test Kirim WhatsApp'"></span>
                                            </button>
                                            <a href="{{ url('admin/presensi/whatsapp') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl px-3 py-1.5 text-[11px] font-bold transition-colors no-underline disabled:cursor-not-allowed disabled:opacity-50 border border-blue-200 bg-white text-blue-700 hover:bg-blue-50">
                                                <i class="bi bi-people mr-1"></i> Kelola Tautan Guru
                                            </a>
                                            <a href="{{ url('admin/presensi/notifikasi-status') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl px-3 py-1.5 text-[11px] font-bold transition-colors no-underline disabled:cursor-not-allowed disabled:opacity-50 border border-slate-200 bg-white text-slate-600 hover:bg-slate-100">
                                                <i class="bi bi-activity mr-1"></i> Status Notifikasi
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Google OAuth Single Sign-On Integration Card -->
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-3">
                                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="22" height="22" alt="Google">
                                        <div>
                                            <h6 class="font-bold text-slate-800 mb-0">Integrasi Login Akun Google (Single Sign-On)</h6>
                                            <p class="text-xs text-slate-500 mb-0">Login instan bagi guru &amp; staf menggunakan akun Google Workspace / Gmail</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-slate-600">Aktifkan SSO</span>
                                        <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center">
                                            <input type="checkbox" class="peer sr-only" name="google_login_enabled" value="1" {{ ($absenSetting->google_login_enabled ?? 1) ? 'checked' : '' }}>
                                            <span class="absolute inset-0 rounded-full bg-slate-300 transition-colors peer-checked:bg-blue-600"></span><span class="absolute left-[3px] h-[18px] w-[18px] rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                        <div class="lg:col-span-2 space-y-4">
                                            <div>
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Google Client ID</label>
                                                <input type="text" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="google_client_id"
                                                    value="{{ $absenSetting->google_client_id ?? '' }}"
                                                    placeholder="Contoh: 1234567890-xxx.apps.googleusercontent.com">
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Google Client Secret</label>
                                                <input type="password" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" name="google_client_secret"
                                                    value="{{ $absenSetting->google_client_secret ?? '' }}"
                                                    placeholder="GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxx">
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Authorized Redirect URI (Wajib didaftarkan di Google Console)</label>
                                                <div class="flex items-center gap-2">
                                                    <input type="text" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 font-mono text-xs text-slate-600 shadow-xs outline-none"
                                                        value="{{ url('presensi/google-callback') }}" readonly>
                                                    <button class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl border border-blue-200 bg-white px-3.5 py-2.5 text-[11px] font-bold text-blue-700 transition-colors hover:bg-blue-50" type="button" @click="salinUri(@js(url('presensi/google-callback')))">
                                                        <i class="bi bi-clipboard"></i> Salin
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-2 text-slate-600">
                                            <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                                <i class="bi bi-info-circle text-blue-600"></i> Panduan Singkat Google OAuth
                                            </div>
                                            <ol class="list-decimal pl-4 space-y-1 text-slate-500">
                                                <li>Buka Google Cloud Console di <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-600 underline">console.cloud.google.com</a></li>
                                                <li>Pilih proyek &rarr; <b>Credentials</b> &rarr; <b>Create Credentials</b> &rarr; <b>OAuth Client ID</b></li>
                                                <li>Pilih type <b>Web Application</b></li>
                                                <li>Tempelkan URI di samping ke <b>Authorized redirect URIs</b></li>
                                                <li>Simpan dan salin Client ID &amp; Secret ke kolom di samping</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aksi simpan pengaturan presensi -->
                    <div x-show="tabAksi" x-cloak
                        class="sticky bottom-0 z-30 mt-6 flex flex-wrap items-center justify-end gap-3 rounded-2xl border border-slate-200/80 bg-white/95 p-3 shadow-lg backdrop-blur">
                        <a href="{{ url('dashboard') }}"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                            <i class="bi bi-arrow-left"></i> Dashboard
                        </a>
                        <button type="submit" :disabled="menyimpanSetting"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-6 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-show="menyimpanSetting" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            <i x-show="!menyimpanSetting" class="bi bi-check2-circle"></i>
                            <span x-text="menyimpanSetting ? 'Menyimpan…' : 'Simpan Pengaturan Presensi'"></span>
                        </button>
                    </div>
                </form>

                <!-- ==================================================== -->
                <!-- TAB 7: TAMPILAN / THEME CUSTOMIZER                   -->
                <!-- ==================================================== -->
                <div class="space-y-6" x-show="tab === 'tampilan'" x-cloak id="tampilan" role="tabpanel">

                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                        <div class="xl:col-span-2 space-y-6">
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 p-6">
                                <div class="flex items-start gap-4 mb-5">
                                    <div class="p-2.5 bg-primary-50 text-primary-600 rounded-xl">
                                        <i class="bi bi-palette text-xl"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800">Tema Warna Aplikasi</h4>
                                        <p class="text-xs text-slate-500 mt-1">
                                            Pilih preset warna atau buat warna sendiri. Tema diterapkan ke panel admin,
                                            halaman login, desktop guru, serta aplikasi mobile guru &amp; siswa tanpa mengubah kode CSS.
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    @foreach ($themePresets as $key => $preset)
                                        <button type="button" @click="pilihTemaPreset(@js($key))"
                                            :class="themePreset === @js($key) ? 'border-blue-500 bg-blue-50/60 ring-4 ring-blue-500/15' : 'border-slate-200 hover:border-slate-300'"
                                            class="relative rounded-2xl border-[1.5px] bg-white p-3.5 text-left transition-all">
                                            <span x-show="themePreset === @js($key)" x-cloak
                                                class="absolute right-2.5 top-2.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[11px] text-white">
                                                <i class="bi bi-check-lg"></i>
                                            </span>
                                            <span class="mb-3 grid grid-cols-3 gap-1.5">
                                                <span class="block h-6 w-full rounded-lg border border-slate-900/5" style="background: {{ $preset['colors']['brand'] }}"></span>
                                                <span class="block h-6 w-full rounded-lg border border-slate-900/5" style="background: {{ $preset['colors']['primary'] }}"></span>
                                                <span class="block h-6 w-full rounded-lg border border-slate-900/5" style="background: {{ $preset['colors']['accent'] }}"></span>
                                            </span>
                                            <span class="block text-sm font-bold text-slate-800">{{ $preset['label'] }}</span>
                                            <span class="mt-0.5 block text-[11px] leading-snug text-slate-500">{{ $preset['desc'] }}</span>
                                        </button>
                                    @endforeach

                                    <button type="button" @click="pilihTemaPreset('custom')"
                                        :class="themePreset === 'custom' ? 'border-blue-500 bg-blue-50/60 ring-4 ring-blue-500/15' : 'border-slate-200 hover:border-slate-300'"
                                        class="relative rounded-2xl border-[1.5px] bg-white p-3.5 text-left transition-all">
                                        <span x-show="themePreset === 'custom'" x-cloak
                                            class="absolute right-2.5 top-2.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[11px] text-white">
                                            <i class="bi bi-check-lg"></i>
                                        </span>
                                        <span class="mb-3 grid grid-cols-3 gap-1.5">
                                            <template x-for="shade in [50, 500, 700]" :key="shade">
                                                <span class="block h-6 w-full rounded-lg border border-slate-900/5" :style="'background:' + paletCustom[shade]"></span>
                                            </template>
                                        </span>
                                        <span class="block text-sm font-bold text-slate-800">Custom</span>
                                        <span class="mt-0.5 block text-[11px] leading-snug text-slate-500">Pilih warna dasar sendiri; palet dan aksen gradien dibuat otomatis.</span>
                                    </button>
                                </div>

                                <div x-show="themePreset === 'custom'" x-cloak
                                    class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                                        <div>
                                            <label for="themeCustomColor" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Warna dasar</label>
                                            <input type="color" id="themeCustomColor" x-model="customHex" @input="pakaiWarna()"
                                                class="h-11 w-16 cursor-pointer rounded-xl border border-slate-300 bg-white p-1">
                                        </div>
                                        <div class="max-w-[180px] flex-1">
                                            <label for="themeCustomHex" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Kode HEX</label>
                                            <input type="text" id="themeCustomHex" x-model="customHex" @change="pakaiWarna()" maxlength="7" spellcheck="false"
                                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 font-mono text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Palet otomatis</div>
                                        <div class="grid grid-cols-10 gap-1">
                                            <template x-for="shade in shades" :key="shade">
                                                <span class="block h-6 w-full rounded-lg border border-slate-900/5" :title="String(shade)"
                                                    :style="'background:' + paletCustom[shade]"></span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-4 flex items-start gap-3">
                                <i class="bi bi-info-circle text-primary-500 mt-0.5"></i>
                                <p class="text-xs leading-relaxed text-slate-500">
                                    Warna status (hadir, izin, alpa) tetap menggunakan warna semantik agar mudah dibaca.
                                    Ekspor PDF dan Excel tidak terpengaruh tema.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 p-5">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-bold text-slate-800 text-sm">Pratinjau Langsung</h4>
                                    <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Live</span>
                                </div>

                                <div class="rounded-xl overflow-hidden border border-border p-3">
                                    <div class="flex">
                                        <div class="w-20 p-2.5 space-y-1.5" style="background: linear-gradient(160deg, #1e293b 0%, #0f172a 55%, #0c1322 100%)">
                                            <div class="h-2 w-8 rounded-full" style="background: var(--primary-400)"></div>
                                            <div class="h-1.5 w-12 rounded-full bg-white/25"></div>
                                            <div class="h-1.5 w-12 rounded-full" style="background: var(--primary-500)"></div>
                                            <div class="h-1.5 w-12 rounded-full bg-white/25"></div>
                                            <div class="h-1.5 w-9 rounded-full bg-white/25"></div>
                                        </div>
                                        <div class="flex-1 bg-surface-muted p-3 space-y-2">
                                            <div class="h-2 w-16 rounded-full" style="background: var(--primary-500)"></div>
                                            <div class="h-1.5 w-24 rounded-full bg-border"></div>
                                            <div class="flex gap-1.5 pt-1">
                                                <div class="h-6 w-16 rounded-lg" style="background: var(--primary-600)"></div>
                                                <div class="h-6 w-16 rounded-lg" style="background: var(--primary-600)"></div>
                                                <div class="h-6 w-6 rounded-lg" style="background: var(--accent-500)"></div>
                                            </div>
                                            <div class="flex gap-1 pt-1">
                                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700">Hadir</span>
                                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-700">Izin</span>
                                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-red-50 text-red-600">Alpa</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-[11px] leading-relaxed text-slate-500 mt-3">
                                    Pratinjau memakai komponen contoh. Setelah disimpan, warna baru langsung dipakai di seluruh aplikasi.
                                </p>
                            </div>

                            <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 p-5">
                                <button type="button" @click="simpanTema()" :disabled="menyimpanTema"
                                    class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                                    <span x-show="menyimpanTema" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                    <i x-show="!menyimpanTema" class="bi bi-check2-circle"></i>
                                    <span x-text="menyimpanTema ? 'Menyimpan…' : 'Simpan Tema'"></span>
                                </button>
                                <button type="button" @click="pilihTemaPreset('default')"
                                    class="mt-2 inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-200">
                                    <i class="bi bi-arrow-counterclockwise"></i> Gunakan Default
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 8: BACKUP & DATABASE MANAGER                     -->
                <!-- ==================================================== -->
                <div class="space-y-6" x-show="tab === 'maintenance'" x-cloak id="maintenance" role="tabpanel">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        <!-- Unduh Data Backup -->
                        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                <div class="bg-blue-100/80 rounded-xl p-2.5 text-blue-600">
                                    <i class="bi bi-cloud-arrow-down text-lg"></i>
                                </div>
                                <div>
                                    <h6 class="font-bold text-slate-800 mb-0">Ekspor &amp; Cadangan Database</h6>
                                    <p class="text-xs text-slate-500 mb-0">Unduh snapshot database sistem secara instan</p>
                                </div>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200/80 hover:border-slate-300 transition-colors">
                                    <div>
                                        <div class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                            <i class="bi bi-filetype-sql text-blue-600 text-lg"></i> Database Penuh (SQL Dump)
                                        </div>
                                        <p class="text-xs text-slate-400 mb-0 mt-0.5">Skema tabel lengkap dan seluruh data presensi, master, serta pengaturan.</p>
                                    </div>
                                    <a href="{{ url('admin/presensi/backup') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl px-3 py-1.5 text-[11px] font-bold transition-colors no-underline disabled:cursor-not-allowed disabled:opacity-50 border border-blue-200 bg-white text-blue-700 hover:bg-blue-50 shrink-0 ml-3" target="_blank">
                                        <i class="bi bi-download mr-1"></i> Unduh SQL
                                    </a>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200/80 hover:border-slate-300 transition-colors">
                                    <div>
                                        <div class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                            <i class="bi bi-filetype-json text-emerald-600 text-lg"></i> Data Log Presensi (JSON)
                                        </div>
                                        <p class="text-xs text-slate-400 mb-0 mt-0.5">Format data portabel riwayat kehadiran GTK untuk migrasi data.</p>
                                    </div>
                                    <a href="{{ url('admin/presensi/backup-json') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl px-3 py-1.5 text-[11px] font-bold transition-colors no-underline disabled:cursor-not-allowed disabled:opacity-50 border border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-50 shrink-0 ml-3" target="_blank">
                                        <i class="bi bi-download mr-1"></i> Unduh JSON
                                    </a>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-indigo-50/50 rounded-xl border border-indigo-100 hover:border-indigo-200 transition-colors">
                                    <div>
                                        <div class="font-bold text-indigo-900 text-sm flex items-center gap-2">
                                            <i class="bi bi-database-fill-gear text-indigo-600 text-lg"></i> Database Manager &amp; Restore
                                        </div>
                                        <p class="text-xs text-slate-500 mb-0 mt-0.5">Kelola berkas arsip backup lokal dan pulihkan tabel database.</p>
                                    </div>
                                    <a href="{{ url('settings/dbManager') }}" class="inline-flex items-center justify-center gap-1.5 rounded-xl px-3 py-1.5 text-[11px] font-bold transition-colors no-underline disabled:cursor-not-allowed disabled:opacity-50 bg-blue-600 text-white hover:bg-blue-700 shrink-0 ml-3">
                                        <i class="bi bi-box-arrow-up-right mr-1"></i> Buka Manager
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Pembersihan Kapasitas Server -->
                        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs transition-shadow hover:border-slate-300 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
                                <div class="bg-rose-100/80 rounded-xl p-2.5 text-rose-600">
                                    <i class="bi bi-trash3 text-lg"></i>
                                </div>
                                <div>
                                    <h6 class="font-bold text-slate-800 mb-0">Pembersihan Kapasitas Penyimpanan</h6>
                                    <p class="text-xs text-slate-500 mb-0">Optimalisasi ruang disk hosting server</p>
                                </div>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="p-4 bg-rose-50/50 rounded-xl border border-rose-200/80">
                                    <div class="font-bold text-sm text-rose-700 flex items-center gap-2 mb-1">
                                        <i class="bi bi-images"></i> Hapus Foto Absen Bulan-Bulan Lalu
                                    </div>
                                    <p class="text-xs text-slate-600 mb-4">
                                        Menghapus berkas gambar foto selfie dari bulan-bulan sebelumnya agar kapasitas disk server hemat. 
                                        <b>Data log kehadiran teks, jam, dan titik koordinat GPS tetap tersimpan aman</b> di database.
                                    </p>
                                    <button type="button" @click="bersihkanFoto()" :disabled="membersihkan"
                                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-red-600 px-3 py-1.5 text-[11px] font-bold text-white transition-colors hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">
                                        <span x-show="membersihkan" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                        <i x-show="!membersihkan" class="bi bi-trash3"></i>
                                        <span x-text="membersihkan ? 'Menghapus…' : 'Bersihkan Foto Lama Sekarang'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
    </div>
</div>

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    const SHADES = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900];
    const ROMAWI = { 1: 'I', 2: 'II', 3: 'III', 4: 'IV', 5: 'V', 6: 'VI', 7: 'VII', 8: 'VIII', 9: 'IX', 10: 'X', 11: 'XI', 12: 'XII' };
    const NAMA_BULAN = { 1: 'Januari', 2: 'Februari', 3: 'Maret', 4: 'April', 5: 'Mei', 6: 'Juni', 7: 'Juli', 8: 'Agustus', 9: 'September', 10: 'Oktober', 11: 'November', 12: 'Desember' };

    const dua = (n) => String(n).padStart(2, '0');
    const HEX_BAWAAN = '#2563eb';

    Alpine.data('settingPage', (config = {}) => ({
        csrf: config.csrf || '',
        urls: config.urls || {},

        tabOpsi: [
            { key: 'profil', label: 'Profil Sekolah', icon: 'bi-building' },
            { key: 'jadwal', label: 'Jadwal & Lokasi', icon: 'bi-calendar-week' },
            { key: 'wajah', label: 'Wajah & Kamera', icon: 'bi-person-bounding-box' },
            { key: 'poin', label: 'Poin & Denda', icon: 'bi-calculator' },
            { key: 'aturan', label: 'Batasan & WFH', icon: 'bi-shield-check' },
            { key: 'notifikasi', label: 'Notifikasi & OAuth', icon: 'bi-bell' },
            { key: 'tampilan', label: 'Tampilan', icon: 'bi-palette' },
            { key: 'maintenance', label: 'Backup & Database', icon: 'bi-database-gear' },
        ],
        tab: config.tabAwal || 'profil',

        /* Profil sekolah */
        jenjang: String(config.jenjang || '3'),
        satuan: String(config.satuanAwal || '1'),
        satuanMap: config.satuan || {},

        /* Jadwal kerja & toleransi */
        jadwal: config.jadwal || {},
        grace: Number(config.grace) || 0,

        /* Biometrik wajah */
        threshold: Number(config.threshold) || 0.72,
        faceMode: config.faceMode || 'ai_match',
        thresholdPreset: [
            { nilai: 0.65, label: 'Ketat (0.65)' },
            { nilai: 0.72, label: '⭐ Rekomendasi (0.72)' },
            { nilai: 0.78, label: 'Fleksibel (0.78)' },
        ],

        /* Poin & denda */
        tier: (config.tiers || []).map((t) => ({ denda: Number(t.denda) || 0, poin: Number(t.poin) || 0 })),
        simMenit: Number(config.simMenit) || 45,

        /* Notifikasi, WFH, QR */
        waMode: config.waMode || 'off',
        wfh: !!config.wfh,
        qr: !!config.qr,

        /* Surat teguran */
        teguran: Object.assign({ format: '', padding: 3, kode: '', defaultFormat: '', defaultPadding: 3, defaultKode: '', sekolah: '', npsn: '', nss: '', kepsek: '', nip: '' }, config.teguran || {}),

        /* Media branding */
        media: Object.assign({}, config.media || {}),
        mediaLoading: {},

        /* Status simpan */
        menyimpanProfil: false,
        menyimpanSetting: false,
        menguji: '',
        membersihkan: false,

        /* Tema */
        shades: SHADES,
        themeCfg: (config.theme && config.theme.client) || {},
        themePreset: (config.theme && config.theme.preset) || 'default',
        customHex: (config.theme && config.theme.custom) || HEX_BAWAAN,
        accentShift: Number(config.accentShift) || 0,
        menyimpanTema: false,

        init() {
            const kandidat = String(
                new URLSearchParams(window.location.search).get('tab')
                    || window.location.hash
                    || window.localStorage.getItem('activeSettingTab')
                    || '',
            ).replace('#', '').trim();

            if (this.tabOpsi.some((t) => t.key === kandidat)) this.tab = kandidat;

            this.terapkanTema();
        },

        /* ── Turunan ───────────────────────────── */

        get tabAksi() {
            return !['profil', 'tampilan', 'maintenance'].includes(this.tab);
        },

        get satuanOpsi() {
            const daftar = this.satuanMap[this.jenjang] || [];

            return daftar.map((label, i) => ({ value: String(i + 1), label }));
        },

        get batasOntime() {
            const jam = (this.jadwal[1] && this.jadwal[1].masuk) || '07:15';

            return this.tambahMenit(jam, this.grace);
        },

        get batasTelat() {
            return this.tambahMenit(this.batasOntime, 1);
        },

        get thresholdJudul() {
            if (this.threshold < 0.68) return 'Sangat Ketat (' + this.threshold.toFixed(2) + ')';
            if (this.threshold <= 0.75) return 'Rekomendasi Seimbang (' + this.threshold.toFixed(2) + ')';

            return 'Fleksibel / Longgar (' + this.threshold.toFixed(2) + ')';
        },

        get thresholdWarna() {
            if (this.threshold < 0.68) return 'text-red-600';
            if (this.threshold <= 0.75) return 'text-blue-600';

            return 'text-amber-600';
        },

        get thresholdDetail() {
            if (this.threshold < 0.68) {
                return 'Hanya menerima wajah yang sangat identik. Berisiko false-reject jika guru memakai kacamata atau di ruangan redup.';
            }
            if (this.threshold <= 0.75) {
                return 'Nilai seimbang terbaik untuk berbagai kondisi pencahayaan dan tipe kamera smartphone.';
            }

            return 'Sangat mudah lolos verifikasi. Cocok untuk lingkungan outdoor atau device berspesifikasi rendah.';
        },

        get sim() {
            return this.hitungSimulasi();
        },

        get simBreakdown() {
            const bagian = this.sim.breakdown;

            return bagian.length ? bagian.join(' + ') : 'Tidak ada denda.';
        },

        get paletCustom() {
            const hex = String(this.customHex || '');

            return this.paletteFromHex(/^#[0-9a-f]{6}$/.test(hex) ? hex : HEX_BAWAAN);
        },

        get teguranPreview() {
            const t = this.teguran;
            const pad = t.padding >= 1 && t.padding <= 6 ? t.padding : t.defaultPadding;
            const sekarang = new Date();
            const bulan = sekarang.getMonth() + 1;
            const tahun = sekarang.getFullYear();
            const nomor = String(1).padStart(pad, '0');

            const nilai = {
                kode_sekolah: String(t.kode || '').trim() || t.defaultKode,
                sekolah: t.sekolah || '',
                npsn: t.npsn || '',
                nss: t.nss || '',
                kepsek: t.kepsek || '',
                nip_kepsek: t.nip || '',
                nomor,
                nomor_urut: nomor,
                bulan_romawi: ROMAWI[bulan] || '',
                bulan_nama: NAMA_BULAN[bulan] || '',
                bulan: dua(bulan),
                tahun_2: dua(tahun % 100),
                tahun: String(tahun),
                level: '3',
                tanggal: dua(sekarang.getDate()) + '-' + dua(bulan) + '-' + tahun,
            };

            const teks = Object.entries(nilai).reduce(
                (hasil, [kunci, isi]) => hasil.split('{' + kunci + '}').join(isi),
                String(t.format || '').trim() || t.defaultFormat,
            );

            return teks.replace(/\/{2,}/g, '/').trim();
        },

        hitungSimulasi() {
            const menit = Number(this.simMenit) || 0;
            const hasil = { denda: 0, poin: 0, breakdown: [] };

            if (menit <= 0) return hasil;

            const ambil = (i) => this.tier[i] || { denda: 0, poin: 0 };

            const m1 = Math.min(menit, 30);
            hasil.denda += m1 * ambil(0).denda;
            hasil.poin += m1 * ambil(0).poin;
            hasil.breakdown.push(`Tier 1 (${m1}m × Rp${ambil(0).denda} / ${ambil(0).poin}pt)`);

            if (menit > 30) {
                const m2 = Math.min(menit - 30, 30);
                hasil.denda += m2 * ambil(1).denda;
                hasil.poin += m2 * ambil(1).poin;
                hasil.breakdown.push(`Tier 2 (${m2}m × Rp${ambil(1).denda} / ${ambil(1).poin}pt)`);
            }

            if (menit > 60) {
                const m3 = menit - 60;
                hasil.denda += m3 * ambil(2).denda;
                hasil.poin += m3 * ambil(2).poin;
                hasil.breakdown.push(`Tier 3 (${m3}m × Rp${ambil(2).denda} / ${ambil(2).poin}pt)`);
            }

            return hasil;
        },

        tambahMenit(jam, menit) {
            const bagian = String(jam || '07:15').split(':');
            const dasar = new Date();
            dasar.setHours(parseInt(bagian[0], 10) || 7, parseInt(bagian[1], 10) || 15, 0, 0);

            const hasil = new Date(dasar.getTime() + (Number(menit) || 0) * 60000);

            return dua(hasil.getHours()) + ':' + dua(hasil.getMinutes());
        },

        formatAngka(nilai) {
            return new Intl.NumberFormat('id-ID').format(Number(nilai) || 0);
        },

        /* ── Navigasi tab ──────────────────────── */

        pilihTab(kunci) {
            this.tab = kunci;
            window.localStorage.setItem('activeSettingTab', kunci);

            const konten = document.getElementById('main-content');
            if (konten) konten.scrollTo({ top: 0, behavior: 'smooth' });
        },

        /* ── Jadwal ────────────────────────────── */

        terapkanSenin() {
            const senin = this.jadwal[1] || { masuk: '07:30', pulang: '15:00' };

            for (let i = 2; i <= 6; i++) {
                if (!this.jadwal[i]) continue;
                this.jadwal[i].masuk = senin.masuk;
                this.jadwal[i].pulang = senin.pulang;
            }

            Swal.fire({
                icon: 'success',
                title: 'Tersalin!',
                text: 'Jam kerja hari Senin berhasil disalin ke Selasa s.d. Sabtu.',
                timer: 1500,
                showConfirmButton: false,
            });
        },

        /* ── Transport ─────────────────────────── */

        async kirimForm(url, isian) {
            const body = isian instanceof FormData ? isian : new FormData();

            if (!(isian instanceof FormData)) {
                Object.entries(isian || {}).forEach(([kunci, nilai]) => body.append(kunci, nilai ?? ''));
            }

            if (!body.has('_token')) body.append('_token', this.csrf);

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    body,
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                return { code: res.status, json: await res.json().catch(() => null) };
            } catch (e) {
                return { code: 0, json: null };
            }
        },

        pesanGagal(hasil, fallback) {
            if (hasil.code === 403) return 'Sesi berakhir atau akses tidak diizinkan. Silakan login ulang.';
            if (hasil.code === 413) return 'Ukuran file melebihi batas server.';

            return (hasil.json && hasil.json.message) || fallback;
        },

        /* ── Simpan profil ─────────────────────── */

        async simpanProfil() {
            if (this.menyimpanProfil) return;

            this.menyimpanProfil = true;

            try {
                const hasil = await this.kirimForm(this.urls.profil, new FormData(this.$refs.profilForm));

                if (hasil.json && hasil.json.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Tersimpan!',
                        text: hasil.json.message || 'Profil dan identitas sekolah berhasil diperbarui.',
                        timer: 1500,
                        showConfirmButton: false,
                    });

                    return;
                }

                Swal.fire('Gagal', this.pesanGagal(hasil, 'Terjadi kesalahan saat menyimpan'), 'error');
            } finally {
                this.menyimpanProfil = false;
            }
        },

        /* ── Simpan pengaturan presensi ────────── */

        async simpanPengaturan() {
            if (this.menyimpanSetting) return;

            this.menyimpanSetting = true;

            try {
                const hasil = await this.kirimForm(this.urls.setting, new FormData(this.$refs.settingForm));

                if (hasil.json && hasil.json.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pengaturan Tersimpan!',
                        text: hasil.json.message || 'Kebijakan jam kerja dan presensi berhasil diperbarui.',
                        timer: 1500,
                        showConfirmButton: false,
                    });

                    return;
                }

                Swal.fire('Gagal', this.pesanGagal(hasil, 'Terjadi kesalahan saat menyimpan'), 'error');
            } finally {
                this.menyimpanSetting = false;
            }
        },

        /* ── Media branding ────────────────────── */

        async unggahMedia(event, field) {
            const input = event.target;
            const file = input.files && input.files[0];

            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                Swal.fire('File Terlalu Besar', 'Batas maksimal file adalah 5MB', 'warning');
                input.value = '';

                return;
            }

            this.mediaLoading[field] = true;

            const body = new FormData();
            body.append('logo', file);

            try {
                const hasil = await this.kirimForm(this.urls.upload + '/' + field, body);

                if (hasil.json && hasil.json.status) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Diunggah!',
                        text: 'Media ' + field + ' berhasil diperbarui.',
                        timer: 1200,
                        showConfirmButton: false,
                    });
                    window.location.reload();

                    return;
                }

                Swal.fire('Upload Gagal', this.pesanGagal(hasil, 'Gagal mengunggah file'), 'error');
            } finally {
                this.mediaLoading[field] = false;
                input.value = '';
            }
        },

        async hapusMedia(field) {
            const konfirmasi = await Swal.fire({
                title: 'Hapus Media?',
                text: 'File ' + field + ' akan dihapus dari server dan data dikosongkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
            });

            if (!konfirmasi.isConfirmed) return;

            const hasil = await this.kirimForm(this.urls.hapusFile, {
                field,
                src: this.media[field] || '',
            });

            if (hasil.json && hasil.json.status) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Dihapus!',
                    text: (hasil.json.fileDeleted ? 'File dihapus dari server' : 'Data dikosongkan') + ' dan tampilan disegarkan.',
                    timer: 1400,
                    showConfirmButton: false,
                });
                window.location.reload();

                return;
            }

            Swal.fire('Gagal', this.pesanGagal(hasil, 'Gagal menghapus file'), 'error');
        },

        async salinUri(uri) {
            try {
                await navigator.clipboard.writeText(uri);
                Swal.fire({
                    icon: 'success',
                    title: 'Disalin!',
                    text: 'Authorized Redirect URI telah disalin ke clipboard.',
                    timer: 1500,
                    showConfirmButton: false,
                });
            } catch (e) {
                Swal.fire('Gagal Menyalin', 'Salin manual dari kolom Authorized Redirect URI.', 'warning');
            }
        },

        sisipPlaceholder(placeholder) {
            this.teguran.format = String(this.teguran.format || '') + placeholder;

            this.$nextTick(() => this.$refs.teguranFormat?.focus());
        },

        /* ── Notifikasi & pembersihan ──────────── */

        async ujiNotifikasi(channel) {
            if (this.menguji) return;

            this.menguji = channel;

            try {
                const body = new FormData(this.$refs.settingForm);
                body.set('channel', channel);

                const hasil = await this.kirimForm(this.urls.testNotif, body);
                const json = hasil.json || {};

                Swal.fire(json.status ? 'Berhasil' : 'Gagal', json.message || 'Gagal mengirim notifikasi test.', json.status ? 'success' : 'error');
            } finally {
                this.menguji = '';
            }
        },

        async bersihkanFoto() {
            if (this.membersihkan) return;

            const konfirmasi = await Swal.fire({
                title: 'Hapus Foto Absen Bulan Lalu?',
                text: 'File gambar selfie bulan-bulan sebelumnya akan dihapus permanen dari server untuk menghemat kapasitas disk.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Bersihkan!',
                cancelButtonText: 'Batal',
            });

            if (!konfirmasi.isConfirmed) return;

            this.membersihkan = true;

            try {
                const hasil = await this.kirimForm(this.urls.cleanPhotos, {});
                const json = hasil.json || {};

                Swal.fire(json.status ? 'Berhasil' : 'Gagal', json.message || 'Terjadi kesalahan sistem saat menghapus foto', json.status ? 'success' : 'error');
            } finally {
                this.membersihkan = false;
            }
        },

        /* ── Tema: matematika warna (port dari Theme::*) ── */

        hexToRgb(hex) {
            let teks = String(hex).replace('#', '');

            if (teks.length === 3) {
                teks = teks[0] + teks[0] + teks[1] + teks[1] + teks[2] + teks[2];
            }

            return [
                parseInt(teks.substr(0, 2), 16),
                parseInt(teks.substr(2, 2), 16),
                parseInt(teks.substr(4, 2), 16),
            ];
        },

        rgbToHsl(r, g, b) {
            r /= 255; g /= 255; b /= 255;

            const max = Math.max(r, g, b);
            const min = Math.min(r, g, b);
            const l = (max + min) / 2;

            if (max === min) return [0, 0, l];

            const d = max - min;
            const s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            let h;

            if (max === r) h = (g - b) / d + (g < b ? 6 : 0);
            else if (max === g) h = (b - r) / d + 2;
            else h = (r - g) / d + 4;

            return [h / 6, s, l];
        },

        hueToRgb(p, q, t) {
            if (t < 0) t += 1;
            if (t > 1) t -= 1;
            if (t < 1 / 6) return p + (q - p) * 6 * t;
            if (t < 1 / 2) return q;
            if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;

            return p;
        },

        hslToHex(h, s, l) {
            const to = (v) => Math.round(v * 255).toString(16).padStart(2, '0');

            if (s === 0) return '#' + to(l) + to(l) + to(l);

            const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
            const p = 2 * l - q;

            return '#' + to(this.hueToRgb(p, q, h + 1 / 3)) + to(this.hueToRgb(p, q, h)) + to(this.hueToRgb(p, q, h - 1 / 3));
        },

        paletteFromHex(hex) {
            const rgb = this.hexToRgb(hex);
            const [h, s, l] = this.rgbToHsl(rgb[0], rgb[1], rgb[2]);
            const lightest = 0.97;
            const darkest = 0.06;
            const towardLight = { 50: 0.94, 100: 0.80, 200: 0.60, 300: 0.35, 400: 0.15 };
            const towardDark = { 600: 0.14, 700: 0.26, 800: 0.42, 900: 0.55 };
            const saturation = { 50: 0.98, 100: 1.0, 200: 1.0, 300: 1.0, 400: 1.0, 500: 1.0, 600: 0.97, 700: 0.90, 800: 0.82, 900: 0.74 };
            const out = {};

            Object.keys(towardLight).forEach((shade) => {
                out[shade] = this.hslToHex(h, Math.min(1, s * saturation[shade]), l + (lightest - l) * towardLight[shade]);
            });

            out[500] = String(hex).toLowerCase();

            Object.keys(towardDark).forEach((shade) => {
                out[shade] = this.hslToHex(h, Math.min(1, s * saturation[shade]), l - (l - darkest) * towardDark[shade]);
            });

            return out;
        },

        shiftHue(hex, degrees) {
            const rgb = this.hexToRgb(hex);
            const [h, s, l] = this.rgbToHsl(rgb[0], rgb[1], rgb[2]);
            let baru = h + degrees / 360;

            baru = baru - Math.floor(baru);

            return this.hslToHex(baru, s, l);
        },

        buildSemantic(primary, accent) {
            const get = (palette, shade) => (palette && palette[shade] ? palette[shade] : '#000000');

            return {
                surface: '#ffffff',
                'surface-hover': get(primary, '50'),
                'surface-muted': '#f8fafc',
                'surface-inverse': '#0f172a',
                'surface-inverse-hover': '#1e293b',
                border: '#e2e8f0',
                'border-focus': get(primary, '500'),
                'border-muted': '#cbd5e1',
                'text-primary': '#0f1729',
                'text-secondary': '#475569',
                'text-muted': '#94a3b8',
                'text-inverse': '#ffffff',
                'text-success': get(primary, '700'),
                'text-warning': get(accent, '700'),
                'text-error': '#dc2626',
                'text-info': get(primary, '700'),
                primary: get(primary, '600'),
                'primary-hover': get(primary, '700'),
                'primary-active': get(primary, '800'),
                'primary-subtle': get(primary, '50'),
                accent: get(accent, '600'),
                'accent-hover': get(accent, '700'),
                'accent-active': get(accent, '800'),
                'accent-subtle': get(accent, '50'),
            };
        },

        resolveTheme(preset, hex) {
            const fallback = (this.themeCfg.presets && this.themeCfg.presets.default) || {};

            if (preset === 'custom') {
                const primary = this.paletteFromHex(hex);
                const accent = this.paletteFromHex(this.shiftHue(hex, this.accentShift));

                return {
                    brand: primary,
                    primary,
                    accent,
                    indigo: fallback.indigo || primary,
                    purple: fallback.purple || primary,
                    secondary: primary[400],
                    tertiary: primary[700],
                    semantic: this.buildSemantic(primary, accent),
                };
            }

            const p = (this.themeCfg.presets && this.themeCfg.presets[preset]) || fallback;
            const sem = this.themeCfg.semantic || this.buildSemantic(p.primary || p.brand, p.accent || p.brand);

            return Object.assign({}, p, { semantic: sem });
        },

        terapkanTema() {
            const t = this.resolveTheme(this.themePreset, this.customHex);

            if (!t || !t.brand) return;

            const root = document.documentElement;
            const families = {
                primary: t.primary || t.brand,
                accent: t.accent || t.brand,
                indigo: t.indigo || t.brand,
                purple: t.purple || t.brand,
            };

            Object.keys(families).forEach((family) => {
                SHADES.forEach((s) => {
                    const value = families[family][s];

                    if (!value) return;

                    root.style.setProperty('--' + family + '-' + s, value);
                    root.style.setProperty('--' + family + '-' + s + '-rgb', this.hexToRgb(value).join(' '));
                });
            });

            SHADES.forEach((s) => {
                const value = t.brand[s];

                if (!value) return;

                root.style.setProperty('--brand-' + s, value);
                root.style.setProperty('--brand-' + s + '-rgb', this.hexToRgb(value).join(' '));
            });

            ['secondary', 'tertiary'].forEach((key) => {
                const value = t[key];

                if (!value) return;

                root.style.setProperty('--brand-' + key, value);
                root.style.setProperty('--brand-' + key + '-rgb', this.hexToRgb(value).join(' '));
            });

            root.style.setProperty('--primary', t.brand[600]);
            root.style.setProperty('--primary-rgb', this.hexToRgb(t.brand[600]).join(', '));

            const sem = t.semantic || {};

            Object.keys(sem).forEach((key) => {
                const value = sem[key];

                if (!value) return;

                root.style.setProperty('--' + key, value);
                root.style.setProperty('--' + key + '-rgb', this.hexToRgb(value).join(' '));
            });

            const meta = document.querySelector('meta[name="theme-color"]');
            if (meta) meta.setAttribute('content', t.brand[600]);
        },

        pilihTemaPreset(preset) {
            this.themePreset = preset;
            this.terapkanTema();
        },

        pakaiWarna() {
            const hex = String(this.customHex || '').trim().toLowerCase();

            if (!/^#[0-9a-f]{6}$/.test(hex)) return;

            this.customHex = hex;
            this.themePreset = 'custom';
            this.terapkanTema();
        },

        async simpanTema() {
            if (this.menyimpanTema) return;

            if (this.themePreset === 'custom' && !/^#[0-9a-f]{6}$/i.test(this.customHex)) {
                Swal.fire('Warna belum valid', 'Masukkan kode HEX 6 digit, misalnya #2563eb.', 'warning');

                return;
            }

            this.menyimpanTema = true;

            try {
                const hasil = await this.kirimForm(this.urls.saveTheme, {
                    preset: this.themePreset,
                    custom: this.customHex,
                });

                if (hasil.json && hasil.json.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Tema Disimpan!',
                        text: 'Warna aplikasi berhasil diperbarui.',
                        timer: 1600,
                        showConfirmButton: false,
                    });

                    return;
                }

                Swal.fire('Gagal', this.pesanGagal(hasil, 'Terjadi kesalahan saat menyimpan tema'), 'error');
            } finally {
                this.menyimpanTema = false;
            }
        },
    }));
});
</script>
@endsection
