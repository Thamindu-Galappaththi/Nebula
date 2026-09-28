<div class="text-start">
    <h5 class="text-primary mb-3 fw-bold">{{ $badge->badge_title }}</h5>
    <div class="table-responsive">
        <table class="table table-bordered mb-0">
            <tr><th>ID</th><td>{{ $badge->id }}</td></tr>
            <tr><th>Student ID</th><td>{{ $badge->student_id }}</td></tr>
            <tr><th>Course</th><td>{{ $badge->course?->course_name ?? '-' }}</td></tr>
            <tr><th>Intake</th><td>{{ $badge->intake?->batch ?? '-' }}</td></tr>
            <tr><th>Verification Code</th><td><code>{{ $badge->verification_code }}</code></td></tr>
            <tr><th>Issued Date</th><td>{{ $badge->issued_date ? \Carbon\Carbon::parse($badge->issued_date)->format('d M Y') : '-' }}</td></tr>
            <tr><th>Status</th><td><span class="badge bg-success">{{ ucfirst((string) $badge->status) }}</span></td></tr>
        </table>
    </div>

    @if($badge->badge_image_path)
        <div class="text-center mt-3">
            <img src="{{ asset('storage/' . $badge->badge_image_path) }}" alt="Badge Image" class="img-fluid rounded shadow" style="max-height:300px;">
        </div>
    @endif
</div>
