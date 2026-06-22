{{--
    Read-only tag chip. Usage: @include('partials.tag-chip', ['tag' => $tag])

    The colour dot's background is carried in data-tag-color and applied via the
    CSSOM in app.js. Inline style attributes are intentionally avoided so the
    strict Content-Security-Policy (style-src 'self', no 'unsafe-inline') holds
    with no console errors; CSSOM assignment is not subject to style-src. The dot
    simply does not appear when JavaScript is off (progressive enhancement).
--}}
<span class="tag-chip">
    @if (filled($tag->color))
        <span class="tag-chip__dot" data-tag-color="{{ $tag->color }}" aria-hidden="true"></span>
    @endif
    {{ $tag->name }}
</span>
