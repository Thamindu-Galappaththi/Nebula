@if($courses->total() > 0)
    <div class="course-pagination-bar">
        <small class="text-muted" id="courseResultsInfo">
            Showing {{ $courses->firstItem() }}–{{ $courses->lastItem() }} of {{ $courses->total() }} courses
        </small>
        @if($courses->hasPages())
            <nav class="course-pagination-nav" aria-label="Course pagination">
                {{ $courses->onEachSide(1)->links('pagination::bootstrap-5') }}
            </nav>
        @endif
    </div>
@else
    <div class="course-pagination-bar">
        <small class="text-muted" id="courseResultsInfo">Showing 0 courses</small>
    </div>
@endif
