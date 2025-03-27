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
                      console.log(data);
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
                  "data": "onutx",
                  "render": function(data, type, row) {
                  let dbm = (data * 0.002) - 30;
                  return (dbm > 50 || dbm < -50) ? "-50" : dbm.toFixed(2);
                }
                },
                { "data": "lastonline" },
                { "data": "lastoffline" },
                {
                    "data": "reason",
                    "render": function(data, type, row) {
                        if (data == 3) return "working";
                        if (data == 6) return "Offline";
                        if (data == 4) return "Dying Gasp";
                        if (data == 1) return "LOS";
                        return data;
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
    let rawoid = $(this).data("oid");
    let pop = $(this).data("pop");
    let oid = rawoid.replace(/\.iso\.3\.6\.1\.4\.1\.3902\.1012\.3\.28\.1\.1\.3/, '');

    $("#snmpData").html("<span class='text-muted'>Fetching data...</span>");

    fetchSNMPData(oid, pop);

    // Hentikan interval jika ada yang berjalan sebelumnya
    if (intervalID !== null) {
        clearInterval(intervalID);
        console.log("Interval dihentikan sebelum membuat yang baru.");
    }

    // Set interval untuk refresh data setiap 1 menit
    intervalID = setInterval(function() {
        console.log("Fetching data...");
        fetchSNMPData(oid, pop);
    }, 60000);

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

function fetchSNMPData(oid, pop) {
  $.ajax({
      url: "{{ route('signal-ont') }}",
      method: "POST",
      headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
      data: {
        oid: oid,
        pop: pop
      },
      success: function(response) {
          let timestamp = new Date().toLocaleString(); // Ambil waktu saat ini

          $("#snmpData").html(`
              <p>Ont Signal: ${response.data}</p>
              <p class="text-muted">Updated at: ${timestamp}</p>
          `);
      },
      error: function(xhr, status, error) {
          console.error("SNMP Fetch Error:", error);
          $("#snmpData").text("Error fetching data.");
      }
  });
}
</script>
<!-- END: Theme JS-->
<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->
<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->
