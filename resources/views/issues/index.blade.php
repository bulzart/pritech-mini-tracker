@extends('layouts.app')

@section('title', 'Issues')

@section('content')
    <div class="page-header">
        <h1>Issues</h1>
        <a href="{{ route('issues.create') }}" class="button button--primary">New issue</a>
    </div>

    @include('issues._filters', ['projects' => $projects, 'tags' => $tags, 'filters' => $filters])

    @if ($issues->isEmpty())
        <div class="card empty-state">
            <h2>No issues found</h2>
            @if (filled($filters['status']) || filled($filters['priority']) || filled($filters['tag']))
                <p>No issues match the selected filters.</p>
                <a href="{{ route('issues.index') }}" class="button">Clear filters</a>
            @else
                <p>Create your first issue to start tracking work.</p>
                <a href="{{ route('issues.create') }}" class="button button--primary">New issue</a>
            @endif
        </div>
    @else
        <div class="table-wrap">
            <table>
                <caption class="sr-only">List of issues with project, status, priority, due date, tags, and actions</caption>
                <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Project</th>
                        <th scope="col">Status</th>
                        <th scope="col">Priority</th>
                        <th scope="col">Due date</th>
                        <th scope="col">Tags</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($issues as $issue)
                        <tr>
                            <th scope="row">
                                <a href="{{ route('issues.show', $issue) }}">{{ $issue->title }}</a>
                            </th>
                            <td>
                                <a href="{{ route('projects.show', $issue->project) }}">{{ $issue->project->name }}</a>
                            </td>
                            <td>@include('issues._status_badge', ['status' => $issue->status])</td>
                            <td>@include('issues._priority_badge', ['priority' => $issue->priority])</td>
                            <td class="numeric">{{ $issue->due_date?->format('M j, Y') ?? '—' }}</td>
                            <td>
                                @forelse ($issue->tags as $tag)
                                    @include('partials.tag-chip', ['tag' => $tag])
                                @empty
                                    <span class="muted">—</span>
                                @endforelse
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="button button--small" href="{{ route('issues.show', $issue) }}">View</a>
                                    <a class="button button--small" href="{{ route('issues.edit', $issue) }}">Edit</a>
                                    <form
                                        method="POST"
                                        action="{{ route('issues.destroy', $issue) }}"
                                        class="inline-form"
                                        data-confirm="Delete &quot;{{ $issue->title }}&quot;? This also removes its comments. This cannot be undone."
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="button button--danger button--small">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination', ['paginator' => $issues])
    @endif
@endsection
