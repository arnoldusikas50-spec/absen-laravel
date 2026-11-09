<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari CI4: app/Models/GuruJadwalPribadiModel.php
 * Tabel: guru_jadwal_pribadi
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class GuruJadwalPribadiModel extends Model
{
    protected $table = 'guru_jadwal_pribadi';

    protected $fillable = [
        'id_tp',
        'id_smt',
        'id_guru',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'id_mapel',
        'id_kelas',
        'ruangan',
    ];

    public $timestamps = false;

    // TODO: tabel guru_jadwal_pribadi belum ada migrasinya
    public function initialize()
    {
        if (Schema::hasTable($this->getTable())) {
            return;
        }

        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_tp');
            $table->integer('id_smt');
            $table->integer('id_guru');
            $table->tinyInteger('hari');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->integer('id_mapel');
            $table->integer('id_kelas');
            $table->string('ruangan', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->index(['id_guru', 'id_tp', 'id_smt', 'hari']);
            $table->index(['id_kelas', 'id_tp', 'id_smt', 'hari']);
        });
    }

    // TODO: tabel guru_jadwal_pribadi belum ada migrasinya
    // TODO: tabel master_mapel belum ada migrasinya
    // TODO: tabel master_kelas belum ada migrasinya
    public function getByGuru(int $idGuru, int $idTp, int $idSmt): array
    {
        return DB::table($this->getTable().' as j')
            ->select('j.*')
            ->addSelect('m.nama_mapel')
            ->addSelect('k.nama_kelas')
            ->leftJoin('master_mapel as m', 'm.id_mapel', '=', 'j.id_mapel')
            ->leftJoin('master_kelas as k', 'k.id_kelas', '=', 'j.id_kelas')
            ->where('j.id_guru', $idGuru)
            ->where('j.id_tp', $idTp)
            ->where('j.id_smt', $idSmt)
            ->orderBy('j.hari', 'ASC')
            ->orderBy('j.jam_mulai', 'ASC')
            ->get()->all();
    }

    // TODO: tabel guru_jadwal_pribadi belum ada migrasinya
    // TODO: tabel master_mapel belum ada migrasinya
    // TODO: tabel master_kelas belum ada migrasinya
    public function getByGuruAndHari(int $idGuru, int $idTp, int $idSmt, int $hari): array
    {
        return DB::table($this->getTable().' as j')
            ->select('j.*')
            ->addSelect('m.nama_mapel')
            ->addSelect('k.nama_kelas')
            ->leftJoin('master_mapel as m', 'm.id_mapel', '=', 'j.id_mapel')
            ->leftJoin('master_kelas as k', 'k.id_kelas', '=', 'j.id_kelas')
            ->where('j.id_guru', $idGuru)
            ->where('j.id_tp', $idTp)
            ->where('j.id_smt', $idSmt)
            ->where('j.hari', $hari)
            ->orderBy('j.jam_mulai', 'ASC')
            ->get()->all();
    }

    // TODO: tabel guru_jadwal_pribadi belum ada migrasinya
    public function findConflictsForGuru(int $idGuru, int $idTp, int $idSmt, int $hari, string $mulai, string $selesai, ?int $excludeId = null): array
    {
        $builder = DB::table($this->getTable())
            ->where('id_guru', $idGuru)
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->where('hari', $hari)
            ->where('jam_mulai', '<', $selesai)
            ->where('jam_selesai', '>', $mulai);

        if ($excludeId) {
            $builder->where('id', '!=', $excludeId);
        }

        return $builder->get()->all();
    }

    // TODO: tabel guru_jadwal_pribadi belum ada migrasinya
    public function findConflictsForKelas(int $idKelas, int $idTp, int $idSmt, int $hari, string $mulai, string $selesai, ?int $excludeId = null): array
    {
        $builder = DB::table($this->getTable())
            ->where('id_kelas', $idKelas)
            ->where('id_tp', $idTp)
            ->where('id_smt', $idSmt)
            ->where('hari', $hari)
            ->where('jam_mulai', '<', $selesai)
            ->where('jam_selesai', '>', $mulai);

        if ($excludeId) {
            $builder->where('id', '!=', $excludeId);
        }

        return $builder->get()->all();
    }
}
