<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>All Students View</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; line-height: 1.4; color: #333; margin: 0; padding: 16px 16px 24px; }
        h2 { font-size: 18px; font-weight: bold; margin: 0 0 8px; text-align: center; }
        hr { border: none; border-top: 1px solid #ddd; margin: 8px 0 12px; }
        .filter-details { margin-bottom: 12px; font-size: 10px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 8px; table-layout: auto; }
        th, td { border: 1px solid #ddd; padding: 5px 6px; vertical-align: middle; word-wrap: break-word; overflow-wrap: anywhere; }
        th { background: #f8f9fa; font-weight: bold; text-align: center; }
        td.center { text-align: center; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        .total { text-align: right; font-weight: bold; margin-top: 8px; }
    </style>
</head>
<body>
    <h2>All Students View</h2>
    <hr>
    <div class="filter-details">
        <strong>Student ID / NIC:</strong> {{ $meta['studentId'] ?? 'All' }}<br>
        <strong>Course:</strong> {{ $meta['courseText'] ?? 'All Courses' }}<br>
        <strong>Intake:</strong> {{ $meta['intakeText'] ?? 'All Intakes' }}<br>
        @if(!empty($meta['specializationText']))
            <strong>Specialization:</strong> {{ $meta['specializationText'] }}<br>
        @endif
        <strong>Status:</strong> {{ $meta['statusText'] ?? 'All' }}
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                @foreach($columns as $column)
                    <th>{{ $columnLabels[$column] ?? ucfirst($column) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($students as $i => $st)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    @foreach($columns as $column)
                        @php
                            $value = match ($column) {
                                'student' => $st['full_name'] ?? '-',
                                'nic' => $st['id_value'] ?? '-',
                                'course' => $st['course'] ?? '-',
                                'intake' => $st['intake'] ?? '-',
                                'specialization' => $st['specialization'] ?? '-',
                                'location' => $st['location'] ?? '-',
                                'status' => ucfirst((string) ($st['academic_status'] ?? '-')),
                                default => '-',
                            };
                            $center = !in_array($column, ['student', 'course', 'specialization'], true);
                        @endphp
                        <td @if($center) class="center" @endif>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="center">No students found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="total">Total Students: {{ $total_count ?? count($students ?? []) }}</div>
</body>
</html>
