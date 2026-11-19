<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sinkron poin disiplin + denda untuk SATU baris presensi dalam satu transaksi.
 *
 * Sebelumnya controller memanggil SiswaPoinService lalu SiswaDendaService
 * secara terpisah. Kalau yang kedua gagal, poin sudah tersimpan sementara
 * denda belum, dan tidak ada yang membatalkan keduanya — siswa bisa terlihat
 * berpoin tapi tidak tertagih (atau sebaliknya).
 *
 * Di sini keduanya berbagi satu transaksi: kegagalan nyata pada salah satu
 * sisi me-rollback keduanya. Kegagalan bersifat best-effort — pemanggil tetap
 * menerima laporan dan absensi yang sudah tersimpan tidak dibatalkan, sesuai
 * prinsip lama "kegagalan poin tidak boleh membatalkan absensi".
 */
class PresensiSiswaSyncService
{
    /**
     * Hitung ulang poin dan denda untuk satu baris presensi.
     *
     * @return array{status:bool, message:string, poin:array, denda:array}
     */
    public static function sinkron(int $presensiId): array
    {
        if ($presensiId <= 0) {
            return [
                'status' => false,
                'message' => 'ID presensi tidak valid',
                'poin' => [],
                'denda' => [],
            ];
        }

        try {
            DB::beginTransaction();
            // lempar=true: recalc yang gagal karena exception melempar ulang
            // setelah rollback savepoint-nya, sehingga transaksi di sini bisa
            // membatalkan sisi yang sudah terlanjur menulis.
            $poin = (new SiswaPoinService)->recalc($presensiId, true);
            $denda = (new SiswaDendaService)->recalc($presensiId, true);
            DB::commit();
        } catch (\Throwable $e) {
            try {
                DB::rollBack();
            } catch (\Throwable $e2) {
            }

            Log::error('[PresensiSiswaSyncService#'.$presensiId.'] poin+denda dibatalkan: '.$e->getMessage());

            return [
                'status' => false,
                'message' => 'Sinkron poin/denda gagal — perubahan dibatalkan',
                'poin' => [],
                'denda' => [],
            ];
        }

        $status = (bool) ($poin['status'] ?? false) && (bool) ($denda['status'] ?? false);
        if (! $status) {
            // Bukan exception: mis. tabel/kolom opsional belum dimigrasi.
            // Tidak ada yang perlu di-rollback, tapi jangan diam saja.
            Log::warning('[PresensiSiswaSyncService#'.$presensiId.'] poin: '
                .($poin['message'] ?? '-').' | denda: '.($denda['message'] ?? '-'));
        }

        return [
            'status' => $status,
            'message' => $status ? 'Poin dan denda tersinkron' : 'Poin/denda tersinkron sebagian',
            'poin' => $poin,
            'denda' => $denda,
        ];
    }
}
