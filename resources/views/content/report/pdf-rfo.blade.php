<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reason of Outage - {{ $ticket->ticket_no ?? 'Report' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            font-size: 12px;
            color: #333;
        }
        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
            color: #333;
        }
        .header-line {
            border-bottom: 2px solid #000;
            margin-bottom: 20px;
        }
        .date {
            text-align: right;
            margin-bottom: 20px;
        }
        .greeting {
            margin-bottom: 15px;
        }
        .intro {
            margin-bottom: 15px;
            text-align: justify;
        }
        
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #000;
        }
        td {
            padding: 5px 8px;
            vertical-align: top;
        }
        .header-cell {
            background-color: #5D478B; /* Dark purple */
            color: white;
            font-weight: bold;
            width: 30%;
        }
        .header-cell-light {
            background-color: #E6E6FA; /* Light purple */
            font-weight: bold;
            width: 30%;
        }
        
        .footer-line {
            border-top: 3px solid #000;
            margin-top: 20px;
            margin-bottom: 15px;
        }
        .closing {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    
    <div style="text-align: center; margin-bottom: 20px;">
        @if(!empty($logoBase64))
            <img src="{{ $logoBase64 }}" style="max-height: 120px;">
        @else
            <h1>LOGO</h1>
        @endif
        <div class="header-title">Reason For Outage Report</div>
        <div class="header-line"></div>
    </div>

    <div class="date">
        Date : {{ now()->translatedFormat('d F Y') }}
    </div>

    <div class="greeting">
        Dear Customer,
    </div>

    <div class="intro">
        Thank you for using our services. We would like to inform you that our service recently experienced a disruption. The details are as follows:
    </div>

    <!-- Customer Info Table -->
    <table>
        <tr>
            <td class="header-cell">Customer Name</td>
            <td>: {{ $ticket->customer_nama }}</td>
        </tr>
        <tr>
            <td class="header-cell">Customer ID</td>
            <td>: {{ $ticket->customer_id }}</td>
        </tr>
        <tr>
            <td class="header-cell">Service Type</td>
            <td>: {{ $ticket->jenis_layanan }}</td>
        </tr>
    </table>

    <div style="margin-bottom: 10px;">
        The details of the outage are as follows:
    </div>

    <!-- Ticket Detail Table -->
    <table>
        <tr>
            <td style="font-weight: bold; border: 2px solid #000;">Trouble Ticket No</td>
            <td style="font-weight: bold; border: 2px solid #000;">: {{ $ticket->ticket_no }}</td>
        </tr>
        <tr>
            <td class="header-cell-light">Problem Type</td>
            <td class="header-cell-light">: {{ strtoupper($ticket->jenis_permasalahan) }}</td>
        </tr>
        <tr>
            <td class="header-cell-light">Log Down</td>
            <td class="header-cell-light">: {{ $ticket->downtime_start }}</td>
        </tr>
        <tr>
            <td class="header-cell-light">Log Up</td>
            <td class="header-cell-light">: {{ $ticket->downtime_end }}</td>
        </tr>
        <tr>
            <td class="header-cell-light">Root Cause</td>
            <td class="header-cell-light">: {{ $ticket->penyebab }}</td>
        </tr>
        <tr>
            <td class="header-cell-light">Action Taken</td>
            <td class="header-cell-light">: {{ $ticket->tindakan }}</td>
        </tr>
        <tr>
            <td class="header-cell-light">Downtime Duration</td>
            <td class="header-cell-light">: {{ $ticket->total_downtime }}</td>
        </tr>
        
        @if($ticket->pending_duration !== '-')
        <tr>
            <td class="header-cell-light">Pending Date</td>
            <td class="header-cell-light">: {{ $ticket->pending_date }}</td>
        </tr>
        <tr>
            <td class="header-cell-light">Pending Duration</td>
            <td class="header-cell-light">: {{ $ticket->pending_duration }}</td>
        </tr>
        <tr>
            <td class="header-cell-light">Pending Reason</td>
            <td class="header-cell-light">: {{ $ticket->pending_reason }}</td>
        </tr>
        @endif
        
    </table>

    <div class="footer-line"></div>

    <div class="instruction">
        We apologize for the inconvenience caused by this disruption.
    </div>

    <div class="closing">
        Thank you for your understanding and cooperation.
    </div>

</body>
</html>
