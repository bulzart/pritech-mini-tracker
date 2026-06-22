@extends('layouts.app')

@section('title', 'New issue')

@section('content')
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('issues.index') }}">Issues</a>
        <span aria-hidden="true">/</span> New issue
    </nav>

    <h1>New issue</h1>

    @include('issues._form', [
        'issue' => $issue,
        'action' => route('issues.store'),
        'method' => 'POST',
        'submitLabel' => 'Create issue',
        'projects' => $projects,
        'selectedProjectId' => $selectedProjectId,
    ])
@endsection
