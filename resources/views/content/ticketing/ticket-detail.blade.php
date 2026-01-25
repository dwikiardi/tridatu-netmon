@extends('layouts/contentNavbarLayout')

@section('title', 'Detail Ticket')

@section('content')

<?php
// Generate ticket number: TDN-DDMMYY-HHMM-NO
$tanggal = $ticket->tanggal_kunjungan ? $ticket->tanggal_kunjungan->format('dmY') : date('dmY');
$jam = $ticket->jam ? date('Hi', strtotime($ticket->jam)) : date('Hi');
$no = str_pad($ticket->id, 3, '0', STR_PAD_LEFT);
$ticketNo = "TDN-{$tanggal}-{$jam}-{$no}";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="py-3 mb-0">
    <span class="text-muted fw-light">Ticketing /</span> Detail Ticket {{ $ticketNo }}
  </h4>
</div>

<div class="mb-3 d-flex justify-content-between align-items-center">
  <div class="d-flex gap-2">
    <a href="{{ route('view-ticketing') }}" class="btn btn-secondary">
      <i class="bx bx-arrow-back me-1"></i> Kembali ke Ticketing
    </a>
  </div>

  <div class="d-flex gap-2">
    @if($ticket->jenis === 'maintenance')
    <!-- Show RFO Button (Always visible) -->
    <button type="button" class="btn btn-info" id="btnShowRfo">
      <i class="bx bx-show me-1"></i> Show RFO
    </button>

    <!-- Edit RFO Button (Visible if not closed) -->
    @if($ticket->status !== 'closed')
    <button type="button" class="btn btn-primary" id="btnEditRfo">
      <i class="bx bx-edit me-1"></i> Edit RFO
    </button>
    @endif
    @endif
  </div>
</div>

<div class="row">
  <!-- Left Column: Ticket Info -->
  <div class="col-md-8">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Ticket Information</h5>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-6">
            <h6 class="text-muted mb-2">Ticket No</h6>
            <p class="mb-0"><strong>{{ $ticketNo }}</strong></p>
          </div>
          <div class="col-md-6">
            <h6 class="text-muted mb-2">Status</h6>
            <p class="mb-0">
              @php
                $status = $ticket->status;
                $statusLabel = $status === 'open'
                  ? 'OPEN'
                  : ($status === 'need visit'
                    ? 'PERLU KUNJUNGAN'
                    : ($status === 'on progress'
                      ? 'ON PROGRESS'
                      : ($status === 'pending'
                        ? 'PENDING'
                        : strtoupper($status))));
                $statusColor = $status === 'open'
                  ? 'primary'
                  : ($status === 'need visit'
                    ? 'warning'
                    : ($status === 'on progress'
                      ? 'info'
                      : ($status === 'pending'
                        ? 'secondary'
                        : 'success')));
              @endphp
              <span id="ticketInfoStatusBadge" class="badge bg-{{ $statusColor }}">{{ strtoupper($statusLabel) }}</span>
            </p>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <h6 class="text-muted mb-2">Customer (CID)</h6>
            <p class="mb-0"><strong>{{ $ticket->cid ?? 'TDNSurvey' }}</strong></p>
          </div>
          <div class="col-md-6">
            <h6 class="text-muted mb-2">Customer Name</h6>
            <p class="mb-0">
              <strong>
                {{ $ticket->customer ? $ticket->customer->nama : ($ticket->calonCustomer ? $ticket->calonCustomer->nama : '-') }}
              </strong>
            </p>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <h6 class="text-muted mb-2">Priority</h6>
            <p class="mb-0">
              <span class="badge bg-{{ $ticket->priority === 'urgent' ? 'danger' : ($ticket->priority === 'high' ? 'warning' : 'info') }}">
                {{ ucfirst($ticket->priority) }}
              </span>
            </p>
          </div>
          <div class="col-md-6">
            <h6 class="text-muted mb-2">Type</h6>
            <p class="mb-0"><strong>{{ ucfirst($ticket->jenis) }}</strong></p>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <h6 class="text-muted mb-2">Method</h6>
            <p class="mb-0"><strong>{{ ucfirst($ticket->metode_penanganan) }}</strong></p>
          </div>
          <div class="col-md-6">
            <h6 class="text-muted mb-2">Created By</h6>
            <p class="mb-0">
              <strong>{{ $ticket->creator ? $ticket->creator->name : 'Admin' }}</strong>
              <small class="text-muted">({{ $ticket->created_by_role ?? 'admin' }})</small>
            </p>
          </div>
        </div>

        @if(in_array($ticket->status, ['selesai']))
        <div class="row mb-3">
          @if($ticket->jenis === 'maintenance')
          <div class="col-md-4">
            <h6 class="text-muted mb-2">MTTR Response</h6>
            <p class="mb-0"><strong>{{ $ticket->sla_remote_minutes !== null ? ($ticket->sla_remote_formatted ?: '0m') : '-' }}</strong></p>
          </div>
          <div class="col-md-4">
            <h6 class="text-muted mb-2">MTTR Resolve</h6>
            <p class="mb-0"><strong>{{ $ticket->sla_onsite_minutes !== null ? ($ticket->sla_onsite_formatted ?: '0m') : '-' }}</strong></p>
          </div>
          <div class="col-md-4">
            <h6 class="text-muted mb-2">Downtime</h6>
            <p class="mb-0"><strong>{{ $ticket->sla_total_minutes !== null ? ($ticket->sla_total_formatted ?: '0m') : '-' }}</strong></p>
          </div>
          @else
          <div class="col-md-12">
            <h6 class="text-muted mb-2">MTTR Resolve</h6>
            <p class="mb-0"><strong>{{ $ticket->sla_onsite_minutes !== null ? ($ticket->sla_onsite_formatted ?: '0m') : '-' }}</strong></p>
          </div>
          @endif
        </div>

        <hr>
        @endif

        <div class="row mb-3">
          <div class="col-12">
            <h6 class="text-muted mb-2">
              @if($ticket->jenis === 'maintenance')
                Problem / Kendala
              @elseif($ticket->jenis === 'survey')
                Hasil Survey / Update Terakhir
              @else
                Detail Update Terakhir
              @endif
            </h6>
            <p class="mb-0">{{ $ticket->kendala }}</p>
          </div>
        </div>

        @if($ticket->solusi && $ticket->jenis === 'maintenance')
        <div class="row mb-3">
          <div class="col-12">
            <h6 class="text-muted mb-2">Solution / Solusi</h6>
            <p class="mb-0">{{ $ticket->solusi }}</p>
          </div>
        </div>
        @endif

        @if($ticket->hasil)
        <div class="row mb-3">
          <div class="col-12">
            <h6 class="text-muted mb-2">Result / Hasil</h6>
            <p class="mb-0">{{ $ticket->hasil }}</p>
          </div>
        </div>
        @endif
      </div>
    </div>

    <!-- Replies Section (Forum) -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Ticket Updates / Forum</h5>
        <div>
          <small class="text-muted me-3" id="replyCount">0 updates</small>
          @if($ticket->status !== 'selesai')
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddUpdate">
            <i class="bx bx-plus"></i> Add Update
          </button>
          @else
          <span class="text-muted">Ticket selesai - update disabled</span>
          @endif
        </div>
      </div>
      <div class="card-body" id="repliesContainer" style="max-height: 500px; overflow-y: auto;">
        <div class="text-center text-muted py-4">
          <small>No updates yet...</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Column: Additional Info -->
  <div class="col-md-4">
    <!-- Current Penangani (if on progress) -->
    @if($ticket->status === 'on progress' || $ticket->status === 'on_progress')
    <div class="card mb-4" style="border-left: 4px solid #4099ff;">
      <div class="card-header bg-light">
        <h5 class="mb-0" style="color: #4099ff;"><i class="bx bx-user-check me-2"></i>Handle By</h5>
      </div>
      <div class="card-body" id="currentTeknisiContainer">
        <div class="text-center text-muted py-2">
          <small><i class="bx bx-loader-alt bx-spin"></i> Loading...</small>
        </div>
      </div>
    </div>
    @endif

    <!-- Summary Daftar Update Ticket -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bx bx-group me-2"></i>History Teknisi yang Berkunjung</h5>
      </div>
      <div class="card-body" id="teknisiContainer" style="max-height: 300px; overflow-y: auto;">
        <div class="text-center text-muted py-3">
          <small><i class="bx bx-loader-alt bx-spin"></i> Loading...</small>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Customer Details</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <h6 class="text-muted mb-2">Contact</h6>
          <p class="mb-0">
            {{ $ticket->calonCustomer ? $ticket->calonCustomer->telepon : ($ticket->customer ? $ticket->customer->pic_it : '-') }}
          </p>
        </div>
        <div class="mb-3">
          <h6 class="text-muted mb-2">Address</h6>
          <p class="mb-0" style="font-size: 0.9rem;">
            {{ $ticket->calonCustomer ? $ticket->calonCustomer->alamat : ($ticket->customer ? $ticket->customer->alamat : '-') }}
          </p>
        </div>

        <div class="mb-3">
          <h6 class="text-muted mb-2">Coordinate</h6>
          <p class="mb-0" style="font-size: 0.9rem;">
            @php
              $coordinate = $ticket->calonCustomer ? ($ticket->calonCustomer->koordinat ?? '-') : ($ticket->customer ? ($ticket->customer->coordinate_maps ?? '-') : '-');
            @endphp
            @if($coordinate !== '-' && $coordinate)
              <a href="{{ $coordinate }}" target="_blank" class="text-primary">
                <i class="bx bx-map me-1"></i>{{ $coordinate }}
              </a>
            @else
              -
            @endif
          </p>
        </div>

        @if($ticket->jenis === 'survey' && $ticket->calonCustomer)
        <hr>
        <div class="mb-3">
          <h6 class="text-muted mb-2">PIC di Lokasi</h6>
          <p class="mb-0">
            {{ $ticket->pic_it_lokasi ?? '-' }}
          </p>
        </div>
        <div class="mb-3">
          <h6 class="text-muted mb-2">Koordinat</h6>
          <p class="mb-0" style="font-size: 0.9rem;">
            {{ $ticket->calonCustomer->koordinat ?? '-' }}
          </p>
        </div>
        <div class="mb-3">
          <h6 class="text-muted mb-2">Calon Customer Punya</h6>
          <p class="mb-0">
            <strong>{{ $ticket->calonCustomer->sales ? $ticket->calonCustomer->sales->name : '-' }}</strong>
            <small class="text-muted">(Sales)</small>
          </p>
        </div>
        @endif
      </div>
    </div>

    <!-- Timeline & History -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Timeline & History</h5>
      </div>
      <div class="card-body" style="max-height: 500px; overflow-y: auto;">
        <div class="timeline" id="timelineContainer">
          <div class="text-center">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
  </div>
