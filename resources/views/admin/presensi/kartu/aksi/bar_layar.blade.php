{{--
    Action bar LAYAR untuk dokumen cetak kartu presensi.

    Dipakai oleh dua dokumen cetak:
      - admin/presensi/kartu/cetak.blade.php       -> $varian = 'siswa'
      - admin/presensi/kartu/guru_cetak.blade.php  -> $varian = 'guru'

    Seluruh kelas di file ini memakai varian `layar:` (menghasilkan
    `@media screen { ... }`), dan folder `kartu/aksi` adalah satu-satunya
    sumber pemindaian Tailwind untuk resources/css/cetak-aksi.css. Jadi tidak
    ada daftar kelas terpisah yang perlu disinkronkan: tambahkan utility baru
    langsung di markup di bawah.

    Karena pemindai membaca seluruh teks di folder ini (termasuk teks dan
    komentar), `$labelGrid` — dan `$modeGrid` untuk varian guru — dikirim oleh
    view induk yang berada di luar folder pindaian.
--}}
@php
    $varian = $varian ?? 'siswa';

    if ($varian === 'guru') {
        $jumlahKartu = count($guru_list);
        $labelKartu = 'Kartu Guru';
        $subjudul = $judul_cetak;
    } else {
        $jumlahKartu = count($siswa_list);
        $labelKartu = 'Kartu Siswa';
        $subjudul = $nama_kelas;
    }

    // Kelas utility layar (varian `layar:`) untuk action bar.
    $kelasBar = 'layar:mx-auto layar:mb-5 layar:flex layar:w-full layar:max-w-[1100px] layar:flex-col layar:gap-3 layar:rounded-[14px] layar:border layar:border-slate-200 layar:bg-white layar:px-5 layar:py-4 layar:shadow-[0_4px_16px_rgba(0,0,0,0.06)]';
    $kelasHead = 'layar:flex layar:w-full layar:flex-wrap layar:items-center layar:justify-between layar:gap-3.5 layar:max-sm:items-start';
    $kelasJudul = 'layar:inline-flex layar:flex-wrap layar:items-center layar:gap-[9px] layar:text-sm layar:font-extrabold layar:text-slate-900';
    $kelasIkonJudul = 'layar:text-base layar:text-blue-600';
    $kelasPill = 'layar:inline-flex layar:items-center layar:gap-[5px] layar:rounded-full layar:border layar:border-blue-200 layar:bg-blue-50 layar:px-2.5 layar:py-[3px] layar:text-[11px] layar:font-extrabold layar:text-blue-700';
    $kelasPillMuted = 'layar:border-slate-200 layar:bg-slate-100 layar:text-slate-700';
    $kelasGrupTombol = 'layar:flex layar:items-center layar:gap-2 layar:max-sm:w-full';
    $kelasTombol = 'layar:inline-flex layar:cursor-pointer layar:items-center layar:justify-center layar:gap-1.5 layar:rounded-lg layar:border-0 layar:px-3.5 layar:py-[7px] layar:text-[11.5px] layar:font-bold layar:no-underline layar:transition-all layar:max-sm:flex-1';
    $kelasTombolSekunder = 'layar:border layar:border-slate-300 layar:bg-slate-50 layar:text-slate-700 layar:hover:bg-slate-200';
    $kelasTombolPrimer = 'layar:bg-blue-600 layar:text-white layar:hover:bg-blue-700';
    $kelasTombolAktif = 'layar:border layar:border-slate-300 layar:bg-blue-700 layar:text-white layar:shadow-[0_2px_6px_rgba(29,78,216,0.3)]';
    $kelasKontrol = 'layar:flex layar:w-full layar:flex-wrap layar:items-center layar:gap-4 layar:border-t layar:border-dashed layar:border-slate-200 layar:pt-[13px]';
    $kelasCtrlGrup = 'layar:inline-flex layar:items-center layar:gap-2';
    $kelasCtrlLabel = 'layar:text-[10px] layar:font-extrabold layar:uppercase layar:tracking-[0.08em] layar:text-slate-400';
    $kelasSeg = 'layar:inline-flex layar:items-center layar:gap-1 layar:rounded-[11px] layar:bg-slate-100 layar:p-1';
    $kelasTip = 'layar:flex layar:w-full layar:items-center layar:gap-[7px] layar:border-t layar:border-dashed layar:border-slate-200 layar:pt-[11px] layar:text-[11px] layar:text-slate-500';
    $kelasIkonTip = 'layar:shrink-0 layar:text-amber-500';
    $kelasAlert = 'layar:flex layar:w-full layar:items-start layar:gap-[7px] layar:rounded-lg layar:border layar:border-amber-300 layar:bg-amber-50 layar:px-3 layar:py-2 layar:text-[11px] layar:font-semibold layar:text-amber-700';
