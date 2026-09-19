@extends('inc.app')

@section('title', 'NEBULA | Student Profile')

@section('content')
<style nonce="{{ $cspNonce }}">
/* Validation Error Styles */
.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

.is-invalid:focus {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

/* Success Message Styles */
.success-message {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 15px 20px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    font-weight: 500;
    font-size: 14px;
    max-width: 400px;
    transform: translateX(100%);
    transition: transform 0.3s ease-in-out;
    border-left: 4px solid #fff;
}

.success-message.show {
    transform: translateX(0);
}

.success-message .success-icon {
    margin-right: 10px;
    font-size: 18px;
}

/* Error Message Styles */
.error-message {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    background: linear-gradient(135deg, #dc3545, #e74c3c);
    color: white;
    padding: 15px 20px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    font-weight: 500;
    font-size: 14px;
    max-width: 400px;
    transform: translateX(100%);
    transition: transform 0.3s ease-in-out;
    border-left: 4px solid #fff;
}

.error-message.show {
    transform: translateX(0);
}

.error-message .error-icon {
    margin-right: 10px;
    font-size: 18px;
}

.student-profile-page,
.student-profile-page .bg-white,
#profileSection,
.student-profile-page .tab-content,
.student-profile-page .tab-pane {
    min-width: 0;
    max-width: 100%;
}
.student-profile-page .bg-white {
    overflow-x: clip;
}
.student-profile-page [class*="col-"] {
    min-width: 0;
}
.student-profile-page .form-select,
.student-profile-page .nebula-select,
.student-profile-page .nebula-select-menu {
    max-width: 100%;
}
.student-profile-tabs-wrap {
    max-width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
}
.student-profile-tabs {
    flex-wrap: nowrap;
    flex: 0 0 auto;
    width: max-content;
    min-width: 100%;
    margin-bottom: 0;
    gap: 0;
}
.student-profile-tabs .nav-item {
    flex: 0 0 auto;
}
.student-profile-tabs .nav-link {
    white-space: nowrap;
}
.student-profile-tabs .nav-link .badge {
    vertical-align: middle;
}
.student-profile-page .tab-pane {
    overflow: visible;
}
.student-profile-page table {
    width: 100%;
}
.student-profile-page .table-responsive table {
    margin-bottom: 0;
}
.student-profile-status-bar,
.student-profile-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}
.reinstate-highlight {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem 0.6rem;
    border: 2px dashed #198754;
    border-radius: 12px;
    background: #e9f9ef;
}
.reinstate-highlight-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #146c43;
    max-width: 10.5rem;
    line-height: 1.2;
}
.reinstate-highlight-btn {
    font-weight: 700;
    box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.65);
    animation: reinstatePulse 1.8s ease-out infinite;
}
@keyframes reinstatePulse {
    0% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.55); }
    70% { box-shadow: 0 0 0 12px rgba(25, 135, 84, 0); }
    100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
}
.student-profile-page .payment-kpi-card h5 {
    font-size: 0.95rem;
    word-break: break-word;
}
.student-profile-page .payment-kpi-card h3 {
    font-size: 1.25rem;
    word-break: break-word;
}
.history-action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}
.specialization-cell {
    min-width: 0;
    max-width: 16rem;
}
.specialization-cell .nebula-select {
    min-width: 0;
}
.student-profile-tabs-wrap::-webkit-scrollbar {
    height: 8px;
}
.student-profile-tabs-wrap::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 8px;
}
.student-profile-tabs-wrap::-webkit-scrollbar-thumb {
    background: #b0b0b0;
    border-radius: 8px;
}
.student-profile-search .input-group > .form-control {
    min-width: 0;
}
.student-profile-search #nicSearchBtn {
    min-width: 6.5rem;
    flex: 0 0 auto;
}
@media (max-width: 767.98px) {
    .student-profile-page h2 {
        font-size: 1.35rem;
        margin-bottom: 1rem !important;
    }
    .student-profile-page .p-4 {
        padding: 1rem 0.75rem !important;
    }
    .student-profile-page #profileSection {
        padding: 0.75rem !important;
    }
    .student-profile-page .mx-3 {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    .student-profile-page .row.align-items-center > [class*="col-sm-"],
    .student-profile-page .row.mb-3 > [class*="col-sm-"] {
        flex: 0 0 100%;
        max-width: 100%;
        text-align: left !important;
        padding-left: 0;
        padding-right: 0;
    }
    .student-profile-page .offset-sm-2 {
        margin-left: 0;
    }
    .student-profile-search {
        padding: 0.75rem !important;
    }
    .student-profile-search #nicSearchBtn {
        min-width: 5.5rem;
    }
    .student-profile-tabs .nav-link {
        padding: 0.5rem 0.7rem;
        font-size: 0.875rem;
    }
    .student-profile-status-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .student-profile-status-bar .btn,
    .student-profile-actions .btn,
    .reinstate-highlight-btn,
    .student-profile-generate-btn,
    .student-profile-modal .modal-footer .btn {
        width: 100%;
        margin-left: 0 !important;
    }
    .reinstate-highlight-label {
        max-width: none;
    }
    .student-profile-modal .modal-footer {
        flex-direction: column;
        align-items: stretch;
    }
    .student-profile-page .alert.d-flex {
        flex-direction: column;
        align-items: stretch !important;
        gap: 0.75rem;
    }
    .student-profile-page .d-flex.gap-2 {
        flex-wrap: wrap;
    }
    .student-profile-page .d-flex.gap-2 .btn {
        flex: 1 1 100%;
    }
    .history-action-buttons {
        flex-direction: column;
    }
    .history-action-buttons .btn {
        width: 100%;
    }
    .success-message,
    .error-message {
        top: 12px;
        right: 12px;
        left: 12px;
        max-width: none;
        transform: translateY(-120%);
    }
    .success-message.show,
    .error-message.show {
        transform: translateY(0);
    }
}

</style>

@php
  $student = $student ?? null;
  $status = $student?->academic_status ?? 'active';
  $studentDob = $student?->birthday
    ? \Illuminate\Support\Carbon::parse($student->birthday)->format('Y-m-d')
    : '';
  $certificateUrl = function (?string $path): string {
    if (!$path) {
      return '';
    }
    $clean = ltrim(preg_replace('#^storage/#', '', $path), '/');
    if (!str_starts_with($clean, 'certificates/')) {
      $clean = 'certificates/' . $clean;
    }
    return asset('storage/' . $clean);
  };
@endphp

