@php
    /**
     * Partial kop sekolah untuk dokumen cetak.
     *
     * Variabel dari view pemanggil: $setting, $namaSekolah, $kopBase64,
     * $logoBase64. Opsi: $kopTinggi (max-height gambar kop, px),
     * $logoLebar (lebar logo, px), $logoKolomLebar (lebar kolom logo, %),
     * $kopMarginBawah (margin bawah gambar kop, px),
     * $kopKontak (array baris kontak, mis. ['Telp: 123']), $kopSpacerKanan
     * (tambah kolom kosong 10% di kanan agar judul tetap di tengah).
     */
    $kopTinggi = $kopTinggi ?? 100;
    $logoLebar = $logoLebar ?? 56;
    $logoKolomLebar = $logoKolomLebar ?? 10;
    $kopMarginBawah = $kopMarginBawah ?? 10;
    $kopKontak = $kopKontak ?? [];
    $kopSpacerKanan = $kopSpacerKanan ?? false;
@endphp
@if ($kopBase64)
<div style="text-align:center; margin-bottom:{{ $kopMarginBawah }}px;">
    <img src="{{ $kopBase64 }}" style="max-height:{{ $kopTinggi }}px; width:auto; max-width:100%;">
</div>
@else
<table width="100%" class="kop">
    <tr>
        <td width="{{ $logoKolomLebar }}%" align="center" valign="middle" style="border:none;">
            @if ($logoBase64)<img src="{{ $logoBase64 }}" style="width:{{ $logoLebar }}px;">@endif
        </td>
        <td align="center" valign="middle" style="border:none;">
            <div class="kop-title">{{ $namaSekolah }}</div>
            @if (!empty($setting->alamat))
            <div class="kop-sub">{{ $setting->alamat }}</div>
            @endif
            @if ($kopKontak)
            <div class="kop-sub">{{ implode(' | ', $kopKontak) }}</div>
            @endif
        </td>
        @if ($kopSpacerKanan)
        <td width="{{ $logoKolomLebar }}%" style="border:none;"></td>
        @endif
    </tr>
</table>
@endif
