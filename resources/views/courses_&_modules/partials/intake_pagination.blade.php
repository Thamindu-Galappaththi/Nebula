@if($intakes->total() > 0)
    <div class="intake-pagination-bar">
        <small class="text-muted" id="intakeResultsInfo">
            Showing {{ $intakes->firstItem() }}–{{ $intakes->lastItem() }} of {{ $intakes->total() }} intakes
        </small>
        @if($intakes->hasPages())
            <nav class="intake-pagination-nav" aria-label="Intake pagination">
                {{ $intakes->onEachSide(1)->links('pagination::bootstrap-5') }}
            </nav>
        @endif
    </div>
@else
    <div class="intake-pagination-bar">
        <small class="text-muted" id="intakeResultsInfo">Showing 0 intakes</small>
    </div>
@endif
