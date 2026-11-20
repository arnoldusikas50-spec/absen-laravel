<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabel: cbt_siswa
 */
class CbtSiswaModel extends Model
{
    protected $table = 'cbt_siswa';

    protected $primaryKey = 'id_cbt_siswa';

    protected $fillable = [
        'id_siswa',
        'id_jadwal',
        'id_bank',
        'mulai',
        'selesai',
        'lama_ujian',
        'status',
        'reset_izin',
        'reset_waktu',
        'ip_address',
        'user_agent',
        'device_type',
        'queue_number',
    ];

    public $timestamps = false;
}
