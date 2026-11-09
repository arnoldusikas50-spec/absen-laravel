<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Port Tahap 1A dari CI4: app/Models/CbtModel.php
 * Tabel: cbt_jadwal
 * Catatan: di CI4 model ini tidak mendeklarasikan $table (query-service);
 * $table diisi tabel utama yang dipakai model tersebut (cbt_jadwal).
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class CbtModel extends Model
{
    protected $table = 'cbt_jadwal';

    protected $fillable = [
    ];

    public $timestamps = false;

}
