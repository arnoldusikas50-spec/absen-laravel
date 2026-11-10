<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\CiForge;
use App\Models\AuditLogModel;
use App\Models\DashboardModel;
use App\Models\LemburModel;
use App\Models\PoinHistoryModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LemburController extends BaseController
{
    protected $lemburModel;

    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->lemburModel = new LemburModel;
        $this->db = DB::connection();
    }

    /**
     * Cukup sekali probe per request: setiap pemanggilan adalah satu DESCRIBE,
     * dan halaman riwayat memanggil ini di index, approve, dan reject.
     */
    private static $catatanDipastikan = false;

    /**
     * Pastikan kolom catatan_admin tersedia (tambahan non-destruktif).
     * Mengembalikan false bila kolom tetap tidak ada, pemanggil wajib jujur
     * ke admin bahwa alasannya tidak tersimpan.
     */
    private function ensureCatatanAdmin(): bool
    {
        if (self::$catatanDipastikan) {
            return true;
        }

        try {
            if (! Schema::hasColumn('presensi_lembur', 'catatan_admin')) {
                $forge = (new CiForge);
                $forge->addColumn('presensi_lembur', [
                    'catatan_admin' => ['type' => 'TEXT', 'null' => true, 'after' => 'keterangan'],
                ]);
            }
            $tersedia = (bool) Schema::hasColumn('presensi_lembur', 'catatan_admin');
            if ($tersedia) {
                self::$catatanDipastikan = true;
            }

            return $tersedia;
        } catch (\Throwable $e) {
            Log::error('ensureCatatanAdmin: '.$e->getMessage());

            return false;
        }
    }

    private function getPointPerJam(): int
    {
        try {
            $row = $this->db->table('absensi_setting')->orderBy('id', 'ASC')->first();
            $rate = (int) ($row->point_lembur_per_jam ?? 5);
        } catch (\Throwable $e) {
            $rate = 5;
        }

        return max(0, min(50, $rate));
    }

    /**
     * Baca SEKALIGUS kunci satu pengajuan yang masih PENDING.
     *
     * Query builder CI4 tidak punya FOR UPDATE, jadi kunci barisnya lewat SQL
     * telanjang. Tanpa kunci, dua admin yang klik "Setujui" bersamaan sama-sama
     * membaca PENDING dan poin bisa dibukukan dua kali. Bila driver tidak
     * mendukung FOR UPDATE (SQLite), pemanggil tetap terlindungi oleh
     * UPDATE ... WHERE status = 'PENDING' plus pemeriksaan affectedRows.
     */
    private function ambilUntukDikunci(int $id): ?object
    {
        $sql = 'SELECT * FROM '.'presensi_lembur'
             .' WHERE id = ? AND status = ? FOR UPDATE';

        try {
            $hasil = $this->db->select($sql, [$id, 'PENDING']);
            if ($hasil === false) {
                throw new \RuntimeException(json_encode(['code' => 0, 'message' => '']));
            }
            $row = ($hasil[0] ?? null);

            return is_object($row) ? $row : null;
        } catch (\Throwable $e) {
            Log::warning('[Lembur] FOR UPDATE tidak tersedia, kembali ke baca biasa: '.$e->getMessage());

            $row = $this->db->table('presensi_lembur')
                ->where('id', $id)->where('status', 'PENDING')
                ->first();

            return $row ?: null;
        }
    }

    public function index()
    {
        if (! $this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $this->ensureCatatanAdmin();

        $bulan = str_pad((int) ($this->request->query('bulan') ?? date('m')), 2, '0', STR_PAD_LEFT);
        $tahun = (int) ($this->request->query('tahun') ?? date('Y'));
        $curYear = (int) date('Y');
        if ((int) $bulan < 1 || (int) $bulan > 12) {
            $bulan = date('m');
        }
        if ($tahun < attendance_tahun_tertua() || $tahun > $curYear + 1) {
            $tahun = $curYear;
        }

        $pending = $this->lemburModel->getPending();
        $rekap = $this->lemburModel->getRekap($bulan, $tahun);
        $riwayat = $this->lemburModel->getRiwayat($bulan, $tahun);
        $pointPerJam = $this->getPointPerJam();

        $summary = [
            'pending' => count($pending),
            'jam_pending' => 0.0,
            'jam_disetujui' => 0.0,
            'guru_lembur' => count($rekap),
        ];
        foreach ($pending as $p) {
            $summary['jam_pending'] += (float) $p->total_jam;
        }
        foreach ($rekap as $r) {
            $summary['jam_disetujui'] += (float) ($r->jam_disetujui ?? 0);
        }
        $summary['poin_bulan'] = (int) round($summary['jam_disetujui'] * $pointPerJam);

        $dashboardModel = new DashboardModel;
        $setting = $this->getSettingRow();
        if (! $setting) {
            $setting = (object) ['logo_kiri' => null, 'nama_aplikasi' => 'absensi by ikasmedia'];
        }

        return view('admin.presensi.lembur', [
            'title' => 'Lembur / Overtime',
            'judul' => 'Lembur / Overtime',
            'pending' => $pending,
            'rekap' => $rekap,
            'riwayat' => $riwayat,
            'summary' => $summary,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'pointPerJam' => $pointPerJam,
            'setting' => $setting,
            'user' => $this->getUser(),
            'tp_active' => DB::table('master_tp')->where('active', 1)->first(),
            'smt_active' => DB::table('master_smt')->where('active', 1)->first(),
        ]);
    }

    public function approve($id)
    {
        if (! $this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID lembur tidak valid');
        }

        $this->ensureCatatanAdmin();
        DB::beginTransaction();

        try {
            // Kunci baris: hanya PENDING yang bisa diproses (cegah double-approve)
            $lembur = $this->ambilUntukDikunci($id);
            if (! $lembur) {
                DB::rollBack();

                return $this->respond(false, 'Data tidak ditemukan atau sudah diproses admin lain');
            }

            $diperbarui = $this->db->table('presensi_lembur')
                ->where('id', $id)->where('status', 'PENDING')
                ->update([
                    'status' => 'APPROVED',
                    'approved_by' => $this->getUserId(),
                    'approved_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            if ($diperbarui < 1) {
                DB::rollBack();

                return $this->respond(false, 'Lembur ini sudah diproses admin lain. Muat ulang halaman.');
            }

            $pointPerJam = $this->getPointPerJam();
            $poinLembur = (int) round((float) $lembur->total_jam * $pointPerJam);
            $poinDitambah = 0;

            if ($poinLembur > 0) {
                // 1) Akumulasi ke kolom points absensi_harian (sumber rekap semester)
                if (Schema::hasColumn('absensi_harian', 'points')) {
                    $absensi = $this->db->table('absensi_harian')
                        ->where('user_id', $lembur->user_id)
                        ->where('tgl', $lembur->tgl)
                        ->first();

                    if ($absensi) {
                        $tertulis = $this->db->table('absensi_harian')
                            ->where('id', $absensi->id)
                            ->update(['points' => DB::raw('COALESCE(points, 0) + '.(int) $poinLembur)]);
                        if ($tertulis < 1) {
                            // Poin harian gagal: lebih baik seluruh persetujuan
                            // dibatalkan daripada status APPROVED tanpa poin.
                            Log::error('[approveLembur] gagal menambah points absensi '.$absensi->id
                                .': '.json_encode(['code' => 0, 'message' => '']));
                            DB::rollBack();

                            return $this->respond(false, 'Poin lembur gagal dibukukan. Persetujuan dibatalkan, silakan coba lagi.');
                        }
                    }
                }

                // 2) Ledger poin_history (sumber SALDO guru) — ref unik per lembur agar idempotent.
                //    Tidak memakai syncAttendancePoint agar tidak menimpa rincian harian yang sudah ada.
                $exists = $this->db->table('poin_history')
                    ->where('ref_id', 'lembur-'.$lembur->id)
                    ->where('user_id', $lembur->user_id)
                    ->first();
                if (! $exists) {
                    $tercatat = $this->db->table('poin_history')->insert([
                        'user_id' => $lembur->user_id,
                        'poin' => $poinLembur,
                        'tipe' => 'BONUS',
                        'keterangan' => 'Lembur '.$lembur->tgl.' ('.rtrim(rtrim(number_format((float) $lembur->total_jam, 2), '0'), '.').' jam)',
                        'ref_id' => 'lembur-'.$lembur->id,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    if ($tercatat === false) {
                        Log::error('[approveLembur] gagal menulis poin_history lembur '.$lembur->id
                            .': '.json_encode(['code' => 0, 'message' => '']));
                        DB::rollBack();

                        return $this->respond(false, 'Poin lembur gagal dibukukan. Persetujuan dibatalkan, silakan coba lagi.');
                    }
                    $poinDitambah = $poinLembur;
                    (new PoinHistoryModel)->updateTotalPoin($lembur->user_id);
                } else {
                    $poinDitambah = (int) $exists->poin;
                }
            }

            DB::commit();

            try {
                $auditTertulis = (new AuditLogModel)->log('presensi_lembur', (int) $lembur->id, 'APPROVE',
                    (int) $lembur->user_id, $this->getUserId(),
                    ['status' => 'PENDING', 'total_jam' => (float) $lembur->total_jam],
                    ['status' => 'APPROVED', 'poin' => $poinDitambah, 'tarif_poin_per_jam' => $pointPerJam],
                    'Persetujuan lembur '.$lembur->tgl);
                if ($auditTertulis === false) {
                    Log::error('[approveLembur] jejak audit lembur '.$lembur->id.' gagal ditulis');
                }
            } catch (\Throwable $ex) {
                Log::error('Audit approveLembur gagal: '.$ex->getMessage());
            }

            $msg = 'Lembur disetujui. Poin +'.$poinDitambah.' masuk ke saldo guru.';
            if ($poinDitambah === 0) {
                $msg = 'Lembur disetujui (tarif poin 0, tidak ada poin ditambahkan).';
            }

            return $this->respond(true, $msg, ['poin' => $poinDitambah]);
        } catch (\Throwable $e) {
            DB::rollBack();
            // Pesan exception (isi DB, nama tabel, path) tidak dikirim ke klien.
            Log::error('Approve Lembur Error (id '.$id.'): '.$e->getMessage());

            return $this->respond(false, 'Gagal menyetujui lembur. Perubahan dibatalkan, silakan coba lagi atau periksa log server.', null, null, 500);
        }
    }

    public function reject($id)
    {
        if (! $this->isAdmin()) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID lembur tidak valid');
        }

        $alasan = trim((string) $this->request->input('alasan'));
        if (mb_strlen($alasan) < 5 || mb_strlen($alasan) > 500) {
            return $this->respond(false, 'Alasan penolakan wajib diisi (5–500 karakter)');
        }

        $kolomCatatan = $this->ensureCatatanAdmin();
        DB::beginTransaction();

        try {
            $lembur = $this->ambilUntukDikunci($id);
            if (! $lembur) {
                DB::rollBack();

                return $this->respond(false, 'Data tidak ditemukan atau sudah diproses admin lain');
            }

            $update = [
                'status' => 'REJECTED',
                'approved_by' => $this->getUserId(),
                'approved_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($kolomCatatan) {
                $update['catatan_admin'] = $alasan;
            }

            $diperbarui = $this->db->table('presensi_lembur')
                ->where('id', $id)->where('status', 'PENDING')
                ->update($update);

            if ($diperbarui < 1) {
                DB::rollBack();

                return $this->respond(false, 'Lembur ini sudah diproses admin lain. Muat ulang halaman.');
            }

            DB::commit();

            try {
                $auditTertulis = (new AuditLogModel)->log('presensi_lembur', (int) $lembur->id, 'REJECT',
                    (int) $lembur->user_id, $this->getUserId(),
                    ['status' => 'PENDING'],
                    ['status' => 'REJECTED', 'catatan_admin' => $kolomCatatan ? $alasan : null],
                    'Penolakan lembur '.$lembur->tgl);
                if ($auditTertulis === false) {
                    Log::error('[rejectLembur] jejak audit lembur '.$lembur->id.' gagal ditulis');
                }
            } catch (\Throwable $ex) {
                Log::error('Audit rejectLembur gagal: '.$ex->getMessage());
            }

            // Penolakannya sendiri berhasil, jadi status tetap true; hanya
            // pencatatan alasannya yang harus jujur kalau kolomnya tidak ada.
            return $this->respond(true,
                $kolomCatatan
                    ? 'Lembur ditolak dengan alasan tercatat'
                    : 'Lembur ditolak, tetapi kolom catatan admin tidak tersedia sehingga alasannya tidak tersimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Reject Lembur Error (id '.$id.'): '.$e->getMessage());

            return $this->respond(false, 'Gagal menolak lembur. Perubahan dibatalkan, silakan coba lagi atau periksa log server.', null, null, 500);
        }
    }
}
