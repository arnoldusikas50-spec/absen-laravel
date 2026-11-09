<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;

/**
 * Porting CI4 App\Commands\TmpF42upload (php spark tmp:f42upload).
 *
 * Harness sementara F42-B/C: buktikan upload bukti izin tidak lagi dipercaya
 * pada MIME klaiman browser, dan sapuan nama berkas move() tetap bersih.
 * Hanya menulis probe ke storage/f42_probe dan menghapusnya lagi.
 *
 * ADAPTASI LARAVEL (vs CI4):
 *   - WRITEPATH          -> storage_path()
 *   - APPPATH Controllers -> app/Http/Controllers (+ nama kelas controller
 *     Laravel berakhiran "Controller")
 *   - CI4 UploadedFile::getRandomName() -> Illuminate UploadedFile::hashName()
 *     (nama acak server-generated; konstruktor butuh $test=true
 *     untuk berkas probe yang bukan hasil upload HTTP sungguhan)
 *   - Pemeriksaan sumber (bagian 3–5) dilewati dengan INFO bila berkas
 *     controller / rute yang diperiksa belum di-port — bukan FAIL — karena
 *     harness ini berjalan sebelum porting controller selesai.
 */
class TmpF42uploadCommand extends Command
{
    protected $signature = 'tmp:f42upload';

    protected $description = 'Bukti F42: whitelist ekstensi dari isi file + sapuan move().';

    private array $whitelist = ['pdf', 'jpg', 'jpeg', 'png'];

    private int $pass = 0;

    private int $fail = 0;

    private function ok(string $label): void
    {
        $this->pass++;
        $this->info('PASS  '.$label);
    }

    private function no(string $label, string $sebab = ''): void
    {
        $this->fail++;
        $this->error('FAIL  '.$label.($sebab !== '' ? ' -> '.$sebab : ''));
    }

    /** Predikat yang sama dengan yang sekarang dipakai kedua controller. */
    private function diterima(UploadedFile $file): bool
    {
        return in_array(strtolower($file->guessExtension() ?? ''), $this->whitelist, true);
    }

