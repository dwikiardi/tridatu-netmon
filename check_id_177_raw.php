<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$res = DB::select('SELECT tanggal_kunjungan FROM ticket_replies WHERE id = 177');
var_dump($res[0]->tanggal_kunjungan);
