<?php
// Partial sisi BELAKANG kartu siswa portrait (CR80 54×85.6mm).
// Desain mengikuti kartu pelajar resmi: header biru "DATA PESERTA DIDIK",
// ombak biru muda & garis emas, latar biru muda dengan watermark logo,
// tabel data siswa, pil "TATA TERTIB SISWA" + daftar aturan, barcode, dan
// ombak bawah dengan motto sekolah.
//
// Template latar: $template === 'png' memakai latar PNG hasil unggahan
// /settings (kolom setting.kartu_bg_belakang) — kop, watermark, garis
// pembatas dan ombak bawah disembunyikan; tabel, tata tertib & barcode tetap.
$isLaki = ($s->jenis_kelamin ?? 'L') === 'L';
$ttlClean = \App\Services\KartuSiswaFormatter::ttl($s);
$barcodeVal = $s->qr_value;
$kartuTemplate = ($template ?? 'vektor') === 'png' ? 'png' : 'vektor';
?>
<div class="card-dp card-dp-back<?php echo $kartuTemplate === 'png' ? ' card-dp-tpl-png' : ''; ?>">
    @if ($kartuTemplate === 'png')
        <img class="dp-bg-img" src="{{ $kartu_bg_belakang }}" alt="Latar kartu belakang">
    @else
    <!-- Kop: judul sisi belakang -->
    <div class="dp-back-header">
        <h4 class="dp-back-title">DATA PESERTA DIDIK</h4>
    </div>
    <!-- Dekorasi: ombak biru muda + garis emas, menjorok tipis di bawah header -->
    <svg class="dp-wave-top dp-wave-top-back" viewBox="0 0 204 14" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0,0 L204,0 L204,2 C160,11 108,3 64,8 C38,11 16,12 0,13.5 Z" fill="#8ecbf5"/>
        <path d="M0,0 L204,0 L204,1 C156,8 100,1.5 56,6 C30,9 12,9.5 0,11" fill="none" stroke="#f0b429" stroke-width="2.5"/>
    </svg>

    <!-- Watermark -->
    <div class="dp-back-watermark">
        <img src="{{ $logo_url }}" alt="Watermark" onerror="this.src='{{ asset('assets/img/logo.png') }}'">
    </div>
    @endif

    <div class="dp-back-body">
        <!-- Data Siswa -->
        <table class="dp-info-table">
            <tr>
                <td class="dp-lbl">Nama</td>
                <td class="dp-sep">:</td>
                <td class="dp-val" title="{{ $s->nama }}">{{ $s->nama }}</td>
            </tr>
            <tr>
                <td class="dp-lbl">JK</td>
                <td class="dp-sep">:</td>
                <td class="dp-val">{{ $isLaki ? 'Laki-laki' : 'Perempuan' }}</td>
            </tr>
            <tr>
                <td class="dp-lbl">TTL</td>
                <td class="dp-sep">:</td>
                <td class="dp-val ttl-wrap" title="{{ $ttlClean }}">{{ $ttlClean }}</td>
            </tr>
            <tr>
                <td class="dp-lbl">NIS/NISN</td>
                <td class="dp-sep">:</td>
                <td class="dp-val">{{ ($s->nis ? $s->nis . ' / ' : '') . ($s->nisn ?: '-') }}</td>
            </tr>
            <tr>
                <td class="dp-lbl">Kelas</td>
                <td class="dp-sep">:</td>
                <td class="dp-val">{{ $s->nama_kelas }}</td>
            </tr>
        </table>

        <!-- Garis biru -->
        <div class="dp-info-divider"></div>

        <!-- Tata Tertib -->
        <div class="dp-rules-section">
            <div class="dp-rules-pill">TATA TERTIB SISWA</div>
            <div class="dp-rules-list">
                <div class="dp-rule-item">
                    <span class="dp-rule-num">1.</span>
                    <span class="dp-rule-text">Siswa wajib membawa kartu pelajar setiap saat.</span>
                </div>
                <div class="dp-rule-item">
                    <span class="dp-rule-num">2.</span>
                    <span class="dp-rule-text">Kartu ini digunakan sebagai identitas resmi sekolah.</span>
                </div>
                <div class="dp-rule-item">
                    <span class="dp-rule-num">3.</span>
                    <span class="dp-rule-text">Jika kartu hilang, segera lapor ke pihak sekolah.</span>
                </div>
                <div class="dp-rule-item">
                    <span class="dp-rule-num">4.</span>
                    <span class="dp-rule-text">Kartu tidak boleh dipindahtangankan.</span>
                </div>
            </div>
        </div>

        <!-- Barcode -->
        <div class="dp-barcode-wrap">
            <svg class="barcode-svg dp-barcode-svg" data-barcode="{{ $barcodeVal }}"></svg>
            <span class="dp-barcode-text">{{ $barcodeVal }}</span>
        </div>
    </div>

    @if ($kartuTemplate !== 'png')
    <!-- Ombak bawah + motto -->
    <div class="dp-back-footer-wave-wrap">
        <svg class="dp-back-footer-wave" viewBox="0 0 204 30" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0,10 C50,0 120,2 204,10 L204,30 L0,30 Z" fill="#0b3d91"/>
            <path d="M0,9 C50,-1 120,1 204,9" fill="none" stroke="#f0b429" stroke-width="2.5"/>
        </svg>
        <div class="dp-footer-motto">Belajar, Berprestasi, Berakhlak Mulia</div>
    </div>
    @endif
</div>
