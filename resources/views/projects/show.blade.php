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

    <h2>Issues ({{ $project->issues->count() }})</h2>

    @if ($project->issues->isEmpty())
        <div class="card empty-state">
            <p>This project has no issues yet.</p>
        </div>
    @else
        <div class="card">
            <ul class="issue-list">
                @foreach ($project->issues as $issue)
                    <li class="issue-list__item">
                        {{-- Plain text: the issue show route does not exist in this checkpoint. --}}
                        <span class="issue-list__title">{{ $issue->title }}</span>
                        <span class="issue-list__meta">
                            <span class="badge badge--status-{{ $issue->status }}">
                                {{ str_replace('_', ' ', $issue->status) }}
                            </span>
                            <span class="badge badge--priority-{{ $issue->priority }}">
                                {{ $issue->priority }}
                            </span>
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
