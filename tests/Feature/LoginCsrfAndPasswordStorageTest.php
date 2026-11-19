<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UsersModel;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LoginCsrfAndPasswordStorageTest extends TestCase
{
    use RefreshDatabase;

    private const GURU_USER_ID = 999001;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('groups')->insert([
            ['id' => 1, 'name' => 'admin', 'description' => 'Administrator'],
            ['id' => 2, 'name' => 'guru', 'description' => 'Guru'],
            ['id' => 3, 'name' => 'siswa', 'description' => 'Siswa'],
        ]);

        DB::table('users')->insert([
            'id' => self::GURU_USER_ID,
            'ip_address' => '127.0.0.1',
            'username' => 'guru_sandi_test',
            'password' => Hash::make('sandi-lama'),
            'created_on' => time(),
            'active' => 1,
            'first_name' => 'Guru',
        ]);

        DB::table('master_guru')->insert([
            'id_guru' => 1,
            'id_user' => self::GURU_USER_ID,
            'nip' => '999000111',
            'nama_guru' => 'GURU SANDI',
            'username' => 'guru_sandi_test',
        ]);

        $this->loginAsAdmin();
    }

    private function loginAsAdmin(): void
    {
        $userId = DB::table('users')->insertGetId([
            'ip_address' => '127.0.0.1',
            'username' => 'admin_sandi_test',
            'password' => Hash::make('password123'),
            'created_on' => time(),
            'active' => 1,
            'first_name' => 'Admin',
        ]);

        DB::table('users_groups')->insert(['user_id' => $userId, 'group_id' => 1]);

        $this->actingAs(User::findOrFail($userId))->withSession([
            'is_login' => true,
            'user_id' => (int) $userId,
            'role' => '1',
            'role_name' => 'admin',
        ]);
    }

    public function test_login_tidak_lagi_dikecualikan_dari_csrf(): void
    {
        foreach (['auth.login', 'presensi.auth.login_auth'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, 'Rute '.$name.' tidak ditemukan');
            $this->assertNotContains(
                VerifyCsrfToken::class,
                $route->excludedMiddleware(),
                'Rute '.$name.' masih mengecualikan CSRF'
            );
        }
    }

    public function test_reset_password_guru_tidak_menulis_plaintext_ke_master(): void
    {
        $this->post('/userguru/reset_password', ['username' => 'guru_sandi_test'])
            ->assertOk()
            ->assertJson(['status' => true]);

        $this->assertNull(DB::table('master_guru')->where('id_guru', 1)->value('password'));

        $hash = DB::table('users')->where('id', self::GURU_USER_ID)->value('password');
        $this->assertTrue(Hash::check('123456', $hash));
    }

    public function test_daftar_guru_tidak_mengembalikan_kolom_password(): void
    {
        $rows = (new UsersModel)->getUserGuru(1, 1);
        $guru = collect($rows)->firstWhere('id_guru', 1);

        $this->assertNotNull($guru);
        $this->assertFalse(property_exists($guru, 'password'));
    }
}
