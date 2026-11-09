<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Port Tahap 1A dari CI4: app/Models/FaceRegistrationRequestModel.php
 * Tabel: face_registration_requests
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class FaceRegistrationRequestModel extends Model
{
    protected $table = 'face_registration_requests';

    protected $fillable = [
        'user_id',
        'face_descriptor',
        'status',
        'admin_id',
        'keterangan',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'admin_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

}
