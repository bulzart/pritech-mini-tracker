@extends('layouts.app')

@section('title', 'Issues')

@section('content')
    <div class="page-header">
        <h1>Issues</h1>
        <a href="{{ route('issues.create') }}" class="button button--primary">New issue</a>
    </div>

    @include('issues._filters', ['projects' => $projects, 'tags' => $tags, 'filters' => $filters])

    {{-- Shown while an AJAX search/filter request is in flight. --}}
    <p class="issues-status" data-issues-loading role="status" hidden>Searching…</p>
    <p class="issues-status issues-status--error" data-issues-error role="alert" hidden>
        Could not update the results. Please try again.
    </p>

    {{--
        Results swap in place on search/filter/pagination via fetch()
        (public/js/issues-index.js); aria-live announces the update. Without
        JavaScript the GET filter form reloads the page normally (progressive
        enhancement).
    --}}
    <div data-issues-results aria-live="polite">
        @include('issues._results', ['issues' => $issues, 'filters' => $filters])
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/issues-index.js') }}" defer></script>
@endpush
