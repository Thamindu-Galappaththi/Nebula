<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>NEBULA | Sign In</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logos/nebula.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logos/nebula.png') }}">

    <link nonce="{{ $cspNonce }}" href="{{ asset('css/styles.min.css') }}" rel="stylesheet">
    <link nonce="{{ $cspNonce }}" href="{{ asset('css/login.css') }}?v={{ filemtime(public_path('css/login.css')) }}" rel="stylesheet">
    <link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" integrity="sha384-Ay26V7L8bsJTsX9Sxclnvsn+hkdiwRnrjZJXqKmkIDobPgIIWBOVguEcQQLDuhfN" crossorigin="anonymous">

    <script nonce="{{ $cspNonce }}" src="{{ asset('libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script nonce="{{ $cspNonce }}" src="{{ asset('js/login.js') }}?v={{ filemtime(public_path('js/login.js')) }}"></script>
</head>

<body class="login-page">
    <div class="login-shell">
        <main class="login-main">
            <div class="login-card card mb-0">
                <div class="card-body">
                    <a href="{{ route('login') }}" class="login-logo-link text-center d-block">
                        <img src="{{ asset('images/logos/nebula.png') }}" alt="Nebula" class="login-logo img-fluid" loading="lazy">
                    </a>

                    <form id="loginForm" method="POST" action="{{ route('login.authenticate') }}" class="login-form">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Username</label>
                            <input type="email"
                                id="email"
                                name="email"
                                class="form-control form-control-lg @error('email') is-invalid @enderror"
                                placeholder="Enter your username"
                                value="{{ old('email') }}"
                                autocomplete="username"
                                inputmode="email"
                                enterkeyhint="next"
                                required>
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group login-password-group @error('password') has-validation @enderror">
                                <input type="password"
                                    id="password"
                                    name="password"
                                    class="form-control form-control-lg @error('password') is-invalid @enderror"
                                    placeholder="Enter your password"
                                    autocomplete="current-password"
                                    enterkeyhint="go"
                                    required>
                                <button type="button" class="btn btn-password" id="togglePassword" aria-label="Show password" title="Show password">
                                    <i id="togglePasswordIcon" class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @error('login')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fs-5 rounded-2" id="loginSubmitBtn">
                            Sign In
                        </button>
                    </form>
                </div>
            </div>
        </main>

        <footer class="login-footer footer bg-dark text-light text-center">
            <p class="mb-0">&copy; {{ date('Y') }} Nebula Institute of Technology. All rights reserved.</p>
        </footer>
    </div>
</body>

</html>
