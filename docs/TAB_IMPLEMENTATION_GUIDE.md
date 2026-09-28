# Tab Implementation Guide for Exam Results and Attendance Pages

## Overview
This guide explains how to add tabs to separate Degree/Diploma and Certificate course workflows in:
1. Exam Results (Add) - `exam_results.blade.php`
2. Exam Results (View & Edit) - `exam_results_view_edit.blade.php`  
3. Attendance - `attendance.blade.php`
4. Overall Attendance - `overall_attendance.blade.php`

## Implementation Strategy

### HTML Structure
Replace the single form with a tabbed interface:

```html
<!-- Tabs Navigation -->
<ul class="nav nav-tabs mb-4" id="examTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="degree-tab" data-bs-toggle="tab" data-bs-target="#degree-panel" type="button" role="tab">
            Degree & Diploma
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="certificate-tab" data-bs-toggle="tab" data-bs-target="#certificate-panel" type="button" role="tab">
            Certificate
        </button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="examTabContent">
    <!-- Degree & Diploma Tab -->
    <div class="tab-pane fade show active" id="degree-panel" role="tabpanel">
        <!-- All filters for degree/diploma with prefixed IDs: degree_location, degree_course, etc. -->
        <!-- Include semester field -->
    </div>

    <!-- Certificate Tab -->
    <div class="tab-pane fade" id="certificate-panel" role="tabpanel">
        <!-- All filters for certificate with prefixed IDs: cert_location, cert_course, etc. -->
        <!-- NO semester field -->
    </div>
</div>
```

### JavaScript Strategy

#### Key Points:
1. **Duplicate Variables**: Create separate variable sets for each tab
   - Degree: `degreeResults`, `degreeLocation`, `degreeCourse`, etc.
   - Certificate: `certResults`, `certLocation`, `certCourse`, etc.

2. **Duplicate Functions**: Create tab-specific versions of all functions
   - `fetchDegreeCoursess()` and `fetchCertCourses()`
   - `handleDegreeModuleFetch()` and `handleCertModuleFetch()`
   - `renderDegreeTable()` and `renderCertTable()`

3. **Separate Event Listeners**: Attach listeners to tab-specific elements
   ```javascript
   // Degree tab
   document.getElementById('degree_location').addEventListener('change', function() {
       // Handle degree location change
   });

   // Certificate tab
   document.getElementById('cert_location').addEventListener('change', function() {
       // Handle certificate location change  
   });
   ```

4. **Common Elements**: Results table, statistics, buttons remain shared - just update based on active tab

### ID Naming Convention

**Degree/Diploma Tab:**
- `degree_location`
- `degree_course_type` (dropdown with only degree/diploma options)
- `degree_course`
- `degree_intake`
- `degree_semester` (REQUIRED)
- `degree_module`

**Certificate Tab:**
- `cert_location`
- `cert_course` (will fetch only certificate courses)
- `cert_intake`
- `cert_module` (NO semester needed)

### Filter Query Differences

**Degree/Diploma:**
```javascript
const data = {
    location: degree_location.value,
    course_type: degree_course_type.value, // 'degree' or 'diploma'
    course_id: degree_course.value,
    intake_id: degree_intake.value,
    semester: degree_semester.value, // REQUIRED
    module_id: degree_module.value
};
```

**Certificate:**
```javascript
const data = {
    location: cert_location.value,
    course_type: 'certificate', // Always 'certificate'
    course_id: cert_course.value,
    intake_id: cert_intake.value,
    semester: null, // NO semester
    module_id: cert_module.value
};
```

### API Endpoints (Already Support Certificate)

The following endpoints already have certificate support:
- `/exam-results/get-courses-by-location?location=X&course_type=certificate`
- `/exam-results/get-semesters` - returns `is_certificate: true` for certificates
- `{{ route('exam.results.get.filtered.modules') }}` - accepts null semester for certificates

### Step-by-Step Implementation

#### Step 1: Update HTML (Already Done for exam_results_view_edit.blade.php)
- Added tabs structure
- Duplicated filters with new IDs
- Removed semester field from certificate tab