</div>

<!-- Modal Update RFO -->
<div class="modal fade" id="modalUpdateRfo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Data RFO</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="rfoForm">
          @csrf
          <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
          
          <!-- Read Only Stats -->
          <div class="row mb-3">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">No Ticket</label>
                <input type="text" class="form-control" value="{{ $ticketNo }}" disabled>
              </div>
              @php
                  // Calculate timestamps dynamically for display
                  // Use first resolution reply for 'Done Time' (End of Downtime)
                  $firstResolve = $ticket->replies->filter(function($r) {
                      return in_array($r->update_status, ['done', 'remote_done', 'selesai']);
                  })->first();
                  
                  // Downtime Start: Time of 'need_visit' (Remote finished) or Ticket Created if onsite/direct
                  $needVisit = $ticket->replies->where('update_status', 'need_visit')->first();
                  $downtimeStart = $ticket->created_at;
                  
                  if ($needVisit) {
                      if ($needVisit->tanggal_kunjungan && $needVisit->jam_kunjungan) {
                          // Handle tanggal_kunjungan as string or object
                          $dateStr = ($needVisit->tanggal_kunjungan instanceof \DateTimeInterface) 
                                      ? $needVisit->tanggal_kunjungan->format('Y-m-d') 
                                      : substr((string)$needVisit->tanggal_kunjungan, 0, 10);
                          $timeStr = $needVisit->jam_kunjungan;
                          try {
                             $downtimeStart = \Carbon\Carbon::parse($dateStr . ' ' . $timeStr);
                          } catch(\Exception $e) {
                             // Retry without seconds if failed
                             try {
                                $downtimeStart = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $dateStr . ' ' . substr($timeStr, 0, 5));
                             } catch(\Exception $ex) {
                                $downtimeStart = $needVisit->created_at;
                             }
                          }
                      } else {
                          $downtimeStart = $needVisit->created_at;
                      }
                  }

                  // Formats
                  $downtimeStartDate = $downtimeStart->format('d-m-Y H:i');
                  $uptime = $firstResolve ? $firstResolve->created_at->format('d-m-Y H:i') : '-'; 
                  $downtime = $ticket->sla_total_minutes ? $ticket->sla_total_formatted : '0 menit';
              @endphp
              <div class="mb-3">
                <label class="form-label">Tgl Down Time (Start)</label>
                <input type="text" class="form-control" value="{{ $downtimeStartDate }}" disabled>
              </div>
            </div>
            <div class="col-md-6">
               <div class="mb-3">
                <label class="form-label">Tgl Uptime (End)</label>
                <input type="text" class="form-control" value="{{ $uptime }}" disabled>
              </div>
              <div class="mb-3">
                <label class="form-label">Durasi Downtime (Auto)</label>
                <input type="text" class="form-control" value="{{ $downtime }}" disabled>
              </div>
            </div>
          </div>
          
          <hr>
          
          <!-- Editable Fields -->
          <div class="mb-3">
            <label class="form-label">Problem Type</label>
            <select class="form-select" name="indikasi">
              <option value="">Pilih Problem Type</option>
              <option value="Internet Down / Total Loss" {{ $ticket->indikasi == 'Internet Down / Total Loss' ? 'selected' : '' }}>Internet Down / Total Loss</option>
              <option value="Internet Partially Down" {{ $ticket->indikasi == 'Internet Partially Down' ? 'selected' : '' }}>Internet Partially Down</option>
              <option value="Internet Slow / Degraded Performance" {{ $ticket->indikasi == 'Internet Slow / Degraded Performance' ? 'selected' : '' }}>Internet Slow / Degraded Performance</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Masalah (Kendala)</label>
            <textarea class="form-control" name="masalah" rows="2" placeholder="Masukkan masalah/kendala...">{{ $ticket->kendala }}</textarea>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Solusi</label>
            <textarea class="form-control" name="solusi" rows="3" placeholder="Masukkan solusi...">{{ $ticket->solusi }}</textarea>
          </div>
          
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <a href="{{ route('export-rfo-single', $ticket->id) }}" target="_blank" class="btn btn-danger" id="btnExportRfoPdf" style="display: none;">
          <i class="bx bxs-file-pdf"></i> Export PDF
        </a>
        <button type="button" class="btn btn-primary" id="saveRfoBtn"><i class="bx bx-save"></i> Save Changes</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Add Update -->
