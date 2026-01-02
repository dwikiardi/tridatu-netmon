@extends('layouts/contentNavbarLayout')

@section('title', 'Data Ticketing')

@section('content')

<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Data Ticketing /</span> Table Ticket
</h4>

<!-- Table Ticket -->
<div class="card p-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5>Data Ticket</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddTicket" data-action="add" onclick="$('#modalAddTicket form').trigger('reset'); $('#modalTitle').text('Add Ticket');">Add Ticket</button>
  </div>

  <!-- Filter Section -->
  <div class="card-body border-bottom">
    <div class="row g-3">
      <div class="col-md-2">
        <label for="filterStatus" class="form-label">Status</label>
        <select class="form-select" id="filterStatus">
          <option value="">Semua Status</option>
          <option value="open">Open</option>
          <option value="need visit">Need Visit</option>
          <option value="on progress">On Progress</option>
          <option value="pending">Pending</option>
          <option value="selesai">Selesai</option>
        </select>
      </div>
      <div class="col-md-2">
        <label for="filterPriority" class="form-label">Priority</label>
        <select class="form-select" id="filterPriority">
          <option value="">Semua Priority</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="urgent">Urgent</option>
        </select>
      </div>
      <div class="col-md-2">
        <label for="filterJenis" class="form-label">Jenis</label>
        <select class="form-select" id="filterJenis">
          <option value="">Semua Jenis</option>
          <option value="maintenance">Maintenance</option>
          <option value="komplain">Komplain</option>
          <option value="survey">Survey</option>
          <option value="installasi">Installasi</option>
        </select>
      </div>
      <div class="col-md-2">
        <label for="filterMetode" class="form-label">Metode</label>
        <select class="form-select" id="filterMetode">
          <option value="">Semua Metode</option>
          <option value="onsite">Onsite</option>
          <option value="remote">Remote</option>
        </select>
      </div>
      <div class="col-md-4 d-flex align-items-end gap-2">
        <button class="btn btn-sm btn-success" id="btnApplyFilter">Apply Filter</button>
        <button class="btn btn-sm btn-outline-secondary" id="btnClearFilter">Clear Filter</button>
        <small class="text-muted d-block w-100">Filter disimpan otomatis</small>
      </div>
    </div>
  </div>

  <div class="table-responsive text-nowrap">
    <table class="table" id="tableTicket">
      <thead>
        <tr>
          <th>Ticket No</th>
          <th>CID</th>
          <th>Nama Customer</th>
          <th>Jenis</th>
          <th>Metode</th>
          <th>Priority</th>
          <th>Tanggal Kunjungan</th>
          <th>Hari</th>
          <th>Kendala</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Detail Ticket -->
<div class="modal fade" id="modalDetailTicket" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Ticket</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <hr class="my-0">
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <p><strong>CID:</strong> <span id="detail_cid"></span></p>
            <p><strong>Customer:</strong> <span id="detail_nama_customer"></span></p>
            <p id="detail_telp_row" style="display: none;"><strong>Telepon:</strong> <span id="detail_telp_customer"></span></p>
            <p id="detail_alamat_row" style="display: none;"><strong>Alamat:</strong> <span id="detail_alamat_customer"></span></p>
            <p id="detail_koordinat_row" style="display: none;"><strong>Koordinat:</strong> <span id="detail_koordinat"></span></p>
            <p><strong>Jenis Ticket:</strong> <span id="detail_jenis"></span></p>
            <p><strong>Metode Penanganan:</strong> <span id="detail_metode"></span></p>
            <p><strong>Priority:</strong> <span id="detail_priority"></span></p>
            <p><strong>Tanggal Kunjungan:</strong> <span id="detail_tanggal"></span></p>
            <p><strong>Jam:</strong> <span id="detail_jam"></span></p>
            <p><strong>Hari:</strong> <span id="detail_hari"></span></p>
          </div>
          <div class="col-md-6">
            <p><strong>PIC IT Lokasi:</strong> <span id="detail_pic_it"></span></p>
            <p><strong>No IT Lokasi:</strong> <span id="detail_no_it"></span></p>
            <p><strong>PIC Teknisi:</strong> <span id="detail_pic_teknisi" class="badge bg-info"></span></p>
            <p><strong>Status:</strong> <span id="detail_status"></span></p>
          </div>
        </div>
        <hr>
        <div class="row">
          <div class="col-12">
            <p><strong>Kendala:</strong></p>
            <p id="detail_kendala" class="text-muted"></p>
          </div>
        </div>
        <hr>
        <div class="row">
          <div class="col-12">
            <p><strong>Solusi:</strong></p>
            <p id="detail_solusi" class="text-muted"></p>
          </div>
        </div>
        <hr>
        <div class="row">
          <div class="col-12">
            <p><strong>Hasil:</strong></p>
            <p id="detail_hasil" class="text-muted"></p>
          </div>
        </div>
        <hr>
        <div class="row" id="detail_sales_row" style="display: none;">
          <div class="col-12">
            <p><strong>Sales:</strong> <span id="detail_sales_name"></span></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Add / Edit Ticket -->
