<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'user_role',
        'action',
        'summary',
        'auditable_type',
        'auditable_id',
        'item_label',
        'old_values',
        'new_values',
        'method',
        'route',
        'url',
        'path',
        'ip_address',
        'location',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function occurredAtSriLanka(): Carbon
    {
        $value = $this->created_at ?: now();

        return $value->copy()->timezone('Asia/Colombo');
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'login' => 'Login',
            'logout' => 'Logout',
            'login_failed' => 'Failed login',
            'submitted' => 'Submitted',
            default => ucfirst(str_replace('_', ' ', (string) $this->action)),
        };
    }

    public function actionBadgeClass(): string
    {
        return match ($this->action) {
            'created', 'login' => 'bg-success',
            'updated', 'submitted' => 'bg-warning text-dark',
            'deleted', 'login_failed' => 'bg-danger',
            'logout' => 'bg-secondary',
            default => 'bg-primary',
        };
    }
}
