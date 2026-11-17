<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatasiswaRfidTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'password123';

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('groups')->insert([
            ['id' => 1, 'name' => 'admin', 'description' => 'Administrator'],
            ['id' => 2, 'name' => 'guru', 'description' => 'Guru'],
            ['id' => 3, 'name' => 'siswa', 'description' => 'Siswa'],
        ]);

        DB::table('master_tp')->insert([
            'id_tp' => 1,
            'tahun' => '2026/2027',
            'active' => 1,
        ]);

        DB::table('master_smt')->insert([
            'id_smt' => 1,
            'smt' => '1',
            'nama_smt' => 'Ganjil',
            'active' => 1,
        ]);
    }

    private function loginAsAdmin(): User
    {
        $userId = DB::table('users')->insertGetId([
            'ip_address' => '127.0.0.1',
            'username' => 'admin_test',
            'password' => Hash::make(self::PASSWORD),
            'created_on' => time(),
            'active' => 1,
            'first_name' => 'Admin',
        ]);

        DB::table('users_groups')->insert([
            'user_id' => $userId,
            'group_id' => 1,
        ]);

        $user = User::findOrFail($userId);

        $this->actingAs($user)->withSession([
            'is_login' => true,
            'user_id' => (int) $user->id,
            'role' => '1',
            'role_name' => 'admin',
        ]);

        return $user;
    }

    private function createSiswa(int $idSiswa, string $nama, ?string $rfid = null): void
    {
        DB::table('master_siswa')->insert([
            'id_siswa' => $idSiswa,
            'nis' => '100'.$idSiswa,
            'nisn' => '00100'.$idSiswa,
            'nama' => $nama,
            'jenis_kelamin' => 'L',
            'username' => 'siswa'.$idSiswa,
            'nik' => '320100000000000'.$idSiswa,
            'warga_negara' => 'WNI',
            'uid' => 'uid-siswa-'.$idSiswa,
            'nomor_rfid' => $rfid,
        ]);
    }

    public function test_assign_rfid_requires_admin_authentication(): void
    {
        $response = $this->postJson('/datasiswa/assign_rfid', [
            'id_siswa' => 1,
            'nomor_rfid' => '04A1B2C3',
        ]);

        $this->assertTrue(in_array($response->status(), [401, 403], true));
    }

    public function test_assign_rfid_successfully_saves_and_normalizes_uid(): void
    {
        $this->loginAsAdmin();
        $this->createSiswa(1, 'Budi Santoso');

        // Input dengan format colon hex (04:a1:b2:c3)
        $response = $this->postJson('/datasiswa/assign_rfid', [
            'id_siswa' => 1,
            'nomor_rfid' => '04:a1:b2:c3',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'nomor_rfid' => '04A1B2C3',
            'id_siswa' => 1,
        ]);

        $this->assertDatabaseHas('master_siswa', [
            'id_siswa' => 1,
            'nomor_rfid' => '04A1B2C3',
        ]);
    }

    public function test_assign_rfid_prevents_duplicate_card(): void
    {
        $this->loginAsAdmin();
        $this->createSiswa(1, 'Siswa Satu', '04A1B2C3');
        $this->createSiswa(2, 'Siswa Dua', null);

        // Siswa dua mencoba memakai UID yang sama
        $response = $this->postJson('/datasiswa/assign_rfid', [
            'id_siswa' => 2,
            'nomor_rfid' => '04:a1:b2:c3',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => false,
        ]);
        $this->assertStringContainsString('sudah terdaftar', (string) $response->json('message'));
    }

    public function test_assign_rfid_siswa_prevents_duplicate_with_guru(): void
    {
        $this->loginAsAdmin();

        DB::table('master_guru')->insert([
            'id_guru' => 1,
            'nip' => '198001012010011001',
            'nama_guru' => 'Pak Guru Budi',
            'status_aktif' => 'Aktif',
            'nomor_rfid' => '04A1B2C3',
        ]);

        $this->createSiswa(1, 'Agnes Obe', null);

        // Siswa mencoba memakai RFID milik Guru
        $response = $this->postJson('/datasiswa/assign_rfid', [
            'id_siswa' => 1,
            'nomor_rfid' => '04:a1:b2:c3',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => false]);
        $this->assertStringContainsString('Guru', (string) $response->json('message'));
    }

    public function test_get_siswa_by_kelas_returns_students(): void
    {
        $this->loginAsAdmin();

        DB::table('level_kelas')->insert([
            'id_level' => 10,
            'level' => 10,
        ]);

        DB::table('master_kelas')->insert([
            'id_kelas' => 10,
            'id_tp' => 1,
            'id_smt' => 1,
            'nama_kelas' => 'X-IPA-1',
            'level_id' => 10,
        ]);

        $this->createSiswa(1, 'Agnes Obe', '04A1B2C3');

        DB::table('kelas_siswa')->insert([
            'id_kelas_siswa' => 1,
            'id_tp' => 1,
            'id_smt' => 1,
            'id_siswa' => 1,
            'id_kelas' => 10,
        ]);

        $response = $this->getJson('/datasiswa/siswa_by_kelas/10');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
        ]);

        $lists = $response->json('lists');
        $this->assertCount(1, $lists);
        $this->assertEquals('Agnes Obe', $lists[0]['nama']);
        $this->assertEquals('04A1B2C3', $lists[0]['nomor_rfid']);
    }

    public function test_scanner_scan_endpoint_handles_unregistered_rfid(): void
    {
        $response = $this->postJson('/scanner/scan', [
            'rfid_value' => '99999999',
            'mode' => 'masuk',
            'lat' => -10.123456,
            'lng' => 123.123456,
            'accuracy' => 15,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => false,
            'type' => 'error',
        ]);
        $this->assertStringContainsString('Nomor kartu tidak terdaftar', (string) $response->json('message'));
    }

    public function test_assign_rfid_guru_successfully_saves_and_normalizes_uid(): void
    {
        $this->loginAsAdmin();

        DB::table('master_guru')->insert([
            'id_guru' => 1,
            'nip' => '198001012010011001',
            'nama_guru' => 'Pak Guru Budi',
            'status_aktif' => 'Aktif',
        ]);

        $response = $this->postJson('/dataguru/assign_rfid', [
            'id_guru' => 1,
            'nomor_rfid' => '04:bb:cc:dd',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'nomor_rfid' => '04BBCCDD',
            'id_guru' => 1,
        ]);

        $this->assertDatabaseHas('master_guru', [
            'id_guru' => 1,
            'nomor_rfid' => '04BBCCDD',
        ]);
    }

    public function test_assign_rfid_guru_prevents_duplicate_card(): void
    {
        $this->loginAsAdmin();

        DB::table('master_guru')->insert([
            [
                'id_guru' => 1,
                'nip' => '198001012010011001',
                'nama_guru' => 'Pak Guru Budi',
                'status_aktif' => 'Aktif',
                'nomor_rfid' => '04BBCCDD',
            ],
            [
                'id_guru' => 2,
                'nip' => '198202022010022002',
                'nama_guru' => 'Bu Guru Siti',
                'status_aktif' => 'Aktif',
                'nomor_rfid' => null,
            ],
        ]);

        // Guru 2 mencoba memakai RFID milik Guru 1
        $response = $this->postJson('/dataguru/assign_rfid', [
            'id_guru' => 2,
            'nomor_rfid' => '04:bb:cc:dd',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => false]);
        $this->assertStringContainsString('sudah terdaftar', (string) $response->json('message'));
    }

    public function test_assign_rfid_guru_prevents_duplicate_with_siswa(): void
    {
        $this->loginAsAdmin();
        $this->createSiswa(1, 'Agnes Obe', '04A1B2C3');

        DB::table('master_guru')->insert([
            'id_guru' => 1,
            'nip' => '198001012010011001',
            'nama_guru' => 'Pak Guru Budi',
            'status_aktif' => 'Aktif',
            'nomor_rfid' => null,
        ]);

        // Guru mencoba memakai RFID milik Siswa
        $response = $this->postJson('/dataguru/assign_rfid', [
            'id_guru' => 1,
            'nomor_rfid' => '04:a1:b2:c3',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => false]);
        $this->assertStringContainsString('Siswa', (string) $response->json('message'));
    }

    public function test_dataguru_page_renders_successfully(): void
    {
        $this->loginAsAdmin();

        DB::table('master_guru')->insert([
            'id_guru' => 1,
            'nip' => '198001012010011001',
            'nama_guru' => 'Pak Guru Budi',
            'status_aktif' => 'Aktif',
            'nomor_rfid' => '04A1B2C3',
        ]);

        $response = $this->get('/dataguru');
        $response->assertStatus(200);
        $response->assertSee('Scan NFC Guru');
        $response->assertSee('Pak Guru Budi');
        $response->assertSee('04A1B2C3');
    }

    public function test_datasiswa_page_renders_successfully(): void
    {
        $this->loginAsAdmin();
        $this->createSiswa(1, 'Agnes Obe', '04A1B2C3');

        $response = $this->get('/datasiswa');
        $response->assertStatus(200);
        $response->assertSee('Scan NFC Siswa');
    }

    public function test_kiosk_rfid_page_renders_successfully(): void
    {
        $this->loginAsAdmin();

        $response = $this->get('/kiosk');
        $response->assertStatus(200);
        $response->assertSee('Tempelkan Kartu');
        $response->assertSee('RFID');

        $altResponse = $this->get('/scanner/rfid');
        $altResponse->assertStatus(200);
        $altResponse->assertSee('Tempelkan Kartu');
    }

    public function test_scanner_automatically_renders_mobile_view_on_android_and_desktop_view_on_pc(): void
    {
        $this->loginAsAdmin();

        // 1. Desktop request
        $desktopResponse = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ])->get('/scanner');
        $desktopResponse->assertStatus(200);
        $desktopResponse->assertSee('Scanner QR Siswa Desktop');

        // 2. Android Mobile request
        $mobileResponse = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 14; SM-S928B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
        ])->get('/scanner');
        $mobileResponse->assertStatus(200);
        $mobileResponse->assertSee('Scan QR Presensi Siswa');

        // 3. Override param ?view=mobile on desktop
        $forcedMobile = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ])->get('/scanner?view=mobile');
        $forcedMobile->assertStatus(200);
        $forcedMobile->assertSee('Scan QR Presensi Siswa');
    }
}
