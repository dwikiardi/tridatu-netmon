# QUICK REFERENCE - Teknisi Penangani Saat Ini

## 🎯 ONE SENTENCE ANSWER

**Teknisi diambil dari `ticket_replies` table (Forum/Update), bukan dari field `tickets.teknisi_id`. Teknisi akan tampil jika:**
1. Ada reply dengan `role='teknisi'` dan `update_status='on_progress'`
2. Ticket status = `'on progress'`

---

## 📌 WHEN TO USE EACH FILE

| File | Gunakan Ketika |
|------|---|
| **TEKNISI_FLOW_EXPLANATION.md** | Ingin tahu alur lengkap dari A-Z, bagaimana teknisi di-assign, database schema, dll |
| **DEBUG_TEKNISI_GUIDE.md** | "Teknisi tidak muncul" - ikuti step-by-step debugging dengan tinker/SQL |
| **TEKNISI_VISUAL_DIAGRAMS.md** | Visual learner - lihat diagram flow, mapping, query path |
| **THIS FILE** | Butuh jawaban cepat 30 detik |

---

## 🔧 3-MINUTE FIX

```bash
# 1. Open tinker
php artisan tinker

# 2. Check ticket status
>>> $t = \App\Models\Ticket::find(100);
>>> $t->status;
# Must be 'on progress'

# 3. Check reply exists
>>> $t->replies()->where('update_status', 'on_progress')->count();
# Must be >= 1

# 4. If count = 0, create test reply
>>> \App\Models\TicketReply::create([
>>>   'ticket_id' => 100,
>>>   'user_id' => 5,  # teknisi user id
>>>   'reply' => 'Test',
>>>   'role' => 'teknisi',
>>>   'update_status' => 'on_progress',
>>>   'tanggal_kunjungan' => '2025-12-26',
>>>   'jam_kunjungan' => '10:30'
>>> ]);

# 5. Update ticket status
>>> $t->update(['status' => 'on progress', 'teknisi_id' => 5]);

# 6. Reload page - teknisi should appear!
```

---

## 🗄️ DATABASE SCHEMA (Minimal)

```sql
-- TICKETS TABLE
CREATE TABLE tickets (
  id INT PRIMARY KEY,
  status ENUM('open','pending','on progress','selesai'),  -- MUST BE 'on progress'
  teknisi_id INT,  -- Foreign key to users.id
  ...
);

-- TICKET_REPLIES TABLE
CREATE TABLE ticket_replies (
  id INT PRIMARY KEY,
  ticket_id INT,  -- FK to tickets.id
  user_id INT,    -- FK to users.id (who created this reply)
  role ENUM('admin','sales','teknisi'),  -- MUST BE 'teknisi' for display
  update_status ENUM(...,'on_progress',...),  -- MUST BE 'on_progress' for display
  tanggal_kunjungan DATE,
  jam_kunjungan TIME,
  ...
);

-- USERS TABLE
CREATE TABLE users (
  id INT PRIMARY KEY,
  name VARCHAR,
  jabatan ENUM('admin','sales','teknisi'),
  ...
);
```

---

## 📋 FORM SUBMISSION CHECKLIST

Ketika submit "Add Update", pastikan:

- [x] **Metode Penanganan** = `onsite` (jika survey/installasi) atau pilihan lain untuk maintenance
- [x] **Teknisi yang Berkunjung** = Selected (dropdown tidak kosong)
- [x] **Update Status** = `on_progress` (untuk direct assignment)
- [x] **Tanggal Kunjungan** = Filled (jika onsite)
- [x] **Jam Kunjungan** = Filled (jika onsite)
- [x] **Catatan** = Filled (required)

Jika semua di-check, submit akan:
1. Create TicketReply dengan `role='admin'` (role pembuat form)
2. Update Ticket dengan `teknisi_id={selected_user_id}` dan `status='on progress'`
3. TicketReply akan ada tapi role-nya admin, bukan teknisi
4. **OPSI**: Teknisi bisa kirim update sendiri dgn role='teknisi'

---

## 🔍 VERIFICATION QUERY

```sql
-- Check jika teknisi muncul di "Teknisi Penangani Saat Ini"
SELECT 
  tr.user_id,
  u.name,
  tr.role,
  tr.update_status,
  tr.created_at
FROM ticket_replies tr
JOIN users u ON tr.user_id = u.id
WHERE tr.ticket_id = 100
  AND tr.role = 'teknisi'
  AND tr.update_status = 'on_progress'
ORDER BY tr.created_at DESC
LIMIT 1;

-- Harus return exactly 1 row (atau lebih untuk multiple teknisi)
```

