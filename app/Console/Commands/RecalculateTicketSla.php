<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\TicketReply;
use Carbon\Carbon;

class RecalculateTicketSla extends Command
{
    protected $signature = 'tickets:recalculate-sla {ticket_id?}';
    protected $description = 'Recalculate SLA for all tickets or a specific ticket';

    public function handle()
    {
        $ticketId = $this->argument('ticket_id');

        if ($ticketId) {
            $tickets = Ticket::where('id', $ticketId)->get();
            if ($tickets->isEmpty()) {
                $this->error("Ticket with ID {$ticketId} not found.");
                return 1;
            }
        } else {
            $tickets = Ticket::all();
        }

        $this->info("Recalculating SLA for {$tickets->count()} ticket(s)...");

        $progressBar = $this->output->createProgressBar($tickets->count());

        foreach ($tickets as $ticket) {
            $this->recalculateSla($ticket);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('SLA recalculation completed!');

        return 0;
    }

    protected function recalculateSla(Ticket $ticket): void
    {
        $replies = $ticket->replies()->orderBy('created_at')->get();
        $ticketCreatedAt = $ticket->created_at;

        $mttrResponse = 0;
        $mttrResolve = 0;
        $downtime = 0;

        // 1. Calculate MTTR Response
        $firstAckReply = $replies->filter(function($r) {
            return in_array($r->update_status, ['need_visit', 'on_progress', 'done', 'remote_done', 'selesai']);
        })->first();

        if ($firstAckReply) {
            $mttrResponse = max(0, $ticketCreatedAt->diffInMinutes($firstAckReply->created_at));
        } else {
            $mttrResponse = max(0, $ticketCreatedAt->diffInMinutes(Carbon::now()));
        }

        // 2. Calculate MTTR Resolve
        $segmentStart = null;
        $segmentMode = null;

        $closeResolveSegment = function (Carbon $end) use (&$segmentStart, &$segmentMode, &$mttrResolve) {
            if (!$segmentStart || $segmentMode !== 'onsite') {
                $segmentStart = null;
                $segmentMode = null;
                return;
            }

            $minutes = max(0, $segmentStart->diffInMinutes($end));
            $mttrResolve += $minutes;

            $segmentStart = null;
            $segmentMode = null;
        };

        foreach ($replies as $reply) {
            $status = $reply->update_status;
            $mode = $reply->metode_penanganan
                ?? (($reply->tanggal_kunjungan || $reply->jam_kunjungan) ? 'onsite' : ($ticket->metode_penanganan));

            $timestamp = $reply->created_at instanceof Carbon ? $reply->created_at : Carbon::parse($reply->created_at);
            $isActive = ($status === 'on_progress') && ($mode === 'onsite');

            $plannedStart = null;
            if ($isActive && !empty($reply->tanggal_kunjungan) && !empty($reply->jam_kunjungan)) {
                $dateStr = substr((string)$reply->tanggal_kunjungan, 0, 10);
                $timeStr = (string)$reply->jam_kunjungan;
                try {
                    $parsed = Carbon::createFromFormat('Y-m-d H:i:s', $dateStr . ' ' . $timeStr);
                } catch (\Exception $e) {
                    try {
                        $parsed = Carbon::createFromFormat('Y-m-d H:i', $dateStr . ' ' . substr($timeStr, 0, 5));
                    } catch (\Exception $e2) {
                        $parsed = null;
                    }
                }
                if ($parsed) $plannedStart = $parsed;
            }

            if ($isActive) {
                if ($segmentStart !== null) {
                    $closeResolveSegment($plannedStart ?? $timestamp);
                }
                $segmentStart = $plannedStart ?? $timestamp;
                $segmentMode = 'onsite';
            } else {
                $end = $timestamp;
                if ($segmentStart && in_array($status, ['done', 'pending', 'selesai'], true) && !empty($reply->tanggal_kunjungan) && !empty($reply->jam_kunjungan)) {
                    $dateStr = substr((string)$reply->tanggal_kunjungan, 0, 10);
                    $timeStr = (string)$reply->jam_kunjungan;
                    try {
                        $scheduledEnd = Carbon::createFromFormat('Y-m-d H:i:s', $dateStr . ' ' . $timeStr);
                    } catch (\Exception $e) {
                        try {
                            $scheduledEnd = Carbon::createFromFormat('Y-m-d H:i', $dateStr . ' ' . substr($timeStr, 0, 5));
                        } catch (\Exception $e2) {
                            $scheduledEnd = null;
                        }
                    }
                    if ($scheduledEnd && $scheduledEnd->gte($segmentStart)) {
                        $end = $scheduledEnd;
                    }
                }
                $closeResolveSegment($end);
            }

            if (in_array($status, ['remote_done', 'done', 'selesai'], true)) {
                break;
            }
        }

        if ($segmentStart !== null && $segmentMode === 'onsite') {
            $closeResolveSegment(Carbon::now());
        }

        // 3. Calculate Downtime
        $resolutionReply = $replies->filter(function($r) {
            return in_array($r->update_status, ['done', 'remote_done', 'selesai']);
        })->first();

        if ($resolutionReply) {
            if ($resolutionReply->update_status === 'remote_done' || $mttrResolve === 0) {
                $downtime = 0;
            } else {
                // Prioritize schedule date/time for end of downtime
                $downtimeEnd = null;
                if (!empty($resolutionReply->tanggal_kunjungan) && !empty($resolutionReply->jam_kunjungan)) {
                    $dateStr = substr((string)$resolutionReply->tanggal_kunjungan, 0, 10);
                    $timeStr = (string)$resolutionReply->jam_kunjungan;
                    try {
                        $downtimeEnd = Carbon::createFromFormat('Y-m-d H:i:s', $dateStr . ' ' . $timeStr);
                    } catch (\Exception $e) {
                        try {
                            $downtimeEnd = Carbon::createFromFormat('Y-m-d H:i', $dateStr . ' ' . substr($timeStr, 0, 5));
                        } catch (\Exception $e2) {
                            $downtimeEnd = null;
                        }
                    }
                }
                
                // Fallback to creation of the resolution reply
                $downtimeEnd = $downtimeEnd ?: $resolutionReply->created_at;

                // Fallback to creation of the resolution reply
                $downtimeEnd = $downtimeEnd ?: $resolutionReply->created_at;
                
                // Determine Start of Downtime: 
                $needVisitReply = $ticket->replies->filter(function($r) {
                    return $r->update_status === 'need_visit';
                })->first();

                $downtimeStart = $ticketCreatedAt;

                if ($needVisitReply) {
                    if (!empty($needVisitReply->tanggal_kunjungan) && !empty($needVisitReply->jam_kunjungan)) {
                        $dateStr = substr((string)$needVisitReply->tanggal_kunjungan, 0, 10);
                        $timeStr = (string)$needVisitReply->jam_kunjungan;
                        try {
                            $downtimeStart = Carbon::createFromFormat('Y-m-d H:i:s', $dateStr . ' ' . $timeStr);
                        } catch (\Exception $e) {
                             try {
                                $downtimeStart = Carbon::createFromFormat('Y-m-d H:i', $dateStr . ' ' . substr($timeStr, 0, 5));
                            } catch (\Exception $e2) {
                                $downtimeStart = $needVisitReply->created_at;
                            }
                        }
                    } else {
                        $downtimeStart = $needVisitReply->created_at;
                    }
                }

                // Ensure start <= end
                if ($downtimeStart->gt($downtimeEnd)) {
                     $downtimeStart = $ticketCreatedAt;
                }

                $downtime = max(0, $downtimeStart->diffInMinutes($downtimeEnd));
            }
        } else {
            if ($mttrResolve > 0) {
                $downtime = max(0, $ticketCreatedAt->diffInMinutes(Carbon::now()));
            } else {
                $downtime = 0;
            }
        }

        $ticket->update([
            'sla_remote_minutes' => $mttrResponse,
            'sla_onsite_minutes' => $mttrResolve,
            'sla_total_minutes' => $downtime,
        ]);
    }
}
