# Special Approval Document Upload Feature

## Overview
This update modifies the special approval functionality in the eligibility page to include document upload capability before sending requests to DGM.

## Changes Made

### 1. Eligibility Page (`resources/views/eligibility_registration.blade.php`)

#### Added Modal Popup
- Added a Bootstrap modal for document upload
- Modal includes:
  - Student NIC (readonly)
  - Course name (readonly)
  - Document upload field (required)
  - Remarks textarea (optional)
  - Submit and Cancel buttons

#### Modified Special Approval Button
- Changed from direct API call to showing modal
- Modal is populated with student and course information
- Form validation before submission

#### Enhanced JavaScript
- Added modal show/hide functionality
- Added form submission handling with file upload
- Added loading states and error handling
- Added form reset on modal close

### 2. Backend Controller (`app/Http/Controllers/EligibilityCheckingAndRegistrationController.php`)

#### Updated `sendSpecialApprovalRequest` Method
- Added file upload validation (PDF, DOC, DOCX, JPG, JPEG, PNG, max 5MB)
- Added remarks field validation
- Added document storage in `special_approvals` directory
- Updated database to store document path and remarks
- Enhanced logging with document information

#### Updated `getSpecialApprovalList` Method
- Added document URL generation for frontend display
- Added document path and remarks to response
- Enhanced data mapping for new columns

### 3. Special Approval List (`resources/views/Special_approval_list.blade.php`)

#### Enhanced Table Structure
- Added new columns: Course, Document, Remarks
- Updated colspan values for error messages

#### Enhanced JavaScript
- Added document display with download/view links
- Added remarks display with tooltip for long text
- Updated table rendering to include new columns

## File Upload Features

### Supported Formats
- PDF (.pdf)
- Microsoft Word (.doc, .docx)
- Images (.jpg, .jpeg, .png)

### File Size Limit
- Maximum 5MB per file

### Storage Location
- Files stored in `storage/app/public/special_approvals/`
- Accessible via `/storage/special_approvals/` URL

## Database Changes

### CourseRegistration Model
- `special_approval_pdf` field stores document path
- `remarks` field stores additional notes

## User Experience Improvements

### Eligibility Page
1. User clicks "Special Approval" button
2. Modal popup appears with student and course information
3. User uploads required document
4. User can add optional remarks
5. User clicks "Submit Request"
6. Loading state shows during submission
7. Success/error messages displayed
8. Page reloads after successful submission

### Special Approval List
1. DGM can view all pending special approval requests
2. Document column shows "View Document" button if document exists
3. Remarks column shows truncated text with full text on hover
4. Course column shows course name for better context

## Security Features
- File type validation on both frontend and backend
- File size limits enforced
- CSRF protection for form submissions
- Proper file storage with unique names

## Error Handling
- Frontend validation for required fields
- Backend validation for file types and sizes
- Graceful error messages for users
- Console logging for debugging

## Testing Checklist
- [ ] Modal opens when Special Approval button is clicked
- [ ] Form validation works for required fields
- [ ] File upload accepts supported formats
- [ ] File size validation works
- [ ] Document is stored correctly
- [ ] Special approval list shows uploaded documents
- [ ] Document links work correctly
- [ ] Error handling works for invalid files
- [ ] Modal closes and form resets properly 