@extends('layouts/contentNavbarLayout')

@section('title', 'Tables - Basic Tables')

@section('content')

{{-- @section('page-script')
<script src="{{asset('assets/js/ui-modals.js')}}"></script>
@endsection --}}

<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Olt Monitoring /</span> Table OLT
</h4>

<!-- Basic Bootstrap Table -->
<div class="card p-4">
  <h5 class="card-header">Table Basic</h5>
  <div class="table-responsive text-nowrap">
    <table class="table" id="myTable">
      <thead>
        <tr>
          <th>Description</th>
          <th>Pon</th>
          <th>ONU Tx</th>
          <th>ONU Rx</th>
          <th>Last Online</th>
          <th>Last Offline</th>
          <th>Reason</th>
          <th>POP</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
      </tbody>
    </table>
  </div>
</div>
<!--/ Basic Bootstrap Table -->
<!-- Modal -->
<div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <!-- Garis pembatas -->
      <hr class="my-0">
      <div class="modal-body">
        <div class="row">
          <p id="snmpData"></p> <!-- Tempat menampilkan hasil data SNMP -->
        </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        {{-- <button type="button" class="btn btn-primary">Save changes</button> --}}
      </div>
    </div>
  </div>
</div>
</div>
</div>
@endsection
