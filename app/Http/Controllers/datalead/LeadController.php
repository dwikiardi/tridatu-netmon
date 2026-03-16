<?php

namespace App\Http\Controllers\datalead;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CalonCustomer;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('content.datalead.table-lead');
    }

    /**
     * Show the lead data for DataTable
     */
    public function show(Request $request)
    {
        $searchValue = $request->input('search.value');
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        // Filter based on your previous logic (only prospects and only 'normal' or 'project' type)
        $query = CalonCustomer::with('sales')
            ->whereIn('tipe_survey', ['normal', 'project']);

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('nama', 'LIKE', "%{$searchValue}%")
                  ->orWhere('telepon', 'LIKE', "%{$searchValue}%")
                  ->orWhere('alamat', 'LIKE', "%{$searchValue}%")
                  ->orWhereHas('sales', function($sq) use ($searchValue) {
                      $sq->where('name', 'LIKE', "%{$searchValue}%");
                  });
            });
        }

        $recordsTotal = CalonCustomer::whereIn('tipe_survey', ['normal', 'project'])->count();
        $recordsFiltered = $query->count();

        // Ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = ['id', 'nama', 'telepon', 'alamat', 'sales_id', 'status'];

        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $data = $query->skip($start)->take($length)->get();

        $transformedData = $data->map(function($lead) {
            return [
                'id' => $lead->id,
                'survey_id' => 'Survey-' . str_pad($lead->id, 3, '0', STR_PAD_LEFT),
                'nama' => $lead->nama,
                'telepon' => $lead->telepon ?? '-',
                'alamat' => $lead->alamat,
                'sales' => $lead->sales ? $lead->sales->name : '-',
                'status' => $lead->status,
                'tipe_survey' => $lead->tipe_survey,
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
     * Get detail of a lead including ticket history
     */
    public function detail(Request $request)
    {
        $lead = CalonCustomer::with(['sales', 'tickets' => function($q) {
            $q->orderBy('created_at', 'desc');
        }])->findOrFail($request->id);

        $formattedTickets = $lead->tickets->map(function($ticket) {
            $tanggal = $ticket->tanggal_kunjungan ? $ticket->tanggal_kunjungan->format('dmY') : $ticket->created_at->format('dmY');
            $jam = $ticket->jam ? date('Hi', strtotime($ticket->jam)) : $ticket->created_at->format('Hi');
            $no = str_pad($ticket->id, 3, '0', STR_PAD_LEFT);
            $ticketNo = "TDN-{$tanggal}-{$jam}-{$no}";

            return [
                'ticket_no' => $ticketNo,
                'jenis' => $ticket->jenis,
                'status' => $ticket->status,
                'kendala' => $ticket->kendala,
                'created_at' => $ticket->created_at->format('d-m-Y H:i'),
            ];
        });

        return response()->json([
            'id' => $lead->id,
            'survey_id' => 'Survey-' . str_pad($lead->id, 3, '0', STR_PAD_LEFT),
            'nama' => $lead->nama,
            'telepon' => $lead->telepon,
            'alamat' => $lead->alamat,
            'koordinat' => $lead->koordinat,
            'sales' => $lead->sales ? $lead->sales->name : '-',
            'status' => $lead->status,
            'tipe_survey' => $lead->tipe_survey,
            'tickets' => $formattedTickets
        ]);
    }

    /**
     * Store a newly created lead
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string',
            'telepon' => 'nullable|string',
            'alamat' => 'required|string',
            'koordinat' => 'required|string',
            'sales_id' => 'required|exists:users,id',
            'tipe_survey' => 'required|in:normal,project',
        ]);

        $validated['status'] = 'prospek';

        $lead = CalonCustomer::create($validated);

        return response()->json(['message' => 'Lead created successfully', 'id' => $lead->id]);
    }

    /**
     * Update lead data
     */
    public function update(Request $request)
    {
        $lead = CalonCustomer::findOrFail($request->id);
        $lead->update($request->all());
        return response()->json(['message' => 'Lead updated successfully']);
    }

    /**
     * Delete a lead
     */
    public function destroy(Request $request)
    {
        $lead = CalonCustomer::findOrFail($request->id);
        // Hapus semua histori ticket terkait lead ini
        $lead->tickets()->delete();
        // Hapus data lead
        $lead->delete();
        return response()->json(['message' => 'Lead dan semua histori ticket berhasil dihapus bersih']);
    }
}
