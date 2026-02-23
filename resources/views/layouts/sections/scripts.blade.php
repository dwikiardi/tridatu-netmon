<!-- BEGIN: Vendor JS-->
<script src="{{ asset(mix('assets/vendor/libs/jquery/jquery.js')) }}"></script>
<script src="{{ asset(mix('assets/vendor/libs/popper/popper.js')) }}"></script>
<script src="{{ asset(mix('assets/vendor/js/bootstrap.js')) }}"></script>
<script src="{{ asset(mix('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js')) }}"></script>
<script src="{{ asset(mix('assets/vendor/js/menu.js')) }}"></script>

@yield('vendor-script')
<!-- END: Page Vendor JS-->
<!-- BEGIN: Theme JS-->
<script src="{{ asset(mix('assets/js/main.js')) }}"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="{{asset('assets/js/ui-modals.js')}}"></script>
<script>
    $(document).ready(function() {
      //Table Olt Monitor
      $('#myTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "{{ route('table-olt') }}", // Ganti dengan endpoint API/backend Anda
                "type": "GET",
                "dataSrc": "data"  // Pastikan membaca array "data"
            },
            "columns": [
                { "data": "description" },
                {
                  "data": "ponid",
                  "render": function(data, type, row) {
                      if (!data) return "-"; // Jika data kosong, tampilkan "-"
                      // console.log(data);
                      // Pisahkan OID dan ambil nilai yang diperlukan
                      let oidParts = data.split(".");
                      let ontId = parseInt(oidParts.pop()); // Ambil ONT ID (angka terakhir)
                      let oidValue = parseInt(oidParts.pop()); // Ambil nilai OID utama

                      // Hitung Frame dari bit 24-31
                      let frame = (oidValue >> 24) & 0xFF;

                      // Koreksi jika Frame lebih besar dari yang diharapkan
                      if (frame >= 16) frame -= 15; // Koreksi pola 16 menjadi 1

                      // Hitung Slot dari bit 16-23
                      let slot = (oidValue >> 16) & 0xFF;

                      // Hitung Port dari bit 8-15
                      let port = (oidValue >> 8) & 0xFF;

                      // Gabungkan hasil dalam format Frame/Slot/Port:ONU ID
                      return `${frame}/${slot}/${port}:${ontId}`;
                  }
              },
              {
                "data": "onurx",
                "render": function(data, type, row) {
                    let dbm = (data * 0.002) - 30;
                    return (dbm > 50 || dbm < -50) ? "-50" : dbm.toFixed(2);
                }
              },
              {
                  "data": "phase",
                  "render": function(data, type, row) {
                      if (data == 3) return "working";
                      if (data == 6) return "Offline";
                      if (data == 4) return "Dying Gasp";
                      if (data == 1) return "LOS";
                      return data;
                  }
                },
                { "data": "lastonline" },
                { "data": "lastoffline" },
                {
                  data: "reason",
                  render: function(data, type, row) {
                      const map = {
                          1: "unknown",
                          2: "LOS",
                          3: "LOSi",
                          4: "LOFi",
                          5: "sfi",
                          6: "loai",
                          7: "loami",
                          8: "AuthFail",
                          9: "PowerOff",
                          10: "deactiveSucc",
                          11: "deactiveFail",
                          12: "Reboot",
                          13: "Shutdown"
                      };
                      return map[data] || data;
                  }
                },
                { "data": "pop" },
                {
                  "data": null,
                  "render": function(data, type, row) {
                    return `<button type="button"
                              class="btn btn-primary btnSnmp"
                              data-bs-toggle="modal"
                              data-bs-target="#basicModal"
                              data-oid=".1.3.6.1.4.1.3902.1012.3.50.12.1.1.10.${row.ponid}"
                              data-cause=".1.3.6.1.4.1.3902.1012.3.28.2.1.4.${row.ponid}"
                              data-desc="${row.description}"
                              data-pop="${row.pop}">
                              <i class='bx bx-signal-5'></i>
                              </button>`;
                  }
              }
            ],
            "language": {
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "zeroRecords": "Tidak ada data ditemukan",
                "info": "Menampilkan _START_ hingga _END_ dari _TOTAL_ data",
                "infoEmpty": "Tidak ada data tersedia",
                "search": "Cari:",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Berikutnya",
                    "previous": "Sebelumnya"
                }
            }
        });
      });

      let intervalID = null; // Variabel global untuk menyimpan interval

      $(document).on("click", ".btnSnmp", function() {
        let desc = $(this).data("desc");
        let rawoid = $(this).data("oid");
        let pop = $(this).data("pop");
        let oid = rawoid.replace(/\.iso\.3\.6\.1\.4\.1\.3902\.1012\.3\.28\.1\.1\.3/, '');
        let cause = $(this).data("cause").replace(/\.iso\.3\.6\.1\.4\.1\.3902\.1012\.3\.28\.1\.1\.3/, '');
        // let cause = convertOid(rawcause);
        $("#snmpData").html("<span class='text-muted'>Fetching data...</span>");

        fetchSNMPData(oid, pop, cause, desc);

        // Hentikan interval jika ada yang berjalan sebelumnya
        if (intervalID !== null) {
            clearInterval(intervalID);
            console.log("Interval dihentikan sebelum membuat yang baru.");
        }

        // Set interval untuk refresh data setiap 1 menit
        intervalID = setInterval(function() {
            console.log("Fetching data...");
            fetchSNMPData(oid, pop, cause, desc);
        }, 60000);
        console.log(cause);
        console.log("Interval baru dibuat:", intervalID);
      });

      // Hentikan interval saat modal ditutup
      $("#basicModal").on("hidden.bs.modal", function() {
          if (intervalID !== null) {
              clearInterval(intervalID);
              console.log("Interval dihentikan karena modal ditutup.");
              intervalID = null; // Reset variabel
          }
      });

      function fetchSNMPData(oid, pop, cause, desc) {
        $.ajax({
            url: "{{ route('signal-ont') }}",
            method: "POST",
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
            data: {
              oid: oid,
              pop: pop,
              cause: cause,
              desc: desc
            },
            success: function(response) {
                let timestamp = new Date().toLocaleString(); // Ambil waktu saat ini
                $("#modalLabel").html(`
                    <p>Realtime Monitoring ONT ${response.desc}</p>
                `);
                $("#snmpData").html(`
                    <p>Ont Signal: ${response.data}</p>
                    <p>Status: ${response.offlineCause}</p>
                    <p class="text-muted">Updated at: ${timestamp}</p>
                `);
            },
            error: function(xhr, status, error) {
                console.error("SNMP Fetch Error:", error);
                $("#snmpData").text("Error fetching data.");
            }
        });
      }

      //table data customer
      $('#tableCustomer').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "{{ route('show-customer') }}", // Ganti dengan endpoint API/backend Anda
                "type": "GET",
                "dataSrc": "data"  // Pastikan membaca array "data"
            },
            "columns": [
                { "data": "cid" },
                {"data": "nama"},
                {"data": "sales"},
                {"data": "packet"},
                {
                    "data": "alamat",
                    "render": function(data, type, row) {
                        if (type !== 'display' || !data) return data || '';
                        const maxLen = 30;
                        const shortText = data.length > maxLen ? data.substring(0, maxLen) + '…' : data;
                        return `<span title="${data.replace(/"/g, '&quot;')}">${shortText}</span>`;
                    }
                },
                {
                    "data": "pembayaran_perbulan_formatted",
                    "render": function(data, type, row) {
                        return data || '-';
                    }
                },
                {"data": "tgl_customer_aktif"},
                {"data": "billing_aktif"},
                {"data": "status"},
                {
                  "data": null,
                  "render": function(data, type, row) {
                    return `  <button type="button"
                              class="btn btn-primary btnDetailCust"
                              data-bs-toggle="modal"
                              data-bs-target="#modalDetailCust"
                              data-cid="${row.cid}">
                              <i class='bx  bx-info-square'></i>
                              </button>
                              <button type="button"
                              class="btn btn-warning btnEditCust"
                              data-bs-toggle="modal"
                              data-bs-target="#modalAddCust"
                              data-action="edit"
                              data-cid="${row.cid}">
                              <i class='bx bx-edit'></i>
                              </button>
                              <button type="button"
                              class="btn btn-danger btnDeleteCust"
                              data-cid="${row.cid}">
                              <i class='bx  bx-user-minus'></i>
                              </button>`;
                  }
                }
            ],
            "language": {
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "zeroRecords": "Tidak ada data ditemukan",
                "info": "Menampilkan _START_ hingga _END_ dari _TOTAL_ data",
                "infoEmpty": "Tidak ada data tersedia",
                "search": "Cari:",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Berikutnya",
                    "previous": "Sebelumnya"
                }
            }
        });

        // Load sales options
        function loadSalesOptions() {
            $.ajax({
                url: "{{ route('show-user') }}",
                type: "GET",
                data: { length: 1000 }, // Get many to cover all
                success: function(response) {
                    var salesSelect = $('#sales');
                    salesSelect.find('option:not(:first)').remove(); // Keep the "Pilih Sales"
                    response.data.forEach(function(user) {
                        if (user.jabatan === 'sales') {
                            salesSelect.append('<option value="' + user.name + '">' + user.name + '</option>');
                        }
                    });
                },
                error: function() {
                    console.log('Error loading sales options');
                }
            });
        }

        // Load sales on page load
        $(document).ready(function() {
            if ($('#sales').length > 0) {
                loadSalesOptions();
            }
        });

        $(document).on("click", ".btnDetailCust", function() {
          let cid = $(this).data("cid");
          $.ajax({
              url: "{{ route('detail-customer') }}",
              type: "GET",
              data: {
                  cid: cid
              },
              success: function (response) {
                $("#labelModalCust").text(`Detail Customer ${response.nama}`);
                $("#dataDetailCust").html(`
                      <p><b>CID:</b> ${response.cid}</p>
                      <p><b>Nama:</b> ${response.nama}</p>
                      <p><b>Email:</b> ${response.email ?? '-'}</p>
                      <p><b>Sales:</b> ${response.sales}</p>
                      <p><b>Paket:</b> ${response.packet}</p>
                      ${response.packet === 'dedicated' ? `<p><b>Note:</b> ${response.note ?? '-'}</p>` : ''}
                      <p><b>Alamat:</b> ${response.alamat}</p>
                                            <p><b>Koordinat:</b> ${response.coordinate_maps ? (() => {
                                                    const coord = response.coordinate_maps;
                                                    const isUrl = /^https?:\/\//i.test(coord);
                                                    const href = isUrl ? coord : `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(coord)}`;
                                                    return `<a href="${href}" target="_blank" rel="noopener noreferrer">${coord}</a>`;
                                                })() : '-'}</p>

                      <hr>

                      <p><b>PIC IT:</b> ${response.pic_it ?? '-'}</p>
                      <p><b>No IT:</b> ${response.no_it ?? '-'}</p>
                      <p><b>PIC Finance:</b> ${response.pic_finance ?? '-'}</p>
                      <p><b>No Finance:</b> ${response.no_finance ?? '-'}</p>

                      <hr>

                      <p><b>Pembayaran Perbulan:</b> ${response.pembayaran_perbulan ?? '-'}</p>
                      <p><b>Tgl Customer Aktif:</b> ${response.tgl_customer_aktif ?? '-'}</p>
                      <p><b>Billing Aktif:</b> ${response.billing_aktif ?? '-'}</p>
                      <p><b>Status:</b>
                          <span class="badge bg-${response.status === 'Aktif' ? 'success' : 'danger'}">
                              ${response.status}
                          </span>
                      </p>

                      <hr>
                    `);
              },
              error: function () {
                  $("#dataDetailCust").html("<p class='text-danger'>Gagal mengambil data</p>");
              }
          });
        });

        $('#modalAddCust').on('show.bs.modal', function (event) {
          var button = $(event.relatedTarget);
          var action = button.data('action');
          if (action === 'edit') {
            $('#modalTitle').text('Edit Customer');
          } else {
            $('#modalTitle').text('Add Customer');
          }
        });

        $(document).on("click", ".btnEditCust", function() {
        let cid = $(this).data("cid");
        $.ajax({
            url: "{{ route('detail-customer') }}",
            type: "GET",
            data: {
                cid: cid
            },
            success: function (response) {
              // Populate the form fields
              $("#cid").val(response.cid);
              $("#nama").val(response.nama);
              $("#email").val(response.email);
              $("#sales").val(response.sales);
              $("#packet").val(response.packet);
              $("#alamat").val(response.alamat);
              $("#pic_it").val(response.pic_it);
              $("#no_it").val(response.no_it);
              $("#pic_finance").val(response.pic_finance);
              $("#no_finance").val(response.no_finance);
              $("#coordinate_maps").val(response.coordinate_maps);
              $("#status").val(response.status);
              $("#pembayaran_perbulan").val(formatRupiah(response.pembayaran_perbulan));
              $("#note").val(response.note || '');
              $("#tgl_customer_aktif").val(response.tgl_customer_aktif || '');
              $("#billing_aktif").val(response.billing_aktif || '');
              toggleNoteField(response.packet);
            },
            error: function () {
                alert("Gagal mengambil data customer");
            }
        });
      });


      // Fungsi format Rupiah
      function formatRupiah(angka) {
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
          return rupiah ? 'Rp. ' + rupiah : '';
      }

      // Event untuk format input pembayaran_perbulan
      $(document).on('input', '#pembayaran_perbulan', function() {
          var val = $(this).val();
          $(this).val(formatRupiah(val));
      });

      // Function to toggle note field
      function toggleNoteField(packetValue) {
          if (packetValue === 'dedicated') {
              $('#noteField').show();
          } else {
              $('#noteField').hide();
              $('#note').val('');
          }
      }

      // Event for packet change
      $(document).on('change', '#packet', function() {
          var packetValue = $(this).val();
          toggleNoteField(packetValue);
      });

      // Event untuk save customer
      $(document).on('click', '#btnSaveCust', function() {
          console.log('Save button clicked');
          var isEdit = $('#modalTitle').text() === 'Edit Customer';
          console.log('Is Edit:', isEdit);
          var url = isEdit ? "{{ url('/datacust/update') }}" : "{{ url('/datacust/store') }}";
          var method = isEdit ? 'PUT' : 'POST';

          var formData = {
              cid: $('#cid').val(),
              nama: $('#nama').val(),
              email: $('#email').val(),
              sales: $('#sales').val(),
              packet: $('#packet').val(),
              alamat: $('#alamat').val(),
              pic_it: $('#pic_it').val(),
              no_it: $('#no_it').val(),
              pic_finance: $('#pic_finance').val(),
              no_finance: $('#no_finance').val(),
              coordinate_maps: $('#coordinate_maps').val(),
              pembayaran_perbulan: $('#pembayaran_perbulan').val().replace(/[^0-9]/g, ''), // Ekstrak angka saja
              status: $('#status').val(),
              note: $('#note').val(),
              tgl_customer_aktif: $('#tgl_customer_aktif').val(),
              billing_aktif: $('#billing_aktif').val(),
              _token: $('meta[name="csrf-token"]').attr('content')
          };
           if (isEdit) {
              formData._method = 'PUT';
          }

          console.log('Is Edit:', isEdit);
          console.log('URL:', url);
          console.log('Method:', method);
          console.log('Form Data:', formData);
          console.log('Sending AJAX...');

          $.ajax({
              url: url,
              type: method,
              data: formData,
              success: function(response) {
                  console.log('Success:', response);
                  $('#modalAddCust').modal('hide');
                  $('#tableCustomer').DataTable().ajax.reload();
                  alert('Customer saved successfully');
              },
              error: function(xhr, status, error) {
                  console.log('Error:', xhr.status, xhr.responseText);
                  alert('Error saving customer: ' + xhr.responseText);
              }
          });
        });

        // Event untuk delete customer
        $(document).on('click', '.btnDeleteCust', function() {
            var cid = $(this).data('cid');
            if (confirm('Are you sure you want to delete this customer?')) {
                $.ajax({
                    url: "{{ url('/datacust/delete') }}",
                    type: 'DELETE',
                    data: {
                        cid: cid,
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        _method: 'DELETE'
                    },
                    success: function(response) {
                        $('#tableCustomer').DataTable().ajax.reload();
                        alert('Customer deleted successfully');
                    },
                    error: function(xhr) {
                        alert('Error deleting customer');
                    }
                });
            }
        });

        // DataTable for users
        $('#tableUser').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "{{ route('show-user') }}",
                "type": "GET",
                "dataSrc": "data"
            },
            "columns": [
                { "data": "id" },
                { "data": "name" },
                { "data": "username" },
                { "data": "email" },
                { "data": "jabatan" },
                {
                    "data": "phone",
                    "render": function(data) {
                        return data ? `<span class="text-success"><i class="bx bxl-whatsapp"></i> ${data}</span>` : '<span class="text-muted">-</span>';
                    }
                },
                {
                    "data": null,
                    "render": function(data, type, row) {
                        return `<button type="button" class="btn btn-primary btnDetailUser" data-bs-toggle="modal" data-bs-target="#modalDetailUser" data-id="${row.id}"> <i class='bx bx-info-square'></i> </button>
                                <button type="button" class="btn btn-warning btnEditUser" data-bs-toggle="modal" data-bs-target="#modalAddUser" data-action="edit" data-id="${row.id}"> <i class='bx bx-edit'></i> </button>
                                <button type="button" class="btn btn-danger btnDeleteUser" data-id="${row.id}"> <i class='bx bx-user-minus'></i> </button>`;
                    }
                }
            ],
            "language": {
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "zeroRecords": "Tidak ada data ditemukan",
                "info": "Menampilkan _START_ hingga _END_ dari _TOTAL_ data",
                "infoEmpty": "Tidak ada data tersedia",
                "search": "Cari:",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Berikutnya",
                    "previous": "Sebelumnya"
                }
            }
        });

        // Event untuk detail user
        $(document).on('click', '.btnDetailUser', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{ url('/user/detail') }}",
                type: "GET",
                data: { id: id },
                success: function(response) {
                    $("#labelModalUser").text(`Detail User ${response.name}`);
                    $("#dataDetailUser").html(`
                        <p><b>ID:</b> ${response.id}</p>
                        <p><b>Name:</b> ${response.name}</p>
                        <p><b>Username:</b> ${response.username ?? '-'}</p>
                        <p><b>Email:</b> ${response.email ?? '-'}</p>
                        <p><b>Jabatan:</b> ${response.jabatan ?? '-'}</p>
                        <p><b>No. HP WA:</b> ${response.phone ? `<span class="text-success"><i class="bx bxl-whatsapp"></i> ${response.phone}</span>` : '-'}</p>
                    `);
                },
                error: function() {
                    $("#dataDetailUser").html("<p class='text-danger'>Gagal mengambil data</p>");
                }
            });
        });

        // Event untuk edit user
        $(document).on('click', '.btnEditUser', function() {
            var id = $(this).data('id');
            $('#modalTitle').text('Edit User');
            $.ajax({
                url: "{{ url('/user/detail') }}",
                type: "GET",
                data: { id: id },
                success: function(response) {
                    $("#id").val(response.id);
                    $("#name").val(response.name);
                    $("#username").val(response.username);
                    $("#email").val(response.email);
                    $("#jabatan").val(response.jabatan);
                    $("#phone").val(response.phone ?? '');
                    $("#password").val(''); // Kosongkan password
                },
                error: function() {
                    alert("Gagal mengambil data user");
                }
            });
        });

        // Event untuk save user
        $(document).on('click', '#btnSaveUser', function() {
            var isEdit = $('#id').val() ? true : false;
            var url = isEdit ? "{{ url('/user/update') }}" : "{{ url('/user/store') }}";
            var method = isEdit ? 'POST' : 'POST'; // Store POST, update POST with _method

            var formData = {
                id: $('#id').val(),
                name: $('#name').val(),
                username: $('#username').val(),
                email: $('#email').val(),
                jabatan: $('#jabatan').val(),
                phone: $('#phone').val(),
                password: $('#password').val(),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            if (isEdit) {
                formData._method = 'PUT';
            }

            $.ajax({
                url: url,
                type: method,
                data: formData,
                success: function(response) {
                    $('#modalAddUser').modal('hide');
                    $('#tableUser').DataTable().ajax.reload();
                    alert('User saved successfully');
                },
                error: function(xhr) {
                    alert('Error saving user: ' + xhr.responseText);
                }
            });
        });

        // Event untuk delete user
        $(document).on('click', '.btnDeleteUser', function() {
            var id = $(this).data('id');
            if (confirm('Are you sure you want to delete this user?')) {
                $.ajax({
                    url: "{{ url('/user/delete') }}",
                    type: 'DELETE',
                    data: {
                        id: id,
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        _method: 'DELETE'
                    },
                    success: function(response) {
                        $('#tableUser').DataTable().ajax.reload();
                        alert('User deleted successfully');
                    },
                    error: function(xhr) {
                        alert('Error deleting user');
                    }
                });
            }
        });


</script>
<!-- END: Theme JS-->
<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->
<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->
