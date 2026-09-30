<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UserTracking;
use App\Traits\CastsDateOnly;

class Semester extends Model
{
    use HasFactory, UserTracking, CastsDateOnly;
    protected $fillable = [
        'name',
        'course_id',
        'intake_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function intake()
    {
        return $this->belongsTo(Intake::class, 'intake_id', 'intake_id');
    }

    public function semesterRegistrations()
    {
        return $this->hasMany(SemesterRegistration::class, 'semester_id', 'id');
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'semester_module', 'semester_id', 'module_id')
            ->withPivot('specialization', 'specializations');
    }

    /**
     * Status from start/end dates, not the last saved snapshot.
     */
    public function derivedStatus(): string
    {
        if (!$this->start_date || !$this->end_date) {
            return $this->status ?: 'completed';
        }

        $today = now()->startOfDay();
        $start = $this->start_date->copy()->startOfDay();
        $end = $this->end_date->copy()->startOfDay();

        if ($start->gt($today)) {
            return 'upcoming';
        }

        if ($end->gte($today)) {
            return 'active';
        }

        return 'completed';
    }

    /**
     * Short label for lists: A, B, C or 1, 2, 3.
     * Ignores a stored value that is the row id (for example 38).
     */
    public function displayName(): string
    {
        return self::labelForSlot(
            $this->resolvedSlotNumber(),
            optional($this->course)->semester_format
        );
    }

    public static function labelForSlot(int $number, ?string $format): string
    {
        if ($format === 'alphabetical' && $number >= 1 && $number <= 26) {
            return chr(64 + $number);
        }

        return (string) $number;
    }

    /**
     * True when name is the auto-increment id (31, 37, 38) instead of A/B/1/2.
     */
    public function nameLooksLikeRowId(): bool
    {
        $name = trim((string) $this->name);
        if ($name === '' || !preg_match('/^\d+$/', $name)) {
            return false;
        }

        $number = (int) $name;
        $max = (int) (optional($this->course)->no_of_semesters ?? 0);

        if ($max > 0) {
            return $number > $max;
        }

        return $number === (int) $this->id;
    }

    public static function slotFromInput(string $raw, Course $course): ?int
    {
        $raw = trim($raw);
        $max = (int) $course->no_of_semesters;

        if (preg_match('/^Semester\s+([A-Za-z])$/i', $raw, $matches) || preg_match('/^([A-Za-z])$/', $raw, $matches)) {
            $offset = ord(strtoupper($matches[1])) - 64;
            if ($offset >= 1 && $offset <= 26 && ($max === 0 || $offset <= $max)) {
                return $offset;
            }

            return null;
        }

        if (preg_match('/^(?:Semester\s+)?(\d+)$/i', $raw, $matches)) {
            $number = (int) $matches[1];
            if ($number >= 1 && ($max === 0 || $number <= $max)) {
                return $number;
            }
        }

        return null;
    }

    public function resolvedSlotNumber(): int
    {
        $max = (int) (optional($this->course)->no_of_semesters ?? 0);
        $name = trim((string) $this->name);

        if (preg_match('/^([A-Za-z])$/', $name, $matches)) {
            $offset = ord(strtoupper($matches[1])) - 64;
            if ($offset >= 1 && $offset <= 26 && ($max === 0 || $offset <= $max)) {
                return $offset;
            }
        }

        if (preg_match('/(\d+)/', $name, $matches)) {
            $number = (int) $matches[1];
            $looksLikeId = $number === (int) $this->id;
            $outOfRange = $max > 0 && $number > $max;
            if ($number >= 1 && !$looksLikeId && !$outOfRange) {
                return $number;
            }
        }

        return $this->sequenceSlotNumber();
    }

    private function sequenceSlotNumber(): int
    {
        if (isset($this->attributes['sequence_number'])) {
            return max(1, (int) $this->attributes['sequence_number']);
        }

        $ids = static::query()
            ->where('course_id', $this->course_id)
            ->where('intake_id', $this->intake_id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->pluck('id');

        $position = $ids->search($this->id);

        return $position === false ? 1 : $position + 1;
    }
}
