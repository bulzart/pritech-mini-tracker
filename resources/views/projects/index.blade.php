@extends('layouts.app')

@section('title', 'Projects')

@section('content')
    <div class="page-header">
        <h1>Projects</h1>
        @can('create', App\Models\Project::class)
            <a href="{{ route('projects.create') }}" class="button button--primary">New project</a>
        @endcan
    </div>

    @if ($projects->isEmpty())
        <div class="card empty-state">
            <h2>No projects yet</h2>
            <p>Create your first project to start tracking issues.</p>
            @can('create', App\Models\Project::class)
                <a href="{{ route('projects.create') }}" class="button button--primary">New project</a>
            @endcan
        </div>
    @else
        <div class="table-wrap">
            <table>
                <caption class="sr-only">List of projects with issue counts and actions</caption>
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Owner</th>
                        <th scope="col">Description</th>
                        <th scope="col">Start date</th>
                        <th scope="col">Deadline</th>
                        <th scope="col">Issues</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($projects as $project)
                        <tr>
                            <th scope="row">
                                <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a>
                            </th>
                            <td>{{ $project->owner->name }}</td>
                            <td>
                                @if (filled($project->description))
                                    {{ str($project->description)->limit(80) }}
                                @else
                                    <span class="muted">No description</span>
                                @endif
                            </td>
                            <td class="numeric">{{ $project->start_date?->format('M j, Y') ?? '—' }}</td>
                            <td class="numeric">{{ $project->deadline?->format('M j, Y') ?? '—' }}</td>
                            <td class="numeric">{{ $project->issues_count }}</td>
                            <td>
                                <div class="actions">
                                    <a class="button button--small" href="{{ route('projects.show', $project) }}">View</a>
                                    @can('update', $project)
                                        <a class="button button--small" href="{{ route('projects.edit', $project) }}">Edit</a>
                                    @endcan
                                    @can('delete', $project)
                                        <form
                                            method="POST"
                                            action="{{ route('projects.destroy', $project) }}"
                                            class="inline-form"
                                            data-confirm="Delete &quot;{{ $project->name }}&quot; and all of its issues? This cannot be undone."
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="button button--danger button--small">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination', ['paginator' => $projects])
    @endif
@endsection
