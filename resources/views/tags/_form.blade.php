{{--
    Shared tag form (currently used by create).
    Expects: $tag (Tag, possibly unsaved), $action, $method, $submitLabel.
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
            value="{{ old('name', $tag->name) }}"
            maxlength="255"
            required
            autofocus
            @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
        >
        @include('partials.field-error', ['field' => 'name'])
    </div>

    <div class="form__group">
        <label class="form__label" for="color">Colour</label>
        <input
            type="text"
            id="color"
            name="color"
            class="form__control"
            value="{{ old('color', $tag->color) }}"
            maxlength="50"
            inputmode="text"
            aria-describedby="color-hint @error('color') color-error @enderror"
            @error('color') aria-invalid="true" @enderror
        >
        <p class="form__hint" id="color-hint">Optional. A CSS colour such as <code>#2563eb</code>.</p>
        @include('partials.field-error', ['field' => 'color'])
    </div>

    <div class="form__actions">
        <button type="submit" class="button button--primary">{{ $submitLabel }}</button>
        <a href="{{ route('tags.index') }}" class="button button--ghost">Cancel</a>
    </div>
</form>
