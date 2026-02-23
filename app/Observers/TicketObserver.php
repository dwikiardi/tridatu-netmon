<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Services\WahaService;
use Illuminate\Support\Facades\Log;

class TicketObserver
{
    protected $waha;

    public function __construct(WahaService $waha)
    {
        $this->waha = $waha;
    }

    public function created(Ticket $ticket)
    {
        $this->notify($ticket, "Ticket Baru Dibuat");
    }

    public function updated(Ticket $ticket)
    {
        // Only notify if status changed
        if ($ticket->wasChanged('status')) {
            $this->notify($ticket, "Update Status: " . strtoupper($ticket->status));
        }
    }

    protected function notify(Ticket $ticket, $title)
    {
        $groupId = env('WA_GROUP_ID');
        if (!$groupId) {
            return;
        }

        $ticketNo = $this->generateTicketNo($ticket);
        $customerNama = $ticket->customer ? $ticket->customer->nama : ($ticket->calonCustomer ? $ticket->calonCustomer->nama : '-');

        $msg = "📢 *{$title}*\n\n";
        $msg .= "*ID:* #{$ticket->id}\n";
        $msg .= "*No:* {$ticketNo}\n";
        $msg .= "*Customer:* {$customerNama}\n";
        $msg .= "*Status:* " . strtoupper($ticket->status) . "\n";
        
        $koordinatRaw = $ticket->customer ? $ticket->customer->coordinate_maps 
            : ($ticket->calonCustomer ? $ticket->calonCustomer->koordinat : null);
        if ($koordinatRaw) {
            $mapsUrl = (strpos($koordinatRaw, 'http') === 0) 
                ? $koordinatRaw 
                : "https://www.google.com/maps?q=" . urlencode(trim($koordinatRaw));
            $msg .= "*Koordinat:* {$mapsUrl}\n";
        }

        $msg .= "*Kendala:* " . ($ticket->kendala ?? '-') . "\n\n";
        
        if (in_array($ticket->status, ['need visit', 'open'])) {
            $msg .= "💡 Ambil job ini: `/ambil #{$ticket->id}`\n";
            $msg .= "🔍 Cek detail: `/cek #{$ticket->id}`";
        }

        $this->waha->sendMessage($groupId, $msg);
    }

    protected function generateTicketNo($ticket)
    {
        $tanggal = $ticket->tanggal_kunjungan ? $ticket->tanggal_kunjungan->format('dmY') : date('dmY');
        $jam = $ticket->jam ? date('Hi', strtotime($ticket->jam)) : date('Hi');
        $no = str_pad($ticket->id, 3, '0', STR_PAD_LEFT);
        return "TDN-{$tanggal}-{$jam}-{$no}";
    }
}
