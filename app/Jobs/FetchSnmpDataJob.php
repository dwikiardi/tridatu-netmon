<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SnmpData;
use Illuminate\Support\Facades\Log;
use Exception;

class FetchSnmpDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 1. CONFIGURATION:
     * Set timeout Job Laravel (dalam detik).
     * Kita set 10 menit (600 detik) agar aman untuk proses 3-5 menit.
     */
    public $timeout = 600;

    // Retry job jika gagal (opsional, set 1 kali saja jika prosesnya sangat berat)
    public $tries = 1;

    public function handle()
    {
        /**
         * 2. CONFIGURATION:
         * Set timeout Script PHP agar tidak time limit.
         */
        ini_set('max_execution_time', 600); 
        ini_set('memory_limit', '512M'); // Naikkan memory jika data ribuan

        // Konfigurasi SNMP
        snmp_set_quick_print(true);
        snmp_set_oid_numeric_print(true);
        snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

        // Timeout SNMP dalam MIKRODETIK.
        // 5 menit = 300 detik = 300,000,000 mikrodetik.
        $snmpTimeout = 300000000; 
        $snmpRetry = 2; // Coba ulang 2x jika timeout

        $hosts = ['10.100.11.3', '10.100.12.3'];

        foreach ($hosts as $host) {
            $records = []; 
            Log::info("START Processing Host: $host at " . now());

            try {
                // --- STEP 1: PENGAMBILAN DATA (SNMP) ---
                
                // Ambil Description dulu sebagai acuan utama
                $desc = @snmp2_real_walk($host, 'tridatunet', ".1.3.6.1.4.1.3902.1012.3.28.1.1.3", $snmpTimeout, $snmpRetry);

                if ($desc === false || empty($desc)) {
                    Log::error("GAGAL SNMP: Tidak bisa koneksi ke host $host atau data kosong.");
                    continue; // Skip ke host berikutnya
                }

                // Ambil data pendukung lainnya
                // Menggunakan operator ?: [] agar jika timeout return array kosong, tidak error
                $onurx = @snmp2_walk($host, 'tridatunet', "1.3.6.1.4.1.3902.1012.3.50.12.1.1.10", $snmpTimeout, $snmpRetry) ?: [];
                $onutx = @snmp2_walk($host, 'tridatunet', ".1.3.6.1.4.1.3902.1012.3.50.12.1.1.14", $snmpTimeout, $snmpRetry) ?: [];
                $lastonline = @snmp2_walk($host, 'tridatunet', ".1.3.6.1.4.1.3902.1012.3.28.2.1.5", $snmpTimeout, $snmpRetry) ?: [];
                $lastoffline = @snmp2_walk($host, 'tridatunet', ".1.3.6.1.4.1.3902.1012.3.28.2.1.6", $snmpTimeout, $snmpRetry) ?: [];
                $reason = @snmp2_walk($host, 'tridatunet', ".1.3.6.1.4.1.3902.1082.500.10.2.3.8.1.7", $snmpTimeout, $snmpRetry) ?: [];
                $phase = @snmp2_walk($host, 'tridatunet', ".1.3.6.1.4.1.3902.1012.3.28.2.1.4", $snmpTimeout, $snmpRetry) ?: [];

                $descValues = array_values($desc);
                $descKeys = array_keys($desc);
                $pop = ($host === '10.100.11.3') ? 'kerobokan' : 'babakan';

                // Mapping Data
                foreach ($descValues as $index => $description) {
                    $records[] = [
                        'ponid'       => $descKeys[$index] ?? null,
                        'description' => $description,
                        'onurx'       => $onurx[$index] ?? 0,
                        'onutx'       => $onutx[$index] ?? 0,
                        'lastonline'  => $lastonline[$index] ?? null,
                        'lastoffline' => $lastoffline[$index] ?? null,
                        'reason'      => $reason[$index] ?? null,
                        'pop'         => $pop,
                        'phase'       => $phase[$index] ?? null,
                    ];
                }

                // --- STEP 2: PENYIMPANAN DATABASE ---
                
                if (!empty($records)) {
                    try {
                        // Proses Upsert (Insert or Update)
                        SnmpData::upsert(
                            $records, 
                            ['ponid'], // Kolom unique (primary key logic)
                            ['description', 'onurx', 'onutx', 'lastonline', 'lastoffline', 'reason', 'pop', 'phase'] // Kolom yang diupdate
                        );

                        Log::info("BERHASIL SAVE: Host $host. Total Records: " . count($records));

                    } catch (Exception $dbError) {
                        // Tangkap Error Database Spesifik
                        Log::error("GAGAL SAVE DB Host $host. Error: " . $dbError->getMessage());
                    }
                } else {
                    Log::warning("DATA KOSONG: Host $host berhasil di-scan tapi tidak ada record yang terbentuk.");
                }

            } catch (Exception $e) {
                // Tangkap Error Umum (Script crash, dll)
                Log::error("CRITICAL ERROR pada Host $host: " . $e->getMessage());
            }
        }
        
        Log::info("Job Selesai.");
    }
}