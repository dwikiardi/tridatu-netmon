<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "PHP Current Time: " . Carbon::now()->toDateTimeString() . " (" . Carbon::now()->timezoneName . ")\n";

$res = DB::select('SELECT NOW() as now, @@session.time_zone as tz, @@global.time_zone as gtz');
print_r($res);
