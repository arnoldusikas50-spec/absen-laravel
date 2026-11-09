<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari CI4: app/Models/JabatanGuruModel.php
 * Tabel: jabatan_guru
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class JabatanGuruModel extends Model
{
    protected $table = 'jabatan_guru';

    protected $primaryKey = 'id_jabatan_guru';

    protected $fillable = [
        'id_jabatan_guru',
        'id_guru',
        'id_jabatan',
        'id_kelas',
        'mapel_kelas',
        'ekstra_kelas',
        'id_tp',
        'id_smt',
    ];

    public $timestamps = false;

    public static function makeKey(int $idGuru, int $idTp, int $idSmt): string
    {
        return $idGuru.'-'.$idTp.'-'.$idSmt;
    }

    /** Baris jabatan milik guru pada satu periode, apa pun format kuncinya. */
    // TODO: tabel jabatan_guru belum ada migrasinya
    public function findForGuru(int $idGuru, int $idTp, int $idSmt): ?object
    {
        return DB::table($this->getTable())
            ->where('id_guru', $idGuru)
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->orderBy('id_jabatan_guru', 'ASC')
            ->first();
    }

    /**
     * Simpan jabatan guru pada satu periode tanpa membuat baris ganda.
     * Baris lama dicari lewat guru + periode sehingga data dengan kunci
     * format lama tetap ikut diperbarui.
     */
    // TODO: tabel jabatan_guru belum ada migrasinya
    public function upsert(int $idGuru, int $idTp, int $idSmt, array $data): bool
    {
        $existing = $this->findForGuru($idGuru, $idTp, $idSmt);

        unset($data['id_jabatan_guru'], $data['id_guru'], $data['id_tp'], $data['id_smt']);

        if ($existing) {
            return DB::table($this->getTable())
                ->where('id_jabatan_guru', $existing->id_jabatan_guru)
                ->update($data) >= 0;
        }

        $data = array_merge([
            'id_jabatan' => 0,
            'id_kelas' => 0,
            'mapel_kelas' => '[]',
            'ekstra_kelas' => '[]',
        ], $data);
        $data['id_jabatan_guru'] = self::makeKey($idGuru, $idTp, $idSmt);
        $data['id_guru'] = $idGuru;
        $data['id_tp'] = $idTp;
        $data['id_smt'] = $idSmt;

        return DB::table($this->getTable())->insert($data);
    }

    /** Lepas status wali dari semua guru lain pada satu kelas. */
    // TODO: tabel jabatan_guru belum ada migrasinya
    public function releaseWaliKelas(int $idTp, int $idSmt, int $idKelas, ?int $keepGuru = null): void
    {
        if ($idKelas <= 0) {
            return;
        }

        // Baris wali resmi (level 4) dilepas sekaligus jabatannya.
        $builder = DB::table($this->getTable())
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->where('id_kelas', $idKelas)
            ->where('id_jabatan', '4');
        if ($keepGuru !== null) {
            $builder->where('id_guru', '!=', $keepGuru);
        }
        $builder->update(['id_kelas' => 0, 'id_jabatan' => '']);

        // Data lama bisa menyimpan id_kelas tanpa level 4; lepaskan kelasnya saja.
        $legacy = DB::table($this->getTable())
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->where('id_kelas', $idKelas)
            ->where('id_jabatan', '!=', '4');
        if ($keepGuru !== null) {
            $legacy->where('id_guru', '!=', $keepGuru);
        }
        $legacy->update(['id_kelas' => 0]);
    }

    /**
     * Samakan master_kelas.guru_id dengan jabatan wali pada periode aktif.
     * $idKelas = 0 berarti guru ini tidak lagi menjadi wali kelas mana pun.
     */
    // TODO: tabel master_kelas belum ada migrasinya
    public function syncWaliToMasterKelas(int $idGuru, int $idTp, int $idSmt, int $idKelas): void
    {
        if (! Schema::hasTable('master_kelas') || ! Schema::hasColumn('master_kelas', 'guru_id')) {
            return;
        }

        DB::table('master_kelas')
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->where('guru_id', $idGuru)
            ->update(['guru_id' => 0]);

        if ($idKelas > 0) {
            DB::table('master_kelas')
                ->where('id_kelas', $idKelas)
                ->where('id_tp', $idTp)
                ->where('id_smt', $idSmt)
                ->update(['guru_id' => $idGuru]);
        }
    }
}
