# SETUP GUIDE - FITUR REPORT

## ✅ Checklist Implementasi

Berikut adalah checklist lengkap untuk memverifikasi semua fitur Report sudah ter-setup dengan sempurna:

### 📁 Files & Struktur

- [x] **Controller**: `app/Http/Controllers/report/ReportController.php`
- [x] **Model**: `app/Models/ReportFilter.php`
- [x] **Policy**: `app/Policies/ReportFilterPolicy.php`
- [x] **Migration**: `database/migrations/2025_01_19_000001_create_report_filters_table.php`
- [x] **Views**: 
  - `resources/views/content/report/table-report.blade.php`
  - `resources/views/content/report/customer-report.blade.php`
  - `resources/views/content/report/maintenance-report.blade.php`
  - `resources/views/content/report/pdf-customer.blade.php`
  - `resources/views/content/report/pdf-maintenance.blade.php`
- [x] **Routes**: Updated `routes/web.php` with 11 new routes
- [x] **Menu**: Updated `resources/menu/verticalMenu.json`

### 🗄️ Database

- [x] Migration executed successfully
- [x] Table `report_filters` created
- [x] Schema includes:
  - `id` - Primary key
  - `name` - Filter name
  - `type` - 'customer' or 'maintenance'
  - `user_id` - Foreign key to users
  - `filters` - JSON data
  - `created_at`, `updated_at`

### 🛣️ Routes (11 routes)

```
GET    /report/view                    → ReportController@index
GET    /report/customer/data          → ReportController@getReportCustomer
GET    /report/maintenance/data       → ReportController@getReportMaintenance
GET    /report/customer/summary       → ReportController@getSummaryCustomer
GET    /report/maintenance/summary    → ReportController@getSummaryMaintenance
POST   /report/filter/save            → ReportController@saveFilterPreference
GET    /report/filters/{type}         → ReportController@getSavedFilters
DELETE /report/filter/{id}            → ReportController@deleteFilter
GET    /report/export/excel           → ReportController@exportExcel
GET    /report/export/pdf             → ReportController@exportPdf
```

---

## 🚀 Deployment Steps

### Step 1: Pull Latest Code
```bash
cd c:\Users\user\Desktop\tridatu-netmon
git pull
```

### Step 2: Run Migration (jika belum)
```bash
php artisan migrate
```

### Step 3: Clear Cache
```bash
php artisan cache:clear
php artisan config:cache
```

### Step 4: Verify Routes
```bash
php artisan route:list | findstr report
```

Expected output harus menunjukkan 11 report routes.

### Step 5: Test Akses
1. Buka browser: `http://localhost/report/view` (atau domain Anda)
2. Harusnya muncul page Report dengan 2 tabs
3. Klik tab "Report Data Customer" dan "Report Maintenance"

---

## 📊 Feature Overview

### TAB 1: Report Data Customer

#### Summary Cards:
- Total Pelanggan
- Pelanggan Aktif  
- Total Revenue
- Pelanggan 100 Mbps

#### Filters Available:
- Packet (string search)
- Min/Max Pembayaran (numeric range)
- Status (dropdown: active/inactive)
- Sales (text search)
- General Search

#### Actions:
- Filter & Reset
- Save Filter Preset
- Export to Excel (CSV)
- Export to PDF (HTML)

---

### TAB 2: Report Maintenance

#### Summary Cards:
- Total Ticket
- Ticket Selesai
- Ticket Pending
- Top Teknisi

#### Analytics:
- Top 5 Pelanggan Paling Sering Dikunjungi
- Top 5 Teknisi by Visit Count

#### Filters Available:
- Teknisi (text search)
- Customer ID (text search)
- Status (dropdown: resolved/pending/open)
- Jenis (dropdown: maintenance/troubleshooting/installation)
- Date Range (from-to)
- General Search

#### Actions:
- Filter & Reset
- Save Filter Preset
- Export to Excel (CSV)
- Export to PDF (HTML)

---

## 🔧 Configuration

### Add New Filter to Customer Report

Edit: `app/Http/Controllers/report/ReportController.php`

Method: `getReportCustomer()`

Add this code:
```php
// Get filter input
$newFilter = $request->input('new_filter');

// Add filter logic
if (!empty($newFilter)) {
    $query->where('some_field', 'like', "%{$newFilter}%");
}
```

Then update View: `resources/views/content/report/customer-report.blade.php`

Add form input:
```blade
<div class="col-md-3">
    <label for="filter-new-field" class="form-label">New Field</label>
    <input type="text" class="form-control" id="filter-new-field" placeholder="Search...">
</div>
```

And JavaScript:
```js
d.new_filter = $('#filter-new-field').val();
```

---

### Add New Export Format

Example: Export to XML