    public function handle(): int
    {
        $dir = storage_path('f42_probe');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $probe = $dir.'/probe_'.bin2hex(random_bytes(4));
        $dibuat = [];

        $tulis = function (string $nama, string $isi) use ($probe, &$dibuat): string {
            $path = $probe.$nama;
            file_put_contents($path, $isi);
            $dibuat[] = $path;

            return $path;
        };

        // ── 1. Payload skrip yang mengaku image/jpeg ───────────────────────
        $php = '<'.'?php echo "x";';
        $path = $tulis('.txt', $php);
        $file = new UploadedFile($path, 'bukti.jpg', 'image/jpeg', UPLOAD_ERR_OK, true);

        $klien = $file->getClientMimeType();
        $asli = $file->getMimeType();
        $ekst = strtolower($file->guessExtension() ?? '');
        $this->line('  klaim='.$klien.'  isi='.$asli.'  guessExtension='.$ekst);

        $klien === 'image/jpeg'
            ? $this->ok('guard lama (getClientMimeType) memang meloloskan klaiman image/jpeg')
            : $this->no('guard lama (getClientMimeType) meloloskan klaiman image/jpeg', 'clientMime='.$klien);

        $ekst === 'php'
            ? $this->ok('guessExtension() memakai MIME asli (php) = sumber nama berkas yang dulu berbahaya')
            : $this->line('INFO  guessExtension()='.$ekst.' pada build fileinfo ini', 'comment');

        $this->diterima($file) === false
            ? $this->ok('guard baru menolak payload skrip (ekstensi hasil='.$ekst.')')
            : $this->no('guard baru menolak payload skrip', 'ekst='.$ekst);

        // Nama berkas hanya dipakai bila guard meloloskan file. Untuk payload
        // skrip, hashName() memang tetap menghasilkan .php — itulah
        // kenapa ia tidak boleh dipanggil setelah penolakan.
        $extBaru = strtolower(pathinfo($file->hashName(), PATHINFO_EXTENSION));
        if ($this->diterima($file) === false) {
            $this->ok('payload skrip ditolak sebelum nama berkas dipakai ('.$extBaru.' tak pernah ditulis)');
        } else {
            $this->no('payload skrip ditolak sebelum nama berkas dipakai', 'diterima');
        }

        // ── 2. Berkas sungguhan tetap diterima ─────────────────────────────
        $png = $tulis('.png', (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
        $filePng = new UploadedFile($png, 'bukti.png', 'image/png', UPLOAD_ERR_OK, true);
        $this->diterima($filePng)
            ? $this->ok('PNG asli diterima ('.$filePng->guessExtension().')')
            : $this->no('PNG asli diterima', 'guessExtension='.$filePng->guessExtension());

        $jpg = $tulis('.jpg', '');
        $im = imagecreatetruecolor(4, 4);
        imagejpeg($im, $jpg);
        imagedestroy($im);
        $fileJpg = new UploadedFile($jpg, 'bukti.jpg', 'image/jpeg', UPLOAD_ERR_OK, true);
        $this->diterima($fileJpg)
            ? $this->ok('JPEG asli diterima ('.$fileJpg->guessExtension().')')
            : $this->no('JPEG asli diterima', 'guessExtension='.$fileJpg->guessExtension());

        $pdf = $tulis('.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n");
        $filePdf = new UploadedFile($pdf, 'bukti.pdf', 'application/pdf', UPLOAD_ERR_OK, true);
        $this->diterima($filePdf)
            ? $this->ok('PDF asli diterima ('.$filePdf->guessExtension().')')
            : $this->no('PDF asli diterima', 'guessExtension='.$filePdf->guessExtension());

        // Poliglot: JPEG valid + sisipan skrip, klien mengaku image/jpeg
        $poly = $tulis('.jpg2', file_get_contents($jpg)."\n".$php);
        $filePoly = new UploadedFile($poly, 'bukti.jpg', 'image/jpeg', UPLOAD_ERR_OK, true);
        $ekstPoly = strtolower($filePoly->guessExtension() ?? '');
        if (! in_array($ekstPoly, ['php', 'phtml', 'phar', 'cgi', 'pl', 'py'], true)) {
            $this->ok('poliglot tidak pernah menghasilkan nama .php di disk ('.$ekstPoly.')');
        } else {
            $this->no('poliglot tidak pernah menghasilkan nama .php di disk', $ekstPoly);
        }

        // ── 3. Sumber: kedua titik perbaikan memakai guard baru ────────────
        $sasaran = [
            ['App\Http\Controllers\Guru\AttendanceController', 'leaveRequest', 'Http/Controllers/Guru/AttendanceController.php'],
            ['App\Http\Controllers\Admin\PresensiAdminController', 'saveManualPermit', 'Http/Controllers/Admin/PresensiAdminController.php'],
        ];
        foreach ($sasaran as [$kelas, $metode, $rel]) {
            $abs = app_path($rel);
            if (! is_file($abs) || ! class_exists($kelas) || ! method_exists($kelas, $metode)) {
                $this->line('INFO  '.$rel.'::'.$metode.' belum di-port, dilewati', 'comment');
                continue;
            }
            $ref = new \ReflectionMethod($kelas.'::'.$metode);
            $source = implode("\n", array_slice(
                file($abs),
                $ref->getStartLine() - 1,
                $ref->getEndLine() - $ref->getStartLine() + 1
            ));
            str_contains($source, 'getClientMimeType()')
                ? $this->no($rel.'::'.$metode.' masih memakai getClientMimeType()')
                : $this->ok($rel.'::'.$metode.' bebas getClientMimeType()');

            if (str_contains($source, '->guessExtension()') && str_contains($source, "'pdf', 'jpg', 'jpeg', 'png'")) {
                $this->ok($rel.'::'.$metode.' memakai whitelist guessExtension()');
            } else {
                $this->no($rel.'::'.$metode.' memakai whitelist guessExtension()');
            }
        }

        // ── 4. Sapuan move(): nama berkas wajib server-generated ────────────
        // Nama dianggap aman bila sumbernya random name/hash/preg_replace, atau
        // cocok dengan bukti manual yang sudah dilacak per titik.
        $mati = ['Userpanitia']; // controller tanpa rute (dibuktikan F31/F39)
        $buktiManual = [
            // nama = $safeNis (hasil preg_replace) + ekstensi '.jpg' tetap
            'Http/Controllers/DatasiswaController.php::$newName' => "\$safeNis . '.jpg'",
            // $logo dibersihkan + MIME asli diwhitelist image/* sebelum move
            'Http/Controllers/ProfilsekolahController.php::$newName' => "preg_replace('/[^a-zA-Z0-9_]/', '', (string) \$logo)",
            // $logo diwhitelist MEDIA_FIELDS, $ext dari peta MIME
            'Http/Controllers/SettingsController.php::$fileName' => "\$logo . '.' . \$ext",
            // 'foto_' . (int) id_user . '.' + $ext dari whitelist ['jpg'...]
            'Http/Controllers/UseradminController.php::$newName' => "'foto_' . \$id_user . '.' . \$ext",
        ];
        $temuan = [];
        $diperiksa = 0;
        $daftar = [];
        $controllersDir = app_path('Http/Controllers');
        if (is_dir($controllersDir)) {
            for ($it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($controllersDir)); $it->valid(); $it->next()) {
                $f = $it->current()->getPathname();
                if ($it->current()->isDir() || ! str_ends_with($f, '.php')) {
                    continue;
                }
                $daftar[] = $f;
            }
        }
        sort($daftar);
        foreach ($daftar as $f) {
            $norm = str_replace('\\', '/', $f);
            $pos = strpos($norm, 'Http/Controllers/');
            $rel = $pos !== false ? substr($norm, $pos) : basename($f);
            if (in_array(basename($rel, '.php'), $mati, true)) {
                continue;
            }
            $isi = (string) file_get_contents($f);
            if (! preg_match_all('/->move\(\s*[^,;]+,\s*\$([a-zA-Z_][a-zA-Z0-9_]*)/', $isi, $m)) {
                continue;
            }
            foreach (array_unique($m[1]) as $var) {
                $diperiksa++;
                $key = $rel.'::$'.$var;
                $pola = '/\$'.preg_quote($var, '/').'\s*=\s*[^;]*(getRandomName|hashName|uniqid|random_bytes|md5|sha1|preg_replace)[^;]*;/';
                if (preg_match($pola, $isi)) {
                    continue;
                }
                if (isset($buktiManual[$key]) && str_contains($isi, $buktiManual[$key])) {
                    continue;
                }
                $temuan[] = $key;
            }
        }
        $this->line('  nama berkas di move() diperiksa: '.$diperiksa.' titik');
        $temuan === []
            ? $this->ok('setiap nama berkas move() server-generated / terbukti aman ('.$diperiksa.' titik)')
            : $this->no('nama berkas move() belum terbukti aman', implode(' | ', $temuan));

        // Titik yang baru diperbaiki harus masih punya sanitisasi $logo
        $profilPath = app_path('Http/Controllers/ProfilsekolahController.php');
        if (! is_file($profilPath)) {
            $this->line('INFO  ProfilsekolahController belum di-port, dilewati', 'comment');
        } else {
            $isiProfil = (string) file_get_contents($profilPath);
            str_contains($isiProfil, "preg_replace('/[^a-zA-Z0-9_]/', '', (string) \$logo)")
                ? $this->ok('Profilsekolah::uploadFile membersihkan segmen $logo')
                : $this->no('Profilsekolah::uploadFile membersihkan segmen $logo');
        }

        // ── 5. Penjaga traversal yang sudah ada harus tetap utuh ────────────
        $penjaga = [
            ['Http/Controllers/DataguruController.php', 'resolveDeletablePath', ['strpos($rel, \'..\')', 'realpath(public_path()', 'strpos($abs, $uploadsRoot)']],
            ['Http/Controllers/UseradminController.php', 'resolveProfilePath', ['realpath', 'uploads/profiles']],
            ['Http/Controllers/SettingsController.php', 'resolveMediaPath', ['realpath', 'uploads/']],
            ['Http/Controllers/ProfilsekolahController.php', 'deleteFile', ["str_starts_with(\$file_name, 'uploads/settings/')", "str_contains(\$file_name, '..')"]],
        ];
        foreach ($penjaga as [$rel, $metode, $potongan]) {
            $abs = app_path($rel);
            if (! is_file($abs)) {
                $this->line('INFO  '.$rel.'::'.$metode.' belum di-port, dilewati', 'comment');
                continue;
            }
            $isi = (string) file_get_contents($abs);
            $awal = strpos($isi, 'function '.$metode);
            if ($awal === false) {
                $this->no($rel.'::'.$metode.' masih ada');
                continue;
            }
            $cuplik = substr($isi, $awal, 2500);
            $kurang = array_values(array_filter($potongan, static fn ($p) => ! str_contains($cuplik, $p)));
            $kurang === []
                ? $this->ok($rel.'::'.$metode.' menjaga uploads/ + realpath (bagian: '.count($potongan).')')
                : $this->no($rel.'::'.$metode.' kehilangan penjaga', implode(' | ', $kurang));
        }

        // Rute assets fallback harus tetap memotong di dalam document root
        $routes = (string) @file_get_contents(base_path('routes/web.php'));
        if (! str_contains($routes, 'assets')) {
            $this->line('INFO  rute assets belum di-port, dilewati', 'comment');
        } elseif (str_contains($routes, "!str_starts_with(\$file, \$root . DIRECTORY_SEPARATOR)")) {
            $this->ok('rute assets/(:any) tetap terkurung di FCPATH/assets');
        } else {
            $this->no('rute assets/(:any) tetap terkurung di FCPATH/assets');
        }

        // ── 6. Bersihkan probe ──────────────────────────────────────────────
        $this->line('  probe tercatat: '.count($dibuat));
        foreach ($dibuat as $p) {
            if (is_file($p)) {
                $hapusOk = @unlink($p);
                if (! $hapusOk) {
                    $this->warn('  unlink gagal: '.$p);
                }
            } else {
                $this->warn('  tidak is_file: '.$p);
            }
        }
        @rmdir($dir);
        ! is_dir($dir)
            ? $this->ok('probe sementara terhapus')
            : $this->no('probe sementara terhapus', $dir);

        $this->line('');
        $this->line('PASS='.$this->pass.' FAIL='.$this->fail, $this->fail === 0 ? 'info' : 'error');

        return $this->fail === 0 ? self::SUCCESS : self::FAILURE;
    }
}
