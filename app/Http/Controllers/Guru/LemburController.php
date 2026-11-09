<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\BaseController;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\LemburModel;

class LemburController extends BaseController
{
    protected $lemburModel;
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->lemburModel = new LemburModel();
        $this->db = \Illuminate\Support\Facades\DB::connection();
    }

    public function index()
    {
        if (!$this->inGroup('guru'))
            return redirect()->to('auth');

        $user = $this->getUser();
        if (!$user) {
            return redirect()->to('auth');
        }

        $guru = $this->db->table('master_guru')->where('id_user', $user->id)->first();
        $lembur = $this->lemburModel->getLemburUser($user->id, 100);

        // Stats bulan berjalan
        $bulanSekarang = date('Y-m');
        $totalJamBulanIni = 0;
        $approvedJamBulanIni = 0;
        $pendingCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;

        foreach ($lembur as $l) {
            $isBulanIni = (substr((string) $l->tgl, 0, 7) === $bulanSekarang);
            if ($isBulanIni) {
                $totalJamBulanIni += (float) $l->total_jam;
                if ($l->status === 'APPROVED') {
                    $approvedJamBulanIni += (float) $l->total_jam;
                    $approvedCount++;
                }
            }
            if ($l->status === 'PENDING') {
                $pendingCount++;
            } elseif ($l->status === 'REJECTED') {
                $rejectedCount++;
            }
        }

        return view('members.guru.lembur_mobile', [
            'guru' => $guru,
            'lembur' => $lembur,
            'jamPulangKerja' => $this->jamPulangKerja((int) ($user->id ?? 0)),
            'stats' => [
                'total_jam_bulan_ini' => $totalJamBulanIni,
                'approved_jam_bulan_ini' => $approvedJamBulanIni,
                'approved_count' => $approvedCount,
                'pending_count' => $pendingCount,
                'rejected_count' => $rejectedCount,
            ]
        ]);
    }

    public function create()
    {
        if (!$this->inGroup('guru'))
            return redirect()->to('auth');

        $user = $this->getUser();
        if (!$user) {
            return redirect()->to('auth');
        }

        $guru = $this->db->table('master_guru')->where('id_user', $user->id)->first();

        return view('members.guru.lembur_form_mobile', [
            'guru' => $guru,
            // Jam mulai lembur default = jam pulang kerja guru hari ini
            // (shift → jadwal harian → setting). Sebelumnya view memakai 16:00
            // tetap, jadi guru pulang 17:00 harus mengoreksi manual.
            'jamPulangKerja' => $this->jamPulangKerja((int) $user->id),
        ]);
    }

    private function jamPulangKerja(int $userId): string
    {
        try {
            $jadwal = \App\Services\AttendancePolicy::jadwalGuru($userId, date('Y-m-d'));
            $pulang = substr(trim((string) ($jadwal['jam_pulang'] ?? '')), 0, 5);

            return ($pulang === '' || $pulang === '00:00') ? '16:00' : $pulang;
        } catch (\Throwable $e) {
            return '16:00';
        }
    }

    public function save()
    {
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        $user = $this->getUser();
        if (!$user) {
            return $this->respond(false, 'Sesi Anda sudah tidak aktif. Silakan masuk kembali.', null, null, 401);
        }

        $guru = $this->db->table('master_guru')->where('id_user', $user->id)->first();

        $tgl = $this->request->input('tgl');
        $jamMulai = $this->request->input('jam_mulai');
        $jamSelesai = $this->request->input('jam_selesai');
        $keterangan = trim((string) $this->request->input('keterangan'));

        if (!$tgl || !$jamMulai || !$jamSelesai) {
            return $this->respond(false, 'Tanggal, jam mulai, dan jam selesai wajib diisi');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl) || !strtotime($tgl)) {
            return $this->respond(false, 'Format tanggal tidak valid');
        }
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $jamMulai) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $jamSelesai)) {
            return $this->respond(false, 'Format jam tidak valid (HH:MM)');
        }
        $jamMulai = substr($jamMulai, 0, 5);
        $jamSelesai = substr($jamSelesai, 0, 5);
        if (mb_strlen($keterangan) > 1000) {
            return $this->respond(false, 'Keterangan terlalu panjang (maks 1000 karakter)');
        }

        // Tidak boleh lembur di masa lalu lebih dari 7 hari
        if ($tgl < date('Y-m-d', strtotime('-7 days'))) {
            return $this->respond(false, 'Tidak bisa mengajukan lembur lebih dari 7 hari yang lalu');
        }

        // Tidak boleh lembur di masa depan
        if ($tgl > date('Y-m-d')) {
            return $this->respond(false, 'Tidak bisa mengajukan lembur untuk tanggal di masa depan');
        }

        $start = strtotime($tgl . ' ' . $jamMulai);
        $end = strtotime($tgl . ' ' . $jamSelesai);

        // Jika jam selesai <= jam mulai, anggap lembur melewati tengah malam
        if ($end <= $start) {
            $end = strtotime($tgl . ' ' . $jamSelesai . ' +1 day');
        }

        $totalDetik = $end - $start;
        $totalJam = round($totalDetik / 3600, 2);

        if ($totalJam < 1) {
            return $this->respond(false, 'Lembur minimal 1 jam');
        }
        if ($totalJam > 16) {
            return $this->respond(false, 'Lembur maksimal 16 jam per pengajuan');
        }

        // Validasi: harus sudah absen masuk hari itu (kecuali hari libur).
        // Sumber libur yang kanonik — libur mingguan dari pengaturan sekolah
        // (bisa Sabtu, Jumat, atau hari lain) plus tabel hari libur nasional —
        // bukan hardcode "Sabtu dan Minggu". Versi lama menolak lembur hari
        // libur nasional di tengah pekan (17 Agustus, cuti bersama) padahal
        // justru di situ lembur paling sering terjadi, dan sebaliknya
        // membebaskan cek kehadiran pada Sabtu untuk sekolah yang Sabtu tetap
        // hari kerja.
        if (!\App\Services\AttendancePolicy::isLibur($tgl)) {
            $attendance = $this->db->table('absensi_harian')
                ->where('user_id', $user->id)
                ->where('tgl', $tgl)
                ->first();

            if (!$attendance) {
                return $this->respond(false, 'Anda belum absen pada tanggal tersebut. Lembur hanya bisa diajukan untuk hari di mana Anda hadir.');
            }
            if (!\App\Services\AttendancePolicy::isHadir($attendance->status_masuk)) {
                return $this->respond(false, 'Status absensi Anda pada tanggal tersebut tidak memungkinkan pengajuan lembur.');
            }
        }

        // Validasi: cek duplikat/jam tumpang tindih (mendukung rentang melewati tengah malam)
        $toMin = static function (string $hhmm): int {
            [$h, $m] = array_map('intval', explode(':', $hhmm));
            return $h * 60 + $m;
        };
        // Pecah rentang (yang bisa membungkus tengah malam) menjadi segmen linear agar
        // perbandingan tumpang tindih benar untuk lembur melewati tengah malam.
        $newS = $toMin($jamMulai);
        $newE = $toMin($jamSelesai);
        if ($newE <= $newS) $newE += 24 * 60;
        // Normalkan ke jangkar 0–1440 agar dapat dibandingkan dengan data tersimpan
        $newSegs = $newE > 24 * 60 ? [[$newS, 24 * 60], [0, $newE - 24 * 60]] : [[$newS, $newE]];

        // Baca-ubah-tulis harus atomik. SELECT tanpa lock lalu INSERT di luar
        // transaksi membuat dua POST serentak (klik ganda, retry dari antrean
        // offline) sama-sama melihat "tidak ada bentrok" dan keduanya masuk —
        // lembur terhitung ganda. Penguncian mengikuti pola yang sudah dipakai
        // jalur izin: kunci baris user di tabel users, jadi pengajuan satu guru
        // terserialisasi tanpa menyapu seluruh tabel presensi_lembur (tabel itu
        // tidak punya index pada user_id/tgl, FOR UPDATE di sana mengunci semua
        // baris dan ikut memblokir approval admin).
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                try {
                    $this->db->select(
                        'SELECT id FROM ' . 'users' . ' WHERE id = ? FOR UPDATE',
                        [(int) $user->id]
                    );
                } catch (\Throwable $e) {
                    // SQLite tidak mendukung FOR UPDATE; transaksi single-writer
                    // di jalur itu sudah cukup.
                }
            }

            $candidates = $this->db->table('presensi_lembur')
                ->where('user_id', $user->id)
                ->where('tgl', $tgl)
                ->where('status', '!=', 'REJECTED')
                ->get();

            foreach ($candidates as $ex) {
                $exS = $toMin(substr((string) $ex->jam_mulai, 0, 5));
                $exE = $toMin(substr((string) $ex->jam_selesai, 0, 5));
                $exSegs = $exE <= $exS ? [[$exS, 24 * 60], [0, $exE]] : [[$exS, $exE]];
                foreach ($newSegs as $ns) {
                    foreach ($exSegs as $es) {
                        if ($ns[0] < $es[1] && $es[0] < $ns[1]) {
                            \Illuminate\Support\Facades\DB::rollBack();
                            return $this->respond(false, 'Anda sudah memiliki pengajuan lembur di jam yang tumpang tindih pada tanggal ini');
                        }
                    }
                }
            }

            $insertId = $this->lemburModel->insert([
                'user_id' => $user->id,
                'id_guru' => $guru->id_guru ?? null,
                'tgl' => $tgl,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'total_jam' => $totalJam,
                'keterangan' => $keterangan,
                'status' => 'PENDING',
            ]);

            if (empty($insertId)) {
                \Illuminate\Support\Facades\DB::rollBack();
                return $this->respond(false, 'Pengajuan lembur gagal disimpan, silakan coba lagi');
            }

            \Illuminate\Support\Facades\DB::commit();
        } catch (\Throwable $e) {
            try { \Illuminate\Support\Facades\DB::rollBack(); } catch (\Throwable $e2) {}
            Log::error('[Guru\Lembur::save] ' . $e->getMessage());
            return $this->respond(false, 'Pengajuan lembur gagal disimpan, silakan coba lagi');
        }

        return $this->respond(true, 'Pengajuan lembur berhasil dikirim');
    }

    public function delete($id = null)
    {
        if (!$this->inGroup('guru'))
            return $this->respond(false, 'Unauthorized', null, null, 401);

        $user = $this->getUser();
        if (!$user) {
            return $this->respond(false, 'Sesi Anda sudah tidak aktif. Silakan masuk kembali.', null, null, 401);
        }
        $id = (int) $id;
        if ($id <= 0) {
            return $this->respond(false, 'ID pengajuan lembur tidak valid');
        }

        $lembur = $this->lemburModel->find($id);
        if (!$lembur || (int) $lembur->user_id !== (int) $user->id) {
            return $this->respond(false, 'Data lembur tidak ditemukan atau bukan milik Anda');
        }

        if ($lembur->status !== 'PENDING') {
            return $this->respond(false, 'Hanya pengajuan dengan status MENUNGGU (PENDING) yang dapat dibatalkan');
        }

        if ($this->lemburModel->where('id', $id)->delete() === false) {
            Log::error('[Guru\Lembur::delete] hapus ' . $id . ' gagal: ' . json_encode(['code' => 0, 'message' => '']));
            return $this->respond(false, 'Pengajuan lembur gagal dibatalkan, silakan coba lagi');
        }

        return $this->respond(true, 'Pengajuan lembur berhasil dibatalkan');
    }
}
