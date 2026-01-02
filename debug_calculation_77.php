<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$ticketId = 77;
$ticket = Ticket::find($ticketId);

echo "Ticket Created At: " . $ticket->created_at . "\n";

$replies = DB::table('ticket_replies')->where('ticket_id', $ticketId)->get();
echo "\n--- Raw DB Values (ticket_replies) ---\n";
foreach ($replies as $r) {
    echo "ID: {$r->id} | Status: {$r->update_status} | Tgl: '{$r->tanggal_kunjungan}' | Jam: '{$r->jam_kunjungan}'\n";
}

$repliesEloquent = $ticket->replies;
echo "\n--- Eloquent Casted Values ---\n";
foreach ($repliesEloquent as $r) {
    echo "ID: {$r->id} | Status: {$r->update_status} | Tgl Class: " . (is_object($r->tanggal_kunjungan) ? get_class($r->tanggal_kunjungan) : 'string') . " | Tgl Val: " . ($r->tanggal_kunjungan ? $r->tanggal_kunjungan->format('Y-m-d H:i:s') : 'null') . "\n";
}

// Emulate recalc logic
$res = $repliesEloquent->filter(fn($r) => in_array($r->update_status, ['done','remote_done','selesai']))->first();
$nv = $repliesEloquent->filter(fn($r) => $r->update_status === 'need_visit')->first();

if ($nv && $res) {
    echo "\n--- Recalc Logic ---\n";
    $start = null;
    if ($nv->tanggal_kunjungan && $nv->jam_kunjungan) {
        $d = $nv->tanggal_kunjungan->format('Y-m-d');
        $t = $nv->jam_kunjungan;
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $d . ' ' . $t);
    }
    
    $end = null;
    if ($res->tanggal_kunjungan && $res->jam_kunjungan) {
        $d = $res->tanggal_kunjungan->format('Y-m-d');
        $t = $res->jam_kunjungan;
        $end = Carbon::createFromFormat('Y-m-d H:i:s', $d . ' ' . $t);
    } else {
        $end = $res->created_at;
    }
    
    echo "Calculated Start: " . ($start ? $start->toDateTimeString() : 'null') . "\n";
    echo "Calculated End: " . ($end ? $end->toDateTimeString() : 'null') . "\n";
    if ($start && $end) {
        echo "Diff In Minutes: " . $end->diffInMinutes($start) . "\n";
    }
}
