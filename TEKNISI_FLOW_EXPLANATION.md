# Alur Teknisi yang Sedang Menangani Ticket

## 📋 RINGKASAN SINGKAT

Teknisi yang sedang menangani ticket **DIAMBIL dari TicketReplies (Forum/Update)** KETIKA teknisi membuat update dengan status `on_progress`. Teknisi TIDAK otomatis diambil saat ticket status menjadi "on progress", tetapi **harus melalui form "Add Update"**.

---

## 🗄️ STRUKTUR DATABASE

### 1. **Table: `tickets`**
Menyimpan informasi utama ticket:
```
- id (primary key)
- cid (customer ID)
- calon_customer_id
- created_by (user ID pembuat)
- created_by_role (admin/sales/teknisi)
- jenis (survey/maintenance/installasi)
- metode_penanganan (onsite/remote)
- priority (low/medium/high/urgent)
- tanggal_kunjungan (tanggal kunjungan field untuk jadwal onsite)
- jam (waktu kunjungan untuk jadwal onsite)
- hari (hari dalam minggu)
- pic_teknisi (nama teknisi, text field)
- teknisi_id (FK ke users table - BARU)
- packet, note, kendala, solusi, hasil
- status (open/pending/on progress/selesai)
- created_at, updated_at
```

### 2. **Table: `ticket_replies`** (Forum/Update per Ticket)
Menyimpan setiap kali ada update/comment dari user:
```
- id (primary key)
- ticket_id (FK ke tickets)
- user_id (FK ke users)
- reply (text komentar/catatan)
- role (sales/teknisi/admin - role pembuat update)
- update_status (need_visit/on_progress/pending/remote_done/done)
- tanggal_kunjungan (tanggal kunjungan - BARU field)
- jam_kunjungan (waktu kunjungan - BARU field)
- created_at, updated_at
```

---

## 🔄 ALUR TEKNISI - STEP BY STEP

### **SKENARIO: Admin membuat ticket dan assign ke Teknisi**

#### **STEP 1: Admin Membuat Ticket (Halaman Table Ticket)**
- Admin submit form: `POST /api/ticket/create`
- Ticket dibuat dengan status `open`
- Field `teknisi_id` di table tickets = **NULL** (belum ada teknisi)
- Di halaman detail ticket, section "Teknisi Penangani Saat Ini" tampil kosong

#### **STEP 2: Admin/User Klik "Add Update" untuk Assign Teknisi**
- Buka Modal "Add Update" di halaman ticket detail
- **PENTING FORM FIELDS:**
  - **Priority**: Pilih level urgency
  - **Jenis Ticket**: Auto-fill dari ticket (tidak bisa diubah)
  - **Metode Penanganan**: Pilih `onsite` atau `remote`
    - Jika **ONSITE** → Muncul form untuk isi:
      - ✅ **Tanggal Kunjungan** (date picker)
      - ✅ **Jam Kunjungan** (time picker)
      - ✅ **Hari** (auto-fill dari tanggal)
      - ✅ **Teknisi yang Berkunjung** (dropdown - CRITICAL!)
    - Jika **REMOTE** → Field jadwal tersembunyi
  - **Update Status**: Pilih dari:
    - `pending` (menunggu)
    - `on_progress` (sedang dikerjakan)
    - `remote_done` / `done` (selesai)
  - **Catatan**: Deskripsi update
  
#### **STEP 3: Submit "Add Update"**
- Form submit: `POST /api/ticket/store-reply`
  ```
  POST /api/ticket/store-reply
  {
    "ticket_id": 1,
    "reply": "Teknisi sudah dispatch ke lokasi customer",
    "update_status": "on_progress",           // ← KUNCI!
    "priority": "high",
    "metode_penanganan": "onsite",
    "tanggal_kunjungan": "2025-12-26",
    "jam": "10:30",
    "hari": "Friday",
    "teknisi_id": 5,                           // ← TEKNISI ID!
    "jenis": "survey"
  }
  ```

#### **STEP 4: Backend Update Data (Controller: storeReply)**
- **Create TicketReply record:**
  ```php
  TicketReply::create([
    'ticket_id' => 1,
    'user_id' => Auth::id(),              // ID user yang membuat update
    'reply' => "...",
    'role' => Auth::user()->jabatan,      // Dari user jabatan
    'update_status' => 'on_progress',
    'tanggal_kunjungan' => '2025-12-26',
    'jam_kunjungan' => '10:30'
  ]);
  ```

