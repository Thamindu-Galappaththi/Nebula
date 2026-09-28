@if($logs->total() > 0)
    <div class="audit-pagination-bar">
        <small class="text-muted" id="auditResultsInfo">
            Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}
        </small>
        @if($logs->hasPages())
            <nav class="audit-pagination-nav" aria-label="Audit log pagination">
                {{ $logs->onEachSide(1)->links('pagination::bootstrap-5') }}
            </nav>
        @endif
    </div>
@else
    <div class="audit-pagination-bar">
        <small class="text-muted" id="auditResultsInfo">Showing 0 of 0</small>
    </div>
@endif
