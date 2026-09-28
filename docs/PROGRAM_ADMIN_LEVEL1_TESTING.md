# Program Administrator Level 1 - Permission Testing Guide

## Overview
Complete testing tool for **Program Administrator (level 01)** role permissions and authorized pages.

---

## 🎯 What Program Administrator Level 1 Can Access

### HOME (1 permission)
- ✅ Dashboard - Main system dashboard

### USER MANAGEMENT (2 permissions)
- ✅ Create User - Add new users to system
- ✅ User Management - Manage existing users

### STUDENT MANAGEMENT (6 permissions)
- ✅ Student Registration - Register new students
- ✅ Course Badge - Manage student badges
- ✅ Student Other Information - Additional student info
- ✅ Student List - View all students
- ✅ Student Profile - View/edit student profiles
- ✅ Student View - View student details

### REGISTRATIONS (6 permissions)
- ✅ Course Registration - Register students to courses
- ✅ Eligibility & Registration - Check eligibility
- ✅ Semester Registration - Register for semesters
- ✅ Module Management - Manage modules
- ✅ UH Index Management - University of Hertfordshire index
- ✅ Course Change - Process course changes

### EXAMS & RESULTS (4 permissions)
- ✅ Exam Results - View exam results
- ✅ Student Exam Result Management - Manage results
- ✅ Exam Results View/Edit - View/edit results
- ✅ Repeat Students Management - Manage repeat students

### ATTENDANCE (2 permissions)
- ✅ Attendance Management - Mark attendance
- ✅ Overall Attendance - View attendance reports

### CLEARANCE (1 permission)
- ✅ All Clearance Management - Manage student clearances

### COURSES & MODULES (5 permissions)
- ✅ Module Creation - Create new modules
- ✅ Course Management - Manage courses
- ✅ Intake Creation - Create new intakes
- ✅ Semester Creation - Create new semesters
- ✅ Timetable Management - Manage timetables

### FINANCIAL (1 permission)
- ✅ Payment Dashboard - View payment information

### FOOTER (1 permission)
- ✅ User Profile - View/edit own profile

---

## 🔧 How to Use the Permission Checker

### Step 1: Access the Tool
1. Login as **Program Administrator (level 01)** user
2. Visit: `http://your-domain/permission-checker`

### Step 2: Run Tests
**Option A - Test Individual Permission:**
- Click the "Test" button next to any permission
- Wait for result (green = passed, red = failed)

**Option B - Test All Permissions:**
- Click "Run All Tests" button at top
- Wait ~15 seconds for all tests to complete
- Review results by category

### Step 3: Review Results
- **Green cards** = Page loads successfully ✅
- **Red cards** = Page has issues ❌
  - 404 = Route not found
  - 403 = Access denied
  - 500 = Server error

### Step 4: Export Results
- Click "Export Test Results" to download JSON report
- Click "Print Report" to print summary

---

## 📊 What Gets Tested

For each permission, the tool checks:
1. **Route Exists** - Does the URL exist?
2. **Access Granted** - Can this role access it?
3. **Page Loads** - Does the page load without errors?
4. **Response Code** - What HTTP status is returned?

---

## 🔍 Common Issues & Solutions

### Issue: 404 Not Found
**Cause:** Route doesn't exist or URL is wrong
**Fix:** 
1. Check `routes/web.php` for the route
2. Run `php artisan route:list` to verify
3. Update route name/URL in permission checker

### Issue: 403 Access Denied
**Cause:** Role doesn't have permission in middleware
**Fix:**
1. Check `routes/web.php` middleware for the route
2. Verify role is included: `role:Program Administrator (level 01)`
3. Check `CheckRole` middleware

### Issue: 500 Server Error
**Cause:** Page has a PHP error
**Fix:**
1. Check `storage/logs/laravel.log`
2. Set `APP_DEBUG=true` temporarily to see error
3. Fix the controller/view issue
4. Set `APP_DEBUG=false` again

### Issue: Test Hangs/Freezes
**Cause:** Page is taking too long to load
**Fix:**
1. Check if page requires data (students, courses, etc.)
2. Seed test data if needed
3. Optimize slow queries

---

## 📝 Manual Testing Checklist

After automated tests, manually verify these critical functions:

### Student Management
- [ ] Can register a new student
- [ ] Can search for students
- [ ] Can view student profile
- [ ] Can edit student information
- [ ] Can upload student documents

### Course Registration
- [ ] Can register student to course
- [ ] Can view course details
- [ ] Can check eligibility
- [ ] Can process course changes

### Exam Results
- [ ] Can enter exam results
- [ ] Can view student results
- [ ] Can edit results (if needed)
- [ ] Can manage repeat students

### Attendance
- [ ] Can mark attendance
- [ ] Can view attendance reports
- [ ] Can edit attendance records

### Module/Course Management
- [ ] Can create new modules
- [ ] Can create new courses
- [ ] Can create intakes
- [ ] Can create semesters
- [ ] Can manage timetable

