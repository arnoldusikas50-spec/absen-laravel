<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\CiForge;
use App\Models\DashboardModel;
use App\Models\PoinHistoryModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PoinGuruController extends BaseController
{
    public function index()
    {
        $this->ensureTableExists();
        $db = DB::connection();
        $year = date('Y');

        // Read teachers with poin_tahunan (now synced from poin_history)
        $teachers = $db->table('master_guru as mg')
            ->select('mg.id_guru', 'mg.nama_guru', 'mg.nip', 'mg.poin_tahunan', 'mg.foto', 'u.foto as user_foto')
            ->leftJoin('users as u', 'u.id', '=', 'mg.id_user')
            ->orderBy('mg.poin_tahunan', 'DESC')
            ->get();

        $dashboardModel = new DashboardModel;
        $setting = $this->getSettingRow();
        if (! $setting) {
            $setting = (object) ['nama_aplikasi' => 'CBT AKM', 'logo_kiri' => null];
        }

        $absensiSetting = $db->table('absensi_setting')->orderBy('id', 'ASC')->first();
        $user = $db->table('users')->where('id', session()->get('user_id'))->first();

        // Summary stats from poin_history
        $stats = $db->table('poin_history')
            ->selectRaw('SUM(poin) as total')
            ->whereRaw('YEAR(created_at) = ?', [$year])
            ->first();
        $annualTotalPoin = (float) ($stats->total ?? 0);

        $data = [
            'teachers' => $teachers,
            'title' => 'Poin Tahunan Guru',
            'setting' => $setting,
            'user' => $user,
            'absensiSetting' => $absensiSetting,
            'currentYear' => $year,
            'annualTotalPoin' => $annualTotalPoin,
        ];

        return view('admin.poin_guru.index', $data);
    }

    public function recalculate()
    {
        $this->ensureTableExists();
        $db = DB::connection();
        $year = date('Y');
        $poinModel = new PoinHistoryModel;

        // PoinHistoryModel::seedFromAttendance()/updateTotalPoin() sudah
        // memastikan tabelnya sendiri lewat safeQuery(). Memanggil method
        // private model dari sini menghasilkan Fatal error begitu halaman
        // ini dirutekan ulang.

        // 1. Backfill poin_history from attendance data for all teachers
        $teachers = $db->table('master_guru')
            ->select('id_guru', 'id_user')
            ->whereRaw('id_user IS NOT NULL')
            ->get();

        $seedCount = 0;
        foreach ($teachers as $guru) {
            for ($m = 1; $m <= 12; $m++) {
                $seedCount += $poinModel->seedFromAttendance($guru->id_user, $m, $year);
            }
        }

        // 2. Sync master_guru.poin_tahunan = SUM(poin_history.poin) per user
        $subQuery = $db->table('poin_history')
            ->select('user_id')
            ->selectRaw('SUM(poin) as total_poin')
            ->whereRaw('YEAR(created_at) = ?', [$year])
            ->groupBy('user_id');

        $rows = $db->table('master_guru as mg')
            ->select('mg.id_guru', 'ph.total_poin')
            ->leftJoinSub($subQuery, 'ph', 'ph.user_id', '=', 'mg.id_user')
            ->get();

        $count = 0;
        $gagal = 0;
        foreach ($rows as $row) {
            $total = (float) ($row->total_poin ?? 0);
            $tersimpan = $db->table('master_guru')
                ->where('id_guru', $row->id_guru)
                ->update(['poin_tahunan' => $total]);
            if ($tersimpan === false) {
                $gagal++;
            } else {
                $count++;
            }
        }

        $msg = "Berhasil menyinkronkan poin {$year} untuk {$count} guru dari poin_history (seed: {$seedCount} entri baru).";
        if ($gagal > 0) {
            $msg = "Sinkronisasi {$year} baru berhasil untuk {$count} guru; {$gagal} guru gagal ditulis. Muat ulang dan ulangi, atau gunakan Sinkron Poin di layar Reward.";
        }

        return redirect()->to('admin/poinguru')->with('message', $msg);
    }

    public function detail($id_guru)
    {
        $db = DB::connection();

        $teacher = $db->table('master_guru as mg')
            ->select('mg.*', 'u.username', 'u.email', 'u.foto as user_foto')
            ->leftJoin('users as u', 'u.id', '=', 'mg.id_user')
            ->where('mg.id_guru', $id_guru)
            ->first();

        if (! $teacher) {
            return redirect()->to('admin/poinguru')->with('error', 'Data guru tidak ditemukan.');
        }

        $year = date('Y');

        // Fetch poin_history for this teacher this year
        $poinHistory = $db->table('poin_history')
            ->where('user_id', $teacher->id_user)
            ->whereRaw('YEAR(created_at) = ?', [$year])
            ->orderBy('created_at', 'DESC')
            ->get();

        // Group by month for chart
        $monthly_stats = [];
        foreach ($poinHistory as $row) {
            $month = date('F', strtotime($row->created_at));
            if (! isset($monthly_stats[$month])) {
                $monthly_stats[$month] = ['points' => 0, 'count' => 0];
            }
            $monthly_stats[$month]['points'] += (float) $row->poin;
            $monthly_stats[$month]['count']++;
        }

        // Totals by type
        $typeSummary = [];
        foreach ($poinHistory as $row) {
            $t = $row->tipe;
            if (! isset($typeSummary[$t])) {
                $typeSummary[$t] = ['poin' => 0, 'count' => 0];
            }
            $typeSummary[$t]['poin'] += (float) $row->poin;
            $typeSummary[$t]['count']++;
        }

        $dashboardModel = new DashboardModel;
        $setting = $this->getSettingRow();
        $user = $db->table('users')->where('id', session()->get('user_id'))->first();

        $data = [
            'teacher' => $teacher,
            'poinHistory' => $poinHistory,
            'monthly_stats' => $monthly_stats,
            'typeSummary' => $typeSummary,
            'title' => 'Detail Poin: '.$teacher->nama_guru,
            'setting' => $setting,
            'user' => $user,
            'currentYear' => $year,
        ];

        return view('admin.poin_guru.detail', $data);
    }

    private function ensureTableExists()
    {
        $db = DB::connection();
        $forge = (new CiForge);

        if (! Schema::hasColumn('master_guru', 'poin_tahunan')) {
            $forge->addColumn('master_guru', [
                'poin_tahunan' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0,
                    'after' => 'foto',
                ],
            ]);
        }
    }
}
