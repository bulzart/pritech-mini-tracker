{{--
    Shared issue create/edit form.
    Expects: $issue (Issue, possibly unsaved), $action (URL), $method (verb),
             $submitLabel (string), $projects (Collection<Project>),
             $selectedProjectId (?int — preselected project, e.g. from ?project_id).
--}}
<form method="POST" action="{{ $action }}" class="form" novalidate>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="form__group">
        <label class="form__label" for="project_id">
            Project <span class="form__required" aria-hidden="true">*</span>
        </label>
        <select
            id="project_id"
            name="project_id"
            class="form__control"
            required
            @error('project_id') aria-invalid="true" aria-describedby="project_id-error" @enderror
        >
            <option value="">Select a project…</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected((int) old('project_id', $selectedProjectId) === $project->id)>
                    {{ $project->name }}
                </option>
            @endforeach
        </select>
        @include('partials.field-error', ['field' => 'project_id'])
    </div>

    <div class="form__group">
        <label class="form__label" for="title">
            Title <span class="form__required" aria-hidden="true">*</span>
        </label>
        <input
            type="text"
            id="title"
            name="title"
            class="form__control"
            value="{{ old('title', $issue->title) }}"
            maxlength="255"
            required
            autofocus
            @error('title') aria-invalid="true" aria-describedby="title-error" @enderror
        >
        @include('partials.field-error', ['field' => 'title'])
    </div>

    <div class="form__group">
        <label class="form__label" for="description">Description</label>
        <textarea
            id="description"
            name="description"
            class="form__control"
            @error('description') aria-invalid="true" aria-describedby="description-error" @enderror
        >{{ old('description', $issue->description) }}</textarea>
        @include('partials.field-error', ['field' => 'description'])
    </div>

    <div class="form__row">
        <div class="form__group">
            <label class="form__label" for="status">
                Status <span class="form__required" aria-hidden="true">*</span>
            </label>
            <select
                id="status"
                name="status"
                class="form__control"
                required
                @error('status') aria-invalid="true" aria-describedby="status-error" @enderror
            >
                @foreach (\App\Models\Issue::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status', $issue->status) === $status)>
                        {{ str_replace('_', ' ', $status) }}
                    </option>
                @endforeach
            </select>
            @include('partials.field-error', ['field' => 'status'])
        </div>

        <div class="form__group">
            <label class="form__label" for="priority">
                Priority <span class="form__required" aria-hidden="true">*</span>
            </label>
            <select
                id="priority"
                name="priority"
                class="form__control"
                required
                @error('priority') aria-invalid="true" aria-describedby="priority-error" @enderror
            >
                @foreach (\App\Models\Issue::PRIORITIES as $priority)
                    <option value="{{ $priority }}" @selected(old('priority', $issue->priority) === $priority)>
                        {{ $priority }}
                    </option>
                @endforeach
            </select>
            @include('partials.field-error', ['field' => 'priority'])
        </div>

        <div class="form__group">
            <label class="form__label" for="due_date">Due date</label>
            <input
                type="date"
                id="due_date"
                name="due_date"
                class="form__control"
                value="{{ old('due_date', $issue->due_date?->format('Y-m-d')) }}"
                @error('due_date') aria-invalid="true" aria-describedby="due_date-error" @enderror
            >
            @include('partials.field-error', ['field' => 'due_date'])
        </div>
    </div>

    <div class="form__actions">
        <button type="submit" class="button button--primary">{{ $submitLabel }}</button>
        <a href="{{ route('issues.index') }}" class="button button--ghost">Cancel</a>
    </div>
</form>
