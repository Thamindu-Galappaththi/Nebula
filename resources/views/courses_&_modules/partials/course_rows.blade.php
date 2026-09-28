@if($courses->total() > 0)
    @foreach($courses as $course)
        <tr id="course-row-{{ $course->course_id }}" data-course-id="{{ $course->course_id }}">
            <td class="course-select-cell" data-label="">
                <input type="checkbox" class="form-check-input course-checkbox" data-course-id="{{ $course->course_id }}" aria-label="Select {{ $course->course_name }}">
            </td>
            <td class="course-name" data-label="Course Name">{{ $course->course_name }}</td>
            <td class="course-type" data-label="Course Type" data-type="{{ $course->course_type }}">
                @if($course->course_type === 'degree')
                    <span class="badge bg-primary">Degree Program</span>
                @elseif($course->course_type === 'diploma')
                    <span class="badge bg-info">Diploma Program</span>
                @elseif($course->course_type === 'certificate')
                    <span class="badge bg-success">Certificate Program</span>
                @else
                    N/A
                @endif
            </td>
            <td class="course-location" data-label="Location">{{ $course->location }}</td>
            <td class="course-duration" data-label="Duration">{{ $course->duration_formatted }}</td>
            <td class="course-medium" data-label="Medium">{{ $course->course_medium }}</td>
            <td class="course-actions-cell" data-label="Actions">
                <div class="btn-group btn-group-sm course-actions" role="group">
                    <button type="button" class="btn btn-outline-primary edit-course-btn" data-course-id="{{ $course->course_id }}" title="Edit">
                        <i class="ti ti-edit"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger delete-course-btn" data-course-id="{{ $course->course_id }}" title="Delete">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    @endforeach
@else
    <tr class="empty-row">
        <td colspan="7" class="text-center py-4">No courses found.</td>
    </tr>
@endif
