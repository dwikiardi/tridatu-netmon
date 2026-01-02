@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard - Analytics')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}">
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>
@endsection

@section('page-script')
<style>
  #currentYearRevenue, #prevYearRevenue {
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: visible;
  }
</style>
<script>
  // Data from controller
  const yearlyDataFromDB = @json($yearlyData);
  const monthlyChartDataFromDB = @json($monthlyChartData);

  function formatCurrency(value) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(Number(value || 0)));
  }

  function formatCurrencyCompact(value) {
    const num = Math.round(Number(value || 0));
    if (num >= 1000000000) {
      const whole = num % 1000000000 === 0;
      return (num / 1000000000).toFixed(whole ? 0 : 1) + 'M'; // M for milyar
    } else if (num >= 1000000) {
      const whole = num % 1000000 === 0;
      return (num / 1000000).toFixed(whole ? 0 : 1) + ' Jt'; // Jt for juta
    } else if (num >= 1000) {
      const whole = num % 1000 === 0;
      return (num / 1000).toFixed(whole ? 0 : 1) + 'k'; // k for ribu
    }
    return String(num);
  }

  let revenueChartInstance = null;

  function renderChart(chartData) {
    const el = document.querySelector('#totalRevenueChart');
    if (!el || !window.ApexCharts) return;

    // Normalize to full-year months and map values
    const allMonths = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const valueByMonth = new Map();
    (chartData || []).forEach(d => valueByMonth.set(d.month, Number(d.revenue) || 0));
    const revenues = allMonths.map(m => valueByMonth.get(m) || 0);

    // Destroy previous chart completely
    if (revenueChartInstance) {
      revenueChartInstance.destroy();
      revenueChartInstance = null;
    }
    el.innerHTML = '';

    const options = {
      series: [{ name: 'Revenue', data: revenues }],
      chart: { height: 300, type: 'bar', toolbar: { show: false } },
      plotOptions: { bar: { horizontal: false, columnWidth: '33%', borderRadius: 12 } },
      colors: ['#696cff'],
      dataLabels: { enabled: false },
      grid: { borderColor: '#eceef1', padding: { top: 0, bottom: -8, left: 20, right: 20 } },
      xaxis: { categories: allMonths, labels: { style: { fontSize: '13px', colors: '#697a8d' } }, axisTicks: { show: false }, axisBorder: { show: false } },
      yaxis: { labels: { style: { fontSize: '13px', colors: '#697a8d' } } },
      states: { hover: { filter: { type: 'none' } }, active: { filter: { type: 'none' } } }
    };

    revenueChartInstance = new ApexCharts(el, options);
    revenueChartInstance.render();
  }

  let growthChartInstance = null;

  function renderGrowthChart(growthPercentage) {
    const el = document.querySelector('#growthChart');
    if (!el) {
      console.error('Growth chart element not found');
      return;
    }
    if (!window.ApexCharts) {
      console.error('ApexCharts not loaded');
      return;
    }

    console.log('Rendering growth chart with percentage:', growthPercentage);

    // Destroy previous chart
    if (growthChartInstance) {
      try {
        growthChartInstance.destroy();
      } catch(e) {
        console.log('Error destroying chart:', e);
      }
      growthChartInstance = null;
    }
    el.innerHTML = '';

    // Pastikan ada nilai, default ke 0 jika tidak ada previous year data
    const displayValue = growthPercentage || 0;
    const absValue = Math.abs(displayValue);

    // ApexCharts radial bar tidak terlihat jika 0 atau terlalu kecil, set minimum 10 untuk visibility
    const chartValue = absValue === 0 ? 10 : (absValue < 10 ? 10 : absValue);

    console.log('Rendering growth chart - Display value:', displayValue, 'Absolute:', absValue, 'Chart value:', chartValue);

    const options = {
      series: [chartValue],
      labels: ['Growth'],
      chart: { height: 240, type: 'radialBar' },
      plotOptions: {
        radialBar: {
          size: 150,
          offsetY: 10,
          startAngle: -150,
          endAngle: 150,
          hollow: { size: '55%' },
          track: { background: '#eceef1', strokeWidth: '100%' },
          dataLabels: {
            name: {
              offsetY: 15,
              color: '#697a8d',
              fontSize: '15px',
              fontWeight: '600',
              formatter: function() {
                return 'Growth';
              }
            },
            value: {
              offsetY: -25,
              color: displayValue >= 0 ? '#696cff' : '#ff3e1d',
              fontSize: '22px',
              fontWeight: '500',
              formatter: function() {
                if (displayValue === 0) return '0%';
                return (displayValue > 0 ? '+' : '') + displayValue + '%';
              }
            }
          }
        }
      },
      colors: [displayValue >= 0 ? '#696cff' : '#ff3e1d'],
      fill: {
        type: 'gradient',
        gradient: {
          shade: 'dark',
          shadeIntensity: 0.5,
          gradientToColors: [displayValue >= 0 ? '#696cff' : '#ff3e1d'],
          inverseColors: true,
          opacityFrom: 1,
          opacityTo: 0.6,
          stops: [30, 70, 100]
        }
      },
      stroke: { dashArray: 5 },
      grid: { padding: { top: -35, bottom: -10 } }
    };

    growthChartInstance = new ApexCharts(el, options);
    growthChartInstance.render();
    console.log('Growth chart rendered successfully');
  }

  function updateGrowthDisplay(startYear, endYear) {
    console.log('Updating growth from', startYear, 'to', endYear);
    console.log('Available yearly data:', yearlyDataFromDB);

    const startYearData = yearlyDataFromDB.find(y => Number(y.year) === Number(startYear));
    const endYearData = yearlyDataFromDB.find(y => Number(y.year) === Number(endYear));

    console.log('Start year data:', startYearData);
    console.log('End year data:', endYearData);

    // Update labels
    document.getElementById('currentYearLabel').textContent = endYear;
    document.getElementById('currentYearRevenue').textContent = formatCurrencyCompact(endYearData?.total_revenue || 0);
    document.getElementById('prevYearLabel').textContent = startYear;
    document.getElementById('prevYearRevenue').textContent = formatCurrencyCompact(startYearData?.total_revenue || 0);

    const startVal = Number(startYearData?.total_revenue || 0);
    const endVal = Number(endYearData?.total_revenue || 0);
    const yearsDiff = Number(endYear) - Number(startYear);

    let growth = 0;

    if (startVal > 0 && yearsDiff > 0) {
      // Calculate CAGR: ((EndValue/StartValue)^(1/Years) - 1) * 100
      const ratio = endVal / startVal;
      const cagr = (Math.pow(ratio, 1/yearsDiff) - 1) * 100;
      growth = Math.round(cagr);
    } else if (startVal > 0 && yearsDiff === 0) {
      // Same year, no growth
      growth = 0;
    } else if (startVal > 0) {
      // Simple growth for same year or edge cases
      growth = Math.round(((endVal - startVal) / startVal) * 100);
    }

    console.log('Start revenue:', startVal, 'End revenue:', endVal, 'Years:', yearsDiff, 'CAGR:', growth + '%');

    document.getElementById('growthPercent').textContent = growth;

    // Update growth chart with calculated percentage
    renderGrowthChart(growth);
  }

  document.addEventListener('DOMContentLoaded', function () {
    const startYearEl = document.getElementById('startYearPicker');
    const endYearEl = document.getElementById('endYearPicker');

    // Sort years ascending
    const sortedYears = [...yearlyDataFromDB].sort((a, b) => Number(a.year) - Number(b.year));
    const firstYear = sortedYears[0]?.year;
    const latestYear = sortedYears[sortedYears.length - 1]?.year;

    // Set default: first year -> latest year
    if (startYearEl) startYearEl.value = firstYear;
    if (endYearEl) endYearEl.value = latestYear;

    updateGrowthDisplay(firstYear, latestYear);
    renderChart((monthlyChartDataFromDB && monthlyChartDataFromDB[latestYear]) || []);

    // Event listeners for both dropdowns
    if (startYearEl) {
      startYearEl.addEventListener('change', function () {
        const startYear = parseInt(this.value, 10);
        const endYear = parseInt(endYearEl.value, 10);
        updateGrowthDisplay(startYear, endYear);
      });
    }

    if (endYearEl) {
      endYearEl.addEventListener('change', function () {
        const startYear = parseInt(startYearEl.value, 10);
        const endYear = parseInt(this.value, 10);
        updateGrowthDisplay(startYear, endYear);
        renderChart((monthlyChartDataFromDB && monthlyChartDataFromDB[endYear]) || []);
      });
    }
  });
