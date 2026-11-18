<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Kolom master_guru ===\n";
$cols = Schema::getColumnListing('master_guru');
foreach ($cols as $c) {
    echo "  - $c\n";
}

echo "\n=== Jumlah data master_guru ===\n";
$count = DB::table('master_guru')->count();
echo "  Total: $count\n";

echo "\n=== 5 data guru pertama ===\n";
$rows = DB::table('master_guru')->limit(5)->get();
foreach ($rows as $r) {
    echo '  id_guru='.($r->id_guru ?? 'N/A').' | nama='.($r->nama_guru ?? 'N/A').' | nip='.($r->nip ?? 'N/A').' | kode_guru='.($r->kode_guru ?? 'N/A').' | id_user='.($r->id_user ?? 'N/A')."\n";
}

echo "\n=== Cek kolom nomor_rfid ===\n";
echo Schema::hasColumn('master_guru', 'nomor_rfid') ? "  ADA\n" : "  TIDAK ADA\n";

echo "\n=== Cek kolom pendidikan ===\n";
echo Schema::hasColumn('master_guru', 'pendidikan') ? "  ADA\n" : "  TIDAK ADA\n";
