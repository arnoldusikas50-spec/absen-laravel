<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Port Tahap 1A dari CI4: app/Models/ShiftAnggotaModel.php
 * Tabel: absensi_shift_anggota
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class ShiftAnggotaModel extends Model
{
    protected $table = 'absensi_shift_anggota';

    protected $fillable = [
        'shift_id',
        'user_id',
        'hari',
    ];

    public $timestamps = false;

    protected $casts = [
        'id' => 'integer',
        'shift_id' => 'integer',
        'user_id' => 'integer',
        'hari' => 'integer',
        'created_at' => 'datetime',
    ];

}