Create method in `ReportController.php`:
```php
public function exportXml(Request $request)
{
    $type = $request->input('type', 'customer');
    
    if ($type === 'customer') {
        $data = Customer::all();
    } else {
        $data = Ticket::with(['customer'])->get();
    }
    
    // XML generation logic
    $xml = new SimpleXMLElement('<root/>');
    foreach ($data as $item) {
        // Add items to XML
    }
    
    return response($xml->asXML(), 200, [
        'Content-Type' => 'application/xml',
        'Content-Disposition' => 'attachment; filename="report.xml"',
    ]);
}
```

Add route:
```php
Route::get('/report/export/xml', [ReportController::class, 'exportXml'])->name('report.export.xml');
```

---

## 🐛 Common Issues & Solutions

### Issue 1: Migration Error
**Error**: "SQLSTATE[HY000]: General error"

**Solution**:
```bash
# Rollback migration
php artisan migrate:rollback

# Clear migrations
php artisan migrate:reset

# Run fresh
php artisan migrate:fresh
```

---

### Issue 2: Routes Not Found
**Error**: "RouteNotFoundException"

**Solution**:
```bash
# Clear route cache
php artisan route:clear

# Verify routes
php artisan route:list
```

---

### Issue 3: Filter Not Saving
**Error**: "Filter tidak muncul di list"

**Solution**:
1. Check database `report_filters` table:
   ```sql
   SELECT * FROM report_filters WHERE user_id = {current_user_id};
   ```
2. Verify user is logged in
3. Check browser console for CSRF token error
4. Verify `X-CSRF-TOKEN` header in request

---

### Issue 4: Export Not Working
**Error**: "Download tidak jalan atau file kosong"

**Solution**:
1. Check file permissions
2. Verify temporary directory exists: `storage/app/temp`
3. Check `php.ini` settings for memory limit
4. Verify data exists before export

---

## 🧪 Testing

### Test Report Data Customer

```javascript
// Open browser console (F12)

// Test 1: Get summary
fetch('/report/customer/summary')
  .then(r => r.json())
  .then(data => console.log(data));

// Test 2: Get customer data with filter
fetch('/report/customer/data?draw=1&start=0&length=10&min_bayar=500000')
  .then(r => r.json())
  .then(data => console.log(data));

// Test 3: Save filter
fetch('/report/filter/save', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('[name="csrf-token"]').getAttribute('content')
  },
  body: JSON.stringify({
    name: 'Test Filter',
    type: 'customer',
    filters: { packet: '100 Mbps' }
  })
})
.then(r => r.json())
.then(data => console.log(data));
```

---

## 📈 Performance Tips

### Optimization for Large Dataset

1. **Add Indexing** (optional):
```sql
ALTER TABLE customers ADD INDEX idx_status (status);
ALTER TABLE customers ADD INDEX idx_packet (packet);
ALTER TABLE tickets ADD INDEX idx_teknisi (pic_teknisi);
ALTER TABLE tickets ADD INDEX idx_customer (cid);
```

2. **Use Database Query Caching**:
```php
$data = Cache::remember('report_summary', 3600, function() {
    return Customer::getSummary();
});
```

3. **Limit Export Size**:
```php
// Di controller
const MAX_EXPORT_ROWS = 5000;

if ($query->count() > self::MAX_EXPORT_ROWS) {
    return response()->json(['error' => 'Data terlalu besar'], 422);
}
```

---

## 📝 API Documentation

### Endpoint: GET /report/customer/data

**Query Parameters:**
```
draw: int (required) - DataTable draw counter
start: int - Pagination start (default: 0)
length: int - Pagination length (default: 10)
search[value]: string - Search value
packet: string - Filter by packet
min_bayar: int - Min payment
max_bayar: int - Max payment
status: string - Filter by status
sales: string - Filter by sales
```

**Response:**
```json
{
  "draw": 1,
  "recordsTotal": 100,
  "recordsFiltered": 25,
  "data": [
    {
      "cid": "CUST001",
      "nama": "Customer Name",
      "email": "email@example.com",
      "alamat": "Address",
      "packet": "100 Mbps",
      "pembayaran_perbulan": "Rp. 500.000",
      "status": "active",
      "sales": "Sales Name",
      "pic_it": "IT Person",
      "tgl_customer_aktif": "2024-01-19"
    }
  ]
}
```

---

## 🔐 Security Checklist

- [x] CSRF protection on POST/DELETE
- [x] Authorization policy on filter delete
- [x] User-scoped filter queries
- [x] Input validation on all filters
- [x] SQL injection prevention (using parameterized queries)
- [x] XSS protection (using blade escaping)

---

## 📚 Additional Resources

- **Laravel Documentation**: https://laravel.com/docs
- **DataTables Documentation**: https://datatables.net/
- **Bootstrap Documentation**: https://getbootstrap.com/
- **jQuery Documentation**: https://jquery.com/

---

## 👤 Support & Contact

For issues or questions about the Report feature:

1. Check `REPORT_DOCUMENTATION.md` for detailed feature guide
2. Check logs: `storage/logs/laravel.log`
3. Review test cases in `/tests`

---

**Last Updated**: 19 January 2025  
**Version**: 1.0  
**Status**: ✅ Production Ready
