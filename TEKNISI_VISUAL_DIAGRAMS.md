# ALUR TEKNISI - VISUAL DIAGRAMS

## 1️⃣ DATA FLOW - Dari Form ke Display

```
┌──────────────────────────────────────────────────────────────────────────┐
│ USER (Admin/Supervisor) - Klik "Add Update"                             │
└───────────────────────────────┬──────────────────────────────────────────┘
                                 │
                                 ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ MODAL FORM - Isi field:                                                  │
│ • Priority: High                                                         │
│ • Metode Penanganan: Onsite                                             │
│ • Tanggal Kunjungan: 2025-12-26                    ◄─── APPEARS WHEN    │
│ • Jam Kunjungan: 10:30                                   METODE=ONSITE  │
│ • Teknisi yang Berkunjung: [Dropdown - Select]     ◄─── CRITICAL!      │
│ • Update Status: on_progress                       ◄─── MUST BE THIS!   │
│ • Catatan: "Teknisi dispatch ke lokasi"                                 │
└───────────────────────────────┬──────────────────────────────────────────┘
                                 │
                    Klik "Send Update" Button
                                 │
                                 ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ BACKEND - POST /api/ticket/store-reply                                   │
│                                                                           │
│ 1. Validate input:                                                       │
│    ✓ ticket_id valid?                                                   │
│    ✓ teknisi_id refers ke user dengan jabatan=teknisi?                  │
│    ✓ update_status dalam enum?                                          │
│                                                                           │
│ 2. Create TicketReply:                                                   │
│    ┌─────────────────────────────────────────────────────────┐          │
│    │ ticket_replies table INSERT:                            │          │
│    │ - ticket_id: 100                                        │          │
│    │ - user_id: Auth::id() (Admin ID)                       │          │
│    │ - reply: "Teknisi dispatch ke lokasi"                  │          │
│    │ - role: Auth::user()->jabatan (admin)                  │          │
│    │ - update_status: 'on_progress'                         │          │
│    │ - tanggal_kunjungan: '2025-12-26'                      │          │
│    │ - jam_kunjungan: '10:30'                               │          │
│    │ - created_at: NOW()                                    │          │
│    └─────────────────────────────────────────────────────────┘          │
│                                                                           │
│ 3. Update Ticket:                                                        │
│    ┌─────────────────────────────────────────────────────────┐          │
│    │ tickets table UPDATE:                                   │          │
│    │ - status: 'on progress' (mapped dari update_status)    │          │
│    │ - priority: 'high'                                      │          │
│    │ - metode_penanganan: 'onsite'                          │          │
│    │ - tanggal_kunjungan: '2025-12-26'                      │          │
│    │ - jam: '10:30'                                          │          │
│    │ - teknisi_id: 5                                         │          │
│    │ - hari: 'Friday' (auto from tanggal)                   │          │
│    │ - updated_at: NOW()                                    │          │
│    └─────────────────────────────────────────────────────────┘          │
│                                                                           │
│ Return JSON: { message: 'success', status: true }                       │
└───────────────────────────────┬──────────────────────────────────────────┘
                                 │
                     Response success to browser
                                 │
                                 ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ BROWSER - JavaScript loadTeknisi() (AJAX refresh every 5 sec)            │
│                                                                           │
│ GET /ticketing/api/replies?ticket_id=100                                 │
│                                                                           │
│ Backend getReplies() return:                                             │
│ [                                                                        │
│   {                                                                      │
│     "id": 1,                                                            │
│     "user_name": "Admin User",                  ◄─── From user relation  │
│     "user_role": "admin",                       ◄─── From role field    │
│     "reply": "Teknisi dispatch ke lokasi",                             │
│     "update_status": "on_progress",             ◄─── FILTER BY THIS    │
│     "tanggal_kunjungan": "2025-12-26",                                 │
│     "jam_kunjungan": "10:30",                                          │
│     "created_at": "26-12-2025 10:30",                                  │
│     "created_at_diff": "just now"                                      │
│   }                                                                      │
│ ]                                                                        │
│                                                                           │
│ JavaScript filter:                                                       │
│ const currentTeknisiList = replies                                       │
│   .filter(r => r.user_role === 'teknisi' &&   ◄─── MUST BE TEKNISI    │
│              r.update_status === 'on_progress') ◄─── MUST BE ON_PROG   │
│   .sort((a,b) => new Date(b.created_at) - ...)   ◄─── SORT BY NEWEST   │
│                                                                           │
│ Result: [                                                                │
│   {                                                                      │
│     "id": 2,                                    ◄─── DIFFERENT REPLY!   │
│     "user_name": "Budi Santoso",                                        │
│     "user_role": "teknisi",                                             │
│     "update_status": "on_progress",                                     │
│     ...                                                                  │
│   }                                                                      │
│ ]                                                                        │
└───────────────────────────────┬──────────────────────────────────────────┘
                                 │
                 Generate HTML & Update DOM
                                 │
                                 ▼
┌──────────────────────────────────────────────────────────────────────────┐
│ UI - "Teknisi Penangani Saat Ini" Section                               │
│                                                                           │
│ ┌──────────────────────────────────────────────────────────────────────┐│
│ │ 👤 Budi Santoso                                                      ││
│ │ Kunjungan: 1x                                                        ││
│ │ Terakhir: 26-12-2025                                                ││
│ └──────────────────────────────────────────────────────────────────────┘│
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 2️⃣ DATABASE RELATIONSHIPS

```
┌──────────────┐                    ┌────────────────────────┐
│    users     │                    │      tickets           │
├──────────────┤                    ├────────────────────────┤
│ id (PK)      │◄─────────────────┐ │ id (PK)                │
│ name         │                  │ │ cid                    │
│ email        │                  │ │ teknisi_id (FK) ──────┐
│ jabatan      │                  │ │ tanggal_kunjungan      │
│ password     │                  │ │ jam                    │
└──────────────┘                  │ │ status                 │
                                  │ │ created_by (FK) ──────┐
                                  │ │ ...                    │
                                  │ └────────────────────────┘
                                  │
            ┌─────────────────────┴─────────────────────┐
            │                                           │
            │                                           │
         Teknisi                                    Creator
         (jabatan=                                  (Admin/Sales)
          teknisi)


