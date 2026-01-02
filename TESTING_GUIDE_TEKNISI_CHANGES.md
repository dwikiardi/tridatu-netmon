# TESTING GUIDE - Perubahan Logic Teknisi/Penangani

## 🎯 APA YANG BERUBAH?

**OLD:** Hanya user dengan `role='teknisi'` dan `update_status='on_progress'` yang muncul  
**NEW:** SIAPA PUN (admin/sales/teknisi) yang update dengan `update_status='on_progress'` akan muncul

---

## 🧪 TEST SCENARIOS

### Test 1: Admin Update Ticket

**Setup:**
```bash
php artisan tinker

>>> $ticket = \App\Models\Ticket::find(1);
>>> $ticket->status;
# Pastikan = 'on progress'

>>> $adminUser = \App\Models\User::where('jabatan', 'admin')->first();
>>> echo $adminUser->name . " (ID: " . $adminUser->id . ")";
# e.g., "Admin User (ID: 1)"
```

**Test:**
1. Login sebagai admin
2. Buka halaman detail ticket
3. Klik "Add Update"
4. Set:
   - Priority: High
   - Metode: Onsite
   - Tanggal Kunjungan: 2025-12-26
   - Jam: 10:30
   - Update Status: **on_progress**
   - Catatan: "Admin menuju lokasi"
5. Klik "Send Update"

**Expected Result:**
- ✅ Halaman auto-refresh
- ✅ Section "Penangani Saat Ini (On Progress)" tampil
- ✅ Nama admin terlihat dengan badge **[admin]** (warna abu)
- ✅ Text "Update: 1x" (bukan "Kunjungan")
- ✅ Tanggal terakhir: 26-12-2025

**Actual Result:** 
- [ ] Sesuai harapan
- [ ] Ada perbedaan (tulis detail di bawah)

```
Catatan:
_______________________________________________________
_______________________________________________________
```

---

### Test 2: Multiple Roles Update Ticket

**Setup:**
Pastikan sudah ada 3 user dengan role berbeda:
```bash
php artisan tinker

>>> \App\Models\User::where('jabatan', 'admin')->count();
# Must >= 1

>>> \App\Models\User::where('jabatan', 'sales')->count();
# Must >= 1

>>> \App\Models\User::where('jabatan', 'teknisi')->count();
# Must >= 1
```

**Test:**
1. Admin update ticket dengan on_progress
2. Sales update ticket dengan on_progress  
3. Teknisi update ticket dengan on_progress

**Expected Result:**
- ✅ Section "Penangani Saat Ini" tampil 3 nama
- ✅ 3 badge berbeda: [admin], [sales], [teknisi]
- ✅ Warna berbeda: abu, kuning, biru
- ✅ Sorted by latest first
- ✅ Section "Daftar Petugas" juga tampil 3 nama

**Actual Result:**
- [ ] Sesuai harapan
- [ ] Ada perbedaan

```
Catatan:
_______________________________________________________
_______________________________________________________
```

---

### Test 3: Status Bukan on_progress

**Test:**
1. Admin update ticket dengan status: **pending** (bukan on_progress)
2. Buka halaman detail
3. Lihat "Penangani Saat Ini" dan "Daftar Petugas"

**Expected Result:**
- ✅ Admin TIDAK muncul di "Penangani Saat Ini" (karena status pending, bukan on_progress)
- ✅ Admin MASIH muncul di "Daftar Petugas" (semua orang tercatat)
- ✅ Badge di "Daftar Petugas" bisa [primary] (on_progress) atau [secondary] (pending)

**Actual Result:**
- [ ] Sesuai harapan
- [ ] Ada perbedaan

```
Catatan:
_______________________________________________________
_______________________________________________________
```

---

### Test 4: Empty State

**Test:**
1. Buat ticket baru (status = open)
2. Buka halaman detail

**Expected Result:**
- ✅ Section "Penangani Saat Ini" TIDAK tampil (karena status bukan on_progress)
- ✅ Section "Daftar Petugas" tampil dengan: "Belum ada update"
- ✅ Tidak ada error di console

**Actual Result:**
- [ ] Sesuai harapan
- [ ] Ada perbedaan

```
Catatan:
_______________________________________________________
_______________________________________________________
```

---

### Test 5: Browser Console Check

