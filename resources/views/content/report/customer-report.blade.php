<div class="mt-4">
  <!-- Summary Cards -->
  <div class="row mb-4" id="customer-summary">
    <div class="col-md-3">
      <div class="card bg-light">
        <div class="card-body">
          <h6 class="card-title">Total Customer</h6>
          <h3 id="total-customers">-</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-light">
        <div class="card-body">
          <h6 class="card-title">Customer Aktif</h6>
          <h3 id="active-customers">-</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-light">
        <div class="card-body">
          <h6 class="card-title">Total Revenue</h6>
          <h3 id="total-revenue">-</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-light">
        <div class="card-body">
          <h6 class="card-title">Customer Tidak Aktif</h6>
          <h3 id="inactive-customers"></h3>
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
      <form id="filter-customer-form" class="row g-3">
        <div class="col-md-3">
          <label for="filter-packet" class="form-label">PACKET</label>
          <select class="form-select" id="filter-packet">
            <option value="">-- Semua --</option>
            <option value="25 Mbps">25 Mbps</option>
            <option value="50 Mbps">50 Mbps</option>
            <option value="75 Mbps">75 Mbps</option>
            <option value="100 Mbps">100 Mbps</option>
            <option value="150 Mbps">150 Mbps</option>
            <option value="Dedicated">Dedicated</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter-min-bayar" class="form-label">MIN PEMBAYARAN</label>
          <input type="text" class="form-control" id="filter-min-bayar" placeholder="Rp.">
        </div>
        <div class="col-md-3">
          <label for="filter-max-bayar" class="form-label">MAX PEMBAYARAN</label>
          <input type="text" class="form-control" id="filter-max-bayar" placeholder="Rp.">
        </div>
        <div class="col-md-3">
          <label for="filter-status" class="form-label">STATUS</label>
          <select class="form-select" id="filter-status">
            <option value="" selected>-- Semua --</option>
            <option value="Aktif">Aktif</option>
            <option value="Isolir">Isolir</option>
            <option value="Terminate">Terminate</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter-sales" class="form-label">SALES</label>
          <select class="form-select" id="filter-sales">
            <option value="">-- Semua --</option>
            <!-- Will be populated via AJAX -->
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter-pop" class="form-label">POP</label>
          <select class="form-select" id="filter-pop">
            <option value="">-- Semua --</option>
            <!-- Will be populated via AJAX -->
          </select>
        </div>
        <div class="col-md-3">
          <label for="filter-min-setup-fee" class="form-label">MIN SETUP FEE</label>
          <input type="text" class="form-control currency-input" id="filter-min-setup-fee" placeholder="Rp.">
        </div>
        <div class="col-md-3">
          <label for="filter-max-setup-fee" class="form-label">MAX SETUP FEE</label>
          <input type="text" class="form-control currency-input" id="filter-max-setup-fee" placeholder="Rp.">
        </div>
        <div class="col-md-3">
          <label for="filter-tgl-aktif-from" class="form-label">TANGGAL AKTIF (Dari)</label>
          <input type="date" class="form-control" id="filter-tgl-aktif-from">
        </div>
        <div class="col-md-3">
          <label for="filter-tgl-aktif-to" class="form-label">TANGGAL AKTIF (Sampai)</label>
          <input type="date" class="form-control" id="filter-tgl-aktif-to">
        </div>
        <div class="col-md-3">
          <label for="filter-billing-aktif-from" class="form-label">BILLING AKTIF (Dari)</label>
          <input type="date" class="form-control" id="filter-billing-aktif-from">
        </div>
        <div class="col-md-3">
          <label for="filter-billing-aktif-to" class="form-label">BILLING AKTIF (Sampai)</label>
          <input type="date" class="form-control" id="filter-billing-aktif-to">
        </div>
        <div class="col-md-3">
          <label>&nbsp;</label>
          <button type="button" class="btn btn-primary w-100" id="btn-filter-customer">
            <i class='bx bx-search'></i> Filter
          </button>
        </div>
        <div class="col-md-3">
          <label>&nbsp;</label>
          <button type="button" class="btn btn-secondary w-100" id="btn-reset-customer">
            <i class='bx bx-reset'></i> Reset
          </button>
        </div>
      </form>

      <!-- Save Filter Section -->
      <div class="mt-3">
        <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="collapse" data-bs-target="#save-filter-section">
          <i class='bx bx-save'></i> Simpan Filter
        </button>
      </div>

      <div class="collapse mt-2" id="save-filter-section">
        <div class="card card-body">
          <div class="row g-2">
            <div class="col-md-6">
              <input type="text" class="form-control" id="filter-name" placeholder="Nama Filter (e.g., Pelanggan 500rb)">
            </div>
            <div class="col-md-3">
              <button type="button" class="btn btn-success w-100" id="btn-save-filter">
                <i class='bx bx-save'></i> Simpan
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Saved Filters -->
      <div class="mt-3" id="saved-filters-container">
        <label class="form-label">Saved Filters:</label>
        <div id="saved-filters-list" class="d-flex flex-wrap gap-2">
          <!-- Saved filters will be loaded here -->
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Modal untuk menampilkan data pelanggan -->
<div class="modal fade" id="customerDataModal" tabindex="-1" aria-labelledby="customerDataModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="customerDataModalLabel">Data Pelanggan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Tombol Export -->
        <div class="mb-3 d-flex gap-2">
          <button type="button" class="btn btn-success" id="btn-open-export-excel">
            <i class='bx bx-download'></i> Export Excel
          </button>
          <button type="button" class="btn btn-danger" id="btn-open-export-pdf">
            <i class='bx bx-file-pdf'></i> Export PDF
          </button>
        </div>

        <!-- Tabel Data -->
        <div class="table-responsive text-nowrap">
          <table class="table table-hover" id="table-customer-report">
            <thead>
              <tr>
                <th style="width: 20px;"><input type="checkbox" class="form-check-input" id="check-all-customer"></th>
                <th>ID</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Alamat</th>
                <th>Koordinat</th>
                <th>Packet</th>
                <th>Pembayaran/Bulan</th>
                <th>POP</th>
                <th>Setup Fee</th>
                <th>Status</th>
                <th>Sales</th>
                <th>PIC IT</th>
                <th>No IT</th>
                <th>PIC Finance</th>
                <th>No Finance</th>
                <th>Tgl Aktif</th>
                <th>Billing Aktif</th>
                <th>Note</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal untuk memilih kolom yang akan di-export -->