</script>
@endsection

@section('content')
<div class="row">
  <div class="col-lg-8 mb-4 order-0">
    <div class="card">
      <div class="d-flex align-items-end row">
        <div class="col-sm-7">
          <div class="card-body">
            <h5 class="card-title text-primary">Hello {{$userName}}! Mau Ngapain Nih?🎉</h5>
            @if(auth()->user()->jabatan === 'sales')
              <p class="mb-4">Jumlah Pelangganmu <span class="fw-medium">{{$userCustomerCount ?? 0}}</span> Ayo Semangat Nambah Pelanggan.</p>
            @elseif(auth()->user()->jabatan === 'teknisi')
              <p class="mb-4">Jumlah Ticket Hari Ini <span class="fw-medium">{{$ticketAktifCount}}</span> Semoga Gak Tipes Ya</p>
            @else
              <p class="mb-4">Jumlah Pelangganmu <span class="fw-medium">{{$customerCount}}</span> Ayo Semangat Nambah Pelanggan.</p>
            @endif
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-left">
          <div class="card-body pb-0 px-0 px-md-4">
            <img src="{{asset('assets/img/illustrations/man-with-laptop-light.png')}}" height="140" alt="View Badge User" data-app-dark-img="illustrations/man-with-laptop-dark.png" data-app-light-img="illustrations/man-with-laptop-light.png">
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4 col-md-4 order-1">
    <div class="row">
      <div class="col-12 mb-4">
        <div class="card">
          <div class="card-body">
            <div class="card-title d-flex align-items-start justify-content-between">
              <div class="avatar flex-shrink-0">
                <img src="{{asset('assets/img/icons/unicons/chart-success.png')}}" alt="chart success" class="rounded">
              </div>
              <div class="dropdown">
                <button class="btn p-0" type="button" id="cardOpt3" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                  <a class="dropdown-item" href="javascript:void(0);">View More</a>
                  <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                </div>
              </div>
            </div>
            <span class="fw-semibold d-block mb-1">Revenue Perusahaan</span>
            <h3 class="card-title mb-2">{{$totalPembayaranFormatted}}</h3>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-8 order-2 order-md-3 order-lg-2 mb-4">
    <div class="card">
      <div class="row row-bordered g-0">
        <div class="col-md-8">
          <h5 class="card-header m-0 me-2 pb-3">Total Revenue Tri Datu</h5>
          <div id="totalRevenueChart" class="px-2"></div>
        </div>
        <div class="col-md-4">
          <div class="card-body">
            <div class="text-center">
              <div class="d-flex justify-content-center align-items-center gap-2">
                <select id="startYearPicker" class="form-control" style="width: auto;">
                  @foreach($yearlyData as $year)
                  <option value="{{ $year['year'] }}">{{ $year['year'] }}</option>
                  @endforeach
                </select>
                <span class="mx-2">→</span>
                <select id="endYearPicker" class="form-control" style="width: auto;">
                  @foreach($yearlyData as $year)
                  <option value="{{ $year['year'] }}">{{ $year['year'] }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div id="growthChart"></div>
          <div class="text-center fw-medium pt-3 mb-2"><span id="growthPercent">0</span>% Growth</div>

          <div class="d-flex px-xxl-4 px-lg-2 p-4 gap-xxl-3 gap-lg-1 gap-3 justify-content-between">
            <div class="d-flex">
              <div class="me-2">
                <span class="badge bg-label-primary p-2"><i class="bx bx-dollar text-primary"></i></span>
              </div>
              <div class="d-flex flex-column">
                <small id="currentYearLabel">2025</small>
                <h6 class="mb-0" id="currentYearRevenue">Rp 0</h6>
              </div>
            </div>
            <div class="d-flex">
              <div class="me-2">
                <span class="badge bg-label-info p-2"><i class="bx bx-dollar text-primary"></i></span>
              </div>
              <div class="d-flex flex-column">
                <small id="prevYearLabel">2024</small>
                <h6 class="mb-0" id="prevYearRevenue">Rp 0</h6>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--/ Total Revenue -->
  <div class="col-12 col-md-8 col-lg-4 order-3 order-md-2">
    <div class="row">
      <div class="col-12 mb-4">
        <div class="card">
          <div class="card-body">
            <div class="card-title d-flex align-items-start justify-content-between">
              <div class="avatar flex-shrink-0">
                <img src="{{asset('assets/img/icons/unicons/wallet-info.png')}}" alt="Credit Card" class="rounded">
              </div>
              <div class="dropdown">
                <button class="btn p-0" type="button" id="cardOpt6" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt6">
                  <a class="dropdown-item" href="javascript:void(0);">View More</a>
                  <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                </div>
              </div>
            </div>
            <span class="fw-semibold d-block mb-1">Total Customer</span>
            <h3 class="card-title text-nowrap mb-2">{{$customerCount}}</h3>
          </div>
        </div>
      </div>
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <div class="card-title d-flex align-items-start justify-content-between">
              <div class="avatar flex-shrink-0">
                <img src="{{asset('assets/img/icons/unicons/cc-warning.png')}}" alt="Ticket" class="rounded">
              </div>
              <div class="dropdown">
                <button class="btn p-0" type="button" id="cardOpt7" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="bx bx-dots-vertical-rounded"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt7">
                  <a class="dropdown-item" href="javascript:void(0);">View More</a>
                  <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                </div>
              </div>
            </div>
            <span class="fw-semibold d-block mb-1">Ticket Aktif</span>
            <h3 class="card-title text-nowrap mb-2">{{$ticketAktifCount}}</h3>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

