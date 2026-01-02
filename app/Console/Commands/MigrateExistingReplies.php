<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\TicketReply;

class MigrateExistingReplies extends Command
{
    protected $signature = 'tickets:migrate-replies';
    protected $description = 'Migrate existing replies to populate teknisi_id and metode_penanganan';

    public function handle()
    {
        $this->info('Migrating existing replies...');

        // Get all replies that have tanggal_kunjungan but missing teknisi_id or metode_penanganan
        $replies = TicketReply::whereNotNull('tanggal_kunjungan')
            ->where(function($query) {
                $query->whereNull('teknisi_id')
                      ->orWhereNull('metode_penanganan');
            })
            ->get();

        $this->info("Found {$replies->count()} replies to migrate.");

        $progressBar = $this->output->createProgressBar($replies->count());

        foreach ($replies as $reply) {
            $ticket = $reply->ticket;

            // Set teknisi_id from ticket if not set
            if (!$reply->teknisi_id && $ticket->teknisi_id) {
                $reply->teknisi_id = $ticket->teknisi_id;
            }

            // Set metode_penanganan from ticket if not set
            if (!$reply->metode_penanganan) {
                // If reply has tanggal_kunjungan, it's onsite
                $reply->metode_penanganan = $ticket->metode_penanganan ?? 'onsite';
            }

            $reply->save();
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Migration completed!');

        return 0;
    }
}
