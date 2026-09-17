@extends('inc.app')

@section('title', 'NEBULA | Student Registration')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
        <h2 class="text-center mb-4">Student Registration</h2>
            <hr>

            <div id="spinner-overlay" style="display:none;">
                <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
            </div>

            {{-- Success/Error Modals can be included here if needed --}}

            <form id="registrationForm" action="{{ route('student_management.register') }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf
                <div id="formErrorSummary" class="alert alert-danger d-none" role="alert"></div>
                
                {{-- Personal Information Section --}}
                <h5 class="mb-3">Personal Information</h5>
                
                <div class="row mb-3">
                    <label for="title" class="col-sm-2 col-form-label">Title<span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <select class="form-select" id="title" name="title" required>
                            <option selected disabled value="#">Select a Title</option>
                            @foreach ($titles as $title)
                            <option value="{{ $title['TitleID'] }}">{{ $title['TitleName'] }}</option>
                            @endforeach
                        </select>
                        <div id="titleOtherContainer" class="mt-2" style="display: none;">
                            <input type="text" class="form-control" id="titleOther" name="titleOther" placeholder="Please specify your title">
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="nameWithInitials" class="col-sm-2 col-form-label">Name with Initials<span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="nameWithInitials" name="nameWithInitials" placeholder="J. A. Smith" required>
                            <div id="nameError" class="text-danger" style="display: none;">Please enter a name using letters, periods (.) and spaces only.</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="fullName" class="col-sm-2 col-form-label">Full Name<span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="fullName" name="fullName" placeholder="John Adam Smith" required>
                            <div id="fullNameError" class="text-danger" style="display: none;">Please enter the full name using letters and spaces only.</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="birthday" class="col-sm-2 col-form-label">Birthday<span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <input type="date" class="form-control" id="birthday" name="birthday" required>
                            <div id="birthdayError" class="text-danger" style="display: none;">Please choose a valid birth date (year should be between 1890 and the current year).</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="gender" class="col-sm-2 col-form-label">Gender<span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <select class="form-select" id="gender" name="gender" required>
                            <option selected disabled value="#">Select a Gender</option>
                            @foreach($genders as $gender)
                            <option value="{{ $gender['id'] }}">{{ $gender['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="idValue" class="col-sm-2 col-form-label">ID Value<span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <div class="input-group">
                            <select class="form-select bg-primary text-white" id="identificationType" name="identificationType" style="flex: 0 0 150px;" required>
                                @foreach($idTypes as $idType)
                                <option value="{{ $idType['id'] }}">{{ $idType['id_type'] }}</option>
                                @endforeach
                            </select>
                            <input type="text" class="form-control" id="idValue" name="idValue" placeholder="Enter ID value" required>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="address" class="col-sm-2 col-form-label">Address<span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <textarea class="form-control" id="address" name="address" placeholder="123 Main Street, City, Country" rows="3" required></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="district" class="col-sm-2 col-form-label">District<span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <select class="form-select" id="district" name="district" required>
                            <option selected disabled value="">Select a District</option>
                            @foreach($districts as $district)
                            <option value="{{ $district }}">{{ $district }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="email" class="col-sm-2 col-form-label">Email<span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <input type="email" class="form-control" id="email" name="email" placeholder="example@example.com" required>
                            <div id="emailError" class="text-danger" style="display: none;">Please enter a valid email address (e.g., example@example.com).</div>
                    </div>
                </div>

                <!-- 🔹 Mobile Phone -->
                <div class="row mb-3">
                    <label for="mobilePhone" class="col-sm-2 col-form-label">Mobile Phone No<span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <div class="input-group">
                            <select class="form-select country-code-select" name="mobileCountryCode" style="max-width: 180px;">
                                <!-- Will auto-populate via JS -->
                            </select>
                            <input type="tel" class="form-control" id="mobilePhone" name="mobilePhone" placeholder="Enter phone number" required>
                        </div>
                            <div id="mobilePhoneError" class="text-danger" style="display:none;">Please enter a valid mobile number (7–15 digits). Include the country code if available, e.g. +94XXXXXXXXX.</div>
                    </div>
                </div>

                <!-- 🔹 Home Phone -->
                <div class="row mb-3">
                    <label for="homePhone" class="col-sm-2 col-form-label">Home Phone No<span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <div class="input-group">
                            <select class="form-select country-code-select" name="homeCountryCode" style="max-width: 180px;">
                                <!-- Will auto-populate via JS -->
                            </select>
                            <input type="tel" class="form-control" id="homePhone" name="homePhone" placeholder="Enter phone number">
                        </div>
                            <div id="homePhoneError" class="text-danger" style="display:none;">Please enter a valid home phone number or leave it blank.</div>
                    </div>
                </div>

                <!-- 🔹 WhatsApp Number -->
                <div class="row mb-3">
                    <label for="whatsappPhone" class="col-sm-2 col-form-label">WhatsApp Number<span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <div class="input-group">
                            <select class="form-select country-code-select" name="whatsappCountryCode" style="max-width: 180px;">
                                <!-- Will auto-populate via JS -->
                            </select>
                            <input type="tel" class="form-control" id="whatsappPhone" name="whatsappPhone" placeholder="Enter WhatsApp number" required>
                        </div>
                            <div id="whatsappNumberError" class="text-danger" style="display:none;">Please enter a valid WhatsApp number (7–15 digits). Include +country code if applicable.</div>
                    </div>
                </div>

                <hr class="my-4">

                {{-- Academic Qualifications Section --}}
                <h5 class="mb-3">Academic Qualifications</h5>
                <div class="row mb-3">
                    <label for="pending_result" class="col-sm-2 col-form-label">O/L Result Pending?<span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <select id="pending_result" name="pending_result" class="form-select" required>
                            <option value="" selected disabled>Select an Option</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                </div>

                <div id="ol_details_container" style="display: none;">
                    <div class="accordion" id="olAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="olHeading">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOL" aria-expanded="true" aria-controls="collapseOL">
                                    O/L Exam Details
                                </button>
                            </h2>
                            <div id="collapseOL" class="accordion-collapse collapse show" aria-labelledby="olHeading" data-bs-parent="#olAccordion">
                                <div class="accordion-body">
                                    <div class="row mb-3">
                                        <label for="ol_index_no" class="col-sm-2 col-form-label">Index No.</label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="ol_index_no" name="ol_index_no" placeholder="XXXXXXXXXX">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="ol_exam_type" class="col-sm-2 col-form-label">Exam Type<span class="text-danger">*</span></label>
                                        <div class="col-sm-4">
                                            <select class="form-select" id="ol_exam_type" name="ol_exam_type">
                                                <option selected disabled>Select an Exam Type</option>
                                                @foreach($examTypes as $examType)
                                                <option value="{{ $examType }}">{{ $examType }}</option>
                                                @endforeach
                                            </select>
                                            <div id="olExamTypeOtherContainer" class="mt-2" style="display: none;">
                                                <input type="text" class="form-control" id="olExamTypeOther" name="olExamTypeOther" placeholder="Please specify the exam type">
                                            </div>
                                        </div>
                                        <label for="ol_exam_year" class="col-sm-2 col-form-label text-end">Exam Year<span class="text-danger">*</span></label>
                                        <div class="col-sm-4">
                                            <input type="number" class="form-control" id="ol_exam_year" name="ol_exam_year" placeholder="eg. 2000">
                                        </div>
                                    </div>
                                    <div class="row mb-3 align-items-end">
                                        <label class="col-sm-2 col-form-label">Result<span class="text-danger">*</span></label>
                                        <div class="col-sm-4">
                                            <select class="form-select" id="ol_subject_select">
                                                <option selected disabled>Select a Subject</option>
                                                {{-- Add O/L Subjects here --}}
                                            </select>
                                            <input type="text" class="form-control mt-2" id="ol_subject_other_input" name="ol_subject_other" placeholder="Enter subject name" style="display:none;">
                                        </div>
                                        <div class="col-sm-4">
                                            <select class="form-select" id="ol_result_select">
                                                <option selected disabled>Select a Result</option>
                                                <option>A</option>
                                                <option>B</option>
                                                <option>C</option>
                                                <option>D</option>
                                                <option>S</option>
                                                <option>F</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                                <option>6</option>
                                                <option>7</option>
                                                <option>8</option>
                                                <option>9</option>
                                            </select>
                                            <div id="olResultError" class="text-danger mt-1" style="display:none;">
                                                This subject is already added.
                                            </div>
                                        </div>
                                        <div class="col-sm-2">
                                            <button type="button" class="btn btn-primary w-100" id="ol_add_btn">Add</button>
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
                                                <tbody>
                                                    {{-- JS will add results here --}}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="ol_certificate" class="col-sm-2 col-form-label">O/L Certificate</label>
                                        <div class="col-sm-10">
                                            <input type="file" class="form-control" id="ol_certificate" name="ol_certificate">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="al_pending_question_container" style="display: none;" class="row mb-3">
                    <label for="al_pending_result" class="col-sm-2 col-form-label">A/L Results Pending?<span class="text-danger">*</span></label>
                    <div class="col-sm-10">
                        <select id="al_pending_result" name="al_pending_result" class="form-select">
                            <option value="" selected disabled>Select an Option</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                </div>
                
                <div id="al_details_container" style="display: none;">
                     <div class="accordion" id="alAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="alHeading">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAL" aria-expanded="true" aria-controls="collapseAL">
                                    A/L Exam Details
                                </button>
                            </h2>
                            <div id="collapseAL" class="accordion-collapse collapse show" aria-labelledby="alHeading" data-bs-parent="#alAccordion">
                                <div class="accordion-body">
                                     <div class="row mb-3">
                                        <label for="al_index_no" class="col-sm-2 col-form-label">Index No.</label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="al_index_no" name="al_index_no" placeholder="XXXXXXXXXX">
                                        </div>
                                    </div>
                                     <div class="row mb-3">
                                        <label for="al_exam_type" class="col-sm-2 col-form-label">Exam Type<span class="text-danger">*</span></label>
                                        <div class="col-sm-4">
                                            <select class="form-select" id="al_exam_type" name="al_exam_type">
                                                <option selected disabled>Select an Exam Type</option>
                                                @foreach($examTypes as $examType)
                                                <option value="{{ $examType }}">{{ $examType }}</option>
                                                @endforeach
                                            </select>
                                            <div id="alExamTypeOtherContainer" class="mt-2" style="display: none;">
                                                <input type="text" class="form-control" id="alExamTypeOther" name="alExamTypeOther" placeholder="Please specify the exam type">
                                            </div>
                                        </div>
                                        <label for="al_exam_year" class="col-sm-2 col-form-label text-end">Exam Year<span class="text-danger">*</span></label>
                                        <div class="col-sm-4">
                                            <input type="number" class="form-control" id="al_exam_year" name="al_exam_year" placeholder="eg. 2000">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="al_stream" class="col-sm-2 col-form-label">A/L Stream<span class="text-danger">*</span></label>
                                        <div class="col-sm-10">
                                             <select class="form-select" id="al_stream" name="al_stream">
                                                <option selected disabled>Select an A/L Stream</option>
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
                                            <select class="form-select" id="al_subject_select">
                                                <option selected disabled>Select a Subject</option>
                                                {{-- Add A/L Subjects here --}}
                                            </select>
                                            <input type="text" class="form-control mt-2" id="al_subject_other_input" name="al_subject_other" placeholder="Enter subject name" style="display:none;">
                                        </div>
                                        <div class="col-sm-4">
                                            <select class="form-select" id="al_result_select">
                                                <option selected disabled>Select a Result</option>
                                                <option>A</option>
                                                <option>B</option>
                                                <option>C</option>
                                                <option>D</option>
                                                <option>E</option>
                                                <option>S</option>
                                                <option>F</option>
                                            </select>
                                            <div id="alResultError" class="text-danger mt-1" style="display:none;">
                                                This subject is already added.
                                            </div>
                                        </div>
                                        <div class="col-sm-2">
                                            <button type="button" class="btn btn-primary w-100" id="al_add_btn">Add</button>
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
                                                <tbody>
                                                    {{-- JS will add results here --}}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="al_certificate" class="col-sm-2 col-form-label">A/L Certificate</label>
                                        <div class="col-sm-10">
                                            <input type="file" class="form-control" id="al_certificate" name="al_certificate">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                {{-- Enrollment Details --}}
                <h5 class="mb-3">Enrollment Details</h5>
                <div class="p-3 border rounded mb-3" style="background-color: #eaf6f6;">
                    <div class="row mb-3">
                        <label for="institute_location" class="col-sm-2 col-form-label">Institute<span class="text-danger">*</span></label>
                        <div class="col-sm-10">
                            <select class="form-select" id="institute_location" name="institute_location" required>
                                <option selected disabled>Select an Institute Location</option>
                                @foreach($campuses as $campus)
                                <option value="{{ $campus['id'] }}">{{ $campus['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                </div>

                <hr class="my-4">
                
                {{-- Parent/Guardian Details --}}
                <div class="accordion" id="parentDetailsAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="parentDetailsHeading">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#parentDetailsSection" aria-expanded="false" aria-controls="parentDetailsSection">
                                Parent/Guardian Details
                            </button>
                        </h2>
                       <div id="parentDetailsSection" class="accordion-collapse collapse" aria-labelledby="parentDetailsHeading" data-bs-parent="#parentDetailsAccordion">
                            <div class="accordion-body">
                                <div class="row mb-3">
                                    <label for="parentName" class="col-sm-2 col-form-label">Name<span class="text-danger">*</span></label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="parentName" name="parentName" placeholder="John Doe" required>
                                        <div id="parentNameError" class="text-danger" style="display: none;">Please enter a valid name using letters and spaces only.</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="parentProfession" class="col-sm-2 col-form-label">Profession</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="parentProfession" name="parentProfession" placeholder="Engineer, Doctor, etc...">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="parentContactNo" class="col-sm-2 col-form-label">Contact No<span class="text-danger">*</span></label>
                                    <div class="col-sm-10">
                                        <div class="input-group">
                                            <select class="form-select country-code-select" name="parentCountryCode" style="max-width: 180px;">
                                                <!-- Will auto-populate via JS -->
                                            </select>
                                            <input type="tel" class="form-control" id="parentContactNo" name="parentContactNo" placeholder="Enter parent contact number" required>
                                        </div>
                                        <div id="parentContactNoError" class="text-danger" style="display:none;">Please enter a valid contact number (7–15 digits). Include +country code if applicable.</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="parentEmail" class="col-sm-2 col-form-label">Email</label>
                                    <div class="col-sm-10">
                                        <input type="email" class="form-control" id="parentEmail" name="parentEmail" placeholder="example@example.com">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="parentAddress" class="col-sm-2 col-form-label">Address<span class="text-danger">*</span></label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" id="parentAddress" name="parentAddress" placeholder="123 Main St, City, Country" required></textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="emergencyContactNo" class="col-sm-2 col-form-label">
                                        Emergency Contact No<span class="text-danger">*</span>
                                    </label>
                                    <div class="col-sm-10">
                                        <div class="input-group">
                                            <select class="form-select country-code-select" name="emergencyCountryCode" style="max-width: 180px;">
                                                <!-- Will auto-populate via JS -->
                                            </select>
                                            <input type="tel" class="form-control" id="emergencyContactNo" name="emergencyContactNo" placeholder="Enter emergency contact number" required>
                                        </div>
                                        <div id="emergencyContactNoError" class="text-danger" style="display:none;">Please enter a valid emergency contact number (7–15 digits). Include +country code if applicable.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                {{-- Other Information --}}
                <div class="accordion" id="otherInfoAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="otherInfoHeading">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOtherInfo" aria-expanded="false" aria-controls="collapseOtherInfo">
                                Other Information
                            </button>
                        </h2>
                        <div id="collapseOtherInfo" class="accordion-collapse collapse" aria-labelledby="otherInfoHeading" data-bs-parent="#otherInfoAccordion">
                            <div class="accordion-body">
                                <div class="row mb-3">
                                    <label for="specialNeeds" class="col-sm-2 col-form-label">Special Needs</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="specialNeeds" name="specialNeeds" placeholder="e.g., Dyslexia, physical disability">
                                    </div>
                                </div>
                                 <div class="row mb-3">
                                    <label for="extraCurricular" class="col-sm-2 col-form-label">Extra-Curricular Activities</label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" id="extraCurricular" name="extraCurricular" placeholder="e.g., Sports, clubs, volunteering" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="futurePotential" class="col-sm-2 col-form-label">Future Potential</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="futurePotential" name="futurePotential" placeholder="Enter future potential">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="userPhoto" class="col-sm-2 col-form-label">Upload Photo</label>
                                    <div class="col-sm-10">
                                        <input type="file" class="form-control" id="userPhoto" name="userPhoto" accept="image/*">
                                    </div>
                                </div>
                                 <div class="row mb-3">
                                    <label for="otherDocumentsFiles" class="col-sm-2 col-form-label">Other Documents</label>
                                    <div class="col-sm-10">
                                        <input type="file" class="form-control" id="otherDocumentsFiles" name="otherDocumentsFiles[]" multiple accept=".pdf,.doc,.docx,.jpg,.png">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="remarks" class="col-sm-2 col-form-label">Remarks</label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Enter remarks"></textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
    <label for="marketing_survey" class="col-sm-2 col-form-label">How did you hear about us?</label>
    <div class="col-sm-10">
        <select class="form-select" id="marketing_survey" name="marketing_survey">
            <option selected disabled>Select an option</option>
            <option value="LinkedIn">LinkedIn</option>
            <option value="Facebook">Facebook</option>
            <option value="Radio Advertisement">Radio Advertisement</option>
            <option value="TV advertisement">TV advertisement</option>
            <option value="Other">Other</option>
        </select>
        <input type="text" class="form-control mt-2" id="marketing_survey_other" name="marketing_survey_other" placeholder="Please describe how you heard about us" style="display:none;">
    </div>
</div>

                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Submit Button --}}
                <button type="submit" class="btn btn-primary w-100 mt-4">Register Student</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">


function showToast(message, type) {
    var toastHtml = '<div class="toast align-items-center text-white bg-' + type + ' border-0" role="alert" aria-live="assertive" aria-atomic="true">' +
                      '<div class="d-flex">' +
                        '<div class="toast-body">' + message + '</div>' +
                        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
                      '</div>' +
                    '</div>';
    $('.toast-container').append(toastHtml);
    var toastEl = $('.toast-container .toast').last();
    var toast = new bootstrap.Toast(toastEl);
    toast.show();
}

document.addEventListener('DOMContentLoaded', function() {
    // BTEC Course Selection Toggle
    // Marketing Survey - Handle "Other" option visibility
    const marketingSurveySelect = document.getElementById('marketing_survey');
    const marketingSurveyOther = document.getElementById('marketing_survey_other');
    
    marketingSurveySelect.addEventListener('change', function() {
        if (this.value === 'Other') {
            marketingSurveyOther.style.display = 'block';
            marketingSurveyOther.required = true;
        } else {
            marketingSurveyOther.style.display = 'none';
            marketingSurveyOther.required = false;
        }
    });


    // O/L & A/L sections toggle
    const olPendingSelect = document.getElementById('pending_result');
    const olDetailsContainer = document.getElementById('ol_details_container');
    const alPendingContainer = document.getElementById('al_pending_question_container');
    const alPendingSelect = document.getElementById('al_pending_result');
    const alDetailsContainer = document.getElementById('al_details_container');

    olPendingSelect.addEventListener('change', function() {
        if (this.value === 'no') {
            olDetailsContainer.style.display = 'block';
            alPendingContainer.style.display = 'flex'; // it's a row, so flex for alignment
        } else { // 'yes'
            olDetailsContainer.style.display = 'none';
            alPendingContainer.style.display = 'none';
            alDetailsContainer.style.display = 'none';
            // Also reset the A/L pending dropdown
            alPendingSelect.value = '';
        }
    });

    alPendingSelect.addEventListener('change', function() {
        if (this.value === 'no') {
            alDetailsContainer.style.display = 'block';
        } else { // 'yes'
            alDetailsContainer.style.display = 'none';
        }
    });

    // --- Validation Listeners ---
    function setupValidator(inputId, errorId, pattern) {
        const input = document.getElementById(inputId);
        const error = document.getElementById(errorId);
        input.addEventListener('input', function() {
            if (pattern.test(this.value)) {
                error.style.display = 'none';
            } else {
                error.style.display = 'block';
            }
        });
    }
    // ✅ Format Name With Initials only after typing is done
const nameWithInitialsInput = document.getElementById('nameWithInitials');

nameWithInitialsInput.addEventListener('blur', function () {
    let value = this.value.trim().toUpperCase();

    // Skip if empty
    if (!value) return;

    // Split words by spaces
    let parts = value.split(/\s+/);

    // If only one word, do nothing
    if (parts.length < 2) return;

    // Take the last word as surname
    const lastName = parts.pop();

    // Combine the rest as initials
    let initials = parts.join('');
    initials = initials.replace(/\./g, '').trim(); // remove any existing dots

    // Insert dots between initials
    const dottedInitials = initials.split('').join('.') + '.';

    // Rebuild name with surname
    this.value = `${dottedInitials}${lastName}`;
});


    setupValidator('nameWithInitials', 'nameError', /^[A-Za-z.\s]+$/);
    setupValidator('fullName', 'fullNameError', /^[A-Za-z\s]+$/);
    setupValidator('email', 'emailError', /^[^\s@]+@[^\s@]+\.[^\s@]+$/);
    setupValidator('mobilePhone', 'mobilePhoneError', /^0\d{9}$/);
    setupValidator('homePhone', 'homePhoneError', /^(|0\d{9})$/); // Optional
    setupValidator('whatsappPhone', 'whatsappNumberError', /^0\d{9}$/);
    setupValidator('parentName', 'parentNameError', /^[A-Za-z\s]+$/);
    setupValidator('parentContactNo', 'parentContactNoError', /^0\d{9}$/);
    setupValidator('emergencyContactNo', 'emergencyContactNoError', /^0\d{9}$/);
    
    const birthdayInput = document.getElementById("birthday");
    const birthdayError = document.getElementById("birthdayError");
    birthdayInput.addEventListener('input', function() {
        const year = new Date(this.value).getFullYear();
        if (year >= 1890 && year <= new Date().getFullYear()) {
            birthdayError.style.display = "none";
        } else {
            birthdayError.style.display = "block";
        }
    });

    const olSubjectSelect = document.getElementById('ol_subject_select');
    const alStreamSelect = document.getElementById('al_stream');
    const olSubjectOtherInput = document.getElementById('ol_subject_other_input');
    const alSubjectSelect = document.getElementById('al_subject_select');
    const alSubjectOtherInput = document.getElementById('al_subject_other_input');

    // Define the subject lists
    const localSubjects = [
        'Sinhala',
        'History',
        'Religion',
        'English',
        'Maths',
        'Science',
        'Other'
    ];
    const otherSubjects = [
        'Other'
    ];

    // Define A/L subjects for each stream
    const alStreamSubjects = {
        'Physical Science': ['Combined Maths', 'Chemistry', 'Physics', 'English', 'Other'],
        'Bio Science': ['Biology', 'Physics', 'Chemistry', 'English', 'Other'],
        'Arts': ['Sinhala', 'Political Science', 'English', 'Other'],
        'Commerce': ['Economics', 'Business Studies', 'Accounting', 'English', 'Other'],
        'Technology': ['Science for Technology', 'Bio System Technology', 'Engineering Technology', 'ICT', 'English', 'Other'],
        'Other': ['English', 'Other']
    };

    function populateOlSubjects(subjects) {
        olSubjectSelect.innerHTML = '<option selected disabled>Select a Subject</option>';
        subjects.forEach(function(subject) {
            const option = document.createElement('option');
            option.value = subject;
            option.textContent = subject;
            olSubjectSelect.appendChild(option);
        });
    }

    function populateAlSubjects(subjects) {
        alSubjectSelect.innerHTML = '<option selected disabled>Select a Subject</option>';
        subjects.forEach(function(subject) {
            const option = document.createElement('option');
            option.value = subject;
            option.textContent = subject;
            alSubjectSelect.appendChild(option);
        });
    }

    // Initial population (default to local)
    populateOlSubjects(localSubjects);

    // Listen for stream change
    alStreamSelect.addEventListener('change', function() {
        populateOlSubjects(localSubjects);
        olSubjectOtherInput.style.display = 'none';
        
        // Update A/L subjects based on selected stream
        const selectedStream = alStreamSelect.value;
        const subjects = alStreamSubjects[selectedStream] || alStreamSubjects['Other'];
        populateAlSubjects(subjects);
        alSubjectOtherInput.style.display = 'none';
    });

    // Listen for O/L subject change
    olSubjectSelect.addEventListener('change', function() {
        if (olSubjectSelect.value === 'Other') {
            olSubjectOtherInput.style.display = 'block';
        } else {
            olSubjectOtherInput.style.display = 'none';
        }
    });

    // Listen for A/L subject change
    alSubjectSelect.addEventListener('change', function() {
        if (alSubjectSelect.value === 'Other') {
            alSubjectOtherInput.style.display = 'block';
        } else {
            alSubjectOtherInput.style.display = 'none';
        }
    });


    // Initialize for O/L & A/L subject addition/removal
    setupExamSubjects({
        addBtnId: 'ol_add_btn',
        subjectSelectId: 'ol_subject_select',
        subjectOtherInputId: 'ol_subject_other_input',
        resultSelectId: 'ol_result_select',
        tableBodySelector: '#ol_details_container table tbody',
        errorId: 'olResultError',
        indexNoId: 'ol_index_no',
        examTypeId: 'ol_exam_type',
        examYearId: 'ol_exam_year'
    });

    setupExamSubjects({
        addBtnId: 'al_add_btn',
        subjectSelectId: 'al_subject_select',
        subjectOtherInputId: 'al_subject_other_input',
        resultSelectId: 'al_result_select',
        tableBodySelector: '#al_details_container table tbody',
        errorId: 'alResultError',
        indexNoId: 'al_index_no',
        examTypeId: 'al_exam_type',
        examYearId: 'al_exam_year'
    });

});

// Handle "Other" option for title field
document.getElementById('title').addEventListener('change', function() {
    const titleOtherContainer = document.getElementById('titleOtherContainer');
    const titleOtherInput = document.getElementById('titleOther');
    
    if (this.value === 'Other') {
        titleOtherContainer.style.display = 'block';
        titleOtherInput.required = true;
    } else {
        titleOtherContainer.style.display = 'none';
        titleOtherInput.required = false;
        titleOtherInput.value = '';
    }
});

// Handle "Other" option for OL exam type field
document.getElementById('ol_exam_type').addEventListener('change', function() {
    const olExamTypeOtherContainer = document.getElementById('olExamTypeOtherContainer');
    const olExamTypeOtherInput = document.getElementById('olExamTypeOther');
    
    if (this.value === 'Other') {
        olExamTypeOtherContainer.style.display = 'block';
        olExamTypeOtherInput.required = true;
    } else {
        olExamTypeOtherContainer.style.display = 'none';
        olExamTypeOtherInput.required = false;
        olExamTypeOtherInput.value = '';
    }
});

// Handle "Other" option for AL exam type field
document.getElementById('al_exam_type').addEventListener('change', function() {
    const alExamTypeOtherContainer = document.getElementById('alExamTypeOtherContainer');
    const alExamTypeOtherInput = document.getElementById('alExamTypeOther');
    
    if (this.value === 'Other') {
        alExamTypeOtherContainer.style.display = 'block';
        alExamTypeOtherInput.required = true;
    } else {
        alExamTypeOtherContainer.style.display = 'none';
        alExamTypeOtherInput.required = false;
        alExamTypeOtherInput.value = '';
    }
});


function setupExamSubjects(config) {
    const { addBtnId, subjectSelectId, subjectOtherInputId, resultSelectId, 
            tableBodySelector, errorId, indexNoId, examTypeId, examYearId } = config;

    const addBtn = document.getElementById(addBtnId);
    const subjectSelect = document.getElementById(subjectSelectId);
    const subjectOtherInput = document.getElementById(subjectOtherInputId);
    const resultSelect = document.getElementById(resultSelectId);
    const tableBody = document.querySelector(tableBodySelector);
    const errorEl = document.getElementById(errorId);

    // Validation fields
    const indexNoInput = document.getElementById(indexNoId);
    const examTypeSelect = document.getElementById(examTypeId);
    const examYearInput = document.getElementById(examYearId);

    // Helper: show error under input
    function showError(inputEl, message) {
        let errorDiv = inputEl.parentNode.querySelector('.field-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'text-danger mt-1 field-error';
            inputEl.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }

    // Helper: hide error
    function hideError(inputEl) {
        const errorDiv = inputEl.parentNode.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }

    addBtn.addEventListener('click', function () {
        let hasError = false;

        // ✅ Validate exam type
        if (!examTypeSelect.value || examTypeSelect.value === 'Select an Exam Type') {
            showError(examTypeSelect, "Please select the exam type.");
            hasError = true;
        } else {
            hideError(examTypeSelect);
        }

        // ✅ Validate exam year (4-digit, reasonable bounds, and after birth year)
        const yearStr = (examYearInput.value || '').toString().trim();
        const yearNum = parseInt(yearStr, 10);
        const nowYear = new Date().getFullYear();
        const birthVal = (document.getElementById('birthday') || {}).value || '';
        let birthYear = null;
        if (birthVal) {
            const parts = birthVal.split('-');
            if (parts.length >= 1) birthYear = parseInt(parts[0], 10);
        }

        if (!/^[0-9]{4}$/.test(yearStr) || isNaN(yearNum) || yearNum < 1900 || yearNum > (nowYear + 1)) {
            showError(examYearInput, "Please enter a 4-digit exam year between 1900 and next year.");
            hasError = true;
        } else if (!birthYear) {
            showError(examYearInput, "Please enter the student's date of birth first so we can validate the exam year.");
            hasError = true;
        } else if (yearNum <= birthYear) {
            showError(examYearInput, "Exam year must be later than the student's year of birth.");
            hasError = true;
        } else {
            hideError(examYearInput);
        }

        if (hasError) return; // stop if any validation failed

        let subject = subjectSelect.value;
        let result = resultSelect.value;
        let subjectOther = subjectOtherInput.value;

        if (subject === 'Other') subject = subjectOther;

        if (!subject || !result || subject === 'Select a Subject' || result === 'Select a Result') {
            showError(resultSelect, "Please select both a subject and its result before adding.");
            return;
        } else {
            hideError(resultSelect);
        }

        // ✅ Check duplicates
        let exists = false;
        tableBody.querySelectorAll('tr').forEach(row => {
            if (row.querySelector('td').textContent === subject) {
                exists = true;
            }
        });

        if (exists) {
            errorEl.style.display = 'block';
            return;
        } else {
            errorEl.style.display = 'none';
        }

        // Add row
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${subject}</td>
            <td>${result}</td>
            <td><button type="button" class="btn btn-danger btn-sm remove-btn">Remove</button></td>
        `;
        tableBody.appendChild(row);

        // Reset inputs
        subjectSelect.value = 'Select a Subject';
        resultSelect.value = 'Select a Result';
        subjectOtherInput.value = '';
        subjectOtherInput.style.display = 'none';
    });

    tableBody.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-btn')) {
            e.target.closest('tr').remove();
            errorEl.style.display = 'none';
        }
    });
}



$(document).ready(function() {
    // Ensure invalid fields inside collapsed accordions are made visible
    function ensureVisibleInvalidFields(formEl){
        const invalidEls = formEl.querySelectorAll(':invalid');
        invalidEls.forEach((el)=>{
            const collapse = el.closest('.collapse');
            if(collapse && !collapse.classList.contains('show')){
                try { new bootstrap.Collapse(collapse, { toggle: true }); } catch(_) { collapse.classList.add('show'); }
                const toggleBtn = document.querySelector(`[data-bs-target="#${collapse.id}"]`);
                if (toggleBtn) toggleBtn.classList.remove('collapsed');
            }
        });
        if(invalidEls.length){
            const target = invalidEls[0];
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            try { target.focus({ preventScroll: true }); } catch(_) {}
        }
    }
    @if(session('success'))
        showToast("{{ session('success') }}", 'success');
    @endif

    // Helper: show server-side validation errors inline
    function showServerErrors(errorBag){
        const form = document.getElementById('registrationForm');
        const summary = document.getElementById('formErrorSummary');
        // Reset previous invalid states
        form.querySelectorAll('.is-invalid').forEach(el=>el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback.dynamic').forEach(el=>el.remove());

        const list = document.createElement('ul');
        list.className = 'mb-0 ps-3';
        Object.entries(errorBag).forEach(([name, messages])=>{
            const msg = Array.isArray(messages) ? messages[0] : messages;
            const li = document.createElement('li');
            li.textContent = msg;
            list.appendChild(li);

            const field = form.querySelector(`[name="${name}"]`);
            if(field){
                // Make sure hidden/collapsed panels are visible first
                const collapse = field.closest('.collapse');
                if(collapse && !collapse.classList.contains('show')){
                    try { new bootstrap.Collapse(collapse, { toggle: true }); } catch(_) { collapse.classList.add('show'); }
                    const toggleBtn = document.querySelector(`[data-bs-target="#${collapse.id}"]`);
                    if (toggleBtn) toggleBtn.classList.remove('collapsed');
                }
                field.classList.add('is-invalid');
                const fb = document.createElement('div');
                fb.className = 'invalid-feedback dynamic';
                fb.textContent = msg;
                if(field.parentNode) field.parentNode.appendChild(fb);
            }
        });
        summary.innerHTML = '';
        summary.appendChild(list);
        summary.classList.remove('d-none');
        summary.scrollIntoView({ behavior:'smooth', block:'start' });
    }
    // ✅ Phone number validator
function setupPhoneValidator(inputId, errorId) {
    const input = document.getElementById(inputId);
    const error = document.getElementById(errorId);
        input.addEventListener('input', function () {
        const value = this.value.trim();
        const isValid = /^(\+?\d{7,15})$/.test(value); // allow + and 7–15 digits
        if (isValid) {
            error.style.display = 'none';
        } else {
            error.style.display = 'block';
            error.textContent = 'Please enter a phone number with 7–15 digits; include a leading +country code if available (e.g., +94XXXXXXXXX).';
        }
    });
}

// Apply to all phone inputs
setupPhoneValidator('mobilePhone', 'mobilePhoneError');
setupPhoneValidator('homePhone', 'homePhoneError');
setupPhoneValidator('whatsappPhone', 'whatsappNumberError');
setupPhoneValidator('parentContactNo', 'parentContactNoError');
setupPhoneValidator('emergencyContactNo', 'emergencyContactNoError');


    $('#registrationForm').on('submit', function(e) {
        e.preventDefault();
        const form = this;

        // Run HTML5 validation manually and show messages inline
        ensureVisibleInvalidFields(form);
        // Build client-side inline feedback and summary
        const summary = document.getElementById('formErrorSummary');
        // clear old states
        form.querySelectorAll('.is-invalid').forEach(el=>el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback.dynamic').forEach(el=>el.remove());
        summary.classList.add('d-none');
        summary.innerHTML = '';

        // Explicit required-fields check (some inputs may not rely on HTML5 validity because form has novalidate)
        const requiredIds = [
            'title','nameWithInitials','fullName','birthday','gender',
            'identificationType','idValue','address','district','email','mobilePhone',
            'pending_result','institute_location','parentName','parentContactNo','parentAddress','emergencyContactNo'
        ];
        const requiredErrors = [];
        requiredIds.forEach(function(id){
            const field = document.getElementById(id) || document.querySelector(`[name="${id}"]`);
            if (!field) return; // field not present on page

            // File input
            if (field.tagName === 'INPUT' && field.type === 'file') {
                if (field.required && (!field.files || field.files.length === 0)) {
                    requiredErrors.push(`${(field.previousElementSibling && field.previousElementSibling.textContent.trim()) || id}: Please attach the required file.`);
                    field.classList.add('is-invalid');
                    const fb = document.createElement('div'); fb.className = 'invalid-feedback dynamic'; fb.textContent = 'This file is required.'; if (field.parentNode) field.parentNode.appendChild(fb);
                }
                return;
            }

            // For radio groups or selects we check value
            let val = '';
            if (field.tagName === 'SELECT') {
                val = (field.value || '').toString().trim();
                // treat disabled placeholder values as empty
                if (!val || val === '#' || val.toLowerCase().includes('select')) val = '';
            } else if (field.type === 'radio' || field.type === 'checkbox') {
                const name = field.name;
                const checked = form.querySelectorAll(`[name="${name}"]:checked`).length > 0;
                if (!checked) {
                    requiredErrors.push(`${(document.querySelector(`label[for="${field.id}"]`) && document.querySelector(`label[for="${field.id}"]`).textContent.trim()) || name}: Please make a selection.`);
                }
                return;
            } else {
                val = (field.value || '').toString().trim();
            }

            if (!val) {
                const labelEl = document.querySelector(`label[for="${field.id}"]`);
                const labelText = labelEl ? labelEl.textContent.replace('*','').trim() : id;
                requiredErrors.push(`${labelText}: This field is required.`);
                field.classList.add('is-invalid');
                const fb = document.createElement('div'); fb.className = 'invalid-feedback dynamic'; fb.textContent = 'This field is required.'; if (field.parentNode) field.parentNode.appendChild(fb);
            }
        });
        if (requiredErrors.length) {
            const list = document.createElement('ul'); list.className = 'mb-0 ps-3'; requiredErrors.forEach(m=>{ const li=document.createElement('li'); li.textContent = m; list.appendChild(li); });
            summary.appendChild(list); summary.classList.remove('d-none'); summary.scrollIntoView({ behavior:'smooth', block:'start' });
            return; // stop submission
        }

        // Filter out optional file inputs from validation
        const invalidEls = Array.from(form.querySelectorAll('input, select, textarea')).filter(el=>{
            if (!el.checkValidity()) {
                // Allow optional file inputs (not required)
                if (el.tagName === 'INPUT' && el.type === 'file' && !el.required) {
                    return false; // exclude from validation errors
                }
                return true;
            }
            return false;
        });
        if (invalidEls.length) {
            const list = document.createElement('ul');
            list.className = 'mb-0 ps-3';
            invalidEls.forEach(el=>{
                // attach inline
                el.classList.add('is-invalid');
                const fb = document.createElement('div');
                fb.className = 'invalid-feedback dynamic';
                fb.textContent = el.validationMessage || 'This field is required.';
                // Put feedback after the control
                if (el.parentNode) el.parentNode.appendChild(fb);
                // Add to summary
                const label = form.querySelector(`label[for="${el.id}"]`);
                const labelText = label ? label.textContent.replace('*','').trim() : (el.name || 'Field');
                const li = document.createElement('li');
                li.textContent = `${labelText}: ${fb.textContent}`;
                list.appendChild(li);
            });
            summary.appendChild(list);
            summary.classList.remove('d-none');
            // focus first invalid
            invalidEls[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            try { invalidEls[0].focus({ preventScroll: true }); } catch(_) {}
            return; // stop here until user fixes errors
        }

        // Additional client-side validation: ensure OL/AL exam years (if provided) are after birth year
        const birthVal2 = (document.getElementById('birthday') || {}).value || '';
        let birthYear2 = null;
        if (birthVal2) {
            const parts2 = birthVal2.split('-');
            if (parts2.length >= 1) birthYear2 = parseInt(parts2[0], 10);
        }
        const nowYear2 = new Date().getFullYear();
        const examYearFields = [ 'ol_exam_year', 'al_exam_year' ];
        const examErrors = [];
        examYearFields.forEach(function(fid){
            const fld = document.getElementById(fid);
            if (!fld) return;
            const val = (fld.value || '').toString().trim();
            if (!val) return; // not provided
            const n = parseInt(val, 10);
            const label = fid === 'ol_exam_year' ? 'O/L exam year' : (fid === 'al_exam_year' ? 'A/L exam year' : fid.replace('_',' '));
            if (!/^[0-9]{4}$/.test(val) || isNaN(n) || n < 1900 || n > (nowYear2 + 1)) {
                examErrors.push(`${label}: Please enter a 4-digit year between 1900 and ${nowYear2 + 1}.`);
                fld.classList.add('is-invalid');
                const fb = document.createElement('div'); fb.className = 'invalid-feedback dynamic'; fb.textContent = `Please enter a 4-digit year between 1900 and ${nowYear2 + 1}.`; if (fld.parentNode) fld.parentNode.appendChild(fb);
            } else if (!birthYear2) {
                examErrors.push('Please enter the student\'s date of birth first so exam years can be validated.');
                fld.classList.add('is-invalid');
                const fb = document.createElement('div'); fb.className = 'invalid-feedback dynamic'; fb.textContent = `Please enter the student's date of birth first so exam years can be validated.`; if (fld.parentNode) fld.parentNode.appendChild(fb);
            } else if (n <= birthYear2) {
                examErrors.push(`${label}: Exam year must be later than the student's year of birth (${birthYear2}).`);
                fld.classList.add('is-invalid');
                const fb = document.createElement('div'); fb.className = 'invalid-feedback dynamic'; fb.textContent = `Exam year must be later than the student's year of birth (${birthYear2}).`; if (fld.parentNode) fld.parentNode.appendChild(fb);
            }
        });
        if (examErrors.length) {
            const list = document.createElement('ul'); list.className = 'mb-0 ps-3'; examErrors.forEach(m=>{ const li=document.createElement('li'); li.textContent = m; list.appendChild(li); });
            summary.appendChild(list); summary.classList.remove('d-none'); summary.scrollIntoView({ behavior:'smooth', block:'start' });
            return; // stop submission
        }

        // Certificate files are now optional

        var formData = new FormData(form);
        
        // Handle "Other" values for title and exam types
        var title = $('#title').val();
        if (title === 'Other') {
            var titleOther = $('#titleOther').val();
            if (titleOther.trim()) {
                formData.set('title', titleOther);
            }
        }
        
        var olExamType = $('#ol_exam_type').val();
        if (olExamType === 'Other') {
            var olExamTypeOther = $('#olExamTypeOther').val();
            if (olExamTypeOther.trim()) {
                formData.set('ol_exam_type', olExamTypeOther);
            }
        }
        
        var alExamType = $('#al_exam_type').val();
        if (alExamType === 'Other') {
            var alExamTypeOther = $('#alExamTypeOther').val();
            if (alExamTypeOther.trim()) {
                formData.set('al_exam_type', alExamTypeOther);
            }
        }

        // Collect O/L subjects and results from the table
        var olSubjects = [];
        var olResults = [];
        $('#ol_details_container table tbody tr').each(function() {
            var subject = $(this).find('td').eq(0).text();
            var result = $(this).find('td').eq(1).text();
            if(subject && result) {
                olSubjects.push(subject);
                olResults.push(result);
            }
        });
        // Append to FormData
        olSubjects.forEach(function(subject) {
            formData.append('ol_subjects[]', subject);
        });
        olResults.forEach(function(result) {
            formData.append('ol_results[]', result);
        });

        // Collect A/L subjects and results from the table
        var alSubjects = [];
        var alResults = [];
        $('#al_details_container table tbody tr').each(function() {
            var subject = $(this).find('td').eq(0).text();
            var result = $(this).find('td').eq(1).text();
            if(subject && result) {
                alSubjects.push(subject);
                alResults.push(result);
            }
        });
        // Append to FormData
        alSubjects.forEach(function(subject) {
            formData.append('al_subjects[]', subject);
        });
        alResults.forEach(function(result) {
            formData.append('al_results[]', result);
        });
        // ✅ Combine country code + number before submit
['mobilePhone', 'homePhone', 'whatsappPhone', 'parentContactNo', 'emergencyContactNo'].forEach(function(field) {
    const input = document.getElementById(field);
    if (input) {
        const select = input.closest('.input-group').querySelector('.country-code-select');
        if (select) {
            let number = input.value.trim();
            const code = select.value || '+94';
            if (!number.startsWith('+')) {
                number = code + number.replace(/^0+/, '');
            }
            formData.set(field, number);
        }
    }
});

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'Accept': 'application/json' },
            
            success: function(response) {
                // clear summary on success
                const summary = document.getElementById('formErrorSummary');
                summary.classList.add('d-none');
                summary.innerHTML = '';
                showToast(response.message || 'Student registered successfully!', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            },
            error: function(xhr) {
                // 422 Unprocessable Entity from Laravel validation
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showServerErrors(xhr.responseJSON.errors);
                    const existing = xhr.responseJSON.existing_student;
                    if (existing && existing.profile_url) {
                        const summary = document.getElementById('formErrorSummary');
                        const actions = document.createElement('div');
                        actions.className = 'mt-2';
                        const profileLink = document.createElement('a');
                        profileLink.href = existing.profile_url;
                        profileLink.className = 'btn btn-primary btn-sm';
                        profileLink.textContent = 'Open existing Student Profile';
                        actions.appendChild(profileLink);
                        summary.appendChild(actions);
                        summary.classList.remove('d-none');
                    }
                    return;
                }
                let errorMessage = 'Validation failed';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showToast(errorMessage, 'danger');
            }
        });
    });
});

