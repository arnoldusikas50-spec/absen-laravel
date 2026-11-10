<?php

namespace App\Models;

use App\Services\AvatarService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari CI4: app/Models/UsersModel.php
 * Tabel: users
 * Catatan: di CI4 model ini tidak mendeklarasikan $table (query-service);
 * $table diisi tabel utama yang dipakai model tersebut (users).
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class UsersModel extends Model
{
    protected $table = 'users';

    protected $fillable = [
    ];

    public $timestamps = false;

    public function getDuplicateUsernames($table)
    {
        $builder = DB::table($table);
        $builder->selectRaw('username, COUNT(*) as total');
        $builder->groupBy('username');
        $builder->havingRaw('total > 1');

        return $builder->get()->all();
    }

    public function usernameCheck($username, $table)
    {
        switch ($table) {
            case 'master_siswa':
                $select_name = 'nama';
                break;
            case 'master_panitia':
                $select_name = 'nama_panitia';
                break;
            default:
                $select_name = 'nama_guru';
        }

        return DB::table($table)
            ->select('username', $select_name)
            ->where('username', $username)
            ->get()->all();
    }

    // TODO: tabel users, groups, users_groups belum ada migrasinya
    public function getDatausers($id = null)
    {
        $builder = DB::table('users_groups');
        $builder->selectRaw('users.id, username, first_name, last_name, email, FROM_UNIXTIME(created_on) as created_on, last_login, active, groups.name as level');
        $builder->join('users', 'users_groups.user_id', '=', 'users.id');
        $builder->join('groups', 'users_groups.group_id', '=', 'groups.id');
        if ($id !== null) {
            $builder->where('users.id', '!=', $id);
        }

        return $builder->get()->all();
    }

    // TODO: tabel level_guru belum ada migrasinya
    public function getLevelGuru()
    {
        return DB::table('level_guru')->get()->all();
    }

    // TODO: tabel users, groups, users_groups belum ada migrasinya
    public function getAllAdmin()
    {
        $builder = DB::table('users_groups');
        $builder->selectRaw('users.id, username, first_name, last_name, email, FROM_UNIXTIME(created_on) as created_on, last_login, active, groups.name as level');
        $builder->join('users', 'users_groups.user_id', '=', 'users.id');
        $builder->join('groups', 'users_groups.group_id', '=', 'groups.id');
        $builder->where('group_id', 1);

        return $builder->get()->all();
    }

    // Alias for compatibility
    public function getDataadmin()
    {
        return $this->getAllAdmin();
    }

    // TODO: tabel master_guru, jabatan_guru, level_guru, users, login_attempts belum ada migrasinya
    public function getUserGuru($tp, $smt)
    {
        $builder = DB::table('master_guru as a');
        $builder->select('a.id_guru', 'a.nama_guru', 'a.username', 'a.password', 'a.foto', 'c.level', 'e.id', 'e.active');
        $builder->selectRaw('(SELECT COUNT(login) FROM login_attempts WHERE login_attempts.login = a.username) AS reset');

        $school_uuid = session()->get('school_uuid');
        if ($school_uuid && Schema::hasColumn('master_guru', 'sync_school_uuid')) {
            $builder->where('a.sync_school_uuid', $school_uuid);
        }

        $builder->leftJoin('jabatan_guru as b', function ($join) use ($tp, $smt) {
            $join->on('a.id_guru', '=', 'b.id_guru')
                ->where('b.id_tp', '=', $tp)
                ->where('b.id_smt', '=', $smt);
        });
        $builder->leftJoin('level_guru as c', 'b.id_jabatan', '=', 'c.id_level');
        $builder->leftJoin('users as e', 'a.id_user', '=', 'e.id');

        return $builder->get()->all();
    }

    // TODO: tabel master_guru belum ada migrasinya
    public function getDataGuru($id)
    {
        return DB::table('master_guru')->where('id_guru', $id)->first();
    }

    // TODO: tabel master_guru, jabatan_guru, level_guru, users belum ada migrasinya
    public function getDetailGuru($id)
    {
        $builder = DB::table('master_guru as a');
        $builder->select('a.id_guru', 'a.nama_guru', 'a.username', 'a.password', 'a.email', 'c.level', 'e.id', 'e.active');
        $builder->leftJoin('jabatan_guru as b', 'a.id_guru', '=', 'b.id_guru');
        $builder->leftJoin('level_guru as c', 'b.id_jabatan', '=', 'c.id_level');
        $builder->leftJoin('users as e', 'a.username', '=', 'e.username');
        $builder->where('a.id_guru', $id);

        return $builder->first();
    }

    // TODO: tabel users belum ada migrasinya
    public function getUsers($username)
    {
        return DB::table('users')->where('username', $username)->first();
    }

    // TODO: tabel master_guru belum ada migrasinya
    public function getGuruByUsername($username)
    {
        return DB::table('master_guru')->where('username', $username)->first();
    }

    // TODO: tabel master_panitia, users, login_attempts belum ada migrasinya
    public function getUserPanitia()
    {
        if (! Schema::hasTable('master_panitia')) {
            return [];
        }
        $count = DB::table('master_panitia')->count();
        if ($count == 0) {
            return [];
        }

        $builder = DB::table('master_panitia as a');
        $builder->select('a.id_panitia', 'a.nama_panitia', 'a.nip', 'a.username', 'a.password', 'a.instansi', 'a.jabatan', 'a.ruang', 'a.email', 'a.no_hp', 'a.foto', 'a.status', 'u.id', 'u.active');
        $builder->selectRaw('(SELECT COUNT(login) FROM login_attempts WHERE login_attempts.login = a.username) AS reset');

        $school_uuid = session()->get('school_uuid');
        if ($school_uuid && Schema::hasColumn('master_panitia', 'sync_school_uuid')) {
            $builder->where('a.sync_school_uuid', $school_uuid);
        }

        $builder->leftJoin('users as u', 'a.id_user', '=', 'u.id');
        $builder->orderBy('a.nama_panitia', 'ASC');

        $result = $builder->get()->all();
        foreach ($result as $row) {
            $row->foto = AvatarService::url($row->foto) ?? url('assets/img/profile-default.png');
        }

        return $result;
    }

    // TODO: tabel master_panitia belum ada migrasinya
    public function getDataPanitia($id)
    {
        if (! Schema::hasTable('master_panitia')) {
            return null;
        }

        return DB::table('master_panitia')->where('id_panitia', $id)->first();
    }

    // TODO: tabel master_kelas belum ada migrasinya
    public function getKelas($tp, $smt)
    {
        $builder = DB::table('master_kelas');
        $builder->where('id_tp', $tp)->where('id_smt', $smt);

        $school_uuid = session()->get('school_uuid');
        if ($school_uuid && Schema::hasColumn('master_kelas', 'sync_school_uuid')) {
            $builder->where('sync_school_uuid', $school_uuid);
        }

        return $builder->get()->all();
    }

    // TODO: tabel master_mapel belum ada migrasinya
    public function getMapel()
    {
        $builder = DB::table('master_mapel');
        $school_uuid = session()->get('school_uuid');
        if ($school_uuid && Schema::hasColumn('master_mapel', 'sync_school_uuid')) {
            $builder->where('sync_school_uuid', $school_uuid);
        }

        return $builder->get()->all();
    }

    // TODO: tabel master_siswa, buku_induk, kelas_siswa, master_kelas, users, login_attempts belum ada migrasinya
    public function getUserSiswaPage($id_tp, $id_smt, $offset, $limit, $search = null, $sort = null, $order = null, $reset = false)
    {
        $builder = DB::table('master_siswa as a');
        $builder->select('a.id_siswa', 'a.nis', 'a.foto', 'a.nama', 'a.username', 'a.password', 'd.id_kelas', 'f.nama_kelas', 'us.active');
        $builder->selectRaw('(SELECT COUNT(login) FROM login_attempts WHERE login_attempts.login = a.username) AS reset');

        $school_uuid = session()->get('school_uuid');
        if ($school_uuid && Schema::hasColumn('master_siswa', 'sync_school_uuid')) {
            $builder->where('a.sync_school_uuid', $school_uuid);
        }

        $builder->join('buku_induk as bu', function ($join) {
            $join->on('bu.id_siswa', '=', 'a.id_siswa')
                ->where('bu.status', '=', '1');
        });
        $builder->leftJoin('kelas_siswa as d', function ($join) use ($id_tp, $id_smt) {
            $join->on('d.id_siswa', '=', 'a.id_siswa')
                ->where('d.id_tp', '=', $id_tp)
                ->where('d.id_smt', '=', $id_smt);
        });
        $builder->leftJoin('master_kelas as f', 'f.id_kelas', '=', 'd.id_kelas');
        $builder->leftJoin('users as us', 'us.username', '=', 'a.username');

        $builder->orderByRaw('IF(ISNULL(f.level_id), 1, 0)');
        $builder->orderBy('f.level_id', 'ASC');
        $builder->orderBy('f.nama_kelas', 'ASC');
        $builder->orderBy('a.nama', 'ASC');

        if ($search) {
            $builder->where(function ($q) use ($search) {
                $q->where('a.nama', 'like', '%'.$search.'%')
                    ->orWhere('a.nis', 'like', '%'.$search.'%')
                    ->orWhere('a.nisn', 'like', '%'.$search.'%');
            });
        }

        if ($reset) {
            $builder->whereRaw('EXISTS (SELECT 1 FROM login_attempts WHERE login_attempts.login = a.username)');
        }

        return $builder->limit($limit)->offset($offset)->get()->all();
    }

    // TODO: tabel master_siswa, buku_induk, login_attempts belum ada migrasinya
    public function getUserSiswaTotalPage($search = null, $reset = false)
    {
        $builder = DB::table('master_siswa as a');

        $school_uuid = session()->get('school_uuid');
        if ($school_uuid && Schema::hasColumn('master_siswa', 'sync_school_uuid')) {
            $builder->where('a.sync_school_uuid', $school_uuid);
        }

        $builder->join('buku_induk as bu', function ($join) {
            $join->on('bu.id_siswa', '=', 'a.id_siswa')
                ->where('bu.status', '=', '1');
        });

        if ($search) {
            $builder->where(function ($q) use ($search) {
                $q->where('a.nama', 'like', '%'.$search.'%')
                    ->orWhere('a.nis', 'like', '%'.$search.'%')
                    ->orWhere('a.nisn', 'like', '%'.$search.'%');
            });
        }

        if ($reset) {
            $builder->whereRaw('EXISTS (SELECT 1 FROM login_attempts WHERE login_attempts.login = a.username)');
        }

        return $builder->count();
    }

    // TODO: tabel master_siswa, kelas_siswa, master_kelas, users belum ada migrasinya
    public function getUserSiswa($tp, $smt)
    {
        $builder = DB::table('master_siswa as a');
        $builder->select('a.id_siswa', 'a.nis', 'a.nama', 'a.username', 'a.password', 'c.nama_kelas', 'd.id', 'd.active');

        $school_uuid = session()->get('school_uuid');
        if ($school_uuid && Schema::hasColumn('master_siswa', 'sync_school_uuid')) {
            $builder->where('a.sync_school_uuid', $school_uuid);
        }

        $builder->leftJoin('kelas_siswa as b', function ($join) use ($tp, $smt) {
            $join->on('b.id_siswa', '=', 'a.id_siswa')
                ->where('b.id_tp', '=', $tp)
                ->where('b.id_smt', '=', $smt);
        });
        $builder->leftJoin('master_kelas as c', 'c.id_kelas', '=', 'b.id_kelas');
        $builder->leftJoin('users as d', 'd.username', '=', 'a.username');

        return $builder->get()->all();
    }
}
