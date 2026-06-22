{{-- Session flash messages. Success uses a polite status region; errors assert. --}}
@if (session('success'))
    <div class="flash flash--success" role="status">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="flash flash--error" role="alert">{{ session('error') }}</div>
@endif