// --- 🌍 Country Calling Codes List ---
const countryCodes = [
  { "name": "Afghanistan", "code": "+93" },
  { "name": "Albania", "code": "+355" },
  { "name": "Algeria", "code": "+213" },
  { "name": "American Samoa", "code": "+1-684" },
  { "name": "Andorra", "code": "+376" },
  { "name": "Angola", "code": "+244" },
  { "name": "Anguilla", "code": "+1-264" },
  { "name": "Antigua and Barbuda", "code": "+1-268" },
  { "name": "Argentina", "code": "+54" },
  { "name": "Armenia", "code": "+374" },
  { "name": "Aruba", "code": "+297" },
  { "name": "Australia", "code": "+61" },
  { "name": "Austria", "code": "+43" },
  { "name": "Azerbaijan", "code": "+994" },
  { "name": "Bahamas", "code": "+1-242" },
  { "name": "Bahrain", "code": "+973" },
  { "name": "Bangladesh", "code": "+880" },
  { "name": "Barbados", "code": "+1-246" },
  { "name": "Belarus", "code": "+375" },
  { "name": "Belgium", "code": "+32" },
  { "name": "Belize", "code": "+501" },
  { "name": "Benin", "code": "+229" },
  { "name": "Bermuda", "code": "+1-441" },
  { "name": "Bhutan", "code": "+975" },
  { "name": "Bolivia", "code": "+591" },
  { "name": "Bosnia and Herzegovina", "code": "+387" },
  { "name": "Botswana", "code": "+267" },
  { "name": "Brazil", "code": "+55" },
  { "name": "British Virgin Islands", "code": "+1-284" },
  { "name": "Brunei", "code": "+673" },
  { "name": "Bulgaria", "code": "+359" },
  { "name": "Burkina Faso", "code": "+226" },
  { "name": "Burundi", "code": "+257" },
  { "name": "Cambodia", "code": "+855" },
  { "name": "Cameroon", "code": "+237" },
  { "name": "Canada", "code": "+1" },
  { "name": "Cape Verde", "code": "+238" },
  { "name": "Cayman Islands", "code": "+1-345" },
  { "name": "Central African Republic", "code": "+236" },
  { "name": "Chad", "code": "+235" },
  { "name": "Chile", "code": "+56" },
  { "name": "China", "code": "+86" },
  { "name": "Colombia", "code": "+57" },
  { "name": "Comoros", "code": "+269" },
  { "name": "Congo (Democratic Republic)", "code": "+243" },
  { "name": "Congo (Republic)", "code": "+242" },
  { "name": "Cook Islands", "code": "+682" },
  { "name": "Costa Rica", "code": "+506" },
  { "name": "Croatia", "code": "+385" },
  { "name": "Cuba", "code": "+53" },
  { "name": "Cyprus", "code": "+357" },
  { "name": "Czech Republic", "code": "+420" },
  { "name": "Denmark", "code": "+45" },
  { "name": "Djibouti", "code": "+253" },
  { "name": "Dominica", "code": "+1-767" },
  { "name": "Dominican Republic", "code": "+1-809" },
  { "name": "Ecuador", "code": "+593" },
  { "name": "Egypt", "code": "+20" },
  { "name": "El Salvador", "code": "+503" },
  { "name": "Equatorial Guinea", "code": "+240" },
  { "name": "Eritrea", "code": "+291" },
  { "name": "Estonia", "code": "+372" },
  { "name": "Eswatini", "code": "+268" },
  { "name": "Ethiopia", "code": "+251" },
  { "name": "Fiji", "code": "+679" },
  { "name": "Finland", "code": "+358" },
  { "name": "France", "code": "+33" },
  { "name": "French Guiana", "code": "+594" },
  { "name": "French Polynesia", "code": "+689" },
  { "name": "Gabon", "code": "+241" },
  { "name": "Gambia", "code": "+220" },
  { "name": "Georgia", "code": "+995" },
  { "name": "Germany", "code": "+49" },
  { "name": "Ghana", "code": "+233" },
  { "name": "Gibraltar", "code": "+350" },
  { "name": "Greece", "code": "+30" },
  { "name": "Greenland", "code": "+299" },
  { "name": "Grenada", "code": "+1-473" },
  { "name": "Guadeloupe", "code": "+590" },
  { "name": "Guam", "code": "+1-671" },
  { "name": "Guatemala", "code": "+502" },
  { "name": "Guernsey", "code": "+44-1481" },
  { "name": "Guinea", "code": "+224" },
  { "name": "Guinea-Bissau", "code": "+245" },
  { "name": "Guyana", "code": "+592" },
  { "name": "Haiti", "code": "+509" },
  { "name": "Honduras", "code": "+504" },
  { "name": "Hong Kong", "code": "+852" },
  { "name": "Hungary", "code": "+36" },
  { "name": "Iceland", "code": "+354" },
  { "name": "India", "code": "+91" },
  { "name": "Indonesia", "code": "+62" },
  { "name": "Iran", "code": "+98" },
  { "name": "Iraq", "code": "+964" },
  { "name": "Ireland", "code": "+353" },
  { "name": "Isle of Man", "code": "+44-1624" },
  { "name": "Israel", "code": "+972" },
  { "name": "Italy", "code": "+39" },
  { "name": "Jamaica", "code": "+1-876" },
  { "name": "Japan", "code": "+81" },
  { "name": "Jersey", "code": "+44-1534" },
  { "name": "Jordan", "code": "+962" },
  { "name": "Kazakhstan", "code": "+7" },
  { "name": "Kenya", "code": "+254" },
  { "name": "Kiribati", "code": "+686" },
  { "name": "Kuwait", "code": "+965" },
  { "name": "Kyrgyzstan", "code": "+996" },
  { "name": "Laos", "code": "+856" },
  { "name": "Latvia", "code": "+371" },
  { "name": "Lebanon", "code": "+961" },
  { "name": "Lesotho", "code": "+266" },
  { "name": "Liberia", "code": "+231" },
  { "name": "Libya", "code": "+218" },
  { "name": "Liechtenstein", "code": "+423" },
  { "name": "Lithuania", "code": "+370" },
  { "name": "Luxembourg", "code": "+352" },
  { "name": "Macau", "code": "+853" },
  { "name": "Madagascar", "code": "+261" },
  { "name": "Malawi", "code": "+265" },
  { "name": "Malaysia", "code": "+60" },
  { "name": "Maldives", "code": "+960" },
  { "name": "Mali", "code": "+223" },
  { "name": "Malta", "code": "+356" },
  { "name": "Marshall Islands", "code": "+692" },
  { "name": "Martinique", "code": "+596" },
  { "name": "Mauritania", "code": "+222" },
  { "name": "Mauritius", "code": "+230" },
  { "name": "Mayotte", "code": "+262" },
  { "name": "Mexico", "code": "+52" },
  { "name": "Micronesia", "code": "+691" },
  { "name": "Moldova", "code": "+373" },
  { "name": "Monaco", "code": "+377" },
  { "name": "Mongolia", "code": "+976" },
  { "name": "Montenegro", "code": "+382" },
  { "name": "Montserrat", "code": "+1-664" },
  { "name": "Morocco", "code": "+212" },
  { "name": "Mozambique", "code": "+258" },
  { "name": "Myanmar (Burma)", "code": "+95" },
  { "name": "Namibia", "code": "+264" },
  { "name": "Nauru", "code": "+674" },
  { "name": "Nepal", "code": "+977" },
  { "name": "Netherlands", "code": "+31" },
  { "name": "New Caledonia", "code": "+687" },
  { "name": "New Zealand", "code": "+64" },
  { "name": "Nicaragua", "code": "+505" },
  { "name": "Niger", "code": "+227" },
  { "name": "Nigeria", "code": "+234" },
  { "name": "North Korea", "code": "+850" },
  { "name": "North Macedonia", "code": "+389" },
  { "name": "Norway", "code": "+47" },
  { "name": "Oman", "code": "+968" },
  { "name": "Pakistan", "code": "+92" },
  { "name": "Palau", "code": "+680" },
  { "name": "Palestine", "code": "+970" },
  { "name": "Panama", "code": "+507" },
  { "name": "Papua New Guinea", "code": "+675" },
  { "name": "Paraguay", "code": "+595" },
  { "name": "Peru", "code": "+51" },
  { "name": "Philippines", "code": "+63" },
  { "name": "Poland", "code": "+48" },
  { "name": "Portugal", "code": "+351" },
  { "name": "Puerto Rico", "code": "+1-787" },
  { "name": "Qatar", "code": "+974" },
  { "name": "Réunion", "code": "+262" },
  { "name": "Romania", "code": "+40" },
  { "name": "Russia", "code": "+7" },
  { "name": "Rwanda", "code": "+250" },
  { "name": "Saint Kitts and Nevis", "code": "+1-869" },
  { "name": "Saint Lucia", "code": "+1-758" },
  { "name": "Saint Martin", "code": "+590" },
  { "name": "Saint Vincent and the Grenadines", "code": "+1-784" },
  { "name": "Samoa", "code": "+685" },
  { "name": "San Marino", "code": "+378" },
  { "name": "Saudi Arabia", "code": "+966" },
  { "name": "Senegal", "code": "+221" },
  { "name": "Serbia", "code": "+381" },
  { "name": "Seychelles", "code": "+248" },
  { "name": "Sierra Leone", "code": "+232" },
  { "name": "Singapore", "code": "+65" },
  { "name": "Slovakia", "code": "+421" },
  { "name": "Slovenia", "code": "+386" },
  { "name": "Solomon Islands", "code": "+677" },
  { "name": "Somalia", "code": "+252" },
  { "name": "South Africa", "code": "+27" },
  { "name": "South Korea", "code": "+82" },
  { "name": "South Sudan", "code": "+211" },
  { "name": "Spain", "code": "+34" },
  { "name": "Sri Lanka", "code": "+94" },
  { "name": "Sudan", "code": "+249" },
  { "name": "Suriname", "code": "+597" },
  { "name": "Sweden", "code": "+46" },
  { "name": "Switzerland", "code": "+41" },
  { "name": "Syria", "code": "+963" },
  { "name": "Taiwan", "code": "+886" },
  { "name": "Tajikistan", "code": "+992" },
  { "name": "Tanzania", "code": "+255" },
  { "name": "Thailand", "code": "+66" },
  { "name": "Timor-Leste", "code": "+670" },
  { "name": "Togo", "code": "+228" },
  { "name": "Tonga", "code": "+676" },
  { "name": "Trinidad and Tobago", "code": "+1-868" },
  { "name": "Tunisia", "code": "+216" },
  { "name": "Turkey", "code": "+90" },
  { "name": "Turkmenistan", "code": "+993" },
  { "name": "Turks and Caicos Islands", "code": "+1-649" },
  { "name": "Tuvalu", "code": "+688" },
  { "name": "Uganda", "code": "+256" },
  { "name": "Ukraine", "code": "+380" },
  { "name": "United Arab Emirates", "code": "+971" },
  { "name": "United Kingdom", "code": "+44" },
  { "name": "United States", "code": "+1" },
  { "name": "Uruguay", "code": "+598" },
  { "name": "Uzbekistan", "code": "+998" },
  { "name": "Vanuatu", "code": "+678" },
  { "name": "Vatican City", "code": "+379" },
  { "name": "Venezuela", "code": "+58" },
  { "name": "Vietnam", "code": "+84" },
  { "name": "Yemen", "code": "+967" },
  { "name": "Zambia", "code": "+260" },
  { "name": "Zimbabwe", "code": "+263" },
  { "name": "Other / Custom", "code": "" }
];


