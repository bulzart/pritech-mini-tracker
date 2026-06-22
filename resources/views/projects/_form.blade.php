{{--
    Shared create/edit form.
    Expects: $project (Project, possibly unsaved), $action (URL),
             $method (HTTP verb), $submitLabel (string).
--}}
<form method="POST" action="{{ $action }}" class="form" novalidate>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="form__group">
        <label class="form__label" for="name">
            Name <span class="form__required" aria-hidden="true">*</span>
        </label>
        <input
            type="text"
            id="name"
            name="name"
            class="form__control"
            value="{{ old('name', $project->name) }}"
            maxlength="255"
            required
            autofocus
            @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
        >
        @include('partials.field-error', ['field' => 'name'])
    </div>

    <div class="form__group">
        <label class="form__label" for="description">Description</label>
        <textarea
            id="description"
            name="description"
            class="form__control"
            @error('description') aria-invalid="true" aria-describedby="description-error" @enderror
        >{{ old('description', $project->description) }}</textarea>
        @include('partials.field-error', ['field' => 'description'])
    </div>

    <div class="form__row">
        <div class="form__group">
            <label class="form__label" for="start_date">Start date</label>
            <input
                type="date"
                id="start_date"
                name="start_date"
                class="form__control"
                value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}"
                @error('start_date') aria-invalid="true" aria-describedby="start_date-error" @enderror
            >
            @include('partials.field-error', ['field' => 'start_date'])
        </div>

        <div class="form__group">
            <label class="form__label" for="deadline">Deadline</label>
            <input
                type="date"
                id="deadline"
                name="deadline"
                class="form__control"
                value="{{ old('deadline', $project->deadline?->format('Y-m-d')) }}"
                aria-describedby="deadline-hint @error('deadline') deadline-error @enderror"
                @error('deadline') aria-invalid="true" @enderror
            >
            <p class="form__hint" id="deadline-hint">Must be on or after the start date.</p>
            @include('partials.field-error', ['field' => 'deadline'])
        </div>
    </div>

    <div class="form__actions">
        <button type="submit" class="button button--primary">{{ $submitLabel }}</button>
        <a href="{{ route('projects.index') }}" class="button button--ghost">Cancel</a>
    </div>
</form>
