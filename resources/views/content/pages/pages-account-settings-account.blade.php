@extends('layouts/contentNavbarLayout')

@section('title', 'Account settings - Account')

@section('page-script')
<script>
$(document).ready(function() {
  // Preview uploaded photo
  $('#upload').on('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        $('#uploadedAvatar').attr('src', e.target.result);
      };
      reader.readAsDataURL(file);
    }
  });

  // Reset photo to default
  $('.account-image-reset').on('click', function(e) {
    e.preventDefault();
    $('#uploadedAvatar').attr('src', '{{ $user->photo ? asset($user->photo) : asset("assets/img/avatars/1.png") }}');
    $('#upload').val('');
  });

  $('#formAccountSettings').on('submit', function(e) {
    e.preventDefault();

    var formData = new FormData(this);
    console.log('Form Data keys:', Array.from(formData.keys()));
    console.log('Photo file:', formData.get('photo'));

    $.ajax({
      url: '{{ route("pages-account-settings-account-update") }}',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        console.log('Response:', response);
        alert(response.message);
        if (response.photo) {
          $('#uploadedAvatar').attr('src', response.photo);
        }
      },
      error: function(xhr) {
        console.log('Error Response:', xhr.responseJSON);
        if (xhr.status === 422) {
          let errors = xhr.responseJSON.errors;
          let errorMsg = '';
          for (let key in errors) {
            errorMsg += errors[key][0] + '\n';
          }
          alert(errorMsg);
        } else {
          alert('An error occurred. Please try again.');
        }
      }
    });
  });

  // Handle change password form
  $('#formChangePassword').on('submit', function(e) {
    e.preventDefault();

    var formData = new FormData(this);

    $.ajax({
      url: '{{ route("pages-account-settings-account-change-password") }}',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        console.log('Password Response:', response);
        alert(response.message);
        $('#formChangePassword')[0].reset();
      },
      error: function(xhr) {
        console.log('Error Response:', xhr.responseJSON);
        if (xhr.status === 422) {
          let errors = xhr.responseJSON.errors;
          let errorMsg = '';
          for (let key in errors) {
            errorMsg += errors[key][0] + '\n';
          }
          alert(errorMsg);
        } else if (xhr.status === 401) {
          alert('Current password is incorrect');
        } else {
          alert('An error occurred. Please try again.');
        }
      }
    });
  });
});
</script>
@endsection

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Account Settings /</span> Account
</h4>

<div class="row">
  <div class="col-md-12">
    <ul class="nav nav-pills flex-column flex-md-row mb-3">
      <li class="nav-item"><a class="nav-link active" href="javascript:void(0);"><i class="bx bx-user me-1"></i> Account</a></li>
      {{-- <li class="nav-item"><a class="nav-link" href="{{url('pages/account-settings-notifications')}}"><i class="bx bx-bell me-1"></i> Notifications</a></li>
      <li class="nav-item"><a class="nav-link" href="{{url('pages/account-settings-connections')}}"><i class="bx bx-link-alt me-1"></i> Connections</a></li> --}}
    </ul>
    <div class="card mb-4">
      <h5 class="card-header">Profile Details</h5>
      <!-- Account -->
      <div class="card-body">
        <div class="d-flex align-items-start align-items-sm-center gap-4">
          <img src="{{ $user->photo ? asset($user->photo) : asset('assets/img/avatars/1.png') }}" alt="user-avatar" class="d-block rounded" height="100" width="100" id="uploadedAvatar" />
          {{-- <div class="button-wrapper">
            <button type="button" class="btn btn-primary me-2 mb-4 account-image-reset">
              <i class="bx bx-reset d-block d-sm-none"></i>
              <span class="d-none d-sm-block">Reset Photo</span>
            </button>
            <p class="text-muted mb-0">Allowed JPG, GIF or PNG. Max size of 8MB</p>
          </div> --}}
        </div>
      </div>
      <hr class="my-0">
      <div class="card-body">
        <form id="formAccountSettings" method="POST">
          @csrf
          <div class="row">
            <div class="mb-3 col-md-6">
              <label for="name" class="form-label">Name</label>
              <input class="form-control" type="text" id="name" name="name" value="{{ $user->name }}" autofocus required />
            </div>
            <div class="mb-3 col-md-6">
              <label for="email" class="form-label">E-mail</label>
              <input class="form-control" type="email" id="email" name="email" value="{{ $user->email }}" required />
            </div>
            <div class="mb-3 col-md-6">
              <label for="jabatan" class="form-label">Jabatan</label>
              <input type="text" class="form-control" id="jabatan" name="jabatan" value="{{ $user->jabatan }}" readonly />
            </div>
            <div class="mb-3 col-md-6">
              <label class="form-label" for="phone">Phone Number</label>
              <input type="text" id="phone" name="phone" class="form-control" value="{{ $user->phone }}" placeholder="08123456789" />
            </div>
            <div class="mb-3 col-md-12">
              <label for="upload" class="form-label">Upload Photo</label>
              <input type="file" id="upload" name="photo" class="form-control account-file-input" accept="image/png, image/jpeg" />
            </div>
          </div>
          <div class="mt-2">
            <button type="submit" class="btn btn-primary me-2" id="btnSaveProfile">Save changes</button>
            <button type="reset" class="btn btn-outline-secondary">Cancel</button>
          </div>
        </form>
      </div>
      <!-- /Account -->
    </div>
    <div class="card mb-4">
      <h5 class="card-header">Change Password</h5>
      <div class="card-body">
        <form id="formChangePassword" method="POST">
          @csrf
          <div class="row">
            <div class="mb-3 col-md-12">
              <label for="current_password" class="form-label">Current Password</label>
              <input class="form-control" type="password" id="current_password" name="current_password" required />
            </div>
            <div class="mb-3 col-md-12">
              <label for="new_password" class="form-label">New Password</label>
              <input class="form-control" type="password" id="new_password" name="new_password" required />
            </div>
            <div class="mb-3 col-md-12">
              <label for="new_password_confirmation" class="form-label">Confirm Password</label>
              <input class="form-control" type="password" id="new_password_confirmation" name="new_password_confirmation" required />
            </div>
          </div>
          <div class="mt-2">
            <button type="submit" class="btn btn-primary me-2" id="btnChangePassword">Change Password</button>
          </div>
        </form>
      </div>
    </div>
    <div class="card">
      <h5 class="card-header">Delete Account</h5>
      <div class="card-body">
        <div class="mb-3 col-12 mb-0">
          <div class="alert alert-warning">
            <h6 class="alert-heading fw-medium mb-1">Are you sure you want to delete your account?</h6>
            <p class="mb-0">Once you delete your account, there is no going back. Please be certain.</p>
          </div>
        </div>
        <form id="formAccountDeactivation" onsubmit="return false">
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="accountActivation" id="accountActivation" />
            <label class="form-check-label" for="accountActivation">I confirm my account deactivation</label>
          </div>
          <button type="submit" class="btn btn-danger deactivate-account">Deactivate Account</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
