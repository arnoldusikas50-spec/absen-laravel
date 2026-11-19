<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'password123';

    private function buatAkun(string $username, string $roleName, array $overrides = []): int
    {
        $groupId = DB::table('groups')->insertGetId([
            'name' => $roleName,
            'description' => ucfirst($roleName),
        ]);

        $userId = DB::table('users')->insertGetId(array_merge([
            'ip_address' => '127.0.0.1',
            'username' => $username,
            'password' => Hash::make(self::PASSWORD),
            'created_on' => time(),
            'active' => 1,
            'first_name' => 'Test',
        ], $overrides));

        DB::table('users_groups')->insert([
            'user_id' => $userId,
            'group_id' => $groupId,
        ]);

        return $userId;
    }

    public function test_admin_dapat_reset_password_guru(): void
    {
        $this->buatAkun('admin_rp', 'admin');
        $guruUserId = $this->buatAkun('guru_rp', 'guru');

        DB::table('master_guru')->insert([
            'id_guru' => 8001,
            'nip' => '1234567890',
            'nama_guru' => 'Guru Reset',
            'username' => 'guru_rp',
            'password' => 'lama',
        ]);

        $this->postJson('/auth/login', [
            'username' => 'admin_rp',
            'password' => self::PASSWORD,
        ])->assertOk();

        $levelSebelum = DB::transactionLevel();
        $response = $this->post('/userguru/reset_password', ['username' => 'guru_rp']);

        $response->assertOk()->assertJsonPath('status', true);

        // Regresi: di Laravel DB::beginTransaction() mengembalikan void, bukan bool
        // seperti CI4. Pola "$dibuka = DB::beginTransaction()" + "if ($dibuka) DB::commit()"
        // meninggalkan transaksi terbuka (di produksi MySQL implicit rollback -> data
        // hilang) padahal respons status true. Level transaksi harus kembali seperti semula.
        $this->assertSame(
            $levelSebelum,
            DB::transactionLevel(),
            'Transaksi tidak di-commit — level transaksi tidak kembali ke semula.'
        );

        $this->assertTrue(
            password_verify('123456', DB::table('users')->where('id', $guruUserId)->value('password')),
            'Hash password di tabel users tidak berubah ke default 123456.'
        );
        $this->assertSame(
            'lama',
            DB::table('master_guru')->where('username', 'guru_rp')->value('password'),
            'Kolom legacy master_guru.password tidak boleh ditulisi plaintext.'
        );
    }

    public function test_admin_dapat_reset_password_siswa(): void
    {
        $this->buatAkun('admin_rp2', 'admin');
        $siswaUserId = $this->buatAkun('0012345678', 'siswa');

        DB::table('master_siswa')->insert([
            'id_siswa' => 8002,
            'nisn' => '0012345678',
            'nis' => '12345',
            'nama' => 'Siswa Reset',
            'username' => '0012345678',
            'nik' => '3301010101010002',
            'warga_negara' => 'WNI',
            'uid' => 'uid-siswa-reset',
            'password' => 'lama',
        ]);

        $this->postJson('/auth/login', [
            'username' => 'admin_rp2',
            'password' => self::PASSWORD,
        ])->assertOk();

        $levelSebelum = DB::transactionLevel();
        $response = $this->post('/usersiswa/reset_password', ['username' => '0012345678']);

        $response->assertOk()->assertJsonPath('status', true);

        // Lihat komentar pada test guru: transaksi harus benar-benar di-commit.
        $this->assertSame(
            $levelSebelum,
            DB::transactionLevel(),
            'Transaksi tidak di-commit — level transaksi tidak kembali ke semula.'
        );

        $this->assertTrue(
            password_verify('0012345678', DB::table('users')->where('id', $siswaUserId)->value('password')),
            'Hash password siswa tidak berubah ke NISN.'
        );
        $this->assertSame(
            'lama',
            DB::table('master_siswa')->where('username', '0012345678')->value('password'),
            'Kolom legacy master_siswa.password tidak boleh ditulisi plaintext.'
        );
    }
}