- **Update Ticket record:**
  ```php
  $ticket->update([
    'priority' => 'high',
    'metode_penanganan' => 'onsite',
    'tanggal_kunjungan' => '2025-12-26',
    'jam' => '10:30',
    'hari' => 'Friday',
    'teknisi_id' => 5,                    // ← SIMPAN DI SINI!
    'status' => 'on progress'              // Map dari update_status
  ]);
  ```

#### **STEP 5: Frontend Load "Teknisi Penangani Saat Ini"**
JavaScript di halaman detail melakukan:
```javascript
// loadTeknisi() - AJAX every 5 seconds
$.ajax({
  url: '/api/ticket/get-detail',
  data: { ticket_id: {{ $ticket->id }} }
}).done(function(response) {
  const replies = response.replies;
  
  // Cari reply dengan:
  // - user_role === 'teknisi' 
  // - update_status === 'on_progress'
  // - PALING BARU (berdasarkan created_at)
  
  const currentTeknisiList = replies
    .filter(r => r.user_role === 'teknisi' && r.update_status === 'on_progress')
    .sort((a,b) => new Date(b.created_at) - new Date(a.created_at))
  
  // Display nama teknisi, jumlah kunjungan, tanggal terakhir
});
```

---

## 🎯 PENYEBAB "Teknisi Penangani Saat Ini" KOSONG

### ❌ **Masalah 1: Teknisi tidak dipilih di form "Add Update"**
- Field `teknisi_id` tidak diisi
- Teknisi tidak muncul di halaman karena tidak ada reply dengan user yang role teknisi

### ❌ **Masalah 2: Update status bukan "on_progress"**
- Misal: Update dikirim dengan status `pending` atau `done`
- Filter di JavaScript hanya menampilkan yang status-nya `on_progress`
- Solusi: Ubah status ke `on_progress` saat teknisi dispatch

### ❌ **Masalah 3: User yang update bukan role "teknisi"**
- Misal: Admin yang membuat update (role = admin)
- Filter `user_role === 'teknisi'` akan skip record ini
- Solusi: Update harus dibuat oleh user dengan jabatan teknisi

### ❌ **Masalah 4: Field belum ada di database**
- Field `tanggal_kunjungan` dan `jam_kunjungan` di ticket_replies harus ada
- Jika belum run migration, field tidak tersimpan

---

## 📊 FIELD REFERENCE - TICKETS TABLE

```
CREATE TABLE tickets (
  id INT PRIMARY KEY AUTO_INCREMENT,
  cid VARCHAR(50),
  calon_customer_id INT,
  created_by INT,
  created_by_role ENUM('sales','teknisi','admin'),
  jenis ENUM('survey','maintenance','installasi'),
  metode_penanganan ENUM('onsite','remote'),
  priority ENUM('low','medium','high','urgent'),
  tanggal_kunjungan DATE,
  pic_it_lokasi VARCHAR(255),
  no_it_lokasi VARCHAR(50),
  pic_teknisi VARCHAR(255),
  teknisi_id INT (FK ke users.id),  ← DISINI TEKNISI DISIMPAN!
  jam TIME,
  hari VARCHAR(50),
  kendala TEXT,
  solusi TEXT,
  hasil TEXT,
  status ENUM('open','pending','on progress','selesai'),
  packet VARCHAR(100),
  note TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  FOREIGN KEY (teknisi_id) REFERENCES users(id) ON DELETE SET NULL
);
```

---

## 📊 FIELD REFERENCE - TICKET_REPLIES TABLE

