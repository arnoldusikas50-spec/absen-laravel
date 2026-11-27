{{--
    Kotak "tidak ada kartu" pada dokumen cetak kartu (layar saja, `no-print`).

    Dipakai oleh:
      - admin/presensi/kartu/cetak.blade.php       -> $varian = 'siswa'
      - admin/presensi/kartu/guru_cetak.blade.php  -> $varian = 'guru'

    Berada di folder `kartu/aksi` sehingga utility `layar:` di sini ikut
    dipindai resources/css/cetak-aksi.css tanpa daftar kelas manual.
--}}
@php
    $varian = $varian ?? 'siswa';
    $daftarKartu = $varian === 'guru' ? $guru_list : $siswa_list;
    $kelasTautan = 'layar:inline-flex layar:items-center layar:justify-center layar:gap-1.5 layar:rounded-lg layar:bg-blue-600 layar:px-3.5 layar:py-2 layar:text-[11.5px] layar:font-bold layar:text-white layar:no-underline layar:transition-all layar:hover:bg-blue-700';
@endphp
@if (empty($daftarKartu))
    <div class="no-print" style="max-width:640px;margin:24px auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:32px;text-align:center;">
        <p style="font-size:15px;font-weight:800;color:#0f172a;margin:0 0 6px 0;">{{ $varian === 'guru' ? 'Tidak ada kartu guru untuk dicetak' : 'Tidak ada kartu untuk dicetak' }}</p>
        <p style="font-size:12px;color:#64748b;margin:0 0 16px 0;">
            @if ($varian === 'guru')
                @if (! empty($catatan))
                    {{ $catatan }}
                @else
                    Guru yang dipilih tidak ditemukan pada master data guru. Periksa kembali filternya di halaman preview.
                @endif
            @else
                Pilih kelas / siswa lain pada halaman preview kartu, lalu buka kembali halaman cetak ini.
            @endif
        </p>
        <a href="{{ $previewUrl }}" class="{{ $kelasTautan }}"><i class="bi bi-arrow-left"></i> Kembali ke Preview</a>
    </div>
@endif
