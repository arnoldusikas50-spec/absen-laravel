<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari CI4: app/Models/ShiftModel.php
 * Tabel: absensi_shift
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class ShiftModel extends Model
{
    protected $table = 'absensi_shift';

    protected $fillable = [
        'nama',
        'jam_masuk',
        'jam_pulang',
        'grace_menit',
        'active',
    ];

    protected $casts = [
        'id' => 'integer',
        'grace_menit' => 'integer',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Ambil shift yang berlaku untuk user pada weekday ISO tertentu.
     */
    public function getShiftForUser(int $userId, int $dayIso): ?object
    {
        if ($userId <= 0 || ! Schema::hasTable('absensi_shift_anggota')) {
            return null;
        }

        foreach ([$dayIso, 0] as $hari) {
            $row = DB::table('absensi_shift_anggota as a')
                ->select('s.*')
                ->join('absensi_shift as s', 's.id', '=', 'a.shift_id')
                ->where('a.user_id', $userId)
                ->where('a.hari', $hari)
                ->where('s.active', 1)
                // Abaikan baris warisan yang jam-nya belum dilengkapi
                ->whereNotNull('s.jam_masuk')
                ->whereNotNull('s.jam_pulang')
                ->orderBy('s.id', 'ASC')
                ->first();
            if ($row) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Jadwal kerja efektif: shift (bila ada) lalu jadwal harian lalu setting.
     *
     * @return array{jam_masuk: string, jam_pulang: string, grace_menit: int|null, sumber: string}
     */
    public function getScheduleForUser(int $userId, int $dayIso, ?object $jadwalHarian, object $setting): array
    {
        $shift = $this->getShiftForUser($userId, $dayIso);
        if ($shift) {
            return [
                'jam_masuk' => substr((string) $shift->jam_masuk, 0, 8),
                'jam_pulang' => substr((string) $shift->jam_pulang, 0, 8),
                'grace_menit' => $shift->grace_menit !== null ? (int) $shift->grace_menit : (int) ($setting->grace_period ?? 15),
                'sumber' => 'shift:'.$shift->nama,
            ];
        }

        return [
            'jam_masuk' => substr((string) ($jadwalHarian->jam_masuk ?? $setting->jam_masuk ?? '07:30:00'), 0, 8),
            'jam_pulang' => substr((string) ($jadwalHarian->jam_pulang ?? $setting->jam_pulang ?? '15:00:00'), 0, 8),
            'grace_menit' => (int) ($setting->grace_period ?? 15),
            'sumber' => 'jadwal',
        ];
    }
}
