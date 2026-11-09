<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Port Tahap 1A dari CI4: app/Models/RewardRedemptionModel.php
 * Tabel: reward_redemptions
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class RewardRedemptionModel extends Model
{
    protected $table = 'reward_redemptions';

    protected $fillable = [
        'user_id',
        'reward_id',
        'poin_digunakan',
        'status',
        'keterangan',
        'approved_by',
        'claimed_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'reward_id' => 'integer',
        'poin_digunakan' => 'integer',
        'approved_by' => 'integer',
        'claimed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getRiwayat($userId, $limit = 20)
    {
        return DB::table('reward_redemptions as rr')
            ->select('rr.*', 'r.nama as reward_nama', 'r.poin_dibutuhkan', 'r.nominal')
            ->join('rewards as r', 'r.id', '=', 'rr.reward_id')
            ->where('rr.user_id', $userId)
            ->orderBy('rr.created_at', 'DESC')
            ->limit($limit)
            ->get()->all();
    }

    // TODO: tabel users belum ada migrasinya
    public function getPending()
    {
        return DB::table('reward_redemptions as rr')
            ->select('rr.*', 'r.nama as reward_nama', 'r.nominal', 'r.poin_dibutuhkan as reward_poin', 'u.first_name', 'u.last_name')
            ->leftJoin('rewards as r', 'r.id', '=', 'rr.reward_id')
            ->leftJoin('users as u', 'u.id', '=', 'rr.user_id')
            ->where('rr.status', 'PENDING')
            ->orderBy('rr.created_at', 'ASC')
            ->get()->all();
    }
}
