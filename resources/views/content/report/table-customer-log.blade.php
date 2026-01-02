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
          <th>CID</th>
          <th>Customer</th>
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
    ajax: {
      url: '{{ url("report/customer-log/show") }}',
      type: 'GET',
      data: function(d) {
        d.customer_cid = $('#filterCustomer').val();
      }
    },
    columns: [
      { data: 'id', name: 'id' },
      { data: 'customer_cid', name: 'customer_cid' },
      { data: 'customer_nama', name: 'customer_nama' },
      {
        data: 'action',
        name: 'action',
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
      { data: 'field_changed', name: 'field_changed' },
      {
        data: 'old_value',
        name: 'old_value',
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
        render: function(data) {
          if (data && data.length > 50) {
            return '<span title="' + data + '">' + data.substring(0, 50) + '...</span>';
          }
          return data;
        }
      },
      { data: 'changed_by', name: 'changed_by' },
      { data: 'created_at', name: 'created_at' }
    ],
    order: [[0, 'desc']],
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]
  });

  // Filter change event
  $('#filterCustomer').on('change', function() {
    table.ajax.reload();
  });
});
</script>
@endsection
