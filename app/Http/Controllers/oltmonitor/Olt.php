<?php

namespace App\Http\Controllers\oltmonitor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ndum\Laravel\Facades\Snmp;
use App\Models\SnmpData;
use Illuminate\Support\Facades\Log;

class Olt extends Controller
{
  public function index()
  {
    return view('content.oltmonitor.table-olt');
  }

  public function dataOnt(Request $request)
  {
    $searchValue = $request->input('search.value');
    $start = $request->input('start', 0);
    $length = $request->input('length', 10);

    // Query dasar tanpa limit
    $query = SnmpData::select('id', 'description', 'ponid', 'onurx', 'onutx', 'lastonline', 'lastoffline','reason','pop');

    // Total Data Sebelum Filter
    $recordsTotal = $query->count();

    // Filter pencarian
    if (!empty($searchValue)) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('description', 'LIKE', "%{$searchValue}%")
              ->orWhere('ponid', 'LIKE', "%{$searchValue}%")
              ->orWhere('onurx', 'LIKE', "%{$searchValue}%")
              ->orWhere('onutx', 'LIKE', "%{$searchValue}%")
              ->orWhere('lastonline', 'LIKE', "%{$searchValue}%")
              ->orWhere('lastoffline', 'LIKE', "%{$searchValue}%")
              ->orWhere('reason', 'LIKE', "%{$searchValue}%")
              ->orWhere('pop', 'LIKE', "%{$searchValue}%");
        });
    }

    // Total data setelah filter
    $recordsFiltered = $query->count();

    // Pagination & Fetch Data
    $data = $query->skip($start)->take($length)->get();

    return response()->json([
        "draw" => intval($request->input('draw')),
        "recordsTotal" => $recordsTotal,
        "recordsFiltered" => $recordsFiltered,
        "data" => $data
    ]);
  }

  public function signalOnt(Request $request)
  {
    // Tangkap data dari request
    $oid = $request->input('oid'); // atau $request->oid
    $pop = $request->input(key: 'pop');

    // Debugging: Cek apakah OID diterima
    if (!$oid) {
        return response()->json(['error' => 'OID not found'], 400);
    }

    // $rawonurx = snmp2_walk('10.100.11.3', 'tridatunet', $oid, '100000000000', 10);
    $oid = $oid . '.1';

    if ($pop == 'kerobokan'){
      $rawonurx = snmp2_get('10.100.11.3', 'tridatunet', $oid, '100000000000', 10);
      // ✅ Hapus teks "INTEGER: " dari hasil SNMP
      $rawonurx = preg_replace('/[^0-9\-.]/', '', trim($rawonurx));
      $onurx = ((float) $rawonurx * 0.002) - 30;
      // ✅ Tetapkan kondisi No Signal jika di luar batas (-50 hingga 50)
      if ($onurx < -50 || $onurx > 50) {
          $onurx = 'No Signal';
      }
    } else {
      $rawonurx = snmp2_get('10.100.12.3', 'tridatunet', $oid, '100000000000', 10);
      // ✅ Hapus teks "INTEGER: " dari hasil SNMP
      $rawonurx = preg_replace('/[^0-9\-.]/', '', trim($rawonurx));
      $onurx = ((float) $rawonurx * 0.002) - 30;
      // ✅ Tetapkan kondisi No Signal jika di luar batas (-50 hingga 50)
      if ($onurx < -50 || $onurx > 50) {
        $onurx = 'No Signal';
      }
    }

    return response()->json([
      'data' => $onurx,
      'pop' => $pop // Menambahkan key untuk $pop
    ]);
  }
}
