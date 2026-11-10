<?php

namespace App\Http\Controllers;

use App\Models\DashboardModel;
use App\Models\LogModel;
use App\Models\MasterModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatatahunController extends BaseController
{
    protected MasterModel $master;

    protected DashboardModel $dashboard;

    protected LogModel $logging;

    public function __construct()
    {
        parent::__construct();
        $this->master = new MasterModel;
        $this->dashboard = new DashboardModel;
        $this->logging = new LogModel;
    }

    public function index()
    {
        if (! $this->isAdmin()) {
            return redirect()->to('auth');
        }

        $tp = DB::table('master_tp')->where('active', 1)->first();
        $smt = DB::table('master_smt')->where('active', 1)->first();

        $data = [
            'user' => $this->authModel->user(),
            'judul' => 'Tahun Pelajaran & Semester',
            'subjudul' => 'Atur periode akademik aktif dan hari efektif pembelajaran',
            'setting' => $this->getSettingRow(),
            'tp' => DB::table('master_tp')->orderBy('id_tp', 'DESC')->get(),
            'tp_active' => $tp,
            'smt' => DB::table('master_smt')->orderBy('id_smt', 'ASC')->get(),
            'smt_active' => $smt,
            'tp_active_obj' => $tp,
            'smt_active_obj' => $smt,
        ];

        $jml = $this->master->getJmlHariEfektif(($tp->id_tp ?? '').($smt->id_smt ?? ''));
        $data['jml_hari'] = $jml == null ? '0' : $jml->jml_hari_efektif;

        // Statistik cepat untuk dashboard admin
        $data['stats'] = [
            'total_tp' => (int) DB::table('master_tp')->count(),
            'aktif_tp' => (int) DB::table('master_tp')->where('active', 1)->count(),
            'total_smt' => (int) DB::table('master_smt')->count(),
            'aktif_smt' => (int) DB::table('master_smt')->where('active', 1)->count(),
            'heb' => (int) $data['jml_hari'],
        ];

        return view('master.tahun.data', $data);
    }

    public function data()
    {
        if (! $this->isAdmin()) {
            return $this->balas(false, 'Unauthorized');
        }

        return $this->response->setJSON($this->dashboard->getDataTahun());
    }

    public function gantiTahun()
    {
        return $this->gantiPeriode('tahun', 'master_tp', 'id_tp', 'tp', 'Tahun Pelajaran', 20);
    }

    public function gantiSemester()
    {
        return $this->gantiPeriode('semester', 'master_smt', 'id_smt', 'Semester', 'Semester', 10);
    }

    /**
     * Pindahkan penanda "aktif" ke satu baris master_tp / master_smt.
     *
     * Invarian: setelah sukses harus ada TEKAT SATU baris active=1.
     * Tabel target divalidasi ada sebelum diproses agar tidak error saat
     * tabel belum tersedia (mis. setelah migrasi awal belum dijalankan).
     */
    private function gantiPeriode(string $fieldJson, string $tabel, string $pk, string $kolomLabel, string $namaManusiawi, int $maksLabel)
    {
        if (! $this->isAdmin()) {
            return $this->balas(false, 'Unauthorized');
        }

        if (! Schema::hasTable($tabel)) {
            return $this->balas(false, 'Tabel '.$tabel.' belum tersedia di database.');
        }

        $aktif = (int) ($this->request->input('active') ?? 0);
        $baris = json_decode((string) ($this->request->input($fieldJson) ?? '[]'), true);
        if (! is_array($baris) || $baris === []) {
            return $this->balas(false, 'Data '.strtolower($namaManusiawi).' tidak terbaca — tidak ada yang diubah.');
        }

        $update = [];
        foreach ($baris as $tps) {
            if (! is_array($tps)) {
                continue;
            }
            $id = (int) ($tps['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $label = trim((string) ($tps[$kolomLabel] ?? ''));
            if ($label === '' || mb_strlen($label) > $maksLabel) {
                return $this->balas(false, 'Label '.strtolower($namaManusiawi).' kosong atau lebih dari '
                    .$maksLabel.' karakter — tidak ada yang diubah.');
            }
            $update[$id] = [
                $pk => $id,
                $this->kolomPeriode($tabel) => $label,
                'active' => $id === $aktif ? 1 : 0,
            ];
        }

        if ($update === []) {
            return $this->balas(false, 'Tidak ada '.strtolower($namaManusiawi).' yang valid — tidak ada yang diubah.');
        }
        if (! isset($update[$aktif])) {
            return $this->balas(false, $namaManusiawi.' yang ingin diaktifkan tidak ditemukan di daftar — tidak ada yang diubah.');
        }

        $db = DB::connection();
        DB::beginTransaction();
        try {
            foreach (array_values($update) as $row) {
                $db->table($tabel)->where($pk, $row[$pk])->update($row);
            }
            // Jaga invarian "tepat satu aktif".
            $db->table($tabel)->where($pk, '<>', $aktif)->where('active', 1)->update(['active' => 0]);
            $db->table($tabel)->where($pk, $aktif)->update(['active' => 1]);

            $jumlahAktif = $db->table($tabel)->where('active', 1)->count();
            if ($jumlahAktif !== 1) {
                DB::rollBack();
                Log::error('[Datatahun] '.$tabel.' punya '.$jumlahAktif.' baris aktif setelah ganti');

                return $this->balas(false, $namaManusiawi.' gagal disimpan: jumlah periode aktif tidak tepat satu. Data belum berubah.');
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Datatahun] gantiPeriode '.$tabel.' aktif='.$aktif.': '.$e->getMessage());

            return $this->balas(false, $namaManusiawi.' gagal disimpan. Data belum berubah — coba lagi, dan bila berulang lapor ke pengembang.');
        }

        $this->saveAuditLog(4, 'mengganti '.strtolower($namaManusiawi).' aktif');

        return $this->balas(true, $namaManusiawi.' berhasil diaktifkan');
    }

    private function kolomPeriode(string $tabel): string
    {
        return $tabel === 'master_tp' ? 'tahun' : 'smt';
    }

    /**
     * Bentuk respon JSON yang dibaca view master/tahun/data.blade.php
     * (menggunakan kunci 'msg', bukan 'message').
     */
    private function balas(bool $status, string $pesan)
    {
        return $this->response->setJSON(['status' => $status, 'msg' => $pesan]);
    }

    public function add()
    {
        if (! $this->isAdmin()) {
            return $this->balas(false, 'Unauthorized');
        }

        $method = $this->request->input('method');
        $tabel = $this->request->input('tabel') ?? 'master_tp'; // 'master_tp' atau 'master_smt'
        if (! in_array($tabel, ['master_tp', 'master_smt'], true)) {
            return $this->balas(false, 'Tabel target tidak valid.');
        }

        $label = trim((string) ($this->request->input('label') ?? ''));
        $kolom = $this->kolomPeriode($tabel);
        $pk = $tabel === 'master_tp' ? 'id_tp' : 'id_smt';
        $nama = $tabel === 'master_tp' ? 'Tahun Pelajaran' : 'Semester';
        $maks = $tabel === 'master_tp' ? 20 : 10;

        if ($label === '' || mb_strlen($label) > $maks) {
            return $this->balas(false, 'Label '.$nama.' wajib diisi (maks '.$maks.' karakter).');
        }

        $db = DB::connection();

        if ($method === 'add') {
            // Satu label hanya sekali agar tidak ambigu saat aktifkan periode.
            if ($db->table($tabel)->where($kolom, $label)->count() > 0) {
                return $this->balas(false, $nama.' "'.$label.'" sudah ada.');
            }
            $tersimpan = $db->table($tabel)->insert([$kolom => $label, 'active' => 0]);
            if ($tersimpan === false) {
                Log::error('[Datatahun] insert '.$tabel.' ditolak DB ('.$kolom.'='.$label.')');

                return $this->balas(false, $nama.' gagal disimpan. Coba lagi, dan bila berulang lapor ke pengembang.');
            }
            $this->saveAuditLog(3, 'menambah '.$nama);

            return $this->balas(true, $nama.' berhasil ditambahkan');
        }

        // Edit
        $idKey = $tabel === 'master_tp' ? 'id_tahun' : 'id_smt';
        $id = (int) ($this->request->input($idKey) ?? 0);
        if ($id <= 0) {
            return $this->balas(false, 'Pilih '.$nama.' yang akan diubah.');
        }
        $ada = $db->table($tabel)->where($pk, $id)->get()->map(fn ($r) => (array) $r)->first();
        if (! $ada) {
            return $this->balas(false, $nama.' tidak ditemukan — mungkin sudah dihapus.');
        }

        $diubah = $db->table($tabel)->where($pk, $id)->update([$kolom => $label]);
        if ($diubah === false) {
            Log::error('[Datatahun] update '.$tabel.'#'.$id.' ditolak DB');

            return $this->balas(false, $nama.' gagal disimpan. Data belum berubah.');
        }
        $this->saveAuditLog(4, 'mengedit '.$nama);

        return $this->balas(true, $nama.' berhasil diubah');
    }

    public function saveHariEfektif()
    {
        if (! $this->isAdmin()) {
            return $this->balas(false, 'Unauthorized');
        }

        $tp = DB::table('master_tp')->where('active', 1)->first();
        $smt = DB::table('master_smt')->where('active', 1)->first();
        if (! $tp || ! $smt) {
            return $this->balas(false, 'Belum ada tahun/semester aktif. Aktifkan periode terlebih dahulu sebelum menyimpan hari efektif.');
        }

        $jumlah = filter_var($this->request->input('jml_hari'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 366]]);
        if ($jumlah === false) {
            return $this->balas(false, 'Jumlah hari efektif harus bilangan bulat 0–366.');
        }

        $db = DB::connection();
        $tersimpan = $db->table('master_hari_efektif')->replace([
            'id_hari_efektif' => (string) ((int) $tp->id_tp.(int) $smt->id_smt),
            'jml_hari_efektif' => $jumlah,
        ]);

        if ($tersimpan === false) {
            Log::error('[Datatahun] replace master_hari_efektif ditolak DB (tp='.$tp->id_tp.' smt='.$smt->id_smt.')');

            return $this->balas(false, 'Jumlah hari efektif gagal disimpan. Data belum berubah — coba lagi.');
        }

        return $this->balas(true, 'Jumlah hari efektif berhasil disimpan untuk TP '.$tp->tahun.' Semester '.$smt->smt);
    }

    public function hapusTahun()
    {
        return $this->hapusPeriode('master_tp', 'id_tp', 'id_tahun', 'Tahun Pelajaran');
    }

    public function hapusSemester()
    {
        return $this->hapusPeriode('master_smt', 'id_smt', 'id_smt', 'Semester');
    }

    /**
     * Hapus baris periode (TP atau Semester) jika tidak sedang aktif dan tidak
     * dipakai oleh tabel lain. Tahun/semester yang dipakai cukup dinonaktifkan.
     */
    private function hapusPeriode(string $tabel, string $pk, string $idKey, string $nama)
    {
        if (! $this->isAdmin()) {
            return $this->balas(false, 'Unauthorized');
        }

        $id = (int) ($this->request->input('hapus') ?? 0);
        if ($id <= 0) {
            return $this->balas(false, 'Pilih '.$nama.' yang akan dihapus.');
        }

        $db = DB::connection();
        $row = $db->table($tabel)->where($pk, $id)->get()->map(fn ($r) => (array) $r)->first();
        if (! $row) {
            return $this->balas(false, $nama.' tidak ditemukan — mungkin sudah dihapus.');
        }
        if ((int) $row['active'] === 1) {
            return $this->balas(false, $nama.' ini sedang aktif. Aktifkan '.$nama.' lain dulu, baru hapus.');
        }

        [$dipakai, $diTabel] = $this->cekPeriodeDipakai($tabel, $pk, $id);
        if ($dipakai) {
            Log::warning('[Datatahun] hapus '.$tabel.'#'.$id.' ditolak: '.($diTabel ?: 'pemakaian tidak dapat diperiksa'));

            return $diTabel === ''
                ? $this->balas(false, 'Pemakaian '.$nama.' ini tidak dapat diperiksa, jadi penghapusan ditolak. '
                    .'Coba lagi nanti, atau biarkan '.$nama.' ini dalam keadaan nonaktif.')
                : $this->balas(false, $nama.' ini masih dipakai data lain ('.$diTabel.'). '
                    .'Biarkan '.$nama.' ini tersimpan dalam keadaan nonaktif.');
        }

        if ($this->dashboard->hapus($tabel, $id, $pk)) {
            $this->saveAuditLog(5, 'menghapus '.$nama);

            return $this->balas(true, $nama.' berhasil dihapus');
        }

        return $this->balas(false, 'Gagal menghapus '.$nama.'. Data belum berubah.');
    }

    /**
     * Cek apakah periode masih dirujuk tabel lain melalui kolom id_tp / id_smt.
     *
     * @return array{0: bool, 1: string} [tolak, contoh tabel & jumlah baris]
     */
    private function cekPeriodeDipakai(string $tabel, string $pk, int $id): array
    {
        $db = DB::connection();
        $kolomTarget = $pk === 'id_tp' ? 'id_tp' : 'id_smt';
        $sql = "SELECT c.TABLE_NAME AS nama
                  FROM information_schema.COLUMNS c
                  JOIN information_schema.TABLES t
                    ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
                 WHERE c.TABLE_SCHEMA = DATABASE()
                   AND c.COLUMN_NAME = ?
                   AND c.TABLE_NAME <> ?
                   AND t.TABLE_TYPE = 'BASE TABLE'
                   AND c.DATA_TYPE IN ('int', 'bigint', 'mediumint', 'smallint')
                   AND UPPER(c.EXTRA) NOT LIKE '%AUTO_INCREMENT%'
                 ORDER BY c.TABLE_NAME";
        $q = $db->select($sql, [$kolomTarget, $tabel]);
        if ($q === false) {
            return [true, ''];
        }

        $kandidat = array_map(
            static fn (array $b): string => (string) ($b['nama'] ?? ''),
            array_map(fn ($r) => (array) $r, $q)
        );

        foreach ($kandidat as $tabelCek) {
            if ($tabelCek === '') {
                continue;
            }
            try {
                $jumlah = $db->table($tabelCek)->where($kolomTarget, $id)->count();
                if ($jumlah > 0) {
                    return [true, $tabelCek.': '.$jumlah.' baris'];
                }
            } catch (\Throwable $e) {
                Log::error('[Datatahun] cek pemakai '.$tabel.'#'.$id.' di '.$tabelCek.': '.$e->getMessage());

                return [true, ''];
            }
        }

        return [false, ''];
    }
}
