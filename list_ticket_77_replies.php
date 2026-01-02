<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ticket;

$ticketId = 77;
$ticket = Ticket::with('replies')->find($ticketId);

if (!$ticket) {
    die("Ticket not found\n");
}

echo "All Replies for Ticket 77:\n";
foreach ($ticket->replies as $r) {
    echo "ID: {$r->id} | Status: {$r->update_status} | Manual: " . ($r->tanggal_kunjungan ? $r->tanggal_kunjungan->format('Y-m-d') : '-') . " " . ($r->jam_kunjungan ?: '-') . " | Created: {$r->created_at}\n";
}
