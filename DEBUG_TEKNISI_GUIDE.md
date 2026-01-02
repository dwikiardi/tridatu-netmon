# DEBUGGING GUIDE: "Teknisi Penangani Saat Ini" Kosong

## 🔍 QUICK DIAGNOSIS

Jika halaman ticket detail bagian "Teknisi Penangani Saat Ini" menampilkan "Tidak ada teknisi yang sedang menangani", lakukan step-by-step debugging ini:

---

## STEP 1: Cek apakah ticket status = "on progress"

```bash
# Buka terminal di project root
php artisan tinker

# Di tinker REPL:
>>> $ticket = \App\Models\Ticket::find(1);  // Ganti 1 dengan ticket ID
>>> $ticket->status;
=> "on progress"  // Harus 'on progress' agar section tampil!
```

**Jika status bukan 'on progress':**
- Section "Teknisi Penangani Saat Ini" tidak akan tampil sama sekali (disembunyikan oleh conditional di blade)
- Perlu ada update dengan status yang benar terlebih dahulu

---

## STEP 2: Cek data di ticket_replies table

```sql
-- Database query
SELECT 
  id,
  ticket_id,
  user_id,
  role,
  update_status,
  tanggal_kunjungan,
  jam_kunjungan,
  created_at
FROM ticket_replies
WHERE ticket_id = 1
ORDER BY created_at DESC;
```

**Harus ada minimal 1 row dengan:**
- `role = 'teknisi'` 
- `update_status = 'on_progress'`

Jika tidak ada atau kosong → Lanjut ke STEP 3

---

## STEP 3: Cek apakah field ada di database

```sql
-- Check struktur table ticket_replies
DESCRIBE ticket_replies;

-- Output harus include kolom:
-- - user_id
-- - role
-- - update_status
-- - tanggal_kunjungan (BARU)
-- - jam_kunjungan (BARU)
```

**Jika field `tanggal_kunjungan` atau `jam_kunjungan` tidak ada:**
```bash
# Run migration untuk menambah field
php artisan migrate

# Atau jika perlu force
php artisan migrate --force
```

---

## STEP 4: Cek user dengan role 'teknisi' ada

```sql
-- Cek users dengan jabatan teknisi
SELECT id, name, jabatan FROM users WHERE jabatan = 'teknisi';

-- Harus ada minimal 1 user dengan jabatan = 'teknisi'
```

**Jika tidak ada teknisi:**
```php
// Tinker - create test user
>>> $teknisi = \App\Models\User::create([
>>>   'name' => 'Budi Santoso',
>>>   'email' => 'budi@example.com',
>>>   'jabatan' => 'teknisi',
>>>   'password' => bcrypt('password123')
>>> ]);
>>> $teknisi->id;
=> 5
```

---

## STEP 5: Buat dummy data untuk testing

```php
// Buka tinker
php artisan tinker

// Get/Create ticket
>>> $ticket = \App\Models\Ticket::find(1);

// Get teknisi user
>>> $teknisi = \App\Models\User::where('jabatan', 'teknisi')->first();
>>> $teknisi->id;
=> 3

// Get current user (untuk created_by TicketReply)
>>> $admin = \App\Models\User::where('jabatan', 'admin')->first();

// Create reply dengan status on_progress
>>> \App\Models\TicketReply::create([
>>>   'ticket_id' => 1,
>>>   'user_id' => $teknisi->id,  // ← PENTING: user yang role teknisi
>>>   'reply' => 'Teknisi sedang menuju lokasi customer',
>>>   'role' => 'teknisi',  // ← PENTING: role = teknisi
>>>   'update_status' => 'on_progress',  // ← PENTING
>>>   'tanggal_kunjungan' => '2025-12-26',
>>>   'jam_kunjungan' => '10:30'
>>> ]);

// Update ticket status
>>> $ticket->update([
>>>   'status' => 'on progress',
>>>   'teknisi_id' => $teknisi->id,
>>>   'tanggal_kunjungan' => '2025-12-26',
>>>   'jam' => '10:30'
>>> ]);
```

---

## STEP 6: Verifikasi data dengan query kompleks

```php
// Tinker - query lengkap untuk simulasi yang dilakukan JavaScript
>>> $ticket_id = 1;

>>> $replies = \App\Models\TicketReply::where('ticket_id', $ticket_id)
>>>   ->with('user')
>>>   ->get();

>>> $replies->map(function($r) {
>>>   return [
>>>     'user_name' => $r->user->name,
>>>     'user_role' => $r->role,
>>>     'update_status' => $r->update_status,
>>>     'created_at' => $r->created_at->toDateTimeString(),
>>>   ];
>>> });

// Harusnya ada reply dengan:
// user_role = 'teknisi'
// update_status = 'on_progress'
```

---

## STEP 7: Cek JavaScript di browser

1. **Buka Halaman ticket detail**
2. **Buka Developer Tools (F12)**
3. **Masuk ke Console tab**
4. **Jalankan:**
   ```javascript
   // Simulasi AJAX call untuk get replies
   $.ajax({
     url: '/ticketing/api/replies?ticket_id=1',
     type: 'GET'
   }).done(function(data) {
     console.log('Replies:', data);
     // Cek ada yang role='teknisi' dan update_status='on_progress'?
   });
   ```