### User Management
- [ ] Can create new users
- [ ] Can edit user details
- [ ] Can assign roles

---

## 🧪 Testing Scenarios

### Scenario 1: New Student Registration Flow
1. Navigate to Student Registration
2. Fill in student details
3. Upload required documents
4. Submit form
5. Verify student appears in Student List
6. Check student profile loads correctly

**Expected Result:** All steps complete without errors

### Scenario 2: Course Registration Flow
1. Navigate to Course Registration
2. Search for student
3. Select course
4. Check eligibility
5. Register student
6. Verify registration in student profile

**Expected Result:** Student successfully registered

### Scenario 3: Exam Results Entry
1. Navigate to Exam Results
2. Select course and semester
3. Enter marks for students
4. Submit results
5. Verify in student profile

**Expected Result:** Results saved and visible

### Scenario 4: Attendance Management
1. Navigate to Attendance
2. Select module and date
3. Mark attendance for students
4. Save attendance
5. View overall attendance report

**Expected Result:** Attendance recorded correctly

---

## 📈 Performance Testing

Test page load times for critical pages:

| Page | Expected Load Time | Action if Slow |
|------|-------------------|----------------|
| Dashboard | < 2 seconds | Check database queries |
| Student List | < 3 seconds | Add pagination/caching |
| Student Profile | < 2 seconds | Optimize data loading |
| Exam Results | < 3 seconds | Index database tables |
| Attendance | < 2 seconds | Optimize queries |

---

## 🔐 Security Testing

Verify role-based access:

### Test 1: Access Denied Routes
Try accessing routes NOT in permissions list:
- `/data-export-import` (should be 403)
- `/special-approval` (should be 403)
- `/developer-dashboard` (should be 403)

**Expected:** Access denied for all

### Test 2: Cross-Role Access
Login as different role and try accessing Program Admin Level 1 pages:
- Login as "Student Counselor"
- Try `/create-user` (should be denied)
- Try `/module-creation` (should be denied)

**Expected:** Only authorized routes accessible

### Test 3: Session Security
1. Login as Program Admin Level 1
2. Wait for session timeout
3. Try accessing page
4. Should redirect to login

**Expected:** Secure session handling

---

## 📋 Test Report Template

```
PROGRAM ADMINISTRATOR LEVEL 1 - TEST REPORT
Date: [DATE]
Tester: [NAME]
Environment: [DEV/STAGING/PRODUCTION]

SUMMARY:
- Total Permissions: 29
- Tests Passed: [X]/29
- Tests Failed: [X]/29
- Pass Rate: [X]%

FAILED TESTS:
1. [Permission Name] - [Error Code] - [Description]
2. [Permission Name] - [Error Code] - [Description]

CRITICAL ISSUES:
- [Issue description]
- [Impact and urgency]

RECOMMENDATIONS:
- [Action items]

TESTED BY: ________________
DATE: ___________
```

---

## 🛠️ Troubleshooting Commands

```bash
# Check routes for Program Admin Level 1
php artisan route:list | grep "Program Administrator"

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check logs for errors
tail -f storage/logs/laravel.log

# Test specific route
curl -X GET http://localhost/dashboard -H "Cookie: laravel_session=YOUR_SESSION"

# Verify middleware
grep -r "Program Administrator (level 01)" routes/
```

---

## 📞 Support

### If Automated Tests Fail:
1. Run `/diagnostics` page first
2. Check browser console (F12)
3. Check `storage/logs/laravel.log`
4. Clear all caches
5. Try in incognito mode

### If Manual Tests Fail:
1. Check user has correct role
2. Check role spelling (exact match required)
3. Verify database has test data
4. Check middleware configuration

### Report Issues With:
- Screenshot of failed test
- Browser console errors
- Laravel log entries
- Steps to reproduce
- Expected vs actual behavior

---

## ✅ Quick Access URLs

After login as Program Administrator Level 1:

- Permission Checker: `/permission-checker`
- Dashboard: `/dashboard`
- Student Registration: `/student-registration`
- Course Registration: `/course-registration`
- Student List: `/student-list`
- Exam Results: `/exam-results`
- Attendance: `/attendance`
- User Management: `/user-management`
- Course Management: `/course-management`

---

## 🎓 Best Practices

1. **Test Regularly** - Run permission checker after:
   - Code changes
   - Role permission updates
   - Middleware modifications
   - Route changes

2. **Document Issues** - Keep a log of:
   - Failed tests
   - Workarounds
   - Known issues

3. **Update Permissions** - When adding new features:
   - Update `RoleHelper.php`
   - Update `routes/web.php` middleware
   - Update permission checker
   - Re-test all permissions

4. **Monitor Logs** - Check for:
   - Authorization failures
   - 403/404 errors
   - Slow queries

---

**Last Updated:** January 2, 2026
**Tool Location:** `/permission-checker`
**Documentation:** `PROGRAM_ADMIN_LEVEL1_TESTING.md`
