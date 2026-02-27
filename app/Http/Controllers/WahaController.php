<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Models\Customer;
use App\Models\CalonCustomer;
use App\Services\WahaService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WahaController extends Controller
{
    protected $waha;

    public function __construct(WahaService $waha)
    {
        $this->waha = $waha;
    }

    public function webhook(Request $request)
    {
        // DEBUG: Log everything
        Log::info("WAHA Webhook Hit!");
        Log::info("Headers: " . json_encode($request->headers->all()));
        Log::info("Payload: " . json_encode($request->all()));

        $event = $request->input('event');
        $payload = $request->input('payload');

        if ($event !== 'message') {
            return response()->json(['status' => 'ignored']);
        }

        $from = $payload['from'] ?? null; // This is the recipient for our reply (could be group or person)
        $body = $payload['body'] ?? '';
        $participant = $payload['participant'] ?? $from; // Who actually sent the message

        if (!$from) {
            return response()->json(['status' => 'invalid payload']);
        }
        
        // Remove @c.us or @g.us to get the phone/ID
        $senderId = explode('@', $participant)[0];

        // Advanced: Check for LID vs Phone Number issue
        // If we have _data, try to find the real phone number
        $data = $payload['_data'] ?? [];
        if (isset($data['Info']['SenderAlt'])) {
             // Example: 62895...:33@s.whatsapp.net
             $altSender = explode(':', $data['Info']['SenderAlt'])[0];
             $altSender = explode('@', $altSender)[0];
             // If looks like a phone number (digits only), use it
             if (preg_match('/^[0-9]+$/', $altSender)) {
                 $senderId = $altSender;
                 Log::info("WAHA: Found alternative sender ID from payload: $senderId");
             }
        }

        // Find user by phone. 
        // We'll search for users where phone ends with the last 10 digits to be safe.
        $searchPhone = substr(preg_replace('/[^0-9]/', '', $senderId), -10);
        $user = User::where('phone', 'like', "%$searchPhone%")->first();
        
        if (!$user) {
            // TEMPORARY: Allow all numbers by using the first available user as fallback
            $user = User::first();
            
            if ($user) {
                Log::info("WAHA: Unknown sender $senderId. Using fallback user: {$user->name}");
            } else {
                Log::warning("WAHA: Received message from unregistered phone: $senderId and no users found in DB.");
                return response()->json(['status' => 'unauthorized']);
            }
        }

        $body = trim($body);

        // -- Semua command pakai prefix /slash seperti Telegram --

        // 1. Sales buat ticket (Maintenance)
        // Format: /ticket [CID] [KENDALA]
        if (preg_match('/^\/ticket\s+([A-Z0-9]+)\s+(.+)$/i', $body, $matches)) {
            return $this->handleMaintenanceCreate($user, $matches[1], $matches[2], $from);
        }

        // 2. Sales buat Survey
        // Format: /survey [NAMA] | [ALAMAT] | [KOORDINAT] | [DESKRIPSI]
        if (preg_match('/^\/survey\s+(.+)$/i', $body, $matches)) {
            $parts = explode('|', $matches[1]);
            if (count($parts) >= 4) {
                return $this->handleSurveyCreate($user, $parts, $from);
            }
            $this->waha->sendMessage($from, "⚠️ Format salah. Gunakan:\n`/survey [NAMA] | [ALAMAT] | [KOORDINAT] | [DESKRIPSI]`");
            return response()->json(['status' => 'invalid format']);
        }

        // 3. Update Ticket via FORM multi-line (NOC / Teknisi)
        // Format:
        //   /update
        //   id: 123
        //   status: remote
        //   priority: high  (opsional)
        //   pesan: teks update
        if (preg_match('/^\/update(\s|$)/i', $body)) {
            return $this->handleFormUpdate($user, $body, $from);
        }

        // 4. Ambil Job (Teknisi)
        // Format: /ambil #[ID] atau /ambil #[ID] (rekan1, rekan2)
        if (preg_match('/^\/ambil\s+#(\d+)(?:\s+\((.+)\))?$/i', $body, $matches)) {
            return $this->handleClaimJob($user, $matches[1], $matches[2] ?? null, $from);
        }

        // 5. Cari Pelanggan
        // Format: /cari [NAMA atau CID]
        if (preg_match('/^\/cari\s+(.+)$/i', $body, $matches)) {
            return $this->handleCariCustomer($matches[1], $from);
        }

        // 6. Jadwal Maintenance
        // Format: /jadwal [CID] [TANGGAL: YYYY-MM-DD] [JAM: HH:MM] [KENDALA]
        if (preg_match('/^\/jadwal\s+([A-Z0-9\-]+)\s+(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2})\s+(.+)$/i', $body, $matches)) {
            return $this->handleJadwalMaintenance($user, $matches[1], $matches[2], $matches[3], $matches[4], $from);
        }

        // 7. Cek Detail Tiket
        // Format: /cek #[ID]
        if (preg_match('/^\/cek\s+#(\d+)$/i', $body, $matches)) {
            return $this->handleCekTicket($matches[1], $from);
        }

        // 8. Cek Semua Jadwal
        if (strtolower($body) === '/semuajadwal') {
            return $this->handleCekSemuaJadwal($from);
        }

        // 9. Cek Remote (CS/NOC)
        if (preg_match('/^\/cekremote\s+#(\d+)\s+(.+)$/i', $body, $matches)) {
            return $this->handleTicketUpdate($user, $matches[1], 'progress', $matches[2], $from, null, 'remote');
        }

        // 10. Perlu Visit (CS/NOC/Teknisi)
        // Format: /visit #[ID] [YYYY-MM-DD] [ALASAN]
        if (preg_match('/^\/visit\s+#(\d+)\s+(\d{4}-\d{2}-\d{2})\s+(.+)$/i', $body, $matches)) {
            return $this->handleTicketUpdate($user, $matches[1], 'visit', $matches[3], $from, null, 'onsite', $matches[2]);
        }

        // 11. Selesai Remote (CS/NOC)
        if (preg_match('/^\/remotedone\s+#(\d+)\s+(.+)$/i', $body, $matches)) {
            return $this->handleTicketUpdate($user, $matches[1], 'selesai', $matches[2], $from, null, 'remote');
        }

        // Help command
        if (strtolower($body) === '/help') {
            return $this->sendHelp($from);
        }

        return response()->json(['status' => 'unknown command']);
    }

    protected function handleFormUpdate($user, $body, $replyTo)
    {
        // Parse key: value from each line
        $lines = preg_split('/\r?\n/', $body);
        $fields = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^([\w]+)\s*:\s*(.+)$/i', $line, $m)) {
                $fields[strtolower($m[1])] = trim($m[2]);
            }
        }

        $ticketId = $fields['id'] ?? null;
        $pesan = $fields['pesan'] ?? null;

        // Validasi minimal ID dan Pesan
        if (!$ticketId || !$pesan) {
            $hint = "⚠️ *Format /update tidak lengkap.*\n\n";
            $hint .= "Gunakan format:\n";
            $hint .= "`/update`\n";
            $hint .= "`id: [nomor tiket]`\n";
            $hint .= "`pesan: [update progress]`\n\n";
            $hint .= "Opsi lain:\n";
            $hint .= "• `status: [visit/pending/selesai]`\n";
            $hint .= "• `metode: [onsite/remote]`\n";
            $hint .= "• `priority: [urgent/high/medium/low]`\n";
            $hint .= "• `tanggal: [YYYY-MM-DD]`\n";
            $hint .= "• `jam: [HH:MM]`";
            
            $this->waha->sendMessage($replyTo, $hint);
            return response()->json(['status' => 'invalid format']);
        }

        $statusKey = $fields['status'] ?? null;
        $priority  = $fields['priority'] ?? null;
        $metode    = $fields['metode'] ?? null;
        $tanggal   = $fields['tanggal'] ?? null;
        $jam       = $fields['jam'] ?? null;

        return $this->handleTicketUpdate($user, $ticketId, $statusKey, $pesan, $replyTo, $priority, $metode, $tanggal, $jam);
    }

    protected function handleMaintenanceCreate($user, $cid, $kendala, $replyTo)
    {
        // Try to find customer
        $customer = Customer::where('cid', $cid)->first();
        if (!$customer) {
            $this->waha->sendMessage($replyTo, "⚠️ CID *{$cid}* tidak ditemukan.");
            return response()->json(['status' => 'not found']);
        }

        $ticket = Ticket::create([
            'jenis' => 'maintenance',
            'cid' => $cid,
            'kendala' => $kendala,
            'status' => 'open',
            'created_by' => $user->id,
            'created_by_role' => $user->jabatan ?? 'admin',
            'hari' => '-',
        ]);

        $ticketNo = $this->generateTicketNo($ticket);
        
        $msg = "✅ Ticket Maintenance Berhasil Dibuat!\n\n";
        $msg .= "*ID:* #{$ticket->id}\n";
        $msg .= "*No:* {$ticketNo}\n";
        if ($customer->coordinate_maps) {
            $mapsUrl = (strpos($customer->coordinate_maps, 'http') === 0) 
                ? $customer->coordinate_maps 
                : "https://www.google.com/maps?q=" . urlencode(trim($customer->coordinate_maps));
            $msg .= "*Koordinat:* {$mapsUrl}\n";
        }
        $msg .= "*Kendala:* {$kendala}\n\n";
        $msg .= "Status: *OPEN*\n";
        $msg .= "────────────────\n";
        $msg .= "🔍 Cek detail: `/cek #{$ticket->id}`\n";
        $msg .= "🟢 Ambil job: `/ambil #{$ticket->id}`";

        $this->waha->sendMessage($replyTo, $msg);
        return response()->json(['status' => 'created']);
    }

    protected function handleSurveyCreate($user, $parts, $replyTo)
    {
        $nama = trim($parts[0]);
        $alamat = trim($parts[1]);
        $koordinat = trim($parts[2]);
        $deskripsi = trim($parts[3]);

        // Create Calon Customer
        $calon = CalonCustomer::create([
            'nama' => $nama,
            'alamat' => $alamat,
            'koordinat' => $koordinat,
            'sales_id' => $user->id,
            'tipe_survey' => 'normal',
            'status' => 'prospek',
        ]);

        $ticket = Ticket::create([
            'jenis' => 'survey',
            'calon_customer_id' => $calon->id,
            'kendala' => $deskripsi,
            'status' => 'open',
            'created_by' => $user->id,
            'created_by_role' => $user->jabatan ?? 'admin',
            'hari' => '-',
        ]);

        $ticketNo = $this->generateTicketNo($ticket);

        $msg = "✅ Ticket Survey Berhasil Dibuat!\n\n";
        $msg .= "*ID:* #{$ticket->id}\n";
        $msg .= "*No:* {$ticketNo}\n";
        $msg .= "*Customer:* {$nama}\n";
        $msg .= "*Alamat:* {$alamat}\n";
        $mapsUrl = (strpos($koordinat, 'http') === 0) 
            ? $koordinat 
            : "https://www.google.com/maps?q=" . urlencode(trim($koordinat));
        $msg .= "*Koordinat:* {$mapsUrl}\n";
        $msg .= "*Deskripsi:* {$deskripsi}\n\n";
        $msg .= "Status: *OPEN*\n";
        $msg .= "────────────────\n";
        $msg .= "🔍 Cek detail: `/cek #{$ticket->id}`\n";
        $msg .= "🟢 Ambil job: `/ambil #{$ticket->id}`";

        $this->waha->sendMessage($replyTo, $msg);
        return response()->json(['status' => 'created']);
    }

    protected function handleTicketUpdate($user, $ticketId, $statusKey, $replyText, $replyTo, $priority = null, $metode = null, $tanggal = null, $jam = null)
    {
        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            $this->waha->sendMessage($replyTo, "⚠️ Ticket #{$ticketId} tidak ditemukan.");
            return response()->json(['status' => 'not found']);
        }

        // Status mapping — selaras dengan form web:
        // progress  = On Progress Kunjungan
        // visit     = Perlu Kunjungan
        // pending   = Pending (menunggu alat/bahan, jadwal ulang)
        // selesai   = Selesai (remote atau onsite)
        $statusMapping = [
            'progress' => ['status' => 'on progress', 'update_status' => 'on_progress'],
            'visit'    => ['status' => 'need visit',  'update_status' => 'need_visit'],
            'pending'  => ['status' => 'pending',     'update_status' => 'pending'],
            'selesai'  => ['status' => 'selesai',     'update_status' => 'done'],
        ];

        // Jika status tidak diisi, gunakan status saat ini (On Progress)
        if (!$statusKey || strtolower($statusKey) === 'update' || strtolower($statusKey) === 'progress') {
            $currentStatus = $ticket->status;
            
            // Fallback jika status masih open/need visit tapi sudah di-update teknisi, 
            // maka otomatis jadi on progress
            if (in_array($currentStatus, ['open', 'need visit'])) {
                $currentStatus = 'on progress';
            }

            $statusInfo = [
                'status' => $currentStatus,
                'update_status' => match($currentStatus) {
                    'need visit'  => 'need_visit',
                    'on progress' => 'on_progress',
                    'pending'     => 'pending',
                    'selesai'     => 'done',
                    default       => 'on_progress',
                }
            ];
            $statusLabel = strtoupper($currentStatus);
        } else {
            $statusInfo = $statusMapping[strtolower($statusKey)] ?? null;
            if (!$statusInfo) {
                $this->waha->sendMessage($replyTo, "⚠️ Status *{$statusKey}* tidak valid.\nGunakan: visit, pending, selesai");
                return response()->json(['status' => 'invalid status']);
            }
            $statusLabel = match(strtolower($statusKey)) {
                'visit'    => 'PERLU KUNJUNGAN',
                'pending'  => 'PENDING (JADWAL ULANG)',
                'selesai'  => 'SELESAI',
                default    => strtoupper($statusKey),
            };
        }

        // Validate priority if provided
        $validPriorities = ['urgent', 'high', 'medium', 'low'];
        if ($priority && !in_array(strtolower($priority), $validPriorities)) {
            $this->waha->sendMessage($replyTo, "⚠️ Priority *{$priority}* tidak valid.\nGunakan: urgent, high, medium, low");
            return response()->json(['status' => 'invalid priority']);
        }

        // Validate metode if provided
        $validMetode = ['onsite', 'remote'];
        if ($metode && !in_array(strtolower($metode), $validMetode)) {
            $this->waha->sendMessage($replyTo, "⚠️ Metode *{$metode}* tidak valid. Gunakan: onsite, remote");
            return response()->json(['status' => 'invalid metode']);
        }

        // Validate tanggal format if provided (for pending)
        $parsedTanggal = null;
        if ($tanggal) {
            $parsedTanggal = \Carbon\Carbon::createFromFormat('Y-m-d', $tanggal);
            if (!$parsedTanggal) {
                $this->waha->sendMessage($replyTo, "⚠️ Format tanggal *{$tanggal}* tidak valid. Gunakan: YYYY-MM-DD (contoh: 2026-02-25)");
                return response()->json(['status' => 'invalid date']);
            }
        }

        // Attempt to inherit teknisi info from last reply or ticket 
        $lastReply = TicketReply::where('ticket_id', $ticket->id)
            ->where(function($q) {
                $q->whereNotNull('teknisi_id')->orWhereNotNull('teknisi_ids');
            })
            ->orderBy('created_at', 'desc')
            ->first();

        $inheritedTeknisiId = $lastReply ? $lastReply->teknisi_id : $ticket->teknisi_id;
        $inheritedTeknisiIds = $lastReply ? $lastReply->teknisi_ids : null;

        // Create Reply
        TicketReply::create([
            'ticket_id'         => $ticket->id,
            'user_id'           => $user->id,
            'reply'             => $replyText,
            'role'              => $user->jabatan ?? 'admin',
            'update_status'     => $statusInfo['update_status'],
            'metode_penanganan' => $metode ? strtolower($metode) : null,
            'tanggal_kunjungan' => $tanggal,
            'jam_kunjungan'     => $jam,
            'teknisi_id'        => $inheritedTeknisiId,
            'teknisi_ids'       => $inheritedTeknisiIds,
        ]);

        // Update Ticket status
        $ticket->status = $statusInfo['status'];
        if ($priority) $ticket->priority = strtolower($priority);
        if ($metode) $ticket->metode_penanganan = strtolower($metode);
        if ($tanggal) $ticket->tanggal_kunjungan = $tanggal;
        if ($jam) $ticket->jam = $jam;
        
        $ticket->save();

        $priorityEmoji = match(strtolower($priority ?? '')) {
            'urgent' => '🔴 URGENT',
            'high'   => '🟠 HIGH',
            'medium' => '🟡 MEDIUM',
            'low'    => '🟢 LOW',
            default  => null,
        };

        $msg = "✅ *Tiket #{$ticket->id} berhasil di-update!*\n\n";
        $msg .= "*Status:* {$statusLabel}\n";
        if ($metode) {
            $metodeEmoji = strtolower($metode) === 'onsite' ? '🚧' : '💻';
            $msg .= "*Metode:* {$metodeEmoji} " . strtoupper($metode) . "\n";
        }
        if ($priorityEmoji) {
            $msg .= "*Priority:* {$priorityEmoji}\n";
        }
        if ($parsedTanggal) {
            $msg .= "*Reschedule:* " . $parsedTanggal->translatedFormat('d F Y') . "\n";
        }
        $msg .= "*Catatan:* {$replyText}";

        $this->waha->sendMessage($replyTo, $msg);
        return response()->json(['status' => 'updated']);
    }

    protected function handleClaimJob($user, $ticketId, $colleagues, $replyTo)
    {
        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            $this->waha->sendMessage($replyTo, "⚠️ Ticket #{$ticketId} tidak ditemukan.");
            return response()->json(['status' => 'not found']);
        }

        // Only allow claim if status is open or need visit or on progress (to allow joining)
        if (!in_array($ticket->status, ['open', 'need visit', 'on progress'])) {
            $this->waha->sendMessage($replyTo, "⚠️ Ticket #{$ticketId} sudah *{$ticket->status}*, tidak bisa diambil.");
            return response()->json(['status' => 'invalid status']);
        }

        // Build current teknisi list from pic_teknisi (comma-separated)
        $existingNames = [];
        if (!empty($ticket->pic_teknisi)) {
            $existingNames = array_map('trim', explode(',', $ticket->pic_teknisi));
        }

        // Check if this user is already in the list
        $isFirst = count($existingNames) === 0;
        if (in_array($user->name, $existingNames)) {
            $this->waha->sendMessage($replyTo, "ℹ️ *{$user->name}* sudah terdaftar di Ticket #{$ticketId}.\nTeknisi saat ini: " . implode(', ', $existingNames));
            return response()->json(['status' => 'already claimed']);
        }

        // Add this user to the list
        $existingNames[] = $user->name;

        // Collect teknisi IDs for visit counting
        $teknisiIds = collect();

        // Inherit past teknisi ids from last reply if any
        $lastReply = TicketReply::where('ticket_id', $ticket->id)
            ->where(function($q) {
                $q->whereNotNull('teknisi_id')->orWhereNotNull('teknisi_ids');
            })->latest()->first();
            
        if ($lastReply) {
            if (!empty($lastReply->teknisi_ids)) $teknisiIds = collect($lastReply->teknisi_ids);
            elseif (!empty($lastReply->teknisi_id)) $teknisiIds->push($lastReply->teknisi_id);
        } elseif ($ticket->teknisi_id) {
            $teknisiIds->push($ticket->teknisi_id);
        }

        $teknisiIds->push($user->id);

        if (!empty($colleagues)) {
            $colleagueNames = array_map('trim', explode(',', $colleagues));
            foreach ($colleagueNames as $cName) {
                $cUser = User::where('name', 'like', "%{$cName}%")->first();
                if ($cUser) {
                    $teknisiIds->push($cUser->id);
                    $actualName = $cUser->name;
                } else {
                    $actualName = $cName; // Fallback
                }
                if (!in_array($actualName, $existingNames)) {
                    $existingNames[] = $actualName;
                }
            }
        }

        $allNames = implode(', ', $existingNames);
        $teknisiIdsArray = $teknisiIds->unique()->values()->all();
        $mainTeknisiId = $teknisiIdsArray[0] ?? $user->id;

        // Update ticket — first person to claim changes status to on progress
        if ($ticket->status === 'open' || $ticket->status === 'need visit') {
            $ticket->status = 'on progress';
        }
        $ticket->teknisi_id = $mainTeknisiId; 
        $ticket->pic_teknisi = $allNames;
        $ticket->save();

        // Add reply
        TicketReply::create([
            'ticket_id'    => $ticket->id,
            'user_id'      => $user->id,
            'reply'        => "{$user->name} bergabung menangani tiket ini. Tim: {$allNames}",
            'role'         => $user->jabatan ?? 'teknisi',
            'update_status'=> 'on_progress',
            'teknisi_id'   => $mainTeknisiId,
            'teknisi_ids'  => count($teknisiIdsArray) > 0 ? $teknisiIdsArray : null,
        ]);

        $isFirst = count($existingNames) === 1;
        $msg = "✅ *{$user->name}* " . ($isFirst ? 'mengambil' : 'bergabung di') . " Tiket #{$ticket->id}\n\n";
        $msg .= "*Tim Teknisi:* {$allNames}\n";
        $msg .= "*Status:* " . strtoupper($ticket->status);

        $this->waha->sendMessage($replyTo, $msg);
        return response()->json(['status' => 'claimed']);
    }

    protected function generateTicketNo($ticket)
    {
        $tanggal = $ticket->tanggal_kunjungan ? $ticket->tanggal_kunjungan->format('dmY') : date('dmY');
        $jam = $ticket->jam ? date('Hi', strtotime($ticket->jam)) : date('Hi');
        $no = str_pad($ticket->id, 3, '0', STR_PAD_LEFT);
        return "TDN-{$tanggal}-{$jam}-{$no}";
    }

    protected function sendHelp($replyTo)
    {
        $msg = "📖 *WAHA Ticketing Bot Help*\n";
        $msg .= "_Semua perintah diawali dengan / (slash)_\n\n";
        $msg .= "🔹 *Sales - Maintenance:*\n";
        $msg .= "`/ticket [CID] [KENDALA]`\n\n";
        $msg .= "🔹 *Sales - Jadwal Maintenance:*\n";
        $msg .= "`/jadwal [CID] [YYYY-MM-DD] [HH:MM] [KENDALA]`\n";
        $msg .= "_Contoh: /jadwal TDN001 2026-02-25 09:00 Cek ONU_\n\n";
        $msg .= "🔹 *Sales - Survey:*\n";
        $msg .= "`/survey [NAMA] | [ALAMAT] | [KOORDINAT] | [DESKRIPSI]`\n\n";
        $msg .= "🔹 *NOC/Teknisi - Cek Detail Tiket:*\n";
        $msg .= "`/cek #[ID]`\n\n";
        $msg .= "🔹 *Cek Semua Jadwal Mendatang:*\n";
        $msg .= "`/semuajadwal`\n\n";
        $msg .= "🔹 *NOC/Teknisi - Update Tiket (Form):*\n";
        $msg .= "`/update`\n`id: [ID]`\n`status: [status]`\n`pesan: [catatan/komentar]`\n\n";
        $msg .= "_Status:_\n";
        $msg .= "• `progress` On Progress Kunjungan\n";
        $msg .= "• `visit` Perlu Kunjungan\n";
        $msg .= "• `pending` Pending (tambah: tanggal: YYYY-MM-DD)\n";
        $msg .= "• `selesai` Selesai\n";
        $msg .= "_Opsional: `metode: onsite/remote`, `priority: urgent/high/medium/low`_\n\n";
        $msg .= "🔹 *Teknisi - Ambil Job:*\n";
        $msg .= "`/ambil #[ID]`\n";
        $msg .= "`/ambil #[ID] (Rekan1, Rekan2)`\n\n";
        $msg .= "🔹 *Cari Pelanggan:*\n";
        $msg .= "`/cari [NAMA atau CID]`\n\n";
        $msg .= "🔹 *NOC/CS - Update Cepat:*\n";
        $msg .= "`/cekremote #[ID] [PESAN]` (Diagnosis)\n";
        $msg .= "`/visit #[ID] [YYYY-MM-DD] [ALASAN]` (Kirim Teknisi)\n";
        $msg .= "`/remotedone #[ID] [SOLUSI]` (Selesai Remote)\n\n";
        $msg .= "🔹 *Bantuan:*\n";
        $msg .= "`/help`";

        $this->waha->sendMessage($replyTo, $msg);
        return response()->json(['status' => 'help sent']);
    }

    protected function handleCariCustomer($keyword, $replyTo)
    {
        $keyword = trim($keyword);

        $customers = Customer::where('cid', 'like', "%{$keyword}%")
            ->orWhere('nama', 'like', "%{$keyword}%")
            ->limit(5)
            ->get();

        if ($customers->isEmpty()) {
            $this->waha->sendMessage($replyTo, "🔍 Tidak ditemukan pelanggan dengan kata kunci *{$keyword}*.");
            return response()->json(['status' => 'not found']);
        }

        $msg = "🔍 *Hasil Pencarian: {$keyword}*\n";
        $msg .= "Ditemukan {$customers->count()} pelanggan:\n\n";

        foreach ($customers as $c) {
            $status = strtoupper($c->status ?? '-');
            $statusEmoji = ($c->status === 'aktif') ? '🟢' : '🔴';
            $msg .= "────────────────\n";
            $msg .= "*CID:* {$c->cid}\n";
            $msg .= "*Nama:* {$c->nama}\n";
            $msg .= "*Status:* {$statusEmoji} {$status}\n";
            $msg .= "*Paket:* " . ($c->packet ?? '-') . "\n";
            $msg .= "*POP:* " . ($c->pop ?? '-') . "\n";
            $msg .= "*Alamat:* " . ($c->alamat ?? '-') . "\n";
            if ($c->coordinate_maps) {
                $mapsUrl = (strpos($c->coordinate_maps, 'http') === 0) 
                    ? $c->coordinate_maps 
                    : "https://www.google.com/maps?q=" . urlencode(trim($c->coordinate_maps));
                $msg .= "*Koordinat:* {$mapsUrl}\n";
            }
        }

        if ($customers->count() === 5) {
            $msg .= "────────────────\n";
            $msg .= "_Hanya menampilkan 5 hasil pertama. Coba kata kunci lebih spesifik._";
        }

        $this->waha->sendMessage($replyTo, $msg);
        return response()->json(['status' => 'found']);
    }

    protected function handleJadwalMaintenance($user, $cid, $tanggal, $jam, $kendala, $replyTo)
    {
        // Validate customer
        $customer = Customer::where('cid', $cid)->first();
        if (!$customer) {
            $this->waha->sendMessage($replyTo, "⚠️ CID *{$cid}* tidak ditemukan.");
            return response()->json(['status' => 'not found']);
        }

        // Validate date
        try {
            $tglCarbon = Carbon::createFromFormat('Y-m-d', $tanggal);
        } catch (\Exception $e) {
            $this->waha->sendMessage($replyTo, "⚠️ Format tanggal salah. Gunakan format *YYYY-MM-DD*.\nContoh: 2026-02-25");
            return response()->json(['status' => 'invalid date']);
        }

        // Validate time
        if (!preg_match('/^\d{2}:\d{2}$/', $jam)) {
            $this->waha->sendMessage($replyTo, "⚠️ Format jam salah. Gunakan format *HH:MM*.\nContoh: 09:00");
            return response()->json(['status' => 'invalid time']);
        }

        $ticket = Ticket::create([
            'jenis'            => 'maintenance',
            'cid'              => $cid,
            'kendala'          => $kendala,
            'status'           => 'open',
            'created_by'       => $user->id,
            'created_by_role'  => $user->jabatan ?? 'admin',
            'tanggal_kunjungan'=> $tanggal,
            'jam'              => $jam . ':00',
            'hari'             => $tglCarbon->locale('id')->isoFormat('dddd'),
        ]);

        $ticketNo = $this->generateTicketNo($ticket);
        $hariNama = $tglCarbon->locale('id')->isoFormat('dddd, DD MMMM YYYY');

        $msg = "✅ *Jadwal Maintenance Berhasil Dibuat!*\n\n";
        $msg .= "*ID:* #{$ticket->id}\n";
        $msg .= "*No:* {$ticketNo}\n";
        $msg .= "*Customer:* {$customer->nama}\n";
        if ($customer->coordinate_maps) {
            $mapsUrl = (strpos($customer->coordinate_maps, 'http') === 0) 
                ? $customer->coordinate_maps 
                : "https://www.google.com/maps?q=" . urlencode(trim($customer->coordinate_maps));
            $msg .= "*Koordinat:* {$mapsUrl}\n";
        }
        $msg .= "*Jadwal:* {$hariNama}\n";
        $msg .= "*Jam:* {$jam} WIB\n";
        $msg .= "*Kendala:* {$kendala}\n\n";
        $msg .= "Status: *OPEN*\n";
        $msg .= "────────────────\n";
        $msg .= "🔍 Cek detail: `/cek #{$ticket->id}`\n";
        $msg .= "🟢 Ambil job: `/ambil #{$ticket->id}`";

        $this->waha->sendMessage($replyTo, $msg);
        return response()->json(['status' => 'scheduled']);
    }

    protected function handleCekSemuaJadwal($replyTo)
    {
        $tickets = Ticket::with(['customer', 'calonCustomer'])
            ->whereNotNull('tanggal_kunjungan')
            ->whereIn('status', ['open', 'on progress', 'need visit', 'pending'])
            ->orderBy('tanggal_kunjungan', 'asc')
            ->orderBy('jam', 'asc')
            ->get();

        if ($tickets->isEmpty()) {
            $this->waha->sendMessage($replyTo, "📅 *Belum ada jadwal maintenance dengan status aktif.*");
            return response()->json(['status' => 'no schedule']);
        }

        $msg = "📅 *Daftar Semua Jadwal Maintenance*\n";
        $msg .= "_(Status: Open, On Progress, Pending)_\n";
        $msg .= "────────────────\n\n";

        $currentDate = '';
        foreach ($tickets as $t) {
            $tglRaw = Carbon::parse($t->tanggal_kunjungan);
            $tglStr = $tglRaw->locale('id')->isoFormat('dddd, D MMM YYYY');
            
            if ($currentDate !== $tglStr) {
                if ($currentDate !== '') $msg .= "\n";
                $msg .= "📍 *{$tglStr}*\n";
                $currentDate = $tglStr;
            }

            $customerNama = $t->customer ? $t->customer->nama 
                : ($t->calonCustomer ? $t->calonCustomer->nama : '-');
            
            $jam = $t->jam ? Carbon::parse($t->jam)->format('H:i') : '--:--';
            $statusStr = strtoupper($t->status);
            
            $msg .= "• `#[ID: {$t->id}]` - {$jam} WIB\n";
            $msg .= "  *Cust:* {$customerNama}\n";
            $msg .= "  *Ket:* {$t->kendala}\n";
            $msg .= "  *Status:* {$statusStr}\n";
        }

        $this->waha->sendMessage($replyTo, $msg);
        return response()->json(['status' => 'ok']);
    }

    protected function handleCekTicket($ticketId, $replyTo)
    {
        $ticket = Ticket::with(['customer', 'calonCustomer', 'creator', 'replies'])->find($ticketId);

        if (!$ticket) {
            $this->waha->sendMessage($replyTo, "⚠️ Tiket *#{$ticketId}* tidak ditemukan.");
            return response()->json(['status' => 'not found']);
        }

        $customerNama = $ticket->customer ? $ticket->customer->nama
            : ($ticket->calonCustomer ? $ticket->calonCustomer->nama : '-');

        $statusEmoji = match($ticket->status) {
            'open'        => '🟡',
            'on progress' => '🔵',
            'need visit'  => '🟠',
            'selesai'     => '🟢',
            'pending'     => '⚪',
            default       => '⚪',
        };

        $jadwal = '-';
        if ($ticket->tanggal_kunjungan) {
            $jadwal = Carbon::parse($ticket->tanggal_kunjungan)->locale('id')->isoFormat('dddd, DD MMM YYYY');
            if ($ticket->jam) {
                $jadwal .= ' ' . Carbon::parse($ticket->jam)->format('H:i') . ' WIB';
            }
        }

        $ticketNo = $this->generateTicketNo($ticket);
        $lastReply = $ticket->replies->last();

        $msg = "📊 *Detail Tiket #{$ticket->id}*\n";
        $msg .= "────────────────\n";
        $msg .= "*No:* {$ticketNo}\n";
        $msg .= "*Customer:* {$customerNama}\n";
        $msg .= "*Jenis:* " . strtoupper($ticket->jenis) . "\n";
        $msg .= "*Status:* {$statusEmoji} " . strtoupper($ticket->status) . "\n";
        $msg .= "*Kendala:* " . ($ticket->kendala ?? '-') . "\n";
        $msg .= "*Jadwal:* {$jadwal}\n";
        
        $koordinatRaw = $ticket->customer ? $ticket->customer->coordinate_maps 
            : ($ticket->calonCustomer ? $ticket->calonCustomer->koordinat : null);
        if ($koordinatRaw) {
            $mapsUrl = (strpos($koordinatRaw, 'http') === 0) 
                ? $koordinatRaw 
                : "https://www.google.com/maps?q=" . urlencode(trim($koordinatRaw));
            $msg .= "*Koordinat:* {$mapsUrl}\n";
        }
        $msg .= "*PIC Teknisi:* " . ($ticket->pic_teknisi ?? '-') . "\n";
        $msg .= "*Dibuat oleh:* " . ($ticket->creator ? $ticket->creator->name : '-') . "\n";

        if ($lastReply) {
            $msg .= "────────────────\n";
            $msg .= "*Update Terakhir:*\n";
            $msg .= "{$lastReply->reply}\n";
        }

        // Tampilkan quick-action jika status masih aktif
        if (in_array($ticket->status, ['open', 'on progress', 'need visit', 'pending'])) {
            $id = $ticket->id;
            $msg .= "────────────────\n";
            $msg .= "💡 *Aksi Cepat (copy & edit):*\n\n";

            $msg .= "💻 *1. CS/NOC - Cek Remote:*\n";
            $msg .= "`/cekremote #{$id} [hasil diagnosa]`\n\n";

            $msg .= "🚧 *2. CS/NOC - Perlu Kunjungan:* (Visit)\n";
            $msg .= "`/visit #{$id} " . date('Y-m-d') . " [alasan, misal: Ganti router]`\n\n";

            $msg .= "✅ *3. CS/NOC - Selesai Remote:*\n";
            $msg .= "`/remotedone #{$id} [solusi, misal: Clear setelah restart]`\n\n";

            $msg .= "🔵 *4. Teknisi - Update progress:*\n";
            $msg .= "`/update`\n`id: {$id}`\n`pesan: [isi progress/posisi]`\n\n";

            $msg .= "⚪ *Pending / Lanjut Besok:*\n";
            $msg .= "`/update`\n`id: {$id}`\n`status: pending`\n`pesan: Lanjut besok karena hujan`\n`tanggal: " . date('Y-m-d', strtotime('+1 day')) . "`\n`jam: 08:30`\n\n";

            $msg .= "🟢 *Selesai / Done:*\n";
            $msg .= "`/update`\n`id: {$id}`\n`status: selesai`\n`metode: onsite`\n`pesan: Done penanganan...`\n\n";

            if ($ticket->status === 'open' || $ticket->status === 'need visit') {
                $msg .= "🟢 *Ambil job (sendiri):*\n";
                $msg .= "`/ambil #{$id}`\n\n";
                $msg .= "🟢 *Ambil job (tim):*\n";
                $msg .= "`/ambil #{$id} (Nama Rekan, Nama Rekan2)`";
            }
        }

        $this->waha->sendMessage($replyTo, $msg);
        return response()->json(['status' => 'ok']);
    }
}
