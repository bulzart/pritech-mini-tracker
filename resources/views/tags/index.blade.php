@extends('layouts.app')

@section('title', 'Tags')

@section('content')
    <div class="page-header">
        <h1>Tags</h1>
        <a href="{{ route('tags.create') }}" class="button button--primary">New tag</a>
    </div>

    @if ($tags->isEmpty())
        <div class="card empty-state">
            <h2>No tags yet</h2>
            <p>Create a tag to start labelling issues.</p>
            <a href="{{ route('tags.create') }}" class="button button--primary">New tag</a>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <caption class="sr-only">List of tags with colour and issue counts</caption>
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Colour</th>
                        <th scope="col">Issues</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tags as $tag)
                        <tr>
                            <th scope="row">@include('partials.tag-chip', ['tag' => $tag])</th>
                            <td>
                                @if (filled($tag->color))
                                    <span class="color-swatch" data-tag-color="{{ $tag->color }}" aria-hidden="true"></span>
                                    <code>{{ $tag->color }}</code>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td class="numeric">{{ $tag->issues_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('partials.pagination', ['paginator' => $tags])
    @endif
@endsection
