<?php

namespace App\Http\Controllers\datacust;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerLog;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
      return view('content.datacust.table-customer');
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        
        // Auto-generate CID if not provided
        if (empty($data['cid'])) {
            $currentMonth = now()->format('m');
            $currentYear = now()->format('y');
            $monthYearSuffix = $currentMonth . $currentYear;
            
            // Generate CID: XXXX-MMYY format
            $lastCustomer = Customer::where('cid', 'REGEXP', '^[0-9]{4}-[0-9]{4}$')
                ->orderByRaw('CAST(SUBSTRING(cid, 1, 4) AS UNSIGNED) DESC')
                ->first();

            $lastNumber = 0;
            if ($lastCustomer) {
                $cidParts = explode('-', $lastCustomer->cid);
                if (count($cidParts) >= 1) {
                    $lastNumber = intval($cidParts[0]);
                }
            }

            $newCid = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT) . '-' . $monthYearSuffix;

            // Double check: pastikan CID belum ada
            while (Customer::where('cid', $newCid)->exists()) {
                $lastNumber++;
                $newCid = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT) . '-' . $monthYearSuffix;
            }
            
            $data['cid'] = $newCid;
        } else {
            // Check if manually provided CID already exists
            if (Customer::where('cid', $data['cid'])->exists()) {
                return response()->json([
                    'message' => 'CID sudah ada di database. Gunakan CID yang berbeda atau gunakan Edit untuk mengubah data customer yang sudah ada.'
                ], 422);
            }
        }
        
        $customer = Customer::create($data);
        
        return response()->json(['message' => 'Customer created successfully', 'cid' => $customer->cid]);
    }

    public function detail(Request $request)
    {
        $customer = Customer::findOrFail($request->cid);

      return response()->json([
          'cid'             => $customer->cid,
          'nama'            => $customer->nama,
          'email'           => $customer->email,
          'sales'           => $customer->sales,
          'pop'             => $customer->pop,
          'packet'          => $customer->packet,
          'alamat'          => $customer->alamat,
          'coordinate_maps' => $customer->coordinate_maps,
          'pic_it'          => $customer->pic_it,
          'no_it'           => $customer->no_it,
          'pic_finance'     => $customer->pic_finance,
          'no_finance'      => $customer->no_finance,
          'pembayaran_perbulan' => $customer->pembayaran_perbulan,
          'pembayaran_perbulan_formatted' => $customer->pembayaran_perbulan_formatted,
          'setup_fee'       => $customer->setup_fee,
          'setup_fee_formatted' => $customer->setup_fee_formatted,
          'status'          => $customer->status,
          'note'            => $customer->note,
          'tgl_customer_aktif' => $customer->tgl_customer_aktif,
          'billing_aktif'   => $customer->billing_aktif,
      ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
      $searchValue = $request->input('search.value');
      $start = $request->input('start', 0);
      $length = $request->input('length', 10);

      // Total Data Sebelum Filter
      $recordsTotal = Customer::count();

      // Query dasar
      $query = Customer::query();

      // Filter pencarian
      if (!empty($searchValue)) {
          // Extract numeric value from search for pembayaran_perbulan
          $numericSearch = preg_replace('/[^0-9]/', '', $searchValue);

          $query->where(function ($q) use ($searchValue, $numericSearch) {
              $q->where('cid', 'LIKE', "%{$searchValue}%")
                ->orWhere('nama', 'LIKE', "%{$searchValue}%")
                ->orWhere('sales', 'LIKE', "%{$searchValue}%")
                ->orWhere('pop', 'LIKE', "%{$searchValue}%")
                ->orWhere('email', 'LIKE', "%{$searchValue}%")
                ->orWhere('packet', 'LIKE', "%{$searchValue}%")
                ->orWhere('alamat', 'LIKE', "%{$searchValue}%")
                ->orWhere('pic_it', 'LIKE', "%{$searchValue}%")
                ->orWhere('pic_finance', 'LIKE', "%{$searchValue}%")
                ->orWhere('tgl_customer_aktif', 'LIKE', "%{$searchValue}%")
                ->orWhere('billing_aktif', 'LIKE', "%{$searchValue}%")
                ->orWhere('status', 'LIKE', "%{$searchValue}%");

              // Search pembayaran_perbulan by numeric value - cast to CHAR for LIKE search
              if (!empty($numericSearch)) {
                  $q->orWhereRaw('CAST(pembayaran_perbulan AS CHAR) LIKE ?', ["%{$numericSearch}%"]);
                  $q->orWhereRaw('CAST(setup_fee AS CHAR) LIKE ?', ["%{$numericSearch}%"]);
              }
          });
      }

      // Total data setelah filter
      $recordsFiltered = $query->count();

      // Ordering
      $orderColumnIndex = $request->input('order.0.column', 0);
      $orderDir = $request->input('order.0.dir', 'asc');
      $columns = [
          'cid', 
          'nama', 
          'sales', 
          'pop', 
          'packet', 
          'alamat', 
          'pembayaran_perbulan', 
          'setup_fee', 
          'tgl_customer_aktif', 
          'billing_aktif', 
          'status'
      ];

      if (isset($columns[$orderColumnIndex])) {
          $query->orderBy($columns[$orderColumnIndex], $orderDir);
      }

      // Pagination & Fetch Data
      $data = $query->skip($start)->take($length)->get();

      // Transform data to include formatted values
      $transformedData = $data->map(function($customer) {
          return [
              'cid' => $customer->cid,
              'nama' => $customer->nama,
              'email' => $customer->email,
              'sales' => $customer->sales,
              'pop' => $customer->pop ?? '-',
              'packet' => $customer->packet,
              'alamat' => $customer->alamat,
              'pic_it' => $customer->pic_it,
              'no_it' => $customer->no_it,
              'pic_finance' => $customer->pic_finance,
              'no_finance' => $customer->no_finance,
              'coordinate_maps' => $customer->coordinate_maps,
              'pembayaran_perbulan' => $customer->pembayaran_perbulan,
              'pembayaran_perbulan_formatted' => $customer->pembayaran_perbulan_formatted,
              'setup_fee' => $customer->setup_fee,
              'setup_fee_formatted' => $customer->setup_fee_formatted,
              'status' => $customer->status,
              'note' => $customer->note,
              'tgl_customer_aktif' => $customer->tgl_customer_aktif,
              'billing_aktif' => $customer->billing_aktif,
          ];
      });

      return response()->json([
          "draw" => intval($request->input('draw')),
          "recordsTotal" => $recordsTotal,
          "recordsFiltered" => $recordsFiltered,
          "data" => $transformedData
      ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $customer = Customer::findOrFail($request->cid);
        $customer->update($request->all());
        return response()->json(['message' => 'Customer updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $customer = Customer::where('cid', $request->cid)->firstOrFail();
        $customer->delete();
        return response()->json(['message' => 'Customer deleted successfully']);
    }

    /**
     * Get unique POP values for autocomplete dropdown
     */
    public function getPops()
    {
        $pops = Customer::whereNotNull('pop')
            ->where('pop', '!=', '')
            ->distinct()
            ->pluck('pop')
            ->sort()
            ->values();
        
        return response()->json($pops);
    }
}
