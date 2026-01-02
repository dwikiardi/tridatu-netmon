<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ticket;
use Carbon\Carbon;

// Find ticket ID 77
$ticket = Ticket::with('replies')->where('id', 77)->first();

if (!$ticket) {
    // Try finding by explicit ID just in case
    $ticket = Ticket::with('replies')->find(77);
}

if ($ticket) {
    echo "Ticket ID: {$ticket->id}\n";
    echo "Created At: " . $ticket->created_at . "\n";
    echo "SLA Total (Downtime): " . $ticket->sla_total_minutes . "m\n";
    echo "SLA Onsite (MTTR Resolve): " . $ticket->sla_onsite_minutes . "m\n";
    
    foreach ($ticket->replies as $r) {
        echo "Reply ID: {$r->id} | Status: {$r->update_status} | Created: {$r->created_at} | Tgl: {$r->tanggal_kunjungan} | Jam: {$r->jam_kunjungan}\n";
    }
} else {
    echo "Ticket 77 not found.\n";
}
