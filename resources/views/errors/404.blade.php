<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>NEBULA | Page not found</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logos/nebula.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logos/nebula.png') }}">
    <link href="{{ asset('css/styles.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/login.css') }}?v={{ file_exists(public_path('css/login.css')) ? filemtime(public_path('css/login.css')) : time() }}" rel="stylesheet">
</head>
@php
    $path = '/' . ltrim(request()->path(), '/');
    $signedIn = auth()->check();
    $isDocumentRecord = request()->is('student-documents') || request()->is('student-documents/*');
    $isDocumentFile = request()->is('student-documents/files/*');
    $isSubmissionPortal = request()->is('document-submit') || request()->is('document-submit/*');

    if ($isDocumentFile) {
        $title = 'This document is no longer available';
        $message = 'The file you tried to open is missing, has already been deleted after the retention period, or the address is incorrect.';
        $primaryLabel = $signedIn ? 'Back to document collection' : 'Go to sign in';
        $primaryUrl = $signedIn ? url('/student-documents') : url('/login');
    } elseif ($isDocumentRecord) {
        $title = 'This document record was not found';
        $message = 'The document collection record you opened is missing, was removed, or the address is incorrect. Open the collection list to find a current record.';
        $primaryLabel = $signedIn ? 'Back to document collection' : 'Go to sign in';
        $primaryUrl = $signedIn ? url('/student-documents') : url('/login');
    } elseif ($isSubmissionPortal) {
        $title = 'This submission link is not valid';
        $message = 'The document submission link is missing, incorrect, or has already been removed. Please contact the staff member who sent it.';
        $primaryLabel = 'Go to Nebula';
        $primaryUrl = url('/dashboard');
    } else {
        $title = 'This page was not found';
        $message = 'The address you entered does not match a page in Nebula. Check the link and try again, or go back to a page you know.';
        $primaryLabel = $signedIn ? 'Back to dashboard' : 'Go to Nebula';
        $primaryUrl = url('/dashboard');
    }
@endphp
<body class="login-page loaded">
    <div class="login-shell">
        <main class="login-main">
            <div class="login-card card mb-0">
                <div class="card-body text-center">
                    <div class="login-logo-link">
                        <img src="{{ asset('images/logos/nebula.png') }}" alt="Nebula" class="login-logo img-fluid">
                    </div>

                    <h1 class="maintenance-title">{{ $title }}</h1>
                    <p class="maintenance-copy">{{ $message }}</p>
                    <p class="text-muted small mt-3 mb-0">Requested: {{ $path }}</p>

                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                        <a href="{{ $primaryUrl }}" class="btn btn-primary">{{ $primaryLabel }}</a>
                        @if($signedIn && $isDocumentRecord)
                            <a href="{{ url('/dashboard') }}" class="btn btn-outline-secondary">Dashboard</a>
                        @endif
                    </div>
                </div>
            </div>
        </main>

        <footer class="login-footer footer bg-dark text-light text-center">
            <p class="mb-0">&copy; {{ date('Y') }} Nebula Institute of Technology. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