<div class="container-fluid px-2 px-md-3 student-profile-page">
  <div class="row justify-content-center mt-4">
    <div class="col-12 col-xl-11">
      <div class="p-4 rounded shadow w-100 bg-white">
        @if (session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <h2 class="text-center mb-4">Student Profile</h2>
        <hr style="margin-bottom:30px;">

        {{-- NIC Search --}}
        <div class="row mb-4 justify-content-center">
          <div class="col-12 col-md-10">
            <div class="p-3 rounded student-profile-search" style="background-color:#e0f1ff;">
              <form id="nicSearchForm" autocomplete="off">
                <div class="input-group">
                  <input type="text" class="form-control" id="nicInput" name="nic" placeholder="Enter NIC number" required>
                  <button class="btn btn-primary" type="submit" id="nicSearchBtn">Search</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="mt-4 rounded border p-3" id="profileSection" style="{{ $student ? '' : 'display:none;' }}">
          <input type="hidden" id="studentIdHidden" value="{{ $student?->student_id ?? '' }}">

          {{-- Tabs --}}
          <div class="student-profile-tabs-wrap">
          <ul class="nav nav-tabs student-profile-tabs" id="studentTabs">
            <li class="nav-item"><a class="nav-link active bg-primary text-white" id="personal-tab" data-bs-toggle="tab" href="#personal">Personal Info</a></li>
            <li class="nav-item"><a class="nav-link" id="parent-tab" data-bs-toggle="tab" href="#parent">Parent/Guardian Info</a></li>
            <li class="nav-item"><a class="nav-link" id="academic-tab" data-bs-toggle="tab" href="#academic">Academic</a></li>
            <li class="nav-item"><a class="nav-link" id="exams-tab" data-bs-toggle="tab" href="#exams">Exams Results</a></li>
            <li class="nav-item"><a class="nav-link" id="history-tab" data-bs-toggle="tab" href="#history">History</a></li>
            <li class="nav-item"><a class="nav-link" id="attendance-tab" data-bs-toggle="tab" href="#attendance">Attendance</a></li>
              <li class="nav-item"><a class="nav-link" id="payment-summary-tab" data-bs-toggle="tab" href="#payment-summary">Payment Summary</a></li>
            <li class="nav-item"><a class="nav-link" id="clearance-tab" data-bs-toggle="tab" href="#clearance">Clearance</a></li>
            <li class="nav-item"><a class="nav-link" id="certificates-tab" data-bs-toggle="tab" href="#certificates">Certificates</a></li>
            <li class="nav-item"><a class="nav-link" id="status-history-tab" data-bs-toggle="tab" href="#status-history">Status History <span id="statusHistoryCount" class="badge bg-danger ms-1" style="display:none;">0</span></a></li>
            <li class="nav-item"><a class="nav-link" id="other-info-tab" data-bs-toggle="tab" href="#other-info">Other Information</a></li>
          </ul>
          </div>

          <div class="tab-content mt-2">
            {{-- PERSONAL TAB --}}
            <div class="tab-pane fade show active" id="personal">
              {{-- Status + Actions --}}
              <div class="student-profile-status-bar justify-content-between mt-3 mb-3 px-2">
                <div>
                  <span class="fw-bold me-2">Academic Status:</span>
                  <span id="studentStatusBadge" class="badge {{ strtolower($status)==='terminated' ? 'bg-danger' : 'bg-success' }}">{{ strtoupper($status) }}</span>
                </div>
                <div class="student-profile-actions">
                  <button type="button" id="terminateBtn" class="btn btn-outline-danger" style="{{ strtolower($status)==='terminated' ? 'display:none;' : '' }}">
                    <i class="ti ti-user-x me-1"></i> Terminate
                  </button>
                  <div class="reinstate-highlight" id="reinstateHighlight" @if(strtolower($status)!=='terminated') hidden @endif>
                    <span class="reinstate-highlight-label">Use this button to restore the student</span>
                    <button type="button" id="reinstateBtn" class="btn btn-success reinstate-highlight-btn">
                      <i class="ti ti-user-check me-1"></i> Re-Register
                    </button>
                  </div>
                </div>
              </div>

              {{-- Profile Picture --}}
              <div class="mb-3 mt-4 text-center">
                <div class="rounded-circle overflow-hidden mx-auto mb-3" style="width:150px;height:150px;border:2px solid #ccc;">
                  <img src="{{ !empty($student?->user_photo) ? asset('storage/' . $student->user_photo) : asset('images/profile/user-1.jpg') }}" alt="Student Profile" width="150" height="150" class="rounded-circle" id="studentProfilePictureImg">
                </div>
                <input type="file" class="form-control visually-hidden" id="profilePicture" accept="image/*">
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editPictureModal" id="editPictureBtn" style="{{ $student ? '' : 'display:none;' }}">Edit Picture</button>
              </div>

              {{-- Personal Details --}}
              <div class="mb-3 row align-items-center mx-3">
                <label for="studentTitle" class="col-sm-3 col-form-label fw-bold">Title <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" id="studentTitle" value="{{ $student?->title ?? '' }}" readonly>
                </div>
              </div>
              <div class="mb-3 row align-items-center mx-3">
                <label for="studentName" class="col-sm-3 col-form-label fw-bold">Name <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" id="studentName" value="{{ $student?->full_name ?? '' }}" readonly>
                </div>
              </div>
              <div class="mb-3 row align-items-center mx-3">
                <label for="studentNIC" class="col-sm-3 col-form-label fw-bold">NIC <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" id="studentNIC" value="{{ $student?->id_value ?? '' }}" readonly>
                </div>
              </div>
              <div class="mb-3 row align-items-center mx-3">
                <label for="studentInstitute" class="col-sm-3 col-form-label fw-bold">Institute <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" id="studentInstitute" value="{{ $student?->institute_location ?? '' }}" readonly>
                </div>
              </div>
              <div class="mb-3 row align-items-center mx-3">
                <label for="studentDOB" class="col-sm-3 col-form-label fw-bold">Date of Birth <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" id="studentDOB" value="{{ $studentDob }}" readonly>
                </div>
              </div>
              <div class="mb-3 row align-items-center mx-3">
                <label for="studentGender" class="col-sm-3 col-form-label fw-bold">Gender <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" id="studentGender" value="{{ $student?->gender ?? '' }}" readonly>
                </div>
              </div>
              <div class="mb-3 row align-items-center mx-3">
                <label for="studentEmail" class="col-sm-3 col-form-label fw-bold">Email <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                  <input type="email" class="form-control" id="studentEmail" value="{{ $student?->email ?? '' }}" readonly>
                </div>
              </div>
              <div class="mb-3 row align-items-center mx-3">
                <label for="studentMobile" class="col-sm-3 col-form-label fw-bold">Mobile Phone No <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                  <input type="tel" class="form-control" id="studentMobile" value="{{ $student?->mobile_phone ?? '' }}" readonly>
                </div>
              </div>
              <div class="mb-3 row align-items-center mx-3">
                <label for="studentHomePhone" class="col-sm-3 col-form-label fw-bold">Home Phone No</label>
                <div class="col-sm-9">
                  <input type="tel" class="form-control" id="studentHomePhone" value="{{ $student?->home_phone ?? '' }}" readonly>
                </div>
              </div>
              <div class="mb-3 row align-items-center mx-3">
                <label for="studentAddress" class="col-sm-3 col-form-label fw-bold">Address <span class="text-danger">*</span></label>
                <div class="col-sm-9">
                  <textarea class="form-control" id="studentAddress" rows="2" readonly>{{ $student?->address ?? '' }}</textarea>
                </div>
              </div>
              <div class="mb-3 row align-items-center mx-3">
                <label for="studentSpecialNeeds" class="col-sm-3 col-form-label fw-bold">Special Needs</label>
                <div class="col-sm-9">
                  <textarea class="form-control" id="studentSpecialNeeds" rows="2" readonly>{{ $student?->special_needs ?? '' }}</textarea>
                </div>
              </div>
              <div class="mb-3 row align-items-center mx-3">
                <label for="studentExtraCurricular" class="col-sm-3 col-form-label fw-bold">Extra Curricular Activities</label>
                <div class="col-sm-9">
                  <textarea class="form-control" id="studentExtraCurricular" rows="2" readonly>{{ $student?->extracurricular_activities ?? '' }}</textarea>
                </div>
              </div>
              <div class="mb-3 row align-items-center mx-3">
                <label for="studentFuturePotentials" class="col-sm-3 col-form-label fw-bold">Future Potentials</label>
                <div class="col-sm-9">
                  <textarea class="form-control" id="studentFuturePotentials" rows="2" readonly>{{ $student?->future_potentials ?? '' }}</textarea>
                </div>
              </div>

              {{-- Edit Buttons --}}
              <div class="student-profile-actions mt-4 mb-3">
                <button type="button" class="btn btn-primary" id="showEditPersonalInfoBtn">Edit Personal Info</button>
                <button type="button" class="btn btn-success" id="updatePersonalInfoBtn" style="display:none;">Update Personal Info</button>
                <button type="button" class="btn btn-secondary" id="cancelEditBtn" style="display:none;">Cancel</button>
              </div>
            </div>

      {{-- PARENT TAB --}}
      <div class="tab-pane fade" id="parent">
      <!-- In the parent tab section of student_profile.blade.php -->
    <div class="mb-3 row align-items-center mx-3">
        <label for="parentName" class="col-sm-3 col-form-label fw-bold">Name <span class="text-danger">*</span></label>
        <div class="col-sm-9">
          <input type="text" class="form-control" id="parentName" value="{{ $student?->parent?->guardian_name ?? '' }}" readonly>
          <div class="invalid-feedback" id="parentNameFeedback" style="display:none;"></div>
        </div>
      </div>
            <div class="mb-3 row align-items-center mx-3">
                <label for="parentProfession" class="col-sm-3 col-form-label fw-bold">Profession</label>
        <div class="col-sm-9">
          <input type="text" class="form-control" id="parentProfession" value="{{ $student?->parent?->guardian_profession ?? '' }}" readonly>
          <div class="invalid-feedback" id="parentProfessionFeedback" style="display:none;"></div>
        </div>
            </div>
            <div class="mb-3 row align-items-center mx-3">
                <label for="parentContactNo" class="col-sm-3 col-form-label fw-bold">Contact Number <span class="text-danger">*</span></label>
        <div class="col-sm-9">
          <input type="tel" class="form-control" id="parentContactNo" value="{{ $student?->parent?->guardian_contact_number ?? '' }}" readonly>
          <div class="invalid-feedback" id="parentContactNoFeedback" style="display:none;"></div>
        </div>
            </div>
            <div class="mb-3 row align-items-center mx-3">
                <label for="parentEmail" class="col-sm-3 col-form-label fw-bold">Email</label>
        <div class="col-sm-9">
          <input type="email" class="form-control" id="parentEmail" value="{{ $student?->parent?->guardian_email ?? '' }}" readonly>
          <div class="invalid-feedback" id="parentEmailFeedback" style="display:none;"></div>
        </div>
            </div>
            <div class="mb-3 row align-items-center mx-3">
                <label for="parentAddress" class="col-sm-3 col-form-label fw-bold">Address <span class="text-danger">*</span></label>
        <div class="col-sm-9">
          <textarea class="form-control" id="parentAddress" rows="2" readonly>{{ $student?->parent?->guardian_address ?? '' }}</textarea>
          <div class="invalid-feedback" id="parentAddressFeedback" style="display:none;"></div>
        </div>
            </div>
            <div class="mb-3 row align-items-center mx-3">
                <label for="parentEmergencyContact" class="col-sm-3 col-form-label fw-bold">Emergency Contact Number <span class="text-danger">*</span></label>
        <div class="col-sm-9">
          <input type="text" class="form-control bg-danger text-white" id="parentEmergencyContact" value="{{ $student?->parent?->emergency_contact_number ?? '' }}" readonly>
          <div class="invalid-feedback" id="parentEmergencyContactFeedback" style="display:none;"></div>
        </div>
            </div>
              <div class="student-profile-actions mt-4 mb-3">
                <button type="button" class="btn btn-primary" id="showEditParentInfoBtn">Edit Parent/Guardian Info</button>
                <button type="button" class="btn btn-success" id="updateParentInfoBtn" style="display:none;">Update Parent/Guardian Info</button>
                <button type="button" class="btn btn-secondary" id="cancelEditParentBtn" style="display:none;">Cancel</button>
              </div>
            </div>

            {{-- ACADEMIC TAB (server-rendered summary, JS will also build) --}}
            <div class="tab-pane fade" id="academic">
              @php
                $ol_pending = true; $al_pending = true; $ol_exam=null; $al_exam=null;
                $ol_subjects = []; $al_subjects = [];
                if (isset($student?->exams) && !$student->exams->isEmpty()) {
                  $exam = $student->exams->first();
                  if ($exam) {
                    $ol_subjects = is_array($exam->ol_exam_subjects) ? $exam->ol_exam_subjects : json_decode($exam->ol_exam_subjects, true);
                    if (!empty($ol_subjects)) { $ol_pending=false; $ol_exam=$exam; }
                    $al_subjects = is_array($exam->al_exam_subjects) ? $exam->al_exam_subjects : json_decode($exam->al_exam_subjects, true);
                    if (!empty($al_subjects)) { $al_pending=false; $al_exam=$exam; }
                  }
                }
              @endphp

              @if ($ol_pending)
                <div class="alert alert-warning mb-3 d-flex justify-content-between align-items-center">
                  <span><strong>Pending Results:</strong> The student's O/L exam results are still pending.</span>
                  <button type="button" class="btn btn-sm btn-primary" onclick="showOLUpdateForm()">Update O/L Results</button>
                </div>
                <div id="olUpdateForm" style="display:none;" class="card mb-3 shadow-sm">
                  <div class="card-body">
                    <h5 class="card-title fw-bold">Update O/L Exam Results</h5>
                    <form id="olResultsForm">
                      <div class="row mb-3">
                        <label for="profile_ol_index_no" class="col-sm-2 col-form-label">Index No.</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control" id="profile_ol_index_no" name="ol_index_no" placeholder="XXXXXXXXXX">
                        </div>
                      </div>
                      <div class="row mb-3">
                        <label for="profile_ol_exam_type" class="col-sm-2 col-form-label">Exam Type<span class="text-danger">*</span></label>
                        <div class="col-sm-4">
                          <select class="form-select" id="profile_ol_exam_type" name="ol_exam_type" required>
                            <option value="">Select Exam Type</option>
                            <option value="Local">Local</option>
                            <option value="London Cambridge">London Cambridge</option>
                            <option value="London Edexcel">London Edexcel</option>
                            <option value="Other">Other</option>
                          </select>
                        </div>
                        <label for="profile_ol_exam_year" class="col-sm-2 col-form-label text-end">Exam Year<span class="text-danger">*</span></label>
                        <div class="col-sm-4">
                          <input type="number" class="form-control" id="profile_ol_exam_year" name="ol_exam_year" placeholder="e.g. 2020" min="1900" max="2027" required>
                        </div>
                      </div>
                      <div class="row mb-3 align-items-end">
                        <label class="col-sm-2 col-form-label">Result<span class="text-danger">*</span></label>
                        <div class="col-sm-4">
                          <select class="form-select" id="profile_ol_subject_select">
                            <option value="" selected disabled>Select a Subject</option>
                            <option value="Sinhala">Sinhala</option>
                            <option value="History">History</option>
                            <option value="Religion">Religion</option>
                            <option value="English">English</option>
                            <option value="Maths">Maths</option>
                            <option value="Science">Science</option>
                            <option value="Other">Other</option>
                          </select>
                          <input type="text" class="form-control mt-2" id="profile_ol_subject_other" placeholder="Enter subject name" style="display:none;">
                        </div>
                        <div class="col-sm-4">
                          <select class="form-select" id="profile_ol_result_select">
                            <option value="" selected disabled>Select a Result</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="S">S</option>
                            <option value="F">F</option>
                          </select>
                          <div id="profileOlResultError" class="text-danger mt-1" style="display:none;">This subject is already added.</div>
                        </div>
                        <div class="col-sm-2">
                          <button type="button" class="btn btn-primary w-100" id="profile_ol_add_btn">Add</button>
                        </div>
                      </div>
                      <div class="row mb-3">
                        <div class="col-sm-10 offset-sm-2">
                          <div class="table-responsive">
                          <table class="table table-bordered">
                            <thead class="bg-primary text-white">
                              <tr>
                                <th>O/L Subject</th>
                                <th>Result</th>
                                <th>Action</th>
                              </tr>
                            </thead>
                            <tbody id="profile_ol_table_body">
                              <!-- JS will add results here -->
                            </tbody>
                          </table>
                          </div>
                        </div>
                      </div>
                      <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">Save O/L Results</button>
                        <button type="button" class="btn btn-secondary" onclick="hideOLUpdateForm()">Cancel</button>
                      </div>
                    </form>
                  </div>
                </div>
              @else
                <div id="olExamSection">
                  <h5 class="mt-4 mb-3 fw-bold">O/L Exam Details</h5>
                  <div class="mb-3 row align-items-center mx-3">
                    <label class="col-sm-3 col-form-label fw-bold">Index No.</label>
                    <div class="col-sm-9"><input type="text" class="form-control" value="{{ $ol_exam->ol_index_no ?? '' }}" readonly></div>
                  </div>
                  <div class="mb-3 row align-items-center mx-3">
                    <label class="col-sm-3 col-form-label fw-bold">Exam Type</label>
                    <div class="col-sm-9"><input type="text" class="form-control" value="{{ $ol_exam->ol_exam_type ?? '' }}" readonly></div>
                  </div>
                  <div class="mb-3 row align-items-center mx-3">
                    <label class="col-sm-3 col-form-label fw-bold">Exam Year</label>
                    <div class="col-sm-9"><input type="text" class="form-control" value="{{ $ol_exam->ol_exam_year ?? '' }}" readonly></div>
                  </div>
                  <div class="mb-3 row align-items-center mx-3">
                    <label class="col-sm-3 col-form-label fw-bold">Subjects & Results</label>
                    <div class="col-sm-9">
                      <div class="table-responsive">
                      <table class="table table-bordered mb-0">
                        <thead class="bg-primary text-white"><tr><th>Subject</th><th>Result</th></tr></thead>
                        <tbody>
                          @foreach ($ol_subjects ?? [] as $subject)
                            <tr><td>{{ $subject['subject'] ?? '' }}</td><td>{{ $subject['result'] ?? '' }}</td></tr>
                          @endforeach
                        </tbody>
                      </table>
                      </div>
                    </div>
                  </div>
                  <div class="mb-3 row align-items-center mx-3">
                    <label class="col-sm-3 col-form-label fw-bold">O/L Certificate</label>
                    <div class="col-sm-9">
                      @if (!empty($ol_exam->ol_certificate))
                        <a href="{{ $certificateUrl($ol_exam->ol_certificate) }}" target="_blank">View Certificate</a>
                      @else
                        <span class="text-muted">Not uploaded</span>
                      @endif
                    </div>
                  </div>
                </div>
              @endif

              @if ($al_pending)
                <div class="alert alert-warning mb-3 d-flex justify-content-between align-items-center">
                  <span><strong>Pending Results:</strong> The student's A/L exam results are still pending.</span>
                  <button type="button" class="btn btn-sm btn-primary" onclick="showALUpdateForm()">Update A/L Results</button>
                </div>
                <div id="alUpdateForm" style="display:none;" class="card mb-3 shadow-sm">
                  <div class="card-body">
                    <h5 class="card-title fw-bold">Update A/L Exam Results</h5>
                    <form id="alResultsForm">
                      <div class="row mb-3">
                        <label for="profile_al_index_no" class="col-sm-2 col-form-label">Index No.</label>
                        <div class="col-sm-10">
                          <input type="text" class="form-control" id="profile_al_index_no" name="al_index_no" placeholder="XXXXXXXXXX">
                        </div>
                      </div>
                      <div class="row mb-3">
                        <label for="profile_al_exam_type" class="col-sm-2 col-form-label">Exam Type<span class="text-danger">*</span></label>
                        <div class="col-sm-4">
                          <select class="form-select" id="profile_al_exam_type" name="al_exam_type" required>
                            <option value="">Select Exam Type</option>
                            <option value="Local">Local</option>
                            <option value="London Cambridge">London Cambridge</option>
                            <option value="London Edexcel">London Edexcel</option>
                            <option value="Other">Other</option>
                          </select>
                        </div>
                        <label for="profile_al_exam_year" class="col-sm-2 col-form-label text-end">Exam Year<span class="text-danger">*</span></label>
                        <div class="col-sm-4">
                          <input type="number" class="form-control" id="profile_al_exam_year" name="al_exam_year" placeholder="e.g. 2022" min="1900" max="2027" required>
                        </div>
                      </div>
                      <div class="row mb-3">
                        <label for="profile_al_stream" class="col-sm-2 col-form-label">A/L Stream<span class="text-danger">*</span></label>
                        <div class="col-sm-10">
                          <select class="form-select" id="profile_al_stream" name="al_stream" required>
                            <option value="" selected disabled>Select an A/L Stream</option>
                            <option value="Physical Science">Physical Science</option>
                            <option value="Bio Science">Bio Science</option>
                            <option value="Commerce">Commerce</option>
                            <option value="Arts">Arts</option>
                            <option value="Technology">Technology</option>
                            <option value="Other">Other</option>
                          </select>
                        </div>
                      </div>
                      <div class="row mb-3 align-items-end">
                        <label class="col-sm-2 col-form-label">Result<span class="text-danger">*</span></label>
                        <div class="col-sm-4">
                          <select class="form-select" id="profile_al_subject_select">
                            <option value="" selected disabled>Select a Subject</option>
                          </select>
                          <input type="text" class="form-control mt-2" id="profile_al_subject_other" placeholder="Enter subject name" style="display:none;">
                        </div>
                        <div class="col-sm-4">
                          <select class="form-select" id="profile_al_result_select">
                            <option value="" selected disabled>Select a Result</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="S">S</option>
                            <option value="F">F</option>
                          </select>
                          <div id="profileAlResultError" class="text-danger mt-1" style="display:none;">This subject is already added.</div>
                        </div>
                        <div class="col-sm-2">
                          <button type="button" class="btn btn-primary w-100" id="profile_al_add_btn">Add</button>
                        </div>
                      </div>
                      <div class="row mb-3">
                        <div class="col-sm-10 offset-sm-2">
                          <div class="table-responsive">
                          <table class="table table-bordered">
                            <thead class="bg-primary text-white">
                              <tr>
                                <th>A/L Subject</th>
                                <th>Result</th>
                                <th>Action</th>
                              </tr>
                            </thead>
                            <tbody id="profile_al_table_body">
                              <!-- JS will add results here -->
                            </tbody>
                          </table>
                          </div>
                        </div>
                      </div>
                      <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">Save A/L Results</button>
                        <button type="button" class="btn btn-secondary" onclick="hideALUpdateForm()">Cancel</button>
                      </div>
                    </form>
                  </div>
                </div>
              @else
                <div id="alExamSection">
                  <hr>
                  <h5 class="mt-4 mb-3 fw-bold">A/L Exam Details</h5>
                  <div class="mb-3 row align-items-center mx-3">
                    <label class="col-sm-3 col-form-label fw-bold">Index No.</label>
                    <div class="col-sm-9"><input type="text" class="form-control" value="{{ $al_exam->al_index_no ?? '' }}" readonly></div>
                  </div>
                  <div class="mb-3 row align-items-center mx-3">
                    <label class="col-sm-3 col-form-label fw-bold">Exam Type</label>
                    <div class="col-sm-9"><input type="text" class="form-control" value="{{ $al_exam->al_exam_type ?? '' }}" readonly></div>
                  </div>
                  <div class="mb-3 row align-items-center mx-3">
                    <label class="col-sm-3 col-form-label fw-bold">Exam Year</label>
                    <div class="col-sm-9"><input type="text" class="form-control" value="{{ $al_exam->al_exam_year ?? '' }}" readonly></div>
                  </div>
                  <div class="mb-3 row align-items-center mx-3">
                    <label class="col-sm-3 col-form-label fw-bold">A/L Stream</label>
                    <div class="col-sm-9"><input type="text" class="form-control" value="{{ $al_exam->al_stream ?? '' }}" readonly></div>
                  </div>
                  <div class="mb-3 row align-items-center mx-3">
                    <label class="col-sm-3 col-form-label fw-bold">Subjects & Results</label>
                    <div class="col-sm-9">
                      <div class="table-responsive">
                      <table class="table table-bordered mb-0">
                        <thead class="bg-primary text-white"><tr><th>Subject</th><th>Result</th></tr></thead>
                        <tbody>
                          @foreach ($al_subjects ?? [] as $subject)
                            <tr><td>{{ $subject['subject'] ?? '' }}</td><td>{{ $subject['result'] ?? '' }}</td></tr>
                          @endforeach
                        </tbody>
                      </table>
                      </div>
                    </div>
                  </div>
                  <div class="mb-3 row align-items-center mx-3">
                    <label class="col-sm-3 col-form-label fw-bold">A/L Certificate</label>
                    <div class="col-sm-9">
                      @if (!empty($al_exam->al_certificate))
                        <a href="{{ $certificateUrl($al_exam->al_certificate) }}" target="_blank">View Certificate</a>
                      @else
                        <span class="text-muted">Not uploaded</span>
                      @endif
                    </div>
                  </div>
                </div>
              @endif
            </div>

            {{-- EXAMS TAB --}}
            <div class="tab-pane fade" id="exams">
              <div class="row mb-3">
                <div class="col-12 col-md-6">
                  <label for="examCourseSelect" class="form-label fw-bold">Select Course</label>
                  <select id="examCourseSelect" class="form-select">
                    <option value="">Select a course</option>
                  </select>
                </div>
                <div class="col-12 col-md-6">
                  <label for="examSemesterSelect" class="form-label fw-bold">Select Semester</label>
                  <select id="examSemesterSelect" class="form-select" disabled>
                    <option value="">Select a semester</option>
                  </select>
                </div>
              </div>
              <div id="examResultsTableWrapper" style="display:none;">
                <h5 class="fw-bold mb-3">Module Results</h5>
                <div class="table-responsive">
                <table class="table table-bordered">
                  <thead class="bg-primary text-white">
                    <tr><th>Module Name</th><th>Marks</th><th>Grade</th></tr>
                  </thead>
                  <tbody id="examResultsTableBody"></tbody>
                </table>
                </div>
              </div>
            </div>

            {{-- HISTORY TAB --}}
            <div class="tab-pane fade" id="history">
              <h5 class="fw-bold mb-3">Course Registration History</h5>
              <div class="table-responsive">
              <table class="table table-bordered">
                <thead class="bg-primary text-white">
                  <tr>
                    <th>Course</th>
                    <th>Intake</th>
                    <th>Status</th>
                    <th>Specialization</th>
                    <th>Overall Grade</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="historyTableBody"></tbody>
              </table>
              </div>
            </div>

            {{-- ATTENDANCE TAB --}}
            <div class="tab-pane fade" id="attendance">
              <div class="row mb-3">
                <div class="col-12 col-md-6">
                  <label for="attendanceCourseSelect" class="form-label fw-bold">Select Course</label>
                  <select id="attendanceCourseSelect" class="form-select"><option value="">Select a course</option></select>
                </div>
                <div class="col-12 col-md-6" id="semesterSelectContainer" style="display:none;">
                  <label for="attendanceSemesterSelect" class="form-label fw-bold">Select Semester</label>
                  <select id="attendanceSemesterSelect" class="form-select"><option value="">Select a semester</option></select>
                </div>
              </div>
              <div id="attendanceTableWrapper" style="display:none;">
                <h5 class="fw-bold mb-3">Module Attendance</h5>
                <div class="table-responsive">
                <table class="table table-bordered">
                  <thead class="bg-primary text-white">
                    <tr><th>Module Name</th><th>Total Days</th><th>Present Days</th><th>Absent Days</th><th>Attendance %</th></tr>
                  </thead>
                  <tbody id="attendanceTableBody"></tbody>
                </table>
                </div>
              </div>
            </div>

            <!-- Payment Summary Tab -->
            <div class="tab-pane fade" id="payment-summary" role="tabpanel" aria-labelledby="payment-summary-tab">
              <div class="mt-4">
                <!-- Filters -->
                <div class="mb-4">
                  <div class="row mb-3 align-items-center">
                    <label class="col-12 col-md-2 col-form-label fw-bold">Course <span class="text-danger">*</span></label>
                    <div class="col-12 col-md-10">
                      <select class="form-select" id="summary-course" required>
                        <option value="" selected disabled>Select a Course</option>
                      </select>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-12 text-center">
                      <button type="button" class="btn btn-primary student-profile-generate-btn" id="generatePaymentSummaryBtn">
                        <i class="ti ti-chart-pie me-2"></i>Generate Summary
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Payment Summary -->
                <div class="mt-4" id="paymentSummarySection" style="display:none;">
                  <h4 class="text-center mb-3">Payment Summary</h4>
                  <!-- Student Information -->
                  <div class="card mb-4">
                    <div class="card-header">
                      <h5 class="mb-0">
                        <i class="ti ti-user me-2"></i>Student Information
                      </h5>
                    </div>
                    <div class="card-body">
                      <div class="row">
                        <div class="col-md-6">
                          <p><strong>Student ID:</strong> <span id="summary-student-id"></span></p>
                          <p><strong>Student Name:</strong> <span id="summary-student-name"></span></p>
                          <p><strong>Course:</strong> <span id="summary-course-name"></span></p>
                        </div>
                        <div class="col-md-6">
                          <p><strong>Registration Date:</strong> <span id="summary-registration-date"></span></p>
                          <p><strong>Local Course Fee (LKR):</strong> <span id="summary-course-fee"></span></p>
                          <p><strong>Registration Fee (LKR):</strong> <span id="summary-registration-fee"></span></p>
                          <p><strong>Total Local + Registration Fee (LKR):</strong> <span id="summary-total-local-fee"></span></p>
                          <p><strong>Total Franchise Fee:</strong> <span id="summary-total-franchise-fee"></span></p>
                          <p><strong>Total Paid (LKR):</strong> <span id="summary-total-local-paid"></span></p>
                          <p><strong>Total Paid (Franchise):</strong> <span id="summary-total-franchise-paid"></span></p>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- Summary Cards -->
                  <div class="row mb-4">
                    <div class="col-12 col-sm-6 col-lg-3">
                      <div class="card bg-primary text-white payment-kpi-card">
                        <div class="card-body text-center">
                          <h5>Total Local + Registration (LKR)</h5>
                          <h3 id="total-local-amount">Rs. 0</h3>
                        </div>
                      </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                      <div class="card bg-success text-white payment-kpi-card">
                        <div class="card-body text-center">
                          <h5>Total Franchise</h5>
                          <h3 id="total-franchise-amount">USD 0</h3>
                        </div>
                      </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                      <div class="card bg-warning text-white payment-kpi-card">
                        <div class="card-body text-center">
                          <h5>Total Paid (LKR)</h5>
                          <h3 id="total-local-paid">Rs. 0</h3>
                        </div>
                      </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                      <div class="card bg-info text-white payment-kpi-card">
                        <div class="card-body text-center">
                          <h5>Total Paid (Franchise)</h5>
                          <h3 id="total-franchise-paid">USD 0</h3>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="row mb-4">
                    <div class="col-md-6">
                      <p><strong>Outstanding (LKR):</strong> <span id="total-local-outstanding">Rs. 0</span></p>
                    </div>
                    <div class="col-md-6">
                      <p><strong>Payment Rate:</strong> <span id="payment-rate">0%</span></p>
                    </div>
                  </div>
                  <!-- Payment Details Table -->
                  <div class="card">
                    <div class="card-header">
                      <h5 class="mb-0">
                        <i class="ti ti-list me-2"></i>Payment Details by Type
                      </h5>
                    </div>
                    <div class="card-body">
                      <!-- Local Course Fee Table -->
                      <div class="mb-4">
                        <h6 class="text-primary mb-3">
                          <i class="ti ti-book me-2"></i>Local Course Fee
                        </h6>
                        <div class="table-responsive">
                          <table class="table table-bordered table-sm">
                            <thead class="table-light">
                              <tr>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Outstanding</th>
                                <th>Paid Date</th>
                                <th>Due Date</th>
                                <th>Receipt No</th>
                                <th>Uploaded Receipt</th>
                                <th>Installments</th>
                              </tr>
                            </thead>
                            <tbody id="courseFeeTableBody">
                              <tr><td colspan="8" class="text-center text-muted">No course fee data available</td></tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                      <!-- Franchise Payments Table -->
                      <div class="mb-4">
                        <h6 class="text-success mb-3">
                          <i class="ti ti-building me-2"></i>Franchise Payments
                        </h6>
                        <div class="table-responsive">
                          <table class="table table-bordered table-sm">
                            <thead class="table-light">
                              <tr>
                                <th>Amount</th>
                                <th>Currency</th>
                                <th>SSCL Tax</th>
                                <th>Bank Charges</th>
                                <th>Total (LKR)</th>
                                <th>Paid Amount</th>
                                <th>Outstanding</th>
                                <th>Paid Date</th>
                                <th>Due Date</th>
                                <th>Receipt No</th>
                                <th>Uploaded Receipt</th>
                                <th>Installments</th>
                              </tr>
                            </thead>
                            <tbody id="franchiseFeeTableBody">
                              <tr><td colspan="12" class="text-center text-muted">No franchise fee data available</td></tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                      <!-- Registration Fee Table -->
                      <div class="mb-4">
                        <h6 class="text-info mb-3">
                          <i class="ti ti-file-text me-2"></i>Registration Fee
                        </h6>
                        <div class="table-responsive">
                          <table class="table table-bordered table-sm">
                            <thead class="table-light">
                              <tr>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Outstanding</th>
                                <th>Paid Date</th>
                                <th>Due Date</th>
                                <th>Receipt No</th>
                                <th>Uploaded Receipt</th>
                                <th>Installments</th>
                              </tr>
                            </thead>
                            <tbody id="registrationFeeTableBody">
                              <tr><td colspan="8" class="text-center text-muted">No registration fee data available</td></tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                      <!-- SLT Loan Receivables -->
                      <div class="mb-4" id="sltLoanReceivablesSection" style="display:none;">
                        <h6 class="text-danger mb-3">
                          <i class="ti ti-building-bank me-2"></i>SLT Loan Receivables
                        </h6>
                        <div class="row mb-3">
                          <div class="col-md-4">
                            <p class="mb-1"><strong>SLT Loan Amount:</strong> <span id="slt-summary-loan-amount">-</span></p>
                          </div>
                          <div class="col-md-4">
                            <p class="mb-1"><strong>Loan Taken Years:</strong> <span id="slt-summary-loan-years">-</span></p>
                          </div>
                          <div class="col-md-4">
                            <p class="mb-1"><strong>No of Loan Installments:</strong> <span id="slt-summary-installment-count">-</span></p>
                          </div>
                          <div class="col-md-4">
                            <p class="mb-1"><strong>Apply From Installment:</strong> <span id="slt-summary-start-installment">-</span></p>
                          </div>
                          <div class="col-md-4">
                            <p class="mb-1"><strong>Monthly Receivable:</strong> <span id="slt-summary-monthly-receivable">-</span></p>
                          </div>
                          <div class="col-md-4">
                            <p class="mb-1"><strong>Last Effective Date:</strong> <span id="slt-summary-effective-date">-</span></p>
                          </div>
                        </div>
                        <div class="table-responsive">
                          <table class="table table-bordered table-sm">
                            <thead class="table-light">
                              <tr>
                                <th>Loan Installment #</th>
                                <th>Receivable Amount (Monthly)</th>
                                <th>Status</th>
                                <th>Payment Effective Date</th>
                                <th>Recorded At</th>
                              </tr>
                            </thead>
                            <tbody id="sltLoanReceivablesTableBody">
                              <tr><td colspan="5" class="text-center text-muted">No SLT loan receivable data available</td></tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                      <!-- Hostel Fee Table -->
                      <div class="mb-4">
                        <h6 class="text-warning mb-3">
                          <i class="ti ti-home me-2"></i>Hostel Fee
                        </h6>
                        <div class="table-responsive">
                          <table class="table table-bordered table-sm">
                            <thead class="table-light">
                              <tr>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Outstanding</th>
                                <th>Paid Date</th>
                                <th>Due Date</th>
                                <th>Receipt No</th>
                                <th>Uploaded Receipt</th>
                                <th>Installments</th>
                              </tr>
                            </thead>
                            <tbody id="hostelFeeTableBody">
                              <tr><td colspan="8" class="text-center text-muted">No hostel fee data available</td></tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                      <!-- Library Fee Table -->
                      <div class="mb-4">
                        <h6 class="text-secondary mb-3">
                          <i class="ti ti-library me-2"></i>Library Fee
                        </h6>
                        <div class="table-responsive">
                          <table class="table table-bordered table-sm">
                            <thead class="table-light">
                              <tr>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Outstanding</th>
                                <th>Paid Date</th>
                                <th>Due Date</th>
                                <th>Receipt No</th>
                                <th>Uploaded Receipt</th>
                                <th>Installments</th>
                              </tr>
                            </thead>
                            <tbody id="libraryFeeTableBody">
                              <tr><td colspan="8" class="text-center text-muted">No library fee data available</td></tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                      <!-- Other Fees Table -->
                      <div class="mb-4">
                        <h6 class="text-dark mb-3">
                          <i class="ti ti-plus me-2"></i>Other
                        </h6>
                        <div class="table-responsive">
                          <table class="table table-bordered table-sm">
                            <thead class="table-light">
                              <tr>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Outstanding</th>
                                <th>Paid Date</th>
                                <th>Due Date</th>
                                <th>Receipt No</th>
                                <th>Uploaded Receipt</th>
                                <th>Installments</th>
                              </tr>
                            </thead>
                            <tbody id="otherFeeTableBody">
                              <tr><td colspan="8" class="text-center text-muted">No other fee data available</td></tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {{-- CLEARANCE TAB --}}
            <div class="tab-pane fade" id="clearance">
              <h5 class="fw-bold mb-3">Student Clearance Status</h5>
              <div class="table-responsive">
              <table class="table table-bordered">
                <thead class="bg-primary text-white">
                  <tr><th>Clearance Type</th><th>Status</th><th>Approved Date</th><th>Remarks</th><th>Uploaded Document</th></tr>
                </thead>
                <tbody id="clearanceTableBody"></tbody>
              </table>
              </div>
            </div>

            {{-- CERTIFICATES TAB --}}
            <div class="tab-pane fade" id="certificates">
              <h5 class="mt-4 mb-3 fw-bold">Certificates</h5>
              <div class="mb-3 row align-items-center mx-3">
                <label class="col-sm-3 col-form-label fw-bold">O/L Certificate</label>
                <div class="col-sm-9" id="olCertificate">
                  <span class="text-muted">Not uploaded</span>
                </div>
              </div>
              <div class="mb-3 row align-items-center mx-3">
                <label class="col-sm-3 col-form-label fw-bold">A/L Certificate</label>
                <div class="col-sm-9" id="alCertificate">
                  <span class="text-muted">Not uploaded</span>
                </div>
              </div>
              <div class="mb-3 row align-items-center mx-3">
                <label class="col-sm-3 col-form-label fw-bold">Disciplinary Issue Document</label>
                <div class="col-sm-9" id="disciplinaryDocument">
                  <span class="text-muted">Not uploaded</span>
                </div>
              </div>
            </div>
            
            {{-- Hidden file inputs for certificate uploads --}}
            <input type="file" id="olCertificateInput" accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
            <input type="file" id="alCertificateInput" accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
            
            {{-- STATUS HISTORY TAB --}}
            <div class="tab-pane fade" id="status-history">
              <h5 class="mt-4 mb-3 fw-bold">Status / Termination History</h5>
              <div class="table-responsive">
                <table class="table table-bordered">
                  <thead class="bg-primary text-white">
                    <tr><th>#</th><th>From Status</th><th>To Status</th><th>Reason</th><th>Document</th><th>Changed By</th><th>Date</th></tr>
                  </thead>
                  <tbody id="statusHistoryTableBody">
                    <tr><td colspan="7" class="text-center text-muted">No status history available.</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
            
            
            {{-- OTHER INFO TAB --}}
            <div class="tab-pane fade" id="other-info">
              <h5 class="mt-4 mb-3 fw-bold">Other Information</h5>
              @if($student?->other_information)
                <div class="mb-3 row align-items-center mx-3">
                  <label class="col-sm-3 col-form-label fw-bold">Disciplinary Issues</label>
                  <div class="col-sm-9">
                    <textarea class="form-control" rows="2" readonly>{{ $student?->other_information?->disciplinary_issues ?? '' }}</textarea>
                  </div>
                </div>
                <div class="mb-3 row align-items-center mx-3">
                  <label class="col-sm-3 col-form-label fw-bold">Disciplinary Document</label>
                  <div class="col-sm-9">
                    @if($student?->other_information?->disciplinary_issue_document)
                      <a href="{{ asset('storage/' . $student->other_information->disciplinary_issue_document) }}" target="_blank">View Document</a>
                    @else
                      <span class="text-muted">Not uploaded</span>
                    @endif
                  </div>
                </div>
                <div class="mb-3 row align-items-center mx-3">
                  <label class="col-sm-3 col-form-label fw-bold">Institute</label>
                  <div class="col-sm-9"><input type="text" class="form-control" readonly value="{{ $student?->other_information?->institute ?? '' }}"></div>
                </div>
                <div class="mb-3 row align-items-center mx-3">
                  <label class="col-sm-3 col-form-label fw-bold">Field of Study</label>
                  <div class="col-sm-9"><input type="text" class="form-control" readonly value="{{ $student?->other_information?->field_of_study ?? '' }}"></div>
                </div>
                <div class="mb-3 row align-items-center mx-3">
                  <label class="col-sm-3 col-form-label fw-bold">Job Title</label>
                  <div class="col-sm-9"><input type="text" class="form-control" readonly value="{{ $student?->other_information?->job_title ?? '' }}"></div>
                </div>
                <div class="mb-3 row align-items-center mx-3">
                  <label class="col-sm-3 col-form-label fw-bold">Workplace</label>
                  <div class="col-sm-9"><input type="text" class="form-control" readonly value="{{ $student?->other_information?->workplace ?? '' }}"></div>
                </div>
                <div class="mb-3 row align-items-center mx-3">
                  <label class="col-sm-3 col-form-label fw-bold">Other Information</label>
                  <div class="col-sm-9">
                    <textarea class="form-control" rows="2" readonly>{{ $student?->other_information?->other_information ?? '' }}</textarea>
                  </div>
                </div>
              @else
                <div class="alert alert-warning">No other information found for this student.</div>
              @endif
            </div>
          </div>
        </div> {{-- /#profileSection --}}
      </div>
    </div>
  </div>
</div>

<script nonce="{{ $cspNonce }}">
// ---------- Notifications ----------
function escapeHtml(value){
  return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
}
function certificateUrl(path){
  if (!path) return '';
  const clean = String(path).replace(/^\/+/, '').replace(/^storage\//, '');
  if (clean.startsWith('certificates/')) {
    return '/storage/' + clean;
  }
  return '/storage/certificates/' + clean;
}

function showSuccessMessage(message){
  document.querySelectorAll('.success-message,.error-message').forEach(m=>m.remove());
  const n=document.createElement('div'); n.className='success-message';
  n.innerHTML=`<i class="ti ti-check-circle success-icon"></i>${String(message ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]))}`;
  document.body.appendChild(n); setTimeout(()=>n.classList.add('show'),100);
  setTimeout(()=>{n.classList.remove('show'); setTimeout(()=>n.remove(),300)},4000);
}
function showErrorMessage(message){
  document.querySelectorAll('.success-message,.error-message').forEach(m=>m.remove());
  const n=document.createElement('div'); n.className='error-message';
  n.innerHTML=`<i class="ti ti-alert-circle error-icon"></i>${String(message ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]))}`;
  document.body.appendChild(n); setTimeout(()=>n.classList.add('show'),100);
  setTimeout(()=>{n.classList.remove('show'); setTimeout(()=>n.remove(),300)},5000);
}

// ---------- Helper: status UI ----------
// Add these functions at the top with your other helper functions
function isValidPhone(phone) {
    // Allows formats like: +94771234567, 0771234567, 771234567
    return /^(?:\+94|0)?[0-9]{9}$/.test(phone.replace(/\s/g, ''));
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// ---------- O/L and A/L Results Update Functions ----------
window.showOLUpdateForm = function() {
  const form = document.getElementById('olUpdateForm');
  if (!form) return;
  form.style.display = 'block';
  const alertEl = form.previousElementSibling;
  if (alertEl && alertEl.classList.contains('alert-warning')) alertEl.style.display = 'none';
};

window.hideOLUpdateForm = function() {
  const form = document.getElementById('olUpdateForm');
  if (!form) return;
  form.style.display = 'none';
  const alertEl = form.previousElementSibling;
  if (alertEl && alertEl.classList.contains('alert-warning')) alertEl.style.display = 'flex';
  const resultsForm = document.getElementById('olResultsForm');
  if (resultsForm) resultsForm.reset();
  const tableBody = document.getElementById('profile_ol_table_body');
  if (tableBody) tableBody.innerHTML = '';
};

window.showALUpdateForm = function() {
  const form = document.getElementById('alUpdateForm');
  if (!form) return;
  form.style.display = 'block';
  const alertEl = form.previousElementSibling;
  if (alertEl && alertEl.classList.contains('alert-warning')) alertEl.style.display = 'none';
};

window.hideALUpdateForm = function() {
  const form = document.getElementById('alUpdateForm');
  if (!form) return;
  form.style.display = 'none';
  const alertEl = form.previousElementSibling;
  if (alertEl && alertEl.classList.contains('alert-warning')) alertEl.style.display = 'flex';
  const resultsForm = document.getElementById('alResultsForm');
  if (resultsForm) resultsForm.reset();
  const tableBody = document.getElementById('profile_al_table_body');
  if (tableBody) tableBody.innerHTML = '';
};

function reloadStudentProfile() {
  const nic = ($('#nicInput').val() || $('#studentNIC').val() || '').trim();
  if (nic) {
    $('#nicInput').val(nic);
    $('#nicSearchForm').trigger('submit');
  } else {
    window.location.reload();
  }
}

// A/L Stream Subjects Mapping
const profileAlStreamSubjects = {
  'Physical Science': ['Combined Maths', 'Chemistry', 'Physics', 'English', 'Other'],
  'Bio Science': ['Biology', 'Physics', 'Chemistry', 'English', 'Other'],
  'Arts': ['Sinhala', 'Political Science', 'English', 'Other'],
  'Commerce': ['Economics', 'Business Studies', 'Accounting', 'English', 'Other'],
  'Technology': ['Science for Technology', 'Bio System Technology', 'Engineering Technology', 'ICT', 'English', 'Other'],
  'Other': ['English', 'Other']
};

// Handle O/L subject "Other" option
$(document).on('change', '#profile_ol_subject_select', function() {
  if ($(this).val() === 'Other') {
    $('#profile_ol_subject_other').show();
  } else {
    $('#profile_ol_subject_other').hide();
  }
});

// Handle A/L stream change to populate subjects
$(document).on('change', '#profile_al_stream', function() {
  const stream = $(this).val();
  const subjects = profileAlStreamSubjects[stream] || profileAlStreamSubjects['Other'];
  const $select = $('#profile_al_subject_select');
  $select.html('<option value=\"\" selected disabled>Select a Subject</option>');
  subjects.forEach(function(subject) {
    $select.append($('<option>').val(subject).text(subject));
  });
  $('#profile_al_subject_other').hide();
});

// Handle A/L subject "Other" option
$(document).on('change', '#profile_al_subject_select', function() {
  if ($(this).val() === 'Other') {
    $('#profile_al_subject_other').show();
  } else {
    $('#profile_al_subject_other').hide();
  }
});

// Add O/L subject to table
$(document).on('click', '#profile_ol_add_btn', function() {
  const subjectSelect = $('#profile_ol_subject_select');
  const subjectOther = $('#profile_ol_subject_other');
  const resultSelect = $('#profile_ol_result_select');
  const errorDiv = $('#profileOlResultError');
  const tableBody = $('#profile_ol_table_body');
  
  let subject = subjectSelect.val();
  if (subject === 'Other') {
    subject = subjectOther.val().trim();
  }
  const result = resultSelect.val();
  
  if (!subject || !result) {
    showErrorMessage('Please select both subject and result.');
    return;
  }
  
  // Check for duplicates
  let exists = false;
  tableBody.find('tr').each(function() {
    if ($(this).find('td:first').text() === subject) {
      exists = true;
    }
  });
  
  if (exists) {
    errorDiv.show();
    return;
  }
  
  errorDiv.hide();
  
  // Add row
  const row = $('<tr>').html(`
    <td>${subject}</td>
    <td>${result}</td>
    <td><button type=\"button\" class=\"btn btn-danger btn-sm remove-ol-btn\">Remove</button></td>
  `);
  tableBody.append(row);
  
  // Reset inputs
  subjectSelect.val('');
  resultSelect.val('');
  subjectOther.val('').hide();
});

// Remove O/L subject from table
$(document).on('click', '.remove-ol-btn', function() {
  $(this).closest('tr').remove();
  $('#profileOlResultError').hide();
});

// Add A/L subject to table
$(document).on('click', '#profile_al_add_btn', function() {
  const subjectSelect = $('#profile_al_subject_select');
  const subjectOther = $('#profile_al_subject_other');
  const resultSelect = $('#profile_al_result_select');
  const errorDiv = $('#profileAlResultError');
  const tableBody = $('#profile_al_table_body');
  
  let subject = subjectSelect.val();
  if (subject === 'Other') {
    subject = subjectOther.val().trim();
  }
  const result = resultSelect.val();
  
  if (!subject || !result) {
    showErrorMessage('Please select both subject and result.');
    return;
  }
  
  // Check for duplicates
  let exists = false;
  tableBody.find('tr').each(function() {
    if ($(this).find('td:first').text() === subject) {
      exists = true;
    }
  });
  
  if (exists) {
    errorDiv.show();
    return;
  }
  
  errorDiv.hide();
  
  // Add row
  const row = $('<tr>').html(`
    <td>${subject}</td>
    <td>${result}</td>
    <td><button type=\"button\" class=\"btn btn-danger btn-sm remove-al-btn\">Remove</button></td>
  `);
  tableBody.append(row);
  
  // Reset inputs
  subjectSelect.val('');
  resultSelect.val('');
  subjectOther.val('').hide();
});

// Remove A/L subject from table
$(document).on('click', '.remove-al-btn', function() {
  $(this).closest('tr').remove();
  $('#profileAlResultError').hide();
});

// Update the updateParentInfoBtn click handler
$('#updateParentInfoBtn').on('click', function() {
    const studentId = $('#studentIdHidden').val();
    if (!studentId) {
        showErrorMessage('No student selected.');
        return;
    }

  // Clear previous validation states and inline feedback
  $('.is-invalid').removeClass('is-invalid');
  $('.invalid-feedback').text('').hide();

    // Get field values (allow empty for optional fields)
    const fields = {
        'guardian_name': $('#parentName').val().trim(),
        'guardian_profession': $('#parentProfession').val().trim(),
        'guardian_contact_number': $('#parentContactNo').val().trim(),
        'guardian_email': $('#parentEmail').val().trim(),
        'guardian_address': $('#parentAddress').val().trim(),
        'emergency_contact_number': $('#parentEmergencyContact').val().trim()
    };

    let hasError = false;
    const errors = [];

  // Validate ONLY required fields (name, contact, address, emergency contact)
  const requiredFields = {
    'guardian_name': 'Guardian Name',
    'guardian_contact_number': 'Contact Number',
    'guardian_address': 'Address',
    'emergency_contact_number': 'Emergency Contact Number'
  };

  Object.entries(requiredFields).forEach(([key, label]) => {
    if (!fields[key]) {
      const idKey = key.split('_').slice(1).map((word, i) => i === 0 ? word.charAt(0).toUpperCase() + word.slice(1) : word.charAt(0).toUpperCase() + word.slice(1)).join('');
      const $field = $(`#parent${idKey}`);
      const feedbackSelector = `#${$field.attr('id')}Feedback`;
      $field.addClass('is-invalid');
      $(feedbackSelector).text('This field is required.').show();
      hasError = true;
      errors.push(label);
    }
  });

    // Validate phone numbers (only if provided)
  if (fields.guardian_contact_number && !isValidPhone(fields.guardian_contact_number)) {
    $('#parentContactNo').addClass('is-invalid');
    $('#parentContactNoFeedback').text('Invalid contact number format.').show();
    hasError = true;
    errors.push('Invalid Contact Number format');
  }
  if (fields.emergency_contact_number && !isValidPhone(fields.emergency_contact_number)) {
    $('#parentEmergencyContact').addClass('is-invalid');
    $('#parentEmergencyContactFeedback').text('Invalid emergency contact number format.').show();
    hasError = true;
    errors.push('Invalid Emergency Contact Number format');
  }

    // Validate email (only if provided and not empty)
    if (fields.guardian_email && !isValidEmail(fields.guardian_email)) {
        $('#parentEmail').addClass('is-invalid');
        $('#parentEmailFeedback').text('Invalid email format.').show();
        hasError = true;
        errors.push('Invalid Email format');
    }

  if (hasError) {
    showErrorMessage('Please correct the following errors: ' + errors.join(', '));
    return;
  }

    // Proceed with update if validation passes
    const data = {
        student_id: studentId,
        ...fields,
        _token: '{{ csrf_token() }}'
    };

  $.post("{{ route('student_management.update.parent.info') }}", data, function(resp) {
    if (resp.success) {
      // Clear any inline errors
      $('.invalid-feedback').text('').hide();
      $('.is-invalid').removeClass('is-invalid');
      showSuccessMessage('Parent/Guardian information updated successfully!');
      $('#cancelEditParentBtn').click();
            
      // Update the display values
      Object.entries(fields).forEach(([key, value]) => {
        const idKey = key.split('_').slice(1).map((word, i) => i === 0 ? word.charAt(0).toUpperCase() + word.slice(1) : word.charAt(0).toUpperCase() + word.slice(1)).join('');
        const $field = $(`#parent${idKey}`);
        $field.val(value);
      });
    } else {
      showErrorMessage(resp.message || 'Failed to update parent/guardian information.');
    }
  }).fail(function(xhr) {
    if (xhr && xhr.status === 422) {
      const errors = (xhr.responseJSON && xhr.responseJSON.errors) ? xhr.responseJSON.errors : {};
      const mapping = {
        'guardian_name':'#parentName',
        'guardian_profession':'#parentProfession',
        'guardian_contact_number':'#parentContactNo',
        'guardian_email':'#parentEmail',
        'guardian_address':'#parentAddress',
        'emergency_contact_number':'#parentEmergencyContact'
      };
      Object.keys(errors).forEach(function(field){
        const sel = mapping[field] || ('#' + field);
        const $el = $(sel);
        if ($el.length) {
          $el.addClass('is-invalid');
          const feed = '#' + $el.attr('id') + 'Feedback';
          $(feed).text(errors[field][0]).show();
        }
      });
      showErrorMessage('Please correct the highlighted fields.');
    } else {
      showErrorMessage('An error occurred while updating parent/guardian information.');
    }
  });
});

function setStatusUI(status){
  const isTerminated=(status||'').toString().toLowerCase()==='terminated';
  const badge=$('#studentStatusBadge');
  badge.text((status||'active').toUpperCase());
  badge.toggleClass('bg-danger',isTerminated).toggleClass('bg-success',!isTerminated);
  $('#terminateBtn').toggle(!isTerminated);
  $('#reinstateHighlight').prop('hidden', !isTerminated);

  // lock personal edit if terminated
  $('#showEditPersonalInfoBtn').prop('disabled', isTerminated);
}

// ---------- Document Ready ----------
$(function(){
  // If server provided $student, set initial status badge
  setStatusUI('{{ $status }}');

  // NIC Search
  $('#nicSearchForm').on('submit', function(e){
    e.preventDefault();
    const nic=$('#nicInput').val().trim();
    if(!nic){
      showErrorMessage('Please enter a NIC number.');
      return;
    }
    $.ajax({
      url:'/api/student-details-by-nic',
      method:'GET',
      data:{nic:nic},
      success:function(res){
        if(res.success && res.student){
          populateStudentProfile(res.student);
          $('#studentIdHidden').val(res.student.student_id);
          $('#profileSection').show();
          const personalTabEl = document.getElementById('personal-tab');
          if (personalTabEl && window.bootstrap?.Tab) {
            bootstrap.Tab.getOrCreateInstance(personalTabEl).show();
          }
          setStatusUI(res.student.academic_status || 'active');
          fetchRegisteredCourses(); // for Exams tab
        }else{
          $('#profileSection').hide();
          $('#editPictureBtn').hide();
          showErrorMessage(res.message || 'No student profile found for this NIC.');
        }
      },
      error:function(xhr){
        $('#profileSection').hide();
        $('#editPictureBtn').hide();
        showErrorMessage(xhr.responseJSON?.message || 'No student profile found for this NIC.');
      }
    });
  });

  // ----- populate profile (builds Academic + Other Info + fills fields) -----
  window.populateStudentProfile = function(student){
    // PERSONAL
    $('#studentIdHidden').val(student.student_id || '');
    $('#studentTitle').val(student.title || '');
    $('#studentName').val(student.full_name || '');
    $('#studentNIC').val(student.id_value || '');
    $('#studentIndexNo').val(student.registration_id || '');
    $('#studentInstitute').val(student.institute_location || '');
    if (student.birthday) {
      const raw = String(student.birthday).split('T')[0];
      $('#studentDOB').val(window.toLocalDateString ? window.toLocalDateString(raw) : raw);
    } else {
      $('#studentDOB').val('');
    }
    $('#studentGender').val(student.gender || '');
    $('#studentEmail').val(student.email || '');
    $('#studentMobile').val(student.mobile_phone || '');
    $('#studentHomePhone').val(student.home_phone || '');
    $('#studentEmergencyContact').val(student.emergency_contact_number || '');
    $('#studentAddress').val(student.address || '');
    $('#studentSpecialNeeds').val(student.special_needs || '');
    $('#studentExtraCurricular').val(student.extracurricular_activities || '');
    $('#studentFuturePotentials').val(student.future_potentials || '');
    setStatusUI(student.academic_status || 'active');
    
    // Show edit picture button and update profile image
    $('#editPictureBtn').show();
    updateStudentProfileImage(student.user_photo);

    // PARENT
    const parent = student.parent || student.parent_guardian || {};
    $('#parentName').val(parent.guardian_name || '');
    $('#parentProfession').val(parent.guardian_profession || '');
    $('#parentContactNo').val(parent.guardian_contact_number || '');
    $('#parentEmail').val(parent.guardian_email || '');
    $('#parentAddress').val(parent.guardian_address || '');
    $('#parentEmergencyContact').val(parent.emergency_contact_number || '');

    // Academic (client-rendered summary)
    const $academic = $('#academic'); $academic.empty();
    let ol_exam=null, al_exam=null, ol_pending=true, al_pending=true;
    if (student.exams && student.exams.length){
      student.exams.forEach(exam=>{
        let ols = typeof exam.ol_exam_subjects==='string' ? (JSON.parse(exam.ol_exam_subjects||'[]')) : (exam.ol_exam_subjects||[]);
        let als = typeof exam.al_exam_subjects==='string' ? (JSON.parse(exam.al_exam_subjects||'[]')) : (exam.al_exam_subjects||[]);
        if(ols && ols.length){ ol_exam=exam; ol_pending=false; }
        if(als && als.length){ al_exam=exam; al_pending=false; }
      });
    }
    if(ol_pending){
      $academic.append(`
        <div class="alert alert-warning mb-3 d-flex justify-content-between align-items-center">
          <span><strong>Pending Results:</strong> The student's O/L exam results are still pending.</span>
          <button type="button" class="btn btn-sm btn-primary" onclick="showOLUpdateForm()">Update O/L Results</button>
        </div>
        <div id="olUpdateForm" style="display:none;" class="card mb-3 shadow-sm">
          <div class="card-body">
            <h5 class="card-title fw-bold">Update O/L Exam Results</h5>
            <form id="olResultsForm">
              <div class="row mb-3">
                <label for="profile_ol_index_no" class="col-sm-2 col-form-label">Index No.</label>
                <div class="col-sm-10">
                  <input type="text" class="form-control" id="profile_ol_index_no" name="ol_index_no" placeholder="XXXXXXXXXX">
                </div>
              </div>
              <div class="row mb-3">
                <label for="profile_ol_exam_type" class="col-sm-2 col-form-label">Exam Type<span class="text-danger">*</span></label>
                <div class="col-sm-4">
                  <select class="form-select" id="profile_ol_exam_type" name="ol_exam_type" required>
                    <option value="">Select Exam Type</option>
                    <option value="Local">Local</option>
                    <option value="London Cambridge">London Cambridge</option>
                    <option value="London Edexcel">London Edexcel</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <label for="profile_ol_exam_year" class="col-sm-2 col-form-label text-end">Exam Year<span class="text-danger">*</span></label>
                <div class="col-sm-4">
                  <input type="number" class="form-control" id="profile_ol_exam_year" name="ol_exam_year" placeholder="e.g. 2020" min="1900" max="2027" required>
                </div>
              </div>
              <div class="row mb-3 align-items-end">
                <label class="col-sm-2 col-form-label">Result<span class="text-danger">*</span></label>
                <div class="col-sm-4">
                  <select class="form-select" id="profile_ol_subject_select">
                    <option value="" selected disabled>Select a Subject</option>
                    <option value="Sinhala">Sinhala</option>
                    <option value="History">History</option>
                    <option value="Religion">Religion</option>
                    <option value="English">English</option>
                    <option value="Maths">Maths</option>
                    <option value="Science">Science</option>
                    <option value="Other">Other</option>
                  </select>
                  <input type="text" class="form-control mt-2" id="profile_ol_subject_other" placeholder="Enter subject name" style="display:none;">
                </div>
                <div class="col-sm-4">
                  <select class="form-select" id="profile_ol_result_select">
                    <option value="" selected disabled>Select a Result</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                    <option value="S">S</option>
                    <option value="F">F</option>
                  </select>
                  <div id="profileOlResultError" class="text-danger mt-1" style="display:none;">This subject is already added.</div>
                </div>
                <div class="col-sm-2">
                  <button type="button" class="btn btn-primary w-100" id="profile_ol_add_btn">Add</button>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-sm-10 offset-sm-2">
                  <table class="table table-bordered">
                    <thead class="bg-primary text-white">
                      <tr>
                        <th>O/L Subject</th>
                        <th>Result</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody id="profile_ol_table_body">
                      <!-- JS will add results here -->
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">Save O/L Results</button>
                <button type="button" class="btn btn-secondary" onclick="hideOLUpdateForm()">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      `);
    }else if(ol_exam){
      let rows=''; (typeof ol_exam.ol_exam_subjects==='string' ? JSON.parse(ol_exam.ol_exam_subjects||'[]') : (ol_exam.ol_exam_subjects||[])).forEach(s=>{ rows+=`<tr><td>${s.subject||''}</td><td>${s.result||''}</td></tr>`; });
      $academic.append(`
        <div id="olExamSection">
          <h5 class="mt-4 mb-3 fw-bold">O/L Exam Details</h5>
          <div class="mb-3 row align-items-center mx-3"><label class="col-sm-3 col-form-label fw-bold">Index No.</label><div class="col-sm-9"><input class="form-control" value="${ol_exam.ol_index_no||''}" readonly></div></div>
          <div class="mb-3 row align-items-center mx-3"><label class="col-sm-3 col-form-label fw-bold">Exam Type</label><div class="col-sm-9"><input class="form-control" value="${ol_exam.ol_exam_type||''}" readonly></div></div>
          <div class="mb-3 row align-items-center mx-3"><label class="col-sm-3 col-form-label fw-bold">Exam Year</label><div class="col-sm-9"><input class="form-control" value="${ol_exam.ol_exam_year||''}" readonly></div></div>
          <div class="mb-3 row align-items-center mx-3">
            <label class="col-sm-3 col-form-label fw-bold">Subjects & Results</label>
            <div class="col-sm-9"><table class="table table-bordered mb-0"><thead class="bg-primary text-white"><tr><th>Subject</th><th>Result</th></tr></thead><tbody>${rows}</tbody></table></div>
          </div>
          <div class="mb-3 row align-items-center mx-3">
            <label class="col-sm-3 col-form-label fw-bold">O/L Certificate</label>
            <div class="col-sm-9">${ol_exam.ol_certificate?`<a href="${certificateUrl(ol_exam.ol_certificate)}" target="_blank">View Certificate</a>`:'<span class="text-muted">Not uploaded</span>'}</div>
          </div>
        </div>`);
    }
    if(al_pending){
      $academic.append(`
        <div class="alert alert-warning mb-3 d-flex justify-content-between align-items-center">
          <span><strong>Pending Results:</strong> The student's A/L exam results are still pending.</span>
          <button type="button" class="btn btn-sm btn-primary" onclick="showALUpdateForm()">Update A/L Results</button>
        </div>
        <div id="alUpdateForm" style="display:none;" class="card mb-3 shadow-sm">
          <div class="card-body">
            <h5 class="card-title fw-bold">Update A/L Exam Results</h5>
            <form id="alResultsForm">
              <div class="row mb-3">
                <label for="profile_al_index_no" class="col-sm-2 col-form-label">Index No.</label>
                <div class="col-sm-10">
                  <input type="text" class="form-control" id="profile_al_index_no" name="al_index_no" placeholder="XXXXXXXXXX">
                </div>
              </div>
              <div class="row mb-3">
                <label for="profile_al_exam_type" class="col-sm-2 col-form-label">Exam Type<span class="text-danger">*</span></label>
                <div class="col-sm-4">
                  <select class="form-select" id="profile_al_exam_type" name="al_exam_type" required>
                    <option value="">Select Exam Type</option>
                    <option value="Local">Local</option>
                    <option value="London Cambridge">London Cambridge</option>
                    <option value="London Edexcel">London Edexcel</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <label for="profile_al_exam_year" class="col-sm-2 col-form-label text-end">Exam Year<span class="text-danger">*</span></label>
                <div class="col-sm-4">
                  <input type="number" class="form-control" id="profile_al_exam_year" name="al_exam_year" placeholder="e.g. 2022" min="1900" max="2027" required>
                </div>
              </div>
              <div class="row mb-3">
                <label for="profile_al_stream" class="col-sm-2 col-form-label">A/L Stream<span class="text-danger">*</span></label>
                <div class="col-sm-10">
                  <select class="form-select" id="profile_al_stream" name="al_stream" required>
                    <option value="" selected disabled>Select an A/L Stream</option>
                    <option value="Physical Science">Physical Science</option>
                    <option value="Bio Science">Bio Science</option>
                    <option value="Commerce">Commerce</option>
                    <option value="Arts">Arts</option>
                    <option value="Technology">Technology</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
              </div>
              <div class="row mb-3 align-items-end">
                <label class="col-sm-2 col-form-label">Result<span class="text-danger">*</span></label>
                <div class="col-sm-4">
                  <select class="form-select" id="profile_al_subject_select">
                    <option value="" selected disabled>Select a Subject</option>
                  </select>
                  <input type="text" class="form-control mt-2" id="profile_al_subject_other" placeholder="Enter subject name" style="display:none;">
                </div>
                <div class="col-sm-4">
                  <select class="form-select" id="profile_al_result_select">
                    <option value="" selected disabled>Select a Result</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                    <option value="S">S</option>
                    <option value="F">F</option>
                  </select>
                  <div id="profileAlResultError" class="text-danger mt-1" style="display:none;">This subject is already added.</div>
                </div>
                <div class="col-sm-2">
                  <button type="button" class="btn btn-primary w-100" id="profile_al_add_btn">Add</button>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-sm-10 offset-sm-2">
                  <table class="table table-bordered">
                    <thead class="bg-primary text-white">
                      <tr>
                        <th>A/L Subject</th>
                        <th>Result</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody id="profile_al_table_body">
                      <!-- JS will add results here -->
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">Save A/L Results</button>
                <button type="button" class="btn btn-secondary" onclick="hideALUpdateForm()">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      `);
    }else if(al_exam){
      let rows=''; (typeof al_exam.al_exam_subjects==='string' ? JSON.parse(al_exam.al_exam_subjects||'[]') : (al_exam.al_exam_subjects||[])).forEach(s=>{ rows+=`<tr><td>${s.subject||''}</td><td>${s.result||''}</td></tr>`; });
      $academic.append(`
        <div id="alExamSection">
          <hr><h5 class="mt-4 mb-3 fw-bold">A/L Exam Details</h5>
          <div class="mb-3 row align-items-center mx-3"><label class="col-sm-3 col-form-label fw-bold">Index No.</label><div class="col-sm-9"><input class="form-control" value="${al_exam.al_index_no||''}" readonly></div></div>
          <div class="mb-3 row align-items-center mx-3"><label class="col-sm-3 col-form-label fw-bold">Exam Type</label><div class="col-sm-9"><input class="form-control" value="${al_exam.al_exam_type||''}" readonly></div></div>
          <div class="mb-3 row align-items-center mx-3"><label class="col-sm-3 col-form-label fw-bold">Exam Year</label><div class="col-sm-9"><input class="form-control" value="${al_exam.al_exam_year||''}" readonly></div></div>
          <div class="mb-3 row align-items-center mx-3"><label class="col-sm-3 col-form-label fw-bold">A/L Stream</label><div class="col-sm-9"><input class="form-control" value="${al_exam.al_stream||''}" readonly></div></div>
          <div class="mb-3 row align-items-center mx-3">
            <label class="col-sm-3 col-form-label fw-bold">Subjects & Results</label>
            <div class="col-sm-9"><table class="table table-bordered mb-0"><thead class="bg-primary text-white"><tr><th>Subject</th><th>Result</th></tr></thead><tbody>${rows}</tbody></table></div>
          </div>
          <div class="mb-3 row align-items-center mx-3">
            <label class="col-sm-3 col-form-label fw-bold">A/L Certificate</label>
            <div class="col-sm-9">${al_exam.al_certificate?`<a href="${certificateUrl(al_exam.al_certificate)}" target="_blank">View Certificate</a>`:'<span class="text-muted">Not uploaded</span>'}</div>
          </div>
        </div>`);
    }

    // Other Info tab (client refresh)
    const $otherInfoTab=$('#other-info'); $otherInfoTab.empty().append('<h5 class="mt-4 mb-3 fw-bold">Other Information</h5>');
    const oi=student.other_information || student.otherInformation;
    if(!!oi){
      $otherInfoTab.append(`
        <div class="mb-3 row align-items-center mx-3"><label class="col-sm-3 col-form-label fw-bold">Disciplinary Issues</label><div class="col-sm-9"><textarea class="form-control" rows="2" readonly>${escapeHtml(oi.disciplinary_issues||'')}</textarea></div></div>
        <div class="mb-3 row align-items-center mx-3"><label class="col-sm-3 col-form-label fw-bold">Disciplinary Document</label><div class="col-sm-9">${oi.disciplinary_issue_document?`<a href="/storage/${encodeURI(oi.disciplinary_issue_document)}" target="_blank">View Document</a>`:'<span class="text-muted">Not uploaded</span>'}</div></div>
        <div class="mb-3 row align-items-center mx-3"><label class="col-sm-3 col-form-label fw-bold">Institute</label><div class="col-sm-9"><input class="form-control" readonly value="${escapeHtml(oi.institute||'-')}"></div></div>
        <div class="mb-3 row align-items-center mx-3"><label class="col-sm-3 col-form-label fw-bold">Field of Study</label><div class="col-sm-9"><input class="form-control" readonly value="${escapeHtml(oi.field_of_study||'-')}"></div></div>
        <div class="mb-3 row align-items-center mx-3"><label class="col-sm-3 col-form-label fw-bold">Job Title</label><div class="col-sm-9"><input class="form-control" readonly value="${escapeHtml(oi.job_title||'-')}"></div></div>
        <div class="mb-3 row align-items-center mx-3"><label class="col-sm-3 col-form-label fw-bold">Workplace</label><div class="col-sm-9"><input class="form-control" readonly value="${escapeHtml(oi.workplace||'-')}"></div></div>
        <div class="mb-3 row align-items-center mx-3"><label class="col-sm-3 col-form-label fw-bold">Other Information</label><div class="col-sm-9"><textarea class="form-control" rows="2" readonly>${escapeHtml(oi.other_information||'-')}</textarea></div></div>
      `);
    }else{
      $otherInfoTab.append('<div class="alert alert-warning">No other information found for this student.</div>');
    }
  };

  // ----- Personal edit buttons -----
  $('#showEditPersonalInfoBtn').on('click', function(){
    $('#studentTitle,#studentName,#studentNIC,#studentIndexNo,#studentInstitute,#studentDOB,#studentGender,#studentEmail,#studentMobile,#studentHomePhone,#studentEmergencyContact,#studentAddress,#studentFoundation,#studentSpecialNeeds,#studentExtraCurricular,#studentFuturePotentials').prop('readonly',false);
    $('#showEditPersonalInfoBtn').hide(); $('#updatePersonalInfoBtn,#cancelEditBtn').show();
  });
  $('#cancelEditBtn').on('click', function(){
    $('#studentTitle,#studentName,#studentNIC,#studentIndexNo,#studentInstitute,#studentDOB,#studentGender,#studentEmail,#studentMobile,#studentHomePhone,#studentEmergencyContact,#studentAddress,#studentFoundation,#studentSpecialNeeds,#studentExtraCurricular,#studentFuturePotentials').prop('readonly',true);
    // Clear validation error classes
    $('.is-invalid').removeClass('is-invalid');
    $('#showEditPersonalInfoBtn').show(); $('#updatePersonalInfoBtn,#cancelEditBtn').hide();
  });
  $('#updatePersonalInfoBtn').on('click', function(){
    const studentId=$('#studentIdHidden').val(); 
    if(!studentId){ return showErrorMessage('No student selected.'); }
    
    // Validate required fields
    const requiredFields = [
      {id: '#studentTitle', name: 'Title'},
      {id: '#studentName', name: 'Name'},
      {id: '#studentNIC', name: 'NIC'},
      {id: '#studentInstitute', name: 'Institute'},
      {id: '#studentDOB', name: 'Date of Birth'},
      {id: '#studentGender', name: 'Gender'},
      {id: '#studentEmail', name: 'Email'},
      {id: '#studentMobile', name: 'Mobile Phone No'},
      {id: '#studentAddress', name: 'Address'}
    ];
    
    let validationErrors = [];
    
    // Check each required field
    requiredFields.forEach(function(field) {
      const value = $(field.id).val();
      if (!value || value.trim() === '') {
        validationErrors.push(field.name);
        $(field.id).addClass('is-invalid');
      } else {
        $(field.id).removeClass('is-invalid');
      }
    });
    
    // Email validation
    const email = $('#studentEmail').val();
    if (email && !isValidEmail(email)) {
      validationErrors.push('Valid Email');
      $('#studentEmail').addClass('is-invalid');
    }
    
    // If there are validation errors, show them and return
    if (validationErrors.length > 0) {
      showErrorMessage('Please fill in all required fields: ' + validationErrors.join(', '));
      return;
    }
    
    const data={
      student_id:studentId,
      title:$('#studentTitle').val(),
      full_name:$('#studentName').val(),
      id_value:$('#studentNIC').val(),
      registration_id:$('#studentIndexNo').val(),
      institute_location:$('#studentInstitute').val(),
      birthday:$('#studentDOB').val(),
      gender:$('#studentGender').val(),
      email:$('#studentEmail').val(),
      mobile_phone:$('#studentMobile').val(),
      home_phone:$('#studentHomePhone').val(),
      emergency_contact_number:$('#studentEmergencyContact').val(),
      address:$('#studentAddress').val(),
      special_needs:$('#studentSpecialNeeds').val(),
      extracurricular_activities:$('#studentExtraCurricular').val(),
      future_potentials:$('#studentFuturePotentials').val(),
      _token:'{{ csrf_token() }}'
    };
    
    $.post("{{ route('student_management.update.personal.info') }}", data, function(resp){
      if(resp.success){
        // Remove any validation error classes
        $('.is-invalid').removeClass('is-invalid');
        showSuccessMessage('Personal information updated successfully!');
        $('#cancelEditBtn').click();
      }else{
        // Handle validation errors from server
        if (resp.errors) {
          let errorMessages = [];
          Object.keys(resp.errors).forEach(function(field) {
            errorMessages.push(resp.errors[field][0]);
          });
          showErrorMessage('Validation Error: ' + errorMessages.join(', '));
        } else {
          showErrorMessage(resp.message||'Failed to update personal information.');
        }
      }
    }).fail(function(xhr) {
      if (xhr.status === 422) {
        const errors = xhr.responseJSON.errors;
        let errorMessages = [];
        Object.keys(errors).forEach(function(field) {
          errorMessages.push(errors[field][0]);
        });
        showErrorMessage('Validation Error: ' + errorMessages.join(', '));
      } else {
        showErrorMessage('An error occurred while updating personal information.');
      }
    });
  });

  // ----- Parent edit buttons -----
  // Show / cancel handlers remain. The actual update is handled by the
  // validated handler defined earlier (to avoid duplicate bindings and
  // inconsistent null submissions).
  $('#showEditParentInfoBtn').on('click', function(){
    $('#parentName,#parentProfession,#parentContactNo,#parentEmail,#parentAddress,#parentEmergencyContact').prop('readonly',false);
    $('#showEditParentInfoBtn').hide(); $('#updateParentInfoBtn,#cancelEditParentBtn').show();
  });
  $('#cancelEditParentBtn').on('click', function(){
    $('#parentName,#parentProfession,#parentContactNo,#parentEmail,#parentAddress,#parentEmergencyContact').prop('readonly',true);
    // Clear validation error classes
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('').hide();
    $('#showEditParentInfoBtn').show(); $('#updateParentInfoBtn,#cancelEditParentBtn').hide();
  });

  // ----- Exams tab dynamic -----
  function getStudentId(){ return $('#studentIdHidden').val(); }
  function fetchRegisteredCourses(){
    const sid=getStudentId(); if(!sid) return;
    $.get('/api/student/'+sid+'/courses', res=>{
      const $s=$('#examCourseSelect'); $s.empty().append('<option value="">Select a course</option>');
      if(res.success && res.courses.length){ res.courses.forEach(c=>$s.append(`<option value="${c.course_id}">${c.course_name}</option>`)); }
    });
  }
  function resetExamsTab(){
    fetchRegisteredCourses();
    $('#examSemesterSelect').empty().append('<option value="">Select a semester</option>').prop('disabled',true);
    $('#examResultsTableWrapper').hide();
    $('#examResultsTableBody').empty();
  }
  function fetchSemesters(courseId){
    const sid=getStudentId(); if(!sid||!courseId) return;
    const $s=$('#examSemesterSelect');
    $s.empty().append('<option value="">Loading semesters...</option>').prop('disabled', true);
    $.get('/api/student/'+sid+'/course/'+courseId+'/semesters', res=>{
      $s.empty().append('<option value="">Select a semester</option>');
      let semesters = [];
      if (Array.isArray(res)) semesters = res;
      else if (res && Array.isArray(res.semesters)) semesters = res.semesters;
      if (semesters.length) {
        semesters.forEach(function(v){
          if (v === null || typeof v === 'undefined' || v === '') return;
          const val = (typeof v === 'object') ? (v.name || v.semester || v.id || '') : v;
          if (val === '') return;
          $s.append(`<option value="${escapeHtml(val)}">${escapeHtml(val)}</option>`);
        });
        $s.prop('disabled', false);
      } else {
        $s.append('<option value="">No semesters found</option>').prop('disabled', true);
      }
    }).fail(function(){
      $s.empty().append('<option value="">Failed to load semesters</option>').prop('disabled', true);
    });
  }
  function fetchModuleResults(courseId, sem){
    const sid=getStudentId(); if(!sid||!courseId||!sem) return;
    $.get('/api/student/'+sid+'/course/'+courseId+'/semester/'+sem+'/results', res=>{
      const $tb=$('#examResultsTableBody').empty();
      if(res.success && res.results.length){ res.results.forEach(r=>$tb.append(`<tr><td>${r.module_name}</td><td>${r.marks}</td><td>${r.grade}</td></tr>`)); }
      else{ $tb.append('<tr><td colspan="3" class="text-center">No results found.</td></tr>'); }
      $('#examResultsTableWrapper').show();
    });
  }
  $('#examCourseSelect').on('change', function(){ const c=$(this).val(); if(c){ fetchSemesters(c); $('#examResultsTableWrapper').hide(); } else { $('#examSemesterSelect').empty().append('<option value="">Select a semester</option>').prop('disabled',true); $('#examResultsTableWrapper').hide(); }});
  $('#examSemesterSelect').on('change', function(){ const c=$('#examCourseSelect').val(), s=$(this).val(); if(c&&s){ fetchModuleResults(c,s); } else { $('#examResultsTableWrapper').hide(); }});

  // ----- Attendance tab -----
  function fetchAttendanceCourses(){
    const sid=getStudentId(); if(!sid) return;
    $.get('/api/student/'+sid+'/courses', res=>{
      const $s=$('#attendanceCourseSelect').empty().append('<option value="">Select a course</option>');
      if(res.success && res.courses && res.courses.length){ 
        res.courses.forEach(c=>$s.append(`<option value="${c.course_id}" data-course-type="${c.course_type}">${c.course_name}</option>`)); 
      }
    }).fail(function(err){
      console.error('Failed to load courses for attendance', err);
      showErrorMessage('Failed to load courses');
    });
  }
  
  function fetchAttendanceSemesters(courseId){
    const sid=getStudentId(); if(!sid||!courseId) return;
    const $s = $('#attendanceSemesterSelect');
    // reset and show loading state
    $s.empty().append('<option value="">Loading semesters...</option>');
    $s.prop('disabled', true).attr('aria-busy', 'true');

    $.get('/api/student/'+sid+'/course/'+courseId+'/semesters', res=>{
      // DEBUG: show raw response in console to aid troubleshooting
      console.log('fetchAttendanceSemesters response:', res);

      // Normalize many possible API shapes: res.semesters, res.data.semesters, res.data (array), res (array)
      let semesters = [];
      if (Array.isArray(res)) semesters = res;
      else if (res && Array.isArray(res.semesters)) semesters = res.semesters;
      else if (res && res.data && Array.isArray(res.data.semesters)) semesters = res.data.semesters;
      else if (res && res.data && Array.isArray(res.data)) semesters = res.data;
      else if (res && res.success && Array.isArray(res.semesters)) semesters = res.semesters;

      if (Array.isArray(semesters) && semesters.length){
        semesters.forEach(function(v){
          // guard: skip null/empty
          if (v === null || typeof v === 'undefined') return;
          const val = (typeof v === 'object' && v.semester) ? v.semester : v;
          $s.append(`<option value="${val}">${val}</option>`);
        });
        // enable the select reliably
        $s.prop('disabled', false);
        $s.removeAttr('disabled');
        $s.attr('aria-busy', 'false').attr('aria-disabled', 'false');
      } else {
        // no semesters: keep it disabled and show a helpful single option
        $s.empty().append('<option value="">No semesters registered for this course</option>');
        $s.prop('disabled', true).attr('disabled', 'disabled').attr('aria-busy', 'false');
      }
    }).fail(function(err){
      console.error('Failed to load attendance semesters for course', courseId, err);
      $s.empty().append('<option value="">Failed to load semesters</option>').prop('disabled', true).attr('disabled','disabled').attr('aria-busy','false');
      showErrorMessage('Failed to load semesters');
    });
  }
  
  function fetchAttendanceTable(courseId, sem){
    const sid=getStudentId(); if(!sid||!courseId||!sem) return;
    
    console.log('Fetching attendance for:', {sid, courseId, sem});
    
    $.get('/api/student/'+sid+'/course/'+courseId+'/semester/'+sem+'/attendance', res=>{
      console.log('Attendance response:', res);
      const $tb=$('#attendanceTableBody').empty();
      
      // Check if response is successful and has attendance data
      if(res.success && res.attendance && Array.isArray(res.attendance) && res.attendance.length > 0){
        res.attendance.forEach(a=>$tb.append(`<tr><td>${a.module_name}</td><td>${a.total_days}</td><td>${a.present_days}</td><td>${a.absent_days}</td><td>${a.attendance_percent}</td></tr>`));
        showSuccessMessage('Attendance data loaded successfully');
      }else{ 
        $tb.append('<tr><td colspan="5" class="text-center text-muted">No attendance data found for this selection.</td></tr>'); 
      }
      $('#attendanceTableWrapper').show();
    }).fail(function(err){
      console.error('Failed to load attendance table', err);
      const $tb=$('#attendanceTableBody').empty();
      $tb.append('<tr><td colspan="5" class="text-center text-danger">Error loading attendance data</td></tr>');
      $('#attendanceTableWrapper').show();
      showErrorMessage('Failed to load attendance data: ' + (err.responseJSON?.message || err.statusText));
    });
  }
  
  function fetchAttendanceTableForCertificate(courseId){
    const sid=getStudentId(); if(!sid||!courseId) return;
    
    console.log('Fetching certificate attendance for:', {sid, courseId});
    
    $.get('/api/student/'+sid+'/course/'+courseId+'/attendance', res=>{
      console.log('Certificate attendance response:', res);
      const $tb=$('#attendanceTableBody').empty();
      
      // Check if response is successful and has attendance data
      if(res.success && res.attendance && Array.isArray(res.attendance) && res.attendance.length > 0){
        res.attendance.forEach(a=>$tb.append(`<tr><td>${a.module_name}</td><td>${a.total_days}</td><td>${a.present_days}</td><td>${a.absent_days}</td><td>${a.attendance_percent}</td></tr>`));
        showSuccessMessage('Attendance data loaded successfully');
      }else{ 
        $tb.append('<tr><td colspan="5" class="text-center text-muted">No attendance data found for this course.</td></tr>'); 
      }
      $('#attendanceTableWrapper').show();
    }).fail(function(err){
      console.error('Failed to load certificate attendance table', err);
      const $tb=$('#attendanceTableBody').empty();
      $tb.append('<tr><td colspan="5" class="text-center text-danger">Error loading attendance data</td></tr>');
      $('#attendanceTableWrapper').show();
      showErrorMessage('Failed to load attendance data: ' + (err.responseJSON?.message || err.statusText));
    });
  }
  
  function resetAttendanceTab(){
    fetchAttendanceCourses();
    $('#attendanceSemesterSelect').empty().append('<option value="">Select a semester</option>').prop('disabled',true);
    $('#semesterSelectContainer').hide();
    $('#attendanceTableWrapper').hide();
    $('#attendanceTableBody').empty();
  }
  $('#attendanceCourseSelect').on('change', function(){
    const c=$(this).val();
    const courseType = $(this).find('option:selected').data('course-type');
    if(c){
      if(courseType === 'certificate'){
        // For certificate courses, hide semester select and fetch attendance directly
        $('#semesterSelectContainer').hide();
        fetchAttendanceTableForCertificate(c);
      } else {
        // For degree courses, show semester select and fetch semesters
        $('#semesterSelectContainer').show();
        fetchAttendanceSemesters(c);
      }
      $('#attendanceTableWrapper').hide();
    } else {
      $('#semesterSelectContainer').hide();
      $('#attendanceTableWrapper').hide();
    }
  });
  $('#attendanceSemesterSelect').on('change', function(){ const c=$('#attendanceCourseSelect').val(), s=$(this).val(); if(c&&s){ fetchAttendanceTable(c,s); } else { $('#attendanceTableWrapper').hide(); }});

  // ----- Payment tab -----
  function fetchPaymentCourses(){
    const sid=getStudentId(); if(!sid) return;
    $.get('/api/student/'+sid+'/courses', res=>{
      const $s=$('#paymentCourseSelect').empty().append('<option value="">Select a course</option>');
      if(res.success && res.courses.length){ res.courses.forEach(c=>$s.append(`<option value="${c.course_id}">${c.course_name}</option>`)); }
    });
  }
  function fetchPaymentIntakes(courseId){
    const sid=getStudentId(); if(!sid||!courseId) return;
    $.get('/api/student/'+sid+'/course/'+courseId+'/intakes', res=>{
      const $s=$('#paymentIntakeSelect').empty().append('<option value="">Select an intake</option>');
      if(res.success && res.intakes.length){ res.intakes.forEach(i=>$s.append(`<option value="${i}">${i}</option>`)); $s.prop('disabled',false); } else { $s.prop('disabled',true); }
    });
  }
  function fetchPaymentDetails(courseId, intake){
    const sid=getStudentId(); if(!sid||!courseId||!intake) return;
    $.get('/api/student/'+sid+'/course/'+courseId+'/intake/'+intake+'/payment-details', res=>{
      if(res.success){ $('#totalFee').text(res.total_fee||'N/A'); $('#paidAmount').text(res.paid_amount||'0'); $('#balance').text(res.balance||'0'); $('#paymentStatus').text(res.payment_status||'N/A'); $('#paymentTableWrapper').show(); }
      else{ $('#paymentTableWrapper').hide(); }
    });
  }
  function fetchPaymentHistory(courseId,intake){
    const sid=getStudentId(); if(!sid||!courseId||!intake) return;
    $.get('/api/student/'+sid+'/course/'+courseId+'/intake/'+intake+'/payment-history', res=>{
      const $h=$('#paymentHistory').empty();
      if(res.success && res.history && res.history.length){
        res.history.forEach(p=>$h.append(`<p class="mb-1"><strong>Date:</strong> ${p.payment_date||'N/A'}</p><p class="mb-1"><strong>Amount:</strong> ${p.amount||'0'}</p><p class="mb-1"><strong>Method:</strong> ${p.payment_method||'N/A'}</p><p class="mb-1"><strong>Receipt:</strong> ${p.receipt_url?`<a href="${p.receipt_url}" target="_blank">View Receipt</a>`:'N/A'}</p><hr class="my-2">`));
      }else{ $h.append('<p class="text-muted">No payment history found for this intake.</p>'); }
    });
  }
  function fetchPaymentSchedule(courseId,intake){
    const sid=getStudentId(); if(!sid||!courseId||!intake) return;
    $.get('/api/student/'+sid+'/course/'+courseId+'/intake/'+intake+'/payment-schedule', res=>{
      const $tb=$('#paymentScheduleTableBody').empty();
      if(res.success && res.schedule.length){
        res.schedule.forEach(p=>$tb.append(`<tr><td>${p.due_date||'N/A'}</td><td>${p.amount||'0'}</td><td>${p.status||'N/A'}</td><td>${p.payment_date||'N/A'}</td><td>${p.receipt_url?`<a href="${p.receipt_url}" target="_blank">View Receipt</a>`:'N/A'}</td></tr>`));
      }else{ $tb.append('<tr><td colspan="5" class="text-center">No payment schedule found for this intake.</td></tr>'); }
    });
  }
  $('#paymentCourseSelect').on('change', function(){ const c=$(this).val(); if(c){ fetchPaymentIntakes(c); $('#paymentIntakeSelect').empty().append('<option value="">Select an intake</option>').prop('disabled',true); $('#paymentTableWrapper').hide(); $('#paymentHistory').empty(); $('#paymentScheduleTableBody').empty(); } else { $('#paymentIntakeSelect').empty().append('<option value="">Select an intake</option>').prop('disabled',true); $('#paymentTableWrapper').hide(); $('#paymentHistory').empty(); $('#paymentScheduleTableBody').empty(); }});
  $('#paymentIntakeSelect').on('change', function(){ const c=$('#paymentCourseSelect').val(), i=$(this).val(); if(c&&i){ fetchPaymentDetails(c,i); fetchPaymentHistory(c,i); fetchPaymentSchedule(c,i); } else { $('#paymentTableWrapper').hide(); $('#paymentHistory').empty(); $('#paymentScheduleTableBody').empty(); }});

  // ----- Clearance tab -----
  function clearanceDocumentCell(info){
    const url = (info && (info.document_url || (info.has_document ? info.clearance_slip : '')) || '').toString().trim();
    if (url && url !== 'null' && url.toLowerCase() !== 'n/a') {
      const href = url.startsWith('http') || url.startsWith('/') ? url : ('/storage/' + url.replace(/^public\//, ''));
      return `<a href="${escapeHtml(href)}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class="ti ti-download"></i> Download</a>`;
    }
    return '<span class="text-muted">No document uploaded</span>';
  }
  function fetchStudentClearances(){
    const sid=$('#studentIdHidden').val(); if(!sid) return;
    $.get('/api/student/'+sid+'/clearances', res=>{
      const $tb=$('#clearanceTableBody').empty();
      if(res.success && res.clearances && res.clearances.length){
        res.clearances.forEach(info=>$tb.append(`<tr>
          <td>${escapeHtml(info.label)}</td>
          <td>${info.status?'<span class="badge bg-success">Approved</span>':'<span class="badge bg-warning text-dark">Pending</span>'}</td>
          <td>${escapeHtml(info.approved_date||'N/A')}</td>
          <td>${escapeHtml(info.remarks || '—')}</td>
          <td>${clearanceDocumentCell(info)}</td>
        </tr>`));
        if(!$tb.children().length){ $tb.append('<tr><td colspan="5" class="text-center">No uploaded clearance documents found.</td></tr>'); }
      }else{
        $tb.append('<tr><td colspan="5" class="text-center">No clearance data found.</td></tr>');
      }
    });
  }

  // ----- Status History tab -----
  function fetchStatusHistory(){
    const sid = $('#studentIdHidden').val(); if(!sid) return;
    $.get('/api/student/' + sid + '/status-history', function(res){
      const $tb = $('#statusHistoryTableBody').empty();
      if(res && res.success && res.history && res.history.length){
        res.history.forEach(function(h, idx){
          const docLink = h.document ? `<a href="/storage/${h.document}" target="_blank">View</a>` : '—';
          // highlight rows that represent a termination event
          const rowClass = (h.to_status || '').toString().toLowerCase() === 'terminated' ? 'table-danger' : '';
          $tb.append(`<tr class="${rowClass}">
            <td>${idx+1}</td>
            <td>${escapeHtml(h.from_status || 'N/A')}</td>
            <td>${escapeHtml(h.to_status || 'N/A')}</td>
            <td>${escapeHtml(h.reason || '')}</td>
            <td>${docLink}</td>
            <td>${escapeHtml(h.changed_by_name || h.changed_by || 'System')}</td>
            <td>${escapeHtml(h.created_at || '')}</td>
          </tr>`);
        });
        $('#statusHistoryCount').text(res.history.length).show();
      } else {
        $tb.append('<tr><td colspan="7" class="text-center text-muted">No status history available.</td></tr>');
        $('#statusHistoryCount').hide();
      }
    }).fail(function(){
      $('#statusHistoryTableBody').html('<tr><td colspan="7" class="text-center text-danger">Error loading status history.</td></tr>');
      $('#statusHistoryCount').hide();
    });
  }

  //-- payment summary tab --
  function fetchCoursesForPaymentSummary() {
    const sid = $('#studentIdHidden').val();
    if (!sid) return;
    $.get('/api/student/' + sid + '/courses', function(courseRes) {
      const $courseSelect = $('#summary-course').empty().append('<option value="" selected disabled>Select a Course</option>');
      if (courseRes.success && courseRes.courses.length) {
        courseRes.courses.forEach(c => $courseSelect.append(`<option value="${escapeHtml(c.course_id)}">${escapeHtml(c.course_name)}</option>`));
      }
    });
  }
  function resetPaymentSummary() {
    $('#paymentSummarySection').hide();
    $('#summary-course').empty().append('<option value="" selected disabled>Select a Course</option>');
  }
  function refreshPaymentSummaryTab() {
    resetPaymentSummary();
    fetchCoursesForPaymentSummary();
  }

  $('#generatePaymentSummaryBtn').on('click', function() {
    const sid = $('#studentIdHidden').val();
    const courseId = $('#summary-course').val();
    if (!sid || !courseId) {
      showErrorMessage('Please select a course.');
      return;
    }
    $.get('/api/student/' + sid + '/course/' + courseId + '/payment-summary', function(res) {
      if (res.success && res.summary) {
        const summary = res.summary;
        const formatRs = function(value) {
          const n = Number(value);
          if (!Number.isFinite(n)) {
            return 'Rs. 0.00';
          }
          return 'Rs. ' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };
        const formatNum = function(value) {
          const n = Number(value);
          if (!Number.isFinite(n)) {
            return '0.00';
          }
          return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };
        const paymentTypeKey = function(detailsType) {
          if (detailsType === 'course') return 'course_fee';
          if (detailsType === 'franchise') return 'franchise_fee';
          if (detailsType === 'registration') return 'registration_fee';
          if (detailsType === 'hostel') return 'hostel_fee';
          if (detailsType === 'library') return 'library_fee';
          return 'other';
        };
        $('#paymentSummarySection').show();
        $('#summary-student-id').text(summary.student.student_id || '');
        $('#summary-student-name').text(summary.student.student_name || '');
        $('#summary-course-name').text(summary.student.course_name || '');
        $('#summary-registration-date').text(summary.student.registration_date || '');
        $('#summary-course-fee').text(formatRs(summary.course_fee));
        $('#summary-registration-fee').text(formatRs(summary.registration_fee));
        $('#summary-total-local-fee').text(formatRs(summary.total_local_amount));
        $('#summary-total-franchise-fee').text(formatNum(summary.total_franchise_amount) + ' ' + (summary.franchise_currency || 'USD'));
        $('#summary-total-local-paid').text(formatRs(summary.local_paid));
        $('#summary-total-franchise-paid').text(formatNum(summary.franchise_paid) + ' ' + (summary.franchise_currency || 'USD'));
        $('#total-local-amount').text(formatRs(summary.total_local_amount));
        $('#total-franchise-amount').text(formatNum(summary.total_franchise_amount) + ' ' + (summary.franchise_currency || 'USD'));
        $('#total-local-paid').text(formatRs(summary.local_paid));
        $('#total-franchise-paid').text(formatNum(summary.franchise_paid) + ' ' + (summary.franchise_currency || 'USD'));
        $('#total-local-outstanding').text(formatRs(summary.local_outstanding));
        $('#payment-rate').text((summary.payment_rate || 0) + '%');

        // Helper to fill tables by payment type
        function fillTable(tableId, detailsType) {
          const details = (summary.payment_details || []).find(d => d.payment_type === paymentTypeKey(detailsType));
          const $tb = $(tableId).empty();
          if (details && details.payments && details.payments.length) {
            details.payments.forEach(row => {
              if (detailsType === 'franchise') {
                $tb.append(`<tr>
                  <td>${row.amount_currency !== undefined ? formatNum(row.amount_currency) : '-'}</td>
                  <td>${row.currency || '-'}</td>
                  <td>${row.sscl_tax !== undefined ? formatRs(row.sscl_tax) : '-'}</td>
                  <td>${row.bank_charges !== undefined ? formatRs(row.bank_charges) : '-'}</td>
                  <td>${row.total_amount_lkr !== undefined ? formatRs(row.total_amount_lkr) : '-'}</td>
                  <td>${formatRs(row.paid_amount)}</td>
                  <td>${formatRs(row.outstanding)}</td>
                  <td>${row.payment_date || '-'}</td>
                  <td>${row.due_date || '-'}</td>
                  <td>${row.receipt_no || '-'}</td>
                  <td>${row.uploaded_receipt ? `<a href="${row.uploaded_receipt}" target="_blank">View</a>` : '-'}</td>
                  <td>${row.installment_number || '-'}</td>
                </tr>`);
              } else {
                $tb.append(`<tr>
                  <td>${formatRs(row.total_amount)}</td>
                  <td>${formatRs(row.paid_amount)}</td>
                  <td>${formatRs(row.outstanding)}</td>
                  <td>${row.payment_date || '-'}</td>
                  <td>${row.due_date || '-'}</td>
                  <td>${row.receipt_no || '-'}</td>
                  <td>${row.uploaded_receipt ? `<a href="${row.uploaded_receipt}" target="_blank">View</a>` : '-'}</td>
                  <td>${row.installment_number || '-'}</td>
                </tr>`);
              }
            });
          } else {
            const colspan = detailsType === 'franchise' ? 12 : 8;
            $tb.append(`<tr><td colspan="${colspan}" class="text-center text-muted">No data available</td></tr>`);
          }
        }
        fillTable('#courseFeeTableBody', 'course');
        fillTable('#franchiseFeeTableBody', 'franchise');
        fillTable('#registrationFeeTableBody', 'registration');
        fillTable('#hostelFeeTableBody', 'hostel');
        fillTable('#libraryFeeTableBody', 'library');
        fillTable('#otherFeeTableBody', 'other');

        const sltReceivables = summary.slt_loan_receivables;
        const $sltSection = $('#sltLoanReceivablesSection');
        const $sltBody = $('#sltLoanReceivablesTableBody').empty();

        if (sltReceivables && sltReceivables.installments && sltReceivables.installments.length) {
          $sltSection.show();
          $('#slt-summary-loan-amount').text('Rs. ' + Number(sltReceivables.slt_loan_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
          $('#slt-summary-loan-years').text(sltReceivables.loan_taken_years || '-');
          $('#slt-summary-installment-count').text(sltReceivables.loan_installment_count || '-');
          $('#slt-summary-start-installment').text(sltReceivables.apply_from_installment || '-');
          $('#slt-summary-monthly-receivable').text('Rs. ' + Number(sltReceivables.monthly_receivable || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
          $('#slt-summary-effective-date').text(sltReceivables.last_payment_effective_date || '-');

          sltReceivables.installments.forEach(row => {
            const statusClass = row.status === 'Recorded' ? 'text-success' : 'text-muted';
            $sltBody.append(`<tr>
              <td>${row.installment_number}</td>
              <td>Rs. ${Number(row.receivable_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
              <td class="${statusClass} fw-bold">${row.status || 'Pending'}</td>
              <td>${row.payment_effective_date || '-'}</td>
              <td>${row.recorded_at || '-'}</td>
            </tr>`);
          });
        } else {
          $sltSection.hide();
          $sltBody.append('<tr><td colspan="5" class="text-center text-muted">No SLT loan receivable data available</td></tr>');
        }
      } else {
        showErrorMessage(res.message || 'No payment summary found.');
        $('#paymentSummarySection').hide();
      }
    }).fail(function() {
      showErrorMessage('Error loading payment summary.');
      $('#paymentSummarySection').hide();
    });
  });

  // ----- History tab -----
  function fetchCourseRegistrationHistory(){
    const sid = $('#studentIdHidden').val();
    if (!sid) return;
    $.get('/api/student/' + sid + '/history', function(res){
      const $tb = $('#historyTableBody').empty();
      if (res.success && res.history && res.history.length) {
        res.history.forEach(h => {
          const specs = Array.isArray(h.specializations) ? h.specializations : [];
          const currentSpec = h.specialization || '';
          const specOptions = [`<option value="">(No Specialization)</option>`]
            .concat(specs.map(spec => {
              const value = String(spec ?? '');
              const selected = value === String(currentSpec) ? ' selected' : '';
              return `<option value="${escapeHtml(value)}"${selected}>${escapeHtml(value)}</option>`;
            }))
            .join('');
          $tb.append(`<tr data-id="${escapeHtml(h.id)}" data-course-id="${escapeHtml(h.course_id)}">
            <td>${escapeHtml(h.course_name)}</td>
            <td>${escapeHtml(h.intake)}</td>
            <td>${escapeHtml(h.status)}</td>
            <td class="specialization-cell">
              <span class="specialization-text">${escapeHtml(currentSpec)}</span>
              <select class="form-select form-select-sm specialization-select" style="display:none;">${specOptions}</select>
            </td>
            <td class="full-grade-cell">
              <span class="grade-text">${escapeHtml(h.full_grade || '')}</span>
              <input type="text" class="form-control grade-input" style="display:none;" value="${escapeHtml(h.full_grade || '')}">
            </td>
            <td>
              <div class="history-action-buttons">
                <button type="button" class="btn btn-sm btn-primary edit-grade-btn">Edit</button>
                <button type="button" class="btn btn-sm btn-success save-grade-btn" style="display:none;">Save</button>
                <button type="button" class="btn btn-sm btn-secondary cancel-grade-btn" style="display:none;">Cancel</button>
              </div>
            </td>
          </tr>`);
        });
      } else {
        $tb.append('<tr><td colspan="6" class="text-center">No registration history found.</td></tr>');
      }
    }).fail(function(){
      $('#historyTableBody').html('<tr><td colspan="6" class="text-center text-danger">Error loading history.</td></tr>');
    });
  }

  // --- Grade & Specialization Edit/Save/Cancel Handlers ---
  $(document).on('click', '.edit-grade-btn', function(){
    const $tr = $(this).closest('tr');
    $tr.find('.grade-text').hide();
    $tr.find('.grade-input').show().focus();
    $tr.find('.edit-grade-btn').hide();
    $tr.find('.save-grade-btn,.cancel-grade-btn').show();

    // Specialization dropdown
    const courseId = $tr.data('course-id');
    const $specCell = $tr.find('.specialization-cell');
    const $specText = $specCell.find('.specialization-text');
    const $specSelect = $specCell.find('.specialization-select');
    $specText.hide();
    $specSelect.show();

    // Load specializations for this course
    $.get('/api/course/' + courseId + '/specializations', function(res){
      $specSelect.empty();
      $specSelect.append(`<option value="">(No Specialization)</option>`);
      if(res.success && res.specializations && res.specializations.length){
        res.specializations.forEach(function(spec){
          if(spec && spec.toString().trim()!=='') {
            $specSelect.append(`<option value="${spec}">${spec}</option>`);
          }
        });
      }
      const current = $specText.text().trim();
      $specSelect.val(current !== '' ? current : '');
    }).fail(function(){
      $specSelect.empty().append(`<option value="">(No Specialization)</option>`);
    });
  });

  $(document).on('click', '.cancel-grade-btn', function(){
    const $tr = $(this).closest('tr');
    $tr.find('.grade-input').hide();
    $tr.find('.grade-text').show();
    $tr.find('.edit-grade-btn').show();
    $tr.find('.save-grade-btn,.cancel-grade-btn').hide();

    const $specCell = $tr.find('.specialization-cell');
    $specCell.find('.specialization-select').hide();
    $specCell.find('.specialization-text').show();
  });

  $(document).on('click', '.save-grade-btn', function(e){
    e.preventDefault();
    const $tr = $(this).closest('tr');
    const id = $tr.data('id');
    if (!id) return showErrorMessage('Invalid registration record.');

    const grade = $tr.find('.grade-input').val().trim();
    const specialization = $tr.find('.specialization-select').val();

    $.ajax({
      url: '/api/course-registration/' + id + '/update-grade',
      method: 'POST',
      data: {
        full_grade: grade,
        specialization: specialization,
        _token: '{{ csrf_token() }}'
      },
      success: function(res){
        if(res.success){
          // refresh history from server to reflect DB state (authoritative)
          fetchCourseRegistrationHistory();
          showSuccessMessage('Grade and specialization updated successfully!');
        } else {
          showErrorMessage(res.message || 'Failed to update.');
        }
      },
      error: function(xhr){
        if(xhr && xhr.status === 419){
          showErrorMessage('Session expired. Please refresh the page and try again.');
        } else {
          showErrorMessage('Error updating. Check the browser console / network tab for details.');
        }
      }
    });
  });

  $(document).on('click', '.cancel-grade-btn', function(){
    const $tr = $(this).closest('tr');
    $tr.find('.grade-input').hide();
    $tr.find('.grade-text').show();
    $tr.find('.edit-grade-btn').show();
    $tr.find('.save-grade-btn,.cancel-grade-btn').hide();

    // Specialization dropdown
    const $specCell = $tr.find('.specialization-cell');
    $specCell.find('.specialization-select').hide();
    $specCell.find('.specialization-text').show();
  });

  $(document).on('click', '.save-grade-btn', function(){
    const $tr = $(this).closest('tr');
    const id = $tr.data('id');
    const grade = $tr.find('.grade-input').val().trim();
    const specialization = $tr.find('.specialization-select').val();
    if (!id) return showErrorMessage('Invalid registration record.');
    $.ajax({
      url: '/api/course-registration/' + id + '/update-grade',
      method: 'POST',
      data: { full_grade: grade, specialization: specialization, _token: '{{ csrf_token() }}' },
      success: function(res){
        if(res.success){
          $tr.find('.grade-text').text(grade).show();
          $tr.find('.grade-input').hide();
          $tr.find('.edit-grade-btn').show();
          $tr.find('.save-grade-btn,.cancel-grade-btn').hide();
          $tr.find('.specialization-text').text(specialization).show();
          $tr.find('.specialization-select').hide();
          showSuccessMessage('Grade and specialization updated successfully!');
        }else{
          showErrorMessage(res.message || 'Failed to update.');
        }
      },
      error: function(){
        showErrorMessage('Error updating.');
      }
    });
  });

  //-- Fetch Module Results --
 function fetchModuleResults(courseId, sem){
    const sid=getStudentId(); if(!sid||!courseId||!sem) return;
    $.get('/api/student/'+sid+'/course/'+courseId+'/semester/'+sem+'/results', res=>{
      const $tb=$('#examResultsTableBody').empty();
      if(res.success && res.results.length){
        res.results.forEach(r=>$tb.append(`<tr><td>${r.module_name}</td><td>${r.marks}</td><td>${r.grade}</td></tr>`));
      }
      else{
        $tb.append('<tr><td colspan="3" class="text-center">No results found.</td></tr>');
      }
      $('#examResultsTableWrapper').show();
    });
  }

  
  // Certificates tab (lazy load)
  function fetchStudentCertificates(){
    const sid=$('#studentIdHidden').val();
    const showEmpty = function(label){
      $('#olCertificate,#alCertificate,#disciplinaryDocument').html('<span class="text-muted">' + (label || 'Not uploaded') + '</span>');
    };
    if(!sid){
      showEmpty('Not uploaded');
      return;
    }
    $.get('/api/student/'+sid+'/certificates', res=>{
      if(res.success){
        // OL Certificate
        if(res.ol_certificate){
          $('#olCertificate').html(`<a href="${certificateUrl(res.ol_certificate)}" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View Certificate</a>`);
        } else {
          $('#olCertificate').html(`<span class="text-muted">Pending</span> <button class="btn btn-sm btn-primary ms-2" onclick="uploadOLCertificate()"><i class="fas fa-upload"></i> Upload OL Certificate</button>`);
        }
        
        // AL Certificate
        if(res.al_certificate){
          $('#alCertificate').html(`<a href="${certificateUrl(res.al_certificate)}" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View Certificate</a>`);
        } else {
          $('#alCertificate').html(`<span class="text-muted">Pending</span> <button class="btn btn-sm btn-primary ms-2" onclick="uploadALCertificate()"><i class="fas fa-upload"></i> Upload AL Certificate</button>`);
        }
        
        // Disciplinary Document
        $('#disciplinaryDocument').html(res.disciplinary_issue_document?`<a href="/storage/${res.disciplinary_issue_document}" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View Document</a>`:'<span class="text-muted">Not uploaded</span>');
      }else{
        showEmpty('Not uploaded');
      }
    }).fail(function(){
      showEmpty('Not uploaded');
    });
  }

  // Upload OL Certificate
  function uploadOLCertificate(){
    $('#olCertificateInput').click();
  }
  window.uploadOLCertificate = uploadOLCertificate;
  
  $('#olCertificateInput').on('change', function(){
    const file = this.files[0];
    if(!file) return;
    
    const sid = $('#studentIdHidden').val();
    if(!sid) return showErrorMessage('No student selected.');
    
    const fd = new FormData();
    fd.append('_token', '{{ csrf_token() }}');
    fd.append('ol_certificate', file);
    
    $.ajax({
      type: 'POST',
      url: '/student/'+sid+'/upload-ol-certificate',
      data: fd,
      processData: false,
      contentType: false,
      beforeSend: function(){
        $('#olCertificate').html('<span class="text-primary"><i class="fas fa-spinner fa-spin"></i> Uploading...</span>');
      },
      success: function(res){
        if(res.success){
          showSuccessMessage(res.message || 'OL certificate uploaded successfully!');
          fetchStudentCertificates(); // Refresh to show new certificate
        } else {
          showErrorMessage(res.message || 'Failed to upload OL certificate.');
          fetchStudentCertificates();
        }
      },
      error: function(xhr){
        const msg = xhr.responseJSON?.message || 'Error uploading OL certificate.';
        showErrorMessage(msg);
        fetchStudentCertificates();
      }
    });
    
    // Reset file input
    $(this).val('');
  });

  // Upload AL Certificate
  function uploadALCertificate(){
    $('#alCertificateInput').click();
  }
  window.uploadALCertificate = uploadALCertificate;
  
  $('#alCertificateInput').on('change', function(){
    const file = this.files[0];
    if(!file) return;
    
    const sid = $('#studentIdHidden').val();
    if(!sid) return showErrorMessage('No student selected.');
    
    const fd = new FormData();
    fd.append('_token', '{{ csrf_token() }}');
    fd.append('al_certificate', file);
    
    $.ajax({
      type: 'POST',
      url: '/student/'+sid+'/upload-al-certificate',
      data: fd,
      processData: false,
      contentType: false,
      beforeSend: function(){
        $('#alCertificate').html('<span class="text-primary"><i class="fas fa-spinner fa-spin"></i> Uploading...</span>');
      },
      success: function(res){
        if(res.success){
          showSuccessMessage(res.message || 'AL certificate uploaded successfully!');
          fetchStudentCertificates(); // Refresh to show new certificate
        } else {
          showErrorMessage(res.message || 'Failed to upload AL certificate.');
          fetchStudentCertificates();
        }
      },
      error: function(xhr){
        const msg = xhr.responseJSON?.message || 'Error uploading AL certificate.';
        showErrorMessage(msg);
        fetchStudentCertificates();
      }
    });
    
    // Reset file input
    $(this).val('');
  });

  // ----- Terminate / Reinstate actions -----
  // Intercept terminate click to check for existing clearances first
  $(document).on('click','#terminateBtn', function(e){
    e.preventDefault();
    const studentId = $('#studentIdHidden').val();
    if(!studentId) return showErrorMessage('No student selected.');

    const $btn = $(this);
    if ($btn.hasClass('loading')) return;
    $btn.addClass('loading').prop('disabled', true);

    $.ajax({
      url: '/api/student/' + encodeURIComponent(studentId) + '/clearances',
      method: 'GET',
      success: function(res){
        if(res && res.success && Array.isArray(res.clearances) && res.clearances.length>0){
          let html = '';
          res.clearances.forEach(function(c){
            const statusText = c.status ? 'Approved' : 'Pending';
            const dateText = c.approved_date ? ' ('+c.approved_date+')' : '';
            const remarks = c.remarks ? ' — '+c.remarks : '';
            html += `<li class="mb-2"><strong>${c.label}</strong>: ${statusText}${dateText}${remarks}</li>`;
          });
          $('#profileTerminateClearanceList').html(html);
          new bootstrap.Modal(document.getElementById('terminateClearanceModal')).show();
        } else if(res && res.success) {
          new bootstrap.Modal(document.getElementById('terminateModal')).show();
        } else {
          showErrorMessage(res && res.message ? res.message : 'Failed to check clearances.');
        }
      },
      error: function(){ showErrorMessage('Failed to check clearances.'); },
      complete: function(){ $btn.removeClass('loading').prop('disabled', false); }
    });
  });
  $(document).on('click','#reinstateBtn',()=> new bootstrap.Modal(document.getElementById('reinstateModal')).show());

  $('#confirmTerminate').on('click', function(){
    const sid=$('#studentIdHidden').val(); const reason=$('#terminateReason').val().trim(); const doc=$('#terminateDocument')[0].files[0];
    if(!sid) return showErrorMessage('No student selected.'); if(!reason) return $('#terminateReason').focus();
    const fd=new FormData(); fd.append('_token','{{ csrf_token() }}'); fd.append('student_id',sid); fd.append('reason',reason); if(doc) fd.append('document',doc);
    $.ajax({type:'POST',url:"{{ route('student_management.terminate') }}",data:fd,processData:false,contentType:false,
      success:function(res){
        if(res.success){ showSuccessMessage(res.message||'Student terminated.'); setStatusUI('terminated'); $('#terminateReason').val(''); $('#terminateDocument').val(''); bootstrap.Modal.getInstance(document.getElementById('terminateModal')).hide(); }
        else{ showErrorMessage(res.message||'Failed to terminate.'); }
      }, error:function(){ showErrorMessage('Error while terminating student.'); }
    });
  });

  $('#confirmReinstate').on('click', function(){
    const sid=$('#studentIdHidden').val(); const reason=$('#reinstateReason').val().trim(); const doc=$('#reinstateDocument')[0].files[0];
    if(!sid) return showErrorMessage('No student selected.'); if(!reason) return $('#reinstateReason').focus();
    const fd=new FormData(); fd.append('_token','{{ csrf_token() }}'); fd.append('student_id',sid); fd.append('reason',reason); if(doc) fd.append('document',doc);
    $.ajax({type:'POST',url:"{{ route('student_management.reinstate') }}",data:fd,processData:false,contentType:false,
      success:function(res){
        if(res.success){ showSuccessMessage(res.message||'Student re‑registered.'); setStatusUI(res.academic_status||'active'); $('#reinstateReason').val(''); $('#reinstateDocument').val(''); bootstrap.Modal.getInstance(document.getElementById('reinstateModal')).hide(); }
        else{ showErrorMessage(res.message||'Failed to re‑register.'); }
      }, error:function(){ showErrorMessage('Error while re‑registering student.'); }
    });
  });

  // If user chooses to proceed despite clearances
  $(document).on('click', '#proceedToTerminateFromClearance', function(){
    bootstrap.Modal.getInstance(document.getElementById('terminateClearanceModal')).hide();
    new bootstrap.Modal(document.getElementById('terminateModal')).show();
  });

  // Tab coloring + refresh on every tab click
  function refreshProfileTab(href) {
    switch (href) {
      case '#exams':
        resetExamsTab();
        break;
      case '#attendance':
        resetAttendanceTab();
        break;
      case '#history':
        fetchCourseRegistrationHistory();
        break;
      case '#payment-summary':
        refreshPaymentSummaryTab();
        break;
      case '#clearance':
        fetchStudentClearances();
        break;
      case '#certificates':
        fetchStudentCertificates();
        break;
      case '#status-history':
        fetchStatusHistory();
        break;
    }
  }

  $('#studentTabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    const href = $(e.target).attr('href');
    localStorage.setItem('studentProfileActiveTab', href);
    $('#studentTabs a.nav-link').removeClass('bg-primary text-white');
    $(e.target).addClass('bg-primary text-white');
    refreshProfileTab(href);
  });

  $('#studentTabs a[data-bs-toggle="tab"]').on('hidden.bs.tab', function (e) {
    if ($(e.target).attr('href') === '#payment-summary') {
      resetPaymentSummary();
    }
  });

  var lastTab = localStorage.getItem('studentProfileActiveTab');
  if (lastTab) {
    var tabTrigger = document.querySelector('#studentTabs a[href="' + lastTab + '"]');
    if (tabTrigger) {
      bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
    }
  }

  // Helper function to update profile image
  window.updateStudentProfileImage = function(imagePath) {
    const profileImg = document.getElementById('studentProfilePictureImg');
    if (!profileImg) return;
    if (imagePath) {
      const clean = String(imagePath).replace(/^\/+/, '').replace(/^storage\//, '');
      profileImg.src = '/storage/' + clean + '?' + Date.now();
    } else {
      profileImg.src = '{{ asset("images/profile/user-1.jpg") }}';
    }
  };

  // (email validation helper is defined earlier)

  // Server-rendered profile already has personal/parent/academic fields filled.
  @if($student)
    $('#profileSection').show();
    $('#editPictureBtn').show();
  @endif

  // Profile picture upload functionality
  const studentPictureForm = document.getElementById('studentProfilePictureForm');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  if (studentPictureForm) {
    studentPictureForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Get student ID from the hidden field (populated when student is loaded)
      const studentId = document.getElementById('studentIdHidden')?.value;
      
      if (!studentId) {
        showErrorMessage('Please select a student first');
        return;
      }
      
      const formData = new FormData(studentPictureForm);
      const submitBtn = document.getElementById('saveStudentProfilePictureBtn');
      
      // Disable submit button
      submitBtn.disabled = true;
      submitBtn.textContent = 'Uploading...';
      
      fetch(`/student/profile/update-photo/${studentId}`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
        },
        body: formData
      })
      .then(resp => resp.json())
      .then(data => {
        if (data.success) {
          const profileImg = document.getElementById('studentProfilePictureImg');
          const fileInput = document.getElementById('newStudentProfilePicture');
          if (profileImg) {
            if (fileInput && fileInput.files && fileInput.files[0]) {
              profileImg.src = URL.createObjectURL(fileInput.files[0]);
            } else if (data.url) {
              profileImg.src = data.url + (data.url.includes('?') ? '&' : '?') + Date.now();
            } else if (data.path) {
              updateStudentProfileImage(data.path);
            }
          }
          const modalEl = document.getElementById('editPictureModal');
          const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
          modal.hide();
          showSuccessMessage(data.message);
          studentPictureForm.reset();
        } else {
          showErrorMessage(data.message || 'Failed to update profile picture');
        }
      })
      .catch(error => {
        console.error('Error uploading profile picture:', error);
        showErrorMessage('An error occurred while uploading the profile picture');
      })
      .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = 'Save changes';
      });
    });
  }

  // ---------- O/L and A/L Results Form Handlers ----------
  // Handle O/L results form submission
  $(document).on('submit', '#olResultsForm', function(e) {
    e.preventDefault();
    
    const studentId = $('#studentIdHidden').val();
    if (!studentId) {
      showErrorMessage('No student selected.');
      return;
    }
    
    // Validate required fields
    const indexNo = $('#profile_ol_index_no').val().trim();
    const examType = $('#profile_ol_exam_type').val();
    const examYear = $('#profile_ol_exam_year').val();
    
    if (!examType || !examYear) {
      showErrorMessage('Please fill in all required fields (Exam Type, Exam Year).');
      return;
    }
    
    // Collect subjects from table
    const subjects = [];
    $('#profile_ol_table_body tr').each(function() {
      const subject = $(this).find('td:eq(0)').text();
      const result = $(this).find('td:eq(1)').text();
      if (subject && result) {
        subjects.push({ subject: subject, result: result });
      }
    });
    
    if (subjects.length === 0) {
      showErrorMessage('Please add at least one subject with result.');
      return;
    }
    
    const formData = {
      _token: '{{ csrf_token() }}',
      ol_index_no: indexNo,
      ol_exam_type: examType,
      ol_exam_year: examYear,
      ol_exam_subjects: subjects
    };
    
    $.ajax({
      type: 'POST',
      url: `/student/profile/${studentId}/update-ol-results`,
      data: JSON.stringify(formData),
      contentType: 'application/json',
      success: function(res) {
        if (res.success) {
          showSuccessMessage(res.message || 'O/L exam results updated successfully!');
          hideOLUpdateForm();
          // Reload student data to show updated results
          setTimeout(() => {
            reloadStudentProfile();
          }, 1000);
        } else {
          showErrorMessage(res.message || 'Failed to update O/L results.');
        }
      },
      error: function(xhr) {
        const errorMsg = xhr.responseJSON?.message || 'Error updating O/L results.';
        showErrorMessage(errorMsg);
      }
    });
  });

  // Handle A/L results form submission
  $(document).on('submit', '#alResultsForm', function(e) {
    e.preventDefault();
    
    const studentId = $('#studentIdHidden').val();
    if (!studentId) {
      showErrorMessage('No student selected.');
      return;
    }
    
    // Validate required fields
    const indexNo = $('#profile_al_index_no').val().trim();
    const examType = $('#profile_al_exam_type').val();
    const examYear = $('#profile_al_exam_year').val();
    const stream = $('#profile_al_stream').val();
    
    if (!examType || !examYear || !stream) {
      showErrorMessage('Please fill in all required fields (Exam Type, Exam Year, Stream).');
      return;
    }
    
    // Collect subjects from table
    const subjects = [];
    $('#profile_al_table_body tr').each(function() {
      const subject = $(this).find('td:eq(0)').text();
      const result = $(this).find('td:eq(1)').text();
      if (subject && result) {
        subjects.push({ subject: subject, result: result });
      }
    });
    
    if (subjects.length === 0) {
      showErrorMessage('Please add at least one subject with result.');
      return;
    }
    
    const formData = {
      _token: '{{ csrf_token() }}',
      al_index_no: indexNo,
      al_exam_type: examType,
      al_exam_year: examYear,
      al_stream: stream,
      al_exam_subjects: subjects
    };
    
    $.ajax({
      type: 'POST',
      url: `/student/profile/${studentId}/update-al-results`,
      data: JSON.stringify(formData),
      contentType: 'application/json',
      success: function(res) {
        if (res.success) {
          showSuccessMessage(res.message || 'A/L exam results updated successfully!');
          hideALUpdateForm();
          // Reload student data to show updated results
          setTimeout(() => {
            reloadStudentProfile();
          }, 1000);
        } else {
          showErrorMessage(res.message || 'Failed to update A/L results.');
        }
      },
      error: function(xhr) {
        const errorMsg = xhr.responseJSON?.message || 'Error updating A/L results.';
        showErrorMessage(errorMsg);
      }
    });
  });
});
</script>

{{-- Terminate Modal --}}
<!-- Edit Picture Modal -->
<div class="modal fade student-profile-modal" id="editPictureModal" tabindex="-1" role="dialog" aria-labelledby="editPictureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPictureModalLabel">Edit Profile Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="studentProfilePictureForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="newStudentProfilePicture" class="form-label fw-bold">New Profile Picture</label>
                        <input type="file" class="form-control" id="newStudentProfilePicture" name="profile_picture" accept="image/*" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="studentProfilePictureForm" class="btn btn-primary" id="saveStudentProfilePictureBtn">Save changes</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade student-profile-modal" id="terminateModal" tabindex="-1" aria-labelledby="terminateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="terminateModalLabel">Terminate Student</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <div class="mb-3">
        <label class="form-label">Reason <span class="text-danger">*</span></label>
        <textarea id="terminateReason" class="form-control" rows="4" placeholder="Explain the reason"></textarea>
      </div>
      <div class="mb-2">
        <label class="form-label">Attach document (optional)</label>
        <input type="file" id="terminateDocument" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
      </div>
      <small class="text-muted">This will set academic status to <b>terminated</b>.</small>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      <button type="button" id="confirmTerminate" class="btn btn-danger">Confirm</button>
    </div>
  </div></div>
</div>

{{-- Re‑Register Modal --}}
<div class="modal fade student-profile-modal" id="reinstateModal" tabindex="-1" aria-labelledby="reinstateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="reinstateModalLabel">Re‑Register Student</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <div class="mb-3">
        <label class="form-label">Reason <span class="text-danger">*</span></label>
        <textarea id="reinstateReason" class="form-control" rows="4" placeholder="Why reinstate?"></textarea>
      </div>
      <div class="mb-2">
        <label class="form-label">Attach document (optional)</label>
        <input type="file" id="reinstateDocument" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
      </div>
      <small class="text-muted">This will set academic status to <b>active</b>.</small>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      <button type="button" id="confirmReinstate" class="btn btn-success">Confirm</button>
    </div>
  </div></div>
</div>

<!-- Modal shown when student has existing clearances -->
<div class="modal fade student-profile-modal" id="terminateClearanceModal" tabindex="-1" aria-labelledby="terminateClearanceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="terminateClearanceModalLabel">Student has existing clearances</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
      <p>The selected student has one or more clearance records. Please review them before terminating. Do you still want to proceed?</p>
      <ul id="profileTerminateClearanceList" class="list-unstyled mb-3"></ul>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      <button type="button" class="btn btn-danger" id="proceedToTerminateFromClearance">Yes, Terminate</button>
    </div>
  </div></div>
</div>
@endsection
