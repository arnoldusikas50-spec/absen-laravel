<?php

namespace Tests\Feature;

use App\Models\AuthModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthModelUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_tidak_menggandakan_nama_yang_sama(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Budi',
            'last_name' => 'Budi',
        ]);

        $result = (new AuthModel)->user($user->id);

        $this->assertNotNull($result);
        $this->assertSame('Budi', $result->nama_lengkap);
    }

    public function test_user_menggabungkan_nama_depan_dan_belakang(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
        ]);

        $result = (new AuthModel)->user($user->id);

        $this->assertNotNull($result);
        $this->assertSame('Budi Santoso', $result->nama_lengkap);
    }

    public function test_reset_error_login_hanya_menghapus_username_tertentu(): void
    {
        $now = time();
        DB::table('login_attempts')->insert([
            ['ip_address' => '127.0.0.1', 'login' => 'budi', 'time' => $now],
            ['ip_address' => '127.0.0.1', 'login' => 'siti', 'time' => $now],
        ]);

        $this->assertTrue((new AuthModel)->resetErrorLogin('budi'));
        $this->assertDatabaseMissing('login_attempts', ['login' => 'budi']);
        $this->assertDatabaseHas('login_attempts', ['login' => 'siti']);
    }
}
