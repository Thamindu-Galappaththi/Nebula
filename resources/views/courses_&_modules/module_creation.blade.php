@extends('inc.app')

@section('title', 'NEBULA | Module Creation')

@section('content')
<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
<style nonce="{{ $cspNonce }}">
    .module-creation-page .nebula-select {
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .module-creation-tabs {
        display: flex;
        flex-wrap: wrap;
        overflow: visible;
    }
    .module-creation-tabs .nav-item {
        flex: 0 0 auto;
    }
    .module-creation-header,
    .module-list-header {
        gap: 0.75rem;
    }
    .module-table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #modulesTable {
        min-width: 980px;
        margin-bottom: 0;
    }
    .module-actions {
        display: inline-flex;
        flex-wrap: nowrap;
        gap: 0.35rem;
    }
    .module-pagination-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    .module-pagination-bar .pagination {
        margin-bottom: 0;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .swal2-container {
        z-index: 20000;
    }
    @media (max-width: 991.98px) {
        .module-list-header {
            flex-direction: column;
            align-items: stretch !important;
        }
        .module-list-header .btn {
            width: 100%;
        }
        #modulesTable {
            min-width: 0;
        }
        #modulesTable thead {
            display: none;
        }
        #modulesTable,
        #modulesTable tbody,
        #modulesTable tr,
        #modulesTable td {
            display: block;
            width: 100%;
        }
        #modulesTable tr[data-module-id] {
            margin-bottom: 0.85rem;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 0.75rem 0.9rem;
            background: #fff;
        }
        #modulesTable td {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.75rem;
            border: 0;
            border-bottom: 1px solid #f1f3f5;
            padding: 0.45rem 0;
        }
        #modulesTable td:last-child {
            border-bottom: 0;
        }
        #modulesTable td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #6c757d;
            flex: 0 0 38%;
            max-width: 38%;
        }
        #modulesTable td[data-label=""]::before,
        #modulesTable td.module-select-cell::before {
            display: none;
        }
        #modulesTable td.module-select-cell,
        #modulesTable td.module-actions-cell {
            justify-content: flex-end;
            align-items: center;
        }
        #modulesTable .empty-row td {
            display: block;
            text-align: center;
            border: 0;
        }
        #modulesTable .empty-row td::before {
            display: none;
        }
        .module-pagination-bar {
            flex-direction: column;
            align-items: stretch;
        }
        .module-pagination-bar .pagination {
            justify-content: center;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3 module-creation-page">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-4">Create New Module</h2>
            <hr>

            <ul class="nav nav-tabs module-creation-tabs mb-4" id="moduleTypeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="degree-tab" data-bs-toggle="tab" data-bs-target="#degree-module" type="button" role="tab">
                        <i class="ti ti-school me-1"></i>Degree/Diploma Modules
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="certificate-tab" data-bs-toggle="tab" data-bs-target="#certificate-module" type="button" role="tab">
                        <i class="ti ti-certificate me-1"></i>Certificate Modules
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="moduleTypeTabContent">
                <div class="tab-pane fade show active" id="degree-module" role="tabpanel">
                    <form id="degreeModuleForm">
                        @csrf
                        <input type="hidden" name="module_category" value="degree">
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="degree_module_name" class="col-12 col-md-3 col-lg-2 col-form-label">Module Name <span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <input type="text" class="form-control" id="degree_module_name" name="module_name" placeholder="Enter module name" required style="text-transform:none !important;">
                            </div>
                        </div>
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="degree_module_code" class="col-12 col-md-3 col-lg-2 col-form-label">Module Code <span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <input type="text" class="form-control" id="degree_module_code" name="module_code"
                                    placeholder="e.g., CS101_Programming_001"
                                    pattern="^[a-zA-Z0-9]+_[a-zA-Z0-9]+_[a-zA-Z0-9]+$"
                                    title="Module code must follow the pattern: program_name_specification_unit_code"
                                    required>
                                <div class="form-text">
                                    <i class="ti ti-info-circle me-1"></i>
                                    Format: <code>programName_unitName_unitCode</code> (e.g., CS101_Programming_001)
                                </div>
                            </div>
                        </div>
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="degree_credits" class="col-12 col-md-3 col-lg-2 col-form-label">Credits <span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <input type="number" class="form-control" id="degree_credits" name="credits" placeholder="Enter module credits" min="0" required>
                            </div>
                        </div>
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="degree_module_type" class="col-12 col-md-3 col-lg-2 col-form-label">Module Type <span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <select class="form-select" id="degree_module_type" name="module_type" required>
                                    <option selected disabled value="">Choose a type...</option>
                                    <option value="core">Core</option>
                                    <option value="elective">Elective</option>
                                    <option value="special_unit_compulsory">Special Unit Compulsory (S/U)</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-grid d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary" id="degreeModuleSubmitBtn">Create Degree Module</button>
                        </div>
                    </form>
                </div>

                <div class="tab-pane fade" id="certificate-module" role="tabpanel">
                    <form id="certificateModuleForm">
                        @csrf
                        <input type="hidden" name="module_category" value="certificate">
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="cert_module_name" class="col-12 col-md-3 col-lg-2 col-form-label">Module Name <span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <input type="text" class="form-control" id="cert_module_name" name="module_name" placeholder="Enter module name" required style="text-transform:none !important;">
                            </div>
                        </div>
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="cert_module_code" class="col-12 col-md-3 col-lg-2 col-form-label">Module Code <span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <input type="text" class="form-control" id="cert_module_code" name="module_code"
                                    placeholder="e.g., CERT_BasicCoding_001"
                                    pattern="^[a-zA-Z0-9]+_[a-zA-Z0-9]+_[a-zA-Z0-9]+$"
                                    title="Module code must follow the pattern: program_name_specification_unit_code"
                                    required>
                                <div class="form-text">
                                    <i class="ti ti-info-circle me-1"></i>
                                    Format: <code>programName_unitName_unitCode</code> (e.g., CERT_BasicCoding_001)
                                </div>
                            </div>
                        </div>
                        <div class="d-grid d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary" id="certModuleSubmitBtn">Create Certificate Module</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 module-list-header">
                <h2 class="mb-0">Existing Modules</h2>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-danger" id="bulkDeleteBtn" hidden>
                        <i class="ti ti-trash"></i> Delete Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" id="exportBtn">
                        <i class="ti ti-download"></i> Export CSV
                    </button>
                </div>
            </div>
            <hr>

            <form id="moduleFilterForm" method="GET" action="{{ route('module.creation') }}" class="row g-2 g-md-3 align-items-end mb-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted" for="searchInput">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" name="search" placeholder="Search modules..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label small text-muted" for="filterCategory">Category</label>
                    <select class="form-select" id="filterCategory" name="category">
                        <option value="">All Categories</option>
                        <option value="degree" @selected(($filters['category'] ?? '') === 'degree')>Degree/Diploma</option>
                        <option value="certificate" @selected(($filters['category'] ?? '') === 'certificate')>Certificate</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label small text-muted" for="filterType">Type</label>
                    <select class="form-select" id="filterType" name="type">
                        <option value="">All Types</option>
                        <option value="core" @selected(($filters['type'] ?? '') === 'core')>Core</option>
                        <option value="elective" @selected(($filters['type'] ?? '') === 'elective')>Elective</option>
                        <option value="special_unit_compulsory" @selected(($filters['type'] ?? '') === 'special_unit_compulsory')>Special Unit Compulsory</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label small text-muted" for="perPageSelect">Per page</label>
                    <select class="form-select" id="perPageSelect" name="per_page">
                        <option value="10" @selected((int) $perPage === 10)>10 per page</option>
                        <option value="25" @selected((int) $perPage === 25)>25 per page</option>
                        <option value="50" @selected((int) $perPage === 50)>50 per page</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2 d-grid gap-2">
                    <button class="btn btn-primary" type="submit" id="filterBtn">Filter</button>
                    <button class="btn btn-outline-secondary" type="button" id="clearFiltersBtn">Clear</button>
                </div>
            </form>

            <div class="d-flex justify-content-end align-items-center mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label" for="selectAll">
                        <small>Select All</small>
                    </label>
                </div>
            </div>

            <div class="module-table-scroll">
                <table class="table table-striped table-bordered table-hover" id="modulesTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="selectAllHeader" class="form-check-input" aria-label="Select all modules">
                            </th>
                            <th>Module Name</th>
                            <th>Module Code</th>
                            <th>Category</th>
                            <th>Credits</th>
                            <th>Type</th>
                            <th style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="moduleTableBody">
                        @include('courses_&_modules.partials.module_rows')
                    </tbody>
                </table>
            </div>

            <div id="modulePagination">
                @include('courses_&_modules.partials.module_pagination')
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModuleModal" tabindex="-1" aria-labelledby="editModuleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <form id="editModuleForm">
                @csrf
                @method('PATCH')
                <input type="hidden" id="edit_module_id" name="module_id">
                <input type="hidden" id="edit_module_category" name="module_category">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModuleModalLabel">Edit Module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <span class="badge" id="editCategoryBadge">Degree/Diploma</span>
                    </div>
                    <div class="mb-3">
                        <label for="edit_module_name" class="form-label">Module Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_module_name" name="module_name" required style="text-transform:none !important;">
                    </div>
                    <div class="mb-3">
                        <label for="edit_module_code" class="form-label">Module Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_module_code" name="module_code"
                            pattern="^[a-zA-Z0-9]+_[a-zA-Z0-9]+_[a-zA-Z0-9]+$" required>
                        <div class="form-text">Format: <code>programName_unitName_unitCode</code></div>
                    </div>
                    <div id="editDegreeFields">
                        <div class="mb-3">
                            <label for="edit_credits" class="form-label">Credits <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_credits" name="credits" min="0">
                        </div>
                        <div class="mb-3">
                            <label for="edit_module_type" class="form-label">Module Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_module_type" name="module_type">
                                <option value="core">Core</option>
                                <option value="elective">Elective</option>
                                <option value="special_unit_compulsory">Special Unit Compulsory (S/U)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="editModuleSaveBtn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.js"></script>
<script nonce="{{ $cspNonce }}">
$(function () {
    const csrfToken = '{{ csrf_token() }}';
    const storeUrl = '{{ route("module.store") }}';
    const updateUrlTemplate = '{{ url("/modules") }}';
    const listUrl = '{{ route("module.creation") }}';
    const exportUrl = '{{ route("module.export") }}';
    const codePattern = /^[a-zA-Z0-9]+_[a-zA-Z0-9]+_[a-zA-Z0-9]+$/;
    const editModal = document.getElementById('editModuleModal');
    const editModalInstance = editModal && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(editModal) : null;

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

    function currentListUrl() {
        const params = new URLSearchParams($('#moduleFilterForm').serialize());
        const query = params.toString();
        return query ? (listUrl + '?' + query) : listUrl;
    }

    function loadModules(url, pushUrl) {
        const $body = $('#moduleTableBody');
        const $pager = $('#modulePagination');
        $body.addClass('opacity-50');
        $pager.addClass('opacity-50');

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (res && res.html) {
                    $body.html(res.html);
                }
                if (res && res.pagination) {
                    $pager.html(res.pagination);
                }
                $('#selectAll, #selectAllHeader').prop('checked', false);
                updateBulkDeleteButton();
                if (pushUrl) {
                    history.pushState({ moduleAjax: true }, '', url);
                }
            },
            error: function () {
                showAlert('Error', 'Failed to load modules.', 'error');
            },
            complete: function () {
                $body.removeClass('opacity-50');
                $pager.removeClass('opacity-50');
            }
        });
    }

    $('#moduleFilterForm').on('submit', function (e) {
        e.preventDefault();
        loadModules(currentListUrl(), true);
    });

    $('#perPageSelect').on('change', function () {
        loadModules(currentListUrl(), true);
    });

    function resetFilterSelect(select, value) {
        if (!select) {
            return;
        }
        if (value === undefined || value === '') {
            select.value = '';
            select.selectedIndex = 0;
        } else {
            select.value = String(value);
            if (select.value !== String(value)) {
                select.selectedIndex = 0;
            }
        }
        const selected = select.options[select.selectedIndex];
        const wrap = select.closest('.nebula-select');
        const toggle = wrap ? wrap.querySelector('.nebula-select-toggle') : null;
        if (toggle) {
            toggle.textContent = selected ? selected.text : '';
            toggle.title = toggle.textContent;
        }
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function normalizeModuleType(raw) {
        const value = String(raw || '').trim().toLowerCase();
        if (['elective', 'e'].includes(value)) {
            return 'elective';
        }
        if ([
            'special_unit_compulsory',
            's/u',
            'su',
            'special unit compulsory',
            'special unit compulsory (s/u)',
            'special_unit'
        ].includes(value)) {
            return 'special_unit_compulsory';
        }
        if (['core', 'c'].includes(value)) {
            return 'core';
        }
        return value || 'core';
    }

    $('#clearFiltersBtn').on('click', function () {
        $('#searchInput').val('');
        resetFilterSelect(document.getElementById('filterCategory'), '');
        resetFilterSelect(document.getElementById('filterType'), '');
        resetFilterSelect(document.getElementById('perPageSelect'), '10');
    });

    $(document).on('click', '#modulePagination .pagination a.page-link', function (e) {
        const href = $(this).attr('href');
        if (!href || href === '#' || $(this).closest('.page-item').hasClass('disabled') || $(this).closest('.page-item').hasClass('active')) {
            e.preventDefault();
            return;
        }
        e.preventDefault();
        loadModules(href, true);
    });

    window.addEventListener('popstate', function () {
        if (!$('.module-creation-page').length) {
            return;
        }
        loadModules(window.location.href, false);
    });

    function updateBulkDeleteButton() {
        const count = $('.module-checkbox:checked').length;
        if (count > 0) {
            $('#bulkDeleteBtn').prop('hidden', false).html('<i class="ti ti-trash"></i> Delete Selected (' + count + ')');
        } else {
            $('#bulkDeleteBtn').prop('hidden', true);
        }
    }

    $('#selectAll, #selectAllHeader').on('change', function () {
        const isChecked = $(this).prop('checked');
        $('#selectAll, #selectAllHeader').prop('checked', isChecked);
        $('.module-checkbox').prop('checked', isChecked);
        updateBulkDeleteButton();
    });

    $(document).on('change', '.module-checkbox', function () {
        const total = $('.module-checkbox').length;
        const checked = $('.module-checkbox:checked').length;
        $('#selectAll, #selectAllHeader').prop('checked', total > 0 && total === checked);
        updateBulkDeleteButton();
    });

    function isValidCode(value) {
        return codePattern.test(value);
    }

    $('#degree_module_code, #cert_module_code, #edit_module_code').on('input', function () {
        const value = $(this).val();
        $(this).removeClass('is-valid is-invalid');
        if (value.length > 0) {
            $(this).addClass(isValidCode(value) ? 'is-valid' : 'is-invalid');
        }
    });

    function submitCreate($form) {
        const codeInput = $form.find('[name="module_code"]');
        if (!isValidCode(codeInput.val())) {
            codeInput.addClass('is-invalid');
            showAlert('Invalid code', 'Module code must follow the pattern: program_name_specification_unit_code', 'warning');
            return;
        }

        $.ajax({
            url: storeUrl,
            type: 'POST',
            data: $form.serialize(),
            success: function (response) {
                if (response.success) {
                    $form[0].reset();
                    $form.find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
                    showAlert('Created', response.message, 'success').then(function () {
                        loadModules(currentListUrl(), false);
                    });
                } else {
                    showAlert('Error', response.message || 'Could not create the module.', 'error');
                }
            },
            error: function (xhr) {
                showAlert('Error', validationMessage(xhr), 'error');
            }
        });
    }

    $('#degreeModuleForm').on('submit', function (e) {
        e.preventDefault();
        submitCreate($(this));
    });

    $('#certificateModuleForm').on('submit', function (e) {
        e.preventDefault();
        submitCreate($(this));
    });

    $(document).on('click', '.edit-module-btn', function () {
        const row = $(this).closest('tr');
        const category = row.data('category') || 'degree';
        $('#edit_module_id').val(row.data('module-id'));
        $('#edit_module_category').val(category);
        $('#edit_module_name').val(row.find('.module-name').text().trim());
        $('#edit_module_code').val(row.find('.module-code').text().trim()).removeClass('is-valid is-invalid');

        if (category === 'certificate') {
            $('#editCategoryBadge').removeClass('bg-secondary').addClass('bg-success').text('Certificate');
            $('#editDegreeFields').hide();
            $('#edit_credits').prop('required', false).val('');
            $('#edit_module_type').prop('required', false);
            resetFilterSelect(document.getElementById('edit_module_type'), 'core');
        } else {
            $('#editCategoryBadge').removeClass('bg-success').addClass('bg-secondary').text('Degree/Diploma');
            $('#editDegreeFields').show();
            $('#edit_credits').prop('required', true).val(row.attr('data-credits'));
            $('#edit_module_type').prop('required', true);
            const storedType = row.attr('data-module-type') || row.attr('data-type') || '';
            resetFilterSelect(document.getElementById('edit_module_type'), normalizeModuleType(storedType));
        }

        if (editModalInstance) {
            editModalInstance.show();
        }
    });

    $('#editModuleForm').on('submit', function (e) {
        e.preventDefault();
        const moduleId = $('#edit_module_id').val();
        const code = $('#edit_module_code').val();
        if (!isValidCode(code)) {
            $('#edit_module_code').addClass('is-invalid');
            showAlert('Invalid code', 'Module code must follow the pattern: program_name_specification_unit_code', 'warning');
            return;
        }

        $.ajax({
            url: updateUrlTemplate + '/' + moduleId,
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                if (response.success) {
                    if (editModalInstance) {
                        editModalInstance.hide();
                    }
                    showAlert('Updated', response.message, 'success').then(function () {
                        loadModules(window.location.href, false);
                    });
                } else {
                    showAlert('Error', response.message || 'Could not update the module.', 'error');
                }
            },
            error: function (xhr) {
                showAlert('Error', validationMessage(xhr), 'error');
            }
        });
    });

    $(document).on('click', '.delete-module-btn', function () {
        const row = $(this).closest('tr');
        const moduleId = row.data('module-id');
        const name = row.find('.module-name').text().trim();

        confirmDelete('Delete module?', 'Delete "' + name + '"? This cannot be undone.').then(function (ok) {
            if (!ok) return;
            $.ajax({
                url: updateUrlTemplate + '/' + moduleId,
                type: 'DELETE',
                data: { _token: csrfToken },
                success: function (response) {
                    if (response.success) {
                        showAlert('Deleted', response.message, 'success').then(function () {
                            loadModules(window.location.href, false);
                        });
                    } else {
                        showAlert('Error', response.message || 'Could not delete the module.', 'error');
                    }
                },
                error: function (xhr) {
                    showAlert('Error', validationMessage(xhr), 'error');
                }
            });
        });
    });

    $('#bulkDeleteBtn').on('click', function () {
        const selectedIds = [];
        $('.module-checkbox:checked').each(function () {
            selectedIds.push($(this).data('module-id'));
        });
        if (selectedIds.length === 0) return;

        confirmDelete('Delete selected modules?', 'Delete ' + selectedIds.length + ' module(s)? This cannot be undone.').then(function (ok) {
            if (!ok) return;
            $.ajax({
                url: '{{ route("module.bulkDestroy") }}',
                type: 'POST',
                data: {
                    _token: csrfToken,
                    ids: selectedIds
                },
                success: function (response) {
                    showAlert(response.success ? 'Deleted' : 'Error', response.message, response.success ? 'success' : 'error').then(function () {
                        loadModules(window.location.href, false);
                    });
                },
                error: function (xhr) {
                    showAlert('Error', validationMessage(xhr), 'error');
                }
            });
        });
    });

    $('#exportBtn').on('click', function () {
        const params = new URLSearchParams();
        const search = $.trim($('#searchInput').val() || '');
        const category = $('#filterCategory').val() || '';
        const type = $('#filterType').val() || '';
        if (search) {
            params.set('search', search);
        }
        if (category) {
            params.set('category', category);
        }
        if (type) {
            params.set('type', type);
        }
        const url = params.toString() ? (exportUrl + '?' + params.toString()) : exportUrl;
        window.location.assign(url);
    });
});
</script>
@endpush
