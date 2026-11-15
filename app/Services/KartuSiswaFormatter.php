<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Format data siswa untuk kartu absen (preview + cetak).
 *
 * Keempat varian desain dan halaman cetak memakai data yang sama, jadi
 * perhitungannya ditarik ke satu tempat. Selain avoiding duplikasi, ini
 * juga menutup dua jebakan yang sebelumnya bikin halaman error:
 * - tanggal_lahir yang tidak bisa diparse (mis. "0000-00-00") dulu membuat
 *   seluruh halaman preview/cetak 500 karena Carbon::parse() melempar;
 * - kolom `nis` kosong (string kosong, bukan NULL) dulu tampil apa adanya
 *   sehingga label "NIS: " muncul tanpa nomor.
 */
class KartuSiswaFormatter
{
    /**
     * Tanggal lahir ("TATAUM, 5 Agustus 2010").
     *
     * @param  string  $format  Format ISO untuk Carbon, mis. 'D MMMM Y'.
     */
    public static function ttl(object $siswa, string $format = 'D MMMM Y'): string
    {
        $tempat = trim((string) ($siswa->tempat_lahir ?? ''));
        $tanggal = trim((string) ($siswa->tanggal_lahir ?? ''));

        $tgl = '';
        if ($tanggal !== '') {
            try {
                $tgl = Carbon::parse($tanggal)->locale('id')->isoFormat($format);
            } catch (\Throwable $e) {
                // Tanggal rusak tidak boleh menggagalkan seluruh halaman:
                // tampilkan apa adanya supaya admin bisa melihat & membetulkan.
                $tgl = $tanggal;
            }
        }

        $bagian = array_values(array_filter([$tempat, $tgl], static fn ($v) => $v !== ''));

        return $bagian === [] ? '-' : implode(', ', $bagian);
    }

    /**
     * Nilai untuk baris "NISN" pada kartu.
     *
     * NISN dipakai utama; NIS hanya sebagai cadangan bila NISN kosong.
     */
    public static function nomorSiswa(object $siswa): string
    {
        $nisn = trim((string) ($siswa->nisn ?? ''));
        if ($nisn !== '') {
            return $nisn;
        }

        $nis = trim((string) ($siswa->nis ?? ''));

        return $nis !== '' ? $nis : '-';
    }

    /**
     * Label + nomor identitas untuk daftar/dropdown siswa.
     *
     * @return array{0: string, 1: string} [label, nilai]
     */
    public static function identitas(object $siswa): array
    {
        $nis = trim((string) ($siswa->nis ?? ''));
        if ($nis !== '') {
            return ['NIS', $nis];
        }

        $nisn = trim((string) ($siswa->nisn ?? ''));
        if ($nisn !== '') {
            return ['NISN', $nisn];
        }

        return ['ID', '-'];
    }
}
