<div class="doc-pagination">
    @if($paginator->total() > 0)
        <small class="text-muted">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </small>
        @if($paginator->hasPages())
            <nav class="doc-pagination-nav" aria-label="Document collection pagination">
                {{ $paginator->onEachSide(1)->links('pagination::bootstrap-5') }}
            </nav>
        @endif
    @else
        <small class="text-muted">Showing 0 of 0</small>
    @endif
</div>
