<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\BaseController;
use App\Models\UsersModel;
use App\Models\DashboardModel;
use App\Models\AuthModel;
use App\Models\MasterModel;

/**
 * Admin/UserManagement
 * ─────────────────────────────────────────────────────────────────────────────
 * Halaman terpadu manajemen user: Admin, Guru, dan Siswa dalam satu tampilan.
 * Controller ini hanya bertugas merender view — semua aksi AJAX tetap
 * diarahkan ke controller lama (Useradmin, Userguru, Usersiswa) agar tidak
 * memutus kompatibilitas dengan kode yang sudah ada.
 */
class UserManagementController extends BaseController
{
    protected UsersModel    $usersModel;
    protected DashboardModel $dashModel;
    protected AuthModel     $authModel;

    public function __construct()
    {
        parent::__construct();
        $this->usersModel = new UsersModel();
        $this->dashModel  = new DashboardModel();
        $this->authModel  = new AuthModel();
    }

    public function index()
    {
        if (!$this->isAdmin()) {
            return redirect()->to('auth')->with('error', 'Akses ditolak');
        }

        $tp  = \Illuminate\Support\Facades\DB::table('master_tp')->where('active', 1)->first();
        $smt = \Illuminate\Support\Facades\DB::table('master_smt')->where('active', 1)->first();

        // ── Statistik ringkas untuk KPI cards ─────────────────────────────
        $totalAdmin = 0;
        $totalGuru  = 0;
        $totalSiswa = 0;

        try {
            $db = \Illuminate\Support\Facades\DB::connection();

            $totalAdmin = (int) $db->table('users_groups')
                ->where('group_id', 1)
                ->count();

            $totalGuru = (int) $db->table('master_guru')->count();

            // Siswa aktif di buku induk
            if (\Illuminate\Support\Facades\Schema::hasTable('buku_induk')) {
                $totalSiswa = (int) $db->table('master_siswa as ms')
                    ->join('buku_induk as bi', "bi.id_siswa = ms.id_siswa AND bi.status = '1'")
                    ->count();
            } else {
                $totalSiswa = (int) $db->table('master_siswa')->count();
            }
        } catch (\Throwable $e) {
            Log::error('UserManagement::index stats error: ' . $e->getMessage());
        }

        // ── Data admin (langsung — biasanya sedikit) ──────────────────────
        $admins = [];
        try {
            $admins = $this->usersModel->getAllAdmin();
        } catch (\Throwable $e) {
            // Daftar kosong bukan berarti tidak ada admin — catat alasannya.
            Log::error('UserManagement::index admins error: ' . $e->getMessage());
        }

        return view('admin.users.index', [
            'user'        => $this->getUser(),
            'judul'       => 'Manajemen Pengguna',
            'subjudul'    => 'Kelola akun Admin, Guru, dan Siswa',
            'setting'     => $this->getSettingRow(),
            'tp_active'   => $tp,
            'smt_active'  => $smt,
            'admins'      => $admins,
            'totalAdmin'  => $totalAdmin,
            'totalGuru'   => $totalGuru,
            'totalSiswa'  => $totalSiswa,
        ]);
    }
}
