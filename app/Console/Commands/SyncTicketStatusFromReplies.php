<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\TicketReply;

class SyncTicketStatusFromReplies extends Command
{
    protected $signature = 'tickets:sync-status';
    protected $description = 'Sync ticket status from latest reply';

    public function handle()
    {
        $tickets = Ticket::all();
        $updated = 0;

        $statusMapping = [
            'need_visit' => 'need visit',
            'on_progress' => 'on progress',
            'pending' => 'pending',
            'remote_done' => 'selesai',
            'done' => 'selesai',
        ];

        foreach ($tickets as $ticket) {
            $lastReply = TicketReply::where('ticket_id', $ticket->id)
                ->whereNotNull('update_status')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastReply && isset($statusMapping[$lastReply->update_status])) {
                $newStatus = $statusMapping[$lastReply->update_status];
                if ($ticket->status !== $newStatus) {
                    $ticket->update(['status' => $newStatus]);
                    $updated++;
                    $this->info("Ticket #{$ticket->id}: {$ticket->status} → {$newStatus}");
                }
            }
        }

        $this->info("Synced {$updated} ticket(s)");
        return 0;
    }
}
