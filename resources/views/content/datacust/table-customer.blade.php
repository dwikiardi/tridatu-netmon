@extends('layouts/contentNavbarLayout')

@section('title', 'Data Customer')

@section('page-style')
<meta name="csrf-token" content="{{ csrf_token() }}">
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
  .select2-container {
    z-index: 9999 !important; /* Extremely high to be above any modal */
  }
  .select2-dropdown {
    z-index: 9999 !important;
  }

  /* Sticky Columns - CID & Nama */
  .sticky-col-1 {
    position: sticky !important;
    left: 0;
    z-index: 2;
    background-color: #fff !important;
    box-shadow: 2px 0 4px rgba(0,0,0,0.08);
  }
  .sticky-col-2 {
    position: sticky !important;
    left: 100px; /* Sesuai lebar baru kolom CID */
    z-index: 2;
    background-color: #fff !important;
    box-shadow: 2px 0 4px rgba(0,0,0,0.08);
  }

  /* Perbaikan agar border tidak menghilang dan teks tidak terpotong */
  #tableCustomer {
    border-collapse: separate !important;
    border-spacing: 0;
  }
  #tableCustomer th, #tableCustomer td {
    border-bottom: 1px solid #dee2e6 !important;
    padding-right: 25px !important; /* Ruang untuk icon sorting */
  }

  /* Header Sticky Column (Corner) */
  .dataTables_scrollHead th.sticky-col-1,
  .dataTables_scrollHead th.sticky-col-2 {
    z-index: 5 !important;
    background-color: #f8f9fa !important;
  }

  /* Sticky thead (DataTables scrollX pakai .dataTables_scrollHead) */
  #tableCustomer_wrapper .dataTables_scrollHead {
    position: sticky;
    top: 0;
    z-index: 4;
    background: #fff;
  }
  /* Pastikan row hover tidak menghilangkan background sticky */
  #tableCustomer tbody tr:hover td.sticky-col-1,
  #tableCustomer tbody tr:hover td.sticky-col-2 {
    background-color: #f1f1f1;
  }

  /* Floating / Sticky Horizontal Scrollbar */
  #floatingScrollbarWrap {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 1050;
    background: #fff;
    border-top: 1px solid #dee2e6;
    padding: 4px 0;
    display: none; /* tampil saat tabel visible */
  }
  #floatingScrollbarInner {
    overflow-x: auto;
    overflow-y: hidden;
    height: 14px;
  }
  #floatingScrollbarContent {
    height: 1px;
  }
</style>
@endsection

@section('content')

<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Data Customer /</span> Table Customer
</h4>

<!-- Table Customer -->
<div class="card p-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5>Data Customer</h5>
    <div class="d-flex gap-2">
      <!-- Column Visibility Dropdown -->
      <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="columnVisibilityBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
          <i class="bx bx-columns"></i> Kolom
        </button>
        <ul class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="columnVisibilityBtn" style="min-width: 200px;">
          <li class="mb-2"><strong>Tampilkan Kolom:</strong></li>
          <li><hr class="dropdown-divider my-1"></li>
          <li class="form-check"><input class="form-check-input col-toggle" type="checkbox" value="0" id="colCid" checked><label class="form-check-label" for="colCid">CID</label></li>
          <li class="form-check"><input class="form-check-input col-toggle" type="checkbox" value="1" id="colNama" checked><label class="form-check-label" for="colNama">Nama</label></li>
          <li class="form-check"><input class="form-check-input col-toggle" type="checkbox" value="2" id="colSales" checked><label class="form-check-label" for="colSales">Sales</label></li>
          <li class="form-check"><input class="form-check-input col-toggle" type="checkbox" value="3" id="colPop" checked><label class="form-check-label" for="colPop">POP</label></li>
          <li class="form-check"><input class="form-check-input col-toggle" type="checkbox" value="4" id="colPacket" checked><label class="form-check-label" for="colPacket">Packet</label></li>
          <li class="form-check"><input class="form-check-input col-toggle" type="checkbox" value="5" id="colAlamat" checked><label class="form-check-label" for="colAlamat">Alamat</label></li>
          <li class="form-check"><input class="form-check-input col-toggle" type="checkbox" value="6" id="colPembayaran" checked><label class="form-check-label" for="colPembayaran">Pembayaran</label></li>
          <li class="form-check"><input class="form-check-input col-toggle" type="checkbox" value="7" id="colSetupFee" checked><label class="form-check-label" for="colSetupFee">Setup Fee</label></li>
          <li class="form-check"><input class="form-check-input col-toggle" type="checkbox" value="8" id="colTglAktif" checked><label class="form-check-label" for="colTglAktif">Tgl Aktif</label></li>
          <li class="form-check"><input class="form-check-input col-toggle" type="checkbox" value="9" id="colBilling" checked><label class="form-check-label" for="colBilling">Billing Aktif</label></li>
          <li class="form-check"><input class="form-check-input col-toggle" type="checkbox" value="10" id="colStatus" checked><label class="form-check-label" for="colStatus">Status</label></li>
          <li><hr class="dropdown-divider my-1"></li>
          <li><button class="btn btn-sm btn-primary w-100" id="btnResetColumns">Reset Default</button></li>
        </ul>
      </div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddCust" data-action="add" onclick="$('#modalAddCust form').trigger('reset');">Add</button>
    </div>
  </div>
  <div class="table-responsive text-nowrap">
    <table class="table" id="tableCustomer">
      <thead>
        <tr>
          <th class="sticky-col-1">CID</th>
          <th class="sticky-col-2">Nama</th>
          <th>Sales</th>
          <th>POP</th>
          <th>Packet</th>
          <th>Alamat</th>
          <th>Pembayaran Perbulan</th>
          <th>Setup Fee</th>
          <th>Tgl Customer Aktif</th>
          <th>Billing Aktif</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
      </tbody>
    </table>
  </div>
