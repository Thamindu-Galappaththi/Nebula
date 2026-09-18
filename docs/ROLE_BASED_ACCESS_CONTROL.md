# Role-Based Access Control System

## Overview
This system implements role-based access control (RBAC) for the Nebula Institute Management System. Users are assigned specific roles that determine their access to different features and functionalities.

## Available Roles

### 1. DGM (Deputy General Manager)
- **Special Approval**: Can access special approval features

### 2. Program Administrator (level 01)
- **User Management**: Can create users and manage user accounts
- **Module Management**: Can manage modules
- **Course Management**: Can manage courses
- **Overall Attendance**: Can view/manage overall attendance
- **Student Profile & Other Information**: Can view/manage student profiles and other information
- **All Clearance**: Can manage all clearance processes

### 3. Program Administrator (level 02)
- **Intake Creation**: Can create intakes
- **Attendance**: Can manage attendance
- **Timetable**: Can manage timetables
- **Student Profile & Other Information**: Can view/manage student profiles and other information
- **Exam Results**: Can manage exam results
- **Semester Creation & Registration**: Can create semesters and manage semester registrations
- **Module Management**: Can manage modules
- **Overall Attendance**: Can view/manage overall attendance

### 4. Student Counselor
- **Student Registration**: Can register students
- **Course Registration**: Can register courses
- **Eligibility Registration**: Can manage eligibility
- **Payment**: Can manage payments
- **Overall Payment**: Can view/manage overall payments
- **Late Payment**: Can manage late payments
- **Payment Discounts**: Can manage payment discounts

### 5. Marketing Manager
- **Payment Plan**: Can manage payment plans

### 6. Librarian
- **Library Clearance**: Only access to library clearance features

### 7. Hostel Manager
- **Hostel Clearance**: Only access to hostel clearance features

### 8. Bursar
- **Financial Management**: Access to student registration, course registration, and exam results
- **Attendance**: Can manage attendance records

### 9. Project Tutor
- **Project Clearance**: Only access to project clearance features
- **Attendance**: Can manage attendance for their projects

### 10. Developer
- **Full System Access**: Has access to every single page and feature in the system
- **All Permissions**: Can perform all operations across all modules
- **System Administration**: Complete administrative privileges

## Permission Matrix

