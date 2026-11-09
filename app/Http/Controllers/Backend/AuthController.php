<?php

namespace App\Http\Controllers\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\BaseController;
use App\Models\DashboardModel;

class AuthController extends BaseController
{
    protected $dashboardModel;

    public function __construct()
    {
        parent::__construct();
        $this->dashboardModel = new DashboardModel();
    }

    /**
     * Halaman login admin/guru — sekarang dialihkan ke /auth
     * Route: GET /admin/login
     */
    public function login()
    {
        if ($this->isLoggedIn()) {
            return redirect()->to('dashboard');
        }
        // Tampilkan view yang sama dengan /auth
        $data['setting'] = (new \App\Models\DashboardModel())->getSettingRow();
        return view('backend.login', $data);
    }

    /**
     * Logout — redirect ke /auth
     */
    public function logout()
    {
        $this->session->destroy();
        return redirect()->to('auth')->with('success', 'Anda telah berhasil keluar.');
    }
}