**Test:**
1. Buka halaman detail ticket dengan data
2. Buka DevTools (F12)
3. Masuk ke Console tab
4. Jalankan:

```javascript
// Cek apakah loadTeknisi() jalan
console.log('loadTeknisi() interval set');

// Cek data replies
$.ajax({
  url: '/ticketing/api/replies?ticket_id=1',
  type: 'GET'
}).done(function(data) {
  console.log('Replies:', data);
  // Lihat ada yang user_role='admin' atau role='sales'?
});
```

**Expected Result:**
- ✅ Tidak ada red error
- ✅ Replies array berisi data dengan berbagai user_role
- ✅ Network tab show status 200

**Actual Result:**
- [ ] Sesuai harapan
- [ ] Ada error (screenshot console)

```
Error message:
_______________________________________________________
_______________________________________________________
```

---

### Test 6: Hard Refresh Cache

**Test:**
1. Update ticket via form
2. Lihat section update
3. Hard refresh: Ctrl+Shift+F5 (Windows) atau Cmd+Shift+R (Mac)
4. Lihat apakah data masih muncul

**Expected Result:**
- ✅ Data masih muncul setelah hard refresh
- ✅ Tidak ada "Loading..." spinner terus-terusan
- ✅ AJAX call berhasil fetch dari server

**Actual Result:**
- [ ] Sesuai harapan
- [ ] Ada perbedaan

```
Catatan:
_______________________________________________________
_______________________________________________________
```

---

### Test 7: Visit Count Increment

**Test:**
1. Admin update ticket dengan on_progress (1st update)
2. Admin update lagi dengan on_progress (2nd update)  
3. Lihat counter

**Expected Result:**
- ✅ Counter jadi 2 (bukan reset ke 1)
- ✅ Text "Update: 2x" di "Daftar Petugas"
- ✅ Text "Update: 2x" di "Penangani Saat Ini"

**Actual Result:**
- [ ] Sesuai harapan
- [ ] Counter always = 1 (bug)
- [ ] Ada perbedaan lain

```
Catatan:
_______________________________________________________
_______________________________________________________
```

---

### Test 8: Label Verification

**Test:**
1. Buka halaman detail ticket
2. Cek setiap label/header

**Expected Result:**
- ✅ Header 1: "Penangani Saat Ini (On Progress)" (bukan "Teknisi Penangani...")
- ✅ Header 2: "Daftar Petugas yang Update" (bukan "Daftar Teknisi...")
- ✅ Text: "Update:" (bukan "Kunjungan:")
- ✅ Empty: "Tidak ada yang sedang menangani" (bukan "Tidak ada teknisi...")

**Actual Result:**
- [ ] Semua label benar
- [ ] Ada label yang salah:
  - [ ] Header 1 masih lama
  - [ ] Header 2 masih lama
  - [ ] Text "kunjungan" masih ada
  - [ ] Message empty masih lama

```
Catatan:
_______________________________________________________
_______________________________________________________
```

---

## 🐛 BUG REPORT TEMPLATE

Jika ada issue, silakan isi:

```
ISSUE TITLE: [Singkat deskripsi masalah]

EXPECTED BEHAVIOR:
[Apa yang harusnya terjadi]

ACTUAL BEHAVIOR:
[Apa yang terjadi sebenarnya]

STEPS TO REPRODUCE:
1. [Step 1]
2. [Step 2]
3. [Step 3]

SCREENSHOT/CONSOLE ERROR:
[Paste screenshot atau error message]

BROWSER & OS:
[e.g., Chrome 120 on Windows 11]

DATABASE DATA:
[Kalau relevan, output dari tinker/query]
```

---

## ✅ SIGN OFF CHECKLIST

Setelah semua test berhasil, checklist:

- [ ] Test 1: Admin update → tampil dengan [admin] badge
- [ ] Test 2: Multiple roles → semua tampil dengan badge masing-masing
- [ ] Test 3: Non-on_progress status → tidak tampil di "Penangani", tapi di "Daftar Petugas"
- [ ] Test 4: Empty state → message benar
- [ ] Test 5: Console clean → no errors
- [ ] Test 6: Hard refresh → data persistent
- [ ] Test 7: Counter increment → correct
- [ ] Test 8: Labels → semua updated correctly

**Status:** 
- [ ] PASS - Ready for production
- [ ] NEEDS FIX - Found issues (see bug reports)

