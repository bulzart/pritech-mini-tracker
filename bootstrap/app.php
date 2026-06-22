<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Apply baseline security headers to every web response.
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Default rendering: JSON when the request expects it (the AJAX tag and
        // comment endpoints send Accept: application/json), HTML redirects for
        // normal form posts. Validation failures therefore return 422 JSON to
        // fetch() and a redirect-with-errors to the server-rendered forms.
    })->create();
