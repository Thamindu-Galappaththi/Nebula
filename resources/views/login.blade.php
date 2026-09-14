<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>NEBULA | Sign In</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logos/nebula.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logos/nebula.png') }}">

    <!-- CSS -->
    <link nonce="{{ $cspNonce }}" href="{{ asset('css/styles.min.css') }}" rel="stylesheet">
    <link nonce="{{ $cspNonce }}" href="{{ asset('css/login.css') }}" rel="stylesheet">
    <link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" integrity="sha384-Ay26V7L8bsJTsX9Sxclnvsn+hkdiwRnrjZJXqKmkIDobPgIIWBOVguEcQQLDuhfN" crossorigin="anonymous">

    <!-- JS -->
    <script nonce="{{ $cspNonce }}" src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>
    <script nonce="{{ $cspNonce }}" src="{{ asset('libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script nonce="{{ $cspNonce }}" src="{{ asset('js/login.js') }}"></script>
</head>

<body>
    <div class="page-wrapper" id="main-wrapper">

        <div class="position-relative radial-gradient min-vh-100 d-flex align-items-center justify-content-center">
            <div class="row justify-content-center w-100">
                <div class="col-md-8 col-lg-6 col-xxl-3">

                    <div class="card mb-0">
                        <div class="card-body">

                            <a href="./" class="text-center d-block py-3 w-100">
                                <img src="{{ asset('images/logos/nebula.png') }}" alt="Nebula" class="img-fluid" loading="lazy">
                            </a>

                            @php
                                $inlineErrors = collect($loginErrors ?? [])->filter();
                                $frameworkErrors = ($errors ?? collect())->any() ? collect($errors->all()) : collect();
                                $allErrors = $inlineErrors->values()->merge($frameworkErrors)->unique();
                            @endphp

                            @if ($allErrors->isNotEmpty())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($allErrors as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form id="loginForm" method="POST" action="{{ route('login.authenticate') }}" class="pt-3">
                                @csrf

                                <div class="form-group mb-3">
                                    <label for="email">Username</label>
                                    <input type="email"
                                        id="email"
                                        name="email"
                                        class="form-control form-control-lg @error('email') is-invalid @enderror"
                                        placeholder="Enter your username"
                                        value="{{ $submittedEmail ?? old('email') }}"
                                        autocomplete="email"
                                        required>

                                    @error('email')
                                        <div class="error-message">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-4">
                                    <label for="password">Password</label>

                                    <div class="input-group">
                                        <input type="password"
                                               id="password"
                                               name="password"
                                               class="form-control form-control-lg @error('password') is-invalid @enderror"
                                               placeholder="Enter your password"
                                               autocomplete="current-password"
                                               required>

                                        <span class="input-group-text btn-password" id="togglePassword">
                                            <i id="togglePasswordIcon" class="bi bi-eye"></i>
                                        </span>
                                    </div>

                                    @error('password')
                                        <div class="error-message">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2 fs-4 rounded-2">
                                    Sign In
                                </button>

                            </form>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <footer class="footer bg-dark text-light text-center py-3">
        <p class="mb-0">&copy; <span id="currentYear">{{ date('Y') }}</span> Nebula. All rights reserved.</p>
    </footer>
</body>

</html>
