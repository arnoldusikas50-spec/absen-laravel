<?php

namespace App\Http\Controllers\Guru;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\BaseController;
use App\Models\DashboardModel;
use App\Models\MasterModel;
use App\Models\PoinSiswaModel;

/**
 * Pencatatan poin disiplin siswa oleh guru (piket).
 * Bisa record manual dari master kategori + lihat riwayat.
 * Tanpa akses CRUD master kategori.
 */
class PoinSiswaController extends BaseController
{
    protected DashboardModel $dashboardModel;
    protected MasterModel $masterModel;
    protected PoinSiswaModel $poinModel;

    public function __construct()
    {
        parent::__construct();
        $this->dashboardModel = new DashboardModel();
        $this->masterModel = new MasterModel();
        $this->poinModel = new PoinSiswaModel();
        $this->poinModel->ensureTables();
    }

    public function index()
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('auth');
        }
        if (!$this->isGuru() && !$this->isAdmin()) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak');
        }

        $tp = \Illuminate\Support\Facades\DB::table('master_tp')->where('active', 1)->first();
        $smt = \Illuminate\Support\Facades\DB::table('master_smt')->where('active', 1)->first();
        $idKelas = (int) ($this->request->query('kelas') ?? 0);
        $kelasList = [];
        if ($tp && $smt) {
            try {
                $kelasList = $this->masterModel->getAllKelas((int) $tp->id_tp, (int) $smt->id_smt);
            } catch (\Throwable $e) {
                $kelasList = [];
            }
        }

        return view('members.guru.poin_siswa', [
            'user' => $this->getUser(),
            'judul' => 'Disiplin Siswa',
            'setting' => $this->getSettingRow(),
            'tp_active' => $tp,
            'smt_active' => $smt,
            'kelas_list' => $kelasList,
            'filter_kelas' => $idKelas,
            'leaderboard' => $this->poinModel->getLeaderboard($idKelas ?: null),
            'kategori' => $this->poinModel->getKategori(true),
        ]);
    }

    public function record()
    {
        if (!$this->isLoggedIn() || (!$this->isGuru() && !$this->isAdmin())) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        $res = $this->poinModel->recordManual(
            (int) $this->request->input('id_siswa'),
            (int) $this->request->input('kategori_id'),
            (string) ($this->request->input('keterangan') ?? ''),
            $this->getUserId()
        );
        return $this->respond($res['status'], $res['message'] ?? '', $res['skor'] ?? null);
    }

    public function history($idSiswa)
    {
        if (!$this->isLoggedIn() || (!$this->isGuru() && !$this->isAdmin())) {
            return $this->respond(false, 'Akses ditolak', null, null, 403);
        }
        return $this->respond(true, 'OK', [
            'skor' => $this->poinModel->getSkor((int) $idSiswa),
            'history' => $this->poinModel->getRiwayat((int) $idSiswa, 30),
        ]);
    }
}