</div>

<!-- Floating Horizontal Scrollbar -->
<div id="floatingScrollbarWrap">
  <div id="floatingScrollbarInner">
    <div id="floatingScrollbarContent"></div>
  </div>
</div>

<!-- Modal Detail Cust -->
<div class="modal fade" id="modalDetailCust" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Customer & Asset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <hr class="my-0">
      <div class="modal-body">
        <div class="row">
          <!-- Kolom Kiri: Profil Customer -->
          <div class="col-lg-7 border-end">
            <h6 class="fw-bold mb-3"><i class="bx bx-user me-1"></i> Customer Profile</h6>
            <div id="customerProfileContent">
              <!-- Detail table will be injected here -->
            </div>
          </div>
          
          <!-- Kolom Kanan: Asset Management Integration -->
          <div class="col-lg-5">
            <h6 class="fw-bold mb-3"><i class="bx bx-package me-1"></i> Asset Summary</h6>
            <div id="assetSummaryContent">
              <div class="text-center py-5">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                <span class="ms-2">Loading assets...</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Edit / Add Cust -->
<div class="modal fade" id="modalAddCust" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <!-- Garis pembatas -->
      <hr class="my-0">
      <div class="modal-body">
        <form>
          <div class="row">
            <!-- Left Column -->
            <div class="col-md-6">
              <div class="mb-3">
                <label for="cid" class="form-label">CID</label>
                <input type="text" class="form-control" id="cid" name="cid" placeholder="Auto-generated">
              </div>
              <div class="mb-3">
                <label for="nama" class="form-label">Nama</label>
                <input type="text" class="form-control" id="nama" name="nama">
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email">
              </div>
              <div class="mb-3">
                <label for="sales" class="form-label">Sales</label>
                <select class="form-control" id="sales" name="sales">
                  <option value="">Pilih Sales</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="pop" class="form-label">POP</label>
                <select class="form-control" id="pop" name="pop">
                  <option value="">Pilih POP</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="packet" class="form-label">Packet</label>
                <select class="form-control" id="packet" name="packet">
                  <option value="">Pilih Packet</option>
                  <option value="25 Mbps">25 Mbps</option>
                  <option value="50 Mbps">50 Mbps</option>
                  <option value="75 Mbps">75 Mbps</option>
                  <option value="100 Mbps">100 Mbps</option>
                  <option value="150 Mbps">150 Mbps</option>
                  <option value="dedicated">Dedicated</option>
                </select>
              </div>
              <div class="mb-3" id="noteField" style="display: none;">
                <label for="note" class="form-label">Note</label>
                <textarea class="form-control" id="note" name="note"></textarea>
              </div>
              <div class="mb-3">
                <label for="alamat" class="form-label">Alamat</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="2"></textarea>
              </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-6">
              <div class="mb-3">
                <label for="pic_it" class="form-label">PIC IT</label>
                <input type="text" class="form-control" id="pic_it" name="pic_it">
              </div>
              <div class="mb-3">
                <label for="no_it" class="form-label">No IT</label>
                <input type="text" class="form-control" id="no_it" name="no_it">
              </div>
              <div class="mb-3">
                <label for="pic_finance" class="form-label">PIC Finance</label>
                <input type="text" class="form-control" id="pic_finance" name="pic_finance">
              </div>
              <div class="mb-3">
                <label for="no_finance" class="form-label">No Finance</label>
                <input type="text" class="form-control" id="no_finance" name="no_finance">
              </div>
              <div class="mb-3">
                <label for="coordinate_maps" class="form-label">Coordinate Maps</label>
                <input type="text" class="form-control" id="coordinate_maps" name="coordinate_maps">
              </div>
              <div class="mb-3">
                <label for="pembayaran_perbulan" class="form-label">Pembayaran Perbulan</label>
                <input type="text" class="form-control" id="pembayaran_perbulan" name="pembayaran_perbulan" placeholder="Rp.">
              </div>
              <div class="mb-3">
                <label for="setup_fee" class="form-label">Setup Fee</label>
                <input type="text" class="form-control" id="setup_fee" name="setup_fee" placeholder="Rp.">
              </div>
              <div class="mb-3">
                <label for="tgl_customer_aktif" class="form-label">Tgl Customer Aktif</label>
                <input type="date" class="form-control" id="tgl_customer_aktif" name="tgl_customer_aktif">
              </div>
              <div class="mb-3">
                <label for="billing_aktif" class="form-label">Billing Aktif</label>
                <input type="date" class="form-control" id="billing_aktif" name="billing_aktif">
              </div>
              <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-control" id="status" name="status">
                  <option value="Aktif">Aktif</option>
                  <option value="Isolir">Isolir</option>
                  <option value="Terminate">Terminate</option>
                </select>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="btnSaveCust">Save</button>
      </div>
    </div>
  </div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
  let currentAction = 'add';
  
  // Setup CSRF token for all AJAX requests
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  console.log('Customer Table Script Loaded - Version Fix_Cols_v1');
  
  // Currency formatting function
  function formatRupiah(angka, prefix = 'Rp. ') {
    if (angka === undefined || angka === null || angka === '') {
        return '';
    }
    
    // Only keep digits
    let number_string = angka.toString().replace(/[^0-9]/g, '');
    
    if (!number_string) return '';
    
    let sisa = number_string.length % 3;
    let rupiah = number_string.substr(0, sisa);
    let ribuan = number_string.substr(sisa).match(/\d{3}/gi);
    
    if (ribuan) {
      let separator = sisa ? '.' : '';
      rupiah += separator + ribuan.join('.');
    }
    
    return prefix + rupiah;
  }
  
  // Parse rupiah to number
  function parseRupiah(rupiah) {
    return parseInt(rupiah.replace(/[^0-9]/g, '')) || 0;
  }
  
  // Format currency inputs on keyup
  $('#pembayaran_perbulan, #setup_fee').on('keyup', function() {
    let value = $(this).val();
    let formatted = formatRupiah(value);
    $(this).val(formatted);
  });
  
  // Load POPs for dropdown
  function loadPops(selectedVal = null) {
    $.ajax({
      url: '{{ route("get-pops") }}',
      type: 'GET',
      success: function(data) {
        let $popSelect = $('#pop');
        
        // Destroy existing Select2 if it exists
        if ($popSelect.hasClass('select2-hidden-accessible')) {
          $popSelect.select2('destroy');
        }
        
        // Clear and rebuild options
        $popSelect.find('option:not(:first)').remove();
        
        data.forEach(function(pop) {
          $popSelect.append($('<option></option>').val(pop).text(pop));
        });
        
        // If we have a selected value that isn't in the list, add it (tagging)
        if (selectedVal && !data.includes(selectedVal)) {
          $popSelect.append($('<option></option>').val(selectedVal).text(selectedVal));
        }

        // Initialize Select2 with tags
        $popSelect.select2({
          tags: true,
          placeholder: 'Pilih atau ketik POP baru',
          allowClear: true,
          dropdownParent: $('#modalAddCust .modal-content'),
          width: '100%'
        });

        // Fix for dropdown position when modal is scrolled
        $popSelect.on('select2:open', function() {
            let container = $('.select2-container--open').last();
            container.css('opacity', '0');
            setTimeout(function() {
                container.css('opacity', '1');
            }, 0);
        });

        // Set value if provided
        if (selectedVal) {
          $popSelect.val(selectedVal).trigger('change');
        }
      }
    });
  }
  
  // Load sales for dropdown
  $.ajax({
    url: '{{ url("user/data") }}',
    type: 'GET',
    data: { length: 1000 },
    success: function(response) {
      // Clear existing options except the first one
      $('#sales').find('option:not(:first)').remove();
      
      if (response.data) {
        response.data.forEach(function(user) {
          if (user.jabatan === 'sales') {
            $('#sales').append($('<option></option>').val(user.name).text(user.name));
          }
        });
      }
    }
  });
  
  // Load POPs on page load and when modal opens
  loadPops();
  
  // Reload POPs when modal is shown
  $('#modalAddCust').on('shown.bs.modal', function() {
    // Small delay to ensure modal is fully rendered
    setTimeout(function() {
      // Pass existing value if we're in edit mode
      let existingPop = $('#pop').val();
      loadPops(existingPop);
    }, 100);
  });
  
  // DataTable initialization
  let table;
  
  // Always destroy existing table to ensure our configuration is applied
  if ($.fn.DataTable.isDataTable('#tableCustomer')) {
    $('#tableCustomer').DataTable().destroy();
  }
  
  table = $('#tableCustomer').DataTable({
      processing: true,
      serverSide: true,
      scrollX: true,
      autoWidth: false,
      ajax: {
        url: '{{ route("show-customer") }}',
        type: 'GET'
      },
      columns: [
        { data: 'cid', name: 'cid', width: '100px', className: 'sticky-col-1' },
        { data: 'nama', name: 'nama', width: '200px', className: 'sticky-col-2' },
        { data: 'sales', name: 'sales', width: '120px' },
        { data: 'pop', name: 'pop', defaultContent: '-', width: '100px' },
        { data: 'packet', name: 'packet', width: '100px' },
        { 
          data: 'alamat', 
          name: 'alamat', 
          width: '200px',
          render: function(data) {
            if (data && data.length > 30) {
              return '<span title="' + data + '">' + data.substring(0, 30) + '...</span>';
            }
            return data || '-';
          }
        },
        { data: 'pembayaran_perbulan_formatted', name: 'pembayaran_perbulan', width: '130px' },
        { data: 'setup_fee_formatted', name: 'setup_fee', defaultContent: '-', width: '120px' },
        { data: 'tgl_customer_aktif', name: 'tgl_customer_aktif', width: '120px' },
        { data: 'billing_aktif', name: 'billing_aktif', width: '120px' },
        { 
          data: 'status', 
          name: 'status',
          width: '80px',
          render: function(data) {
            let badgeClass = data === 'Aktif' ? 'bg-success' : (data === 'Isolir' ? 'bg-warning' : 'bg-danger');
            return '<span class="badge ' + badgeClass + '">' + data + '</span>';
          }
        },
        {
          data: null,
          orderable: false,
          searchable: false,
          width: '200px',
          render: function(data, type, row) {
            return `
              <button class="btn btn-sm btn-info btn-detail" data-cid="${row.cid}">Detail</button>
              <button class="btn btn-sm btn-warning btn-edit" data-cid="${row.cid}">Edit</button>
              <button class="btn btn-sm btn-danger btn-delete" data-cid="${row.cid}">Delete</button>
            `;
          }
        }
      ],
      order: [[0, 'asc']]
    });

  // ── Floating Horizontal Scrollbar ──────────────────────────────
  // Buat scrollbar yang sticky di bawah viewport agar user tidak
  // perlu scroll ke bawah tabel hanya untuk geser ke samping.
  (function initFloatingScrollbar() {
    // DataTables wraps table in .dataTables_scrollBody
    // tapi karena kita pakai scrollX tanpa scrollY, ambil .dataTables_scroll > .dataTables_scrollBody
    // Lebih aman: target .table-responsive yang bungkus #tableCustomer
    const $tableWrapper = $('#tableCustomer').closest('.dataTables_scrollBody, .table-responsive');
    const $floatWrap    = $('#floatingScrollbarWrap');
    const $floatInner   = $('#floatingScrollbarInner');
    const $floatContent = $('#floatingScrollbarContent');

    function getRealScrollEl() {
      // DataTables dengan scrollX membuat .dataTables_scrollBody
      return $('#tableCustomer').closest('.dataTables_scrollBody').length
        ? $('#tableCustomer').closest('.dataTables_scrollBody')
        : $('#tableCustomer').closest('.table-responsive');
    }

    function syncFloatingScrollbar() {
      const $scrollEl = getRealScrollEl();
      const scrollWidth = $scrollEl[0] ? $scrollEl[0].scrollWidth : 0;
      const clientWidth = $scrollEl[0] ? $scrollEl[0].clientWidth : 0;

      if (scrollWidth > clientWidth) {
        // Sesuaikan lebar konten floating agar ratio scroll-nya sama
        $floatContent.width(scrollWidth);
        $floatInner.width($scrollEl.outerWidth());

        // Posisikan floatWrap sejajar dengan kiri & lebar $scrollEl
        const offset = $scrollEl.offset();
        $floatWrap.css({
          left:  offset ? offset.left : 0,
          width: $scrollEl.outerWidth()
        });

        // Tampilkan hanya jika scrollbar asli TIDAK terlihat di viewport
        const tableBottom = offset ? offset.top + $scrollEl.outerHeight() : 9999;
        const viewBottom  = $(window).scrollTop() + $(window).height();
        if (tableBottom > viewBottom) {
          $floatWrap.show();
        } else {
          $floatWrap.hide();
        }
      } else {
        $floatWrap.hide();
      }
    }

    // Sinkronisasi dua arah ── flag agar tidak infinite-loop
    let syncingFloat = false;
    let syncingReal  = false;

    $floatInner.on('scroll', function() {
      if (syncingFloat) return;
      syncingReal = true;
      const $scrollEl = getRealScrollEl();
      if ($scrollEl.length) $scrollEl[0].scrollLeft = this.scrollLeft;
      setTimeout(function() { syncingReal = false; }, 20);
    });

    $(document).on('scroll.floatbar', function() {
      const $scrollEl = getRealScrollEl();
      if (!syncingReal && $scrollEl.length) {
        syncingFloat = true;
        $floatInner[0].scrollLeft = $scrollEl[0].scrollLeft;
        setTimeout(function() { syncingFloat = false; }, 20);
      }
      syncFloatingScrollbar();
    });

    // Sinkronkan juga saat tabel itu sendiri di-scroll
    $(document).on('scroll.floatbar2', '#tableCustomer', function() {
      syncFloatingScrollbar();
    });

    // Jalankan saat DataTable selesai draw
    $('#tableCustomer').on('draw.dt', function() {
      setTimeout(syncFloatingScrollbar, 100);
    });

    // Jalankan saat window resize
    $(window).on('resize.floatbar', syncFloatingScrollbar);

    // Trigger pertama kali
    setTimeout(syncFloatingScrollbar, 500);

    // Sync scroll real → float
    $(document).on('scroll', function() {
      const $scrollEl = getRealScrollEl();
      if (!syncingReal && $scrollEl.length) {
        syncingFloat = true;
        $floatInner[0].scrollLeft = $scrollEl[0].scrollLeft;
        setTimeout(function() { syncingFloat = false; }, 20);
      }
    });

    // Pasang listener pada elemen scroll DataTable langsung
    $(document).on('scroll.dtscroll', '.dataTables_scrollBody, .table-responsive', function() {
      if (this === getRealScrollEl()[0]) {
        if (!syncingReal) {
          syncingFloat = true;
          $floatInner[0].scrollLeft = this.scrollLeft;
          setTimeout(function() { syncingFloat = false; }, 20);
        }
      }
    });
  })();
  // ───────────────────────────────────────────────────────────────
  
  // Column visibility toggle with localStorage persistence
  const STORAGE_KEY = 'customerTableColumns';
  
  // Load saved column visibility from localStorage
  function loadColumnVisibility() {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
      const visibility = JSON.parse(saved);
      visibility.forEach(function(item) {
        const column = table.column(item.index);
        column.visible(item.visible);
        $('input.col-toggle[value="' + item.index + '"]').prop('checked', item.visible);
      });
    }
  }
  
  // Save column visibility to localStorage
  function saveColumnVisibility() {
    const visibility = [];
    $('input.col-toggle').each(function() {
      visibility.push({
        index: parseInt($(this).val()),
        visible: $(this).is(':checked')
      });
    });
    localStorage.setItem(STORAGE_KEY, JSON.stringify(visibility));
  }
  
  // Handle column toggle checkbox changes
  $(document).on('change', '.col-toggle', function() {
    const colIndex = parseInt($(this).val());
    const isVisible = $(this).is(':checked');
    table.column(colIndex).visible(isVisible);
    saveColumnVisibility();
  });
  
  // Reset columns to default (all visible)
  $('#btnResetColumns').click(function() {
    $('input.col-toggle').prop('checked', true);
    for (let i = 0; i <= 10; i++) {
      table.column(i).visible(true);
    }
    localStorage.removeItem(STORAGE_KEY);
  });
  
  // Load saved visibility on page load
  loadColumnVisibility();
  
  // Handle Detail button
  $(document).on('click', '.btn-detail', function() {
    let cid = $(this).data('cid');
    let $modal = $('#modalDetailCust');
    
    // Reset contents
    $('#customerProfileContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');
    $('#assetSummaryContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>');
    
    $modal.modal('show');

    // 1. Load Customer Profile
    $.ajax({
      url: '{{ route("detail-customer") }}',
      type: 'GET',
      data: { cid: cid },
      success: function(data) {
        let profileHtml = `
          <div class="row g-3">
            <div class="col-sm-6">
              <small class="text-muted text-uppercase fw-semibold d-block">CID</small>
              <p class="fw-bold text-primary mb-0">${data.cid}</p>
            </div>
            <div class="col-sm-6 text-sm-end">
              <small class="text-muted text-uppercase fw-semibold d-block">Status</small>
              <span class="badge ${data.status === 'Aktif' ? 'bg-success' : 'bg-danger'}">${data.status || '-'}</span>
            </div>
            
            <div class="col-12"><hr class="my-0"></div>

            <div class="col-sm-6">
              <small class="text-muted text-uppercase fw-semibold d-block">Customer Name</small>
              <p class="mb-0">${data.nama}</p>
            </div>
            <div class="col-sm-6">
              <small class="text-muted text-uppercase fw-semibold d-block">Email</small>
              <p class="mb-0">${data.email || '-'}</p>
            </div>

            <div class="col-sm-6">
              <small class="text-muted text-uppercase fw-semibold d-block">Sales / PIC</small>
              <p class="mb-0 text-truncate" title="${data.sales}">${data.sales || '-'}</p>
            </div>
            <div class="col-sm-6">
              <small class="text-muted text-uppercase fw-semibold d-block">POP Location</small>
              <span class="badge bg-label-info">${data.pop || '-'}</span>
            </div>

            <div class="col-sm-6">
              <small class="text-muted text-uppercase fw-semibold d-block">Packet Plan</small>
              <p class="mb-0">${data.packet || '-'}</p>
            </div>
            <div class="col-sm-6">
              <small class="text-muted text-uppercase fw-semibold d-block">Billing Active</small>
              <p class="mb-0">${data.billing_aktif || '-'}</p>
            </div>

            <div class="col-12"><hr class="my-0"></div>

            <div class="col-md-6">
              <small class="text-muted text-uppercase fw-semibold d-block">Payment / Month</small>
              <p class="mb-0 text-success fw-bold">${data.pembayaran_perbulan_formatted || '-'}</p>
            </div>
            <div class="col-md-6">
              <small class="text-muted text-uppercase fw-semibold d-block">Setup Fee</small>
              <p class="mb-0">${data.setup_fee_formatted || '-'}</p>
            </div>

            <div class="col-12"><hr class="my-0"></div>

            <div class="col-12">
              <small class="text-muted text-uppercase fw-semibold d-block">Address</small>
              <p class="mb-0 small">${data.alamat || '-'}</p>
            </div>

            <div class="col-sm-6">
              <small class="text-muted text-uppercase fw-semibold d-block">PIC IT</small>
              <p class="mb-0">${data.pic_it || '-'} <br> <small class="text-muted">${data.no_it || '-'}</small></p>
            </div>
            <div class="col-sm-6">
              <small class="text-muted text-uppercase fw-semibold d-block">PIC Finance</small>
              <p class="mb-0">${data.pic_finance || '-'} <br> <small class="text-muted">${data.no_finance || '-'}</small></p>
            </div>

            <div class="col-12">
              <small class="text-muted text-uppercase fw-semibold d-block">Maps Coordinate</small>
              <a href="${data.coordinate_maps}" target="_blank" class="text-truncate d-block small">${data.coordinate_maps || '-'}</a>
            </div>

            <div class="col-12">
              <div class="p-2 bg-light rounded shadow-sm">
                <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.65rem">Internal Note:</small>
                <div style="font-size: 0.85rem">${data.note || '-'}</div>
              </div>
            </div>
          </div>
        `;
        $('#customerProfileContent').html(profileHtml);
      },
      error: function() {
        $('#customerProfileContent').html('<div class="alert alert-danger">Failed to load profile.</div>');
      }
    });

    // 2. Load Asset Summary (Integrated with Asset Management)
    // Route: /customers/{externalId}/assets
    $.ajax({
      url: `/customers/${cid}/assets`,
      type: 'GET',
      success: function(response) {
        let assets = [];
        // Handle both possible response structures (wrap in summary or direct array)
        if (response.summary) assets = response.summary;
        else if (Array.isArray(response)) assets = response;
        else if (response.data) assets = response.data;

        if (assets.length === 0) {
          $('#assetSummaryContent').html(`
            <div class="text-center py-5">
              <i class="bx bx-info-circle fs-1 text-muted mb-2"></i>
              <p class="text-muted">No assets deployed for this customer.</p>
            </div>
          `);
          return;
        }

        let assetHtml = '<div class="list-group list-group-flush">';
        assets.forEach(function(asset) {
          assetHtml += `
            <div class="list-group-item px-0 py-3">
              <div class="d-flex align-items-start">
                <div class="avatar flex-shrink-0 me-3">
                  <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-cube"></i></span>
                </div>
                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                  <div class="me-2">
                    <h6 class="mb-0 text-dark">${asset.asset_name || asset.name || 'Unknown Asset'}</h6>
                    <small class="text-muted">${asset.total_qty || asset.qty || 0} ${asset.uom || 'unit'}</small>
                  </div>
                  <div class="user-progress text-end">
                    <span class="badge bg-label-info" style="font-size: 0.75rem">${asset.label || (asset.asset_name + ' (' + asset.total_qty + ')') }</span>
                  </div>
                </div>
              </div>
            </div>
          `;
        });
        assetHtml += '</div>';
        $('#assetSummaryContent').html(assetHtml);
      },
      error: function(xhr) {
        let errMsg = 'Data not found / connection error';
        if (xhr.status === 404) {
          errMsg = 'CID not found in Asset Management';
        } else if (xhr.responseJSON && (xhr.responseJSON.detail || xhr.responseJSON.message)) {
          errMsg = xhr.responseJSON.detail || xhr.responseJSON.message;
        }
        
        $('#assetSummaryContent').html(`
          <div class="alert alert-warning py-3 mb-0">
            <i class="bx bx-error-circle me-1"></i>
            ${errMsg}
          </div>
        `);
      }
    });
  });
  
  // Handle Edit button
  $(document).on('click', '.btn-edit', function() {
    currentAction = 'edit';
    let cid = $(this).data('cid');
    
    $.ajax({
      url: '{{ route("detail-customer") }}',
      type: 'GET',
      data: { cid: cid },
      success: function(data) {
        $('#modalTitle').text('Edit Customer');
        $('#cid').val(data.cid).prop('readonly', true);
        $('#nama').val(data.nama);
        $('#email').val(data.email);
        $('#sales').val(data.sales);
        
        // Pre-set POP value so loadPops can pick it up in shown.bs.modal
        $('#pop').val(data.pop);
        
        $('#packet').val(data.packet);
        $('#alamat').val(data.alamat);
        $('#pic_it').val(data.pic_it);
        $('#no_it').val(data.no_it);
        $('#pic_finance').val(data.pic_finance);
        $('#no_finance').val(data.no_finance);
        $('#coordinate_maps').val(data.coordinate_maps);
        
        // For edit mode, display raw numeric values in input fields
        // We use Math.floor to remove .00 decimals from database
        $('#pembayaran_perbulan').val(data.pembayaran_perbulan ? Math.floor(data.pembayaran_perbulan) : '').trigger('keyup');
        $('#setup_fee').val(data.setup_fee ? Math.floor(data.setup_fee) : '').trigger('keyup');
        
        $('#tgl_customer_aktif').val(data.tgl_customer_aktif);
        $('#billing_aktif').val(data.billing_aktif);
        $('#status').val(data.status);
        $('#note').val(data.note);
        
        if (data.packet === 'dedicated') {
          $('#noteField').show();
        }
        
        $('#modalAddCust').modal('show');
      }
    });
  });
  
  // Handle Save button
  $('#btnSaveCust').click(function() {
    let $btn = $(this);
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
    
    let formData = {
      nama: $('#nama').val(),
      email: $('#email').val(),
      sales: $('#sales').val(),
      pop: $('#pop').val(),
      packet: $('#packet').val(),
      alamat: $('#alamat').val(),
      pic_it: $('#pic_it').val(),
      no_it: $('#no_it').val(),
      pic_finance: $('#pic_finance').val(),
      no_finance: $('#no_finance').val(),
      coordinate_maps: $('#coordinate_maps').val(),
      pembayaran_perbulan: parseRupiah($('#pembayaran_perbulan').val()),
      setup_fee: parseRupiah($('#setup_fee').val()),
      tgl_customer_aktif: $('#tgl_customer_aktif').val(),
      billing_aktif: $('#billing_aktif').val(),
      status: $('#status').val(),
      note: $('#note').val()
    };
    
    // Include CID for both add (manual entry) and edit
    formData.cid = $('#cid').val();
    
    let url = currentAction === 'add' ? '{{ route("store-customer") }}' : '{{ route("update-customer") }}';
    let method = currentAction === 'add' ? 'POST' : 'PUT';
    
    $.ajax({
      url: url,
      type: method,
      data: formData,
      success: function(response) {
        alert(response.message);
        $('#modalAddCust').modal('hide');
        table.ajax.reload();
        $('#modalAddCust form').trigger('reset');
        $('#cid').prop('readonly', false);
      },
      error: function(xhr) {
        alert('Error: ' + (xhr.responseJSON?.message || 'Something went wrong'));
      },
      complete: function() {
        $btn.prop('disabled', false).text('Save');
      }
    });
  });
  
  // Handle Delete button
  $(document).on('click', '.btn-delete', function() {
    if (!confirm('Are you sure you want to delete this customer?')) return;
    
    let cid = $(this).data('cid');
    
    $.ajax({
      url: '{{ route("delete-customer") }}',
      type: 'POST',
      data: { 
        cid: cid, 
        _token: '{{ csrf_token() }}',
        _method: 'DELETE'
      },
      success: function(response) {
        alert(response.message);
        table.ajax.reload();
      },
      error: function(xhr, status, error) {
        console.error('Delete error:', xhr.responseText);
        alert('Failed to delete customer: ' + (xhr.responseJSON?.message || error));
      }
    });
  });
  
  // Show note field when packet is dedicated
  $('#packet').change(function() {
    if ($(this).val() === 'dedicated') {
      $('#noteField').show();
    } else {
      $('#noteField').hide();
    }
  });
  
  // Reset form when modal is closed
  $('#modalAddCust').on('hidden.bs.modal', function() {
    currentAction = 'add';
    $('#modalTitle').text('Add Customer');
    $('#modalAddCust form').trigger('reset');
    $('#cid').val('').prop('readonly', false);
    $('#noteField').hide();
    $('#pop').val(null).trigger('change');
  });
  
  // When opening modal for add
  $('[data-bs-target="#modalAddCust"]').click(function() {
    if ($(this).data('action') === 'add') {
      currentAction = 'add';
      $('#modalTitle').text('Add Customer');
      $('#cid').val('').prop('readonly', false);
      $('#modalAddCust form').trigger('reset');
    }
  });
});
</script>
@endsection