| Feature                | DGM | Program Admin (L1) | Program Admin (L2) | Student Counselor | Marketing Manager | Librarian | Hostel Manager | Bursar | Project Tutor | Developer |
|------------------------|-----|--------------------|--------------------|-------------------|-------------------|-----------|----------------|--------|---------------|-----------|
| Special Approval       | ✅  | ❌                 | ❌                 | ❌                | ❌                | ❌        | ❌             | ❌     | ❌            | ✅        |
| User Management        | ❌  | ✅                 | ❌                 | ❌                | ❌                | ❌        | ❌             | ❌     | ❌            | ✅        |
| Module Management      | ❌  | ✅                 | ✅                 | ❌                | ❌                | ❌        | ❌             | ❌     | ❌            | ✅        |
| Course Management      | ❌  | ✅                 | ❌                 | ❌                | ❌                | ❌        | ❌             | ❌     | ❌            | ✅        |
| Overall Attendance     | ❌  | ✅                 | ✅                 | ❌                | ❌                | ❌        | ❌             | ❌     | ❌            | ✅        |
| Student Profile        | ❌  | ✅                 | ✅                 | ❌                | ❌                | ❌        | ❌             | ❌     | ❌            | ✅        |
| Other Information      | ❌  | ✅                 | ✅                 | ❌                | ❌                | ❌        | ❌             | ❌     | ❌            | ✅        |
| Student Registration   | ❌  | ❌                 | ❌                 | ✅                | ❌                | ❌        | ❌             | ✅     | ❌            | ✅        |
| Course Registration    | ❌  | ❌                 | ❌                 | ✅                | ❌                | ❌        | ❌             | ✅     | ❌            | ✅        |
| Eligibility Registration| ❌  | ❌                 | ❌                 | ✅                | ❌                | ❌        | ❌             | ✅     | ❌            | ✅        |
| Payment                | ❌  | ❌                 | ❌                 | ✅                | ❌                | ❌        | ❌             | ❌     | ❌            | ✅        |
| Overall Payment        | ❌  | ❌                 | ❌                 | ✅                | ❌                | ❌        | ❌             | ❌     | ❌            | ✅        |
| Late Payment           | ❌  | ❌                 | ❌                 | ✅                | ❌                | ❌        | ❌             | ❌     | ❌            | ✅        |
| Payment Discounts      | ❌  | ❌                 | ❌                 | ✅                | ❌                | ❌        | ❌             | ❌     | ❌            | ✅        |
| Payment Plan           | ❌  | ❌                 | ❌                 | ❌                | ✅                | ❌        | ❌             | ❌     | ❌            | ✅        |
| Library Clearance      | ❌  | ❌                 | ❌                 | ❌                | ❌                | ✅        | ❌             | ❌     | ❌            | ✅        |
| Hostel Clearance       | ❌  | ❌                 | ❌                 | ❌                | ❌                | ❌        | ✅             | ❌     | ❌            | ✅        |
| Project Clearance      | ❌  | ❌                 | ❌                 | ❌                | ❌                | ❌        | ❌             | ❌     | ✅            | ✅        |
| Financial Management   | ❌  | ❌                 | ❌                 | ❌                | ❌                | ❌        | ❌             | ✅     | ❌            | ✅        |
| File Management        | ❌  | ✅                 | ✅                 | ❌                | ❌                | ❌        | ❌             | ❌     | ❌            | ✅        |
| Reporting              | ❌  | ✅                 | ✅                 | ❌                | ❌                | ❌        | ❌             | ❌     | ❌            | ✅        |
| Data Export/Import     | ❌  | ✅                 | ✅                 | ❌                | ❌                | ❌        | ❌             | ❌     | ❌            | ✅        |
| API Documentation      | ❌  | ✅                 | ✅                 | ❌                | ❌                | ❌        | ❌             | ❌     | ❌            | ✅        |

## Implementation Details

### Middleware
- **CheckRole**: Middleware that validates user roles and permissions
- **Location**: `app/Http/Middleware/CheckRole.php`

### Helper Class
- **RoleHelper**: Centralized role and permission management
- **Location**: `app/Helpers/RoleHelper.php`
- **Features**:
  - Role definitions
  - Permission matrix
  - Permission checking methods
  - Access control methods

### Route Protection
All routes are protected with role-based middleware:
```php
Route::middleware(['role:DGM,Manager,Program Administrator'])->group(function () {
    // Protected routes
});
```

### Sidebar Navigation
The sidebar automatically shows/hides menu items based on user permissions using the RoleHelper class.

## Usage Examples

### Checking Permissions in Controllers
```php
use App\Helpers\RoleHelper;

// Check if user can access a specific feature
if (RoleHelper::hasPermission($user->user_role, 'student.registration')) {
    // Allow access
}

// Check if user can access student management
if (RoleHelper::canAccessStudentManagement($user->user_role)) {
    // Show student management features
}
```

### Checking Permissions in Views
```php
@if(RoleHelper::hasPermission(auth()->user()->user_role, 'student.registration'))
    <!-- Show student registration form -->
@endif
```

## Security Features

1. **Route Protection**: All routes are protected with role middleware
2. **Active User Check**: Only active users can access the system
3. **JSON Response Handling**: Proper error responses for API calls
4. **Session Management**: Proper session handling and logout on inactive accounts

## Adding New Roles

To add a new role:

1. Add the role to `RoleHelper::ROLES`
2. Define permissions in `RoleHelper::PERMISSIONS`
3. Update route middleware groups
4. Update sidebar navigation conditions

## Adding New Features

To add a new feature:

1. Define the route name in `RoleHelper::PERMISSIONS`
2. Add role middleware to the route
3. Update sidebar navigation if needed
4. Add permission checks in controllers/views if needed 