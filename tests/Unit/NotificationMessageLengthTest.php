<?php

namespace Tests\Unit;

use App\Services\NotificationService;
use App\Services\WhatsAppFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationMessageLengthTest extends TestCase
{
    use RefreshDatabase;

    private function extractMessageSent(NotificationService $service, callable $callback): string
    {
        $captured = '';
        // NotificationService uses notifyGrup which calls sendTelegram and sendWhatsAppGrup
        // We can subclass or use reflection to inspect message
        $reflection = new \ReflectionClass($service);
        $notifyGrupMethod = $reflection->getMethod('notifyGrup');
        $notifyGrupMethod->setAccessible(true);

        return $captured;
    }

    public function test_catatan_block_does_not_truncate_long_sentence(): void
    {
        $service = new NotificationService;
        $ref = new \ReflectionClass($service);
        $method = $ref->getMethod('catatanBlock');
        $method->setAccessible(true);

        $longAlasan = 'Surat keterangan sakit dari dokter belum dilampirkan secara lengkap dan jelas, mohon unggah ulang surat keterangan yang sah melalui menu pengajuan izin agar dapat diverifikasi oleh admin.';

        $result = $method->invoke($service, $longAlasan, 'Alasan: ');

        $this->assertStringContainsString($longAlasan, $result);
        $this->assertStringNotContainsString('…', $result);
    }

    public function test_permit_rejected_message_preserves_full_rejection_reason(): void
    {
        $service = new class extends NotificationService
        {
            public string $lastSentMessage = '';

            public function isChannelEnabled(string $jenis, string $channel): bool
            {
                return true;
            }

            public function sendTelegram(string $message): bool
            {
                $this->lastSentMessage = $message;

                return true;
            }

            public function sendWhatsApp(string $message): bool
            {
                return true;
            }
        };

        $longAlasan = 'Pengajuan izin tidak dapat disetujui karena pada tanggal tersebut sekolah sedang melaksanakan Asesmen Nasional Berbasis Komputer (ANBK) dan seluruh guru diharapkan hadir untuk mendampingi siswa di lab komputer.';

        $service->notifyPermitRejected('Budi Santoso, S.Pd.', 'ijin', null, $longAlasan);

        $msg = $service->lastSentMessage;
        $this->assertStringContainsString('❌ <b>IZIN DITOLAK</b>', $msg);
        $this->assertStringContainsString('Nama: Budi Santoso, S.Pd.', $msg);
        $this->assertStringContainsString('Alasan: '.$longAlasan, $msg);
        $this->assertStringNotContainsString('…', $msg);

        // Check WhatsApp formatting as well
        $waMsg = WhatsAppFormatter::toWhatsApp($msg, false);
        $this->assertStringContainsString('*IZIN DITOLAK*', $waMsg);
        $this->assertStringContainsString('Alasan: '.$longAlasan, $waMsg);
        $this->assertStringNotContainsString('…', $waMsg);
    }

    public function test_permit_submitted_message_preserves_full_reason(): void
    {
        $service = new class extends NotificationService
        {
            public string $lastSentMessage = '';

            public function isChannelEnabled(string $jenis, string $channel): bool
            {
                return true;
            }

            public function sendTelegram(string $message): bool
            {
                $this->lastSentMessage = $message;

                return true;
            }

            public function sendWhatsApp(string $message): bool
            {
                return true;
            }
        };

        $longReason = 'Mengantar orang tua yang sedang sakit keras untuk menjalani pemeriksaan dan kontrol rutin spesialis di RSUD Dr. Soetomo Surabaya seharian.';

        $service->notifyPermitSubmitted('Siti Aminah, M.Pd.', 'sakit', '20/11/2026', '21/11/2026', 2, $longReason);

        $msg = $service->lastSentMessage;
        $this->assertStringContainsString('📨 <b>PENGAJUAN BARU — Sakit</b>', $msg);
        $this->assertStringContainsString('Nama: Siti Aminah, M.Pd.', $msg);
        $this->assertStringContainsString('Alasan: '.$longReason, $msg);
        $this->assertStringNotContainsString('…', $msg);

        $waMsg = WhatsAppFormatter::toWhatsApp($msg, false);
        $this->assertStringContainsString('*PENGAJUAN BARU — Sakit*', $waMsg);
        $this->assertStringContainsString('Alasan: '.$longReason, $waMsg);
        $this->assertStringNotContainsString('…', $waMsg);
    }

    public function test_dinas_rejected_message_preserves_full_rejection_reason(): void
    {
        $service = new class extends NotificationService
        {
            public string $lastSentMessage = '';

            public function isChannelEnabled(string $jenis, string $channel): bool
            {
                return true;
            }

            public function sendTelegram(string $message): bool
            {
                $this->lastSentMessage = $message;

                return true;
            }

            public function sendWhatsApp(string $message): bool
            {
                return true;
            }
        };

        $longAlasan = 'Surat tugas dinas luar dari dinas pendidikan belum ditandatangani oleh pejabat yang berwenang, mohon lengkapi tanda tangan dan stempel resmi lalu ajukan verifikasi ulang.';

        $service->notifyDinasRejected('Drs. Joko Widodo', '2026-11-20', null, $longAlasan);

        $msg = $service->lastSentMessage;
        $this->assertStringContainsString('❌ <b>DINAS DITOLAK</b>', $msg);
        $this->assertStringContainsString('Nama: Drs. Joko Widodo', $msg);
        $this->assertStringContainsString('Alasan: '.$longAlasan, $msg);
        $this->assertStringNotContainsString('…', $msg);

        $waMsg = WhatsAppFormatter::toWhatsApp($msg, false);
        $this->assertStringContainsString('*DINAS DITOLAK*', $waMsg);
        $this->assertStringContainsString('Alasan: '.$longAlasan, $waMsg);
        $this->assertStringNotContainsString('…', $waMsg);
    }

    public function test_dinas_submitted_message_preserves_full_keterangan(): void
    {
        $service = new class extends NotificationService
        {
            public string $lastSentMessage = '';

            public function isChannelEnabled(string $jenis, string $channel): bool
            {
                return true;
            }

            public function sendTelegram(string $message): bool
            {
                $this->lastSentMessage = $message;

                return true;
            }

            public function sendWhatsApp(string $message): bool
            {
                return true;
            }
        };

        $longKeterangan = 'Menghadiri Workshop Peningkatan Kompetensi Guru Penggerak dan Implementasi Kurikulum Merdeka di Gedung Guru Indonesia Provinsi Jawa Timur bersama tim kurikulum sekolah.';

        $service->notifyDinasSubmitted('Ahmad Fauzi, S.Pd.', '2026-11-20', $longKeterangan);

        $msg = $service->lastSentMessage;
        $this->assertStringContainsString('🚗 <b>PENGAJUAN DINAS BARU</b>', $msg);
        $this->assertStringContainsString('Nama: Ahmad Fauzi, S.Pd.', $msg);
        $this->assertStringContainsString('Keterangan: '.$longKeterangan, $msg);
        $this->assertStringNotContainsString('…', $msg);

        $waMsg = WhatsAppFormatter::toWhatsApp($msg, false);
        $this->assertStringContainsString('*PENGAJUAN DINAS BARU*', $waMsg);
        $this->assertStringContainsString('Keterangan: '.$longKeterangan, $waMsg);
        $this->assertStringNotContainsString('…', $waMsg);
    }

    public function test_permit_approved_preserves_full_note(): void
    {
        $service = new class extends NotificationService
        {
            public string $lastSentMessage = '';

            public function isChannelEnabled(string $jenis, string $channel): bool
            {
                return true;
            }

            public function sendTelegram(string $message): bool
            {
                $this->lastSentMessage = $message;

                return true;
            }

            public function sendWhatsApp(string $message): bool
            {
                return true;
            }
        };

        $longCatatan = 'Disetujui dengan catatan tugas piket dialihkan ke guru pengganti dan seluruh materi pembelajaran daring sudah diunggah di LMS sekolah.';

        $service->notifyPermitApproved('Dewi Lestari, S.Pd.', 'ijin', '2026-11-20', '2026-11-21', null, $longCatatan);

        $msg = $service->lastSentMessage;
        $this->assertStringContainsString('✅ <b>IZIN DISETUJUI</b>', $msg);
        $this->assertStringContainsString('Catatan: '.$longCatatan, $msg);
        $this->assertStringNotContainsString('…', $msg);

        $waMsg = WhatsAppFormatter::toWhatsApp($msg, false);
        $this->assertStringContainsString('*IZIN DISETUJUI*', $waMsg);
        $this->assertStringContainsString('Catatan: '.$longCatatan, $waMsg);
        $this->assertStringNotContainsString('…', $waMsg);
    }

    public function test_dinas_approved_preserves_full_note(): void
    {
        $service = new class extends NotificationService
        {
            public string $lastSentMessage = '';

            public function isChannelEnabled(string $jenis, string $channel): bool
            {
                return true;
            }

            public function sendTelegram(string $message): bool
            {
                $this->lastSentMessage = $message;

                return true;
            }

            public function sendWhatsApp(string $message): bool
            {
                return true;
            }
        };

        $longCatatan = 'Dinas disetujui, mohon membawa surat tugas fisik dan meminta stempel serta tanda tangan dari pihak penyelenggara kegiatan di lokasi.';

        $service->notifyDinasApproved('Bambang Sutrisno, M.Pd.', '2026-11-20', null, $longCatatan);

        $msg = $service->lastSentMessage;
        $this->assertStringContainsString('✅ <b>DINAS DISETUJUI</b>', $msg);
        $this->assertStringContainsString('Catatan: '.$longCatatan, $msg);
        $this->assertStringNotContainsString('…', $msg);

        $waMsg = WhatsAppFormatter::toWhatsApp($msg, false);
        $this->assertStringContainsString('*DINAS DISETUJUI*', $waMsg);
        $this->assertStringContainsString('Catatan: '.$longCatatan, $waMsg);
        $this->assertStringNotContainsString('…', $waMsg);
    }

    public function test_rekap_piket_preserves_longer_reason(): void
    {
        $service = new class extends NotificationService
        {
            public string $lastSentMessage = '';

            public function isChannelEnabled(string $jenis, string $channel): bool
            {
                return true;
            }

            public function sendTelegram(string $message): bool
            {
                $this->lastSentMessage = $message;

                return true;
            }

            public function sendWhatsApp(string $message): bool
            {
                return true;
            }
        };

        $longAlasan = 'Izin dinas luar mendampingi siswa mengikuti Lomba Cerdas Cermat tingkat Kabupaten';
        $this->assertGreaterThan(80, mb_strlen($longAlasan));

        $data = [
            'tanggal' => '2026-11-20',
            'guru_absen' => [
                [
                    'nama' => 'Guru Teladan',
                    'alpha' => false,
                    'alasan' => $longAlasan,
                ],
            ],
        ];

        $service->notifyRekapPiket($data);

        $msg = $service->lastSentMessage;
        $this->assertStringContainsString($longAlasan, $msg);
        $this->assertStringNotContainsString('…', $msg);
    }
}
