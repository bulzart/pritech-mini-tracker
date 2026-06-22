{{--
    Priority badge. Usage: @include('issues._priority_badge', ['priority' => $issue->priority])
--}}
<span class="badge badge--priority-{{ $priority }}">{{ $priority }}</span>
