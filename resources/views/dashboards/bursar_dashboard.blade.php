@extends('inc.app')

@section('title', 'NEBULA | Bursar Dashboard')

@section('content')

<style nonce="{{ $cspNonce }}">
    .stat-card {
        transition: 0.2s;
        border-left: 4px solid transparent;
        background: white;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.1);
    }

    .pending { border-left-color: #f59e0b; }
    .approved { border-left-color: #198754; }
    .rejected { border-left-color: #dc3545; }

    .badge-status {
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
    }
    .badge-pending { background: #f59e0b; color: #1f2937; }
    .badge-approved { background: #198754; color: white; }
    .badge-rejected { background: #dc3545; color: white; }
</style>

<div class="container-fluid">
    <div class="card shadow-sm p-3 mb-4 bg-white">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h3 class="fw-bold mb-1">Bursar Dashboard</h3>
                <small class="text-muted">Monitoring financial clearance for non-tuition and bursary requirements</small>
            </div>
            <a href="{{ route('payment.clearance') }}" class="btn btn-primary btn-sm">
                Open payment clearance
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card pending p-4 shadow-sm">
                <h6 class="text-muted">Pending Reviews</h6>
                <h2 class="text-warning fw-bold">{{ $pendingCount }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card approved p-4 shadow-sm">
                <h6 class="text-muted">Approved This Month</h6>
                <h2 class="text-success fw-bold">{{ $approvedCount }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card rejected p-4 shadow-sm">
                <h6 class="text-muted">Rejected This Month</h6>
                <h2 class="text-danger fw-bold">{{ $rejectedCount }}</h2>
            </div>
        </div>
    </div>

    <div class="card shadow-sm p-4 mb-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-semibold m-0">Financial Clearance Pending</h5>
            <span class="badge bg-warning text-dark">{{ $pendingCount }} pending</span>
        </div>

        <div id="bursarPendingList">
            @include('dashboards.partials.bursar_pending_list', ['pendingList' => $pendingList])
        </div>
    </div>

    <div class="card shadow-sm p-4 mb-4 bg-white">
        <h5 class="fw-semibold mb-1">Recent Financial Clearance Updates</h5>
        <p class="text-muted mb-3">Latest approved or rejected requests</p>

        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>ID</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Processed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $req)
                        @php $status = strtolower((string) $req->status); @endphp
                        <tr>
                            <td>{{ $req->student?->name_with_initials ?? $req->student?->full_name ?? 'N/A' }}</td>
                            <td><code>{{ $req->student?->student_id ?? $req->student_id ?? '-' }}</code></td>
                            <td>{{ $req->course?->course_name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge-status badge-{{ $status }}">{{ $req->status_text }}</span>
                            </td>
                            <td>{{ $req->processedAtSriLanka()?->format('Y-m-d H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted py-3">No processed financial updates yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
(function () {
    const list = document.getElementById('bursarPendingList');
    if (!list) {
        return;
    }

    function ajaxHeaders() {
        return {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
    }

    function loadPendingPage(url, pushUrl) {
        list.classList.add('opacity-50');

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: ajaxHeaders()
        })
        .then(function (res) {
            if (!res.ok) {
                throw new Error('Failed to load pending requests');
            }
            return res.json();
        })
        .then(function (payload) {
            if (payload && payload.html) {
                list.innerHTML = payload.html;
            }
            if (pushUrl) {
                history.pushState({ bursarPendingAjax: true }, '', url);
            }
        })
        .catch(function () {
            window.alert('Failed to load the next page.');
        })
        .finally(function () {
            list.classList.remove('opacity-50');
        });
    }

    document.addEventListener('click', function (e) {
        const link = e.target.closest('#bursarPendingList a.bursar-pending-page');
        if (!link) {
            return;
        }

        e.preventDefault();
        if (link.classList.contains('disabled') || link.getAttribute('aria-disabled') === 'true') {
            return;
        }

        const href = link.getAttribute('href');
        if (!href || href === '#') {
            return;
        }

        loadPendingPage(href, true);
    });

    window.addEventListener('popstate', function () {
        if (!document.getElementById('bursarPendingList')) {
            return;
        }
        loadPendingPage(window.location.href, false);
    });
})();
</script>
@endsection
