<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UserTracking;
use App\Traits\CastsDateOnly;

class CourseRegistration extends Model
{
    use HasFactory, UserTracking, CastsDateOnly;
    protected $table = 'course_registration';
    protected $primaryKey = 'id';

    protected $fillable = [
        'student_id',
        'course_id',
        'intake_id',
        'course_registration_id',
        'registration_date',
        'registration_fee',
        'status',
        'approval_status',
        'location',
        'slt_employee',
        'employee_service_number',
        'counselor_name',
        'counselor_id',
        'counselor_phone',
        'counselor_nic',
        'course_start_date',
        'remarks',
        'special_approval_pdf',
        'dgm_comment',
        'uh_index_number',
        'full_grade',
    ];

    protected $casts = [
        'id' => 'int',
        'student_id' => 'int',
        'course_id' => 'int',
        'intake_id' => 'int',
        'registration_date' => 'date',
        'registration_fee' => 'decimal:2',
        'slt_employee' => 'boolean',
        'special_approval_date' => 'date',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function intake()
    {
        return $this->belongsTo(Intake::class, 'intake_id', 'intake_id');
    }

    public function payments()
    {
        return $this->hasMany(PaymentDetail::class, 'course_registration_id', 'id');
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class, 'registration_id', 'id');
    }

    public function examResults()
    {
        return $this->hasMany(ExamResult::class, 'registration_id', 'id');
    }

    // Scopes

    /**
     * Eligible registrations: fully registered rows, or rows approved by manager
     * (the only approval_status values that exist in the live database are
     * "Approved by manager" and "Pending"; "Approved by DGM" is never stored).
     */
    public function scopeEligible($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'Registered')
              ->orWhere('approval_status', 'Approved by manager');
        });
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByApprovalStatus($query, $approvalStatus)
    {
        return $query->where('approval_status', $approvalStatus);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeByIntake($query, $intakeId)
    {
        return $query->where('intake_id', $intakeId);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    public function scopeSLTEmployees($query)
    {
        return $query->where('slt_employee', true);
    }

    public function scopeNonSLTEmployees($query)
    {
        return $query->where('slt_employee', false);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            'Pending' => 'Pending',
            'Registered' => 'Registered',
            'Not eligible' => 'Not Eligible',
            'Special approval required' => 'Special Approval Required'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getApprovalStatusTextAttribute()
    {
        $approvalStatuses = [
            'Approved by manager' => 'Approved by Manager',
            'Sent to DGM' => 'Sent to DGM',
            'Rejected' => 'Rejected',
            'Pending' => 'Pending'
        ];

        return $approvalStatuses[$this->approval_status] ?? $this->approval_status;
    }

    public function getFormattedRegistrationDateAttribute()
    {
        return $this->registration_date ? $this->registration_date->format('d/m/Y') : 'N/A';
    }

    public function getFormattedSpecialApprovalDateAttribute()
    {
        return $this->special_approval_date ? $this->special_approval_date->format('d/m/Y') : 'N/A';
    }

    public function getFormattedRegistrationFeeAttribute()
    {
        return 'Rs. ' . number_format($this->registration_fee, 2);
    }

    // Methods
    public function isApproved()
    {
        return $this->approval_status === 'Approved by manager';
    }

    public function isPending()
    {
        return $this->approval_status === 'Pending';
    }

    public function isRejected()
    {
        return $this->approval_status === 'Rejected';
    }

    public function isSLTEmployee()
    {
        return $this->slt_employee;
    }

    public function requiresSpecialApproval()
    {
        return $this->status === 'Special approval required';
    }

    public function courseBadge()
    {
        return $this->hasOne(\App\Models\CourseBadge::class, 'course_id', 'course_id')
                    ->where('student_id', $this->student_id)
                    ->where('intake_id', $this->intake_id);
    }


}
