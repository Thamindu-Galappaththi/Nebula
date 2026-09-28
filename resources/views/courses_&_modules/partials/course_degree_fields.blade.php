@php
    $p = $prefix ?? '';
    $req = !empty($required);
    $modal = ($layout ?? 'form') === 'modal';
    $labelClass = $modal ? 'form-label' : 'col-12 col-md-3 col-lg-2 col-form-label';
    $fieldClass = $modal ? '' : 'col-12 col-md-9 col-lg-10';
@endphp

@if($modal)
<div class="col-12 col-md-6">
    <label for="{{ $p }}course_name" class="form-label">Course Name <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="{{ $p }}course_name" name="course_name" placeholder="Enter the course name" @if($req) required @endif>
</div>
<div class="col-12 col-md-6">
    <label for="{{ $p }}course_medium" class="form-label">Course Medium <span class="text-danger">*</span></label>
    <select class="form-select" id="{{ $p }}course_medium" name="course_medium" @if($req) required @endif>
        <option selected disabled value="">Choose a medium...</option>
        <option value="Sinhala">Sinhala</option>
        <option value="English">English</option>
    </select>
</div>
<div class="col-12 col-md-6">
    <label class="form-label">Specialization</label>
    <div class="d-flex flex-wrap align-items-center gap-3 pt-1">
        <div class="form-check form-check-inline mb-0">
            <input class="form-check-input has-specialization-yes" type="radio" name="has_specialization" id="{{ $p }}specializationYes" value="yes">
            <label class="form-check-label" for="{{ $p }}specializationYes">Yes</label>
        </div>
        <div class="form-check form-check-inline mb-0">
            <input class="form-check-input has-specialization-no" type="radio" name="has_specialization" id="{{ $p }}specializationNo" value="no" checked>
            <label class="form-check-label" for="{{ $p }}specializationNo">No</label>
        </div>
    </div>
</div>
<div class="col-12 specialization-fields" id="{{ $p }}specializationFields" hidden>
    <label class="form-label">Specialization Name(s)</label>
    <div class="specialization-inputs" id="{{ $p }}specializationInputs">
        <div class="input-group mb-2">
            <input type="text" class="form-control specialization-input" name="specializations[]" placeholder="Enter specialization name">
            <button type="button" class="btn btn-outline-danger btn-sm remove-specialization" title="Remove" aria-label="Remove" hidden><i class="ti ti-x"></i></button>
        </div>
    </div>
    <button type="button" class="btn btn-sm btn-success add-specialization-btn">Add Another Specialization</button>
</div>
<div class="col-12 col-md-6">
    <label for="{{ $p }}conducted_by" class="form-label">Conducted by <span class="text-danger">*</span></label>
    <select class="form-select conducted-by-select" id="{{ $p }}conducted_by" name="conducted_by" @if($req) required @endif>
        <option selected disabled value="">Select who conducts</option>
        <option value="SLT-MOBITEL Nebula Institute of Technology">SLT-MOBITEL Nebula Institute of Technology</option>
        <option value="Pearson">Pearson</option>
        <option value="University of Hertfordshire">University of Hertfordshire</option>
        <option value="Other">Other</option>
    </select>
    <input type="text" class="form-control mt-2 other-conducted-by" id="{{ $p }}other_conducted_by" name="other_conducted_by" placeholder="Please specify" hidden>
</div>
<div class="col-12 col-md-6">
    <label class="form-label">Duration <span class="text-danger">*</span></label>
    <div class="duration-fields">
        <input type="number" class="form-control" id="{{ $p }}duration_years" name="duration_years" placeholder="Years" min="0" @if($req) required @endif>
        <input type="number" class="form-control" id="{{ $p }}duration_months" name="duration_months" placeholder="Months" min="0" max="11" @if($req) required @endif>
        <input type="number" class="form-control" id="{{ $p }}duration_days" name="duration_days" placeholder="Days" min="0" max="30" @if($req) required @endif>
    </div>
</div>
<div class="col-12 col-md-6">
    <label for="{{ $p }}no_of_semesters" class="form-label">Semesters</label>
    <input type="number" class="form-control" id="{{ $p }}no_of_semesters" name="no_of_semesters" placeholder="Total semesters" min="1">
</div>
<div class="col-12 col-md-6">
    <label for="{{ $p }}semester_format" class="form-label">Semester Naming Convention <span class="text-danger">*</span></label>
    <select class="form-select" id="{{ $p }}semester_format" name="semester_format" @if($req) required @endif>
        <option value="">Select Semester Format</option>
        <option value="numerical">Numerical (1, 2, 3, 4...)</option>
        <option value="alphabetical">Alphabetical (A, B, C, D...)</option>
    </select>
</div>
<div class="col-12 col-md-6">
    <label class="form-label">Training Period</label>
    <div class="duration-fields">
        <input type="number" class="form-control" id="{{ $p }}training_years" name="training_years" placeholder="Years" min="0">
        <input type="number" class="form-control" id="{{ $p }}training_months" name="training_months" placeholder="Months" min="0" max="11">
        <input type="number" class="form-control" id="{{ $p }}training_days" name="training_days" placeholder="Days" min="0" max="30">
    </div>
