<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$deleted = DB::table('migrations')
    ->where('migration', '2026_11_17_153323_add_pendidikan_to_master_guru_table')
    ->delete();

echo "Deleted rows: $deleted\n";
