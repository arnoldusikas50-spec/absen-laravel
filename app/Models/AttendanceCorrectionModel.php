<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Port Tahap 1A dari CI4: app/Models/AttendanceCorrectionModel.php
 * Tabel: absensi_koreksi
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class AttendanceCorrectionModel extends Model
{
    protected $table = 'absensi_koreksi';

    protected $fillable = [
        'attendance_id',
        'user_id',
        'field_name',
        'old_value',
        'new_value',
        'reason',
        'status',
        'processed_by',
        'processed_at',
        'admin_note',
    ];

    protected $casts = [
        'id' => 'integer',
        'attendance_id' => 'integer',
        'user_id' => 'integer',
        'processed_by' => 'integer',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get pending corrections with user and attendance info.
     * LEFT JOIN agar baris tetap terlihat walau user/guru/absensi terkait
     * sudah dihapus (kasus tsb otomatis ditolak saat diproses).
     */
    // TODO: tabel master_guru belum ada migrasinya
    // TODO: tabel users belum ada migrasinya
    public function getPendingWithDetails()
    {
        return DB::table('absensi_koreksi')
            ->selectRaw("absensi_koreksi.*,
                             COALESCE(mg.nama_guru, TRIM(CONCAT_WS(' ', u.first_name, u.last_name)), u.username) as nama_guru,
                             mg.nip, u.username,
                             a.tgl, a.jam_masuk, a.jam_pulang, a.status_masuk")
            ->leftJoin('users as u', 'u.id', '=', 'absensi_koreksi.user_id')
            ->leftJoin('master_guru as mg', 'mg.id_user', '=', 'u.id')
            ->leftJoin('absensi_harian as a', 'a.id', '=', 'absensi_koreksi.attendance_id')
            ->where('absensi_koreksi.status', 'pending')
            ->orderBy('absensi_koreksi.created_at', 'DESC')
            ->get()->all();
    }

    /**
     * Riwayat koreksi yang sudah diproses (approved/rejected) + auto-rejected sistem.
     */
    // TODO: tabel master_guru belum ada migrasinya
    // TODO: tabel users belum ada migrasinya
    public function getHistoryWithDetails($limit = 100)
    {
        return DB::table('absensi_koreksi')
            ->selectRaw("absensi_koreksi.*,
                             COALESCE(mg.nama_guru, TRIM(CONCAT_WS(' ', u.first_name, u.last_name)), u.username) as nama_guru,
                             mg.nip, u.username,
                             a.tgl, a.jam_masuk, a.jam_pulang, a.status_masuk,
                             CONCAT_WS(' ', adm.first_name, adm.last_name) as diproses_oleh")
            ->leftJoin('users as u', 'u.id', '=', 'absensi_koreksi.user_id')
            ->leftJoin('master_guru as mg', 'mg.id_user', '=', 'u.id')
            ->leftJoin('absensi_harian as a', 'a.id', '=', 'absensi_koreksi.attendance_id')
            ->leftJoin('users as adm', 'adm.id', '=', 'absensi_koreksi.processed_by')
            ->whereIn('absensi_koreksi.status', ['approved', 'rejected'])
            ->orderBy('absensi_koreksi.processed_at', 'DESC')
            ->limit($limit)
            ->get()->all();
    }
}
