<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UserTracking;
use Carbon\Carbon;

class ClearanceRequest extends Model
{
    use HasFactory, UserTracking;
    protected $fillable = [
        'clearance_type',
        'location',
        'course_id',
        'intake_id',
        'specialization',
        'student_id',
        'status',
        'remarks',
        'clearance_slip',
        'approved_by',
        'approved_at',
        'requested_at',
        'is_individual_request'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'requested_at' => 'datetime',
        'is_individual_request' => 'boolean',
    ];

    // Clearance types
    const TYPE_LIBRARY = 'library';
    const TYPE_HOSTEL = 'hostel';
    const TYPE_PAYMENT = 'payment';
    const TYPE_PROJECT = 'project';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

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

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public function courseRegistrations()
    {
        return $this->hasMany(CourseRegistration::class, 'student_id', 'student_id');
    }

    public function getCourseRegistrationIdentifierAttribute(): ?string
    {
        $registrations = $this->relationLoaded('courseRegistrations')
            ? $this->courseRegistrations
            : $this->courseRegistrations()->get();

        return optional($registrations->first(function ($registration) {
            return (int) $registration->course_id === (int) $this->course_id
                && (int) $registration->intake_id === (int) $this->intake_id
                && strtolower((string) $registration->location) === strtolower((string) $this->location);
        }))->course_registration_id;
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('clearance_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    // Methods
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function approve($approverId, $remarks = null, $clearanceSlip = null)
    {
        $this->status = self::STATUS_APPROVED;
        $this->approved_by = $approverId;
        $this->approved_at = now();
        $this->remarks = $remarks;
        if ($clearanceSlip) {
            $this->clearance_slip = $clearanceSlip;
        }
        $this->save();
    }

    public function reject($approverId, $remarks = null, $clearanceSlip = null)
    {
        $this->status = self::STATUS_REJECTED;
        $this->approved_by = $approverId;
        $this->approved_at = now();
        $this->remarks = $remarks;
        if ($clearanceSlip) {
            $this->clearance_slip = $clearanceSlip;
        }
        $this->save();
    }

    // Static methods
    public static function getClearanceTypes()
    {
        return [
            self::TYPE_LIBRARY => 'Library Clearance',
            self::TYPE_HOSTEL => 'Hostel Clearance',
            self::TYPE_PAYMENT => 'Payment Clearance',
            self::TYPE_PROJECT => 'Project Clearance'
        ];
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected'
        ];
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getClearanceTypeTextAttribute()
    {
        return self::getClearanceTypes()[$this->clearance_type] ?? ucfirst($this->clearance_type);
    }

    public function getStatusColorAttribute()
    {
        return [
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger'
        ][$this->status] ?? 'secondary';
    }

    public function processedAtSriLanka(): ?Carbon
    {
        return $this->toSriLankaTime($this->approved_at ?? $this->updated_at);
    }

    public function requestedAtSriLanka(): ?Carbon
    {
        return $this->toSriLankaTime($this->requested_at ?? $this->created_at);
    }

    private function toSriLankaTime($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        $carbon = $value instanceof Carbon
            ? $value
            : Carbon::parse($value);

        // TIMESTAMP columns are stored in UTC; show Asia/Colombo (Sri Lanka) time.
        return Carbon::parse($carbon->format('Y-m-d H:i:s'), 'UTC')->timezone('Asia/Colombo');
    }
}
