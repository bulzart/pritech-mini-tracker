<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Session-based login/logout.
 *
 * Hand-rolled rather than Laravel Breeze: this project ships its UI as static
 * same-origin assets with no Vite/Tailwind build step, and Breeze would pull in
 * that build pipeline (see CHECKPOINT.md). The auth rules — throttling,
 * enumeration-resistant errors — live in LoginRequest; this controller is a
 * thin transport layer.
 */
final class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login form. An already-authenticated visitor has no reason to
     * see it, so send them on to the app.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('projects.index');
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Session-fixation defence: issue a fresh session id now that the
        // privilege level has changed (OWASP Session Management;
        // rules/enterprise/security.md — "Rotate session ID on login").
        $request->session()->regenerate();

        // intended() honours a pre-login target captured by the auth middleware;
        // /projects is the default landing page.
        return redirect()->intended(route('projects.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // Invalidate the session and rotate the CSRF token so the pre-logout
        // session cannot be replayed (OWASP Session Management).
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
