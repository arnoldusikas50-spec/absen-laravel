@php
    /**
     * Partial tanda tangan dokumen cetak.
     *
     * Variabel dari view pemanggil: $setting, $tanggalCetak, $ttdBase64.
     * Opsi: $kiri (kolom kiri; null = hanya kolom kanan), $kanan (kolom
     * penanda tangan utama), $ttdTinggi dan $ttdLebar (ukuran gambar tanda
     * tangan, px).
     *
     * Setiap kolom berisi: tanggal (bool, tampilkan tempat/tanggal cetak),
     * peran (HTML, boleh berisi <br>), nama, nip (null = sembunyikan baris
     * NIP), dan ttd (bool, tampilkan gambar tanda tangan di kolom ini).
     */
    $kiri = $kiri ?? null;
    $kanan = $kanan ?? [];
    $ttdTinggi = $ttdTinggi ?? 58;
    $ttdLebar = $ttdLebar ?? 180;

    $kolomList = $kiri === null ? [$kanan] : [$kiri, $kanan];
    $lebar = $kiri === null ? '45%' : '50%';
@endphp
<table class="sign">
    <tr>
        @if ($kiri === null)
        <td width="55%"></td>
        @endif
        @foreach ($kolomList as $kolom)
        <td width="{{ $lebar }}" align="center">
            @if (!empty($kolom['tanggal']))
            {{ !empty($setting->tempat) ? $setting->tempat . ', ' : '' }}{{ $tanggalCetak }}<br>
            @endif
            {!! $kolom['peran'] ?? '' !!}
            <div class="space">
                @if (!empty($kolom['ttd']) && $ttdBase64)
                <img src="{{ $ttdBase64 }}" style="max-height:{{ $ttdTinggi }}px; max-width:{{ $ttdLebar }}px;">
                @endif
            </div>
            <span class="bold">{{ $kolom['nama'] ?? '' }}</span><br>
            @if (($kolom['nip'] ?? null) !== null)
            NIP. {{ $kolom['nip'] }}
            @endif
        </td>
        @endforeach
    </tr>
</table>
