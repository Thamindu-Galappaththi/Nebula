<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UserTracking;
use App\Models\StudentHostelClearance;
use App\Models\StudentPaymentClearance;
use App\Models\StudentLibraryClearance;
use App\Models\StudentProjectClearance;
use App\Models\StudentOtherInformation;
use App\Models\StudentExam;
use App\Models\ParentGuardian;
use App\Models\CourseRegistration;
use App\Models\SemesterRegistration;
use App\Models\ModuleManagement;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\PaymentDetail;
use App\Traits\CastsDateOnly;
class Student extends Model
{
    use HasFactory, UserTracking, CastsDateOnly;

    protected $primaryKey = 'student_id';
    protected $table = 'students';

    /**
     * Academic status constants
     */
    public const ACADEMIC_ACTIVE     = 'active';
    public const ACADEMIC_TERMINATED = 'terminated';
    public const ACADEMIC_SUSPENDED  = 'suspended';
    public const ACADEMIC_GRADUATED  = 'graduated';

    protected $fillable = [
        'title',
        'name_with_initials',
        'full_name',
        'gender',
        'id_type',
        'id_value',
        'address',
        'district',
        'email',
        'mobile_phone',
        'home_phone',
        'whatsapp_phone',
        'birthday',
        'institute_location',
        'special_needs',
        'extracurricular_activities',
        'future_potentials',
        'other_document_upload',
        'remarks',

        // NOTE: existing "status" is marital status in your DB
        'status',

        'marketing_survey',
        'blacklisted',
        'user_photo',

        // NEW — academic status fields
        'academic_status',
        'academic_status_reason',
        'academic_status_document',
        'academic_status_changed_at',

        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'blacklisted'        => 'boolean',
        'birthday'           => 'date',

        // NEW
        'academic_status_changed_at' => 'datetime',
    ];

    protected $appends = ['parent'];

    /* =======================
     * Relationships
     * ======================= */
    public function exams()
    {
        return $this->hasMany(StudentExam::class, 'student_id', 'student_id');
    }

    public function parentGuardian()
    {
        return $this->hasOne(ParentGuardian::class, 'student_id', 'student_id');
    }

    // Accessor to make parent available as 'parent' attribute
    public function getParentAttribute()
    {
        return $this->parentGuardian;
    }

    public function courseRegistrations()
    {
        return $this->hasMany(CourseRegistration::class, 'student_id', 'student_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(StudentStatusHistory::class, 'student_id', 'student_id');
    }

    public function clearanceRequests()
    {
        return $this->hasMany(ClearanceRequest::class, 'student_id', 'student_id');
    }

    public function semesterRegistrations()
    {
        return $this->hasMany(SemesterRegistration::class, 'student_id', 'student_id');
    }

    public function moduleManagements()
    {
        return $this->hasMany(ModuleManagement::class, 'student_id', 'student_id');
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class, 'student_id', 'student_id');
    }

    public function examResults()
    {
        return $this->hasMany(ExamResult::class, 'student_id', 'student_id');
    }

    public function payments()
    {
        return $this->hasMany(PaymentDetail::class, 'student_id', 'student_id');
    }

    public function libraryClearance()
    {
        return $this->hasOne(StudentLibraryClearance::class, 'student_id', 'student_id');
    }

    public function hostelClearance()
    {
        return $this->hasOne(StudentHostelClearance::class, 'student_id', 'student_id');
    }

    public function paymentClearance()
    {
        return $this->hasOne(StudentPaymentClearance::class, 'student_id', 'student_id');
    }

    public function projectClearance()
    {
        return $this->hasOne(StudentProjectClearance::class, 'student_id', 'student_id');
    }

    public function otherInformation()
    {
        return $this->hasOne(StudentOtherInformation::class, 'student_id', 'student_id');
    }

    /* =======================
     * Query Scopes
     * ======================= */

    // Your existing blacklist scopes
    public function scopeActive($query)
    {
        return $query->where('blacklisted', false);
    }

    public function scopeBlacklisted($query)
    {
        return $query->where('blacklisted', true);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('institute_location', $location);
    }

    // NEW: filter by academic status
    public function scopeAcademicActive($query)
    {
        return $query->where('academic_status', self::ACADEMIC_ACTIVE);
    }

    public function scopeAcademicTerminated($query)
    {
        return $query->where('academic_status', self::ACADEMIC_TERMINATED);
    }

    public function scopeAcademicSuspended($query)
    {
        return $query->where('academic_status', self::ACADEMIC_SUSPENDED);
    }

    public function scopeAcademicGraduated($query)
    {
        return $query->where('academic_status', self::ACADEMIC_GRADUATED);
    }

    /* =======================
     * Accessors
     * ======================= */
    public function getFullNameAttribute($value)
    {
        return ucwords(strtolower($value));
    }

    public function getNameWithInitialsAttribute($value)
    {
        return strtoupper($value);
    }

    /* =======================
     * Mutators
     * ======================= */
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }

    public function setMobilePhoneAttribute($value)
    {
        $this->attributes['mobile_phone'] = preg_replace('/[^0-9]/', '', $value);
    }

    /* =======================
     * Helpers
     * ======================= */
    public function isAcademicallyActive(): bool
    {
        return $this->academic_status === self::ACADEMIC_ACTIVE;
    }

    public function isTerminated(): bool
    {
        return $this->academic_status === self::ACADEMIC_TERMINATED;
    }

    public function canRegisterForSemester(): bool
    {
        // block if terminated or suspended; allow if active or graduated (adjust if needed)
        return in_array($this->academic_status, [self::ACADEMIC_ACTIVE], true);
    }

    public function scopeNotTerminated($q)
    {
        return $q->where('academic_status', '!=', 'terminated');
    }
}
