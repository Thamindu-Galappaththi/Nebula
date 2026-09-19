@extends('inc.app')

@section('title', 'NEBULA | Student Other Information')

@section('content')
<div class="container-fluid student-other-info-page px-2 px-md-3">
    <div class="row justify-content-center">
        <div class="col-12 col-md-11 mt-2">
            <div class="card">
                <div class="card-body">
                    <h2 class="text-center mb-4">Student Other Information</h2>
                    <hr class="mb-4">

                    <!-- Status banner -->
                    <div id="statusBanner" class="alert d-none mt-2 mb-0" role="alert"></div>

                    {{-- Search --}}
                    <div class="card mb-4">
                        <div class="card-body">
                            <form id="nicSearchForm" class="student-other-search" autocomplete="off">
                                <label for="nicInput" class="form-label">
                                    Student NIC <span class="text-danger">*</span>
                                </label>
                                <div class="student-other-search-controls">
                                    <input type="text" class="form-control bg-white" id="nicInput" name="nic"
                                        placeholder="Enter Student ID (NIC)" required>
                                    <button type="submit" class="btn btn-primary">Search</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Toasts + Spinner + Generic Message Modal --}}
                    <div id="toastContainer" aria-live="polite" aria-atomic="true"
                        style="position: fixed; top: 10px; right: 10px; z-index: 1000;"></div>

                    <div id="spinner-overlay" style="display:none;">
                        <div class="lds-ring">
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </div>

                    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="messageModalLabel">Message</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body" id="messageModalBody"></div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="mt-4">

                    {{-- Main Form --}}
                    <div id="studentOtherInformationForm" style="display:none;">
                        <form id="otherInformationForm" class="p-2 p-md-4 rounded w-100 bg-white mt-2"
                            enctype="multipart/form-data" novalidate>
                            @csrf

                            {{-- Student Details --}}
                            <div class="mb-4">
                                <h5 class="mb-3">Student Details</h5>
                                <div class="mb-3 row g-2 mx-0 align-items-md-center">
                                    <label class="col-12 col-md-3 col-form-label" for="studentNameInput">Name</label>
                                    <div class="col-12 col-md-9">
                                        <input type="text" class="form-control bg-white" id="studentNameInput"
                                            name="studentName" readonly>
                                    </div>
                                </div>
                                <div class="mb-3 row g-2 mx-0 align-items-md-center">
                                    <label class="col-12 col-md-3 col-form-label" for="studentIDInput">Student ID</label>
                                    <div class="col-12 col-md-9">
                                        <input type="text" class="form-control bg-white" id="studentIDInput"
                                            name="studentID" readonly>
                                    </div>
                                </div>
                            </div>

                            {{-- Disciplinary --}}
                            <div class="mb-4">
                                <div class="mb-3 row g-2 mx-0 align-items-md-center">
                                    <label class="col-12 col-md-3 col-form-label" for="disciplinaryIssues">Disciplinary Issues</label>
                                    <div class="col-12 col-md-9">
                                        <textarea class="form-control" id="disciplinaryIssues" name="disciplinaryIssues"
                                            placeholder="Enter disciplinary issues" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="mb-3 row g-2 mx-0 align-items-md-center">
                                    <label class="col-12 col-md-3 col-form-label" for="disciplinary_issue_document">Disciplinary Issue Document</label>
                                    <div class="col-12 col-md-9">
                                        <input type="file" class="form-control" id="disciplinary_issue_document"
                                            name="disciplinary_issue_document" accept=".pdf,.doc,.docx,.jpg,.png">
                                    </div>
                                </div>
                            </div>

                            <hr>

                            {{-- Higher Studies --}}
                            <div class="mb-4">
                                <div class="mb-3 row g-2 mx-0 align-items-md-center">
                                    <label class="col-12 col-md-3 col-form-label">Continue to Higher Studies?</label>
                                    <div class="col-12 col-md-9">
                                        <div class="form-check form-check-inline">
                                            <input value="true" class="form-check-input" type="radio" id="continueYes"
                                                name="continueStudies" required>
                                            <label class="form-check-label" for="continueYes">Yes</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input value="false" class="form-check-input" type="radio" id="continueNo"
                                                name="continueStudies" checked required>
                                            <label class="form-check-label" for="continueNo">No</label>
                                        </div>
                                    </div>
                                </div>

                                <div id="higherStudiesContainer" class="mb-3 mx-0 mx-md-3 bg-light-primary p-3 rounded"
                                    style="display:none;">
                                    <div class="mb-3 row g-2 align-items-md-center">
                                        <label for="institute" class="col-12 col-md-3 col-form-label">Institute <span class="text-danger">*</span></label>
                                        <div class="col-12 col-md-9">
                                            <input type="text" class="form-control bg-white" id="institute"
                                                name="institute" placeholder="Enter institute">
                                        </div>
                                    </div>
                                    <div class="mb-1 row g-2 align-items-md-center">
                                        <label for="fieldOfStudy" class="col-12 col-md-3 col-form-label">Field of Study <span class="text-danger">*</span></label>
                                        <div class="col-12 col-md-9">
                                            <input type="text" class="form-control bg-white" id="fieldOfStudy"
                                                name="fieldOfStudy" placeholder="Enter field of study">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            {{-- Employment --}}
                            <div class="mb-4">
                                <div class="mb-3 row g-2 mx-0 align-items-md-center">
                                    <label class="col-12 col-md-3 col-form-label">Currently an Employee?</label>
                                    <div class="col-12 col-md-9">
                                        <div class="form-check form-check-inline">
                                            <input value="true" class="form-check-input" type="radio" id="employeeYes"
                                                name="currentlyEmployee" required>
                                            <label class="form-check-label" for="employeeYes">Yes</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input value="false" class="form-check-input" type="radio" id="employeeNo"
                                                name="currentlyEmployee" checked required>
                                            <label class="form-check-label" for="employeeNo">No</label>
                                        </div>
                                    </div>
                                </div>

                                <div id="employmentContainer" class="mb-3 mx-0 mx-md-3 bg-light-primary p-3 rounded"
                                    style="display:none;">
                                    <div class="mb-3 row g-2 align-items-md-center">
                                        <label for="jobTitle" class="col-12 col-md-3 col-form-label">Job Title <span class="text-danger">*</span></label>
                                        <div class="col-12 col-md-9">
                                            <input type="text" class="form-control bg-white" id="jobTitle"
                                                name="jobTitle" placeholder="Enter job title" >
                                        </div>
                                    </div>
                                    <div class="mb-1 row g-2 align-items-md-center">
                                        <label for="workplace" class="col-12 col-md-3 col-form-label">Workplace <span class="text-danger">*</span></label>
                                        <div class="col-12 col-md-9">
                                            <input type="text" class="form-control bg-white" id="workplace"
                                                name="workplace" placeholder="Enter workplace">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            {{-- Other Information --}}
                            <div class="mb-4">
                                <div class="mb-3 row g-2 mx-0 align-items-md-center">
                                    <label for="otherInformation" class="col-12 col-md-3 col-form-label">Other Information</label>
                                    <div class="col-12 col-md-9">
                                        <textarea class="form-control" id="otherInformation" name="otherInformation"
                                            placeholder="Enter other information" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="text-center mt-4">
                                <button id="dataSubmit" type="submit" class="btn btn-primary w-100 mt-3">SAVE</button>
                            </div>
                        </form>
                    </div> {{-- /#studentOtherInformationForm --}}
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
    // Toggle Higher Studies
    const continueYes = document.getElementById('continueYes');
    const continueNo = document.getElementById('continueNo');
    const higherContainer = document.getElementById('higherStudiesContainer');
    const institute = document.getElementById('institute');
    const fieldOfStudy = document.getElementById('fieldOfStudy');

    document.querySelectorAll('input[name="continueStudies"]').forEach(r => {
        r.addEventListener('change', () => {
            if(continueYes.checked){
                higherContainer.style.display = 'block';
                institute.setAttribute('required','required');
                fieldOfStudy.setAttribute('required','required');
            } else {
                higherContainer.style.display = 'none';
                institute.removeAttribute('required');
                fieldOfStudy.removeAttribute('required');
            }
        });
    });

    // Toggle Employment
    const employeeYes = document.getElementById('employeeYes');
    const employeeNo = document.getElementById('employeeNo');
    const employmentContainer = document.getElementById('employmentContainer');
    const jobTitle = document.getElementById('jobTitle');
    const workplace = document.getElementById('workplace');

    document.querySelectorAll('input[name="currentlyEmployee"]').forEach(r=>{
        r.addEventListener('change',()=>{
            if(employeeYes.checked){
                employmentContainer.style.display = 'block';
                jobTitle.setAttribute('required','required');
                workplace.setAttribute('required','required');
            } else {
                employmentContainer.style.display = 'none';
                jobTitle.removeAttribute('required');
                workplace.removeAttribute('required');
            }
        });
    });

    // NIC Search
    document.getElementById('nicSearchForm').addEventListener('submit', function(e){
        e.preventDefault();
        const nic = document.getElementById('nicInput').value.trim();
        const wrapper = document.getElementById('studentOtherInformationForm');
        if(!nic){ showMessage('Warning','Please enter NIC'); return; }

        document.getElementById('spinner-overlay').style.display='flex';
        $.ajax({
            type:'POST',
            url:'{{ route("student_management.retrieve.details") }}',
            data:{ _token:'{{ csrf_token() }}', identificationType:'nic', idValue:nic },
            success:function(res){
                document.getElementById('spinner-overlay').style.display='none';
                if(res.success){
                    document.getElementById('studentNameInput').value=res.data.student_name;
                    document.getElementById('studentIDInput').value=res.data.student_id;
                    wrapper.style.display='block';

                    const status = (res.data.academic_status || '').toLowerCase();
                    const banner = document.getElementById('statusBanner');
                    banner.className='alert d-none mt-2 mb-0';
                    document.getElementById('otherInformationForm').classList.remove('bg-terminated');
                    if(status==='terminated'){
                        banner.innerHTML='STUDENT TERMINATED. Do not create a new record. Use Student Profile to Re-Register, or process clearance from All Clearance / Termination Tracking.'
                            + (res.data.profile_url ? ' <a class="alert-link" href="'+res.data.profile_url+'">Open Student Profile</a>' : '');
                        banner.classList.add('alert-terminated');
                        document.getElementById('otherInformationForm').classList.add('bg-terminated');
                    }
                } else {
                    wrapper.style.display='none';
                    showMessage('Warning',res.message);
                }
            },
            error:function(){
                document.getElementById('spinner-overlay').style.display='none';
                showMessage('Error','An error occurred while searching for the student.');
            }
        });
    });

    // SAVE
    document.getElementById('otherInformationForm').addEventListener('submit',function(e){
        e.preventDefault();
        const form=this;
        if(!form.checkValidity()){
            form.reportValidity(); // Show browser native validation
            return;
        }
        const fd=new FormData(form);
        document.getElementById('spinner-overlay').style.display='flex';
        $.ajax({
            type:'POST',
            url:'{{ route("student_management.store.other.informations") }}',
            data:fd,
            processData:false,
            contentType:false,
            success:function(res){
                document.getElementById('spinner-overlay').style.display='none';
                if(res.success){
                    showToast('Success',res.message,'#ccffcc');
                } else {
                    showMessage('Error',res.message);
                }
            },
            error:function(){
                document.getElementById('spinner-overlay').style.display='none';
                showMessage('Error','An error occurred while saving the data.');
            }
        });
    });

    function showMessage(title,message){
        document.getElementById('messageModalLabel').textContent=title;
        document.getElementById('messageModalBody').textContent=message;
        new bootstrap.Modal(document.getElementById('messageModal')).show();
    }

    function showToast(title,message,backgroundColor){
        const toastContainer=document.getElementById('toastContainer');
        const toast=document.createElement('div');
        toast.className='toast'; toast.style.backgroundColor=backgroundColor;
        toast.innerHTML=`
            <div class="toast-header">
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">${message}</div>`;
        toastContainer.appendChild(toast);
        new bootstrap.Toast(toast).show();
        toast.addEventListener('hidden.bs.toast',()=>toast.remove());
    }
</script>

<style nonce="{{ $cspNonce }}">
    .lds-ring { display:inline-block; position:relative; width:80px; height:80px }
    .lds-ring div { box-sizing:border-box; display:block; position:absolute; width:64px; height:64px; margin:8px; border:8px solid #fff; border-radius:50%; animation:lds-ring 1.2s cubic-bezier(.5,0,.5,1) infinite; border-color:#fff transparent transparent transparent }
    .lds-ring div:nth-child(1){ animation-delay:-.45s }
    .lds-ring div:nth-child(2){ animation-delay:-.3s }
    .lds-ring div:nth-child(3){ animation-delay:-.15s }
    @keyframes lds-ring{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}

    #spinner-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background-color:rgba(0,0,0,.5);display:flex;justify-content:center;align-items:center;z-index:9999}

    .bg-light-primary{background-color:#f1f5f9!important}
    .bg-terminated{background:#ffe6e9;border:1px solid #ff9aa7;}
    .alert-terminated{background:#ffccd3;color:#7a0014;border-color:#ff9aa7;font-weight:600;letter-spacing:.3px;}

    .student-other-search-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: stretch;
    }
    .student-other-search-controls .form-control {
        flex: 1 1 12rem;
        min-width: 0;
    }
    .student-other-search-controls .btn {
        flex: 0 0 auto;
        white-space: nowrap;
    }
    @media (max-width: 575.98px) {
        .student-other-search-controls .btn {
            width: 100%;
        }
        #toastContainer {
            left: 10px;
            right: 10px;
        }
    }
</style>
@endsection
