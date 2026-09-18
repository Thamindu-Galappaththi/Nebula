@extends('inc.app')

@section('title', 'NEBULA | User Management')

@section('content')
<style nonce="{{ $cspNonce }}">
    .body-wrapper > .container-fluid {
        min-width: 0;
        max-width: 100%;
        overflow-x: hidden;
    }
    .user-mgmt-page {
        max-width: 1100px;
        width: 100%;
        min-width: 0;
        margin: 0 auto;
    }
    .user-mgmt-card {
        border-radius: 18px;
        box-shadow: 0 4px 24px 0 rgba(60, 72, 100, 0.08);
        background: #fff;
        padding: 2rem 1.5rem 1.5rem 1.5rem;
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }
    .user-mgmt-table-wrap {
        width: 100%;
        min-width: 0;
        max-width: 100%;
    }
    #usersTable {
        width: 100% !important;
        min-width: 980px;
    }
    .table thead th {
        position: sticky;
        top: 0;
        background: #e8f0fe !important;
        color: #23408e !important;
        z-index: 2;
        font-weight: 600;
        font-size: 0.95rem;
        border-bottom: 2px solid #dbeafe;
        white-space: nowrap;
    }
    .table td, .table th {
        vertical-align: middle;
        font-size: 0.93rem;
        padding: 0.7rem 0.6rem;
    }
    .user-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        min-width: 170px;
    }
    .user-actions .btn {
        margin: 0 !important;
        white-space: nowrap;
    }
    .created-at-cell {
        white-space: nowrap;
    }
    .dataTables_wrapper {
        width: 100% !important;
        max-width: 100%;
        min-width: 0;
        overflow: hidden;
    }
    .dataTables_wrapper .row {
        margin-bottom: 0.5rem;
        --bs-gutter-x: 0;
        min-width: 0;
    }
    .dataTables_wrapper .col-12,
    .dataTables_wrapper [class*="col-"] {
        min-width: 0;
        max-width: 100%;
    }
    .user-mgmt-table-scroll {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        scrollbar-gutter: auto;
    }
    .user-mgmt-table-scroll::-webkit-scrollbar {
        height: 10px;
    }
    .user-mgmt-table-scroll::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 8px;
    }
    .user-mgmt-table-scroll::-webkit-scrollbar-thumb {
        background: #b0b0b0;
        border-radius: 8px;
    }
    .dataTables_length,
    .dataTables_filter {
        margin-bottom: 1rem;
    }
    .dataTables_filter {
        text-align: right;
        float: right;
        width: 100%;
    }
    .dataTables_length label,
    .dataTables_filter label {
        display: flex;
        align-items: center;
        flex-wrap: nowrap;
        gap: 0.5rem;
        margin-bottom: 0;
    }
    .dataTables_filter label {
        justify-content: flex-end;
        width: 100%;
    }
    .dataTables_length select {
        width: auto;
        display: inline-block;
    }
    .dataTables_filter input {
        width: 280px !important;
        max-width: 100%;
        display: inline-block;
        margin-left: 0.5rem;
    }
    div.dataTables_wrapper div.dataTables_info,
    div.dataTables_wrapper div.dataTables_paginate {
        overflow: visible !important;
        white-space: normal;
    }
    @media (max-width: 991.98px) {
        .user-mgmt-card {
            padding: 1.1rem 0.75rem 1rem 0.75rem;
            border-radius: 12px;
        }
    }
    @media (max-width: 767.98px) {
        div.dataTables_wrapper div.dataTables_length,
        div.dataTables_wrapper div.dataTables_filter,
        div.dataTables_wrapper div.dataTables_info,
        div.dataTables_wrapper div.dataTables_paginate {
            float: none !important;
            text-align: left !important;
            width: 100%;
        }
        .dataTables_filter label {
            justify-content: flex-start;
            flex-wrap: wrap;
        }
        div.dataTables_wrapper div.dataTables_filter input {
            width: 100% !important;
            margin-left: 0;
            display: block;
        }
        .dataTables_wrapper .pagination {
            flex-wrap: wrap;
            justify-content: flex-start;
        }
        .user-actions {
            flex-direction: column;
        }
        .user-actions .btn {
            width: 100%;
        }
        .modal-dialog {
            margin: 0.5rem;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3">
    <div class="user-mgmt-page">
    <div class="user-mgmt-card">
        <h3 class="text-center mb-4">User Management</h3>
        <div class="user-mgmt-table-wrap">
            <table class="table table-striped table-bordered" id="usersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Employee ID</th>
                        <th>Roles</th>
                        <th>Location</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($usersArray as $user)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $user['user_name'] }}</td>
                        <td>{{ $user['email'] }}</td>
                        <td>{{ $user['employee_id'] }}</td>
                        <td>{{ $user['user_role'] }}</td>
                        <td>{{ $user['user_location'] }}</td>
                        <td class="created-at-cell">{{ $user['created_at'] }}</td>
                        <td>
                            <div class="user-actions">
                                <button type="button" class="btn btn-sm btn-primary btn-edit-user" data-user-id="{{ $user['user_id'] }}" data-user-location="{{ $user['user_location'] }}">Edit</button>
                                <button type="button" class="btn btn-sm btn-danger btn-delete-user" data-user-id="{{ $user['user_id'] }}" data-user-name="{{ $user['user_name'] }}">Delete</button>
                                <button type="button" class="btn btn-sm btn-warning btn-reset-password" data-user-id="{{ $user['user_id'] }}" data-user-name="{{ $user['user_name'] }}">Reset Password</button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm" method="POST" action="{{ route('user.updateStatus') }}">
                    @csrf
                    <input type="hidden" id="edit_user_id" name="user_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_employee_id" name="employee_id" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_user_roles" class="form-label">Roles <span class="text-danger">*</span></label>
                                <div class="dropdown">
                                    <button class="form-control text-start dropdown-toggle"
                                            type="button"
                                            id="editRoleDropdownButton"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                        Select role(s)
                                    </button>
                                    <div class="dropdown-menu w-100 p-2" aria-labelledby="editRoleDropdownButton" style="max-height: 260px; overflow-y: auto;">
                                        @foreach ($userRoles as $index => $role)
                                            <div class="form-check">
                                                <input class="form-check-input edit-role-option" type="checkbox" value="{{ $role }}" id="edit_role_{{ $index }}">
                                                <label class="form-check-label" for="edit_role_{{ $index }}">{{ $role }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <select class="d-none" id="edit_user_roles" name="user_roles[]" multiple required>
                                    @foreach ($userRoles as $role)
                                        <option value="{{ $role }}">{{ $role }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">You can select more than one role from the dropdown.</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_user_location" class="form-label">Location <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_user_location" name="user_location" required>
                                    <option value="">Select Location</option>
                                    @foreach ($locations as $locationValue => $locationLabel)
                                        <option value="{{ $locationValue }}">{{ $locationLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_status" name="status" required>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                    <option value="2">Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editUserForm" class="btn btn-primary">Update User</button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password for <span id="resetUserName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="resetPasswordForm" method="POST" action="{{ route('user.resetPassword') }}">
                @csrf
                <input type="hidden" id="reset_user_id" name="user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control password-toggle" id="new_password" name="new_password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include DataTables CSS and JS -->
<link nonce="{{ $cspNonce }}" rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" integrity="sha384-Dv1j0mqPOKbG6R+/4/adHCn5JaMBLG3iu8uTXFBM2MjEZuKwtsyLedRcRMR0cq7P" crossorigin="anonymous">
<script nonce="{{ $cspNonce }}" type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js" integrity="sha384-ficRBwtap/VLzILv81vIvgp30PoJYnlCm96tPpNYHXAf+h9SIThOZxxIzRUzbpAh" crossorigin="anonymous"></script>
<script nonce="{{ $cspNonce }}" type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js" integrity="sha384-jIAE3P7Re8BgMkT0XOtfQ6lzZgbDw/02WeRMJvXK3WMHBNynEx5xofqia1OHuGh0" crossorigin="anonymous"></script>

<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function() {
    const editRoleSelect = document.getElementById('edit_user_roles');
    const editRoleDropdownButton = document.getElementById('editRoleDropdownButton');
    const editRoleCheckboxes = document.querySelectorAll('.edit-role-option');

    function getRoleButtonText(selectedRoles) {
        if (selectedRoles.length === 0) {
            return 'Select role(s)';
        }

        if (selectedRoles.length <= 2) {
            return selectedRoles.join(', ');
        }

        return `${selectedRoles.length} roles selected`;
    }

    function syncEditRoleSelection() {
        const selectedRoles = [];

        editRoleCheckboxes.forEach((checkbox) => {
            const option = Array.from(editRoleSelect.options).find((opt) => opt.value === checkbox.value);

            if (option) {
                option.selected = checkbox.checked;
            }

            if (checkbox.checked) {
                selectedRoles.push(checkbox.value);
            }
        });

        editRoleDropdownButton.textContent = getRoleButtonText(selectedRoles);
    }

    window.setEditRoleSelection = function(selectedRoles) {
        editRoleCheckboxes.forEach((checkbox) => {
            checkbox.checked = selectedRoles.includes(checkbox.value);
        });

        syncEditRoleSelection();
    };

    editRoleCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', syncEditRoleSelection);
    });

    syncEditRoleSelection();

    // Event delegation for action buttons (CSP compliant)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-edit-user')) {
            const btn = e.target.closest('.btn-edit-user');
            editUser(btn.dataset.userId, btn.dataset.userLocation);
        }
        if (e.target.closest('.btn-delete-user')) {
            const btn = e.target.closest('.btn-delete-user');
            deleteUser(btn.dataset.userId, btn.dataset.userName);
        }
        if (e.target.closest('.btn-reset-password')) {
            const btn = e.target.closest('.btn-reset-password');
            showResetPasswordModal(btn.dataset.userId, btn.dataset.userName);
        }
    });
    
    // Initialize DataTable
    $('#usersTable').DataTable({
        order: [],
        pageLength: 10,
        autoWidth: false,
        language: {
            search: "Search users:",
            searchPlaceholder: "Search by name, email, employee ID or role",
            lengthMenu: "Show _MENU_ users per page",
            info: "Showing _START_ to _END_ of _TOTAL_ users"
        },
        dom: '<"row align-items-center"<"col-12 col-md-6"l><"col-12 col-md-6"f>>' +
             '<"row"<"col-12 user-mgmt-table-scroll"tr>>' +
             '<"row align-items-center"<"col-12 col-md-5"i><"col-12 col-md-7"p>>',
        initComplete: function () {
            const input = this.api().table().container().querySelector('.dataTables_filter input');
            if (input && !input.placeholder) {
                input.placeholder = 'Search by name, email, employee ID or role';
            }
        },
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        columnDefs: [
            { orderable: false, targets: '_all' }
        ]
    });

    // Handle edit user form submission
    const editUserForm = document.getElementById('editUserForm');
    if (editUserForm) {
        editUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    const editModalEl = document.getElementById('editUserModal');
                    const editModal = bootstrap.Modal.getInstance(editModalEl) || bootstrap.Modal.getOrCreateInstance(editModalEl);
                    editModal.hide();
                    // Reload page to show updated data
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message || 'Error updating user', 'danger');
                }
            })
            .catch(error => {
                showToast('Error: ' + error.message, 'danger');
            });
        });
    }
});

