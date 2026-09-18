@extends('inc.app')

@section('title', 'NEBULA | User Profile')

@section('content')
<style nonce="{{ $cspNonce }}">
    .body-wrapper > .container-fluid {
        min-width: 0;
        max-width: 100%;
        overflow-x: hidden;
    }
    .user-profile-page {
        max-width: 920px;
        width: 100%;
        min-width: 0;
        margin: 0 auto;
    }
    .user-profile-card {
        border-radius: 18px;
        box-shadow: 0 4px 24px 0 rgba(60, 72, 100, 0.08);
        background: #fff;
        padding: 2rem 1.5rem 1.5rem;
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }
    .user-profile-page .nav-tabs {
        flex-wrap: wrap;
        gap: 0;
        border-bottom: 1px solid #dee2e6;
    }
    .user-profile-page .nav-tabs .nav-item {
        margin: 0;
    }
    .user-profile-page .nav-tabs .nav-link {
        color: #6c8cff;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        margin: 0;
        border-radius: 4px 4px 0 0;
        padding: 0.65rem 1rem;
        white-space: nowrap;
    }
    .user-profile-page .nav-tabs .nav-link.active {
        background-color: #6c8cff !important;
        color: #fff !important;
        border-color: #6c8cff #6c8cff #fff !important;
        font-weight: 500;
    }
    .user-profile-avatar {
        width: 150px;
        height: 150px;
        border: 2px solid #ccc;
        border-radius: 50%;
        overflow: hidden;
        margin: 0 auto 0.85rem;
    }
    .user-profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .user-profile-preview {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #dbe3ef;
        display: none;
        margin: 0.75rem auto 0;
    }
    .user-profile-preview.is-visible {
        display: block;
    }
    .cursor-pointer {
        cursor: pointer;
    }
    @media (max-width: 767.98px) {
        .user-profile-card {
            padding: 1.1rem 0.85rem 1rem;
            border-radius: 12px;
        }
        .user-profile-page .nav-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }
        .user-profile-page .nav-tabs .nav-link {
            flex: 0 0 auto;
        }
        .user-profile-page .col-form-label {
            margin-bottom: 0.25rem;
            padding-top: 0;
        }
        .modal-dialog {
            margin: 0.5rem;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3">
    <div class="user-profile-page">
        <div class="user-profile-card">
            <h2 class="text-center mb-3 mb-md-4">User Profile</h2>
            <hr class="mb-4">

            <div class="text-center mb-4">
                <div class="user-profile-avatar">
                    <img src="{{ !empty($userData['user_profile']) ? asset('storage/' . $userData['user_profile']) : asset('images/profile/user-1.jpg') }}" alt="User Profile" id="profilePictureImg">
                </div>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editPictureModal">Edit Picture</button>
            </div>

            <ul class="nav nav-tabs" id="userProfileTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">Profile</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false">Settings</button>
                </li>
            </ul>

            <div class="tab-content pt-3" id="userProfileTabContent">
                <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                    <div class="row g-2 mb-3 align-items-center">
                        <label for="adminEmail" class="col-12 col-md-3 col-form-label fw-bold">Email</label>
                        <div class="col-12 col-md-9">
                            <input type="email" class="form-control" value="{{ $userData['email'] }}" id="adminEmail" placeholder="User email" readonly>
                        </div>
                    </div>
                    <div class="row g-2 mb-3 align-items-center">
                        <label for="employeeId" class="col-12 col-md-3 col-form-label fw-bold">Employee ID</label>
                        <div class="col-12 col-md-9">
                            <input type="text" class="form-control" id="employeeId" value="{{ $userData['employee_id'] ?? '' }}" placeholder="Employee ID" readonly>
                        </div>
                    </div>
                    <div class="row g-2 mb-3 align-items-center">
                        <label for="userName" class="col-12 col-md-3 col-form-label fw-bold">User Name</label>
                        <div class="col-12 col-md-9">
                            <input type="text" class="form-control" id="userName" value="{{ $userData['user_name'] ?? '' }}" placeholder="User Name" readonly>
                        </div>
                    </div>
                    <div class="row g-2 mb-3 align-items-center">
                        <label for="userRole" class="col-12 col-md-3 col-form-label fw-bold">User Role</label>
                        <div class="col-12 col-md-9">
                            <input type="text" class="form-control" id="userRole" value="{{ $userData['user_role'] ?? '' }}" placeholder="User Role" readonly>
                        </div>
                    </div>
                    <div class="row g-2 mb-3 align-items-center">
                        <label for="userLocation" class="col-12 col-md-3 col-form-label fw-bold">User Location</label>
                        <div class="col-12 col-md-9">
                            <input type="text" class="form-control" id="userLocation" value="{{ $userData['user_location'] ?? '' }}" placeholder="User Location" readonly>
                        </div>
                    </div>
                    <div class="row g-2 mb-3 align-items-center">
                        <label for="userStatus" class="col-12 col-md-3 col-form-label fw-bold">Status</label>
                        <div class="col-12 col-md-9">
                            <input type="text" class="form-control" id="userStatus" value="{{ $userData['status'] }}" placeholder="User status" readonly>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab" tabindex="0">
                    <div class="card mt-2 border-0 shadow-sm">
                        <div class="card-body px-2 px-md-3">
                            <h4 class="mb-3 text-center">Change Password</h4>
                            <form id="changePasswordForm" method="POST" action="{{ route('user.changePassword') }}" autocomplete="off">
                                @csrf
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control password-toggle" id="current_password" name="current_password" required autocomplete="current-password">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1" title="Show password">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control password-toggle" id="new_password" name="new_password" required minlength="6" autocomplete="new-password">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1" title="Show password">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password_confirmation" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control password-toggle" id="new_password_confirmation" name="new_password_confirmation" required minlength="6" autocomplete="new-password">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1" title="Show password">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100" id="changePasswordBtn">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editPictureModal" tabindex="-1" aria-labelledby="editPictureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPictureModalLabel">Edit Profile Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="profilePictureForm" method="POST" action="{{ route('user.updateProfilePicture') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <label for="newProfilePicture" class="form-label fw-bold">New Profile Picture</label>
                    <input type="file" class="form-control" id="newProfilePicture" name="profile_picture" accept="image/jpeg,image/png,image/jpg,image/webp" required>
                    <img src="" alt="Selected profile preview" id="profilePicturePreview" class="user-profile-preview">
                    <small class="text-muted d-block mt-2">JPEG, PNG or WebP. Maximum 4 MB.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveProfilePictureBtn">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    const pwdForm = document.getElementById('changePasswordForm');
    const pictureForm = document.getElementById('profilePictureForm');
    const profileImg = document.getElementById('profilePictureImg');
    const pictureInput = document.getElementById('newProfilePicture');
    const picturePreview = document.getElementById('profilePicturePreview');
    const savePictureBtn = document.getElementById('saveProfilePictureBtn');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    function showToast(message, type) {
        const container = document.querySelector('.toast-container') || (function () {
            const el = document.createElement('div');
            el.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(el);
            return el;
        })();

        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body"></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', toastHtml);
        const toastEl = container.querySelector('.toast:last-child');
        toastEl.querySelector('.toast-body').textContent = message;
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
        return toast;
    }

    async function parseJsonResponse(response) {
        const data = await response.json().catch(function () {
            return {};
        });

        if (!response.ok || data.success === false) {
            const error = new Error(data.message || 'Request failed.');
            error.payload = data;
            throw error;
        }

        return data;
    }

    function resetPasswordFields() {
        if (!pwdForm) {
            return;
        }

        pwdForm.reset();
        pwdForm.querySelectorAll('.password-toggle').forEach(function (input) {
            input.value = '';
            input.setAttribute('type', 'password');
        });
        pwdForm.querySelectorAll('.toggle-password').forEach(function (button) {
            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.remove('ti-eye-off');
                icon.classList.add('ti-eye');
            }
            button.setAttribute('title', 'Show password');
        });
    }

    const settingsTabButton = document.getElementById('settings-tab');
    if (settingsTabButton) {
        settingsTabButton.addEventListener('hide.bs.tab', resetPasswordFields);
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            resetPasswordFields();
        }
    });

    window.addEventListener('pagehide', resetPasswordFields);

    document.querySelectorAll('.toggle-password').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            if (!input) {
                return;
            }

            if (input.getAttribute('type') === 'password') {
                input.setAttribute('type', 'text');
                if (icon) {
                    icon.classList.remove('ti-eye');
                    icon.classList.add('ti-eye-off');
                }
                this.setAttribute('title', 'Hide password');
            } else {
                input.setAttribute('type', 'password');
                if (icon) {
                    icon.classList.remove('ti-eye-off');
                    icon.classList.add('ti-eye');
                }
                this.setAttribute('title', 'Show password');
            }
        });
    });

    if (pictureInput && picturePreview) {
        pictureInput.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file) {
                picturePreview.src = '';
                picturePreview.classList.remove('is-visible');
                return;
            }

            picturePreview.src = URL.createObjectURL(file);
            picturePreview.classList.add('is-visible');
        });
    }

    if (pwdForm) {
        pwdForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const submitBtn = document.getElementById('changePasswordBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            fetch(pwdForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: new FormData(pwdForm)
            })
            .then(parseJsonResponse)
            .then(function (data) {
                showToast(data.message, 'success');
                resetPasswordFields();
            })
            .catch(function (error) {
                showToast(error.message || 'Error changing password', 'danger');
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            });
        });
    }

    if (pictureForm) {
        pictureForm.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!pictureInput || !pictureInput.files || !pictureInput.files[0]) {
                showToast('Please choose an image to upload.', 'danger');
                return;
            }

            if (savePictureBtn) {
                savePictureBtn.disabled = true;
                savePictureBtn.textContent = 'Uploading...';
            }

            fetch(pictureForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: new FormData(pictureForm)
            })
            .then(parseJsonResponse)
            .then(function (data) {
                const nextUrl = data.url ? (data.url + (data.url.indexOf('?') === -1 ? '?' : '&') + Date.now()) : '';
                if (profileImg && nextUrl) {
                    profileImg.src = nextUrl;
                }
                const headerAvatar = document.getElementById('headerAvatar');
                if (headerAvatar && nextUrl) {
                    headerAvatar.src = nextUrl;
                }

                const modalEl = document.getElementById('editPictureModal');
                const modal = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.hide();
                pictureForm.reset();
                if (picturePreview) {
                    picturePreview.src = '';
                    picturePreview.classList.remove('is-visible');
                }
                showToast(data.message, 'success');
            })
            .catch(function (error) {
                showToast(error.message || 'Error updating profile picture', 'danger');
            })
            .finally(function () {
                if (savePictureBtn) {
                    savePictureBtn.disabled = false;
                    savePictureBtn.textContent = 'Save changes';
                }
            });
        });
    }
});
</script>
@endsection
