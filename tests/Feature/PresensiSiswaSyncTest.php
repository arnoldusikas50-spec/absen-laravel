<?php

namespace Tests\Feature;

use App\Services\AttendancePolicy;
use App\Services\PresensiSiswaSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PresensiSiswaSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // AttendancePolicy menyimpan setting di static cache per proses.
        // Di test (satu proses untuk banyak kasus) cache itu harus dibuang
        // agar setting yang baru di-insert benar-benar terbaca.
        foreach (['setting' => null, 'settingLoaded' => false, 'jadwalKerja' => null, 'liburNasional' => null] as $prop => $value) {
            $ref = new \ReflectionProperty(AttendancePolicy::class, $prop);
            $ref->setAccessible(true);
            $ref->setValue(null, $value);
        }
    }

    private function seedDasar(): int
    {
        DB::table('absensi_setting')->insert([
            'jam_masuk' => '07:00:00',
            'jam_pulang' => '15:00:00',
            'grace_period' => 0,
            'hari_libur_mingguan' => 'Sun',
            'denda_alpha_siswa_per_hari' => 5000,
            'denda_bolos_siswa_per_hari' => 0,
            'max_denda_siswa_bulan' => 0,
        ]);

        DB::table('disiplin_kategori')->insert([
            'nama' => 'Bolos sekolah',
            'jenis' => 'PELANGGARAN',
            'bobot' => 5,
            'is_active' => 1,
        ]);

        DB::table('master_siswa')->insert([
            'id_siswa' => 1,
            'nisn' => '0010000001',
            'nis' => '1001',
            'nama' => 'Siswa Uji',
            'username' => 'siswa_uji',
            'nik' => '3201000000000001',
            'warga_negara' => 'WNI',
            'uid' => 'uid-siswa-uji',
        ]);

        // Rabu, hari kerja, sudah lewat → alpha tertagih.
        return (int) DB::table('presensi_siswa')->insertGetId([
            'id_kelas' => 1,
            'id_siswa' => 1,
            'tgl' => '2025-01-15',
            'status' => 'ALPA',
        ]);
    }

    public function test_poin_dan_denda_dihitung_dalam_satu_sinkron(): void
    {
        $presensiId = $this->seedDasar();

        $hasil = PresensiSiswaSyncService::sinkron($presensiId);

        $this->assertTrue($hasil['status'], (string) $hasil['message']);

        $this->assertSame(
            -5,
            (int) DB::table('poin_siswa_history')->where('ref_id', 'presensi-'.$presensiId)->value('poin')
        );

        $baris = DB::table('presensi_siswa')->where('id', $presensiId)->first();
        $this->assertSame(5000.0, (float) $baris->denda);
        $this->assertSame('alpha', (string) $baris->denda_jenis);
    }

    public function test_kegagalan_denda_membatalkan_poin_yang_sudah_ditulis(): void
    {
        $presensiId = $this->seedDasar();

        // Paksa UPDATE presensi_siswa gagal — denda dihitung SETELAH poin,
        // jadi ini membuktikan poin ikut dibatalkan (satu transaksi).
        DB::statement("CREATE TRIGGER gagalkan_denda BEFORE UPDATE ON presensi_siswa BEGIN SELECT RAISE(ABORT, 'boom'); END;");

        $hasil = PresensiSiswaSyncService::sinkron($presensiId);

        $this->assertFalse($hasil['status']);
        $this->assertSame(0, DB::table('poin_siswa_history')->where('ref_id', 'presensi-'.$presensiId)->count());
        $this->assertSame(0.0, (float) DB::table('presensi_siswa')->where('id', $presensiId)->value('denda'));
    }

    public function test_id_presensi_tidak_valid_tidak_menulis_apa_pun(): void
    {
        $hasil = PresensiSiswaSyncService::sinkron(0);

        $this->assertFalse($hasil['status']);
        $this->assertSame(0, DB::table('poin_siswa_history')->count());
    }
}
