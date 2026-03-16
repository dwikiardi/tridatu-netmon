<?php
// Scratch script to verify status reversion logic
$rootDir = realpath(__DIR__ . '/..');
require $rootDir . '/vendor/autoload.php';
$app = require_once $rootDir . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();

    // 1. Find a ticket to test
    $ticket = Ticket::first();
    if (!$ticket) {
        echo "No tickets found to test.\n";
        return;
    }
    echo "Testing with Ticket ID: " . $ticket->id . " current status: " . $ticket->status . "\n";

    // 2. Create a reply with a new status
    $oldStatus = $ticket->status;
    $newStatus = 'pending';
    $reply = TicketReply::create([
        'ticket_id' => $ticket->id,
        'user_id' => 1, // Assume admin
        'reply' => 'Testing status reversion',
        'update_status' => $newStatus,
        'role' => 'admin'
    ]);
    
    $ticket->update(['status' => $newStatus]);
    echo "Created reply ID: " . $reply->id . " with status: " . $newStatus . "\n";
    echo "Ticket status is now: " . $ticket->status . "\n";

    // 3. Simulate deletion logic from TicketController
    echo "Simulating deletion...\n";
    $reply->update(['is_deleted' => true]);

    $lastValidReply = TicketReply::where('ticket_id', $ticket->id)
        ->where('is_deleted', false)
        ->whereNotNull('update_status')
        ->orderBy('created_at', 'desc')
        ->first();

    if ($lastValidReply) {
        // Map reply status to ticket status enum
        $statusMapping = [
            'need_visit' => 'need visit',
            'on_progress' => 'on progress',
            'pending' => 'pending',
            'remote_done' => 'selesai',
            'done' => 'selesai',
        ];
        
        $targetStatus = $statusMapping[$lastValidReply->update_status] ?? $lastValidReply->update_status;
        echo "Reverting to status: " . $targetStatus . " (from " . $lastValidReply->update_status . ")\n";
        $ticket->update(['status' => $targetStatus]);
    } else {
        echo "Reverting to status: open (no valid replies left)\n";
        $ticket->update(['status' => 'open']);
    }

    echo "Final Ticket status: " . $ticket->status . "\n";

    // 4. Verify system log insertion (no update_status)
    $log = TicketReply::create([
        'ticket_id' => $ticket->id,
        'user_id' => 1,
        'reply' => 'System log test',
        'role' => 'admin'
    ]);
    echo "Successfully created system log ID: " . $log->id . " without truncation error.\n";

    DB::rollBack();
    echo "Test passed and changes rolled back.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Test FAILED: " . $e->getMessage() . "\n";
}
