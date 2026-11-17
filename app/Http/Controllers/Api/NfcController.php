<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Services\NfcAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Controller untuk menangani request NFC dari aplikasi Android.
 *
 * Endpoint ini dipanggil oleh aplikasi Android ketika kartu NFC/RFID
 * di-tap ke perangkat. Server akan memvalidasi kartu, mencatat absensi,
 * dan mengembalikan hasil ke Android untuk ditampilkan ke user.
 */
class NfcController extends BaseController
{
    /**
     * Proses scan NFC untuk absensi masuk atau pulang.
     *
     * @return JsonResponse
     *
     * @bodyParam nfc_uid string required UID kartu NFC yang di-scan
     * @bodyParam jenis_scan string required 'masuk' atau 'pulang'
     * @bodyParam lat float optional Latitude GPS dari perangkat
     * @bodyParam lng float optional Longitude GPS dari perangkat
     * @bodyParam accuracy int optional Akurasi GPS dalam meter
     * @bodyParam device_id string optional ID unik perangkat Android
     * @bodyParam api_key string required API key untuk autentikasi
     *
     * @response {
     *   "success": true,
     *   "message": "Absen masuk berhasil (Tepat waktu)",
     *   "status": "SUCCESS",
     *   "nama": "John Doe",
     *   "tipe": "guru",
     *   "jam": "07:25:00",
     *   "telat_menit": 0,
     *   "denda": 0
     * }
     */
    public function scan(Request $request)
    {
        // Rate limiting: max 10 request per menit per device/IP
        $key = 'nfc_scan_'.md5(($request->input('device_id') ?: $request->ip()));

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak permintaan. Tunggu beberapa saat.',
                'status' => 'RATE_LIMITED',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        // Validasi input
        $nfcUid = trim((string) $request->input('nfc_uid'));
        $jenisScan = strtolower(trim((string) $request->input('jenis_scan')));
        $lat = $request->input('lat') ? (float) $request->input('lat') : null;
        $lng = $request->input('lng') ? (float) $request->input('lng') : null;
        $accuracy = $request->input('accuracy') ? (int) $request->input('accuracy') : null;
        $deviceId = trim((string) $request->input('device_id'));
        $apiKey = trim((string) $request->input('api_key'));

        // Validasi wajib
        if (empty($nfcUid)) {
            return response()->json([
                'success' => false,
                'message' => 'UID kartu NFC tidak valid',
                'status' => 'INVALID_UID',
            ], 400);
        }

        if (! in_array($jenisScan, ['masuk', 'pulang'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Jenis scan harus "masuk" atau "pulang"',
                'status' => 'INVALID_TYPE',
            ], 400);
        }

        // Validasi API key
        if (! $this->validateApiKey($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'API key tidak valid',
                'status' => 'UNAUTHORIZED',
            ], 401);
        }

        // Validasi GPS (opsional tapi direkomendasikan)
        if ($lat !== null && $lng !== null) {
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                return response()->json([
                    'success' => false,
                    'message' => 'Koordinat GPS tidak valid',
                    'status' => 'INVALID_GPS',
                ], 400);
            }
        }

        // Proses scan
        try {
            $service = new NfcAttendanceService;
            $result = $service->processScan($nfcUid, $jenisScan, [
                'lat' => $lat,
                'lng' => $lng,
                'accuracy' => $accuracy,
                'device_id' => $deviceId,
                'ip' => $request->ip(),
            ]);

            $statusCode = $result['success'] ? 200 : 400;

            return response()->json($result, $statusCode);

        } catch (\Throwable $e) {
            Log::error('[NfcController::scan] Error: '.$e->getMessage(), [
                'nfc_uid' => $nfcUid,
                'jenis_scan' => $jenisScan,
                'device_id' => $deviceId,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'status' => 'SYSTEM_ERROR',
            ], 500);
        }
    }

    /**
     * Cek status kartu NFC tanpa mencatat absensi.
     * Dipakai untuk validasi awal saat kartu baru di-tap.
     *
     * @return JsonResponse
     */
    public function checkCard(Request $request)
    {
        $nfcUid = trim((string) $request->input('nfc_uid'));
        $apiKey = trim((string) $request->input('api_key'));

        if (empty($nfcUid)) {
            return response()->json([
                'success' => false,
                'message' => 'UID kartu NFC tidak valid',
            ], 400);
        }

        if (! $this->validateApiKey($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'API key tidak valid',
            ], 401);
        }

        try {
            $service = new NfcAttendanceService;
            $user = $service->findUserByNfcUid($nfcUid);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kartu tidak terdaftar',
                    'registered' => false,
                ]);
            }

            return response()->json([
                'success' => true,
                'registered' => true,
                'nama' => $user['nama'],
                'tipe' => $user['tipe'],
            ]);

        } catch (\Throwable $e) {
            Log::error('[NfcController::checkCard] Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
            ], 500);
        }
    }

    /**
     * Daftar kartu NFC yang terdaftar (untuk sinkronisasi ke perangkat).
     *
     * @return JsonResponse
     */
    public function listCards(Request $request)
    {
        $apiKey = trim((string) $request->input('api_key'));

        if (! $this->validateApiKey($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'API key tidak valid',
            ], 401);
        }

        try {
            $cards = [];

            // Ambil kartu guru
            $guruCards = DB::table('master_guru')
                ->select('nomor_rfid', 'nama_guru as nama', 'id_guru')
                ->whereNotNull('nomor_rfid')
                ->where('nomor_rfid', '!=', '')
                ->get();

            foreach ($guruCards as $g) {
                $cards[] = [
                    'nfc_uid' => $g->nomor_rfid,
                    'nama' => $g->nama,
                    'tipe' => 'guru',
                ];
            }

            // Ambil kartu siswa
            $siswaCards = DB::table('master_siswa')
                ->select('nomor_rfid', 'nama', 'id_siswa')
                ->whereNotNull('nomor_rfid')
                ->where('nomor_rfid', '!=', '')
                ->get();

            foreach ($siswaCards as $s) {
                $cards[] = [
                    'nfc_uid' => $s->nomor_rfid,
                    'nama' => $s->nama,
                    'tipe' => 'siswa',
                ];
            }

            return response()->json([
                'success' => true,
                'count' => count($cards),
                'cards' => $cards,
            ]);

        } catch (\Throwable $e) {
            Log::error('[NfcController::listCards] Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
            ], 500);
        }
    }

    /**
     * Validasi API key.
     */
    private function validateApiKey(?string $apiKey): bool
    {
        if (empty($apiKey)) {
            return false;
        }

        // Cek di tabel setting
        $settingKey = DB::table('setting')->value('api_key');

        if (! empty($settingKey) && hash_equals($settingKey, $apiKey)) {
            return true;
        }

        // Cek di tabel absensi_setting
        $absenKey = DB::table('absensi_setting')->value('api_key');

        if (! empty($absenKey) && hash_equals($absenKey, $apiKey)) {
            return true;
        }

        // Cek di .env
        $envKey = env('NFC_API_KEY');

        if (! empty($envKey) && hash_equals($envKey, $apiKey)) {
            return true;
        }

        return false;
    }
}
