<?php
// Partial sisi BELAKANG kartu siswa portrait (CR80 54×85.6mm).
$isLaki = ($s->jenis_kelamin ?? 'L') === 'L';
$ttlClean = \App\Services\KartuSiswaFormatter::ttl($s);
$barcodeVal = $s->qr_value;
?>
<div class="card-dp card-dp-back">
    <div class="dp-back-header">
        <h4 class="dp-back-title">DATA PESERTA DIDIK</h4>
        <!-- Accent ombak di bawah header back -->
        <svg class="dp-header-accent" viewBox="0 0 204 6" preserveAspectRatio="none">
            <path d="M0,0 L204,0 L204,2 Q150,6 102,3 Q50,0 0,3 Z" fill="#f59e0b" />
            <path d="M0,3 Q50,0 102,3 Q150,6 204,2 L204,4 Q150,7 102,4 Q50,1 0,4 Z" fill="#38bdf8" />
        </svg>
    </div>

    <!-- Watermark -->
    <div class="dp-back-watermark">
        <img src="{{ $logo_url }}" alt="Watermark" onerror="this.src='{{ asset('assets/img/logo.png') }}'">
    </div>

    <div class="dp-back-body">
        <table class="dp-info-table">
            <tr>
                <td class="dp-lbl">Nama</td>
                <td class="dp-sep">:</td>
                <td class="dp-val" title="{{ $s->nama }}">{{ $s->nama }}</td>
            </tr>
            <tr>
                <td class="dp-lbl">Jenis Kelamin</td>
                <td class="dp-sep">:</td>
                <td class="dp-val">{{ $isLaki ? 'Laki-laki' : 'Perempuan' }}</td>
            </tr>
            <tr>
                <td class="dp-lbl">Tempat Tgl Lahir</td>
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
        <!-- Divider -->
        <div style="height:1px;background:linear-gradient(90deg,transparent,#e2e8f0,transparent);margin:4px 0 3px;"></div>

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

        <!-- Pengesahan Kepala Sekolah -->
        <div class="dp-back-sign">
            <div>{{ $setting->kota ?? 'Kupang' }}, {{ date('Y') }}</div>
            <div class="sign-stamp-area">
            @if (!empty($stampel_url))
                <img class="sign-stamp" src="{{ $stampel_url }}" alt="Cap Sekolah">
            @endif
                <div style="font-weight:700;">Kepala Sekolah,</div>
                <div class="dp-sign-name">{{ $setting->kepsek ?? 'Kepala Sekolah' }}</div>
                <div>NIP. {{ $setting->nip ?? '-' }}</div>
            </div>
        </div>

        <div class="dp-barcode-wrap">
            <svg class="barcode-svg dp-barcode-svg" data-barcode="{{ $barcodeVal }}"></svg>
            <span class="dp-barcode-text">{{ $barcodeVal }}</span>
        </div>
    </div>

    <!-- Footer wave with motto -->
    <div class="dp-back-footer-wave-wrap">
        <svg class="dp-back-footer-wave" viewBox="0 0 204 22" preserveAspectRatio="none">
            <path d="M0,8 C60,0 140,0 204,8 L204,22 L0,22 Z" fill="#09265e" />
            <path d="M0,7 C60,-1 140,-1 204,7" fill="none" stroke="#f59e0b" stroke-width="1.8" />
            <path d="M0,9 C60,1 140,1 204,9" fill="none" stroke="#38bdf8" stroke-width="1" />
        </svg>
        <div class="dp-footer-motto">Belajar, Berprestasi, Berakhlak Mulia</div>
    </div>
</div>
