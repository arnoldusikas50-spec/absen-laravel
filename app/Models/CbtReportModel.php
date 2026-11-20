<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabel: cbt_siswa
 * $table diisi tabel utama yang dipakai model tersebut (cbt_siswa).
 */
class CbtReportModel extends Model
{
    protected $table = 'cbt_siswa';

    protected $fillable = [
    ];

    public $timestamps = false;
}
