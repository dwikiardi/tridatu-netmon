<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ticket;

$tickets = Ticket::with('replies')
    ->orderBy('id', 'desc')
    ->take(5)
    ->get()
    ->map(function($t) {
        return [
            'id' => $t->id,
            'created' => $t->created_at->toDateTimeString(),
            'sla_total' => $t->sla_total_minutes,
            'replies' => $t->replies->map(function($r) {
                return [
                    'id' => $r->id,
                    'status' => $r->update_status,
                    'created' => $r->created_at->toDateTimeString(),
                    'tgl' => $r->tanggal_kunjungan,
                    'jam' => $r->jam_kunjungan
                ];
            })
        ];
    });

echo json_encode($tickets, JSON_PRETTY_PRINT);
