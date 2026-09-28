<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens; // Add this line
use App\Traits\UserTracking;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, UserTracking; // Add HasApiTokens here

    protected $table = 'users'; // Set the table name

    protected $primaryKey = 'user_id'; // Set the primary key

    protected $fillable = [
        'name',
        'email',
        'employee_id',
        'password',
        'user_role',
        'user_roles',
        'status',        
        'user_profile',
        'user_location',
        'active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'user_roles' => 'array',
        // Remove status boolean cast since it's stored as string
        // 'status' => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public function getRoleList(): array
    {
        $roles = [];

        if (is_array($this->user_roles)) {
            $roles = $this->user_roles;
        } elseif (is_string($this->user_roles) && $this->user_roles !== '') {
            $decoded = json_decode($this->user_roles, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $roles = $decoded;
            }
        }

        $roles = array_values(array_filter(array_map(function ($role) {
            return is_string($role) ? trim($role) : '';
        }, $roles)));

        $primaryRole = $this->getAttribute('user_role');
        if (is_string($primaryRole) && trim($primaryRole) !== '') {
            array_unshift($roles, trim($primaryRole));
        }

        return array_values(array_unique($roles));
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoleList(), true);
    }

    public function hasAnyRole(array $requiredRoles): bool
    {
        if (empty($requiredRoles)) {
            return true;
        }

        $requiredRoles = array_values(array_filter(array_map(function ($role) {
            return is_string($role) ? trim($role) : '';
        }, $requiredRoles)));

        if (empty($requiredRoles)) {
            return false;
        }

        return count(array_intersect($this->getRoleList(), $requiredRoles)) > 0;
    }

    public function hasAssignedRoles(): bool
    {
        return !empty($this->getRoleList());
    }

    public function createdAtSriLanka(): ?Carbon
    {
        return $this->toSriLankaTime($this->created_at);
    }

    public function updatedAtSriLanka(): ?Carbon
    {
        return $this->toSriLankaTime($this->updated_at);
    }

    private function toSriLankaTime($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        $carbon = $value instanceof Carbon
            ? $value->copy()
            : Carbon::parse($value, config('app.timezone'));

        return $carbon->timezone('Asia/Colombo');
    }
}
