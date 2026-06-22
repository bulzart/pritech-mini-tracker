<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- CSRF token exposed for future AJAX requests (tag attach/detach, comments). --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Projects') · Mini Issue Tracker</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <header class="site-header">
        <nav class="site-nav" aria-label="Primary">
            <a class="site-nav__brand" href="{{ route('projects.index') }}">Mini Issue Tracker</a>
            <ul class="site-nav__links">
                <li>
                    <a href="{{ route('projects.index') }}"
                       @if (request()->routeIs('projects.*')) aria-current="page" @endif>
                        Projects
                    </a>
                </li>
                <li>
                    <a href="{{ route('issues.index') }}"
                       @if (request()->routeIs('issues.*')) aria-current="page" @endif>
                        Issues
                    </a>
                </li>
                <li>
                    <a href="{{ route('tags.index') }}"
                       @if (request()->routeIs('tags.*')) aria-current="page" @endif>
                        Tags
                    </a>
                </li>
            </ul>
        </nav>
    </header>

    <main id="main-content" class="container">
        @include('partials.flash')

        @yield('content')
    </main>

    <script src="{{ asset('js/app.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
