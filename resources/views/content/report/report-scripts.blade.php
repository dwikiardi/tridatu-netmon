@section('page-script')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
  console.log('Report scripts loaded');

  // Initialize Select2 for maintenance filters
  $('.select2').select2({
    width: '100%'
  });

  // Selection Arrays
  let selectedCustomerIds = [];
  let selectedMaintenanceIds = [];

  // ===== HELPER FUNCTIONS =====
  // Format Rupiah
  function formatRupiah(angka, prefix = 'Rp. ') {
    var number_string = angka.replace(/[^,\d]/g, '').toString(),
      split = number_string.split(','),
      sisa = split[0].length % 3,
      rupiah = split[0].substr(0, sisa),
      ribuan = split[0].substr(sisa).match(/\d{3}/gi);

    if (ribuan) {
      separator = sisa ? '.' : '';
      rupiah += separator + ribuan.join('.');
    }

    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    return prefix + rupiah;
  }

  // Remove format Rupiah
  function removeRupiahFormat(rupiah) {
    return rupiah.replace(/[^0-9]/g, '');
  }

  // Format Duration (minutes to h m)
  function formatDuration(minutes) {
    if (!minutes || minutes === 0) return '0m';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return (h > 0 ? h + 'h ' : '') + m + 'm';
  }

  // Format Rupiah Input Events
  $('#filter-min-bayar, #filter-max-bayar').on('keyup', function(e) {
    var value = $(this).val();
    $(this).val(formatRupiah(value, 'Rp. '));
  });

  // Load Sales Data
  $.ajax({
    url: '{{ route('report.sales.users') }}',
    method: 'GET',
    success: function(response) {
      var salesSelect = $('#filter-sales');
      if (response.data && response.data.length > 0) {
        response.data.forEach(function(user) {
          salesSelect.append('<option value="' + user.name + '">' + user.name + '</option>');
        });
      }
    },
    error: function(xhr, status, error) {
      console.log('Failed to load sales data:', error);
    }
  });

  // Load POP Data for filter dropdown
  $.ajax({
    url: '{{ route('get-pops') }}',
    method: 'GET',
    success: function(response) {
      var popSelect = $('#filter-pop');
      if (response && response.length > 0) {
        response.forEach(function(pop) {
          popSelect.append('<option value="' + pop + '">' + pop + '</option>');
        });
      }
    },
    error: function(xhr, status, error) {
      console.log('Failed to load POP data:', error);
    }
  });

  // Format Rupiah Input Events (including setup fee)
  $('#filter-min-bayar, #filter-max-bayar, #filter-min-setup-fee, #filter-max-setup-fee').on('keyup', function(e) {
    var value = $(this).val();
    $(this).val(formatRupiah(value, 'Rp. '));
  });

  // ===== CUSTOMER REPORT TABLE =====
  var customerReportTable = null;
  var currentFilters = null; // Global variable untuk menyimpan filter yang sedang digunakan

  // Initialize DataTable only when needed
  function initCustomerReportTable(filterData = null) {
    // Set current filters SEBELUM cek atau destroy table
    if (filterData) {
      currentFilters = filterData;
      console.log('Setting currentFilters to:', currentFilters);
    } else {
      currentFilters = null;
      console.log('Resetting currentFilters to null');
    }

    // Jika table sudah ada, hanya reload - JANGAN destroy
    if (customerReportTable) {
      console.log('Table exists, reloading with currentFilters:', currentFilters);
      customerReportTable.ajax.reload();
      return;
    }

    // Jika belum ada, buat table baru
    console.log('Creating new DataTable');
    customerReportTable = $('#table-customer-report').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '{{ route('report.customer.data') }}',
        data: function (d) {
          console.log('=== AJAX DATA FUNCTION CALLED ===');
          console.log('currentFilters at ajax time:', currentFilters);

          // Jika ada currentFilters (dari saved filter), gunakan itu. Jika tidak, gunakan form values
          if (currentFilters) {
            console.log('Branch: Using currentFilters');

            // Parse jika masih string
            const filters = typeof currentFilters === 'string' ? JSON.parse(currentFilters) : currentFilters;
            console.log('Parsed filters:', filters);
            console.log('filters.packet:', filters.packet);
            console.log('filters.min_bayar:', filters.min_bayar);

            d.packet = filters.packet || '';
            d.min_bayar = filters.min_bayar || '';
            d.max_bayar = filters.max_bayar || '';
            d.status = filters.status || '';
            d.sales = filters.sales || '';
            d.pop = filters.pop || '';
            d.min_setup_fee = filters.min_setup_fee || '';
            d.max_setup_fee = filters.max_setup_fee || '';
            d.tgl_aktif_from = filters.tgl_aktif_from || '';
            d.tgl_aktif_to = filters.tgl_aktif_to || '';
            d.billing_aktif_from = filters.billing_aktif_from || '';
            d.billing_aktif_to = filters.billing_aktif_to || '';

            console.log('After assignment - d.packet:', d.packet);
            console.log('After assignment - d.min_bayar:', d.min_bayar);
          } else {
            console.log('Branch: Using form values');
            d.packet = $('#filter-packet').val();
            d.min_bayar = removeRupiahFormat($('#filter-min-bayar').val());
            d.max_bayar = removeRupiahFormat($('#filter-max-bayar').val());
            d.status = $('#filter-status').val();
            d.sales = $('#filter-sales').val();
            d.pop = $('#filter-pop').val();
            d.min_setup_fee = removeRupiahFormat($('#filter-min-setup-fee').val());
            d.max_setup_fee = removeRupiahFormat($('#filter-max-setup-fee').val());
            d.tgl_aktif_from = $('#filter-tgl-aktif-from').val();
            d.tgl_aktif_to = $('#filter-tgl-aktif-to').val();
            d.billing_aktif_from = $('#filter-billing-aktif-from').val();
            d.billing_aktif_to = $('#filter-billing-aktif-to').val();
          }

          console.log('Final DataTable Ajax params:', {
            packet: d.packet,
            min_bayar: d.min_bayar,
            max_bayar: d.max_bayar,
            status: d.status,
            sales: d.sales,
            pop: d.pop,
            min_setup_fee: d.min_setup_fee,
            max_setup_fee: d.max_setup_fee,
            tgl_aktif_from: d.tgl_aktif_from,
            tgl_aktif_to: d.tgl_aktif_to,
            billing_aktif_from: d.billing_aktif_from,
            billing_aktif_to: d.billing_aktif_to
          });
        }
      },
      columns: [
        { 
          data: null, 
          orderable: false, 
          searchable: false,
          render: function(data, type, row) {
            const isChecked = selectedCustomerIds.includes(row.cid) ? 'checked' : '';
            return '<input type="checkbox" class="form-check-input customer-checkbox" value="' + row.cid + '" ' + isChecked + '>';
          }
        },
        { data: 'cid' },
        { data: 'nama' },
        { data: 'email' },
        { data: 'alamat' },
        { data: 'coordinate_maps' },
        { data: 'packet' },
        { data: 'pembayaran_perbulan' },
        { data: 'pop' },
        { data: 'setup_fee_formatted' },
        { data: 'status' },
        { data: 'sales' },
        { data: 'pic_it' },
        { data: 'no_it' },
        { data: 'pic_finance' },
        { data: 'no_finance' },
        { data: 'tgl_customer_aktif' },
        { data: 'billing_aktif' },
        { data: 'note' }
      ],
      pageLength: 10,
      lengthMenu: [10, 25, 50, 100],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
        infoFiltered: "(difilter dari _MAX_ total data)",
        zeroRecords: "Tidak ada data yang ditemukan",
        emptyTable: "Tidak ada data",
        paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" }
      }
    });
    window.customerReportTable = customerReportTable;
  }

  // Customer filter & button handlers
  $('#btn-filter-customer').on('click', function() {
    initCustomerReportTable(null); // Reset dengan pass null
    $('#customerDataModal').modal('show');
  });
  $('#btn-reset-customer').on('click', function() {
    $('#filter-customer-form')[0].reset();
    if (customerReportTable) {
      customerReportTable.ajax.reload();
    }
  });

  // ===== COLUMN SELECTION & EXPORT =====
  var exportType = 'excel'; // Default export type

  // Open Export Excel Column Selector
  $('#btn-open-export-excel').on('click', function() {
    exportType = 'excel';
    console.log('Opening export modal for Excel');
    $('#btn-confirm-export-excel').show();
    $('#btn-confirm-export-pdf').hide();
    $('#exportColumnModal').modal('show');
  });

  // Open Export PDF Column Selector
  $('#btn-open-export-pdf').on('click', function() {
    exportType = 'pdf';
    console.log('Opening export modal for PDF');
    $('#btn-confirm-export-excel').hide();
    $('#btn-confirm-export-pdf').show();
    $('#exportColumnModal').modal('show');
  });

  // Select/Deselect all columns
  $('#btn-select-all-columns').on('click', function() {
    $('#exportColumnModal .form-check-input').prop('checked', true);
  });

  $('#btn-deselect-all-columns').on('click', function() {
    $('#exportColumnModal .form-check-input').prop('checked', false);
  });

  // Get selected columns
  function getSelectedColumns() {
    const columns = [];
    $('#exportColumnModal .form-check-input:checked').each(function() {
      columns.push($(this).val());
    });
    return columns;
  }

  // Get filter value from currentFilters or form
  function getFilterValue(filterKey, formSelector, isRupiah = false) {
    let value = '';

    if (currentFilters) {
      const filters = typeof currentFilters === 'string' ? JSON.parse(currentFilters) : currentFilters;
      value = filters[filterKey] || '';
    } else {
      value = $(formSelector).val() || '';
    }

    // Remove empty/null/undefined
    if (!value || value === 'null' || value === 'undefined') {
      return '';
    }

    if (isRupiah) {
      value = removeRupiahFormat(value);
    }

    return value;
  }

  // Export Excel with selected columns
  $('#btn-confirm-export-excel').on('click', function() {
    const columns = getSelectedColumns();
    if (columns.length === 0) {
      alert('Pilih minimal 1 kolom untuk di-export');
      return;
    }

    const params = new URLSearchParams({
      type: 'customer',
      packet: getFilterValue('packet', '#filter-packet'),
      min_bayar: getFilterValue('min_bayar', '#filter-min-bayar', true),
      max_bayar: getFilterValue('max_bayar', '#filter-max-bayar', true),
      status: getFilterValue('status', '#filter-status'),
      sales: getFilterValue('sales', '#filter-sales'),
      pop: getFilterValue('pop', '#filter-pop'),
      min_setup_fee: getFilterValue('min_setup_fee', '#filter-min-setup-fee', true),
      max_setup_fee: getFilterValue('max_setup_fee', '#filter-max-setup-fee', true),
      tgl_aktif_from: getFilterValue('tgl_aktif_from', '#filter-tgl-aktif-from'),
      tgl_aktif_to: getFilterValue('tgl_aktif_to', '#filter-tgl-aktif-to'),
      billing_aktif_from: getFilterValue('billing_aktif_from', '#filter-billing-aktif-from'),
      billing_aktif_to: getFilterValue('billing_aktif_to', '#filter-billing-aktif-to'),
      columns: columns.join(','),
      selected_ids: selectedCustomerIds.join(',')
    });

    $('#exportColumnModal').modal('hide');
    window.location.href = '{{ route('report.export.excel') }}?' + params.toString();
  });

  // Export PDF with selected columns
  $('#btn-confirm-export-pdf').on('click', function() {
    const columns = getSelectedColumns();

    if (columns.length === 0) {
      alert('Pilih minimal 1 kolom untuk di-export');
      return;
    }

    const params = new URLSearchParams({
      type: 'customer',
      packet: getFilterValue('packet', '#filter-packet'),
      min_bayar: getFilterValue('min_bayar', '#filter-min-bayar', true),
      max_bayar: getFilterValue('max_bayar', '#filter-max-bayar', true),
      status: getFilterValue('status', '#filter-status'),
      sales: getFilterValue('sales', '#filter-sales'),
      pop: getFilterValue('pop', '#filter-pop'),
      min_setup_fee: getFilterValue('min_setup_fee', '#filter-min-setup-fee', true),
      max_setup_fee: getFilterValue('max_setup_fee', '#filter-max-setup-fee', true),
      tgl_aktif_from: getFilterValue('tgl_aktif_from', '#filter-tgl-aktif-from'),
      tgl_aktif_to: getFilterValue('tgl_aktif_to', '#filter-tgl-aktif-to'),
      billing_aktif_from: getFilterValue('billing_aktif_from', '#filter-billing-aktif-from'),
      billing_aktif_to: getFilterValue('billing_aktif_to', '#filter-billing-aktif-to'),
      columns: columns.join(','),
      selected_ids: selectedCustomerIds.join(',')
    });

    $('#exportColumnModal').modal('hide');
    window.open('{{ route('report.export.pdf') }}?' + params.toString(), '_blank');
  });

  // Clean up modal backdrop when export modal is hidden
  $('#exportColumnModal').on('hidden.bs.modal', function () {
    // Remove any leftover backdrop
    $('.modal-backdrop').remove();
    // Ensure body doesn't keep modal-open class
    $('body').removeClass('modal-open');
    $('body').css('overflow', '');
    $('body').css('padding-right', '');
  });

  // Load customer summary
  $.get('{{ route('report.customer.summary') }}', function (data) {
    $('#total-customers').text(data.total_customers);
    $('#active-customers').text(data.active_customers);
    $('#total-revenue').text(data.total_revenue);
    $('#inactive-customers').text(data.inactive_customers);
  });

  // ===== SAVE FILTER FUNCTIONALITY =====
  // Save Filter
  $('#btn-save-filter').on('click', function() {
    const filterName = $('#filter-name').val();
    if (!filterName) {
      alert('Mohon masukkan nama filter');
      return;
    }

    const filterData = {
      name: filterName,
      type: 'customer',
      filters: {
        packet: $('#filter-packet').val(),
        min_bayar: removeRupiahFormat($('#filter-min-bayar').val()),
        max_bayar: removeRupiahFormat($('#filter-max-bayar').val()),
        status: $('#filter-status').val(),
        sales: $('#filter-sales').val(),
        pop: $('#filter-pop').val(),
        min_setup_fee: removeRupiahFormat($('#filter-min-setup-fee').val()),
        max_setup_fee: removeRupiahFormat($('#filter-max-setup-fee').val()),
        tgl_aktif_from: $('#filter-tgl-aktif-from').val(),
        tgl_aktif_to: $('#filter-tgl-aktif-to').val(),
        billing_aktif_from: $('#filter-billing-aktif-from').val(),
        billing_aktif_to: $('#filter-billing-aktif-to').val()
      }
    };

    $.ajax({
      url: '{{ route('report.filter.save') }}',
      method: 'POST',
      data: filterData,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        alert('Filter berhasil disimpan!');
        $('#filter-name').val('');
        $('#save-filter-section').collapse('hide');
        loadSavedFilters();
      },
      error: function(xhr, status, error) {
        console.error('Save filter error:', xhr.responseText);
        alert('Gagal menyimpan filter: ' + (xhr.responseJSON?.message || error));
      }
    });
  });

  // Load Saved Filters
  function loadSavedFilters() {
    $.get('{{ route('report.filters.get', ['type' => 'customer']) }}', function(response) {
      const container = $('#saved-filters-list');
      container.empty();

      if (response.data && response.data.length > 0) {
        response.data.forEach(function(filter) {
          const badge = $('<button>')
            .addClass('btn btn-sm btn-outline-primary position-relative me-2 mb-2')
            .text(filter.name)
            .attr('data-filter-id', filter.id)
            .on('click', function() {
              applyFilter(filter);
            });

          const deleteBtn = $('<span>')
            .addClass('position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger')
            .html('&times;')
            .css('cursor', 'pointer')
            .on('click', function(e) {
              e.stopPropagation();
              deleteFilter(filter.id);
            });

          badge.append(deleteBtn);
          container.append(badge);
        });
      } else {
        container.html('<small class="text-muted">Belum ada filter tersimpan</small>');
      }
    }).fail(function(xhr, status, error) {
      console.error('Load saved filters error:', error);
      $('#saved-filters-list').html('<small class="text-danger">Gagal memuat filter tersimpan</small>');
    });
  }

  // Apply Filter
  function applyFilter(filter) {
    // Model sudah auto-cast filters ke array, jadi tidak perlu parse lagi
    const filters = typeof filter.filters === 'string' ? JSON.parse(filter.filters) : filter.filters;

    // Buka modal
    $('#customerDataModal').modal('show');

    // Destroy table lama jika ada
    if (customerReportTable) {
      customerReportTable.destroy();
      customerReportTable = null;
    }

    // Init datatable dengan filters langsung
    setTimeout(function() {
      initCustomerReportTable(filters);
    }, 300);
  }

  // Delete Filter
  function deleteFilter(filterId) {
    if (!confirm('Hapus filter ini?')) {
      return;
    }

    $.ajax({
      url: '{{ route('report.filter.delete', ['id' => ':id']) }}'.replace(':id', filterId),
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function() {
        loadSavedFilters();
      },
      error: function(xhr) {
        alert('Gagal menghapus filter: ' + (xhr.responseJSON?.message || 'Unknown error'));
      }
    });
  }

  // Load saved filters on page load
  loadSavedFilters();

  // ===== MAINTENANCE REPORT TABLE =====
  var maintenanceReportTable = null;
  var currentMaintenanceFilters = null;

  function initMaintenanceReportTable(filterData = null) {
    if (filterData) {
      currentMaintenanceFilters = filterData;
      console.log('Setting currentMaintenanceFilters to:', currentMaintenanceFilters);
    } else {
      currentMaintenanceFilters = null;
      console.log('Resetting currentMaintenanceFilters to null');
    }

    // Check if table is already a DataTable
    if ($.fn.DataTable.isDataTable('#table-maintenance-report')) {
      console.log('Maintenance DataTable exists (isDataTable check).');
      
      // If we have a stored reference, use it
      if (!maintenanceReportTable) {
        maintenanceReportTable = $('#table-maintenance-report').DataTable();
      }
      
      console.log('Reloading existing table with filters:', currentMaintenanceFilters);
      maintenanceReportTable.ajax.reload();
      return;
    }

    console.log('Creating new Maintenance DataTable');
    maintenanceReportTable = $('#table-maintenance-report').DataTable({
      destroy: true, // Add destroy option to be safe
      processing: true,
      serverSide: true,
      ajax: {
        url: '{{ route('report.maintenance.data') }}',
        data: function (d) {
          console.log('=== MAINTENANCE AJAX DATA FUNCTION CALLED ===');
          console.log('currentMaintenanceFilters at ajax time:', currentMaintenanceFilters);

          if (currentMaintenanceFilters) {
            console.log('Branch: Using currentMaintenanceFilters');
            const filters = typeof currentMaintenanceFilters === 'string' ? JSON.parse(currentMaintenanceFilters) : currentMaintenanceFilters;
            console.log('Parsed filters:', filters);

            d.pic_teknisi = filters.pic_teknisi || '';
            d.customer_nama = filters.customer_nama || '';
            d.status = filters.status || '';
            d.sales_id = filters.sales_id || '';
            d.date_from = filters.date_from || '';
            d.date_to = filters.date_to || '';
          } else {
            console.log('Branch: Using form values');
            d.pic_teknisi = $('#filter-teknisi').val();
            d.customer_nama = $('#filter-maintenance-cust-name').val();
            d.status = $('#filter-maintenance-status').val();
            d.sales_id = $('#filter-maintenance-sales').val();
            d.date_from = $('#filter-maintenance-date-from').val();
            d.date_to = $('#filter-maintenance-date-to').val();
          }
        },
        error: function(xhr, error, thrown) {
          console.error('Maintenance data error:', error, xhr.responseText);
        }
      },
      columns: [
        { 
          data: null, 
          orderable: false, 
          searchable: false,
          render: function(data, type, row) {
            // Need a unique ID for maintenance rows. Row might have an 'id' property.
            const isChecked = selectedMaintenanceIds.includes(row.id) ? 'checked' : '';
            return '<input type="checkbox" class="form-check-input maintenance-checkbox" value="' + row.id + '" ' + isChecked + '>';
          }
        },
        { data: 'ticket_no', defaultContent: '-' },
        { data: 'cid', defaultContent: '-' },
        { data: 'customer_nama', defaultContent: '-' },
        { data: 'jenis', defaultContent: '-' },
        { data: 'pic_teknisi', defaultContent: '-' },
        { data: 'tanggal_kunjungan', defaultContent: '-' },
        { data: 'kendala', defaultContent: '-' },
        { data: 'hasil', defaultContent: '-' },
        { 
          data: 'sla_remote_minutes', 
          render: function(data) {
            return formatDuration(data);
          }
        },
        { 
          data: 'sla_onsite_minutes', 
          render: function(data) {
            return formatDuration(data);
          }
        },
        { 
          data: 'sla_total_minutes', 
          render: function(data) {
            return formatDuration(data);
          }
        },
        { data: 'status', defaultContent: '-' },
        { data: 'priority', defaultContent: '-' }
      ],
      pageLength: 10,
      lengthMenu: [10, 25, 50, 100],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
        infoFiltered: "(difilter dari _MAX_ total data)",
        zeroRecords: "Tidak ada data yang ditemukan",
        emptyTable: "Tidak ada data",
        paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" }
      }
    });
    window.maintenanceReportTable = maintenanceReportTable;
  }

  // Maintenance filter & button handlers
  $('#btn-filter-maintenance').on('click', function() {
    initMaintenanceReportTable(null);
    $('#maintenanceDataModal').modal('show');
  });

  // Maintenance chart filter handler
  $('#btn-apply-chart-filter').on('click', function() {
    const filters = {
      date_from: $('#chart-date-from').val(),
      date_to: $('#chart-date-to').val()
    };
    loadMaintenanceSummary(filters);
  });

  $('#btn-reset-maintenance').on('click', function() {
    $('#filter-maintenance-form')[0].reset();
    $('#filter-maintenance-form select').val('').trigger('change');
    if (maintenanceReportTable) {
      maintenanceReportTable.ajax.reload();
    }
    // Summary cards and chart are reset separately or by reloading the page
  });

  $('#btn-reset-chart').on('click', function() {
    $('#chart-date-from').val('');
    $('#chart-date-to').val('');
    loadMaintenanceSummary();
  });

  // Export Excel for Maintenance with selected columns
  $('#btn-open-export-excel-maintenance').on('click', function() {
    exportType = 'excel';
    console.log('Opening export modal for Excel (Maintenance)');
    $('#btn-confirm-export-excel-maintenance').show();
    $('#btn-confirm-export-pdf-maintenance').hide();
    $('#exportMaintenanceColumnModal').modal('show');
  });

  // Export PDF for Maintenance with selected columns
  $('#btn-open-export-pdf-maintenance').on('click', function() {
    exportType = 'pdf';
    console.log('Opening export modal for PDF (Maintenance)');
    $('#btn-confirm-export-excel-maintenance').hide();
    $('#btn-confirm-export-pdf-maintenance').show();
    $('#exportMaintenanceColumnModal').modal('show');
  });

  // Get selected columns for maintenance
  function getSelectedMaintenanceColumns() {
    const columns = [];
    $('#exportMaintenanceColumnModal .form-check-input:checked').each(function() {
      columns.push($(this).val());
    });
    return columns;
  }

  // Get filter value from currentMaintenanceFilters or form
  function getMaintenanceFilterValue(filterKey, formSelector) {
    let value = '';

    if (currentMaintenanceFilters) {
      const filters = typeof currentMaintenanceFilters === 'string' ? JSON.parse(currentMaintenanceFilters) : currentMaintenanceFilters;
      value = filters[filterKey] || '';
    } else {
      value = $(formSelector).val() || '';
    }

    if (!value || value === 'null' || value === 'undefined') {
      return '';
    }

    return value;
  }

  // Export Excel for Maintenance with selected IDs
  $('#btn-confirm-export-excel-maintenance').on('click', function() {
    const columns = getSelectedMaintenanceColumns();
    if (columns.length === 0) {
      alert('Pilih minimal 1 kolom untuk di-export');
      return;
    }

    const params = new URLSearchParams({
      type: 'maintenance',
      pic_teknisi: getMaintenanceFilterValue('pic_teknisi', '#filter-teknisi'),
      cid: getMaintenanceFilterValue('cid', '#filter-maintenance-cid'),
      customer_nama: getMaintenanceFilterValue('customer_nama', '#filter-maintenance-cust-name'),
      status: getMaintenanceFilterValue('status', '#filter-maintenance-status'),
      jenis: getMaintenanceFilterValue('jenis', '#filter-jenis'),
      date_from: getMaintenanceFilterValue('date_from', '#filter-maintenance-date-from'),
      date_to: getMaintenanceFilterValue('date_to', '#filter-maintenance-date-to'),
      columns: columns.join(','),
      selected_ids: selectedMaintenanceIds.join(',')
    });

    $('#exportMaintenanceColumnModal').modal('hide');
    window.location.href = '{{ route('report.export.excel') }}?' + params.toString();
  });

  // Export PDF for Maintenance with selected IDs
  $('#btn-confirm-export-pdf-maintenance').on('click', function() {
    const columns = getSelectedMaintenanceColumns();
    if (columns.length === 0) {
      alert('Pilih minimal 1 kolom untuk di-export');
      return;
    }

    const params = new URLSearchParams({
      type: 'maintenance',
      pic_teknisi: getMaintenanceFilterValue('pic_teknisi', '#filter-teknisi'),
      cid: getMaintenanceFilterValue('cid', '#filter-maintenance-cid'),
      customer_nama: getMaintenanceFilterValue('customer_nama', '#filter-maintenance-cust-name'),
      status: getMaintenanceFilterValue('status', '#filter-maintenance-status'),
      jenis: getMaintenanceFilterValue('jenis', '#filter-jenis'),
      date_from: getMaintenanceFilterValue('date_from', '#filter-maintenance-date-from'),
      date_to: getMaintenanceFilterValue('date_to', '#filter-maintenance-date-to'),
      columns: columns.join(','),
      selected_ids: selectedMaintenanceIds.join(',')
    });

    $('#exportMaintenanceColumnModal').modal('hide');
    window.open('{{ route('report.export.pdf') }}?' + params.toString(), '_blank');
  });

  // Export RFO for Maintenance
  $('#btn-export-rfo-maintenance').on('click', function() {
    if (selectedMaintenanceIds.length === 0) {
      alert('Pilih minimal 1 ticket untuk export RFO');
      return;
    }

    const params = new URLSearchParams({
      selected_ids: selectedMaintenanceIds.join(',')
    });

    window.open('{{ route('report.export.rfo') }}?' + params.toString(), '_blank');
  });

  // Select/Deselect all columns for maintenance
  $('#btn-select-all-columns-maintenance').on('click', function() {
    $('#exportMaintenanceColumnModal .form-check-input').prop('checked', true);
  });

  $('#btn-deselect-all-columns-maintenance').on('click', function() {
    $('#exportMaintenanceColumnModal .form-check-input').prop('checked', false);
  });

  // Clean up modal backdrop when maintenance export modal is hidden
  $('#exportMaintenanceColumnModal').on('hidden.bs.modal', function () {
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
    $('body').css('overflow', '');
    $('body').css('padding-right', '');
  });

  // Save Filter for Maintenance
  $('#btn-save-filter-maintenance').on('click', function() {
    const filterName = $('#filter-maintenance-name').val();
    if (!filterName) {
      alert('Mohon masukkan nama filter');
      return;
    }

    const filterData = {
      name: filterName,
      type: 'maintenance',
      filters: {
        pic_teknisi: $('#filter-teknisi').val(),
        customer_nama: $('#filter-maintenance-cust-name').val(),
        status: $('#filter-maintenance-status').val(),
        sales_id: $('#filter-maintenance-sales').val(),
        date_from: $('#filter-maintenance-date-from').val(),
        date_to: $('#filter-maintenance-date-to').val()
      }
    };

    $.ajax({
      url: '{{ route('report.filter.save') }}',
      method: 'POST',
      data: filterData,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        alert('Filter berhasil disimpan');
        $('#filter-maintenance-name').val('');
        loadSavedMaintenanceFilters();
      },
      error: function(xhr, status, error) {
        console.log('Error saving filter:', error, xhr.responseText);
        alert('Gagal menyimpan filter');
      }
    });
  });

  // Load saved filters for maintenance
  function loadSavedMaintenanceFilters() {
    $.get('{{ route('report.filters.get', 'maintenance') }}', function(response) {
      const filtersList = $('#saved-filters-maintenance-list');
      filtersList.html('');

      if (response.data && response.data.length > 0) {
        response.data.forEach(function(filter) {
          const filterBtn = $('<button type="button" class="btn btn-sm btn-outline-primary"></button>')
            .text(filter.name)
            .on('click', function() {
              console.log('Loading saved filter:', filter.name);
              const filterParams = typeof filter.filters === 'string' ? JSON.parse(filter.filters) : filter.filters;
              initMaintenanceReportTable(filterParams);
              loadMaintenanceSummary(filterParams);
              $('#maintenanceDataModal').modal('show');
            });

          const deleteBtn = $('<button type="button" class="btn btn-sm btn-outline-danger ms-2"></button>')
            .html('<i class="bx bx-trash"></i>')
            .on('click', function(e) {
              e.stopPropagation();
              if (confirm('Hapus filter ini?')) {
                $.ajax({
                  url: '{{ route('report.filter.delete', '') }}/' + filter.id,
                  method: 'DELETE',
                  headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                  },
                  success: function() {
                    loadSavedMaintenanceFilters();
                  }
                });
              }
            });

          const wrapper = $('<div class="d-flex align-items-center"></div>')
            .append(filterBtn)
            .append(deleteBtn);

          filtersList.append(wrapper);
        });
      }
    });
  }

  // Load saved maintenance filters
  loadSavedMaintenanceFilters();

  let teknisiChart = null;

  // Load maintenance summary
  function loadMaintenanceSummary(filters = {}) {
    $.get('{{ route('report.maintenance.summary') }}', filters, function (data) {
      $('#total-tickets').text(data.total_tickets);
      $('#completed-tickets').text(data.completed_tickets);
      $('#pending-tickets').text(data.pending_tickets);

      if (data.visit_per_teknisi && data.visit_per_teknisi.length > 0) {
        $('#top-teknisi').html(data.visit_per_teknisi[0].name + '<br><small>' + data.visit_per_teknisi[0].visits + ' visits</small>');
      } else {
        $('#top-teknisi').text('-');
      }

      // Render Chart
      const ctx = document.getElementById('teknisiVisitChart').getContext('2d');
      const labels = data.visit_per_teknisi.map(item => item.name);
      const visits = data.visit_per_teknisi.map(item => item.visits);

      if (teknisiChart) {
        teknisiChart.destroy();
      }

      teknisiChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Jumlah Kunjungan',
            data: visits,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
            borderRadius: 5
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                stepSize: 1
              }
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });

      // Top Customers List
      const customersList = document.getElementById('top-customers-list');
      if (customersList) {
        customersList.innerHTML = '';
        if (data.most_visited_customers && data.most_visited_customers.length > 0) {
          data.most_visited_customers.forEach((customer, index) => {
            const li = document.createElement('div');
            li.className = 'd-flex justify-content-between align-items-center border-bottom py-2';
            li.innerHTML = '<span>' + (index + 1) + '. ' + customer.customer_name + '</span><span class="badge bg-primary">' + customer.visit_count + ' visits</span>';
            customersList.appendChild(li);
          });
        } else {
          customersList.innerHTML = '<small class="text-muted">Tidak ada data</small>';
        }
      }

      // Visits Per Teknisi List
      const teknisiList = document.getElementById('visits-teknisi-list');
      if (teknisiList) {
        teknisiList.innerHTML = '';
        if (data.visit_per_teknisi && data.visit_per_teknisi.length > 0) {
          data.visit_per_teknisi.forEach((teknisi, index) => {
            const li = document.createElement('div');
            li.className = 'd-flex justify-content-between align-items-center border-bottom py-2';
            li.innerHTML = '<span>' + (index + 1) + '. ' + teknisi.name + '</span><span class="badge bg-success">' + teknisi.visits + ' visits</span>';
            teknisiList.appendChild(li);
          });
        } else {
          teknisiList.innerHTML = '<small class="text-muted">Tidak ada data</small>';
        }
      }
    });
  }

  // Initial load
  loadMaintenanceSummary();

  // CHECKBOX SELECTION LOGIC
  
  // Customer Checkboxes
  $('#check-all-customer').on('click', function() {
    const isChecked = $(this).prop('checked');
    $('.customer-checkbox').prop('checked', isChecked);
    
    $('.customer-checkbox').each(function() {
      const id = $(this).val();
      if (isChecked) {
        if (!selectedCustomerIds.includes(id)) selectedCustomerIds.push(id);
      } else {
        selectedCustomerIds = selectedCustomerIds.filter(item => item !== id);
      }
    });
    console.log('Selected Customer IDs:', selectedCustomerIds);
  });

  $(document).on('change', '.customer-checkbox', function() {
    const id = $(this).val();
    if ($(this).prop('checked')) {
      if (!selectedCustomerIds.includes(id)) selectedCustomerIds.push(id);
    } else {
      selectedCustomerIds = selectedCustomerIds.filter(item => item !== id);
      $('#check-all-customer').prop('checked', false);
    }
    console.log('Selected Customer IDs:', selectedCustomerIds);
  });

  // Maintenance Checkboxes
  $('#check-all-maintenance').on('click', function() {
    const isChecked = $(this).prop('checked');
    $('.maintenance-checkbox').prop('checked', isChecked);
    
    $('.maintenance-checkbox').each(function() {
      const id = parseInt($(this).val());
      if (isChecked) {
        if (!selectedMaintenanceIds.includes(id)) selectedMaintenanceIds.push(id);
      } else {
        selectedMaintenanceIds = selectedMaintenanceIds.filter(item => item !== id);
      }
    });
    console.log('Selected Maintenance IDs:', selectedMaintenanceIds);
  });

  $(document).on('change', '.maintenance-checkbox', function() {
    const id = parseInt($(this).val());
    if ($(this).prop('checked')) {
      if (!selectedMaintenanceIds.includes(id)) selectedMaintenanceIds.push(id);
    } else {
      selectedMaintenanceIds = selectedMaintenanceIds.filter(item => item !== id);
      $('#check-all-maintenance').prop('checked', false);
    }
    console.log('Selected Maintenance IDs:', selectedMaintenanceIds);
  });

  // Reset selected IDs on filter
  $('#btn-filter-customer, #btn-filter-maintenance').on('click', function() {
    selectedCustomerIds = [];
    selectedMaintenanceIds = [];
    $('#check-all-customer, #check-all-maintenance').prop('checked', false);
  });
});
</script>
@endsection
