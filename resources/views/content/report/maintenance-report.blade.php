<div class="mt-4">
  <!-- Summary Cards -->
  <div class="row mb-4" id="maintenance-summary">
    <div class="col-md-3">
      <div class="card bg-light">
        <div class="card-body">
          <h6 class="card-title">Total Ticket</h6>
          <h3 id="total-tickets">-</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-light">
        <div class="card-body">
          <h6 class="card-title">Selesai</h6>
          <h3 id="completed-tickets">-</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-light">
        <div class="card-body">
          <h6 class="card-title">Pending</h6>
          <h3 id="pending-tickets">-</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-light">
        <div class="card-body">
          <h6 class="card-title">Top Teknisi</h6>
          <p id="top-teknisi" class="mb-0"><small>-</small></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Technician Chart -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h6 class="mb-0">Grafik Kunjungan Teknisi</h6>
          <div class="d-flex align-items-center gap-2">
            <input type="date" class="form-control form-control-sm" id="chart-date-from" style="width: 150px;">
            <span>s/d</span>
            <input type="date" class="form-control form-control-sm" id="chart-date-to" style="width: 150px;">
            <button type="button" class="btn btn-sm btn-primary" id="btn-apply-chart-filter" title="Apply Filter">
              <i class="bx bx-filter-alt"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-reset-chart" title="Reset Chart">
              <i class="bx bx-refresh"></i>
            </button>
          </div>
        </div>
        <div class="card-body">
          <div style="height: 300px;">
            <canvas id="teknisiVisitChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Top Visited Customers -->
  <div class="row mb-4">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0">Top Pelanggan Dikunjungi</h6>
        </div>
        <div class="card-body">
          <div id="top-customers-list">
            <small class="text-muted">Loading...</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0">Kunjungan Per Teknisi</h6>
        </div>
        <div class="card-body">
          <div id="visits-teknisi-list">
            <small class="text-muted">Loading...</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters Section -->
  <div class="card mb-4">
    <div class="card-header">
      <h6 class="mb-0">Filter & Pencarian</h6>
    </div>
    <div class="card-body">
      <form id="filter-maintenance-form" class="row g-3">
        <div class="col-md-3">
          <label for="filter-teknisi" class="form-label">Teknisi</label>
          <select class="form-select select2" id="filter-teknisi">
            <option value="">-- Pilih Teknisi --</option>
            @foreach($teknisi as $t)
              <option value="{{ $t->name }}">{{ $t->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter-maintenance-cust-name" class="form-label">Nama Customer</label>
          <select class="form-select select2" id="filter-maintenance-cust-name">
            <option value="">-- Pilih Customer --</option>
            @foreach($customers as $c)
              <option value="{{ $c->nama }}">{{ $c->nama }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter-maintenance-date-from" class="form-label">Tanggal Dari</label>
          <input type="date" class="form-control" id="filter-maintenance-date-from">
        </div>
        <div class="col-md-3">
          <label for="filter-maintenance-date-to" class="form-label">Tanggal Sampai</label>
          <input type="date" class="form-control" id="filter-maintenance-date-to">
        </div>
        <div class="col-md-3">
          <label for="filter-maintenance-status" class="form-label">Status</label>
          <select class="form-select" id="filter-maintenance-status">
            <option value="">-- Semua --</option>
            <option value="on progress">On Progress</option>
            <option value="pending">Pending</option>
            <option value="selesai">Selesai</option>
            <option value="need visit">Perlu Kunjungan</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter-maintenance-sales" class="form-label">Sales</label>
          <select class="form-select select2" id="filter-maintenance-sales">
            <option value="">-- Semua Sales --</option>
            @foreach($sales as $s)
              <option value="{{ $s->name }}">{{ $s->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label>&nbsp;</label>
          <button type="button" class="btn btn-primary w-100" id="btn-filter-maintenance">
            <i class='bx bx-search'></i> Filter
          </button>
        </div>
        <div class="col-md-3">
          <label>&nbsp;</label>
          <button type="button" class="btn btn-secondary w-100" id="btn-reset-maintenance">
            <i class='bx bx-reset'></i> Reset
          </button>
        </div>
      </form>

      <!-- Save Filter Section -->
      <div class="mt-3">
        <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="collapse" data-bs-target="#save-filter-maintenance-section">
          <i class='bx bx-save'></i> Simpan Filter
        </button>
      </div>

      <div class="collapse mt-2" id="save-filter-maintenance-section">
        <div class="card card-body">
          <div class="row g-2">
            <div class="col-md-6">
              <input type="text" class="form-control" id="filter-maintenance-name" placeholder="Nama Filter (e.g., Teknisi Dwiki)">
            </div>
            <div class="col-md-3">
              <button type="button" class="btn btn-success w-100" id="btn-save-filter-maintenance">
                <i class='bx bx-save'></i> Simpan
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Saved Filters -->
      <div class="mt-3" id="saved-filters-maintenance-container">
        <label class="form-label">Saved Filters:</label>
        <div id="saved-filters-maintenance-list" class="d-flex flex-wrap gap-2">
          <!-- Saved filters will be loaded here -->
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal untuk menampilkan data maintenance -->
<div class="modal fade" id="maintenanceDataModal" tabindex="-1" aria-labelledby="maintenanceDataModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="maintenanceDataModalLabel">Data Maintenance</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Tombol Export -->
        <div class="mb-3 d-flex gap-2">
          <button type="button" class="btn btn-success" id="btn-open-export-excel-maintenance">
            <i class='bx bx-download'></i> Export Excel
          </button>
          <button type="button" class="btn btn-danger" id="btn-open-export-pdf-maintenance">
            <i class='bx bx-file-pdf'></i> Export PDF
          </button>
        </div>

        <!-- Tabel Data -->
        <div class="table-responsive text-nowrap">
          <table class="table table-hover" id="table-maintenance-report">
            <thead>
              <tr>
                <th style="width: 20px;"><input type="checkbox" class="form-check-input" id="check-all-maintenance"></th>
                <th>Ticket NO</th>
                <th>Customer ID</th>
                <th>Nama Customer</th>
                <th>Jenis</th>
                <th>Teknisi</th>
                <th>Tanggal Kunjungan</th>
                <th>Kendala</th>
                <th>Hasil</th>
                <th>MTTR Response</th>
                <th>MTTR Resolve</th>
                <th>Downtime</th>
                <th>Status</th>
                <th>Priority</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal untuk memilih kolom yang akan di-export untuk Maintenance -->
<div class="modal fade" id="exportMaintenanceColumnModal" tabindex="-1" aria-labelledby="exportMaintenanceColumnModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exportMaintenanceColumnModalLabel">Pilih Kolom untuk Export</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3">Pilih kolom yang ingin di-export:</p>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="ticket_no" id="col-ticket-no-maint" checked>
          <label class="form-check-label" for="col-ticket-no-maint">Ticket NO</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="cid" id="col-cid-maint" checked>
          <label class="form-check-label" for="col-cid-maint">Customer ID</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="customer_nama" id="col-customer-nama-maint" checked>
          <label class="form-check-label" for="col-customer-nama-maint">Nama Customer</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="jenis" id="col-jenis-maint" checked>
          <label class="form-check-label" for="col-jenis-maint">Jenis</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="pic_teknisi" id="col-teknisi-maint" checked>
          <label class="form-check-label" for="col-teknisi-maint">Teknisi</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="tanggal_kunjungan" id="col-tgl-kunjungan-maint" checked>
          <label class="form-check-label" for="col-tgl-kunjungan-maint">Tanggal Kunjungan</label>
        </div>
        <!-- RFO Fields Group -->
        <div class="form-check mb-2">
           <input class="form-check-input" type="checkbox" value="rfo_data" id="col-rfo-data-maint" checked>
           <label class="form-check-label" for="col-rfo-data-maint">Data RFO (Problem, Root Cause, Action)</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="sla_remote_minutes" id="col-mttr-response-maint" checked>
          <label class="form-check-label" for="col-mttr-response-maint">MTTR Response</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="sla_onsite_minutes" id="col-mttr-resolve-maint" checked>
          <label class="form-check-label" for="col-mttr-resolve-maint">MTTR Resolve</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="sla_total_minutes" id="col-downtime-maint" checked>
          <label class="form-check-label" for="col-downtime-maint">Downtime</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="status" id="col-status-maint" checked>
          <label class="form-check-label" for="col-status-maint">Status</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="priority" id="col-priority-maint" checked>
          <label class="form-check-label" for="col-priority-maint">Priority</label>
        </div>
        <hr>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-sm btn-secondary" id="btn-select-all-columns-maintenance">
            <i class='bx bx-check-square'></i> Pilih Semua
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-deselect-all-columns-maintenance">
            <i class='bx bx-square'></i> Hapus Semua
          </button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success" id="btn-confirm-export-excel-maintenance">
          <i class='bx bx-download'></i> Export Excel
        </button>
        <button type="button" class="btn btn-danger" id="btn-confirm-export-pdf-maintenance">
          <i class='bx bx-file-pdf'></i> Export PDF
        </button>
      </div>
    </div>
  </div>
</div>
