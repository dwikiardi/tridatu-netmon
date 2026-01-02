# DOKUMENTASI FITUR REPORT

## 📋 Daftar Lengkap File yang Dibuat

### 1. Controllers
- **`app/Http/Controllers/report/ReportController.php`** - Controller utama dengan semua logic untuk Report

### 2. Models  
- **`app/Models/ReportFilter.php`** - Model untuk menyimpan preferensi filter

### 3. Policies
- **`app/Policies/ReportFilterPolicy.php`** - Authorization policy untuk ReportFilter

### 4. Migrations
- **`database/migrations/2025_01_19_000001_create_report_filters_table.php`** - Migration untuk tabel report_filters

### 5. Views
- **`resources/views/content/report/table-report.blade.php`** - View utama dengan tabs
- **`resources/views/content/report/customer-report.blade.php`** - Tab Report Data Customer
- **`resources/views/content/report/maintenance-report.blade.php`** - Tab Report Maintenance
- **`resources/views/content/report/pdf-customer.blade.php`** - Template PDF untuk report customer
- **`resources/views/content/report/pdf-maintenance.blade.php`** - Template PDF untuk report maintenance

### 6. Routing & Menu
- **`routes/web.php`** - Update dengan route-route Report
- **`resources/menu/verticalMenu.json`** - Menambah menu Report di sidebar

---

## 🎯 Fitur Utama

### 1. REPORT DATA CUSTOMER

#### Informasi Summary:
- Total Pelanggan
- Pelanggan Aktif
- Total Revenue (dari pelanggan aktif)
- Pelanggan dengan koneksi 100 Mbps

#### Filter yang Tersedia:
- **Packet** - Cari berdasarkan paket (contoh: 100 Mbps)
- **Min/Max Pembayaran** - Filter range pembayaran per bulan
- **Status** - Active/Inactive
- **Sales** - Nama sales person
- **Search** - Pencarian general

#### Fitur Tambahan:
✅ **Simpan Filter** - Bisa menyimpan filter preset (misal: "Pelanggan 500rb", "100 Mbps Users")
✅ **Export Excel** - Export data ke CSV
✅ **Export PDF** - Export data ke PDF

#### Kasus Penggunaan:
```
Owner/CEO ingin tahu:
- Berapa jumlah pelanggan yang bayar 500rb? 
  → Filter pembayaran dari 500000-500000
  
- Berapa jumlah pelanggan 100 Mbps?
  → Filter packet = "100 Mbps"
  
- Total pelanggan aktif?
  → Lihat di Summary card atau filter status = active
```

---

### 2. REPORT MAINTENANCE/TICKETING

#### Informasi Summary:
- Total Ticket
- Ticket Selesai (resolved)
- Ticket Pending
- Top 5 Teknisi (by visit count)
- Top 5 Pelanggan Paling Sering Dikunjungi

#### Filter yang Tersedia:
- **Teknisi** - Cari berdasarkan nama teknisi (contoh: "Dwiki")
- **Customer ID** - ID pelanggan/villa
- **Status** - Resolved/Pending/Open
- **Jenis** - Maintenance/Troubleshooting/Installation
- **Date Range** - Filter by tanggal kunjungan
- **Search** - Pencarian general

#### Fitur Tambahan:
✅ **Simpan Filter** - Bisa menyimpan filter preset (misal: "Teknisi Dwiki", "Villa XXX")
✅ **Export Excel** - Export data ke CSV
✅ **Export PDF** - Export data ke PDF

#### Kasus Penggunaan:
```
Manajemen ingin tahu:
- Teknisi Dwiki pernah ke villa mana saja?
  → Filter teknisi = "Dwiki", lihat hasilnya
  
- Villa XXX sudah berapa kali dikunjungi?
  → Filter customer ID = "villa_xxx_code", 
    lihat di summary "Top Pelanggan Dikunjungi"
  
- Berapa maintenance yang sudah diselesaikan bulan ini?
  → Filter date range + status = "resolved"
```

---

## 🚀 Cara Menggunakan

### Step 1: Buka Report
1. Login ke sistem
2. Di sidebar, klik menu **"Report"** (icon chart)
3. Pilih tab "Report Data Customer" atau "Report Maintenance"

### Step 2: Filter Data

**Report Customer:**
```
Contoh 1: Pelanggan pembayaran 500rb
- Min Pembayaran: 500000
- Max Pembayaran: 500000
- Klik "Filter"

Contoh 2: Pelanggan 100 Mbps yang active
- Packet: 100 Mbps
- Status: active
- Klik "Filter"
```

