<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Ticket;

class Analytics extends Controller
{
  public function index()
  {
    $user = Auth::user();
    $userName = $user->name;
    $userJabatan = $user->jabatan;

    // Count customer milik user (untuk greeting card sales)
    $userCustomerCount = 0;
    if ($userJabatan === 'sales') {
      $userCustomerCount = Customer::where('status', 'aktif')
        ->where('sales', $userName)
        ->count();
    }

    // Ambil semua customer aktif (untuk card Total Customer dan chart - ALWAYS GLOBAL)
    $customerCount = Customer::where('status', 'aktif')->count();
    $totalPembayaran = Customer::where('status', 'aktif')->sum('pembayaran_perbulan');

    // Format compact Indonesia: 500k, 1 Jt, 1M
    if ($totalPembayaran >= 1000000000) {
      // Milyar -> M
      $totalPembayaranFormatted = rtrim(rtrim(number_format($totalPembayaran / 1000000000, 1, ',', '.'), '0'), ',') . 'M';
    } elseif ($totalPembayaran >= 1000000) {
      // Juta -> Jt
      $totalPembayaranFormatted = rtrim(rtrim(number_format($totalPembayaran / 1000000, 1, ',', '.'), '0'), ',') . ' Jt';
    } elseif ($totalPembayaran >= 1000) {
      // Ribu -> k
      $totalPembayaranFormatted = rtrim(rtrim(number_format($totalPembayaran / 1000, 1, ',', '.'), '0'), ',') . 'k';
    } else {
      $totalPembayaranFormatted = (string) number_format($totalPembayaran, 0, ',', '.');
    }

    // Count active tickets (status bukan 'selesai')
    $ticketAktifCount = Ticket::where('status', '!=', 'selesai')->count();

    // Get revenue data grouped by month (ALWAYS GLOBAL - semua revenue)
    $revenueByMonth = Customer::select(
      DB::raw('MONTH(tgl_customer_aktif) as month'),
      DB::raw('YEAR(tgl_customer_aktif) as year'),
      DB::raw('COUNT(*) as count'),
      DB::raw('SUM(pembayaran_perbulan) as total_revenue')
    )
    ->whereNotNull('tgl_customer_aktif')
    ->groupBy('year', 'month')
    ->orderBy('year', 'asc')
    ->orderBy('month', 'asc')
    ->get();

    // Format data for chart - organize by year
    $monthlyChartData = [];
    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    foreach ($revenueByMonth as $data) {
      $year = $data->year;
      if (!isset($monthlyChartData[$year])) {
        $monthlyChartData[$year] = [];
      }
      $monthlyChartData[$year][] = [
        'month' => $monthNames[$data->month - 1],
        'count' => $data->count,
        'revenue' => $data->total_revenue ?? 0
      ];
    }

    // Get yearly revenue data for growth calculation (ALWAYS GLOBAL - semua revenue)
    $yearlyRevenue = Customer::select(
      DB::raw('YEAR(tgl_customer_aktif) as year'),
      DB::raw('COUNT(*) as customer_count'),
      DB::raw('SUM(pembayaran_perbulan) as total_revenue')
    )
    ->whereNotNull('tgl_customer_aktif')
    ->groupBy('year')
    ->orderBy('year', 'desc')
    ->get();

    $yearlyData = [];
    foreach ($yearlyRevenue as $data) {
      $yearlyData[] = [
        'year' => $data->year,
        'customer_count' => $data->customer_count,
        'total_revenue' => $data->total_revenue ?? 0
      ];
    }

    return view('content.dashboard.dashboards-analytics', compact('userName', 'customerCount', 'userCustomerCount', 'totalPembayaran', 'totalPembayaranFormatted', 'monthlyChartData', 'yearlyData', 'ticketAktifCount'));
  }
}
