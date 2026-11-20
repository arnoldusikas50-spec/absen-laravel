<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabel: absensi_shift_anggota
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
