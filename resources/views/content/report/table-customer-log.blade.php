@extends('layouts/contentNavbarLayout')

@section('title', 'Customer Log History')

@section('page-style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
  .select2-container .select2-selection--single {
    height: 38px;
    padding: 6px 12px;
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 24px;
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
  }

  /* Sticky Columns - CID & Customer */
  .sticky-col-1 {
    position: sticky !important;
    left: 0;
    z-index: 2;
    background-color: #fff !important;
    box-shadow: 2px 0 4px rgba(0,0,0,0.08);
  }
  .sticky-col-2 {
    position: sticky !important;
    left: 100px;
    z-index: 2;
    background-color: #fff !important;
    box-shadow: 2px 0 4px rgba(0,0,0,0.08);
  }

  /* Header Sticky Column (Corner) */
  .dataTables_scrollHead th.sticky-col-1,
  .dataTables_scrollHead th.sticky-col-2 {
    z-index: 5 !important;
    background-color: #f8f9fa !important;
  }

  /* Sticky thead (DataTables scrollX pakai .dataTables_scrollHead) */
  #tableCustomerLog_wrapper .dataTables_scrollHead {
    position: sticky;
    top: 0;
    z-index: 4;
    background: #fff;
  }
  #tableCustomerLog tbody tr:hover td.sticky-col-1,
  #tableCustomerLog tbody tr:hover td.sticky-col-2 {
    background-color: #f1f1f1;
  }

  #tableCustomerLog {
    border-collapse: separate !important;
    border-spacing: 0;
  }
  #tableCustomerLog th, #tableCustomerLog td {
    border-bottom: 1px solid #dee2e6 !important;
    padding-right: 25px !important;
  }

  /* Floating / Sticky Horizontal Scrollbar */
  #floatingScrollbarWrapLog {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 1050;
    background: #fff;
    border-top: 1px solid #dee2e6;
    padding: 4px 0;
    display: none;
  }
  #floatingScrollbarInnerLog {
    overflow-x: auto;
    overflow-y: hidden;
    height: 14px;
  }
  #floatingScrollbarContentLog {
    height: 1px;
  }
</style>
@endsection

@section('content')

<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Data Report /</span> Customer Log History
</h4>

<!-- Table Customer Log -->
<div class="card p-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5>Customer Change History</h5>
    <div>
      <select class="form-select" id="filterCustomer" style="width: 250px;">
        <option value="">Semua Customer</option>
      </select>
    </div>
  </div>
  <div class="table-responsive text-nowrap">
    <table class="table" id="tableCustomerLog">
      <thead>
        <tr>
          <th>ID</th>
          <th class="sticky-col-1">CID</th>
          <th class="sticky-col-2">Customer</th>
          <th>Action</th>
          <th>Field Changed</th>
          <th>Old Value</th>
          <th>New Value</th>
          <th>Changed By</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
      </tbody>
    </table>
  </div>
</div>

<!-- Floating Horizontal Scrollbar -->
<div id="floatingScrollbarWrapLog">
  <div id="floatingScrollbarInnerLog">
    <div id="floatingScrollbarContentLog"></div>
  </div>
</div>