#### Step 2: Update JavaScript Variables
```javascript
// At the top of DOMContentLoaded
// Degree Tab Elements
const degreeLocation = document.getElementById('degree_location');
const degreeCourseType = document.getElementById('degree_course_type');
const degreeCourse = document.getElementById('degree_course');
const degreeIntake = document.getElementById('degree_intake');
const degreeSemester = document.getElementById('degree_semester');
const degreeModule = document.getElementById('degree_module');
let degreeResults = [];

// Certificate Tab Elements  
const certLocation = document.getElementById('cert_location');
const certCourse = document.getElementById('cert_course');
const certIntake = document.getElementById('cert_intake');
const certModule = document.getElementById('cert_module');
let certResults = [];

// Shared Elements
const resultsTableBody = document.getElementById('resultsTableBody');
const updateAllBtn = document.getElementById('updateAllBtn');
```

#### Step 3: Create Tab-Specific Event Listeners
```javascript
// Degree Location Change
degreeLocation.addEventListener('change', function() {
    // Reset dependent dropdowns
    // Fetch courses for degree/diploma
    if (degreeCourseType.value) {
        fetchDegreeCoursess(this.value, degreeCourseType.value);
    }
});

// Certificate Location Change  
certLocation.addEventListener('change', function() {
    // Reset dependent dropdowns
    // Fetch certificate courses
    fetchCertCourses(this.value);
});

// Continue for all other dropdowns...
```

#### Step 4: Create Tab-Specific Functions

```javascript
function fetchDegreeCoursess(location, courseType) {
    fetch(`/exam-results/get-courses-by-location?location=${location}&course_type=${courseType}`)
        .then(/* populate degree_course dropdown */);
}

function fetchCertCourses(location) {
    fetch(`/exam-results/get-courses-by-location?location=${location}&course_type=certificate`)
        .then(/* populate cert_course dropdown */);
}

function handleDegreeModuleFetch() {
    const data = {
        location: degreeLocation.value,
        course_id: degreeCourse.value,
        intake_id: degreeIntake.value,
        semester: degreeSemester.value // REQUIRED
    };
    fetchModules(data, degreeModule);
}

function handleCertModuleFetch() {
    const data = {
        location: certLocation.value,
        course_id: certCourse.value,
        intake_id: certIntake.value,
        semester: null // Certificate - no semester
    };
    fetchModules(data, certModule);
}

// Shared function
function fetchModules(data, targetSelect) {
    fetch('{{ route("exam.results.get.filtered.modules") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        populateDropdown(targetSelect, data.modules, 'module_id', 'module_name', 'Module');
    });
}
```

#### Step 5: Determine Active Tab for Shared Functions

```javascript
function getActiveTab() {
    const degreeTab = document.getElementById('degree-panel');
    return degreeTab.classList.contains('active') ? 'degree' : 'certificate';
}

function getFilterData() {
    const activeTab = getActiveTab();
    
    if (activeTab === 'degree') {
        return {
            location: degreeLocation.value,
            course_type: degreeCourseType.value,
            course_id: degreeCourse.value,
            intake_id: degreeIntake.value,
            semester: degreeSemester.value,
            module_id: degreeModule.value
        };
    } else {
        return {
            location: certLocation.value,
            course_type: 'certificate',
            course_id: certCourse.value,
            intake_id: certIntake.value,
            semester: null,
            module_id: certModule.value
        };
    }
}

// When fetching results
function fetchResults() {
    const data = getFilterData();
    // Proceed with API call
}
```

### Complete Example for One Page

Would you like me to:
1. Create a complete rewritten version of one of these files (exam_results_view_edit.blade.php)?
2. Provide the JavaScript code to paste into the files?
3. Create separate JS files to include?

Let me know which approach you prefer and I'll implement it completely.

## Files Status

### exam_results_view_edit.blade.php
- ✅ HTML tabs structure added
- ❌ JavaScript needs updating
- Next: Update JavaScript with tab-specific handlers

### exam_results.blade.php
- ❌ HTML tabs not added yet
- ❌ JavaScript needs updating

### attendance.blade.php
- ❌ HTML tabs not added yet
- ❌ JavaScript needs updating
