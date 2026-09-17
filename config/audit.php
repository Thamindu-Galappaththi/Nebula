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

];
