<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabel: cbt_jadwal
 * $table diisi tabel utama yang dipakai model tersebut (cbt_jadwal).
 */
class CbtModel extends Model
{
    protected $table = 'cbt_jadwal';

    protected $fillable = [
    ];

    public $timestamps = false;
}
