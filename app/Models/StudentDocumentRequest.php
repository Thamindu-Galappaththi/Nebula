<?php

namespace App\Models;

use App\Traits\UserTracking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class StudentDocumentRequest extends Model
{
    use UserTracking;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PURGED = 'purged';

    public const NOTIFICATION_PENDING = 'pending';
    public const NOTIFICATION_SENT = 'sent';
    public const NOTIFICATION_FAILED = 'failed';

    public const DELETION_RETAINED = 'retained';
    public const DELETION_PURGED = 'purged';

    protected $hidden = [
        'token_hash',
        'access_token',
    ];

    protected $fillable = [
        'token_hash',
        'access_token',
        'student_id',
        'created_by',
        'updated_by',
        'reviewed_by',
        'status',
        'notification_status',
        'document_deletion_status',
        'link_expires_at',
        'submitted_at',
        'retention_expires_at',
        'notified_at',
        'reviewed_at',
        'purged_at',
        'submitted_full_name',
        'submitted_id_value',
        'submitted_email',
        'submitted_phone',
        'submitted_notes',
        'submitted_file_count',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'link_expires_at' => 'datetime',
        'submitted_at' => 'datetime',
        'retention_expires_at' => 'datetime',
        'notified_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'purged_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }

    public function files()
    {
        return $this->hasMany(StudentDocumentFile::class, 'document_request_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->link_expires_at
            && $this->link_expires_at->isFuture();
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isAccessibleToStudent(): bool
    {
        return $this->isPending();
    }

    public function documentsAreAvailable(): bool
    {
        return $this->status === self::STATUS_SUBMITTED
            && $this->document_deletion_status === self::DELETION_RETAINED
            && $this->retention_expires_at
            && $this->retention_expires_at->isFuture();
    }

    public function retentionDaysRemaining(): ?int
    {
        if (!$this->retention_expires_at || !$this->documentsAreAvailable()) {
            return null;
        }

        return max(0, (int) Carbon::now()->startOfDay()->diffInDays($this->retention_expires_at->copy()->startOfDay(), false));
    }
}
