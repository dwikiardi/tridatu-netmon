<?php

namespace App\Http\Controllers\report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomerLog;
use App\Models\Customer;

class CustomerLogController extends Controller
{
    public function index()
    {
        return view('content.report.table-customer-log');
    }

    public function show(Request $request)
    {
        $searchValue = $request->input('search.value');
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $customerCid = $request->input('customer_cid'); // filter by customer

        // Total Data Sebelum Filter
        $recordsTotal = CustomerLog::count();

        // Query dasar
        $query = CustomerLog::with(['customer', 'user']);

        // Filter by customer if specified
        if ($customerCid) {
            $query->where('customer_cid', $customerCid);
        }

        // Filter pencarian
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('customer_cid', 'LIKE', "%{$searchValue}%")
                  ->orWhere('action', 'LIKE', "%{$searchValue}%")
                  ->orWhere('field_changed', 'LIKE', "%{$searchValue}%")
                  ->orWhere('old_value', 'LIKE', "%{$searchValue}%")
                  ->orWhere('new_value', 'LIKE', "%{$searchValue}%")
                  ->orWhere('changed_by', 'LIKE', "%{$searchValue}%")
                  ->orWhereHas('customer', function($q) use ($searchValue) {
                      $q->where('nama', 'LIKE', "%{$searchValue}%");
                  });
            });
        }

        // Total data setelah filter
        $recordsFiltered = $query->count();

        // Ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['id', 'customer_cid', 'action', 'field_changed', 'old_value', 'new_value', 'changed_by', 'created_at'];

        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination & Fetch Data
        $data = $query->skip($start)->take($length)->get();

        // Format data
        $formattedData = $data->map(function ($log) {
            return [
                'id' => $log->id,
                'customer_cid' => $log->customer_cid,
                'customer_nama' => $log->customer ? $log->customer->nama : '-',
                'action' => $log->action,
                'field_changed' => $log->field_changed ?? '-',
                'old_value' => $log->old_value ?? '-',
                'new_value' => $log->new_value ?? '-',
                'changed_by' => $log->changed_by,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $formattedData
        ]);
    }

    public function getCustomers()
    {
        $customers = Customer::select('cid', 'nama')->orderBy('nama')->get();
        return response()->json($customers);
    }
}
