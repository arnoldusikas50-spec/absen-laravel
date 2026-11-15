<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Test untuk endpoint admin/piket/generate-jadwal.
 *
 * Fokus: `generateJadwal()` memetakan hasil query (`get()`) menjadi array ID
 * guru. `get()` mengembalikan Illuminate\Support\Collection, sedangkan
 * array_map() menolak Collection sebagai argumen #2 — harus lewat API Collection
 * atau dikonversi ke array biasa.
 */
class PiketGenerateJadwalTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'password123';

    protected function setUp(): void
    {
        parent::setUp();

        // Konvensi grup produksi: 1=admin, 2=guru, 3=siswa, 4=pengawas.
        // ID di-set eksplisit karena BaseController::isAdmin() memakai
        // session('role') === '1' sebagai penanda admin, dan
        // RestoreRememberLogin mengisi session('role') dari group_id.
        DB::table('groups')->insert([
            ['id' => 1, 'name' => 'admin', 'description' => 'Administrator'],
            ['id' => 2, 'name' => 'guru', 'description' => 'Guru'],
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

    /**
     * Buat user lengkap dengan grup (konvensi AdminUserSeeder).
     *
     * Grup ikut dibaca middleware RestoreRememberLogin yang merevalidasi role
     * dari DB tiap 60 detik, jadi penentuan admin/guru harus benar-benar
     * tersimpan di tabel `groups` — bukan hanya di session.
     */
    private function createUserWithGroup(string $username, string $roleName): User
    {
        $groupId = DB::table('groups')->where('name', $roleName)->value('id');

        $userId = DB::table('users')->insertGetId([
            'ip_address' => '127.0.0.1',
            'username' => $username,
            'password' => Hash::make(self::PASSWORD),
            'created_on' => time(),
            'active' => 1,
            'first_name' => 'Test',
        ]);

        DB::table('users_groups')->insert([
            'user_id' => $userId,
            'group_id' => $groupId,
        ]);

        return User::findOrFail($userId);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function createGuru(int $idGuru, string $nama, array $extra = []): void
    {
        DB::table('master_guru')->insert(array_merge([
            'id_guru' => $idGuru,
            'nip' => '19'.str_pad((string) $idGuru, 8, '0', STR_PAD_LEFT),
            'nama_guru' => $nama,
            'status_aktif' => 'Aktif',
        ], $extra));
    }

    private function loginAsAdmin(): User
    {
        $user = $this->createUserWithGroup('admin_piket', 'admin');

        $this->actingAs($user)->withSession([
            'is_login' => true,
            'user_id' => (int) $user->id,
            'role' => '1',
            'role_name' => 'admin',
        ]);

        return $user;
    }

    public function test_generate_jadwal_membuat_jadwal_piket(): void
    {
        $this->loginAsAdmin();

        $this->createGuru(1, 'Guru Satu');
        $this->createGuru(2, 'Guru Dua');
        $this->createGuru(3, 'Guru Tiga');

        $response = $this->postJson('/admin/piket/generate-jadwal', [
            'jumlah_per_hari' => 2,
            'hari' => ['Senin', 'Selasa'],
            'mode' => 'replace',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('total_inserted', 4);

        $this->assertSame(4, DB::table('piket_jadwal')->count());

        foreach (['Senin', 'Selasa'] as $hari) {
            $this->assertSame(
                2,
                DB::table('piket_jadwal')->where('hari', $hari)->count(),
                "Jumlah petugas untuk hari {$hari} tidak sesuai."
            );
        }

        $this->assertSame(
            4,
            DB::table('piket_jadwal')
                ->where('id_tp', 1)
                ->where('id_smt', 1)
                ->count()
        );
    }

    public function test_generate_jadwal_mengecualikan_kepsek(): void
    {
        $this->loginAsAdmin();

        $this->createGuru(1, 'Guru Satu');
        $this->createGuru(2, 'Guru Dua');
        $this->createGuru(3, 'Kepala Sekolah');

        // Guru 3 berperan sebagai Kepala Sekolah (id_jabatan = 1).
        DB::table('jabatan_guru')->insert([
            'id_jabatan_guru' => 'jb-1',
            'id_guru' => 3,
            'id_jabatan' => 1,
            'id_kelas' => 0,
            'id_tp' => 1,
            'id_smt' => 1,
        ]);

        $response = $this->postJson('/admin/piket/generate-jadwal', [
            'jumlah_per_hari' => 2,
            'hari' => ['Senin'],
            'mode' => 'replace',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('total_inserted', 2)
            ->assertJsonPath('excluded_kepsek', 1);

        $this->assertSame(2, DB::table('piket_jadwal')->count());
        $this->assertSame(0, DB::table('piket_jadwal')->where('id_guru', 3)->count());
    }

    public function test_generate_jadwal_mengganti_jadwal_lama_pada_hari_yang_dipilih(): void
    {
        $this->loginAsAdmin();

        $this->createGuru(1, 'Guru Satu');
        $this->createGuru(2, 'Guru Dua');
        $this->createGuru(3, 'Guru Tiga');

        DB::table('piket_jadwal')->insert([
            'id_tp' => 1,
            'id_smt' => 1,
            'hari' => 'Senin',
            'id_guru' => 3,
            'keterangan' => 'Jadwal lama',
        ]);

        $this->postJson('/admin/piket/generate-jadwal', [
            'jumlah_per_hari' => 1,
            'hari' => ['Senin'],
            'mode' => 'replace',
        ])->assertOk()->assertJsonPath('status', true);

        // Jadwal lama untuk hari Senin harus tergantikan.
        $this->assertSame(1, DB::table('piket_jadwal')->where('hari', 'Senin')->count());
        $this->assertSame(0, DB::table('piket_jadwal')->where('keterangan', 'Jadwal lama')->count());
    }

    public function test_generate_jadwal_menolak_pengguna_bukan_admin(): void
    {
        $user = $this->createUserWithGroup('guru_piket', 'guru');

        $this->actingAs($user)->withSession([
            'is_login' => true,
            'user_id' => (int) $user->id,
            'role' => '2',
            'role_name' => 'guru',
        ]);

        $this->createGuru(1, 'Guru Satu');

        $this->postJson('/admin/piket/generate-jadwal', [
            'jumlah_per_hari' => 1,
            'hari' => ['Senin'],
        ])->assertStatus(403);

        $this->assertSame(0, DB::table('piket_jadwal')->count());
    }

    public function test_generate_jadwal_gagal_bila_tahun_pelajaran_belum_aktif(): void
    {
        $this->loginAsAdmin();

        DB::table('master_tp')->update(['active' => 0]);
        DB::table('master_smt')->update(['active' => 0]);

        $this->createGuru(1, 'Guru Satu');

        $this->postJson('/admin/piket/generate-jadwal', [
            'jumlah_per_hari' => 1,
            'hari' => ['Senin'],
        ])->assertOk()->assertJsonPath('status', false);

        $this->assertSame(0, DB::table('piket_jadwal')->count());
    }
}