@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
  // Initialize Select2
  $('#filterCustomer').select2({
    placeholder: 'Pilih atau ketik nama customer',
    allowClear: true,
    width: '250px'
  });

  // Load customer list for filter
  $.ajax({
    url: '{{ url("report/customer-log/customers") }}',
    type: 'GET',
    success: function(data) {
      data.forEach(function(customer) {
        $('#filterCustomer').append(
          $('<option></option>').val(customer.cid).text(customer.nama)
        );
      });
      // Trigger change to refresh Select2
      $('#filterCustomer').trigger('change');
    }
  });

  // DataTable
  var table = $('#tableCustomerLog').DataTable({
    processing: true,
    serverSide: true,
    scrollX: true,
    autoWidth: false,
    ajax: {
      url: '{{ url("report/customer-log/show") }}',
      type: 'GET',
      data: function(d) {
        d.customer_cid = $('#filterCustomer').val();
      }
    },
    columns: [
      { data: 'id', name: 'id', width: '60px' },
      { data: 'customer_cid', name: 'customer_cid', width: '100px', className: 'sticky-col-1' },
      { data: 'customer_nama', name: 'customer_nama', width: '160px', className: 'sticky-col-2' },
      {
        data: 'action',
        name: 'action',
        width: '90px',
        render: function(data) {
          if (data === 'created') {
            return '<span class="badge bg-success">Created</span>';
          } else if (data === 'updated') {
            return '<span class="badge bg-warning">Updated</span>';
          } else if (data === 'deleted') {
            return '<span class="badge bg-danger">Deleted</span>';
          }
          return data;
        }
      },
      { data: 'field_changed', name: 'field_changed', width: '130px' },
      {
        data: 'old_value',
        name: 'old_value',
        width: '180px',
        render: function(data) {
          if (data && data.length > 50) {
            return '<span title="' + data + '">' + data.substring(0, 50) + '...</span>';
          }
          return data;
        }
      },
      {
        data: 'new_value',
        name: 'new_value',
        width: '180px',
        render: function(data) {
          if (data && data.length > 50) {
            return '<span title="' + data + '">' + data.substring(0, 50) + '...</span>';
          }
          return data;
        }
      },
      { data: 'changed_by', name: 'changed_by', width: '120px' },
      { data: 'created_at', name: 'created_at', width: '140px' }
    ],
    order: [[0, 'desc']],
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]
  });

  // ── Floating Horizontal Scrollbar (Customer Log) ───────────────
  (function initFloatingScrollbar() {
    const $floatWrap    = $('#floatingScrollbarWrapLog');
    const $floatInner   = $('#floatingScrollbarInnerLog');
    const $floatContent = $('#floatingScrollbarContentLog');

    function getRealScrollEl() {
      return $('#tableCustomerLog').closest('.dataTables_scrollBody').length
        ? $('#tableCustomerLog').closest('.dataTables_scrollBody')
        : $('#tableCustomerLog').closest('.table-responsive');
    }

    function syncFloatingScrollbar() {
      const $scrollEl = getRealScrollEl();
      const scrollWidth = $scrollEl[0] ? $scrollEl[0].scrollWidth : 0;
      const clientWidth = $scrollEl[0] ? $scrollEl[0].clientWidth : 0;
      if (scrollWidth > clientWidth) {
        $floatContent.width(scrollWidth);
        $floatInner.width($scrollEl.outerWidth());
        const offset = $scrollEl.offset();
        $floatWrap.css({ left: offset ? offset.left : 0, width: $scrollEl.outerWidth() });
        const tableBottom = offset ? offset.top + $scrollEl.outerHeight() : 9999;
        const viewBottom  = $(window).scrollTop() + $(window).height();
        if (tableBottom > viewBottom) { $floatWrap.show(); } else { $floatWrap.hide(); }
      } else {
        $floatWrap.hide();
      }
    }

    let syncingFloat = false, syncingReal = false;

    $floatInner.on('scroll', function() {
      if (syncingFloat) return;
      syncingReal = true;
      const $scrollEl = getRealScrollEl();
      if ($scrollEl.length) $scrollEl[0].scrollLeft = this.scrollLeft;
      setTimeout(function() { syncingReal = false; }, 20);
    });

    $(document).on('scroll.floatbarlog', function() {
      const $scrollEl = getRealScrollEl();
      if (!syncingReal && $scrollEl.length) {
        syncingFloat = true;
        $floatInner[0].scrollLeft = $scrollEl[0].scrollLeft;
        setTimeout(function() { syncingFloat = false; }, 20);
      }
      syncFloatingScrollbar();
    });

    $('#tableCustomerLog').on('draw.dt', function() { setTimeout(syncFloatingScrollbar, 100); });
    $(window).on('resize.floatbarlog', syncFloatingScrollbar);
    setTimeout(syncFloatingScrollbar, 500);

    $(document).on('scroll.dtscrolllog', '.dataTables_scrollBody, .table-responsive', function() {
      if (this === getRealScrollEl()[0] && !syncingReal) {
        syncingFloat = true;
        $floatInner[0].scrollLeft = this.scrollLeft;
        setTimeout(function() { syncingFloat = false; }, 20);
      }
    });
  })();
  // ─────────────────────────────────────────────────────────────

  // Filter change event
  $('#filterCustomer').on('change', function() {
    table.ajax.reload();
  });
});
</script>
@endsection
