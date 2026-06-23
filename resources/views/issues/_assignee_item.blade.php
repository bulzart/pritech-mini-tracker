{{--
    One row in the assignee manager on the issue detail page. Mirrors
    issues/_tag_item. Expects: $issue, $user, $action ('assign'|'detach').

    The attach and detach routes share the same URI (/issues/{issue}/users/{user});
    only the HTTP method differs, which issue-show.js chooses from the action.
--}}
<li
    class="tag-manager__item"
    data-user-id="{{ $user->id }}"
    data-user-name="{{ $user->name }}"
    data-user-email="{{ $user->email }}"
    data-assignee-url="{{ route('issues.users.attach', [$issue, $user]) }}"
>
    <span class="assignee">
        <span class="assignee__name">{{ $user->name }}</span>
        <span class="assignee__email muted">{{ $user->email }}</span>
    </span>
    <button type="button" class="button button--small tag-manager__action" data-assignee-action="{{ $action }}">
        {{ $action === 'detach' ? 'Unassign' : 'Assign' }}
    </button>
</li>