┌──────────────┐                    ┌────────────────────────┐
│    users     │                    │   ticket_replies       │
├──────────────┤                    ├────────────────────────┤
│ id (PK)      │◄────────────────┐  │ id (PK)                │
│ name         │                 │  │ ticket_id (FK) ────┐   │
│ email        │                 │  │ user_id (FK) ──────┤───┤
│ jabatan      │                 │  │ role                │   │
│ password     │                 │  │ reply               │   │
└──────────────┘                 │  │ update_status       │   │
                                 │  │ tanggal_kunjungan   │   │
                                 │  │ jam_kunjungan       │   │
                                 │  │ created_at          │   │
                                 │  └────────────────────────┘
                                 │            │
                            User yang         │
                            buat reply   Ke ticket_replies
                            (bisa admin,
                             sales,
                             atau teknisi)
```

---

## 3️⃣ STATUS & ROLE MAPPING

```
┌─────────────────────────────────────────────────────────────┐
│ TICKET STATUS CHANGES                                       │
└─────────────────────────────────────────────────────────────┘

When Add Update submitted with:
  update_status: 'on_progress'
  
Maps to:
  ticket.status: 'on progress'  ◄─── SHOWS "Teknisi Penangani Saat Ini"

Other mappings:
  update_status          →  ticket.status
  ──────────────────────────────────────────
  'need_visit'           →  'open'
  'on_progress'          →  'on progress'
  'pending'              →  'pending'
  'remote_done'          →  'selesai'
  'done'                 →  'selesai'


┌─────────────────────────────────────────────────────────────┐
│ ROLE HIERARCHY (for display)                                │
└─────────────────────────────────────────────────────────────┘

