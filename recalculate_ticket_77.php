<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ticket;
use App\Http\Controllers\ticketing\TicketController;
use Carbon\Carbon;

$ticketId = 77;
$ticket = Ticket::with('replies')->find($ticketId);

if (!$ticket) {
    die("Ticket not found\n");
}

echo "Current Ticket SLA:\n";
echo "Downtime: " . $ticket->sla_total_minutes . "m (" . $ticket->sla_total_formatted . ")\n";

// Use the controller's logic
$controller = new TicketController();
$controller->recalculateSla($ticket);

$ticket->refresh();
echo "\nUpdated Ticket SLA:\n";
echo "Downtime: " . $ticket->sla_total_minutes . "m (" . $ticket->sla_total_formatted . ")\n";

// Show manual dates for diagnostic
$needVisit = $ticket->replies->where('update_status', 'need_visit')->first();
$resolution = $ticket->replies->filter(function($r) {
    return in_array($r->update_status, ['done', 'remote_done', 'selesai']);
})->first();

if ($needVisit) {
    echo "\nNeed Visit (Manual): " . ($needVisit->tanggal_kunjungan ? $needVisit->tanggal_kunjungan->format('Y-m-d') : 'null') . " " . $needVisit->jam_kunjungan . "\n";
    echo "Need Visit (Created): " . $needVisit->created_at . "\n";
}
if ($resolution) {
    echo "Resolution (Created): " . $resolution->created_at . "\n";
}
