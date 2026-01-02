<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$ticketId = 77;
$replies = DB::table('ticket_replies')->where('ticket_id', $ticketId)->get();

echo "Raw Replies for Ticket 77 (from DB::table):\n";
foreach ($replies as $r) {
    echo "ID: {$r->id} | Status: {$r->update_status} | Tgl: {$r->tanggal_kunjungan} | Jam: {$r->jam_kunjungan} | Created: {$r->created_at}\n";
}
