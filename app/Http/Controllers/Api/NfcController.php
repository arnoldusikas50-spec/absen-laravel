<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Services\NfcAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

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
     * @bodyParam lat float required Latitude GPS dari perangkat
     * @bodyParam lng float required Longitude GPS dari perangkat
     * @bodyParam accuracy int optional Akurasi GPS dalam meter
     * @bodyParam device_id string optional ID unik perangkat Android
     * @bodyParam api_key string required API key (boleh dikirim via header X-API-KEY)
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
        // Rate limiting dua lapis: per-IP membatasi volume total, per-device
        // mencegah satu perangkat menyapu banyak kartu. device_id dikirim
        // klien, jadi tidak boleh menjadi satu-satunya kunci (mudah diacak).
        $ipKey = 'nfc_scan_ip_'.md5((string) $request->ip());
        if (RateLimiter::tooManyAttempts($ipKey, 120)) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak permintaan. Tunggu beberapa saat.',
                'status' => 'RATE_LIMITED',
            ], 429);
        }
        RateLimiter::hit($ipKey, 60);

        $deviceKey = 'nfc_scan_dev_'.md5(trim((string) $request->input('device_id')) ?: (string) $request->ip());
        if (RateLimiter::tooManyAttempts($deviceKey, 10)) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak permintaan dari perangkat ini. Tunggu beberapa saat.',
                'status' => 'RATE_LIMITED',
            ], 429);
        }
        RateLimiter::hit($deviceKey, 60);

        // Validasi input. 0 adalah nilai sah (lintang 0 / akurasi 0 = mock),
        // jadi jangan memakai falsy-check yang mengubahnya menjadi null.
        $nfcUid = trim((string) $request->input('nfc_uid'));
        $jenisScan = strtolower(trim((string) $request->input('jenis_scan')));
        $latRaw = $request->input('lat');
        $lngRaw = $request->input('lng');
        $accRaw = $request->input('accuracy');
        $lat = is_numeric($latRaw) ? (float) $latRaw : null;
        $lng = is_numeric($lngRaw) ? (float) $lngRaw : null;
        $accuracy = is_numeric($accRaw) ? (int) $accRaw : null;
        $deviceId = trim((string) $request->input('device_id'));
        $apiKey = trim((string) ($request->input('api_key') ?? $request->header('X-API-KEY') ?? ''));

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

        // GPS wajib: tanpa koordinat, geofence di NfcAttendanceService
        // dilewati dan kartu bisa dipakai dari mana saja.
        if ($lat === null || $lng === null) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi GPS wajib dikirim (lat dan lng).',
                'status' => 'GPS_REQUIRED',
            ], 422);
        }
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return response()->json([
                'success' => false,
                'message' => 'Koordinat GPS tidak valid',
                'status' => 'INVALID_GPS',
            ], 400);
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
        $apiKey = trim((string) ($request->input('api_key') ?? $request->header('X-API-KEY') ?? ''));

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
        $apiKey = trim((string) ($request->input('api_key') ?? $request->header('X-API-KEY') ?? ''));

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
     *
     * Sumber yang didukung: config/services.php (NFC_API_KEY), tabel setting
     * (legacy), dan api_key per guru di master_guru. Tabel absensi_setting
     * sengaja tidak dipakai karena tidak memiliki kolom api_key.
     */
    private function validateApiKey(?string $apiKey): bool
    {
        $apiKey = trim((string) $apiKey);
        if ($apiKey === '') {
            return false;
        }

        $configKey = config('services.nfc.api_key');
        if (is_string($configKey) && $configKey !== '' && hash_equals($configKey, $apiKey)) {
            return true;
        }

        try {
            if (Schema::hasTable('setting') && Schema::hasColumn('setting', 'api_key')) {
                foreach (DB::table('setting')->pluck('api_key') as $settingKey) {
                    if (is_string($settingKey) && $settingKey !== '' && hash_equals($settingKey, $apiKey)) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[NfcController] Gagal membaca api_key dari setting: '.$e->getMessage());
        }

        try {
            if (Schema::hasTable('master_guru') && Schema::hasColumn('master_guru', 'api_key')
                && DB::table('master_guru')->where('api_key', $apiKey)->exists()) {
                return true;
            }
        } catch (\Throwable $e) {
            Log::warning('[NfcController] Gagal membaca api_key dari master_guru: '.$e->getMessage());
        }

        return false;
    }
}
