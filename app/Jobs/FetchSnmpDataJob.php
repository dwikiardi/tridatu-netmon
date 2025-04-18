<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SnmpData;
use Illuminate\Support\Facades\Log;

class FetchSnmpDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function handle()
    {
      snmp_set_quick_print(true);
      snmp_set_oid_numeric_print(true);
      snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

      $hosts = ['10.100.11.3' , '10.100.12.3'];
      $allRecords = [];
      $records = []; // Simpan data untuk upsert ke database

      foreach($hosts as $host){
        $onurx = snmp2_walk($host, 'tridatunet', "1.3.6.1.4.1.3902.1012.3.50.12.1.1.10", '100000000000', 10);
        $onutx = snmp2_walk($host, 'tridatunet', ".1.3.6.1.4.1.3902.1012.3.50.12.1.1.14", '100000000000', 10);
        $desc = snmp2_real_walk($host, 'tridatunet', ".1.3.6.1.4.1.3902.1012.3.28.1.1.3");
        $lastonline = snmp2_walk($host, 'tridatunet', ".1.3.6.1.4.1.3902.1012.3.28.2.1.5", '100000000000', 10);
        $lastoffline = snmp2_walk($host, 'tridatunet', ".1.3.6.1.4.1.3902.1012.3.28.2.1.6", '100000000000', 10);
        $reason = snmp2_walk($host, 'tridatunet', ".1.3.6.1.4.1.3902.1082.500.10.2.3.8.1.7", '100000000000', 10);
        $phase = snmp2_walk($host, 'tridatunet', ".1.3.6.1.4.1.3902.1012.3.28.2.1.4", '100000000000', 10);

        // Ambil jumlah ONT dari desc
        $ontCount = count($desc);
        $descValues = array_values($desc); // Simpan hanya nilai deskripsi

        // Tambahkan pop berdasarkan host
        $pop = ($host === '10.100.11.3') ? 'kerobokan' : 'babakan';

        // Buat array pop dengan jumlah yang sama
        $popArray = array_fill(0, $ontCount, $pop);

        $allRecords[$host] = [
          'ponid' => array_keys($desc),
          'onurx' => $onurx,
          'onutx' => $onutx,
          'desc' => $desc,
          'lastonline' => $lastonline,
          'lastoffline' => $lastoffline,
          'reason' => $reason,
          'pop' => $popArray, // Tambahkan lokasi POP
          'phase' => $phase
        ];

        // Persiapkan data untuk database
        foreach ($descValues as $index => $description) {
          $records[] = [
              'ponid' => array_keys($desc)[$index] ?? null,
              'description' => $description,
              'onurx' => $onurx[$index] ?? null,
              'onutx' => $onutx[$index] ?? null,
              'lastonline' => $lastonline[$index] ?? null,
              'lastoffline' => $lastoffline[$index] ?? null,
              'reason' => $reason[$index] ?? null,
              'pop' => $popArray[$index] ?? null,
              'phase' => $phase[$index] ?? null,
          ];
        }
      }
      // Upsert data ke database untuk menghindari duplikasi
      if (!empty($records)) {
        SnmpData::upsert($records, ['ponid'], ['description', 'onurx', 'onutx', 'lastonline', 'lastoffline', 'reason', 'pop', 'phase']);
      }
      Log::info("Processed host: $host, Records: " . count($records));
    }
}
