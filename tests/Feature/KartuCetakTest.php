<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KartuCetakTest extends TestCase
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

    private function siapkanKelasDanSiswa(int $jumlahSiswa): void
    {
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

        for ($i = 1; $i <= $jumlahSiswa; $i++) {
            DB::table('master_siswa')->insert([
                'id_siswa' => $i,
                'nis' => '100'.$i,
                'nisn' => '00100'.$i,
                'nama' => 'Siswa '.$i,
                'jenis_kelamin' => 'L',
                'username' => 'siswa'.$i,
                'nik' => '32010000000000'.$i,
                'warga_negara' => 'WNI',
                'uid' => 'uid-siswa-'.$i,
            ]);

            DB::table('kelas_siswa')->insert([
                'id_kelas_siswa' => $i,
                'id_tp' => 1,
                'id_smt' => 1,
                'id_siswa' => $i,
                'id_kelas' => 10,
            ]);
        }
    }

    public function test_kartu_siswa_mode_ktp_mengatur_ukuran_kertas_cr80(): void
    {
        $this->loginAsAdmin();
        $this->siapkanKelasDanSiswa(2);

        $response = $this->get('/admin/presensi/siswa/kartu/cetak?id_kelas=10&mode=ktp');

        $response->assertStatus(200);
        $response->assertSee('85.6mm 54mm');
        $response->assertSee('ktp-page');
        $response->assertSee('class="card-dl card-dl-front"', false);
    }

    public function test_kartu_siswa_mode_bolakbalik_mencetak_depan_dan_belakang_sama_banyak(): void
    {
        $this->loginAsAdmin();
        $this->siapkanKelasDanSiswa(3);

        $response = $this->get('/admin/presensi/siswa/kartu/cetak?id_kelas=10&mode=bolakbalik&flip=long');

        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertSame(3, substr_count($html, 'class="card-dl card-dl-front"'));
        $this->assertSame(3, substr_count($html, 'class="card-dl card-dl-back"'));
        $this->assertSame(1, substr_count($html, 'class="bolakbalik-group"'));
    }

    public function test_kartu_siswa_bolakbalik_mencerminkan_urutan_sisi_belakang_untuk_flip_sisi_panjang(): void
    {
        $this->loginAsAdmin();
        $this->siapkanKelasDanSiswa(3);

        $response = $this->get('/admin/presensi/siswa/kartu/cetak?id_kelas=10&mode=bolakbalik&flip=long');
        $response->assertStatus(200);

        // Grid 2 kolom: potongan [1,2],[3] → kolom dibalik → [2,1],[3].
        $back = substr($response->getContent(), strpos($response->getContent(), 'Sisi Belakang'));

        $pos1 = strpos($back, 'Siswa 1');
        $pos2 = strpos($back, 'Siswa 2');
        $pos3 = strpos($back, 'Siswa 3');

        $this->assertNotFalse($pos1);
        $this->assertNotFalse($pos2);
        $this->assertNotFalse($pos3);
        $this->assertLessThan($pos1, $pos2, 'Sisi belakang kartu 2 harus dicetak sebelum kartu 1 (kolom dibalik).');
        $this->assertLessThan($pos3, $pos1, 'Sisi belakang kartu 3 tetap di baris berikutnya.');
    }

    public function test_kartu_guru_mode_ktp_menyiapkan_satu_halaman_per_sisi_dua_sisi(): void
    {
        $this->loginAsAdmin();

        DB::table('master_guru')->insert([
            [
                'id_guru' => 1,
                'nip' => '198001012010011001',
                'nama_guru' => 'Pak Guru Budi',
                'status_aktif' => 'Aktif',
            ],
            [
                'id_guru' => 2,
                'nip' => '198202022010022002',
                'nama_guru' => 'Bu Guru Siti',
                'status_aktif' => 'Aktif',
            ],
        ]);

        $response = $this->get('/admin/presensi/guru/kartu/cetak?mode=ktp');

        $response->assertStatus(200);
        $response->assertSee('85.6mm 54mm');
        // Mode KTP cetak satu sisi per halaman: semua depan dulu, lalu semua belakang (2 sisi × 2 guru = 4 halaman).
        $this->assertSame(4, substr_count($response->getContent(), 'class="ktp-page"'));
        $this->assertSame(4, substr_count($response->getContent(), 'class="ktp-cap no-print"'));
        $this->assertStringContainsString('Sisi Depan', $response->getContent());
        $this->assertStringContainsString('Sisi Belakang', $response->getContent());
    }

    public function test_kartu_guru_tanpa_mode_tetap_merender_grid_a4(): void
    {
        $this->loginAsAdmin();

        DB::table('master_guru')->insert([
            'id_guru' => 1,
            'nip' => '198001012010011001',
            'nama_guru' => 'Pak Guru Budi',
            'status_aktif' => 'Aktif',
        ]);

        $response = $this->get('/admin/presensi/guru/kartu/cetak');

        $response->assertStatus(200);
        $response->assertSee('card-grid', false);
        $response->assertDontSee('class="ktp-page"', false);
    }

    /**
     * Buat berkas PNG latar kartu palsu di uploads/settings/ dan isi kolom
     * setting.kartu_bg_depan / kartu_bg_belakang. Nama unik supaya tidak
     * menimpa latar asli milik sekolah.
     *
     * @return list<string> daftar path relatif yang dibuat
     */
    private function buatLatarKartuPalsu(): array
    {
        $paths = [
            'uploads/settings/test-kartu-bg-depan-'.uniqid().'.png',
            'uploads/settings/test-kartu-bg-belakang-'.uniqid().'.png',
        ];

        foreach ($paths as $path) {
            file_put_contents(public_path($path), base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
            ));
        }

        DB::table('setting')->insert([
            'sekolah' => 'SMA NEGERI 1 UJI COBA',
            'kartu_bg_depan' => $paths[0],
            'kartu_bg_belakang' => $paths[1],
        ]);

        return $paths;
    }

    private function hapusLatarKartuPalsu(array $paths): void
    {
        foreach ($paths as $path) {
            if (file_exists(public_path($path))) {
                unlink(public_path($path));
            }
        }
    }

    public function test_kartu_siswa_template_png_memakai_latar_dan_menyembunyikan_vektor(): void
    {
        $this->loginAsAdmin();
        $this->siapkanKelasDanSiswa(1);
        $latar = $this->buatLatarKartuPalsu();

        try {
            $response = $this->get('/admin/presensi/siswa/kartu/cetak?id_kelas=10&desain=duplex_portrait&template=png');

            $response->assertStatus(200);
            // Root kartu diberi penanda template PNG + latar dirender <img>.
            $response->assertSee('class="card-dp card-dp-front card-dp-tpl-png"', false);
            $response->assertSee('<img class="dp-bg-img"', false);
            $response->assertSee('uploads/settings/', false);
            // Hiasan vektor tidak ikut tercetak di atas latar PNG.
            $response->assertDontSee('class="dp-header"', false);
            $response->assertDontSee('class="dp-back-watermark"', false);
        } finally {
            $this->hapusLatarKartuPalsu($latar);
        }
    }

    public function test_kartu_siswa_template_png_fallback_ke_vektor_tanpa_latar(): void
    {
        $this->loginAsAdmin();
        $this->siapkanKelasDanSiswa(1);

        DB::table('setting')->insert([
            'sekolah' => 'SMA NEGERI 1 UJI COBA',
        ]);

        // Minta template PNG padahal belum ada latar terunggah → vektor.
        $response = $this->get('/admin/presensi/siswa/kartu/cetak?id_kelas=10&desain=duplex_portrait&template=png');

        $response->assertStatus(200);
        $response->assertSee('class="card-dp card-dp-front"', false);
        $response->assertDontSee('card-dp-front card-dp-tpl-png', false);
        $response->assertDontSee('<img class="dp-bg-img"', false);
        // Desain vektor utuh: kop & watermark tetap dirender.
        $response->assertSee('class="dp-header"', false);
        $response->assertSee('class="dp-back-watermark"', false);
    }

    public function test_kartu_siswa_template_png_hanya_untuk_duplex_portrait(): void
    {
        $this->loginAsAdmin();
        $this->siapkanKelasDanSiswa(1);
        $latar = $this->buatLatarKartuPalsu();

        try {
            // Desain landscape duplex tetap memakai kartu_dl — template PNG tidak berlaku.
            $response = $this->get('/admin/presensi/siswa/kartu/cetak?id_kelas=10&desain=duplex&template=png');

            $response->assertStatus(200);
            $response->assertSee('class="card-dl card-dl-front"', false);
            $response->assertDontSee('card-dp-front card-dp-tpl-png', false);
            $response->assertDontSee('<img class="dp-bg-img"', false);
        } finally {
            $this->hapusLatarKartuPalsu($latar);
        }
    }
}
