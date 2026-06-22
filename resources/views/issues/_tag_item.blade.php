{{--
    A single tag row in the attach/detach manager.
    Expects: $issue, $tag, $action ('attach' for the available column, 'detach'
    for the attached column). The data-* attributes let app.js move the item
    between columns after an AJAX call without a full reload. attach and detach
    share the same URI (they differ only by HTTP method), so one data-tag-url
    serves both.
--}}
<li class="tag-manager__item"
    data-tag-id="{{ $tag->id }}"
    data-tag-name="{{ $tag->name }}"
    data-tag-color="{{ $tag->color }}"
    data-tag-url="{{ route('issues.tags.attach', [$issue, $tag]) }}">
    @include('partials.tag-chip', ['tag' => $tag])
    <button type="button" class="button button--small tag-manager__action" data-tag-action="{{ $action }}">
        {{ $action === 'detach' ? 'Detach' : 'Attach' }}
    </button>
</li>
