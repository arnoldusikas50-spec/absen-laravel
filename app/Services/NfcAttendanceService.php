<?php

namespace App\Services;

use App\Models\AttendanceSettingModel;
use App\Models\NfcScanLogModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Service untuk menangani absensi via NFC/RFID.
 * Dipanggil dari NfcController untuk memproses tap kartu dari perangkat Android.
 */
class NfcAttendanceService
{
    /**
     * Proses scan NFC untuk absensi masuk atau pulang.
     *
     * @param  string  $nfcUid  UID kartu NFC yang di-scan
     * @param  string  $jenisScan  'masuk' atau 'pulang'
     * @param  array  $options  Opsi tambahan (lat, lng, accuracy, device_id, ip)
     * @return array Hasil proses dengan status, pesan, dan data
     */
    public function processScan(string $nfcUid, string $jenisScan, array $options = []): array
    {
        $tanggal = date('Y-m-d');
        $jam = date('H:i:s');
        $lat = $options['lat'] ?? null;
        $lng = $options['lng'] ?? null;
        $accuracy = $options['accuracy'] ?? null;
        $deviceId = $options['device_id'] ?? null;
        $ip = $options['ip'] ?? null;

        // 1. Cari user berdasarkan NFC UID
        $user = $this->findUserByNfcUid($nfcUid);
        if (! $user) {
            $this->logScan($nfcUid, null, null, null, $jenisScan, $tanggal, $jam, $lat, $lng, $accuracy, $deviceId, $ip, false, 'Kartu tidak terdaftar');

            return [
                'success' => false,
                'message' => 'Kartu NFC tidak terdaftar dalam sistem',
                'status' => 'NOT_REGISTERED',
            ];
        }

        $tipeUser = $user['tipe']; // 'guru' atau 'siswa'
        $userId = $user['user_id'];
        $referenceId = $user['reference_id']; // id_guru atau id_siswa
        $nama = $user['nama'];

        // 2. Validasi duplikasi scan (sama seperti QR)
        if ($this->isDuplicateScan($nfcUid, $tanggal, $jenisScan)) {
            // Cek apakah sudah ada absensi hari ini untuk jenis ini
            $existingMsg = $this->getExistingAttendanceMessage($tipeUser, $referenceId, $tanggal, $jenisScan);
            $this->logScan($nfcUid, $tipeUser, $userId, $referenceId, $jenisScan, $tanggal, $jam, $lat, $lng, $accuracy, $deviceId, $ip, false, 'Sudah absen', null, $nama);

            return [
                'success' => false,
                'message' => $existingMsg ?: 'Sudah melakukan absensi '.$jenisScan.' hari ini',
                'status' => 'ALREADY_CHECKED',
                'nama' => $nama,
                'tipe' => $tipeUser,
            ];
        }

        // 3. Validasi waktu absensi
        $setting = $this->getAttendanceSettings();
        $timeValidation = $this->validateTime($jenisScan, $setting, $tanggal);
        if (! $timeValidation['valid']) {
            $this->logScan($nfcUid, $tipeUser, $userId, $referenceId, $jenisScan, $tanggal, $jam, $lat, $lng, $accuracy, $deviceId, $ip, false, $timeValidation['message'], null, $nama);

            return [
                'success' => false,
                'message' => $timeValidation['message'],
                'status' => 'TIME_INVALID',
                'nama' => $nama,
                'tipe' => $tipeUser,
            ];
        }

        // 4. Validasi hari libur
        $liburCheck = $this->isHoliday($tanggal);
        if ($liburCheck) {
            $this->logScan($nfcUid, $tipeUser, $userId, $referenceId, $jenisScan, $tanggal, $jam, $lat, $lng, $accuracy, $deviceId, $ip, false, $liburCheck, null, $nama);

            return [
                'success' => false,
                'message' => $liburCheck,
                'status' => 'HOLIDAY',
                'nama' => $nama,
                'tipe' => $tipeUser,
            ];
        }

        // 5. Validasi geofence (jika GPS tersedia dan tabel lokasi dikonfigurasi)
        if ($lat !== null && $lng !== null) {
            $geoCheck = $this->validateGeofence($lat, $lng, $accuracy, $setting);
            if (! $geoCheck['ok']) {
                $this->logScan($nfcUid, $tipeUser, $userId, $referenceId, $jenisScan, $tanggal, $jam, $lat, $lng, $accuracy, $deviceId, $ip, false, $geoCheck['message'], null, $nama);

                return [
                    'success' => false,
                    'message' => $geoCheck['message'],
                    'status' => 'OUT_OF_RANGE',
                    'nama' => $nama,
                    'tipe' => $tipeUser,
                ];
            }
        }

        // 6. Proses absensi berdasarkan tipe user
        try {
            if ($tipeUser === 'guru') {
                $result = $this->processGuruAttendance($referenceId, $userId, $jenisScan, $tanggal, $jam, $lat, $lng, $accuracy, $setting, $deviceId, $ip);
            } else {
                $result = $this->processSiswaAttendance($referenceId, $jenisScan, $tanggal, $jam, $lat, $lng, $setting);
            }
        } catch (\Throwable $e) {
            Log::error('[NfcAttendanceService] Error: '.$e->getMessage());
            $this->logScan($nfcUid, $tipeUser, $userId, $referenceId, $jenisScan, $tanggal, $jam, $lat, $lng, $accuracy, $deviceId, $ip, false, 'Error sistem: '.$e->getMessage(), null, $nama);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'status' => 'SYSTEM_ERROR',
                'nama' => $nama,
                'tipe' => $tipeUser,
            ];
        }

