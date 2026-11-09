<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UserTracking;
use App\Traits\CastsDateOnly;

class Attendance extends Model
{
    use HasFactory, UserTracking, CastsDateOnly;
    protected $table = 'attendance';
    protected $primaryKey = 'id';

    protected $fillable = [
        'location',
        'course_id',
        'module_id',
        'intake_id',
        'semester',
        'date',
        'attendance_type',
        'session_start',
        'session_end',
        'student_id',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'int',
        'course_id' => 'int',
        'module_id' => 'int',
        'intake_id' => 'int',
        'student_id' => 'int',
        'date' => 'date',
        'session_start' => 'datetime:H:i:s',
        'session_end' => 'datetime:H:i:s',
        'status' => 'boolean',
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

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id', 'module_id');
    }

    public function intake()
    {
        return $this->belongsTo(Intake::class, 'intake_id', 'intake_id');
    }

    public function registration()
    {
        return $this->belongsTo(CourseRegistration::class, 'registration_id', 'id');
    }

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeByModule($query, $moduleId)
    {
        return $query->where('module_id', $moduleId);
    }

    public function scopeByIntake($query, $intakeId)
    {
        return $query->where('intake_id', $intakeId);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    public function scopeBySemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('date', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }

    // Accessors
    public function getFormattedDateAttribute()
    {
        return $this->date ? $this->date->format('d/m/Y') : 'N/A';
    }

    public function getDayOfWeekAttribute()
    {
        return $this->date ? $this->date->format('l') : 'N/A';
    }

    public function getLocationTextAttribute()
    {
        $locations = [
            'Welisara' => 'Welisara',
            'Moratuwa' => 'Moratuwa',
            'Peradeniya' => 'Peradeniya'
        ];
        
        return $locations[$this->location] ?? $this->location;
    }

    public function getSemesterTextAttribute()
    {
        return "Semester {$this->semester}";
    }

    // Methods
    public function getStudentAttendancePercentage($studentId, $courseId, $startDate = null, $endDate = null)
    {
        $query = self::where('student_id', $studentId)
                    ->where('course_id', $courseId);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $total = $query->count();
        $present = $query->where('status', true)->count();

        return $total > 0 ? round(($present / $total) * 100, 2) : 0;
    }

    public static function getIntakeAttendance($intakeId, $date)
    {
        return self::where('intake_id', $intakeId)
                  ->where('date', $date)
                  ->with('student')
                  ->get();
    }

    public static function getCourseAttendance($courseId, $date)
    {
        return self::where('course_id', $courseId)
                  ->where('date', $date)
                  ->with('student')
                  ->get();
    }

    public static function getModuleAttendance($moduleId, $date)
    {
        return self::where('module_id', $moduleId)
                  ->where('date', $date)
                  ->with('student')
                  ->get();
    }
}
