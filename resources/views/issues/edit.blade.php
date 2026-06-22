@extends('layouts.app')

@section('title', 'Edit issue')

@section('content')
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('issues.index') }}">Issues</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('issues.show', $issue) }}">{{ $issue->title }}</a>
        <span aria-hidden="true">/</span> Edit
    </nav>

    <h1>Edit issue</h1>

    @include('issues._form', [
        'issue' => $issue,
        'action' => route('issues.update', $issue),
        'method' => 'PATCH',
        'submitLabel' => 'Save changes',
        'projects' => $projects,
        'selectedProjectId' => $selectedProjectId,
    ])
@endsection
