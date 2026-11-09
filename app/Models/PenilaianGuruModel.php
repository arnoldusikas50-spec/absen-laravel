<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari CI4: app/Models/PenilaianGuruModel.php
 * Tabel: penilaian_guru_periode
 * Catatan: di CI4 model ini tidak mendeklarasikan $table (query-service);
 * $table diisi tabel utama yang dipakai model tersebut (penilaian_guru_periode).
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 */
class PenilaianGuruModel extends Model
{
    protected $table = 'penilaian_guru_periode';

    protected $fillable = [
    ];

    public $timestamps = false;

    // TODO: tabel penilaian_guru_periode, penilaian_guru_skor belum ada migrasinya
    public function initialize()
    {
        if (! Schema::hasTable('penilaian_guru_periode')) {
            Schema::create('penilaian_guru_periode', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nama', 255);
                $table->integer('id_tp');
                $table->integer('id_smt');
                $table->date('tgl_mulai');
                $table->date('tgl_selesai');
                $table->decimal('bobot_kehadiran', 5, 2)->default(30.00);
                $table->decimal('bobot_mengajar', 5, 2)->default(40.00);
                $table->decimal('bobot_piket', 5, 2)->default(30.00);
                $table->enum('status', ['draft', 'aktif', 'selesai'])->default('draft');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('penilaian_guru_skor')) {
            Schema::create('penilaian_guru_skor', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('id_periode');
                $table->integer('id_guru');
                $table->decimal('skor_kehadiran', 5, 2)->default(0.00);
                $table->decimal('skor_mengajar', 5, 2)->default(0.00);
                $table->decimal('skor_piket', 5, 2)->default(0.00);
                $table->decimal('skor_akhir', 5, 2)->default(0.00);
                $table->string('predikat', 5)->nullable();
                $table->longText('detail')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->unique(['id_periode', 'id_guru']);
            });
        }
    }

    // --- Periode CRUD ---

    // TODO: tabel penilaian_guru_periode, master_tp, master_smt belum ada migrasinya
    public function getPeriodeList()
    {
        return DB::table('penilaian_guru_periode as p')
            ->select('p.*', 'tp.tahun', 'smt.smt')
            ->join('master_tp as tp', 'tp.id_tp', '=', 'p.id_tp', 'left')
            ->join('master_smt as smt', 'smt.id_smt', '=', 'p.id_smt', 'left')
            ->orderBy('p.tgl_mulai', 'DESC')
            ->get()->all();
    }

    // TODO: tabel penilaian_guru_periode belum ada migrasinya
    public function getPeriode($id)
    {
        return DB::table('penilaian_guru_periode')
            ->where('id', $id)
            ->first();
    }

