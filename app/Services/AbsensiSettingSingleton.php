<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * absensi_setting diperlakukan singleton oleh seluruh kode, tetapi setiap
 * `spark migrate` yang berjalan ulang melewati migrasi pembuatnya dan
 * menyisipkan baris default baru. Karena itu tabel pernah berisi 3 baris
 * dengan jam masuk, hari libur, dan denda alpha yang berbeda, dan pembacaan
 * tanpa ORDER BY mengambil baris mana pun.
 *
 * Dipakai oleh migrasi dan command CLI: di instance yang tracker migrasinya
 * tidak selaras, `php artisan migrate` tidak bisa dijalankan, jadi pembersihan
 * harus bisa dipanggil sendiri.
 *
 * Port dari CodeIgniter 4 ke Laravel 13. Parameter $db dipertahankan demi
 * kompatibilitas pemanggil lama namun tidak lagi dipakai — semua akses
 * memakai DB/Schema facade. Guard kolom generated + UNIQUE index memakai
 * DB::statement() (MySQL).
 */
class AbsensiSettingSingleton
{
    private const BACKUP_TABLE = 'absensi_setting_dup_backup';
    private const GUARD_COLUMN = 'singleton_guard';
    private const GUARD_KEY    = 'uq_absensi_setting_single_row';

    /**
     * @return array{archived: list<int>, guard: string}
     */
    public static function enforce($db = null): array
    {
        $archived = [];
        $guard    = 'skip';

        if (!Schema::hasTable('absensi_setting')) {
            return ['archived' => $archived, 'guard' => 'no-table'];
        }

        $rows = DB::table('absensi_setting')->orderBy('id', 'ASC')->get()
            ->map(fn($r) => (array) $r)->all();

        $extras = array_slice($rows, 1);
        if ($extras !== []) {
            // Sekali saja: membuat tabel di dalam loop akan mencoba CREATE dua kali.
            DB::statement('CREATE TABLE IF NOT EXISTS `' . self::BACKUP_TABLE . '` LIKE `absensi_setting`');
            self::stripGuardFromBackup();
            foreach ($extras as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $archived[] = self::archiveThenDelete($row, $id);
            }
        }

        $guard = self::addGuard();

        return ['archived' => array_values(array_filter($archived)), 'guard' => $guard];
    }

    /** Kembalikan baris yang pernah diarsipkan (untuk down() / uji coba). */
    public static function restore($db = null): int
    {
        self::dropGuard();

        if (!Schema::hasTable(self::BACKUP_TABLE)) {
            return 0;
        }

        $existing = DB::table('absensi_setting')->pluck('id')->all();
        $moved    = 0;
        foreach (DB::table(self::BACKUP_TABLE)->orderBy('id', 'ASC')->get() as $rowObj) {
            $row = (array) $rowObj;
            if (!in_array($row['id'], $existing, true)) {
                unset($row[self::GUARD_COLUMN]);
                DB::table('absensi_setting')->insert($row);
                $moved++;
            }
        }

        return $moved;
    }

    private static function archiveThenDelete(array $row, int $id): int
    {
        if (!DB::table(self::BACKUP_TABLE)->where('id', $id)->exists()) {
            unset($row[self::GUARD_COLUMN]);
            DB::table(self::BACKUP_TABLE)->insert($row);
        }
        DB::table('absensi_setting')->where('id', $id)->delete();

        Log::warning('[absensi_setting] baris duplikat id ' . $id
            . ' (jam_masuk ' . ($row['jam_masuk'] ?? '?') . ', hari libur ' . ($row['hari_libur_mingguan'] ?? '?')
            . ', denda alpha ' . ($row['denda_alpha_per_hari'] ?? '?') . ') diarsipkan ke ' . self::BACKUP_TABLE);

        return $id;
    }

    /**
     * Kolom generated konstan 1 + UNIQUE index: MySQL menolak baris kedua dari
     * jalur tulis mana pun. Dilewati bila server terlalu tua agar proses
     * pembersihan tidak gagal total.
     */
    private static function addGuard(): string
    {
        if (Schema::hasColumn('absensi_setting', self::GUARD_COLUMN)) {
            return 'already';
        }

        try {
            DB::statement(
                'ALTER TABLE `absensi_setting`
                    ADD COLUMN `' . self::GUARD_COLUMN . '` TINYINT GENERATED ALWAYS AS (1) STORED,
                    ADD UNIQUE KEY `' . self::GUARD_KEY . '` (`' . self::GUARD_COLUMN . '`)'
            );
            return 'installed';
        } catch (\Throwable $e) {
            Log::error('[absensi_setting] guard single-row tidak terpasang: ' . $e->getMessage());
            return 'unsupported';
        }
    }

    /**
     * CREATE TABLE ... LIKE menyalin kolom generated beserta UNIQUE index-nya,
     * jadi tabel arsip juga hanya mau satu baris. Unik di tabel sumber bahkan
     * membuat arsip baris kedua gagal, jadi guard dilepas dari salinan.
     */
    private static function stripGuardFromBackup(): void
    {
        foreach ([
            'ALTER TABLE `' . self::BACKUP_TABLE . '` DROP INDEX `' . self::GUARD_KEY . '`',
            'ALTER TABLE `' . self::BACKUP_TABLE . '` DROP COLUMN `' . self::GUARD_COLUMN . '`',
        ] as $sql) {
            try {
                DB::statement($sql);
            } catch (\Throwable $e) {
                // Guard memang tidak ikut tersalin (mis. tabel arsip dibuat
                // sebelum guard ada) — tidak ada yang perlu dilepas.
            }
        }
    }

    private static function dropGuard(): void
    {
        if (!Schema::hasTable('absensi_setting') || !Schema::hasColumn('absensi_setting', self::GUARD_COLUMN)) {
            return;
        }

        try {
            DB::statement(
                'ALTER TABLE `absensi_setting`
                    DROP INDEX `' . self::GUARD_KEY . '`,
                    DROP COLUMN `' . self::GUARD_COLUMN . '`'
            );
        } catch (\Throwable $e) {
            Log::error('[absensi_setting] guard single-row gagal dilepas: ' . $e->getMessage());
        }
    }
}
