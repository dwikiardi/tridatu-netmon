@extends('layouts/contentNavbarLayout')

@section('title', 'Data Leads')

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
    z-index: 9999 !important;
  }

  /* Sticky Columns */
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

  #tableLead {
    border-collapse: separate !important;
    border-spacing: 0;
  }
  #tableLead th, #tableLead td {
    border-bottom: 1px solid #dee2e6 !important;
    padding-right: 25px !important;
  }

  #tableLead_wrapper .dataTables_scrollHead {
    position: sticky;
    top: 0;
    z-index: 4;
    background: #fff;
  }
</style>
@endsection

@section('content')

<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Data Leads /</span> Table Leads
</h4>

<div class="card p-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5>Data Leads (Prospek)</h5>
  </div>
  <div class="table-responsive text-nowrap">
    <table class="table" id="tableLead">
      <thead>
        <tr>
          <th class="sticky-col-1">ID Lead</th>
          <th class="sticky-col-2">Nama</th>
          <th>Telepon</th>
          <th>Alamat</th>
          <th>Sales</th>
          <th>Tipe Survey</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Detail Lead -->
<div class="modal fade" id="modalDetailLead" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Lead & History Ticket</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <hr class="my-0">
      <div class="modal-body">
        <div class="row">
          <!-- Kolom Kiri: Profil Lead -->
          <div class="col-lg-5 border-end">
            <h6 class="fw-bold mb-3"><i class="bx bx-user me-1"></i> Lead Profile</h6>
            <div id="leadProfileContent">
              <table class="table table-sm table-borderless">
                <tr><th width="35%">ID Survey</th><td>: <span id="det-survey-id" class="fw-bold text-primary"></span></td></tr>
                <tr><th>Nama</th><td>: <span id="det-nama"></span></td></tr>
                <tr><th>Telepon</th><td>: <span id="det-telepon"></span></td></tr>
                <tr><th>Alamat</th><td>: <span id="det-alamat"></span></td></tr>
                <tr><th>Koordinat</th><td>: <span id="det-koordinat"></span></td></tr>
                <tr><th>Sales</th><td>: <span id="det-sales"></span></td></tr>
                <tr><th>Status</th><td>: <span id="det-status" class="badge bg-label-info"></span></td></tr>
                <tr><th>Tipe</th><td>: <span id="det-tipe" class="badge bg-label-secondary"></span></td></tr>
              </table>
              <div class="mt-4">
                <a href="" id="det-maps-link" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bx bx-map-alt me-1"></i> Lihat di Maps</a>
              </div>
            </div>
          </div>
          
          <!-- Kolom Kanan: History Ticket -->
          <div class="col-lg-7">
            <h6 class="fw-bold mb-3"><i class="bx bx-receipt me-1"></i> Ticket History</h6>
            <div class="table-responsive">
              <table class="table table-sm table-hover" id="tableHistory">
                <thead>
                  <tr>
                    <th>No Ticket</th>
                    <th>Jenis</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                  </tr>
                </thead>
                <tbody id="historyContent">
                  <tr><td colspan="4" class="text-center">Loading history...</td></tr>
                </tbody>
              </table>
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

@section('page-script')
<script>
$(document).ready(function() {
  const table = $('#tableLead').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ url("datalead/data") }}',
    columns: [
      { data: 'survey_id', className: 'sticky-col-1 fw-bold' },
      { data: 'nama', className: 'sticky-col-2' },
      { data: 'telepon' },
      { data: 'alamat' },
      { data: 'sales' },
      { 
        data: 'tipe_survey',
        render: function(data) {
          let badge = data === 'project' ? 'bg-label-warning' : 'bg-label-secondary';
          let label = data === 'normal' ? 'New Cust' : data;
          return `<span class="badge ${badge}">${label.toUpperCase()}</span>`;
        }
      },
      { 
        data: 'status',
        render: function(data) {
          return `<span class="badge bg-label-info">${data.toUpperCase()}</span>`;
        }
      },
      {
        data: 'id',
        render: function(data) {
          return `
            <div class="dropdown">
              <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button>
              <div class="dropdown-menu">
                <a class="dropdown-item btn-detail" href="javascript:void(0);" data-id="${data}"><i class="bx bx-show-alt me-1"></i> Detail & History</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item btn-delete-lead text-danger" href="javascript:void(0);" data-id="${data}"><i class="bx bx-trash me-1"></i> Hapus Lead</a>
              </div>
            </div>
          `;
        }
      }
    ],
    order: [[0, 'desc']],
    scrollX: true,
    language: {
      searchPlaceholder: "Cari Lead..."
    }
  });

  $(document).on('click', '.btn-detail', function() {
    const id = $(this).data('id');
    $('#modalDetailLead').modal('show');
    $('#historyContent').html('<tr><td colspan="4" class="text-center"><div class="spinner-border spinner-border-sm text-primary"></div> Loading...</td></tr>');

    $.ajax({
      url: '{{ url("datalead/detail") }}',
      data: { id: id },
      success: function(res) {
        $('#det-survey-id').text(res.survey_id);
        $('#det-nama').text(res.nama);
        $('#det-telepon').text(res.telepon || '-');
        $('#det-alamat').text(res.alamat);
        $('#det-koordinat').text(res.koordinat);
        $('#det-sales').text(res.sales);
        $('#det-status').text(res.status.toUpperCase());
        let tipeLabel = res.tipe_survey === 'normal' ? 'New Cust' : res.tipe_survey;
        $('#det-tipe').text(tipeLabel.toUpperCase());

        if (res.koordinat) {
          let mapsUrl = res.koordinat.startsWith('http') ? res.koordinat : `https://www.google.com/maps?q=${encodeURIComponent(res.koordinat)}`;
          $('#det-maps-link').attr('href', mapsUrl).show();
        } else {
          $('#det-maps-link').hide();
        }

        let html = '';
        if (res.tickets.length > 0) {
          res.tickets.forEach(t => {
            let badge = 'bg-label-secondary';
            if(t.status === 'selesai') badge = 'bg-label-success';
            if(t.status === 'on progress') badge = 'bg-label-info';
            
            html += `
              <tr>
                <td><span class="fw-bold">${t.ticket_no}</span></td>
                <td>${t.jenis.toUpperCase()}</td>
                <td><span class="badge ${badge}">${t.status.toUpperCase()}</span></td>
                <td>${t.created_at}</td>
              </tr>
              <tr>
                <td colspan="4" class="small text-muted border-bottom mb-2">Note: ${t.kendala}</td>
              </tr>
            `;
          });
        } else {
          html = '<tr><td colspan="4" class="text-center">No ticket history found</td></tr>';
        }
        $('#historyContent').html(html);
      }
    });
  });

  // Delete Lead Handler
  $(document).on('click', '.btn-delete-lead', function() {
    const id = $(this).data('id');
    if (confirm('Apakah Anda yakin ingin menghapus lead ini? Semua histori ticket terkait (survey, maintenance, dll) akan ikut terhapus permanen.')) {
      $.ajax({
        url: '{{ url("datalead/delete") }}',
        type: 'DELETE',
        data: { id: id },
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
          table.ajax.reload();
          alert(response.message);
        },
        error: function() {
          alert('Terjadi kesalahan saat menghapus data.');
        }
      });
    }
  });
});
</script>
@endsection
@endsection