<div class="modal fade" id="modalAddUpdate" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Ticket Update</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <hr class="my-0">
      <div class="modal-body">
        <form id="replyForm">
          <!-- Update Date Flexibility -->
          <div class="mb-3">
             <div class="form-check">
               <input class="form-check-input" type="checkbox" id="reply_is_created_today" name="is_created_today" checked>
               <label class="form-check-label" for="reply_is_created_today">
                 Update Dibuat Hari Ini
               </label>
             </div>
          </div>
          <div class="mb-3" id="replyCustomDateRow" style="display: none;">
             <label for="reply_custom_created_at" class="form-label">Waktu Update</label>
             <input type="datetime-local" class="form-control" id="reply_custom_created_at" name="custom_created_at">
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="ticketPriority" class="form-label">Priority</label>
                <select class="form-control" id="ticketPriority" name="priority">
                  <option value="">Pilih Priority</option>
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="ticketJenis" class="form-label">Jenis Ticket</label>
                <input type="text" class="form-control" id="ticketJenis" name="jenis" value="{{ ucfirst($ticket->jenis) }}" readonly>
                <small class="text-muted">Sudah ditentukan saat pembuatan ticket</small>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="ticketMetode" class="form-label">Metode Penanganan</label>
                <select class="form-control" id="ticketMetode" name="metode_penanganan">
                  <option value="">Pilih Metode</option>
                  <option value="onsite">Onsite</option>
                  @if(!$hasOnsiteHistory && $ticket->jenis !== 'survey' && $ticket->jenis !== 'installasi')
                  <option value="remote">Remote</option>
                  @endif
                </select>
                @if($hasOnsiteHistory)
                <small class="text-muted">Remote tidak tersedia karena sudah pernah onsite</small>
                @elseif($ticket->jenis === 'survey')
                <small class="text-muted">Survey hanya bisa dilakukan onsite</small>
                @elseif($ticket->jenis === 'installasi')
                <small class="text-muted">Installasi hanya bisa dilakukan onsite</small>
                @endif
              </div>
            </div>
          </div>

          <!-- Schedule fields - hidden for remote -->
          <div id="scheduleFields" style="display:none;">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="ticketTanggal" class="form-label">Tanggal Kunjungan <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="ticketTanggal" name="tanggal_kunjungan">
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="ticketJam" class="form-label">Jam Kunjungan <span class="text-danger">*</span></label>
                  <input type="time" class="form-control" id="ticketJam" name="jam">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="ticketHari" class="form-label">Hari</label>
                  <input type="text" class="form-control" id="ticketHari" name="hari" placeholder="Auto-fill dari tanggal" readonly>
                </div>
              </div>
              <div class="col-md-6" id="teknisiFieldGroup">
                <div class="mb-3">
                  <label for="ticketTeknisi" class="form-label">Teknisi yang Berkunjung <span class="text-danger">*</span></label>
                  <div class="d-flex align-items-start gap-2">
                    <select class="form-control" id="ticketTeknisi" name="ticket_teknisi_picker">
                      <option value="">Pilih Teknisi</option>
                    </select>
                    <button type="button" class="btn btn-outline-primary" id="addTeknisiBtn">Tambah</button>
                  </div>
                  <div id="teknisiBadges" class="mt-2 d-flex flex-wrap gap-2"></div>
                  <small class="text-muted">Klik tambah untuk memasukkan teknisi, klik badge untuk menghapus.</small>
                </div>
              </div>
            </div>
          </div>

          <hr>

          <div class="mb-3" id="statusSection" style="display:none;">
            <label class="form-label">Update Status <span class="text-danger">*</span></label>
            <div class="btn-group w-100" role="group" id="statusOptions">
              @if($ticket->jenis === 'survey')
                {{-- Survey: Pending, Perlu Kunjungan, On Progress, dan Selesai --}}
                <input type="radio" class="btn-check" name="update_status" id="statusPending" value="pending" checked>
                <label class="btn btn-outline-secondary" for="statusPending">Pending</label>

                <input type="radio" class="btn-check" name="update_status" id="statusNeedVisitSurvey" value="need_visit">
                <label class="btn btn-outline-warning" for="statusNeedVisitSurvey">Perlu Kunjungan</label>

                <input type="radio" class="btn-check" name="update_status" id="statusOnProgress" value="on_progress">
                <label class="btn btn-outline-info" for="statusOnProgress">On Progress</label>

                <input type="radio" class="btn-check" name="update_status" id="statusDone" value="done">
                <label class="btn btn-outline-success" for="statusDone">Selesai</label>
              @elseif($ticket->jenis === 'installasi')
                {{-- Installasi: On Progress, Pending, Selesai --}}
                <input type="radio" class="btn-check" name="update_status" id="statusOnProgress" value="on_progress" checked>
                <label class="btn btn-outline-info" for="statusOnProgress">On Progress</label>

                <input type="radio" class="btn-check" name="update_status" id="statusNeedVisitInstall" value="need_visit">
                <label class="btn btn-outline-warning" for="statusNeedVisitInstall">Perlu Kunjungan</label>

                <input type="radio" class="btn-check" name="update_status" id="statusPending" value="pending">
                <label class="btn btn-outline-secondary" for="statusPending">Pending</label>

                <input type="radio" class="btn-check" name="update_status" id="statusDone" value="done">
                <label class="btn btn-outline-success" for="statusDone">Selesai</label>
              @elseif($ticket->jenis === 'maintenance')
                {{-- Maintenance: semua status --}}
                {{-- Remote options --}}
                <input type="radio" class="btn-check status-remote" name="update_status" id="statusOnCheckRemote" value="on_progress">
                <label class="btn btn-outline-info status-remote" for="statusOnCheckRemote">On Check</label>

                <input type="radio" class="btn-check status-remote" name="update_status" id="statusNeedVisitRemote" value="need_visit">
                <label class="btn btn-outline-warning status-remote" for="statusNeedVisitRemote">Perlu Kunjungan</label>

                <input type="radio" class="btn-check status-remote" name="update_status" id="statusDoneRemote" value="done">
                <label class="btn btn-outline-success status-remote" for="statusDoneRemote">Selesai</label>

                {{-- Onsite options --}}
                <input type="radio" class="btn-check status-onsite" name="update_status" id="statusOnProgressOnsite" value="on_progress" @if($hasOnsiteHistory) checked @endif>
                <label class="btn btn-outline-info status-onsite" for="statusOnProgressOnsite">On Progress Kunjungan</label>

                <input type="radio" class="btn-check status-onsite" name="update_status" id="statusNeedVisitOnsite" value="need_visit">
                <label class="btn btn-outline-warning status-onsite" for="statusNeedVisitOnsite">Perlu Kunjungan</label>

                <input type="radio" class="btn-check status-onsite" name="update_status" id="statusPendingOnsite" value="pending">
                <label class="btn btn-outline-secondary status-onsite" for="statusPendingOnsite">Pending</label>

                <input type="radio" class="btn-check status-onsite" name="update_status" id="statusDoneOnsite" value="done">
                <label class="btn btn-outline-success status-onsite" for="statusDoneOnsite">Selesai</label>
              @endif
            </div>
            <small class="text-muted d-block mt-2">
              @if($ticket->jenis === 'survey')
              Survey memiliki status: Pending (menunggu), On Progress (sedang dikerjakan), atau Selesai
              @elseif($ticket->jenis === 'installasi')
              Installasi memiliki status: On Progress (sedang dikerjakan), Pending (menunggu alat/bahan), Perlu Kunjungan (Jadwal Ulang), atau Selesai
              @elseif($hasOnsiteHistory)
              Pending = menunggu alat/bahan. Perlu Kunjungan = Jadwal datang kembali.
              @else
              Pilih tipe update untuk status ticket
              @endif
            </small>
          </div>

          <div class="mb-3">
            <label for="replyInput" class="form-label">Catatan / Komentar <span class="text-danger">*</span></label>
            <textarea
              class="form-control"
              id="replyInput"
              placeholder="Tambahkan catatan atau komentar update..."
              rows="4"
              required></textarea>
            <small class="text-muted d-block mt-2">Jelaskan secara detail status dan progress ticket</small>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="submitReplyBtn">
          <i class="bx bx-send"></i> Send Update
        </button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('page-script')
