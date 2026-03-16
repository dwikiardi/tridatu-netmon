<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ticket;
use App\Models\TicketReply;

$ticketId = 101;
$ticket = Ticket::find($ticketId);

if ($ticket) {
    $validReplies = TicketReply::where('ticket_id', $ticket->id)
        ->where('is_deleted', false)
        ->orderBy('created_at', 'asc')
        ->get();

    $targetStatus = 'open';
    $statusMapping = [
        'need_visit' => 'need visit',
        'on_progress' => 'on progress',
        'pending' => 'pending',
        'remote_done' => 'selesai',
        'done' => 'selesai'
    ];

    foreach ($validReplies as $r) {
        if ($r->update_status && isset($statusMapping[$r->update_status])) {
            $targetStatus = $statusMapping[$r->update_status];
        }
    }

    $ticket->update(['status' => $targetStatus]);
    echo "Fixed ticket $ticketId status to: $targetStatus\n";
} else {
    echo "Ticket $ticketId not found\n";
}
