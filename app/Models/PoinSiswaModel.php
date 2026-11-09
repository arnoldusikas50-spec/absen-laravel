<?php

namespace App\Models;

use App\Services\SiswaPoinService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari CI4: app/Models/PoinSiswaModel.php
 * Tabel: poin_siswa_history
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 *
 * Modul Poin Kedisiplinan Siswa
 * Skor = SKOR_AWAL (100) + SUM(poin_siswa_history.poin)
 * Kunci identitas: master_siswa.id_siswa (selaras presensi_siswa).
 */
class PoinSiswaModel extends Model
{
    public const SKOR_AWAL = 100;

    protected $table = 'poin_siswa_history';

    protected $fillable = [
        'id_siswa',
        'id_kelas',
        'poin',
        'kategori_id',
        'tipe',
        'keterangan',
        'ref_id',
        'created_by',
        'created_at',
    ];

    public $timestamps = false;

    protected $casts = [
        'id' => 'integer',
        'id_siswa' => 'integer',
        'id_kelas' => 'integer',
        'poin' => 'integer',
        'kategori_id' => 'integer',
        'created_by' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Buat tabel + kolom cache bila belum ada (pola runtime-ensure,
     * karena spark migrate tidak selalu dijalankan di server).
     */
    // TODO: tabel master_siswa belum ada migrasinya
    public function ensureTables(): void
    {
        if (! Schema::hasTable('disiplin_kategori')) {
            Schema::create('disiplin_kategori', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nama', 100);
                $table->enum('jenis', ['PELANGGARAN', 'PENGHARGAAN'])->default('PELANGGARAN');
                $table->integer('bobot')->default(5);
                $table->boolean('is_active')->default(1);
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->index('jenis');
                $table->index('is_active');
            });
            $this->seedDefaultKategori();
        }
        if (! Schema::hasTable('poin_siswa_history')) {
            Schema::create('poin_siswa_history', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('id_siswa');
                $table->integer('id_kelas')->nullable();
                $table->integer('poin')->default(0);
                $table->unsignedInteger('kategori_id')->nullable();
                $table->enum('tipe', ['PELANGGARAN', 'PENGHARGAAN', 'KOREKSI', 'PRESENSI'])->default('PELANGGARAN');
                $table->string('keterangan', 255)->nullable();
                $table->string('ref_id', 80)->nullable()->unique();
                $table->integer('created_by')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->index('id_siswa');
                $table->index('tipe');
                $table->index('created_at');
            });
        }
        if (Schema::hasTable('master_siswa') && ! Schema::hasColumn('master_siswa', 'poin_disiplin')) {
            try {
                Schema::table('master_siswa', function (Blueprint $table) {
                    $table->integer('poin_disiplin')->default(100);
                });
            } catch (\Throwable $e) {
                Log::error('[PoinSiswaModel::ensureTables] '.$e->getMessage());
            }
        }
    }

    /**
     * Skor terkini siswa (100 + akumulasi ledger).
     *
     * null bila ledger tidak terbaca. Dulu mengembalikan SKOR_AWAL pada
     * kegagalan: query yang error membuat siswa berpoin -60 terlihat bersih
     * kembali, dan updateCache() lalu menulis angka palsu itu ke
     * master_siswa.poin_disiplin.
     */
    public function getSkor(int $idSiswa): ?int
    {
        if ($idSiswa <= 0) {
            return null;
        }
        try {
            $row = DB::table('poin_siswa_history')
                ->selectRaw('SUM(poin) as total')
                ->where('id_siswa', $idSiswa)
                ->first();

            return self::SKOR_AWAL + (int) ($row->total ?? 0);
        } catch (\Throwable $e) {
            Log::error('[PoinSiswaModel::getSkor#'.$idSiswa.'] '.$e->getMessage());

            return null;
        }
    }

    /**
     * Kunci baris siswa untuk transaksi poin.
     *
     * Ambang "skor tidak boleh di bawah 0" dibaca lalu ditulis. Tanpa kunci,
     * dua pencatatan paralel sama-sama melihat skor 5 dan keduanya lolos
     * sehingga skor final jadi negatif.
     */
    // TODO: tabel master_siswa belum ada migrasinya
    private function kunciSiswa(object $db, int $idSiswa): void
    {
        try {
            $db->select(
                'SELECT id_siswa FROM '.$db->getTablePrefix().'master_siswa WHERE id_siswa = ? FOR UPDATE',
                [$idSiswa]
            );
        } catch (\Throwable $e) {
            // SQLite tidak mendukung FOR UPDATE; di sana transaksi sudah seri.
        }
    }

    /**
     * Rombel aktif siswa pada periode berjalan (bukan nilai kiriman klien).
     */
    // TODO: tabel kelas_siswa belum ada migrasinya
    public function kelasAktifSiswa(int $idSiswa): ?int
    {
        if ($idSiswa <= 0) {
            return null;
        }
        try {
            $dash = new DashboardModel;
            $tp = $dash->getTahunActive();
            $smt = $dash->getSemesterActive();
            if (! $tp || ! $smt) {
                return null;
            }
            $row = DB::table('kelas_siswa')
                ->select('id_kelas')
                ->where('id_siswa', $idSiswa)
                ->where('id_tp', (int) $tp->id_tp)
                ->where('id_smt', (int) $smt->id_smt)
                ->first();
            $row = $row !== null ? (array) $row : null;
            $idKelas = (int) ($row['id_kelas'] ?? 0);

            return $idKelas > 0 ? $idKelas : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Sinkron kolom cache master_siswa.poin_disiplin.
     * Tidak menulis apa pun bila skor terkini tidak terbaca — menulis 100
     * hasil fallback justru menghapus jejak poin siswa di kolom cache.
     */
    // TODO: tabel master_siswa belum ada migrasinya
    public function updateCache(int $idSiswa): ?int
    {
        $skor = $this->getSkor($idSiswa);
        if ($skor === null) {
            return null;
        }
        try {
            if (Schema::hasTable('master_siswa') && Schema::hasColumn('master_siswa', 'poin_disiplin')) {
                DB::table('master_siswa')->where('id_siswa', $idSiswa)->update(['poin_disiplin' => $skor]);
            }
        } catch (\Throwable $e) {
            Log::error('[PoinSiswaModel::updateCache] '.$e->getMessage());
        }

        return $skor;
    }

    /**
     * Riwayat terbaru siswa (join nama kategori).
     */
    public function getRiwayat(int $idSiswa, int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));
        try {
            return DB::table('poin_siswa_history as h')
                ->select('h.*', 'k.nama as kategori_nama', 'k.jenis as kategori_jenis')
                ->leftJoin('disiplin_kategori as k', 'k.id', '=', 'h.kategori_id')
                ->where('h.id_siswa', $idSiswa)
                ->orderBy('h.created_at', 'DESC')
                ->orderBy('h.id', 'DESC')
                ->limit($limit)
                ->get()->all();
        } catch (\Throwable $e) {
            Log::error('[PoinSiswaModel::getRiwayat] '.$e->getMessage());

            return [];
        }
    }

    /**
     * Leaderboard skor per kelas (atau semua).
     * Kembalikan [{id_siswa, nama, nis, nama_kelas, skor, total_kasus}]
     *
     * Dulu join ke kelas_siswa lalu groupBy('s.id_siswa'): tanpa filter
     * TP/SMT satu siswa punya banyak baris enrollment (riwayat rombel),
     * sehingga nama_kelas yang tampil acak dari periode mana pun, dan
     * kolom non-agregat di SELECT gugur di ONLY_FULL_GROUP_BY — model
     * menelan error itu dan halaman hanya menampilkan daftar kosong.
     */
    // TODO: tabel master_siswa, kelas_siswa, master_kelas, buku_induk belum ada migrasinya
    public function getLeaderboard(?int $idKelas = null, int $limit = 200): array
    {
        try {
            $tp = null;
            $smt = null;
            try {
                $dash = new DashboardModel;
                $tpRow = $dash->getTahunActive();
                $smtRow = $dash->getSemesterActive();
                $tp = $tpRow ? (int) $tpRow->id_tp : null;
                $smt = $smtRow ? (int) $smtRow->id_smt : null;
            } catch (\Throwable $e) {
            }

            $ketentuanKelas = 'ks.id_siswa = s.id_siswa AND ks.id_kelas IS NOT NULL';
            if ($tp && $smt) {
                $ketentuanKelas .= ' AND ks.id_tp = '.$tp.' AND ks.id_smt = '.$smt;
            }

            $b = DB::table('master_siswa as s')
                ->selectRaw('s.id_siswa, s.nama, s.nis, s.foto,
                    (SELECT c.nama_kelas FROM kelas_siswa ks
                        JOIN master_kelas c ON c.id_kelas = ks.id_kelas
                        WHERE '.$ketentuanKelas.'
                        ORDER BY ks.id_kelas_siswa DESC LIMIT 1) as nama_kelas,
                    COALESCE((SELECT SUM(h.poin) FROM poin_siswa_history h WHERE h.id_siswa = s.id_siswa), 0) as akumulasi,
                    (SELECT COUNT(*) FROM poin_siswa_history h WHERE h.id_siswa = s.id_siswa) as total_kasus');

            // Hanya siswa AKTIF (buku_induk.status = '1'); siswa tanpa buku induk dianggap aktif.
            try {
                if (Schema::hasTable('buku_induk')) {
                    $b->whereRaw("(SELECT COUNT(*) FROM buku_induk bi WHERE bi.id_siswa = s.id_siswa AND bi.status <> '1') = 0");
                }
            } catch (\Throwable $e) {
            }

            if ($idKelas) {
                // Kondisi utuh apa adanya: where() biasa memotong string pada
                // tanda '=' lalu meng-escape backtick potongan kirinya, sehingga
                // subquery berkorelasi jadi sintaks salah dan get() mengembalikan
                // false — filter rombel diam-diam selalu kosong.
                $b->whereRaw('(SELECT ks2.id_kelas FROM kelas_siswa ks2 WHERE ks2.id_siswa = s.id_siswa AND ks2.id_kelas IS NOT NULL'
                    .($tp && $smt ? ' AND ks2.id_tp = '.$tp.' AND ks2.id_smt = '.$smt : '')
                    .' ORDER BY ks2.id_kelas_siswa DESC LIMIT 1) = '.(int) $idKelas);
            }

            $hasil = $b->orderBy('akumulasi', 'DESC')->orderBy('s.nama', 'ASC')
                ->limit(max(1, min($limit, 500)))
                ->get();
            $rows = $hasil->all();
            foreach ($rows as $r) {
                $r->skor = self::SKOR_AWAL + (int) ($r->akumulasi ?? 0);
            }

            return $rows;
        } catch (\Throwable $e) {
            Log::error('[PoinSiswaModel::getLeaderboard] '.$e->getMessage());

            return [];
        }
    }

    /**
     * Agregasi bulanan setahun untuk grafik detail.
     */
    public function getMonthly(int $idSiswa, int $tahun): array
    {
        $out = array_fill(1, 12, 0);
        try {
            $rows = DB::table('poin_siswa_history')
                ->selectRaw('MONTH(created_at) as bln, SUM(poin) as total')
                ->where('id_siswa', $idSiswa)
                ->whereYear('created_at', $tahun)
                ->groupBy('bln')->get()->all();
            foreach ($rows as $r) {
                $out[(int) $r->bln] = (int) $r->total;
            }
        } catch (\Throwable $e) {
            // abaikan, kembalikan nol
        }

        return $out;
    }

    /**
     * Ringkasan per tipe setahun: [tipe => {poin, count}]
     */
    public function getTypeSummary(int $idSiswa, int $tahun): array
    {
        try {
            $rows = DB::table('poin_siswa_history')
                ->selectRaw('tipe, SUM(poin) as poin, COUNT(*) as jumlah')
                ->where('id_siswa', $idSiswa)
                ->whereYear('created_at', $tahun)
                ->groupBy('tipe')->get()->all();
            $out = [];
            foreach ($rows as $r) {
                $out[$r->tipe] = ['poin' => (int) $r->poin, 'count' => (int) $r->jumlah];
            }

            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ── Master kategori ──────────────────────────────────────────

    public function getKategori(bool $activeOnly = true): array
    {
        try {
            $b = DB::table('disiplin_kategori')->orderBy('jenis', 'ASC')->orderBy('bobot', 'DESC')->orderBy('nama', 'ASC');
            if ($activeOnly) {
                $b->where('is_active', 1);
            }

            return $b->get()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function saveKategori(array $data, ?int $id = null): array
    {
        $nama = trim((string) ($data['nama'] ?? ''));
        $jenis = strtoupper(trim((string) ($data['jenis'] ?? '')));
        $bobot = (int) ($data['bobot'] ?? 0);
        $active = (int) ($data['is_active'] ?? 1) === 1 ? 1 : 0;

        if (mb_strlen($nama) < 3 || mb_strlen($nama) > 100) {
            return ['status' => false, 'message' => 'Nama kategori 3–100 karakter'];
        }
        if (! in_array($jenis, ['PELANGGARAN', 'PENGHARGAAN'], true)) {
            return ['status' => false, 'message' => 'Jenis tidak valid'];
        }
        if ($bobot < 1 || $bobot > 100) {
            return ['status' => false, 'message' => 'Bobot harus 1–100 poin'];
        }
        try {
            $dup = DB::table('disiplin_kategori')->whereRaw('LOWER(nama) = ?', [strtolower($nama)]);
            if ($id) {
                $dup->where('id', '!=', $id);
            }
            if ($dup->count() > 0) {
                return ['status' => false, 'message' => 'Nama kategori sudah ada'];
            }
            $row = [
                'nama' => $nama, 'jenis' => $jenis, 'bobot' => $bobot,
                'is_active' => $active, 'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($id) {
                if (DB::table('disiplin_kategori')->where('id', $id)->count() === 0) {
                    return ['status' => false, 'message' => 'Kategori tidak ditemukan'];
                }
                DB::table('disiplin_kategori')->where('id', $id)->update($row);

                return ['status' => true, 'message' => 'Kategori diperbarui'];
            }
            $row['created_at'] = date('Y-m-d H:i:s');
            DB::table('disiplin_kategori')->insert($row);

            return ['status' => true, 'message' => 'Kategori ditambahkan'];
        } catch (\Throwable $e) {
            Log::error('[PoinSiswaModel::saveKategori] '.$e->getMessage());

            return ['status' => false, 'message' => 'Gagal menyimpan kategori'];
        }
    }

    public function deleteKategori(int $id): array
    {
        try {
            if ($id <= 0 || DB::table('disiplin_kategori')->where('id', $id)->count() === 0) {
                return ['status' => false, 'message' => 'Kategori tidak ditemukan'];
            }
            $used = DB::table('poin_siswa_history')->where('kategori_id', $id)->count() > 0;
            if ($used) {
                return ['status' => false, 'message' => 'Kategori sudah dipakai di riwayat — nonaktifkan saja'];
            }
            DB::table('disiplin_kategori')->where('id', $id)->delete();

            return ['status' => true, 'message' => 'Kategori dihapus'];
        } catch (\Throwable $e) {
            Log::error('[PoinSiswaModel::deleteKategori] '.$e->getMessage());

            return ['status' => false, 'message' => 'Gagal menghapus kategori'];
        }
    }

    // ── Pencatatan ───────────────────────────────────────────────

    /**
     * Catat poin manual — HANYA dari master kategori aktif (master saja).
     */
    // TODO: tabel master_siswa, buku_induk, kelas_siswa belum ada migrasinya
    public function recordManual(int $idSiswa, int $kategoriId, string $keterangan, ?int $createdBy = null): array
    {
        $keterangan = trim($keterangan);
        if ($idSiswa <= 0) {
            return ['status' => false, 'message' => 'Siswa tidak valid'];
        }
        if (mb_strlen($keterangan) < 5 || mb_strlen($keterangan) > 255) {
            return ['status' => false, 'message' => 'Keterangan 5–255 karakter'];
        }

        try {
            // Siswa harus benar-benar ada. Tanpa ini id_siswa karangan punya
            // "skor 100" sendiri dan ledger-nya tidak akan pernah tampil di
            // halaman mana pun (semua laporan join ke master_siswa).
            $ada = DB::table('master_siswa')->select('id_siswa')
                ->where('id_siswa', $idSiswa)->first();
            $ada = $ada !== null ? (array) $ada : null;
            if (! $ada) {
                return ['status' => false, 'message' => 'Siswa tidak ditemukan'];
            }

            // Hanya siswa AKTIF boleh dicatat poinnya.
            try {
                if (Schema::hasTable('buku_induk')) {
                    $st = DB::table('buku_induk')->select('status')
                        ->where('id_siswa', $idSiswa)->first();
                    if ($st && (string) ($st->status ?? '1') !== '1') {
                        return ['status' => false, 'message' => 'Siswa sudah tidak aktif (lulus/pindah/keluar)'];
                    }
                }
            } catch (\Throwable $e) {
            }

            $kat = DB::table('disiplin_kategori')
                ->where('id', $kategoriId)->where('is_active', 1)->first();
            if (! $kat) {
                return ['status' => false, 'message' => 'Kategori tidak valid / nonaktif'];
            }
            $poin = $kat->jenis === 'PENGHARGAAN' ? abs((int) $kat->bobot) : -abs((int) $kat->bobot);

            $db = DB::connection();
            $db->beginTransaction();
            try {
                $this->kunciSiswa($db, $idSiswa);

                $skorSekarang = $this->getSkor($idSiswa);
                if ($skorSekarang === null) {
                    $db->rollBack();

                    return ['status' => false, 'message' => 'Skor terkini gagal dibaca — coba lagi sebentar'];
                }
                if ($skorSekarang + $poin < 0) {
                    $db->rollBack();

                    return ['status' => false, 'message' => 'Ditolak: skor tidak boleh di bawah 0'];
                }

                $tertulis = $db->table('poin_siswa_history')->insert([
                    'id_siswa' => $idSiswa,
                    'id_kelas' => $this->kelasAktifSiswa($idSiswa),
                    'poin' => $poin,
                    'kategori_id' => $kat->id,
                    'tipe' => $kat->jenis,
                    'keterangan' => $keterangan,
                    'ref_id' => 'manual-'.$kat->id.'-'.$idSiswa.'-'.time().'-'.substr(bin2hex(random_bytes(3)), 0, 6),
                    'created_by' => $createdBy,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                if ($tertulis === false) {
                    $db->rollBack();
                    Log::error('[PoinSiswaModel::recordManual] insert gagal siswa='.$idSiswa);

                    return ['status' => false, 'message' => 'Gagal menyimpan — tidak ada poin yang tercatat'];
                }
                $db->commit();

                $skor = $this->updateCache($idSiswa);

                return [
                    'status' => true,
                    'message' => 'Tersimpan ('.($poin > 0 ? '+' : '').$poin.' poin)',
                    'skor' => $skor,
                ];
            } catch (\Throwable $e) {
                try {
                    $db->rollBack();
                } catch (\Throwable $e2) {
                }
                throw $e;
            }
        } catch (\Throwable $e) {
            Log::error('[PoinSiswaModel::recordManual] '.$e->getMessage());

            return ['status' => false, 'message' => 'Gagal menyimpan'];
        }
    }

    /**
     * Sinkron satu baris presensi_siswa → ledger.
     *
     * Diperbarui: dulu hanya menangani ALPA/TANPA_KETERANGAN, sekarang juga
     * keterlambatan. Logikanya dipegang SiswaPoinService (batas jam memakai
     * jam_masuk + grace_period, sama dengan rekap) dan bersifat rebuild —
     * entri lama dihapus dulu, jadi aman dipanggil berulang dan ikut membatalkan
     * pengurangan yang tak lagi berlaku.
     *
     * Return: ['status'=>bool, 'skipped'=>bool, 'message'=>string]
     */
    public function syncPresensi(object $row): array
    {
        $id = (int) ($row->id ?? 0);
        if ($id <= 0) {
            return ['status' => false, 'skipped' => true, 'message' => 'ID presensi tidak valid'];
        }
        $res = (new SiswaPoinService)->recalc($id);

        return [
            'status' => (bool) $res['status'],
            'skipped' => (bool) $res['skipped'],
            'message' => (string) $res['message'],
        ];
    }

    private function seedDefaultKategori(): void
    {
        $defaults = [
            ['Terlambat masuk sekolah', 'PELANGGARAN', 5],
            ['Tidak memakai seragam lengkap/atribut', 'PELANGGARAN', 5],
            ['Tidak mengerjakan tugas/PR', 'PELANGGARAN', 5],
            ['Keluar kelas tanpa izin', 'PELANGGARAN', 10],
            ['Merokok di lingkungan sekolah', 'PELANGGARAN', 25],
            ['Berkelahi / tawuran', 'PELANGGARAN', 50],
            ['Bolos sekolah', 'PELANGGARAN', 25],
            ['Membawa HP tanpa izin', 'PELANGGARAN', 10],
            ['Juara lomba / prestasi akademik', 'PENGHARGAAN', 20],
            ['Prestasi non-akademik / ekstrakurikuler', 'PENGHARGAAN', 15],
            ['Membantu kegiatan sekolah', 'PENGHARGAAN', 10],
            ['Hafalan / prestasi tahfidz', 'PENGHARGAAN', 15],
        ];
        $now = date('Y-m-d H:i:s');
        foreach ($defaults as [$nama, $jenis, $bobot]) {
            try {
                $exists = DB::table('disiplin_kategori')
                    ->whereRaw('LOWER(nama) = ?', [strtolower($nama)])->count() > 0;
                if (! $exists) {
                    DB::table('disiplin_kategori')->insert([
                        'nama' => $nama, 'jenis' => $jenis, 'bobot' => $bobot,
                        'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            } catch (\Throwable $e) {
                // abaikan per-baris
            }
        }
    }
}
