<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Plans</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        .meta { margin-bottom: 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .text-end { text-align: right; }
        .note { margin-top: 10px; color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Payment Plans</h1>
    <div class="meta">Generated: {{ $generatedAt }}</div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Location</th>
                <th>Course</th>
                <th>Intake</th>
                <th class="text-end">Reg. Fee</th>
                <th class="text-end">Local Fee</th>
                <th class="text-end">Franchise</th>
                <th>Discount</th>
                <th>Installments</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['id'] }}</td>
                    <td>{{ $row['location'] }}</td>
                    <td>{{ $row['course'] }}</td>
                    <td>{{ $row['intake'] }}</td>
                    <td class="text-end">{{ $row['registration_fee'] }}</td>
                    <td class="text-end">{{ $row['local_fee'] }}</td>
                    <td class="text-end">{{ $row['international_fee'] }} {{ $row['currency'] }}</td>
                    <td>{{ $row['discount'] }}</td>
                    <td>{{ $row['installments'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">No payment plans found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if(($totalRecords ?? 0) > ($maxRows ?? 0))
        <p class="note">Showing {{ count($rows) }} of {{ $totalRecords }} matching plans.</p>
    @endif
</body>
</html>
