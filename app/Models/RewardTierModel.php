<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabel: reward_tiers
 */
class RewardTierModel extends Model
{
    protected $table = 'reward_tiers';

    protected $fillable = [
        'nama_tier',
        'poin_min',
        'poin_max',
        'badge',
        'warna',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'poin_min' => 'integer',
        'poin_max' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getTierByPoin($poin)
    {
        $poin = (int) $poin;

        // poin_max = 0 berarti tak terhingga; pilih tier dengan poin_min terbesar yang cocok
        return static::query()
            ->where(function ($query) use ($poin) {
                $query->where('poin_min', '<=', $poin)
                    ->where(function ($query) use ($poin) {
                        $query->where('poin_max', '>=', $poin)
                            ->orWhere('poin_max', 0);
                    });
            })
            ->orderBy('poin_min', 'DESC')
            ->first();
    }
}
