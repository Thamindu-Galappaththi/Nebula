<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit logging
    |--------------------------------------------------------------------------
    |
    | Set AUDIT_LOGGING=false in .env to stop storing new audit rows.
    | Existing rows stay, and Developers can still open the Audit Log page.
    | After changing .env, run: php artisan config:clear
    |
    */

    'enabled' => filter_var(env('AUDIT_LOGGING', true), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Scheduled command `audit:prune` deletes audit_logs older than this many
    | days. Change AUDIT_LOG_RETENTION_DAYS in .env anytime (7, 3, 30, ...).
    | After changing .env, run: php artisan config:clear
    | One-off override: php artisan audit:prune --days=3
    |
    */

    'retention_days' => max(1, (int) env('AUDIT_LOG_RETENTION_DAYS', 7)),

];
