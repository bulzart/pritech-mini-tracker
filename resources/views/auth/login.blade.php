@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
    <div class="auth">
        <h1>Sign in</h1>
        <p class="muted">Sign in to manage your projects and issues.</p>

        @if (config('app.demo_mode'))
            <p class="auth-note" role="note">
                Demo credentials are prefilled in demo mode.
            </p>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="form" novalidate>
            @csrf

            <div class="form__group">
                <label class="form__label" for="email">
                    Email <span class="form__required" aria-hidden="true">*</span>
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form__control"
                    value="{{ old('email', config('app.demo_mode') ? 'owner@example.com' : '') }}"
                    required
                    autofocus
                    autocomplete="username"
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                >
                @include('partials.field-error', ['field' => 'email'])
            </div>

            <div class="form__group">
                <label class="form__label" for="password">
                    Password <span class="form__required" aria-hidden="true">*</span>
                </label>
                {{--
                    The password value is only ever pre-populated in demo mode, so
                    a reviewer can sign in with one click. It is never prefilled
                    (and Laravel never flashes it back) outside demo mode.
                --}}
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form__control"
                    value="{{ config('app.demo_mode') ? 'password' : '' }}"
                    required
                    autocomplete="current-password"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                >
                @include('partials.field-error', ['field' => 'password'])
            </div>

            <div class="form__actions">
                <button type="submit" class="button button--primary">Sign in</button>
            </div>
        </form>
    </div>
@endsection
