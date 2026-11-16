<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk log scan NFC/RFID dari perangkat Android.
 */
class NfcScanLogModel extends Model
{
    protected $table = 'nfc_scan_log';

    protected $fillable = [
        'nfc_uid',
        'tipe_user',
        'user_id',
        'reference_id',
        'nama',
        'jenis_scan',
        'tanggal',
        'jam',
        'lat',
        'lng',
        'akurasi_gps',
        'device_id',
        'ip_address',
        'berhasil',
        'pesan',
        'presensi_id',
    ];

    protected $casts = [
        'berhasil' => 'boolean',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'akurasi_gps' => 'integer',
    ];

    public $timestamps = true;

    /**
     * Catat log scan NFC.
     */
    public static function catat(array $data): self
    {
        return static::create(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }
}
