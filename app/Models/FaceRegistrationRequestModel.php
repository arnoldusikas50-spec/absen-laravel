<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabel: face_registration_requests
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