</div>
<div class="col-12 col-md-6">
    <label for="{{ $p }}min_credits" class="form-label">Minimum Credits</label>
    <input type="number" class="form-control" id="{{ $p }}min_credits" name="min_credits" placeholder="Minimum credits" min="1">
</div>
<div class="col-12">
    <label for="{{ $p }}entry_qualification" class="form-label">Entry Qualification <span class="text-danger">*</span></label>
    <textarea class="form-control" id="{{ $p }}entry_qualification" name="entry_qualification" rows="2" @if($req) required @endif></textarea>
</div>
@else
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label for="{{ $p }}course_name" class="{{ $labelClass }}">Course Name <span class="text-danger">*</span></label>
    <div class="{{ $fieldClass }}">
        <input type="text" class="form-control" id="{{ $p }}course_name" name="course_name" placeholder="Enter the course name" @if($req) required @endif>
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label for="{{ $p }}course_medium" class="{{ $labelClass }}">Course Medium <span class="text-danger">*</span></label>
    <div class="{{ $fieldClass }}">
        <select class="form-select" id="{{ $p }}course_medium" name="course_medium" @if($req) required @endif>
            <option selected disabled value="">Choose a medium...</option>
            <option value="Sinhala">Sinhala</option>
            <option value="English">English</option>
        </select>
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label class="{{ $labelClass }}">Specialization</label>
    <div class="{{ $fieldClass }} d-flex flex-wrap align-items-center gap-3">
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
        <label class="{{ $labelClass }}">Specialization Name(s)</label>
        <div class="{{ $fieldClass }}">
            <div class="specialization-inputs" id="{{ $p }}specializationInputs">
                <div class="input-group mb-2">
                    <input type="text" class="form-control specialization-input" name="specializations[]" placeholder="Enter specialization name">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-specialization" title="Remove" aria-label="Remove" hidden><i class="ti ti-x"></i></button>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-success add-specialization-btn">Add Another Specialization</button>
        </div>
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label for="{{ $p }}conducted_by" class="{{ $labelClass }}">Conducted by <span class="text-danger">*</span></label>
    <div class="{{ $fieldClass }}">
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
    <label class="{{ $labelClass }}">Duration <span class="text-danger">*</span></label>
    <div class="{{ $fieldClass }}">
        <div class="duration-fields">
            <input type="number" class="form-control" id="{{ $p }}duration_years" name="duration_years" placeholder="Years" min="0" @if($req) required @endif>
            <input type="number" class="form-control" id="{{ $p }}duration_months" name="duration_months" placeholder="Months" min="0" max="11" @if($req) required @endif>
            <input type="number" class="form-control" id="{{ $p }}duration_days" name="duration_days" placeholder="Days" min="0" max="30" @if($req) required @endif>
        </div>
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label for="{{ $p }}no_of_semesters" class="{{ $labelClass }}">Semesters</label>
    <div class="{{ $fieldClass }}">
        <input type="number" class="form-control" id="{{ $p }}no_of_semesters" name="no_of_semesters" placeholder="Enter the number of total semesters" min="1">
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label for="{{ $p }}semester_format" class="{{ $labelClass }}">Semester Naming Convention <span class="text-danger">*</span></label>
    <div class="{{ $fieldClass }}">
        <select class="form-select" id="{{ $p }}semester_format" name="semester_format" @if($req) required @endif>
            <option value="">Select Semester Format</option>
            <option value="numerical">Numerical (1, 2, 3, 4...)</option>
            <option value="alphabetical">Alphabetical (A, B, C, D...)</option>
        </select>
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label class="{{ $labelClass }}">Training Period</label>
    <div class="{{ $fieldClass }}">
        <div class="duration-fields">
            <input type="number" class="form-control" id="{{ $p }}training_years" name="training_years" placeholder="Years" min="0">
            <input type="number" class="form-control" id="{{ $p }}training_months" name="training_months" placeholder="Months" min="0" max="11">
            <input type="number" class="form-control" id="{{ $p }}training_days" name="training_days" placeholder="Days" min="0" max="30">
        </div>
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-center mb-3">
    <label for="{{ $p }}min_credits" class="{{ $labelClass }}">Minimum Credits</label>
    <div class="{{ $fieldClass }}">
        <input type="number" class="form-control" id="{{ $p }}min_credits" name="min_credits" placeholder="Enter the minimum credits" min="1">
    </div>
</div>
<div class="row g-2 g-md-3 align-items-md-start mb-3">
    <label for="{{ $p }}entry_qualification" class="{{ $labelClass }}">Entry Qualification <span class="text-danger">*</span></label>
    <div class="{{ $fieldClass }}">
        <textarea class="form-control" id="{{ $p }}entry_qualification" name="entry_qualification" rows="3" @if($req) required @endif></textarea>
    </div>
</div>
@endif