<div class="modal fade" id="exportColumnModal" tabindex="-1" aria-labelledby="exportColumnModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exportColumnModalLabel">Pilih Kolom untuk Export</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3">Pilih kolom yang ingin di-export:</p>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="cid" id="col-cid" checked>
          <label class="form-check-label" for="col-cid">ID Customer</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="nama" id="col-nama" checked>
          <label class="form-check-label" for="col-nama">Nama</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="email" id="col-email" checked>
          <label class="form-check-label" for="col-email">Email</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="alamat" id="col-alamat" checked>
          <label class="form-check-label" for="col-alamat">Alamat</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="coordinate_maps" id="col-koordinat">
          <label class="form-check-label" for="col-koordinat">Koordinat</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="packet" id="col-packet" checked>
          <label class="form-check-label" for="col-packet">Packet</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="pembayaran_perbulan" id="col-pembayaran" checked>
          <label class="form-check-label" for="col-pembayaran">Pembayaran/Bulan</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="status" id="col-status" checked>
          <label class="form-check-label" for="col-status">Status</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="sales" id="col-sales" checked>
          <label class="form-check-label" for="col-sales">Sales</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="pop" id="col-pop" checked>
          <label class="form-check-label" for="col-pop">POP</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="setup_fee" id="col-setup-fee" checked>
          <label class="form-check-label" for="col-setup-fee">Setup Fee</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="pic_it" id="col-pic-it" checked>
          <label class="form-check-label" for="col-pic-it">PIC IT</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="no_it" id="col-no-it">
          <label class="form-check-label" for="col-no-it">No IT</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="pic_finance" id="col-pic-finance">
          <label class="form-check-label" for="col-pic-finance">PIC Finance</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="no_finance" id="col-no-finance">
          <label class="form-check-label" for="col-no-finance">No Finance</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="tgl_customer_aktif" id="col-tgl-aktif" checked>
          <label class="form-check-label" for="col-tgl-aktif">Tanggal Aktif</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="billing_aktif" id="col-billing-aktif">
          <label class="form-check-label" for="col-billing-aktif">Billing Aktif</label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" value="note" id="col-note">
          <label class="form-check-label" for="col-note">Note</label>
        </div>
        <hr>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-sm btn-secondary" id="btn-select-all-columns">
            <i class='bx bx-check-square'></i> Pilih Semua
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-deselect-all-columns">
            <i class='bx bx-square'></i> Hapus Semua
          </button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success" id="btn-confirm-export-excel">
          <i class='bx bx-download'></i> Export Excel
        </button>
        <button type="button" class="btn btn-danger" id="btn-confirm-export-pdf">
          <i class='bx bx-file-pdf'></i> Export PDF
        </button>
      </div>
    </div>
  </div>
</div>
