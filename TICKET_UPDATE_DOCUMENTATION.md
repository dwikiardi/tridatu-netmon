# Ticket System Update - Forum/Thread Feature

## Overview
Sistem ticketing telah diupdate dengan fitur forum/thread yang memungkinkan komunikasi antara sales, teknisi, dan admin mengenai ticket yang dibuat.

## Perubahan Utama

### 1. **Database Schema Changes**
- **New Table: `ticket_replies`**
  - Menyimpan semua reply/update pada setiap ticket
  - Kolom: id, ticket_id, user_id, reply, role, timestamps
  - Foreign key ke tickets dan users

- **Updated Table: `tickets`**
  - Tambah kolom `created_by` (user_id yang membuat ticket)
  - Tambah kolom `created_by_role` (role pembuat: sales/teknisi/admin)

### 2. **Models**
- **Ticket Model**
  - Relasi `creator()` - belongs to User
  - Relasi `replies()` - has many TicketReply

- **TicketReply Model (Baru)**
  - Relasi `ticket()` - belongs to Ticket
  - Relasi `user()` - belongs to User

### 3. **Controller Methods**
- **showDetailPage($ticketId)**
  - Menampilkan halaman detail ticket dengan forum thread
  - URL: `/ticketing/{ticketId}`

- **getTicketDetail(Request $request)**
  - API untuk mendapatkan detail ticket
  - Route: `GET /ticketing/api/detail`

- **storeReply(Request $request)**
  - API untuk menambah reply/update ticket
  - Route: `POST /ticketing/api/reply`
  - Hanya user yang authenticated yang bisa reply

- **getReplies(Request $request)**
  - API untuk mendapatkan semua replies ticket
  - Route: `GET /ticketing/api/replies`

### 4. **Views**
- **ticket-detail.blade.php (Baru)**
  - Halaman detail ticket seperti forum thread
  - Menampilkan:
    - Info ticket (CID, Customer, Priority, Status, dll)
    - Customer details (Contact, Address)
    - Visit details (Date, Time, Technician)
    - Forum section dengan semua replies
    - Form untuk add reply
  - Auto-refresh replies setiap 3 detik

- **table-ticket.blade.php (Updated)**
  - Tambah button baru (link icon) yang redirect ke detail page
  - Tetap ada button modal detail, edit, delete untuk backward compatibility

### 5. **Features**

#### Ticket Forum/Thread
- Setiap ticket punya ID unik (contoh: #00015 untuk ticket dengan id 15)
- Creator info ditampilkan (who, role, when)
- Replies ditampilkan seperti forum dengan:
  - Username dan role (sales/teknisi/admin)
  - Content
  - Timestamp (absolute dan relative)
  
#### Auto-Refresh
- Replies di-refresh otomatis setiap 3 detik
- No need to manually refresh page

#### Security
- Hanya user yang authenticated bisa add reply
- User role otomatis tercatat saat add reply
- Creator info tercatat saat ticket dibuat

## Usage

### Membuat Ticket
1. Ke halaman Ticketing
2. Click "Add Ticket"
3. Isi form seperti biasa
4. Save
5. System otomatis record creator (user yang login) dan role-nya

### Lihat Ticket & Add Update
1. Di table ticket, ada button baru (link icon) untuk buka halaman detail
2. Atau tetap bisa click icon "eye" untuk modal detail (old method)
3. Di halaman detail:
   - Lihat semua info ticket
   - Scroll ke section "Ticket Updates / Forum"
   - Lihat semua updates/replies dari thread
   - Ketik update di textarea "Add update or reply..."
   - Click "Send"
   - Reply langsung muncul di forum

## Routes

```
GET  /ticketing/view              - List tickets (table view)
GET  /ticketing/{ticketId}        - Detail ticket page (new)
POST /ticketing/api/reply         - Add reply (new)
GET  /ticketing/api/replies       - Get all replies (new)
GET  /ticketing/api/detail        - Get ticket detail via API (new)
```

## Migration Files
- `2025_12_22_000001_update_tickets_table_add_creator.php`
- `2025_12_22_000002_create_ticket_replies_table.php`

## Next Steps (Optional)
- Tambah attachment support untuk replies
- Tambah notification ke users yang assign di ticket
- Tambah email notification saat ada reply baru
- Tambah search/filter di halaman detail
- Tambah mention @user functionality