    // TODO: tabel penilaian_guru_periode belum ada migrasinya
    public function savePeriode($data)
    {
        if (! empty($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            $data['updated_at'] = date('Y-m-d H:i:s');
            DB::table('penilaian_guru_periode')->where('id', $id)->update($data);

            return true;
        }
        $data['created_at'] = date('Y-m-d H:i:s');

        return DB::table('penilaian_guru_periode')->insert($data);
    }

    // TODO: tabel penilaian_guru_periode, penilaian_guru_skor belum ada migrasinya
    public function deletePeriode($id)
    {
        DB::table('penilaian_guru_skor')->where('id_periode', $id)->delete();
        DB::table('penilaian_guru_periode')->where('id', $id)->delete();

        return true;
    }

    // --- Scoring ---

    // TODO: tabel penilaian_guru_skor, master_guru belum ada migrasinya
    public function getSkorByPeriode($id_periode)
    {
        return DB::table('penilaian_guru_skor as s')
            ->select('s.*', 'g.nama_guru', 'g.nip')
            ->join('master_guru as g', 'g.id_guru', '=', 's.id_guru')
            ->where('s.id_periode', $id_periode)
            ->orderBy('s.skor_akhir', 'DESC')
            ->get()->all();
    }

    // TODO: tabel penilaian_guru_skor belum ada migrasinya
    public function getSkorGuru($id_periode, $id_guru)
    {
        return DB::table('penilaian_guru_skor')
            ->where('id_periode', $id_periode)
            ->where('id_guru', $id_guru)
            ->first();
    }

    // TODO: tabel penilaian_guru_skor, master_guru, penilaian_guru_periode belum ada migrasinya
    public function getSkorById($id)
    {
        return DB::table('penilaian_guru_skor as s')
            ->select('s.*', 'g.nama_guru', 'g.nip', 'p.nama as nama_periode', 'p.tgl_mulai', 'p.tgl_selesai', 'p.bobot_kehadiran', 'p.bobot_mengajar', 'p.bobot_piket')
            ->join('master_guru as g', 'g.id_guru', '=', 's.id_guru')
            ->join('penilaian_guru_periode as p', 'p.id', '=', 's.id_periode')
            ->where('s.id', $id)
            ->first();
    }

    // --- Scoring Engine ---

    // TODO: tabel penilaian_guru_periode, penilaian_guru_skor, master_guru belum ada migrasinya
    public function hitungSkor($id_periode)
    {
        $periode = $this->getPeriode($id_periode);
        if (! $periode) {
            return ['status' => false, 'message' => 'Periode tidak ditemukan'];
        }

        $id_tp = (int) $periode->id_tp;
        $id_smt = (int) $periode->id_smt;
        $tgl_mulai = $periode->tgl_mulai;
        $tgl_selesai = $periode->tgl_selesai;
        $bobot_kehadiran = (float) $periode->bobot_kehadiran;
        $bobot_mengajar = (float) $periode->bobot_mengajar;
        $bobot_piket = (float) $periode->bobot_piket;

        // All active teachers
        $gurus = DB::table('master_guru')
            ->select('id_guru', 'nama_guru')
            ->orderBy('nama_guru', 'ASC')
            ->get()->all();

        // Pre-load holidays & approved leaves
        $holidays = $this->getHolidays($tgl_mulai, $tgl_selesai);
        $approvedLeaves = $this->getApprovedLeaves($tgl_mulai, $tgl_selesai);

        // Pre-load schedule: which teacher teaches what per day
        $scheduleMap = $this->getScheduleMap($id_tp, $id_smt);

        // Pre-load piket schedule
        $piketMap = $this->getPiketMap($id_tp, $id_smt);

        $results = [];

        foreach ($gurus as $g) {
            $id_guru = (int) $g->id_guru;

            // === 1. SKOR KEHADIRAN ===
            $skorHadir = $this->hitungKehadiran($id_guru, $tgl_mulai, $tgl_selesai, $holidays, $approvedLeaves);
            $rataHadir = $skorHadir['rata_rata'];

            // === 2. SKOR MENGAJAR ===
            $skorMengajar = $this->hitungMengajar($id_guru, $id_tp, $id_smt, $tgl_mulai, $tgl_selesai, $holidays, $scheduleMap);
            $rataMengajar = $skorMengajar['persentase'];

            // === 3. SKOR PIKET ===
            $skorPiket = $this->hitungPiket($id_guru, $id_tp, $id_smt, $tgl_mulai, $tgl_selesai, $holidays, $piketMap);
            $rataPiket = $skorPiket['persentase'];

            // === SKOR AKHIR ===
            $totalWeight = $bobot_kehadiran + $bobot_mengajar + $bobot_piket;
            if ($totalWeight > 0) {
                $skor_akhir = ($rataHadir * $bobot_kehadiran + $rataMengajar * $bobot_mengajar + $rataPiket * $bobot_piket) / $totalWeight;
            } else {
                $skor_akhir = 0;
            }
            $skor_akhir = round($skor_akhir, 2);

            $predikat = $this->getPredikat($skor_akhir);

            $detail = json_encode([
                'kehadiran' => $skorHadir,
                'mengajar' => $skorMengajar,
                'piket' => $skorPiket,
            ]);

            // Save or update
            $existing = DB::table('penilaian_guru_skor')
                ->where('id_periode', $id_periode)
                ->where('id_guru', $id_guru)
                ->first();

            $payload = [
                'id_periode' => $id_periode,
                'id_guru' => $id_guru,
                'skor_kehadiran' => $rataHadir,
                'skor_mengajar' => $rataMengajar,
                'skor_piket' => $rataPiket,
                'skor_akhir' => $skor_akhir,
                'predikat' => $predikat,
                'detail' => $detail,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                DB::table('penilaian_guru_skor')->where('id', $existing->id)->update($payload);
            } else {
                $payload['created_at'] = date('Y-m-d H:i:s');
                DB::table('penilaian_guru_skor')->insert($payload);
            }

            $results[] = [
                'id_guru' => $id_guru,
                'nama_guru' => $g->nama_guru,
                'skor_kehadiran' => $rataHadir,
                'skor_mengajar' => $rataMengajar,
                'skor_piket' => $rataPiket,
                'skor_akhir' => $skor_akhir,
                'predikat' => $predikat,
            ];
        }

        return ['status' => true, 'message' => 'Perhitungan selesai untuk '.count($results).' guru', 'data' => $results];
    }

    // --- Kehadiran Score ---

    private function hitungKehadiran($id_guru, $tgl_mulai, $tgl_selesai, $holidays, $approvedLeaves)
    {
        $absensi = DB::table('absensi_harian')
            ->where('id_guru', $id_guru)
            ->where('tgl', '>=', $tgl_mulai)
            ->where('tgl', '<=', $tgl_selesai)
            ->orderBy('tgl', 'ASC')
            ->get()->all();

        $absensiByDate = [];
        foreach ($absensi as $a) {
            $absensiByDate[$a->tgl] = $a;
        }

        $totalSkor = 0;
        $totalHari = 0;
        $detailHari = [];
        $current = new \DateTime($tgl_mulai);
        $end = new \DateTime($tgl_selesai);

        while ($current <= $end) {
            $tgl = $current->format('Y-m-d');
            $hariNum = (int) $current->format('N');

            // Skip weekends (Saturday=6, Sunday=7)
            if ($hariNum >= 6) {
                $current->modify('+1 day');

                continue;
            }

            // Skip holidays
            if (isset($holidays[$tgl])) {
                $current->modify('+1 day');

                continue;
            }

            $totalHari++;
            $skorHari = 0;
            $status = 'alpha';

            if (isset($absensiByDate[$tgl])) {
                $a = $absensiByDate[$tgl];
                $masuk = trim((string) ($a->status_masuk ?? ''));
                $pulang = trim((string) ($a->status_pulang ?? ''));
                $jamMasuk = $a->jam_masuk;
                $jamPulang = $a->jam_pulang;

                if ($masuk === 'DINAS_APPROVED' || $masuk === 'DINAS' || (strpos($masuk, 'DINAS') === 0 && $masuk !== 'DINAS_REJECTED' && $masuk !== 'DINAS_PENDING')) {
                    $skorHari = 100;
                    $status = 'dinas';
                } elseif ($masuk === 'DINAS_PENDING') {
                    $skorHari = 80;
                    $status = 'dinas';
                } elseif ($masuk === 'ONTIME' || $masuk === 'LATE') {
                    if (! empty($jamMasuk)) {
                        if ($masuk === 'ONTIME' && (! empty($jamPulang) || $pulang === 'ONTIME')) {
                            $skorHari = 100;
                            $status = 'hadir_sempurna';
                        } elseif ($masuk === 'ONTIME' && ! empty($jamPulang)) {
                            $skorHari = 90;
                            $status = 'hadir_pulang';
                        } elseif ($masuk === 'ONTIME' && empty($jamPulang)) {
                            $skorHari = 70;
                            $status = 'hadir_belum_pulang';
                        } elseif ($masuk === 'LATE') {
                            if (! empty($jamPulang)) {
                                $skorHari = 70;
                                $status = 'terlambat_pulang';
                            } else {
                                $skorHari = 50;
                                $status = 'terlambat';
                            }
                        }
                    } else {
                        $skorHari = 30;
                        $status = 'tanpa_jam';
                    }
                } else {
                    $skorHari = 30;
                    $status = 'tanpa_status';
                }
            } else {
                // Check approved leave
                $cuti = $approvedLeaves[$id_guru][$tgl] ?? null;
                if ($cuti) {
                    $skorHari = 70;
                    $status = 'izin_'.$cuti;
                } else {
                    $skorHari = 0;
                    $status = 'alpha';
                }
            }

            $totalSkor += $skorHari;
            $detailHari[$tgl] = ['skor' => $skorHari, 'status' => $status];
            $current->modify('+1 day');
        }

        $rataRata = $totalHari > 0 ? round(($totalSkor / ($totalHari * 100)) * 100, 2) : 0;

        return [
            'rata_rata' => $rataRata,
            'total_hari' => $totalHari,
            'total_skor' => $totalSkor,
            'detail' => $detailHari,
        ];
    }

    // --- Teaching Score ---

    // TODO: tabel guru_jurnal belum ada migrasinya
    private function hitungMengajar($id_guru, $id_tp, $id_smt, $tgl_mulai, $tgl_selesai, $holidays, $scheduleMap)
    {
        $scheduledSessions = 0;
        $taughtSessions = 0;
        $detail = [];

        $current = new \DateTime($tgl_mulai);
        $end = new \DateTime($tgl_selesai);

        // Get all journals for this teacher in period
        $journals = DB::table('guru_jurnal')
            ->where('id_guru', $id_guru)
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->where('tanggal', '>=', $tgl_mulai)
            ->where('tanggal', '<=', $tgl_selesai)
            ->get()->all();

        $journalMap = [];
        foreach ($journals as $j) {
            $key = $j->tanggal.'|'.$j->id_kelas.'|'.$j->id_mapel.'|'.$j->jam_ke;
            $journalMap[$key] = $j;
        }

        while ($current <= $end) {
            $tgl = $current->format('Y-m-d');
            $hariNum = (int) $current->format('N');

            if ($hariNum >= 6) {
                $current->modify('+1 day');

                continue;
            }
            if (isset($holidays[$tgl])) {
                $current->modify('+1 day');

                continue;
            }

            $hariNama = $current->format('N');
            $kelasMap = $scheduleMap[$id_guru][$hariNama] ?? [];

            foreach ($kelasMap as $sesi) {
                $scheduledSessions++;
                $key = $tgl.'|'.$sesi['id_kelas'].'|'.$sesi['id_mapel'].'|'.$sesi['jam_ke'];
                if (isset($journalMap[$key])) {
                    $taughtSessions++;
                    $detail[$tgl][] = ['jam_ke' => $sesi['jam_ke'], 'id_kelas' => $sesi['id_kelas'], 'id_mapel' => $sesi['id_mapel'], 'status' => 'mengajar'];
                } else {
                    $detail[$tgl][] = ['jam_ke' => $sesi['jam_ke'], 'id_kelas' => $sesi['id_kelas'], 'id_mapel' => $sesi['id_mapel'], 'status' => 'tidak_mengajar'];
                }
            }

            $current->modify('+1 day');
        }

        $persentase = $scheduledSessions > 0 ? round(($taughtSessions / $scheduledSessions) * 100, 2) : 100;

        return [
            'persentase' => $persentase,
            'terjadwal' => $scheduledSessions,
            'terlaksana' => $taughtSessions,
            'detail' => $detail,
        ];
    }

    // --- Piket Score ---

    private function hitungPiket($id_guru, $id_tp, $id_smt, $tgl_mulai, $tgl_selesai, $holidays, $piketMap)
    {
        $totalTugas = 0;
        $terlaksana = 0;
        $detail = [];

        $current = new \DateTime($tgl_mulai);
        $end = new \DateTime($tgl_selesai);

        while ($current <= $end) {
            $tgl = $current->format('Y-m-d');
            $hariNum = (int) $current->format('N');

            if ($hariNum >= 6) {
                $current->modify('+1 day');

                continue;
            }
            if (isset($holidays[$tgl])) {
                $current->modify('+1 day');

                continue;
            }

            $hariNama = $current->format('l');
            $isPiket = false;
            foreach ($piketMap as $p) {
                if ($p['id_guru'] == $id_guru && $p['hari'] === $hariNama) {
                    $isPiket = true;
                    break;
                }
            }

            if ($isPiket) {
                $totalTugas++;

                $absensi = DB::table('absensi_harian')
                    ->where('id_guru', $id_guru)
                    ->where('tgl', $tgl)
                    ->first();

                if ($absensi && ! empty($absensi->jam_masuk)) {
                    $terlaksana++;
                    $detail[$tgl] = ['status' => 'hadir', 'jam' => $absensi->jam_masuk];
                } else {
                    $detail[$tgl] = ['status' => 'tidak_hadir', 'jam' => null];
                }
            }

            $current->modify('+1 day');
        }

        $persentase = $totalTugas > 0 ? round(($terlaksana / $totalTugas) * 100, 2) : 100;

        return [
            'persentase' => $persentase,
            'total_tugas' => $totalTugas,
            'terlaksana' => $terlaksana,
            'detail' => $detail,
        ];
    }

    // --- Helpers ---

    private function getHolidays($tgl_mulai, $tgl_selesai)
    {
        $rows = DB::table('absensi_hari_libur')
            ->where('tanggal', '>=', $tgl_mulai)
            ->where('tanggal', '<=', $tgl_selesai)
            ->get()->all();

        $result = [];
        foreach ($rows as $r) {
            $result[$r->tanggal] = $r->keterangan;
        }

        return $result;
    }

    private function getApprovedLeaves($tgl_mulai, $tgl_selesai)
    {
        $rows = DB::table('absensi_izin')
            ->where('status', 'approved')
            ->where('tgl_mulai', '<=', $tgl_selesai)
            ->where('tgl_selesai', '>=', $tgl_mulai)
            ->get()->all();

        $result = [];
        foreach ($rows as $r) {
            $start = max(new \DateTime($r->tgl_mulai), new \DateTime($tgl_mulai));
            $end = min(new \DateTime($r->tgl_selesai), new \DateTime($tgl_selesai));
            $period = new \DatePeriod($start, new \DateInterval('P1D'), $end->modify('+1 day'));
            foreach ($period as $d) {
                $tgl = $d->format('Y-m-d');
                $result[(int) $r->id_guru][$tgl] = $r->jenis;
            }
        }

        return $result;
    }

    // TODO: tabel kelas_jadwal_mapel, jabatan_guru belum ada migrasinya
    private function getScheduleMap($id_tp, $id_smt)
    {
        $jadwal = DB::table('kelas_jadwal_mapel as jm')
            ->select('jm.*', 'jb.id_guru')
            ->leftJoin('jabatan_guru as jb', function ($join) {
                $join->on('jb.id_tp', '=', 'jm.id_tp')
                    ->on('jb.id_smt', '=', 'jm.id_smt');
            })
            ->where('jm.id_tp', $id_tp)
            ->where('jm.id_smt', $id_smt)
            ->get()->all();

        $guruMap = [];
        foreach ($jadwal as $row) {
            if (empty($row->id_guru)) {
                continue;
            }
            $mapels = @unserialize($row->mapel_kelas ?? '', ['allowed_classes' => false]);
            if ($mapels === false && ! empty($row->mapel_kelas) && $row->mapel_kelas !== 'b:0;') {
                $mapels = json_decode($row->mapel_kelas, true);
            }
            if (! is_array($mapels)) {
                continue;
            }

            $assignedGuru = null;
            foreach ($mapels as $m) {
                if (isset($m['kelas_mapel']) && is_array($m['kelas_mapel'])) {
                    foreach ($m['kelas_mapel'] as $km) {
                        if (isset($km['kelas']) && (int) $km['kelas'] === (int) $row->id_kelas) {
                            $assignedGuru = (int) $row->id_guru;
                            break 2;
                        }
                    }
                }
            }

            if ($assignedGuru) {
                $guruMap[$assignedGuru][(int) $row->id_hari][] = [
                    'id_kelas' => $row->id_kelas,
                    'id_mapel' => $row->id_mapel,
                    'jam_ke' => $row->jam_ke,
                ];
            }
        }

        return $guruMap;
    }

    // TODO: tabel piket_jadwal belum ada migrasinya
    private function getPiketMap($id_tp, $id_smt)
    {
        $rows = DB::table('piket_jadwal')
            ->where('id_tp', $id_tp)
            ->where('id_smt', $id_smt)
            ->get()->all();

        $result = [];
        foreach ($rows as $r) {
            $result[] = ['id_guru' => (int) $r->id_guru, 'hari' => $r->hari];
        }

        return $result;
    }

    private function getPredikat($skor)
    {
        if ($skor >= 90) {
            return 'A';
        }
        if ($skor >= 75) {
            return 'B';
        }
        if ($skor >= 60) {
            return 'C';
        }
        if ($skor >= 45) {
            return 'D';
        }

        return 'E';
    }
}
