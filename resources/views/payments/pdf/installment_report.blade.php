<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Installment Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .title { font-size: 20px; font-weight: bold; margin-bottom: 5px; }
        .subtitle { font-size: 14px; color: #666; }
        .summary-box { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .summary-box th, .summary-box td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        .summary-box th { background-color: #f5f5f5; font-weight: bold; width: 30%; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.data-table th, table.data-table td { border: 1px solid #ccc; padding: 6px; text-align: left; font-size: 11px; }
        table.data-table th { background-color: #f8f9fa; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row td { font-weight: bold; background-color: #f8f9fa; }
        .badge { padding: 3px 6px; border-radius: 3px; font-size: 10px; color: #fff; }
        .bg-success { background-color: #28a745; }
        .bg-danger { background-color: #dc3545; }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">Installment &amp; Transaction Report</div>
        <div class="subtitle">Generated on {{ date('Y-m-d H:i:s') }}</div>
    </div>

    <table class="summary-box">
        <tr>
            <th>Filter Scope</th>
            <td>
                <strong>Location:</strong> {{ $filters['location'] }} <br>
                <strong>Course:</strong> {{ $filters['course'] }} <br>
                <strong>Intake:</strong> {{ $filters['intake'] }} <br>
                <strong>Payment Type:</strong> {{ $filters['payment_type'] }} <br>
                <strong>Installment No:</strong> {{ $filters['installment_no'] }} <br>
                <strong>Status Filter:</strong> {{ $filters['status'] }}
            </td>
        </tr>
        <tr>
            <th>Summary</th>
            <td>
                <strong>Total Paid:</strong> LKR {{ number_format((float) $paidTotal, 2) }} <br>
                <strong>Total Pending:</strong> LKR {{ number_format((float) $pendingTotal, 2) }} <br>
                <strong>Grand Total:</strong> LKR {{ number_format((float) $grandTotal, 2) }} <br>
                <strong>Total Records:</strong> {{ number_format((int) ($totalRecords ?? count($transactions))) }}
                @if(!empty($isTruncated))
                    <br><em>Showing {{ count($transactions) }} of {{ number_format((int) $totalRecords) }} matching rows. Narrow the filters to export the rest.</em>
                @endif
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Installment</th>
                <th>Type</th>
                <th>Status</th>
                <th>Date</th>
                <th class="text-right">Amount (LKR)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $tx)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $tx->student_id ?? 'N/A' }}</td>
                    <td>{{ $tx->student_name ?? 'Unknown' }}</td>
                    <td>No. {{ $tx->installment_number ?? '-' }}</td>
                    <td>{{ $tx->payment_type ?? 'N/A' }}</td>
                    <td class="text-center">
                        @if(($tx->status ?? '') === 'paid')
                            <span class="badge bg-success">PAID</span>
                        @else
                            <span class="badge bg-danger">PENDING</span>
                        @endif
                    </td>
                    <td>{{ $tx->date ?? '-' }}</td>
                    <td class="text-right">{{ number_format((float) ($tx->amount ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No transactions found for the selected criteria.</td>
                </tr>
            @endforelse
            @if(count($transactions) > 0)
                <tr class="total-row">
                    <td colspan="7" class="text-right">Total:</td>
                    <td class="text-right">{{ number_format((float) collect($transactions)->sum('amount'), 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>
