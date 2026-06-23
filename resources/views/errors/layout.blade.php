{{--
    Shared error page. Deliberately standalone (it does not extend the app
    layout, so it cannot fail again on the same error) and uses only the
    external stylesheet — no inline styles — so it satisfies the strict
    Content-Security-Policy (style-src 'self') with no console violations.
    Messages are static so an error never leaks internal details (OWASP A02).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Error') · Mini Issue Tracker</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <main class="container">
        <div class="card empty-state">
            <p class="error-code">@yield('code')</p>
            <h1>@yield('heading', 'Something went wrong')</h1>
            <p>@yield('message', 'An unexpected error occurred. Please try again.')</p>
            <a class="button button--primary" href="{{ url('/') }}">Back to the app</a>
        </div>
    </main>
</body>
</html>
