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
          <th>CID</th>
          <th>Nama</th>
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

<!-- Modal Detail Cust -->
<div class="modal fade" id="modalDetailCust" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="labelModalCust"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <!-- Garis pembatas -->
      <hr class="my-0">
      <div class="modal-body">
        <div class="row">
          <p id="dataDetailCust"></p>
        </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
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
        { data: 'cid', name: 'cid', width: '80px' },
        { data: 'nama', name: 'nama', width: '150px' },
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
    
    $.ajax({
      url: '{{ route("detail-customer") }}',
      type: 'GET',
      data: { cid: cid },
      success: function(data) {
        let detailHtml = `
          <table class="table table-sm">
            <tr><th>CID</th><td>${data.cid}</td></tr>
            <tr><th>Nama</th><td>${data.nama}</td></tr>
            <tr><th>Email</th><td>${data.email || '-'}</td></tr>
            <tr><th>Sales</th><td>${data.sales || '-'}</td></tr>
            <tr><th>POP</th><td>${data.pop || '-'}</td></tr>
            <tr><th>Packet</th><td>${data.packet || '-'}</td></tr>
            <tr><th>Alamat</th><td>${data.alamat || '-'}</td></tr>
            <tr><th>PIC IT</th><td>${data.pic_it || '-'}</td></tr>
            <tr><th>No IT</th><td>${data.no_it || '-'}</td></tr>
            <tr><th>PIC Finance</th><td>${data.pic_finance || '-'}</td></tr>
            <tr><th>No Finance</th><td>${data.no_finance || '-'}</td></tr>
            <tr><th>Coordinate</th><td>${data.coordinate_maps || '-'}</td></tr>
            <tr><th>Pembayaran</th><td>${data.pembayaran_perbulan_formatted || '-'}</td></tr>
            <tr><th>Setup Fee</th><td>${data.setup_fee_formatted || '-'}</td></tr>
            <tr><th>Tgl Aktif</th><td>${data.tgl_customer_aktif || '-'}</td></tr>
            <tr><th>Billing Aktif</th><td>${data.billing_aktif || '-'}</td></tr>
            <tr><th>Status</th><td>${data.status || '-'}</td></tr>
            <tr><th>Note</th><td>${data.note || '-'}</td></tr>
          </table>
        `;
        
        // Show in a modal or alert
        if ($('#modalDetail').length === 0) {
          $('body').append(`
            <div class="modal fade" id="modalDetail" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Detail Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body" id="detailContent"></div>
                </div>
              </div>
            </div>
          `);
        }
        
        $('#detailContent').html(detailHtml);
        $('#modalDetail').modal('show');
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
