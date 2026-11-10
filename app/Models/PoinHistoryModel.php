<?php

namespace App\Models;

use App\Services\AttendancePolicy;
use App\Services\AvatarService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari CI4: app/Models/PoinHistoryModel.php
 * Tabel: poin_history
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class PoinHistoryModel extends Model
{
    protected $table = 'poin_history';

    protected $fillable = [
        'user_id',
        'poin',
        'tipe',
        'keterangan',
        'ref_id',
        'created_at',
    ];

    public $timestamps = false;

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'poin' => 'integer',
        'created_at' => 'datetime',
    ];

    private function ensureTable()
    {
        if (Schema::hasTable('poin_history')) {
            return;
        }
        Schema::create('poin_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->integer('poin')->default(0);
            $table->string('tipe', 20)->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->string('ref_id', 100)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->index('user_id');
        });
    }

    private function safeQuery(callable $fn, $default = null)
    {
        try {
            $this->ensureTable();

            return $fn();
        } catch (\Throwable $e) {
            Log::error('PoinHistoryModel: '.$e->getMessage());

            return $default;
        }
    }

    public function getSaldo($userId)
    {
        return $this->safeQuery(function () use ($userId) {
            $result = DB::table('poin_history')
                ->selectRaw('SUM(poin) as total')
                ->where('user_id', $userId)
                ->first();

            return (int) ($result->total ?? 0);
        }, 0);
    }

    /**
     * Menyinkronkan cache master_guru.poin_tahunan.
     *
     * Kolomnya bernama "tahunan" dan satu-satunya layar yang membacanya
     * melabelinya "tahun berjalan", jadi SUM-nya wajib dibatasi
     * YEAR(created_at). Tanpa batas, isinya akumulasi sepanjang masa dan
     * berbeda dari angka tahun berjalan di layar Reward admin.
     */
    // TODO: tabel master_guru belum ada migrasinya
    public function updateTotalPoin($userId, ?int $tahun = null)
    {
        return $this->safeQuery(function () use ($userId, $tahun) {
            $tahun = $tahun ?? (int) date('Y');

            $row = DB::table('poin_history')
                ->selectRaw('SUM(poin) as poin')
                ->where('user_id', $userId)
                ->whereYear('created_at', $tahun)
                ->first();
            $total = $row !== null ? ($row->poin ?? 0) : 0;

            if (Schema::hasColumn('master_guru', 'poin_tahunan')) {
                // users tidak pernah punya kolom id_guru; cabang lama yang
                // mencarinya selalu jatuh ke fallback dan hanya menambah
                // satu query per mutasi.
                DB::table('master_guru')
                    ->where('id_user', $userId)
                    ->update(['poin_tahunan' => (int) $total]);
            }

            return (int) $total;
        }, 0);
    }

    /**
     * Centralized function to sync a day's attendance points from absensi_harian to poin_history.
     * It trusts the 'points' column in absensi_harian.
     */
    public function syncAttendancePoint($attendanceId)
    {
        return $this->safeQuery(function () use ($attendanceId) {
            $attendance = DB::table('absensi_harian')->where('id', $attendanceId)->first();

            if (! $attendance) {
                return false;
            }

            $userId = $attendance->user_id;
            $refId = 'att-'.$attendanceId.'-daily';
            $points = (int) ($attendance->points ?? 0);

            // We consolidate all points for this attendance day into one entry to prevent duplicates and simplify
            // First, delete any old entries for this specific attendance (both old split format and new daily format)
            DB::table('poin_history')
                ->where('user_id', $userId)
                ->where(function ($q) use ($attendanceId, $refId) {
                    $q->where('ref_id', 'att-'.$attendanceId)
                        ->orWhere('ref_id', 'att-'.$attendanceId.'-out')
                        ->orWhere('ref_id', $refId);
                })
                ->delete();

            // If points == 0, we don't necessarily need an entry, but let's keep it for tracking (or maybe just skip if 0 and not alpha)
            // Actually, inserting 0 points is fine for audit.
            $tipe = 'KEHADIRAN';
            $ket = 'Poin Kehadiran Tgl '.$attendance->tgl;
            if ($points < 0) {
                $tipe = 'PENALTI';
            } elseif ($points > 0) {
                $tipe = 'REWARD';
            }

            // Angka hari ini gabungan beberapa sebab (telat, pulang cepat,
            // penalti tidak absen pulang). Tanpa catatan porsinya, guru yang
            // melihat "-8" di riwayat poin tidak bisa tahu 5 di antaranya
            // karena tidak absen pulang — dan itu pertanyaan pertama yang
            // mereka ajukan ke admin.
            $penaltiPulang = (int) ($attendance->poin_penalti_pulang ?? 0);
            if ($penaltiPulang > 0) {
                $ket .= ' (termasuk -'.$penaltiPulang.' tidak absen pulang)';
            }

            DB::table('poin_history')->insert([
                'user_id' => $userId,
                'poin' => $points,
                'tipe' => $tipe,
                'keterangan' => $ket,
                'ref_id' => $refId,
                'created_at' => substr($attendance->tgl, 0, 10).' '.($attendance->jam_masuk ?? '00:00:00'),
            ]);

            // Auto update master_guru
            $this->updateTotalPoin($userId);

            return true;
        }, false);
    }

    public function getRiwayat($userId, $limit = 20)
    {
        return $this->safeQuery(function () use ($userId, $limit) {
            return DB::table('poin_history')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get()->all();
        }, []);
    }

    public function getRiwayatBulanan($userId, $bulan, $tahun)
    {
        return $this->safeQuery(function () use ($userId, $bulan, $tahun) {
            $tglAwal = sprintf('%04d-%02d-01', (int) $tahun, (int) $bulan);
            $tglAkhir = date('Y-m-t', strtotime($tglAwal));

            return DB::table('poin_history')
                ->where('user_id', $userId)
                ->where('created_at', '>=', $tglAwal)
                ->where('created_at', '<=', $tglAkhir.' 23:59:59')
                ->orderBy('created_at', 'DESC')
                ->get()->all();
        }, []);
    }

    // TODO: tabel master_guru, users, users_groups belum ada migrasinya
    public function getLeaderboard($bulan, $tahun, $limit = 20)
    {
        return $this->safeQuery(function () use ($bulan, $tahun, $limit) {
            $monthStr = str_pad((int) $bulan, 2, '0', STR_PAD_LEFT);
            $monthPrefix = "{$tahun}-{$monthStr}";

            if (! Schema::hasTable('absensi_harian') || ! Schema::hasTable('users')) {
                return [];
            }

            $hasTelatMenit = Schema::hasColumn('absensi_harian', 'telat_menit');
            $hasPulangCepat = Schema::hasColumn('absensi_harian', 'pulang_cepat_menit');
            $hasDatangAwal = Schema::hasColumn('absensi_harian', 'datang_awal_menit');
            $hasPulangAkhir = Schema::hasColumn('absensi_harian', 'pulang_akhir_menit');

            // Satu rumus skor untuk semua layar (AttendancePolicy). Versi lokal
            // sebelumnya memberi 0 poin untuk hari dinas dan tetap memotong menit
            // telat pada hari dinas, sehingga bonus yang ditulis ke poin_history
            // lebih kecil 10–60 poin daripada angka yang tampil di presensi.
            $kolomSkor = [
                'telat_menit' => $hasTelatMenit,
                'pulang_cepat_menit' => $hasPulangCepat,
                'datang_awal_menit' => $hasDatangAwal,
                'pulang_akhir_menit' => $hasPulangAkhir,
            ];
            $scoreExpr = AttendancePolicy::sqlExprSkorHarian('a', $kolomSkor);

            $rows = DB::table('absensi_harian as a')
                ->select('a.user_id', 'u.first_name', 'u.last_name', 'u.username', 'u.foto as user_foto', 'mg.nama_guru', 'mg.foto as guru_foto')
                // "Tepat waktu" = ONTIME saja (label yang sama dipakai layar admin).
                // Hari dinas/WFH tampak lewat total_hadir dan skor, bukan di sini.
                ->selectRaw(AttendancePolicy::sqlTermHadir('a').' as total_hadir')
                ->selectRaw("SUM(CASE WHEN a.status_masuk = 'ONTIME' THEN 1 ELSE 0 END) as ontime")
                ->selectRaw("SUM(CASE WHEN a.status_masuk = 'LATE' THEN 1 ELSE 0 END) as late")
                ->selectRaw("ROUND($scoreExpr, 1) as score")
                ->selectRaw("ROUND($scoreExpr, 1) as total_poin")
                ->join('users as u', 'u.id', '=', 'a.user_id')
                ->join('users_groups as ug', 'ug.user_id', '=', 'u.id')
                ->leftJoin('master_guru as mg', 'mg.id_user', '=', 'u.id')
                ->where('ug.group_id', 2)
                ->where('a.tgl', 'like', $monthPrefix.'%')
                ->groupBy(['a.user_id', 'u.first_name', 'u.last_name', 'u.username', 'u.foto', 'mg.nama_guru', 'mg.foto'])
                ->orderBy('score', 'DESC')
                ->limit($limit)
                ->get()->all();

            // Nama dan foto diselesaikan AvatarService: tanpa foto pun hasilnya
            // tetap bisa ditampilkan (avatar inisial lokal), bukan URL host luar.
            foreach ($rows as $row) {
                $row->nama_lengkap = AvatarService::namaUtama($row);
                $row->foto = AvatarService::untukBaris($row);
            }

            return $rows;
        }, []);
    }

    public function seedFromAttendance($userId, $bulan, $tahun)
    {
        $setting = DB::table('absensi_setting')->first();

        // Pad bulan agar perbandingan string tanggal valid
        // ("2026-1-01" membuat '2026-01-05' < batas awal secara leksikal).
        $tglAwal = sprintf('%04d-%02d-01', (int) $tahun, (int) $bulan);
        $tglAkhir = date('Y-m-t', strtotime($tglAwal));

        $rows = DB::table('absensi_harian')
            ->where('user_id', $userId)
            ->where('tgl', '>=', $tglAwal)
            ->where('tgl', '<=', $tglAkhir)
            ->get()->all();

        $inserted = 0;
        foreach ($rows as $r) {
            // Lewati bila format DAILY sudah ada: syncAttendancePoint()
            // menggabungkan masuk+pulang dalam satu baris att-{id}-daily.
            // Cek lama (hanya att-{id}) membuat seed menulis ganda di
            // samping -daily setelah sync pernah berjalan.
            $refId = 'att-'.$r->id;
            $exists = DB::table('poin_history')
                ->where('user_id', $userId)
                ->where(function ($q) use ($refId) {
                    $q->where('ref_id', $refId)
                        ->orWhere('ref_id', $refId.'-out')
                        ->orWhere('ref_id', $refId.'-daily');
                })
                ->first();
            if (! $exists) {
                // Use the already-calculated points from absensi_harian (progressive calculation)
                $poin = (int) ($r->points ?? 0);
                $tipe = 'HADIR';
                $ket = 'Hadir';

                switch ($r->status_masuk) {
                    case 'ONTIME':
                        $tipe = 'HADIR';
                        $ket = $poin > 0 ? 'Hadir (datang awal)' : 'Hadir tepat waktu';
                        break;
                    case 'LATE':
                        $tipe = 'TELAT';
                        $ket = 'Terlambat '.($r->telat_menit ?? 0).' menit';
                        break;
                    case 'IJIN':
                    case 'IZIN':
                        $tipe = 'IZIN';
                        $ket = 'Izin';
                        $poin = 0;
                        break;
                    case 'SAKIT':
                        $tipe = 'SAKIT';
                        $ket = 'Sakit';
                        $poin = 0;
                        break;
                    case 'CUTI':
                        $tipe = 'CUTI';
                        $ket = 'Cuti';
                        $poin = 0;
                        break;
                    case 'ALPHA':
                    case 'ALPA':
                        $tipe = 'ALPHA';
                        // abs() di sini bukan kosmetik: tanpa itu, nilai
                        // point_alpa yang tersimpan negatif (kolomnya integer
                        // biasa, tidak ada yang melarang) justru memberi poin
                        // POSITIF untuk hari bolos.
                        $poin = isset($setting->point_alpa) ? -abs((int) $setting->point_alpa) : -5;
                        $ket = 'Alpha';
                        break;
                    case 'DINAS_PENDING':
                    case 'DINAS_APPROVED':
                    case 'DINAS':
                        $tipe = 'REWARD';
                        $ket = 'Tugas Dinas';
                        $poin = max(0, (int) ($r->points ?? 0));
                        break;
                    case 'PENDING_VERIFICATION':
                        // Netral selama jendela tinjau: bukan hadir, bukan alpha,
                        // dan tidak boleh dicatat sebagai 'Hadir' oleh default.
                        $tipe = 'PENDING';
                        $ket = 'Menunggu verifikasi admin';
                        $poin = 0;
                        break;
                    default:
                        if ($poin > 0) {
                            $tipe = 'HADIR';
                            $ket = 'Hadir ('.$r->status_masuk.')';
                        } else {
                            $tipe = 'HADIR';
                            $ket = 'Hadir';
                        }
                }

                DB::table('poin_history')->insert([
                    'user_id' => $userId,
                    'poin' => $poin,
                    'tipe' => $tipe,
                    'keterangan' => $ket,
                    'ref_id' => $refId,
                    'created_at' => $r->tgl.' '.($r->jam_masuk ?? '07:00:00'),
                ]);
                $inserted++;
            }

            // Also seed checkout delta entries if they exist and not yet synced (skip for dinas).
            // Lewati bila -daily sudah ada (sudah mencakup porsi pulang).
            $isDinas = in_array($r->status_masuk ?? '', ['DINAS_PENDING', 'DINAS_APPROVED', 'DINAS']) || strpos($r->status_masuk ?? '', 'DINAS') === 0;
            $outRefId = 'att-'.$r->id.'-out';
            $outExists = DB::table('poin_history')
                ->where('user_id', $userId)
                ->where(function ($q) use ($outRefId, $r) {
                    $q->where('ref_id', $outRefId)
                        ->orWhere('ref_id', 'att-'.$r->id.'-daily');
                })
                ->first();
            if (! $isDinas && ! $outExists && ! empty($r->jam_pulang)) {
                if (! empty($r->pulang_cepat_menit) && $r->pulang_cepat_menit > 0) {
                    // Calculate early penalty points for seed (use progressive formula)
                    $penaltyPoints = $this->estimateCheckoutPoints($r->pulang_cepat_menit, $setting, true);
                    if ($penaltyPoints !== 0) {
                        DB::table('poin_history')->insert([
                            'user_id' => $userId,
                            'poin' => $penaltyPoints,
                            'tipe' => 'PENALTI',
                            'keterangan' => 'Pulang Awal '.$r->pulang_cepat_menit.' menit',
                            'ref_id' => $outRefId,
                            'created_at' => $r->tgl.' '.($r->jam_pulang ?? '00:00:00'),
                        ]);
                        $inserted++;
                    }
                }
                if (! empty($r->pulang_akhir_menit) && $r->pulang_akhir_menit > 0) {
                    $rewardPoints = $this->estimateCheckoutPoints($r->pulang_akhir_menit, $setting, false);
                    if ($rewardPoints !== 0) {
                        DB::table('poin_history')->insert([
                            'user_id' => $userId,
                            'poin' => $rewardPoints,
                            'tipe' => 'REWARD',
                            'keterangan' => 'Pulang Akhir '.$r->pulang_akhir_menit.' menit',
                            'ref_id' => $outRefId,
                            'created_at' => $r->tgl.' '.($r->jam_pulang ?? '00:00:00'),
                        ]);
                        $inserted++;
                    }
                }
            }
        }

        return $inserted;
    }

    private function estimateCheckoutPoints($minutes, $setting, $isPenalty)
    {
        if ($minutes <= 0) {
            return 0;
        }
        if ($isPenalty) {
            $tier1_limit = 30;
            $tier2_limit = 60;
            $p_t1 = (int) ($setting->point_tier_1 ?? 1);
            $p_t2 = (int) ($setting->point_tier_2 ?? 2);
            $p_t3 = (int) ($setting->point_tier_3 ?? 5);
            $total = 0;
            $t1 = min($minutes, $tier1_limit);
            $total += $t1 * $p_t1;
            if ($minutes > $tier1_limit) {
                $remaining = $minutes - $tier1_limit;
                $t2_range = $tier2_limit - $tier1_limit;
                $t2 = min($remaining, $t2_range);
                $total += $t2 * $p_t2;
                if ($minutes > $tier2_limit) {
                    $t3 = $minutes - $tier2_limit;
                    $total += $t3 * $p_t3;
                }
            }

            return -1 * $total;
        } else {
            $cap = (int) ($setting->reward_cap_menit ?? 30);
            if ($cap > 0 && $minutes > $cap) {
                $minutes = $cap;
            }
            $tier1_limit = 15;
            $tier2_limit = 60;
            $p_t1 = (int) ($setting->reward_point_tier_1 ?? 1);
            $p_t2 = (int) ($setting->reward_point_tier_2 ?? 1);
            $p_t3 = (int) ($setting->reward_point_tier_3 ?? 2);
            $total = 0;
            $t1 = min($minutes, $tier1_limit);
            $total += $t1 * $p_t1;
            if ($minutes > $tier1_limit) {
                $remaining = $minutes - $tier1_limit;
                $t2_range = $tier2_limit - $tier1_limit;
                $t2 = min($remaining, $t2_range);
                $total += $t2 * $p_t2;
                if ($minutes > $tier2_limit) {
                    $t3 = $minutes - $tier2_limit;
                    $total += $t3 * $p_t3;
                }
            }

            return $total;
        }
    }

    // TODO: tabel users belum ada migrasinya
    public function calculateMonthlyScore($userId, $bulan, $tahun)
    {
        $score = 0;
        $detail = [];

        if (! Schema::hasTable('absensi_harian') || ! Schema::hasTable('users')) {
            return ['score' => 0, 'bonus' => 0, 'detail' => []];
        }

        $tglAwal = sprintf('%04d-%02d-01', (int) $tahun, (int) $bulan);
        $tglAkhir = date('Y-m-t', strtotime($tglAwal));
        $monthPrefix = substr($tglAwal, 0, 7);

        $kolomSkor = [
            'telat_menit' => Schema::hasColumn('absensi_harian', 'telat_menit'),
            'pulang_cepat_menit' => Schema::hasColumn('absensi_harian', 'pulang_cepat_menit'),
            'datang_awal_menit' => Schema::hasColumn('absensi_harian', 'datang_awal_menit'),
            'pulang_akhir_menit' => Schema::hasColumn('absensi_harian', 'pulang_akhir_menit'),
        ];

        // Skor yang sama dengan halaman presensi; nilai ini yang dipakai
        // syncMonthlyScoreBonus() untuk menulis bonus poin nyata.
        $scoreExpr = AttendancePolicy::sqlExprSkorHarian('a', $kolomSkor);

        try {
            $row = DB::table('absensi_harian as a')
                ->selectRaw("$scoreExpr as score")
                ->where('a.user_id', $userId)
                ->where('a.tgl', 'like', $monthPrefix.'%')
                ->groupBy('a.user_id')
                ->first();
            if ($row) {
                $score = (float) ($row->score ?? 0);
            }
        } catch (\Throwable $e) {
            Log::error('calculateMonthlyScore failed: '.$e->getMessage());
        }

        // Get detail breakdown
        try {
            $rowDetail = DB::table('absensi_harian as a')
                ->selectRaw('
                    '.AttendancePolicy::sqlTermHadir('a')." as total_hadir,
                    SUM(CASE WHEN a.status_masuk = 'ONTIME' THEN 1 ELSE 0 END) as ontime,
                    SUM(CASE WHEN a.status_masuk = 'LATE' THEN 1 ELSE 0 END) as late,
                    SUM(COALESCE(a.telat_menit, 0)) as total_telat_menit,
                    SUM(COALESCE(a.datang_awal_menit, 0)) as total_datang_awal,
                    SUM(COALESCE(a.pulang_akhir_menit, 0)) as total_pulang_akhir,
                    SUM(COALESCE(a.pulang_cepat_menit, 0)) as total_pulang_cepat
                ")
                ->where('a.user_id', $userId)
                ->where('a.tgl', 'like', $monthPrefix.'%')
                ->first();
            if ($rowDetail) {
                $detail = (array) $rowDetail;
            }
        } catch (\Throwable $e) {
        }

        $bonus = max(0, round($score));

        return ['score' => $score, 'bonus' => $bonus, 'detail' => $detail];
    }

    public function syncMonthlyScoreBonus($userId, $bulan, $tahun)
    {
        $this->ensureTable();

        $refId = "bonus-score-{$tahun}-{$bulan}";
        $exists = DB::table('poin_history')
            ->where('user_id', $userId)
            ->where('ref_id', $refId)
            ->first();
        if ($exists) {
            return ['bonus' => 0, 'action' => 'already_exists'];
        }

        $result = $this->calculateMonthlyScore($userId, $bulan, $tahun);
        $bonus = $result['bonus'];

        if ($bonus <= 0) {
            return ['bonus' => 0, 'action' => 'no_bonus'];
        }

        DB::table('poin_history')->insert([
            'user_id' => $userId,
            'poin' => $bonus,
            'tipe' => 'BONUS',
            'keterangan' => "Bonus skor bulanan {$tahun}-{$bulan}: {$result['score']} skor = {$bonus} poin",
            'ref_id' => $refId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return ['bonus' => $bonus, 'action' => 'created', 'score' => $result['score']];
    }
}
