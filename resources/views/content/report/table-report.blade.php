@extends('layouts/contentNavbarLayout')

@section('title', 'Report')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Page Title -->
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <h5>Report</h5>
        </div>
        <div class="card-body">
          <!-- Nav tabs -->
          <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
              <button class="nav-link active" id="tab-customer-report" data-bs-toggle="tab" data-bs-target="#content-customer-report" type="button" role="tab">
                <i class='bx bxs-user-detail'></i> Report Data Customer
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link" id="tab-maintenance-report" data-bs-toggle="tab" data-bs-target="#content-maintenance-report" type="button" role="tab">
                <i class='bx bx-wrench'></i> Report Maintenance
              </button>
            </li>
          </ul>

          <!-- Tab contents -->
          <div class="tab-content">
            <!-- Report Data Customer Tab -->
            <div class="tab-pane fade show active" id="content-customer-report" role="tabpanel">
              @include('content.report.customer-report')
            </div>

            <!-- Report Maintenance Tab -->
            <div class="tab-pane fade" id="content-maintenance-report" role="tabpanel">
              @include('content.report.maintenance-report')
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@include('content.report.report-scripts')
