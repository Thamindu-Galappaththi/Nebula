@php
    $p = $prefix ?? '';
    $req = !empty($required);
@endphp
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label for="{{ $p }}course_name" class="col-12 col-md-3 col-lg-2 col-form-label">Course Name <span class="text-danger">*</span></label>
    <div class="col-12 col-md-9 col-lg-10">
        <input type="text" class="form-control" id="{{ $p }}course_name" name="course_name" placeholder="Enter the course name" @if($req) required @endif>
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label for="{{ $p }}course_medium" class="col-12 col-md-3 col-lg-2 col-form-label">Course Medium <span class="text-danger">*</span></label>
    <div class="col-12 col-md-9 col-lg-10">
        <select class="form-select" id="{{ $p }}course_medium" name="course_medium" @if($req) required @endif>
            <option selected disabled value="">Choose a medium...</option>
            <option value="Sinhala">Sinhala</option>
            <option value="English">English</option>
        </select>
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label class="col-12 col-md-3 col-lg-2 col-form-label">Specialization</label>
    <div class="col-12 col-md-9 col-lg-10 d-flex flex-wrap align-items-center gap-3">
        <div class="form-check form-check-inline">
            <input class="form-check-input has-specialization-yes" type="radio" name="has_specialization" id="{{ $p }}specializationYes" value="yes">
            <label class="form-check-label" for="{{ $p }}specializationYes">Yes</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input has-specialization-no" type="radio" name="has_specialization" id="{{ $p }}specializationNo" value="no" checked>
            <label class="form-check-label" for="{{ $p }}specializationNo">No</label>
        </div>
    </div>
</div>
<div class="specialization-fields" id="{{ $p }}specializationFields" hidden>
    <div class="row g-2 g-md-3 align-items-md-start mb-3">
        <label class="col-12 col-md-3 col-lg-2 col-form-label">Specialization Name(s)</label>
        <div class="col-12 col-md-9 col-lg-10">
            <div class="specialization-inputs" id="{{ $p }}specializationInputs">
                <div class="input-group mb-2">
                    <input type="text" class="form-control specialization-input" name="specializations[]" placeholder="Enter specialization name">
                    <button type="button" class="btn btn-outline-secondary remove-specialization" hidden>Remove</button>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-success add-specialization-btn">Add Another Specialization</button>
        </div>
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label for="{{ $p }}conducted_by" class="col-12 col-md-3 col-lg-2 col-form-label">Conducted by <span class="text-danger">*</span></label>
    <div class="col-12 col-md-9 col-lg-10">
        <select class="form-select conducted-by-select" id="{{ $p }}conducted_by" name="conducted_by" @if($req) required @endif>
            <option selected disabled value="">Select who conducts</option>
            <option value="SLT-MOBITEL Nebula Institute of Technology">SLT-MOBITEL Nebula Institute of Technology</option>
            <option value="Pearson">Pearson</option>
            <option value="University of Hertfordshire">University of Hertfordshire</option>
            <option value="Other">Other</option>
        </select>
        <input type="text" class="form-control mt-2 other-conducted-by" id="{{ $p }}other_conducted_by" name="other_conducted_by" placeholder="Please specify" hidden>
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label class="col-12 col-md-3 col-lg-2 col-form-label">Duration <span class="text-danger">*</span></label>
    <div class="col-12 col-md-9 col-lg-10">
        <div class="duration-fields">
            <input type="number" class="form-control" id="{{ $p }}duration_years" name="duration_years" placeholder="Years" min="0" @if($req) required @endif>
            <input type="number" class="form-control" id="{{ $p }}duration_months" name="duration_months" placeholder="Months" min="0" max="11" @if($req) required @endif>
            <input type="number" class="form-control" id="{{ $p }}duration_days" name="duration_days" placeholder="Days" min="0" max="30" @if($req) required @endif>
        </div>
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label for="{{ $p }}no_of_semesters" class="col-12 col-md-3 col-lg-2 col-form-label">Semesters</label>
    <div class="col-12 col-md-9 col-lg-10">
        <input type="number" class="form-control" id="{{ $p }}no_of_semesters" name="no_of_semesters" placeholder="Enter the number of total semesters" min="1">
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label for="{{ $p }}semester_format" class="col-12 col-md-3 col-lg-2 col-form-label">Semester Naming Convention <span class="text-danger">*</span></label>
    <div class="col-12 col-md-9 col-lg-10">
        <select class="form-select" id="{{ $p }}semester_format" name="semester_format" @if($req) required @endif>
            <option value="">Select Semester Format</option>
            <option value="numerical">Numerical (1, 2, 3, 4...)</option>
            <option value="alphabetical">Alphabetical (A, B, C, D...)</option>
        </select>
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label class="col-12 col-md-3 col-lg-2 col-form-label">Training Period</label>
    <div class="col-12 col-md-9 col-lg-10">
        <div class="duration-fields">
            <input type="number" class="form-control" id="{{ $p }}training_years" name="training_years" placeholder="Years" min="0">
            <input type="number" class="form-control" id="{{ $p }}training_months" name="training_months" placeholder="Months" min="0" max="11">
            <input type="number" class="form-control" id="{{ $p }}training_days" name="training_days" placeholder="Days" min="0" max="30">
        </div>
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label for="{{ $p }}min_credits" class="col-12 col-md-3 col-lg-2 col-form-label">Minimum Credits</label>
    <div class="col-12 col-md-9 col-lg-10">
        <input type="number" class="form-control" id="{{ $p }}min_credits" name="min_credits" placeholder="Enter the minimum credits" min="1">
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-start mb-3">
    <label for="{{ $p }}entry_qualification" class="col-12 col-md-3 col-lg-2 col-form-label">Entry Qualification <span class="text-danger">*</span></label>
    <div class="col-12 col-md-9 col-lg-10">
        <textarea class="form-control" id="{{ $p }}entry_qualification" name="entry_qualification" rows="3" @if($req) required @endif></textarea>
    </div>
</div>
