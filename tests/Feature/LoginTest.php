<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'password123';

    /**
     * Buat user lengkap dengan grup (konvensi AdminUserSeeder).
     *
     * @param  array<string, mixed>  $userAttributes
     * @return array{user_id: int, group_id: int}
     */
    private function createUserWithGroup(string $username, string $roleName, array $userAttributes = []): array
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
        ], $userAttributes));

        DB::table('users_groups')->insert([
            'user_id' => $userId,
            'group_id' => $groupId,
        ]);

        return ['user_id' => $userId, 'group_id' => $groupId];
    }

    /**
     * Buat baris master_siswa + buku_induk untuk username tertentu.
     */
    private function createSiswa(string $username, int $status): void
    {
        $idSiswa = 9001;

        DB::table('master_siswa')->insert([
            'id_siswa' => $idSiswa,
            'nisn' => '0012345678',
            'nis' => '12345',
            'nama' => 'Siswa Test',
            'username' => $username,
            'nik' => '3301010101010001',
            'warga_negara' => 'WNI',
            'uid' => 'uid-'.$username,
        ]);

        DB::table('buku_induk')->insert([
            'id_siswa' => $idSiswa,
            'status' => $status,
        ]);
    }

    public function test_active_user_can_login(): void
    {
        $created = $this->createUserWithGroup('admin_test', 'admin');

        $response = $this->postJson('/auth/login', [
            'username' => 'admin_test',
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'Login berhasil')
            ->assertJsonPath('data.redirect', url('dashboard'));

        $this->assertSame($created['user_id'], (int) session('user_id'));
        $this->assertTrue((bool) session('is_login'));
    }

    public function test_inactive_user_is_rejected(): void
    {
        $this->createUserWithGroup('admin_nonaktif', 'admin', ['active' => 0]);

        $response = $this->postJson('/auth/login', [
            'username' => 'admin_nonaktif',
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Akun Anda dinonaktifkan!');

        $this->assertFalse((bool) session('is_login'));
    }

    public function test_active_siswa_can_login(): void
    {
        $created = $this->createUserWithGroup('siswa_test', 'siswa');
        $this->createSiswa('siswa_test', 1);

        $response = $this->postJson('/auth/login', [
            'username' => 'siswa_test',
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'Login berhasil')
            ->assertJsonPath('data.redirect', url('siswa/presensi'));

        $this->assertSame($created['user_id'], (int) session('user_id'));
        $this->assertTrue((bool) session('is_login'));
    }

    public function test_siswa_with_inactive_buku_induk_is_rejected(): void
    {
        $this->createUserWithGroup('siswa_nonaktif', 'siswa');
        $this->createSiswa('siswa_nonaktif', 0);

        $response = $this->postJson('/auth/login', [
            'username' => 'siswa_nonaktif',
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Akun Anda dinonaktifkan!');

        $this->assertFalse((bool) session('is_login'));
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->createUserWithGroup('admin_test', 'admin');

        $response = $this->postJson('/auth/login', [
            'username' => 'admin_test',
            'password' => 'password-salah',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Username atau Password salah!');

        $this->assertFalse((bool) session('is_login'));
    }
}
