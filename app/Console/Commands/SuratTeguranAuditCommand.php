<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Porting CI4 App\Commands\SuratTeguranAudit (php spark teguran:audit).
 *
 * Audit & perbaikan data surat teguran yang rusak oleh bug lama.
 *
 * Dua kerusakan berasal dari logika penerbitan yang salah dan sudah
 * Produksi. Perintah ini memperbaiki yang bisa dibuktikan dari data yang
 * ada, dan MELAPORKAN yang tidak — menebak dokumen legal lebih berbahaya
 * daripada membiarkannya tidak terlacak.
 *
 * Yang diperbaiki (aman, hanya menyelaraskan angka nonsensical):
 *   - jumlah_alpha < level. PDF menampilkan "Alpha N hari" berdampingan
 *     dengan "TINGKAT III (10 Hari)"; kalau N lebih kecil dari ambangnya,
 *     suratnya kontradiktif dengan dirinya sendiri. Diperbaiki ke ambang.
 *
 * Yang dilaporkan, TIDAK diubah otomatis:
 *   - id_guru = 0 / tidak ada di master_guru. Surat tidak bisa
 *     diatribusikan ke guru mana pun tanpa menebak. Barisnya tidak
 *     dihapus — arsip sekolah tidak boleh hilang tanpa keputusan manusia.
 *
 * Jalankan dulu tanpa --apply untuk melihat apa yang akan berubah.
 */
class SuratTeguranAuditCommand extends Command
{
    protected $signature = 'teguran:audit {--apply}';

    protected $description = 'Audit dan perbaiki data surat teguran (jumlah_alpha rusak, surat yatim).';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        if (! Schema::hasTable('absensi_surat_teguran')) {
            $this->error('Tabel absensi_surat_teguran tidak ada — jalankan php artisan migrate.');

            return self::FAILURE;
        }

        $this->line(
            $apply
                ? 'Mode: TERAPKAN'
                : 'Mode: LAPORAN (gunakan --apply untuk mengubah data)',
            $apply ? 'info' : 'comment'
        );
        $this->newLine();

        $diperbaiki = $this->perbaikiSnapshot($apply);
        $this->laporanYatim();

        $this->newLine();
        if (! $apply) {
            $this->line('Tidak ada data yang diubah. Jalankan ulang dengan --apply untuk menerapkan.');

            return self::SUCCESS;
        }

        $this->info('Snapshot jumlah_alpha diperbaiki: '.$diperbaiki.' baris');

        return self::SUCCESS;
    }

    /**
     * jumlah_alpha tidak boleh di bawah ambang surat.
     *
     * Sources of truth: level di baris itu sendiri. Nilai aslinya sudah
     * hilang (0 = kolom tidak diisi saat surat terbit lewat jalur check-in),
     * jadi yang bisa dilakukan hanya menyelaraskan ke batas yang pasti
     * benar — bukan menebak angka alpha sebenarnya.
     */
    private function perbaikiSnapshot(bool $apply): int
    {
        $rows = DB::table('absensi_surat_teguran')
            ->select('id', 'id_guru', 'bulan', 'tahun', 'level', 'jumlah_alpha')
            ->whereColumn('jumlah_alpha', '<', 'level')
            ->get();

        if ($rows->isEmpty()) {
            $this->line('Snapshot jumlah_alpha: semua baris sudah >= ambang surat.');

            return 0;
        }

        $n = 0;
        foreach ($rows as $row) {
            $level = (int) $row->level;
            $alpha = (int) ($row->jumlah_alpha ?? 0);
            $label = sprintf(
                'id=%d guru=%d %d/%d level=%d alpha=%d',
                (int) $row->id,
                (int) $row->id_guru,
                (int) $row->bulan,
                (int) $row->tahun,
                $level,
                $alpha
            );

            if (! $apply) {
                $this->line('  akan diselaraskan ke '.$level.': '.$label);
                $n++;
                continue;
            }

            DB::table('absensi_surat_teguran')
                ->where('id', $row->id)
                ->update(['jumlah_alpha' => $level]);

            $this->line('  diperbaiki: '.$label.' -> alpha '.$level);
            $n++;
        }

        return $n;
    }

    /**
     * Surat tanpa guru yang bisa ditelusuri.
     *
     * Hanya dilaporkan. Menghapus atau menebaknya sama-sama merusak: hapus
     * menghilangkan arsip, tebak bisa menempelkan surat orang yang keliru
     * ke kepala orang lain.
     */
    private function laporanYatim(): void
    {
        $rows = DB::table('absensi_surat_teguran as a')
            ->select('a.id', 'a.id_guru', 'a.bulan', 'a.tahun', 'a.level', 'a.status', 'a.nomor_urut')
            ->leftJoin('master_guru as g', 'g.id_guru', '=', 'a.id_guru')
            ->whereNull('g.id_guru')
            ->orderBy('a.id', 'ASC')
            ->get();

        $this->newLine();
        if ($rows->isEmpty()) {
            $this->line('Surat yatim: tidak ada.');

            return;
        }

        $this->warn(
            sprintf(
                '%d surat tidak bisa ditelusuri ke guru (id_guru tidak ada di master_guru):',
                $rows->count()
            )
        );
        foreach ($rows as $row) {
            $this->line(sprintf(
                '  id=%d id_guru=%d %d/%d level=%d status=%s no=%d',
                (int) $row->id,
                (int) $row->id_guru,
                (int) $row->bulan,
                (int) $row->tahun,
                (int) $row->level,
                (string) $row->status,
                (int) $row->nomor_urut
            ));
        }
        $this->line('');
        $this->line('  Tidak diubah otomatis. Surat ini tidak muncul di daftar admin dan');
        $this->line('  tidak bisa diunduh. Perbaiki master_guru guru terkait, atau hapus');
        $this->line('  manual lewat SQL kalau memang tidak ada pemiliknya.');
        $this->line('  Sumber: id_guru tidak ada di tabel master_guru (INNER JOIN di daftar admin).');
    }
}