function campusKey(value) {
    const text = String(value || '');
    if (/Peradeniya/i.test(text)) {
        return 'Peradeniya';
    }
    if (/Moratuwa/i.test(text)) {
        return 'Moratuwa';
    }
    if (/Welisara/i.test(text)) {
        return 'Welisara';
    }
    return text.trim();
}

function syncNebulaSelect(select) {
    if (!select) {
        return;
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

function setEditUserLocation(storedLocation) {
    const select = document.getElementById('edit_user_location');
    if (!select) {
        return;
    }

    Array.from(select.querySelectorAll('option[data-extra="1"]')).forEach((option) => option.remove());

    const stored = String(storedLocation ?? '').trim();
    const key = campusKey(stored);
    const matched = Array.from(select.options).find((option) => option.value !== '' && (option.value === key || option.value === stored));

    if (matched) {
        select.value = matched.value;
        syncNebulaSelect(select);
        return;
    }

    if (stored) {
        const extra = new Option(stored, stored, true, true);
        extra.dataset.extra = '1';
        select.add(extra);
        syncNebulaSelect(select);
        return;
    }

    select.value = '';
    syncNebulaSelect(select);
}

function editUser(userId, fallbackLocation) {
    setEditUserLocation(fallbackLocation);

    // Fetch user details
    fetch('/user/get-details', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ user_id: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const user = data.user;
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_employee_id').value = user.employee_id;
            const selectedRoles = Array.isArray(user.user_roles) && user.user_roles.length > 0
                ? user.user_roles
                : (user.user_role ? [user.user_role] : []);
            if (typeof window.setEditRoleSelection === 'function') {
                window.setEditRoleSelection(selectedRoles);
            }
            setEditUserLocation(user.user_location_key || user.user_location || fallbackLocation);
            const statusSelect = document.getElementById('edit_status');
            statusSelect.value = user.status;
            syncNebulaSelect(statusSelect);
            
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editUserModal')).show();
        } else {
            showToast(data.message || 'Error fetching user details', 'danger');
        }
    })
    .catch(error => {
        showToast('Error: ' + error.message, 'danger');
    });
}

function deleteUser(userId, userName) {
    if (confirm(`Are you sure you want to delete user "${userName}"? This action cannot be undone.`)) {
        fetch('/user/delete', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                // Reload page to show updated data
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Error deleting user', 'danger');
            }
        })
        .catch(error => {
            showToast('Error: ' + error.message, 'danger');
        });
    }
}

function showResetPasswordModal(userId, userName) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('resetUserName').textContent = userName;
    document.getElementById('new_password').value = '';
    var modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    modal.show();
}

// Show/hide password toggle for all password fields
$(document).on('click', '.toggle-password', function() {
    var input = $(this).siblings('input');
    var icon = $(this).find('i');
    if (input.attr('type') === 'password') {
        input.attr('type', 'text');
        icon.removeClass('ti-eye').addClass('ti-eye-off');
        $(this).attr('title', 'Hide password');
    } else {
        input.attr('type', 'password');
        icon.removeClass('ti-eye-off').addClass('ti-eye');
        $(this).attr('title', 'Show password');
    }
});

function showToast(message, type) {
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>`;
    document.querySelector('.toast-container').insertAdjacentHTML('beforeend', toastHtml);
    const toastEl = document.querySelector('.toast-container .toast:last-child');
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
    return toast;
}
</script>

@endsection
