<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari CI4: app/Models/AttendanceLocationModel.php
 * Tabel: absensi_lokasi
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class AttendanceLocationModel extends Model
{
    protected $table = 'absensi_lokasi';

    protected $fillable = [
        'nama_lokasi',
        'lat',
        'lng',
        'radius_meter',
        'keterangan',
        'is_utama',
    ];

    public $timestamps = false;

    protected $casts = [
        'id' => 'integer',
        'lat' => 'float',
        'lng' => 'float',
        'radius_meter' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_utama' => 'boolean',
    ];

    /**
     * Lokasi utama yang ditandai (kantor pusat).
     * Null bila belum ada yang ditandai atau tabelnya belum punya kolom.
     */
    public function getUtama(): ?object
    {
        try {
            if (! Schema::hasTable($this->getTable())) {
                return null;
            }
            if (! Schema::hasColumn($this->getTable(), 'is_utama')) {
                return null;
            }

            return static::query()->where('is_utama', 1)->orderBy('id', 'ASC')->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Jadikan satu lokasi sebagai lokasi utama.
     *
     * Hanya satu lokasi boleh berflag is_utama=1. Bila $id null/0, semua
     * flag dilepas (dipakai saat operator membatalkan penandaan).
     *
     * @return ?object lokasi utama setelah perubahan
     */
    public function setUtama($id): ?object
    {
        try {
            if (! Schema::hasColumn($this->getTable(), 'is_utama')) {
                return null;
            }

            $table = $this->getTable();
            DB::transaction(function () use ($table, &$id) {
                DB::table($table)->update(['is_utama' => 0]);
                $id = (int) $id;
                if ($id > 0) {
                    DB::table($table)->where('id', $id)->update(['is_utama' => 1]);
                }
            });

            return $id > 0 ? $this->getUtama() : null;
        } catch (\Throwable $e) {
            Log::error('setUtama gagal: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Pastikan tepat satu lokasi utama ada (dipanggil saat menyimpan lokasi).
     * Bila belum ada yang ditandai, lokasi yang sedang disimpan menjadi utama
     * agar absen manual selalu punya acuan koordinat.
     */
    public function ensureAdaUtama(): void
    {
        if ($this->getUtama() !== null) {
            return;
        }
        try {
            $first = static::query()->orderBy('id', 'ASC')->first();
            if ($first) {
                $this->setUtama((int) $first->id);
            }
        } catch (\Throwable $e) {
            Log::error('ensureAdaUtama gagal: '.$e->getMessage());
        }
    }
}
