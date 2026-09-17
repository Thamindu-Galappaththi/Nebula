@extends('inc.app')

@section('title', 'NEBULA | Audit Log')

@section('content')
<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
<style nonce="{{ $cspNonce }}">
    .audit-log-page [class*="col-"] { min-width: 0; }
    .audit-log-page .form-select,
    .audit-log-page .form-control,
    .audit-log-page .nebula-select {
        max-width: 100%;
    }
    .audit-table-scroll {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #auditLogTable {
        width: max-content;
        min-width: 100%;
        margin-bottom: 0;
        table-layout: auto;
    }
    #auditLogTable th,
    #auditLogTable td {
        vertical-align: top;
        padding: 0.75rem 0.9rem;
        overflow-wrap: break-word;
        word-break: normal;
        hyphens: none;
    }
    #auditLogTable .col-select {
        min-width: 2.5rem;
        width: 2.5rem;
        white-space: nowrap;
    }
    #auditLogTable .col-time {
        min-width: 10.5rem;
        white-space: nowrap;
    }
    #auditLogTable .col-actions {
        min-width: 4.5rem;
        white-space: nowrap;
    }
    #auditLogTable .col-staff {
        min-width: 13rem;
        max-width: 16rem;
        white-space: normal;
    }
    #auditLogTable .col-action {
        min-width: 7.25rem;
        white-space: nowrap;
    }
    #auditLogTable .col-summary {
        min-width: 22rem;
        max-width: 34rem;
        white-space: normal;
    }
    #auditLogTable .col-item {
        min-width: 11rem;
        max-width: 16rem;
        white-space: normal;
    }
    #auditLogTable .col-page {
        min-width: 14rem;
        max-width: 18rem;
        white-space: normal;
    }
    #auditLogTable .col-ip {
        min-width: 11rem;
        max-width: 15rem;
        white-space: normal;
    }
    .audit-path-text,
    .audit-route-text {
        overflow-wrap: anywhere;
        word-break: normal;
    }
    .audit-pagination-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    .audit-pagination-bar .pagination {
        margin-bottom: 0;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .audit-change-list {
        margin: 0.35rem 0 0;
        padding-left: 1.1rem;
        font-size: 0.8125rem;
        color: #5c6370;
    }
    @media (max-width: 1199.98px) {
        .audit-table-scroll { overflow: visible; }
        #auditLogTable {
            width: 100%;
            min-width: 0;
        }
        #auditLogTable thead { display: none; }
        #auditLogTable tbody tr {
            display: block;
            border: 1px solid #dee2e6;
            border-radius: 0.75rem;
            margin-bottom: 0.85rem;
            padding: 0.85rem 1rem;
            background: #fff;
        }
        #auditLogTable tbody td,
        #auditLogTable .col-time,
        #auditLogTable .col-staff,
        #auditLogTable .col-action,
        #auditLogTable .col-summary,
        #auditLogTable .col-item,
        #auditLogTable .col-page,
        #auditLogTable .col-ip,
        #auditLogTable .col-select,
        #auditLogTable .col-actions {
            display: grid;
            grid-template-columns: minmax(6.5rem, 8rem) minmax(0, 1fr);
            align-items: start;
            gap: 0.5rem 0.85rem;
            min-width: 0;
            max-width: none;
            width: 100%;
            border: 0;
            border-bottom: 1px solid #f1f3f5;
            padding: 0.5rem 0;
            white-space: normal;
        }
        #auditLogTable tbody td:last-child { border-bottom: 0; }
        #auditLogTable tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #6c757d;
        }
        #auditLogTable tbody td.col-select,
        #auditLogTable tbody td.col-actions {
            grid-template-columns: 1fr;
            justify-items: end;
        }
        #auditLogTable tbody td.col-select::before,
        #auditLogTable tbody td.col-actions::before,
        #auditLogTable tbody td[data-label=""]::before { display: none; }
        #auditLogTable .empty-row td {
            display: block;
            text-align: center;
            border: 0;
        }
        #auditLogTable .empty-row td::before { display: none; }
        .audit-pagination-bar {
            flex-direction: column;
            align-items: stretch;
        }
        .audit-pagination-bar .pagination { justify-content: center; }
    }
    @media (max-width: 575.98px) {
        #auditLogTable tbody td,
        #auditLogTable .col-time,
        #auditLogTable .col-staff,
        #auditLogTable .col-action,
        #auditLogTable .col-summary,
        #auditLogTable .col-item,
        #auditLogTable .col-page,
        #auditLogTable .col-ip,
        #auditLogTable .col-select,
        #auditLogTable .col-actions {
            grid-template-columns: 1fr;
            gap: 0.2rem;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3 audit-log-page">
    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h2 class="mb-1">Audit Log</h2>
                    <p class="text-muted mb-0">Staff create, update, delete, and login activity. Times are Asia/Colombo.</p>
                    @if(!config('audit.enabled', true))
                        <p class="text-warning small mb-0 mt-1">Recording is paused. Set AUDIT_LOGGING=true in .env to store new entries.</p>
                    @endif
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" id="bulkDeleteAuditBtn" hidden>
                    <i class="ti ti-trash"></i> Delete Selected
                </button>
            </div>

            <form method="GET" action="{{ route('audit.log') }}" class="row g-2 g-md-3 align-items-end mb-3" id="auditFilterForm">
                <div class="col-12 col-lg-3">
                    <label class="form-label small text-muted" for="auditSearch">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="auditSearch" name="search" placeholder="Staff, item, path, IP..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small text-muted" for="auditAction">Action</label>
                    <select class="form-select" id="auditAction" name="action">
                        <option value="">All actions</option>
                        @foreach(['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted', 'login' => 'Login', 'logout' => 'Logout', 'login_failed' => 'Failed login', 'submitted' => 'Submitted'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['action'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small text-muted" for="auditRole">Role</label>
                    <select class="form-select" id="auditRole" name="user_role">
                        <option value="">All roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}" @selected(($filters['user_role'] ?? '') === $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted" for="auditDateFrom">From</label>
                    <input type="date" class="form-control" id="auditDateFrom" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted" for="auditDateTo">To</label>
                    <input type="date" class="form-control" id="auditDateTo" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-1">
                    <label class="form-label small text-muted" for="auditPerPage">Per page</label>
                    <select class="form-select" id="auditPerPage" name="per_page">
                        <option value="10" @selected((int) $perPage === 10)>10</option>
                        <option value="25" @selected((int) $perPage === 25)>25</option>
                        <option value="50" @selected((int) $perPage === 50)>50</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2 d-grid gap-2">
                    <button class="btn btn-primary" type="submit" id="auditFilterBtn">Filter</button>
                    <button class="btn btn-outline-secondary" type="button" id="clearAuditFiltersBtn">Clear</button>
                </div>
            </form>

            <div class="audit-table-scroll">
                <table class="table table-hover align-middle" id="auditLogTable">
                    <thead>
                        <tr>
                            <th class="col-select">
                                <input type="checkbox" id="selectAllAuditLogsHeader" class="form-check-input" aria-label="Select all audit entries">
                            </th>
                            <th class="col-time">Time</th>
                            <th class="col-staff">Staff</th>
                            <th class="col-action">Action</th>
                            <th class="col-summary">Summary</th>
                            <th class="col-item">Item</th>
                            <th class="col-page">Page</th>
                            <th class="col-ip">IP / Location</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="audit-table-body">
                        @include('audit.partials.rows')
                    </tbody>
                </table>
            </div>
            <div id="auditPagination">
                @include('audit.partials.pagination')
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.js"></script>
<script nonce="{{ $cspNonce }}">
$(function () {
    const csrfToken = '{{ csrf_token() }}';
    const listUrl = '{{ route('audit.log') }}';
    const bulkUrl = '{{ route('audit.bulkDestroy') }}';
    const destroyUrlTemplate = '{{ url('/audit-log') }}';

    function showAlert(title, text, icon) {
        if (window.Swal) {
            return Swal.fire({ title: title, text: text, icon: icon, confirmButtonText: 'OK' });
        }
        window.alert(text);
        return Promise.resolve();
    }

    function confirmDelete(title, text) {
        if (!window.Swal) {
            return Promise.resolve(window.confirm(text));
        }
        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: true
        }).then(function (result) { return result.isConfirmed; });
    }

    function validationMessage(xhr) {
        if (xhr.responseJSON && xhr.responseJSON.errors) {
            return Object.values(xhr.responseJSON.errors).flat().join(' ');
        }
        return (xhr.responseJSON && xhr.responseJSON.message) || 'An error occurred.';
    }

    function selectedIds() {
        const ids = [];
        $('.audit-checkbox:checked').each(function () {
            ids.push($(this).data('audit-id'));
        });
        return ids;
    }

    function updateBulkDeleteButton() {
        const count = selectedIds().length;
        if (count > 0) {
            $('#bulkDeleteAuditBtn').prop('hidden', false).html('<i class="ti ti-trash"></i> Delete Selected (' + count + ')');
        } else {
            $('#bulkDeleteAuditBtn').prop('hidden', true).html('<i class="ti ti-trash"></i> Delete Selected');
        }
    }

    function syncNebulaSelect(select) {
        if (!select) return;
        const selected = select.options[select.selectedIndex];
        const wrap = select.closest('.nebula-select');
        const toggle = wrap ? wrap.querySelector('.nebula-select-toggle') : null;
        if (toggle) {
            toggle.textContent = selected ? selected.text : '';
            toggle.title = toggle.textContent;
        }
    }

    function resetFilterSelect(select, value) {
        if (!select) return;
        if (value === undefined || value === '') {
            select.value = '';
            select.selectedIndex = 0;
        } else {
            select.value = String(value);
        }
        syncNebulaSelect(select);
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function currentListUrl() {
        const params = new URLSearchParams($('#auditFilterForm').serialize());
        ['search', 'action', 'user_role', 'date_from', 'date_to', 'per_page'].forEach(function (key) {
            const value = $.trim(params.get(key) || '');
            if (value) {
                params.set(key, value);
            } else {
                params.delete(key);
            }
        });
        params.delete('page');
        const query = params.toString();
        return query ? (listUrl + '?' + query) : listUrl;
    }

    function loadLogs(url, pushUrl) {
        const $body = $('#audit-table-body');
        const $pager = $('#auditPagination');
        $body.addClass('opacity-50');
        $pager.addClass('opacity-50');
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (res && res.html) $body.html(res.html);
                if (res && res.pagination) $pager.html(res.pagination);
                $('#selectAllAuditLogsHeader').prop('checked', false);
                updateBulkDeleteButton();
                if (pushUrl) history.pushState({ auditAjax: true }, '', url);
            },
            error: function () {
                if (window.Swal) {
                    Swal.fire('Error', 'Failed to load audit log.', 'error');
                }
            },
            complete: function () {
                $body.removeClass('opacity-50');
                $pager.removeClass('opacity-50');
            }
        });
    }

    $('#auditFilterForm').on('submit', function (e) {
        e.preventDefault();
        loadLogs(currentListUrl(), true);
    });

    $('#clearAuditFiltersBtn').on('click', function () {
        $('#auditSearch').val('');
        $('#auditDateFrom').val('');
        $('#auditDateTo').val('');
        resetFilterSelect(document.getElementById('auditAction'), '');
        resetFilterSelect(document.getElementById('auditRole'), '');
        resetFilterSelect(document.getElementById('auditPerPage'), '10');
        loadLogs(listUrl, true);
    });

    $(document).on('click', '#auditPagination .pagination a.page-link', function (e) {
        const href = $(this).attr('href');
        if (!href || href === '#' || $(this).closest('.page-item').hasClass('disabled') || $(this).closest('.page-item').hasClass('active')) {
            e.preventDefault();
            return;
        }
        e.preventDefault();
        loadLogs(href, true);
    });

    window.addEventListener('popstate', function () {
        if (!$('.audit-log-page').length) return;
        loadLogs(window.location.href, false);
    });

    $(document).on('change', '.audit-checkbox', function () {
        const total = $('.audit-checkbox').length;
        const checked = $('.audit-checkbox:checked').length;
        $('#selectAllAuditLogsHeader').prop('checked', total > 0 && total === checked);
        updateBulkDeleteButton();
    });

    $(document).on('change', '#selectAllAuditLogsHeader', function () {
        $('.audit-checkbox').prop('checked', this.checked);
        updateBulkDeleteButton();
    });

    $(document).on('click', '.delete-audit-btn', function () {
        const id = $(this).data('audit-id');
        confirmDelete('Delete audit entry?', 'Delete this audit entry? This cannot be undone.').then(function (ok) {
            if (!ok) return;
            $.ajax({
                url: destroyUrlTemplate + '/' + id,
                type: 'DELETE',
                data: { _token: csrfToken },
                success: function (response) {
                    if (response.success) {
                        showAlert('Deleted', response.message, 'success').then(function () {
                            loadLogs(window.location.href, false);
                        });
                    } else {
                        showAlert('Error', response.message || 'Could not delete the entry.', 'error');
                    }
                },
                error: function (xhr) {
                    showAlert('Error', validationMessage(xhr), 'error');
                }
            });
        });
    });

    $('#bulkDeleteAuditBtn').on('click', function () {
        const ids = selectedIds();
        if (!ids.length) return;
        confirmDelete('Delete selected entries?', 'Delete ' + ids.length + ' audit ' + (ids.length === 1 ? 'entry' : 'entries') + '? This cannot be undone.').then(function (ok) {
            if (!ok) return;
            $.ajax({
                url: bulkUrl,
                type: 'POST',
                data: { _token: csrfToken, ids: ids },
                success: function (response) {
                    showAlert(response.success ? 'Deleted' : 'Error', response.message, response.success ? 'success' : 'error').then(function () {
                        loadLogs(window.location.href, false);
                    });
                },
                error: function (xhr) {
                    showAlert('Error', validationMessage(xhr), 'error');
                }
            });
        });
    });
});
</script>
@endpush
