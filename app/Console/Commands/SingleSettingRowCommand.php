<?php

namespace App\Console\Commands;

use App\Services\AbsensiSettingSingleton;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Porting CI4 App\Commands\SingleSettingRow (php spark absensi:single-setting).
 *
 * Jalankan pembersihan baris duplikat absensi_setting tanpa `migrate`.
 * Diperlukan karena pada beberapa instance tracker migrasi tidak selaras
 * dengan skema (schema dibuat lewat import SQL), sehingga `migrate`
 * menjalankan ulang seluruh migrasi dan berhenti di tengah.
 *
 *   php artisan absensi:single-setting [--restore]
 *
 * CATATAN PORTING: CI4 memanggil AbsensiSettingSingleton::enforce($db) /
 * ::restore($db) dengan objek koneksi CI4. Di sini diteruskan koneksi
 * Laravel (Illuminate\Database\Connection) sebagai padanannya; bila
 * service hasil porting memakai DB facade secara internal tanpa parameter,
 * panggilannya perlu disesuaikan oleh pekerja service.
 */
class SingleSettingRowCommand extends Command
{
    protected $signature = 'absensi:single-setting {--restore}';

    protected $description = 'Arsipkan & hapus baris duplikat absensi_setting, lalu pasang guard single-row.';

    public function handle(): int
    {
        if (! Schema::hasTable('absensi_setting')) {
            $this->error('Tabel absensi_setting tidak ada.');

            return self::FAILURE;
        }

        $before = DB::table('absensi_setting')->orderBy('id', 'ASC')->get();
        $this->line('Baris sebelum: '.count($before));
        foreach ($before as $r) {
            $this->line(sprintf(
                '  id=%-3s jam_masuk=%-9s hari_libur=%-8s denda_alpha=%-7s updated=%s',
                $r->id ?? '?',
                $r->jam_masuk ?? '?',
                $r->hari_libur_mingguan ?? '?',
                $r->denda_alpha_per_hari ?? '?',
                $r->updated_at ?? '?'
            ));
        }

        if ($this->option('restore')) {
            $moved = AbsensiSettingSingleton::restore(DB::connection());
            $this->warn("Baris dipulihkan dari arsip: {$moved}");

            return self::SUCCESS;
        }

        $result = AbsensiSettingSingleton::enforce(DB::connection());

        $this->line(
            $result['archived'] === []
                ? 'Tidak ada baris duplikat.'
                : 'Baris duplikat diarsipkan & dihapus: id '.implode(', ', $result['archived']),
            $result['archived'] === [] ? 'info' : 'comment'
        );
        $this->line([
            'installed' => 'Guard single-row terpasang (baris kedua akan ditolak MySQL).',
            'already' => 'Guard single-row sudah terpasang.',
            'unsupported' => 'Guard tidak terpasang — server tidak mendukung generated column.',
            'no-table' => 'Tabel tidak ada.',
            'skip' => 'Guard dilewati.',
        ][$result['guard']] ?? $result['guard'], $result['guard'] === 'unsupported' ? 'error' : 'info');

        $after = DB::table('absensi_setting')->count();
        $this->line("Baris sesudah: {$after}");

        return self::SUCCESS;
    }
}
