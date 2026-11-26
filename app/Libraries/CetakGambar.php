<?php

namespace App\Libraries;

/**
 * CetakGambar — penyemat gambar publik ke dokumen cetak sebagai data URI.
 *
 * Dipakai view cetak/PDF agar logo, kop, dan tanda tangan tetap tampil tanpa
 * bergantung pada akses berkas eksternal saat dokumen dicetak.
 */
class CetakGambar
{
    /**
     * Ubah path relatif di public/ menjadi data URI base64.
     *
     * Mengembalikan string kosong bila path kosong atau berkas tidak ada.
     */
    public static function dataUri(?string $path): string
    {
        if (empty($path)) {
            return '';
        }

        $absolute = public_path().'/'.ltrim($path, '/');

        if (! is_file($absolute)) {
            return '';
        }

        $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        $mime = $extension === 'jpg' ? 'jpeg' : $extension;

        return 'data:image/'.$mime.';base64,'.base64_encode(file_get_contents($absolute));
    }
}
