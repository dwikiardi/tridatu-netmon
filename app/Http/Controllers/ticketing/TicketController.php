<?php

namespace App\Http\Controllers\ticketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Customer;
use App\Models\CalonCustomer;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TicketController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('content.ticketing.table-ticket', compact('users'));
    }

    public function show(Request $request)
    {
        $searchValue = $request->input('search.value');
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        // Total Data Sebelum Filter
        $recordsTotal = Ticket::count();

        // Query dasar
        $query = Ticket::with('customer', 'calonCustomer');

        // Filter pencarian
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('cid', 'LIKE', "%{$searchValue}%")
                  ->orWhere('priority', 'LIKE', "%{$searchValue}%")
                  ->orWhere('tanggal_kunjungan', 'LIKE', "%{$searchValue}%")
                  ->orWhere('pic_it_lokasi', 'LIKE', "%{$searchValue}%")
                  ->orWhere('pic_teknisi', 'LIKE', "%{$searchValue}%")
                  ->orWhere('jam', 'LIKE', "%{$searchValue}%")
                  ->orWhere('hari', 'LIKE', "%{$searchValue}%")
                  ->orWhere('kendala', 'LIKE', "%{$searchValue}%")
                  ->orWhere('status', 'LIKE', "%{$searchValue}%")
                  ->orWhereHas('customer', function($q) use ($searchValue) {
                      $q->where('nama', 'LIKE', "%{$searchValue}%");
                  })
                  ->orWhereHas('calonCustomer', function($q) use ($searchValue) {
                      $q->where('nama', 'LIKE', "%{$searchValue}%");
                  });
            });
        }

        // Filter by status
        if (!empty($request->input('status'))) {
            $query->where('status', $request->input('status'));
        }

        // Filter by priority
        if (!empty($request->input('priority'))) {
            $query->where('priority', $request->input('priority'));
        }

        // Filter by jenis
        if (!empty($request->input('jenis'))) {
            $query->where('jenis', $request->input('jenis'));
        }

        // Filter by metode
        if (!empty($request->input('metode'))) {
            $query->where('metode_penanganan', $request->input('metode'));
        }

        // Total data setelah filter
        $recordsFiltered = $query->count();

        // Ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');
        $columns = [
            'id',
            'cid',
            'jenis',
            'metode_penanganan',
            'priority',
            'tanggal_kunjungan',
            'pic_it_lokasi',
            'pic_teknisi',
            'jam',
            'hari',
            'kendala'
        ];

        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination & Fetch Data
        $data = $query->skip($start)->take($length)->get();

        // Format data untuk display
        $formattedData = $data->map(function ($ticket) {
            $namaCustomer = $ticket->customer ? $ticket->customer->nama : ($ticket->calonCustomer ? $ticket->calonCustomer->nama : '-');
            $cid = $ticket->cid ?? 'TDNSurvey';

            // Generate ticket no: TDN-DDMMYY-HHMM-NO
            $tanggal = $ticket->tanggal_kunjungan ? $ticket->tanggal_kunjungan->format('dmY') : date('dmY');
            $jam = $ticket->jam ? date('Hi', strtotime($ticket->jam)) : date('Hi');
            $no = str_pad($ticket->id, 3, '0', STR_PAD_LEFT);
            $ticketNo = "TDN-{$tanggal}-{$jam}-{$no}";

            return [
                'ticket_no' => $ticketNo,
                'cid' => $cid,
                'nama_customer' => $namaCustomer,
                'jenis' => $ticket->jenis,
                'metode_penanganan' => $ticket->metode_penanganan,
                'priority' => $ticket->priority,
                'tanggal_kunjungan' => $ticket->tanggal_kunjungan ? $ticket->tanggal_kunjungan->format('d-m-Y') : '-',
                'hari' => $ticket->hari,
                'kendala' => $ticket->kendala,
                'status' => $ticket->status,
                'id' => $ticket->id,
            ];
        });

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $formattedData
        ]);
    }

    public function detail(Request $request)
    {
        $ticket = Ticket::with('customer', 'calonCustomer.sales')->findOrFail($request->id);

        $namaCustomer = $ticket->customer ? $ticket->customer->nama : ($ticket->calonCustomer ? $ticket->calonCustomer->nama : '-');
        $telpCustomer = $ticket->calonCustomer ? $ticket->calonCustomer->telepon : ($ticket->customer ? $ticket->customer->pic_it : '-');
        $alamatCustomer = $ticket->calonCustomer ? $ticket->calonCustomer->alamat : ($ticket->customer ? $ticket->customer->alamat : '-');
        $koordinat = $ticket->customer ? $ticket->customer->coordinate_maps : ($ticket->calonCustomer ? $ticket->calonCustomer->koordinat : null);
        $salesName = $ticket->calonCustomer && $ticket->calonCustomer->sales ? $ticket->calonCustomer->sales->name : '-';

        return response()->json([
            'id' => $ticket->id,
            'cid' => $ticket->cid,
            'calon_customer_id' => $ticket->calon_customer_id,
            'nama_customer' => $namaCustomer,
            'telp_customer' => $telpCustomer,
            'alamat_customer' => $alamatCustomer,
            'koordinat' => $koordinat,
            'sales_name' => $salesName,
            'jenis' => $ticket->jenis,
            'metode_penanganan' => $ticket->metode_penanganan,
            'priority' => $ticket->priority,
            'tanggal_kunjungan' => $ticket->tanggal_kunjungan ? $ticket->tanggal_kunjungan->format('Y-m-d') : '',
            'pic_it_lokasi' => $ticket->pic_it_lokasi,
            'no_it_lokasi' => $ticket->no_it_lokasi,
            'pic_teknisi' => $ticket->pic_teknisi,
            'jam' => $ticket->jam ? date('H:i', strtotime($ticket->jam)) : '',
            'hari' => $ticket->hari,
            'kendala' => $ticket->kendala,
            'solusi' => $ticket->solusi,
            'hasil' => $ticket->hasil,
            'status' => $ticket->status,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'jenis' => 'required|in:maintenance-komplain,survey,installasi',
            // Maintenance/Komplain
            'cid' => 'nullable|string',
            'kendala' => 'nullable|string',
            // Survey fields
            'survey_tipe' => 'nullable|in:baru,existing,project',
            // Survey - Pelanggan Baru
            'survey_nama' => 'nullable|string',
            'survey_telepon' => 'nullable|string',
            'survey_alamat' => 'nullable|string',
            'survey_koordinat' => 'nullable|string',
            'survey_pic_it' => 'nullable|string',
            'survey_sales_id' => 'nullable|exists:users,id',
            'survey_deskripsi' => 'nullable|string',
            // Survey - Project (gunakan field terpisah agar tidak bentrok)
            'survey_project_nama' => 'nullable|string',
            'survey_project_telepon' => 'nullable|string',
            'survey_project_alamat' => 'nullable|string',
            'survey_project_koordinat' => 'nullable|string',
            'survey_project_pic' => 'nullable|string',
            'survey_project_sales_id' => 'nullable|exists:users,id',
            // Survey - Pelanggan Existing
            'survey_customer_id' => 'nullable|string',
            'survey_jenis_existing' => 'nullable|string',
            // Installasi - tipe
            'install_tipe' => 'nullable|in:calon,penambahan,terminate,project',
            // Installasi - Calon Customer (Pelanggan Baru)
            'install_calon_customer_id' => 'nullable|exists:calon_customers,id',
            'install_nama' => 'nullable|string',
            'install_alamat' => 'nullable|string',
            'install_koordinat' => 'nullable|string',
            // Installasi - Penambahan Alat (Existing Customer)
            'install_customer_penambahan_id' => 'nullable|string',
            // Installasi - Customer Terminate
            'install_customer_id' => 'nullable|string',
            // Installasi - Project
            'install_survey_project_id' => 'nullable|exists:tickets,id',
            'install_project_nama' => 'nullable|string',
            'install_project_lokasi' => 'nullable|string',
            'install_project_koordinat' => 'nullable|string',
            'install_project_pic' => 'nullable|string',
            // Common installasi
            'install_deskripsi' => 'nullable|string',
            // POP fields for installation
            'install_pop' => 'nullable|string',
            'install_terminate_pop' => 'nullable|string',
            // Ticket Creation Date
            'is_created_today' => 'nullable|in:on,off,true,false,1,0',
            'custom_created_at' => 'nullable|required_if:is_created_today,off,false,0|date',
        ]);

        $user = Auth::user();
        $jenis = $validated['jenis'];
        $data = [];
        // Common fields
        $data['created_by'] = $user->id;
        $data['created_by_role'] = $user->jabatan ?? 'admin';
        $data['status'] = 'open';
        $data['hari'] = '-';

        // Type-specific handling
        if ($jenis === 'maintenance-komplain') {
            // Maintenance & Komplain: Only customer + kendala
            // Other fields (priority, metode, lokasi, tanggal) akan diisi saat update
            $data['jenis'] = 'maintenance'; // Default ke maintenance, bisa diubah di update
            $data['cid'] = $validated['cid'] ?? null;
            $data['kendala'] = $validated['kendala'] ?? null;
            // Don't set priority, metode_penanganan, tanggal_kunjungan - akan null di database
        }
        elseif ($jenis === 'survey') {
            // Survey: bisa dari pelanggan baru, existing, atau project
            $surveyTipe = $validated['survey_tipe'] ?? null;

            // Inference fallback: jika survey_tipe tidak dikirim, deteksi dari field yang terisi
            if (!$surveyTipe) {
                if (!empty($validated['survey_customer_id'])) {
                    $surveyTipe = 'existing';
                } elseif (!empty($validated['survey_project_nama'])) {
                    $surveyTipe = 'project';
                } else {
                    $surveyTipe = 'baru';
                }
            }
            // Validasi: survey_tipe harus valid
            if (!in_array($surveyTipe, ['baru', 'existing', 'project'])) {
                return response()->json([
                    'message' => 'Tipe Survey harus dipilih (Pelanggan Baru, Pelanggan Existing, atau Project)',
                    'status' => false
                ], 422);
            }

            // Tambahan validasi untuk mencegah nilai null saat membuat calon customer
            if ($surveyTipe === 'baru') {
                $request->validate([
                    'survey_nama' => 'required|string',
                    'survey_alamat' => 'required|string',
                    'survey_koordinat' => 'required|string',
                    'survey_pic_it' => 'required|string',
                    'survey_sales_id' => 'required|exists:users,id',
                    'survey_deskripsi' => 'required|string',
                ]);
            } elseif ($surveyTipe === 'project') {
                $request->validate([
                    'survey_project_nama' => 'required|string',
                    'survey_project_alamat' => 'required|string',
                    'survey_project_koordinat' => 'required|string',
                    'survey_project_pic' => 'required|string',
                    'survey_project_sales_id' => 'required|exists:users,id',
                    'survey_deskripsi' => 'required|string',
                ]);
            }

            $data['jenis'] = 'survey';
            $data['kendala'] = $validated['survey_deskripsi'] ?? null;

            if ($surveyTipe === 'baru') {
                // Pelanggan baru biasa
                $calonCustomer = CalonCustomer::create([
                    'nama' => $validated['survey_nama'],
                    'telepon' => $validated['survey_telepon'] ?? null,
                    'alamat' => $validated['survey_alamat'],
                    'koordinat' => $validated['survey_koordinat'],
                    'sales_id' => $validated['survey_sales_id'],
                    'tipe_survey' => 'normal',
                ]);
                $data['calon_customer_id'] = $calonCustomer->id;
                $data['pic_it_lokasi'] = $validated['survey_pic_it'] ?? null;
            } elseif ($surveyTipe === 'project') {
                // Survey project
                $calonCustomer = CalonCustomer::create([
                    'nama' => $validated['survey_project_nama'],
                    'telepon' => $validated['survey_project_telepon'] ?? null,
                    'alamat' => $validated['survey_project_alamat'],
                    'koordinat' => $validated['survey_project_koordinat'],
                    'sales_id' => $validated['survey_project_sales_id'],
                    'tipe_survey' => 'project',
                ]);
                $data['calon_customer_id'] = $calonCustomer->id;
                $data['pic_it_lokasi'] = $validated['survey_project_pic'] ?? null;
            } else {
                // Pelanggan existing: link ke customer
                $data['cid'] = $validated['survey_customer_id'] ?? null;
                $data['pic_it_lokasi'] = $validated['survey_jenis_existing'] ?? null;

                // Buat calon_customers record untuk tracking survey existing
                // Ini untuk kemudahan filter di penambahan alat dropdown
                $customer = Customer::find($validated['survey_customer_id']);
                if ($customer) {
                    $calonCustomer = CalonCustomer::create([
                        'nama' => $customer->nama,
                        'telepon' => $customer->telepon ?? null,
                        'alamat' => $customer->alamat,
                        'koordinat' => $customer->coordinate_maps,
                        'sales_id' => auth()->id(), // Logged in user as sales
                        'tipe_survey' => 'existing',
                    ]);
                    $data['calon_customer_id'] = $calonCustomer->id;
                }
            }
        }
        elseif ($jenis === 'installasi') {
            // Installasi: bisa dari calon customer, customer terminate, atau project

            $installTipe = $validated['install_tipe'] ?? 'calon';

            $data['jenis'] = 'installasi';
            $data['kendala'] = $validated['install_deskripsi'] ?? null;

            if ($installTipe === 'calon') {
                // Dari calon customer survey
                if (!empty($validated['install_calon_customer_id'])) {
                    $calonCustomer = CalonCustomer::find($validated['install_calon_customer_id']);

                    if (!$calonCustomer) {
                        return response()->json([
                            'message' => 'Calon customer tidak ditemukan',
                            'status' => false
                        ], 400);
                    }

                    // Cek apakah ada ticket survey yang selesai
                    $hasSurveyDone = $calonCustomer->tickets()
                        ->where('jenis', 'survey')
                        ->where('status', 'selesai')
                        ->exists();

                    if (!$hasSurveyDone) {
                        return response()->json([
                            'message' => 'Calon customer ini belum memiliki ticket survey yang selesai. Selesaikan survey terlebih dahulu.',
                            'status' => false
                        ], 400);
                    }
                }

                $data['calon_customer_id'] = $validated['install_calon_customer_id'] ?? null;
                $data['pic_it_lokasi'] = $validated['install_alamat'] ?? null;
                $data['pop'] = $validated['install_pop'] ?? null;
            } elseif ($installTipe === 'penambahan') {
                // Penambahan alat untuk customer existing (sudah survey sebelumnya)
                $data['cid'] = $validated['install_customer_penambahan_id'] ?? null;
                $data['pic_it_lokasi'] = 'Penambahan Alat'; // Mark as penambahan alat
            } elseif ($installTipe === 'terminate') {
                // Re-aktivasi customer terminate
                $data['cid'] = $validated['install_customer_id'] ?? null;
                $data['pic_it_lokasi'] = 'Re-aktivasi'; // Mark as re-activation
                $data['pop'] = $validated['install_terminate_pop'] ?? null;
            } elseif ($installTipe === 'project') {
                // Project installation
                // Link ke survey project jika ada, atau buat baru dengan project data
                if (!empty($validated['install_survey_project_id'])) {
                    $surveyProject = Ticket::find($validated['install_survey_project_id']);
                    if ($surveyProject) {
                        $data['parent_ticket_id'] = $surveyProject->id; // Link ke survey project
                        $data['calon_customer_id'] = $surveyProject->calon_customer_id; // Copy calon_customer_id agar nama muncul
                    }
                }

                // Use description from install_deskripsi (which comes from survey's latest update)
                $data['kendala'] = $validated['install_deskripsi'] ?? $validated['install_project_nama'] ?? null;
                $data['pic_it_lokasi'] = $validated['install_project_lokasi'] ?? null; // Store location
                $data['note'] = json_encode([
                    'koordinat' => $validated['install_project_koordinat'] ?? null,
                    'pic' => $validated['install_project_pic'] ?? null,
                    'project_nama' => $validated['install_project_nama'] ?? null,
                ], JSON_UNESCAPED_UNICODE); // Store koordinat, pic & project name as JSON
            }
        }

        // Handle Custom Created Date
        $isCreatedToday = $request->has('is_created_today'); // Checkbox checks send 'on', unchecked sends nothing usually. 
        // Wait, default HTML checkbox: checked = 'on', unchecked = nothing (missing from request).
        // My JS validation handles 'required' for date if it's unchecked.
        // Let's rely on the input presence.
        
        $customDate = $request->input('custom_created_at');
        
        // Logic: If checkbox is NOT checked (meaning user wants custom date) AND custom date is provided.
        // If checkbox IS checked, we ignore custom date.
        // Since checkbox sends nothing when unchecked, checking `$request->has('is_created_today')` works for "Checked".
        
        // Logic: If checkbox is NOT checked (meaning user wants custom date) AND custom date is provided.
        // If checkbox IS checked, we ignore custom date.
        
        $ticket = Ticket::create($data);

        if (!$request->has('is_created_today') && !empty($customDate)) {
             $parsedDate = \Carbon\Carbon::parse($customDate);
             // Manually set timestamps
             $ticket->created_at = $parsedDate;
             $ticket->updated_at = $parsedDate;
             $ticket->save();
        }

        return response()->json(['message' => 'Ticket created successfully']);
    }

    public function update(Request $request)
    {
        $ticket = Ticket::findOrFail($request->id);

        $data = $request->all();
        $user = Auth::user();

        // Set creator info jika belum ada
        if (!$ticket->created_by) {
            $data['created_by'] = $user->id;
            $data['created_by_role'] = $user->jabatan ?? 'admin';
        }

        // Only update allowed fields from simplified form
        $ticket->update([
            'cid' => $data['cid'] ?? $ticket->cid,
            'kendala' => $data['kendala'] ?? $ticket->kendala,
        ]);

        return response()->json(['message' => 'Ticket updated successfully']);
    }

    public function destroy(Request $request)
    {
        $ticket = Ticket::findOrFail($request->id);
        $ticket->delete();
        return response()->json(['message' => 'Ticket deleted successfully']);
    }

    public function getCustomers()
    {
        $customers = Customer::select('cid', 'nama', 'pic_it', 'no_it', 'alamat', 'status')->orderBy('nama')->get();
        return response()->json($customers);
    }

    public function getCalonCustomers()
    {
        // Hanya tampilkan calon customer yang:
        // 1. Belum di-convert (status = prospek)
        // 2. Ticket survey-nya sudah selesai
        // 3. Tipe survey = 'normal' (bukan project)
        $calonCustomers = CalonCustomer::where('status', 'prospek')
            ->where('tipe_survey', 'normal')
            ->whereHas('tickets', function($query) {
                $query->where('jenis', 'survey')
                      ->where('status', 'selesai');
            })
            ->with(['tickets' => function($query) {
                $query->where('jenis', 'survey')
                      ->where('status', 'selesai')
                      ->with(['replies' => function($subQuery) {
                          $subQuery->orderBy('created_at', 'desc')->limit(1);
                      }])
                      ->latest()
                      ->limit(1);
            }])
            ->select('id', 'nama', 'alamat', 'koordinat')
            ->orderBy('nama')
            ->get();

        // Format dengan data dari latest survey ticket
        $formatted = $calonCustomers->map(function($customer) {
            $latestSurveyTicket = $customer->tickets->first();
            $latestReply = $latestSurveyTicket ? $latestSurveyTicket->replies->first() : null;
            $deskripsi = $latestReply ? $latestReply->reply : ($latestSurveyTicket ? $latestSurveyTicket->kendala : '');

            return [
                'id' => $customer->id,
                'nama' => $customer->nama,
                'alamat' => $customer->alamat,
                'koordinat' => $customer->koordinat,
                'deskripsi_update' => $deskripsi,
            ];
        });

        return response()->json($formatted);
    }

    public function getExistingCustomersWithSurvey()
    {
        // Get customers yang sudah ada survey selesai di existing customer
        // TAPI: Filter hanya survey yang belum ada installasi penambahan dari survey tersebut

        // Get all completed existing customer surveys
        $surveyTickets = Ticket::where('jenis', 'survey')
            ->where('status', 'selesai')
            ->whereNotNull('cid')
            ->whereHas('calonCustomer', function($query) {
                $query->where('tipe_survey', 'existing');
            })
            ->with(['customer', 'replies' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(1);
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('cid') // Group by customer ID untuk ambil survey terbaru per customer
            ->map(function($tickets) {
                return $tickets->first();
            });

        // Filter: hanya include survey yang belum ada installasi penambahan dari parent_ticket_id atau cid yang sama
        $formatted = $surveyTickets->filter(function($ticket) {
            if ($ticket->customer === null) {
                return false;
            }

            // Check apakah sudah ada installasi penambahan dari survey ini
            // Cek dengan parent_ticket_id atau cek berdasarkan cid + jenis installasi + pic_it_lokasi = 'Penambahan Alat'
            $existingInstallasi = Ticket::where('jenis', 'installasi')
                ->where('cid', $ticket->cid)
                ->where('pic_it_lokasi', 'Penambahan Alat') // Check by marker
                ->exists();

            // Return true jika belum ada installasi penambahan
            return !$existingInstallasi;
        })->map(function($ticket) {
            $customer = $ticket->customer;
            $latestReply = $ticket->replies->first();
            $deskripsi = $latestReply ? $latestReply->reply : $ticket->kendala;

            // Format display: Nama Customer - Ticket ID - Hasil Survey Terakhir
            $displayName = $customer->nama . ' - Ticket #' . $ticket->id . ' - ' . \Illuminate\Support\Str::limit($deskripsi, 50);

            return [
                'cid' => $customer->cid,
                'nama' => $customer->nama,
                'display_name' => $displayName,
                'ticket_id' => $ticket->id,
                'alamat' => $customer->alamat ?? '',
                'coordinate_maps' => $customer->coordinate_maps ?? '',
                'deskripsi_update' => $deskripsi,
            ];
        });

        return response()->json($formatted->values());
    }

    public function getSurveyProjects()
    {
        // Get all survey tickets dengan tipe_survey = 'project' yang sudah selesai
        $surveyProjects = Ticket::where('jenis', 'survey')
            ->where('status', 'selesai')
            ->whereHas('calonCustomer', function($query) {
                $query->where('tipe_survey', 'project');
            })
            ->with(['calonCustomer', 'replies' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(1);
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Format data untuk dropdown
        $formatted = $surveyProjects->map(function($ticket) {
            // Generate ticket number
            $tanggal = $ticket->tanggal_kunjungan ? $ticket->tanggal_kunjungan->format('dmY') : date('dmY');
            $jam = $ticket->jam ? date('Hi', strtotime($ticket->jam)) : date('Hi');
            $no = str_pad($ticket->id, 3, '0', STR_PAD_LEFT);
            $ticketNo = "TDN-{$tanggal}-{$jam}-{$no}";

            // Get latest reply/update
            $latestReply = $ticket->replies->first();
            $deskripsi = $latestReply ? $latestReply->reply : $ticket->kendala;

            return [
                'id' => $ticket->id,
                'tiket_number' => $ticketNo,
                'nama' => $ticket->calonCustomer ? $ticket->calonCustomer->nama : $ticket->kendala,
                'lokasi' => $ticket->calonCustomer ? $ticket->calonCustomer->alamat : '',
                'koordinat' => $ticket->calonCustomer ? $ticket->calonCustomer->koordinat : '',
                'pic' => $ticket->pic_it_lokasi ?? '',
                'kendala' => $ticket->kendala,
                'deskripsi_update' => $deskripsi,
            ];
        });

        return response()->json($formatted);

    }

    public function getTeknisi()
    {
        $teknisi = \App\Models\User::where('jabatan', 'teknisi')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        return response()->json($teknisi);
    }

    public function getSales()
    {
        $sales = \App\Models\User::where('jabatan', 'sales')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        return response()->json($sales);
    }

    // New method: show detail page (halaman forum/thread)
    public function showDetailPage($ticketId)
    {
        $ticket = Ticket::with(['customer', 'calonCustomer.sales', 'creator', 'replies.user'])->findOrFail($ticketId);

        // Recalculate SLA setiap kali halaman dibuka
        $this->recalculateSla($ticket);
        $ticket->refresh();

        // Check if ticket pernah ada update onsite
        $hasOnsiteHistory = $ticket->metode_penanganan === 'onsite';

        return view('content.ticketing.ticket-detail', [
            'ticket' => $ticket,
            'hasOnsiteHistory' => $hasOnsiteHistory,
        ]);
    }

    // New method: get ticket detail dengan replies
    public function getTicketDetail(Request $request)
    {
        $ticket = Ticket::with(['customer', 'calonCustomer.sales', 'creator', 'replies.user'])->findOrFail($request->ticket_id);

        $namaCustomer = $ticket->customer ? $ticket->customer->nama : ($ticket->calonCustomer ? $ticket->calonCustomer->nama : '-');
        $telpCustomer = $ticket->calonCustomer ? $ticket->calonCustomer->telepon : ($ticket->customer ? $ticket->customer->pic_it : '-');
        $alamatCustomer = $ticket->calonCustomer ? $ticket->calonCustomer->alamat : ($ticket->customer ? $ticket->customer->alamat : '-');

        // Generate ticket no: TDN-DDMMYY-HHMM-NO
        $tanggal = $ticket->tanggal_kunjungan ? $ticket->tanggal_kunjungan->format('dmY') : date('dmY');
        $jam = $ticket->jam ? date('Hi', strtotime($ticket->jam)) : date('Hi');
        $no = str_pad($ticket->id, 3, '0', STR_PAD_LEFT);
        $ticketNo = "TDN-{$tanggal}-{$jam}-{$no}";

        return response()->json([
            'id' => $ticket->id,
            'ticket_no' => $ticketNo,
            'cid' => $ticket->cid,
            'nama_customer' => $namaCustomer,
            'telp_customer' => $telpCustomer,
            'alamat_customer' => $alamatCustomer,
            'jenis' => $ticket->jenis,
            'metode_penanganan' => $ticket->metode_penanganan,
            'priority' => $ticket->priority,
            'tanggal_kunjungan' => $ticket->tanggal_kunjungan ? $ticket->tanggal_kunjungan->format('d-m-Y') : '-',
            'pic_teknisi' => $ticket->pic_teknisi,
            'kendala' => $ticket->kendala,
            'solusi' => $ticket->solusi,
            'hasil' => $ticket->hasil,
            'status' => $ticket->status,
            'created_by_name' => $ticket->creator ? $ticket->creator->name : 'Admin',
            'created_by_role' => $ticket->created_by_role ?? 'admin',
            'created_at' => $ticket->created_at->format('d-m-Y H:i'),
        ]);
    }

    // New method: store reply ke ticket
    public function storeReply(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'reply' => 'required|string|min:3',
            'update_status' => 'nullable|in:need_visit,on_progress,pending,remote_done,done',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'jenis' => 'nullable|string',
            'metode_penanganan' => 'nullable|in:onsite,remote',
            'tanggal_kunjungan' => 'nullable|date',
            'jam' => 'nullable|date_format:H:i',
            'hari' => 'nullable|string',
            'teknisi_id' => 'nullable|exists:users,id',
            'teknisi_ids' => 'nullable|array',
            'teknisi_ids.*' => 'exists:users,id',
            // Update Date
            'is_created_today' => 'nullable|in:on,off,true,false,1,0',
            'custom_created_at' => 'nullable|required_if:is_created_today,off,false,0|date',
        ]);

        $user = Auth::user();
        $ticket = Ticket::findOrFail($validated['ticket_id']);

        // Block updates for completed tickets
        if ($ticket->status === 'selesai') {
            return response()->json([
                'message' => 'Ticket sudah selesai dan tidak dapat di-update lagi.',
                'status' => false
            ], 422);
        }

        // Update ticket fields if provided
        $updateData = [];
        if (!empty($validated['priority'])) $updateData['priority'] = $validated['priority'];
        if (!empty($validated['jenis'])) $updateData['jenis'] = $validated['jenis'];
        if (!empty($validated['metode_penanganan'])) $updateData['metode_penanganan'] = $validated['metode_penanganan'];
        if (!empty($validated['tanggal_kunjungan'])) $updateData['tanggal_kunjungan'] = $validated['tanggal_kunjungan'];
        if (!empty($validated['jam'])) $updateData['jam'] = $validated['jam'];
        if (!empty($validated['hari'])) $updateData['hari'] = $validated['hari'];
        $teknisiIds = collect($validated['teknisi_ids'] ?? [])->filter()->values();
        if (!empty($validated['teknisi_id'])) {
            $teknisiIds = $teknisiIds->prepend($validated['teknisi_id'])->unique();
        }

        if ($teknisiIds->isNotEmpty()) {
            $updateData['teknisi_id'] = $teknisiIds->first();
            
            // Update pic_teknisi (string) secara kumulatif agar filter/search teknisi 
            // bisa menemukan ticket ini meskipun teknisinya bertambah atau berganti.
            $currentPic = $ticket->pic_teknisi ?? '';
            $teknisiNames = User::whereIn('id', $teknisiIds)->pluck('name')->toArray();
            
            $existingNames = array_map('trim', explode(',', $currentPic));
            $allNames = array_unique(array_merge($existingNames, $teknisiNames));
            $updateData['pic_teknisi'] = implode(', ', array_filter($allNames));
        }

        // Update status ticket berdasarkan update_status dari reply (ALWAYS update status)
        $updateStatus = $validated['update_status'] ?? 'need_visit';
        $statusMapping = [
            'need_visit' => 'need visit',
            'on_progress' => 'on progress',
            'pending' => 'pending',
            'remote_done' => 'selesai',
            'done' => 'selesai',
        ];

        // Status update is mandatory - always include in updateData
        if (isset($statusMapping[$updateStatus])) {
            $updateData['status'] = $statusMapping[$updateStatus];
        }

        // Konversi metode dari remote -> onsite jika pilih "Perlu Kunjungan"
        $incomingMetode = $validated['metode_penanganan'] ?? $ticket->metode_penanganan;
        if ($updateStatus === 'need_visit' && $incomingMetode === 'remote') {
            // Wajib isi jadwal kunjungan, teknisi optional saat konversi dari remote
            if (empty($validated['tanggal_kunjungan']) || empty($validated['jam'])) {
                return response()->json([
                    'message' => 'Untuk status Perlu Kunjungan, tanggal dan jam kunjungan harus diisi.',
                    'status' => false
                ], 422);
            }
            // Enforce schedule not before ticket created, last update, or latest scheduled datetime
            $lastReply = TicketReply::where('ticket_id', $ticket->id)->orderBy('created_at', 'desc')->first();
            $baseline = $ticket->created_at;
            if ($lastReply && $lastReply->created_at->gt($baseline)) {
                $baseline = $lastReply->created_at;
            }
            $lastScheduleBaseline = $this->getLastScheduleBaseline($ticket);
            if ($lastScheduleBaseline && $lastScheduleBaseline->gt($baseline)) {
                $baseline = $lastScheduleBaseline;
            }
            $selectedDT = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $validated['tanggal_kunjungan'].' '.$validated['jam']);
            if ($selectedDT->lt($baseline)) {
                return response()->json([
                    'message' => 'Tanggal/jam kunjungan tidak boleh kurang dari update sebelumnya.',
                    'status' => false
                ], 422);
            }
            $updateData['metode_penanganan'] = 'onsite';
            // Pastikan jadwal dan teknisi ikut tersimpan di ticket
            $updateData['tanggal_kunjungan'] = $validated['tanggal_kunjungan'];
            $updateData['jam'] = $validated['jam'];
            $updateData['hari'] = $validated['hari'] ?? $ticket->hari;
            if ($teknisiIds->isNotEmpty()) {
                $updateData['teknisi_id'] = $teknisiIds->first();
            }
        }

        // Determine Reply Time (Baseline for schedule validation)
        $replyCreatedAt = now();
        if (!$request->has('is_created_today') && $request->filled('custom_created_at')) {
            $replyCreatedAt = \Carbon\Carbon::parse($request->input('custom_created_at'));
        }

        // Additional guard: when tanggal/jam provided in any flow, enforce baseline against REPPLY time (or strictly previous logic?)
        // The user wants to allow backdated reply so backdated schedule is valid.
        // So baseline should be the Effective Reply Time, OR the previous state if Reply Time is also previous.
        // Actually, logic: Schedule cannot be before the "Point of Truth".
        // If we backdate the Reply to Jan 24, then Jan 24 schedule is valid.
        // So validation should check against $replyCreatedAt.
        // HOWEVER, it also shouldn't be before the Ticket Creation strictly? (Unless Ticket is also backdated, which it is in DB).
        
        if (!empty($validated['tanggal_kunjungan']) || !empty($validated['jam'])) {
            // Baseline 1: Ticket Creation
            $baseline = $ticket->created_at; 
            
            // Baseline 2: Last Reply (if strictly enforcing sequential updates? 
            // If user inserts a reply in the past, effectively branching history? 
            // Let's assume sequential: cannot verify against future replies if inserting in past.
            // But usually we just want to ensure Schedule >= This Reply Effective Date (or slightly before? No, usually schedule is future/same as report).
            // "Perlu Kunjungan" means Future/Now relative to Report.
            // "Done" means Past relative to Report.
            // But validated['tanggal_kunjungan'] is usually meant for "Next Visit".
            // If status is "Done" or "On Progress", maybe date is meaningless?
            
            // If status is Need Visit, Schedule must be >= Reply Date.
            if (($updateStatus === 'need_visit' || $updateStatus === 'pending') && isset($validated['tanggal_kunjungan'])) {
                $selDate = $validated['tanggal_kunjungan'];
                $selTime = $validated['jam'] ?? '00:00';
                $selectedDT = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $selDate.' '.$selTime);
                
                if ($selectedDT->lt($replyCreatedAt)) {
                     // Allow slight tolerance? No.
                     return response()->json([
                        'message' => 'Jadwal kunjungan (Need Visit) tidak boleh sebelum Tanggal Update.',
                        'status' => false
                    ], 422);
                }
            }
        }

        // Create the Reply
        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'reply' => $validated['reply'],
            'update_status' => $updateStatus,
            'tanggal_kunjungan' => $validated['tanggal_kunjungan'] ?? null,
            'jam_kunjungan' => $validated['jam'] ?? null,
            // 'created_at' => $replyCreatedAt, // Not mass assignable usually
        ]);

        // Manually update timestamp if custom
        if (!$request->has('is_created_today') && $request->filled('custom_created_at')) {
            $reply->created_at = $replyCreatedAt;
            $reply->updated_at = $replyCreatedAt;
            $reply->save();
        }

        // Update Ticket Updated At as well?
        // Usually Ticket Updated At should reflect the entry time of the latest info. 
        // If providing backdated info, maybe update ticket to Backdated time? 
        // Or keep it Now? 
        // Let's set it to $replyCreatedAt to be consistent with the timeline.
        $ticket->updated_at = $replyCreatedAt;
        // Don't save ticket yet, proceed to $ticket->update($updateData)

        // Always update ticket with at least the status from reply
        $ticket->update($updateData);
        $ticket->refresh();

        // Jika installasi calon customer (pelanggan baru biasa) selesai, convert ke customer
        if ($ticket->jenis === 'installasi' && $updateStatus === 'done' && $ticket->calon_customer_id) {
            $calonCustomer = CalonCustomer::find($ticket->calon_customer_id);

            // Hanya convert jika tipe_survey = 'normal' (bukan project)
            if ($calonCustomer && $calonCustomer->tipe_survey === 'normal') {
                // Generate CID: XXXX-MMYY format (4-digit number + current month-year)
                $currentMonth = now()->format('m');
                $currentYear = now()->format('y');
                $monthYearSuffix = $currentMonth . $currentYear;
                
                // Cari customer terakhir dengan format baru (XXXX-MMYY pattern)
                $lastCustomer = Customer::where('cid', 'REGEXP', '^[0-9]{4}-[0-9]{4}$')
                    ->orderByRaw('CAST(SUBSTRING(cid, 1, 4) AS UNSIGNED) DESC')
                    ->first();

                $lastNumber = 0;
                if ($lastCustomer) {
                    // Extract number dari CID (contoh: 0001-1225 -> 0001 -> 1)
                    $cidParts = explode('-', $lastCustomer->cid);
                    if (count($cidParts) >= 1) {
                        $lastNumber = intval($cidParts[0]);
                    }
                }

                $newCid = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT) . '-' . $monthYearSuffix;

                // Double check: pastikan CID belum ada (untuk safety)
                while (Customer::where('cid', $newCid)->exists()) {
                    $lastNumber++;
                    $newCid = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT) . '-' . $monthYearSuffix;
                }

                // Ambil nama sales dari relasi
                $salesName = $calonCustomer->sales ? $calonCustomer->sales->name : '-';

                // Ambil POP dari ticket (disimpan di field pop di ticket)
                $popValue = $ticket->pop ?? null;

                // Create customer baru dari calon_customer
                Customer::create([
                    'cid' => $newCid,
                    'nama' => $calonCustomer->nama ?? '-',
                    'email' => '-', // Default, bisa diupdate nanti
                    'sales' => $salesName,
                    'pop' => $popValue,
                    'packet' => '-', // Default
                    'alamat' => $calonCustomer->alamat ?? '-',
                    'pic_it' => $calonCustomer->telepon ?? '-',
                    'pic_finance' => '-',
                    'no_it' => $calonCustomer->telepon ?? '-',
                    'no_finance' => '-',
                    'coordinate_maps' => $calonCustomer->koordinat ?? '-',
                    'status' => 'Aktif',
                    'note' => null,
                    'tgl_customer_aktif' => now()->toDateString(),
                    'pembayaran_perbulan' => null,
                    'billing_aktif' => null,
                ]);

                // Update ticket dengan CID baru (tetap simpan calon_customer_id)
                $ticket->update([
                    'cid' => $newCid,
                ]);

                // Update SEMUA ticket yang terkait dengan calon_customer ini untuk punya CID yang sama
                // Jadi history dari survey sampai installasi tetap terhubung ke customer baru
                Ticket::where('calon_customer_id', $calonCustomer->id)
                    ->update(['cid' => $newCid]);

                // Update status calon_customer menjadi 'converted' dan simpan CID
                $calonCustomer->update([
                    'status' => 'converted',
                    'converted_to_cid' => $newCid
                ]);
            }
        }

        // Jika installasi re-aktivasi (customer terminate) selesai, ubah status customer jadi Aktif
        if ($ticket->jenis === 'installasi' && $updateStatus === 'done' && $ticket->cid && $ticket->pic_it_lokasi === 'Re-aktivasi') {
            $customer = Customer::where('cid', $ticket->cid)->first();
            if ($customer && (strtolower($customer->status) === 'terminate')) {
                $customer->update([
                    'status' => 'Aktif',
                    'tgl_customer_aktif' => now()->toDateString(),
                ]);
            }
        }

        // Create reply
        $replyMetode = $updateData['metode_penanganan'] ?? $incomingMetode;
        TicketReply::create([
            'ticket_id' => $validated['ticket_id'],
            'user_id' => $user->id,
            'reply' => $validated['reply'],
            'role' => $user->jabatan ?? 'admin',
            'update_status' => $updateStatus,
            'metode_penanganan' => $replyMetode,
            'tanggal_kunjungan' => $validated['tanggal_kunjungan'] ?? null,
            'jam_kunjungan' => $validated['jam'] ?? null,
            'teknisi_id' => $teknisiIds->first() ?? null,
            'teknisi_ids' => $teknisiIds->isNotEmpty() ? $teknisiIds->values()->all() : null,
        ]);

        $this->recalculateSla($ticket);

        return response()->json([
            'message' => 'Reply and ticket details updated successfully',
            'status' => true
        ]);
    }

    // Ambil baseline jadwal terakhir dari ticket dan replies (max datetime)
    protected function getLastScheduleBaseline(Ticket $ticket): ?Carbon
    {
        $dates = [];
        if ($ticket->tanggal_kunjungan) {
            $tTime = $ticket->jam ? (\Carbon\Carbon::parse($ticket->jam)->format('H:i:s')) : '00:00:00';
            $dt = $this->parseScheduleDT(optional($ticket->tanggal_kunjungan)->format('Y-m-d'), $tTime);
            if ($dt) { $dates[] = $dt; }
        }

        $lastReplyWithSchedule = TicketReply::where('ticket_id', $ticket->id)
            ->whereNotNull('tanggal_kunjungan')
            ->orderBy('tanggal_kunjungan', 'desc')
            ->orderBy('jam_kunjungan', 'desc')
            ->first();
        if ($lastReplyWithSchedule) {
            $rTime = $lastReplyWithSchedule->jam_kunjungan ?: '00:00:00';
            $dt = $this->parseScheduleDT($lastReplyWithSchedule->tanggal_kunjungan, $rTime);
            if ($dt) { $dates[] = $dt; }
        }

        if (empty($dates)) return null;
        return collect($dates)->sortDesc()->first();
    }

    // Helper: parse date+time accepting both H:i and H:i:s
    protected function parseScheduleDT($date, $time): ?Carbon
    {
        if (empty($date)) return null;
        
        // Ensure date is just Y-m-d if it comes as a full string or Carbon-ish string
        $dateStr = substr(trim($date), 0, 10);
        $timeStr = $time ?: '00:00:00';
        
        $candidates = [
            ['Y-m-d H:i:s', $dateStr.' '.$timeStr],
            ['Y-m-d H:i', $dateStr.' '.substr($timeStr, 0, 5)],
        ];
        
        foreach ($candidates as [$fmt, $val]) {
            try {
                return Carbon::createFromFormat($fmt, $val);
            } catch (\Exception $e) {
                // try next
            }
        }
        
        try {
            return Carbon::parse($dateStr.' '.$timeStr);
        } catch (\Exception $e) {
            return null;
        }
    }

    // New method: get all replies untuk ticket
    public function getReplies(Request $request)
    {
        $ticket = Ticket::findOrFail($request->ticket_id);

        $replies = TicketReply::where('ticket_id', $request->ticket_id)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($reply) {
                return [
                    'id' => $reply->id,
                    'user_id' => $reply->user_id,
                    'user_name' => $reply->user->name,
                    'user_role' => $reply->role,
                    'reply' => $reply->reply,
                    'update_status' => $reply->update_status,
                    'metode_penanganan' => $reply->metode_penanganan,
                    'tanggal_kunjungan' => $reply->tanggal_kunjungan,
                    'jam_kunjungan' => $reply->jam_kunjungan,
                    'teknisi_id' => $reply->teknisi_id ?? null,
                    'teknisi_ids' => $reply->teknisi_ids ?? null,
                    'created_at' => $reply->created_at->format('d-m-Y H:i'),
                    'created_at_diff' => $reply->created_at->diffForHumans(),
                ];
            });

        // Add ticket's current teknisi info sebagai reference
        $teknisiData = null;
        if ($ticket->teknisi_id) {
            $teknisi = User::find($ticket->teknisi_id);
            if ($teknisi) {
                $teknisiData = [
                    'id' => $teknisi->id,
                    'name' => $teknisi->name,
                    'role' => $teknisi->jabatan ?? 'teknisi'
                ];
            }
        }

        // Build teknisi history berdasarkan teknisi_id yang pernah di-assign
        $teknisiHistory = [];

        // First, add current ticket's teknisi as default
        if ($ticket->teknisi_id && $teknisiData) {
            $teknisiHistory[$ticket->teknisi_id] = [
                'id' => $teknisiData['id'],
                'name' => $teknisiData['name'],
                'role' => $teknisiData['role'],
                'visit_count' => 0,
                'last_visit' => null,
                'last_visit_date' => null,
                'visited_dates' => [] // Track unique dates
            ];
        }

        // Then add/update from replies that have teknisi_ids and tanggal_kunjungan (actual visits)
        foreach ($replies as $reply) {
            $replyTeknisiIds = collect($reply['teknisi_ids'] ?? [])->filter()->values();

            if ($replyTeknisiIds->isEmpty() && $reply['teknisi_id']) {
                $replyTeknisiIds = collect([$reply['teknisi_id']]);
            }

            if ($replyTeknisiIds->isEmpty() || empty($reply['tanggal_kunjungan'])) {
                continue;
            }

            foreach ($replyTeknisiIds as $tid) {
                $teknisi = User::find($tid);
                if ($teknisi) {
                    $key = $tid;
                    if (!isset($teknisiHistory[$key])) {
                        $teknisiHistory[$key] = [
                            'id' => $teknisi->id,
                            'name' => $teknisi->name,
                            'role' => $teknisi->jabatan ?? 'teknisi',
                            'visit_count' => 0,
                            'last_visit' => null,
                            'last_visit_date' => null,
                            'visited_dates' => [] // Track unique dates
                        ];
                    }
                    
                    // Only increment if this date hasn't been counted for this technician yet
                    // Ensure we work with string Y-m-d for checking
                    $dateObj = $reply['tanggal_kunjungan'];
                    $date = $dateObj instanceof \DateTime ? $dateObj->format('Y-m-d') : substr((string)$dateObj, 0, 10);
                    
                    if (!in_array($date, $teknisiHistory[$key]['visited_dates'])) {
                        $teknisiHistory[$key]['visit_count']++;
                        $teknisiHistory[$key]['visited_dates'][] = $date;
                    }
                    
                    // Always update last visit info to the latest one
                    // Format for display: d-m-Y
                    $teknisiHistory[$key]['last_visit'] = $dateObj instanceof \DateTime ? $dateObj->format('d-m-Y') : $date;
                    $teknisiHistory[$key]['last_visit_date'] = $dateObj instanceof \DateTime ? $dateObj->getTimestamp() : strtotime($date);
                }
            }
        }

        // Current teknisi assigned (latest on_progress with teknisi_ids/teknisi_id)
        $currentTeknisi = [];
        $latestActive = $replies->filter(function ($r) {
            return $r['update_status'] === 'on_progress';
        })->last();

        if ($latestActive) {
            $activeIds = collect($latestActive['teknisi_ids'] ?? [])->filter()->values();
            if ($activeIds->isEmpty() && $latestActive['teknisi_id']) {
                $activeIds = collect([$latestActive['teknisi_id']]);
            }

            $activeIds->each(function ($tid) use (&$currentTeknisi) {
                $u = User::find($tid);
                if ($u) {
                    $currentTeknisi[] = [
                        'id' => $u->id,
                        'name' => $u->name,
                        'role' => $u->jabatan ?? 'teknisi'
                    ];
                }
            });
        }

        // Fallback: ticket teknisi_id jika belum ada daftar aktif
        if (empty($currentTeknisi) && $teknisiData) {
            $currentTeknisi[] = $teknisiData;
        }

        // Sort by last visit date desc (current teknisi without visit date goes first)
        usort($teknisiHistory, function($a, $b) {
            $aDate = $a['last_visit_date'] ?? 0;
            $bDate = $b['last_visit_date'] ?? 0;
            if ($aDate === 0 && $bDate === 0) return 0;
            if ($aDate === 0) return -1;
            if ($bDate === 0) return 1;
            return $bDate - $aDate;
        });

        return response()->json([
            'replies' => $replies,
            'teknisi' => $teknisiData,  // Teknisi yang sedang ditugaskan (on progress)
            'teknisi_history' => array_values($teknisiHistory),  // History semua teknisi yang pernah berkunjung
            'current_teknisi' => $currentTeknisi,
            'ticket_status' => $ticket->status,
        ]);
    }

    public function recalculateSla(Ticket $ticket): void
    {
        $replies = $ticket->replies()->orderBy('created_at')->get();
        $ticketCreatedAt = $ticket->created_at;

        $mttrResponse = 0; // Minutes from creation to first acknowledgment
        $mttrResolve = 0;  // Cumulative minutes in "on_progress" onsite segments
        $downtime = 0;     // Total minutes from creation to final resolution (only if onsite work was needed)

        // 1. Calculate MTTR Response
        $firstAckReply = $replies->filter(function($r) {
            return in_array($r->update_status, ['need_visit', 'on_progress', 'done', 'remote_done', 'selesai']);
        })->first();

        if ($firstAckReply) {
            $mttrResponse = max(0, $ticketCreatedAt->diffInMinutes($firstAckReply->created_at));
        } else {
            $mttrResponse = max(0, $ticketCreatedAt->diffInMinutes(Carbon::now()));
        }

        // 2. Calculate MTTR Resolve (Cumulative On-Progress segments)
        $segmentStart = null;
        $segmentMode = null;

        $closeResolveSegment = function (Carbon $end) use (&$segmentStart, &$segmentMode, &$mttrResolve, $ticket) {
            if (!$segmentStart || $segmentMode !== 'onsite') {
                $segmentStart = null;
                $segmentMode = null;
                return;
            }

            $minutes = max(0, $segmentStart->diffInMinutes($end));
            $mttrResolve += $minutes;

            Log::info("MTTR Resolve Segment Close [{$ticket->id}]", [
                'start' => $segmentStart->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
                'minutes' => $minutes
            ]);

            $segmentStart = null;
            $segmentMode = null;
        };

        foreach ($replies as $reply) {
            $status = $reply->update_status;
            $mode = $reply->metode_penanganan
                ?? (($reply->tanggal_kunjungan || $reply->jam_kunjungan) ? 'onsite' : ($ticket->metode_penanganan));

            $timestamp = $reply->created_at instanceof Carbon ? $reply->created_at : Carbon::parse($reply->created_at);
            $isActive = ($status === 'on_progress') && ($mode === 'onsite');

            $plannedStart = null;
            if ($isActive && !empty($reply->tanggal_kunjungan) && !empty($reply->jam_kunjungan)) {
                $plannedStart = $this->parseScheduleDT($reply->tanggal_kunjungan, $reply->jam_kunjungan);
            }

            if ($isActive) {
                if ($segmentStart !== null) {
                    $closeResolveSegment($plannedStart ?? $timestamp);
                }
                $segmentStart = $plannedStart ?? $timestamp;
                $segmentMode = 'onsite';
            } else {
                $end = $timestamp;
                if ($segmentStart && in_array($status, ['done', 'pending', 'selesai'], true) && !empty($reply->tanggal_kunjungan) && !empty($reply->jam_kunjungan)) {
                    $scheduledEnd = $this->parseScheduleDT($reply->tanggal_kunjungan, $reply->jam_kunjungan);
                    if ($scheduledEnd && $scheduledEnd->gte($segmentStart)) {
                        $end = $scheduledEnd;
                    }
                }
                $closeResolveSegment($end);
            }

            if (in_array($status, ['remote_done', 'done', 'selesai'], true)) {
                break;
            }
        }

        if ($segmentStart !== null && $segmentMode === 'onsite') {
            $closeResolveSegment(Carbon::now());
        }

        // 3. Calculate Downtime (Total Duration until service is first restored)
        $resolutionReply = $replies->filter(function($r) {
            return in_array($r->update_status, ['done', 'remote_done', 'selesai']);
        })->first();

        if ($resolutionReply) {
            // Per user request: if resolved remotely (remote_done) or no onsite activity occurred (mttrResolve == 0), downtime is 0
            if ($resolutionReply->update_status === 'remote_done' || $mttrResolve === 0) {
                $downtime = 0;
            } else {
                // Prioritize schedule date/time for end of downtime
                $downtimeEnd = null;
                if (!empty($resolutionReply->tanggal_kunjungan) && !empty($resolutionReply->jam_kunjungan)) {
                    $downtimeEnd = $this->parseScheduleDT($resolutionReply->tanggal_kunjungan, $resolutionReply->jam_kunjungan);
                }
                
                // Fallback to creation of the resolution reply
                $downtimeEnd = $downtimeEnd ?: $resolutionReply->created_at;
                
                // Determine Start of Downtime: 
                // User Request: "ambil dari database ticket replies , tanggal kunjungan dan jam kunjungan , jangan ambil dari time stamp"
                // Interpretation: Start from the 'need_visit' manual schedule fields.
                $needVisitReply = $replies->filter(function($r) {
                    return $r->update_status === 'need_visit';
                })->first();

                $downtimeStart = $ticketCreatedAt; // Default fallback

                if ($needVisitReply) {
                    // Try to parse manual schedule
                    if (!empty($needVisitReply->tanggal_kunjungan) && !empty($needVisitReply->jam_kunjungan)) {
                        $downtimeStart = $this->parseScheduleDT($needVisitReply->tanggal_kunjungan, $needVisitReply->jam_kunjungan);
                        // If parsing failed (shouldn't if valid), it returns null or throws? parseScheduleDT returns Carbon or null?
                        // Based on previous code, parseScheduleDT returns Carbon.
                        if (!$downtimeStart) {
                             $downtimeStart = $needVisitReply->created_at;
                        }
                    } else {
                        // Fallback if manual fields missing
                        $downtimeStart = $needVisitReply->created_at;
                    }
                }

                // Ensure start <= end to avoid negative
                if ($downtimeStart->gt($downtimeEnd)) {
                     // Fallback check: maybe needVisit was *after* resolution? Unlikely logic but safe to fallback
                     $downtimeStart = $ticketCreatedAt;
                }

                $downtime = max(0, $downtimeStart->diffInMinutes($downtimeEnd));
            }
        } else {
            // If still open and onsite work is involved, count until now
            if ($mttrResolve > 0) {
                $downtime = max(0, $ticketCreatedAt->diffInMinutes(Carbon::now()));
            } else {
                $downtime = 0;
            }
        }

        $ticket->update([
            'sla_remote_minutes' => $mttrResponse,
            'sla_onsite_minutes' => $mttrResolve,
            'sla_total_minutes' => $downtime,
        ]);
    }

    public function updateRfo(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'indikasi' => 'nullable|string',
            'masalah' => 'nullable|string', // Maps to kendala
            'solusi' => 'nullable|string',
        ]);

        $ticket = Ticket::findOrFail($validated['ticket_id']);
        
        $ticket->update([
            'indikasi' => $validated['indikasi'],
            'kendala' => $validated['masalah'],
            'solusi' => $validated['solusi'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'RFO updated successfully'
        ]);
    }
    /**
     * Export single ticket RFO to PDF (HTML view for printing)
     */
    public function exportSingleRfo($id)
    {
        $ticket = Ticket::with(['customer', 'calonCustomer', 'replies' => function($q) {
            $q->orderBy('created_at', 'asc');
        }])->findOrFail($id);

        // Get and base64 encode logo for PDF
        $logoPath = public_path('assets/img/logo-rfo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoBase64 = 'data:image/png;base64,' . $logoData;
        }

        // Generate ticket no
        $tanggal = $ticket->tanggal_kunjungan 
            ? (\Carbon\Carbon::parse($ticket->tanggal_kunjungan)->format('dmY')) 
            : $ticket->created_at->format('dmY');
        $jam = $ticket->jam ? date('Hi', strtotime($ticket->jam)) : $ticket->created_at->format('Hi');
        $no = str_pad($ticket->id, 3, '0', STR_PAD_LEFT);
        $ticketNo = "TDN-{$tanggal}-{$jam}-{$no}";

        // Calculate Downtime Start/End
        $downtimeStart = $ticket->created_at;
        $needVisit = $ticket->replies->where('update_status', 'need_visit')->first();
        if ($needVisit) {
            if ($needVisit->tanggal_kunjungan && $needVisit->jam_kunjungan) {
                try {
                    $dPart = ($needVisit->tanggal_kunjungan instanceof \DateTimeInterface) 
                        ? $needVisit->tanggal_kunjungan->format('Y-m-d') 
                        : substr((string)$needVisit->tanggal_kunjungan, 0, 10);
                    $downtimeStart = \Carbon\Carbon::parse($dPart . ' ' . $needVisit->jam_kunjungan);
                } catch (\Exception $e) {
                    $downtimeStart = $needVisit->created_at;
                }
            } else {
                $downtimeStart = $needVisit->created_at;
            }
        }

        $selesaiReply = $ticket->replies->filter(function($r) {
            return in_array($r->update_status, ['done', 'remote_done', 'selesai']);
        })->last();

        $downtimeEnd = $selesaiReply ? $selesaiReply->created_at : now();
        if ($selesaiReply && $selesaiReply->tanggal_kunjungan && $selesaiReply->jam_kunjungan) {
            try {
                $dPart = ($selesaiReply->tanggal_kunjungan instanceof \DateTimeInterface) 
                    ? $selesaiReply->tanggal_kunjungan->format('Y-m-d') 
                    : substr((string)$selesaiReply->tanggal_kunjungan, 0, 10);
                $downtimeEnd = \Carbon\Carbon::parse($dPart . ' ' . $selesaiReply->jam_kunjungan);
            } catch (\Exception $e) {}
        }

        // Calculate Pending Duration
        $pendingDuration = 0;
        $pendingReasons = [];
        $pendingStart = null;
        
        // Helper to parse reply schedule
        $getReplySchedule = function($reply) {
            if (!empty($reply->tanggal_kunjungan) && !empty($reply->jam_kunjungan)) {
                try {
                    $dPart = ($reply->tanggal_kunjungan instanceof \DateTimeInterface) 
                        ? $reply->tanggal_kunjungan->format('Y-m-d') 
                        : substr((string)$reply->tanggal_kunjungan, 0, 10);
                    return \Carbon\Carbon::parse($dPart . ' ' . $reply->jam_kunjungan);
                } catch (\Exception $e) { return null; }
            }
            return null;
        };

        foreach ($ticket->replies as $reply) {
            // Only 'pending' status triggers the start, skipping 'need_visit' as requested
            if ($reply->update_status === 'pending') {
                // Try to get schedule, otherwise fallback to created_at (though user request implies schedule is key)
                $schedule = $getReplySchedule($reply);
                $pendingStart = $schedule ?: $reply->created_at;
                
                // Collect reason
                if (!empty($reply->reply) && !in_array($reply->reply, $pendingReasons)) {
                    $pendingReasons[] = $reply->reply;
                }
            } elseif ($reply->update_status === 'on_progress' && $pendingStart) {
                // If we found a pending start, this on_progress marks the end
                // Use THIS reply's schedule as the end time
                $schedule = $getReplySchedule($reply);
                $pendingEnd = $schedule ?: $reply->created_at;
                
                $mins = ceil($pendingStart->floatDiffInMinutes($pendingEnd));
                $pendingDuration += $mins;
                
                // Store the date range string (e.g. "02 Jan 10:00 - 02 Jan 12:00")
                // Format: d M Y H:i
                $startStr = $pendingStart->translatedFormat('d F Y H:i');
                $endStr = $pendingEnd->translatedFormat('d F Y H:i');
                $pendingDates[] = "{$startStr} - {$endStr}";

                $pendingStart = null; // Reset
            }
        }
        
        $pendingReason = !empty($pendingReasons) ? implode(', ', $pendingReasons) : '-';
        $pendingDateStr = !empty($pendingDates) ? implode(', ', $pendingDates) : '-';

        $pendingDurationStr = '-';
        if ($pendingDuration > 0) {
            $pHours = intdiv($pendingDuration, 60);
            $pMins = $pendingDuration % 60;
            $pendingDurationStr = ($pHours > 0 ? $pHours . " Hours " : "") . $pMins . " Minutes";
        }

        $hours = intdiv($ticket->sla_total_minutes, 60);
        $mins = $ticket->sla_total_minutes % 60;
        $totalDowntime = ($hours > 0 ? $hours . " Hours " : "") . $mins . " Minutes";
        
        // Map data for updated PDF layout
        $packet = $ticket->customer ? $ticket->customer->packet : '-'; // Jenis Layanan from customer packet

        // Tindakan taken from solusi as RFO modal updates 'solusi'
        $tindakan = $ticket->solusi ?: $ticket->hasil;

        $data = (object)[
            'ticket_no' => $ticketNo,
            'customer_nama' => $ticket->jenis === 'survey' ? ($ticket->calonCustomer ? $ticket->calonCustomer->nama : '-') : ($ticket->customer ? $ticket->customer->nama : '-'),
            'customer_id' => $ticket->cid ?? '-',
            'downtime_start' => $downtimeStart->translatedFormat('l, d F Y H:i:s'), // Format: Friday, 02 January 2026 15:00:00
            'downtime_end' => ($selesaiReply || $ticket->status === 'selesai') ? $downtimeEnd->translatedFormat('l, d F Y H:i:s') : '-',
            'total_downtime' => $totalDowntime, // "X Hours Y Minutes"
            'jenis_layanan' => $packet,
            'jenis_permasalahan' => $ticket->indikasi ?: '-', // Dari indikasi
            'penyebab' => $ticket->kendala ?: '-', // Dari masalah (kendala)
            'tindakan' => $tindakan ?: '-', // Dari solusi/hasil
            'pending_duration' => $pendingDurationStr,
            'pending_reason' => $pendingReason,
            'pending_date' => $pendingDateStr,
        ];
        
        // Set locale to English
        \Carbon\Carbon::setLocale('en');

        return view('content.report.pdf-rfo', [
            'ticket' => $data, 
            'logoBase64' => $logoBase64
        ]);
    }
}
