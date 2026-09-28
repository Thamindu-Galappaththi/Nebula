<?php
// app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (!app()->environment('local')) {
            URL::forceScheme('https');

            config([
                'session.secure' => true,
                'session.http_only' => true,
                'session.same_site' => 'strict',
            ]);

            Cookie::setDefaultPathAndDomain(
                config('session.path', '/'),
                config('session.domain'),
                true,
                true,
                false,
                config('session.same_site', 'strict')
            );
        } else {
            // Browsers drop Secure cookies on http://127.0.0.1, which causes 419 on login.
            config(['session.secure' => false]);
        }

        // Register nonce directive
        Blade::directive('nonce', function () {
            return "<?php echo 'nonce=\"' . app('csp_nonce', '') . '\"'; ?>";
        });
    }
}