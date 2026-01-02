<?php

namespace App\Http\Controllers\report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\ReportFilter;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $teknisi = \App\Models\User::where('jabatan', 'teknisi')->orderBy('name')->get();
        $sales = \App\Models\User::where('jabatan', 'sales')->orderBy('name')->get();
        $customers = \App\Models\Customer::orderBy('nama')->get();

        return view('content.report.table-report', compact('teknisi', 'sales', 'customers'));
    }

    /**
     * Get Report Data Customer dengan filter
     */
    public function getReportCustomer(Request $request)
    {
        $searchValue = $request->input('search.value');
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        // Filter parameters
        $packet = $request->input('packet');
        $minBayar = $request->input('min_bayar');
        $maxBayar = $request->input('max_bayar');
        $minSpeed = $request->input('min_speed');
        $maxSpeed = $request->input('max_speed');
        $status = $request->input('status');
        $sales = $request->input('sales');
        $pop = $request->input('pop');
        $minSetupFee = $request->input('min_setup_fee');
        $maxSetupFee = $request->input('max_setup_fee');
        $tglAktifFrom = $request->input('tgl_aktif_from');
        $tglAktifTo = $request->input('tgl_aktif_to');
        $billingAktifFrom = $request->input('billing_aktif_from');
        $billingAktifTo = $request->input('billing_aktif_to');

        // Total Data Sebelum Filter
        $recordsTotal = Customer::count();

        // Query dasar
        $query = Customer::query();

        // Filter by packet - exact match untuk menghindari "50" match dengan "150"
        if (!empty($packet)) {
            $query->where('packet', '=', $packet);
        }

        // Filter by pembayaran (monthly payment)
        if ($minBayar) {
            $query->where('pembayaran_perbulan', '>=', $minBayar);
        }
        if ($maxBayar) {
            $query->where('pembayaran_perbulan', '<=', $maxBayar);
        }

        // Filter by speed (parsing from packet field e.g., "100 Mbps")
        if ($minSpeed || $maxSpeed) {
            $query->where(function ($q) use ($minSpeed, $maxSpeed) {
                if ($minSpeed) {
                    $q->whereRaw("CAST(SUBSTRING_INDEX(packet, ' ', 1) AS UNSIGNED) >= ?", [$minSpeed]);
                }
                if ($maxSpeed) {
                    $q->whereRaw("CAST(SUBSTRING_INDEX(packet, ' ', 1) AS UNSIGNED) <= ?", [$maxSpeed]);
                }
            });
        }

        // Filter by status
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Filter by sales
        if (!empty($sales)) {
            $query->where('sales', 'like', "%{$sales}%");
        }

        // Filter by POP
        if (!empty($pop)) {
            $query->where('pop', '=', $pop);
        }

        // Filter by setup fee range
        if (!empty($minSetupFee)) {
            $query->where('setup_fee', '>=', $minSetupFee);
        }
        if (!empty($maxSetupFee)) {
            $query->where('setup_fee', '<=', $maxSetupFee);
        }

        // Filter by tanggal aktif range
        if (!empty($tglAktifFrom)) {
            $query->where('tgl_customer_aktif', '>=', $tglAktifFrom);
        }
        if (!empty($tglAktifTo)) {
            $query->where('tgl_customer_aktif', '<=', $tglAktifTo);
        }

        // Filter by billing aktif (date range dari kolom billing_aktif)
        if (!empty($billingAktifFrom)) {
            $query->where('billing_aktif', '>=', $billingAktifFrom);
        }
        if (!empty($billingAktifTo)) {
            $query->where('billing_aktif', '<=', $billingAktifTo);
        }

        // Filter pencarian
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('cid', 'LIKE', "%{$searchValue}%")
                  ->orWhere('nama', 'LIKE', "%{$searchValue}%")
                  ->orWhere('email', 'LIKE', "%{$searchValue}%")
                  ->orWhere('alamat', 'LIKE', "%{$searchValue}%")
                  ->orWhere('packet', 'LIKE', "%{$searchValue}%");
            });
        }

        // Total data setelah filter
        $recordsFiltered = $query->count();

        // Get data dengan pagination
        $data = $query->orderBy('created_at', 'desc')
                     ->offset($start)
                     ->limit($length)
                     ->get();

        // Transform data
        $data = $data->map(function ($customer) {
            return [
                'cid' => $customer->cid,
                'nama' => $customer->nama,
                'email' => $customer->email,
                'alamat' => $customer->alamat,
                'coordinate_maps' => $customer->coordinate_maps ?? '-',
                'packet' => $customer->packet,
                'pembayaran_perbulan' => $customer->pembayaran_perbulan_formatted,
                'pop' => $customer->pop ?? '-',
                'setup_fee_formatted' => $customer->setup_fee_formatted ?? '-',
                'status' => $customer->status,
                'sales' => $customer->sales,
                'pic_it' => $customer->pic_it ?? '-',
                'no_it' => $customer->no_it ?? '-',
                'pic_finance' => $customer->pic_finance ?? '-',
                'no_finance' => $customer->no_finance ?? '-',
                'tgl_customer_aktif' => $customer->tgl_customer_aktif ?? '-',
                'billing_aktif' => $customer->billing_aktif ?? '-',
                'note' => $customer->note ?? '-',
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
    }

    /**
     * Get Report Data Maintenance (dari Ticketing)
     */
    public function getReportMaintenance(Request $request)
    {
        $searchValue = $request->input('search.value');
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        // Filter parameters
        $picTeknisi = $request->input('pic_teknisi');
        $customerNama = $request->input('customer_nama');
        $status = $request->input('status');
        $salesId = $request->input('sales_id');

        // Total Data Sebelum Filter
        $recordsTotal = Ticket::count();

        // Query dasar dengan LEFT JOIN untuk customer dan calon_customer
        $query = Ticket::leftJoin('customers', 'tickets.cid', '=', 'customers.cid')
                       ->leftJoin('calon_customers', 'tickets.calon_customer_id', '=', 'calon_customers.id')
                       ->select('tickets.*',
                               'customers.nama as customer_nama_real',
                               'customers.sales as customer_sales',
                               'calon_customers.nama as calon_nama_real');

        // Filter by teknisi
        if (!empty($picTeknisi)) {
            $query->where('tickets.pic_teknisi', 'like', "%{$picTeknisi}%");
        }

        // Filter by sales
        if (!empty($salesId)) {
            $query->where('customers.sales', $salesId);
        }

        // Filter by customer nama
        if (!empty($customerNama)) {
            $query->where(function ($q) use ($customerNama) {
                $q->where('customers.nama', 'like', "%{$customerNama}%")
                  ->orWhere('calon_customers.nama', 'like', "%{$customerNama}%");
            });
        }

        // Filter by status
        if (!empty($status)) {
            $query->where('tickets.status', $status);
        }

        // Filter by date range
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (!empty($dateFrom)) {
            $query->whereDate('tickets.tanggal_kunjungan', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('tickets.tanggal_kunjungan', '<=', $dateTo);
        }

        // Filter pencarian
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('tickets.cid', 'LIKE', "%{$searchValue}%")
                  ->orWhere('tickets.pic_teknisi', 'LIKE', "%{$searchValue}%")
                  ->orWhere('tickets.kendala', 'LIKE', "%{$searchValue}%")
                  ->orWhere('tickets.solusi', 'LIKE', "%{$searchValue}%")
                  ->orWhere('customers.nama', 'LIKE', "%{$searchValue}%")
                  ->orWhere('calon_customers.nama', 'LIKE', "%{$searchValue}%");
            });
        }

        // Total data setelah filter
        $recordsFiltered = $query->count();

        // Get data dengan pagination
        $data = $query->orderBy('tickets.tanggal_kunjungan', 'desc')
                     ->offset($start)
                     ->limit($length)
                     ->get();

        // Eager load replies for Hasil calculation
        $data->load(['replies' => function($q) {
            $q->orderBy('created_at', 'desc');
        }]);

        // Get maintenance counts for all CIDs in one query
        $cids = $data->pluck('cid')->filter()->unique();
        $maintenanceCounts = Ticket::whereIn('cid', $cids)
            ->select('cid', \DB::raw('COUNT(*) as count'))
            ->groupBy('cid')
            ->pluck('count', 'cid');

        // Transform data - select customer_nama based on jenis
        $data = $data->map(function ($ticket) use ($maintenanceCounts) {
            // Generate ticket no: TDN-DDMMYY-HHMM-NO
            $tanggal = $ticket->tanggal_kunjungan ? $ticket->tanggal_kunjungan->format('dmY') : date('dmY');
            $jam = $ticket->jam ? date('Hi', strtotime($ticket->jam)) : '0000';
            $no = str_pad($ticket->id, 3, '0', STR_PAD_LEFT);
            $ticketNo = "TDN-{$tanggal}-{$jam}-{$no}";

            return [
                'ticket_no' => $ticketNo,
                'id' => $ticket->id,
                'cid' => $ticket->jenis === 'survey' ? 'TDNSurvey' : ($ticket->cid ?? '-'),
                'customer_nama' => $ticket->jenis === 'survey' ? ($ticket->calon_nama_real ?? '-') : ($ticket->customer_nama_real ?? '-'),
                'jenis' => $ticket->jenis,
                'pic_teknisi' => $ticket->pic_teknisi,
                'tanggal_kunjungan' => $ticket->tanggal_kunjungan ? \Carbon\Carbon::parse($ticket->tanggal_kunjungan)->format('d-m-Y') : '-',
                'kendala' => $ticket->kendala,
                'hasil' => $ticket->replies->where('update_status', 'selesai')->first() 
                            ? $ticket->replies->where('update_status', 'selesai')->first()->reply 
                            : ($ticket->replies->first() ? $ticket->replies->first()->reply : ($ticket->solusi ?? '-')),
                'sla_remote_minutes' => $ticket->sla_remote_minutes,
                'sla_onsite_minutes' => $ticket->sla_onsite_minutes,
                'sla_total_minutes' => $ticket->sla_total_minutes,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'maintenance_count' => $maintenanceCounts[$ticket->cid] ?? 0
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
    }

    /**
     * Get Summary Report Customer
     */
    public function getSummaryCustomer()
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'Aktif')->count();
        $inactiveCustomers = Customer::whereIn('status', ['Isolir', 'Terminate'])->count();
        $totalRevenue = Customer::where('status', 'Aktif')->sum('pembayaran_perbulan');

        return response()->json([
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'inactive_customers' => $inactiveCustomers,
            'total_revenue' => 'Rp. ' . number_format($totalRevenue, 0, ',', '.'),
        ]);
    }

    /**
     * Get Summary Report Maintenance
     */
    public function getSummaryMaintenance(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Base Query for Summary
        $query = Ticket::query();
        if ($dateFrom) $query->whereDate('tanggal_kunjungan', '>=', $dateFrom);
        if ($dateTo) $query->whereDate('tanggal_kunjungan', '<=', $dateTo);

        $totalTickets = (clone $query)->count();
        $completedTickets = (clone $query)->where('status', 'selesai')->count();
        $pendingTickets = (clone $query)->where('status', '!=', 'selesai')->count();

        // High-fidelity visit counting from ticket_replies for accuracy
        $repliesQuery = TicketReply::where(function($q) {
            $q->whereNotNull('teknisi_id')->orWhereNotNull('teknisi_ids');
        });
            
        if ($dateFrom || $dateTo) {
            $repliesQuery->whereHas('ticket', function($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) $q->whereDate('tanggal_kunjungan', '>=', $dateFrom);
                if ($dateTo) $q->whereDate('tanggal_kunjungan', '<=', $dateTo);
            });
        }

        $allReplies = $repliesQuery->get();
        $teknisiVisitCounts = [];

        foreach ($allReplies as $reply) {
            $ids = [];
            if ($reply->teknisi_ids && is_array($reply->teknisi_ids)) {
                $ids = $reply->teknisi_ids;
            } elseif ($reply->teknisi_id) {
                $ids = [$reply->teknisi_id];
            }

            foreach ($ids as $id) {
                $teknisiVisitCounts[$id] = ($teknisiVisitCounts[$id] ?? 0) + 1;
            }
        }

        // Get names for the IDs
        $technicianNames = User::whereIn('id', array_keys($teknisiVisitCounts))->pluck('name', 'id');
        
        $visitData = [];
        foreach ($teknisiVisitCounts as $id => $count) {
            $visitData[] = [
                'name' => $technicianNames[$id] ?? 'Unknown',
                'visits' => $count
            ];
        }

        // Sort by visits desc
        usort($visitData, function($a, $b) {
            return $b['visits'] - $a['visits'];
        });

        // Most visited customer 
        $customerQuery = Ticket::leftJoin('customers', 'tickets.cid', '=', 'customers.cid')
            ->leftJoin('calon_customers', 'tickets.calon_customer_id', '=', 'calon_customers.id');
            
        if ($dateFrom) $customerQuery->whereDate('tickets.tanggal_kunjungan', '>=', $dateFrom);
        if ($dateTo) $customerQuery->whereDate('tickets.tanggal_kunjungan', '<=', $dateTo);

        $mostVisited = $customerQuery->select(
                DB::raw("COALESCE(customers.nama, calon_customers.nama, 'Unknown') as customer_name"),
                DB::raw('COUNT(*) as visit_count')
            )
            ->groupBy('customer_name')
            ->orderBy('visit_count', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_tickets' => $totalTickets,
            'completed_tickets' => $completedTickets,
            'pending_tickets' => $pendingTickets,
            'visit_per_teknisi' => array_slice($visitData, 0, 10), // Return top 10 for chart
            'most_visited_customers' => $mostVisited,
        ]);
    }

    /**
     * Get Sales Users for dropdown filter
     */
    public function getSalesUsers()
    {
        $users = \App\Models\User::select('id', 'name', 'email')
            ->where('jabatan', 'sales')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $users]);
    }

    /**
     * Save filter preference
     */
    public function saveFilterPreference(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:customer,maintenance',
            'filters' => 'required|array',
        ]);

        // Check if filter with same name and type exists for this user
        $filter = ReportFilter::updateOrCreate(
            [
                'name' => $validated['name'],
                'type' => $validated['type'],
                'user_id' => auth()->id(),
            ],
            ['filters' => json_encode($validated['filters'])]
        );

        return response()->json([
            'success' => true,
            'message' => 'Filter berhasil disimpan',
            'filter' => $filter
        ]);
    }

    /**
     * Get saved filter preferences
     */
    public function getSavedFilters($type)
    {
        $filters = ReportFilter::where('user_id', auth()->id())
                               ->where('type', $type)
                               ->orderBy('created_at', 'desc')
                               ->get()
                               ->map(function ($filter) {
                                   return [
                                       'id' => $filter->id,
                                       'name' => $filter->name,
                                       'type' => $filter->type,
                                       'filters' => $filter->filters, // Already a JSON string
                                       'created_at' => $filter->created_at,
                                   ];
                               });

        return response()->json(['data' => $filters]);
    }

    /**
     * Delete saved filter
     */
    public function deleteFilter($id)
    {
        $filter = ReportFilter::where('id', $id)
                              ->where('user_id', auth()->id())
                              ->firstOrFail();

        $filter->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Export to Excel
     */
    public function exportExcel(Request $request)
    {
        $type = $request->input('type', 'customer');

        if ($type === 'customer') {
            return $this->exportCustomerExcel($request);
        } else {
            return $this->exportMaintenanceExcel($request);
        }
    }

    /**
     * Export Customer Data to Excel
     */
    private function exportCustomerExcel(Request $request)
    {
        // Query data dengan filters
        $query = Customer::query();

        // Filter by selected IDs if provided
        $selectedIdsParam = $request->input('selected_ids', '');
        if (!empty($selectedIdsParam)) {
            $selectedIds = explode(',', $selectedIdsParam);
            $query->whereIn('cid', $selectedIds);
        }

        $packet = $request->input('packet');
        $minBayar = $request->input('min_bayar');
        $maxBayar = $request->input('max_bayar');
        $status = $request->input('status');
        $sales = $request->input('sales');
        $pop = $request->input('pop');
        $minSetupFee = $request->input('min_setup_fee');
        $maxSetupFee = $request->input('max_setup_fee');
        $tglAktifFrom = $request->input('tgl_aktif_from');
        $tglAktifTo = $request->input('tgl_aktif_to');
        $billingAktifFrom = $request->input('billing_aktif_from');
        $billingAktifTo = $request->input('billing_aktif_to');

        // Get selected columns (default to all if not specified)
        $columnsParam = $request->input('columns', '');
        $selectedColumns = !empty($columnsParam) ? explode(',', $columnsParam) : [
            'cid', 'nama', 'email', 'alamat', 'coordinate_maps', 'packet',
            'pembayaran_perbulan', 'pop', 'setup_fee', 'status', 'sales', 'pic_it', 'no_it',
            'pic_finance', 'no_finance', 'tgl_customer_aktif', 'billing_aktif', 'note'
        ];

        if (!empty($packet)) {
            $query->where('packet', '=', $packet);
        }
        if ($minBayar) {
            $query->where('pembayaran_perbulan', '>=', $minBayar);
        }
        if ($maxBayar) {
            $query->where('pembayaran_perbulan', '<=', $maxBayar);
        }
        if (!empty($status)) {
            $query->where('status', $status);
        }
        if (!empty($sales)) {
            $query->where('sales', 'like', "%{$sales}%");
        }
        if (!empty($pop)) {
            $query->where('pop', '=', $pop);
        }
        if (!empty($minSetupFee)) {
            $query->where('setup_fee', '>=', $minSetupFee);
        }
        if (!empty($maxSetupFee)) {
            $query->where('setup_fee', '<=', $maxSetupFee);
        }
        if (!empty($tglAktifFrom)) {
            $query->where('tgl_customer_aktif', '>=', $tglAktifFrom);
        }
        if (!empty($tglAktifTo)) {
            $query->where('tgl_customer_aktif', '<=', $tglAktifTo);
        }
        if (!empty($billingAktifFrom)) {
            $query->where('billing_aktif', '>=', $billingAktifFrom);
        }
        if (!empty($billingAktifTo)) {
            $query->where('billing_aktif', '<=', $billingAktifTo);
        }

        $data = $query->with(['replies' => function($q) {
            $q->orderBy('created_at', 'desc');
        }])->get();

        // Column name mapping
        $columnNames = [
            'cid' => 'Customer ID',
            'nama' => 'Nama',
            'email' => 'Email',
            'alamat' => 'Alamat',
            'coordinate_maps' => 'Koordinat',
            'packet' => 'Packet',
            'pembayaran_perbulan' => 'Pembayaran/Bulan',
            'pop' => 'POP',
            'setup_fee' => 'Setup Fee',
            'status' => 'Status',
            'sales' => 'Sales',
            'pic_it' => 'PIC IT',
            'no_it' => 'No IT',
            'pic_finance' => 'PIC Finance',
            'no_finance' => 'No Finance',
            'tgl_customer_aktif' => 'Tgl Aktif',
            'billing_aktif' => 'Billing Aktif',
            'note' => 'Note'
        ];

        // Use Laravel Excel if installed, otherwise create CSV
        $filename = 'report_customer_' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $handle = fopen('php://memory', 'w');

        // Header row - only selected columns
        $headerRow = [];
        foreach ($selectedColumns as $col) {
            if (isset($columnNames[$col])) {
                $headerRow[] = $columnNames[$col];
            }
        }
        fputcsv($handle, $headerRow);

        // Data rows - only selected columns
        foreach ($data as $customer) {
            $row = [];
            foreach ($selectedColumns as $col) {
                switch ($col) {
                    case 'cid':
                        $row[] = $customer->cid;
                        break;
                    case 'nama':
                        $row[] = $customer->nama;
                        break;
                    case 'email':
                        $row[] = $customer->email;
                        break;
                    case 'alamat':
                        $row[] = $customer->alamat;
                        break;
                    case 'coordinate_maps':
                        $row[] = $customer->coordinate_maps ?? '';
                        break;
                    case 'packet':
                        $row[] = $customer->packet;
                        break;
                    case 'pembayaran_perbulan':
                        $row[] = $customer->pembayaran_perbulan;
                        break;
                    case 'status':
                        $row[] = $customer->status;
                        break;
                    case 'sales':
                        $row[] = $customer->sales;
                        break;
                    case 'pic_it':
                        $row[] = $customer->pic_it ?? '';
                        break;
                    case 'no_it':
                        $row[] = $customer->no_it ?? '';
                        break;
                    case 'pic_finance':
                        $row[] = $customer->pic_finance ?? '';
                        break;
                    case 'no_finance':
                        $row[] = $customer->no_finance ?? '';
                        break;
                    case 'tgl_customer_aktif':
                        $row[] = $customer->tgl_customer_aktif ?? '';
                        break;
                    case 'billing_aktif':
                        $row[] = $customer->billing_aktif ?? '';
                        break;
                    case 'note':
                        $row[] = $customer->note ?? '';
                        break;
                }
            }
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, $headers);
    }

    /**
     * Export Maintenance Data to Excel
     */
    private function exportMaintenanceExcel(Request $request)
    {
        $query = Ticket::with(['customer', 'calonCustomer']);

        // Filter by selected IDs if provided
        $selectedIdsParam = $request->input('selected_ids', '');
        if (!empty($selectedIdsParam)) {
            $selectedIds = explode(',', $selectedIdsParam);
            $query->whereIn('tickets.id', $selectedIds);
        }

        $picTeknisi = $request->input('pic_teknisi');
        $customerId = $request->input('cid');
        $status = $request->input('status');
        $jenis = $request->input('jenis');
        $salesId = $request->input('sales_id');
        $customerNama = $request->input('customer_nama');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Get selected columns (default to all if not specified)
        $columnsParam = $request->input('columns', '');
        $selectedColumns = !empty($columnsParam) ? explode(',', $columnsParam) : [
            'ticket_no', 'cid', 'customer_nama', 'jenis', 'pic_teknisi',
            'tanggal_kunjungan', 'kendala', 'hasil', 'status', 'priority'
        ];

        if (!empty($picTeknisi)) {
            $query->where('pic_teknisi', 'like', "%{$picTeknisi}%");
        }
        if (!empty($salesId)) {
            $query->whereHas('customer', function($q) use ($salesId) {
                $q->where('sales', $salesId);
            });
        }
        if (!empty($customerId)) {
            $query->where('cid', $customerId);
        }
        if (!empty($customerNama)) {
            $query->where(function ($q) use ($customerNama) {
                $q->whereHas('customer', function($sub) use ($customerNama) {
                    $sub->where('nama', 'like', "%{$customerNama}%");
                })->orWhereHas('calonCustomer', function($sub) use ($customerNama) {
                    $sub->where('nama', 'like', "%{$customerNama}%");
                });
            });
        }
        if (!empty($status)) {
            $query->where('status', $status);
        }
        if (!empty($jenis)) {
            $query->where('jenis', $jenis);
        }
        if ($dateFrom) {
            $query->whereDate('tanggal_kunjungan', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('tanggal_kunjungan', '<=', $dateTo);
        }

        $data = $query->with(['replies' => function($q) {
            $q->orderBy('created_at', 'desc');
        }])->get();

        // Column name mapping
        $columnNames = [
            'ticket_no' => 'Ticket NO',
            'id' => 'ID',
            'cid' => 'Customer ID',
            'customer_nama' => 'Nama Customer',
            'jenis' => 'Jenis',
            'pic_teknisi' => 'Teknisi',
            'tanggal_kunjungan' => 'Tanggal Kunjungan',
            'kendala' => 'Kendala',
            'hasil' => 'Hasil',
            'sla_remote_minutes' => 'MTTR Response',
            'sla_onsite_minutes' => 'MTTR Resolve',
            'sla_total_minutes' => 'Downtime',
            'status' => 'Status',
            'priority' => 'Priority',
            'rfo_data' => 'Data RFO (Problem | Cause | Action)'
        ];

        $filename = 'report_maintenance_' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $handle = fopen('php://memory', 'w');

        // Header row - only selected columns
        $headerRow = [];
        foreach ($selectedColumns as $col) {
            if (isset($columnNames[$col])) {
                $headerRow[] = $columnNames[$col];
            }
        }
        fputcsv($handle, $headerRow);

        // Get maintenance counts for all CIDs in one query (optimization)
        $cids = $data->pluck('cid')->filter()->unique();
        $maintenanceCounts = Ticket::whereIn('cid', $cids)
            ->select('cid', \DB::raw('COUNT(*) as count'))
            ->groupBy('cid')
            ->pluck('count', 'cid');

        // Data rows - only selected columns
        foreach ($data as $ticket) {
            $namaCustomer = $ticket->jenis === 'survey' ? ($ticket->calonCustomer ? $ticket->calonCustomer->nama : '-') : ($ticket->customer ? $ticket->customer->nama : '-');

            $row = [];
            foreach ($selectedColumns as $col) {
                switch ($col) {
                    case 'ticket_no':
                        // Generate ticket no: TDN-DDMMYY-HHMM-NO
                        $tanggal = $ticket->tanggal_kunjungan ? $ticket->tanggal_kunjungan->format('dmY') : date('dmY');
                        $jam = $ticket->jam ? date('Hi', strtotime($ticket->jam)) : '0000';
                        $no = str_pad($ticket->id, 3, '0', STR_PAD_LEFT);
                        $row[] = "TDN-{$tanggal}-{$jam}-{$no}";
                        break;
                    case 'id':
                        $row[] = $ticket->id;
                        break;
                    case 'cid':
                        $row[] = $ticket->jenis === 'survey' ? 'TDNSurvey' : ($ticket->cid ?? '-');
                        break;
                    case 'customer_nama':
                        $row[] = $namaCustomer;
                        break;
                    case 'jenis':
                        $row[] = $ticket->jenis;
                        break;
                    case 'pic_teknisi':
                        $row[] = $ticket->pic_teknisi;
                        break;
                    case 'tanggal_kunjungan':
                        $row[] = $ticket->tanggal_kunjungan ? $ticket->tanggal_kunjungan->format('d-m-Y') : '-';
                        break;
                    case 'kendala':
                        $row[] = $ticket->kendala ?? '';
                        break;
                    case 'hasil':
                        $lastReply = $ticket->replies->where('update_status', 'selesai')->first() 
                                     ?? $ticket->replies->first();
                        $row[] = $lastReply ? $lastReply->reply : ($ticket->solusi ?? '-');
                        break;
                    case 'sla_remote_minutes':
                        $row[] = $this->formatDuration($ticket->sla_remote_minutes);
                        break;
                    case 'sla_onsite_minutes':
                        $row[] = $this->formatDuration($ticket->sla_onsite_minutes);
                        break;
                    case 'sla_total_minutes':
                        $row[] = $this->formatDuration($ticket->sla_total_minutes);
                        break;
                    case 'status':
                        $row[] = $ticket->status;
                        break;
                    case 'priority':
                        $row[] = $ticket->priority;
                        break;
                    case 'rfo_data':
                        $lastReply = $ticket->replies->where('update_status', 'selesai')->first() 
                                     ?? $ticket->replies->first();
                        $action = $lastReply ? $lastReply->reply : ($ticket->solusi ?: '-');
                        $row[] = "Problem: " . ($ticket->indikasi ?: '-') . " | Root Cause: " . ($ticket->kendala ?: '-') . " | Action: " . $action;
                        break;
                }
            }
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, $headers);
    }

    /**
     * Export to PDF
     */
    public function exportPdf(Request $request)
    {
        // This will use mPDF or similar library
        $type = $request->input('type', 'customer');

        if ($type === 'customer') {
            return $this->exportCustomerPdf($request);
        } else {
            return $this->exportMaintenancePdf($request);
        }
    }

    private function exportCustomerPdf(Request $request)
    {
        // Implementation akan menggunakan mPDF
        // For now, return as HTML
        $query = Customer::query();

        // Filter by selected IDs if provided
        $selectedIdsParam = $request->input('selected_ids', '');
        if (!empty($selectedIdsParam)) {
            $selectedIds = explode(',', $selectedIdsParam);
            $query->whereIn('cid', $selectedIds);
        }

        $packet = $request->input('packet');
        $minBayar = $request->input('min_bayar');
        $maxBayar = $request->input('max_bayar');
        $status = $request->input('status');
        $sales = $request->input('sales');
        $pop = $request->input('pop');
        $minSetupFee = $request->input('min_setup_fee');
        $maxSetupFee = $request->input('max_setup_fee');
        $tglAktifFrom = $request->input('tgl_aktif_from');
        $tglAktifTo = $request->input('tgl_aktif_to');
        $billingAktifFrom = $request->input('billing_aktif_from');
        $billingAktifTo = $request->input('billing_aktif_to');

        // Get selected columns
        $columnsParam = $request->input('columns', '');
        $selectedColumns = !empty($columnsParam) ? explode(',', $columnsParam) : [
            'cid', 'nama', 'email', 'alamat', 'coordinate_maps', 'packet',
            'pembayaran_perbulan', 'pop', 'setup_fee', 'status', 'sales', 'pic_it', 'no_it',
            'pic_finance', 'no_finance', 'tgl_customer_aktif', 'billing_aktif', 'note'
        ];

        if (!empty($packet)) {
            $query->where('packet', '=', $packet);
        }
        if ($minBayar) {
            $query->where('pembayaran_perbulan', '>=', $minBayar);
        }
        if ($maxBayar) {
            $query->where('pembayaran_perbulan', '<=', $maxBayar);
        }
        if (!empty($status)) {
            $query->where('status', $status);
        }
        if (!empty($sales)) {
            $query->where('sales', 'like', "%{$sales}%");
        }
        if (!empty($pop)) {
            $query->where('pop', '=', $pop);
        }
        if (!empty($minSetupFee)) {
            $query->where('setup_fee', '>=', $minSetupFee);
        }
        if (!empty($maxSetupFee)) {
            $query->where('setup_fee', '<=', $maxSetupFee);
        }
        if (!empty($tglAktifFrom)) {
            $query->where('tgl_customer_aktif', '>=', $tglAktifFrom);
        }
        if (!empty($tglAktifTo)) {
            $query->where('tgl_customer_aktif', '<=', $tglAktifTo);
        }
        if (!empty($billingAktifFrom)) {
            $query->where('billing_aktif', '>=', $billingAktifFrom);
        }
        if (!empty($billingAktifTo)) {
            $query->where('billing_aktif', '<=', $billingAktifTo);
        }

        $data = $query->get();

        return view('content.report.pdf-customer', [
            'data' => $data,
            'selectedColumns' => $selectedColumns
        ]);
    }

    private function exportMaintenancePdf(Request $request)
    {
        $query = Ticket::with(['customer', 'calonCustomer']);

        // Filter by selected IDs if provided
        $selectedIdsParam = $request->input('selected_ids', '');
        if (!empty($selectedIdsParam)) {
            $selectedIds = explode(',', $selectedIdsParam);
            $query->whereIn('tickets.id', $selectedIds);
        }

        $picTeknisi = $request->input('pic_teknisi');
        $customerId = $request->input('cid');
        $status = $request->input('status');
        $jenis = $request->input('jenis');
        $salesId = $request->input('sales_id');
        $customerNama = $request->input('customer_nama');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Get selected columns from input if provided
        $columnsParam = $request->input('columns', '');
        $selectedColumns = !empty($columnsParam) ? explode(',', $columnsParam) : [
            'ticket_no', 'cid', 'customer_nama', 'jenis', 'pic_teknisi',
            'tanggal_kunjungan', 'kendala', 'hasil', 'status', 'priority'
        ];

        if (!empty($picTeknisi)) {
            $query->where('pic_teknisi', 'like', "%{$picTeknisi}%");
        }
        if (!empty($salesId)) {
            $query->whereHas('customer', function($q) use ($salesId) {
                $q->where('sales', $salesId);
            });
        }
        if (!empty($customerId)) {
            $query->where('cid', $customerId);
        }
        if (!empty($customerNama)) {
            $query->where(function ($q) use ($customerNama) {
                $q->whereHas('customer', function($sub) use ($customerNama) {
                    $sub->where('nama', 'like', "%{$customerNama}%");
                })->orWhereHas('calonCustomer', function($sub) use ($customerNama) {
                    $sub->where('nama', 'like', "%{$customerNama}%");
                });
            });
        }
        if (!empty($status)) {
            $query->where('status', $status);
        }
        if (!empty($jenis)) {
            $query->where('jenis', $jenis);
        }
        if ($dateFrom) {
            $query->whereDate('tanggal_kunjungan', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('tanggal_kunjungan', '<=', $dateTo);
        }

        $data = $query->with(['replies' => function($q) {
            $q->orderBy('created_at', 'desc');
        }])->get();

        // Get maintenance counts for all CIDs in one query (optimization)
        $cids = $data->pluck('cid')->filter()->unique();
        $maintenanceCounts = Ticket::whereIn('cid', $cids)
            ->select('cid', \DB::raw('COUNT(*) as count'))
            ->groupBy('cid')
            ->pluck('count', 'cid');

        // Pass calculated hasil and ticket_no to collection for PDF view
        $data->each(function($ticket) use ($maintenanceCounts) {
            // Hasil calculation
            $lastReply = $ticket->replies->where('update_status', 'selesai')->first() 
                         ?? $ticket->replies->first();
            $ticket->hasil = $lastReply ? $lastReply->reply : ($ticket->solusi ?? '-');
            
            // Fallback for Solusi (Action) to use Hasil if empty
            if (empty($ticket->solusi)) {
                $ticket->solusi = $ticket->hasil;
            }

            // Ticket No generation
            $tanggal = $ticket->tanggal_kunjungan ? \Carbon\Carbon::parse($ticket->tanggal_kunjungan)->format('dmY') : date('dmY');
            $jam = $ticket->jam ? date('Hi', strtotime($ticket->jam)) : '0000';
            $no = str_pad($ticket->id, 3, '0', STR_PAD_LEFT);
            $ticket->ticket_no = "TDN-{$tanggal}-{$jam}-{$no}";

            // Add maintenance_count
            $ticket->maintenance_count = $maintenanceCounts[$ticket->cid] ?? 0;
        });

        return view('content.report.pdf-maintenance', [
            'data' => $data,
            'selectedColumns' => $selectedColumns
        ]);
    }

    public function exportRFO(Request $request)
    {
        $selectedIdsParam = $request->input('selected_ids', '');
        if (empty($selectedIdsParam)) {
            return redirect()->back()->with('error', 'Pilih minimal 1 ticket untuk export RFO');
        }

        // Get and base64 encode logo for PDF
        $logoPath = public_path('assets/img/logo-rfo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoBase64 = 'data:image/png;base64,' . $logoData;
        }

        $selectedIds = explode(',', $selectedIdsParam);
        $tickets = Ticket::with(['customer', 'calonCustomer', 'replies' => function($q) {
            $q->orderBy('created_at', 'asc');
        }])->whereIn('id', $selectedIds)->get();

        $data = $tickets->map(function($ticket) {
            // Generate ticket no
            $tanggal = $ticket->tanggal_kunjungan ? \Carbon\Carbon::parse($ticket->tanggal_kunjungan)->format('dmY') : $ticket->created_at->format('dmY');
            $jam = $ticket->jam ? date('Hi', strtotime($ticket->jam)) : $ticket->created_at->format('Hi');
            $no = str_pad($ticket->id, 3, '0', STR_PAD_LEFT);
            $ticketNo = "TDN-{$tanggal}-{$jam}-{$no}";

            // Sections with timestamps
        $downtimeStart = $ticket->created_at;

        $selesaiReply = $ticket->replies->filter(function($r) {
            return in_array($r->update_status, ['done', 'remote_done', 'selesai']);
        })->last(); 
        $downtimeEnd = $selesaiReply ? $selesaiReply->created_at : now();

        $hours = intdiv($ticket->sla_total_minutes, 60);
        $mins = $ticket->sla_total_minutes % 60;
        $totalDowntime = ($hours > 0 ? $hours . " Jam " : "") . $mins . " Menit";

        return (object)[
            'ticket_no' => $ticketNo,
            'customer_nama' => $ticket->jenis === 'survey' ? ($ticket->calonCustomer ? $ticket->calonCustomer->nama : '-') : ($ticket->customer ? $ticket->customer->nama : '-'),
            'downtime_start' => $downtimeStart->format('d-m-Y H:i'),
            'downtime_end' => $selesaiReply ? $downtimeEnd->format('d-m-Y H:i') : '-',
            'total_downtime' => $totalDowntime,
            'indikasi_rfo' => $ticket->indikasi ?: '-',
            'masalah_rfo' => $ticket->kendala ?: '-',
            'solusi_rfo' => $ticket->solusi ?: '-'
        ];
    });

    return view('content.report.pdf-rfo', [
        'tickets' => $data,
        'logoBase64' => $logoBase64
    ]);
}

    private function formatDuration($minutes)
    {
        if (!$minutes || $minutes == 0) return '0m';
        $h = floor($minutes / 60);
        $m = $minutes % 60;
        return ($h > 0 ? "{$h}h " : '') . "{$m}m";
    }
}
