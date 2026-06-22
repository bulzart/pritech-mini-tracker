{{--
    Issue filter bar. A GET form so filters live in the URL (shareable,
    bookmarkable) and pagination can preserve them. Expects:
      $projects (unused here but available), $tags (Collection<Tag>),
      $filters (['status' => ?, 'priority' => ?, 'tag' => ?]).
    Each scope ignores empty values, so "All …" (value="") shows everything.
--}}
<form method="GET" action="{{ route('issues.index') }}" class="filters" role="search" aria-label="Filter issues">
    <div class="filters__group">
        <label class="filters__label" for="filter-status">Status</label>
        <select id="filter-status" name="status" class="form__control">
            <option value="">All statuses</option>
            @foreach (\App\Models\Issue::STATUSES as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                    {{ str_replace('_', ' ', $status) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="filters__group">
        <label class="filters__label" for="filter-priority">Priority</label>
        <select id="filter-priority" name="priority" class="form__control">
            <option value="">All priorities</option>
            @foreach (\App\Models\Issue::PRIORITIES as $priority)
                <option value="{{ $priority }}" @selected(($filters['priority'] ?? '') === $priority)>
                    {{ $priority }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="filters__group">
        <label class="filters__label" for="filter-tag">Tag</label>
        <select id="filter-tag" name="tag" class="form__control">
            <option value="">All tags</option>
            @foreach ($tags as $tag)
                <option value="{{ $tag->name }}" @selected(($filters['tag'] ?? '') === $tag->name)>
                    {{ $tag->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="filters__actions">
        <button type="submit" class="button button--primary">Filter</button>
        @if (filled($filters['status'] ?? '') || filled($filters['priority'] ?? '') || filled($filters['tag'] ?? ''))
            <a href="{{ route('issues.index') }}" class="button button--ghost">Clear</a>
        @endif
    </div>
</form>
