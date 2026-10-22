<div class="doc-list" data-current-status="{{ $status }}">
    <div class="doc-table-scroll">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Sent by</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Retention</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $item)
                    <tr>
                        <td>
                            <div class="doc-student-name">{{ $item->student->full_name ?? 'Unknown student' }}</div>
                            <div class="doc-student-meta">ID {{ $item->student_id }}@if($item->student?->id_value) · {{ $item->student->id_value }}@endif</div>
                        </td>
                        <td>{{ $item->creator->name ?? 'Staff' }}</td>
                        <td>
                            <span @class([
                                'doc-status',
                                'is-pending' => $item->status === 'pending',
                                'is-submitted' => $item->status === 'submitted',
                                'is-expired' => $item->status === 'expired',
                                'is-purged' => $item->status === 'purged',
                            ])>{{ $item->status === 'pending' ? 'Awaiting upload' : ucfirst($item->status) }}</span>
                            @if($item->reviewed_at)
                                <div class="doc-student-meta">Reviewed</div>
                            @endif
                        </td>
                        <td>{{ $item->submitted_at ? $item->submitted_at->timezone('Asia/Colombo')->format('Y-m-d H:i') : '—' }}</td>
                        <td>
                            @if($item->documentsAreAvailable())
                                {{ $item->retention_expires_at->timezone('Asia/Colombo')->format('Y-m-d H:i') }}
                            @elseif($item->status === 'purged')
                                Deleted
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <a class="btn btn-primary btn-sm doc-open-btn" href="{{ route('student.documents.show', $item) }}">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="doc-empty">No records in this list yet.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('student_management.partials.document_collection_pagination', ['paginator' => $requests])
</div>
