<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuditLogger
{
    private static int $loggedThisRequest = 0;

    public function resetRequestState(): void
    {
        self::$loggedThisRequest = 0;
    }

    public function enabled(): bool
    {
        return (bool) config('audit.enabled', true);
    }

    private const IGNORE_TABLES = [
        'audit_logs',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
        'password_resets',
        'personal_access_tokens',
        'migrations',
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
    ];

    private const IGNORE_ATTRIBUTES = [
        'password',
        'password_confirmation',
        'remember_token',
        'api_token',
        'token',
        'secret',
        'otp',
        'pin',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_by',
        'email_verified_at',
    ];

    public function recordModel(Model $model, string $action): void
    {
        if (!$this->enabled() || !$this->shouldLogModel($model)) {
            return;
        }

        $newValues = [];
        $oldValues = [];

        if ($action === 'created') {
            $newValues = $this->sanitizeAttributes($model->getAttributes());
        } elseif ($action === 'deleted') {
            $oldValues = $this->sanitizeAttributes($model->getAttributes());
        } else {
            $changes = $model->getChanges();
            unset($changes['updated_at'], $changes['updated_by']);
            if (empty($changes)) {
                return;
            }
            foreach ($changes as $key => $value) {
                if ($this->isIgnoredAttribute($key)) {
                    continue;
                }
                $newValues[$key] = $this->stringify($value);
                $oldValues[$key] = $this->stringify($model->getOriginal($key));
            }
            if (empty($newValues)) {
                return;
            }
        }

        $item = $this->itemLabel($model);
        $this->write(array_merge($this->actorPayload(), $this->requestPayload(), [
            'action' => $action,
            'summary' => $this->modelSummary($action, $item, $oldValues, $newValues),
            'auditable_type' => $model::class,
            'auditable_id' => (string) $model->getKey(),
            'item_label' => $item,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
        ]));
    }

    public function recordAuth(string $action, $user = null, ?string $email = null): void
    {
        if (!$this->enabled()) {
            return;
        }

        $actor = $this->actorFromUser($user instanceof User ? $user : null, $email);
        $name = $actor['user_name'] ?: ($email ?: 'Unknown user');
        $role = $actor['user_role'] ?: 'unauthenticated';

        $summary = match ($action) {
            'login' => "{$name} ({$role}) logged in.",
            'logout' => "{$name} ({$role}) logged out.",
            'login_failed' => "Failed login attempt" . ($email ? " for {$email}." : '.'),
            default => "{$name} ({$role}) {$action}.",
        };

        $this->write(array_merge($actor, $this->requestPayload(), [
            'action' => $action,
            'summary' => $summary,
            'auditable_type' => $user instanceof User ? User::class : null,
            'auditable_id' => $user instanceof User ? (string) $user->getKey() : null,
            'item_label' => $user instanceof User ? 'User ' . $user->name : ($email ?: 'Login'),
        ]));
    }

    public function recordRequestFallback(Request $request): void
    {
        if (!$this->enabled() || self::$loggedThisRequest > 0) {
            return;
        }

        if ($request->routeIs('login.authenticate') && !Auth::check()) {
            $this->recordAuth('login_failed', null, $request->input('email'));
            return;
        }

        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        if ($request->routeIs(['login', 'login.authenticate', 'logout', 'audit.log', 'refresh.csrf', 'log.js.error'])) {
            return;
        }

        if (Str::startsWith($request->path(), ['audit-log', 'livewire', '_debugbar', 'telescope', 'horizon'])) {
            return;
        }

        if (!Auth::check()) {
            return;
        }

        if ($this->isLookupRequest($request)) {
            return;
        }

        $actor = $this->actorPayload();
        $path = '/' . ltrim($request->path(), '/');
        $route = optional($request->route())->getName();
        $name = $actor['user_name'] ?: 'Staff';
        $role = $actor['user_role'] ?: 'Unknown role';

        $this->write(array_merge($actor, $this->requestPayload($request), [
            'action' => 'submitted',
            'summary' => "{$name} ({$role}) submitted {$request->method()} {$path}" . ($route ? " ({$route})." : '.'),
            'item_label' => $route ?: $path,
        ]));
    }

    private function shouldLogModel(Model $model): bool
    {
        if ($model instanceof \App\Models\AuditLog) {
            return false;
        }

        if (app()->runningInConsole() && !app()->environment('testing')) {
            return false;
        }

        $table = $model->getTable();
        if (in_array($table, self::IGNORE_TABLES, true)) {
            return false;
        }

        return true;
    }

    private function isLookupRequest(Request $request): bool
    {
        $route = strtolower((string) optional($request->route())->getName());
        $path = strtolower('/' . ltrim($request->path(), '/'));
        $haystack = $route . ' ' . $path;

        foreach (['.get.', 'get.', '/get-', '.fetch.', '/fetch-', '.load.', '/load-', '.search', '/search', 'dropdown', '.options', 'by.category', 'by-category', 'by.location', 'by-location', 'by.course', 'by-course'] as $hint) {
            if (str_contains($haystack, $hint)) {
                return true;
            }
        }

        $isJson = $request->ajax() || $request->expectsJson() || $request->wantsJson();
        if (!$isJson) {
            return false;
        }

        foreach (['.store', '.update', '.destroy', '.delete', '.create', '.save', '.approve', '.reject', '.terminate', '.register', '.upload', '.import', '.export', '.sync', '.assign', '.remove', '.bulk'] as $hint) {
            if (str_contains($haystack, $hint)) {
                return false;
            }
        }

        return true;
    }

    private function isDuplicate(array $payload): bool
    {
        try {
            return DB::table('audit_logs')
                ->where('user_id', $payload['user_id'] ?? null)
                ->where('action', $payload['action'] ?? '')
                ->where('path', $payload['path'] ?? null)
                ->where('item_label', $payload['item_label'] ?? null)
                ->where('auditable_id', $payload['auditable_id'] ?? null)
                ->where('created_at', '>=', now()->timezone('Asia/Colombo')->subSeconds(10)->format('Y-m-d H:i:s'))
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function write(array $payload): void
    {
        if (!$this->enabled() || !$this->tableReady()) {
            return;
        }

        if ($this->isDuplicate($payload)) {
            return;
        }

        $now = now()->timezone('Asia/Colombo')->format('Y-m-d H:i:s');

        try {
            DB::table('audit_logs')->insert([
                'user_id' => $payload['user_id'] ?? null,
                'user_name' => $this->limit($payload['user_name'] ?? null, 255),
                'user_email' => $this->limit($payload['user_email'] ?? null, 255),
                'user_role' => $this->limit($payload['user_role'] ?? null, 255),
                'action' => $this->limit($payload['action'] ?? 'updated', 40),
                'summary' => $payload['summary'] ?? 'Staff made a change.',
                'auditable_type' => $this->limit($payload['auditable_type'] ?? null, 255),
                'auditable_id' => $this->limit($payload['auditable_id'] ?? null, 255),
                'item_label' => $this->limit($payload['item_label'] ?? null, 255),
                'old_values' => isset($payload['old_values']) ? json_encode($payload['old_values']) : null,
                'new_values' => isset($payload['new_values']) ? json_encode($payload['new_values']) : null,
                'method' => $this->limit($payload['method'] ?? null, 10),
                'route' => $this->limit($payload['route'] ?? null, 255),
                'url' => $payload['url'] ?? null,
                'path' => $this->limit($payload['path'] ?? null, 255),
                'ip_address' => $this->limit($payload['ip_address'] ?? null, 45),
                'location' => $this->limit($payload['location'] ?? null, 255),
                'user_agent' => $this->limit($payload['user_agent'] ?? null, 500),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            self::$loggedThisRequest++;
        } catch (\Throwable $e) {
            Log::warning('Audit log write failed: ' . $e->getMessage());
        }
    }

    private function tableReady(): bool
    {
        if (app()->bound('audit.table_ready') && app('audit.table_ready') === true) {
            return true;
        }

        try {
            $ready = Schema::hasTable('audit_logs');
        } catch (\Throwable $e) {
            return false;
        }

        if ($ready) {
            app()->instance('audit.table_ready', true);
        }

        return $ready;
    }

    private function actorPayload(): array
    {
        return $this->actorFromUser(Auth::user());
    }

    private function actorFromUser($user, ?string $fallbackEmail = null): array
    {
        if ($user instanceof User) {
            $roles = $user->getRoleList();
            return [
                'user_id' => $user->getKey(),
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_role' => $user->user_role ?: implode(', ', $roles),
            ];
        }

        $authUser = Auth::user();

        return [
            'user_id' => $authUser?->getKey(),
            'user_name' => $authUser?->name,
            'user_email' => $fallbackEmail ?: $authUser?->email,
            'user_role' => $authUser?->user_role,
        ];
    }

    private function requestPayload(?Request $request = null): array
    {
        $request = $request ?: request();
        $context = app()->bound('audit.context') ? app('audit.context') : [];
        $user = Auth::user();

        $ip = $context['ip'] ?? ($request ? $request->ip() : null);
        $country = $context['country'] ?? ($request ? ($request->headers->get('CF-IPCountry') ?: $request->headers->get('X-AppEngine-Country')) : null);
        $campus = $user?->user_location;

        $locationParts = [];
        if ($campus) {
            $locationParts[] = $campus;
        }
        if ($ip && in_array($ip, ['127.0.0.1', '::1'], true)) {
            $locationParts[] = 'Local';
        } elseif (is_string($country) && $country !== '' && strtoupper($country) !== 'XX') {
            $locationParts[] = strtoupper($country);
        } elseif ($ip) {
            $locationParts[] = 'Sri Lanka';
        }

        $path = $context['path'] ?? ($request ? '/' . ltrim($request->path(), '/') : null);

        return [
            'method' => $context['method'] ?? ($request ? $request->method() : null),
            'route' => $context['route'] ?? optional($request?->route())->getName(),
            'url' => $context['url'] ?? ($request ? $request->fullUrl() : null),
            'path' => $path === '//' ? '/' : $path,
            'ip_address' => $ip,
            'location' => implode(' / ', array_unique(array_filter($locationParts))) ?: null,
            'user_agent' => $context['user_agent'] ?? ($request ? substr((string) $request->userAgent(), 0, 500) : null),
        ];
    }

    private function modelSummary(string $action, string $item, array $oldValues, array $newValues): string
    {
        $actor = $this->actorPayload();
        $name = $actor['user_name'] ?: 'Staff';
        $role = $actor['user_role'] ?: 'Unknown role';
        $verb = match ($action) {
            'created' => 'created',
            'deleted' => 'deleted',
            default => 'updated',
        };

        $summary = "{$name} ({$role}) {$verb} {$item}.";

        if ($action === 'updated' && $newValues) {
            $bits = [];
            foreach ($newValues as $field => $value) {
                $from = $oldValues[$field] ?? 'empty';
                $bits[] = $this->humanField($field) . ': ' . $from . ' → ' . $value;
                if (count($bits) >= 6) {
                    break;
                }
            }
            if ($bits) {
                $summary .= ' ' . implode('; ', $bits) . '.';
            }
        }

        return $summary;
    }

    private function itemLabel(Model $model): string
    {
        $type = Str::headline(class_basename($model));

        foreach (['batch', 'course_name', 'module_name', 'full_name', 'name', 'title', 'email', 'student_id', 'course_registration_id', 'employee_id'] as $field) {
            $value = $model->getAttribute($field);
            if ($value !== null && $value !== '') {
                return $type . ' ' . $this->stringify($value);
            }
        }

        $key = $model->getKey();
        return $key ? $type . ' #' . $key : $type;
    }

    private function sanitizeAttributes(array $attributes): array
    {
        $clean = [];
        foreach ($attributes as $key => $value) {
            if ($this->isIgnoredAttribute($key)) {
                continue;
            }
            $clean[$key] = $this->stringify($value);
        }
        return $clean;
    }

    private function isIgnoredAttribute(string $key): bool
    {
        $key = strtolower($key);
        if (in_array($key, self::IGNORE_ATTRIBUTES, true)) {
            return true;
        }

        return Str::contains($key, ['password', 'token', 'secret', 'otp']);
    }

    private function stringify($value): string
    {
        if ($value === null) {
            return 'empty';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_array($value)) {
            $value = json_encode($value);
        }

        $text = trim((string) $value);
        if (strlen($text) > 180) {
            return substr($text, 0, 177) . '...';
        }

        return $text === '' ? 'empty' : $text;
    }

    private function humanField(string $field): string
    {
        return strtolower(str_replace('_', ' ', $field));
    }

    private function limit($value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Str::limit((string) $value, $max, '');
    }
}