In TicketReply:
  role = 'admin'      → Badge COLOR: secondary
  role = 'sales'      → Badge COLOR: warning
  role = 'teknisi'    → Badge COLOR: info  ◄─── HIGHLIGHTED!

JavaScript filter specifically looks for:
  role === 'teknisi' (case sensitive!)
```

---

## 4️⃣ FORM VISIBILITY LOGIC

```
┌──────────────────────────────────────────────────────────────┐
│ "Add Update" Modal - Schedule Fields Visibility              │
└──────────────────────────────────────────────────────────────┘

Metode Penanganan Selection:
┌─────────────────────────────────┐
│ Radio: Onsite  │  Radio: Remote  │
└────────┬────────────────┬────────┘
         │                │
         ▼                ▼
    SHOW FIELDS      HIDE FIELDS
    - Tanggal *      - Tanggal
    - Jam *          - Jam
    - Hari *         - Hari
    - Teknisi *      - Teknisi
                      (all optional)

* = required when Onsite


Special Rules:
───────────────────────────────────────
Ticket Jenis: SURVEY
  • Only allow ONSITE (no Remote option)
  • Schedule fields always show
  • Always require Teknisi

Ticket Jenis: INSTALLASI
  • Only allow ONSITE (no Remote option)
  • Schedule fields always show
  • Always require Teknisi

Ticket Jenis: MAINTENANCE
  • Allow both ONSITE & REMOTE
  • Schedule fields show only for ONSITE
  • Teknisi required only for ONSITE
```

---

## 5️⃣ ACTUAL QUERY FLOW IN CODE

```php
// Controller: storeReply()
public function storeReply(Request $request)
{
    // 1. VALIDATE INPUT
    $validated = $request->validate([
        'ticket_id' => 'required|exists:tickets,id',
        'reply' => 'required|string|min:3',
        'update_status' => 'nullable|in:need_visit,on_progress,pending,remote_done,done',
        'teknisi_id' => 'nullable|exists:users,id',
        'tanggal_kunjungan' => 'nullable|date',
        'jam' => 'nullable|date_format:H:i',
        // ... other fields
    ]);

    // 2. GET CURRENT USER & TICKET
    $user = Auth::user();  // e.g., Admin user
    $ticket = Ticket::findOrFail($validated['ticket_id']);

    // 3. PREPARE UPDATE DATA
    $updateData = [];
    if (!empty($validated['teknisi_id'])) {
        $updateData['teknisi_id'] = $validated['teknisi_id'];  // ← SAVE HERE
    }
    // ... other fields

    // 4. MAP update_status TO ticket.status
    $statusMapping = [
        'on_progress' => 'on progress',  // ← THIS MAPPING
        // ...
    ];
    if (isset($statusMapping[$updateStatus])) {
        $updateData['status'] = $statusMapping[$updateStatus];
    }

    // 5. UPDATE TICKET
    if (!empty($updateData)) {
        $ticket->update($updateData);  // ← UPDATE teknisi_id HERE
    }

    // 6. CREATE REPLY RECORD
    TicketReply::create([
        'ticket_id' => $validated['ticket_id'],
        'user_id' => $user->id,        // ← CURRENT USER (Admin)
        'reply' => $validated['reply'],
        'role' => $user->jabatan ?? 'admin',  // ← Role dari user, bukan teknisi!
        'update_status' => $updateStatus,  // ← STORED HERE
        'tanggal_kunjungan' => $validated['tanggal_kunjungan'] ?? null,
        'jam_kunjungan' => $validated['jam'] ?? null,
    ]);

    return response()->json(['message' => 'success']);
}

