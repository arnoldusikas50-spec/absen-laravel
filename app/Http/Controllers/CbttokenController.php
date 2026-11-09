<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\CbtModel;

class CbttokenController extends BaseController
{
    protected $cbtModel;

    public function __construct()
    {
        parent::__construct();
        $this->cbtModel = new CbtModel();
    }

    /**
     * Load current CBT token
     */
    public function loadtoken()
    {
        try {
            $token = $this->getToken();
            $data = $token ?: (object) [
                'token'   => 'GARUDA',
                'auto'    => '0',
                'jarak'   => '15',
                'now'     => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s')
            ];

            // Ensure expected properties exist for generate.js
            if (is_object($data)) {
                if (!isset($data->jarak)) $data->jarak = '15';
                if (!isset($data->now)) $data->now = date('Y-m-d H:i:s');
                if (!isset($data->updated)) $data->updated = date('Y-m-d H:i:s');
                if (!isset($data->token)) $data->token = 'GARUDA';
            }

            return $this->response->setJSON($data);
        } catch (\Throwable $th) {
            return $this->response->setJSON((object) [
                'token'   => 'GARUDA',
                'auto'    => '0',
                'jarak'   => '15',
                'now'     => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Generate new CBT token
     */
    public function generate()
    {
        try {
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $newToken = '';
            for ($i = 0; $i < 6; $i++) {
                $newToken .= $chars[rand(0, strlen($chars) - 1)];
            }

            $data = [
                'token'   => $newToken,
                'auto'    => '0',
                'jarak'   => '15',
                'now'     => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s')
            ];

            $this->saveToken($data);

            return $this->response->setJSON([
                'status' => true,
                'data'   => (object) $data,
                'token'  => $newToken
            ]);
        } catch (\Throwable $th) {
            return $this->response->setJSON([
                'status'  => true,
                'data'    => (object) [
                    'token'   => 'GARUDA',
                    'auto'    => '0',
                    'jarak'   => '15',
                    'now'     => date('Y-m-d H:i:s'),
                    'updated' => date('Y-m-d H:i:s')
                ],
                'token'   => 'GARUDA'
            ]);
        }
    }

    // ------------------------------------------------------------------
    // CbtModel CI4 diimplementasikan di sini karena model Laravel
    // (App\Models\CbtModel) belum mem-port method-methodnya.
    // ------------------------------------------------------------------

    /**
     * CI4 CbtModel::getToken() — baris pertama tabel `cbt_token`.
     */
    private function getToken()
    {
        try {
            if (!Schema::hasTable('cbt_token')) {
                return null;
            }
            return DB::table('cbt_token')->limit(1)->first();
        } catch (\Throwable $e) {
            Log::error('getToken error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * CI4 CbtModel::saveToken() — update token bila sudah ada, insert bila
     * tabel masih kosong (satu token saja, tanpa riwayat).
     */
    private function saveToken(array $data)
    {
        try {
            unset($data['status'], $data['updated'], $data['now']);

            if (!Schema::hasTable('cbt_token')) {
                return false;
            }
            $existing = $this->getToken();
            if ($existing) {
                $pk = isset($existing->id_token) ? 'id_token' : 'token';
                $val = $existing->{$pk};
                return DB::table('cbt_token')->where($pk, $val)->update($data);
            }
            return DB::table('cbt_token')->insert($data);
        } catch (\Throwable $e) {
            Log::error('saveToken error: ' . $e->getMessage());
            return false;
        }
    }
}