5. **Lihat Network tab**
   - Filter: XHR
   - Cari request ke `/ticketing/api/replies`
   - Response harus berisi array dengan teknisi yang status-nya on_progress

---

## STEP 8: Clear cache dan reload

```bash
# Di terminal
php artisan cache:clear
php artisan view:clear
php artisan config:cache

# Di browser
Ctrl+Shift+Delete → Clear browsing data
Ctrl+F5 (Hard refresh)
```

---

## 🎯 CHECKLIST LENGKAP

Sebelum mulai debugging, pastikan:

- [ ] **Production Environment** - Tidak ada display error yang hide actual problem
- [ ] **Database Connection** - Sudah terhubung dengan database yang benar
- [ ] **All Migrations Run** - `php artisan migrate:status` menunjukkan semua ✓
- [ ] **Valid Ticket ID** - Ticket dengan ID tersebut ada di database
- [ ] **Ticket Status = "on progress"** - Bukan status lain
- [ ] **Browser Cache Clear** - F5 atau Ctrl+Shift+Delete

---

## 📋 COMMON ISSUES & FIXES

| Problem | Cause | Fix |
|---------|-------|-----|
| Section tidak muncul | Ticket status bukan "on progress" | Update ticket ke status "on progress" |
| "Tidak ada teknisi" | Tidak ada reply dengan role=teknisi | Add Update dengan teknisi yang dipilih |
| "Tidak ada teknisi" | Reply ada tapi update_status ≠ on_progress | Change update_status ke "on_progress" |
| "Tidak ada teknisi" | user_id di reply tidak punya role teknisi | Pilih user dengan jabatan=teknisi di form |
| JavaScript error | Field tanggal_kunjungan/jam_kunjungan kosong | Migrate database untuk add field |
| Data tidak update | Browser cache | Hard refresh Ctrl+Shift+F5 |

---

## 🧪 TESTING SCENARIO - END TO END

### Scenario: Admin assign technician via "Add Update"

**1. Create test ticket (if not exist)**
```php
php artisan tinker
>>> $ticket = \App\Models\Ticket::create([
>>>   'cid' => 'TDN-001',
>>>   'jenis' => 'survey',
>>>   'status' => 'open',
>>>   'created_by' => 1,
>>>   'created_by_role' => 'admin'
>>> ]);
>>> $ticket->id;
=> 100
```

**2. Get teknisi user ID**
```php
>>> $teknisi = \App\Models\User::where('jabatan', 'teknisi')->first();
>>> echo "Teknisi ID: " . $teknisi->id;
Teknisi ID: 5
```

**3. Simulate "Add Update" form submission**
```bash
# Via browser or curl
curl -X POST http://localhost:8000/api/ticket/store-reply \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "ticket_id": 100,
    "reply": "Teknisi dispatch ke lokasi",
    "update_status": "on_progress",
    "priority": "high",
    "metode_penanganan": "onsite",
    "tanggal_kunjungan": "2025-12-26",
    "jam": "10:30",
    "teknisi_id": 5
  }'
```

**4. Check if data saved**
```php
>>> $ticket->refresh();
>>> $ticket->status;
=> "on progress"
>>> $ticket->teknisi_id;
=> 5

>>> $ticket->replies()->latest()->first();
=> TicketReply object with role='teknisi', update_status='on_progress'
```

**5. Open ticket detail page**
- Navigate to: `http://localhost:8000/ticketing/{ticket_id}/detail`
- Section "Teknisi Penangani Saat Ini" harus menampilkan nama teknisi

---

## 📊 API RESPONSE FORMAT

Ketika JavaScript call `/ticketing/api/replies?ticket_id=100`, response harus seperti:

```json
[
  {
    "id": 1,
    "user_name": "Budi Santoso",
    "user_role": "teknisi",
    "reply": "Teknisi dispatch ke lokasi",
    "update_status": "on_progress",
    "tanggal_kunjungan": "2025-12-26",
    "jam_kunjungan": "10:30",
    "created_at": "26-12-2025 10:30",
    "created_at_diff": "1 minute ago"
  },
  {
    "id": 2,
    "user_name": "Admin User",
    "user_role": "admin",
    "reply": "Ticket dibuat",
    "update_status": null,
    "tanggal_kunjungan": null,
    "jam_kunjungan": null,
    "created_at": "26-12-2025 09:00",
    "created_at_diff": "2 hours ago"
  }
]
```

JavaScript akan filter:
```javascript
replies.filter(r => r.user_role === 'teknisi' && r.update_status === 'on_progress')
// Return: [reply dengan id=1]
```

---

## 🔧 DIRECT SQL INSERT (Last Resort)

Jika form tidak bekerja, bisa langsung insert ke database:

```sql
-- Update ticket status
UPDATE tickets 
SET status = 'on progress', teknisi_id = 5, tanggal_kunjungan = '2025-12-26', jam = '10:30'
WHERE id = 100;

-- Insert reply dari teknisi
INSERT INTO ticket_replies (ticket_id, user_id, reply, role, update_status, tanggal_kunjungan, jam_kunjungan, created_at, updated_at)
VALUES (100, 5, 'Teknisi on the way', 'teknisi', 'on_progress', '2025-12-26', '10:30', NOW(), NOW());
```

Then reload page, teknisi should appear in "Teknisi Penangani Saat Ini" section.

