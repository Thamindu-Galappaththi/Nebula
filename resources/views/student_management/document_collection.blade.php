@extends('inc.app')

@section('title', 'NEBULA | Document Collection')

@section('content')
<style nonce="{{ $cspNonce }}">
    .doc-page {
        min-width: 0;
        max-width: 1120px;
        margin: 0 auto;
    }
    .doc-page [class*="col-"] { min-width: 0; }
    .doc-hero,
    .doc-panel {
        background: #fff;
        border: 0;
        border-radius: 20px;
        box-shadow: 0 12px 36px rgba(35, 64, 142, 0.08);
        overflow: hidden;
    }
    .doc-hero {
        padding: 1.5rem 1.4rem 1.35rem;
        background: linear-gradient(135deg, #f4f7ff 0%, #ffffff 58%);
        margin-bottom: 1rem;
    }
    .doc-kicker {
        color: #4c6bd8;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 0.35rem;
    }
    .doc-hero h3 {
        margin: 0 0 0.35rem;
        color: #1b2c5b;
        font-weight: 700;
    }
    .doc-search-row {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        max-width: 34rem;
    }
    .doc-search-row .form-control {
        flex: 1 1 auto;
        min-width: 0;
        max-width: 22rem;
    }
    .doc-search-row .btn {
        flex: 0 0 auto;
        min-width: 7.5rem;
        padding-left: 1.35rem;
        padding-right: 1.35rem;
        white-space: nowrap;
    }
    .doc-share-box {
        border-radius: 12px;
        background: #eef4ff;
        border: 1px solid #d7e3ff;
        padding: 0.85rem 1rem;
    }
    .doc-share-url {
        word-break: break-all;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.85rem;
        color: #23408e;
        background: #fff;
        border-radius: 8px;
        padding: 0.55rem 0.7rem;
    }
    .doc-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .doc-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0;
        margin: 0 0 1rem;
        border-bottom: 1px solid #e4ebf6;
    }
    .doc-tab {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.7rem 0.95rem 0.8rem;
        border: 0;
        border-radius: 0;
        background: transparent;
        color: #64748b;
        font-size: 0.92rem;
        font-weight: 600;
        line-height: 1.2;
        width: auto;
    }
    .doc-tab:hover,
    .doc-tab:focus {
        background: transparent;
        color: #23408e;
    }
    .doc-tab.is-active {
        background: transparent;
        color: #23408e;
    }
    .doc-tab.is-active::after {
        content: '';
        position: absolute;
        left: 0.7rem;
        right: 0.7rem;
        bottom: -1px;
        height: 3px;
        border-radius: 3px 3px 0 0;
        background: #23408e;
    }
    .doc-tab-count {
        min-width: 1.35rem;
        height: 1.2rem;
        padding: 0 0.35rem;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        font-weight: 700;
        background: #eef2f7;
        color: #64748b;
        flex: 0 0 auto;
    }
    .doc-tab.is-active .doc-tab-count {
        background: #e8eefc;
        color: #23408e;
    }
    .doc-table-scroll {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .doc-table-scroll table {
        min-width: 860px;
        width: 100%;
    }
    .doc-list thead th {
        background: #eef3fb;
        color: #23408e;
        font-weight: 600;
        border: 0;
        white-space: nowrap;
        padding: 0.65rem 0.7rem;
        font-size: 0.9rem;
    }
    .doc-list td {
        padding: 0.7rem;
        border-color: #edf1f7;
        vertical-align: middle;
        font-size: 0.93rem;
    }
    .doc-student-name { font-weight: 700; color: #1b2c5b; }
    .doc-student-meta { color: #6b7a99; font-size: 0.84rem; margin-top: 0.15rem; }
    .doc-status {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 0.2rem 0.7rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 700;
        background: #eef2f7;
        color: #44506b;
        white-space: nowrap;
    }
    .doc-status.is-pending { background: #fff4d6; color: #8a6d12; }
    .doc-status.is-submitted { background: #d9f7e6; color: #0f7a45; }
    .doc-status.is-expired { background: #eceff4; color: #5b6475; }
    .doc-status.is-purged { background: #efe7ff; color: #5b3ea6; }
    .doc-open-btn {
        padding: 0.25rem 0.7rem;
        font-size: 0.875rem;
    }
    .doc-empty {
        text-align: center;
        color: #6b7a99;
        padding: 2rem 1rem;
    }
    .doc-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
        padding: 1rem 0.15rem 0.2rem;
    }
    .doc-pagination .pagination {
        margin: 0;
        flex-wrap: wrap;
    }
    @media (max-width: 767.98px) {
        .doc-hero, .doc-panel .card-body { padding: 1rem; }
        .doc-search-row {
            max-width: 100%;
        }
        .doc-search-row .form-control {
            max-width: none;
        }
        .doc-search-row .btn {
            min-width: 6.5rem;
        }
        .doc-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .doc-tab { flex: 0 0 auto; }
        .doc-pagination { justify-content: flex-start; }
        .doc-pagination-nav { width: 100%; }
        .doc-pagination .pagination { justify-content: flex-start; }
    }
</style>

<div class="container-fluid px-2 px-md-3">
    <div class="doc-page">
        <div class="doc-hero">
            <div class="doc-kicker">Student management</div>
            <h3>Document Collection</h3>
            <p class="text-muted mb-4">Create a unique link, share it with the student, then review uploaded files for {{ $retentionDays }} days.</p>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <label for="studentSearch" class="form-label fw-semibold">Find a student</label>
            <div class="doc-search-row">
                <input type="search" id="studentSearch" class="form-control" placeholder="Search by name, NIC, email or student ID" autocomplete="off">
                <button type="button" class="btn btn-primary" id="studentSearchBtn">Search</button>
            </div>
            <div id="studentSearchResults" class="list-group mt-3"></div>

            <div id="generatedLinkBox" class="doc-share-box mt-3 d-none">
                <div class="fw-semibold mb-2" id="generatedStudentName"></div>
                <div class="doc-share-url mb-3" id="generatedLinkUrl"></div>
                <div class="doc-actions">
                    <button type="button" class="btn btn-dark" id="copyLinkBtn">Copy link</button>
                    <a class="btn btn-success" id="whatsappShareBtn" target="_blank" rel="noopener noreferrer">Share on WhatsApp</a>
                    <a class="btn btn-outline-primary" id="emailShareBtn">Share by email</a>
                </div>
                <small class="d-block mt-3 text-muted" id="generatedExpiry"></small>
            </div>
        </div>

        <div class="card doc-panel">
            <div class="card-body">
                <div class="doc-tabs" role="tablist">
                    @foreach($tabs as $key => $label)
                        <button type="button"
                            class="doc-tab {{ $status === $key ? 'is-active' : '' }}"
                            data-status="{{ $key }}"
                            role="tab"
                            aria-selected="{{ $status === $key ? 'true' : 'false' }}">
                            <span>{{ $label }}</span>
                            <span class="doc-tab-count">{{ $counts[$key] ?? 0 }}</span>
                        </button>
                    @endforeach
                </div>

                <div id="docListWrap">
                    @include('student_management.partials.document_collection_list', ['requests' => $requests, 'status' => $status])
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('studentSearch');
    const searchBtn = document.getElementById('studentSearchBtn');
    const results = document.getElementById('studentSearchResults');
    const generatedBox = document.getElementById('generatedLinkBox');
    const listWrap = document.getElementById('docListWrap');
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const indexUrl = @json(route('student.documents.index'));

    function showToast(message, type) {
        const html = '<div class="toast align-items-center text-white bg-' + type + ' border-0" role="alert"><div class="d-flex"><div class="toast-body">' + message + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>';
        document.querySelector('.toast-container').insertAdjacentHTML('beforeend', html);
        const toastEl = document.querySelector('.toast-container .toast:last-child');
        new bootstrap.Toast(toastEl, { delay: 3000 }).show();
    }

    function setActiveTab(status) {
        document.querySelectorAll('.doc-tab').forEach(function (tab) {
            const active = tab.dataset.status === status;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }

    function loadTab(status) {
        const url = indexUrl + '?status=' + encodeURIComponent(status);
        listWrap.style.opacity = '0.55';

        fetch(url, {
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) { return response.text(); })
            .then(function (html) {
                listWrap.innerHTML = html;
                listWrap.style.opacity = '1';
                setActiveTab(status);
                if (window.history && window.history.pushState) {
                    window.history.pushState({ status: status }, '', url);
                }
            })
            .catch(function () {
                listWrap.style.opacity = '1';
                showToast('Could not load that list. Please try again.', 'danger');
            });
    }

    document.querySelectorAll('.doc-tab').forEach(function (tab) {
        tab.addEventListener('click', function (event) {
            event.preventDefault();
            loadTab(tab.dataset.status);
        });
    });

    function searchStudents() {
        const query = searchInput.value.trim();
        if (query.length < 2) {
            showToast('Enter at least 2 characters to search.', 'danger');
            return;
        }

        results.innerHTML = '<div class="list-group-item text-muted">Searching...</div>';
        fetch(@json(route('student.documents.search')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: JSON.stringify({ q: query })
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data.students || !data.students.length) {
                    results.innerHTML = '<div class="list-group-item text-muted">No matching students found.</div>';
                    return;
                }

                results.innerHTML = data.students.map(function (student) {
                    const extra = student.existing_status ? ' · ' + student.existing_status : '';
                    return '<button type="button" class="list-group-item list-group-item-action doc-search-result" data-student-id="' + student.student_id + '">' +
                        '<div class="fw-semibold">' + student.full_name + '</div>' +
                        '<small class="text-muted">ID ' + student.student_id + ' · ' + (student.id_value || 'No NIC') + extra + '</small>' +
                        '</button>';
                }).join('');
            })
            .catch(function () {
                results.innerHTML = '<div class="list-group-item text-danger">Search failed. Please try again.</div>';
            });
    }

    function generateLink(studentId) {
        fetch(@json(route('student.documents.generate')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: JSON.stringify({ student_id: studentId })
        })
            .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok || !result.data.url) {
                    showToast(result.data.message || 'Unable to generate a new link.', 'danger');
                    if (result.data.request_id) {
                        window.location.href = @json(url('/student-documents')) + '/' + result.data.request_id;
                    }
                    return;
                }

                document.getElementById('generatedStudentName').textContent = result.data.student.full_name;
                document.getElementById('generatedLinkUrl').textContent = result.data.url;
                document.getElementById('generatedExpiry').textContent = 'Link expires on ' + (result.data.expires_at || 'the configured date') + '. Send it only to this student.';
                document.getElementById('whatsappShareBtn').href = result.data.share.whatsapp;
                document.getElementById('emailShareBtn').href = result.data.share.email;
                generatedBox.dataset.url = result.data.url;
                generatedBox.classList.remove('d-none');
                generatedBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                showToast(result.data.message, 'success');
            })
            .catch(function () {
                showToast('Unable to generate a link right now.', 'danger');
            });
    }

    searchBtn.addEventListener('click', searchStudents);
    searchInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchStudents();
        }
    });

    results.addEventListener('click', function (event) {
        const button = event.target.closest('[data-student-id]');
        if (button) {
            generateLink(button.dataset.studentId);
        }
    });

    document.getElementById('copyLinkBtn').addEventListener('click', function () {
        const url = generatedBox.dataset.url || '';
        if (!url) {
            return;
        }
        navigator.clipboard.writeText(url).then(function () {
            showToast('Link copied. Send it to the student.', 'success');
        }).catch(function () {
            showToast('Copy the link from the box above.', 'danger');
        });
    });
});
</script>
@endsection
