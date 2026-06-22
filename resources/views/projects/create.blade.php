@extends('layouts.app')

@section('title', 'New project')

@section('content')
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('projects.index') }}">Projects</a>
        <span aria-hidden="true">/</span> New
    </nav>

    <h1>New project</h1>

    @include('projects._form', [
        'project' => $project,
        'action' => route('projects.store'),
        'method' => 'POST',
        'submitLabel' => 'Create project',
    ])
@endsection