// Controller: getReplies()
public function getReplies(Request $request)
{
    // 1. GET ALL REPLIES FOR TICKET, WITH USER INFO
    $replies = TicketReply::where('ticket_id', $request->ticket_id)
        ->with('user')  // ← Load user relation
        ->orderBy('created_at', 'asc')
        ->get()
        ->map(function($reply) {
            return [
                'id' => $reply->id,
                'user_name' => $reply->user->name,  // ← From users table
                'user_role' => $reply->role,        // ← From ticket_replies.role
                'reply' => $reply->reply,
                'update_status' => $reply->update_status,  // ← FOR FILTERING
                'tanggal_kunjungan' => $reply->tanggal_kunjungan,
                'jam_kunjungan' => $reply->jam_kunjungan,
                'created_at' => $reply->created_at->format('d-m-Y H:i'),
                'created_at_diff' => $reply->created_at->diffForHumans(),
            ];
        });

    return response()->json($replies);
}
```

---

## 6️⃣ JAVASCRIPT FILTERING LOGIC

```javascript
// Inside loadTeknisi() function
$.ajax({
    type: 'GET',
    url: '/ticketing/api/replies',
    data: { ticket_id: ticketId },
    success: function(replies) {
        // replies = [
        //   { user_name: 'Admin', user_role: 'admin', update_status: 'open', ... },
        //   { user_name: 'Budi', user_role: 'teknisi', update_status: 'on_progress', ... },
        //   { user_name: 'Adi', user_role: 'teknisi', update_status: 'pending', ... }
        // ]

        const teknisiSet = new Map();
        const lastUpdates = {};

        // BUILD MAP OF TEKNISI
        replies.forEach(function(reply) {
            if (reply.user_role === 'teknisi' && reply.user_id) {  // ← FILTER 1
                // Build teknisi profile...
                teknisiSet.set(reply.user_id, { ... });
            }
        });

        // FILTER: Current teknisi (on_progress)
        const currentStatus = '{{ $ticket->status }}';
        if (currentStatus === 'on progress' || currentStatus === 'on_progress') {
            const currentTeknisiList = Array.from(teknisiSet.values())
                .filter(t => lastUpdates[t.id] && 
                            lastUpdates[t.id].update_status === 'on_progress')  // ← FILTER 2
                .sort((a, b) => new Date(b.last_update_time) - new Date(a.last_update_time));

            if (currentTeknisiList.length === 0) {
                $('#currentTeknisiContainer').html(
                    '<div class="text-center text-muted py-2"><small>Tidak ada teknisi yang sedang menangani</small></div>'
                );
            } else {
                // Display teknisi
                let currentHtml = '';
                currentTeknisiList.forEach(function(teknisi) {
                    currentHtml += `<div>...${teknisi.name}...</div>`;
                });
                $('#currentTeknisiContainer').html(currentHtml);
            }
        }
    }
});
```

---

## 7️⃣ WHAT NEEDS TO BE TRUE

```
✅ UNTUK "Teknisi Penangani Saat Ini" MUNCUL:
─────────────────────────────────────────────

1. Ticket.status = 'on progress'
   ✓ Check: SELECT status FROM tickets WHERE id=1;
   
2. Minimal 1 reply dengan:
   - role = 'teknisi'
   - update_status = 'on_progress'
   
   ✓ Check: SELECT * FROM ticket_replies 
            WHERE ticket_id=1 
            AND role='teknisi' 
            AND update_status='on_progress';
   
3. User dengan id = reply.user_id punya:
   - name field diisi
   - jabatan = 'teknisi' (optional, tapi role dari reply field)
   
   ✓ Check: SELECT id, name, jabatan FROM users WHERE id=X;

4. Field ada di database:
   - tickets.teknisi_id
   - tickets.status
   - ticket_replies.user_id
   - ticket_replies.role
   - ticket_replies.update_status
   - ticket_replies.tanggal_kunjungan
   - ticket_replies.jam_kunjungan
   
   ✓ Check: DESCRIBE tickets; DESCRIBE ticket_replies;

5. JavaScript tidak error
   - Console F12 clear
   - AJAX call success
   - Response JSON valid
   
   ✓ Check: F12 → Console tab → See if any red errors
```

