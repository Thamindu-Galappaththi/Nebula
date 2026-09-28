<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>NEBULA | We’ll be back soon</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logos/nebula.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logos/nebula.png') }}">

    <link href="{{ asset('css/styles.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/login.css') }}?v={{ file_exists(public_path('css/login.css')) ? filemtime(public_path('css/login.css')) : time() }}" rel="stylesheet">
</head>

<body class="login-page loaded maintenance-page">
    <div class="login-shell">
        <main class="login-main">
            <div class="login-card card mb-0">
                <div class="card-body text-center">
                    <div class="login-logo-link">
                        <img src="{{ asset('images/logos/nebula.png') }}" alt="Nebula" class="login-logo img-fluid">
                    </div>

                    <h1 class="maintenance-title">We’ll be back soon</h1>
                    <p class="maintenance-copy">
                        Nebula Institute of Technology is temporarily unavailable for maintenance.
                        Please try again in a few minutes.
                    </p>
                </div>
            </div>
        </main>

        <footer class="login-footer footer bg-dark text-light text-center">
            <p class="mb-0">&copy; {{ date('Y') }} Nebula Institute of Technology. All rights reserved.</p>
        </footer>
    </div>
</body>

</html>