        // 7. Catat log sukses
        $this->logScan(
            $nfcUid, $tipeUser, $userId, $referenceId, $jenisScan, $tanggal, $jam,
            $lat, $lng, $accuracy, $deviceId, $ip, true,
            $result['message'] ?? 'Berhasil',
            $result['presensi_id'] ?? null,
            $nama
        );

        // 8. Kirim notifikasi (jika telat)
        if (! empty($result['telat_menit']) && $result['telat_menit'] > 0) {
            try {
                (new NotificationService)->notifyLateCheckin($nama, $jam, $result['telat_menit'], $userId);
            } catch (\Throwable $e) {
                Log::error('[NfcAttendanceService] Notifikasi gagal: '.$e->getMessage());
            }
        }

        return [
            'success' => true,
            'message' => $result['message'],
            'status' => $result['status'] ?? 'SUCCESS',
            'nama' => $nama,
            'tipe' => $tipeUser,
            'jam' => $jam,
            'telat_menit' => $result['telat_menit'] ?? 0,
            'denda' => $result['denda'] ?? 0,
        ];
    }

    /**
     * Normalisasi UID RFID: uppercase, hapus separator (:, -, spasi).
     */
    private function normalizeUid(string $uid): string
    {
        return strtoupper(str_replace([':', '-', ' '], '', trim($uid)));
    }

    /**
     * Cari user (guru/siswa) berdasarkan NFC UID.
     * Match case-insensitive dengan normalisasi separator yang konsisten
     * dengan assignRfid() dan findSiswaByRfid()/findGuruByRfid().
     */
    public function findUserByNfcUid(string $nfcUid): ?array
    {
        $db = DB::connection();
        $nfcUid = trim($nfcUid);

        if (empty($nfcUid)) {
            return null;
        }

        $cleanUid = strtolower(str_replace([':', '-', ' '], '', $nfcUid));

        // Cari di master_guru
        if (Schema::hasTable('master_guru') && Schema::hasColumn('master_guru', 'nomor_rfid')) {
            $guru = $db->table('master_guru')
                ->where('nomor_rfid', $nfcUid)
                ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(nomor_rfid, ':', ''), '-', ''), ' ', '')) = ?", [$cleanUid])
                ->first();

            if ($guru) {
                $userId = $db->table('users')->where('id', $guru->id_user)->value('id');

                return [
                    'tipe' => 'guru',
                    'user_id' => $userId,
                    'reference_id' => $guru->id_guru,
                    'nama' => $guru->nama_guru,
                ];
            }
        }

        // Cari di master_siswa
        if (Schema::hasTable('master_siswa') && Schema::hasColumn('master_siswa', 'nomor_rfid')) {
            $siswa = $db->table('master_siswa')
                ->where('nomor_rfid', $nfcUid)
                ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(nomor_rfid, ':', ''), '-', ''), ' ', '')) = ?", [$cleanUid])
                ->first();

            if ($siswa) {
                $userId = $db->table('users')->where('id', $siswa->id_user)->value('id');

                return [
                    'tipe' => 'siswa',
                    'user_id' => $userId,
                    'reference_id' => $siswa->id_siswa,
                    'nama' => $siswa->nama,
                ];
            }
        }

        return null;
    }

    /**
     * Cek apakah sudah scan dengan UID yang sama pada hari dan jenis yang sama.
     */
    private function isDuplicateScan(string $nfcUid, string $tanggal, string $jenisScan): bool
    {
        return NfcScanLogModel::where('nfc_uid', $nfcUid)
            ->where('tanggal', $tanggal)
            ->where('jenis_scan', $jenisScan)
            ->where('berhasil', true)
            ->exists();
    }

    /**
     * Ambil pesan absensi yang sudah ada.
     */
    private function getExistingAttendanceMessage(string $tipeUser, int $referenceId, string $tanggal, string $jenisScan): ?string
    {
        $db = DB::connection();

        if ($tipeUser === 'guru') {
            $record = $db->table('absensi_harian')
                ->where('id_guru', $referenceId)
                ->where('tgl', $tanggal)
                ->first();

            if ($record) {
                if ($jenisScan === 'masuk' && $record->jam_masuk) {
                    return 'Sudah absen masuk pukul '.substr($record->jam_masuk, 11, 5);
                }
                if ($jenisScan === 'pulang' && $record->jam_pulang) {
                    return 'Sudah absen pulang pukul '.substr($record->jam_pulang, 11, 5);
                }
            }
        } else {
            $record = $db->table('presensi_siswa')
                ->where('id_siswa', $referenceId)
                ->where('tgl', $tanggal)
                ->first();

            if ($record) {
                if ($jenisScan === 'masuk' && $record->jam_masuk) {
                    return 'Sudah absen masuk pukul '.substr($record->jam_masuk, 0, 5);
                }
                if ($jenisScan === 'pulang' && $record->jam_keluar) {
                    return 'Sudah absen pulang pukul '.substr($record->jam_keluar, 0, 5);
                }
            }
        }

        return null;
    }

    /**
     * Validasi waktu absensi.
     */
    private function validateTime(string $jenisScan, object $setting, string $tanggal): array
    {
        $now = date('H:i:s');
        $dayOfWeek = (int) date('N', strtotime($tanggal));

        // Ambil jadwal kerja
        $jadwal = null;
        if (Schema::hasTable('absensi_jadwal_kerja')) {
            $jadwal = DB::table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->first();
        }

        $jamMasuk = $jadwal->jam_masuk ?? ($setting->jam_masuk ?? '07:30:00');
        $jamPulang = $jadwal->jam_pulang ?? ($setting->jam_pulang ?? '15:00:00');

        // Cek apakah hari libur mingguan
        if ($jadwal && ! empty($jadwal->is_libur)) {
            return ['valid' => false, 'message' => 'Hari ini adalah hari libur'];
        }

        if ($jenisScan === 'masuk') {
            // Check-in: bisa 1 jam sebelum jam masuk
            $buka = date('H:i:s', strtotime($jamMasuk) - 3600);
            $tutup = date('H:i:s', strtotime($jamMasuk) + 14400); // 4 jam setelah jam masuk

            if ($now < $buka) {
                return ['valid' => false, 'message' => 'Absen masuk dibuka pukul '.substr($buka, 0, 5)];
            }
            if ($now > $tutup) {
                return ['valid' => false, 'message' => 'Waktu absen masuk sudah lewat (tutup pukul '.substr($tutup, 0, 5).')'];
            }
        } else {
            // Check-out: hanya setelah jam pulang
            if ($now < $jamPulang) {
                return ['valid' => false, 'message' => 'Belum waktunya pulang. Jam pulang: '.substr($jamPulang, 0, 5)];
            }
        }

        return ['valid' => true];
    }

    /**
     * Cek apakah hari libur.
     */
    private function isHoliday(string $tanggal): ?string
    {
        $dayOfWeek = (int) date('N', strtotime($tanggal));

        // Cek jadwal kerja
        if (Schema::hasTable('absensi_jadwal_kerja')) {
            $jadwal = DB::table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->first();
            if ($jadwal && ! empty($jadwal->is_libur)) {
                return 'Hari ini adalah hari libur mingguan';
            }
        }

        // Cek tabel absensi_hari_libur (libur nasional / khusus)
        if (Schema::hasTable('absensi_hari_libur')) {
            $libur = DB::table('absensi_hari_libur')
                ->where('tanggal', $tanggal)
                ->first();

            if ($libur) {
                return 'Hari libur: '.($libur->keterangan ?? 'Libur nasional');
            }
        }

        // Cek tabel kalender_akademik (tipe = LIBUR, tanggal dalam rentang tgl_mulai..tgl_selesai)
        if (Schema::hasTable('kalender_akademik')) {
            $kalender = DB::table('kalender_akademik')
                ->where('tipe', 'LIBUR')
                ->where('tgl_mulai', '<=', $tanggal)
                ->where(function ($q) use ($tanggal) {
                    $q->whereNull('tgl_selesai')->orWhere('tgl_selesai', '>=', $tanggal);
                })
                ->first();

            if ($kalender) {
                return 'Hari libur: '.($kalender->judul ?? 'Libur kalender akademik');
            }
        }

        return null;
    }

    /**
     * Proses absensi guru.
     */
    private function processGuruAttendance(int $idGuru, int $userId, string $jenisScan, string $tanggal, string $jam, ?float $lat, ?float $lng, ?int $accuracy, object $setting, ?string $deviceId = null, ?string $ip = null): array
    {
        $db = DB::connection();

        if ($jenisScan === 'masuk') {
            // Cek apakah sudah ada record hari ini
            $existing = $db->table('absensi_harian')
                ->where('user_id', $userId)
                ->where('tgl', $tanggal)
                ->first();

            if ($existing && $existing->jam_masuk) {
                return [
                    'status' => 'ALREADY_CHECKED',
                    'message' => 'Sudah absen masuk pukul '.substr($existing->jam_masuk, 11, 5),
                ];
            }

            // Hitung keterlambatan
            $dayOfWeek = (int) date('N', strtotime($tanggal));
            $jadwal = null;
            if (Schema::hasTable('absensi_jadwal_kerja')) {
                $jadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->first();
            }
            $jamMasukEfektif = $jadwal->jam_masuk ?? ($setting->jam_masuk ?? '07:30:00');

            $telatMenit = AttendancePolicy::telat($jam, $jamMasukEfektif, (int) ($setting->grace_period ?? 0));
            $statusMasuk = $telatMenit > 0 ? 'LATE' : 'ONTIME';

            // Hitung denda
            $denda = 0;
            $keteranganDenda = null;
            if ($telatMenit > 0) {
                $penalty = (new AttendancePenaltyService)->calculatePenalty($telatMenit, $setting, 'Telat');
                $denda = $penalty['denda'] ?? 0;
                $keteranganDenda = $penalty['keterangan'] ?? null;
            }

            // Hitung poin
            $poin = $telatMenit > 0
                ? (int) ($setting->point_telat ?? 1)
                : (int) ($setting->point_hadir ?? 1);

            // Validasi lokasi
            $lokasiId = null;
            $jarak = null;
            if ($lat && $lng) {
                $lokasi = $this->findNearestLocation($lat, $lng, $setting);
                if ($lokasi) {
                    $lokasiId = $lokasi->id;
                    $jarak = $lokasi->jarak;
                }
            }

            $data = [
                'user_id' => $userId,
                'id_guru' => $idGuru,
                'tgl' => $tanggal,
                'jam_masuk' => $tanggal.' '.$jam.':00',
                'lat_masuk' => $lat,
                'lng_masuk' => $lng,
                'lokasi_masuk_id' => $lokasiId,
                'jarak_masuk' => $jarak,
                'status_masuk' => $statusMasuk,
                'telat_menit' => $telatMenit,
                'points' => $poin,
                'denda' => $denda,
                'keterangan_denda' => $keteranganDenda,
                'gps_accuracy' => $accuracy,
                'device_info' => $deviceId ? substr('NFC: '.$deviceId, 0, 255) : 'NFC Android',
                'ip_address' => $ip,
                'updated_at' => now(),
            ];

            if ($existing) {
                $db->table('absensi_harian')->where('id', $existing->id)->update($data);
                $presensiId = $existing->id;
            } else {
                $data['created_at'] = now();
                $db->table('absensi_harian')->insert($data);
                $presensiId = DB::getPdo()->lastInsertId();
            }

            // Sync poin history
            $this->syncPoinHistory($userId, $telatMenit > 0 ? 'TELAT' : 'HADIR', $poin, "Absen NFC - $statusMasuk");

            return [
                'status' => 'SUCCESS',
                'message' => $telatMenit > 0
                    ? "Absen masuk berhasil (Terlambat {$telatMenit} menit)"
                    : 'Absen masuk berhasil (Tepat waktu)',
                'telat_menit' => $telatMenit,
                'denda' => $denda,
                'presensi_id' => (string) $presensiId,
            ];
        } else {
            // Check-out
            $existing = $db->table('absensi_harian')
                ->where('user_id', $userId)
                ->where('tgl', $tanggal)
                ->first();

            if (! $existing || ! $existing->jam_masuk) {
                return [
                    'status' => 'NO_CHECKIN',
                    'message' => 'Belum ada absen masuk hari ini',
                ];
            }

            if ($existing->jam_pulang) {
                return [
                    'status' => 'ALREADY_CHECKED',
                    'message' => 'Sudah absen pulang pukul '.substr($existing->jam_pulang, 11, 5),
                ];
            }

            // Hitung pulang cepat
            $dayOfWeek = (int) date('N', strtotime($tanggal));
            $jadwal = null;
            if (Schema::hasTable('absensi_jadwal_kerja')) {
                $jadwal = $db->table('absensi_jadwal_kerja')->where('hari', $dayOfWeek)->first();
            }
            $jamPulangEfektif = $jadwal->jam_pulang ?? ($setting->jam_pulang ?? '15:00:00');

            $pulangCepat = AttendancePolicy::pulangCepat($jam, $jamPulangEfektif);
            $statusPulang = $pulangCepat > 0 ? 'EARLY' : 'ONTIME';

            // Validasi lokasi
            $lokasiId = null;
            $jarak = null;
            if ($lat && $lng) {
                $lokasi = $this->findNearestLocation($lat, $lng, $setting);
                if ($lokasi) {
                    $lokasiId = $lokasi->id;
                    $jarak = $lokasi->jarak;
                }
            }

            $data = [
                'jam_pulang' => $tanggal.' '.$jam.':00',
                'lat_pulang' => $lat,
                'lng_pulang' => $lng,
                'lokasi_pulang_id' => $lokasiId,
                'jarak_pulang' => $jarak,
                'status_pulang' => $statusPulang,
                'pulang_cepat_menit' => $pulangCepat,
                'device_info' => $deviceId ? substr('NFC: '.$deviceId, 0, 255) : 'NFC Android',
                'ip_address' => $ip,
                'updated_at' => now(),
            ];

            $db->table('absensi_harian')->where('id', $existing->id)->update($data);

            return [
                'status' => 'SUCCESS',
                'message' => $pulangCepat > 0
                    ? "Absen pulang berhasil (Pulang cepat {$pulangCepat} menit)"
                    : 'Absen pulang berhasil',
                'presensi_id' => (string) $existing->id,
            ];
        }
    }

    /**
     * Proses absensi siswa.
     */
    private function processSiswaAttendance(int $idSiswa, string $jenisScan, string $tanggal, string $jam, ?float $lat, ?float $lng, object $setting): array
    {
        $db = DB::connection();

        // Ambil info siswa
        $siswa = $db->table('master_siswa')->where('id_siswa', $idSiswa)->first();
        if (! $siswa) {
            return ['status' => 'ERROR', 'message' => 'Data siswa tidak ditemukan'];
        }

        // Cari kelas aktif
        $tp = $db->table('master_tp')->where('active', 1)->first();
        $smt = $db->table('master_smt')->where('active', 1)->first();
        $kelasSiswa = $db->table('kelas_siswa')
            ->where('id_siswa', $idSiswa)
            ->when($tp, fn ($q) => $q->where('id_tp', $tp->id_tp))
            ->when($smt, fn ($q) => $q->where('id_smt', $smt->id_smt))
            ->first();

        $idKelas = $kelasSiswa->id_kelas ?? null;

        if ($jenisScan === 'masuk') {
            // Cek record yang sudah ada
            $existing = $db->table('presensi_siswa')
                ->where('id_siswa', $idSiswa)
                ->where('tgl', $tanggal)
                ->first();

            if ($existing && $existing->jam_masuk) {
                return [
                    'status' => 'ALREADY_CHECKED',
                    'message' => 'Sudah absen masuk pukul '.substr($existing->jam_masuk, 0, 5),
                ];
            }

            $data = [
                'id_kelas' => $idKelas,
                'id_siswa' => $idSiswa,
                'tgl' => $tanggal,
                'jam_masuk' => $jam.':00',
                'status' => 'HADIR',
                'sumber_masuk' => 'nfc',
                'lat_masuk' => $lat,
                'lng_masuk' => $lng,
                'updated_at' => now(),
            ];

            if ($existing) {
                $db->table('presensi_siswa')->where('id', $existing->id)->update($data);
                $presensiId = $existing->id;
            } else {
                $data['created_at'] = now();
                $db->table('presensi_siswa')->insert($data);
                $presensiId = DB::getPdo()->lastInsertId();
            }

            return [
                'status' => 'SUCCESS',
                'message' => 'Absen masuk berhasil',
                'presensi_id' => (string) $presensiId,
            ];
        } else {
            // Check-out
            $existing = $db->table('presensi_siswa')
                ->where('id_siswa', $idSiswa)
                ->where('tgl', $tanggal)
                ->first();

            if (! $existing || ! $existing->jam_masuk) {
                return [
                    'status' => 'NO_CHECKIN',
                    'message' => 'Belum ada absen masuk hari ini',
                ];
            }

            if ($existing->jam_keluar) {
                return [
                    'status' => 'ALREADY_CHECKED',
                    'message' => 'Sudah absen pulang pukul '.substr($existing->jam_keluar, 0, 5),
                ];
            }

            $db->table('presensi_siswa')->where('id', $existing->id)->update([
                'jam_keluar' => $jam.':00',
                'sumber_keluar' => 'nfc',
                'lat_keluar' => $lat,
                'lng_keluar' => $lng,
                'updated_at' => now(),
            ]);

            return [
                'status' => 'SUCCESS',
                'message' => 'Absen pulang berhasil',
                'presensi_id' => (string) $existing->id,
            ];
        }
    }

    /**
     * Validasi geofence: tolak scan jika di luar radius semua lokasi sekolah.
     * Mengembalikan ['ok' => bool, 'message' => string].
     */
    private function validateGeofence(float $lat, float $lng, ?int $accuracy, object $setting): array
    {
        // Koordinat tidak valid
        if (($lat === 0.0 && $lng === 0.0) || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return ['ok' => false, 'message' => 'Koordinat GPS tidak valid'];
        }

        // Akurasi 0 = pasti mock location
        if ($accuracy !== null && $accuracy === 0) {
            return ['ok' => false, 'message' => 'Terdeteksi lokasi palsu (mock location)'];
        }

        if ($accuracy !== null && $accuracy > 1000) {
            return ['ok' => false, 'message' => 'Akurasi GPS terlalu rendah (±'.$accuracy.'m)'];
        }

        if (! Schema::hasTable('absensi_lokasi')) {
            // Jika tidak ada tabel lokasi, lewati geofence (opsional)
            return ['ok' => true];
        }

        $locations = DB::table('absensi_lokasi')->get();
        if ($locations->isEmpty()) {
            return ['ok' => false, 'message' => 'Titik lokasi sekolah belum dikonfigurasi'];
        }

        $defaultRadius = (int) ($setting->radius_meter ?? 100);
        if ($defaultRadius <= 0) {
            $defaultRadius = 100;
        }

        foreach ($locations as $loc) {
            if ($loc->latitude && $loc->longitude) {
                $radius = (int) ($loc->radius_meter ?? $defaultRadius);
                if ($radius <= 0) {
                    $radius = $defaultRadius;
                }
                $distance = $this->haversineDistance($lat, $lng, (float) $loc->latitude, (float) $loc->longitude);
                if ($distance <= $radius) {
                    return ['ok' => true];
                }
            }
        }

        return ['ok' => false, 'message' => 'Di luar area sekolah. Absensi hanya bisa dilakukan di lokasi yang sudah ditentukan.'];
    }

    /**
     * Cari lokasi terdekat.
     */
    private function findNearestLocation(float $lat, float $lng, object $setting): ?object
    {
        $db = DB::connection();

        if (! Schema::hasTable('absensi_lokasi')) {
            return null;
        }

        $locations = $db->table('absensi_lokasi')->get();
        $radius = (int) ($setting->radius_meter ?? 100);
        $nearest = null;
        $minDistance = PHP_INT_MAX;

        foreach ($locations as $loc) {
            if ($loc->latitude && $loc->longitude) {
                $distance = $this->haversineDistance($lat, $lng, $loc->latitude, $loc->longitude);
                if ($distance <= $radius && $distance < $minDistance) {
                    $minDistance = $distance;
                    $nearest = $loc;
                    $nearest->jarak = (int) $distance;
                }
            }
        }

        return $nearest;
    }

    /**
     * Hitung jarak haversine dalam meter.
     */
    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Ambil pengaturan absensi.
     */
    private function getAttendanceSettings(): object
    {
        $model = new AttendanceSettingModel;
        $setting = $model->first();

        if (! $setting) {
            return (object) [
                'jam_masuk' => '07:30:00',
                'jam_pulang' => '15:00:00',
                'radius_meter' => 100,
                'grace_period' => 0,
                'point_telat' => 1,
                'point_hadir' => 1,
            ];
        }

        return $setting;
    }

    /**
     * Catat ke poin history.
     */
    private function syncPoinHistory(int $userId, string $tipe, int $poin, string $keterangan): void
    {
        try {
            if (! Schema::hasTable('poin_history')) {
                return;
            }

            DB::table('poin_history')->insert([
                'user_id' => $userId,
                'poin' => $poin,
                'tipe' => $tipe,
                'keterangan' => $keterangan,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[NfcAttendanceService] syncPoinHistory error: '.$e->getMessage());
        }
    }

    /**
     * Catat log scan.
     */
    private function logScan(
        string $nfcUid,
        ?string $tipeUser,
        ?int $userId,
        ?int $referenceId,
        string $jenisScan,
        string $tanggal,
        string $jam,
        ?float $lat,
        ?float $lng,
        ?int $accuracy,
        ?string $deviceId,
        ?string $ip,
        bool $berhasil,
        string $pesan,
        ?string $presensiId = null,
        ?string $nama = null
    ): void {
        try {
            NfcScanLogModel::create([
                'nfc_uid' => $nfcUid,
                'tipe_user' => $tipeUser,
                'user_id' => $userId,
                'reference_id' => $referenceId,
                'nama' => $nama,
                'jenis_scan' => $jenisScan,
                'tanggal' => $tanggal,
                'jam' => $jam,
                'lat' => $lat,
                'lng' => $lng,
                'akurasi_gps' => $accuracy,
                'device_id' => $deviceId,
                'ip_address' => $ip,
                'berhasil' => $berhasil,
                'pesan' => $pesan,
                'presensi_id' => $presensiId,
            ]);
        } catch (\Throwable $e) {
            Log::error('[NfcAttendanceService] logScan error: '.$e->getMessage());
        }
    }
}