---

## ⚠️ COMMON MISTAKES

| ❌ Mistake | ✅ Solution |
|-----------|-----------|
| Select metode=remote, then teknisi field hidden | Select metode=onsite so teknisi field appears |
| Teknisi dropdown kosong | Run: php artisan tinker → User where jabatan=teknisi ada? |
| Submit tapi nothing happens | Check browser console (F12) for error |
| Teknisi muncul tapi wrong | Verify user has proper name & jabatan in users table |
| Data ada tapi halaman blank | Hard refresh Ctrl+Shift+F5 |

---

## 🚀 TECHNOLOGY STACK

| Layer | Tech | Key Point |
|-------|------|-----------|
| **Form** | HTML/Blade | Modal dengan conditional fields |
| **Frontend Logic** | jQuery + AJAX | loadTeknisi() every 5sec |
| **Backend** | Laravel Controller | storeReply() + getReplies() |
| **Database** | MySQL | 2 tables: tickets + ticket_replies |
| **Data Flow** | REST API | POST /api/ticket/store-reply, GET /api/replies |

---

## 🔗 RELATED FILES IN PROJECT

```
app/Models/
  ├── Ticket.php           ← Relation ke TicketReply
  ├── TicketReply.php      ← Model untuk forum
  └── User.php             ← User yang membuat reply

app/Http/Controllers/ticketing/
  └── TicketController.php ← storeReply(), getReplies() methods

resources/views/content/ticketing/
  └── ticket-detail.blade.php  ← UI + JavaScript loadTeknisi()

routes/
  └── web.php              ← API routes definition

database/migrations/
  ├── 2025_12_22_113305_add_teknisi_id_to_tickets_table.php
  └── 2025_12_25_035740_add_schedule_fields_to_ticket_replies_table.php
```

---

## 📞 SUPPORT DECISION TREE

```
Q: Teknisi tidak muncul?
├─ Q: Ticket status = 'on progress'?
│  ├─ NO → Update ticket ke 'on progress' dulu
│  └─ YES → Lanjut
├─ Q: Ada reply dengan role='teknisi' & update_status='on_progress'?
│  ├─ NO → Teknisi atau admin harus submit "Add Update" dengan status ini
│  └─ YES → Lanjut
└─ Q: User dengan ID tersebut punya name?
   ├─ NO → Update user.name field
   └─ YES → Halaman mungkin cache, hard refresh

Q: Form "Add Update" tidak show teknisi dropdown?
└─ Q: Metode Penanganan dipilih?
   ├─ YES & onsite → field seharusnya muncul, check console
   └─ NO atau remote → select onsite dulu

Q: Teknisi dropdown ada tapi kosong?
└─ Q: Ada user dengan jabatan='teknisi'?
   ├─ NO → Create test user atau check DB
   └─ YES → Check loading error di network tab
```

---

## 📊 FIELD MAPPING REFERENCE

```
Form Input               →  Database Field         →  Usage
────────────────────────────────────────────────────────────
Priority                →  tickets.priority       →  Display badge
Metode Penanganan       →  tickets.metode_penanganan  →  Logic for schedule
Tanggal Kunjungan       →  tickets.tanggal_kunjungan  →  Schedule display
Jam Kunjungan           →  tickets.jam            →  Schedule display
Teknisi (dropdown)      →  tickets.teknisi_id     →  FK to users
Update Status (radio)   →  ticket_replies.update_status → Filter for display
Catatan                 →  ticket_replies.reply   →  Comment text

Status Mapping:
Update Status           →  Ticket Status
─────────────────────────────────────
'on_progress'          →  'on progress'  (TRIGGERS display)
'pending'              →  'pending'
'done'                 →  'selesai'
'remote_done'          →  'selesai'
'need_visit'           →  'open'
```

---

## 🎓 KEY CONCEPTS

1. **Forum-based tracking**: Teknisi tidak di-assign langsung ke ticket, tapi tracked melalui reply history
2. **Status + Role filtering**: Display logic: `status='on progress' AND role='teknisi' AND update_status='on_progress'`
3. **Dual update**: Form submission updates BOTH tickets table (untuk teknisi_id) DAN ticket_replies table (untuk forum)
4. **Auto-mapping**: update_status di reply automatically maps ke ticket.status
5. **Multi-teknisi**: Bisa ada multiple teknisi jika mereka semua kirim update dengan on_progress status

