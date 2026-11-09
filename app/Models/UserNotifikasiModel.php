<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Port Tahap 1A dari CI4: app/Models/UserNotifikasiModel.php
 * Tabel: user_notifikasi
 * Tidak ada foreign key di migrasi CI4, jadi model ini belum punya relasi;
 * relasi ditambahkan di tahap berikutnya bila FK didefinisikan.
 *
 * Mapping kanal personal per user (Telegram chat id + nomor WhatsApp) dan
 * preferensi notifikasi personal.
 */
class UserNotifikasiModel extends Model
{
    protected $table = 'user_notifikasi';

    protected $fillable = [
        'user_id',
        'telegram_chat_id',
        'prefs',
        'link_token',
        'link_expires',
        'wa_number',
        'wa_verified',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'link_expires' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const PREF_DEFAULTS = [
        'denda' => true,
        'teguran' => true,
        'alpha' => true,
        'kehadiran' => true,
        'izin' => true,
        'koreksi' => true,
        'guru_terawal' => true,
        'panggilan' => true,
    ];

    /** Alasan bindChatDirect()/bindChat() menolak, untuk ditampilkan ke user. */
    public string $lastBindError = '';

    public function getByUserId(int $userId): ?object
    {
        if ($userId <= 0) {
            return null;
        }
        try {
            return $this->where('user_id', $userId)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getByChatId(string $chatId): ?object
    {
        if ($chatId === '') {
            return null;
        }
        try {
            return $this->where('telegram_chat_id', $chatId)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getByLinkToken(string $token): ?object
    {
        if ($token === '') {
            return null;
        }
        try {
            return $this->where('link_token', $token)
                ->where('link_expires', '>=', now()->format('Y-m-d H:i:s'))
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Terbitkan token tautan 15 menit untuk user (ditukar via /start <token>).
     */
    public function issueLinkToken(int $userId): ?string
    {
        if ($userId <= 0) {
            return null;
        }
        try {
            $token = bin2hex(random_bytes(16));
            $row = $this->getByUserId($userId);
            $data = [
                'link_token' => $token,
                'link_expires' => date('Y-m-d H:i:s', time() + 900),
            ];
            if ($row) {
                if ($row->update($data) === false) {
                    // Produksi: update() mengembalikan false, bukan melempar.
                    // Tanpa pemeriksaan ini user tetap diberi token yang tidak
                    // pernah bisa menyelesaikan /start.
                    Log::error('[notify] issueLinkToken: gagal menyimpan token untuk user '.$userId);

                    return null;
                }
            } else {
                $data['user_id'] = $userId;
                if (! static::query()->create($data)) {
                    Log::error('[notify] issueLinkToken: gagal membuat baris untuk user '.$userId);

                    return null;
                }
            }

            return $token;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Ikat chat_id ke user pemilik token tautan (satu chat = satu user).
     */
    public function bindChat(string $token, string $chatId): ?int
    {
        $row = $this->getByLinkToken($token);
        if (! $row) {
            return null;
        }
        try {
            // Jalur ini memakai token bertanda tangan yang hanya bisa
            // diselesaikan dari chat milik sendiri, jadi chat_id di sini
            // sudah terbukti milik pemohon. Tetap diperiksa supaya satu
            // chat tidak pernah tertaut ke dua akun.
            $pemilik = $this->where('telegram_chat_id', $chatId)
                ->where('user_id', '!=', (int) $row->user_id)
                ->first();

            if ($pemilik !== null) {
                Log::warning('[notify] bindChat: chat id sudah milik user '
                    .$pemilik->user_id.', permintaan dari user '.$row->user_id.' ditolak.');

                return null;
            }

            $tersimpan = $row->update([
                'telegram_chat_id' => $chatId,
                'link_token' => null,
                'link_expires' => null,
            ]);
            if ($tersimpan === false) {
                Log::error('[notify] bindChat: gagal menyimpan chat id untuk user '.$row->user_id);
                $this->lastBindError = 'Gagal menyimpan tautan. Coba lagi nanti.';

                return null;
            }

            return (int) $row->user_id;
        } catch (\Throwable $e) {
            Log::error('bindChat gagal: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Ikat chat_id ke user secara langsung (tanpa /start <token>).
     * Dipakai saat admin atau guru mengetikkan ID Telegram-nya sendiri.
     *
     * Chat yang sudah tertaut ke user lain TIDAK lagi direbut diam-diam.
     * Sebelumnya baris milik user lain dikosongkan tanpa izin, sehingga
     * guru yang mengetik chat id rekan kerjanya diam-diam mematikan
     * notifikasi rekannya — dan tidak ada yang diberi tahu.
     *
     * Chat yang terlanjur dipakai user lain berarti input salah: satu chat
     * hanya bisa dimiliki satu akun. Penolakan ini Return pesan
     * eksplisit supaya UI bisa menampilkannya ke pengguna.
     */
    public function bindChatDirect(int $userId, string $chatId): bool
    {
        if ($userId <= 0 || $chatId === '') {
            return false;
        }
        try {
            $pemilik = $this->where('telegram_chat_id', $chatId)
                ->where('user_id', '!=', $userId)
                ->first();

            if ($pemilik !== null) {
                Log::warning('[notify] user '.$userId
                    .' mencoba memakai chat id yang sudah milik user '.$pemilik->user_id
                    .' — ditolak.');
                $this->lastBindError = 'ID Telegram ini sudah tertaut ke akun lain.';

                return false;
            }

            $row = $this->getByUserId($userId);
            if ($row) {
                $row->update([
                    'telegram_chat_id' => $chatId,
                    'link_token' => null,
                    'link_expires' => null,
                ]);
            } else {
                static::query()->create([
                    'user_id' => $userId,
                    'telegram_chat_id' => $chatId,
                ]);
            }
            $this->lastBindError = '';

            return true;
        } catch (\Throwable $e) {
            Log::error('bindChatDirect gagal: '.$e->getMessage());
            $this->lastBindError = 'Gagal menyimpan tautan. Coba lagi nanti.';

            return false;
        }
    }

    public function unbindByChatId(string $chatId): bool
    {
        try {
            // CI4 update() mengembalikan bool sukses; Laravel mengembalikan
            // jumlah baris — query yang berjalan tanpa exception dianggap
            // sukses seperti CI4.
            static::query()->where('telegram_chat_id', $chatId)
                ->update(['telegram_chat_id' => null]);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getPrefs(int $userId): array
    {
        $row = $this->getByUserId($userId);
        $prefs = self::PREF_DEFAULTS;
        if ($row && ! empty($row->prefs)) {
            $decoded = json_decode((string) $row->prefs, true);
            if (is_array($decoded)) {
                foreach ($prefs as $k => $v) {
                    if (array_key_exists($k, $decoded)) {
                        $prefs[$k] = (bool) $decoded[$k];
                    }
                }
            }
        }

        return $prefs;
    }

    /**
     * Simpan preferensi (hanya kunci yang dikenal).
     */
    public function setPrefs(int $userId, array $prefs): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $clean = self::PREF_DEFAULTS;
        foreach ($clean as $k => $v) {
            if (array_key_exists($k, $prefs)) {
                $clean[$k] = (bool) $prefs[$k];
            }
        }
        try {
            $row = $this->getByUserId($userId);
            $data = ['prefs' => json_encode($clean)];
            if ($row) {
                return (bool) $row->update($data);
            }
            $data['user_id'] = $userId;

            return (bool) static::query()->create($data);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Daftar akun tertaut untuk halaman admin (nama + chat + prefs).
     *
     * @return array<int, object>
     */
    // TODO: tabel master_guru, users belum ada migrasinya
    public function getAllLinked(): array
    {
        try {
            return DB::table('user_notifikasi as n')
                ->select('n.user_id', 'n.telegram_chat_id', 'n.prefs', 'n.updated_at', 'u.username', 'u.first_name', 'u.last_name', 'g.nama_guru')
                ->leftJoin('users as u', 'u.id', '=', 'n.user_id')
                ->leftJoin('master_guru as g', 'g.id_user', '=', 'n.user_id')
                ->whereNotNull('n.telegram_chat_id')
                ->where('n.telegram_chat_id', '!=', '')
                ->orderBy('g.nama_guru', 'ASC')
                ->get()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ── WhatsApp personal ──────────────────────────────────────────────

    /**
     * Nomor WhatsApp yang sudah dikonfirmasi untuk sebuah user.
     *
     * Hanya baris dengan wa_verified = 1 yang dikembalikan: nomor yang
     * baru diketik guru belum tentu miliknya (salah ketik digit), dan
     * OpenWA tidak punya mekanisme verifikasi inbound seperti bot
     * Telegram. Kiriman ke nomor yang salah berarti membocorkan data
     * absensi ke orang tak dikenal, jadi konfirmasi admin itu wajib.
     *
     * @return string '' bila belum ada / belum dikonfirmasi
     */
    // TODO: kolom wa_number dan wa_verified belum ada di migrasi user_notifikasi (2026_11_09_000051)
    public function getVerifiedWaNumber(int $userId): string
    {
        if ($userId <= 0) {
            return '';
        }
        try {
            if (! Schema::hasColumn($this->getTable(), 'wa_number')) {
                return '';
            }
            $row = $this->where('user_id', $userId)
                ->whereNotNull('wa_number')
                ->where('wa_number', '!=', '')
                ->where('wa_verified', 1)
                ->first();

            return $row ? (string) $row->wa_number : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Nomor WA apa adanya, Terverifikasi atau belum (untuk UI admin). */
    // TODO: kolom wa_number dan wa_verified belum ada di migrasi user_notifikasi (2026_11_09_000051)
    public function getWaNumber(int $userId): string
    {
        $row = $this->getByUserId($userId);

        return ($row && ! empty($row->wa_number)) ? (string) $row->wa_number : '';
    }

    /**
     * Simpan nomor WA untuk sebuah user.
     *
     * Berbeda dengan Telegram, tidak ada "/start <token>": nomor diisi
     * langsung. $verified kontrol apakah nomor langsung aktif atau masih
     * menunggu konfirmasi admin.
     *
     * PENTING: kalau nomornya TIDAK berubah, status verifikasi yang sudah ada
     * dipertahankan. Sebelumnya setiap penyimpanan ulang menulis
     * `wa_verified = 0` apa pun isinya — jadi mengubah satu field lain di
     * profil (mis. alamat email) diam-diam mencabut konfirmasi admin, dan
     * guru harus dikonfirmasi ulang setiap kali menyimpan apa pun.
     *
     * Nomor kosong berarti melepas tautan.
     */
    // TODO: kolom wa_number dan wa_verified belum ada di migrasi user_notifikasi (2026_11_09_000051)
    public function setWaNumber(int $userId, string $number, bool $verified = false): bool
    {
        if ($userId <= 0) {
            return false;
        }
        try {
            $row = $this->getByUserId($userId);

            if ($row && (string) $row->wa_number === $number && $number !== '') {
                // Nomor sama persis. Status yang ada sudah sah — jangan
                // diturunkan hanya karena form dikirim ulang.
                return true;
            }

            $data = [
                'wa_number' => $number === '' ? null : $number,
                'wa_verified' => $number === '' ? 0 : ($verified ? 1 : 0),
            ];
            if ($row) {
                return (bool) $row->update($data);
            }
            if ($number === '') {
                return true; // tidak ada yang perlu dilepas
            }
            $data['user_id'] = $userId;

            return (bool) static::query()->create($data);
        } catch (\Throwable $e) {
            Log::error('setWaNumber gagal: '.$e->getMessage());

            return false;
        }
    }

    /** Tandai nomor WA milik user sebagai sudah dikonfirmasi admin. */
    // TODO: kolom wa_number dan wa_verified belum ada di migrasi user_notifikasi (2026_11_09_000051)
    public function verifyWaNumber(int $userId): bool
    {
        $number = $this->getWaNumber($userId);
        if ($number === '') {
            return false;
        }
        try {
            // CI4 update() mengembalikan bool sukses; Laravel mengembalikan
            // jumlah baris — query yang berjalan tanpa exception dianggap
            // sukses seperti CI4.
            static::query()->where('user_id', $userId)->update(['wa_verified' => 1]);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Lepas tautan WA seorang user. */
    // TODO: kolom wa_number dan wa_verified belum ada di migrasi user_notifikasi (2026_11_09_000051)
    public function unbindWa(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        try {
            // CI4 update() mengembalikan bool sukses; Laravel mengembalikan
            // jumlah baris — query yang berjalan tanpa exception dianggap
            // sukses seperti CI4.
            static::query()->where('user_id', $userId)
                ->update(['wa_number' => null, 'wa_verified' => 0]);

            return true;
        } catch (\Throwable $e) {
            Log::error('unbindWa gagal: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Daftar akun yang punya nomor WhatsApp, untuk halaman admin.
     *
     * @return array<int, object>
     */
    // TODO: tabel master_guru, users belum ada migrasinya
    // TODO: kolom wa_number dan wa_verified belum ada di migrasi user_notifikasi (2026_11_09_000051)
    public function getAllWaLinked(): array
    {
        try {
            return DB::table('user_notifikasi as n')
                ->select('n.user_id', 'n.wa_number', 'n.wa_verified', 'n.updated_at', 'u.username', 'u.first_name', 'u.last_name', 'g.nama_guru', 'g.wa_number as wa_number_guru')
                ->leftJoin('users as u', 'u.id', '=', 'n.user_id')
                ->leftJoin('master_guru as g', 'g.id_user', '=', 'n.user_id')
                ->whereNotNull('n.wa_number')
                ->where('n.wa_number', '!=', '')
                ->orderBy('g.nama_guru', 'ASC')
                ->get()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Berapa user yang sudah punya nomor WA terverifikasi — dipakai untuk
     * ringkasan di halaman pengaturan.
     */
    // TODO: kolom wa_number dan wa_verified belum ada di migrasi user_notifikasi (2026_11_09_000051)
    public function countVerifiedWa(): int
    {
        try {
            return (int) $this->where('wa_verified', 1)
                ->whereNotNull('wa_number')
                ->where('wa_number', '!=', '')
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
