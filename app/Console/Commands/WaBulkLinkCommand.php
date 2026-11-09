<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Porting CI4 App\Commands\WaBulkLink (php spark wa:bulk).
 *
 * Isi kolom WhatsApp notifikasi untuk sekumpulan guru sekaligus.
 *
 *   php artisan wa:bulk --nomor=6285104899117 [--apply]
 *   php artisan wa:bulk 6285104899117 --apply   (bentuk positional juga diterima)
 *
 * Ditulis lewat UserNotifikasiModel supaya normalisasi nomor dan
 * penanganan upsert-nya sama dengan jalur yang dipakai form Profil —
 * bukan UPDATE mentah yang bisa melewati validasi.
 *
 * --apply untuk menulis; tanpa --apply hanya dry-run (default).
 */
class WaBulkLinkCommand extends Command
{
    protected $signature = 'wa:bulk {nomor?} {--nomor=} {--apply}';

    protected $description = 'Isi wa_number untuk semua guru (default dry-run, --apply untuk menulis).';

    public function handle(): int
    {
        $nomorArg = (string) ($this->option('nomor') ?? '');
        if ($nomorArg === '') {
            $nomorArg = (string) ($this->argument('nomor') ?? '');
        }

        $apply = (bool) $this->option('apply');

        // Validasi lewat pustaka yang sama dengan form, supaya CLI dan UI
        // tidak bisa menerima format berbeda.
        try {
            $nomor = \App\Libraries\WhatsAppLink::normalizeNumber($nomorArg);
        } catch (\InvalidArgumentException $e) {
            $this->error('Nomor ditolak: '.$e->getMessage());
            $this->line('Contoh benar: --nomor=6285104899117');

            return self::FAILURE;
        }
        if ($nomor === '') {
            $this->error('--nomor wajib diisi.');
            $this->error('Hint: pakai tanda "=" — --nomor=6285104899117');

            return self::FAILURE;
        }

        // Guru = user aktif di group 2 yang tertaut ke master_guru.
        $guru = DB::table('master_guru as g')
            ->select('g.id_guru', 'g.nama_guru', 'g.id_user', 'g.no_hp')
            ->join('users as u', 'u.id', '=', 'g.id_user')
            ->join('users_groups as ug', 'ug.user_id', '=', 'u.id')
            ->where('ug.group_id', 2)
            ->where('u.active', 1)
            ->orderBy('g.id_guru', 'ASC')
            ->get();

        $this->line('Nomor tujuan : '.$nomor.'  (OpenWA: '.$nomor.'@c.us)');
        $this->line('Mode         : '.($apply ? 'APPLY (menulis ke database)' : 'DRY-RUN (tidak menulis)'));
        $this->line('Guru ditangani: '.count($guru));
        $this->line('');

        $model = new \App\Models\UserNotifikasiModel();
        $ok = 0;
        $gagal = 0;
        $tabel = [];

        foreach ($guru as $g) {
            $userId = (int) $g->id_user;
            if ($userId <= 0) {
                $this->error('  guru id='.$g->id_guru.' tanpa id_user, dilewati');
                $gagal++;
                continue;
            }

            // verifying = true: nomor sudah disetujui admin lewat CLI ini,
            // jadi tidak perlu menunggu konfirmasi halaman tautan.
            if ($apply) {
                $tersimpan = $model->setWaNumber($userId, $nomor, true);
                $masterOk = true;
                try {
                    if (Schema::hasColumn('master_guru', 'wa_number')) {
                        DB::table('master_guru')->where('id_guru', $g->id_guru)
                            ->update(['wa_number' => $nomor]);
                    }
                } catch (\Throwable $e) {
                    $masterOk = false;
                    \Illuminate\Support\Facades\Log::error('[wa:bulk] master_guru id='.$g->id_guru.': '.$e->getMessage());
                }
                $sukses = $tersimpan && $masterOk;
            } else {
                $sukses = true;
            }

            if ($sukses) {
                $ok++;
                $tabel[] = sprintf('  OK   %-38s user=%-5d no_hp=%s', $g->nama_guru ?? '-', $userId, $g->no_hp ?: '(kosong)');
            } else {
                $gagal++;
                $this->error('  GAGAL '.($g->nama_guru ?? '-').' user='.$userId);
            }
        }

        $this->line(implode("\n", $tabel));
        $this->line('');
        $this->line("Berhasil: {$ok}   Gagal: {$gagal}");

        if (! $apply) {
            $this->line('');
            $this->line('Dry-run selesai, tidak ada yang diubah. Tambahkan --apply untuk menulis.');

            return self::SUCCESS;
        }

        // Id sesi di-cache 60 detik; tidak perlu flush di sini karena
        // konfigurasi gateway tidak berubah, hanya data tujuan.
        $this->line('');
        $this->line('Selesai. Cek daftar di menu Kelola Tautan Guru.');

        return $gagal === 0 ? self::SUCCESS : self::FAILURE;
    }
}
