# User Tracking Implementation Guide

## Overview
This implementation adds automatic user tracking to all database tables, recording which user created, updated, or deleted each record.

## What Was Added

### 1. Database Columns
The following columns are added to all tables:
- `created_by` - Stores the user ID of who created the record
- `updated_by` - Stores the user ID of who last updated the record
- `deleted_by` - Stores the user ID of who deleted the record (only for tables with soft deletes)

### 2. UserTracking Trait
Location: `app/Traits/UserTracking.php`

This trait automatically populates the tracking columns when:
- Creating a new record
- Updating an existing record
- Soft deleting a record (if applicable)

## How to Use

### Step 1: Run the Migration
```bash
php artisan migrate
```

This will add the tracking columns to all existing tables.

### Step 2: Add the Trait to Your Models
Open each model and add the `UserTracking` trait:

```php
<?php

namespace App\Models;

use App\Traits\UserTracking;
use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    use UserTracking;  // Add this line
    
    // Rest of your model code...
}
```

### Step 3: Update the $fillable Array (Optional)
If you want to allow manual assignment of these fields:

```php
protected $fillable = [
    'your_existing_fields',
    'created_by',
    'updated_by',
];
```

## Example Usage

### Creating a Record
```php
// The created_by and updated_by will be automatically set
$student = Student::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    // created_by and updated_by are set automatically
]);
```

### Updating a Record
```php
// The updated_by will be automatically updated
$student->update([
    'email' => 'newemail@example.com'
]);
// updated_by is now set to the current user's ID
```

### Getting User Information
```php
// Get who created the record
$creator = $student->creator; // Returns User model

// Get who last updated the record
$updater = $student->updater; // Returns User model

// Get who deleted the record (if soft deleted)
$deleter = $student->deleter; // Returns User model
```

### Displaying in Blade Views
```blade
<p>Created by: {{ $student->creator->name ?? 'System' }}</p>
<p>Last updated by: {{ $student->updater->name ?? 'System' }}</p>
<p>Created at: {{ $student->created_at }}</p>
<p>Updated at: {{ $student->updated_at }}</p>
```

## Models to Update

Add the `UserTracking` trait to these models:

- [ ] Student
- [ ] Course
- [ ] Module
- [ ] Intake
- [ ] ParentGuardian
- [ ] StudentExam
- [ ] User (optional)
- [ ] CourseRegistration
- [ ] StudentClearance
- [ ] ExamResult
- [ ] StudentOtherInformation
- [ ] StudentList
- [ ] Attendance
- [ ] ModuleManagement
- [ ] Timetable
- [ ] Semester
- [ ] SemesterRegistration
- [ ] PaymentPlan
- [ ] ClearanceRequest
- [ ] Discount
- [ ] PaymentInstallment
- [ ] StudentPaymentPlan
- [ ] PaymentDetail
- [ ] StudentStatusHistory
- [ ] CustomPayment
- [ ] CourseBadge
- [ ] Phase
- [ ] Team
- [ ] TeamRole
- [ ] CourseChangeLog
- [ ] CourseChangePayment

## Benefits

1. **Audit Trail**: Know exactly who made changes to your data
2. **Accountability**: Track user actions for compliance
3. **Debugging**: Easier to trace data issues
4. **Reporting**: Generate reports on user activity
5. **Security**: Identify unauthorized changes

## Important Notes

1. **Authentication Required**: The user must be logged in (authenticated) for tracking to work
2. **Nullable Columns**: All tracking columns are nullable, so existing data won't break
3. **Foreign Keys**: The columns have foreign keys to the `users` table with `onDelete('set null')`
4. **Performance**: Minimal performance impact as it uses Laravel's model events
5. **Existing Records**: Existing records will have NULL values until they're updated

## Customization

### Skip Tracking for Specific Operations
If you need to skip tracking for a specific operation:

```php
// Temporarily disable tracking
$model->updated_by = null;
$model->saveQuietly(); // Skips all model events
```

### Custom User ID
If you need to set a specific user ID:

```php
$model->created_by = $specificUserId;
$model->save();
```

## Troubleshooting

### Tracking Not Working
1. Ensure the trait is added to your model
2. Check if the user is authenticated: `Auth::check()`
3. Verify the columns exist in your database: `php artisan migrate:status`

### Foreign Key Errors
If you get foreign key errors, ensure the `users` table exists and has the correct primary key.

### Existing Data
For existing records, the tracking columns will be NULL until the record is updated. If you need to populate them, you can run a seeder or manual update.

## Future Enhancements

Consider adding:
1. **Change History Table**: Store complete history of all changes
2. **IP Address Tracking**: Record the IP address of who made changes
3. **Browser/Device Info**: Track what device was used
4. **Action Type**: Track what type of action was performed (create, update, delete)
