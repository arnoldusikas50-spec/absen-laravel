<?php
// Partial sisi BELAKANG kartu siswa landscape (CR80 85.6×54mm).
$isLaki = ($s->jenis_kelamin ?? 'L') === 'L';
$nisnClean = \App\Services\KartuSiswaFormatter::nomorSiswa($s);
$ttlClean = \App\Services\KartuSiswaFormatter::ttl($s);
$barcodeVal = $s->qr_value;
?>
<div class="card-dl card-dl-back">
    <!-- Header Back -->
    <div class="dl-back-header-bar">
        <h4 class="dl-back-title">DATA PESERTA DIDIK</h4>
    </div>

    <!-- Watermark logo transparan -->
    <div class="dl-back-watermark">
        <img src="{{ $logo_url }}" alt="Watermark" onerror="this.src='{{ asset('assets/img/logo.png') }}'">
    </div>

    <div class="dl-back-body">
        <!-- Left Column: Data Siswa + Barcode -->
        <div class="dl-back-col-left">
            <div class="dl-back-sublabel">Identitas Siswa</div>
            <table class="dl-back-table">
                <tr>
                    <td class="b-lbl">Nama</td>
                    <td class="b-sep">:</td>
                    <td class="b-val" title="{{ $s->nama }}">{{ $s->nama }}</td>
                </tr>
                <tr>
                    <td class="b-lbl">JK</td>
                    <td class="b-sep">:</td>
                    <td class="b-val">{{ $isLaki ? 'Laki-laki' : 'Perempuan' }}</td>
                </tr>
                <tr>
                    <td class="b-lbl">TTL</td>
                    <td class="b-sep">:</td>
                    <td class="b-val ttl-wrap" title="{{ $ttlClean }}">{{ $ttlClean }}</td>
                </tr>
                <tr>
                    <td class="b-lbl">NISN</td>
                    <td class="b-sep">:</td>
                    <td class="b-val">{{ $nisnClean }}</td>
                </tr>
                <tr>
                    <td class="b-lbl">Kelas</td>
                    <td class="b-sep">:</td>
                    <td class="b-val">{{ $s->nama_kelas }}</td>
                </tr>
            </table>

            <!-- Barcode di bawah tabel -->
            <div class="dl-barcode-box">
                <svg class="barcode-svg" data-barcode="{{ $barcodeVal }}"></svg>
                <span class="dl-barcode-text">{{ $barcodeVal }}</span>
            </div>
        </div>

        <!-- Right Column: Tata Tertib & Pengesahan Kepala Sekolah -->
        <div class="dl-back-col-right">
            <div class="dl-back-rules-box">
                <div class="dl-back-pill">TATA TERTIB SISWA</div>
                <div class="dl-rules-list">
                    <div class="dl-rule-item">
                        <span class="dl-rule-num">1.</span>
                        <span class="dl-rule-text">Wajib membawa kartu saat KBM & presensi.</span>
                    </div>
                    <div class="dl-rule-item">
                        <span class="dl-rule-num">2.</span>
                        <span class="dl-rule-text">Merupakan identitas resmi siswa di sekolah.</span>
                    </div>
                    <div class="dl-rule-item">
                        <span class="dl-rule-num">3.</span>
                        <span class="dl-rule-text">Jika kartu hilang, segera lapor ke sekolah.</span>
                    </div>
                    <div class="dl-rule-item">
                        <span class="dl-rule-num">4.</span>
                        <span class="dl-rule-text">Kartu tidak boleh dipindahtangankan.</span>
                    </div>
                </div>
            </div>

            <!-- Pengesahan Kepala Sekolah -->
            <div class="dl-back-sign">
                <div>{{ $setting->kota ?? 'Kupang' }}, {{ date('Y') }}</div>
                <div class="sign-stamp-area">
                @if (!empty($stampel_url))
                    <img class="sign-stamp" src="{{ $stampel_url }}" alt="Cap Sekolah">
                @endif
                    <div style="font-weight:700;">Kepala Sekolah,</div>
                    <div class="dl-sign-name" style="margin-top:10px;">{{ $setting->kepsek ?? 'Kepala Sekolah' }}</div>
                    <div>NIP. {{ $setting->nip ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Card: Motto Sekolah -->
    <div class="dl-back-footer">
        <span class="dl-motto">Belajar, Berprestasi, Berakhlak Mulia</span>
    </div>
</div>
