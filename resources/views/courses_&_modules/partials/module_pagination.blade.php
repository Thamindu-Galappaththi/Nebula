@if($modules->total() > 0)
    <div class="module-pagination-bar">
        <small class="text-muted" id="resultsInfo">
            Showing {{ $modules->firstItem() }}–{{ $modules->lastItem() }} of {{ $modules->total() }} modules
        </small>
        @if($modules->hasPages())
            <nav class="module-pagination-nav" aria-label="Module pagination">
                {{ $modules->onEachSide(1)->links('pagination::bootstrap-5') }}
            </nav>
        @endif
    </div>
@else
    <div class="module-pagination-bar">
        <small class="text-muted" id="resultsInfo">Showing 0 modules</small>
    </div>
@endif
