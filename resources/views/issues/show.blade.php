@extends('layouts.app')

@section('title', $issue->title)

@section('content')
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('issues.index') }}">Issues</a>
        <span aria-hidden="true">/</span> {{ $issue->title }}
    </nav>

    <div class="page-header">
        <h1>{{ $issue->title }}</h1>
        <div class="actions">
            <a class="button" href="{{ route('issues.edit', $issue) }}">Edit</a>
            <form
                method="POST"
                action="{{ route('issues.destroy', $issue) }}"
                class="inline-form"
                data-confirm="Delete &quot;{{ $issue->title }}&quot;? This also removes its comments. This cannot be undone."
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="button button--danger">Delete</button>
            </form>
        </div>
    </div>

    <div class="card">
        <dl class="detail-list">
            <dt>Project</dt>
            <dd><a href="{{ route('projects.show', $issue->project) }}">{{ $issue->project->name }}</a></dd>

            <dt>Status</dt>
            <dd>@include('issues._status_badge', ['status' => $issue->status])</dd>

            <dt>Priority</dt>
            <dd>@include('issues._priority_badge', ['priority' => $issue->priority])</dd>

            <dt>Due date</dt>
            <dd>{{ $issue->due_date?->format('M j, Y') ?? '—' }}</dd>

            <dt>Description</dt>
            <dd>
                @if (filled($issue->description))
                    <p class="prose">{{ $issue->description }}</p>
                @else
                    <span class="muted">No description</span>
                @endif
            </dd>
        </dl>
    </div>

    {{-- Tags: attach/detach via AJAX (app.js), no full-page reload. --}}
    <section class="card" aria-labelledby="tags-heading">
        <h2 id="tags-heading">Tags</h2>

        <p class="field-error" data-tag-error role="alert" hidden></p>

        <div class="tag-manager" data-tag-manager>
            <div class="tag-manager__col">
                <h3 class="tag-manager__title">Attached</h3>
                <ul class="tag-manager__list" data-attached-list aria-live="polite">
                    @forelse ($issue->tags as $tag)
                        @include('issues._tag_item', ['issue' => $issue, 'tag' => $tag, 'action' => 'detach'])
                    @empty
                        <li class="muted" data-empty>No tags attached.</li>
                    @endforelse
                </ul>
            </div>

            <div class="tag-manager__col">
                <h3 class="tag-manager__title">Available</h3>
                <ul class="tag-manager__list" data-available-list aria-live="polite">
                    @forelse ($availableTags as $tag)
                        @include('issues._tag_item', ['issue' => $issue, 'tag' => $tag, 'action' => 'attach'])
                    @empty
                        <li class="muted" data-empty>
                            All tags are attached. <a href="{{ route('tags.create') }}">Create a tag</a>.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </section>

    {{-- Comments: loaded and submitted via AJAX (app.js). --}}
    <section class="card" aria-labelledby="comments-heading"
             data-comments-section
             data-comments-url="{{ route('issues.comments.index', $issue) }}">
        <h2 id="comments-heading">Comments</h2>

        <form class="form form--inline-card" data-comment-form novalidate>
            <p class="flash flash--success" data-comment-success role="status" hidden></p>

            <div class="form__group">
                <label class="form__label" for="author_name">
                    Your name <span class="form__required" aria-hidden="true">*</span>
                </label>
                <input type="text" id="author_name" name="author_name" class="form__control" maxlength="255" required>
                <p class="field-error" data-error-for="author_name" role="alert" hidden></p>
            </div>

            <div class="form__group">
                <label class="form__label" for="body">
                    Comment <span class="form__required" aria-hidden="true">*</span>
                </label>
                <textarea id="body" name="body" class="form__control" maxlength="5000" required></textarea>
                <p class="field-error" data-error-for="body" role="alert" hidden></p>
            </div>

            <p class="field-error" data-comment-form-error role="alert" hidden></p>

            <div class="form__actions">
                <button type="submit" class="button button--primary" data-comment-submit>Add comment</button>
            </div>
        </form>

        <div class="comments" data-comments aria-busy="true">
            <p class="comments__loading muted" data-comments-loading>Loading comments…</p>
            <ul class="comments__list" data-comments-list></ul>
            <p class="comments__empty muted" data-comments-empty hidden>No comments yet. Be the first to comment.</p>
            <p class="field-error" data-comments-error role="alert" hidden></p>
        </div>

        <nav class="pagination" data-comments-pagination aria-label="Comments pagination" hidden>
            <button type="button" class="pagination__link" data-comments-prev>Previous</button>
            <span class="pagination__status" data-comments-status></span>
            <button type="button" class="pagination__link" data-comments-next>Next</button>
        </nav>

        <noscript>
            <p class="muted">Comments require JavaScript to load on this page.</p>
        </noscript>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/issue-show.js') }}" defer></script>
@endpush
