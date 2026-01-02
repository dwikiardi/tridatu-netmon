<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Http\Controllers\ticketing\TicketController;

$ticketId = 77;

// Update Reply 177 (Need Visit)
$rv = TicketReply::find(177);
if ($rv) {
    $rv->tanggal_kunjungan = '2025-12-30';
    $rv->jam_kunjungan = '10:00:00';
    $rv->save();
    echo "Updated Reply 177 to 2025-12-30 10:00\n";
}

// Update Reply 184 (Selesai)
$rs = TicketReply::find(184);
if ($rs) {
    $rs->tanggal_kunjungan = '2025-12-31';
    $rs->jam_kunjungan = '16:00:00';
    $rs->save();
    echo "Updated Reply 184 to 2025-12-31 16:00\n";
}

// Recalculate
$ticket = Ticket::find($ticketId);
$controller = new TicketController();
$controller->recalculateSla($ticket);

$ticket->refresh();
echo "New Downtime: " . $ticket->sla_total_minutes . "m (" . $ticket->sla_total_formatted . ")\n";