<div class="modal fade" id="modalAddTicket" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Ticket</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <hr class="my-0">
      <div class="modal-body">
        <form id="formTicket">
          <input type="hidden" id="ticket_id" name="id">

          <!-- Jenis Ticket -->
          <div class="mb-3">
            <label for="jenis" class="form-label">Jenis Ticket <span class="text-danger">*</span></label>
            <select class="form-control" id="jenis" name="jenis" required>
              <option value="">Pilih Jenis</option>
              <option value="maintenance-komplain">Maintenance / Komplain</option>
              <option value="survey">Survey</option>
              <option value="installasi">Installasi</option>
            </select>
          </div>

          <!-- ========== MAINTENANCE / KOMPLAIN ========== -->
          <div id="maintenanceKomplainFields">
            <div class="mb-3">
              <label for="cid" class="form-label">Customer (CID) <span class="text-danger">*</span></label>
              <select class="form-control" id="cid" name="cid">
                <option value="">Pilih Customer</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="kendala" class="form-label">Kendala / Problem <span class="text-danger">*</span></label>
              <textarea class="form-control" id="kendala" name="kendala" rows="3" placeholder="Jelaskan masalah yang dialami customer..."></textarea>
            </div>
          </div>

          <!-- ========== SURVEY ========== -->
          <div id="surveyFields" style="display:none;">
            <!-- Tipe Pelanggan Survey -->
            <div class="mb-3">
              <label class="form-label">Tipe Survey <span class="text-danger">*</span></label>
              <div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="survey_tipe" id="survey_tipe_baru" value="baru">
                  <label class="form-check-label" for="survey_tipe_baru">
                    Pelanggan Baru
                  </label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="survey_tipe" id="survey_tipe_existing" value="existing">
                  <label class="form-check-label" for="survey_tipe_existing">
                    Pelanggan Existing (Penambahan)
                  </label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="survey_tipe" id="survey_tipe_project" value="project">
                  <label class="form-check-label" for="survey_tipe_project">
                    Project
                  </label>
                </div>
              </div>
            </div>

            <!-- Form untuk Pelanggan Baru -->
            <div id="survey_baru_fields">
              <div class="mb-3">
                <label for="survey_nama_pelanggan" class="form-label">Nama Pelanggan <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="survey_nama_pelanggan" name="survey_nama" placeholder="Nama pelanggan baru">
              </div>

              <div class="mb-3">
                <label for="survey_telepon" class="form-label">Telepon</label>
                <input type="text" class="form-control" id="survey_telepon" name="survey_telepon" placeholder="Nomor telepon pelanggan">
              </div>

              <div class="mb-3">
                <label for="survey_alamat" class="form-label">Alamat <span class="text-danger">*</span></label>
                <textarea class="form-control" id="survey_alamat" name="survey_alamat" rows="2" placeholder="Alamat lengkap pelanggan"></textarea>
              </div>

              <div class="mb-3">
                <label for="survey_koordinat" class="form-label">Koordinat <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="survey_koordinat" name="survey_koordinat" placeholder="Contoh: -6.1234, 106.1234 atau link maps">
              </div>

              <div class="mb-3">
                <label for="survey_pic_it" class="form-label">PIC di Lokasi (Contact Person) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="survey_pic_it" name="survey_pic_it" placeholder="Nama contact person di lokasi customer">
                <small class="text-muted">Nama orang yang bisa dihubungi di lokasi customer</small>
              </div>

              <div class="mb-3">
                <label for="survey_sales_id" class="form-label">Calon Customer Punya <span class="text-danger">*</span></label>
                <select class="form-control" id="survey_sales_id" name="survey_sales_id">
                  <option value="">Pilih Sales</option>
                  @foreach($users->where('jabatan', 'sales') as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <!-- Form untuk Pelanggan Existing -->
            <div id="survey_existing_fields" style="display:none;">
              <div class="mb-3">
                <label for="survey_customer_id" class="form-label">Pilih Customer <span class="text-danger">*</span></label>
                <select class="form-control" id="survey_customer_id" name="survey_customer_id">
                  <option value="">Pilih Customer</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label">Jenis Survey</label>
                <select class="form-control" id="survey_jenis_existing" name="survey_jenis_existing">
                  <option value="penambahan_ap">Penambahan Access Point</option>
                  <option value="penambahan_bandwidth">Upgrade Bandwidth</option>
                  <option value="survey_project">Survey Project</option>
                  <option value="lainnya">Lainnya</option>
                </select>
              </div>
            </div>

            <!-- Form untuk Project -->
            <div id="survey_project_fields" style="display:none;">
              <div class="mb-3">
                <label for="survey_project_nama" class="form-label">Nama Project <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="survey_project_nama" name="survey_project_nama" placeholder="Nama project">
              </div>

              <div class="mb-3">
                <label for="survey_project_telepon" class="form-label">Telepon</label>
                <input type="text" class="form-control" id="survey_project_telepon" name="survey_project_telepon" placeholder="Nomor telepon project">
              </div>

              <div class="mb-3">
                <label for="survey_project_alamat" class="form-label">Lokasi Project <span class="text-danger">*</span></label>
                <textarea class="form-control" id="survey_project_alamat" name="survey_project_alamat" rows="2" placeholder="Alamat lengkap lokasi project"></textarea>
              </div>

              <div class="mb-3">
                <label for="survey_project_koordinat" class="form-label">Koordinat <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="survey_project_koordinat" name="survey_project_koordinat" placeholder="Contoh: -6.1234, 106.1234 atau link maps">
              </div>

              <div class="mb-3">
                <label for="survey_project_pic" class="form-label">PIC di Lokasi (Contact Person) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="survey_project_pic" name="survey_project_pic" placeholder="Nama contact person di lokasi project">
                <small class="text-muted">Nama orang yang bisa dihubungi di lokasi project</small>
              </div>

              <div class="mb-3">
                <label for="survey_project_sales" class="form-label">Calon Customer Punya <span class="text-danger">*</span></label>
                <select class="form-control" id="survey_project_sales" name="survey_project_sales_id">
                  <option value="">Pilih Sales</option>
                  @foreach($users->where('jabatan', 'sales') as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="mb-3">
              <label for="survey_deskripsi" class="form-label">Deskripsi Survey <span class="text-danger">*</span></label>
              <textarea class="form-control" id="survey_deskripsi" name="survey_deskripsi" rows="3" placeholder="Jelaskan tujuan, kebutuhan, dan hasil survey..."></textarea>
            </div>
          </div>

          <!-- ========== INSTALLASI ========== -->
          <div id="instalasiFields" style="display:none;">
            <!-- Tipe Installasi -->
            <div class="mb-3">
              <label class="form-label">Tipe Installasi <span class="text-danger">*</span></label>
              <div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="install_tipe" id="install_tipe_calon" value="calon" checked>
                  <label class="form-check-label" for="install_tipe_calon">
                    Pelanggan Baru (dari Survey/Calon Customer)
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="install_tipe" id="install_tipe_penambahan" value="penambahan">
                  <label class="form-check-label" for="install_tipe_penambahan">
                    Penambahan Alat (Pelanggan Existing)
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="install_tipe" id="install_tipe_terminate" value="terminate">
                  <label class="form-check-label" for="install_tipe_terminate">
                    Re-aktivasi (Customer Terminate)
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="install_tipe" id="install_tipe_project" value="project">
                  <label class="form-check-label" for="install_tipe_project">
                    Installasi Project
                  </label>
                </div>
              </div>
            </div>

            <!-- Form untuk Calon Customer (Pelanggan Baru) -->
            <div id="install_calon_fields">
              <div class="mb-3">
                <label for="install_calon_customer" class="form-label">Calon Customer (dari Survey) <span class="text-danger">*</span></label>
                <select class="form-control" id="install_calon_customer" name="install_calon_customer_id">
                  <option value="">Pilih Calon Customer</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="install_nama_pelanggan" class="form-label">Nama Pelanggan <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="install_nama_pelanggan" name="install_nama" placeholder="Nama pelanggan" readonly>
              </div>

              <div class="mb-3">
                <label for="install_alamat" class="form-label">Alamat <span class="text-danger">*</span></label>
                <textarea class="form-control" id="install_alamat" name="install_alamat" rows="2" placeholder="Alamat pelanggan" readonly></textarea>
              </div>

              <div class="mb-3">
                <label for="install_koordinat" class="form-label">Koordinat <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="install_koordinat" name="install_koordinat" placeholder="Koordinat lokasi" readonly>
              </div>

              <div class="mb-3">
                <label for="install_pop" class="form-label">POP <span class="text-danger">*</span></label>
                <select class="form-control" id="install_pop" name="install_pop">
                  <option value="">Pilih atau ketik POP baru</option>
                </select>
              </div>
            </div>

            <!-- Form untuk Penambahan Alat (Pelanggan Existing) -->
            <div id="install_penambahan_fields" style="display:none;">
              <div class="mb-3">
                <label for="install_customer_penambahan" class="form-label">Pilih Customer Existing (dari Survey) <span class="text-danger">*</span></label>
                <select class="form-control" id="install_customer_penambahan" name="install_customer_penambahan_id">
                  <option value="">Pilih Customer yang sudah di-survey</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="install_penambahan_nama" class="form-label">Nama Customer</label>
                <input type="text" class="form-control" id="install_penambahan_nama" readonly>
              </div>

              <div class="mb-3">
                <label for="install_penambahan_alamat" class="form-label">Alamat</label>
                <textarea class="form-control" id="install_penambahan_alamat" rows="2" readonly></textarea>
              </div>

              <div class="mb-3">
                <label for="install_penambahan_koordinat" class="form-label">Koordinat</label>
                <input type="text" class="form-control" id="install_penambahan_koordinat" readonly>
              </div>
            </div>

            <!-- Form untuk Customer Terminate -->
            <div id="install_terminate_fields" style="display:none;">
              <div class="mb-3">
                <label for="install_customer_terminate" class="form-label">Customer Terminate <span class="text-danger">*</span></label>
                <select class="form-control" id="install_customer_terminate" name="install_customer_id">
                  <option value="">Pilih Customer yang Terminate</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="install_terminate_nama" class="form-label">Nama Customer</label>
                <input type="text" class="form-control" id="install_terminate_nama" readonly>
              </div>

              <div class="mb-3">
                <label for="install_terminate_alamat" class="form-label">Alamat</label>
                <textarea class="form-control" id="install_terminate_alamat" rows="2" readonly></textarea>
              </div>

              <div class="mb-3">
                <label for="install_terminate_pop" class="form-label">POP <span class="text-danger">*</span></label>
                <select class="form-control" id="install_terminate_pop" name="install_terminate_pop">
                  <option value="">Pilih atau ketik POP baru</option>
                </select>
              </div>
            </div>

            <!-- Form untuk Project -->
            <div id="install_project_fields" style="display:none;">
              <div class="mb-3">
                <label for="install_survey_project" class="form-label">Pilih Survey Project <span class="text-danger">*</span></label>
                <select class="form-control" id="install_survey_project" name="install_survey_project_id">
                  <option value="">Pilih dari Survey Project sebelumnya</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="install_project_nama" class="form-label">Nama Project <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="install_project_nama" name="install_project_nama" placeholder="Nama project installasi">
              </div>

              <div class="mb-3">
                <label for="install_project_lokasi" class="form-label">Lokasi Project <span class="text-danger">*</span></label>
                <textarea class="form-control" id="install_project_lokasi" name="install_project_lokasi" rows="2" placeholder="Alamat lokasi project"></textarea>
              </div>

              <div class="mb-3">
                <label for="install_project_koordinat" class="form-label">Koordinat</label>
                <input type="text" class="form-control" id="install_project_koordinat" name="install_project_koordinat" placeholder="Koordinat lokasi project">
              </div>

              <div class="mb-3">
                <label for="install_project_pic" class="form-label">PIC / Contact Person</label>
                <input type="text" class="form-control" id="install_project_pic" name="install_project_pic" placeholder="Contact person di lokasi project">
              </div>
            </div>

            <div class="mb-3">
              <label for="install_deskripsi" class="form-label">Deskripsi Installasi <span class="text-danger">*</span></label>
              <textarea class="form-control" id="install_deskripsi" name="install_deskripsi" rows="3" placeholder="Jelaskan detail perangkat dan kebutuhan installasi..."></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="btnSave">Save</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('page-style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
  .select2-container--default .select2-selection--single {
    height: 38px;
    padding: 6px 12px;
    border: 1px solid #d9dee3;
    border-radius: 0.375rem;
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 24px;
    padding-left: 0;
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
  }
</style>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
  let currentAction = 'add';
  let customersData = [];

  // Handle Jenis Ticket change - show/hide fields dynamically
  $('#jenis').on('change', function() {
    const jenis = $(this).val();

    // Hide all sections first
    $('#maintenanceKomplainFields').hide();
    $('#surveyFields').hide();
    $('#instalasiFields').hide();

    if (jenis === 'maintenance-komplain') {
      $('#maintenanceKomplainFields').show();
      $('#cid').prop('required', true);
      $('#kendala').prop('required', true);
    }
    else if (jenis === 'survey') {
      $('#surveyFields').show();
      // Pastikan user memilih tipe survey
      $('input[name="survey_tipe"]').prop('checked', false);
      $('#survey_baru_fields').hide();
      $('#survey_existing_fields').hide();
      $('#survey_project_fields').hide();
      $('#survey_calon_customer').prop('required', true);
      $('#survey_deskripsi').prop('required', true);
    }
    else if (jenis === 'installasi') {
      $('#instalasiFields').show();
      $('#install_calon_customer').prop('required', true);
      $('#install_deskripsi').prop('required', true);
    }
  });

  // Load customer list for Maintenance/Komplain dan Survey existing
  $.ajax({
    url: '{{ url("ticketing/customers") }}',
    type: 'GET',
    success: function(data) {
      customersData = data;
      data.forEach(function(customer) {
        $('#cid').append(
          $('<option></option>')
            .val(customer.cid)
            .text(customer.cid + ' - ' + customer.nama)
            .data('pic_it', customer.pic_it)
            .data('no_it', customer.no_it)
        );

        // Add to survey existing customer dropdown
        $('#survey_customer_id').append(
          $('<option></option>')
            .val(customer.cid)
            .text(customer.cid + ' - ' + customer.nama)
        );

        // Add to installasi terminate dropdown HANYA customer yang STATUS = terminate
        // Asumsi ada field 'status' di customer, atau gunakan custom logic
        if (customer.status === 'terminate' || customer.status === 'Terminate') {
          $('#install_customer_terminate').append(
            $('<option></option>')
              .val(customer.cid)
              .text(customer.cid + ' - ' + customer.nama)
              .data('nama', customer.nama)
              .data('alamat', customer.alamat)
          );
        }
      });

      // Initialize Select2 after options are loaded
      $('#cid').select2({
        placeholder: 'Cari CID atau Nama Customer',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#modalAddTicket')
      });

      $('#survey_customer_id').select2({
        placeholder: 'Pilih Customer Existing',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#modalAddTicket')
      });

      $('#install_customer_terminate').select2({
        placeholder: 'Pilih Customer yang Terminate',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#modalAddTicket')
      });
    }
  });

  // Load survey projects for Installasi Project
  function loadSurveyProjects() {
    $.ajax({
      url: '{{ url("tickets/survey-projects") }}',
      type: 'GET',
      success: function(data) {
        // Clear and rebuild options
        const $select = $('#install_survey_project');
        $select.find('option:not(:first)').remove(); // Keep placeholder, remove others

        data.forEach(function(ticket) {
          $('<option></option>')
            .val(ticket.id)
            .text(ticket.nama + ' - #' + ticket.id + ' - ' + ticket.kendala)
            .data('nama', ticket.nama)
            .data('lokasi', ticket.lokasi)
            .data('koordinat', ticket.koordinat)
            .data('pic', ticket.pic)
            .data('deskripsi', ticket.deskripsi_update)
            .appendTo($select);
        });

        // Trigger change to update select2
        $select.trigger('change');
      }
    });
  }

  // Load data on page load
  loadSurveyProjects();

  // Reload data every time modal is opened
  $('#modalAddTicket').on('show.bs.modal', function() {
    loadSurveyProjects();
  });

  // Initialize Select2 for survey project (only once)
  $('#install_survey_project').select2({
    placeholder: 'Pilih dari Survey Project',
    allowClear: true,
    width: '100%',
    dropdownParent: $('#modalAddTicket')
  });

  // Load calon customer list for Installasi only
  function loadCalonCustomers() {
    $.ajax({
      url: '{{ url("calon-customers") }}',
      type: 'GET',
      success: function(data) {
        // Clear and rebuild options
        const $select = $('#install_calon_customer');
        $select.find('option:not(:first)').remove(); // Keep placeholder, remove others

        data.forEach(function(customer) {
          $('<option></option>')
            .val(customer.id)
            .text(customer.nama)
            .data('nama', customer.nama)
            .data('alamat', customer.alamat)
            .data('koordinat', customer.koordinat)
            .data('deskripsi', customer.deskripsi_update)
            .appendTo($select);
        });

        // Trigger change to update select2
        $select.trigger('change');
      }
    });
  }

  // Load data on page load
  loadCalonCustomers();

  // Reload data every time modal is opened
  $('#modalAddTicket').on('show.bs.modal', function() {
    loadCalonCustomers();
  });

  // Initialize Select2 for calon (only once)
  $('#install_calon_customer').select2({
    placeholder: 'Pilih Calon Customer dari Survey sebelumnya',
    allowClear: true,
    width: '100%',
    dropdownParent: $('#modalAddTicket')
  });

  // Load POPs for installation forms
  function loadInstallPops() {
    $.ajax({
      url: '{{ route("get-pops") }}',
      type: 'GET',
      success: function(data) {
        // Clear and rebuild options for both POP selects
        const $installPop = $('#install_pop');
        const $terminatePop = $('#install_terminate_pop');
        
        $installPop.find('option:not(:first)').remove();
        $terminatePop.find('option:not(:first)').remove();
        
        data.forEach(function(pop) {
          $installPop.append($('<option></option>').val(pop).text(pop));
          $terminatePop.append($('<option></option>').val(pop).text(pop));
        });
      }
    });
  }

  // Load POPs on page load
  loadInstallPops();

  // Reload POPs when modal is opened
  $('#modalAddTicket').on('show.bs.modal', function() {
    loadInstallPops();
  });

  // Initialize Select2 for POP fields with tagging
  $('#install_pop').select2({
    tags: true,
    placeholder: 'Pilih atau ketik POP baru',
    allowClear: true,
    width: '100%',
    dropdownParent: $('#modalAddTicket')
  });

  $('#install_terminate_pop').select2({
    tags: true,
    placeholder: 'Pilih atau ketik POP baru',
    allowClear: true,
    width: '100%',
    dropdownParent: $('#modalAddTicket')
  });

  // Handle Installasi calon customer selection - auto-fill data
  $('#install_calon_customer').on('change', function() {
    const selectedOption = $(this).find('option:selected');
    $('#install_nama_pelanggan').val(selectedOption.data('nama') || '');
    $('#install_alamat').val(selectedOption.data('alamat') || '');
    $('#install_koordinat').val(selectedOption.data('koordinat') || '');
    $('#install_deskripsi').val(selectedOption.data('deskripsi') || '');
  });

  // Load existing customers with survey for Penambahan Alat
  function loadExistingCustomers() {
    $.ajax({
      url: '{{ url("existing-customers-with-survey") }}',
      type: 'GET',
      success: function(data) {
        // Clear and rebuild options
        const $select = $('#install_customer_penambahan');
        $select.find('option:not(:first)').remove(); // Keep placeholder, remove others

        data.forEach(function(customer) {
          $('<option></option>')
            .val(customer.cid)
            .text(customer.display_name) // Format: Nama - Ticket ID - Hasil Survey
            .data('nama', customer.nama)
            .data('alamat', customer.alamat)
            .data('koordinat', customer.coordinate_maps)
            .data('deskripsi', customer.deskripsi_update)
            .appendTo($select);
        });

        // Trigger change to update select2
        $select.trigger('change');
      }
    });
  }

  // Load data on page load
  loadExistingCustomers();

  // Reload data every time modal is opened (to ensure fresh data)
  $('#modalAddTicket').on('show.bs.modal', function() {
    loadExistingCustomers();
  });

  // Initialize Select2 for penambahan (only once)
  $('#install_customer_penambahan').select2({
    placeholder: 'Pilih Customer yang sudah di-survey',
    allowClear: true,
    width: '100%',
    dropdownParent: $('#modalAddTicket')
  });

  // Handle Installasi penambahan customer selection - auto-fill data
  $('#install_customer_penambahan').on('change', function() {
    const selectedOption = $(this).find('option:selected');
    $('#install_penambahan_nama').val(selectedOption.data('nama') || '');
    $('#install_penambahan_alamat').val(selectedOption.data('alamat') || '');
    $('#install_penambahan_koordinat').val(selectedOption.data('koordinat') || '');
    $('#install_deskripsi').val(selectedOption.data('deskripsi') || '');
  });

  // Handle Installasi terminate customer selection - auto-fill data
  $('#install_customer_terminate').on('change', function() {
    const selectedOption = $(this).find('option:selected');
    $('#install_terminate_nama').val(selectedOption.data('nama') || '');
    $('#install_terminate_alamat').val(selectedOption.data('alamat') || '');
  });

  // Handle Survey tipe change
  $('input[name="survey_tipe"]').on('change', function() {
    const tipe = $(this).val();
    $('#survey_baru_fields').hide();
    $('#survey_existing_fields').hide();
    $('#survey_project_fields').hide();

    if (tipe === 'baru') {
      $('#survey_baru_fields').show();
      // Clear other fields
      $('#survey_customer_id').val('').trigger('change');
      $('#survey_jenis_existing').val('');
      $('#survey_project_nama').val('');
      $('#survey_project_telepon').val('');
      $('#survey_project_alamat').val('');
      $('#survey_project_koordinat').val('');
      $('#survey_project_pic').val('');
      $('#survey_project_sales').val('').trigger('change');
    } else if (tipe === 'existing') {
      $('#survey_existing_fields').show();
      // Clear other fields
      $('#survey_nama_pelanggan').val('');
      $('#survey_telepon').val('');
      $('#survey_alamat').val('');
      $('#survey_koordinat').val('');
      $('#survey_pic_it').val('');
      $('#survey_sales_id').val('').trigger('change');
      $('#survey_project_nama').val('');
      $('#survey_project_telepon').val('');
      $('#survey_project_alamat').val('');
      $('#survey_project_koordinat').val('');
      $('#survey_project_pic').val('');
      $('#survey_project_sales').val('').trigger('change');
    } else if (tipe === 'project') {
      $('#survey_project_fields').show();
      // Clear other fields
      $('#survey_nama_pelanggan').val('');
      $('#survey_telepon').val('');
      $('#survey_alamat').val('');
      $('#survey_koordinat').val('');
      $('#survey_pic_it').val('');
      $('#survey_sales_id').val('').trigger('change');
      $('#survey_customer_id').val('').trigger('change');
      $('#survey_jenis_existing').val('');
    }
  });

  // Handle Installasi tipe change
  $('input[name="install_tipe"]').on('change', function() {
    const tipe = $(this).val();
    $('#install_calon_fields').hide();
    $('#install_penambahan_fields').hide();
    $('#install_terminate_fields').hide();
    $('#install_project_fields').hide();

    // Clear all fields
    $('#install_calon_customer').val('').trigger('change');
    $('#install_nama_pelanggan').val('');
    $('#install_alamat').val('');
    $('#install_koordinat').val('');
    $('#install_customer_penambahan').val('').trigger('change');
    $('#install_penambahan_nama').val('');
    $('#install_penambahan_alamat').val('');
    $('#install_penambahan_koordinat').val('');
    $('#install_customer_terminate').val('').trigger('change');
    $('#install_terminate_nama').val('');
    $('#install_terminate_alamat').val('');
    $('#install_survey_project').val('').trigger('change');
    $('#install_project_nama').val('');
    $('#install_project_lokasi').val('');
    $('#install_project_koordinat').val('');
    $('#install_project_pic').val('');

    if (tipe === 'calon') {
      $('#install_calon_fields').show();
    } else if (tipe === 'penambahan') {
      $('#install_penambahan_fields').show();
    } else if (tipe === 'terminate') {
      $('#install_terminate_fields').show();
    } else if (tipe === 'project') {
      $('#install_project_fields').show();
    }
  });

  // Handle Survey Project selection - auto-fill data
  $('#install_survey_project').on('change', function() {
    const selectedOption = $(this).find('option:selected');
    $('#install_project_nama').val(selectedOption.data('nama') || '');
    $('#install_project_lokasi').val(selectedOption.data('lokasi') || '');
    $('#install_project_koordinat').val(selectedOption.data('koordinat') || '');
    $('#install_project_pic').val(selectedOption.data('pic') || '');
    $('#install_deskripsi').val(selectedOption.data('deskripsi') || '');
  });

  // DataTable
  var table = $('#tableTicket').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: '{{ url("ticketing/data") }}',
      type: 'GET',
      data: function(d) {
        d.status = $('#filterStatus').val();
        d.priority = $('#filterPriority').val();
        d.jenis = $('#filterJenis').val();
        d.metode = $('#filterMetode').val();
        return d;
      }
    },
    columns: [
      {
        data: 'ticket_no',
        name: 'ticket_no',
        render: function(data, type, row) {
          return `<a href="/ticketing/${row.id}" class="text-primary fw-bold">${data}</a>`;
        }
      },
      { data: 'cid', name: 'cid' },
      { data: 'nama_customer', name: 'nama_customer' },
      {
        data: 'jenis',
        name: 'jenis',
        render: function(data) {
          if (!data) return '<span class="badge bg-secondary">-</span>';
          const badges = {
            'maintenance': 'bg-primary',
            'komplain': 'bg-danger',
            'survey': 'bg-info',
            'installasi': 'bg-success'
          };
          return '<span class="badge ' + (badges[data] || 'bg-secondary') + '">' + data.toUpperCase() + '</span>';
        }
      },
      {
        data: 'metode_penanganan',
        name: 'metode_penanganan',
        render: function(data) {
          if (!data) return '<span class="badge bg-secondary">-</span>';
          const badge = data === 'remote' ? 'bg-dark' : 'bg-primary';
          return '<span class="badge ' + badge + '">' + data.toUpperCase() + '</span>';
        }
      },
      {
        data: 'priority',
        name: 'priority',
        render: function(data) {
          if (!data) return '<span class="badge bg-secondary">-</span>';
          const badges = {
            'low': 'bg-info',
            'medium': 'bg-warning',
            'high': 'bg-danger',
            'urgent': 'bg-dark'
          };
          return '<span class="badge ' + (badges[data] || 'bg-secondary') + '">' + data.toUpperCase() + '</span>';
        }
      },
      { data: 'tanggal_kunjungan', name: 'tanggal_kunjungan' },
      { data: 'hari', name: 'hari' },
      {
        data: 'kendala',
        name: 'kendala',
        render: function(data) {
          if (!data) return '-';
          if (data.length > 50) {
            return data.substring(0, 50) + '...';
          }
          return data;
        }
      },
      {
        data: 'status',
        name: 'status',
        render: function(data) {
          const badges = {
            'open': 'bg-secondary',
            'need visit': 'bg-warning',
            'on progress': 'bg-primary',
            'pending': 'bg-warning',
            'selesai': 'bg-success'
          };
          return '<span class="badge ' + (badges[data] || 'bg-secondary') + '">' + (data ? data.toUpperCase() : '-') + '</span>';
        }
      },
      {
        data: 'id',
        orderable: false,
        searchable: false,
        render: function(data, type, row) {
          return `
            <button class="btn btn-sm btn-warning btn-edit" data-id="${data}" title="Edit">
              <i class="bx bx-edit"></i>
            </button>
            <button class="btn btn-sm btn-danger btn-delete" data-id="${data}" title="Delete">
              <i class="bx bx-trash"></i>
            </button>
          `;
        }
      }
    ],
    order: [[0, 'desc']],
    pageLength: 10
  });

  // Show detail
  $('#tableTicket').on('click', '.btn-detail', function() {
    const id = $(this).data('id');
    $.ajax({
      url: '{{ url("ticketing/detail") }}',
      type: 'GET',
      data: { id: id },
      success: function(data) {
        $('#detail_cid').text(data.cid || '-');
        $('#detail_nama_customer').text(data.nama_customer);

        if (data.jenis === 'survey' && data.calon_customer_id) {
          $('#detail_telp_row').hide();
          $('#detail_alamat_row').show();
          $('#detail_alamat_customer').text(data.alamat_customer || '-');
          if (data.koordinat) {
            $('#detail_koordinat_row').show();
            $('#detail_koordinat').html('<a href="' + data.koordinat + '" target="_blank">' + data.koordinat + '</a>');
          } else {
            $('#detail_koordinat_row').hide();
          }
        } else {
          $('#detail_telp_row').hide();
          $('#detail_alamat_row').show();
          $('#detail_alamat_customer').text(data.alamat_customer || '-');
          if (data.koordinat) {
            $('#detail_koordinat_row').show();
            $('#detail_koordinat').html('<a href="' + data.koordinat + '" target="_blank">' + data.koordinat + '</a>');
          } else {
            $('#detail_koordinat_row').hide();
          }
        }

        $('#detail_jenis').text((data.jenis || '-').toUpperCase());
        $('#detail_metode').text((data.metode_penanganan || 'onsite').toUpperCase());
        $('#detail_priority').html('<span class="badge bg-' +
          (data.priority === 'urgent' ? 'dark' : data.priority === 'high' ? 'danger' : data.priority === 'medium' ? 'warning' : 'info') +
          '">' + data.priority.toUpperCase() + '</span>');
        $('#detail_tanggal').text(data.tanggal_kunjungan || '-');
        $('#detail_jam').text(data.jam || '-');
        $('#detail_hari').text(data.hari || '-');
        $('#detail_pic_it').text(data.pic_it_lokasi);
        $('#detail_no_it').text(data.no_it_lokasi || '-');
        $('#detail_pic_teknisi').text(data.pic_teknisi || '-');
        $('#detail_status').html('<span class="badge bg-' +
          (data.status === 'selesai' ? 'success' : data.status === 'on progress' ? 'primary' : data.status === 'pending' ? 'warning' : 'secondary') +
          '">' + data.status.toUpperCase() + '</span>');
        $('#detail_kendala').text(data.kendala || '-');
        $('#detail_solusi').text(data.solusi || '-');
        $('#detail_hasil').text(data.hasil || '-');

        if (data.jenis === 'survey' && data.sales_name && data.sales_name !== '-') {
          $('#detail_sales_row').show();
          $('#detail_sales_name').text(data.sales_name);
        } else {
          $('#detail_sales_row').hide();
        }

        $('#modalDetailTicket').modal('show');
      }
    });
  });

  // Edit button
  $('#tableTicket').on('click', '.btn-edit', function() {
    currentAction = 'edit';
    const id = $(this).data('id');
    $('#modalTitle').text('Edit Ticket');

    $.ajax({
      url: '{{ url("ticketing/detail") }}',
      type: 'GET',
      data: { id: id },
      success: function(data) {
        $('#ticket_id').val(data.id);
        $('#cid').val(data.cid).trigger('change');
        $('#kendala').val(data.kendala);
        $('#modalAddTicket').modal('show');
      }
    });
  });

  // Save button
  $('#btnSave').click(function() {
    // Validasi sederhana di frontend
    const jenisVal = $('#jenis').val();
    if (jenisVal === 'survey') {
      const surveyTipeVal = $('input[name="survey_tipe"]:checked').val();
      if (!surveyTipeVal) {
        alert('Pilih Tipe Survey terlebih dahulu');
        return;
      }
    }

    const formData = $('#formTicket').serialize();
    const url = currentAction === 'add' ? '{{ url("ticketing/store") }}' : '{{ url("ticketing/update") }}';
    const method = currentAction === 'add' ? 'POST' : 'PUT';

    $.ajax({
      url: url,
      type: method,
      data: formData,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        $('#modalAddTicket').modal('hide');
        $('#formTicket')[0].reset();
        $('#cid').val(null).trigger('change');
        table.ajax.reload();
        alert(response.message);
      },
      error: function(xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message)
          ? xhr.responseJSON.message
          : (function(){
              if (xhr.responseJSON && xhr.responseJSON.errors) {
                var firstKey = Object.keys(xhr.responseJSON.errors)[0];
                if (firstKey) {
                  return xhr.responseJSON.errors[firstKey][0];
                }
              }
              return 'Terjadi kesalahan saat menyimpan ticket';
            })();
        alert('Error: ' + msg);
      }
    });
  });

  // Delete button
  $('#tableTicket').on('click', '.btn-delete', function() {
    const id = $(this).data('id');
    if (confirm('Apakah Anda yakin ingin menghapus ticket ini?')) {
      $.ajax({
        url: '{{ url("ticketing/delete") }}',
        type: 'DELETE',
        data: { id: id },
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          table.ajax.reload();
          alert(response.message);
        }
      });
    }
  });

  // Reset form when modal is hidden
  $('#modalAddTicket').on('hidden.bs.modal', function() {
    currentAction = 'add';
    $('#formTicket')[0].reset();
    $('#cid').val(null).trigger('change');
    $('#install_calon_customer').val(null).trigger('change');
    $('#ticket_id').val('');
    $('#modalTitle').text('Add Ticket');

    // Reset form display to default
    $('#jenis').val('').trigger('change');
    $('#maintenanceKomplainFields').hide();
    $('#surveyFields').hide();
    $('#instalasiFields').hide();
    $('#install_noteField').hide(); // Reset note field for installasi
  });

  // Load saved filters from localStorage
  function loadFilters() {
    const savedFilters = localStorage.getItem('ticketFilters');
    if (savedFilters) {
      const filters = JSON.parse(savedFilters);
      $('#filterStatus').val(filters.status || '');
      $('#filterPriority').val(filters.priority || '');
      $('#filterJenis').val(filters.jenis || '');
      $('#filterMetode').val(filters.metode || '');
    }
  }

  // Save filters to localStorage
  function saveFilters() {
    const filters = {
      status: $('#filterStatus').val(),
      priority: $('#filterPriority').val(),
      jenis: $('#filterJenis').val(),
      metode: $('#filterMetode').val()
    };
    localStorage.setItem('ticketFilters', JSON.stringify(filters));
  }

  // Apply filter button
  $('#btnApplyFilter').click(function() {
    saveFilters();
    table.ajax.reload();
  });

  // Clear filter button
  $('#btnClearFilter').click(function() {
    $('#filterStatus').val('');
    $('#filterPriority').val('');
    $('#filterJenis').val('');
    $('#filterMetode').val('');
    localStorage.removeItem('ticketFilters');
    table.ajax.reload();
  });

  // Load filters on page load
  loadFilters();
  table.ajax.reload();
});
</script>
@endsection
