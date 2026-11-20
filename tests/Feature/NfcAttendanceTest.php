<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\NfcController;
use App\Services\AttendancePolicy;
use App\Services\NfcAttendanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class NfcAttendanceTest extends TestCase
{
    protected function tearDown(): void
    {
        AttendancePolicy::reset();

        parent::tearDown();
    }

    public function test_config_api_key_is_accepted_and_invalid_key_is_rejected(): void
    {
        config()->set('services.nfc.api_key', 'nfc-secret');

        $method = new ReflectionMethod(NfcController::class, 'validateApiKey');
        $controller = new NfcController;

        $this->assertTrue($method->invoke($controller, 'nfc-secret'));
        $this->assertFalse($method->invoke($controller, 'salah'));
        $this->assertFalse($method->invoke($controller, ''));
    }

    public function test_api_key_falls_back_to_setting_and_master_guru_tables(): void
    {
        config()->set('services.nfc.api_key', null);

        Schema::create('setting', function ($table): void {
            $table->increments('id');
            $table->text('api_key')->nullable();
        });
        Schema::create('master_guru', function ($table): void {
            $table->increments('id_guru');
            $table->unsignedInteger('id_user')->nullable();
            $table->string('nama_guru');
            $table->string('nomor_rfid')->nullable();
            $table->string('api_key')->nullable();
        });

        DB::table('setting')->insert(['api_key' => 'legacy-secret']);
        DB::table('master_guru')->insert([
            'id_guru' => 1,
            'id_user' => 11,
            'nama_guru' => 'Guru Satu',
            'nomor_rfid' => '04A1B2C3',
            'api_key' => 'guru-secret',
        ]);

        $method = new ReflectionMethod(NfcController::class, 'validateApiKey');
        $controller = new NfcController;

        $this->assertTrue($method->invoke($controller, 'legacy-secret'));
        $this->assertTrue($method->invoke($controller, 'guru-secret'));
        $this->assertFalse($method->invoke($controller, 'tidak-ada'));
    }

    public function test_uid_normalization_finds_card_with_separators(): void
    {
        $this->createNfcSchema();

        DB::table('master_siswa')->insert([
            'id_siswa' => 5,
            'nama' => 'Siswa Lima',
            'nomor_rfid' => '04:A1:B2:C3',
            'kelas_awal' => 7,
        ]);

        $service = new NfcAttendanceService;
        $user = $service->findUserByNfcUid(' 04:a1-b2 c3 ');

        $this->assertNotNull($user);
        $this->assertSame('siswa', $user['tipe']);
        $this->assertSame(5, $user['reference_id']);
        $this->assertNull($user['user_id']);
    }

    public function test_scan_records_siswa_attendance_without_extra_time_suffix_and_logs_duplicate_as_failed(): void
    {
        $this->createNfcSchema();

        DB::table('master_siswa')->insert([
            'id_siswa' => 5,
            'nama' => 'Siswa Lima',
            'nomor_rfid' => '04:A1:B2:C3',
            'kelas_awal' => 7,
        ]);
        DB::table('absensi_jadwal_kerja')->insert([
            'hari' => (int) date('N'),
            'jam_masuk' => $this->validJamMasukForNow(),
            'jam_pulang' => '15:00:00',
            'is_libur' => 0,
        ]);
        DB::table('absensi_lokasi')->insert([
            'nama' => 'Sekolah',
            'lat' => -6.2,
            'lng' => 106.8,
            'radius_meter' => 500,
        ]);

        $options = [
            'lat' => -6.2,
            'lng' => 106.8,
            'accuracy' => 10,
            'device_id' => 'android-test',
            'ip' => '127.0.0.1',
        ];

        $service = new NfcAttendanceService;
        $first = $service->processScan('04:a1-b2 c3', 'masuk', $options);

        $this->assertSame('SUCCESS', $first['status']);
        $this->assertTrue($first['success']);

        $record = DB::table('presensi_siswa')->first();
        $this->assertNotNull($record);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $record->jam_masuk);
        $this->assertSame(7, (int) $record->id_kelas);
        $this->assertSame('nfc', $record->sumber_masuk);
        $this->assertSame('127.0.0.1', $record->ip_masuk);

        $second = $service->processScan('04A1B2C3', 'masuk', $options);

        $this->assertSame('ALREADY_CHECKED', $second['status']);
        $this->assertFalse($second['success']);
        $this->assertSame(2, DB::table('nfc_scan_log')->count());
        $this->assertSame(1, (int) DB::table('nfc_scan_log')->orderBy('id')->first()->berhasil);
        $this->assertSame(0, (int) DB::table('nfc_scan_log')->orderBy('id', 'desc')->first()->berhasil);
    }

    public function test_scan_requires_gps_coordinates(): void
    {
        $this->createNfcSchema();

        DB::table('master_siswa')->insert([
            'id_siswa' => 5,
            'nama' => 'Siswa Lima',
            'nomor_rfid' => '04A1B2C3',
            'kelas_awal' => 7,
        ]);
        DB::table('absensi_jadwal_kerja')->insert([
            'hari' => (int) date('N'),
            'jam_masuk' => $this->validJamMasukForNow(),
            'jam_pulang' => '15:00:00',
            'is_libur' => 0,
        ]);

        $result = (new NfcAttendanceService)->processScan('04A1B2C3', 'masuk');

        $this->assertSame('GPS_REQUIRED', $result['status']);
        $this->assertFalse($result['success']);
        $this->assertSame(0, DB::table('presensi_siswa')->count());
    }

    private function createNfcSchema(): void
    {
        AttendancePolicy::reset();

        Schema::create('absensi_setting', function ($table): void {
            $table->increments('id');
            $table->string('jam_masuk')->nullable();
            $table->string('jam_pulang')->nullable();
            $table->unsignedInteger('radius_meter')->nullable();
            $table->unsignedInteger('grace_period')->nullable();
            $table->unsignedInteger('point_telat')->nullable();
            $table->unsignedInteger('point_hadir')->nullable();
        });
        Schema::create('absensi_jadwal_kerja', function ($table): void {
            $table->increments('id');
            $table->unsignedTinyInteger('hari');
            $table->string('jam_masuk')->nullable();
            $table->string('jam_pulang')->nullable();
            $table->boolean('is_libur')->default(false);
        });
        Schema::create('absensi_lokasi', function ($table): void {
            $table->increments('id');
            $table->string('nama')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->unsignedInteger('radius_meter')->nullable();
        });
        Schema::create('master_guru', function ($table): void {
            $table->increments('id_guru');
            $table->unsignedInteger('id_user')->nullable();
            $table->string('nama_guru');
            $table->string('nomor_rfid')->nullable();
            $table->string('api_key')->nullable();
        });
        Schema::create('master_siswa', function ($table): void {
            $table->increments('id_siswa');
            $table->string('nama');
            $table->string('nomor_rfid')->nullable();
            $table->unsignedInteger('kelas_awal')->nullable();
        });
        Schema::create('nfc_scan_log', function ($table): void {
            $table->increments('id');
            $table->string('nfc_uid', 128);
            $table->string('tipe_user', 20)->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('reference_id')->nullable();
            $table->string('nama', 150)->nullable();
            $table->string('jenis_scan', 20);
            $table->date('tanggal');
            $table->string('jam', 8);
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->unsignedInteger('akurasi_gps')->nullable();
            $table->string('device_id', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('berhasil')->default(false);
            $table->string('pesan', 255)->nullable();
            $table->string('presensi_id', 50)->nullable();
            $table->timestamps();
        });
        Schema::create('presensi_siswa', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('id_kelas');
            $table->unsignedInteger('id_siswa');
            $table->date('tgl');
            $table->string('jam', 8)->nullable();
            $table->string('jam_masuk', 8)->nullable();
            $table->string('jam_keluar', 8)->nullable();
            $table->decimal('lat_masuk', 10, 8)->nullable();
            $table->decimal('lng_masuk', 11, 8)->nullable();
            $table->unsignedInteger('akurasi_masuk')->nullable();
            $table->unsignedInteger('lokasi_id')->nullable();
            $table->decimal('lat_keluar', 10, 8)->nullable();
            $table->decimal('lng_keluar', 11, 8)->nullable();
            $table->string('sumber_masuk', 30)->nullable();
            $table->string('sumber_keluar', 30)->nullable();
            $table->string('ip_masuk', 45)->nullable();
            $table->string('ip_keluar', 45)->nullable();
            $table->boolean('pulang_tidak_tercatat')->default(false);
            $table->string('status', 30)->nullable();
            $table->timestamps();

            $table->unique(['id_siswa', 'tgl'], 'uq_ps_siswa_tgl');
        });
    }

    private function validJamMasukForNow(): string
    {
        $now = date('H:i:s');

        for ($detik = 0; $detik < 86400; $detik += 60) {
            $jamMasuk = date('H:i:s', $detik);
            $buka = date('H:i:s', $detik - 3600);
            $tutup = date('H:i:s', $detik + 14400);

            if ($now >= $buka && $now <= $tutup) {
                return $jamMasuk;
            }
        }

        return '12:00:00';
    }
}
