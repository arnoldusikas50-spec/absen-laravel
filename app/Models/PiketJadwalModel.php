<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel: piket_jadwal
 */
class PiketJadwalModel extends Model
{
    protected $table = 'piket_jadwal';

    protected $fillable = [
        'id_tp',
        'id_smt',
        'hari',
        'id_guru',
        'keterangan',
    ];

    public $timestamps = false;

    public function initialize()
    {
        if (! Schema::hasTable('piket_jadwal')) {
            Schema::create('piket_jadwal', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('id_tp');
                $table->integer('id_smt');
                $table->string('hari', 20);
                $table->integer('id_guru');
                $table->text('keterangan')->nullable();
                // CURRENT_TIMESTAMP untuk updated_at); useCurrent() hanya
                // mereproduksi DEFAULT-nya.
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->useCurrent();
                $table->index(['id_tp', 'id_smt', 'hari']);
            });
        }
    }

    public function getJadwalByTpSmt($id_tp, $id_smt)
    {
        return DB::table('piket_jadwal as pj')
            ->select('pj.*', 'g.nama_guru', 'g.nip')
            ->join('master_guru as g', 'g.id_guru', '=', 'pj.id_guru')
            ->where('pj.id_tp', $id_tp)
            ->where('pj.id_smt', $id_smt)
            ->orderByRaw('FIELD(pj.hari, "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu", "Minggu")')
            ->get()->all();
    }

    public function getPiketHariIni($hari, $id_tp, $id_smt)
    {
        return DB::table('piket_jadwal as pj')
            ->select('pj.*', 'g.nama_guru', 'g.nip', 'g.id_user')
            ->join('master_guru as g', 'g.id_guru', '=', 'pj.id_guru')
            ->where('pj.id_tp', $id_tp)
            ->where('pj.id_smt', $id_smt)
            ->where('pj.hari', $hari)
            ->get()->all();
    }

    /**
     * Jadwal piket per guru (hari-hari tugas guru yang login).
     * Dipakai frontend presensi /guru/piket untuk "Jadwal Saya".
     */
    public function getJadwalByGuru($id_guru, $id_tp, $id_smt)
    {
        return DB::table('piket_jadwal as pj')
            ->select('pj.hari', 'pj.keterangan')
            ->where('pj.id_guru', $id_guru)
            ->where('pj.id_tp', $id_tp)
            ->where('pj.id_smt', $id_smt)
            ->orderByRaw('FIELD(pj.hari, "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu", "Minggu")')
            ->get()->all();
    }

    /**
     * Jadwal piket seminggu dikelompokkan per hari: ['Senin' => [...], ...].
     * Dipakai frontend presensi /guru/piket untuk "Jadwal Mingguan".
     */
    public function getJadwalMingguan($id_tp, $id_smt)
    {
        $rows = $this->getJadwalByTpSmt($id_tp, $id_smt);
        $order = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $grouped = array_fill_keys($order, []);
        foreach ($rows as $r) {
            $hari = $r->hari ?? '';
            if (! isset($grouped[$hari])) {
                $grouped[$hari] = [];
            }
            $grouped[$hari][] = $r;
        }

        return $grouped;
    }
}
