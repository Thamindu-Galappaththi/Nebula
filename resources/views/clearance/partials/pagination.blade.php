@if($paginator->total() > 0)
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3 clearance-pagination">
        <small class="text-muted">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </small>
        @if($paginator->hasPages())
            <div class="clearance-pagination-nav">
                {{ $paginator->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
    @once
        <style>
            .clearance-pagination .pagination { margin-bottom: 0; flex-wrap: wrap; }
            .clearance-pagination-nav .pagination { justify-content: flex-end; }
        </style>
    @endonce
@endif

