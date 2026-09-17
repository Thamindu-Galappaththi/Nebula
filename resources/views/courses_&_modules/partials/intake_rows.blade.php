@if($intakes->total() > 0)
    @foreach($intakes as $intake)
        @php
            $status = $intake->isPast() ? 'finished' : ($intake->isCurrent() ? 'ongoing' : 'upcoming');
            $startDate = $intake->start_date ? $intake->start_date->format('Y-m-d') : '';
            $endDate = $intake->end_date ? $intake->end_date->format('Y-m-d') : '';
            $enrollmentEnd = $intake->enrollment_end_date ? $intake->enrollment_end_date->format('Y-m-d') : '-';
            $typeLabel = $intake->intake_type === 'Fulltime' ? 'Full Time' : ($intake->intake_type === 'Parttime' ? 'Part Time' : $intake->intake_type);
        @endphp
        <tr id="intake-row-{{ $intake->intake_id }}" data-intake-id="{{ $intake->intake_id }}">
            <td class="intake-select-cell" data-label="">
                <input type="checkbox" class="form-check-input intake-checkbox" data-intake-id="{{ $intake->intake_id }}" aria-label="Select {{ $intake->batch }}">
            </td>
            <td class="intake-course-name" data-label="Course Name">{{ $intake->course_name }}</td>
            <td class="intake-batch" data-label="Batch">{{ $intake->batch }}</td>
            <td class="intake-location" data-label="Location">{{ $intake->location }}</td>
            <td class="intake-mode" data-label="Mode">{{ $intake->intake_mode }}</td>
            <td class="intake-type" data-label="Type">{{ $typeLabel }}</td>
            <td class="intake-start-date" data-label="Start Date">{{ $startDate }}</td>
            <td class="intake-end-date" data-label="End Date">{{ $endDate }}</td>
            <td class="intake-enrollment-end" data-label="Enrollment End">{{ $enrollmentEnd }}</td>
            <td class="intake-capacity" data-label="Capacity">{{ $intake->registrations_count ?? 0 }} / {{ $intake->batch_size }}</td>
            <td class="intake-status" data-label="Status" data-status="{{ $status }}">
                @if($status === 'finished')
                    <span class="badge bg-danger">Finished</span>
                @elseif($status === 'ongoing')
                    <span class="badge bg-success">Ongoing</span>
                @else
                    <span class="badge bg-warning">Upcoming</span>
                @endif
            </td>
            <td class="intake-actions-cell" data-label="Actions">
                <div class="btn-group btn-group-sm intake-actions" role="group">
                    <button type="button" class="btn btn-outline-primary edit-intake-btn" data-intake-id="{{ $intake->intake_id }}" title="Edit">
                        <i class="ti ti-edit"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger delete-intake-btn" data-intake-id="{{ $intake->intake_id }}" title="Delete">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    @endforeach
@else
    <tr class="empty-row">
        <td colspan="12" class="text-center py-4">No intakes found.</td>
    </tr>
@endif