```
CREATE TABLE ticket_replies (
  id INT PRIMARY KEY AUTO_INCREMENT,
  ticket_id INT (FK ke tickets.id),
  user_id INT (FK ke users.id),
  reply TEXT,
  role ENUM('sales','teknisi','admin'),
  update_status ENUM(
    'need_visit',
    'on_progress',
    'pending',
    'remote_done',
    'done'
  ),
  tanggal_kunjungan DATE,      ← SCHEDULE FIELDS
  jam_kunjungan TIME,          ← SCHEDULE FIELDS
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 🔧 CHECKLIST TROUBLESHOOTING

Jika "Teknisi Penangani Saat Ini" tidak terlihat, cek:

- [ ] **Database Migration** - Pastikan semua migration sudah dijalankan:
  ```bash
  php artisan migrate --force
  ```
  
- [ ] **Field ada di DB:**
  ```sql
  DESCRIBE tickets;  -- Cek ada teknisi_id?
  DESCRIBE ticket_replies;  -- Cek ada tanggal_kunjungan & jam_kunjungan?
  ```

- [ ] **Data ada di ticket_replies:**
  ```sql
  SELECT * FROM ticket_replies 
  WHERE ticket_id = 1 
  AND update_status = 'on_progress';
  ```

- [ ] **User role check:**
  ```sql
  SELECT user_id, role FROM ticket_replies 
  WHERE ticket_id = 1;
  -- Pastikan ada yang role='teknisi'?
  ```

- [ ] **Browser Console** - Cek JavaScript error saat load detail ticket

- [ ] **Network Tab** - Cek respons dari `/api/ticket/get-detail`
  - Pastikan replies ada
  - Pastikan user_role = 'teknisi'
  - Pastikan update_status = 'on_progress'

---

## 📝 ALTERNATIF: Jika ingin Teknisi diambil dari `tickets.teknisi_id`

Sekarang sistem mengambil dari **TicketReplies (Forum)**. Jika ingin menggunakan field `tickets.teknisi_id` saja (tanpa check forum), bisa modify JavaScript:

```javascript
// Current logic (dari forum):
const currentTeknisiList = replies
  .filter(r => r.user_role === 'teknisi' && r.update_status === 'on_progress')

// Alternative (dari tickets.teknisi_id):
if (ticket.teknisi_id) {
  const teknisiUser = // fetch user dari teknisi_id
  $('#currentTeknisiContainer').html(`
    <div>${teknisiUser.name}</div>
  `);
}
```

---

## 🎬 FLOW DIAGRAM

```
┌─────────────────────────────────────────────────────────────┐
│ TICKET CREATION (Admin)                                     │
│ → Status: open                                              │
│ → teknisi_id: NULL                                          │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│ ADD UPDATE MODAL - Admin/Supervisor fill form:              │
│ ✓ Priority: High                                            │
│ ✓ Metode: Onsite                                            │
│ ✓ Tanggal Kunjungan: 2025-12-26                            │
│ ✓ Jam Kunjungan: 10:30                                      │
│ ✓ Teknisi: Select "Budi Santoso"                           │
│ ✓ Update Status: on_progress                                │
│ ✓ Catatan: "Teknisi dispatch ke lokasi"                     │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│ DATABASE UPDATE (Backend)                                   │
│                                                              │
│ 1. INSERT INTO ticket_replies:                             │
│    - ticket_id: 1                                          │
│    - user_id: Admin ID                                     │
│    - role: admin                                           │
│    - update_status: on_progress                            │
│    - tanggal_kunjungan: 2025-12-26                         │
│    - jam_kunjungan: 10:30                                  │
│                                                              │
│ 2. UPDATE tickets:                                          │
│    - status: on progress                                    │
│    - teknisi_id: 3 (Budi ID)                              │
│    - tanggal_kunjungan: 2025-12-26                         │
│    - jam: 10:30                                            │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│ TEKNISI ADD THEIR OWN UPDATE (Optional)                     │
│ Teknisi bisa submit update dengan:                          │
│ - update_status: on_progress                                │
│ - status ticket: on progress                                │
│ - role: teknisi                                             │
│                                                              │
│ Insert kedua di ticket_replies dgn user_id=Teknisi         │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│ FRONTEND DISPLAY (loadTeknisi AJAX)                         │
│                                                              │
│ Query: ticket_replies where:                                │
│ - ticket_id = 1                                            │
│ - user_role = 'teknisi'                                    │
│ - update_status = 'on_progress'                            │
│ - Sorting: terbaru (created_at DESC)                       │
│                                                              │
│ Show: "Budi Santoso - Kunjungan: 1x, Terakhir: 26-12-2025" │
└─────────────────────────────────────────────────────────────┘
```

---

## 💡 SUMMARY

| Aspek | Penjelasan |
|-------|-----------|
| **Dari mana data teknisi?** | Dari `ticket_replies` (forum update), bukan dari `tickets.teknisi_id` |
| **Kapan teknisi muncul?** | Ketika ada reply dengan `update_status = 'on_progress'` dan `role = 'teknisi'` |
| **Siapa yang bisa input?** | Admin/Supervisor via "Add Update" form, atau teknisi send own update |
| **Apakah otomatis saat status on progress?** | TIDAK - harus melalui form "Add Update" dan pilih teknisi |
| **Field database key** | `tickets.teknisi_id`, `ticket_replies.user_id`, `ticket_replies.update_status` |
| **Migration yang diperlukan** | `add_teknisi_id_to_tickets_table`, `add_schedule_fields_to_ticket_replies_table` |

