<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Ticket;
use App\Models\Customer;
use App\Models\TicketReply;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SlaCalculationTest extends TestCase
{
    public function test_onsite_sla_uses_scheduled_start_and_pauses_on_pending()
    {
        // Create prerequisite customer and base ticket (remote -> need_visit -> onsite flow)
        $cid = 'TESTCUST-'.Str::uuid();
        $customer = Customer::create([
            'cid' => $cid,
            'nama' => 'Customer Test',
            'email' => 'test@example.com',
            'sales' => 'SALES01',
            'packet' => 'Paket Test',
            'alamat' => 'Alamat Test',
            'no_it' => '081234567890',
            'no_finance' => '081234567891',
            'coordinate_maps' => '-8.123,115.123',
            'status' => 'aktif',
        ]);

        $ticket = Ticket::create([
            'cid' => $customer->cid,
            'jenis' => 'maintenance',
            'metode_penanganan' => 'remote',
            'status' => 'open',
            'kendala' => 'Uji SLA onsite',
            'created_by' => 1,
            'created_by_role' => 'admin',
        ]);

        // 1) Remote update deciding need_visit for tomorrow 10:00
        $r1 = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => 1,
            'reply' => 'Perlu kunjungan besok',
            'role' => 'admin',
            'update_status' => 'need_visit',
            'metode_penanganan' => 'remote',
            'tanggal_kunjungan' => '2025-01-10',
            'jam_kunjungan' => '10:00',
        ]);
        TicketReply::where('id', $r1->id)->update(['created_at' => Carbon::parse('2025-01-09 08:00'), 'updated_at' => Carbon::parse('2025-01-09 08:00')]);

        // 2) Onsite on_progress after schedule begins
        $r2 = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => 2,
            'reply' => 'Mulai kunjungan',
            'role' => 'teknisi',
            'update_status' => 'on_progress',
            'metode_penanganan' => 'onsite',
        ]);
        TicketReply::where('id', $r2->id)->update(['created_at' => Carbon::parse('2025-01-10 10:30'), 'updated_at' => Carbon::parse('2025-01-10 10:30')]);

        // 3) Pending (pause SLA)
        $r3 = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => 2,
            'reply' => 'Pending menunggu alat',
            'role' => 'teknisi',
            'update_status' => 'pending',
            'metode_penanganan' => 'onsite',
        ]);
        TicketReply::where('id', $r3->id)->update(['created_at' => Carbon::parse('2025-01-10 11:00'), 'updated_at' => Carbon::parse('2025-01-10 11:00')]);

        // 4) Resume on_progress
        $r4 = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => 2,
            'reply' => 'Lanjut pengerjaan',
            'role' => 'teknisi',
            'update_status' => 'on_progress',
            'metode_penanganan' => 'onsite',
        ]);
        TicketReply::where('id', $r4->id)->update(['created_at' => Carbon::parse('2025-01-10 12:00'), 'updated_at' => Carbon::parse('2025-01-10 12:00')]);

        // 5) Done (close SLA)
        $r5 = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => 2,
            'reply' => 'Selesai',
            'role' => 'teknisi',
            'update_status' => 'done',
            'metode_penanganan' => 'onsite',
        ]);
        TicketReply::where('id', $r5->id)->update(['created_at' => Carbon::parse('2025-01-10 13:00'), 'updated_at' => Carbon::parse('2025-01-10 13:00')]);

        // Trigger SLA recalculation via console command
        Artisan::call('tickets:recalculate-sla', ['ticket_id' => $ticket->id]);

        $ticket->refresh();

        // Expect onsite SLA: (10:00 -> 11:00) 60 minutes + (12:00 -> 13:00) 60 minutes = 120
        $this->assertEquals(120, $ticket->sla_onsite_minutes, 'Onsite SLA should count from scheduled start and pause on pending');

        // Remote SLA should be 0 in this scenario
        $this->assertEquals(0, $ticket->sla_remote_minutes, 'Remote SLA should be zero in this flow');

        // Total SLA should match sum
        $this->assertEquals(120, $ticket->sla_total_minutes, 'Total SLA should equal onsite in this test');
    }
}
