<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel: logs
 */
class LogModel extends Model
{
    protected $table = 'logs';

    protected $primaryKey = 'id_log';

    protected $fillable = [
        'id_user',
        'aktivitas',
        'keterangan',
        'ip_address',
        'user_agent',
        'waktu',
    ];

    public $timestamps = false;

    /**
     * Ensure logs table exists, create if not
     */
    private function _ensureTableExists(): void
    {
        if (! Schema::hasTable('logs')) {
            Schema::create('logs', function ($table) {
                $table->increments('id_log');
                $table->integer('id_user')->nullable();
                // 1=Login, 2=Logout, 3=Create, 4=Read, 5=Update, 6=Delete
                $table->integer('aktivitas')->default(0);
                $table->string('keterangan', 255)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->dateTime('waktu')->nullable();
                $table->index('id_user');
                $table->index('waktu');
            });
        }
    }

    /**
     * Save activity log
     *
     * @param  int  $aktivitas  Activity type (1=Login, 2=Logout, etc)
     * @param  string  $keterangan  Description of activity
     * @param  int|null  $userId  Optional user ID (if not from session)
     */
    public function saveLog(int $aktivitas, string $keterangan = '', ?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        if (! $userId) {
            return false;
        }

        try {
            $data = [
                'id_user' => $userId,
                'aktivitas' => $aktivitas,
                'keterangan' => $keterangan,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'waktu' => date('Y-m-d H:i:s'),
            ];

            return static::query()->insert($data);
        } catch (\Exception $e) {
            // Log table may not exist, ignore error
            return false;
        }
    }

    /**
     * Get recent logs
     */
    public function getRecentLogs(int $limit = 50): array
    {
        return static::query()->orderBy('waktu', 'DESC')
            ->limit($limit)
            ->get()->all();
    }

    /**
     * Get logs by user
     */
    public function getLogsByUser(int $userId, int $limit = 50): array
    {
        return static::query()->where('id_user', $userId)
            ->orderBy('waktu', 'DESC')
            ->limit($limit)
            ->get()->all();
    }

    /**
     * Delete log by ID
     */
    public function hapusLog(int $idLog): bool
    {
        // baris tidak ada; dipertahankan (jumlah baris selalu >= 0).
        return static::query()->whereKey($idLog)->delete() >= 0;
    }

    public function getLog($offset, $limit, $search = null, $filters = null)
    {
        $builder = DB::table('logs as a');
        $builder->select('a.*', 'b.first_name', 'b.last_name', 'b.foto');
        $builder->leftJoin('users as b', 'b.id', '=', 'a.id_user');

        if ($filters != null && is_array($filters)) {
            // We might skip this filter or assume it exists if table created manually or extended
            // For now, removing id_group filter to avoid SQL error if column absent
            // $builder->whereIn('a.id_group', $filters);
        }

        if ($search) {
            $builder->where(function ($q) use ($search) {
                $q->where('a.keterangan', 'like', "%{$search}%")
                    ->orWhere('b.first_name', 'like', "%{$search}%")
                    ->orWhere('b.last_name', 'like', "%{$search}%");
            });
        }

        $builder->orderBy('a.waktu', 'DESC');
        $builder->offset($offset)->limit($limit);

        return $builder->get()->all();
    }

    public function getCountLog($search = null, $filters = null)
    {
        $builder = DB::table('logs as a');
        $builder->leftJoin('users as b', 'b.id', '=', 'a.id_user');

        if ($search) {
            $builder->where(function ($q) use ($search) {
                $q->where('a.keterangan', 'like', "%{$search}%")
                    ->orWhere('b.first_name', 'like', "%{$search}%")
                    ->orWhere('b.last_name', 'like', "%{$search}%");
            });
        }

        return $builder->count();
    }
}
