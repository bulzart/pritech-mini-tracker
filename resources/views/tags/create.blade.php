@extends('layouts.app')

@section('title', 'New tag')

@section('content')
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('tags.index') }}">Tags</a>
        <span aria-hidden="true">/</span> New tag
    </nav>

    <h1>New tag</h1>

    @include('tags._form', [
        'tag' => $tag,
        'action' => route('tags.store'),
        'method' => 'POST',
        'submitLabel' => 'Create tag',
    ])
@endsection
