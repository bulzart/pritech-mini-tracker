{{--
    Renders the validation error for a single field, if any.
    Usage: @include('partials.field-error', ['field' => 'name'])
    The id ({field}-error) is referenced by the input's aria-describedby.
--}}
@error($field)
    <p class="field-error" id="{{ $field }}-error" role="alert">{{ $message }}</p>
@enderror