@endphp
<div class="top-bar no-print {{ $kelasBar }}">
    <div class="{{ $kelasHead }}">
        <div class="{{ $kelasJudul }}">
            <i class="bi bi-printer-fill {{ $kelasIkonJudul }}"></i> Siap Cetak
            <span class="{{ $kelasPill }}">{{ $jumlahKartu }} {{ $labelKartu }}</span>
            <span class="{{ $kelasPill }} {{ $kelasPillMuted }}">{{ $subjudul }}</span>
        </div>
        <div class="{{ $kelasGrupTombol }}">
            <a href="{{ $previewUrl }}" class="{{ $kelasTombol }} {{ $kelasTombolSekunder }}">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button onclick="window.print()" class="{{ $kelasTombol }} {{ $kelasTombolPrimer }}">
                <i class="bi bi-printer"></i> Cetak Sekarang
            </button>
        </div>
    </div>

    <div class="{{ $kelasKontrol }}">
        @if ($varian === 'guru')
            <div class="{{ $kelasCtrlGrup }}">
                <span class="{{ $kelasCtrlLabel }}">Desain</span>
                <div class="{{ $kelasSeg }}">
                    <a href="{{ $cetakBase . 'desain=landscape&mode=' . $modeAktif }}" class="{{ $kelasTombol }} {{ $desainAktif === 'landscape' ? $kelasTombolAktif : $kelasTombolSekunder }}" title="Ukuran 9.2 × 5.8 cm">
                        <i class="bi bi-layers-fill"></i> Landscape
                    </a>
                    <a href="{{ $cetakBase . 'desain=portrait&mode=' . $modeAktif }}" class="{{ $kelasTombol }} {{ $desainAktif === 'portrait' ? $kelasTombolAktif : $kelasTombolSekunder }}" title="Portrait 5.8 × 9.2 cm">
                        <i class="bi bi-card-heading"></i> Portrait
                    </a>
                </div>
            </div>
            <div class="{{ $kelasCtrlGrup }}">
                <span class="{{ $kelasCtrlLabel }}">Tata Letak</span>
                <div class="{{ $kelasSeg }}">
                    <a href="{{ $cetakBase . 'desain=' . $desainAktif . '&mode=' . $modeGrid }}" class="{{ $kelasTombol }} {{ $modeAktif === $modeGrid ? $kelasTombolAktif : $kelasTombolSekunder }}" title="{{ $labelGrid }} — beberapa kartu per lembar">
                        <i class="bi bi-grid-3x3-gap"></i> {{ $labelGrid }}
                    </a>
                    <a href="{{ $cetakBase . 'desain=' . $desainAktif . '&mode=ktp' }}" class="{{ $kelasTombol }} {{ $modeAktif === 'ktp' ? $kelasTombolAktif : $kelasTombolSekunder }}" title="Cetak langsung ukuran kartu KTP (85.6 × 54 mm)">
                        <i class="bi bi-credit-card-2-front"></i> Ukuran KTP
                    </a>
                </div>
            </div>
        @else
            <div class="{{ $kelasCtrlGrup }}">
                <span class="{{ $kelasCtrlLabel }}">Desain</span>
                <div class="{{ $kelasSeg }}">
                    <a href="{{ $qsDesain('duplex') }}" class="{{ $kelasTombol }} {{ $desainAktif === 'duplex' ? $kelasTombolAktif : $kelasTombolSekunder }}" title="Ukuran 8.56 × 5.4 cm">
                        <i class="bi bi-layers-fill"></i> Landscape
                    </a>
                    <a href="{{ $qsDesain('duplex_portrait') }}" class="{{ $kelasTombol }} {{ $desainAktif === 'duplex_portrait' ? $kelasTombolAktif : $kelasTombolSekunder }}" title="Portrait 5.4 × 8.56 cm">
                        <i class="bi bi-card-heading"></i> Portrait
                    </a>
                </div>
            </div>
            <div class="{{ $kelasCtrlGrup }}">
                <span class="{{ $kelasCtrlLabel }}">Tata Letak</span>
                <div class="{{ $kelasSeg }}">
                    <a href="{{ $qsMode('lembar') }}" class="{{ $kelasTombol }} {{ $modeAktif === 'lembar' ? $kelasTombolAktif : $kelasTombolSekunder }}" title="Sisi depan & belakang berdampingan dalam satu lembar">
                        <i class="bi bi-file-earmark"></i> Lembar
                    </a>
                    <a href="{{ $qsMode('bolakbalik') }}" class="{{ $kelasTombol }} {{ $modeAktif === 'bolakbalik' ? $kelasTombolAktif : $kelasTombolSekunder }}" title="{{ $labelGrid }} dua tahap: semua depan, lalu semua belakang">
                        <i class="bi bi-layout-text-window-reverse"></i> Bolak-balik
                    </a>
                    <a href="{{ $qsMode('ktp') }}" class="{{ $kelasTombol }} {{ $modeAktif === 'ktp' ? $kelasTombolAktif : $kelasTombolSekunder }}" title="Cetak langsung ukuran kartu KTP (85.6 × 54 mm)">
                        <i class="bi bi-credit-card-2-front"></i> Ukuran KTP
                    </a>
                </div>
            </div>
            @if ($isPortrait)
                <div class="{{ $kelasCtrlGrup }}">
                    <span class="{{ $kelasCtrlLabel }}">Template</span>
                    <div class="{{ $kelasSeg }}">
                        <a href="{{ $qsTemplate('vektor') }}" class="{{ $kelasTombol }} {{ $templateAktif === 'vektor' ? $kelasTombolAktif : $kelasTombolSekunder }}" title="Desain vektor bawaan">
                            <i class="bi bi-vector-pen"></i> Vektor
                        </a>
                        <a href="{{ $templatePngSiap ? $qsTemplate('png') : ($bisaSetelan ? url('setting') : '#') }}"
                           {{ $templatePngSiap ? '' : ($bisaSetelan ? 'title="Unggah latar kartu depan &amp; belakang di menu Pengaturan dulu" style="opacity:.45;"' : 'aria-disabled="true" title="Latar kartu diunggah admin melalui menu Pengaturan" style="pointer-events:none;opacity:.45;"') }}
                           class="{{ $kelasTombol }} {{ $templateAktif === 'png' ? $kelasTombolAktif : $kelasTombolSekunder }}" title="Latar PNG hasil unggahan /settings">
                            <i class="bi bi-image"></i> Latar PNG
                        </a>
                    </div>
                </div>
            @endif
            @if ($modeAktif === 'bolakbalik')
                <div class="{{ $kelasCtrlGrup }}">
                    <span class="{{ $kelasCtrlLabel }}">Balik Kertas</span>
                    <div class="{{ $kelasSeg }}">
                        <a href="{{ $qsFlip('long') }}" class="{{ $kelasTombol }} {{ $flipAktif === 'long' ? $kelasTombolAktif : $kelasTombolSekunder }}" title="Balik kertas pada sisi panjang (sisi belakang tegak lurus)">
                            <i class="bi bi-arrow-left-right"></i> Sisi Panjang
                        </a>
                        <a href="{{ $qsFlip('short') }}" class="{{ $kelasTombol }} {{ $flipAktif === 'short' ? $kelasTombolAktif : $kelasTombolSekunder }}" title="Balik kertas pada sisi pendek (sisi belakang terbalik 180°)">
                            <i class="bi bi-arrow-down-up"></i> Sisi Pendek
                        </a>
                    </div>
                </div>
            @endif
        @endif
    </div>

    @if ($varian === 'guru' && ! empty($terpotong))
        <div class="{{ $kelasAlert }}">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>Daftar dipotong pada {{ (int) ($batas_cetak ?? 500) }} kartu pertama — cetak sisanya dengan memilih satu guru.</span>
        </div>
    @endif

    <div class="{{ $kelasTip }}">
        <i class="bi bi-lightbulb-fill {{ $kelasIkonTip }}"></i>
        <span>
            Aktifkan <strong>“Background graphics”</strong> pada dialog cetak agar warna kartu tercetak penuh, dan pilih ukuran kertas
            <strong>{{ $modeAktif === 'ktp' ? 'Card Size / CR80' : 'A4' }}</strong>.
        </span>
    </div>
</div>
