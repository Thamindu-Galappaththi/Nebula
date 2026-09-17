<?php

namespace App\Providers;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class);
    }

    public function boot(): void
    {
        if (!config('audit.enabled', true)) {
            return;
        }

        Event::listen('eloquent.created: *', function ($event, $payload) {
            $this->recordModelEvent($payload, 'created');
        });
        Event::listen('eloquent.updated: *', function ($event, $payload) {
            $this->recordModelEvent($payload, 'updated');
        });
        Event::listen('eloquent.deleted: *', function ($event, $payload) {
            $this->recordModelEvent($payload, 'deleted');
        });

        Event::listen(Login::class, function (Login $event) {
            app(AuditLogger::class)->recordAuth('login', $event->user);
        });
        Event::listen(Logout::class, function (Logout $event) {
            app(AuditLogger::class)->recordAuth('logout', $event->user);
        });
        Event::listen(Failed::class, function (Failed $event) {
            app(AuditLogger::class)->recordAuth(
                'login_failed',
                $event->user,
                $event->credentials['email'] ?? null
            );
        });
    }

    private function recordModelEvent($payload, string $action): void
    {
        $model = is_array($payload) ? ($payload[0] ?? null) : $payload;
        if ($model instanceof Model) {
            app(AuditLogger::class)->recordModel($model, $action);
        }
    }
}
