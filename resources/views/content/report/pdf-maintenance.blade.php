<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Report Maintenance Data</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 10px;
            color: #333;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            border-bottom: 2px solid #e74c3c;
            padding-bottom: 5px;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .report-header {
            text-align: center;
            margin-bottom: 15px;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th {
            background-color: #e74c3c;
            color: white;
            padding: 6px;
            text-align: left;
            font-size: 9px;
            border: 1px solid #c0392b;
        }
        td {
            padding: 6px;
            border: 1px solid #bdc3c7;
            font-size: 9px;
            word-wrap: break-word;
            vertical-align: top;
        }
        .row-main {
            background-color: #f8f9fa;
        }
        .row-details {
            background-color: #ffffff;
        }
        .label-text {
            font-weight: bold;
            color: #c0392b;
            display: block;
            margin-bottom: 2px;
        }
        .content-text {
            display: block;
            margin-bottom: 8px;
            line-height: 1.4;
            text-align: justify;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 9px;
            color: #7f8c8d;
            border-top: 1px solid #bdc3c7;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <h1>LAPORAN MAINTENANCE & TICKETING</h1>

    <div class="report-header">
        <p>Generated: {{ now()->format('d-m-Y H:i:s') }} | Total Records: {{ $data->count() }}</p>
    </div>

    @php
        // Check if RFO Data is selected
        $showRfoObj = in_array('rfo_data', $selectedColumns);

        // Define long columns (details row)
        // If RFO Data is selected, use RFO fields. Otherwise default to old behavior (if kendala/hasil selected)
        $longCols = [];
        if ($showRfoObj) {
            $longCols = ['indikasi', 'kendala', 'solusi'];
        } else {
            $longCols = ['kendala', 'hasil', 'solusi'];
        }

        // Separate long columns from short columns for table header
        $displayShortCols = array_filter($selectedColumns, function($col) use ($longCols) {
            return !in_array($col, $longCols) && $col !== 'rfo_data';
        });
        
        // We manually force the long cols to be displayed if RFO is active, 
        // effectively treating 'rfo_data' as the trigger for these columns.
        $displayLongCols = $longCols;
        
        // However, we only want to show the long cols if they were actually selected (for the legacy case)
        // For RFO case, 'rfo_data' IS the selection.
        if (!$showRfoObj) {
            $displayLongCols = array_filter($selectedColumns, function($col) use ($longCols) {
                return in_array($col, $longCols);
            });
        }

        $columnNames = [
            'ticket_no' => 'Ticket NO',
            'id' => 'ID',
            'cid' => 'Cust ID',
            'customer_nama' => 'Customer',
            'jenis' => 'Jenis',
            'pic_teknisi' => 'Teknisi',
            'tanggal_kunjungan' => 'Tgl Kunjungan',
            'sla_remote_minutes' => 'MTTR Response',
            'sla_onsite_minutes' => 'MTTR Resolve',
            'sla_total_minutes' => 'Downtime',
            'kendala' => $showRfoObj ? 'Root Cause' : 'Kendala',
            'hasil' => 'Hasil',
            'solusi' => 'Action',
            'indikasi' => 'Problem Type',
            'status' => 'Status',
            'priority' => 'Priority',
            'maintenance_count' => 'Maint. Count'
        ];
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                @foreach($displayShortCols as $col)
                    <th>{{ $columnNames[$col] ?? ucfirst($col) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $ticket)
                <tr class="row-main">
                    <td>{{ $index + 1 }}</td>
                    @foreach($displayShortCols as $col)
                        <td>
                            @if($col == 'ticket_no')
                                {{ $ticket->ticket_no ?? $ticket->id }}
                            @elseif($col == 'tanggal_kunjungan')
                                {{ $ticket->tanggal_kunjungan ? $ticket->tanggal_kunjungan->format('d-m-Y') : '-' }}
                            @elseif($col == 'cid')
                                {{ $ticket->jenis === 'survey' ? 'TDNSurvey' : ($ticket->cid ?? '-') }}
                            @elseif($col == 'customer_nama')
                                {{ $ticket->customer ? $ticket->customer->nama : ($ticket->calonCustomer ? $ticket->calonCustomer->nama : '-') }}
                            @elseif($col == 'sla_remote_minutes')
                                {{ $ticket->sla_remote_formatted ?: '0m' }}
                            @elseif($col == 'sla_onsite_minutes')
                                {{ $ticket->sla_onsite_formatted ?: '0m' }}
                            @elseif($col == 'sla_total_minutes')
                                {{ $ticket->sla_total_formatted ?: '0m' }}
                            @else
                                {{ $ticket->$col ?? '-' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
                @if(count($displayLongCols) > 0)
                <tr class="row-details">
                    <td colspan="{{ count($displayShortCols) + 1 }}" style="padding: 10px 15px;">
                        @foreach($displayLongCols as $col)
                            <span class="label-text">{{ $columnNames[$col] ?? ucfirst($col) }}:</span>
                            <div class="content-text">
                                @if($col == 'hasil')
                                    {!! nl2br(e($ticket->hasil)) !!}
                                @else
                                    {!! nl2br(e($ticket->$col)) !!}
                                @endif
                            </div>
                        @endforeach
                    </td>
                </tr>
                @endif
            @empty
                <tr>
                    <td colspan="{{ count($displayShortCols) + 1 }}" style="text-align: center;">Data tidak ditemukan</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Laporan ini di-generate secara otomatis oleh sistem.</p>
    </div>
</body>
</html>
