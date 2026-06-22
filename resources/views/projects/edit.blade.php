@extends('layouts.app')

@section('title', 'Edit ' . $project->name)

@section('content')
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('projects.index') }}">Projects</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a>
        <span aria-hidden="true">/</span> Edit
    </nav>

    <h1>Edit project</h1>

    @include('projects._form', [
        'project' => $project,
        'action' => route('projects.update', $project),
        'method' => 'PATCH',
        'submitLabel' => 'Save changes',
    ])
@endsection
