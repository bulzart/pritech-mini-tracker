<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validates and authenticates a login attempt.
 *
 * Brute-force throttling (per email + IP) and the enumeration-resistant error
 * message live here so the controller stays a thin transport layer.
 */
final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * On failure a single generic message is attached to the `email` field —
     * identical for a wrong password and for an unknown account — so the
     * endpoint never reveals which emails are registered (OWASP "Account
     * Enumeration Prevention"; NIST SP 800-63B-4). Auth::attempt runs a
     * constant-time hash check, which also equalises response timing.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Progressive brute-force protection: after 5 failed attempts for the same
     * email + IP the pair is locked out until the decay window passes (OWASP
     * "Blocking Brute Force Attacks"). The counter is cleared on success.
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
        ]);
    }

    /**
     * Rate-limit key: the lower-cased email paired with the client IP, so the
     * limit is per credential per source rather than a blunt global cap.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->string('email')).'|'.$this->ip());
    }
}
