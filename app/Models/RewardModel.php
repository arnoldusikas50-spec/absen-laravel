<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabel: rewards
 */
class RewardModel extends Model
{
    protected $table = 'rewards';

    protected $fillable = [
        'nama',
        'deskripsi',
        'poin_dibutuhkan',
        'nominal',
        'stok',
        'gambar',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'poin_dibutuhkan' => 'integer',
        'stok' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'nominal' => 'decimal:2',
    ];
}
