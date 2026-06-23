{{--
    Issue search + filter bar. A GET form so the search term and filters live in
    the URL (shareable, bookmarkable) and pagination can preserve them. With
    JavaScript the search input debounces and swaps results in place
    (public/js/issues-index.js); without it, the Filter button reloads the page.
    Expects: $projects (unused), $tags (Collection<Tag>),
      $filters (['search' => ?, 'status' => ?, 'priority' => ?, 'tag' => ?]).
    Each scope ignores empty values, so "All …" (value="") shows everything.
--}}
<form method="GET" action="{{ route('issues.index') }}" class="filters" role="search" aria-label="Search and filter issues" data-issues-filters>
    <div class="filters__group filters__group--search">
        <label class="filters__label" for="filter-search">Search</label>
        <div class="filters__search">
            <input
                type="search"
                id="filter-search"
                name="search"
                class="form__control"
                value="{{ $filters['search'] ?? '' }}"
                placeholder="Search issues..."
                autocomplete="off"
                data-issues-search
            >
            {{-- Hidden by default; revealed by JS when there is a term to clear. --}}
            <button type="button" class="button button--ghost button--small" data-issues-clear hidden>
                Clear
            </button>
        </div>
    </div>

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
        @if (filled($filters['search'] ?? '') || filled($filters['status'] ?? '') || filled($filters['priority'] ?? '') || filled($filters['tag'] ?? ''))
            <a href="{{ route('issues.index') }}" class="button button--ghost">Clear all</a>
        @endif
    </div>
</form>
