@php
    $isEmbed = request()->boolean('embed');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"@if($isEmbed) class="embed-mode"@endif>
<!-- ϥϙϜϞϧϰαα -->

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'NEBULA')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}"> <!-- CSRF Token -->
    <meta name="csp-nonce" content="{{ $cspNonce }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logos/favicon.jpg') }}" />

    <!-- Tabler Icons CSS -->
    <link nonce="{{ $cspNonce }}" rel="stylesheet" href="{{ asset('css/icons/tabler-icons/tabler-icons.css') }}">
    <!-- Bootstrap Icons (CDN) -->
    <link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" integrity="sha384-Ay26V7L8bsJTsX9Sxclnvsn+hkdiwRnrjZJXqKmkIDobPgIIWBOVguEcQQLDuhfN" crossorigin="anonymous">

    <!-- CSS -->
    <link nonce="{{ $cspNonce }}" href="{{ asset('css/styles.min.css') }}" rel="stylesheet">
    

    <!-- JS -->
    <script nonce="{{ $cspNonce }}" src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>
    <script nonce="{{ $cspNonce }}" src="{{ asset('libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    @unless($isEmbed)
    <script nonce="{{ $cspNonce }}" src="{{ asset('libs/simplebar/dist/simplebar.js') }}"></script>
    <!-- Sidebar + layout interactions (hamburger toggle, responsive sidebar) -->
    <script nonce="{{ $cspNonce }}" src="{{ asset('js/app.min.js') }}"></script>
    <script nonce="{{ $cspNonce }}" src="{{ asset('js/sidebarmenu.js') }}"></script>
    @endunless
    <!-- Global utilities for error handling and CSRF management -->
    <script nonce="{{ $cspNonce }}" src="{{ asset('js/global-utilities.js') }}"></script>
    <script nonce="{{ $cspNonce }}" src="{{ asset('js/nebula-select.js') }}?v={{ file_exists(public_path('js/nebula-select.js')) ? filemtime(public_path('js/nebula-select.js')) : time() }}"></script>
    <style nonce="{{ $cspNonce }}">
        body {
            background: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E') no-repeat center center fixed;
            background-size: cover;
            width: 100%;
            height: 100vh;
        }

        body.loaded {
            background-image: url('{{ asset('images/backgrounds/nebula.jpg') }}');
        }

        .left-sidebar {
            z-index: 1050;
        }
        #main-wrapper[data-layout=vertical][data-header-position=fixed] .app-header {
            z-index: 1040;
        }
        @media (max-width: 1199.98px) {
            #main-wrapper.show-sidebar .nebula-select-menu {
                display: none !important;
            }
        }

        html.embed-mode,
        body.embed-mode {
            height: auto !important;
            min-height: 0 !important;
            overflow-x: auto;
        }

        body.embed-mode {
            display: block !important;
            background-image: none !important;
            background-color: #f6f8fb;
        }

        body.embed-mode .page-wrapper {
            min-height: 0 !important;
            height: auto !important;
        }

        body.embed-mode .body-wrapper {
            margin-left: 0 !important;
            min-height: 0 !important;
            height: auto !important;
        }

        body.embed-mode #main-wrapper[data-layout=vertical][data-header-position=fixed] .body-wrapper > .container-fluid {
            padding: 12px !important;
            max-width: 100% !important;
        }

        .navbar {
            box-shadow: 0 8px 8px -8px rgba(0, 0, 0, 0.1);
        }

        .dropdown-menu-outline-shadow {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        /* Apply the class to your dropdown menu */
        .dropdown-menu.dropdown-menu-end.dropdown-menu-animate-up.bg-light-primary.outline-shadow {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        select.form-select,
        select.form-control {
            max-width: 100%;
        }
        .nebula-select {
            position: relative;
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }
        .nebula-select-sm {
            width: 5.75rem;
            max-width: 5.75rem;
            flex: 0 0 5.75rem;
        }
        select.form-select.nebula-select-native,
        select.form-control.nebula-select-native,
        .nebula-select-native {
            display: none !important;
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 1px !important;
            height: 1px !important;
            opacity: 0 !important;
            pointer-events: none !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            clip-path: inset(50%) !important;
            appearance: none !important;
        }
        .nebula-select-toggle {
            width: 100%;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            text-align: left;
        }
        .nebula-select-menu {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 2000;
            width: 100%;
            max-width: min(100vw - 24px, 24rem);
            box-sizing: border-box;
            max-height: min(240px, 50vh);
            overflow: hidden;
            background: #fff;
            border: 1px solid #d9e0ea;
            border-radius: 8px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.16);
        }
        .nebula-select.is-open .nebula-select-menu {
            display: flex;
            flex-direction: column;
        }
        .nebula-select-search {
            position: sticky;
            top: 0;
            z-index: 1;
            padding: 0.45rem 0.45rem 0.35rem;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
        }
        .nebula-select-search-input {
            width: 100%;
            min-height: 34px;
        }
        .nebula-select-options {
            overflow-x: hidden;
            overflow-y: auto;
            flex: 1 1 auto;
            min-height: 0;
        }
        .nebula-select-empty {
            padding: 0.65rem 0.75rem;
            color: #64748b;
            font-size: 0.85rem;
        }
        .nebula-select-option {
            display: block;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            border: 0;
            background: #fff;
            color: #1f2937;
            text-align: left;
            padding: 0.55rem 0.75rem;
            font-size: 0.9rem;
            line-height: 1.35;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .nebula-select-option:hover,
        .nebula-select-option:focus {
            background: #eef4ff;
            color: #1f2937;
        }
        .nebula-select-option.is-selected {
            background: #0d6efd;
            color: #fff;
        }
        .nebula-select-option.is-disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
        select.page-size-select,
        select#perPageSelect {
            width: 5.75rem;
            max-width: 5.75rem;
            flex: 0 0 5.75rem;
        }
        @media (max-width: 767.98px) {
            select.form-select,
            select.form-control,
            .nebula-select-toggle {
                font-size: 16px;
            }
            .dropdown-menu {
                max-width: min(calc(100vw - 1.5rem), 24rem);
            }
        }
    </style>

    <script nonce="{{ $cspNonce }}">
        document.addEventListener('DOMContentLoaded', function() {
            const body = document.querySelector('body');
            body.classList.add('loaded');
        });
    </script>
</head>

<body class="d-flex flex-column{{ $isEmbed ? ' embed-mode' : '' }}">
    

    <!--  Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">
        @unless($isEmbed)
        <!-- Sidebar Start -->
        <aside class="left-sidebar">
            <!-- Sidebar scroll-->
            @include('components.sidebar')
            <!-- End Sidebar scroll-->
        </aside>
        <div>
<script nonce="{{ $cspNonce }}">
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.querySelector('.scroll-sidebar');
        const activeLink = sidebar?.querySelector('.sidebar-link.active');
        if (activeLink && sidebar) {
            activeLink.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>

        </div>


        <!--  Sidebar End -->
        @endunless
        <!--  Main wrapper -->
        <div class="body-wrapper d-flex flex-column min-vh-100">
            @unless($isEmbed)
            <!--  Header Start -->
            <header class="app-header">
                <nav class="navbar navbar-expand-lg navbar-light">
                    <ul class="navbar-nav">
                        <li class="nav-item d-block d-xl-none">
                            <a class="nav-link sidebartoggler nav-icon-hover" id="headerCollapse"
                                href="#" role="button">
                                <i class="ti ti-menu-2"></i>
                            </a>
                        </li>
                    </ul>
                    <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
                        <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end">
                            <div class="user-name">
                                <li class="nav-item mr-10" id="greeting"></li>
                            </div>
                            <li class="nav-item">
                                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up"
                                    aria-labelledby="drop1">
                                    <div class="message-body">
                                        <a href="#" role="button"
                                            class="d-flex align-items-center gap-2 dropdown-item">
                                            <i class="ti ti-user fs-6"></i>
                                            <p class="mb-0 fs-3">My Profile</p>
                                        </a>
                                        <a href="./authentication-login.html"
                                            class="btn btn-outline-primary mx-3 mt-2 d-block">Logout</a>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link nav-icon-hover" href="#" role="button" id="drop2"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <img id="headerAvatar" src="{{ (auth()->check() && !empty(auth()->user()->user_profile)) ? asset('storage/' . auth()->user()->user_profile) : asset('images/profile/user-1.jpg') }}" alt="User avatar"
                                        width="35" height="35" class="rounded-circle">
                                </a>
                                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up outline-shadow"
                                    aria-labelledby="drop2">
                                    <div class="message-body">
                                        <a href="{{ route('user.profile') }}"
                                            class="d-flex align-items-center gap-2 dropdown-item">
                                            <i class="ti ti-user fs-6"></i>
                                            <p class="mb-0 fs-3">My Profile</p>
                                        </a>
                                        <a href="{{ route('logout') }}"
                                            class="btn btn-outline-primary mx-3 mt-2 d-block">Logout</a>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
            </header>
            <!--  Header End -->
            @endunless
            <div class="container-fluid flex-grow-1">
                @yield('content')
            </div>
            @unless($isEmbed)
            <div class="footer-wrapper mt-auto">
                <footer class="footer bg-dark text-light text-center py-3">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-4 col-md-4 col-sm-4">
                                <!-- First Column - Left Blank -->
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-4 mb-3 mb-md-0 align-items-center">
                                <!-- Second Column - Text -->
                                <p id="footer-year" class="mb-0">&copy; <span id="current-year">{{ date('Y') }}</span> Nebula Institute of Technology. All rights reserved.</p>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-4">
                                <!-- Third Column - Left Blank -->
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
            @endunless
        </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        document.addEventListener("DOMContentLoaded", function() {
            var greetingEl = document.getElementById("greeting");
            if (!greetingEl) {
                return;
            }

            // Get the current time
            var currentTime = new Date();
            var currentHour = currentTime.getHours();
            var greeting;

            // Define the greeting based on the current time
            if (currentHour >= 5 && currentHour < 12) {
                greeting = 'Good morning';
            } else if (currentHour >= 12 && currentHour < 18) {
                greeting = 'Good afternoon';
            } else {
                greeting = 'Good evening ';
            }

            // Get the user's name
            var userName = "{{ auth()->check() ? auth()->user()->name : '' }}";

            // Display the greeting and user's name
            if (userName) {
                greetingEl.innerHTML = greeting + ", <b>" + userName + "</b>";
            }
        });
    </script>

    <script nonce="{{ $cspNonce }}">
        // Global AJAX setup for CSRF token
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>

    @yield('scripts')
    @stack('scripts')

    <script nonce="{{ $cspNonce }}">
        document.addEventListener("DOMContentLoaded", function() {
            const courseManagementLink = document.getElementById('course-management-link');
            if (courseManagementLink && window.location.pathname.startsWith('/course-management')) {
                courseManagementLink.classList.add('active');
            }
        });
    </script>
    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>
    @if($isEmbed)
    <script nonce="{{ $cspNonce }}">
        (function() {
            let lastHeight = 0;
            let timer = null;

            function contentRoot() {
                return document.getElementById('pageContent')
                    || document.querySelector('.body-wrapper > .container-fluid')
                    || document.body;
            }

            function measureHeight() {
                const content = contentRoot();
                if (!content) {
                    return 0;
                }
                const container = document.querySelector('.body-wrapper > .container-fluid');
                let height = Math.max(content.scrollHeight, content.offsetHeight);
                if (container && content !== container) {
                    const styles = window.getComputedStyle(container);
                    height += (parseFloat(styles.paddingTop) || 0) + (parseFloat(styles.paddingBottom) || 0);
                }
                return Math.ceil(height);
            }

            function postEmbedHeight(force) {
                const height = measureHeight();
                if (!height) {
                    return;
                }
                if (!force && Math.abs(height - lastHeight) < 2) {
                    return;
                }
                lastHeight = height;
                window.parent.postMessage({ type: 'nebula-embed-height', height: height }, window.location.origin);
            }

            function schedulePost() {
                if (timer) {
                    clearTimeout(timer);
                }
                timer = setTimeout(function() {
                    postEmbedHeight(false);
                }, 80);
            }

            window.addEventListener('load', function() { postEmbedHeight(true); });
            window.addEventListener('resize', schedulePost);
            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', schedulePost);
            }
            window.addEventListener('message', function(e) {
                if (e.origin !== window.location.origin || !e.data || e.data.type !== 'nebula-embed-remeasure') {
                    return;
                }
                postEmbedHeight(true);
            });

            function startObservers() {
                const content = contentRoot();
                if (!content) {
                    return;
                }
                if (typeof ResizeObserver !== 'undefined') {
                    new ResizeObserver(schedulePost).observe(content);
                }
                new MutationObserver(schedulePost).observe(content, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['class', 'style']
                });
            }

            if (document.body) {
                startObservers();
            } else {
                document.addEventListener('DOMContentLoaded', startObservers);
            }

            [150, 400, 900, 1800, 3500].forEach(function(ms) {
                setTimeout(function() { postEmbedHeight(true); }, ms);
            });
        })();
    </script>
    @endif
</body>
</html>

