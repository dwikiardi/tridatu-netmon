@extends('layouts/contentNavbarLayout')

@section('title', 'Tables - User Management')

@section('content')

<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">User Management /</span> Table User
</h4>

<!-- Table User -->
<div class="card p-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5>Data User</h5>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddUser" data-action="add"
      onclick="$('#modalAddUser form').trigger('reset'); $('#id').val(''); $('#modalTitle').text('Add User');">Add</button>
  </div>
  <div class="table-responsive text-nowrap">
    <table class="table" id="tableUser">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Username</th>
          <th>Email</th>
          <th>Jabatan</th>
          <th>No. HP (WA)</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0"></tbody>
    </table>
  </div>
</div>

<!-- Modal Edit / Add User -->
<div class="modal fade" id="modalAddUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <hr class="my-0">
      <div class="modal-body">
        <form>
          <input type="hidden" id="id" name="id">
          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name">
          </div>
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username">
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email">
          </div>
          <div class="mb-3">
            <label for="jabatan" class="form-label">Jabatan</label>
            <select class="form-control" id="jabatan" name="jabatan">
              <option value="">Pilih Jabatan</option>
              <option value="sales">Sales</option>
              <option value="teknisi">Teknisi</option>
              <option value="noc">NOC</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="phone" class="form-label">No. HP WhatsApp</label>
            <input type="text" class="form-control" id="phone" name="phone" placeholder="Contoh: 6289512345678">
            <small class="text-muted">Format internasional tanpa + (misal: 6289512345678). Digunakan untuk integrasi bot WhatsApp.</small>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password">
            <small class="text-muted">Kosongkan jika tidak ingin mengubah password.</small>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="btnSaveUser">Save</button>
      </div>
    </div>
  </div>
</div>

@endsection


