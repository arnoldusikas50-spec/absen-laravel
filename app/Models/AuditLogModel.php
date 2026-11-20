<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabel: audit_logs
 */
class AuditLogModel extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'table_name',
        'record_id',
        'target_user_id',
        'actor_id',
        'action',
        'old_values',
        'new_values',
        'reason',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    public $timestamps = false;

    protected $casts = [
        'id' => 'integer',
        'record_id' => 'integer',
        'target_user_id' => 'integer',
        'actor_id' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Helper to log an action
     */
    public function log($tableName, $recordId, $action, $targetUserId = null, $actorId = null, $oldValues = null, $newValues = null, $reason = null)
    {
        $request = request();
        // request() di console tidak punya user agent bermakna; tanpa
        // penjagaan ini setiap audit yang ditulis dari jalur CLI/worker
        // melempar exception yang lalu ditelan try/catch pemanggil —
        // pada CLIRequest.)
        $agent = $request->userAgent();

        $formatValues = function ($val) {
            if ($val === null) {
                return null;
            }
            if (is_array($val) || is_object($val)) {
                return json_encode($val, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            }

            return (string) $val;
        };

        $data = [
            'table_name' => $tableName,
            'record_id' => $recordId,
            'target_user_id' => $targetUserId,
            'actor_id' => $actorId ?? auth()->id(), // Fallback to current session user
            'action' => $action,
            'old_values' => $formatValues($oldValues),
            'new_values' => $formatValues($newValues),
            'reason' => $reason,
            'ip_address' => $request->ip() ?? 'cli',
            'user_agent' => $agent ? $agent : '',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // false; insertGetId() mengembalikan id baru (int) dan melempar
        // QueryException saat query gagal — pemanggil sudah membungkus
        // dengan try/catch (lihat LemburController::approveLembur).
        return static::query()->insertGetId($data);
    }
}
