<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Registration List</title>
    <style nonce="{{ $cspNonce }}">
        body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
        .container-fluid { width: 100%; }
        .card { border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card-body { padding: 20px; }
        .text-center { text-align: center; }
        .mb-4 { margin-bottom: 1.5rem; }
        .mt-4 { margin-top: 1.5rem; }
        .mt-2 { margin-top: 0.5rem; }
        hr { border: none; border-top: 1px solid #ddd; margin: 1rem 0; }
        h2 { font-size: 24px; font-weight: bold; margin: 0; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        th, td { border: 1px solid #ddd; padding: 10px 12px; }
        th { background: #f8f9fa; font-weight: bold; text-align: center; }
        .fw-bold { font-weight: bold; }
        .filter-details { margin-bottom: 16px; font-size: 14px; }
        .status-chip { font-size: 11px; padding: 2px 8px; border-radius: 10px; border: 1px solid #bbb; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h2 class="text-center mb-4">Student Registration List</h2>
                <hr>

                <div class="filter-details">
                    <strong>Location:</strong> {{ $locationText }}<br>
                    <strong>Course:</strong> {{ $courseText }}<br>
                    <strong>Batch:</strong> {{ $intakeText }}<br>
                    @if(!empty($showSpecializationColumn) && !empty($specializationText) && $specializationText !== 'All')
                        <strong>Specialization:</strong> {{ $specializationText }}<br>
                    @endif
                        <strong>View:</strong>
                    @if(($status ?? 'all') === 'all') All
                    @elseif(($status ?? '') === 'terminated') Not Eligible
                    @else {{ ucfirst($status) }}
                    @endif
                </div>

                <div class="mt-4">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Course Registration ID</th>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                @if(!empty($showSpecializationColumn))
                                    <th>Specialization</th>
                                @endif
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $i => $st)
                                <tr>
                                    <td style="text-align:center;">{{ $i + 1 }}</td>
                                    <td style="text-align:center;">{{ $st->course_registration_id }}</td>
                                    <td style="text-align:center;">{{ $st->student_id }}</td>
                                    <td>{{ $st->name }}</td>
                                    @if(!empty($showSpecializationColumn))
                                        <td style="text-align:center;">{{ $st->specialization }}</td>
                                    @endif
                                    <td style="text-align:center;">
                                        <span class="status-chip">
                                            @if(!empty($st->status_label))
                                                {{ $st->status_label }}
                                            @elseif(($st->status ?? '') === 'terminated')
                                                Not Eligible - Termination
                                            @else
                                                {{ ucfirst($st->status) }}
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ !empty($showSpecializationColumn) ? 6 : 5 }}" style="text-align:center;">No students found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-2" style="text-align:right;">
                        <span class="fw-bold">Total Students: {{ $total_count ?? count($students ?? []) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
