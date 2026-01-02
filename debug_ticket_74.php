<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ticket;
use Carbon\Carbon;

$ticket = Ticket::with('replies')->find(74);

if ($ticket) {
    echo "Tciket ID: 74\n";
    echo "Created At: " . $ticket->created_at . "\n";
    foreach ($ticket->replies as $r) {
        echo "Reply ID: {$r->id} | Status: {$r->update_status} | Created: {$r->created_at} | Updated: {$r->updated_at} | Tgl: {$r->tanggal_kunjungan} | Jam: {$r->jam_kunjungan}\n";
    }
} else {
    echo "Ticket 74 not found in recent ID list assumption. Dumping last reply of a recent done ticket.\n";
    $ticket = Ticket::whereHas('replies', function($q) { $q->where('update_status', 'done'); })->orderBy('id', 'desc')->first();
    echo "Ticket ID: {$ticket->id}\n";
    echo "Created At: " . $ticket->created_at . "\n";
    foreach ($ticket->replies as $r) {
        echo "Reply ID: {$r->id} | Status: {$r->update_status} | Created: {$r->created_at} | Updated: {$r->updated_at} | Tgl: {$r->tanggal_kunjungan} | Jam: {$r->jam_kunjungan}\n";
    }
}
