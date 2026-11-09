<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Port Tahap 1A dari CI4: app/Models/CbtReportModel.php
 * Tabel: cbt_siswa
 * Catatan: di CI4 model ini tidak mendeklarasikan $table (query-service);
 * $table diisi tabel utama yang dipakai model tersebut (cbt_siswa).
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class CbtReportModel extends Model
{
    protected $table = 'cbt_siswa';

    protected $fillable = [
    ];

    public $timestamps = false;

}