**Report Maintenance:**
```
Contoh 1: Cari kunjungan Dwiki ke villa
- Teknisi: Dwiki
- Klik "Filter"

Contoh 2: Maintenance di villa tertentu
- Customer ID: villa_id
- Status: resolved
- Klik "Filter"
```

### Step 3: Simpan Filter (Opsional)
1. Setelah mengatur filter, klik "Simpan Filter"
2. Masukkan nama filter (contoh: "Pelanggan 500rb")
3. Klik "Simpan"
4. Filter akan muncul sebagai badge di bawah
5. Klik badge untuk apply filter lagi di masa depan

### Step 4: Export Data
- **Excel** - Klik tombol "Export Excel", file CSV akan download
- **PDF** - Klik tombol "Export PDF", akan buka di tab baru

---

## 🔧 Konfigurasi & Customization

### Menambah Filter Baru

Edit file `app/Http/Controllers/report/ReportController.php`:

**Untuk Customer Report:**
```php
// Di method getReportCustomer(), tambah filter:
$minSpeed = $request->input('min_speed');
$maxSpeed = $request->input('max_speed');

if ($minSpeed || $maxSpeed) {
    $query->where(function ($q) use ($minSpeed, $maxSpeed) {
        // logic filter speed
    });
}
```

**Untuk Maintenance Report:**
```php
// Di method getReportMaintenance(), tambah filter:
$priority = $request->input('priority');

if (!empty($priority)) {
    $query->where('priority', $priority);
}
```

---

## 📊 Database Schema

### Tabel: report_filters

```sql
CREATE TABLE report_filters (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type ENUM('customer', 'maintenance') NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    filters JSON NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY unique_user_name_type (user_id, name, type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 🔐 Security

### Authorization
- Filter hanya bisa dihapus oleh user yang membuatnya
- Filter tersimpan per user (tidak bisa lihat filter orang lain)
- CSRF protection untuk POST/DELETE request

---

## 🐛 Troubleshooting

### Export PDF tidak muncul
- Pastikan sudah install PHP extensions yang diperlukan
- Cek error log di `storage/logs/laravel.log`

### Filter tidak tersimpan
- Pastikan sudah login
- Check database `report_filters` table
- Pastikan tidak ada unique constraint violation

### Data tidak muncul di tabel
- Clear cache: `php artisan cache:clear`
- Check browser console untuk error
- Verify query parameters di Network tab

---

## 📝 Routes

```
GET    /report/view                           - Tampilkan halaman report
GET    /report/customer/data                  - Get data customer (AJAX)
GET    /report/maintenance/data               - Get data maintenance (AJAX)
GET    /report/customer/summary               - Get customer summary (AJAX)
GET    /report/maintenance/summary            - Get maintenance summary (AJAX)
POST   /report/filter/save                    - Simpan filter preference
GET    /report/filters/{type}                 - Get saved filters
DELETE /report/filter/{id}                    - Delete saved filter
GET    /report/export/excel                   - Export ke Excel
GET    /report/export/pdf                     - Export ke PDF
```

---

## 📱 Frontend Features

### DataTables
- Sortable columns
- Pagination
- Server-side processing
- Search functionality

### UI Components
- Bootstrap tabs
- Cards untuk summary
- Forms untuk filters
- Badges untuk saved filters
- Modals (opsional)

---

## 🎓 Tips & Best Practices

1. **Untuk Performance:**
   - Filter besar data dengan kriteria spesifik
   - Jangan export terlalu banyak data sekaligus
   - Gunakan saved filters untuk query yang sering digunakan

2. **Untuk Accuracy:**
   - Pastikan data customer dan ticket sudah lengkap
   - Check tanggal format consistency
   - Verify pembayaran_perbulan field

3. **Untuk User Experience:**
   - Berikan nama filter yang deskriptif
   - Gunakan kombinasi filter untuk hasil lebih spesifik
   - Export PDF untuk presentasi, Excel untuk analysis

---

## 🔄 Update Log

- **v1.0** (19-01-2025)
  - ✅ Report Data Customer dengan filter advanced
  - ✅ Report Maintenance dengan analytics
  - ✅ Save filter preferences
  - ✅ Export Excel & PDF
  - ✅ Summary cards & statistics

---

**Dibuat untuk:** Tridatu Network Monitoring System  
**Tanggal:** 19 Januari 2025  
**Versi:** 1.0
