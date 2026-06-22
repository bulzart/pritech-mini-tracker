@extends('layouts.app')

@section('title', $project->name)

@section('content')
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('projects.index') }}">Projects</a>
        <span aria-hidden="true">/</span> {{ $project->name }}
    </nav>

    <div class="page-header">
        <h1>{{ $project->name }}</h1>
        <div class="actions">
            <a class="button" href="{{ route('projects.edit', $project) }}">Edit</a>
            <form
                method="POST"
                action="{{ route('projects.destroy', $project) }}"
                class="inline-form"
                data-confirm="Delete &quot;{{ $project->name }}&quot; and all of its issues? This cannot be undone."
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="button button--danger">Delete</button>
            </form>
        </div>
    </div>

    <div class="card">
        <dl class="detail-list">
            <dt>Description</dt>
            <dd>
                @if (filled($project->description))
                    {{ $project->description }}
                @else
                    <span class="muted">No description</span>
                @endif
            </dd>

            <dt>Start date</dt>
            <dd>{{ $project->start_date?->format('M j, Y') ?? '—' }}</dd>

            <dt>Deadline</dt>
            <dd>{{ $project->deadline?->format('M j, Y') ?? '—' }}</dd>

            <dt>Issues</dt>
            <dd>{{ $project->issues->count() }}</dd>
        </dl>
    </div>

    <div class="page-header">
        <h2>Issues ({{ $project->issues->count() }})</h2>
        <a class="button button--primary" href="{{ route('issues.create', ['project_id' => $project->id]) }}">
            New issue
        </a>
    </div>

    @if ($project->issues->isEmpty())
        <div class="card empty-state">
            <p>This project has no issues yet.</p>
            <a class="button button--primary" href="{{ route('issues.create', ['project_id' => $project->id]) }}">
                Create the first issue
            </a>
        </div>
    @else
        <div class="card">
            <ul class="issue-list">
                @foreach ($project->issues as $issue)
                    <li class="issue-list__item">
                        <a class="issue-list__title" href="{{ route('issues.show', $issue) }}">{{ $issue->title }}</a>
                        <span class="issue-list__meta">
                            @include('issues._status_badge', ['status' => $issue->status])
                            @include('issues._priority_badge', ['priority' => $issue->priority])
                            @if ($issue->due_date)
                                <span class="muted">due {{ $issue->due_date->format('M j, Y') }}</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
