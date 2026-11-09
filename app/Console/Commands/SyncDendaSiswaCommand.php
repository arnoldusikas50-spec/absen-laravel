<?php

namespace App\Console\Commands;

use App\Services\SiswaDendaService;
use Illuminate\Console\Command;

/**
 * Porting CI4 App\Commands\SyncDendaSiswa (php spark denda:sync-siswa).
 *
 * Hitung ulang denda siswa satu bulan.
 *
 * Tanpa argumen bulan berjalan yang dikerjakan. Nominal hanya bisa tersimpan
 * pada baris presensi yang ada; alpha tanpa baris tetap muncul di kolom Denda
 * rekap (dihitung saat rekap dibaca) dan dilaporkan di sini supaya selisihnya
 * tidak disangka bug. Command ini manual (tidak ada jadwal cron-nya di
 * CRONTAB.MD — jangan dikacaukan dengan /cron/poin-siswa 19:30 yang
 * menghitung ulang POIN siswa, bukan denda).
 */
class SyncDendaSiswaCommand extends Command
{
    protected $signature = 'denda:sync-siswa {bulan?} {tahun?}';

    protected $description = 'Hitung ulang denda siswa satu bulan (default: bulan berjalan)';

    public function handle(): int
    {
        $bulan = $this->argument('bulan') !== null ? (int) $this->argument('bulan') : null;
        $tahun = $this->argument('tahun') !== null ? (int) $this->argument('tahun') : null;

        $svc = new SiswaDendaService();
        $hasil = $svc->recalcBulanBerjalan($bulan, $tahun);

        $this->info($hasil['message']);
        $this->line(sprintf(
            'baris: periksa=%d diubah=%d tertagih=%d | alpha tanpa baris=%d | total baris=Rp %s | total rekap=Rp %s',
            $hasil['teriksa'],
            $hasil['diubah'],
            $hasil['tertagih'],
            $hasil['tanpa_baris'],
            number_format($hasil['total'], 0, ',', '.'),
            number_format($hasil['total_rekap'], 0, ',', '.')
        ));
        $this->line(sprintf(
            'tarif: alpha Rp %s/hari · bolos Rp %s/hari · plafon %s/siswa/bulan · siswa dinilai=%d',
            number_format($svc->tarif('alpha'), 0, ',', '.'),
            number_format($svc->tarif('bolos'), 0, ',', '.'),
            $svc->capBulan() > 0 ? 'Rp '.number_format($svc->capBulan(), 0, ',', '.') : 'tanpa batas',
            $hasil['siswa']
        ));

        if ($svc->tarif('alpha') <= 0 && $svc->tarif('bolos') <= 0) {
            $this->warn(
                'Semua tarif 0: fitur denda siswa masih mati, jadi tidak ada nominal yang ditulis maupun tertagih.'
            );
        }

        return self::SUCCESS;
    }
}