<script>
$(document).ready(function() {
  const ticketId = {{ $ticket->id }};
  const ticketJenis = '{{ $ticket->jenis }}';
  const ticketMetodeInitial = '{{ $ticket->metode_penanganan }}';
  const ticketCreatedDateStr = '{{ $ticket->created_at->format('Y-m-d') }}';
  const ticketCreatedTimeStr = '{{ $ticket->created_at->format('H:i') }}';
  let latestUpdateDateTime = new Date(ticketCreatedDateStr + 'T' + ticketCreatedTimeStr);
  let teknisiSelections = [];

  function setMinScheduleDate() {
    const minDate = latestUpdateDateTime;
    const y = minDate.getFullYear();
    const m = String(minDate.getMonth() + 1).padStart(2, '0');
    const d = String(minDate.getDate()).padStart(2, '0');
    $('#ticketTanggal').attr('min', `${y}-${m}-${d}`);
  }

  function updateScheduleFieldsVisibility() {
    const metode = $('#ticketMetode').val();

    // Survey, Maintenance, dan Installasi onsite butuh schedule fields
    if (ticketJenis === 'survey' || ticketJenis === 'maintenance' || ticketJenis === 'installasi') {
      if (metode === 'onsite') {
        $('#scheduleFields').show();
        $('#ticketTanggal').prop('required', true);
        $('#ticketJam').prop('required', true);
        $('#ticketTeknisi').prop('required', true);
        updateStatusOptions('onsite');
        $('#statusSection').show();
      } else if (metode === 'remote') {
        $('#scheduleFields').hide();
        $('#ticketTanggal').prop('required', false);
        $('#ticketJam').prop('required', false);
        $('#ticketTeknisi').prop('required', false);
        updateStatusOptions('remote');
        $('#statusSection').show();
      } else {
        $('#scheduleFields').hide();
        updateStatusOptions('default');
        $('#statusSection').hide();
      }
    } else {
      $('#scheduleFields').hide();
      updateStatusOptions('default');
      $('#statusSection').hide();
    }
  }

  // Load teknisi list for onsite
  $.ajax({
    url: '{{ route("get-ticketing-teknisi") }}',
    type: 'GET',
    success: function(data) {
      data.forEach(function(teknisi) {
        $('#ticketTeknisi').append(
          $('<option></option>')
            .val(teknisi.id)
            .text(teknisi.name)
        );
      });
    }
  });

  function renderTeknisiBadges() {
    const container = $('#teknisiBadges');
    if (teknisiSelections.length === 0) {
      container.html('<span class="text-muted small">Belum ada teknisi dipilih</span>');
      return;
    }
    const html = teknisiSelections.map(function(t) {
      return `<span class="badge bg-primary" data-id="${t.id}" style="cursor:pointer;">${t.name} <i class="bx bx-x"></i></span>`;
    }).join(' ');
    container.html(html);
    container.find('span').on('click', function() {
      const id = $(this).data('id');
      teknisiSelections = teknisiSelections.filter(t => t.id !== id);
      renderTeknisiBadges();
    });
  }

  $('#addTeknisiBtn').on('click', function() {
    const selectedId = $('#ticketTeknisi').val();
    const selectedName = $('#ticketTeknisi option:selected').text();
    if (!selectedId) {
      alert('Pilih teknisi terlebih dahulu');
      return;
    }
    const exists = teknisiSelections.some(t => t.id == selectedId);
    if (!exists) {
      teknisiSelections.push({ id: parseInt(selectedId, 10), name: selectedName });
      renderTeknisiBadges();
    }
  });

  function clearTeknisiSelection() {
    teknisiSelections = [];
    renderTeknisiBadges();
  }

  // Initial call on page load
  updateScheduleFieldsVisibility();
  // Also ensure status visibility consistent with initial metode
  const initialMetode = $('#ticketMetode').val();
  updateStatusOptions(initialMetode || 'default');
  if (initialMetode) {
    $('#statusSection').show();
  } else {
    $('#statusSection').hide();
  }

  // Handle metode change with trigger
  $('#ticketMetode').on('change', function() {
    updateScheduleFieldsVisibility();
  });

  // Handle modal show
  $('#modalAddUpdate').on('show.bs.modal', function() {
    updateScheduleFieldsVisibility();
    // Prefill priority with the current ticket priority
    $('#ticketPriority').val('{{ $ticket->priority ?? '' }}');
    // Apply min allowable date for schedule
    setMinScheduleDate();
  });

  // Handle Reply Created Today toggle
  $('#reply_is_created_today').on('change', function() {
    if ($(this).is(':checked')) {
      $('#replyCustomDateRow').slideUp();
      $('#reply_custom_created_at').prop('required', false);
    } else {
      $('#replyCustomDateRow').slideDown();
      $('#reply_custom_created_at').prop('required', true);
    }
  });

  // Handle status change - jangan show schedule fields untuk Pending
  $('input[name="update_status"]').on('change', function() {
    const selectedStatus = $(this).val();
    const metode = $('#ticketMetode').val();
    if (selectedStatus === 'pending') {
      // Pending bisa input schedule (optional) untuk akurasi SLA
      $('#scheduleFields').show();
      $('#ticketTanggal').prop('required', false); // Optional untuk pending
      $('#ticketJam').prop('required', false); // Optional untuk pending
      $('#ticketTeknisi').prop('required', false);
      $('#teknisiFieldGroup').hide();
      // Tidak bersihkan nilai agar teknisi bisa isi
      $('#ticketTeknisi').val('');
      clearTeknisiSelection();
    } else {
      // Untuk status lain, ikuti logic metode tanpa mereset pilihan status
      if (metode === 'onsite') {
        $('#scheduleFields').show();
        $('#ticketTanggal').prop('required', true);
        $('#ticketJam').prop('required', true);
        // Teknisi hanya wajib untuk On Progress Kunjungan
        const isOnProgress = selectedStatus === 'on_progress';
        $('#ticketTeknisi').prop('required', isOnProgress);
        // Hide teknisi field untuk Selesai dan Perlu Kunjungan
        if (selectedStatus === 'done' || selectedStatus === 'need_visit') {
          $('#teknisiFieldGroup').hide();
          // Bersihkan pilihan teknisi saat Perlu Kunjungan
          if (selectedStatus === 'need_visit') {
            $('#ticketTeknisi').val('');
            clearTeknisiSelection();
          }
        } else {
          $('#teknisiFieldGroup').show();
        }
      } else {
        // Khusus: jika metode remote dan pilih Perlu Kunjungan,
        // wajib isi jadwal kunjungan (akan dikonversi menjadi onsite di server)
        if (metode === 'remote' && selectedStatus === 'need_visit') {
          $('#scheduleFields').show();
          $('#ticketTanggal').prop('required', true);
          $('#ticketJam').prop('required', true);
          // Teknisi TIDAK wajib saat masih remote + perlu kunjungan
          $('#ticketTeknisi').prop('required', false);
          // Sembunyikan field teknisi agar tidak membingungkan
          $('#teknisiFieldGroup').hide();
        } else {
          $('#scheduleFields').hide();
          $('#ticketTanggal').prop('required', false);
          $('#ticketJam').prop('required', false);
          $('#ticketTeknisi').prop('required', false);
          // Untuk remote selain need_visit (mis. selesai), sembunyikan teknisi
          $('#teknisiFieldGroup').hide();
          // Bersihkan nilai jadwal saat remote bukan perlu kunjungan
          $('#ticketTanggal').val('');
          $('#ticketJam').val('');
          $('#ticketHari').val('');
          $('#ticketTeknisi').val('');
          clearTeknisiSelection();
        }
      }
    }
  });

  // Auto-fill hari based on tanggal selected
  $('#ticketTanggal').on('change', function() {
    const selectedDate = $(this).val();
    if (selectedDate) {
      const date = new Date(selectedDate);
      const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
      const dayName = days[date.getDay()];
      $('#ticketHari').val(dayName);
    }
  });

  // Toggle status options based on metode
  function updateStatusOptions(mode) {
    // Hide all first
    $('.status-remote').hide();
    $('.status-onsite').hide();

    // Clear any previously checked radio to prevent double selection
    $('input[name="update_status"]').prop('checked', false);

    if (mode === 'remote') {
      $('.status-remote').show();
      $('#statusOnCheckRemote').prop('checked', true);
    } else if (mode === 'onsite') {
      $('.status-onsite').show();
      $('#statusOnProgressOnsite').prop('checked', true);
    } else {
      // default: show all
      $('.status-remote').show();
      $('.status-onsite').show();
    }
  }

  loadReplies();
  loadTimeline();

  // Auto-load replies every 3 seconds
  setInterval(loadReplies, 3000);
  setInterval(loadTimeline, 5000);

  // Handle form submission - modal button
  $('#submitReplyBtn').on('click', function() {
    const reply = $('#replyInput').val().trim();
    let updateStatus = $('input[name="update_status"]:checked').val();

    // Collect ticket fields
    const priority = $('#ticketPriority').val();
    const jenis = ticketJenis; // Use from ticket object, not form
    const metode = $('#ticketMetode').val();
    let tanggal = $('#ticketTanggal').val();
    let jam = $('#ticketJam').val();
    let hari = $('#ticketHari').val();
    let teknisiIds = teknisiSelections.map(t => t.id);

    // Auto-include selected teknisi if none added via 'Tambah' button
    if (teknisiIds.length === 0 && $('#ticketTeknisi').val()) {
      teknisiIds = [parseInt($('#ticketTeknisi').val(), 10)];
    }

    if (!reply) {
      alert('Please enter a comment');
      return;
    }

    // Tentukan metode efektif: jika remote + perlu kunjungan, konversi menjadi onsite
    const effectiveMetode = (metode === 'remote' && updateStatus === 'need_visit') ? 'onsite' : metode;

    // Pending sekarang bisa kirim schedule (optional) untuk akurasi SLA
    if (updateStatus === 'pending') {
      // teknisi not needed for pending
      teknisiIds = [];
      clearTeknisiSelection();
      // Schedule tetap dikirim jika diisi (untuk SLA stop time yang akurat)
    } else if (updateStatus === 'need_visit') {
      // teknisi tidak perlu untuk perlu kunjungan
      teknisiIds = [];
      clearTeknisiSelection();
    }

    // Validate required fields berdasarkan metode efektif
    if ((ticketJenis === 'maintenance' || ticketJenis === 'installasi' || ticketJenis === 'survey') && metode === 'remote' && updateStatus === 'need_visit') {
      // Kasus konversi: hanya wajib tanggal & jam, teknisi optional
      if (!tanggal) {
        alert('Tanggal kunjungan harus diisi untuk Perlu Kunjungan');
        return;
      }
      if (!jam) {
        alert('Jam kunjungan harus diisi untuk Perlu Kunjungan');
        return;
      }
      // Enforce baseline: not before ticket created or last update
      const chosenDT_remote = new Date(tanggal + 'T' + jam);
      if (chosenDT_remote < latestUpdateDateTime) {
        alert('Tanggal/jam kunjungan tidak boleh kurang dari update sebelumnya');
        return;
      }
      // teknisiId boleh kosong
    } else if ((ticketJenis === 'maintenance' || ticketJenis === 'installasi' || ticketJenis === 'survey') && effectiveMetode === 'onsite' && updateStatus !== 'pending') {
      if (!tanggal) {
        alert('Tanggal kunjungan harus diisi untuk metode onsite');
        return;
      }
      if (!jam) {
        alert('Jam kunjungan harus diisi untuk metode onsite');
        return;
      }
      // Enforce baseline: not before ticket created or last update
      const chosenDT = new Date(tanggal + 'T' + jam);
      if (chosenDT < latestUpdateDateTime) {
        alert('Tanggal/jam kunjungan tidak boleh kurang dari update sebelumnya');
        return;
      }
      // Teknisi hanya wajib untuk On Progress Kunjungan
      if (updateStatus === 'on_progress' && (!teknisiIds || teknisiIds.length === 0)) {
        alert('Teknisi harus dipilih untuk status On Progress Kunjungan');
        return;
      }
    }

    // Intercept submission to check for completion
    if ((updateStatus === 'done' || updateStatus === 'remote_done') && ticketJenis === 'maintenance') {
      // Hide update modal
      $('#modalAddUpdate').modal('hide');
      
      // Prepare RFO modal
      $('#rfoForm textarea').prop('disabled', false);
      $('#saveRfoBtn').show();
      $('#modalUpdateRfo .modal-title').text('Update Data RFO (Wajib diisi untuk Close Ticket)');
      $('#modalUpdateRfo').modal('show');
      
      // Set global flag to trigger reply submission after RFO save
      window.pendingReplySubmission = true;
      return; 
    }

    sendReplyAjax(reply, updateStatus, priority, jenis, effectiveMetode, tanggal, jam, hari, teknisiIds);
  });

  // Reusable function for sending reply
  function sendReplyAjax(reply, updateStatus, priority, jenis, metode, tanggal, jam, hari, teknisiIds) {
    const btn = $('#submitReplyBtn');
    btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Sending...');

    // Send both reply and ticket field updates
    $.ajax({
      type: 'POST',
      url: '{{ route("store-ticket-reply") }}',
      data: {
        _token: '{{ csrf_token() }}',
        ticket_id: ticketId,
        reply: reply,
        update_status: updateStatus,
        priority: priority || null,
        jenis: jenis || null,
        metode_penanganan: metode || null,
        tanggal_kunjungan: tanggal || null,
        jam: jam || null,
        hari: hari || null,
        teknisi_ids: teknisiIds || []
      },
      success: function(response) {
        $('#replyInput').val('');
        $('#ticketPriority').val('');
        $('#ticketMetode').val('');
        $('#ticketTanggal').val('');
        $('#ticketJam').val('');
        $('#ticketHari').val('');
        $('#ticketTeknisi').val('');
        clearTeknisiSelection();
        updateScheduleFieldsVisibility();
        $('#modalAddUpdate').modal('hide');
        loadReplies();
        // Reload page to show updated ticket fields
        setTimeout(function() {
          location.reload();
        }, 500);
        alert('Update added successfully!');
      },
      error: function(xhr) {
        const message = xhr.responseJSON?.message || 'Failed to add update';
        alert('Error: ' + message);
        // If failed during pending submission, re-enable buttons
        if(window.pendingReplySubmission) {
           // Maybe show the update modal again? Or just alert.
           window.pendingReplySubmission = false;
        }
      },
      complete: function() {
        btn.prop('disabled', false).html('<i class="bx bx-send"></i> Send Update');
      }
    });
  }

  function loadReplies() {
    $.ajax({
      type: 'GET',
      url: '{{ route("get-ticket-replies") }}',
      data: { ticket_id: ticketId },
      success: function(response) {
        let replies = response.replies || [];
        // Update latest baseline to the most recent update timestamp
        if (replies.length > 0) {
          const last = replies[replies.length - 1];
          if (last && last.created_at) {
            const parts = last.created_at.split(' ');
            if (parts.length === 2) {
              const [d, m, y] = parts[0].split('-');
              latestUpdateDateTime = new Date(`${y}-${m}-${d}T${parts[1]}`);
              setMinScheduleDate();
            }
          }
        }
        // Update Ticket Information status badge based on latest update
        const infoStatusMap = {
          // update_status values
          'need_visit': { label: 'PERLU KUNJUNGAN', color: 'warning' },
          'on_progress': { label: 'ON PROGRESS', color: 'info' },
          'pending': { label: 'PENDING', color: 'secondary' },
          'remote_done': { label: 'SELESAI', color: 'success' },
          'done': { label: 'SELESAI', color: 'success' },
          // ticket.status (DB enum) values
          'open': { label: 'OPEN', color: 'primary' },
          'on progress': { label: 'ON PROGRESS', color: 'info' },
          'selesai': { label: 'SELESAI', color: 'success' }
        };
        const latestStatus = response.ticket_status;
        if (latestStatus && infoStatusMap[latestStatus]) {
          const s = infoStatusMap[latestStatus];
          const $badge = $('#ticketInfoStatusBadge');
          if ($badge.length) {
            $badge
              .removeClass('bg-success bg-warning bg-info bg-secondary bg-primary')
              .addClass('bg-' + s.color)
              .text(s.label);
          }
        }
        if (replies.length === 0) {
          $('#repliesContainer').html(
            '<div class="text-center text-muted py-4"><small>No updates yet...</small></div>'
          );
          $('#replyCount').text('0 updates');
        } else {
          let html = '';
          replies.forEach(function(reply, index) {
            // Define status badge
            let statusBadge = '';
            const statusMap = {
              'need_visit': { label: 'Perlu Kunjungan', color: 'warning' },
              'on_progress': { label: 'On Progress', color: 'info' },
              'pending': { label: 'Pending', color: 'secondary' },
              'remote_done': { label: 'Selesai Remote', color: 'success' },
              'done': { label: 'Selesai', color: 'success' }
            };

            if (reply.update_status && statusMap[reply.update_status]) {
              const status = statusMap[reply.update_status];
              statusBadge = `<span class="badge bg-${status.color} ms-2">${status.label}</span>`;
            }

            // Format jadwal kunjungan jika ada
            let scheduleInfo = '';
            if (reply.tanggal_kunjungan && reply.jam_kunjungan) {
              const [year, month, day] = reply.tanggal_kunjungan.substring(0, 10).split('-');
              const formattedDate = `${day}-${month}-${year}`;
              scheduleInfo = `<div class="mt-2"><small class="text-primary"><i class="bx bx-calendar me-1"></i><strong>Jadwal Kunjungan:</strong> ${formattedDate} pukul ${reply.jam_kunjungan}</small></div>`;
            }

            html += `
              <div class="mb-3 pb-3 ${index < replies.length - 1 ? 'border-bottom' : ''}">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <h6 class="mb-1">
                      <strong>${reply.user_name}</strong>
                      <span class="badge bg-${reply.user_role === 'teknisi' ? 'info' : (reply.user_role === 'sales' ? 'warning' : 'secondary')} ms-2">
                        ${reply.user_role}
                      </span>
                      ${statusBadge}
                    </h6>
                    <small class="text-muted">${reply.created_at_diff}</small>
                  </div>
                </div>
                <p class="mt-2 mb-0">${reply.reply}</p>
                ${scheduleInfo}
              </div>
            `;
          });
          $('#repliesContainer').html(html);
          $('#replyCount').text(replies.length + ' ' + (replies.length === 1 ? 'update' : 'updates'));
        }
      }
    });
  }

  function loadTimeline() {
    $.ajax({
      type: 'GET',
      url: '{{ route("get-ticket-replies") }}',
      data: { ticket_id: ticketId },
      success: function(response) {
        let replies = response.replies || [];
        let html = '';

        // Add created event
        html += `
          <div class="mb-3 pb-2 border-bottom">
            <small class="text-muted">{{ $ticket->created_at->format('d-m-Y H:i') }}</small>
            <p class="mb-0"><strong>Ticket dibuat</strong> oleh {{ $ticket->creator ? $ticket->creator->name : 'Admin' }} ({{ $ticket->created_by_role ?? 'admin' }})</p>
          </div>
        `;

        // Add all replies/updates
        replies.forEach(function(reply, index) {
          // Determine status label more contextually
          let statusLabel = '';
          if (reply.update_status === 'on_progress') {
            statusLabel = (reply.tanggal_kunjungan || reply.teknisi_id) ? 'On Progress Kunjungan' : 'On Check';
          } else if (reply.update_status === 'need_visit') {
            statusLabel = 'Perlu Kunjungan';
          } else if (reply.update_status === 'pending') {
            statusLabel = 'Pending';
          } else if (reply.update_status === 'remote_done') {
            statusLabel = 'Selesai Remote';
          } else if (reply.update_status === 'done') {
            statusLabel = 'Selesai';
          } else {
            statusLabel = reply.update_status || '-';
          }

          // Badge color mapping
          const badgeColorMap = {
            'Perlu Kunjungan': 'warning',
            'On Progress Kunjungan': 'info',
            'On Check': 'info',
            'Pending': 'secondary',
            'Selesai Remote': 'success',
            'Selesai': 'success'
          };
          const badgeColor = badgeColorMap[statusLabel] || 'secondary';

          // Format schedule if present
          let scheduleBlock = '';
          if (reply.tanggal_kunjungan && reply.jam_kunjungan) {
            const [year, month, day] = reply.tanggal_kunjungan.substring(0, 10).split('-');
            const formattedDate = `${day}-${month}-${year}`;
            scheduleBlock = `<div class="mt-1"><small class="text-primary"><i class="bx bx-calendar me-1"></i>Jadwal: ${formattedDate} pukul ${reply.jam_kunjungan}</small></div>`;
          }

          html += `
            <div class="mb-3 pb-2 ${index < replies.length - 1 ? 'border-bottom' : ''}">
              <small class="text-muted">${reply.created_at}</small>
              <p class="mb-1">
                <span class="badge bg-${badgeColor}">${statusLabel}</span>
                <span class="ms-2">oleh ${reply.user_name} (${reply.user_role})</span>
              </p>
              ${scheduleBlock}
              <p class="mb-0 text-muted" style="font-size: 0.9rem;">${reply.reply}</p>
            </div>
          `;
        });

        $('#timelineContainer').html(html);
      }
    });
  }

  function loadTeknisi() {
    $.ajax({
      type: 'GET',
      url: '{{ route("get-ticket-replies") }}',
      data: { ticket_id: ticketId },
      success: function(response) {
        // Safety check: ensure response is valid
        if (!response) {
          console.error('Empty response from getReplies');
          return;
        }

        let teknisiData = response.teknisi || null;
        let teknisiHistory = response.teknisi_history || [];
        let replies = response.replies || [];
        let currentTeknisi = response.current_teknisi || [];

        console.log('teknisiHistory:', teknisiHistory);
        console.log('teknisiData:', teknisiData);

        // Display handler: onsite uses teknisi; remote uses last updater on on_progress
        const currentStatus = '{{ $ticket->status }}';
        const currentMetode = ticketMetodeInitial || '{{ $ticket->metode_penanganan }}';
        if (currentStatus === 'on progress' || currentStatus === 'on_progress') {
          if (currentTeknisi.length > 0) {
            const badges = currentTeknisi.map(t => `<span class="badge bg-info me-1 mb-1">${t.name}</span>`).join(' ');
            let currentHtml = `
              <div class="mb-3 p-3 bg-light border-left border-info rounded">
                <h6 class="mb-2"><i class="bx bx-user me-2 text-info"></i><strong>Teknisi Aktif</strong></h6>
                <div class="mb-2">${badges}</div>
                <small class="d-block text-muted mb-1">Status: <strong>${currentMetode === 'remote' ? 'On Check (Remote)' : 'On Progress Kunjungan'}</strong></small>
              </div>
            `;
            $('#currentTeknisiContainer').html(currentHtml);
          } else if (currentMetode === 'remote') {
            // Remote fallback: last on_progress user
            let lastOnProgress = null;
            for (let i = replies.length - 1; i >= 0; i--) {
              if (replies[i].update_status === 'on_progress') {
                lastOnProgress = replies[i];
                break;
              }
            }
            if (lastOnProgress) {
              const roleColor = lastOnProgress.user_role === 'teknisi' ? 'info' : (lastOnProgress.user_role === 'sales' ? 'warning' : 'secondary');
              let currentHtml = `
                <div class="mb-3 p-3 bg-light border-left border-info rounded">
                  <h6 class="mb-2"><i class="bx bx-user me-2 text-info"></i><strong>${lastOnProgress.user_name}</strong></h6>
                  <small class="d-block mb-2">
                    <span class="badge bg-${roleColor}">${lastOnProgress.user_role}</span>
                  </small>
                  <small class="d-block text-muted mb-1">Status: <strong>On Check (Remote)</strong></small>
                </div>
              `;
              $('#currentTeknisiContainer').html(currentHtml);
            } else {
              $('#currentTeknisiContainer').html('<div class="text-center text-muted py-2"><small>Belum ada yang handle</small></div>');
            }
          } else {
            $('#currentTeknisiContainer').html('<div class="text-center text-muted py-2"><small>Belum ada teknisi yang dipilih</small></div>');
          }
        }

        // Dynamic render: store data and render with controls
        window.teknisiHistoryData = teknisiHistory;
        ensureTeknisiControls();
        renderTeknisiHistory();
      },
      error: function(xhr, status, error) {
        console.error('AJAX error:', error);
        console.error('Response:', xhr.responseText);
      }
    });
  }

  // State for teknisi history UI
  const teknisiUIState = {
    pageSize: 15,
    showAll: false,
    sort: 'visits_desc', // visits_desc | last_visit_desc | name_asc
    query: ''
  };

  // Ensure controls (search + sort + summary) are present
  function ensureTeknisiControls() {
    if ($('#teknisiControls').length) return;

    const controls = `
      <div id="teknisiControls" class="mb-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <input id="teknisiSearch" type="text" class="form-control form-control-sm" placeholder="Cari teknisi..." style="max-width: 55%;">
          <select id="teknisiSort" class="form-select form-select-sm" style="max-width: 40%;">
            <option value="visits_desc">Urutkan: Kunjungan terbanyak</option>
            <option value="last_visit_desc">Urutkan: Kunjungan terbaru</option>
            <option value="name_asc">Urutkan: Nama (A–Z)</option>
          </select>
        </div>
        <div id="teknisiSummary" class="mt-2 small text-muted">-</div>
        <hr class="my-2">
      </div>
      <div id="teknisiList"></div>
    `;

    // Inject controls at top of container
    $('#teknisiContainer').html(controls);

    // Bind events
    let debounceTimer;
    $('#teknisiSearch').on('input', function() {
      teknisiUIState.query = this.value.trim();
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(renderTeknisiHistory, 180);
    });
    $('#teknisiSort').on('change', function() {
      teknisiUIState.sort = this.value;
      renderTeknisiHistory();
    });
  }

  // Render teknisi history list based on state
  function renderTeknisiHistory() {
    const data = Array.isArray(window.teknisiHistoryData) ? window.teknisiHistoryData.slice() : [];

    if (!data.length) {
      $('#teknisiContainer').html(
        '<div class="text-center text-muted py-3"><small>Belum ada teknisi yang berkunjung</small></div>'
      );
      return;
    }

    // Filter by name
    let filtered = data;
    if (teknisiUIState.query) {
      const q = teknisiUIState.query.toLowerCase();
      filtered = data.filter(t => (t.name || '').toLowerCase().includes(q));
    }

    // Sort
    const sort = teknisiUIState.sort;
    filtered.sort((a, b) => {
      if (sort === 'name_asc') {
        return (a.name || '').localeCompare(b.name || '');
      }
      if (sort === 'last_visit_desc') {
        const ad = a.last_visit_date || 0;
        const bd = b.last_visit_date || 0;
        return bd - ad;
      }
      // visits_desc default
      const av = a.visit_count || 0;
      const bv = b.visit_count || 0;
      if (bv !== av) return bv - av;
      // tie-breaker: last visit desc
      const ad = a.last_visit_date || 0;
      const bd = b.last_visit_date || 0;
      return bd - ad;
    });

    // Compute summary
    const totalTeknisi = filtered.length;
    const totalVisits = filtered.reduce((sum, t) => sum + (t.visit_count || 0), 0);
    $('#teknisiSummary').text(`Total teknisi: ${totalTeknisi} • Total kunjungan: ${totalVisits}`);

    // Build list
    const limit = teknisiUIState.showAll ? filtered.length : teknisiUIState.pageSize;
    const items = filtered.slice(0, limit);

    let html = '';
    items.forEach((teknisi, index) => {
      html += `
        <div class="mb-3 pb-3 ${index < items.length - 1 ? 'border-bottom' : ''}">
          <div class="d-flex justify-content-between align-items-start">
            <div style="flex:1; min-width:0;">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <strong>${teknisi.name || '-'}</strong>
                <span class="badge bg-info">${teknisi.role || 'teknisi'}</span>
                <span class="badge bg-primary">${teknisi.visit_count || 0}x kunjungan</span>
                ${teknisi.last_visit ? `<small class="text-muted">Terakhir: ${teknisi.last_visit}</small>` : ''}
              </div>
            </div>
          </div>
        </div>
      `;
    });

    // Show more / less button
    if (filtered.length > limit) {
      html += `
        <div class="text-center">
          <button id="teknisiShowMore" class="btn btn-sm btn-outline-secondary">
            Tampilkan semua (${filtered.length})
          </button>
        </div>
      `;
    } else if (teknisiUIState.showAll && filtered.length > teknisiUIState.pageSize) {
      html += `
        <div class="text-center">
          <button id="teknisiShowMore" class="btn btn-sm btn-outline-secondary">Tampilkan lebih sedikit</button>
        </div>
      `;
    }

    // Ensure controls exist, then render list
    ensureTeknisiControls();
    $('#teknisiList').html(html);

    // Bind show more/less
    $('#teknisiShowMore').off('click').on('click', function() {
      teknisiUIState.showAll = !teknisiUIState.showAll;
      renderTeknisiHistory();
    });
  }

  loadTeknisi();
  setInterval(loadTeknisi, 5000);

  // RFO Button Handlers
  $('#btnEditRfo').on('click', function() {
      // Enable fields
      $('#rfoForm textarea, #rfoForm select').prop('disabled', false);
      $('#saveRfoBtn').show();
      $('#btnExportRfoPdf').hide();
      $('#modalUpdateRfo .modal-title').text('Update Data RFO');
      $('#modalUpdateRfo').modal('show');
  });

  $('#btnShowRfo').on('click', function() {
      // Disable fields for viewing
      $('#rfoForm textarea, #rfoForm select').prop('disabled', true);
      $('#saveRfoBtn').hide();
      $('#btnExportRfoPdf').show();
      $('#modalUpdateRfo .modal-title').text('View Data RFO');
      $('#modalUpdateRfo').modal('show');
  });

  // RFO Save Logic
  $('#saveRfoBtn').on('click', function() {
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    
    $.ajax({
       url: '{{ route("update-rfo") }}',
       type: 'POST',
       data: $('#rfoForm').serialize(),
       success: function(res) {
          if (window.pendingReplySubmission) {
             alert('RFO saved. Proceeding to close ticket...');
             $('#modalUpdateRfo').modal('hide');
             
             // Retrieve values from the hidden reply form to submit
             // We can re-trigger the sendReplyAjax using the closures variables IF they are accessible.
             // But they are inside the click handler scope.
             // Solution: Trigger the submit button click again? No, checks validation logic.
             // Better: Store the args in global or re-read from form since form is not cleared.
             
             const reply = $('#replyInput').val().trim();
             let updateStatus = $('input[name="update_status"]:checked').val();
             const priority = $('#ticketPriority').val();
             const jenis = ticketJenis; 
             const metode = $('#ticketMetode').val();
             let tanggal = $('#ticketTanggal').val();
             let jam = $('#ticketJam').val();
             let hari = $('#ticketHari').val();
             // Re-calculate teknisiIds from selections
             let teknisiIds = teknisiSelections.map(t => t.id);
             if (teknisiIds.length === 0 && $('#ticketTeknisi').val()) {
                teknisiIds = [parseInt($('#ticketTeknisi').val(), 10)];
             }
             const effectiveMetode = (metode === 'remote' && updateStatus === 'need_visit') ? 'onsite' : metode;
             
             sendReplyAjax(reply, updateStatus, priority, jenis, effectiveMetode, tanggal, jam, hari, teknisiIds);
             
             // Reset flag
             window.pendingReplySubmission = false;
          } else {
             alert('RFO updated successfully');
             $('#modalUpdateRfo').modal('hide');
             location.reload(); 
          }
       },
       error: function(xhr) {
          alert('Failed to update RFO: ' + (xhr.responseJSON?.message || 'Error occurred'));
       },
       complete: function() {
          btn.prop('disabled', false).html('<i class="bx bx-save"></i> Save Changes');
       }
    });
  });
});
</script>
@endsection
