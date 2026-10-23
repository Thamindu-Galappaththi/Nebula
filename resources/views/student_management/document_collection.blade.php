@extends('inc.app')

@section('title', 'NEBULA | Document Collection')

@section('content')
<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
<style nonce="{{ $cspNonce }}">
    .doc-page {
        min-width: 0;
        max-width: 1180px;
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
    .doc-hero-inner {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(200px, 240px);
        gap: 1.1rem 1.25rem;
        align-items: center;
    }
    .doc-hero-art {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        min-height: 210px;
    }
    .doc-hero-art svg {
        width: 100%;
        max-width: 270px;
        height: auto;
    }
    .doc-hero-art-caption {
        margin-top: 0.35rem;
        color: #6b7a99;
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-align: center;
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
        max-width: 100%;
    }
    .doc-search-row .form-control {
        flex: 1 1 auto;
        min-width: 0;
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
    .doc-share-box.is-busy .doc-actions {
        pointer-events: none;
        opacity: 0.72;
    }
    .doc-share-box.is-busy .doc-actions .btn {
        cursor: wait;
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
        padding: 0.7rem 0.75rem;
        font-size: 0.9rem;
        vertical-align: middle;
        line-height: 1.2;
    }
    .doc-list td {
        padding: 0.75rem;
        border-color: #edf1f7;
        vertical-align: top;
        font-size: 0.93rem;
        line-height: 1.35;
    }
    .doc-list tbody td:not(:first-child) {
        padding-top: 0.82rem;
        white-space: nowrap;
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
    @media (max-width: 1199.98px) {
        .doc-hero-inner { grid-template-columns: minmax(0, 1fr) 200px; }
    }
    @media (max-width: 991.98px) {
        .doc-hero-inner { grid-template-columns: 1fr; }
        .doc-hero-art { display: none; }
    }
    @media (max-width: 767.98px) {
        .doc-hero, .doc-panel .card-body { padding: 1rem; }
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
            <div class="doc-hero-inner">
                <div class="doc-hero-copy">
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
                </div>

                <div class="doc-hero-art" aria-hidden="true">
                    <svg viewBox="0 0 280 220" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <ellipse cx="148" cy="196" rx="88" ry="14" fill="#dbe6ff"/>
                        <rect x="86" y="58" width="128" height="122" rx="16" fill="#e8efff"/>
                        <rect x="98" y="74" width="104" height="14" rx="7" fill="#c5d4f5"/>
                        <rect x="98" y="98" width="78" height="10" rx="5" fill="#d7e2fb"/>
                        <rect x="98" y="116" width="90" height="10" rx="5" fill="#d7e2fb"/>
                        <rect x="98" y="134" width="62" height="10" rx="5" fill="#d7e2fb"/>
                        <rect x="46" y="38" width="92" height="118" rx="12" fill="#fff" stroke="#23408e" stroke-width="3"/>
                        <rect x="62" y="58" width="60" height="8" rx="4" fill="#c5d4f5"/>
                        <rect x="62" y="74" width="48" height="8" rx="4" fill="#e0e8f8"/>
                        <rect x="62" y="90" width="54" height="8" rx="4" fill="#e0e8f8"/>
                        <circle cx="74" cy="122" r="10" fill="#d9f7e6"/>
                        <path d="M69 122.5l3.2 3.2 7-7" stroke="#0f7a45" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="158" y="28" width="84" height="108" rx="12" fill="#23408e"/>
                        <rect x="174" y="48" width="52" height="8" rx="4" fill="#9bb4f0"/>
                        <rect x="174" y="64" width="40" height="8" rx="4" fill="#6e8fe0"/>
                        <rect x="174" y="80" width="46" height="8" rx="4" fill="#6e8fe0"/>
                        <circle cx="196" cy="112" r="16" fill="#fff"/>
                        <path d="M196 104v16M188 112h16" stroke="#23408e" stroke-width="2.4" stroke-linecap="round"/>
                        <circle cx="232" cy="46" r="18" fill="#4c6bd8"/>
                        <path d="M226 46h7.5a4.5 4.5 0 110 9H229" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                        <path d="M232 40v4.5" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                    </svg>
                    <div class="doc-hero-art-caption">Secure unique links · {{ $retentionDays }}-day retention</div>
                </div>
            </div>

            <div id="generatedLinkBox" class="doc-share-box mt-3 d-none">
                <div class="fw-semibold mb-2" id="generatedStudentName"></div>
                <div class="doc-share-url mb-3" id="generatedLinkUrl"></div>
                <div class="doc-actions">
                    <button type="button" class="btn btn-dark" id="copyLinkBtn">Copy link</button>
                    <a class="btn btn-success" id="whatsappShareBtn" target="_blank" rel="noopener noreferrer">Share on WhatsApp</a>
                    <button type="button" class="btn btn-outline-primary" id="emailShareBtn">Send email to student</button>
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

<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.js"></script>
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('studentSearch');
    const searchBtn = document.getElementById('studentSearchBtn');
    const results = document.getElementById('studentSearchResults');
    const generatedBox = document.getElementById('generatedLinkBox');
    const listWrap = document.getElementById('docListWrap');
    const emailShareBtn = document.getElementById('emailShareBtn');
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const indexUrl = @json(route('student.documents.index'));
    const generateUrl = @json(route('student.documents.generate'));
    const showBaseUrl = @json(url('/student-documents'));
    const emailUrlTemplate = @json(url('/student-documents/__ID__/email'));

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
        });
    }

    function showToast(message, type) {
        const container = document.querySelector('.toast-container');
        if (!container) {
            return;
        }
        const html = '<div class="toast align-items-center text-white bg-' + type + ' border-0" role="alert"><div class="d-flex"><div class="toast-body">' + escapeHtml(message) + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>';
        container.insertAdjacentHTML('beforeend', html);
        const toastEl = container.querySelector('.toast:last-child');
        new bootstrap.Toast(toastEl, { delay: 3000 }).show();
    }

    function showNotice(message, icon) {
        return Swal.fire({
            icon: icon || 'info',
            title: icon === 'success' ? 'Done' : 'Please note',
            text: message,
            confirmButtonColor: '#23408e'
        });
    }

    function setActiveTab(status) {
        document.querySelectorAll('.doc-tab').forEach(function (tab) {
            const active = tab.dataset.status === status;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }

    function updateTabCounts(counts) {
        if (!counts || typeof counts !== 'object') {
            return;
        }
        document.querySelectorAll('.doc-tab').forEach(function (tab) {
            const badge = tab.querySelector('.doc-tab-count');
            const key = tab.dataset.status;
            if (!badge || !Object.prototype.hasOwnProperty.call(counts, key)) {
                return;
            }
            badge.textContent = String(counts[key] ?? 0);
        });
    }

    function applyListHtml(html) {
        listWrap.innerHTML = html;
        const countsEl = listWrap.querySelector('[data-counts]');
        if (!countsEl || !countsEl.dataset.counts) {
            return;
        }
        try {
            updateTabCounts(JSON.parse(countsEl.dataset.counts));
        } catch (error) {
            // Keep the previous tab counts if the payload is not valid JSON.
        }
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
                applyListHtml(html);
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

    function statusLabel(status) {
        if (status === 'pending') {
            return 'Active link already sent';
        }
        if (status === 'submitted') {
            return 'Documents already submitted';
        }
        if (status === 'expired') {
            return 'Previous link expired';
        }
        return status || '';
    }

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
                    const extra = student.existing_status ? ' · ' + statusLabel(student.existing_status) : '';
                    return '<button type="button" class="list-group-item list-group-item-action doc-search-result" data-student="' + encodeURIComponent(JSON.stringify(student)) + '">' +
                        '<div class="fw-semibold">' + escapeHtml(student.full_name) + '</div>' +
                        '<small class="text-muted">ID ' + escapeHtml(student.student_id) + ' · ' + escapeHtml(student.id_value || 'No NIC') + extra + '</small>' +
                        '</button>';
                }).join('');
            })
            .catch(function () {
                results.innerHTML = '<div class="list-group-item text-danger">Search failed. Please try again.</div>';
            });
    }

    function showLinkBox(data) {
        document.getElementById('generatedStudentName').textContent = data.student.full_name;
        document.getElementById('generatedLinkUrl').textContent = data.url;
        const emailNote = data.can_email
            ? ' Email will be sent to ' + data.student.email + '.'
            : ' This student has no email on file, so send the link another way.';
        document.getElementById('generatedExpiry').textContent = (data.reused
            ? 'Existing link is still valid until '
            : 'Link expires on ') + (data.expires_at || 'the configured date') + '.' + emailNote;
        document.getElementById('whatsappShareBtn').href = data.share.whatsapp;
        generatedBox.dataset.url = data.url;
        generatedBox.dataset.requestId = data.request_id || '';
        generatedBox.dataset.email = data.student.email || '';
        generatedBox.dataset.mailto = (data.share && data.share.email) ? data.share.email : '';
        emailShareBtn.disabled = !data.can_email;
        generatedBox.classList.remove('d-none');
        generatedBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        loadTab('pending');
    }

    function generateLink(studentId, forceNew) {
        fetch(generateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: JSON.stringify({ student_id: studentId, force_new: !!forceNew })
        })
            .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
            .then(function (result) {
                if (result.data && result.data.blocked && result.data.request_id) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Documents already submitted',
                        text: result.data.message,
                        confirmButtonText: 'Open record',
                        showCancelButton: true,
                        confirmButtonColor: '#23408e'
                    }).then(function (choice) {
                        if (choice.isConfirmed) {
                            window.location.href = showBaseUrl + '/' + result.data.request_id;
                        }
                    });
                    return;
                }

                if (result.data && result.data.needs_replace) {
                    confirmReplaceThenGenerate(studentId, result.data.message);
                    return;
                }

                if (!result.ok || !result.data.url) {
                    showNotice(result.data.message || 'Unable to generate a new link.', 'error');
                    return;
                }

                showLinkBox(result.data);
                showToast(result.data.message, 'success');
            })
            .catch(function () {
                showNotice('Unable to generate a link right now.', 'error');
            });
    }

    function confirmReplaceThenGenerate(studentId, message) {
        Swal.fire({
            icon: 'warning',
            title: 'Replace the existing link?',
            text: message || 'The link already sent to this student will stop working immediately. Only do this if they lost the previous link.',
            showCancelButton: true,
            confirmButtonText: 'Yes, create a new link',
            cancelButtonText: 'Keep existing link',
            confirmButtonColor: '#c2410c'
        }).then(function (result) {
            if (result.isConfirmed) {
                generateLink(studentId, true);
            }
        });
    }

    function handleStudentChoice(student) {
        if (student.existing_status === 'submitted') {
            Swal.fire({
                icon: 'info',
                title: 'Documents already submitted',
                text: 'This student already uploaded documents. A new link cannot be created until the 7-day retention period ends.',
                confirmButtonText: 'Open record',
                showCancelButton: true,
                confirmButtonColor: '#23408e'
            }).then(function (result) {
                if (result.isConfirmed && student.existing_request_id) {
                    window.location.href = showBaseUrl + '/' + student.existing_request_id;
                }
            });
            return;
        }

        if (student.existing_status === 'pending') {
            const sentBy = student.existing_sent_by ? ' by <strong>' + escapeHtml(student.existing_sent_by) + '</strong>' : '';
            const expires = student.existing_expires_at ? ' and expires on <strong>' + escapeHtml(student.existing_expires_at) + '</strong>' : '';
            Swal.fire({
                icon: 'info',
                title: 'Active link already exists',
                html: '<p class="mb-2">A submission link was already sent' + sentBy + expires + '.</p><p class="mb-0">Use the existing link so the one already shared with the student keeps working. Create a new link only if they lost it — that will make the previous link stop working.</p>',
                showCancelButton: true,
                showDenyButton: !!student.has_recoverable_link,
                confirmButtonText: student.has_recoverable_link ? 'Use existing link' : 'Create a replacement',
                denyButtonText: 'Create a new link',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#23408e',
                denyButtonColor: '#c2410c'
            }).then(function (result) {
                if (result.isConfirmed) {
                    if (student.has_recoverable_link) {
                        generateLink(student.student_id, false);
                    } else {
                        confirmReplaceThenGenerate(student.student_id);
                    }
                } else if (result.isDenied) {
                    confirmReplaceThenGenerate(student.student_id);
                }
            });
            return;
        }

        generateLink(student.student_id, false);
    }

    function clearSearchResults() {
        results.innerHTML = '';
    }

    searchBtn.addEventListener('click', searchStudents);
    searchInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            searchStudents();
        }
    });
    searchInput.addEventListener('search', function () {
        if (!searchInput.value.trim()) {
            clearSearchResults();
        }
    });
    searchInput.addEventListener('input', function () {
        if (!searchInput.value.trim()) {
            clearSearchResults();
        }
    });

    results.addEventListener('click', function (event) {
        const button = event.target.closest('[data-student]');
        if (!button) {
            return;
        }
        try {
            handleStudentChoice(JSON.parse(decodeURIComponent(button.dataset.student)));
        } catch (error) {
            showNotice('Could not open that student record.', 'error');
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

    function setShareBusy(busy) {
        generatedBox.classList.toggle('is-busy', busy);
        generatedBox.setAttribute('aria-busy', busy ? 'true' : 'false');
        generatedBox.querySelectorAll('.doc-actions .btn, .doc-actions a').forEach(function (el) {
            el.classList.toggle('disabled', busy);
            if (el.tagName === 'BUTTON') {
                el.disabled = busy;
            }
            if (el.tagName === 'A') {
                el.setAttribute('aria-disabled', busy ? 'true' : 'false');
                el.tabIndex = busy ? -1 : 0;
            }
        });
        emailShareBtn.innerHTML = busy
            ? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Sending email...'
            : 'Send email to student';
        if (!busy) {
            emailShareBtn.disabled = !generatedBox.dataset.email;
        }
    }

    emailShareBtn.addEventListener('click', function () {
        const requestId = generatedBox.dataset.requestId || '';
        const email = generatedBox.dataset.email || '';
        if (!requestId || generatedBox.classList.contains('is-busy')) {
            return;
        }
        if (!email) {
            showNotice('This student has no email on file. Copy the link and share it another way.', 'info');
            return;
        }

        setShareBusy(true);
        fetch(emailUrlTemplate.replace('__ID__', requestId), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf
            }
        })
            .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
            .then(function (result) {
                setShareBusy(false);
                if (result.ok) {
                    showNotice(result.data.message, 'success');
                    return;
                }
                const mailto = generatedBox.dataset.mailto || '';
                Swal.fire({
                    icon: 'warning',
                    title: 'Email was not sent',
                    text: result.data.message || 'The server could not send the email. Copy the link, or try your mail app.',
                    showCancelButton: true,
                    confirmButtonText: 'Copy link',
                    cancelButtonText: mailto ? 'Open mail app' : 'Close',
                    confirmButtonColor: '#23408e'
                }).then(function (choice) {
                    if (choice.isConfirmed) {
                        document.getElementById('copyLinkBtn').click();
                    } else if (choice.dismiss === Swal.DismissReason.cancel && mailto) {
                        window.location.href = mailto;
                    }
                });
            })
            .catch(function () {
                setShareBusy(false);
                showNotice('The email could not be sent. Copy the link and share it another way.', 'error');
            });
    });
});
</script>
@endsection
