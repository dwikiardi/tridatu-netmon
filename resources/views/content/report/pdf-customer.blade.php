@php
    // Set default columns jika tidak ada atau kosong
    if (!isset($selectedColumns) || empty($selectedColumns)) {
        $selectedColumns = ['cid', 'nama', 'email', 'alamat', 'packet', 'pembayaran_perbulan', 'pop', 'setup_fee', 'status', 'sales', 'pic_it', 'tgl_customer_aktif'];
    }
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Report Customer Data</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .report-header {
            text-align: center;
            margin-bottom: 20px;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #3498db;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 11px;
            border: 1px solid #34495e;
        }
        td {
            padding: 8px;
            border: 1px solid #bdc3c7;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        tr:hover {
            background-color: #ecf0f1;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 10px;
            color: #7f8c8d;
            border-top: 1px solid #bdc3c7;
            padding-top: 10px;
        }
        .summary-section {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #ecf0f1;
            border-left: 4px solid #3498db;
        }
        .summary-section h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 12px;
        }
        .summary-item {
            display: inline-block;
            margin-right: 30px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <h1>LAPORAN DATA PELANGGAN</h1>

    <div class="report-header">
        <p>Generated: {{ now()->format('d-m-Y H:i:s') }}</p>
        <p>Total Records: {{ $data->count() }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                @if(in_array('cid', $selectedColumns))
                    <th>Customer ID</th>
                @endif
                @if(in_array('nama', $selectedColumns))
                    <th>Nama</th>
                @endif
                @if(in_array('email', $selectedColumns))
                    <th>Email</th>
                @endif
                @if(in_array('alamat', $selectedColumns))
                    <th>Alamat</th>
                @endif
                @if(in_array('coordinate_maps', $selectedColumns))
                    <th>Koordinat</th>
                @endif
                @if(in_array('packet', $selectedColumns))
                    <th>Packet</th>
                @endif
                @if(in_array('pembayaran_perbulan', $selectedColumns))
                    <th>Pembayaran/Bulan</th>
                @endif
                @if(in_array('pop', $selectedColumns))
                    <th>POP</th>
                @endif
                @if(in_array('setup_fee', $selectedColumns))
                    <th>Setup Fee</th>
                @endif
                @if(in_array('status', $selectedColumns))
                    <th>Status</th>
                @endif
                @if(in_array('sales', $selectedColumns))
                    <th>Sales</th>
                @endif
                @if(in_array('pic_it', $selectedColumns))
                    <th>PIC IT</th>
                @endif
                @if(in_array('no_it', $selectedColumns))
                    <th>No IT</th>
                @endif
                @if(in_array('pic_finance', $selectedColumns))
                    <th>PIC Finance</th>
                @endif
                @if(in_array('no_finance', $selectedColumns))
                    <th>No Finance</th>
                @endif
                @if(in_array('tgl_customer_aktif', $selectedColumns))
                    <th>Tgl Aktif</th>
                @endif
                @if(in_array('billing_aktif', $selectedColumns))
                    <th>Billing Aktif</th>
                @endif
                @if(in_array('note', $selectedColumns))
                    <th>Note</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $customer)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @if(in_array('cid', $selectedColumns))
                        <td>{{ $customer->cid }}</td>
                    @endif
                    @if(in_array('nama', $selectedColumns))
                        <td>{{ $customer->nama }}</td>
                    @endif
                    @if(in_array('email', $selectedColumns))
                        <td>{{ $customer->email }}</td>
                    @endif
                    @if(in_array('alamat', $selectedColumns))
                        <td>{{ $customer->alamat }}</td>
                    @endif
                    @if(in_array('coordinate_maps', $selectedColumns))
                        <td>{{ $customer->coordinate_maps ?? '-' }}</td>
                    @endif
                    @if(in_array('packet', $selectedColumns))
                        <td>{{ $customer->packet }}</td>
                    @endif
                    @if(in_array('pembayaran_perbulan', $selectedColumns))
                        <td>Rp. {{ number_format($customer->pembayaran_perbulan, 0, ',', '.') }}</td>
                    @endif
                    @if(in_array('pop', $selectedColumns))
                        <td>{{ $customer->pop ?? '-' }}</td>
                    @endif
                    @if(in_array('setup_fee', $selectedColumns))
                        <td>{{ $customer->setup_fee ? 'Rp. ' . number_format($customer->setup_fee, 0, ',', '.') : '-' }}</td>
                    @endif
                    @if(in_array('status', $selectedColumns))
                        <td>{{ ucfirst($customer->status) }}</td>
                    @endif
                    @if(in_array('sales', $selectedColumns))
                        <td>{{ $customer->sales }}</td>
                    @endif
                    @if(in_array('pic_it', $selectedColumns))
                        <td>{{ $customer->pic_it ?? '-' }}</td>
                    @endif
                    @if(in_array('no_it', $selectedColumns))
                        <td>{{ $customer->no_it ?? '-' }}</td>
                    @endif
                    @if(in_array('pic_finance', $selectedColumns))
                        <td>{{ $customer->pic_finance ?? '-' }}</td>
                    @endif
                    @if(in_array('no_finance', $selectedColumns))
                        <td>{{ $customer->no_finance ?? '-' }}</td>
                    @endif
                    @if(in_array('tgl_customer_aktif', $selectedColumns))
                        <td>{{ $customer->tgl_customer_aktif ?? '-' }}</td>
                    @endif
                    @if(in_array('billing_aktif', $selectedColumns))
                        <td>{{ $customer->billing_aktif ?? '-' }}</td>
                    @endif
                    @if(in_array('note', $selectedColumns))
                        <td>{{ $customer->note ?? '-' }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($selectedColumns) + 1 }}" style="text-align: center;">Data tidak ditemukan</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Laporan ini di-generate secara otomatis oleh sistem. Silakan hubungi tim IT untuk pertanyaan lebih lanjut.</p>
    </div>
</body>
</html>