// --- 🧩 Populate all selects with class .country-code-select ---
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".country-code-select").forEach(select => {
    select.innerHTML = ""; // clear existing options

    countryCodes.forEach(c => {
      const opt = document.createElement("option");
      opt.value = c.code || "";
      opt.textContent = `${c.name} ${c.code ? "(" + c.code + ")" : ""}`;
      if (c.code === "+94") opt.selected = true; // default Sri Lanka
      select.appendChild(opt);
    });
  });
});

// ✅ Universal phone validator: supports +countrycode and digits
function setupPhoneValidator(inputId, errorId) {
    const input = document.getElementById(inputId);
    const error = document.getElementById(errorId);

    input.addEventListener('input', function () {
        const value = this.value.trim();
        const isValid = /^(\+?\d{7,15})$/.test(value); // allow + and 7–15 digits
        if (isValid) {
            error.style.display = 'none';
        } else {
            error.style.display = 'block';
            error.textContent = 'Please enter a phone number with 7–15 digits; include the country code if available (e.g., +94XXXXXXXXX).';
        }
    });
}

// Apply to all
setupPhoneValidator('mobilePhone', 'mobilePhoneError');
setupPhoneValidator('homePhone', 'homePhoneError');
setupPhoneValidator('whatsappPhone', 'whatsappNumberError');
setupPhoneValidator('parentContactNo', 'parentContactNoError');
setupPhoneValidator('emergencyContactNo', 'emergencyContactNoError');

</script>
@endpush
